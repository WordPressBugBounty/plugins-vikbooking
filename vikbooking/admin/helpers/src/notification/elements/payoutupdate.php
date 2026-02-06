<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Notification elements registry (Notification Center) of type "Payout Update".
 * 
 * @since 1.18.3 (J) - 1.8.3 (WP)
 */
class VBONotificationElementsPayoutupdate extends VBONotificationElements
{
    /**
     * @inheritDoc
     */
    public function postflight()
    {
        $ota_res_id = $this->getOTAReservationID();
        $channel    = $this->getChannel();
        $data       = (array) $this->get('data', []);

        if (empty($ota_res_id) || empty($channel) || (empty($data['total_payout']) && empty($data['commissions']))) {
            // no interesting payload data information to process
            return;
        }

        // access the reservation model
        $model = VBOModelReservation::getInstance();

        // fetch the OTA booking details
        $booking = $model
            ->setFilters([
                'ota_id'  => $ota_res_id,
                'channel' => $channel,
            ])
            ->search();

        if (!$booking) {
            // booking not found
            return;
        }

        if (stripos($channel, 'booking.com') !== false && !empty($data['status']) && strcasecmp($data['status'], 'paid')) {
            // in case of Booking.com payout update notification with "status" different than "paid" do not update the payout
            $data['total_payout'] = null;
        }

        // update either the payout amount, compensation or both
        $model->updatePayoutCompensation([
            'payout'       => $data['total_payout'] ?? null,
            'compensation' => $data['commissions'] ?? null,
        ], $booking[0]);

        return;
    }
}
