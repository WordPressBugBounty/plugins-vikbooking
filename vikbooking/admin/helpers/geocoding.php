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
 * Helper class for the geocoding functions.
 * 
 * @since 	1.4.0
 */
class VikBookingHelperGeocoding
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikBookingHelperGeocoding
	 */
	protected static $instance = null;

	/**
	 * The Google Maps API Key.
	 *
	 * @var string
	 */
	protected $gmaps_apikey = null;

	/**
	 * The current room geo-params.
	 *
	 * @var mixed
	 */
	protected $room_geoparams = null;

	/**
	 * The current room params.
	 *
	 * @var mixed
	 */
	protected $room_params = null;

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		// load the Google Maps API Key from the configuration settings
		$this->setGoogleMapsKey(VikBooking::getGoogleMapsKey());
	}

	/**
	 * Returns the global object, either
	 * a new instance or the existing instance
	 * if the class was already instantiated.
	 *
	 * @return 	self 	A new instance of the class.
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Sets the Google Maps API Key.
	 * 
	 * @param 	string 	the Google Maps API Key to set
	 * 
	 * @return 	self
	 */
	public function setGoogleMapsKey($apikey = null)
	{
		$this->gmaps_apikey = $apikey;

		return $this;
	}

	/**
	 * Gets the current Google Maps API Key.
	 * 
	 * @return 	mixed 	string or false if empty.
	 */
	public function getGoogleMapsKey()
	{
		if (empty($this->gmaps_apikey)) {
			return false;
		}
		
		return $this->gmaps_apikey;
	}

	/**
	 * Checks whether the Google Maps can be used.
	 * 
	 * @return 	bool 	true or false
	 */
	public function isSupported()
	{
		return (!empty($this->gmaps_apikey));
	}

	/**
	 * Loads the necessary assets to the document for Google Maps.
	 * 
	 * @param 	mixed 	string or array values values to append to the URL.
	 * 
	 * @return 	self
	 */
	public function loadAssets($params = null)
	{
		if (empty($this->gmaps_apikey)) {
			return $this;
		}

		// values like &callback=initMap could be passed as argument
		$qstring = '';

		if (!empty($params)) {
			if (is_string($params)) {
				$qstring = (substr($params, 0, 1) != '&' ? '&' : '') . $params;
			}
			if (is_array($params)) {
				$qstring = '&' . http_build_query($params);
			}
		}

		// load Google Maps API JS
		JFactory::getDocument()->addScript('https://maps.googleapis.com/maps/api/js?key=' . $this->gmaps_apikey . $qstring);

		return $this;
	}

	/**
	 * Gets the room parameters (one or all) related to the geocoding.
	 * Class variables are using to cache parameters into the buffer.
	 * 
	 * @param 	mixed 	$rparams 	string to be decoded or array/object params.
	 * @param 	string 	$key 		the optional param key to get.
	 * @param 	mixed 	$def 		the default room param value to return.
	 * 
	 * @return 	mixed 	object with all params or empty object, requested param otherwise.
	 */
	public function getRoomGeoParams($rparams, $key = null, $def = null)
	{
		$geoparams = new stdClass;

		if (empty($rparams)) {
			return !empty($key) ? $def : $geoparams;
		}

		if ($this->room_geoparams === null || $this->room_params != $rparams || $key === null) {
			if (is_string($rparams)) {
				$rparams_obj = json_decode($rparams);
				if (!is_object($rparams_obj) || !isset($rparams_obj->geo)) {
					return !empty($key) ? $def : $geoparams;
				}
				$geoparams = $rparams_obj->geo;
			} elseif (is_array($rparams)) {
				// rooms parameters already decoded, convert it to object
				$geoparams = json_decode(json_encode($rparams));
			} elseif (is_object($rparams)) {
				// rooms parameters already decoded
				$geoparams = $rparams;
			}

			if (is_object($geoparams) && isset($geoparams->geo)) {
				$geoparams = $geoparams->geo;
			}

			// update global vars
			$this->room_params = $rparams;
			$this->room_geoparams = $geoparams;
		}

		if (($key == 'units_pos' || $key === null) && is_object($this->room_geoparams) && isset($this->room_geoparams->units_pos)) {
			$units_pos = $this->room_geoparams->units_pos;
			// make sure to adjust some properties of type float (number) so that they are not strings
			foreach ($units_pos as $k => $unit) {
				if (isset($unit->lat)) {
					$units_pos->{$k}->lat = (float)$unit->lat;
				}
				if (isset($unit->lng)) {
					$units_pos->{$k}->lng = (float)$unit->lng;
				}
				if (isset($unit->icon)) {
					foreach ($unit->icon as $icon_prop => $icon_val) {
						if ($icon_prop == 'fillOpacity') {
							$units_pos->{$k}->icon->{$icon_prop} = (float)$icon_val;
						}
						if ($icon_prop == 'scale') {
							$units_pos->{$k}->icon->{$icon_prop} = (float)$icon_val;
						}
						if ($icon_prop == 'strokeWeight') {
							$units_pos->{$k}->icon->{$icon_prop} = (float)$icon_val;
						}
						if ($icon_prop == 'anchor' && isset($icon_val->x) && isset($icon_val->y)) {
							// anchor point object is composed of coordinates with numbers
							$units_pos->{$k}->icon->{$icon_prop}->x = (float)$icon_val->x;
							$units_pos->{$k}->icon->{$icon_prop}->y = (float)$icon_val->y;
						}
						if (($icon_prop == 'size' || $icon_prop == 'scaledSize') && isset($icon_val->width) && isset($icon_val->height)) {
							// size and scaledSize object is composed of size width and height numbers
							$units_pos->{$k}->icon->{$icon_prop}->width = (int)$icon_val->width;
							$units_pos->{$k}->icon->{$icon_prop}->height = (int)$icon_val->height;
						}
					}
				}
			}
			$this->room_geoparams->units_pos = $units_pos;
		}

		if (!empty($key)) {
			return is_object($this->room_geoparams) && isset($this->room_geoparams->{$key}) ? $this->room_geoparams->{$key} : $def;
		}

		return $geoparams;
	}

	/**
	 * Gets the list of default marker symbols (SVG icons) for Google Maps.
	 * 
	 * @return 	array 			list of default marker symbols.
	 */
	public function getDefaultMarkerSymbols()
	{
		$symbols = array();
		$group = 'default';
		$color = '#000000';
		$opacity = 1;

		$symbol = new stdClass;
		$symbol->id = 'hotel';
		$symbol->name = 'Hotel';
		$symbol->group = $group;
		$symbol->fill = $color;
		$symbol->opacity = $opacity;
		$symbol->width = 48.4;
		$symbol->height = 43;
		$symbol->path = 'M47,5.4c0.7,0,1.3-0.6,1.3-1.3V1.3C48.4,0.6,47.8,0,47,0H1.3C0.6,0,0,0.6,0,1.3V4c0,0.7,0.6,1.3,1.3,1.3h1.3v32.2H1.3 C0.6,37.6,0,38.2,0,39v2.7C0,42.4,0.6,43,1.3,43h20.2v-6.7c0-0.7,0.6-1.3,1.3-1.3h2.7c0.7,0,1.3,0.6,1.3,1.3V43H47 c0.7,0,1.3-0.6,1.3-1.3V39c0-0.7-0.6-1.3-1.3-1.3h-1.3V5.4H47z M21.5,9.1c0-0.5,0.5-1.1,1.1-1.1h3.2c0.5,0,1.1,0.5,1.1,1.1v3.2 c0,0.5-0.5,1.1-1.1,1.1h-3.2c-0.5,0-1.1-0.5-1.1-1.1L21.5,9.1L21.5,9.1z M21.5,17.2c0-0.5,0.5-1.1,1.1-1.1h3.2 c0.5,0,1.1,0.5,1.1,1.1v3.2c0,0.5-0.5,1.1-1.1,1.1h-3.2c-0.5,0-1.1-0.5-1.1-1.1L21.5,17.2L21.5,17.2z M10.8,9.1 c0-0.5,0.5-1.1,1.1-1.1h3.2c0.5,0,1.1,0.5,1.1,1.1v3.2c0,0.5-0.5,1.1-1.1,1.1h-3.2c-0.5,0-1.1-0.5-1.1-1.1L10.8,9.1L10.8,9.1z  M15.1,21.5h-3.2c-0.5,0-1.1-0.5-1.1-1.1v-3.2c0-0.5,0.5-1.1,1.1-1.1H15c0.5,0,1.1,0.5,1.1,1.1v3.2C16.1,21,15.6,21.5,15.1,21.5 L15.1,21.5z M16.1,32.2c0-4.5,3.6-8.1,8.1-8.1s8.1,3.6,8.1,8.1H16.1z M37.6,20.4c0,0.5-0.5,1.1-1.1,1.1h-3.2c-0.5,0-1.1-0.5-1.1-1.1 v-3.2c0-0.5,0.5-1.1,1.1-1.1h3.2c0.5,0,1.1,0.5,1.1,1.1V20.4L37.6,20.4z M37.6,12.4c0,0.5-0.5,1.1-1.1,1.1h-3.2 c-0.5,0-1.1-0.5-1.1-1.1V9.1c0-0.5,0.5-1.1,1.1-1.1h3.2c0.5,0,1.1,0.5,1.1,1.1V12.4z';
		array_push($symbols, $symbol);

		$symbol = new stdClass;
		$symbol->id = 'home';
		$symbol->name = 'Home';
		$symbol->group = $group;
		$symbol->fill = $color;
		$symbol->opacity = $opacity;
		$symbol->width = 56.8;
		$symbol->height = 43;
		$symbol->path = 'M27.7,11.2L10,25.8v15.7c0,0.8,0.7,1.5,1.5,1.5l10.8,0c0.8,0,1.5-0.7,1.5-1.5v-9.2c0-0.8,0.7-1.5,1.5-1.5h6.1 c0.8,0,1.5,0.7,1.5,1.5v9.2c0,0.8,0.7,1.5,1.5,1.5c0,0,0,0,0,0l10.8,0c0.8,0,1.5-0.7,1.5-1.5V25.7L29.1,11.2 C28.7,10.8,28.1,10.8,27.7,11.2L27.7,11.2z M55.6,21.1l-8-6.6V1.2c0-0.6-0.5-1.2-1.2-1.2h-5.4c-0.6,0-1.2,0.5-1.2,1.2v7l-8.6-7.1 c-1.7-1.4-4.2-1.4-5.9,0l-24.3,20c-0.5,0.4-0.6,1.1-0.2,1.6c0,0,0,0,0,0l2.4,3c0.4,0.5,1.1,0.6,1.6,0.2c0,0,0,0,0,0L27.7,7.2 c0.4-0.3,1-0.3,1.5,0l22.6,18.6c0.5,0.4,1.2,0.3,1.6-0.2c0,0,0,0,0,0l2.4-3C56.2,22.2,56.1,21.5,55.6,21.1 C55.6,21.1,55.6,21.1,55.6,21.1L55.6,21.1z';
		array_push($symbols, $symbol);

		$symbol = new stdClass;
		$symbol->id = 'city';
		$symbol->name = 'City';
		$symbol->group = $group;
		$symbol->fill = $color;
		$symbol->opacity = $opacity;
		$symbol->width = 53.8;
		$symbol->height = 43;
		$symbol->path = 'M51.7,16.1H40.3V2c0-1.1-0.9-2-2-2H26.2c-1.1,0-2,0.9-2,2v6h-5.4V1.3c0-0.7-0.6-1.3-1.3-1.3h-1.3c-0.7,0-1.3,0.6-1.3,1.3 v6.7H9.4V1.3C9.4,0.6,8.8,0,8.1,0H6.7C6,0,5.4,0.6,5.4,1.3v6.7H2c-1.1,0-2,0.9-2,2v30.2C0,41.8,1.2,43,2.7,43h48.4 c1.5,0,2.7-1.2,2.7-2.7V18.1C53.8,17,52.8,16.1,51.7,16.1z M10.8,33.9c0,0.6-0.5,1-1,1H6.4c-0.6,0-1-0.5-1-1v-3.4c0-0.6,0.5-1,1-1 h3.4c0.6,0,1,0.5,1,1V33.9z M10.8,25.9c0,0.6-0.5,1-1,1H6.4c-0.6,0-1-0.5-1-1v-3.4c0-0.6,0.5-1,1-1h3.4c0.6,0,1,0.5,1,1V25.9z  M10.8,17.8c0,0.6-0.5,1-1,1H6.4c-0.6,0-1-0.5-1-1v-3.4c0-0.6,0.5-1,1-1h3.4c0.6,0,1,0.5,1,1V17.8z M21.5,33.9c0,0.6-0.5,1-1,1h-3.4 c-0.6,0-1-0.5-1-1v-3.4c0-0.6,0.5-1,1-1h3.4c0.6,0,1,0.5,1,1V33.9z M21.5,25.9c0,0.6-0.5,1-1,1h-3.4c-0.6,0-1-0.5-1-1v-3.4 c0-0.6,0.5-1,1-1h3.4c0.6,0,1,0.5,1,1V25.9z M21.5,17.8c0,0.6-0.5,1-1,1h-3.4c-0.6,0-1-0.5-1-1v-3.4c0-0.6,0.5-1,1-1h3.4 c0.6,0,1,0.5,1,1V17.8z M34.9,25.9c0,0.6-0.5,1-1,1h-3.4c-0.6,0-1-0.5-1-1v-3.4c0-0.6,0.5-1,1-1h3.4c0.6,0,1,0.5,1,1V25.9z  M34.9,17.8c0,0.6-0.5,1-1,1h-3.4c-0.6,0-1-0.5-1-1v-3.4c0-0.6,0.5-1,1-1h3.4c0.6,0,1,0.5,1,1V17.8z M34.9,9.7c0,0.6-0.5,1-1,1h-3.4 c-0.6,0-1-0.5-1-1V6.4c0-0.6,0.5-1,1-1h3.4c0.6,0,1,0.5,1,1V9.7z M48.4,33.9c0,0.6-0.5,1-1,1H44c-0.6,0-1-0.5-1-1v-3.4 c0-0.6,0.5-1,1-1h3.4c0.6,0,1,0.5,1,1V33.9z M48.4,25.9c0,0.6-0.5,1-1,1H44c-0.6,0-1-0.5-1-1v-3.4c0-0.6,0.5-1,1-1h3.4 c0.6,0,1,0.5,1,1V25.9z';
		array_push($symbols, $symbol);

		$symbol = new stdClass;
		$symbol->id = 'campground';
		$symbol->name = 'Campground';
		$symbol->group = $group;
		$symbol->fill = $color;
		$symbol->opacity = $opacity;
		$symbol->width = 53.7;
		$symbol->height = 43;
		$symbol->path = 'M52.4,37.6h-2.1L30.2,9.9l4.5-6.2c0.4-0.6,0.3-1.4-0.3-1.9l-2.2-1.6c-0.6-0.4-1.4-0.3-1.9,0.3l-3.5,4.8l-3.5-4.8 C23,0,22.1-0.2,21.5,0.3l-2.2,1.6c-0.6,0.4-0.7,1.3-0.3,1.9l4.5,6.2L3.4,37.6H1.3C0.6,37.6,0,38.2,0,39v2.7C0,42.4,0.6,43,1.3,43 h51.1c0.7,0,1.3-0.6,1.3-1.3V39C53.7,38.2,53.1,37.6,52.4,37.6z M26.9,24.2l9.8,13.4H17.1L26.9,24.2z';
		array_push($symbols, $symbol);

		$symbol = new stdClass;
		$symbol->id = 'trailer';
		$symbol->name = 'Trailer';
		$symbol->group = $group;
		$symbol->fill = $color;
		$symbol->opacity = $opacity;
		$symbol->width = 66.2;
		$symbol->height = 43;
		$symbol->path = 'M64.5,26.5h-8.3V1.7c0-0.9-0.7-1.7-1.7-1.7H1.7C0.7,0,0,0.7,0,1.7v29.8c0,0.9,0.7,1.7,1.7,1.7h5.1c0.8-5.6,5.6-9.9,11.4-9.9 s10.6,4.3,11.4,9.9h34.9c0.9,0,1.7-0.7,1.7-1.7v-3.3C66.2,27.2,65.4,26.5,64.5,26.5z M9.9,18.6c-1.2,0.6-2.3,1.3-3.3,2.1V7.4 C6.6,7,7,6.6,7.4,6.6h1.7c0.5,0,0.8,0.4,0.8,0.8V18.6z M19.8,16.7c-0.5-0.1-1.1-0.1-1.7-0.1c-0.6,0-1.1,0.1-1.7,0.1V7.4 c0-0.5,0.4-0.8,0.8-0.8H19c0.5,0,0.8,0.4,0.8,0.8V16.7z M29.8,20.7c-1-0.8-2.1-1.5-3.3-2.1V7.4c0-0.5,0.4-0.8,0.8-0.8h1.7 c0.5,0,0.8,0.4,0.8,0.8V20.7z M39.7,26.5h-3.3v-19c0-0.5,0.4-0.8,0.8-0.8h1.7c0.5,0,0.8,0.4,0.8,0.8V26.5z M49.6,26.5h-3.3v-19 c0-0.5,0.4-0.8,0.8-0.8h1.7c0.5,0,0.8,0.4,0.8,0.8V26.5z M18.2,26.5c-4.6,0-8.3,3.7-8.3,8.3s3.7,8.3,8.3,8.3s8.3-3.7,8.3-8.3 S22.8,26.5,18.2,26.5z M18.2,38c-1.8,0-3.3-1.5-3.3-3.3s1.5-3.3,3.3-3.3s3.3,1.5,3.3,3.3S20,38,18.2,38z';
		array_push($symbols, $symbol);

		return $symbols;
	}

	/**
	 * Gets the list of marker symbols for Google Maps.
	 * 
	 * @param 	string 	$group 	optionally filter the symbols by group
	 * 
	 * @return 	array 			list of marker symbols.
	 */
	public function getMarkerSymbols($group = null)
	{
		$dbo = JFactory::getDbo();
		$symbols = array();
		
		$q = "SELECT `param`, `setting` FROM `#__vikbooking_config` WHERE `param` LIKE 'marker_symbols_%' ORDER BY `param` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$all_symbols = $dbo->loadAssocList();
			foreach ($all_symbols as $symbols_data) {
				$list = json_decode($symbols_data['setting']);
				if (!is_array($list) || !count($list)) {
					continue;
				}
				$symbols = array_merge($symbols, $list);
			}
		} else {
			$default_symbols = $this->getDefaultMarkerSymbols();
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('marker_symbols_default', " . $dbo->quote(json_encode($default_symbols)) . ");";
			$dbo->setQuery($q);
			$dbo->execute();

			return $default_symbols;
		}

		$symbols = count($symbols) ? $symbols : $this->getDefaultMarkerSymbols();

		if (!empty($group)) {
			// filter symbol objects by group
			$group = strtolower($group);
			foreach ($symbols as $k => $symbol) {
				if (!isset($symbol->group)) {
					// filtering is impossible
					continue;
				}
				if ($symbol->group != $group) {
					unset($symbols[$k]);
				}
			}
			// reset keys
			$symbols = array_values($symbols);
		}

		return $symbols;
	}

	/**
	 * Gets one marker symbol for Google Maps.
	 * 
	 * @param 	string 	$id 	the symbol identifier
	 * @param 	string 	$group 	optionally filter the symbols by group
	 * 
	 * @return 	mixed 			marker symbol object or null.
	 */
	public function getMarkerSymbol($id, $group = null)
	{
		if (empty($id)) {
			return null;
		}

		$symbols = $this->getMarkerSymbols($group);
		foreach ($symbols as $symbol) {
			if (isset($symbol->id) && $symbol->id == $id) {
				// symbol found
				return $symbol;
			}
		}

		// symbol not found
		return null;
	}

	/**
	 * AJAX method called by the main controller when adding
	 * or updating a new or existing SVG symbol icon.
	 * 
	 * @return 	mixed
	 * 
	 * @throws 	Exception
	 */
	public function storeSvgSymbol()
	{
		$symbol = VikRequest::getVar('symbol', array(), 'request', 'array');
		if (!is_array($symbol) || empty($symbol['id'])) {
			throw new Exception("Symbol ID not found", 404);
		}

		if (empty($symbol['name'])) {
			throw new Exception("Symbol name is empty", 500);
		}

		if (empty($symbol['path'])) {
			throw new Exception("Symbol path is empty", 500);
		}

		// sanitize values
		$symbol['group'] = !empty($symbol['group']) ? strtolower($symbol['group']) : 'default';
		if (isset($symbol['opacity'])) {
			// this must be a float, not a string
			$symbol['opacity'] = floatval($symbol['opacity']);
		}

		$current_symbol = $this->getMarkerSymbol($symbol['id']);
		$addnew = false;
		if (is_object($current_symbol)) {
			// force symbol group because ID exists
			$symbol['group'] = $current_symbol->group;
			// update existing marker symbol
			foreach ($current_symbol as $prop => $val) {
				if (isset($symbol[$prop])) {
					// overwrite existing property taken from the new icon
					$current_symbol->{$prop} = $symbol[$prop];
				}
			}
		} else {
			// create new marker symbol
			$addnew = true;
			$current_symbol = (object)$symbol;
		}

		$dbo = JFactory::getDbo();

		$group_symbols = array();
		$update = false;
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`=" . $dbo->quote('marker_symbols_' . $symbol['group']) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$update = true;
			$group_symbols = json_decode($dbo->loadResult());
			$group_symbols = !is_array($group_symbols) ? array() : $group_symbols;
		}

		if ($addnew) {
			// push new symbol
			array_push($group_symbols, $current_symbol);
		} else {
			// find current symbol
			$found = false;
			foreach ($group_symbols as $k => $s) {
				if ($s->id == $symbol['id']) {
					// replace existing symbol
					$found = true;
					$group_symbols[$k] = $current_symbol;
					break;
				}
			}
			if (!$found) {
				// push new symbol as it was not found
				array_push($group_symbols, $current_symbol);
			}
		}

		// update values in db
		if ($update) {
			$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $dbo->quote(json_encode($group_symbols)) . " WHERE `param`=" . $dbo->quote('marker_symbols_' . $symbol['group']) . ";";
		} else {
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES (" . $dbo->quote('marker_symbols_' . $symbol['group']) . ", " . $dbo->quote(json_encode($group_symbols)) . ");";
		}
		$dbo->setQuery($q);
		$dbo->execute();

		// return the new symbol object just stored
		return $current_symbol;
	}

	/**
	 * AJAX method called by the main controller when needing
	 * to delete an existing SVG symbol icon.
	 * 
	 * @return 	mixed
	 * 
	 * @throws 	Exception
	 */
	public function deleteSvgSymbol()
	{
		$symbol_id = VikRequest::getString('symbol_id', '', 'request');
		if (empty($symbol_id)) {
			throw new Exception("Symbol ID is empty", 404);
		}

		$current_symbol = $this->getMarkerSymbol($symbol_id);
		if (!is_object($current_symbol)) {
			throw new Exception("Symbol ID not found", 404);
		}

		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`=" . $dbo->quote('marker_symbols_' . $current_symbol->group) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception("Symbol Group not found", 404);
		}
		$records = json_decode($dbo->loadResult());
		if (!is_array($records) || !count($records)) {
			throw new Exception("Symbol Group has got no symbols", 404);
		}

		$found = false;
		foreach ($records as $k => $v) {
			if ($v->id == $symbol_id) {
				$found = true;
				unset($records[$k]);
				break;
			}
		}

		if (!$found) {
			throw new Exception("Symbol ID not found in all symbols of this group", 404);
		}

		// reset keys
		$records = array_values($records);

		// update db
		$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $dbo->quote(json_encode($records)) . " WHERE `param`=" . $dbo->quote('marker_symbols_' . $current_symbol->group) . ";";
		$dbo->setQuery($q);
		$dbo->execute();

		return $symbol_id;
	}

	/**
	 * AJAX method called by the main controller when starting to work
	 * on the geo coding information for one specific room.
	 * 
	 * @return 	int 		a simple result integer code identifier.
	 * 
	 * @throws 	Exception
	 */
	public function initRoomGeoTransient()
	{
		$dbo = JFactory::getDbo();
		$room_id = VikRequest::getInt('room_id', 0, 'request');

		$param = "geocoding_transient_room_" . $room_id;
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`=" . $dbo->quote($param) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			// make the temporary record empty
			$q = "UPDATE `#__vikbooking_config` SET `setting`='' WHERE `param`=" . $dbo->quote($param) . ";";
			$dbo->setQuery($q);
			$dbo->execute();

			return 2;
		}

		return 1;
	}

	/**
	 * AJAX method called by the main controller when updating
	 * the geo coding information for one specific room.
	 * 
	 * @return 	object 		the geo params stored.
	 * 
	 * @throws 	Exception
	 */
	public function updateRoomGeoTransient()
	{
		$dbo = JFactory::getDbo();

		$room_id = VikRequest::getInt('room_id', 0, 'request');
		$geo_enabled = VikRequest::getInt('geo_enabled', 0, 'request');
		$geo_address = VikRequest::getString('geo_address', '', 'request');
		$geo_latitude = VikRequest::getFloat('geo_latitude', 0, 'request');
		$geo_longitude = VikRequest::getFloat('geo_longitude', 0, 'request');
		$geo_zoom = VikRequest::getInt('geo_zoom', 1, 'request');
		$geo_mtype = VikRequest::getString('geo_mtype', '', 'request');
		$geo_height = VikRequest::getInt('geo_height', 100, 'request');
		$geo_markers_multi = VikRequest::getInt('geo_markers_multi', 0, 'request');
		$geo_marker_lat = VikRequest::getFloat('geo_marker_lat', 0, 'request');
		$geo_marker_lng = VikRequest::getFloat('geo_marker_lng', 0, 'request');
		$geo_marker_hide = VikRequest::getInt('geo_marker_hide', 0, 'request');
		$geo_units_pos = VikRequest::getVar('geo_units_pos', array(), 'request', 'array');
		$geo_goverlay = VikRequest::getInt('geo_goverlay', 0, 'request');
		$geo_overlay_img = VikRequest::getString('geo_overlay_img', '', 'request');
		$geo_overlay_south = VikRequest::getFloat('geo_overlay_south', 0, 'request');
		$geo_overlay_west = VikRequest::getFloat('geo_overlay_west', 0, 'request');
		$geo_overlay_north = VikRequest::getFloat('geo_overlay_north', 0, 'request');
		$geo_overlay_east = VikRequest::getFloat('geo_overlay_east', 0, 'request');

		// prepare geo params object
		$geo_params = new stdClass;
		/**
		 * Property "enabled" must ALWAYS be the first one
		 * 
		 * @see 	getImportableConfigRooms()
		 */
		$geo_params->enabled = $geo_enabled;
		//
		$geo_params->address = $geo_address;
		$geo_params->latitude = $geo_latitude;
		$geo_params->longitude = $geo_longitude;
		$geo_params->zoom = $geo_zoom;
		$geo_params->mtype = $geo_mtype;
		$geo_params->height = $geo_height;
		$geo_params->markers_multi = $geo_markers_multi;
		$geo_params->marker_lat = $geo_marker_lat;
		$geo_params->marker_lng = $geo_marker_lng;
		$geo_params->marker_hide = $geo_marker_hide;
		$geo_params->units_pos = $geo_units_pos;
		$geo_params->goverlay = $geo_goverlay;
		$geo_params->overlay_img = $geo_overlay_img;
		$geo_params->overlay_south = $geo_overlay_south;
		$geo_params->overlay_west = $geo_overlay_west;
		$geo_params->overlay_north = $geo_overlay_north;
		$geo_params->overlay_east = $geo_overlay_east;

		// check transient record
		$param = "geocoding_transient_room_" . $room_id;
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`=" . $dbo->quote($param) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $dbo->quote(json_encode($geo_params)) . " WHERE `param`=" . $dbo->quote($param) . ";";
		} else {
			$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES (" . $dbo->quote($param) . ", " . $dbo->quote(json_encode($geo_params)) . ");";
		}
		// update transient record with new geo params
		$dbo->setQuery($q);
		$dbo->execute();

		return $geo_params;
	}

	/**
	 * Retrieves the last transient for the given room ID (or new room). This
	 * way the room geo params are ready to be stored by the main controller.
	 * 
	 * @param 	int 	$room_id 	the room ID (0 for new).
	 * 
	 * @return 	mixed 				geo params object or false.
	 */
	public function getRoomGeoTransient($room_id)
	{
		$dbo = JFactory::getDbo();
		$param = "geocoding_transient_room_" . (int)$room_id;

		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`=" . $dbo->quote($param) . ";";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			$geo_enabled = VikRequest::getInt('geo_enabled', 0, 'request');
			if ($geo_enabled) {
				VikError::raiseWarning('', 'no records found ' . $param);
			}
			return false;
		}
		$geo_params = json_decode($dbo->loadResult());

		return is_object($geo_params) ? $geo_params : false;
	}

	/**
	 * Returns a list of room-types with a complete geocoding information that could be imported.
	 * This facilitates the setup of the geographic information for a new room-type.
	 * 
	 * @param 	int 	$exclude_id 	the current room to exclude, 0 for new.
	 * 
	 * @return 	array 	list of room-types with an importable geo configuration.
	 */
	public function getImportableConfigRooms($exclude_id = 0)
	{
		$importable = array();

		$dbo = JFactory::getDbo();
		$clauses = array();

		if ($exclude_id > 0) {
			array_push($clauses, '`id` != ' . $exclude_id);
		}

		// fetch all rooms with the geo information enabled
		array_push($clauses, '`params` LIKE ' . $dbo->quote('%"geo":{"enabled":1%'));

		$q = "SELECT `id`, `name` FROM `#__vikbooking_rooms` WHERE " . implode(' AND ', $clauses) . " ORDER BY `name` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$importable = $dbo->loadAssocList();
		}

		return $importable;
	}

	/**
	 * AJAX method called by the main controller when requesting
	 * to import the geo coding information for one specific room.
	 * 
	 * @return 	object 		the geo params of the room requested.
	 * 
	 * @throws 	Exception
	 */
	public function importRoomGeoConfig()
	{
		$dbo = JFactory::getDbo();

		$room_id = VikRequest::getInt('room_id', 0, 'request');
		
		$q = "SELECT `id`, `name`, `params` FROM `#__vikbooking_rooms` WHERE id={$room_id};";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			throw new Exception("Room not found for importing geo config", 404);
		}
		$room = $dbo->loadAssoc();
		$rparams = json_decode($room['params']);
		if (!is_object($rparams)) {
			throw new Exception("Unable to decode room params", 500);
		}

		// get all geo params
		$geo_params = $this->getRoomGeoParams($rparams);

		if (!is_object($geo_params)) {
			throw new Exception("Unable to get room geo params", 500);
		}

		return $geo_params;
	}
}
