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
 * RMS Pace Data Metric Room Revenue implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricRoomrevenue extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'roomrev';
    }

    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        $roomRevenue = 0;

        // iterate all bookings affecting the current period
        foreach ($paceDataPeriod as $booking) {
            if ($booking['status'] != 'confirmed') {
                // exclude non-confirmed reservations
                continue;
            }
            foreach (($booking['_rooms'] ?? []) as $bookingRoom) {
                if (!empty($bookingRoom['room_cost'])) {
                    $netRoomCost = VikBooking::sayCostMinusIva($bookingRoom['room_cost'], $bookingRoom['idprice'] ?? 0);
                    $roomRevenue += (float) $netRoomCost;
                } elseif (!empty($bookingRoom['cust_cost'])) {
                    $netRoomCost = VikBooking::sayPackageMinusIva($bookingRoom['cust_cost'], $bookingRoom['cust_idiva']);
                    $roomRevenue += (float) $netRoomCost;
                }
            }
        }

        return round($roomRevenue, 2);
    }
}
