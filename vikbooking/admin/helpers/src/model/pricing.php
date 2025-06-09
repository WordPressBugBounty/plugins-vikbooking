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

	/** @var array */
	protected $cached_restrictions = [];

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
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP) added support for multiple room IDs and faster processing.
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
		$id_rooms = array_values(array_filter(array_map('intval', (array) ($options['id_rooms'] ?? []))));

		if (!$from_date || !$to_date) {
			// must be in Y-m-d format
			throw new InvalidArgumentException('Missing dates for applying the new rates or restriction.', 400);
		}

		if (JFactory::getDate($to_date) < JFactory::getDate($from_date)) {
			// invalid dates
			throw new InvalidArgumentException('Invalid dates received.', 400);
		}

		if (!$id_room && !$id_rooms) {
			throw new InvalidArgumentException('Room record ID is mandatory.', 400);
		}

		// collect the list of room IDs involved
		$listing_ids = array_values(array_filter(array_merge([$id_room], $id_rooms)));

		// load check-in and check-out times
		list($checkin_h, $checkin_m, $checkout_h, $checkout_m) = VBOModelReservation::getInstance()->loadCheckinOutTimes();

		// date format
		$vbo_df = VikBooking::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y/m/d');

		// get room rates either from the provided rate plan ID or by fetching the main one
		if (!$all_rplans && !$id_price) {
			// load all rate plans
			$all_rate_plans = VikBooking::getAvailabilityInstance(true)->loadRatePlans();

			// use the first (main) rate plan ID after the automatic sorting
			foreach ($all_rate_plans as $all_rate_plan) {
				$id_price = $all_rate_plan['id'];
				break;
			}

			if (!$id_price) {
				throw new Exception('No rate plans configured.', 500);
			}
		}

		// pool of room rates
		$pool_roomrates = [];

		// iterate over all listing IDs to obtain the respective room rates
		foreach ($listing_ids as $listing_id) {
			if (!$all_rplans) {
				// read the rates for the lowest number of nights for a specific rate plan ID
				$q = "SELECT `r`.`id`,`r`.`idroom`,`r`.`days`,`r`.`idprice`,`r`.`cost`,`p`.`name` 
					FROM `#__vikbooking_dispcost` AS `r` 
					INNER JOIN (
						SELECT MIN(`days`) AS `min_days` 
						FROM `#__vikbooking_dispcost` 
						WHERE `idroom`=" . $listing_id . " AND `idprice`=" . $id_price . " 
						GROUP BY `idroom`
					) AS `r2` ON `r`.`days`=`r2`.`min_days` 
					LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` AND `p`.`id`=" . $id_price . " 
					WHERE `r`.`idroom`=" . $listing_id . " AND `r`.`idprice`=" . $id_price . " 
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
						WHERE `idroom`=" . $listing_id . " 
						GROUP BY `idroom`
					) AS `r2` ON `r`.`days`=`r2`.`min_days` 
					LEFT JOIN `#__vikbooking_prices` `p` ON `p`.`id`=`r`.`idprice` 
					WHERE `r`.`idroom`=" . $listing_id . " 
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

			if ($roomrates) {
				// set room rates to the pool
				$pool_roomrates[$listing_id] = $roomrates;
			}
		}

		if (!$pool_roomrates) {
			// terminate the process by throwing an error
			throw new UnexpectedValueException('No rates found for the given room ID.', 400);
		}

		// fetch all restrictions, if requested
		$all_restrictions = $restrictions ? VikBooking::loadRestrictions(true, $listing_ids) : [];

		// calculate global minimum stay
		$glob_minlos = VikBooking::getDefaultNightsCalendar();
		$glob_minlos = $glob_minlos < 1 ? 1 : $glob_minlos;

		// dates involved
		$start_ts = strtotime($from_date);
		$end_ts = strtotime($to_date);

		// read current room rates
		$current_rates_pool = [];

		/**
		 * Preload seasonal records in favour of CPU usage, but against RAM usage.
		 * 
		 * @since 	1.17.2 (J) - 1.7.2 (WP)
		 */
		$cached_seasons = VikBooking::getDateSeasonRecords($start_ts, ($end_ts + ($checkout_h * 3600)), $listing_ids);

		// loop through all the requested range of dates
		$infostart = getdate($start_ts);
		while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
			// calculate timestamps
			$tomorrow_ts = mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']);
			$today_tsin = VikBooking::getDateTimestamp(date($df, $infostart[0]), $checkin_h, $checkin_m);
			$today_tsout = VikBooking::getDateTimestamp(date($df, $tomorrow_ts), $checkout_h, $checkout_m);
			$today_mid_ts = mktime(0, 0, 0, $infostart['mon'], $infostart['mday'], $infostart['year']);

			// current day key
			$day_key = date('Y-m-d', $infostart[0]);

			if (count($pool_roomrates) > 1 && !$all_rplans) {
				// process all listings at once through the cached season records in case of
				// multiple listings and single rate plan to reduce the work load
				$listing_tars = VikBooking::applySeasonalPrices($pool_roomrates, $today_tsin, $today_tsout, $cached_seasons);

				// scan the tariff results
				foreach ($listing_tars as $listing_id => $tars) {
					// initialize listing rates, if needed
					if (!isset($current_rates_pool[$listing_id])) {
						$current_rates_pool[$listing_id] = [];
					}

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

					// set rate for this day (single rate plan)
					$current_rates_pool[$listing_id][$day_key] = $tars[0];
				}
			} else {
				// iterate over all listing IDs involved
				foreach ($listing_ids as $listing_id) {
					if (!isset($pool_roomrates[$listing_id])) {
						continue;
					}

					// initialize listing rates, if needed
					if (!isset($current_rates_pool[$listing_id])) {
						$current_rates_pool[$listing_id] = [];
					}

					// calculate listing tariffs for this day
					$tars = VikBooking::applySeasonsRoom($pool_roomrates[$listing_id], $today_tsin, $today_tsout, [], $cached_seasons);

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
						$current_rates_pool[$listing_id][$day_key] = $tars[0];
					} else {
						// set rates for this day (all rate plans)
						$current_rates_pool[$listing_id][$day_key] = $tars;
					}
				}
			}

			// go to next day
			$infostart = getdate($tomorrow_ts);
		}

		// free memory up
		unset($cached_seasons);

		if ($id_room && isset($current_rates_pool[$id_room])) {
			// single listing room rates requested
			return $current_rates_pool[$id_room];
		}

		// return the calculated rates for all listings
		return $current_rates_pool;
	}

	/**
	 * Applies new rates and/or restrictions to the given room and rate plan(s).
	 * Changes are always applied to the website rates, and eventually also on the OTAs.
	 * 
	 * @return 	array
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP) added support to CTA, CTD and Max LOS restrictions.
	 */
	public function modifyRateRestrictions()
	{
		$dbo = JFactory::getDbo();

		// expected and supported properties binded
		$from_date    = (string) $this->get('from_date', '');
		$to_date      = (string) $this->get('to_date', '');
		$id_room      = (int) $this->get('id_room', 0);
		$id_price     = (int) $this->get('id_price', 0);
		$rplan_name   = $this->get('rplan_name', '');
		$rate         = (float) $this->get('rate', 0);
		$min_los      = (int) $this->get('min_los', 0);
		$max_los      = (int) $this->get('max_los', 0);
		$cta_wdays    = (array) $this->get('cta_wdays', []);
		$ctd_wdays    = (array) $this->get('ctd_wdays', []);
		$upd_otas     = (bool) $this->get('update_otas', true);
		$close_rplan  = (bool) $this->get('close_rate_plan', false);
		$merge_restr  = (bool) $this->get('merge_restrictions', true);
		$ota_pricing  = (array) $this->get('ota_pricing', []);
		$skip_derived = (bool) $this->get('skip_derived', false);

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

		// load all rate plans
		$all_rate_plans = $av_helper->loadRatePlans(true);

		if (!$id_price) {
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
		$derived_rate_plans = $skip_derived ? [] : $av_helper->getDerivedRatePlans($id_price);

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

			// dates involved
			$start_ts = strtotime($from_date);
			$end_ts = strtotime($to_date);

			/**
			 * Preload seasonal records in favour of CPU usage, but against RAM usage.
			 * 
			 * @since 	1.17.2 (J) - 1.7.2 (WP)
			 */
			$cached_seasons = VikBooking::getDateSeasonRecords($start_ts, ($end_ts + ($checkout_h * 3600)), [$id_room]);

			// read current room rates
			$current_rates = [];
			$infostart = getdate($start_ts);
			while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
				$tomorrow_ts = mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']);
				$today_tsin = VikBooking::getDateTimestamp(date($df, $infostart[0]), $checkin_h, $checkin_m);
				$today_tsout = VikBooking::getDateTimestamp(date($df, $tomorrow_ts), $checkout_h, $checkout_m);

				// apply seasonal rates by injecting the cached seasonal records
				$tars = VikBooking::applySeasonsRoom([$roomrates], $today_tsin, $today_tsout, [], $cached_seasons);

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
									$sto -= date('d-m', $baseone[0]) != '31-12' && date('d-m', $basetwo[0]) == '31-12' ? 1 : 86400;
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

					/**
					 * Push the newly created season record to the list of preloaded records.
					 * 
					 * @since 	1.17.2 (J) - 1.7.2 (WP)
					 */
					$cached_seasons[] = (array) $season_record;
				}
			}

			// calculate the involved dates
			$start_ts  = strtotime($from_date);
			$end_ts    = strtotime($to_date);
			$infostart = getdate($start_ts);
			$infoend   = getdate($end_ts);

			/**
			 * Restrictions can be set only if VCM is enabled because we use the Connector Class.
			 * It is allowed to set just a restriction for the website without any rate modification.
			 * OTAs instead would need a rate to be passed in order to eventually transmit the restrictions.
			 */
			$current_minlos = 0;
			$current_maxlos = 0;
			$current_cta    = [];
			$current_ctd    = [];
			$split_ct_nodes = [];
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

			// minimum length of stay is always necessary for creating a restriction even with other modifiers
			if ($min_los > 0 && $vboConnector) {
				// set the end date to the last second
				$end_ts = mktime(23, 59, 59, $infoend['mon'], $infoend['mday'], $infoend['year']);

				/**
				 * Setting just a min los restriction may lift a previously defined CTA/CTD rule for the same dates.
				 * If merging restrictions is not disabled, and if no other modifiers are set (max los, cta, ctd), the
				 * system will automatically calculate the current modifiers in order to keep them on the OTAs. Such
				 * calculated restriction modifiers will not need to be created on VikBooking, but just passed to the OTAs.
				 * If conflicting restriction modifiers are detected, it is recommended to schedule an auto bulk action.
				 * 
				 * @since 	1.17.1 (J) - 1.7.1 (WP)
				 */
				if ($merge_restr && !$max_los && !$cta_wdays && !$ctd_wdays) {
					// calculate the restriction modifiers beside the min los and get additional details
					list($calc_maxlos, $calc_cta, $calc_ctd, $split_ct_nodes, $conflicts) = $this->calculateRestrictionModifiers($start_ts, $end_ts, $id_room);

					if ($calc_maxlos) {
						// set calculated max los
						$max_los = $calc_maxlos;
					}

					if ($calc_cta) {
						// set calculated cta week days
						$cta_wdays = $calc_cta;
					}

					if ($calc_ctd) {
						// set calculated ctd week days
						$ctd_wdays = $calc_ctd;
					}
				}

				if (!$rplan_info['is_derived']) {
					// build the minimum stay restriction string, eventually inclusive of CTA/CTD rules
					$vbo_min_los_str = $min_los;
					if ($cta_wdays) {
						// append cta instructions
						$vbo_min_los_str .= 'CTA[' . implode(',', $cta_wdays) . ']';
					}
					if ($ctd_wdays) {
						// append ctd instructions
						$vbo_min_los_str .= 'CTD[' . implode(',', $ctd_wdays) . ']';
					}

					// create the restriction in VBO (only for the parent rate since it will be a room-level restriction)
					$restr_res = $vboConnector->createRestriction(date('Y-m-d H:i:s', $start_ts), date('Y-m-d H:i:s', $end_ts), [$id_room], [$vbo_min_los_str, $max_los]);

					// update values for the response and the rest of the operations
					if ($restr_res) {
						$current_minlos = $min_los;
						$current_maxlos = $max_los;
						$current_cta = $cta_wdays;
						$current_ctd = $ctd_wdays;
					} else {
						$current_minlos = 'e4j.error.' . $vboConnector->getError();
					}
				} else {
					// always update the min/max stay information
					$current_minlos = $min_los;
					$current_maxlos = $max_los;
					$current_cta = $cta_wdays;
					$current_ctd = $ctd_wdays;
				}
			}

			/**
			 * Ensure the room and rate plan effective min/max LOS is applied (room-rate level restrictions).
			 * 
			 * @since 	1.18.0 (J) - 1.8.0 (WP)
			 */
			if (is_int($current_minlos) && $current_minlos > 0) {
				$effective_min_los = VBORoomHelper::calcEffectiveMinLOS($id_room, $now_id_price);
				$effective_max_los = VBORoomHelper::calcEffectiveMaxLOS($id_room, $now_id_price);

				// if we have a weekly rate plan, the minimum stay should always be forced regardless of room-level restrictions
				if ($effective_min_los > 1 && $current_minlos < $effective_min_los) {
					$current_minlos = $effective_min_los;
				}

				// if we have a one-night rate plan, the maximum stay should always be 1 regardless of room-level restrictions
				if ($effective_max_los === 1) {
					$current_minlos = $effective_max_los;
					$current_maxlos = $effective_max_los;
				}

				// ensure the minimum stay is less than or equal to the maximum stay
				if (is_int($current_maxlos) && $current_maxlos > 0 && $current_minlos > $current_maxlos) {
					$current_minlos = $current_maxlos;
				}
			}

			// check if all dates involved share the same price
			$common_rate = -1;

			// prepare output by re-calculating the new rates in real-time
			while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
				$tomorrow_ts = mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']);
				$today_tsin = VikBooking::getDateTimestamp(date($df, $infostart[0]), $checkin_h, $checkin_m);
				$today_tsout = VikBooking::getDateTimestamp(date($df, $tomorrow_ts), $checkout_h, $checkout_m);

				// apply seasonal rates by injecting the cached seasonal records
				$tars = VikBooking::applySeasonsRoom([$roomrates], $today_tsin, $today_tsout, [], $cached_seasons);

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

			// free memory up
			unset($cached_seasons);

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
					$rflow_record->setRestrictions([
						'minLOS' => $current_minlos,
						'maxLOS' => $current_maxlos,
						'cta'    => (bool) $current_cta,
						'ctd'    => (bool) $current_ctd,
					]);
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

					// check for custom ota pricing overrides
					if ($ota_pricing) {
						// build ota pricing overrides for each channel
						$ota_pricing_overrides = [];

						foreach ($ota_pricing as $ota_id => $pricing_command) {
							if (!preg_match("/^(\+|\-)[0-9]+(\.|\,)?([0-9]+)?(\%|\*)$/", $pricing_command)) {
								// invalid pricing instructions
								continue;
							}

							// get pricing command
							$rmodop = substr($pricing_command, 0, 1);
							$rmodval = substr($pricing_command, -1, 1);
							$rmodamount = (float) str_replace([$rmodop, $rmodval], '', $pricing_command);

							if ($rmodamount <= 0) {
								// invalid rate amount factor
								continue;
							}

							// build ota pricing instructions
							$ota_pricing_overrides[$ota_id] = [
								// modify rates
								1,
								// increase or decrease
								($rmodop == '+' ? 1 : 0),
								// amount
								$rmodamount,
								// percent or absolute
								($rmodval == '%' ? 1 : 0),
							];
						}

						if ($ota_pricing_overrides && method_exists($vboConnector, 'setOTAPricingOverrides')) {
							/**
							 * Set OTA pricing instruction overrides.
							 * 
							 * @since 		1.17.2 (J) - 1.7.2 (WP)
							 * 
							 * @requires 	VCM >= 1.9.4
							 */
							$vboConnector->setOTAPricingOverrides($ota_pricing_overrides);
						}
					}

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
						$setctawdays = '';
						$setctdwdays = '';

						// pass the restrictions to the channels if specified
						if (is_int($current_maxlos) && $current_maxlos > 0) {
							// set max los first
							$setmaxlos = $current_maxlos;
						}
						if (is_int($current_minlos) && $current_minlos > 0) {
							// set min los after
							$setminlos = $current_minlos;
							// max los must have a length > 0 or min los won't be set
							$setmaxlos = $setmaxlos ?: '0';
							// eavaluate whether cta/ctd week-days should be added
							if ($current_cta) {
								$setctawdays = 'CTA[' . implode(',', $current_cta) . ']';
							}
							if ($current_ctd) {
								$setctdwdays = 'CTD[' . implode(',', $current_ctd) . ']';
							}
						}

						// check for follow restriction flag in a derived rate plan
						if ($rplan_info['is_derived'] && !((bool) ($rplan_info['derived_data']['follow_restr'] ?? 1))) {
							// unset restriction values for only updating the rates
							$setminlos = '';
							$setmaxlos = '';
							$setctawdays = '';
							$setctdwdays = '';
						}

						// close rate plan (min or max los do not need to be set)
						if ($close_rplan) {
							// VikBookingConnector class in VCM requires the closure to be concatenated to maxlos
							$setmaxlos .= 'closed';
						}

						// check bulk rates cache to see if the exact rate should be increased for the channels (the exact rate has already been set in VBO at this point of the code)
						if (!$ota_pricing && ($bulk_rates_cache[$id_room][$rd['rate_id']] ?? null)) {
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

						// set rate inventory node(s)
						if (count($split_ct_nodes) > 1 && $setminlos && ($current_cta || $current_ctd)) {
							// there will be multiple OTA rate inventory nodes for better accuracy and avoid conflicts with cta/ctd rules
							$node['ratesinventory'] = [];

							foreach ($split_ct_nodes as $split_ct_node) {
								// calculate proper cta/ctd strings for this date interval
								$setctawdays_range = $split_ct_node['cta'] ? 'CTA[' . implode(',', $split_ct_node['cta']) . ']' : '';
								$setctdwdays_range = $split_ct_node['ctd'] ? 'CTD[' . implode(',', $split_ct_node['ctd']) . ']' : '';

								// set rate inventory node for the properly calculated range of dates, cta and ctd rules
								$node['ratesinventory'][] = implode('_', [
									$split_ct_node['from_dt'],
									$split_ct_node['to_dt'],
									$setminlos . $setctawdays_range . $setctdwdays_range,
									$setmaxlos,
									1,
									2,
									$rd['cost'],
									0,
								]);
							}
						} else {
							// regular rate inventory node
							$node['ratesinventory'] = [
								$from_date . '_' . $to_date . '_' . $setminlos . $setctawdays . $setctdwdays . '_' . $setmaxlos . '_1_2_' . $rd['cost'] . '_0',
							];
						}

						// set rate data
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
								// this channel was not found in the Bulk Rates Cache
								$ota_mapping_pricing = (array) json_decode($ch_data['otapricing'], true);

								if (defined('VikChannelManagerConfig::EXPEDIA') && $idchannel == VikChannelManagerConfig::EXPEDIA) {
									// make sure to sort the Expedia rate plans accordingly
									$ota_mapping_pricing = VikChannelManager::sortExpediaChannelPricing($ota_mapping_pricing);
								}

								// read data from ota mapping pricing
								$ch_rplan_id = '';
								if (isset($ota_mapping_pricing['RatePlan'])) {
									foreach ($ota_mapping_pricing['RatePlan'] as $rpkey => $rpv) {
										// get the first key (rate plan ID) of the RatePlan array from OTA Pricing
										$ch_rplan_id = $rpkey;
										break;
									}
								}

								// build a list of OTAs NOT supporting rate plans
								$ota_single_rplan = [];
								if (defined('VikChannelManagerConfig::AIRBNBAPI')) {
									$ota_single_rplan[] = VikChannelManagerConfig::AIRBNBAPI;
								}
								if (defined('VikChannelManagerConfig::VRBOAPI')) {
									$ota_single_rplan[] = VikChannelManagerConfig::VRBOAPI;
								}

								// prevent channel from being updated if not directly involved
								$vbo_single_rplan   = count($all_rate_plans) === 1;
								$is_secondary_rplan = $this->guessOTASecondaryRatePlan($idchannel, $roomrates, ($bulk_rates_cache[$id_room] ?? []));
								$is_google_platform = defined('VikChannelManagerConfig::GOOGLEHOTEL') && $idchannel == VikChannelManagerConfig::GOOGLEHOTEL;
								$is_google_platform = $is_google_platform || (defined('VikChannelManagerConfig::GOOGLEVR') && $idchannel == VikChannelManagerConfig::GOOGLEVR);

								/**
								 * No bulk rates cache found for this channel, room and rate plan.
								 * We prevent channels like Airbnb from being updated if not for the
								 * main rate plan only, otherwise we attempt to process the request.
								 * 
								 * @since 	1.17.6 (J) - 1.7.6 (WP)
								 */
								if (in_array($idchannel, $ota_single_rplan)) {
									if (($rplan_info['is_derived'] || $is_secondary_rplan) && !$vbo_single_rplan) {
										// skip this channel from updating a derived/secondary rate plan that would not exist
										$ch_rplan_id = '';
									}
								} elseif (!$is_google_platform && $rplan_info['is_derived'] && !$vbo_single_rplan) {
									// derived rate plans will always require bulk rates cache information, unless it's Google
									$ch_rplan_id = '';
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
										// since the Connector does not return breakdown info about the restrictions, we concatenate the response here
										if ((int) $setminlos > 0) {
											$bkdown = rtrim($bkdown, "\n");
											$bkdown .= ' - Min LOS: ' . $setminlos;
											if ((int) $setmaxlos > 0) {
												$bkdown .= ' - Max LOS: ' . $setmaxlos;
											}
											$bkdown_ctad_rules = [];
											if ($setctawdays && $current_cta) {
												$bkdown_ctad_rules[] = 'CTA: ' . implode(',', $this->weekDaysToShort($current_cta));
											}
											if ($setctdwdays && $current_ctd) {
												$bkdown_ctad_rules[] = 'CTD: ' . implode(',', $this->weekDaysToShort($current_ctd));
											}
											if ($bkdown_ctad_rules) {
												$bkdown .= ' [' . implode(' ', $bkdown_ctad_rules) . ']';
											}
											$bkdown .= "\n";
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

					// check for possible conflicts
					if (!$rplan_info['is_derived'] && !$split_ct_nodes && ($conflicts ?? false) === true) {
						// trigger an automatic bulk action for uploading the rates to ensure a full refresh
						VikChannelManager::autoBulkActions([
							'from_date'    => $from_date,
							'to_date'      => $to_date,
							'forced_rooms' => [$id_room],
							'update'       => 'rates',
						]);
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
	 * Calculates the restriction modifiers for a specific range of dates and room.
	 * 
	 * @param 	int 	$start_ts 	The date range start date timestamp.
	 * @param 	int 	$end_ts 	The date range end date timestamp.
	 * @param 	int 	$room_id 	The VikBooking room ID.
	 * 
	 * @return 	array 	Numeric list of modifiers (Max LOS, CTA, CTD, split nodes, conflicts).
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	public function calculateRestrictionModifiers($start_ts, $end_ts, $room_id)
	{
		// build cache signature
		$from_dt = date('Y-m-d', $start_ts);
		$to_dt = date('Y-m-d', $end_ts);
		$signature = $from_dt . '_' . $to_dt . '_' . $room_id;

		if (!isset($this->cached_restrictions[$signature])) {
			// load and cache restrictions
			$this->cached_restrictions[$signature] = VikBooking::loadRestrictions(true, [$room_id]);
		}

		// build default return values
		$calc_maxlos    = 0;
		$calc_cta       = [];
		$calc_ctd       = [];
		$split_ct_nodes = [];
		$conflicts      = false;

		if (!($this->cached_restrictions[$signature] ?? [])) {
			// do not proceed as nothing would be found
			return [
				$calc_maxlos,
				$calc_cta,
				$calc_ctd,
				$split_ct_nodes,
				$conflicts,
			];
		}

		// list of week days involved
		$wdays_involved = [];

		// loop through the requested range of dates
		$infostart = getdate($start_ts);
		$infofirst = $infostart;
		while ($infostart[0] > 0 && $infostart[0] <= $end_ts) {
			// calculate timestamps
			$tomorrow_ts = mktime(0, 0, 0, $infostart['mon'], ($infostart['mday'] + 1), $infostart['year']);
			$today_mid_ts = mktime(0, 0, 0, $infostart['mon'], $infostart['mday'], $infostart['year']);

			// set week-day involved
			$wdays_involved[] = $infostart['wday'];

			// calculate restrictions
			$restrictions = VikBooking::parseSeasonRestrictions($today_mid_ts, $tomorrow_ts, 1, $this->cached_restrictions[$signature] ?? []);

			// check for max LOS
			if ($restrictions['maxlos'] ?? null) {
				if (!$calc_maxlos) {
					$calc_maxlos = (int) $restrictions['maxlos'];
				}
			}

			// check for CTA week-days
			if ($restrictions['cta'] ?? []) {
				if (!$calc_cta) {
					$calc_cta = (array) $restrictions['cta'];
					$conflicts = $conflicts || !in_array($infostart['wday'], (array) $restrictions['cta']);
				} else {
					$conflicts = $conflicts || $calc_cta != (array) $restrictions['cta'];
				}
			} elseif ($calc_cta) {
				$conflicts = true;
			}

			// check for CTD week-days
			if ($restrictions['ctd'] ?? []) {
				if (!$calc_ctd) {
					$calc_ctd = (array) $restrictions['ctd'];
					$conflicts = $conflicts || !in_array($infostart['wday'], (array) $restrictions['ctd']);
				} else {
					$conflicts = $conflicts || $calc_ctd != (array) $restrictions['ctd'];
				}
			} elseif ($calc_ctd) {
				$conflicts = true;
			}

			// go to next day
			$infostart = getdate($tomorrow_ts);
		}

		if ($calc_cta) {
			// ensure it's a list of integers representing week-days
			$calc_cta = array_map(function($w) {
				return (int) str_replace('-', '', (string) $w);
			}, $calc_cta);

			if (count($wdays_involved) < 7) {
				// filter the CTA week days according to the nights involved
				$calc_cta = array_filter($calc_cta, function($w) use ($wdays_involved) {
					return in_array($w, $wdays_involved);
				});
			}
		}

		if ($calc_ctd) {
			// ensure it's a list of integers representing week-days
			$calc_ctd = array_map(function($w) {
				return (int) str_replace('-', '', (string) $w);
			}, $calc_ctd);

			if (count($wdays_involved) < 7) {
				// filter the CTD week days according to the nights involved
				$calc_ctd = array_filter($calc_ctd, function($w) use ($wdays_involved) {
					return in_array($w, $wdays_involved);
				});
			}
		}

		if ($conflicts && count($wdays_involved) < 7 && ($calc_cta || $calc_ctd)) {
			// conflicting cta/ctd restrictions for a range of dates less than a week may be split into multiple OTA nodes
			$day_ctad_list = [];
			for ($w = 0; $w < count($wdays_involved); $w++) {
				// build day individual cta/ctd restrictions
				$split_ts = mktime(0, 0, 0, $infofirst['mon'], $infofirst['mday'] + $w, $infofirst['year']);
				$day_ctad_list[] = [
					'ts'  => $split_ts,
					'day' => date('Y-m-d', $split_ts),
					'cta' => in_array($wdays_involved[$w], $calc_cta) ? $calc_cta : [],
					'ctd' => in_array($wdays_involved[$w], $calc_ctd) ? $calc_ctd : [],
				];
			}

			// attempt to merge the split cta/ctd nodes for consecutive dates with equal rules
			$split_from_ts = 0;
			$split_to_ts   = 0;
			$split_cta     = [];
			$split_ctd     = [];
			foreach ($day_ctad_list as $ct_node_restr) {
				if (!$split_from_ts) {
					// initialize values
					$split_from_ts = $ct_node_restr['ts'];
					$split_to_ts   = $ct_node_restr['ts'];
					$split_cta     = $ct_node_restr['cta'];
					$split_ctd     = $ct_node_restr['ctd'];
					continue;
				}
				if ($split_cta != $ct_node_restr['cta'] || $split_ctd != $ct_node_restr['ctd']) {
					// close previous interval
					$split_ct_nodes[] = [
						'from_ts' => $split_from_ts,
						'to_ts'   => $split_to_ts,
						'cta'     => $split_cta,
						'ctd'     => $split_ctd,
					];
					// start new values
					$split_from_ts = $ct_node_restr['ts'];
					$split_to_ts   = $ct_node_restr['ts'];
					$split_cta     = $ct_node_restr['cta'];
					$split_ctd     = $ct_node_restr['ctd'];
				} else {
					// increase till day timestamp
					$split_to_ts = $ct_node_restr['ts'];
				}
			}
			if (!$split_ct_nodes || $split_ct_nodes[count($split_ct_nodes) - 1]['to_ts'] != $split_from_ts) {
				// close last or only interval
				$split_ct_nodes[] = [
					'from_ts' => $split_from_ts,
					'to_ts'   => $split_to_ts,
					'cta'     => $split_cta,
					'ctd'     => $split_ctd,
				];
			}

			// normalize timestamps
			foreach ($split_ct_nodes as &$split_ct_node) {
				$split_ct_node['from_dt'] = date('Y-m-d', $split_ct_node['from_ts']);
				$split_ct_node['to_dt'] = date('Y-m-d', $split_ct_node['to_ts']);
			}

			// unset last reference
			unset($split_ct_node);
		}

		// double check for possible conflicts
		$conflicts = count($wdays_involved) < 7 ? false : $conflicts;

		return [
			// max LOS
			$calc_maxlos,
			// cta week-days
			$calc_cta,
			// ctd week-days
			$calc_ctd,
			// split cta/ctd nodes (range of dates)
			$split_ct_nodes,
			// whether some dates have conflicting modifiers
			$conflicts,
		];
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

	/**
	 * Detects if we are updating a non-mapped rate plan, probably not supported by the OTA. Useful to
	 * prevent non-refundable rate plans to be transmitted to OTAs that would not directly support it.
	 * 
	 * @param 	int 	$idchannel 		The channel unique key.
	 * @param 	array 	$room_rates 	The rate plan record details.
	 * @param 	array 	$room_cahe 		The Bulk Rates Cache for the current room-type.
	 * 
	 * @return 	bool 					True if the rate plan was mapped in the Bulk Rates Cache.
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)
	 */
	protected function isOTARatePlanMapped($idchannel, array $room_rates, array $room_cache)
	{
		$room_type_id   = $room_rates['idroom'] ?? 0;
		$rate_plan_id   = $room_rates['idprice'] ?? 0;

		// access the room rate plan relations
		if (!($this->room_rate_plans[$room_type_id] ?? [])) {
			$this->room_rate_plans[$room_type_id] = VBORoomHelper::getInstance()->getRatePlans($room_type_id);
		}

		if (count(($this->room_rate_plans[$room_type_id] ?: [])) < 2) {
			// this room-type has got just one rate plan assigned, so we consider it as mapped
			return true;
		}

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

		// return whether this rate plan was updated through a bulk action
		return in_array($rate_plan_id, $cached_ota_rate_plans);
	}

	/**
	 * Used for breakdown purposes, converts a list of week-day indexes.
	 * 
	 * @param 	array 	$wdays 	List of zero-based week day indexes.
	 * 
	 * @return 	array 			List of readable week days.
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function weekDaysToShort(array $wdays)
	{
		$map = [
			'Sun',
			'Mon',
			'Tue',
			'Wed',
			'Thu',
			'Fri',
			'Sat',
		];

		return array_map(function($wday_index) use ($map) {
			$wday_index = (int) $wday_index;
			return $map[$wday_index] ?? $wday_index;
		}, $wdays);
	}
}
