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
 * PMS Report abstract-parent class that all drivers should extend.
 */
#[\AllowDynamicProperties]
abstract class VikBookingReport
{
	/**
	 * @var 	string
	 */
	protected $reportName = '';

	/**
	 * @var 	string
	 */
	protected $reportFile = '';

	/**
	 * @var 	array
	 */
	protected $reportFilters = [];

	/**
	 * @var 	string
	 */
	protected $reportScript = '';

	/**
	 * @var 	string
	 */
	protected $warning = '';

	/**
	 * @var 	string
	 */
	protected $error = '';

	/**
	 * @var 	object
	 */
	protected $dbo;

	/**
	 * @var 	array
	 */
	protected $cols = [];

	/**
	 * @var 	array
	 */
	protected $rows = [];

	/**
	 * @var 	array
	 */
	protected $footerRow = [];

	/**
	 * An array of custom options to be passed to the report.
	 * Reports can use them before generating the report data.
	 * 
	 * @var 	array
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected $options = [];

	/**
	 * @var 	resource
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	protected $fp_export = null;

	/**
	 * @var 	string
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	protected $csv_export_format = 'csv';

	/**
	 * @var 	string
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	protected $csv_export_fname = '';

	/**
	 * Class constructor should define the name of
	 * the report and the filters to be displayed.
	 */
	public function __construct()
	{
		$this->dbo = JFactory::getDbo();
	}

	/**
	 * Extending Classes should define this method
	 * to get the name of the report.
	 */
	abstract public function getName();

	/**
	 * Extending Classes should define this method
	 * to get the name of class file.
	 */
	abstract public function getFileName();

	/**
	 * Extending Classes should define this method
	 * to get the filters of the report.
	 */
	abstract public function getFilters();

	/**
	 * Extending Classes should define this method
	 * to generate the report data (cols and rows).
	 */
	abstract public function getReportData();

	/**
	 * Main method to generate the report columns and rows. Stores on the current
	 * resource the CSV lines and forces the browser to download the file. In case
	 * of errors, the process is not terminated to let the View display the errors.
	 * 
	 * @return 	void|bool 	script termination on success, false otherwise.
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP) drivers no longer need to implement it,
	 * 									unless the driver needs to override it.
	 */
	public function exportCSV()
	{
		// grab all report lines to export
		$csvlines = $this->getExportCSVLines();

		if (!$csvlines) {
			// no data to export
			return false;
		}

		// force the download of the CSV file
		$this->outputHeaders();

		/**
		 * Trigger event to allow third-party plugins to set additional headers or contents to output.
		 * 
		 * @since 	1.16.8 (J) - 1.6.8 (WP)
		 */
		VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeExportOutputCSV', [$this, $this->csv_export_format]);

		// send lines to output
		$this->outputCSV($csvlines);

		exit;
	}

	/**
	 * Returns the name to give to the CSV (or custom) file being exported.
	 * 
	 * @param 	bool 	$cut_suffix 	if true the file name suffix will be cut off.
	 * @param 	string 	$suffix 		the optional file name suffix, hence the extension.
	 * @param 	bool 	$pretty 		if true, dashes will be converted to spaces and more.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public function getExportCSVFileName($cut_suffix = false, $suffix = '.csv', $pretty = false)
	{
		if ($this->csv_export_fname) {
			// use the exact report file name set
			$export_fname = $this->csv_export_fname;
		} else {
			// use a generic file name
			$export_fname = date('Y-m-d_H.i.s') . '-' . $this->reportFile . '.csv';
		}

		if ($cut_suffix) {
			// cut off the file name suffix
			if (!$suffix) {
				// detect file extension
				$suffix = substr($export_fname, strrpos($export_fname, '.'));
			}
			$export_fname = basename($export_fname, $suffix);
		}

		if ($pretty) {
			// get the default date separator char
			$datesep = VikBooking::getDateSeparator();
			// add a dash between a range of dates
			$export_fname = preg_replace("/([0-9])-([0-9])/i", '$1 - $2', $export_fname);
			// add an empty space between report name and channel name
			$export_fname = preg_replace("/([A-Z])-([A-Z])/i", '$1 $2', $export_fname);
			// add an empty space between report name and date
			$export_fname = preg_replace("/([A-Z0-9])-([0-9])/i", '$1 $2', $export_fname);
			// convert the dashes to the actual date separator char
			$export_fname = preg_replace("/([0-9])_([0-9])/i", '$1' . $datesep . '$2', $export_fname);
		}

		return $export_fname;
	}

	/**
	 * Sets the name to give to the CSV (or custom) file being exported.
	 * 
	 * @param 	string 	$fname 	the full name of the file to export.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public function setExportCSVFileName($fname)
	{
		$this->csv_export_fname = (string)$fname;

		return $this;
	}

	/**
	 * Builds the list of the CSV lines to be exported from the current report data.
	 * 
	 * @param 	bool 	$no_data 	true for actually not letting the report run.
	 * 
	 * @return 	array 				list of CSV lines containing lists of CSV fields.
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public function getExportCSVLines($no_data = false)
	{
		if (!$no_data && !$this->getReportData()) {
			// nothing to export
			return [];
		}

		// list of CSV lines
		$csvlines = [];

		if (!strcasecmp($this->csv_export_format, 'excel')) {
			// add instructions for Excel

			// UTF-8 BOM (not needed because we convert the encoding to UTF-16LE, which has BOM)
			// $csvlines[] = chr(0xEF) . chr(0xBB) . chr(0xBF);

			// define the separator
			$csvlines[] = "sep=;\n";
		}

		// push the head of the CSV file
		$csvcols = [];
		foreach ($this->cols as $col) {
			if (!is_array($col) || isset($col['ignore_export'])) {
				// skip column
				continue;
			}

			// push column label
			$csvcols[] = $this->encodeExportCSVField($col['label']);
		}

		// push all columns
		$csvlines[] = $csvcols;

		// push the rows + footer row of the CSV file
		foreach (array_merge($this->rows, $this->footerRow) as $row) {
			$csvrow = [];
			foreach ($row as $field) {
				if (!is_array($field) || isset($field['ignore_export'])) {
					// skip value
					continue;
				}

				// build value for export
				$export_value = $field['value'];
				if (!isset($field['no_export_callback']) && !isset($field['no_csv_callback'])) {
					if (isset($field['export_callback']) && is_callable($field['export_callback'])) {
						// trigger closure callback to prepare the value for export
						$export_value = $field['export_callback']($field['value']);
					} elseif (isset($field['callback']) && is_callable($field['callback'])) {
						// trigger closure callback to prepare the value for export
						$export_value = $field['callback']($field['value']);
					}
				}

				// ensure no HTML is present
				$export_value = strip_tags($export_value);

				// apply encoding and transliteration, if needed
				$enc_trans_value = $this->encodeExportCSVField($export_value);

				// make sure transliteration or encoding did not break the string
				if (empty($enc_trans_value) && !empty($export_value)) {
					// fallback to original and raw value
					$enc_trans_value = $export_value;
				}

				// push row value
				$csvrow[] = $enc_trans_value;
			}

			// push the whole row
			$csvlines[] = $csvrow;
		}

		// return the list of lines
		return $csvlines;
	}

	/**
	 * Checks if a custom resource file pointer to export the file has been set.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public function hasExportHandler()
	{
		return is_resource($this->fp_export);
	}

	/**
	 * Returns the resource file pointer on which the CSV lines will be exported.
	 * 
	 * @return 	resource
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public function getExportCSVHandler()
	{
		if (is_null($this->fp_export)) {
			// set the default resource file pointer (output)
			$this->setExportCSVHandler();
		}

		return $this->fp_export;
	}

	/**
	 * Sets the resource file pointer on which the CSV lines will be exported.
	 * Useful in case the export should be made on a file rather than on output.
	 * 
	 * @param 	string|resource 	$filename 	file path, identifier or resource.
	 * @param 	string 				$mode 		the mode for opening the file pointer.
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public function setExportCSVHandler($filename = 'php://output', $mode = 'w')
	{
		// set resource file pointer
		if (is_resource($filename)) {
			$this->fp_export = $filename;
		} else {
			$this->fp_export = fopen($filename, $mode);
		}

		return $this;
	}

	/**
	 * Sets the current type of CSV export format.
	 * 
	 * @param 	string 	$format 	either "csv" or "excel".
	 * 
	 * @return 	self
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public function setExportCSVFormat($format)
	{
		$export_format = 'csv';
		if (!strcasecmp($format, 'excel')) {
			$export_format = 'excel';
		}

		// set format type
		$this->csv_export_format = $export_format;

		return $this;
	}

	/**
	 * Sends to output the necessary headers to download the export file.
	 * 
	 * @param 	array 	$headers 	optional additional or actual headers.
	 * @param 	bool 	$replace 	if true, only the provided headers will be used.
	 * 
	 * @return 	void
	 */
	public function outputHeaders(array $headers = [], $replace = false)
	{
		foreach ($headers as $header) {
			// send custom header
			header($header);
		}

		if ($replace) {
			// do not send any other header
			return;
		}

		// force the download of the CSV file
		if (!strcasecmp($this->csv_export_format, 'excel')) {
			// file compatible with Excel
			header('Content-type: text/csv; charset=UTF-16LE');
		} else {
			// regular CSV
			header('Content-type: text/csv; charset=UTF-8');
		}
		header('Cache-Control: no-store, no-cache');
		header('Content-Disposition: attachment; filename="' . addslashes(basename($this->getExportCSVFileName(), '.csv')) . '.csv"');
	}

	/**
	 * Writes the CSV lines to export onto the current resource handler.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	public function outputCSV(array $csvlines)
	{
		// get resource file pointer
		$fp = $this->getExportCSVHandler();

		if (!$fp) {
			// resource file pointer unavailable
			return false;
		}

		// default CSV delimiter and enclosure
		$separator = ',';
		$enclosure = "\"";
		if (!strcasecmp($this->csv_export_format, 'excel')) {
			// for Excel we use the semicolon as separator and double quotes as enclosure
			$separator = ';';
		}

		// send lines to output
		foreach ($csvlines as $csvline) {
			if (is_string($csvline)) {
				// must be the first line instructions for the export format
				fputs($fp, $csvline);

				// go to the next line
				continue;
			}

			// put the array of values as a new CSV line
			fputcsv($fp, $csvline, $separator, $enclosure);
		}

		// close the file pointer
		fclose($fp);

		return true;
	}

	/**
	 * Loads a specific report class and returns its instance.
	 * Should be called for instantiating any report sub-class.
	 * 
	 * @param 	string 	$report 	the report file name (i.e. "revenue").
	 * 
	 * @return 	mixed 	false or requested report object.
	 */
	public static function getInstanceOf($report)
	{
		if (empty($report) || !is_string($report)) {
			return false;
		}

		if (substr($report, -4) != '.php') {
			$report .= '.php';
		}

		$report_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $report;
		$classname = 'VikBookingReport' . str_replace(' ', '', ucwords(str_replace('.php', '', str_replace('_', ' ', $report))));

		if (!is_file($report_path)) {
			/**
			 * Trigger event to let other plugins register additional drivers.
			 *
			 * @since 	1.16.0 (J) - 1.6.0 (WP)
			 */
			$list = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadPmsReports');
			foreach ($list as $chunk) {
				if (!is_array($chunk) || !$chunk) {
					continue;
				}
				foreach ($chunk as $thirdp_report) {
					if (basename($thirdp_report) == $report) {
						// driver found
						$report_path = $thirdp_report;
						break;
					}
				}
			}
		}

		if (!is_file($report_path)) {
			// report driver file not found
			return false;
		}

		// load report
		require_once $report_path;

		if (class_exists($classname)) {
			// return the instance of the report object found
			return new $classname;
		}

		return false;
	}

	/**
	 * Injects request variables for the report like if some filters were set.
	 * 
	 * @param 	array 	$vars 	associative list of request vars to inject.
	 *
	 * @return 	void
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP) static-context used to construct the report object later.
	 */
	public static function setRequestVars(array $vars)
	{
		foreach ($vars as $key => $value) {
			/**
			 * For more safety across different platforms and versions (J3/J4 or WP)
			 * we inject values in the super global array as well as in the input object.
			 */
			VikRequest::setVar($key, $value, 'request');
			VikRequest::setVar($key, $value);
		}
	}

	/**
	 * Proxy for object-context to inject request variables for the report.
	 * 
	 * @param 	array 	$params 	associative list of request vars to inject.
	 *
	 * @return 	self
	 */
	public function injectParams($params)
	{
		if (is_array($params) && $params) {
			self::setRequestVars($params);
		}

		return $this;
	}

	/**
	 * Loads Charts CSS/JS assets.
	 *
	 * @return 	self
	 */
	public function loadChartsAssets()
	{
		$document = JFactory::getDocument();
		$document->addStyleSheet(VBO_ADMIN_URI . 'resources/Chart.min.css', ['version' => VIKBOOKING_SOFTWARE_VERSION]);
		$document->addScript(VBO_ADMIN_URI . 'resources/Chart.min.js', ['version' => VIKBOOKING_SOFTWARE_VERSION]);

		return $this;
	}

	/**
	 * Loads the jQuery UI Datepicker.
	 * Method used only by sub-classes.
	 *
	 * @return 	self
	 */
	protected function loadDatePicker()
	{
		$vbo_app = VikBooking::getVboApplication();
		$vbo_app->loadDatePicker();

		return $this;
	}

	/**
	 * Applies the proper encoding to the field being added to
	 * the CSV lines for export, depending on CSV or Excel.
	 * 
	 * @param 	string 	$field 	the value being added to the export line.
	 * 
	 * @return 	string 			either the original or the properly encoded field.
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 * @since 	1.16.8 (J) - 1.6.8 (WP) introduced hook to allow to manipulate encoding.
	 */
	protected function encodeExportCSVField($field)
	{
		/**
		 * Trigger event to allow third-party plugins to manipulate encoding,
		 * in case certain dependencies are not available, such as "mb" support,
		 * PECL intl for "transliterator_transliterate" or "iconv" to ASCII.
		 * 
		 * @since 	1.16.8 (J) - 1.6.8 (WP)
		 */
		$apply_encoding = VBOFactory::getPlatform()->getDispatcher()->filter('onBeforeEncodingCSVField', [&$field, $this->csv_export_format]);

		if (in_array(false, $apply_encoding, true)) {
			// the hook ordered to not proceed with applying any encoding
			return $field;
		}

		if (!is_string($field) || !strcasecmp($this->csv_export_format, 'csv')) {
			// apply no encoding in case of regular CSV or if non-string data type
			return $field;
		}

		// process the Excel-like string field
		if (preg_match('/[\\x80-\\xff]/', $field)) {
			// UTF-8 encoding detected
			if (function_exists('transliterator_transliterate')) {
				// if Transliterator is available (PECL intl >= 2.0.0), transliterate to ASCII
				$field = transliterator_transliterate('Any-Latin; Latin-ASCII;', $field);
			}

			// attempt to convert UTF-8 to ASCII to support currencies
		    $field = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $field);
		}

		if (!function_exists('mb_convert_encoding')) {
			// abort to prevent server errors
			return $field;
		}

		// convert encoding to UTF-16LE (low-endian with BOM)
		return mb_convert_encoding($field, 'UTF-16LE', ['ASCII', 'UTF-8', 'ISO-8859-1']);
	}

	/**
	 * Loads all the rooms in VBO and returns the array.
	 *
	 * @return 	array
	 */
	protected function getRooms()
	{
		$q = "SELECT * FROM `#__vikbooking_rooms` ORDER BY `name` ASC;";
		$this->dbo->setQuery($q);
		$rooms = $this->dbo->loadAssocList();

		return $rooms;
	}

	/**
	 * Loads all the rate plans in VBO and returns the array.
	 *
	 * @return 	array
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	protected function getRatePlans()
	{
		$q = "SELECT * FROM `#__vikbooking_prices` ORDER BY `name` ASC;";
		$this->dbo->setQuery($q);
		$rplans = $this->dbo->loadAssocList();

		return VikBooking::sortRatePlans($rplans);
	}

	/**
	 * Returns the number of total units for all rooms, or for a specific room.
	 * By default, the rooms unpublished are skipped, and all rooms are used.
	 * 
	 * @param 	[mixed] $idroom 	int or array.
	 * @param 	[int] 	$published 	true or false.
	 *
	 * @return 	int
	 */
	protected function countRooms($idroom = 0, $published = 1)
	{
		$clauses = [];
		if (is_int($idroom) && $idroom > 0) {
			$clauses[] = "`id`=".(int)$idroom;
		} elseif (is_array($idroom) && $idroom) {
			$clauses[] = "`id` IN (" . implode(', ', $idroom) . ")";
		}
		if ($published) {
			$clauses[] = "`avail`=1";
		}

		$q = "SELECT SUM(`units`) FROM `#__vikbooking_rooms`".($clauses ? " WHERE ".implode(' AND ', $clauses) : "").";";
		$this->dbo->setQuery($q);
		$totrooms = (int)$this->dbo->loadResult();

		return $totrooms;
	}

	/**
	 * Concatenates the JavaScript rules.
	 * Method used only by sub-classes.
	 *
	 * @param 	string 		$str
	 *
	 * @return 	self
	 */
	protected function setScript($str)
	{
		$this->reportScript .= $str."\n";

		return $this;
	}

	/**
	 * Gets the current script string.
	 *
	 * @return 	string
	 */
	public function getScript()
	{
		return rtrim($this->reportScript, "\n");
	}

	/**
	 * Returns the date format in VBO for date, jQuery UI, Joomla/WordPress.
	 * The visibility of this method should be public for anyone who needs it.
	 *
	 * @param 	string 		$type
	 *
	 * @return 	string
	 */
	public function getDateFormat($type = 'date')
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
	 * Returns the translated weekday.
	 * Uses the back-end language definitions.
	 *
	 * @param 	int 	$wday
	 * @param 	string 	$type 	use 'long' for the full name of the week, short for the 3-char version
	 *
	 * @return 	string
	 */
	protected function getWdayString($wday, $type = 'long')
	{
		$wdays_map_long = [
			JText::translate('VBWEEKDAYZERO'),
			JText::translate('VBWEEKDAYONE'),
			JText::translate('VBWEEKDAYTWO'),
			JText::translate('VBWEEKDAYTHREE'),
			JText::translate('VBWEEKDAYFOUR'),
			JText::translate('VBWEEKDAYFIVE'),
			JText::translate('VBWEEKDAYSIX')
		];

		$wdays_map_short = [
			JText::translate('VBSUN'),
			JText::translate('VBMON'),
			JText::translate('VBTUE'),
			JText::translate('VBWED'),
			JText::translate('VBTHU'),
			JText::translate('VBFRI'),
			JText::translate('VBSAT')
		];

		if ($type != 'long') {
			return isset($wdays_map_short[(int)$wday]) ? $wdays_map_short[(int)$wday] : '';
		}

		return isset($wdays_map_long[(int)$wday]) ? $wdays_map_long[(int)$wday] : '';
	}

	/**
	 * Sets the columns for this report.
	 *
	 * @param 	array 	$arr
	 *
	 * @return 	self
	 */
	public function setReportCols($arr)
	{
		$this->cols = $arr;

		return $this;
	}

	/**
	 * Returns the columns for this report.
	 * Should be called after getReportData()
	 * or the returned array will be empty.
	 *
	 * @return 	array
	 */
	public function getReportCols()
	{
		return $this->cols;
	}

	/**
	 * Sorts the rows of the report by key.
	 *
	 * @param 	string 		$krsort 	the key attribute of the array pairs
	 * @param 	string 		$krorder 	ascending (ASC) or descending (DESC)
	 *
	 * @return 	void
	 */
	protected function sortRows($krsort, $krorder)
	{
		if (empty($krsort) || !$this->rows) {
			return;
		}

		$map = [];
		foreach ($this->rows as $k => $row) {
			foreach ($row as $kk => $v) {
				if (isset($v['key']) && $v['key'] == $krsort) {
					$map[$k] = $v['value'];
				}
			}
		}
		if (!$map) {
			return;
		}

		if ($krorder == 'ASC') {
			asort($map);
		} else {
			arsort($map);
		}

		$sorted = [];
		foreach ($map as $k => $v) {
			$sorted[$k] = $this->rows[$k];
		}

		$this->rows = $sorted;
	}

	/**
	 * Sets the rows for this report.
	 *
	 * @param 	array 	$arr
	 *
	 * @return 	self
	 */
	public function setReportRows($arr)
	{
		$this->rows = $arr;

		return $this;
	}

	/**
	 * Returns the rows for this report.
	 * Should be called after getReportData()
	 * or the returned array will be empty.
	 *
	 * @return 	array
	 */
	public function getReportRows()
	{
		return $this->rows;
	}

	/**
	 * This method returns one or more rows (given the depth) generated by
	 * the current report invoked. It is useful to clean up the callbacks
	 * of the various cell-rows, to obtain a parsable result.
	 * Can be called as first method, by skipping also getReportData(). 
	 * 
	 * @param 	int 	$depth 	how many records to obtain, null for all.
	 *
	 * @return 	array 	the queried report value in the given depth.
	 * 
	 * @uses 	getReportData()
	 */
	public function getReportValues($depth = null)
	{
		if (!$this->rows && !$this->getReportData()) {
			return [];
		}

		$report_values = [];

		foreach ($this->rows as $rk => $row) {
			$report_values[$rk] = [];
			foreach ($row as $col => $coldata) {
				$display_value = $coldata['value'];
				if (isset($coldata['callback']) && is_callable($coldata['callback'])) {
					// launch callback
					$display_value = $coldata['callback']($coldata['value']);
				}
				// push column value
				$report_values[$rk][$coldata['key']] = [
					'value' 		=> $coldata['value'],
					'display_value' => $display_value,
				];
				/**
				 * We also pass along any reserved key for this row-data.
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				foreach ($coldata as $res_key => $data_val) {
					if (substr($res_key, 0, 1) == '_') {
						// push this reserved key
						$report_values[$rk][$coldata['key']][$res_key] = $data_val;
					}
				}
			}
		}

		if (!$report_values) {
			return [];
		}

		if ($depth === 1) {
			// get an associative array with the first row calculated
			return $report_values[0];
		}

		if (is_int($depth) && $depth > 0 && count($report_values) >= $depth) {
			// get the requested portion of the array
			return array_slice($report_values, 0, $depth);
		}

		return $report_values;
	}

	/**
	 * Maps the columns labels to an associative array to be used for the values.
	 * 
	 * @return 	array 	associative list of column keys and related values.
	 */
	public function getColumnsValues()
	{
		if (!$this->cols) {
			return [];
		}

		$col_values = [];

		foreach ($this->cols as $col) {
			if (!isset($col['key'])) {
				continue;
			}
			$col_values[$col['key']] = $col;
			unset($col_values[$col['key']]['key']);
		}

		return $col_values;
	}

	/**
	 * Gets a property defined by the report. Useful to get custom
	 * properties set up by a specific report maybe for the Chart.
	 * 
	 * @param 	string 	$property 	the name of the property needed.
	 * @param 	mixed 	$def 		default value to return.
	 * 
	 * @return 	mixed 	false on failure, property requested otherwise.
	 */
	public function getProperty($property, $def = false)
	{
		if (isset($this->{$property})) {
			return $this->{$property};
		}

		return $def;
	}

	/**
	 * Counts the number of days of difference between two timestamps.
	 * 
	 * @param 	int 	$to_ts 		the target end date timestamp.
	 * @param 	int 	$from_ts 	the starting date timestamp.
	 * 
	 * @return 	int 	the days of difference between from and to timestamps.
	 */
	public function countDaysTo($to_ts, $from_ts = 0)
	{
		if (empty($from_ts)) {
			$from_ts = time();
		}

		// whether DateTime can be used
		$usedt = false;

		if (class_exists('DateTime')) {
			$from_date = new DateTime(date('Y-m-d', $from_ts));
			if (method_exists($from_date, 'diff')) {
				$usedt = true;
			}
		}

		if ($usedt) {
			$to_date = new DateTime(date('Y-m-d', $to_ts));
			$daysdiff = (int)$from_date->diff($to_date)->format('%a');
			if ($to_ts < $from_ts) {
				// we need a negative integer number
				$daysdiff = $daysdiff - ($daysdiff * 2);
			}
			return $daysdiff;
		}

		return (int)round(($to_ts - $from_ts) / 86400);
	}

	/**
	 * Counts the average difference between two integers.
	 * 
	 * @param 	int 	$in_days_from 	days to the lowest timestamp.
	 * @param 	int 	$in_days_to 	days to the highest timestamp.
	 * 
	 * @return 	int 	the average number between the two values.
	 */
	public function countAverageDays($in_days_from, $in_days_to)
	{
		return (int)floor(($in_days_from + $in_days_to) / 2);
	}

	/**
	 * Sets the footer row (the totals) for this report.
	 *
	 * @param 	array 	$arr
	 *
	 * @return 	self
	 */
	protected function setReportFooterRow($arr)
	{
		$this->footerRow = $arr;

		return $this;
	}

	/**
	 * Returns the footer row for this report.
	 * Should be called after getReportData()
	 * or the returned array will be empty.
	 *
	 * @return 	array
	 */
	public function getReportFooterRow()
	{
		return $this->footerRow;
	}

	/**
	 * Sub-classes can extend this method to define the
	 * the canvas HTML tag for rendenring the Chart.
	 * Any necessary script shall be set within this method.
	 * Data can be passed as a mixed value through the argument.
	 * This is the first method to be called when working with the Chart.
	 * 
	 * @param 	mixed 	$data 	any necessary value to render the Chart.
	 *
	 * @return 	string 	the HTML of the canvas element.
	 */
	public function getChart($data = null)
	{
		return '';
	}

	/**
	 * Sub-classes can extend this method to define the
	 * the title of the Chart to be rendered.
	 *
	 * @return 	string 	the title of the Chart.
	 */
	public function getChartTitle()
	{
		return '';
	}

	/**
	 * Sub-classes can extend this method to define
	 * the meta data for the Chart containing stats.
	 * An array for each meta-data should be returned.
	 * 
	 * @param 	mixed 	$position 	string for the meta-data position
	 * 								in the Chart (top, right, bottom).
	 * @param 	mixed 	$data 		some arguments to be passed.
	 *
	 * @return 	array
	 */
	public function getChartMetaData($position = null, $data = null)
	{
		return [];
	}

	/**
	 * Sets an array of custom options for this report. Useful to inject
	 * params before getting the report data and changing the behavior.
	 *
	 * @param 	array 	$arr
	 *
	 * @return 	self
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public function setReportOptions($options = [])
	{
		$this->options = $options;

		return $this;
	}

	/**
	 * Returns the custom options for the report. Useful to
	 * behave differently depending on who calls the report.
	 * By default, the method returns an instance of JObject
	 * to easily access all custom options defined, if any.
	 * 
	 * @param 	bool 	$registry 	true to get a JObject instance.
	 *
	 * @return 	mixed 				instance of JObject or raw array.
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
	 */
	public function getReportOptions($registry = true)
	{
		if ($registry) {
			return new JObject($this->options);
		}

		return $this->options;
	}

	/**
	 * Sets warning messages by concatenating the existing ones.
	 * Method used only by sub-classes.
	 *
	 * @param 	string 		$str
	 *
	 * @return 	self
	 */
	protected function setWarning($str)
	{
		$this->warning .= $str."\n";

		return $this;
	}

	/**
	 * Gets the current warning string.
	 *
	 * @return 	string
	 */
	public function getWarning()
	{
		return rtrim($this->warning, "\n");
	}

	/**
	 * Sets errors by concatenating the existing ones.
	 * Method used only by sub-classes.
	 *
	 * @param 	string 		$str
	 *
	 * @return 	self
	 */
	protected function setError($str)
	{
		$this->error .= $str."\n";

		return $this;
	}

	/**
	 * Gets the current error string.
	 *
	 * @return 	string
	 */
	public function getError()
	{
		return rtrim($this->error, "\n");
	}
}
