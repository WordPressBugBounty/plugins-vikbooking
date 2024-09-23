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

/**
 * Helper class originally introcued to get the hotel availability and rates.
 */
class TACVBO
{
	/**
	 * @var  bool  whether to return an array.
	 */
	public static $getArray = false;

	/**
	 * @var  	bool 	flag to ignore booking restrictions.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static $ignoreRestrictions = false;

	/**
	 * Error code identifier:
	 * 
	 * 1 = missing/invalid request options.
	 * 2 = invalid authentication.
	 * 3 = no rooms found for the given party.
	 * 4 = not compliant with booking restrictions.
	 * 5 = not compliant with global closing dates.
	 * 6 = no rates defined for the given length of stay.
	 * 7 = no availability for the dates requested (error code 8 might have occurred as well).
	 * 8 = no rooms available due to restrictions at room or rate plan level.
	 * 
	 * @var  	int 	the error code identifier.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected static $errorCode = 0;

	/**
	 * Given the request or injected arguments, calulcates the availability and rates.
	 * 
	 * @param 	array 	$options 	forced options injected rather than request variables.
	 * 
	 * @return 	mixed 				array with values or error description if getArray=true, or void.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) added $options argument.
	 */
	public static function tac_av_l($options = array())
	{
		$dbo = JFactory::getDbo();
		$vbo_tn = VikBooking::getTranslator();

		$response = 'e4j.error';
		$args = $options;

		/**
		 * All the calls to VikRequest's methods should not use 'request' as 3rd argument, 
		 * but rather 'default' to get the right $input object in the VikRequest Class.
		 * This will resolve issues after the setVar() method was called by other functions. 
		 * POST and GET requests will still provide non-empty values by using 'default'.
		 * 
		 * @since 	1.10
		 */
		$args['hash'] 		= empty($args['hash']) ? VikRequest::getString('e4jauth', '', 'default') : $args['hash'];
		$args['start_date'] = empty($args['start_date']) ? VikRequest::getString('start_date', '', 'default') : $args['start_date'];
		$args['end_date'] 	= empty($args['end_date']) ? VikRequest::getString('end_date', '', 'default') : $args['end_date'];
		$args['nights'] 	= empty($args['nights']) ? VikRequest::getInt('nights', 1, 'default') : $args['nights'];
		$args['num_rooms'] 	= empty($args['num_rooms']) ? VikRequest::getInt('num_rooms', 1, 'default') : $args['num_rooms'];

		$args['start_ts'] 	= strtotime($args['start_date']);
		$args['end_ts'] 	= strtotime($args['end_date']);

		$valid = true;
		$mandatory_keys = array(
			'start_date',
			'end_date',
			'nights',
			'num_rooms',
		);
		foreach ($mandatory_keys as $man_key) {
			$valid = $valid && isset($args[$man_key]) && !empty($args[$man_key]);
		}

		// request type
		$req_type = VikRequest::getString('req_type', '', 'default');
		$req_type = empty($req_type) && !empty($args['req_type']) ? $args['req_type'] : $req_type;

		// API version
		$tac_apiv = 4;
		// API v4
		$args['num_adults'] = empty($args['num_adults']) ? VikRequest::getInt('num_adults', 1, 'request') : $args['num_adults'];
		// API v5
		$args['adults'] = empty($args['adults']) ? VikRequest::getVar('adults', array()) : $args['adults'];
		$args['children'] = empty($args['children']) ? VikRequest::getVar('children', array()) : $args['children'];
		$args['children_age'] = empty($args['children_age']) ? VikRequest::getVar('children_age', array()) : $args['children_age'];
		if (!empty($args['adults']) && !empty($args['children'])) {
			$tac_apiv = 5;
		}

		if ($tac_apiv == 4) {
			$valid = !empty($args['num_adults']) ? $valid : false;
		} elseif ($tac_apiv == 5) {
			$valid = !empty($args['adults']) ? $valid : false;
		}

		/**
		 * The back-end page Calendar can allow the admin to force bookings in case of
		 * no-availability. Its AJAX requests will contain "only_rates=1" and we compose
		 * an extra array-key with all the fully-booked rooms for the requested dates.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		$only_rates = VikRequest::getInt('only_rates', 0, 'request');
		$only_rates = empty($only_rates) && !empty($args['only_rates']) ? $args['only_rates'] : $only_rates;
		$fullybooked = array();

		if (!$valid) {
			// set error code
			self::$errorCode = 1;

			if (self::$getArray) {
				return $response;
			}
			echo $response;
			exit;
		}

		$checkauth = md5('vbo.e4j.vbo');
		if ($checkauth != $args['hash'] && !count($options)) {
			// set error code
			self::$errorCode = 2;

			$response = 'e4j.error.auth';
			if (self::$getArray) {
				return $response;
			}
			echo $response;
			exit;
		}

		$avail_rooms = array();
		if ($tac_apiv == 5) {
			// compose adults-children array and sql clause
			$arradultsrooms = array();
			$arradultsclause = array();
			$arrpeople = array();
			if (count($args['adults']) > 0) {
				foreach ($args['adults'] as $kad => $adu) {
					$roomnumb = $kad + 1;
					if (strlen($adu)) {
						$numadults = intval($adu);
						if ($numadults >= 0) {
							$arradultsrooms[$roomnumb] = $numadults;
							$arrpeople[$roomnumb]['adults'] = $numadults;
							$strclause = "(`fromadult`<=".$numadults." AND `toadult`>=".$numadults."";
							if (!empty($args['children'][$kad]) && intval($args['children'][$kad]) > 0) {
								$numchildren = intval($args['children'][$kad]);
								$arrpeople[$roomnumb]['children'] = $numchildren;
								$arrpeople[$roomnumb]['children_age'] = isset($args['children_age'][$roomnumb]) && count($args['children_age'][$roomnumb]) ? $args['children_age'][$roomnumb] : array();
								$strclause .= " AND `fromchild`<=".$numchildren." AND `tochild`>=".$numchildren."";
							} else {
								$arrpeople[$roomnumb]['children'] = 0;
								$arrpeople[$roomnumb]['children_age'] = array();
								if (intval($args['children'][$kad]) == 0) {
									$strclause .= " AND `fromchild` = 0";
								}
							}
							$strclause .= " AND `totpeople` >= ".($arrpeople[$roomnumb]['adults'] + $arrpeople[$roomnumb]['children']);
							$strclause .= " AND `mintotpeople` <= ".($arrpeople[$roomnumb]['adults'] + $arrpeople[$roomnumb]['children']);
							$strclause .= ")";
							$arradultsclause[] = $strclause;
						}
					}
				}
			}
			// set $args['adults'] to the number of adults occupying the first room but it could be a party of multiple rooms
			$args['num_adults'] = $arrpeople[1]['adults'];
			// this clause would return one room type for each party type: implode(" OR ", $arradultsclause) - the AND clause must be used rather than OR.
			$q = "SELECT `id`, `units` FROM `#__vikbooking_rooms` WHERE `avail`=1 AND (".implode(" AND ", $arradultsclause).");";
		} else {
			// API v4
			$arrpeople = array();
			$arrpeople[1]['adults'] = $args['num_adults'];
			$arrpeople[1]['children'] = 0;
			$q = "SELECT `id`, `units` FROM `#__vikbooking_rooms` WHERE `avail`=1 AND `toadult`>=".$args['num_adults'].";";
		}
		$dbo->setQuery($q);
		$avail_rooms = $dbo->loadAssocList();
		if (!$avail_rooms) {
			// set error code
			self::$errorCode = 3;

			if (self::$getArray) {
				return array('e4j.error' => 'The Query for fetching the rooms returned an empty result');
			}
			echo json_encode(array('e4j.error' => 'The Query for fetching the rooms returned an empty result'));
			exit;
		}

		// arr[0] = (sec) checkin, arr[1] = (sec) checkout
		$check_in_out = VikBooking::getTimeOpenStore();
		if (is_array($check_in_out)) {
			$args['start_ts'] += $check_in_out[0];
			$args['end_ts'] += $check_in_out[1];
		}

		$room_ids = array();
		for ($i = 0; $i < count($avail_rooms); $i++) {
			$room_ids[$i] = $avail_rooms[$i]['id'];
		}

		$all_restrictions = VikBooking::loadRestrictions(true, $room_ids);
		$glob_restrictions = VikBooking::globalRestrictions($all_restrictions);

		// validate restrictions error message
		$x_restr = count($glob_restrictions) ? VikBooking::validateRoomRestriction($glob_restrictions, getdate($args['start_ts']), getdate($args['end_ts']), $args['nights']) : '';

		if (!self::$ignoreRestrictions && strlen($x_restr) > 0) {
			// set error code
			self::$errorCode = 4;

			if (self::$getArray) {
				return array('e4j.error' => 'Unable to proceed because of booking Restrictions in these dates (' . $x_restr . ')');
			}
			echo json_encode(array('e4j.error' => 'Unable to proceed because of booking Restrictions in these dates (' . $x_restr . ')'));
			exit;
		}

		$nowdf = VikBooking::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		// closing dates
		$err_closingdates = VikBooking::validateClosingDates($args['start_ts'], $args['end_ts'], $df);
		if (!self::$ignoreRestrictions && !empty($err_closingdates)) {
			// set error code
			self::$errorCode = 5;

			if (self::$getArray) {
				return array('e4j.error' => JText::sprintf('VBERRDATESCLOSED', $err_closingdates));
			}
			echo json_encode(array('e4j.error' => JText::sprintf('VBERRDATESCLOSED', $err_closingdates)));
			exit;
		}

		// hours to arrival may affect rate plan restrictions
		$hoursdiff = VikBooking::countHoursToArrival($args['start_ts']);

		// get rates
		$room_ids = array();
		foreach ($avail_rooms as $k => $room) {
			$room_ids[$room['id']] = $room;
		}
		$rates = array();
		$q = "SELECT `p`.*, `r`.`id` AS `r_reference_id`, `r`.`name` AS `r_short_desc`, `r`.`img`, `r`.`units`, `r`.`moreimgs`, `r`.`imgcaptions`, `prices`.`id` AS `price_reference_id`, `prices`.`name` AS `pricename`, `prices`.`breakfast_included`, `prices`.`free_cancellation`, `prices`.`canc_deadline`, `prices`.`minlos`, `prices`.`minhadv` FROM `#__vikbooking_dispcost` AS `p`, `#__vikbooking_rooms` AS `r`, `#__vikbooking_prices` AS `prices` WHERE `r`.`id`=`p`.`idroom` AND `p`.`idprice`=`prices`.`id` AND `p`.`days`=".$args['nights']." AND `r`.`id` IN (".implode(',', array_keys($room_ids)).") ORDER BY `p`.`cost` ASC;";
		$dbo->setQuery($q);
		$rates = $dbo->loadAssocList();
		if (!$rates) {
			// set error code
			self::$errorCode = 6;

			if (self::$getArray) {
				return array('e4j.error' => 'The Query for fetching the rates returned an empty result');
			}
			echo json_encode(array('e4j.error' => 'The Query for fetching the rates returned an empty result'));
			exit;
		}
		$vbo_tn->translateContents($rates, '#__vikbooking_rooms', array('id' => 'r_reference_id', 'r_short_desc' => 'name'));
		$vbo_tn->translateContents($rates, '#__vikbooking_prices', array('id' => 'price_reference_id', 'pricename' => 'name'));
		$arr_rates = array();
		/**
		 * If all results are excluded because of restrictions at rate plan level, we use this flag
		 * to know that the rate plans have a Min LOS or a Min Hours in Advance (Advance Booking Offset).
		 * This is to avoid users to say "why do I get no availability?"
		 * 
		 * @since 	1.12.1
		 */
		$err_rplan_restr = false;
		//
		foreach ($rates as $rate) {
			// rate plans with a minlos, or with a min hours in advance
			if ((!empty($rate['minlos']) && $rate['minlos'] > $args['nights']) || $hoursdiff < $rate['minhadv']) {
				// this flag will tell us that some results were excluded due to restrictions at rate plan level
				$err_rplan_restr = true;
				continue;
			} else {
				// we don't want the properties 'minlos' and 'minhadv' in the response.
				unset($rate['minlos']);
				unset($rate['minhadv']);
			}
			if (!isset($arr_rates[$rate['idroom']])) {
				$arr_rates[$rate['idroom']] = array();
			}
			$arr_rates[$rate['idroom']][] = $rate;
		}

		// flags to understand exactly what's making some rooms unavailable
		$err_rtype_restr = false;
		$err_rtype_booked = false;

		// check availability for the rooms with a rate for this number of nights
		$minus_units = 0;
		if (count($arr_rates) < $args['num_rooms']) {
			$minus_units = $args['num_rooms'] - count($arr_rates);
		}
		foreach ($arr_rates as $k => $datarate) {
			$room = $room_ids[$k];
			$consider_units = $room['units'] - $minus_units;
			if (!VikBooking::roomBookable($room['id'], $consider_units, $args['start_ts'], $args['end_ts']) || $consider_units <= 0) {
				// unset room from results
				unset($arr_rates[$k]);
				// push room as fully booked
				array_push($fullybooked, (int)$room['id']);
				// turn flag on
				$err_rtype_booked = true;
			} else {
				if (!self::$ignoreRestrictions && count($all_restrictions)) {
					$room_restr = VikBooking::roomRestrictions($room['id'], $all_restrictions);
					if (count($room_restr)) {
						if (strlen(VikBooking::validateRoomRestriction($room_restr, getdate($args['start_ts']), getdate($args['end_ts']), $args['nights'])) > 0) {
							// unset room from results
							unset($arr_rates[$k]);
							// turn flag on
							$err_rtype_restr = true;
						}
					}
				}
			}
		}

		if (!$arr_rates) {
			// set error code
			self::$errorCode = $err_rtype_booked ? 7 : 8;

			// build error response
			$err_mess = JText::translate('VBNOROOMSINDATE');
			$err_mess = $err_mess == 'VBNOROOMSINDATE' ? JText::translate('VBO_AV_ECODE_7') : $err_mess;
			$res = array('e4j.error' => $err_mess);
			if ($only_rates && count($fullybooked)) {
				$res['fullybooked'] = $fullybooked;
			}

			if (self::$getArray) {
				return $res;
			}

			// concatenate rate plan restriction errors, if any
			if ($err_rplan_restr === true) {
				$res['e4j.error'] .= ' (Rate Plan Restrictions)';
			}

			echo json_encode($res);
			exit;
		}

		// apply special prices
		$arr_rates = VikBooking::applySeasonalPrices($arr_rates, $args['start_ts'], $args['end_ts']);
		$multi_rates = 1;
		foreach ($arr_rates as $idr => $tars) {
			$multi_rates = count($tars) > $multi_rates ? count($tars) : $multi_rates;
		}
		if ($multi_rates > 1) {
			for ($r = 1; $r < $multi_rates; $r++) {
				$deeper_rates = array();
				foreach ($arr_rates as $idr => $tars) {
					foreach ($tars as $tk => $tar) {
						if ($tk == $r) {
							$deeper_rates[$idr][0] = $tar;
							break;
						}
					}
				}
				if (!count($deeper_rates) > 0) {
					continue;
				}
				$deeper_rates = VikBooking::applySeasonalPrices($deeper_rates, $args['start_ts'], $args['end_ts']);
				foreach ($deeper_rates as $idr => $dtars) {
					foreach ($dtars as $dtk => $dtar) {
						$arr_rates[$idr][$r] = $dtar;
					}
				}
			}
		}
		
		// children ages charge
		$children_sums = array();
		
		// sum charges/discounts per occupancy for each room party
		foreach ($arrpeople as $roomnumb => $party) {
			// charges/discounts per adults occupancy
			foreach ($arr_rates as $r => $rates) {
				$children_charges = VikBooking::getChildrenCharges($r, $party['children'], $party['children_age'], $args['nights']);
				if (count($children_charges) > 0) {
					$children_sums[$r] += $children_charges['total'];
				}
				$diffusageprice = VikBooking::loadAdultsDiff($r, $party['adults']);
				// Occupancy Override - Special Price may be setting a charge/discount for this occupancy while default price had no occupancy pricing
				if (!is_array($diffusageprice)) {
					foreach ($rates as $kpr => $vpr) {
						if (array_key_exists('occupancy_ovr', $vpr) && array_key_exists($party['adults'], $vpr['occupancy_ovr']) && strlen($vpr['occupancy_ovr'][$party['adults']]['value'])) {
							$diffusageprice = $vpr['occupancy_ovr'][$party['adults']];
							break;
						}
					}
					reset($rates);
				}
				if (is_array($diffusageprice)) {
					foreach ($rates as $kpr => $vpr) {
						if ($roomnumb == 1) {
							$arr_rates[$r][$kpr]['costbeforeoccupancy'] = $arr_rates[$r][$kpr]['cost'];
						}
						// Occupancy Override
						if (array_key_exists('occupancy_ovr', $vpr) && array_key_exists($party['adults'], $vpr['occupancy_ovr']) && strlen($vpr['occupancy_ovr'][$party['adults']]['value'])) {
							$diffusageprice = $vpr['occupancy_ovr'][$party['adults']];
						}
						$arr_rates[$r][$kpr]['diffusage'] = $party['adults'];
						if ($diffusageprice['chdisc'] == 1) {
							// charge
							if ($diffusageprice['valpcent'] == 1) {
								// fixed value
								$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $arr_rates[$r][$kpr]['days'] : $diffusageprice['value'];
								$arr_rates[$r][$kpr]['diffusagecost'][$roomnumb] = $aduseval;
								$arr_rates[$r][$kpr]['cost'] += $aduseval;
							} else {
								// percentage value
								$aduseval = $diffusageprice['pernight'] == 1 ? round(($arr_rates[$r][$kpr]['costbeforeoccupancy'] * $diffusageprice['value'] / 100) * $arr_rates[$r][$kpr]['days'], 2) : round(($arr_rates[$r][$kpr]['costbeforeoccupancy'] * $diffusageprice['value'] / 100), 2);
								$arr_rates[$r][$kpr]['diffusagecost'][$roomnumb] = $aduseval;
								$arr_rates[$r][$kpr]['cost'] += $aduseval;
							}
						} else {
							// discount
							if ($diffusageprice['valpcent'] == 1) {
								// fixed value
								$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $arr_rates[$r][$kpr]['days'] : $diffusageprice['value'];
								$arr_rates[$r][$kpr]['diffusagediscount'][$roomnumb] = $aduseval;
								$arr_rates[$r][$kpr]['cost'] -= $aduseval;
							} else {
								// percentage value
								$aduseval = $diffusageprice['pernight'] == 1 ? round(((($arr_rates[$r][$kpr]['costbeforeoccupancy'] / $arr_rates[$r][$kpr]['days']) * $diffusageprice['value'] / 100) * $arr_rates[$r][$kpr]['days']), 2) : round(($arr_rates[$r][$kpr]['costbeforeoccupancy'] * $diffusageprice['value'] / 100), 2);
								$arr_rates[$r][$kpr]['diffusagediscount'][$roomnumb] = $aduseval;
								$arr_rates[$r][$kpr]['cost'] -= $aduseval;
							}
						}
					}
				} elseif ($roomnumb == 1) {
					foreach ($rates as $kpr => $vpr) {
						$arr_rates[$r][$kpr]['costbeforeoccupancy'] = $arr_rates[$r][$kpr]['cost'];
					}
				}
			}
		}

		// if the rooms are given to a party of multiple rooms, multiply the basic rates per room per number of rooms
		for($i = 2; $i <= $args['num_rooms']; $i++) {
			foreach ($arr_rates as $r => $rates) {
				foreach ($rates as $kpr => $vpr) {
					$arr_rates[$r][$kpr]['cost'] += $arr_rates[$r][$kpr]['costbeforeoccupancy'];
				}
			}
		}
		
		// children ages charge
		if (count($children_sums) > 0) {
			foreach ($arr_rates as $r => $rates) {
				if (array_key_exists($r, $children_sums)) {
					foreach ($rates as $kpr => $vpr) {
						$arr_rates[$r][$kpr]['cost'] += $children_sums[$r];
					}
				}
			}
		}
		
		//sort results by price ASC
		$arr_rates = VikBooking::sortResults($arr_rates);

		// compose taxes information
		$ivainclusa = VikBooking::ivaInclusa();
		$rates_ids = array();
		foreach ($arr_rates as $r => $rate) {
			foreach ($rate as $ids) {
				if (!in_array($ids['idprice'], $rates_ids)) {
					$rates_ids[] = $ids['idprice'];
				}
			}
		}
		$tax_rates = array();
		$q = "SELECT `p`.`id`,`t`.`aliq`,`t`.`taxcap` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` `t` ON `p`.`idiva`=`t`.`id` WHERE `p`.`id` IN (".implode(',', $rates_ids).");";
		$dbo->setQuery($q);
		$alltaxrates = $dbo->loadAssocList();
		if ($alltaxrates) {
			foreach ($alltaxrates as $tx) {
				if (!empty($tx['aliq']) && $tx['aliq'] > 0) {
					/**
					 * Tax Cap implementation.
					 * 
					 * @since 	1.12
					 */
					$tax_rates[$tx['id']] = array($tx['aliq'], (float)$tx['taxcap']);
				}
			}
		}
		if (count($tax_rates) > 0) {
			foreach ($arr_rates as $r => $rates) {
				foreach ($rates as $k => $rate) {
					if (array_key_exists($rate['idprice'], $tax_rates)) {
						if (intval($ivainclusa) == 1) {
							// prices tax included
							$realcost = $rate['cost'];
							$tax_oper = ($tax_rates[$rate['idprice']][0] + 100) / 100;
							$taxes = $rate['cost'] - ($rate['cost'] / $tax_oper);
							/**
							 * Tax Cap implementation.
							 * 
							 * @since 	1.12
							 */
							if ($tax_rates[$rate['idprice']][1] > 0 && $taxes > $tax_rates[$rate['idprice']][1]) {
								$taxes = $tax_rates[$rate['idprice']][1];
							}
						} else {
							// prices tax excluded
							$realcost = $rate['cost'] * (100 + $tax_rates[$rate['idprice']][0]) / 100;
							$taxes = $realcost - $rate['cost'];
							/**
							 * Tax Cap implementation.
							 * 
							 * @since 	1.12
							 */
							if ($tax_rates[$rate['idprice']][1] > 0 && $taxes > $tax_rates[$rate['idprice']][1]) {
								$realcost = $rate['cost'] + $tax_rates[$rate['idprice']][1];
								$taxes = $tax_rates[$rate['idprice']][1];
							}
						}
						if ($req_type == 'hotel_availability' || $req_type == 'booking_availability') {
							// always set 'cost' to the base rate tax excluded
							$realcost = $realcost - $taxes;
						}
						$arr_rates[$r][$k]['cost'] = round($realcost, 2);
						$arr_rates[$r][$k]['taxes'] = round($taxes, 2);
					}
				}
			}
			// sum taxes/fees for each room party
			foreach ($arrpeople as $roomnumb => $party) {
				foreach ($arr_rates as $r => $rates) {
					$city_tax_fees = VikBooking::getMandatoryTaxesFees([$r], $party['adults'], $args['nights'], $args['start_ts'], $args['end_ts']);
					foreach ($rates as $k => $rate) {
						if (!isset($arr_rates[$r][$k]['city_taxes'])) {
							$arr_rates[$r][$k]['city_taxes'] = 0;
						}
						if (!isset($arr_rates[$r][$k]['fees'])) {
							$arr_rates[$r][$k]['fees'] = 0;
						}
						$arr_rates[$r][$k]['city_taxes'] += round($city_tax_fees['city_taxes'], 2);
						$arr_rates[$r][$k]['fees'] += round($city_tax_fees['fees'], 2);
					}
				}
			}
		} else {
			foreach ($arr_rates as $r => $rates) {
				foreach ($rates as $k => $rate) {
					$arr_rates[$r][$k]['taxes'] = round(0, 2);
					$arr_rates[$r][$k]['city_taxes'] = round(0, 2);
					$arr_rates[$r][$k]['fees'] = round(0, 2);
				}
			}
		}

		if (self::$getArray) {
			return $arr_rates;
		}

		if ($only_rates && count($fullybooked)) {
			$arr_rates['fullybooked'] = $fullybooked;
		}

		echo json_encode($arr_rates);
		exit;
	}

	/**
	 * Returns the error code to understand what produced an error.
	 * 
	 * @return 	int 	the error code identifier.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function getErrorCode()
	{
		return self::$errorCode;
	}
}
