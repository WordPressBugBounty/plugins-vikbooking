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
 * RMS Pace Data Metric New Bookings implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricNewbookings extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'nb';
    }

    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        // start counter
        $nbCount = 0;

        // iterate all bookings
        foreach ($paceDataPeriod as $booking) {
            if ($booking['status'] != 'confirmed') {
                // exclude non-confirmed reservations
                continue;
            }

            // increase counter
            $nbCount++;
        }

        // update pickup date period "on the books" counter
        $paceDataPeriod->registerNewBooking($nbCount);

        // return the number of confirmed bookings that were generated on this period interval
        return $nbCount;
    }
}
