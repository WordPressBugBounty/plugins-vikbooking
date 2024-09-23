<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Taxonomy summary class.
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
class VBOTaxonomySummary
{
	/**
	 * The tax summary associative array.
	 * 
	 * @var 	array
	 */
	protected static $summary = [];

	/**
	 * The tax names associative array.
	 * 
	 * @var 	array
	 */
	protected static $tax_names = [];

	/**
	 * Starts a new summary array.
	 * 
	 * @return 	array
	 */
	public static function start()
	{
		static::$summary = [];

		return [];
	}

	/**
	 * Adds a tax amount to a tax rate.
	 * 
	 * @param 	float 		$rate 		the tax aliquot (rate).
	 * @param 	float|int 	$amount 	the amount of taxes.
	 * @param 	string 		$name 		the name of the tax.
	 * 
	 * @return 	void
	 */
	public static function addTax($rate, $amount = 0, $name = null)
	{
		$rate = (float)$rate;

		if (!isset(static::$summary[$rate])) {
			static::$summary[$rate] = 0;
		}

		static::$summary[$rate] += $amount;

		if (!empty($name)) {
			static::$tax_names[$rate] = $name;
		}
	}

	/**
	 * Adds a tax amount to a tax rate from a given rate plan ID.
	 * 
	 * @param 	int 		$ratep_id	the rate plan ID.
	 * @param 	float|int 	$amount 	the amount of taxes.
	 * 
	 * @return 	float|int	the tax rate applied.
	 */
	public static function addRatePlanTax($ratep_id, $amount = 0)
	{
		list($rate, $name) = static::getTaxRatePlan($ratep_id);

		static::addTax($rate, $amount, $name);

		return $rate;
	}

	/**
	 * Adds a tax amount to a tax rate from a given tax ID.
	 * 
	 * @param 	int 		$tax_id		the tax ID.
	 * @param 	float|int 	$amount 	the amount of taxes.
	 * 
	 * @return 	float|int	the tax rate applied.
	 */
	public static function addOptionTax($tax_id, $amount = 0)
	{
		list($rate, $name) = static::getTaxRateFromId($tax_id);

		static::addTax($rate, $amount, $name);

		return $rate;
	}

	/**
	 * Gets the tax rate assigned to the given rate plan ID.
	 * 
	 * @param 	int 	$ratep_id	the rate plan ID.
	 * 
	 * @return 	array 	float|int (tax rate) and string (tax name).
	 */
	public static function getTaxRatePlan($ratep_id)
	{
		if (empty($ratep_id)) {
			return [0, null];
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `p`.`idiva`, `t`.`name`, `t`.`aliq` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` AS `t` ON `p`.`idiva`=`t`.`id` WHERE `p`.`id`=" . (int)$ratep_id;
		$dbo->setQuery($q, 0, 1);
		$record = $dbo->loadAssoc();
		if (!$record) {
			return [0, null];
		}

		return [(float)$record['aliq'], $record['name']];
	}

	/**
	 * Gets the tax rate assigned to the given tax ID.
	 * 
	 * @param 	int 	$tax_id		the tax ID.
	 * 
	 * @return 	array 	float|int (tax rate) and string (tax name).
	 */
	public static function getTaxRateFromId($tax_id)
	{
		if (empty($tax_id)) {
			return [0, null];
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT `t`.`name`, `t`.`aliq` FROM `#__vikbooking_iva` AS `t` WHERE `t`.`id`=" . (int)$tax_id;
		$dbo->setQuery($q, 0, 1);
		$record = $dbo->loadAssoc();
		if (!$record) {
			return [0, null];
		}

		return [(float)$record['aliq'], $record['name']];
	}

	/**
	 * Returns the whole tax rate record from the given ID.
	 * 
	 * @param 	int 	$tax_id 	the tax record ID.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public static function getTaxRateRecord($tax_id)
	{
		static $tax_map = [];

		$tax_id = (int)$tax_id;

		if (isset($tax_map[$tax_id])) {
			return $tax_map[$tax_id];
		}

		$dbo = JFactory::getDbo();

		$q = "SELECT * FROM `#__vikbooking_iva` WHERE `id`=" . $tax_id;
		$dbo->setQuery($q, 0, 1);
		$record = $dbo->loadAssoc();

		if (!$record) {
			$record = [];
		}

		// cache value and return it
		$tax_map[$tax_id] = $record;

		return $record;
	}

	/**
	 * Gets the tax summary associative array.
	 * 
	 * @param 	bool 	$sort 	if true, the array will be sorted by key desc.
	 * 
	 * @return 	array
	 */
	public static function get($sort = true)
	{
		if ($sort) {
			krsort(static::$summary);
		}

		return static::$summary;
	}

	/**
	 * Gets the tax names associative array.
	 * 
	 * @return 	array
	 */
	public static function getNames()
	{
		return static::$tax_names;
	}

	/**
	 * Sets the tax summary associative array.
	 * 
	 * @param 	array 	$summary
	 * 
	 * @return 	void
	 */
	public static function set(array $summary = [])
	{
		static::$summary = $summary;
	}

	/**
	 * Sets the tax names associative array.
	 * 
	 * @param 	array 	$names
	 * 
	 * @return 	void
	 */
	public static function setNames(array $names = [])
	{
		static::$tax_names = $names;
	}
}
