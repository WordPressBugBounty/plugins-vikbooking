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
 * RMS Pace Data Metric Nightly Rates implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricNightlyrates extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        $ratesRegistry = $paceDataPeriod->getRatesRegistry();

        if (!$ratesRegistry) {
            // registry is not available
            return null;
        }

        // associative list of room nightly rates
        $roomNightlyRates = [];

        // iterate all listing IDs
        foreach (array_keys($paceDataPeriod->getListings()) as $listingId) {
            // get the latest rate set for this listing and period
            $lastRate = $ratesRegistry->matchPeriodLastNightlyRate($paceDataPeriod->getPeriod(), $paceDataPeriod->getIntervalType(), $listingId);
            if (!$lastRate) {
                // no flow records available
                continue;
            }

            // push listing latest nightly rate for this period
            $roomNightlyRates[$listingId] = $lastRate;
        }

        // return the associative list of room nightly rates, or null
        return $roomNightlyRates ?: null;
    }
}
