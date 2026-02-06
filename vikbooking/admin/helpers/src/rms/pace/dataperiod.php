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
 * RMS Pace Data-period abstract implementation.
 * 
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
abstract class VBORmsPaceDataperiod implements IteratorAggregate
{
    /**
     * @var  array
     */
    private array $bookings = [];

    /**
     * @var  DateTimeInterface
     */
    private DateTimeInterface $period;

    /**
     * @var  ?DateInterval
     */
    private ?DateInterval $interval = null;

    /**
     * @var  array
     */
    private array $listingsData = [];

    /**
     * Constucts the object by binding the various properties.
     * 
     * @param   array               $bookings   The period eligible bookings to iterate.
     * @param   DateTimeInterface   $period     The data period under evaluation.
     * @param   ?DateInterval       $interval   The date evaluation interval.
     */
    public function __construct(array $bookings, DateTimeInterface $period, ?DateInterval $interval = null)
    {
        // bind properties
        $this->bookings = $bookings;
        $this->period   = $period;
        $this->interval = $interval;
    }

    /**
     * Returns the internal array iterator over the bookings.
     * 
     * @return  Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->bookings);
    }

    /**
     * Returns the data period under evaluation.
     * 
     * @return  DateTimeInterface
     */
    public function getPeriod(): DateTimeInterface
    {
        return $this->period;
    }

    /**
     * Returns a list with the start and end period timestamps.
     * 
     * @param   bool    $fromMidnight   True to get the from timestamp at 00:00:00.
     * 
     * @return  array
     */
    public function getPeriodTimestamps(bool $fromMidnight = false): array
    {
        // get the type of date interval
        $intervalType = $this->getIntervalType();

        // clone the current date period
        $clonePeriod = clone $this->getPeriod();

        // assume the date interval type is "DAY"
        if ($fromMidnight) {
            // start at 00:00:00
            $clonePeriod->modify('00:00:00');
            $tsFrom = $clonePeriod->format('U');
            $clonePeriod->modify('23:59:59');
            $tsTo = $clonePeriod->format('U');
        } else {
            // start at 23:59:59
            $clonePeriod->modify('23:59:59');
            $tsFrom = $clonePeriod->format('U');
            $tsTo   = $tsFrom;
        }

        if ($intervalType === 'MONTH') {
            // adjust timestamp to the end of the month
            $tsTo = strtotime($clonePeriod->format('Y-m-t 23:59:59'));
        }

        return [$tsFrom, $tsTo];
    }

    /**
     * Returns the current date evaluation interval.
     * 
     * @return  ?DateInterval
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * Returns the current date evaluation interval type.
     * 
     * @return  string  Interval type like 'DAY' or 'MONTH'.
     */
    public function getIntervalType(): string
    {
        // default interval type
        $intervalType = 'DAY';

        if ($dateInterval = $this->getInterval()) {
            // check if the interval type is by month
            $intervalType = ($dateInterval->m ?? 0) ? 'MONTH' : 'DAY';
        }

        return $intervalType;
    }

    /**
     * Returns the current listings data.
     * 
     * @return  array
     */
    public function getListings(): array
    {
        return $this->listingsData;
    }

    /**
     * Sets the current listings data.
     * 
     * @param   array   $listings   Associative listings data list.
     * 
     * @return  self
     */
    public function setListings(array $listings): VBORmsPaceDataperiod
    {
        $this->listingsData = $listings;

        return $this;
    }

    /**
     * Counts the total number of units for the present listings.
     * 
     * @return  int
     */
    public function getTotalInventoryCount(): int
    {
        return (int) array_sum(array_column($this->listingsData, 'units'));
    }
}
