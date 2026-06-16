<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking reservation model.
 *
 * @since   1.16.0 (J) - 1.6.0 (WP)
 */
class VBOModelReservation extends JObject
{
    /**
     * The singleton instance of the class.
     *
     * @var  VBOModelReservation
     */
    private static $instance = null;

    /**
     * The total number of bookings found through the last search.
     * 
     * @var int
     */
    protected $totalBookings = 0;

    /**
     * @var array
     */
    protected $allRooms = [];

    /**
     * @var ?VBOBookingRegistry
     */
    protected ?VBOBookingRegistry $prevBookingRegistry = null;

    /**
     * Proxy for immediately getting the object and bind data.
     * 
     * @param   array|object  $data  optional data to bind.
     * @param   boolean       $anew  true for forcing a new instance.
     * 
     * @return  self
     */
    public static function getInstance($data = [], $anew = false)
    {
        if (is_null(static::$instance) || $anew) {
            static::$instance = new static($data);
        }

        return static::$instance;
    }

    /**
     * Sets the caller information used to save history records.
     * 
     * @param   string  $caller     The caller identifier.
     * 
     * @return  self
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function setCaller($caller = '')
    {
        $this->set('_caller', (string) $caller);

        return $this;
    }

    /**
     * Returns the caller information.
     * 
     * @return  string
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function getCaller()
    {
        return (string) $this->get('_caller', '');
    }

    /**
     * Sets the history extra data value.
     * 
     * @param   array   $data   The history extra data array.
     * 
     * @return  self
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function setHistoryData(array $data = [])
    {
        $this->set('_historyData', $data);

        return $this;
    }

    /**
     * Returns the customer information.
     * 
     * @return  array
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function getHistoryData()
    {
        return (array) $this->get('_historyData', []);
    }

    /**
     * Sets the search filters.
     * 
     * @param   array   $data   The search filters associative array.
     * 
     * @return  self
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function setFilters(array $data = [])
    {
        $this->set('_filters', $data);

        return $this;
    }

    /**
     * Returns the search filters.
     * 
     * @return  array
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function getFilters()
    {
        return (array) $this->get('_filters', []);
    }

    /**
     * Sets the booking information record.
     * 
     * @param   array   $booking    The booking record.
     * 
     * @return  self
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function setBooking(array $booking = [])
    {
        $this->set('_booking', $booking);

        return $this;
    }

    /**
     * Returns the booking information record.
     * 
     * @return  array
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function getBooking()
    {
        return (array) $this->get('_booking', []);
    }

    /**
     * Sets the room booking records.
     * 
     * @param   array   $room_booking   The room booking records.
     * 
     * @return  self
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function setRoomBooking(array $room_booking = [])
    {
        $this->set('_roomBooking', $room_booking);

        return $this;
    }

    /**
     * Returns the room booking records.
     * 
     * @return  array
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function getRoomBooking()
    {
        return (array) $this->get('_roomBooking', []);
    }

    /**
     * Sets the customer information.
     * 
     * @param   array   $customer   the customer array.
     * 
     * @return  self
     */
    public function setCustomer(array $customer = [])
    {
        $this->set('_customer', $customer);

        return $this;
    }

    /**
     * Returns the customer information.
     * 
     * @return  array
     */
    public function getCustomer()
    {
        return (array) $this->get('_customer', []);
    }

    /**
     * Sets a list of rooms data for a multi-room context.
     * 
     * @param   array   $rooms  List of rooms data arrays.
     * 
     * @return  self
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP)
     */
    public function setRooms(array $rooms)
    {
        // set rooms list data
        $this->set('_rooms', $rooms);

        if (!$this->getRoom() && ($rooms[0] ?? [])) {
            // populate data also for the current/first room
            $this->set('_room', (array) $rooms[0]);
        }

        return $this;
    }

    /**
     * Sets the room booking information for both single and multi room context.
     * 
     * @param   array   $room   The room data array.
     * @param   ?int    $index  Optional room index to update in a multi-room context.
     * 
     * @return  self
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP) added argument $index.
     */
    public function setRoom(array $room, ?int $index = null)
    {
        // set current room data
        $this->set('_room', $room);

        // access all rooms previously set
        $rooms = $this->getRooms();

        if ($index !== null && isset($rooms[$index])) {
            // overwrite requested room index
            $rooms[$index] = $room;
            $this->set('_rooms', $rooms);
        } elseif ($index === null) {
            // push room data to list
            $rooms[] = $room;
            $this->set('_rooms', $rooms);
        }

        return $this;
    }

    /**
     * Returns the information of all rooms set. This works
     * for both single-room and multi-room booking context.
     * 
     * @return  array
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP)
     */
    public function getRooms()
    {
        return (array) $this->get('_rooms', []);
    }

    /**
     * Returns the current room information.
     * 
     * @return  array
     */
    public function getRoom()
    {
        return (array) $this->get('_room', []);
    }

    /**
     * Tells if we are dealing with a multi-room booking.
     * 
     * @return  bool
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP)
     */
    public function isMultiRoom()
    {
        return (bool) (count($this->getRooms()) > 1);
    }

    /**
     * Sets the new booking ID created.
     * 
     * @param   int     $bid    the newly added record ID.
     * 
     * @return  self
     */
    protected function setNewBookingID($bid = 0)
    {
        $this->set('_newBookingID', $bid);

        return $this;
    }

    /**
     * Returns the new booking ID created, or 0.
     * 
     * @return  int
     */
    public function getNewBookingID()
    {
        return (int) $this->get('_newBookingID', 0);
    }

    /**
     * Sets the VCM action to be performed in order to sync the availability.
     * 
     * @param   string  $action     the VCM action, usually an HTML link.
     * 
     * @return  self
     */
    protected function setChannelManagerAction($action = '')
    {
        $this->set('_vcmAction', $action);

        return $this;
    }

    /**
     * Returns the VCM action (if any) to sync the availability.
     * 
     * @return  string
     */
    public function getChannelManagerAction()
    {
        return $this->get('_vcmAction', '');
    }

    /**
     * Sets the check-in and check-out times with hours and minutes.
     * 
     * @return  array   list of check-in and check-out hours and minutes.
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP) added support to room-level times.
     *          removed static cache because the object could be used multiple times.
     */
    public function loadCheckinOutTimes()
    {
        $timeopst = VikBooking::getTimeOpenStore();
        if (is_array($timeopst) && $timeopst) {
            $opent = VikBooking::getHoursMinutes($timeopst[0]);
            $closet = VikBooking::getHoursMinutes($timeopst[1]);
            $hcheckin = $opent[0];
            $mcheckin = $opent[1];
            $hcheckout = $closet[0];
            $mcheckout = $closet[1];
        } else {
            $hcheckin = 0;
            $mcheckin = 0;
            $hcheckout = 0;
            $mcheckout = 0;
        }

        // set global check-in/check-out hours and minutes
        $this->set('checkin_h', $hcheckin);
        $this->set('checkin_m', $mcheckin);
        $this->set('checkout_h', $hcheckout);
        $this->set('checkout_m', $mcheckout);

        // calculate room-level check-in/check-out hours and minutes
        $involvedRoomIds = array_map('intval', array_values(array_unique(array_column($this->getRooms(), 'id'))));
        $roomParamsAssoc = array_combine($involvedRoomIds, array_map(function($roomId) {
            return VikBooking::getRoomInfo($roomId, ['params'], true)['params'] ?? '';
        }, $involvedRoomIds));
        foreach ($roomParamsAssoc as $roomId => $roomParams) {
            $customCheckin  = VikBooking::getRoomParam('checkin', $roomParams);
            $customCheckout = VikBooking::getRoomParam('checkout', $roomParams);
            if ($customCheckin) {
                // set proper check-in time at room-level
                $parts = explode(':', $customCheckin);
                $this->set($roomId . '_checkin_h', (int) $parts[0]);
                $this->set($roomId . '_checkin_m', (int) ($parts[1] ?? 0));
            }
            if ($customCheckout) {
                // set proper check-out time at room-level
                $parts = explode(':', $customCheckout);
                $this->set($roomId . '_checkout_h', (int) $parts[0]);
                $this->set($roomId . '_checkout_m', (int) ($parts[1] ?? 0));
            }
        }

        // return the linear list
        return [
            $hcheckin,
            $mcheckin,
            $hcheckout,
            $mcheckout,
        ];
    }

    /**
     * Attempts to extract the Special Requests from the customer raw data.
     * 
     * @return  string
     * 
     * @since   1.16.5 (J) - 1.6.5 (WP)
     */
    public function extractSpecialRequests()
    {
        $raw_cust_data = $this->get('custdata', '');
        if (empty($raw_cust_data)) {
            return '';
        }

        $special_requests = '';
        if (preg_match("/(?:special_?requests:\s*)(.*?)$/is", $raw_cust_data, $match)) {
            $special_requests = $match[1];
        } elseif (preg_match("/(?:special_?request:\s*)(.*?)$/is", $raw_cust_data, $match)) {
            $special_requests = $match[1];
        } elseif (preg_match("/(?:special_?request\s*)(.*?)$/is", $raw_cust_data, $match)) {
            $special_requests = $match[1];
        } elseif (preg_match("/(?:" . JText::translate('ORDER_SPREQUESTS') . ":\s*)(.*?)$/is", $raw_cust_data, $match)) {
            $special_requests = $match[1];
        }

        return $special_requests;
    }

    /**
     * Runs before a reservation is created or updated.
     * 
     * @param   ?int    $bookingId  Optional reservation ID being updated.
     * 
     * @return  bool
     * 
     * @since   1.18.11 (J) - 1.8.11 (WP)
     */
    protected function preflight(?int $bookingId = null)
    {
        // if updating a reservation, fetch the previous details
        try {
            $prevBookingRegistry = $bookingId ? VBOBookingRegistry::getInstance(['id' => $bookingId]) : null;
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        if ($prevBookingRegistry) {
            // set booking registry snapshot prior to update
            $this->prevBookingRegistry = $prevBookingRegistry;
        }

        // availability helper
        $av_helper = VikBooking::getAvailabilityInstance(true);

        // validate mandatory fields
        $room = $this->getRoom();
        if (!$this->get('checkin') || !$this->get('checkout') || empty($room['id'])) {
            $this->setError('Missing mandatory fields');
            return false;
        }

        if ($this->get('checkin') >= $this->get('checkout')) {
            $this->setError('Invalid dates');
            return false;
        }

        // make sure we have the time for check-in and check-out
        if (!$this->get('checkin_h') || !$this->get('checkout_h')) {
            // make sure to set the times (hours/minutes) for check-in and check-out
            $this->loadCheckinOutTimes();

            // load default check-in/check-out times
            $defCheckinHour  = $this->get('checkin_h');
            $defCheckinMin   = $this->get('checkin_m');
            $defCheckoutHour = $this->get('checkout_h');
            $defCheckoutMin  = $this->get('checkout_m');

            if (!$this->isMultiRoom()) {
                // check if the only room we are booking has got check-in/check-out times at room-level
                $defCheckinHour  = $this->get($room['id'] . '_checkin_h', $defCheckinHour);
                $defCheckinMin   = $this->get($room['id'] . '_checkin_m', $defCheckinMin);
                $defCheckoutHour = $this->get($room['id'] . '_checkout_h', $defCheckoutHour);
                $defCheckoutMin  = $this->get($room['id'] . '_checkout_m', $defCheckoutMin);
            }

            // make sure check-in and check-out timestamps have been set to a proper time
            $from_info = getdate($this->get('checkin'));
            $to_info   = getdate($this->get('checkout'));
            if ((int) $from_info['hours'] != (int) $defCheckinHour) {
                $this->set('checkin', mktime((int) $defCheckinHour, (int) $defCheckinMin, 0, $from_info['mon'], $from_info['mday'], $from_info['year']));
            }
            if ((int) $to_info['hours'] != (int) $defCheckoutHour) {
                $this->set('checkout', mktime((int) $defCheckoutHour, (int) $defCheckoutMin, 0, $to_info['mon'], $to_info['mday'], $to_info['year']));
            }

            if ($this->isMultiRoom()) {
                // apply check-in and check-out times at room-level in case of different stay dates
                foreach ($this->getRooms() as $index => $roomData) {
                    // default values at booking-level
                    $room_checkin_ts = $this->get('checkin');
                    $room_checkout_ts = $this->get('checkout');
                    if (!empty($roomData['checkin']) && !is_numeric($roomData['checkin'])) {
                        // convert room-level stay dates into timestamps
                        $room_checkin_ts = strtotime($roomData['checkin']);
                        $room_checkout_ts = strtotime($roomData['checkout']);
                    }
                    // gather global or room-level check-in/check-out hours and minutes
                    $useCheckinHour  = $this->get($roomData['id'] . '_checkin_h', $defCheckinHour);
                    $useCheckinMin   = $this->get($roomData['id'] . '_checkin_m', $defCheckinMin);
                    $useCheckoutHour = $this->get($roomData['id'] . '_checkout_h', $defCheckoutHour);
                    $useCheckoutMin  = $this->get($roomData['id'] . '_checkout_m', $defCheckoutMin);
                    // calculate room-level stay dates and nights of stay
                    $roomData['checkin'] = strtotime(sprintf('%d:%d', (int) $useCheckinHour, (int) $useCheckinMin), $room_checkin_ts);
                    $roomData['checkout'] = strtotime(sprintf('%d:%d', (int) $useCheckoutHour, (int) $useCheckoutMin), $room_checkout_ts);
                    $roomData['nights'] = $av_helper->countNightsOfStay($roomData['checkin'], $roomData['checkout']);
                    // update room data
                    $this->setRoom($roomData, $index);
                }
            }
        }

        // number of nights of stay
        if (!$this->get('nights')) {
            $this->set('nights', $av_helper->countNightsOfStay($this->get('checkin'), $this->get('checkout')));
        }

        // fetch and apply turnover time before doing anything else
        $this->applyTurnover();

        return true;
    }

    /**
     * Creates a new reservation record after having constructed the
     * object by properly injecting all the necessary booking information.
     * 
     * @return  bool
     */
    public function create()
    {
        if (!$this->canCreate()) {
            $this->setError('Forbidden');
            return false;
        }

        /**
         * Run preflight to ensure data integrity.
         * 
         * @since   1.18.11 (J) - 1.8.11 (WP)
         */
        if (!$this->preflight()) {
            return false;
        }

        // get pool of rooms involved
        $rooms_pool = $this->getRoomsPool();
        if (!$rooms_pool) {
            if ($this->getError() === false) {
                // set generic error if not set already
                $this->setError('No rooms involved in the reservation');
            }
            return false;
        }

        // check if the rooms are available
        $rooms_available = $this->checkRoomsAvailability($rooms_pool);
        if (!$rooms_available && !$this->get('force_booking', 0) && !$this->get('set_closed', 0)) {
            // no forcing, no closure and room(s) fully booked means we have an error
            if (!$this->getError()) {
                $this->setError(JText::translate('VBBOOKNOTMADE'));
            }
            return false;
        }

        // detect if we are forcing the reservation
        $this->detectForcedReason($rooms_available);

        // store the customer information
        $this->storeCustomer();

        // calculate total amount and total tax
        $this->calculateTotal();

        // store booking and room-booking records
        if (!$this->storeReservationRecords($rooms_pool)) {
            if ($this->getError() === false) {
                // set generic error if not set already
                $this->setError('Could not create the reservation');
            }
            return false;
        }

        return true;
    }

    /**
     * Updates a reservation record after having constructed the
     * object by properly injecting all the necessary booking information.
     * Unlike the modify() method, this method follows the create() pattern.
     * 
     * @return  bool
     * 
     * @since   1.18.11 (J) - 1.8.11 (WP)
     */
    public function update()
    {
        // access booking ID
        $bookingId = (int) $this->get('booking_id', 0);

        if (!$bookingId) {
            $this->setError('Missing booking ID to update.');
            return false;
        }

        /**
         * Run preflight to ensure data integrity.
         */
        if (!$this->preflight($bookingId)) {
            return false;
        }

        // get pool of rooms involved
        $rooms_pool = $this->getRoomsPool();
        if (!$rooms_pool) {
            if ($this->getError() === false) {
                // set generic error if not set already
                $this->setError('No rooms involved in the reservation');
            }
            return false;
        }

        try {
            // check if the rooms are available for modification
            $rooms_available = $this->bookingModifiable($bookingId, $this->get('checkin'), $this->get('checkout'));
            if (!$rooms_available && !$this->get('force_booking', 0) && !$this->get('set_closed', 0)) {
                // no forcing, no closure and room(s) fully booked means we have an error
                if (!$this->getError()) {
                    $this->setError(JText::translate('VBBOOKNOTMADE'));
                }
                return false;
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // detect if we are forcing the reservation
        $this->detectForcedReason($rooms_available);

        // store or update the customer information
        $this->storeCustomer();

        // calculate total amount and total tax
        $this->calculateTotal();

        // update booking and room-booking records
        if (!$this->storeReservationRecords($rooms_pool, $bookingId)) {
            if ($this->getError() === false) {
                // set generic error if not set already
                $this->setError('Could not update the reservation');
            }
            return false;
        }

        return true;
    }

    /**
     * Searches for bookings according to specified filters.
     * 
     * @return  array
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     * @since   1.18.3 (J) - 1.8.3 (WP) added support for "ota_id" and "channel" filters.
     */
    public function search()
    {
        $dbo = JFactory::getDbo();

        $this->totalBookings = 0;

        $filters = $this->getFilters();

        if (!$filters) {
            $this->setError('Missing filters to search for a booking.');
            return [];
        }

        $q = $dbo->getQuery(true)
            ->select($dbo->qn('o') . '.*')
            ->select([
                $dbo->qn('c.first_name', 'customer_first_name'),
                $dbo->qn('c.last_name', 'customer_last_name'),
            ])
            ->from($dbo->qn('#__vikbooking_orders', 'o'))
            ->leftJoin($dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $dbo->qn('co.idorder') . ' = ' . $dbo->qn('o.id'))
            ->leftJoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('c.id') . ' = ' . $dbo->qn('co.idcustomer'))
            ->where(1);

        if (($filters['booking_id'] ?? null)) {
            $q->andWhere([
                $dbo->qn('o.id') . ' = ' . $dbo->q($filters['booking_id']),
                $dbo->qn('o.idorderota') . ' = ' . $dbo->q($filters['booking_id']),
            ], $glue = 'OR');
        }

        if (($filters['ota_id'] ?? null)) {
            $q->where($dbo->qn('o.idorderota') . ' = ' . $dbo->q($filters['ota_id']));
            if (($filters['channel'] ?? null)) {
                $q->where($dbo->qn('o.channel') . ' LIKE ' . (strpos($filters['channel'], '%') !== false ? $dbo->q($filters['channel']) : $dbo->q('%' . $filters['channel'] . '%')));
            } else {
                $q->where($dbo->qn('o.channel') . ' IS NOT NULL');
            }
        }

        if (($filters['status'] ?? null)) {
            $q->where($dbo->qn('o.status') . ' = ' . $dbo->q($filters['status']));
        }

        if (($filters['exclude_closures'] ?? false)) {
            $q->where($dbo->qn('o.closure') . ' = 0');
        }

        if (($filters['exclude_expired'] ?? false)) {
            // take only active reservations with a check-out date in the future
            $today_dt = JFactory::getDate('today', new DateTimeZone(date_default_timezone_get()));
            $q->where($dbo->qn('o.checkout') . ' >= ' . $dbo->q($today_dt->format('U', true)));
        }

        if (($filters['email'] ?? null)) {
            $q->where($dbo->qn('o.custmail') . ' = ' . $dbo->q($filters['email']));
        }

        if (($filters['phone'] ?? null)) {
            $q->where(sprintf('REPLACE(%s, \' \', \'\') LIKE REPLACE(%s, \' \', \'\')', 
                $dbo->qn('o.phone'),
                $dbo->q('%' . $filters['phone'])
            ));
        }

        if (($filters['date_range']['type'] ?? null) && (($filters['date_range']['start'] ?? null) || ($filters['date_range']['end'] ?? null))) {
            // search by date range
            $from_dt = JFactory::getDate(($filters['date_range']['start'] ?? $filters['date_range']['end']));
            $from_dt->modify('00:00:00');
            $to_dt = JFactory::getDate(($filters['date_range']['end'] ?? $filters['date_range']['start']));
            $to_dt->modify('23:59:59');

            // check the type of date
            if ($filters['date_range']['type'] == 'stay') {
                // find intersections of stay dates
                $q->andWhere([
                    '(' . $dbo->qn('o.checkin') . ' <= ' . $dbo->q($from_dt->format('U')) . ' AND ' . $dbo->qn('o.checkout') . ' >= ' . $dbo->q($to_dt->format('U')) . ')',
                    '(' . $dbo->qn('o.checkin') . ' >= ' . $dbo->q($from_dt->format('U')) . ' AND ' . $dbo->qn('o.checkout') . ' <= ' . $dbo->q($to_dt->format('U')) . ')',
                    '(' . $dbo->qn('o.checkin') . ' >= ' . $dbo->q($from_dt->format('U')) . ' AND ' . $dbo->qn('o.checkin') . ' < ' . $dbo->q($to_dt->format('U')) . ' AND ' . $dbo->qn('o.checkout') . ' >= ' . $dbo->q($to_dt->format('U')) . ')',
                    '(' . $dbo->qn('o.checkin') . ' <= ' . $dbo->q($from_dt->format('U')) . ' AND ' . $dbo->qn('o.checkout') . ' > ' . $dbo->q($from_dt->format('U')) . ' AND ' . $dbo->qn('o.checkout') . ' <= ' . $dbo->q($to_dt->format('U')) . ')',
                ], $glue = 'OR');
            } else {
                $column = $dbo->qn('o.checkin');
                if ($filters['date_range']['type'] == 'checkout') {
                    $column = $dbo->qn('o.checkout');
                } elseif ($filters['date_range']['type'] == 'creation') {
                    $column = $dbo->qn('o.ts');
                }
                $q->where($column . ' >= ' . $dbo->q($from_dt->format('U')));
                $q->where($column . ' <= ' . $dbo->q($to_dt->format('U')));
            }
        } else {
            // check for single date filters
            if (($filters['creation_date'] ?? null)) {
                // dates are expected to be in military format
                $creation = JFactory::getDate($filters['creation_date']);
                $creation->modify('00:00:00');
                $q->where($dbo->qn('o.ts') . ' >= ' . $dbo->q($creation->format('U')));
                $creation->modify('23:59:59');
                $q->where($dbo->qn('o.ts') . ' <= ' . $dbo->q($creation->format('U')));
            }

            if (($filters['checkin_date'] ?? null) && !($filters['checkout_date'] ?? null)) {
                // dates are expected to be in military format
                $checkin = JFactory::getDate($filters['checkin_date']);
                $checkin->modify('00:00:00');
                $q->where($dbo->qn('o.checkin') . ' >= ' . $dbo->q($checkin->format('U')));
                $checkin->modify('23:59:59');
                $q->where($dbo->qn('o.checkin') . ' <= ' . $dbo->q($checkin->format('U')));
            }

            if (($filters['checkout_date'] ?? null) && !($filters['checkin_date'] ?? null)) {
                // dates are expected to be in military format
                $checkout = JFactory::getDate($filters['checkout_date']);
                $checkout->modify('00:00:00');
                $q->where($dbo->qn('o.checkout') . ' >= ' . $dbo->q($checkout->format('U')));
                $checkout->modify('23:59:59');
                $q->where($dbo->qn('o.checkout') . ' <= ' . $dbo->q($checkout->format('U')));
            }

            if (($filters['checkin_date'] ?? null) && ($filters['checkout_date'] ?? null)) {
                // range of dates (dates are expected to be in military format)
                $checkin = JFactory::getDate($filters['checkin_date']);
                $checkin->modify('00:00:00');
                $checkout = JFactory::getDate($filters['checkout_date']);
                $checkout->modify('23:59:59');
                $q->andWhere([
                    $dbo->qn('o.checkin') . ' BETWEEN ' . $dbo->q($checkin->format('U')) . ' AND ' . $dbo->q($checkout->format('U')),
                    $dbo->qn('o.checkout') . ' BETWEEN ' . $dbo->q($checkin->format('U')) . ' AND ' . $dbo->q($checkout->format('U')),
                ], $glue = 'OR');
            }

            if (($filters['stay_date'] ?? null)) {
                // dates are expected to be in military format
                $staydt = JFactory::getDate($filters['stay_date']);
                $staydt->modify('23:59:59');
                $q->where($dbo->qn('o.checkin') . ' < ' . $dbo->q($staydt->format('U')));
                $q->where($dbo->qn('o.checkout') . ' > ' . $dbo->q($staydt->format('U')));
            }
        }

        if (($filters['customer_name'] ?? null)) {
            $q->where('CONCAT_WS(\' \', ' . $dbo->qn('c.first_name') . ', ' . $dbo->qn('c.last_name') . ') LIKE ' . $dbo->q('%' . $filters['customer_name'] . '%'));
        }

        if (($filters['confirmation_number'] ?? null)) {
            $q->where($dbo->qn('o.confirmnumber') . ' = ' . $dbo->q($filters['confirmation_number']));
        }

        if (($filters['room_name'] ?? null)) {
            // find the room involved from the given name
            $room_record = VikBooking::getAvailabilityInstance()->getRoomByName($filters['room_name']);
            if ($room_record) {
                $q->leftJoin($dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $dbo->qn('or.idorder') . ' = ' . $dbo->qn('o.id'));
                $q->where($dbo->qn('or.idroom') . ' = ' . (int) $room_record['id']);
            }
        }

        /**
         * It is now possible to use a custom ordering.
         * 
         * @since 1.17.1 (J) - 1.7.1 (WP)
         */
        switch ($filters['ordering'] ?? 'id') {
            case 'creation': $ordering = 'o.ts'; break;
            case 'checkin': $ordering = 'o.checkin'; break;
            case 'checkout': $ordering = 'o.checkout'; break;
            default: $ordering = 'o.id';
        }

        $q->order($dbo->qn($ordering) . ' ' . (strcasecmp($filters['direction'] ?? 'desc', 'desc') ? 'ASC' : 'DESC'));

        $dbo->setQuery($q, 0, ($filters['max_bookings'] ?? 0));
        $rows = $dbo->loadAssocList();

        $this->totalBookings = count($rows);

        /**
         * Calculate the total number of matching records.
         * 
         * @since 1.17.1 (J) - 1.7.1 (WP)
         */
        if ($this->totalBookings && $this->totalBookings == ($filters['max_bookings'] ?? 0)) {
            // set up the query used to count the matching records
            $dbo->setQuery($q->clear('select')->clear('offset')->clear('limit')->select('COUNT(1)'));
            $this->totalBookings = (int) $dbo->loadResult();
        }

        return $rows;
    }

    /**
     * Returns the total number of bookings matching the last search query made.
     * 
     * @return  int
     * 
     * @since   1.17.1 (J) - 1.7.1 (WP)
     */
    public function getTotBookingsFound()
    {
        return $this->totalBookings;
    }

    /**
     * Updates the total amount paid and the amount of (OTA) compensation. To be used for
     * OTA reservations only, previously validated. Runs often after an OTA notification.
     * 
     * @param   array   $options    Associative list of booking values to update.
     * @param   array   $booking    Optional associative booking record to update.
     * 
     * @return  bool
     * 
     * @since   1.18.3 (J) - 1.8.3 (WP)
     */
    public function updatePayoutCompensation(array $options, array $booking = [])
    {
        $dbo = JFactory::getDbo();

        if ($booking) {
            // update the internal reference
            $this->setBooking($booking);
        } else {
            // get the internal booking record
            $booking = $this->getBooking();
        }

        if (empty($booking['id'])) {
            // no booking information available
            return false;
        }

        // build booking record values to update
        $booking_record = new stdClass;
        $booking_record->id = $booking['id'];

        // access the booking history object
        $history_obj = VikBooking::getBookingHistoryInstance($booking['id']);

        // flag/counter that indicates if any booking record value was updated
        $booking_values_counter = 0;

        // check if a payout should be set
        if ($options['payout'] ?? null) {
            // update the booking total amount paid and register a payout received event
            $booking_record->totpaid = (float) $options['payout'];

            // update cached booking information for history
            $booking['totpaid'] = (float) $options['payout'];
            $history_obj->setBookingInfo($booking);

            // the payout received event type
            $ev_type = 'PO';

            // load current history
            $history_rows = array_reverse($history_obj->loadHistory());

            // check if there is a valid event with an amount paid greater than zero
            $prev_paid = 0;
            foreach ($history_rows as $hevent) {
                if ($hevent['totpaid'] < 1 || $hevent['totpaid'] == $options['payout'] || $hevent['type'] == $ev_type) {
                    // skip history records with no amount paid, amount paid equal to payout, or payout events
                    continue;
                }
                // amount paid found before payout notification
                $prev_paid = $hevent['totpaid'];
                break;
            }

            // sum any previously paid amount
            $booking_record->totpaid += $prev_paid;

            // update booking record in VBO
            if ($dbo->updateObject('#__vikbooking_orders', $booking_record, 'id')) {
                // increase counter
                $booking_values_counter++;
            }

            // build event description
            $ev_descr = preg_replace('/^[^_]*_/', '', (string) ($booking['channel'] ?? '')) . ': ' . $options['payout'];

            // set event extra data
            $history_obj->setExtraData([
                // this is the total amount paid up until now
                'payout_total' => (float) $options['payout'],
            ]);

            // silence the notification center
            $history_obj->setSilentNotificationCenter(true);

            // store event history log
            $history_obj->store($ev_type, $ev_descr);
        }

        // check if the commission (compensation) amount should be set
        if ($options['compensation'] ?? null) {
            // update the total commissions amount
            $booking_record->cmms = (float) $options['compensation'];

            // update cached booking information for history
            $booking['cmms'] = (float) $options['compensation'];
            $history_obj->setBookingInfo($booking);

            // update booking record in VBO
            if ($dbo->updateObject('#__vikbooking_orders', $booking_record, 'id')) {
                // increase counter
                $booking_values_counter++;
            }

            // set a generic CM event type
            $ev_type = 'CM';

            // build event description
            $ev_descr = 'OTA Commissions: ' . $options['compensation'];

            // set event extra data
            $history_obj->setExtraData([
                'cmms' => (float) $options['compensation'],
            ]);

            // silence the notification center
            $history_obj->setSilentNotificationCenter(true);

            // store event history log
            $history_obj->store($ev_type, $ev_descr);
        }

        return (bool) $booking_values_counter;
    }

    /**
     * Updates the value for the booking OTA type data payload.
     * 
     * @param   array   $data       OTA type-data payload to set.
     * @param   ?int    $bookingId  Optional booking ID to update.
     * 
     * @return  bool
     * 
     * @throws  Exception
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP)
     */
    public function updateOTATypeData(array $data, ?int $bookingId = null)
    {
        $dbo = JFactory::getDbo();

        if (!$bookingId) {
            // get the internal booking record
            $booking = $this->getBooking();
            $bookingId = $booking['id'] ?? null;
        }

        if (empty($bookingId)) {
            throw new Exception('Missing booking ID.', 400);
        }

        // update booking record
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->update($dbo->qn('#__vikbooking_orders'))
                ->set($dbo->qn('ota_type_data') . ' = ' . $dbo->q(json_encode($data)))
                ->where($dbo->qn('id') . ' = ' . $bookingId)
        );
        $dbo->execute();

        return (bool) $dbo->getAffectedRows();
    }

    /**
     * Modifies the requested booking ID according to the provided options.
     * This method does not support all rate plan options like for the creation of a
     * new booking. This is a method for making quick updates concerning a room switch,
     * a change of stay dates, new booking total amount, guests, add extra services etc..
     * For modifying a reservation with the same data as for the creation, see update().
     * 
     * @param   array   $options    List of details to perform the modification.
     * 
     * @return  bool
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function modify(array $options)
    {
        $dbo = JFactory::getDbo();

        // access the previous booking details
        $prev_booking = $this->getBooking();

        // access the current rooms booked
        $roomBooking  = $this->getRoomBooking();

        // gather modification options
        $booking_id = $options['booking_id'] ?? $prev_booking['id'] ?? 0;

        if (!$booking_id) {
            $this->setError('Missing booking ID.');
            return false;
        }

        if (!$prev_booking) {
            // load current booking record if not injected
            $prev_booking = VikBooking::getBookingInfoFromID($booking_id);
            if (!$prev_booking) {
                $this->setError('Booking not found.');
                return false;
            }
        }

        if (!$roomBooking) {
            // load current rooms booked
            $roomBooking = VikBooking::loadOrdersRoomsData($booking_id);
        }

        // do not touch this array property because it's used by VCM
        $prev_booking['rooms_info'] = $roomBooking;

        // list of operations to trigger/perform
        $trigger_operations = [];

        // list of history description rows
        $history_descr_rows = [];

        // access availability helper
        $av_helper = VikBooking::getAvailabilityInstance(true);

        // calculate the new stay dates, if different
        $diff_stay_dates = false;
        $set_checkin  = date('Y-m-d', $prev_booking['checkin']);
        $set_checkout = date('Y-m-d', $prev_booking['checkout']);
        if (($options['checkin'] ?? null)) {
            // date is expected in military format
            $diff_stay_dates = $diff_stay_dates || ($options['checkin'] != $set_checkin);
            $set_checkin = $options['checkin'];
        }
        if (($options['checkout'] ?? null)) {
            // date is expected in military format
            $diff_stay_dates = $diff_stay_dates || ($options['checkout'] != $set_checkout);
            $set_checkout = $options['checkout'];
        }

        // ensure the stay dates are valid
        if (JFactory::getDate($set_checkin) >= JFactory::getDate($set_checkout)) {
            $this->setError('Invalid stay dates provided.');
            return false;
        }

        // ensure we are not changing dates for a split-stay reservation
        if ($diff_stay_dates && !empty($prev_booking['split_stay'])) {
            // we receive the stay dates at booking record, so we cannot proceed with the update
            $this->setError('Cannot modify the stay dates for a split-stay reservation. Please do it manually.');
            return false;
        }

        // set dates involved
        $av_helper->setStayDates($set_checkin, $set_checkout);

        // count new nights of stay
        $set_nights = $av_helper->countNightsOfStay();

        // gather stay timestamps
        list($set_checkin_ts, $set_checkout_ts) = $av_helper->getStayDates(true);

        // load the current busy record IDs before any modification, if any
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_ordersbusy'))
                ->where($dbo->qn('idorder') . ' = ' . (int) $booking_id)
        );
        $busy_ids = array_column($dbo->loadAssocList(), 'idbusy');

        // first off, check if any room switch was requested (recommended one switch at most)
        $switching_details = [];
        if ($options['switch_rooms'] ?? []) {
            // get all room IDs for the switch that were not booked already
            $booked_rooms = array_column($roomBooking, 'idroom');
            $new_missing_rooms = array_values(array_diff((array) $options['switch_rooms'], $booked_rooms));

            // scan all rooms requested for the switch that were not booked already
            foreach ($new_missing_rooms as $index => $switch_room_id) {
                if (!isset($roomBooking[$index])) {
                    // adding more rooms is not supported
                    break;
                }
                // ensure the room switch is allowed (room should be available on the new dates)
                $switched_room_info = VikBooking::getRoomInfo($switch_room_id, ['id', 'name', 'units']);
                if (!$switched_room_info) {
                    $this->setError('The requested room could not be found for the switch.');
                    return false;
                }
                if (!VikBooking::roomBookable($switch_room_id, 1, $set_checkin_ts, $set_checkout_ts, $busy_ids)) {
                    // abort by setting a descriptive error message
                    $this->setError(sprintf(
                        'The room %s is not available from %s to %s, and so the room switch cannot be made.',
                        $switched_room_info['name'] ?? '',
                        $set_checkin,
                        $set_checkout
                    ));
                    return false;
                }
            }

            // scan again all rooms to be switched once we know they are available
            foreach ($new_missing_rooms as $index => $switch_room_id) {
                if (!isset($roomBooking[$index])) {
                    // adding more rooms is not supported
                    break;
                }

                // update room-booking record by switching room ID
                $q = $dbo->getQuery(true)
                    ->update($dbo->qn('#__vikbooking_ordersrooms'))
                    ->set($dbo->qn('idroom') . ' = ' . (int) $switch_room_id)
                    ->where($dbo->qn('idorder') . ' = ' . (int) $booking_id)
                    ->where($dbo->qn('idroom') . ' = ' . (int) ($roomBooking[$index]['idroom'] ?? 0));
                $dbo->setQuery($q, 0, 1);
                $dbo->execute();

                if ($busy_ids) {
                    // update busy records with new stay dates just for the switched room
                    $q = $dbo->getQuery(true)
                        ->update($dbo->qn('#__vikbooking_busy'))
                        ->set($dbo->qn('idroom') . ' = ' . (int) $switch_room_id)
                        ->set($dbo->qn('checkin') . ' = ' . $dbo->q($set_checkin_ts))
                        ->set($dbo->qn('checkout') . ' = ' . $dbo->q($set_checkout_ts))
                        ->set($dbo->qn('realback') . ' = ' . $dbo->q($set_checkout_ts + (VikBooking::getHoursRoomAvail() * 3600)))
                        ->where($dbo->qn('id') . ' IN (' . implode(', ', array_map('intval', $busy_ids)) . ')')
                        ->where($dbo->qn('idroom') . ' = ' . (int) ($roomBooking[$index]['idroom'] ?? 0));
                    $dbo->setQuery($q, 0, 1);
                    $dbo->execute();

                    // register room switching details
                    $switching_details[$index] = $switch_room_id;

                    // register CM sync operation
                    $trigger_operations[] = 'vcm_sync';
                }

                // register history description row
                $switched_room_info = VikBooking::getRoomInfo($switch_room_id, ['id', 'name', 'units']);
                $prev_room_info     = VikBooking::getRoomInfo($roomBooking[$index]['idroom'] ?? 0, ['id', 'name', 'units']);
                $history_descr_rows[] = sprintf('%s switched with %s.', $prev_room_info['name'] ?? '', $switched_room_info['name'] ?? '');
            }
        }

        // start query builder for booking record
        $bookingQ = $dbo->getQuery(true)
            ->update($dbo->qn('#__vikbooking_orders'))
            ->where($dbo->qn('id') . ' = ' . (int) $booking_id);

        // modify stay dates, if requested
        if ($diff_stay_dates) {
            // ensure all rooms are bookable on the new stay dates
            if ($prev_booking['status'] == 'confirmed') {
                foreach ($roomBooking as $kor => $or) {
                    if ($switching_details[$kor] ?? null) {
                        // this room index was switched with another room, hence we know it was available
                        continue;
                    }
                    if (!VikBooking::roomBookable($or['idroom'], 1, $set_checkin_ts, $set_checkout_ts, $busy_ids)) {
                        // abort
                        $abort_room_info = VikBooking::getRoomInfo($or['idroom'], ['id', 'name', 'units']);
                        $this->setError(sprintf(
                            'The room %s is not available from %s to %s, and so the stay dates cannot be modified.',
                            $abort_room_info['name'] ?? '',
                            $set_checkin,
                            $set_checkout
                        ));
                        return false;
                    }
                }
            }

            // set booking values to update
            $bookingQ->set($dbo->qn('checkin') . ' = ' . $dbo->q($set_checkin_ts));
            $bookingQ->set($dbo->qn('checkout') . ' = ' . $dbo->q($set_checkout_ts));
            $bookingQ->set($dbo->qn('days') . ' = ' . $dbo->q($set_nights));

            // update busy records, if any (reservation status could be confirmed)
            if ($busy_ids) {
                // update busy records with new stay dates for all rooms
                $dbo->setQuery(
                    $dbo->getQuery(true)
                        ->update($dbo->qn('#__vikbooking_busy'))
                        ->set($dbo->qn('checkin') . ' = ' . $dbo->q($set_checkin_ts))
                        ->set($dbo->qn('checkout') . ' = ' . $dbo->q($set_checkout_ts))
                        ->set($dbo->qn('realback') . ' = ' . $dbo->q($set_checkout_ts + (VikBooking::getHoursRoomAvail() * 3600)))
                        ->where($dbo->qn('id') . ' IN (' . implode(', ', array_map('intval', $busy_ids)) . ')')
                );
                $dbo->execute();

                // register CM sync operation
                $trigger_operations[] = 'vcm_sync';
            }

            // register operation to trigger the shared calendars
            $trigger_operations[] = 'shared_calendars';
        }

        // update number of guests, if requested
        if (($options['guests']['adults'] ?? null) || ($options['guests']['children'] ?? null)) {
            // update the requested number of guests ONLY on the first room booked
            $q = $dbo->getQuery(true)
                ->update($dbo->qn('#__vikbooking_ordersrooms'))
                ->set($dbo->qn('adults') . ' = ' . (int) ($options['guests']['adults'] ?? $roomBooking[0]['adults']))
                ->set($dbo->qn('children') . ' = ' . (int) ($options['guests']['children'] ?? $roomBooking[0]['children']))
                ->where($dbo->qn('idorder') . ' = ' . (int) $booking_id);
            $dbo->setQuery($q, 0, 1);
            $dbo->execute();

            // register history description row
            $history_descr_rows[] = sprintf(
                'New adults %d, new children %d.',
                (int) ($options['guests']['adults'] ?? $roomBooking[0]['adults']),
                (int) ($options['guests']['children'] ?? $roomBooking[0]['children'])
            );
        }

        // check if extra services should be added and calculate the booking cost difference
        $new_extras_cost = 0;
        if (is_array(($options['add_extra_services'] ?? null))) {
            $current_extras = !empty($roomBooking[0]['extracosts']) ? json_decode($roomBooking[0]['extracosts'], true) : [];
            $current_extras = is_array($current_extras) ? $current_extras : [];
            $new_extras = [];
            foreach ($options['add_extra_services'] as $extras) {
                if (!is_array($extras) || (!isset($extras['name']) && !isset($extras['cost']))) {
                    // invalid extra service structure
                    continue;
                }

                // build new extra service
                $new_extra = [
                    'name'  => (string) ($extras['name'] ?? 'Custom Extra'),
                    'cost'  => (float) ($extras['cost'] ?? 0),
                    'idtax' => null,
                ];

                // push custom extra service
                $current_extras[] = $new_extra;

                // push the custom extra service in the new list
                $new_extras[] = $new_extra;
            }

            if ($new_extras) {
                // update the extra services ONLY on the first room booked
                $q = $dbo->getQuery(true)
                    ->update($dbo->qn('#__vikbooking_ordersrooms'))
                    ->set($dbo->qn('extracosts') . ' = ' . $dbo->q(json_encode($current_extras)))
                    ->where($dbo->qn('idorder') . ' = ' . (int) $booking_id);
                $dbo->setQuery($q, 0, 1);
                $dbo->execute();

                // register history description row
                $history_descr_rows[] = sprintf(
                    'New extras: %s.',
                    implode(', ', array_column($new_extras, 'name'))
                );

                // check if we need to increase the booking total amount
                $new_extras_cost = array_sum(array_column($new_extras, 'cost'));

                if ($new_extras_cost > 0 && (float) ($options['cost_difference'] ?? 0) < $new_extras_cost) {
                    // increase the "cost difference" due to the newly added extra services
                    $options['cost_difference'] = ($options['cost_difference'] ?? 0) + $new_extras_cost;
                }
            }
        }

        // check if the booking total amount should change
        if ($options['cost_difference'] ?? null) {
            // this difference should be summed to (or deducted from) the current booking total value
            $bookingQ->set($dbo->qn('total') . ' = ' . ($prev_booking['total'] + (float) $options['cost_difference']));

            // register history description row
            $history_descr_rows[] = sprintf(
                'Booking total cost difference calculated: %d.',
                (float) $options['cost_difference']
            );

            // calculate the cost difference just for the rooms
            $rooms_cost_difference = (float) $options['cost_difference'] - $new_extras_cost;

            if ($rooms_cost_difference) {
                // this value should be summed to (or deducted from) the current room rate to have a proper calculation
                $new_room_cost  = null;
                $room_cost_prop = null;
                if (!empty($roomBooking[0]['cust_cost'])) {
                    $new_room_cost  = $roomBooking[0]['cust_cost'] + $rooms_cost_difference;
                    $room_cost_prop = 'cust_cost';
                } elseif (!empty($roomBooking[0]['room_cost'])) {
                    $new_room_cost  = $roomBooking[0]['room_cost'] + $rooms_cost_difference;
                    $room_cost_prop = 'room_cost';
                }

                if ($room_cost_prop) {
                    // we can update the room cost for the difference calculated ONLY on the first room booked
                    // if no room cost was found, maybe because of a tariff, we would keep just the total changed
                    $q = $dbo->getQuery(true)
                        ->update($dbo->qn('#__vikbooking_ordersrooms'))
                        ->set($dbo->qn($room_cost_prop) . ' = ' . $new_room_cost)
                        ->where($dbo->qn('idorder') . ' = ' . (int) $booking_id);
                    $dbo->setQuery($q, 0, 1);
                    $dbo->execute();
                }
            }
        }

        if ($options['extra_notes'] ?? '') {
            // update administrator notes
            $bookingQ->set($dbo->qn('adminnotes') . ' = ' . $dbo->q(trim($prev_booking['adminnotes'] . "\n" . $options['extra_notes'])));
        }

        if (($options['custmail'] ?? '') || ($options['customer_email'] ?? '')) {
            // update guest email address at booking level
            $set_cust_mail = $options['custmail'] ?? $options['customer_email'] ?? '';
            if (preg_match("/^[^@]+@[^@]+\.[^@]+$/", $set_cust_mail)) {
                // email pattern is safe
                $bookingQ->set($dbo->qn('custmail') . ' = ' . $dbo->q(trim($set_cust_mail)));
            }
        }

        // finally, update the booking record
        try {
            // make sure something to update was set by using the apposite getter magic method
            if ($bookingQ->set) {
                // some booking record values should be updated
                $dbo->setQuery($bookingQ);
                $dbo->execute();
            }
        } catch (Throwable $e) {
            $this->setError($e->getMessage());
            return false;
        }

        // update booking history
        $history_obj = VikBooking::getBookingHistoryInstance($booking_id);

        $now_user  = JFactory::getUser();
        $caller_id = $now_user->name ? "({$now_user->name})" : '';
        if ($this->getCaller()) {
            $caller_id = '(' . $this->getCaller() . ')';
            if ($this->getHistoryData()) {
                $history_obj->setExtraData($this->getHistoryData());
            }
        }

        // update Booking History
        $history_obj->store('MB', $caller_id . ($history_descr_rows ? "\n" . implode("\n", $history_descr_rows) : ''));

        // check for the operations to perform
        if (in_array('shared_calendars', $trigger_operations)) {
            // unset any previously booked room due to calendar sharing
            VikBooking::cleanSharedCalendarsBusy($booking_id);
            // check if some of the rooms booked have shared calendars
            VikBooking::updateSharedCalendars($booking_id);
        }

        if (in_array('vcm_sync', $trigger_operations)) {
            // invoke Channel Manager
            $vcm_autosync = VikBooking::vcmAutoUpdate();
            if ($vcm_autosync > 0) {
                $vcm_obj = VikBooking::getVcmInvoker();
                $vcm_obj->setOids([$booking_id])->setSyncType('modify')->setOriginalBooking($prev_booking);
                $sync_result = $vcm_obj->doSync();
                if ($sync_result === false) {
                    // set error message
                    $vcm_err = $vcm_obj->getError();
                    $this->setError(JText::translate('VBCHANNELMANAGERRESULTKO') . (!empty($vcm_err) ? ' - ' . $vcm_err : ''));
                }
            } elseif (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'synch.vikbooking.php')) {
                // set the necessary action to invoke VCM manually
                $this->setChannelManagerAction(
                    JText::translate('VBCHANNELMANAGERINVOKEASK') . ' ' .
                    '<form action="index.php?option=com_vikbooking" method="post">' .
                    '<input type="hidden" name="option" value="com_vikbooking"/>' .
                    '<input type="hidden" name="task" value="invoke_vcm"/>' .
                    '<input type="hidden" name="stype" value="modify"/>' .
                    '<input type="hidden" name="cid[]" value="' . $booking_id . '"/>' .
                    '<input type="hidden" name="origb" value="' . urlencode(json_encode($prev_booking)) . '"/>' .
                    '<button type="submit" class="btn btn-primary">' . JText::translate('VBCHANNELMANAGERSENDRQ') . '</button>' .
                    '</form>'
                );
            }
        }

        if (($options['ota_reporting'] ?? null) && $diff_stay_dates) {
            // perform the OTA reporting action, if allowed
            if (class_exists('VCMOtaReporting') && VCMOtaReporting::getInstance($ord)->stayChangeAllowed()) {
                // check if an OTA reporting action is needed
                $ota_stay_change_data = [];
                foreach ($roomBooking as $kor => $or) {
                    // set room data for stay change
                    $ota_stay_change_room = [
                        'idroom'   => $or['idroom'],
                        'checkin'  => $set_checkin,
                        'checkout' => $set_checkout,
                    ];
                    if (isset($or['modified_price'])) {
                        $ota_stay_change_room['price'] = $or['modified_price'];
                    }
                    // push room data for stay change
                    $ota_stay_change_data[] = $ota_stay_change_room;
                }

                // notify the OTA through Vik Channel Manager
                $ota_reporting = VCMOtaReporting::getInstance();
                $ota_result    = $ota_reporting->notifyStayChange($ota_stay_change_data);
                if (!$ota_result) {
                    // register error message
                    $this->setError($ota_reporting->getError());
                }
            }
        }

        return true;
    }

    /**
     * Deletes the requested booking ID and related records.
     * 
     * @param   array   $options     List of details to perform the cancellation.
     * @param   bool    $reCreating  True if the booking is being re-created (updated).
     * 
     * @return  bool
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     * @since   1.18.11 (J) - 1.8.11 (WP) added argument $reCreating.
     */
    public function delete(array $options, bool $reCreating = false)
    {
        $dbo = JFactory::getDbo();

        $booking_id   = $options['booking_id'] ?? 0;
        $canc_reason  = $options['cancellation_reason'] ?? '';
        $purge_remove = $options['purge_remove'] ?? false;

        if ($reCreating && $this->prevBookingRegistry && $this->prevBookingRegistry->getID() == $booking_id) {
            $booking = $this->prevBookingRegistry->getData();
        } else {
            $booking = VikBooking::getBookingInfoFromID($booking_id);
        }

        if (!$booking) {
            $this->setError('Booking not found.');
            return false;
        }

        if (!$reCreating && $booking['status'] === 'cancelled' && !$purge_remove) {
            $this->setError(sprintf('Booking ID %d is already cancelled.', $booking['id']));
            return false;
        }

        if (!$reCreating && class_exists('VCMFeesCancellation')) {
            // let VCM detect if there are any constraints for the cancellation
            $canc_denied = VCMFeesCancellation::getInstance($booking, $anew = true)->isBookingConstrained();
            if ($canc_denied) {
                // set error message
                $canc_deny_error = VCMFeesCancellation::getInstance()->getError();
                $this->setError($canc_deny_error ?: 'Booking cannot be cancelled due to OTA contraints.');
                return false;
            }
        }

        // access the current user
        $now_user = JFactory::getUser();

        // whether OTAs should be notified
        $notify_otas = false;

        if ($booking['status'] != 'cancelled') {
            if (!$reCreating) {
                // update status to cancelled
                $q = $dbo->getQuery(true)
                    ->update($dbo->qn('#__vikbooking_orders'))
                    ->set($dbo->qn('status') . ' = ' . $dbo->q('cancelled'))
                    ->where($dbo->qn('id') . ' = ' . (int) $booking['id']);
                if (!empty($canc_reason)) {
                    $set_canc_reason = (!empty($booking['adminnotes']) ? $booking['adminnotes'] . "\n" : '') . $canc_reason;
                    $q->set($dbo->qn('adminnotes') . ' = ' . $dbo->q($set_canc_reason));
                }
                $dbo->setQuery($q);
                $dbo->execute();
            }

            // delete temporarily locked records, if any
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->delete($dbo->qn('#__vikbooking_tmplock'))
                    ->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
            );
            $dbo->execute();

            if (!$reCreating && $booking['status'] == 'confirmed') {
                // turn flag on
                $notify_otas = true;
            }

            if (!$reCreating) {
                // access history object
                $history_obj = VikBooking::getBookingHistoryInstance($booking['id']);

                $caller_id = $now_user->name ? "({$now_user->name})" : '';
                if ($this->getCaller()) {
                    $caller_id = '(' . $this->getCaller() . ')';
                    if ($this->getHistoryData()) {
                        $history_obj->setExtraData($this->getHistoryData());
                    }
                }

                // update Booking History
                $history_obj->store('CB', $caller_id);
            }
        }

        /**
         * In case of pending bookings being cancelled, schedule the release through VCM.
         * 
         * @since   1.18.8 (J) - 1.8.8 (WP)
         */
        if ($booking['status'] == 'standby' && method_exists('VCMRequestAvailability', 'setForRelease')) {
            // let the CM schedule the release of the involved and unconfirmed booking IDs, if needed
            VCMRequestAvailability::getInstance()->setForRelease([$booking['id']]);
        }

        // always attempt to free records up
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_ordersbusy'))
                ->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
        );
        foreach ($dbo->loadAssocList() as $ob) {
            // delete busy record
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->delete($dbo->qn('#__vikbooking_busy'))
                    ->where($dbo->qn('id') . ' = ' . (int) $ob['idbusy'])
            );
            $dbo->execute();
        }

        // delete booking-busy-record relations
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->delete($dbo->qn('#__vikbooking_ordersbusy'))
                ->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
        );
        $dbo->execute();

        // check for purge removal
        if (!$reCreating && $booking['status'] === 'cancelled' && $purge_remove) {
            // delete booking-customer relation
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->delete($dbo->qn('#__vikbooking_customers_orders'))
                    ->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
            );
            $dbo->execute();

            // delete booking-room relations
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->delete($dbo->qn('#__vikbooking_ordersrooms'))
                    ->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
            );
            $dbo->execute();

            // delete booking-history relations
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->delete($dbo->qn('#__vikbooking_orderhistory'))
                    ->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
            );
            $dbo->execute();

            // delete the booking record
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->delete($dbo->qn('#__vikbooking_orders'))
                    ->where($dbo->qn('id') . ' = ' . (int) $booking['id'])
            );
            $dbo->execute();

            // in case of split stay booking, remove the transient
            if ($booking['split_stay']) {
                VBOFactory::getConfig()->remove('split_stay_' . $booking['id']);
            }
        }

        if ($reCreating === true) {
            // when updating a booking record, get rid of all rooms previously assigned

            // delete booking-room relations
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->delete($dbo->qn('#__vikbooking_ordersrooms'))
                    ->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
            );
            $dbo->execute();

            // in case of split stay booking, remove the transient
            if ($booking['split_stay']) {
                VBOFactory::getConfig()->remove('split_stay_' . $booking['id']);
            }
        }

        if (!$reCreating && $notify_otas) {
            $vcm_autosync = VikBooking::vcmAutoUpdate();
            if ($vcm_autosync > 0) {
                $vcm_obj = VikBooking::getVcmInvoker();
                $vcm_obj->setOids([$booking['id']])->setSyncType('cancel');
                $sync_result = $vcm_obj->doSync();
                if ($sync_result === false) {
                    // set error message
                    $vcm_err = $vcm_obj->getError();
                    $this->setError(JText::translate('VBCHANNELMANAGERRESULTKO') . (!empty($vcm_err) ? ' - ' . $vcm_err : ''));
                }
            }
        }

        return true;
    }

    /**
     * Sets a booking ID to confirmed according to the provided options.
     * 
     * @param   array   $options    List of details to perform the update.
     * 
     * @return  bool
     * 
     * @since   1.17.3 (J) - 1.7.3 (WP)
     */
    public function setConfirmed(array $options)
    {
        $dbo = JFactory::getDbo();

        // access the current booking details, if any
        $booking = $this->getBooking();

        // access the current rooms booked, if any
        $roomBooking  = $this->getRoomBooking();

        // gather modification options
        $booking_id = $options['booking_id'] ?? $booking['id'] ?? 0;

        if (!$booking_id) {
            $this->setError('Missing booking ID.');
            return false;
        }

        if (!$booking) {
            // load current booking record if not injected
            $booking = VikBooking::getBookingInfoFromID($booking_id);
            if (!$booking) {
                $this->setError('Booking not found.');
                return false;
            }
        }

        if (!$roomBooking) {
            // load current rooms booked
            $roomBooking = VikBooking::loadOrdersRoomsData($booking_id);
        }

        // make sure the booking status is not already confirmed
        if (!strcasecmp($booking['status'], 'confirmed')) {
            $this->setError('Booking is already confirmed.');
            return false;
        }

        // memorize the original booking status for VCM in case of OTA booking
        $original_book_status = null;
        if (!empty($booking['idorderota']) && !empty($booking['channel'])) {
            $original_book_status = $booking['status'];
        }

        // availability helper
        $av_helper = VikBooking::getAvailabilityInstance(true);

        // room stay dates in case of split stay
        $room_stay_dates = [];
        if ($booking['split_stay']) {
            $room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $booking['id'], []);
        }

        // make sure all rooms are available for confirmation
        $turnover_secs = VikBooking::getHoursRoomAvail() * 3600;
        $realback = $turnover_secs + $booking['checkout'];
        $allbook  = true;
        $notavail = [];

        /**
         * We need to calculate a minus operator for each room that was booked more than once.
         * In case we are confirming a booking for more than one unit of the same room, we need to
         * make sure the calculation is made properly, as only one unit of that room could be free.
         */
        $units_minus_oper = [];
        foreach ($roomBooking as $ind => $or) {
            if (!isset($units_minus_oper[$or['idroom']])) {
                $units_minus_oper[$or['idroom']] = -1;
            }
            // increase counter
            $units_minus_oper[$or['idroom']]++;
            if (!empty($room_stay_dates)) {
                // split stay rooms never have the same stay dates, but they should also be different rooms
                $units_minus_oper[$or['idroom']] = 0;
            }
        }

        // check availability for each room involved
        foreach ($roomBooking as $ind => $or) {
            // determine proper values for this room
            $room_stay_checkin  = $booking['checkin'];
            $room_stay_checkout = $booking['checkout'];
            $room_stay_nights   = $booking['days'];
            if ($booking['split_stay'] && $room_stay_dates && isset($room_stay_dates[$ind]) && $room_stay_dates[$ind]['idroom'] == $or['idroom']) {
                $room_stay_checkin  = $room_stay_dates[$ind]['checkin_ts'] ?: $room_stay_dates[$ind]['checkin'];
                $room_stay_checkout = $room_stay_dates[$ind]['checkout_ts'] ?: $room_stay_dates[$ind]['checkout'];
                $room_stay_nights   = $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
                // inject nights calculated for this room
                $room_stay_dates[$ind]['nights'] = $room_stay_nights;
            }

            // get room record
            $room_record = VikBooking::getRoomInfo($or['idroom']);

            // check if the room is available
            if (!VikBooking::roomBookable($or['idroom'], (($room_record['units'] ?? 0) - $units_minus_oper[$or['idroom']]), $room_stay_checkin, $room_stay_checkout)) {
                $allbook = false;
                $notavail[] = $room_record['name'] ?? '?';
            }
        }

        // ensure all rooms involved were available or forced to be
        if (!$allbook && !($options['force_availability'] ?? false)) {
            $this->setError(sprintf('Some rooms are no longer available: %s', implode(', ', $notavail)));
            return false;
        }

        // occupy the involved rooms on the db
        foreach ($roomBooking as $ind => $or) {
            // determine proper values for this room
            $room_stay_checkin  = $booking['checkin'];
            $room_stay_checkout = $booking['checkout'];
            $room_stay_realback = $realback;
            if ($booking['split_stay'] && $room_stay_dates && isset($room_stay_dates[$ind]) && $room_stay_dates[$ind]['idroom'] == $or['idroom']) {
                $room_stay_checkin  = $room_stay_dates[$ind]['checkin_ts'] ?: $room_stay_dates[$ind]['checkin'];
                $room_stay_checkout = $room_stay_dates[$ind]['checkout_ts'] ?: $room_stay_dates[$ind]['checkout'];
                $room_stay_realback = $turnover_secs + $room_stay_checkout;
            }

            // build busy record
            $busy_record = new stdClass;
            $busy_record->idroom   = (int) $or['idroom'];
            $busy_record->checkin  = (int) $room_stay_checkin;
            $busy_record->checkout = (int) $room_stay_checkout;
            $busy_record->realback = (int) $room_stay_realback;

            // store busy record and obtain the newly created ID
            $dbo->insertObject('#__vikbooking_busy', $busy_record, 'id');
            $lid = $busy_record->id ?? 0;

            // build busy relation record
            $obusy_record = new stdClass;
            $obusy_record->idorder = (int) $booking['id'];
            $obusy_record->idbusy  = (int) $lid;

            // store busy relation record
            $dbo->insertObject('#__vikbooking_ordersbusy', $obusy_record, 'id');
        }

        // delete temporarily locked records, if any
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->delete($dbo->qn('#__vikbooking_tmplock'))
                ->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
        );
        $dbo->execute();

        // update booking status (and notes, if any)
        $q = $dbo->getQuery(true)
            ->update($dbo->qn('#__vikbooking_orders'))
            ->set($dbo->qn('status') . ' = ' . $dbo->q('confirmed'))
            ->where($dbo->qn('id') . ' = ' . (int) $booking['id']);
        if ($options['extra_notes'] ?? '') {
            // update administrator notes
            $q->set($dbo->qn('adminnotes') . ' = ' . $dbo->q(trim($booking['adminnotes'] . "\n" . $options['extra_notes'])));
        }
        $dbo->setQuery($q);
        $dbo->execute();

        if (!empty($booking['idquote'])) {
            // let the quote model handle the release of other locked room records (OTAs included), if any
            VBOMvcModel::getInstance('quote')->releaseUnconfirmedSolutions((int) $booking['idquote'], (int) $booking['id']);
        }

        // set booking confirmation number
        $confirmnumber = VikBooking::generateConfirmNumber($booking['id'], true);

        // assign room specific unit(s)
        $set_room_indexes = VikBooking::autoRoomUnit();
        $room_indexes_usemap = [];

        foreach ($roomBooking as $kor => $or) {
            // determine proper values for this room
            $room_stay_checkin  = $booking['checkin'];
            $room_stay_checkout = $booking['checkout'];
            $room_stay_nights   = $booking['days'];
            if ($booking['split_stay'] && $room_stay_dates && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
                $room_stay_checkin  = $room_stay_dates[$kor]['checkin_ts'] ?: $room_stay_dates[$kor]['checkin'];
                $room_stay_checkout = $room_stay_dates[$kor]['checkout_ts'] ?: $room_stay_dates[$kor]['checkout'];
                $room_stay_nights   = $room_stay_dates[$kor]['nights'];
            }

            // assign room specific unit
            if ($set_room_indexes === true) {
                $room_indexes = VikBooking::getRoomUnitNumsAvailable($booking, $or['idroom']);
                $use_ind_key = 0;
                if ($room_indexes) {
                    if (!isset($room_indexes_usemap[$or['idroom']])) {
                        $room_indexes_usemap[$or['idroom']] = $use_ind_key;
                    } else {
                        $use_ind_key = $room_indexes_usemap[$or['idroom']];
                    }

                    // update room-reservation record by assigning the room index (unit)
                    $dbo->setQuery(
                        $dbo->getQuery(true)
                            ->update($dbo->qn('#__vikbooking_ordersrooms'))
                            ->set($dbo->qn('roomindex') . ' = ' . (int) $room_indexes[$use_ind_key])
                            ->where($dbo->qn('id') . ' = ' . (int) $or['id'])
                    );
                    $dbo->execute();

                    // increase index counter
                    $room_indexes_usemap[$or['idroom']]++;
                }
            }
        }

        // check if some of the rooms booked have shared calendars
        VikBooking::updateSharedCalendars($booking['id'], array_column($roomBooking, 'idroom'), $booking['checkin'], $booking['checkout']);

        // update booking history
        $history_obj = VikBooking::getBookingHistoryInstance($booking['id']);

        $now_user  = JFactory::getUser();
        $caller_id = $now_user->name ? "({$now_user->name})" : '';
        if ($this->getCaller()) {
            $caller_id = '(' . $this->getCaller() . ')';
            if ($this->getHistoryData()) {
                $history_obj->setExtraData($this->getHistoryData());
            }
        }

        // update Booking History
        $history_obj->store('TC', $caller_id);

        // Invoke Channel Manager
        $vcm_autosync = VikBooking::vcmAutoUpdate();
        if ($vcm_autosync > 0) {
            $vcm_obj = VikBooking::getVcmInvoker();
            $vcm_obj->setOids([$booking['id']])->setSyncType('new')->setOriginalStatuses([$original_book_status]);
            $sync_result = $vcm_obj->doSync();
            if ($sync_result === false) {
                // set error message
                $vcm_err = $vcm_obj->getError();
                $this->setError(JText::translate('VBCHANNELMANAGERRESULTKO') . (!empty($vcm_err) ? ' - ' . $vcm_err : ''));
            }
        } elseif (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'synch.vikbooking.php')) {
            // set the necessary action to invoke VCM
            $vcm_sync_url = 'index.php?option=com_vikbooking&task=invoke_vcm&stype=new&cid[]=' . $booking['id'] . '&returl=' . urlencode('index.php?option=com_vikbooking&task=editorder&cid[]=' . $booking['id']);

            $this->setChannelManagerAction(JText::translate('VBCHANNELMANAGERINVOKEASK') . ' <button type="button" class="btn btn-primary" onclick="document.location.href=\'' . $vcm_sync_url . '\';">' . JText::translate('VBCHANNELMANAGERSENDRQ') . '</button>');
        }

        // check if the guest should be notified via email
        if ($options['notify'] ?? null) {
            // send email notification to guest
            VikBooking::sendBookingEmail($booking['id'], ['guest']);

            // SMS skipping the administrator
            VikBooking::sendBookingSMS($booking['id'], ['admin']);
        }

        return true;
    }

    /**
     * Tells if the booking record set can be modified and/or cancelled.
     * 
     * @return  array
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function getAlterationDetails()
    {
        $dbo = JFactory::getDbo();

        $booking = $this->getBooking();
        $roomBooking = $this->getRoomBooking();

        if (!$booking || !$roomBooking) {
            $this->setError('Missing booking or room booking record details.');
            return [];
        }

        // gather room booking tariffs
        $tars = [];
        foreach ($roomBooking as $kor => $or) {
            $num = $kor + 1;
            if (!empty($order['pkg']) || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
                // package or custom cost set from the back-end
                continue;
            }

            // get room tariff details
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->select($dbo->qn('t') . '.*')
                    ->select([
                        $dbo->qn('p.name'),
                        $dbo->qn('p.free_cancellation'),
                        $dbo->qn('p.canc_deadline'),
                        $dbo->qn('p.canc_policy'),
                    ])
                    ->from($dbo->qn('#__vikbooking_dispcost', 't'))
                    ->leftJoin($dbo->qn('#__vikbooking_prices', 'p') . ' ON ' . $dbo->qn('t.idprice') . ' = ' . $dbo->qn('p.id'))
                    ->where($dbo->qn('t.id') . ' = ' . (int) ($or['idtar'] ?? 0))
            );
            $tar = $dbo->loadAssoc();

            if ($tar) {
                // push room booking tariff
                $tars[$num] = $tar;
            }
        }

        // count days to arrival
        $days_to_arrival = 0;
        $now_info = getdate();
        $checkin_info = getdate($booking['checkin'] ?? 0);
        if ($now_info[0] < $checkin_info[0]) {
            while ($now_info[0] < $checkin_info[0]) {
                if (!($now_info['mday'] != $checkin_info['mday'] || $now_info['mon'] != $checkin_info['mon'] || $now_info['year'] != $checkin_info['year'])) {
                    break;
                }
                $days_to_arrival++;
                $now_info = getdate(mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] + 1), $now_info['year']));
            }
        }

        // check if the rate plan(s) are refundable
        $is_refundable = 0;
        $daysadv_refund_arr = [];
        $daysadv_refund = 0;
        $canc_policy = '';
        foreach ($tars as $num => $tar) {
            if (!$tar['free_cancellation']) {
                // if at least one rate plan is non-refundable, the whole reservation cannot be cancelled
                $is_refundable = 0;
                $daysadv_refund_arr = [];
                break;
            }
            $is_refundable = 1;
            $daysadv_refund_arr[] = $tar['canc_deadline'];
        }

        // get the rate plan with the lowest cancellation deadline
        $daysadv_refund = $daysadv_refund_arr ? min($daysadv_refund_arr) : $daysadv_refund;
        if ($daysadv_refund > 0) {
            foreach ($tars as $num => $tar) {
                if ($tar['free_cancellation'] && $tar['canc_deadline'] == $daysadv_refund) {
                    // get the cancellation policy from the first rate plan with free cancellation and same cancellation deadline
                    $canc_policy = $tar['canc_policy'];
                    break;
                }
            }
        }

        // access global settings to determine the alterations available
        $resmodcanc = VikBooking::getReservationModCanc();
        $resmodcanc = !$days_to_arrival ? 0 : $resmodcanc;
        $resmodcancmin = VikBooking::getReservationModCancMin();

        // build alteration deadline date
        $checkin_dt = JFactory::getDate(date('Y-m-d', ($booking['checkin'] ?? 0)));
        $checkin_dt->modify("-{$resmodcancmin} days");
        $alteration_deadline = $checkin_dt->format('Y-m-d');

        return [
            'refundable'          => ($resmodcanc > 1 && $resmodcanc != 2 && $is_refundable > 0 && $daysadv_refund <= $days_to_arrival && $days_to_arrival >= $resmodcancmin),
            'modifiable'          => ($resmodcanc > 1 && $resmodcanc != 3 && $days_to_arrival >= $resmodcancmin),
            'alteration_disabled' => $resmodcanc === 0,
            'request_alteration'  => $resmodcanc === 1,
            'cancellation_policy' => $canc_policy ?: null,
            'alteration_deadline' => $alteration_deadline,
        ];
    }

    /**
     * Tells whether a given booking ID can be moved to new stay dates. Ignores rooms individual stay
     * dates or split stay reservations. Should be called when changing the dates for a whole booking.
     * 
     * @param   int         $booking_id     The booking ID to modify.
     * @param   int|string  $new_checkin    The new check-in date (timestamp or date string with no time).
     * @param   int|string  $new_checkout   The new check-out date (timestamp or date string with no time).
     * 
     * @return  bool                        True if all booking rooms are available on the new dates.
     * 
     * @throws  Exception
     * 
     * @since   1.18.2 (J) - 1.8.2 (WP)
     */
    public function bookingModifiable(int $booking_id, $new_checkin, $new_checkout)
    {
        // attempt to access previous booking registry to reduce queries
        if ($this->prevBookingRegistry && $this->prevBookingRegistry->getID() == $booking_id) {
            $booking_rooms = $this->prevBookingRegistry->getRooms();
        } else {
            // load all booking rooms
            $booking_rooms = VikBooking::loadOrdersRoomsData($booking_id);
        }

        if (!$booking_rooms) {
            throw new Exception('Could not find any rooms booked within the reservation.', 500);
        }

        if (empty($new_checkin) || empty($new_checkout)) {
            throw new Exception('Missing stay dates.', 500);
        }

        // load the booking occupied record IDs, if any
        $busy_ids = VikBooking::loadBookingBusyIds($booking_id);

        // load check-in and check-out times
        list($checkin_h, $checkin_m, $checkout_h, $checkout_m) = $this->loadCheckinOutTimes();

        // get the new stay timestamps
        $new_checkin  = is_numeric($new_checkin) ? date('Y-m-d', $new_checkin) : $new_checkin;
        $new_checkout = is_numeric($new_checkout) ? date('Y-m-d', $new_checkout) : $new_checkout;
        $from_ts = VikBooking::getDateTimestamp($new_checkin, $checkin_h, $checkin_m);
        $to_ts   = VikBooking::getDateTimestamp($new_checkout, $checkout_h, $checkout_m);

        if ($to_ts <= $from_ts) {
            throw new Exception('Invalid stay dates provided.', 500);
        }

        // count room equal units
        $rooms_units_counter = [];
        foreach ($booking_rooms as $booking_room) {
            $rooms_units_counter[$booking_room['idroom']] = ($rooms_counter[$booking_room['idroom']] ?? -1) + 1;
        }

        // check the availability for each room booked
        $rooms_parsed = [];
        foreach ($booking_rooms as $booking_room) {
            if (in_array($booking_room['idroom'], $rooms_parsed)) {
                continue;
            }

            // count effective units to check
            $effective_units = $booking_room['tot_units'] - $rooms_units_counter[$booking_room['idroom']];

            // check if the room is available on the new dates
            if (!VikBooking::roomBookable($booking_room['idroom'], $effective_units, $from_ts, $to_ts, $busy_ids)) {
                // the room is occupied
                return false;
            }

            // push room just checked
            $rooms_parsed[] = $booking_room['idroom'];
        }

        return true;
    }

    /**
     * Attempts to invoke the payment processor assigned to the current booking.
     * 
     * @param   array   $card   Optional credit card details to bind.
     * 
     * @return  object          The payment processor dispatcher instance.
     * 
     * @throws  Exception
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     * @since   1.17.6 (J) - 1.7.6 (WP) added "tn_metadata" details.
     */
    public function getPaymentProcessor(array $card = [])
    {
        $booking   = $this->getProperties();
        $processor = null;
        $payment   = [];

        if (!$booking) {
            throw new Exception('Missing booking details', 500);
        }

        if (!empty($booking['idpayment'])) {
            $payment = VikBooking::getPayment($booking['idpayment']);
        }

        if (!$payment) {
            throw new Exception('Missing payment method details', 500);
        }

        // set payment details internally
        $this->set('_payment_info', $payment);

        if ($card) {
            // inject CC details for the payment processor
            $booking['card'] = $card;
        }

        // get the booking customer record, if any
        $customer = $this->getCustomer();
        if (!$customer) {
            $customer = VikBooking::getCPinInstance()->getCustomerFromBooking($booking['id']);
        }

        // build and inject transaction metadata
        $booking['tn_metadata'] = [
            'booking_id'     => $booking['id'],
            'source'         => (($booking['channel'] ?? '') ?: 'Website'),
            'ota_booking_id' => (($booking['idorderota'] ?? '') ?: ''),
            'guest_name'     => implode(' ', array_filter([($customer['first_name'] ?? ''), ($customer['last_name'] ?? '')])),
            'guest_email'    => $customer['email'] ?? null,
            'guest_phone'    => $customer['phone'] ?? null,
            'guest_country'  => $customer['country'] ?? null,
        ];

        /**
         * Trigger event to allow third-party plugins to manipulate the transaction data.
         * 
         * @since   1.18.5 (J) - 1.8.5 (WP)
         */
        VBOFactory::getPlatform()->getDispatcher()->trigger('onInitPaymentTransaction', [&$booking, &$payment['params'], $customer]);

        if (VBOPlatformDetection::isWordPress()) {
            /**
             * @wponly  The payment gateway is loaded 
             *          through the apposite dispatcher.
             */
            JLoader::import('adapter.payment.dispatcher');
            $processor = JPaymentDispatcher::getInstance('vikbooking', $payment['file'], $booking, $payment['params']);
        } elseif (VBOPlatformDetection::isJoomla()) {
            /**
             * @joomlaonly  The Payment Factory library will invoke the gateway.
             */
            require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'factory.php';
            $processor = VBOPaymentFactory::getPaymentInstance($payment['file'], $booking, $payment['params']);
        }

        if (!$processor) {
            throw new Exception('Could not invoke the payment processor', 500);
        }

        // return the valid payment processor instance
        return $processor;
    }

    /**
     * Gets the reservation's payment method name.
     * 
     * @return  string
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function getPaymentName()
    {
        // access the reserved property
        $payment = (array) $this->get('_payment_info', []);

        if (!$payment) {
            return '';
        }

        return $payment['name'] ?? '';
    }

    /**
     * Attempts to get the most recent transaction data for an off-session capturing.
     * 
     * @param   string  $tn_driver  Optional payment processor driver name.
     * 
     * @return  object[]            Eligible transaction data list or empty array.
     * 
     * @since   1.18.0 (J) - 1.8.0 (WP)
     */
    public function getOffSessionTransactionData(string $tn_driver = '')
    {
        // transaction data validation callback
        $tn_data_callback = function($data) use ($tn_driver) {
            return (is_object($data) && isset($data->driver) && (!$tn_driver || basename($data->driver, '.php') == basename($tn_driver, '.php')) && ($data->future_usage ?? null));
        };

        // get previous transactions (in date ascending order)
        $prev_tn_data = (array) VikBooking::getBookingHistoryInstance($this->get('id', 0))->getEventsWithData(['P0', 'PN'], $tn_data_callback);

        if (!$prev_tn_data) {
            return [];
        }

        // return the eligible transaction data list in reverse order
        return array_reverse(array_values(array_filter(array_map(function($data) {
            if (is_array($data)) {
                // cast to object
                $data = (object) $data;
            }
            return is_object($data) ? $data : null;
        }, $prev_tn_data))));
    }

    /**
     * Attempts to get the credit card value pairs from the current booking.
     * 
     * @return  array   Associative list of CC value-pairs, if any.
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)
     */
    public function getCardValuePairs()
    {
        $booking_info = $this->getProperties();

        if (empty($booking_info['paymentlog']) && empty($booking_info['idorderota'])) {
            // do not proceed when no CC data is available, or cannot be obtained via API
            return [];
        }

        // build complete credit card payload, if available
        $cc_payload_str = '';

        // extract CC data from payment logs by ensuring they're not null
        $booking_info['paymentlog'] = (string) $booking_info['paymentlog'];
        if (stripos($booking_info['paymentlog'], 'card number') !== false && strpos($booking_info['paymentlog'], '*') !== false) {
            // matched a log for an OTA CC
            $cc_payload_str = $booking_info['paymentlog'];
        } elseif (preg_match("/(([\d\*]{4,4}\s*){4,4})|(([\d\*]{4,6}\s*){3,3})/", $booking_info['paymentlog'])) {
            // matched a credit card
            $cc_payload_str = $booking_info['paymentlog'];
        }

        // check if this is an OTA reservation with remotely decoded CC details required
        $remote_cc_data = [];
        if (!empty($booking_info['idorderota']) && !empty($booking_info['channel'])) {
            // channel source
            $channel_source = (string)$booking_info['channel'];
            if (strpos($booking_info['channel'], '_') !== false) {
                $channelparts = explode('_', $booking_info['channel']);
                $channel_source = $channelparts[0];
            }

            // only updated versions of VCM will support remote CC decoding for OTA reservations
            if (class_exists('VCMOtaBooking')) {
                // invoke the OTA Booking helper class from VCM
                $cc_helper = VCMOtaBooking::getInstance([
                    'channel_source' => $channel_source,
                    'ota_id'         => $booking_info['idorderota'],
                    'booking'        => $booking_info,
                ], $anew = true);

                if (method_exists($cc_helper, 'decodeCreditCardDetails')) {
                    $remote_cc_data = $cc_helper->decodeCreditCardDetails();
                    // make sure the response was valid
                    if (!$remote_cc_data || !empty($remote_cc_data['error'])) {
                        // we ignore the error by simply resetting the array
                        $remote_cc_data = [];
                    }
                }
            }
        }

        // check if we have already a full VCC
        if (($remote_cc_data['card_number'] ?? '') && strlen(preg_replace('/[^0-9]/', '', (string) $remote_cc_data['card_number'])) >= 15) {
            // do not merge any local data and return the full VCC details
            return $remote_cc_data;
        }

        // merge remotely decoded CC details with parsed payment log (if any)
        return array_merge($remote_cc_data, $this->parseCreditCardValuePairs($cc_payload_str, $remote_cc_data));
    }

    /**
     * Given a raw string of credit card key-value pairs from payments log,
     * parse the corresponding keys and values into an associative array.
     * In case of conflicting keys with the remotely decoded CC details,
     * attempts to replace the masked numbers with asterisks.
     * 
     * @param   string  $cc_payload         the raw CC details from payment logs.
     * @param   array   $remote_cc_data     associative array of decoded CC data.
     * 
     * @return  array                       associative or empty array.
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)   moved from widget Virtual Terminal.
     */
    protected function parseCreditCardValuePairs($cc_payload, array $remote_cc_data = [])
    {
        $cc_value_pairs = [];

        if (empty($cc_payload)) {
            return $cc_value_pairs;
        }

        $cc_lines = preg_split("/(\r\n|\n|\r)/", $cc_payload);

        foreach ($cc_lines as $cc_line) {
            if (strpos($cc_line, ':') === false) {
                continue;
            }

            $cc_line_parts = explode(':', $cc_line);

            if (empty($cc_line_parts[0]) || !strlen(trim($cc_line_parts[1]))) {
                continue;
            }

            $key   = str_replace(' ', '_', strtolower($cc_line_parts[0]));
            $value = trim($cc_line_parts[1]);

            if (isset($cc_value_pairs[$key])) {
                /**
                 * Do not overwrite existing keys because this probably means that the
                 * credit card was updated by an OTA like Booking.com, hence the payment
                 * logs string in VBO may contain the information of two different cards.
                 * New credit card details are always pre-pended by VCM in the payment logs.
                 */
                continue;
            }

            if (!empty($remote_cc_data[$key]) && is_string($remote_cc_data[$key]) && strpos($value, '*') !== false) {
                // replace masked numbers with remote content
                $value = $this->replaceMaskedNumbers($value, $remote_cc_data[$key]);
            }

            $cc_value_pairs[$key] = $value;
        }

        return $cc_value_pairs;
    }

    /**
     * Given a local and a remote credit card number string with
     * masked symbols, replaces the values in the corresponding
     * positions with the unmasked numbers.
     * 
     * @param   string  $local      current string with masked values.
     * @param   string  $remote     remote string with unmasked values.
     * 
     * @return  string              the local string with unmasked values.
     * 
     * @since   1.16.10 (J) - 1.6.10 (WP)   moved from widget Virtual Terminal.
     */
    protected function replaceMaskedNumbers($local, $remote)
    {
        // split anything but numbers
        $numbers = preg_split("/([^0-9]+)/", trim($remote));

        if ($numbers) {
            // filter empty values
            $numbers = array_filter($numbers);
        }

        if (!$numbers) {
            // unable to proceed
            return $local;
        }

        // split anything but stars (asterisks)
        $stars = preg_split("/([^\*]+)/", trim($local));

        if ($stars) {
            // filter empty values
            $stars = array_filter($stars);
        }

        if (!$stars) {
            // unable to proceed
            return $local;
        }

        // replace masked symbols with numbers at their first occurrence
        foreach ($numbers as $k => $unmasked) {
            if (!isset($stars[$k])) {
                continue;
            }

            $masked_pos = strpos($local, $stars[$k]);

            if ($masked_pos === false) {
                continue;
            }

            $local = substr_replace($local, $unmasked, $masked_pos, strlen($stars[$k]));
        }

        // return the string with possibly unmasked values
        return $local;
    }

    /**
     * Tells whether the booking can be created. By default this
     * is only allowed from the administrator section of the site.
     * 
     * @return  bool
     */
    protected function canCreate()
    {
        return $this->get('_isAdministrator') || JFactory::getApplication()->isClient('administrator');
    }

    /**
     * Gets and sets the tariff ID if a rate plan was set.
     * 
     * @param   ?array  $roomData   Optional room data to evaluate.
     * 
     * @return  int     The tariff ID found, or 0.
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP) added argument $roomData for multi-room booking context.
     */
    protected function loadTariffID(?array $roomData = null)
    {
        $dbo = JFactory::getDbo();

        $id_tariff = 0;

        // access current room data
        $room = $roomData ?: $this->getRoom();

        // current nights of stay
        $daysdiff = (int) (($room['nights'] ?? 0) ?: $this->get('nights', 1));

        // make sure we have a rate plan ID with a cost set
        if (!empty($room['id']) && !empty($room['id_price']) && !empty($room['room_cost']) && !boolval($this->get('set_closed', 0)) && !$this->get('split_stay', [])) {
            // load tariff for given room, rate plan and nights of stay
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->select($dbo->qn('id'))
                    ->from($dbo->qn('#__vikbooking_dispcost'))
                    ->where($dbo->qn('idroom') . ' = ' . (int) $room['id'])
                    ->where($dbo->qn('days') . ' = ' . $daysdiff)
                    ->where($dbo->qn('idprice') . ' = ' . (int) $room['id_price'])
            );

            $id_tariff = (int) $dbo->loadResult();
        }

        // set current tariff ID
        $this->set('id_tariff', $id_tariff);

        // return the current tariff ID
        return $id_tariff;
    }

    /**
     * Applies the turnover time to the checkout timestamp and sets its value.
     * 
     * @return  int     the turnover seconds applied.
     */
    protected function applyTurnover()
    {
        $turnover_secs = 0;
        $checkout = $this->get('checkout', 0);

        if ($checkout) {
            // turnover time
            $turnover_secs = VikBooking::getHoursRoomAvail() * 3600;

            $this->set('checkout_real', ($checkout + $turnover_secs));
        }

        $this->set('turnover_secs', $turnover_secs);

        return $turnover_secs;
    }

    /**
     * Returns an associative list of all rooms data.
     * 
     * @return  array
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP)
     */
    protected function loadAllRoomsData()
    {
        if (!$this->allRooms) {
            // load once and cache internally
            $this->allRooms = VikBooking::getAvailabilityInstance(true)->loadRooms();
        }

        return $this->allRooms;
    }

    /**
     * Returns the details of a specific room ID.
     * 
     * @param   ?int    $rid    Optional room ID to fetch.
     * 
     * @return  array   The record found or empty array.
     */
    protected function getRoomDetails(?int $rid = null)
    {
        $all_rooms = $this->loadAllRoomsData();

        if (!$rid) {
            $inj_room = $this->getRoom();
            $rid = $inj_room['id'] ?? 0;
        }

        if ($rid && isset($all_rooms[$rid])) {
            return $all_rooms[$rid];
        }

        return [];
    }

    /**
     * Gets the list of rooms involved in the reservation in case of
     * closures or if multiple rooms were set for booking.
     * 
     * @return  array   The list of rooms involved.
     */
    protected function getRoomsPool()
    {
        // preload all rooms
        $av_helper = VikBooking::getAvailabilityInstance(true);
        $all_rooms = $this->loadAllRoomsData();

        // access the first room set for booking
        $room = $this->getRoom();
        if (empty($room['id'])) {
            return [];
        }
        $room = $this->getRoomDetails($room['id']);

        // gather values
        $set_close_others = (array) $this->get('close_others', []);
        $split_stay_data  = $this->get('split_stay', []);
        $set_closed       = (int) $this->get('set_closed');
        $turnover_secs    = $this->get('turnover_secs', 0);
        $hcheckin         = $this->get('checkin_h', 12);
        $mcheckin         = $this->get('checkin_m', 0);
        $hcheckout        = $this->get('checkout_h', 10);
        $mcheckout        = $this->get('checkout_m', 0);

        // build containers
        $rooms_pool  = [];
        $closeothers = [];

        if ($set_close_others && $set_closed) {
            // prepend current room for closing
            array_unshift($set_close_others, $room['id']);
        }
        $set_close_others = array_unique($set_close_others);

        foreach ($set_close_others as $closeid) {
            if (empty($closeid)) {
                continue;
            }
            if ((int) $closeid === -1) {
                // close all rooms
                $closeothers = [];
                foreach ($all_rooms as $cr) {
                    array_push($closeothers, $cr);
                }
                break;
            }
            foreach ($all_rooms as $cr) {
                if ((int) $cr['id'] == (int) $closeid) {
                    // push the main room or one of the other rooms requested for closure
                    array_push($closeothers, $cr);
                    break;
                }
            }
        }

        if (!$closeothers || !$set_closed) {
            // check if we are in a multi-room booking context
            $multi_rooms = $this->getRooms();
            if (count($multi_rooms) > 1) {
                // multiple rooms set for booking
                $rooms_pool = array_values(array_filter(array_map(function($roomData) {
                    return $this->getRoomDetails($roomData['id'] ?? -1);
                }, $multi_rooms)));
            } else {
                // single room involved
                $rooms_pool = [$room];
            }
        } else {
            // set all rooms involved
            $rooms_pool = $closeothers;
        }

        // check split stay rooms booking
        if (!empty($split_stay_data)) {
            // reset pool and set it with the split stay rooms
            $rooms_pool = [];
            foreach ($split_stay_data as $sps_k => $split_stay) {
                if (!isset($all_rooms[$split_stay['idroom']])) {
                    continue;
                }
                // calculate and set the exact check-in and check-out timestamps for this split-room
                $split_stay['checkin_ts']  = VikBooking::getDateTimestamp($split_stay['checkin'], $hcheckin, $mcheckin);
                $split_stay['checkout_ts'] = VikBooking::getDateTimestamp($split_stay['checkout'], $hcheckout, $mcheckout);
                $split_stay['realback_ts'] = $turnover_secs + $split_stay['checkout_ts'];
                $split_stay['nights']      = $av_helper->countNightsOfStay($split_stay['checkin_ts'], $split_stay['checkout_ts']);
                $split_stay_data[$sps_k]   = $split_stay;
                // push room data to pool after storing additional information
                $room_data = $all_rooms[$split_stay['idroom']];
                $room_data['checkin_ts']  = $split_stay['checkin_ts'];
                $room_data['checkout_ts'] = $split_stay['checkout_ts'];
                $rooms_pool[] = $room_data;
            }
            if (!$rooms_pool) {
                $this->setError('No valid rooms for the split stay booking');
                return [];
            }
            // update split stay data manipulated
            $this->set('split_stay', $split_stay_data);
        }

        return $rooms_pool;
    }

    /**
     * Checks if the rooms are available on the requested dates.
     * 
     * @param   array   $rooms_pool     Pool of rooms involved in the reservation.
     * 
     * @return  bool
     * 
     * @since   1.18.8 (J) - 1.8.8 (WP) added argument $rooms_pool.
     */
    protected function checkRoomsAvailability(array $rooms_pool)
    {
        if (empty($rooms_pool[0]['id'])) {
            // invalid argument
            return false;
        }

        // gather variables
        $split_stay_data  = $this->get('split_stay', []);
        $set_closed       = $this->get('set_closed', 0);
        $num_rooms        = $this->isMultiRoom() ? count($rooms_pool) : $this->get('num_rooms', 1);
        $default_checkin  = $this->get('checkin', 0);
        $default_checkout = $this->get('checkout', 0);
        $check_locked     = (bool) $this->get('check_locked', 0);

        // determine availability
        $rooms_available = true;

        // check if we are dealing with split-stay data or regular booking values
        if (empty($split_stay_data)) {
            // make sure the rooms are available
            if ($this->isMultiRoom() && !$set_closed) {
                // multi-room booking context with support for stay dates at room-level
                $roomBookings = $this->getRooms();
                $roomStayData = [];
                $roomUnitsUse = [];
                $roomNamesMap = [];
                foreach ($rooms_pool as $index => $room) {
                    $roomUnitsUse[$room['id']] = ($roomUnitsUse[$room['id']] ?? 0) + 1;
                    $roomNamesMap[$room['id']] = $room['name'] ?? $room['id'];
                    $roomStayData[] = [
                        'idroom'   => $room['id'],
                        'units'    => ($room['units'] ?? 1),
                        'checkin'  => $roomBookings[$index]['checkin'] ?? $default_checkin,
                        'checkout' => $roomBookings[$index]['checkout'] ?? $default_checkout,
                    ];
                }
                foreach ($roomStayData as $roomStay) {
                    // check the remaining availability for the number of room units booked
                    $unitsBooked = $roomUnitsUse[$roomStay['idroom']];
                    $check_units = $roomStay['units'] - $unitsBooked + 1;
                    if ($check_locked) {
                        // check if the room is available and not temporarily locked
                        $rooms_available = VikBooking::roomNotLocked($roomStay['idroom'], $check_units, $roomStay['checkin'], $roomStay['checkout'], true);
                    } else {
                        // check if the room is available
                        $rooms_available = VikBooking::roomBookable($roomStay['idroom'], $check_units, $roomStay['checkin'], $roomStay['checkout']);
                    }
                    if (!$rooms_available) {
                        // set an error and abort
                        $this->setError(sprintf(
                            'Room "%s" is not available from %s to %s. Looking for %d free unit(s) over a total of %d.',
                            $roomNamesMap[$roomStay['idroom']],
                            date('Y-m-d H:i', $roomStay['checkin']),
                            date('Y-m-d H:i', $roomStay['checkout']),
                            $unitsBooked,
                            $roomStay['units']
                        ));
                        break;
                    }
                }
            } else {
                // single-room booking context
                $check_units = $rooms_pool[0]['units'] ?? 1;
                if ($num_rooms > 1 && $num_rooms <= $check_units && !$set_closed) {
                    // only when non closing the room we check the availability for the units requested for booking
                    $check_units = $check_units - $num_rooms + 1;
                }
                if ($check_locked) {
                    // check if the room is available and not temporarily locked
                    $rooms_available = VikBooking::roomNotLocked($rooms_pool[0]['id'], $check_units, $default_checkin, $default_checkout, true);
                } else {
                    // check if the room is available
                    $rooms_available = VikBooking::roomBookable($rooms_pool[0]['id'], $check_units, $default_checkin, $default_checkout);
                }
            }
        } else {
            $all_rooms = $this->loadAllRoomsData();
            // make sure the rooms for the split stay are available
            foreach ($split_stay_data as $split_stay) {
                if (!isset($all_rooms[$split_stay['idroom']])) {
                    $rooms_available = false;
                    break;
                }
                $rooms_available = $rooms_available && VikBooking::roomBookable($split_stay['idroom'], $all_rooms[$split_stay['idroom']]['units'], $split_stay['checkin_ts'], $split_stay['checkout_ts']);
            }
        }

        return $rooms_available;
    }

    /**
     * In case the reservation is forced or is a closure, we detect the
     * forced reason to eventually attach it to the booking history.
     * 
     * @param   bool    $rooms_available     Whether the rooms are available.
     * 
     * @return  void
     */
    protected function detectForcedReason($rooms_available = true)
    {
        $split_stay_data = $this->get('split_stay', []);
        $force_booking = $this->get('force_booking', 0);
        $set_closed = $this->get('set_closed', 0);

        $forced_reason = $this->get('forced_reason', '');

        if (empty($split_stay_data)) {
            // eventually build string for the description of the history event
            if (($force_booking || $set_closed) && !$rooms_available) {
                $forced_reason = JText::translate('VBO_FORCED_BOOKDATES');
            }
        } else {
            $all_rooms = $this->loadAllRoomsData();
            // set "split stay" as the description of the history event
            $forced_reason = JText::translate('VBO_SPLIT_STAY') . "\n";
            foreach ($split_stay_data as $sps_k => $split_stay) {
                // describe the split stay for each room
                if (!isset($all_rooms[$split_stay['idroom']])) {
                    continue;
                }
                $room_stay_nights = $split_stay['nights'];
                $forced_reason .= $all_rooms[$split_stay['idroom']]['name'] . ': ' . $room_stay_nights . ' ' . ($room_stay_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')) . ', ';
                $forced_reason .= $split_stay['checkin'] . ' - ' . $split_stay['checkout'] . "\n";
            }
            $forced_reason = rtrim($forced_reason, "\n");
        }

        $this->set('forced_reason', $forced_reason);
    }

    /**
     * Stores the customer information to a new or existing record.
     * In case of success, the customer ID property is updated.
     * The customer shall be stored before the reservation records.
     * 
     * @return  bool
     */
    protected function storeCustomer()
    {
        $dbo = JFactory::getDbo();

        $inj_customer = $this->getCustomer();
        $first_name   = !empty($inj_customer['first_name']) ? $inj_customer['first_name'] : '';
        $last_name    = !empty($inj_customer['last_name']) ? $inj_customer['last_name'] : '';
        $custdata     = !empty($inj_customer['data']) ? $inj_customer['data'] : '';
        $email        = !empty($inj_customer['email']) ? $inj_customer['email'] : '';
        $country      = !empty($inj_customer['country']) ? $inj_customer['country'] : '';
        $phone        = !empty($inj_customer['phone']) ? $inj_customer['phone'] : '';
        $gender       = !empty($inj_customer['gender']) ? $inj_customer['gender'] : '';

        // custom fields
        $q = "SELECT * FROM `#__vikbooking_custfields` ORDER BY `ordering` ASC;";
        $dbo->setQuery($q);
        $all_cfields = $dbo->loadAssocList();

        $customer_cfields = [];
        $customer_extrainfo = [];
        $custdata_parts = explode("\n", $custdata);
        foreach ($custdata_parts as $cdataline) {
            if (!strlen(trim($cdataline))) {
                continue;
            }
            $cdata_parts = explode(':', $cdataline);
            if (count($cdata_parts) < 2 || !strlen(trim($cdata_parts[0])) || !strlen(trim($cdata_parts[1]))) {
                continue;
            }
            foreach ($all_cfields as $cf) {
                $needle = JText::translate($cf['name']);
                if (!empty($needle) && strpos($cdata_parts[0], $needle) !== false && !array_key_exists($cf['id'], $customer_cfields) && $cf['type'] != 'country') {
                    $user_input_val = trim($cdata_parts[1]);
                    $customer_cfields[$cf['id']] = $user_input_val;
                    if (!empty($cf['flag'])) {
                        $customer_extrainfo[$cf['flag']] = $user_input_val;
                    } elseif ($cf['type'] == 'state') {
                        $customer_extrainfo['state'] = $user_input_val;
                    }
                    break;
                }
            }
        }

        if (!empty($gender) && in_array(strtoupper((string) $gender), ['M', 'F'])) {
            // inject the customer gender value
            $customer_extrainfo['gender'] = strtoupper($gender);
        }

        $cpin = VikBooking::getCPinInstance();
        $cpin->is_admin = true;
        $cpin->setCustomerExtraInfo($customer_extrainfo);
        $cpin->saveCustomerDetails($first_name, $last_name, $email, $phone, $country, $customer_cfields);

        $customer_id = $cpin->getNewCustomerId();
        if (!$customer_id) {
            return false;
        }

        $inj_customer['id'] = $customer_id;
        $this->setCustomer($inj_customer);

        return true;
    }

    /**
     * Returns the calculated total booking amount and total taxes.
     * Sets the necessary properties with the calculated amounts.
     * 
     * @return  array   list of booking total amount, taxes and fees.
     */
    protected function calculateTotal()
    {
        $dbo = JFactory::getDbo();

        // the values to calculate
        $set_total    = 0;
        $set_taxes    = 0;
        $set_city_tax = 0;
        $set_fees     = 0;

        // access all rooms booking data
        $roomsData = $this->getRooms();

        if (!$roomsData) {
            return [$set_total, $set_taxes];
        }

        // get booking values
        $set_closed      = $this->get('set_closed', 0);
        $daysdiff        = (int) $this->get('nights', 1);
        $num_rooms       = (int) $this->get('num_rooms', 1);
        $split_stay_data = $this->get('split_stay', []);

        // iterate all booking rooms data
        foreach ($roomsData as $index => $roomData) {
            // gather booking room values
            $totalpnight = ($roomData['total_or_pnight'] ?? '') ?: 'total';
            $cust_cost   = (float) ($roomData['cust_cost'] ?? 0);
            $room_cost   = (float) ($roomData['room_cost'] ?? 0);
            $id_price    = (int) ($roomData['id_price'] ?? 0);
            $id_tax      = (int) ($roomData['id_tax'] ?? 0);
            $guess_tax   = $roomData['guess_tax'] ?? false;

            // access room details
            $roomDetails = $this->getRoomDetails($roomData['id'] ?? 0);

            // check for stay dates at room level
            $roomNightsStay = ($roomData['nights'] ?? 0) ?: $daysdiff;

            // calculate room totals
            $roomTotalValue   = 0;
            $roomTotalTax     = 0;
            $roomTotalCityTax = 0;
            $roomTotalFees    = 0;

            if ($cust_cost > 0 && !$set_closed) {
                // custom cost can be per night
                if ($totalpnight == 'pnight') {
                    $cust_cost = $cust_cost * $roomNightsStay;
                }
                $roomTotalValue = $cust_cost;

                if (!$id_tax && $guess_tax) {
                    // try to guess the tax rate
                    $dbo->setQuery(
                        $dbo->getQuery(true)
                            ->select($dbo->qn('id'))
                            ->from($dbo->qn('#__vikbooking_iva'))
                            ->order($dbo->qn('aliq') . ' ASC'),
                    0, 1);
                    $guessed_id_tax = $dbo->loadResult();
                    if ($guessed_id_tax) {
                        // update the id_tax values
                        $id_tax = $guessed_id_tax;
                        $roomData['id_tax'] = $guessed_id_tax;
                        $this->setRoom($roomData, $index);
                    }
                }

                // apply taxes, if necessary
                if ($id_tax) {
                    $taxdata = VBOTaxonomySummary::getTaxRateRecord((int) $id_tax);
                    if (!empty($taxdata['aliq'])) {
                        $aliq = $taxdata['aliq'];
                        if (!VikBooking::ivaInclusa()) {
                            // add tax to the total amount
                            $subt = 100 + (float)$aliq;
                            $roomTotalValue = ($roomTotalValue * $subt / 100);
                            /**
                             * Tax Cap implementation for prices tax excluded (most common).
                             * 
                             * @since   1.12 (J) - 1.2 (WP)
                             */
                            if ($taxdata['taxcap'] > 0 && ($roomTotalValue - $cust_cost) > $taxdata['taxcap']) {
                                $roomTotalValue = ($cust_cost + $taxdata['taxcap']);
                            }
                            // calculate tax
                            $roomTotalTax = $roomTotalValue - $cust_cost;
                        } else {
                            // calculate tax
                            $cost_minus_tax = VikBooking::sayPackageMinusIva($cust_cost, $id_tax);
                            $roomTotalTax += ($cust_cost - $cost_minus_tax);
                        }
                    }
                }
            } elseif (!empty($id_price) && $room_cost > 0 && !$set_closed && empty($split_stay_data)) {
                // one website rate plan was selected, so we calculate total and taxes
                $roomTotalValue = $room_cost;

                // find tax rate assigned to this rate plan
                $taxdata = VBOTaxonomySummary::getTaxRecordFromRatePlan($id_price);
                if (!empty($taxdata['aliq'])) {
                    $aliq = $taxdata['aliq'];
                    if (!VikBooking::ivaInclusa()) {
                        // add tax to the total amount
                        $subt = 100 + (float)$aliq;
                        $roomTotalValue = ($roomTotalValue * $subt / 100);
                        /**
                         * Tax Cap implementation for prices tax excluded (most common).
                         * 
                         * @since   1.12 (J) - 1.2 (WP)
                         */
                        if ($taxdata['taxcap'] > 0 && ($roomTotalValue - $room_cost) > $taxdata['taxcap']) {
                            $roomTotalValue = ($room_cost + $taxdata['taxcap']);
                        }
                        // calculate tax
                        $roomTotalTax = $roomTotalValue - $room_cost;
                    } else {
                        // calculate tax
                        $cost_minus_tax = VikBooking::sayPackageMinusIva($room_cost, $taxdata['idiva']);
                        $roomTotalTax += ($room_cost - $cost_minus_tax);
                    }
                }

                // total and taxes should be multiplied by the number of rooms booked when using a website rate plan
                if ($set_closed) {
                    $roomTotalValue *= $roomDetails['units'];
                    $roomTotalTax   *= $roomDetails['units'];
                } elseif (!$this->isMultiRoom() && $num_rooms > 1 && $num_rooms <= $roomDetails['units']) {
                    $roomTotalValue *= $num_rooms;
                    $roomTotalTax   *= $num_rooms;
                }
            }

            // check for options/extras at room level
            if ($roomData['options'] ?? null) {
                // load all the eligible options for this room party
                $eligibleOptions = VBORoomHelper::getInstance()->getEligibleOptions($roomData['id'], [
                    'checkin'  => date('Y-m-d', ($roomData['checkin'] ?? null) ?: $this->get('checkin', 0)),
                    'checkout' => date('Y-m-d', ($roomData['checkout'] ?? null) ?: $this->get('checkout', 0)),
                    'adults'   => $roomData['adults'] ?? $this->get('adults', 1),
                    'children' => $roomData['children'] ?? $this->get('children', 0),
                    'rate_id'  => $roomData['id_price'] ?? null,
                ]);
                // obtain a list of eligible option IDs
                $eligibleOptIds = array_column($eligibleOptions, 'id');
                // get all eligible room options/extras that were set for booking
                $roomOptData = array_values(array_filter((array) $roomData['options'], function($roomOpt) use ($eligibleOptIds) {
                    if (!is_array($roomOpt) || empty($roomOpt['id'])) {
                        return false;
                    }
                    return in_array($roomOpt['id'], $eligibleOptIds) && (!empty($roomOpt['quantity']) || !empty($roomOpt['age_intervals']));
                }));
                if ($roomOptData) {
                    // turn all eligible options into an associative list
                    $eligibleOptions = array_combine(array_column($eligibleOptions, 'id'), array_values($eligibleOptions));
                    // scan all room options to increase totals
                    foreach ($roomOptData as $optData) {
                        // obtain room option computed data
                        $computedData = $eligibleOptions[$optData['id']] ?? [];
                        // calculate option cost
                        $roomOptCost = 0;
                        if (!empty($optData['age_intervals'])) {
                            // children age interval option
                            foreach ((array) $optData['age_intervals'] as $childIndex => $ageIntervalIndex) {
                                // child number is 0-based
                                $childNumber = $childIndex + 1;
                                // age interval index is 1-based
                                $ageIntervalNumber = $ageIntervalIndex - 1;
                                // check if a room cost is known
                                $roomOptCost += (float) ($computedData['_computed_children_costs'][$childNumber][$ageIntervalNumber] ?? 0);
                            }
                        } else {
                            // regular option
                            $roomOptCost = floatval($computedData['_computed_cost'] ?? 0) * floatval($optData['quantity'] ?? 1);
                        }
                        // calculate gross and net costs
                        $roomOptGross = VikBooking::sayOptionalsPlusIva($roomOptCost, $computedData['idiva'] ?? 0);
                        $roomOptNet   = VikBooking::sayOptionalsMinusIva($roomOptCost, $computedData['idiva'] ?? 0);
                        // increase total value
                        $roomTotalValue += $roomOptGross;
                        // increase total tax
                        $roomTotalTax += ($roomOptGross - $roomOptNet);
                        if ($computedData['is_citytax'] ?? null) {
                            // increase total city tax
                            $roomTotalCityTax += $roomOptNet;
                        } elseif ($computedData['is_fee'] ?? null) {
                            // increase total fees
                            $roomTotalFees += $roomOptNet;
                        }
                    }
                }
            }

            // check for custom extra services at room level
            if ($roomData['extras'] ?? null) {
                $roomExtraCosts = array_values(array_filter((array) ($roomData['extras'] ?? []), function($extraCost) {
                    return is_array($extraCost) && !empty($extraCost['name']);
                }));
                if ($roomExtraCosts) {
                    // normalize values
                    $roomExtraCosts = array_map(function($extraCost) {
                        return [
                            'name'  => $extraCost['name'],
                            'cost'  => (float) ($extraCost['cost'] ?? 0),
                            'idtax' => (int) ($extraCost['idtax'] ?? $extraCost['vat'] ?? 0),
                        ];
                    }, $roomExtraCosts);
                    // scan all room extra services to increase totals
                    foreach ($roomExtraCosts as $roomExtraCost) {
                        $ecplustax = !empty($roomExtraCost['idtax']) ? VikBooking::sayOptionalsPlusIva($roomExtraCost['cost'], $roomExtraCost['idtax']) : $roomExtraCost['cost'];
                        $ecminustax = !empty($roomExtraCost['idtax']) ? VikBooking::sayOptionalsMinusIva($roomExtraCost['cost'], $roomExtraCost['idtax']) : $roomExtraCost['cost'];
                        // increase total value
                        $roomTotalValue += $ecplustax;
                        // increase total tax
                        $roomTotalTax += ($ecplustax - $ecminustax);
                    }
                }
            }

            // increase global totals
            $set_total    += $roomTotalValue;
            $set_taxes    += $roomTotalTax;
            $set_city_tax += $roomTotalCityTax;
            $set_fees     += $roomTotalFees;
        }

        // set values
        $this->set('_total', $set_total);
        $this->set('_total_tax', $set_taxes);
        $this->set('_total_city_tax', $set_city_tax);
        $this->set('_total_fees', $set_fees);

        // return the list of calculated values
        return [
            $set_total,
            $set_taxes,
            $set_city_tax,
            $set_fees,
        ];
    }

    /**
     * Stores (or updates) the booking and room-booking records.
     * If no errors, the newly generated or updated booking id is set.
     * 
     * @param   array   $rooms_pool     List of rooms involved.
     * @param   ?int    $bookingId      The booking ID being updated, if any.
     * 
     * @return  bool
     * 
     * @since   1.18.11 (J) - 1.8.11 (WP) added $bookingId argument to support updates.
     */
    protected function storeReservationRecords(array $rooms_pool, ?int $bookingId = null)
    {
        if ($bookingId && !$this->prevBookingRegistry) {
            $this->setError('Missing previous booking registry for update.');
            return false;
        }

        $dbo = JFactory::getDbo();

        // access properties
        $set_closed   = $this->get('set_closed', 0);
        $units_closed = $this->get('units_closed', 0);
        $daysdiff     = (int) $this->get('nights', 1);
        $num_rooms    = (int) $this->get('num_rooms', 1);
        $adults       = (int) $this->get('adults', 1);
        $children     = (int) $this->get('children', 0);
        $children_age = (array) $this->get('children_age', []);
        $status       = $this->get('status', 'confirmed');

        $split_stay_data = $this->get('split_stay', []);
        $room = $this->getRoomDetails();
        if (!$room || !$rooms_pool) {
            return false;
        }

        // access all rooms data
        $roomsData = $this->getRooms();

        // get current Joomla/WordPress User ID
        $now_user = JFactory::getUser();
        $store_ujid = property_exists($now_user, 'id') ? (int)$now_user->id : 0;

        // forced booking reason, status validation and additional data
        $forced_reason  = $this->get('forced_reason', '');
        $valid_statuses = ['confirmed', 'standby'];
        $status         = in_array($status, $valid_statuses) ? $status : 'confirmed';
        $paymentmeth    = $this->get('id_payment', '');
        $auto_paymeth   = (bool) $this->get('auto_payment_method', false);
        $set_total      = (float) $this->get('_total', 0);
        $set_taxes      = (float) $this->get('_total_tax', 0);
        $set_city_tax   = (float) $this->get('_total_city_tax', 0);
        $set_fees       = (float) $this->get('_total_fees', 0);

        if ($bookingId) {
            // updating a booking should not change the creation date
            $now_ts = $this->prevBookingRegistry->getProperty('ts', time());
        } else {
            // booking creation date
            $now_ts = time();
            if ($created_on = $this->get('created_on')) {
                if (is_int($created_on)) {
                    // timestamp expected
                    $now_ts = $created_on;
                } elseif (preg_match('/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}/', (string) $created_on)) {
                    // date in military format expected, with or without the time
                    $now_ts = strtotime($created_on);
                }
            }
        }

        // stay dates
        $checkin_ts  = $this->get('checkin');
        $checkout_ts = $this->get('checkout');
        $realback_ts = $this->get('checkout_real', $checkout_ts);

        // customer
        $cpin           = VikBooking::getCPinInstance();
        $inj_customer   = $this->getCustomer();
        $customer_id    = ($inj_customer['id'] ?? null) ?: 0;
        $customer_pin   = ($inj_customer['pin'] ?? null) ?: '';
        $t_first_name   = ($inj_customer['first_name'] ?? null) ?: '';
        $t_last_name    = ($inj_customer['last_name'] ?? null) ?: '';
        $customer_data  = ($inj_customer['data'] ?? null) ?: '';
        $customer_email = ($inj_customer['email'] ?? null) ?: '';
        $country_code   = ($inj_customer['country'] ?? null) ?: '';
        $phone_number   = ($inj_customer['phone'] ?? null) ?: '';

        if ($set_closed) {
            // get the possibly custom "customer notes"
            $customer_notes = $customer_data;
            // overwrite "customer notes" to "Room Closed"
            $customer_data = JText::translate('VBDBTEXTROOMCLOSED');
            if ($customer_notes != $customer_data) {
                // append the custom "custom notes" to the administrator notes instead
                $custom_admin_notes = trim($this->get('admin_notes', '') . "\n" . $customer_notes);
                $this->set('admin_notes', $custom_admin_notes);
            }
        }

        // check for default customer raw data
        if (!$customer_data && $t_first_name) {
            // build a default raw data string
            $customer_data = "Name: {$t_first_name}\n";
            if ($t_last_name) {
                $customer_data .= "Last Name: {$t_last_name}\n";
            }
            if ($customer_email) {
                $customer_data .= "eMail: {$customer_email}\n";
            }
            if ($country_code) {
                $customer_data .= "Country: {$country_code}\n";
            }
            if ($phone_number) {
                $customer_data .= "Phone: {$phone_number}\n";
            }
            $customer_data = rtrim($customer_data, "\n");
        }

        // ensure the country code is in the right format
        if (!empty($country_code)) {
            $country_code = $cpin->get3CharCountry($country_code);
        }

        // ensure we have a valid country ISO code
        if (strlen((string) $country_code) > 3) {
            // this looks like an unknown country name
            $country_code = '';
        }

        // generate booking SID, unless we are updating
        $sid = !$bookingId ? VikBooking::getSecretLink() : null;

        // assign room specific unit
        $set_room_indexes = !$set_closed ? VikBooking::autoRoomUnit() : false;
        $num_rooms = $num_rooms > 0 ? $num_rooms : 1;

        // occupancy and loop limits
        $forend = 1;
        $or_forend = 1;
        $adults_map = [];
        $children_map = [];
        if ($set_closed && empty($split_stay_data)) {
            $forend = $room['units'];
        } elseif ($num_rooms > 1 && $num_rooms <= $room['units'] && empty($split_stay_data)) {
            $forend = $num_rooms;
            $or_forend = $num_rooms;
            // assign adults/children proportionally
            if (($adults + $children) < $num_rooms) {
                // the number of guests does not make much sense but we build the maps anyway
                for ($r = 1; $r <= $or_forend; $r++) {
                    $adults_map[$r] = $adults;
                    $children_map[$r] = $children;
                }
            } else {
                $adults_per_room = floor(($adults / $num_rooms));
                $adults_left = ($adults % $num_rooms);
                $children_per_room = floor(($children / $num_rooms));
                $children_left = ($children % $num_rooms);
                for ($r = 1; $r <= $or_forend; $r++) {
                    $adults_map[$r] = $adults_per_room;
                    $children_map[$r] = $children_per_room;
                    if ($r == $or_forend) {
                        $adults_map[$r] += $adults_left;
                        $children_map[$r] += $children_left;
                    }
                }
            }
        }

        if ($this->isMultiRoom()) {
            // adjust values
            $forend = 1;
            $or_forend = 1;
        }

        // count total rooms booked
        $totalrooms = (($set_closed && $status == 'confirmed') || $this->isMultiRoom() ? count($rooms_pool) : ($num_rooms > 1 && $num_rooms <= $room['units'] ? $num_rooms : 1));
        $totalrooms = !empty($split_stay_data) ? count($split_stay_data) : $totalrooms;

        // attempt to get the default payment method
        if (!$paymentmeth && ($auto_paymeth || $status == 'standby')) {
            // get the default payment method, if any
            $paymentmeth = $this->getDefaultPaymentMethod($auto_paymeth);
        }

        if ($bookingId) {
            // when updating, delete all room booking records involved
            // to allow the re-generation of the updated records
            $this->delete(['booking_id' => $bookingId], $reCreating = true);
        }

        // prepare booking record
        $booking = new stdClass;

        if ($bookingId) {
            // inject current booking ID for update
            $booking->id = $bookingId;
        }

        // set booking record properties
        $booking->custdata   = $customer_data;
        $booking->ts         = $now_ts;
        $booking->status     = $status;
        $booking->days       = $daysdiff;
        $booking->checkin    = $checkin_ts;
        $booking->checkout   = $checkout_ts;
        $booking->custmail   = $customer_email;
        $booking->sid        = $sid;
        $booking->idpayment  = $paymentmeth;
        $booking->ujid       = (int)$store_ujid;
        $booking->roomsnum   = $totalrooms;
        $booking->total      = $set_total ?: null;
        if ($this->get('admin_notes')) {
            $booking->adminnotes = $this->get('admin_notes', '');
        }
        $booking->lang           = VikBooking::guessBookingLangFromCountry($country_code);
        $booking->country        = $country_code;
        $booking->tot_taxes      = $set_taxes ?: null;
        $booking->tot_city_taxes = $set_city_tax ?: ($this->get('tot_city_taxes') ? ((float) $this->get('tot_city_taxes')) : null);
        $booking->tot_fees       = $set_fees ?: ($this->get('tot_fees') ? ((float) $this->get('tot_fees')) : null);
        $booking->tot_damage_dep = $this->get('tot_damage_dep') ? ((float) $this->get('tot_damage_dep')) : null;
        $booking->phone          = $phone_number;
        $booking->cmms           = $this->get('cmms') ? ((float) $this->get('cmms')) : null;
        $booking->closure        = ($status == 'standby' ? 0 : ($set_closed || $units_closed ? 1 : 0));
        $booking->payable        = $this->get('payable') ? ((float) $this->get('payable')) : null;
        $booking->refund         = $this->get('refund') ? ((float) $this->get('refund')) : null;
        $booking->type           = $this->get('type') ? ((string) $this->get('type')) : null;
        $booking->ota_type_data  = $this->get('ota_type_data') && !is_scalar($this->get('ota_type_data')) ? json_encode($this->get('ota_type_data')) : null;
        $booking->split_stay     = !empty($split_stay_data) ? 1 : 0;
        $booking->canc_fee       = $this->get('canc_fee') ? ((float) $this->get('canc_fee')) : null;
        $booking->idquote        = $this->get('idquote') ? ((int) $this->get('idquote')) : null;

        // process reservation
        if ($status == 'confirmed') {
            // occupy room records first, when status is confirmed
            $insertedbusy = [];
            if (empty($split_stay_data)) {
                // only when closing other rooms we have an array containing multiple rooms info
                foreach ($rooms_pool as $index => $nowroom) {
                    // determine the number of records to occupy
                    $nowforend = $set_closed ? $nowroom['units'] : $forend;
                    // determine the timestamps to occupy by supporting room-level stay dates
                    $room_checkin_ts = $roomsData[$index]['checkin'] ?? $checkin_ts;
                    $room_checkout_ts = $roomsData[$index]['checkout'] ?? $checkout_ts;
                    $room_realback_ts = $roomsData[$index]['realback'] ?? $roomsData[$index]['checkout'] ?? $realback_ts;
                    // occupy records
                    for ($b = 1; $b <= $nowforend; $b++) {
                        $busy_record = new stdClass;
                        $busy_record->idroom   = (int) $nowroom['id'];
                        $busy_record->checkin  = (int) $room_checkin_ts;
                        $busy_record->checkout = (int) $room_checkout_ts;
                        $busy_record->realback = (int) $room_realback_ts;

                        // store busy record
                        $dbo->insertObject('#__vikbooking_busy', $busy_record, 'id');

                        if (isset($busy_record->id)) {
                            $insertedbusy[] = $busy_record->id;
                        }
                    }
                }
            } else {
                // for split stay bookings we occupy the rooms on the individual stay dates
                foreach ($split_stay_data as $split_stay) {
                    $busy_record = new stdClass;
                    $busy_record->idroom   = (int) $split_stay['idroom'];
                    $busy_record->checkin  = (int) $split_stay['checkin_ts'];
                    $busy_record->checkout = (int) $split_stay['checkout_ts'];
                    $busy_record->realback = (int) $split_stay['realback_ts'];

                    // store busy record
                    $dbo->insertObject('#__vikbooking_busy', $busy_record, 'id');

                    if (isset($busy_record->id)) {
                        $insertedbusy[] = $busy_record->id;
                    }
                }
            }

            if (!$insertedbusy) {
                $this->setError('No records were occupied');
                return false;
            }
        }

        if ($bookingId) {
            // update booking record
            $dbo->updateObject('#__vikbooking_orders', $booking, 'id');
        } else {
            // store booking record
            $dbo->insertObject('#__vikbooking_orders', $booking, 'id');
        }

        if (empty($booking->id)) {
            $this->setError('Could not save reservation record.');
            return false;
        }

        // get the newly generated (or just updated) booking ID
        $newoid = $booking->id;

        // set the new booking ID
        $this->setNewBookingID($newoid);

        if (!empty($split_stay_data)) {
            // save transient on db for split stay information
            VBOFactory::getConfig()->set('split_stay_' . $newoid, json_encode($split_stay_data));
        }

        if ($status == 'confirmed') {
            // check if some of the rooms booked have shared calendars
            VikBooking::updateSharedCalendars($newoid, [$room['id']], $checkin_ts, $checkout_ts);

            if (!$bookingId) {
                // generate and set confirmation number for the newly created confirmed reservation
                $confirmnumber = VikBooking::generateConfirmNumber($newoid, true);
            }

            // store busy record-booking relations
            foreach ($insertedbusy as $lid) {
                $obusy_record = new stdClass;
                $obusy_record->idorder = (int) $newoid;
                $obusy_record->idbusy  = (int) $lid;

                // store busy relation record
                $dbo->insertObject('#__vikbooking_ordersbusy', $obusy_record, 'id');
            }
        }

        // store room booking records
        foreach ($rooms_pool as $rind => $nowroom) {
            $room_indexes_usemap = [];
            for ($r = 1; $r <= $or_forend; $r++) {
                // determine room-level stay dates
                $room_stay_checkin  = $nowroom['checkin_ts'] ?? $roomsData[$rind]['checkin'] ?? $checkin_ts;
                $room_stay_checkout = $nowroom['checkout_ts'] ?? $roomsData[$rind]['checkout'] ?? $checkout_ts;
                $room_stay_realback = $roomsData[$rind]['realback'] ?? $roomsData[$rind]['checkout'] ?? $realback_ts;

                // check booking status for sub-units
                if ($status == 'confirmed') {
                    // assign room specific unit
                    $info_room_avail = [
                        'id'       => $newoid,
                        'checkin'  => $nowroom['checkin_ts'] ?? $roomsData[$rind]['checkin'] ?? $checkin_ts,
                        'checkout' => $nowroom['checkout_ts'] ?? $roomsData[$rind]['checkout'] ?? $checkout_ts,
                    ];
                    $room_indexes = $set_room_indexes === true ? VikBooking::getRoomUnitNumsAvailable($info_room_avail, $nowroom['id']) : [];
                    $use_ind_key = 0;
                    if ($room_indexes) {
                        if (!array_key_exists($nowroom['id'], $room_indexes_usemap)) {
                            $room_indexes_usemap[$nowroom['id']] = $use_ind_key;
                        } else {
                            $use_ind_key = $room_indexes_usemap[$nowroom['id']];
                        }
                    }
                }

                // gather room-level information
                $cust_cost = (float) ($roomsData[$rind]['cust_cost'] ?? 0);
                $room_cost = (float) ($roomsData[$rind]['room_cost'] ?? 0);
                $id_price  = (int) ($roomsData[$rind]['id_price'] ?? 0);
                $id_tax    = (int) ($roomsData[$rind]['id_tax'] ?? 0);
                $id_tariff = $this->loadTariffID($roomsData[$rind]);
                $cust_indexes = (array) ($roomsData[$rind]['cust_indexes'] ?? []);
                $totalpnight  = ($roomsData[$rind]['total_or_pnight'] ?? null) ?: 'total';
                if ($cust_cost > 0.00 && !$set_closed && $totalpnight == 'pnight') {
                    // custom rate modifier per night
                    $cust_cost = $cust_cost * $daysdiff;
                }
                $cust_cpolicy_id = (int) ($roomsData[$rind]['cust_cpolicy_id'] ?? 0);

                // room custom cost
                $or_cust_cost = $cust_cost > 0.00 ? $cust_cost : 0;
                $or_cust_cost = $or_forend > 1 && $or_cust_cost > 0 ? round(($or_cust_cost / $or_forend), 2) : $or_cust_cost;
                // room cost from website rate plan is always based on one room
                $or_room_cost = $room_cost > 0.00 && !$or_cust_cost ? $room_cost : 0;
                if (!empty($split_stay_data) && $cust_cost > 0) {
                    // set the average cost per room in case of split stay
                    $cost_per_room = ($cust_cost / count($split_stay_data));
                    $or_cust_cost = round($cost_per_room, 2);
                    if (isset($split_stay_data[$rind]['nights'])) {
                        // count the average cost per room depending on the number of nights of stay
                        $cost_per_room = $cust_cost / $daysdiff * $split_stay_data[$rind]['nights'];
                        $or_cust_cost = round($cost_per_room, 2);
                    }
                }

                // room guests
                $room_adults      = (int) ($roomsData[$rind]['adults'] ?? (isset($adults_map[$r]) && empty($split_stay_data) ? $adults_map[$r] : $adults));
                $room_children    = (int) ($roomsData[$rind]['children'] ?? (isset($children_map[$r]) && empty($split_stay_data) ? $children_map[$r] : $children));
                $use_children_age = (array) ($roomsData[$rind]['children_age'] ?? $children_age);

                // number of pets per room
                $room_pets = (int) ($roomsData[$rind]['pets'] ?? 0);

                // attempt to gather the children age for this room
                $room_children_age = null;
                if ($room_children && $use_children_age) {
                    $children_age_pool = [];
                    for ($ic = 0; $ic < $room_children; $ic++) {
                        if (!$use_children_age) {
                            $children_age_pool[] = 0;
                            continue;
                        }
                        // shorten the list and push the current child age
                        $current_child_age = array_shift($use_children_age);
                        $children_age_pool[] = (int) $current_child_age;
                    }
                    $room_children_age = json_encode(['age' => $children_age_pool]);
                }

                // determine room index to use, if any
                $roomindex = null;
                if ($status == 'confirmed') {
                    if ($cust_indexes[$use_ind_key] ?? null) {
                        // use a custom room index
                        $roomindex = (int) $cust_indexes[$use_ind_key];
                    } elseif ($room_indexes) {
                        $roomindex = (int) $room_indexes[$use_ind_key];
                    }
                }

                // handle room record options/extras
                $roomOptStr = null;
                if ($roomsData[$rind]['options'] ?? null) {
                    $roomOptData = array_values(array_filter((array) $roomsData[$rind]['options'], function($roomOpt) {
                        return is_array($roomOpt) && !empty($roomOpt['id']) && (!empty($roomOpt['quantity']) || !empty($roomOpt['age_intervals']));
                    }));
                    // normalize values
                    $roomOptData = array_map(function($roomOpt) {
                        // base option string with ID and quantity
                        $baseOptStr  = sprintf('%d:%d', (int) $roomOpt['id'], (int) (($roomOpt['quantity'] ?? 0) ?: 1));
                        if (!empty($roomOpt['age_intervals'])) {
                            $ageData = [];
                            foreach ((array) $roomOpt['age_intervals'] as $ageIntervalIndex) {
                                $ageData[] = $baseOptStr . '-' . $ageIntervalIndex;
                            }
                            $finalOptStr = implode(';', $ageData);
                        } else {
                            $finalOptStr = $baseOptStr;
                        }
                        return $finalOptStr;
                    }, $roomOptData);
                    if ($roomOptData) {
                        // obtain the value to save
                        $roomOptStr = implode(';', $roomOptData) . ';';
                    }
                }

                // handle room record extra costs
                $roomExtraCosts = array_values(array_filter((array) ($roomsData[$rind]['extras'] ?? []), function($extraCost) {
                    return is_array($extraCost) && !empty($extraCost['name']);
                }));
                if ($roomExtraCosts) {
                    // normalize values
                    $roomExtraCosts = array_map(function($extraCost) {
                        return [
                            'name'  => $extraCost['name'],
                            'cost'  => (float) ($extraCost['cost'] ?? 0),
                            'idtax' => (int) ($extraCost['idtax'] ?? $extraCost['vat'] ?? 0),
                        ];
                    }, $roomExtraCosts);
                }

                // store room record
                $room_record = new stdClass;
                $room_record->idorder         = (int) $newoid;
                $room_record->idroom          = (int) $nowroom['id'];
                $room_record->adults          = $room_adults;
                $room_record->children        = $room_children;
                $room_record->pets            = $room_pets ?: 0;
                $room_record->idtar           = $id_tariff ?: null;
                $room_record->optionals       = $roomOptStr ?: null;
                $room_record->childrenage     = $room_children_age;
                $room_record->t_first_name    = $t_first_name;
                $room_record->t_last_name     = $t_last_name;
                $room_record->roomindex       = $roomindex;
                $room_record->cust_cost       = $cust_cost > 0 ? $or_cust_cost : null;
                $room_record->cust_idiva      = $cust_cost > 0 && !empty($id_tax) ? $id_tax : null;
                $room_record->cust_cpolicy_id = $cust_cpolicy_id ?: null;
                $room_record->extracosts      = $roomExtraCosts ? json_encode($roomExtraCosts) : null;
                $room_record->room_cost       = $or_room_cost > 0 ? $or_room_cost : null;

                $dbo->insertObject('#__vikbooking_ordersrooms', $room_record, 'id');

                if (empty($room_record->id)) {
                    $this->setError('Could not store room reservation record for booking ID ' . $room_record->idorder);
                    continue;
                }

                if ($status == 'confirmed') {
                    // assign room specific unit
                    if ($room_indexes) {
                        $room_indexes_usemap[$nowroom['id']]++;
                    }
                } elseif ($status == 'standby' && empty($split_stay_data)) {
                    // lock room for pending status when NO split-stay data by supporting room-level dates
                    $lockUntil = VikBooking::getMinutesLock(true);

                    if ($this->get('lock_until')) {
                        // convert expected date-time string into a timestamp for custom lock until date
                        $lockUntil = strtotime((string) $this->get('lock_until')) ?: $lockUntil;
                    }

                    if ($lockUntil && $this->get('dont_lock') !== true) {
                        // store room lock record
                        $tmplock_record = new stdClass;
                        $tmplock_record->idroom   = (int) $nowroom['id'];
                        $tmplock_record->checkin  = $room_stay_checkin;
                        $tmplock_record->checkout = $room_stay_checkout;
                        $tmplock_record->until    = $lockUntil;
                        $tmplock_record->realback = $room_stay_realback;
                        $tmplock_record->idorder  = (int) $newoid;

                        $dbo->insertObject('#__vikbooking_tmplock', $tmplock_record, 'id');
                    }
                }
            }
        }

        if ($status == 'standby' && !empty($split_stay_data)) {
            // lock split-stay rooms for pending status on proper stay dates
            foreach ($split_stay_data as $split_stay) {
                $tmplock_record = new stdClass;
                $tmplock_record->idroom   = (int) $split_stay['idroom'];
                $tmplock_record->checkin  = (int) $split_stay['checkin_ts'];
                $tmplock_record->checkout = (int) $split_stay['checkout_ts'];
                $tmplock_record->until    = VikBooking::getMinutesLock(true);
                $tmplock_record->realback = (int) $split_stay['realback_ts'];
                $tmplock_record->idorder  = (int) $newoid;

                $dbo->insertObject('#__vikbooking_tmplock', $tmplock_record, 'id');
            }
        }

        // assign booking to customer
        if (!$cpin->getNewCustomerId() && !empty($customer_id)) {
            $cpin->setNewPin($customer_pin);
            $cpin->setNewCustomerId($customer_id);
        }
        $cpin->saveCustomerBooking($newoid);

        // handle booking history
        $history_obj = VikBooking::getBookingHistoryInstance($newoid);

        $forced_reason = !empty($forced_reason) ? " {$forced_reason}" : $forced_reason;
        $caller_id = $now_user->name ? "({$now_user->name})" : '';
        if ($this->getCaller()) {
            $caller_id = '(' . $this->getCaller() . ')';
            if ($this->getHistoryData()) {
                $history_obj->setExtraData($this->getHistoryData());
            }
        }
        if ($this->get('idquote')) {
            // mention the quote ID in the history description
            $forced_reason .= sprintf(' %s #%d', JText::translate('VBO_BTYPE_QUOTE'), (int) $this->get('idquote'));
        }

        // tell whether alterations were detected, in case of booking update
        $alterationsDetected = false;

        if ($bookingId) {
            // booking updated event
            $historyPrevBooking = $this->prevBookingRegistry->getData();
            $historyPrevBooking['rooms_info'] = $this->prevBookingRegistry->getRooms();
            $history_obj
                ->setPrevBooking($historyPrevBooking)
                ->store('MB', trim($caller_id . $forced_reason) . ' ' . VikBooking::getLogBookingModification($historyPrevBooking));

            // construct a new booking registry, and inject the booking snapshot prior updating
            $currentRegistry = VBOBookingRegistry::getInstance(['id' => $bookingId], [], $historyPrevBooking);
            // detect alterations (include room-level)
            $alterationsDetected = $currentRegistry->detectAlterations(true);
        } else {
            // new booking event
            $history_obj->store('NB', trim($caller_id . $forced_reason));
        }

        if ($status == 'confirmed' || ($status == 'standby' && class_exists('VCMRequestAvailability'))) {
            // Invoke Channel Manager
            $vcm_autosync = VikBooking::vcmAutoUpdate();
            if ($vcm_autosync > 0) {
                $vcm_obj = VikBooking::getVcmInvoker();

                // tell if sync is actually needed
                $sync_needed = true;
                if ($bookingId && $alterationsDetected === false) {
                    // do not waste connections when nothing sensitive was updated
                    $sync_needed = false;
                }

                if ($sync_needed) {
                    if ($bookingId) {
                        // sync for an updated booking, after something was truly modified
                        $vcm_obj->setOids([$bookingId])->setSyncType('modify')->setOriginalBooking($historyPrevBooking ?? []);
                    } else {
                        // always sync for a new booking
                        $vcm_obj->setOids([$newoid])->setSyncType('new');
                    }

                    // run operation
                    $sync_result = $vcm_obj->doSync();

                    if ($sync_result === false) {
                        // set error message, but do NOT return false at this point
                        $vcm_err = $vcm_obj->getError();
                        $this->setError(JText::translate('VBCHANNELMANAGERRESULTKO') . (!empty($vcm_err) ? ' - ' . $vcm_err : ''));
                    }
                }
            } elseif (!$bookingId && is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'synch.vikbooking.php')) {
                // set the necessary action to invoke VCM (only when creating a new booking)
                $vcm_sync_url = 'index.php?option=com_vikbooking&task=invoke_vcm&stype=new&cid[]=' . $newoid . '&returl=' . urlencode('index.php?option=com_vikbooking&task=calendar&cid[]=' . $room['id']);

                $this->setChannelManagerAction(JText::translate('VBCHANNELMANAGERINVOKEASK') . ' <button type="button" class="btn btn-primary" onclick="document.location.href=\'' . $vcm_sync_url . '\';">' . JText::translate('VBCHANNELMANAGERSENDRQ') . '</button>');
            }
        }

        if (VikBooking::isAdmin()) {
            /**
             * Trigger event to allow third party plugins to intercept the admin new/updated booking event.
             * 
             * @since   1.16.8 (J) - 1.6.8 (WP)
             */
            VBOFactory::getPlatform()->getDispatcher()->trigger($bookingId ? 'onAfterUpdateBookingAdmin' : 'onAfterCreateNewBookingAdmin', [$newoid]);
        }

        return true;
    }

    /**
     * Attempts to find the default payment method to be assigned to a booking.
     * 
     * @param   bool    $auto   If true, it was requested to automatically find the best payment method.
     * 
     * @return  string          The payment method string for the reservation "ID=Name", or an empty string.
     * 
     * @since   1.17.3 (J) - 1.7.3 (WP)
     */
    protected function getDefaultPaymentMethod($auto = false)
    {
        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select('*')
                ->from($dbo->qn('#__vikbooking_gpayments'))
                ->order($dbo->qn('published') . ' DESC')
                ->order($dbo->qn('ordering') . ' ASC')
                ->order($dbo->qn('setconfirmed') . ' ASC')
                ->order($dbo->qn('name') . ' ASC')
        );

        $methods = $dbo->loadAssocList();

        if ($auto) {
            // exclude all the offline or unpublished payment methods
            $methods = array_filter($methods, function($method) {
                return !((bool) intval($method['setconfirmed'])) && (bool) intval($method['published']);
            });

            // reset array keys
            $methods = array_values($methods);
        }

        if ($methods) {
            // default payment method found
            return sprintf('%s=%s', $methods[0]['id'], $methods[0]['name']);
        }

        // nothing was found
        return '';
    }
}
