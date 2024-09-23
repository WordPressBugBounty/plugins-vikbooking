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

class VikbookingViewShowprc extends JViewVikBooking
{
	function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$session = JFactory::getSession();
		$vbo_tn = VikBooking::getTranslator();

		$proomopt = VikRequest::getVar('roomopt', array());
		$proomindex = VikRequest::getVar('roomindex', array());
		$pdays = VikRequest::getInt('days', 0, 'request');
		$pcheckin = VikRequest::getInt('checkin', '', 'request');
		$pcheckout = VikRequest::getInt('checkout', '', 'request');
		$padults = VikRequest::getVar('adults', array());
		$pchildren = VikRequest::getVar('children', array());
		$proomsnum = VikRequest::getInt('roomsnum', 0, 'request');
		$ppkg_id = VikRequest::getInt('pkg_id', 0, 'request');
		$split_stay = VikRequest::getVar('split_stay', array());
		$pitemid = VikRequest::getInt('Itemid', 0, 'request');

		$nowdf = VikBooking::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}

		$rooms = [];
		$arrpeople = [];
		for ($ir = 1; $ir <= $proomsnum; $ir++) {
			$ind = $ir - 1;
			if (!empty($proomopt[$ind])) {
				$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`=" . (int)$proomopt[$ind] . " AND `avail`='1';";
				$dbo->setQuery($q);
				$get_room = $dbo->loadAssoc();
				if ($get_room) {
					$rooms[$ir] = $get_room;
				}
			}
			if (!empty($padults[$ind])) {
				$arrpeople[$ir]['adults'] = intval($padults[$ind]);
			} else {
				$arrpeople[$ir]['adults'] = 0;
			}
			if (!empty($pchildren[$ind])) {
				$arrpeople[$ir]['children'] = intval($pchildren[$ind]);
			} else {
				$arrpeople[$ir]['children'] = 0;
			}
		}
		if (empty($rooms) || count($rooms) != $proomsnum) {
			VikError::raiseWarning('', JText::translate('VBERRSELECTINGROOMS'));
			$app->redirect(JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')));
			exit;
		}
		$vbo_tn->translateContents($rooms, '#__vikbooking_rooms');

		// collect all room IDs involved
		$rooms_involved = [];
		foreach ($rooms as $room_booked) {
			if (!in_array($room_booked['id'], $rooms_involved)) {
				$rooms_involved[] = $room_booked['id'];
			}
		}

		// calculate total nights of stay
		$secdiff = $pcheckout - $pcheckin;
		$daysdiff = $secdiff / 86400;
		if (is_int($daysdiff)) {
			if ($daysdiff < 1) {
				$daysdiff = 1;
			}
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

		if ($pdays != $daysdiff) {
			showSelectVb(JText::translate('VBERRCALCTAR'));
			return;
		}

		// get check-in and check-out dates information
		$checkin_info  = getdate($pcheckin);
		$checkout_info = getdate($pcheckout);

		/**
		 * Check split stay information.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		if (!empty($split_stay) && count($split_stay) == count($proomopt) && count($split_stay) == $proomsnum && $proomsnum > 1) {
			// valid split stay request vars received
			$split_stay_checkins  = [];
			$split_stay_checkouts = [];
			foreach ($split_stay as $sps_k => $split_room) {
				// calculate and set the exact check-in and check-out timestamps for this split-room
				$new_room_checkin  = VikBooking::getDateTimestamp($split_room['checkin'], $checkin_info['hours'], $checkin_info['minutes'], $checkin_info['seconds']);
				$new_room_checkout = VikBooking::getDateTimestamp($split_room['checkout'], $checkout_info['hours'], $checkout_info['minutes'], $checkout_info['seconds']);
				$split_stay_checkins[]  = $new_room_checkin;
				$split_stay_checkouts[] = $new_room_checkout;
				// update split stay information
				$split_room['checkin_ts']  = $new_room_checkin;
				$split_room['checkout_ts'] = $new_room_checkout;
				$split_stay[$sps_k] = $split_room;
			}
			// validate minimum and maximum stay dates for the split stay
			if (empty($split_stay_checkins) || empty($split_stay_checkouts)) {
				// error, unset any value for the split stay
				$split_stay = [];
			} elseif (min($split_stay_checkins) != $pcheckin) {
				// error, unset any value for the split stay
				$split_stay = [];
			} elseif (max($split_stay_checkouts) != $pcheckout) {
				// error, unset any value for the split stay
				$split_stay = [];
			}
		} else {
			// unset any possible value as it's invalid
			$split_stay = [];
		}

		// modify booking
		$mod_booking   = [];
		$skip_busy_ids = [];
		$only_non_ref  = false;
		$cur_mod = $session->get('vboModBooking', '');
		if (is_array($cur_mod) && count($cur_mod)) {
			$mod_booking = $cur_mod;
			$skip_busy_ids = VikBooking::loadBookingBusyIds($mod_booking['id']);
			/**
			 * Booking modification should check if a non refundable rate plan was
			 * previously selected so that moving to a refundable rate will be denied.
			 * 
			 * @since 	1.15.3 (J) - 1.5.5 (WP)
			 */
			$mod_booking_rooms 	= VikBooking::loadOrdersRoomsData($mod_booking['id']);
			$mod_booking_rplans = [];
			$mod_booking_tars 	= [];
			foreach ($mod_booking_rooms as $mod_booking_room) {
				if (!empty($mod_booking_room['idtar']) && !in_array($mod_booking_room['idtar'], $mod_booking_tars)) {
					$mod_booking_tars[] = $mod_booking_room['idtar'];
				}
			}
			if (count($mod_booking_tars)) {
				$q = "SELECT `idprice` FROM `#__vikbooking_dispcost` WHERE `id` IN (" . implode(', ', $mod_booking_tars) . ") GROUP BY `idprice`;";
				$dbo->setQuery($q);
				$mod_booking_rplans = $dbo->loadAssocList();
			}
			$only_non_ref = VikBooking::findNonRefundableRates($mod_booking_rplans);
		}

		// check that room(s) are available
		$groupdays = VikBooking::getGroupDays($pcheckin, $pcheckout, $daysdiff);
		$morehst = VikBooking::getHoursRoomAvail() * 3600;
		$goonunits = true;
		$rooms_counts = [];
		$all_busy = VikBooking::loadBusyRecords($rooms_involved, time(), $pcheckout);
		foreach ($rooms as $num => $r) {
			// room busy records
			$busy = isset($all_busy[$r['id']]) ? $all_busy[$r['id']] : [];
			// determine the days to consider for the count of the availability
			$use_groupdays = $groupdays;
			if (!empty($split_stay) && !empty($split_stay[($num - 1)]) && $split_stay[($num - 1)]['idroom'] == $r['id']) {
				$split_room = $split_stay[($num - 1)];
				$use_groupdays = VikBooking::getGroupDays($split_room['checkin_ts'], $split_room['checkout_ts'], (int)$split_room['nights']);
			}
			foreach ($use_groupdays as $gday) {
				$bfound = 0;
				foreach ($busy as $bu) {
					if (in_array($bu['id'], $skip_busy_ids)) {
						// booking modification
						continue;
					}
					if ($gday >= $bu['checkin'] && $gday <= ($morehst + $bu['checkout'])) {
						$bfound++;
					}
				}
				if ($bfound >= $r['units']) {
					$goonunits = false;
					break;
				}
			}
			if (!isset($rooms_counts[$r['id']])) {
				$rooms_counts[$r['id']] = [
					'name'  => $r['name'],
					'units' => $r['units'],
					'count' => 0,
				];
			}
			// increase counter for this room ID in case multiple units of this same room are asked
			$rooms_counts[$r['id']]['count']++;
		}

		if (!$goonunits) {
			showSelectVb(JText::translate('VBROOMNOTRIT') . " " . date($df . ' H:i', $pcheckin) . " " . JText::translate('VBROOMNOTCONSTO') . " " . date($df . ' H:i', $pcheckout));
			return;
		}

		// validate multiple units of the same room type ID
		foreach ($rooms_counts as $idr => $unitused) {
			if ($unitused['count'] > $unitused['units']) {
				VikError::raiseWarning('', JText::sprintf('VBERRROOMUNITSNOTAVAIL', $unitused['count'], $unitused['name']));
				$app->redirect(JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')));
				$goonunits = false;
				break;
			}
		}

		// collect closed rate plans information
		$all_rooms = [];
		foreach ($rooms as $num => $r) {
			if (!in_array($r['id'], $all_rooms)) {
				$all_rooms[] = $r['id'];
			}
		}
		$roomrpclosed = VikBooking::getRoomRplansClosedInDates($all_rooms, $pcheckin, $daysdiff);

		// prepare data
		$tars = [];
		$hoursdiff = VikBooking::countHoursToArrival($pcheckin);
		$aretherefares = true;
		
		// prepare tariffs available
		foreach ($rooms as $num => $r) {
			// determine the number of nights of stay and dates to consider
			$use_los = (int)$daysdiff;
			$room_checkin  = $pcheckin;
			$room_checkout = $pcheckout;
			if (!empty($split_stay) && !empty($split_stay[($num - 1)]) && $split_stay[($num - 1)]['idroom'] == $r['id']) {
				$use_los = (int)$split_stay[($num - 1)]['nights'];
				$room_checkin  = $split_stay[($num - 1)]['checkin_ts'];
				$room_checkout = $split_stay[($num - 1)]['checkout_ts'];
			}

			// fetch rates
			$q = "SELECT `d`.*,`p`.`minlos`,`p`.`minhadv` FROM `#__vikbooking_dispcost` AS `d` LEFT JOIN `#__vikbooking_prices` AS `p` ON `p`.`id`=`d`.`idprice` WHERE `d`.`days`=" . $use_los . " AND `d`.`idroom`=" . (int)$r['id'] . ($only_non_ref === true ? ' AND `p`.`free_cancellation`=0' : '') . " ORDER BY `d`.`cost` ASC;";
			$dbo->setQuery($q);
			$tar = $dbo->loadAssocList();
			if (!$tar) {
				$aretherefares = false;
				break;
			}

			// closed rate plans on these dates
			if (count($roomrpclosed) && array_key_exists($r['id'], $roomrpclosed)) {
				foreach ($tar as $kk => $tt) {
					if (array_key_exists('idprice', $tt) && array_key_exists($tt['idprice'], $roomrpclosed[$r['id']])) {
						unset($tar[$kk]);
					}
				}
			}

			// rate plans with a minlos, or with a min hours in advance
			foreach ($tar as $kk => $tt) {
				if (!empty($tt['minlos']) && $tt['minlos'] > $use_los) {
					unset($tar[$kk]);
				} elseif ($hoursdiff < $tt['minhadv']) {
					unset($tar[$kk]);
				}
			}

			if (!count($tar)) {
				$aretherefares = false;
				break;
			}

			// reset values
			$tar = array_values($tar);

			// apply seasonal rates
			$tar = VikBooking::applySeasonsRoom($tar, $room_checkin, $room_checkout);

			// apply OBP rules
			$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $r, $arrpeople[$num]['adults']);

			$tars[$num] = $tar;
		}

		if ($aretherefares !== true) {
			showSelectVb(JText::translate('VBNOTARFNDSELO'));
			return;
		}

		$pkg = [];
		if (!empty($ppkg_id)) {
			$pkg = VikBooking::validateRoomPackage($ppkg_id, $rooms, $daysdiff, $pcheckin, $pcheckout);
			if (!is_array($pkg) || empty($pkg)) {
				if (is_string($pkg)) {
					VikError::raiseWarning('', $pkg);
				}
				$app->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=packagedetails&pkgid=".$ppkg_id.(!empty($pitemid) ? "&Itemid=".$pitemid : ""), false));
				exit;
			}
		}

		$this->tars = $tars;
		$this->rooms = $rooms;
		$this->roomsnum = $proomsnum;
		$this->arrpeople = $arrpeople;
		$this->checkin = $pcheckin;
		$this->checkout = $pcheckout;
		$this->days = $daysdiff;
		$this->pkg = $pkg;
		$this->mod_booking = $mod_booking;
		$this->split_stay = $split_stay;
		$this->vbo_tn = $vbo_tn;
		//theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'showprc';
			if (is_dir($thdir)) {
				$this->_setPath('template', $thdir.DS);
			}
		}

		/**
		 * We append to the booking process the rooms indexes booked through the interactive map, if any.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		if (count($proomindex) == count($rooms)) {
			$only_empty_indexes = true;
			foreach ($proomindex as $rindex) {
				if ((int)$rindex > 0) {
					// a true room index was selected
					$only_empty_indexes = false;
					break;
				}
			}
			if ($only_empty_indexes) {
				// we don't need to pass along the room indexes
				$proomindex = [];
			}
		} else {
			$proomindex = [];
		}
		$this->roomindex = $proomindex;

		// VBO 1.11 - push data to tracker
		$rooms_ids = [];
		foreach ($rooms as $ir => $r) {
			$rooms_ids[$ir] = $r['id'];
		}
		VikBooking::getTracker()->pushDates($pcheckin, $pcheckout, $daysdiff)->pushParty($arrpeople)->pushRooms($rooms_ids, array(), $proomindex)->closeTrack();

		parent::display($tpl);
	}
}
