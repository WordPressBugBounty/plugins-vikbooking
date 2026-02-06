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
 * RMS Pace Data Metric Room Rate Variation implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricRoomratevariation extends VBORmsPaceDataMetric
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'roomratevr';
    }

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

        // associative list of room rate variations
        $roomRateVariations = [];

        // iterate all listing IDs
        foreach (array_keys($paceDataPeriod->getListings()) as $listingId) {
            // get the last rates variation date object for the current listing, period and interval, if any
            $lastDt = $ratesRegistry->matchPeriodLastFlowDate($paceDataPeriod->getPeriod(), $paceDataPeriod->getIntervalType(), [$listingId]);
            if (!$lastDt) {
                // no flow records available
                continue;
            }

            // get the last room rate variation timestamp
            $lastTs = $lastDt->format('U');

            // start counters
            $plusCount = $minusCount = 0;

            // iterate all confirmed bookings affecting the current period
            foreach ($paceDataPeriod as $booking) {
                if (($booking['ts'] ?? 0) >= $lastTs && in_array($listingId, array_column($booking['_rooms'] ?? [], 'idroom'))) {
                    // increase counter
                    $plusCount++;
                }
            }

            // iterate all cancelled bookings affecting the current period
            foreach ($paceDataPeriod->getCancellations() as $booking) {
                if (($booking['ts'] ?? 0) >= $lastTs && in_array($listingId, array_column($booking['_rooms'] ?? [], 'idroom'))) {
                    // increase counter
                    $minusCount++;
                }
            }

            if ($plusCount || $minusCount) {
                // push listing ID rate variations
                $roomRateVariations[$listingId] = [
                    'dt'    => $lastDt,
                    'plus'  => $plusCount,
                    'minus' => $minusCount,
                ];
            }
        }

        // return the associative list of room rate variations, or null
        return $roomRateVariations ?: null;
    }
}
