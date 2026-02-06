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
 * RMS Pace Data Metric Booked Rooms implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricBookedrooms extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        // associative list of booked room IDs and related room nights
        $bookedRooms = [];

        // determine the period interval type for evaluation
        $intervalType = $paceDataPeriod->getIntervalType();

        // get the period start and end timestamps
        list($periodTsFrom, $periodTsTo) = $paceDataPeriod->getPeriodTimestamps();

        // iterate all bookings affecting the current period
        foreach ($paceDataPeriod as $booking) {
            foreach (($booking['_rooms'] ?? []) as $bookingRoom) {
                if ($intervalType === 'DAY') {
                    // single day period will count as one night
                    $bookedRooms[$bookingRoom['idroom']] = ($bookedRooms[$bookingRoom['idroom']] ?? 0) + 1;
                } else {
                    // period interval is longer than one day, count the affected nights of stay
                    $startTs = max($periodTsFrom, $booking['checkin']);
                    $endTs   = min($periodTsTo, $booking['checkout']);
                    $inclusive = $endTs === $periodTsTo;
                    $bookedRooms[$bookingRoom['idroom']] = ($bookedRooms[$bookingRoom['idroom']] ?? 0) + VBORmsPace::getInstance()->countNightsDifferenceTs($startTs, $endTs, $inclusive);
                }
            }
        }

        // sort descending and keep indexes
        arsort($bookedRooms);

        // return the sorted associative list
        return $bookedRooms;
    }
}
