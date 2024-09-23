<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking model pricing.
 *
 * @since 	1.16.10 (J) - 1.6.10 (WP)
 */
class VBOModelPricing extends JObject
{
	/** @var array */
	protected $room_rate_plans = [];

	/** @var array */
	protected $channels_updated_list = [];

	/** @var array */
	protected $channel_warnings = [];

	/** @var array */
	protected $channel_errors = [];

	/**
	 * Proxy for immediately accessing the object and bind data.
	 * 
	 * @param 	array|object  $data  optional data to bind.
	 * @param 	boolean 	  $anew  true for forcing a new instance.
	 * 
	 * @return 	self
	 */
	public static function getInstance($data = [])
	{
		return new static($data);
	}

	/**
	 * Returns the information about rates and restrictions for
	 * a given room in a range of dates.
	 * 
	 * @param 	array 	$options 	Options for getting room rates.
	 * 
	 * @return 	array
	 * 
	 * @throws 	Exception
	 */
	public function getRoomRates(array $options)
	{
		$dbo = JFactory::getDbo();

		// gather options
		$from_date = (string) ($options['from_date'] ?? '');
		$to_date   = (string) ($options['to_date'] ?? '');
		$id_room   = (int) ($options['id_room'] ?? 0);
		$id_price  = (int) ($options['id_price'] ?? 0);
		$all_rplans   = (bool) ($options['all_rplans'] ?? false);
		$restrictions = (bool) ($options['restrictions'] ?? true);

		if (!$from_date || !$to_date) {
			// must be in Y-m-d format
			throw new InvalidArgumentException('Missing dates for applying the new rates or restriction.', 400);
		}

		if (JFactory::getDate($to_date) < JFactory::getDate($from_date)) {
			// invalid dates
			throw new InvalidArgumentException('Invalid dates received.', 400);
		}

		if (!$id_room) {
			throw new InvalidArgumentException('Room record ID is mandatory.', 400);
		}

		/**
		 * Disable season records caching because new rates will have to be re-calculated
		 * for the response by checking the same exact dates.
		 */
		VikBooking::setSeasonsCache(false);

		// load check-in and check-out times
		list($checkin_h, $checkin_m, $checkout_h, $checkout_m) = VBOModelReservation::getInstance()->loadCheckinOutTimes();

		// date format
		$vbo_df = VikBooking::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y/m/d');

		if (!$all_rplans) {
			// get room rates either from the provided rate plan ID or by fetching the main one
			if (!$id_price) {
				// load all rate plans
				$all_rate_plans = VikBooking::getAvailabilityInstance(true)->loadRatePlans();

				// use the first (main) rate plan ID after the automatic sorting
				foreach ($all_rate_plans as $all_rate_plan) {
					$id_price = $all_rate_plan['id'];
					break;
				}
			}

			if (!$id_price) {
				throw new Exception('No rate plans configured.', 500);
			}

			// read the rates for the lowest number of nights for a specific rate plan ID
			$q = "SELECT `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` 
				FROM `#__vikbooking_dispcost` AS `r` 
				INNER JOIN (
					SELECT MIN(`days`) AS `min_days` 
					FROM `#__vikbooking_dispcost` 
					WHERE `idroom`=" . $id_room . " AND `idprice`=" . $id_price . " 
					GROUP BY `idroom`
				) AS `r2` ON `r`.`days`=`r2`.`min_days` 
				LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` AND `p`.`id`=" . $id_price . " 
				WHERE `r`.`idroom`=" . $id_room . " AND `r`.`idprice`=" . $id_price . " 
				GROUP BY `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` 
				ORDER BY `r`.`days` ASC, `r`.`cost` ASC;";
		} else {
			// get room rates from all rate plans configured for the given room
			// read the rates for the lowest number of nights for all rate plans
			$q = "SELECT `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` 
				FROM `#__vikbooking_dispcost` AS `r` 
				INNER JOIN (
					SELECT MIN(`days`) AS `min_days` 
					FROM `#__vikbooking_dispcost` 
					WHERE `idroom`=" . $id_room . " 
					GROUP BY `idroom`
				) AS `r2` ON `r`.`days`=`r2`.`min_days` 
				LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` 
				WHERE `r`.`idroom`=" . $id_room . " 
				GROUP BY `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` 
				ORDER BY `r`.`days` ASC, `r`.`cost` ASC;";
		}

		// load room rates from db
		$dbo->setQuery($q);
		$roomrates = $dbo->loadAssocList();

		foreach ($roomrates as $rrk => $rrv) {
			$roomrates[$rrk]['cost'] = round(($rrv['cost'] / $rrv['days']), 2);
			$roomrates[$rrk]['days'] = 1;
		}

		if (!$roomrates) {
			// terminate the process by throwing an error
			throw new UnexpectedValueException('No rates found for the given room ID.', 400);
		}

		// fetch all restrictions, if requested
		$all_restrictions = $restrictions ? VikBooking::loadRestrictions(true, [$id_room]) : [];

		// calculate global minimum stay
		$glob_minlos = VikBooking::getDefaultNightsCalendar();
		$glob_minlos = $glob_minlos < 1 ? 1 : $glob_minlos;

		// read current room rates
		$current_rates = [];

		// loop through all the requested range of dates
		$start_ts = strtotime($from_date);
		$end_ts = strtotime($to_date);
		$infostart = getdate($start_ts);
		while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
			// calculate timestamps
			$tomorrow_ts = mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']);
			$today_tsin = VikBooking::getDateTimestamp(date($df, $infostart[0]), $checkin_h, $checkin_m);
			$today_tsout = VikBooking::getDateTimestamp(date($df, $tomorrow_ts), $checkout_h, $checkout_m);
			$today_mid_ts = mktime(0, 0, 0, $infostart['mon'], $infostart['mday'], $infostart['year']);

			// calculate tariffs for this day
			$tars = VikBooking::applySeasonsRoom($roomrates, $today_tsin, $today_tsout);

			foreach ($tars as $index => $tar) {
				// apply rounding to 2 decimals at most
				$tars[$index]['cost'] = round($tar['cost'], 2);

				// set formatted cost
				$tars[$index]['formatted_cost'] = VikBooking::numberFormat($tar['cost']);

				// calculate restrictions
				$tars[$index]['restrictions'] = [];
				if ($restrictions) {
					$restr = VikBooking::parseSeasonRestrictions($today_mid_ts, $tomorrow_ts, 1, $all_restrictions);
					if (!$restr) {
						$restr = ['minlos' => $glob_minlos];
					}
					// set day restrictions
					$tars[$index]['restrictions'] = $restr;
				}
			}

			if (!$all_rplans) {
				// set rate for this day (single rate plan)
				$current_rates[(date('Y-m-d', $infostart[0]))] = $tars[0];
			} else {
				// set rates for this day (all rate plans)
				$current_rates[(date('Y-m-d', $infostart[0]))] = $tars;
			}

			// go to next day
			$infostart = getdate($tomorrow_ts);
		}

		return $current_rates;
	}

	/**
	 * Applies new rates and/or restrictions (Min LOS) to the given room and rate plan(s).
	 * Changes are always applied to the website rates, and eventually also on the OTAs.
	 * 
	 * @return 	array
	 * 
	 * @throws 	Exception
	 */
	public function modifyRateRestrictions()
	{
		$dbo = JFactory::getDbo();

		// expected and supported properties binded
		$from_date   = (string) $this->get('from_date', '');
		$to_date     = (string) $this->get('to_date', '');
		$id_room     = (int) $this->get('id_room', 0);
		$id_price    = (int) $this->get('id_price', 0);
		$rplan_name  = $this->get('rplan_name', '');
		$rate        = (float) $this->get('rate', 0);
		$min_los     = (int) $this->get('min_los', 0);
		$upd_otas    = (bool) $this->get('update_otas', true);
		$close_rplan = (bool) $this->get('close_rate_plan', false);

		if (!$from_date || !$to_date) {
			// must be in Y-m-d format
			throw new InvalidArgumentException('Missing dates for applying the new rates or restriction.', 400);
		}

		if (!$id_room) {
			throw new InvalidArgumentException('Room record ID is mandatory.', 400);
		}

		/**
		 * Disable season records caching because new rates will have to be re-calculated
		 * for the response by checking the same exact dates.
		 */
		VikBooking::setSeasonsCache(false);

		// load check-in and check-out times
		list($checkin_h, $checkin_m, $checkout_h, $checkout_m) = VBOModelReservation::getInstance([], true)->loadCheckinOutTimes();

		// date format
		$vbo_df = VikBooking::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y/m/d');

		// access the availability helper
		$av_helper = VikBooking::getAvailabilityInstance(true);

		if (!$id_price) {
			// load all rate plans
			$all_rate_plans = $av_helper->loadRatePlans();

			// use the first rate plan ID after the automatic sorting
			foreach ($all_rate_plans as $all_rate_plan) {
				$id_price = $all_rate_plan['id'];
				break;
			}

			if ($rplan_name) {
				// check if the given rate plan name is found
				$rplan_name = trim(preg_replace("/[^a-z]/i", ' ', $rplan_name));
				foreach ($all_rate_plans as $all_rate_plan) {
					$match_against = trim(preg_replace("/[^a-z]/i", ' ', $all_rate_plan['name']));
					if (stripos($match_against, $rplan_name) !== false || stripos($rplan_name, $match_against) !== false) {
						// use the matched rate plan instead
						$id_price = $all_rate_plan['id'];
						break;
					}
				}
			}
		}

		if (!$id_price) {
			throw new Exception('No rate plans configured.', 500);
		}

		// load the eventually involved derived rate plans from the given rate ID
		$derived_rate_plans = $av_helper->getDerivedRatePlans($id_price);

		// build the list of rate plans involved by adding the requested one
		$rate_plans_pool = [
			$id_price => [
				'id'           => $id_price,
				// the main rate plan selected for the update is NEVER considered as derived, even if it actually was.
				'is_derived'   => 0,
				'derived_data' => null,
				'rate'         => $rate,
			],
		];

		foreach ($derived_rate_plans as $derived_rate_plan) {
			if (isset($rate_plans_pool[$derived_rate_plan['id']])) {
				// skip duplicate rate plan
				continue;
			}

			// calculate new rate for this derived rate plan
			$rplan_derived_rate = $rate;
			if (($derived_rate_plan['derived_data']['mode'] ?? 'discount') == 'discount') {
				// discount rate
				if (($derived_rate_plan['derived_data']['type'] ?? 'percent') == 'percent') {
					// percent value
					$rplan_derived_rate = $rplan_derived_rate * (100 - (float) ($derived_rate_plan['derived_data']['value'] ?? 0)) / 100;
				} else {
					// absolute value
					$rplan_derived_rate -= (float) ($derived_rate_plan['derived_data']['value'] ?? 0);
				}
			} else {
				// increase rate
				if (($derived_rate_plan['derived_data']['type'] ?? 'percent') == 'percent') {
					// percent value
					$rplan_derived_rate = $rplan_derived_rate * (100 + (float) ($derived_rate_plan['derived_data']['value'] ?? 0)) / 100;
				} else {
					// absolute value
					$rplan_derived_rate += (float) ($derived_rate_plan['derived_data']['value'] ?? 0);
				}
			}

			if ($rplan_derived_rate < 0) {
				// negative rates are not allowed
				continue;
			}

			// make sure to apply rounding
			$rplan_derived_rate = round($rplan_derived_rate, 2);

			// push derived rate plan to the update pool
			$rate_plans_pool[$derived_rate_plan['id']] = [
				'id'           => $derived_rate_plan['id'],
				'is_derived'   => 1,
				'derived_data' => $derived_rate_plan['derived_data'],
				'rate'         => $rplan_derived_rate,
			];
		}

		// the newly applied rates
		$newly_rates = [];

		// apply the pricing modification to all the involved rate plans
		foreach ($rate_plans_pool as $rplan_id => $rplan_info) {
			// set rate plan ID
			$now_id_price = $rplan_info['id'];

			// set rate to apply to the current rate plan
			$rate = $rplan_info['rate'];

			// read the rates for the lowest number of nights
			$q = "SELECT `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` 
				FROM `#__vikbooking_dispcost` AS `r` 
				INNER JOIN (
					SELECT MIN(`days`) AS `min_days` 
					FROM `#__vikbooking_dispcost` 
					WHERE `idroom`=" . $id_room . " AND `idprice`=" . $now_id_price . " 
					GROUP BY `idroom`
				) AS `r2` ON `r`.`days`=`r2`.`min_days` 
				LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` AND `p`.`id`=" . $now_id_price . " 
				WHERE `r`.`idroom`=" . $id_room . " AND `r`.`idprice`=" . $now_id_price . " 
				GROUP BY `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` 
				ORDER BY `r`.`days` ASC, `r`.`cost` ASC;";

			$dbo->setQuery($q);
			$roomrates = $dbo->loadAssocList();

			foreach ($roomrates as $rrk => $rrv) {
				$roomrates[$rrk]['cost'] = round(($rrv['cost'] / $rrv['days']), 2);
				$roomrates[$rrk]['days'] = 1;
			}

			if (!$roomrates) {
				if ($rplan_info['is_derived']) {
					// this is not the main rate plan we are updating
					continue;
				}

				// terminate the process by throwing an error
				throw new UnexpectedValueException('No rates found for the given room ID.', 400);
			}

			// turn the rates list into a single-level array
			$roomrates = $roomrates[0];

			// set rate plan name
			$rate_plan_name = $roomrates['name'];

			// read current room rates
			$current_rates = [];
			$start_ts = strtotime($from_date);
			$end_ts = strtotime($to_date);
			$infostart = getdate($start_ts);
			while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
				$tomorrow_ts = mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']);
				$today_tsin = VikBooking::getDateTimestamp(date($df, $infostart[0]), $checkin_h, $checkin_m);
				$today_tsout = VikBooking::getDateTimestamp(date($df, $tomorrow_ts), $checkout_h, $checkout_m);

				$tars = VikBooking::applySeasonsRoom([$roomrates], $today_tsin, $today_tsout);

				// apply rounding to 2 decimals at most
				$tars[0]['cost'] = round($tars[0]['cost'], 2);

				$current_rates[(date('Y-m-d', $infostart[0]))] = $tars[0];

				$infostart = getdate($tomorrow_ts);
			}

			if (!$current_rates) {
				if ($rplan_info['is_derived']) {
					// this is not the main rate plan we are updating
					continue;
				}

				// terminate the process by throwing an error
				throw new UnexpectedValueException('No seasonal rates found for the given room ID.', 400);
			}

			$all_days = array_keys($current_rates);
			$season_intervals = [];
			$firstind = 0;
			$firstdaycost = $current_rates[$all_days[0]]['cost'];
			$nextdaycost = false;
			for ($i = 1; $i < count($all_days); $i++) {
				$ind = $all_days[$i];
				$nextdaycost = $current_rates[$ind]['cost'];
				if ($firstdaycost != $nextdaycost) {
					$interval = [
						'from' => $all_days[$firstind],
						'to'   => $all_days[($i - 1)],
						'cost' => $firstdaycost
					];
					$season_intervals[] = $interval;
					$firstdaycost = $nextdaycost;
					$firstind = $i;
				}
			}
			if ($nextdaycost === false) {
				$interval = [
					'from' => $all_days[$firstind],
					'to'   => $all_days[$firstind],
					'cost' => $firstdaycost
				];
				$season_intervals[] = $interval;
			} elseif ($firstdaycost == $nextdaycost) {
				$interval = [
					'from' => $all_days[$firstind],
					'to' => $all_days[($i - 1)],
					'cost' => $firstdaycost
				];
				$season_intervals[] = $interval;
			}
			foreach ($season_intervals as $sik => $siv) {
				if ((float)$siv['cost'] == $rate) {
					unset($season_intervals[$sik]);
				}
			}

			if (!$season_intervals) {
				// do not raise this error if it was requested to set the restriction or to close a rate plan
				if ((!($min_los > 0) && !$close_rplan) || !$upd_otas) {
					if ($rplan_info['is_derived']) {
						// this is not the main rate plan we are updating
						continue;
					}

					// terminate the process by throwing an error
					throw new RuntimeException('No rates modification needed with the given parameters.', 500);
				}
			}

			if ($rate > 0) {
				// make sure to set a cost greater than zero to avoid errors
				foreach ($season_intervals as $sik => $siv) {
					$first = strtotime($siv['from']);
					$second = strtotime($siv['to']);

					if ($second > 0 && $second == $first) {
						$second += 86399;
					}

					if (!($second > $first)) {
						unset($season_intervals[$sik]);
						continue;
					}

					$baseone = getdate($first);
					$basets = mktime(0, 0, 0, 1, 1, $baseone['year']);
					$sfrom = $baseone[0] - $basets;
					$basetwo = getdate($second);
					$basets = mktime(0, 0, 0, 1, 1, $basetwo['year']);
					$sto = $basetwo[0] - $basets;

					// check leap year
					if ($baseone['year'] % 4 == 0 && ($baseone['year'] % 100 != 0 || $baseone['year'] % 400 == 0)) {
						$leapts = mktime(0, 0, 0, 2, 29, $baseone['year']);
						if ($baseone[0] > $leapts) {
							$sfrom -= 86400;
							/**
							 * To avoid issue with leap years and dates near Feb 29th, we only reduce the seconds if these were reduced
							 * for the from-date of the seasons. Doing it just for the to-date in 2019 for 2020 (leap) produced invalid results.
							 */
							if ($basetwo['year'] % 4 == 0 && ($basetwo['year'] % 100 != 0 || $basetwo['year'] % 400 == 0)) {
								$leapts = mktime(0, 0, 0, 2, 29, $basetwo['year']);
								if ($basetwo[0] > $leapts) {
									$sto -= 86400;
								}
							}
						}
					}

					$tieyear = $baseone['year'];
					$season_type = (float)$siv['cost'] > $rate ? "2" : "1";
					$season_diffcost = $season_type == "1" ? ($rate - (float)$siv['cost']) : ((float)$siv['cost'] - $rate);
					$roomstr = "-" . $id_room . "-,";
					$season_name = date('Y-m-d H:i').' - '.substr($baseone['month'], 0, 3).' '.$baseone['mday'].($siv['from'] != $siv['to'] ? '/'.($baseone['month'] != $basetwo['month'] ? substr($basetwo['month'], 0, 3).' ' : '').$basetwo['mday'] : '');
					$pricestr = "-" . $now_id_price . "-,";

					// build and store season record
					$season_record = new stdClass;
					$season_record->type = $season_type == "1" ? 1 : 2;
					$season_record->from = $sfrom;
					$season_record->to = $sto;
					$season_record->diffcost = $season_diffcost;
					$season_record->idrooms = $roomstr;
					$season_record->spname = $season_name;
					$season_record->wdays = '';
					$season_record->checkinincl = 0;
					$season_record->val_pcent = 1;
					$season_record->losoverride = '';
					$season_record->year = $tieyear;
					$season_record->idprices = $pricestr;

					$dbo->insertObject('#__vikbooking_seasons', $season_record, 'id');
				}
			}
			
			$start_ts = strtotime($from_date);
			$end_ts = strtotime($to_date);
			$infostart = getdate($start_ts);
			$infoend = getdate($end_ts);

			/**
			 * Restrictions can be set only if VCM is enabled because we use the Connector Class.
			 * It is allowed to set just a restriction for the website without any rate modification.
			 * OTAs instead would need a rate to be passed in order to eventually transmit the restrictions.
			 */
			$current_minlos = 0;
			$vboConnector   = null;

			if (method_exists('VikChannelManager', 'getVikBookingConnectorInstance')) {
				// invoke the Connector for any update request
				$vboConnector = VikChannelManager::getVikBookingConnectorInstance();
				// set the caller to 'VBO' to reduce the sleep time between the requests
				$vboConnector->caller = 'VBO';
			} else {
				// make sure the OTA update flag is off
				$upd_otas = false;
			}

			if ($min_los > 0 && $vboConnector) {
				// set the end date to the last second
				$end_ts = mktime(23, 59, 59, $infoend['mon'], $infoend['mday'], $infoend['year']);

				if (!$rplan_info['is_derived']) {
					// create the restriction in VBO (only for the parent rate since it will be a room-level restriction)
					$restr_res = $vboConnector->createRestriction(date('Y-m-d H:i:s', $start_ts), date('Y-m-d H:i:s', $end_ts), [$id_room], [$min_los, '']);

					// update value for the ajax response
					if ($restr_res) {
						$current_minlos = $min_los;
					} else {
						$current_minlos = 'e4j.error.' . $vboConnector->getError();
					}
				} else {
					// always update the minimum stay information
					$current_minlos = $min_los;
				}
			}

			// check if all dates involved share the same price
			$common_rate = -1;

			// prepare output by re-calculating the new rates in real-time
			while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
				$tomorrow_ts = mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']);
				$today_tsin = VikBooking::getDateTimestamp(date($df, $infostart[0]), $checkin_h, $checkin_m);
				$today_tsout = VikBooking::getDateTimestamp(date($df, $tomorrow_ts), $checkout_h, $checkout_m);

				$tars = VikBooking::applySeasonsRoom([$roomrates], $today_tsin, $today_tsout);

				// apply rounding to 2 decimals at most
				$tars[0]['cost'] = round($tars[0]['cost'], 2);

				if ($common_rate < 0) {
					// save first-day common rate
					$common_rate = $tars[0]['cost'];
				} else {
					// check if we've got a different rate for this day
					if ($common_rate != $tars[0]['cost']) {
						// freeze all controls, because this day has got a different cost
						$common_rate = 0;
					}
				}

				$indkey = $infostart['mday'] . '-' . $infostart['mon'] . '-' . $infostart['year'] . '-' . $now_id_price;
				$newly_rates[$indkey] = $tars[0];
				if (is_int($current_minlos) && $current_minlos > 0) {
					$newly_rates[$indkey]['newminlos'] = $current_minlos;
				}

				$infostart = getdate($tomorrow_ts);
			}

			/**
			 * Store a record in the rates flow for this rate modification on VBO.
			 */
			$rflow_handler = VikBooking::getRatesFlowInstance($anew = true);
			if ($rflow_handler !== null) {
				$rflow_record = $rflow_handler->getRecord()
					->setCreatedBy($this->get('_created_by', 'VBO'))
					->setDates($from_date, $to_date)
					->setVBORoomID($id_room)
					->setVBORatePlanID($now_id_price);

				if ($rate > 0) {
					// a new rate was set
					$rflow_record->setNightlyFee($rate);
				}

				if (is_int($current_minlos) && $current_minlos > 0) {
					// push restriction extra data
					$rflow_record->setRestrictions(['minLOS' => $current_minlos]);
				}

				if (method_exists($rflow_record, 'setBaseFee')) {
					$rflow_record->setBaseFee($roomrates['cost']);
				}

				// push rates flow record
				$rflow_handler->pushRecord($rflow_record);

				// store rates flow record
				$rflow_handler->storeRecords();
			}

			// check if restrictions can be transmitted to OTAs in case of no rate given
			if ($upd_otas && $rate <= 0 && is_int($current_minlos) && $current_minlos > 0 && $common_rate > 0) {
				// ensure OTAs will get the minimum stay by using the rate shared by all dates involved
				$rate = $common_rate;
			}

			/**
			 * Channels will only be updated if a rate greater than zero was passed,
			 * and of course if the flag to the update the OTAs is enabled. Restrictions
			 * alone could not be pushed to the OTAs, or rate threshold errors would occur.
			 */
			if ($upd_otas && $rate > 0) {
				// launch channel manager (from VBO, unlikely through the App, we update one rate plan per request)
				$vcm_logos = VikBooking::getVcmChannelsLogo('', true);
				$channels_updated  = [];
				$channels_bkdown   = [];
				$channels_success  = [];
				$channels_warnings = [];
				$channels_errors   = [];

				// load room details
				$q = "SELECT `id`,`name`,`units` FROM `#__vikbooking_rooms` WHERE `id`=" . $id_room . ";";
				$dbo->setQuery($q);
				$row = $dbo->loadAssoc();
				if ($row) {
					$row['channels'] = [];
					// get the mapped channels for this room
					$q = "SELECT * FROM `#__vikchannelmanager_roomsxref` WHERE `idroomvb`=" . $id_room . ";";
					$dbo->setQuery($q);
					foreach ($dbo->loadAssocList() as $ch_data) {
						$row['channels'][$ch_data['idchannel']] = $ch_data;
					}
				}

				if ($row && ($row['channels'] ?? [])) {
					// this room is actually mapped to some channels supporting AV requests
					// load the 'Bulk Action - Rates Upload' cache
					$bulk_rates_cache = VikChannelManager::getBulkRatesCache();

					// we update one rate plan per time, even though we could update all of them with a similar request
					$rates_data = [
						[
							'rate_id' => $now_id_price,
							'cost'    => $rate,
						]
					];

					// build the array with the update details
					$update_rows = [];
					foreach ($rates_data as $rk => $rd) {
						$node = $row;
						$setminlos = '';
						$setmaxlos = '';

						// pass the restrictions to the channels if specified
						if (is_int($current_minlos) && $current_minlos > 0) {
							$setminlos = $current_minlos;
							// max los must have a length > 0 or min los won't be set
							$setmaxlos = '0';
						}

						// check for follow restriction flag in a derived rate plan
						if ($rplan_info['is_derived'] && !((bool) ($rplan_info['derived_data']['follow_restr'] ?? 1))) {
							// unset restriction values for only updating the rates
							$setminlos = '';
							$setmaxlos = '';
						}

						// close rate plan (min or max los do not need to be set)
						if ($close_rplan) {
							// VikBookingConnector class in VCM requires the closure to be concatenated to maxlos
							$setmaxlos .= 'closed';
						}

						// check bulk rates cache to see if the exact rate should be increased for the channels (the exact rate has already been set in VBO at this point of the code)
						if (($bulk_rates_cache[$id_room][$rd['rate_id']] ?? null)) {
							if ((int) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmod'] > 0 && (float) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmodamount'] > 0) {
								if ((int) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmodop'] > 0) {
									// Increase rates
									if ((int) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmodval'] > 0) {
										// Percentage charge
										$rd['cost'] = $rd['cost'] * (100 + (float) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmodamount']) / 100;
									} else {
										// Fixed charge
										$rd['cost'] += (float) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmodamount'];
									}
								} else {
									// Lower rates
									if ((int) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmodval'] > 0) {
										// Percentage discount
										$disc_op = $rd['cost'] * (float) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmodamount'] / 100;
										$rd['cost'] -= $disc_op;
									} else {
										// Fixed discount
										$rd['cost'] -= (float) $bulk_rates_cache[$id_room][$rd['rate_id']]['rmodamount'];
									}
								}
							}
						}

						$node['ratesinventory'] = [
							$from_date.'_'.$to_date.'_'.$setminlos.'_'.$setmaxlos.'_1_2_'.$rd['cost'].'_0',
						];

						$node['pushdata'] = [
							'pricetype'    => $rd['rate_id'],
							'defrate'      => $roomrates['cost'],
							'rplans'       => [],
							'cur_rplans'   => [],
							'rplanarimode' => [],
						];

						// build push data for each channel rate plan according to the Bulk Rates Cache or to the OTA Pricing
						if (($bulk_rates_cache[$id_room][$rd['rate_id']] ?? null)) {
							// Bulk Rates Cache available for this room_id and rate_id
							$node['pushdata']['rplans'] = $bulk_rates_cache[$id_room][$rd['rate_id']]['rplans'];
							$node['pushdata']['cur_rplans'] = $bulk_rates_cache[$id_room][$rd['rate_id']]['cur_rplans'];
							$node['pushdata']['rplanarimode'] = $bulk_rates_cache[$id_room][$rd['rate_id']]['rplanarimode'];
						}

						// check the channels mapped for this room and add what was not found in the Bulk Rates Cache, if anything
						foreach ($node['channels'] as $idchannel => $ch_data) {
							if (!isset($node['pushdata']['rplans'][$idchannel])) {
								// this channel was not found in the Bulk Rates Cache. Read data from OTA Pricing
								$otapricing = json_decode($ch_data['otapricing'], true);
								$ch_rplan_id = '';
								if (is_array($otapricing) && isset($otapricing['RatePlan'])) {
									foreach ($otapricing['RatePlan'] as $rpkey => $rpv) {
										// get the first key (rate plan ID) of the RatePlan array from OTA Pricing
										$ch_rplan_id = $rpkey;
										break;
									}
								}

								// prevent Airbnb from being updated if not for the main rate plan only
								if ($idchannel == VikChannelManagerConfig::AIRBNBAPI) {
									if ($rplan_info['is_derived'] || $this->guessOTASecondaryRatePlan($idchannel, $roomrates, ($bulk_rates_cache[$id_room] ?? []))) {
										// skip this channel from updating a derived/secondary rate plan that would not exist
										$ch_rplan_id = '';
									}
								}

								// make sure an OTA rate plan ID was found
								if (empty($ch_rplan_id)) {
									// exclude this channel from being updated
									unset($node['channels'][$idchannel]);
									continue;
								}

								// set channel rate plan data
								$node['pushdata']['rplans'][$idchannel] = $ch_rplan_id;
								if ($idchannel == VikChannelManagerConfig::BOOKING) {
									// Default Pricing is used by default, when no data available
									$node['pushdata']['rplanarimode'][$idchannel] = 'person';
								}
							}
						}

						// push update node
						$update_rows[] = $node;
					}

					// update rates on the various channels
					$channels_map = [];
					foreach ($update_rows as $update_row) {
						if (!$update_row['channels']) {
							// skip update for this room as no channels are involved
							continue;
						}

						// set channels updated
						foreach ($update_row['channels'] as $ch) {
							if (($channels_updated[$ch['idchannel']] ?? [])) {
								continue;
							}
							$channels_map[$ch['idchannel']] = ucfirst($ch['channel']);
							$ota_logo_url = is_object($vcm_logos) ? $vcm_logos->setProvenience($ch['channel'])->getLogoURL() : false;
							$channel_logo = $ota_logo_url !== false ? $ota_logo_url : '';
							$channels_updated[$ch['idchannel']] = [
								'id' 	=> $ch['idchannel'],
								'name' 	=> ucfirst($ch['channel']),
								'logo' 	=> $channel_logo
							];
						}

						// prepare request data
						$channels_ids = array_keys($update_row['channels']);
						$channels_rplans = [];
						foreach ($channels_ids as $ch_id) {
							$ch_rplan = isset($update_row['pushdata']['rplans'][$ch_id]) ? $update_row['pushdata']['rplans'][$ch_id] : '';
							$ch_rplan .= isset($update_row['pushdata']['rplanarimode'][$ch_id]) ? '='.$update_row['pushdata']['rplanarimode'][$ch_id] : '';
							$ch_rplan .= isset($update_row['pushdata']['cur_rplans'][$ch_id]) && !empty($update_row['pushdata']['cur_rplans'][$ch_id]) ? ':'.$update_row['pushdata']['cur_rplans'][$ch_id] : '';
							$channels_rplans[] = $ch_rplan;
						}

						$channels = [
							implode(',', $channels_ids)
						];
						$chrplans = [
							implode(',', $channels_rplans)
						];
						$nodes = [
							implode(';', $update_row['ratesinventory'])
						];
						$rooms = [$id_room];
						$pushvars = [
							implode(';', [
								$update_row['pushdata']['pricetype'],
								$update_row['pushdata']['defrate'],
							])
						];

						// send the request
						$result = $vboConnector->channelsRatesPush($channels, $chrplans, $nodes, $rooms, $pushvars);
						if ($vc_error = $vboConnector->getError(true)) {
							$channels_errors[] = $vc_error;
							continue;
						}

						// parse the channels update result and compose success, warnings, errors
						$result_pool = json_decode($result, true);
						foreach (($result_pool ?: []) as $rid => $ch_responses) {
							foreach ($ch_responses as $ch_id => $ch_res) {
								if ($ch_id == 'breakdown' || !is_numeric($ch_id)) {
									// get the rates/dates breakdown of the update request
									$bkdown = $ch_res;
									if (is_array($ch_res)) {
										$bkdown = '';
										foreach ($ch_res as $bk => $bv) {
											$bkparts = explode('-', $bk);
											if (count($bkparts) == 6) {
												// breakdown key is usually composed of two dates in Y-m-d concatenated with another "-".
												$bkdown .= 'From ' . implode('-', array_slice($bkparts, 0, 3)) . ' - To ' . implode('-', array_slice($bkparts, 3, 3)) . ': ' . $bv . "\n";
											} else {
												$bkdown .= $bk . ': ' . $bv . "\n";
											}
										}
										// since the Connector does not return breakdown info about the restrictions, we concatenate the response here for the Ajax request
										if ((int)$setminlos > 0) {
											$bkdown = rtrim($bkdown, "\n");
											$bkdown .= ' - Min LOS: ' . $setminlos."\n";
										}
										$bkdown = rtrim($bkdown, "\n");
									}
									if (!isset($channels_bkdown[$ch_id])) {
										$channels_bkdown[$ch_id] = $bkdown;
									} else {
										$channels_bkdown[$ch_id] .= "\n".$bkdown;
									}
									continue;
								}
								$ch_id = (int)$ch_id;
								if (substr($ch_res, 0, 6) == 'e4j.OK') {
									// success
									if (!isset($channels_success[$ch_id])) {
										$channels_success[$ch_id] = $channels_map[$ch_id];
									}
								} elseif (substr($ch_res, 0, 11) == 'e4j.warning') {
									// warning
									if (!isset($channels_warnings[$ch_id])) {
										$channels_warnings[$ch_id] = $channels_map[$ch_id].': '.str_replace('e4j.warning.', '', $ch_res);
									} else {
										$channels_warnings[$ch_id] .= "\n".str_replace('e4j.warning.', '', $ch_res);
									}
									// add the channel also to the successful list in case of Warning
									if (!isset($channels_success[$ch_id])) {
										$channels_success[$ch_id] = $channels_map[$ch_id];
									}
								} elseif (substr($ch_res, 0, 9) == 'e4j.error') {
									// error
									if (!isset($channels_errors[$ch_id])) {
										$channels_errors[$ch_id] = $channels_map[$ch_id].': '.str_replace('e4j.error.', '', $ch_res);
									} else {
										$channels_errors[$ch_id] .= "\n".str_replace('e4j.error.', '', $ch_res);
									}
								}
							}
						}
					}
				}

				if ($channels_updated) {
					/**
					 * We now support chained updates due to derived rate plans.
					 * The "vcm" response property is now an array of objects with equal structure.
					 * 
					 * @since 	1.16.10 (J) - 1.6.10 (WP)
					 */
					if (!isset($newly_rates['vcm'])) {
						$newly_rates['vcm'] = [];
					}

					// build channels response data for the current rate plan
					$channels_response_data = [
						'rplan_id'         => $now_id_price,
						'rplan_name'       => $rate_plan_name,
						'is_derived'       => $rplan_info['is_derived'],
						'channels_updated' => $channels_updated,
					];

					// set these property only if not empty
					if ($channels_bkdown) {
						$channels_response_data['channels_bkdown'] = $channels_bkdown['breakdown'];
					}
					if ($channels_success) {
						$channels_response_data['channels_success'] = $channels_success;
					}
					if ($channels_warnings) {
						$channels_response_data['channels_warnings'] = $channels_warnings;
						// cache channel warnings
						$this->channel_warnings = $channels_warnings;
					}
					if ($channels_errors) {
						$channels_response_data['channels_errors'] = $channels_errors;
						// cache channel errors
						$this->channel_errors = $channels_errors;
					}

					// push channels response data to the pool
					$newly_rates['vcm'][] = $channels_response_data;

					// cache the channels updated list by eventually merging what was already set
					foreach ($channels_updated as $idch => $chinfo) {
						$this->channels_updated_list[$idch] = $chinfo;
					}
				}
			}
		}

		return $newly_rates;
	}

	/**
	 * Returns the information about the channels updated with
	 * the last rates/restrictions modification request, if any.
	 * 
	 * @return 	array
	 */
	public function getChannelsUpdated()
	{
		$channel_details = [];

		$vcm_logos = VikBooking::getVcmChannelsLogo('', true);

		foreach ($this->channels_updated_list as $idchannel => $channel_data) {
			$small_logo_url = $vcm_logos ? $vcm_logos->setProvenience(strtolower($channel_data['name']))->getSmallLogoURL() : '';
			$tiny_logo_url = $vcm_logos ? $vcm_logos->setProvenience(strtolower($channel_data['name']))->getTinyLogoURL() : '';

			// set channel details
			$channel_details[] = [
				'id'   => $channel_data['id'],
				'name' => $channel_data['name'],
				'logo' => $channel_data['logo'],
				'small_logo' => $small_logo_url,
				'tiny_logo'  => $tiny_logo_url,
			];
		}

		return $channel_details;
	}

	/**
	 * Returns the associative list of channel warning messages.
	 * 
	 * @return 	array
	 */
	public function getChannelWarnings()
	{
		return $this->channel_warnings;
	}

	/**
	 * Returns the associative list of channel error messages.
	 * 
	 * @return 	array
	 */
	public function getChannelErrors()
	{
		return $this->channel_errors;
	}

	/**
	 * Detects if we are updating a secondary rate plan, probably not supported by the OTA.
	 * Useful to prevent non-refundable rate plans to be transmitted to channels like Airbnb.
	 * 
	 * @param 	int 	$idchannel 		The channel unique key.
	 * @param 	array 	$room_rates 	The rate plan record details.
	 * @param 	array 	$room_cahe 		The Bulk Rates Cache for the current room-type.
	 * 
	 * @return 	bool 					True if a secondary rate plan was detected.
	 */
	protected function guessOTASecondaryRatePlan($idchannel, array $room_rates, array $room_cache)
	{
		$room_type_id   = $room_rates['idroom'] ?? 0;
		$rate_plan_id   = $room_rates['idprice'] ?? 0;
		$rate_plan_name = $room_rates['name'] ?? 'Standard';

		if (!$room_cache) {
			// unable to perform a detection
			return false;
		}

		// check how many rate plans are linked to the given channel identifier
		$cached_ota_rate_plans = [];

		foreach ($room_cache as $price_id => $plan_cache) {
			if (is_array($plan_cache) && ($plan_cache['rplans'][$idchannel] ?? null)) {
				$cached_ota_rate_plans[] = $price_id;
			}
		}

		if (!$cached_ota_rate_plans) {
			// unable to perform a detection due to missing bulk rates cache data
			return false;
		}

		if (in_array($rate_plan_id, $cached_ota_rate_plans)) {
			// this rate plan was updated through a bulk action, so it's reliable
			return false;
		}

		// access the room rate plan relations
		if (!($this->room_rate_plans[$room_type_id] ?? [])) {
			$this->room_rate_plans[$room_type_id] = VBORoomHelper::getInstance()->getRatePlans($room_type_id);
		}

		if (count(($this->room_rate_plans[$room_type_id] ?: [])) < 2) {
			// this room-type has got just one rate plan assigned, so it must be a parent rate
			return false;
		}

		if (stripos($rate_plan_name, 'Standard') !== false) {
			// we assume this a main rate plan
			return false;
		}

		// this is probably a secondary rate plan for this OTA
		return true;
	}
}
