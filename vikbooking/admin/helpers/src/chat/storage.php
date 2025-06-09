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
 * Chat messages input/output handler interface.
 * 
 * @since 1.8
 */
interface VBOChatStorage
{
    /**
     * Returns the messages matching the specified search query.
     * 
     * @param   VBOChatSearch  $search
     * 
     * @return  object[]
     */
    public function getMessages(VBOChatSearch $search);

    /**
     * Returns the details of the specified message.
     * 
     * @param   int             $messageId
     * @param   VBOChatContext  $context
     * 
     * @return  object|null
     */
    public function getMessage(int $messageId, VBOChatContext $context);

    /**
     * Saves the provided message.
     * 
     * @param   VBOChatMessage  $message
     * 
     * @return  void
     * 
     * @throws  Exception
     */
    public function saveMessage(VBOChatMessage $message);

    /**
     * Marks the given message as read by the specified user.
     * 
     * @param   int  $messageId  The ID of the message to read.
     * @param   int  $userId     The ID of the user that read the message.
     * 
     * @return  void
     */
    public function readMessage(int $messageId, int $userId = 0);
}
