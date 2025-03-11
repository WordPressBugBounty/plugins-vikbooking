<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_horizontalsearch
 * @author      Alessio Gaggii - E4J s.r.l
 * @copyright   Copyright (C) 2025 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$dbo = JFactory::getDbo();
$vbo_tn = VikBooking::getTranslator();
$is_mobile = VikBooking::detectUserAgent(false, false);

$document = JFactory::getDocument();
$document->addStyleSheet($baseurl.'modules/mod_vikbooking_horizontalsearch/mod_vikbooking_horizontalsearch.css');
// load jQuery UI
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery-ui.min.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.min.js');

// load DRP assets
VikBooking::getVboApplication()->loadDatesRangePicker(['type' => 'dates_range']);

$timeopst = VikBooking::getTimeOpenStore();
$restrictions = VikBooking::loadRestrictions();
$vbdateformat = VikBooking::getDateFormat();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

// define the dates layout type
$dates_layout_type = $params->get('datestype');
// whether the inquiry form should be displayed
$needs_inquiry = ($dates_layout_type == 'inquiry');
// inquiry layout has got the same output as the human
$dates_layout_type = $dates_layout_type == 'inquiry' ? 'human' : $dates_layout_type;

// define the apposite wrapper class
$wrapper_class = 'vbo-horizsearch-standardformat-wrap';
if ($params->get('datestype') == 'human') {
	$wrapper_class = 'vbo-horizsearch-humanformat-wrap';
} elseif ($params->get('datestype') == 'inquiry') {
	$wrapper_class = 'vbo-horizsearch-inquiryformat-wrap';
}

// language definitions for JS
JText::script('VBO_PLEASE_FILL_FIELDS');
JText::script('VBO_PLEASE_SEL_DATES');
JText::script('VBO_THANKS_INQ_SUBMITTED');

?>

<div class="vbmodhorsearchmaindiv <?php echo $wrapper_class; ?>">
	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=search&Itemid=' . $params->get('itemid', 0)); ?>" method="post" id="vbo-mod-horsearch-form-<?php echo $randid; ?>">
		<input type="hidden" name="task" value="search" />
<?php
if (intval($params->get('room_id')) > 0) {
	?>
		<input type="hidden" name="roomdetail" value="<?php echo $params->get('room_id'); ?>" />
	<?php
}
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

if ($vbdateformat == "%d/%m/%Y") {
	$juidf = 'dd/mm/yy';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$juidf = 'mm/dd/yy';
} else {
	$juidf = 'yy/mm/dd';
}

// lang for jQuery UI Calendar
$is_rtl_str = 'false';
$now_lang = JFactory::getLanguage();
if (method_exists($now_lang, 'isRtl')) {
	$is_rtl_str = $now_lang->isRtl() ? 'true' : $is_rtl_str;
}

$ldecl = '
jQuery.noConflict();
jQuery(function($) {'."\n".'
	$.datepicker.regional["vikbookingmod"] = {'."\n".'
		closeText: "'.JText::translate('VBJQCALDONE').'",'."\n".'
		prevText: "'.JText::translate('VBJQCALPREV').'",'."\n".'
		nextText: "'.JText::translate('VBJQCALNEXT').'",'."\n".'
		currentText: "'.JText::translate('VBJQCALTODAY').'",'."\n".'
		monthNames: ["'.JText::translate('VBMONTHONE').'","'.JText::translate('VBMONTHTWO').'","'.JText::translate('VBMONTHTHREE').'","'.JText::translate('VBMONTHFOUR').'","'.JText::translate('VBMONTHFIVE').'","'.JText::translate('VBMONTHSIX').'","'.JText::translate('VBMONTHSEVEN').'","'.JText::translate('VBMONTHEIGHT').'","'.JText::translate('VBMONTHNINE').'","'.JText::translate('VBMONTHTEN').'","'.JText::translate('VBMONTHELEVEN').'","'.JText::translate('VBMONTHTWELVE').'"],'."\n".'
		monthNamesShort: ["'.mb_substr(JText::translate('VBMONTHONE'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHTWO'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHTHREE'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHFOUR'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHFIVE'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHSIX'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHSEVEN'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHEIGHT'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHNINE'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHTEN'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHELEVEN'), 0, 3, 'UTF-8').'","'.mb_substr(JText::translate('VBMONTHTWELVE'), 0, 3, 'UTF-8').'"],'."\n".'
		dayNames: ["'.JText::translate('VBJQCALSUN').'", "'.JText::translate('VBJQCALMON').'", "'.JText::translate('VBJQCALTUE').'", "'.JText::translate('VBJQCALWED').'", "'.JText::translate('VBJQCALTHU').'", "'.JText::translate('VBJQCALFRI').'", "'.JText::translate('VBJQCALSAT').'"],'."\n".'
		dayNamesShort: ["'.mb_substr(JText::translate('VBJQCALSUN'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALMON'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALTUE'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALWED'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALTHU'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALFRI'), 0, 3, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALSAT'), 0, 3, 'UTF-8').'"],'."\n".'
		dayNamesMin: ["'.mb_substr(JText::translate('VBJQCALSUN'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALMON'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALTUE'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALWED'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALTHU'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALFRI'), 0, 2, 'UTF-8').'", "'.mb_substr(JText::translate('VBJQCALSAT'), 0, 2, 'UTF-8').'"],'."\n".'
		weekHeader: "'.JText::translate('VBJQCALWKHEADER').'",'."\n".'
		dateFormat: "'.$juidf.'",'."\n".'
		firstDay: '.VikBooking::getFirstWeekDay().','."\n".'
		isRTL: ' . $is_rtl_str . ','."\n".'
		showMonthAfterYear: false,'."\n".'
		yearSuffix: ""'."\n".'
	};'."\n".'
	$.datepicker.setDefaults($.datepicker.regional["vikbookingmod"]);'."\n".'
});
'.($dates_layout_type == 'human' ? VikBookingWidgetHorizontalSearch::getMonWdayScript($params->get('mondayslen'), $randid) : '').'
function vbGetDateObject'.$randid.'(dstring) {
	var dparts = dstring.split("-");
	return new Date(dparts[0], (parseInt(dparts[1]) - 1), parseInt(dparts[2]), 0, 0, 0, 0);
}
function vbFullObject'.$randid.'(obj) {
	var jk;
	for(jk in obj) {
		return obj.hasOwnProperty(jk);
	}
}
var vbrestrctarange, vbrestrctdrange, vbrestrcta, vbrestrctd;';
$document->addScriptDeclaration($ldecl);

// global restrictions
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

	$resdecl = "
var vbrestrmonthswdays = " . json_encode($wdaysrestrictionsmonths) . ";
var vbrestrmonths = " . json_encode(array_keys($restrictions)) . ";
var vbrestrmonthscombojn = " . json_encode($monthscomborestr) . ";
var vbrestrminlos = " . json_encode((object) $minlosrestrictions) . ";
var vbrestrminlosrangejn = " . json_encode($minlosrestrictionsrange) . ";
var vbrestrmultiplyminlos = " . json_encode($notmultiplyminlosrestrictions) . ";
var vbrestrmaxlos = " . json_encode((object) $maxlosrestrictions) . ";
var vbrestrmaxlosrangejn = " . json_encode($maxlosrestrictionsrange) . ";
var vbrestrwdaysrangejn = " . json_encode($wdaysrestrictionsrange) . ";
var vbrestrcta = " . json_encode($ctarestrictionsmonths) . ";
var vbrestrctarange = " . json_encode($ctarestrictionsrange) . ";
var vbrestrctd = " . json_encode($ctdrestrictionsmonths) . ";
var vbrestrctdrange = " . json_encode($ctdrestrictionsrange) . ";
var vbcombowdays = {};
function vbRefreshCheckout".$randid."(darrive) {
	if (vbFullObject".$randid."(vbcombowdays)) {
		var vbtosort = new Array();
		for(var vbi in vbcombowdays) {
			if (vbcombowdays.hasOwnProperty(vbi)) {
				var vbusedate = darrive;
				vbtosort[vbi] = vbusedate.setDate(vbusedate.getDate() + (vbcombowdays[vbi] - 1 - vbusedate.getDay() + 7) % 7 + 1);
			}
		}
		vbtosort.sort(function(da, db) {
			return da > db ? 1 : -1;
		});
		for(var vbnext in vbtosort) {
			if (vbtosort.hasOwnProperty(vbnext)) {
				var vbfirstnextd = new Date(vbtosort[vbnext]);
				jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'minDate', vbfirstnextd);
				jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'setcheckoutdate', vbfirstnextd);
				break;
			}
		}
	}
}
function vbSetMinCheckoutDatemod".$randid."(selectedDate) {
	var minlos = ".VikBooking::getDefaultNightsCalendar().";
	var maxlosrange = 0;
	var nowcheckin = jQuery('#checkindatemod".$randid."').vboDatesRangePicker('getCheckinDate');
	var nowd = nowcheckin.getDay();
	var nowcheckindate = new Date(nowcheckin.getTime());
	vbcombowdays = {};
	if (vbFullObject".$randid."(vbrestrminlosrangejn)) {
		for (var rk in vbrestrminlosrangejn) {
			if (vbrestrminlosrangejn.hasOwnProperty(rk)) {
				var minldrangeinit = vbGetDateObject".$randid."(vbrestrminlosrangejn[rk][0]);
				if (nowcheckindate >= minldrangeinit) {
					var minldrangeend = vbGetDateObject".$randid."(vbrestrminlosrangejn[rk][1]);
					if (nowcheckindate <= minldrangeend) {
						minlos = parseInt(vbrestrminlosrangejn[rk][2]);
						if (vbFullObject".$randid."(vbrestrmaxlosrangejn)) {
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
	if (vbFullObject".$randid."(vbrestrmonthscombojn) && vbrestrmonthscombojn.hasOwnProperty(nowm)) {
		if (nowd in vbrestrmonthscombojn[nowm]) {
			vbcombowdays = vbrestrmonthscombojn[nowm][nowd];
		}
	}
	if (jQuery.inArray((nowm + 1), vbrestrmonths) != -1) {
		minlos = parseInt(vbrestrminlos[nowm]);
	}
	nowcheckindate.setDate(nowcheckindate.getDate() + minlos);
	jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'minDate', nowcheckindate);
	jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'minStayNights', minlos);
	if (maxlosrange > 0) {
		var diffmaxminlos = maxlosrange - minlos;
		var maxcheckoutdate = new Date(nowcheckindate.getTime());
		maxcheckoutdate.setDate(maxcheckoutdate.getDate() + diffmaxminlos);
		jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'maxDate', maxcheckoutdate);
	}
	if (nowm in vbrestrmaxlos) {
		var diffmaxminlos = parseInt(vbrestrmaxlos[nowm]) - minlos;
		var maxcheckoutdate = new Date(nowcheckindate.getTime());
		maxcheckoutdate.setDate(maxcheckoutdate.getDate() + diffmaxminlos);
		jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'maxDate', maxcheckoutdate);
	}
	if (!vbFullObject".$randid."(vbcombowdays)) {
		var is_checkout_disabled = false;
		if (typeof selectedDate !== 'undefined' && typeof jQuery('#checkindatemod".$randid."').vboDatesRangePicker('drpoption', 'beforeShowDay.checkout') === 'function') {
			// let the datepicker validate if the min date to set for check-out is disabled due to CTD rules
			is_checkout_disabled = !jQuery('#checkindatemod".$randid."').vboDatesRangePicker('drpoption', 'beforeShowDay.checkout')(nowcheckindate)[0];
		}
		if (!is_checkout_disabled) {
			jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'setCheckoutDate', nowcheckindate);
		} else {
			setTimeout(() => {
				// make sure the minimum date just set for the checkout has not populated a CTD date that we do not want
				var current_out_dt = jQuery('#checkindatemod".$randid."').vboDatesRangePicker('getCheckoutDate');
				if (current_out_dt && current_out_dt.getTime() === nowcheckindate.getTime()) {
					jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'setCheckoutDate', null);
				}
			}, 100);
		}
	} else {
		vbRefreshCheckout".$randid."(nowcheckin);
	}
	jQuery('#checkoutdatemod".$randid."').find('.ui-datepicker-current-day').click();
}";

	if ($wdaysrestrictions || $wdaysrestrictionsrange) {
		$resdecl .= "
var vbrestrwdays = " . json_encode((object) $wdaysrestrictions) . ";
var vbrestrwdaystwo = " . json_encode((object) $wdaystworestrictions) . ";
function vbIsDayDisabledmod".$randid."(date) {
	if (!vbIsDayOpenmod".$randid."(date) || !vboValidateCtamod".$randid."(date)) {
		return [false];
	}
	var m = date.getMonth(), wd = date.getDay();
	if (vbFullObject".$randid."(vbrestrwdaysrangejn)) {
		for (var rk in vbrestrwdaysrangejn) {
			if (vbrestrwdaysrangejn.hasOwnProperty(rk)) {
				var wdrangeinit = vbGetDateObject".$randid."(vbrestrwdaysrangejn[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vbGetDateObject".$randid."(vbrestrwdaysrangejn[rk][1]);
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
	if (vbFullObject".$randid."(vbrestrwdays)) {
		if (jQuery.inArray((m+1), vbrestrmonthswdays) == -1) {
			return [true];
		}
		if (wd == vbrestrwdays[m]) {
			return [true];
		}
		if (vbFullObject".$randid."(vbrestrwdaystwo)) {
			if (wd == vbrestrwdaystwo[m]) {
				return [true];
			}
		}
		return [false];
	}
	return [true];
}
function vbIsDayDisabledCheckoutmod".$randid."(date) {
	if (!vbIsDayOpenmod".$randid."(date) || !vboValidateCtdmod".$randid."(date)) {
		return [false];
	}
	var m = date.getMonth(), wd = date.getDay();
	if (vbFullObject".$randid."(vbcombowdays)) {
		if (jQuery.inArray(wd, vbcombowdays) != -1) {
			return [true];
		} else {
			return [false];
		}
	}
	if (vbFullObject".$randid."(vbrestrwdaysrangejn)) {
		for (var rk in vbrestrwdaysrangejn) {
			if (vbrestrwdaysrangejn.hasOwnProperty(rk)) {
				var wdrangeinit = vbGetDateObject".$randid."(vbrestrwdaysrangejn[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vbGetDateObject".$randid."(vbrestrwdaysrangejn[rk][1]);
					if (date <= wdrangeend) {
						if (wd != vbrestrwdaysrangejn[rk][2] && vbrestrwdaysrangejn[rk][3] == 1) {
							return [false];
						}
					}
				}
			}
		}
	}
	if (vbFullObject".$randid."(vbrestrwdays)) {
		if (jQuery.inArray((m+1), vbrestrmonthswdays) == -1 || jQuery.inArray((m+1), vbrestrmultiplyminlos) != -1) {
			return [true];
		}
		if (wd == vbrestrwdays[m]) {
			return [true];
		}
		return [false];
	}
	return [true];
}";
	}
	$document->addScriptDeclaration($resdecl);
}

// global closing dates
$closing_dates = VikBooking::parseJsClosingDates();
$sdecl = "
var vbclosingdates = " . json_encode($closing_dates) . ";
function vbCheckClosingDatesInmod".$randid."(date) {
	if (!vbIsDayOpenmod".$randid."(date) || !vboValidateCtamod".$randid."(date)) {
		return [false];
	}
	return [true];
}
function vbCheckClosingDatesOutmod".$randid."(date) {
	if (!vbIsDayOpenmod".$randid."(date) || !vboValidateCtdmod".$randid."(date)) {
		return [false];
	}
	return [true];
}
function vbIsDayOpenmod".$randid."(date) {
	if (vbFullObject".$randid."(vbclosingdates)) {
		for (var cd in vbclosingdates) {
			if (vbclosingdates.hasOwnProperty(cd)) {
				var cdfrom = vbGetDateObject".$randid."(vbclosingdates[cd][0]);
				var cdto = vbGetDateObject".$randid."(vbclosingdates[cd][1]);
				if (date >= cdfrom && date <= cdto) {
					return false;
				}
			}
		}
	}
	return true;
}
function vboValidateCtamod".$randid."(date) {
	var m = date.getMonth(), wd = date.getDay();
	if (vbFullObject".$randid."(vbrestrctarange)) {
		for (var rk in vbrestrctarange) {
			if (vbrestrctarange.hasOwnProperty(rk)) {
				var wdrangeinit = vbGetDateObject".$randid."(vbrestrctarange[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vbGetDateObject".$randid."(vbrestrctarange[rk][1]);
					if (date <= wdrangeend) {
						if (jQuery.inArray('-'+wd+'-', vbrestrctarange[rk][2]) >= 0) {
							return false;
						}
					}
				}
			}
		}
	}
	if (vbFullObject".$randid."(vbrestrcta)) {
		if (vbrestrcta.hasOwnProperty(m) && jQuery.inArray('-'+wd+'-', vbrestrcta[m]) >= 0) {
			return false;
		}
	}
	return true;
}
function vboValidateCtdmod".$randid."(date) {
	var m = date.getMonth(), wd = date.getDay();
	if (vbFullObject".$randid."(vbrestrctdrange)) {
		for (var rk in vbrestrctdrange) {
			if (vbrestrctdrange.hasOwnProperty(rk)) {
				var wdrangeinit = vbGetDateObject".$randid."(vbrestrctdrange[rk][0]);
				if (date >= wdrangeinit) {
					var wdrangeend = vbGetDateObject".$randid."(vbrestrctdrange[rk][1]);
					if (date <= wdrangeend) {
						if (jQuery.inArray('-'+wd+'-', vbrestrctdrange[rk][2]) >= 0) {
							return false;
						}
					}
				}
			}
		}
	}
	if (vbFullObject".$randid."(vbrestrctd)) {
		if (vbrestrctd.hasOwnProperty(m) && jQuery.inArray('-'+wd+'-', vbrestrctd[m]) >= 0) {
			return false;
		}
	}
	return true;
}
function vbSetGlobalMinCheckoutDatemod".$randid."() {
	var minlos = ".VikBooking::getDefaultNightsCalendar().";
	var nowcheckin = jQuery('#checkindatemod".$randid."').vboDatesRangePicker('getCheckinDate');
	var nowcheckindate = new Date(nowcheckin.getTime());
	nowcheckindate.setDate(nowcheckindate.getDate() + minlos);
	jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'minDate', nowcheckindate);
	jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'minStayNights', minlos);
	jQuery('#checkindatemod".$randid."').vboDatesRangePicker('checkout', 'setCheckoutDate', nowcheckindate);
}
function vbFormatCalDateMod{$randid}(idc) {
	var vb_period = document.getElementById((idc == 'from' ? 'inp-checkindatemod{$randid}' : 'inp-checkoutdatemod{$randid}')).value;
	if (!vb_period || !vb_period.length) {
		return;
	}
	var vb_period_parts = vb_period.split('/');
	if ('%d/%m/%Y' == '{$vbdateformat}') {
		var period_date = new Date(vb_period_parts[2], (parseInt(vb_period_parts[1]) - 1), parseInt(vb_period_parts[0], 10), 0, 0, 0, 0);
		var data = [parseInt(vb_period_parts[0], 10), parseInt(vb_period_parts[1]), vb_period_parts[2]];
	} else if ('%m/%d/%Y' == '{$vbdateformat}') {
		var period_date = new Date(vb_period_parts[2], (parseInt(vb_period_parts[0]) - 1), parseInt(vb_period_parts[1], 10), 0, 0, 0, 0);
		var data = [parseInt(vb_period_parts[1], 10), parseInt(vb_period_parts[0]), vb_period_parts[2]];
	} else {
		var period_date = new Date(vb_period_parts[0], (parseInt(vb_period_parts[1]) - 1), parseInt(vb_period_parts[2], 10), 0, 0, 0, 0);
		var data = [parseInt(vb_period_parts[2], 10), parseInt(vb_period_parts[1]), vb_period_parts[0]];
	}
	jQuery('.vbo-horizsearch-showcalendar-'+idc).find('.vbo-horizsearch-placeholder').remove();
	var elcont = jQuery('#vbo-horizsearch-period{$randid}-'+idc);
	elcont.find('.vbo-horizsearch-period-wday').text(vboMapWdays{$randid}[period_date.getDay()]);
	elcont.find('.vbo-horizsearch-period-mday').text(period_date.getDate());
	elcont.find('.vbo-horizsearch-period-month').text(vboMapMons{$randid}[period_date.getMonth()]);
	elcont.find('.vbo-horizsearch-period-year').text(period_date.getFullYear());
	// jQuery('.vbo-horizsearch-dpicker-cont').hide();
}

var vboCalVisible{$randid} = false;
var vboGuestsVisible{$randid} = false;

jQuery(function() {
	// reset regional
	jQuery.datepicker.setDefaults(jQuery.datepicker.regional['']);

	// start DRP (will be inline in case of human layout)
	jQuery('#checkindatemod".$randid."').vboDatesRangePicker({
		checkout: '#checkoutdatemod".$randid."',
		dateFormat: '{$juidf}',
		" . ($dates_layout_type != 'human' ? "showOn: 'focus'," : '') . "
		numberOfMonths: " . ($is_mobile ? '1' : '2') . ",
		minDate: '" . VikBooking::getMinDaysAdvance() . "d',
		maxDate: '" . VikBooking::getMaxDateFuture() . "',
		inlineContainer: '.vbo-horizsearch-dpicker-cont',
		altFields: {
			checkin: " . ($dates_layout_type == 'human' ? "'#inp-checkindatemod{$randid}'" : 'null') . ",
			checkout: " . ($dates_layout_type == 'human' ? "'#inp-checkoutdatemod{$randid}'" : 'null') . ",
		},
		beforeShowDay: {
			checkin: " . ($wdaysrestrictions || $wdaysrestrictionsrange ? "vbIsDayDisabledmod{$randid}" : "vbCheckClosingDatesInmod{$randid}") . ",
			checkout: " . ($wdaysrestrictions || $wdaysrestrictionsrange ? "vbIsDayDisabledCheckoutmod{$randid}" : "vbCheckClosingDatesOutmod{$randid}") . ",
		},
		onSelect: {
			checkin: (selectedDate) => {
				" . ($totrestrictions ? "vbSetMinCheckoutDatemod".$randid."(selectedDate);" : "vbSetGlobalMinCheckoutDatemod".$randid."();") . "
				vbCalcNightsMod".$randid."();
				" . ($dates_layout_type == 'human' ? "vbFormatCalDateMod".$randid."('from');" : '') . "
			},
			checkout: (selectedDate) => {
				vbCalcNightsMod".$randid."();
				" . ($dates_layout_type == 'human' ? "vbFormatCalDateMod".$randid."('to');" : '') . "
				// in case of human layout hide the datepicker container upon selecting the check-out
				" . ($dates_layout_type == 'human' ? "jQuery('.vbo-horizsearch-dpicker-cont').hide(); jQuery('.vbo-horizsearch-showcalendar').removeClass('vbo-horizsearch-dpicker-cont-active');" : '') . "
			},
		},
		labels: {
			checkin: Joomla.JText._('VBPICKUPROOM'),
			checkout: Joomla.JText._('VBRETURNROOM'),
			minStayNights: (nights) => {
				return (Joomla.JText._('VBO_MIN_STAY_NIGHTS') + '').replace('%d', nights);
			},
		},
		// bottomCommands not supported with inline datepicker (human layout)
		bottomCommands: {
			clear: Joomla.JText._('VBO_CLEAR_DATES'),
			close: Joomla.JText._('VBO_CLOSE'),
			onClear: () => {
				vbCalcNightsMod".$randid."();
			},
		},
	});

	// set proper regional
	jQuery('#checkindatemod".$randid."').datepicker('option', jQuery.datepicker.regional['vikbookingmod']);

	// register additional triggers (classic layout)
	jQuery('.vb-cal-img, .vbo-caltrigger').click(function() {
		let dp = jQuery(this).prev('input');
		if (!dp.length) {
			return;
		}
		if (dp.hasClass('hasDatepicker')) {
			dp.focus();
		} else if (dp.attr('id') == 'checkoutdatemod".$randid."') {
			jQuery('#checkindatemod".$randid."').focus();
		}
	});

	jQuery('#vbo-horizsearch-checkin{$randid}, #vbo-horizsearch-checkout{$randid}').click(function() {
		var childcal = jQuery(this).parent().find('.vbo-horizsearch-dpicker-cont');
		if (!childcal.hasClass('hasDatepicker') && childcal.hasClass('vbo-horizsearch-dpicker-to')) {
			// the DRP calendar in human layout requires the check-in container to be displayed
			childcal = jQuery('#vbo-horizsearch-checkin{$randid}').parent().find('.vbo-horizsearch-dpicker-cont');
		}
		if (!childcal.is(':visible')) {
			jQuery('.vbo-horizsearch-dpicker-cont').hide();
		}
		childcal.fadeToggle(400, function() {
			if (jQuery(this).is(':visible')) {
				vboCalVisible{$randid} = true;
				jQuery(this).parent().find('.vbo-horizsearch-showcalendar').addClass('vbo-horizsearch-dpicker-cont-active');
			} else {
				vboCalVisible{$randid} = false;
				jQuery(this).parent().find('.vbo-horizsearch-showcalendar').removeClass('vbo-horizsearch-dpicker-cont-active');
			}
		});
	});
	jQuery('label.vbo-horizsearch-lbl-dt-{$randid}').click(function() {
		jQuery(this).next('.vbo-horizsearch-showcalendar').trigger('click');
	});
	jQuery(document).keydown(function(e) {
		if (e.keyCode == 27) {
			if (vboCalVisible{$randid}) {
				jQuery('.vbo-horizsearch-dpicker-cont').hide();
				jQuery('.vbo-horizsearch-dpicker-cont-active').removeClass('vbo-horizsearch-dpicker-cont-active');
				vboCalVisible{$randid} = false;
			}
			if (vboGuestsVisible{$randid}) {
				jQuery('.vbmodhorsearch-hum-guests-modifier').hide();
				jQuery('.vbmodhorsearch-hum-guests-count-active').removeClass('vbmodhorsearch-hum-guests-count-active');
				vboGuestsVisible{$randid} = false;
			}
		}
	});
	jQuery(document).mouseup(function(e) {
		if (!vboCalVisible{$randid} && !vboGuestsVisible{$randid}) {
			return false;
		}
		if (vboCalVisible{$randid}) {
			var vbo_overlay_cont = jQuery('.vbo-horizsearch-dpicker-cont');
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				jQuery('.vbo-horizsearch-dpicker-cont').hide();
				jQuery('.vbo-horizsearch-dpicker-cont-active').removeClass('vbo-horizsearch-dpicker-cont-active');
				vboCalVisible{$randid} = false;
			}
		}
		if (vboGuestsVisible{$randid}) {
			var vbo_overlay_cont = jQuery('#vbmodhorsearch-hum-guests-count{$randid}').parent().find('.vbmodhorsearch-hum-guests-modifier');
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				vbo_overlay_cont.hide();
				jQuery('.vbmodhorsearch-hum-guests-count-active').removeClass('vbmodhorsearch-hum-guests-count-active');
				vboGuestsVisible{$randid} = false;
			}
		}
	});
	jQuery('#vbmodhorsearch-hum-guests-count{$randid}').click(function() {
		var mainblock = jQuery(this);
		mainblock.parent().find('.vbmodhorsearch-hum-guests-modifier').fadeToggle(400, function() {
			if (jQuery(this).is(':visible')) {
				vboGuestsVisible{$randid} = true;
				mainblock.addClass('vbmodhorsearch-hum-guests-count-active');
			} else {
				vboGuestsVisible{$randid} = false;
				mainblock.removeClass('vbmodhorsearch-hum-guests-count-active');
			}
		});
	});
});";
$document->addScriptDeclaration($sdecl);

if ($dates_layout_type == 'human') {
	// layout with readable dates
	?>
		<div class="vbmodhorsearch-hum-dates-wrap">
			<div class="vbmodhorsearch-humcalcont vbmodhorsearchcheckindiv">
				<label class="vbo-horizsearch-lbl-dt-<?php echo $randid; ?>"><?php echo JText::translate('VBMCHECKIN'); ?></label>
				<div class="vbo-horizsearch-showcalendar vbo-horizsearch-showcalendar-from" id="vbo-horizsearch-checkin<?php echo $randid; ?>">
					<?php VikBookingIcons::e('calendar'); ?>
					<span class="vbo-horizsearch-placeholder"><?php echo JText::translate('HORIZSEARCHPICKDATE'); ?></span>
					<div id="vbo-horizsearch-period<?php echo $randid; ?>-from" class="vbo-horizsearch-period-from">
						<span class="vbo-horizsearch-period-mday"></span>
						<div class="vbo-horizsearch-period-dt">
							<span class="vbo-horizsearch-period-month"></span>
							<span class="vbo-horizsearch-period-year"></span>
							<span class="vbo-horizsearch-period-wday"></span>
						</div>
					</div>
				</div>
				<div class="vbo-horizsearch-dpicker-cont vbo-horizsearch-dpicker-from" id="checkindatemod<?php echo $randid; ?>" style="display: none;"></div>
				<input type="hidden" name="checkindate" id="inp-checkindatemod<?php echo $randid; ?>"/>
				<input type="hidden" name="checkinh" value="<?php echo $hcheckin; ?>"/>
				<input type="hidden" name="checkinm" value="<?php echo $mcheckin; ?>"/>
			</div>
			<div class="vbmodhorsearch-humcalcont vbmodhorsearchcheckoutdiv">
				<label class="vbo-horizsearch-lbl-dt-<?php echo $randid; ?>"><?php echo JText::translate('VBMCHECKOUT'); ?></label>
				<div class="vbo-horizsearch-showcalendar vbo-horizsearch-showcalendar-to" id="vbo-horizsearch-checkout<?php echo $randid; ?>">
					<?php VikBookingIcons::e('calendar'); ?>
					<span class="vbo-horizsearch-placeholder"><?php echo JText::translate('HORIZSEARCHPICKDATE'); ?></span>
					<div id="vbo-horizsearch-period<?php echo $randid; ?>-to" class="vbo-horizsearch-period-to">
						<span class="vbo-horizsearch-period-mday"></span>
						<div class="vbo-horizsearch-period-dt">
							<span class="vbo-horizsearch-period-month"></span>
							<span class="vbo-horizsearch-period-year"></span>
							<span class="vbo-horizsearch-period-wday"></span>
						</div>
					</div>
				</div>
				<div class="vbo-horizsearch-dpicker-cont vbo-horizsearch-dpicker-to" id="checkoutdatemod<?php echo $randid; ?>" style="display: none;"></div>
				<input type="hidden" name="checkoutdate" id="inp-checkoutdatemod<?php echo $randid; ?>"/>
				<input type="hidden" name="checkouth" value="<?php echo $hcheckout; ?>"/>
				<input type="hidden" name="checkoutm" value="<?php echo $mcheckout; ?>"/>
			</div>
			<div class="vbmodhorsearchtotnights" id="vbjstotnightsmod<?php echo $randid; ?>"></div>
		</div>
	<?php
} else {
	// classic layout with input fields
	?>
		<div class="vbmodhorsearchcheckindiv">
			<label for="checkindatemod<?php echo $randid; ?>"><?php echo JText::translate('VBMCHECKIN'); ?></label>
			<div class="input-group">
				<input type="text" name="checkindate" id="checkindatemod<?php echo $randid; ?>" size="10" autocomplete="off" onfocus="this.blur();" readonly/>
				<?php VikBookingIcons::e('calendar', 'vbo-caltrigger'); ?>
				<input type="hidden" name="checkinh" value="<?php echo $hcheckin; ?>"/>
				<input type="hidden" name="checkinm" value="<?php echo $mcheckin; ?>"/>
			</div>
		</div>
		<div class="vbmodhorsearchcheckoutdiv">
			<label for="checkoutdatemod<?php echo $randid; ?>"><?php echo JText::translate('VBMCHECKOUT'); ?></label>
			<div class="input-group">
				<input type="text" name="checkoutdate" id="checkoutdatemod<?php echo $randid; ?>" size="10" autocomplete="off" onfocus="this.blur();" readonly/>
				<?php VikBookingIcons::e('calendar', 'vbo-caltrigger'); ?>
				<input type="hidden" name="checkouth" value="<?php echo $hcheckout; ?>"/>
				<input type="hidden" name="checkoutm" value="<?php echo $mcheckout; ?>"/>
			</div>
		</div>
	<?php
}

// number of nights calculated (they are displayed above for the human readable layout)
if ($dates_layout_type == 'standard') {
	?>
		<div class="vbmodhorsearchtotnights" id="vbjstotnightsmod<?php echo $randid; ?>"></div>
	<?php
}

// rooms, adults, children
$showchildren = VikBooking::showChildrenFront();
$guests_label = VBOFactory::getConfig()->get('guests_label', 'adults');
$use_guests_label = 'VBMFORMADULTS';
if (!$showchildren && !strcasecmp($guests_label, 'guests')) {
	$use_guests_label = 'VBMHORSGUESTS';
}
// max number of rooms
$maxsearchnumrooms = VikBooking::getSearchNumRooms();
// overwrite to always one room if inquiry
if ($needs_inquiry) {
	$maxsearchnumrooms = 1;
}

if (intval($maxsearchnumrooms) > 1) {
	$roomsel = "<span class=\"vbhsrnselsp\"><select name=\"roomsnum\" id=\"vbmodformroomsn".$randid."\" onchange=\"vbSetRoomsAdultsMod".$randid."(this.value);\">\n";
	for ($r = 1; $r <= intval($maxsearchnumrooms); $r++) {
		$roomsel .= "<option value=\"".$r."\">".$r."</option>\n";
	}
	$roomsel .= "</select></span>\n";
} else {
	$roomsel = "<input type=\"hidden\" name=\"roomsnum\" value=\"1\">\n";
}

// max number of adults per room
$globnumadults = VikBooking::getSearchNumAdults();
$adultsparts = explode('-', $globnumadults);
// overwrite maximum adults per room to max total adults if inquiry
if ($needs_inquiry) {
	$adultsparts[1] = VikBookingWidgetHorizontalSearch::getMaxestGuests('adults');
	$adultsparts[1] = $adultsparts[1] < $adultsparts[0] ? $adultsparts[0] : $adultsparts[1];
}
$adultsel = "<select name=\"adults[]\" id=\"vbo-mhs-adults-" . $randid . "\" onchange=\"vbCountTotGuests" . $randid . "();\">";
$def_adults = intval($params->get('defadults')) > 1 ? intval($params->get('defadults')) : 0;
for ($a = $adultsparts[0]; $a <= $adultsparts[1]; $a++) {
	$adultsel .= "<option value=\"".$a."\"".(($def_adults > 1 && $a == $def_adults) || (intval($adultsparts[0]) < 1 && $a == 1) ? " selected=\"selected\"" : "").">".$a."</option>";
}
$adultsel .= "</select>";

// max number of children per room
$globnumchildren = VikBooking::getSearchNumChildren();
$childrenparts = explode('-', $globnumchildren);
// overwrite maximum children per room to max total children if inquiry
if ($needs_inquiry) {
	$childrenparts[1] = VikBookingWidgetHorizontalSearch::getMaxestGuests('children');
	$childrenparts[1] = $childrenparts[1] < $childrenparts[0] ? $childrenparts[0] : $childrenparts[1];
}
$childrensel = "<select name=\"children[]\" id=\"vbo-mhs-children-" . $randid . "\" onchange=\"vbCountTotGuests" . $randid . "();\">";
for ($c = $childrenparts[0]; $c <= $childrenparts[1]; $c++) {
	$childrensel .= "<option value=\"".$c."\">".$c."</option>";
}
$childrensel .= "</select>";

if ($dates_layout_type == 'human') {
	// layout with readable dates and guests selection in modal
	$def_adults = $def_adults > 0 ? $def_adults : (int)$adultsparts[0];
	$def_children = (int)$childrenparts[0];
	?>
		<div class="vbmodhorsearch-hum-guests-wrap">
			
			<label onclick="jQuery('#vbmodhorsearch-hum-guests-count<?php echo $randid; ?>').trigger('click');"><?php echo JText::translate('VBMHORSGUESTS'); ?></label>

			<div class="vbmodhorsearch-hum-guests-count" id="vbmodhorsearch-hum-guests-count<?php echo $randid; ?>">
				<div class="vbmodhorsearch-hum-guests-elem vbmodhorsearch-hum-guests-rooms"<?php echo intval($maxsearchnumrooms) < 2 ? ' style="display: none;"' : ''; ?>>
					<label><?php echo JText::translate('VBMFORMROOMSN'); ?></label>
					<span id="vbmodhorsearch-hum-guests-rooms<?php echo $randid; ?>">1</span>
				</div>
				<div class="vbmodhorsearch-hum-guests-elem vbmodhorsearch-hum-guests-adults">
					<label><?php echo JText::translate($use_guests_label); ?></label>
					<span id="vbmodhorsearch-hum-guests-adults<?php echo $randid; ?>"><?php echo $def_adults; ?></span>
				</div>
			<?php
			if ($showchildren) {
				?>
				<div class="vbmodhorsearch-hum-guests-elem vbmodhorsearch-hum-guests-children">
					<label><?php echo JText::translate('VBFORMCHILDREN'); ?></label>
					<span id="vbmodhorsearch-hum-guests-children<?php echo $randid; ?>"><?php echo $def_children; ?></span>
				</div>
				<?php
			}
			?>
			</div>
			
			<div class="vbmodhorsearch-hum-guests-modifier" style="display: none;">
				<div class="vbmodhorsearch-hum-guests-modifier-inner">
				
				<?php
				if (intval($maxsearchnumrooms) > 1) {
					?>
					<div class="vbmodhorsearchroomsel">
						<label for="vbmodformroomsn<?php echo $randid; ?>"><?php echo JText::translate('VBMFORMROOMSN'); ?></label>
						<?php echo $roomsel; ?>
					</div>
					<?php
				} else {
					echo $roomsel;
				}
				?>
					<div class="vbmodhorsearchroomdentr">
						<div class="vbmodhorsearchroomdentrfirst">
						<?php
						if (intval($maxsearchnumrooms) > 1) {
							?>
							<span class="horsrnum"><?php echo JText::translate('VBMFORMNUMROOM'); ?> 1</span>
							<?php
						}
						?>
							<div class="horsanumdiv">
								<label class="horsanumlb" for="vbo-mhs-adults-<?php echo $randid; ?>"><?php echo JText::translate($use_guests_label); ?></label>
								<span class="horsanumsel"><?php echo $adultsel; ?></span>
							</div>
							<?php if ($showchildren): ?>
							<div class="horscnumdiv">
								<label class="horscnumlb" for="vbo-mhs-children-<?php echo $randid; ?>"><?php echo JText::translate('VBFORMCHILDREN'); ?></label>
								<span class="horscnumsel"><?php echo $childrensel; ?></span>
							</div>
							<?php endif; ?>
						</div>
						<div class="vbmoreroomscontmod" id="vbmoreroomscontmod<?php echo $randid; ?>"></div>
					</div>

				</div>
			</div>

		</div>
	<?php
} else {
	// classic layout with select fields
	?>
		<div class="vbmodhorsearchrac">
		
			<div class="vbmodhorsearchroomsel">
				<?php if (intval($maxsearchnumrooms) > 1): ?><label for="vbmodformroomsn<?php echo $randid; ?>"><?php echo JText::translate('VBMFORMROOMSN'); ?></label><?php endif; ?>
				<?php echo $roomsel; ?>
			</div>
			
			<div class="vbmodhorsearchroomdentr">
				
				<div class="vbmodhorsearchroomdentrfirst">
					<?php if (intval($maxsearchnumrooms) > 1): ?><span class="horsrnum"><?php echo JText::translate('VBMFORMNUMROOM'); ?> 1</span><?php endif; ?>
					<div class="horsanumdiv">
						<label class="horsanumlb" for="vbo-mhs-adults-<?php echo $randid; ?>"><?php echo JText::translate($use_guests_label); ?></label>
						<span class="horsanumsel"><?php echo $adultsel; ?></span>
					</div>
					<?php if ($showchildren): ?>
					<div class="horscnumdiv">
						<label class="horscnumlb" for="vbo-mhs-children-<?php echo $randid; ?>"><?php echo JText::translate('VBFORMCHILDREN'); ?></label>
						<span class="horscnumsel"><?php echo $childrensel; ?></span>
					</div>
					<?php endif; ?>
				</div>
				
				<div class="vbmoreroomscontmod" id="vbmoreroomscontmod<?php echo $randid; ?>"></div>
				
			</div>
			
		</div>
	<?php
}

if (intval($params->get('showcat')) === 1) {
	$q = "SELECT * FROM `#__vikbooking_categories` ORDER BY `#__vikbooking_categories`.`name` ASC;";
	$dbo->setQuery($q);
	$dbo->execute();
	if ($dbo->getNumRows() > 0) {
		$categories = $dbo->loadAssocList();
		$vbo_tn->translateContents($categories, '#__vikbooking_categories');
		?>
		<div class="vbmodhorsearchcategoriesblock">
			<label class="vbmodhscategories" for="vbmodhscategories<?php echo $randid; ?>"><?php echo JText::translate('VBMROOMCAT'); ?></label>
			<span class="vbhsrcselsp">
				<select name="categories" id="vbmodhscategories<?php echo $randid; ?>">
					<option value="all"><?php echo JText::translate('VBMALLCAT'); ?></option>
				<?php
				foreach ($categories as $cat) {
					?>
					<option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
					<?php
				}
				?>
				</select>
			</span>
		</div>
		<?php
	}
} elseif (intval($params->get('category_id')) > 0) {
	/**
	 * We need to pass also the hidden value "category_id" in order
	 * to allow the back-nav ("change dates" or "step-bar") to keep
	 * the requested category filter.
	 * 
	 * @since 	1.2.11
	 */
		?>
		<input type="hidden" name="categories" value="<?php echo (int)$params->get('category_id'); ?>" />
		<input type="hidden" name="category_id" value="<?php echo (int)$params->get('category_id'); ?>" />
		<?php
}

if ($needs_inquiry) {
	// build the inquiry/request quotation fields
	$inquiry_fields = VikBookingWidgetHorizontalSearch::grabInquiryFields();
	// start a field counter
	$field_counter = 0;
	?>
		<div class="vbo-mod-horsearch-inquiry-fields">
	<?php
	foreach ($inquiry_fields as $fieldset) {
		// need to ignore some default fields? evaluate their ->type property and skip it.
		if (!is_object($fieldset) || empty($fieldset->type) || empty($fieldset->fields) || !is_array($fieldset->fields)) {
			// invalid fieldset object
			continue;
		}
		foreach ($fieldset->fields as $inq_field) {
			// get the field input type
			$input_type  = VikBookingWidgetHorizontalSearch::parseFieldType($fieldset->type, $inq_field);
			$input_title = VikBookingWidgetHorizontalSearch::parseFieldLabel($fieldset->type, $inq_field);
			// increase field counter
			$field_counter++;
			// build field unique ID
			$field_id = $randid . '-' . $field_counter;
			// check if the fields of this fieldset should be required
			$required_attr = (isset($fieldset->required) && $fieldset->required) ? ' required' : '';
			?>
			<div class="vbo-mod-horsearch-inquiry-field" data-type="<?php echo $fieldset->type; ?>" data-input-type="<?php echo $input_type; ?>">
				<label for="<?php echo $field_id; ?>"><?php echo $input_title; ?></label>
			<?php
			switch ($input_type) {
				case 'text':
					?>
					<input type="text" id="<?php echo $field_id; ?>" name="inquiry[<?php echo $fieldset->type; ?>][]" value=""<?php echo $required_attr; ?> />
					<?php
					break;
				case 'email':
					?>
					<input type="email" id="<?php echo $field_id; ?>" name="inquiry[<?php echo $fieldset->type; ?>][]" value=""<?php echo $required_attr; ?> />
					<?php
					break;
				case 'phone':
					?>
					<input type="tel" id="<?php echo $field_id; ?>" name="inquiry[<?php echo $fieldset->type; ?>][]" value=""<?php echo $required_attr; ?> />
					<?php
					break;
				case 'number':
					?>
					<input type="number" id="<?php echo $field_id; ?>" name="inquiry[<?php echo $fieldset->type; ?>][]" value=""<?php echo $required_attr; ?> />
					<?php
					break;
				case 'textarea':
					?>
					<textarea id="<?php echo $field_id; ?>" name="inquiry[<?php echo $fieldset->type; ?>][]"></textarea>
					<?php
					break;
				case 'checkbox':
					?>
					<input type="checkbox" id="<?php echo $field_id; ?>" name="inquiry[<?php echo $fieldset->type; ?>][]" value="1"<?php echo $required_attr; ?> />
					<?php
					break;
				case 'country':
					$countries_sel = VikBooking::getCountriesSelect("inquiry[{$fieldset->type}][]", [], $inq_field->defvalue);
					// make sure to define the ID of the select tag
					if (strpos($countries_sel, 'id=') === false) {
						$countries_sel = str_replace('<select ', '<select id="' . $field_id . '" ', $countries_sel);
					}
					// check if the select should be required
					if (!empty($required_attr) && strpos($countries_sel, 'required') === false) {
						$countries_sel = str_replace('<select ', '<select required ', $countries_sel);
					}
					// print the select field
					echo $countries_sel;

					break;
				default:
					break;
			}
			?>
			</div>
			<?php
		}
	}

	// security measure: register a new CSRF token to avoid spammers
	JHtml::fetch('vbohtml.scripts.ajaxcsrf');

	?>
		</div>

		<div class="vbo-mod-horsearch-inquiry-submit">
			<button type="button" onclick="vboModHorSearchSendRequest(this, '<?php echo $randid; ?>');" class="btn vbsearchinputmodhors vbo-pref-color-btn"><?php echo JText::translate('INQ_SEND_REQUESTS') ?></button>
		</div>

		<div class="vbo-mod-horsearch-inquiry-checkav">
			<div class="vbo-mod-horsearch-inquiry-checkav-inner">
				<h4><?php echo JText::translate('INQ_OR_BOOK_ONLINE'); ?></h4>
				<div>
					<button type="button" onclick="vboModHorSearchBookOnline('<?php echo $randid; ?>');" class="btn vbsearchinputmodhors vbo-mod-horsearch-checkav-btn"><?php echo JText::translate('INQ_CHECK_AVAILABILITY') ?></button>
				</div>
			</div>
		</div>
	<?php
	// append a hidden input field for the current user language
	?>
	<input type="hidden" name="ulang" value="<?php echo JFactory::getLanguage()->getTag(); ?>" />
	<?php
} else {
	// regular search form widget
	?>
		<div class="vbmodhorsearchbookdiv">
			<button type="submit" id="vbo-mod-horsearch-sbt-<?php echo $randid; ?>" class="btn vbsearchinputmodhors vbo-pref-color-btn"><?php echo JText::translate('SEARCHD'); ?></button>
		</div>

		<script type="text/javascript">
			jQuery(function() {
				jQuery('button#vbo-mod-horsearch-sbt-<?php echo $randid; ?>').on('click', function(e) {
					e.preventDefault();
					jQuery(this).prop('disabled', true);
					jQuery(this).html('<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?> ' + jQuery(this).text());
					jQuery(this).closest('form').submit();
				});
			});
		</script>
	<?php
}
?>
	</form>
</div>

<div class="vbo-modhs-js-helpers" style="display: none;">
	<div class="vbo-modhs-add-element-html">
		<div class="vbmodhorsearchroomdentr">
			<span class="horsrnum"><?php echo JText::translate('VBMFORMNUMROOM'); ?> %d</span>
			<div class="horsanumdiv">
				<label style="display: none;" for="vbo-mhs-adults-<?php echo $randid; ?>-hid"><?php echo JText::translate($use_guests_label); ?></label>
				<span class="horsanumsel"><?php echo preg_replace("/\sid=\"([a-z0-9\-]+)\"/i", ' id="$1-hid"', $adultsel); ?></span>
			<?php
			if ($showchildren) {
				?>
				<div class="horscnumdiv">
					<label style="display: none;" for="vbo-mhs-children-<?php echo $randid; ?>-hid"><?php echo JText::translate('VBFORMCHILDREN'); ?></label>
					<span class="horscnumsel"><?php echo preg_replace("/\sid=\"([a-z0-9\-]+)\"/i", ' id="$1-hid"', $childrensel); ?></span>
				</div>
				<?php
			}
			?>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
function vboModHorSearchSendRequest(elem, formId) {
	var form_el = document.getElementById('vbo-mod-horsearch-form-' + formId);
	if (!form_el) {
		console.error('form not found', formId);
		return false;
	}

	// valid all form required fields
	if (typeof form_el.checkValidity === 'function' && !form_el.checkValidity()) {
		// modern browsers will support this HTML5 method to check the form required fields
		alert(Joomla.JText._('VBO_PLEASE_FILL_FIELDS'));
		return false;
	}

	// make sure the dates have been selected
	var checkin_date_el = jQuery(form_el).find('input[name="checkindate"]');
	var checkout_date_el = jQuery(form_el).find('input[name="checkoutdate"]');
	if (!checkin_date_el.length || !checkin_date_el.val().length || !checkout_date_el.length || !checkout_date_el.val().length) {
		alert(Joomla.JText._('VBO_PLEASE_SEL_DATES'));
		return false;
	}
	// make sure the dates are not identical to have a min stay of 1 night
	// as by default the datepicker could set equal dates to the hidden fields.
	if (checkin_date_el.val() == checkout_date_el.val()) {
		alert(Joomla.JText._('VBO_PLEASE_SEL_DATES'));
		return false;
	}

	// disable the send request button to avoid double submissions
	elem.disabled = true;
	
	// get form values
	var qstring = jQuery(form_el).serialize();

	// make sure the task is not set
	qstring = qstring.replace('task=search', '');
	qstring = qstring.replace('view=search', '');

	// make the ajax request to the controller
	jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=submit_inquiry&Itemid=' . $params->get('itemid', 0), false); ?>",
		data: qstring
	}).done(function(res) {
		if (!res.hasOwnProperty('status')) {
			alert('Invalid response');
			// re-enable the button
			elem.disabled = false;
			return false;
		}
		if (!res.status) {
			alert(res.error);
			// re-enable the button
			elem.disabled = false;
			return false;
		}
		// show success message by replacing all fields
		jQuery('.vbo-mod-horsearch-inquiry-fields').html('<p class="vbo-mod-horsearch-inquiry-mess-success">' + Joomla.JText._('VBO_THANKS_INQ_SUBMITTED') + '</p>');
		// remove submit button container
		jQuery('.vbo-mod-horsearch-inquiry-submit').remove();
	}).fail(function(err) {
		alert(err.responseText);
		// re-enable the button
		elem.disabled = false;
	});
}

function vboModHorSearchBookOnline(formId) {
	var form_el = document.getElementById('vbo-mod-horsearch-form-' + formId);
	if (!form_el) {
		console.error('form not found', formId);
		return false;
	}

	// we need to let a button of type button trigger the submit of the "check availability"
	// or in case of inquiry, the browser will check the validity of the required fields.

	// we only check if the dates have been selected
	var checkin_date_el = jQuery(form_el).find('input[name="checkindate"]');
	var checkout_date_el = jQuery(form_el).find('input[name="checkoutdate"]');
	if (!checkin_date_el.length || !checkin_date_el.val().length || !checkout_date_el.length || !checkout_date_el.val().length) {
		alert(Joomla.JText._('VBO_PLEASE_SEL_DATES'));
		return false;
	}

	// make sure the dates are not identical to have a min stay of 1 night
	// as by default the datepicker could set equal dates to the hidden fields.
	if (checkin_date_el.val() == checkout_date_el.val()) {
		alert(Joomla.JText._('VBO_PLEASE_SEL_DATES'));
		return false;
	}

	// simply submit the form to start the booking process
	form_el.submit();

	return true;
}

function vbAddElementMod<?php echo $randid; ?>() {
	var ni = document.getElementById('vbmoreroomscontmod<?php echo $randid; ?>');
	var numi = document.getElementById('vbroomhelpermod<?php echo $randid; ?>');
	var num = (document.getElementById('vbroomhelpermod<?php echo $randid; ?>').value -1) + 2;
	numi.value = num;
	var newdiv = document.createElement('div');
	var divIdName = 'vb'+num+'racont';
	newdiv.setAttribute('id', divIdName);
	// build HTML content to append to new element
	var new_element_html = document.getElementsByClassName('vbo-modhs-add-element-html')[0].innerHTML;
	// set room number
	let rp_rgx = new RegExp('%d', 'g');
	new_element_html = new_element_html.replace(rp_rgx, num);
	// get rid of label tag (Web Accessibility)
	let lbl_rgx = new RegExp(/<label\s.+>.+<\/label>/, 'g');
	new_element_html = new_element_html.replace(lbl_rgx, '');
	// get rid of any id attribute (Web Accessibility)
	let idattr_rgx = new RegExp(/id="[^\"]+"/, 'g');
	new_element_html = new_element_html.replace(idattr_rgx, '');
	// set HTML content and append element
	newdiv.innerHTML = new_element_html;
	ni.appendChild(newdiv);
}

function vbSetRoomsAdultsMod<?php echo $randid; ?>(totrooms) {
	var actrooms = parseInt(document.getElementById('vbroomhelpermod<?php echo $randid; ?>').value);
	var torooms = parseInt(totrooms);
	var difrooms;
	if (torooms > actrooms) {
		difrooms = torooms - actrooms;
		for (var ir = 1; ir <= difrooms; ir++) {
			vbAddElementMod<?php echo $randid; ?>();
		}
	}
	if (torooms < actrooms) {
		for (var ir = actrooms; ir > torooms; ir--) {
			if (ir > 1) {
				var rmra = document.getElementById('vb' + ir + 'racont');
				rmra.parentNode.removeChild(rmra);
			}
		}
		document.getElementById('vbroomhelpermod<?php echo $randid; ?>').value = torooms;
	}
	if (document.getElementById('vbmodhorsearch-hum-guests-rooms<?php echo $randid; ?>')) {
		document.getElementById('vbmodhorsearch-hum-guests-rooms<?php echo $randid; ?>').innerText = torooms;
		vbCountTotGuests<?php echo $randid; ?>();
	}
}

function vbCountTotGuests<?php echo $randid; ?>() {
	if (!document.getElementById('vbmodhorsearch-hum-guests-rooms<?php echo $randid; ?>')) {
		return;
	}
	var totadults = 0;
	var totchildren = 0;
	jQuery('#vbmodhorsearch-hum-guests-count<?php echo $randid; ?>').parent().find('select[name="adults[]"]').each(function() {
		var curel = jQuery(this).find('option:selected');
		if (curel.length) {
			totadults += parseInt(curel.val());
		}
	});
	jQuery('#vbmodhorsearch-hum-guests-adults<?php echo $randid; ?>').text(totadults);
	if (jQuery('#vbmodhorsearch-hum-guests-children<?php echo $randid; ?>').length) {
		jQuery('#vbmodhorsearch-hum-guests-count<?php echo $randid; ?>').parent().find('select[name="children[]"]').each(function() {
			var curel = jQuery(this).find('option:selected');
			if (curel.length) {
				totchildren += parseInt(curel.val());
			}
		});
		jQuery('#vbmodhorsearch-hum-guests-children<?php echo $randid; ?>').text(totchildren);
	}
}

function vbCalcNightsMod<?php echo $randid; ?>() {
	var vbcheckin = document.getElementById('<?php echo $dates_layout_type == 'human' ? 'inp-' : ''; ?>checkindatemod<?php echo $randid; ?>').value;
	var vbcheckout = document.getElementById('<?php echo $dates_layout_type == 'human' ? 'inp-' : ''; ?>checkoutdatemod<?php echo $randid; ?>').value;
	if (vbcheckin.length > 0 && vbcheckout.length > 0) {
		var vbcheckinp = vbcheckin.split("/");
		var vbcheckoutp = vbcheckout.split("/");
	<?php
	if ($vbdateformat == "%d/%m/%Y") {
		?>
		var vbinmonth = parseInt(vbcheckinp[1]);
		vbinmonth = vbinmonth - 1;
		var vbinday = parseInt(vbcheckinp[0], 10);
		var vbcheckind = new Date(vbcheckinp[2], vbinmonth, vbinday);
		var vboutmonth = parseInt(vbcheckoutp[1]);
		vboutmonth = vboutmonth - 1;
		var vboutday = parseInt(vbcheckoutp[0], 10);
		var vbcheckoutd = new Date(vbcheckoutp[2], vboutmonth, vboutday);
		<?php
	} elseif ($vbdateformat == "%m/%d/%Y") {
		?>
		var vbinmonth = parseInt(vbcheckinp[0]);
		vbinmonth = vbinmonth - 1;
		var vbinday = parseInt(vbcheckinp[1], 10);
		var vbcheckind = new Date(vbcheckinp[2], vbinmonth, vbinday);
		var vboutmonth = parseInt(vbcheckoutp[0]);
		vboutmonth = vboutmonth - 1;
		var vboutday = parseInt(vbcheckoutp[1], 10);
		var vbcheckoutd = new Date(vbcheckoutp[2], vboutmonth, vboutday);
		<?php
	} else {
		?>
		var vbinmonth = parseInt(vbcheckinp[1]);
		vbinmonth = vbinmonth - 1;
		var vbinday = parseInt(vbcheckinp[2], 10);
		var vbcheckind = new Date(vbcheckinp[0], vbinmonth, vbinday);
		var vboutmonth = parseInt(vbcheckoutp[1]);
		vboutmonth = vboutmonth - 1;
		var vboutday = parseInt(vbcheckoutp[2], 10);
		var vbcheckoutd = new Date(vbcheckoutp[0], vboutmonth, vboutday);
		<?php
	}
	?>
		var vbdivider = 1000 * 60 * 60 * 24;
		var vbints = vbcheckind.getTime();
		var vboutts = vbcheckoutd.getTime();
		if (vboutts > vbints) {
			//var vbnights = Math.ceil((vboutts - vbints) / (vbdivider));
			var utc1 = Date.UTC(vbcheckind.getFullYear(), vbcheckind.getMonth(), vbcheckind.getDate());
			var utc2 = Date.UTC(vbcheckoutd.getFullYear(), vbcheckoutd.getMonth(), vbcheckoutd.getDate());
			var vbnights = Math.ceil((utc2 - utc1) / vbdivider);
			if (vbnights > 0) {
				document.getElementById('vbjstotnightsmod<?php echo $randid; ?>').innerHTML = '<div class="vbo-horizsearch-numnights-inner"><span><?php echo addslashes(JText::translate('VBMJSTOTNIGHTS')); ?>:</span> <span>'+vbnights+'</span></div>';
			} else {
				document.getElementById('vbjstotnightsmod<?php echo $randid; ?>').innerHTML = '';
			}
		} else {
			document.getElementById('vbjstotnightsmod<?php echo $randid; ?>').innerHTML = '';
		}
	} else {
		document.getElementById('vbjstotnightsmod<?php echo $randid; ?>').innerHTML = '';
	}
}
</script>

<input type="hidden" id="vbroomhelpermod<?php echo $randid; ?>" value="1"/>
