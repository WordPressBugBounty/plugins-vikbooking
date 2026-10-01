<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Chat APP push notification trait.
 * 
 * @since 1.8.15
 */
trait VBOChatNotificationApp
{
    /**
     * Schedules a notification for the specified device account.
     * 
     * @param   VBOChatMessage  $message        The message instance.
     * @param   string          $deviceAccount  The account (email or "admin") of the device to notify.
     * 
     * @return  bool
     */
    public function scheduleAppNotification(VBOChatMessage $message, string $deviceAccount)
    {
        // do not notify messages wrote from the front-end chat
        if ($message->getContext()->getAlias() === 'session') {
            return false;
        }

        // make sure VCM exposes an object to dispatch notifications
        if (!method_exists('VCMFactory', 'getContainer')) {
            // required VCM 1.9.27
            return false;
        }

        /** @var VBOChatContext */
        $context = $message->getContext();

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
        }

        // prepare notification data
        $data = [
            'type' => 'Messaging',
            'from' => [
                'id' => $message->getSenderID(),
                'name' => $message->getSenderName(),
            ],
            'context' => [
                'alias' => $context->getAlias(),
                'id' => $context->getID(),
            ],
            'preview' => $summary,
        ];

        // schedule notification delivery to the involved devices
        VCMFactory::getContainer()->get('vikchannelmanager.pnd.lazy')->sendNotification(
            new E4J\VCM\PND\Notification($data, $deviceAccount)
        );

        return true;
    }
}
