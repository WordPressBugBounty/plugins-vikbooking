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
 * Booking form.
 */

// JSON-decode room parameters
$rparams = json_decode($this->room['params'], true);

$currencysymb = VikBooking::getCurrencySymb();
$vbdateformat = VikBooking::getDateFormat();
$datesep = VikBooking::getDateSeparator();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

// room layout name
$layout = VikBooking::getRoomParam('layout_style', $rparams, 'classic');

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

$pitemid = VikRequest::getInt('Itemid', '', 'request');

?>
<div id="vbo-bookingpart-form"></div>

<div class="vbo-seldates-cont">
	<div class="vbo-seldates-cont-inner">
		<h4><?php echo JText::translate('VBSELECTPDDATES'); ?></h4>

	<?php
	$paramshowpeople = intval(VikBooking::getRoomParam('maxminpeople', $rparams));
	if ($paramshowpeople > 0) {
		$maxadustr = ($this->room['fromadult'] != $this->room['toadult'] ? $this->room['fromadult'].' - '.$this->room['toadult'] : $this->room['toadult']);
		$maxchistr = ($this->room['fromchild'] != $this->room['tochild'] ? $this->room['fromchild'].' - '.$this->room['tochild'] : $this->room['tochild']);
		$maxtotstr = ($this->room['mintotpeople'] != $this->room['totpeople'] ? $this->room['mintotpeople'].' - '.$this->room['totpeople'] : $this->room['totpeople']);
		?>
		<div class="vbmaxminpeopleroom">
		<?php
		if ($paramshowpeople == 1) {
			?>
			<div class="vbmaxadultsdet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
			<?php
		} elseif ($paramshowpeople == 2) {
			?>
			<div class="vbmaxchildrendet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
			<?php
		} elseif ($paramshowpeople == 3) {
			?>
			<div class="vbmaxadultsdet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
			<div class="vbmaxtotdet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
			<?php
		} elseif ($paramshowpeople == 4) {
			?>
			<div class="vbmaxchildrendet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
			<div class="vbmaxtotdet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
			<?php
		} elseif ($paramshowpeople == 5) {
			?>
			<div class="vbmaxadultsdet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('male'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMADULTS'); ?></span><span class="vbmaxnumberdet"><?php echo $maxadustr; ?></span></div>
			<div class="vbmaxchildrendet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('child'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBFORMCHILDREN'); ?></span><span class="vbmaxnumberdet"><?php echo $maxchistr; ?></span></div>
			<div class="vbmaxtotdet"><span class="vbmaximgdet vbo-rdetails-capacity-icn"><?php VikBookingIcons::e('users'); ?></span><span class="vbmaxlabeldet"><?php echo JText::translate('VBMAXTOTPEOPLE'); ?></span><span class="vbmaxnumberdet"><?php echo $maxtotstr; ?></span></div>
			<?php
		}
		?>
		</div>
		<?php
	}

	if (VikBooking::allowBooking()) {
		// channel manager default values
		$ch_start_date = VikRequest::getString('start_date', '', 'request');
		$ch_end_date = VikRequest::getString('end_date', '', 'request');
		$ch_num_adults = VikRequest::getInt('num_adults', '', 'request');
		$ch_num_children = VikRequest::getInt('num_children', '', 'request');
		$arr_adults = VikRequest::getVar('adults', []);
		$ch_num_adults = empty($ch_num_adults) && !empty($arr_adults[0]) ? $arr_adults[0] : $ch_num_adults;
		$arr_children = VikRequest::getVar('children', []);
		$ch_num_children = empty($ch_num_children) && !empty($arr_children[0]) ? $arr_children[0] : $ch_num_children;
		//
		$promo_checkin = VikRequest::getString('checkin', '', 'request');
		$ispromo = $this->promo_season ? $this->promo_season['id'] : 0;

		$form_method = VBOPlatformDetection::isWordPress() ? 'post' : 'get';
		
		$selform = "<div class=\"vbdivsearch" . (!strcasecmp($layout, 'listing') ? ' vbo-listing-details-divsearch' : '') . "\"><form action=\"".JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''))."\" method=\"{$form_method}\" onsubmit=\"return vboValidateDates();\"><div class=\"vb-search-inner\">\n";
		$selform .= "<input type=\"hidden\" name=\"option\" value=\"com_vikbooking\"/>\n";
		$selform .= "<input type=\"hidden\" name=\"task\" value=\"search\"/>\n";
		if (!empty($pitemid)) {
			$selform .= "<input type=\"hidden\" name=\"Itemid\" value=\"".$pitemid."\"/>\n";
		}
		$selform .= "<input type=\"hidden\" name=\"roomdetail\" value=\"".$this->room['id']."\"/>\n";

		// add HTML code to the form
		$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkin\"><label for=\"checkindate\">" . JText::translate('VBPICKUPROOM') . "</label><div class=\"input-group\"><input type=\"text\" name=\"checkindate\" id=\"checkindate\" size=\"10\" autocomplete=\"off\" onfocus=\"this.blur();\" readonly/><i class=\"".VikBookingIcons::i('calendar', 'vbo-caltrigger')."\"></i></div><input type=\"hidden\" name=\"checkinh\" value=\"".$hcheckin."\"/><input type=\"hidden\" name=\"checkinm\" value=\"".$mcheckin."\"/></div>\n";
		$selform .= "<div class=\"vbo-search-inpblock vbo-search-inpblock-checkout\"><label for=\"checkoutdate\">" . JText::translate('VBRETURNROOM') . "</label><div class=\"input-group\"><input type=\"text\" name=\"checkoutdate\" id=\"checkoutdate\" size=\"10\" autocomplete=\"off\" onfocus=\"this.blur();\" readonly/><i class=\"".VikBookingIcons::i('calendar', 'vbo-caltrigger')."\"></i></div><input type=\"hidden\" name=\"checkouth\" value=\"".$hcheckout."\"/><input type=\"hidden\" name=\"checkoutm\" value=\"".$mcheckout."\"/></div>\n";

		// rooms, adults, children
		$showchildren = VikBooking::showChildrenFront();
		$guests_label = VBOFactory::getConfig()->get('guests_label', 'adults');
		$use_guests_label = 'VBFORMADULTS';
		if (!$showchildren && !strcasecmp($guests_label, 'guests')) {
			$use_guests_label = 'VBOINVTOTGUESTS';
		}
		// max number of rooms
		$multi_units = (int)VikBooking::getRoomParam('multi_units', $rparams);
		if ($multi_units === 1 && $this->room['units'] > 1) {
			$maxsearchnumrooms = (int)VikBooking::getSearchNumRooms();
			$maxsearchnumrooms = $this->room['units'] > $maxsearchnumrooms ? $maxsearchnumrooms : $this->room['units'];
			$roomsel = "<label for=\"vbo-detroomsnum\">".JText::translate('VBFORMROOMSN')."</label><select id=\"vbo-detroomsnum\" name=\"roomsnum\" onchange=\"vbSetRoomsAdults(this.value);\">\n";
			for ($r = 1; $r <= $maxsearchnumrooms; $r++) {
				$roomsel .= "<option value=\"".$r."\">".$r."</option>\n";
			}
			$roomsel .= "</select>\n";
		} else {
			$roomsel = "<input type=\"hidden\" name=\"roomsnum\" value=\"1\">\n";
		}

		// max number of adults per room
		$suggocc = (int)VikBooking::getRoomParam('suggocc', $rparams);
		$adultsel = "<select name=\"adults[]\">";
		for ($a = $this->room['fromadult']; $a <= $this->room['toadult']; $a++) {
			$adultsel .= "<option value=\"".$a."\"".((!empty($ch_num_adults) && $ch_num_adults == $a) || (empty($ch_num_adults) && $a == $suggocc) ? " selected=\"selected\"" : "").">".$a."</option>";
		}
		$adultsel .= "</select>";

		// max number of children per room
		$childrensel = "<select name=\"children[]\">";
		for ($c = $this->room['fromchild']; $c <= $this->room['tochild']; $c++) {
			$childrensel .= "<option value=\"".$c."\"".(!empty($ch_num_children) && $ch_num_children == $c ? " selected=\"selected\"" : "").">".$c."</option>";
		}
		$childrensel .= "</select>";

		$selform .= "<div class=\"vbo-search-num-racblock\">\n";
		$selform .= "	<div class=\"vbo-search-num-rooms\">".$roomsel."</div>\n";
		$selform .= "	<div class=\"vbo-search-num-aduchild-block\" id=\"vbo-search-num-aduchild-block\">\n";
		$selform .= "		<div class=\"vbo-search-num-aduchild-entry\">" . ($multi_units === 1 && $this->room['units'] > 1 ? "<span class=\"vbo-search-roomnum\">".JText::translate('VBFORMNUMROOM')." 1</span>" : '') . "\n";
		$selform .= "			<div class=\"vbo-search-num-adults-entry\"><label class=\"vbo-search-num-adults-entry-label\">".JText::translate($use_guests_label)."</label><span class=\"vbo-search-num-adults-entry-inp\">".$adultsel."</span></div>\n";
		if ($showchildren) {
			$selform .= "		<div class=\"vbo-search-num-children-entry\"><label class=\"vbo-search-num-children-entry-label\">".JText::translate('VBFORMCHILDREN')."</label><span class=\"vbo-search-num-children-entry-inp\">".$childrensel."</span></div>\n";
		}
		$selform .= "		</div>\n";
		$selform .= "	</div>\n";
		// the tag <div id=\"vbjstotnights\"></div> will be used by javascript to calculate the nights
		$selform .= "	<div id=\"vbjstotnights\"></div>\n";
		$selform .= "</div>\n";
		$selform .= "<div class=\"vbo-search-submit\"><input type=\"submit\" name=\"search\" value=\"" . JText::translate('VBBOOKTHISROOM') . "\" class=\"btn vbdetbooksubmit vbo-pref-color-btn\"/></div>\n";
		$selform .= "</div>\n";
		$selform .= "</form></div>";
		?>

		<div class="vbo-js-helpers" style="display: none;">
			<div class="vbo-add-element-html">
				<div class="vbo-search-num-aduchild-entry">
					<span class="vbo-search-roomnum"><?php echo JText::translate('VBFORMNUMROOM'); ?> %d</span>
					<div class="vbo-search-num-adults-entry">
						<label class="vbo-search-num-adults-entry-label"><?php echo JText::translate($use_guests_label); ?></label>
						<span class="vbo-search-num-adults-entry-inp"><?php echo $adultsel; ?></span>
					</div>
				<?php
				if ($showchildren) {
					?>
					<div class="vbo-search-num-children-entry">
						<label class="vbo-search-num-children-entry-label"><?php echo JText::translate('VBFORMCHILDREN'); ?></label>
						<span class="vbo-search-num-adults-entry-inp"><?php echo $childrensel; ?></span>
					</div>
					<?php
				}
				?>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		function vboValidateDates() {
			var vbcheckin = document.getElementById('checkindate').value;
			var vbcheckout = document.getElementById('checkoutdate').value;
			if (!vbcheckin || !vbcheckout) {
				alert(Joomla.JText._('VBSELPRDATE'));
				return false;
			}
			return true;
		}
		function vbCalcNights() {
			var vbcheckin = document.getElementById('checkindate').value;
			var vbcheckout = document.getElementById('checkoutdate').value;
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
						document.getElementById('vbjstotnights').innerHTML = '<?php echo addslashes(JText::translate('VBJSTOTNIGHTS')); ?>: '+vbnights;
					} else {
						document.getElementById('vbjstotnights').innerHTML = '';
					}
				} else {
					document.getElementById('vbjstotnights').innerHTML = '';
				}
			} else {
				document.getElementById('vbjstotnights').innerHTML = '';
			}
		}
		function vbAddElement() {
			var ni = document.getElementById('vbo-search-num-aduchild-block');
			var numi = document.getElementById('vbroomdethelper');
			var num = (document.getElementById('vbroomdethelper').value -1)+ 2;
			numi.value = num;
			var newdiv = document.createElement('div');
			var divIdName = 'vb'+num+'detracont';
			newdiv.setAttribute('id', divIdName);
			var new_element_html = document.getElementsByClassName('vbo-add-element-html')[0].innerHTML;
			var rp_rgx = new RegExp('%d', 'g');
			newdiv.innerHTML = new_element_html.replace(rp_rgx, num);
			ni.appendChild(newdiv);
		}
		function vbSetRoomsAdults(totrooms) {
			var actrooms = parseInt(document.getElementById('vbroomdethelper').value);
			var torooms = parseInt(totrooms);
			var difrooms;
			if (torooms > actrooms) {
				difrooms = torooms - actrooms;
				for (var ir=1; ir<=difrooms; ir++) {
					vbAddElement();
				}
			}
			if (torooms < actrooms) {
				for (var ir=actrooms; ir>torooms; ir--) {
					if (ir > 1) {
						var rmra = document.getElementById('vb' + ir + 'detracont');
						rmra.parentNode.removeChild(rmra);
					}
				}
				document.getElementById('vbroomdethelper').value = torooms;
			}
		}
		<?php
		$scroll_booking = false;
		// channel manager
		if (!empty($ch_start_date) && !empty($ch_end_date)) {
			$ch_ts_startdate = strtotime($ch_start_date);
			$ch_ts_enddate = strtotime($ch_end_date);
			if ($ch_ts_startdate > time() && $ch_ts_startdate < $ch_ts_enddate) {
				?>
		jQuery(function() {
			document.getElementById('checkindate').value = '<?php echo date($df, $ch_ts_startdate); ?>';
			document.getElementById('checkoutdate').value = '<?php echo date($df, $ch_ts_enddate); ?>';
			vbCalcNights();
		});
				<?php
			}
		} elseif (!empty($promo_checkin) && intval($promo_checkin) > 0) {
			$scroll_booking = $promo_checkin > mktime(0, 0, 0, date("n"), date("j"), date("Y")) ? true : $scroll_booking;
			$min_nights = 1;
			if ($this->promo_season && $scroll_booking) {
				if ($this->promo_season['promominlos'] > 1) {
					$min_nights = $this->promo_season['promominlos'];
					$promo_end_ts = $promo_checkin + ($min_nights * 86400);
					if ((bool)date('I', $promo_checkin) !== (bool)date('I', $promo_end_ts)) {
						if ((bool)$promo_checkin === true) {
							$promo_end_ts += 3600;
						} else {
							$promo_end_ts -= 3600;
						}
					}
				}
			}
			?>
		jQuery(function() {
			jQuery("#checkin-hidden").val("<?php echo $promo_checkin; ?>");
			jQuery("#checkindate").vboDatesRangePicker("setCheckinDate", new Date(<?php echo date('Y', $promo_checkin); ?>, <?php echo ((int)date('n', $promo_checkin) - 1); ?>, <?php echo date('j', $promo_checkin); ?>));
			<?php
			if ($min_nights > 1) {
				?>
			jQuery("#promo-hidden").val("<?php echo $this->promo_season['id']; ?>");
			jQuery("#checkindate").vboDatesRangePicker("option", "minDate", new Date(<?php echo date('Y', $promo_end_ts); ?>, <?php echo ((int)date('n', $promo_end_ts) - 1); ?>, <?php echo date('j', $promo_end_ts); ?>));
				<?php
			}
			?>
			jQuery(".ui-datepicker-current-day").click();
		});
			<?php
		}
		//
		?>
		jQuery(function() {
		<?php
		if ($ispromo > 0 || $scroll_booking === true || VikRequest::getInt('booknow', 0, 'request')) {
			?>
			setTimeout(function() {
				jQuery('html,body').animate({ scrollTop: (jQuery("#vbo-bookingpart-init").offset().top - 5) }, { duration: 'slow' });
			}, 200);
			<?php
		}
		?>
			jQuery(document.body).on('click', 'td.vbtdfree, td.vbtdwarning, td.vbtdbusyforcheckout', function() {
				if (!jQuery("#checkindate").length || jQuery(this).hasClass('vbtdpast')) {
					return;
				}
				var tdday = jQuery(this).attr('data-daydate');
				var tdymd = jQuery(this).attr('data-ymd');
				if (!tdday || !tdymd) {
					return;
				}
				// make sure the clicked date is not disabled
				if (typeof jQuery('#checkindate').vboDatesRangePicker('drpoption', 'beforeShowDay.checkin') === 'function') {
					// let the datepicker validate the clicked day
					let ymd_parts = tdymd.split('-');
					let ymd_object = new Date(ymd_parts[0], ymd_parts[1] - 1, ymd_parts[2], 0, 0, 0, 0);
					if (!jQuery('#checkindate').vboDatesRangePicker('drpoption', 'beforeShowDay.checkin')(ymd_object)[0]) {
						return;
					}
				}
				// set check-in date in dates range picker
				jQuery('#checkindate').vboDatesRangePicker('setCheckinDate', tdday);
				// animate to datepickers position
				jQuery('html,body').animate({
					scrollTop: (jQuery('#vbo-bookingpart-form').offset().top - 5)
				}, 600, function() {
					// animation-complete callback should simulate the onSelect event of the check-in datepicker
					if (typeof vbSetMinCheckoutDate !== "undefined") {
						vbSetMinCheckoutDate();
					} else if (typeof vbSetGlobalMinCheckoutDate !== "undefined") {
						vbSetGlobalMinCheckoutDate();
					}
					vbCalcNights();
					// give focus to check-out datepicker
					jQuery('#checkoutdate').focus();
				});
			});
		});
		</script>

		<input type="hidden" id="vbroomdethelper" value="1"/>

		<div class="vbo-intro-main"><?php echo VikBooking::getIntroMain(); ?></div>

		<div class="vbo-room-details-booking-wrapper">
		<?php
		echo $selform;
		if ($this->promo_season && !empty($this->promo_season['promotxt'])) {
			?>
			<div class="vbo-promotion-block">
				<div class="vbo-promotion-icon">
					<?php VikBookingIcons::e('percentage'); ?>
				</div>
				<div class="vbo-promotion-description">
					<?php echo $this->promo_season['promotxt']; ?>
				</div>
			</div>
			<?php
		}
		?>
		</div>

		<?php
		// check for guests allowed policy
		if (!$showchildren && $guests_allowed_policy = VikBooking::getGuestsAllowedPolicy($this->vbo_tn)) {
			?>
		<div class="vbo-guests-allowed-policy"><?php echo $guests_allowed_policy; ?></div>
			<?php
		}
		?>

		<div class="vbo-closing-main"><?php echo VikBooking::getClosingMain(); ?></div>
		<?php
	} else {
		echo VikBooking::getDisabledBookingMsg();
	}
	?>
	</div>
</div>
