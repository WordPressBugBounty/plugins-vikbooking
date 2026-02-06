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
 * RMS Pace Booking Data-period implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
final class VBORmsPaceBookingDataperiod extends VBORmsPaceDataperiod
{
    /**
     * @var  array
     */
    private array $pickupData = [];

    /**
     * @var  int
     */
    private int $pickupBookingsCount = 0;

    /**
     * Constucts the object by binding pickup data information and calls parent's constructor.
     * 
     * @param   int                 $baseCount  The number of bookings before current pickup period.
     * @param   array               $bookings   The period eligible bookings to iterate.
     * @param   DateTimeInterface   $period     The data period under evaluation.
     * @param   ?DateInterval       $interval   The date evaluation interval.
     * 
     * @throws  InvalidArgumentException
     */
    public function __construct(int $baseCount, array $bookings, DateTimeInterface $period, ?DateInterval $interval = null)
    {
        // bind bookings count at current pickup
        $this->pickupBookingsCount = $baseCount;

        // call parent constructor
        parent::__construct($bookings, $period, $interval);
    }

    /**
     * Increases the number of "on the books" bookings at current pickup period.
     * 
     * @param   int     $count  The number of new bookings to register.
     * 
     * @return  int             The total number of "on the books" bookings at current pickup.
     */
    public function registerNewBooking(int $count = 1): int
    {
        // increase counter
        $this->pickupBookingsCount += $count;

        // return the updated counter
        return $this->pickupBookingsCount;
    }

    /**
     * Decreases the number of "on the books" bookings at current pickup period.
     * 
     * @param   int     $count  The number of booking cancellations to register.
     * 
     * @return  int             The total number of "on the books" bookings at current pickup.
     */
    public function registerBookingCancellation(int $count = 1): int
    {
        // decrease counter
        $this->pickupBookingsCount -= $count;

        // return the updated counter
        return $this->pickupBookingsCount;
    }

    /**
     * Returns the starting bookings counter at the current pickup period.
     * 
     * @return  int
     */
    public function getPickupStartingBookings(): int
    {
        return $this->pickupBookingsCount;
    }
}
