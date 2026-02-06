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
 * RMS Pace Data Metric Cancelled Bookings implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceDataMetricCancbookings extends VBORmsPaceDataMetric
{
    /**
     * @var  array
     */
    private array $allBookings = [];

    /**
     * Class constructor requires all bookings intersecting the (target) stay dates.
     * 
     * @param   array   $bookings   All booking records, not only the ones of the period.
     * @param   ?array  $options    Optional settings to follow.
     */
    public function __construct(array $bookings, ?array $options = null)
    {
        // bind all bookings
        $this->allBookings = $bookings;

        // call parent constructor
        parent::__construct($options);
    }

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'cb';
    }

    /**
     * @inheritDoc
     */
    public function extract(VBORmsPaceDataperiod $paceDataPeriod, ?array $periodPaceMetrics = null)
    {
        // start counter
        $cbCount = 0;

        // filter bookings with a cancellation date made on the current period
        $cancBookings = VBORmsPace::getInstance()->filterPeriodBookings(
            $paceDataPeriod->getPeriod(),
            $this->allBookings,
            $paceDataPeriod->getInterval(),
            ['intersect' => 'cancellation']
        );

        // iterate all bookings whose cancellation date intersects the current period
        foreach ($cancBookings as $booking) {
            if ($booking['status'] != 'cancelled' || empty($booking['cancellation_ts'])) {
                // exclude non-cancelled reservations
                continue;
            }

            // increase counter
            $cbCount++;
        }

        // update pickup date period "on the books" counter
        $paceDataPeriod->registerBookingCancellation($cbCount);

        // return the number of booking cancellations that were made on this period interval
        return $cbCount;
    }
}
