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
 * Rates Flow child Class of VikBookingReport
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VikBookingReportRatesFlow extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 * 
	 * @var 	string
	 */
	public $defaultKeySort = 'created_on';

	/**
	 * Property 'defaultKeyOrder' is used by the View that renders the report.
	 * 
	 * @var 	string
	 */
	public $defaultKeyOrder = 'DESC';

	/**
	 * Property 'exportAllowed' is used by the View to display the export button.
	 * 
	 * @var 	int
	 */
	public $exportAllowed = 1;

	/**
	 * Debug mode is activated by passing the value 'e4j_debug' > 0
	 * 
	 * @var 	bool
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
			// do not run this method twice, as it could load JS and CSS files.
			return $this->reportFilters;
		}

		// get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		// date format
		$df = $this->getDateFormat();

		// load the jQuery UI Datepicker
		$this->loadDatePicker();

		/**
		 * Get the rates flow handler from VCM, which is mandatory for this report.
		 * This will also load the VCM dependencies in case of success.
		 */
		$rflow_handler = VikBooking::getRatesFlowInstance();
		if (!$rflow_handler) {
			// VCM is not installed or is outdated: do not proceed and set an error
			$this->setError(JText::translate('VBCONFIGVCMAUTOUPDMISS'));
			return $this->reportFilters;
		}

		// from Date Filter
		$filter_opt = array(
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTSDATEFROM').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />',
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		// to Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
			'type' => 'calendar',
			'name' => 'todate'
		);
		array_push($this->reportFilters, $filter_opt);

		// date type filter
		$pdt_type = VikRequest::getString('dt_type', 'night', 'request');
		$dt_types = array(
			'night'    => JText::translate('VBDAY'),
			'creation' => JText::translate('VBOINVCREATIONDATE'),
		);
		$dt_type_sel_html = $vbo_app->getNiceSelect($dt_types, $pdt_type, 'dt_type', '', '', '', '', 'dt_type');
		$filter_opt = array(
			'label' => '<label for="dt_type">' . JText::translate('VBODASHSEARCHKEYS') . '</label>',
			'html' => $dt_type_sel_html,
			'type' => 'select',
			'name' => 'dt_type'
		);
		array_push($this->reportFilters, $filter_opt);


		// room ID filter
		$pidroom = VikRequest::getInt('idroom', 0, 'request');
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

		// rate plan id filter
		$pidprice = VikRequest::getInt('idprice', 0, 'request');
		$all_prices = $this->getRatePlans();
		$prices = array();
		foreach ($all_prices as $price) {
			$prices[$price['id']] = $price['name'];
		}
		if (count($prices)) {
			$prices_sel_html = $vbo_app->getNiceSelect($prices, $pidprice, 'idprice', JText::translate('VBAFFANYPRICE'), JText::translate('VBAFFANYPRICE'), '', '', 'idprice');
			$filter_opt = array(
				'label' => '<label for="idprice">'.JText::translate('VBOROVWSELRPLAN').'</label>',
				'html' => $prices_sel_html,
				'type' => 'select',
				'name' => 'idprice'
			);
			array_push($this->reportFilters, $filter_opt);
		}

		// channel filter
		$all_channels = array();
		// push website channel identifier (-1) as the first option
		$all_channels['-1'] = JText::translate('VBORDFROMSITE');
		try {
			$all_av_channels = VikChannelManager::getAllAvChannels();
			foreach ($all_av_channels as $ch_key => $ch_name) {
				// push VCM channel
				$all_channels[$ch_key] = $this->sayChannelName($ch_key, $all_av_channels);
			}
		} catch (Exception $e) {
			// do nothing
		}
		$pchannel = VikRequest::getString('channel', '', 'request');
		// push filter
		$channels_sel_html = $vbo_app->getNiceSelect($all_channels, $pchannel, 'channel', '- - - -', '- - - -', '', '', 'channel');
		$filter_opt = array(
			'label' => '<label for="channel">'.JText::translate('VBCHANNELFILTER').'</label>',
			'html' => $channels_sel_html,
			'type' => 'select',
			'name' => 'channel'
		);
		array_push($this->reportFilters, $filter_opt);

		// get minimum and maximum nights updated for dates filters
		list($mindate, $maxdate) = $this->getMinDatesRatesFlow();

		// jQuery code for the datepicker calendars and select2
		$now = time();
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		// try to build the default dates
		if (!empty($pfromdate) && empty($ptodate)) {
			$ptodate = $pfromdate;
		} elseif (empty($pfromdate) && !empty($ptodate)) {
			$pfromdate = $ptodate;
		} elseif (empty($pfromdate) && empty($ptodate) && !empty($mindate)) {
			// filter dates are empty
			if ($now < $maxdate) {
				// populate default filter dates to today and one month ahead
				$pfromdate = date($df);
				$next_mon_ts = mktime(0, 0, 0, (date("n") + 1), date("j"), date("Y"));
				$next_mon_ts = $next_mon_ts > $maxdate ? $maxdate : $next_mon_ts;
				$ptodate = date($df, $next_mon_ts);
			}
		}

		$js = 'jQuery(document).ready(function() {
			jQuery(".vbo-report-datepicker:input").datepicker({
				'.(!empty($mindate) ? 'minDate: "'.date($df, $mindate).'", ' : '').'
				'.(!empty($maxdate) ? 'maxDate: "'.date($df, $maxdate).'", ' : '').'
				'.(!empty($mindate) && !empty($maxdate) ? 'yearRange: "'.(date('Y', $mindate)).':'.date('Y', $maxdate).'", changeMonth: true, changeYear: true, ' : '').'
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
			// export functions may set errors rather than exiting the process, and the View may continue the execution to attempt to render the report.
			return false;
		}

		/**
		 * Get the rates flow handler from VCM, which is mandatory for this report.
		 * This will also load the VCM dependencies in case of success.
		 */
		$rflow_handler = VikBooking::getRatesFlowInstance();
		if (!$rflow_handler) {
			// VCM is not installed or is outdated: do not proceed and set an error
			$this->setError(JText::translate('VBCONFIGVCMAUTOUPDMISS'));
			return false;
		}

		// load all AV-enabled channels from VCM
		$all_av_channels = VikChannelManager::getAllAvChannels();

		/**
		 * This report makes use of the options that could be injected by those who
		 * invoke this report. Rather than injecting request vars, this report supports
		 * custom options to change the behavior of the report data calculated.
		 */
		$options = $this->getReportOptions();

		// injected options will replace request variables, if any
		$opt_fromdate = $options->get('fromdate', '');
		$opt_todate   = $options->get('todate', '');
		$opt_dt_type  = $options->get('dt_type', '');
		$opt_prices   = $options->get('idprice');
		$opt_rooms 	  = $options->get('idroom');
		$opt_channel  = $options->get('channel', 0);
		$opt_sort 	  = $options->get('krsort');
		$opt_order 	  = $options->get('krorder');

		// input (request) vars
		$pfromdate = !empty($opt_fromdate) ? $opt_fromdate : VikRequest::getString('fromdate', '', 'request');
		$ptodate   = !empty($opt_todate) ? $opt_todate : VikRequest::getString('todate', '', 'request');
		$dt_type   = !empty($opt_dt_type) ? $opt_dt_type : VikRequest::getString('dt_type', 'night', 'request');

		// adjust dates, if necessary
		if (!empty($pfromdate) && empty($ptodate)) {
			$ptodate = $pfromdate;
		} elseif (empty($pfromdate) && !empty($ptodate)) {
			$pfromdate = $ptodate;
		}

		// idroom can be an array of IDs or just one ID as int/string
		$pidroom = VikRequest::getVar('idroom', null, 'request');
		$pidroom = empty($pidroom) && !empty($opt_rooms) ? $opt_rooms : $pidroom;
		// idprice can be an array of IDs or just one ID as int/string
		$pidprice = VikRequest::getVar('idprice', null, 'request');
		$pidprice = empty($pidprice) && !empty($opt_prices) ? $opt_prices : $pidprice;
		// channel filter is an integer, can be signed (-1 = website), and it's taken from VCM
		$pchannel = !empty($opt_channel) ? $opt_channel : VikRequest::getInt('channel', 0, 'request');

		// sorting and ordering
		$pkrsort  = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort  = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrsort  = !empty($opt_sort) ? $opt_sort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = !empty($opt_order) ? $opt_order : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';

		// currency symbol and date params
		$currency_symb = VikBooking::getCurrencySymb();
		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();

		// get dates timestamps and SQL datetime strings
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			// filtering by dates is mandatory
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}
		$from_sql_date = date('Y-m-d', $from_ts);
		$to_sql_date = date('Y-m-d', $to_ts);

		// months map
		$months_map = array(
			JText::translate('VBMONTHONE'),
			JText::translate('VBMONTHTWO'),
			JText::translate('VBMONTHTHREE'),
			JText::translate('VBMONTHFOUR'),
			JText::translate('VBMONTHFIVE'),
			JText::translate('VBMONTHSIX'),
			JText::translate('VBMONTHSEVEN'),
			JText::translate('VBMONTHEIGHT'),
			JText::translate('VBMONTHNINE'),
			JText::translate('VBMONTHTEN'),
			JText::translate('VBMONTHELEVEN'),
			JText::translate('VBMONTHTWELVE'),
		);

		// query to obtain the records
		$records = array();
		$clauses = array();
		// date type and date filters
		if ($dt_type == 'night') {
			// filter nights updated
			$sub_clauses = array();
			// build sub-clauses
			$sub_clause_one = array();
			array_push($sub_clause_one, "`rf`.`day_from` <= " . $this->dbo->quote($from_sql_date));
			array_push($sub_clause_one, "`rf`.`day_from` <= " . $this->dbo->quote($to_sql_date));
			array_push($sub_clause_one, "`rf`.`day_to` >= " . $this->dbo->quote($from_sql_date));
			array_push($sub_clause_one, "`rf`.`day_to` >= " . $this->dbo->quote($to_sql_date));
			$sub_clause_two = array();
			array_push($sub_clause_two, "`rf`.`day_from` >= " . $this->dbo->quote($from_sql_date));
			array_push($sub_clause_two, "`rf`.`day_from` <= " . $this->dbo->quote($to_sql_date));
			array_push($sub_clause_two, "`rf`.`day_to` >= " . $this->dbo->quote($from_sql_date));
			array_push($sub_clause_two, "`rf`.`day_to` <= " . $this->dbo->quote($to_sql_date));
			$sub_clause_three = array();
			array_push($sub_clause_three, "`rf`.`day_from` >= " . $this->dbo->quote($from_sql_date));
			array_push($sub_clause_three, "`rf`.`day_from` <= " . $this->dbo->quote($to_sql_date));
			array_push($sub_clause_three, "`rf`.`day_to` >= " . $this->dbo->quote($from_sql_date));
			array_push($sub_clause_three, "`rf`.`day_to` >= " . $this->dbo->quote($to_sql_date));
			$sub_clause_four = array();
			array_push($sub_clause_four, "`rf`.`day_from` <= " . $this->dbo->quote($from_sql_date));
			array_push($sub_clause_four, "`rf`.`day_from` <= " . $this->dbo->quote($to_sql_date));
			array_push($sub_clause_four, "`rf`.`day_to` >= " . $this->dbo->quote($from_sql_date));
			array_push($sub_clause_four, "`rf`.`day_to` <= " . $this->dbo->quote($to_sql_date));
			// push all sub-clauses
			array_push($sub_clauses, "(" . implode(' AND ', $sub_clause_one) . ")");
			array_push($sub_clauses, "(" . implode(' AND ', $sub_clause_two) . ")");
			array_push($sub_clauses, "(" . implode(' AND ', $sub_clause_three) . ")");
			array_push($sub_clauses, "(" . implode(' AND ', $sub_clause_four) . ")");
			// push full clause
			array_push($clauses, "(" . implode(' OR ', $sub_clauses) . ")");
		} else {
			// filter dates for creation date
			array_push($clauses, "`rf`.`created_on` >= " . $this->dbo->quote($from_sql_date));
			array_push($clauses, "`rf`.`created_on` <= " . $this->dbo->quote($to_sql_date));
		}
		// room ID or room IDs
		if (!empty($pidroom) && !is_array($pidroom)) {
			array_push($clauses, "`rf`.`vbo_room_id` = " . (int)$pidroom);
		} elseif (is_array($pidroom) && count($pidroom)) {
			array_push($clauses, "`rf`.`vbo_room_id` IN (" . implode(', ', $pidroom) . ")");
		}
		// rate plan ID or rate plan IDs
		if (!empty($pidprice) && !is_array($pidprice)) {
			array_push($clauses, "`rf`.`vbo_price_id` = " . (int)$pidprice);
		} elseif (is_array($pidprice) && count($pidprice)) {
			array_push($clauses, "`rf`.`vbo_price_id` IN (" . implode(', ', $pidprice) . ")");
		}
		// channel filter
		if (!empty($pchannel)) {
			array_push($clauses, "`rf`.`channel_id` = " . $pchannel);
		}
		// additional filters set through custom options
		$fetch_alterations = (!strcasecmp($options->get('fetch', ''), 'alterations'));
		if ($fetch_alterations) {
			// exclude rates flow records generated by the Bulk Actions in VCM (i.e. rates flow admin widget)
			array_push($clauses, "`rf`.`created_by` != " . $this->dbo->quote('channelsRatesPush'));
		}
		// query limits
		$limfirst	= $options->get('lim', 0);
		$limstart 	= $options->get('limstart', 0);
		$lim 		= $limfirst;
		$found_rows = '';
		$tot_rows 	= 0;
		$t_records  = 0;
		$multiplim 	= 1;
		if ($lim > 0 && $fetch_alterations) {
			/**
			 * We need to multiply the limit by the number of channels to fetch, so
			 * that the results will include all rate modifications for any channel.
			 */
			$multiplim = $this->countChannels();
			$lim *= $multiplim;
			$found_rows = "SQL_CALC_FOUND_ROWS ";
		}

		// query the database (do not change the default ordering columns!)
		$q = "SELECT {$found_rows}`rf`.*, `r`.`name` AS `room_name`, `p`.`name` AS `rplan_name` " .
			"FROM `#__vikchannelmanager_rates_flow` AS `rf` " .
			"LEFT JOIN `#__vikbooking_rooms` AS `r` ON `r`.`id`=`rf`.`vbo_room_id` " .
			"LEFT JOIN `#__vikbooking_prices` AS `p` ON `p`.`id`=`rf`.`vbo_price_id` " .
			"WHERE " . implode(' AND ', $clauses) . " " .
			"ORDER BY `rf`.`created_on` ASC, `rf`.`channel_id` ASC";
		$this->dbo->setQuery($q, $limstart, $lim);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$records = $this->dbo->loadAssocList();
			if (!empty($found_rows)) {
				// grab total rows count without limits for pagination
				$this->dbo->setQuery('SELECT FOUND_ROWS();');
				$tot_rows = $this->dbo->loadResult();
			}
		}

		// count total records fetched from query before any manipulation
		$t_records = count($records);

		// define the columns of the report
		$this->cols = array(
			// creation date
			array(
				'key' => 'created_on',
				'sortable' => 1,
				'label' => JText::translate('VBOINVCREATIONDATE'),
			),
			// channel
			array(
				'key' => 'channel_id',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOCHANNEL'),
			),
			// from night (date) updated
			array(
				'key' => 'day_from',
				'sortable' => 1,
				'label' => JText::translate('VBNEWRESTRICTIONDFROMRANGE'),
			),
			// to night (date) updated
			array(
				'key' => 'day_to',
				'sortable' => 1,
				'label' => JText::translate('VBNEWRESTRICTIONDTORANGE'),
			),
			// VBO room id
			array(
				'key' => 'vbo_room_id',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBNEWROOMFIVE'),
			),
			// VBO price id
			array(
				'key' => 'vbo_price_id',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOROVWSELRPLAN'),
			),
			// base price per night
			array(
				'key' => 'base_fee',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBO_BASE_RATE'),
			),
			// price per night set
			array(
				'key' => 'nightly_fee',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBNEWOPTFIVE'),
			),
			// channel alteration
			array(
				'key' => 'channel_alter',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBNEWSEASONSIX'),
			),
			// created by (through)
			array(
				'key' => 'created_by',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBCSVCREATEDBY'),
			),
			// extra data
			array(
				'key' => 'data',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPSHOWPAYMENTSTHREE'),
			),
		);

		// check if paging should be added or if records should be adjusted
		if (!empty($found_rows) && $fetch_alterations) {
			// adjust records according to limit multiplied by number of AV channels
			$records_intvals_keys = array();
			$consequent_key = -1;
			$unexpected_records = 0;
			foreach ($records as $k => $record) {
				if ($consequent_key >= $k) {
					continue;
				}
				$consequent_key = $k;
				for ($i = 1; $i < $multiplim; $i++) {
					$check_key = ($k + $i);
					if (!isset($records[$check_key])) {
						break;
					}
					if ($record['day_from'] == $records[$check_key]['day_from'] && $record['day_to'] == $records[$check_key]['day_to']) {
						// expected record found
						$consequent_key = $check_key;
						continue;
					}
					// unexpected record found according to limit multiplied by number of AV channels
					$unexpected_records++;
				}
				// push interval of consequent keys
				array_push($records_intvals_keys, array($k, $consequent_key));
			}

			// check if the offset for the next request needs to be adjusted
			$offset_removed = 0;
			if (count($records_intvals_keys) > $limfirst) {
				// let's split up the records found to respect the limit requested
				$max_key = $records_intvals_keys[($limfirst - 1)][1];
				foreach ($records as $k => $v) {
					if ($k > $max_key) {
						// remove this record that would exceed the limit requested
						unset($records[$k]);
						// increase the offset for removed records
						$offset_removed++;
					}
				}
			}

			// check if there is a next page and calculate next limit and offset
			$has_next_page = false;
			$page_number = 1;
			$next_lim = null;
			$next_offset = null;
			if ($lim > 0 && $tot_rows > 0 && $t_records >= $limfirst) {
				// limit requested satisfied, so we may have a next page
				$has_next_page = (($limstart + $t_records - $offset_removed) < $tot_rows);
				if ($has_next_page) {
					// calculate the actual next offset
					$next_offset = $limstart + $t_records - $offset_removed;
					// keep the original limit
					$next_lim = $limfirst;
				}
			}
			// count (approx) current page number
			if ($lim > 0 && $tot_rows > 0 && $limstart > 0) {
				// we must be at a page after the #1
				$page_number = floor($tot_rows / $limstart);
				$page_number = $page_number < 2 ? 2 : $page_number;
			}

			// add paging details as a special column (if fetch "alterations")
			array_push($this->cols, array(
				'key' 			=> 'paging',
				'has_next_page' => (int)$has_next_page,
				'page_num' 		=> (int)$page_number,
				'lim' 			=> $next_lim,
				'limstart' 		=> $next_offset,
				'rm_offset' 	=> $offset_removed,
			));
		}

		// loop over the records to build the rows
		foreach ($records as $record) {
			// get rates flow record object
			$rflow_record = $rflow_handler->getRecord($record);

			$created_on = $rflow_record->getCreatedOn();
			list($day_from, $day_to) = $rflow_record->getDates();

			$ts_created   = strtotime($created_on);
			$info_created = getdate($ts_created);
			$wday_created = $this->getWdayString($info_created['wday'], 'short');
			$mon_created  = $months_map[($info_created['mon'] - 1)];

			$say_channel_name = $this->sayChannelName($rflow_record->getChannelID(), $all_av_channels);
			$vbo_room_name = $record['room_name'];
			$vbo_rplan_name = $record['rplan_name'];
			$vbo_rplan_id = $rflow_record->getVBORatePlanID();
			$channel_alteration_str = $rflow_record->getChannelAlteration();
			$channel_alteration_num = !empty($channel_alteration_str) ? (float)preg_replace("/[^0-9.,-]/", '', $channel_alteration_str) : $channel_alteration_str;
			$say_created_by = $this->sayCreatedBy($rflow_record->getCreatedBy());
			$decoded_data = $rflow_record->getExtraData();

			// attempt to get the channel logo, if any
			$channel_raw_name = $this->getRawChannelName($rflow_record->getChannelID(), $all_av_channels);
			$channel_logo = $this->getChannelLogoURI($channel_raw_name);
			
			// push fields in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'created_on',
					'callback' => function($val) use ($df, $datesep, $wday_created, $mon_created, $ts_created) {
						return $wday_created . ', ' . date('j', $ts_created) . ' ' . $mon_created . ' ' . date('Y', $ts_created) . ' ' . date('H:i', $ts_created);
					},
					'value' => $created_on,
				),
				array(
					'key' => 'channel_id',
					'callback' => function($val) use ($say_channel_name) {
						return empty($val) ? '' : $say_channel_name;
					},
					'attr' => array(
						'class="center"'
					),
					'value' => $rflow_record->getChannelID(),
					// set a special (reserved) key for the channel logo
					'_logo' => $channel_logo,
				),
				array(
					'key' => 'day_from',
					'callback' => function($val) use ($df, $datesep) {
						return date(str_replace("/", $datesep, $df), strtotime($val));
					},
					'value' => $day_from,
				),
				array(
					'key' => 'day_to',
					'callback' => function($val) use ($df, $datesep) {
						return date(str_replace("/", $datesep, $df), strtotime($val));
					},
					'value' => $day_to,
				),
				array(
					'key' => 'vbo_room_id',
					'callback' => function($val) use ($vbo_room_name) {
						return empty($vbo_room_name) ? $val : $vbo_room_name;
					},
					'attr' => array(
						'class="center"'
					),
					'title' => $rflow_record->getOTARoomID(),
					'value' => $rflow_record->getVBORoomID(),
				),
				array(
					'key' => 'vbo_price_id',
					'callback' => function($val) use ($vbo_rplan_name) {
						return empty($vbo_rplan_name) ? $val : $vbo_rplan_name;
					},
					'attr' => array(
						'class="center"'
					),
					'value' => $vbo_rplan_id,
				),
				array(
					'key' => 'base_fee',
					'attr' => array(
						'class="center vbo-report-col-hideable"'
					),
					'callback' => function($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $rflow_record->getBaseFee(),
				),
				array(
					'key' => 'nightly_fee',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $rflow_record->getNightlyFee(),
				),
				array(
					'key' => 'channel_alter',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) use ($channel_alteration_str) {
						return !empty($channel_alteration_str) ? $channel_alteration_str : '';
					},
					'value' => $channel_alteration_num,
				),
				array(
					'key' => 'created_by',
					'callback' => function($val) use ($decoded_data) {
						$uname = '';
						if (is_object($decoded_data) && !empty($decoded_data->User)) {
							$uname = ' (' . $decoded_data->User . ')';
						}
						return empty($val) ? trim($uname) : $val . $uname;
					},
					'attr' => array(
						'class="center"'
					),
					'value' => $say_created_by,
				),
				array(
					'key' => 'data',
					'attr' => array(
						'class="center vbo-report-col-hideable"'
					),
					'callback' => function($val) use ($vbo_rplan_id) {
						if (!is_object($val)) {
							return '';
						}
						$data_parts = array();
						if (isset($val->RatePlan)) {
							$ota_rplan_name = !empty($val->RatePlan->name) ? $val->RatePlan->name : '';
							$ota_rplan_name .= !empty($val->RatePlan->id) && $val->RatePlan->id != '-1' && $val->RatePlan->id != $vbo_rplan_id ? (' (' . $val->RatePlan->id . ')') : '';
							array_push($data_parts, $ota_rplan_name);
						}
						if (isset($val->RatesLOS)) {
							array_push($data_parts, 'LOS Model');
						}
						if (isset($val->Restrictions)) {
							if (isset($val->Restrictions->minLOS)) {
								array_push($data_parts, 'Min LOS ' . $val->Restrictions->minLOS);
							}
							if (isset($val->Restrictions->cta)) {
								if ((is_bool($val->Restrictions->cta) && $val->Restrictions->cta === true) || (is_string($val->Restrictions->cta) && !strcasecmp($val->Restrictions->cta, 'true'))) {
									array_push($data_parts, 'CTA');
								}
							}
							if (isset($val->Restrictions->ctd)) {
								if ((is_bool($val->Restrictions->ctd) && $val->Restrictions->ctd === true) || (is_string($val->Restrictions->ctd) && !strcasecmp($val->Restrictions->ctd, 'true'))) {
									array_push($data_parts, 'CTD');
								}
							}
						}
						return implode(', ', $data_parts);
					},
					'value' => $decoded_data,
				),
			));

			if (!empty($found_rows) && $fetch_alterations) {
				// unshift the row just pushed and prepend the ID of the record just added
				$rows_last_key = count($this->rows) - 1;
				array_unshift($this->rows[$rows_last_key], array(
					'key' 	=> 'id',
					'value' => $record['id'],
				));
			}
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// update sorting and ordering key
		$this->defaultKeySort  = $pkrsort;
		$this->defaultKeyOrder = $pkrorder;

		return true;
	}

	/**
	 * Returns an array with the minimum and maximum dates updated.
	 * We keep the visibility as public so that who invokes this class can use it.
	 * 
	 * @return 	array 	to be used with list() to get the min/max date timestamps.
	 */
	public function getMinDatesRatesFlow()
	{
		$mindate = null;
		$maxdate = null;

		$rflow_handler = VikBooking::getRatesFlowInstance();
		if (!$rflow_handler) {
			// make sure VCM is installed, or the query below will raise an error
			return array($mindate, $maxdate);
		}

		$q = "SELECT MIN(`day_from`) AS `mindate`, MAX(`day_to`) AS `maxdate`, MIN(`created_on`) AS `mincreatedate` FROM `#__vikchannelmanager_rates_flow`;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();
		if ($this->dbo->getNumRows()) {
			$data = $this->dbo->loadAssoc();
			if (!empty($data['mindate']) && !empty($data['maxdate'])) {
				$mindate = strtotime($data['mindate']);
				$maxdate = strtotime($data['maxdate']);
				$mincreatedate = strtotime($data['mincreatedate']);
				if ($mincreatedate < $mindate) {
					$mindate = $mincreatedate;
				}
			}
		}

		return array($mindate, $maxdate);
	}

	/**
	 * Returns the total number of unique channel identifiers updated at least once.
	 * We keep the visibility as public so that who invokes this class can use it.
	 * 
	 * @return 	int 	total number of unique channels updated at least once.
	 */
	public function countRatesFlowChannels()
	{
		$rflow_handler = VikBooking::getRatesFlowInstance();
		if (!$rflow_handler) {
			// make sure VCM is installed, or the query below will raise an error
			return 0;
		}

		$q = "SELECT DISTINCT `channel_id` FROM `#__vikchannelmanager_rates_flow`;";
		$this->dbo->setQuery($q);
		$this->dbo->execute();

		return (int)$this->dbo->getNumRows();
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
		$pchannel = VikRequest::getInt('channel', 0, 'request');
		if (!empty($pchannel)) {
			// set channel name for exported file
			$report_extraname = $this->sayChannelName($pchannel);
		}

		$this->setExportCSVFileName($this->reportName . (!empty($report_extraname) ? '-' . $report_extraname : '') . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.csv');
	}

	/**
	 * Given a channel identifier number, returns a proper name for it.
	 * 
	 * @param 	int 	$ch_key 	 	the channel identifier number.
	 * @param 	array 	$av_channels 	optional list of AV-enabled channels.
	 * 
	 * @return 	string 					the proper channel name.
	 */
	private function sayChannelName($ch_key, $av_channels = array())
	{
		$channel_name = '';

		if ((int)$ch_key == -1) {
			// website
			return JText::translate('VBORDFROMSITE');
		}

		try {
			$all_av_channels = count($av_channels) ? $av_channels : VikChannelManager::getAllAvChannels();
			foreach ($all_av_channels as $ch_id => $ch_name) {
				if ($ch_key != $ch_id) {
					continue;
				}
				$channel_name = $ch_id == VikChannelManagerConfig::GOOGLEHOTEL ? 'Google Hotel' : ucwords($ch_name);
				$channel_name = $ch_id == VikChannelManagerConfig::AIRBNBAPI ? 'Airbnb' : $channel_name;
				$channel_name = defined('VikChannelManagerConfig::VRBOAPI') && $ch_id == VikChannelManagerConfig::VRBOAPI ? 'Vrbo' : $channel_name;
			}
		} catch (Exception $e) {
			// do nothing
		}

		return $channel_name;
	}

	/**
	 * Given a channel identifier number, returns the raw name of it.
	 * 
	 * @param 	int 	$ch_key 	 	the channel identifier number.
	 * @param 	array 	$av_channels 	optional list of AV-enabled channels.
	 * 
	 * @return 	string 					the raw channel name (provenience).
	 */
	private function getRawChannelName($ch_key, $av_channels = array())
	{
		$channel_name = '';

		if ((int)$ch_key == -1) {
			// website
			return JText::translate('VBORDFROMSITE');
		}

		try {
			$all_av_channels = count($av_channels) ? $av_channels : VikChannelManager::getAllAvChannels();
			foreach ($all_av_channels as $ch_id => $ch_name) {
				if ($ch_key == $ch_id) {
					// channel found
					$channel_name = $ch_name;
					break;
				}
			}
		} catch (Exception $e) {
			// do nothing
		}

		return $channel_name;
	}

	/**
	 * Attempts to match a channel name (provenience) to its logo URI.
	 * 
	 * @param 	string 	$ch_name 	the raw channel name.
	 * 
	 * @return 	string 				the channel logo URI or an empty string.
	 */
	private function getChannelLogoURI($ch_name)
	{
		$channel_logo = '';

		if (empty($ch_name)) {
			return $channel_logo;
		}

		try {
			$channel_logo = VikChannelManager::getLogosInstance($ch_name)->getSmallLogoURL();
		} catch (Exception $e) {
			// do nothing
		}

		return $channel_logo;
	}

	/**
	 * Given a created by string identifier, returns a readable name for it.
	 * 
	 * @param 	string 	$created_by		the raw created by string.
	 * 
	 * @return 	string 					the readable created by string.
	 */
	private function sayCreatedBy($created_by)
	{
		if (empty($created_by)) {
			return '';
		}

		if (!strcasecmp($created_by, 'VBO') || !strcasecmp($created_by, 'VikBooking')) {
			// website
			return JText::translate('VBORDFROMSITE');
		}

		if (!strcasecmp($created_by, 'setNewRate') || !strcasecmp($created_by, 'VCM')) {
			// VCM Custom Rates
			return JText::translate('VBMENUCHANNELMANAGER');
		}

		if (!strcasecmp($created_by, 'channelsRatesPush') || !strcasecmp(str_replace(' ', '', $created_by), 'SmartBalancer')) {
			// VCM Bulk Action - Rates Upload
			return JText::translate('VBMENUCHANNELMANAGER');
		}

		if (!strcasecmp($created_by, 'App')) {
			// e4jConnect Mobile App
			return JText::translate('VBO_MOBILE_APP');
		}

		return $created_by;
	}

	/**
	 * Returns the total number of channels supporting updates of rates.
	 * 
	 * @return 	int 	the total number of channels for the rates flow.
	 */
	private function countChannels()
	{
		// we start from 1 to include the website
		$tot_channels = 1;

		try {
			$all_av_channels = VikChannelManager::getAllAvChannels();
			$tot_channels += count($all_av_channels);
		} catch (Exception $e) {
			// do nothing
		}

		return $tot_channels;
	}
}
