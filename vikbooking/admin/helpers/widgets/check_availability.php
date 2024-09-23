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
 * Class handler for admin widget "check availability".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VikBookingAdminWidgetCheckAvailability extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget. Since we do not load individual parameters
	 * for each widget's instance, we use a static counter to determine its settings.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Default number of results per page.
	 * 
	 * @var 	int
	 */
	protected $res_per_page = 3;

	/**
	 * Maximum alternative dates to be displayed.
	 * 
	 * @var 	int
	 */
	protected $max_alternative_dates = 6;

	/**
	 * Maximum alternative parties to be displayed.
	 * 
	 * @var 	int
	 */
	protected $max_alternative_parties = 8;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_CHECKAV_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_CHECKAV_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('calculator') . '"></i>';
		$this->widgetStyleName = 'blue';
	}

	/**
	 * Custom method for this widget only to load the rates flow records.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 * 
	 * It's the actual rendering of the widget which also allows navigation.
	 */
	public function loadRoomRates()
	{
		$offset = VikRequest::getInt('offset', 0, 'request');
		$length = VikRequest::getInt('length', $this->res_per_page, 'request');
		$wrapper = VikRequest::getString('wrapper', '', 'request');

		$checkin_date = VikRequest::getString('checkin_date', '', 'request');
		$num_nights = VikRequest::getInt('num_nights', 1, 'request');
		$num_adults = VikRequest::getInt('num_adults', 2, 'request');
		$num_children = VikRequest::getInt('num_children', 0, 'request');

		// optional room IDs and guests array to consider
		$room_ids 	  = [];
		$arr_adults   = [];
		$arr_children = [];

		// multitask can inject a booking id
		$bid = VikRequest::getInt('bid', 0, 'request');
		if (!empty($bid)) {
			// get proper loading values for this booking
			list($b_checkin_date, $b_num_nights, $b_num_adults, $b_num_children, $room_ids) = $this->getBookingLoadValues($bid);
			// only check the calculated booking checkin date to determine if loading values was successful
			if (!empty($b_checkin_date)) {
				// overwrite default values
				$checkin_date = $b_checkin_date;
				$num_nights   = $b_num_nights;
				if (is_array($b_num_adults)) {
					// multiple rooms involved
					$num_adults   = array_sum($b_num_adults);
					$num_children = array_sum($b_num_children);
					$arr_adults   = $b_num_adults;
					$arr_children = $b_num_children;
				} else {
					// single room involved
					$num_adults   = $b_num_adults;
					$num_children = $b_num_children;
				}
			}
		}

		if (empty($checkin_date)) {
			// default to today's date
			$checkin_date = date('Y-m-d');
		}
		if ($num_nights < 1) {
			// minimum number of nights is 1
			$num_nights = 1;
		}

		// convert date to timestamp to support custom date formats
		$checkin_ts = VikBooking::getDateTimestamp($checkin_date);
		$checkin_info = getdate($checkin_ts);
		$checkout_ts = mktime(0, 0, 0, $checkin_info['mon'], ($checkin_info['mday'] + $num_nights), $checkin_info['year']);
		$checkout_info = getdate($checkout_ts);
		// build final dates
		$checkin_date = date('Y-m-d', $checkin_ts);
		$checkout_date = date('Y-m-d', $checkout_ts);

		// we make use of the rates flow report helper class
		$report = VikBooking::getReportInstance('rates_flow');
		if (!$report) {
			// display nothing
			return;
		}

		// date formats and separator
		$df = $report->getDateFormat();
		$dtpicker_df = $report->getDateFormat('jui');
		$date_sep = VikBooking::getDateSeparator();

		// currency symbol
		$currency = VikBooking::getCurrencySymb();

		// invoke availability helper class
		$av_helper = VikBooking::getAvailabilityInstance();

		// set stay values
		$av_helper->setStayDates($checkin_date, $checkout_date);
		if (count($arr_adults) > 1) {
			// multiple rooms involved
			foreach ($arr_adults as $gkey => $radults) {
				$av_helper->setRoomParty($radults, (isset($arr_children[$gkey]) ? $arr_children[$gkey] : 0));
			}
		} else {
			// single room involved
			$av_helper->setRoomParty($num_adults, $num_children);
		}

		if ($room_ids) {
			$av_helper->setRoomIds($room_ids);
		}

		// load available room rates (all records, before pagination that will be applied later)
		$room_rates = $av_helper->getRates();

		// load rooms and rate plans
		$all_rooms  = $av_helper->loadRooms();
		$all_rplans = $av_helper->loadRatePlans();

		// check if availability errors occurred
		$has_av_error  = strlen($av_helper->getError());
		$av_error_code = $av_helper->getErrorCode();

		// count total records
		$tot_records = is_array($room_rates) && !$has_av_error ? count($room_rates) : 0;

		// check if a next page can be available
		$has_next_page = (($offset + $length) < $tot_records) ? 1 : 0;

		// splice the array returned to support pagination
		if ($tot_records > $length) {
			$split_room_rates = array();
			$index_offset = 0;
			foreach ($room_rates as $rid => $rates) {
				if ($index_offset >= $offset) {
					$split_room_rates[$rid] = $rates;
				}
				if (count($split_room_rates) >= $length) {
					break;
				}
				$index_offset++;
			}
			$room_rates = $split_room_rates;
			unset($split_room_rates);
		}

		// list of eligible and available room IDs
		$eligible_rids = [];

		// start output buffering
		ob_start();

		// display the information about the dates and room party
		$party_info_parts = array();
		// stay dates information
		$checkin_str = VikBooking::sayWeekDay($checkin_info['wday'], true) . ', ' . date(str_replace('/', $date_sep, $df), $checkin_ts);
		$checkout_str = VikBooking::sayWeekDay($checkout_info['wday'], true) . ', ' . date(str_replace('/', $date_sep, $df), $checkout_ts);
		$party_info_parts[] = $checkin_str . ' - ' . $checkout_str;
		// nights information
		$nights_lbl = $num_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY');
		$party_info_parts[] = $num_nights . ' ' . $nights_lbl;
		// adults information
		$adults_lbl = $num_adults === 1 ? JText::translate('VBMAILADULT') : JText::translate('VBEDITORDERADULTS');
		$party_info_parts[] = $num_adults . ' ' . $adults_lbl;
		// children information
		$children_lbl = $num_children === 1 ? JText::translate('VBMAILCHILD') : JText::translate('VBEDITORDERCHILDREN');
		if ($num_children > 0) {
			$party_info_parts[] = $num_children . ' ' . $children_lbl;
		}
		?>
		<div class="vbo-widget-checkav-result-party">
			<p><?php echo implode(', ', $party_info_parts); ?></p>
		</div>
		<?php
		
		// check if there is availability
		if (!is_array($room_rates) || !$room_rates || $has_av_error) {
			$explain_err = $av_helper->explainErrorCode();
			$say_error 	 = !empty($explain_err) ? $explain_err : $av_helper->getError();
			$say_error 	 = empty($say_error) ? JText::translate('VBOVERVIEWLEGRED') : $say_error;
			?>
			<p class="err"><?php echo $say_error; ?></p>
			<?php
		} else {
			// parse room rates
			foreach ($room_rates as $rid => $rates) {
				if (!isset($all_rooms[$rid])) {
					// this is to prevent reserved keys from being displayed
					continue;
				}
				// push room ID as eligible and available
				$eligible_rids[] = $rid;
				?>
			<div class="vbo-widget-checkav-result-room-rates">
				<div class="vbo-widget-checkav-result-room-name">
					<span><?php echo $all_rooms[$rid]['name']; ?></span>
				</div>
				<div class="vbo-widget-checkav-result-rates-list">
				<?php
				foreach ($rates as $rplan) {
					$rplan_taxes  = isset($rplan['taxes']) ? $rplan['taxes'] : 0;
					$net_price    = $rplan['cost'] - $rplan_taxes;
					$js_book_args = "'$wrapper', '{$rid}', '{$checkin_date}', '{$checkout_date}', '{$num_adults}', '{$num_children}', '{$rplan['idprice']}'";
					?>
					<div class="vbo-widget-checkav-result-room-rate">
						<div class="vbo-widget-checkav-result-rate-name">
							<span><?php echo isset($all_rplans[$rplan['idprice']]) ? $all_rplans[$rplan['idprice']]['name'] : '?'; ?></span>
						</div>
						<div class="vbo-widget-checkav-result-rate-prices">
						<?php
						if ($net_price != $rplan['cost']) {
							// taxes are greater than zero
							?>
							<span class="vbo-widget-checkav-result-rate-price vbo-widget-checkav-result-rate-net">
								<span><?php echo JText::translate('VBCALCRATESNET'); ?></span>
								<?php echo $currency . ' ' . VikBooking::numberFormat($net_price); ?>
							</span>
							<span class="vbo-widget-checkav-result-rate-price vbo-widget-checkav-result-rate-tax">
								<span><?php echo JText::translate('VBCALCRATESTAX'); ?></span>
								<?php echo $currency . ' ' . VikBooking::numberFormat($rplan_taxes); ?>
							</span>
							<?php
						}
						?>
							<span class="vbo-widget-checkav-result-rate-price vbo-widget-checkav-result-rate-total">
								<span><?php echo JText::translate('VBCALCRATESTOT'); ?></span>
								<?php echo $currency . ' ' . VikBooking::numberFormat($rplan['cost']); ?>
							</span>
							<span class="vbo-widget-checkav-result-rate-price vbo-widget-checkav-result-rate-booknow">
								<button type="button" class="btn btn-primary" onclick="vboWidgetCheckAvailabilityBook(<?php echo $js_book_args; ?>);"><?php echo JText::translate('VBO_BOOKNOW'); ?></button>
							</span>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div>
			<?php
			}

			// append navigation
			?>
			<div class="vbo-widget-commands vbo-widget-commands-right">
				<div class="vbo-widget-commands-main">
				<?php
				if ($offset > 0) {
					// show backward navigation button
					?>
					<div class="vbo-widget-command-chevron vbo-widget-command-prev">
						<span class="vbo-widget-command-chevron-prev" onclick="vboWidgetCheckAvailabilityNavigate('<?php echo $wrapper; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
					</div>
					<?php
				}
				if ($has_next_page) {
					// show forward navigation button
					?>
					<div class="vbo-widget-command-chevron vbo-widget-command-next">
						<span class="vbo-widget-command-chevron-next" onclick="vboWidgetCheckAvailabilityNavigate('<?php echo $wrapper; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
					</div>
				<?php
				}
				?>
				</div>
			</div>
			<?php
		}

		// default pool of suggestions
		$alternative_dates 	 = [];
		$alternative_parties = [];
		$split_stay_sols 	 = [];

		if (!is_array($room_rates) || !$room_rates || $has_av_error) {
			// try to get the suggestions when no availability
			list($alternative_dates, $alternative_parties, $split_stay_sols) = $av_helper->findSuggestions();
		} elseif ($eligible_rids) {
			/**
			 * We did return some available rooms, but we still want to calculate if some
			 * split-stay solutions are also available for some rooms that were left unsold.
			 * 
			 * @since 	1.16.3 (J) - 1.6.3 (WP)
			 */
			$parsable_rids = array_diff(array_keys($av_helper->filterPublishedRooms()), $eligible_rids);
			if ($parsable_rids) {
				// try to suggest split stay solutions by using the room IDs that were not available
				$split_stay_sols = $av_helper->findSplitStays($parsable_rids);
			}
		}

		// parse booking split stays
		if ($split_stay_sols) {
			// we've got booking split stays to suggest
			?>
			<p class="info"><?php VikBookingIcons::e('random'); ?> <?php echo JText::translate('VBO_SPLIT_STAYS'); ?></p>
			<div class="vbo-widget-checkav-result-splitstays-wrap">
			<?php
			foreach ($split_stay_sols as $split_stay_sol) {
				?>
				<div class="vbo-widget-checkav-splitstay-rooms">
					<div class="vbo-widget-checkav-splitstay-rooms-inner">
					<?php
					// start args for booking this split stay solution
					$first_rid = $split_stay_sol[0]['idroom'];
					$split_stay_values = [
						'checkin'  	 => $checkin_date, 
						'checkout' 	 => $checkout_date,
						'split_stay' => [],
					];
					// parse rooms for the split stay
					foreach ($split_stay_sol as $split_k => $split_stay_room) {
						// parse stay dates into DateTime objects
						$checkin_dt_obj  = new JDate($split_stay_room['checkin']);
						$checkout_dt_obj = new JDate($split_stay_room['checkout']);
						// push split stay data for booking
						$split_stay_values['split_stay'][] = [
							'idroom'   => $split_stay_room['idroom'],
							'checkin'  => $split_stay_room['checkin'],
							'checkout' => $split_stay_room['checkout'],
							'nights'   => $split_stay_room['nights'],
						];
						?>
						<div class="vbo-widget-checkav-splitstay-room">
							<div class="vbo-widget-checkav-result-room-name">
								<span><?php VikBookingIcons::e(($split_k > 0 ? 'random' : 'arrow-right')); ?> <?php echo $split_stay_room['room_name']; ?></span>
							</div>
							<div class="vbo-widget-checkav-result-altdates-info">
								<div class="vbo-widget-checkav-result-alt-date">
									<span class="vbo-widget-checkav-result-split-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo $split_stay_room['nights']; ?> <?php echo $split_stay_room['nights'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY'); ?></span>
									<span class="vbo-widget-checkav-result-alt-date-in"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo $checkin_dt_obj->format('D, d M Y', true); ?></span>
									<span class="vbo-widget-checkav-result-alt-date-out"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo $checkout_dt_obj->format('D, d M Y', true); ?></span>
								</div>
							</div>
						</div>
						<?php
					}
					// finalize args for booking this split stay
					$split_stay_qstring = http_build_query($split_stay_values);
					$js_book_args = "'{$first_rid}', '{$num_adults}', '{$num_children}', '{$split_stay_qstring}'";
					?>
					</div>
					<div class="vbo-widget-checkav-splitstay-rooms-book">
						<button type="button" class="btn btn-primary" onclick="vboWidgetCheckAvailabilityBookSplitStay(<?php echo $js_book_args; ?>);"><?php echo JText::translate('VBO_BOOKNOW'); ?></button>
					</div>
				</div>
				<?php
			}
			?>
			</div>
			<?php
		}

		// parse alrernative dates (if any)
		if ($alternative_dates) {
			// we've got some alternative dates to suggest for some rooms
			?>
			<p class="info"><?php echo JText::translate('VBO_ALT_STAY_DATES'); ?></p>
			<div class="vbo-widget-checkav-result-altdates-wrap">
			<?php
			$alt_displayed = 0;
			foreach ($alternative_dates as $arrive_dt => $alt_rooms) {
				$alt_displayed++;
				foreach ($alt_rooms as $rid => $alt_room) {
					if (empty($alt_room['days_av_left'])) {
						continue;
					}
					$sugg_checkin_dt  = null;
					$sugg_checkout_dt = null;
					foreach ($alt_room['days_av_left'] as $dayk => $uleft) {
						if (empty($sugg_checkin_dt)) {
							// grab the first date
							$sugg_checkin_dt = $dayk;
						}
						// always overwrite until last date
						$sugg_checkout_dt = $dayk;
					}
					// increase check-out date by one day (day after last night of stay)
					$sugg_out_info = getdate(strtotime($sugg_checkout_dt));
					$sugg_checkout_dt = date('Y-m-d', mktime(0, 0, 0, $sugg_out_info['mon'], ($sugg_out_info['mday'] + 1), $sugg_out_info['year']));
					// parse stay dates into DateTime objects
					$checkin_dt_obj  = new JDate($sugg_checkin_dt);
					$checkout_dt_obj = new JDate($sugg_checkout_dt);
					// count total nights of stay
					$sug_tot_nights = count($alt_room['days_av_left']);
					// format suggested check-in date for datepicker
					$cal_checkin_dt = date($df, strtotime($sugg_checkin_dt));
					// js arguments
					$js_pick_args = "'$wrapper', '{$rid}', '{$cal_checkin_dt}', '{$sug_tot_nights}', '{$num_adults}', '{$num_children}'";
					?>
				<div class="vbo-widget-checkav-result-room-rates vbo-widget-checkav-result-altdates">
					<div class="vbo-widget-checkav-result-room-name">
						<span><?php echo $alt_room['name']; ?></span>
					</div>
					<div class="vbo-widget-checkav-result-altdates-info">
						<div class="vbo-widget-checkav-result-alt-date">
							<span class="vbo-widget-checkav-result-alt-date-in"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo $checkin_dt_obj->format('D, d M Y', true); ?></span>
							<span class="vbo-widget-checkav-result-alt-date-out"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo $checkout_dt_obj->format('D, d M Y', true); ?></span>
						</div>
						<div class="vbo-widget-checkav-result-alt-choose">
							<button type="button" class="btn btn-primary" onclick="vboWidgetCheckAvailabilityPickRoom(<?php echo $js_pick_args; ?>);"><?php echo JText::translate('VBO_SELECT'); ?></button>
						</div>
					</div>
				</div>
					<?php
				}
				// check limit of alternative dates
				if ($alt_displayed >= $this->max_alternative_dates) {
					break;
				}
			}
			?>
			</div>
			<?php
		}

		// parse alrernative parties (if any)
		if ($alternative_parties) {
			// we've got some alternative combinations of rooms to suggest to fit the party
			?>
			<p class="info"><?php echo JText::translate('VBO_ALT_ROOM_PARTIES'); ?></p>
			<div class="vbo-widget-checkav-result-altparties-wrap">
			<?php
			$alt_displayed = 0;
			foreach ($alternative_parties as $arrive_dt => $alt_parties) {
				$alt_displayed++;
				$alt_dates_displayed = false;
				?>
				<div class="vbo-widget-checkav-result-room-rates vbo-widget-checkav-result-altparty">
				<?php
				foreach ($alt_parties as $alt_party) {
					if (empty($alt_party['days_av_left']) || empty($alt_party['guests_allocation'])) {
						continue;
					}
					// room ID
					$rid = $alt_party['id'];
					// count total nights of stay
					$sug_tot_nights = count($alt_party['days_av_left']);
					// party info
					$party_info_parts = array();
					// adults information
					$adults_lbl = $alt_party['guests_allocation']['adults'] == 1 ? JText::translate('VBMAILADULT') : JText::translate('VBEDITORDERADULTS');
					$party_info_parts[] = $alt_party['guests_allocation']['adults'] . ' ' . $adults_lbl;
					// children information
					$children_lbl = isset($alt_party['guests_allocation']['children']) && $alt_party['guests_allocation']['children'] == 1 ? JText::translate('VBMAILCHILD') : JText::translate('VBEDITORDERCHILDREN');
					if (!empty($alt_party['guests_allocation']['children'])) {
						$party_info_parts[] = $alt_party['guests_allocation']['children'] . ' ' . $children_lbl;
					}
					if (!$alt_dates_displayed) {
						// display the stay dates once per alternative party
						$alt_dates_displayed = true;
						// calculate suggested stay dates
						$sugg_checkin_dt  = null;
						$sugg_checkout_dt = null;
						foreach ($alt_party['days_av_left'] as $dayk => $uleft) {
							if (empty($sugg_checkin_dt)) {
								// grab the first date
								$sugg_checkin_dt = $dayk;
							}
							// always overwrite until last date
							$sugg_checkout_dt = $dayk;
						}
						// increase check-out date by one day (day after last night of stay)
						$sugg_out_info = getdate(strtotime($sugg_checkout_dt));
						$sugg_checkout_dt = date('Y-m-d', mktime(0, 0, 0, $sugg_out_info['mon'], ($sugg_out_info['mday'] + 1), $sugg_out_info['year']));
						// parse stay dates into DateTime objects
						$checkin_dt_obj  = new JDate($sugg_checkin_dt);
						$checkout_dt_obj = new JDate($sugg_checkout_dt);
						?>
					<div class="vbo-widget-checkav-result-altparty-dates">
						<div class="vbo-widget-checkav-result-altparty-stay">
							<span class="vbo-widget-checkav-result-alt-date-in"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo $checkin_dt_obj->format('D, d M Y', true); ?></span>
							<span class="vbo-widget-checkav-result-alt-date-out"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo $checkout_dt_obj->format('D, d M Y', true); ?></span>
						</div>
					</div>
						<?php
					}
					?>
					<div class="vbo-widget-checkav-result-altparty-room">
						<div class="vbo-widget-checkav-result-room-name">
							<span><?php echo $alt_party['name']; ?></span>
						</div>
						<div class="vbo-widget-checkav-result-alt-guests-rparty">
							<span><?php VikBookingIcons::e('users'); ?> <?php echo implode(', ', $party_info_parts); ?></span>
						</div>
					</div>
					<?php
				}
				?>
				</div>
				<?php
				// check limit of alternative parties
				if ($alt_displayed >= $this->max_alternative_parties) {
					break;
				}
			}
			?>
			</div>
			<?php
		}

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return array(
			'html' 		  => $html_content,
			'tot_records' => $tot_records,
			'next_page'   => $has_next_page,
		);
	}

	/**
	 * Preload the necessary CSS/JS assets.
	 * 
	 * @return 	void
	 */
	public function preload()
	{
		// load assets
		$this->vbo_app->loadDatePicker();
	}

	public function render(VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the sticky notes wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-checkav-' . $wrapper_instance;

		// get permissions
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$vbo_auth_bookings) {
			// display nothing
			return;
		}

		// we make use of the rates flow report helper class
		$report = VikBooking::getReportInstance('rates_flow');
		if (!$report) {
			// display nothing
			return;
		}

		// date formats
		$df = $report->getDateFormat();
		$dtpicker_df = $report->getDateFormat('jui');

		// get highest max adults and max children
		list($max_adults, $max_children) = $this->getMaxestGuests();

		// check multitask data
		$page_bid = 0;
		$modal_load_bid = '';
		if ($data) {
			$page_bid = $data->getBookingID();
			if ($page_bid && $data->isModalRendering()) {
				// immediately load contents according to injected multitask data
				$modal_load_bid = $page_bid;
			}
		}

		?>
		<div class="vbo-admin-widget-wrapper">
			<div class="vbo-admin-widget-head">
				<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
			</div>
			<div id="<?php echo $wrapper_id; ?>" class="vbo-widget-checkav-wrap" data-instance="<?php echo $wrapper_instance; ?>" data-pagebid="<?php echo $page_bid; ?>" data-offset="0" data-length="<?php echo $this->res_per_page; ?>">
				<div class="vbo-widget-checkav-filters">
					<div class="vbo-widget-checkav-filters-main">
						<div class="vbo-widget-checkav-filter vbo-widget-checkav-filter-dpicker">
							<div class="vbo-field-calendar">
								<div class="input-append">
									<input type="text" class="vbo-widget-checkav-checkindt" value="" placeholder="<?php echo htmlspecialchars(JText::translate('VBPICKUPAT')); ?>" />
									<button type="button" class="btn btn-secondary vbo-widget-checkav-checkindt-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
								</div>
							</div>
						</div>
						<div class="vbo-widget-checkav-filter vbo-widget-checkav-filter-nights">
							<label for="vbo-widget-checkav-nights-<?php echo $wrapper_instance; ?>"><?php echo JText::translate('VBDAYS'); ?></label>
							<input type="number" id="vbo-widget-checkav-nights-<?php echo $wrapper_instance; ?>" class="vbo-widget-checkav-nights" min="1" max="365" step="1" value="1" />
						</div>
					</div>
					<div class="vbo-widget-checkav-filters-secondary">
						<div class="vbo-widget-checkav-filter vbo-widget-checkav-filter-adults">
							<label for="vbo-widget-checkav-adults-<?php echo $wrapper_instance; ?>"><?php echo JText::translate('VBEDITORDERADULTS'); ?></label>
							<input type="number" id="vbo-widget-checkav-adults-<?php echo $wrapper_instance; ?>" class="vbo-widget-checkav-adults" min="0" max="<?php echo $max_adults; ?>" step="1" value="2" />
						</div>
						<div class="vbo-widget-checkav-filter vbo-widget-checkav-filter-children">
							<label for="vbo-widget-checkav-children-<?php echo $wrapper_instance; ?>"><?php echo JText::translate('VBEDITORDERCHILDREN'); ?></label>
							<input type="number" id="vbo-widget-checkav-children-<?php echo $wrapper_instance; ?>" class="vbo-widget-checkav-children" min="0" max="<?php echo $max_children; ?>" step="1" value="0" />
						</div>
					</div>
					<div class="vbo-widget-checkav-filters-submit">
						<div class="vbo-widget-checkav-filter vbo-widget-checkav-filter-submit">
							<button type="button" class="btn vbo-config-btn" onclick="vboWidgetCheckAvailabilityCalc('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('bed'); ?><?php echo JText::translate('VBRATESOVWRATESCALCULATORCALC'); ?></button>
						</div>
					</div>
				</div>
				<div class="vbo-widget-checkav-results"></div>
			</div>
		</div>
		<?php

		if (static::$instance_counter === 0 || $is_ajax) {
			/**
			 * Print the JS code only once for all instances of this widget.
			 * The real rendering is made through AJAX, not when the page loads.
			 */
			?>
		<a class="vbo-widget-checkav-basenavuri" href="index.php?option=com_vikbooking&task=calendar&cid[]=%d&checkin=%s&checkout=%s&adults=%d&children=%d&idprice=%d&booknow=1" style="display: none;"></a>
		<a class="vbo-widget-checkav-splitstayuri" href="index.php?option=com_vikbooking&task=calendar&cid[]=%d&adults=%d&children=%d" style="display: none;"></a>

		<script type="text/javascript">

			/**
			 * Display the loading skeletons.
			 */
			function vboWidgetCheckAvailabilitySkeletons(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}
				widget_instance.find('.vbo-widget-checkav-results').html('');
				for (var i = 0; i < 1; i++) {
					var skeleton = '';
					skeleton += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">';
					skeleton += '	<div class="vbo-dashboard-guest-activity-avatar">';
					skeleton += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>';
					skeleton += '	</div>';
					skeleton += '	<div class="vbo-dashboard-guest-activity-content">';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-head">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>';
					skeleton += '		</div>';
					skeleton += '		<div class="vbo-dashboard-guest-activity-content-info-msg">';
					skeleton += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>';
					skeleton += '		</div>';
					skeleton += '	</div>';
					skeleton += '</div>';
					// append skeleton
					jQuery(skeleton).appendTo(widget_instance.find('.vbo-widget-checkav-results'));
				}
			}

			/**
			 * Perform the request to load the available room rates.
			 */
			function vboWidgetCheckAvailabilityLoad(wrapper, checkbid) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// get vars for making the request
				var current_offset  = parseInt(widget_instance.attr('data-offset'));
				var length_per_page = parseInt(widget_instance.attr('data-length'));
				var checkin_date 	= widget_instance.find('.vbo-widget-checkav-checkindt').val();
				var num_nights 		= widget_instance.find('.vbo-widget-checkav-nights').val();
				var num_adults 		= widget_instance.find('.vbo-widget-checkav-adults').val();
				var num_children 	= widget_instance.find('.vbo-widget-checkav-children').val();

				var bid = null;
				if (typeof checkbid !== 'undefined' && checkbid) {
					bid = checkbid;
				}

				// the widget method to call
				var call_method = 'loadRoomRates';

				// make a request to load the available room rates
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						offset: current_offset,
						length: length_per_page,
						checkin_date: checkin_date,
						num_nights: num_nights,
						num_adults: num_adults,
						num_children: num_children,
						bid: bid,
						wrapper: wrapper,
						tmpl: "component"
					},
					function(response) {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method) || !obj_res[call_method]) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// replace HTML with new available room rates
							widget_instance.find('.vbo-widget-checkav-results').html(obj_res[call_method]['html']);
							
							// check results
							var tot_records = obj_res[call_method]['tot_records'] || 0;
							if (!isNaN(tot_records) && parseInt(tot_records) < 1) {
								// no results can indicate the offset is invalid or too high
								if (!isNaN(current_offset) && parseInt(current_offset) > 0) {
									// reset offset to 0
									widget_instance.attr('data-offset', 0);
									// show loading skeletons
									vboWidgetCheckAvailabilitySkeletons(wrapper);
									// reload the first page
									vboWidgetCheckAvailabilityLoad(wrapper);
								}
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					function(error) {
						// remove the skeleton loading
						widget_instance.find('.vbo-widget-checkav-results').find('.vbo-dashboard-guest-activity-skeleton').remove();
						console.error(error);
					}
				);
			}

			/**
			 * Navigate between the various pages of the available room rates.
			 */
			function vboWidgetCheckAvailabilityNavigate(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading skeletons
				vboWidgetCheckAvailabilitySkeletons(wrapper);

				var instance_val = widget_instance.attr('data-instance');

				// current offset
				var current_offset = parseInt(widget_instance.attr('data-offset'));

				// events per page per type
				var steps = <?php echo $this->res_per_page; ?>;

				// check direction and update offsets for nav
				if (direction > 0) {
					// navigate forward
					widget_instance.attr('data-offset', (current_offset + steps));
				} else {
					// navigate backward
					var new_offset = current_offset - steps;
					new_offset = new_offset >= 0 ? new_offset : 0;
					widget_instance.attr('data-offset', new_offset);
				}

				// launch navigation
				vboWidgetCheckAvailabilityLoad(wrapper);
			}

			/**
			 * Calculate the available room rates.
			 */
			function vboWidgetCheckAvailabilityCalc(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading skeletons
				vboWidgetCheckAvailabilitySkeletons(wrapper);

				// always reset offset to the first page
				widget_instance.attr('data-offset', 0);

				// load data
				vboWidgetCheckAvailabilityLoad(wrapper);
			}

			/**
			 * Triggers when the multitask panel opens.
			 */
			function vboWidgetCheckAvailabilityMultitaskOpen(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// check if a booking ID was set for this page
				var page_bid = widget_instance.attr('data-pagebid');
				if (!page_bid || page_bid < 1) {
					return false;
				}

				// show loading skeletons
				vboWidgetCheckAvailabilitySkeletons(wrapper);

				// always reset offset to the first page
				widget_instance.attr('data-offset', 0);

				// load data by injecting the current booking ID
				vboWidgetCheckAvailabilityLoad(wrapper, page_bid);
			}

			/**
			 * Book a room rate.
			 */
			function vboWidgetCheckAvailabilityBook(wrapper, rid, din, dout, adults, children, rpid) {
				var open_url = jQuery('.vbo-widget-checkav-basenavuri').first().attr('href');
				open_url = open_url.replace('%d', rid);
				open_url = open_url.replace('%s', din);
				open_url = open_url.replace('%s', dout);
				open_url = open_url.replace('%d', adults);
				open_url = open_url.replace('%d', children);
				open_url = open_url.replace('%d', rpid);
				// navigate
				document.location.href = open_url;
			}

			/**
			 * Book a split stay.
			 */
			function vboWidgetCheckAvailabilityBookSplitStay(rid, adults, children, split_qstring) {
				var open_url = jQuery('.vbo-widget-checkav-splitstayuri').first().attr('href');
				open_url = open_url.replace('%d', rid);
				open_url = open_url.replace('%d', adults);
				open_url = open_url.replace('%d', children);
				open_url += '&' + split_qstring;
				// navigate
				document.location.href = open_url;
			}

			/**
			 * Pick alternative stay dates suggested for calculation.
			 */
			function vboWidgetCheckAvailabilityPickRoom(wrapper, rid, din, nights, adults, children) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading skeletons
				vboWidgetCheckAvailabilitySkeletons(wrapper);

				// always reset offset to the first page
				widget_instance.attr('data-offset', 0);

				// populate check-in date
				widget_instance.find('.vbo-widget-checkav-checkindt').datepicker('setDate', din);

				// set number of nights, adults and children
				widget_instance.find('.vbo-widget-checkav-nights').val(nights);
				widget_instance.find('.vbo-widget-checkav-adults').val(adults);
				widget_instance.find('.vbo-widget-checkav-children').val(children);

				// load data (we ignore the room ID passed to display all options available)
				vboWidgetCheckAvailabilityLoad(wrapper);
			}

		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			jQuery(function() {

				// render datepicker calendar for dates navigation
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-checkav-checkindt').datepicker({
					minDate: 0,
					maxDate: "+3y",
					yearRange: "<?php echo date('Y'); ?>:<?php echo (date('Y') + 3); ?>",
					changeMonth: true,
					changeYear: true,
					dateFormat: "<?php echo $dtpicker_df; ?>"
				});

				// triggering for datepicker calendar icon
				jQuery('#<?php echo $wrapper_id; ?>').find('.vbo-widget-checkav-checkindt-trigger').click(function() {
					var jdp = jQuery(this).parent().find('input.hasDatepicker');
					if (jdp.length) {
						jdp.focus();
					}
				});

				// subscribe to the multitask-panel-open event
				document.addEventListener(VBOCore.multitask_open_event, function() {
					vboWidgetCheckAvailabilityMultitaskOpen('<?php echo $wrapper_id; ?>');
				});

			<?php
			if ($modal_load_bid) {
				// immediately fire the adaptive rendering according to multitask data
				?>
				vboWidgetCheckAvailabilityMultitaskOpen('<?php echo $wrapper_id; ?>');
				<?php
			}
			?>

			});
			
		</script>

		<?php
	}

	/**
	 * Returns the maximum number of adults and children as a sum from all rooms.
	 * Internal method for this widget only.
	 * 
	 * @return 	array 	two values, respectively for highest adults and children.
	 */
	protected function getMaxestGuests()
	{
		$maxest = array(1, 1);

		$dbo = JFactory::getDbo();

		$q = "SELECT SUM(`toadult`) AS `max_adults`, SUM(`tochild`) AS `max_children` FROM `#__vikbooking_rooms`;";
		$dbo->setQuery($q);
		$record = $dbo->loadAssoc();
		if ($record) {
			$maxest = array($record['max_adults'], $record['max_children']);
		}

		return $maxest;
	}

	/**
	 * In case the multitask panel injects a booking ID, this method
	 * prepares the proper values to use for loading room rates.
	 * 
	 * @param 	int 	$bid 			the booking id.
	 * @param 	bool 	$multi_rooms 	whether to return array of guests
	 *									when multiple rooms booked.
	 * 
	 * @return 	array 	list of values to use for loading.
	 */
	protected function getBookingLoadValues($bid, $multi_rooms = true)
	{
		// set default values
		$checkin_date = '';
		$num_nights   = 0;
		$num_adults   = 0;
		$num_children = 0;
		$room_ids	  = [];

		if (empty($bid)) {
			// do nothing
			return [$checkin_date, $num_nights, $num_adults, $num_children, $room_ids];
		}

		// get booking details
		$booking = VikBooking::getBookingInfoFromID($bid);

		if (!is_array($booking) || !count($booking)) {
			// do nothing
			return [$checkin_date, $num_nights, $num_adults, $num_children, $room_ids];
		}

		// get reservation rooms
		$book_rooms = VikBooking::loadOrdersRoomsData($booking['id']);

		if (!is_array($book_rooms) || !count($book_rooms)) {
			// do nothing
			return [$checkin_date, $num_nights, $num_adults, $num_children, $room_ids];
		}

		// build proper values for this reservation
		$checkin_date = date('Y-m-d', $booking['checkin']);
		$num_nights   = $booking['days'];
		$arr_adults   = [];
		$arr_children = [];
		foreach ($book_rooms as $roomres) {
			$num_adults 	+= $roomres['adults'];
			$num_children 	+= $roomres['children'];
			$arr_adults[] 	= $roomres['adults'];
			$arr_children[] = $roomres['children'];
			$room_ids[] 	= $roomres['idroom'];
			if ($booking['split_stay']) {
				// do not sum the guests in case of split stay
				$num_adults   = $roomres['adults'];
				$num_children = $roomres['children'];
			}
		}

		if (count($book_rooms) > 1 && $multi_rooms === true && !$booking['split_stay']) {
			// return the adults and children as array rather than as integers
			$num_adults = $arr_adults;
			$num_children = $arr_children;
		}

		return [$checkin_date, $num_nights, $num_adults, $num_children, $room_ids];
	}
}
