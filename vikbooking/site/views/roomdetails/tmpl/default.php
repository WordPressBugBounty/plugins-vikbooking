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

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadDatePicker(['type' => 'dates_range']);

// register lang vars for JS
JText::script('VBODISTFEATURERUNIT');
JText::script('VBO_GEO_ADDRESS');
JText::script('VBSELPRDATE');

$is_mobile = VikBooking::detectUserAgent(false, false);

$def_nights_cal = VikBooking::getDefaultNightsCalendar();
$vbdateformat = VikBooking::getDateFormat();
$datesep = VikBooking::getDateSeparator();
// datepicker calendars
if ($vbdateformat == "%d/%m/%Y") {
	$juidf = 'dd/mm/yy';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$juidf = 'mm/dd/yy';
} else {
	$juidf = 'yy/mm/dd';
}

// JSON-decode room parameters
$rparams = json_decode($this->room['params'], true);

// room layout name
$layout = VikBooking::getRoomParam('layout_style', $rparams, 'classic');

$min_days_advance = VikBooking::getMinDaysAdvance();
$max_date_future  = VikBooking::getMaxDateFuture($this->room['id']);

// load assets
$document = JFactory::getDocument();
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery.fancybox.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery.fancybox.js');
$navdecl = '
jQuery(function() {
	jQuery(".vbomodalframe").fancybox({
		"helpers": {
			"overlay": {
				"locked": false
			}
		},
		"width": "75%",
		"height": "75%",
	    "autoScale": false,
	    "transitionIn": "none",
		"transitionOut": "none",
		"padding": 0,
		"type": "iframe"
	});
});';
$document->addScriptDeclaration($navdecl);
$document->addStyleSheet(VBO_SITE_URI.'resources/vikfxgallery.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/vikfxgallery.js');

// photo gallery script declaration
if (!empty($this->room['moreimgs'])) {
	$vikfx = '
jQuery(function() {
	window["vikfxgallery"] = jQuery(".vikfx-gallery a").vikFxGallery();
	jQuery(".vikfx-gallery-previous-image, .vikfx-gallery-next-image, .vikfx-gallery-open").click(function() {
		if (typeof window["vikfxgallery"] !== "undefined") {
			window["vikfxgallery"].open();
		}
	});
});';
	$document->addScriptDeclaration($vikfx);
}

// set up script declarations for datepicker
$declarations = [];

// helper functions
$declarations[] = <<<JAVASCRIPT
jQuery.noConflict();

function vbGetDateObject(dstring) {
	var dparts = dstring.split("-");
	return new Date(dparts[0], (parseInt(dparts[1]) - 1), parseInt(dparts[2]), 0, 0, 0, 0);
}

function vbFullObject(obj) {
	var jk;
	for (jk in obj) {
		return obj.hasOwnProperty(jk);
	}
}

var vbrestrctarange, vbrestrctdrange, vbrestrcta, vbrestrctd;

JAVASCRIPT;

/**
 * We need to exclude from the datepicker calendars all fully booked dates.
 * For this reason we loop for 18 months in the future for the sole purpose
 * of pushing other dates onto $push_disabled_in/out.
 */
$push_disabled_in = [];
$push_disabled_out = [];
$inonout_allowed = true;
$timeopst = VikBooking::getTimeOpenStore();
if (is_array($timeopst)) {
	if ($timeopst[0] < $timeopst[1]) {
		// check-in not allowed on a day where there is already a check out (no arrivals/depatures on the same day)
		$inonout_allowed = false;
	}
}
if (is_array($this->busy)) {
	// start date from today
	$now_info = getdate();
	// we loop for the next 18 months ahead to disable dates for check-in/check-out
	$max_ts = mktime(23, 59, 59, $now_info['mon'] + 18, $now_info['mday'], $now_info['year']);
	// loop until the maximum date in the future
	$wasprevbusy = false;
	while ($now_info[0] < $max_ts) {
		$totfound      = 0;
		$ischeckinday  = false;
		$ischeckoutday = false;
		foreach ($this->busy as $b) {
			$info_in = getdate($b['checkin']);
			$checkin_ts = mktime(0, 0, 0, $info_in['mon'], $info_in['mday'], $info_in['year']);
			$info_out = getdate($b['checkout']);
			$checkout_ts = mktime(0, 0, 0, $info_out['mon'], $info_out['mday'], $info_out['year']);
			if ($now_info[0] >= $checkin_ts && $now_info[0] == $checkout_ts) {
				$ischeckoutday = true;
			}
			if ($now_info[0] >= $checkin_ts && $now_info[0] < $checkout_ts) {
				$totfound++;
				if ($now_info[0] == $checkin_ts) {
					$ischeckinday = true;
				}
			}
		}
		if ($totfound >= $this->room['units']) {
			$push_disabled_in[] = date('Y-m-d', $now_info[0]);
			if (!$ischeckinday || $wasprevbusy) {
				$push_disabled_out[] = date('Y-m-d', $now_info[0]);
			} elseif ($ischeckinday && !$inonout_allowed && !$wasprevbusy) {
				// check-out not allowed on a day where someone is already checking-in
				$push_disabled_out[] = date('Y-m-d', $now_info[0]);
			}
			// update previous day status only after checking the disabled out date
			$wasprevbusy = true;
		} else {
			// update flag
			$wasprevbusy = false;
			if (!$totfound && $ischeckoutday && !$inonout_allowed && !($this->room['units'] > 1)) {
				$push_disabled_in[] = date('Y-m-d', $now_info[0]);
			}
		}
		// go to next day
		$now_info = getdate(mktime(0, 0, 0, $now_info['mon'], ($now_info['mday'] + 1), $now_info['year']));
	}
}

// room-level restrictions
$restrictions = VikBooking::loadRestrictions(true, [$this->room['id']]);

// restrictions
$totrestrictions               = count($restrictions);
$wdaysrestrictions             = [];
$wdaystworestrictions          = [];
$wdaysrestrictionsrange        = [];
$wdaysrestrictionsmonths       = [];
$ctarestrictionsrange          = [];
$ctarestrictionsmonths         = [];
$ctdrestrictionsrange          = [];
$ctdrestrictionsmonths         = [];
$monthscomborestr              = [];
$minlosrestrictions            = [];
$minlosrestrictionsrange       = [];
$maxlosrestrictions            = [];
$maxlosrestrictionsrange       = [];
$notmultiplyminlosrestrictions = [];

if ($totrestrictions > 0) {
	foreach ($restrictions as $rmonth => $restr) {
		if ($rmonth != 'range') {
			if (strlen((string)$restr['wday'])) {
				$wdaysrestrictions[($rmonth - 1)] = $restr['wday'];
				$wdaysrestrictionsmonths[] = $rmonth;
				if (strlen((string)$restr['wdaytwo'])) {
					$wdaystworestrictions[($rmonth - 1)] = $restr['wdaytwo'];
					$monthscomborestr[($rmonth - 1)] = VikBooking::parseJsDrangeWdayCombo($restr);
				}
			} elseif (!empty($restr['ctad']) || !empty($restr['ctdd'])) {
				if (!empty($restr['ctad'])) {
					$ctarestrictionsmonths[($rmonth - 1)] = explode(',', $restr['ctad']);
				}
				if (!empty($restr['ctdd'])) {
					$ctdrestrictionsmonths[($rmonth - 1)] = explode(',', $restr['ctdd']);
				}
			}
			if ($restr['multiplyminlos'] == 0) {
				$notmultiplyminlosrestrictions[] = $rmonth;
			}
			$minlosrestrictions[($rmonth - 1)] = $restr['minlos'];
			if (!empty($restr['maxlos']) && $restr['maxlos'] > 0 && $restr['maxlos'] >= $restr['minlos']) {
				$maxlosrestrictions[($rmonth - 1)] = $restr['maxlos'];
			}
		} else {
			foreach ($restr as $kr => $drestr) {
				if (strlen((string)$drestr['wday'])) {
					$wdaysrestrictionsrange[$kr][0] = date('Y-m-d', $drestr['dfrom']);
					$wdaysrestrictionsrange[$kr][1] = date('Y-m-d', $drestr['dto']);
					$wdaysrestrictionsrange[$kr][2] = $drestr['wday'];
					$wdaysrestrictionsrange[$kr][3] = $drestr['multiplyminlos'];
					$wdaysrestrictionsrange[$kr][4] = strlen((string)$drestr['wdaytwo']) ? $drestr['wdaytwo'] : -1;
					$wdaysrestrictionsrange[$kr][5] = VikBooking::parseJsDrangeWdayCombo($drestr);
				} elseif (!empty($drestr['ctad']) || !empty($drestr['ctdd'])) {
					$ctfrom = date('Y-m-d', $drestr['dfrom']);
					$ctto = date('Y-m-d', $drestr['dto']);
					if (!empty($drestr['ctad'])) {
						$ctarestrictionsrange[$kr][0] = $ctfrom;
						$ctarestrictionsrange[$kr][1] = $ctto;
						$ctarestrictionsrange[$kr][2] = explode(',', $drestr['ctad']);
					}
					if (!empty($drestr['ctdd'])) {
						$ctdrestrictionsrange[$kr][0] = $ctfrom;
						$ctdrestrictionsrange[$kr][1] = $ctto;
						$ctdrestrictionsrange[$kr][2] = explode(',', $drestr['ctdd']);
					}
				}
				$minlosrestrictionsrange[$kr][0] = date('Y-m-d', $drestr['dfrom']);
				$minlosrestrictionsrange[$kr][1] = date('Y-m-d', $drestr['dto']);
				$minlosrestrictionsrange[$kr][2] = $drestr['minlos'];
				if (!empty($drestr['maxlos']) && $drestr['maxlos'] > 0 && $drestr['maxlos'] >= $drestr['minlos']) {
					$maxlosrestrictionsrange[$kr] = $drestr['maxlos'];
				}
			}
			unset($restrictions['range']);
		}
	}

	// prepare JSON-encoded values
	$json_vals = [
		'vbrestrmonthswdays'    => json_encode($wdaysrestrictionsmonths),
		'vbrestrmonths'         => json_encode(array_keys($restrictions)),
		'vbrestrmonthscombojn'  => json_encode($monthscomborestr),
		'vbrestrminlos'         => json_encode((object) $minlosrestrictions),
		'vbrestrminlosrangejn'  => json_encode($minlosrestrictionsrange),
		'vbrestrmultiplyminlos' => json_encode($notmultiplyminlosrestrictions),
		'vbrestrmaxlos'         => json_encode((object) $maxlosrestrictions),
		'vbrestrmaxlosrangejn'  => json_encode($maxlosrestrictionsrange),
		'vbrestrwdaysrangejn'   => json_encode($wdaysrestrictionsrange),
		'vbrestrcta'            => json_encode($ctarestrictionsmonths),
		'vbrestrctarange'       => json_encode($ctarestrictionsrange),
		'vbrestrctd'            => json_encode($ctdrestrictionsmonths),
		'vbrestrctdrange'       => json_encode($ctdrestrictionsrange),
	];

	// start restrictions declaration
	$resdecl = <<<JAVASCRIPT
var vbrestrmonthswdays    = {$json_vals['vbrestrmonthswdays']};
var vbrestrmonths         = {$json_vals['vbrestrmonths']};
var vbrestrmonthscombojn  = {$json_vals['vbrestrmonthscombojn']};
var vbrestrminlos         = {$json_vals['vbrestrminlos']};
var vbrestrminlosrangejn  = {$json_vals['vbrestrminlosrangejn']};
var vbrestrmultiplyminlos = {$json_vals['vbrestrmultiplyminlos']};
var vbrestrmaxlos         = {$json_vals['vbrestrmaxlos']};
var vbrestrmaxlosrangejn  = {$json_vals['vbrestrmaxlosrangejn']};
var vbrestrwdaysrangejn   = {$json_vals['vbrestrwdaysrangejn']};
var vbrestrcta            = {$json_vals['vbrestrcta']};
var vbrestrctarange       = {$json_vals['vbrestrctarange']};
var vbrestrctd            = {$json_vals['vbrestrctd']};
var vbrestrctdrange       = {$json_vals['vbrestrctdrange']};
var vbcombowdays          = {};

function vbRefreshCheckout(darrive) {
	if (vbFullObject(vbcombowdays)) {
		var vbtosort = new Array();
		for (var vbi in vbcombowdays) {
			if (vbcombowdays.hasOwnProperty(vbi)) {
				var vbusedate = darrive;
				vbtosort[vbi] = vbusedate.setDate(vbusedate.getDate() + (vbcombowdays[vbi] - 1 - vbusedate.getDay() + 7) % 7 + 1);
			}
		}
		vbtosort.sort(function(da, db) {
			return da > db ? 1 : -1;
		});
		for (var vbnext in vbtosort) {
			if (vbtosort.hasOwnProperty(vbnext)) {
				var vbfirstnextd = new Date(vbtosort[vbnext]);
				jQuery('#checkindate').vboDatesRangePicker('checkout', 'minDate', vbfirstnextd);
				jQuery('#checkindate').vboDatesRangePicker('checkout', 'setcheckoutdate', vbfirstnextd);
				break;
			}
		}
	}
}

function vbSetMinCheckoutDate(selectedDate) {
	var minlos = {$def_nights_cal};
	var maxlosrange = 0;
	var nowcheckin = jQuery('#checkindate').vboDatesRangePicker('getCheckinDate');
	var nowd = nowcheckin.getDay();
	var nowcheckindate = new Date(nowcheckin.getTime());
	vbcombowdays = {};
	if (vbFullObject(vbrestrminlosrangejn)) {
		for (var rk in vbrestrminlosrangejn) {
			if (vbrestrminlosrangejn.hasOwnProperty(rk)) {
				var minldrangeinit = vbGetDateObject(vbrestrminlosrangejn[rk][0]);
				if (nowcheckindate >= minldrangeinit) {
					var minldrangeend = vbGetDateObject(vbrestrminlosrangejn[rk][1]);
					if (nowcheckindate <= minldrangeend) {
						minlos = parseInt(vbrestrminlosrangejn[rk][2]);
						if (vbFullObject(vbrestrmaxlosrangejn)) {
							if (rk in vbrestrmaxlosrangejn) {
								maxlosrange = parseInt(vbrestrmaxlosrangejn[rk]);
							}
						}
						if (rk in vbrestrwdaysrangejn && nowd in vbrestrwdaysrangejn[rk][5]) {
							vbcombowdays = vbrestrwdaysrangejn[rk][5][nowd];
						}
					}
				}
			}
		}
	}
	var nowm = nowcheckin.getMonth();
	if (vbFullObject(vbrestrmonthscombojn) && vbrestrmonthscombojn.hasOwnProperty(nowm)) {
		if (nowd in vbrestrmonthscombojn[nowm]) {
			vbcombowdays = vbrestrmonthscombojn[nowm][nowd];
		}
	}
	if (vbrestrmonths.includes((nowm + 1))) {
		minlos = parseInt(vbrestrminlos[nowm]);
	}
	nowcheckindate.setDate(nowcheckindate.getDate() + minlos);
	jQuery('#checkindate').vboDatesRangePicker('checkout', 'minDate', nowcheckindate);
	jQuery('#checkindate').vboDatesRangePicker('checkout', 'minStayNights', minlos);
	if (maxlosrange > 0) {
		var diffmaxminlos = maxlosrange - minlos;
		var maxcheckoutdate = new Date(nowcheckindate.getTime());
		maxcheckoutdate.setDate(maxcheckoutdate.getDate() + diffmaxminlos);
		jQuery('#checkindate').vboDatesRangePicker('checkout', 'maxDate', maxcheckoutdate);
	}
	if (nowm in vbrestrmaxlos) {
		var diffmaxminlos = parseInt(vbrestrmaxlos[nowm]) - minlos;
		var maxcheckoutdate = new Date(nowcheckindate.getTime());
		maxcheckoutdate.setDate(maxcheckoutdate.getDate() + diffmaxminlos);
		jQuery('#checkindate').vboDatesRangePicker('checkout', 'maxDate', maxcheckoutdate);
	}
	if (!vbFullObject(vbcombowdays)) {
		var is_checkout_disabled = false;
		if (typeof selectedDate !== 'undefined' && typeof jQuery('#checkindate').vboDatesRangePicker('drpoption', 'beforeShowDay.checkout') === 'function') {
			// let the datepicker validate if the min date to set for check-out is disabled due to CTD rules
			is_checkout_disabled = !jQuery('#checkindate').vboDatesRangePicker('drpoption', 'beforeShowDay.checkout')(nowcheckindate)[0];
		}
		if (!is_checkout_disabled) {
			jQuery('#checkindate').vboDatesRangePicker('checkout', 'setCheckoutDate', nowcheckindate);
		} else {
			setTimeout(() => {
				// make sure the minimum date just set for the checkout has not populated a CTD date that we do not want
				var current_out_dt = jQuery('#checkindate').vboDatesRangePicker('getCheckoutDate');
				if (current_out_dt && current_out_dt.getTime() === nowcheckindate.getTime()) {
					jQuery('#checkindate').vboDatesRangePicker('checkout', 'setCheckoutDate', null);
				}
			}, 100);
		}
	} else {
		vbRefreshCheckout(nowcheckin);
	}
}
JAVASCRIPT;

	if ($wdaysrestrictions || $wdaysrestrictionsrange) {
		$dfull_in = '';
		$dfull_out = '';

		if ($push_disabled_in) {
			$dfull_in = <<<JAVASCRIPT
var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
if (vbfulldays_in.includes(actd)) {
	return [false];
}
JAVASCRIPT;
		}

		if ($push_disabled_out) {
			$dfull_out = <<<JAVASCRIPT
var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
if (vbfulldays_out.includes(actd)) {
	return [false];
}
// exclude days after a fully booked day, because a date selection cannot contain a fully booked day in between.
var exclude_after = false;
var last_fully_booked = null;
var nowcheckin = jQuery('#checkindate').vboDatesRangePicker('getCheckinDate');
if (nowcheckin && vbfulldays_out.length) {
	var nowcheckindate = new Date(nowcheckin.getTime());
	nowcheckindate.setHours(0);
	nowcheckindate.setMinutes(0);
	nowcheckindate.setSeconds(0);
	nowcheckindate.setMilliseconds(0);
	for (var i in vbfulldays_out) {
		var nowfullday = new Date(vbfulldays_out[i]);
		nowfullday.setHours(0);
		nowfullday.setMinutes(0);
		nowfullday.setSeconds(0);
		nowfullday.setMilliseconds(0);
		exclude_after = (nowcheckindate <= nowfullday);
		if (exclude_after) {
			// selected check-in date is before a fully booked day
			last_fully_booked = nowfullday;
			break;
		}
	}
}
if (exclude_after) {
	date.setHours(0);
	date.setMinutes(0);
	date.setSeconds(0);
	date.setMilliseconds(0);
	if (date > last_fully_booked) {
		// current day for display is after a fully booked day, with a selected check-in day before a fully booked day. Disable it.
		return [false];
	}
}
JAVASCRIPT;
		}

		// prepare JSON-encoded values
		$json_vals = [
			'vbrestrwdays'    => json_encode((object) $wdaysrestrictions),
			'vbrestrwdaystwo' => json_encode((object) $wdaystworestrictions),
			'vbfulldays_in'   => json_encode($push_disabled_in),
			'vbfulldays_out'  => json_encode($push_disabled_out),
		];

		// finalize restrictions declaration
		$resdecl .= <<<JAVASCRIPT
var vbrestrwdays    = {$json_vals['vbrestrwdays']};
var vbrestrwdaystwo = {$json_vals['vbrestrwdaystwo']};
var vbfulldays_in   = {$json_vals['vbfulldays_in']};
var vbfulldays_out  = {$json_vals['vbfulldays_out']};

function vbIsDayDisabled(date) {
	if (!vbIsDayOpen(date) || !vboValidateCta(date)) {
		return [false];
	}
	var m = date.getMonth(), wd = date.getDay();
	if (vbFullObject(vbrestrwdaysrangejn)) {
		for (var rk in vbrestrwdaysrangejn) {
			if (vbrestrwdaysrangejn.hasOwnProperty(rk)) {
				var wdrangeinit = vbGetDateObject(vbrestrwdaysrangejn[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vbGetDateObject(vbrestrwdaysrangejn[rk][1]);
					if (date <= wdrangeend) {
						if (wd != vbrestrwdaysrangejn[rk][2]) {
							if (vbrestrwdaysrangejn[rk][4] == -1 || wd != vbrestrwdaysrangejn[rk][4]) {
								return [false];
							}
						}
					}
				}
			}
		}
	}

	{$dfull_in}

	if (vbFullObject(vbrestrwdays)) {
		if (!vbrestrmonthswdays.includes((m+1))) {
			return [true];
		}
		if (wd == vbrestrwdays[m]) {
			return [true];
		}
		if (vbFullObject(vbrestrwdaystwo)) {
			if (wd == vbrestrwdaystwo[m]) {
				return [true];
			}
		}
		return [false];
	}
	return [true];
}

function vbIsDayDisabledCheckout(date) {
	if (!vbIsDayOpen(date) || !vboValidateCtd(date)) {
		return [false];
	}
	var m = date.getMonth(), wd = date.getDay();

	{$dfull_out}

	if (vbFullObject(vbcombowdays)) {
		if (vbcombowdays.includes(wd)) {
			return [true];
		} else {
			return [false];
		}
	}
	if (vbFullObject(vbrestrwdaysrangejn)) {
		for (var rk in vbrestrwdaysrangejn) {
			if (vbrestrwdaysrangejn.hasOwnProperty(rk)) {
				var wdrangeinit = vbGetDateObject(vbrestrwdaysrangejn[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vbGetDateObject(vbrestrwdaysrangejn[rk][1]);
					if (date <= wdrangeend) {
						if (wd != vbrestrwdaysrangejn[rk][2] && vbrestrwdaysrangejn[rk][3] == 1) {
							return [false];
						}
					}
				}
			}
		}
	}
	if (vbFullObject(vbrestrwdays)) {
		if (!vbrestrmonthswdays.includes((m+1)) || vbrestrmultiplyminlos.includes((m+1))) {
			return [true];
		}
		if (wd == vbrestrwdays[m]) {
			return [true];
		}
		return [false];
	}
	return [true];
}
JAVASCRIPT;
	}

	// push restrictions JS declaration
	$declarations[] = $resdecl;
}

// fully booked dates
if ($push_disabled_in) {
	// prepare JSON-encoded values
	$json_vals = [
		'vbfulldays_in' => json_encode($push_disabled_in),
	];

	// push JS declaration
	$declarations[] = <<<JAVASCRIPT
var vbfulldays_in = {$json_vals['vbfulldays_in']};
function vbIsDayFull(date) {
	if (!vbIsDayOpen(date) || !vboValidateCta(date)) {
		return [false];
	}
	var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
	if (!vbfulldays_in.includes(actd)) {
		return [true];
	}
	return [false];
}
JAVASCRIPT;
}

if ($push_disabled_out) {
	// prepare JSON-encoded values
	$json_vals = [
		'vbfulldays_out' => json_encode($push_disabled_out),
	];

	// push JS declaration
	$declarations[] = <<<JAVASCRIPT
var vbfulldays_out = {$json_vals['vbfulldays_out']};
function vbIsDayFullOut(date) {
	if (!vbIsDayOpen(date) || !vboValidateCtd(date)) {
		return [false];
	}
	var actd = jQuery.datepicker.formatDate('yy-mm-dd', date);
	if (!vbfulldays_out.includes(actd)) {
		// exclude days after a fully booked day, because a date selection cannot contain a fully booked day in between.
		var exclude_after = false;
		var last_fully_booked = null;
		var nowcheckin = jQuery('#checkindate').vboDatesRangePicker('getCheckinDate');
		if (nowcheckin && vbfulldays_out.length) {
			var nowcheckindate = new Date(nowcheckin.getTime());
			nowcheckindate.setHours(0);
			nowcheckindate.setMinutes(0);
			nowcheckindate.setSeconds(0);
			nowcheckindate.setMilliseconds(0);
			for (var i in vbfulldays_out) {
				var nowfullday = new Date(vbfulldays_out[i]);
				nowfullday.setHours(0);
				nowfullday.setMinutes(0);
				nowfullday.setSeconds(0);
				nowfullday.setMilliseconds(0);
				exclude_after = (nowcheckindate <= nowfullday);
				if (exclude_after) {
					// selected check-in date is before a fully booked day
					last_fully_booked = nowfullday;
					break;
				}
			}
		}
		if (exclude_after) {
			date.setHours(0);
			date.setMinutes(0);
			date.setSeconds(0);
			date.setMilliseconds(0);
			if (date > last_fully_booked) {
				// current day for display is after a fully booked day, with a selected check-in day before a fully booked day. Disable it.
				return [false];
			}
		}
		return [true];
	}
	return [false];
}
JAVASCRIPT;
}

// prepare JSON-encoded values
$closing_dates = VikBooking::parseJsClosingDates();
$json_vals = [
	'vbclosingdates' => json_encode($closing_dates),
];

// dates validation
$dtdecl = <<<JAVASCRIPT
var vbclosingdates = {$json_vals['vbclosingdates']};

function vbCheckClosingDates(date) {
	if (!vbIsDayOpen(date)) {
		return [false];
	}
	return [true];
}

function vbIsDayOpen(date) {
	if (vbFullObject(vbclosingdates)) {
		for (var cd in vbclosingdates) {
			if (vbclosingdates.hasOwnProperty(cd)) {
				var cdfrom = vbGetDateObject(vbclosingdates[cd][0]);
				var cdto = vbGetDateObject(vbclosingdates[cd][1]);
				if (date >= cdfrom && date <= cdto) {
					return false;
				}
			}
		}
	}
	return true;
}

function vboCheckClosingDatesIn(date) {
	var isdayopen = vbIsDayOpen(date) && vboValidateCta(date);
	return [isdayopen];
}

function vboCheckClosingDatesOut(date) {
	var isdayopen = vbIsDayOpen(date) && vboValidateCtd(date);
	return [isdayopen];
}

function vboValidateCta(date) {
	var m = date.getMonth(), wd = date.getDay();
	if (vbFullObject(vbrestrctarange)) {
		for (var rk in vbrestrctarange) {
			if (vbrestrctarange.hasOwnProperty(rk)) {
				var wdrangeinit = vbGetDateObject(vbrestrctarange[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vbGetDateObject(vbrestrctarange[rk][1]);
					if (date <= wdrangeend) {
						if (vbrestrctarange[rk][2].includes('-'+wd+'-')) {
							return false;
						}
					}
				}
			}
		}
	}
	if (vbFullObject(vbrestrcta)) {
		if (vbrestrcta.hasOwnProperty(m) && vbrestrcta[m].includes('-'+wd+'-')) {
			return false;
		}
	}
	return true;
}

function vboValidateCtd(date) {
	var m = date.getMonth(), wd = date.getDay();
	if (vbFullObject(vbrestrctdrange)) {
		for (var rk in vbrestrctdrange) {
			if (vbrestrctdrange.hasOwnProperty(rk)) {
				var wdrangeinit = vbGetDateObject(vbrestrctdrange[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vbGetDateObject(vbrestrctdrange[rk][1]);
					if (date <= wdrangeend) {
						if (vbrestrctdrange[rk][2].includes('-'+wd+'-')) {
							return false;
						}
					}
				}
			}
		}
	}
	if (vbFullObject(vbrestrctd)) {
		if (vbrestrctd.hasOwnProperty(m) && vbrestrctd[m].includes('-'+wd+'-')) {
			return false;
		}
	}
	return true;
}

function vbSetGlobalMinCheckoutDate() {
	var nowcheckin = jQuery('#checkindate').vboDatesRangePicker('getCheckinDate');
	var nowcheckindate = new Date(nowcheckin.getTime());
	nowcheckindate.setDate(nowcheckindate.getDate() + {$def_nights_cal});
	jQuery('#checkindate').vboDatesRangePicker('checkout', 'minDate', nowcheckindate);
	jQuery('#checkindate').vboDatesRangePicker('checkout', 'minStayNights', '{$def_nights_cal}');
	jQuery('#checkindate').vboDatesRangePicker('checkout', 'setCheckoutDate', nowcheckindate);
}
JAVASCRIPT;

// push JS declaration
$declarations[] = $dtdecl;

// dates-range-picker rendering JS declaration
$numberOfMonths   = $is_mobile ? 1 : 2;
$beforeShowDayIn  = $wdaysrestrictions || $wdaysrestrictionsrange ? 'vbIsDayDisabled' : ($push_disabled_in ? 'vbIsDayFull' : 'vboCheckClosingDatesIn');
$beforeShowDayOut = $wdaysrestrictions || $wdaysrestrictionsrange ? 'vbIsDayDisabledCheckout' : ($push_disabled_out ? 'vbIsDayFullOut' : 'vboCheckClosingDatesOut');
$onSelectCheckin  = $totrestrictions ? 'vbSetMinCheckoutDate(selectedDate);' : 'vbSetGlobalMinCheckoutDate();';

$declarations[] = <<<JAVASCRIPT
jQuery(function() {
	// reset regional
	jQuery.datepicker.setDefaults(jQuery.datepicker.regional['']);

	// start DRP
	jQuery('#checkindate').vboDatesRangePicker({
		checkout: '#checkoutdate',
		dateFormat: '{$juidf}',
		showOn: 'focus',
		numberOfMonths: {$numberOfMonths},
		minDate: '{$min_days_advance}d',
		maxDate: '{$max_date_future}',
		beforeShowDay: {
			checkin: {$beforeShowDayIn},
			checkout: {$beforeShowDayOut},
		},
		onSelect: {
			checkin: (selectedDate) => {
				{$onSelectCheckin}
				vbCalcNights();
			},
			checkout: (selectedDate) => {
				vbCalcNights();
			},
		},
		labels: {
			checkin: Joomla.JText._('VBPICKUPROOM'),
			checkout: Joomla.JText._('VBRETURNROOM'),
			minStayNights: (nights) => {
				return (Joomla.JText._('VBO_MIN_STAY_NIGHTS') + '').replace('%d', nights);
			},
		},
		bottomCommands: {
			clear: Joomla.JText._('VBO_CLEAR_DATES'),
			close: Joomla.JText._('VBO_CLOSE'),
			onClear: () => {
				vbCalcNights();
			},
		},
	});

	// set proper regional
	jQuery('#checkindate').datepicker('option', jQuery.datepicker.regional['vikbooking']);

	// register additional triggers
	jQuery('.vb-cal-img, .vbo-caltrigger').click(function() {
		let dp = jQuery(this).prev('input');
		if (!dp.length) {
			return;
		}
		if (dp.hasClass('hasDatepicker')) {
			dp.focus();
		} else if (dp.attr('id') == 'checkoutdate') {
			jQuery('#checkindate').focus();
		}
	});
});
JAVASCRIPT;

// add script declarations to document
$document->addScriptDeclaration(implode("\n", $declarations));

/**
 * Render proper room/listing layout according to parameters.
 * 
 * @since 	1.17.3 (J) - 1.7.3 (WP)
 */
if (!strcasecmp($layout, 'listing')) {
	// render listing-style template
	echo $this->loadTemplate('listing');
} else {
	// fallback onto default room-style template
	echo $this->loadTemplate('classic');
}
