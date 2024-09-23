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
 * Daily Room Report child Class of VikBookingReport
 */
class VikBookingReportDailyRoomReport extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 */
	public $defaultKeySort = 'type';

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
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTREVENUEDAY').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />',
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		//To Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" placeholder="'.addslashes(JText::translate('VBOFILTEISROPTIONAL')).'" class="vbo-report-datepicker vbo-report-datepicker-to" />',
			'type' => 'calendar',
			'name' => 'todate'
		);
		array_push($this->reportFilters, $filter_opt);

		// type filter
		$types = [
			'arrivals' 	 => JText::translate('VBOARRIVING'),
			'departures' => JText::translate('VBODEPARTING'),
			'stayover' 	 => JText::translate('VBOTYPESTAYOVER'),
		];
		$ptype = VikRequest::getString('type', '', 'request');
		$types_sel_html = $vbo_app->getNiceSelect($types, $ptype, 'type', JText::translate('VBANYTHING'), JText::translate('VBANYTHING'), '', '', 'type');
		$filter_opt = array(
			'label' => '<label for="type">' . JText::translate('VBPSHOWSEASONSTHREE') . '</label>',
			'html' => $types_sel_html,
			'type' => 'select',
			'name' => 'type'
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
		$pfromdate = VikRequest::getString('fromdate', date($df), 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$js = 'jQuery(document).ready(function() {
			jQuery(".vbo-report-datepicker:input").datepicker({
				'.(!empty($mincheckin) ? 'minDate: "'.date($df, $mincheckin).'", ' : '').'
				'.(!empty($maxcheckout) ? 'maxDate: "'.date($df, $maxcheckout).'", ' : '').'
				dateFormat: "'.$this->getDateFormat('jui').'"
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
		});';
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
		$ptodate = VikRequest::getString('todate', $pfromdate, 'request');
		$ptodate = empty($ptodate) ? $pfromdate : $ptodate;
		$ptype = VikRequest::getString('type', '', 'request');
		$pidroom = VikRequest::getInt('idroom', '', 'request');
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';

		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		// get date timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		// arrival and departure dates
		$arrivedt = date('Y-m-d', $from_ts);
		$departdt = date('Y-m-d', $to_ts);

		// date type filter
		$dtype_clause = "`o`.`checkout` >= {$from_ts} AND `o`.`checkin` <= {$to_ts}";
		if ($ptype == 'arrivals') {
			$dtype_clause = "`o`.`checkin` >= {$from_ts} AND `o`.`checkin` <= {$to_ts}";
		} elseif ($ptype == 'departures') {
			$dtype_clause = "`o`.`checkout` >= {$from_ts} AND `o`.`checkout` <= {$to_ts}";
		} elseif ($ptype == 'stayover') {
			$dtype_clause = "`o`.`checkin` < {$from_ts} AND `o`.`checkout` > {$to_ts}";
		}

		// query to obtain the records
		$q = "SELECT `o`.`id`,`o`.`custdata`,`o`.`ts`,`o`.`days`,`o`.`checkin`,`o`.`checkout`,`o`.`roomsnum`,`o`.`idorderota`,`o`.`channel`,`o`.`country`,`o`.`adminnotes`,".
			"`or`.`idorder`,`or`.`idroom`,`or`.`adults`,`or`.`children`,`or`.`pets`,`or`.`idtar`,`or`.`t_first_name`,`or`.`t_last_name`,`or`.`roomindex`,`or`.`pkg_name`,`or`.`otarplan`,".
			"`or`.`meals`,`r`.`name`,`r`.`params`,`co`.`idcustomer`,`c`.`first_name`,`c`.`last_name`,`c`.`country` AS `customer_country` ".
			"FROM `#__vikbooking_orders` AS `o` LEFT JOIN `#__vikbooking_ordersrooms` AS `or` ON `or`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_rooms` AS `r` ON `or`.`idroom`=`r`.`id` ".
			"LEFT JOIN `#__vikbooking_customers_orders` AS `co` ON `co`.`idorder`=`o`.`id` LEFT JOIN `#__vikbooking_customers` AS `c` ON `c`.`id`=`co`.`idcustomer` ".
			"WHERE `o`.`status`='confirmed' AND `o`.`closure`=0 AND {$dtype_clause} ".(!empty($pidroom) ? "AND `or`.`idroom`=".(int)$pidroom." " : "").
			"ORDER BY `o`.`checkin` DESC, `o`.`id` ASC;";
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
			//calculate the from_ts and to_ts values for later comparison
			$in_info = getdate($v['checkin']);
			$out_info = getdate($v['checkout']);
			$v['from_ts'] = mktime(0, 0, 0, $in_info['mon'], $in_info['mday'], $in_info['year']);
			$v['to_ts'] = mktime(23, 59, 59, $out_info['mon'], ($out_info['mday'] - 1), $out_info['year']);
			//
			array_push($bookings[$v['id']], $v);
		}

		//define the columns of the report
		$this->cols = array(
			//Type
			array(
				'key' => 'type',
				'sortable' => 1,
				'label' => JText::translate('VBPSHOWSEASONSTHREE')
			),
			//Room name
			array(
				'key' => 'room_name',
				'attr' => array(
					'class="left"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBEDITORDERTHREE')
			),
			//customer
			array(
				'key' => 'customer',
				'attr' => array(
					'class="left"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBCUSTOMERNOMINATIVE')
			),
			//guests
			array(
				'key' => 'guests',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBPVIEWORDERSPEOPLE')
			),
			//rate plan
			array(
				'key' => 'rplan',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOROVWSELRPLAN')
			),
			// meal plan
			array(
				'key' => 'mplan',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBO_MEAL_PLAN')
			),
			//Channel
			array(
				'key' => 'channel',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBOCHANNEL')
			),
			//checkin
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="left"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBPICKUPAT')
			),
			//checkout
			array(
				'key' => 'checkout',
				'attr' => array(
					'class="left"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBRELEASEAT')
			),
			//nights
			array(
				'key' => 'nights',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBDAYS')
			),
			//admin notes
			array(
				'key' => 'admin_notes',
				'attr' => array(
					'class="left"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBPSHOWPAYMENTSTHREE')
			),
			//ID
			array(
				'key' => 'id',
				'attr' => array(
					'class="center"'
				),
				'sortable' => 1,
				'label' => JText::translate('VBDASHBOOKINGID')
			),
		);

		//loop over the bookings to build the list of countries
		$countries = [];
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

		// countries map
		$countries_map = $this->getCountriesMap($countries);

		// default channel provenience
		$website_provenience = JText::translate('VBORDFROMSITE');

		// channel logos helper
		$vcm_logos = VikBooking::getVcmChannelsLogo('', true);

		// guests stay counter
		$guests_stay_counter = [
			'arriving'  => 0,
			'departing' => 0,
			'stayover'  => 0,
		];

		// meals included
		$default_meal_plans = VBOMealplanManager::getInstance()->getPlans();
		$guest_meals_included = [];
		foreach ($default_meal_plans as $meal_enum => $meal_name) {
			$guest_meals_included[$meal_enum] = 0;
		}

		// loop over the bookings to build the rows
		foreach ($bookings as $gbook) {
			// prepare vars
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
			$in_info = getdate($gbook[0]['checkin']);
			$curwday_in = $this->getWdayString($in_info['wday'], 'short');
			$out_info = getdate($gbook[0]['checkout']);
			$curwday_out = $this->getWdayString($out_info['wday'], 'short');
			$type = JText::translate('VBOTYPESTAYOVER');
			$raw_type = 'stayover';
			if (date('Y-m-d', $gbook[0]['checkin']) == $arrivedt) {
				$type = JText::translate('VBOTYPEARRIVAL');
				$raw_type = 'arriving';
			} elseif (date('Y-m-d', $gbook[0]['checkout']) == $departdt) {
				$type = JText::translate('VBOTYPEDEPARTURE');
				$raw_type = 'departing';
			}

			// channel logo and provenience
			$provenience  = $website_provenience;
			$channel_html = $website_provenience;
			if (!empty($gbook[0]['channel'])) {
				// build channel name
				$otachannel = '';
				$channelparts = explode('_', $gbook[0]['channel']);
				$otachannel = array_key_exists(1, $channelparts) && strlen($channelparts[1]) ? $channelparts[1] : ucwords($channelparts[0]);
				$provenience = $otachannel;

				// attempt to fetch the channel logo
				$ota_logo_img = is_object($vcm_logos) ? $vcm_logos->setProvenience($otachannel, $gbook[0]['channel'])->getLogoURL() : false;
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

			foreach ($gbook as $roomres) {
				// rate plan name and ID, if any
				$active_rplan_id = 0;
				if (!empty($roomres['otarplan'])) {
					$rplan = $roomres['otarplan'];
				} else {
					list($rplan, $active_rplan_id) = $this->getPriceName($roomres['idtar']);
				}

				// room name and index
				$unit_index = '';
				if (strlen($roomres['roomindex']) && !empty($roomres['params'])) {
					$room_params = json_decode($roomres['params'], true);
					if (is_array($room_params) && array_key_exists('features', $room_params) && @count($room_params['features']) > 0) {
						foreach ($room_params['features'] as $rind => $rfeatures) {
							if ($rind == $roomres['roomindex']) {
								foreach ($rfeatures as $fname => $fval) {
									if (strlen($fval)) {
										$unit_index = ' #'.$fval;
										break;
									}
								}
								break;
							}
						}
					}
				}
				$roomname = $roomres['name'] . $unit_index;

				// room guests number
				$room_guests_numb = ($roomres['adults'] + $roomres['children']);
				$pets_str = '';
				if ($roomres['pets'] > 0) {
					$pets_str = $roomres['pets'] > 1 ? "{$roomres['pets']} " . JText::translate('VBO_PETS') : '1 ' . JText::translate('VBO_PET');
				}

				// update guests stay counter
				$guests_stay_counter[$raw_type] += $room_guests_numb;

				// meals included in the room rate
				$short_meal_enums = VBOMealplanManager::getInstance()->getShortMealPlans();
				$included_meals = [];
				$room_meals = [];
				$room_meals_not_today = [];
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
							// set meal short name
							$room_meals_not_today[] = $short_meal_enums[$meal_enum];
							continue;
						}
						// increase pax for this meal
						$guest_meals_included[$meal_enum] += $room_guests_numb;
						// set meal short name for this room
						$room_meals[] = $short_meal_enums[$meal_enum];
					} elseif ($raw_type == 'departing') {
						// only breakfast for those who depart
						if ($meal_enum == 'breakfast') {
							// increase pax for this meal
							$guest_meals_included[$meal_enum] += $room_guests_numb;
							// set meal short name for this room
							$room_meals[] = $short_meal_enums[$meal_enum];
						} else {
							// set meal short name
							$room_meals_not_today[] = $short_meal_enums[$meal_enum];
						}
					} else {
						// increase pax for this meal (any kind of meal for those who are staying)
						$guest_meals_included[$meal_enum] += $room_guests_numb;
						// set meal first letter for this room
						$room_meals[] = $short_meal_enums[$meal_enum];
					}
				}

				//push fields in the rows array as a new row
				array_push($this->rows, array(
					array(
						'key' => 'type',
						'value' => $type
					),
					array(
						'key' => 'room_name',
						'attr' => array(
							'class="left"'
						),
						'value' => $roomname
					),
					array(
						'key' => 'customer',
						'attr' => array(
							'class="vbo-report-touristtaxes-countryname"'
						),
						'callback' => function ($val) use ($country) {
							if (is_file(VBO_ADMIN_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'countries'.DIRECTORY_SEPARATOR.$country.'.png')) {
								return $val.'<img src="'.VBO_ADMIN_URI.'resources/countries/'.$country.'.png" title="'.$country.'" class="vbo-country-flag vbo-country-flag-left" />';
							}
							return $val;
						},
						'export_callback' => function ($val) use ($country) {
							return $val . (!empty($country) ? ' ('.$country.')' : '');
						},
						'value' => $customer_name
					),
					array(
						'key' => 'guests',
						'attr' => array(
							'class="center"'
						),
						'value' => $room_guests_numb . ($roomres['pets'] > 0 ? " + {$pets_str}" : '')
					),
					array(
						'key' => 'rplan',
						'attr' => array(
							'class="center"'
						),
						'value' => ucwords($rplan)
					),
					array(
						'key' => 'mplan',
						'attr' => array(
							'class="center"'
						),
						'callback' => function ($val) use ($room_meals_not_today, $room_meals) {
							$meals_str = '';
							if ($room_meals_not_today || $room_meals) {
								$meals_str .= '<span class="vbo-wider-badges-wrap vbo-hidein-print">';
								if (count($room_meals_not_today) === 1) {
									// we assume this is no breakfast today because of an arrival
									$meals_str .= '<span class="badge badge-primary">' . implode(', ', $room_meals_not_today) . '</span>';
									$meals_str .= $room_meals ? ' ' : '';
								}
								if ($room_meals) {
									$meals_str .= '<span class="badge badge-info">' . implode(', ', $room_meals) . '</span>';
								}
								if ($room_meals_not_today && count($room_meals_not_today) !== 1) {
									// we assume this is no lunch & dinner today because of a departure
									$meals_str .= $room_meals ? ' ' : '';
									$meals_str .= '<span class="badge badge-primary">' . implode(', ', $room_meals_not_today) . '</span>';
								}
								$meals_str .= '</span>';
								$meals_str .= '<span class="vbo-showin-print">(' . implode(', ', array_merge($room_meals_not_today, $room_meals)) . ')</span>';
							}
							return $meals_str;
						},
						'export_callback' => function ($val) {
							return $val;
						},
						'value' => ($room_meals ? '(' . implode(', ', $room_meals) . ')' : '')
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
							return $curwday_in.', '.date(str_replace("/", $datesep, $df), $val);
						},
						'export_callback' => function ($val) use ($df, $datesep, $curwday_in) {
							return $curwday_in.', '.date(str_replace("/", $datesep, $df), $val);
						},
						'value' => $roomres['checkin']
					),
					array(
						'key' => 'checkout',
						'callback' => function ($val) use ($df, $datesep, $curwday_out) {
							return $curwday_out.', '.date(str_replace("/", $datesep, $df), $val);
						},
						'export_callback' => function ($val) use ($df, $datesep, $curwday_out) {
							return $curwday_out.', '.date(str_replace("/", $datesep, $df), $val);
						},
						'value' => $roomres['checkout']
					),
					array(
						'key' => 'nights',
						'attr' => array(
							'class="center"'
						),
						'value' => $roomres['days']
					),
					array(
						'key' => 'admin_notes',
						'attr' => array(
							'class="left"'
						),
						'value' => $roomres['adminnotes']
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
						'value' => $roomres['id']
					),
				));
			}
		}

		// sort rows
		$this->sortRows($pkrsort, $pkrorder);

		// build the string for tot arrivals, departures, stayovers
		$stay_type_tots = [
			JText::translate('VBOARRIVING') . ': ' . $guests_stay_counter['arriving'],
			JText::translate('VBODEPARTING') . ': ' . $guests_stay_counter['departing'],
			JText::translate('VBOTYPESTAYOVER') . ': ' . $guests_stay_counter['stayover'],
		];

		// build the string for total meal plans included
		$meal_plan_tots = [];
		foreach ($guest_meals_included as $meal_enum => $meal_pax) {
			$meal_plan_tots[] = $default_meal_plans[$meal_enum] . ': ' . $meal_pax;
		}

		// push footer rows
		$this->footerRow[] = [
			[
				'attr' => [
					'class="vbo-report-total"',
					'colspan="12"',
				],
				'value' => '<h3><span class="vbo-report-footer-inlinedata">' . implode(' - ', $stay_type_tots) . '</span> <span class="vbo-report-footer-inlinedata">' . implode(' - ', $meal_plan_tots) . '</span></h3>',
			],
		];

		// Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
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

		$this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . (!empty($ptodate) && $ptodate != $pfromdate ? '-' . str_replace('/', '_', $ptodate) : '') . '.csv');
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
			$records = $this->dbo->loadAssocList();
			if ($records) {
				foreach ($records as $v) {
					$map[$v['country_3_code']] = $v['country_name'];
				}
			}
		}

		return $map;
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
		if (empty($idtar)) {
			return [JText::translate('VBOROOMCUSTRATEPLAN'), 0];
		}

		$q = "SELECT `p`.`id`, `p`.`name` FROM `#__vikbooking_prices` AS `p`
			LEFT JOIN `#__vikbooking_dispcost` AS `t` ON `p`.`id`=`t`.`idprice` WHERE `t`.`id`=".(int)$idtar.";";
		$this->dbo->setQuery($q);
		$price_record = $this->dbo->loadAssoc();

		if ($price_record) {
			return [$price_record['name'], $price_record['id']];
		}

		return ['', 0];
	}
}
