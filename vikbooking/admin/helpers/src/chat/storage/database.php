<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Chat messages input/output database handler.
 * 
 * @since 1.8
 */
class VBOChatStorageDatabase implements VBOChatStorage
{
    /** @var JDatabaseDriver */
    protected $db;

    /**
     * Class constructor.
     * 
     * @param  object|null  $db  The database driver.
     */
    public function __construct($db = null)
    {
        $this->db = $db ?: JFactory::getDbo();
    }

    /**
     * @inheritDoc
     */
    public function getMessages(VBOChatSearch $search)
    {
        $query = $this->db->getQuery(true);

        $query->select('m.*');
        $query->from($this->db->qn('#__vikbooking_chat_messages', 'm'));

        if ($search->hasReader()) {
            // join with the table holding the notifications to read
            $query->select('IF(' . $this->db->qn('r.id') . ' IS NULL, 1, 0) AS ' . $this->db->qn('read'));
            $query->leftjoin($this->db->qn('#__vikbooking_chat_messages_unread', 'r')
                . ' ON ' . $this->db->qn('r.id_message') . ' = ' . $this->db->qn('m.id')
                . ' AND ' . $this->db->qn('r.id_sender') . ' = ' . (int) $search->getReader());
        } else {
            // treat "read" column as unavailable
            $query->select('NULL AS ' . $this->db->qn('read'));
        }

        if ($search->hasUnread()) {
            // take only the unread messages
            $query->having($this->db->qn('read') . ' = 0');
        }
        
        if ($search->hasMessage()) {
            $msg = $search->getMessage();

            // filter by message ID
            $query->where($this->db->qn('m.id') . ' ' . $msg->operator  . ' ' . (int) $msg->id);
        }

        if ($search->hasSender()) {
            $sender = $search->getSender();

            // filter by sender ID
            $query->where($this->db->qn('m.id_sender') . ' ' . $sender->operator  . ' ' . (int) $sender->id);
        }

        if ($search->hasDate()) {
            $date = $search->getDate();

            // filter by date
            $query->where($this->db->qn('m.createdon') . ' ' . $date->operator  . ' ' . $this->db->q($date->utc));
        }

        if ($search->hasContext()) {
            // filter by context
            $query->where($this->db->qn('m.context') . ' = ' . $this->db->q($search->getContext()->getAlias()));
            $query->where($this->db->qn('m.id_context') . ' = ' . (int) $search->getContext()->getID());
        }

        if ($search->hasAggregate()) {
            $inner = $this->db->getQuery(true);

            // create inner query to fetch the latest message for each "thread"
            $inner->select($this->db->qn('t.id_context'));
            $inner->select($this->db->qn('t.context'));
            // $inner->select('MAX(' . $this->db->qn('t.createdon') . ') AS ' . $this->db->qn('maxdate'));
            $inner->select('MAX(' . $this->db->qn('t.id') . ') AS ' . $this->db->qn('id'));

            $inner->from($this->db->qn('#__vikbooking_chat_messages', 't'));

            $inner->group($this->db->qn('t.id_context'));
            $inner->group($this->db->qn('t.context'));

            // aggregate messages by "thread"
            $query->innerjoin(
                '(' . $inner . ') AS ' . $this->db->qn('latest')
                . ' ON ' . $this->db->qn('latest.id_context') . ' = ' . $this->db->qn('m.id_context')
                . ' AND ' . $this->db->qn('latest.context') . ' = ' . $this->db->qn('m.context')
                . ' AND ' . $this->db->qn('latest.id') . ' = ' . $this->db->qn('m.id')
            );
        }

        // always use a descending order (from the newest one to the oldest one)
        $query->order($this->db->qn('m.createdon') . ' DESC');
        $query->order($this->db->qn('m.id') . ' DESC');

        // apply the pagination bounds
        $this->db->setQuery($query, $search->getStart(), $search->getLimit());
        $messages = $this->db->loadObjectList();

        foreach ($messages as $message) {
            // unserialize attachments for each message
            $message->attachments = (array) @unserialize($message->attachments);
        }

        return $messages;
    }

    /**
     * @inheritDoc
     */
    public function getMessage(int $messageId, VBOChatContext $context)
    {
        // defines message search
        $search = (new VBOChatSearch)
            ->message($messageId)
            ->withContext($context)
            ->limit(1);

        // obtain all the matching messages
        $messages = $this->getMessages($search);

        // return the first one available, null in case of no matches
        return array_shift($messages);
    }

    /**
     * @inheritDoc
     */
    public function saveMessage(VBOChatMessage $message)
    {
        $isNew = !$message->getID();

        // prepare record to save
        $data = (object) $message->jsonSerialize();
        $data->attachments = serialize($data->attachments);

        // unset read information
        unset($data->read);

        if ($isNew) {
            // insert a new message
            $result = $this->db->insertObject('#__vikbooking_chat_messages', $data, 'id');
        } else {
            // update the existing message
            $result = $this->db->updateObject('#__vikbooking_chat_messages', $data, 'id');
        }

        if (!$result || empty($data->id)) {
            // query failed or message ID empty
            throw new \UnexpectedValueException('Unable to save the specified chat message.', 500);
        }

        if ($isNew) {
            // inject ID within the message properties
            $message->bind(['id' => (int) $data->id]);

            // iterate all the users that should receive a notification
            foreach ($message->getContext()->getRecipients() as $recipient) {
                if ($message->getSenderID() == $recipient->getID()) {
                    // do not notify myself
                    continue;
                }

                // register unread notification
                $unreadMessage = new stdClass;
                $unreadMessage->id_message = $message->getID();
                $unreadMessage->id_sender = (int) $recipient->getID();
                $this->db->insertObject('#__vikbooking_chat_messages_unread', $unreadMessage, 'id');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function readMessage(int $messageId, int $userId = 0)
    {
        $query = $this->db->getQuery(true);

        // delete notification record from the database
        $query->delete($this->db->qn('#__vikbooking_chat_messages_unread'));
        $query->where($this->db->qn('id_message') . ' = ' . $messageId);
        $query->where($this->db->qn('id_sender') . ' = ' . $userId);

        $this->db->setQuery($query);
        $this->db->execute();
    }
}
