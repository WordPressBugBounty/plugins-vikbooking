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
 * Booking registry implementation.
 * 
 * @since   1.18.4 (J) - 1.8.4 (WP)
 */
class VBOBookingRegistry
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
     * @var  int
     */
    protected $currentRoomNumber = 0;

    /**
     * @var  array
     */
    protected $dacData = [];

    /**
     * @var  array
     */
    protected $roomDetails = [];

    /**
     * @var  ?array
     */
    protected $customer = null;

    /**
     * Proxy to construct the object.
     * 
     * @param   array   $options    Associative list of booking information to bind.
     * @param   array   $rooms      Associative list of booking rooms to bind.
     * @param   array   $previous   Associative list of previous booking information to bind.
     * 
     * @return  VBOBookingRegistry
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
        // ensure we have enough booking details
        if ($options === ['id' => $options['id'] ?? 0]) {
            // load full booking details
            $options = VikBooking::getBookingInfoFromID($options['id']);
        }

        if (empty($options['id'])) {
            throw new Exception('Missing booking ID.', 500);
        }

        // bind booking options to internal registry
        $this->bind($options);

        if (!$rooms) {
            // load booking rooms
            $rooms = VikBooking::loadOrdersRoomsData($this->getID());
        }

        if (!$rooms) {
            throw new Exception('No booking rooms found.', 404);
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
     * @param   bool    $roomLevel  True to calculate the nights at room-level.
     * 
     * @return  int
     */
    public function getTotalNights(bool $roomLevel = false)
    {
        if ($roomLevel) {
            // get the stay timestamps at room-level
            $stayTimestamps = $this->getStayTimestamps(true);

            // return the room-level nights of stay
            return VikBooking::getAvailabilityInstance()->countNightsOfStay($stayTimestamps[0], $stayTimestamps[1]) ?: 1;
        }

        // return the booking nights of stay
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
     * Sets a value for the requested registry property name.
     * 
     * @param   string  $name     The registry property to set.
     * @param   mixed   $value    The value to set.
     * 
     * @return  self
     */
    public function setProperty(string $name, $value)
    {
        $this->registry[$name] = $value;

        return $this;
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
     * Returns the whole or provider-alias DAC data-registry.
     * 
     * @param   ?string  $providerAlias  Optional DAC provider alias identifier.
     * 
     * @return  array
     */
    public function getDACData(?string $providerAlias = null)
    {
        if ($providerAlias) {
            return $this->dacData[$providerAlias] ?? [];
        }

        return $this->dacData;
    }

    /**
     * Returns the requested DAC data-registry property name.
     * 
     * @param   string  $providerAlias  The DAC provider alias identifier.
     * @param   string  $name           The DAC registry property to fetch.
     * @param   mixed   $default        The default value to return.
     * 
     * @return  mixed
     */
    public function getDACProperty(string $providerAlias, string $name, $default = null)
    {
        return $this->dacData[$providerAlias][$name] ?? $default;
    }

    /**
     * Sets a value within the DAC data-registry for a given property name.
     * 
     * @param   string  $providerAlias  The DAC provider alias identifier.
     * @param   string  $name           The DAC registry property to set.
     * @param   mixed   $value          The property value to set.
     * 
     * @return  self
     */
    public function setDACProperty(string $providerAlias, string $name, $value)
    {
        if (!isset($this->dacData[$providerAlias])) {
            // start a new container
            $this->dacData[$providerAlias] = [];
        }

        // set the value for the requested property name
        $this->dacData[$providerAlias][$name] = $value;

        return $this;
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
     * Returns the booked listing IDs.
     * 
     * @param   bool    $unique     True to only get a unique list.
     * 
     * @return  array               List of booked room ID integers, unique or not.
     */
    public function getBookedListingIds(bool $unique = true)
    {
        if (!$unique) {
            return array_map('intval', array_column($this->bookingRooms, 'idroom'));
        }

        return array_values(array_unique(array_map('intval', array_column($this->bookingRooms, 'idroom'))));
    }

    /**
     * Returns a list of booked listing IDs and subunits (0 if no subunit).
     * 
     * @param   bool    $unique     True to only get a unique list.
     * 
     * @return  array   Linear array of strings as "roomid-subunit".
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function getBookedListingSubunits(bool $unique = true)
    {
        $bookedListingSubunits = [];

        foreach ($this->bookingRooms as $bookingRoom) {
            // push booked listing ID and related subunit number
            $bookedListingSubunits[] = sprintf('%d-%d', $bookingRoom['idroom'], (int) ($bookingRoom['roomindex'] ?? 0));
        }

        if ($unique) {
            // remove duplicate entries on listing-level with no subunits
            $bookedListingSubunits = array_values(array_unique($bookedListingSubunits));
        }

        return $bookedListingSubunits;
    }

    /**
     * Counts the number of total adults, either at booking or room level.
     * 
     * @param   bool    $roomLevel  True to count the adults of the current room.
     * 
     * @return  int
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function countTotalAdults(bool $roomLevel = false)
    {
        if ($roomLevel) {
            foreach ($this->bookingRooms as $index => $bookingRoom) {
                if ($index == $this->getCurrentRoomIndex()) {
                    return (int) ($bookingRoom['adults'] ?? 0);
                }
            }
        }

        return array_sum(array_map('intval', array_column($this->bookingRooms, 'adults')));
    }

    /**
     * Counts the number of total children, either at booking or room level.
     * 
     * @param   bool    $roomLevel  True to count the children of the current room.
     * 
     * @return  int
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function countTotalChildren(bool $roomLevel = false)
    {
        if ($roomLevel) {
            foreach ($this->bookingRooms as $index => $bookingRoom) {
                if ($index == $this->getCurrentRoomIndex()) {
                    return (int) ($bookingRoom['children'] ?? 0);
                }
            }
        }

        return array_sum(array_map('intval', array_column($this->bookingRooms, 'children')));
    }

    /**
     * Counts the number of total pets, either at booking or room level.
     * 
     * @param   bool    $roomLevel  True to count the pets of the current room.
     * 
     * @return  int
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function countTotalPets(bool $roomLevel = false)
    {
        if ($roomLevel) {
            foreach ($this->bookingRooms as $index => $bookingRoom) {
                if ($index == $this->getCurrentRoomIndex()) {
                    return (int) ($bookingRoom['pets'] ?? 0);
                }
            }
        }

        return array_sum(array_map('intval', array_column($this->bookingRooms, 'pets')));
    }

    /**
     * Counts the number of total guests, either at booking or room level.
     * 
     * @param   bool    $roomLevel  True to count the guests of the current room.
     * 
     * @return  int
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function countTotalGuests(bool $roomLevel = false)
    {
        return $this->countTotalAdults($roomLevel) + $this->countTotalChildren($roomLevel);
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
     * Gets the current room number (1-based index).
     * 
     * @return  int
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function getCurrentRoomNumber()
    {
        return $this->currentRoomNumber;
    }

    /**
     * Sets the current room number (1-based index).
     * 
     * @param   int     $number  The current room number.
     * 
     * @return  void
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function setCurrentRoomNumber(int $number)
    {
        $this->currentRoomNumber = $number;
    }

    /**
     * Gets the room ID from the current room index set.
     * 
     * @return  int
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP) added argument $roomLevel.
     */
    public function getCurrentRoomID()
    {
        foreach ($this->getRooms() as $index => $bookingRoom) {
            if ($index == $this->currentRoomIndex) {
                return (int) $bookingRoom['idroom'];
            }
        }

        return (int) (($this->getRooms()[0]['idroom'] ?? 0) ?: 0);
    }

    /**
     * Returns the details for the given room ID or for all rooms set.
     * 
     * @param   ?int     $listingId  Optional listing ID to get.
     * 
     * @return  array
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function getRoomDetails(?int $listingId = null)
    {
        if ($listingId && !($this->roomDetails[$listingId] ?? [])) {
            // obtain the information for the requested listing ID
            $listingDetails = VikBooking::getRoomInfo($listingId, ['name', 'img', 'units', 'params'], true);
            if ($listingDetails) {
                // cache value
                $this->setRoomDetails($listingId, $listingDetails);
            }
        }

        if (!$listingId) {
            return $this->roomDetails;
        }

        return $this->roomDetails[$listingId] ?? [];
    }

    /**
     * Sets the details for the given room ID.
     * 
     * @param   int     $listingId  The listing ID to update.
     * @param   array   $data       The details data to set.
     * 
     * @return  self
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function setRoomDetails(int $listingId, array $data)
    {
        $this->roomDetails[$listingId] = $data;

        return $this;
    }

    /**
     * Gets the booking customer details.
     * 
     * @return  array
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function getCustomer()
    {
        if ($this->customer === null) {
            // load and cache customer details
            $this->customer = VikBooking::getCPinInstance()->getCustomerFromBooking($this->getID());
        }

        return $this->customer;
    }

    /**
     * Sets the booking customer details.
     * 
     * @param   array   $customer   Raw customer details.
     * 
     * @return  self
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function setCustomer(array $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Gets the booking customer data: nominative, logo, provenience name.
     * 
     * @return  array   Numeric list of booking-customer data value strings.
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP)
     */
    public function getBookingCustomerData()
    {
        // starting values
        $customer_nominative = '';
        $booking_avatar_src  = '';
        $booking_avatar_alt  = '';

        if ($this->customer === null) {
            // attempt to load customer details first
            $this->getCustomer();
        }

        if (!empty($this->customer['first_name']) || !empty($this->customer['last_name'])) {
            // check if we need to display a profile picture or a channel logo
            if (!empty($this->customer['pic'])) {
                // customer profile picture
                $booking_avatar_src = strpos($this->customer['pic'], 'http') === 0 ? $this->customer['pic'] : VBO_SITE_URI . 'resources/uploads/' . $this->customer['pic'];
                $booking_avatar_alt = basename($booking_avatar_src);
            } elseif ($this->getProperty('idorderota') && $this->getProperty('channel')) {
                // channel logo
                $logo_helper = VikBooking::getVcmChannelsLogo($this->getProperty('channel'), $get_istance = true);
                if ($logo_helper !== false) {
                    $booking_avatar_src = $logo_helper->getSmallLogoURL();
                    $booking_avatar_alt = $logo_helper->provenience;
                }
            }

            if (!empty($booking_avatar_src)) {
                // make sure the alt attribute is not too long in case of broken images
                $booking_avatar_alt = !empty($booking_avatar_alt) && strlen($booking_avatar_alt) > 15 ? '...' . substr($booking_avatar_alt, -12) : $booking_avatar_alt;
            }

            // customer name
            $customer_fullname = trim($this->customer['first_name'] . ' ' . $this->customer['last_name']);
            if (strlen($customer_fullname) > 26) {
                if (function_exists('mb_substr')) {
                    $customer_fullname = trim(mb_substr($customer_fullname, 0, 26, 'UTF-8')) . '..';
                } else {
                    $customer_fullname = trim(substr($customer_fullname, 0, 26)) . '..';
                }
            }
            $customer_nominative = $customer_fullname;
        } else {
            // parse the customer data string
            $custdata_parts = explode("\n", (string) $this->getProperty('custdata'));
            $enoughinfo = false;
            if (count($custdata_parts) > 2 && strpos($custdata_parts[0], ':') !== false && strpos($custdata_parts[1], ':') !== false) {
                // get the first two fields
                $custvalues = array();
                foreach ($custdata_parts as $custdet) {
                    if (strlen($custdet) < 1) {
                        continue;
                    }
                    $custdet_parts = explode(':', $custdet);
                    if (count($custdet_parts) >= 2) {
                        unset($custdet_parts[0]);
                        array_push($custvalues, trim(implode(':', $custdet_parts)));
                    }
                    if (count($custvalues) > 1) {
                        break;
                    }
                }
                if (count($custvalues) > 1) {
                    $enoughinfo = true;
                    $customer_nominative = trim(implode(' ', $custvalues));
                    if (strlen($customer_nominative) > 26) {
                        if (function_exists('mb_substr')) {
                            $customer_nominative = trim(mb_substr($customer_nominative, 0, 26, 'UTF-8')) . '..';
                        } else {
                            $customer_nominative = trim(substr($customer_nominative, 0, 26)) . '..';
                        }
                    }
                    if ($this->getProperty('idorderota') && $this->getProperty('channel')) {
                        // add support for the channel logo for the imported OTA reservations with no customer record
                        $logo_helper = VikBooking::getVcmChannelsLogo($this->getProperty('channel'), $get_istance = true);
                        if ($logo_helper !== false) {
                            $booking_avatar_src = $logo_helper->getSmallLogoURL();
                            $booking_avatar_alt = $logo_helper->provenience;
                            // make sure the alt attribute is not too long in case of broken images
                            $booking_avatar_alt = !empty($booking_avatar_alt) && strlen($booking_avatar_alt) > 15 ? '...' . substr($booking_avatar_alt, -12) : $booking_avatar_alt;
                        }
                    }
                }
            }
            if (!$enoughinfo) {
                $customer_nominative = '#' . $this->getID();
            }
        }

        return [
            $customer_nominative,
            $booking_avatar_src,
            $booking_avatar_alt,
        ];
    }

    /**
     * Returns the booking (or current room booking record) stay timestamps.
     * 
     * @param   bool    $roomLevel  True to return the stay timestamps at booking room level.
     * 
     * @return  array
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP) added argument $roomLevel.
     */
    public function getStayTimestamps(bool $roomLevel = false)
    {
        if ($roomLevel) {
            // current room index signature for the stay timestamps
            $roomSignature = '_stay_timestamps' . $this->getCurrentRoomIndex();

            if ($cachedTimestamps = $this->getProperty($roomSignature)) {
                // return the cached stay timestamps for the current room index
                return $cachedTimestamps;
            }

            // load room occupied records
            $room_stay_dates = VikBooking::getAvailabilityInstance(true)->loadSplitStayBusyRecords($this->getID());

            // default room-level stay timestamps
            $roomLevelCheckin  = $this->getProperty('checkin');
            $roomLevelCheckout = $this->getProperty('checkout');

            // split-stay booking or booked rooms for modified stay nights should rely on occupied records
            if ($room_stay_dates[$this->getCurrentRoomIndex()] ?? []) {
                // set booking-room-level stay timestamps
                $roomLevelCheckin  = $room_stay_dates[$this->getCurrentRoomIndex()]['checkin'];
                $roomLevelCheckout = $room_stay_dates[$this->getCurrentRoomIndex()]['checkout'];
            }

            // build room-level stay timestamps list
            $roomLevelStayList = [
                $roomLevelCheckin,
                $roomLevelCheckout,
            ];

            // cache stay timestamps for the current room index
            $this->setProperty($roomSignature, $roomLevelStayList);

            // return booking-room-level stay timestamps, if different than global stay dates
            return $roomLevelStayList;
        }

        // global reservation stay timestamps
        return [
            $this->getProperty('checkin'),
            $this->getProperty('checkout'),
        ];
    }

    /**
     * Builds and returns the iterable date period interval for the nights of stay.
     * 
     * @param   string  $duration   The interval specification used for DateInterval::__construct().
     * @param   int     $from_ts    Optional from date period timestamp.
     * @param   int     $to_ts      Optional to date period timestamp.
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
     * @param   bool    $roomLevel  True to also detect alterations at room-level.
     * 
     * @return  bool    False if no changes were actually proved, true otherwise.
     * 
     * @since   1.18.7 (J) - 1.8.7 (WP) added argument $roomLevel.
     */
    public function detectAlterations(bool $roomLevel = false)
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

        // attempt to also detect changes at room-level
        if ($roomLevel) {
            // check subunits
            $current_room_indexes  = array_map('intval', array_column($this->getRooms(), 'roomindex'));
            $previous_room_indexes = array_map('intval', array_column((array) $this->getPreviousProperty('rooms_info', []), 'roomindex'));
            sort($current_room_indexes);
            sort($previous_room_indexes);
            // ensure room subunits information is available for current and previous booking
            if ($current_room_indexes && $previous_room_indexes && $current_room_indexes != $previous_room_indexes) {
                // room subunits alteration detected
                return true;
            }
        }

        // no significant changes to stay dates or listings could be proved
        return false;
    }

    /**
     * Tells if the current date and time is between the stay dates.
     * 
     * @return  bool
     */
    public function isStaying()
    {
        $now = time();

        return $this->getProperty('checkin', 0) <= $now && $now <= $this->getProperty('checkout', 0);
    }

    /**
     * Tells if the booking arrival date is today.
     * 
     * @return  bool
     */
    public function isArrivingToday()
    {
        return date('Y-m-d', $this->getProperty('checkin', 0)) === date('Y-m-d');
    }

    /**
     * Tells if the booking departure date is today.
     * 
     * @return  bool
     */
    public function isDepartingToday()
    {
        return date('Y-m-d', $this->getProperty('checkout', 0)) === date('Y-m-d');
    }

    /**
     * Tells if the booking arrival date and time is in the future.
     * 
     * @return  bool
     */
    public function isFuture()
    {
        return $this->getProperty('checkin', 0) > time();
    }

    /**
     * Tells if the booking departure date and time is in the past.
     * 
     * @return  bool
     */
    public function isPast()
    {
        return $this->getProperty('checkout', 0) < time();
    }

    /**
     * Tells if the booking went through the pre-checkin process.
     * 
     * @return  bool
     * 
     * @since   1.18.6 (J) - 1.8.6 (WP)
     */
    public function hasPreCheckedIn()
    {
        // check registry protected value
        $pre_checkin = $this->getProperty('_precheckin');

        if (!is_int($pre_checkin)) {
            // fetch the actual status and cache it internally
            $pre_checkin = (int) boolval(VikBooking::getBookingHistoryInstance($this->getID())->hasEvent('PC'));
            $this->registry['_precheckin'] = $pre_checkin;
        }

        return (bool) $pre_checkin;
    }
}
