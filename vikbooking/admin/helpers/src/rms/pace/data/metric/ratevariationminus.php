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
 * RMS Pace Data Metric Rate Variation Minus implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricRatevariationminus extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'ratevrminus';
    }

    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        // this metric should run after "Rate Variation Date"
        if (empty($periodPaceMetrics['ratevrdt']) || !($periodPaceMetrics['ratevrdt'] instanceof DateTimeInterface)) {
            return 0;
        }

        // start counter for the booking cancellations received after a certain date
        $minusCount = 0;

        // get the last variation timestamp
        $lastTs = $periodPaceMetrics['ratevrdt']->format('U');

        // iterate all cancelled bookings affecting the current period
        foreach ($paceDataPeriod->getCancellations() as $booking) {
            // attempt to evaluate the exact cancellation date timestamp
            if (($booking['cancellation_ts'] ?? $booking['ts'] ?? 0) >= $lastTs) {
                // increase counter
                $minusCount++;
            }
        }

        // return the number of booking cancellations after the last rate variation date
        return $minusCount;
    }
}
