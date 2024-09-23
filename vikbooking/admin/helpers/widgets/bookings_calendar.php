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
 * Class handler for admin widget "bookings calendar".
 * 
 * @since 	1.15.0 (J) - 1.5.0 (WP)
 */
class VikBookingAdminWidgetBookingsCalendar extends VikBookingAdminWidget
{
	/**
	 * The instance counter of this widget. Since we do not load individual parameters
	 * for each widget's instance, we use a static counter to determine its settings.
	 *
	 * @var 	int
	 */
	protected static $instance_counter = -1;

	/**
	 * Default number of bookings per page.
	 * 
	 * @var 	int
	 */
	protected $bookings_per_page = 6;

	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_BOOKSCAL_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_BOOKSCAL_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		// define widget and icon and style name
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('calendar') . '"></i>';
		$this->widgetStyleName = 'brown';
	}

	/**
	 * Custom method for this widget only to load the bookings calendar.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 */
	public function loadBookingsCalendar()
	{
		// get today's date
		$today_ymd = date('Y-m-d');

		$wrapper = VikRequest::getString('wrapper', '', 'request');
		$offset = VikRequest::getString('offset', $today_ymd, 'request');
		$room_id = VikRequest::getInt('room_id', 0, 'request');
		$date_dir = VikRequest::getInt('date_dir', 0, 'request');
		$bid = VikRequest::getInt('bid', 0, 'request');
		$bid = !empty($date_dir) ? 0 : $bid;

		// the booking ID can be passed in case of multitask data for this page
		if (!empty($bid)) {
			// load the availability of the month when booking starts
			$booking_info = VikBooking::getBookingInfoFromID($bid);
			if ($booking_info) {
				// force the offset to use as start date
				$offset = date('Y-m-d', $booking_info['checkin']);
			}
		}

		// calculate date timestamps interval
		$now_info = getdate(strtotime($offset));
		if ($date_dir > 0) {
			// next month from current offset
			$from_ts = mktime(0, 0, 0, ($now_info['mon'] + 1), 1, $now_info['year']);
			$to_ts = mktime(23, 59, 59, ($now_info['mon'] + 1), date('t', $from_ts), $now_info['year']);
		} elseif ($date_dir < 0) {
			// prev month from current offset
			$from_ts = mktime(0, 0, 0, ($now_info['mon'] - 1), 1, $now_info['year']);
			$to_ts = mktime(23, 59, 59, ($now_info['mon'] - 1), date('t', $from_ts), $now_info['year']);
		} else {
			// no navigation, use current offset
			$from_ts = mktime(0, 0, 0, $now_info['mon'], 1, $now_info['year']);
			$to_ts = mktime(23, 59, 59, $now_info['mon'], date('t', $now_info[0]), $now_info['year']);
		}

		// build week days list according to settings
		$firstwday = (int)VikBooking::getFirstWeekDay();
		$days_labels = array(
			JText::translate('VBSUN'),
			JText::translate('VBMON'),
			JText::translate('VBTUE'),
			JText::translate('VBWED'),
			JText::translate('VBTHU'),
			JText::translate('VBFRI'),
			JText::translate('VBSAT'),
		);
		$days_indexes = [];
		for ($i = 0; $i < 7; $i++) {
			$days_indexes[$i] = (6 - ($firstwday - $i) + 1) % 7;
		}

		// currency symbol
		$currencysymb = VikBooking::getCurrencySymb();

		// start looping from the first day of the current month
		$info_arr = getdate($from_ts);

		// build period name
		$period_date = VikBooking::sayMonth($info_arr['mon']) . ' ' . $info_arr['year'];

		// invoke availability helper class
		$av_helper = VikBooking::getAvailabilityInstance();

		// get all rooms and tax rates
		$all_rooms = $av_helper->loadRooms();
		$tax_rates = $av_helper->getTaxRates();

		// count maximum units available depending on filter
		$tot_rooms = 1;
		if (!empty($room_id) && isset($all_rooms[$room_id])) {
			// use the units of the filtered room
			$max_units = $all_rooms[$room_id]['units'];
		} else {
			// sum all room units
			$max_units = 0;
			$tot_rooms = count($all_rooms);
			foreach ($all_rooms as $rid => $room) {
				$max_units += $room['units'];
			}
		}

		// build "search name"
		if (!empty($room_id) && isset($all_rooms[$room_id])) {
			$search_name = $all_rooms[$room_id]['name'];
		} else {
			$search_name = preg_replace("/[^A-Za-z0-9 ]/", '', JText::translate('VBOSTATSALLROOMS'));
		}

		// load busy records
		$room_filter = !empty($room_id) ? array($room_id) : array();
		$busy_records = VikBooking::loadBusyRecords($room_filter, $from_ts, $to_ts);

		// load festivities or room-day notes
		$festivities = [];
		$rday_notes  = [];
		if (!empty($room_id) && isset($all_rooms[$room_id])) {
			// load room-day notes for the given room id
			$rday_notes = VikBooking::getCriticalDatesInstance()->loadRoomDayNotes(date('Y-m-d', $from_ts), date('Y-m-d', $to_ts), $room_id);
		} else {
			// load festivities when no specific room filter set
			$fests = VikBooking::getFestivitiesInstance();
			if ($fests->shouldCheckFestivities()) {
				$fests->storeNextFestivities();
			}
			$festivities = $fests->loadFestDates(date('Y-m-d', $from_ts), date('Y-m-d', $to_ts));
		}

		// date format
		$dtpicker_df = $this->getDateFormat('jui');

		// start output buffering
		ob_start();

		// generate calendar
		$d_count = 0;
		$mon_lim = $info_arr['mon'];
		$next_offset = date('Y-m-d', $from_ts);

		?>
		<div class="vbo-widget-booskcal-mday-wrap" style="display: none;">
			<div class="vbo-widget-booskcal-mday-head">
				<a class="vbo-widget-booskcal-mday-back" href="JavaScript: void(0);" onclick="vboWidgetBooksCalMonth('<?php echo $wrapper; ?>');"><?php VikBookingIcons::e('chevron-left'); ?> <?php echo $period_date; ?></a>
				<span class="vbo-widget-booskcal-mday-name"></span>
			</div>
			<div class="vbo-dashboard-guests-latest vbo-widget-booskcal-mday-list" data-ymd="" data-offset="0" data-length="<?php echo $this->bookings_per_page; ?>"></div>
		</div>

		<div class="vbo-widget-booskcal-newbook-wrap" data-ymd="" data-roomid="" style="display: none;">
			<div class="vbo-widget-booskcal-newbook-head">
				<a class="vbo-widget-booskcal-newbook-back" href="JavaScript: void(0);" onclick="vboWidgetBooksCalMonth('<?php echo $wrapper; ?>');"><?php VikBookingIcons::e('chevron-left'); ?> <?php echo JText::translate('VBANNULLA'); ?></a>
				<span class="vbo-widget-booskcal-newbook-name"><?php echo JText::translate('VBQUICKBOOK'); ?></span>
			</div>
			<div class="vbo-widget-booskcal-newbook-cont">
				<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
					<div class="vbo-params-wrap">
						<div class="vbo-params-container">

							<div class="vbo-param-container">
								<div class="vbo-param-label"><?php echo JText::translate('VBPICKUPAT'); ?></div>
								<div class="vbo-param-setting">
									<div class="vbo-field-calendar">
										<div class="input-append">
											<input type="text" class="vbo-widget-bookscal-checkindt" value="" autocomplete="off" />
											<button type="button" class="btn btn-secondary vbo-widget-bookscal-checkindt-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
										</div>
									</div>
								</div>
							</div>

							<div class="vbo-param-container">
								<div class="vbo-param-label"><?php echo JText::translate('VBRELEASEAT'); ?></div>
								<div class="vbo-param-setting">
									<div class="vbo-field-calendar">
										<div class="input-append">
											<input type="text" class="vbo-widget-bookscal-checkoutdt" value="" autocomplete="off" />
											<button type="button" class="btn btn-secondary vbo-widget-bookscal-checkoutdt-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
										</div>
									</div>
								</div>
							</div>

							<div class="vbo-param-container vbo-toggle-small">
								<div class="vbo-param-label"><?php echo JText::translate('VBSUBMCLOSEROOM'); ?></div>
								<div class="vbo-param-setting">
									<?php echo $this->vbo_app->printYesNoButtons('closeroom', JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0, "vboWidgetBooksCalClosure('{$wrapper}');"); ?>
								</div>
							</div>

							<div class="vbo-param-container" data-noclosure="1">
								<div class="vbo-param-label"><?php echo JText::translate('VBOCUSTOMER'); ?></div>
								<div class="vbo-param-setting">
									<span class="vbo-assign-customer" onclick="vboWidgetBooksCalAssignCustomer('<?php echo $wrapper; ?>');">
										<?php VikBookingIcons::e('user-circle'); ?>
										<span><?php echo JText::translate('VBFILLCUSTFIELDS'); ?></span>
									</span>
									<input type="hidden" value="" class="vbo-widget-bookscal-custid" />
									<input type="hidden" value="" class="vbo-widget-bookscal-custmail" />
									<input type="hidden" value="" class="vbo-widget-bookscal-custdata" />
									<input type="hidden" value="" class="vbo-widget-bookscal-country" />
									<input type="hidden" value="" class="vbo-widget-bookscal-state" />
									<input type="hidden" value="" class="vbo-widget-bookscal-phone" />
									<input type="hidden" value="" class="vbo-widget-bookscal-tfname" />
									<input type="hidden" value="" class="vbo-widget-bookscal-tlname" />
									<input type="hidden" value="" class="vbo-widget-bookscal-roomcost" />
									<input type="hidden" value="" class="vbo-widget-bookscal-idprice" />
								</div>
							</div>

							<div class="vbo-param-container" data-noclosure="1">
								<div class="vbo-param-label"><?php echo JText::translate('VBPVIEWROOMSEVEN'); ?></div>
								<div class="vbo-param-setting">
									<input type="number" class="vbo-input-number-small vbo-widget-bookscal-units" value="1" min="1" max="99" onchange="vboWidgetBooksCalGetWebsiteRates('<?php echo $wrapper; ?>');" />
								</div>
							</div>

							<div class="vbo-param-container" data-noclosure="1">
								<div class="vbo-param-label"><?php echo JText::translate('VBPVIEWORDERSPEOPLE'); ?></div>
								<div class="vbo-param-setting">
									<span class="vbo-quickres-aduchi-wrap">
										<span class="vbo-quickres-aduchi-inlbl"><?php echo JText::translate('VBEDITORDERADULTS'); ?></span>
										<input type="number" class="vbo-input-number-small vbo-widget-bookscal-adults" value="2" min="0" max="99" onchange="vboWidgetBooksCalGetWebsiteRates('<?php echo $wrapper; ?>');" />
									</span>
									<span class="vbo-quickres-aduchi-wrap">
										<span class="vbo-quickres-aduchi-inlbl"><?php echo JText::translate('VBEDITORDERCHILDREN'); ?></span>
										<input type="number" class="vbo-input-number-small vbo-widget-bookscal-children" value="0" min="0" max="99" />
									</span>
								</div>
							</div>

							<div class="vbo-param-container vbo-website-rates-row" data-noclosure="1" data-unavailable="1" style="display: none;">
								<div class="vbo-param-label"><?php echo JText::translate('VBOWEBSITERATES'); ?></div>
								<div class="vbo-param-setting">
									<div class="vbo-website-rates-cont"></div>
								</div>
							</div>

							<div class="vbo-param-container vbo-row-custcost" data-noclosure="1">
								<div class="vbo-param-label"><?php echo JText::translate('VBOROOMCUSTRATEPLANADD'); ?></div>
								<div class="vbo-param-setting">
									<div class="vbo-calendar-costs-wrapper">
										<?php echo $currencysymb; ?> <input type="number" class="vbo-widget-bookscal-custcost" value="" step="any" min="0" onfocus="vboWidgetBooksCalFocusTaxes('<?php echo $wrapper; ?>');" />
									<?php
									if ($tax_rates) {
										?>
										<select class="vbo-widget-bookscal-taxid" style="display: none;">
											<option value=""><?php echo JText::translate('VBNEWOPTFOUR'); ?></option>
										<?php
										foreach ($tax_rates as $kiv => $iv) {
											?>
											<option value="<?php echo $iv['id']; ?>"<?php echo $kiv < 1 ? ' selected="selected"' : ''; ?>><?php echo empty($iv['name']) ? "{$iv['aliq']}%" : "{$iv['name']} - {$iv['aliq']}%"; ?></option>
											<?php
										}
										?>
										</select>
										<?php
									}
									?>
									</div>
								</div>
							</div>

							<div class="vbo-param-container vbo-param-confirm-btn">
								<div class="vbo-param-label"></div>
								<div class="vbo-param-setting">
									<button type="button" class="btn btn-success vbo-btn-wide" onclick="vboWidgetBooksCalSaveBooking('<?php echo $wrapper; ?>');"><?php VikBookingIcons::e('save'); ?> <?php echo JText::translate('VBSAVE'); ?></button>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</div>

		<table class="vbadmincaltable vbo-widget-booskcal-calendar-table">
			<tbody>
				<tr class="vbadmincaltrmdays">
				<?php
				// display week days in the proper order
				for ($i = 0; $i < 7; $i++) {
					$d_ind = ($i + $firstwday) < 7 ? ($i + $firstwday) : ($i + $firstwday - 7);
					?>
					<td class="vbo-widget-booskcal-cell-wday"><?php echo $days_labels[$d_ind]; ?></td>
					<?php
				}
				?>
				</tr>
				<tr>
				<?php
				// display empty cells until the first week-day of the month
				for ($i = 0, $n = $days_indexes[$info_arr['wday']]; $i < $n; $i++, $d_count++) {
					?>
					<td class="vbo-widget-booskcal-cell-mday vbo-widget-booskcal-cell-empty">&nbsp;</td>
					<?php
				}
				// display month days
				while ($info_arr['mon'] == $mon_lim) {
					if ($d_count > 6) {
						$d_count = 0;
						// close current row and open a new one
						echo "\n</tr>\n<tr>\n";
					}
					// count units booked on this day
					$tot_units_booked = 0;
					$cell_classes = [];
					$cell_bids = [];
					foreach ($busy_records as $rid => $rbusy) {
						foreach ($rbusy as $b) {
							$tmpone = getdate($b['checkin']);
							$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
							$tmptwo = getdate($b['checkout']);
							$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
							if ($info_arr[0] >= $ritts && $info_arr[0] < $conts) {
								// increase units booked
								$tot_units_booked++;
								if ($tot_rooms === 1) {
									if (!empty($b['closure'])) {
										// hightlight that this was a closure
										$cell_classes[] = 'busy-closure';
									} elseif (!empty($b['sharedcal'])) {
										// hightlight that this was a reflection from a shared calendar
										$cell_classes[] = 'busy-sharedcalendar';
									}
								}
								// check if we can push the booking ID involved
								if (!empty($b['idorder'])) {
									$cell_bids[] = $b['idorder'];
								}
							}
						}
					}
					// check status for this day
					if ($tot_units_booked > 0) {
						if ($tot_units_booked < $max_units) {
							// prepend the "partially-busy" class
							array_unshift($cell_classes, 'vbo-partially');
						}
						// prepend the "busy" cell class so that this will be first
						array_unshift($cell_classes, 'busy');
					} else {
						// set the "free" cell class
						$cell_classes[] = 'free';
					}
					// set ymd values
					$cell_ymd = date('Y-m-d', $info_arr[0]);
					if ($cell_ymd == $today_ymd) {
						// set the "today" cell class
						$cell_classes[] = 'is-today';
					}
					$cell_day_read = VikBooking::sayWeekDay($info_arr['wday']) . ' ' . $info_arr['mday'];

					// count values for this day
					$has_fests = isset($festivities[$cell_ymd]);
					$rdnotes_key = $cell_ymd . '_' . $room_id . '_0';
					$has_rdnotes = isset($rday_notes[$rdnotes_key]);

					?>
					<td class="vbo-widget-booskcal-cell-mday <?php echo implode(' ', $cell_classes); ?>" onclick="vboWidgetBooksCalMday('<?php echo $wrapper; ?>', this);" data-bids="<?php echo implode(',', array_unique($cell_bids)); ?>" data-ymd="<?php echo $cell_ymd; ?>" data-dayread="<?php echo htmlspecialchars($cell_day_read); ?>">
						<span class="vbo-widget-booskcal-mday-val"><?php echo $info_arr['mday']; ?></span>
					<?php
					if ($has_fests || $has_rdnotes) {
						?>
						<div class="vbo-widget-booskcal-mday-info">
						<?php
						if ($has_fests) {
							?>
							<span class="vbo-widget-booskcal-mday-fests"><?php VikBookingIcons::e('birthday-cake'); ?></span>
							<?php
						}
						if ($has_rdnotes) {
							?>
							<span class="vbo-widget-booskcal-mday-rdnotes"><?php VikBookingIcons::e('sticky-note'); ?></span>
							<?php
						}
						?>
						</div>
						<?php
					}
					?>
					</td>
					<?php
					$dayts = mktime(0, 0, 0, $info_arr['mon'], ($info_arr['mday'] + 1), $info_arr['year']);
					$info_arr = getdate($dayts);
					$d_count++;
				}
				// add empty cells until the end of the row
				for ($i = $d_count; $i <= 6; $i++) {
					?>
					<td class="vbo-widget-booskcal-cell-mday vbo-widget-booskcal-cell-empty">&nbsp;</td>
					<?php
				}
				?>
				</tr>
			</tbody>
		</table>

		<script type="text/javascript">

			// render datepicker calendar for dates navigation
			jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bookscal-checkindt, .vbo-widget-bookscal-checkoutdt').datepicker({
				minDate: "-1m",
				maxDate: "+3y",
				yearRange: "<?php echo date('Y'); ?>:<?php echo (date('Y') + 3); ?>",
				changeMonth: true,
				changeYear: true,
				dateFormat: "<?php echo $dtpicker_df; ?>",
				onSelect: function(selectedDate) {
					if (!selectedDate) {
						return;
					}
					if (jQuery(this).hasClass('vbo-widget-bookscal-checkindt')) {
						let nowstart = jQuery(this).datepicker('getDate');
						let nowstartdate = new Date(nowstart.getTime());
						jQuery('.vbo-widget-bookscal-checkoutdt').datepicker('option', {minDate: nowstartdate});
					}
					// calculate website rates
					vboWidgetBooksCalGetWebsiteRates('<?php echo $wrapper; ?>');
				}
			});

			// triggering for datepicker calendar icon
			jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bookscal-checkindt-trigger, .vbo-widget-bookscal-checkoutdt-trigger').click(function() {
				var jdp = jQuery(this).parent().find('input.hasDatepicker');
				if (jdp.length) {
					jdp.focus();
				}
			});

			// check room units
			var vbo_bookscal_roomfilt = jQuery('#<?php echo $wrapper; ?>').find('.vbo-booskcal-roomid');
			if (vbo_bookscal_roomfilt.val()) {
				var vbo_bookscal_room_units = vbo_bookscal_roomfilt.find('option:selected').attr('data-units');
				if (vbo_bookscal_room_units > 1) {
					jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bookscal-units').attr('max', vbo_bookscal_room_units);
				} else {
					jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bookscal-units').closest('[data-noclosure="1"]').hide();
				}
			} else {
				jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-bookscal-units').closest('[data-noclosure="1"]').hide();
			}

		<?php
		// check if a specific day was requested through the widget options
		$force_day = $this->getOption('day', '');
		if ($force_day && preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $force_day)) {
			// trigger the click event over the requested day
			?>
			setTimeout(() => {
				jQuery('.vbo-widget-booskcal-cell-mday[data-ymd="<?php echo $force_day; ?>"]').trigger('click');
			}, 100);
			<?php
		}
		?>

		</script>
		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return array(
			'html' 		  => $html_content,
			'offset' 	  => $next_offset,
			'search_name' => $search_name,
			'period_date' => $period_date,
		);
	}

	/**
	 * Custom method for this widget only to load the bookings of a month-day.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, it should not exit the process, and
	 * any content sent to output will be returned to the AJAX response.
	 * In this case we return an array because this method requires "return":1.
	 */
	public function loadMdayBookings()
	{
		// get today's date
		$today_ymd = date('Y-m-d');

		$page_offset = VikRequest::getInt('page_offset', 0, 'request');
		$page_length = VikRequest::getInt('page_length', $this->bookings_per_page, 'request');
		$ymd = VikRequest::getString('ymd', $today_ymd, 'request');
		$wrapper = VikRequest::getString('wrapper', '', 'request');
		$room_id = VikRequest::getInt('room_id', 0, 'request');

		// calculate date timestamps interval for the given day
		$day_info = getdate(strtotime($ymd));
		$from_ts = mktime(0, 0, 0, $day_info['mon'], $day_info['mday'], $day_info['year']);
		$to_ts = mktime(23, 59, 59, $day_info['mon'], $day_info['mday'], $day_info['year']);

		// load busy records
		$room_filter = !empty($room_id) ? array($room_id) : array();
		$busy_records = VikBooking::loadBusyRecords($room_filter, $from_ts, $to_ts);

		// gather all bookings touching this day
		$booking_ids = [];
		foreach ($busy_records as $rid => $rbusy) {
			foreach ($rbusy as $b) {
				$tmpone = getdate($b['checkin']);
				$ritts = mktime(0, 0, 0, $tmpone['mon'], $tmpone['mday'], $tmpone['year']);
				$tmptwo = getdate($b['checkout']);
				$conts = mktime(0, 0, 0, $tmptwo['mon'], $tmptwo['mday'], $tmptwo['year']);
				if ($from_ts >= $ritts && $from_ts < $conts) {
					if (empty($b['idorder']) || in_array($b['idorder'], $booking_ids)) {
						continue;
					}
					array_push($booking_ids, $b['idorder']);
				}
			}
		}

		// invoke availability helper class
		$av_helper = VikBooking::getAvailabilityInstance();

		// collect booking information
		$booking_details = [];
		foreach ($booking_ids as $bid) {
			$booking = $av_helper->getBookingDetails($bid);
			if (!is_array($booking) || !$booking) {
				continue;
			}
			$booking_details[$bid] = $booking;
		}

		// check if a next page can be available
		$tot_bookings  = count($booking_details);
		$has_next_page = ($tot_bookings > ($page_length + $page_offset));

		// slice the records, if needed
		if ($tot_bookings > $page_length) {
			$booking_details = array_slice($booking_details, $page_offset, $page_length, true);
		}

		// load cancellations for today (only if a room ID filter is set)
		$cancellations = [];
		if (!empty($room_id)) {
			$cancellations = $this->loadCancellations($room_id, $ymd);
		}

		// load festivities or room-day notes
		$festivities = [];
		$rday_notes  = [];
		if (!empty($room_id)) {
			// load room-day notes for the given room id
			$rday_notes = VikBooking::getCriticalDatesInstance()->loadRoomDayNotes(date('Y-m-d', $from_ts), date('Y-m-d', $to_ts), $room_id);
		} else {
			// load festivities when no specific room filter set
			$festivities = VikBooking::getFestivitiesInstance()->loadFestDates(date('Y-m-d', $from_ts), date('Y-m-d', $to_ts));
		}

		// start output buffering
		ob_start();

		if ($festivities) {
			// display the festivities for this day
			?>
			<div class="vbo-widget-booskcal-events vbo-widget-booskcal-fests">
			<?php
			foreach ($festivities as $fest_ymd => $fest) {
				if (empty($fest['festinfo']) || !is_array($fest['festinfo'])) {
					continue;
				}
				foreach ($fest['festinfo'] as $fest_info) {
					if (!is_object($fest_info) || empty($fest_info->trans_name)) {
						continue;
					}
					?>
				<div class="vbo-widget-booskcal-event vbo-widget-booskcal-fest">
					<strong><?php echo $fest_info->trans_name; ?></strong>
					<?php
					if (!empty($fest_info->descr)) {
						?>
					<div><?php echo nl2br($fest_info->descr); ?></div>
						<?php
					}
					?>
				</div>
					<?php
				}
			}
			?>
			</div>
			<?php
		}

		if ($rday_notes) {
			// display the room-day notes for this day
			?>
			<div class="vbo-widget-booskcal-events vbo-widget-booskcal-rdaynotes">
			<?php
			foreach ($rday_notes as $rday_note) {
				if (empty($rday_note['info']) || !is_array($rday_note['info'])) {
					continue;
				}
				foreach ($rday_note['info'] as $note_info) {
					if (!is_object($note_info) || empty($note_info->name)) {
						continue;
					}
					?>
				<div class="vbo-widget-booskcal-event vbo-widget-booskcal-rdaynote">
					<strong><?php echo $note_info->name; ?></strong>
					<?php
					if (!empty($note_info->descr)) {
						?>
					<div><?php echo nl2br($note_info->descr); ?></div>
						<?php
					}
					?>
				</div>
					<?php
				}
			}
			?>
			</div>
			<?php
		}

		if (!$booking_details && !$cancellations) {
			?>
			<p class="info"><?php echo JText::translate('VBNOORDERSFOUND'); ?></p>
			<?php
		} else {
			if (!$booking_details) {
				?>
			<p class="info"><?php echo JText::translate('VBNOORDERSFOUND'); ?></p>
				<?php
			}

			// merge confirmed bookings with cancellations (if any)
			$booking_details = array_merge($booking_details, $cancellations);

			// display all bookings of this day
			$canc_separator = false;
			foreach ($booking_details as $booking) {
				// get channel logo and other details
				$ch_logo_obj  = VikBooking::getVcmChannelsLogo($booking['channel'], true);
				$channel_logo = is_object($ch_logo_obj) ? $ch_logo_obj->getSmallLogoURL() : '';
				$nights_lbl = $booking['days'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY');
				$rooms_lbl = !empty($booking['roomsnum']) && $booking['roomsnum'] > 1 ? ', ' . $booking['roomsnum'] . ' ' . JText::translate('VBPVIEWORDERSTHREE') : '';
				// compose customer name
				$customer_name = !empty($booking['customer_fullname']) ? $booking['customer_fullname'] : '';
				if ($booking['closure'] > 0 || !strcasecmp($booking['custdata'], JText::translate('VBDBTEXTROOMCLOSED'))) {
					$customer_name = '<span class="vbordersroomclosed"><i class="' . VikBookingIcons::i('ban') . '"></i> ' . JText::translate('VBDBTEXTROOMCLOSED') . '</span>';
				}
				if (empty($customer_name)) {
					$customer_name = VikBooking::getFirstCustDataField($booking['custdata']);
				}
				// customer country flag
				$customer_country = '';
				$customer_cflag   = '';
				if (!empty($booking['customer_country'])) {
					$customer_country = $booking['customer_country'];
				} elseif (!empty($booking['country'])) {
					$customer_country = $booking['country'];
				}
				if ($customer_country && is_file(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'countries' . DIRECTORY_SEPARATOR . $customer_country . '.png')) {
					$customer_cflag = '<img src="'.VBO_ADMIN_URI.'resources/countries/' . $customer_country . '.png'.'" title="' . htmlspecialchars($customer_country) . '" class="vbo-country-flag vbo-country-flag-left"/>';
				}

				// check for cancellations
				$elem_style = $booking['status'] == 'cancelled' ? 'display: none;' : '';
				if ($booking['status'] == 'cancelled' && !$canc_separator) {
					// display the button to show the cancellations
					$canc_separator = true;
					?>
				<div class="vbo-bookings-status-separator">
					<button type="button" class="btn btn-small btn-secondary" onclick="vboWidgetBooksCalCancToggle('<?php echo $wrapper; ?>');"><?php echo JText::translate('VBO_SHOW_CANCELLATIONS'); ?></button>
				</div>
					<?php
				}

				?>
				<div class="vbo-dashboard-guest-activity vbo-widget-booskcal-reservation" data-type="<?php echo $booking['status']; ?>" data-resid="<?php echo $booking['id']; ?>" style="<?php echo $elem_style; ?>">
					<div class="vbo-dashboard-guest-activity-avatar">
					<?php
					if (!empty($channel_logo)) {
						// channel logo has got the highest priority
						?>
						<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo $channel_logo; ?>" />
						<?php
					} elseif (!empty($booking['pic'])) {
						// customer profile picture
						?>
						<img class="vbo-dashboard-guest-activity-avatar-profile" src="<?php echo strpos($booking['pic'], 'http') === 0 ? $booking['pic'] : VBO_SITE_URI . 'resources/uploads/' . $booking['pic']; ?>" />
						<?php
					} else {
						// we use an icon as fallback
						VikBookingIcons::e('hotel', 'vbo-dashboard-guest-activity-avatar-icon');
					}
					?>
					</div>
					<div class="vbo-dashboard-guest-activity-content">
						<div class="vbo-dashboard-guest-activity-content-head">
							<div class="vbo-dashboard-guest-activity-content-info-details">
								<h4><?php echo $customer_name . $customer_cflag; ?></h4>
								<div class="vbo-dashboard-guest-activity-content-info-icon">
								<?php
								if (!strcasecmp($booking['type'], 'overbooking')) {
									?>
									<span class="label label-error vbo-label-overbooking"><?php echo JText::translate('VBO_BTYPE_' . strtoupper($booking['type'])); ?></span>
									<?php
								}
								if ($booking['status'] == 'cancelled') {
									?>
									<span class="badge badge-danger"><?php echo JText::translate('VBCANCELLED'); ?></span>
									<?php
								}
								?>
									<span><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo date(str_replace("/", $this->datesep, $this->df), $booking['checkin']); ?> - <?php echo $booking['days'] . ' ' . $nights_lbl . $rooms_lbl; ?></span>
								</div>
							</div>
							<div class="vbo-dashboard-guest-activity-content-info-date">
								<span>
									<span class="label label-info"><?php echo $booking['id']; ?></span>
								</span>
								<span><?php echo date(str_replace("/", $this->datesep, $this->df) . ' H:i', $booking['ts']); ?></span>
							</div>
						</div>
					</div>
				</div>
				<?php
			}

			// append navigation
			?>
				<div class="vbo-widget-commands vbo-widget-commands-right">
					<div class="vbo-widget-commands-main">
					<?php
					if ($page_offset > 0) {
						// show backward navigation button
						?>
						<div class="vbo-widget-command-chevron vbo-widget-command-prev">
							<span class="vbo-widget-command-chevron-prev" onclick="vboWidgetBooksCalMdayNavigate('<?php echo $wrapper; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
						</div>
						<?php
					}
					if ($has_next_page) {
						// show forward navigation button
						?>
						<div class="vbo-widget-command-chevron vbo-widget-command-next">
							<span class="vbo-widget-command-chevron-next" onclick="vboWidgetBooksCalMdayNavigate('<?php echo $wrapper; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
						</div>
					<?php
					}
					?>
					</div>
				</div>
			<?php

			// append JS code
			?>
				<script type="text/javascript">

					setTimeout(() => {
						jQuery('#<?php echo $wrapper; ?>').find('.vbo-widget-booskcal-reservation').on('click', function(e) {
							let bid = jQuery(this).attr('data-resid');
							let target = jQuery(e.target);
							if (target.is('img.vbo-dashboard-guest-activity-avatar-profile') || target.is('i.vbo-dashboard-guest-activity-avatar-icon') || target.is('span.label-info')) {
								// render booking details within the widget booking details
								VBOCore.handleDisplayWidgetNotification({widget_id: 'booking_details'}, {
									bid: bid,
									modal_options: {
										/**
										 * Overwrite modal options for rendering the admin widget.
										 * We need to use a different suffix in case this current widget was
										 * also rendered within a modal, or it would get dismissed in favour
										 * of the newly opened admin widget.
										 */
										suffix: 'widget_modal_inner_booking_details',
									},
								});
							} else {
								// navigate to booking details page
								vboWidgetBooksCalOpenBooking(bid);
							}
						});
					}, 300);

				</script>
			<?php
		}

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return array(
			'html' 		   => $html_content,
			'tot_bookings' => $tot_bookings,
			'next_page'    => (int)$has_next_page,
		);
	}

	/**
	 * Custom method for this widget only to load the custom fields for the new booking.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, and an array is requested to be returned.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function displayCustomerFilling()
	{
		$dbo = JFactory::getDbo();

		$wrapper = VikRequest::getString('wrapper', '', 'request');

		// load custom fields and countries
		$all_countries = [];
		$q = "SELECT * FROM `#__vikbooking_custfields` ORDER BY `#__vikbooking_custfields`.`ordering` ASC;";
		$dbo->setQuery($q);
		$all_cfields = $dbo->loadAssocList();
		if ($all_cfields) {
			$q = "SELECT * FROM `#__vikbooking_countries` ORDER BY `#__vikbooking_countries`.`country_name` ASC;";
			$dbo->setQuery($q);
			$all_countries = $dbo->loadAssocList();
		}

		// start output buffering
		ob_start();

		?>
		<div class="vbo-calendar-cfields-filler" data-wrapper="<?php echo $wrapper; ?>">
			<div class="vbo-calendar-cfields-topcont">
				<div class="vbo-calendar-cfields-search">
					<label for="vbo-searchcust<?php echo $wrapper; ?>"><?php echo JText::translate('VBOSEARCHEXISTCUST'); ?></label>
					<span id="vbo-searchcust-loading<?php echo $wrapper; ?>" class="vbo-searchcust-loading">
						<i class="vboicn-hour-glass"></i>
					</span>
					<input type="text" id="vbo-searchcust<?php echo $wrapper; ?>" class="vbo-searchcust" autocomplete="off" value="" placeholder="<?php echo htmlspecialchars(JText::translate('VBOSEARCHCUSTBY')); ?>" size="35" />
					<div id="vbo-searchcust-res<?php echo $wrapper; ?>" class="vbo-searchcust-res"></div>
				</div>
			</div>
			<div class="vbo-calendar-cfields-inner vbo-widget-bookscal-cfields-inner">
				<input type="hidden" value="" id="vbo-widget-bookscal-cfield-custid<?php echo $wrapper; ?>" />
		<?php
		foreach ($all_cfields as $cfield) {
			if ($cfield['type'] == 'text' && $cfield['isphone'] == 1) {
				?>
				<div class="vbo-calendar-cfield-entry">
					<label for="cfield<?php echo $cfield['id'] . $wrapper; ?>"><?php echo JText::translate($cfield['name']); ?></label>
					<span>
						<?php
						echo $this->vbo_app->printPhoneInputField(
							[
								'id' 				=> 'cfield' . $cfield['id'] . $wrapper,
								'class' 			=> 'vbo-calendar-cfield-phone',
								'data-isemail' 		=> '0',
								'data-isnominative' => '0',
								'data-isphone' 		=> '1'
							],
							[
								'fullNumberOnBlur' 	=> true
							], 
							$load_assets = false
						);
						?>
					</span>
				</div>
				<?php
			} elseif ($cfield['type'] == 'text') {
				?>
				<div class="vbo-calendar-cfield-entry">
					<label for="cfield<?php echo $cfield['id'] . $wrapper; ?>"><?php echo JText::translate($cfield['name']); ?></label>
					<span>
						<input type="text" id="cfield<?php echo $cfield['id'] . $wrapper; ?>" data-isemail="<?php echo ($cfield['isemail'] == 1 ? '1' : '0'); ?>" data-isnominative="<?php echo ($cfield['isnominative'] == 1 ? '1' : '0'); ?>" data-isphone="0" value="" size="35"/>
					</span>
				</div>
				<?php
			} elseif ($cfield['type'] == 'textarea') {
				?>
				<div class="vbo-calendar-cfield-entry">
					<label for="cfield<?php echo $cfield['id'] . $wrapper; ?>"><?php echo JText::translate($cfield['name']); ?></label>
					<span>
						<textarea id="cfield<?php echo $cfield['id'] . $wrapper; ?>" rows="4" cols="35"></textarea>
					</span>
				</div>
				<?php
			} elseif ($cfield['type'] == 'country') {
				?>
				<div class="vbo-calendar-cfield-entry">
					<label for="cfield<?php echo $cfield['id'] . $wrapper; ?>"><?php echo JText::translate($cfield['name']); ?></label>
					<span>
						<select id="cfield<?php echo $cfield['id'] . $wrapper; ?>" class="vbo-calendar-cfield-country" onchange="vboWidgetBooksCalChangeCountry(this, '<?php echo $wrapper; ?>');">
							<option value=""> </option>
						<?php
						foreach ($all_countries as $country) {
							?>
							<option value="<?php echo $country['country_name']; ?>" data-ccode="<?php echo $country['country_3_code']; ?>" data-c2code="<?php echo $country['country_2_code']; ?>"><?php echo $country['country_name']; ?></option>
							<?php
						}
						?>
						</select>
					</span>
				</div>
				<?php
			} elseif ($cfield['type'] == 'state') {
				?>
				<div class="vbo-calendar-cfield-entry">
					<label for="cfield<?php echo $cfield['id'] . $wrapper; ?>"><?php echo JText::translate($cfield['name']); ?></label>
					<span>
						<select id="cfield<?php echo $cfield['id'] . $wrapper; ?>" class="vbo-calendar-cfield-state">
							<option value="">-----</option>
						</select>
					</span>
				</div>
				<?php
			}
		}
		?>
			</div>
		</div>

		<script type="text/javascript">

			/**
			 * Pool of customers previous information collected.
			 */
			window['customers_search_vals<?php echo $wrapper; ?>'] = {};

			/**
			 * Search among the customers for the given keyword.
			 */
			function vboWidgetBooksCalCustomerSearch(words) {
				jQuery('#vbo-searchcust-res<?php echo $wrapper; ?>').hide().html('');
				jQuery('#vbo-searchcust-loading<?php echo $wrapper; ?>').show();

				VBOCore.doAjax(
					"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=searchcustomer'); ?>",
					{
						kw: words,
						selector: 'vbo-widget-custsearchres-entry',
						no_script: 1,
						tmpl: "component"
					},
					(cont) => {
						// parse the response
						if (cont) {
							// populate values
							var obj_res = typeof cont === 'string' ? JSON.parse(cont) : cont;
							window['customers_search_vals<?php echo $wrapper; ?>'] = obj_res[0];
							jQuery('#vbo-searchcust-res<?php echo $wrapper; ?>').html(obj_res[1]);

							setTimeout(() => {
								// de-register listeners
								jQuery('#vbo-searchcust<?php echo $wrapper; ?>').off('keydown');
								jQuery('#vbo-searchcust-res<?php echo $wrapper; ?>').find('.vbo-widget-custsearchres-entry').off('hover');

								// register listeners
								var vboCust = jQuery('#vbo-searchcust-res<?php echo $wrapper; ?>').find('.vbo-widget-custsearchres-entry');
								var vboCustSelected = null;
								jQuery('#vbo-searchcust<?php echo $wrapper; ?>').keydown(function(e) {
									if (e.which === 40) {
										if (vboCustSelected) {
											vboCustSelected.removeClass('vbo-widget-custsearchres-entry-highligthed');
											next = vboCustSelected.next();
											if (next.length > 0) {
												vboCustSelected = next.addClass('vbo-widget-custsearchres-entry-highligthed');
											} else {
												vboCustSelected = vboCust.eq(0).addClass('vbo-widget-custsearchres-entry-highligthed');
											}
										} else {
											vboCustSelected = vboCust.eq(0).addClass('vbo-widget-custsearchres-entry-highligthed');
										}
									} else if (e.which === 38) {
										if (vboCustSelected) {
											vboCustSelected.removeClass('vbo-widget-custsearchres-entry-highligthed');
											next = vboCustSelected.prev();
											if (next.length > 0) {
												vboCustSelected = next.addClass('vbo-widget-custsearchres-entry-highligthed');
											} else {
												vboCustSelected = vboCust.last().addClass('vbo-widget-custsearchres-entry-highligthed');
											}
										} else {
											vboCustSelected = vboCust.last().addClass('vbo-widget-custsearchres-entry-highligthed');
										}
									} else if (e.which === 13) {
										if (vboCustSelected) {
											vboCustSelected.trigger('click');
										}
									}
								});

								jQuery('#vbo-searchcust-res<?php echo $wrapper; ?>').find('.vbo-widget-custsearchres-entry').hover(function() {
									if (vboCustSelected) {
										vboCustSelected.removeClass('vbo-widget-custsearchres-entry-highligthed');
										vboCustSelected = null;
									}
									vboCustSelected = jQuery(this).addClass('vbo-widget-custsearchres-entry-highligthed');
								}, function() {
									if (vboCustSelected) {
										vboCustSelected.removeClass('vbo-widget-custsearchres-entry-highligthed');
										vboCustSelected = null;
									}
									jQuery(this).removeClass('vbo-widget-custsearchres-entry-highligthed');
								});
							}, 300);
						} else {
							window['customers_search_vals<?php echo $wrapper; ?>'] = {};
							jQuery("#vbo-searchcust-res<?php echo $wrapper; ?>").html("----");
						}
						jQuery("#vbo-searchcust-res<?php echo $wrapper; ?>").show();
						jQuery("#vbo-searchcust-loading<?php echo $wrapper; ?>").hide();
					},
					(error) => {
						jQuery("#vbo-searchcust-loading<?php echo $wrapper; ?>").hide();
						console.error(error);
						alert(error.responseText);
					}
				);
			}

			/**
			 * Register keyup event listener with a debounce technique for searching customers.
			 */
			document.getElementById('vbo-searchcust<?php echo $wrapper; ?>').addEventListener('keyup', VBOCore.debounceEvent((e) => {
				var keywords = jQuery("#vbo-searchcust<?php echo $wrapper; ?>").val();
				var chars = keywords ? keywords.length : '';
				// we prefer e.key, as e.keyCode is deprecated
				var key_pressed = e.key || e.keyCode;
				var rgx = new RegExp(/^[A-Za-z0-9]$/);
				if (chars > 1 && key_pressed) {
					if (key_pressed == 'Enter' || key_pressed == 13 || rgx.test(key_pressed)) {
						vboWidgetBooksCalCustomerSearch(keywords);
					}
				} else {
					if (jQuery("#vbo-searchcust-res<?php echo $wrapper; ?>").is(":visible")) {
						jQuery("#vbo-searchcust-res<?php echo $wrapper; ?>").hide();
					}
				}
			}, 600));

			/**
			 * Register the click event over the customer search results.
			 */
			jQuery(document).on('click', '#vbo-searchcust-res<?php echo $wrapper; ?> .vbo-widget-custsearchres-entry', function() {
				var custid 		  = jQuery(this).attr('data-custid');
				var custemail 	  = jQuery(this).attr('data-email');
				var custphone 	  = jQuery(this).attr('data-phone');
				var custcountry   = jQuery(this).attr('data-country');
				var custfirstname = jQuery(this).attr('data-firstname');
				var custlastname  = jQuery(this).attr('data-lastname');

				// set customer ID
				jQuery('#vbo-widget-bookscal-cfield-custid<?php echo $wrapper; ?>').val(custid);

				// check previous custom fields
				if (window['customers_search_vals<?php echo $wrapper; ?>'].hasOwnProperty(custid)) {
					jQuery.each(window['customers_search_vals<?php echo $wrapper; ?>'][custid], function(cfid, cfval) {
						var fill_field = jQuery('#cfield' + cfid + '<?php echo $wrapper; ?>');
						if (fill_field.length) {
							// set previous value
							fill_field.val(cfval);
						}
					});
				}

				// always populate basic information on custom fields
				var fields_wrap = jQuery('.vbo-calendar-cfields-filler[data-wrapper="<?php echo $wrapper; ?>"]');

				if (custcountry.length) {
					if (custcountry.length > 3) {
						fields_wrap.find('select.vbo-calendar-cfield-country').val(custcountry);
					} else {
						var country_opt = fields_wrap.find('select.vbo-calendar-cfield-country').find('option[data-ccode="' + custcountry + '"]');
						if (country_opt.length) {
							fields_wrap.find('select.vbo-calendar-cfield-country').val(country_opt.attr('value'));
						}
					}
				}
				fields_wrap.find('input[data-isnominative="1"]').each(function(k, v) {
					if (k == 0) {
						jQuery(this).val(custfirstname);
						return true;
					}
					if (k == 1) {
						jQuery(this).val(custlastname);
						return true;
					}
					return false;
				});
				fields_wrap.find('input[data-isemail="1"]').val(custemail);
				fields_wrap.find('input[data-isphone="1"]').val(custphone);

				// set customer upon the selection just made
				vboWidgetBooksCalSetCustomer('<?php echo $wrapper; ?>');
			});

			// focus search customer input field
			setTimeout(() => {
				jQuery('#vbo-searchcust<?php echo $wrapper; ?>').focus();
			}, 500);

		</script>
		<?php

		// get the HTML buffer
		$html_content = ob_get_contents();
		ob_end_clean();

		// return an associative array of values
		return [
			'html' => $html_content,
		];
	}

	/**
	 * Custom method for this widget only to save a new booking with the input values.
	 * The method is called by the admin controller through an AJAX request.
	 * The visibility should be public, and an array is requested to be returned.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.16.0 (J) - 1.6.0 (WP)
	 */
	public function saveBooking()
	{
		$wrapper = VikRequest::getString('wrapper', '', 'request');
		$forcebooking = VikRequest::getInt('forcebooking', 0, 'request');
		$room_id = VikRequest::getInt('room_id', 0, 'request');
		$checkin = VikRequest::getString('checkin', '', 'request');
		$checkout = VikRequest::getString('checkout', '', 'request');
		$closure = VikRequest::getInt('closure', 0, 'request');
		$units_closed = VikRequest::getInt('units_closed', 0, 'request');
		$units = VikRequest::getInt('units', 1, 'request');
		$adults = VikRequest::getInt('adults', 2, 'request');
		$children = VikRequest::getInt('children', 0, 'request');
		$cust_id = VikRequest::getInt('cust_id', 0, 'request');
		$cust_email = VikRequest::getString('cust_email', '', 'request');
		$cust_data = VikRequest::getString('cust_data', '', 'request');
		$cust_country = VikRequest::getString('cust_country', '', 'request');
		$cust_state = VikRequest::getString('cust_state', '', 'request');
		$cust_phone = VikRequest::getString('cust_phone', '', 'request');
		$cust_tfname = VikRequest::getString('cust_tfname', '', 'request');
		$cust_tlname = VikRequest::getString('cust_tlname', '', 'request');
		$roomcost = VikRequest::getFloat('roomcost', 0, 'request');
		$idprice = VikRequest::getInt('idprice', 0, 'request');
		$cust_roomcost = VikRequest::getFloat('cust_roomcost', 0, 'request');
		$taxid = VikRequest::getInt('taxid', 0, 'request');

		if (empty($room_id) || empty($checkin) || empty($checkout)) {
			VBOHttpDocument::getInstance()->close(500, JText::translate('VBO_PLEASE_FILL_FIELDS'));
		}

		// invoke the reservation model and inject values
		$model_res = VBOModelReservation::getInstance([
			'force_booking' => $forcebooking,
			'set_closed' 	=> $closure,
			'units_closed'  => $units_closed,
			'status' 		=> 'confirmed',
			'num_rooms' 	=> $units,
			'adults' 		=> $adults,
			'children' 		=> $children,
		])->setCustomer([
			'id' 		 => $cust_id,
			'first_name' => $cust_tfname,
			'last_name'  => $cust_tlname,
			'data' 		 => $cust_data,
			'email' 	 => $cust_email,
			'country' 	 => $cust_country,
			'state' 	 => $cust_state,
			'phone' 	 => $cust_phone,
		])->setRoom([
			'id' 		=> $room_id,
			'cust_cost' => $cust_roomcost,
			'room_cost' => $roomcost,
			'id_price' 	=> $idprice,
			'id_tax' 	=> $taxid,
		]);

		// calculate proper check-in and check-out timestamps
		list($hcheckin, $mcheckin, $hcheckout, $mcheckout) = $model_res->loadCheckinOutTimes();

		// get final stay timestamps
		$checkin_ts  = VikBooking::getDateTimestamp($checkin, $hcheckin, $mcheckin);
		$checkout_ts = VikBooking::getDateTimestamp($checkout, $hcheckout, $mcheckout);

		if (!$checkin_ts || !$checkout_ts || $checkin_ts >= $checkout_ts) {
			VBOHttpDocument::getInstance()->close(500, JText::translate('ERRINVDATESEASON'));
		}

		// set stay dates
		$model_res->set('checkin', $checkin_ts);
		$model_res->set('checkout', $checkout_ts);

		// store the reservation
		$model_res->create();

		// get the new booking ID
		$res_id = $model_res->getNewBookingID();
		if (!$res_id) {
			VBOHttpDocument::getInstance()->close(500, $model_res->getError());
		}

		return [
			'new_booking_id' => $res_id,
			'vcm_action' 	 => $model_res->getChannelManagerAction(),
		];
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
		$this->vbo_app->loadPhoneInputFieldAssets();

		// JS lang def
		JText::script('VBFILLCUSTFIELDS');
		JText::script('VBO_PLEASE_FILL_FIELDS');
		JText::script('VBDASHUPRESONE');
		JText::script('VBANNULLA');
		JText::script('VBAPPLY');
		JText::script('VBDBTEXTROOMCLOSED');
		JText::script('VBO_MARK_UNITS_CLOSED');
	}

	/**
	 * Main method to invoke the widget. Contents will be loaded
	 * through AJAX requests, not via PHP when the page loads.
	 * 
	 * @param 	VBOMultitaskData 	$data
	 * 
	 * @return 	void
	 */
	public function render(VBOMultitaskData $data = null)
	{
		// increase widget's instance counter
		static::$instance_counter++;

		// check whether the widget is being rendered via AJAX when adding it through the customizer
		$is_ajax = $this->isAjaxRendering();

		// generate a unique ID for the sticky notes wrapper instance
		$wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
		$wrapper_id = 'vbo-widget-booskcal-' . $wrapper_instance;

		// get permissions
		$vbo_auth_bookings = JFactory::getUser()->authorise('core.vbo.bookings', 'com_vikbooking');
		if (!$vbo_auth_bookings) {
			// display nothing
			return;
		}

		// invoke availability helper class
		$av_helper = VikBooking::getAvailabilityInstance();

		// get all rooms
		$all_rooms = $av_helper->loadRooms();

		// check pricing tax configuration
		$prices_vat_included = (int)VikBooking::ivaInclusa();

		/**
		 * This widget can make use of the Select2 jQuery plugin, but we do not preload it in
		 * order to save resources. If any other widget has preloaded Select2, JS will detect it.
		 */
		$use_nice_select = 0;
		if (count($all_rooms) > 1) {
			// turn flag on
			$use_nice_select = 1;
		}

		// default dates and values
		$now_info = getdate();
		$from_ts = mktime(0, 0, 0, $now_info['mon'], 1, $now_info['year']);

		// build week days list according to settings
		$firstwday = (int)VikBooking::getFirstWeekDay();
		$days_labels = array(
			JText::translate('VBSUN'),
			JText::translate('VBMON'),
			JText::translate('VBTUE'),
			JText::translate('VBWED'),
			JText::translate('VBTHU'),
			JText::translate('VBFRI'),
			JText::translate('VBSAT'),
		);
		$days_indexes = [];
		for ($i = 0; $i < 7; $i++) {
			$days_indexes[$i] = (6 - ($firstwday - $i) + 1) % 7;
		}

		// check multitask data
		$page_bid 		= 0;
		$load_room 		= 0;
		$modal_load_bid = '';
		$js_modal_id 	= '';
		if ($data) {
			// access Multitask data
			$page_bid = $data->getBookingID() ?: $this->options()->fetchBookingId();
			$is_modal_rendering = $data->isModalRendering();
			if ($page_bid && $is_modal_rendering) {
				// load contents according to injected multitask data
				$modal_load_bid = $page_bid;
			}
			if ($is_modal_rendering) {
				// get modal JS identifier
				$js_modal_id = $data->getModalJsIdentifier();
			}

			// first check if an overbooking flag was given
			$overbooking = $this->getOption('overbooking', 0);
			$overbooking = $page_bid && $overbooking ? $page_bid : 0;
			if ($overbooking) {
				// prepare the options to set for rendering the admin-widget
				// correctly and show the overbooking details
				$this->prepareOverbookingOptions($overbooking);
			}

			// check if a specific room ID was set
			$load_room = $this->getOption('id_room', 0);

			// check for a custom date (Y-m-d)
			$option_offset = $this->getOption('offset', '');
			if ($option_offset && preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $option_offset)) {
				// overwrite values
				$now_info = getdate(strtotime($option_offset));
				$from_ts = mktime(0, 0, 0, $now_info['mon'], 1, $now_info['year']);
			}
		}

		// default labels
		$search_name = preg_replace("/[^A-Za-z0-9 ]/", '', JText::translate('VBOSTATSALLROOMS'));
		$period_date = VikBooking::sayMonth($now_info['mon']) . ' ' . $now_info['year'];

		// start looping from the first day of the current month
		$info_arr = getdate($from_ts);

		// week days counter
		$d_count = 0;
		$mon_lim = $info_arr['mon'];

		?>
		<div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>" data-pagebid="<?php echo $page_bid; ?>" data-offset="<?php echo date('Y-m-d', $from_ts); ?>">
			<div class="vbo-admin-widget-head">
				<div class="vbo-admin-widget-head-inline">
					<h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
					<div class="vbo-admin-widget-head-commands">

						<div class="vbo-reportwidget-commands">
							<div class="vbo-reportwidget-commands-main">
								<div class="vbo-reportwidget-command-dates">
									<div class="vbo-reportwidget-period-name"><?php echo $search_name; ?></div>
									<div class="vbo-reportwidget-period-date"><?php echo $period_date; ?></div>
								</div>
								<div class="vbo-reportwidget-command-chevron vbo-reportwidget-command-prev">
									<span class="vbo-widget-booskcal-dt-prev" onclick="vboWidgetBookCalsMonthNav('<?php echo $wrapper_id; ?>', -1);"><?php VikBookingIcons::e('chevron-left'); ?></span>
								</div>
								<div class="vbo-reportwidget-command-chevron vbo-reportwidget-command-next">
									<span class="vbo-widget-booskcal-dt-next" onclick="vboWidgetBookCalsMonthNav('<?php echo $wrapper_id; ?>', 1);"><?php VikBookingIcons::e('chevron-right'); ?></span>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
			<div class="vbo-widget-booskcal-wrap">
				<div class="vbo-widget-booskcal-inner">

					<div class="vbo-widget-booskcal-top-wrap">
						<div class="vbo-widget-booskcal-filter">
							<select class="vbo-booskcal-roomid" onchange="vboWidgetBookCalsSetRoom('<?php echo $wrapper_id; ?>', this.value);">
								<option data-units="0"></option>
							<?php
							foreach ($all_rooms as $rid => $room) {
								?>
								<option value="<?php echo $rid; ?>" data-units="<?php echo $room['units']; ?>"<?php echo $rid == $load_room ? ' selected="selected"' : ''; ?>><?php echo $room['name']; ?></option>
								<?php
							}
							?>
							</select>
						</div>
						<div class="vbo-widget-booskcal-newbook">
							<button type="button" class="btn btn-success vbo-widget-booskcal-newbook-start" onclick="vboWidgetBookCalsNewBooking('<?php echo $wrapper_id; ?>');"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBO_NEW_BOOKING'); ?></button>
							<button type="button" class="btn btn-success vbo-widget-booskcal-newbook-id" data-bookingid="" onclick="vboWidgetBookCalsOpenNewBooking('<?php echo $wrapper_id; ?>');" style="display: none;"><?php VikBookingIcons::e('check-circle'); ?> <span></span></button>
						</div>
					</div>

					<div class="vbo-widget-booskcal-calendar">

						<table class="vbadmincaltable vbo-widget-booskcal-calendar-table">
							<tbody>
								<tr class="vbadmincaltrmdays">
								<?php
								// display week days in the proper order
								for ($i = 0; $i < 7; $i++) {
									$d_ind = ($i + $firstwday) < 7 ? ($i + $firstwday) : ($i + $firstwday - 7);
									?>
									<td class="vbo-widget-booskcal-cell-wday"><?php echo $days_labels[$d_ind]; ?></td>
									<?php
								}
								?>
								</tr>
								<tr>
								<?php
								// display empty cells until the first week-day of the month
								for ($i = 0, $n = $days_indexes[$info_arr['wday']]; $i < $n; $i++, $d_count++) {
									?>
									<td class="vbo-widget-booskcal-cell-mday vbo-widget-booskcal-cell-empty">&nbsp;</td>
									<?php
								}
								// display month days
								while ($info_arr['mon'] == $mon_lim) {
									if ($d_count > 6) {
										$d_count = 0;
										// close current row and open a new one
										echo "\n</tr>\n<tr>\n";
									}
									?>
									<td class="vbo-widget-booskcal-cell-mday">
										<span class="vbo-widget-booskcal-mday-val"><?php echo $info_arr['mday']; ?></span>
									</td>
									<?php
									$dayts = mktime(0, 0, 0, $info_arr['mon'], ($info_arr['mday'] + 1), $info_arr['year']);
									$info_arr = getdate($dayts);
									$d_count++;
								}
								// add empty cells until the end of the row
								for ($i = $d_count; $i <= 6; $i++) {
									?>
									<td class="vbo-widget-booskcal-cell-mday vbo-widget-booskcal-cell-empty">&nbsp;</td>
									<?php
								}
								?>
								</tr>
							</tbody>
						</table>

					</div>

				</div>
			</div>
		</div>
		<?php

		if (static::$instance_counter === 0 || $is_ajax) {
			/**
			 * Print the JS code only once for all instances of this widget.
			 * The real rendering is made through AJAX, not when the page loads.
			 */
			?>
		<a class="vbo-widget-bookscal-basenavuri" href="<?php echo VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&task=editorder&cid[]=%d', $xhtml = false); ?>" style="display: none;"></a>

		<script type="text/javascript">

			/**
			 * Open the booking details page for the clicked reservation.
			 */
			function vboWidgetBooksCalOpenBooking(id) {
				var open_url = jQuery('.vbo-widget-bookscal-basenavuri').first().attr('href');
				open_url = open_url.replace('%d', id);
				// navigate in a new tab
				window.open(open_url, '_blank');
			}

			/**
			 * Display the loading skeletons.
			 */
			function vboWidgetBooksCalSkeletons(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var skelton_html = '<div class="vbo-skeleton-loading vbo-skeleton-loading-mday-cell"></div>';
				widget_instance.find('.vbo-widget-booskcal-calendar').find('.vbo-widget-booskcal-cell-mday').attr('class', 'vbo-widget-booskcal-cell-mday').html(skelton_html);
			}

			/**
			 * Perform the request to load the bookings calendar.
			 */
			function vboWidgetBooksCalLoad(wrapper, dates_direction, page_bid) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// check if a navigation of dates was requested (0 = no dates nav)
				if (typeof dates_direction === 'undefined') {
					dates_direction = 0;
				}

				// check if multitask data passed a booking ID for the current page
				var force_bid = 0;
				if (typeof page_bid !== 'undefined' && page_bid && !isNaN(page_bid)) {
					force_bid = page_bid;
				}

				// get vars for making the request
				var current_offset = widget_instance.attr('data-offset');
				var room_id = widget_instance.find('.vbo-booskcal-roomid').val();

				// gather options, if any
				var options = vbo_widget_books_cal_options_oo || {};
				if (vbo_widget_books_cal_options_oo) {
					// multitask data options should be used only once ("oo")
					vbo_widget_books_cal_options_oo = null;
				}

				// the widget method to call
				var call_method = 'loadBookingsCalendar';

				// make a request to load the bookings calendar
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call:      call_method,
						return:    1,
						bid:       force_bid,
						offset:    current_offset,
						room_id:   room_id,
						date_dir:  dates_direction,
						wrapper:   wrapper,
						_options:  options,
						tmpl:      "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// set new offset for navigation
							widget_instance.attr('data-offset', obj_res[call_method]['offset']);

							// update search name and month
							widget_instance.find('.vbo-reportwidget-period-name').text(obj_res[call_method]['search_name']);
							widget_instance.find('.vbo-reportwidget-period-date').text(obj_res[call_method]['period_date']);

							// replace HTML with new bookings calendar
							widget_instance.find('.vbo-widget-booskcal-calendar').html(obj_res[call_method]['html']);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// remove the skeleton loading
						widget_instance.find('.vbo-widget-booskcal-calendar').find('.vbo-skeleton-loading').remove();
						console.error(error);
					}
				);
			}

			/**
			 * Perform the request to load the month-day reservations.
			 */
			function vboWidgetBooksCalGetMdayRes(wrapper, ymd) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length || !ymd) {
					return false;
				}

				// get vars for making the request
				var room_id = widget_instance.find('.vbo-booskcal-roomid').val();
				var page_offset = widget_instance.find('.vbo-widget-booskcal-mday-list').attr('data-offset');
				var page_length = widget_instance.find('.vbo-widget-booskcal-mday-list').attr('data-length');

				// the widget method to call
				var call_method = 'loadMdayBookings';

				// make a request to load the bookings calendar
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						page_offset: page_offset,
						page_length: page_length,
						ymd: ymd,
						room_id: room_id,
						wrapper: wrapper,
						tmpl: "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}
							// replace HTML with month-day reservations
							widget_instance.find('.vbo-widget-booskcal-mday-list').html(obj_res[call_method]['html']);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// remove the skeleton loading
						widget_instance.find('.vbo-widget-booskcal-mday-list').html('');
						console.error(error);
					}
				);
			}

			/**
			 * Navigate between the months and load the bookings calendar.
			 */
			function vboWidgetBookCalsMonthNav(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// show loading skeletons
				vboWidgetBooksCalSkeletons(wrapper);

				// launch dates navigation and load records
				vboWidgetBooksCalLoad(wrapper, direction);
			}

			/**
			 * Change room calendar.
			 */
			function vboWidgetBookCalsSetRoom(wrapper, rid) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// check if we are creating a new booking
				if (widget_instance.find('.vbo-widget-booskcal-newbook-wrap').is(':visible')) {
					// check room units
					var vbo_bookscal_roomfilt = widget_instance.find('.vbo-booskcal-roomid');
					if (rid) {
						// manage units depending on selected room total units
						var vbo_bookscal_room_units = vbo_bookscal_roomfilt.find('option:selected').attr('data-units');
						if (vbo_bookscal_room_units > 1) {
							widget_instance.find('.vbo-widget-bookscal-units').attr('max', vbo_bookscal_room_units).val('1').closest('[data-noclosure="1"]').show();
						} else {
							widget_instance.find('.vbo-widget-bookscal-units').val('1').closest('[data-noclosure="1"]').hide();
						}
					} else {
						// hide units when no room is selected
						vbo_bookscal_roomfilt.closest('[data-noclosure="1"]').hide();
					}

					// check the closure status
					if (widget_instance.find('input[name="closeroom"]').prop('checked')) {
						// reset values
						widget_instance.find('.vbo-widget-bookscal-units').val('1');
						widget_instance.find('[data-noclosure="1"]').hide();
					}

					// attempt to update the website rates
					vboWidgetBooksCalGetWebsiteRates(wrapper);

					// do nothing else when adding a new booking
					return;
				}

				// show loading skeletons
				vboWidgetBooksCalSkeletons(wrapper);

				// let the records be loaded for this new room filter
				vboWidgetBooksCalLoad(wrapper, 0);
			}

			/**
			 * Generate the HTML skeleton string to the month-day reservations.
			 */
			function vboWidgetBooksCalMdaySkeleton() {
				var monthday_loading = '';
				monthday_loading += '<div class="vbo-dashboard-guest-activity vbo-dashboard-guest-activity-skeleton">';
				monthday_loading += '	<div class="vbo-dashboard-guest-activity-avatar">';
				monthday_loading += '		<div class="vbo-skeleton-loading vbo-skeleton-loading-avatar"></div>';
				monthday_loading += '	</div>';
				monthday_loading += '	<div class="vbo-dashboard-guest-activity-content">';
				monthday_loading += '		<div class="vbo-dashboard-guest-activity-content-head">';
				monthday_loading += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-title"></div>';
				monthday_loading += '		</div>';
				monthday_loading += '		<div class="vbo-dashboard-guest-activity-content-subhead">';
				monthday_loading += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-subtitle"></div>';
				monthday_loading += '		</div>';
				monthday_loading += '		<div class="vbo-dashboard-guest-activity-content-info-msg">';
				monthday_loading += '			<div class="vbo-skeleton-loading vbo-skeleton-loading-content"></div>';
				monthday_loading += '		</div>';
				monthday_loading += '	</div>';
				monthday_loading += '</div>';

				return monthday_loading;
			}

			/**
			 * Enter the month-day view mode from monthly view.
			 */
			function vboWidgetBooksCalMday(wrapper, element) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// get cell element
				var cell = jQuery(element);
				if (!cell || !cell.length) {
					return false;
				}

				// set month-day title
				var day_read = cell.attr('data-dayread');
				widget_instance.find('.vbo-widget-booskcal-mday-name').text(day_read);

				// get cell ymd value
				var day_ymd = cell.attr('data-ymd');

				// always update the proper ymd day
				widget_instance.find('.vbo-widget-booskcal-mday-list').attr('data-ymd', day_ymd);

				// get pre-loaded booking ids
				var tot_day_res = 0;
				var day_bids = cell.attr('data-bids');
				if (day_bids && day_bids.length) {
					tot_day_res = day_bids.split(',').length;
				}

				// populate loading skeletons for month-day bookings
				var monthday_loading = vboWidgetBooksCalMdaySkeleton();
				if (tot_day_res > 1) {
					// double up the loading skeletons
					monthday_loading = monthday_loading + monthday_loading;
				}
				widget_instance.find('.vbo-widget-booskcal-mday-list').html(monthday_loading);

				// toggle elements
				widget_instance.find('.vbo-widget-booskcal-calendar-table').hide();
				widget_instance.find('.vbo-widget-booskcal-newbook-wrap').hide();
				widget_instance.find('.vbo-widget-booskcal-mday-wrap').show();

				// launch month-day bookings retrieval
				vboWidgetBooksCalGetMdayRes(wrapper, day_ymd);
			}

			/**
			 * Navigate between the various pages of the month-day bookings.
			 */
			function vboWidgetBooksCalMdayNavigate(wrapper, direction) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// get bookings container
				var bookings_list = widget_instance.find('.vbo-widget-booskcal-mday-list');

				// show loading skeletons
				var monthday_loading = vboWidgetBooksCalMdaySkeleton();
				bookings_list.html(monthday_loading + monthday_loading);

				// get current offset and length (MUST be numbers, not strings)
				var current_offset = parseInt(bookings_list.attr('data-offset'));
				var current_length = parseInt(bookings_list.attr('data-length'));
				var day_ymd 	   = bookings_list.attr('data-ymd');

				// check direction and update offsets for nav
				if (direction > 0) {
					// navigate forward
					bookings_list.attr('data-offset', (current_offset + current_length));
				} else {
					// navigate backward
					var new_offset = current_offset - current_length;
					new_offset = new_offset >= 0 ? new_offset : 0;
					bookings_list.attr('data-offset', new_offset);
				}

				// launch month-day bookings retrieval
				vboWidgetBooksCalGetMdayRes(wrapper, day_ymd);
			}

			/**
			 * Go back to the montly/month-day view from the month-day/new-booking view.
			 */
			function vboWidgetBooksCalMonth(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var current_ymd = '';
				var current_rid = '';
				if (widget_instance.find('.vbo-widget-booskcal-newbook-wrap').is(':visible')) {
					current_ymd = widget_instance.find('.vbo-widget-booskcal-newbook-wrap').attr('data-ymd');
					current_rid = widget_instance.find('.vbo-widget-booskcal-newbook-wrap').attr('data-roomid');
					if (current_rid != widget_instance.find('.vbo-booskcal-roomid').val()) {
						// going back from the new-booking view detected a change of the room filter, so we do a reset

						// show loading skeletons
						vboWidgetBooksCalSkeletons(wrapper);

						// let the records be loaded for this new room filter
						vboWidgetBooksCalLoad(wrapper, 0);

						// do not proceed
						return;
					}
				}

				// toggle elements depending on the current view
				widget_instance.find('.vbo-widget-booskcal-newbook-wrap').hide().attr('data-ymd', '');
				if (current_ymd) {
					// back to the month-day view
					widget_instance.find('.vbo-widget-booskcal-calendar-table').hide();
					widget_instance.find('.vbo-widget-booskcal-mday-wrap').show();
				} else {
					// back to the monthly view
					widget_instance.find('.vbo-widget-booskcal-mday-wrap').hide();
					widget_instance.find('.vbo-widget-booskcal-mday-list').html('').attr('data-ymd', '').attr('data-offset', '0');
					widget_instance.find('.vbo-widget-booskcal-calendar-table').show();
				}
			}

			/**
			 * Display the new booking interface by hiding the other elements.
			 */
			function vboWidgetBookCalsNewBooking(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var current_ymd = '';
				var current_rid = widget_instance.find('.vbo-booskcal-roomid').val();
				if (widget_instance.find('.vbo-widget-booskcal-mday-wrap').is(':visible')) {
					current_ymd = widget_instance.find('.vbo-widget-booskcal-mday-list').attr('data-ymd');
				}

				// toggle elements
				widget_instance.find('.vbo-widget-booskcal-calendar-table').hide();
				widget_instance.find('.vbo-widget-booskcal-mday-wrap').hide();
				widget_instance.find('.vbo-widget-booskcal-newbook-wrap').show().attr('data-ymd', current_ymd).attr('data-roomid', current_rid);

				if (current_ymd) {
					// set the check-in date
					var ymd_parts = current_ymd.split('-');
					widget_instance.find('.vbo-widget-bookscal-checkindt').datepicker('setDate', new Date(ymd_parts[0], parseInt(ymd_parts[1]) - 1, parseInt(ymd_parts[2], 0, 0, 0)));
					// trigger the onSelect function in datepicker
					jQuery('.ui-datepicker-current-day').click();
				}
			}

			/**
			 * Toggle the room-closure status when creating a new booking.
			 */
			function vboWidgetBooksCalClosure(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				if (widget_instance.find('input[name="closeroom"]').prop('checked')) {
					widget_instance.find('.vbo-param-container[data-noclosure="1"]').hide();
				} else {
					widget_instance.find('.vbo-param-container[data-noclosure="1"]').show();
					// check selected room
					var vbo_bookscal_roomfilt = widget_instance.find('.vbo-booskcal-roomid');
					if (vbo_bookscal_roomfilt.val()) {
						var vbo_bookscal_room_units = vbo_bookscal_roomfilt.find('option:selected').attr('data-units');
						if (vbo_bookscal_room_units < 2) {
							widget_instance.find('.vbo-widget-bookscal-units').closest('[data-noclosure="1"]').hide();
						}
					} else {
						widget_instance.find('.vbo-widget-bookscal-units').closest('[data-noclosure="1"]').hide();
					}
					// hide website rates if not available
					widget_instance.find('.vbo-param-container[data-unavailable="1"]').hide();
				}
			}

			/**
			 * Open the modal to assign an existing or a new customer to the booking.
			 */
			function vboWidgetBooksCalAssignCustomer(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var modal_body = VBOCore.displayModal({
					extra_class: 	'vbo-modal-rounded vbo-modal-tall',
					title: 			Joomla.JText._('VBFILLCUSTFIELDS'),
					footer_left: 	'<button type="button" class="btn" onclick="VBOCore.emitEvent(\'vbo-widget-booskcal-assigncustomer-dismiss\');">' + Joomla.JText._('VBANNULLA') + '</button>',
					footer_right: 	'<button type="button" class="btn btn-success" onclick="vboWidgetBooksCalSetCustomer(\'' + wrapper + '\');">' + Joomla.JText._('VBAPPLY') + '</button>',
					dismiss_event: 	'vbo-widget-booskcal-assigncustomer-dismiss',
					loading_event: 	'vbo-widget-booskcal-assigncustomer-loading',
					onDismiss: (e) => {
						if (!e || !e.detail) {
							// no event data received, maybe the modal was simply dismissed
							return;
						}

						// parse data received within the dismiss event
						try {
							let customer_data = JSON.parse(e.detail);

							// set values received
							widget_instance.find('.vbo-widget-bookscal-custid').val((customer_data['id'] || ''));
							widget_instance.find('.vbo-widget-bookscal-custmail').val((customer_data['email'] || ''));
							widget_instance.find('.vbo-widget-bookscal-custdata').val((customer_data['data'] || ''));
							widget_instance.find('.vbo-widget-bookscal-country').val((customer_data['country'] || ''));
							widget_instance.find('.vbo-widget-bookscal-state').val((customer_data['state'] || ''));
							widget_instance.find('.vbo-widget-bookscal-phone').val((customer_data['phone'] || ''));

							let tot_names = customer_data['nominatives'] ? customer_data['nominatives'].length : 0;
							if (tot_names > 1) {
								widget_instance.find('.vbo-widget-bookscal-tfname').val(customer_data['nominatives'][0]);
								widget_instance.find('.vbo-widget-bookscal-tlname').val(customer_data['nominatives'][1]);
							}
							if (tot_names) {
								widget_instance.find('.vbo-assign-customer').find('span').text(customer_data['nominatives'].join(' '));
							} else {
								widget_instance.find('.vbo-assign-customer').find('span').text(Joomla.JText._('VBFILLCUSTFIELDS'));
							}
						} catch(e) {
							// log the error and abort
							console.error(e);
							return;
						}
					},
				});

				// start loading
				VBOCore.emitEvent('vbo-widget-booskcal-assigncustomer-loading');

				// the widget method to call
				var call_method = 'displayCustomerFilling';

				// make a request to load the bookings calendar
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						wrapper: wrapper,
						tmpl: "component"
					},
					(response) => {
						// stop loading
						VBOCore.emitEvent('vbo-widget-booskcal-assigncustomer-loading');
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								// dismiss modal and abort
								VBOCore.emitEvent('vbo-widget-booskcal-assigncustomer-dismiss');
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// append modal content
							modal_body.append(obj_res[call_method]['html']);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// stop loading
						VBOCore.emitEvent('vbo-widget-booskcal-assigncustomer-loading');
						// display error
						alert(error.responseText);
						// dismiss modal
						VBOCore.emitEvent('vbo-widget-booskcal-assigncustomer-dismiss');
					}
				);
			}

			/**
			 * Applies the provided customer information when creating a new booking.
			 */
			function vboWidgetBooksCalSetCustomer(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var cfields_cont = '';
				var cfield_vals  = {
					id: 		 jQuery('#vbo-widget-bookscal-cfield-custid' + wrapper).val(),
					email: 		 '',
					phone: 		 '',
					nominatives: [],
					country: 	 '',
					state: 		 '',
					data: 		 '',
				};

				jQuery('.vbo-calendar-cfields-filler[data-wrapper="' + wrapper + '"]').find('.vbo-calendar-cfield-entry').each(function() {
					var cfield_entry 	= jQuery(this);
					var cfield_name 	= cfield_entry.find('label').text();
					var cfield_input 	= cfield_entry.find('span').find('input');
					var cfield_textarea = cfield_entry.find('span').find('textarea');
					var cfield_select 	= cfield_entry.find('span').find('select.vbo-calendar-cfield-country');
					var cfield_state 	= cfield_entry.find('span').find('select.vbo-calendar-cfield-state');
					var cfield_cont 	= '';
					if (cfield_input.length) {
						cfield_cont = cfield_input.val();
						if (cfield_input.attr('data-isemail') == '1' && cfield_cont && cfield_cont.length) {
							cfield_vals['email'] = cfield_cont;
						}
						if (cfield_input.attr('data-isphone') == '1') {
							cfield_vals['phone'] = cfield_cont;
						}
						if (cfield_input.attr('data-isnominative') == '1') {
							cfield_vals['nominatives'].push(cfield_cont);
						}
					} else if (cfield_textarea.length) {
						cfield_cont = cfield_textarea.val();
					} else if (cfield_select.length) {
						cfield_cont = cfield_select.val();
						if (cfield_cont && cfield_cont.length) {
							var country_code = jQuery('option:selected', cfield_select).attr('data-ccode');
							if (country_code && country_code.length) {
								cfield_vals['country'] = country_code;
							}
						}
					} else if (cfield_state.length) {
						cfield_cont = cfield_state.val();
						cfield_vals['state'] = cfield_cont;
					}
					if (cfield_cont && cfield_cont.length) {
						cfields_cont += cfield_name + ": " + cfield_cont + "\r\n";
					}
				});

				if (!cfields_cont.length) {
					// empty information
					alert(Joomla.JText._('VBO_PLEASE_FILL_FIELDS'));

					// do not proceed
					return false;
				}

				// clean up last new lines
				cfields_cont = cfields_cont.replace(/\r\n+$/, "");

				// set raw customer data string
				cfield_vals['data'] = cfields_cont;

				// dimiss the modal by injecting the customer object information
				VBOCore.emitEvent('vbo-widget-booskcal-assigncustomer-dismiss', JSON.stringify(cfield_vals));
			}

			/**
			 * Reloads a list of states according to the given country.
			 */
			function vboWidgetBooksCalReloadStates(country_3_code, wrapper) {
				var states_elem = jQuery('.vbo-calendar-cfields-filler[data-wrapper="' + wrapper + '"]').find('select.vbo-calendar-cfield-state');

				if (!states_elem.length || !country_3_code || !country_3_code.length) {
					return;
				}

				// unset the current states/provinces
				states_elem.html('');

				// make a request to load the states/provinces of the selected country
				VBOCore.doAjax(
					"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=states.load_from_country'); ?>",
					{
						country_3_code: country_3_code,
						tmpl: "component"
					},
					(response) => {
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// append empty value
							states_elem.append('<option value="">-----</option>');

							for (var i = 0; i < obj_res.length; i++) {
								// append state
								states_elem.append('<option value="' + obj_res[i]['state_2_code'] + '">' + obj_res[i]['state_name'] + '</option>');
							}
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						console.error(error);
					}
				);
			}

			/**
			 * Triggers when a custom field of type country is changed.
			 */
			function vboWidgetBooksCalChangeCountry(elem, wrapper) {
				var sel_country = jQuery(elem).find('option:selected');
				if (!sel_country.length) {
					return false;
				}

				// trigger event for phone number
				jQuery('.vbo-calendar-cfield-phone').trigger('vboupdatephonenumber', sel_country.attr('data-c2code'));

				// reload state/province
				vboWidgetBooksCalReloadStates(sel_country.attr('data-ccode'), wrapper);
			}

			/**
			 * Calculates the website rates according to the input values.
			 */
			function vboWidgetBooksCalGetWebsiteRates(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// unset previously selected rates, if any
				widget_instance.find('.vbo-widget-bookscal-roomcost').val('');
				widget_instance.find('.vbo-widget-bookscal-idprice').val('');
				widget_instance.find('.vbo-widget-bookscal-custcost').val('').attr('readonly', false);
				widget_instance.find('.vbo-cal-wbrate-wrap').removeClass('vbo-cal-wbrate-wrap-selected');

				// gather values
				var is_closing = widget_instance.find('input[name="closeroom"]').prop('checked');
				var room_id = widget_instance.find('.vbo-booskcal-roomid').val();
				var checkinfdate = widget_instance.find('.vbo-widget-bookscal-checkindt').val();
				var checkoutfdate = widget_instance.find('.vbo-widget-bookscal-checkoutdt').val();
				var adults = widget_instance.find('.vbo-widget-bookscal-adults').val();
				var children = widget_instance.find('.vbo-widget-bookscal-children').val();
				var units = widget_instance.find('.vbo-widget-bookscal-units').val();

				if (is_closing || !room_id || !checkinfdate || !checkoutfdate) {
					// do not proceed
					widget_instance.find('.vbo-website-rates-row').hide().attr('data-unavailable', '1');
					return;
				}

				// tax settings
				var vbo_tax_included = <?php echo $prices_vat_included; ?>;

				// make the request
				VBOCore.doAjax(
					"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=calc_rates'); ?>",
					{
						id_room: room_id,
						checkinfdate: checkinfdate,
						checkoutfdate: checkoutfdate,
						num_nights: 0,
						num_adults: adults,
						num_children: children,
						units: units,
						only_rates: 1,
						tmpl: "component"
					},
					(resp) => {
						try {
							obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
							if (!obj_res[0].hasOwnProperty('idprice')) {
								widget_instance.find('.vbo-website-rates-row').hide().attr('data-unavailable', '1');
								console.log(resp);
								return false;
							}

							// display the rates obtained
							var wrhtml = "";
							for (var i in obj_res) {
								if (!obj_res.hasOwnProperty(i)) {
									continue;
								}
								if (!vbo_tax_included && obj_res[i].hasOwnProperty('net') && obj_res[i].hasOwnProperty('fnet')) {
									obj_res[i]['tot'] = obj_res[i]['net'];
									obj_res[i]['ftot'] = obj_res[i]['fnet'];
								}
								wrhtml += "<div class=\"vbo-cal-wbrate-wrap\" onclick=\"vboWidgetBooksCalSelWebsiteRate(this, '" + wrapper + "');\">";
								wrhtml += "	<div class=\"vbo-cal-wbrate-inner\">";
								wrhtml += "		<span class=\"vbo-cal-wbrate-name\" data-idprice=\"" + obj_res[i]['idprice'] + "\">" + obj_res[i]['name'] + "</span>";
								wrhtml += "		<span class=\"vbo-cal-wbrate-cost\" data-cost=\"" + obj_res[i]['tot'] + "\">" + obj_res[i]['ftot'] + "</span>";
								wrhtml += "	</div>";
								wrhtml += "</div>";
							}
							widget_instance.find('.vbo-website-rates-cont').html(wrhtml);
							widget_instance.find('.vbo-website-rates-row').fadeIn().attr('data-unavailable', '0');
						} catch(err) {
							widget_instance.find('.vbo-website-rates-row').hide().attr('data-unavailable', '1');
							console.error("could not parse JSON response", resp);
							return false;
						}
					},
					(error) => {
						widget_instance.find('.vbo-website-rates-row').hide().attr('data-unavailable', '1');
						console.error("Error calculating the rates", error);
					}
				);
			}

			/**
			 * Attempts to display the tax rates drop down for the custom rate.
			 */
			function vboWidgetBooksCalFocusTaxes(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var tax_rates_sel = widget_instance.find('.vbo-widget-bookscal-taxid');
				if (tax_rates_sel.length) {
					tax_rates_sel.show();
				}
			}

			/**
			 * Select a website rate plan.
			 */
			function vboWidgetBooksCalSelWebsiteRate(elem, wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var rate = jQuery(elem);
				var idprice = rate.find('.vbo-cal-wbrate-name').attr('data-idprice');
				var cost = rate.find('.vbo-cal-wbrate-cost').attr('data-cost');
				var prev_idprice = widget_instance.find('.vbo-widget-bookscal-idprice').val();

				// reset all selected classes
				widget_instance.find('.vbo-cal-wbrate-wrap').removeClass('vbo-cal-wbrate-wrap-selected');
				if (prev_idprice && prev_idprice == idprice) {
					// rate plan has been de-selected
					widget_instance.find('.vbo-widget-bookscal-idprice').val("");
					widget_instance.find('.vbo-widget-bookscal-roomcost').val("");
					widget_instance.find('.vbo-widget-bookscal-custcost').attr('readonly', false);
				} else {
					// rate plan has been selected
					rate.addClass('vbo-cal-wbrate-wrap-selected');
					widget_instance.find('.vbo-widget-bookscal-idprice').val(idprice);
					widget_instance.find('.vbo-widget-bookscal-roomcost').val(cost);
					widget_instance.find('.vbo-widget-bookscal-custcost').attr('readonly', true);
				}
			}

			/**
			 * Save a new booking.
			 */
			function vboWidgetBooksCalSaveBooking(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				// gather all the necessary information
				var room_id 	  = widget_instance.find('.vbo-booskcal-roomid').val();
				var checkin 	  = widget_instance.find('.vbo-widget-bookscal-checkindt').val();
				var checkout 	  = widget_instance.find('.vbo-widget-bookscal-checkoutdt').val();
				var closure 	  = widget_instance.find('input[name="closeroom"]').prop('checked') ? 1 : 0;
				var units 		  = widget_instance.find('.vbo-widget-bookscal-units').val();
				var adults 		  = widget_instance.find('.vbo-widget-bookscal-adults').val();
				var children 	  = widget_instance.find('.vbo-widget-bookscal-children').val();
				var cust_id 	  = widget_instance.find('.vbo-widget-bookscal-custid').val();
				var cust_email 	  = widget_instance.find('.vbo-widget-bookscal-custmail').val();
				var cust_data 	  = widget_instance.find('.vbo-widget-bookscal-custdata').val();
				var cust_country  = widget_instance.find('.vbo-widget-bookscal-country').val();
				var cust_state 	  = widget_instance.find('.vbo-widget-bookscal-state').val();
				var cust_phone 	  = widget_instance.find('.vbo-widget-bookscal-phone').val();
				var cust_tfname   = widget_instance.find('.vbo-widget-bookscal-tfname').val();
				var cust_tlname   = widget_instance.find('.vbo-widget-bookscal-tlname').val();
				var roomcost 	  = widget_instance.find('.vbo-widget-bookscal-roomcost').val();
				var idprice 	  = widget_instance.find('.vbo-widget-bookscal-idprice').val();
				var cust_roomcost = widget_instance.find('.vbo-widget-bookscal-custcost').val();
				var taxid 		  = widget_instance.find('.vbo-widget-bookscal-taxid').val();

				if (!room_id || !checkin || !checkout) {
					// missing information
					alert(Joomla.JText._('VBO_PLEASE_FILL_FIELDS'));

					// abort
					return false;
				}

				// check if suggesting to mark the rooms as closed is necessary
				var set_units_closed      = 0;
				var current_room_units    = 0;
				var vbo_bookscal_roomfilt = widget_instance.find('.vbo-booskcal-roomid');
				if (vbo_bookscal_roomfilt.val()) {
					var current_room_units = vbo_bookscal_roomfilt.find('option:selected').attr('data-units');
				}
				if (!cust_id && !cust_data && !closure && current_room_units > 1) {
					// no closure, no customer email, multiple units, ask if the units should be marked as closed
					if (confirm(Joomla.JText._('VBO_MARK_UNITS_CLOSED'))) {
						set_units_closed = 1;
						cust_data = Joomla.JText._('VBDBTEXTROOMCLOSED');
					}
				}

				if (!set_units_closed && !cust_id && !cust_data && !closure) {
					// missing information
					alert(Joomla.JText._('VBO_PLEASE_FILL_FIELDS'));

					// abort
					return false;
				}

				// loading content
				var loading_content = '<div class="vbo-modal-overlay-content-backdrop"><div class="vbo-modal-overlay-content-backdrop-body">' + VBOCore.options.default_loading_body + '</div></div>';

				// show loading
				widget_instance.find('.vbo-widget-booskcal-newbook-cont').prepend(loading_content);

				// the widget method to call
				var call_method = 'saveBooking';

				// make a request to save the booking
				VBOCore.doAjax(
					"<?php echo $this->getExecWidgetAjaxUri(); ?>",
					{
						widget_id: "<?php echo $this->getIdentifier(); ?>",
						call: call_method,
						return: 1,
						room_id: room_id,
						checkin: checkin,
						checkout: checkout,
						closure: closure,
						units_closed: set_units_closed,
						units: units,
						adults: adults,
						children: children,
						cust_id: cust_id,
						cust_email: cust_email,
						cust_data: cust_data,
						cust_country: cust_country,
						cust_state: cust_state,
						cust_phone: cust_phone,
						cust_tfname: cust_tfname,
						cust_tlname: cust_tlname,
						roomcost: roomcost,
						idprice: idprice,
						cust_roomcost: cust_roomcost,
						taxid: taxid,
						wrapper: wrapper,
						tmpl: "component"
					},
					(response) => {
						// stop loading
						widget_instance.find('.vbo-modal-overlay-content-backdrop').remove();
						try {
							var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
							if (!obj_res.hasOwnProperty(call_method)) {
								console.error('Unexpected JSON response', obj_res);
								return false;
							}

							// show last booking created
							widget_instance.find('.vbo-widget-booskcal-newbook-start').hide();
							widget_instance.find('.vbo-widget-booskcal-newbook-id').attr('data-bookingid', obj_res[call_method]['new_booking_id']).show().find('span').text(Joomla.JText._('VBDASHUPRESONE') + ': ' + obj_res[call_method]['new_booking_id']);

							// check if should suggest to run VCM
							if (obj_res[call_method]['vcm_action']) {
								widget_instance.append('<p class="info" onclick="jQuery(this).remove();">' + obj_res[call_method]['vcm_action'] + '</p>');
							}

							// register the last booking ID created
							vbo_widget_books_cal_last_new_bid = obj_res[call_method]['new_booking_id'];

							// reload bookings calendar

							// show loading skeletons
							vboWidgetBooksCalSkeletons(wrapper);

							// let the records be loaded for this new room filter
							vboWidgetBooksCalLoad(wrapper, 0);
						} catch(err) {
							console.error('could not parse JSON response', err, response);
						}
					},
					(error) => {
						// stop loading
						widget_instance.find('.vbo-modal-overlay-content-backdrop').remove();
						// display error
						console.error(error);
						alert(error.responseText);
					}
				);
			}

			/**
			 * Triggers when the newly created booking button is clicked.
			 */
			function vboWidgetBookCalsOpenNewBooking(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				var bid = widget_instance.find('.vbo-widget-booskcal-newbook-id').attr('data-bookingid');
				// open the booking
				vboWidgetBooksCalOpenBooking(bid);

				// switch back to the regular button to create a new booking
				widget_instance.find('.vbo-widget-booskcal-newbook-id').hide();
				widget_instance.find('.vbo-widget-booskcal-newbook-start').show();
			}

			/**
			 * Toggles the cancelled reservations.
			 */
			function vboWidgetBooksCalCancToggle(wrapper) {
				var widget_instance = jQuery('#' + wrapper);
				if (!widget_instance.length) {
					return false;
				}

				widget_instance.find('[data-type="cancelled"]').toggle();
			}

			/**
			 * Triggers when the multitask panel opens.
			 */
			function vboWidgetBooksCalMultitaskOpen(wrapper) {
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
				vboWidgetBooksCalSkeletons(wrapper);

				// load data by injecting the current booking ID
				vboWidgetBooksCalLoad(wrapper, 0, page_bid);
			}
			
		</script>
			<?php
		}
		?>

		<script type="text/javascript">

			// store the lastly created booking ID
			var vbo_widget_books_cal_last_new_bid = null;

			// store widget options, if any
			var vbo_widget_books_cal_options_oo = <?php echo json_encode($this->getOptions()); ?>;

			jQuery(function() {

				// when document is ready, load bookings calendar for this widget's instance
				vboWidgetBooksCalLoad('<?php echo $wrapper_id; ?>', 0, '<?php echo $modal_load_bid; ?>');

				// convert the select to a Select2 element
				if (typeof jQuery.fn.select2 !== 'undefined' && <?php echo $use_nice_select; ?>) {
					jQuery('#<?php echo $wrapper_id; ?>').find('select.vbo-booskcal-roomid').select2({
						width: "100%",
						placeholder: "<?php echo htmlspecialchars(VikBooking::strTrimLiteral(JText::translate('VBOREPORTSROOMFILT'))); ?>",
						allowClear: true
					});
				}

				// subscribe to the multitask-panel-open event
				document.addEventListener(VBOCore.multitask_open_event, function() {
					vboWidgetBooksCalMultitaskOpen('<?php echo $wrapper_id; ?>');
				});

				// subscribe to the multitask-panel-close event to emit the event for the lastly created booking ID
				document.addEventListener(VBOCore.multitask_close_event, function() {
					if (vbo_widget_books_cal_last_new_bid) {
						// emit the event with data for anyone who is listening to it
						VBOCore.emitEvent('vbo_new_booking_created', {
							bid: vbo_widget_books_cal_last_new_bid
						});
					}
				});

			<?php
			if ($js_modal_id) {
				// widget can be dismissed through the modal
				?>
				// subscribe to the modal-dismissed event to emit the event for the lastly created booking ID
				document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_modal_id; ?>', function() {
					if (vbo_widget_books_cal_last_new_bid) {
						// emit the event with data for anyone who is listening to it
						VBOCore.emitEvent('vbo_new_booking_created', {
							bid: vbo_widget_books_cal_last_new_bid
						});
					}
				});
				<?php
			}
			?>

			});

		</script>

		<?php
	}

	/**
	 * Helper method to load the booking cancellations for the given room and date.
	 * 
	 * @param 	int 	$room_id 	the Vik Booking room ID.
	 * @param 	string 	$ymd 		the current calendar date in Y-m-d format.
	 * 
	 * @return 	array 				list of involved booking cancellations.
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	protected function loadCancellations($room_id, $ymd)
	{
		$dbo = JFactory::getDbo();

		if (empty($room_id) || empty($ymd)) {
			return [];
		}

		$stay_date_info = getdate(strtotime($ymd));
		$lim_ts_to = mktime(23, 59, 59, $stay_date_info['mon'], $stay_date_info['mday'], $stay_date_info['year']);

		$q = $dbo->getQuery(true);

		$q->select($dbo->qn('o') . '.*');
		$q->from($dbo->qn('#__vikbooking_orders', 'o'));
		$q->leftjoin($dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $dbo->qn('o.id') . ' = ' . $dbo->qn('or.idorder'));
		$q->where($dbo->qn('o.status') . ' = ' . $dbo->q('cancelled'));
		$q->where($dbo->qn('o.checkin') . ' <= ' . $lim_ts_to);
		$q->where($dbo->qn('o.checkout') . ' > ' . $lim_ts_to);
		$q->where($dbo->qn('or.idroom') . ' = ' . (int)$room_id);
		$q->group($dbo->qn('o.id'));
		$q->order($dbo->qn('o.id') . ' ASC');

		$dbo->setQuery($q);
		$cancellations = $dbo->loadAssocList();

		// join the customer information with a separate query as this is faster than joining two more tables at once
		foreach ($cancellations as &$canc_book) {
			$q = $dbo->getQuery(true);

			$q->select($dbo->qn('co.idcustomer'));
			$q->select('CONCAT_WS(" ", ' . $dbo->qn('c.first_name') . ', ' . $dbo->qn('c.last_name') . ') AS ' . $dbo->qn('customer_fullname'));
			$q->select($dbo->qn('c.country', 'customer_country'));
			$q->select($dbo->qn('c.pic'));
			$q->from($dbo->qn('#__vikbooking_customers_orders', 'co'));
			$q->leftjoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('c.id') . ' = ' . $dbo->qn('co.idcustomer'));
			$q->where($dbo->qn('co.idorder') . ' = ' . (int)$canc_book['id']);

			$dbo->setQuery($q);
			$customer_data = $dbo->loadAssoc();

			if ($customer_data) {
				// merge properties
				$canc_book = array_merge($canc_book, $customer_data);
			}
		}

		// unset last reference
		unset($canc_book);

		// return the list of cancellation bookings
		return $cancellations;
	}

	/**
	 * When Multitask Data Options are injected with an overbooking flag, this
	 * method loads the overbooking details and sets the proper option values.
	 * 
	 * @param 	int 	$bid 	the overbooking reservation ID.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.8 (J) - 1.6.8 (WP)
	 */
	protected function prepareOverbookingOptions($bid)
	{
		// get the booking details
		$booking_info = VikBooking::getBookingInfoFromID($bid);
		if (!$booking_info || strcasecmp((string) $booking_info['type'], 'overbooking')) {
			return;
		}

		$booking_checkin_dt  = date('Y-m-d', $booking_info['checkin']);
		$booking_checkout_dt = date('Y-m-d', $booking_info['checkout']);

		// access the availability helper object
		$av_helper = VikBooking::getAvailabilityInstance()
			->setStayDates($booking_checkin_dt, $booking_checkout_dt);

		// get stay date timestamps
		list($checkin_ts, $checkout_ts) = $av_helper->getStayDates(true);

		// count length of stay and nights involved
		$tot_nights = $av_helper->countNightsOfStay();
		$groupdays  = VikBooking::getGroupDays($checkin_ts, $checkout_ts, $tot_nights);

		// get all rooms involved
		$booking_rooms = VikBooking::loadOrdersRoomsData($booking_info['id']);
		$room_ids      = array_unique(array_column($booking_rooms, 'idroom'));

		// load all the occupied records for the involved rooms
		$busy_records = VikBooking::loadBusyRecords($room_ids, $checkin_ts, strtotime('+1 day', $checkout_ts));

		// scan all rooms to find the first date in overbooking state
		foreach ($booking_rooms as $booking_room) {
			$room_id   = $booking_room['idroom'];
			$room_data = VikBooking::getRoomInfo($room_id, ['units']);
			if (empty($room_data['units']) || !isset($busy_records[$room_id])) {
				// room is no longer on db or has got no occupied records
				continue;
			}

			// scan all nights involved
			foreach ($groupdays as $gday) {
				// start counter
				$bfound = 0;

				// scan all the occupied records
				foreach ($busy_records[$room_id] as $bu) {
					$busy_info_in  = getdate($bu['checkin']);
					$busy_info_out = getdate($bu['checkout']);
					$busy_in_ts    = mktime(0, 0, 0, $busy_info_in['mon'], $busy_info_in['mday'], $busy_info_in['year']);
					$busy_out_ts   = mktime(0, 0, 0, $busy_info_out['mon'], $busy_info_out['mday'], $busy_info_out['year']);
					if ($gday >= $busy_in_ts && $gday <= $busy_out_ts) {
						// room is occupied
						$bfound++;
					}

					// check if the room is overbooked
					if ($bfound > $room_data['units']) {
						// overbooking date found, inject data and abort
						$overbooking_dt = date('Y-m-d', $gday);

						// set "offset" and "day" widget options
						$this->setOption('offset', $overbooking_dt);
						$this->setOption('day', $overbooking_dt);

						// set room ID widget option
						$this->setOption('id_room', $room_id);

						// abort for information found
						return;
					}
				}
			}
		}

		// fallback onto setting the first values available

		// set "offset" and "day" widget options
		$this->setOption('offset', $booking_checkin_dt);
		$this->setOption('day', $booking_checkin_dt);

		$booking_rooms = VikBooking::loadOrdersRoomsData($booking_info['id']);
		foreach ($booking_rooms as $booking_room) {
			// set room ID widget option
			$this->setOption('id_room', $booking_room['idroom']);

			break;
		}
	}
}
