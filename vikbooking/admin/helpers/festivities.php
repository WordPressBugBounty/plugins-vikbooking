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
 * Check whether we can extend the VCM's main Festivities class
 * or if we need to define a new middle-man class just to make VBO work.
 * The main class VikBookingFestivities is declared at the bottom of the file.
 */
$vcm_festivites = false;
if (is_file(VCM_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'festivities.php')) {
	if (!class_exists('VCMFestivities')) {
		require_once(VCM_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'festivities.php');
	}
	if (method_exists('VCMFestivities', 'getInstance')) {
		// VCM is up to date and so we can extend its main class for the festivites
		$vcm_festivites = true;
	}
}

if ($vcm_festivites === true) {
	// we can use the updated, native and main class of VCM
	class VikBookingFestivitiesBC extends VCMFestivities { }
} else {
	/**
	 * VCM missing/outdated. We need to define the old class for backward compatibility.
	 * The class of VCM has to be the MAIN one, so all festivities should be
	 * declared there and the new class VikBookingFestivities will extend it.
	 */
	class VikBookingFestivitiesBC
	{
		/**
		 * The singleton instance of the class.
		 *
		 * @var VCMFestivities
		 */
		protected static $instance = null;

		/**
		 * The (current) timestamp from which the class
		 * should calculate the closest festivities.
		 *
		 * @var int
		 */
		protected $now;

		/**
		 * Whether the class should translate the names
		 * of the festivities. False by default in this BC class.
		 *
		 * @var boolean
		 */
		public $translate = false;

		/**
		 * The list of all the supported regions for the festivities.
		 *
		 * @var array
		 */
		protected $regions = array(
			'global' 	=> 'GLOBAL',
			'ita' 		=> 'ITA',
			'fra' 		=> 'FRA',
			'ger' 		=> 'GER',
			'spa' 		=> 'SPA',
			'usa' 		=> 'USA'
		);

		/**
		 * The list of all the supported festivities.
		 *
		 * @var array
		 */
		protected $festivities = array(
			'newYearsDay' 	=> array(
				'regions' 	=> array('global'),
				'mon' 		=> 1,
				'mday' 		=> 1
			),
			'epiphany' 		=> array(
				'regions' 	=> array('global'),
				'mon' 		=> 1,
				'mday' 		=> 6
			),
			'mlkDay' 		=> array(
				'regions' 	=> array('usa'),
				'func' 		=> 'getMLKDate' //3rd monday of January
			),
			'valentinesDay'	=> array(
				'regions' 	=> array('global'),
				'mon' 		=> 2,
				'mday' 		=> 14
			),
			'presidentsDay'	=> array(
				'regions' 	=> array('usa'),
				'func' 		=> 'getPresidentsDate' //3rd monday of February
			),
			'rosenmontag' 	=> array(
				'regions' 	=> array('ger'),
				'func' 		=> 'getRosenmontagDate' //monday before Ash Wednesday (7th week before Easter)
			),
			'mardiGras' 	=> array(
				'regions' 	=> array('global'),
				'func' 		=> 'getMardigrasDate' //tuesday before Ash Wednesday (7th week before Easter)
			),
			'easter' 		=> array(
				'regions' 	=> array('global'),
				'func' 		=> 'getEasterDate'
			),
			'endofwarita'	=> array(
				'regions' 	=> array('ita'),
				'mon' 		=> 4,
				'mday' 		=> 25
			),
			'walpurgisNight'=> array(
				'regions' 	=> array('ger'),
				'mon' 		=> 4,
				'mday' 		=> 30
			),
			'dayOfWork'		=> array(
				'regions' 	=> array('ita', 'fra', 'ger'),
				'mon' 		=> 5,
				'mday' 		=> 1
			),
			'cincomayo'		=> array(
				'regions' 	=> array('usa'),
				'mon' 		=> 5,
				'mday' 		=> 5
			),
			'vedayfra'		=> array(
				'regions' 	=> array('fra'),
				'mon' 		=> 5,
				'mday' 		=> 8
			),
			'memorialDay'	=> array(
				'regions' 	=> array('usa'),
				'func' 		=> 'getMemorialDate' //last monday of May
			),
			'republicDay'	=> array(
				'regions' 	=> array('ita'),
				'mon' 		=> 6,
				'mday' 		=> 2
			),
			'4thJuly'		=> array(
				'regions' 	=> array('usa'),
				'mon' 		=> 7,
				'mday' 		=> 4
			),
			'bastilleDay'	=> array(
				'regions' 	=> array('fra'),
				'mon' 		=> 7,
				'mday' 		=> 14
			),
			'ferragosto'	=> array(
				'regions' 	=> array('ita'),
				'mon' 		=> 8,
				'mday' 		=> 15
			),
			'laborDay'	=> array(
				'regions' 	=> array('usa'),
				'func' 		=> 'getLaborDate' //1st Monday of September
			),
			'columbusDay'	=> array(
				'regions' 	=> array('usa'),
				'func' 		=> 'getColumbusDate' //2nd Monday of October
			),
			'hispanityDay'	=> array(
				'regions' 	=> array('spa'),
				'mon' 		=> 10,
				'mday' 		=> 12,
			),
			'halloween'	=> array(
				'regions' 	=> array('global'),
				'mon' 		=> 10,
				'mday' 		=> 31,
				'bridge' 	=> array('saintsDay', 'soulsDay')
			),
			'saintsDay'	=> array(
				'regions' 	=> array('ita'),
				'mon' 		=> 11,
				'mday' 		=> 1,
				'bridge' 	=> array('soulsDay')
			),
			'soulsDay'	=> array(
				'regions' 	=> array('ita'),
				'mon' 		=> 11,
				'mday' 		=> 2
			),
			'wallOfBerlin'	=> array(
				'regions' 	=> array('ger'),
				'mon' 		=> 11,
				'mday' 		=> 9
			),
			'armisticeDay'	=> array(
				'regions' 	=> array('fra'),
				'mon' 		=> 11,
				'mday' 		=> 11
			),
			'veteransDay'	=> array(
				'regions' 	=> array('usa'),
				'mon' 		=> 11,
				'mday' 		=> 11
			),
			'thanksgiving'	=> array(
				'regions' 	=> array('usa'),
				'func' 		=> 'getThanksgivingDate' //4th Thursday of November
			),
			'immacolata'	=> array(
				'regions' 	=> array('ita', 'spa'),
				'mon' 		=> 12,
				'mday' 		=> 8
			),
			'christmasEve'	=> array(
				'regions' 	=> array('global'),
				'mon' 		=> 12,
				'mday' 		=> 24,
				'bridge' 	=> array('christmasDay', 'stStephensDay', 'newYearsEve', 'newYearsDay')
			),
			'christmasDay'	=> array(
				'regions' 	=> array('global'),
				'mon' 		=> 12,
				'mday' 		=> 25,
				'bridge' 	=> array('stStephensDay', 'newYearsEve', 'newYearsDay')
			),
			'stStephensDay' => array(
				'regions' 	=> array('global'),
				'mon' 		=> 12,
				'mday' 		=> 26,
				'bridge' 	=> array('newYearsEve', 'newYearsDay')
			),
			'newYearsEve'	=> array(
				'regions' 	=> array('global'),
				'mon' 		=> 12,
				'mday' 		=> 31,
				'bridge' 	=> array('newYearsDay')
			),
		);

		/**
		 * Class constructor is still public, even though
		 * it is possible to access the Singletone instance
		 * of the class through the method getInstance().
		 *
		 * @see 	getInstance()
		 * @since 	VCM 1.6.13
		 */
		public function __construct()
		{
			$this->now = time();
		}

		/**
		 * Returns the global class object, either
		 * a new instance or the existing instance
		 * if the class was already instantiated.
		 * This method was introduced in the v 1.6.13 for
		 * VBO to check whether the class can be extended.
		 * It used to have private vars and methods that
		 * have now been changed to protected.
		 *
		 * @return 	self 	A new instance of the class.
		 *
		 * @since 	VCM 1.6.13
		 */
		public static function getInstance()
		{
			if (is_null(static::$instance)) {
				static::$instance = new static();
			}

			return static::$instance;
		}

		/**
		 * Method to set the current timestamp from which
		 * the class should find the closest festivities.
		 *
		 * @param 	int  		$from_ts
		 *
		 * @return 	self
		 */
		public function setFromTimestamp($from_ts)
		{
			if (!empty($from_ts)) {
				$this->now = $from_ts;
			}

			return $this;
		}

		/**
		 * Returns all the supported regions with
		 * the corresponding translated names.
		 *
		 * @return 	array
		 */
		public function getTranslatedRegions()
		{
			$trans_regions = $this->regions;
			foreach ($trans_regions as $k => $v) {
				if ($this->translate) {
					$say_name = JText::translate('VCMFEST'.strtoupper($v));
					// make sure the lang definition is available
					$say_name = substr($say_name, 0, 7) == 'VCMFEST' ? $k : $say_name;
				} else {
					$say_name = $k;
				}
				$trans_regions[$k] = $say_name;
			}

			return $trans_regions;
		}

		/**
		 * Main method to get a list of the closest festivities
		 * for the given region, starting from the current timestamp.
		 *
		 * @param 	string  		[$region]
		 *
		 * @return 	array
		 */
		public function loadFestivities($region = 'global')
		{
			return $this->calculateFestivititesDates(
				$this->getFestivitiesByRegion(strtolower($region))
			);
		}

		/**
		 * Method that filters the festivities by the
		 * requested region or the global ones.
		 *
		 * @param 	string  		$region
		 *
		 * @return 	array
		 */
		protected function getFestivitiesByRegion($region)
		{
			$fests = array();
			foreach ($this->festivities as $k => $v) {
				if ($v['regions'][0] == 'global' || in_array($region, $v['regions'])) {
					$fests[$k] = $v;
				}
			}

			return $fests;
		}

		/**
		 * Method that parses all the region-filtered festivities to
		 * calculate the next timestamp for each fest and the end date,
		 * by setting the properties 'from_ts' and 'to_ts' for each fest.
		 * Ex. If a holiday is on Monday, the method sets the dates to Sat-Mon.
		 * Ex. If a holiday is on Thursday, the method sets the dates to Thu-Sat.
		 * Ex. If a holiday is on Friday, the method sets the dates to Fri-Sat.
		 * The method returns a sorted and filtered array of key-value pairs with
		 * some new properties: 'next_ts', 'from_ts', 'to_ts', 'wday', 'trans_name'.
		 *
		 * @param 	array  		$regions_festivities (global festivities + region's festivities)
		 *
		 * @return 	array
		 */
		protected function calculateFestivititesDates($regions_festivities)
		{
			$fests = array();
			$info_from = getdate($this->now);

			//calculate the next timestamp from today of each festivity ('from_ts')
			foreach ($regions_festivities as $k => $v) {
				if (isset($v['func'])) {
					//custom function
					if (!method_exists($this, $v['func']) || !is_callable(array($this, $v['func']))) {
						continue;
					}
					$next_ts = $this->{$v['func']}();
				} else {
					//fixed month and month-day
					$next_ts = mktime(0, 0, 0, $v['mon'], $v['mday'], $info_from['year']);
					if ($next_ts < $this->now) {
						$next_ts = mktime(0, 0, 0, $v['mon'], $v['mday'], ($info_from['year'] + 1));
					}
				}
				if (empty($next_ts)) {
					continue;
				}
				$info_next = getdate($next_ts);
				$v['wday'] = $info_next['wday'];
				$v['from_ts'] = $v['next_ts'] = $next_ts;
				$regions_festivities[$k] = $v;
				$fests[$k] = $v;
			}
			//

			//calculate the end date for each festivity by considering weekends and bridges ('to_ts', and 'from_ts', if necessary)
			foreach ($fests as $k => $v) {
				$info_date = getdate($v['from_ts']);
				if ((int)$info_date['wday'] < 2) {
					//Festivity is on a Sunday or a Monday, switch 'from_ts' to the Saturday before
					$fests[$k]['from_ts'] = mktime(0, 0, 0, $info_date['mon'], ($info_date['mday'] - ((int)$info_date['wday'] + 1)), $info_date['year']);
				}
				if (isset($v['bridge']) && count($v['bridge'])) {
					//Check next bridges of this festivity
					$bridges_ts = array();
					foreach ($v['bridge'] as $fest_key) {
						if (isset($fests[$fest_key]) && isset($fests[$fest_key]['from_ts'])) {
							$bridges_ts[] = $regions_festivities[$fest_key]['from_ts'];
						}
					}
					if (count($bridges_ts)) {
						//bridges found: set the 'to_ts' to the last festivity of the bridge and continue
						$info_max = getdate(max($bridges_ts));
						$fests[$k]['to_ts'] = mktime(23, 59, 59, $info_max['mon'], $info_max['mday'], $info_max['year']);
						continue;
					}
				}
				if ((int)$info_date['wday'] == 4) {
					//Festivity is on a Thursday, set 'to_ts' to the Saturday after
					$fests[$k]['to_ts'] = mktime(0, 0, 0, $info_date['mon'], ($info_date['mday'] + 2), $info_date['year']);
					continue;
				}
				if ((int)$info_date['wday'] == 5) {
					//Festivity is on a Friday, set 'to_ts' to the Saturday after
					$fests[$k]['to_ts'] = mktime(0, 0, 0, $info_date['mon'], ($info_date['mday'] + 1), $info_date['year']);
					continue;
				}
				//no bridges, set 'to_ts' to the end of the same day at 23:59:59
				$fests[$k]['to_ts'] = mktime(23, 59, 59, $info_date['mon'], $info_date['mday'], $info_date['year']);
			}
			//

			// sorting and translation
			$sort_map = array();
			foreach ($fests as $k => $v) {
				$sort_map[$k] = $v['next_ts'];
				if ($this->translate) {
					$say_name = JText::translate('VCMFEST'.strtoupper($k));
					// make sure the lang definition is available
					$say_name = substr($say_name, 0, 7) == 'VCMFEST' ? $k : $say_name;
				} else {
					$say_name = $k;
				}
				$fests[$k]['trans_name'] = $say_name;
			}
			asort($sort_map);
			$sorted_fests = array();
			foreach ($sort_map as $k => $v) {
				$sorted_fests[$k] = $fests[$k];
			}
			$fests = $sorted_fests;

			return $fests;
		}

		/**
		 * Custom method that returns the next Easter ts.
		 * We use easter_days() to get the number of days where Easter
		 * falls after March 21st of the given year.
		 *
		 * @return 	int
		 */
		public function getEasterDate()
		{
			$next_ts = mktime(0, 0, 0, 3, (21 + easter_days(date('Y', $this->now))), date('Y', $this->now));
			if ($next_ts < $this->now) {
				$next_ts = mktime(0, 0, 0, 3, (21 + easter_days(((int)date('Y', $this->now) + 1))), ((int)date('Y', $this->now) + 1));
			}

			return $next_ts;
		}

		/**
		 * Custom method that returns the Rosenmontag ts.
		 * (Monday before the Ash Wednesday, 7th week before Easter)
		 *
		 * @return 	int
		 */
		public function getRosenmontagDate()
		{
			$eightw_easter = getdate(strtotime('-7 weeks', mktime(0, 0, 0, 3, (21 + easter_days(date('Y', $this->now))), date('Y', $this->now))));
			$next_ts = mktime(0, 0, 0, $eightw_easter['mon'], ($eightw_easter['mday'] + 1), $eightw_easter['year']);
			if ($next_ts < $this->now) {
				$eightw_easter = getdate(strtotime('-7 weeks', mktime(0, 0, 0, 3, (21 + easter_days(((int)date('Y', $this->now) + 1))), ((int)date('Y', $this->now) + 1))));
				$next_ts = mktime(0, 0, 0, $eightw_easter['mon'], ($eightw_easter['mday'] + 1), $eightw_easter['year']);
			}

			return $next_ts;
		}

		/**
		 * Custom method that returns the Mardi Gras ts.
		 * (Tuesday before the Ash Wednesday, 7th week before Easter)
		 *
		 * @return 	int
		 */
		public function getMardigrasDate()
		{
			$eightw_easter = getdate(strtotime('-7 weeks', mktime(0, 0, 0, 3, (21 + easter_days(date('Y', $this->now))), date('Y', $this->now))));
			$next_ts = mktime(0, 0, 0, $eightw_easter['mon'], ($eightw_easter['mday'] + 2), $eightw_easter['year']);
			if ($next_ts < $this->now) {
				$eightw_easter = getdate(strtotime('-7 weeks', mktime(0, 0, 0, 3, (21 + easter_days(((int)date('Y', $this->now) + 1))), ((int)date('Y', $this->now) + 1))));
				$next_ts = mktime(0, 0, 0, $eightw_easter['mon'], ($eightw_easter['mday'] + 2), $eightw_easter['year']);
			}

			return $next_ts;
		}

		/**
		 * Custom method that returns the next ts for the
		 * Martin Luther King's Day (3rd Monday of January).
		 *
		 * @return 	int
		 */
		public function getMLKDate()
		{
			$next_ts = strtotime('third Monday of January '.date('Y', $this->now));
			if (!empty($next_ts) && $next_ts < $this->now) {
				$next_ts = strtotime('third Monday of January '.((int)date('Y', $this->now) + 1));
			}

			return $next_ts;
		}

		/**
		 * Custom method that returns the next ts for the
		 * President's Day (3rd Monday of February).
		 *
		 * @return 	int
		 */
		public function getPresidentsDate()
		{
			$next_ts = strtotime('third Monday of February '.date('Y', $this->now));
			if (!empty($next_ts) && $next_ts < $this->now) {
				$next_ts = strtotime('third Monday of February '.((int)date('Y', $this->now) + 1));
			}

			return $next_ts;
		}

		/**
		 * Custom method that returns the next ts for the
		 * Memorial's Day (last Monday of May).
		 *
		 * @return 	int
		 */
		public function getMemorialDate()
		{
			$next_ts = strtotime('last Monday of May '.date('Y', $this->now));
			if (!empty($next_ts) && $next_ts < $this->now) {
				$next_ts = strtotime('last Monday of May '.((int)date('Y', $this->now) + 1));
			}

			return $next_ts;
		}

		/**
		 * Custom method that returns the next ts for the
		 * Labor's Day (first Monday of September).
		 *
		 * @return 	int
		 */
		public function getLaborDate()
		{
			$next_ts = strtotime('first Monday of September '.date('Y', $this->now));
			if (!empty($next_ts) && $next_ts < $this->now) {
				$next_ts = strtotime('first Monday of September '.((int)date('Y', $this->now) + 1));
			}

			return $next_ts;
		}

		/**
		 * Custom method that returns the next ts for the
		 * Columbus's Day (second Monday of October).
		 *
		 * @return 	int
		 */
		public function getColumbusDate()
		{
			$next_ts = strtotime('second Monday of October '.date('Y', $this->now));
			if (!empty($next_ts) && $next_ts < $this->now) {
				$next_ts = strtotime('second Monday of October '.((int)date('Y', $this->now) + 1));
			}

			return $next_ts;
		}

		/**
		 * Custom method that returns the next ts for the
		 * Thanksgiving Day (4th Thursday of November).
		 *
		 * @return 	int
		 */
		public function getThanksgivingDate()
		{
			$next_ts = strtotime('fourth Thursday of November '.date('Y', $this->now));
			if (!empty($next_ts) && $next_ts < $this->now) {
				$next_ts = strtotime('fourth Thursday of November '.((int)date('Y', $this->now) + 1));
			}

			return $next_ts;
		}
	}
}

/**
 * Festivities handler class for Vik Booking.
 *
 * @since 	1.12.0
 */
class VikBookingFestivities extends VikBookingFestivitiesBC
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikBookingTracker
	 */
	protected static $instance = null;

	/**
	 * The guessed region for the festivities.
	 *
	 * @var string
	 */
	protected $guessed_region = 'global';

	/**
	 * Regions adapter values to find the proper region key in festivities.
	 *
	 * @var array
	 */
	protected $region_adapters = array(
		'de' => 'ger',
		'es' => 'spa',
		'en' => 'usa',
	);

	/**
	 * Class constructor is public for bc.
	 *
	 * @see 	getInstance()
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		// load the language file of VCM
		$this->loadLanguage();

		// guess the region to use for the festivities
		$this->guessRegion();
	}

	/**
	 * Returns the global Tracker object, either
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
	 * Checks whether the next festivities should be verified
	 * depending on the last time they were checked.
	 * By default we check new festivities every month.
	 * 
	 * @param 	string 		$current_check 	the current checking identifier.
	 * 
	 * @return 	boolean 	True if it's time to check, false otherwise.
	 */
	public function shouldCheckFestivities($current_check = '')
	{
		if (empty($current_check)) {
			$current_check = date('Y-m');
		}
		$dbo = JFactory::getDbo();
		$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='fests_last_check';";
		$dbo->setQuery($q);
		$last_check = $dbo->loadResult();
		if ($last_check) {
			$should_check = ($last_check != $current_check);
			if ($should_check) {
				// update last time we checked
				$q = "UPDATE `#__vikbooking_config` SET `setting`=".$dbo->quote($current_check)." WHERE `param`='fests_last_check';";
				$dbo->setQuery($q);
				$dbo->execute();
			}
			return $should_check;
		}

		// first time we access this method
		$q = "INSERT INTO `#__vikbooking_config` (`param`,`setting`) VALUES ('fests_last_check', ".$dbo->quote($current_check).");";
		$dbo->setQuery($q);
		$dbo->execute();
		return true;
	}

	/**
	 * Main method to find the next festivities for the calculated/given region
	 * and to store them onto the database, only if they do not exist already.
	 * 
	 * @return 	int 	the total number of festivities stored
	 * 
	 * @see 	shouldCheckFestivities() should be called before this method to not waste resources.
	 */
	public function storeNextFestivities()
	{
		$tot_added = 0;
		$all_fests = $this->getNextFestivities();

		// stores the festivities found if they do not exist already
		foreach ($all_fests as $k => $v) {
			$fest_ymd = date('Y-m-d', $v['next_ts']);
			if (!$this->festivityExists($fest_ymd, $k) && $this->storeFestivity($fest_ymd, $v, $k)) {
				$tot_added++;
			}
		}

		return $tot_added;
	}

	/**
	 * Gets all the next festivities for the current region.
	 * 
	 * @return 	array
	 */
	public function getNextFestivities()
	{
		$all_fests = $this->loadFestivities($this->guessed_region);

		// add up some useful properties for other methods, not really for storing
		foreach ($all_fests as $k => $v) {
			$all_fests[$k]['next_day'] = date('Y-m-d', $v['next_ts']);
			$all_fests[$k]['from_day'] = date('Y-m-d', $v['from_ts']);
			$all_fests[$k]['to_day'] = date('Y-m-d', $v['to_ts']);
			$all_fests[$k]['all_timestamps'] = $this->getFestsDatesArray($v['from_ts'], $v['to_ts']);
		}

		return $all_fests;
	}

	/**
	 * Checks whether a precise festivity exists on the given date.
	 * 
	 * @param 	string 		$ymd 	the date string in Y-m-d format.
	 * @param 	string 		$key 	the key name of the fest (custom/VCM).
	 * @param 	boolean 	$get 	whether to get the found record.
	 *
	 * @return 	mixed 		True/array if festivity exists, false otherwise.
	 */
	public function festivityExists($ymd, $key = '', $get = false)
	{
		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_fests_dates` WHERE `dt`=".$dbo->quote($ymd);
		$dbo->setQuery($q);
		$festivity = $dbo->loadAssoc();
		if (!$festivity) {
			return false;
		}
		
		$fests_info = json_decode($festivity['festinfo']);
		if (!$fests_info) {
			return false;
		}
		
		// update this information in the array in case it needs to be returned
		$festivity['festinfo'] = $fests_info;

		// seek for the requested fest
		foreach ($fests_info as $fest) {
			if (!empty($fest->type) && $fest->type == $key) {
				return $get ? $festivity : true;
			}
		}
		return false;
	}

	/**
	 * Stores/updates a festivity for the given date. If a record for the given date exists, then the
	 * festivity is added to or updated in the current record. Otherwise a new record is created.
	 * 
	 * @param 	string 		$ymd 	the date string in Y-m-d format.
	 * @param 	array 		$fest 	the array information of the festivity to be JSON-encoded.
	 * @param 	string 		$type 	the key name of the festivity ('custom' for manual entries).
	 * @param 	string 		$descr 	the description of the festivity (some notes).
	 *
	 * @return 	boolean 	True if the new record is stored or updated, false otherwise.
	 */
	public function storeFestivity($ymd, $fest, $type = 'custom', $descr = '')
	{
		// build "hot dates" next to the main festivity if set, and if description is empty
		$descr = empty($descr) && isset($fest['all_timestamps']) && count($fest['all_timestamps']) > 1 ? $this->parseBridgeTimestamps($fest['all_timestamps']) : $descr;
		
		// build festivity mandatory properties ('trans_name' is another mandatory prop which must be already set)
		$fest['type'] = $type;
		$fest['descr'] = $descr;

		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_fests_dates` WHERE `dt`=".$dbo->quote($ymd);
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// create a new record
			$q = "INSERT INTO `#__vikbooking_fests_dates` (`dt`, `festinfo`) VALUES (".$dbo->quote($ymd).", ".$dbo->quote(json_encode(array($fest))).");";
			$dbo->setQuery($q);
			$dbo->execute();

			return ((int)$dbo->insertid() > 0);
		}
		// update current record by pushing the festivity into the existing festinfo array of objects
		$festivity  = $dbo->loadAssoc();
		$fests_arr  = json_decode($festivity['festinfo']);
		array_push($fests_arr, (object)$fest);
		$q = "UPDATE `#__vikbooking_fests_dates` SET `festinfo`=".$dbo->quote(json_encode($fests_arr))." WHERE `id`=".$festivity['id'];
		$dbo->setQuery($q);
		$dbo->execute();

		return ((int)$dbo->getAffectedRows() > 0);
	}

	/**
	 * Deletes a specific festivity for the given date.
	 * If the day will not contain anymore fests, then
	 * the whole record will be deleted.
	 * 
	 * @param 	string 		$ymd 	the date string in Y-m-d format.
	 * @param 	int 		$index 	the array index of the fest.
	 * @param 	string 		$type 	the key name of the festivity ('custom' for manual entries).
	 *
	 * @return 	boolean 	True if the fest is removed, false otherwise.
	 */
	public function deleteFestivity($ymd, $index, $type = 'custom')
	{
		$record = $this->festivityExists($ymd, $type, true);
		if (!$record) {
			return false;
		}

		$drop_record = false;
		if (isset($record['festinfo'][$index])) {
			// fest was found by array-index
			array_splice($record['festinfo'], $index, 1);
			if (!count($record['festinfo'])) {
				// this day has got no more fests
				$drop_record = true;
			}
		} else {
			// seek for the requested fest by type
			foreach ($record['festinfo'] as $k => $fest) {
				if (!empty($fest->type) && $fest->type == $type) {
					// fest found
					array_splice($record['festinfo'], $k, 1);
					if (!count($record['festinfo'])) {
						// this day has got no more fests
						$drop_record = true;
					}
					break;
				}
			}
		}
		
		$dbo = JFactory::getDbo();
		if ($drop_record) {
			$q = "DELETE FROM `#__vikbooking_fests_dates` WHERE `dt`=".$dbo->quote($ymd);
		} else {
			$q = "UPDATE `#__vikbooking_fests_dates` SET `festinfo`=".$dbo->quote(json_encode($record['festinfo']))." WHERE `dt`=".$dbo->quote($ymd);
		}
		$dbo->setQuery($q);
		$dbo->execute();

		return true;
	}

	/**
	 * Loads all the fests from Vik Booking, either the custom and the global ones.
	 * Returns an associative array indexed by date with decoded fests information.
	 * 
	 * @param 	string 		$from_ymd 	the optional minimum date in Y-m-d (defaults to today).
	 * @param 	string 		$to_ymd 	the optional maximum date in Y-m-d (defaults to none).
	 *
	 * @return 	array 		the list of festivities found in Vik Booking.
	 */
	public function loadFestDates($from_ymd = null, $to_ymd = null)
	{
		$fests = array();
		if (empty($from_ymd)) {
			$from_ymd = date('Y-m-d');
		}

		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_fests_dates` WHERE `dt`>=".$dbo->quote($from_ymd).(!empty($to_ymd) ? " AND `dt`<=".$dbo->quote($to_ymd) : '')." ORDER BY `dt` ASC;";
		$dbo->setQuery($q);
		$all_fests = $dbo->loadAssocList();
		if ($all_fests) {
			// make sure to decode all festivity infos
			foreach ($all_fests as $k => $v) {
				$v['festinfo'] = json_decode($v['festinfo']);
				// make sure the TS properties are set for custom fests
				foreach ($v['festinfo'] as $fk => $fv) {
					if (!isset($fv->next_ts)) {
						$start_ts = strtotime($v['dt']);
						$start_info = getdate($start_ts);
						$v['festinfo'][$fk]->next_ts = $start_ts;
						$v['festinfo'][$fk]->from_ts = $start_ts;
						$v['festinfo'][$fk]->to_ts = mktime(23, 59, 59, $start_info['mon'], $start_info['mday'], $start_info['year']);
					}
				}
				//
				$fests[$v['dt']] = $v;
			}
		}

		return $fests;
	}

	/**
	 * Sets the proper region for the festivities to look for.
	 * If the given region identifier does not exist, the 'global' region is used.
	 * By instantiating the class object, the constructor will guess automatically
	 * the region and will call this method.
	 * 
	 * @param 	string 		$identifier 	the region identifier (array key).
	 *
	 * @return 	boolean 	True if region exists, false otherwise by using the global region.
	 */
	public function setRegion($identifier)
	{
		if (isset($this->regions[$identifier])) {
			// region requested exists
			$this->guessed_region = $identifier;
			return true;
		}

		// use default region
		$this->guessed_region = 'global';
		return false;
	}

	/**
	 * Attempts to find the proper region for the festivities
	 * from a given identifier, or from the language tag if empty.
	 * If the guessed region does not exist, then we revert to 'global'.
	 * 
	 * @param 	string 		$identifier 	the region identifier (array key).
	 *
	 * @return 	boolean 	True if region exists, false otherwise.
	 * 
	 * @uses 	setRegion()
	 */
	protected function guessRegion($identifier = '')
	{
		if (empty($identifier)) {
			$identifier = substr(JFactory::getLanguage()->getTag(), 0, 2);
		}

		// make sure the identifier is in lower case
		$identifier = strtolower($identifier);

		// check if identifier is an adapter value for a region
		if (isset($this->region_adapters[$identifier])) {
			// adapter value was found, so this is more likely a valid identifier
			$identifier = $this->region_adapters[$identifier];
		}

		// parse all festivities to find the proper matching region identifier if not yet found
		if (!isset($this->regions[$identifier])) {
			$regions = array_keys($this->regions);
			foreach ($regions as $region) {
				$haystack = strlen($identifier) > strlen($region) ? $identifier : $region;
				$needle = strlen($identifier) > strlen($region) ? $region : $identifier;
				if (strpos($haystack, $needle) !== false) {
					// we were lucky enough to find a valid region, so just update the identifier
					$identifier = $region;
					break;
				}
			}
		}

		return $this->setRegion($identifier);
	}

	/**
	 * Calculates the range of dates affecting the festivity start and end times.
	 * 
	 * @param 	int 	$from_ts 	festivity start timestamp
	 * @param 	int 	$to_ts 		festivity end timestamp
	 * 
	 * @return 	array 	the range of dates (timestamps) touching the festivity
	 */
	protected function getFestsDatesArray($from_ts, $to_ts)
	{
		$dates_ts = array();
		if (empty($from_ts) || empty($to_ts) || $to_ts < $from_ts) {
			return $dates_ts;
		}

		$from_info = getdate($from_ts);
		while ($from_info[0] <= $to_ts) {
			array_push($dates_ts, $from_info[0]);
			// next day
			$from_info = getdate(mktime(0, 0, 0, $from_info['mon'], ($from_info['mday'] + 1), $from_info['year']));
		}

		return $dates_ts;
	}

	/**
	 * Converts a list of timestamps into a list of readable dates.
	 * 
	 * @param 	array 	$all_ts 	list of timestamps affecting the fest.
	 * 
	 * @return 	string 	the list (c.s.v.) of dates touching this festivity or an empty string.
	 */
	protected function parseBridgeTimestamps($all_ts)
	{
		$hot_dates = array();
		if (empty($all_ts) || !is_array($all_ts)) {
			return '';
		}

		$nowdf = VikBooking::getDateFormat();
		if ($nowdf == "%d/%m/%Y") {
			$df = 'd/m/Y';
		} elseif ($nowdf == "%m/%d/%Y") {
			$df = 'm/d/Y';
		} else {
			$df = 'Y/m/d';
		}
		$datesep = VikBooking::getDateSeparator();

		foreach ($all_ts as $ts) {
			array_push($hot_dates, date(str_replace("/", $datesep, $df), $ts));
		}

		return implode(', ', $hot_dates);
	}

	/**
	 * Loads the language file from VCM, necessary to translate most festivities.
	 * This method does not extend anything from the VCM's parent class. It's a
	 * method only of VikBookingFestivities. Compatible with both Joomla and WordPress.
	 *
	 * @return 	void
	 */
	protected function loadLanguage()
	{
		// load the VCM admin language file
		$vcm_admin_lang_path = '';
		if (VBOPlatformDetection::isJoomla()) {
			$vcm_admin_lang_path = JPATH_ADMINISTRATOR;
		} elseif (defined('VIKCHANNELMANAGER_ADMIN_LANG')) {
			$vcm_admin_lang_path = VIKCHANNELMANAGER_ADMIN_LANG;
		} elseif (VBOPlatformDetection::isWordPress()) {
			/**
			 * If running within Vik Booking, the constant VIKCHANNELMANAGER_ADMIN_LANG may not be available
			 * 
			 * @since 	1.4.3 (WP) - 1.14.2 (J)
			 */
			$vcm_admin_lang_path = str_replace('vikbooking', 'vikchannelmanager', VIKBOOKING_ADMIN_LANG);
		}
		if (empty($vcm_admin_lang_path)) {
			return;
		}
		
		$lang = JFactory::getLanguage();
		$lang->load('com_vikchannelmanager', $vcm_admin_lang_path);
		if (VBOPlatformDetection::isWordPress() && defined('VIKCHANNELMANAGER_LIBRARIES')) {
			/**
			 * @wponly  load language admin handler as well for WP.
			 * 			We do this only because of WordPress, but in a way also compatible with Joomla as
			 * 			the constant VIKCHANNELMANAGER_LIBRARIES and method attachHandler are not in Joomla.
			 */
			$lang->attachHandler(VIKCHANNELMANAGER_LIBRARIES . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'admin.php', 'vikchannelmanager');
		}
	}
}
