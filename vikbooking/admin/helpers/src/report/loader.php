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
 * Class handler for loading PMS Reports.
 * 
 * @since 	1.16.1 (J) - 1.6.1 (WP)
 */
final class VBOReportLoader
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var  VBOReportLoader
	 */
	private static $instance = null;

	/**
	 * @var  array
	 */
	private $global_reports = [];

	/**
	 * @var  array
	 */
	private $country_reports = [];

	/**
	 * @var  array
	 */
	private $countries = [];

	/**
	 * Proxy to construct the object.
	 * 
	 * @return 	self
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Class constructor will require the necessary dependencies.
	 */
	public function __construct()
	{
		// require the report abstract class
		$report_base = $this->getReportBasePath();
		require_once $report_base . 'report.php';
	}

	/**
	 * Gets the instance of a specific report class.
	 * 
	 * @param 	string 	$report 	the name of the report to load.
	 * 
	 * @return 	mixed 	false or report object instance.
	 */
	public function getDriver($report)
	{
		return VikBooking::getReportInstance($report);
	}

	/**
	 * Loads and returns the list of available PMS Report objects.
	 * 
	 * @return 	array 	list of "global" and "country" reports.
	 */
	public function getDrivers($type = '')
	{
		if ($this->global_reports || $this->country_reports) {
			// drivers loaded already
			return [$this->global_reports, $this->country_reports];
		}

		// read all driver files
		$report_files = glob($this->getReportBasePath() . '*.php');

		/**
		 * Trigger event to let other plugins register additional driver full paths.
		 *
		 * @since 	1.16.0 (J) - 1.6.0 (WP)
		 */
		$list = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadPmsReports');
		foreach ($list as $chunk) {
			// merge default driver files with the returned ones
			$report_files = array_merge($report_files, (array)$chunk);
		}

		// parse and invoke all drivers
		foreach ($report_files as $k => $report_path) {
			// driver file name
			$report_file = basename($report_path, '.php');

			if ($report_file == 'report') {
				// exclude protected abstract report class
				unset($report_files[$k]);
				continue;
			}

			// load report file
			require_once $report_path;

			// build report class name
			$classname = 'VikBookingReport' . str_replace(' ', '', ucwords(str_replace('_', ' ', $report_file)));
			if (!class_exists($classname)) {
				// invalid class name declared
				unset($report_files[$k]);
				continue;
			}

			if ($report_file == 'revenue' && $this->global_reports) {
				// make the "revenue" the first element of the list
				array_unshift($this->global_reports, new $classname);
			} elseif (strpos($report_file, 'istat') !== false || strpos($report_file, 'polizia') !== false) {
				// this is a country (italy) specific report so we push it to a separate array
				$country_key = 'IT';
				if (!isset($this->country_reports[$country_key])) {
					$this->country_reports[$country_key] = [];
				}
				$this->country_reports[$country_key][] = new $classname;
			}  elseif (substr($report_file, 2, 1) == '_') {
				// this is probably a country specific report so we push it to a separate array (two-letter country code + underscore in file name)
				$country_key = strtoupper(substr($report_file, 0, 2));
				if (!isset($this->country_reports[$country_key])) {
					$this->country_reports[$country_key] = [];
				}
				$this->country_reports[$country_key][] = new $classname;
			} else {
				// push this object as a global report
				$this->global_reports[] = new $classname;
			}
		}

		// get countries information for the reports
		$countries = $this->getInvolvedCountries();

		// check whether some country specific reports truly belong to a country
		foreach ($this->country_reports as $ckey => $cvalue) {
			if (!isset($countries[$ckey])) {
				// this country does not exist, so maybe the report file was given a short beginning name. Push it to the global reports array
				unset($countries[$ckey]);
				$this->global_reports = array_merge($this->global_reports, $cvalue);
			}
		}

		// return the list of drivers
		return [$this->global_reports, $this->country_reports];
	}

	/**
	 * Returns the associative list of the involved countries for the reports.
	 * 
	 * @return 	array
	 */
	public function getInvolvedCountries()
	{
		if ($this->countries || !$this->country_reports) {
			return $this->countries;
		}

		$dbo = JFactory::getDbo();

		// get countries information for the reports
		$country_keys = array_keys($this->country_reports);
		$country_keys = array_map(array($dbo, 'quote'), $country_keys);

		$q = $dbo->getQuery(true);

		$q->select($dbo->qn(['country_name', 'country_2_code']));
		$q->from($dbo->qn('#__vikbooking_countries'));
		$q->where($dbo->qn('country_2_code') . ' IN (' . implode(', ', $country_keys) . ')');

		$dbo->setQuery($q);
		$cdata = $dbo->loadAssocList();

		foreach ($cdata as $cd) {
			$this->countries[$cd['country_2_code']] = $cd['country_name'];
		}

		return $this->countries;
	}

	/**
	 * Returns the report base path.
	 * 
	 * @return 	string 	the base path for the reports.
	 */
	private function getReportBasePath()
	{
		return VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'report' . DIRECTORY_SEPARATOR;
	}
}
