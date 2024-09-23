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

if (!class_exists('VikBookingIcons')) {
	// require the Icons class
	require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "icons.php");
}

if (!function_exists('showSelectVb')) {
	function showSelectVb($err, $err_code_info = array()) {
		include(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'error_form.php');
	}
}

class VikBooking
{
	public static function addJoomlaUser($name, $username, $email, $password) {
		//new method
		jimport('joomla.application.component.helper');
		$params = JComponentHelper::getParams('com_users');
		$user = new JUser;
		$data = array();
		//Get the default new user group, Registered if not specified.
		$system = $params->get('new_usertype', 2);
		$data['groups'] = array();
		$data['groups'][] = $system;
		$data['name'] = $name;
		$data['username'] = $username;
		$data['email'] = self::getVboApplication()->emailToPunycode($email);
		$data['password'] = $password;
		$data['password2'] = $password;
		$data['sendEmail'] = 0; //should the user receive system mails?
		//$data['block'] = 0;
		if (!$user->bind($data)) {
			VikError::raiseWarning('', JText::translate($user->getError()));
			return false;
		}
		if (!$user->save()) {
			VikError::raiseWarning('', JText::translate($user->getError()));
			return false;
		}
		return $user->id;
	}
	
	public static function userIsLogged() {
		$user = JFactory::getUser();

		return !$user->guest;
	}

	public static function prepareViewContent()
	{
		/**
		 * @wponly  JApplication::getMenu() cannot be adapted to WP
		 */
	}

	public static function isFontAwesomeEnabled()
	{
		return VBOFactory::getConfig()->getBool('usefa', true);
	}

	public static function loadFontAwesome($force_load = false)
	{
		if (!self::isFontAwesomeEnabled() && !$force_load) {
			return false;
		}

		/**
		 * We let the class VikBookingIcons load the proper FontAwesome libraries.
		 * 
		 * @since 	1.11
		 */
		VikBookingIcons::loadAssets();

		return true;
	}

	/**
	 * Checks if modifications or cancellations via front-end are allowed.
	 * 0 = everything is Disabled.
	 * 1 = Disabled, with request message (default).
	 * 2 = Modification Enabled, Cancellation Disabled.
	 * 3 = Cancellation Enabled, Modification Disabled.
	 * 4 = everything is Enabled.
	 *
	 * @return 	int
	 */
	public static function getReservationModCanc()
	{
		return VBOFactory::getConfig()->getInt('resmodcanc', 1);
	}

	public static function getReservationModCancMin()
	{
		return VBOFactory::getConfig()->getInt('resmodcancmin', 1);
	}

	public static function getDefaultDistinctiveFeatures()
	{
		return [
			'VBODEFAULTDISTFEATUREONE' => '',
			// Below is the default feature for 'Room Code'. One default feature is sufficient
			// 'VBODEFAULTDISTFEATURETWO' => '',
		];
	}

	/**
	 * Given the room's parameters and index, tries to take the first distinctive feature.
	 * 
	 * @param 	mixed 	$rparams 	string to be decoded, or decoded array/object params
	 * @param 	int 	$rindex 	room index to look for, starting from 1.
	 * 
	 * @return 	mixed 	false on failure, array otherwise [feature name, feature value]
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function getRoomUnitDistinctiveFeature($rparams, $rindex) {
		$rindex = (int)$rindex;
		if ($rindex < 1 || empty($rparams)) {
			return false;
		}

		if (is_string($rparams)) {
			// decode params
			$rparams = self::getRoomParam('features', $rparams);
		}

		if (is_object($rparams)) {
			// typecast to array
			$rparams = (array)$rparams;
		}

		if (!is_array($rparams) || !count($rparams)) {
			return false;
		}

		if (isset($rparams['features'])) {
			$rparams = $rparams['features'];
		}

		$feature = array();
		foreach ($rparams as $param_index => $rfeatures) {
			if ((int)$param_index != $rindex || !is_array($rfeatures) || !count($rfeatures)) {
				continue;
			}
			foreach ($rfeatures as $featname => $featval) {
				if (empty($featval)) {
					continue;
				}
				// use the first distinctive feature
				$tn_featname = JText::translate($featname);
				if ($tn_featname == $featname) {
					// no translation was applied
					if (VBOPlatformDetection::isWordPress()) {
						// try to apply a translation through Gettext even if we have to pass a variable
						$tn_featname = __($featname);
					} else {
						// convert the string to a hypothetical INI constant
						$ini_constant = str_replace(' ', '_', strtoupper($featname));
						$tn_featname = JText::translate($ini_constant);
						$tn_featname = $tn_featname == $ini_constant ? $featname : $tn_featname;
					}
				}
				// store values and break loop
				$feature = array($tn_featname, $featval);
				break;
			}
		}

		return count($feature) ? $feature : false;
	}

	public static function getRoomUnitNumsUnavailable($order, $idroom) {
		$dbo = JFactory::getDbo();
		$unavailable_indexes = array();
		$first = $order['checkin'];
		$second = $order['checkout'];
		$secdiff = $second - $first;
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
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		$q = "SELECT `b`.`id`,`b`.`checkin`,`b`.`checkout`,`b`.`realback`,`ob`.`idorder`,`ob`.`idbusy`,`or`.`id` AS `or_id`,`or`.`idroom`,`or`.`roomindex`,`o`.`status` ".
			"FROM `#__vikbooking_busy` AS `b` ".
			"LEFT JOIN `#__vikbooking_ordersbusy` `ob` ON `ob`.`idbusy`=`b`.`id` ".
			"LEFT JOIN `#__vikbooking_ordersrooms` `or` ON `or`.`idorder`=`ob`.`idorder` AND `or`.`idorder`!=".(int)$order['id']." ".
			"LEFT JOIN `#__vikbooking_orders` `o` ON `o`.`id`=`or`.`idorder` AND `o`.`id`=`ob`.`idorder` AND `o`.`id`!=".(int)$order['id']." ".
			"WHERE `or`.`idroom`=".(int)$idroom." AND `b`.`checkout` > ".time()." AND `o`.`status`='confirmed' AND `ob`.`idorder`!=".(int)$order['id']." AND `ob`.`idorder` > 0;";
		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();
		if ($busy) {
			foreach ($groupdays as $gday) {
				foreach ($busy as $bu) {
					if (empty($bu['roomindex']) || empty($bu['idorder'])) {
						continue;
					}
					if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
						$unavailable_indexes[$bu['or_id']] = $bu['roomindex'];
					} elseif (count($groupdays) == 2 && $gday == $groupdays[0]) {
						if ($groupdays[0] < $bu['checkin'] && $groupdays[0] < $bu['realback'] && $groupdays[1] > $bu['checkin'] && $groupdays[1] > $bu['realback']) {
							$unavailable_indexes[$bu['or_id']] = $bu['roomindex'];
						}
					}
				}
			}
		}

		return $unavailable_indexes;
	}

	public static function getRoomUnitNumsAvailable($order, $idroom) {
		$dbo = JFactory::getDbo();
		$unavailable_indexes = array();
		$available_indexes = array();
		$first = $order['checkin'];
		$second = $order['checkout'];
		$secdiff = $second - $first;
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
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		$q = "SELECT `b`.`id`,`b`.`checkin`,`b`.`checkout`,`b`.`realback`,`ob`.`idorder`,`ob`.`idbusy`,`or`.`id` AS `or_id`,`or`.`idroom`,`or`.`roomindex`,`o`.`status` ".
			"FROM `#__vikbooking_busy` AS `b` ".
			"LEFT JOIN `#__vikbooking_ordersbusy` `ob` ON `ob`.`idbusy`=`b`.`id` ".
			"LEFT JOIN `#__vikbooking_ordersrooms` `or` ON `or`.`idorder`=`ob`.`idorder` AND `or`.`idorder`!=".(int)$order['id']." ".
			"LEFT JOIN `#__vikbooking_orders` `o` ON `o`.`id`=`or`.`idorder` AND `o`.`id`=`ob`.`idorder` AND `o`.`id`!=".(int)$order['id']." ".
			"WHERE `or`.`idroom`=".(int)$idroom." AND `b`.`checkout` > ".time()." AND `o`.`status`='confirmed' AND `ob`.`idorder`!=".(int)$order['id']." AND `ob`.`idorder` > 0;";
		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();
		if ($busy) {
			foreach ($groupdays as $gday) {
				foreach ($busy as $bu) {
					if (empty($bu['roomindex']) || empty($bu['idorder'])) {
						continue;
					}
					if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
						$unavailable_indexes[$bu['or_id']] = $bu['roomindex'];
					} elseif (count($groupdays) == 2 && $gday == $groupdays[0]) {
						if ($groupdays[0] < $bu['checkin'] && $groupdays[0] < $bu['realback'] && $groupdays[1] > $bu['checkin'] && $groupdays[1] > $bu['realback']) {
							$unavailable_indexes[$bu['or_id']] = $bu['roomindex'];
						}
					}
				}
			}
		}
		$q = "SELECT `params` FROM `#__vikbooking_rooms` WHERE `id`=".(int)$idroom.";";
		$dbo->setQuery($q);
		$room_params = $dbo->loadResult();
		if ($room_params) {
			$room_params_arr = json_decode($room_params, true);
			if (array_key_exists('features', $room_params_arr) && is_array($room_params_arr['features']) && count($room_params_arr['features'])) {
				foreach ($room_params_arr['features'] as $rind => $rfeatures) {
					if (in_array($rind, $unavailable_indexes)) {
						continue;
					}
					$available_indexes[] = $rind;
				}
			}
		}

		return $available_indexes;
	}
	
	/**
	 * Load the restrictions applying the given filters to the passed rooms.
	 * The ordering of the query SHOULD remain unchanged, because it's required
	 * to have it by ascending order of the ID. So the older records first.
	 * Even when no filters, we always exclude expired restrictions.
	 * 
	 * @param 		boolean 	$filters 		whether to apply filters
	 * @param 		array 		$rooms 			the list of rooms to filter
	 * 
	 * @return 		array 		the list of restrictions found or an empty array.
	 */
	public static function loadRestrictions($filters = true, $rooms = [])
	{
		$dbo = JFactory::getDbo();

		$restrictions = [];
		$limts = strtotime(date('Y').'-'.date('m').'-'.date('d'));

		if (!$filters) {
			$q = "SELECT * FROM `#__vikbooking_restrictions` WHERE `dto` = 0 OR `dto` >= ".$limts.";";
		} else {
			if (count($rooms) == 0) {
				$q = "SELECT * FROM `#__vikbooking_restrictions` WHERE `allrooms` = 1 AND (`dto` = 0 OR `dto` >= ".$limts.");";
			} else {
				$clause = array();
				foreach ($rooms as $idr) {
					if (empty($idr)) continue;
					$clause[] = "`idrooms` LIKE '%-".intval($idr)."-%'";
				}
				if (count($clause) > 0) {
					$q = "SELECT * FROM `#__vikbooking_restrictions` WHERE (`dto` = 0 OR `dto` >= ".$limts.") AND (`allrooms` = 1 OR (`allrooms` = 0 AND (".implode(" OR ", $clause).")));";
				} else {
					$q = "SELECT * FROM `#__vikbooking_restrictions` WHERE `allrooms` = 1 AND (`dto` = 0 OR `dto` >= ".$limts.");";
				}
			}
		}
		$dbo->setQuery($q);
		$allrestrictions = $dbo->loadAssocList();

		if ($allrestrictions) {
			foreach ($allrestrictions as $k => $res) {
				if (!empty($res['month'])) {
					$restrictions[$res['month']] = $res;
				} else {
					if (!isset($restrictions['range'])) {
						$restrictions['range'] = array();
					}
					$restrictions['range'][$k] = $res;
				}
			}
		}

		return $restrictions;
	}

	public static function globalRestrictions($restrictions = [])
	{
		$ret = [];
		foreach ($restrictions as $kr => $rr) {
			if ($kr == 'range') {
				foreach ($rr as $kd => $dr) {
					if ($dr['allrooms'] == 1) {
						$ret['range'][$kd] = $restrictions[$kr][$kd];
					}
				}
				continue;
			}
			if ($rr['allrooms'] == 1) {
				$ret[$kr] = $restrictions[$kr];
			}
		}
		return $ret;
	}

	/**
	 * From the given restrictions, check-in, check-out and nights, looks for
	 * a restriction to be returned and applied over these stays.
	 * In order to give priority to newer Restriction IDs, the order of the
	 * $restrictions array should be ascending, so that the newer restrictions
	 * will overwrite the array with the last ID (more recent). Only for date ranges.
	 * Returns an array with the record of the restriction found. The loop is not
	 * broken when the first valid restriction is found to give priority to
	 * newer restriction IDs to overwrite older records.
	 * 
	 * @param 	int 	$first 			the unix timestamp for the check-in date
	 * @param 	int 	$second 		the unix timestamp for the check-out date
	 * @param 	int 	$daysdiff 		the number of nights of stay
	 * @param 	array 	$restrictions 	the list of restrictions loaded
	 * 
	 * @return 	array 	the restriction found, or an empty array.
	 */
	public static function parseSeasonRestrictions($first, $second, $daysdiff, $restrictions) {
		$season_restrictions = array();
		$restrcheckin = getdate($first);
		$restrcheckout = getdate($second);
		if (array_key_exists($restrcheckin['mon'], $restrictions)) {
			//restriction found for this month, checking:
			$season_restrictions['id'] = $restrictions[$restrcheckin['mon']]['id'];
			$season_restrictions['name'] = $restrictions[$restrcheckin['mon']]['name'];
			$season_restrictions['allowed'] = true; //set to false when these nights are not allowed
			if (strlen($restrictions[$restrcheckin['mon']]['wday']) > 0) {
				//Week Day Arrival Restriction
				$rvalidwdays = array($restrictions[$restrcheckin['mon']]['wday']);
				if (strlen($restrictions[$restrcheckin['mon']]['wdaytwo']) > 0) {
					$rvalidwdays[] = $restrictions[$restrcheckin['mon']]['wdaytwo'];
				}
				$season_restrictions['wdays'] = $rvalidwdays;
			} elseif (!empty($restrictions[$restrcheckin['mon']]['ctad']) || !empty($restrictions[$restrcheckin['mon']]['ctdd'])) {
				if (!empty($restrictions[$restrcheckin['mon']]['ctad'])) {
					$season_restrictions['cta'] = explode(',', $restrictions[$restrcheckin['mon']]['ctad']);
				}
				if (!empty($restrictions[$restrcheckin['mon']]['ctdd'])) {
					$season_restrictions['ctd'] = explode(',', $restrictions[$restrcheckin['mon']]['ctdd']);
				}
			}
			if (!empty($restrictions[$restrcheckin['mon']]['maxlos']) && $restrictions[$restrcheckin['mon']]['maxlos'] > 0 && $restrictions[$restrcheckin['mon']]['maxlos'] > $restrictions[$restrcheckin['mon']]['minlos']) {
				$season_restrictions['maxlos'] = $restrictions[$restrcheckin['mon']]['maxlos'];
				if ($daysdiff > $restrictions[$restrcheckin['mon']]['maxlos']) {
					$season_restrictions['allowed'] = false;
				}
			}
			if ($daysdiff < $restrictions[$restrcheckin['mon']]['minlos']) {
				$season_restrictions['allowed'] = false;
			}
			$season_restrictions['minlos'] = $restrictions[$restrcheckin['mon']]['minlos'];
		} elseif (array_key_exists('range', $restrictions)) {
			foreach ($restrictions['range'] as $restr) {
				if ($restr['dfrom'] <= $first && $restr['dto'] >= $first) {
					//restriction found for this date range, checking:
					$season_restrictions['id'] = $restr['id'];
					$season_restrictions['name'] = $restr['name'];
					$season_restrictions['allowed'] = true; //set to false when these nights are not allowed
					if (strlen((string)$restr['wday']) > 0) {
						//Week Day Arrival Restriction
						$rvalidwdays = array($restr['wday']);
						if (strlen((string)$restr['wdaytwo']) > 0) {
							$rvalidwdays[] = $restr['wdaytwo'];
						}
						$season_restrictions['wdays'] = $rvalidwdays;
					} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
						if (!empty($restr['ctad'])) {
							$season_restrictions['cta'] = explode(',', $restr['ctad']);
						}
						if (!empty($restr['ctdd'])) {
							$season_restrictions['ctd'] = explode(',', $restr['ctdd']);
						}
					}
					if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
						$season_restrictions['maxlos'] = $restr['maxlos'];
						if ($daysdiff > $restr['maxlos']) {
							$season_restrictions['allowed'] = false;
						}
					}
					if ($daysdiff < $restr['minlos']) {
						$season_restrictions['allowed'] = false;
					}
					$season_restrictions['minlos'] = $restr['minlos'];
				}
			}
		}

		return $season_restrictions;
	}

	public static function compareSeasonRestrictionsNights($restrictions)
	{
		$base_compare = array();
		$base_nights = 0;
		foreach ($restrictions as $nights => $restr) {
			$base_compare = $restr;
			$base_nights = $nights;
			break;
		}

		/**
		 * Prepare 1st hypothetical multi dimension array for string-comparison with array_diff().
		 * Keys "cta" and "ctd" may be the 2nd dimension array variables which cannot be casted to string.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		list($casted_base_compare, $use_base_compare) = self::prepareMultiDimArrayDiff($base_compare);

		foreach ($restrictions as $nights => $restr) {
			if ($nights == $base_nights) {
				continue;
			}

			// prepare 2nd hypothetical multi dimension array for string-comparison with array_diff().
			list($casted_restr, $use_restr) = self::prepareMultiDimArrayDiff($restr);

			// get associative array of differences
			$diff = array_diff($use_base_compare, $use_restr);

			if (count($diff) > 0 && array_key_exists('id', $diff)) {
				// return differences only if the Restriction ID is different: ignore allowed, wdays, minlos, maxlos.
				// only one Restriction per time should be applied to certain Season Dates but check just in case.
				return self::restoreMultiDimArrayDiff($casted_base_compare, $casted_restr, $diff);
			}
		}

		return array();
	}

	/**
	 * Methods using array_diff() may need one-dimension arrays, because this
	 * native functions applies a string comparison, and so casting an array
	 * value to a string would generate a Notice message. This method simply
	 * converts any sub-array into a restorable string into a non-scalar var.
	 * This only works when comparing 2 array variables.
	 * 
	 * @param 	array 	$arr 	the hypothetical multi dimension array.
	 * 
	 * @return 	array 			the one dimension array to be passed to array_diff()
	 * 							and the list of keys converted to strings for comparison.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function prepareMultiDimArrayDiff($arr)
	{
		$casted_keys = array();

		if (!is_array($arr) || !count($arr)) {
			return array($casted_keys, $arr);
		}

		foreach ($arr as $key => $val) {
			if (!is_scalar($val)) {
				// memorize original value
				$casted_keys[$key] = $val;
				// cast to string must be applied
				$arr[$key] = json_encode($val);
			}
		}

		return array($casted_keys, $arr);
	}

	/**
	 * Methods using array_diff() may need one-dimension arrays, because this
	 * native functions applies a string comparison, and so casting an array
	 * value to a string would generate a Notice message. This method restores
	 * the original values of the "prepared" arrays by getting the list of the
	 * modified keys from non-scalar to scalar values.
	 * This only works when comparing 2 array variables.
	 * 
	 * @param 	array 	$keys_one 	associative array of casted key-value pairs from 1st arg.
	 * @param 	array 	$keys_two 	associative array of casted key-value pairs from 2nd arg.
	 * @param 	array 	$arr 		the array result of array_diff().
	 * 
	 * @return 	array 				the array result of array_diff() with its original values.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function restoreMultiDimArrayDiff($keys_one, $keys_two, $arr)
	{
		if (!is_array($arr) || !count($arr)) {
			return $arr;
		}

		foreach ($arr as $key => $val) {
			// keys returned by array_diff can be present in both $keys_one and $keys_two
			if (isset($keys_one[$key])) {
				// always give higher priority to the 1st argument keys for array_diff()
				$arr[$key] = json_decode($keys_one[$key]);
			} elseif (isset($keys_two[$key])) {
				// fallback to 2nd argument keys
				$arr[$key] = json_decode($keys_two[$key]);
			}
		}

		return $arr;
	}
	
	public static function roomRestrictions($roomid, $restrictions) {
		$ret = array();
		if (!empty($roomid) && count($restrictions) > 0) {
			foreach ($restrictions as $kr => $rr) {
				if ($kr == 'range') {
					foreach ($rr as $kd => $dr) {
						if ($dr['allrooms'] == 0 && !empty($dr['idrooms'])) {
							$allrooms = explode(';', $dr['idrooms']);
							if (in_array('-'.$roomid.'-', $allrooms)) {
								$ret['range'][$kd] = $restrictions[$kr][$kd];
							}
						}
					}
				} else {
					if ($rr['allrooms'] == 0 && !empty($rr['idrooms'])) {
						$allrooms = explode(';', $rr['idrooms']);
						if (in_array('-'.$roomid.'-', $allrooms)) {
							$ret[$kr] = $restrictions[$kr];
						}
					}
				}
			}
		}
		return $ret;
	}
	
	public static function validateRoomRestriction($roomrestr, $restrcheckin, $restrcheckout, $daysdiff) {
		$restrictionerrmsg = '';
		$restrictions_affcount = 0;
		if (array_key_exists($restrcheckin['mon'], $roomrestr)) {
			//restriction found for this month, checking:
			$restrictions_affcount++;
			if (strlen((string)$roomrestr[$restrcheckin['mon']]['wday'])) {
				$rvalidwdays = array($roomrestr[$restrcheckin['mon']]['wday']);
				if (strlen((string)$roomrestr[$restrcheckin['mon']]['wdaytwo'])) {
					$rvalidwdays[] = $roomrestr[$restrcheckin['mon']]['wdaytwo'];
				}
				if (!in_array($restrcheckin['wday'], $rvalidwdays)) {
					$restrictionerrmsg = JText::sprintf('VBRESTRTIPWDAYARRIVAL', self::sayMonth($restrcheckin['mon']), self::sayWeekDay($roomrestr[$restrcheckin['mon']]['wday']).(strlen($roomrestr[$restrcheckin['mon']]['wdaytwo']) > 0 ? '/'.self::sayWeekDay($roomrestr[$restrcheckin['mon']]['wdaytwo']) : ''));
				} elseif ($roomrestr[$restrcheckin['mon']]['multiplyminlos'] == 1) {
					if (($daysdiff % $roomrestr[$restrcheckin['mon']]['minlos']) != 0) {
						$restrictionerrmsg = JText::sprintf('VBRESTRTIPMULTIPLYMINLOS', self::sayMonth($restrcheckin['mon']), $roomrestr[$restrcheckin['mon']]['minlos']);
					}
				}
				$comborestr = self::parseJsDrangeWdayCombo($roomrestr[$restrcheckin['mon']]);
				if (count($comborestr) > 0) {
					if (array_key_exists($restrcheckin['wday'], $comborestr)) {
						if (!in_array($restrcheckout['wday'], $comborestr[$restrcheckin['wday']])) {
							$restrictionerrmsg = JText::sprintf('VBRESTRTIPWDAYCOMBO', self::sayMonth($restrcheckin['mon']), self::sayWeekDay($comborestr[$restrcheckin['wday']][0]).(count($comborestr[$restrcheckin['wday']]) == 2 ? '/'.self::sayWeekDay($comborestr[$restrcheckin['wday']][1]) : ''), self::sayWeekDay($restrcheckin['wday']));
						}
					}
				}
			} elseif (!empty($roomrestr[$restrcheckin['mon']]['ctad']) || !empty($roomrestr[$restrcheckin['mon']]['ctdd'])) {
				if (!empty($roomrestr[$restrcheckin['mon']]['ctad'])) {
					$ctarestrictions = explode(',', $roomrestr[$restrcheckin['mon']]['ctad']);
					if (in_array('-'.$restrcheckin['wday'].'-', $ctarestrictions)) {
						$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTAMONTH', self::sayWeekDay($restrcheckin['wday']), self::sayMonth($restrcheckin['mon']));
					}
				}
				if (!empty($roomrestr[$restrcheckin['mon']]['ctdd'])) {
					$ctdrestrictions = explode(',', $roomrestr[$restrcheckin['mon']]['ctdd']);
					if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions)) {
						$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTDMONTH', self::sayWeekDay($restrcheckout['wday']), self::sayMonth($restrcheckin['mon']));
					}
				}
			}
			if (!empty($roomrestr[$restrcheckin['mon']]['maxlos']) && $roomrestr[$restrcheckin['mon']]['maxlos'] > 0 && $roomrestr[$restrcheckin['mon']]['maxlos'] > $roomrestr[$restrcheckin['mon']]['minlos']) {
				if ($daysdiff > $roomrestr[$restrcheckin['mon']]['maxlos']) {
					$restrictionerrmsg = JText::sprintf('VBRESTRTIPMAXLOSEXCEEDED', self::sayMonth($restrcheckin['mon']), $roomrestr[$restrcheckin['mon']]['maxlos']);
				}
			}
			if ($daysdiff < $roomrestr[$restrcheckin['mon']]['minlos']) {
				$restrictionerrmsg = JText::sprintf('VBRESTRTIPMINLOSEXCEEDED', self::sayMonth($restrcheckin['mon']), $roomrestr[$restrcheckin['mon']]['minlos']);
			}
		} elseif (array_key_exists('range', $roomrestr)) {
			$restrictionsvalid = true;
			/**
			 * We use this map to know which restriction IDs are okay or not okay with the Min LOS.
			 * The most recent restrictions will have a higher priority over the oldest ones.
			 * 
			 * @since 	1.12.1
			 */
			$minlos_priority = array(
				'ok'  => array(),
				'nok' => array()
			);
			foreach ($roomrestr['range'] as $restr) {
				/**
				 * We should not always add 82799 seconds to the end date of the restriction
				 * because if they only last for one day (like a Saturday), then $restr['dto']
				 * will be already set to the time 23:59:59.
				 * 
				 * @since 	1.13 (J) - 1.2.18 (WP)
				 */
				$end_operator = date('Y-m-d', $restr['dfrom']) != date('Y-m-d', $restr['dto']) ? 82799 : 0;
				//
				if ($restr['dfrom'] <= $restrcheckin[0] && ($restr['dto'] + $end_operator) >= $restrcheckin[0]) {
					// restriction found for this date range based on arrival date, check if compliant
					$restrictions_affcount++;
					if (strlen((string)$restr['wday']) > 0) {
						$rvalidwdays = array($restr['wday']);
						if (strlen((string)$restr['wdaytwo']) > 0) {
							$rvalidwdays[] = $restr['wdaytwo'];
						}
						if (!in_array($restrcheckin['wday'], $rvalidwdays)) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VBRESTRTIPWDAYARRIVALRANGE', self::sayWeekDay($restr['wday']).(strlen((string)$restr['wdaytwo']) ? '/'.self::sayWeekDay($restr['wdaytwo']) : ''));
						} elseif ($restr['multiplyminlos'] == 1) {
							if (($daysdiff % $restr['minlos']) != 0) {
								$restrictionsvalid = false;
								$restrictionerrmsg = JText::sprintf('VBRESTRTIPMULTIPLYMINLOSRANGE', $restr['minlos']);
							}
						}
						$comborestr = self::parseJsDrangeWdayCombo($restr);
						if ($comborestr) {
							if (array_key_exists($restrcheckin['wday'], $comborestr)) {
								if (!in_array($restrcheckout['wday'], $comborestr[$restrcheckin['wday']])) {
									$restrictionsvalid = false;
									$restrictionerrmsg = JText::sprintf('VBRESTRTIPWDAYCOMBORANGE', self::sayWeekDay($comborestr[$restrcheckin['wday']][0]).(count($comborestr[$restrcheckin['wday']]) == 2 ? '/'.self::sayWeekDay($comborestr[$restrcheckin['wday']][1]) : ''), self::sayWeekDay($restrcheckin['wday']));
								}
							}
						}
					} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
						if (!empty($restr['ctad'])) {
							$ctarestrictions = explode(',', $restr['ctad']);
							if (in_array('-'.$restrcheckin['wday'].'-', $ctarestrictions)) {
								$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTARANGE', self::sayWeekDay($restrcheckin['wday']));
							}
						}
						if (!empty($restr['ctdd'])) {
							$ctdrestrictions = explode(',', $restr['ctdd']);
							if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions) && $restrcheckout[0] <= ($restr['dto'] + $end_operator)) {
								$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTDRANGE', self::sayWeekDay($restrcheckout['wday']));
							}
						}
					}
					if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] > $restr['minlos']) {
						if ($daysdiff > $restr['maxlos']) {
							$restrictionsvalid = false;
							$restrictionerrmsg = JText::sprintf('VBRESTRTIPMAXLOSEXCEEDEDRANGE', $restr['maxlos']);
						}
					}
					if ($daysdiff < $restr['minlos']) {
						$restrictionsvalid = false;
						$restrictionerrmsg = JText::sprintf('VBRESTRTIPMINLOSEXCEEDEDRANGE', $restr['minlos']);
						array_push($minlos_priority['nok'], (int)$restr['id']);
					} else {
						array_push($minlos_priority['ok'], (int)$restr['id']);
					}
				} elseif ($restr['dfrom'] <= $restrcheckout[0] && ($restr['dto'] + $end_operator) >= $restrcheckout[0] && !empty($restr['ctdd'])) {
					/**
					 * We validate the CTD restrictions depending on the check-out date.
					 * 
					 * @since 	1.16.3 (J) - 1.6.3 (WP)
					 */
					$ctdrestrictions = explode(',', $restr['ctdd']);
					if (in_array('-'.$restrcheckout['wday'].'-', $ctdrestrictions)) {
						$restrictions_affcount++;
						$restrictionsvalid = false;
						$restrictionerrmsg = JText::sprintf('VBRESTRERRWDAYCTDRANGE', VikBooking::sayWeekDay($restrcheckout['wday']));
					}
				}
			}
			if (!$restrictionsvalid && count($minlos_priority['ok']) && count($minlos_priority['nok']) && max($minlos_priority['ok']) > max($minlos_priority['nok'])) {
				// we unset the error message because a more recent restriction is allowing this MinLOS
				$restrictionerrmsg = '';
			}
		}
		//April 2017 - Check global restriction of Min LOS for TAC functions in VBO and VCM
		if (empty($restrictionerrmsg) && count($roomrestr) && $restrictions_affcount <= 0) {
			//Check global MinLOS (only in case there are no restrictions affecting these dates or no restrictions at all)
			$globminlos = self::getDefaultNightsCalendar();
			if ($globminlos > 1 && $daysdiff < $globminlos) {
				$restrictionerrmsg = JText::sprintf('VBRESTRERRMINLOSEXCEEDEDRANGE', $globminlos);
			}
		}
		//

		return $restrictionerrmsg;
	}
	
	public static function parseJsDrangeWdayCombo($drestr) {
		$combo = array();
		if (strlen((string)$drestr['wday']) && strlen((string)$drestr['wdaytwo']) && !empty($drestr['wdaycombo'])) {
			$cparts = explode(':', $drestr['wdaycombo']);
			foreach ($cparts as $kc => $cw) {
				if (!empty($cw)) {
					$nowcombo = explode('-', $cw);
					$combo[intval($nowcombo[0])][] = intval($nowcombo[1]);
				}
			}
		}
		return $combo;
	}

	public static function validateRoomPackage($pkg_id, $rooms, $numnights, $checkints, $checkoutts) {
		$dbo = JFactory::getDbo();
		$pkg = array();
		$q = "SELECT * FROM `#__vikbooking_packages` WHERE `id`='".intval($pkg_id)."';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$pkg = $dbo->loadAssoc();
			$vbo_tn = self::getTranslator();
			$vbo_tn->translateContents($pkg, '#__vikbooking_packages');
		} else {
			return JText::translate('VBOPKGERRNOTFOUND');
		}
		$rooms_req = array();
		foreach ($rooms as $num => $room) {
			if (!empty($room['id']) && !in_array($room['id'], $rooms_req)) {
				$rooms_req[] = $room['id'];
			}
		}
		$q = "SELECT `id` FROM `#__vikbooking_packages_rooms` WHERE `idpackage`=".$pkg['id']." AND `idroom` IN (".implode(', ', $rooms_req).");";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() != count($rooms_req)) {
			//error, not all the rooms requested are available for this package
			return JText::translate('VBOPKGERRNOTROOM');
		}
		if ($numnights < $pkg['minlos'] || ($pkg['maxlos'] > 0 && $numnights > $pkg['maxlos'])) {
			return JText::translate('VBOPKGERRNUMNIGHTS');
		}
		if ($checkints < $pkg['dfrom'] || $checkints > $pkg['dto']) {
			return JText::translate('VBOPKGERRCHECKIND');
		}
		if ($checkoutts < $pkg['dfrom'] || ($checkoutts > $pkg['dto'] && date('Y-m-d', $pkg['dfrom']) != date('Y-m-d', $pkg['dto']))) {
			//VBO 1.10 - we allow a check-out date after the pkg validity-end-date only if the validity dates are equal (dfrom & dto)
			return JText::translate('VBOPKGERRCHECKOUTD');
		}
		if (!empty($pkg['excldates'])) {
			//this would check if any stay date is excluded
			//$bookdates_ts = self::getGroupDays($checkints, $checkoutts, $numnights);
			//check just the arrival and departure dates
			$bookdates_ts = array($checkints, $checkoutts);
			$bookdates = array();
			foreach ($bookdates_ts as $bookdate_ts) {
				$info_d = getdate($bookdate_ts);
				$bookdates[] = $info_d['mon'].'-'.$info_d['mday'].'-'.$info_d['year'];
			}
			$edates = explode(';', $pkg['excldates']);
			foreach ($edates as $edate) {
				if (!empty($edate) && in_array($edate, $bookdates)) {
					return JText::sprintf('VBOPKGERREXCLUDEDATE', $edate);
				}
			}
		}
		return $pkg;
	}

	public static function getPackage($pkg_id) {
		$dbo = JFactory::getDbo();
		$pkg = array();
		$q = "SELECT * FROM `#__vikbooking_packages` WHERE `id`='".intval($pkg_id)."';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() == 1) {
			$pkg = $dbo->loadAssoc();
		}
		return $pkg;
	}
	
	public static function getRoomParam($paramname, $paramstr)
	{
		if (empty($paramstr)) {
			return '';
		}

		$paramarr = json_decode($paramstr, true);

		if (is_array($paramarr) && isset($paramarr[$paramname])) {
			return $paramarr[$paramname];
		}

		return '';
	}

	public static function filterNightsSeasonsCal($arr_nights) {
		$nights = array();
		foreach ($arr_nights as $night) {
			if (intval(trim($night)) > 0) {
				$nights[] = intval(trim($night));
			}
		}
		sort($nights);
		return array_unique($nights);
	}

	public static function getSeasonRangeTs($from, $to, $year) {
		$sfrom = 0;
		$sto = 0;
		$tsbase = mktime(0, 0, 0, 1, 1, $year);
		$curyear = $year;
		$tsbasetwo = $tsbase;
		$curyeartwo = $year;
		if ($from > $to) {
			//between two years
			$curyeartwo += 1;
			$tsbasetwo = mktime(0, 0, 0, 1, 1, $curyeartwo);
		}
		$sfrom = ($tsbase + $from);
		$sto = ($tsbasetwo + $to);
		if ($curyear % 4 == 0 && ($curyear % 100 != 0 || $curyear % 400 == 0)) {
			//leap years
			$infoseason = getdate($sfrom);
			$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
			if ($infoseason[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom += 86400;
				if ($curyear == $curyeartwo) {
					$sto += 86400;
				}
			}
		} elseif ($curyeartwo % 4 == 0 && ($curyeartwo % 100 != 0 || $curyeartwo % 400 == 0)) {
			//leap years
			$infoseason = getdate($sto);
			$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
			if ($infoseason[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sto += 86400;
			}
		}
		return array($sfrom, $sto);
	}

	public static function sortSeasonsRangeTs ($all_seasons) {
		$sorted = array();
		$map = array();
		foreach ($all_seasons as $key => $season) {
			$map[$key] = $season['from_ts'];
		}
		asort($map);
		foreach ($map as $key => $s) {
			$sorted[] = $all_seasons[$key];
		}
		return $sorted;
	}

	public static function formatSeasonDates ($from_ts, $to_ts) {
		$one = getdate($from_ts);
		$two = getdate($to_ts);
		$months_map = array(
			1 => JText::translate('VBSHORTMONTHONE'),
			2 => JText::translate('VBSHORTMONTHTWO'),
			3 => JText::translate('VBSHORTMONTHTHREE'),
			4 => JText::translate('VBSHORTMONTHFOUR'),
			5 => JText::translate('VBSHORTMONTHFIVE'),
			6 => JText::translate('VBSHORTMONTHSIX'),
			7 => JText::translate('VBSHORTMONTHSEVEN'),
			8 => JText::translate('VBSHORTMONTHEIGHT'),
			9 => JText::translate('VBSHORTMONTHNINE'),
			10 => JText::translate('VBSHORTMONTHTEN'),
			11 => JText::translate('VBSHORTMONTHELEVEN'),
			12 => JText::translate('VBSHORTMONTHTWELVE')
		);
		$mday_map = array(
			1 => JText::translate('VBMDAYFRIST'),
			2 => JText::translate('VBMDAYSECOND'),
			3 => JText::translate('VBMDAYTHIRD'),
			'generic' => JText::translate('VBMDAYNUMGEN')
		);
		if ($one['year'] == $two['year']) {
			return $one['year'].' '.$months_map[(int)$one['mon']].' '.$one['mday'].'<sup>'.(array_key_exists((int)substr($one['mday'], -1), $mday_map) && ($one['mday'] < 10 || $one['mday'] > 20) ? $mday_map[(int)substr($one['mday'], -1)] : $mday_map['generic']).'</sup> - '.$months_map[(int)$two['mon']].' '.$two['mday'].'<sup>'.(array_key_exists((int)substr($two['mday'], -1), $mday_map) && ($two['mday'] < 10 || $two['mday'] > 20) ? $mday_map[(int)substr($two['mday'], -1)] : $mday_map['generic']).'</sup>';
		}
		return $months_map[(int)$one['mon']].' '.$one['mday'].'<sup>'.(array_key_exists((int)substr($one['mday'], -1), $mday_map) && ($one['mday'] < 10 || $one['mday'] > 20) ? $mday_map[(int)substr($one['mday'], -1)] : $mday_map['generic']).'</sup> '.$one['year'].' - '.$months_map[(int)$two['mon']].' '.$two['mday'].'<sup>'.(array_key_exists((int)substr($two['mday'], -1), $mday_map) && ($two['mday'] < 10 || $two['mday'] > 20) ? $mday_map[(int)substr($two['mday'], -1)] : $mday_map['generic']).'</sup> '.$two['year'];
	}

	public static function getFirstCustDataField($custdata) {
		$first_field = '';
		if (strpos($custdata, JText::translate('VBDBTEXTROOMCLOSED')) !== false) {
			//Room is closed with this booking
			return '----';
		}
		$parts = explode("\n", $custdata);
		foreach ($parts as $part) {
			if (!empty($part)) {
				$field = explode(':', trim($part));
				if (!empty($field[1])) {
					return trim($field[1]);
				}
			}
		}
		return $first_field;
	}

	/**
	 * This method composes a string to be logged for the admin
	 * to keep track of what was inside the booking before the
	 * modification. Returns a string and it uses language definitions
	 * that should be available on the front-end and back-end INI files.
	 *
	 * @param 	array 	$old_booking 		the array of the booking prior to the modification
	 * @param 	array 	$room_stay_dates 	optional list of room stay information in case of split stays.
	 *
	 * @return 	string 						text describing the situation with the booking before the changes.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP) 	added second argument.
	 */
	public static function getLogBookingModification($old_booking, $room_stay_dates = [])
	{
		$vbo_df = self::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
		$wdays_map = array(
			JText::translate('VBWEEKDAYZERO'),
			JText::translate('VBWEEKDAYONE'),
			JText::translate('VBWEEKDAYTWO'),
			JText::translate('VBWEEKDAYTHREE'),
			JText::translate('VBWEEKDAYFOUR'),
			JText::translate('VBWEEKDAYFIVE'),
			JText::translate('VBWEEKDAYSIX'),
		);
		$now_info = getdate();
		$checkin_info = getdate($old_booking['checkin']);
		$checkout_info = getdate($old_booking['checkout']);

		$datemod = $wdays_map[$now_info['wday']].', '.date($df.' H:i', $now_info[0]);
		$prev_nights = $old_booking['days'].' '.($old_booking['days'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY'));
		$prev_dates = $prev_nights.' - '.$wdays_map[$checkin_info['wday']].', '.date($df.' H:i', $checkin_info[0]).' - '.$wdays_map[$checkout_info['wday']].', '.date($df.' H:i', $checkout_info[0]);
		$prev_rooms = '';

		$orooms_map = [];
		$orooms_arr = [];
		
		if (isset($old_booking['rooms_info'])) {
			foreach ($old_booking['rooms_info'] as $oroom) {
				$orooms_arr[] = $oroom['name'].', '.JText::translate('VBMAILADULTS').': '.$oroom['adults'].', '.JText::translate('VBMAILCHILDREN').': '.$oroom['children'];
				if (!empty($oroom['idroom'])) {
					$orooms_map[$oroom['idroom']] = $oroom['name'];
				}
			}
			$prev_rooms = implode("\n", $orooms_arr);
		}

		if (!empty($old_booking['split_stay']) && !empty($room_stay_dates) && count($orooms_map)) {
			$split_stay_prev_infos = [];
			foreach ($room_stay_dates as $rs_ind => $room_stay) {
				if (empty($room_stay['idroom']) || !isset($orooms_map[$room_stay['idroom']])) {
					continue;
				}
				$room_checkin  = date('Y-m-d', $room_stay['checkin']);
				$room_checkout = date('Y-m-d', $room_stay['checkout']);
				$room_nights   = !empty($room_stay['nights']) ? $room_stay['nights'] : 0;

				$split_stay_descr  = $orooms_map[$room_stay['idroom']];
				if (!empty($room_nights)) {
					$split_stay_descr .= ': ' . $room_nights . ' ' . ($room_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY'));
				}
				$split_stay_descr .= ', ';
				$split_stay_descr .= $room_checkin . ' - ' . $room_checkout;

				// push split stay description string
				$split_stay_prev_infos[] = $split_stay_descr;
			}
			if (count($split_stay_prev_infos)) {
				$prev_rooms .= "\n" . JText::translate('VBO_SPLIT_STAY') . ":\n" . implode("\n", $split_stay_prev_infos);
			}
		}

		$currencyname = self::getCurrencyName();
		$prev_total = $currencyname.' '.self::numberFormat($old_booking['total']);

		return JText::sprintf('VBOBOOKMODLOGSTR', $datemod, $prev_dates, $prev_rooms, $prev_total);
	}

	/**
	 * This method invokes the class
	 * VikChannelManagerLogos (new in VCM 1.6.4) to
	 * map the name of a channel to its corresponding logo.
	 * The method can also be used to get an istance of the class.
	 *
	 * @param 	mixed 		$provenience 	either a string or an array with main and sub channels.
	 * @param 	boolean 	$get_istance
	 *
	 * @return 	mixed 		boolean if the Class doesn't exist or if the provenience cannot be matched. Instance otherwise.
	 */
	public static function getVcmChannelsLogo($provenience, $get_istance = false) {
		if (!file_exists(VCM_ADMIN_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'logos.php')) {
			return false;
		}
		if (!class_exists('VikChannelManagerLogos')) {
			require_once(VCM_ADMIN_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'logos.php');
		}
		
		/**
		 * Due to the new iCal channel we now support main and sub channels.
		 * 
		 * @since 	1.13 - VCM 1.7.0
		 */
		if (is_string($provenience) && strpos($provenience, '_') !== false) {
			$provenience = explode('_', $provenience);
		}
		$main_channel = '';
		$full_channel = '';
		if (is_array($provenience)) {
			if (count($provenience) > 1) {
				$main_channel = $provenience[1];
			} else {
				$main_channel = $provenience[0];
			}
			if (stripos($provenience[0], 'ical') !== false) {
				$full_channel = implode('_', $provenience);
			}
		} else {
			$main_channel = $provenience;
		}

		// get object instance by passing the main provenience
		$obj = new VikChannelManagerLogos($main_channel);
		
		// update provenience with main and full channel
		if (!empty($full_channel)) {
			$obj->setProvenience($main_channel, $full_channel);
		}

		// return either the instance or the logo URL for this channel source
		return $get_istance ? $obj : $obj->getLogoURL();
	}

	public static function vcmAutoUpdate()
	{
		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			return -1;
		}

		$vcm_auto_upd = (int)VBOFactory::getConfig()->get('vcmautoupd', 0);

		return $vcm_auto_upd ? 1 : 0;
	}

	public static function getVcmInvoker()
	{
		if (!class_exists('VboVcmInvoker')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "vcm.php");
		}

		return new VboVcmInvoker();
	}

	/**
	 * Returns an instance of VikBooking History object.
	 * 
	 * @param 	int 	$bid 	optional booking ID to bind.
	 * 
	 * @return 	VboBookingHistory
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP) added argument $bid.
	 */
	public static function getBookingHistoryInstance($bid = 0)
	{
		if (!class_exists('VboBookingHistory')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "history.php");
		}

		return new VboBookingHistory($bid);
	}

	/**
	 * Returns an instance of the VCMChatHandler class to handle
	 * the messaging/chat for the given reservation ID.
	 * 
	 * @param 	int 	$oid 		the ID of the booking in VBO
	 * @param 	string 	$channel 	the name of the source channel
	 * 
	 * @return 	mixed 	null if VCM is not available, VCMChatHandler instance otherwise
	 * 
	 * @since 	1.11.2 (J) - 1.1.2 (WP)
	 * @since 	1.16.0 (J) - 1.6.0 (WP) the method can also be used to require the chat handler.
	 * @since 	1.16.4 (J) - 1.6.4 (WP) preloading widgets on WP when VCM is inactive is prevented.
	 */
	public static function getVcmChatInstance($oid, $channel = null)
	{
		$vcm_messaging_helper = VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'chat' . DIRECTORY_SEPARATOR . 'handler.php';
		if (!is_file($vcm_messaging_helper)) {
			// VCM is not available
			return null;
		}

		if (VBOPlatformDetection::isWordPress() && !defined('VIKCHANNELMANAGER_LIBRARIES')) {
			// VCM must be inactive, prevent the loading of the instance
			return null;
		}

		// make sure the channel name is correct as it may contain the sub-network
		if (!empty($channel)) {
			$segments = explode('_', $channel);
			if (count($segments) > 1) {
				// we take the first segment, as the second could be the source sub-network (expedia_Hotels.com)
				$channel = $segments[0];
			}
		}

		// always require main file of the abstract class even if arguments are empty/invalid
		require_once $vcm_messaging_helper;

		// return the instance of the class for this channel handler
		return VCMChatHandler::getInstance($oid, $channel);
	}

	/**
	 * Returns an instance of the VCMOpportunityHandler class to handle
	 * the various opportunities through VCM.
	 * 
	 * @return 	mixed 	null if VCM is not available, VCMOpportunityHandler instance otherwise
	 * 
	 * @since 	1.2.0
	 */
	public static function getVcmOpportunityInstance() {
		$vcm_opp_helper = VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'opportunity.php';
		if (!is_file($vcm_opp_helper)) {
			// VCM is not available
			return null;
		}
		// require main file of the class
		require_once $vcm_opp_helper;
		// return the instance of the class
		return VCMOpportunityHandler::getInstance();
	}

	/**
	 * Tells if the Channel Manager can handle certain reporting operations with Booking.com.
	 * 
	 * @param 	  array 	$data 	optional data to bind to the object.
	 * 
	 * @return 	  bool
	 * 
	 * @since 	  1.16.8 (J) - 1.6.8 (WP)  the method now relies on a recent VCM class.
	 * 
	 * @requires  VCM >= 1.8.24
	 */
	public static function vcmBcomReportingSupported(array $data = [])
	{
		return class_exists('VCMOtaReporting') && VCMOtaReporting::getInstance($data)->reportingAllowed();
	}

	/**
	 * Gets a list of all channels supporting the promotions.
	 * 
	 * @param 		string 	$key 	the key of the handler.
	 * 
	 * @return 		mixed 	false if VCM is not installed, empty array otherwise.
	 * 
	 * @requires 	Vik Channel Manager 1.7.1
	 * 
	 * @since 		1.3.0
	 */
	public static function getPromotionHandlers($key = null)
	{
		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			// VCM not installed
			return false;
		}

		if (!class_exists('VikChannelManager') || !method_exists('VikChannelManager', 'getPromotionHandlers')) {
			// VCM is outdated yet installed: an empty array is sufficient to not display warning messages
			return [];
		}

		return VikChannelManager::getPromotionHandlers($key);
	}

	/**
	 * Gets the factors for suggesting the application of the promotions.
	 * 
	 * @param 		mixed 	$data 	some optional instructions to be passed as argument.
	 * 
	 * @return 		mixed 	false if VCM is not installed, associative array otherwise.
	 * 
	 * @requires 	Vik Channel Manager 1.7.1
	 * 
	 * @since 		1.3.0
	 */
	public static function getPromotionFactors($data = null)
	{
		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			// VCM not installed
			return false;
		}

		if (!class_exists('VikChannelManager') || !method_exists('VikChannelManager', 'getPromotionFactors')) {
			// VCM is outdated
			return false;
		}

		return VikChannelManager::getPromotionFactors($data);
	}
	
	public static function getTheme()
	{
		return VBOFactory::getConfig()->get('theme', '');
	}
	
	public static function getFooterOrdMail($vbo_tn = null)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='footerordmail';";
		$dbo->setQuery($q);
		$ft = $dbo->loadAssocList();

		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}

		$vbo_tn->translateContents($ft, '#__vikbooking_texts');

		return $ft ? $ft[0]['setting'] : '';
	}
	
	public static function requireLogin()
	{
		return VBOFactory::getConfig()->getBool('requirelogin', false);
	}

	public static function autoRoomUnit()
	{
		return VBOFactory::getConfig()->getBool('autoroomunit', false);
	}

	public static function todayBookings()
	{
		static $todayBookings = null;

		if ($todayBookings === null) {
			$todayBookings = VBOFactory::getConfig()->getBool('todaybookings', false);
		}

		return $todayBookings;
	}
	
	public static function couponsEnabled()
	{
		return VBOFactory::getConfig()->getBool('enablecoupons', false);
	}

	public static function customersPinEnabled()
	{
		return VBOFactory::getConfig()->getBool('enablepin', false);
	}
	
	/**
	 * Detects the type of visitor from the user agent.
	 * Known types are: computer, smartphone, tablet.
	 * 
	 * @param 	boolean  $returnua 		whether the type of visitor should be returned. If false
	 * 									boolean is returned in case of mobile device detected.
	 * @param 	boolean  $loadassets 	whether the system should load an apposite CSS file.
	 * 
	 * @return 	mixed 	 string for the type of visitor or boolean if mobile detected.
	 * 
	 * @since 	1.0.13 - Revision September 2018
	 */
	public static function detectUserAgent($returnua = false, $loadassets = true) {
		$session = JFactory::getSession();
		$sval = $session->get('vbuseragent', '');
		$mobiles = array('tablet', 'smartphone');
		if (!empty($sval)) {
			if ($loadassets) {
				self::userAgentStyleSheet($sval);
			}
			return $returnua ? $sval : in_array($sval, $mobiles);
		}

		// detect visitor (device) type
		$visitoris = 'computer';
		try {
			if (!class_exists('MobileDetector')) {
				require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "mobile_detector.php";
			}
			$detector = new MobileDetector;
			$visitoris = $detector->isMobile() ? ($detector->isTablet() ? 'tablet' : 'smartphone') : 'computer';
		} catch (Exception $e) {
			// do nothing
		}

		$session->set('vbuseragent', $visitoris);
		if ($loadassets) {
			self::userAgentStyleSheet($visitoris);
		}

		return $returnua ? $visitoris : in_array($visitoris, $mobiles);
	}
	
	public static function userAgentStyleSheet($ua)
	{
		/**
		 * @wponly 	in order to not interfere with AJAX requests, we do nothing if doing AJAX.
		 */
		if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
			return;
		}
		//

		$document = JFactory::getDocument();
		/**
		 * @wponly 	the following CSS files are located in /site/resources/ for WP, not just on /site
		 */
		if ($ua == 'smartphone') {
			$document->addStyleSheet(VBO_SITE_URI.'resources/vikbooking_smartphones.css');
		} elseif ($ua == 'tablet') {
			$document->addStyleSheet(VBO_SITE_URI.'resources/vikbooking_tablets.css');
		}
		return true;
	}
	
	public static function loadJquery()
	{
		return VBOFactory::getConfig()->getBool('loadjquery', true);
	}

	public static function loadBootstrap()
	{
		return VBOFactory::getConfig()->getBool('bootstrap', true);
	}

	public static function allowMultiLanguage()
	{
		return VBOFactory::getConfig()->getBool('multilang', true);
	}

	public static function getTranslator()
	{
		if (!class_exists('VikBookingTranslator')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'translator.php');
		}

		return new VikBookingTranslator();
	}

	/**
	 * New method's name spelled correctly. The object will be cached by default.
	 * 
	 * @return 	VikBookingCustomersPin
	 * 
	 * @since 	1.15.5 (J) - 1.5.11 (WP) cache the instance of the object to better support new cookies set.
	 */
	public static function getCPinInstance()
	{
		static $cpin_instance = null;

		if (!$cpin_instance) {
			require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'cpin.php';

			$cpin_instance = new VikBookingCustomersPin();
		}

		return $cpin_instance;
	}

	/**
	 * Old method's name spelled wrongly for BC.
	 * 
	 * @return 	VikBookingCustomersPin
	 */
	public static function getCPinIstance()
	{
		return self::getCPinInstance();
	}

	/**
	 * Returns an instance of the VikBookingTracker Class.
	 * It is also possible to call this method to just require the library.
	 * This is useful for the back-end to access some static methods
	 * without tracking any data.
	 * 
	 * @param 	boolean 	$require_only 	whether to return the object.
	 * 
	 * @return 	VikBookingTracker
	 * 
	 * @since 	1.11
	 */
	public static function getTracker($require_only = false) {
		if (!class_exists('VikBookingTracker')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tracker.php");
		}
		return $require_only ? true : VikBookingTracker::getInstance();
	}

	/**
	 * Returns an instance of the VikBookingOperator Class.
	 * 
	 * @return 	VikBookingOperator
	 * 
	 * @since 	1.11
	 */
	public static function getOperatorInstance() {
		if (!class_exists('VikBookingOperator')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "operator.php");
		}
		return VikBookingOperator::getInstance();
	}

	/**
	 * Returns an instance of the VikBookingFestivities Class.
	 * 
	 * @return 	VikBookingFestivities
	 * 
	 * @since 	1.12
	 */
	public static function getFestivitiesInstance() {
		if (!class_exists('VikBookingFestivities')) {
			require_once(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'festivities.php');
		}
		return VikBookingFestivities::getInstance();
	}

	/**
	 * Returns an instance of the VikBookingCriticalDates Class.
	 * 
	 * @return 	VikBookingCriticalDates
	 * 
	 * @since 	1.13.5
	 */
	public static function getCriticalDatesInstance() {
		if (!class_exists('VikBookingCriticalDates')) {
			require_once(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'critical_dates.php');
		}
		return VikBookingCriticalDates::getInstance();
	}

	/**
	 * Checks whether the chat is enabled.
	 * 
	 * @return 	int 	-1 if VCM is not installed, 0 if disabled, 1 otherwise
	 * 
	 * @since 	1.12
	 */
	public static function chatEnabled() {
		if (!is_file(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			return -1;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='chatenabled';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// enable the chat by default if VCM is installed
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('chatenabled', '1');";
			$dbo->setQuery($q);
			$dbo->execute();
			return 1;
		}
		$s = $dbo->loadResult();
		return intval($s);
	}

	/**
	 * Loads the chat parameters from the configuration.
	 * 
	 * @return 	object 	stdClass object from decoded JSON string
	 * 
	 * @since 	1.12
	 */
	public static function getChatParams() {
		if (!is_file(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			return new stdClass;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='chatparams';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// compose default and basic chat params for the first time
			$basic_params = new stdClass;
			$basic_params->res_status = array('confirmed', 'standby', 'cancelled');
			$basic_params->av_type = 'checkin';
			$basic_params->av_days = 0;

			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('chatparams', ".$dbo->quote(json_encode($basic_params)).");";
			$dbo->setQuery($q);
			$dbo->execute();

			return $basic_params;
		}
		return json_decode($dbo->loadResult());
	}

	/**
	 * Checks whether the pre-checkin is enabled.
	 * 
	 * @return 	int 	0 if disabled, 1 otherwise
	 * 
	 * @since 	1.12
	 */
	public static function precheckinEnabled() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='precheckinenabled';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// enable the pre-checkin by default
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('precheckinenabled', '1');";
			$dbo->setQuery($q);
			$dbo->execute();
			return 1;
		}
		$s = $dbo->loadResult();
		return intval($s);
	}

	/**
	 * Returns the minimum number of days in advance to
	 * enable the pre-checkin via front-end.
	 * 
	 * @return 	int 	the min number of days in advance for pre-checkin.
	 * 
	 * @since 	1.12
	 */
	public static function precheckinMinOffset() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='precheckinminoffset';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// set the limit to 1 day before arrival by default
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('precheckinminoffset', '1');";
			$dbo->setQuery($q);
			$dbo->execute();
			return 1;
		}
		$s = $dbo->loadResult();
		return intval($s);
	}

	/**
	 * Whether upselling extra services is enabled.
	 * 
	 * @return 	boolean 	true if enabled, false otherwise.
	 * 
	 * @since 	1.13
	 */
	public static function upsellingEnabled() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='upselling';";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// enable the upselling feature by default
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES('upselling', '1');";
			$dbo->setQuery($q);
			$dbo->execute();
			return true;
		}
		return (intval($dbo->loadResult()) > 0);
	}

	/**
	 * Collects a list of options/extra that can be upsold for the
	 * the rooms booked.
	 * 
	 * @param 	array 	$upsell_data 	list of stdClass objects for each room booked.
	 * @param 	array 	$info 			some booking details (id, checkin, checkout).
	 * @param 	object 	$vbo_tn 		the translation object.
	 * 
	 * @return 	array 	list of upsellable options for each room booked.
	 * 
	 * @since 	1.3.0
	 */
	public static function loadUpsellingData($upsell_data, $info, $vbo_tn) {
		$dbo = JFactory::getDbo();
		// get all options for all rooms booked
		$all_room_ids = array();
		foreach ($upsell_data as $v) {
			if (!in_array($v->id, $all_room_ids)) {
				array_push($all_room_ids, $v->id);
			}
		}
		$q = "SELECT `id`, `idopt` FROM `#__vikbooking_rooms` WHERE `id` IN (" . implode(', ', $all_room_ids) . ");";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no rooms found
			return array();
		}
		$records = $dbo->loadAssocList();
		$all_options = array();
		$rooms_options = array();
		foreach ($records as $v) {
			$allopts = explode(';', $v['idopt']);
			$room_opt = array();
			foreach ($allopts as $o) {
				if (empty($o)) {
					continue;
				}
				if (!in_array((int)$o, $all_options)) {
					array_push($all_options, (int)$o);
				}
				array_push($room_opt, (int)$o);
			}
			$rooms_options[$v['id']] = $room_opt;
		}
		if (!count($all_options)) {
			// no options found
			return array();
		}
		// load all options that could be used by the booked rooms no matter what was already booked
		$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id` IN (" . implode(', ', $all_options) . ") AND `forcesel`=0 AND `ifchildren`=0 AND `is_citytax`=0 AND `is_fee`=0;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// no upsell-able options found
			return array();
		}
		$records = $dbo->loadAssocList();
		// filter options by available date and translate records
		self::filterOptionalsByDate($records, $info['checkin'], $info['checkout']);
		$vbo_tn->translateContents($records, '#__vikbooking_optionals');
		$records = !is_array($records) ? array() : $records;
		//
		$tot_upsellable = 0;
		foreach ($upsell_data as $k => $rdata) {
			if (!isset($upsell_data[$k]->upsellable)) {
				$upsell_data[$k]->upsellable = array();
			}
			foreach ($records as $opt) {
				if (!empty($opt['ageintervals'])) {
					// upsellable options should not contain age intervals for children
					continue;
				}
				if (!in_array($opt['id'], $rooms_options[$rdata->id])) {
					// this option is not assigned to this room
					continue;
				}
				// check if the option is suited for this room party
				$clone_opt = array($opt);
				self::filterOptionalsByParty($clone_opt, $rdata->adults, $rdata->children);
				if (!is_array($clone_opt) || !count($clone_opt)) {
					// this option is not suited for this room party
					continue;
				}
				//
				if (in_array($opt['id'], $rdata->options)) {
					// this option has already been booked
					continue;
				}
				// push this option and increase counter
				array_push($upsell_data[$k]->upsellable, $opt);
				$tot_upsellable++;
			}
		}
		
		// if no upsellable options were found, we return an empty array
		return $tot_upsellable > 0 ? $upsell_data : array();
	}

	/**
	 * Returns the minimum days in advance for booking, by considering by default
	 * also the property closing dates. If the property is currently closed, then
	 * the minimum number of days in advance will be increased.
	 * 
	 * @param 	bool 	$no_closing_dates 	whether to skip checking closing dates.
	 * 
	 * @return 	int 						the number of days in advance for booking.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP) the signature was changed from:
	 *			getMinDaysAdvance($skipsession = false) to:
	 * 			getMinDaysAdvance($no_closing_dates = false)
	 * 			This was made to return the proper number of days in advance
	 * 			in case the property is currently closed.
	 */
	public static function getMinDaysAdvance($no_closing_dates = false) {
		// cache value in static var
		static $getMinDaysAdvance = null;

		if ($getMinDaysAdvance) {
			return $getMinDaysAdvance;
		}
		//

		$dbo = JFactory::getDbo();
		
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='mindaysadvance';";
		$dbo->setQuery($q);
		$dbo->execute();
		$mind = $dbo->getNumRows() ? (int)$dbo->loadResult() : 0;

		// update cached var
		$getMinDaysAdvance = $mind;

		if ($no_closing_dates) {
			// do not check the closing dates
			return $mind;
		}

		// check if the property is currently closed
		$cur_closed_dates = self::getClosingDates();
		if (is_array($cur_closed_dates) && count($cur_closed_dates)) {
			$today_midnight = mktime(0, 0, 0);
			$closed_until = null;
			foreach ($cur_closed_dates as $kcd => $vcd) {
				if ($today_midnight >= $vcd['from'] && $today_midnight <= $vcd['to']) {
					// closing period found
					$closed_until = $vcd['to'];
					break;
				}
			}
			if ($closed_until !== null) {
				// count the number of days until property is closed
				$mind = 0;
				$today_info = getdate($today_midnight);
				while ($today_info[0] <= $closed_until) {
					$mind++;
					$today_info = getdate(mktime(0, 0, 0, $today_info['mon'], ($today_info['mday'] + 1), $today_info['year']));
				}
			}
		}

		// update cached var
		$getMinDaysAdvance = $mind;

		return $mind;
	}
	
	public static function getDefaultNightsCalendar($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='autodefcalnights';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (int)$s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbdefaultNightsCalendar', '');
			if (!empty($sval)) {
				return (int)$sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='autodefcalnights';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vbdefaultNightsCalendar', $s[0]['setting']);
				return (int)$s[0]['setting'];
			}
		}
	}
	
	public static function getSearchNumRooms($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numrooms';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (int)$s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbsearchNumRooms', '');
		if (!empty($sval)) {
			return (int)$sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numrooms';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbsearchNumRooms', $s[0]['setting']);
		return (int)$s[0]['setting'];
	}
	
	public static function getSearchNumAdults($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numadults';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbsearchNumAdults', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numadults';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbsearchNumAdults', $s[0]['setting']);
		return $s[0]['setting'];
	}
	
	public static function getSearchNumChildren($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numchildren';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbsearchNumChildren', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numchildren';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbsearchNumChildren', $s[0]['setting']);
		return $s[0]['setting'];
	}
	
	public static function getSmartSearchType($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smartsearch';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbsmartSearchType', '');
		if (!empty($sval)) {
			return $sval;
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smartsearch';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		$session->set('vbsmartSearchType', $s[0]['setting']);
		return $s[0]['setting'];
	}

	/**
	 * Returns the maximum advance booking notice (offset) as a period.
	 * It either returns the global configuration setting, or the value
	 * defined at room-level, if any and if requested.
	 * 
	 * @param 	int 	$idroom 	optional room ID for room-level setting.
	 * 
	 * @return 	string 				the maximum booking notice period (i.e. +1Y).
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP) replaced previous argument $skipsession with
	 * 									$idroom to support room-level settings.
	 */
	public static function getMaxDateFuture($idroom = 0)
	{
		static $max_adv_book_pool = [];

		$pool_id = !empty($idroom) && is_numeric($idroom) ? (int)$idroom : 'global';

		if (isset($max_adv_book_pool[$pool_id])) {
			return $max_adv_book_pool[$pool_id];
		}

		$config = VBOFactory::getConfig();

		if ($pool_id === 'global') {
			$max_adv_book_pool[$pool_id] = $config->get('maxdate', '');
		} else {
			$room_level_max_notice = $config->get("room_{$pool_id}_max_adv_notice");
			if (!empty($room_level_max_notice)) {
				// setting is defined at room-level
				$max_adv_book_pool[$pool_id] = $room_level_max_notice;
			} else {
				// recurse for global setting
				$max_adv_book_pool[$pool_id] = self::getMaxDateFuture();
			}
		}

		return $max_adv_book_pool[$pool_id];
	}

	/**
	 * Validates the maximum advance booking notice against the requested check-in
	 * timestamp. The method supports the validation at both room and property levels.
	 * 
	 * @param 	int 	$checkints 	the check-in date timestamp.
	 * @param 	int 	$idroom 	the optional room ID for room-level validation.
	 * 
	 * @return 	string 				error date string in case of validation failure.
	 * 
	 * @since 	1.16.3 (J) - 1.6.3 (WP) introduced $idroom argument for room-level.
	 */
	public static function validateMaxDateBookings($checkints, $idroom = 0)
	{
		$datelim = self::getMaxDateFuture($idroom);
		$datelim = empty($datelim) ? '+2y' : $datelim;
		$numlim = (int)substr($datelim, 1, (strlen($datelim) - 2));
		$quantlim = substr($datelim, -1, 1);

		$now = getdate();
		if ($quantlim == 'w') {
			$until_ts = strtotime("+$numlim weeks") + 86399;
		} else {
			$use_mon  = $quantlim == 'm' ? ($now['mon'] + $numlim) : $now['mon'];
			$use_day  = $quantlim == 'd' ? ($now['mday'] + $numlim) : $now['mday'];
			$use_year = $quantlim == 'y' ? ($now['year'] + $numlim) : $now['year'];
			$until_ts = mktime(23, 59, 59, $use_mon, $use_day, $use_year);
		}

		if ($until_ts > $now[0] && $checkints > $until_ts) {
			$vbo_df = self::getDateFormat();
			$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');

			// error, return the maximum date in the future allowed
			return date($df, $until_ts);
		}

		// validation passed
		return '';
	}

	public static function validateMinDaysAdvance($checkints) {
		$mindadv = self::getMinDaysAdvance(true);
		if ($mindadv > 0) {
			$tsinfo = getdate($checkints);
			$limit_ts = mktime($tsinfo['hours'], $tsinfo['minutes'], $tsinfo['seconds'], date('n'), ((int)date('j') + $mindadv), date('Y'));
			if ($checkints < $limit_ts) {
				return $mindadv;
			}
		}

		return '';
	}
	
	/**
	 * The only supported calendar type has been changed to jQuery UI.
	 * 
	 * @since 	1.13
	 */
	public static function calendarType($skipsession = false)
	{
		return 'jqueryui';
	}

	public static function getSiteLogo()
	{
		static $siteLogo = null;

		if ($siteLogo !== null) {
			return $siteLogo;
		}

		$siteLogo = VBOFactory::getConfig()->get('sitelogo', '');

		return $siteLogo;
	}

	public static function getBackendLogo()
	{
		static $backLogo = null;

		if ($backLogo !== null) {
			return $backLogo;
		}

		$backLogo = VBOFactory::getConfig()->get('backlogo', '');

		return $backLogo;
	}

	public static function numCalendars()
	{
		static $numCalendars = null;

		if ($numCalendars !== null) {
			return $numCalendars;
		}

		$numCalendars = VBOFactory::getConfig()->getUInt('numcalendars', 0);

		return $numCalendars;
	}

	public static function getFirstWeekDay()
	{
		static $firstWeekDay = null;

		if ($firstWeekDay !== null) {
			return $firstWeekDay;
		}

		$firstWeekDay = VBOFactory::getConfig()->getUInt('firstwday', 0);

		return $firstWeekDay;
	}
	
	public static function showPartlyReserved()
	{
		return VBOFactory::getConfig()->getBool('showpartlyreserved', false);
	}

	public static function showStatusCheckinoutOnly()
	{
		return VBOFactory::getConfig()->getBool('showcheckinoutonly', false);
	}

	public static function getDisclaimer($vbo_tn = null)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='disclaimer';";
		$dbo->setQuery($q);
		$ft = $dbo->loadAssocList();
		if (!$ft) {
			return '';
		}

		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');

		return $ft[0]['setting'];
	}

	public static function showFooter()
	{
		return VBOFactory::getConfig()->getBool('showfooter', false);
	}

	public static function getPriceName($idp, $vbo_tn = null)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`,`name` FROM `#__vikbooking_prices` WHERE `id`=" . (int)$idp;
		$dbo->setQuery($q, 0, 1);
		$n = $dbo->loadAssocList();
		if ($n) {
			if (is_object($vbo_tn)) {
				$vbo_tn->translateContents($n, '#__vikbooking_prices');
			}
			return $n[0]['name'];
		}

		return "";
	}

	public static function getPriceAttr($idp, $vbo_tn = null)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`,`attr` FROM `#__vikbooking_prices` WHERE `id`=" . (int)$idp;
		$dbo->setQuery($q, 0, 1);
		$n = $dbo->loadAssocList();
		if ($n) {
			if (is_object($vbo_tn)) {
				$vbo_tn->translateContents($n, '#__vikbooking_prices');
			}
			return $n[0]['attr'];
		}

		return "";
	}

	/**
	 * Fetches the requested rate plan information.
	 * 
	 * @param 	int 	$idp 		the rate plan ID.
	 * @param 	object 	$vbo_tn 	the Vik Booking translator object.
	 * 
	 * @return 	array 	empty array or associative record.
	 */
	public static function getPriceInfo($idp, $vbo_tn = null)
	{
		// cache values
		static $priceInfos = [];

		$idp = (int)$idp;

		if ($priceInfos && isset($priceInfos[$idp])) {
			return $priceInfos[$idp];
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_prices` WHERE `id`={$idp}";
		$dbo->setQuery($q, 0, 1);
		$rplan = $dbo->loadAssocList();
		if (!$rplan) {
			return [];
		}

		if (is_object($vbo_tn)) {
			$vbo_tn->translateContents($rplan, '#__vikbooking_prices');
		}

		// set cached value
		$priceInfos[$idp] = $rplan[0];

		return $rplan[0];
	}

	public static function getAliq($idal)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `aliq` FROM `#__vikbooking_iva` WHERE `id`=" . (int)$idal . ";";
		$dbo->setQuery($q);
		$aliq = $dbo->loadResult();
		if ($aliq) {
			return $aliq;
		}

		return 0;
	}

	public static function getTimeOpenStore($skipsession = false)
	{
		// cache value in static var
		static $getTimeOpenStore = null;

		if ($getTimeOpenStore) {
			return $getTimeOpenStore;
		}
		//

		$dbo = JFactory::getDbo();
		$session = JFactory::getSession();

		if ($skipsession) {
			$setting = VBOFactory::getConfig()->get('timeopenstore', '');
			if (empty($setting) && $setting != "0") {
				return false;
			} else {
				$x = explode("-", $setting);
				if (!empty($x[1]) && $x[1] != "0") {
					$getTimeOpenStore = $x;
					return $x;
				}
			}
		} else {
			$sval = $session->get('vbgetTimeOpenStore', '');
			if (!empty($sval)) {
				return $sval;
			} else {
				$setting = VBOFactory::getConfig()->get('timeopenstore', '');
				if (empty($setting) && $setting != "0") {
					return false;
				} else {
					$x = explode("-", $setting);
					if (!empty($x[1]) && $x[1] != "0") {
						$session->set('vbgetTimeOpenStore', $x);
						$getTimeOpenStore = $x;
						return $x;
					}
				}
			}
		}
		return false;
	}

	public static function getHoursMinutes($secs) {
		if ($secs >= 3600) {
			$op = $secs / 3600;
			$hours = floor($op);
			$less = $hours * 3600;
			$newsec = $secs - $less;
			$optwo = $newsec / 60;
			$minutes = floor($optwo);
		} else {
			$hours = "0";
			$optwo = $secs / 60;
			$minutes = floor($optwo);
		}
		$x[] = $hours;
		$x[] = $minutes;
		return $x;
	}

	public static function getClosingDates()
	{
		// cache value in static var
		static $getClosingDates = null;

		if (is_array($getClosingDates)) {
			return $getClosingDates;
		}

		$dbo = JFactory::getDbo();

		$allcd = [];

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='closingdates';";
		$dbo->setQuery($q);
		$s = $dbo->loadAssocList();
		if ($s && !empty($s[0]['setting'])) {
			$allcd = json_decode($s[0]['setting'], true);
			$allcd = is_array($allcd) ? $allcd : array();
			$base_ts = mktime(0, 0, 0, date("n"), date("j"), date("Y"));
			foreach ($allcd as $k => $v) {
				if ($v['to'] < $base_ts) {
					unset($allcd[$k]);
				}
			}
			$allcd = array_values($allcd);
		}

		$getClosingDates = $allcd;

		return $allcd;
	}

	public static function parseJsClosingDates() {
		$cd = self::getClosingDates();
		if (count($cd) > 0) {
			$cdjs = array();
			foreach ($cd as $k => $v) {
				$cdjs[] = array(date('Y-m-d', $v['from']), date('Y-m-d', $v['to']));
			}
			return $cdjs;
		}
		return array();
	}

	public static function validateClosingDates($checkints, $checkoutts, $df = null)
	{
		$cd = self::getClosingDates();
		if (!count($cd)) {
			return '';
		}
		$df = empty($df) ? 'Y-m-d' : $df;
		$margin_seconds = 22 * 60 * 60;
		foreach ($cd as $k => $v) {
			$inner_closed = ($checkints >= $v['from'] && $checkints <= ($v['to'] + $margin_seconds));
			$outer_closed = ($checkoutts >= $v['from'] && $checkoutts <= ($v['to'] + $margin_seconds));
			$middle_closed = ($checkints <= $v['from'] && $checkoutts >= ($v['to'] + $margin_seconds));
			if ($inner_closed || $outer_closed || $middle_closed) {
				return date($df, $v['from']) . ' - ' . date($df, $v['to']);
			}
		}
		return '';
	}

	/**
	 * Whether the categories dropdown filter menu should be displayed.
	 * 
	 * @param 	boolean 	$skipsession 	[optional] re-read the configuration setting.
	 * 
	 * @return 	boolean
	 */
	public static function showCategoriesFront($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showcategories';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$s = $dbo->loadAssocList();
				return (intval($s[0]['setting']) == 1);
			}
			return false;
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbshowCategoriesFront', '');
		if (strlen($sval)) {
			return (intval($sval) == 1 ? true : false);
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showcategories';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			$session->set('vbshowCategoriesFront', $s[0]['setting']);
			return (intval($s[0]['setting']) == 1);
		}
		return false;
	}
	
	/**
	 * Whether the number of children dropdown menu should be displayed.
	 * Defaults to skip the session values and to re-read the configuration setting.
	 * 
	 * @param 	boolean 	$skipsession 	[optional] re-read the configuration setting.
	 * 
	 * @return 	boolean
	 */
	public static function showChildrenFront($skipsession = true)
	{
		$dbo = JFactory::getDbo();
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showchildren';";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$s = $dbo->loadAssocList();
				return (intval($s[0]['setting']) == 1);
			}
			return false;
		}
		$session = JFactory::getSession();
		$sval = $session->get('vbshowChildrenFront', '');
		if (strlen($sval)) {
			return (intval($sval) == 1 ? true : false);
		}
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='showchildren';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			$session->set('vbshowChildrenFront', $s[0]['setting']);
			return (intval($s[0]['setting']) == 1);
		}
		return false;
	}

	public static function allowBooking() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='allowbooking';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			return (intval($s[0]['setting']) == 1 ? true : false);
		} else {
			return false;
		}
	}

	public static function getDisabledBookingMsg($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='disabledbookingmsg';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($s, '#__vikbooking_texts');
		return $s[0]['setting'];
	}

	/**
	 * Returns the text for the guests allowed policy.
	 * 
	 * @param 	mixed 	$vbo_tn 	null or VikBookingTranslator instance to translate the text.
	 * 
	 * @return 	string 				either the raw or the translated policy text.
	 * 
	 * @since 	1.16.5 (WP) - 1.6.5 (WP)
	 */
	public static function getGuestsAllowedPolicy($vbo_tn = null)
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select([$dbo->qn('id'), $dbo->qn('setting')])
			->from($dbo->qn('#__vikbooking_texts'))
			->where($dbo->qn('param') . ' = ' . $dbo->q('guests_allowed_policy'));

		$dbo->setQuery($q);
		$text_record = $dbo->loadAssocList();

		if (!$text_record) {
			// missing record, insert it with empty values
			if (self::isAdmin()) {
				$record = new stdClass;
				$record->param 	 = 'guests_allowed_policy';
				$record->exp 	 = JText::translate('VBO_GUESTS_POLICY');
				$record->setting = '';
				$dbo->insertObject('#__vikbooking_texts', $record, 'id');
			}

			return '';
		}

		if (is_object($vbo_tn)) {
			// translate the record only if requested
			$vbo_tn->translateContents($text_record, '#__vikbooking_texts');
		}

		return $text_record[0]['setting'];
	}

	public static function getDateFormat($skipsession = true)
	{
		// cache value in static var
		static $getDateFormat = null;

		if ($getDateFormat) {
			return $getDateFormat;
		}

		$dbo = JFactory::getDbo();

		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat'";
			$dbo->setQuery($q, 0, 1);
			$getDateFormat = $dbo->loadResult();

			return $getDateFormat;
		}

		$session = JFactory::getSession();
		$sval = $session->get('vbgetDateFormat', '');
		if (!empty($sval)) {
			return $sval;
		}

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat'";
		$dbo->setQuery($q, 0, 1);
		$getDateFormat = $dbo->loadResult();

		$session->set('vbgetDateFormat', $getDateFormat);

		return $getDateFormat;
	}

	public static function getDateSeparator($skipsession = true)
	{
		// cache value in static var
		static $getDateSeparator = null;

		if ($getDateSeparator) {
			return $getDateSeparator;
		}

		$dbo = JFactory::getDbo();

		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='datesep'";
			$dbo->setQuery($q, 0, 1);
			$val = $dbo->loadResult();

			$getDateSeparator = empty($val) ? "/" : $val;

			return $getDateSeparator;
		}

		$session = JFactory::getSession();
		$sval = $session->get('vbgetDateSep', '');
		if (!empty($sval)) {
			return $sval;
		}

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='datesep'";
		$dbo->setQuery($q, 0, 1);
		$val = $dbo->loadResult();

		$getDateSeparator = empty($val) ? "/" : $val;

		$session->set('vbgetDateSep', $getDateSeparator);

		return $getDateSeparator;
	}

	public static function getHoursMoreRb($skipsession = false)
	{
		// cache value in static var
		static $getHoursMoreRb = null;

		if ($getHoursMoreRb !== null) {
			return $getHoursMoreRb;
		}

		$dbo = JFactory::getDbo();

		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='hoursmorebookingback'";
			$dbo->setQuery($q, 0, 1);
			$getHoursMoreRb = (int)$dbo->loadResult();

			return $getHoursMoreRb;
		}

		$session = JFactory::getSession();
		$sval = $session->get('getHoursMoreRb', '');
		if (strlen($sval)) {
			return $sval;
		}

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='hoursmorebookingback'";
		$dbo->setQuery($q, 0, 1);
		$getHoursMoreRb = (int)$dbo->loadResult();

		$session->set('getHoursMoreRb', $getHoursMoreRb);

		return $getHoursMoreRb;
	}

	public static function getHoursRoomAvail()
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='hoursmoreroomavail'";
		$dbo->setQuery($q, 0, 1);

		return (int)$dbo->loadResult();
	}

	public static function getFrontTitle($vbo_tn = null) {
		// cache value in static var
		static $getFrontTitle = null;

		if ($getFrontTitle) {
			return $getFrontTitle;
		}
		//

		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='fronttitle';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		
		$getFrontTitle = $ft[0]['setting'];

		return $ft[0]['setting'];
	}

	public static function getFrontTitleTag()
	{
		return VBOFactory::getConfig()->get('fronttitletag', '');
	}

	public static function getFrontTitleTagClass()
	{
		return VBOFactory::getConfig()->get('fronttitletagclass', '');
	}

	public static function getCurrencyName()
	{
		static $currencyName = null;

		if ($currencyName !== null) {
			return $currencyName;
		}

		$currencyName = VBOFactory::getConfig()->get('currencyname', '');

		return $currencyName;
	}

	public static function getCurrencySymb($skipsession = false) {
		// cache value in static var
		static $getCurrencySymb = null;

		if ($getCurrencySymb) {
			return $getCurrencySymb;
		}

		$dbo = JFactory::getDbo();

		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencysymb'";
			$dbo->setQuery($q, 0, 1);
			$csymb = $dbo->loadResult();

			$getCurrencySymb = $csymb;

			return $csymb;
		}

		$session = JFactory::getSession();
		$sval = $session->get('vbgetCurrencySymb', '');
		if (!empty($sval)) {
			return $sval;
		}

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencysymb'";
		$dbo->setQuery($q, 0, 1);
		$csymb = $dbo->loadResult();

		// update session value, cache it and return it
		$session->set('vbgetCurrencySymb', $csymb);
		$getCurrencySymb = $csymb;

		return $csymb;
	}
	
	public static function getNumberFormatData($skipsession = false) {
		// cache value in static var
		static $getNumberFormatData = null;

		if ($getNumberFormatData) {
			return $getNumberFormatData;
		}
		//

		$dbo = JFactory::getDbo();
		
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numberformat'";
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			$numfdata = $dbo->loadResult();

			// cache value and return it
			$getNumberFormatData = $numfdata;

			return $numfdata;
		}

		$session = JFactory::getSession();
		$sval = $session->get('getNumberFormatData', '');
		if (!empty($sval)) {
			return $sval;
		}

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='numberformat'";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		$numfdata = $dbo->loadResult();

		// update session value, cache it and return it
		$session->set('getNumberFormatData', $numfdata);
		$getNumberFormatData = $numfdata;

		return $numfdata;
	}

	/**
	 * It is possible to hide the decimals if they are like "N.00".
	 * 
	 * @return 	int 	0 if disabled, 1 if enabled (default).
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function hideEmptyDecimals()
	{
		// cache value in static var
		static $hideEmptyDecimals = null;

		if ($hideEmptyDecimals !== null) {
			return $hideEmptyDecimals;
		}
		//

		$dbo = JFactory::getDbo();

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='noemptydecimals';";
		$dbo->setQuery($q);
		$hideEmptyDecimals = $dbo->loadResult();
		if ($hideEmptyDecimals !== null) {
			$hideEmptyDecimals = (int)$hideEmptyDecimals;
			return $hideEmptyDecimals;
		}

		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('noemptydecimals', '1');";
		$dbo->setQuery($q);
		$dbo->execute();

		$hideEmptyDecimals = 1;
		
		return $hideEmptyDecimals;
	}

	public static function numberFormat($num, $skipsession = false)
	{
		$formatvals = self::getNumberFormatData($skipsession);
		$formatparts = explode(':', $formatvals);

		if ((int)$formatparts[0] > 0 && (floatval($num) - intval($num)) == 0 && self::hideEmptyDecimals()) {
			// number has got no decimals
			$formatparts[0] = 0;
		}

		return number_format((float)$num, (int)$formatparts[0], $formatparts[1], $formatparts[2]);
	}

	/**
	 * Given a date timestamp, returns the formatted date.
	 * 
	 * @param 	int|string 	$ts 	the date representation in Unix timestamp.
	 * @param 	bool 		$wtime 	if true, a formatted date-time will be returned.
	 * 
	 * @return 	string 				the formatted date according to settings.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public static function formatDateTs($ts, $wtime = false)
	{
		$nowdf = self::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator();

		$time_f = $wtime ? ' H:i' : '';

		return date(str_replace("/", $datesep, $df) . $time_f, $ts);
	}

	/**
	 * Given a country name, 3-char or 2-char code, this method attempts
	 * to guess the best language to assign to the booking according to
	 * what languages are installed on the website. This way, cron jobs
	 * and any other email notification will be correctly and automatically
	 * sent to the guest in the proper language without any manual action.
	 * To comply with VCM (>= 1.8.3), also VBO supports this feature.
	 * 
	 * @param 	string 	$country 	the country name, 3-char or 2-char code.
	 * @param 	string 	$locale 	optional locale lang tag of the guest.
	 * 
	 * @return 	mixed 				the best lang-tag string to use or null.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public static function guessBookingLangFromCountry($country, $locale = null)
	{
		if (empty($country)) {
			return null;
		}

		// get all the available languages
		$known_langs = self::getVboApplication()->getKnownLanguages();
		if (!is_array($known_langs) || !count($known_langs)) {
			return null;
		}

		// check if the booking included a supported "locale" for the guest
		if (!empty($locale)) {
			// make the locale compatible with the language tags format
			$locale = str_replace('_', '-', $locale);
			foreach ($known_langs as $ltag => $ldet) {
				if (stripos($ltag, $locale) !== false || stripos($locale, $ltag) !== false) {
					// we support this language tag, so we just use it
					return $ltag;
				}
			}
		}

		// build similarities with country-languages
		$similarities = array(
			'AU' => 'en',
			'GB' => 'en',
			'IE' => 'en',
			'NZ' => 'en',
			'US' => 'en',
			'CA' => array(
				'en',
				'fr',
			),
			'CL' => 'es',
			'AR' => 'es',
			'PE' => 'es',
			'MX' => 'es',
			'CR' => 'es',
			'CO' => 'es',
			'EC' => 'es',
			'BO' => 'es',
			'CU' => 'es',
			'VE' => 'es',
			'BE' => 'fr',
			'LU' => 'fr',
			'CH' => array(
				'de',
				'it',
				'fr',
			),
			'AT' => 'de',
			'GR' => 'el',
			'GL' => 'dk',
		);

		// fetch values from db
		$dbo = JFactory::getDbo();
		$q = "SELECT `country_name`, `country_3_code`, `country_2_code` FROM `#__vikbooking_countries` WHERE `country_name`=" . $dbo->quote($country) . " OR `country_3_code`=" . $dbo->quote($country) . " OR `country_2_code`=" . $dbo->quote($country);
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return null;
		}
		$country_record = $dbo->loadAssoc();

		// assign country name/code versions
		$country_name  = $country_record['country_name'];
		$country_3char = strtoupper($country_record['country_3_code']);
		$country_2char = strtoupper($country_record['country_2_code']);

		// build an associative array of language tags and related match-score
		$langtags_score = array();
		foreach ($known_langs as $ltag => $ldet) {
			// default language tag score is 0 for no matches
			$langtags_score[$ltag] = 0;
			// get language and country codes
			$lang_country_codes = explode('-', str_replace('_', '-', strtoupper($ltag)));
			
			// check matches with the installed language details
			if ($lang_country_codes[0] == $country_2char || $lang_country_codes[0] == $country_3char) {
				// increase language tag score
				$langtags_score[$ltag]++;
			}
			if (!empty($lang_country_codes[1]) && ($lang_country_codes[1] == $country_2char || $lang_country_codes[1] == $country_3char)) {
				// increase language tag score
				$langtags_score[$ltag]++;
			}
			if (!empty($ldet['locale'])) {
				// sanitize locale for matching the 2-char code safely
				$ldet['locale'] = str_replace(array('standard', 'euro', 'iso', 'utf'), '', strtolower($ldet['locale']));
				if (stripos($ldet['locale'], $country_2char) !== false || stripos($ldet['locale'], $country_name) !== false) {
					// increase language tag score
					$langtags_score[$ltag]++;
				}
			}
			if (!empty($ldet['name']) && stripos($ldet['name'], $country_name) !== false) {
				// increase language tag score
				$langtags_score[$ltag]++;
			}
			if (!empty($ldet['nativeName']) && stripos($ldet['nativeName'], $country_name) !== false) {
				// increase language tag score
				$langtags_score[$ltag]++;
			}

			// check language similarities between countries
			if (isset($similarities[$country_2char])) {
				$spoken_tags = !is_array($similarities[$country_2char]) ? array($similarities[$country_2char]) : $similarities[$country_2char];
				// check if language tag(s) is available for this spoken language
				foreach ($spoken_tags as $spoken_tag) {
					if ($lang_country_codes[0] == strtoupper($spoken_tag)) {
						// increase language tag score
						$langtags_score[$ltag]++;
					}
				}
			}
		}

		// make sure at least one language tag has got some points
		if (max($langtags_score) === 0) {
			// no languages installed to honor this country
			return null;
		}

		// sort language tag scores
		arsort($langtags_score);

		// reset array pointer to the first (highest) element
		reset($langtags_score);

		// return the language tag with the highest score
		return key($langtags_score);
	}

	public static function getCurrencyCodePp() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='currencycodepp';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		return $ft[0]['setting'];
	}

	public static function getIntroMain($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='intromain';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		return $ft[0]['setting'];
	}

	public static function getClosingMain($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='closingmain';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		return $ft[0]['setting'];
	}

	public static function getFullFrontTitle($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='fronttitle';";
		$dbo->setQuery($q);
		$dbo->execute();
		$ft = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='fronttitletag';";
		$dbo->setQuery($q);
		$dbo->execute();
		$fttag = $dbo->loadAssocList();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='fronttitletagclass';";
		$dbo->setQuery($q);
		$dbo->execute();
		$fttagclass = $dbo->loadAssocList();
		if (empty($ft[0]['setting'])) {
			return "";
		} else {
			if (empty($fttag[0]['setting'])) {
				return $ft[0]['setting'] . "<br/>\n";
			} else {
				$tag = str_replace("<", "", $fttag[0]['setting']);
				$tag = str_replace(">", "", $tag);
				$tag = str_replace("/", "", $tag);
				$tag = trim($tag);
				return "<" . $tag . "" . (!empty($fttagclass) ? " class=\"" . $fttagclass[0]['setting'] . "\"" : "") . ">" . $ft[0]['setting'] . "</" . $tag . ">";
			}
		}
	}

	public static function dateIsValid($date) {
		$df = self::getDateFormat();
		$datesep = self::getDateSeparator();
		if (strlen($date) != 10) {
			return false;
		}
		$cur_dsep = "/";
		if ($datesep != $cur_dsep && strpos($date, $datesep) !== false) {
			$cur_dsep = $datesep;
		}
		$x = explode($cur_dsep, $date);
		if ($df == "%d/%m/%Y") {
			if (strlen($x[0]) != 2 || $x[0] > 31 || strlen($x[1]) != 2 || $x[1] > 12 || strlen($x[2]) != 4) {
				return false;
			}
		} elseif ($df == "%m/%d/%Y") {
			if (strlen($x[1]) != 2 || $x[1] > 31 || strlen($x[0]) != 2 || $x[0] > 12 || strlen($x[2]) != 4) {
				return false;
			}
		} else {
			if (strlen($x[2]) != 2 || $x[2] > 31 || strlen($x[1]) != 2 || $x[1] > 12 || strlen($x[0]) != 4) {
				return false;
			}
		}
		return true;
	}

	public static function sayDateFormat() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='dateformat';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if ($s[0]['setting'] == "%d/%m/%Y") {
			return JText::translate('VBCONFIGONETWELVE');
		} elseif ($s[0]['setting'] == "%m/%d/%Y") {
			return JText::translate('VBCONFIGONEMDY');
		} else {
			return JText::translate('VBCONFIGONETENTHREE');
		}
	}

	/**
	 * Calculates the Unix timestamp from the given date and
	 * time. Avoids DST issues thanks to mktime. With prior releases,
	 * DST issues may occur due to the sum of seconds.
	 * 
	 * @param 	string 	$date 	the date string formatted with the current settings
	 * @param 	int 	$h 		hours from 0 to 23 for check-in/check-out
	 * @param 	int 	$m 		minutes from 0 to 59 for check-in/check-out
	 * @param 	int 	$s 		seconds from 0 to 59 for check-in/check-out
	 * 
	 * @return 	int 	the Unix timestamp of the date
	 * 
	 * @since 	1.0.14
	 * @since 	1.15.0 (J) - 1.5.0 (WP)  added capability to always support Y-m-d format for VCM.
	 * 									 signature modified for $h and $m that now take 0 as default value.
	 */
	public static function getDateTimestamp($date, $h = 0, $m = 0, $s = 0) {
		$df = self::getDateFormat();
		$datesep = self::getDateSeparator();
		$cur_dsep = "/";
		if ($datesep != $cur_dsep && strpos($date, $datesep) !== false) {
			$cur_dsep = $datesep;
		}
		if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
			// date is in Y-m-d format (with no time)
			$cur_dsep = '-';
			$df = "%Y/%m/%d";
		}
		$x = explode($cur_dsep, $date);
		if (!(count($x) > 2)) {
			return 0;
		}
		if ($df == "%d/%m/%Y") {
			$month = (int)$x[1];
			$mday = (int)$x[0];
			$year = (int)$x[2];
		} elseif ($df == "%m/%d/%Y") {
			$month = (int)$x[0];
			$mday = (int)$x[1];
			$year = (int)$x[2];
		} else {
			$month = (int)$x[1];
			$mday = (int)$x[2];
			$year = (int)$x[0];
		}
		$h = empty($h) ? 0 : (int)$h;
		$m = empty($m) ? 0 : (int)$m;
		$s = $s > 0 && $s <= 59 ? $s : 0;

		return mktime($h, $m, $s, $month, $mday, $year);
	}

	public static function ivaInclusa($skipsession = false) {
		// cache value in static var
		static $getTaxIncluded = null;

		if ($getTaxIncluded !== null) {
			return (bool)$getTaxIncluded;
		}

		$dbo = JFactory::getDbo();
		
		if ($skipsession) {
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='ivainclusa'";
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			$vat_incl_data = $dbo->getNumRows() ? (int)$dbo->loadResult() : 1;

			// cache value and return it
			$getTaxIncluded = $vat_incl_data;

			return (bool)$vat_incl_data;
		}

		$session = JFactory::getSession();
		$sval = $session->get('getTaxIncluded', '');
		if (strlen($sval)) {
			return (bool)$sval;
		}

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='ivainclusa'";
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		$vat_incl_data = $dbo->getNumRows() ? (int)$dbo->loadResult() : 1;

		// update session value, cache it and return it
		$session->set('getTaxIncluded', $vat_incl_data);
		$getTaxIncluded = $vat_incl_data;

		return (bool)$vat_incl_data;
	}
	
	public static function showTaxOnSummaryOnly($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='taxsummary';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return (intval($s[0]['setting']) == 1 ? true : false);
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('vbshowTaxOnSummaryOnly', '');
			if (strlen($sval) > 0) {
				return (intval($sval) == 1 ? true : false);
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='taxsummary';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('vbshowTaxOnSummaryOnly', $s[0]['setting']);
				return (intval($s[0]['setting']) == 1 ? true : false);
			}
		}
	}

	public static function tokenForm() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='tokenform';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getPaypalAcc() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='ccpaypal';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}

	public static function getAccPerCent() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='payaccpercent';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return $s[0]['setting'];
	}
	
	public static function getTypeDeposit($skipsession = false) {
		if ($skipsession) {
			$dbo = JFactory::getDbo();
			$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='typedeposit';";
			$dbo->setQuery($q);
			$dbo->execute();
			$s = $dbo->loadAssocList();
			return $s[0]['setting'];
		} else {
			$session = JFactory::getSession();
			$sval = $session->get('getTypeDeposit', '');
			if (strlen($sval) > 0) {
				return $sval;
			} else {
				$dbo = JFactory::getDbo();
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='typedeposit';";
				$dbo->setQuery($q);
				$dbo->execute();
				$s = $dbo->loadAssocList();
				$session->set('getTypeDeposit', $s[0]['setting']);
				return $s[0]['setting'];
			}
		}
	}

	public static function multiplePayments()
	{
		return VBOFactory::getConfig()->getBool('multipay', false);
	}

	public static function getAdminMail()
	{
		return VBOFactory::getConfig()->getString('adminemail', '');
	}

	public static function getSenderMail()
	{
		$sender = VBOFactory::getConfig()->getString('senderemail', '');

		return empty($sender) ? self::getAdminMail() : $sender;
	}

	public static function getPaymentName($vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='paymentname';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		if (!is_object($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		$vbo_tn->translateContents($s, '#__vikbooking_texts');
		return $s[0]['setting'];
	}

	public static function getTermsConditions($vbo_tn = null) {
		//VBO 1.10
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='termsconds';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadAssocList();
			if (!is_object($vbo_tn)) {
				$vbo_tn = self::getTranslator();
			}
			$vbo_tn->translateContents($s, '#__vikbooking_texts');
		} else {
			//the record has never been saved. Compose it with the default lang definition
			$timeopst = self::getTimeOpenStore(true);
			if (is_array($timeopst)) {
				$openat = self::getHoursMinutes($timeopst[0]);
				$closeat = self::getHoursMinutes($timeopst[1]);
			} else {
				$openat = array(12, 0);
				$closeat = array(10, 0);
			}
			$checkin_str = ($openat[0] < 10 ? '0'.$openat[0] : $openat[0]).':'.($openat[1] < 10 ? '0'.$openat[1] : $openat[1]);
			$checkout_str = ($closeat[0] < 10 ? '0'.$closeat[0] : $closeat[0]).':'.($closeat[1] < 10 ? '0'.$closeat[1] : $closeat[1]);
			$s = array(0 => array('setting' => nl2br(JText::sprintf('VBOTERMSCONDSDEFTEXT', $checkin_str, $checkout_str))));
		}
		
		return $s[0]['setting'];
	}

	public static function getMinutesLock($conv = false)
	{
		static $minutesLock = null;

		if ($minutesLock === null) {
			$minutesLock = VBOFactory::getConfig()->getInt('minuteslock', 0);
		}

		if (!$minutesLock) {
			return 0;
		}

		if ($conv) {
			return (time() + ($minutesLock * 60));
		}

		return $minutesLock;
	}

	/**
	 * Checks if the room units are temporarily locked on the given dates.
	 * 
	 * @param 	int 	$idroom 		the room ID.
	 * @param 	int 	$units 			the total number of room units.
	 * @param 	int 	$first 			the check-in timestamp.
	 * @param 	int 	$second 		the check-out timestamp.
	 * @param 	bool 	$occupied 		if true, the system will also consider the booked units.
	 * @param 	array 	$skip_busy_ids 	optional list of busy record IDs to skip (booking modification).
	 * 
	 * @return 	bool 	true if the room is available, false if fully locked/occupied, hence not bookable.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP) added $occupied argument.
	 * @since 	1.16.3 (J) - 1.6.3 (WP) added $skip_busy_ids argument to support usage for booking modifications.
	 */
	public static function roomNotLocked($idroom, $units, $first, $second, $occupied = false, $skip_busy_ids = [])
	{
		$dbo = JFactory::getDbo();

		if ($units < 1) {
			return false;
		}

		$actnow = time();

		// clean up expired records
		$q = "DELETE FROM `#__vikbooking_tmplock` WHERE `until`<" . $dbo->quote($actnow) . ";";
		$dbo->setQuery($q);
		$dbo->execute();

		$secdiff = $second - $first;
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
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}
		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		$tmplock_map = [];

		// check temporarily locked records
		$check = "SELECT `id`,`checkin`,`realback` FROM `#__vikbooking_tmplock` WHERE `idroom`=" . $dbo->quote($idroom) . " AND `until`>=" . $dbo->quote($actnow) . ";";
		$dbo->setQuery($check);
		$busy = $dbo->loadAssocList();
		if ($busy) {
			foreach ($groupdays as $kg => $gday) {
				$bfound = 0;
				foreach ($busy as $bu) {
					if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
						$bfound++;
					}
				}
				$tmplock_map[$kg] = $bfound;
				if ($bfound >= $units) {
					return false;
				}
			}
		}

		if ($occupied) {
			// check also the busy records and some the occupied units to the temporarily locked ones
			$check = "SELECT `id`,`checkin`,`realback` FROM `#__vikbooking_busy` WHERE `idroom`=" . $dbo->quote($idroom) . " AND `realback`>=" . $first . ";";
			$dbo->setQuery($check);
			$busy = $dbo->loadAssocList();
			if (!$busy) {
				return true;
			}

			foreach ($groupdays as $kg => $gday) {
				$bfound = 0;
				foreach ($busy as $bu) {
					if (in_array($bu['id'], $skip_busy_ids)) {
						// Booking modification
						continue;
					}
					if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
						$bfound++;
					}
				}
				// sum units occupied to the units temporarily locked
				$bfound += isset($tmplock_map[$kg]) ? (int)$tmplock_map[$kg] : 0;
				if ($bfound >= $units) {
					return false;
				}
			}
		}

		return true;
	}

	public static function getGroupDays($first, $second, $daysdiff) {
		$ret = array();
		$ret[] = $first;
		if ($daysdiff > 1) {
			$start = getdate($first);
			$end = getdate($second);
			$endcheck = mktime(0, 0, 0, $end['mon'], $end['mday'], $end['year']);
			for($i = 1; $i < $daysdiff; $i++) {
				$checkday = $start['mday'] + $i;
				$dayts = mktime(0, 0, 0, $start['mon'], $checkday, $start['year']);
				if ($dayts != $endcheck) {
					$ret[] = $dayts;
				}
			}
		}
		$ret[] = $second;
		return $ret;
	}

	/**
	 * Counts the hours of difference between the current
	 * server time and the selected check-in date and time.
	 *
	 * @param 	int 	$checkin_ts
	 * @param 	[int] 	$now_ts
	 *
	 * @return 	int
	 */
	public static function countHoursToArrival($checkin_ts, $now_ts = '') {
		$hoursdiff = 0;

		if (empty($now_ts)) {
			$now_ts = time();
		}

		if ($now_ts >= $checkin_ts) {
			return $hoursdiff;
		}

		$hoursdiff = floor(($checkin_ts - $now_ts) / 3600);

		return $hoursdiff;
	}

	/**
	 * Loads all the occupied records for the given rooms and offset.
	 * 
	 * @param 	array 	$roomids 	the optional list of room IDs to filter.
	 * @param 	int 	$from_ts 	the optional base date to use.
	 * @param 	int 	$max_ts 	the optional max date to use.
	 * @param 	bool 	$closures 	whether to ignore, include or exclude closures.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) changed arguments signature with 3rd arg.
	 * @since 	1.15.2 (J) - 1.5.5 (WP) changed arguments signature with 4th arg.
	 */
	public static function loadBusyRecords($roomids = array(), $from_ts = 0, $max_ts = 0, $closures = null)
	{
		$base_ts = empty($from_ts) ? time() : $from_ts;
		$busy 	 = [];
		$clauses = [];

		if (is_array($roomids) && count($roomids)) {
			// filter busy records only by the requested room ids
			$roomids = array_map(function($rid) {
				return (int)$rid;
			}, $roomids);
			$clauses[] = "`b`.`idroom` IN (" . implode(', ', $roomids) . ")";
		}

		// exclude past reservations
		$clauses[] = "`b`.`checkout` >= {$base_ts}";

		if (!empty($max_ts)) {
			// exclude future reservations (recommended for large datasets)
			$clauses[] = "`b`.`checkin` <= {$max_ts}";
		}

		if (is_bool($closures)) {
			// include or exclude closures
			if ($closures) {
				$clauses[] = "`o`.`closure` = 1";
			} else {
				$clauses[] = "`o`.`closure` != 1";
			}
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `b`.*, `ob`.`idorder`, `o`.`closure` FROM `#__vikbooking_busy` AS `b` 
			LEFT JOIN `#__vikbooking_ordersbusy` AS `ob` ON `ob`.`idbusy`=`b`.`id` 
			LEFT JOIN `#__vikbooking_orders` AS `o` ON `o`.`id`=`ob`.`idorder` 
			WHERE " . implode(' AND ', $clauses) . ";";
		$dbo->setQuery($q);
		$allbusy = $dbo->loadAssocList();
		if ($allbusy) {
			foreach ($allbusy as $br) {
				if (!isset($busy[$br['idroom']])) {
					$busy[$br['idroom']] = [];
				}
				$busy[$br['idroom']][] = $br;
			}
		}

		return $busy;
	}

	/**
	 * Loads all the busy records by excluding the closures.
	 * Built for the page dashboard to calculate the actual
	 * rooms occupancy by excluding the rooms closed.
	 * 
	 * @param 	array 	$roomids 	the optional list of room IDs to filter.
	 * @param 	int 	$from_ts 	the optional base date to use.
	 * @param 	int 	$max_ts 	the optional max date to use.
	 * 
	 * @return 	array 				the list of busy records.
	 * 
	 * @since 	1.12
	 * @since 	1.15.2 (J) - 1.5.5 (WP) changed arguments signature with 3rd arg.
	 */
	public static function loadBusyRecordsUnclosed($roomids = array(), $from_ts = 0, $max_ts = 0)
	{
		if (!is_array($roomids) || !count($roomids)) {
			return [];
		}

		return self::loadBusyRecords($roomids, $from_ts, $max_ts, false);
	}

	public static function loadBookingBusyIds($idorder) {
		$busy = array();
		if (empty($idorder)) {
			return $busy;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_ordersbusy` WHERE `idorder`=".(int)$idorder.";";
		$dbo->setQuery($q);
		$allbusy = $dbo->loadAssocList();
		if ($allbusy) {
			foreach ($allbusy as $b) {
				array_push($busy, $b['idbusy']);
			}
		}
		return $busy;
	}

	public static function loadLockedRecords($roomids, $ts = 0) {
		$dbo = JFactory::getDbo();
		$actnow = empty($ts) ? time() : $ts;
		$locked = array();
		$q = "DELETE FROM `#__vikbooking_tmplock` WHERE `until`<" . $actnow . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!is_array($roomids) || !(count($roomids) > 0)) {
			return $locked;
		}
		$check = "SELECT `id`,`idroom`,`checkin`,`realback` FROM `#__vikbooking_tmplock` WHERE `idroom` IN (".implode(', ', $roomids).") AND `until` > ".$actnow.";";
		$dbo->setQuery($check);
		$all_locked = $dbo->loadAssocList();
		if ($all_locked) {
			foreach ($all_locked as $kb => $br) {
				$locked[$br['idroom']][$kb] = $br;
			}
		}
		return $locked;
	}

	public static function getRoomBookingsFromBusyIds($idroom, $arr_bids) {
		$bookings = [];
		if (empty($idroom) || !is_array($arr_bids) || !(count($arr_bids) > 0)) {
			return $bookings;
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `ob`.`idorder`,`ob`.`idbusy` FROM `#__vikbooking_ordersbusy` AS `ob` WHERE `ob`.`idbusy` IN (".implode(',', $arr_bids).") GROUP BY `ob`.`idorder`,`ob`.`idbusy`;";
		$dbo->setQuery($q);
		$all_booking_ids = $dbo->loadAssocList();
		if ($all_booking_ids) {
			$oids = array();
			foreach ($all_booking_ids as $bid) {
				$oids[] = $bid['idorder'];
			}
			$q = "SELECT `or`.`idorder`,CONCAT_WS(' ',`or`.`t_first_name`,`or`.`t_last_name`) AS `nominative`,`or`.`roomindex`,`o`.`status`,`o`.`days`,`o`.`checkout`,`o`.`custdata`,`o`.`country`,`o`.`closure`,`o`.`checked` ".
				"FROM `#__vikbooking_ordersrooms` AS `or` ".
				"LEFT JOIN `#__vikbooking_orders` `o` ON `o`.`id`=`or`.`idorder` ".
				"WHERE `or`.`idorder` IN (".implode(',', $oids).") AND `or`.`idroom`=".(int)$idroom." AND `o`.`status`='confirmed' ".
				"ORDER BY `o`.`checkout` ASC;";
			$dbo->setQuery($q);
			$bookings = $dbo->loadAssocList();
			if (!$bookings) {
				$bookings = [];
			}
		}
		return $bookings;
	}

	public static function roomBookable($idroom, $units, $first, $second, $skip_busy_ids = [])
	{
		$dbo = JFactory::getDbo();

		if ($units < 1) {
			return false;
		}

		$room_info = self::getRoomInfo($idroom, ['id', 'name', 'units', 'img']);
		if (!$room_info) {
			return false;
		}

		$secdiff = $second - $first;
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
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}

		$q = $dbo->getQuery(true)
			->select($dbo->qn([
				'id',
				'checkin',
				'realback',
			]))
			->from($dbo->qn('#__vikbooking_busy'))
			->where($dbo->qn('idroom') . ' = ' . (int)$idroom)
			->where($dbo->qn('realback') . ' >= ' . $first);

		$dbo->setQuery($q);
		$busy = $dbo->loadAssocList();
		if (!$busy) {
			return ($units <= $room_info['units']);
		}

		$groupdays = self::getGroupDays($first, $second, $daysdiff);
		foreach ($groupdays as $gday) {
			$bfound = 0;
			foreach ($busy as $bu) {
				if (in_array($bu['id'], $skip_busy_ids)) {
					// booking modification
					continue;
				}
				if ($gday >= $bu['checkin'] && $gday <= $bu['realback']) {
					$bfound++;
				}
			}
			if ($bfound >= $units) {
				return false;
			}
		}

		return ($units <= $room_info['units']);
	}

	public static function payTotal()
	{
		return VBOFactory::getConfig()->getBool('paytotal', true);
	}

	public static function getDepositIfDays()
	{
		return VBOFactory::getConfig()->getInt('depifdaysadv', 0);
	}

	public static function depositAllowedDaysAdv($checkints) {
		$days_adv = self::getDepositIfDays();
		if (!($days_adv > 0) || !($checkints > 0)) {
			return true;
		}
		$now_info = getdate();
		$maxts = mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] + $days_adv), $now_info['year']);
		return $maxts > $checkints ? false : true;
	}

	public static function depositCustomerChoice() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='depcustchoice';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getDepositOverrides($getjson = false) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='depoverrides';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return $getjson ? $s : json_decode($s, true);
		}
		//count of this array will be at least 1 to store the "more" property
		$def_arr = array('more' => '');
		$def_val = json_encode($def_arr);
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('depoverrides', ".$dbo->quote($def_val).");";
		$dbo->setQuery($q);
		$dbo->execute();
		return $getjson ? $def_val : $def_arr;
	}

	public static function calcDepositOverride($amount_deposit, $nights) {
		$overrides = self::getDepositOverrides();
		$nights = intval($nights);
		$andmore = intval($overrides['more']);
		if (!(count($overrides) > 1)) {
			//no overrides
			return $amount_deposit;
		}
		foreach ($overrides as $k => $v) {
			if ((int)$k == $nights && strlen($v) > 0) {
				//exact override found
				return (float)$v;
			}
		}
		if ($andmore > 0 && $andmore <= $nights) {
			foreach ($overrides as $k => $v) {
				if ((int)$k == $andmore && strlen($v) > 0) {
					//"and more" nights found
					return (float)$v;
				}
			}
		}
		//nothing was found
		return $amount_deposit;
	}

	public static function noDepositForNonRefund() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='nodepnonrefund';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			return ((int)$s == 1);
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('nodepnonrefund', '0');";
		$dbo->setQuery($q);
		$dbo->execute();
		//default to false
		return false;
	}

	/**
	 * This method returns the room-rate array with the lowest price
	 * that matches the preferred rate plan parameter (if available).
	 * The array $website_rates could not be an array, or it could be
	 * an array with the error string (response from the TACVBO Class).
	 * The method has been introduced in VBO 1.10 and it's mainly used
	 * by the module mod_vikbooking_channelrates and its ajax requests.
	 *
	 * @param 	array  		$website_rates 		the array of the website rates returned by the method fetchWebsiteRates()
	 * @param 	int  		$def_rplan 			the id of the default type of price to take for display. If empty, take the lowest rate
	 *
	 * @return 	array
	 */
	public static function getBestRoomRate($website_rates, $def_rplan) {
		if (!is_array($website_rates) || !(count($website_rates) > 0) || (is_array($website_rates) && isset($website_rates['e4j.error']))) {
			return array();
		}
		$best_room_rate = array();
		foreach ($website_rates as $rid => $tars) {
			foreach ($tars as $tar) {
				if (empty($def_rplan) || (int)$tar['idprice'] == $def_rplan) {
					//the array $website_rates is already sorted by price ASC, so we take the first useful array
					$best_room_rate = $tar;
					break 2;
				}
			}
		}
		if (!(count($best_room_rate) > 0)) {
			//the default rate plan is not available if we enter this statement, so we take the first and lowest rate
			foreach ($website_rates as $rid => $tars) {
				foreach ($tars as $tar) {
					$best_room_rate = $tar;
					break 2;
				}
			}
		}

		return $best_room_rate;
	}

	/**
	 * This method returns an array with the details
	 * of all channels in VCM that supports AV requests,
	 * and that have at least one room type mapped.
	 * The method invokes the Logos Class to return details
	 * about the name and logo URL of the channel.
	 * The method has been introduced in VBO 1.10 and it's mainly used
	 * by the module mod_vikbooking_channelrates and its ajax requests.
	 *
	 * @param 	array 	$channels 	an array of channel IDs to be mapped on the VCM relations
	 *
	 * @return 	array
	 */
	public static function getChannelsMap($channels) {
		if (!is_array($channels) || !(count($channels))) {
			return array();
		}
		$vcm_logos = self::getVcmChannelsLogo('', true);
		if (!is_object($vcm_logos)) {
			return array();
		}
		$channels_ids = array();
		foreach ($channels as $chid) {
			$ichid = (int)$chid;
			if ($ichid < 1) {
				continue;
			}
			array_push($channels_ids, $ichid);
		}
		if (!(count($channels_ids))) {
			return array();
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `idchannel`, `channel` FROM `#__vikchannelmanager_roomsxref` WHERE `idchannel` IN (".implode(', ', $channels_ids).") GROUP BY `idchannel`,`channel` ORDER BY `channel` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			return array();
		}
		$channels_names = $dbo->loadAssocList();
		$channels_map = array();
		foreach ($channels_names as $ch) {
			$ota_logo_url = $vcm_logos->setProvenience($ch['channel'])->getLogoURL();
			$ota_logo_url = $ota_logo_url === false ? '' : $ota_logo_url;
			$chdata = array(
				'id' => $ch['idchannel'],
				'name' => ucwords($ch['channel']),
				'logo' => $ota_logo_url
			);
			array_push($channels_map, $chdata);
		}
		return $channels_map;
	}

	/**
	 * This method returns a string to calculate the rates for the OTAs. Data is taken from the Bulk Rates Cache
	 * of Vik Channel Manager. The string returned contains the charge/discount operator at the position 0 (+ or -),
	 * and the percentage char (%) at the last position (if percent). Between the first and last position there is 
	 * the float value. The method has been introduced in VBO 1.10 and it's mainly used by the "channel rates" 
	 * module/widget and its ajax requests.
	 *
	 * @param 	array 	$best_room_rate 	array containing a specific tariff returned by getBestRoomRate().
	 * @param 	bool 	$per_channel 		if true, an associative array will be returned for each channel alteration.
	 *
	 * @return 	mixed 						string (empty or indicating the alteration), or associative array per channel.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)  added support for VCM different alterations per channel.
	 */
	public static function getOtasRatesVal($best_room_rate, $per_channel = false)
	{
		$otas_rates_val  = '';
		if (!is_array($best_room_rate) || !count($best_room_rate) || !is_file(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			return $otas_rates_val;
		}
		if (!class_exists('VikChannelManager')) {
			require_once(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php');
		}
		$bulk_rates_cache = VikChannelManager::getBulkRatesCache();
		if (count($bulk_rates_cache) && isset($best_room_rate['idprice'])) {
			if (isset($bulk_rates_cache[$best_room_rate['idroom']]) && isset($bulk_rates_cache[$best_room_rate['idroom']][$best_room_rate['idprice']])) {
				// the Bulk Rates Cache contains data for this room type and rate plan
				$data_cont = $bulk_rates_cache[$best_room_rate['idroom']][$best_room_rate['idprice']];
				// build rates modification string
				if ((int)$data_cont['rmod'] > 0) {
					// rates were modified for the OTAs, check how
					$rmodop = (int)$data_cont['rmodop'] > 0 ? '+' : '-';
					$rmodpcent = (int)$data_cont['rmodval'] > 0 ? '%' : '';
					$otas_rates_val = $rmodop.(float)$data_cont['rmodamount'].$rmodpcent;
					// check alterations per channel, if requested
					if ($per_channel && !empty($data_cont['rmod_channels']) && is_array($data_cont['rmod_channels'])) {
						// we compose an associative array with the alteration for each channel (0 = generic rule)
						$channel_alterations = array($otas_rates_val);
						foreach ($data_cont['rmod_channels'] as $ch_id => $ch_mods) {
							if ((int)$ch_mods['rmod'] > 0) {
								// custom rate alteration for this channel
								$rmodop = (int)$ch_mods['rmodop'] > 0 ? '+' : '-';
								$rmodpcent = (int)$ch_mods['rmodval'] > 0 ? '%' : '';
								$ch_alter_string = $rmodop . (float)$ch_mods['rmodamount'] . $rmodpcent;
							} else {
								// no rates alteration for this channel identifier
								$ch_alter_string = '+0%';
							}
							$channel_alterations[$ch_id] = $ch_alter_string;
						}
						// overwrite return value with associative array
						$otas_rates_val = $channel_alterations;
					}
				}
			}
		}

		return $otas_rates_val;
	}

	/**
	 * This method checks if some non-refundable rates were selected
	 * (`free_cancellation`=0), the only argument is an array of tariffs.
	 * The property 'idprice' must be defined on each sub-array.
	 * 
	 * @param 	$tars 		array
	 * 
	 * @return 	boolean
	 **/
	public static function findNonRefundableRates($tars = [])
	{
		$id_prices = [];

		foreach ($tars as $tar) {
			if (isset($tar['idprice'])) {
				if (!in_array($tar['idprice'], $id_prices)) {
					array_push($id_prices, (int)$tar['idprice']);
				}
				continue;
			}
			foreach ($tar as $t) {
				if (isset($t['idprice'])) {
					if (!in_array($t['idprice'], $id_prices)) {
						array_push($id_prices, (int)$t['idprice']);
					}
				}
			}
		}

		if (!count($id_prices)) {
			// no price IDs found (probably a package)
			return false;
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `id`,`name` FROM `#__vikbooking_prices` WHERE `id` IN (".implode(', ', $id_prices).") AND `free_cancellation`=0;";
		$dbo->setQuery($q);
		$dbo->execute();

		return (bool)($dbo->getNumRows() > 0);
	}

	/**
	 * This method checks if the deposit is allowed depending on
	 * the selected rate plans (idprice) for the rooms reserved.
	 * If the configuration setting is enabled, and if some
	 * non-refundable rates were selected (`free_cancellation`=0),
	 * the method will return false, because the deposit is not allowed.
	 * The only argument is an array of tariffs. The property 'idprice'
	 * must be defined on each sub-array (multi-dimension supported)
	 * throgh the method findNonRefundableRates();
	 * 
	 * @param 	$tars 		array
	 * 
	 * @return 	boolean
	 **/
	public static function allowDepositFromRates($tars) {
		if (!self::noDepositForNonRefund()) {
			//deposit can be paid also if non-refundable rates
			return true;
		}
		return !self::findNonRefundableRates($tars);
	}

	public static function showSearchSuggestions() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='searchsuggestions';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return (int)$dbo->loadResult();
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('searchsuggestions', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	/**
	 * Given a code, returns the coupon information array.
	 * 
	 * @param 	string 	$code 	the coupon code.
	 * 
	 * @return 	array 			empty array or coupon record.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)  the customers assigned are also returned.
	 */
	public static function getCouponInfo($code)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_coupons` WHERE `code`=".$dbo->quote($code);
		$dbo->setQuery($q, 0, 1);
		$coupon = $dbo->loadAssoc();
		if (!$coupon) {
			return [];
		}

		// check for customers assigned
		$q = "SELECT `idcustomer`, `automatic` FROM `#__vikbooking_customers_coupons` WHERE `idcoupon`=" . (int)$coupon['id'];
		$dbo->setQuery($q);
		$coupon_customers = $dbo->loadAssocList();
		if ($coupon_customers) {
			// set customers and automatic properties
			$coupon['customers'] = [];
			$coupon['automatic'] = $coupon_customers[0]['automatic'];
			foreach ($coupon_customers as $coupon_customer) {
				// push customer ID
				$coupon['customers'][] = $coupon_customer['idcustomer'];
			}
		}

		return $coupon;
	}

	public static function getRoomInfo($idroom, $columns = [])
	{
		static $room_infos = [];

		if (isset($room_infos[$idroom])) {
			return $room_infos[$idroom];
		}

		$dbo = JFactory::getDbo();

		if ($columns) {
			$columns = array_map([$dbo, 'qn'], $columns);
		}

		$q = $dbo->getQuery(true)
			->select($columns ? $columns : '*')
			->from($dbo->qn('#__vikbooking_rooms'))
			->where($dbo->qn('id') . ' = ' . (int)$idroom);

		$dbo->setQuery($q);
		$room = $dbo->loadAssoc();

		$room_infos[$idroom] = $room ? $room : [];

		return $room_infos[$idroom];
	}

	public static function loadOrdersRoomsData($idorder)
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select($dbo->qn('or') . '.*')
			->select($dbo->qn('r.name', 'room_name'))
			->select($dbo->qn('r.params'))
			->from($dbo->qn('#__vikbooking_ordersrooms', 'or'))
			->leftJoin($dbo->qn('#__vikbooking_rooms', 'r') . ' ON ' . $dbo->qn('or.idroom') . ' = ' . $dbo->qn('r.id'))
			->where($dbo->qn('or.idorder') . ' = ' . (int)$idorder)
			->order($dbo->qn('or.id') . ' ASC');

		$dbo->setQuery($q);

		return $dbo->loadAssocList();
	}

	public static function sayCategory($ids, $vbo_tn = null)
	{
		$dbo = JFactory::getDbo();
		$split = explode(";", $ids);
		$say = "";
		foreach ($split as $k => $s) {
			if (!strlen($s)) {
				continue;
			}
			$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` WHERE `id`=" . (int)$s . ";";
			$dbo->setQuery($q);
			$nam = $dbo->loadAssocList();
			if (!$nam) {
				continue;
			}
			if (is_object($vbo_tn)) {
				$vbo_tn->translateContents($nam, '#__vikbooking_categories');
			}
			$say .= $nam[0]['name'];
			$say .= (strlen($split[($k +1)]) && end($split) != $s ? ", " : "");
		}
		return $say;
	}

	public static function getRoomCaratOriz($idc, $vbo_tn = null)
	{
		$dbo = JFactory::getDbo();
		$split = explode(";", $idc);
		$carat = "";
		$arr = array();
		$where = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$where[] = $s;
			}
		}
		if ($where) {
			$q = "SELECT `c`.* FROM `#__vikbooking_characteristics` AS `c` WHERE `c`.`id` IN (" . implode(",", $where) . ") ORDER BY `c`.`ordering` ASC;";
			$dbo->setQuery($q);
			$arr = $dbo->loadAssocList();
			if ($arr && is_object($vbo_tn)) {
				$vbo_tn->translateContents($arr, '#__vikbooking_characteristics');
			}
		}
		if ($arr) {
			$carat .= "<div class=\"vbo-room-carats\">\n";
			foreach ($arr as $a) {
				if (!empty($a['textimg'])) {
					//tooltip icon text is not empty
					if (!empty($a['icon'])) {
						//an icon has been uploaded: display the image
						$carat .= "<span class=\"vbo-room-carat\"><span class=\"vbo-expl\" data-vbo-expl=\"".$a['textimg']."\"><img src=\"".VBO_SITE_URI."resources/uploads/".$a['icon']."\" alt=\"" . $a['name'] . "\" /></span></span>\n";
					} else {
						if (strpos($a['textimg'], '</i>') !== false) {
							//the tooltip icon text is a font-icon, we can use the name as tooltip
							$carat .= "<span class=\"vbo-room-carat\"><span class=\"vbo-expl\" data-vbo-expl=\"".$a['name']."\">".$a['textimg']."</span></span>\n";
						} else {
							//display just the text
							$carat .= "<span class=\"vbo-room-carat\">".$a['textimg']."</span>\n";
						}
					}
				} else {
					$carat .= (!empty($a['icon']) ? "<span class=\"vbo-room-carat\"><img src=\"".VBO_SITE_URI."resources/uploads/" . $a['icon'] . "\" alt=\"" . $a['name'] . "\" title=\"" . $a['name'] . "\"/></span>\n" : "<span class=\"vbo-room-carat\">".$a['name']."</span>\n");
				}
			}
			$carat .= "</div>\n";
		}
		return $carat;
	}

	public static function getRoomOptionals($idopts, $vbo_tn = null)
	{
		$dbo = JFactory::getDbo();
		$split = explode(";", $idopts);
		$fetch = array();
		foreach ($split as $s) {
			if (!empty($s)) {
				$fetch[] = $s;
			}
		}
		if ($fetch) {
			$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id` IN (".implode(", ", $fetch).") ORDER BY `#__vikbooking_optionals`.`ordering` ASC;";
			$dbo->setQuery($q);
			$arr = $dbo->loadAssocList();
			if ($arr) {
				if (is_object($vbo_tn)) {
					$vbo_tn->translateContents($arr, '#__vikbooking_optionals');
				}
				return $arr;
			}
		}
		return [];
	}

	public static function getSingleOption($idopt, $vbo_tn = null)
	{
		$dbo = JFactory::getDbo();
		$opt = array();
		if (!empty($idopt)) {
			$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . (int)$idopt . ";";
			$dbo->setQuery($q);
			$opt = $dbo->loadAssoc();
			if ($opt && is_object($vbo_tn)) {
				$vbo_tn->translateContents($opt, '#__vikbooking_optionals');
			}
		}
		return $opt;
	}

	/**
	 * Unsets the options that are not available in the requested dates.
	 *
	 * @param 	array 	$optionals 	the array of options passed by reference
	 * @param 	int 	$checkin 	the timestamp of the check-in date and time
	 * @param 	int 	$checkout 	the timestamp of the check-out date and time
	 *
	 * @return 	void
	 *
	 * @since 	1.11
	 */
	public static function filterOptionalsByDate(&$optionals, $checkin, $checkout)
	{
		if (!$optionals || empty($checkin) || empty($checkout)) {
			return;
		}

		foreach ($optionals as $k => $v) {
			if (!empty($v['alwaysav'])) {
				$dates = explode(';', $v['alwaysav']);
				if (empty($dates[0]) || empty($dates[1])) {
					continue;
				}
				// it is sufficient that the check-in is included within the validity dates, we ignore the checkout
				if (!($checkin >= $dates[0]) || !($checkin <= $dates[1])) {
					unset($optionals[$k]);
				}
			}
		}
	}

	/**
	 * Unsets the options that are not suited for the requested room party.
	 *
	 * @param 	array 	$optionals 	the array of options passed by reference.
	 * @param 	int 	$adults 	the number of adults in the room party.
	 * @param 	int 	$children 	the number of children in the room party.
	 *
	 * @return 	void
	 *
	 * @since 	1.13.5
	 */
	public static function filterOptionalsByParty(&$optionals, $adults, $children)
	{
		if (!$optionals || (empty($adults) && empty($children))) {
			return;
		}

		foreach ($optionals as $k => $v) {
			if (empty($v['oparams'])) {
				continue;
			}
			$v['oparams'] = json_decode($v['oparams'], true);
			if (!is_array($v['oparams']) || !count($v['oparams']) || (empty($v['oparams']['minguestsnum']) && empty($v['oparams']['maxguestsnum']))) {
				continue;
			}
			if (!empty($v['oparams']['minguestsnum'])) {
				// filter by minimum adults/guests
				if ($v['oparams']['mingueststype'] == 'adults' && $adults <= $v['oparams']['minguestsnum']) {
					// minimum number of adults not sufficient, unset option
					unset($optionals[$k]);
				} elseif ($v['oparams']['mingueststype'] == 'guests' && ($adults + $children) <= $v['oparams']['minguestsnum']) {
					// minimum number of total guests not sufficient, unset option
					unset($optionals[$k]);
				}
			}
			if (!empty($v['oparams']['maxguestsnum'])) {
				// filter by maximum adults/guests
				if ($v['oparams']['maxgueststype'] == 'adults' && $adults >= $v['oparams']['maxguestsnum']) {
					// maximum number of adults exceeded, unset option
					unset($optionals[$k]);
				} elseif ($v['oparams']['maxgueststype'] == 'guests' && ($adults + $children) >= $v['oparams']['maxguestsnum']) {
					// maximum number of total guests exceeded, unset option
					unset($optionals[$k]);
				}
			}
		}
	}
	
	/**
	 * Builds up the information related to mandatory taxes and fees for a list of rooms.
	 * 
	 * @param 	array 	$id_rooms 		list of room IDs.
	 * @param 	int 	$num_adults 	number of adults.
	 * @param 	int 	$num_nights 	number of nights of stay.
	 * @param 	int 	$checkin 		optional check-in timestamp.
	 * @param 	int 	$checkout 		optional check-out timestamp.
	 * 
	 * @return 	array 					associative information of city taxes, fees and options.
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP) added arguments $checkin and $checkout to allow filtering by dates.
	 */
	public static function getMandatoryTaxesFees(array $id_rooms, $num_adults, $num_nights, $checkin = null, $checkout = null)
	{
		$dbo = JFactory::getDbo();

		$taxes = 0;
		$fees  = 0;

		$options_data = [];
		$id_options   = [];

		$q = "SELECT `id`,`idopt` FROM `#__vikbooking_rooms` WHERE `id` IN (" . implode(", ", array_map('intval', $id_rooms)) . ");";
		$dbo->setQuery($q);
		$assocs = $dbo->loadAssocList();
		if ($assocs) {
			foreach ($assocs as $opts) {
				if (!empty($opts['idopt'])) {
					$r_ido = explode(';', rtrim($opts['idopt']));
					foreach ($r_ido as $ido) {
						if (!empty($ido) && !in_array($ido, $id_options)) {
							$id_options[] = (int)$ido;
						}
					}
				}
			}
		}

		if ($id_options) {
			$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id` IN (" . implode(", ", $id_options) . ") AND `forcesel`=1 AND `ifchildren`=0 AND (`is_citytax`=1 OR `is_fee`=1);";
			$dbo->setQuery($q);
			$alltaxesfees = $dbo->loadAssocList();

			if ($checkin && $checkout) {
				// filter the records by stay dates to support, for example, seasonal city taxes
				self::filterOptionalsByDate($alltaxesfees, $checkin, $checkout);
			}

			foreach ($alltaxesfees as $tf) {
				$realcost = (intval($tf['perday']) == 1 ? ($tf['cost'] * $num_nights) : $tf['cost']);
				if (!empty($tf['maxprice']) && $tf['maxprice'] > 0 && $realcost > $tf['maxprice']) {
					$realcost = $tf['maxprice'];
				}
				$realcost = $tf['perperson'] == 1 ? ($realcost * $num_adults) : $realcost;
				$realcost = self::sayOptionalsPlusIva($realcost, $tf['idiva']);
				if ($tf['is_citytax'] == 1) {
					$taxes += $realcost;
				} elseif ($tf['is_fee'] == 1) {
					$fees += $realcost;
				}
				$optsett = explode('-', $tf['forceval']);
				$options_data[] = $tf['id'].':'.$optsett[0];
			}
		}

		return [
			'city_taxes' => $taxes,
			'fees' 		 => $fees,
			'options' 	 => $options_data
		];
	}
	
	/**
	 * From a list of option/extras records, it returns an array with the options/extras
	 * that do not support age intervals, and options for the children age intervals.
	 * To be used as list($optionals, $ageintervals) = VikBooking::loadOptionAgeIntervals($optionals).
	 * 
	 * @param 	array 	$optionals 	the full list of options/extras records.
	 * @param 	int 	$adults 	the number of adults in the room requested.
	 * @param 	int 	$children 	the number of children in the room requested.
	 * 
	 * @return 	array 	the filtered regular and children age intervals options.
	 */
	public static function loadOptionAgeIntervals($optionals, $adults = null, $children = null)
	{
		// container for age intervals
		$ageintervals = [];

		// regular options should start as an array
		if (!is_array($optionals)) {
			$optionals = [];
		}
		
		// check for the first valid age intervals option
		foreach ($optionals as $kopt => $opt) {
			if (!empty($opt['ageintervals'])) {
				$intervals = explode(';;', $opt['ageintervals']);
				foreach ($intervals as $intv) {
					if (empty($intv)) {
						continue;
					}
					$parts = explode('_', $intv);
					if (count($parts) >= 3) {
						// age intervals option found, get the first and only one
						$ageintervals = $optionals[$kopt];
						break 2;
					}
				}
			}
		}
		
		if ($ageintervals) {
			/**
			 * We allow price adjustments for a minimum total guests by overriding the costs
			 * for each age interval for specific child numbers when the party covers some children.
			 * For example, when children should pay only starting from the 5th guest (minguestsnum=4),
			 * in case of a room party for 2 adults and 3 children, only the 3rd child should pay the
			 * regular fees, while the 1st and second children should have all age intervals to 0.00.
			 *
			 * @since 	1.13.5
			 */
			$oparams = !empty($ageintervals['oparams']) ? json_decode($ageintervals['oparams'], true) : array();
			$oparams = !is_array($oparams) ? array() : $oparams;
			$adults = (int)$adults;
			$children = (int)$children;
			$valid_guesttype = (!empty($oparams['mingueststype']) && $oparams['mingueststype'] == 'guests');
			if ($children > 0 && !empty($oparams['minguestsnum']) && $valid_guesttype && ($adults + $children) > $oparams['minguestsnum']) {
				$children_to_pay = ($adults + $children) - $oparams['minguestsnum'];
				if ($children_to_pay < $children) {
					// compose string for free age intervals
					$intervals = explode(';;', $ageintervals['ageintervals']);
					foreach ($intervals as $k => $v) {
						$parts = explode('_', $v);
						// set cost to 0 (key = 2)
						$parts[2] = '0';
						$intervals[$k] = implode('_', $parts);
					}
					$free_ageintervals = implode(';;', $intervals);
					for ($i = 1; $i <= $children; $i++) {
						if (($children - $i + 1) <= $children_to_pay) {
							// costs for this Nth last child should be regular as this child should pay
							$ageintervals['ageintervals_child' . $i] = $ageintervals['ageintervals'];
						} else {
							// override costs to 0.00 for this Nth first child since it's covered by the min guests number
							$ageintervals['ageintervals_child' . $i] = $free_ageintervals;
						}
					}
				}
			}

			// remove age intervals from regular options
			foreach ($optionals as $kopt => $opt) {
				if ($opt['id'] == $ageintervals['id'] || !empty($opt['ageintervals'])) {
					// unset the option of type age intervals from the regular options
					unset($optionals[$kopt]);
				}
			}
		}

		// return the filtered list of regular options and age intervals options
		return [$optionals, $ageintervals];
	}

	/**
	 * Returns an array of overrides (if any) for the specific children Nth number in the party.
	 * This is because some children in the party may not need to pay anything due to the min guests.
	 * 
	 * @param 	array 	$optional	the option full record to parse.
	 * @param 	int 	$adults 	the number of adults in the room requested.
	 * @param 	int 	$children 	the number of children in the room requested.
	 * 
	 * @return 	array 	associative array with default age intervals and override strings.
	 * 
	 * @since 	1.13.5
	 */
	public static function getOptionIntervalChildOverrides($optional, $adults, $children)
	{
		if (!is_array($optional)) {
			$optional = array();
		}

		$overrides = array(
			'ageintervals' => (!empty($optional['ageintervals']) ? $optional['ageintervals'] : '')
		);

		$oparams = !empty($optional['oparams']) ? json_decode($optional['oparams'], true) : array();
		$oparams = !is_array($oparams) ? array() : $oparams;
		$adults = (int)$adults;
		$children = (int)$children;

		if (!count($oparams)) {
			return $overrides;
		}

		$valid_guesttype = (!empty($oparams['mingueststype']) && $oparams['mingueststype'] == 'guests');
		if ($children > 0 && !empty($oparams['minguestsnum']) && $valid_guesttype && ($adults + $children) > $oparams['minguestsnum']) {
			$children_to_pay = ($adults + $children) - $oparams['minguestsnum'];
			if ($children_to_pay < $children) {
				// compose string for free age intervals
				$intervals = explode(';;', $optional['ageintervals']);
				foreach ($intervals as $k => $v) {
					$parts = explode('_', $v);
					// set cost to 0 (key = 2)
					$parts[2] = '0';
					$intervals[$k] = implode('_', $parts);
				}
				$free_ageintervals = implode(';;', $intervals);
				for ($i = 1; $i <= $children; $i++) {
					if (($children - $i + 1) <= $children_to_pay) {
						// costs for this Nth last child should be regular as this child should pay
						$overrides['ageintervals_child' . $i] = $optional['ageintervals'];
					} else {
						// override costs to 0.00 for this Nth first child since it's covered by the min guests number
						$overrides['ageintervals_child' . $i] = $free_ageintervals;
					}
				}
			}
		}

		return $overrides;
	}

	/**
	 * Returns the number/index of the children being parsed given the full list of room options string,
	 * the current key in the array of the split room options string, the ID of the ageintervals option,
	 * and the total number of children. This is useful for later applying the cost overrides for the
	 * children ages through the method getOptionIntervalChildOverrides().
	 * 
	 * @param 	string 	$roptstr	plain room option string of "#__vikbooking_ordersrooms".
	 * @param 	int 	$optid 		the ID of the option of type age intervals to check.
	 * @param 	int 	$roptkey 	the current position in the loop of the room options string.
	 * @param 	int 	$children 	the number of children in the room requested.
	 * 
	 * @return 	int 	the position/number of the children being parsed, -1 if not found.
	 * 
	 * @since 	1.13.5
	 */
	public static function getRoomOptionChildNumber($roptstr, $optid, $roptkey, $children)
	{
		// default to -1 for not found
		$child_num = -1;

		$valid_opt_counter = 0;

		$roptions = explode(";", $roptstr);
		foreach ($roptions as $k => $opt) {
			if (empty($opt)) {
				continue;
			}
			$optvals = explode(":", $opt);
			/**
			 * In some cases, like the "saveorder" task, the room option string may contain
			 * the room number beside the option ID, separated with an underscore.
			 * If an underscore is present, we need to split it to find the option ID.
			 */
			if (strpos($optvals[0], '_') !== false) {
				// underscore found in room number portion, extract the option ID
				$rn_parts = explode('_', $optvals[0]);
				// 0th element is the room number in the party, 1st elem is the option ID
				$optvals[0] = $rn_parts[1];
			}
			//
			if ((int)$optvals[0] != (int)$optid) {
				// we are not interested into this option ID
				continue;
			}
			// increase counter for this option ID
			$valid_opt_counter++;

			if ((int)$k == (int)$roptkey && $valid_opt_counter <= $children) {
				// children position found, we need to return it as a 0th base
				$child_num = ($valid_opt_counter - 1);
				break;
			}
		}

		return $child_num;
	}
	
	public static function getOptionIntervalsCosts($intvstr) {
		$optcosts = array();
		$intervals = explode(';;', $intvstr);
		foreach ($intervals as $kintv => $intv) {
			if (empty($intv)) continue;
			$parts = explode('_', $intv);
			if (count($parts) >= 3) {
				$optcosts[$kintv] = (float)$parts[2];
			}
		}
		return $optcosts;
	}
	
	public static function getOptionIntervalsAges($intvstr) {
		$optages = array();
		$intervals = explode(';;', $intvstr);
		foreach ($intervals as $kintv => $intv) {
			if (empty($intv)) continue;
			$parts = explode('_', $intv);
			if (count($parts) >= 3) {
				$optages[$kintv] = $parts[0].' - '.$parts[1];
			}
		}
		return $optages;
	}

	public static function getOptionIntervalsPercentage($intvstr) {
		/* returns an associative array to tell whether an interval has a percentage cost (VBO 1.8) */
		$optcostspcent = array();
		$intervals = explode(';;', $intvstr);
		foreach ($intervals as $kintv => $intv) {
			if (empty($intv)) continue;
			$parts = explode('_', $intv);
			if (count($parts) >= 3) {
				//fixed amount
				$setval = 0;
				if (array_key_exists(3, $parts) && strpos($parts[3], '%b') !== false) {
					//percentage value of the room base cost (VBO 1.10)
					$setval = 2;
				} elseif (array_key_exists(3, $parts) && strpos($parts[3], '%') !== false) {
					//percentage value of the adults tariff
					$setval = 1;
				}
				$optcostspcent[$kintv] = $setval;
			}
		}
		return $optcostspcent;
	}

	public static function dayValidTs($days, $first, $second)
	{
		$secdiff = $second - $first;
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
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}

		return ($daysdiff == $days);
	}

	/**
	 * Calculates the stay fee inclusive of taxes.
	 * 
	 * @param 	float 	$cost 		the cost to evaluate.
	 * @param 	int 	$idprice 	the rate plan ID associated.
	 * 
	 * @return 	float 	the given cost after tax, if taxes had to be applied.
	 */
	public static function sayCostPlusIva($cost, $idprice)
	{
		$ivainclusa = self::ivaInclusa();
		if ($ivainclusa) {
			return $cost;
		}

		static $tax_rplans_map = [];

		if (array_key_exists($idprice, $tax_rplans_map)) {
			$tax_rate = $tax_rplans_map[$idprice];
		} else {
			$dbo = JFactory::getDbo();
			$q = "SELECT `p`.`idiva`,`i`.`aliq`,`i`.`taxcap` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` `i` ON `i`.`id`=`p`.`idiva` WHERE `p`.`id`=" . (int)$idprice . ";";
			$dbo->setQuery($q);
			$tax_rate = $dbo->loadAssoc();

			// cache value
			$tax_rplans_map[$idprice] = $tax_rate;
		}

		if (!$tax_rate || empty($tax_rate['aliq'])) {
			return $cost;
		}

		$subt = 100 + $tax_rate['aliq'];
		$op = ($cost * $subt / 100);

		/**
		 * Tax Cap implementation for prices tax excluded (most common).
		 * 
		 * @since 	1.12
		 */
		if ($tax_rate['taxcap'] > 0 && ($op - $cost) > $tax_rate['taxcap']) {
			$op = ($cost + $tax_rate['taxcap']);
		}

		// apply rounding to avoid issues with multiple tax rates when tax excluded
		$formatvals = self::getNumberFormatData();
		$formatparts = explode(':', $formatvals);
		$rounded_op = round($op, (int)$formatparts[0]);
		if ($rounded_op > $op) {
			return $rounded_op;
		}

		/**
		 * When using base costs with decimals, and no tax rates assigned, maybe for a foreigners rate plan,
		 * no rounding is ever made, and so we should always apply such rounding to avoid getting decimals if they should be 0.
		 * 
		 * @since 	1.11.1
		 */
		return round($op, (int)$formatparts[0]);
	}

	/**
	 * Calculates the stay fee exclusive of taxes.
	 * 
	 * @param 	float 	$cost 		the cost to evaluate.
	 * @param 	int 	$idprice 	the rate plan ID associated.
	 * 
	 * @return 	float 	the given cost before tax.
	 */
	public static function sayCostMinusIva($cost, $idprice)
	{
		$ivainclusa = self::ivaInclusa();
		if (!$ivainclusa) {
			return $cost;
		}

		static $tax_rplans_map = [];

		if (array_key_exists($idprice, $tax_rplans_map)) {
			$tax_rate = $tax_rplans_map[$idprice];
		} else {
			$dbo = JFactory::getDbo();
			$q = "SELECT `p`.`idiva`,`i`.`aliq`,`i`.`taxcap` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` `i` ON `i`.`id`=`p`.`idiva` WHERE `p`.`id`=" . (int)$idprice . ";";
			$dbo->setQuery($q);
			$tax_rate = $dbo->loadAssoc();

			// cache value
			$tax_rplans_map[$idprice] = $tax_rate;
		}

		if (!$tax_rate || empty($tax_rate['aliq'])) {
			return $cost;
		}

		$subt = 100 + $tax_rate['aliq'];
		$op = ($cost * 100 / $subt);

		/**
		 * Tax Cap implementation also when prices tax included.
		 * 
		 * @since 	1.12
		 */
		if ($tax_rate['taxcap'] > 0 && ($cost - $op) > $tax_rate['taxcap']) {
			$op = ($cost - $tax_rate['taxcap']);
		}

		// apply rounding to avoid issues with multiple tax rates when tax included
		$formatvals = self::getNumberFormatData();
		$formatparts = explode(':', $formatvals);
		$rounded_op = round($op, (int)$formatparts[0]);
		if ($rounded_op < $op) {
			return $rounded_op;
		}

		/**
		 * When using base costs with decimals, and no tax rates assigned, maybe for a foreigners rate plan,
		 * no rounding is ever made, and so we should always apply such rounding to avoid getting decimals if they should be 0.
		 * 
		 * @since 	1.11.1
		 */
		return round($op, (int)$formatparts[0]);
	}

	/**
	 * Given an option/extra cost, determines whether taxes should be applied over it.
	 * Can also be used to calculate taxes on the extra costs per room in the bookings
	 * 
	 * @param 	float 	$cost 	the cost to evaluate, option or extra service fee.
	 * @param 	int 	$idiva 	the ID of the tax rate to use to apply taxes.
	 * @param 	bool 	$force 	if true, taxes will always be applied.
	 * 
	 * @return 	float 	the given option/extra cost plus tax, if taxes had to be applied.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) added third argument $force to comply with VCM.
	 */
	public static function sayOptionalsPlusIva($cost, $idiva, $force = false)
	{
		$ivainclusa = self::ivaInclusa();
		if ($ivainclusa && $force !== true) {
			return $cost;
		}

		$tax_rate = VBOTaxonomySummary::getTaxRateRecord($idiva);
		if (!$tax_rate) {
			return $cost;
		}

		$subt = 100 + $tax_rate['aliq'];
		$op = ($cost * $subt / 100);

		/**
		 * Tax Cap implementation for prices tax excluded (most common).
		 * 
		 * @since 	1.12
		 */
		if ($tax_rate['taxcap'] > 0 && ($op - $cost) > $tax_rate['taxcap']) {
			$op = ($cost + $tax_rate['taxcap']);
		}

		// apply rounding to avoid issues with multiple tax rates when tax excluded
		$formatvals = self::getNumberFormatData();
		$formatparts = explode(':', $formatvals);
		$rounded_op = round($op, (int)$formatparts[0]);
		if ($rounded_op > $op) {
			return $rounded_op;
		}

		return $op;
	}

	/**
	 * Calculates the net amount for an option/extra service.
	 * 
	 * @param 	float 	$cost 	the cost to evaluate, option or extra service fee.
	 * @param 	int 	$idiva 	the ID of the tax rate to use to apply taxes.
	 * 
	 * @return 	float 	the given option/extra cost before tax, if taxes were inclusive.
	 */
	public static function sayOptionalsMinusIva($cost, $idiva)
	{
		if (empty($idiva)) {
			return $cost;
		}

		$ivainclusa = self::ivaInclusa();
		if (!$ivainclusa) {
			return $cost;
		}

		$tax_rate = VBOTaxonomySummary::getTaxRateRecord($idiva);
		if (!$tax_rate) {
			return $cost;
		}

		$subt = 100 + $tax_rate['aliq'];
		$op = ($cost * 100 / $subt);

		/**
		 * Tax Cap implementation also when prices tax included.
		 * 
		 * @since 	1.12
		 */
		if ($tax_rate['taxcap'] > 0 && ($cost - $op) > $tax_rate['taxcap']) {
			$op = ($cost - $tax_rate['taxcap']);
		}

		// apply rounding to avoid issues with multiple tax rates when tax included
		$formatvals = self::getNumberFormatData();
		$formatparts = explode(':', $formatvals);
		$rounded_op = round($op, (int)$formatparts[0]);
		if ($rounded_op < $op) {
			return $rounded_op;
		}

		return $op;
	}

	/**
	 * Given a cost, determines whether taxes should be applied over it.
	 * 
	 * @param 	float 	$cost 	the cost to evaluate, could be a room custom price.
	 * @param 	int 	$idiva 	the ID of the tax rate to use to apply taxes.
	 * @param 	bool 	$force 	if true, taxes will always be applied.
	 * 
	 * @return 	float 	the given cost plus tax, if taxes had to be applied.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) added third argument $force to comply with VCM.
	 */
	public static function sayPackagePlusIva($cost, $idiva, $force = false)
	{
		$ivainclusa = self::ivaInclusa();
		if ($ivainclusa && $force !== true) {
			return $cost;
		}

		$tax_rate = VBOTaxonomySummary::getTaxRateRecord($idiva);
		if (!$tax_rate) {
			return $cost;
		}

		$subt = 100 + $tax_rate['aliq'];
		$op = ($cost * $subt / 100);

		/**
		 * Tax Cap implementation for prices tax excluded (most common).
		 * 
		 * @since 	1.12
		 */
		if ($tax_rate['taxcap'] > 0 && ($op - $cost) > $tax_rate['taxcap']) {
			$op = ($cost + $tax_rate['taxcap']);
		}

		// apply rounding to avoid issues with multiple tax rates when tax excluded
		$formatvals = self::getNumberFormatData();
		$formatparts = explode(':', $formatvals);
		$rounded_op = round($op, (int)$formatparts[0]);
		if ($rounded_op > $op) {
			return $rounded_op;
		}

		return $op;
	}

	/**
	 * Calculates the net amount for a package.
	 * 
	 * @param 	float 	$cost 					the cost to evaluate, option or extra service fee.
	 * @param 	int 	$idiva 					the ID of the tax rate to use to apply taxes.
	 * @param 	bool 	$force_invoice_excltax 	whether to force the deduction of taxes.
	 * 
	 * @return 	float 							the given cost before tax.
	 */
	public static function sayPackageMinusIva($cost, $idiva, $force_invoice_excltax = false)
	{
		$ivainclusa = self::ivaInclusa();
		if (!$ivainclusa && $force_invoice_excltax !== true) {
			return $cost;
		}

		$tax_rate = VBOTaxonomySummary::getTaxRateRecord($idiva);
		if (!$tax_rate) {
			return $cost;
		}

		$subt = 100 + $tax_rate['aliq'];
		$op = ($cost * 100 / $subt);

		/**
		 * Tax Cap implementation also when prices tax included.
		 * 
		 * @since 	1.12
		 */
		if ($tax_rate['taxcap'] > 0 && ($cost - $op) > $tax_rate['taxcap']) {
			$op = ($cost - $tax_rate['taxcap']);
		}

		// apply rounding to avoid issues with multiple tax rates when tax included
		$formatvals = self::getNumberFormatData();
		$formatparts = explode(':', $formatvals);
		$rounded_op = round($op, (int)$formatparts[0]);
		if ($rounded_op < $op) {
			return $rounded_op;
		}

		return $op;
	}

	public static function getSecretLink()
	{
		$dbo = JFactory::getDbo();

		$sid = mt_rand();

		$q = "SELECT `sid` FROM `#__vikbooking_orders`;";
		$dbo->setQuery($q);
		$all = $dbo->loadAssocList();
		if ($all) {
			$list = [];
			foreach ($all as $s) {
				$list[] = $s['sid'];
			}
			if (in_array($sid, $list)) {
				while(in_array($sid, $list)) {
					$sid++;
				}
			}
		}

		return $sid;
	}

	/**
	 * Generates a confirmation number for the given booking.
	 * Modified the way random string-numbers are obtained to
	 * avoid any possible brute-force attempt.
	 * 
	 * @since 	1.15.1 (J) - 1.5.4 (WP)
	 */
	public static function generateConfirmNumber($oid, $update = true)
	{
		$dbo = JFactory::getDbo();

		$confirmnumb = date('y') . $oid;
		for ($i = 0; $i < 8; $i++) {
			$confirmnumb .= rand(0, 9);
		}

		if ($update) {
			$q = "UPDATE `#__vikbooking_orders` SET `confirmnumber`=" . $dbo->quote($confirmnumb) . " WHERE `id`=" . (int)$oid . ";";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		return $confirmnumb;
	}

	public static function buildCustData($arr, $sep) {
		$cdata = "";
		foreach ($arr as $k => $e) {
			if (strlen($e)) {
				$cdata .= (strlen($k) > 0 ? $k . ": " : "") . $e . $sep;
			}
		}
		return $cdata;
	}

	/**
	 * This method parses the Joomla menu object to see if a menu item of a
	 * specific type is available, to get its ID. Useful when links should be
	 * displayed in pages where there is no Itemid set (booking details pages).
	 *
	 * @param 	array 	$viewtypes 	list of accepted menu items
	 * @param 	string 	$lang 		the optional language to use.
	 *
	 * @return 	int
	 * 
	 * @since 	1.15.6 (J) - 1.5.12 (WP) added second argument $lang.
	 */
	public static function findProperItemIdType($viewtypes, $lang = null)
	{
		if (VBOPlatformDetection::isWordPress()) {
			$model = JModel::getInstance('vikbooking', 'shortcodes', 'admin');

			$itemid = $model->best($viewtypes, $lang);

			if ($itemid) {
				return $itemid;
			}

			return 0;
		}

		$bestitemid = 0;

		$current_lang = !empty($lang) ? $lang : JFactory::getLanguage()->getTag();

		$app = JFactory::getApplication();

		$menu = $app->getMenu('site');

		if (!$menu) {
			return 0;
		}

		$menu_items = $menu->getMenu();

		if (!$menu_items) {
			return 0;
		}

		foreach ($menu_items as $itemid => $item) {
			if (isset($item->query['option']) && $item->query['option'] == 'com_vikbooking' && in_array($item->query['view'], $viewtypes)) {
				// proper menu item type found
				$bestitemid = empty($bestitemid) ? $itemid : $bestitemid;

				if (isset($item->language) && $item->language == $current_lang) {
					// we found the exact menu item type for the given language
					return $itemid;
				}
			}
		}

		return $bestitemid;
	}

	/**
	 * Rewrites an internal URI that needs to be used outside of the website.
	 * This means that the routed URI MUST start with the base path of the site.
	 *
	 * @param 	mixed 	 $query 	The query string or an associative array of data.
	 * @param 	boolean  $xhtml  	Replace & by &amp; for XML compliance.
	 * @param 	mixed 	 $itemid 	The itemid to use. If null, the current one will be used.
	 *
	 * @return 	string 	The complete routed URI.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) adopted use of VBOPlatformUriAware, which also supports
	 * 									routing from back-end (if available on the CMS version).
	 */
	public static function externalroute($query = '', $xhtml = true, $itemid = null)
	{
		return VBOFactory::getPlatform()->getUri()->route($query, $xhtml, $itemid);
	}

	/**
	 * Generates an iCal file to be attached to the email message for the
	 * customer or the administrator with some basic booking details.
	 * 
	 * @param 	string 	$recip 		either admin or customer.
	 * @param 	array 	$booking 	the booking array or some keys.
	 * 
	 * @return 	mixed 	string in case of success, false otherwise.
	 * 
	 * @since 	1.12.0
	 */
	public static function getEmailIcal($recip, $booking) {
		// load configuration setting
		$attachical = self::attachIcal();

		if ($attachical === 0) {
			// do not attach any iCal file
			return false;
		}

		if ($attachical === 2 && strpos($recip, 'admin') === false) {
			// skip the iCal for the admin
			return false;
		}

		if ($attachical === 3 && strpos($recip, 'admin') !== false) {
			// skip the iCal for the customer
			return false;
		}

		if (strpos($recip, 'admin') !== false) {
			// prepare event description and summary for the admin
			$description = $booking['custdata'];
			$summary = !empty($booking['subject']) ? $booking['subject'] : '';
			$fname = $booking['ts'] . '.ics';
		} else {
			// event description and summary for the customer
			$description = '';
			$summary = self::getFrontTitle();
			$fname = 'reservation_reminder.ics';
		}

		// prepare iCal head
		$company_name = self::getFrontTitle();
		$ics_str = "BEGIN:VCALENDAR\r\n" .
					"PRODID:-//".$company_name."//".JUri::root()." 1.0//EN\r\n" .
					"CALSCALE:GREGORIAN\r\n" .
					"VERSION:2.0\r\n";
		// compose iCal body
		$ics_str .= 'BEGIN:VEVENT'."\r\n";
		$ics_str .= 'DTEND;VALUE=DATE:'.date('Ymd', $booking['checkout'])."\r\n";
		$ics_str .= 'DTSTART;VALUE=DATE:'.date('Ymd', $booking['checkin'])."\r\n";
		$ics_str .= 'UID:'.sha1($booking['ts'])."\r\n";
		$ics_str .= 'DESCRIPTION:'.preg_replace('/([\,;])/','\\\$1', $description)."\r\n";
		$ics_str .= 'SUMMARY:'.preg_replace('/([\,;])/','\\\$1', $summary)."\r\n";
		$ics_str .= 'LOCATION:'.preg_replace('/([\,;])/','\\\$1', $company_name)."\r\n";
		$ics_str .= 'END:VEVENT'."\r\n";
		// close iCal file content
		$ics_str .= "END:VCALENDAR";

		/**
		 * Trigger event to allow third party plugins to overwrite the iCal file.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeCreateMailIcalVikBooking', [$recip, $booking, &$ics_str]);

		if (empty($ics_str)) {
			return false;
		}

		// store the event onto a .ics file. We use the resources folder in back-end.
		$fpath = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $fname;
		$fp = fopen($fpath, 'w+');
		$bytes = fwrite($fp, $ics_str);
		fclose($fp);

		return $bytes ? $fpath : false;
	}
	
	public static function loadEmailTemplate($booking_info = array()) {
		define('_VIKBOOKINGEXEC', '1');
		ob_start();
		include VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "email_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
	
	/**
	 * Parses the raw HTML content of the booking email template.
	 * 
	 * @param 	string 	$tmpl 		the raw content of the template.
	 * @param 	mixed 	$bid 		int for the booking ID or booking array.
	 * @param 	array 	$rooms 		list of rooms booked and translated.
	 * @param 	array 	$rates 		list of translated rates for the booked rooms.
	 * @param 	array 	$options 	list of translated options booked.
	 * @param 	float 	[$total] 	the booking total amount (in case it has changed).
	 * @param 	string 	[$link] 	the booking link can be passed for the no-deposit.
	 * 
	 * @return 	string 	the HTML content of the parsed email template.
	 * 
	 * @since 	1.13 with different arguments.
	 */
	public static function parseEmailTemplate($tmpl, $bid, $rooms, $rates, $options, $total = 0, $link = null)
	{
		$app = JFactory::getApplication();
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();

		// availability helper
		$av_helper = self::getAvailabilityInstance();

		// get necessary values
		if (is_array($bid)) {
			// we got the full booking record
			$order_info = $bid;
			$bid = $order_info['id'];
		} else {
			$order_info = array();
			$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=" . (int)$bid . ";";
			$dbo->setQuery($q);
			$order_info = $dbo->loadAssoc();
			if (!$order_info) {
				throw new Exception('Booking not found', 404);
			}
		}

		$tars_info = array();
		$q = "SELECT `or`.`id`,`or`.`idroom`,`or`.`idtar`,`d`.`idprice` FROM `#__vikbooking_ordersrooms` AS `or` LEFT JOIN `#__vikbooking_dispcost` AS `d` ON `or`.`idtar`=`d`.`id` WHERE `or`.`idorder`=" . (int)$order_info['id'] . ";";
		$dbo->setQuery($q);
		$tars_info = $dbo->loadAssocList();
		if (!$tars_info) {
			throw new Exception('No rooms found', 404);
		}

		/**
		 * Split stay reservation.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$room_stay_dates = [];
		if ($order_info['split_stay']) {
			if ($order_info['status'] == 'confirmed') {
				$room_stay_dates = $av_helper->loadSplitStayBusyRecords($order_info['id']);
			} else {
				$room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $order_info['id'], []);
			}
			// immediately count the number of nights of stay for each split room
			foreach ($room_stay_dates as $sps_r_k => $sps_r_v) {
				if (!empty($sps_r_v['checkin_ts']) && !empty($sps_r_v['checkout_ts'])) {
					// overwrite values for compatibility with non-confirmed bookings
					$sps_r_v['checkin'] = $sps_r_v['checkin_ts'];
					$sps_r_v['checkout'] = $sps_r_v['checkout_ts'];
				}
				$sps_r_v['nights'] = $av_helper->countNightsOfStay($sps_r_v['checkin'], $sps_r_v['checkout']);
				// overwrite the whole array
				$room_stay_dates[$sps_r_k] = $sps_r_v;
			}
		}

		// check if the language in use is the same as the one used during the checkout
		$lang = JFactory::getLanguage();
		if (!empty($order_info['lang'])) {
			if ($lang->getTag() != $order_info['lang']) {
				$lang->load('com_vikbooking', (defined('VIKBOOKING_LANG') ? VIKBOOKING_LANG : JPATH_SITE), $order_info['lang'], true);
				if (VBOPlatformDetection::isJoomla()) {
					$lang->load('joomla', JPATH_SITE, $order_info['lang'], true);
				}
			}
			if ($vbo_tn->getDefaultLang() != $order_info['lang']) {
				// force the translation to start because contents should be translated
				$vbo_tn::$force_tolang = $order_info['lang'];
			}
		}

		// values for replacements
		$company_name 	= self::getFrontTitle();
		$currencyname 	= self::getCurrencyName();
		$sitelogo 		= self::getSiteLogo();
		$footermess 	= self::getFooterOrdMail($vbo_tn);
		$dateformat 	= self::getDateFormat();
		$datesep 		= self::getDateSeparator();
		if ($dateformat == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($dateformat == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$create_date = date(str_replace("/", $datesep, $df) . ' H:i', $order_info['ts']);
		$checkin_date = date(str_replace("/", $datesep, $df) . ' H:i', $order_info['checkin']);
		$checkout_date = date(str_replace("/", $datesep, $df) . ' H:i', $order_info['checkout']);
		$customer_info = nl2br($order_info['custdata']);
		$company_logo = '';
		if (!empty($sitelogo) && is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources'. DIRECTORY_SEPARATOR . $sitelogo)) {
			$company_logo = '<img src="' . VBO_ADMIN_URI . 'resources/' . $sitelogo . '" alt="' . $company_name . '" />';
		}
		if ($order_info['status'] == 'cancelled') {
			$confirmnumber = '';
			$status_str = JText::translate('VBCANCELLED');
		} elseif ($order_info['status'] == 'standby') {
			$confirmnumber = '';
			$status_str = JText::translate('VBWAITINGFORPAYMENT');
			if (!empty($order_info['type']) && !strcasecmp($order_info['type'], 'Inquiry')) {
				// this is an inquiry reservation
				$status_str = JText::translate('VBO_INQUIRY_PENDING');
			}
		} else {
			$confirmnumber = $order_info['confirmnumber'];
			$status_str = JText::translate('VBCOMPLETED');
		}
		// booking total amount
		$total = $total === 0 ? (float)$order_info['total'] : (float)$total;
		// booking link
		$use_sid = !empty($order_info['idorderota']) && !empty($order_info['channel']) ? $order_info['idorderota'] : $order_info['sid'];
		if (is_null($link)) {
			$lang_link = !empty($order_info['lang']) ? "&lang={$order_info['lang']}" : '';
			$link = self::externalroute("index.php?option=com_vikbooking&view=booking&sid={$use_sid}&ts={$order_info['ts']}{$lang_link}", false);
		}

		// raw HTML content
		$parsed = $tmpl;

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($order_info, $rooms))
			->parseTokens($parsed);
		//
		
		// special tokens (tags) replacement
		$parsed = str_replace("{logo}", $company_logo, $parsed);
		$parsed = str_replace("{company_name}", $company_name, $parsed);
		$parsed = str_replace("{order_id}", $order_info['id'], $parsed);
		$statusclass = $order_info['status'] == 'confirmed' ? "confirmed" : "standby";
		$statusclass = $order_info['status'] == 'cancelled' ? "cancelled" : $statusclass;
		$parsed = str_replace("{order_status_class}", $statusclass, $parsed);
		$parsed = str_replace("{order_status}", $status_str, $parsed);
		$parsed = str_replace("{order_date}", $create_date, $parsed);

		// PIN Code
		if ($order_info['status'] == 'confirmed' && self::customersPinEnabled()) {
			$cpin = self::getCPinInstance();
			$customer_pin = $cpin->getPinCodeByOrderId($order_info['id']);
			if (!empty($customer_pin)) {
				$customer_info .= '<h3>'.JText::translate('VBYOURPIN').': '.$customer_pin.'</h3>';
			}
		}

		$parsed = str_replace("{customer_info}", $customer_info, $parsed);
		// Confirmation Number
		if (strlen($confirmnumber) > 0) {
			$parsed = str_replace("{confirmnumb}", $confirmnumber, $parsed);
		} else {
			$parsed = preg_replace('#('.preg_quote('{confirmnumb_delimiter}').')(.*)('.preg_quote('{/confirmnumb_delimiter}').')#si', '$1'.' '.'$3', $parsed);
		}
		$parsed = str_replace("{confirmnumb_delimiter}", "", $parsed);
		$parsed = str_replace("{/confirmnumb_delimiter}", "", $parsed);

		$roomsnum = count($rooms);
		$parsed = str_replace("{rooms_count}", $roomsnum, $parsed);
		$roomstr = "";
		// Rooms Distinctive Features
		preg_match_all('/\{roomfeature ([a-zA-Z0-9 ]+)\}/U', $parsed, $matches);
		//
		foreach ($rooms as $num => $r) {
			$roomstr .= "<strong>".$r['name']."</strong> ".$r['adults']." ".($r['adults'] > 1 ? JText::translate('VBMAILADULTS') : JText::translate('VBMAILADULT')).($r['children'] > 0 ? ", ".$r['children']." ".($r['children'] > 1 ? JText::translate('VBMAILCHILDREN') : JText::translate('VBMAILCHILD')) : "")."<br/>";
			// Rooms Distinctive Features
			if (is_array($matches[1]) && count($matches[1])) {
				$distinctive_features = array();
				$rparams = (array)json_decode($r['params'], true);
				if (array_key_exists('features', $rparams) && count($rparams['features']) > 0 && array_key_exists('roomindex', $r) && !empty($r['roomindex']) && array_key_exists($r['roomindex'], $rparams['features'])) {
					$distinctive_features = $rparams['features'][$r['roomindex']];
				}
				$docheck = (count($distinctive_features) > 0);
				foreach ($matches[1] as $reqf) {
					$feature_found = false;
					if ($docheck) {
						foreach ($distinctive_features as $dfk => $dfv) {
							if (stripos($dfk, $reqf) !== false) {
								$feature_found = $dfk;
								if (strlen(trim($dfk)) == strlen(trim($reqf))) {
									break;
								}
							}
						}
					}
					if ($feature_found !== false && strlen($distinctive_features[$feature_found]) > 0) {
						$roomstr .= JText::translate($feature_found).': '.$distinctive_features[$feature_found].'<br/>';
					}
					$parsed = str_replace("{roomfeature ".$reqf."}", "", $parsed);
				}
			}
		}

		// custom fields replace
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $cmatches);
		if (is_array($cmatches[1]) && count($cmatches[1])) {
			$cfids = array();
			foreach ($cmatches[1] as $cfid ) {
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$cfields = $dbo->loadAssocList();
			$vbo_tn->translateContents($cfields, '#__vikbooking_custfields');
			$cfmap = array();
			foreach ($cfields as $cf) {
				$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $order_info['custdata']);
			if (count($partsreceived) > 0) {
				foreach ($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach ($cmatches[1] as $cfid ) {
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}

		$parsed = str_replace("{rooms_info}", $roomstr, $parsed);
		$parsed = str_replace("{checkin_date}", $checkin_date, $parsed);
		$parsed = str_replace("{checkout_date}", $checkout_date, $parsed);

		// order details
		$orderdetails = "";
		$room_loop_ind = isset($rooms[0]) ? 0 : 1;
		foreach ($rooms as $num => $r) {
			$room_id = !empty($r['idroom']) ? $r['idroom'] : $r['id'];
			$use_room_ind = $num - $room_loop_ind;
			$split_stay_str = '';
			$split_stay_info = [];
			if ($order_info['split_stay'] && !empty($room_stay_dates) && isset($room_stay_dates[$use_room_ind]) && $room_stay_dates[$use_room_ind]['idroom'] == $room_id) {
				$split_stay_info[] = $room_stay_dates[$use_room_ind]['nights'] . ' ' . ($room_stay_dates[$use_room_ind]['nights'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY'));
				$split_stay_info[] = date(str_replace("/", $datesep, $df), $room_stay_dates[$use_room_ind]['checkin']) . ' - ' . date(str_replace("/", $datesep, $df), $room_stay_dates[$use_room_ind]['checkout']);
				$split_stay_str .= '<br/>' . implode(', ', $split_stay_info);
			}

			$expdet = explode("\n", isset($rates[$num]) ? $rates[$num] : '-----');
			$faredets = explode(":", $expdet[0]);
			$orderdetails .= '<div class="roombooked"><strong>' . $r['name'] . '</strong>' . $split_stay_str . '<br/>' . $faredets[0];
			if (!empty($expdet[1])) {
				$attrfaredets = explode(":", $expdet[1]);
				if (strlen($attrfaredets[1]) > 0) {
					$orderdetails .= ' - '.$attrfaredets[0].':'.$attrfaredets[1];
				}
			}
			$fareprice = isset($faredets[1]) ? trim(str_replace($currencyname, "", $faredets[1])) : 0;
			$orderdetails .= '<div class="service-amount" style="float: right;"><span>'.$currencyname.' '.self::numberFormat($fareprice).'</span></div></div>';
			// options
			if (isset($options[$num]) && is_array($options[$num]) && count($options[$num]) > 0) {
				foreach ($options[$num] as $oo) {
					$expopts = explode("\n", $oo);
					foreach ($expopts as $optinfo) {
						if (!empty($optinfo)) {
							$splitopt = explode(":", $optinfo);
							$optprice = trim(str_replace($currencyname, "", $splitopt[1]));
							$orderdetails .= '<div class="roomoption"><span>'.$splitopt[0].'</span><div class="service-amount" style="float: right;"><span>'.$currencyname.' '.self::numberFormat($optprice).'</span></div></div>';
						}
					}
				}
			}
			//
			if ($roomsnum > 1 && $num < $roomsnum) {
				$orderdetails .= '<br/>';
			}
		}
		//
		// coupon
		if (!empty($order_info['coupon'])) {
			$expcoupon = explode(";", $order_info['coupon']);
			$orderdetails .= '<br/><div class="discount"><span>'.JText::translate('VBCOUPON').' '.$expcoupon[2].'</span><div class="service-amount" style="float: right;"><span>- '.$currencyname.' '.self::numberFormat($expcoupon[1]).'</span></div></div>';
		}
		//
		// discount payment method
		if ($order_info['status'] != 'cancelled') {
			$idpayment = $order_info['idpayment'];
			if (!empty($idpayment)) {
				$exppay = explode('=', $idpayment);
				$payment = self::getPayment($exppay[0], $vbo_tn);
				if (is_array($payment)) {
					if ($payment['charge'] > 0.00 && $payment['ch_disc'] != 1) {
						// Discount (not charge)
						if ($payment['val_pcent'] == 1) {
							// fixed value
							$total -= $payment['charge'];
							$orderdetails .= '<br/><div class="discount"><span>'.$payment['name'].'</span><div class="service-amount" style="float: right;"><span>- '.$currencyname.' '.self::numberFormat($payment['charge']).'</span></div></div>';
						} else {
							// percent value
							$percent_disc = $total * $payment['charge'] / 100;
							$total -= $percent_disc;
							$orderdetails .= '<br/><div class="discount"><span>'.$payment['name'].'</span><div class="service-amount" style="float: right;"><span>- '.$currencyname.' '.self::numberFormat($percent_disc).'</span></div></div>';
						}
					}
				}
			}
		}
		//
		$parsed = str_replace("{order_details}", $orderdetails, $parsed);
		//
		$parsed = str_replace("{order_total}", $currencyname.' '.self::numberFormat($total), $parsed);
		$parsed = str_replace("{footer_emailtext}", $footermess, $parsed);
		$parsed = str_replace("{order_link}", '<a href="'.$link.'">'.$link.'</a>', $parsed);
		$parsed = str_replace("{booking_link}", $link, $parsed);

		// deposit
		$deposit_str = '';
		if (!in_array($order_info['status'], array('confirmed', 'cancelled')) && !self::payTotal() && self::allowDepositFromRates($tars_info)) {
			$percentdeposit = self::getAccPerCent();
			$percentdeposit = self::calcDepositOverride($percentdeposit, $order_info['days']);
			if ($percentdeposit > 0 && self::depositAllowedDaysAdv($order_info['checkin'])) {
				if (self::getTypeDeposit() == "fixed") {
					$deposit_amount = $percentdeposit;
				} else {
					$deposit_amount = $total * $percentdeposit / 100;
				}
				if ($deposit_amount > 0) {
					$deposit_str = '<div class="deposit"><span>'.JText::translate('VBLEAVEDEPOSIT').'</span><div class="service-amount" style="float: right;"><strong>'.$currencyname.' '.self::numberFormat($deposit_amount).'</strong></div></div>';
				}
			}
		}
		$parsed = str_replace("{order_deposit}", $deposit_str, $parsed);
		//
		// Amount Paid - Remaining Balance - Refunded Amount - Cancellation Fee
		$totpaid_str = '';
		if ($order_info['refund'] > 0) {
			$totpaid_str .= '<div class="amountpaid amountrefunded"><span>' . JText::translate('VBO_AMOUNT_REFUNDED') . '</span><div class="service-amount" style="float: right;"><strong>' . $currencyname . ' ' . self::numberFormat($order_info['refund']) . '</strong></div></div>';
		}
		if ($order_info['status'] != 'cancelled') {
			$tot_paid = $order_info['totpaid'];
			$diff_topay = (float)$total - (float)$tot_paid;
			if ((float)$tot_paid > 0) {
				$totpaid_str .= '<div class="amountpaid"><span>'.JText::translate('VBAMOUNTPAID').'</span><div class="service-amount" style="float: right;"><strong>'.$currencyname.' '.self::numberFormat($tot_paid).'</strong></div></div>';
				// only in case the remaining balance is greater than 1 to avoid commissions issues
				if ($diff_topay > 1) {
					$totpaid_str .= '<div class="amountpaid"><span>'.JText::translate('VBTOTALREMAINING').'</span><div class="service-amount" style="float: right;"><strong>'.$currencyname.' '.self::numberFormat($diff_topay).'</strong></div></div>';
				}
			}
		}
		if ($order_info['status'] == 'cancelled' && isset($order_info['canc_fee']) && $order_info['canc_fee'] > 0) {
			$totpaid_str .= '<div class="amountpaid amountcancfee"><span>' . JText::translate('VBO_CANC_FEE') . '</span><div class="service-amount" style="float: right;"><strong>' . $currencyname . ' ' . self::numberFormat($order_info['canc_fee']) . '</strong></div></div>';
		}
		$parsed = str_replace("{order_total_paid}", $totpaid_str, $parsed);

		/**
		 * Language direction (LTR or RTL).
		 * 
		 * @since 	1.16.4 (J) - 1.6.4 (WP)
		 */
		$lang_direction = 'ltr';
		if (!empty($order_info['lang'])) {
			// attempt to define the language direction and replace the special tag
			try {
				$lang_direction = JLanguage::getInstance($order_info['lang'])->isRtl() ? 'rtl' : 'ltr';
			} catch (Throwable $e) {
				// do nothing, default to ltr
				$lang_direction = 'ltr';
			}
			// static map for Arabic and Hebrew
			$lang_direction = !strcasecmp($order_info['lang'], 'ar') || !strcasecmp($order_info['lang'], 'he') ? 'rtl' : $lang_direction;
		}
		$parsed = str_replace("{lang_direction}", $lang_direction, $parsed);
		$parsed = str_replace("{text_natural_direction}", ($lang_direction == 'ltr' ? 'left' : 'right'), $parsed);
		$parsed = str_replace("{text_opposite_direction}", ($lang_direction == 'ltr' ? 'right' : 'left'), $parsed);

		/**
		 * Replace static floating styles depending on language direction (LTR or RTL).
		 * For BC with older email template sources, we need to keep the inline style
		 * for the floating of certain elements, which looks good only on LTR.
		 * 
		 * @since 	1.16.4 (J) - 1.6.4 (WP)
		 */
		if ($lang_direction === 'rtl') {
			// get rid of inline static style declaration because the template uses CSS classes
			$parsed = str_replace("float: right;", '', $parsed);
		}

		return $parsed;
	}

	/**
	 * New method for sending booking email messages
	 * to the guest or to the administrator(s).
	 * 
	 * @param 	int 	$bid 		the booking ID.
	 * @param 	array 	$for 		guest, admin or a custom email address.
	 * @param 	bool 	$send 		whether to send or return the HTML message.
	 * @param 	bool 	$no_config 	if true, no configuration settings will be used for the status.
	 * @param 	string 	$type 		optional type to indicate a booking modification.
	 * 
	 * @return 	mixed 	True or False depending on the result or HTML string for the preview.
	 * 
	 * @since 	1.13 (J) - 1.3 (WP)
	 * @since 	1.16.3 (J) - 1.6.3 (WP) 	added fourth argument $no_config to send the email even if
	 * 										the reservation status is pending and against the configuration.
	 * @since 	1.16.7 (J) - 1.6.7 (WP) 	added argument $type.
	 */
	public static function sendBookingEmail($bid, $for = array(), $send = true, $no_config = false, $type = '')
	{
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();

		$vbo_tn = self::getTranslator();
		$av_helper = self::getAvailabilityInstance();

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=" . (int)$bid . ";";
		$dbo->setQuery($q);
		$booking = $dbo->loadAssoc();
		if (!$booking) {
			return false;
		}

		$result = false;

		// check if the language in use is the same as the one used during the checkout
		$lang = JFactory::getLanguage();
		if (!empty($booking['lang'])) {
			if ($lang->getTag() != $booking['lang']) {
				$lang->load('com_vikbooking', (defined('VIKBOOKING_LANG') ? VIKBOOKING_LANG : JPATH_SITE), $booking['lang'], true);
				if (VBOPlatformDetection::isJoomla()) {
					$lang->load('joomla', JPATH_SITE, $booking['lang'], true);
				}
			}
			if ($vbo_tn->getDefaultLang() != $booking['lang']) {
				// force the translation to start because contents should be translated
				$vbo_tn::$force_tolang = $booking['lang'];
			}
		}

		// load rooms booked
		$q = "SELECT `or`.*,`r`.`id` AS `r_reference_id`,`r`.`name`,`r`.`units`,`r`.`fromadult`,`r`.`toadult`,`r`.`params` FROM `#__vikbooking_ordersrooms` AS `or`,`#__vikbooking_rooms` AS `r` WHERE `or`.`idorder`=" . $booking['id'] . " AND `or`.`idroom`=`r`.`id` ORDER BY `or`.`id` ASC;";
		$dbo->setQuery($q);
		$ordersrooms = $dbo->loadAssocList();
		if (!$ordersrooms) {
			return false;
		}
		$vbo_tn->translateContents($ordersrooms, '#__vikbooking_rooms', array('id' => 'r_reference_id'));

		/**
		 * Split stay reservation.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$room_stay_dates = [];
		if ($booking['split_stay']) {
			if ($booking['status'] == 'confirmed') {
				$room_stay_dates = $av_helper->loadSplitStayBusyRecords($booking['id']);
			} else {
				$room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $booking['id'], []);
			}
			// immediately count the number of nights of stay for each split room
			foreach ($room_stay_dates as $sps_r_k => $sps_r_v) {
				if (!empty($sps_r_v['checkin_ts']) && !empty($sps_r_v['checkout_ts'])) {
					// overwrite values for compatibility with non-confirmed bookings
					$sps_r_v['checkin'] = $sps_r_v['checkin_ts'];
					$sps_r_v['checkout'] = $sps_r_v['checkout_ts'];
				}
				$sps_r_v['nights'] = $av_helper->countNightsOfStay($sps_r_v['checkin'], $sps_r_v['checkout']);
				// overwrite the whole array
				$room_stay_dates[$sps_r_k] = $sps_r_v;
			}
		}

		$rooms = array();
		$tars = array();
		$is_package = !empty($booking['pkg']) ? true : false;
		$ftitle = self::getFrontTitle();
		$currencyname = self::getCurrencyName();
		foreach ($ordersrooms as $kor => $or) {
			$num = $kor + 1;
			$rooms[$num] = $or;
			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				// package or custom cost set from the back-end
				continue;
			}

			// determine the days to consider for the count of the availability
			$room_nights   = $booking['days'];
			$room_checkin  = $booking['checkin'];
			$room_checkout = $booking['checkout'];
			if ($booking['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
				$room_nights   = $room_stay_dates[$kor]['nights'];
				$room_checkin  = $room_stay_dates[$kor]['checkin'];
				$room_checkout = $room_stay_dates[$kor]['checkout'];
			}

			$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=" . (int)$or['idtar'] . ";";
			$dbo->setQuery($q);
			$tar = $dbo->loadAssocList();
			if (!$tar) {
				// tariff not found
				if (self::isAdmin()) {
					VikError::raiseWarning('', JText::translate('VBERRNOFAREFOUND'));
				}
				continue;
			}

			// apply seasonal rates
			$tar = self::applySeasonsRoom($tar, $room_checkin, $room_checkout);

			// different usage
			if ($or['fromadult'] <= $or['adults'] && $or['toadult'] >= $or['adults']) {
				$diffusageprice = self::loadAdultsDiff($or['idroom'], $or['adults']);
				// Occupancy Override
				$occ_ovr = self::occupancyOverrideExists($tar, $or['adults']);
				$diffusageprice = $occ_ovr !== false ? $occ_ovr : $diffusageprice;
				//
				if (is_array($diffusageprice)) {
					// set a charge or discount to the price(s) for the different usage of the room
					foreach ($tar as $kpr => $vpr) {
						$tar[$kpr]['diffusage'] = $or['adults'];
						if ($diffusageprice['chdisc'] == 1) {
							// charge
							if ($diffusageprice['valpcent'] == 1) {
								// fixed value
								$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
								$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
								$tar[$kpr]['diffusagecost'] = "+".$aduseval;
								$tar[$kpr]['room_base_cost'] = $vpr['cost'];
								$tar[$kpr]['cost'] = $vpr['cost'] + $aduseval;
							} else {
								// percentage value
								$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
								$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $tar[$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
								$tar[$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
								$tar[$kpr]['room_base_cost'] = $vpr['cost'];
								$tar[$kpr]['cost'] = $aduseval;
							}
						} else {
							// discount
							if ($diffusageprice['valpcent'] == 1) {
								// fixed value
								$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
								$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
								$tar[$kpr]['diffusagecost'] = "-".$aduseval;
								$tar[$kpr]['room_base_cost'] = $vpr['cost'];
								$tar[$kpr]['cost'] = $vpr['cost'] - $aduseval;
							} else {
								// percentage value
								$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
								$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $tar[$kpr]['days']) * $diffusageprice['value'] / 100) * $tar[$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
								$tar[$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
								$tar[$kpr]['room_base_cost'] = $vpr['cost'];
								$tar[$kpr]['cost'] = $aduseval;
							}
						}
					}
				}
			}
			//
			$tars[$num] = $tar[0];
		}

		$secdiff = $booking['checkout'] - $booking['checkin'];
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
				$maxhmore = self::getHoursMoreRb() * 3600;
				if ($maxhmore >= $newdiff) {
					$daysdiff = floor($daysdiff);
				} else {
					$daysdiff = ceil($daysdiff);
				}
			}
		}

		$isdue = 0;
		$pricestr = array();
		$optstr = array();
		foreach ($ordersrooms as $kor => $or) {
			$num = $kor + 1;

			// determine the days to consider for the count of the availability
			$room_nights   = $booking['days'];
			$room_checkin  = $booking['checkin'];
			$room_checkout = $booking['checkout'];
			if ($booking['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
				$room_nights   = $room_stay_dates[$kor]['nights'];
				$room_checkin  = $room_stay_dates[$kor]['checkin'];
				$room_checkout = $room_stay_dates[$kor]['checkout'];
			}

			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				// package cost or cust_cost may not be inclusive of taxes if prices tax included is off
				$calctar = self::sayPackagePlusIva($or['cust_cost'], $or['cust_idiva']);
				$isdue += $calctar;
				$pricestr[$num] = (!empty($or['pkg_name']) ? $or['pkg_name'] : (!empty($or['otarplan']) ? ucwords($or['otarplan']) : JText::translate('VBOROOMCUSTRATEPLAN'))).": ".$calctar." ".$currencyname;
			} elseif (array_key_exists($num, $tars) && is_array($tars[$num])) {
				$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
				$calctar = self::sayCostPlusIva($display_rate, $tars[$num]['idprice']);
				$tars[$num]['calctar'] = $calctar;
				$isdue += $calctar;
				$pricestr[$num] = self::getPriceName($tars[$num]['idprice'], $vbo_tn) . ": " . $calctar . " " . $currencyname . (!empty($tars[$num]['attrdata']) ? "\n" . self::getPriceAttr($tars[$num]['idprice'], $vbo_tn) . ": " . $tars[$num]['attrdata'] : "");
			}

			if (!empty($or['optionals'])) {
				$stepo = explode(";", $or['optionals']);
				foreach ($stepo as $roptkey => $oo) {
					if (empty($oo)) {
						continue;
					}
					$stept = explode(":", $oo);
					$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
					$dbo->setQuery($q);
					$actopt = $dbo->loadAssocList();
					if ($actopt) {
						$vbo_tn->translateContents($actopt, '#__vikbooking_optionals', array(), array(), (!empty($booking['lang']) ? $booking['lang'] : null));
						$chvar = '';
						if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
							$optagenames = self::getOptionIntervalsAges($actopt[0]['ageintervals']);
							$optagepcent = self::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
							$optageovrct = self::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
							$child_num 	 = self::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
							$optagecosts = self::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
							$agestept = explode('-', $stept[1]);
							$stept[1] = $agestept[0];
							$chvar = $agestept[1];
							if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
								// percentage value of the adults tariff
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
								} else {
									$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
									$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
								}
							} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
								// VBO 1.10 - percentage value of room base cost
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
								} else {
									$display_rate = isset($tars[$num]['room_base_cost']) ? $tars[$num]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost']);
									$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
								}
							}
							$actopt[0]['chageintv'] = $chvar;
							$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
							$actopt[0]['quan'] = $stept[1];
							$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $room_nights * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
						} else {
							$actopt[0]['quan'] = $stept[1];
							// VBO 1.11 - options percentage cost of the room total fee
							if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
								$deftar_basecosts = $or['cust_cost'];
							} else {
								$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
							}
							$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
							//
							$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $room_nights * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
						}
						if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
							$realcost = $actopt[0]['maxprice'];
							if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
								$realcost = $actopt[0]['maxprice'] * $stept[1];
							}
						}
						if ($actopt[0]['perperson'] == 1) {
							$realcost = $realcost * $or['adults'];
						}
						$tmpopr = self::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
						$isdue += $tmpopr;
						$optstr[$num][] = ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'] . ": " . $tmpopr . " " . $currencyname . "\n";
					}
				}
			}

			// custom extra costs
			if (!empty($or['extracosts'])) {
				$cur_extra_costs = json_decode($or['extracosts'], true);
				foreach ($cur_extra_costs as $eck => $ecv) {
					$ecplustax = !empty($ecv['idtax']) ? self::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
					$isdue += $ecplustax;
					$optstr[$num][] = $ecv['name'] . ": " . $ecplustax . " " . $currencyname."\n";
				}
			}
		}

		// force the original total amount if rates have changed
		if (number_format($isdue, 2) != number_format($booking['total'], 2)) {
			$isdue = $booking['total'];
		}

		// mail subject
		$subject = JText::sprintf('VBOMAILSUBJECT', strip_tags($ftitle));
		// $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
		
		// inject the recipient of the message for the template
		$booking['for'] = $for;

		// load template file that will get $booking as variable
		$tmpl = self::loadEmailTemplate($booking);

		// parse email template
		$hmess = self::parseEmailTemplate($tmpl, $booking, $rooms, $pricestr, $optstr, $isdue);
		$hmess = '<html>'."\n".'<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>'."\n".'<body>'.$hmess.'</body>'."\n".'</html>';

		if ($send !== true) {
			// return the content of the email message parsed
			return $hmess;
		}

		// when the message can be sent
		$sendwhen = self::getSendEmailWhen();

		if ($no_config) {
			// force the configuration setting to send the message no matter what's the booking status
			$sendwhen = 1;
		}

		// send the message
		foreach ($for as $who) {
			$use_subject = $subject;
			$recipients = array();
			$attachments = self::addEmailAttachment(null);
			$attach_ical = false;
			$force_replyto = null;
			if (strpos($who, '@') !== false) {
				// send email to custom email address
				array_push($recipients, trim($who));
			} elseif (stripos($who, 'guest') !== false || stripos($who, 'customer') !== false) {
				// send email to the customer
				if ($sendwhen > 1 && $booking['status'] == 'standby') {
					continue;
				}
				array_push($recipients, $booking['custmail']);
				/**
				 * Check whether an iCal should be attached for the customer.
				 * 
				 * @since 	1.12.0
				 */
				$attach_ical = self::getEmailIcal('customer', $booking);
			} elseif (stripos($who, 'admin') !== false) {
				// send email to the administrator(s)
				if ($sendwhen > 1 && $booking['status'] == 'standby') {
					continue;
				}
				$use_subject = $subject . ' #' . $booking['id'];
				if (!strcasecmp($type, 'modified')) {
					$use_subject = JText::sprintf('VBOMODDEDORDER', $booking['id']);
				}
				$adminemail = self::getAdminMail();
				$extra_admin_recipients = self::addAdminEmailRecipient(null);
				if (empty($adminemail) && empty($extra_admin_recipients)) {
					// Prevent Joomla Exceptions that would stop the script execution
					VikError::raiseWarning('', 'The administrator email address is empty. Email message could not be sent.');
					continue;
				}
				if (strpos($adminemail, ',') !== false) {
					// multiple addresses
					$adminemails = explode(',', $adminemail);
					foreach ($adminemails as $am) {
						if (strpos($am, '@') !== false) {
							array_push($recipients, trim($am));
						}
					}
				} else {
					// single address
					array_push($recipients, trim($adminemail));
				}
				
				// merge extra recipients
				$recipients = array_merge($recipients, $extra_admin_recipients);

				// admin should reply to the customer
				$force_replyto = !empty($booking['custmail']) ? $booking['custmail'] : $force_replyto;

				/**
				 * Check whether an iCal should be attached for the admin.
				 * 
				 * @since 	1.2.0
				 */
				$attach_ical = self::getEmailIcal('admin', array(
					'ts' => $booking['ts'],
					'custdata' => $booking['custdata'],
					'checkin' => $booking['checkin'],
					'checkout' => $booking['checkout'],
					'subject' => JText::sprintf('VBNEWORDER', $booking['id']),
				));
			}

			// send the message, recipients should always be an array to support multiple admin addresses

			// get sender e-mail
			$adsendermail = VBOFactory::getConfig()->get('senderemail');

			// init mail data
			$mail = new VBOMailWrapper([
				'sender'      => [$adsendermail, $ftitle],
				'recipient'   => $recipients,
				'bcc'         => self::addAdminEmailRecipient(null, true),
				'reply'       => !empty($force_replyto) ? $force_replyto : $adsendermail,
				'subject'     => $use_subject,
				'content'     => $hmess,
				'attachments' => $attachments,
			]);

			if ($attach_ical !== false && $booking['status'] != 'standby') {
				// attach iCal file
				$mail->addAttachment($attach_ical);
			}

			/**
			 * Trigger event to allow third party plugins to overwrite any aspect of the mail message.
			 * 
			 * @see 	VBOMailWrapper is the $mail object and its setter methods can modify the mail data.
			 * 
			 * @since 	1.15.4 (J) - 1.5.10 (WP)
			 */
			VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeSendBookingMailVikBooking', [$who, $booking, $mail]);

			// send e-mail
			$result = VBOFactory::getPlatform()->getMailer()->send($mail) || $result;

			// unlink iCal file
			if ($attach_ical !== false) {
				@unlink($attach_ical);
			}
		}

		return $result;
	}

	/**
	 * This method allows to add one or more recipient email
	 * addresses for the next queue of email sending for the admin.
	 * This method can be used in the template file for the customer
	 * email to register an additional email address, maybe when a 
	 * specific room-type is booked.
	 * The methods sending the email messages are supposed to call this
	 * method by passing no arguments to obtain the extra addresses set.
	 *
	 * @param 	mixed 	$email 	null, string or array of email address(es).
	 * @param 	bool 	$bcc 	if true, addresses will be used as bcc.
	 * @param 	bool 	$reset 	if true, the queues will be emptied.
	 * 
	 * @return 	array 	the current extra recipients or bcc addresses set.
	 * 
	 * @since 	1.13 (J) - 1.3.0 (WP)
	 * @since 	1.14 (J) - 1.4.0 (WP) added argument $bcc.
	 * @since 	1.16 (J) - 1.6.0 (WP) added argument $reset.
	 */
	public static function addAdminEmailRecipient($email, $bcc = false, $reset = false)
	{
		static $extra_recipients = [];
		static $extra_bcc = [];

		if ($reset) {
			$extra_recipients = [];
			$extra_bcc = [];
		}

		if (!empty($email)) {
			if (is_scalar($email)) {
				if ($bcc) {
					array_push($extra_bcc, $email);
				} else {
					array_push($extra_recipients, $email);
				}
			} else {
				if ($bcc) {
					$extra_bcc = array_merge($extra_bcc, $email);
				} else {
					$extra_recipients = array_merge($extra_recipients, $email);
				}
			}
		}
		
		return $bcc ? array_unique($extra_bcc) : array_unique($extra_recipients);
	}

	/**
	 * This method serves to add one or more attachments to the
	 * next queue of email sending for the admin.
	 * The methods sending the email messages are supposed to call this
	 * method by passing a null argument to obtain the attachments set.
	 *
	 * @param 	mixed 	$file 	null or string with path to file to attach.
	 * @param 	bool 	$reset 	if true, the queue will be emptied.
	 * 
	 * @return 	array 	the current attachments set.
	 * 
	 * @since 	1.14
	 * @since 	1.16 (J) - 1.6.0 (WP) added argument $reset.
	 */
	public static function addEmailAttachment($file, $reset = false)
	{
		static $extra_attachments = [];

		if ($reset) {
			$extra_attachments = [];
		}

		if (!empty($file)) {
			if (is_scalar($file)) {
				array_push($extra_attachments, $file);
			} else {
				$extra_attachments = array_merge($extra_attachments, $file);
			}
		}
		
		return array_unique($extra_attachments);
	}

	/**
	 * This method is called whenever some rooms get booked.
	 * It checks whether the rooms involved have shared calendars.
	 * If some are found, then also such rooms will be occupied.
	 * 
	 * @param 	int 	$bid 		the booking ID.
	 * @param 	array 	$roomids 	the list of rooms booked.
	 * @param 	int 	$checkin 	checkin timestamp.
	 * @param 	int 	$checkout 	checkout timestamp.
	 * 
	 * @return 	boolean true if some other cals were occupied, false otherwise.
	 * 
	 * @since 	1.13 (J) - 1.3.0 (WP)
	 * 
	 * @see 	SynchVikBooking::getRoomsSharedCalsInvolved() in VCM that checks if this method exists.
	 */
	public static function updateSharedCalendars($bid, $roomids = array(), $checkin = 0, $checkout = 0)
	{
		$dbo = JFactory::getDbo();
		$bid = (int)$bid;

		// availability helper
		$av_helper = self::getAvailabilityInstance();

		// check for split stay rooms reservation
		$known_split_stay = false;
		$is_split_stay 	  = 0;

		if (empty($checkin) || empty($checkout)) {
			// get checkin and checkout timestamps from booking
			$q = "SELECT `checkin`, `checkout`, `split_stay` FROM `#__vikbooking_orders` WHERE `id`={$bid};";
			$dbo->setQuery($q);
			$dbo->execute();
			if (!$dbo->getNumRows()) {
				// booking not found
				return false;
			}
			$bdata = $dbo->loadAssoc();
			$checkin = $bdata['checkin'];
			$checkout = $bdata['checkout'];
			$is_split_stay = $bdata['split_stay'];
			// turn flag on
			$known_split_stay = true;
		}

		if (!$known_split_stay) {
			// we need to query the db to access this information
			$q = "SELECT `split_stay` FROM `#__vikbooking_orders` WHERE `id`={$bid}";
			$dbo->setQuery($q, 0, 1);
			$dbo->execute();
			if ($dbo->getNumRows()) {
				$is_split_stay = (int)$dbo->loadResult();
			}
			// turn flag on
			$known_split_stay = true;
		}

		// check split stay room records
		$room_stay_dates = [];
		if ($is_split_stay) {
			$room_stay_dates = $av_helper->loadSplitStayBusyRecords($bid);
			// immediately count the number of nights of stay for each split room
			foreach ($room_stay_dates as $sps_r_k => $sps_r_v) {
				if (!empty($sps_r_v['checkin_ts']) && !empty($sps_r_v['checkout_ts'])) {
					// overwrite values for compatibility with non-confirmed bookings
					$sps_r_v['checkin'] = $sps_r_v['checkin_ts'];
					$sps_r_v['checkout'] = $sps_r_v['checkout_ts'];
				}
				$sps_r_v['nights'] = $av_helper->countNightsOfStay($sps_r_v['checkin'], $sps_r_v['checkout']);
				// overwrite the whole array
				$room_stay_dates[$sps_r_k] = $sps_r_v;
			}
		}

		// get the rooms booked
		if (!count($roomids)) {
			// get the IDs of all rooms booked
			$q = "SELECT `idroom` FROM `#__vikbooking_ordersrooms` WHERE `idorder`={$bid};";
			$dbo->setQuery($q);
			$orr = $dbo->loadAssocList();
			if ($orr) {
				foreach ($orr as $or) {
					array_push($roomids, $or['idroom']);
				}
			}
		}
		if (!count($roomids) || empty($bid)) {
			// unable to proceed
			return false;
		}
		$roomids = array_unique($roomids);

		// build room stay dates map in case of split stay
		$room_split_stay_map = [];
		if ($is_split_stay && !empty($room_stay_dates) && count($room_stay_dates) == count($roomids) && count($roomids) > 1) {
			// we have unique room IDs in a split stay reservation for more than one room
			foreach ($room_stay_dates as $room_stay_date) {
				$room_split_stay_map[$room_stay_date['idroom']] = $room_stay_date;
			}
		}

		// get rooms involved
		$involved = [];
		$involved_stay_map = [];
		$q = "SELECT * FROM `#__vikbooking_calendars_xref` WHERE `mainroom` IN (" . implode(', ', $roomids) . ") OR `childroom` IN (" . implode(', ', $roomids) . ");";
		$dbo->setQuery($q);
		$rooms_found = $dbo->loadAssocList();
		if (!$rooms_found) {
			// no rooms involved that need their calendars updated
			return false;
		}
		foreach ($rooms_found as $rf) {
			if (!in_array($rf['mainroom'], $roomids)) {
				// push room ID
				array_push($involved, $rf['mainroom']);
				// check for specific stay dates in connected room
				if (!empty($rf['childroom']) && isset($room_split_stay_map[$rf['childroom']])) {
					// split stay information available
					$involved_stay_map[$rf['mainroom']] = $room_split_stay_map[$rf['childroom']];
				}
			}
			if (!in_array($rf['childroom'], $roomids)) {
				// push room ID
				array_push($involved, $rf['childroom']);
				// check for specific stay dates in connected room
				if (!empty($rf['mainroom']) && isset($room_split_stay_map[$rf['mainroom']])) {
					// split stay information available
					$involved_stay_map[$rf['childroom']] = $room_split_stay_map[$rf['mainroom']];
				}
			}
		}
		$involved = array_unique($involved);
		if (!count($involved)) {
			// no rooms involved
			return false;
		}

		// turnover seconds
		$turnover_secs = VikBooking::getHoursRoomAvail() * 3600;

		// occupy the calendars for the involved rooms found
		$bids_generated = [];
		foreach ($involved as $rid) {
			// determine the values to use
			$room_checkin  = (int)$checkin;
			$room_checkout = (int)$checkout;
			$room_realback = ($checkout + $turnover_secs);
			if (isset($involved_stay_map[$rid])) {
				// split stay dates available
				$room_checkin  = (int)$involved_stay_map[$rid]['checkin'];
				$room_checkout = (int)$involved_stay_map[$rid]['checkout'];
				$room_realback = ($room_checkout + $turnover_secs);
			}

			// occupy record in room with shared calendar
			$q = "INSERT INTO `#__vikbooking_busy` (`idroom`,`checkin`,`checkout`,`realback`,`sharedcal`) VALUES(" . (int)$rid . ", " . $room_checkin . ", " . $room_checkout . ", " . $room_realback . ", 1);";
			$dbo->setQuery($q);
			$dbo->execute();
			array_push($bids_generated, $dbo->insertid());
		}

		// store busy relations created
		foreach ($bids_generated as $busyid) {
			$q = "INSERT INTO `#__vikbooking_ordersbusy` (`idorder`,`idbusy`) VALUES(" . $bid . ", " . (int)$busyid . ");";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		return true;
	}

	/**
	 * This method is needed whenever a booking gets modified by
	 * adding or removing rooms. This way we reset (remove) all
	 * busy records that were previously stored due to a shared
	 * calendar. The correct relations should then be re-created
	 * by calling updateSharedCalendars().
	 * 
	 * @param 	int 	$bid 	the booking ID.
	 * 
	 * @return 	boolean 		true if some records were cleaned.
	 * 
	 * @see 	updateSharedCalendars() should be called after.
	 * 
	 * @since 	1.3.0
	 */
	public static function cleanSharedCalendarsBusy($bid) {
		$dbo = JFactory::getDbo();
		$bid = (int)$bid;
		// get all the occupied records due to shared calendars for this booking
		$q = "SELECT `b`.`id`, `b`.`idroom`, `b`.`sharedcal`, `ob`.`idorder`, `ob`.`idbusy` FROM `#__vikbooking_busy` AS `b` LEFT JOIN `#__vikbooking_ordersbusy` AS `ob` ON `ob`.`idbusy`=`b`.`id` WHERE `ob`.`idorder`={$bid} AND `b`.`sharedcal`=1;";
		$dbo->setQuery($q);
		$allbusy = $dbo->loadAssocList();
		if (!$allbusy) {
			return false;
		}
		
		$busy_ids = array();
		foreach ($allbusy as $b) {
			// push busy ID to be removed later
			array_push($busy_ids, $b['id']);
			// delete the current busy ID-booking ID relation for this shared calendar
			$q = "DELETE FROM `#__vikbooking_ordersbusy` WHERE `idorder`={$bid} AND `idbusy`={$b['id']};";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		if (count($busy_ids)) {
			// delete all busy records due to shared calendars
			$q = "DELETE FROM `#__vikbooking_busy` WHERE `id` IN (" . implode(', ', $busy_ids) . ");";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		return true;
	}

	public static function sendJutility() {
		//deprecated in VBO 1.10
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='sendjutility';";
		$dbo->setQuery($q);
		$dbo->execute();
		$s = $dbo->loadAssocList();
		return (intval($s[0]['setting']) == 1 ? true : false);
	}

	public static function getCategoryName($idcat, $vbo_tn = null) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`name` FROM `#__vikbooking_categories` WHERE `id`=" . $dbo->quote($idcat) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		$p = $dbo->getNumRows() > 0 ? $dbo->loadAssocList() : array();
		if (is_object($vbo_tn) && count($p) > 0) {
			$vbo_tn->translateContents($p, '#__vikbooking_categories');
		}
		return count($p) > 0 ? $p[0]['name'] : '';
	}

	public static function loadAdultsDiff($idroom, $adults)
	{
		static $obp_map = [];

		$map_sign = "{$idroom}:{$adults}";

		if (array_key_exists($map_sign, $obp_map)) {
			return $obp_map[$map_sign];
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_adultsdiff` WHERE `idroom`=" . (int)$idroom . " AND `adults`=" . (int)$adults;
		$dbo->setQuery($q, 0, 1);
		$obp_diff = $dbo->loadAssoc();
		if ($obp_diff) {
			// cache value and return it
			$obp_map[$map_sign] = $obp_diff;

			return $obp_diff;
		}

		// cache value and return it
		$obp_map[$map_sign] = null;

		return null;
	}

	public static function loadRoomAdultsDiff($idroom)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_adultsdiff` WHERE `idroom`=" . (int)$idroom . " ORDER BY `adults` ASC;";
		$dbo->setQuery($q);
		$diff = $dbo->loadAssocList();
		if ($diff) {
			$roomdiff = [];
			foreach ($diff as $v) {
				$roomdiff[$v['adults']] = $v;
			}

			return $roomdiff;
		}

		return [];
	}

	public static function occupancyOverrideExists($tar, $adults) {
		foreach ($tar as $k => $v) {
			if (is_array($v) && array_key_exists('occupancy_ovr', $v)) {
				if (array_key_exists($adults, $v['occupancy_ovr'])) {
					return $v['occupancy_ovr'][$adults];
				}
			}
		}
		return false;
	}
	
	public static function getChildrenCharges($idroom, $children, $ages, $num_nights) {
		/* charges as percentage amounts of the adults tariff not supported for third parties (only VBO 1.8) */
		$charges = array();
		if (!($children > 0) || !(count($ages) > 0)) {
			return $charges;
		}
		$dbo = JFactory::getDbo();
		$id_options = array();
		$q = "SELECT `id`,`idopt` FROM `#__vikbooking_rooms` WHERE `id`=".(int)$idroom.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$assocs = $dbo->loadAssocList();
			foreach ($assocs as $opts) {
				if (!empty($opts['idopt'])) {
					$r_ido = explode(';', rtrim($opts['idopt']));
					foreach ($r_ido as $ido) {
						if (!empty($ido) && !in_array($ido, $id_options)) {
							$id_options[] = $ido;
						}
					}
				}
			}
		}
		if (count($id_options) > 0) {
			$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id` IN (".implode(", ", $id_options).") AND `ifchildren`=1 AND (LENGTH(`ageintervals`) > 0 OR `ageintervals` IS NOT NULL) LIMIT 1;";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$ageintervals = $dbo->loadAssocList();
				$split_ages = explode(';;', $ageintervals[0]['ageintervals']);
				$age_range = array();
				foreach ($split_ages as $kg => $spage) {
					if (empty($spage)) {
						continue;
					}
					$parts = explode('_', $spage);
					if (strlen($parts[0]) > 0 && intval($parts[1]) > 0 && floatval($parts[2]) > 0) {
						$ind = count($age_range);
						$age_range[$ind]['from'] = intval($parts[0]);
						$age_range[$ind]['to'] = intval($parts[1]);
						//taxes are calculated later in VCM
						//$age_range[$ind]['cost'] = self::sayOptionalsPlusIva((floatval($parts[2]) * $num_nights), $ageintervals[0]['idiva']);
						$age_range[$ind]['cost'] = floatval($parts[2]) * $num_nights;
						$age_range[$ind]['option_str'] = $ageintervals[0]['id'].':1-'.($kg + 1);
					}
				}
				if (count($age_range) > 0) {
					$tot_charge = 0;
					$affected = array();
					$option_str = '';
					foreach ($ages as $age) {
						if (strlen($age) == 0) {
							continue;
						}
						foreach ($age_range as $range) {
							if (intval($age) >= $range['from'] && intval($age) <= $range['to']) {
								$tot_charge += $range['cost'];
								$affected[] = $age;
								$option_str .= $range['option_str'].';';
								break;
							}
						}
					}
					if ($tot_charge > 0) {
						$charges['total'] = $tot_charge;
						$charges['affected'] = $affected;
						$charges['options'] = $option_str;
					}
				}
			}
		}
		
		return $charges;
	}
	
	public static function sortRoomPrices($arr) {
		$newarr = array();
		foreach ($arr as $k => $v) {
			$newarr[$k] = $v['cost'];
		}
		asort($newarr);
		$sorted = array();
		foreach ($newarr as $k => $v) {
			$sorted[$k] = $arr[$k];
		}
		return $sorted;
	}
	
	public static function sortResults($arr) {
		$newarr = array();
		foreach ($arr as $k => $v) {
			$newarr[$k] = $v[0]['cost'];
		}
		asort($newarr);
		$sorted = array();
		foreach ($newarr as $k => $v) {
			$sorted[$k] = $arr[$k];
		}
		return $sorted;
	}
	
	public static function sortMultipleResults($arr) {
		foreach ($arr as $k => $v) {
			$newarr = array();
			foreach ($v as $subk => $subv) {
				$newarr[$subk] = $subv[0]['cost'];
			}
			asort($newarr);
			$sorted = array();
			foreach ($newarr as $nk => $v) {
				$sorted[$nk] = $arr[$k][$nk];
			}
			$arr[$k] = $sorted;
		}
		return $arr;
	}

	/**
	 * Caches a list of promotion IDs that will be skipped when applying the special rates. This is
	 * useful for the VCM's Bulk Actions to register a skip of the promotions for the various OTAs
	 * to avoid duplicate discounts to be applied (room-rate-day level and promotion).
	 * 
	 * @param 	array 	$promos 	optional list of promotion IDs to register in the execution flow.
	 * 
	 * @return 	array 				list of cached promotion IDs (if any).
	 * 
	 * @since 	1.16.4 (J) - 1.6.4 (WP)
	 */
	public static function registerPromotionIds(array $promos = null)
	{
		static $flagged_promos = [];

		if ($promos) {
			// set cached value
			$flagged_promos = $promos;
		}

		return $flagged_promos;
	}

	/**
	 * Getter and setter for seasons cache allowed.
	 * 
	 * @param 	bool 	$enabled 	true or false for setter, null for getter.
	 * 
	 * @return 	bool|null
	 * 
	 * @since 	1.16.5 (J) - 1.6.5 (WP)
	 */
	public static function setSeasonsCache($enabled = null)
	{
		static $seasons_cache = null;

		if ($enabled !== null) {
			// set value
			$seasons_cache = (bool)$enabled;
		}

		return $seasons_cache;
	}

	/**
	 * Applies the seasonal rates over a list of room rate records.
	 * 
	 * @param 	array 	$arr 	list of room rate records.
	 * @param 	int 	$from 	unix timestamp for start date.
	 * @param 	int 	$to 	unix timestamp for end date.
	 * 
	 * @return 	array 			list of manipulated room rate records.
	 */
	public static function applySeasonalPrices(array $arr, $from, $to)
	{
		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();

		$roomschange = [];
		$one = getdate($from);

		// leap years
		if (($one['year'] % 4) == 0 && ($one['year'] % 100 != 0 || $one['year'] % 400 == 0)) {
			$isleap = true;
		} else {
			$isleap = false;
		}

		$baseone = mktime(0, 0, 0, 1, 1, $one['year']);
		$tomidnightone = intval($one['hours']) * 3600;
		$tomidnightone += intval($one['minutes']) * 60;
		$sfrom = $from - $baseone - $tomidnightone;
		$fromdayts = mktime(0, 0, 0, $one['mon'], $one['mday'], $one['year']);
		$two = getdate($to);
		$basetwo = mktime(0, 0, 0, 1, 1, $two['year']);
		$tomidnighttwo = intval($two['hours']) * 3600;
		$tomidnighttwo += intval($two['minutes']) * 60;
		$sto = $to - $basetwo - $tomidnighttwo;

		// leap years, check what dates to manipulate
		if ($isleap) {
			$leapts = mktime(0, 0, 0, 2, 29, $one['year']);
			if ($one[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom -= 86400;
			}
			if ($two[0] > $leapts && $one['year'] == $two['year']) {
				// lower checkin date when in leap year but not for checkout
				$sto -= 86400;
			}
		}

		// check for DST changes to adjust the query values to fetch
		if (date('I', $from) != date('I', mktime($one['hours'], $one['minutes'], $one['seconds'], $one['mon'], ($one['mday'] - 1), $one['year']))) {
			if (date('Y-m-d', $to) == date('Y-m-d', mktime($one['hours'], $one['minutes'], $one['seconds'], $one['mon'], ($one['mday'] + 1), $one['year']))) {
				// we are parsing the day when the DST changed (probably a Sunday)
				if (!date('I', $from)) {
					// DST was just turned off
					$sfrom -= 3600;
				}
			}
		}
		
		// count nights requested
		$booking_nights = 1;
		foreach ($arr as $k => $a) {
			if (!isset($a[0])) {
				continue;
			}
			if (isset($a[0]['booking_nights'])) {
				// this value may be set when displaying pricing calendars
				$booking_nights = $a[0]['booking_nights'];
				break;
			}
			if (isset($a[0]['days'])) {
				$booking_nights = $a[0]['days'];
				break;
			}
		}

		/**
		 * Get a list of promotion IDs that may have been set to avoid duplicate discounts on OTAs.
		 * 
		 * @since 	1.16.4 (J) - 1.6.4 (WP)
		 */
		$skip_promo_ids = self::registerPromotionIds();

		$q = "SELECT * FROM `#__vikbooking_seasons` WHERE (" .
		 ($sto > $sfrom ? "(`from` <= " . $sfrom . " AND `to` >= " . $sto . ") " : "") .
		 ($sto > $sfrom ? "OR (`from` <= " . $sfrom . " AND `to` >= " . $sfrom . ") " : "(`from` <= " . $sfrom . " AND `to` <= " . $sfrom . " AND `from` > `to`) ") .
		 ($sto > $sfrom ? "OR (`from` <= " . $sto . " AND `to` >= " . $sto . ") " : "OR (`from` >= " . $sto . " AND `to` >= " . $sto . " AND `from` > `to`) ") .
		 ($sto > $sfrom ? "OR (`from` >= " . $sfrom . " AND `from` <= " . $sto . " AND `to` >= " . $sfrom . " AND `to` <= " . $sto . ")" : "OR (`from` >= " . $sfrom . " AND `from` > " . $sto . " AND `to` < " . $sfrom . " AND `to` <= " . $sto . " AND `from` > `to`)") .
		 ($sto > $sfrom ? " OR (`from` <= " . $sfrom . " AND `from` <= " . $sto . " AND `to` < " . $sfrom . " AND `to` < " . $sto . " AND `from` > `to`) OR (`from` > " . $sfrom . " AND `from` > " . $sto . " AND `to` >= " . $sfrom . " AND `to` >= " . $sto . " AND `from` > `to`)" : " OR (`from` <= " . $sfrom . " AND `to` >= " . $sfrom . " AND `from` >= " . $sto . " AND `to` > " . $sto . " AND `from` < `to`)") .
		 ($sto > $sfrom ? " OR (`from` >= " . $sfrom . " AND `from` < " . $sto . " AND `to` < " . $sfrom . " AND `to` < " . $sto . " AND `from` > `to`)" : " OR (`from` < " . $sfrom . " AND `to` >= " . $sto . " AND `from` <= " . $sto . " AND `to` < " . $sfrom . " AND `from` < `to`)") .
		 ($sto > $sfrom ? " OR (`from` > " . $sfrom . " AND `from` > " . $sto . " AND `to` >= " . $sfrom . " AND `to` < " . $sto . " AND `from` > `to`)" : " OR (`from` >= " . $sfrom . " AND `from` > " . $sto . " AND `to` > " . $sfrom . " AND `to` > " . $sto . " AND `from` < `to`) OR (`from` < " . $sfrom . " AND `from` < " . $sto . " AND `to` < " . $sfrom . " AND `to` <= " . $sto . " AND `from` < `to`)") .
		 ($sto < $sfrom ? " OR (`from` = 0 AND `to` >= " . $sto . " AND `to` >= " . $sfrom . ")" : '') .
		") ORDER BY `#__vikbooking_seasons`.`promo` ASC;";

		$dbo->setQuery($q);
		$seasons = $dbo->loadAssocList();

		if ($seasons) {
			$vbo_tn->translateContents($seasons, '#__vikbooking_seasons');
			$applyseasons = false;
			$mem = [];
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = [];
				/**
				 * The keys below are all needed to apply the promotions on the room's final cost.
				 * 
				 * @since 	1.13.5
				 */
				$mem[$k]['diffs'] = [];
				$mem[$k]['trans_keys'] = [];
				$mem[$k]['trans_factors'] = [];
				$mem[$k]['trans_affdays'] = [];
			}
			foreach ($seasons as $s) {
				// check if this is a promotion registered for skipping
				if (isset($s['promo']) && $s['promo'] && in_array($s['id'], $skip_promo_ids)) {
					continue;
				}

				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					//VBO 1.7 - do not skip seasons tied to the year for bookings between two years
					if ($one['year'] != $s['year'] && $two['year'] != $s['year']) {
						//VBO 1.9 - tied to the year can be set for prev year (Dec 27 to Jan 3) and booking can be Jan 1 to Jan 3 - do not skip in this case
						if (($one['year'] - $s['year']) != 1 || $s['from'] < $s['to']) {
							continue;
						}
						//VBO 1.9 - tied to 2016 going through Jan 2017: dates of December 2017 should skip this speacial price
						if (($one['year'] - $s['year']) == 1 && $s['from'] > $s['to']) {
							$calc_ends = mktime(0, 0, 0, 1, 1, ($s['year'] + 1)) + $s['to'];
							if ($calc_ends < ($from - $tomidnightone)) {
								continue;
							}
						}
					} elseif ($one['year'] < $s['year'] && $two['year'] == $s['year']) {
						//VBO 1.9 - season tied to the year 2017 accross 2018 and we are parsing dates accross prev year 2016-2017
						if ($s['from'] > $s['to']) {
							continue;
						}
						if (($basetwo + $s['from'] + 86399) > $to) {
							/**
							 * Assuming that we are on 2021, and we are booking a 2-night stay from 30/12 to 01/01. This statement involves
							 * a special price tied to the year 2022 for the night of 31/12 (or near dates), but we are booking the night of
							 * New Year's Eve of 2021, and so the special price pre-prepared for the year after (2022) should be ignored.
							 * 
							 * @since 	1.14.3 (J) - 1.4.3 (WP)
							 */
							continue;
						}
					} elseif ($one['year'] == $s['year'] && $two['year'] > $s['year']) {
						if (($baseone + $s['to'] + 86399) < $from && $s['from'] < $s['to']) {
   							/**
							 * Assuming that we are on 2021, and we are booking a 4-night stay from 29/12 to 02/01. This statement involves
							 * a special price tied to the year 2021 for the night of 01/01 (or near dates), but we are booking the night of
							 * First of the Year of 2022, and so the old special price for the year before (2021) should be ignored.
							 * 
							 * @since 	1.14.3 (J) - 1.4.3 (WP)
							 */
   							continue;
   						}
   					} elseif ($one['year'] == $s['year'] && $two['year'] == $s['year'] && $s['from'] > $s['to']) {
						// season tied to the year 2017 accross 2018 and we are parsing dates at the beginning of 2017 due to beginning loop in 2016 (Rates Overview)
						if (($baseone + $s['from']) > $to) {
							continue;
						}
					}
				}
				//
				$allrooms = explode(",", $s['idrooms']);
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : [];
				$inits = $baseone + $s['from'];
				if ($s['from'] < $s['to']) {
					$ends = $basetwo + $s['to'];
					// check if the inits must be set to the year after
					// ex. Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8 to charge Jan 6,7
					if ($sfrom > $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto <= $s['to'] && $s['from'] < $s['to'] && $sfrom > $sto) {
						$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
						$inits = $tmpbase + $s['from'];
					} elseif ($sfrom >= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						// Season Dec 23 to Dec 29 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom <= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						// Season Dec 30 to Dec 31 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom > $s['from'] && $sfrom > $s['to'] && $sto >= $s['from'] && ($sto >= $s['to'] || $sto <= $s['to']) && $sfrom > $sto) {
						// Season Jan 1 to Jan 2 - Booking Dec 29 to Jan 5
						$inits = $basetwo + $s['from'];
					} elseif ($sfrom > $sto && !empty($s['year']) && ($one['year'] != $s['year'] || $two['year'] != $s['year']) && !($one['year'] == $s['year'] && $two['year'] == $s['year'])) {
						// booking dates across two years for a season tied to a specific year
						if ($one['year'] == $s['year']) {
							$inits = $baseone + $s['from'];
						} else {
							$inits = $basetwo + $s['from'];
						}
						if ($two['year'] == $s['year']) {
							$ends = $basetwo + $s['to'];
						} else {
							$ends = $baseone + $s['to'];
						}
					}
				} else {
					// between 2 years
					if ($baseone < $basetwo) {
						// ex. 29/12/2012 - 14/01/2013
						$ends = $basetwo + $s['to'];
					} else {
						if (($sfrom >= $s['from'] && $sto >= $s['from']) || ($sfrom < $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto > $s['to'])) {
							// ex. 25/12 - 30/12 with init season on 20/12 OR 27/12 for counting 28,29,30/12
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
							$ends = $tmpbase + $s['to'];
						} else {
							// ex. 03/01 - 09/01
							$ends = $basetwo + $s['to'];
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] - 1));
							$inits = $tmpbase + $s['from'];
						}
					}
				}
				
				// leap years
				if ($isleap == true) {
					$infoseason = getdate($inits);
					$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
					// leap years, check what dates to manipulate
					if ($infoseason[0] > $leapts && $infoseason['year'] == $one['year']) {
						/**
						 * Timestamp must be greater than the leap-day of Feb 29th.
						 * It used to be checked for >= $leapts.
						 * 
						 * @since 	July 3rd 2019
						 */
						$inits += 86400;
						if ($s['from'] < $s['to']) {
							// increase season end date only if still on the same leap year
							$ends += 86400;
						}
					}
				}
				
				// promotions
				$promotion = [];
				if ($s['promo'] == 1) {
					$daysadv = (($inits - time()) / 86400);
					$daysadv = $daysadv > 0 ? (int)ceil($daysadv) : 0;
					if (!empty($s['promodaysadv']) && $s['promodaysadv'] > $daysadv) {
						continue;
					} elseif (!empty($s['promolastmin']) && $s['promolastmin'] > 0) {
						$secstocheckin = ($from - time());
						if ($s['promolastmin'] < $secstocheckin) {
							// VBO 1.11 - too many seconds to the check-in date, skip this last minute promotion
							continue;
						}
					}
					if ($s['promominlos'] > 1 && $booking_nights < $s['promominlos']) {
						/**
						 * The minimum length of stay parameter is also taken to exclude the promotion from the calculation.
						 * 
						 * @since 	1.13.5
						 */
						continue;
					}
					$promotion['todaydaysadv'] = $daysadv;
					$promotion['promodaysadv'] = $s['promodaysadv'];
					$promotion['promotxt'] = $s['promotxt'];
				}
				
				// occupancy override
				$occupancy_ovr = !empty($s['occupancy_ovr']) ? json_decode($s['occupancy_ovr'], true) : [];
				
				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && $wdays) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}
				
				// checkin must be after the beginning of the season
				$checkininclok = true;
				if ($s['checkinincl'] == 1) {
					$checkininclok = false;
					if ($s['from'] < $s['to']) {
						if ($sfrom >= $s['from'] && $sfrom <= $s['to']) {
							$checkininclok = true;
						}
					} else {
						if (($sfrom >= $s['from'] && $sfrom > $s['to']) || ($sfrom < $s['from'] && $sfrom <= $s['to'])) {
							$checkininclok = true;
						}
					}
				}
				if ($checkininclok !== true) {
					continue;
				}

				foreach ($arr as $k => $a) {
					// applied only to some types of price
					if ($allprices && !empty($allprices[0])) {
						if (!in_array("-" . $a[0]['idprice'] . "-", $allprices)) {
							continue;
						}
					}
					// applied only to some room types
					if (!in_array("-" . $a[0]['idroom'] . "-", $allrooms)) {
						continue;
					}
					
					// count affected nights of stay
					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a[0]['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						if ($todayts >= $inits && $todayts <= $ends) {
							// week days
							if ($filterwdays == true) {
								$checkwday = getdate($todayts);
								if (in_array($checkwday['wday'], $wdays)) {
									$affdays++;
								}
							} else {
								$affdays++;
							}
							//
						}
					}
					if (!($affdays > 0)) {
						// no nights affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a[0]['cost'] / $a[0]['days'];

					// modification factor object
					$factor = new stdClass;
					
					// calculate new price progressively
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$factor->pcent = 1;
						$pctval = $s['diffcost'];
						if (strlen((string)$s['losoverride'])) {
							// values overrides
							$arrvaloverrides = [];
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists($a[0]['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$factor->type = '-';
							$cpercent = 100 - $pctval;
						}
						$factor->amount = $pctval;
						$newprice = ($dailyprice * $cpercent / 100) * $affdays;
					} else {
						// absolute value
						$factor->pcent = 0;
						$absval = $s['diffcost'];
						if (strlen((string)$s['losoverride'])) {
							// values overrides
							$arrvaloverrides = [];
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists($a[0]['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$newprice = ($dailyprice + $absval) * $affdays;
						} else {
							// discount
							$factor->type = '-';
							$newprice = ($dailyprice - $absval) * $affdays;
						}
						$factor->amount = $absval;
					}
					
					// apply rounding
					$factor->roundmode = $s['roundmode'];
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}
					
					// define the promotion (only if no value overrides set the amount to 0)
					if ($promotion && ((isset($absval) && $absval > 0) || $pctval > 0)) {
						/**
						 * Include the discount information (if any). The cost re-calculated may not be
						 * precise if multiple special prices were applied over the same dates.
						 * 
						 * @since 	1.13
						 */
						if ($s['type'] == 2 && $s['diffcost'] > 0) {
							$promotion['discount'] = [
								'amount' => $s['diffcost'],
								'pcent'	 => (int)($s['val_pcent'] == 2),
							];
						}
						//
						$mem[$k]['promotion'] = $promotion;
					}
					
					// define the occupancy override
					if (array_key_exists($a[0]['idroom'], $occupancy_ovr) && $occupancy_ovr[$a[0]['idroom']]) {
						$mem[$k]['occupancy_ovr'] = $occupancy_ovr[$a[0]['idroom']];
					}

					// push difference generated only if to be applied progressively
					if (!$s['promo'] || ($s['promo'] && !$s['promofinalprice'])) {
						/**
						 * Push the difference generated by this special price for later transliteration of final price,
						 * only if the special price is calculated progressively and not on the final price.
						 * 
						 * @since 	1.13.5
						 */
						array_push($mem[$k]['diffs'], ($newprice - ($dailyprice * $affdays)));
					} elseif ($s['promo'] && $s['promofinalprice'] && $factor->pcent) {
						/**
						 * This is a % promotion to be applied on the final price, so we need to save that this memory key 
						 * will need the transliteration, aka adjusting this new price by applying the charge/discount on
						 * all differences applied by the previous special pricing rules.
						 * 
						 * @since 	1.13.5
						 */
						array_push($mem[$k]['trans_keys'], count($mem[$k]['sum']));
						array_push($mem[$k]['trans_factors'], $factor);
						array_push($mem[$k]['trans_affdays'], $affdays);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
					array_push($roomschange, $a[0]['idroom']);
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && $v['sum']) {
						$newprice = 0;
						$dailyprice = $arr[$k][0]['cost'] / $arr[$k][0]['days'];
						$restdays = $arr[$k][0]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;

						// calculate new final cost
						$redo_rounding = null;
						foreach ($v['sum'] as $sum_index => $add) {
							/**
							 * The application of the various special pricing rules is made in a progressive and cumulative way
							 * by always starting from the room base cost or its average daily cost. However, promotions may need
							 * to be applied on the room final cost, and not in a progresive way. In order to keep the progressive
							 * algorithm, for applying the special prices on the room final cost we need to apply the same promotion
							 * onto the differences generated by all the regular and progressively applied special pricing rules.
							 * 
							 * @since 	1.13.5
							 */
							if (in_array($sum_index, $v['trans_keys']) && $v['diffs']) {
								/**
								 * This progressive price difference must be applied on the room final cost, so we need to
								 * apply the transliteration over the other differences applied by other special prices.
								 */
								$transliterate_key = array_search($sum_index, $v['trans_keys']);
								if ($transliterate_key !== false && isset($v['trans_factors'][$transliterate_key])) {
									// this is the % promotion we are looking for applying it on the final cost
									$factor = $v['trans_factors'][$transliterate_key];
									if (is_object($factor) && $factor->pcent) {
										$final_factor = 0;
										foreach ($v['diffs'] as $diff_index => $prog_diff) {
											$final_factor += $prog_diff * $factor->amount / 100;
										}
										// update rounding
										$redo_rounding = !empty($factor->roundmode) ? $factor->roundmode : $redo_rounding;

										/**
										 * Leverage the final factor depending on how many nights this affected.
										 * 
										 * @since 	1.15.0 (J) - 1.5.0 (WP)
										 */
										if (isset($v['trans_affdays'][$transliterate_key]) && $v['trans_affdays'][$transliterate_key] < $arr[$k][0]['days']) {
											$avg_final_factor = $final_factor / $arr[$k][0]['days'];
											$final_factor = $avg_final_factor * $v['trans_affdays'][$transliterate_key];
										}

										// apply the final transliteration to obtain a value like if it was applied on the room's final cost
										$add = $factor->type == '+' ? ($add + $final_factor) : ($add - $final_factor);
									}
								}
							}

							// apply new price progressively
							$newprice += $add;
						}

						// apply rounding from factor
						if (!empty($redo_rounding)) {
							$newprice = round($newprice, 0, constant($redo_rounding));
						}

						// set promotion (if any)
						if (isset($v['promotion'])) {
							$arr[$k][0]['promotion'] = $v['promotion'];
						}
						
						// set occupancy overrides (if any)
						if (isset($v['occupancy_ovr'])) {
							$arr[$k][0]['occupancy_ovr'] = $v['occupancy_ovr'];
						}
						
						// set new final cost and update nights affected
						$arr[$k][0]['cost'] = $newprice;
						$arr[$k][0]['affdays'] = $v['daysused'];
					}
				}
			}
		}
		
		// week days with no season
		$roomschange = array_unique($roomschange);
		$q = "SELECT * FROM `#__vikbooking_seasons` WHERE ((`from` = 0 AND `to` = 0) OR (`from` IS NULL AND `to` IS NULL));";
		$dbo->setQuery($q);
		$specials = $dbo->loadAssocList();
		if ($specials) {
			$vbo_tn->translateContents($specials, '#__vikbooking_seasons');
			$applyseasons = false;
			unset($mem);
			$mem = [];
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = [];
			}
			foreach ($specials as $s) {
				// check if this is a promotion registered for skipping
				if (isset($s['promo']) && $s['promo'] && in_array($s['id'], $skip_promo_ids)) {
					continue;
				}

				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					if ($one['year'] != $s['year']) {
						continue;
					}
				}

				$allrooms = explode(",", $s['idrooms']);
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : [];
				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && $wdays) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}

				foreach ($arr as $k => $a) {
					// only rooms with no price modifications from seasons
					
					// applied only to some types of price
					if ($allprices && !empty($allprices[0])) {
						if (!in_array("-" . $a[0]['idprice'] . "-", $allprices)) {
							continue;
						}
					}
					
					/**
					 * We should not exclude the rooms that already had a modification of the price through a season
					 * with a dates filter or we risk to get invalid prices by skipping a rule for just some weekdays.
					 * The control " || in_array($a[0]['idroom'], $roomschange)" was removed from the IF below.
					 * 
					 * @since 	1.11
					 */
					if (!in_array("-" . $a[0]['idroom'] . "-", $allrooms)) {
						continue;
					}

					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a[0]['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						// week days
						if ($filterwdays == true) {
							$checkwday = getdate($todayts);
							if (in_array($checkwday['wday'], $wdays)) {
								$affdays++;
							}
						}
					}
					if (!($affdays > 0)) {
						// no nights affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a[0]['cost'] / $a[0]['days'];
					
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$pctval = $s['diffcost'];
						if (strlen((string)$s['losoverride'])) {
							// values overrides
							$arrvaloverrides = [];
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists($a[0]['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$cpercent = 100 - $pctval;
						}
						$newprice = ($dailyprice * $cpercent / 100) * $affdays;
					} else {
						// absolute value
						$absval = $s['diffcost'];
						if (strlen((string)$s['losoverride'])) {
							// values overrides
							$arrvaloverrides = [];
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a[0]['days']) {
											$arrvaloverrides[$a[0]['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (array_key_exists($a[0]['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a[0]['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$newprice = ($dailyprice + $absval) * $affdays;
						} else {
							// discount
							$newprice = ($dailyprice - $absval) * $affdays;
						}
					}
					
					// apply rounding
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && $v['sum']) {
						$newprice = 0;
						$dailyprice = $arr[$k][0]['cost'] / $arr[$k][0]['days'];
						$restdays = $arr[$k][0]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;
						foreach ($v['sum'] as $add) {
							$newprice += $add;
						}
						$arr[$k][0]['cost'] = $newprice;
						$arr[$k][0]['affdays'] = $v['daysused'];
					}
				}
			}
		}
		// end week days with no season

		return $arr;
	}

	/**
	 * Applies the special prices over an array of tariffs.
	 * The function is also used by VCM (>= 1.6.5) with specific arguments.
	 *
	 * @param 	array  		$arr 			array of tariffs taken from the DB
	 * @param 	int  		$from 			start timestamp
	 * @param 	int  		$to 			end timestamp
	 * @param 	array  		$parsed_season 	array of a season to parse (used to render the seasons calendars in back-end and front-end)
	 * @param 	array  		$seasons_dates 	(VBO 1.10) array of seasons with dates filter taken from the DB to avoid multiple queries (VCM)
	 * @param 	array  		$seasons_wdays 	(VBO 1.10) array of seasons with weekdays filter (only) taken from the DB to avoid multiple queries (VCM)
	 *
	 * @return 	array
	 */
	public static function applySeasonsRoom(array $arr, $from, $to, array $parsed_season = [], array $seasons_dates = [], array $seasons_wdays = [])
	{
		static $cached_seasons = [];

		$cache_signature 	   = '';
		$cache_signature_wdays = '';
		if (self::setSeasonsCache() !== false && !$parsed_season && !$seasons_dates && !$seasons_wdays) {
			// enable season records caching
			$cache_signature 	   = "{$from}_{$to}";
			$cache_signature_wdays = "wdays";
		}

		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();

		$roomschange = [];
		$one = getdate($from);

		// leap years
		if ($one['year'] % 4 == 0 && ($one['year'] % 100 != 0 || $one['year'] % 400 == 0)) {
			$isleap = true;
		} else {
			$isleap = false;
		}

		$baseone = mktime(0, 0, 0, 1, 1, $one['year']);
		$tomidnightone = intval($one['hours']) * 3600;
		$tomidnightone += intval($one['minutes']) * 60;
		$sfrom = $from - $baseone - $tomidnightone;
		$fromdayts = mktime(0, 0, 0, $one['mon'], $one['mday'], $one['year']);
		$two = getdate($to);
		$basetwo = mktime(0, 0, 0, 1, 1, $two['year']);
		$tomidnighttwo = intval($two['hours']) * 3600;
		$tomidnighttwo += intval($two['minutes']) * 60;
		$sto = $to - $basetwo - $tomidnighttwo;

		// leap years, check what dates to manipulate
		if ($isleap) {
			$leapts = mktime(0, 0, 0, 2, 29, $one['year']);
			if ($one[0] > $leapts) {
				/**
				 * Timestamp must be greater than the leap-day of Feb 29th.
				 * It used to be checked for >= $leapts.
				 * 
				 * @since 	July 3rd 2019
				 */
				$sfrom -= 86400;
			}
			if ($two[0] > $leapts && $one['year'] == $two['year']) {
				// lower checkin date when in leap year but not for checkout
				$sto -= 86400;
			}
		}

		// check for DST changes to adjust the query values to fetch
		if (date('I', $from) != date('I', mktime($one['hours'], $one['minutes'], $one['seconds'], $one['mon'], ($one['mday'] - 1), $one['year']))) {
			if (date('Y-m-d', $to) == date('Y-m-d', mktime($one['hours'], $one['minutes'], $one['seconds'], $one['mon'], ($one['mday'] + 1), $one['year']))) {
				// we are parsing the day when the DST changed (probably a Sunday)
				if (!date('I', $from)) {
					// DST was just turned off
					$sfrom -= 3600;
				}
			}
		}

		// count nights requested
		$booking_nights = 1;
		foreach ($arr as $k => $a) {
			if (isset($a['booking_nights'])) {
				// this value may be set when displaying pricing calendars
				$booking_nights = $a['booking_nights'];
				break;
			}
			if (isset($a['days'])) {
				$booking_nights = $a['days'];
				break;
			}
		}

		/**
		 * Get a list of promotion IDs that may have been set to avoid duplicate discounts on OTAs.
		 * 
		 * @since 	1.16.4 (J) - 1.6.4 (WP)
		 */
		$skip_promo_ids = self::registerPromotionIds();

		$totseasons = 0;
		if (!$parsed_season && !$seasons_dates) {
			$q = "SELECT * FROM `#__vikbooking_seasons` WHERE (" .
		 	($sto > $sfrom ? "(`from` <= " . $sfrom . " AND `to` >= " . $sto . ") " : "") .
		 	($sto > $sfrom ? "OR (`from` <= " . $sfrom . " AND `to` >= " . $sfrom . ") " : "(`from` <= " . $sfrom . " AND `to` <= " . $sfrom . " AND `from` > `to`) ") .
		 	($sto > $sfrom ? "OR (`from` <= " . $sto . " AND `to` >= " . $sto . ") " : "OR (`from` >= " . $sto . " AND `to` >= " . $sto . " AND `from` > `to`) ") .
		 	($sto > $sfrom ? "OR (`from` >= " . $sfrom . " AND `from` <= " . $sto . " AND `to` >= " . $sfrom . " AND `to` <= " . $sto . ")" : "OR (`from` >= " . $sfrom . " AND `from`>" . $sto . " AND `to`<" . $sfrom . " AND `to` <= " . $sto . " AND `from` > `to`)") .
		 	($sto > $sfrom ? " OR (`from` <= " . $sfrom . " AND `from` <= " . $sto . " AND `to`<" . $sfrom . " AND `to`<" . $sto . " AND `from` > `to`) OR (`from`>" . $sfrom . " AND `from`>" . $sto . " AND `to` >= " . $sfrom . " AND `to` >= " . $sto . " AND `from` > `to`)" : " OR (`from` <= " . $sfrom . " AND `to` >=" . $sfrom . " AND `from` >= " . $sto . " AND `to` > " . $sto . " AND `from` < `to`)") .
		 	($sto > $sfrom ? " OR (`from` >= " . $sfrom . " AND `from` < " . $sto . " AND `to` < " . $sfrom . " AND `to` < " . $sto . " AND `from` > `to`)" : " OR (`from` < " . $sfrom . " AND `to` >=" . $sto . " AND `from` <= " . $sto . " AND `to` < " . $sfrom . " AND `from` < `to`)") .
		 	($sto > $sfrom ? " OR (`from` > " . $sfrom . " AND `from` > " . $sto . " AND `to` >=" . $sfrom . " AND `to` < " . $sto . " AND `from` > `to`)" : " OR (`from` >= " . $sfrom . " AND `from` > " . $sto . " AND `to` > " . $sfrom . " AND `to` > " . $sto . " AND `from` < `to`) OR (`from` < " . $sfrom . " AND `from` < " . $sto . " AND `to` < " . $sfrom . " AND `to` <= " . $sto . " AND `from` < `to`)") . 
		 	($sto < $sfrom ? " OR (`from` = 0 AND `to` >= " . $sto . " AND `to` >= " . $sfrom . ")" : '') .
			") ORDER BY `#__vikbooking_seasons`.`promo` ASC;";

			if ($cache_signature && isset($cached_seasons[$cache_signature])) {
				// avoid making a query
				$seasons = $cached_seasons[$cache_signature];
			} else {
				// get the season records by running the query
				$dbo->setQuery($q);
				$seasons = $dbo->loadAssocList();
			}

			// count total seasons
			$totseasons = $seasons ? count($seasons) : 0;

			if ($cache_signature) {
				// cache season records
				$cached_seasons[$cache_signature] = $seasons;
			}
		}

		if ($totseasons > 0 || $parsed_season || $seasons_dates) {
			if ($totseasons > 0) {
				$seasons = $seasons;
			} elseif ($parsed_season) {
				$seasons = [$parsed_season];
			} else {
				$seasons = $seasons_dates;
			}
			$vbo_tn->translateContents($seasons, '#__vikbooking_seasons');
			$applyseasons = false;
			$mem = [];
			foreach ($arr as $k => $a) {
				$mem[$k]['daysused'] = 0;
				$mem[$k]['sum'] = [];
				$mem[$k]['spids'] = [];
				/**
				 * The keys below are all needed to apply the promotions on the room's final cost.
				 * 
				 * @since 	1.13.5
				 */
				$mem[$k]['diffs'] = [];
				$mem[$k]['trans_keys'] = [];
				$mem[$k]['trans_factors'] = [];
				$mem[$k]['trans_affdays'] = [];
			}
			$affdayslistless = [];
			foreach ($seasons as $s) {
				// double check that the 'from' and 'to' properties are not empty (dates filter), in case VCM passes an array of seasons already taken from the DB
				if (empty($s['from']) && empty($s['to']) && !empty($s['wdays'])) {
					// a season for Jan 1st to Jan 1st (1 day), with NO week-days filter is still accepted
					continue;
				}

				/**
				 * VCM may build a fake season as a "restriction placeholder" if no special prices found.
				 * We need to skip such fake seasons as they do not need any parsing.
				 * 
				 * @since 	1.13
				 */
				if (empty($s['from']) && empty($s['to']) && empty($s['diffcost']) && !isset($s['from'])) {
					continue;
				}

				// check if this is a promotion registered for skipping
				if (isset($s['promo']) && $s['promo'] && in_array($s['id'], $skip_promo_ids)) {
					continue;
				}

				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					//VBO 1.7 - do not skip seasons tied to the year for bookings between two years
					if ($one['year'] != $s['year'] && $two['year'] != $s['year']) {
						//VBO 1.9 - tied to the year can be set for prev year (Dec 27 to Jan 3) and booking can be Jan 1 to Jan 3 - do not skip in this case
						if (($one['year'] - $s['year']) != 1 || $s['from'] < $s['to']) {
							continue;
						}
						//VBO 1.9 - tied to 2016 going through Jan 2017: dates of December 2017 should skip this speacial price
						if (($one['year'] - $s['year']) == 1 && $s['from'] > $s['to']) {
							$calc_ends = mktime(0, 0, 0, 1, 1, ($s['year'] + 1)) + $s['to'];
							if ($calc_ends < ($from - $tomidnightone)) {
								continue;
							}
						}
					} elseif ($one['year'] < $s['year'] && $two['year'] == $s['year']) {
						//VBO 1.9 - season tied to the year 2017 accross 2018 and we are parsing dates accross prev year 2016-2017
						if ($s['from'] > $s['to']) {
							continue;
						}
						if (($basetwo + $s['from'] + 86399) > $to) {
							/**
							 * Assuming that we are on 2021, and we are booking a 2-night stay from 30/12 to 01/01. This statement involves
							 * a special price tied to the year 2022 for the night of 31/12 (or near dates), but we are booking the night of
							 * New Year's Eve of 2021, and so the special price pre-prepared for the year after (2022) should be ignored.
							 * 
							 * @since 	1.14.3 (J) - 1.4.3 (WP)
							 */
							continue;
						}
					} elseif ($one['year'] == $s['year'] && $two['year'] > $s['year']) {
						if (($baseone + $s['to'] + 86399) < $from && $s['from'] < $s['to']) {
   							/**
							 * Assuming that we are on 2021, and we are booking a 4-night stay from 29/12 to 02/01. This statement involves
							 * a special price tied to the year 2021 for the night of 01/01 (or near dates), but we are booking the night of
							 * First of the Year of 2022, and so the old special price for the year before (2021) should be ignored.
							 * 
							 * @since 	1.14.3 (J) - 1.4.3 (WP)
							 */
   							continue;
   						}
   					} elseif ($one['year'] == $s['year'] && $two['year'] == $s['year'] && $s['from'] > $s['to']) {
						// season tied to the year 2017 accross 2018 and we are parsing dates at the beginning of 2017 due to beginning loop in 2016 (Rates Overview)
						if (($baseone + $s['from']) > $to) {
							continue;
						}
					}
				}
				//
				$allrooms = !empty($s['idrooms']) ? explode(",", $s['idrooms']) : [];
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : [];
				$inits = $baseone + $s['from'];
				if ($s['from'] < $s['to']) {
					$ends = $basetwo + $s['to'];
					// check if the inits must be set to the year after
					// ex. Season Jan 6 to Feb 12 - Booking Dec 31 to Jan 8 to charge Jan 6,7
					if ($sfrom > $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto <= $s['to'] && $s['from'] < $s['to'] && $sfrom > $sto) {
						$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
						$inits = $tmpbase + $s['from'];
					} elseif ($sfrom >= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						// Season Dec 23 to Dec 29 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom <= $s['from'] && $sfrom <= $s['to'] && $sto < $s['from'] && $sto < $s['to'] && $sfrom > $sto) {
						// Season Dec 30 to Dec 31 - Booking Dec 29 to Jan 5
						$ends = $baseone + $s['to'];
					} elseif ($sfrom > $s['from'] && $sfrom > $s['to'] && $sto >= $s['from'] && ($sto >= $s['to'] || $sto <= $s['to']) && $sfrom > $sto) {
						// Season Jan 1 to Jan 2 - Booking Dec 29 to Jan 5
						$inits = $basetwo + $s['from'];
					} elseif ($sfrom > $sto && !empty($s['year']) && ($one['year'] != $s['year'] || $two['year'] != $s['year']) && !($one['year'] == $s['year'] && $two['year'] == $s['year'])) {
						// booking dates across two years for a season tied to a specific year
						if ($one['year'] == $s['year']) {
							$inits = $baseone + $s['from'];
						} else {
							$inits = $basetwo + $s['from'];
						}
						if ($two['year'] == $s['year']) {
							$ends = $basetwo + $s['to'];
						} else {
							$ends = $baseone + $s['to'];
						}
					}
				} else {
					// between 2 years
					if ($baseone < $basetwo) {
						// ex. 29/12/2012 - 14/01/2013
						$ends = $basetwo + $s['to'];
					} else {
						if (($sfrom >= $s['from'] && $sto >= $s['from']) || ($sfrom < $s['from'] && $sto >= $s['from'] && $sfrom > $s['to'] && $sto > $s['to'])) {
							// ex. 25/12 - 30/12 with init season on 20/12 OR 27/12 for counting 28,29,30/12
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] + 1));
							$ends = $tmpbase + $s['to'];
						} else {
							// ex. 03/01 - 09/01
							$ends = $basetwo + $s['to'];
							$tmpbase = mktime(0, 0, 0, 1, 1, ($one['year'] - 1));
							$inits = $tmpbase + $s['from'];
						}
					}
				}
				// leap years
				if ($isleap == true) {
					$infoseason = getdate($inits);
					$leapts = mktime(0, 0, 0, 2, 29, $infoseason['year']);
					// leap years, check what dates to manipulate
					if ($infoseason[0] > $leapts && $infoseason['year'] == $one['year']) {
						/**
						 * Timestamp must be greater than the leap-day of Feb 29th.
						 * It used to be checked for >= $leapts.
						 * 
						 * @since 	July 3rd 2019
						 */
						$inits += 86400;
						if ($s['from'] < $s['to']) {
							// increase season end date only if still on the same leap year
							$ends += 86400;
						}
					}
				}

				// promotions
				$promotion = [];
				if ($s['promo'] == 1) {
					$daysadv = (($inits - time()) / 86400);
					$daysadv = $daysadv > 0 ? (int)ceil($daysadv) : 0;
					if (!empty($s['promodaysadv']) && $s['promodaysadv'] > $daysadv) {
						continue;
					} elseif (!empty($s['promolastmin']) && $s['promolastmin'] > 0) {
						$secstocheckin = ($from - time());
						if ($s['promolastmin'] < $secstocheckin) {
							// VBO 1.11 - too many seconds to the check-in date, skip this last minute promotion
							continue;
						}
					}
					if ($s['promominlos'] > 1 && $booking_nights < $s['promominlos']) {
						/**
						 * The minimum length of stay parameter is also taken to exclude the promotion from the calculation.
						 * 
						 * @since 	1.13.5
						 */
						continue;
					}
					$promotion['todaydaysadv'] = $daysadv;
					$promotion['promodaysadv'] = $s['promodaysadv'];
					$promotion['promotxt'] = $s['promotxt'];
				}

				// occupancy override
				$occupancy_ovr = !empty($s['occupancy_ovr']) ? json_decode($s['occupancy_ovr'], true) : [];

				//week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && $wdays) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}

				// checkin must be after the begin of the season
				$checkininclok = true;
				if ($s['checkinincl'] == 1) {
					$checkininclok = false;
					if ($s['from'] < $s['to']) {
						if ($sfrom >= $s['from'] && $sfrom <= $s['to']) {
							$checkininclok = true;
						}
					} else {
						if (($sfrom >= $s['from'] && $sfrom > $s['to']) || ($sfrom < $s['from'] && $sfrom <= $s['to'])) {
							$checkininclok = true;
						}
					}
				}
				if ($checkininclok !== true) {
					continue;
				}

				foreach ($arr as $k => $a) {
					// applied only to some types of price
					if ($allprices && !empty($allprices[0])) {
						// VikBooking 1.6: Price Calendar sets the idprice to -1
						if (!in_array("-" . $a['idprice'] . "-", $allprices) && $a['idprice'] > 0) {
							continue;
						}
					}
					// applied only to some room types
					if (!in_array("-" . $a['idroom'] . "-", $allrooms)) {
						continue;
					}

					if (isset($a['days']) && is_float($a['days'])) {
						// prevent any possible warning with array_key_exists()
						$a['days'] = (int)$a['days'];
					}

					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						if ($todayts >= $inits && $todayts <= $ends) {
							$checkwday = getdate($todayts);
							// week days
							if ($filterwdays == true) {
								if (in_array($checkwday['wday'], $wdays)) {
									if (!isset($arr[$k]['affdayslist'])) {
										$arr[$k]['affdayslist'] = [];
									}
									$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = isset($arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']]) && $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] > 0 ? $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] : 0;
									$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
									$affdayslistless[$s['id']][] = $checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon'];
									$affdays++;
								}
							} else {
								if (!isset($arr[$k]['affdayslist'])) {
									$arr[$k]['affdayslist'] = [];
								}
								$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = isset($arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']]) && $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] > 0 ? $arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] : 0;
								$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
								$affdayslistless[$s['id']][] = $checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon'];
								$affdays++;
							}
							//
						}
					}
					
					if (!($affdays > 0)) {
						// no nights affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a['cost'] / $a['days'];

					// modification factor object
					$factor = new stdClass;
					
					// calculate new price progressively
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$factor->pcent = 1;
						$pctval = $s['diffcost'];
						if (strlen((string)$s['losoverride'])) {
							// values overrides
							$arrvaloverrides = [];
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists($a['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$factor->type = '-';
							$cpercent = 100 - $pctval;
						}
						$factor->amount = $pctval;
						$dailysum = ($dailyprice * $cpercent / 100);
						$newprice = $dailysum * $affdays;
					} else {
						// absolute value
						$factor->pcent = 0;
						$absval = $s['diffcost'];
						if (strlen((string)$s['losoverride'])) {
							// values overrides
							$arrvaloverrides = [];
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists($a['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$factor->type = '+';
							$dailysum = ($dailyprice + $absval);
							$newprice = $dailysum * $affdays;
						} else {
							// discount
							$factor->type = '-';
							$dailysum = ($dailyprice - $absval);
							$newprice = $dailysum * $affdays;
						}
						$factor->amount = $absval;
					}
					
					// apply rounding
					$factor->roundmode = $s['roundmode'];
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					// define the promotion (only if no value overrides set the amount to 0)
					if ($promotion && ((isset($absval) && $absval > 0) || $pctval > 0)) {
						/**
						 * Include the discount information (if any). The cost re-calculated may not be
						 * precise if multiple special prices were applied over the same dates.
						 * 
						 * @since 	1.13
						 */
						if ($s['type'] == 2 && $s['diffcost'] > 0) {
							$promotion['discount'] = [
								'amount' => $s['diffcost'],
								'pcent'	 => (int)($s['val_pcent'] == 2),
							];
						}
						//
						$mem[$k]['promotion'] = $promotion;
					}

					// define the occupancy override
					if (array_key_exists($a['idroom'], $occupancy_ovr) && $occupancy_ovr[$a['idroom']]) {
						$mem[$k]['occupancy_ovr'] = $occupancy_ovr[$a['idroom']];
					}
					
					// affected days list
					if (isset($arr[$k]['affdayslist']) && is_array($arr[$k]['affdayslist'])) {
						foreach ($arr[$k]['affdayslist'] as $affk => $affv) {
							if (isset($affdayslistless[$s['id']]) && in_array($affk, $affdayslistless[$s['id']])) {
								$arr[$k]['affdayslist'][$affk] = !empty($arr[$k]['affdayslist'][$affk]) && $arr[$k]['affdayslist'][$affk] > 0 ? ($arr[$k]['affdayslist'][$affk] - $arr[$k]['origdailycost'] + $dailysum) : ($affv + $dailysum);
							}
						}
					}

					// push special price ID
					if (!in_array($s['id'], $mem[$k]['spids'])) {
						array_push($mem[$k]['spids'], $s['id']);
					}

					// push difference generated only if to be applied progressively
					if (!$s['promo'] || ($s['promo'] && !$s['promofinalprice'])) {
						/**
						 * Push the difference generated by this special price for later transliteration of final price,
						 * only if the special price is calculated progressively and not on the final price.
						 * 
						 * @since 	1.13.5
						 */
						array_push($mem[$k]['diffs'], ($newprice - ($dailyprice * $affdays)));
					} elseif ($s['promo'] && $s['promofinalprice'] && $factor->pcent) {
						/**
						 * This is a % promotion to be applied on the final price, so we need to save that this memory key 
						 * will need the transliteration, aka adjusting this new price by applying the charge/discount on
						 * all differences applied by the previous special pricing rules.
						 * 
						 * @since 	1.13.5
						 */
						array_push($mem[$k]['trans_keys'], count($mem[$k]['sum']));
						array_push($mem[$k]['trans_factors'], $factor);
						array_push($mem[$k]['trans_affdays'], $affdays);
					}

					// push values in memory array
					array_push($mem[$k]['sum'], $newprice);
					$mem[$k]['daysused'] += $affdays;
					array_push($roomschange, $a['idroom']);
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && $v['sum']) {
						$newprice = 0;
						$dailyprice = $arr[$k]['cost'] / $arr[$k]['days'];
						$restdays = $arr[$k]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;

						// calculate new final cost
						$redo_rounding = null;
						foreach ($v['sum'] as $sum_index => $add) {
							/**
							 * The application of the various special pricing rules is made in a progressive and cumulative way
							 * by always starting from the room base cost or its average daily cost. However, promotions may need
							 * to be applied on the room final cost, and not in a progresive way. In order to keep the progressive
							 * algorithm, for applying the special prices on the room final cost we need to apply the same promotion
							 * onto the differences generated by all the regular and progressively applied special pricing rules.
							 * 
							 * @since 	1.13.5
							 */
							if (in_array($sum_index, $v['trans_keys']) && $v['diffs']) {
								/**
								 * This progressive price difference must be applied on the room final cost, so we need to
								 * apply the transliteration over the other differences applied by other special prices.
								 */
								$transliterate_key = array_search($sum_index, $v['trans_keys']);
								if ($transliterate_key !== false && isset($v['trans_factors'][$transliterate_key])) {
									// this is the % promotion we are looking for applying it on the final cost
									$factor = $v['trans_factors'][$transliterate_key];
									if (is_object($factor) && $factor->pcent) {
										$final_factor = 0;
										foreach ($v['diffs'] as $diff_index => $prog_diff) {
											$final_factor += $prog_diff * $factor->amount / 100;
										}
										// update rounding
										$redo_rounding = !empty($factor->roundmode) ? $factor->roundmode : $redo_rounding;

										/**
										 * Leverage the final factor depending on how many nights this affected.
										 * 
										 * @since 	1.15.0 (J) - 1.5.0 (WP)
										 */
										if (isset($v['trans_affdays'][$transliterate_key]) && $v['trans_affdays'][$transliterate_key] < $arr[$k]['days']) {
											$avg_final_factor = $final_factor / $arr[$k]['days'];
											$final_factor = $avg_final_factor * $v['trans_affdays'][$transliterate_key];
										}

										// apply the final transliteration to obtain a value like if it was applied on the room's final cost
										$add = $factor->type == '+' ? ($add + $final_factor) : ($add - $final_factor);
									}
								}
							}

							// apply new price progressively
							$newprice += $add;
						}

						// apply rounding from factor
						if (!empty($redo_rounding)) {
							$newprice = round($newprice, 0, constant($redo_rounding));
						}
						
						// set promotion (if any)
						if (isset($v['promotion'])) {
							$arr[$k]['promotion'] = $v['promotion'];
						}

						// set occupancy overrides (if any)
						if (isset($v['occupancy_ovr'])) {
							$arr[$k]['occupancy_ovr'] = $v['occupancy_ovr'];
						}

						// set new final cost and update nights affected
						$arr[$k]['cost'] = $newprice;
						$arr[$k]['affdays'] = $v['daysused'];
						if (array_key_exists('spids', $v) && $v['spids']) {
							$arr[$k]['spids'] = $v['spids'];
						}
					}
				}
			}
		}
		
		// week days with no season
		$roomschange = array_unique($roomschange);
		$totspecials = 0;
		if (!$seasons_wdays) {
			$q = "SELECT * FROM `#__vikbooking_seasons` WHERE ((`from` = 0 AND `to` = 0) OR (`from` IS NULL AND `to` IS NULL));";

			if ($cache_signature_wdays && isset($cached_seasons[$cache_signature_wdays])) {
				// avoid making a query
				$specials = $cached_seasons[$cache_signature_wdays];
			} else {
				// get the week-days-with-no-season records by running the query
				$dbo->setQuery($q);
				$specials = $dbo->loadAssocList();
			}

			// count records
			$totspecials = $specials ? count($specials) : 0;

			if ($cache_signature_wdays) {
				// cache week-days-with-no-season records
				$cached_seasons[$cache_signature_wdays] = $specials;
			}
		}
		if ($totspecials > 0 || $seasons_wdays) {
			$specials = $totspecials > 0 ? $specials : $seasons_wdays;
			$vbo_tn->translateContents($specials, '#__vikbooking_seasons');
			$applyseasons = false;
			/**
			 * We no longer unset the previous memory of the seasons with dates filters
			 * because we need the responses to be merged. We do it only if not set.
			 * We only keep the property 'spids' but the others should be unset.
			 * 
			 * @since 	1.11
			 */
			if (!isset($mem)) {
				$mem = [];
				foreach ($arr as $k => $a) {
					$mem[$k]['daysused'] = 0;
					$mem[$k]['sum'] = [];
					$mem[$k]['spids'] = [];
				}
			} else {
				foreach ($arr as $k => $a) {
					$mem[$k]['daysused'] = 0;
					$mem[$k]['sum'] = [];
				}
			}
			//
			foreach ($specials as $s) {
				// double check that the 'from' and 'to' properties are empty (only weekdays), in case VCM passes an array of seasons already taken from the DB
				if (!empty($s['from']) || !empty($s['to'])) {
					continue;
				}

				// check if this is a promotion registered for skipping
				if (isset($s['promo']) && $s['promo'] && in_array($s['id'], $skip_promo_ids)) {
					continue;
				}

				// Special Price tied to the year
				if (!empty($s['year']) && $s['year'] > 0) {
					if ($one['year'] != $s['year']) {
						continue;
					}
				}

				$allrooms = !empty($s['idrooms']) ? explode(",", $s['idrooms']) : [];
				$allprices = !empty($s['idprices']) ? explode(",", $s['idprices']) : [];
				// week days
				$filterwdays = !empty($s['wdays']) ? true : false;
				$wdays = $filterwdays == true ? explode(';', $s['wdays']) : '';
				if (is_array($wdays) && $wdays) {
					foreach ($wdays as $kw => $wd) {
						if (strlen($wd) == 0) {
							unset($wdays[$kw]);
						}
					}
				}

				foreach ($arr as $k => $a) {
					// only rooms with no price modifications from seasons
					
					// applied only to some types of price
					if ($allprices && !empty($allprices[0])) {
						// VikBooking 1.6: Price Calendar sets the idprice to -1
						if (!in_array("-" . $a['idprice'] . "-", $allprices) && $a['idprice'] > 0) {
							continue;
						}
					}

					/**
					 * We should not exclude the rooms that already had a modification of the price through a season
					 * with a dates filter or we risk to get invalid prices by skipping a rule for just some weekdays.
					 * The control " || in_array($a['idroom'], $roomschange)" was removed from the IF below.
					 * 
					 * @since 	1.11
					 */
					if (!in_array("-" . $a['idroom'] . "-", $allrooms)) {
						continue;
					}

					if (isset($a['days']) && is_float($a['days'])) {
						// prevent any possible warning with array_key_exists()
						$a['days'] = (int)$a['days'];
					}

					$affdays = 0;
					$season_fromdayts = $fromdayts;
					$is_dst = date('I', $season_fromdayts);
					for ($i = 0; $i < $a['days']; $i++) {
						$todayts = $season_fromdayts + ($i * 86400);
						$is_now_dst = date('I', $todayts);
						if ($is_dst != $is_now_dst) {
							// Daylight Saving Time has changed, check how
							if ((bool)$is_dst === true) {
								$todayts += 3600;
								$season_fromdayts += 3600;
							} else {
								$todayts -= 3600;
								$season_fromdayts -= 3600;
							}
							$is_dst = $is_now_dst;
						}
						// week days
						if ($filterwdays == true) {
							$checkwday = getdate($todayts);
							if (in_array($checkwday['wday'], $wdays)) {
								$arr[$k]['affdayslist'][$checkwday['wday'].'-'.$checkwday['mday'].'-'.$checkwday['mon']] = 0;
								$arr[$k]['origdailycost'] = $a['cost'] / $a['days'];
								$affdays++;
							}
						}
						//
					}

					if (!($affdays > 0)) {
						// no nights affected
						continue;
					}

					// apply the rule
					$applyseasons = true;
					$dailyprice = $a['cost'] / $a['days'];
					
					if (intval($s['val_pcent']) == 2) {
						// percentage value
						$pctval = $s['diffcost'];
						if (strlen((string)$s['losoverride'])) {
							// values overrides
							$arrvaloverrides = [];
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists($a['days'], $arrvaloverrides)) {
								$pctval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$cpercent = 100 + $pctval;
						} else {
							// discount
							$cpercent = 100 - $pctval;
						}
						$dailysum = ($dailyprice * $cpercent / 100);
						$newprice = $dailysum * $affdays;
					} else {
						// absolute value
						$absval = $s['diffcost'];
						if (strlen((string)$s['losoverride'])) {
							// values overrides
							$arrvaloverrides = [];
							$valovrparts = explode('_', $s['losoverride']);
							foreach ($valovrparts as $valovr) {
								if (!empty($valovr)) {
									$ovrinfo = explode(':', $valovr);
									if (strstr($ovrinfo[0], '-i') != false) {
										$ovrinfo[0] = str_replace('-i', '', $ovrinfo[0]);
										if ((int)$ovrinfo[0] < $a['days']) {
											$arrvaloverrides[$a['days']] = $ovrinfo[1];
										}
									}
									$arrvaloverrides[$ovrinfo[0]] = $ovrinfo[1];
								}
							}
							if (isset($a['days']) && array_key_exists($a['days'], $arrvaloverrides)) {
								$absval = $arrvaloverrides[$a['days']];
							}
						}
						if (intval($s['type']) == 1) {
							// charge
							$dailysum = ($dailyprice + $absval);
							$newprice = $dailysum * $affdays;
						} else {
							// discount
							$dailysum = ($dailyprice - $absval);
							$newprice = $dailysum * $affdays;
						}
					}
					
					// apply rounding
					if (!empty($s['roundmode'])) {
						$newprice = round($newprice, 0, constant($s['roundmode']));
					} else {
						$newprice = round($newprice, 2);
					}

					foreach ($arr[$k]['affdayslist'] as $affk => $affv) {
						$arr[$k]['affdayslist'][$affk] = $affv + $dailysum;
					}
					if (!in_array($s['id'], $mem[$k]['spids'])) {
						$mem[$k]['spids'][] = $s['id'];
					}
					$mem[$k]['sum'][] = $newprice;
					$mem[$k]['daysused'] += $affdays;
				}
			}
			if ($applyseasons) {
				foreach ($mem as $k => $v) {
					if ($v['daysused'] > 0 && $v['sum']) {
						$newprice = 0;
						$dailyprice = $arr[$k]['cost'] / $arr[$k]['days'];
						$restdays = $arr[$k]['days'] - $v['daysused'];
						$addrest = $restdays * $dailyprice;
						$newprice += $addrest;
						foreach ($v['sum'] as $add) {
							$newprice += $add;
						}
						$arr[$k]['cost'] = $newprice;
						$arr[$k]['affdays'] = $v['daysused'];
						if (array_key_exists('spids', $v) && $v['spids']) {
							$arr[$k]['spids'] = $v['spids'];
						}
					}
				}
			}
		}
		// end week days with no season

		return $arr;
	}

	public static function getRoomRplansClosingDates($idroom)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_prices` WHERE `closingd` IS NOT NULL;";
		$dbo->setQuery($q);
		$price_records = $dbo->loadAssocList();
		if (!$price_records) {
			return [];
		}

		$closingd = [];
		foreach ($price_records as $prec) {
			if (empty($prec['closingd'])) {
				continue;
			}
			$price_closing = json_decode($prec['closingd'], true);
			if (!$price_closing || !isset($price_closing[$idroom]) || !$price_closing[$idroom]) {
				continue;
			}
			// check expired dates and clean up
			$today_midnight = mktime(0, 0, 0);
			$cleaned = false;
			foreach ($price_closing[$idroom] as $k => $v) {
				if (strtotime($v) < $today_midnight) {
					$cleaned = true;
					unset($price_closing[$idroom][$k]);
				}
			}
			if (!$price_closing[$idroom]) {
				unset($price_closing[$idroom]);
			} elseif ($cleaned === true) {
				// reset array keys for smaller JSON size
				$price_closing[$idroom] = array_values($price_closing[$idroom]);
			}
			if ($cleaned === true) {
				$q = "UPDATE `#__vikbooking_prices` SET `closingd`=".(count($price_closing) > 0 ? $dbo->quote(json_encode($price_closing)) : "NULL")." WHERE `id`=".$prec['id'].";";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			if (!isset($price_closing[$idroom]) || !$price_closing[$idroom]) {
				continue;
			}
			$closingd[$prec['id']] = $price_closing[$idroom];
		}

		return $closingd;
	}

	public static function getRoomRplansClosedInDates($roomids, $checkints, $numnights)
	{
		$dbo = JFactory::getDbo();

		$closingd = [];

		$q = "SELECT * FROM `#__vikbooking_prices` WHERE `closingd` IS NOT NULL;";
		$dbo->setQuery($q);
		$price_records = $dbo->loadAssocList();
		if ($price_records && $roomids) {
			$info_start = getdate($checkints);
			$checkin_midnight = mktime(0, 0, 0, $info_start['mon'], $info_start['mday'], $info_start['year']);
			$all_nights = [];
			for ($i = 0; $i < (int)$numnights; $i++) {
				$next_midnight = mktime(0, 0, 0, $info_start['mon'], ($info_start['mday'] + $i), $info_start['year']);
				$all_nights[] = date('Y-m-d', $next_midnight);
			}
			foreach ($price_records as $prec) {
				if (empty($prec['closingd'])) {
					continue;
				}
				$price_closing = json_decode($prec['closingd'], true);
				if (!$price_closing) {
					continue;
				}
				foreach ($price_closing as $idroom => $rclosedd) {
					if (!in_array($idroom, $roomids) || !is_array($rclosedd)) {
						continue;
					}
					if (!array_key_exists($idroom, $closingd)) {
						$closingd[$idroom] = [];
					}
					foreach ($all_nights as $night) {
						if (in_array($night, $rclosedd)) {
							if (array_key_exists($prec['id'], $closingd[$idroom])) {
								$closingd[$idroom][$prec['id']][] = $night;
							} else {
								$closingd[$idroom][$prec['id']] = [$night];
							}
						}
					}
				}
			}
		}

		return $closingd;
	}

	/**
	 * Tells whether at least one payment method must be selected to check-out.
	 * 
	 * @param 	array 	$rooms_involved 	optional list of room IDs being booked.
	 * 
	 * @return 	bool 						true if at least one payment method is applicable.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP) 	added 1st argument to check the room associations.
	 */
	public static function areTherePayments($rooms_involved = [])
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`, `idrooms` FROM `#__vikbooking_gpayments` WHERE `published`=1;";
		$dbo->setQuery($q);
		$dbo->execute();

		if (!$dbo->getNumRows()) {
			// no published payment options found
			return false;
		}

		$pay_options = $dbo->loadAssocList();

		if (!count($rooms_involved)) {
			// if no filters given, return true
			return true;
		}

		// validate room associations
		foreach ($pay_options as $pay_k => $pay_v) {
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
					unset($pay_options[$pay_k]);
					break;
				}
			}
		}

		return (bool)count($pay_options);
	}

	public static function getPayment($idp, $vbo_tn = null)
	{
		if (empty($idp)) {
			return false;
		}

		if (strstr((string)$idp, '=') !== false) {
			$parts = explode('=', $idp);
			$idp = $parts[0];
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_gpayments` WHERE `id`=" . $dbo->quote($idp) . ";";
		$dbo->setQuery($q);
		$payment = $dbo->loadAssocList();
		if (!$payment) {
			return false;
		}

		if (is_object($vbo_tn)) {
			$vbo_tn->translateContents($payment, '#__vikbooking_gpayments');
		}

		return $payment[0];
	}

	public static function getCronKey()
	{
		return VBOFactory::getConfig()->get('cronkey', '');
	}

	/**
	 * Returns the next invoice number to be used for generating a new invoice/e-invoice.
	 * 
	 * @return 	int
	 * 
	 * @see 	do NOT ever implement caching on this method, as it needs to always query the DB.
	 */
	public static function getNextInvoiceNumber()
	{
		return (VBOFactory::getConfig()->getInt('invoiceinum', 0) + 1);
	}

	public static function getInvoiceNumberSuffix()
	{
		return VBOFactory::getConfig()->get('invoicesuffix', '');
	}

	public static function getInvoiceCompanyInfo()
	{
		return VBOFactory::getConfig()->get('invcompanyinfo', '');
	}

	/**
	 * Gets the number for the next booking receipt generation,
	 * updates the last receipt number used for the later iterations,
	 * stores a new receipt record to keep track of the receipts issued.
	 *
	 * @param 	int 	$bid 		the Booking ID for which we are/want to generating the receipt.
	 * @param 	[int]	$newnum 	the last number used for generating the receipt.
	 *
	 * @return 	int
	 */
	public static function getNextReceiptNumber ($bid, $newnum = false) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='receiptinum';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			//check if this booking has already a receipt, and return that number
			$prev_receipt = array();
			$q = "SELECT * FROM `#__vikbooking_receipts` WHERE `idorder`=".(int)$bid.";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$prev_receipt = $dbo->loadAssoc();
			}
			//update value (receipt generated)
			if ($newnum !== false && $newnum > 0) {
				$s = (int)$newnum;
				if (!(count($prev_receipt) > 0)) {
					$q = "UPDATE `#__vikbooking_config` SET `setting`=".$s." WHERE `param`='receiptinum';";
					$dbo->setQuery($q);
					$dbo->execute();
					//insert the new receipt record
					$q = "INSERT INTO `#__vikbooking_receipts` (`number`,`idorder`,`created_on`) VALUES (".(int)$newnum.", ".(int)$bid.", ".time().");";
					$dbo->setQuery($q);
					$dbo->execute();
				} else {
					//update receipt record
					$q = "UPDATE `#__vikbooking_receipts` SET `number`=".(int)$newnum.",`created_on`=".time()." WHERE `idorder`=".(int)$bid.";";
					$dbo->setQuery($q);
					$dbo->execute();
				}
			}
			//
			return count($prev_receipt) > 0 ? (int)$prev_receipt['number'] : ((int)$s + 1);
		}
		//first execution of the method should create the configuration record
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('receiptinum', '0');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	public static function getReceiptNotes ($newnotes = false) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='receiptnotes';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$s = $dbo->loadResult();
			//update value
			if ($newnotes !== false) {
				$s = $newnotes;
				$q = "UPDATE `#__vikbooking_config` SET `setting`=".$dbo->quote($s)." WHERE `param`='receiptnotes';";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			//
			return $s;
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('receiptnotes', '');";
		$dbo->setQuery($q);
		$dbo->execute();
		return "";
	}

	public static function loadColorTagsRules()
	{
		$color_tag_rules = [
			0 		=> 'VBOCOLORTAGRULECUSTOMCOLOR',
			'pend1' => 'VBWAITINGFORPAYMENT',
			'conf1' => 'VBDBTEXTROOMCLOSED',
			'conf2' => 'VBOCOLORTAGRULECONFTWO',
			'conf3' => 'VBOCOLORTAGRULECONFTHREE',
			'inv1' 	=> 'VBOCOLORTAGRULEINVONE',
			'rcp1' 	=> 'VBOCOLORTAGRULERCPONE',
			'conf4' => 'VBOCOLORTAGRULECONFFOUR',
			'conf5' => 'VBOCOLORTAGRULECONFFIVE',
			'inv2' 	=> 'VBOCOLORTAGRULEINVTWO',
		];

		/**
		 * Trigger event to let third-party plugins override the default booking color tag rules.
		 * 
		 * @since 	1.16.4 (J) - 1.6.4 (WP)
		 */
		VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeLoadColorTagRules', [&$color_tag_rules]);

		return $color_tag_rules;
	}

	public static function loadDefaultColorTags()
	{
		$default_color_tags = [
			[
				'color' => '#9b9b9b',
				'name' => 'VBWAITINGFORPAYMENT',
				'rule' => 'pend1'
			],
			[
				'color' => '#333333',
				'name' => 'VBDBTEXTROOMCLOSED',
				'rule' => 'conf1'
			],
			[
				'color' => '#ff8606',
				'name' => 'VBOCOLORTAGRULECONFTWO',
				'rule' => 'conf2'
			],
			[
				'color' => '#0418c9',
				'name' => 'VBOCOLORTAGRULECONFTHREE',
				'rule' => 'conf3'
			],
			[
				'color' => '#bed953',
				'name' => 'VBOCOLORTAGRULEINVONE',
				'rule' => 'inv1'
			],
			[
				'color' => '#67f5b5',
				'name' => 'VBOCOLORTAGRULERCPONE',
				'rule' => 'rcp1'
			],
			[
				'color' => '#04d2c2',
				'name' => 'VBOCOLORTAGRULECONFFOUR',
				'rule' => 'conf4'
			],
			[
				'color' => '#00b316',
				'name' => 'VBOCOLORTAGRULECONFFIVE',
				'rule' => 'conf5'
			],
			[
				'color' => '#00f323',
				'name' => 'VBOCOLORTAGRULEINVTWO',
				'rule' => 'inv2'
			],
		];

		/**
		 * Trigger event to let third-party plugins override the default color tags.
		 * 
		 * @since 	1.16.4 (J) - 1.6.4 (WP)
		 */
		VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeLoadDefaultColorTags', [&$default_color_tags]);

		return $default_color_tags;
	}

	public static function loadBookingsColorTags()
	{
		$bookings_ctags = VBOFactory::getConfig()->getArray('bookingsctags', []);

		return is_array($bookings_ctags) && $bookings_ctags ? $bookings_ctags : self::loadDefaultColorTags();
	}

	public static function getBestColorContrast($hexcolor)
	{
		$hexcolor = str_replace('#', '', $hexcolor);

		if (empty($hexcolor) || strlen($hexcolor) != 6) {
			return '#000000';
		}

		$r = hexdec(substr($hexcolor, 0, 2));
		$g = hexdec(substr($hexcolor, 2, 2));
		$b = hexdec(substr($hexcolor, 4, 2));

		// Counting the perceptive luminance - human eye favors green color
		// < 0.5 bright colors
		// > 0.5 dark colors

		return (1 - ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255) < 0.5 ? '#000000' : '#ffffff';
	}

	public static function applyBookingColorTag($booking, $tags = [])
	{
		if (!is_array($tags) || !$tags) {
			$tags = self::loadBookingsColorTags();
		}

		if (!empty($booking['colortag'])) {
			$color_tag_arr = json_decode($booking['colortag'], true);
			if (is_array($color_tag_arr) && array_key_exists('color', $color_tag_arr)) {
				$color_tag_arr['fontcolor'] = self::getBestColorContrast($color_tag_arr['color']);
				return $color_tag_arr;
			}
		}

		$dbo = JFactory::getDbo();

		$bid = array_key_exists('idorder', $booking) ? $booking['idorder'] : $booking['id'];
		$docs_data 	  = [];
		$invoice_numb = false;
		$receipt_numb = false;

		if ($booking['status'] == 'confirmed') {
			$q = "SELECT `b`.`id` AS `o_id`, `i`.`id` AS `inv_id`, `i`.`number` AS `inv_number`, `i`.`file_name` AS `inv_file_name`, `r`.`id` AS `rcp_id`, `r`.`number` AS `rcp_number`
				FROM `#__vikbooking_orders` AS `b`
				LEFT JOIN `#__vikbooking_invoices` AS `i` ON `b`.`id`=`i`.`idorder`
				LEFT JOIN `#__vikbooking_receipts` AS `r` ON `b`.`id`=`r`.`idorder`
				WHERE `b`.`id`=" . (int)$bid . ";";
			$dbo->setQuery($q);
			$docs_data = $dbo->loadAssoc();
			if ($docs_data) {
				$invoice_numb = (!empty($docs_data['inv_id']));
				$receipt_numb = (!empty($docs_data['rcp_id']));

				/**
				 * We inject the invoice number or receipt number into the color tag rules.
				 * This is useful for the Tableaux page to show the invoice/receipt number.
				 * 
				 * @since 	1.12 (J) - 1.1.7 (WP)
				 */
				if ($invoice_numb || $receipt_numb) {
					foreach ($tags as &$tval) {
						if ($invoice_numb) {
							$tval['invoice_number'] = $docs_data['inv_number'];
							$tval['invoice_file_name'] = $docs_data['inv_file_name'];
						}
						if ($receipt_numb) {
							$tval['receipt_number'] = $docs_data['rcp_number'];
						}
					}
					// unset last reference
					unset($tval);
				}
			}
		}

		/**
		 * Some OTA bookings using an OTA-Collect business model may look as not entirely
		 * paid when they actually are, due to commissions and pass through taxes. We also
		 * take care of those OTA reservations that include a Virtual Credit Card (Hotel Collect).
		 * 
		 * @since 	1.16.3 (J) - 1.6.3 (WP)
		 */
		$ota_collected_booking 	  = false;
		$hotel_collect_vcc_unpaid = false;
		if ($booking['status'] == 'confirmed' && !empty($booking['idorderota']) && !empty($booking['channel']) && $booking['total'] > 0 && $booking['totpaid'] > 0) {
			if (stripos($booking['channel'], 'airbnb') !== false && ($booking['checkout'] < time() || date('Y-m-d', $booking['checkout']) == date('Y-m-d'))) {
				$ota_collected_booking = true;
			}
			if (!empty($booking['paymentlog']) && stripos($booking['paymentlog'], 'virtual credit card') !== false) {
				// VCC detected, check the history for a payment event
				$history_obj = self::getBookingHistoryInstance()->setBid($bid);
				// list of history types to fetch
				$h_payment_types = [];
				$h_payment_group = $history_obj->getTypeGroups($group = 'GPM');
				if ($h_payment_group && $h_payment_group['types']) {
					// merge default types with 'PU' for the manually set "new amount paid" and with 'RU' (refunded amount updated)
					$h_payment_types = array_unique(array_merge($h_payment_group['types'], ['PU', 'RU']));
				}
				$payment_ev_datas = $history_obj->getEventsWithData($h_payment_types, $callvalid = null, $onlydata = false);
				if (!$payment_ev_datas) {
					// this booking should be flagged as "unpaid" because a payment (manual/online) has never been recorded
					$hotel_collect_vcc_unpaid = true;
				}
			}
		}

		foreach ($tags as $tkey => $tval) {
			if (empty($tval['rule'])) {
				continue;
			}

			/**
			 * Trigger event to let third-party plugins validate the custom rule.
			 * 
			 * @since 	1.16.4 (J) - 1.6.4 (WP)
			 */
			$custom_rule = VBOFactory::getPlatform()->getDispatcher()->filter('onApplyBookingColorTag', [$ota_collected_booking, $hotel_collect_vcc_unpaid, $booking, $docs_data, $tval]);
			if (!empty($custom_rule)) {
				return (array) $custom_rule[0];
			}

			switch ($tval['rule']) {
				case 'pend1':
					// Room is waiting for the payment (locked record)
					if ($booking['status'] == 'standby') {
						$q = "SELECT `id` FROM `#__vikbooking_tmplock` WHERE `idorder`=" . (int)$bid . " AND `until`>=" . time() . ";";
						$dbo->setQuery($q);
						$tmp_lock = $dbo->loadAssoc();
						if ($tmp_lock) {
							$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
							return $tval;
						}
					}
					break;
				case 'conf1':
					// Confirmed (Room Closed)
					if ($booking['status'] == 'confirmed' && $booking['custdata'] == JText::translate('VBDBTEXTROOMCLOSED')) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'conf2':
					// Confirmed (No Rate 0.00/NULL Total)
					if ($booking['status'] == 'confirmed' && (empty($booking['total']) || $booking['total'] <= 0.00 || $booking['total'] === null)) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'conf3':
					// Confirmed (Total > 0 && Total Paid = 0 && No Invoice && No Receipt)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && (empty($booking['totpaid']) || $booking['totpaid'] <= 0.00 || $booking['totpaid'] === null || $hotel_collect_vcc_unpaid) && $invoice_numb === false && $receipt_numb === false) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'inv1':
					// Confirmed + Invoice (Total > 0 && Total Paid = 0 && Invoice Exists)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && (empty($booking['totpaid']) || $booking['totpaid'] <= 0.00 || $booking['totpaid'] === null) && $invoice_numb !== false) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'rcp1':
					// Confirmed + Receipt Issued (Total > 0 && Total Paid = 0 && Receipt Issued)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && (empty($booking['totpaid']) || $booking['totpaid'] <= 0.00 || $booking['totpaid'] === null) && $receipt_numb !== false) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'conf4':
					// Confirmed (Total > 0 && Total Paid > 0 && Total Paid < Total)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && $booking['totpaid'] > 0 && $booking['totpaid'] < $booking['total'] && !$ota_collected_booking) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'conf5':
					// Confirmed (Total > 0 && Total Paid >= Total)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && $booking['totpaid'] > 0 && ($booking['totpaid'] >= $booking['total'] || $ota_collected_booking) && !$hotel_collect_vcc_unpaid) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				case 'inv2':
					// Confirmed + Invoice + Paid (Total > 0 && Total Paid >= Total && Invoice Exists)
					if ($booking['status'] == 'confirmed' && $booking['total'] > 0 && $booking['totpaid'] > 0 && ($booking['totpaid'] >= $booking['total'] || $ota_collected_booking) && $invoice_numb !== false) {
						$tval['fontcolor'] = self::getBestColorContrast($tval['color']);
						return $tval;
					}
					break;
				default:
					// unsupported rule
					break;
			}
		}

		return [];
	}

	public static function getBookingInfoFromID($bid)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_orders` WHERE `id`=" . (int)$bid . ";";
		$dbo->setQuery($q);
		$booking_info = $dbo->loadAssoc();
		if ($booking_info) {
			return $booking_info;
		}

		return [];
	}

	/**
	 * Load booking details for a given list of IDs.
	 * 
	 * @param 	int 	$roomid 			the room integer number.
	 * @param 	array 	$room_bids_pool 	list of booking IDs involved.
	 * 
	 * @return 	array
	 */
	public static function loadRoomIndexesBookings($roomid, array $room_bids_pool)
	{
		$dbo = JFactory::getDbo();

		if (empty($roomid) || !$room_bids_pool) {
			return [];
		}

		$room_features_bookings = [];
		$roomid = (int)$roomid;

		$all_bids = [];
		foreach ($room_bids_pool as $day => $bids) {
			$all_bids = array_merge($all_bids, $bids);
		}
		$all_bids = array_map('intval', array_unique($all_bids));
		$all_bids_str = implode(', ', $all_bids);

		$q = "SELECT `or`.`id`,`or`.`idorder`,`or`.`roomindex` FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idroom`={$roomid} AND `or`.`idorder` IN ({$all_bids_str})";
		$dbo->setQuery($q);
		$rbookings = $dbo->loadAssocList();
		if (!$rbookings) {
			return [];
		}

		foreach ($rbookings as $k => $v) {
			if (empty($v['roomindex'])) {
				continue;
			}
			if (!isset($room_features_bookings[$v['roomindex']])) {
				$room_features_bookings[$v['roomindex']] = [];
			}
			$room_features_bookings[$v['roomindex']][] = $v['idorder'];
		}

		return $room_features_bookings;
	}

	public static function getSendEmailWhen() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='emailsendwhen';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			return intval($cval[0]['setting']) > 1 ? 2 : 1;
		} else {
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('emailsendwhen','1');";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		return 1;
	}

	public static function getMinutesAutoRemove() {
		$dbo = JFactory::getDbo();
		$minar = 0;
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='minautoremove';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$minar = (int)$dbo->loadResult();
		}
		return $minar;
	}

	public static function getSMSAPIClass() {
		$dbo = JFactory::getDbo();
		$cfile = '';
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smsapi';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			$cfile = $cval[0]['setting'];
		}
		return $cfile;
	}

	public static function autoSendSMSEnabled() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smsautosend';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			return intval($cval[0]['setting']) > 0 ? true : false;
		}
		return false;
	}

	public static function getSendSMSTo() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smssendto';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			if (!empty($cval[0]['setting'])) {
				$sto = json_decode($cval[0]['setting'], true);
				if (is_array($sto)) {
					return $sto;
				}
			}
		}
		return array();
	}

	public static function getSendSMSWhen() {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smssendwhen';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			return intval($cval[0]['setting']) > 1 ? 2 : 1;
		}
		return 1;
	}

	public static function getSMSAdminPhone() {
		$dbo = JFactory::getDbo();
		$pnum = '';
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smsadminphone';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			$pnum = $cval[0]['setting'];
		}
		return $pnum;
	}

	public static function getSMSParams($as_array = true) {
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='smsparams';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$cval = $dbo->loadAssocList();
			if (!empty($cval[0]['setting'])) {
				if (!$as_array) {
					return $cval[0]['setting'];
				}
				$sparams = json_decode($cval[0]['setting'], true);
				if (is_array($sparams)) {
					return $sparams;
				}
			}
		}
		return array();
	}

	public static function getSMSTemplate($vbo_tn = null, $booking_status = 'confirmed', $type = 'admin') {
		$dbo = JFactory::getDbo();
		switch (strtolower($booking_status)) {
			case 'standby':
				$status = 'pend';
				break;
			case 'cancelled':
				$status = 'canc';
				break;
			default:
				$status = '';
				break;
		}
		$paramtype = 'sms'.$type.'tpl'.$status;
		$q = "SELECT `id`,`setting` FROM `#__vikbooking_texts` WHERE `param`='".$paramtype."';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() < 1) {
			if ($status == 'canc') {
				//Type cancelled is used by VCM since v1.6.6
				$q = "INSERT INTO `#__vikbooking_texts` (`param`,`exp`,`setting`) VALUES ('".$paramtype."','".($type == 'admin' ? 'Administrator' : 'Customer')." SMS Template (Cancelled)','');";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			return '';
		}
		$ft = $dbo->loadAssocList();
		if (is_object($vbo_tn)) {
			$vbo_tn->translateContents($ft, '#__vikbooking_texts');
		}
		return $ft[0]['setting'];
	}

	public static function getSMSAdminTemplate($vbo_tn = null, $booking_status = 'confirmed') {
		return self::getSMSTemplate($vbo_tn, $booking_status, 'admin');
	}

	public static function getSMSCustomerTemplate($vbo_tn = null, $booking_status = 'confirmed') {
		return self::getSMSTemplate($vbo_tn, $booking_status, 'customer');
	}

	public static function checkPhonePrefixCountry($phone, $country_threecode) {
		$dbo = JFactory::getDbo();
		$phone = str_replace(" ", '', trim($phone));
		$cprefix = '';
		if (!empty($country_threecode)) {
			$q = "SELECT `phone_prefix` FROM `#__vikbooking_countries` WHERE `country_3_code`=".$dbo->quote($country_threecode).";";
			$dbo->setQuery($q);
			$dbo->execute();
			if ($dbo->getNumRows() > 0) {
				$cprefix = $dbo->loadResult();
				$cprefix = str_replace(" ", '', trim($cprefix));
			}
		}
		if (!empty($cprefix)) {
			if (substr($phone, 0, 1) != '+') {
				if (substr($phone, 0, 2) == '00') {
					$phone = '+'.substr($phone, 2);
				} else {
					$phone = $cprefix.$phone;
				}
			}
		}
		return $phone;
	}

	public static function parseAdminSMSTemplate($booking, $booking_rooms, $vbo_tn = null) {
		$tpl = self::getSMSAdminTemplate($vbo_tn, $booking['status']);

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($booking, $booking_rooms))
			->parseTokens($tpl);
		//

		$vbo_df = self::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
		$datesep = self::getDateSeparator();

		$tpl = str_replace('{customer_name}', $booking['customer_name'], $tpl);
		$tpl = str_replace('{booking_id}', $booking['id'], $tpl);
		$tpl = str_replace('{checkin_date}', date(str_replace("/", $datesep, $df), $booking['checkin']), $tpl);
		$tpl = str_replace('{checkout_date}', date(str_replace("/", $datesep, $df), $booking['checkout']), $tpl);
		$tpl = str_replace('{num_nights}', $booking['days'], $tpl);
		if (!empty($booking['confirmnumber'])) {
			$tpl = str_replace("{confirmnumb}", $booking['confirmnumber'], $tpl);
		} else {
			$tpl = str_replace("{confirmnumb}", '', $tpl);
		}
		if (!empty($booking['idorderota'])) {
			$tpl = str_replace("{ota_booking_id}", $booking['idorderota'], $tpl);
		} else {
			$tpl = str_replace("{ota_booking_id}", '', $tpl);
		}

		$rooms_booked = array();
		$rooms_names = array();
		$tot_adults = 0;
		$tot_children = 0;
		$tot_guests = 0;
		foreach ($booking_rooms as $broom) {
			$rooms_names[] = $broom['room_name'];
			if (array_key_exists($broom['room_name'], $rooms_booked)) {
				$rooms_booked[$broom['room_name']] += 1;
			} else {
				$rooms_booked[$broom['room_name']] = 1;
			}
			$tot_adults += (int)$broom['adults'];
			$tot_children += (int)$broom['children'];
			$tot_guests += ((int)$broom['adults'] + (int)$broom['children']);
		}
		$tpl = str_replace('{tot_adults}', $tot_adults, $tpl);
		$tpl = str_replace('{tot_children}', $tot_children, $tpl);
		$tpl = str_replace('{tot_guests}', $tot_guests, $tpl);
		$rooms_booked_quant = array();
		foreach ($rooms_booked as $rname => $quant) {
			$rooms_booked_quant[] = ($quant > 1 ? $quant.' ' : '').$rname;
		}
		$tpl = str_replace('{rooms_booked}', implode(', ', $rooms_booked_quant), $tpl);
		$tpl = str_replace('{rooms_names}', implode(', ', $rooms_names), $tpl);
		$tpl = str_replace('{customer_country}', $booking['country_name'], $tpl);
		$tpl = str_replace('{customer_email}', $booking['custmail'], $tpl);
		$tpl = str_replace('{customer_phone}', $booking['phone'], $tpl);
		$tpl = str_replace('{total}', self::numberFormat($booking['total']), $tpl);
		$tpl = str_replace('{total_paid}', self::numberFormat($booking['totpaid']), $tpl);
		$remaining_bal = $booking['total'] - $booking['totpaid'];
		$tpl = str_replace('{remaining_balance}', self::numberFormat($remaining_bal), $tpl);

		return $tpl;
	}

	public static function parseCustomerSMSTemplate($booking, $booking_rooms, $vbo_tn = null, $force_text = null) {
		$tpl = !empty($force_text) ? $force_text : self::getSMSCustomerTemplate($vbo_tn, $booking['status']);

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($booking, $booking_rooms))
			->parseTokens($tpl);
		//

		$vbo_df = self::getDateFormat();
		$df = $vbo_df == "%d/%m/%Y" ? 'd/m/Y' : ($vbo_df == "%m/%d/%Y" ? 'm/d/Y' : 'Y-m-d');
		$datesep = self::getDateSeparator();

		$tpl = str_replace('{customer_name}', $booking['customer_name'], $tpl);
		$tpl = str_replace('{booking_id}', $booking['id'], $tpl);
		$tpl = str_replace('{checkin_date}', date(str_replace("/", $datesep, $df), $booking['checkin']), $tpl);
		$tpl = str_replace('{checkout_date}', date(str_replace("/", $datesep, $df), $booking['checkout']), $tpl);
		$tpl = str_replace('{num_nights}', $booking['days'], $tpl);
		if (!empty($booking['confirmnumber'])) {
			$tpl = str_replace("{confirmnumb}", $booking['confirmnumber'], $tpl);
		} else {
			$tpl = str_replace("{confirmnumb}", '', $tpl);
		}
		if (!empty($booking['idorderota'])) {
			$tpl = str_replace("{ota_booking_id}", $booking['idorderota'], $tpl);
		} else {
			$tpl = str_replace("{ota_booking_id}", '', $tpl);
		}

		$rooms_booked = array();
		$rooms_names = array();
		$tot_adults = 0;
		$tot_children = 0;
		$tot_guests = 0;
		foreach ($booking_rooms as $broom) {
			$rooms_names[] = $broom['room_name'];
			if (array_key_exists($broom['room_name'], $rooms_booked)) {
				$rooms_booked[$broom['room_name']] += 1;
			} else {
				$rooms_booked[$broom['room_name']] = 1;
			}
			$tot_adults += (int)$broom['adults'];
			$tot_children += (int)$broom['children'];
			$tot_guests += ((int)$broom['adults'] + (int)$broom['children']);
		}
		$tpl = str_replace('{tot_adults}', $tot_adults, $tpl);
		$tpl = str_replace('{tot_children}', $tot_children, $tpl);
		$tpl = str_replace('{tot_guests}', $tot_guests, $tpl);
		$rooms_booked_quant = array();
		foreach ($rooms_booked as $rname => $quant) {
			$rooms_booked_quant[] = ($quant > 1 ? $quant.' ' : '').$rname;
		}
		$tpl = str_replace('{rooms_booked}', implode(', ', $rooms_booked_quant), $tpl);
		$tpl = str_replace('{rooms_names}', implode(', ', $rooms_names), $tpl);
		$tpl = str_replace('{total}', self::numberFormat($booking['total']), $tpl);
		$tpl = str_replace('{total_paid}', self::numberFormat($booking['totpaid']), $tpl);
		$remaining_bal = $booking['total'] - $booking['totpaid'];
		$tpl = str_replace('{remaining_balance}', self::numberFormat($remaining_bal), $tpl);
		$tpl = str_replace('{customer_pin}', $booking['customer_pin'], $tpl);
		
		$use_sid = empty($booking['sid']) && !empty($booking['idorderota']) ? $booking['idorderota'] : $booking['sid'];
		$book_link = JUri::root() . 'index.php?option=com_vikbooking&view=booking&sid=' . $use_sid . '&ts=' . $booking['ts'];

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly 	Rewrite URI for front-end
			 */
			$book_link 	= str_replace(JUri::root(), '', $book_link);
			$model 	= JModel::getInstance('vikbooking', 'shortcodes', 'admin');
			$itemid = $model->best(['booking'], (!empty($booking['lang']) ? $booking['lang'] : null));
			if ($itemid) {
				$book_link = JRoute::rewrite($book_link . "&Itemid={$itemid}", false);
			}
		} else {
			/**
			 * @joomlaonly 	Rewrite URI for front-end
			 */
			$bestitemid = self::findProperItemIdType(['booking'], (!empty($booking['lang']) ? $booking['lang'] : null));
			$uri_parts = [
				'option' => 'com_vikbooking',
				'view' 	 => 'booking',
				'sid' 	 => $use_sid,
				'ts' 	 => $booking['ts'],
			];
			if (!empty($bestitemid) && !empty($booking['lang']) && JFactory::getApplication()->isClient('administrator')) {
				// inject lang in query string for proper link routing
				$uri_parts['lang'] = $booking['lang'];
			}
			$book_link = self::externalroute('index.php?' . http_build_query($uri_parts), false, (!empty($bestitemid) ? $bestitemid : null));
		}

		$tpl = str_replace('{booking_link}', $book_link, $tpl);
		// we use the alias tag {booking_url} to support just the URI unlike {order_link} that includes HTML
		$tpl = str_replace('{booking_url}', $book_link, $tpl);

		return $tpl;
	}

	public static function sendBookingSMS($oid, $skip_send_to = array(), $force_send_to = array(), $force_text = null) {
		$dbo = JFactory::getDbo();
		if (!class_exists('VboApplication')) {
			require_once(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'jv_helper.php');
		}
		$vbo_app = new VboApplication;
		if (empty($oid)) {
			return false;
		}
		$sms_api = self::getSMSAPIClass();
		if (empty($sms_api)) {
			return false;
		}
		if (!is_file(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'smsapi'.DIRECTORY_SEPARATOR.$sms_api)) {
			return false;
		}
		$sms_api_params = self::getSMSParams();
		if (!is_array($sms_api_params) || !(count($sms_api_params) > 0)) {
			return false;
		}
		if (!self::autoSendSMSEnabled() && !(count($force_send_to) > 0)) {
			return false;
		}
		$send_sms_to = self::getSendSMSTo();
		if (!(count($send_sms_to) > 0) && !(count($force_send_to) > 0)) {
			return false;
		}
		$booking = array();
		$q = "SELECT `o`.*,`co`.`idcustomer`,CONCAT_WS(' ',`c`.`first_name`,`c`.`last_name`) AS `customer_name`,`c`.`pin` AS `customer_pin`,`nat`.`country_name` FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_customers_orders` `co` ON `co`.`idorder`=`o`.`id` AND `co`.`idorder`=".(int)$oid." LEFT JOIN `#__vikbooking_customers` `c` ON `c`.`id`=`co`.`idcustomer` LEFT JOIN `#__vikbooking_countries` `nat` ON `nat`.`country_3_code`=`o`.`country` WHERE `o`.`id`=".(int)$oid.";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$booking = $dbo->loadAssoc();
		}
		if (!(count($booking) > 0)) {
			return false;
		}
		if (strtolower($booking['status']) == 'standby' && self::getSendSMSWhen() < 2) {
			return false;
		}
		$booking_rooms = array();
		$q = "SELECT `or`.*,`r`.`name` AS `room_name` FROM `#__vikbooking_ordersrooms` AS `or` LEFT JOIN `#__vikbooking_rooms` `r` ON `r`.`id`=`or`.`idroom` WHERE `or`.`idorder`=".(int)$booking['id'].";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			$booking_rooms = $dbo->loadAssocList();
		}
		$admin_phone = self::getSMSAdminPhone();
		$admin_sendermail = self::getSenderMail();
		$admin_email = self::getAdminMail();
		$f_result = false;
		require_once(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'smsapi'.DIRECTORY_SEPARATOR.$sms_api);
		if ((in_array('admin', $send_sms_to) && !empty($admin_phone) && !in_array('admin', $skip_send_to)) || in_array('admin', $force_send_to)) {
			//SMS for the administrator
			$sms_text = self::parseAdminSMSTemplate($booking, $booking_rooms);
			if (!empty($sms_text)) {
				$sms_obj = new VikSmsApi($booking, $sms_api_params);
				//administrator phone can contain multiple numbers separated by comma or semicolon
				$admin_phones = array();
				if (strpos($admin_phone, ',') !== false) {
					$all_phones = explode(',', $admin_phone);
					foreach ($all_phones as $aph) {
						if (!empty($aph)) {
							$admin_phones[] = trim($aph);
						}
					}
				} elseif (strpos($admin_phone, ';') !== false) {
					$all_phones = explode(';', $admin_phone);
					foreach ($all_phones as $aph) {
						if (!empty($aph)) {
							$admin_phones[] = trim($aph);
						}
					}
				} else {
					$admin_phones[] = $admin_phone;
				}
				foreach ($admin_phones as $admphone) {
					$response_obj = $sms_obj->sendMessage($admphone, $sms_text);
					if ( !$sms_obj->validateResponse($response_obj) ) {
						//notify the administrator via email with the error of the SMS sending
						$vbo_app->sendMail($admin_sendermail, $admin_sendermail, $admin_email, $admin_sendermail, JText::translate('VBOSENDSMSERRMAILSUBJ'), JText::translate('VBOSENDADMINSMSERRMAILTXT')."<br />".$sms_obj->getLog(), true);
					} else {
						$f_result = true;
					}
				}
			}
		}
		if ((in_array('customer', $send_sms_to) && !empty($booking['phone']) && !in_array('customer', $skip_send_to)) || in_array('customer', $force_send_to)) {
			//SMS for the Customer
			$vbo_tn = self::getTranslator();
			$vbo_tn->translateContents($booking_rooms, '#__vikbooking_rooms', array('id' => 'idroom', 'room_name' => 'name'));
			$sms_text = self::parseCustomerSMSTemplate($booking, $booking_rooms, $vbo_tn, $force_text);
			if (!empty($sms_text)) {
				$sms_obj = new VikSmsApi($booking, $sms_api_params);
				$response_obj = $sms_obj->sendMessage($booking['phone'], $sms_text);
				if ( !$sms_obj->validateResponse($response_obj) ) {
					//notify the administrator via email with the error of the SMS sending
					$vbo_app->sendMail($admin_sendermail, $admin_sendermail, $admin_email, $admin_sendermail, JText::translate('VBOSENDSMSERRMAILSUBJ'), JText::translate('VBOSENDCUSTOMERSMSERRMAILTXT')."<br />".$sms_obj->getLog(), true);
				} else {
					$f_result = true;
				}
			}
		}
		return $f_result;
	}

	public static function loadInvoiceTmpl($booking_info = array(), $booking_rooms = array())
	{
		if (!defined('_VIKBOOKINGEXEC')) {
			define('_VIKBOOKINGEXEC', '1');
		}

		ob_start();
		include VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR . 'invoice_tmpl.php';
		$content = ob_get_contents();
		ob_end_clean();

		$default_params = array(
			'show_header' => 0,
			'header_data' => array(),
			'show_footer' => 0,
			'pdf_page_orientation' => 'PDF_PAGE_ORIENTATION',
			'pdf_unit' => 'PDF_UNIT',
			'pdf_page_format' => 'PDF_PAGE_FORMAT',
			'pdf_margin_left' => 'PDF_MARGIN_LEFT',
			'pdf_margin_top' => 'PDF_MARGIN_TOP',
			'pdf_margin_right' => 'PDF_MARGIN_RIGHT',
			'pdf_margin_header' => 'PDF_MARGIN_HEADER',
			'pdf_margin_footer' => 'PDF_MARGIN_FOOTER',
			'pdf_margin_bottom' => 'PDF_MARGIN_BOTTOM',
			'pdf_image_scale_ratio' => 'PDF_IMAGE_SCALE_RATIO',
			'header_font_size' => '10',
			'body_font_size' => '10',
			'footer_font_size' => '8',
			'show_lines_taxrate_col' => 0,
		);

		if (defined('_VIKBOOKING_INVOICE_PARAMS') && isset($invoice_params) && is_array($invoice_params) && count($invoice_params)) {
			$default_params = array_merge($default_params, $invoice_params);
		}

		return array($content, $default_params);
	}

	/**
	 * Includes within a buffer the template file for the custom (manual) invoice.
	 * 
	 * @param 	array 	$invoice 	the record of the custom invoice
	 * @param 	array 	$customer 	the customer record
	 * 
	 * @return 	string 	the HTML content of the template file
	 *
	 * @since 	1.11.1
	 */
	public static function loadCustomInvoiceTmpl($invoice, $customer) {
		if (!defined('_VIKBOOKINGEXEC')) {
			define('_VIKBOOKINGEXEC', '1');
		}
		ob_start();
		include VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . "custom_invoice_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		$default_params = array(
			'show_header' => 0,
			'header_data' => array(),
			'show_footer' => 0,
			'pdf_page_orientation' => 'PDF_PAGE_ORIENTATION',
			'pdf_unit' => 'PDF_UNIT',
			'pdf_page_format' => 'PDF_PAGE_FORMAT',
			'pdf_margin_left' => 'PDF_MARGIN_LEFT',
			'pdf_margin_top' => 'PDF_MARGIN_TOP',
			'pdf_margin_right' => 'PDF_MARGIN_RIGHT',
			'pdf_margin_header' => 'PDF_MARGIN_HEADER',
			'pdf_margin_footer' => 'PDF_MARGIN_FOOTER',
			'pdf_margin_bottom' => 'PDF_MARGIN_BOTTOM',
			'pdf_image_scale_ratio' => 'PDF_IMAGE_SCALE_RATIO',
			'header_font_size' => '10',
			'body_font_size' => '10',
			'footer_font_size' => '8'
		);
		if (defined('_VIKBOOKING_INVOICE_PARAMS') && isset($invoice_params) && is_array($invoice_params) && $invoice_params) {
			$default_params = array_merge($default_params, $invoice_params);
		}
		return array($content, $default_params);
	}

	public static function parseInvoiceTemplate($invoicetpl, $booking, $booking_rooms, $invoice_num, $invoice_suff, $invoice_date, $company_info, $vbo_tn = null, $pdfparams = [])
	{
		// clone the original template file source
		$parsed = $invoicetpl;

		/**
		 * Parse all conditional text rules.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		self::getConditionalRulesInstance()
			->set(array('booking', 'rooms'), array($booking, $booking_rooms))
			->parseTokens($parsed);
		//

		$dbo = JFactory::getDbo();
		if (is_null($vbo_tn)) {
			$vbo_tn = self::getTranslator();
		}
		// availability helper
		$av_helper = self::getAvailabilityInstance();

		$nowdf = self::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator();

		/**
		 * Split stay reservation.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$room_stay_dates = [];
		if ($booking['split_stay']) {
			if ($booking['status'] == 'confirmed') {
				$room_stay_dates = $av_helper->loadSplitStayBusyRecords($booking['id']);
			} else {
				$room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $booking['id'], []);
			}
			// immediately count the number of nights of stay for each split room
			foreach ($room_stay_dates as $sps_r_k => $sps_r_v) {
				if (!empty($sps_r_v['checkin_ts']) && !empty($sps_r_v['checkout_ts'])) {
					// overwrite values for compatibility with non-confirmed bookings
					$sps_r_v['checkin'] = $sps_r_v['checkin_ts'];
					$sps_r_v['checkout'] = $sps_r_v['checkout_ts'];
				}
				$sps_r_v['nights'] = $av_helper->countNightsOfStay($sps_r_v['checkin'], $sps_r_v['checkout']);
				// overwrite the whole array
				$room_stay_dates[$sps_r_k] = $sps_r_v;
			}
		}

		$companylogo = self::getSiteLogo();
		$uselogo = '';
		if (!empty($companylogo)) {
			/**
			 * Let's try to prevent TCPDF errors.
			 * 
			 * @since 		August 2nd 2019
			 */
			if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				$uselogo = '<img src="' . VBO_ADMIN_URI_REL . 'resources/' . $companylogo . '"/>';
			} elseif (is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				$uselogo = '<img src="' . VBO_SITE_URI_REL . 'resources/' . $companylogo . '"/>';
			}
		}
		$parsed = str_replace("{company_logo}", $uselogo, $parsed);
		$parsed = str_replace("{company_info}", $company_info, $parsed);
		$parsed = str_replace("{invoice_number}", $invoice_num, $parsed);
		$parsed = str_replace("{invoice_suffix}", $invoice_suff, $parsed);
		$parsed = str_replace("{invoice_date}", $invoice_date, $parsed);
		$parsed = str_replace("{customer_info}", nl2br(rtrim($booking['custdata'], "\n")), $parsed);

		// custom fields replacement
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $cmatches);
		if (is_array($cmatches[1]) && $cmatches[1]) {
			$cfids = array();
			foreach ($cmatches[1] as $cfid) {
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$cfields = $dbo->loadAssocList();
			$vbo_tn->translateContents($cfields, '#__vikbooking_custfields');
			$cfmap = array();
			foreach ($cfields as $cf) {
				$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $booking['custdata']);
			if (count($partsreceived) > 0) {
				foreach ($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach ($cmatches[1] as $cfid) {
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}

		// invoice price description - Start
		$rooms = array();
		$tars = array();
		$arrpeople = array();
		$is_package = !empty($booking['pkg']) ? true : false;
		$tot_adults = 0;
		$tot_children = 0;
		$tot_guests = 0;
		foreach ($booking_rooms as $kor => $or) {
			$num = $kor + 1;
			$rooms[$num] = $or;

			// determine the days to consider for the count of the availability
			$room_nights   = $booking['days'];
			$room_checkin  = $booking['checkin'];
			$room_checkout = $booking['checkout'];
			if ($booking['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
				$room_nights   = $room_stay_dates[$kor]['nights'];
				$room_checkin  = $room_stay_dates[$kor]['checkin'];
				$room_checkout = $room_stay_dates[$kor]['checkout'];
			}

			$arrpeople[$num]['adults'] = $or['adults'];
			$arrpeople[$num]['children'] = $or['children'];
			$tot_adults += $or['adults'];
			$tot_children += $or['children'];
			$tot_guests += ($or['adults'] + $or['children']);
			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				//package or custom cost set from the back-end
				continue;
			}
			$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=" . (int)$or['idtar'] . ";";
			$dbo->setQuery($q);
			$tar = $dbo->loadAssocList();
			if ($tar) {
				$tar = self::applySeasonsRoom($tar, $room_checkin, $room_checkout);
				//different usage
				if ($or['fromadult'] <= $or['adults'] && $or['toadult'] >= $or['adults']) {
					$diffusageprice = self::loadAdultsDiff($or['idroom'], $or['adults']);
					//Occupancy Override
					$occ_ovr = self::occupancyOverrideExists($tar, $or['adults']);
					$diffusageprice = $occ_ovr !== false ? $occ_ovr : $diffusageprice;
					//
					if (is_array($diffusageprice)) {
						//set a charge or discount to the price(s) for the different usage of the room
						foreach ($tar as $kpr => $vpr) {
							$tar[$kpr]['diffusage'] = $or['adults'];
							if ($diffusageprice['chdisc'] == 1) {
								//charge
								if ($diffusageprice['valpcent'] == 1) {
									//fixed value
									$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
									$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
									$tar[$kpr]['diffusagecost'] = "+".$aduseval;
									$tar[$kpr]['room_base_cost'] = $vpr['cost'];
									$tar[$kpr]['cost'] = $vpr['cost'] + $aduseval;
								} else {
									//percentage value
									$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
									$aduseval = $diffusageprice['pernight'] == 1 ? round(($vpr['cost'] * $diffusageprice['value'] / 100) * $tar[$kpr]['days'] + $vpr['cost'], 2) : round(($vpr['cost'] * (100 + $diffusageprice['value']) / 100), 2);
									$tar[$kpr]['diffusagecost'] = "+".$diffusageprice['value']."%";
									$tar[$kpr]['room_base_cost'] = $vpr['cost'];
									$tar[$kpr]['cost'] = $aduseval;
								}
							} else {
								//discount
								if ($diffusageprice['valpcent'] == 1) {
									//fixed value
									$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? 1 : 0;
									$aduseval = $diffusageprice['pernight'] == 1 ? $diffusageprice['value'] * $tar[$kpr]['days'] : $diffusageprice['value'];
									$tar[$kpr]['diffusagecost'] = "-".$aduseval;
									$tar[$kpr]['room_base_cost'] = $vpr['cost'];
									$tar[$kpr]['cost'] = $vpr['cost'] - $aduseval;
								} else {
									//percentage value
									$tar[$kpr]['diffusagecostpernight'] = $diffusageprice['pernight'] == 1 ? $vpr['cost'] : 0;
									$aduseval = $diffusageprice['pernight'] == 1 ? round($vpr['cost'] - ((($vpr['cost'] / $tar[$kpr]['days']) * $diffusageprice['value'] / 100) * $tar[$kpr]['days']), 2) : round(($vpr['cost'] * (100 - $diffusageprice['value']) / 100), 2);
									$tar[$kpr]['diffusagecost'] = "-".$diffusageprice['value']."%";
									$tar[$kpr]['room_base_cost'] = $vpr['cost'];
									$tar[$kpr]['cost'] = $aduseval;
								}
							}
						}
					}
				}
				//
				$tars[$num] = $tar[0];
			}
		}
		$parsed = str_replace("{checkin_date}", date(str_replace("/", $datesep, $df), $booking['checkin']), $parsed);
		$parsed = str_replace("{checkout_date}", date(str_replace("/", $datesep, $df), $booking['checkout']), $parsed);
		$parsed = str_replace("{num_nights}", $booking['days'], $parsed);
		$parsed = str_replace("{tot_guests}", $tot_guests, $parsed);
		$parsed = str_replace("{tot_adults}", $tot_adults, $parsed);
		$parsed = str_replace("{tot_children}", $tot_children, $parsed);

		$isdue = 0;
		$tot_taxes = 0;
		$tot_city_taxes = 0;
		$tot_fees = 0;
		$pricestr = array();
		$optstr = array();
		// start building the tax summary
		VBOTaxonomySummary::start();
		foreach ($booking_rooms as $kor => $or) {
			$num = $kor + 1;

			// determine the days to consider for the count of the availability
			$room_nights   = $booking['days'];
			$room_checkin  = $booking['checkin'];
			$room_checkout = $booking['checkout'];
			if ($booking['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
				$room_nights   = $room_stay_dates[$kor]['nights'];
				$room_checkin  = $room_stay_dates[$kor]['checkin'];
				$room_checkout = $room_stay_dates[$kor]['checkout'];
			}

			$pricestr[$num] = array();
			if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
				// package cost or cust_cost may not be inclusive of taxes if prices tax included is off
				$calctar = self::sayPackagePlusIva($or['cust_cost'], $or['cust_idiva']);
				$cost_minus_tax = self::sayPackageMinusIva($or['cust_cost'], $or['cust_idiva']);
				$pricestr[$num]['name'] = (!empty($or['pkg_name']) ? $or['pkg_name'] : (!empty($or['otarplan']) ? ucwords($or['otarplan']) : JText::translate('VBOROOMCUSTRATEPLAN')));
				$pricestr[$num]['tot'] = $calctar;
				$pricestr[$num]['tax'] = ($calctar - $cost_minus_tax);
				$tot_taxes += ($calctar - $cost_minus_tax);
				$isdue += $calctar;
				$tax_rate = VBOTaxonomySummary::addOptionTax($or['cust_idiva'], ($calctar - $cost_minus_tax));
				$pricestr[$num]['tax_rate'] = $tax_rate;
			} elseif (array_key_exists($num, $tars) && is_array($tars[$num])) {
				$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
				$calctar = self::sayCostPlusIva($display_rate, $tars[$num]['idprice']);
				$pricestr[$num]['name'] = self::getPriceName($tars[$num]['idprice'], $vbo_tn) . (!empty($tars[$num]['attrdata']) ? "\n" . self::getPriceAttr($tars[$num]['idprice'], $vbo_tn) . ": " . $tars[$num]['attrdata'] : "");
				$pricestr[$num]['tot'] = $calctar;
				$tars[$num]['calctar'] = $calctar;
				$isdue += $calctar;
				if ($calctar == $display_rate) {
					$cost_minus_tax = self::sayCostMinusIva($display_rate, $tars[$num]['idprice']);
					$tot_taxes += ($display_rate - $cost_minus_tax);
					$pricestr[$num]['tax'] = ($display_rate - $cost_minus_tax);
					$tax_rate = VBOTaxonomySummary::addRatePlanTax($tars[$num]['idprice'], ($display_rate - $cost_minus_tax));
					$pricestr[$num]['tax_rate'] = $tax_rate;
				} else {
					$tot_taxes += ($calctar - $display_rate);
					$pricestr[$num]['tax'] = ($calctar - $display_rate);
					$tax_rate = VBOTaxonomySummary::addRatePlanTax($tars[$num]['idprice'], ($calctar - $display_rate));
					$pricestr[$num]['tax_rate'] = $tax_rate;
				}
			}
			$optstr[$num] = array();
			$opt_ind = 0;
			if (!empty($or['optionals'])) {
				$stepo = explode(";", $or['optionals']);
				foreach ($stepo as $roptkey => $oo) {
					if (!empty($oo)) {
						$stept = explode(":", $oo);
						$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=" . $dbo->quote($stept[0]) . ";";
						$dbo->setQuery($q);
						$actopt = $dbo->loadAssocList();
						if ($actopt) {
							if (is_object($vbo_tn)) {
								$vbo_tn->translateContents($actopt, '#__vikbooking_optionals');
							}
							$optstr[$num][$opt_ind] = array();
							$chvar = '';
							if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
								$optagenames = self::getOptionIntervalsAges($actopt[0]['ageintervals']);
								$optagepcent = self::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
								$optageovrct = self::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
								$child_num 	 = self::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
								$optagecosts = self::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
								$agestept = explode('-', $stept[1]);
								$stept[1] = $agestept[0];
								$chvar = $agestept[1];
								if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
									//percentage value of the adults tariff
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
									} else {
										$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
								} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
									//VBO 1.10 - percentage value of room base cost
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
									} else {
										$display_rate = isset($tars[$num]['room_base_cost']) ? $tars[$num]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost']);
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
								}
								$actopt[0]['chageintv'] = $chvar;
								$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
								$actopt[0]['quan'] = $stept[1];
								$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $room_nights * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
							} else {
								$actopt[0]['quan'] = $stept[1];
								// VBO 1.11 - options percentage cost of the room total fee
								if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
									$deftar_basecosts = $or['cust_cost'];
								} else {
									$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
								}
								$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
								//
								$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $room_nights * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
							}
							if (!empty($actopt[0]['maxprice']) && $actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
								$realcost = $actopt[0]['maxprice'];
								if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
									$realcost = $actopt[0]['maxprice'] * $stept[1];
								}
							}
							if ($actopt[0]['perperson'] == 1) {
								$realcost = $realcost * $or['adults'];
							}
							$tmpopr = self::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
							$optstr[$num][$opt_ind]['name'] = ($stept[1] > 1 ? $stept[1] . " " : "") . $actopt[0]['name'];
							$optstr[$num][$opt_ind]['tot'] = $tmpopr;
							$optstr[$num][$opt_ind]['tax'] = 0;
							if ($actopt[0]['is_citytax'] == 1) {
								$tot_city_taxes += $tmpopr;
							} elseif ($actopt[0]['is_fee'] == 1) {
								$tot_fees += $tmpopr;
							}
							// VBO 1.11 - always calculate the amount of tax no matter if this is already a tax or a fee
							if ($tmpopr == $realcost) {
								$opt_minus_tax = self::sayOptionalsMinusIva($realcost, $actopt[0]['idiva']);
								$tot_taxes += ($realcost - $opt_minus_tax);
								$optstr[$num][$opt_ind]['tax'] = ($realcost - $opt_minus_tax);
								$tax_rate = VBOTaxonomySummary::addOptionTax($actopt[0]['idiva'], ($realcost - $opt_minus_tax));
								$optstr[$num][$opt_ind]['tax_rate'] = $tax_rate;
							} else {
								$tot_taxes += ($tmpopr - $realcost);
								$optstr[$num][$opt_ind]['tax'] = ($tmpopr - $realcost);
								$tax_rate = VBOTaxonomySummary::addOptionTax($actopt[0]['idiva'], ($tmpopr - $realcost));
								$optstr[$num][$opt_ind]['tax_rate'] = $tax_rate;
							}
							//
							$opt_ind++;
							$isdue += $tmpopr;
						}
					}
				}
			}

			// custom extra costs
			if (!empty($or['extracosts'])) {
				$cur_extra_costs = json_decode($or['extracosts'], true);
				foreach ($cur_extra_costs as $eck => $ecv) {
					$ecplustax = !empty($ecv['idtax']) ? self::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
					$isdue += $ecplustax;
					$optstr[$num][$opt_ind]['name'] = $ecv['name'];
					$optstr[$num][$opt_ind]['tot'] = $ecplustax;
					$optstr[$num][$opt_ind]['tax'] = 0;
					if ($ecplustax == $ecv['cost']) {
						$ec_minus_tax = !empty($ecv['idtax']) ? self::sayOptionalsMinusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
						$tot_taxes += ($ecv['cost'] - $ec_minus_tax);
						$optstr[$num][$opt_ind]['tax'] = ($ecv['cost'] - $ec_minus_tax);
						$tax_rate = VBOTaxonomySummary::addOptionTax($ecv['idtax'], ($ecv['cost'] - $ec_minus_tax));
						$optstr[$num][$opt_ind]['tax_rate'] = $tax_rate;
					} else {
						$tot_taxes += ($ecplustax - $ecv['cost']);
						$optstr[$num][$opt_ind]['tax'] = ($ecplustax - $ecv['cost']);
						$tax_rate = VBOTaxonomySummary::addOptionTax($ecv['idtax'], ($ecplustax - $ecv['cost']));
						$optstr[$num][$opt_ind]['tax_rate'] = $tax_rate;
					}
					$opt_ind++;
				}
			}
		}

		$usedcoupon = false;
		if (strlen($booking['coupon']) > 0) {
			$orig_isdue = $isdue;
			$expcoupon = explode(";", $booking['coupon']);
			$usedcoupon = $expcoupon;
			$isdue = $isdue - (float)$expcoupon[1];
			if ($isdue != $orig_isdue) {
				//lower taxes proportionally
				$tot_taxes = $isdue * $tot_taxes / $orig_isdue;
			}
		}
		if ($booking['refund'] > 0) {
			$orig_isdue = $isdue;
			$isdue -= $booking['refund'];
			if ($isdue != $orig_isdue) {
				//lower taxes proportionally
				$tot_taxes = $isdue * $tot_taxes / $orig_isdue;
			}
		}
		$rows_written = 0;
		$inv_rows = '';
		foreach ($pricestr as $num => $price_descr) {
			// add room split stay information, if any
			$split_stay_str = '';
			$kor = $num - 1;
			if ($booking['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor])) {
				$split_stay_str .= $room_stay_dates[$kor]['nights'] . ' ' . ($room_stay_dates[$kor]['nights'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')) . ', ';
				$split_stay_str .= date(str_replace("/", $datesep, $df), $room_stay_dates[$kor]['checkin']) . ' - ' . date(str_replace("/", $datesep, $df), $room_stay_dates[$kor]['checkout']);
			}

			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td>' . $rooms[$num]['room_name'] . (!empty($split_stay_str) ? '<br/>' . $split_stay_str : '') . '<br/>' . nl2br(rtrim($price_descr['name'], "\n")) . '</td>' . "\n";
			$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberFormat(($price_descr['tot'] - $price_descr['tax'])).'</td>'."\n";
			$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberFormat($price_descr['tax']).'</td>'."\n";
			$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberFormat($price_descr['tot']).'</td>'."\n";
			if (!empty($pdfparams['show_lines_taxrate_col'])) {
				$inv_rows .= '<td>' . (isset($price_descr['tax_rate']) ? $price_descr['tax_rate'] : '0') . '%</td>'."\n";
			}
			$inv_rows .= '</tr>'."\n";
			$rows_written++;
			if (array_key_exists($num, $optstr) && count($optstr[$num]) > 0) {
				foreach ($optstr[$num] as $optk => $optv) {
					$inv_rows .= '<tr>'."\n";
					$inv_rows .= '<td>'.$optv['name'].'</td>'."\n";
					$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberFormat(($optv['tot'] - $optv['tax'])).'</td>'."\n";
					$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberFormat($optv['tax']).'</td>'."\n";
					$inv_rows .= '<td>'.$booking['currencyname'].' '.self::numberFormat($optv['tot']).'</td>'."\n";
					if (!empty($pdfparams['show_lines_taxrate_col'])) {
						$inv_rows .= '<td>' . (isset($optv['tax_rate']) ? $optv['tax_rate'] : '0') . '%</td>'."\n";
					}
					$inv_rows .= '</tr>'."\n";
					$rows_written++;
				}
			}
		}

		// if discount print row
		if ($usedcoupon !== false) {
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td></td><td></td><td></td><td></td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td>'.$usedcoupon[2].'</td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td>- '.$booking['currencyname'].' '.self::numberFormat($usedcoupon[1]).'</td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$rows_written += 2;
		}
		// if refunded amount, print row
		if ($booking['refund'] > 0) {
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td></td><td></td><td></td><td></td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$inv_rows .= '<tr>'."\n";
			$inv_rows .= '<td>' . JText::translate('VBO_AMOUNT_REFUNDED') . '</td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td></td>'."\n";
			$inv_rows .= '<td>- '.$booking['currencyname'].' '.self::numberFormat($booking['refund']).'</td>'."\n";
			$inv_rows .= '</tr>'."\n";
			$rows_written += 2;
		}
		//
		$min_records = 8;
		if ($rows_written < $min_records) {
			for ($i = 1; $i <= ($min_records - $rows_written); $i++) { 
				$inv_rows .= '<tr>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '<td></td>'."\n";
				$inv_rows .= '</tr>'."\n";
			}
		}
		//invoice price description - End
		$parsed = str_replace("{invoice_products_descriptions}", $inv_rows, $parsed);
		$parsed = str_replace("{invoice_totalnet}", $booking['currencyname'].' '.self::numberFormat(($isdue - $tot_taxes)), $parsed);
		$parsed = str_replace("{invoice_totaltax}", $booking['currencyname'].' '.self::numberFormat($tot_taxes), $parsed);
		$parsed = str_replace("{invoice_grandtotal}", $booking['currencyname'].' '.self::numberFormat($isdue), $parsed);
		$parsed = str_replace("{inv_notes}", ($booking['inv_notes'] ?? ''), $parsed);

		// invoice tax summary
		$tax_summary = VBOTaxonomySummary::get();
		$tax_names 	 = VBOTaxonomySummary::getNames();
		$taxsum_rows = "";
		foreach ($tax_summary as $tax_rate => $tax_amount) {
			if (!$tax_rate && !$tax_amount) {
				continue;
			}
			$tax_name = isset($tax_names[$tax_rate]) ? $tax_names[$tax_rate] : JText::translate('VBO_INV_VATGST');
			$taxsum_rows .= '<tr>' . "\n";
			$taxsum_rows .= '<td>' . $tax_name . '</td>' . "\n";
			$taxsum_rows .= '<td>' . $tax_rate . '%</td>' . "\n";
			$taxsum_rows .= '<td>' . $booking['currencyname'] . ' ' . self::numberFormat($tax_amount) . '</td>' . "\n";
			$taxsum_rows .= '</tr>' . "\n";
		}
		$parsed = str_replace("{invoice_tax_summary}", $taxsum_rows, $parsed);

		return $parsed;
	}

	/**
	 * Specific method for parsing the template file for the custom (manual) invoices.
	 * 
	 * @param 	string 	$invoicetpl the plain custom invoice template file before parsing
	 * @param 	array 	$invoice 	the invoice record
	 * @param 	array 	$customer	the customer record
	 *
	 * @return 	string 	the HTML content of the parsed custom invoice template
	 * 
	 * @since 	1.11.1
	 */
	public static function parseCustomInvoiceTemplate($invoicetpl, $invoice, $customer) {
		$nowdf = self::getDateFormat(true);
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator(true);
		$companylogo = self::getSiteLogo();
		$uselogo = '';
		if (!empty($companylogo)) {
			/**
			 * Let's try to prevent TCPDF errors by checking if the file exists.
			 * 
			 * @since 		August 2nd 2019
			 */
			if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				$uselogo = '<img src="' . VBO_ADMIN_URI_REL . 'resources/' . $companylogo . '"/>';
			} elseif (is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				$uselogo = '<img src="' . VBO_SITE_URI_REL . 'resources/' . $companylogo . '"/>';
			}
		}
		$invoicetpl = str_replace("{company_logo}", $uselogo, $invoicetpl);
		$invoicetpl = str_replace("{company_info}", self::getInvoiceCompanyInfo(), $invoicetpl);
		$invoicetpl = str_replace("{invoice_number}", $invoice['number'], $invoicetpl);
		$invoicetpl = str_replace("{invoice_date}", date(str_replace("/", $datesep, $df), $invoice['for_date']), $invoicetpl);
		// customer information
		$custinfo = '';
		$custinfo .= $customer['first_name'] . ' ' . $customer['last_name'] . "\n";
		$custinfo .= $customer['email'] . "\n";
		$custinfo .= !empty($customer['company']) ? $customer['company'] . "\n" : '';
		$custinfo .= !empty($customer['vat']) ? $customer['vat'] . "\n" : '';
		$custinfo .= !empty($customer['address']) ? $customer['address'] . "\n" : '';
		$custinfo .= (!empty($customer['zip']) ? $customer['zip'] . " " : '') . (!empty($customer['city']) ? $customer['city'] . "\n" : '');
		$custinfo .= (!empty($customer['country_name']) ? $customer['country_name'] . "\n" : (!empty($customer['country']) ? $customer['country'] . "\n" : ''));
		$custinfo .= !empty($customer['fisccode']) ? $customer['fisccode'] . "\n" : '';
		$invoicetpl = str_replace("{customer_info}", nl2br($custinfo), $invoicetpl);
		// invoice notes
		$invoicetpl = str_replace("{invoice_notes}", (isset($invoice['rawcont']) && isset($invoice['rawcont']['notes']) ? $invoice['rawcont']['notes'] : ''), $invoicetpl);

		return $invoicetpl;
	}

	/**
	 * Generates or updates an invoice for a specific reservation, always in PDF format, and electronic if some drivers were enabled.
	 * 
	 * @param 	array 	$booking 		the booking information.
	 * @param 	int 	$invoice_num 	the invoice number.
	 * @param 	string 	$invoice_suff 	the invoice suffix.
	 * @param 	string 	$invoice_date 	the invoice date.
	 * @param 	string 	$company_info 	optional company information.
	 * @param 	bool 	$translate 		whether the invoice contents should be translated in the booking language.
	 * @param 	bool 	$refresh_pdf 	true to only refresh (re-generate) the PDF, in case some extra information is available.
	 * 
	 * @return 	mixed 					false in case of failure, invoice record ID if the PDF invoice was generated, and the record stored.
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP) added argument $refresh_pdf to be used by electronic invoicing drivers after a succesful invoice transmission.
	 */
	public static function generateBookingInvoice($booking, $invoice_num = 0, $invoice_suff = '', $invoice_date = '', $company_info = '', $translate = false, $refresh_pdf = false)
	{
		$invoice_num  = empty($invoice_num) ? self::getNextInvoiceNumber() : $invoice_num;
		$invoice_suff = empty($invoice_suff) ? self::getInvoiceNumberSuffix() : $invoice_suff;
		$company_info = empty($company_info) ? self::getInvoiceCompanyInfo() : $company_info;

		if (!is_array($booking) || !$booking || !($booking['total'] > 0)) {
			return false;
		}

		/**
		 * Trigger event to allow third party plugins to override some values for the invoice generation.
		 * 
		 * @since 	1.16.3 (J) - 1.6.3 (WP)
		 */
		$event_args = [
			'invoice_num'  => $invoice_num,
			'invoice_suff' => $invoice_suff,
			'invoice_date' => $invoice_date,
			'company_info' => $company_info,
			'translate'    => $translate,
		];
		VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeGenerateInvoiceVikBooking', [$booking, &$event_args]);
		if (is_array($event_args) && $event_args) {
			// extract the new values to be used
			extract($event_args);
		}

		$dbo = JFactory::getDbo();
		$vbo_tn = self::getTranslator();

		// inject the currency name value
		$currencyname = self::getCurrencyName();
		$booking['currencyname'] = $currencyname;

		// make sure we've got a customer ID assigned to the reservation record
		if (!isset($booking['idcustomer']) || !isset($booking['customer_name'])) {
			$booking_customer = self::getCPinInstance()->getCustomerFromBooking($booking['id']);
			if ($booking_customer) {
				$booking['idcustomer'] 	  = $booking_customer['id'];
				$booking['customer_name'] = $booking_customer['first_name'] . ' ' . $booking_customer['last_name'];
				$booking['customer_pin']  = $booking_customer['pin'];
				$booking['country_name']  = $booking_customer['country_name'] ?? '';
			}
		}

		$nowdf = self::getDateFormat(true);
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = self::getDateSeparator(true);

		if (empty($invoice_date)) {
			$invoice_date = date(str_replace("/", $datesep, $df), $booking['ts']);
			$used_date = $booking['ts'];
		} else {
			/**
			 * We could be re-generating an invoice for a booking that already had an invoice.
			 * In order to modify some entries in the invoice, the whole PDF is re-generated.
			 * It is now possible to keep the same invoice date as the previous one, so check
			 * what value contains $invoice_date to see if it's different from today's date.
			 * The cron jobs may be calling this method with a $invoice_date = 1, so we need
			 * to also check the length of the string $invoice_date before using that date.
			 * 
			 * @since 	1.10 - August 2018
			 */
			$base_ts = time();
			if (date($df, $base_ts) != $invoice_date && strlen($invoice_date) >= 6) {
				$base_ts = self::getDateTimestamp($invoice_date, 0, 0);
			}
			$invoice_date = date(str_replace("/", $datesep, $df), $base_ts);
			$used_date = $base_ts;
		}

		// load room reservation records
		$q = $dbo->getQuery(true)
			->select($dbo->qn('or') . '.*')
			->select($dbo->qn('r.name', 'room_name'))
			->select($dbo->qn('r.fromadult'))
			->select($dbo->qn('r.toadult'))
			->from($dbo->qn('#__vikbooking_ordersrooms', 'or'))
			->leftJoin($dbo->qn('#__vikbooking_rooms', 'r') . ' ON ' . $dbo->qn('or.idroom') . ' = ' . $dbo->qn('r.id'))
			->where($dbo->qn('or.idorder') . ' = ' . (int)$booking['id'])
			->order($dbo->qn('or.id') . ' ASC');

		$dbo->setQuery($q);
		$booking_rooms = $dbo->loadAssocList();

		if (!$booking_rooms) {
			return false;
		}

		// check if an invoice for this reservation exists
		$invoice_id = 0;
		$dbo->setQuery(
			$dbo->getQuery(true)
				->select('*')
				->from($dbo->qn('#__vikbooking_invoices'))
				->where($dbo->qn('idorder') . ' = ' . (int)$booking['id'])
				->order($dbo->qn('id') . ' DESC')
		);
		$invoice_data = $dbo->loadAssoc();
		if ($invoice_data) {
			$invoice_id = $invoice_data['id'];
		}

		// if requested, prepare data to refresh the PDF invoice
		if ($refresh_pdf === true) {
			if (!$invoice_id || !$invoice_data) {
				// cannot refresh the PDF if an invoice was never created before
				return false;
			}

			// load the same exact previous information to refresh the invoice
			$invoice_num  = $invoice_data['number'];
			$invoice_suff = $invoice_suff;
			$invoice_date = date(str_replace("/", $datesep, $df), $invoice_data['for_date']);
			$used_date 	  = $invoice_data['for_date'];

			// parse the previous (current) invoice number
			preg_match("/^([0-9]+)([\/\s-]?.+)/i", $invoice_data['number'], $matches);
			if ($matches) {
				// overwrite values
				$invoice_num  = $matches[1] ?: $invoice_num;
				$invoice_suff = $matches[2] ?: $invoice_suff;
			}
		}

		// translations for the invoices are disabled by default as well as the language definitions for the customer language
		if ($translate === true) {
			if (!empty($booking['lang'])) {
				$lang = JFactory::getLanguage();
				if ($lang->getTag() != $booking['lang']) {
					if (VBOPlatformDetection::isWordPress()) {
						$lang->load('com_vikbooking', VIKBOOKING_LANG, $booking['lang'], true);
					} else {
						$lang->load('com_vikbooking', JPATH_SITE, $booking['lang'], true);
						$lang->load('com_vikbooking', JPATH_ADMINISTRATOR, $booking['lang'], true);
						$lang->load('joomla', JPATH_SITE, $booking['lang'], true);
						$lang->load('joomla', JPATH_ADMINISTRATOR, $booking['lang'], true);
					}
				}
				if ($vbo_tn->getDefaultLang() != $booking['lang']) {
					// force the translation to start because contents should be translated
					$vbo_tn::$force_tolang = $booking['lang'];
				}
				$vbo_tn->translateContents($booking_rooms, '#__vikbooking_rooms', array('id' => 'idroom', 'room_name' => 'name'), array(), $booking['lang']);
			}
		}

		// the PDF file name
		$pdffname = $booking['id'] . '_' . $booking['sid'] . '.pdf';

		/**
		 * If an analogic invoice is not available, make sure to create the record
		 * before letting the e-invoicing drivers run to avoid a loop.
		 * 
		 * @since 	1.16.7 (J) - 1.6.7 (WP)
		 */
		$retval = null;
		if (!$refresh_pdf && !$invoice_id) {
			// prepare record object
			$invoice_record = new stdClass;
			$invoice_record->number 	= $invoice_num . $invoice_suff;
			$invoice_record->file_name  = $pdffname;
			$invoice_record->idorder 	= (int)$booking['id'];
			$invoice_record->idcustomer = (int)$booking['idcustomer'];
			$invoice_record->created_on = time();
			$invoice_record->for_date 	= (int)$used_date;

			// insert record
			if (!$dbo->insertObject('#__vikbooking_invoices', $invoice_record, 'id')) {
				return false;
			}

			$retval = $invoice_record->id ?? false;

			if (!$retval) {
				return false;
			}
		}

		/**
		 * The generation of the analogic invoices can trigger the drivers for the
		 * generation of the e-Invoices if they are set to automatically run.
		 * However, the e-Invoicing classes may be calling this method, so we need
		 * to make sure the eInvoicing class is not running before proceeding.
		 * This method could be called within a loop, and so the second iterations
		 * may already have loaded the eInvocing class. For this we use a static variable.
		 *
		 * @since 	1.16.7 (J) - 1.6.7 (WP) the electronic invoices run before the PDF invoices, even
		 *  		though additional information may be available only after the transmission. On top
		 * 			of that, e-invoicing drivers always run, but they may not generate the e-invoice.
		 */
		static $einvocing_can_run = false;

		if (!defined('VBO_EINVOICING_RUN') && !$einvocing_can_run) {
			// allow second iterations calling this method to run
			$einvocing_can_run = true;
		}

		// always load all drivers, db and plugin based ones
		$drivers = VBOEinvoicingFactory::getInstance()->getDrivers();

		foreach ($drivers as $driver_obj) {
			// set the flag of the external call
			$driver_obj->externalCall = __METHOD__;

			// inject invoice number to avoid discrepanices between analogic and electronic, maybe due to missing information for the e-Invoices
			$driver_obj->externalData['einvnum'] = $invoice_num;

			// let the driver eventually elaborate the booking information before invoicing
			$driver_obj->elaborateBookingDetails($booking, $booking_rooms);

			if ($einvocing_can_run === true && !$refresh_pdf) {
				// generate the e-Invoice
				$driver_err = '';
				if (!$driver_obj->generateEInvoice((int)$booking['id'])) {
					$driver_err = $driver_obj->getError();
					VikError::raiseWarning('', $driver_err);
				}

				// Booking History - store log for the e-Invoice result
				self::getBookingHistoryInstance()->setBid($booking['id'])->store('BI', $driver_obj->getName().(!empty($driver_err) ? ': '.$driver_err : ''));
			}
		}

		/**
		 * Start the generation of the analogic (courtesy) PDF invoice.
		 */

		// load dependencies
		if (!class_exists('TCPDF')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . 'tcpdf.php');
		}
		$usepdffont = is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . "fonts" . DIRECTORY_SEPARATOR . "dejavusans.php") ? 'dejavusans' : 'helvetica';

		/**
		 * Trigger event to allow third party plugins to return a specific font name.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$custom_pdf_font = VBOFactory::getPlatform()->getDispatcher()->filter('onGetPdfFontNameVikBooking', [$usepdffont]);
		if (is_array($custom_pdf_font) && !empty($custom_pdf_font[0])) {
			$usepdffont = $custom_pdf_font[0];
		}

		// set array variable to the template file
		$booking_info = self::getBookingInfoFromID($booking['id']);

		list($invoicetpl, $pdfparams) = self::loadInvoiceTmpl($booking_info, $booking_rooms);

		$invoice_body = self::parseInvoiceTemplate($invoicetpl, $booking, $booking_rooms, $invoice_num, $invoice_suff, $invoice_date, $company_info, ($translate === true ? $vbo_tn : null), $pdfparams);

		$pathpdf = VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . "generated" . DIRECTORY_SEPARATOR . $pdffname;

		if (is_file($pathpdf)) {
			@unlink($pathpdf);
		}

		$pdf_page_format = is_array($pdfparams['pdf_page_format']) ? $pdfparams['pdf_page_format'] : constant($pdfparams['pdf_page_format']);

		$pdf = new TCPDF(constant($pdfparams['pdf_page_orientation']), constant($pdfparams['pdf_unit']), $pdf_page_format, true, 'UTF-8', false);
		$pdf->SetTitle(JText::translate('VBOINVNUM').' '.$invoice_num);

		// header for each page of the pdf
		if ($pdfparams['show_header'] == 1 && $pdfparams['header_data']) {
			$pdf->SetHeaderData($pdfparams['header_data'][0], $pdfparams['header_data'][1], $pdfparams['header_data'][2], $pdfparams['header_data'][3], $pdfparams['header_data'][4], $pdfparams['header_data'][5]);
		}

		// change some currencies to their unicode (decimal) value
		$unichr_map = array('EUR' => 8364, 'USD' => 36, 'AUD' => 36, 'CAD' => 36, 'GBP' => 163);
		if (array_key_exists($booking['currencyname'], $unichr_map)) {
			$invoice_body = str_replace($booking['currencyname'], TCPDF_FONTS::unichr($unichr_map[$booking['currencyname']]), $invoice_body);
		}

		// header and footer fonts
		$pdf->setHeaderFont(array($usepdffont, '', $pdfparams['header_font_size']));
		$pdf->setFooterFont(array($usepdffont, '', $pdfparams['footer_font_size']));

		// margins
		$pdf->SetMargins(constant($pdfparams['pdf_margin_left']), constant($pdfparams['pdf_margin_top']), constant($pdfparams['pdf_margin_right']));
		$pdf->SetHeaderMargin(constant($pdfparams['pdf_margin_header']));
		$pdf->SetFooterMargin(constant($pdfparams['pdf_margin_footer']));

		$pdf->SetAutoPageBreak(true, constant($pdfparams['pdf_margin_bottom']));
		$pdf->setImageScale(constant($pdfparams['pdf_image_scale_ratio']));
		$pdf->SetFont($usepdffont, '', (int)$pdfparams['body_font_size']);

		if ($pdfparams['show_header'] == 0 || !$pdfparams['header_data']) {
			$pdf->SetPrintHeader(false);
		}
		if ($pdfparams['show_footer'] == 0) {
			$pdf->SetPrintFooter(false);
		}

		$pdf->AddPage();
		$pdf->writeHTML($invoice_body, true, false, true, false, '');
		$pdf->lastPage();
		$pdf->Output($pathpdf, 'F');

		if (!is_file($pathpdf)) {
			return false;
		}

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly - trigger files mirroring
			 */
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerUploadBackup($pathpdf);
		}

		// Booking History
		self::getBookingHistoryInstance()->setBid($booking['id'])->store('BI', '#' . $invoice_num . $invoice_suff);

		if ($retval) {
			// a new invoice ID was inserted above
			return $retval;
		}

		// prepare record object to be updated (if insert needed, it's done at first)
		$invoice_record = new stdClass;
		$invoice_record->id 		= (int)$invoice_id;
		$invoice_record->number 	= $invoice_num . $invoice_suff;
		$invoice_record->file_name  = $pdffname;
		$invoice_record->idorder 	= (int)$booking['id'];
		$invoice_record->idcustomer = (int)$booking['idcustomer'];
		$invoice_record->created_on = time();
		$invoice_record->for_date 	= (int)$used_date;

		// update record
		$dbo->updateObject('#__vikbooking_invoices', $invoice_record, 'id');

		// return the PDF invoice ID
		return $invoice_id;
	}

	/**
	 * Generates an analogic invoice in PDF format for a custom list of services.
	 * No bookings are assigned to this custom invoice. The method parses the same
	 * invoice template as for the regular process with real booking IDs.
	 * The invoice number must be stored before calling this method.
	 * 
	 * @param 	int 	$invoice_id 	the ID of the custom invoice record
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.11.1 (J) - 1.1.1 (WP)
	 */
	public static function generateCustomInvoice($invoice_id)
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_invoices` WHERE `id`=".(int)$invoice_id." AND `idorder` < 0;";
		$dbo->setQuery($q);
		$invoice = $dbo->loadAssoc();
		if (!$invoice) {
			return false;
		}

		$rawcont = !empty($invoice['rawcont']) ? json_decode($invoice['rawcont'], true) : array();
		$rawcont = is_array($rawcont) ? $rawcont : array();
		$rows = isset($rawcont['rows']) ? $rawcont['rows'] : array();
		$invoice['rawcont'] = $rawcont;
		$customer = self::getCPinInstance()->getCustomerByID($invoice['idcustomer']);
		if (!$rawcont || !$rows || !$customer) {
			// at least one invoice raw is mandatory as well as the customer
			return false;
		}

		/**
		 * Start the generation of the electronic invoice, if any driver was enabled.
		 *
		 * @since 	1.16.7 (J) - 1.6.7 (WP) the electronic invoices run before the PDF invoices, even
		 *  		though additional information would be available only after the transmission.
		 */
		$drivers = VBOEinvoicingFactory::getInstance()->getDrivers();

		if ($drivers) {
			// make sure the invoice number is just a number
			$invoice['number'] = str_replace(self::getInvoiceNumberSuffix(), '', $invoice['number']);

			// invoke all drivers that should run automatically
			foreach ($drivers as $driver_obj) {
				// set the flag of the external call
				$driver_obj->externalCall = __METHOD__;

				// inject custom invoice number, date and details to avoid discrepanices between analogic and electronic
				$driver_obj->externalData['einvnum'] = $invoice['number'];
				$driver_obj->externalData['einvdate'] = (int)$invoice['for_date'];
				$driver_obj->externalData['einvcustom'] = $invoice;

				// prepare data array for the generation of the e-Invoice
				$einvdata = $driver_obj->prepareCustomInvoiceData($invoice, $customer);

				// before generating the e-invoice, make sure to obliterate it if exists already (case of update custom invoice)
				$preveinv = $driver_obj->eInvoiceExists(array('idorder' => $einvdata[0]['id']));
				if ($preveinv !== false) {
					$driver_obj->obliterateEInvoice(array('id' => $preveinv));
				}

				// generate the e-Invoice
				$driver_err = '';
				if (!$driver_obj->generateEInvoice($einvdata)) {
					$driver_err = $driver_obj->getError();
					VikError::raiseWarning('', $driver_err);
				}
			}
		}

		/**
		 * Start the generation of the analogic (courtesy) PDF invoice.
		 */

		// load dependencies
		if (!class_exists('TCPDF')) {
			require_once(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . 'tcpdf.php');
		}
		$usepdffont = is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "tcpdf" . DIRECTORY_SEPARATOR . "fonts" . DIRECTORY_SEPARATOR . "dejavusans.php") ? 'dejavusans' : 'helvetica';

		/**
		 * Trigger event to allow third party plugins to return a specific font name.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$custom_pdf_font = VBOFactory::getPlatform()->getDispatcher()->filter('onGetPdfFontNameVikBooking', [$usepdffont]);
		if (is_array($custom_pdf_font) && !empty($custom_pdf_font[0])) {
			$usepdffont = $custom_pdf_font[0];
		}

		// load invoice template file
		list($invoicetpl, $pdfparams) = self::loadCustomInvoiceTmpl($invoice, $customer);

		// parse invoice template file
		$invoice_body = self::parseCustomInvoiceTemplate($invoicetpl, $invoice, $customer);

		$pdffname = $invoice['file_name'];
		$pathpdf = VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . "generated" . DIRECTORY_SEPARATOR . $pdffname;

		if (is_file($pathpdf)) {
			@unlink($pathpdf);
		}

		$pdf_page_format = is_array($pdfparams['pdf_page_format']) ? $pdfparams['pdf_page_format'] : constant($pdfparams['pdf_page_format']);

		$pdf = new TCPDF(constant($pdfparams['pdf_page_orientation']), constant($pdfparams['pdf_unit']), $pdf_page_format, true, 'UTF-8', false);
		$pdf->SetTitle(JText::translate('VBOINVNUM').' '.$invoice['number']);

		// header for each page of the pdf
		if ($pdfparams['show_header'] == 1 && count($pdfparams['header_data']) > 0) {
			$pdf->SetHeaderData($pdfparams['header_data'][0], $pdfparams['header_data'][1], $pdfparams['header_data'][2], $pdfparams['header_data'][3], $pdfparams['header_data'][4], $pdfparams['header_data'][5]);
		}

		// change some currencies to their unicode (decimal) value
		$currencyname = self::getCurrencyName();
		$unichr_map = array('EUR' => 8364, 'USD' => 36, 'AUD' => 36, 'CAD' => 36, 'GBP' => 163);
		if (array_key_exists($currencyname, $unichr_map)) {
			$invoice_body = str_replace($currencyname, TCPDF_FONTS::unichr($unichr_map[$currencyname]), $invoice_body);
		}

		// header and footer fonts
		$pdf->setHeaderFont(array($usepdffont, '', $pdfparams['header_font_size']));
		$pdf->setFooterFont(array($usepdffont, '', $pdfparams['footer_font_size']));

		// margins
		$pdf->SetMargins(constant($pdfparams['pdf_margin_left']), constant($pdfparams['pdf_margin_top']), constant($pdfparams['pdf_margin_right']));
		$pdf->SetHeaderMargin(constant($pdfparams['pdf_margin_header']));
		$pdf->SetFooterMargin(constant($pdfparams['pdf_margin_footer']));

		$pdf->SetAutoPageBreak(true, constant($pdfparams['pdf_margin_bottom']));
		$pdf->setImageScale(constant($pdfparams['pdf_image_scale_ratio']));
		$pdf->SetFont($usepdffont, '', (int)$pdfparams['body_font_size']);

		if ($pdfparams['show_header'] == 0 || !$pdfparams['header_data']) {
			$pdf->SetPrintHeader(false);
		}
		if ($pdfparams['show_footer'] == 0) {
			$pdf->SetPrintFooter(false);
		}

		$pdf->AddPage();
		$pdf->writeHTML($invoice_body, true, false, true, false, '');
		$pdf->lastPage();
		$pdf->Output($pathpdf, 'F');

		if (!is_file($pathpdf)) {
			return false;
		}

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly - trigger files mirroring
			 */
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerUploadBackup($pathpdf);
		}

		// return the result of the generation of the PDF invoice
		return true;
	}

	public static function sendBookingInvoice($invoice_id, $booking, $text = '', $subject = '')
	{
		if (empty($invoice_id) || !is_array($booking) || empty($booking['custmail'])) {
			return false;
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_invoices` WHERE `id`=".(int)$invoice_id.";";
		$dbo->setQuery($q);
		$invoice_data = $dbo->loadAssoc();

		if (!$invoice_data) {
			return false;
		}

		$mail_text = empty($text) ? JText::translate('VBOEMAILINVOICEATTACHTXT') : $text;
		$mail_subject = empty($subject) ? JText::translate('VBOEMAILINVOICEATTACHSUBJ') : $subject;
		$invoice_file_path = VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "invoices" . DIRECTORY_SEPARATOR . "generated" . DIRECTORY_SEPARATOR . $invoice_data['file_name'];
		if (!file_exists($invoice_file_path)) {
			return false;
		}

		$vbo_app = self::getVboApplication();
		$admin_sendermail = self::getSenderMail();

		$vbo_app->sendMail($admin_sendermail, $admin_sendermail, $booking['custmail'], $admin_sendermail, $mail_subject, $mail_text, (strpos($mail_text, '<') !== false && strpos($mail_text, '>') !== false ? true : false), 'base64', $invoice_file_path);

		// update record
		$q = "UPDATE `#__vikbooking_invoices` SET `emailed`=1, `emailed_to`=".$dbo->quote($booking['custmail'])." WHERE `id`=".(int)$invoice_id.";";
		$dbo->setQuery($q);
		$dbo->execute();

		return true;
	}

	public static function loadCheckinDocTmpl($booking_info = array(), $booking_rooms = array(), $customer = array()) {
		if (!defined('_VIKBOOKINGEXEC')) {
			define('_VIKBOOKINGEXEC', '1');
		}
		ob_start();
		include VBO_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "checkins" . DIRECTORY_SEPARATOR . "checkin_tmpl.php";
		$content = ob_get_contents();
		ob_end_clean();
		$default_params = array(
			'show_header' => 0,
			'header_data' => array(),
			'show_footer' => 0,
			'pdf_page_orientation' => 'PDF_PAGE_ORIENTATION',
			'pdf_unit' => 'PDF_UNIT',
			'pdf_page_format' => 'PDF_PAGE_FORMAT',
			'pdf_margin_left' => 'PDF_MARGIN_LEFT',
			'pdf_margin_top' => 'PDF_MARGIN_TOP',
			'pdf_margin_right' => 'PDF_MARGIN_RIGHT',
			'pdf_margin_header' => 'PDF_MARGIN_HEADER',
			'pdf_margin_footer' => 'PDF_MARGIN_FOOTER',
			'pdf_margin_bottom' => 'PDF_MARGIN_BOTTOM',
			'pdf_image_scale_ratio' => 'PDF_IMAGE_SCALE_RATIO',
			'header_font_size' => '10',
			'body_font_size' => '10',
			'footer_font_size' => '8'
		);
		if (defined('_VIKBOOKING_CHECKIN_PARAMS') && isset($checkin_params) && is_array($checkin_params) && $checkin_params) {
			$default_params = array_merge($default_params, $checkin_params);
		}
		return array($content, $default_params);
	}

	public static function parseCheckinDocTemplate($checkintpl, $booking, $booking_rooms, $customer) {
		$parsed = $checkintpl;
		$dbo = JFactory::getDbo();
		$app = JFactory::getApplication();
		$nowdf = self::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df='d/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df='m/d/Y';
		} else {
			$df='Y/m/d';
		}
		$datesep = self::getDateSeparator();
		$companylogo = self::getSiteLogo();
		$uselogo = '';
		if (!empty($companylogo)) {
			/**
			 * Let's try to prevent TCPDF errors, as custom logos are always uploaded in /admin
			 * 
			 * @since 		March 16th 2021
			 */
			if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				// $uselogo = '<img src="' . VBO_ADMIN_URI_REL . 'resources/' . $companylogo . '"/>';
				$uselogo = '<img src="' . VBO_ADMIN_URI . 'resources/' . $companylogo . '"/>';
			} elseif (is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . $companylogo)) {
				// $uselogo = '<img src="' . VBO_SITE_URI_REL . 'resources/' . $companylogo . '"/>';
				$uselogo = '<img src="' . VBO_SITE_URI . 'resources/' . $companylogo . '"/>';
			}
		}
		$company_name = self::getFrontTitle();
		$company_info = self::getInvoiceCompanyInfo();
		$parsed = str_replace("{company_name}", $company_name, $parsed);
		$parsed = str_replace("{company_logo}", $uselogo, $parsed);
		$parsed = str_replace("{company_info}", $company_info, $parsed);
		$parsed = str_replace("{customer_info}", nl2br(rtrim($booking['custdata'], "\n")), $parsed);
		$parsed = str_replace("{checkin_date}", date(str_replace("/", $datesep, $df), $booking['checkin']), $parsed);
		$parsed = str_replace("{checkout_date}", date(str_replace("/", $datesep, $df), $booking['checkout']), $parsed);
		$parsed = str_replace("{num_nights}", $booking['days'], $parsed);
		$tot_guests = 0;
		$tot_adults = 0;
		$tot_children = 0;
		foreach ($booking_rooms as $kor => $or) {
			$tot_guests += ($or['adults'] + $or['children']);
			$tot_adults += $or['adults'];
			$tot_children += $or['children'];
		}
		$parsed = str_replace("{tot_guests}", $tot_guests, $parsed);
		$parsed = str_replace("{tot_adults}", $tot_adults, $parsed);
		$parsed = str_replace("{tot_children}", $tot_children, $parsed);
		if (count($customer) && isset($customer['comments'])) {
			$parsed = str_replace("{checkin_comments}", $customer['comments'], $parsed);
		}
		$termsconds = self::getTermsConditions();
		$parsed = str_replace("{terms_and_conditions}", $termsconds, $parsed);
		//custom fields replace
		preg_match_all('/\{customfield ([0-9]+)\}/U', $parsed, $cmatches);
		if (is_array($cmatches[1]) && $cmatches[1]) {
			$cfids = array();
			foreach ($cmatches[1] as $cfid) {
				$cfids[] = $cfid;
			}
			$q = "SELECT * FROM `#__vikbooking_custfields` WHERE `id` IN (".implode(", ", $cfids).");";
			$dbo->setQuery($q);
			$cfields = $dbo->loadAssocList();
			$vbo_tn->translateContents($cfields, '#__vikbooking_custfields');
			$cfmap = array();
			foreach ($cfields as $cf) {
				$cfmap[trim(JText::translate($cf['name']))] = $cf['id'];
			}
			$cfmapreplace = array();
			$partsreceived = explode("\n", $booking['custdata']);
			if (count($partsreceived) > 0) {
				foreach ($partsreceived as $pst) {
					if (!empty($pst)) {
						$tmpdata = explode(":", $pst);
						if (array_key_exists(trim($tmpdata[0]), $cfmap)) {
							$cfmapreplace[$cfmap[trim($tmpdata[0])]] = trim($tmpdata[1]);
						}
					}
				}
			}
			foreach ($cmatches[1] as $cfid) {
				if (array_key_exists($cfid, $cfmapreplace)) {
					$parsed = str_replace("{customfield ".$cfid."}", $cfmapreplace[$cfid], $parsed);
				} else {
					$parsed = str_replace("{customfield ".$cfid."}", "", $parsed);
				}
			}
		}
		//end custom fields replace

		return $parsed;
	}

	/**
	 * Returns an array of key-value pairs to be used for building the Guest Details
	 * in the Check-in process. The keys will be compared to the fields of the table
	 * #__customers to see if some values already exist. The values can use lang defs
	 * of both front-end or back-end. To be called as list(fields, attributes).
	 * 
	 * @param 	boolean 	$precheckin 	true if requested for front-end pre check-in.
	 * @param 	string 		$type 			optional type of custom pax fields to force.
	 *
	 * @return 	array 						key-value pairs for showing and collecting guest details.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) introduced custom pax data collection type.
	 * @since 	1.16.3 (J) - 1.6.3 (WP) implemented hook to override the pre-checkin custom fields.
	 */
	public static function getPaxFields($precheckin = false, $type = null)
	{
		if (!$precheckin) {
			// back-end check-in ("registration") key-value pairs

			// check the type of pax fields collection data
			$collection_type = !empty($type) ? $type : VBOFactory::getConfig()->getString('checkindata', 'basic');

			// return the requested pax fields collection list
			$custom_pax_fields = VBOCheckinPax::getFields($collection_type);

			if (is_array($custom_pax_fields) && count($custom_pax_fields)) {
				// requested driver returned a list of fields
				return $custom_pax_fields;
			}

			// in case the driver is unsupported, fallback to "basic" (default) driver
			return VBOCheckinPax::getFields('basic');
		}

		// front-end key-value pairs for pre check-in
		$precheckin_pax_fields = [
			[
				'first_name'  => JText::translate('VBCCFIRSTNAME'),
				'last_name'   => JText::translate('VBCCLASTNAME'),
				'date_birth'  => JText::translate('ORDER_DBIRTH'),
				'place_birth' => JText::translate('VBOCUSTPLACEBIRTH'),
				'country' 	  => JText::translate('ORDER_STATE'),
				'city' 		  => JText::translate('ORDER_CITY'),
				'zip' 		  => JText::translate('ORDER_ZIP'),
				'nationality' => JText::translate('VBOCUSTNATIONALITY'),
				'gender' 	  => JText::translate('VBOCUSTGENDER'),
				'doctype' 	  => JText::translate('VBOCUSTDOCTYPE'),
				'docnum' 	  => JText::translate('VBOCUSTDOCNUM'),
				'documents'   => JText::translate('VBO_CUSTOMER_UPLOAD_DOCS'),
			],
			[
				'first_name'  => 'text',
				'last_name'   => 'text',
				'date_birth'  => 'calendar',
				'place_birth' => 'text',
				'country' 	  => 'country',
				'city' 		  => 'text',
				'zip' 		  => 'text',
				'nationality' => 'country',
				'gender' 	  => [
					'M' => JText::translate('VBOCUSTGENDERM'),
					'F' => JText::translate('VBOCUSTGENDERF'),
				],
				'doctype' 	  => 'text',
				'docnum' 	  => 'text',
				'documents'   => 'file',
			],
		];

		// make a safe copy of the default precheckin fields
		$def_precheckin_pax_fields = $precheckin_pax_fields;

		/**
		 * Trigger event to allow third party plugins to override the default pre-checkin pax fields.
		 * 
		 * @since 	1.16.3 (J) - 1.6.3 (WP)
		 */
		VBOFactory::getPlatform()->getDispatcher()->trigger('onDisplayPrecheckinPaxFieldsVikBooking', [&$precheckin_pax_fields]);

		if (is_array($precheckin_pax_fields) && count($precheckin_pax_fields) > 1 && !empty($precheckin_pax_fields[0]) && !empty($precheckin_pax_fields[1])) {
			// use the filtered pax fields
			$def_precheckin_pax_fields = $precheckin_pax_fields;
		}

		// return the front-end key-value pairs for pre check-in
		return $def_precheckin_pax_fields;
	}

	/**
	 * Returns the associative list of countries from the DB.
	 * 
	 * @param 	bool 	$tn 	whether to translate the country name.
	 * @param 	bool 	$no_id 	whether to unset the ID of the country.
	 * 
	 * @return 	array 			associative or empty array.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) translations supported and applied by default.
	 * @since 	1.16.0 (J) - 1.6.0 (WP) added second argument.
	 */
	public static function getCountriesArray($tn = true, $no_id = true)
	{
		$dbo = JFactory::getDbo();
		$all_countries = [];

		$q = "SELECT " . ($no_id ? '`id`, `country_name`, `country_3_code`' : '*') . " FROM `#__vikbooking_countries` ORDER BY `country_name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return [];
		}
		$countries = $dbo->loadAssocList();

		if ($tn === true) {
			$vbo_tn = self::getTranslator();
			$vbo_tn->translateContents($countries, '#__vikbooking_countries');
			// re-apply sorting by country name
			$sorting = [];
			foreach ($countries as $country) {
				$sorting[$country['country_name']] = $country;
			}
			ksort($sorting);
			$sorted = [];
			foreach ($sorting as $country) {
				$sorted[] = $country;
			}
			$countries = $sorted;
			unset($sorting, $sorted);
		}

		foreach ($countries as $v) {
			if ($no_id) {
				// keep the original structure by unsetting the ID only needed for translation
				unset($v['id']);
			}
			$all_countries[$v['country_3_code']] = $v;
		}

		return $all_countries;
	}

	public static function getCountriesSelect($name, $all_countries = array(), $current_value = '', $empty_value = ' ')
	{
		if (!count($all_countries)) {
			$all_countries = self::getCountriesArray();
		}

		$countries = '<select name="'.$name.'">'."\n";
		if (strlen($empty_value)) {
			$countries .= '<option value="">'.$empty_value.'</option>'."\n";
		}
		foreach ($all_countries as $v) {
			$countries .= '<option value="'.$v['country_3_code'].'"'.($v['country_3_code'] == $current_value ? ' selected="selected"' : '').'>'.$v['country_name'].'</option>'."\n";
		}
		$countries .= '</select>';

		return $countries;
	}

	public static function getThumbSize($skipsession = false)
	{
		static $thumb_size = null;

		if ($thumb_size) {
			return $thumb_size;
		}

		$thumb_size = VBOFactory::getConfig()->get('thumbsize', 500);

		return $thumb_size;
	}

	/**
	 * Checks whether an iCal file for the reservation should be
	 * attached to the confirmation email for customer and/or admin.
	 * 
	 * @return 	int 	1=admin+customer, 2=admin, 3=customer, 0=no
	 * 
	 * @since 	1.12.0 (J) - 1.2.0 (WP)
	 */
	public static function attachIcal()
	{
		return VBOFactory::getConfig()->getInt('attachical', 1);
	}

	/**
	 * How the calculation of the orphan dates should take place.
	 * 
	 * @return 	string 	"next" for only checking the bookings ahead, "prevnext" if
	 * 					also the previous bookings should be checked.
	 * 
	 * @since 	1.13 (J) - 1.3.0 (WP)
	 */
	public static function orphansCalculation()
	{
		return VBOFactory::getConfig()->get('orphanscalculation', 'next');
	}

	/**
	 * Returns the name of the template file to use for the front-end View "search".
	 * 
	 * @return 	string 	the name of the template file. New and upgraded user will both use the classic file.
	 * 
	 * @since 	1.13 (J) - 1.3.0 (WP)
	 */
	public static function searchResultsTmpl()
	{
		return VBOFactory::getConfig()->get('searchrestmpl', 'classic');
	}

	/**
	 * Returns a string without any new-line characters
	 * to be used for JavaScript values without facing
	 * errors like 'unterminated string literal'.
	 * By passing nl2br($str) as argument, we can keep
	 * the wanted new-line HTML tags for PRE tags. 
	 * We use implode() with just one argument to 
	 * not use an empty string as glue for the string.
	 *
	 * @param 	$str 	string
	 *
	 * @return 	string 	
	 */
	public static function strTrimLiteral($str) {
		$str = str_replace(array("\r\n", "\r"), "\n", $str);
		$lines = explode("\n", $str);
		$new_lines = array();
		foreach ($lines as $i => $line) {
		    if (strlen($line)) {
				$new_lines[] = trim($line);
			}
		}
		return implode($new_lines);
	}

	/**
	 * Returns an instance of the VboApplication class.
	 * 
	 * @return 	VboApplication
	 */
	public static function getVboApplication()
	{
		// require library
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'jv_helper.php';

		// return a new instance
		return new VboApplication();
	}

	/**
	 * Returns the translation of the given 0-indexed week day.
	 * 
	 * @param 	int 	$wd 	the 0-indexed day of the week.
	 * @param 	bool 	$short 	whether to get the short version of the weekday.
	 * 
	 * @return 	string 			the name of the week day.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) added support for short translation.
	 */
	public static function sayWeekDay($wd, $short = false)
	{
		switch ($wd) {
			case '6' :
				$ret = $short ? JText::translate('VBSAT') : JText::translate('VBWEEKDAYSIX');
				break;
			case '5' :
				$ret = $short ? JText::translate('VBFRI') : JText::translate('VBWEEKDAYFIVE');
				break;
			case '4' :
				$ret = $short ? JText::translate('VBTHU') : JText::translate('VBWEEKDAYFOUR');
				break;
			case '3' :
				$ret = $short ? JText::translate('VBWED') : JText::translate('VBWEEKDAYTHREE');
				break;
			case '2' :
				$ret = $short ? JText::translate('VBTUE') : JText::translate('VBWEEKDAYTWO');
				break;
			case '1' :
				$ret = $short ? JText::translate('VBMON') : JText::translate('VBWEEKDAYONE');
				break;
			default :
				$ret = $short ? JText::translate('VBSUN') : JText::translate('VBWEEKDAYZERO');
				break;
		}
		return $ret;
	}

	/**
	 * Returns the translation of the given 1-indexed month of the year.
	 * 
	 * @param 	int 	$idm 	the 1-indexed month of the year.
	 * @param 	bool 	$short 	whether to get the short version of the month.
	 * 
	 * @return 	string 			the name of the month.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) added support for short translation.
	 */
	public static function sayMonth($idm, $short = false)
	{
		switch ($idm) {
			case '12' :
				$ret = $short ? JText::translate('VBSHORTMONTHTWELVE') : JText::translate('VBMONTHTWELVE');
				break;
			case '11' :
				$ret = $short ? JText::translate('VBSHORTMONTHELEVEN') : JText::translate('VBMONTHELEVEN');
				break;
			case '10' :
				$ret = $short ? JText::translate('VBSHORTMONTHTEN') : JText::translate('VBMONTHTEN');
				break;
			case '9' :
				$ret = $short ? JText::translate('VBSHORTMONTHNINE') : JText::translate('VBMONTHNINE');
				break;
			case '8' :
				$ret = $short ? JText::translate('VBSHORTMONTHEIGHT') : JText::translate('VBMONTHEIGHT');
				break;
			case '7' :
				$ret = $short ? JText::translate('VBSHORTMONTHSEVEN') : JText::translate('VBMONTHSEVEN');
				break;
			case '6' :
				$ret = $short ? JText::translate('VBSHORTMONTHSIX') : JText::translate('VBMONTHSIX');
				break;
			case '5' :
				$ret = $short ? JText::translate('VBSHORTMONTHFIVE') : JText::translate('VBMONTHFIVE');
				break;
			case '4' :
				$ret = $short ? JText::translate('VBSHORTMONTHFOUR') : JText::translate('VBMONTHFOUR');
				break;
			case '3' :
				$ret = $short ? JText::translate('VBSHORTMONTHTHREE') : JText::translate('VBMONTHTHREE');
				break;
			case '2' :
				$ret = $short ? JText::translate('VBSHORTMONTHTWO') : JText::translate('VBMONTHTWO');
				break;
			default :
				$ret = $short ? JText::translate('VBSHORTMONTHONE') : JText::translate('VBMONTHONE');
				break;
		}
		return $ret;
	}

	public static function sayDayMonth($d) {
		switch ($d) {
			case '31' :
				$ret = JText::translate('VBDAYMONTHTHIRTYONE');
				break;
			case '30' :
				$ret = JText::translate('VBDAYMONTHTHIRTY');
				break;
			case '29' :
				$ret = JText::translate('VBDAYMONTHTWENTYNINE');
				break;
			case '28' :
				$ret = JText::translate('VBDAYMONTHTWENTYEIGHT');
				break;
			case '27' :
				$ret = JText::translate('VBDAYMONTHTWENTYSEVEN');
				break;
			case '26' :
				$ret = JText::translate('VBDAYMONTHTWENTYSIX');
				break;
			case '25' :
				$ret = JText::translate('VBDAYMONTHTWENTYFIVE');
				break;
			case '24' :
				$ret = JText::translate('VBDAYMONTHTWENTYFOUR');
				break;
			case '23' :
				$ret = JText::translate('VBDAYMONTHTWENTYTHREE');
				break;
			case '22' :
				$ret = JText::translate('VBDAYMONTHTWENTYTWO');
				break;
			case '21' :
				$ret = JText::translate('VBDAYMONTHTWENTYONE');
				break;
			case '20' :
				$ret = JText::translate('VBDAYMONTHTWENTY');
				break;
			case '19' :
				$ret = JText::translate('VBDAYMONTHNINETEEN');
				break;
			case '18' :
				$ret = JText::translate('VBDAYMONTHEIGHTEEN');
				break;
			case '17' :
				$ret = JText::translate('VBDAYMONTHSEVENTEEN');
				break;
			case '16' :
				$ret = JText::translate('VBDAYMONTHSIXTEEN');
				break;
			case '15' :
				$ret = JText::translate('VBDAYMONTHFIFTEEN');
				break;
			case '14' :
				$ret = JText::translate('VBDAYMONTHFOURTEEN');
				break;
			case '13' :
				$ret = JText::translate('VBDAYMONTHTHIRTEEN');
				break;
			case '12' :
				$ret = JText::translate('VBDAYMONTHTWELVE');
				break;
			case '11' :
				$ret = JText::translate('VBDAYMONTHELEVEN');
				break;
			case '10' :
				$ret = JText::translate('VBDAYMONTHTEN');
				break;
			case '9' :
				$ret = JText::translate('VBDAYMONTHNINE');
				break;
			case '8' :
				$ret = JText::translate('VBDAYMONTHEIGHT');
				break;
			case '7' :
				$ret = JText::translate('VBDAYMONTHSEVEN');
				break;
			case '6' :
				$ret = JText::translate('VBDAYMONTHSIX');
				break;
			case '5' :
				$ret = JText::translate('VBDAYMONTHFIVE');
				break;
			case '4' :
				$ret = JText::translate('VBDAYMONTHFOUR');
				break;
			case '3' :
				$ret = JText::translate('VBDAYMONTHTHREE');
				break;
			case '2' :
				$ret = JText::translate('VBDAYMONTHTWO');
				break;
			default :
				$ret = JText::translate('VBDAYMONTHONE');
				break;
		}
		return $ret;
	}

	public static function totElements($arr) {
		$n = 0;
		if (is_array($arr)) {
			foreach ($arr as $a) {
				if (!empty($a)) {
					$n++;
				}
			}
			return $n;
		}
		return false;
	}

	/**
	 * Returns a list of documents that were uploaded
	 * for the specified customer.
	 *
	 * @param 	integer  $id  The customer ID.
	 *
	 * @return 	array 	 A list of documents.
	 * 
	 * @since 	1.3.0
	 */
	public static function getCustomerDocuments($id)
	{
		$dbo = JFactory::getDbo();

		$q = $dbo->getQuery(true)
			->select($dbo->qn('docsfolder'))
			->from($dbo->qn('#__vikbooking_customers'))
			->where($dbo->qn('id') . ' = ' . (int) $id);

		$dbo->setQuery($q, 0, 1);
		$dbo->execute();

		if (!$dbo->getNumRows())
		{
			// customer not found
			return array();
		}

		// retrieve customer documents directory name
		$dirname = $dbo->loadResult();

		if (empty($dirname))
		{
			// no available directory
			return array();
		}

		// build documents folder path
		$dirname = VBO_CUSTOMERS_PATH . DIRECTORY_SEPARATOR . $dirname;

		if (!is_dir($dirname))
		{
			// the customer directory doesn't exist
			return array();
		}

		// read all files from customer directory
		$glob = glob($dirname . DIRECTORY_SEPARATOR . '*');

		$files = array();

		foreach ($glob as $path)
		{
			// skip "index.html"
			if (!preg_match("/[\/\\\\]index\.html$/i", $path))
			{
				// extract name and extension from file path
				if (preg_match("/(.*)\.([a-z0-9]{2,})$/i", basename($path), $match))
				{
					$name = $match[1];
					$ext  = $match[2];
				}
				else
				{
					$name = basename($path);
					$ext  = '';
				}

				$file = new stdClass;
				$file->path     = $path;
				$file->name     = $name;
				$file->ext      = $ext;
				$file->basename = $file->name . '.' . $file->ext;
				$file->size     = filesize($path);
				$file->date     = filemtime($path);
				$file->url 		= str_replace(DIRECTORY_SEPARATOR, '/', str_replace(VBO_CUSTOMERS_PATH . DIRECTORY_SEPARATOR, VBO_CUSTOMERS_URI, $file->path));

				$files[] = $file;
			}
		}

		// sort files by creation date
		usort($files, function($a, $b)
		{
			return $b->date - $a->date;
		});

		return $files;
	}
	
	public static function displayPaymentParameters($pfile, $pparams = '') {
		$html = '<p>---------</p>';

		/**
		 * @wponly 	The payment gateway is now loaded 
		 * 			using the apposite dispatcher.
		 *
		 * @since 1.0.5
		 */
		JLoader::import('adapter.payment.dispatcher');

		try
		{
			$payment = JPaymentDispatcher::getInstance('vikbooking', $pfile);
		}
		catch (Exception $e)
		{
			// payment not found
			$html = $e->getMessage();

			if ($code = $e->getCode())
			{
				$html = '<b>' . $code . '</b> : ' . $html;
			}

			return $html;
		}
		//

		$arrparams = !empty($pparams) ? json_decode($pparams, true) : array();
		$arrparams = !is_array($arrparams) ? array() : $arrparams;
		
		// get admin parameters
		$pconfig = $payment->getAdminParameters();

		if (count($pconfig) > 0) {
			$html = '';
			foreach ($pconfig as $value => $cont) {
				if (empty($value)) {
					continue;
				}
				$labelparts = explode('//', (isset($cont['label']) ? $cont['label'] : ''));
				$label = $labelparts[0];
				$labelhelp = isset($labelparts[1]) ? $labelparts[1] : '';
				if (!empty($cont['help'])) {
					$labelhelp = $cont['help'];
				}
				$default_paramv = isset($cont['default']) ? $cont['default'] : null;
				$html .= '<div class="vbo-param-container">';
				if (strlen($label) > 0 && (!isset($cont['hidden']) || $cont['hidden'] != true)) {
					$html .= '<div class="vbo-param-label">'.$label.'</div>';
				}
				$html .= '<div class="vbo-param-setting">';
				switch ($cont['type']) {
					case 'custom':
						$html .= $cont['html'];
						break;
					case 'select':
						$options = isset($cont['options']) && is_array($cont['options']) ? $cont['options'] : array();
						$is_assoc = (array_keys($options) !== range(0, count($options) - 1));
						if (isset($cont['multiple']) && $cont['multiple']) {
							$html .= '<select name="vikpaymentparams['.$value.'][]" multiple="multiple">';
						} else {
							$html .= '<select name="vikpaymentparams['.$value.']">';
						}
						foreach ($options as $optkey => $poption) {
							$checkval = $is_assoc ? $optkey : $poption;
							$selected = false;
							if (isset($arrparams[$value])) {
								if (is_array($arrparams[$value])) {
									$selected = in_array($checkval, $arrparams[$value]);
								} else {
									$selected = ($checkval == $arrparams[$value]);
								}
							} elseif (isset($default_paramv)) {
								if (is_array($default_paramv)) {
									$selected = in_array($checkval, $default_paramv);
								} else {
									$selected = ($checkval == $default_paramv);
								}
							}
							$html .= '<option value="' . ($is_assoc ? $optkey : $poption) . '"'.($selected ? ' selected="selected"' : '').'>'.$poption.'</option>';
						}
						$html .= '</select>';
						break;
					case 'password':
						$html .= '<div class="btn-wrapper input-append">';
						$html .= '<input type="password" name="vikpaymentparams['.$value.']" value="'.(isset($arrparams[$value]) ? JHtml::fetch('esc_attr', $arrparams[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"/>';
						$html .= '<button type="button" class="btn btn-primary" onclick="vikPaymentParamTogglePwd(this);"><i class="' . VikBookingIcons::i('eye') . '"></i></button>';
						$html .= '</div>';
						break;
					case 'number':
						$number_attr = array();
						if (isset($cont['min'])) {
							$number_attr[] = 'min="' . JHtml::fetch('esc_attr', $cont['min']) . '"';
						}
						if (isset($cont['max'])) {
							$number_attr[] = 'max="' . JHtml::fetch('esc_attr', $cont['max']) . '"';
						}
						if (isset($cont['step'])) {
							$number_attr[] = 'step="' . JHtml::fetch('esc_attr', $cont['step']) . '"';
						}
						$html .= '<input type="number" name="vikpaymentparams['.$value.']" value="'.(isset($arrparams[$value]) ? JHtml::fetch('esc_attr', $arrparams[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" ' . implode(' ', $number_attr) . '/>';
						break;
					case 'textarea':
						$html .= '<textarea name="vikpaymentparams['.$value.']">'.(isset($arrparams[$value]) ? JHtml::fetch('esc_textarea', $arrparams[$value]) : JHtml::fetch('esc_textarea', $default_paramv)).'</textarea>';
						break;
					case 'hidden':
						$html .= '<input type="hidden" name="vikpaymentparams['.$value.']" value="'.(isset($arrparams[$value]) ? JHtml::fetch('esc_attr', $arrparams[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'"/>';
						break;
					case 'checkbox':
						// always display a hidden input value turned off before the actual checkbox to support the "off" (0) status
						$html .= '<input type="hidden" name="vikpaymentparams['.$value.']" value="0" />';
						$html .= self::getVboApplication()->printYesNoButtons('vikpaymentparams['.$value.']', JText::translate('VBYES'), JText::translate('VBNO'), (isset($arrparams[$value]) ? (int)$arrparams[$value] : (int)$default_paramv), 1, 0);
						break;
					default:
						$html .= '<input type="text" name="vikpaymentparams['.$value.']" value="'.(isset($arrparams[$value]) ? JHtml::fetch('esc_attr', $arrparams[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"/>';
						break;
				}
				if (strlen($labelhelp) > 0) {
					$html .= '<span class="vbo-param-setting-comment">'.$labelhelp.'</span>';
				}
				$html .= '</div>';
				$html .= '</div>';
			}
			// JS helper function to toggle the password fields
			$html .= "\n" . '<script>' . "\n";
			$html .= 'function vikPaymentParamTogglePwd(elem) {' . "\n";
			$html .= '	var btn = jQuery(elem), inp = btn.parent().find("input").first();' . "\n";
			$html .= '	if (!inp || !inp.length) {return false;}' . "\n";
			$html .= '	var inp_type = inp.attr("type");' . "\n";
			$html .= '	inp.attr("type", (inp_type == "password" ? "text" : "password"));' . "\n";
			$html .= '}' . "\n";
			$html .= "\n" . '</script>' . "\n";
		}

		return $html;
	}

	public static function displaySMSParameters($pfile, $params = null)
	{
		$html = '---------';

		$params = is_string($params) ? json_decode($params, true) : $params;
		$params = is_array($params) ? $params : [];

		if (empty($pfile) || !is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'smsapi' . DIRECTORY_SEPARATOR . $pfile)) {
			return $html;
		}

		// attempt to load the class file
		require_once(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'smsapi' . DIRECTORY_SEPARATOR . $pfile);

		if (!class_exists('VikSmsApi') || !method_exists('VikSmsApi', 'getAdminParameters')) {
			return $html;
		}

		// load the gateway parameters
		$config = VikSmsApi::getAdminParameters();

		if (!is_array($config) || !count($config)) {
			return $html;
		}

		// flags for JS helpers
		$js_helpers = array();

		$html = '';
		foreach ($config as $value => $cont) {
			if (empty($value)) {
				continue;
			}
			$inp_attr = '';
			if (isset($cont['attributes'])) {
				foreach ($cont['attributes'] as $inpk => $inpv) {
					$inp_attr .= $inpk.'="'.$inpv.'" ';
				}
				$inp_attr = ' ' . rtrim($inp_attr);
			}
			$labelparts = explode('//', (isset($cont['label']) ? $cont['label'] : ''));
			$label = $labelparts[0];
			$labelhelp = isset($labelparts[1]) ? $labelparts[1] : '';
			if (!empty($cont['help'])) {
				$labelhelp = $cont['help'];
			}
			$default_paramv = isset($cont['default']) ? $cont['default'] : null;
			$html .= '<div class="vbo-param-container' . (in_array($cont['type'], array('textarea', 'visual_html')) ? ' vbo-param-container-full' : '') . '">';
			if (strlen($label) > 0 && (!isset($cont['hidden']) || $cont['hidden'] != true)) {
				$html .= '<div class="vbo-param-label">'.$label.'</div>';
			}
			$html .= '<div class="vbo-param-setting">';
			switch ($cont['type']) {
				case 'custom':
					$html .= $cont['html'];
					break;
				case 'select':
					$options = isset($cont['options']) && is_array($cont['options']) ? $cont['options'] : array();
					$is_assoc = (array_keys($options) !== range(0, count($options) - 1));
					if (isset($cont['multiple']) && $cont['multiple']) {
						$html .= '<select name="viksmsparams['.$value.'][]" multiple="multiple"' . $inp_attr . '>';
					} else {
						$html .= '<select name="viksmsparams['.$value.']"' . $inp_attr . '>';
					}
					foreach ($options as $optkey => $poption) {
						$checkval = $is_assoc ? $optkey : $poption;
						$selected = false;
						if (isset($params[$value])) {
							if (is_array($params[$value])) {
								$selected = in_array($checkval, $params[$value]);
							} else {
								$selected = ($checkval == $params[$value]);
							}
						} elseif (isset($default_paramv)) {
							if (is_array($default_paramv)) {
								$selected = in_array($checkval, $default_paramv);
							} else {
								$selected = ($checkval == $default_paramv);
							}
						}
						$html .= '<option value="' . ($is_assoc ? $optkey : $poption) . '"'.($selected ? ' selected="selected"' : '').'>'.$poption.'</option>';
					}
					$html .= '</select>';
					break;
				case 'password':
					$html .= '<div class="btn-wrapper input-append">';
					$html .= '<input type="password" name="viksmsparams['.$value.']" value="'.(isset($params[$value]) ? JHtml::fetch('esc_attr', $params[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"' . $inp_attr . '/>';
					$html .= '<button type="button" class="btn btn-primary" onclick="vikSMSParamTogglePwd(this);"><i class="' . VikBookingIcons::i('eye') . '"></i></button>';
					$html .= '</div>';
					// set flag for JS helper
					$js_helpers[] = $cont['type'];
					break;
				case 'number':
					$number_attr = array();
					if (isset($cont['min'])) {
						$number_attr[] = 'min="' . JHtml::fetch('esc_attr', $cont['min']) . '"';
					}
					if (isset($cont['max'])) {
						$number_attr[] = 'max="' . JHtml::fetch('esc_attr', $cont['max']) . '"';
					}
					if (isset($cont['step'])) {
						$number_attr[] = 'step="' . JHtml::fetch('esc_attr', $cont['step']) . '"';
					}
					$html .= '<input type="number" name="viksmsparams['.$value.']" value="'.(isset($params[$value]) ? JHtml::fetch('esc_attr', $params[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" ' . implode(' ', $number_attr) . $inp_attr . '/>';
					break;
				case 'textarea':
					$html .= '<textarea name="viksmsparams['.$value.']"' . $inp_attr . '>'.(isset($params[$value]) ? JHtml::fetch('esc_textarea', $params[$value]) : JHtml::fetch('esc_textarea', $default_paramv)).'</textarea>';
					break;
				case 'visual_html':
					$tarea_cont = isset($params[$value]) ? JHtml::fetch('esc_textarea', $params[$value]) : JHtml::fetch('esc_textarea', $default_paramv);
					$tarea_attr = isset($cont['attributes']) && is_array($cont['attributes']) ? $cont['attributes'] : array();
					$editor_opts = isset($cont['editor_opts']) && is_array($cont['editor_opts']) ? $cont['editor_opts'] : array();
					$editor_btns = isset($cont['editor_btns']) && is_array($cont['editor_btns']) ? $cont['editor_btns'] : array();
					$html .= self::getVboApplication()->renderVisualEditor('viksmsparams[' . $value . ']', $tarea_cont, $tarea_attr, $editor_opts, $editor_btns);
					break;
				case 'hidden':
					$html .= '<input type="hidden" name="viksmsparams['.$value.']" value="'.(isset($params[$value]) ? JHtml::fetch('esc_attr', $params[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'"' . $inp_attr . '/>';
					break;
				case 'checkbox':
					// always display a hidden input value turned off before the actual checkbox to support the "off" (0) status
					$html .= '<input type="hidden" name="viksmsparams['.$value.']" value="0" />';
					$html .= self::getVboApplication()->printYesNoButtons('viksmsparams['.$value.']', JText::translate('VBYES'), JText::translate('VBNO'), (isset($params[$value]) ? (int)$params[$value] : (int)$default_paramv), 1, 0);
					break;
				default:
					$html .= '<input type="text" name="viksmsparams['.$value.']" value="'.(isset($params[$value]) ? JHtml::fetch('esc_attr', $params[$value]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"' . $inp_attr . '/>';
					break;
			}
			if (strlen($labelhelp) > 0) {
				$html .= '<span class="vbo-param-setting-comment">'.$labelhelp.'</span>';
			}
			$html .= '</div>';
			$html .= '</div>';
		}

		// JS helper functions
		if (in_array('password', $js_helpers)) {
			// toggle the password fields
			$html .= "\n" . '<script>' . "\n";
			$html .= 'function vikSMSParamTogglePwd(elem) {' . "\n";
			$html .= '	var btn = jQuery(elem), inp = btn.parent().find("input").first();' . "\n";
			$html .= '	if (!inp || !inp.length) {return false;}' . "\n";
			$html .= '	var inp_type = inp.attr("type");' . "\n";
			$html .= '	inp.attr("type", (inp_type == "password" ? "text" : "password"));' . "\n";
			$html .= '}' . "\n";
			$html .= "\n" . '</script>' . "\n";
		}

		return $html;
	}

	/**
	 * Renders the params of the requested cron job file-class.
	 * 
	 * @param 	string 	$file 	 the name of the cron job driver file.
	 * @param 	array 	$params  the parameters array.
	 *  
	 * @return 	string 	the necessary HTML content to render.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP) - support for new fields added.
	 */
	public static function displayCronParameters($file, $params = [])
	{
		try
		{
			// attempt to create a new instance
			$job = VBOFactory::getCronFactory()->createInstance($file);
		}
		catch (Exception $e)
		{
			// something went wrong, display error message
			return '<p>' . $e->getMessage() . '<p>';
		}

		// get admin parameters
		$config = $job->getForm();

		if (!is_array($config) || !$config) {
			return '<p>---------</p>';
		}

		/**
		 * Render the cron parameters through the global params rendering class.
		 * 
		 * @since 	1.16.9 (J) - 1.6.9 (WP)
		 */
		return VBOParamsRendering::getInstance($config, $params)->setInputName('vikcronparams')->getHtml();
	}
	
	public static function invokeChannelManager($skiporder = true, $order = array()) {
		$task = VikRequest::getString('task', '', 'request');
		$view = VikRequest::getString('view', '', 'request');
		$tmpl = VikRequest::getString('tmpl', '', 'request');
		$noimpression = array('vieworder', 'booking');
		if ($tmpl != 'component' && (!$skiporder || (!in_array($task, $noimpression) && !in_array($view, $noimpression))) && file_exists(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			//VCM Channel Impression
			if (!class_exists('VikChannelManagerConfig')) {
				require_once(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'vcm_config.php');
			}
			if (!class_exists('VikChannelManager')) {
				require_once(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php');
			}
			VikChannelManager::invokeChannelImpression();
		} elseif ($tmpl != 'component' && count($order) > 0 && file_exists(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php')) {
			//VCM Channel Conversion-Impression
			if (!class_exists('VikChannelManagerConfig')) {
				require_once(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'vcm_config.php');
			}
			if (!class_exists('VikChannelManager')) {
				require_once(VCM_SITE_PATH.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'lib.vikchannelmanager.php');
			}
			VikChannelManager::invokeChannelConversionImpression($order);
		}
	}

	public static function validEmail($email) {
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		} else {
			$domain = substr($email, $atIndex +1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else
				if ($domainLen < 1 || $domainLen > 255) {
					// domain part length exceeded
					$isValid = false;
				} else
					if ($local[0] == '.' || $local[$localLen -1] == '.') {
						// local part starts or ends with '.'
						$isValid = false;
					} else
						if (preg_match('/\\.\\./', $local)) {
							// local part has two consecutive dots
							$isValid = false;
						} else
							if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
								// character not valid in domain part
								$isValid = false;
							} else
								if (preg_match('/\\.\\./', $domain)) {
									// domain part has two consecutive dots
									$isValid = false;
								} else
									if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
										// character not valid in local part unless 
										// local part is quoted
										if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
											$isValid = false;
										}
									}
			if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
				// domain not found in DNS
				$isValid = false;
			}
		}
		return $isValid;
	}

	public static function caniWrite($path) {
		if ($path[strlen($path) - 1] == '/') {
			// ricorsivo return a temporary file path
			return self::caniWrite($path . uniqid(mt_rand()) . '.tmp');
		}
		if (is_dir($path)) {
			return self::caniWrite($path . '/' . uniqid(mt_rand()) . '.tmp');
		}
		// check tmp file for read/write capabilities
		$rm = file_exists($path);
		$f = @fopen($path, 'a');
		if ($f === false) {
			return false;
		}
		fclose($f);
		if (!$rm) {
			unlink($path);
		}
		return true;
	}

	/**
	 * Alias method of JFile::upload to unify any
	 * upload function into one.
	 * 
	 * @param   string   $src 			The name of the php (temporary) uploaded file.
	 * @param   string   $dest 			The path (including filename) to move the uploaded file to.
	 * @param   boolean  [$copy_only] 	Whether to skip the file upload and just copy the file.
	 * 
	 * @return  boolean  True on success.
	 * 
	 * @since 	1.10 - Revision April 24th 2018 for compatibility with the VikWP Framework.
	 * 			@wponly 1.0.7 added the third $copy_only argument to remove the use of copy()
	 */
	public static function uploadFile($src, $dest, $copy_only = false) {
		// always attempt to include the File class
		jimport('joomla.filesystem.file');

		// upload the file
		if (!$copy_only) {
			$result = JFile::upload($src, $dest);
		} else {
			// this is to avoid the use of the PHP function copy() and allow files mirroring in WP (triggerUploadBackup)
			$result = JFile::copy($src, $dest);
		}

		/**
		 * @wponly  in order to not lose uploaded files after installing an update,
		 * 			we need to move any uploaded file onto a recovery folder.
		 */
		if ($result) {
			VikBookingLoader::import('update.manager');
			VikBookingUpdateManager::triggerUploadBackup($dest);
		}
		//

		// return upload result
		return $result;
	}

	/**
	 * Helper method used to upload the given file (retrieved from $_FILES)
	 * into the specified destination.
	 *
	 * @param 	array 	$file 		An associative array with the file details.
	 * @param 	string 	$dest 		The destination path.
	 * @param 	string 	$filters 	A string (or a regex) containing the allowed extensions.
	 *
	 * @return 	array 	The uploading result.
	 *
	 * @throws  RuntimeException
	 * 
	 * @since 	1.3.0
	 */
	public static function uploadFileFromRequest($file, $dest, $filters = '*')
	{
		jimport('joomla.filesystem.file');

		$dest = rtrim($dest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		
		if (empty($file['name']))
		{
			throw new RuntimeException('Missing file', 400);
		}

		/**
		 * Make sure the upload of the file didn't raise any errors.
		 * 
		 * @link https://www.php.net/manual/en/features.file-upload.errors.php
		 */
		if ((int) $file['error'])
		{
			if ($file['error'] == UPLOAD_ERR_INI_SIZE)
			{
				$error = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
			}
			else if ($file['error'] == UPLOAD_ERR_FORM_SIZE)
			{
				$error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
			}
			else if ($file['error'] == UPLOAD_ERR_PARTIAL)
			{
				$error = 'The uploaded file was only partially uploaded.';
			}
			else if ($file['error'] == UPLOAD_ERR_NO_FILE)
			{
				$error = 'No file was uploaded.';
			}
			else if ($file['error'] == UPLOAD_ERR_NO_TMP_DIR)
			{
				$error = 'Missing a temporary folder.';
			}
			else if ($file['error'] == UPLOAD_ERR_CANT_WRITE)
			{
				$error = 'Failed to write file to disk.';
			}
			else if ($file['error'] == UPLOAD_ERR_EXTENSION)
			{
				$error = 'A PHP extension stopped the file upload.';
			}
			else
			{
				$error = 'Unknown error.';
			}

			throw new RuntimeException($error, 500);
		}

		$src = $file['tmp_name'];

		// extract file name and extension
		if (preg_match("/(.*?)(\.[0-9a-z]{2,})$/i", basename($file['name']), $match))
		{
			$filename = $match[1];
			$fileext  = $match[2];
		}
		else
		{
			// probably no extension provided
			$filename = basename($file['name']);
			$fileext  = '';
		}

		$j = '';
		
		if (file_exists($dest . $filename . $fileext))
		{
			$j = 2;

			while (file_exists($dest . $filename . '-' . $j . $fileext))
			{
				$j++;
			}

			$j = '-' . $j;
		}

		$finaldest = $dest . $filename . $j . $fileext;

		if ($filters !== '*')
		{
			$ext = $file['type'];

			// check if we have a regex
			if (preg_match("/^[#\/]/", $filters) && preg_match("/[#\/][a-z]*$/", $filters))
			{
				if (!preg_match($filters, $ext))
				{
					// extension not supported
					throw new RuntimeException(sprintf('Extension [%s] is not supported', $ext), 400);
				}
			}
			else
			{
				// get all supported types
				$types = array_map('strtolower', array_filter(explode(',', $filters)));

				if (!in_array($ext, $types))
				{
					// extension not supported
					throw new RuntimeException(sprintf('Extension [%s] is not supported', $ext), 400);
				}
			}
		}
		
		// try to upload the file
		if (!JFile::upload($src, $finaldest, $use_streams = false, $allow_unsafe = true))
		{
			throw new RuntimeException(sprintf('Unable to upload the file [%s] to [%s]', $src, $finaldest), 500);
		}

		$file = new stdClass;
		$file->name     = $filename . $j;
		$file->ext      = ltrim($fileext, '.');
		$file->filename = basename($finaldest);
		$file->path     = $finaldest;
		
		return $file;
	}

	/**
	 * Gets the instance of a specific report class.
	 * 
	 * @param 	string 	$report 	the name of the report to load.
	 * 
	 * @return 	mixed 	false or report object instance.
	 * 
	 * @since 	1.3.0
	 */
	public static function getReportInstance($report)
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR . 'report.php';

		return VikBookingReport::getInstanceOf($report);
	}

	/**
	 * Checks whether the guest reviews are enabled and VCM is installed.
	 * 
	 * @return 	boolean 	true if enabled, false otherwise.
	 * 
	 * @since 	1.3.0
	 */
	public static function allowGuestReviews()
	{
		$dbo = JFactory::getDbo();
		$vcm_installed = is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php');
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='grenabled';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$s = $dbo->loadResult();
			return ((int)$s === 1 && $vcm_installed);
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('grenabled', '1');";
		$dbo->setQuery($q);
		$dbo->execute();
		return $vcm_installed;
	}

	/**
	 * Gets the minimum chars for the guest review message.
	 * 
	 * @return 	int 	minimum number of chars for the comment (0 = no limits, -1 = disabled).
	 * 
	 * @since 	1.3.0
	 */
	public static function guestReviewMinChars()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='grminchars';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return (int)$dbo->loadResult();
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('grminchars', '15');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 15;
	}

	/**
	 * The approval type for new guest reviews.
	 * 
	 * @return 	string 		auto or manual.
	 * 
	 * @since 	1.3.0
	 */
	public static function guestReviewsApproval()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='grappr';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return $dbo->loadResult() == 'manual' ? 'manual' : 'auto';
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('grappr', 'auto');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 'auto';
	}

	/**
	 * The type of reviews guests should leave.
	 * 
	 * @return 	string 		global or service.
	 * 
	 * @since 	1.3.0
	 */
	public static function guestReviewsType()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='grtype';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows() > 0) {
			return $dbo->loadResult() == 'global' ? 'global' : 'service';
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('grtype', 'service');";
		$dbo->setQuery($q);
		$dbo->execute();
		return 'service';
	}

	/**
	 * The services to be reviewed by the guests.
	 * 
	 * @return 	array 		list, empty or not, of the services to be reviewed.
	 * 
	 * @since 	1.3.0
	 */
	public static function guestReviewsServices()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `id`,`service_name` FROM `#__vikbooking_greview_service` GROUP BY `service_name` ORDER BY `id` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return $dbo->loadAssocList();
		}
		// insert the default services
		$default_services = array(
			JText::translate('VBOGREVVALUE'),
			JText::translate('VBOGREVLOCATION'),
			JText::translate('VBOGREVSTAFF'),
			JText::translate('VBOGREVCLEAN'),
			JText::translate('VBOGREVCOMFORT'),
			JText::translate('VBOGREVFACILITIES'),
		);
		foreach ($default_services as $def_service) {
			$q = "INSERT INTO `#__vikbooking_greview_service` (`service_name`) VALUES (" . $dbo->quote($def_service) . ");";
			$dbo->setQuery($q);
			$dbo->execute();
		}
		return array();
	}

	/**
	 * Checks whether the guest reviews should be downloaded (max 1 per day).
	 * VCM must be installed in order to download the new reviews.
	 * 
	 * @return 	int 	-1 if VCM is not installed, 0 if already downloaded, 1 for download.
	 * 
	 * @since 	1.3.0
	 */
	public static function shouldDownloadReviews()
	{
		if (!is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php')) {
			return -1;
		}

		$today = date('Y-m-d');

		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='gr_last_download' LIMIT 1;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$last_download = $dbo->loadResult();
			// update last download day
			$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $dbo->quote($today) . " WHERE `param`='gr_last_download';";
			$dbo->setQuery($q);
			$dbo->execute();
			//
			return ($last_download != $today ? 1 : 0);
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('gr_last_download', " . $dbo->quote($today) . ");";
		$dbo->setQuery($q);
		$dbo->execute();
		return 1;
	}

	/**
	 * Tells whether a booking can be reviewed.
	 * 
	 * @param 	array 		$booking 	booking array details.
	 * 
	 * @return 	boolean 	true if a review can be left, false otherwise.
	 * 
	 * @since 	1.3.0
	 */
	public static function canBookingBeReviewed($booking)
	{
		// reviews must be enabled and supported
		if (!self::allowGuestReviews()) {
			return false;
		}

		// booking status must be confirmed
		if ($booking['status'] != 'confirmed') {
			return false;
		}

		// review can be left starting from the check-out day
		$checkout_info = getdate($booking['checkout']);
		$checkout_midn = mktime(0, 0, 0, $checkout_info['mon'], $checkout_info['mday'], $checkout_info['year']);
		if (time() < $checkout_midn) {
			return false;
		}

		// make sure a review for this booking does not exist
		$noreview = true;
		$dbo = JFactory::getDbo();
		try {
			$q = "SELECT `id` FROM `#__vikchannelmanager_otareviews` WHERE `idorder`=" . $dbo->quote($booking['id']) . ";";
			$dbo->setQuery($q);
			$dbo->execute();
			$noreview = ($dbo->getNumRows() < 1);
		} catch (Exception $e) {
			// if the query fails, we do not allow the review to be left
			$noreview = false;
		}

		return $noreview;
	}

	/**
	 * Gets the review for a booking, no matter if it's published or not.
	 * Review will be returned only if reviews are enabled and supported.
	 * Only reviews left through the website, no OTA reviews as this method
	 * should be used in the front-end to display the review to the guest.
	 * 
	 * @param 	array 		$booking 	booking array details.
	 * 
	 * @return 	mixed 		associative array or false.
	 * 
	 * @since 	1.3.0
	 */
	public static function getBookingReview($booking)
	{
		// reviews must be enabled and supported
		if (!self::allowGuestReviews()) {
			return false;
		}

		if (empty($booking['id'])) {
			return false;
		}

		// make sure a review for this booking exists
		$dbo = JFactory::getDbo();
		$review = array();
		try {
			$q = "SELECT * FROM `#__vikchannelmanager_otareviews` WHERE `idorder`=" . $dbo->quote($booking['id']) . " LIMIT 1;";
			$dbo->setQuery($q);
			$dbo->execute();
			$review = $dbo->getNumRows() ? $dbo->loadAssoc() : $review;
		} catch (Exception $e) {
			// query has failed
			$review = array();
		}

		if (count($review)) {
			// make sure the review was left from the website
			$review['uniquekey'] = (int)$review['uniquekey'];
		}
		
		if (count($review) && !empty($review['uniquekey'])) {
			// this is an OTA review
			return false;
		}

		if (count($review) && !empty($review['content'])) {
			// decode content
			$review['content'] = json_decode($review['content'], true);
		}

		return count($review) ? $review : false;
	}

	/**
	 * Attempts to find a translation for a raw customer data label.
	 * Bookings downloaded from the OTAs will save a raw string of information
	 * composed of pairs of label-value separated by new line feeds.
	 * We try to translate the given label into the current language.
	 * 
	 * @param 	string 	$label 	the raw string label to translate.
	 * 
	 * @return 	string 			either the original or the translated label.
	 * 
	 * @since 	1.3.5
	 * @since 	1.4.0  			back-end support added.
	 */
	public static function tnCustomerRawDataLabel($label)
	{
		// this is a map of the known labels
		$known_lbls = array(
			'NAME' => 'VBOCUSTOMERNOMINATIVE',
			'LASTNAME' => 'ORDER_LNAME',
			'COUNTRY' => 'ORDER_STATE',
			'EMAIL' => 'VBMAIL',
			'TELEPHONE' => 'VBPHONE',
			'PHONE' => 'VBPHONE',
			'SPECIALREQUEST' => 'ORDER_SPREQUESTS',
			'MEAL_PLAN' => 'VBOMEALPLAN',
			'CITY' => 'VBCITY',
			'BEDPREFERENCE' => 'VBOBEDPREFERENCE',
			'BOOKER_IS_GENIUS' => 'VBOBOOKERISGENIUS',
		);

		if (self::isAdmin()) {
			// override the map of known translation strings
			$known_lbls = array(
				'NAME' => 'ORDER_NAME',
				'LASTNAME' => 'ORDER_LNAME',
				'COUNTRY' => 'ORDER_STATE',
				'ADDRESS' => 'ORDER_ADDRESS',
				'CITY' => 'ORDER_CITY',
				'LOCATION' => 'ORDER_CITY',
				'EMAIL' => 'ORDER_EMAIL',
				'TELEPHONE' => 'ORDER_PHONE',
				'PHONE' => 'ORDER_PHONE',
				'SPECIALREQUEST' => 'ORDER_SPREQUESTS',
			);
		}

		// we get rid of any empty space by keeping the underscores
		$converted = str_replace(' ', '', strtoupper($label));

		if (isset($known_lbls[$converted])) {
			// this language definition has been mapped
			return JText::translate($known_lbls[$converted]);
		}

		// we try to guess the translation string by prepending VBO
		$guessed = JText::translate('VBO' . $converted);
		if ($guessed != 'VBO' . $converted) {
			// the label was translated correctly
			return $guessed;
		}

		// this label could not be translated, so we return the plain string
		return $label;
	}

	/**
	 * We check whether some bookings are available for import from third party plugins.
	 * 
	 * @return 	mixed 		array with list of plugins supported, false otherwise.
	 * 
	 * @wponly 				this method is only useful for WordPress.
	 * 
	 * @since 	1.3.5
	 */
	public static function canImportBookingsFromThirdPartyPlugins()
	{
		$dbo = JFactory::getDbo();

		$plugins = array();

		/**
		 * As requested from hundreds of our clients, for the moment we check only the custom
		 * post types of type "mphb_booking" to see if some bookings are available for import.
		 */
		$q = "SELECT `post_type` FROM `#__posts` WHERE `post_type`=" . $dbo->quote('mphb_booking') . ";";
		$dbo->setQuery($q);
		$dbo->execute();

		if (!$dbo->getNumRows()) {
			return false;
		}

		// push this third party plugin
		$plugins['mphb'] = 'MotoPress Hotel Booking';

		return count($plugins) ? $plugins : false;
	}

	/**
	 * This method returns a list of the known languages sorted by the
	 * administrator custom preferences. Useful for the phone input fields.
	 * 
	 * @param 	boolean 	$code_assoc 	whether to get an associative array with the lang name.
	 * 
	 * @return 	array 		the sorted list of preferred countries.
	 * 
	 * @since 	1.3.11
	 */
	public static function preferredCountriesOrdering($code_assoc = false)
	{
		$preferred_countries = array();

		// try to get the preferred countries from db
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='preferred_countries';";
		$dbo->setQuery($q);
		$setting = $dbo->loadResult();
		if (!$setting) {
			// create empty configuration record
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('preferred_countries', '[]');";
			$dbo->setQuery($q);
			$dbo->execute();
		} else {
			$preferred_countries = json_decode($setting);
		}

		// get the default known languages
		$sorted_known_langs = self::getVboApplication()->getKnownLanguages();
		
		if (!is_array($preferred_countries) || !count($preferred_countries)) {
			// sort the default known languages by country code alphabetically
			ksort($sorted_known_langs);
			foreach ($sorted_known_langs as $k => $v) {
				$langsep = strpos($k, '_') !== false ? '_' : '-';
				$langparts = explode($langsep, $k);
				array_push($preferred_countries, isset($langparts[1]) ? strtolower($langparts[1]) : strtolower($langparts[0]));
			}
			// update the database record
			$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $dbo->quote(json_encode($preferred_countries)) . " WHERE `param`='preferred_countries';";
			$dbo->setQuery($q);
			$dbo->execute();
		}

		if ($code_assoc) {
			// this is useful for displaying the preferred countries codes together with the language name
			$map = array();
			foreach ($preferred_countries as $ccode) {
				// look for the current country code in the keys of the known language tags
				$match_found = false;
				foreach ($sorted_known_langs as $langtag => $langinfo) {
					$langsep = strpos($langtag, '_') !== false ? '_' : '-';
					$langparts = explode($langsep, $langtag);
					if (isset($langparts[1]) && strtoupper($ccode) == strtoupper($langparts[1])) {
						// match found
						$match_found = true;
						$map[$ccode] = !empty($langinfo['nativeName']) ? $langinfo['nativeName'] : $langinfo['name'];
					} elseif (strtoupper($ccode) == strtoupper($langparts[0])) {
						// match found
						$match_found = true;
						$map[$ccode] = !empty($langinfo['nativeName']) ? $langinfo['nativeName'] : $langinfo['name'];
					}
				}
				if (!$match_found) {
					// in case someone would like to add a custom country code via DB, we allow to do so by returning the raw value
					$map[$ccode] = strtoupper($ccode);
				}
			}
			if (count($map)) {
				// set the associatve array to be returned
				$preferred_countries = $map;
			}
		}

		return $preferred_countries;
	}

	/**
	 * Gets the instance of the admin widgets helper class.
	 * 
	 * @return 	VikBookingHelperAdminWidgets
	 * 
	 * @since 	1.4.0
	 */
	public static function getAdminWidgetsInstance()
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'admin_widgets.php';

		return VikBookingHelperAdminWidgets::getInstance();
	}

	/**
	 * Gets the instance of the conditional rules helper class.
	 * 
	 * @param 	bool 	$require_only 	whether to return the object.
	 * 
	 * @return 	mixed 	VikBookingHelperConditionalRules or true.
	 * 
	 * @since 	1.4.0
	 */
	public static function getConditionalRulesInstance($require_only = false)
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'conditional_rules.php';

		return $require_only ? true : VikBookingHelperConditionalRules::getInstance();
	}

	/**
	 * Gets the instance of the geocoding helper class.
	 * 
	 * @param 	bool 	$require_only 	whether to return the object.
	 * 
	 * @return 	mixed 	VikBookingHelperGeocoding or true.
	 * 
	 * @since 	1.4.0
	 */
	public static function getGeocodingInstance($require_only = false)
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'geocoding.php';

		return $require_only ? true : VikBookingHelperGeocoding::getInstance();
	}

	/**
	 * Helper method to cope with the removal of the same method
	 * in the JApplication class introduced with Joomla 4. Using
	 * isClient() would break the compatibility with J < 3.7 so
	 * we can rely on this helper method to avoid Fatal Errors.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	October 2020
	 */
	public static function isAdmin()
	{
		$app = JFactory::getApplication();
		if (method_exists($app, 'isClient')) {
			return $app->isClient('administrator');
		}

		return $app->isAdmin();
	}

	/**
	 * Helper method to cope with the removal of the same method
	 * in the JApplication class introduced with Joomla 4. Using
	 * isClient() would break the compatibility with J < 3.7 so
	 * we can rely on this helper method to avoid Fatal Errors.
	 * 
	 * @return 	boolean
	 * 
	 * @since 	October 2020
	 */
	public static function isSite()
	{
		$app = JFactory::getApplication();
		if (method_exists($app, 'isClient')) {
			return $app->isClient('site');
		}

		return $app->isSite();
	}

	/**
	 * Gets the Google Maps API Key.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function getGoogleMapsKey()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='gmapskey';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return $dbo->loadResult();
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('gmapskey', '');";
		$dbo->setQuery($q);
		$dbo->execute();
		return '';
	}

	/**
	 * Checks whether the interactive map booking is enabled.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function interactiveMapEnabled()
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='interactive_map';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			return ((int)$dbo->loadResult() > 0);
		}
		
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('interactive_map', '0');";
		$dbo->setQuery($q);
		$dbo->execute();
		
		return false;
	}

	/**
	 * Gets the preferred colors saved in the configuration, if any.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function getPreferredColors()
	{
		$dbo = JFactory::getDbo();
		$pref_colors = array(
			'textcolor' => '',
			'bgcolor' => '',
			'fontcolor' => '',
			'bgcolorhov' => '',
			'fontcolorhov' => '',
		);

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='pref_colors';";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$colors = json_decode($dbo->loadResult(), true);
			if (!is_array($colors) || !isset($colors['textcolor'])) {
				return $pref_colors;
			}
			return $colors;
		}
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('pref_colors', '{}');";
		$dbo->setQuery($q);
		$dbo->execute();
		return $pref_colors;
	}

	/**
	 * Adds to the document inline styles for the preferred colors, if any.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function loadPreferredColorStyles()
	{
		$view = VikRequest::getString('view', '', 'request');
		$pref_colors = self::getPreferredColors();
		
		$css_classes = array();
		
		if (!empty($pref_colors['textcolor'])) {
			// titles and headings
			array_push($css_classes, '.vbo-pref-color-text { color: ' . $pref_colors['textcolor'] . ' !important; }');
			// stepbar, oconfirm
			array_push($css_classes, 'ol.vbo-stepbar li.vbo-step-complete, ol.vbo-stepbar li.vbo-step-current, ol.vbo-stepbar li.vbo-step-current:before, .vbo-coupon-outer, .vbo-enterpin-block { border-color: ' . $pref_colors['textcolor'] . ' !important; }');
			// buttons secondary color
			array_push($css_classes, '.vbo-pref-color-btn-secondary { border: 2px solid ' . $pref_colors['textcolor'] . ' !important; color: ' . $pref_colors['textcolor'] . ' !important; background: transparent !important; }');
			if (!empty($pref_colors['fontcolor'])) {
				array_push($css_classes, '.vbo-pref-color-btn-secondary:hover { color: ' . $pref_colors['fontcolor'] . ' !important; background: ' . $pref_colors['textcolor'] . ' !important; }');
			}
			// datepicker
			array_push($css_classes, '.ui-datepicker .ui-datepicker-today {
				color: ' . $pref_colors['textcolor'] . ' !important;
				border-color: ' . $pref_colors['textcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-today a {
				color: ' . $pref_colors['textcolor'] . ' !important;
			}');
			// operators tableaux
			if ($view == 'tableaux') {
				array_push($css_classes, '.vbo-roomdaynote-empty .vbo-roomdaynote-trigger i { color: ' . $pref_colors['textcolor'] . ' !important; }');
			}
		}

		if (!empty($pref_colors['bgcolor']) && !empty($pref_colors['fontcolor'])) {
			// elements with backgrounds
			array_push($css_classes, '.vbo-pref-color-element { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			array_push($css_classes, '.vbo-pref-bordercolor { border-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			array_push($css_classes, '.vbo-pref-bordertext { color: ' . $pref_colors['bgcolor'] . ' !important; border-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			// buttons with backgrounds
			array_push($css_classes, '.vbo-pref-color-btn { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			// stepbar
			array_push($css_classes, 'ol.vbo-stepbar li.vbo-step-complete:before { background-color: ' . $pref_colors['bgcolor'] . ' !important; }');
			// datepicker
			array_push($css_classes, '.ui-datepicker table td:hover {
				border-color: ' . $pref_colors['bgcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-current-day {
				background: ' . $pref_colors['bgcolor'] . ' !important;
				color: ' . $pref_colors['fontcolor'] . ' !important;
			}');
			array_push($css_classes, '.ui-datepicker .ui-datepicker-current-day a {
				color: ' . $pref_colors['fontcolor'] . ' !important;
			}');
			// operators tableaux
			if ($view == 'tableaux') {
				array_push($css_classes, '.vbo-tableaux-roombooks > div:not(.vbo-tableaux-booking-empty), .vbo-tableaux-togglefullscreen { background-color: ' . $pref_colors['bgcolor'] . ' !important; color: ' . $pref_colors['fontcolor'] . ' !important; }');
			}
		}

		if (!empty($pref_colors['bgcolorhov']) && !empty($pref_colors['fontcolorhov'])) {
			// buttons with backgrounds during hover state
			array_push($css_classes, '.vbo-pref-color-btn:hover { background-color: ' . $pref_colors['bgcolorhov'] . ' !important; color: ' . $pref_colors['fontcolorhov'] . ' !important; }');
			// operators tableaux
			if ($view == 'tableaux') {
				array_push($css_classes, '.vbo-tableaux-togglefullscreen:hover { background-color: ' . $pref_colors['bgcolorhov'] . ' !important; color: ' . $pref_colors['fontcolorhov'] . ' !important; }');
			}
		}

		if (!count($css_classes)) {
			return;
		}

		// add in-line style declaration
		JFactory::getDocument()->addStyleDeclaration(implode("\n", $css_classes));
	}

	/**
	 * Given the full endpoint URL for the AJAX request, it
	 * returns an appropriate URI for the current platform.
	 * 
	 * @param 	mixed 	 $query 	The query string or a routed URL.
	 * @param 	boolean  $xhtml  	Replace & by &amp; for XML compliance.
	 * 
	 * @return 	string 				The AJAX end-point URI.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 * @since 	1.15.0 (J) - 1.5.0 (WP) we rely on the new platform libraries.
	 */
	public static function ajaxUrl($query = '', $xhtml = false)
	{
		return VBOFactory::getPlatform()->getUri()->ajax($query, $xhtml);
	}

	/**
	 * Tells whether no tax rates have been defined so far.
	 * 
	 * @return 	bool 	True if no tax rates defined, false otherwise.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function noTaxRates()
	{
		$dbo = JFactory::getDbo();

		$q = "SELECT `id`, `aliq` FROM `#__vikbooking_iva` WHERE `aliq` > 0;";
		$dbo->setQuery($q);
		$dbo->execute();

		return ($dbo->getNumRows() < 1);
	}

	/**
	 * Booking Type feature is strictly connected to VCM and OTAs. Updated
	 * versions of VBO will always support it as long as VCM is installed.
	 * Needed by VCM to understand whether certain SQL queries can be performed.
	 * 
	 * @return 	bool 	true if VCM exists, false otherwise.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	public static function isBookingTypeSupported()
	{
		return is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php');
	}

	/**
	 * Returns an instance of the Rates Flow helper class of VCM, if available.
	 * 
	 * @return 	mixed 	VCMRatesFlow if VCM available and updated, or null.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function getRatesFlowInstance()
	{
		$vcm_fpath = VCM_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'rates_flow.php';
		if (!is_file($vcm_fpath)) {
			return null;
		}

		// load dependencies
		require_once $vcm_fpath;

		// return the instance of the class
		return VCMRatesFlow::getInstance();
	}

	/**
	 * Returns the current appearance preference.
	 * 
	 * @return 	string 	light, auto or dark.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function getAppearancePref()
	{
		static $preference = null;

		if ($preference !== null) {
			return $preference;
		}

		// auto is the default appearance preference
		$default_pref = 'auto';

		// accepted preferences
		$valid_pref = [
			'auto',
			'light',
			'dark',
		];

		$preference = VBOFactory::getConfig()->get('appearance_pref', $default_pref);
		$preference = in_array($preference, $valid_pref) ? $preference : $default_pref;

		return $preference;
	}

	/**
	 * According to the appearance preferences, the apposite
	 * CSS assets are loaded within the document.
	 * 
	 * @param 	bool 	$get_info 	true to not load any assets.
	 * 
	 * @return 	mixed 	string light, auto, dark, info array or false.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 * @since 	1.16.0 (J) - 1.6.0 (WP)  added argument $get_info.
	 */
	public static function loadAppearancePreferenceAssets($get_info = false)
	{
		// get document object
		$document = JFactory::getDocument();

		// load current color scheme preference
		$current_pref = self::getAppearancePref();

		// define caching values
		$file_opt = array('version' => VIKBOOKING_SOFTWARE_VERSION);

		// define file attributes
		$file_attr = array('id' => 'vbo-css-appearance-' . $current_pref);

		// apposite file path and URI for back-end
		$css_path = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'vbo-appearance-' . $current_pref . '.css';
		$css_uri  = VBO_ADMIN_URI . 'resources/vbo-appearance-' . $current_pref . '.css';

		// check if this is for the front-end ("auto" file only)
		if (self::isSite() && in_array($current_pref, array('auto', 'dark'))) {
			$css_path = VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'vbo-appearance-auto.css';
			$css_uri  = VBO_SITE_URI . 'resources/vbo-appearance-auto.css';
			// check if front-end appearance is disabled
			if (!VBOFactory::getConfig()->getInt('appearance_front', 0)) {
				// do nothing for the front-end by default
				return false;
			}
		}

		if (VBOPlatformDetection::isJoomla()) {
			$css_path = str_replace('resources' . DIRECTORY_SEPARATOR, '', $css_path);
			$css_uri  = str_replace('resources/', '', $css_uri);
		}

		if (!is_file($css_path)) {
			// this preference does not require a specific stylesheet
			return false;
		}

		if ($get_info) {
			// do not actually load any assets
			return [
				'id'   => $file_attr['id'],
				'href' => $css_uri,
			];
		}

		// load the apposite CSS file
		$document->addStyleSheet($css_uri, $file_opt, $file_attr);

		return $current_pref;
	}

	/**
	 * VBO trigger for VCM subscription status and updates. If called within the admin section
	 * returns the expiration reminder array to display a modal window, and sets a warning
	 * message. If called within the front-end, may trigger an email alert.
	 * 
	 * @return 	mixed 	null, array or boolean depending on client position.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function getVCMSubscriptionStatus()
	{
		$vcm_lib_path = VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php';
		if (!is_file($vcm_lib_path)) {
			// do nothing
			return null;
		}

		// determine client position
		$is_admin = self::isAdmin();

		try {
			if (!class_exists('VikChannelManager') || !method_exists('VikChannelManager', 'checkSubscriptionReminder')) {
				// VCM is outdated
				return null;
			}

			// always attempt to load the admin lang handler of VCM
			$lang = JFactory::getLanguage();

			// load the VCM admin language file
			$vcm_admin_lang_path = '';
			if (VBOPlatformDetection::isJoomla()) {
				$vcm_admin_lang_path = JPATH_ADMINISTRATOR;
				/**
				 * @joomlaonly  if not defined VCM Software Version constant, include the defines files.
				 */
				if (!defined('VIKCHANNELMANAGER_SOFTWARE_VERSION')) {
					include_once VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'adapter' . DIRECTORY_SEPARATOR . 'defines.php';
				}
			} elseif (defined('VIKCHANNELMANAGER_ADMIN_LANG')) {
				$vcm_admin_lang_path = VIKCHANNELMANAGER_ADMIN_LANG;
			} elseif (VBOPlatformDetection::isWordPress() && defined('VIKBOOKING_ADMIN_LANG')) {
				/**
				 * If running within Vik Booking, the constant VIKCHANNELMANAGER_ADMIN_LANG may not be available
				 */
				$vcm_admin_lang_path = str_replace('vikbooking', 'vikchannelmanager', VIKBOOKING_ADMIN_LANG);
			}
			// load translation file
			$lang->load('com_vikchannelmanager', $vcm_admin_lang_path, $lang->getTag(), true);
			if (VBOPlatformDetection::isWordPress() && defined('VIKCHANNELMANAGER_LIBRARIES')) {
				/**
				 * @wponly  load language admin handler as well for WP.
				 * 			We do this only because of WordPress, but in a way also compatible with Joomla as
				 * 			the constant VIKCHANNELMANAGER_LIBRARIES and method attachHandler are not in Joomla.
				 */
				$lang->attachHandler(VIKCHANNELMANAGER_LIBRARIES . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'admin.php', 'vikchannelmanager');
			}

			if ($is_admin) {
				// back-end section of the site: check status
				if (VikChannelManager::loadExpirationDetails() === false) {
					// download and update the expiration details
					VikChannelManager::downloadExpirationDetails();
				}
				$expiration_reminder = null;
				if (VikChannelManager::isSubscriptionExpired()) {
					VikError::raiseWarning('', JText::translate('VCM_WARN_SUBSCR_EXPIRED'));
				} else {
					$expiration_reminder = VikChannelManager::shouldRemindExpiration();
					// we also check if an update is available
					if (VikChannelManager::checkUpdates()) {
						// an update is more important
						JFactory::getApplication()->redirect('index.php?option=com_vikchannelmanager&task=update_program&forcecheck=1&force_check=1');
						exit;
					}
				}
				// return reminder status
				return $expiration_reminder;
			}

			// front-end section of the site: trigger alert, if any (bool)
			return VikChannelManager::checkSubscriptionReminder();

		} catch (Exception $e) {
			// do nothing
		}

		return null;
	}

	/**
	 * Given a list of website rate plans, returns the sorted list.
	 * Naming score is the highest sorting method, pricing scoring is also supported.
	 * 
	 * @param 	array 	$rate_plans 	the list of rate plan arrays to sort.
	 * @param 	bool 	$assoc 			whether to keep the original array keys.
	 * 
	 * @return 	array 					the sorted list of rate plan arrays.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function sortRatePlans($rate_plans, $assoc = false)
	{
		if (!is_array($rate_plans) || count($rate_plans) < 2) {
			return $rate_plans;
		}

		foreach ($rate_plans as $rp_ind => $rate_plan) {
			if (!is_array($rate_plan) || empty($rate_plan['name'])) {
				// do not proceed in case of a weird array structure
				return $rate_plans;
			}
			// count the sorting-score of this rate plan
			$sort_score = 0;
			if (stripos($rate_plan['name'], 'Standard') !== false || stripos($rate_plan['name'], 'Base') !== false) {
				// "standard rate" rate plans should go first
				$sort_score += 3;
			} else {
				$sort_score--;
			}
			if (stripos($rate_plan['name'], 'Non') === false && stripos($rate_plan['name'], 'Not') === false) {
				// this doesn't seem to be a "non refundable rate" so it should go first
				$sort_score += 2;
			}

			// set sorting score for this rate plan ID
			$rate_plans[$rp_ind]['sort_score'] = $sort_score;
		}

		// apply custom sorting and keep index association
		uasort($rate_plans, function($a, $b) {
			// sort by name-scoring DESC
			$sort_diff = $b['sort_score'] - $a['sort_score'];
			if (isset($b['cost'])) {
				// apply also sorting by price ASC
				$sort_diff += ($a['cost'] < $b['cost']) ? -1 : 1;
			}
			return $sort_diff;
		});

		return !$assoc ? array_values($rate_plans) : $rate_plans;
	}

	/**
	 * Returns an instance of the Availability helper class.
	 * 
	 * @return 	VikBookingAvailability
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function getAvailabilityInstance()
	{
		// require library
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'availability.php';

		// return the instance of the class
		return VikBookingAvailability::getInstance();
	}

	/**
	 * Helper method to quickly guess the internal dependency to import.
	 * 
	 * @param 	string 	$path_id 	either a full path to a file or its identifier.
	 * 
	 * @return 	bool 				true if the requested library was imported or false.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public static function import($path_id)
	{
		if (empty($path_id)) {
			return false;
		}

		// always append .php extension
		$path_id = basename($path_id, '.php') . '.php';

		if (is_file($path_id)) {
			// full path given
			require_once $path_id;

			return true;
		}

		// try guessing where the file id is located
		if (is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $path_id)) {
			// library was found
			require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $path_id;

			return true;
		}

		if (is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $path_id)) {
			// library was found
			require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . $path_id;

			return true;
		}

		return false;
	}
}

if (!class_exists('VikResizer'))
{
	class VikResizer
	{
		public function __construct()
		{
			// objects of this class can also be instantiated without calling the methods statically.
		}

		/**
		 * Resizes an image proportionally. For PNG files it can optionally
		 * trim the image to exclude the transparency, and add some padding to it.
		 * All PNG files keep the alpha background in the resized version.
		 *
		 * @param 	string 		$fileimg 	path to original image file
		 * @param 	string 		$dest 		path to destination image file
		 * @param 	int 		$towidth 	
		 * @param 	int 		$toheight 	
		 * @param 	bool 		$trim_png 	remove empty background from image
		 * @param 	string 		$trim_pad 	CSS-style version of padding (top right bottom left) ex: '1 2 3 4'
		 *
		 * @return 	boolean
		 */
		public static function proportionalImage($fileimg, $dest, $towidth, $toheight, $trim_png = false, $trim_pad = null)
		{
			if (!is_file($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				return copy($fileimg, $dest);
			}

			$result = true;

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid > $towidth || $ohei > $toheight) {
				$xscale = $owid / $towidth;
				$yscale = $ohei / $toheight;
				if ($yscale > $xscale) {
					$new_width = round($owid * (1 / $yscale));
					$new_height = round($ohei * (1 / $yscale));
				} else {
					$new_width = round($owid * (1 / $xscale));
					$new_height = round($ohei * (1 / $xscale));
				}

				$imageresized = imagecreatetruecolor($new_width, $new_height);

				switch ($type) {
					case '1' :
						$imagetmp = imagecreatefromgif ($fileimg);
						break;
					case '2' :
						$imagetmp = imagecreatefromjpeg($fileimg);
						break;
					case '18' :
						$imagetmp = imagecreatefromwebp($fileimg);
						break;
					default :
						//keep alpha for PNG files
						$background = imagecolorallocate($imageresized, 0, 0, 0);
						imagecolortransparent($imageresized, $background);
						imagealphablending($imageresized, false);
						imagesavealpha($imageresized, true);
						//
						$imagetmp = imagecreatefrompng($fileimg);
						break;
				}

				if (!$imagetmp) {
					return false;
				}

				imagecopyresampled($imageresized, $imagetmp, 0, 0, 0, 0, $new_width, $new_height, $owid, $ohei);

				switch ($type) {
					case '1' :
						imagegif($imageresized, $dest);
						break;
					case '2' :
						imagejpeg($imageresized, $dest);
						break;
					case '18' :
						imagewebp($imageresized, $dest);
						break;
					default :
						if ($trim_png) {
							self::imageTrim($imageresized, $background, $trim_pad);
						}
						imagepng($imageresized, $dest);
						break;
				}

				imagedestroy($imageresized);
			} else {
				$result = copy($fileimg, $dest);
			}

			if (VBOPlatformDetection::isWordPress()) {
				/**
				 * @wponly  in order to not lose resized files after installing an update,
				 * 			we need to move any uploaded file onto a recovery folder.
				 */
				VikBookingLoader::import('update.manager');
				VikBookingUpdateManager::triggerUploadBackup($dest);
			}

			return $result;
		}

		/**
		 * (BETA) Resizes an image proportionally. For PNG files it can optionally
		 * trim the image to exclude the transparency, and add some padding to it.
		 * All PNG files keep the alpha background in the resized version.
		 *
		 * @param 	resource 	$im 		Image link resource (reference)
		 * @param 	int 		$bg 		imagecolorallocate color identifier
		 * @param 	string 		$pad 		CSS-style version of padding (top right bottom left) ex: '1 2 3 4'
		 *
		 * @return 	void
		 */
		public static function imagetrim(&$im, $bg, $pad = null)
		{
			// Calculate padding for each side.
			if (isset($pad)) {
				$pp = explode(' ', $pad);
				if (isset($pp[3])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[3]);
				} elseif (isset($pp[2])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[2], (int) $pp[1]);
				} elseif (isset($pp[1])) {
					$p = array((int) $pp[0], (int) $pp[1], (int) $pp[0], (int) $pp[1]);
				} else {
					$p = array_fill(0, 4, (int) $pp[0]);
				}
			} else {
				$p = array_fill(0, 4, 0);
			}

			// Get the image width and height.
			$imw = imagesx($im);
			$imh = imagesy($im);

			// Set the X variables.
			$xmin = $imw;
			$xmax = 0;

			// Start scanning for the edges.
			for ($iy=0; $iy<$imh; $iy++) {
				$first = true;
				for ($ix=0; $ix<$imw; $ix++) {
					$ndx = imagecolorat($im, $ix, $iy);
					if ($ndx != $bg) {
						if ($xmin > $ix) {
							$xmin = $ix;
						}
						if ($xmax < $ix) {
							$xmax = $ix;
						}
						if (!isset($ymin)) {
							$ymin = $iy;
						}
						$ymax = $iy;
						if ($first) {
							$ix = $xmax;
							$first = false;
						}
					}
				}
			}

			// The new width and height of the image. (not including padding)
			$imw = 1+$xmax-$xmin; // Image width in pixels
			$imh = 1+$ymax-$ymin; // Image height in pixels

			// Make another image to place the trimmed version in.
			$im2 = imagecreatetruecolor($imw+$p[1]+$p[3], $imh+$p[0]+$p[2]);

			// Make the background of the new image the same as the background of the old one.
			$bg2 = imagecolorallocate($im2, ($bg >> 16) & 0xFF, ($bg >> 8) & 0xFF, $bg & 0xFF);
			imagefill($im2, 0, 0, $bg2);

			// Copy it over to the new image.
			imagecopy($im2, $im, $p[3], $p[0], $xmin, $ymin, $imw, $imh);

			// To finish up, we replace the old image which is referenced.
			$im = $im2;
		}

		public static function bandedImage($fileimg, $dest, $towidth, $toheight, $rgb)
		{
			if (!file_exists($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				copy($fileimg, $dest);
				return true;
			}

			$exp = explode(",", $rgb);
			if (count($exp) == 3) {
				$r = trim($exp[0]);
				$g = trim($exp[1]);
				$b = trim($exp[2]);
			} else {
				$r = 0;
				$g = 0;
				$b = 0;
			}

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid > $towidth || $ohei > $toheight) {
				$xscale = $owid / $towidth;
				$yscale = $ohei / $toheight;
				if ($yscale > $xscale) {
					$new_width = round($owid * (1 / $yscale));
					$new_height = round($ohei * (1 / $yscale));
					$ydest = 0;
					$diff = $towidth - $new_width;
					$xdest = ($diff > 0 ? round($diff / 2) : 0);
				} else {
					$new_width = round($owid * (1 / $xscale));
					$new_height = round($ohei * (1 / $xscale));
					$xdest = 0;
					$diff = $toheight - $new_height;
					$ydest = ($diff > 0 ? round($diff / 2) : 0);
				}

				$imageresized = imagecreatetruecolor($towidth, $toheight);

				$bgColor = imagecolorallocate($imageresized, (int) $r, (int) $g, (int) $b);
				imagefill($imageresized, 0, 0, $bgColor);

				switch ($type) {
					case '1' :
						$imagetmp = imagecreatefromgif ($fileimg);
						break;
					case '2' :
						$imagetmp = imagecreatefromjpeg($fileimg);
						break;
					default :
						$imagetmp = imagecreatefrompng($fileimg);
						break;
				}

				imagecopyresampled($imageresized, $imagetmp, $xdest, $ydest, 0, 0, $new_width, $new_height, $owid, $ohei);

				switch ($type) {
					case '1' :
						imagegif ($imageresized, $dest);
						break;
					case '2' :
						imagejpeg($imageresized, $dest);
						break;
					default :
						imagepng($imageresized, $dest);
						break;
				}

				imagedestroy($imageresized);

				return true;
			} else {
				copy($fileimg, $dest);
			}
			return true;
		}

		public static function croppedImage($fileimg, $dest, $towidth, $toheight)
		{
			if (!file_exists($fileimg)) {
				return false;
			}
			if (empty($towidth) && empty($toheight)) {
				copy($fileimg, $dest);
				return true;
			}

			list ($owid, $ohei, $type) = getimagesize($fileimg);

			if ($owid <= $ohei) {
				$new_width = $towidth;
				$new_height = ($towidth / $owid) * $ohei;
			} else {
				$new_height = $toheight;
				$new_width = ($new_height / $ohei) * $owid;
			}

			switch ($type) {
				case '1' :
					$img_src = imagecreatefromgif ($fileimg);
					$img_dest = imagecreate($new_width, $new_height);
					break;
				case '2' :
					$img_src = imagecreatefromjpeg($fileimg);
					$img_dest = imagecreatetruecolor($new_width, $new_height);
					break;
				default :
					$img_src = imagecreatefrompng($fileimg);
					$img_dest = imagecreatetruecolor($new_width, $new_height);
					break;
			}

			imagecopyresampled($img_dest, $img_src, 0, 0, 0, 0, $new_width, $new_height, $owid, $ohei);

			switch ($type) {
				case '1' :
					$cropped = imagecreate($towidth, $toheight);
					break;
				case '2' :
					$cropped = imagecreatetruecolor($towidth, $toheight);
					break;
				default :
					$cropped = imagecreatetruecolor($towidth, $toheight);
					break;
			}

			imagecopy($cropped, $img_dest, 0, 0, 0, 0, $owid, $ohei);

			switch ($type) {
				case '1' :
					imagegif ($cropped, $dest);
					break;
				case '2' :
					imagejpeg($cropped, $dest);
					break;
				default :
					imagepng($cropped, $dest);
					break;
			}

			imagedestroy($img_dest);
			imagedestroy($cropped);

			return true;
		}
	}
}
