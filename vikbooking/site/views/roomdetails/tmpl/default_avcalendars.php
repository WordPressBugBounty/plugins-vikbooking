<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 e4j - E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Availability calendars.
 */

// JSON-decode room parameters
$rparams = json_decode($this->room['params'], true);

$showpartlyres = VikBooking::showPartlyReserved();
$showcheckinoutonly = VikBooking::showStatusCheckinoutOnly();
$currencysymb = VikBooking::getCurrencySymb();
$numcalendars = VikBooking::numCalendars();

$vbdateformat = VikBooking::getDateFormat();
$datesep = VikBooking::getDateSeparator();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

$inonout_allowed = true;
$timeopst = VikBooking::getTimeOpenStore();
if (is_array($timeopst)) {
	if ($timeopst[0] < $timeopst[1]) {
		// check-in not allowed on a day where there is already a check out (no arrivals/depatures on the same day)
		$inonout_allowed = false;
	}
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

$pitemid = VikRequest::getInt('Itemid', '', 'request');

$closing_dates = VikBooking::parseJsClosingDates();
$cal_closing_dates = $closing_dates;
if ($cal_closing_dates) {
	foreach ($cal_closing_dates as $ccdk => $ccdv) {
		if (!(count($ccdv) == 2)) {
			continue;
		}
		$cal_closing_dates[$ccdk][0] = strtotime($ccdv[0]);
		$cal_closing_dates[$ccdk][1] = strtotime($ccdv[1]);
	}
}

if ($numcalendars > 0) {
	$pmonth = VikRequest::getInt('month', '', 'request');
	$arr = getdate();
	$mon = $arr['mon'];
	$realmon = ($mon < 10 ? "0".$mon : $mon);
	$year = $arr['year'];
	$day = $realmon."/01/".$year;
	$dayts = strtotime($day);
	$validmonth = false;
	if ($pmonth > 0 && $pmonth >= $dayts) {
		$validmonth = true;
	}

	/**
	 * Default number of future months is 12, but if a max date in the future is defined for at least one month, we use that number.
	 * 
	 * @since 	1.13.5 (J) - 1.3.5 (WP)
	 */
	$max_months_future = 12;
	$lim_months = $max_months_future;
	if (!empty($max_date_future)) {
		$numlim = (int)substr($max_date_future, 1, (strlen($max_date_future) - 2));
		$numlim = $numlim < 1 ? 1 : $numlim;
		$quantlim = substr($max_date_future, -1, 1);
		if ($quantlim == 'm' || $quantlim == 'y') {
			$max_months_future = $numlim * ($quantlim == 'm' ? 1 : 12);
			$lim_months = $max_months_future + 1 - $numcalendars + 1;
			$lim_months = $lim_months < 0 ? 1 : $lim_months;
		}
	}
	//

	$moptions = "";
	for ($i = 0; $i < $lim_months; $i++) {
		$moptions .= "<option value=\"".$dayts."\"".($validmonth && $pmonth == $dayts ? " selected=\"selected\"" : "").">".VikBooking::sayMonth($arr['mon'])." ".$arr['year']."</option>\n";
		$next = $arr['mon'] + 1;
		$dayts = mktime(0, 0, 0, $next, 1, $arr['year']);
		$arr = getdate($dayts);
	}
	?>

<div id="vbo-bookingpart-init"></div>

<div class="vbo-availcalendars-cont">

	<h4><?php echo JText::translate('VBOAVAILABILITYCALENDAR'); ?></h4>
	
	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=roomdetails&roomid='.$this->room['id'].'&Itemid='.$pitemid); ?>" method="post" name="vbmonths">
		<select name="month" onchange="javascript: document.vbmonths.submit();" class="vbselectm"><?php echo $moptions; ?></select>
		<input type="hidden" name="checkin" id="checkin-hidden" value="" />
		<input type="hidden" name="promo" id="promo-hidden" value="" />
		<input type="hidden" name="booknow" value="1" />
		<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
	</form>
	
	<div class="vblegendediv">
	
		<span class="vblegenda"><span class="vblegenda-status vblegfree">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGFREE'); ?></span></span>
	<?php
	if ($showpartlyres) {
		?>
		<span class="vblegenda"><span class="vblegenda-status vblegwarning">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGWARNING'); ?></span></span>
		<?php
	}
	if ($showcheckinoutonly) {
		?>
		<span class="vblegenda"><span class="vblegenda-status vblegbusycheckout">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGBUSYCHECKOUT'); ?></span></span>
		<span class="vblegenda"><span class="vblegenda-status vblegbusycheckin">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGBUSYCHECKIN'); ?></span></span>
		<?php
	}
	?>
		<span class="vblegenda"><span class="vblegenda-status vblegbusy">&nbsp;</span> <span class="vblegenda-lbl"><?php echo JText::translate('VBLEGBUSY'); ?></span></span>
		
	</div>
	
	<?php
	$check = is_array($this->busy);
	if ($validmonth) {
		$arr = getdate($pmonth);
		$mon = $arr['mon'];
		$realmon = ($mon < 10 ? "0".$mon : $mon);
		$year = $arr['year'];
		$day = $realmon."/01/".$year;
		$dayts = strtotime($day);
		$newarr = getdate($dayts);
	} else {
		$arr = getdate();
		$mon = $arr['mon'];
		$realmon = ($mon < 10 ? "0".$mon : $mon);
		$year = $arr['year'];
		$day = $realmon."/01/".$year;
		$dayts = strtotime($day);
		$newarr = getdate($dayts);
	}

	// price calendar
	$veryfirst = $newarr[0];
	$untilmonth = (int)$newarr['mon'] + intval(($numcalendars - 1));
	$addyears = $untilmonth > 12 ? intval(($untilmonth / 12)) : 0;
	$monthop = $addyears > 0 ? ($addyears * 12) : 0;
	$untilmonth = $untilmonth > 12 ? ($untilmonth - $monthop) : $untilmonth;
	$verylast = mktime(23, 59, 59, $untilmonth, date('t', mktime(0, 0, 0, $untilmonth, 1, ($newarr['year'] + $addyears))), ($newarr['year'] + $addyears));

	$priceseasons = [];
	$roomrate = [];
	$assumedailycost = 0;
	$usepricecal = false;
	if (intval(VikBooking::getRoomParam('pricecal', $rparams)) == 1) {
		// turn flag on
		$usepricecal = true;

		/**
		 * In order to avoid special prices of another year to be applied over the months of the current year,
		 * in case the number of months displayed includes two years (Nov, Dec and Jan), we need to perform
		 * one call per year to properly apply the special prices of the right years.
		 * 
		 * @since 	1.4.3
		 */
		$first_year  = date('Y', $veryfirst);
		$last_year   = date('Y', $verylast);
		$parse_dates = [];
		// one call per year involved
		$checking_year = $first_year;
		$checking_first = $veryfirst;
		while ($checking_year <= $last_year) {
			if ($checking_year < $last_year) {
				// parsing first year, or an year in between in case of 3+ years of month-calendars
				array_push($parse_dates, [
					'from' => $checking_first,
					'to'   => mktime(23, 59, 59, 12, 31, $checking_year)
				]);
				// update next date to check
				$checking_first = mktime(0, 0, 0, 1, 1, ($checking_year + 1));
			} else {
				// parsing the last year
				array_push($parse_dates, [
					'from' => $checking_first,
					'to'   => $verylast
				]);
			}
			// go to next year
			$checking_year++;
		}

		// assume nightly room base cost
		$assumedailycost = VikBooking::getRoomParam('defcalcost', $rparams);
		$assumedailycost = empty($assumedailycost) && !empty($this->room['base_cost']) ? $this->room['base_cost'] : $assumedailycost;

		/**
		 * Get the default room rate plan rates for a more accurate calculation of the nightly rates.
		 * 
		 * @since 	1.16.3 (J) - 1.6.3 (WP)
		 */
		$def_rplan_id = (int)VikBooking::getRoomParam('defrplan', $rparams);
		$roomrate = $def_rplan_id ? VBORoomHelper::getInstance($this->room)->getRatePlans($this->room['id'], $def_rplan_id) : [];

		if (!$roomrate) {
			// loop through all dates interval just built (old method with the default cost)
			foreach ($parse_dates as $dates_intv) {
				$dummy_checkin  = $dates_intv['from'];
				$dummy_checkout = $dates_intv['to'];
				$current_year 	= date('Y', $dummy_checkin);

				$assumedays = floor((($dummy_checkout - $dummy_checkin) / (60 * 60 * 24)));
				$assumedays++;
				$assumeprice = $assumedailycost * $assumedays;
				$parserates = [
					[
						'id' 			 => -1,
						'idroom' 		 => $this->room['id'],
						'days' 			 => $assumedays,
						'idprice' 		 => -1,
						'cost' 			 => $assumeprice,
						'booking_nights' => 1,
						'attrdata' 		 => '',
					]
				];
				$priceseasons[$current_year] = VikBooking::applySeasonsRoom($parserates, $dummy_checkin, $dummy_checkout);
			}
		}
		?>
		<p class="vbpricecalwarning"><?php echo JText::translate('VBPRICECALWARNING'); ?></p>
		<?php
	}

	$firstwday = (int)VikBooking::getFirstWeekDay();
	$days_labels = [
		JText::translate('VBSUN'),
		JText::translate('VBMON'),
		JText::translate('VBTUE'),
		JText::translate('VBWED'),
		JText::translate('VBTHU'),
		JText::translate('VBFRI'),
		JText::translate('VBSAT')
	];
	$days_indexes = [];
	for ($i = 0; $i < 7; $i++) {
		$days_indexes[$i] = (6 - ($firstwday - $i) + 1) % 7;
	}
	?>
	<div class="vbcalsblock <?php echo ($usepricecal === true ? 'vbcalsblock-price' : 'vbcalsblock-regular'); ?>">
	<?php
	$today_ts = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
	$previousdayclass = "";
	for ($jj = 1; $jj <= $numcalendars; $jj++) {
		$d_count = 0;
		$cal = "";
		?>
		<div class="vbcaldivcont">
			<table class="<?php echo ($usepricecal === true ? 'vbcalprice' : 'vbcal'); ?>">
				<tr class="vbcaltrmonth">
					<td colspan="7" align="center" class="vbo-pref-bordercolor">
						<strong class="vbcaltrmonth-month"><?php echo VikBooking::sayMonth($newarr['mon']); ?></strong>
						<strong class="vbcaltrmonth-year"><?php echo $newarr['year']; ?></strong>
					</td>
				</tr>
				<tr class="vbcaldays">
				<?php
				for ($i = 0; $i < 7; $i++) {
					$d_ind = ($i + $firstwday) < 7 ? ($i + $firstwday) : ($i + $firstwday - 7);
					echo '<td>'.$days_labels[$d_ind].'</td>';
				}
				?>
				</tr>
				<tr class="<?php echo $usepricecal === true ? 'vbcalnumdaysprice' : 'vbcalnumdays'; ?>">
				<?php
				for ($i = 0, $n = $days_indexes[$newarr['wday']]; $i < $n; $i++, $d_count++) {
					$cal .= "<td class=\"vbtdempty\" align=\"center\">&nbsp;</td>";
				}
				while ($newarr['mon'] == $mon) {
					if ($d_count > 6) {
						$d_count = 0;
						$cal .= "</tr>\n<tr class=\"".($usepricecal === true ? 'vbcalnumdaysprice' : 'vbcalnumdays')."\">";
					}
					$dclass = "vbtdfree";
					$dalt = "";
					$bid = "";
					$totfound = 0;
					if ($check) {
						$ischeckinday = false;
						$ischeckoutday = false;
						foreach ($this->busy as $b) {
							$info_in = getdate($b['checkin']);
							$checkin_ts = mktime(0, 0, 0, $info_in['mon'], $info_in['mday'], $info_in['year']);
							$info_out = getdate($b['checkout']);
							$checkout_ts = mktime(0, 0, 0, $info_out['mon'], $info_out['mday'], $info_out['year']);
							if ($newarr[0] >= $checkin_ts && $newarr[0] == $checkout_ts) {
								$ischeckoutday = true;
							}
							if ($newarr[0] >= $checkin_ts && $newarr[0] < $checkout_ts) {
								$totfound++;
								if ($newarr[0] == $checkin_ts) {
									$ischeckinday = true;
								}
							}
						}
						if ($totfound >= $this->room['units']) {
							$dclass = "vbtdbusy";
							if ($ischeckinday && $showcheckinoutonly && $inonout_allowed && $previousdayclass != "vbtdbusy" && $previousdayclass != "vbtdbusy vbtdbusyforcheckin") {
								$dclass = "vbtdbusy vbtdbusyforcheckin";
							} elseif ($ischeckinday && !$inonout_allowed && $previousdayclass != "vbtdbusy" && $previousdayclass != "vbtdbusy vbtdbusyforcheckin") {
								//check-out not allowed on a day where someone is already checking-in
								$dclass = "vbtdbusy";
							}
						} elseif ($totfound > 0) {
							if ($showpartlyres) {
								$dclass = "vbtdwarning";
							}
						} else {
							if ($ischeckoutday && $showcheckinoutonly && $inonout_allowed && !($this->room['units'] > 1)) {
								$dclass = "vbtdbusy vbtdbusyforcheckout";
							} elseif ($ischeckoutday && !$inonout_allowed && !($this->room['units'] > 1)) {
								$dclass = "vbtdbusy";
							}
						}
					}
					if ($cal_closing_dates) {
						foreach ($cal_closing_dates as $closed_interval) {
							if ($newarr[0] >= $closed_interval[0] && $newarr[0] <= $closed_interval[1]) {
								$dclass = "vbtdbusy";
								break;
							}
						}
					}
					$previousdayclass = $dclass;
					$useday = ($newarr['mday'] < 10 ? "0".$newarr['mday'] : $newarr['mday']);
					// price calendar
					$useday = $usepricecal === true ? '<div class="vbcalpricedaynum"><span>'.$useday.'</span></div>' : $useday;
					if ($usepricecal === true) {
						$todaycost = $assumedailycost;
						if ($roomrate) {
							// new accurate calculation method (slower)
							$today_tsin = mktime($hcheckin, $mcheckin, 0, $newarr['mon'], $newarr['mday'], $newarr['year']);
							$today_tsout = mktime($hcheckout, $mcheckout, 0, $newarr['mon'], ($newarr['mday'] + 1), $newarr['year']);
							$tars = VikBooking::applySeasonsRoom([$roomrate], $today_tsin, $today_tsout);
							$todaycost = $tars[0]['cost'];
						} else {
							// fallback to old default cost (faster)
							$check_priceseasons = isset($priceseasons[$newarr['year']]) ? $priceseasons[$newarr['year']][0] : [];
							if (array_key_exists('affdayslist', $check_priceseasons) && array_key_exists($newarr['wday'].'-'.$newarr['mday'].'-'.$newarr['mon'], $check_priceseasons['affdayslist'])) {
								$todaycost = $check_priceseasons['affdayslist'][$newarr['wday'].'-'.$newarr['mday'].'-'.$newarr['mon']];
							}
						}
						$writecost = ($todaycost - intval($todaycost)) > 0.00 ? VikBooking::numberFormat($todaycost) : number_format($todaycost, 0);
						$useday .= '<div class="vbcalpricedaycost"><div><span class="vbo_currency">' . $currencysymb . '</span> <span class="vbo_price">' . $writecost . '</span></div></div>';
					} else {
						$useday = '<span>' . $useday . '</span>';
					}
					//
					$past_dclass = $newarr[0] < $today_ts ? ' vbtdpast' : '';
					if ($totfound == 1) {
						$cal .= "<td align=\"center\" class=\"" . $dclass . $past_dclass . "\" data-daydate=\"" . date($df, $newarr[0]) . "\" data-ymd=\"" . date('Y-m-d', $newarr[0]) . "\">" . $useday . "</td>\n";
					} elseif ($totfound > 1) {
						$cal .= "<td align=\"center\" class=\"" . $dclass . $past_dclass . "\" data-daydate=\"" . date($df, $newarr[0]) . "\" data-ymd=\"" . date('Y-m-d', $newarr[0]) . "\">" . $useday . "</td>\n";
					} else {
						$cal .= "<td align=\"center\" class=\"" . $dclass . $past_dclass . "\" data-daydate=\"" . date($df, $newarr[0]) . "\" data-ymd=\"" . date('Y-m-d', $newarr[0]) . "\">" . $useday . "</td>\n";
					}
					$next = $newarr['mday'] + 1;
					$dayts = mktime(0, 0, 0, $newarr['mon'], $next, $newarr['year']);
					$newarr = getdate($dayts);
					$d_count++;
				}

				for ($i = $d_count; $i <= 6; $i++) {
					$cal .= "<td class=\"vbtdempty\" align=\"center\">&nbsp;</td>";
				}

				echo $cal;
				?>
				</tr>
			</table>
		</div>
		<?php
		if ($mon == 12) {
			$mon = 1;
			$year += 1;
			$dayts = mktime(0, 0, 0, $mon, 1, $year);
		} else {
			$mon += 1;
			$dayts = mktime(0, 0, 0, $mon, 1, $year);
		}
		$newarr = getdate($dayts);
		
		if (($jj % 3) == 0) {
			echo "";
		}
	}
	?>
	</div>
</div>
	<?php
	/**
	 * If not pricing calendar, we allow the AJAX navigation between the months.
	 * 
	 * @since 	1.13.5
	 */
	if (!$usepricecal) {
		$nav_next = (strtotime("+{$max_months_future} months") > $newarr[0]);
		$nav_next_start = date('Y-m-d', $newarr[0]);
		$lim_past_ts = mktime(0, 0, 0, date('n'), 1, date('Y'));
		$months_back_ts = strtotime("-{$numcalendars} months", $newarr[0]);
		$nav_prev_start = date('Y-m-d', $months_back_ts);
		$nav_prev = ($months_back_ts > $lim_past_ts);
		?>
<script type="text/javascript">
var vboAvCalsNavNext = '<?php echo $nav_next_start; ?>';
var vboAvCalsNavPrev = '<?php echo $nav_prev_start; ?>';
var vboAvCalsNavLoading = false;
jQuery(function() {
<?php
if ($nav_next) {
	?>
	// add forward navigation
	jQuery('.vbcaldivcont').last().find('.vbcaltrmonth td').append('<span class="vbo-rdet-avcal-nav vbo-rdet-avcal-nav-next vbo-pref-color-btn">&gt;</span>');
	<?php
}
if ($nav_prev) {
	?>
	// add backward navigation
	jQuery('.vbcaldivcont').first().find('.vbcaltrmonth td').prepend('<span class="vbo-rdet-avcal-nav vbo-rdet-avcal-nav-prev vbo-pref-color-btn">&lt;</span>');
	<?php
}
?>
	jQuery(document.body).on('click', '.vbo-rdet-avcal-nav', function() {
		if (vboAvCalsNavLoading) {
			// prevent double submissions
			return false;
		}
		var direction = jQuery(this).hasClass('vbo-rdet-avcal-nav-prev') ? 'prev' : 'next';
		jQuery('.vbcaldivcont').addClass('vbcaldivcont-loading');
		vboAvCalsNavLoading = true;
		// make the AJAX request to the controller to request the new availability calendars
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl(JRoute::rewrite('index.php?option=com_vikbooking&task=get_avcalendars_data&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)); ?>",
			data: {
				option: "com_vikbooking",
				task: "get_avcalendars_data",
				rid: "<?php echo $this->room['id']; ?>",
				direction: direction,
				fromdt: (direction == 'next' ? vboAvCalsNavNext : vboAvCalsNavPrev),
				nextdt: vboAvCalsNavNext,
				prevdt: vboAvCalsNavPrev
			}
		}).done(function(res) {
			// parse the JSON response that contains the calendars objects for the requested navigation
			try {
				var cal_data = typeof res === 'string' ? JSON.parse(res) : res;
				
				if (!cal_data || !cal_data['calendars'] || !cal_data['calendars'].length) {
					console.error('no availability calendars to parse');
					return false;
				}

				// total number of calendars returned by the navigation (1 by default)
				var tot_new_calendars = cal_data['calendars'].length;
				var new_calendars_parsed = 0;

				// build the new calendar(s)
				for (var i in cal_data['calendars']) {
					if (!cal_data['calendars'].hasOwnProperty(i)) {
						continue;
					}
					// start table
					var cal_html = '<div class="vbcaldivcont">' + "\n";
					cal_html += '<table class="vbcal">' + "\n";
					cal_html += '<tbody>' + "\n";
					// month name row
					cal_html += '<tr class="vbcaltrmonth">' + "\n";
					cal_html += '<td class="vbo-pref-bordercolor" colspan="7" align="center">' + "\n";
					cal_html += '<strong class="vbcaltrmonth-month">' + cal_data['calendars'][i].month + '</strong> <strong class="vbcaltrmonth-year">' + cal_data['calendars'][i].year + '</strong>' + "\n";
					cal_html += '</td>' + "\n";
					cal_html += '</tr>' + "\n";
					// ordered week days row
					cal_html += '<tr class="vbcaldays">' + "\n";
					for (var w in cal_data['calendars'][i]['wdays']) {
						if (!cal_data['calendars'][i]['wdays'].hasOwnProperty(w)) {
							continue;
						}
						cal_html += '<td>' + cal_data['calendars'][i]['wdays'][w] + '</td>' + "\n";
					}
					cal_html += '</tr>' + "\n";
					// calendar week rows
					for (var r in cal_data['calendars'][i]['rows']) {
						if (!cal_data['calendars'][i]['rows'].hasOwnProperty(r)) {
							continue;
						}
						// start calendar week row
						cal_html += '<tr class="vbcalnumdays">' + "\n";
						// loop over the cell dates of this row
						var rowcells = cal_data['calendars'][i]['rows'][r];
						for (var rc in rowcells) {
							if (!rowcells.hasOwnProperty(rc) || !rowcells[rc].hasOwnProperty('type')) {
								continue;
							}
							if (rowcells[rc]['type'] != 'day') {
								// empty cell placeholder
								cal_html += '<td align="center">' + rowcells[rc]['cont'] + '</td>' + "\n";
							} else {
								// real day cell
								cal_html += '<td align="center" class="' + rowcells[rc]['class'] + rowcells[rc]['past_class'] + '" data-daydate="' + rowcells[rc]['dt'] + '" data-ymd="' + rowcells[rc]['ymd'] + '"><span>' + rowcells[rc]['cont'] + '</span></td>' + "\n";
							}
						}
						// finalise calendar week row
						cal_html += '</tr>' + "\n";
					}
					// finalise table
					cal_html += '</tbody>' + "\n";
					cal_html += '</table>' + "\n";
					cal_html += '</div>';

					// remove first or last calendar, then prepend or append this calendar depending on the direction
					var cur_old_cal_index = direction == 'next' ? (jQuery('.vbcaldivcont').length - 1) : new_calendars_parsed;
					if (direction == 'next') {
						jQuery('.vbcaldivcont').eq(cur_old_cal_index).after(cal_html);
						jQuery('.vbcaldivcont').first().remove();
					} else {
						jQuery('.vbcaldivcont').eq(cur_old_cal_index).before(cal_html);
						jQuery('.vbcaldivcont').last().remove();
					}

					// increase parsed calendars counter
					new_calendars_parsed++;
				}

				// update navigation dates
				if (cal_data['next_ymd']) {
					vboAvCalsNavNext = cal_data['next_ymd'];
				}
				if (cal_data['prev_ymd']) {
					vboAvCalsNavPrev = cal_data['prev_ymd'];
				}

				// stop loading
				jQuery('.vbcaldivcont').removeClass('vbcaldivcont-loading');
				vboAvCalsNavLoading = false;

				// restore navigation arrows
				jQuery('.vbo-rdet-avcal-nav').remove();
				if (cal_data['can_nav_next']) {
					jQuery('.vbcaldivcont').last().find('.vbcaltrmonth td').append('<span class="vbo-rdet-avcal-nav vbo-rdet-avcal-nav-next vbo-pref-color-btn">&gt;</span>');
				}
				if (cal_data['can_nav_prev']) {
					jQuery('.vbcaldivcont').first().find('.vbcaltrmonth td').prepend('<span class="vbo-rdet-avcal-nav vbo-rdet-avcal-nav-prev vbo-pref-color-btn">&lt;</span>');
				}
			} catch (e) {
				console.log(e);
				alert('Invalid response');
				jQuery('.vbcaldivcont').removeClass('vbcaldivcont-loading');
				vboAvCalsNavLoading = false;
				return false;
			}
		}).fail(function(err) {
			console.error(err);
			alert('Could not navigate');
			jQuery('.vbcaldivcont').removeClass('vbcaldivcont-loading');
			vboAvCalsNavLoading = false;
		});
	});
});
</script>
		<?php
	}
}
