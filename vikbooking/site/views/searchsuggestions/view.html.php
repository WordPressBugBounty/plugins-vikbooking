<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

jimport('joomla.application.component.view');

class VikbookingViewSearchsuggestions extends JViewVikBooking
{
	function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		$vbo_tn = VikBooking::getTranslator();

		// invoke availability helper class and define settings
		$av_helper = VikBooking::getAvailabilityInstance()
			->setBackForthDays(14)
			->setIsFrontBooking(true)
			->setNightsTransfersRatio(true);

		$code = VikRequest::getInt('code', 0, 'request');
		$fromts = VikRequest::getInt('fromts', 0, 'request');
		$tots = VikRequest::getInt('tots', 0, 'request');
		$party = $padults = VikRequest::getVar('party', array());
		$pcategories = VikRequest::getString('categories', '', 'request');

		// set stay values
		$checkin_date  = date('Y-m-d', $fromts);
		$checkout_date = date('Y-m-d', $tots);
		$av_helper->setStayDates($checkin_date, $checkout_date);

		// count nights of stay
		$daysdiff = $av_helper->countNightsOfStay();

		// get how many guests were asked in the party
		$party_guests = [
			'adults'   => 0,
			'children' => 0,
		];
		foreach ($party as $guests) {
			// set party involved for this room
			$av_helper->setRoomParty((isset($guests['adults']) ? (int)$guests['adults'] : 0), (isset($guests['children']) ? (int)$guests['children'] : 0));
			if (isset($guests['adults'])) {
				$party_guests['adults'] += (int)$guests['adults'];
			}
			if (isset($guests['children'])) {
				$party_guests['children'] += (int)$guests['children'];
			}
		}

		// get nights requested
		$info_from = getdate($fromts);
		$nights_requested = [
			mktime(0, 0, 0, $info_from['mon'], $info_from['mday'], $info_from['year']),
		];
		for ($n = 1; $n < $daysdiff; $n++) {
			$nights_requested[] = mktime(0, 0, 0, $info_from['mon'], ($info_from['mday'] + $n), $info_from['year']);
		}

		// get rooms IDs available
		$rooms_info = [];
		$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `avail`=1 ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$getrooms = $dbo->loadAssocList();
			$vbo_tn->translateContents($getrooms, '#__vikbooking_rooms');
			foreach ($getrooms as $r) {
				/**
				 * If requested, we apply the same category filter used for the search.
				 * 
				 * @since 	1.14 (J) - 1.4.0 (WP)
				 */
				if (!empty($pcategories) && $pcategories != 'all' && !empty($r['idcat'])) {
					$room_cats = explode(';', $r['idcat']);
					if (!empty($room_cats[0]) && !in_array($pcategories, $room_cats)) {
						// room has got categories, but the filtered one is not available, so skip this room
						continue;
					}
				}

				// push room
				$rooms_info[$r['id']] = $r;
			}
		}

		// set rooms
		$av_helper->setRooms($rooms_info);

		// check for turnover day between bookings
		$inonout_allowed = true;
		$timeopst = VikBooking::getTimeOpenStore();
		if (is_array($timeopst)) {
			if ($timeopst[0] < $timeopst[1]) {
				// check-in not allowed on a day where there is already a check out (no arrivals/depatures on the same day)
				$inonout_allowed = false;
			}
		}

		// get closest timestamps to the requested dates (14 days before and after the requested dates, if not in the past)
		$info_from = getdate($fromts);
		// get nights requested
		$nights_requested = [];
		$nights_requested[] = mktime(0, 0, 0, $info_from['mon'], $info_from['mday'], $info_from['year']);
		for ($n = 1; $n < $daysdiff; $n++) {
			$nights_requested[] = mktime(0, 0, 0, $info_from['mon'], ($info_from['mday'] + $n), $info_from['year']);
		}
		// count suggested dates back/forth
		$info_to = getdate($tots);
		$lim_past_ts = mktime($info_from['hours'], $info_from['minutes'], $info_from['seconds'], date('n'), ((int)date('j') + VikBooking::getMinDaysAdvance()), date('Y'));
		$sug_from_ts = mktime($info_from['hours'], $info_from['minutes'], $info_from['seconds'], $info_from['mon'], ($info_from['mday'] - 14), $info_from['year']);
		$sug_from_ts = $sug_from_ts < $lim_past_ts ? $lim_past_ts : $sug_from_ts;
		$sug_to_ts = mktime($info_to['hours'], $info_to['minutes'], $info_to['seconds'], $info_to['mon'], ($info_to['mday'] + 14), $info_to['year']);
		$sug_to_ts = $sug_to_ts < $sug_from_ts ? $sug_from_ts : $sug_to_ts;

		// get days for suggestions
		$groupdays = [];
		$sug_start_info = getdate($sug_from_ts);
		$sug_from_midnight = mktime(0, 0, 0, $sug_start_info['mon'], $sug_start_info['mday'], $sug_start_info['year']);
		$sug_start_info = getdate($sug_from_midnight);
		while ($sug_start_info[0] <= $sug_to_ts) {
			array_push($groupdays, $sug_start_info[0]);
			$sug_start_info = getdate(mktime(0, 0, 0, $sug_start_info['mon'], ($sug_start_info['mday'] + 1), $sug_start_info['year']));
		}

		// build suggestions array of dates with some availability
		$suggestions  = [];
		$fully_booked = [];
		$allbusy = VikBooking::loadBusyRecords(array_keys($rooms_info), $sug_from_ts, strtotime('+1 day', $sug_to_ts));
		foreach ($rooms_info as $rid => $room) {
			foreach ($groupdays as $gday) {
				$day_key = date('Y-m-d', $gday);
				$bfound = 0;
				if (!isset($allbusy[$rid])) {
					$allbusy[$rid] = [];
				}
				foreach ($allbusy[$rid] as $bu) {
					$busy_info_in = getdate($bu['checkin']);
					$busy_info_out = getdate($bu['checkout']);
					$busy_in_ts = mktime(0, 0, 0, $busy_info_in['mon'], $busy_info_in['mday'], $busy_info_in['year']);
					$busy_out_ts = mktime(0, 0, 0, $busy_info_out['mon'], $busy_info_out['mday'], $busy_info_out['year']);
					if ($gday >= $busy_in_ts && $gday == $busy_out_ts && !$inonout_allowed && $room['units'] < 2) {
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
					$suggestions[$day_key] = $suggestions[$day_key] + array($rid => $room_day);
				} else {
					if (!in_array($room['id'], $fully_booked)) {
						// push fully booked room ID
						$fully_booked[] = $room['id'];
					}
				}
			}
		}

		// build the solutions array with keys=checkin, values=all rooms suited for the requested number of nights
		$solutions = [];
		if (count($suggestions)) {
			//get all rooms available for the number of nights requested in the suggestions array of dates
			foreach ($suggestions as $kday => $rooms) {
				$day_ts_info = getdate(strtotime($kday));
				foreach ($rooms as $rid => $room) {
					$suitable = true;
					$room_days_av_left = array($kday => $room['units_left']);
					for ($i = 1; $i < $daysdiff; $i++) {
						$next_night = mktime(0, 0, 0, $day_ts_info['mon'], ($day_ts_info['mday'] + $i), $day_ts_info['year']);
						$next_night_dt = date('Y-m-d', $next_night);
						if (!isset($suggestions[$next_night_dt]) || !isset($suggestions[$next_night_dt][$rid])) {
							$suitable = false;
							break;
						}
						$room_days_av_left[$next_night_dt] = $suggestions[$next_night_dt][$rid]['units_left'];
					}
					if ($suitable === true) {
						if (!isset($solutions[$day_ts_info[0]])) {
							$solutions[$day_ts_info[0]] = [];
						}
						unset($room['units_left']);
						$room['days_av_left'] = $room_days_av_left;
						$solutions[$day_ts_info[0]] = $solutions[$day_ts_info[0]] + array($rid => $room);
					}
				}
			}
		}

		if (count($solutions)) {
			//sort the solutions by the closest checkin date to the one requested
			$sortmap = [];
			foreach ($solutions as $kdayts => $solution) {
				$sortmap[$kdayts] = $kdayts > $fromts ? ($kdayts - $fromts) : ($fromts - $kdayts);
			}
			asort($sortmap);
			$sorted = [];
			foreach ($sortmap as $kdayts => $v) {
				$sorted[$kdayts] = $solutions[$kdayts];
			}
			$solutions = $sorted;

			/**
			 * Load rooms involved in the final alternative solutions in order
			 * to validate global/room-level restrictions and closing dates.
			 * 
			 * @since 	1.15.4 (J) - 1.5.10 (WP)
			 */
			$rooms_involved = [];
			foreach ($solutions as $kdayts => $roomsol) {
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

			foreach ($solutions as $kdayts => $roomsol) {
				// build suggested stay dates
				$sug_in  = getdate($kdayts);
				$sug_out = getdate(mktime(0, 0, 0, $sug_in['mon'], ($sug_in['mday'] + $daysdiff), $sug_in['year']));
				// validate global restrictions
				if (VikBooking::validateRoomRestriction($glob_restrictions, $sug_in, $sug_out, $daysdiff)) {
					// global restrictions apply over this stay
					unset($solutions[$kdayts]);
					continue;
				}
				// validate closing dates
				if (VikBooking::validateClosingDates($sug_in[0], $sug_out[0])) {
					// global closing dates apply over this stay
					unset($solutions[$kdayts]);
					continue;
				}
				// validate restrictions at room level
				foreach ($roomsol as $rid => $rdata) {
					if (!isset($room_level_restr[$rid])) {
						// load restrictions at room level
						$room_level_restr[$rid] = VikBooking::roomRestrictions($rid, $all_restrictions);
					}
					if (VikBooking::validateRoomRestriction($room_level_restr[$rid], $sug_in, $sug_out, $daysdiff)) {
						// room-level restrictions apply over this stay
						unset($solutions[$kdayts][$rid]);
						if (!count($solutions[$kdayts])) {
							// unset the entire suggested date
							unset($solutions[$kdayts]);
							break;
						}
						continue;
					}
				}
			}
		}

		// this array should only be parsed in case of error codes 2 and 3. It is not populated in case of error code 1
		$booking_solutions = [];

		/**
		 * In case of error code 1, we try to check for some split stay solutions.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$split_stay_solutions = [];

		// parse the solutions depending on the error code to give the right booking solutions
		if (count($solutions)) {
			switch ($code) {
				case 1:
					// no availability for the dates requested (there are rates defined for this number of nights and rooms compatible with the party requested)
					// the solution is to suggest another date for check-in/out when there is availability for the requested party

					// check if the party requested is suited for the current solutions and unset the ones we can't suggest
					foreach ($solutions as $kdayts => $solution) {
						$party_solutions = [];
						foreach ($party as $nump => $guests) {
							foreach ($solution as $rid => $roomsol) {
								if (($roomsol['fromadult'] <= $guests['adults'] && $roomsol['toadult'] >= $guests['adults']) && 
									($roomsol['fromchild'] <= $guests['children'] && $roomsol['tochild'] >= $guests['children']) && 
									($roomsol['mintotpeople'] <= ($guests['adults'] + $guests['children'])) && 
									($roomsol['totpeople'] >= ($guests['adults'] + $guests['children']))) {
									// this room-solution can fit this party
									$sol_min_rooms = min($roomsol['days_av_left']);
									$solutions[$kdayts][$rid]['minrooms'] = $sol_min_rooms;
									if (!isset($party_solutions[$nump])) {
										$party_solutions[$nump] = [];
									}
									$party_solutions[$nump] = $party_solutions[$nump] + array($rid => $solutions[$kdayts][$rid]);
								}
							}
						}
						if (count($party_solutions) != count($party)) {
							// in this type of error-code we can't suggest other booking solutions for a specific day, so we unset this solution that doesn't fit the party
							unset($solutions[$kdayts]);
							continue;
						}

						// check if the rooms-solutions have enough units to be used
						$rooms_usage = [];
						$rooms_avail = [];
						foreach ($party_solutions as $nump => $party_sol) {
							foreach ($party_sol as $rid => $roomsol) {
								if (!isset($rooms_avail[$rid])) {
									$rooms_avail[$rid] = $roomsol['minrooms'];
								}
								if (!isset($rooms_usage[$rid])) {
									$rooms_usage[$rid] = 0;
								}
								$rooms_usage[$rid]++;
							}
						}
						foreach ($rooms_usage as $rid => $usage) {
							if ($usage > $rooms_avail[$rid]) {
								// this room has been suggested too many times for its remaining availability on this day. Remove it until it's okay
								while ($usage > $rooms_avail[$rid]) {
									foreach ($party_solutions as $nump => $party_sol) {
										if (isset($party_sol[$rid])) {
											$usage--;
											unset($party_solutions[$nump][$rid]);
											if (!(count($party_solutions[$nump]) > 0)) {
												// this party has no more solutions
												unset($party_solutions[$nump]);
											}
											if (!($usage > $rooms_avail[$rid])) {
												break;
											}
										}
									}
								}
							}
						}
						if (count($party_solutions) != count($party)) {
							// in this type of error-code we can't suggest other booking solutions for a specific day, so we unset this solution that doesn't fit the party
							unset($solutions[$kdayts]);
							continue;
						}
					}

					// try to populate the split stays available according to settings
					$split_stay_solutions = $av_helper->findSplitStays($fully_booked, $allbusy);

					break;
				case 2:
				case 3:
					// (error code 2) query failed for fetching the rooms (no rates defined for this number of nights or no rooms for the party requested)
					// the solution is to suggest another combination of party for booking (as long as there are rates for the requested number of nights and the number of guests can fit in the available rooms).
					// (error code 3) not enough rooms available for the party requested
					// the solution to suggest is the same as for the error code 2.

					// unset the rooms in the solutions that have no rates for this number of nights as they are useless.
					foreach ($solutions as $kdayts => $solution) {
						$rooms_with_rates = [];
						$q = "SELECT `p`.`idroom` FROM `#__vikbooking_dispcost` AS `p` WHERE `p`.`idroom` IN (".implode(', ', array_keys($solution)).") AND `p`.`days`=".(int)$daysdiff." GROUP BY `p`.`idroom`;";
						$dbo->setQuery($q);
						$dbo->execute();
						if ($dbo->getNumRows() > 0) {
							$getrooms = $dbo->loadAssocList();
							foreach ($getrooms as $r) {
								array_push($rooms_with_rates, (int)$r['idroom']);
							}
						}
						foreach ($solution as $rid => $roomsol) {
							if (!in_array($rid, $rooms_with_rates)) {
								unset($solutions[$kdayts][$rid]);
								continue;
							}
							$solutions[$kdayts][$rid]['minrooms'] = min($roomsol['days_av_left']);
						}
						if (!(count($solutions[$kdayts]))) {
							unset($solutions[$kdayts]);
						}
					}
					if (!(count($solutions))) {
						break;
					}

					// build the booking solution to suggest, for example, to book some rooms more or less.
					// Example: 1 room for 8 adults can be booked as 3 double rooms (3 adults, 3 adults, 2 adults = 8 adults)
					// Example: 2 quadruple rooms for 4 adults each, can be booked as 4 double rooms, with 2 adults each.

					// sort rooms-solutions by 'max-adults' (toadult), 'max-guests' (totpeople), 'max-children' (tochild), in a Descending order
					foreach ($solutions as $kdayts => $solution) {
						// with this sorting, we will have the bigger rooms on top to quickly fit the party requested
						uasort($solutions[$kdayts], function($a, $b) {
							if ($a['toadult'] == $b['toadult']) {
								if ($a['totpeople'] == $b['totpeople']) {
									return $a['tochild'] > $b['tochild'] ? -1 : 1;
								}
								return $a['totpeople'] > $b['totpeople'] ? -1 : 1;
							}
							return $a['toadult'] > $b['toadult'] ? -1 : 1;
						});
					}

					// check if the rooms of each solution can fit the number of guests requested, unset the solution otherwise
					foreach ($solutions as $kdayts => $solution) {
						$solution_guests = array(
							'adults' => 0,
							'children' => 0
						);
						foreach ($solution as $rid => $roomsol) {
							//check if this solution of rooms can allocate all the guests requested
							if ($roomsol['totpeople'] < ($roomsol['toadult'] + $roomsol['tochild']) && !$party_guests['children']) {
								// in case of no children requested, we ignore them to avoid adjusting the room capacity
								$roomsol['tochild'] = 0;
							}
							if ($roomsol['totpeople'] < ($roomsol['toadult'] + $roomsol['tochild'])) {
								// the sum of the max_adults and max_children exceeds the max_guests: lower the adults we can take first (if party children > 0), then the children, until sum=max_guests
								while (($roomsol['toadult'] > 0 || $roomsol['tochild'] > 0)) {
									if (!$party_guests['children'] && $roomsol['totpeople'] == $roomsol['toadult']) {
										/**
										 * When no children requested in the party, we cannot under-utilize rooms.
										 * Break the loop without lowering the 'toadult'.
										 * @since 	1.10 - August 2018
										 */
										$roomsol['tochild'] = 0;
										break;
									}
									if ($party_guests['children'] && $solution_guests['children'] >= $party_guests['children']) {
										/**
										 * If all the children requested were allocated in other solutions,
										 * we should not under-utilize rooms by reduing the number of adults.
										 * @since 	1.10 - August 2018
										 */
										break;
									}
									if ($roomsol['toadult'] > 0 && $party_guests['children'] > 0 && !($roomsol['tochild'] > $party_guests['children'])) {
										//we lower first the adults that we put in this room, only if there are children in the party and if the children in the party are more than the 'max_children' of this room
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
							$solution_guests['adults'] += $roomsol['toadult'] * $roomsol['minrooms'];
							$solution_guests['children'] += $roomsol['tochild'] * $roomsol['minrooms'];
							// update 'max_adults' and 'max_children' for this solution (for later guests allocation in $booking_solution)
							$solution[$rid]['toadult'] = $roomsol['toadult'];
							$solution[$rid]['tochild'] = $roomsol['tochild'];
						}
						$solutions[$kdayts] = $solution;
						if ($solution_guests['adults'] < $party_guests['adults'] || $solution_guests['children'] < $party_guests['children']) {
							// the guests we can allocate with the solution of this day are not enough: unset the solution
							unset($solutions[$kdayts]);
							continue;
						}
						// if we get to this point we can suggest a booking solution for the party requested, but in different rooms
						if (!isset($booking_solutions[$kdayts])) {
							$booking_solutions[$kdayts] = [];
						}
						// re-loop over the rooms in this solution to build the booking solution for this day
						$guests_allocated = array(
							'adults' => 0,
							'children' => 0
						);

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
							array_push($booking_solutions[$kdayts], $roomsol);
							// turn flag on and break the loop
							$smaller_fit_found = true;
							break;
						}
						if ($smaller_fit_found) {
							// no need to parse the rooms from the largest to the smallest
							continue;
						}

						foreach ($solution as $rid => $roomsol) {
							// fullfil all the units of this room
							for ($units_taken = 0; $units_taken < $roomsol['minrooms']; $units_taken++) { 
								$current_allocation = array(
									'adults' => 0,
									'children' => 0
								);
								if ($guests_allocated['adults'] < $party_guests['adults']) {
									$humans_taken = $roomsol['toadult'];
									$missing_humans = $party_guests['adults'] - $guests_allocated['adults'];
									$humans_taken = $humans_taken > $missing_humans ? $missing_humans : $humans_taken;
									$current_allocation['adults'] = $humans_taken;
									$guests_allocated['adults'] += $humans_taken;
								}
								if ($guests_allocated['children'] < $party_guests['children']) {
									$humans_taken = $roomsol['tochild'];
									$missing_humans = $party_guests['children'] - $guests_allocated['children'];
									$humans_taken = $humans_taken > $missing_humans ? $missing_humans : $humans_taken;
									$current_allocation['children'] = $humans_taken;
									$guests_allocated['children'] += $humans_taken;
								}
								$roomsol['guests_allocation'] = $current_allocation;
								array_push($booking_solutions[$kdayts], $roomsol);
								if ($guests_allocated['adults'] >= $party_guests['adults'] && $guests_allocated['children'] >= $party_guests['children']) {
									//we have allocated all guests, exit the for-loop
									break;
								}
							}
							if ($guests_allocated['adults'] >= $party_guests['adults'] && $guests_allocated['children'] >= $party_guests['children']) {
								// we have allocated all guests with this solution, no need to loop over other rooms available in this day.
								break;
							}
						}
					}

					break;
				default:
					break;
			}
		}

		$this->code = $code;
		$this->nights = $daysdiff;
		$this->checkin = $fromts;
		$this->checkout = $tots;
		$this->nights_requested = $nights_requested;
		$this->party = $party;
		$this->party_guests = $party_guests;
		$this->suggestions = $suggestions;
		$this->solutions = $solutions;
		$this->booking_solutions = $booking_solutions;
		$this->sug_from_ts = $sug_from_ts;
		$this->sug_to_ts = $sug_to_ts;
		$this->vbo_tn = $vbo_tn;
		$this->split_stay_solutions = $split_stay_solutions;
		//theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'searchsuggestions';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}
		
		/**
		 * 
		 * This view is displayed via Ajax, so the output must be buffered and echoed as JSON-encoded, then exit the process.
		 * This is to avoid printing meta data values of the head. However, we need to receive the request var getjson=1 for bc.
		 * 
		 * @since 	1.13 (J) - 1.3.1 (WP)
		 */
		$getjson = VikRequest::getInt('getjson', 0, 'request');
		if ($getjson) {
			ob_start();
			parent::display($tpl);
			$ajax_buffer = ob_get_contents();
			ob_end_clean();
			echo json_encode(array($ajax_buffer));
			exit;
		}

		parent::display($tpl);
	}
}
