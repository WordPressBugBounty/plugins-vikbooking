<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Notification elements registry (Notification Center) of type "Virtual Credit Card Update".
 * 
 * @since 1.18.6 (J) - 1.8.6 (WP)
 */
class VBONotificationElementsVirtualcreditcardupdate extends VBONotificationElements
{
    /**
     * @inheritDoc
     */
    public function postflight()
    {
        $ota_res_id = $this->getOTAReservationID();
        $channel    = $this->getChannel();
        $data       = (array) $this->get('data', []);

        if (empty($ota_res_id) || empty($channel) || empty($data['activationDate']) || empty($data['balance'])) {
            // no interesting payload data information to process
            return;
        }

        // ensure we received a positive amount (VCC balance)
        $vccBalance = (float) $data['balance'];

        if ($vccBalance <= 0) {
            // invalid amount
            return;
        }

        if (!VBOFactory::getConfig()->getBool('auto_payment_collection', false)) {
            // automatic payment collection is disabled, so we ignore the notification
            return;
        }

        // access the reservation model
        $resModel = VBOModelReservation::getInstance();

        // fetch the OTA booking details (list of records returned)
        $booking = $resModel
            ->setFilters([
                'ota_id'  => $ota_res_id,
                'channel' => $channel,
            ])
            ->search();

        if (!$booking) {
            // booking not found
            return;
        }

        // access the pay-schedules model
        $paySchedulesModel = VBOModelPayschedules::getInstance();

        // make sure this booking does not have any payment schedule to process
        $prevSchedules = $paySchedulesModel->getItems([
            'idorder' => [
                'value' => $booking[0]['id'],
            ],
            'status' => [
                'value' => 0,
            ],
        ]);

        if ($prevSchedules) {
            // do not schedule any other payment collection for this booking
            return;
        }

        // if the VCC update notification comes from Booking.com, ensure the card status is eligible
        if (stripos($channel, 'booking.com') !== false && !in_array(strtoupper((string) ($data['status'] ?? '')), ['AVAILABLE', 'FUNDED'])) {
            // do not process the notification due to a not-eligible status
            return;
        }

        // today's date object and current SQL date-time
        $todayDate = JFactory::getdate('now');
        $midnightDate = clone $todayDate;
        $midnightDate->modify('00:00:00');

        // validate and store the payment collection schedule
        try {
            // ensure the card activation date is in the future
            $activationDate = JFactory::getdate($data['activationDate']);

            if ($activationDate < $midnightDate) {
                // delayed notification occurred, notification is still accepted
                // payment collection date will be adjusted to a date in the future
            }

            if ($activationDate->format('H:i') === '00:00') {
                // give the payment collection date some grace hours
                $activationDate->modify('+6 hours');
            }

            // make sure the payment collection date is not in the past
            if ($activationDate < $todayDate) {
                // adjust the collection schedule time so the cron will process it
                $activationDate = clone $todayDate;
                $activationDate->modify('+1 hour');
            }

            // prepare the new payment schedule record
            $record = [
                'idorder'    => (int) $booking[0]['id'],
                'fordt'      => $activationDate->toSql(),
                'amount'     => $vccBalance,
                'status'     => 0,
                'created_on' => $todayDate->toSql(),
                'created_by' => 'Automatic Payment Scheduler',
            ];

            // store the record
            $paySchedulesModel->save($record);
        } catch (Exception $e) {
            // do nothing
        }

        return;
    }
}
