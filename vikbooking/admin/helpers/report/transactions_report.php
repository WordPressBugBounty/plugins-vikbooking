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
 * Transactions child Class of VikBookingReport
 */
class VikBookingReportTransactionsReport extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 */
	public $defaultKeySort = 'tot';

	/**
	 * Property 'defaultKeyOrder' is used by the View that renders the report.
	 */
	public $defaultKeyOrder = 'DESC';

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

		//Room ID filter
		$pidroom = VikRequest::getInt('idroom', '', 'request');
		$all_rooms = $this->getRooms();
		$rooms = array();
		foreach ($all_rooms as $room) {
			$rooms[$room['id']] = $room['name'];
		}
		if (count($rooms)) {
			$rooms_sel_html = $vbo_app->getNiceSelect($rooms, $pidroom, 'idroom', JText::translate('VBOSTATSALLROOMS'), JText::translate('VBOSTATSALLROOMS'), '', '', 'idroom');
			$filter_opt = array(
				'label' => '<label for="idroom">'.JText::translate('VBOREPORTSROOMFILT').'</label>',
				'html' => $rooms_sel_html,
				'type' => 'select',
				'name' => 'idroom'
			);
			array_push($this->reportFilters, $filter_opt);
		}

		// get minimum check-in and maximum check-out for dates filters
		$df = $this->getDateFormat();
		$mincheckin = 0;
		$maxcheckout = 0;
		$q = "SELECT MIN(`checkin`) AS `mincheckin`, MAX(`checkout`) AS `maxcheckout` FROM `#__vikbooking_orders` WHERE `status`='confirmed' AND `closure`=0;";
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
		$pidroom = VikRequest::getInt('idroom', '', 'request');
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

		/**
		 * We do not make a left join onto the orders roooms table
		 * or we may obtain multiple records with the query. Since we cannot
		 * group by booking ID as we need all history logs of type PU,
		 * we try to make a sub-query to concatenate all room IDs.
		 */

		// Query to obtain the records
		$bookings = array();
		$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`idpayment`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,`o`.`tot_taxes`,".
			"`o`.`tot_city_taxes`,`o`.`tot_fees`,`o`.`cmms`,`h`.`dt`,`h`.`data`, (SELECT GROUP_CONCAT(`or`.`idroom` SEPARATOR ';') FROM `#__vikbooking_ordersrooms` AS `or` WHERE `or`.`idorder`=`o`.`id`) AS `idrooms` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_orderhistory` AS `h` ON `h`.`idorder`=`o`.`id` AND `h`.`type`='PU' ".
			"WHERE `o`.`status`='confirmed' AND `o`.`closure`=0 AND `o`.`totpaid` > 0 ".
			"AND ((`o`.`ts`>=".$from_ts." AND `o`.`ts`<=".$to_ts.") OR (`h`.`data` IS NOT NULL AND `h`.`dt`>=".$this->dbo->quote(date('Y-m-d H:i:s', $from_ts))." AND `h`.`dt`<=".$this->dbo->quote(date('Y-m-d H:i:s', $to_ts)).")) ".
			"ORDER BY `o`.`ts` ASC, `h`.`dt` DESC;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$bookings = $this->dbo->loadAssocList();
			// apply room ID filter that was not used via SQL
			if (!empty($pidroom)) {
				foreach ($bookings as $k => $gbook) {
					$roomids = explode(';', $gbook['idrooms']);
					if (!in_array($pidroom, $roomids)) {
						unset($bookings[$k]);
					}
				}
			}
		}
		if (!count($bookings)) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			return false;
		}

		// Debug
		// $this->setWarning('<pre>'.print_r($bookings, true).'</pre><br/>');
		//

		//define the columns of the report
		$this->cols = array(
			//date
			array(
				'key' => 'paymeth',
				'sortable' => 1,
				'label' => JText::translate('VBPAYMENTMETHOD')
			),
			//rooms sold
			array(
				'key' => 'tot',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBLIBSIX')
			),
			//bookings affected
			array(
				'key' => 'ids',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 0,
				'ignore_export' => 0,
				'label' => JText::translate('VBMENUTHREE')
			),
		);

		$paystats = array();
		$allbids  = array();

		// loop over the bookings to build the payment stats
		foreach ($bookings as &$gbook) {
			if (!in_array($gbook['id'], $allbids)) {
				array_push($allbids, $gbook['id']);
			}
			$payname = null;
			if (!empty($gbook['data'])) {
				$paydata = json_decode($gbook['data']);
				$payname = is_object($paydata) && isset($paydata->payment_method) ? $paydata->payment_method : $payname;
				// update totpaid if history data available
				if (is_object($paydata) && isset($paydata->amount_paid) && $paydata->amount_paid > 0) {
					$gbook['totpaid'] = (float)$paydata->amount_paid;
				}
			} elseif (!empty($gbook['idpayment'])) {
				if (strpos($gbook['idpayment'], '=') !== false) {
					$parts = explode('=', $gbook['idpayment']);
					$payname = $parts[1];
				} else {
					$payname = $gbook['idpayment'];
				}
			} elseif (!empty($gbook['channel']) && !empty($gbook['idorderota'])) {
				$parts = explode('_', $gbook['channel']);
				unset($parts[0]);
				$payname = implode('_', $parts);
			}

			if (is_null($payname)) {
				// unknown
				$payname = JText::translate('VBOREPORTTOPCUNKNC');
			}

			// update reference
			$gbook['payment_method'] = $payname;

			// update stats for this payment method
			if (!isset($paystats[$payname])) {
				$paystats[$payname] = array(
					'totpaid' => 0,
					'bids' 	  => array()
				);
			}

			// increase total paid and push booking ID
			$paystats[$payname]['totpaid'] += $gbook['totpaid'];
			if (!in_array($gbook['id'], $paystats[$payname]['bids'])) {
				array_push($paystats[$payname]['bids'], $gbook['id']);
			}
		}

		// loop over the stats to build the rows
		foreach ($paystats as $payname => $stats) {
			//push fields in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'paymeth',
					'value' => $payname
				),
				array(
					'key' => 'tot',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb.' '.VikBooking::numberFormat($val);
					},
					'export_callback' => function ($val) use ($currency_symb) {
						return $currency_symb.' '.VikBooking::numberFormat($val);
					},
					'value' => $stats['totpaid']
				),
				array(
					'key' => 'ids',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						$str = '';
						foreach ($val as $bid) {
							$str .= '<span style="display: inline-block; margin: 0 2px;"><a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$bid.'" target="_blank"><i class="'.VikBookingIcons::i('external-link').'"></i> '.$bid.'</a></span>';
						}
						return $str;
					},
					'ignore_export' => 1,
					'value' => $stats['bids']
				),
			));
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// loop over the rows to build the footer row with the totals
		$foot_tot_collected = 0;
		foreach ($this->rows as $row) {
			$foot_tot_collected += $row[1]['value'];
		}

		// push footer row
		array_push($this->footerRow, array(
			array(
				'attr' => array(
					'class="vbo-report-total"'
				),
				'value' => '<h3>'.JText::translate('VBOREPORTSTOTALROW').'</h3>'
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb.' '.VikBooking::numberFormat($val);
				},
				'value' => $foot_tot_collected
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => count($allbids)
			),
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
}
