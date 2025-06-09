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
 * Encpsulates the supported search techniques.
 * 
 * @since 1.8
 */
class VBOChatSearch
{
    /**
     * The pagination offset.
     * 
     * @var int|null
     */
    protected $start = null;

    /**
     * The pagination limit.
     * 
     * @var int|null
     */
    protected $limit = null;

    /**
     * Searches the messages by context.
     * 
     * @var VBOChatContext|null
     */
    protected $context = null;

    /**
     * Searches the messages by ID.
     * The object contains the "id" and "operator" properties.
     * 
     * @var object|null
     */
    protected $message = null;

    /**
     * Searches the messages by sender ID.
     * The object contains the "id" and "operator" properties.
     * 
     * @var object|null
     */
    protected $sender = null;

    /**
     * Searches the messages by date.
     * The object contains the "utc" and "operator" properties.
     * 
     * @var object|null
     */
    protected $date = null;

    /**
     * The ID of the user that is requesting the search.
     * 
     * @var int|null
     */
    protected $readerId = null;

    /**
     * Whether the system should return only the unread messages.
     * 
     * @var bool
     */
    protected $unread = false;

    /**
     * Whether the results should be aggregated per message.
     * 
     * @var bool
     */
    protected $aggregate = false;

    /**
     * Sets the specified pagination offset.
     * 
     * @param   int  $start
     * 
     * @return  self
     */
    public function start(?int $start)
    {
        $this->start = is_null($start) ? null : abs($start);

        return $this;
    }

    /**
     * Checks whether a custom start position has been specified.
     * 
     * @return  bool
     */
    public function hasStart()
    {
        return $this->start !== null;
    }

    /**
     * Returns the specified pagination offset (0 if not provided).
     * 
     * @return  int
     */
    public function getStart()
    {
        return $this->start ?: 0;
    }

    /**
     * Sets the specified pagination limit.
     * 
     * @param   int|null  $limit
     * 
     * @return  self
     */
    public function limit(?int $limit)
    {
        $this->limit = is_null($limit) ? null : abs($limit);

        return $this;
    }

    /**
     * Checks whether a custom limit has been specified.
     * 
     * @return  bool
     */
    public function hasLimit()
    {
        return $this->limit !== null;
    }

    /**
     * Returns the specified pagination limit (0 if not provided).
     * 
     * @return  int|null
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Sets the specified context parent.
     * 
     * @param   VBOChatContext  $context
     * 
     * @return  self
     */
    public function withContext(?VBOChatContext $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Checks whether a custom context has been specified.
     * 
     * @return  bool
     */
    public function hasContext()
    {
        return $this->context !== null;
    }

    /**
     * Returns the specified context parent, if any.
     * 
     * @return  VBOChatContext|null
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sets the specified message ID.
     * 
     * @param   int     $messageId  The ID of the message.
     * @param   string  $operator   Used to set the comparison operator.
     * 
     * @return  self
     */
    public function message(?int $messageId, string $operator = '=')
    {
        if ($messageId !== null) {
            $this->message = new stdClass;
            $this->message->id = $messageId;
            $this->message->operator = $operator;
        } else {
            $this->message = null;
        }

        return $this;
    }

    /**
     * Checks whether a custom message has been specified.
     * 
     * @return  bool
     */
    public function hasMessage()
    {
        return $this->message !== null;
    }

    /**
     * Returns the specified message, if any.
     * 
     * @return  object|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the specified sender ID.
     * 
     * @param   int   $senderId  The ID of the sender.
     * @param   bool  $equals    Whether the search should match the ID.
     * 
     * @return  self
     */
    public function sender(?int $senderId, bool $equals = true)
    {
        if ($senderId !== null) {
            $this->sender = new stdClass;
            $this->sender->id = $senderId;
            $this->sender->operator = $equals ? '=' : '<>';
        } else {
            $this->sender = null;
        }

        return $this;
    }

    /**
     * Checks whether a custom sender has been specified.
     * 
     * @return  bool
     */
    public function hasSender()
    {
        return $this->sender !== null;
    }

    /**
     * Returns the specified sender, if any.
     * 
     * @return  object|null
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Sets the specified date.
     * 
     * @param   mixed   $date       The search date.
     * @param   string  $operator   Used to set the comparison operator.
     * 
     * @return  self
     */
    public function date($date, string $operator = '=')
    {
        if ($date !== null) {
            if (is_scalar($date)) {
                $date = JFactory::getDate($date)->toSql();
            }

            $this->date = new stdClass;
            $this->date->utc = $date;
            $this->date->operator = $operator;
        } else {
            $this->date = null;
        }

        return $this;
    }

    /**
     * Checks whether a custom date has been specified.
     * 
     * @return  bool
     */
    public function hasDate()
    {
        return $this->date !== null;
    }

    /**
     * Returns the specified date, if any.
     * 
     * @return  object|null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Sets the specified reader ID.
     * 
     * @param   int|null  $readerId
     * 
     * @return  self
     */
    public function reader(?int $readerId)
    {
        $this->readerId = is_null($readerId) ? null : abs($readerId);

        return $this;
    }

    /**
     * Checks whether a custom reader has been specified.
     * 
     * @return  bool
     */
    public function hasReader()
    {
        return $this->readerId !== null;
    }

    /**
     * Returns the specified reader.
     * 
     * @return  int|null
     */
    public function getReader()
    {
        return $this->readerId;
    }

    /**
     * Sets whether only unread messages should be returned.
     * 
     * @param   bool  $unread
     * 
     * @return  self
     */
    public function unread(bool $unread = true)
    {
        $this->unread = $unread;

        return $this;
    }

    /**
     * Checks whether only unread messages should be returned.
     * 
     * @return  bool
     */
    public function hasUnread()
    {
        return $this->unread;
    }

    /**
     * Sets whether the messages should be aggregated.
     * 
     * @param   bool  $aggregate
     * 
     * @return  self
     */
    public function aggregate(bool $aggregate = true)
    {
        $this->aggregate = $aggregate;

        return $this;
    }

    /**
     * Checks whether the messages should be aggregated.
     * 
     * @return  bool
     */
    public function hasAggregate()
    {
        return $this->aggregate;
    }
}
