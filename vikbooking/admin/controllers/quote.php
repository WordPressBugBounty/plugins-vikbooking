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
 * VikBooking quote (quotation) controller (admin).
 *
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VikBookingControllerQuote extends JControllerAdmin
{
    /**
     * AJAX endpoint to save a new quotation.
     * 
     * @return  void
     */
    public function save()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // gather request values
        $quote = $app->input->get('quote', [], 'array');
        $sessionId = $app->input->getUInt('session_id', 0);

        if (empty($quote['customer']['id']) && empty($quote['customer']['first_name'])) {
            // missing customer data
            VBOHttpDocument::getInstance($app)->close(400, 'Missing customer information.');
        }

        // filter out invalid booking solutions
        $quote['solutions'] = array_values(array_filter((array) ($quote['solutions'] ?? []), function($solution) {
            if (empty($solution['checkin']) || empty($solution['checkout']) || empty($solution['rooms']) || !is_array($solution['rooms'])) {
                // missing stay dates or booking rooms
                return false;
            }

            foreach ($solution['rooms'] as $roomSol) {
                if (empty($roomSol['id'])) {
                    // missing room ID
                    return false;
                }
                if ((empty($roomSol['id_price']) || empty($roomSol['room_cost'])) && empty($roomSol['cust_cost'])) {
                    // missing room rate, either a rate plan or a custom rate
                    return false;
                }
                if (empty($roomSol['adults']) && empty($roomSol['children'])) {
                    // invalid guest party
                    return false;
                }
            }

            return true;
        }));

        if (empty($quote['solutions'])) {
            // missing booking solutions
            VBOHttpDocument::getInstance($app)->close(400, 'No valid booking solutions.');
        }

        // access customer model
        $customerModel = VBOMvcModel::getInstance('customer');

        // build customer information
        $customerInfo = [
            'first_name' => $quote['customer']['first_name'],
            'last_name'  => ($quote['customer']['last_name'] ?? '') ?: '(Quote)',
            'email'      => $quote['customer']['email'] ?? null,
            'phone'      => $quote['customer']['phone'] ?? null,
            'country'    => $quote['customer']['country'] ?? null,
        ];

        if (!empty($quote['customer']['id'])) {
            // make sure the given customer exists
            $customer = $customerModel->getItem((int) $quote['customer']['id']);
            if (!$customer) {
                // abort on error
                VBOHttpDocument::getInstance($app)->close(404, 'Customer ID not found.');
            }
            // assign customer ID and information
            $customerId = $customer->id;
            $customerInfo = (array) $customer;
        } else {
            // save new customer or update existing
            $customerId = $customerModel->save($customerInfo);
        }

        // access quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // save quote record
        $quoteId = $quoteModel->save([
            'name'           => ($quote['name'] ?? '') ?: date('Y-m-d H:i:s'),
            'subject'        => $quote['message']['subject'] ?? null,
            'message'        => $quote['message']['content'] ?? null,
            'notes'          => $quote['message']['notes'] ?? null,
            'valid_until'    => $quote['validity'] ?? null,
            'idcustomer'     => $customerId ?: null,
            'first_name'     => $quote['customer']['first_name'],
            'last_name'      => $quote['customer']['last_name'] ?? null,
            'email'          => $quote['customer']['email'] ?? null,
            'phone'          => $quote['customer']['phone'] ?? null,
            'country_3_code' => $quote['customer']['country'] ?? null,
            'preferred'      => !empty($quote['message']['preferred']) ? 1 : 0,
            'sent'           => !empty($quote['message']['send']) ? 1 : 0,
        ]);

        if (!$quoteId) {
            // raise an error
            VBOHttpDocument::getInstance($app)->close(500, sprintf('Could not save quote record: %s.', (string) ($quoteModel->getError() ?: '---')));
        }

        foreach ($quote['solutions'] as $index => $solution) {
            // access a new instance of the reservation model
            $resModel = VBOModelReservation::getInstance([
                'status'     => 'standby',
                'type'       => 'quote',
                'checkin'    => strtotime($solution['checkin']),
                'checkout'   => strtotime($solution['checkout']),
                'num_rooms'  => count($solution['rooms']),
                'adults'     => array_sum(array_column($solution['rooms'], 'adults')),
                'children'   => array_sum(array_column($solution['rooms'], 'children')),
                'id_payment' => $quote['id_payment'] ?? null,
                'lock_until' => $quote['validity'] ?? null,
                'idquote'    => $quoteId,
            ], true);

            // set booking customer
            $resModel->setCustomer([
                'id'         => $customerId,
                'first_name' => $customerInfo['first_name'],
                'last_name'  => $customerInfo['last_name'],
                'email'      => $customerInfo['email'],
                'country'    => $customerInfo['country'],
                'phone'      => $customerInfo['phone'],
            ]);

            // scan all booking solution rooms
            $roomSolutions = [];
            foreach ($solution['rooms'] as $roomSolution) {
                // check if we have a custom cancellation policy ID to apply
                $custCancPolicyId = null;
                if (!empty($roomSolution['cust_cost']) && !empty($roomSolution['id_price'])) {
                    // apply the selected rate plan for the custom cancellation policy
                    $custCancPolicyId = $roomSolution['id_price'];
                }

                // push booking solution room
                $roomSolutions[] = [
                    'id'              => $roomSolution['id'],
                    'adults'          => $roomSolution['adults'] ?? null,
                    'children'        => $roomSolution['children'] ?? null,
                    'id_price'        => $roomSolution['id_price'] ?? null,
                    'room_cost'       => $roomSolution['room_cost'] ?? null,
                    'cust_cost'       => $roomSolution['cust_cost'] ?? null,
                    'id_tax'          => $roomSolution['id_tax'] ?? null,
                    'cust_cpolicy_id' => $custCancPolicyId,
                    'options'         => $roomSolution['options'] ?? null,
                    'extras'          => $roomSolution['extras'] ?? null,
                ];
            }

            // set all booking rooms
            $resModel->setRooms($roomSolutions);

            // store the reservation
            $resModel->create();

            // get the new booking ID
            $res_id = $resModel->getNewBookingID();

            if (!$res_id) {
                // delete quote
                $quoteModel->delete($quoteId);

                // abort on error
                VBOHttpDocument::getInstance($app)->close(500, sprintf('Error saving booking solution #%d: %s', ($index + 1), (string) ($resModel->getError() ?: '---')));
            }
        }

        // check if the quote should be sent via email
        $sendResultErr = null;
        if (!empty($quote['message']['send'])) {
            // send the quotation to the guest via email
            try {
                $quoteModel->sendEmail($quoteId);
            } catch (Exception $e) {
                // catch the error message
                $sendResultErr = $e->getMessage();
            }
        }

        // check if the quote was originated from a chat session
        if ($sessionId) {
            try {
                // link the newly generated quote to the initial chat session
                (new VBOChatSessionModel)->setMetadata($sessionId, 'quote_id', $quoteId);
            } catch (Exception $e) {
                // do nothing on error
            }
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => true,
            'idquote' => $quoteId,
            'sending_error' => $sendResultErr,
        ]);
    }

    /**
     * AJAX endpoint to update a quotation.
     * 
     * @return  void
     */
    public function update()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // gather request values
        $quote = $app->input->get('quote', [], 'array');

        if (empty($quote['id'])) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing quote ID to update.');
        }

        // access quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // get requested quote record
        $quoteRecord = $quoteModel->getItem($quote['id']);

        if (!$quoteRecord) {
            VBOHttpDocument::getInstance($app)->close(404, 'Quote record not found.');
        }

        // update record
        $result = $quoteModel->save([
            'id'             => $quoteRecord->id,
            'name'           => $quote['name'] ?? null,
            'subject'        => $quote['message']['subject'] ?? null,
            'message'        => $quote['message']['content'] ?? null,
            'notes'          => $quote['message']['notes'] ?? null,
            'valid_until'    => $quote['validity'] ?? null,
            'idcustomer'     => $quote['customer']['id'] ?? null,
            'first_name'     => $quote['customer']['first_name'] ?? null,
            'last_name'      => $quote['customer']['last_name'] ?? null,
            'email'          => $quote['customer']['email'] ?? null,
            'phone'          => $quote['customer']['phone'] ?? null,
            'country_3_code' => $quote['customer']['country'] ?? null,
            'preferred'      => !empty($quote['message']['preferred']) ? 1 : 0,
        ]);

        if (!$result) {
            // abort
            VBOHttpDocument::getInstance($app)->close(500, 'Could not update quote record.');
        }

        // check if we have a different validity date for the quote
        if (!empty($quote['validity']) && JFactory::getDate($quote['validity'], $app->get('offset'))->toSql() != $quoteRecord->valid_until) {
            // validity date has changed, booking solutions must be updated
            $quoteData = $quoteModel->loadBookingRecords(0, 0, [
                'filters' => [
                    'id' => $quoteRecord->id,
                ],
            ])[0] ?? null;

            if (!$quoteData) {
                // abort
                VBOHttpDocument::getInstance($app)->close(500, 'Quote record missing booking solutions.');
            }

            // extract confirmed booking solutions
            $confirmedBookings = array_filter($quoteData->solutions ?? [], function($solution) {
                return ($solution->status ?? '') == 'confirmed';
            });

            // extract pending booking solutions
            $pendingBookings = array_filter($quoteData->solutions ?? [], function($solution) {
                return ($solution->status ?? '') == 'standby';
            });

            // make sure the quote still needs to be confirmed
            if (!$confirmedBookings && $pendingBookings) {
                // determine the timestamp to lock until
                $lockUntilTs = strtotime($quote['validity']);

                // check whether the CM may require to update previously locked records
                if (method_exists('VCMRequestAvailability', 'updateReleaseDate')) {
                    // let the CM update the release date of the involved and unconfirmed booking IDs, if needed
                    VCMRequestAvailability::getInstance()->updateReleaseDate(array_column($pendingBookings, 'id'), $lockUntilTs);
                }

                // access temp-lock model
                $tmpLockModel = VBOMvcModel::getInstance('tmplock');

                // scan all pending reservations
                foreach ($pendingBookings as $pendingBooking) {
                    // delete any previously locked record for this booking
                    $tmpLockModel->deleteFromBooking($pendingBooking->id);
                    // scan all booking rooms
                    foreach ($pendingBooking->rooms as $pendingRoomBooking) {
                        // lock records temporarily until new validity date
                        $tmpLockModel->save([
                            'idroom'   => $pendingRoomBooking->id,
                            'checkin'  => $pendingBooking->checkin,
                            'checkout' => $pendingBooking->checkout,
                            'until'    => $lockUntilTs,
                            'realback' => $pendingBooking->checkout,
                            'idorder'  => $pendingBooking->id,
                        ]);
                    }
                    if (VikBooking::vcmAutoUpdate() > 0) {
                        // silently trigger an availability update request for this pending booking
                        $vcm_obj = VikBooking::getVcmInvoker();
                        $vcm_obj->setOids([$pendingBooking->id])->setSyncType('new');
                        // ignore the update result
                        $sync_result = $vcm_obj->doSync();
                    }
                }
            }
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => true,
        ]);
    }

    /**
     * AJAX endpoint to delete a quotation.
     * 
     * @return  void
     */
    public function delete()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // gather quotation ID
        $quoteId = $app->input->getUInt('quote_id', 0);

        if (empty($quoteId)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing quote ID to delete.');
        }

        // access quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // get requested quote data
        $quoteData = $quoteModel->loadBookingRecords(0, 0, [
            'filters' => [
                'id' => $quoteId,
            ],
        ])[0] ?? null;

        if (!$quoteData) {
            VBOHttpDocument::getInstance($app)->close(404, 'Quote record not found.');
        }

        // extract confirmed booking solutions
        $confirmedBookings = array_filter($quoteData->solutions ?? [], function($solution) {
            return ($solution->status ?? '') == 'confirmed';
        });

        // extract pending booking solutions
        $pendingBookings = array_filter($quoteData->solutions ?? [], function($solution) {
            return ($solution->status ?? '') == 'standby';
        });

        if ($confirmedBookings) {
            // do not proceed
            VBOHttpDocument::getInstance($app)->close(400, 'Cannot delete quote record because one of its booking solutions is confirmed. Delete reservations first.');
        }

        // access reservation model
        $resModel = VBOModelReservation::getInstance();

        // scan all pending bookings involved, if any
        foreach ($pendingBookings as $pendingBooking) {
            // set reservation status to cancelled
            $resModel->delete([
                'booking_id' => $pendingBooking->id,
                'cancellation_reason' => sprintf('Quote #%d cancelled.', $quoteData->id),
            ]);
        }

        // delete quote record
        $result = $quoteModel->delete($quoteData->id);

        if (!$result) {
            // raise error
            VBOHttpDocument::getInstance($app)->close(500, 'Deleting requested quote record failed.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => true,
        ]);
    }

    /**
     * AJAX endpoint to send a quotation via email.
     * 
     * @return  void
     */
    public function sendMail()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // gather quote ID
        $quoteId = $app->input->getUInt('quote_id', 0);

        if (!$quoteId) {
            // missing quotation ID
            VBOHttpDocument::getInstance($app)->close(400, 'Missing quotation ID.');
        }

        // access quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        try {
            // send the quotation via email
            $quoteModel->sendEmail($quoteId);
        } catch (Exception $e) {
            // propagate the error
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => true,
        ]);
    }

    /**
     * AJAX endpoint to send a quotation via messaging account (i.e. WhatsApp).
     * 
     * @return  void
     */
    public function sendMessaging()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // gather quote ID and template configuration data
        $quoteId   = $app->input->getUInt('quote_id', 0);
        $accountId = $app->input->getString('account_id', '');
        $phoneId   = $app->input->getString('phone_id', '');
        $configId  = $app->input->getUInt('config_id', 0);

        if (!$quoteId) {
            // missing quotation ID
            VBOHttpDocument::getInstance($app)->close(400, 'Missing quotation ID.');
        }

        if (empty($accountId) || empty($phoneId)) {
            // missing account data
            VBOHttpDocument::getInstance($app)->close(400, 'Missing messaging account information.');
        }

        // access quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // get current quote and related booking record(s)
        $quoteData = $quoteModel->loadBookingRecords(0, 0, [
            'filters' => [
                'id' => $quoteId,
            ],
        ])[0] ?? null;

        if (!$quoteData) {
            // quotation not found
            VBOHttpDocument::getInstance($app)->close(404, 'Quotation not found.');
        }

        // access booking registry through the first quote booking solution
        try {
            $booking = VBOBookingRegistry::getInstance(['id' => $quoteData->solutions[0]->id ?? null]);
        } catch (Exception $e) {
            // propagate error
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 404, 'Could not load quote booking solution.');
        }

        // get recipient (guest) phone number
        $phoneNumber = ($quoteData->phone ?? null) ?: $booking->getPhoneNumber();
        if (!$phoneNumber) {
            // unable to proceed without a recipient
            VBOHttpDocument::getInstance($app)->close(400, 'Missing guest recipient phone number.');
        }

        if (!class_exists('VCMMessagingAccountsModel')) {
            // unsupported request
            VBOHttpDocument::getInstance($app)->close(400, 'Missing Channel Manager with messaging accounts.');
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
                'recipient'             => $phoneNumber,
                'account'               => $accountRecord,
                'message_template'      => $configId,
                'quote_data'            => $quoteData, 
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

        // flag record and "sent"
        $quoteModel->save([
            'id'   => $quoteId,
            'sent' => 1,
        ]);

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => true,
            'recipient' => $phoneNumber,
        ]);
    }

    /**
     * AJAX endpoint to toggle or update the preferred status of a quotation.
     * 
     * @return  void
     */
    public function togglePreferred()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // gather quote ID and optional preferred status
        $quoteId = $app->input->getUInt('quote_id', 0);
        $status  = $app->input->get('status', null);

        if (!$quoteId) {
            // missing quotation ID
            VBOHttpDocument::getInstance($app)->close(400, 'Missing quotation ID.');
        }

        // access quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // get current record
        $quoteRecord = $quoteModel->getItem($quoteId);

        if (!$quoteRecord) {
            // quotation not found
            VBOHttpDocument::getInstance($app)->close(404, 'Quotation not found.');
        }

        // determine new status
        $preferredStatus = $quoteRecord->preferred ? 0 : 1;
        if ($status !== null) {
            $preferredStatus = (int) boolval($status);
        }

        // update record
        $result = $quoteModel->save([
            'id'        => $quoteRecord->id,
            'preferred' => $preferredStatus,
        ]);

        if (!$result) {
            // abort
            VBOHttpDocument::getInstance($app)->close(500, 'Could not update quote record.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'success' => true,
        ]);
    }

    /**
     * AJAX endpoint to get a list of messaging template configurations for a given quote.
     * 
     * @return  void
     */
    public function getMessagingConfigurations()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // gather quote ID
        $quoteId = $app->input->getUInt('quote_id', 0);

        if (!$quoteId) {
            // missing quotation ID
            VBOHttpDocument::getInstance($app)->close(400, 'Missing quotation ID.');
        }

        // access quote model
        $quoteModel = VBOMvcModel::getInstance('quote');

        // get current record
        $quoteRecord = $quoteModel->getItem($quoteId);

        if (!$quoteRecord) {
            // quotation not found
            VBOHttpDocument::getInstance($app)->close(404, 'Quotation not found.');
        }

        if (!class_exists('VCMMessagingAccountsModel')) {
            // unsupported feature
            VBOHttpDocument::getInstance($app)->close(400, 'Missing required Channel Manager and Messaging services.');
        }

        // load messaging account configurations data by injecting the current quote
        $configurationsData = VCMMessagingAccountsModel::getInstance()->getConfigurationsData(
            new VCMMessagingTemplateDecoratorQuote($quoteId)
        );

        if (!$configurationsData) {
            // abort
            VBOHttpDocument::getInstance($app)->close(400, 'Missing configuration for Channel Manager messaging services. No messaging templates found.');
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json([
            'data' => $configurationsData,
        ]);
    }
}
