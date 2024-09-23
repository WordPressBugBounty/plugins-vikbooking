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
 * Tourist Taxes child Class of VikBookingReport
 */
class VikBookingReportTouristTaxes extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 */
	public $defaultKeySort = 'customer';

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

		// get minimum check-in and maximum check-out for dates filters
		$df = $this->getDateFormat();
		$mincheckin = 0;
		$maxcheckout = 0;
		$q = "SELECT MIN(`checkin`) AS `mincheckin`, MAX(`checkout`) AS `maxcheckout` FROM `#__vikbooking_orders` WHERE `status`='confirmed';";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$data = $this->dbo->loadAssoc();
			if (!empty($data['mincheckin']) && !empty($data['maxcheckout'])) {
				$mincheckin = $data['mincheckin'];
				$maxcheckout = $data['maxcheckout'];
			}
		}
		//

		//jQuery code for the datepicker calendars and select2
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$js = 'jQuery(document).ready(function() {
			jQuery(".vbo-report-datepicker:input").datepicker({
				'.(!empty($mincheckin) ? 'minDate: "'.date($df, $mincheckin).'", ' : '').'
				'.(!empty($maxcheckout) ? 'maxDate: "'.date($df, $maxcheckout).'", ' : '').'
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
		//Input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
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
		$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,`o`.`tot_taxes`,".
			"`o`.`tot_city_taxes`,`o`.`tot_fees`,`o`.`cmms`,`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`optionals`,`or`.`t_first_name`,`or`.`t_last_name`,".
			"`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`co`.`idcustomer`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` ".
			"LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` ".
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

		//nest records with multiple rooms booked inside sub-array
		$bookings = array();
		foreach ($records as $v) {
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = array();
			}
			array_push($bookings[$v['id']], $v);
		}

		//define the columns of the report
		$this->cols = array(
			//ID
			array(
				'key' => 'id',
				'sortable' => 1,
				'label' => JText::translate('VBDASHBOOKINGID'),
			),
			//customer
			array(
				'key' => 'customer',
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTOURISTTAXCUSTOMER')
			),
			//country
			array(
				'key' => 'country',
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTTOPCOUNTRIESC')
			),
			//adults
			array(
				'key' => 'adults',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBMAILADULTS')
			),
			//children
			array(
				'key' => 'children',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBMAILCHILDREN')
			),
			//nights
			array(
				'key' => 'nights',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBCSVNIGHTS')
			),
			//checkin
			array(
				'key' => 'checkin',
				'sortable' => 1,
				'label' => JText::translate('VBCSVCHECKIN')
			),
			//checkout
			array(
				'key' => 'checkout',
				'sortable' => 1,
				'label' => JText::translate('VBCSVCHECKOUT')
			),
			//Taxes
			array(
				'key' => 'taxes',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTOURISTTAXTOT')
			)
		);

		//loop over the bookings to build the list of countries
		$countries = array();
		foreach ($bookings as $k => $gbook) {
			$country = 'unknown';
			if (!empty($gbook[0]['country'])) {
				$country = $gbook[0]['country'];
			} elseif (!empty($gbook[0]['customer_country'])) {
				$country = $gbook[0]['customer_country'];
			}
			if (!in_array($country, $countries)) {
				array_push($countries, $country);
			}
			$bookings[$k][0]['country'] = $country;
		}

		$countries_map = $this->getCountriesMap($countries);

		//loop again over the bookings to build the rows of the report
		foreach ($bookings as $gbook) {
			$customer_name = '-----';
			if (!empty($gbook[0]['t_first_name']) && !empty($gbook[0]['t_last_name'])) {
				$customer_name = ltrim($gbook[0]['t_first_name'].' '.$gbook[0]['t_last_name'], ' ');
			} elseif (!empty($gbook[0]['first_name']) && !empty($gbook[0]['last_name'])) {
				$customer_name = ltrim($gbook[0]['first_name'].' '.$gbook[0]['last_name'], ' ');
			} elseif (!empty($gbook[0]['custdata'])) {
				$parts = explode("\n", $gbook[0]['custdata']);
				if (count($parts) >= 2 && strpos($parts[0], ':') !== false && strpos($parts[1], ':') !== false) {
					$first_parts = explode(':', $parts[0]);
					$second_parts = explode(':', $parts[1]);
					$customer_name = ltrim(trim($first_parts[1]).' '.trim($second_parts[1]), ' ');
				}
			}
			$country = $gbook[0]['country'];
			$tot_adults = 0;
			$tot_children = 0;
			foreach ($gbook as $or) {
				$tot_adults += (int)$or['adults'];
				$tot_children += (int)$or['children'];
			}
			$in_info = getdate($gbook[0]['checkin']);
			$inwday = $this->getWdayString($in_info['wday'], 'short');
			$out_info = getdate($gbook[0]['checkout']);
			$outwday = $this->getWdayString($out_info['wday'], 'short');
			$city_tax = (float)$gbook[0]['tot_city_taxes'];
			//push data in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'id',
					'callback' => function ($val) {
						return '<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$val.'" target="_blank"><i class="'.VikBookingIcons::i('external-link').'"></i> '.$val.'</a>';
					},
					'no_csv_callback' => 1,
					'value' => $gbook[0]['id'],
				),
				array(
					'key' => 'customer',
					'attr' => array(
						'class="vbo-report-touristtaxes-countryname"'
					),
					'callback' => function ($val) use ($country) {
						if (file_exists(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'countries'.DIRECTORY_SEPARATOR.$country.'.png')) {
							return $val.'<img src="'.VBO_ADMIN_URI.'resources/countries/'.$country.'.png" title="'.$country.'" class="vbo-country-flag vbo-country-flag-left" />';
						}
						return $val;
					},
					'no_csv_callback' => 1,
					'value' => $customer_name
				),
				array(
					'key' => 'country',
					'value' => (isset($countries_map[$country]) ? $countries_map[$country] : $country)
				),
				array(
					'key' => 'adults',
					'attr' => array(
						'class="center"'
					),
					'value' => $tot_adults
				),
				array(
					'key' => 'children',
					'attr' => array(
						'class="center"'
					),
					'value' => $tot_children
				),
				array(
					'key' => 'nights',
					'attr' => array(
						'class="center"'
					),
					'value' => $gbook[0]['days']
				),
				array(
					'key' => 'checkin',
					'callback' => function ($val) use ($df, $datesep, $inwday) {
						return $inwday.', '.date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $gbook[0]['checkin']
				),
				array(
					'key' => 'checkout',
					'callback' => function ($val) use ($df, $datesep, $outwday) {
						return $outwday.', '.date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $gbook[0]['checkout']
				),
				array(
					'key' => 'taxes',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb.' '.VikBooking::numberFormat($val);
					},
					'value' => $city_tax
				)
			));
		}

		//sort rows
		$this->sortRows($pkrsort, $pkrorder);

		//loop over the rows to build the footer row with the totals
		$foot_adults = 0;
		$foot_children = 0;
		$foot_nights = 0;
		$foot_taxes = 0;
		foreach ($this->rows as $row) {
			$foot_adults += $row[3]['value'];
			$foot_children += $row[4]['value'];
			$foot_nights += $row[5]['value'];
			$foot_taxes += $row[8]['value'];
		}
		array_push($this->footerRow, array(
			array(
				'attr' => array(
					'class="vbo-report-total"'
				),
				'value' => '<h3>'.JText::translate('VBOREPORTSTOTALROW').'</h3>'
			),
			array(
				'value' => ''
			),
			array(
				'value' => ''
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_adults
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_children
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_nights
			),
			array(
				'value' => ''
			),
			array(
				'value' => ''
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb.' '.VikBooking::numberFormat($val);
				},
				'value' => $foot_taxes
			)
		));

		//Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
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
	 * Maps the 3-char country codes to their full names.
	 * Translates also the 'unknown' country.
	 *
	 * @param 	array  		$countries
	 *
	 * @return 	array
	 */
	private function getCountriesMap($countries)
	{
		$map = array();

		if (in_array('unknown', $countries)) {
			$map['unknown'] = JText::translate('VBOREPORTTOPCUNKNC');
			foreach ($countries as $k => $v) {
				if ($v == 'unknown') {
					unset($countries[$k]);
				}
			}
		}

		if (count($countries)) {
			$clauses = array();
			foreach ($countries as $country) {
				array_push($clauses, $this->dbo->quote($country));
			}
			$q = "SELECT `country_name`,`country_3_code` FROM `#__vikbooking_countries` WHERE `country_3_code` IN (".implode(', ', $clauses).");";
			$this->dbo->setQuery($q);
			$this->dbo->execute();
			if ($this->dbo->getNumRows() > 0) {
				$records = $this->dbo->loadAssocList();
				foreach ($records as $v) {
					$map[$v['country_3_code']] = $v['country_name'];
				}
			}
		}

		return $map;
	}
}
