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
 * Defines an abstract handler for a pax field data collection.
 * Every pax data field type will be extending this class.
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
abstract class VBOCheckinPaxfieldType
{
	/**
	 * @var 	VBOCheckinPaxfield
	 */
	protected $field;

	/**
	 * @var 	string
	 */
	protected $collector_id;

	/**
	 * @var 	array
	 */
	protected $countries = array();

	/**
	 * @var 	string
	 */
	protected $container_class_attr = '';

	/**
	 * Class constructor.
	 * 
	 * @param 	VBOCheckinPaxfield 	$field
	 * @param 	string 				$collector_id
	 */
	public function __construct(VBOCheckinPaxfield $field, $collector_id = '')
	{
		// set the pax field registry object
		$this->field = $field;

		// register what pax data collector class invoked the field
		$this->collector_id = $collector_id;
	}

	/**
	 * Composes the id attribute for the current field.
	 * Public method that also the Views could use.
	 * 
	 * @return 	string 	the id attribute of the field.
	 */
	public function getFieldIdAttr()
	{
		// get the room index
		$room_index = $this->field->getRoomIndex();

		// get the guest number
		$guest_number = $this->field->getGuestNumber();

		// get the field number
		$field_ind = $this->field->getFieldNumber();

		return "vbo-room-pax-field-{$room_index}-{$guest_number}-{$field_ind}";
	}

	/**
	 * Composes the class attribute for the current field container.
	 * Public method that also the Views could use.
	 * 
	 * @return 	string 	the class attribute of the field container.
	 */
	public function getContainerClassAttr()
	{
		return $this->container_class_attr;
	}

	/**
	 * Composes the class attribute for the current field.
	 * 
	 * @return 	string 	the class attribute of the field.
	 */
	protected function getFieldClassAttr()
	{
		$pax_field_class = 'vbo-paxfield';

		$field_ind = $this->field->getFieldNumber();

		if ($field_ind < 3) {
			// use the first two fields to check via JS whether the Guests details are empty
			$pax_field_class .= ' vbo-paxfield-' . $this->field->getRoomIndex();
		}

		return $pax_field_class;
	}

	/**
	 * Composes the name attribute for the current input field.
	 * 
	 * @return 	string 	the name attribute of the input field.
	 */
	protected function getFieldNameAttr()
	{
		// get the current room index
		$room_index = $this->field->getRoomIndex();

		// get the guest number
		$guest_number = $this->field->getGuestNumber();

		// get field key
		$key = $this->field->getKey();

		return "guests[$room_index][$guest_number][$key]";
	}

	/**
	 * Composes the value attribute for the current field.
	 * 
	 * @return 	string 	the value attribute of the field.
	 */
	protected function getFieldValueAttr()
	{
		// get current guest data
		$current_guest = $this->field->getGuestData();

		// get current field key
		$paxk = $this->field->getKey();

		if (empty($current_guest) || empty($paxk)) {
			return '';
		}

		if (!isset($current_guest[$paxk])) {
			return '';
		}

		// return the current value (previously submitted) for this field
		return $current_guest[$paxk];
	}

	/**
	 * Helper method to allow field implementors to call methods declared by
	 * the data collection class that registered this type of field.
	 * 
	 * @param 	string 	$method 	the method to call from the collector.
	 * 
	 * @return 	null|mixed 	null if collector's method could not be called.
	 */
	protected function callCollector($method)
	{
		// access the collector class
		$collector = VBOCheckinPax::getInstance($this->collector_id);

		if (!$collector || empty($method) || !is_callable(array($collector, $method))) {
			return null;
		}

		// build extra arguments, if any
		$args = func_get_args();
		unset($args[0]);

		// invoke the collector's method
		return call_user_func_array(array($collector, $method), $args);
	}

	/**
	 * Returns the associative list of VBO countries.
	 * 
	 * @return 	array 	the associative list of VBO countries.
	 */
	protected function getCountries()
	{
		if (count($this->countries)) {
			// cached value
			return $this->countries;
		}

		// cache value
		$this->countries = VikBooking::getCountriesArray();

		return $this->countries;
	}

	/**
	 * Loads the necessary assets to render the datepicker calendar.
	 * Make sure to do it only once.
	 * 
	 * @return 	void
	 */
	protected function loadCalendarAssets()
	{
		static $cal_assets_loaded = null;

		if ($cal_assets_loaded) {
			// prevent multiple useless loadings
			return;
		}

		// let VikBooking load the necessary assets
		VikBooking::getVboApplication()->loadDatePicker();

		// cache loading
		$cal_assets_loaded = 1;

		return;
	}

	protected function loadSelectAssets()
	{
		static $sel_assets_loaded = null;

		if ($sel_assets_loaded) {
			// prevent multiple useless loadings
			return;
		}

		// let VikBooking load the necessary assets
		VikBooking::getVboApplication()->loadSelect2();

		// cache loading
		$sel_assets_loaded = 1;

		return;
	}

	/**
	 * Returns the date format in VBO for date, jQuery UI, Joomla/WordPress.
	 * 
	 * @param 	string 	$type 	the type of date format to get,
	 * 
	 * @return 	string
	 */
	protected function getDateFormat($type = 'date')
	{
		$nowdf = VikBooking::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
			$juidf = 'dd/mm/yy';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
			$juidf = 'mm/dd/yy';
		} else {
			$df = 'Y/m/d';
			$juidf = 'yy/mm/dd';
		}

		switch ($type) {
			case 'jui':
				return $juidf;
			case 'joomla':
			case 'wordpress':
				return $nowdf;
			default:
				return $df;
		}
	}

	/**
	 * Renders the current pax field HTML.
	 * 
	 * @return 	string 	the HTML string to render the field.
	 */
	abstract public function render();
}
