<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Helper class to handle rooms data.
 * 
 * @since 	1.15.1 (J) - 1.5.2 (WP)
 */
final class VBORoomHelper extends JObject
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var  VBORoomHelper
	 */
	private static $instance = null;

	/**
	 * Proxy to construct the object.
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
	 * Checks whether a room has been configured with LOS pricing rules.
	 * VCM comes with a similar built-in method, but we need this feature
	 * to be available also for those who only use VBO. Moreover, this method
	 * can identify the first night with a non-proportional rate.
	 * 
	 * @param 	int 	$idroom 	the ID of the room in VBO.
	 * @param 	int 	$idprice 	the optional rate plan ID in VBO.
	 * @param 	bool 	$get_nights whether to return the number of nights when LOS starts.
	 * 
	 * @return 	bool|int			false on failure or if no LOS prices found, true or int otherwise.
	 */
	public static function hasLosRecords($idroom, $idprice = 0, $get_nights = false)
	{
		if (empty($idroom)) {
			return false;
		}

		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `idroom`=" . (int)$idroom . (!empty($idprice) ? " AND `idprice`=" . (int)$idprice : '') . " ORDER BY `days` ASC;";
		$dbo->setQuery($q);
		$los_data = $dbo->loadAssocList();
		if (!$los_data) {
			return false;
		}

		$los_pricing = array();
		foreach ($los_data as $cost) {
			if (!isset($los_pricing[$cost['days']])) {
				$los_pricing[$cost['days']] = array();
			}
			array_push($los_pricing[$cost['days']], $cost);
		}
		// sort by number of nights
		ksort($los_pricing);

		// compose lowest costs per rate plan
		$base_costs = array();
		foreach ($los_pricing as $nights => $costs) {
			foreach ($costs as $rplan_cost) {
				$base_costs[$rplan_cost['idprice']] = ($rplan_cost['cost'] / $rplan_cost['days']);
			}
			// we take the costs for the lowest number of nights
			break;
		}

		// check if rates change depending on the number of nights of stay
		foreach ($los_pricing as $nights => $costs) {
			foreach ($costs as $rplan_cost) {
				$base_cost = ($rplan_cost['cost'] / $rplan_cost['days']);
				if (isset($base_costs[$rplan_cost['idprice']]) && round($base_costs[$rplan_cost['idprice']], 2) != round($base_cost, 2)) {
					/**
					 * Average rates should be compared after applying rounding or we may face issues.
					 * For example, 383.97 / 3 = 127.99, but it's actually = 127.99000000000001 with
					 * an absolute number for the difference with 127.99 of 1.4210854715202004E-14
					 * which results to be greater than 0 but less than 1. Therefore, we also allow
					 * an absolute number for the difference of 0.05 cents for a proper check.
					 */
					$price_diff = abs($base_costs[$rplan_cost['idprice']] - $base_cost);
					if ($price_diff > 0.05) {
						// this is a non-proportional cost per night, so LOS records have been defined
						return $get_nights ? $nights : true;
					}
				}
			}
		}

		// all costs per night were proportional
		return false;
	}

	/**
	 * Calculates the effective Min LOS from the Rates Table
	 * for the given room and rate plan ID.
	 * 
	 * @param 	int 	$idroom 	the ID of the room-type on the website.
	 * @param 	int 	$idprice 	the ID of the rate plan on the website.
	 * 
	 * @return 	int 				the effective Min LOS or 0.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public static function calcEffectiveMinLOS($idroom, $idprice)
	{
		if (empty($idroom) || empty($idprice)) {
			return 0;
		}

		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select('MIN(' . $dbo->qn('days') . ')')
				->from($dbo->qn('#__vikbooking_dispcost'))
				->where($dbo->qn('idroom') . ' = ' . (int) $idroom)
				->where($dbo->qn('idprice') . ' = ' . (int) $idprice)
		, 0, 1);

		return (int) $dbo->loadResult();
	}

	/**
	 * Calculates the effective Max LOS from the Rates Table
	 * for the given room and rate plan ID.
	 * 
	 * @param 	int 	$idroom 	the ID of the room-type on the website.
	 * @param 	int 	$idprice 	the ID of the rate plan on the website.
	 * 
	 * @return 	int 				the effective Max LOS or 0.
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public static function calcEffectiveMaxLOS($idroom, $idprice)
	{
		if (empty($idroom) || empty($idprice)) {
			return 0;
		}

		$dbo = JFactory::getDbo();

		$dbo->setQuery(
			$dbo->getQuery(true)
				->select('MAX(' . $dbo->qn('days') . ')')
				->from($dbo->qn('#__vikbooking_dispcost'))
				->where($dbo->qn('idroom') . ' = ' . (int) $idroom)
				->where($dbo->qn('idprice') . ' = ' . (int) $idprice)
		, 0, 1);

		return (int) $dbo->loadResult();
	}

	/**
	 * Gets the available room upgrade options, if any.
	 * 
	 * @param 	VikBookingTranslator 	$vbo_tn 	the translator object.
	 * 
	 * @return 	array 					list of available upgrade options,
	 * 									or empty array if nothing availabe.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function getUpgradeOptions($vbo_tn = null)
	{
		$booking = $this->get('booking', []);
		$rooms 	 = $this->get('rooms', []);

		if (!$booking || !$rooms || $booking['status'] != 'confirmed') {
			return [];
		}

		$dbo = JFactory::getDbo();
		$config = VBOFactory::getConfig();

		$upgrade_options = [];
		$room_ids = [];

		foreach ($rooms as $num => $broom) {
			if (empty($broom['idroom']) || empty($broom['idtar'])) {
				// room must have a valid tariff assigned
				continue;
			}
			$room_upgrade_options = $config->getArray('room_upgrade_options_' . $broom['idroom'], []);
			if (empty($room_upgrade_options) || empty($room_upgrade_options['rooms'])) {
				// no relations for this room
				continue;
			}
			// fetch the original tariff for this room
			$orig_tariff = $this->getTariffData($broom['idtar']);
			if (!$orig_tariff) {
				// unable to get the original tariff information for this room booked
				continue;
			}
			// push suitable rooms
			$upgrade_options[$num] = [
				'rooms'    => array_map('intval', array_filter(array_unique($room_upgrade_options['rooms']))),
				'discount' => (!empty($room_upgrade_options['discount']) ? (float)$room_upgrade_options['discount'] : 0),
				'tariff'   => $orig_tariff,
				'r_costs'  => [],
			];
			$room_ids = array_merge($room_ids, $room_upgrade_options['rooms']);
		}

		if (!$upgrade_options) {
			return [];
		}

		// get all room IDs involved
		$room_ids = array_map('intval', array_filter(array_unique($room_ids)));

		$q = "SELECT * FROM `#__vikbooking_rooms` WHERE `id` IN (" . implode(', ', $room_ids) . ") AND `avail`=1;";
		$dbo->setQuery($q);
		$room_records = $dbo->loadAssocList();
		if (!$room_records) {
			return [];
		}
		if ($vbo_tn) {
			// translate rooms
			$vbo_tn->translateContents($room_records, '#__vikbooking_rooms');
		}

		// build up an associative array of room infos
		$room_infos = [];
		foreach ($room_records as $room_record) {
			$room_infos[$room_record['id']] = $this->prepareCMSContents($room_record, ['info', 'smalldesc']);
		}
		unset($room_records);

		// keep the count of the room units suggested
		$room_units_counter = [];

		// filter the suitable rooms by rate plan, and calculate the costs
		foreach ($upgrade_options as $num => $upgrade_option) {
			// build the costs for each upgrade room option
			$upgrade_room_costs = [];
			// parse all rooms compatible
			foreach ($upgrade_option['rooms'] as $rkey => $rid) {
				// find the same tariff for this room and nights
				$room_same_tariff = $this->findTariff($rid, $upgrade_option['tariff']['days'], $upgrade_option['tariff']['idprice']);
				if (!$room_same_tariff || !isset($room_infos[$rid])) {
					// this room is not suited
					unset($upgrade_options[$num]['rooms'][$rkey]);
					continue;
				}

				// count the actual number of room remaining units
				$use_room_units = $room_infos[$rid]['units'];
				if (isset($room_units_counter[$rid])) {
					$use_room_units -= $room_units_counter[$rid];
				}

				// make sure the room is bookable on these dates (restrictions are ignored)
				if (!VikBooking::roomBookable($rid, $use_room_units, $booking['checkin'], $booking['checkout'])) {
					// room is not available for upgrade
					unset($upgrade_options[$num]['rooms'][$rkey]);
					continue;
				}

				// update room units counter
				if (!isset($room_units_counter[$rid])) {
					$room_units_counter[$rid] = 0;
				}
				$room_units_counter[$rid]++;

				// apply seasonal rates
				$tar = VikBooking::applySeasonsRoom([$room_same_tariff], $booking['checkin'], $booking['checkout']);

				// apply OBP rules
				$tar = $this->applyOBPRules($tar, $room_infos[$rid], $rooms[$num]['adults']);

				// apply upgrade discount (if any) and calculate upgrade cost
				foreach ($tar as $tk => $tv) {
					$tar[$tk]['upgrade_cost'] = $upgrade_option['discount'] > 0 ? round(($tv['cost'] * (100 - $upgrade_option['discount']) / 100), 2) : $tv['cost'];
				}

				// push room tariff (just one rate plan, the originally booked one)
				$upgrade_room_costs[$rid] = $tar[0];
			}

			if (!count($upgrade_options[$num]['rooms'])) {
				// no more suitable rooms
				unset($upgrade_options[$num]);
				continue;
			}

			// sort by price descending (most expensive on top)
			$sort_map = [];
			foreach ($upgrade_room_costs as $rid => $tar) {
				$sort_map[$rid] = $tar['upgrade_cost'];
			}
			arsort($sort_map);

			// replace values with sorted ordering
			$cp_upgrade_room_costs = [];
			foreach ($sort_map as $rid => $sorted) {
				$cp_upgrade_room_costs[$rid] = $upgrade_room_costs[$rid];
			}
			$upgrade_room_costs = $cp_upgrade_room_costs;

			// set upgrade room costs
			$upgrade_options[$num]['r_costs'] = $upgrade_room_costs;
		}

		if (!count($upgrade_options)) {
			return [];
		}

		// return the associative array information
		return [
			'upgrade' => $upgrade_options,
			'rooms'   => $room_infos,
		];
	}

	/**
	 * Gets the record details about a specific tariff ID.
	 * 
	 * @param 	int 	$idtar 	the ID of the room-tariff.
	 * 
	 * @return 	array 			record found, or empty array.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function getTariffData($idtar)
	{
		$dbo = JFactory::getDbo();

		$dbo->setQuery("SELECT * FROM `#__vikbooking_dispcost` WHERE `id` = " . (int)$idtar, 0, 1);
		$tariff = $dbo->loadAssoc();
		if (!$tariff) {
			return [];
		}

		return $tariff;
	}

	/**
	 * Finds a tariff for the given rate plan ID, room and nights.
	 * 
	 * @param 	int 	$rid 		the room ID.
	 * @param 	int 	$nights 	the number of nights of stay.
	 * @param 	int 	$idprice 	the rate plan ID.
	 * 
	 * @return 	array 				record found or empty array.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function findTariff($rid, $nights, $idprice)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `t`.*, `p`.`name` AS `rate_plan_name` FROM `#__vikbooking_dispcost` AS `t` 
			LEFT JOIN `#__vikbooking_prices` AS `p` ON `t`.`idprice`=`p`.`id` 
			WHERE `t`.`idroom` = " . (int)$rid . " AND `t`.`days`=" . (int)$nights . " AND `t`.`idprice`=" . (int)$idprice;
		$dbo->setQuery($q, 0, 1);
		$tariff = $dbo->loadAssoc();
		if (!$tariff) {
			return [];
		}

		return $tariff;
	}

	/**
	 * Applies the OBP rules over an array of tariffs.
	 * 
	 * @param 	array 	$tar 		list of tariff records, one per rate plan, after seasonal rates.
	 * @param 	array 	$room 		the room (or order-room) record for which tariffs where loaded.
	 * @param 	int 	$adults 	the number of adults to consider.
	 * 
	 * @return 	array 				original tariffs array with OBP costs applied.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function applyOBPRules(array $tar, array $room, $adults = 2)
	{
		// check for different usage
		if (!isset($room['fromadult']) || $room['fromadult'] > $adults || $room['toadult'] < $adults) {
			return $tar;
		}

		// check for room ID
		$use_room_id = isset($room['idroom']) ? $room['idroom'] : $room['id'];

		// different usage
		$diffusageprice = VikBooking::loadAdultsDiff($use_room_id, $adults);

		/**
		 * Memorize immediately the OBP rules defined at room-level in order to avoid conflicts
		 * with rate plans with and without OBP overrides defined at rate plan level through SP.
		 */
		$orig_diffusage = $diffusageprice;

		// occupancy overrides
		$occ_ovr = VikBooking::occupancyOverrideExists($tar, $adults);
		$diffusageprice = $occ_ovr !== false ? $occ_ovr : $diffusageprice;

		if (!$diffusageprice) {
			return $tar;
		}

		// set a charge or discount to the price(s) for the different usage of the room
		foreach ($tar as $kpr => $vpr) {
			// occupancy override
			$diffusageprice = isset($vpr['occupancy_ovr']) && isset($vpr['occupancy_ovr'][$adults]) ? $vpr['occupancy_ovr'][$adults] : $orig_diffusage;

			// set usage of the room
			$tar[$kpr]['diffusage'] = $adults;

			if ($diffusageprice['chdisc'] == 1) {
				// charge
				if ($diffusageprice['valpcent'] == 1) {
					// fixed value
					$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
					$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
					$tar[$kpr]['diffusagecost'] = "+" . $aduseval;
					$tar[$kpr]['room_base_cost'] = $vpr['cost'];
					$tar[$kpr]['cost'] = $vpr['cost'] + $aduseval;
				} else {
					// percentage value
					$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
					$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $tar[$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
					$tar[$kpr]['diffusagecost'] = "+" . $diffusageprice['value'] . "%";
					$tar[$kpr]['room_base_cost'] = $vpr['cost'];
					$tar[$kpr]['cost'] = $aduseval;
				}
			} else {
				// discount
				if ($diffusageprice['valpcent'] == 1) {
					// fixed value
					$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
					$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
					$tar[$kpr]['diffusagecost'] = "-" . $aduseval;
					$tar[$kpr]['room_base_cost'] = $vpr['cost'];
					$tar[$kpr]['cost'] = $vpr['cost'] - $aduseval;
				} else {
					// percentage value
					$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
					$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $tar[$kpr]['days']) * $diffusageprice['value'] / 100) * $tar[$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
					$tar[$kpr]['diffusagecost'] = "-" . $diffusageprice['value'] . "%";
					$tar[$kpr]['room_base_cost'] = $vpr['cost'];
					$tar[$kpr]['cost'] = $aduseval;
				}
			}
		}

		// return the array of tariffs with OBP included
		return $tar;
	}

	/**
	 * Prepares some description strings for the current CMS, by triggering
	 * the necessary platform-related functions for third party plugins.
	 * 
	 * @param 	array 	$room_record 	the room record to prepare.
	 * @param 	array 	$keys 			list of record keys to prepare.
	 * 
	 * @return 	array 					the original array given with keys prepared.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function prepareCMSContents(array $room_record, array $keys)
	{
		foreach ($keys as $key) {
			if (!isset($room_record[$key])) {
				continue;
			}

			if (VBOPlatformDetection::isWordPress()) {
				/**
				 * @wponly 	we try to parse any shortcode inside the HTML description of the room
				 */
				$room_record[$key] = do_shortcode(wpautop($room_record[$key]));
			} else {
				// BEGIN: Joomla Content Plugins Rendering
				JPluginHelper::importPlugin('content');

				$myItem = JTable::getInstance('content');

				$myItem->text = $room_record[$key];
				$objparams = array();
				if (class_exists('JEventDispatcher')) {
					$dispatcher = JEventDispatcher::getInstance();
					$dispatcher->trigger('onContentPrepare', array('com_vikbooking.roomdetails', &$myItem, &$objparams, 0));
				} else {
					/**
					 * @joomla4only
					 */
					$dispatcher = JFactory::getApplication();
					if (method_exists($dispatcher, 'triggerEvent')) {
						$dispatcher->triggerEvent('onContentPrepare', array('com_vikbooking.roomdetails', &$myItem, &$objparams, 0));
					}
				}
				$room_record[$key] = $myItem->text;
				// END: Joomla Content Plugins Rendering
			}
		}

		return $room_record;
	}

	/**
	 * Gets an associative list of rate plans with a few pricing information for the given room.
	 * 
	 * @param 	int 	$rid 		the VBO room id.
	 * @param 	int 	$rplan_id 	optional rate plan ID to get.
	 * 
	 * @return 	array 				associative list of rate plans for the given room or specific rate plan.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP)
	 */
	public function getRatePlans($rid = 0, $rplan_id = 0)
	{
		if (empty($rid)) {
			$rid = $this->get('id', 0);
		}

		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select([
				$dbo->qn('r.id'),
				$dbo->qn('r.idroom'),
				$dbo->qn('r.days'),
				$dbo->qn('r.idprice'),
				$dbo->qn('r.cost'),
				$dbo->qn('p.name'),
				$dbo->qn('p.minlos'),
				$dbo->qn('p.derived_id'),
			])
			->from($dbo->qn('#__vikbooking_dispcost', 'r'))
			->leftJoin($dbo->qn('#__vikbooking_prices', 'p') . ' ON ' . $dbo->qn('r.idprice') . ' = ' . $dbo->qn('p.id'))
			->where($dbo->qn('r.idroom') . ' = ' . (int)$rid)
			->order($dbo->qn('r.days') . ' ASC')
			->order($dbo->qn('r.cost') . ' ASC');

		$dbo->setQuery($q, 0, 50);

		$tariffs = $dbo->loadObjectList();

		if (!$tariffs) {
			return [];
		}

		$parsed_room_prices = [];
		foreach ($tariffs as $rrk => $rrv) {
			if (isset($parsed_room_prices[$rrv->idprice])) {
				unset($tariffs[$rrk]);
				continue;
			}
			$tariffs[$rrk]->cost = round(($rrv->cost / $rrv->days), 2);
			$tariffs[$rrk]->days = 1;
			$parsed_room_prices[$rrv->idprice] = 1;
		}

		$tariffs = array_values($tariffs);

		$room_rate_plans = [];
		foreach ($tariffs as $rplan) {
			if ($rplan_id && $rplan_id == $rplan->idprice) {
				return (array)$rplan;
			}

			$room_rate_plans[] = [
				'id'         => $rplan->idprice,
				'name'       => $rplan->name,
				'cost'       => $rplan->cost,
				'minlos'     => $rplan->minlos,
				'derived_id' => $rplan->derived_id,
			];
		}

		if ($rplan_id) {
			return [];
		}

		return $room_rate_plans;
	}

	/**
	 * Calculates if the provided booking information require a split payment for the damage deposit.
	 * 
	 * @param 	array 	$booking 		The booking record data.
	 * @param 	array 	$booking_rooms 	The rooms booking list.
	 * 
	 * @return 	array 					Associative list of damage deposit details.
	 * 
	 * @since 	1.17.6 (J) - 1.7.6 (WP)
	 */
	public function getDamageDepositSplitPayment(array $booking, array $booking_rooms)
	{
		// load all option records of type damage deposit
		$dbo = JFactory::getDbo();
		$dbo->setQuery(
			$dbo->getQuery(true)
				->select('*')
				->from($dbo->qn('#__vikbooking_optionals'))
				->where($dbo->qn('forcesel') . ' = 1')
				->where($dbo->qn('oparams') . ' LIKE ' . $dbo->q('%' . str_replace(['{', '}'], '', json_encode(['damagedep' => 1])) . '%'))
		);
		$dd_records = $dbo->loadAssocList();

		// scan all records for validation, if any
		foreach ($dd_records as &$dd_record) {
			// make sure to decode the option params
			$dd_record['oparams'] = (array) json_decode($dd_record['oparams'], true);

			if (empty($dd_record['oparams']['damagedep_settings']['paywhen'])) {
				// no separate payment defined
				unset($dd_record);
				continue;
			}

			// validate maximum nights of stay
			if (!empty($dd_record['oparams']['damagedep_settings']['bmaxlos']) && ($booking['days'] ?? 1) > $dd_record['oparams']['damagedep_settings']['bmaxlos']) {
				// limit exceeded
				unset($dd_record);
				continue;
			}

			// validate payment method ID
			if (empty($dd_record['oparams']['damagedep_settings']['payid']) && empty($booking['idpayment'])) {
				// no payment method defined anywhere
				unset($dd_record);
				continue;
			}

			// calculate and set the payment window values
			$dd_record['payment_window'] = [];
			if (!strlen((string) $dd_record['oparams']['damagedep_settings']['paywind'])) {
				// payable from today (always)
				$dd_record['payment_window']['payment_from_dt'] = date('Y-m-d');
				$dd_record['payment_window']['payable'] = true;
			} elseif (empty($dd_record['oparams']['damagedep_settings']['paywind'])) {
				// payable from the check-in day
				$dd_record['payment_window']['payment_from_dt'] = date('Y-m-d', $booking['checkin']);
				$dd_record['payment_window']['payable'] = strtotime($dd_record['payment_window']['payment_from_dt']) <= strtotime(date('Y-m-d'));
			} else {
				// calculate the payable date
				$window_days = (int) $dd_record['oparams']['damagedep_settings']['paywind'];
				$dd_record['payment_window']['payment_from_dt'] = date('Y-m-d', strtotime(sprintf('-%d days', $window_days), $booking['checkin']));
				$dd_record['payment_window']['payable'] = strtotime($dd_record['payment_window']['payment_from_dt']) <= strtotime(date('Y-m-d'));
			}

			// check if a custom payment method should be used
			if (!empty($dd_record['oparams']['damagedep_settings']['payid'])) {
				$dd_record['payment_window']['pay_id'] = $dd_record['oparams']['damagedep_settings']['payid'];
			}

			// ensure damage deposit amount was not paid already
			if (empty($booking['idorderota']) && !empty($booking['totpaid']) && $booking['totpaid'] >= ($booking['total'] ?? 0)) {
				// payment window not available because damage deposit already paid
				$dd_record['payment_window'] = [];
			}
		}

		// unset last reference
		unset($dd_record);

		if (!$dd_records) {
			// unable to proceed
			return [];
		}

		// always reset array keys
		$dd_records = array_values($dd_records);

		// list of damage deposit option IDs
		$dd_record_ids = array_column($dd_records, 'id');

		// room reservation IDs affected
		$room_reservation_dd = [];

		// collect all damage deposit options from the booked rooms
		$rooms_dd_data = [];
		foreach ($booking_rooms as $or) {
			if (empty($or['optionals'])) {
				continue;
			}

			$stepo = array_filter(explode(";", $or['optionals']));
			foreach ($stepo as $roptkey => $one) {
				$stept = explode(":", $one);
				if (in_array($stept[0], $dd_record_ids)) {
					// push damage deposit ID and room record
					$rooms_dd_data[] = [
						'dd_id' => $stept[0],
						'rr'    => $or,
					];

					// push room reservation ID
					$room_reservation_dd[] = $or['idroom'] ?? 0;
				}
			}
		}

		if (!$rooms_dd_data) {
			// no damage deposit options were booked
			return [];
		}

		// get the unique array
		$rooms_dd_unique = array_values(array_unique(array_column($rooms_dd_data, 'dd_id')));

		// turn the records into an associative list
		$dd_records_assoc = [];
		foreach ($dd_records as $dd_record) {
			$dd_records_assoc[$dd_record['id']] = $dd_record;
		}

		// calculate amounts and damage deposit payment window
		$tot_dd_amount_gross = 0;
		$tot_dd_amount_net = 0;
		$tot_dd_amount_tax = 0;
		$payment_window = [];

		foreach ($rooms_dd_data as $room_dd_data) {
			$opt_id = $room_dd_data['dd_id'];
			if (!($dd_records_assoc[$opt_id] ?? [])) {
				continue;
			}

			// calculate damage deposit price
			$dd_price = (float) $dd_records_assoc[$opt_id]['cost'];
			if (!empty($dd_records_assoc[$opt_id]['pcentroom'])) {
				// percent cost of the room reservation
				$room_cost = ($room_dd_data['rr']['room_cost'] ?? 0) ?: ($room_dd_data['rr']['cust_cost'] ?? 0) ?: 0;
				$dd_price = $room_cost * $dd_price / 100;
			}

			if ($dd_price <= 0) {
				// invalid damage deposit cost
				continue;
			}

			if ($dd_records_assoc[$opt_id]['perday'] == 1) {
				// cost per night
				$dd_price = $dd_price * ($booking['days'] ?? 1);
			}

			if (($dd_records_assoc[$opt_id]['maxprice'] ?? 0) > 0 && $dd_price > $dd_records_assoc[$opt_id]['maxprice']) {
				// maximum cost
				$dd_price = (float) $dd_records_assoc[$opt_id]['maxprice'];
			}

			if ($dd_records_assoc[$opt_id]['perperson'] == 1) {
				// cost per person
				$dd_price = $dd_price * ((int) $room_dd_data['rr']['adults']);
			}

			/**
			 * Trigger event to allow third party plugins to apply a custom calculation for the option/extra fee or tax.
			 * 
			 * @since 	1.17.7 (J) - 1.7.7 (WP)
			 */
			$custom_calculation = VBOFactory::getPlatform()->getDispatcher()->filter('onCalculateBookingOptionFeeCost', [$dd_price, &$dd_records_assoc[$opt_id], $booking, $booking_rooms]);
			if ($custom_calculation) {
				$dd_price = (float) $custom_calculation[0];
			}

			if ($dd_price <= 0) {
				// invalid damage deposit cost
				continue;
			}

			// calculate taxes, if any
			$dd_amount_gross = VikBooking::sayOptionalsPlusIva($dd_price, $dd_records_assoc[$opt_id]['idiva']);
			$dd_amount_net = VikBooking::sayOptionalsMinusIva($dd_price, $dd_records_assoc[$opt_id]['idiva']);
			$dd_amount_tax = $dd_amount_gross - $dd_amount_net;

			// increase global values
			$tot_dd_amount_gross += $dd_amount_gross;
			$tot_dd_amount_net += $dd_amount_net;
			$tot_dd_amount_tax += $dd_amount_tax;

			// update payment window (one for all option records)
			$payment_window = (array) $dd_records_assoc[$opt_id]['payment_window'];
		}

		if (!$tot_dd_amount_gross) {
			// no compliant damage deposit option found for separate payment
			return [];
		}

		return [
			'damagedep_gross' => $tot_dd_amount_gross,
			'damagedep_net'   => $tot_dd_amount_net,
			'damagedep_tax'   => $tot_dd_amount_tax,
			'payment_window'  => $payment_window,
			'damagedep_rids'  => $room_reservation_dd,
		];
	}

	/**
	 * Given a list of room records, returns an associative list
	 * of room IDs and corresponding mini thumbnail URLs, if any.
	 * 
	 * @param 	array 	$rooms 	List of room records.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.17.6 (J) - 1.7.6 (WP)
	 */
	public function loadMiniThumbnails(array $rooms, string $def_uri = '')
	{
		$mini_thumbnails = [];

		$base_img_path = implode(DIRECTORY_SEPARATOR, [VBO_SITE_PATH, 'resources', 'uploads']) . DIRECTORY_SEPARATOR;
		$base_img_uri  = VBO_SITE_URI . 'resources/uploads/';

		foreach ($rooms as $room) {
			if (empty($room['id'])) {
				continue;
			}

			if (!empty($room['img']) && is_file($base_img_path . 'mini_' . $room['img'])) {
				$mini_thumbnails[$room['id']] = $base_img_uri . 'mini_' . $room['img'];
			} elseif ($def_uri) {
				$mini_thumbnails[$room['id']] = $def_uri;
			}
		}

		return $mini_thumbnails;
	}
}
