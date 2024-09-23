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
 * Helper class for the administrator widgets.
 * 
 * @since 	1.14 (J) - 1.4.0 (WP)
 */
class VikBookingHelperAdminWidgets
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikBookingHelperAdminWidgets
	 */
	protected static $instance = null;

	/**
	 * An array to store some cached/static values.
	 *
	 * @var array
	 */
	protected static $helper = null;

	/**
	 * The database handler instance.
	 *
	 * @var object
	 */
	protected $dbo;

	/**
	 * The list of widget instances loaded.
	 *
	 * @var array
	 */
	protected $widgets;

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		static::$helper = [];
		$this->dbo = JFactory::getDbo();
		$this->widgets = [];
		$this->load();
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
	 * Loads a list of all available admin widgets.
	 *
	 * @return 	self
	 */
	protected function load()
	{
		/** @var VBOPlatformDispatcherInterface */
		$dispatcher = VBOFactory::getPlatform()->getDispatcher();

		// require main/parent admin-widget class
		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'admin_widget.php');

		$widgets_base   = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR;
		$widgets_files  = glob($widgets_base . '*.php');
		$widgets_banned = [];

		/**
		 * Trigger event to let other plugins register additional widgets.
		 *
		 * @return 	array 	A list of supported widgets.
		 */
		$list = $dispatcher->filter('onLoadAdminWidgets');

		foreach ($list as $chunk) {
			// merge default widget files with the returned ones
			$widgets_files = array_merge($widgets_files, (array)$chunk);
		}

		/**
		 * Trigger event to let other plugins unregister specific widgets.
		 *
		 * @return 	array 	A list of widget identifiers to unload.
		 * 
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$unloaded = $dispatcher->filter('onUnloadAdminWidgets');

		foreach ($unloaded as $chunk) {
			// merge all the the returned ones
			$widgets_banned = array_merge($widgets_banned, (array)$chunk);
		}

		// parse all admin widgets
		foreach ($widgets_files as $wf) {
			try {
				/**
				 * Require widget class file only if available, to allow registering classes at
				 * runtime by third-party plugins without needing to have a dedicated PHP file.
				 * The widget class must obviously exist all the times, but the file must not.
				 */
				if (is_file($wf)) {
					require_once($wf);
				}

				// widget identifier
				$widget_identif = basename($wf, '.php');

				// check if the widget was unloaded
				if (in_array($widget_identif, $widgets_banned)) {
					continue;
				}

				// build widget class name
				$classname  = 'VikBookingAdminWidget' . str_replace(' ', '', ucwords(str_replace('_', ' ', $widget_identif)));

				if (class_exists($classname)) {
					// instantiate widget object
					$widget = new $classname();

					// push widget object
					array_push($this->widgets, $widget);
				}
			} catch (Exception $e) {
				// do nothing
			}
		}

		return $this;
	}

	/**
	 * Gets the default map of admin widgets.
	 *
	 * @return 	object 	the associative map of sections,
	 * 					containers and widgets.
	 */
	protected function getDefaultWidgetsMap()
	{
		// sections container
		$sections = [];

		// build default sections

		// new section
		$section = new stdClass;
		$section->name = 'Top';
		$section->containers = [];
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = [
			'sticky_notes',
			'arriving_today',
			'departing_today',
			'check_availability',
			'latest_events',
		];
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'small';
		$container->widgets = [
			'guest_messages',
			'latest_from_guests',
			'booking_details',
			'visitors_counter',
		];
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'small';
		$container->widgets = [
			'forecast',
			'bookings_calendar',
			'reminders',
		];
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// new section
		$section = new stdClass;
		$section->name = 'Middle';
		$section->containers = [];
		// start container
		$container = new stdClass;
		$container->size = 'full';
		$container->widgets = [
			'today_rooms_occupancy',
			'weekly_bookings',
		];
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// new section
		$section = new stdClass;
		$section->name = 'Middle 2';
		$section->containers = [];
		// start container
		$container = new stdClass;
		$container->size = 'small';
		$container->widgets = [
			'latest_events',
		];
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'small';
		$container->widgets = [
			'rates_flow',
		];
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = [
			'finance',
			'currency_converter',
		];
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// new section
		$section = new stdClass;
		$section->name = 'Bottom';
		$section->containers = [];
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = [
			'last_reservations',
		];
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = [
			'next_bookings',
		];
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// new section
		$section = new stdClass;
		$section->name = 'Bottom 2';
		$section->containers = [];
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = [
			'orphan_dates',
			'rooms_locked',
		];
		// push container
		array_push($section->containers, $container);
		// start container
		$container = new stdClass;
		$container->size = 'medium';
		$container->widgets = [
			'sticky_notes',
		];
		// push container
		array_push($section->containers, $container);
		// push section
		array_push($sections, $section);

		// compose the final map object
		$map = new stdClass;
		$map->sections = $sections;
		
		return $map;
	}

	/**
	 * Gets the list of admin widgets instantiated.
	 *
	 * @return 	array 	list of admin widget objects.
	 */
	public function getWidgets()
	{
		return $this->widgets;
	}

	/**
	 * Gets a single admin widget instantiated.
	 * 
	 * @param 	string 	$id 	the widget identifier.
	 *
	 * @return 	mixed 	the admin widget object, false otherwise.
	 */
	public function getWidget($id)
	{
		$id = $this->simplifyId($id);

		foreach ($this->widgets as $widget) {
			if ($widget->getIdentifier() == $id) {
				return $widget;
			}
		}

		return false;
	}

	/**
	 * Gets a list of sorted widget names, ids, descriptions, icons and styles.
	 * If called with pre-loading, widgets will be able to load their CSS and JS
	 * assets. Moreover, pre-loading a widget can return a watch-data object to
	 * implement the notifications scheduling of a specific widget.
	 * 
	 * @param 	bool 	$preload 	whether to call the widget's preload method.
	 *
	 * @return 	array 	associative and sorted widgets list.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)  added support for icons and style names.
	 * 									 implemented widgets preloading for assets.
	 */
	public function getWidgetNames($preload = false)
	{
		// containers
		$names = [];
		$pool  = [];
		$watch = [];

		foreach ($this->widgets as $widget) {
			// get widget details
			$id 	= $widget->getIdentifier();
			$name 	= $widget->getName();
			$descr 	= $widget->getDescription();

			if ($preload) {
				// preload widget's CSS/JS assets (if any)
				$watch_data = $widget->preload();
				if ($watch_data) {
					// assign watching data to this widget
					$watch[$id] = $watch_data;
				}
			}

			// build widget info object
			$wtdata = new stdClass;
			$wtdata->id 	= $id;
			$wtdata->name 	= $name;
			$wtdata->descr 	= $descr;
			$wtdata->icon 	= $widget->getIcon();
			$wtdata->style 	= str_replace(array(' ', '_'), '-', $widget->getStyleName());

			// set object for sorting
			$names[$name] 	= $wtdata;
		}

		// apply sorting by name
		ksort($names);

		// push sorted widgets to pool
		foreach ($names as $wtdata) {
			$pool[] = $wtdata;
		}

		/**
		 * In case some widgets were pre-loaded for watching certain
		 * events, enqueue the watch-data list to the document.
		 */
		if (count($watch)) {
			// schedule the browser notifications watching data
			VBONotificationScheduler::getInstance()->registerWatchData($watch);
		}

		// return the list of admin widgets
		return $pool;
	}

	/**
	 * Gets the current or default map of admin widgets.
	 * If no map currently set, stores the default map.
	 *
	 * @return 	array 	the associative map of sections,
	 * 					containers and widgets.
	 */
	public function getWidgetsMap()
	{
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='admin_widgets_map';";
		$this->dbo->setQuery($q);
		$map = $this->dbo->loadResult();
		if ($map) {
			$map = json_decode($map);
			return is_object($map) && !empty($map->sections) && is_array($map->sections) ? $map : $this->getDefaultWidgetsMap();
		}

		$default_map = $this->getDefaultWidgetsMap();
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('admin_widgets_map', " . $this->dbo->quote(json_encode($default_map)) . ");";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		return $default_map;
	}

	/**
	 * Updates the map of admin widgets.
	 * 
	 * @param 	array 	$sections 	the list of sections for the map.
	 *
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function updateWidgetsMap($sections)
	{
		if (!is_array($sections) || !count($sections)) {
			return false;
		}

		// prepare new map object
		$map = new stdClass;
		$map->sections = $sections;

		$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $this->dbo->quote(json_encode($map)) . " WHERE `param`='admin_widgets_map';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		return true;
	}

	/**
	 * Restores the default admin widgets map.
	 * First it resets the settings of each widget.
	 *
	 * @return 	bool 	True on success, false otherwise.
	 */
	public function restoreDefaultWidgetsMap()
	{
		foreach ($this->widgets as $widget) {
			$widget->resetSettings();
		}

		$default_map = $this->getDefaultWidgetsMap();

		return $this->updateWidgetsMap($default_map->sections);
	}

	/**
	 * Forces the rendering of a specific widget identifier.
	 * 
	 * @param 	string 				$id 	the widget identifier.
	 * @param 	VBOMultitaskData 	$data 	optional multitask data for the widget.
	 *
	 * @return 	void
	 */
	public function renderWidget($id, VBOMultitaskData $data = null)
	{
		$id = $this->simplifyId($id);

		foreach ($this->widgets as $widget) {
			if ($widget->getIdentifier() == $id) {
				// render the requested widget
				$widget->render($data);

				// return void
				return;
			}
		}
	}

	/**
	 * Maps the size identifier to a CSS class.
	 * 
	 * @param 	string 	$size 	the container size identifier.
	 *
	 * @return 	string 	the full CSS class for the container.
	 */
	public function getContainerCssClass($size)
	{
		$css_size_map = [
			'small'  => 'vbo-admin-widgets-container-small',
			'medium' => 'vbo-admin-widgets-container-medium',
			'large'  => 'vbo-admin-widgets-container-large',
			'full'   => 'vbo-admin-widgets-container-fullwidth',
		];

		return isset($css_size_map[$size]) ? $css_size_map[$size] : $css_size_map['full'];
	}

	/**
	 * Returns an associative array with the class names for the containers.
	 *
	 * @return 	array 	a text representation list of all sizes.
	 */
	public function getContainerClassNames()
	{
		return array(
			'full' => array(
				'name' => JText::translate('VBO_WIDGETS_CONTFULL'),
				'css' => $this->getContainerCssClass('full'),
			),
			'large' => array(
				'name' => JText::translate('VBO_WIDGETS_CONTLARGE'),
				'css' => $this->getContainerCssClass('large'),
			),
			'medium' => array(
				'name' => JText::translate('VBO_WIDGETS_CONTMEDIUM'),
				'css' => $this->getContainerCssClass('medium'),
			),
			'small' => array(
				'name' => JText::translate('VBO_WIDGETS_CONTSMALL'),
				'css' => $this->getContainerCssClass('small'),
			),
		);
	}

	/**
	 * Maps the size identifier to the corresponding name.
	 * 
	 * @param 	string 	$size 	the container size identifier.
	 *
	 * @return 	string 	the size name for the container.
	 */
	public function getContainerName($size)
	{
		$names = $this->getContainerClassNames();

		return isset($names[$size]) ? $names[$size]['name'] : $names['full']['name'];
	}

	/**
	 * Many widgets may need to know some values about the rooms.
	 * This method uses the static instance of the class to cache data.
	 * 
	 * @return 	void
	 */
	protected function loadRoomsData()
	{
		if (isset(static::$helper['all_rooms_ids'])) {
			// do not execute the same queries again
			return;
		}

		$all_rooms_ids = [];
		$all_rooms_units = [];
		$all_rooms_features = [];
		$unpublished_rooms = [];
		$tot_rooms_units = 0;

		$q = "SELECT `id`,`name`,`units`,`params`,`avail` FROM `#__vikbooking_rooms`;";
		$this->dbo->setQuery($q);
		$all_rooms = $this->dbo->loadAssocList();
		if ($all_rooms) {
			foreach ($all_rooms as $k => $r) {
				if ($r['avail'] < 1) {
					$unpublished_rooms[] = $r['id'];
				} else {
					$tot_rooms_units += $r['units'];
				}
				$all_rooms_ids[$r['id']] = $r['name'];
				$all_rooms_units[$r['id']] = $r['units'];
				$rparams = json_decode($r['params'], true);
				$all_rooms_features[$r['id']] = is_array($rparams) && array_key_exists('features', $rparams) && is_array($rparams['features']) ? $rparams['features'] : [];
			}
		}

		// update static values
		static::$helper['all_rooms_ids'] = $all_rooms_ids;
		static::$helper['all_rooms_units'] = $all_rooms_units;
		static::$helper['all_rooms_features'] = $all_rooms_features;
		static::$helper['unpublished_rooms'] = $unpublished_rooms;
		static::$helper['tot_rooms_units'] = $tot_rooms_units;
	}

	/**
	 * Many widgets could use this method to access cached information.
	 * 
	 * @param 	string 	$key 	the data key identifier.
	 * 
	 * @return 	mixed 	array/int on success, false otherwise.
	 */
	public function getRoomsData($key)
	{
		if (empty($key)) {
			return false;
		}

		if (!count(static::$helper)) {
			$this->loadRoomsData();
		}

		if (isset(static::$helper[$key])) {
			return static::$helper[$key];
		}

		return false;
	}

	/**
	 * Helper method to load all busy real records for all rooms for dates near today.
	 * This is useful to avoid double queries in the various widgets and to get data
	 * about yesterday, today and the next week.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.15.2 (J) - 1.5.5 (WP) changed the way limit timestamps are calculated.
	 */
	public function loadBusyRecordsUnclosed()
	{
		if (!isset(static::$helper['all_rooms_ids'])) {
			$this->loadRoomsData();
		}

		if (isset(static::$helper['busy_records_unclosed'])) {
			return static::$helper['busy_records_unclosed'];
		}

		// cache value and return it
		$base_from_ts = mktime(0, 0, 0, date("n"), (date("j") - 1), date("Y"));
		$base_end_ts  = mktime(23, 59, 59, date("n"), (date("j") + 7), date("Y"));
		static::$helper['busy_records_unclosed'] = VikBooking::loadBusyRecordsUnclosed(array_keys(static::$helper['all_rooms_ids']), $base_from_ts, $base_end_ts);

		return static::$helper['busy_records_unclosed'];
	}

	/**
	 * The first time the widget's customizer is open, the welcome is displayed.
	 * Congig value >= 1 means hide the welcome text, 0 or lower means show it.
	 * 
	 * @return 	bool
	 */
	public function showWelcome()
	{
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='admin_widgets_welcome';";
		$this->dbo->setQuery($q);
		$show_welcome = $this->dbo->loadResult();
		if ($show_welcome !== null) {
			return ((int)$show_welcome < 1);
		}

		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('admin_widgets_welcome', '0');";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		return true;
	}

	/**
	 * Updates the status of the welcome message for the widget's customizer.
	 * Congig value >= 1 means hide the welcome text, 0 or lower means show it.
	 * 
	 * @param 	int 	$val 	the new value to set in the configuration.
	 * 
	 * @return 	void
	 */
	public function updateWelcome($val)
	{
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='admin_widgets_welcome';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$q = "UPDATE `#__vikbooking_config` SET `setting`=" . $this->dbo->quote((int)$val) . " WHERE `param`='admin_widgets_welcome';";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			return;
		}

		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('admin_widgets_welcome', " . $this->dbo->quote((int)$val) . ");";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		return;
	}

	/**
	 * Gets the default map for multitasking pages.
	 *
	 * @return 	object 	the associative map of widgets.
	 */
	protected function getDefaultMultitaskingMap()
	{
		$map = new stdClass;

		// booking details page
		$map->editorder = [
			'reminders',
			'check_availability',
			'bookings_calendar',
		];

		// booking modifying page
		$map->editbusy = [
			'bookings_calendar',
			'check_availability',
			'reminders',
		];

		// availability overview
		$map->overv = [
			'reminders',
			'rates_flow',
			'latest_events',
		];

		return $map;
	}

	/**
	 * Gets the list of widgets assigned to the current page.
	 * 
	 * @param 	string 	$page 	the name of the current View/task.
	 * @param 	bool 	$whole 	whether to get the entire map object.
	 * 
	 * @return 	array|object 	the list of widgets for the given page,
	 * 							or entire map object if $whole is true.
	 */
	public function getMultitaskingMap($page = null, $whole = false)
	{
		// get saved map
		$saved_map = VBOFactory::getConfig()->get('multitasking_map');
		if (!empty($saved_map)) {
			// try decoding the map data
			$saved_map = json_decode($saved_map);
		}

		if (!is_object($saved_map)) {
			// revert to the default map
			$saved_map = $this->getDefaultMultitaskingMap();
		}

		if ($whole) {
			// do not proceed and return the entire map object
			return $saved_map;
		}

		if (empty($page) || !is_string($page)) {
			return [];
		}

		return isset($saved_map->$page) && is_array($saved_map->$page) ? $saved_map->$page : [];
	}

	/**
	 * Updates the map of multitasking widgets.
	 * 
	 * @param 	string 			$page 		the name of the current View/task.
	 * @param 	array|string 	$widgets 	the list of or the widget(s) to set.
	 * @param 	int 			$action 	0 = replace, -1 = prepend, 1 = append.
	 *
	 * @return 	bool 	true on success, false otherwise.
	 */
	public function updateMultitaskingMap($page, $widgets, $action = 0)
	{
		if (empty($page) || !is_string($page)) {
			return false;
		}

		// check if we have one widget or a list
		$widgets = is_string($widgets) ? [$widgets] : $widgets;
		if (!is_array($widgets)) {
			return false;
		}

		// get the whole current map
		$map = $this->getMultitaskingMap($page, true);

		if (!isset($map->$page)) {
			$map->$page = [];
		}

		if ($action > 0) {
			// append widgets
			foreach ($widgets as $widget_id) {
				if (empty($widget_id)) {
					continue;
				}
				array_push($map->$page, $widget_id);
			}
		} elseif ($action < 0) {
			// prepend widgets
			foreach ($widgets as $widget_id) {
				if (empty($widget_id)) {
					continue;
				}
				array_unshift($map->$page, $widget_id);
			}
		} else {
			// replace current list when $action === 0
			$map->$page = $widgets;
		}

		// update configuration record
		VBOFactory::getConfig()->set('multitasking_map', json_encode($map));

		return true;
	}

	/**
	 * Admin widgets used to be named as the entire file name,
	 * inclusive of the ".php" extension. For BC we make sure
	 * to evaluate the admin widget identifiers correclty.
	 * 
	 * @param 	string 	$id 	the widget identifier to simplify.
	 * 
	 * @return 	string 			the simplified widget identifier.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected function simplifyId($id)
	{
		return str_replace('.php', '', $id);
	}
}
