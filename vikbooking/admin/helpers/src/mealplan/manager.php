<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Meal plan manager.
 * 
 * @since 	1.16.1 (J) - 1.6.1 (WP)
 */
final class VBOMealplanManager extends JObject
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var  VBOMealplanManager
	 */
	private static $instance = null;

	/**
	 * @var  array
	 */
	private $meal_plans = [];

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
	 * Class constructor.
	 * 
	 * @param 	array|object  $data  optional data to bind.
	 */
	public function __construct($data)
	{
		// make sure to load the back-end language definitions
		if (VikBooking::isSite()) {
			$lang = JFactory::getLanguage();
			$lang_tag = $lang->getTag();
			if (VBOPlatformDetection::isWordPress()) {
				$lang->load('com_vikbooking', VIKBOOKING_LANG, $lang_tag, true);
				$lang->attachHandler(VIKBOOKING_LIBRARIES . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'admin.php', 'vikbooking');
			} else {
				$lang->load('com_vikbooking', JPATH_ADMINISTRATOR, $lang_tag, true);
				$lang->load('joomla', JPATH_ADMINISTRATOR, $lang_tag, true);
			}
		}

		// set up the various meal plans
		$this->setup();

		parent::__construct($data);
	}

	/**
	 * Tells whether the specified rate plan includes the given meal plan enum code.
	 * 
	 * @param 	array|int 	$rplan 		either the rate plan record or rate plan ID.
	 * @param 	string 		$meal_enum 	the meal plan enum identifier.
	 * 
	 * @return 	bool
	 */
	public function ratePlanMealIncluded($rplan, $meal_enum)
	{
		if (is_scalar($rplan)) {
			$rplan = VikBooking::getPriceInfo($rplan);
		}

		if (!is_array($rplan) || !$rplan) {
			return false;
		}

		// for BC we check the dedicated flag to breakfast included
		if (!strcasecmp($meal_enum, 'breakfast') && !empty($rplan['breakfast_included'])) {
			return true;
		}

		if (empty($rplan['meal_plans'])) {
			return false;
		}

		if (is_string($rplan['meal_plans'])) {
			return (stripos($rplan['meal_plans'], $meal_enum) !== false);
		}

		if (is_array($rplan['meal_plans'])) {
			return in_array($meal_enum, $rplan['meal_plans']);
		}

		return false;
	}

	/**
	 * Returns an associative list of meal plans included in the room rate plan.
	 * 
	 * @param 	array|int 	$rplan 		either the rate plan record or rate plan ID.
	 * 
	 * @return 	array
	 */
	public function ratePlanIncludedMeals($rplan)
	{
		if (is_scalar($rplan)) {
			$rplan = VikBooking::getPriceInfo($rplan);
		}

		if (!is_array($rplan) || !$rplan) {
			return [];
		}

		$included_meals = [];

		// for BC we check the dedicated flag to breakfast included
		if (!empty($rplan['breakfast_included'])) {
			$included_meals['breakfast'] = $this->meal_plans['breakfast'];
		}

		if (empty($rplan['meal_plans'])) {
			return $included_meals;
		}

		if (is_string($rplan['meal_plans'])) {
			// we support JSON encoded strings as well as comma-separated strings
			$included = json_decode($rplan['meal_plans'], true);
			if (is_array($included)) {
				$rplan['meal_plans'] = $included;
			} else {
				$rplan['meal_plans'] = explode(',', $rplan['meal_plans']);
				$rplan['meal_plans'] = array_map(function($mn) {
					return trim($mn);
				}, $rplan['meal_plans']);
			}
		}

		if (!is_array($rplan['meal_plans']) || !$rplan['meal_plans']) {
			return $included_meals;
		}

		foreach ($rplan['meal_plans'] as $meal_enum) {
			if (isset($this->meal_plans[$meal_enum])) {
				$included_meals[$meal_enum] = $this->meal_plans[$meal_enum];
			}
		}

		return $included_meals;
	}

	/**
	 * Returns an associative list of meal plans included in OTA reservation record.
	 * For those OTA bookings downloaded by older versions of VCM, this method attempts
	 * to find the meals included in the reservation by parsing the "Meal_plan" string.
	 * Alternatively, it relies on VCM to check the Content/Product/Listing API data.
	 * 
	 * @param 	array 	$res 		the OTA reservation record.
	 * @param 	array 	$room_res 	the optional room reservation record.
	 * 
	 * @return 	array 				list of included meal enum values calculated on the fly.
	 */
	public function otaDataIncludedMeals(array $res, array $room_res = [])
	{
		$included_meals = [];

		// make sure this is actually an OTA reservation downloaded by VCM
		if (empty($res['idorderota']) || empty($res['channel']) || empty($res['custdata'])) {
			return $included_meals;
		}

		// this usually works for Booking.com reservations
		foreach ($this->meal_plans as $meal_enum => $meal_name) {
			$meal_regexp = preg_quote($meal_name);
			if (preg_match("/meal_plan:([a-z0-9 ,\.])*{$meal_regexp}/i", $res['custdata'], $matches)) {
				if ($matches && $matches[0]) {
					/**
					 * Make sure the statement is not "Enjoy a convenient {meal} at the property for NN per person, per night."
					 * or "Breakfast costs US$12 per person per night." (same for "Lunch costs" and "Dinner costs").
					 * 
					 * @since 	1.16.2 (J) - 1.6.2 (WP)
					 */
					if (preg_match("/enjoy|convenient/i", $matches[0])) {
						// the meal plan is not included, it is just available at the property
						continue;
					} elseif (stripos($res['custdata'], "{$matches[0]} cost") !== false) {
						// the meal plan is not included, it has a cost
						continue;
					}
				}
				$included_meals[$meal_enum] = $meal_name;
			}
		}

		// let VCM detect any included meal plan for this OTA reservation
		if (!$included_meals && class_exists('VCMOtaRateplan')) {
			// this method will rely on the information stored through Content/Product/Listing APIs of the involved OTA
			$included_meals = VCMOtaRateplan::getInstance(['meal_plans' => $this->meal_plans])->findIncludedMeals($res, $room_res);
		}

		return $included_meals;
	}

	/**
	 * Tells if a meal plan is included in the OTA reservation or room data.
	 * 
	 * @param 	array 	$res 		the OTA reservation record.
	 * @param 	array 	$room_res 	the optional room reservation record.
	 * @param 	string 	$meal_enum 	the meal plan enum identifier.
	 * 
	 * @return 	bool
	 */
	public function otaDataMealIncluded(array $res, array $room_res, $meal_enum)
	{
		$included_meals = $this->otaDataIncludedMeals($res, $room_res);

		return isset($included_meals[$meal_enum]);
	}

	/**
	 * Tells whether the given room reservation record includes the given meal plan enum code.
	 * 
	 * @param 	array 	$room_res 	the room reservation record.
	 * @param 	string 	$meal_enum 	the meal plan enum identifier.
	 * 
	 * @return 	bool
	 */
	public function roomRateMealIncluded($room_res, $meal_enum)
	{
		if (!is_array($room_res) || empty($room_res['meals'])) {
			return false;
		}

		if (is_string($room_res['meals'])) {
			return (stripos($room_res['meals'], $meal_enum) !== false);
		}

		if (is_array($room_res['meals'])) {
			return in_array($meal_enum, $room_res['meals']);
		}

		return false;
	}

	/**
	 * Returns an associative list of meal plans included in the room reservation record.
	 * 
	 * @param 	array 	$room_res 	the room reservation record.
	 * 
	 * @return 	array
	 */
	public function roomRateIncludedMeals($room_res)
	{
		if (!is_array($room_res) || empty($room_res['meals'])) {
			return [];
		}

		$meals_included = $room_res['meals'];

		if (is_string($meals_included)) {
			// we support JSON encoded strings as well as comma-separated strings
			$included = json_decode($meals_included, true);
			if (is_array($included)) {
				$meals_included = $included;
			} else {
				$meals_included = explode(',', $meals_included);
				$meals_included = array_map(function($mn) {
					return trim($mn);
				}, $meals_included);
			}
		}

		if (!is_array($meals_included) || !$meals_included) {
			return [];
		}

		$valid_enums = [];
		foreach ($this->meal_plans as $meal_enum => $meal_name) {
			if (isset($meals_included[$meal_enum]) || in_array($meal_enum, $meals_included)) {
				$valid_enums[$meal_enum] = $meal_name;
			}
		}

		return $valid_enums;
	}

	/**
	 * Given one or more meal plan enum code, tries to convert them to translated strings.
	 * 
	 * @param 	array|string 	$meal_enum 	the meal plan enum identifier(s).
	 * 
	 * @return 	string
	 */
	public function sayMealPlanEnum($meal_enum)
	{
		if (!$meal_enum) {
			return '';
		}

		$plans_included = [];
		if (!is_array($meal_enum)) {
			$meal_enum = [$meal_enum];
		}

		foreach ($meal_enum as $enum) {
			if (is_string($enum) && isset($this->meal_plans[$enum])) {
				$plans_included[] = $this->meal_plans[$enum];
			}
		}

		if (!$plans_included) {
			return '';
		}

		return implode(', ', array_unique($plans_included));
	}

	/**
	 * This method is mainly used by VCM when storing new reservations onto VBO. Given
	 * a raw list of meals included in the OTA room tariff, builds the string to be stored.
	 * The information about the OTA meals is usually fetched from the rooms mapping information
	 * when receiving the whole reservation payload including the OTA room and rate plan IDs.
	 * In case the OTA rate plan was mapped with a "meal_plans" property, that value will be
	 * passed along to this method for parsing the OTA enum values transmitted by the E4jConnect servers.
	 * 
	 * @param 	string|array 	$ota_meals 	the raw meals included fetched from the OTA rate plan.
	 * 
	 * @return 	string 			the meals value to be stored onto the room reservation record.
	 */
	public function buildOTAMealPlans($ota_meals)
	{
		$store_meals = '';

		if (empty($ota_meals)) {
			return $store_meals;
		}

		// convert the OTA meals into an array of enums or raw names
		if (is_string($ota_meals)) {
			// we usually have comma-separated strings
			$included_meals = explode(',', $ota_meals);
			if ($included_meals && !empty($included_meals[0])) {
				// we've got something to parse
				$ota_meals = array_map(function($mn) {
					return trim($mn);
				}, $included_meals);
			} else {
				// attempt to decode a JSON string
				$ota_meals = json_decode($ota_meals, true);
			}
		}

		if (!is_array($ota_meals) || !$ota_meals) {
			return $store_meals;
		}

		$valid_enums = [];
		$known_enums = array_keys($this->meal_plans);

		foreach ($ota_meals as $ota_meal) {
			foreach ($known_enums as $known_enum) {
				if ($ota_meal && !strcasecmp($ota_meal, $known_enum)) {
					// valid meal found
					$valid_enums[] = $known_enum;
					// parse the next one, if any
					break;
				}
			}
		}

		if ($valid_enums) {
			// some valid OTA meals were found
			$store_meals = json_encode(array_unique($valid_enums));
		}

		return $store_meals;
	}

	/**
	 * Returns the supported meal plans.
	 * 
	 * @return 	array
	 */
	public function getPlans()
	{
		return $this->meal_plans;
	}

	/**
	 * Returns an associative list of meal plans and short name identifier (B, L, D).
	 * 
	 * @return 	array
	 */
	public function getShortMealPlans()
	{
		return [
			'breakfast' => JText::translate('VBO_SHORTM_BREAKFAST'),
			'lunch' 	=> JText::translate('VBO_SHORTM_LUNCH'),
			'dinner' 	=> JText::translate('VBO_SHORTM_DINNER'),
		];
	}

	/**
	 * Finds the ID and name of the rate plan from the given tariff ID.
	 *
	 * @param 	int  	$idtar	the ID of the tariff.
	 *
	 * @return 	array 			list of rate plan name (or an empty string) and ID.
	 */
	public function getPriceData($idtar)
	{
		if (empty($idtar)) {
			return [JText::translate('VBOROOMCUSTRATEPLAN'), 0];
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `p`.`id`, `p`.`name` FROM `#__vikbooking_prices` AS `p`
			LEFT JOIN `#__vikbooking_dispcost` AS `t` ON `p`.`id`=`t`.`idprice` WHERE `t`.`id`=" . (int)$idtar;
		$dbo->setQuery($q, 0, 1);
		$price_record = $dbo->loadAssoc();

		if ($price_record) {
			return [$price_record['name'], $price_record['id']];
		}

		return ['', 0];
	}

	/**
	 * Prepares the various meal plans supported.
	 * 
	 * @return 	void
	 */
	private function setup()
	{
		$config = VBOFactory::getConfig();

		$def_meals = $this->getDefaultMealPlans();

		$meals = $config->getArray('meal_plans', []);
		if (!$meals) {
			$meals = $def_meals;
			$config->set('meal_plans', $meals);
		}

		// map translations at runtime
		foreach ($meals as $meal_enum => &$meal_name) {
			$meal_name = $def_meals[$meal_enum];
		}

		// unset last reference
		unset($meal_name);

		// set updated map
		$this->meal_plans = $meals;
	}

	/**
	 * Returns an associative list of default meal plans.
	 * 
	 * @return 	array
	 */
	private function getDefaultMealPlans()
	{
		return [
			'breakfast' => JText::translate('VBO_MEAL_BREAKFAST'),
			'lunch' 	=> JText::translate('VBO_MEAL_LUNCH'),
			'dinner' 	=> JText::translate('VBO_MEAL_DINNER'),
		];
	}
}
