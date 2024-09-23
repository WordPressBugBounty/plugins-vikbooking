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
 * Rate Plans Revenue child Class of VikBookingReport
 */
class VikBookingReportRplansRevenue extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 */
	public $defaultKeySort = 'revenue';

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
		$this->reportFilters = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

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
		$rooms = [];
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

		// channel filter
		$all_channels = [];
		$pchannel = VikRequest::getString('channel', '', 'request');
		$q = "SELECT `channel` FROM `#__vikbooking_orders` WHERE `channel` IS NOT NULL GROUP BY `channel`;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$ord_channels = $this->dbo->loadAssocList();
			// push website as first option
			$all_channels['-1'] = JText::translate('VBORDFROMSITE');
			// push all channel names
			foreach ($ord_channels as $o_channel) {
				$channel_parts = explode('_', $o_channel['channel']);
				$channel_name = count($channel_parts) > 1 ? trim($channel_parts[1]) : trim($channel_parts[0]);
				if (isset($all_channels[$channel_name])) {
					continue;
				}
				$all_channels[$channel_name] = $channel_name;
			}
			// push filter
			$channels_sel_html = $vbo_app->getNiceSelect($all_channels, $pchannel, 'channel', '- - - -', '- - - -', '', '', 'channel');
			$filter_opt = array(
				'label' => '<label for="channel">'.JText::translate('VBCHANNELFILTER').'</label>',
				'html' => $channels_sel_html,
				'type' => 'select',
				'name' => 'channel'
			);
			array_push($this->reportFilters, $filter_opt);
		}

		// full stay count filter
		$pfull_stay_count = VikRequest::getInt('full_stay_count', 0, 'request');
		$fullsc_sel_html = $vbo_app->getNiceSelect([JText::translate('VBO_STAYED_NIGHTS'), JText::translate('VBO_FULL_STAY_VALUES')], $pfull_stay_count, 'full_stay_count', '', '', '', '', 'full_stay_count');
		$filter_opt = array(
			'label' => '<label for="full_stay_count">'.JText::translate('VBO_DATA_CALCULATION').'</label>',
			'html' => $fullsc_sel_html,
			'type' => 'select',
			'name' => 'full_stay_count'
		);
		array_push($this->reportFilters, $filter_opt);

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
		$js = 'jQuery(function() {
			jQuery(".vbo-report-datepicker:input").datepicker({
				'.(!empty($mincheckin) ? 'minDate: "'.date($df, $mincheckin).'", ' : '').'
				'.(!empty($maxcheckout) ? 'maxDate: "'.date($df, $maxcheckout).'", ' : '').'
				'.(!empty($mincheckin) && !empty($maxcheckout) ? 'yearRange: "'.(date('Y', $mincheckin)).':'.date('Y', $maxcheckout).'", changeMonth: true, changeYear: true, ' : '').'
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
		$pchannel = VikRequest::getString('channel', '', 'request');
		$pfull_stay_count = VikRequest::getInt('full_stay_count', 0, 'request');
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
		$records = [];
		$q = "SELECT `o`.`id`,`o`.`ts`,`o`.`status`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`totpaid`,`o`.`roomsnum`,`o`.`total`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,`o`.`tot_taxes`," .
			"`o`.`tot_city_taxes`,`o`.`tot_fees`,`o`.`cmms`,`o`.`canc_fee`,`or`.`idorder`,`or`.`idroom`,`or`.`idtar`,`or`.`optionals`,`or`.`pkg_id`,`or`.`pkg_name`,`or`.`cust_cost`,`or`.`cust_idiva`,`or`.`extracosts`,`or`.`room_cost`,`or`.`otarplan`,`d`.`idprice` " .
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` " .
			"LEFT JOIN `#__vikbooking_dispcost` AS `d` ON `d`.`id`=`or`.`idtar` " .
			"WHERE (`o`.`status`='confirmed' OR (`o`.`status`='cancelled' AND `o`.`canc_fee` > 0)) AND `o`.`closure`=0 AND `o`.`checkout`>={$from_ts} AND `o`.`checkin`<={$to_ts} ".(!empty($pidroom) ? "AND `or`.`idroom`=".(int)$pidroom." " : "") .
			(strlen($pchannel) ? "AND `o`.`channel` " . ($pchannel == '-1' ? 'IS NULL' : "LIKE " . $this->dbo->quote("%{$pchannel}%")) . ' ' : '') .
			"ORDER BY `o`.`checkin` ASC, `o`.`id` ASC;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$records = $this->dbo->loadAssocList();
		}
		if (!count($records)) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			return false;
		}

		// nest records with multiple rooms booked inside sub-array
		$bookings = [];
		foreach ($records as $v) {
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = [];
			}

			// calculate the from_ts and to_ts values for later comparison
			$in_info = getdate($v['checkin']);
			$out_info = getdate($v['checkout']);
			$v['from_ts'] = mktime(0, 0, 0, $in_info['mon'], $in_info['mday'], $in_info['year']);
			$v['to_ts'] = mktime(23, 59, 59, $out_info['mon'], ($out_info['mday'] - 1), $out_info['year']);

			if ($v['status'] == 'cancelled' && count($bookings[$v['id']])) {
				// one room is sufficient for a cancelled booking with cancellation fees
				continue;
			}

			// push nested room-booking
			array_push($bookings[$v['id']], $v);
		}

		// define the columns of the report
		$this->cols = array(
			//date
			array(
				'key' => 'rplan',
				'sortable' => 1,
				'label' => JText::translate('VBOROVWSELRPLAN')
			),
			//rooms sold
			array(
				'key' => 'rooms_sold',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUERSOLD')
			),
			//total bookings
			array(
				'key' => 'tot_bookings',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUETOTB')
			),
			//IBE revenue
			array(
				'key' => 'ibe_revenue',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEREVWEB')
			),
			//OTAs revenue
			array(
				'key' => 'ota_revenue',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEREVOTA')
			),
			//ADR
			array(
				'key' => 'adr',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEADR'),
				'tip' => JText::translate('VBOREPORTREVENUEADRHELP')
			),
			//RevPAR
			array(
				'key' => 'revpar',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEREVPAR'),
				'tip' => JText::translate('VBOREPORTREVENUEREVPARH')
			),
			//Taxes
			array(
				'key' => 'taxes',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUETAX')
			),
			// Commissions
			array(
				'key' => 'cmms',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBTOTALCOMMISSIONS')
			),
			// Cancellation fees
			array(
				'key' => 'canc_fees',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBO_CANC_FEE')
			),
			//Revenue
			array(
				'key' => 'revenue',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEREV'),
				'tip' => JText::translate('VBO_DATA_CALCULATION_HELP'),
				'tip_pos' => 'left',
			)
		);

		$total_rooms_units = $this->countRooms($pidroom);

		// loop over the bookings to build the data for each rate plan involved
		$from_info = getdate($from_ts);
		$to_info = getdate($to_ts);
		$rplans_pool = [];
		$website_rplans_map = [];
		foreach ($bookings as $gbook) {
			// count nights affected
			$nights_affected = $gbook[0]['days'];
			if (!$pfull_stay_count && ($gbook[0]['from_ts'] < $from_info[0] || $gbook[0]['to_ts'] > $to_info[0])) {
				/**
				 * We count the average revenue per nights "stayed" only if the filter "full_stay_count" is off.
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				$nights_affected = $this->countNightsInvolved($gbook[0]['from_ts'], $gbook[0]['to_ts'], $from_info[0], $to_info[0], $gbook[0]['days']);
			}

			// parse rooms booked
			foreach ($gbook as $rk => $book) {
				// identify rate plan ("unknown" by default)
				$rplan_identifier = JText::translate('VBOREPORTTOPCUNKNC');
				if (!empty($book['pkg_id']) && !empty($book['pkg_name'])) {
					// package
					$rplan_identifier = $book['pkg_name'];
				} elseif (!empty($book['cust_cost'])) {
					// website custom cost
					$rplan_identifier = JText::translate('VBOROOMCUSTRATEPLAN');
					if (!empty($book['otarplan'])) {
						// OTA Rate Plan
						$rplan_identifier = ucwords($book['otarplan']);
						// prepend the name of the channel
						if (!empty($book['channel']) && !empty($book['idorderota'])) {
							$channel_parts = explode('_', $book['channel']);
							$channel_name = count($channel_parts) > 1 ? trim($channel_parts[1]) : trim($channel_parts[0]);
							$rplan_identifier = $channel_name . ' - ' . $rplan_identifier;
						}
					}
				} elseif (!empty($book['idprice'])) {
					// website rate plan
					$website_rplan_name = isset($website_rplans_map[$book['idprice']]) ? $website_rplans_map[$book['idprice']] : VikBooking::getPriceName($book['idprice']);
					if (!empty($website_rplan_name)) {
						$rplan_identifier = $website_rplan_name;
						// cache rate plan ID/name to not make any other queries for the next loops
						$website_rplans_map[$book['idprice']] = $website_rplan_name;
					}
				}

				// collect information
				if (!isset($rplans_pool[$rplan_identifier])) {
					$rplans_pool[$rplan_identifier] = [
						'rooms_sold' 	=> 0,
						'tot_bookings' 	=> 0,
						'ibe_revenue' 	=> 0,
						'ota_revenue' 	=> 0,
						'taxes' 		=> 0,
						'cmms' 			=> 0,
						'canc_fees' 	=> 0,
						'revenue' 		=> 0,
					];
				}

				// immediately check for cancellation fees
				if ($book['status'] == 'cancelled') {
					// increase value
					$rplans_pool[$rplan_identifier]['canc_fees'] += $book['canc_fee'];

					// do not proceed to increase anything else
					continue;
				}

				// increase rooms sold
				$rplans_pool[$rplan_identifier]['rooms_sold']++;

				// increase tot bookings, IBE/OTA revenue, taxes and commissions just once per booking
				if ($rk < 1) {
					$rplans_pool[$rplan_identifier]['tot_bookings']++;
					
					// calculate net revenue and taxes for the affected nights of stay
					$tot_net = $book['total'] - (float)$book['tot_taxes'] - (float)$book['tot_city_taxes'] - (float)$book['tot_fees'] - (float)$book['cmms'];
					$tot_net = ($tot_net / (int)$book['days']) * $nights_affected;
					$rplans_pool[$rplan_identifier]['revenue'] += $tot_net;
					if (!empty($book['idorderota']) && !empty($book['channel'])) {
						$rplans_pool[$rplan_identifier]['ota_revenue'] += $tot_net;
					} else {
						$rplans_pool[$rplan_identifier]['ibe_revenue'] += $tot_net;
					}
					$rplans_pool[$rplan_identifier]['taxes'] += (((float)$book['tot_taxes'] + (float)$book['tot_city_taxes'] + (float)$book['tot_fees']) / (int)$book['days']) * $nights_affected;
					$rplans_pool[$rplan_identifier]['cmms'] += ((float)$book['cmms'] / (int)$book['days']) * $nights_affected;
				}
			}
		}

		// parse all rate plans found to build the rows
		foreach ($rplans_pool as $rplan_name => $rplan_stats) {
			// push fields in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'rplan',
					'value' => $rplan_name,
				),
				array(
					'key' => 'rooms_sold',
					'attr' => array(
						'class="center"'
					),
					'value' => $rplan_stats['rooms_sold'],
				),
				array(
					'key' => 'tot_bookings',
					'attr' => array(
						'class="center"'
					),
					'value' => $rplan_stats['tot_bookings'],
				),
				array(
					'key' => 'ibe_revenue',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $rplan_stats['ibe_revenue'],
				),
				array(
					'key' => 'ota_revenue',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $rplan_stats['ota_revenue'],
				),
				array(
					'key' => 'adr',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => ($rplan_stats['rooms_sold'] > 0 ? ($rplan_stats['revenue'] / $rplan_stats['rooms_sold']) : 0),
				),
				array(
					'key' => 'revpar',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => ($rplan_stats['revenue'] / $total_rooms_units),
				),
				array(
					'key' => 'taxes',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $rplan_stats['taxes'],
				),
				array(
					'key' => 'cmms',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $rplan_stats['cmms'],
				),
				array(
					'key' => 'canc_fees',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $rplan_stats['canc_fees'],
				),
				array(
					'key' => 'revenue',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => ($rplan_stats['revenue'] + $rplan_stats['canc_fees']),
				)
			));
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// loop over the rows to build the footer row with the totals
		$foot_rooms_sold = 0;
		$foot_tot_bookings = 0;
		$foot_ibe_revenue = 0;
		$foot_ota_revenue = 0;
		$foot_taxes = 0;
		$foot_cmms = 0;
		$foot_canc_fees = 0;
		$foot_revenue = 0;

		foreach ($this->rows as $row) {
			$foot_rooms_sold 	+= $row[1]['value'];
			$foot_tot_bookings 	+= $row[2]['value'];
			$foot_ibe_revenue 	+= $row[3]['value'];
			$foot_ota_revenue 	+= $row[4]['value'];
			$foot_taxes 		+= $row[7]['value'];
			$foot_cmms 			+= $row[8]['value'];
			$foot_canc_fees		+= $row[9]['value'];
			$foot_revenue 		+= $row[10]['value'];
		}

		array_push($this->footerRow, array(
			array(
				'attr' => array(
					'class="vbo-report-total"'
				),
				'value' => '<h3>'.JText::translate('VBOREPORTSTOTALROW').'</h3>',
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_rooms_sold,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_tot_bookings,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb . ' ' . VikBooking::numberFormat($val);
				},
				'value' => $foot_ibe_revenue,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb . ' ' . VikBooking::numberFormat($val);
				},
				'value' => $foot_ota_revenue,
			),
			array(
				'value' => '',
			),
			array(
				'value' => '',
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb . ' ' . VikBooking::numberFormat($val);
				},
				'value' => $foot_taxes,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb . ' ' . VikBooking::numberFormat($val);
				},
				'value' => $foot_cmms,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb . ' ' . VikBooking::numberFormat($val);
				},
				'value' => $foot_canc_fees,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb . ' ' . VikBooking::numberFormat($val);
				},
				'value' => $foot_revenue,
			)
		));

		// Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
			$this->setWarning('$total_rooms_units = '.$total_rooms_units.'<br/>');
			$this->setWarning('$bookings:<pre>'.print_r($bookings, true).'</pre><br/>');
		}

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

		$report_extraname = '';
		$pchannel = VikRequest::getString('channel', '', 'request');
		if (strlen($pchannel)) {
			// set channel name for exported file
			if ($pchannel == '-1') {
				$report_extraname = JText::translate('VBORDFROMSITE');
			} else {
				$report_extraname = $pchannel;
			}
		}

		$this->setExportCSVFileName($this->reportName . (!empty($report_extraname) ? '-' . $report_extraname : '') . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.csv');
	}

	/**
	 * Private method that counts the number of nights involved for a reservation given some filter dates.
	 * 
	 * @param 	int 	$bfrom_ts 	booking check-in timestamp.
	 * @param 	int 	$bto_ts 	booking check-out timestamp.
	 * @param 	int 	$fstart_ts 	filter start timestamp.
	 * @param 	int 	$fend_ts 	filter end timestamp.
	 * @param 	int 	$nights 	booking total nights.
	 *
	 * @return 	int 	number of nights involved.
	 */
	private function countNightsInvolved($bfrom_ts, $bto_ts, $fstart_ts, $fend_ts, $nights)
	{
		$nights = (int)$nights;
		$nights = $nights > 0 ? $nights : 1;

		if (empty($bfrom_ts) || empty($bto_ts) || empty($fstart_ts) || empty($fend_ts)) {
			// rollback to number of nights
			return $nights;
		}

		$affected = 0;
		if ($fend_ts < $fstart_ts) {
			return $affected;
		}

		$from_info = getdate($fstart_ts);
		while ($from_info[0] <= $fend_ts) {
			if ($from_info[0] >= $bfrom_ts && $from_info[0] <= $bto_ts) {
				$affected++;
			}
			if ($from_info[0] > $bto_ts) {
				// useless to continue
				break;
			}
			// go to next day
			$from_info = getdate(mktime(0, 0, 0, $from_info['mon'], ($from_info['mday'] + 1), $from_info['year']));
		}

		return $affected;
	}
}
