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
 * This class holds the information of a generic chat message.
 * 
 * @since 1.8
 */
class VBOChatMessage implements JsonSerializable
{
    /**
     * The context of the message.
     * 
     * @var VBOChatContext 
     */
    protected $context;

    /**
     * The message identifier.
     * 
     * @var int
     */
    protected $id;

    /**
     * The name of the user that sent the message.
     * 
     * @var string
     */
    protected $sender_name;

    /**
     * The ID of the user that sent the message.
     * When empty, the sender will be equal to the administrator.
     * 
     * @var int
     */
    protected $id_sender;

    /**
     * The content of the message.
     * 
     * @var string
     */
    protected $message;

    /**
     * An array of attached files.
     * 
     * @var VBOChatAttachment[]
     */
    protected $attachments = [];

    /**
     * The creation date of the message.
     * 
     * @var string
     */
    protected $createdon;

    /**
     * The ID of the user that created the message.
     * 
     * @var int
     */
    protected $createdby;

    /**
     * Whether the message has been read by the user.
     * 
     * @var bool
     */
    protected $read = true;

    /**
     * Class constructor.
     * 
     * @param  VBOChatContext     $context  The message context.
     * @param  array|object|null  $data     The message details.
     */
    public function __construct(VBOChatContext $context, $data = null)
    {
        $this->context = $context;

        if ($data) {
            $this->bind($data);
        }
    }

    /**
     * Binds the message properties with the provided data.
     * 
     * @param   array|object  $data  The message data.
     * 
     * @return  self  This object to support chaining.
     */
    public function bind($data)
    {
        if (!is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException('Cannot bind chat message! Array or object expected, ' . gettype($data) . ' given.', 400);
        }

        foreach ($data as $k => $v) {
            // never update the context of the message
            if (!strcasecmp($k, 'context')) {
                continue;
            }

            if (!strcasecmp($k, 'attachments')) {
                // set attachments accordingly
                $this->setAttachments((array) $v);
            } else if (!strcasecmp($k, 'createdon')) {
                // set creation date accordingly
                $this->setCreationDate($v);
            } else if (property_exists($this, $k)) {
                // inject value only in case the property actually exists
                $this->{$k} = $v;
            }
        }

        return $this;
    }

    /**
     * Returns the context of the message.
     * 
     * @return  VBOChatContext
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns the ID the message.
     * 
     * @return  int
     */
    public function getID()
    {
        return (int) $this->id;
    }

    /**
     * Returns the name of the user that wrote the message.
     * 
     * @return  string
     */
    public function getSenderName()
    {
        return (string) $this->sender_name;
    }

    /**
     * Returns the ID of the sender.
     * When the value is equal to 0, the message is sent by an administrator.
     * 
     * @return  int
     */
    public function getSenderID()
    {
        return (int) $this->id_sender;
    }

    /**
     * Sets the sender information.
     * 
     * @param   string  $name  The name of the sender.
     * @param   int     $id    The ID of the sender (0 for admin).
     * 
     * @return  self
     */
    public function setSender(string $name, int $id = 0)
    {
        $this->sender_name = $name;
        $this->id_sender = $id;

        return $this;
    }

    /**
     * Returns the text of the message.
     * 
     * @return  string
     */
    public function getMessage()
    {
        return (string) $this->message;
    }

    /**
     * Sets the text of the message.
     * 
     * @param   string  $message
     * 
     * @return  self
     */
    public function setMessage(string $message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Returns an array of files.
     * 
     * @return  array
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Sets the specified files as attachments.
     * 
     * @param   VBOChatAttachment[]  $attachments
     * 
     * @return  self
     */
    public function setAttachments(array $attachments)
    {
        $this->attachments = [];

        foreach ($attachments as $attachment) {
            if ($attachment instanceof VBOChatAttachment) {
                $this->addAttachment($attachment);
            }
        }

        return $this;
    }

    /**
     * Adds a new file as attachment.
     * 
     * @param   VBOChatAttachment  $attachment
     * 
     * @return  self
     * 
     * @throws  InvalidArgumentException
     */
    public function addAttachment(VBOChatAttachment $attachment)
    {
        // make sure the file exists
        if (!$attachment->exists()) {
            throw new \InvalidArgumentException('The attached file does not exist: ' . $attachment->getName(), 404);
        }

        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Returns the creation date of the message (UTC).
     * 
     * @return  string
     */
    public function getCreationDate()
    {
        if (!$this->createdon) {
            $this->createdon = JFactory::getDate()->toSql();
        }

        return $this->createdon;
    }

    /**
     * Sets the creation date of the message (UTC).
     * 
     * @param   mixed  $date  Either a date string or a DateTime object.
     * 
     * @return  self
     */
    public function setCreationDate($date)
    {
        try {
            if (is_string($date)) {
                // make sure the provided date is correct
                $date = JFactory::getDate($date);
            }

            // convert date object into a string
            $date = $date->toSql();
        } catch (Exception $error) {
            // malformed date
            $date = null;
        }

        $this->createdon = $date;

        return $this;
    }

    /**
     * Returns the user ID of the message author.
     * 
     * @return  int
     */
    public function getAuthor()
    {
        if (!$this->createdby) {
            $this->createdby = \JFactory::getUser()->id;
        }

        return $this->createdby;
    }

    /**
     * Checks whether the message has been read by the current user.
     * 
     * @return bool
     */
    public function isRead()
    {
        return (bool) $this->read;
    }

    /**
     * @inheritDoc
     *
     * @see JsonSerializable
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return [
            'id' => $this->getID(),
            'context' => $this->getContext()->getAlias(),
            'id_context' => $this->getContext()->getID(),
            'sender_name' => $this->getSenderName(),
            'id_sender' => $this->getSenderID(),
            'message' => $this->getMessage(),
            'attachments' => $this->getAttachments(),
            'createdon' => $this->getCreationDate(),
            'createdby' => $this->getAuthor(),
            'read' => $this->isRead(),
        ];
    }
}
