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
 * Options/Extras child Class of VikBookingReport
 */
class VikBookingReportOptionsExtras extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 */
	public $defaultKeySort = 'day';

	/**
	 * Property 'defaultKeyOrder' is used by the View that renders the report.
	 */
	public $defaultKeyOrder = 'ASC';

	/**
	 * Property 'exportAllowed' is used by the View to display the export button.
	 */
	public $exportAllowed = 1;

	/**
	 * Debug mode is activated by passing the value 'e4j_debug' > 0
	 */
	private $debug;

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = JText::translate('VBOREPORT'.strtoupper(str_replace('_', '', $this->reportFile)));
		$this->reportFilters = array();

		$this->cols = array();
		$this->rows = array();
		$this->footerRow = array();

		$this->debug = (VikRequest::getInt('e4j_debug', 0, 'request') > 0);

		$this->registerExportCSVFileName();

		parent::__construct();
	}

	/**
	 * Returns the name of this report.
	 *
	 * @return 	string
	 */
	public function getName()
	{
		return $this->reportName;
	}

	/**
	 * Returns the name of this file without .php.
	 *
	 * @return 	string
	 */
	public function getFileName()
	{
		return $this->reportFile;
	}

	/**
	 * Returns the filters of this report.
	 *
	 * @return 	array
	 */
	public function getFilters()
	{
		if (count($this->reportFilters)) {
			//do not run this method twice, as it could load JS and CSS files.
			return $this->reportFilters;
		}

		//get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		//load the jQuery UI Datepicker
		$this->loadDatePicker();

		//From Date Filter
		$filter_opt = array(
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTSDATEFROM').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />',
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		//To Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
			'type' => 'calendar',
			'name' => 'todate'
		);
		array_push($this->reportFilters, $filter_opt);

		//Option ID filter
		$pidoptional = VikRequest::getInt('idoptional', '', 'request');
		$options = $this->getAllOptions();
		if (count($options)) {
			$opts_sel_html = $vbo_app->getNiceSelect($options, $pidoptional, 'idoptional', JText::translate('VBOREPORTFILTALLOPTS'), JText::translate('VBOREPORTFILTALLOPTS'), '', '', 'idoptional');
			$filter_opt = array(
				'label' => '<label for="idoptional">'.JText::translate('VBOREPORTOPTIONSEXTRAS').'</label>',
				'html' => $opts_sel_html,
				'type' => 'select',
				'name' => 'idoptional'
			);
			array_push($this->reportFilters, $filter_opt);
		}

		//jQuery code for the datepicker calendars and select2
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$js = 'jQuery(document).ready(function() {
			jQuery(".vbo-report-datepicker:input").datepicker({
				maxDate: 0,
				dateFormat: "'.$this->getDateFormat('jui').'",
				onSelect: vboReportCheckDates
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
		});
		function vboReportCheckDates(selectedDate, inst) {
			if (selectedDate === null || inst === null) {
				return;
			}
			var cur_from_date = jQuery(this).val();
			if (jQuery(this).hasClass("vbo-report-datepicker-from") && cur_from_date.length) {
				var nowstart = jQuery(this).datepicker("getDate");
				var nowstartdate = new Date(nowstart.getTime());
				jQuery(".vbo-report-datepicker-to").datepicker("option", {minDate: nowstartdate});
			}
		}';
		$this->setScript($js);

		return $this->reportFilters;
	}

	/**
	 * Loads the report data from the DB.
	 * Returns true in case of success, false otherwise.
	 * Sets the columns and rows for the report to be displayed.
	 *
	 * @return 	boolean
	 */
	public function getReportData()
	{
		if (strlen($this->getError())) {
			//Export functions may set errors rather than exiting the process, and the View may continue the execution to attempt to render the report.
			return false;
		}
		$options = $this->getAllOptions(true);
		if (!count($options)) {
			$this->setError('No Options configured in the system.');
			return false;
		}
		//Input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$pidoptional = VikRequest::getInt('idoptional', '', 'request');
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
		$currency_symb = VikBooking::getCurrencySymb();
		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		if (empty($ptodate)) {
			$ptodate = $pfromdate;
		}
		//Get dates timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		//Query to obtain the records
		$records = array();
		$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,`o`.`tot_taxes`,`o`.`tot_city_taxes`,".
			"`o`.`tot_fees`,`o`.`cmms`,`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`optionals`,`or`.`childrenage`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
			"WHERE `o`.`status`='confirmed' AND `o`.`closure`=0 AND `o`.`checkout`>=".$from_ts." AND `o`.`checkin`<=".$to_ts." ".
			"ORDER BY `o`.`checkin` ASC, `o`.`id` ASC;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows() > 0) {
			$records = $this->dbo->loadAssocList();
		}
		if (!count($records)) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			return false;
		}

		$valid_opt_ids = array_keys($options);

		//nest records with multiple rooms booked inside sub-array
		$bookings = array();
		foreach ($records as $v) {
			if (empty($v['optionals'])) {
				continue;
			}
			if (!empty($pidoptional) && strpos($v['optionals'], $pidoptional.':') === false) {
				//this booking does not contain the filtered option
				continue;
			}
			$has_valid_options = false;
			foreach ($valid_opt_ids as $optid) {
				if (strpos($v['optionals'], $optid.':') !== false) {
					$has_valid_options = true;
					break;
				}
			}
			if (!$has_valid_options) {
				//maybe this booking has only city taxes as options, not real ones, so we don't take it
				continue;
			}
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = array();
			}
			//calculate the from_ts and to_ts values for later comparison
			$in_info = getdate($v['checkin']);
			$out_info = getdate($v['checkout']);
			$v['from_ts'] = mktime(0, 0, 0, $in_info['mon'], $in_info['mday'], $in_info['year']);
			$v['to_ts'] = mktime(23, 59, 59, $out_info['mon'], ($out_info['mday'] - 1), $out_info['year']);
			//
			array_push($bookings[$v['id']], $v);
		}

		//define the columns of the report and the Option IDs for the rows
		$option_rows = array();
		$this->cols = array(
			//date
			array(
				'key' => 'day',
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEDAY')
			)
		);
		foreach ($options as $opt) {
			if (!empty($pidoptional) && $pidoptional != $opt['id']) {
				continue;
			}
			$opt['row_value_net'] = 0;
			$opt['row_value_tax'] = 0;
			$option_rows[$opt['id']] = $opt;
			array_push($this->cols, array(
				'key' => 'optid'.$opt['id'],
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => $opt['name']
			));
		}

		//loop over the dates of the report to build the rows
		$from_info = getdate($from_ts);
		$to_info = getdate($to_ts);
		while ($from_info[0] <= $to_info[0]) {
			//prepare default fields for this row
			$day_ts = $from_info[0];
			$curwday = $this->getWdayString($from_info['wday'], 'short');
			$option_day_row = $option_rows;
			//calculate the report details for this day
			foreach ($bookings as $gbook) {
				if ($from_info[0] >= $gbook[0]['from_ts'] && $from_info[0] <= $gbook[0]['to_ts']) {
					//this booking affects the current day
					foreach ($gbook as $or) {
						$stepo = explode(";", $or['optionals']);
						foreach ($stepo as $roptkey => $one) {
							if (empty($one)) {
								continue;
							}
							$stept = explode(":", $one);
							if (!isset($option_rows[(int)$stept[0]])) {
								//we don't need this option
								continue;
							}
							$actopt = $option_rows[(int)$stept[0]];
							//calculate the cost of this option
							if (!empty($actopt['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
								$optagenames = VikBooking::getOptionIntervalsAges($actopt['ageintervals']);
								$optagepcent = VikBooking::getOptionIntervalsPercentage($actopt['ageintervals']);
								$optageovrct = VikBooking::getOptionIntervalChildOverrides($actopt, $or['adults'], $or['children']);
								$child_num 	 = VikBooking::getRoomOptionChildNumber($or['optionals'], $actopt['id'], $roptkey, $or['children']);
								$optagecosts = VikBooking::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt['ageintervals']);
								$agestept = explode('-', $stept[1]);
								$stept[1] = $agestept[0];
								$chvar = $agestept[1];
								if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
									//percentage value of the adults tariff
									if (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00) {
										$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
									} else {
										//we ignore the room base cost (tars) as it's not available here
										$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : 0;
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
								} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
									//VBO 1.10 - percentage value of room base cost
									if (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00) {
										$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
									} else {
										//we ignore the room base cost (tars) as it's not available here
										$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : 0;
										$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
									}
								}
								$realcost = (intval($actopt['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $gbook[0]['days'] * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
							} else {
								// VBO 1.11 - options percentage cost of the room total fee
								if (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00) {
									$deftar_basecosts = $or['cust_cost'];
								} else {
									$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : 0;
								}
								$actopt['cost'] = (int)$actopt['pcentroom'] ? ($deftar_basecosts * $actopt['cost'] / 100) : $actopt['cost'];
								//
								$realcost = (intval($actopt['perday']) == 1 ? ($actopt['cost'] * $gbook[0]['days'] * $stept[1]) : ($actopt['cost'] * $stept[1]));
							}
							if (!empty ($actopt['maxprice']) && $actopt['maxprice'] > 0 && $realcost > $actopt['maxprice']) {
								$realcost = $actopt['maxprice'];
								if (intval($actopt['hmany']) == 1 && intval($stept[1]) > 1) {
									$realcost = $actopt['maxprice'] * $stept[1];
								}
							}
							if ($actopt['perperson'] == 1) {
								$realcost = $realcost * $or['adults'];
							}
							$with_tax = VikBooking::sayOptionalsPlusIva($realcost, $actopt['idiva']);
							$with_tax_daily = $with_tax / (int)$gbook[0]['days'];
							$without_tax = VikBooking::sayOptionalsMinusIva($realcost, $actopt['idiva']);
							$without_tax_daily = $without_tax / (int)$gbook[0]['days'];
							//update values for this option, for this day
							$option_day_row[(int)$stept[0]]['row_value_net'] += $without_tax_daily;
							$option_day_row[(int)$stept[0]]['row_value_tax'] += $with_tax_daily - $without_tax_daily;

						}
					}
				}
			}

			//push fields in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'day',
					'callback' => function ($val) use ($df, $datesep, $curwday) {
						return $curwday.', '.date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $day_ts
				)
			));
			$row_key = count($this->rows) - 1;
			foreach ($option_day_row as $idopt => $v) {
				array_push($this->rows[$row_key], array(
					'key' => 'optid'.$idopt,
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb.' '.VikBooking::numberFormat($val);
					},
					'value' => $v['row_value_net']
				));
			}

			//next day iteration
			$from_info = getdate(mktime(0, 0, 0, $from_info['mon'], ($from_info['mday'] + 1), $from_info['year']));
		}

		//sort rows
		$this->sortRows($pkrsort, $pkrorder);

		//loop over the rows to build the footer row with the totals
		$foot_tots = array();
		foreach ($this->rows as $row) {
			foreach ($row as $k => $v) {
				if ($k < 1) {
					continue;
				}
				if (!isset($foot_tots[$k])) {
					$foot_tots[$k] = 0;
				}
				$foot_tots[$k] += $v['value'];
			}
		}
		array_push($this->footerRow, array(
			array(
				'attr' => array(
					'class="vbo-report-total"'
				),
				'value' => '<h3>'.JText::translate('VBOREPORTSTOTALROW').'</h3>'
			)
		));
		foreach ($foot_tots as $v) {
			array_push($this->footerRow[0], array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb.' '.VikBooking::numberFormat($val);
				},
				'value' => $v
			));
		}

		//Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
			$this->setWarning('$total_rooms_units = '.$total_rooms_units.'<br/>');
			$this->setWarning('$bookings:<pre>'.print_r($bookings, true).'</pre><br/>');
		}
		//

		return true;
	}

	/**
	 * Registers the name to give to the CSV file being exported.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	private function registerExportCSVFileName()
	{
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.csv');
	}

	/**
	 * Reads from the DB all the Options that are not
	 * tourist taxes, and returns an associative array
	 * with the ID of the Option as key.
	 *
	 * @param 	boolean 	$return_all 	return the whole array record
	 *
	 * @return 	array
	 */
	private function getAllOptions($return_all = false)
	{
		$all_options = array();
		$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `is_citytax`=0 ORDER BY `name` ASC;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$all_options = $this->dbo->loadAssocList();
		}
		$options = array();
		foreach ($all_options as $opt) {
			$options[$opt['id']] = $return_all ? $opt : $opt['name'];
		}

		return $options;
	}
}
