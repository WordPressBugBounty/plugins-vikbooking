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
 * Revenue child Class of VikBookingReport
 */
class VikBookingReportRevenue extends VikBookingReport
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

		// Listings Filter
		$filter_opt = array(
			'label' => '<label for="listingsfilt">' . JText::translate('VBO_LISTINGS') . '</label>',
			'html' => '<span class="vbo-toolbar-multiselect-wrap">' . $vbo_app->renderElementsDropDown([
				'id'              => 'listingsfilt',
				'elements'        => 'listings',
				'placeholder'     => JText::translate('VBO_LISTINGS'),
				'allow_clear'     => 1,
				'attributes'      => [
					'name' => 'listings[]',
					'multiple' => 'multiple',
				],
				'selected_values' => (array) JFactory::getApplication()->input->get('listings', [], 'array'),
			]) . '</span>',
			'type' => 'select',
			'multiple' => true,
			'name' => 'listings',
		);
		array_push($this->reportFilters, $filter_opt);

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
		if ($this->getError()) {
			// export functions may set errors rather than exiting the process, and the View may continue the execution to attempt to render the report.
			return false;
		}

		// input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$pidroom = VikRequest::getInt('idroom', '', 'request');
		$plistings = (array) VikRequest::getVar('listings', array());
		$pchannel = VikRequest::getString('channel', '', 'request');
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

		// get dates timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		// query to obtain the records
		$q = $this->dbo->getQuery(true)
			->select([
				$this->dbo->qn('o.id'),
				$this->dbo->qn('o.custdata'),
				$this->dbo->qn('o.ts'),
				$this->dbo->qn('o.status'),
				$this->dbo->qn('o.days'),
				$this->dbo->qn('o.checkin'),
				$this->dbo->qn('o.checkout'),
				$this->dbo->qn('o.totpaid'),
				$this->dbo->qn('o.coupon'),
				$this->dbo->qn('o.roomsnum'),
				$this->dbo->qn('o.total'),
				$this->dbo->qn('o.idorderota'),
				$this->dbo->qn('o.channel'),
				$this->dbo->qn('o.country'),
				$this->dbo->qn('o.tot_taxes'),
				$this->dbo->qn('o.tot_city_taxes'),
				$this->dbo->qn('o.tot_fees'),
				$this->dbo->qn('o.tot_damage_dep'),
				$this->dbo->qn('o.cmms'),
				$this->dbo->qn('o.refund'),
				$this->dbo->qn('o.canc_fee'),
				$this->dbo->qn('or.idorder'),
				$this->dbo->qn('or.idroom'),
				$this->dbo->qn('or.optionals'),
				$this->dbo->qn('or.cust_cost'),
				$this->dbo->qn('or.cust_idiva'),
				$this->dbo->qn('or.extracosts'),
				$this->dbo->qn('or.room_cost'),
			])
			->from($this->dbo->qn('#__vikbooking_orders', 'o'))
			->leftJoin($this->dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $this->dbo->qn('or.idorder') . ' = ' . $this->dbo->qn('o.id'))
			->where('(' . $this->dbo->qn('o.status') . ' = ' . $this->dbo->q('confirmed') . ' OR (' . $this->dbo->qn('o.status') . ' = ' . $this->dbo->q('cancelled') . ' AND ' . $this->dbo->qn('o.canc_fee') . ' > 0))')
			->where($this->dbo->qn('o.closure') . ' = 0')
			->where($this->dbo->qn('o.checkout') . ' >= ' . $from_ts)
			->where($this->dbo->qn('o.checkin') . ' <= ' . $to_ts)
			->order($this->dbo->qn('o.checkin') . ' ASC')
			->order($this->dbo->qn('o.id') . ' ASC')
			->order($this->dbo->qn('or.id') . ' ASC');

		/**
		 * Do not filter the room booking records by room ID, but always join them, to improve accuracy with calculations.
		 * 
		 * @since 	1.18.0 (J) - 1.8.0 (WP)
		 */
		if (!empty($pidroom)) {
			// keep supporting the old single-listing filter, no longer used
			// $q->where($this->dbo->qn('or.idroom') . ' = ' . (int) $pidroom);
		} elseif ($plistings) {
			// new multi-listing filter
			// $q->where($this->dbo->qn('or.idroom') . ' IN (' . implode(', ', array_map('intval', $plistings)) . ')');
		}

		if (strlen($pchannel)) {
			// filter by channel
			if ($pchannel == -1) {
				$q->where($this->dbo->qn('o.channel') . ' IS NULL');
			} else {
				$q->where($this->dbo->qn('o.channel') . ' LIKE ' . $this->dbo->q('%' . $pchannel . '%'));
			}
		}

		$this->dbo->setQuery($q);
		$records = $this->dbo->loadAssocList();

		if (!$records) {
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

		unset($records);

		// define the columns of the report
		$this->cols = array(
			//date
			array(
				'key' => 'day',
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEDAY')
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
			//% occupancy
			array(
				'key' => 'occupancy',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOREPORTREVENUEPOCC')
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
			// Refunds
			array(
				'key' => 'refunds',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBO_TOT_REFUNDS')
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
			// Damage deposit
			array(
				'key' => 'damagedep',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBO_DAMAGE_DEPOSIT')
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
				'label' => JText::translate('VBOREPORTREVENUEREV')
			)
		);

		$total_rooms_units = $this->countRooms($pidroom ?: $plistings) ?: 1;

		// loop over the dates of the report to build the rows
		$from_info = getdate($from_ts);
		$to_info = getdate($to_ts);
		$canc_fee_bookings = [];
		while ($from_info[0] <= $to_info[0]) {
			// prepare default fields for this row
			$day_ts = $from_info[0];
			$curwday = $this->getWdayString($from_info['wday'], 'short');
			$rooms_sold = 0;
			$tot_bookings = 0;
			$occupancy = 0;
			$ibe_revenue = 0;
			$ota_revenue = 0;
			$tot_refunds = 0;
			$adr = 0;
			$revpar = 0;
			$taxes = 0;
			$tot_damage_deps = 0;
			$cmms = 0;
			$canc_fees = 0;
			$revenue = 0;

			// calculate the report details for this day
			foreach ($bookings as $gbook) {
				// skip already parsed cancelled bookings with cancellation fees
				if (in_array($gbook[0]['id'], $canc_fee_bookings)) {
					continue;
				}

				// check if booking affects the current day
				if ($from_info[0] >= $gbook[0]['from_ts'] && $from_info[0] <= $gbook[0]['to_ts']) {
					// immediately check for cancelled bookings with cancellation fees
					if ($gbook[0]['status'] == 'cancelled') {
						// increase value
						$canc_fees += $gbook[0]['canc_fee'];
						// record this booking as already counted
						$canc_fee_bookings[] = $gbook[0]['id'];
						continue;
					}

					// this booking affects the current day
					$tot_bookings++;

					// get the optionally filtered rooms (single listing no longer used, or multiple listings)
					$filter_rooms = (array) ($pidroom ?: $plistings);

					// count the rooms booked in case of rooms filtering
					if ($filter_rooms) {
						foreach ($gbook as $sgbook) {
							if (in_array($sgbook['idroom'], $filter_rooms)) {
								$rooms_sold++;
							}
						}
					} else {
						$rooms_sold += $gbook[0]['roomsnum'];
					}

					// determine the base total amount ONLY in case of rooms filtering
					$room_base_total = 0;
					if ($filter_rooms && $rooms_sold > 0) {
						foreach ($gbook as $room_book) {
							if (!in_array($room_book['idroom'], $filter_rooms)) {
								continue;
							}
							if ($room_book['cust_cost'] > 0) {
								$room_base_total += $room_book['cust_cost'];
							} elseif ($room_book['room_cost'] > 0) {
								$room_base_total += $room_book['room_cost'];
							}
						}
					}

					// check if a (coupon) discount was applied
					$discount = 0;
					if (!empty($gbook[0]['coupon'])) {
						$coupon_parts = explode(';', $gbook[0]['coupon']);
						$discount = $coupon_parts[1] ?? $coupon_parts[0];
						$discount = (float) preg_replace('/[^0-9\.]/', '', $discount);
					}

					// calculate net revenue and taxes
					$tot_net = $gbook[0]['total'] - (float) $gbook[0]['tot_taxes'] - (float) $gbook[0]['tot_city_taxes'] - (float) $gbook[0]['tot_fees'] - (float) $gbook[0]['tot_damage_dep'] - (float) $gbook[0]['cmms'];

					if ($room_base_total && (!((float) $gbook[0]['tot_taxes']) || !VikBooking::ivaInclusa())) {
						/**
						 * Overwrite total net value in case of rooms filtering only in case of no taxes, or in
						 * case of prices before taxes, because the amount of tax per room is not available.
						 * 
						 * @since 	1.16.9 (J) - 1.6.9 (WP)
						 * @since 	1.18.0 (J) - 1.8.0 (WP) when relying on the room costs, ensure to deduct the discount.
						 */
						$tot_net = $room_base_total - ($discount / max(1, count(array_intersect(array_column($gbook, 'idroom'), $filter_rooms))));
					} elseif ($room_base_total && $gbook[0]['roomsnum'] > 1 && array_diff(array_column($gbook, 'idroom'), $filter_rooms)) {
						/**
						 * Overwrite total net value in case of rooms filtering, multi-room booking and booking rooms different than filters.
						 * The amount of tax per room is not available, hence the tax calculation may not be accurate in these cases.
						 * 
						 * @since 	1.18.0 (J) - 1.8.0 (WP)
						 */
						$tot_net = $room_base_total - ($discount / max(1, count(array_intersect(array_column($gbook, 'idroom'), $filter_rooms))));
					}

					// ensure we do not have negative values
					$tot_net = $tot_net > 0 ? $tot_net : 0;

					$tot_net = $tot_net / (int) $gbook[0]['days'];
					$revenue += $tot_net;

					if (!empty($gbook[0]['idorderota']) && !empty($gbook[0]['channel'])) {
						$ota_revenue += $tot_net;
					} else {
						$ibe_revenue += $tot_net;
					}

					$taxes += ((float) $gbook[0]['tot_taxes'] + (float) $gbook[0]['tot_city_taxes'] + (float) $gbook[0]['tot_fees']) / (int) $gbook[0]['days'];
					$cmms += (float) $gbook[0]['cmms'] / (int) $gbook[0]['days'];
					$tot_refunds += $gbook[0]['refund'] / $gbook[0]['days'];

					// attempt to calculate the damage deposit for the current day and room
					$room_day_damage_dep = (float) $gbook[0]['tot_damage_dep'] / (int) $gbook[0]['days'];
					if ($room_base_total) {
						// when filters applied, calculate the amount of damage deposit per room for this day
						$room_day_damage_dep = $room_day_damage_dep / (int) $gbook[0]['roomsnum'] * max(1, count(array_intersect(array_column($gbook, 'idroom'), $filter_rooms)));
					}
					$tot_damage_deps += $room_day_damage_dep;
				}
			}

			$occupancy = round(($rooms_sold * 100 / $total_rooms_units), 2);
			$adr = $rooms_sold > 0 ? $revenue / $rooms_sold : 0;
			$revpar = $revenue / $total_rooms_units;

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
					'key' => 'rooms_sold',
					'attr' => array(
						'class="center"'
					),
					'value' => $rooms_sold,
				),
				array(
					'key' => 'tot_bookings',
					'attr' => array(
						'class="center"'
					),
					'value' => $tot_bookings,
				),
				array(
					'key' => 'occupancy',
					'attr' => array(
						'class="center"'
					),
					'value' => $occupancy,
				),
				array(
					'key' => 'ibe_revenue',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $ibe_revenue,
				),
				array(
					'key' => 'ota_revenue',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $ota_revenue,
				),
				array(
					'key' => 'refunds',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $tot_refunds,
				),
				array(
					'key' => 'adr',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $adr,
				),
				array(
					'key' => 'revpar',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $revpar,
				),
				array(
					'key' => 'taxes',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $taxes,
				),
				array(
					'key' => 'damagedep',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $tot_damage_deps,
				),
				array(
					'key' => 'cmms',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $cmms,
				),
				array(
					'key' => 'canc_fees',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $canc_fees,
				),
				array(
					'key' => 'revenue',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => ($revenue + $canc_fees),
				)
			));

			// next day iteration
			$from_info = getdate(mktime(0, 0, 0, $from_info['mon'], ($from_info['mday'] + 1), $from_info['year']));
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// loop over the rows to build the footer row with the totals
		$foot_rooms_sold = 0;
		$foot_tot_bookings = 0;
		$foot_ibe_revenue = 0;
		$foot_ota_revenue = 0;
		$foot_tot_refunds = 0;
		$foot_taxes = 0;
		$foot_damage_deps = 0;
		$foot_cmms = 0;
		$foot_canc_fees = 0;
		$foot_revenue = 0;

		foreach ($this->rows as $row) {
			$foot_rooms_sold 	+= $row[1]['value'];
			$foot_tot_bookings  += $row[2]['value'];
			$foot_ibe_revenue 	+= $row[4]['value'];
			$foot_ota_revenue 	+= $row[5]['value'];
			$foot_tot_refunds 	+= $row[6]['value'];
			$foot_taxes 		+= $row[9]['value'];
			$foot_damage_deps	+= $row[10]['value'];
			$foot_cmms 			+= $row[11]['value'];
			$foot_canc_fees 	+= $row[12]['value'];
			$foot_revenue 		+= $row[13]['value'];
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
				'value' => '',
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
				'attr' => array(
					'class="center"'
				),
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb . ' ' . VikBooking::numberFormat($val);
				},
				'value' => $foot_tot_refunds,
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
				'value' => $foot_damage_deps,
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
}
