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

class VikbookingViewOconfirm extends JViewVikBooking
{
	public function display($tpl = null)
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$session = JFactory::getSession();
		$vbo_tn = VikBooking::getTranslator();

		$proomid = VikRequest::getVar('roomid', array());
		$proomindex = VikRequest::getVar('roomindex', array());
		$pdays = VikRequest::getInt('days', 0, 'request');
		$pcheckin = VikRequest::getInt('checkin', 0, 'request');
		$pcheckout = VikRequest::getInt('checkout', 0, 'request');
		$proomsnum = VikRequest::getInt('roomsnum', 0, 'request');
		$padults = VikRequest::getVar('adults', array());
		$pchildren = VikRequest::getVar('children', array());
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
			if (!empty($proomid[$ind])) {
				$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id`=" . (int)$proomid[$ind] . " AND `avail`='1';";
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
		if (count($rooms) != $proomsnum) {
			VikError::raiseWarning('', JText::translate('VBROOMNOTFND'));
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
		if (!empty($split_stay) && count($split_stay) == count($rooms) && count($split_stay) == $proomsnum && $proomsnum > 1) {
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
		$mod_booking = [];
		$skip_busy_ids = [];
		$cur_mod = $session->get('vboModBooking', '');
		if (is_array($cur_mod) && count($cur_mod)) {
			$mod_booking = $cur_mod;
			$skip_busy_ids = VikBooking::loadBookingBusyIds($mod_booking['id']);
		}

		// check that room(s) are available and get the price(s)
		$groupdays = VikBooking::getGroupDays($pcheckin, $pcheckout, $daysdiff);
		$morehst = VikBooking::getHoursRoomAvail() * 3600;
		$validtime = true;
		$prices = [];
		$rooms_counts = [];
		$all_busy = VikBooking::loadBusyRecords($rooms_involved, time(), $pcheckout);
		foreach ($rooms as $num => $r) {
			// get rate plan ID
			$ppriceid = VikRequest::getString('priceid' . $num, '', 'request');
			if (!empty($ppriceid)) {
				$prices[$num] = intval($ppriceid);
			}
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
					$validtime = false;
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

		if (!$validtime) {
			showSelectVb(JText::translate('VBROOMNOTCONS') . " " . date($df . ' H:i', $pcheckin) . " " . JText::translate('VBROOMNOTCONSTO') . " " . date($df . ' H:i', $pcheckout));
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

		if (count($prices) != count($rooms)) {
			showSelectVb(JText::translate('VBNOTARSELECTED'));
			return;
		}

		// load options
		$optionals = [];
		$selopt = [];

		$q = "SELECT `opt`.* FROM `#__vikbooking_optionals` AS `opt` ORDER BY `opt`.`ordering` ASC;";
		$dbo->setQuery($q);
		$optionals = $dbo->loadAssocList();
		if ($optionals) {
			$vbo_tn->translateContents($optionals, '#__vikbooking_optionals');
		}

		// package
		$pkg = [];
		if (!empty($ppkg_id)) {
			$pkg = VikBooking::validateRoomPackage($ppkg_id, $rooms, $daysdiff, $pcheckin, $pcheckout);
			if (!is_array($pkg) || (is_array($pkg) && !(count($pkg) > 0)) ) {
				if (!is_array($pkg)) {
					VikError::raiseWarning('', $pkg);
				}
				$app->redirect(JRoute::rewrite("index.php?option=com_vikbooking&view=packagedetails&pkgid=".$ppkg_id.(!empty($pitemid) ? "&Itemid=".$pitemid : ""), false));
				exit;
			}
		}

		// prepare tariffs available
		$tars = [];
		$validfares = true;
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
			if (!count($pkg)) {
				$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`=" . (int)$r['id'] . " AND `days`=" . $use_los . " AND `idprice`=" . $prices[$num] . ";";
				$dbo->setQuery($q);
				$tar = $dbo->loadAssocList();
				if (!$tar) {
					$validfares = false;
					break;
				}
				$tar = VikBooking::applySeasonsRoom($tar, $room_checkin, $room_checkout);

				// apply OBP rules
				$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $r, $arrpeople[$num]['adults']);

				// push room tariff
				$tars[$num] = $tar;
			}

			// load selected options
			foreach ($optionals as $opt) {
				if (!empty($opt['ageintervals']) && $arrpeople[$num]['children'] > 0) {
					$tmpvar = VikRequest::getInt('optid'.$num.$opt['id'], []);
					if (is_array($tmpvar) && $tmpvar) {
						$opt['quan'] = 1;
						$optagenames = VikBooking::getOptionIntervalsAges($opt['ageintervals']);
						$optagepcent = VikBooking::getOptionIntervalsPercentage($opt['ageintervals']);
						$optageovrct = VikBooking::getOptionIntervalChildOverrides($opt, $arrpeople[$num]['adults'], $arrpeople[$num]['children']);
						$optorigname = $opt['name'];
						foreach ($tmpvar as $child_num => $chvar) {
							$ageintervals_child_string = isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $opt['ageintervals'];
							$optagecosts = VikBooking::getOptionIntervalsCosts($ageintervals_child_string);
							$opt['cost'] = $optagecosts[($chvar - 1)];
							if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1 && ( (array_key_exists($num, $tars) && count($tars[$num]) > 0) || (is_array($pkg) && count($pkg) > 0) )) {
								//percentage value of the adults tariff
								if (is_array($pkg) && count($pkg) > 0) {
									$opt['cost'] = ($pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost']) * $optagecosts[($chvar - 1)] / 100;
								} else {
									$opt['cost'] = $tars[$num][0]['cost'] * $optagecosts[($chvar - 1)] / 100;
								}
							} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2 && ( (array_key_exists($num, $tars) && count($tars[$num]) > 0) || (is_array($pkg) && count($pkg) > 0) )) {
								//VBO 1.10 - percentage value of room base cost
								if (is_array($pkg) && count($pkg) > 0) {
									$opt['cost'] = ($pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost']) * $optagecosts[($chvar - 1)] / 100;
								} else {
									$display_rate = isset($tars[$num][0]['room_base_cost']) ? $tars[$num][0]['room_base_cost'] : $tars[$num][0]['cost'];
									$opt['cost'] = $display_rate * $optagecosts[($chvar - 1)] / 100;
								}
							}
							$opt['name'] = $optorigname.' ('.$optagenames[($chvar - 1)].')';
							$opt['chageintv'] = $chvar;
							$selopt[$num][] = $opt;
						}
					}
				} else {
					$tmpvar = VikRequest::getString('optid'.$num.$opt['id'], '', 'request');
					if (!empty($tmpvar)) {
						$opt['quan'] = $tmpvar;
						// VBO 1.11 - options percentage cost of the room total fee
						if (is_array($pkg) && count($pkg) > 0) {
							$deftar_basecosts = $pkg['pernight_total'] == 1 ? ($pkg['cost'] * $daysdiff) : $pkg['cost'];
						} else {
							$deftar_basecosts = $tars[$num][0]['cost'];
						}
						$opt['cost'] = (int)$opt['pcentroom'] ? ($deftar_basecosts * $opt['cost'] / 100) : $opt['cost'];
						//
						$selopt[$num][] = $opt;
					}
				}
			}
		}

		if ($validfares !== true) {
			showSelectVb(JText::translate('VBTARNOTFOUND'));
			return;
		}

		// load contents from db
		$q = "SELECT * FROM `#__vikbooking_gpayments` WHERE `published`='1' ORDER BY `#__vikbooking_gpayments`.`ordering` ASC;";
		$dbo->setQuery($q);
		$payments = $dbo->loadAssocList();
		if ($payments) {
			$vbo_tn->translateContents($payments, '#__vikbooking_gpayments');
		}

		/**
		 * Validate payment methods assigned to specific room IDs.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0
		 */
		foreach ($payments as $pay_k => $pay_v) {
			if (empty($pay_v['idrooms'])) {
				continue;
			}
			$rooms_supported = json_decode($pay_v['idrooms']);
			if (!is_array($rooms_supported) || !count($rooms_supported)) {
				continue;
			}
			// make sure all rooms being booked are supported
			foreach ($rooms_involved as $room_id_booked) {
				if (!in_array($room_id_booked, $rooms_supported)) {
					// we need to unset this payment option as soon as we find one non-matching room ID
					unset($payments[$pay_k]);
					break;
				}
			}
		}

		// reset payment array keys in case some methods were unset
		$payments = array_values($payments);

		$q = "SELECT * FROM `#__vikbooking_custfields` ORDER BY `#__vikbooking_custfields`.`ordering` ASC;";
		$dbo->setQuery($q);
		$cfields = $dbo->loadAssocList();
		if ($cfields) {
			$vbo_tn->translateContents($cfields, '#__vikbooking_custfields');
		}

		$countries = [];
		foreach ($cfields as $cf) {
			if ($cf['type'] == 'country') {
				$q = "SELECT * FROM `#__vikbooking_countries` ORDER BY `#__vikbooking_countries`.`country_name` ASC;";
				$dbo->setQuery($q);
				$countries = $dbo->loadAssocList();
				break;
			}
		}
		if (!empty($countries) && is_array($countries)) {
			$vbo_tn->translateContents($countries, '#__vikbooking_countries');
		}

		// customer details
		$cpin = VikBooking::getCPinIstance();
		$customer_details = $cpin->loadCustomerDetails();

		// coupon
		$pcouponcode = VikRequest::getString('couponcode', '', 'request');
		$coupon = [];

		/**
		 * Check if the customer has an automatic discount.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$customer_id = null;
		if (!empty($customer_details['id'])) {
			$customer_id = $customer_details['id'];
			$customer_coupon = $cpin->getCustomerCoupon($customer_details, [
				'checkin'  => $pcheckin,
				'checkout' => $pcheckout,
				'days' 	   => $daysdiff,
				'rooms'    => $rooms,
			]);
			if ($customer_coupon) {
				// force the customer coupon code
				$pcouponcode = $customer_coupon['code'];
			}
		}

		if (strlen($pcouponcode) && !count($pkg)) {
			$coupon = VikBooking::getCouponInfo($pcouponcode);
			$valid_customer_coupon = true;
			if (!empty($coupon) && !empty($coupon['customers'])) {
				if (empty($customer_id) || !in_array($customer_id, $coupon['customers'])) {
					$valid_customer_coupon = false;
				}
			}
			if (!empty($coupon) && $valid_customer_coupon) {
				$coupondateok = true;
				$couponroomok = true;
				if (strlen($coupon['datevalid']) > 0) {
					$dateparts = explode("-", $coupon['datevalid']);
					$pickinfo = getdate($pcheckin);
					$dropinfo = getdate($pcheckout);
					$checkpick = mktime(0, 0, 0, $pickinfo['mon'], $pickinfo['mday'], $pickinfo['year']);
					$checkdrop = mktime(0, 0, 0, $dropinfo['mon'], $dropinfo['mday'], $dropinfo['year']);
					if (!($checkpick >= $dateparts[0] && $checkpick <= $dateparts[1] && $checkdrop >= $dateparts[0] && $checkdrop <= $dateparts[1])) {
						$coupondateok = false;
					}
				}
				if (!empty($coupon['minlos']) && $coupon['minlos'] > $daysdiff) {
					$coupondateok = false;
				}
				if ($coupondateok === true) {
					if ($coupon['allvehicles'] == 0) {
						foreach ($rooms as $num => $r) {
							if (!(preg_match("/;".$r['id'].";/i", $coupon['idrooms']))) {
								$couponroomok = false;
								break;
							}
						}
					}
					if ($couponroomok !== true) {
						$coupon = [];
						VikError::raiseWarning('', JText::translate('VBCOUPONINVROOM'));
					}

					/**
					 * Trigger event to allow third-party plugins to implement additional coupon validations or manipulation.
					 * 
					 * @since 	1.16.7 (J) - 1.6.7 (WP)
					 */
					if ($couponroomok) {
						$coupon_validation = VBOFactory::getPlatform()->getDispatcher()->filter('onValidateCouponCode', [&$coupon]);
						if (is_array($coupon_validation) && in_array(false, $coupon_validation, true)) {
							// coupon is not valid, errors should be set by the plugin's hook
							$coupon = [];
						}
					}

				} else {
					$coupon = [];
					VikError::raiseWarning('', JText::translate('VBCOUPONINVDATES'));
				}
			} else {
				if (!$valid_customer_coupon) {
					$coupon = [];
				}
				VikError::raiseWarning('', JText::translate('VBCOUPONNOTFOUND'));
			}
		}

		$this->rooms = $rooms;
		$this->tars = $tars;
		$this->prices = $prices;
		$this->arrpeople = $arrpeople;
		$this->roomsnum = $proomsnum;
		$this->selopt = $selopt;
		$this->days = $daysdiff;
		$this->coupon = $coupon;
		$this->first = $pcheckin;
		$this->second = $pcheckout;
		$this->payments = $payments;
		$this->cfields = $cfields;
		$this->customer_details = $customer_details;
		$this->countries = $countries;
		$this->pkg = $pkg;
		$this->mod_booking = $mod_booking;
		$this->split_stay = $split_stay;
		$this->vbo_tn = $vbo_tn;
		//theme
		$theme = VikBooking::getTheme();
		if ($theme != 'default') {
			$thdir = VBO_SITE_PATH.DS.'themes'.DS.$theme.DS.'oconfirm';
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

		// push initial data to tracker
		$rooms_ids  = [];
		$prices_ids = [];
		foreach ($rooms as $ir => $r) {
			$rooms_ids[$ir]  = $r['id'];
			$prices_ids[$ir] = $prices[$ir];
		}
		VikBooking::getTracker()
			->pushDates($pcheckin, $pcheckout, $daysdiff)
			->pushParty($arrpeople)
			->pushRooms($rooms_ids, $prices_ids, $proomindex);

		// display the View
		parent::display($tpl);

		// close tracking
		VikBooking::getTracker()
			->closeTrack();
	}
}
