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
		return (array)$this->get('_customer', []);
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
		return (array)$this->get('_room', []);
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
		return (int)$this->get('_newBookingID', 0);
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
		$av_helper = VikBooking::getAvailabilityInstance();

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
	 * Tells whether the operation is authenticated. By default this
	 * model is only available in the administrator section of the site.
	 * 
	 * @return 	bool
	 */
	protected function canCreate()
	{
		if (!JFactory::getApplication()->isClient('administrator') && !$this->get('_isAdministrator')) {
			return false;
		}

		return true;
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
		$all_rooms = VikBooking::getAvailabilityInstance()->loadRooms();

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

		$av_helper 	 = VikBooking::getAvailabilityInstance();
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
		$all_rooms = VikBooking::getAvailabilityInstance()->loadRooms();

		if (empty($split_stay_data)) {
			// make sure the rooms are available
			$check_units = $room['units'];
			if ($num_rooms > 1 && $num_rooms <= $room['units'] && !$set_closed) {
				// only when non closing the room we check the availability for the units requested for booking
				$check_units = $room['units'] - $num_rooms + 1;
			}
			$room_available = VikBooking::roomBookable($room['id'], $check_units, $this->get('checkin', 0), $this->get('checkout', 0));
		} else {
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

		$forced_reason = '';
		$all_rooms = VikBooking::getAvailabilityInstance()->loadRooms();

		if (empty($split_stay_data)) {
			// eventually build string for the description of the history event
			if (($force_booking || $set_closed) && !$room_available) {
				$forced_reason = JText::translate('VBO_FORCED_BOOKDATES');
			}
		} else {
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

			// apply taxes, if necessary
			if ($id_tax) {
				$q = "SELECT `i`.`aliq`,`i`.`taxcap` FROM `#__vikbooking_iva` AS `i` WHERE `i`.`id`=" . $id_tax . ";";
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
		$daysdiff 	  = (int)$this->get('nights', 1);
		$num_rooms 	  = (int)$this->get('num_rooms', 1);
		$adults 	  = (int)$this->get('adults', 1);
		$children 	  = (int)$this->get('children', 0);
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
		$set_total 		= (float)$this->get('_total', 0);
		$set_taxes 		= (float)$this->get('_total_tax', 0);

		// stay dates
		$now_ts 	 = time();
		$checkin_ts  = $this->get('checkin');
		$checkout_ts = $this->get('checkout');
		$realback_ts = $this->get('checkout_real', $checkout_ts);

		// room
		$inj_room  = $this->getRoom();
		$cust_cost = !empty($inj_room['cust_cost']) ? (float)$inj_room['cust_cost'] : 0;
		$room_cost = !empty($inj_room['room_cost']) ? (float)$inj_room['room_cost'] : 0;
		$id_price  = !empty($inj_room['id_price']) ? (int)$inj_room['id_price'] : 0;
		$id_tax    = !empty($inj_room['id_tax']) ? (int)$inj_room['id_tax'] : 0;
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

					// store room record
					$room_record = new stdClass;
					$room_record->idorder 	   = (int)$newoid;
					$room_record->idroom 	   = (int)$nowroom['id'];
					$room_record->adults 	   = $room_adults;
					$room_record->children 	   = $room_children;
					$room_record->idtar 	   = !empty($id_tariff) ? $id_tariff : null;
					$room_record->t_first_name = $t_first_name;
					$room_record->t_last_name  = $t_last_name;
					$room_record->roomindex    = count($room_indexes) ? (int)$room_indexes[$use_ind_key] : null;
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

					// store room record
					$room_record = new stdClass;
					$room_record->idorder 	   = (int)$newoid;
					$room_record->idroom 	   = (int)$nowroom['id'];
					$room_record->adults 	   = $room_adults;
					$room_record->children 	   = $room_children;
					$room_record->idtar 	   = !empty($id_tariff) ? $id_tariff : null;
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
		$forced_reason = !empty($forced_reason) ? " {$forced_reason}" : $forced_reason;
		VikBooking::getBookingHistoryInstance()->setBid($newoid)->store('NB', "({$now_user->name}){$forced_reason}");

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
}
