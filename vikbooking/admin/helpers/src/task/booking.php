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
 * Task booking implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskBooking
{
    /**
     * @var  array
     */
    protected $registry = [];

    /**
     * @var  array
     */
    protected $bookingRooms = [];

    /**
     * @var  array
     */
    protected $previousBooking = [];

    /**
     * @var  int
     */
    protected $currentRoomIndex = 0;

    /**
     * Proxy to construct the object.
     * 
     * @param   array   $options    Associative list of booking information to bind.
     * @param   array   $rooms      Associative list of booking rooms to bind.
     * @param   array   $previous   Associative list of previous booking information to bind.
     * 
     * @return  VBOTaskBooking
     */
    public static function getInstance(array $options, array $rooms = [], array $previous = [])
    {
        return new static($options, $rooms, $previous);
    }

    /**
     * Class constructor.
     * 
     * @param   array   $options    Associative list of booking information to bind.
     * @param   array   $rooms      Associative list of booking rooms to bind.
     * @param   array   $previous   Associative list of previous booking information to bind.
     * 
     * @throws  Exception
     */
    public function __construct(array $options, array $rooms = [], array $previous = [])
    {
        if (empty($options['id'])) {
            throw new Exception('Missing booking ID.', 500);
        }

        // ensure we have enough booking details
        if ($options === ['id' => $options['id']]) {
            // load full booking details
            $options = VikBooking::getBookingInfoFromID($options['id']);
        }

        // bind booking options to internal registry
        $this->bind($options);

        if (!$rooms) {
            // load booking rooms
            $rooms = VikBooking::loadOrdersRoomsData($this->getID());
        }

        // bind booking rooms
        $this->bookingRooms = $rooms;

        // bind previous booking information in case of alteration
        $this->previousBooking = $previous;
    }

    /**
     * Binds the given options onto the internal booking registry.
     * 
     * @param   array   $options   The booking options to bind.
     * 
     * @return  void
     */
    public function bind(array $options)
    {
        $this->registry = array_merge($this->registry, $options);
    }

    /**
     * Returns the current booking ID.
     * 
     * @return  int
     */
    public function getID()
    {
        return (int) $this->getProperty('id', 0);
    }

    /**
     * Returns the number of nights of stay for the current booking ID.
     * 
     * @return  int
     */
    public function getTotalNights()
    {
        return (int) $this->getProperty('days', 1);
    }

    /**
     * Tells whether the booking is actually a closure reservation.
     * 
     * @return  bool
     */
    public function isClosure()
    {
        return (bool) $this->getProperty('closure', 0);
    }

    /**
     * Tells whether the booking status is confirmed.
     * 
     * @return  bool
     */
    public function isConfirmed()
    {
        return $this->getProperty('status', '') == 'confirmed';
    }

    /**
     * Tells whether the booking status is pending (stand-by).
     * 
     * @return  bool
     */
    public function isPending()
    {
        return $this->getProperty('status', '') == 'standby';
    }

    /**
     * Tells whether the booking status is cancelled.
     * 
     * @return  bool
     */
    public function isCancelled()
    {
        return $this->getProperty('status', '') == 'cancelled';
    }

    /**
     * Tells whether the booking is flagged as overbooking.
     * 
     * @return  bool
     */
    public function isOverbooking()
    {
        return $this->getProperty('type', '') == 'overbooking';
    }

    /**
     * Returns the requested registry property name.
     * 
     * @param   string  $name       The registry property to fetch.
     * @param   mixed   $default    The default value to return.
     * 
     * @return  mixed
     */
    public function getProperty(string $name, $default = null)
    {
        return $this->registry[$name] ?? $default;
    }

    /**
     * Returns the requested previous booking property name.
     * 
     * @param   string  $name       The previous booking property to fetch.
     * @param   mixed   $default    The default value to return.
     * 
     * @return  mixed
     */
    public function getPreviousProperty(string $name, $default = null)
    {
        return $this->previousBooking[$name] ?? $default;
    }

    /**
     * Returns the booking data.
     * 
     * @return  array
     */
    public function getData()
    {
        return $this->registry;
    }

    /**
     * Returns the booking rooms data.
     * 
     * @return  array
     */
    public function getRooms()
    {
        return $this->bookingRooms;
    }

    /**
     * Returns the previous booking data.
     * 
     * @return  array
     */
    public function getPrevious()
    {
        return $this->previousBooking;
    }

    /**
     * Gets the current room index.
     * 
     * @return  int
     */
    public function getCurrentRoomIndex()
    {
        return $this->currentRoomIndex;
    }

    /**
     * Sets the current room index.
     * 
     * @param   int     $index  The current room index.
     * 
     * @return  void
     */
    public function setCurrentRoomIndex(int $index)
    {
        $this->currentRoomIndex = $index;
    }

    /**
     * Returns the booking stay timestamps.
     * 
     * @return  array
     */
    public function getStayTimestamps()
    {
        /**
         * @todo    Do we need to do something different for split-stay bookings?
         *          We are aware of the current room index when this method is called.
         */

        return [
            $this->getProperty('checkin'),
            $this->getProperty('checkout'),
        ];
    }

    /**
     * Builds and returns the iterable date period interval for the nights of stay.
     * 
     * @param   string  $duration   The interval specification used for DateInterval::__construct().
     * 
     * @return  DatePeriod
     */
    public function buildStayPeriodInterval(string $duration = 'P1D', int $from_ts = 0, int $to_ts = 0)
    {
        if (empty($from_ts)) {
            $from_ts = $this->getProperty('checkin');
        }

        if (empty($to_ts)) {
            $to_ts = $this->getProperty('checkout');
        }

        // local timezone
        $tz = new DateTimezone(date_default_timezone_get());

        // get date bounds
        $from_bound = new DateTime(date('Y-m-d H:i:s', $from_ts), $tz);
        $to_bound = new DateTime(date('Y-m-d H:i:s', $to_ts), $tz);

        // build iterable dates interval (period)
        $date_range = new DatePeriod(
            // start date included by default in the result set
            $from_bound,
            // interval between recurrences within the period
            new DateInterval($duration),
            // end date (check-out) excluded by default from the result set
            $to_bound
        );

        return $date_range;
    }

    /**
     * Returns the iterable date period range of dates for the nights of stay.
     * 
     * @return  DatePeriod
     */
    public function getStayPeriod()
    {
        if (($this->registry['stay_date_period'] ?? null) instanceof DatePeriod) {
            // return cached value
            return $this->registry['stay_date_period'];
        }

        // build iterable dates interval (period)
        $date_range = $this->buildStayPeriodInterval('P1D');

        // cache value
        $this->bind(['stay_date_period' => $date_range]);

        return $date_range;
    }

    /**
     * Attempts to detect changes between the current and previous bookings.
     * 
     * @return  bool    False if no changes were actually proved, true otherwise.
     */
    public function detectAlterations()
    {
        if ($this->getProperty('checkin') != $this->getPreviousProperty('checkin')) {
            return true;
        }

        if ($this->getProperty('checkout') != $this->getPreviousProperty('checkout')) {
            return true;
        }

        if ($this->getProperty('days') != $this->getPreviousProperty('days')) {
            return true;
        }

        if ($this->getProperty('roomsnum') != $this->getPreviousProperty('roomsnum')) {
            return true;
        }

        // get the rooms booked with the current reservation
        $current_room_ids = array_column($this->getRooms(), 'idroom');
        if (!$current_room_ids && is_array($this->getProperty('rooms_info'))) {
            $current_room_ids = array_column($this->getProperty('rooms_info'), 'idroom');
        }

        // get the rooms booked with the previous reservation
        $previous_room_ids = array_column((array) $this->getPreviousProperty('rooms_info', []), 'idroom');

        // map and sort both room lists
        $current_room_ids = array_map('intval', $current_room_ids);
        $previous_room_ids = array_map('intval', $previous_room_ids);
        sort($current_room_ids);
        sort($previous_room_ids);

        if (!$current_room_ids || !$previous_room_ids || $current_room_ids != $previous_room_ids) {
            return true;
        }

        // no significant changes to stay dates or listings could be proved
        return false;
    }
}
