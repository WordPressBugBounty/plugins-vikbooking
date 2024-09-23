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

$pcategories = VikRequest::getString('categories', '', 'request');
$pitemid = VikRequest::getInt('Itemid', '', 'request');
$vbdateformat = VikBooking::getDateFormat();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$sugg_units = (int)VikBooking::showSearchSuggestions();
$datesep = VikBooking::getDateSeparator();
$timeopst = VikBooking::getTimeOpenStore();
if (is_array($timeopst)) {
	$opent = VikBooking::getHoursMinutes($timeopst[0]);
	$closet = VikBooking::getHoursMinutes($timeopst[1]);
	$hcheckin = $opent[0];
	$mcheckin = $opent[1];
	$hcheckout = $closet[0];
	$mcheckout = $closet[1];
} else {
	$hcheckin = 0;
	$mcheckin = 0;
	$hcheckout = 0;
	$mcheckout = 0;
}

$map_months = array(
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
	JText::translate('VBMONTHTWELVE')
);

$map_short_wday = array(
	JText::translate('VBSUN'),
	JText::translate('VBMON'),
	JText::translate('VBTUE'),
	JText::translate('VBWED'),
	JText::translate('VBTHU'),
	JText::translate('VBFRI'),
	JText::translate('VBSAT')
);
$map_long_wday = array(
	JText::translate('VBWEEKDAYZERO'),
	JText::translate('VBWEEKDAYONE'),
	JText::translate('VBWEEKDAYTWO'),
	JText::translate('VBWEEKDAYTHREE'),
	JText::translate('VBWEEKDAYFOUR'),
	JText::translate('VBWEEKDAYFIVE'),
	JText::translate('VBWEEKDAYSIX')
);

if ($this->code == 1 && count($this->suggestions) && count($this->solutions) && count($this->split_stay_solutions)) {
	// parse split stay solutions first
	?>
<div id="vbo-splitstay-suggestions-container-<?php echo $this->code; ?>" class="vbo-search-suggestions-container vbo-splitstay-suggestions-container">
	<h4><?php echo JText::translate('VBO_SPLIT_STAY_SOLS'); ?></h4>
	<p class="vbo-search-suggestions-intro"><?php echo JText::translate('VBO_SPLIT_STAY_SOLS_DESCR'); ?></p>
	<div class="vbo-booking-solutions vbo-splitstay-solutions">
	<?php
	// limits and general values
	$max_splitstay_suggs = 4;
	$splitstay_suggested = 0;
	$orig_checkin_info  = getdate($this->checkin);
	$orig_checkout_info = getdate($this->checkout);
	$main_adults   = 0;
	$main_children = 0;
	foreach ($this->party as $guests) {
		if (isset($guests['adults'])) {
			$main_adults = (int)$guests['adults'];
		}
		if (isset($guests['children'])) {
			$main_children = (int)$guests['children'];
		}
		break;
	}

	// loop throuh all split stay solutions available
	foreach ($this->split_stay_solutions as $split_stay_sol) {
		// count rooms in this split stay solutions
		$sol_tot_rooms = count($split_stay_sol);
		// build book now URI for this split stay solution
		$split_stay_url_data = [
			'option' 	 => 'com_vikbooking',
			'task' 		 => 'showprc',
			'roomsnum' 	 => $sol_tot_rooms,
			'roomopt' 	 => [],
			'adults'   	 => [],
			'children' 	 => [],
			'split_stay' => [],
			'days' 	   	 => $this->nights,
			'checkin' 	 => $this->checkin,
			'checkout' 	 => $this->checkout,
			'categories' => (!empty($pcategories) ? $pcategories : null),
			'Itemid' 	 => (!empty($pitemid) ? $pitemid : null),
		];
		?>
		<div class="vbo-booking-solution vbo-splitstay-solution">
			<div class="vbo-booking-solution-dates">
				<span class="vbo-booking-solution-checkin">
					<span class="vbo-booking-solution-date-lbl"><?php echo JText::sprintf('VBOBOOKSOLSUGGCKIN', $map_long_wday[$orig_checkin_info['wday']]); ?></span>
					<span class="vbo-booking-solution-date-dt"><?php echo date(str_replace("/", $datesep, $df), $orig_checkin_info[0]); ?></span>
				</span>
				<span class="vbo-booking-solution-checkout">
					<span class="vbo-booking-solution-date-lbl"><?php echo JText::sprintf('VBOBOOKSOLSUGGCKOUT', $map_long_wday[$orig_checkout_info['wday']]); ?></span>
					<span class="vbo-booking-solution-date-dt"><?php echo date(str_replace("/", $datesep, $df), $orig_checkout_info[0]); ?></span>
				</span>
			</div>
			<div class="vbo-booking-solution-rooms">
				<div class="vbo-booking-solution-totrooms"><?php echo $sol_tot_rooms . ' ' . ($sol_tot_rooms > 1 ? JText::translate('VBSEARCHRESROOMS') : JText::translate('VBSEARCHRESROOM')); ?></div>
			<?php
			foreach ($split_stay_sol as $split_stay) {
				// get stay dates info
				$split_checkin_info  = getdate(strtotime($split_stay['checkin']));
				$split_checkout_info = getdate(strtotime($split_stay['checkout']));
				// push split stay data to URL
				$split_stay_copy = $split_stay;
				unset($split_stay_copy['room_name']);
				$split_stay_url_data['split_stay'][] = $split_stay_copy;
				// push additional URL values
				$split_stay_url_data['roomopt'][]  = $split_stay['idroom'];
				$split_stay_url_data['adults'][]   = $main_adults;
				$split_stay_url_data['children'][] = $main_children;
				?>
				<div class="vbo-booking-solution-room vbo-splitstay-solution-room">
					<div class="vbo-booking-solution-rname">
						<span><?php echo $split_stay['room_name']; ?></span>
					</div>
					<div class="vbo-splitstay-solution-details">
						<span class="vbo-splitstay-solution-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo $split_stay['nights'] . ' ' . ($split_stay['nights'] > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?></span>
						<span class="vbo-splitstay-solution-checkin"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo $map_short_wday[$split_checkin_info['wday']] . ', ' . date(str_replace("/", $datesep, $df), $split_checkin_info[0]); ?></span>
						<span class="vbo-splitstay-solution-checkout"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo $map_short_wday[$split_checkout_info['wday']] . ', ' . date(str_replace("/", $datesep, $df), $split_checkout_info[0]); ?></span>
					</div>
				</div>
				<?php
			}
			?>
			</div>
			<div class="vbo-booking-solution-book vbselectr">
				<a class="btn vbo-pref-color-btn" href="<?php echo JRoute::rewrite('index.php?' . http_build_query($split_stay_url_data)); ?>"><?php echo JText::translate('VBBOOKNOW'); ?></a>
			</div>
		</div>
		<?php
		$splitstay_suggested++;
		if ($splitstay_suggested >= $max_splitstay_suggs) {
			break;
		}
	}
	?>
	</div>
</div>
	<?php
}

if (count($this->suggestions)) {
	$sug_from_ts_info = getdate($this->sug_from_ts);
	$begin_month = $sug_from_ts_info['mon'];
	$sug_calendars = array();
	while ($sug_from_ts_info[0] <= $this->sug_to_ts) {
		$calkey = $sug_from_ts_info['mon'].'_'.$sug_from_ts_info['year'];
		if (!isset($sug_calendars[$calkey])) {
			$sug_calendars[$calkey] = array();
		}
		array_push($sug_calendars[$calkey], $sug_from_ts_info);
		$sug_from_ts_info = getdate(mktime(0, 0, 0, $sug_from_ts_info['mon'], ($sug_from_ts_info['mday'] + 1), $sug_from_ts_info['year']));
	}
	$todays_arr = array();
	foreach ($sug_calendars as $daycal) {
		$todays_arr[] = count($daycal);
	}
	$max_day_cells = max($todays_arr);
	?>
<div id="vbo-search-suggestions-container-<?php echo $this->code; ?>" class="vbo-search-suggestions-container">
	<h4><?php echo JText::translate('VBOSEARCHSUGGAVNEAR'); ?></h4>
	<p class="vbo-search-suggestions-intro"><?php echo JText::translate('VBOSEARCHSUGGINTROCODE' . ($this->code === 1 ? '1' : '2')); ?></p>
	<div class="vbo-search-suggestions-av table-responsive">
		<table class="table">
			<tbody>
			<?php
			foreach ($sug_calendars as $calkey => $caldays) {
				$monparts = explode('_', $calkey);
				?>
				<tr class="vbo-search-suggestions-month-days">
					<td class="vbo-search-suggestions-av-mon vbo-pref-color-text" rowspan="2"><?php echo $map_months[((int)$monparts[0] - 1)].' '.$monparts[1]; ?></td>
				<?php
				$days_counter = 0;
				foreach ($caldays as $calday) {
					?>
					<td class="vbo-search-suggestions-month-day<?php echo in_array($calday[0], $this->nights_requested) ? ' vbo-search-suggestions-month-day-requested' : ''; ?>" data-day="<?php echo date('Y-m-d', $calday[0]); ?>">
						<span class="vbo-suggestion-daynumber"><?php echo $calday['mday']; ?></span>
						<span class="vbo-suggestion-weekday"><?php echo $map_short_wday[(int)$calday['wday']]; ?></span>
					</td>
					<?php
					$days_counter++;
				}
				if ($days_counter < $max_day_cells) {
					while ($days_counter < $max_day_cells) {
						?>
					<td></td>
						<?php
						$days_counter++;
					}
				}
				?>
				</tr>
				<tr class="vbo-search-suggestions-av-days">
				<?php
				$days_counter = 0;
				foreach ($caldays as $calday) {
					$keyday = date('Y-m-d', $calday[0]);
					$dayclass = isset($this->suggestions[$keyday]) ? 'vbo-suggestion-free' : 'vbo-suggestion-busy';
					$daycont = 0;
					if (isset($this->suggestions[$keyday])) {
						foreach ($this->suggestions[$keyday] as $rr) {
							$daycont += $rr['units_left'];
						}
					}
					$sayday = $map_long_wday[(int)$calday['wday']].', '.$calday['mday'].' '.$map_months[((int)$monparts[0] - 1)].' '.$monparts[1];
					?>
					<td class="vbo-search-suggestions-av-day <?php echo $dayclass; ?>" data-keyday="<?php echo $keyday; ?>" data-sayday="<?php echo $sayday; ?>"><?php echo $sugg_units == 1 || $daycont < 1 ? $daycont : '&nbsp;'; ?></td>
					<?php
					$days_counter++;
				}
				if ($days_counter < $max_day_cells) {
					while ($days_counter < $max_day_cells) {
						?>
					<td></td>
						<?php
						$days_counter++;
					}
				}
				?>
				</tr>
				<tr class="vbo-search-suggestions-av-daysel" style="display: none;">
					<td class="vbo-search-suggestions-av-daysel-cont" data-keyday="" colspan="<?php echo ($max_day_cells + 1); ?>"></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
	</div>
	<?php
	if ($this->code == 1 && count($this->solutions)) {
		// (error code 1) closest bookings solutions with availability
		?>
	<div class="vbo-search-solutions-container">
		<h4><?php echo JText::sprintf('VBOBOOKSOLSUGGNIGHTS', $this->nights.' '.($this->nights > 1 ? JText::translate('VBSEARCHRESNIGHTS') : JText::translate('VBSEARCHRESNIGHT'))); ?></h4>
		<div class="vbo-search-solutions">
		<?php
		$max_suggestions = 4;
		$sug_count = 0;
		foreach ($this->solutions as $keyday => $solution) {
			$day_info = getdate($keyday);
			$out_info = getdate(mktime(0, 0, 0, $day_info['mon'], ($day_info['mday'] + $this->nights), $day_info['year']));
			$aduchild_str = '';
			foreach ($this->party as $gp) {
				$aduchild_str .= '&adults[]='.$gp['adults'].'&children[]='.$gp['children'];
			}
			$nights_suggested = array(date('Y-m-d', $day_info[0]));
			for ($n = 1; $n < $this->nights; $n++) {
				$nights_suggested[] = date('Y-m-d', mktime(0, 0, 0, $day_info['mon'], ($day_info['mday'] + $n), $day_info['year']));
			}
			?>
			<div class="vbo-search-solution" data-daysol="<?php echo implode(';', $nights_suggested); ?>">
				<div class="vbo-search-solution-dates">
					<span class="vbo-search-solution-checkin">
						<span class="vbo-search-solution-date-lbl"><?php echo JText::sprintf('VBOBOOKSOLSUGGCKIN', $map_long_wday[(int)$day_info['wday']]); ?></span>
						<span class="vbo-search-solution-date-dt"><?php echo date(str_replace("/", $datesep, $df), $day_info[0]); ?></span>
					</span>
					<span class="vbo-search-solution-checkout">
						<span class="vbo-search-solution-date-lbl"><?php echo JText::sprintf('VBOBOOKSOLSUGGCKOUT', $map_long_wday[(int)$out_info['wday']]); ?></span>
						<span class="vbo-search-solution-date-dt"><?php echo date(str_replace("/", $datesep, $df), $out_info[0]); ?></span>
					</span>
				</div>
				<div class="vbo-search-solution-book vbselectr">
					<a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=search&checkindate='.date($df, $day_info[0]).'&checkinh='.$hcheckin.'&checkinm='.$mcheckin.'&checkoutdate='.date($df, $out_info[0]).'&checkouth='.$hcheckout.'&checkoutm='.$mcheckout.'&roomsnum='.count($this->party).$aduchild_str.(!empty($pcategories) ? '&categories='.$pcategories : '').(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" class="btn vbo-pref-color-btn"><?php echo JText::translate('VBOBOOKSOLSEARCHROOMS'); ?></a>
				</div>
			</div>
			<?php
			$sug_count++;
			if ($sug_count >= $max_suggestions) {
				break;
			}
		}
		?>
		</div>
	</div>
		<?php
	} elseif ($this->code == 1 && !count($this->solutions)) {
		//if error code = 1 and count(solutions) = 0, it probably means that the party requested cannot be satisfied in the suggested dates.
		//we display a message like: "...try to select a different number of rooms and guests"
		?>
	<div class="vbo-search-solutions-container">
		<p class="vbo-search-no-solutions"><?php echo JText::translate('VBOSEARCHNOSOLUTIONS'); ?></p>
	</div>
		<?php
	}

	if (($this->code == 2 || $this->code == 3) && count($this->booking_solutions)) {
		//(error codes 2/3) other combinations of party for booking
		$guests_strarr = array();
		if ($this->party_guests['adults'] > 0) {
			$guests_strarr[] = $this->party_guests['adults'].' '.($this->party_guests['adults'] > 1 ? JText::translate('VBSEARCHRESADULTS') : JText::translate('VBSEARCHRESADULT'));
		}
		if ($this->party_guests['children'] > 0) {
			$guests_strarr[] = $this->party_guests['children'].' '.($this->party_guests['children'] > 1 ? JText::translate('VBSEARCHRESCHILDREN') : JText::translate('VBSEARCHRESCHILD'));
		}
		?>
	<div class="vbo-booking-solutions-container">
		<h4><?php echo JText::sprintf('VBOBOOKSOLSUGGPARTY', implode(', ', $guests_strarr)); ?></h4>
		<div class="vbo-booking-solutions">
		<?php
		$max_suggestions = 4;
		$sug_count = 0;
		foreach ($this->booking_solutions as $keyday => $solution) {
			$day_info = getdate($keyday);
			$out_info = getdate(mktime(0, 0, 0, $day_info['mon'], ($day_info['mday'] + $this->nights), $day_info['year']));
			$sol_checkints = mktime($hcheckin, $mcheckin, 0, $day_info['mon'], $day_info['mday'], $day_info['year']);
			$sol_checkoutts = mktime($hcheckout, $mcheckout, 0, $out_info['mon'], $out_info['mday'], $out_info['year']);
			$sol_tot_rooms = count($solution);
			$aduchild_str = '';
			$nights_suggested = array(date('Y-m-d', $day_info[0]));
			for ($n = 1; $n < $this->nights; $n++) {
				$nights_suggested[] = date('Y-m-d', mktime(0, 0, 0, $day_info['mon'], ($day_info['mday'] + $n), $day_info['year']));
			}
			?>
			<div class="vbo-booking-solution" data-daysol="<?php echo implode(';', $nights_suggested); ?>">
				<div class="vbo-booking-solution-dates">
					<span class="vbo-booking-solution-checkin">
						<span class="vbo-booking-solution-date-lbl"><?php echo JText::sprintf('VBOBOOKSOLSUGGCKIN', $map_long_wday[(int)$day_info['wday']]); ?></span>
						<span class="vbo-booking-solution-date-dt"><?php echo date(str_replace("/", $datesep, $df), $day_info[0]); ?></span>
					</span>
					<span class="vbo-booking-solution-checkout">
						<span class="vbo-booking-solution-date-lbl"><?php echo JText::sprintf('VBOBOOKSOLSUGGCKOUT', $map_long_wday[(int)$out_info['wday']]); ?></span>
						<span class="vbo-booking-solution-date-dt"><?php echo date(str_replace("/", $datesep, $df), $out_info[0]); ?></span>
					</span>
				</div>
				<div class="vbo-booking-solution-rooms">
					<div class="vbo-booking-solution-totrooms"><?php echo $sol_tot_rooms.' '.($sol_tot_rooms > 1 ? JText::translate('VBSEARCHRESROOMS') : JText::translate('VBSEARCHRESROOM')); ?></div>
				<?php
				foreach ($solution as $roomsol) {
					$room_guests_strarr = array();
					if ($roomsol['guests_allocation']['adults'] > 0) {
						$room_guests_strarr[] = $roomsol['guests_allocation']['adults'].' '.($roomsol['guests_allocation']['adults'] > 1 ? JText::translate('VBSEARCHRESADULTS') : JText::translate('VBSEARCHRESADULT'));
					}
					if ($roomsol['guests_allocation']['children'] > 0) {
						$room_guests_strarr[] = $roomsol['guests_allocation']['children'].' '.($roomsol['guests_allocation']['children'] > 1 ? JText::translate('VBSEARCHRESCHILDREN') : JText::translate('VBSEARCHRESCHILD'));
					}
					$aduchild_str .= '&adults[]='.$roomsol['guests_allocation']['adults'].'&children[]='.$roomsol['guests_allocation']['children'];
					?>
					<div class="vbo-booking-solution-room">
						<span class="vbo-booking-solution-rname">
							<a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=searchdetails&roomid='.$roomsol['id'].'&checkin='.$sol_checkints.'&checkout='.$sol_checkoutts.'&adults='.$roomsol['guests_allocation']['adults'].'&children='.$roomsol['guests_allocation']['children'].'&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" class="vbmodalframe" target="_blank"><?php echo $roomsol['name']; ?></a>
						</span>
						<span class="vbo-booking-solution-guests"><?php echo implode(', ', $room_guests_strarr); ?></span>
					</div>
					<?php
				}
				?>
				</div>
				<div class="vbo-booking-solution-book vbselectr">
					<a class="btn vbo-pref-color-btn" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=search&suggestion='.$this->code.'&checkindate='.date($df, $day_info[0]).'&checkinh='.$hcheckin.'&checkinm='.$mcheckin.'&checkoutdate='.date($df, $out_info[0]).'&checkouth='.$hcheckout.'&checkoutm='.$mcheckout.'&roomsnum='.$sol_tot_rooms.$aduchild_str.(!empty($pcategories) ? '&categories='.$pcategories : '').(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VBBOOKNOW'); ?></a>
				</div>
			</div>
			<?php
			$sug_count++;
			if ($sug_count >= $max_suggestions) {
				break;
			}
		}
		?>
		</div>
	</div>
		<?php
	} elseif (($this->code == 2 || $this->code == 3) && !count($this->booking_solutions)) {
		//if error code = 2/3 and count(booking_solutions) = 0, it probably means that there are no rates, or no rooms to fit this number of nights and guests
		?>
	<div class="vbo-booking-solutions-container">
		<p class="vbo-search-no-solutions"><?php echo JText::translate('VBOSEARCHNOBOOKSOLUTIONS'); ?></p>
	</div>
		<?php
	}
	?>
</div>
<script type="text/javascript">
jQuery('#vbo-search-suggestions-container-<?php echo $this->code; ?>').find(".vbo-search-solution, .vbo-booking-solution").hover(function() {
	var daysol = jQuery(this).attr("data-daysol").split(";");
	if (!daysol.length) {
		return;
	}
	for (var i = 0; i < daysol.length; i++) {
		var avday = jQuery('#vbo-search-suggestions-container-<?php echo $this->code; ?>').find("td.vbo-search-suggestions-month-day[data-day='"+daysol[i]+"']");
		if (avday && avday.length) {
			avday.addClass("vbo-search-suggestions-month-day-highlighted");
		}
	}
}, function() {
	jQuery('#vbo-search-suggestions-container-<?php echo $this->code; ?>').find("td.vbo-search-suggestions-month-day-highlighted").removeClass("vbo-search-suggestions-month-day-highlighted");
});

jQuery(".vbmodalframe").fancybox({
	"helpers": {
		"overlay": {
			"locked": false
		}
	},
	"width": "70%",
	"height": "60%",
	"autoScale": true,
	"transitionIn": "none",
	"transitionOut": "none",
	"padding": 0,
	"fitToView" : true,
	"autoSize" : false,
	"type": "iframe" 
});

/**
 * We allow multiple requests to be made on this View, one for each error code.
 * For this reason, all JS functions should be restricted to the current error code.
 * 
 * @since 	1.3.6
 */
var vbo_suggestions_<?php echo $this->code; ?> = <?php echo json_encode($this->suggestions); ?>;

jQuery('#vbo-search-suggestions-container-<?php echo $this->code; ?>').find(".vbo-suggestion-free").click(function() {
	var vbo_suggestions = vbo_suggestions_<?php echo $this->code; ?>;
	var keyday = jQuery(this).attr('data-keyday');
	var sayday = jQuery(this).attr('data-sayday');
	var trshow = jQuery(this).parent("tr").next(".vbo-search-suggestions-av-daysel");
	if (!trshow || !trshow.length) {
		return;
	}
	var daycont = '';
	var tdcont = trshow.find(".vbo-search-suggestions-av-daysel-cont");
	if (trshow.is(":visible") && tdcont.attr('data-keyday') == keyday) {
		jQuery(this).removeClass("vbo-suggestion-selected");
		trshow.fadeOut(400, function() {
			tdcont.html(daycont);
		});
		return;
	}

	if (vbo_suggestions.hasOwnProperty(keyday)) {
		jQuery('#vbo-search-suggestions-container-<?php echo $this->code; ?>').find(".vbo-suggestion-free.vbo-suggestion-selected").removeClass("vbo-suggestion-selected");
		jQuery(this).addClass("vbo-suggestion-selected");
		daycont += '<h5>'+sayday+'</h5>';
		daycont += '<div class="vbo-search-suggestions-dayrooms">';
		for (var rid in vbo_suggestions[keyday]) {
			var paramshowpeople = parseInt(JSON.parse(vbo_suggestions[keyday][rid]['params'])['maxminpeople']);
			daycont += '<div class="vbo-search-suggestions-dayroom">';
				daycont += '<div class="vbo-search-suggestions-dayroom-name">'+vbo_suggestions[keyday][rid]['name']+'</div>';
			<?php
			if ($sugg_units == 1) :
			?>
				daycont += '<div class="vbo-search-suggestions-dayroom-units">x'+vbo_suggestions[keyday][rid]['units_left']+'</div>';
			<?php
			endif;
			?>
				daycont += '<div class="vbo-search-suggestions-dayroom-guests">';
				if (paramshowpeople > 0) {
					var maxadustr = (vbo_suggestions[keyday][rid]['fromadult'] != vbo_suggestions[keyday][rid]['toadult'] ? vbo_suggestions[keyday][rid]['fromadult']+' - '+vbo_suggestions[keyday][rid]['toadult'] : vbo_suggestions[keyday][rid]['toadult']);
					var maxchistr = (vbo_suggestions[keyday][rid]['fromchild'] != vbo_suggestions[keyday][rid]['tochild'] ? vbo_suggestions[keyday][rid]['fromchild']+' - '+vbo_suggestions[keyday][rid]['tochild'] : vbo_suggestions[keyday][rid]['tochild']);
					var maxtotstr = (vbo_suggestions[keyday][rid]['mintotpeople'] != vbo_suggestions[keyday][rid]['totpeople'] ? vbo_suggestions[keyday][rid]['mintotpeople']+' - '+vbo_suggestions[keyday][rid]['totpeople'] : vbo_suggestions[keyday][rid]['totpeople']);
					daycont += '<div class="vbmaxminpeopleroom">';
					if (paramshowpeople == 1) {
						daycont += '<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBFORMADULTS')); ?></span><span class="vbmaxnumberdet">'+maxadustr+'</span></div>';
					} else if (paramshowpeople == 2) {
						daycont += '<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBFORMCHILDREN')); ?></span><span class="vbmaxnumberdet">'+maxchistr+'</span></div>';
					} else if (paramshowpeople == 3) {
						daycont += '<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBFORMADULTS')); ?></span><span class="vbmaxnumberdet">'+maxadustr+'</span></div>';
						daycont += '<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBMAXTOTPEOPLE')); ?></span><span class="vbmaxnumberdet">'+maxtotstr+'</span></div>';
					} else if (paramshowpeople == 4) {
						daycont += '<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBFORMCHILDREN')); ?></span><span class="vbmaxnumberdet">'+maxchistr+'</span></div>';
						daycont += '<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBMAXTOTPEOPLE')); ?></span><span class="vbmaxnumberdet">'+maxtotstr+'</span></div>';
					} else if (paramshowpeople == 5) {
						daycont += '<div class="vbmaxadultsdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBFORMADULTS')); ?></span><span class="vbmaxnumberdet">'+maxadustr+'</span></div>';
						daycont += '<div class="vbmaxchildrendet"><span class="vbmaximgdet"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBFORMCHILDREN')); ?></span><span class="vbmaxnumberdet">'+maxchistr+'</span></div>';
						daycont += '<div class="vbmaxtotdet"><span class="vbmaximgdet"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo addslashes(JText::translate('VBMAXTOTPEOPLE')); ?></span><span class="vbmaxnumberdet">'+maxtotstr+'</span></div>';
					}
					daycont += '</div>';
				}
				daycont += '</div>';
			daycont += '</div>';
		}
		daycont += '</div>';
		tdcont.attr('data-keyday', keyday);
		tdcont.html(daycont);
		trshow.fadeIn();
	}
});
</script>
	<?php
}
