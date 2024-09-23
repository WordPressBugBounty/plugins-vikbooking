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
 * Helper class to support custom pax fields data collection types.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
final class VBOCheckinPax
{
	/**
	 * Container of all pax fields collection instances.
	 * 
	 * @var 	array
	 */
	private static $instances = array();

	/**
	 * Data container of all pax fields collection instances.
	 * 
	 * @var 	array
	 */
	private static $instances_data = array();

	/**
	 * Retrieves the list of back-end pax fields for an extended collection type.
	 * 
	 * @param 	string 	$type 	the pax data type representing the class.
	 * 
	 * @return 	array 	the list of pax fields to collect in the back-end.
	 */
	public static function getFields($type = '')
	{
		// default empty containers
		$labels = [];
		$attributes = [];

		if (empty($type)) {
			return [$labels, $attributes];
		}

		// invoke custom pax fields object
		$paxf_obj = self::getInstance($type);
		if (!$paxf_obj) {
			return [$labels, $attributes];
		}

		return $paxf_obj->listFields();
	}

	/**
	 * Returns the instance of the custom pax data collection type object.
	 * 
	 * @param 	string 	$type 	the pax data type representing the class.
	 * 
	 * @return 	null|object 	the requested driver object or null.
	 */
	public static function getInstance($type = '')
	{
		// make sure the type of pax data collection type is set
		if (empty($type)) {
			if (count(static::$instances)) {
				// we return the last previously instantiated object
				$type = key(static::$instances);
				return static::$instances[$type];
			}
			// get the currently active pax data collection type
			$type = VBOFactory::getConfig()->getString('checkindata', 'basic');
		}

		if (isset(static::$instances[$type])) {
			return static::$instances[$type];
		}

		// invoke custom pax fields class
		$custom_paxf_class = self::getPaxClass($type);

		if (!class_exists($custom_paxf_class)) {
			return null;
		}

		// invoke and cache the object
		static::$instances[$type] = new $custom_paxf_class();

		// register the data container registry for this collection type
		static::$instances_data[$type] = new JObject;

		// return the object
		return static::$instances[$type];
	}

	/**
	 * Attempts to get the instance data registry for the data collection type previously
	 * previously loaded. This serves to push and cache heavy data so that it will be read
	 * only once, in case multiple pax fields will need to access such values multiple times.
	 * 
	 * @return 	null|JObject 	the driver object instance data registry, or null.
	 */
	public static function getInstanceData()
	{
		if (!count(static::$instances)) {
			// no drivers ever loaded
			return null;
		}

		// get the first (previous) type of collection driver loaded
		$type = key(static::$instances);

		return isset(static::$instances_data[$type]) ? static::$instances_data[$type] : null;
	}

	/**
	 * Returns a list of all custom drivers available.
	 * 
	 * @param 	bool 	$no_basic 	whether to exclude the "basic" (default) driver.
	 * 
	 * @return 	array 	the associative list of custom pax data drivers.
	 */
	public static function getDrivers($no_basic = true)
	{
		$drivers_list  = [];
		$drivers_base  = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'paxfields' . DIRECTORY_SEPARATOR;
		$drivers_files = glob($drivers_base . '*.php');

		/**
		 * Trigger event to let other plugins register additional drivers.
		 *
		 * @return 	array 	a list of supported drivers.
		 */
		$list = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadPaxdatafieldsDrivers');
		foreach ($list as $chunk) {
			// merge default driver files with the returned ones
			$drivers_files = array_merge($drivers_files, (array)$chunk);
		}

		foreach ($drivers_files as $df) {
			$driver_base_name = basename($df, '.php');
			if ($no_basic && !strcasecmp($driver_base_name, 'basic')) {
				continue;
			}
			$drivers_list[] = $driver_base_name;
		}

		return $drivers_list;
	}

	/**
	 * Helper method to get the decoded pax_data of an existing booking.
	 * 
	 * @param 	int 	$bid 	the booking ID.
	 * 
	 * @return 	null|array 		the decoded pax data or null.
	 */
	public static function getBookingPaxData($bid)
	{
		if (empty($bid)) {
			return null;
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `pax_data` FROM `#__vikbooking_customers_orders` WHERE `idorder`=" . (int)$bid;
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return null;
		}
		$pax_data = $dbo->loadResult();
		if (empty($pax_data)) {
			return null;
		}

		// attempt to decode current data
		$pax_data = json_decode($pax_data, true);

		return is_array($pax_data) && count($pax_data) ? $pax_data : null;
	}

	/**
	 * Helper method to update the pax_data of an existing booking.
	 * 
	 * @param 	int 	$bid 		the booking ID.
	 * @param 	array 	$pax_data 	the array of room guests pax data.
	 * 
	 * @return 	bool 				true if record was updated.
	 */
	public static function setBookingPaxData($bid, $pax_data)
	{
		if (empty($bid) || !is_array($pax_data) || !count($pax_data)) {
			return false;
		}

		$dbo = JFactory::getDbo();

		$record = new stdClass;
		$record->idorder = $bid;
		$record->pax_data = json_encode($pax_data);

		return $dbo->updateObject('#__vikbooking_customers_orders', $record, 'idorder');
	}

	/**
	 * Helper method to load the previous pax data for this customer in case
	 * previous check-ins were made already for this customer. This is to
	 * quicky populate the previous check-in information.
	 * 
	 * @param 	int 	$bid 	the booking ID.
	 * @param 	int 	$lim 	the maximum number of previous records to load.
	 * 
	 * @return 	array 	the list of previous pax data records for this customer.
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public static function getCustomerAllPaxData($bid, $lim = 5)
	{
		if (empty($bid)) {
			return [];
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `idcustomer` FROM `#__vikbooking_customers_orders` WHERE `idorder`=" . (int)$bid;
		$dbo->setQuery($q, 0, 1);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return [];
		}
		$id_customer = $dbo->loadResult();

		$q = "SELECT `co`.`idorder`, `co`.`pax_data`, `o`.`ts`, `o`.`checkin`, `o`.`checkout` FROM `#__vikbooking_customers_orders` AS `co` 
			LEFT JOIN `#__vikbooking_orders` AS `o` ON `co`.`idorder`=`o`.`id` 
			WHERE `co`.`idcustomer`=" . (int)$id_customer . " AND `co`.`idorder` != " . (int)$bid . " AND `co`.`pax_data` IS NOT NULL ORDER BY `co`.`idorder` DESC";
		$dbo->setQuery($q, 0, $lim);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return [];
		}
		$prev_records = $dbo->loadAssocList();

		foreach ($prev_records as $k => $v) {
			// attempt to decode checkin data
			$pax_data = json_decode($v['pax_data'], true);
			if (!is_array($pax_data) || !count($pax_data)) {
				unset($prev_records[$k]);
				continue;
			}
			$prev_records[$k]['pax_data'] = $pax_data;
		}

		return $prev_records;
	}

	/**
	 * Given a type string, returns the full class name for this driver.
	 * 
	 * @param 	string 	$type 	the pax data type representing the class.
	 * 
	 * @return 	string 	the name of the class for the custom pax data collection.
	 */
	private static function getPaxClass($type)
	{
		$base_paxf_class = 'VBOCheckinPaxfields';

		$type_class = ucwords(str_replace(array('-', '_'), ' ', $type));
		$type_class = preg_replace("/[^a-zA-Z0-9]/", '', $type_class);

		return $base_paxf_class . $type_class;
	}
}
