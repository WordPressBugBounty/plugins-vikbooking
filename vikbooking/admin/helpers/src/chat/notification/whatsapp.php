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
 * Chat WhatsApp notification trait.
 * 
 * @since 1.8.8
 */
trait VBOChatNotificationWhatsapp
{
    /**
     * Sends a WhatsApp notification to the specified session.
     * 
     * @param   VBOChatMessage  $message  The message instance.
     * @param   array           $data     The WhatsApp account details.
     * @param   string          $phone    The recipient phone number.
     * 
     * @return  bool
     */
    public function sendWhatsAppNotification(VBOChatMessage $message, array $data, string $phone)
    {
        // make sure the channel manager is installed
        if (!class_exists('VikChannelManager')) {
            return false;
        }

        // make sure the channel manager is up to date
        if (!defined('VikChannelManagerConfig::WHATSAPP')) {
            return false;
        }

        // create internal logger
        $logger = new VCMLogDriverJsonlines(
            // groups logs by day
            VBO_MEDIA_PATH . '/logs/whatsapp/' . JHtml::fetch('date', 'now', 'Y-m-d') . '.php',
            [
                // auto-delete log files that were updated more than 4 weeks ago
                'gc_threshold' => '-4 weeks',
            ]
        );

        try {
            // make sure WhatsApp is supported
            $channel = VikChannelManager::getChannel(VikChannelManagerConfig::WHATSAPP);

            // make sure the user owns the provided channel
            if (!$channel) {
                throw new Exception("Missing WhatsApp capabilities", 403);
            }

            // fetch WABA record for given ID and phone
            $account = VCMMessagingAccountsModel::getInstance()->getItem([
                'account_id' => $data['waba_id'] ?? null,
                'phone_id' => $data['phone_id'] ?? null,
            ]);

            if (!$account) {
                // WABA not yet configured...
                throw new DomainException('Missing WABA configuration.', 404);
            }

            // convert HTML to WhatsApp markup
            $waText = VCMWhatsappTemplateRenderer::convertHtml($message->getMessage());
            
            // determine the type of message(s) to send
            if (!$message->getAttachments()) {
                // send free-text message
                $result = (new VCMWhatsappModelService($account->account_id, $account->phone_id))
                    ->sendMessage(
                        $phone,
                        $waText,
                        $account
                    );

                // save received payload for debug purposes
                $logger->debug("Free text WhatsApp response.\n\n```\n{response}\n```", [
                    'response' => json_encode($result, JSON_PRETTY_PRINT),
                ]);
            } else {
                // send one media message per attachment
                foreach ($message->getAttachments() as $index => $attachment) {
                    // send media message
                    $result = (new VCMWhatsappModelService($account->account_id, $account->phone_id))
                        ->sendMedia(
                            $phone,
                            $attachment->getUrl(),
                            $index == 0 ? $waText : '',
                            $account,
                        );

                    // save received payload for debug purposes
                    $logger->debug("Media content WhatsApp response.\n\n```\n{response}\n```", [
                        'response' => json_encode($result, JSON_PRETTY_PRINT),
                    ]);
                }
            }

            if (!empty($result->id)) {
                // in case WhatsApp returned an ID for the message, save it for later use
                VBOFactory::getChatMediator()->saveMessage($message->setReferenceID($result->id));
            }
        } catch (Throwable $error) {
            // silently catch the error
            $result = false;

            // safely log the error for debug purposes
            $logger->error("Failed to send a message to **{recipient}** for the following reason.\n> {error} ({code})\n\n**Account**\n- Waba ID: {waba_id}\n- Phone ID: {phone_id}\n\n**Message payload**\n\n```\n{message}\n```", [
                'recipient' => $phone,
                'error' => $error->getMessage(),
                'code' => $error->getCode(),
                'waba_id' => $data['waba_id'],
                'phone_id' => $data['phone_id'],
                'message' => json_encode($message, JSON_PRETTY_PRINT),
            ]);

            // delete message as we don't want to miss WhatsApp notifications
            VBOFactory::getChatMediator()->deleteMessage($message);

            // propagate the exception to enable delivery retries
            throw $error;
        }

        return (bool) $result;
    }
}
