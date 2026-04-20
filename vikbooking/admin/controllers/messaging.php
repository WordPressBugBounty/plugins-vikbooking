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
 * VikBooking messaging controller (admin).
 *
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VikBookingControllerMessaging extends JControllerAdmin
{
    /**
     * AJAX endpoint to send a message template through a CM messaging account.
     * 
     * @return  void
     */
    public function sendMessageTemplate()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $accountId = $app->input->getString('account_id', '');
        $phoneId   = $app->input->getString('phone_id', '');
        $configId  = $app->input->getUInt('config_id', 0);
        $bookingId = $app->input->getUInt('booking_id', 0);

        if (empty($accountId) || empty($phoneId)) {
            // missing account data
            VBOHttpDocument::getInstance($app)->close(400, 'Missing messaging account information.');
        }

        // access registry for the given booking ID
        try {
            $booking = VBOBookingRegistry::getInstance(['id' => $bookingId]);
        } catch (Exception $e) {
            // propagate error
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 404, $e->getMessage());
        }

        // get recipient (guest) phone number
        $phoneNumber = $booking->getPhoneNumber();
        if (!$phoneNumber) {
            // unable to proceed without a recipient
            VBOHttpDocument::getInstance($app)->close(400, 'Missing guest recipient phone number.');
        }

        // access messaging accounts model
        $maModel = VCMMessagingAccountsModel::getInstance();

        // fetch requested messaging account record
        $accountRecord = $maModel->getItem([
            'account_id' => $accountId,
            'phone_id'   => $phoneId,
        ]);

        if (!$accountRecord || empty($accountRecord->settings['configurations'][$configId])) {
            VBOHttpDocument::getInstance($app)->close(404, 'Could not find the requested messaging account.');
        }

        // load channel details
        $channelDetails = VikChannelManager::getChannel($accountRecord->idchannel);
        if (!$channelDetails) {
            VBOHttpDocument::getInstance($app)->close(404, 'Messaging channel not found.');
        }

        // register booking messaging account provider
        $maModel->setBookingAccountProvider($booking, $accountRecord);

        // access chat handler first
        $chatHandler = VikBooking::getVcmChatInstance($booking->getID(), $channelDetails['name']);

        if (!$chatHandler) {
            VBOHttpDocument::getInstance($app)->close(500, 'Could not invoke proper chat handler.');
        }

        // build chat message object
        $chatMessage = new VCMChatMessage(
            // no message content
            '',
            // no attachments
            [],
            // data to bind
            [
                'recipient' => $phoneNumber,
                'account' => $accountRecord,
                'message_template' => $configId,
                'mark_previous_replied' => true,
            ]
        );

        // inject thread ID with recipient phone number
        $chatMessage->set('idthread', $phoneNumber);

        try {
            // send message
            if (!$chatHandler->send($chatMessage)) {
                throw new Exception($chatHandler->getError() ?: 'Could not send the message template to recipient phone number.', 500);
            }
        } catch (Exception $e) {
            // propagate error
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => true,
        ]);
    }
}
