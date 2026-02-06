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
 * RMS Pace Data Metric Gross Revenue implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricGrossrevenue extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'grossrev';
    }

    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        $grossRevenueAssoc = [];

        // iterate all bookings affecting the current period
        foreach ($paceDataPeriod as $booking) {
            // set metric under this booking ID
            $grossRevenueAssoc[$booking['id']] = max(
                0,
                ((float) ($booking['total'] ?? 0) - (float) ($booking['tot_taxes'] ?? 0) - (float) ($booking['tot_city_taxes'] ?? 0))
            );
        }

        return $grossRevenueAssoc;
    }
}
