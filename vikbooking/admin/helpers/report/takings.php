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
 * Takings Report child Class of VikBookingReport.
 * 
 * @since 	1.16.2 (J) - 1.6.2 (WP)
 */
class VikBookingReportTakings extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 */
	public $defaultKeySort = 'dt';

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
		$this->reportName = JText::translate('VBO_TAKINGS');
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
		if ($this->reportFilters) {
			// do not run this method twice, as it could load JS and CSS files.
			return $this->reportFilters;
		}

		// get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		// load the jQuery UI Datepicker
		$this->loadDatePicker();

		// From Date Filter
		$filter_opt = array(
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTREVENUEDAY').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />',
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		// To Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
			'type' => 'calendar',
			'name' => 'todate'
		);
		array_push($this->reportFilters, $filter_opt);

		// status filter
		$types = [
			'any' 		=> JText::translate('VBANYTHING'),
			'confirmed' => JText::translate('VBCONFIRMED'),
		];
		$ptype = VikRequest::getString('type', '', 'request');
		$types_sel_html = $vbo_app->getNiceSelect($types, $ptype, 'type', JText::translate('VBANYTHING'), JText::translate('VBANYTHING'), '', '', 'type');
		$filter_opt = array(
			'label' => '<label for="type">' . JText::translate('VBSTATUS') . '</label>',
			'html' => $types_sel_html,
			'type' => 'select',
			'name' => 'type'
		);
		array_push($this->reportFilters, $filter_opt);

		// get minimum check-in and maximum check-out for dates filters
		$df = $this->getDateFormat();
		$mindate = 0;
		$maxdate = 0;
		$q = "SELECT MIN(`ts`) AS `mindate`, MAX(`checkout`) AS `maxdate` FROM `#__vikbooking_orders` WHERE `status`='confirmed' AND `closure`=0;";
		$this->dbo->setQuery($q);
		$data = $this->dbo->loadAssoc();	
		if ($data && !empty($data['mindate']) && !empty($data['maxdate'])) {
			$mindate = $data['mindate'];
			$maxdate = $data['maxdate'];
		}

		// jQuery code for the datepicker calendars and select2
		$pfromdate = VikRequest::getString('fromdate', date($df), 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$js = 'jQuery(function() {
			jQuery(".vbo-report-datepicker:input").datepicker({
				'.(!empty($mindate) ? 'minDate: "'.date($df, $mindate).'", ' : '').'
				'.(!empty($maxdate) ? 'maxDate: "'.date($df, $maxdate).'", ' : '').'
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
		$ptype = VikRequest::getString('type', '', 'request');
		$ptype = $ptype ? $ptype : 'any';
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';

		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		$currency_symb = VikBooking::getCurrencySymb();
		if (empty($ptodate)) {
			$ptodate = $pfromdate;
		}
		// get date timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		// history object
		$history_obj = VikBooking::getBookingHistoryInstance();

		// list of history types to fetch
		$h_payment_group = $history_obj->getTypeGroups($group = 'GPM');
		if (!$h_payment_group || !$h_payment_group['types']) {
			return false;
		}
		// merge default types with 'PU' for the manually set "new amount paid" and with 'RU' (refunded amount updated)
		$h_payment_types = array_unique(array_merge($h_payment_group['types'], ['PU', 'RU']));

		// query to obtain the records
		$q = $this->dbo->getQuery(true);
		$q->select($this->dbo->qn([
			'h.idorder',
			'h.dt',
			'h.type',
			'h.descr',
			'h.totpaid',
			'h.total',
			'h.data',
		]));
		$q->from($this->dbo->qn('#__vikbooking_orderhistory', 'h'));
		$q->where($this->dbo->qn('h.dt') . ' >= ' . $this->dbo->q(JDate::getInstance(date('Y-m-d H:i:s', $from_ts))->toSql()));
		$q->where($this->dbo->qn('h.dt') . ' <= ' . $this->dbo->q(JDate::getInstance(date('Y-m-d H:i:s', $to_ts))->toSql()));
		$q->where($this->dbo->qn('h.type') . ' IN (' . implode(', ', array_map([$this->dbo, 'q'], $h_payment_types)) . ')');
		$q->where($this->dbo->qn('h.total') . ' > 0');
		$q->order($this->dbo->qn('h.dt') . ' ASC');

		$this->dbo->setQuery($q);
		$records = $this->dbo->loadAssocList();
		if (!$records) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			return false;
		}

		// grab all booking IDs involved
		$all_bids = [];
		foreach ($records as $hrecord) {
			if (!in_array($hrecord['idorder'], $all_bids)) {
				$all_bids[] = (int)$hrecord['idorder'];
			}
		}

		// fetch additional record information
		$q = $this->dbo->getQuery(true);
		$q->select($this->dbo->qn([
			'o.id',
			'o.custdata',
			'o.ts',
			'o.status',
			'o.days',
			'o.checkin',
			'o.checkout',
			'o.idpayment',
			'o.roomsnum',
			'o.idorderota',
			'o.channel',
			'o.chcurrency',
			'o.country',
			'o.closure',
			'o.refund',
			'o.canc_fee',
			'co.idcustomer',
			'c.first_name',
			'c.last_name',
		]));
		$q->from($this->dbo->qn('#__vikbooking_orders', 'o'));
		$q->leftJoin($this->dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $this->dbo->qn('co.idorder') . ' = ' . $this->dbo->qn('o.id'));
		$q->leftJoin($this->dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $this->dbo->qn('c.id') . ' = ' . $this->dbo->qn('co.idcustomer'));
		$q->where($this->dbo->qn('o.id') . ' IN (' . implode(', ', $all_bids) . ')');
		if ($ptype == 'confirmed') {
			$q->where($this->dbo->qn('o.status') . ' = ' . $this->dbo->q('confirmed'));
		}

		$this->dbo->setQuery($q);
		$bookings = $this->dbo->loadAssocList();
		if (!$bookings) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			return false;
		}

		// merge history records with booking values
		foreach ($records as $k => &$hrecord) {
			$found = false;
			foreach ($bookings as $booking) {
				if ($booking['id'] == $hrecord['idorder']) {
					$hrecord = array_merge($hrecord, $booking);
					$found = true;
					continue;
				}
			}
			if (!$found) {
				unset($records[$k]);
			}
		}

		// unset last reference
		unset($hrecord);

		// define the columns of the report
		$this->cols = array(
			// date
			array(
				'key' => 'dt',
				'sortable' => 1,
				'label' => JText::translate('VBPVIEWORDERSONE')
			),
			// customer
			array(
				'key' => 'customer',
				'attr' => array(
					'class="left"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBCUSTOMERNOMINATIVE')
			),
			// channel
			array(
				'key' => 'channel',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOCHANNEL')
			),
			// checkin
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="left"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBPICKUPAT')
			),
			// checkout
			array(
				'key' => 'checkout',
				'attr' => array(
					'class="left"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBRELEASEAT')
			),
			// nights
			array(
				'key' => 'nights',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBDAYS')
			),
			// rooms
			array(
				'key' => 'rooms',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBPVIEWORDERSTHREE')
			),
			// event
			array(
				'key' => 'event',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOBOOKHISTORYLBLTYPE')
			),
			// amount paid
			array(
				'key' => 'amount_paid',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBAMOUNTPAID')
			),
			// payment method
			array(
				'key' => 'pay_meth',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBLIBPAYNAME')
			),
			// status
			array(
				'key' => 'status',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBSTATUS')
			),
			// ID
			array(
				'key' => 'id',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBDASHBOOKINGID')
			),
		);

		// default channel provenience
		$website_provenience = JText::translate('VBORDFROMSITE');

		// channel logos helper
		$vcm_logos = VikBooking::getVcmChannelsLogo('', true);

		// default payment method
		$def_pay_meth = JText::translate('VBOREPORTTOPCUNKNC');

		// loop over the history records to build the rows
		foreach ($records as $hrecord) {
			// customer name
			$customer_name = '-----';
			if (!empty($hrecord['first_name']) && !empty($hrecord['last_name'])) {
				$customer_name = ltrim($hrecord['first_name'] . ' ' . $hrecord['last_name'], ' ');
			} elseif (!empty($hrecord['custdata'])) {
				$parts = explode("\n", $hrecord['custdata']);
				if (count($parts) >= 2 && strpos($parts[0], ':') !== false && strpos($parts[1], ':') !== false) {
					$first_parts = explode(':', $parts[0]);
					$second_parts = explode(':', $parts[1]);
					$customer_name = ltrim(trim($first_parts[1]) . ' ' . trim($second_parts[1]), ' ');
				}
			}

			// country and stay dates
			$country = $hrecord['country'];
			$in_info = getdate($hrecord['checkin']);
			$curwday_in = $this->getWdayString($in_info['wday'], 'short');
			$out_info = getdate($hrecord['checkout']);
			$curwday_out = $this->getWdayString($out_info['wday'], 'short');

			// channel logo and provenience
			$provenience  = $website_provenience;
			$channel_html = $website_provenience;
			if (!empty($hrecord['channel'])) {
				// build channel name
				$otachannel = '';
				$channelparts = explode('_', $hrecord['channel']);
				$otachannel = array_key_exists(1, $channelparts) && strlen($channelparts[1]) ? $channelparts[1] : ucwords($channelparts[0]);
				$provenience = $otachannel;

				// attempt to fetch the channel logo
				$ota_logo_img = is_object($vcm_logos) ? $vcm_logos->setProvenience($otachannel, $hrecord['channel'])->getLogoURL() : false;
				if ($ota_logo_img !== false) {
					// display the image in the View and hide it during printing
					$channel_html = '<img src="' . $ota_logo_img . '" title="' . htmlspecialchars($otachannel) . '" class="vbo-hidein-print vbo-channelimg-medium" />';
					// get rid of the ending "...api" in the channel name
					$channel_html .= '<span class="vbo-showin-print">' . preg_replace("/([a-z0-9 ]+)(api)$/i", '$1', $otachannel) . '</span>';
				} else {
					// get rid of the ending "...api" in the channel name
					$channel_html = '<span class="vbo-provenience">' . preg_replace("/([a-z0-9 ]+)(api)$/i", '$1', $otachannel) . '</span>';
				}
			}

			// total booking amount, amount paid and payment method
			$tot_booking = $hrecord['total'];
			$amount_paid = $hrecord['totpaid'];
			$pay_meth 	 = '';
			if (!empty($hrecord['data'])) {
				$ev_data = json_decode($hrecord['data'], true);
				$ev_data = is_array($ev_data) ? $ev_data : [];
				if (isset($ev_data['amount_paid'])) {
					$amount_paid = (float)$ev_data['amount_paid'];
				}
				if (isset($ev_data['payment_method'])) {
					$pay_meth = $ev_data['payment_method'];
				} elseif (isset($ev_data['driver'])) {
					$pay_meth = basename($ev_data['driver'], '.php');
				}
			}

			if (!$pay_meth && !empty($hrecord['idpayment']) && in_array($hrecord['type'], ['P0', 'PN'])) {
				$payment_record = VikBooking::getPayment($hrecord['idpayment']);
				if ($payment_record) {
					$pay_meth = $payment_record['name'];
				}
			}

			if (!$pay_meth) {
				// unknown payment method
				$pay_meth = $def_pay_meth;
			}

			// refund events
			if ($hrecord['type'] == 'RF' || $hrecord['type'] == 'RU') {
				// set the actual (negative) amount refunded
				$amount_paid = ($hrecord['refund'] - ($hrecord['refund'] * 2));
			}

			// booking status
			if ($hrecord['status'] == 'confirmed') {
				$book_status = JText::translate('VBCONFIRMED');
				$saystaus = '<span class="label label-success">' . JText::translate('VBCONFIRMED') . '</span>';
			} elseif ($hrecord['status'] == 'standby') {
				$book_status = JText::translate('VBSTANDBY');
				$saystaus = '<span class="label label-warning">' . JText::translate('VBSTANDBY') . '</span>';
			} else {
				$book_status = JText::translate('VBCANCELLED');
				$saystaus = '<span class="label label-error">' . JText::translate('VBCANCELLED') . '</span>';
			}

			// push fields in the rows array as a new row
			array_push($this->rows, array(
				array(
					'key' => 'dt',
					'callback' => function ($val) use ($df, $datesep) {
						return JHtml::fetch('date', $val, str_replace("/", $datesep, $df) . ' H:i:s');
					},
					'export_callback' => function ($val) use ($df, $datesep) {
						return JHtml::fetch('date', $val, str_replace("/", $datesep, $df) . ' H:i:s');
					},
					'value' => $hrecord['dt']
				),
				array(
					'key' => 'customer',
					'attr' => array(
						'class="vbo-report-touristtaxes-countryname"'
					),
					'callback' => function ($val) use ($country) {
						if (is_file(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'countries' . DIRECTORY_SEPARATOR . $country . '.png')) {
							return $val . '<img src="' . VBO_ADMIN_URI . 'resources/countries/' . $country . '.png" title="' . $country . '" class="vbo-country-flag vbo-country-flag-left" />';
						}
						return $val;
					},
					'export_callback' => function ($val) use ($country) {
						return $val . (!empty($country) ? ' (' . $country . ')' : '');
					},
					'value' => $customer_name
				),
				array(
					'key' => 'channel',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($channel_html) {
						return $channel_html;
					},
					'export_callback' => function ($val) {
						return $val;
					},
					'value' => $provenience
				),
				array(
					'key' => 'checkin',
					'callback' => function ($val) use ($df, $datesep, $curwday_in) {
						return $curwday_in . ', ' . date(str_replace("/", $datesep, $df), $val);
					},
					'export_callback' => function ($val) use ($df, $datesep, $curwday_in) {
						return $curwday_in . ', ' . date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $hrecord['checkin']
				),
				array(
					'key' => 'checkout',
					'callback' => function ($val) use ($df, $datesep, $curwday_out) {
						return $curwday_out . ', ' . date(str_replace("/", $datesep, $df), $val);
					},
					'export_callback' => function ($val) use ($df, $datesep, $curwday_out) {
						return $curwday_out . ', ' . date(str_replace("/", $datesep, $df), $val);
					},
					'value' => $hrecord['checkout']
				),
				array(
					'key' => 'nights',
					'attr' => array(
						'class="center"'
					),
					'value' => $hrecord['days']
				),
				array(
					'key' => 'rooms',
					'attr' => array(
						'class="center"'
					),
					'value' => $hrecord['roomsnum']
				),
				array(
					'key' => 'event',
					'attr' => array(
						'class="center"'
					),
					'value' => $history_obj->validType($hrecord['type'], true)
				),
				array(
					'key' => 'amount_paid',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($currency_symb) {
						return $currency_symb . ' ' . VikBooking::numberFormat($val);
					},
					'value' => $amount_paid
				),
				array(
					'key' => 'pay_meth',
					'attr' => array(
						'class="center"'
					),
					'value' => $pay_meth
				),
				array(
					'key' => 'status',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) use ($saystaus) {
						return $saystaus;
					},
					'export_callback' => function ($val) {
						return $book_status;
					},
					'value' => $book_status
				),
				array(
					'key' => 'id',
					'attr' => array(
						'class="center"'
					),
					'callback' => function ($val) {
						return '<a href="index.php?option=com_vikbooking&task=editorder&cid[]='.$val.'" target="_blank"><i class="'.VikBookingIcons::i('external-link').'"></i> '.$val.'</a>';
					},
					'export_callback' => function ($val) {
						return $val;
					},
					'value' => $hrecord['idorder']
				),
			));
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// count footer values
		$total_takings = 0;
		foreach ($this->rows as $row_vals) {
			foreach ($row_vals as $row_val) {
				if ($row_val['key'] == 'amount_paid') {
					$total_takings += $row_val['value'];
				}
			}
		}

		// push footer rows
		$this->footerRow[] = [
			[
				'attr' => [
					'class="vbo-report-total"'
				],
				'value' => '<h3>' . JText::translate('VBO_TOTAL_TAKINGS') . '</h3>',
			],
			[
				'value' => '',
			],
			[
				'value' => '',
			],
			[
				'value' => '',
			],
			[
				'value' => '',
			],
			[
				'value' => '',
			],
			[
				'value' => '',
			],
			[
				'value' => '',
			],
			[
				'attr' => [
					'class="center"'
				],
				'callback' => function ($val) use ($currency_symb) {
					return $currency_symb . ' ' . VikBooking::numberFormat($val);
				},
				'value' => $total_takings,
			],
			[
				'value' => '',
			],
			[
				'value' => '',
			],
			[
				'value' => '',
			],
		];

		// Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
			$this->setWarning('$records:<pre>'.print_r($records, true).'</pre><br/>');
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

		$this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . (!empty($ptodate) && $ptodate != $pfromdate ? '-' . str_replace('/', '_', $ptodate) : '') . '.csv');
	}
}
