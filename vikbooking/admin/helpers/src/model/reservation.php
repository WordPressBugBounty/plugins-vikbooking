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
 * @since 	1.16.0 (J) - 1.6.0 (WP)
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
	 * Proxy for immediately getting the object and bind data.
	 * 
	 * @param 	array|object  $data  optional data to bind.
	 * @param 	boolean 	  $anew  true for forcing a new instance.
	 * 
	 * @return 	self
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
	 * @param 	string 	$caller 	The caller identifier.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function setCaller($caller = '')
	{
		$this->set('_caller', (string) $caller);

		return $this;
	}

	/**
	 * Returns the caller information.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function getCaller()
	{
		return (string) $this->get('_caller', '');
	}

	/**
	 * Sets the history extra data value.
	 * 
	 * @param 	array 	$data 	The history extra data array.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function setHistoryData(array $data = [])
	{
		$this->set('_historyData', $data);

		return $this;
	}

	/**
	 * Returns the customer information.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function getHistoryData()
	{
		return (array) $this->get('_historyData', []);
	}

	/**
	 * Sets the search filters.
	 * 
	 * @param 	array 	$data 	The search filters associative array.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function setFilters(array $data = [])
	{
		$this->set('_filters', $data);

		return $this;
	}

	/**
	 * Returns the search filters.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function getFilters()
	{
		return (array) $this->get('_filters', []);
	}

	/**
	 * Sets the booking information record.
	 * 
	 * @param 	array 	$booking 	The booking record.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function setBooking(array $booking = [])
	{
		$this->set('_booking', $booking);

		return $this;
	}

	/**
	 * Returns the booking information record.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function getBooking()
	{
		return (array) $this->get('_booking', []);
	}

	/**
	 * Sets the room booking records.
	 * 
	 * @param 	array 	$room_booking 	The room booking records.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function setRoomBooking(array $room_booking = [])
	{
		$this->set('_roomBooking', $room_booking);

		return $this;
	}

	/**
	 * Returns the room booking records.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function getRoomBooking()
	{
		return (array) $this->get('_roomBooking', []);
	}

	/**
	 * Sets the customer information.
	 * 
	 * @param 	array 	$customer 	the customer array.
	 * 
	 * @return 	self
	 */
	public function setCustomer(array $customer = [])
	{
		$this->set('_customer', $customer);

		return $this;
	}

	/**
	 * Returns the customer information.
	 * 
	 * @return 	array
	 */
	public function getCustomer()
	{
		return (array) $this->get('_customer', []);
	}

	/**
	 * Sets the room information.
	 * 
	 * @param 	array 	$room 	the room array.
	 * 
	 * @return 	self
	 */
	public function setRoom(array $room = [])
	{
		$this->set('_room', $room);

		return $this;
	}

	/**
	 * Returns the room information.
	 * 
	 * @return 	array
	 */
	public function getRoom()
	{
		return (array) $this->get('_room', []);
	}

	/**
	 * Sets the new booking ID created.
	 * 
	 * @param 	int 	$bid 	the newly added record ID.
	 * 
	 * @return 	self
	 */
	protected function setNewBookingID($bid = 0)
	{
		$this->set('_newBookingID', $bid);

		return $this;
	}

	/**
	 * Returns the new booking ID created, or 0.
	 * 
	 * @return 	int
	 */
	public function getNewBookingID()
	{
		return (int) $this->get('_newBookingID', 0);
	}

	/**
	 * Sets the VCM action to be performed in order to sync the availability.
	 * 
	 * @param 	string 	$action 	the VCM action, usually an HTML link.
	 * 
	 * @return 	self
	 */
	protected function setChannelManagerAction($action = '')
	{
		$this->set('_vcmAction', $action);

		return $this;
	}

	/**
	 * Returns the VCM action (if any) to sync the availability.
	 * 
	 * @return 	string
	 */
	public function getChannelManagerAction()
	{
		return $this->get('_vcmAction', '');
	}

	/**
	 * Sets the check-in and check-out times with hours and minutes.
	 * 
	 * @return 	array 	list of check-in and check-out hours and minutes.
	 */
	public function loadCheckinOutTimes()
	{
		static $times_loaded = null;

		if ($times_loaded) {
			return [
				$this->get('checkin_h'),
				$this->get('checkin_m'),
				$this->get('checkout_h'),
				$this->get('checkout_m'),
			];
		}

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

		$this->set('checkin_h', $hcheckin);
		$this->set('checkin_m', $mcheckin);
		$this->set('checkout_h', $hcheckout);
		$this->set('checkout_m', $mcheckout);

		$times_loaded = 1;

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
	 * @return 	string
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
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
	 * Creates a new reservation record after having constructed the
	 * object by properly injecting all the necessary booking information.
	 * 
	 * @return 	bool
	 */
	public function create()
	{
		if (!$this->canCreate()) {
			$this->setError('Forbidden');
			return false;
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

			// make sure check-in and check-out timestamps have been set to a proper time
			$from_info = getdate($this->get('checkin'));
			$to_info   = getdate($this->get('checkout'));
			if ((int) $from_info['hour'] != (int) $this->get('checkin_h')) {
				$this->set('checkin', mktime((int) $this->get('checkin_h'), (int) $this->get('checkin_m'), 0, $from_info['mon'], $from_info['mday'], $from_info['year']));
			}
			if ((int) $to_info['hour'] != (int) $this->get('checkout_h')) {
				$this->set('checkout', mktime((int) $this->get('checkout_h'), (int) $this->get('checkout_m'), 0, $to_info['mon'], $to_info['mday'], $to_info['year']));
			}
		}

		// number of nights of stay
		if (!$this->get('nights')) {
			$this->set('nights', $av_helper->countNightsOfStay($this->get('checkin'), $this->get('checkout')));
		}

		// fetch and apply turnover time before doing anything else
		$this->applyTurnover();

		// if rate plan selected, get the tariff ID
		$this->loadTariffID();

		// get pool of rooms involved
		$rooms_pool = $this->getRoomsPool();
		if (!$rooms_pool) {
			if ($this->getError() === false) {
				// set generic error if not set already
				$this->setError('No rooms involved in the reservation');
			}
			return false;
		}

		// check if the room is available
		$room_available = $this->isRoomAvailable();
		if (!$this->get('force_booking', 0) && !$this->get('set_closed', 0) && !$room_available) {
			// no forcing, no closure and room fully booked results into an error message
			$this->setError(JText::translate('VBBOOKNOTMADE'));
			return false;
		}

		// detect if we are forcing the reservation
		$this->detectForcedReason($room_available);

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
	 * Searches for bookings according to specified filters.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
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
	 * Modifies the requested booking ID according to the provided options.
	 * This method does not support all rate plan options like for the creation of a
	 * new booking. This is a method for making quick updates concerning a room switch,
	 * a change of stay dates, new booking total amount, guests, add extra services etc..
	 * 
	 * @param 	array 	$options 	List of details to perform the modification.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
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
	 * Deletes the requested booking ID.
	 * 
	 * @param 	array 	$options 	List of details to perform the cancellation.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function delete(array $options)
	{
		$dbo = JFactory::getDbo();

		$booking_id   = $options['booking_id'] ?? 0;
		$canc_reason  = $options['cancellation_reason'] ?? '';
		$purge_remove = $options['purge_remove'] ?? false;

		$booking = VikBooking::getBookingInfoFromID($booking_id);

		if (!$booking) {
			$this->setError('Booking not found.');
			return false;
		}

		if ($booking['status'] === 'cancelled' && !$purge_remove) {
			$this->setError(sprintf('Booking ID %d is already cancelled.', $booking['id']));
			return false;
		}

		if (class_exists('VCMFeesCancellation')) {
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

			// delete temporarily locked records, if any
			$dbo->setQuery(
				$dbo->getQuery(true)
					->delete($dbo->qn('#__vikbooking_tmplock'))
					->where($dbo->qn('idorder') . ' = ' . (int) $booking['id'])
			);
			$dbo->execute();

			if ($booking['status'] == 'confirmed') {
				// turn flag on
				$notify_otas = true;
			}

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
		if ($booking['status'] === 'cancelled' && $purge_remove) {
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

		if ($notify_otas) {
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
	 * @param 	array 	$options 	List of details to perform the update.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.17.3 (J) - 1.7.3 (WP)
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
			$room_stay_nights 	= $booking['days'];
			if ($booking['split_stay'] && $room_stay_dates && isset($room_stay_dates[$ind]) && $room_stay_dates[$ind]['idroom'] == $or['idroom']) {
				$room_stay_checkin  = $room_stay_dates[$ind]['checkin_ts'] ?: $room_stay_dates[$ind]['checkin'];
				$room_stay_checkout = $room_stay_dates[$ind]['checkout_ts'] ?: $room_stay_dates[$ind]['checkout'];
				$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
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

		// set booking confirmation number
		$confirmnumber = VikBooking::generateConfirmNumber($booking['id'], true);

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

		// check if some of the rooms booked have shared calendars
		VikBooking::updateSharedCalendars($booking['id'], array_column($roomBooking, 'idroom'), $booking['checkin'], $booking['checkout']);

		// assign room specific unit(s)
		$set_room_indexes = VikBooking::autoRoomUnit();
		$room_indexes_usemap = [];

		foreach ($roomBooking as $kor => $or) {
			// determine proper values for this room
			$room_stay_checkin  = $booking['checkin'];
			$room_stay_checkout = $booking['checkout'];
			$room_stay_nights 	= $booking['days'];
			if ($booking['split_stay'] && $room_stay_dates && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
				$room_stay_checkin  = $room_stay_dates[$kor]['checkin_ts'] ?: $room_stay_dates[$kor]['checkin'];
				$room_stay_checkout = $room_stay_dates[$kor]['checkout_ts'] ?: $room_stay_dates[$kor]['checkout'];
				$room_stay_nights 	= $room_stay_dates[$kor]['nights'];
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

				// return true because the booking was actually confirmed
				return true;
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
	 * @return 	array
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
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
	 * Attempts to invoke the payment processor assigned to the current booking.
	 * 
	 * @param 	array 	$card 	Optional credit card details to bind.
	 * 
	 * @return 	object 			The payment processor dispatcher instance.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 * @since 	1.17.6 (J) - 1.7.6 (WP) added "tn_metadata" details.
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
		];

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly 	The payment gateway is loaded 
			 * 			through the apposite dispatcher.
			 */
			JLoader::import('adapter.payment.dispatcher');
			$processor = JPaymentDispatcher::getInstance('vikbooking', $payment['file'], $booking, $payment['params']);
		} elseif (VBOPlatformDetection::isJoomla()) {
			/**
			 * @joomlaonly 	The Payment Factory library will invoke the gateway.
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
	 * @return 	string
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
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
	 * @param 	string 	$tn_driver 	Optional payment processor driver name.
	 * 
	 * @return 	object[] 			Eligible transaction data list or empty array.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
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
	 * @return 	array 	Associative list of CC value-pairs, if any.
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)
	 */
	public function getCardValuePairs()
	{
		$booking_info = $this->getProperties();

		if (empty($booking_info['paymentlog'])) {
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
					'ota_id' 		 => $booking_info['idorderota'],
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
	 * @param 	string 	$cc_payload 		the raw CC details from payment logs.
	 * @param 	array 	$remote_cc_data 	associative array of decoded CC data.
	 * 
	 * @return 	array 						associative or empty array.
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)   moved from widget Virtual Terminal.
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
	 * @param 	string 	$local 		current string with masked values.
	 * @param 	string 	$remote 	remote string with unmasked values.
	 * 
	 * @return 	string 				the local string with unmasked values.
	 * 
	 * @since 	1.16.10 (J) - 1.6.10 (WP)   moved from widget Virtual Terminal.
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
	 * @return 	bool
	 */
	protected function canCreate()
	{
		return $this->get('_isAdministrator') || JFactory::getApplication()->isClient('administrator');
	}

	/**
	 * Gets and sets the tariff ID if a rate plan was set.
	 * 
	 * @return 	int 	the tariff ID or 0.
	 */
	protected function loadTariffID()
	{
		$dbo = JFactory::getDbo();

		$id_tariff = 0;
		$room = $this->getRoom();
		$daysdiff = (int)$this->get('nights', 1);

		if (!empty($room['id']) && !empty($room['id_price']) && !empty($room['room_cost']) && $room['room_cost'] > 0 && !(int)$this->get('set_closed') && !$this->get('split_stay', [])) {
			$room['id_price'] = (int)$room['id_price'];

			$q = "SELECT `id` FROM `#__vikbooking_dispcost` WHERE `idroom`={$room['id']} AND `days`={$daysdiff} AND `idprice`={$room['id_price']};";
			$dbo->setQuery($q);
			$id_tariff = $dbo->loadResult();
		}

		$this->set('id_tariff', (int)$id_tariff);

		return (int)$id_tariff;
	}

	/**
	 * Applies the turnover time to the checkout timestamp and sets its value.
	 * 
	 * @return 	int 	the turnover seconds applied.
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
	 * Returns the details of a specific room ID.
	 * 
	 * @param 	int 	$rid 	the room ID.
	 * 
	 * @return 	array 	the record found or empty array.
	 */
	protected function getRoomDetails($rid = null)
	{
		$all_rooms = VikBooking::getAvailabilityInstance(true)->loadRooms();

		if (!$rid) {
			$inj_room = $this->getRoom();
			if (!empty($inj_room['id'])) {
				$rid = $inj_room['id'];
			}
		}

		if ($rid && isset($all_rooms[$rid])) {
			return $all_rooms[$rid];
		}

		return [];
	}

	/**
	 * Gets the list of rooms involved in the reservation in case of closures.
	 * 
	 * @return 	array the list of rooms involved
	 */
	protected function getRoomsPool()
	{
		$room = $this->getRoom();
		if (empty($room['id'])) {
			return [];
		}
		$room = $this->getRoomDetails($room['id']);

		// gather values
		$set_close_others = (array)$this->get('close_others', []);
		$split_stay_data  = $this->get('split_stay', []);
		$set_closed 	  = (int)$this->get('set_closed');
		$turnover_secs 	  = $this->get('turnover_secs', 0);
		$hcheckin 		  = $this->get('checkin_h', 12);
		$mcheckin 		  = $this->get('checkin_m', 0);
		$hcheckout 		  = $this->get('checkout_h', 10);
		$mcheckout 		  = $this->get('checkout_m', 0);

		$av_helper 	 = VikBooking::getAvailabilityInstance(true);
		$all_rooms 	 = $av_helper->loadRooms();
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
			if ((int)$closeid === -1) {
				// close all rooms
				$closeothers = [];
				foreach ($all_rooms as $cr) {
					array_push($closeothers, $cr);
				}
				break;
			}
			foreach ($all_rooms as $cr) {
				if ((int)$cr['id'] == (int)$closeid) {
					// push the main room or one of the other rooms requested for closure
					array_push($closeothers, $cr);
					break;
				}
			}
		}

		if (!$closeothers || !$set_closed) {
			$rooms_pool = [$room];
		} else {
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
				$split_stay['nights'] 	   = $av_helper->countNightsOfStay($split_stay['checkin_ts'], $split_stay['checkout_ts']);
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
	 * Checks that the room is available on the requested dates.
	 * Call this method only after getting the rooms pool.
	 * 
	 * @return 	bool
	 */
	protected function isRoomAvailable()
	{
		$inj_room = $this->getRoom();
		if (empty($inj_room['id'])) {
			return false;
		}
		$room = $this->getRoomDetails($inj_room['id']);
		if (!$room) {
			return false;
		}

		$split_stay_data = $this->get('split_stay', []);
		$set_closed = $this->get('set_closed', 0);
		$num_rooms = $this->get('num_rooms', 1);

		$room_available = true;

		if (empty($split_stay_data)) {
			// make sure the rooms are available
			$check_units = $room['units'];
			if ($num_rooms > 1 && $num_rooms <= $room['units'] && !$set_closed) {
				// only when non closing the room we check the availability for the units requested for booking
				$check_units = $room['units'] - $num_rooms + 1;
			}
			$room_available = VikBooking::roomBookable($room['id'], $check_units, $this->get('checkin', 0), $this->get('checkout', 0));
		} else {
			$all_rooms = VikBooking::getAvailabilityInstance(true)->loadRooms();
			// make sure the rooms for the split stay are available
			foreach ($split_stay_data as $split_stay) {
				if (!isset($all_rooms[$split_stay['idroom']])) {
					$room_available = false;
					break;
				}
				$room_available = $room_available && VikBooking::roomBookable($split_stay['idroom'], $all_rooms[$split_stay['idroom']]['units'], $split_stay['checkin_ts'], $split_stay['checkout_ts']);
			}
		}

		return $room_available;
	}

	/**
	 * In case the reservation is forced or is a closure, we detect the
	 * forced reason to eventually attach it to the booking history.
	 * 
	 * @param 	bool 	$room_available 	whether the room is available.
	 * 
	 * @return 	void
	 */
	protected function detectForcedReason($room_available = true)
	{
		$split_stay_data = $this->get('split_stay', []);
		$force_booking = $this->get('force_booking', 0);
		$set_closed = $this->get('set_closed', 0);

		$forced_reason = $this->get('forced_reason', '');

		if (empty($split_stay_data)) {
			// eventually build string for the description of the history event
			if (($force_booking || $set_closed) && !$room_available) {
				$forced_reason = JText::translate('VBO_FORCED_BOOKDATES');
			}
		} else {
			$all_rooms = VikBooking::getAvailabilityInstance(true)->loadRooms();
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
	 * @return 	bool
	 */
	protected function storeCustomer()
	{
		$dbo = JFactory::getDbo();

		$inj_customer = $this->getCustomer();
		$first_name   = !empty($inj_customer['first_name']) ? $inj_customer['first_name'] : '';
		$last_name 	  = !empty($inj_customer['last_name']) ? $inj_customer['last_name'] : '';
		$custdata 	  = !empty($inj_customer['data']) ? $inj_customer['data'] : '';
		$email 		  = !empty($inj_customer['email']) ? $inj_customer['email'] : '';
		$country 	  = !empty($inj_customer['country']) ? $inj_customer['country'] : '';
		$phone 		  = !empty($inj_customer['phone']) ? $inj_customer['phone'] : '';

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
	 * @return 	array 	list of booking total amount and total taxes.
	 */
	protected function calculateTotal()
	{
		// the values to calculate
		$set_total = 0;
		$set_taxes = 0;

		$dbo = JFactory::getDbo();

		// get data
		$inj_room 	 = $this->getRoom();
		$set_closed  = $this->get('set_closed', 0);
		$daysdiff 	 = (int)$this->get('nights', 1);
		$num_rooms 	 = (int)$this->get('num_rooms', 1);
		$totalpnight = !empty($inj_room['total_or_pnight']) ? $inj_room['total_or_pnight'] : 'total';
		$cust_cost 	 = !empty($inj_room['cust_cost']) ? (float)$inj_room['cust_cost'] : 0;
		$room_cost 	 = !empty($inj_room['room_cost']) ? (float)$inj_room['room_cost'] : 0;
		$id_price 	 = !empty($inj_room['id_price']) ? (int)$inj_room['id_price'] : 0;
		$id_tax 	 = !empty($inj_room['id_tax']) ? (int)$inj_room['id_tax'] : 0;

		$split_stay_data = $this->get('split_stay', []);

		$room = $this->getRoomDetails();
		if (!$room) {
			return [$set_total, $set_taxes];
		}

		if ($cust_cost > 0 && !$set_closed) {
			// custom cost can be per night
			if ($totalpnight == 'pnight') {
				$cust_cost = $cust_cost * $daysdiff;
			}
			$set_total = $cust_cost;

			if (!$id_tax && ($inj_room['guess_tax'] ?? false)) {
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
					$inj_room['id_tax'] = $guessed_id_tax;
					$this->setRoom($inj_room);
				}
			}

			// apply taxes, if necessary
			if ($id_tax) {
				$dbo->setQuery(
					$dbo->getQuery(true)
						->select([
							$dbo->qn('i.aliq'),
							$dbo->qn('i.taxcap'),
						])
						->from($dbo->qn('#__vikbooking_iva', 'i'))
						->where($dbo->qn('i.id') . ' = ' . (int) $id_tax),
				0, 1);
				$taxdata = $dbo->loadAssoc();
				if ($taxdata) {
					$aliq = $taxdata['aliq'];
					if (floatval($aliq) > 0.00) {
						if (!VikBooking::ivaInclusa()) {
							// add tax to the total amount
							$subt = 100 + (float)$aliq;
							$set_total = ($set_total * $subt / 100);
							/**
							 * Tax Cap implementation for prices tax excluded (most common).
							 * 
							 * @since 	1.12 (J) - 1.2 (WP)
							 */
							if ($taxdata['taxcap'] > 0 && ($set_total - $cust_cost) > $taxdata['taxcap']) {
								$set_total = ($cust_cost + $taxdata['taxcap']);
							}
							// calculate tax
							$set_taxes = $set_total - $cust_cost;
						} else {
							// calculate tax
							$cost_minus_tax = VikBooking::sayPackageMinusIva($cust_cost, $id_tax);
							$set_taxes += ($cust_cost - $cost_minus_tax);
						}
					}
				}
			}
		} elseif (!empty($id_price) && $room_cost > 0 && !$set_closed && empty($split_stay_data)) {
			// one website rate plan was selected, so we calculate total and taxes
			$set_total = $room_cost;

			// find tax rate assigned to this rate plan
			$q = "SELECT `p`.`id`,`p`.`idiva`,`i`.`aliq`,`i`.`taxcap` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` AS `i` ON `p`.`idiva`=`i`.`id` WHERE `p`.`id`=" . $id_price . ";";
			$dbo->setQuery($q);
			$taxdata = $dbo->loadAssoc();
			if ($taxdata) {
				$aliq = $taxdata['aliq'];
				if (floatval($aliq) > 0.00) {
					if (!VikBooking::ivaInclusa()) {
						// add tax to the total amount
						$subt = 100 + (float)$aliq;
						$set_total = ($set_total * $subt / 100);
						/**
						 * Tax Cap implementation for prices tax excluded (most common).
						 * 
						 * @since 	1.12 (J) - 1.2 (WP)
						 */
						if ($taxdata['taxcap'] > 0 && ($set_total - $room_cost) > $taxdata['taxcap']) {
							$set_total = ($room_cost + $taxdata['taxcap']);
						}
						// calculate tax
						$set_taxes = $set_total - $room_cost;
					} else {
						// calculate tax
						$cost_minus_tax = VikBooking::sayPackageMinusIva($room_cost, $taxdata['idiva']);
						$set_taxes += ($room_cost - $cost_minus_tax);
					}
				}
			}

			// total and taxes should be multiplied by the number of rooms booked when using a website rate plan
			if ($set_closed) {
				$set_total *= $room['units'];
				$set_taxes *= $room['units'];
			} elseif ($num_rooms > 1 && $num_rooms <= $room['units']) {
				$set_total *= $num_rooms;
				$set_taxes *= $num_rooms;
			}
		}

		// set values
		$this->set('_total', $set_total);
		$this->set('_total_tax', $set_taxes);

		return [$set_total, $set_taxes];
	}

	/**
	 * Stores the booking and room-booking records.
	 * If no errors, the newly generated booking id is set.
	 * 
	 * @param 	array 	$rooms_pool 	list of rooms involved.
	 * 
	 * @return 	bool
	 */
	protected function storeReservationRecords(array $rooms_pool)
	{
		$dbo = JFactory::getDbo();

		$set_closed   = $this->get('set_closed', 0);
		$units_closed = $this->get('units_closed', 0);
		$daysdiff 	  = (int) $this->get('nights', 1);
		$num_rooms 	  = (int) $this->get('num_rooms', 1);
		$adults 	  = (int) $this->get('adults', 1);
		$children 	  = (int) $this->get('children', 0);
		$children_age = (array) $this->get('children_age', []);
		$status 	  = $this->get('status', 'confirmed');

		$split_stay_data = $this->get('split_stay', []);
		$room = $this->getRoomDetails();
		if (!$room || !$rooms_pool) {
			return false;
		}

		// get current Joomla/WordPress User ID
		$now_user = JFactory::getUser();
		$store_ujid = property_exists($now_user, 'id') ? (int)$now_user->id : 0;

		// forced booking reason, status validation and additional data
		$forced_reason  = $this->get('forced_reason', '');
		$valid_statuses = ['confirmed', 'standby'];
		$status 		= in_array($status, $valid_statuses) ? $status : 'confirmed';
		$paymentmeth 	= $this->get('id_payment', '');
		$auto_paymeth   = (bool) $this->get('auto_payment_method', false);
		$set_total 		= (float) $this->get('_total', 0);
		$set_taxes 		= (float) $this->get('_total_tax', 0);

		// stay dates
		$now_ts 	 = time();
		$checkin_ts  = $this->get('checkin');
		$checkout_ts = $this->get('checkout');
		$realback_ts = $this->get('checkout_real', $checkout_ts);

		// room
		$inj_room  = $this->getRoom();
		$cust_cost = !empty($inj_room['cust_cost']) ? (float) $inj_room['cust_cost'] : 0;
		$room_cost = !empty($inj_room['room_cost']) ? (float) $inj_room['room_cost'] : 0;
		$id_price  = !empty($inj_room['id_price']) ? (int) $inj_room['id_price'] : 0;
		$id_tax    = !empty($inj_room['id_tax']) ? (int) $inj_room['id_tax'] : 0;
		$id_tariff = $this->get('id_tariff', 0);

		// custom rate modifier per night
		$totalpnight = !empty($inj_room['total_or_pnight']) ? $inj_room['total_or_pnight'] : 'total';
		if ($cust_cost > 0.00 && !$set_closed && $totalpnight == 'pnight') {
			$cust_cost = $cust_cost * $daysdiff;
		}

		// customer
		$cpin 			= VikBooking::getCPinInstance();
		$inj_customer 	= $this->getCustomer();
		$customer_id 	= !empty($inj_customer['id']) ? $inj_customer['id'] : 0;
		$customer_pin 	= !empty($inj_customer['pin']) ? $inj_customer['pin'] : '';
		$t_first_name 	= !empty($inj_customer['first_name']) ? $inj_customer['first_name'] : '';
		$t_last_name 	= !empty($inj_customer['last_name']) ? $inj_customer['last_name'] : '';
		$customer_data 	= !empty($inj_customer['data']) ? $inj_customer['data'] : '';
		$customer_email = !empty($inj_customer['email']) ? $inj_customer['email'] : '';
		$country_code 	= !empty($inj_customer['country']) ? $inj_customer['country'] : '';
		$phone_number 	= !empty($inj_customer['phone']) ? $inj_customer['phone'] : '';

		if ($set_closed) {
			$customer_data = JText::translate('VBDBTEXTROOMCLOSED');
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

		// generate booking SID
		$sid = VikBooking::getSecretLink();

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

		// count total rooms booked
		$totalrooms = ($set_closed && $status == 'confirmed' ? count($rooms_pool) : ($num_rooms > 1 && $num_rooms <= $room['units'] ? $num_rooms : 1));
		$totalrooms = !empty($split_stay_data) ? count($split_stay_data) : $totalrooms;

		// attempt to get the default payment method
		if (!$paymentmeth && ($auto_paymeth || $status == 'standby')) {
			// get the default payment method, if any
			$paymentmeth = $this->getDefaultPaymentMethod($auto_paymeth);
		}

		// prepare booking record
		$booking = new stdClass;
		$booking->custdata 	 = $customer_data;
		$booking->ts 		 = $now_ts;
		$booking->status 	 = $status;
		$booking->days 		 = $daysdiff;
		$booking->checkin 	 = $checkin_ts;
		$booking->checkout 	 = $checkout_ts;
		$booking->custmail 	 = $customer_email;
		$booking->sid 		 = $sid;
		$booking->idpayment  = $paymentmeth;
		$booking->ujid 		 = (int)$store_ujid;
		$booking->roomsnum 	 = $totalrooms;
		$booking->total 	 = $set_total > 0 ? $set_total : null;
		if ($this->get('admin_notes')) {
			$booking->adminnotes = $this->get('admin_notes', '');
		}
		$booking->lang 	 	 = VikBooking::guessBookingLangFromCountry($country_code);
		$booking->country 	 = $country_code;
		$booking->tot_taxes  = $set_taxes > 0 ? $set_taxes : null;
		$booking->phone 	 = $phone_number;
		$booking->closure 	 = ($status == 'standby' ? 0 : ($set_closed || $units_closed ? 1 : 0));
		$booking->split_stay = !empty($split_stay_data) ? 1 : 0;

		// store reservation
		if ($status == 'confirmed') {
			// occupy the rooms
			$insertedbusy = [];
			if (empty($split_stay_data)) {
				// only when closing other rooms we have an array containing multiple rooms info
				foreach ($rooms_pool as $nowroom) {
					$nowforend = $set_closed ? $nowroom['units'] : $forend;
					for ($b = 1; $b <= $nowforend; $b++) {
						$busy_record = new stdClass;
						$busy_record->idroom   = (int)$nowroom['id'];
						$busy_record->checkin  = (int)$checkin_ts;
						$busy_record->checkout = (int)$checkout_ts;
						$busy_record->realback = (int)$realback_ts;

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
					$busy_record->idroom   = (int)$split_stay['idroom'];
					$busy_record->checkin  = (int)$split_stay['checkin_ts'];
					$busy_record->checkout = (int)$split_stay['checkout_ts'];
					$busy_record->realback = (int)$split_stay['realback_ts'];

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

			// store booking record
			$dbo->insertObject('#__vikbooking_orders', $booking, 'id');

			if (!isset($booking->id)) {
				$this->setError('Could not store the reservation record');
				return false;
			}

			// get the newly generated booking ID
			$newoid = $booking->id;

			// set the new booking ID
			$this->setNewBookingID($newoid);

			if (!empty($split_stay_data)) {
				// save transient on db for split stay information
				VBOFactory::getConfig()->set('split_stay_' . $newoid, json_encode($split_stay_data));
			}

			// check if some of the rooms booked have shared calendars
			VikBooking::updateSharedCalendars($newoid, [$room['id']], $checkin_ts, $checkout_ts);

			// confirmation number
			$confirmnumber = VikBooking::generateConfirmNumber($newoid, true);

			// store busy records/booking relations
			foreach ($insertedbusy as $lid) {
				$obusy_record = new stdClass;
				$obusy_record->idorder = (int)$newoid;
				$obusy_record->idbusy  = (int)$lid;

				// store busy relation record
				$dbo->insertObject('#__vikbooking_ordersbusy', $obusy_record, 'id');
			}

			// write room records
			foreach ($rooms_pool as $rind => $nowroom) {
				$room_indexes_usemap = [];
				for ($r = 1; $r <= $or_forend; $r++) {
					// Assign room specific unit
					$info_room_avail = [
						'id' 	   => $newoid,
						'checkin'  => (!empty($nowroom['checkin_ts']) ? $nowroom['checkin_ts'] : $checkin_ts),
						'checkout' => (!empty($nowroom['checkout_ts']) ? $nowroom['checkout_ts'] : $checkout_ts),
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

					// room custom cost
					$or_cust_cost = $cust_cost > 0.00 ? $cust_cost : 0;
					$or_cust_cost = $or_forend > 1 && $or_cust_cost > 0 ? round(($or_cust_cost / $or_forend), 2) : $or_cust_cost;
					// room cost from website rate plan is always based on one room
					$or_room_cost = $room_cost > 0.00 ? $room_cost : 0;
					if (!empty($split_stay_data) && $cust_cost > 0) {
						// set the average cost per room in case of split stay
						$cost_per_room = ($cust_cost / count($split_stay_data));
						$or_cust_cost = round($cost_per_room, 2);
						if (isset($split_stay_data[$rind]) && isset($split_stay_data[$rind]['nights'])) {
							// count the average cost per room depending on the number of nights of stay
							$cost_per_room = $cust_cost / $daysdiff * $split_stay_data[$rind]['nights'];
							$or_cust_cost = round($cost_per_room, 2);
						}
					}

					// room guests
					$room_adults = isset($adults_map[$r]) && empty($split_stay_data) ? $adults_map[$r] : $adults;
					$room_children = isset($children_map[$r]) && empty($split_stay_data) ? $children_map[$r] : $children;

					// attempt to gather the children age for this room
					$room_children_age = null;
					if ($room_children && $children_age) {
						$children_age_pool = [];
						for ($ic = 0; $ic < $room_children; $ic++) {
							if (!$children_age) {
								$children_age_pool[] = 0;
								continue;
							}
							// shorten the list and push the current child age
							$current_child_age = array_shift($children_age);
							$children_age_pool[] = (int) $current_child_age;
						}
						$room_children_age = json_encode(['age' => $children_age_pool]);
					}

					// store room record
					$room_record = new stdClass;
					$room_record->idorder 	   = (int) $newoid;
					$room_record->idroom 	   = (int) $nowroom['id'];
					$room_record->adults 	   = $room_adults;
					$room_record->children 	   = $room_children;
					$room_record->idtar 	   = !empty($id_tariff) ? $id_tariff : null;
					$room_record->childrenage  = $room_children_age;
					$room_record->t_first_name = $t_first_name;
					$room_record->t_last_name  = $t_last_name;
					$room_record->roomindex    = count($room_indexes) ? (int) $room_indexes[$use_ind_key] : null;
					$room_record->cust_cost    = $cust_cost > 0.00 ? $or_cust_cost : null;
					$room_record->cust_idiva   = $cust_cost > 0.00 && !empty($id_tax) ? $id_tax : null;
					$room_record->room_cost    = $room_cost > 0.00 ? $or_room_cost : null;

					$dbo->insertObject('#__vikbooking_ordersrooms', $room_record, 'id');

					if (!isset($room_record->id)) {
						$this->setError('Could not store room reservation record for booking ID ' . $room_record->idorder);
						continue;
					}

					// Assign room specific unit
					if ($room_indexes) {
						$room_indexes_usemap[$nowroom['id']]++;
					}
				}
			}
		} elseif ($status == 'standby') {
			// store booking record
			$dbo->insertObject('#__vikbooking_orders', $booking, 'id');

			if (!isset($booking->id)) {
				$this->setError('Could not store the reservation record');
				return false;
			}

			// get the newly generated booking ID
			$newoid = $booking->id;

			// set the new booking ID
			$this->setNewBookingID($newoid);

			if (!empty($split_stay_data)) {
				// save transient on db for split stay information
				VBOFactory::getConfig()->set('split_stay_' . $newoid, json_encode($split_stay_data));
			}

			// write room records
			foreach ($rooms_pool as $rind => $nowroom) {
				for ($r = 1; $r <= $or_forend; $r++) {
					// room custom cost
					$or_cust_cost = $cust_cost > 0.00 ? $cust_cost : 0;
					$or_cust_cost = $or_forend > 1 && $or_cust_cost > 0 ? round(($or_cust_cost / $or_forend), 2) : $or_cust_cost;
					// room cost from website rate plan is always based on one room
					$or_room_cost = $room_cost > 0.00 ? $room_cost : 0;
					if (!empty($split_stay_data) && $cust_cost > 0) {
						// set the average cost per room in case of split stay
						$cost_per_room = ($cust_cost / count($split_stay_data));
						$or_cust_cost = round($cost_per_room, 2);
						if (isset($split_stay_data[$rind]) && isset($split_stay_data[$rind]['nights'])) {
							// count the average cost per room depending on the number of nights of stay
							$cost_per_room = $cust_cost / $daysdiff * $split_stay_data[$rind]['nights'];
							$or_cust_cost = round($cost_per_room, 2);
						}
					}

					// room guests
					$room_adults = isset($adults_map[$r]) && empty($split_stay_data) ? $adults_map[$r] : $adults;
					$room_children = isset($children_map[$r]) && empty($split_stay_data) ? $children_map[$r] : $children;

					// attempt to gather the children age for this room
					$room_children_age = null;
					if ($room_children && $children_age) {
						$children_age_pool = [];
						for ($ic = 0; $ic < $room_children; $ic++) {
							if (!$children_age) {
								$children_age_pool[] = 0;
								continue;
							}
							// shorten the list and push the current child age
							$current_child_age = array_shift($children_age);
							$children_age_pool[] = (int) $current_child_age;
						}
						$room_children_age = json_encode(['age' => $children_age_pool]);
					}

					// store room record
					$room_record = new stdClass;
					$room_record->idorder 	   = (int) $newoid;
					$room_record->idroom 	   = (int) $nowroom['id'];
					$room_record->adults 	   = $room_adults;
					$room_record->children 	   = $room_children;
					$room_record->idtar 	   = !empty($id_tariff) ? $id_tariff : null;
					$room_record->childrenage  = $room_children_age;
					$room_record->t_first_name = $t_first_name;
					$room_record->t_last_name  = $t_last_name;
					$room_record->cust_cost    = $cust_cost > 0.00 ? $or_cust_cost : null;
					$room_record->cust_idiva   = $cust_cost > 0.00 && !empty($id_tax) ? $id_tax : null;
					$room_record->room_cost    = $room_cost > 0.00 ? $or_room_cost : null;

					$dbo->insertObject('#__vikbooking_ordersrooms', $room_record, 'id');

					if (!isset($room_record->id)) {
						$this->setError('Could not store room reservation record for booking ID ' . $room_record->idorder);
						continue;
					}

					if (empty($split_stay_data)) {
						// lock room for pending status
						$tmplock_record = new stdClass;
						$tmplock_record->idroom   = (int)$room['id'];
						$tmplock_record->checkin  = $checkin_ts;
						$tmplock_record->checkout = $checkout_ts;
						$tmplock_record->until 	  = VikBooking::getMinutesLock(true);
						$tmplock_record->realback = $realback_ts;
						$tmplock_record->idorder  = (int)$newoid;

						$dbo->insertObject('#__vikbooking_tmplock', $tmplock_record, 'id');
					}
				}
			}

			if (!empty($split_stay_data)) {
				// lock rooms for pending status on proper stay dates
				foreach ($split_stay_data as $split_stay) {
					$tmplock_record = new stdClass;
					$tmplock_record->idroom   = (int)$split_stay['idroom'];
					$tmplock_record->checkin  = (int)$split_stay['checkin_ts'];
					$tmplock_record->checkout = (int)$split_stay['checkout_ts'];
					$tmplock_record->until 	  = VikBooking::getMinutesLock(true);
					$tmplock_record->realback = (int)$split_stay['realback_ts'];
					$tmplock_record->idorder  = (int)$newoid;

					$dbo->insertObject('#__vikbooking_tmplock', $tmplock_record, 'id');
				}
			}
		}

		$newoid = $this->getNewBookingID();
		if (!$newoid) {
			return false;
		}

		// assign booking to customer
		if (!$cpin->getNewCustomerId() && !empty($customer_id)) {
			$cpin->setNewPin($customer_pin);
			$cpin->setNewCustomerId($customer_id);
		}
		$cpin->saveCustomerBooking($newoid);

		// Booking History
		$history_obj = VikBooking::getBookingHistoryInstance($newoid);
		$forced_reason = !empty($forced_reason) ? " {$forced_reason}" : $forced_reason;
		$caller_id = $now_user->name ? "({$now_user->name})" : '';
		if ($this->getCaller()) {
			$caller_id = '(' . $this->getCaller() . ')';
			if ($this->getHistoryData()) {
				$history_obj->setExtraData($this->getHistoryData());
			}
		}
		$history_obj->store('NB', trim($caller_id . $forced_reason));

		if ($status == 'confirmed' || ($status == 'standby' && class_exists('VCMRequestAvailability'))) {
			// Invoke Channel Manager
			$vcm_autosync = VikBooking::vcmAutoUpdate();
			if ($vcm_autosync > 0) {
				$vcm_obj = VikBooking::getVcmInvoker();
				$vcm_obj->setOids([$newoid])->setSyncType('new');
				$sync_result = $vcm_obj->doSync();
				if ($sync_result === false) {
					// set error message
					$vcm_err = $vcm_obj->getError();
					$this->setError(JText::translate('VBCHANNELMANAGERRESULTKO') . (!empty($vcm_err) ? ' - ' . $vcm_err : ''));

					// return true because the booking was actually stored
					return true;
				}
			} elseif (is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'synch.vikbooking.php')) {
				// set the necessary action to invoke VCM
				$vcm_sync_url = 'index.php?option=com_vikbooking&task=invoke_vcm&stype=new&cid[]=' . $newoid . '&returl=' . urlencode('index.php?option=com_vikbooking&task=calendar&cid[]=' . $room['id']);

				$this->setChannelManagerAction(JText::translate('VBCHANNELMANAGERINVOKEASK') . ' <button type="button" class="btn btn-primary" onclick="document.location.href=\'' . $vcm_sync_url . '\';">' . JText::translate('VBCHANNELMANAGERSENDRQ') . '</button>');
			}
		}

		if (VikBooking::isAdmin()) {
			/**
			 * Trigger event to allow third party plugins to intercept the admin new booking event.
			 * 
			 * @since 	1.16.8 (J) - 1.6.8 (WP)
			 */
			VBOFactory::getPlatform()->getDispatcher()->trigger('onAfterCreateNewBookingAdmin', [$newoid]);
		}

		return true;
	}

	/**
	 * Attempts to find the default payment method to be assigned to a booking.
	 * 
	 * @param 	bool 	$auto 	If true, it was requested to automatically find the best payment method.
	 * 
	 * @return 	string 			The payment method string for the reservation "ID=Name", or an empty string.
	 * 
	 * @since 	1.17.3 (J) - 1.7.3 (WP)
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
