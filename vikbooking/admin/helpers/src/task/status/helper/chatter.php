<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Chatter helper trait to use for status change behaviors.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
trait VBOTaskStatusHelperChatter
{
    /**
     * Sends a chat message.
     * 
     * @param   int                  $taskId
     * @param   string               $body
     * @param   VBOChatAttachment[]  $attachments
     * 
     * @return  void
     */
    public function sendMessage(int $taskId, string $body, array $attachments = [])
    {
        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            /** @var VBOChatMessage */
            $message = $chat->createMessage([
                'context' => 'task',
                'id_context' => $taskId,
                'message' => $body,
                'attachments' => $attachments,
            ]);

            // deliver the message
            $chat->send($message);
        } catch (Exception $error) {
            // unable to send the message... ignore and go ahead
        }
    }
}
