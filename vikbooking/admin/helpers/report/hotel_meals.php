<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2023 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Hotel Meals child Class of VikBookingReport.
 * 
 * @since 	1.16.1 (J) - 1.6.1 (WP)
 */
class VikBookingReportHotelMeals extends VikBookingReport
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
		$this->reportName = 'Hotel - ' . JText::translate('VBO_MEALS');
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

		// set up datepicker calendars
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
			// export functions may set errors rather than exiting the process, and the View may continue the execution to attempt to render the report.
			return false;
		}

		// input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';

		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		if (empty($ptodate)) {
			$ptodate = $pfromdate;
		}

		// get date timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts) || $to_ts < $from_ts) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		// query to obtain the records
		$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`roomsnum`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,`o`.`adminnotes`,".
			"`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`pets`,`or`.`idtar`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`roomindex`,`or`.`pkg_name`,`or`.`otarplan`,".
			"`or`.`meals`,`r`.`name`,`r`.`params`,`co`.`idcustomer`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_rooms` AS `r` ON `or`.`idroom`=`r`.`id` ".
			"LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` ".
			"WHERE `o`.`status`='confirmed' AND `o`.`closure`=0 AND `o`.`checkout` >= {$from_ts} AND `o`.`checkin` <= {$to_ts} ".
			"ORDER BY `o`.`checkin` ASC, `o`.`id` ASC;";
		$this->dbo->setQuery($q);
		$records = $this->dbo->loadAssocList();
		if (!$records) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			return false;
		}

		// nest records with multiple rooms booked inside sub-array
		$bookings = array();
		foreach ($records as $v) {
			if (!isset($bookings[$v['id']])) {
				$bookings[$v['id']] = array();
			}

			// calculate the from_ts and to_ts values for later comparison
			$in_info = getdate($v['checkin']);
			$out_info = getdate($v['checkout']);
			$v['from_ts'] = mktime(0, 0, 0, $in_info['mon'], $in_info['mday'], $in_info['year']);
			$v['to_ts'] = mktime(23, 59, 59, $out_info['mon'], $out_info['mday'], $out_info['year']);

			array_push($bookings[$v['id']], $v);
		}

		// define the columns of the report
		$this->cols = array(
			// date
			array(
				'key' => 'day',
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEDAY')
			),
			// arrivals
			array(
				'key' => 'arrivals',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOARRIVING')
			),
			// departures
			array(
				'key' => 'departures',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBODEPARTING')
			),
			// stayover
			array(
				'key' => 'stayover',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOTYPESTAYOVER')
			),
			// breakfast
			array(
				'key' => 'breakfast',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBO_MEAL_BREAKFAST')
			),
			// lunch
			array(
				'key' => 'lunch',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBO_MEAL_LUNCH')
			),
			// dinner
			array(
				'key' => 'dinner',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBO_MEAL_DINNER')
			),
			// website
			array(
				'key' => 'website',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBORDFROMSITE')
			),
			// channels
			array(
				'key' => 'channels',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOCHANNELS'),
				'tip' => JText::translate('VBO_CRON_OTA_RES'),
			),
		);

		// loop over the dates of the report to build the rows
		$from_info = getdate($from_ts);
		$to_info = getdate($to_ts);
		while ($from_info[0] <= $to_info[0]) {
			// prepare default fields for this row
			$day_ts = $from_info[0];
			$day_ymd = date('Y-m-d', $day_ts);
			$curwday = $this->getWdayString($from_info['wday'], 'short');

			$tot_arrivals 	= 0;
			$gst_arrivals 	= 0;
			$tot_departures = 0;
			$gst_departures = 0;
			$tot_stayover   = 0;
			$gst_stayover   = 0;
			$tot_breakfast  = 0;
			$tot_lunch 		= 0;
			$tot_dinner 	= 0;
			$website_res 	= 0;
			$channel_res 	= 0;

			// calculate the report details for this day
			foreach ($bookings as $gbook) {
				// make sure the booking affects the current day
				if (!($from_info[0] >= $gbook[0]['from_ts'] && $from_info[0] <= $gbook[0]['to_ts'])) {
					// skip reservation for the current day
					continue;
				}

				// check stay type for today
				if ($day_ymd == date('Y-m-d', $gbook[0]['checkin'])) {
					// arrival
					$raw_type = 'arriving';
					$tot_arrivals++;
				} elseif ($day_ymd == date('Y-m-d', $gbook[0]['checkout'])) {
					// departure
					$raw_type = 'departing';
					$tot_departures++;
				} else {
					// stayover
					$raw_type = 'stayover';
					$tot_stayover++;
				}

				// check reservation provenience
				if (!empty($gbook[0]['idorderota']) && !empty($gbook[0]['channel'])) {
					// OTA reservation
					$channel_res++;
				} else {
					// website reservation
					$website_res++;
				}

				// check meal plans for each room record of this booking
				foreach ($gbook as $roomres) {
					// rate plan name and ID, if any
					$active_rplan_id = 0;
					if (!empty($roomres['otarplan'])) {
						$rplan = $roomres['otarplan'];
					} else {
						list($rplan, $active_rplan_id) = $this->getPriceName($roomres['idtar']);
					}

					// room guests number
					$room_guests_numb = ($roomres['adults'] + $roomres['children']);

					// count number of guests per stay type
					if ($raw_type == 'arriving') {
						// arrival
						$gst_arrivals += $room_guests_numb;
					} elseif ($raw_type == 'departing') {
						// departure
						$gst_departures += $room_guests_numb;
					} else {
						// stayover
						$gst_stayover += $room_guests_numb;
					}

					// meals included in the room rate
					$included_meals = [];
					if (!empty($roomres['meals'])) {
						// display included meals defined at room-reservation record
						$included_meals = VBOMealplanManager::getInstance()->roomRateIncludedMeals($roomres);
					} else {
						// fetch default included meals in the selected rate plan
						$included_meals = $active_rplan_id ? VBOMealplanManager::getInstance()->ratePlanIncludedMeals($active_rplan_id) : [];
					}
					if (!$included_meals && empty($roomres['meals']) && !empty($roomres['idorderota']) && !empty($roomres['channel']) && !empty($roomres['custdata'])) {
						// attempt to fetch the included meal plans from the raw customer data or OTA reservation and room
						$included_meals = VBOMealplanManager::getInstance()->otaDataIncludedMeals($roomres, $roomres);
					}
					foreach ($included_meals as $meal_enum => $meal_name) {
						if ($raw_type == 'arriving') {
							// no breakfast for those who arrive
							if ($meal_enum == 'breakfast') {
								continue;
							}
							// increase pax for this meal
							if ($meal_enum == 'lunch') {
								$tot_lunch += $room_guests_numb;
							} elseif ($meal_enum == 'dinner') {
								$tot_dinner += $room_guests_numb;
							}
						} elseif ($raw_type == 'departing') {
							// only breakfast for those who depart
							if ($meal_enum == 'breakfast') {
								// increase pax for this meal
								$tot_breakfast += $room_guests_numb;
							}
						} else {
							// increase pax for this meal (any kind of meal for those who are staying)
							if ($meal_enum == 'breakfast') {
								$tot_breakfast += $room_guests_numb;
							} elseif ($meal_enum == 'lunch') {
								$tot_lunch += $room_guests_numb;
							} elseif ($meal_enum == 'dinner') {
								$tot_dinner += $room_guests_numb;
							}
						}
					}
				}
			}

			// push fields in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'day',
					'callback' => function ($val) use ($df, $datesep, $curwday) {
						return $curwday.', '.date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $day_ts,
				),
				array(
					'key' => 'arrivals',
					'attr' => array(
						'class="center"'
					),
					'value' => $gst_arrivals,
				),
				array(
					'key' => 'departures',
					'attr' => array(
						'class="center"'
					),
					'value' => $gst_departures,
				),
				array(
					'key' => 'stayover',
					'attr' => array(
						'class="center"'
					),
					'value' => $gst_stayover,
				),
				array(
					'key' => 'breakfast',
					'attr' => array(
						'class="center"'
					),
					'value' => $tot_breakfast,
				),
				array(
					'key' => 'lunch',
					'attr' => array(
						'class="center"'
					),
					'value' => $tot_lunch,
				),
				array(
					'key' => 'dinner',
					'attr' => array(
						'class="center"'
					),
					'value' => $tot_dinner,
				),
				array(
					'key' => 'website',
					'attr' => array(
						'class="center"'
					),
					'value' => $website_res,
				),
				array(
					'key' => 'channels',
					'attr' => array(
						'class="center"'
					),
					'value' => $channel_res,
				),
			));

			// next day iteration
			$from_info = getdate(mktime(0, 0, 0, $from_info['mon'], ($from_info['mday'] + 1), $from_info['year']));
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// loop over the rows to build the footer row with the totals
		$foot_arrivals 	  = 0;
		$foot_departures  = 0;
		$foot_stayover 	  = 0;
		$foot_breakfast   = 0;
		$foot_lunch 	  = 0;
		$foot_dinner 	  = 0;
		$foot_website_res = 0;
		$foot_channel_res = 0;

		foreach ($this->rows as $row) {
			$foot_arrivals 	  += $row[1]['value'];
			$foot_departures  += $row[2]['value'];
			$foot_stayover 	  += $row[3]['value'];
			$foot_breakfast   += $row[4]['value'];
			$foot_lunch 	  += $row[5]['value'];
			$foot_dinner 	  += $row[6]['value'];
			$foot_website_res += $row[7]['value'];
			$foot_channel_res += $row[8]['value'];
		}

		array_push($this->footerRow, array(
			array(
				'attr' => array(
					'class="vbo-report-total"'
				),
				'value' => '<h3>' . JText::translate('VBOREPORTSTOTALROW') . '</h3>',
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_arrivals,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_departures,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_stayover,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_breakfast,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_lunch,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_dinner,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_website_res,
			),
			array(
				'attr' => array(
					'class="center"'
				),
				'value' => $foot_channel_res,
			),
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
	 */
	private function registerExportCSVFileName()
	{
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.csv');
	}

	/**
	 * Finds the ID and name of the rate plan from the given tariff ID.
	 *
	 * @param 	int  	$idtar	the ID of the tariff.
	 *
	 * @return 	array 			list of rate plan name (or an empty string) and ID.
	 */
	private function getPriceName($idtar)
	{
		static $tariffs_map = [];

		$idtar = (int)$idtar;
		if (!$idtar) {
			return [JText::translate('VBOROOMCUSTRATEPLAN'), 0];
		}

		if (isset($tariffs_map[$idtar])) {
			return $tariffs_map[$idtar];
		}

		$q = "SELECT `p`.`id`, `p`.`name` FROM `#__vikbooking_prices` AS `p`
			LEFT JOIN `#__vikbooking_dispcost` AS `t` ON `p`.`id`=`t`.`idprice` WHERE `t`.`id`={$idtar}";
		$this->dbo->setQuery($q, 0, 1);
		$price_record = $this->dbo->loadAssoc();

		if ($price_record) {
			// cache value and return it
			$tariffs_map[$idtar] = [$price_record['name'], $price_record['id']];

			return $tariffs_map[$idtar];
		}

		// cache value and return it
		$tariffs_map[$idtar] = ['', 0];

		return $tariffs_map[$idtar];
	}
}
