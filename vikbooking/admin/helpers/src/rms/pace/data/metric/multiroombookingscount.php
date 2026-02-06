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
 * RMS Pace Data Metric Multi-Room Bookings Count implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricMultiroombookingscount extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'groupres';
    }

    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        // start counter
        $groupBookingsCount = 0;

        // iterate all bookings affecting the current period
        foreach ($paceDataPeriod as $booking) {
            if (($booking['roomsnum'] ?? 1) > 1) {
                // increase counter
                $groupBookingsCount++;
            }
        }

        // return counted multi-room reservations
        return $groupBookingsCount;
    }
}
