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
 * Chat web push notification trait.
 * 
 * @since 1.8
 */
trait VBOChatNotificationWebpush
{
    /**
     * Enqueues a new messages within the notification center.
     * 
     * @param   VBOChatMessage  $message  The message instance.
     * @param   VBOChatUser     $user     The user that sent the message.
     * 
     * @return  bool
     */
    public function sendWebPushNotification(VBOChatMessage $message, VBOChatUser $user)
    {
        // display the whole message as summary
        $summary = (string) $message->getMessage();

        if (strlen($summary) === 0) {
            // no message provided, display the number of attached files
            $attachmentsCount = count($message->getAttachments());

            if ($attachmentsCount === 1) {
                // only one attachment
                $summary = JText::sprintf('VBO_CHAT_MESSAGE_WEBPUSH_NOTIFICATION_SUMMARY_N_FILES_1', $message->getSenderName());
            } else {
                // multiple attachments
                $summary = JText::plural('VBO_CHAT_MESSAGE_WEBPUSH_NOTIFICATION_SUMMARY_N_FILES', $message->getSenderName(), $attachmentsCount);
            }
        } else {
            // message provided
            $summary = JText::sprintf('VBO_CHAT_MESSAGE_WEBPUSH_NOTIFICATION_SUMMARY', $message->getSenderName(), $summary);
        }

        try {
            // store the notification record
            VBOFactory::getNotificationCenter()->store([
                [
                    'sender' => 'operators',
                    'type' => 'chat.newmessage',
                    'title' => JText::sprintf('VBO_CHAT_MESSAGE_WEBPUSH_NOTIFICATION_TITLE', $message->getContext()->getSubject()),
                    'summary' => $summary,
                    'label' => JText::translate('VBO_REPLY'),
                    'avatar' => $user->getAvatar(),
                    'widget' => 'operators_chat',
                    'widget_options' => [
                        'context_alias' => $message->getContext()->getAlias(),
                        'context_id' => $message->getContext()->getID(),
                    ],
                    // always skip signature check, so that we can allow a duplicate insert
                    '_signature' => md5(time()),
                ],
            ]);
        } catch (Exception $e) {
            // silently catch the error
            return false;
        }

        return true;
    }
}
