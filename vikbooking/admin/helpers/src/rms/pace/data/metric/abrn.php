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
 * RMS Pace Data Metric ABRN (As Booked Room Nights) implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricAbrn extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        // start counter
        $abrnCount = 0;

        // determine the period interval type for evaluation
        $intervalType = $paceDataPeriod->getIntervalType();

        // get the period start and end timestamps
        list($periodTsFrom, $periodTsTo) = $paceDataPeriod->getPeriodTimestamps();

        // iterate all bookings affecting the current period
        foreach ($paceDataPeriod as $booking) {
            // multi or single room booking multiplier factor
            $unitsFactor = ($booking['roomsnum'] ?? 1) ?: 1;

            // increase counter depending on period interval type
            if ($intervalType === 'DAY') {
                // single day period will count the number of booked listings
                $abrnCount += $unitsFactor;
            } else {
                // period interval is longer than one day, count the affected nights of stay
                $startTs = max($periodTsFrom, $booking['checkin']);
                $endTs   = min($periodTsTo, $booking['checkout']);
                $inclusive = $endTs === $periodTsTo;
                $abrnCount += VBORmsPace::getInstance()->countNightsDifferenceTs($startTs, $endTs, $inclusive) * $unitsFactor;
            }
        }

        // return the number of as booked room nights
        return $abrnCount;
    }
}
