<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2022 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Availability handler class for Vik Booking.
 * Also used to handle website inquiry reservations.
 *
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VikBookingAvailability
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikBookingAvailability
	 */
	protected static $instance = null;

	/**
	 * An array containing the stay dates.
	 *
	 * @var array
	 */
	protected $stay_dates = [];

	/**
	 * An array containing the stay date timestamps.
	 *
	 * @var array
	 */
	protected $stay_ts = [];

	/**
	 * An array containing the room parties.
	 *
	 * @var array
	 */
	protected $room_parties = [];

	/**
	 * The total number of days to go "back and forth".
	 * 
	 * @var int
	 */
	protected $back_and_forth = 14;

	/**
	 * A list of the room ids to be checked.
	 *
	 * @var array
	 */
	protected $room_ids = [];

	/**
	 * Whether to ignore restrictions.
	 *
	 * @var bool
	 */
	protected $ignore_restrictions = false;

	/**
	 * Whether check-ins on check-outs are allowed.
	 * 
	 * @var bool
	 */
	protected $inonout_allowed = true;

	/**
	 * The percent ratio for nights/transfers in split stays.
	 * 
	 * @var int
	 */
	protected $nights_transfers_ratio = 100;

	/**
	 * Whether we need to behave for the front-end booking process.
	 * 
	 * @var bool
	 */
	protected $is_front_booking = false;

	/**
	 * The warning string occurred.
	 *
	 * @var string
	 */
	protected $warning = '';

	/**
	 * The error string occurred.
	 *
	 * @var string
	 */
	protected $error = '';

	/**
	 * The last error code occurred in TACVBO.
	 *
	 * @var int
	 */
	protected $errorCode = 0;

	/**
	 * A list of fully booked room ids.
	 *
	 * @var array
	 */
	protected $fully_booked = [];

	/**
	 * Associative list of all rooms.
	 *
	 * @var array
	 */
	protected $all_rooms = [];

	/**
	 * Associative list of all rate plans.
	 *
	 * @var array
	 */
	protected $all_rplans = [];

	/**
	 * Map of min/max LOS tariffs defined per room.
	 *
	 * @var array
	 */
	protected $min_max_los_tariffs_map = [];

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		// load dependencies
		if (!class_exists('TACVBO')) {
			require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'tac.vikbooking.php';
		}
	}

	/**
	 * Returns the global object, either
	 * a new instance or the existing instance
	 * if the class was already instantiated.
	 *
	 * @return 	self 	A new instance of the class.
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Counts the total number of nights of stay according to the stay dates.
	 * 
	 * @param 	int 	$from_ts 	optional check-in timestamp.
	 * @param 	int 	$to_ts 		optional check-out timestamp.
	 * 
	 * @return 	int 	the total number of nights of stay.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP) added args to make this an helper method.
	 */
	public function countNightsOfStay($from_ts = null, $to_ts = null)
	{
		if (!count($this->stay_ts) && (empty($from_ts) || empty($to_ts))) {
			return 1;
		}

		if (!empty($from_ts) && !empty($to_ts)) {
			$use_from = $from_ts;
			$use_to   = $to_ts;
		} else {
			$use_from = $this->stay_ts[0];
			$use_to   = $this->stay_ts[1];
		}

		$secdiff = $use_to - $use_from;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			$daysdiff = $daysdiff < 1 ? 1 : $daysdiff;
		} else {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			} else {
				$sum = floor($daysdiff) * 86400;
				$newdiff = $secdiff - $sum;
				$maxhmore = VikBooking::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}

		return $daysdiff;
	}

	/**
	 * Explains the error code occurred or passed by using translation strings.
	 * 
	 * @param 	int 	$force_code 	optional error code to explain.
	 * 
	 * @return 	string 					the explanation of the error, or an empty string.
	 */
	public function explainErrorCode($force_code = 0)
	{
		// the error code to parse
		$use_ecode = $force_code ? $force_code : $this->errorCode;

		if (empty($use_ecode)) {
			return '';
		}

		/**
		 * Error code identifier:
		 * 
		 * 1 = missing/invalid request options.
		 * 2 = invalid authentication.
		 * 3 = no rooms found for the given party.
		 * 4 = not compliant with booking restrictions.
		 * 5 = not compliant with global closing dates.
		 * 6 = no rates defined for the given length of stay.
		 * 7 = no availability for the dates requested.
		 * 8 = no rooms available due to restrictions at room or rate plan level.
		 */

		switch ($use_ecode) {
			case 1:
				return 'Missing or invalid request options.';
			case 2:
				return 'Invalid request authentication.';
			case 3:
				return JText::translate('VBO_AV_ECODE_3');
			case 4:
				return 'Not compliant with the booking restrictions.';
			case 5:
				return 'Not compliant with the global closing dates.';
			case 6:
				return 'No rates defined for the given length of stay.';
			case 7:
				return JText::translate('VBO_AV_ECODE_7');
			case 8:
				return 'No rooms available due to room or rate plan restrictions.';
			default:
				return 'Unknown error code.';
		}
		
	}

	/**
	 * Loads all rooms in VBO and maps them into an associative array.
	 * 
	 * @return 	array 	the associative list of rooms.
	 */
	public function loadRooms()
	{
		if ($this->all_rooms) {
			// return previously cached array if available
			return $this->all_rooms;
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `id`, `name`, `img`, `idcat`, `avail`, `units`, `fromadult`, `toadult`, 
			`fromchild`, `tochild`, `totpeople`, `mintotpeople` FROM `#__vikbooking_rooms` 
			ORDER BY `avail` DESC, `name` ASC;";
		$dbo->setQuery($q);
		$room_rows = $dbo->loadAssocList();

		if (!$room_rows) {
			return $this->all_rooms;
		}

		if ($this->isFrontBooking()) {
			// apply translations on rooms
			$vbo_tn = VikBooking::getTranslator();
			$vbo_tn->translateContents($room_rows, '#__vikbooking_rooms');
		}

		foreach ($room_rows as $room) {
			$this->all_rooms[$room['id']] = $room;
		}

		return $this->all_rooms;
	}

	/**
	 * Sets the current rooms as an associative array of information. The
	 * array keys represent the room IDs as an associative array of details.
	 * 
	 * @param 	array 	$rooms 	the associatve list of rooms.
	 * 
	 * @return 	self
	 */
	public function setRooms(array $rooms = [])
	{
		$this->all_rooms = $rooms;

		return $this;
	}

	/**
	 * Filters all rooms by keeping just the ones published/available.
	 * 
	 * @return 	array 	associative array of published (available) rooms.
	 */
	public function filterPublishedRooms()
	{
		$rooms = $this->loadRooms();

		foreach ($rooms as $k => $room) {
			if (!$room['avail']) {
				unset($rooms[$k]);
			}
		}

		return $rooms;
	}

	/**
	 * Loads all rate plans in VBO and maps them into an associative array.
	 * 
	 * @return 	array 	the associative list of rate plans.
	 */
	public function loadRatePlans()
	{
		if (count($this->all_rplans)) {
			// return previously cached array if available
			return $this->all_rplans;
		}

		$dbo = JFactory::getDbo();

		$rate_plans = [];

		$q = "SELECT * FROM `#__vikbooking_prices` ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$rplan_rows = $dbo->loadAssocList();

		if ($rplan_rows) {
			foreach ($rplan_rows as $rplan) {
				$rate_plans[$rplan['id']] = $rplan;
			}
			// sort rate plans
			$rate_plans = VikBooking::sortRatePlans($rate_plans, true);
		}

		$this->all_rplans = $rate_plans;

		return $rate_plans;
	}

	/**
	 * Gets the available room rates for the specified dates, party and rooms.
	 * 
	 * @param 	array 	$params 	optional list of options to be forced for TACVBO.
	 * 
	 * @return 	mixed 	boolean false in case of errors or array result of TACVBO class.
	 */
	public function getRates($params = [])
	{
		if (!count($this->stay_dates)) {
			$this->setError('No dates provided');
			return false;
		}

		if (!count($this->room_parties)) {
			$this->setError('No room party provided');
			return false;
		}

		// count injected rooms, if any
		$tot_rooms = count($this->room_ids);

		// build options array for TACVBO
		$options = [
			'start_date' => $this->stay_dates[0],
			'end_date' 	 => $this->stay_dates[1],
			'nights' 	 => $this->countNightsOfStay(),
			'num_rooms'  => ($tot_rooms > 0 ? $tot_rooms : 1),
			'adults' 	 => [$this->getPartyGuests('adults', 0)],
			'children' 	 => [$this->getPartyGuests('children', 0)],
			'only_rates' => 1,
		];

		if ($tot_rooms > 1 && count($this->room_parties) == $tot_rooms) {
			// re-build list of adults and children
			$options['adults'] = [];
			$options['children'] = [];
			for ($i = 0; $i < $tot_rooms; $i++) {
				$options['adults'][] = $this->getPartyGuests('adults', $i);
				$options['children'][] = $this->getPartyGuests('children', $i);
			}
		}

		// merge default options with params, if any
		$options = array_merge($options, $params);

		// invoke TACVBO class by injecting the options
		TACVBO::$getArray = true;
		TACVBO::$ignoreRestrictions = $this->ignore_restrictions;
		$website_rates = TACVBO::tac_av_l($options);

		// store the error code occurred (if any)
		$this->errorCode = TACVBO::getErrorCode();

		if (!is_array($website_rates)) {
			// critical error
			$this->setError(str_replace('e4j.error.', '', $website_rates));
			return false;
		}

		if (isset($website_rates['e4j.error'])) {
			// calculation/availability error
			$this->setError($website_rates['e4j.error']);
			// check if reserved keys like "fullybooked" are present
			if (isset($website_rates['fullybooked']) && is_array($website_rates['fullybooked'])) {
				// store fully booked rooms array
				$this->fully_booked = $website_rates['fullybooked'];
			}
			// always return false
			return false;
		}

		// optional filter by room IDs will be applied on this flow
		$found_rids = array_keys($website_rates);
		$unwanted_rids = $tot_rooms ? array_diff($found_rids, $this->room_ids) : [];
		foreach ($unwanted_rids as $rid) {
			unset($website_rates[$rid]);
		}

		return $website_rates;
	}

	/**
	 * Finds the available suggestions in case of no availability previously occurred
	 * while getting the rates. This method should be called after getRates() so that
	 * a valid errorCode to be analized will be available, unless code is forced.
	 * Suggestions can include closest booking dates and/or different room-guest parties.
	 * 
	 * @param 	int 	$force_code 	the error code to force (no availability or party unsatisfied).
	 * @param 	array 	$force_rooms 	an optional list of room IDs to consider for the suggestions.
	 * 
	 * @return 	array 	array of alternative dates, alternative room-parties and split stays.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP) list of split stays available is also returned.
	 */
	public function findSuggestions($force_code = 0, $force_rooms = [])
	{
		// reset error and warning strings to start a new calculation
		$this->error = '';
		$this->warning = '';

		// build containers for the two types of suggestions
		$alternative_dates 	 = [];
		$alternative_parties = [];
		$split_stay_sols 	 = [];

		// the error code to parse
		$use_ecode = $force_code ? $force_code : $this->errorCode;

		if (empty($use_ecode)) {
			// do not continue if no valid errors previously occurred or forced
			$this->setError('Empty error code');
			return [$alternative_dates, $alternative_parties, $split_stay_sols];
		}

		if ($use_ecode == 7 && (count($this->fully_booked) || count($force_rooms))) {
			// get the closest booking dates for the compatible, yet unavailable, rooms
			$use_rooms = count($force_rooms) ? $force_rooms : $this->fully_booked;
			$alternative_dates = $this->findClosestRoomDateSolutions($use_rooms);
			// calculate the split stay solutions available for the compatible rooms
			$split_stay_sols = $this->findSplitStays($use_rooms);
		}

		if ($use_ecode == 3) {
			// no rooms found for the given party, suggest alternative parties
			$active_rooms = count($force_rooms) ? $force_rooms : array_keys($this->filterPublishedRooms());
			// match all the available rooms in the requested or near dates
			$all_solutions = $this->findClosestRoomDateSolutions($active_rooms);
			// sort solutions by bigger rooms
			$all_solutions = $this->sortBiggerRoomSolutions($all_solutions);
			// find matching solutions for the requested party
			$alternative_parties = $this->matchSolutionsParty($all_solutions);
		}

		return [$alternative_dates, $alternative_parties, $split_stay_sols];
	}

	/**
	 * Given a list of unavailable room IDs, yet compatible with the party and LOS requested,
	 * we build a list of available solutions for booking split stays on the same dates. The
	 * visibility should be public so that other Views could use just this method.
	 * 
	 * @param 	array 	$room_ids 	list of unavailable, yet compatible, room IDs.
	 * @param 	array 	$busy_list 	optional list of busy records for the involved dates.
	 * 
	 * @return 	array 				associative list of available split stays, or empty array.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function findSplitStays(array $room_ids = [], array $busy_list = null)
	{
		if (!$room_ids) {
			return [];
		}

		// get all website rooms
		$all_rooms = $this->loadRooms();

		// validate max occupancy for the given rooms
		if (count($this->room_parties) === 1) {
			// we only use the first party for the occupancy validation
			$party_adults 	= $this->getPartyGuests('adults', $party = 0);
			$party_children = $this->getPartyGuests('children', $party = 0);
			foreach ($room_ids as $rindex => $rid) {
				if (!isset($all_rooms[$rid])) {
					unset($room_ids[$rindex]);
					continue;
				}
				if ($party_adults > $all_rooms[$rid]['toadult'] || $party_children > $all_rooms[$rid]['tochild'] || ($party_adults + $party_children) > $all_rooms[$rid]['totpeople']) {
					// max occupancy not met
					unset($room_ids[$rindex]);
					continue;
				}
			}

			if (!$room_ids) {
				return [];
			}

			// reset array keys
			$room_ids = array_values($room_ids);
		}

		// get original check-in and check-out timestamps
		list($orig_checkin_ts, $orig_checkout_ts) = $this->getStayDates(true);
		$info_from = getdate($orig_checkin_ts);
		$info_to   = getdate($orig_checkout_ts);

		// the final check-out date
		$final_checkout_ymd = date('Y-m-d', $orig_checkout_ts);

		// count original length of stay and nights involved
		$tot_nights = $this->countNightsOfStay();
		$groupdays  = VikBooking::getGroupDays($orig_checkin_ts, $orig_checkout_ts, $tot_nights);

		if ($tot_nights < 2) {
			// useless to waste time on finding a split stay if not at least 2 nights
			return [];
		}

		// load the occupied records for these dates and rooms
		$busy_records = !is_null($busy_list) ? $busy_list : VikBooking::loadBusyRecords($room_ids, $orig_checkin_ts, strtotime('+1 day', $orig_checkout_ts));

		// calculate available rooms for each night
		$avroom_nights = [];
		foreach ($room_ids as $rid) {
			if (!isset($all_rooms[$rid])) {
				continue;
			}
			$room = $all_rooms[$rid];
			foreach ($groupdays as $gday) {
				$day_key = date('Y-m-d', $gday);
				$bfound = 0;
				if (!isset($busy_records[$rid])) {
					$busy_records[$rid] = [];
				}
				foreach ($busy_records[$rid] as $bu) {
					$busy_info_in = getdate($bu['checkin']);
					$busy_info_out = getdate($bu['checkout']);
					$busy_in_ts = mktime(0, 0, 0, $busy_info_in['mon'], $busy_info_in['mday'], $busy_info_in['year']);
					$busy_out_ts = mktime(0, 0, 0, $busy_info_out['mon'], $busy_info_out['mday'], $busy_info_out['year']);
					if ($gday >= $busy_in_ts && $gday == $busy_out_ts && !$this->inonout_allowed && $room['units'] < 2) {
						// check-ins on check-outs not allowed
						$bfound++;
						if ($bfound >= $room['units']) {
							break;
						}
					}
					if ($gday >= $busy_in_ts && $gday < $busy_out_ts) {
						$bfound++;
						if ($bfound >= $room['units']) {
							break;
						}
					}
				}
				if ($bfound < $room['units']) {
					// push this night as available for this room
					if (!isset($avroom_nights[$rid])) {
						$avroom_nights[$rid] = [];
					}
					$avroom_nights[$rid][] = $day_key;
				} else {
					// room not available on this night, make sure to unset any previous value
					if (isset($avroom_nights[$rid]) && in_array($day_key, $avroom_nights[$rid])) {
						$unav_key = array_search($day_key, $avroom_nights[$rid]);
						unset($avroom_nights[$rid][$unav_key]);
						$avroom_nights[$rid] = array_values($avroom_nights[$rid]);
					}
				}
			}
		}

		if (!count($avroom_nights)) {
			// no rooms available at all, there's no way to do anything
			return [];
		}

		// make sure all nights requested can be satisfied by at least one room
		foreach ($groupdays as $gday) {
			$day_key = date('Y-m-d', $gday);
			$day_av  = false;
			foreach ($avroom_nights as $rid => $av_nights) {
				if (in_array($day_key, $av_nights)) {
					// night was found
					$day_av = true;
					break;
				}
			}
			if (!$day_av) {
				// this night is not available in any room, unable to proceed
				return [];
			}
		}

		// count the number of consecutive nights per room
		$cons_room_nights = [];
		$tot_gdays = count($groupdays);
		foreach ($groupdays as $k => $gday) {
			$day_key = date('Y-m-d', $gday);
			if (!isset($cons_room_nights[$day_key])) {
				$cons_room_nights[$day_key] = [];
			}
			foreach ($avroom_nights as $rid => $av_nights) {
				if (in_array($day_key, $av_nights)) {
					if (!isset($cons_room_nights[$day_key][$rid])) {
						$cons_room_nights[$day_key][$rid] = [];
					}
					// count the next consecutive nights of stay
					$cons_room_nights[$day_key][$rid][] = $day_key;
					for ($j = ($k + 1); $j < $tot_gdays; $j++) {
						$next_day_key = date('Y-m-d', $groupdays[$j]);
						if (in_array($next_day_key, $av_nights)) {
							$cons_room_nights[$day_key][$rid][] = $next_day_key;
						} else {
							break;
						}
					}
				}
			}
		}

		// sort the solutions with the highest number of consecutive nights to reduce the splits
		$cons_room_nights_sorted = [];
		foreach ($cons_room_nights as $day_key => $cons_nights) {
			$cons_room_nights_cnt = [];
			foreach ($cons_nights as $rid => $cons_dates) {
				$cons_room_nights_cnt[$rid] = count($cons_dates);
			}
			// sort the array in a descending order
			arsort($cons_room_nights_cnt);
			// restore sorted values in cloned array
			$cons_room_nights_sorted[$day_key] = [];
			foreach ($cons_room_nights_cnt as $rid => $tot_cons_nights) {
				$cons_room_nights_sorted[$day_key][$rid] = $cons_room_nights[$day_key][$rid];
			}
		}
		$cons_room_nights = $cons_room_nights_sorted;

		// validate the data just built
		$first_day_key = date('Y-m-d', $groupdays[0]);
		if (!isset($cons_room_nights[$first_day_key]) || !count($cons_room_nights[$first_day_key]) || count($cons_room_nights) != count($groupdays)) {
			// unable to proceed
			return [];
		}

		// remove the consecutive nights from the check-out date as this won't be a night of stay
		unset($cons_room_nights[$final_checkout_ymd]);

		// build the split stay solutions
		$split_stay_sols = [];

		// the number of rooms available on the first night should determine the number of split stay solutions
		foreach ($cons_room_nights[$first_day_key] as $start_rid => $cons_nights) {
			// start container of the various splits for this stay
			$split_stay_sol = [];

			// calculate last consecutive night available
			$leave_date = end($cons_nights);
			$leave_date_info = getdate(strtotime($leave_date));

			// set the check-out date to the day after the last night
			$checkout_ymd = date('Y-m-d', mktime(0, 0, 0, $leave_date_info['mon'], ($leave_date_info['mday'] + 1), $leave_date_info['year']));

			// define the first stay
			$split_stay = [
				'idroom' 	=> $start_rid,
				'room_name' => $all_rooms[$start_rid]['name'],
				'checkin'  	=> $cons_nights[0],
				'checkout' 	=> $checkout_ymd,
				'nights' 	=> $this->countNightsOfStay(strtotime($cons_nights[0]), strtotime($checkout_ymd)),
			];

			// make sure this room has got a tariff defined for this number of nights of stay
			if (!$this->roomNightsAllowed($start_rid, $split_stay['nights'])) {
				// no tariffs found for this los
				continue;
			}

			// push first stay
			$split_stay_sol[] = $split_stay;

			// loop through the next stays
			while (isset($cons_room_nights[$checkout_ymd])) {
				/**
				 * For the next splits, we use just the first available rooms, which is
				 * the one with the highest number of consecutive nights available.
				 */
				foreach ($cons_room_nights[$checkout_ymd] as $rid => $split_cons_nights) {
					// calculate last consecutive night available
					$leave_date = end($split_cons_nights);
					$leave_date_info = getdate(strtotime($leave_date));

					// set the check-out date to the day after the last night
					$checkout_ymd = date('Y-m-d', mktime(0, 0, 0, $leave_date_info['mon'], ($leave_date_info['mday'] + 1), $leave_date_info['year']));
					if ($leave_date == $final_checkout_ymd) {
						// check-out date reached
						$checkout_ymd = $final_checkout_ymd;
					}

					// count nights of stay
					$split_nights = $this->countNightsOfStay(strtotime($split_cons_nights[0]), strtotime($checkout_ymd));

					// make sure this room has got a tariff defined for this number of nights of stay
					if (!$this->roomNightsAllowed($rid, $split_nights)) {
						// no tariffs found for this los, abort solution
						$split_stay_sol = [];
						break 2;
					}

					// push split stay
					$split_stay_sol[] = [
						'idroom' 	=> $rid,
						'room_name' => $all_rooms[$rid]['name'],
						'checkin'  	=> $split_cons_nights[0],
						'checkout' 	=> $checkout_ymd,
						'nights' 	=> $split_nights,
					];

					// we try to reduce the number of splits by considering just the first room
					break;
				}
			}

			if (count($split_stay_sol) < 2) {
				// not a split stay, but rather a fully available room
				continue;
			}

			// push split stay solution
			$split_stay_sols[] = $split_stay_sol;
		}

		/**
		 * Load rooms involved in all split stays in order to validate
		 * global/room-level restrictions and closing dates on the stay.
		 */
		$rooms_involved = [];
		foreach ($split_stay_sols as $split_stay_sol) {
			foreach ($split_stay_sol as $split_stay) {
				if (!in_array($split_stay['idroom'], $rooms_involved)) {
					$rooms_involved[] = $split_stay['idroom'];
				}
			}
		}

		// load restrictions for all rooms involved
		$all_restrictions   = VikBooking::loadRestrictions(true, $rooms_involved);
		$glob_restrictions  = VikBooking::globalRestrictions($all_restrictions);
		$invalid_room_restr = [];

		// validate global restrictions
		if (VikBooking::validateRoomRestriction($glob_restrictions, $info_from, $info_to, $tot_nights)) {
			// global restrictions apply over this stay
			return [];
		}

		// validate closing dates
		if (VikBooking::validateClosingDates($orig_checkin_ts, $orig_checkout_ts)) {
			// global closing dates apply over this stay
			return [];
		}

		// validate restrictions at room level
		foreach ($rooms_involved as $rid) {
			// load restrictions at room level
			$room_level_restr = VikBooking::roomRestrictions($rid, $all_restrictions);
			if (VikBooking::validateRoomRestriction($room_level_restr, $info_from, $info_to, $tot_nights)) {
				// room-level restrictions apply over this stay
				$invalid_room_restr[] = $rid;
			}
		}

		// unset the split stays with the restricted rooms (if any)
		$altered_sols = false;
		foreach ($invalid_room_restr as $rid) {
			foreach ($split_stay_sols as $k => $split_stay_sol) {
				foreach ($split_stay_sol as $split_stay) {
					if ($rid == $split_stay['idroom']) {
						// this booking split stay cannot be suggested because of this room
						unset($split_stay_sols[$k]);
						$altered_sols = true;
						continue 2;
					}
				}
			}
		}

		// apply nights/transfers ratio limit (unless disabled)
		$nights_transfers_ratio = $this->getNightsTransfersRatio();
		if ($nights_transfers_ratio > 0 && $nights_transfers_ratio < 100) {
			// count and apply limits
			foreach ($split_stay_sols as $k => $split_stay_sol) {
				// count nights and transfers
				$split_stay_transfers = count($split_stay_sol) - 1;
				$split_stay_nights 	  = 0;
				foreach ($split_stay_sol as $split_stay_room) {
					$split_stay_nights += $split_stay_room['nights'];
				}
				// max allowed transfers
				$max_transfers = round($split_stay_nights * $nights_transfers_ratio / 100, 0);
				if (!$split_stay_transfers || $split_stay_transfers > $max_transfers) {
					// unset solution
					unset($split_stay_sols[$k]);
					$altered_sols = true;
				}
			}
		}

		if ($altered_sols && count($split_stay_sols)) {
			// restore the array keys
			$split_stay_sols = array_values($split_stay_sols);
		}

		// return the available booking split stay solutions (if any)
		return $split_stay_sols;
	}

	/**
	 * Returns the number of guests requested from the given room-party index.
	 * 
	 * @param 	string 	$guest 	either "adults", "children" or "guests".
	 * @param 	int 	$party 	the party index number, 0 by default.
	 * 
	 * @return 	int 			the total number of guests requested in the party.
	 */
	protected function getPartyGuests($guest = 'adults', $party = 0)
	{
		if (!isset($this->room_parties[$party])) {
			return 0;
		}

		if (!strcasecmp($guest, 'adults')) {
			// adults
			return $this->room_parties[$party]['adults'];
		}

		if (!strcasecmp($guest, 'children')) {
			// children
			return $this->room_parties[$party]['children'];
		}

		// total guests
		$tot_guests = 0;
		foreach ($this->room_parties as $rparty) {
			$tot_guests += $rparty['adults'];
			$tot_guests += $rparty['children'];
		}

		return $tot_guests;
	}

	/**
	 * Given a list of unavailable room IDs, yet compatible with the party and LOS requested,
	 * we build a list of available dates when such rooms could be booked for the same LOS.
	 * 
	 * @param 	array 	$room_ids 	list of unavailable, yet compatible, room IDs.
	 * 
	 * @return 	array 				associative list of available room-dates, or empty array.
	 */
	protected function findClosestRoomDateSolutions($room_ids = [])
	{
		if (!$room_ids) {
			return [];
		}

		// get all website rooms
		$all_rooms = $this->loadRooms();

		// get original check-in and check-out timestamps
		list($orig_checkin_ts, $orig_checkout_ts) = $this->getStayDates(true);
		$info_from = getdate($orig_checkin_ts);
		$info_to   = getdate($orig_checkout_ts);

		// count original length of stay
		$tot_nights = $this->countNightsOfStay();

		// earliest checkin timestamp allowed
		$lim_past_ts = mktime(0, 0, 0, date('n'), ((int)date('j') + VikBooking::getMinDaysAdvance()), date('Y'));

		// suggested range of dates (+/- "back and forth" days from original dates)
		$sug_from_ts = mktime($info_from['hours'], $info_from['minutes'], $info_from['seconds'], $info_from['mon'], ($info_from['mday'] - $this->back_and_forth), $info_from['year']);
		if ($sug_from_ts < $lim_past_ts) {
			$sug_from_ts = $lim_past_ts;
			// since we are close to the requested check-in, double up the "back and forth" for the max date
			$this->setBackForthDays($this->getBackForthDays() * 2);
		}
		$sug_to_ts = mktime($info_to['hours'], $info_to['minutes'], $info_to['seconds'], $info_to['mon'], ($info_to['mday'] + $this->back_and_forth), $info_to['year']);
		$sug_to_ts = $sug_to_ts < $sug_from_ts ? $sug_from_ts : $sug_to_ts;

		// get days timestamps for suggestions
		$groupdays = [];
		$sug_start_info = getdate($sug_from_ts);
		$sug_from_midnight = mktime(0, 0, 0, $sug_start_info['mon'], $sug_start_info['mday'], $sug_start_info['year']);
		$sug_start_info = getdate($sug_from_midnight);
		while ($sug_start_info[0] <= $sug_to_ts) {
			array_push($groupdays, $sug_start_info[0]);
			$sug_start_info = getdate(mktime(0, 0, 0, $sug_start_info['mon'], ($sug_start_info['mday'] + 1), $sug_start_info['year']));
		}

		// build suggestions array of dates with some availability for the given rooms
		$suggestions = [];
		$busy_records = VikBooking::loadBusyRecords($room_ids, $sug_from_ts, strtotime('+1 day', $sug_to_ts));
		foreach ($room_ids as $rid) {
			if (!isset($all_rooms[$rid])) {
				continue;
			}
			$room = $all_rooms[$rid];
			foreach ($groupdays as $gday) {
				$day_key = date('Y-m-d', $gday);
				$bfound = 0;
				if (!isset($busy_records[$rid])) {
					$busy_records[$rid] = [];
				}
				foreach ($busy_records[$rid] as $bu) {
					$busy_info_in = getdate($bu['checkin']);
					$busy_info_out = getdate($bu['checkout']);
					$busy_in_ts = mktime(0, 0, 0, $busy_info_in['mon'], $busy_info_in['mday'], $busy_info_in['year']);
					$busy_out_ts = mktime(0, 0, 0, $busy_info_out['mon'], $busy_info_out['mday'], $busy_info_out['year']);
					if ($gday >= $busy_in_ts && $gday == $busy_out_ts && !$this->inonout_allowed && $room['units'] < 2) {
						// check-ins on check-outs not allowed
						$bfound++;
						if ($bfound >= $room['units']) {
							break;
						}
					}
					if ($gday >= $busy_in_ts && $gday < $busy_out_ts) {
						$bfound++;
						if ($bfound >= $room['units']) {
							break;
						}
					}
				}
				if ($bfound < $room['units']) {
					if (!isset($suggestions[$day_key])) {
						$suggestions[$day_key] = [];
					}
					$room_day = $room;
					$room_day['units_left'] = $room['units'] - $bfound;
					$suggestions[$day_key] = $suggestions[$day_key] + [$rid => $room_day];
				}
			}
		}

		if (!$suggestions) {
			// no available nights found for the prior and next "back and forth" days for the given rooms
			return [];
		}

		// build the solutions array with keys=checkin, values=all rooms suited for the requested number of nights
		$solutions = [];
		// get all rooms available for the number of nights requested in the suggestions array of dates
		foreach ($suggestions as $kday => $rooms) {
			$day_ts_info = getdate(strtotime($kday));
			foreach ($rooms as $rid => $room) {
				$suitable = true;
				$room_days_av_left = [$kday => $room['units_left']];
				for ($i = 1; $i < $tot_nights; $i++) {
					$next_night = mktime(0, 0, 0, $day_ts_info['mon'], ($day_ts_info['mday'] + $i), $day_ts_info['year']);
					$next_night_dt = date('Y-m-d', $next_night);
					if (!isset($suggestions[$next_night_dt]) || !isset($suggestions[$next_night_dt][$rid])) {
						$suitable = false;
						break;
					}
					$room_days_av_left[$next_night_dt] = $suggestions[$next_night_dt][$rid]['units_left'];
				}
				if ($suitable === true) {
					if (!isset($solutions[$kday])) {
						$solutions[$kday] = [];
					}
					unset($room['units_left']);
					$room['days_av_left'] = $room_days_av_left;
					$solutions[$kday] = $solutions[$kday] + [$rid => $room];
				}
			}
		}

		if (!count($solutions)) {
			// the requested length of stay could not be satisfied for any available night
			return [];
		}

		// sort the solutions by the closest checkin date to the one requested
		$sortmap = [];
		$orig_checkin_ymd = date('Y-m-d', $orig_checkin_ts);
		foreach ($solutions as $kday => $solution) {
			$kdayts = strtotime($kday);
			$sortmap[$kdayts] = $kdayts > $orig_checkin_ts ? ($kdayts - $orig_checkin_ts) : ($orig_checkin_ts - $kdayts);
			if ($orig_checkin_ymd == $kday) {
				// the original check-in day is available, so we want it to be first, regardless of the check-in time
				$sortmap[$kdayts] = 1;
			}
		}
		asort($sortmap);
		$sorted = [];
		foreach ($sortmap as $kdayts => $v) {
			$kday = date('Y-m-d', $kdayts);
			$sorted[$kday] = $solutions[$kday];
		}
		$solutions = $sorted;
		unset($sorted);

		/**
		 * Load rooms involved in the final alternative solutions in order
		 * to validate global/room-level restrictions and closing dates.
		 * 
		 * @since 	1.15.4 (J) - 1.5.10 (WP)
		 */
		$rooms_involved = [];
		foreach ($solutions as $arrive_ymd => $roomsol) {
			foreach (array_keys($roomsol) as $rid) {
				if (!in_array($rid, $rooms_involved)) {
					$rooms_involved[] = $rid;
				}
			}
		}

		// load restrictions for all rooms involved
		$all_restrictions  = VikBooking::loadRestrictions(true, $rooms_involved);
		$glob_restrictions = VikBooking::globalRestrictions($all_restrictions);
		$room_level_restr  = [];

		foreach ($solutions as $arrive_ymd => $roomsol) {
			// build suggested stay dates
			$sug_in  = getdate(strtotime($arrive_ymd));
			$sug_out = getdate(mktime(0, 0, 0, $sug_in['mon'], ($sug_in['mday'] + $tot_nights), $sug_in['year']));
			// validate global restrictions
			if (VikBooking::validateRoomRestriction($glob_restrictions, $sug_in, $sug_out, $tot_nights)) {
				// global restrictions apply over this stay
				unset($solutions[$arrive_ymd]);
				continue;
			}
			// validate closing dates
			if (VikBooking::validateClosingDates($sug_in[0], $sug_out[0])) {
				// global closing dates apply over this stay
				unset($solutions[$arrive_ymd]);
				continue;
			}
			// validate restrictions at room level
			foreach ($roomsol as $rid => $rdata) {
				if (!isset($room_level_restr[$rid])) {
					// load restrictions at room level
					$room_level_restr[$rid] = VikBooking::roomRestrictions($rid, $all_restrictions);
				}
				if (VikBooking::validateRoomRestriction($room_level_restr[$rid], $sug_in, $sug_out, $tot_nights)) {
					// room-level restrictions apply over this stay
					unset($solutions[$arrive_ymd][$rid]);
					if (!count($solutions[$arrive_ymd])) {
						// unset the entire suggested date
						unset($solutions[$arrive_ymd]);
						break;
					}
					continue;
				}
			}
		}

		if (!count($solutions)) {
			// the calculated suggestions do not meet the restrictions or the closing dates
			return [];
		}

		// return the solution alternative dates for all rooms available
		return $solutions;
	}

	/**
	 * Sorts an associative array of room-solutions by the bigger rooms.
	 * 
	 * @param 	array 	$solutions 	the date solutions obtained for some rooms.
	 * 
	 * @return 	array 				the same array sorted by bigger rooms on top.
	 */
	protected function sortBiggerRoomSolutions($solutions)
	{
		if (!is_array($solutions) || !$solutions) {
			return $solutions;
		}

		// sort rooms-solutions by max-adults, 'max-guests', 'max-children', in a descending order
		foreach ($solutions as $kday => $solution) {
			// with this sorting, we will have the bigger rooms on top to quickly fit the party requested
			uasort($solutions[$kday], function($a, $b) {
				if ($a['toadult'] == $b['toadult']) {
					if ($a['totpeople'] == $b['totpeople']) {
						return $a['tochild'] > $b['tochild'] ? -1 : 1;
					}
					return $a['totpeople'] > $b['totpeople'] ? -1 : 1;
				}
				return $a['toadult'] > $b['toadult'] ? -1 : 1;
			});
		}

		return $solutions;
	}

	/**
	 * Given a list of available dates and rooms (solutions), attempts
	 * to match a party of rooms that fits the party requested.
	 * 
	 * @param 	array 	$solutions 	the list of available dates and related rooms.
	 * 
	 * @return 	array 				list of alternative party solutions, if any.
	 */
	protected function matchSolutionsParty($solutions)
	{
		if (!is_array($solutions) || !$solutions) {
			return [];
		}

		// build the list of alternative parties
		$alternative_parties = [];

		// build list of party guests
		$party_guests = [
			'adults'   => 0,
			'children' => 0,
		];
		foreach ($this->room_parties as $rparty) {
			$party_guests['adults']   += $rparty['adults'];
			$party_guests['children'] += $rparty['children'];
		}

		// check if the rooms of each solution can fit the number of guests requested, unset the solution otherwise
		foreach ($solutions as $kday => $solution) {
			$solution_guests = [
				'adults'   => 0,
				'children' => 0,
			];
			foreach ($solution as $rid => $roomsol) {
				// count minimum units left for this room
				$room_min_uleft = min($roomsol['days_av_left']);
				// check if this solution of rooms can allocate all the guests requested
				if ($roomsol['totpeople'] < ($roomsol['toadult'] + $roomsol['tochild']) && !$party_guests['children']) {
					// in case of no children requested, we ignore them to avoid adjusting the room capacity
					$roomsol['tochild'] = 0;
				}
				if ($roomsol['totpeople'] < ($roomsol['toadult'] + $roomsol['tochild'])) {
					/**
					 * The sum of the max_adults and max_children exceeds the max_guests: lower the adults 
					 * we can take first (if party children > 0), then the children, until sum=max_guests
					 */
					while (($roomsol['toadult'] > 0 || $roomsol['tochild'] > 0)) {
						if (!$party_guests['children'] && $roomsol['totpeople'] == $roomsol['toadult']) {
							/**
							 * When no children requested in the party, we cannot under-utilize rooms.
							 * Break the loop without lowering the 'toadult'.
							 */
							$roomsol['tochild'] = 0;
							break;
						}
						if ($party_guests['children'] && $solution_guests['children'] >= $party_guests['children']) {
							/**
							 * If all the children requested were allocated in other solutions,
							 * we should not under-utilize rooms by reducing the number of adults.
							 */
							break;
						}
						if ($roomsol['toadult'] > 0 && $party_guests['children'] > 0 && !($roomsol['tochild'] > $party_guests['children'])) {
							/**
							 * We lower first the adults that we put in this room, only if there are children in the party 
							 * and if the children in the party are more than the 'max_children' of this room.
							 */
							$roomsol['toadult']--;
							if ($roomsol['totpeople'] >= ($roomsol['toadult'] + $roomsol['tochild'])) {
								break;
							}
						}
						if ($roomsol['tochild'] > 0) {
							// if the max_guests is still greater than the sum of adults+children we take, take out one child
							$roomsol['tochild']--;
							if ($roomsol['totpeople'] >= ($roomsol['toadult'] + $roomsol['tochild'])) {
								break;
							}
						}
						if ($roomsol['toadult'] > 0) {
							// if even at this point we still have a high sum of guests to take compared to the max_guests, take out again one adult
							$roomsol['toadult']--;
							if ($roomsol['totpeople'] >= ($roomsol['toadult'] + $roomsol['tochild'])) {
								break;
							}
						}
					}
				}
				$solution_guests['adults']   += $roomsol['toadult'] * $room_min_uleft;
				$solution_guests['children'] += $roomsol['tochild'] * $room_min_uleft;
				// update 'max_adults' and 'max_children' for this solution (for later guests allocation)
				$solution[$rid]['toadult'] = $roomsol['toadult'];
				$solution[$rid]['tochild'] = $roomsol['tochild'];
			}

			$solutions[$kday] = $solution;
			if ($solution_guests['adults'] < $party_guests['adults'] || $solution_guests['children'] < $party_guests['children']) {
				// the guests we can allocate with the solution of this day are not enough: unset the solution
				unset($solutions[$kday]);
				continue;
			}

			// if we get to this point we can suggest a booking solution for the party requested, but in different rooms
			if (!isset($alternative_parties[$kday])) {
				$alternative_parties[$kday] = [];
			}

			// re-loop over the rooms in this solution to build the booking solution for this day
			$guests_allocated = [
				'adults' => 0,
				'children' => 0
			];

			/**
			 * The rooms available for an alternative booking solutions have been sorted by capacity
			 * in a descending order to quickly fit the guest party requested. However, if a smaller
			 * and cheaper room was capable of fitting all guests, we should opt for this solution.
			 * 
			 * @since 	1.15.4 (J) - 1.5.9 (WP)
			 */
			$smaller_fit_found = false;
			$smaller_solutions = array_reverse($solution, true);
			foreach ($smaller_solutions as $rid => $roomsol) {
				if ($party_guests['adults'] > 0 && $party_guests['adults'] > $roomsol['toadult']) {
					// too many adults requested for this small room
					continue;
				}
				if ($party_guests['children'] > 0 && $party_guests['children'] > $roomsol['tochild']) {
					// too many children requested for this small room
					continue;
				}
				if (($party_guests['adults'] + $party_guests['children']) > $roomsol['totpeople']) {
					// too many guests requested for this small room
					continue;
				}
				// we've got a fitting room which could be smaller
				$roomsol['guests_allocation'] = [
					'adults'   => $party_guests['adults'],
					'children' => $party_guests['children'],
				];
				array_push($alternative_parties[$kday], $roomsol);
				// turn flag on and break the loop
				$smaller_fit_found = true;
				break;
			}
			if ($smaller_fit_found) {
				// no need to parse the rooms from the largest to the smallest
				continue;
			}

			foreach ($solution as $rid => $roomsol) {
				// count minimum units left for this room
				$room_min_uleft = min($roomsol['days_av_left']);
				// fullfil all the units of this room
				for ($units_taken = 0; $units_taken < $room_min_uleft; $units_taken++) { 
					$current_allocation = [
						'adults' => 0,
						'children' => 0
					];
					if ($guests_allocated['adults'] < $party_guests['adults']) {
						$humans_taken 	= $roomsol['toadult'];
						$missing_humans = $party_guests['adults'] - $guests_allocated['adults'];
						$humans_taken 	= $humans_taken > $missing_humans ? $missing_humans : $humans_taken;

						$current_allocation['adults'] = $humans_taken;
						$guests_allocated['adults']   += $humans_taken;
					}
					if ($guests_allocated['children'] < $party_guests['children']) {
						$humans_taken 	= $roomsol['tochild'];
						$missing_humans = $party_guests['children'] - $guests_allocated['children'];
						$humans_taken 	= $humans_taken > $missing_humans ? $missing_humans : $humans_taken;

						$current_allocation['children'] = $humans_taken;
						$guests_allocated['children'] 	+= $humans_taken;
					}
					$roomsol['guests_allocation'] = $current_allocation;
					array_push($alternative_parties[$kday], $roomsol);
					if ($guests_allocated['adults'] >= $party_guests['adults'] && $guests_allocated['children'] >= $party_guests['children']) {
						// we have allocated all guests, exit the for-loop
						break;
					}
				}
				if ($guests_allocated['adults'] >= $party_guests['adults'] && $guests_allocated['children'] >= $party_guests['children']) {
					//we have allocated all guests with this solution, no need to loop over other rooms available in this day.
					break;
				}
			}
		}

		// return the alternative parties found, if any
		return $alternative_parties;
	}

	/**
	 * Given a list of alternative dates obtained from an inquiry/request information,
	 * composes a valid room-rate array to store the inquiry reservation. By calling this
	 * method, the original stay dates will be overwritten.
	 * 
	 * @param 	array 	$alt_dates 	the list of alternative dates found for the stay.
	 * @param 	object 	$customer 	a stdClass object with the basic customer details.
	 * 
	 * @return 	int 				the ID of the newly created inquiry reservation.
	 */
	public function allocateAltDatesInquiry($alt_dates, $customer)
	{
		if (!is_array($alt_dates) || !$alt_dates) {
			return 0;
		}

		foreach ($alt_dates as $ymd => $rooms) {
			// we expect just one room-type for the party, and we use the first suggestion
			foreach ($rooms as $rid => $alt_stay) {
				if (empty($alt_stay['days_av_left']) || !is_array($alt_stay['days_av_left'])) {
					// invalid structure
					continue;
				}
				// compose the new stay dates
				$sugg_checkin_dt  = null;
				$sugg_checkout_dt = null;
				foreach ($alt_stay['days_av_left'] as $dayk => $uleft) {
					if (empty($sugg_checkin_dt)) {
						// grab the first date
						$sugg_checkin_dt = $dayk;
					}
					// always overwrite until last date
					$sugg_checkout_dt = $dayk;
				}
				// increase check-out date by one day (day after last night of stay)
				$sugg_out_info = getdate(strtotime($sugg_checkout_dt));
				$sugg_checkout_dt = date('Y-m-d', mktime(0, 0, 0, $sugg_out_info['mon'], ($sugg_out_info['mday'] + 1), $sugg_out_info['year']));

				// set the new stay dates
				$this->setStayDates($sugg_checkin_dt, $sugg_checkout_dt);

				// build the room rate plan array without any rate plan information
				$room_rplan = [
					'idroom' => $alt_stay['id'],
				];

				// create the inquiry reservation for the closest alternative dates
				return $this->createInquiryReservation($room_rplan, $customer);
			}
		}

		return 0;
	}

	/**
	 * Given a list of alternative parties obtained from an inquiry/request information,
	 * composes valid room-rate arrays to store the inquiry reservation. By calling this
	 * method, the original stay dates and room party will be overwritten.
	 * 
	 * @param 	array 	$alt_parties 	the list of alternative parties found for the stay.
	 * @param 	object 	$customer 		a stdClass object with the basic customer details.
	 * 
	 * @return 	int 					the ID of the newly created inquiry reservation.
	 */
	public function allocateAltPartyInquiry($alt_parties, $customer)
	{
		if (!is_array($alt_parties) || !$alt_parties) {
			return 0;
		}

		// build list of rooms to assign to the inquiry reservation
		$room_rates = [];

		// start party counter
		$party_counter = 0;

		foreach ($alt_parties as $ymd => $alt_rooms) {
			// we expect to have more than one room-type for the large party suggestion
			foreach ($alt_rooms as $alt_room) {
				if (empty($alt_room['guests_allocation']) || !is_array($alt_room['guests_allocation'])) {
					// invalid structure
					continue;
				}
				if (empty($alt_room['days_av_left']) || !is_array($alt_room['days_av_left'])) {
					// invalid structure
					continue;
				}
				// compose the new stay dates
				$sugg_checkin_dt  = null;
				$sugg_checkout_dt = null;
				foreach ($alt_room['days_av_left'] as $dayk => $uleft) {
					if (empty($sugg_checkin_dt)) {
						// grab the first date
						$sugg_checkin_dt = $dayk;
					}
					// always overwrite until last date
					$sugg_checkout_dt = $dayk;
				}
				// increase check-out date by one day (day after last night of stay)
				$sugg_out_info = getdate(strtotime($sugg_checkout_dt));
				$sugg_checkout_dt = date('Y-m-d', mktime(0, 0, 0, $sugg_out_info['mon'], ($sugg_out_info['mday'] + 1), $sugg_out_info['year']));

				// set the new stay dates
				$this->setStayDates($sugg_checkin_dt, $sugg_checkout_dt);

				// set the current guests party (the first will replace the previous party, others will be pushed)
				$this->setRoomParty($alt_room['guests_allocation']['adults'], $alt_room['guests_allocation']['children'], ($party_counter === 0));

				// increase party counter
				$party_counter++;

				// push current room with no rate plan information
				array_push($room_rates, [
					'idroom' => $alt_room['id'],
				]);
			}

			if (count($room_rates)) {
				// we use the closest dates in the first suggestion party array
				break;
			}
		}

		// count total rooms in the party
		$tot_room_party = count($room_rates);

		if (!$tot_room_party) {
			// something went wrong
			return 0;
		}

		// grab the main/first room reservation
		$room_rplan = $room_rates[0];

		// build extra rooms
		$extra_rooms = [];
		if ($tot_room_party > 1) {
			// grab the remaining rooms
			unset($room_rates[0]);
			$extra_rooms = array_values($room_rates);
		}

		// create the inquiry reservation for the closest alternative dates and rooms party
		return $this->createInquiryReservation($room_rplan, $customer, $extra_rooms);
	}

	/**
	 * Creates a new pending reservation from the inquiry/request information.
	 * Requires a valid room-rate array to be available, or in case suggestions should
	 * be applied, the room-rate array should be adjusted to comply with this method.
	 * 
	 * @param 	array 	$room_rplan 	a room-rate array to allocate the booking.
	 * @param 	object 	$customer 		a stdClass object with the basic customer details.
	 * @param 	array 	$extra_rooms 	optional list of additional room-rate arrays to store
	 * 									in case of alternative parties suggested.
	 * 
	 * @return 	int 					the ID of the newly created inquiry reservation.
	 */
	public function createInquiryReservation($room_rplan, $customer, $extra_rooms = array())
	{
		if (!is_array($room_rplan) || empty($room_rplan['idroom'])) {
			return 0;
		}

		if (empty($this->stay_ts) || empty($this->room_parties)) {
			// no stay dates or room party set
			return 0;
		}

		$dbo = JFactory::getDbo();

		// build reservation object
		$res_obj = new stdClass;
		$res_obj->custdata = $customer->custdata;
		$res_obj->ts = time();
		$res_obj->status = 'standby';
		$res_obj->days = $this->countNightsOfStay();
		$res_obj->checkin = $this->stay_ts[0];
		$res_obj->checkout = $this->stay_ts[1];
		$res_obj->custmail = $customer->email;
		$res_obj->sid = VikBooking::getSecretLink();
		$res_obj->idpayment = $this->getDefaultPaymentId();
		$res_obj->roomsnum = count($this->room_parties);
		if (!empty($room_rplan['cost'])) {
			$res_obj->total = (float)$room_rplan['cost'];
		}
		$res_obj->adminnotes = $customer->adminnotes;
		$res_obj->lang = $customer->lang;
		$res_obj->country = $customer->country;
		if (!empty($room_rplan['taxes'])) {
			$res_obj->tot_taxes = (float)$room_rplan['taxes'];
		}
		if (!empty($room_rplan['city_taxes'])) {
			$res_obj->tot_city_taxes = (float)$room_rplan['city_taxes'];
		}
		$res_obj->phone = $customer->phone;
		$res_obj->type = 'inquiry';

		// store record
		if (!$dbo->insertObject('#__vikbooking_orders', $res_obj, 'id')) {
			// could not store the booking record
			return 0;
		}

		// get the ID of the newly created reservation
		$res_id = $res_obj->id;

		// check if mandatory options should be assigned (not in case of suggestions)
		$room_options = null;
		if (!empty($room_rplan['idprice']) && isset($res_obj->tot_city_taxes)) {
			$mand_taxes = VikBooking::getMandatoryTaxesFees([$room_rplan['idroom']], $this->getPartyGuests('adults', 0), $res_obj->days);
			if (is_array($mand_taxes) && !empty($mand_taxes['options'])) {
				$room_options = implode(';', $mand_taxes['options']);
			}
		}

		// build room-reservation object
		$room_res_obj = new stdClass;
		$room_res_obj->idorder = $res_id;
		$room_res_obj->idroom = $room_rplan['idroom'];
		$room_res_obj->adults = $this->getPartyGuests('adults', 0);
		$room_res_obj->children = $this->getPartyGuests('children', 0);
		if (!empty($room_rplan['idprice'])) {
			$room_res_obj->idtar = $this->getTariffId($room_rplan['idroom'], $room_rplan['idprice'], $res_obj->days);
		}
		$room_res_obj->optionals = $room_options;
		$room_res_obj->t_first_name = $customer->name;
		$room_res_obj->t_last_name = $customer->lname;

		// store record
		if (!$dbo->insertObject('#__vikbooking_ordersrooms', $room_res_obj, 'id')) {
			// could not store the room-reservation record
			return $res_id;
		}

		// in case of suggestions for alternative room parties, parse the extra rooms
		$party_index = 1;
		foreach ($extra_rooms as $extra_room) {
			if (!is_array($extra_room) || empty($extra_room['idroom'])) {
				continue;
			}
			// build additional room-reservation object
			$room_res_obj = new stdClass;
			$room_res_obj->idorder = $res_id;
			$room_res_obj->idroom = $extra_room['idroom'];
			$room_res_obj->adults = $this->getPartyGuests('adults', $party_index);
			$room_res_obj->children = $this->getPartyGuests('children', $party_index);

			// store record
			$dbo->insertObject('#__vikbooking_ordersrooms', $room_res_obj, 'id');

			// increase room party index
			$party_index++;
		}

		// return the newly created reservation ID
		return $res_id;
	}

	/**
	 * Attempts to get the tariff ID for the given room, rate plan and nights.
	 * 
	 * @param 	int 	$room_id 	the ID of the VBO room.
	 * @param 	int 	$rplan_id 	the rate plan ID in VBO.
	 * @param 	int 	$nights 	the number of nights of stay.
	 * 
	 * @return 	int|null 			the tariff ID or null.
	 */
	public function getTariffId($room_id, $rplan_id, $nights)
	{
		if (empty($room_id) || empty($rplan_id) || $nights < 1) {
			return null;
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `id` FROM `#__vikbooking_dispcost` WHERE `idroom`={$room_id} AND `days`={$nights} AND `idprice`={$rplan_id}";
		$dbo->setQuery($q, 0, 1);
		$res = $dbo->loadResult();

		if ($res) {
			return (int)$res;
		}

		return null;
	}

	/**
	 * Grabs the details of a given booking id.
	 * 
	 * @param 	int 	$bid 	the booking ID to look for.
	 * 
	 * @return 	array 	empty array or booking record details.
	 */
	public function getBookingDetails($bid)
	{
		$bid = (int)$bid;

		$dbo = JFactory::getDbo();

		$q = "SELECT `o`.*, `co`.`idcustomer`, CONCAT_WS(' ', `c`.`first_name`, `c`.`last_name`) AS `customer_fullname`, `c`.`country` AS `customer_country`, `c`.`pic` 
			FROM `#__vikbooking_orders` AS `o` 
			LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` 
			LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` 
			WHERE `o`.`id`={$bid}";
		$dbo->setQuery($q, 0, 1);
		$row = $dbo->loadAssoc();
		if ($row) {
			return $row;
		}

		return [];
	}

	/**
	 * Validates if the room allows the given number of nights of stay by checking if a
	 * tariff is defined for the given length of stay. Useful for particular rate tables.
	 * 
	 * @param 	int 	$room_id 	the ID of the VBO room.
	 * @param 	int 	$nights 	the number of nights of stay.
	 * 
	 * @return 	bool 				true if a tariff is found between min and max nights.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP)
	 */
	public function roomNightsAllowed($room_id, $nights)
	{
		if (!isset($this->min_max_los_tariffs_map[$room_id])) {
			$dbo = JFactory::getDbo();

			$q = $dbo->getQuery(true)
				->select('MIN(' . $dbo->qn('t.days') . ') AS ' . $dbo->qn('min_nights'))
				->select('MAX(' . $dbo->qn('t.days') . ') AS ' . $dbo->qn('max_nights'))
				->from($dbo->qn('#__vikbooking_dispcost', 't'))
				->where($dbo->qn('t.idroom') . ' = ' . (int)$room_id);

			$dbo->setQuery($q, 0, 1);
			$tariffs = $dbo->loadObject();

			if (!$tariffs || !$tariffs->min_nights || !$tariffs->max_nights) {
				return false;
			}

			// set values
			$this->min_max_los_tariffs_map[$room_id] = [$tariffs->min_nights, $tariffs->max_nights];
		}

		// check if the number of nights of stay is within the range of tariffs los map
		return ($nights >= min($this->min_max_los_tariffs_map[$room_id]) && $nights <= max($this->min_max_los_tariffs_map[$room_id]));
	}

	/**
	 * Sets the stay dates, check-in and check-out date timestamps.
	 * 
	 * @param 	string 	$from 	check-in date string in Y-m-d or VBO format.
	 * @param 	string 	$to 	check-out date string in Y-m-d or VBO format.
	 * 
	 * @return 	self
	 */
	public function setStayDates($from, $to)
	{
		if (empty($from) || empty($to)) {
			return $this;
		}

		$checkinh = 0;
		$checkinm = 0;
		$checkouth = 0;
		$checkoutm = 0;
		$timeopst = VikBooking::getTimeOpenStore();
		if (is_array($timeopst)) {
			if ($timeopst[0] < $timeopst[1]) {
				// check-in not allowed on a day where there is already a check out (no arrivals/depatures on the same day)
				$this->inonout_allowed = false;
			}
			$opent = VikBooking::getHoursMinutes($timeopst[0]);
			$closet = VikBooking::getHoursMinutes($timeopst[1]);
			$checkinh = $opent[0];
			$checkinm = $opent[1];
			$checkouth = $closet[0];
			$checkoutm = $closet[1];
		}
		$from_ts = VikBooking::getDateTimestamp($from, $checkinh, $checkinm);
		$to_ts 	 = VikBooking::getDateTimestamp($to, $checkouth, $checkoutm);

		// set stay dates and timestamps
		$this->stay_dates = [date('Y-m-d', $from_ts), date('Y-m-d', $to_ts)];
		$this->stay_ts 	  = [$from_ts, $to_ts];

		return $this;
	}

	/**
	 * Returns the current stay date or timestamps.
	 * 
	 * @param 	bool 	$ts 	whether to get the date timestamps.
	 * 
	 * @return 	array 			the current stay date timestamps.
	 */
	public function getStayDates($ts = false)
	{
		return $ts ? $this->stay_ts : $this->stay_dates;
	}

	/**
	 * Sets a room party with adults and children, by optionally replacing the others.
	 * 
	 * @param 	int 	$adults 	the number of adults for this room party.
	 * @param 	int 	$children 	the number of children for this room party.
	 * @param 	bool 	$replace 	if true, any previously set room party will be replaced.
	 * 
	 * @return 	self
	 */
	public function setRoomParty($adults, $children = 0, $replace = false)
	{
		$room_party = [
			'adults'   => $adults,
			'children' => $children,
		];

		if ($replace) {
			$this->room_parties = [$room_party];
		} else {
			array_push($this->room_parties, $room_party);
		}

		return $this;
	}

	/**
	 * Returns the current room parties array.
	 * 
	 * @return 	array 	the current room parties.
	 */
	public function getRoomParties()
	{
		return $this->room_parties;
	}

	/**
	 * Sets and returns the flag to ignore the restrictions.
	 * 
	 * @param 	bool 	$set 	the boolean status to set.
	 * 
	 * @return 	bool 			the current ignore status.
	 */
	public function ignoreRestrictions($set = null)
	{
		if (is_bool($set)) {
			$this->ignore_restrictions = $set;
		}

		return $this->ignore_restrictions;
	}

	/**
	 * Takes the first payment ID "string", if available.
	 * 
	 * @return 	string|null
	 */
	protected function getDefaultPaymentId()
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`, `name` FROM `#__vikbooking_gpayments` WHERE `published`=1 ORDER BY `ordering` ASC";
		$dbo->setQuery($q, 0, 1);
		$data = $dbo->loadAssoc();

		if ($data) {
			return $data['id'] . '=' . $data['name'];
		}

		return null;
	}

	/**
	 * Returns the current nights/transfers ratio for split stays.
	 * 
	 * @return 	int 	the nights transfers ratio for the percent calculation.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function getNightsTransfersRatio()
	{
		return $this->nights_transfers_ratio;
	}

	/**
	 * Returns the default nights/transfers ratio for split stays.
	 * 
	 * @return 	int 	the nights transfers ratio defined in the configuration.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function getDefaultNightsTransfersRatio()
	{
		$config = VBOFactory::getConfig();

		return (int)$config->get('split_stay_ratio', 50);
	}

	/**
	 * Sets the nights/transfers ratio for split stays. Use it to start applying limits.
	 * 
	 * @param 	mixed 	$ratio 	integer value, or the default config setting will be applied.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function setNightsTransfersRatio($ratio = null)
	{
		if (is_int($ratio)) {
			$this->nights_transfers_ratio = $ratio;
		} else {
			$this->nights_transfers_ratio = $this->getDefaultNightsTransfersRatio();
		}

		return $this;
	}

	/**
	 * Tells whether we need to behave for the front-end booking process.
	 * 
	 * @return 	bool 	true if we are in the front-end booking process or false.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function isFrontBooking()
	{
		return (bool)$this->is_front_booking;
	}

	/**
	 * Toggles the flag to behave for the front-end booking process.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function setIsFrontBooking($is_front = true)
	{
		$this->is_front_booking = (bool)$is_front;

		return $this;
	}

	/**
	 * This helper method aims to collect the stay dates of each room in
	 * a booking with split stay. Records will be loaded by ID ascending,
	 * so in the same exact way as the rooms get stored. For this reason,
	 * it is then possible to match the stay dates of a room by array-key,
	 * even if bookings with split stay should always have different room IDs.
	 * 
	 * @param 	int 	$bid 	the website reservation ID.
	 * 
	 * @return 	array 			the list of busy records with stay dates information.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function loadSplitStayBusyRecords($bid)
	{
		$dbo = JFactory::getDbo();

		$bid = (int)$bid;

		/**
		 * It is fundamental to keep the ID column as the busy record ID
		 * to allow the room switching in case of booking modification.
		 */
		$q = "SELECT `ob`.`idorder`, `b`.`id`, `b`.`idroom`, `b`.`checkin`, `b`.`checkout`, `b`.`sharedcal` 
			FROM `#__vikbooking_ordersbusy` AS `ob` 
			LEFT JOIN `#__vikbooking_busy` AS `b` ON `ob`.`idbusy`=`b`.`id` 
			WHERE `ob`.`idorder`={$bid} 
			ORDER BY `b`.`sharedcal` ASC, `b`.`id` ASC;";
		$dbo->setQuery($q);
		$records = $dbo->loadAssocList();

		if (!$records) {
			// the booking may no longer exist, or maybe it was cancelled
			return [];
		}

		return $records;
	}

	/**
	 * Returns a list of the tax rate records.
	 * 
	 * @return 	array 	the list of tax rates.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function getTaxRates()
	{
		$dbo = JFactory::getDbo();
		$dbo->setQuery("SELECT * FROM `#__vikbooking_iva`;");

		return $dbo->loadAssocList();
	}

	/**
	 * Sets the IDs of the rooms to filter or use.
	 * 
	 * @param 	mixed 	$room_ids 	the list of room IDs to filter or use, or int room ID.
	 * 
	 * @return 	self
	 */
	public function setRoomIds($room_ids = [])
	{
		if (is_scalar($room_ids)) {
			// single room ID integer
			$room_ids = [$room_ids];
		}

		$this->room_ids = $room_ids;

		return $this;
	}

	/**
	 * Returns the current room ids to filter or use.
	 * 
	 * @return 	array 	the current room ids.
	 */
	public function getRoomIds()
	{
		return $this->room_ids;
	}

	/**
	 * In case of no availability, overrides the default number of days to check
	 * prior and after the originally requested check-in and check-out dates.
	 * 
	 * @param 	int 	$days 	the total number of days to use back and forth.
	 * 
	 * @return 	self
	 */
	public function setBackForthDays($days)
	{
		if (is_int($days) && $days >= 0) {
			$this->back_and_forth = $days;
		}

		return $this;
	}

	/**
	 * Returns the current number of back and forth days.
	 * 
	 * @return 	int 	the current number of days.
	 */
	public function getBackForthDays()
	{
		return $this->back_and_forth;
	}

	/**
	 * Sets warning messages by concatenating the existing ones.
	 *
	 * @param 	string 		$str
	 *
	 * @return 	self
	 */
	protected function setWarning($str)
	{
		$this->warning .= $str . "\n";

		return $this;
	}

	/**
	 * Gets the current warning string.
	 *
	 * @return 	string
	 */
	public function getWarning()
	{
		return rtrim($this->warning, "\n");
	}

	/**
	 * Sets errors by concatenating the existing ones.
	 *
	 * @param 	string 		$str
	 *
	 * @return 	self
	 */
	protected function setError($str)
	{
		$this->error .= $str . "\n";

		return $this;
	}

	/**
	 * Gets the current error string.
	 *
	 * @return 	string
	 */
	public function getError()
	{
		return rtrim($this->error, "\n");
	}

	/**
	 * Gets the current error code.
	 *
	 * @return 	int
	 */
	public function getErrorCode()
	{
		return $this->errorCode;
	}
}
