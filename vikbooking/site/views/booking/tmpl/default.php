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

$ord = $this->ord;
$orderrooms = $this->orderrooms;
$tars = $this->tars;
$payment = $this->payment;
$vbo_tn = $this->vbo_tn;

// availability helper
$av_helper = VikBooking::getAvailabilityInstance();

// room stay dates in case of split stay
$room_stay_dates = [];
if ($ord['split_stay']) {
	if ($ord['status'] == 'confirmed') {
		$room_stay_dates = $av_helper->loadSplitStayBusyRecords($ord['id']);
	} else {
		$room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $ord['id'], []);
	}
}

$currencysymb = VikBooking::getCurrencySymb();
$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep 	= VikBooking::getDateSeparator();
$ptmpl 		= VikRequest::getString('tmpl', '', 'request');
$pitemid 	= VikRequest::getInt('Itemid', 0, 'request');
$bestitemid = VikBooking::findProperItemIdType(['booking']);
$now_info 	= getdate();

$wdays_map 	= [
	JText::translate('VBWEEKDAYZERO'),
	JText::translate('VBWEEKDAYONE'),
	JText::translate('VBWEEKDAYTWO'),
	JText::translate('VBWEEKDAYTHREE'),
	JText::translate('VBWEEKDAYFOUR'),
	JText::translate('VBWEEKDAYFIVE'),
	JText::translate('VBWEEKDAYSIX')
];

$isdue = 0;
$isdue_orig = 0;
$imp = 0;
$pricenames = array();
$optbought = array();
$extraservices = array();
$roomsnames = array();
$is_package = !empty($ord['pkg']) ? true : false;
foreach ($orderrooms as $kor => $or) {
	$num = $kor + 1;
	$roomsnames[] = $or['name'];

	// determine proper values for this room
	$room_stay_checkin  = $ord['checkin'];
	$room_stay_checkout = $ord['checkout'];
	$room_stay_nights 	= $ord['days'];
	if ($ord['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
		$room_stay_checkin  = !empty($room_stay_dates[$kor]['checkin_ts']) ? $room_stay_dates[$kor]['checkin_ts'] : $room_stay_dates[$kor]['checkin'];
		$room_stay_checkout = !empty($room_stay_dates[$kor]['checkout_ts']) ? $room_stay_dates[$kor]['checkout_ts'] : $room_stay_dates[$kor]['checkout'];
		$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
		// inject nights calculated for this room
		$room_stay_dates[$kor]['nights'] = $room_stay_nights;
	}

	if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
		// package cost or cust_cost may not be inclusive of taxes if prices tax included is off
		$calctar = VikBooking::sayPackagePlusIva($or['cust_cost'], $or['cust_idiva']);
		$isdue += $calctar;
		$isdue_orig += $calctar;
		$imp += VikBooking::sayPackageMinusIva($or['cust_cost'], $or['cust_idiva']);
		$pricenames[$num] = (!empty($or['pkg_name']) ? $or['pkg_name'] : (!empty($or['otarplan']) ? $or['otarplan'] : JText::translate('VBOROOMCUSTRATEPLAN')));
	} elseif (array_key_exists($num, $tars) && is_array($tars[$num])) {
		$display_rate = $ord['status'] != 'confirmed' && !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
		$calctar = VikBooking::sayCostPlusIva($display_rate, $tars[$num]['idprice']);
		$tars[$num]['calctar'] = $calctar;
		$isdue += $calctar;
		$isdue_orig += $or['room_cost'];
		$imp += VikBooking::sayCostMinusIva($display_rate, $tars[$num]['idprice']);
		$pricenames[$num] = VikBooking::getPriceName($tars[$num]['idprice'], $vbo_tn);
	}
	if (!empty($or['optionals'])) {
		$stepo = explode(";", $or['optionals']);
		foreach ($stepo as $roptkey => $one) {
			if (empty($one)) {
				continue;
			}
			$stept = explode(":", $one);
			$actopt = VikBooking::getSingleOption($stept[0], $vbo_tn);
			if (!$actopt) {
				continue;
			}
			$chvar = '';
			if (!empty($actopt['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
				$optagenames = VikBooking::getOptionIntervalsAges($actopt['ageintervals']);
				$optagepcent = VikBooking::getOptionIntervalsPercentage($actopt['ageintervals']);
				$optageovrct = VikBooking::getOptionIntervalChildOverrides($actopt, $or['adults'], $or['children']);
				$child_num 	 = VikBooking::getRoomOptionChildNumber($or['optionals'], $actopt['id'], $roptkey, $or['children']);
				$optagecosts = VikBooking::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt['ageintervals']);
				$agestept = explode('-', $stept[1]);
				$stept[1] = $agestept[0];
				$chvar = $agestept[1];
				if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
					//percentage value of the adults tariff
					if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
						$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
					} else {
						$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
						$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
					}
				} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
					//VBO 1.10 - percentage value of room base cost
					if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
						$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
					} else {
						$display_rate = isset($tars[$num]['room_base_cost']) ? $tars[$num]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost']);
						$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
					}
				}
				$actopt['chageintv'] = $chvar;
				$actopt['name'] .= ' ('.$optagenames[($chvar - 1)].')';
				$realcost = (intval($actopt['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $room_stay_nights * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
			} else {
				// VBO 1.11 - options percentage cost of the room total fee
				if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
					$deftar_basecosts = $or['cust_cost'];
				} else {
					$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num]['cost'];
				}
				$actopt['cost'] = (int)$actopt['pcentroom'] ? ($deftar_basecosts * $actopt['cost'] / 100) : $actopt['cost'];
				//
				$realcost = (intval($actopt['perday']) == 1 ? ($actopt['cost'] * $room_stay_nights * $stept[1]) : ($actopt['cost'] * $stept[1]));
			}
			if (!empty($actopt['maxprice']) && $actopt['maxprice'] > 0 && $realcost > $actopt['maxprice']) {
				$realcost = $actopt['maxprice'];
				if (intval($actopt['hmany']) == 1 && intval($stept[1]) > 1) {
					$realcost = $actopt['maxprice'] * $stept[1];
				}
			}
			if ($actopt['perperson'] == 1) {
				$realcost = $realcost * $or['adults'];
			}
			$tmpopr = VikBooking::sayOptionalsPlusIva($realcost, $actopt['idiva']);
			$isdue += $tmpopr;
			$isdue_orig += $tmpopr;
			$imp += VikBooking::sayOptionalsMinusIva($realcost, $actopt['idiva']);
			if (!isset($optbought[$num])) {
				$optbought[$num] = '';
			}
			$optbought[$num] .= "<div class=\"vbo-booking-item-row\"><span class=\"vbo-booking-pricename\">".($stept[1] > 1 ? $stept[1] . " " : "") . $actopt['name'] . "</span> <span class=\"vbo-booking-pricedet\"><span class=\"vbo_currency\">" . $currencysymb . "</span> <span class=\"vbo_price\">" . VikBooking::numberFormat($tmpopr) . "</span></span></div>";
		}
	}

	// custom extra costs
	if (!empty($or['extracosts'])) {
		$extraservices[$num] = '';
		$cur_extra_costs = json_decode($or['extracosts'], true);
		foreach ($cur_extra_costs as $eck => $ecv) {
			$ecplustax = !empty($ecv['idtax']) ? VikBooking::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
			$isdue += $ecplustax;
			$isdue_orig += $ecplustax;
			$imp += !empty($ecv['idtax']) ? VikBooking::sayOptionalsMinusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'];
			$extraservices[$num] .= "<div class=\"vbo-booking-item-row\"><span class=\"vbo-booking-pricename\">".$ecv['name']."</span> <span class=\"vbo-booking-pricedet\"><span class=\"vbo_currency\">" . $currencysymb . "</span> <span class=\"vbo_price\">" . VikBooking::numberFormat($ecplustax) . "</span></span></div>";
		}
	}
}

$tax = $isdue - $imp;

$usedcoupon = false;
$origisdue = $isdue;
if (strlen((string)$ord['coupon']) > 0) {
	$usedcoupon = true;
	$expcoupon = explode(";", $ord['coupon']);
	$isdue = $isdue - $expcoupon[1];
	$isdue_orig = $isdue_orig - $expcoupon[1];
}

if ($ord['refund'] > 0) {
	$isdue -= $ord['refund'];
	$isdue_orig -= $ord['refund'];
}

//Check whether the booking total amount has changed due to rates modifications for these dates, made after this booking
$rooms_total_changed = ($ord['status'] == 'confirmed' && number_format($isdue, 2) != number_format($ord['total'], 2) && number_format($origisdue, 2) != number_format($ord['total'], 2));
$only_roomsrates_changed = ($rooms_total_changed === true && number_format($isdue_orig, 2) == number_format($ord['total'], 2));

//booking modification, cancellation and request
$resmodcanc = VikBooking::getReservationModCanc();
$resmodcanc = $this->days_to_arrival < 1 ? 0 : $resmodcanc;
$resmodcancmin = VikBooking::getReservationModCancMin();
$mod_allowed = ($resmodcanc > 1 && $resmodcanc != 3 && $this->days_to_arrival >= $resmodcancmin);
$canc_allowed = ($resmodcanc > 1 && $resmodcanc != 2 && $this->is_refundable > 0 && $this->daysadv_refund <= $this->days_to_arrival && $this->days_to_arrival >= $resmodcancmin);

$ts_info = getdate($ord['ts']);
$checkin_info = getdate($ord['checkin']);
$checkout_info = getdate($ord['checkout']);

// the current booking URI
$current_booking_uri = JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid='.(!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']).'&ts='.$ord['ts'].(!empty($bestitemid) ? '&Itemid='.$bestitemid : (!empty($pitemid) ? '&Itemid='.$pitemid : '')));
?>
<a class="vbo-current-booking-uri" href="<?php echo $current_booking_uri; ?>" style="display: none;"></a>
<?php

//print button
if ($ord['status'] == 'confirmed' && $ptmpl != 'component') {
	?>
<div class="vbo-booking-print">
	<a class="vbo-booking-print-link" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid='.(!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']).'&ts='.$ord['ts'].'&tmpl=component'.(!empty($bestitemid) ? '&Itemid='.$bestitemid : (!empty($pitemid) ? '&Itemid='.$pitemid : ''))); ?>" target="_blank" title="<?php echo JText::translate('VBOPRINT'); ?>"><?php VikBookingIcons::e('print'); ?></a>
</div>
	<?php
}

if ($ord['status'] == 'confirmed') {
	$head_css = 'vbo-booking-details-head-confirmed';
	?>
<h3 class="vbo-booking-details-intro"><?php echo JText::sprintf('VBOYOURBOOKCONFAT', VikBooking::getFrontTitle()); ?></h3>
	<?php
} elseif ($ord['status'] == 'cancelled') {
	$head_css = 'vbo-booking-details-head-cancelled';
} else {
	$head_css = 'vbo-booking-details-head-pending';
}
?>

<div class="vbo-booking-details-topcontainer">

	<div class="vbo-booking-details-head <?php echo $head_css; ?>">
	<?php
	if ($ord['status'] == 'confirmed') {
		?>
		<h4><?php echo JText::translate('VBOYOURBOOKISCONF'); ?></h4>
		<?php
	} elseif ($ord['status'] != 'cancelled') {
		?>
		<h4><?php echo JText::translate('VBOYOURBOOKISPEND'); ?></h4>
		<?php
	} else {
		?>
		<h4><?php echo JText::translate('VBOYOURBOOKISCANC'); ?></h4>
		<?php
	}
	?>
	</div>

	<div class="vbo-paycontainer-pos vbo-paycontainer-pos-top" style="display: none;"></div>

	<div class="vbo-booking-details-midcontainer">

		<div class="vbo-booking-details-bookinfos">
			<span class="vbvordudatatitle"><?php echo JText::translate('VBORDERDETAILS'); ?></span>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBORDEREDON'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$ts_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $ord['ts']); ?></span>
			</div>
		<?php
		if (!empty($ord['idorderota']) && !empty($ord['channel'])) {
			?>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBORDERNUMBER'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $ord['idorderota']; ?></span>
			</div>
			<?php
		}
		if ($ord['status'] == 'confirmed') {
			?>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBCONFIRMNUMB'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $ord['confirmnumber']; ?></span>
			</div>
			<?php
		}
		?>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBDAL'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$checkin_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $ord['checkin']); ?></span>
			</div>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBAL'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$checkout_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $ord['checkout']); ?></span>
			</div>
			<div class="vbo-booking-details-bookinfo">
				<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBDAYS'); ?></span>
				<span class="vbo-booking-details-bookinfo-val"><?php echo $ord['days']; ?></span>
			</div>
		<?php
		if ($ord['split_stay']) {
			?>
			<div class="vbo-booking-details-bookinfo vbo-booking-details-bookinfo-splitstay">
				<span class="vbo-booking-details-bookinfo-lbl"><?php VikBookingIcons::e('random'); ?> <?php echo JText::translate('VBO_SPLIT_STAY_RES'); ?></span>
			</div>
			<?php
		}
		?>
		</div>

		<div class="vbo-booking-details-udets">
			<span class="vbvordudatatitle"><?php echo JText::translate('VBPERSDETS'); ?></span>
			<div class="vbo-bookingdet-custdata">
			<?php
			$custdata_parts = explode("\n", $ord['custdata']);
			if (count($custdata_parts) > 2 && strpos($custdata_parts[0], ':') !== false && strpos($custdata_parts[1], ':') !== false) {
				//attempt to format labels and values
				foreach ($custdata_parts as $custdet) {
					if (strlen((string)$custdet) < 1) {
						continue;
					}
					$custdet_parts = explode(':', $custdet);
					$custd_lbl = '';
					$custd_val = '';
					if (count($custdet_parts) < 2) {
						$custd_val = $custdet;
					} else {
						$custd_lbl = $custdet_parts[0];
						unset($custdet_parts[0]);
						$custd_val = trim(implode(':', $custdet_parts));
					}
					?>
				<div class="vbo-bookingdet-userdetail">
					<?php
					if (strlen((string)$custd_lbl)) {
						?>
					<span class="vbo-bookingdet-userdetail-lbl"><?php echo VikBooking::tnCustomerRawDataLabel($custd_lbl); ?></span>
						<?php
					}
					if (strlen((string)$custd_val)) {
						?>
					<span class="vbo-bookingdet-userdetail-val"><?php echo $custd_val; ?></span>
						<?php
					}
					?>
				</div>
					<?php
				}
			} else {
				echo nl2br($ord['custdata']);
			}
			?>
			</div>
		</div>
	<?php
	// booking modification, cancellation, pre check-in or modification request (confirmed status only)
	$precheckin = VikBooking::precheckinEnabled();
	if ($precheckin) {
		// make sure the limit of days in advance is reflected
		$precheckin_mind = VikBooking::precheckinMinOffset();
		$precheckin_lim_ts = strtotime("+{$precheckin_mind} days 00:00:00");
		$precheckin = ($precheckin_lim_ts <= $ord['checkin'] || ($precheckin_mind === 1 && time() <= $ord['checkin']));
	}

	/**
	 * If this is an OTA booking, try to print the OTA logo.
	 * 
	 * @since 	1.13
	 */
	$isotabooking = (!empty($ord['idorderota']) && !empty($ord['channel']));
	$otalogo 	  = false;
	if ($isotabooking) {
		$otalogo = VikBooking::getVcmChannelsLogo($ord['channel']);
	}
	//

	/**
	 * Booking guest review
	 * 
	 * @since 	1.13
	 */
	$canbereviewed = VikBooking::canBookingBeReviewed($ord);
	//

	if ($ord['status'] == 'confirmed' && ($precheckin || $canbereviewed || $mod_allowed || $canc_allowed || ($resmodcanc === 1 && $this->days_to_arrival >= $resmodcancmin) || $otalogo || count($this->upselling))) {
	?>
		<div class="vbo-booking-details-actions">
			<div class="vbo-booking-details-actions-inner">
			<?php
			if ($otalogo) {
				?>
				<div class="vbo-booking-mod-container vbo-booking-otabooking-wrap">
					<div class="vbo-booking-mod-inner">
						<div class="vbo-booking-mod-cmd">
							<img class="vbo-otabooking-logo" src="<?php echo $otalogo; ?>" alt="<?php echo htmlspecialchars($ord['idorderota']); ?>" title="<?php echo htmlspecialchars($ord['idorderota']); ?>" />
						</div>
					</div>
				</div>
				<?php
			}
			if ($precheckin) {
				$start_itemid = VikBooking::findProperItemIdType(['booking', 'vikbooking']);
				?>
				<div class="vbo-booking-mod-container">
					<div class="vbo-booking-mod-inner">
						<div class="vbo-booking-mod-cmd vbo-booking-precheckin-cmd">
							<a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=precheckin&sid='.(!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']).'&ts='.$ord['ts'].(!empty($pitemid) ? '&Itemid='.$pitemid : (!empty($start_itemid) ? '&Itemid='.$start_itemid : ''))); ?>"><?php VikBookingIcons::e('users'); ?> <?php echo JText::translate('VBOPRECHECKIN'); ?></a>
						</div>
					</div>
				</div>
				<?php
			}
			if ($canbereviewed) {
				$start_itemid = VikBooking::findProperItemIdType(['booking', 'vikbooking']);
				?>
				<div class="vbo-booking-mod-container">
					<div class="vbo-booking-mod-inner">
						<div class="vbo-booking-mod-cmd vbo-booking-review-cmd">
							<a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=revstay&sid='.(!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']).'&ts='.$ord['ts'].(!empty($pitemid) ? '&Itemid='.$pitemid : (!empty($start_itemid) ? '&Itemid='.$start_itemid : ''))); ?>"><?php VikBookingIcons::e('star'); ?> <?php echo JText::translate('VBOLEAVEAREVIEW'); ?></a>
						</div>
					</div>
				</div>
				<?php
			}
			if ($mod_allowed && !$isotabooking && !$ord['split_stay']) {
				$start_itemid = VikBooking::findProperItemIdType(['vikbooking', 'roomslist']);
				?>
				<div class="vbo-booking-mod-container">
					<div class="vbo-booking-mod-inner">
						<div class="vbo-booking-mod-cmd">
							<a onclick="return confirm('<?php echo addslashes(JText::translate('VBOMODYOURBOOKINGCONF')); ?>');" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=vikbooking&modify_sid='.$ord['sid'].'&modify_id='.$ord['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : (!empty($start_itemid) ? '&Itemid='.$start_itemid : ''))); ?>"><?php VikBookingIcons::e('edit'); ?> <?php echo JText::translate('VBOMODYOURBOOKING'); ?></a>
						</div>
					</div>
				</div>
				<?php
				/**
				 * Suggest room upgrade options, if any.
				 * 
				 * @since 	1.16.0 (J) - 1.6.0 (WP)
				 */
				echo $this->loadTemplate('upgrade');
			}
			if ($canc_allowed && !$isotabooking) {
				?>
				<div class="vbo-booking-canc-container">
					<div class="vbo-booking-canc-inner">
						<div class="vbo-booking-canc-cmd">
							<span onclick="document.getElementById('vbo-booking-cancform-container').style.display='block';location.hash='bcancf';"><?php VikBookingIcons::e('times-circle'); ?> <?php echo JText::translate('VBOCANCYOURBOOKING'); ?></span>
						</div>
					</div>
				</div>
				<?php
			}
			if ($resmodcanc === 1 && $this->days_to_arrival >= $resmodcancmin && !$isotabooking) {
				?>
				<div class="vbo-booking-mod-container">
					<div class="vbo-booking-mod-inner">
						<div class="vbo-booking-mod-cmd">
							<a onclick="vbOpenCancOrdForm();" href="javascript: void(0);"><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBREQUESTCANCMOD'); ?></a>
						</div>
					</div>
				</div>
				<?php
			}
			if (count($this->upselling)) {
				?>
				<div class="vbo-hidein-print vbo-booking-mod-container vbo-booking-upselling-wrap">
					<div class="vbo-booking-mod-inner">
						<div class="vbo-booking-mod-cmd">
							<a onclick="vbGotoUpsell();" href="javascript: void(0);"><?php VikBookingIcons::e('cart-plus'); ?> <?php echo JText::translate('VBOADDEXTRASTOBOOK'); ?></a>
						</div>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		</div>
	<?php
	}
	?>

	</div>

	<div class="vbo-paycontainer-pos vbo-paycontainer-pos-middle" style="display: none;"></div>

</div>

<div class="vbo-booking-rooms-wrapper">
<?php
foreach ($orderrooms as $kor => $or) {
	$num = $kor + 1;
	?>
	<div class="vbvordroominfo<?php echo count($orderrooms) > 1 ? ' vbvordroominfo-multi' : ''; ?>">
		<?php
		if (strlen((string)$or['img']) > 0) {
			?>
		<div class="vbo-booking-roomphoto">
			<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $or['img']; ?>"/>
		</div>
			<?php
		}
		?>
		<div class="vbordroomdet">
			<span class="vbvordroominfotitle"><?php echo $or['name']; ?></span>
			<div class="vbordroomdetpeople">
				<span class="vbo-booking-numadults"><?php echo $or['adults']; ?> <?php echo ($or['adults'] == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?></span>
			<?php
			if ($or['children'] > 0) {
				?>
				<span class="vbo-booking-numchildren"><?php echo $or['children']." ".($or['children'] == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN')); ?></span>
				<?php
			}
			?>
			</div>
		<?php
		if ($ord['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $or['idroom']) {
			$room_stay_checkin  = !empty($room_stay_dates[$kor]['checkin_ts']) ? $room_stay_dates[$kor]['checkin_ts'] : $room_stay_dates[$kor]['checkin'];
			$room_stay_checkout = !empty($room_stay_dates[$kor]['checkout_ts']) ? $room_stay_dates[$kor]['checkout_ts'] : $room_stay_dates[$kor]['checkout'];
			$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
			// inject nights calculated for this room
			$room_stay_dates[$kor]['nights'] = $room_stay_nights;
			?>
			<div class="vbo-booking-splitstay-info">
				<div class="vbo-booking-splitstay-info-room">
					<span class="vbo-booking-splitstay-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo $room_stay_nights . ' ' . ($room_stay_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?></span>
					<span class="vbo-booking-splitstay-checkin"><?php VikBookingIcons::e('sign-in'); ?> <?php echo date(str_replace("/", $datesep, $df), $room_stay_checkin); ?></span>
					<span class="vbo-booking-splitstay-checkout"><?php VikBookingIcons::e('sign-out'); ?> <?php echo date(str_replace("/", $datesep, $df), $room_stay_checkout); ?></span>
				</div>
			</div>
			<?php
		}
		if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
			?>
			<div class="vbo-booking-roomrate">
				<span class="vbvordcoststitlemain">
					<span class="vbo-booking-pricename"><?php echo $pricenames[$num]; ?></span>
					<span class="room_cost">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span> 
						<span class="vbo_price"><?php echo VikBooking::numberFormat($or['cust_cost']); ?></span>
					</span>
				</span>
			</div>
			<?php
		} elseif (array_key_exists($num, $tars) && is_array($tars[$num])) {
			?>
			<div class="vbo-booking-roomrate">
				<span class="vbvordcoststitlemain">
					<span class="vbo-booking-pricename"><?php echo $pricenames[$num]; ?></span>
					<span class="room_cost">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span> 
						<span class="vbo_price"<?php echo $ord['status'] == 'confirmed' && $or['room_cost'] > 0 ? ' data-vborigprice="'.VikBooking::numberFormat($or['room_cost']).'"' : ''; ?>><?php echo VikBooking::numberFormat($tars[$num]['calctar']); ?></span>
					</span>
				</span>
			</div>
			<?php
		}
		?>
		</div>
		
	<?php
	if ((array_key_exists($num, $optbought) && strlen((string)$optbought[$num]) > 0) || (array_key_exists($num, $extraservices) && strlen((string)$extraservices[$num]) > 0)) {
	?>
		<div class="vbo-booking-room-extras">
		<?php
		if (array_key_exists($num, $optbought) && strlen((string)$optbought[$num]) > 0) {
			?>
			<div class="vbo-booking-room-extras-options">
				<span class="vbvordcoststitle"><?php echo JText::translate('VBOPTS'); ?></span>
				<div class="vbo-booking-room-extras-options-list"><?php echo $optbought[$num]; ?></div>
			</div>
			<?php
		}
		if (array_key_exists($num, $extraservices) && strlen((string)$extraservices[$num]) > 0) {
			?>
			<div class="vbo-booking-room-extras-services">
				<span class="vbvordcoststitle"><?php echo JText::translate('VBOEXTRASERVICES'); ?></span>
				<div class="vbo-booking-room-extras-services-list"><?php echo $extraservices[$num]; ?></div>
			</div>
			<?php
		}
		?>
		</div>
	<?php
	}
	?>
		
	</div>
	<?php
}
?>
</div>

<?php
if ($rooms_total_changed === true) {
	?>
<script type="text/javascript">
jQuery(function() {
	jQuery(".vbo_price").not(".vbo_keepcost").each(function(k, v) {
		var origp = jQuery(this).attr('data-vborigprice');
		if (origp !== undefined) {
			jQuery(this).addClass("vbo_keepcost").text(origp).parent().find(".vbo_currency").addClass("vbo_keepcost");
		} else {
			<?php
			//if only the room rates changed but not the options, keep printing the prices
			echo !$only_roomsrates_changed ? 'jQuery(this).text("").parent().find(".vbo_currency").text("");' : 'jQuery(this).addClass("vbo_keepcost").parent().find(".vbo_currency").addClass("vbo_keepcost");';
			?>
		}
	});
	jQuery(".vbo_currency").not(".vbo_keepcost").each(function(){
		var cur_txt = jQuery(this).parent("span").html();
		if (cur_txt) {
			jQuery(this).parent("span").html(cur_txt.replace(":", ""));
		} else {
			var cur_txt = jQuery(this).parent("div").html();
			if (cur_txt) {
				jQuery(this).parent("div").html(cur_txt.replace(":", ""));
			}
		}
	});
});
</script>
	<?php
}

if ($ord['status'] == 'confirmed' && is_array($payment) && intval($payment['shownotealw']) == 1 && !empty($payment['note'])) {
	?>
<div class="vbvordpaynote">
	<?php
	/**
	 * @wponly 	we need to let WordPress parse the paragraphs in the message.
	 */
	if (VBOPlatformDetection::isWordPress()) {
		echo wpautop($payment['note']);
	} else {
		echo $payment['note'];
	}
	?>
</div>
	<?php
}
?>

<div class="vbo-booking-costs-list">
<?php
$extra_css = $ord['status'] == 'confirmed' ? ' vbo_keepcost' : '';
if ($usedcoupon === true) {
	?>
	<div class="vbo-booking-cost-detail vbo-booking-cost-detail-discount">
		<div class="vbo-booking-cost-lbl">
			<span><?php echo JText::translate('VBCOUPON') . ' ' . $expcoupon[2]; ?></span>
		</div>
		<div class="vbo-booking-cost-val">
			<span class="vbo-booking-cost-val-number">
				<span>-</span>
				<span class="vbo_currency<?php echo $extra_css; ?>"><?php echo $currencysymb; ?></span> 
				<span class="vbo_price<?php echo $extra_css; ?>"><?php echo VikBooking::numberFormat($expcoupon[1]); ?></span>
			</span>
		</div>
	</div>
	<?php
}
if ($ord['refund'] > 0) {
	?>
	<div class="vbo-booking-cost-detail vbo-booking-cost-detail-refund">
		<div class="vbo-booking-cost-lbl">
			<span><?php echo JText::translate('VBO_AMOUNT_REFUNDED'); ?></span>
		</div>
		<div class="vbo-booking-cost-val">
			<span class="vbo-booking-cost-val-number">
				<span class="vbo_currency<?php echo $extra_css; ?>"><?php echo $currencysymb; ?></span> 
				<span class="vbo_price<?php echo $extra_css; ?>"><?php echo VikBooking::numberFormat($ord['refund']); ?></span>
			</span>
		</div>
	</div>
	<?php
}
?>
	<div class="vbo-booking-cost-detail vbo-booking-cost-detail-total">
		<div class="vbo-booking-cost-lbl">
			<span><?php echo JText::translate('VBTOTAL'); ?></span>
		</div>
		<div class="vbo-booking-cost-val">
			<span class="vbo-booking-cost-val-number">
				<span class="vbo_currency<?php echo $extra_css; ?>"><?php echo $currencysymb; ?></span> 
				<span class="vbo_price<?php echo $extra_css; ?>"><?php echo VikBooking::numberFormat(($ord['status'] == 'confirmed' ? $ord['total'] : $isdue)); ?></span>
			</span>
		</div>
	</div>
<?php
/**
 * We allow the payment for confirmed bookings when a payment method is assigned, the configuration setting is enabled,
 * the payment counter is greater than 0 (some tasks will force it to 1 when empty) and the amount paid is greater than
 * zero but less than the total amount, or when the 'payable' property is greater than zero.
 * 
 * @since 	1.13 (J) - 1.3.0 (WP)
 * 
 * We no longer need the payment counter to be greater than zero to allow a payment, as the payable amount can be defined by the admin.
 * 
 * @since 	1.14 (J) - 1.4.0 (WP)
 * 
 * Some OTA bookings using an OTA-Collect business model may look as not entirely paid when they actually are, due to commissions.
 * 
 * @since 	1.16.5 (J) - 1.6.5 (WP)
 * 
 * Channels supporting payout notifications will not allow to pay the outstanding balance unless a payable amount is set.
 * 
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
$payable = (($ord['totpaid'] > 0.00 && $ord['totpaid'] < $ord['total'] && $ord['paymcount'] > 0) || $ord['payable'] > 0);
$ota_will_pay = false;
if ($payable && !empty($ord['idorderota']) && !empty($ord['channel']) && $ord['cmms'] && ($ord['total'] - $ord['totpaid'] - $ord['cmms']) < 1) {
	// the difference of the amount paid is equal to the OTA commissions amount
	$payable = false;
}
if ($payable && $isotabooking && stripos($ord['channel'], 'airbnbapi') === 0 && !((float) $ord['payable'])) {
	// access OTA payout events
	$prev_ota_payments = VikBooking::getBookingHistoryInstance($ord['id'])
		->getEventsWithData('PO', null, false);
	if (!$prev_ota_payments) {
		// the OTA will pay the remaining balance
		$payable = false;
		$ota_will_pay = true;
	}
}

if ($ord['status'] == 'confirmed' && is_array($payment) && VikBooking::multiplePayments() && $ord['total'] > 0 && $payable) {
	// write again the payment form because the order was not fully paid

	if (VBOPlatformDetection::isWordPress()) {
		/**
		 * @wponly
		 *
		 * @since 	1.0.5
		 */
		$return_url = JUri::root() . "index.php?option=com_vikbooking&view=booking&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts'];
		$error_url = JUri::root() . "index.php?option=com_vikbooking&view=booking&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts'];
		$notify_url = JUri::root() . "index.php?option=com_vikbooking&task=notifypayment&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts']."&tmpl=component";
		/**
		 * @wponly  the URLs must be routed differently for WP
		 */
		$model 	= JModel::getInstance('vikbooking', 'shortcodes', 'admin');
		$itemid = $model->best(array('booking'), (!empty($ord['lang']) ? $ord['lang'] : null));
		if ($itemid) {
			$return_url = str_replace(JUri::root(), '', $return_url);
			$error_url = str_replace(JUri::root(), '', $error_url);
			$notify_url = str_replace(JUri::root(), '', $notify_url);
			$return_url = JRoute::rewrite($return_url . "&Itemid={$itemid}", false);
			$error_url = JRoute::rewrite($error_url . "&Itemid={$itemid}", false);
			$notify_url = JRoute::rewrite($notify_url . "&Itemid={$itemid}", false);
		}
	} else {
		$return_url = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts'], false, (!empty($bestitemid) ? $bestitemid : null));
		$error_url = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts'], false, (!empty($bestitemid) ? $bestitemid : null));
		$notify_url = VikBooking::externalroute("index.php?option=com_vikbooking&task=notifypayment&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts']."&tmpl=component", false, null);
	}

	$transaction_name = VikBooking::getPaymentName();
	$remainingamount = $ord['payable'] > 0 ? $ord['payable'] : ($ord['total'] - $ord['totpaid']);
	$leave_deposit = 0;
	$percentdeposit = "";

	$array_order = [];
	$array_order['details'] = $ord;
	if (empty($array_order['details']['sid']) && !empty($array_order['details']['idorderota']) && !empty($array_order['details']['channel'])) {
		$array_order['details']['sid'] = $array_order['details']['idorderota'];
		$ord['sid'] = $ord['idorderota'];
	}
	$array_order['customer_email'] = $ord['custmail'];
	$array_order['account_name'] = VikBooking::getPaypalAcc();
	$array_order['transaction_currency'] = VikBooking::getCurrencyCodePp();
	$array_order['rooms_name'] = implode(", ", $roomsnames);
	$array_order['transaction_name'] = !empty($transaction_name) ? $transaction_name : (JText::translate('VBORDERNUMBER') . ' ' . $ord['id']);
	$array_order['order_total'] = $remainingamount;
	$array_order['currency_symb'] = $currencysymb;
	$array_order['net_price'] = $remainingamount;
	$array_order['tax'] = 0;
	$array_order['return_url'] = $return_url;
	$array_order['error_url'] = $error_url;
	$array_order['notify_url'] = $notify_url;
	$array_order['total_to_pay'] = $remainingamount;
	$array_order['total_net_price'] = $remainingamount;
	$array_order['total_tax'] = 0;
	$array_order['leave_deposit'] = $leave_deposit;
	$array_order['percentdeposit'] = $percentdeposit;
	$array_order['payment_info'] = $payment;
	$array_order = array_merge($ord, $array_order);
	?>
	<div class="vbo-booking-cost-detail vbo-booking-cost-detail-amountpaid">
		<div class="vbo-booking-cost-lbl">
			<span><?php echo JText::translate('VBAMOUNTPAID'); ?></span>
		</div>
		<div class="vbo-booking-cost-val">
			<span class="vbo-booking-cost-val-number">
				<span class="vbo_currency vbo_keepcost"><?php echo $currencysymb; ?></span> 
				<span class="vbo_price vbo_keepcost"><?php echo VikBooking::numberFormat($ord['totpaid']); ?></span>
			</span>
		</div>
	</div>
	<div class="vbo-booking-cost-detail vbo-booking-cost-detail-remainingbalance">
		<div class="vbo-booking-cost-lbl">
			<span><?php echo JText::translate('VBTOTALREMAINING'); ?></span>
		</div>
		<div class="vbo-booking-cost-val">
			<span class="vbo-booking-cost-val-number">
				<span class="vbo_currency vbo_keepcost"><?php echo $currencysymb; ?></span> 
				<span class="vbo_price vbo_keepcost"><?php echo VikBooking::numberFormat($remainingamount); ?></span>
			</span>
		</div>
	</div>

	<div class="vbvordpaybutton">
	<?php
	if (VBOPlatformDetection::isWordPress()) {
		/**
		 * @wponly 	The payment gateway is now loaded 
		 * 			using the apposite dispatcher.
		 *
		 * @since 1.0.5
		 */
		JLoader::import('adapter.payment.dispatcher');

		$obj = JPaymentDispatcher::getInstance('vikbooking', $payment['file'], $array_order, $payment['params']);
		// remember to echo the payment
		echo $obj->showPayment();
	} else {
		/**
		 * @joomlaonly 	The Payment Factory library will invoke the gateway.
		 * 
		 * @since 	1.14.3
		 */
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'factory.php';
		$obj = VBOPaymentFactory::getPaymentInstance($payment['file'], $array_order, $payment['params']);

		$obj->showPayment();
	}
	?>
	</div>
	<?php
} elseif ($ord['status'] == 'confirmed') {
	if ($ptmpl != 'component' && $ord['total'] > 0 && $ord['totpaid'] > 0.00 && $ord['totpaid'] < $ord['total']) {
		$remainingamount = $ord['total'] - $ord['totpaid'];
		?>
	<div class="vbo-booking-cost-detail vbo-booking-cost-detail-amountpaid">
		<div class="vbo-booking-cost-lbl">
			<span><?php echo JText::translate('VBAMOUNTPAID'); ?></span>
		</div>
		<div class="vbo-booking-cost-val">
			<span class="vbo-booking-cost-val-number">
				<span class="vbo_currency vbo_keepcost"><?php echo $currencysymb; ?></span> 
				<span class="vbo_price vbo_keepcost"><?php echo VikBooking::numberFormat($ord['totpaid']); ?></span>
			</span>
		</div>
	</div>
		<?php
		if (!$ota_will_pay) {
			?>
	<div class="vbo-booking-cost-detail vbo-booking-cost-detail-remainingbalance">
		<div class="vbo-booking-cost-lbl">
			<span><?php echo JText::translate('VBTOTALREMAINING'); ?></span>
		</div>
		<div class="vbo-booking-cost-val">
			<span class="vbo-booking-cost-val-number">
				<span class="vbo_currency vbo_keepcost"><?php echo $currencysymb; ?></span> 
				<span class="vbo_price vbo_keepcost"><?php echo VikBooking::numberFormat($remainingamount); ?></span>
			</span>
		</div>
	</div>
			<?php
		}
	}
	if ($ptmpl == 'component') {
		?>
	<script type="text/javascript">
		window.print();
	</script>
		<?php
	}
}

?>
</div>
<?php

// booking modification/cancellation request, cancellation form or upsell animation
if (($ord['status'] == 'confirmed' && $resmodcanc > 0 && $this->days_to_arrival >= $resmodcancmin) || count($this->upselling)) {
	?>
<script type="text/javascript">
	function vbOpenCancOrdForm() {
		location.hash = 'bmodreqf';
		document.getElementById('vbordcancformbox').style.display = 'block';
	}
	function vbValidateCancForm() {
		if (!document.getElementById('vbcancemail').value.match(/\S/)) {
			document.getElementById('vbformcancemail').style.color='#ff0000';
			return false;
		} else {
			document.getElementById('vbformcancemail').style.color='';
		}
		if (!document.getElementById('vbcancreason').value.match(/\S/)) {
			document.getElementById('vbformcancreason').style.color='#ff0000';
			return false;
		} else {
			document.getElementById('vbformcancreason').style.color='';
		}
		return true;
	}
	function vbGotoUpsell() {
		jQuery('html,body').animate({scrollTop: jQuery('.vbo-booking-upsell-container').offset().top - 20}, {duration: 400});
	}
</script>
	<?php
}
if ($ord['status'] == 'confirmed' && $resmodcanc === 1 && $this->days_to_arrival >= $resmodcancmin) {
	?>
<a name="bmodreqf"></a>
<div class="vbordcancformbox" id="vbordcancformbox">
	<div class="vbo-booking-cancform-inner">
		<h4><?php echo JText::translate('VBREQUESTCANCMOD'); ?></h4>
		<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" name="vbcanc" method="post" onsubmit="javascript: return vbValidateCancForm();">
			<div class="vbordcancform-inner">
				<div class="vbordcancform-entry">
					<div class="vbordcancform-entry-label">
						<label for="vbcancemail" id="vbformcancemail"><?php echo JText::translate('VBREQUESTCANCMODEMAIL'); ?></label>
					</div>
					<div class="vbordcancform-entry-inp">
						<input type="text" class="vbinput" name="email" id="vbcancemail" value="<?php echo $ord['custmail']; ?>"/>
					</div>
				</div>
				<div class="vbordcancform-entry">
					<div class="vbordcancform-entry-label">
						<label for="vbcancreason" id="vbformcancreason"><?php echo JText::translate('VBREQUESTCANCMODREASON'); ?></label>
					</div>
					<div class="vbordcancform-entry-inp">
						<textarea name="reason" id="vbcancreason" rows="7" cols="30" class="vbtextarea"></textarea>
					</div>
				</div>
				<div class="vbordcancform-entry-submit">
					<input type="submit" name="sendrequest" value="<?php echo JText::translate('VBREQUESTCANCMODSUBMIT'); ?>" class="btn vbo-pref-color-btn"/>
				</div>
			</div>
		<?php
		if (!empty($pitemid)) {
			?>
			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
			<?php
		}
		?>
			<input type="hidden" name="sid" value="<?php echo $ord['sid']; ?>"/>
			<input type="hidden" name="idorder" value="<?php echo $ord['id']; ?>"/>
			<input type="hidden" name="option" value="com_vikbooking"/>
			<input type="hidden" name="task" value="cancelrequest"/>
			<?php echo JHtml::fetch('form.token'); ?>
		</form>
	</div>
</div>
	<?php
}
//booking cancellation
if ($ord['status'] == 'confirmed' && $canc_allowed) {
	?>
<a name="bcancf"></a>
<div class="vbo-booking-cancform-container" id="vbo-booking-cancform-container" style="display: none;">
	<div class="vbo-booking-cancform-inner">
		<h4><?php echo JText::translate('VBOCANCYOURBOOKING'); ?></h4>
		<div class="vbo-booking-cancform-details">
			<div class="vbo-booking-canc-details-policy">
				<?php echo $this->canc_policy; ?>
			</div>
			<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" name="vbcanc" method="post" onsubmit="javascript: return vbValidateCancForm();">
				<div class="vbordcancform-inner vbo-booking-canc">
					<div class="vbordcancform-entry">
						<div class="vbordcancform-entry-label">
							<label for="vbcancemail" id="vbformcancemail"><?php echo JText::translate('VBREQUESTCANCMODEMAIL'); ?></label>
						</div>
						<div class="vbordcancform-entry-inp">
							<input type="text" class="vbinput" name="email" id="vbcancemail" value="<?php echo $ord['custmail']; ?>"/>
						</div>
					</div>
					<div class="vbordcancform-entry">
						<div class="vbordcancform-entry-label">
							<label for="vbcancreason" id="vbformcancreason"><?php echo JText::translate('VBOCANCBOOKINGREASON'); ?></label>
						</div>
						<div class="vbordcancform-entry-inp">
							<textarea name="reason" id="vbcancreason" rows="7" cols="30" class="vbtextarea"></textarea>
						</div>
					</div>
					<div class="vbo-booking-canc-submit">
						<input type="submit" name="sendrequest" value="<?php echo JText::translate('VBOCANCYOURBOOKING'); ?>" class="vbo-btn-cancelbooking"/>
					</div>
				</div>
			<?php
			if (!empty($pitemid)) {
				?>
				<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
				<?php
			}
			?>
				<input type="hidden" name="sid" value="<?php echo $ord['sid']; ?>"/>
				<input type="hidden" name="idorder" value="<?php echo $ord['id']; ?>"/>
				<input type="hidden" name="option" value="com_vikbooking"/>
				<input type="hidden" name="task" value="docancelbooking"/>
				<?php echo JHtml::fetch('form.token'); ?>
			</form>
		</div>
	</div>
</div>
	<?php
}

// stand-by booking payment rendering
if (is_array($payment) && $ord['status'] == 'standby') {
	// render the selected payment method
	$lang = JFactory::getLanguage();
	$langtag = substr($lang->getTag(), 0, 2);

	if (VBOPlatformDetection::isWordPress()) {
		/**
		 * @wponly
		 *
		 * @since 	1.0.5
		 */
		$return_url = JUri::root() . "index.php?option=com_vikbooking&view=booking&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts']."&lang=".$langtag;
		$error_url = JUri::root() . "index.php?option=com_vikbooking&view=booking&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts']."&lang=".$langtag;
		$notify_url = JUri::root() . "index.php?option=com_vikbooking&task=notifypayment&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts']."&lang=".$langtag."&tmpl=component";
		/**
		 * @wponly  the URLs must be routed differently for WP
		 */
		$model 	= JModel::getInstance('vikbooking', 'shortcodes', 'admin');
		$itemid = $model->best(array('booking'), (!empty($ord['lang']) ? $ord['lang'] : null));
		if ($itemid) {
			$return_url = str_replace(JUri::root(), '', $return_url);
			$error_url = str_replace(JUri::root(), '', $error_url);
			$notify_url = str_replace(JUri::root(), '', $notify_url);
			$return_url = JRoute::rewrite($return_url . "&Itemid={$itemid}", false);
			$error_url = JRoute::rewrite($error_url . "&Itemid={$itemid}", false);
			$notify_url = JRoute::rewrite($notify_url . "&Itemid={$itemid}", false);
		}
	} else {
		$return_url = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts'] ."&lang=" . $langtag, false, (!empty($bestitemid) ? $bestitemid : null));
		$error_url = VikBooking::externalroute("index.php?option=com_vikbooking&view=booking&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts'] ."&lang=" . $langtag, false, (!empty($bestitemid) ? $bestitemid : null));
		$notify_url = VikBooking::externalroute("index.php?option=com_vikbooking&task=notifypayment&sid=" . (!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']) . "&ts=" . $ord['ts'] . "&lang=" . $langtag . "&tmpl=component", false);
	}

	$transaction_name = VikBooking::getPaymentName();
	$leave_deposit = 0;
	$percentdeposit = "";

	$array_order = [];
	$array_order['details'] = $ord;
	if (empty($array_order['details']['sid']) && !empty($array_order['details']['idorderota']) && !empty($array_order['details']['channel'])) {
		$array_order['details']['sid'] = $array_order['details']['idorderota'];
		$ord['sid'] = $ord['idorderota'];
	}
	$array_order['customer_email'] = $ord['custmail'];
	$array_order['account_name'] = VikBooking::getPaypalAcc();
	$array_order['transaction_currency'] = VikBooking::getCurrencyCodePp();
	$array_order['rooms_name'] = implode(", ", $roomsnames);
	$array_order['transaction_name'] = !empty($transaction_name) ? $transaction_name : (JText::translate('VBORDERNUMBER') . ' ' . $ord['id']);
	$array_order['order_total'] = $isdue;
	$array_order['currency_symb'] = $currencysymb;
	$array_order['net_price'] = $imp;
	$array_order['tax'] = $tax;
	$array_order['return_url'] = $return_url;
	$array_order['error_url'] = $error_url;
	$array_order['notify_url'] = $notify_url;
	$array_order['total_to_pay'] = $isdue;
	$array_order['total_net_price'] = $imp;
	$array_order['total_tax'] = $tax;
	$totalchanged = false;
	if ($payment['charge'] > 0.00) {
		$totalchanged = true;
		if ($payment['ch_disc'] == 1) {
			//charge
			if ($payment['val_pcent'] == 1) {
				//fixed value
				$array_order['total_net_price'] += $payment['charge'];
				$array_order['total_tax'] += $payment['charge'];
				$array_order['total_to_pay'] += $payment['charge'];
				$newtotaltopay = $array_order['total_to_pay'];
			} else {
				//percent value
				$percent_net = $array_order['total_net_price'] * $payment['charge'] / 100;
				$percent_tax = $array_order['total_tax'] * $payment['charge'] / 100;
				$percent_to_pay = $array_order['total_to_pay'] * $payment['charge'] / 100;
				$array_order['total_net_price'] += $percent_net;
				$array_order['total_tax'] += $percent_tax;
				$array_order['total_to_pay'] += $percent_to_pay;
				$newtotaltopay = $array_order['total_to_pay'];
			}
		} else {
			//discount
			if ($payment['val_pcent'] == 1) {
				//fixed value
				$array_order['total_net_price'] -= $payment['charge'];
				$array_order['total_tax'] -= $payment['charge'];
				$array_order['total_to_pay'] -= $payment['charge'];
				$newtotaltopay = $array_order['total_to_pay'];
			} else {
				//percent value
				$percent_net = $array_order['total_net_price'] * $payment['charge'] / 100;
				$percent_tax = $array_order['total_tax'] * $payment['charge'] / 100;
				$percent_to_pay = $array_order['total_to_pay'] * $payment['charge'] / 100;
				$array_order['total_net_price'] -= $percent_net;
				$array_order['total_tax'] -= $percent_tax;
				$array_order['total_to_pay'] -= $percent_to_pay;
				$newtotaltopay = $array_order['total_to_pay'];
			}
		}
	}
	$percentdeposit = false;
	if (!VikBooking::payTotal() && $this->nodep != 1 && VikBooking::allowDepositFromRates($tars)) {
		$percentdeposit = VikBooking::getAccPerCent();
		$percentdeposit = VikBooking::calcDepositOverride($percentdeposit, $ord['days']);
		if ($percentdeposit > 0 && VikBooking::depositAllowedDaysAdv($ord['checkin'])) {
			$leave_deposit = 1;
			if (VikBooking::getTypeDeposit() == "fixed") {
				$array_order['total_to_pay'] = $percentdeposit;
				$array_order['total_net_price'] = $percentdeposit;
				$array_order['total_tax'] = ($array_order['total_to_pay'] - $array_order['total_net_price']);
			} else {
				$array_order['total_to_pay'] = $array_order['total_to_pay'] * $percentdeposit / 100;
				$array_order['total_net_price'] = $array_order['total_net_price'] * $percentdeposit / 100;
				$array_order['total_tax'] = ($array_order['total_to_pay'] - $array_order['total_net_price']);
			}
		}
	}
	$array_order['leave_deposit'] = $leave_deposit;
	$array_order['percentdeposit'] = $percentdeposit;
	$array_order['payment_info'] = $payment;
	$array_order = array_merge($ord, $array_order);

	if (VBOPlatformDetection::isWordPress()) {
		$elapsed_redirect_uri = $return_url;
	} else {
		$elapsed_redirect_uri = JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid='.(!empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']).'&ts='.$ord['ts'].(!empty($bestitemid) ? '&Itemid='.$bestitemid : (!empty($pitemid) ? '&Itemid='.$pitemid : '')), false);;
	}

	//Auto Removal Minutes
	$minautoremove = VikBooking::getMinutesAutoRemove();
	$mins_elapsed = floor(($now_info[0] - $ord['ts']) / 60);
	if ($minautoremove > 0) {
		$booktime_info = getdate($ord['ts']);
		$booktime_offset = date('Z', $ord['ts']) / 60;
		$remainmin = $minautoremove - $mins_elapsed;
		$remainmin = $remainmin < 1 ? 1 : $remainmin;
		$remainmilsec = intval($remainmin * 60 * 1000) + 100;
		$remainmilsec = $remainmilsec < 100 ? 100 : $remainmilsec;
		//calculate the values for the timer
		$hours_left = $remainmin > 59 ? floor($remainmin / 60) : 0;
		$minutes_left = $remainmin - ($hours_left * 60);
		$lbl_hour = strtolower(JText::translate('VBHOUR'));
		$lbl_hours = strtolower(JText::translate('VBHOURS'));
		$lbl_minute = strtolower(JText::translate('VBMINUTE'));
		$lbl_minutes = strtolower(JText::translate('VBMINUTES'));
		$timer_str = $hours_left > 0 ? '<span id="vbo-timer-hours">'.$hours_left.' '.($hours_left == 1 ? $lbl_hour : $lbl_hours).'</span> ' : '';
		$timer_str .= '<span id="vbo-timer-minutes">'.$minutes_left.' '.($minutes_left == 1 ? $lbl_minute : $lbl_minutes).'</span>';
		?>
<script type="text/javascript">
	var vboPayTimerLbl = {
		"hour": "<?php echo addslashes($lbl_hour); ?>",
		"hours": "<?php echo addslashes($lbl_hours); ?>",
		"minute": "<?php echo addslashes($lbl_minute); ?>",
		"minutes": "<?php echo addslashes($lbl_minutes); ?>"
	}
	var vboPayTimeout = setTimeout(function() {
		document.location.href = '<?php echo $elapsed_redirect_uri; ?>';
	}, <?php echo $remainmilsec; ?>);
	var vboPayInterval = setInterval("vboRefreshPayTimer()", 60000);
	var vboBookInfo = new Date(<?php echo $booktime_info['year']; ?>, <?php echo ($booktime_info['mon'] - 1); ?>, <?php echo $booktime_info['mday']; ?>, <?php echo $booktime_info['hours']; ?>, <?php echo $booktime_info['minutes']; ?>, <?php echo $booktime_info['seconds']; ?>, 0);
	var vboPayTimerOffsetSet = false;
	function vboPauseTimeout() {
		clearTimeout(vboPayTimeout);
	}
	function vboRefreshPayTimer() {
		var vboNow = new Date();
		if (!vboPayTimerOffsetSet) {
			var tzoffset = vboNow.getTimezoneOffset() * -1 - <?php echo $booktime_offset; ?>;
			vboBookInfo.setMinutes(vboBookInfo.getMinutes() + tzoffset);
			vboPayTimerOffsetSet = true;
		}

		var mins_elapsed = Math.floor((vboNow - vboBookInfo) / 1000 / 60);
		var remainmin = <?php echo $minautoremove; ?> - mins_elapsed;
		var hours_left = remainmin > 59 ? Math.floor(remainmin / 60) : 0;
		var minutes_left = remainmin - (hours_left * 60);
		if (hours_left < 1 && minutes_left < 1) {
			clearInterval(vboPayInterval);
			if (document.getElementById('vbo-timer-payment')) {
				document.getElementById('vbo-timer-payment').style.display = 'none';
			}
			return false;
		}
		if (document.getElementById('vbo-timer-hours')) {
			if (hours_left < 1) {
				document.getElementById('vbo-timer-hours').style.display = 'none';
			} else {
				document.getElementById('vbo-timer-hours').innerText = hours_left+' '+(hours_left == 1 ? vboPayTimerLbl['hour'] : vboPayTimerLbl['hours']);
			}
		}
		document.getElementById('vbo-timer-minutes').innerText = minutes_left+' '+(minutes_left == 1 ? vboPayTimerLbl['minute'] : vboPayTimerLbl['minutes']);
	}
</script>

<div class="vbo-timer-payment" id="vbo-timer-payment">
	<span class="vbo-timer-payment-str">
		<?php echo JText::sprintf('VBOTIMERPAYMENTSTR', $timer_str); ?>
	</span>
</div>
		<?php
	}
	//

?>
<div class="vbvordpaybutton">
	<?php	
	if ($totalchanged) {
		$chdecimals = $payment['charge'] - (int)$payment['charge'];
		?>
	<p class="vbpaymentchangetot">
		<span class="vbpaymentnamediff">
			<span><?php echo $payment['name']; ?></span>
			(<?php echo ($payment['ch_disc'] == 1 ? "+" : "-").($payment['val_pcent'] == 1 ? '<span class="vbo_currency">'.$currencysymb.'</span> ' : '').'<span class="vbo_price">'.VikBooking::numberFormat($payment['charge']).'</span>'.($payment['val_pcent'] == 1 ? '' : " %"); ?>) 
		</span>
		<span class="vborddiffpayment"><span class="vbo_currency"><?php echo $currencysymb; ?></span> <span class="vbo_price"><?php echo VikBooking::numberFormat($newtotaltopay); ?></span></span>
	</p>
		<?php
	}

	if (VBOPlatformDetection::isWordPress()) {
		/**
		 * @wponly 	The payment gateway is now loaded 
		 * 			using the apposite dispatcher.
		 *
		 * @since 1.0.5
		 */
		JLoader::import('adapter.payment.dispatcher');

		$obj = JPaymentDispatcher::getInstance('vikbooking', $payment['file'], $array_order, $payment['params']);
		// remember to echo the payment
		echo $obj->showPayment();
	} else {
		/**
		 * @joomlaonly 	The Payment Factory library will invoke the gateway.
		 * 
		 * @since 	1.14.3
		 */
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'factory.php';
		$obj = VBOPaymentFactory::getPaymentInstance($payment['file'], $array_order, $payment['params']);

		$obj->showPayment();
	}
	
	?>
</div>
	<?php
}

/**
 * Booking review left by the guest
 * 
 * @since  1.13
 */
$review = VikBooking::getBookingReview($ord);
if (is_array($review) && count($review)) {
	$rev_services = VikBooking::guestReviewsServices();
	$raw_rev_services = array();
	foreach ($rev_services as $sk => $sn) {
		$raw_rev_services[$sn['service_name']] = $sk;
	}
	// translate services
	$vbo_tn->translateContents($rev_services, '#__vikbooking_greview_service');
	?>
<div class="vbo-booking-guest-review">
	<div class="vbo-booking-guest-review-inner">
		<div class="vbo-booking-guest-review-top">
			<div class="vbo-booking-guest-review-time">
				<span><?php echo date(str_replace("/", $datesep, $df).' H:i', strtotime($review['dt'])); ?></span>
			</div>
			<div class="vbo-booking-guest-review-globalscore">
				<span><?php echo $review['score']; ?></span>
			</div>
		</div>
		<div class="vbo-booking-guest-review-bottom">
		<?php
		// scoring per service
		if (isset($review['content']['scoring']) && count($review['content']['scoring']) > 1) {
			// some services have been reviewed ("review_score" is always present)
			?>
			<div class="vbo-booking-guest-review-services-score">
			<?php
			$counter = 0;
			foreach ($review['content']['scoring'] as $servicename => $servicescore) {
				if ($servicename == 'review_score') {
					// protected keyword for the global review score
					$counter++;
					continue;
				}
				$stars_count = floor($servicescore / 2);
				?>
				<div class="vbo-booking-guest-review-service-score">
					<div class="vbo-booking-guest-review-service-score-inner">
						<div class="vbo-booking-guest-review-service-name">
							<span><?php echo isset($raw_rev_services[$servicename]) && isset($rev_services[$raw_rev_services[$servicename]]) ? $rev_services[$raw_rev_services[$servicename]]['service_name'] : ucwords(str_replace('_', ' ', $servicename)); ?></span>
						</div>
						<div class="vbo-booking-guest-review-service-stars">
						<?php
						for ($i = 1; $i <= $stars_count; $i++) {
							VikBookingIcons::e('star', 'vbo-review-star vbo-review-star-full');
						}
						for ($i = ($stars_count + 1); $i <= 5; $i++) {
							VikBookingIcons::e('star', 'vbo-review-star');
						}
						?>
						</div>
					</div>
				</div>
				<?php
				$counter++;
			}
			?>
			</div>
			<?php
		} elseif (isset($review['content']['scoring']) && isset($review['content']['scoring']['review_score'])) {
			// this is a global score for no services
			$stars_count = floor($review['content']['scoring']['review_score'] / 2);
			?>
			<div class="vbo-booking-guest-review-services-score vbo-booking-guest-review-singleservice">
				<div class="vbo-booking-guest-review-service-name">
					<span><?php echo JText::translate('VBOREVIEWYOURRATING'); ?></span>
				</div>
				<div class="vbo-booking-guest-review-service-stars">
				<?php
				for ($i = 1; $i <= $stars_count; $i++) {
					VikBookingIcons::e('star', 'vbo-review-star vbo-review-star-full');
				}
				for ($i = ($stars_count + 1); $i <= 5; $i++) {
					VikBookingIcons::e('star', 'vbo-review-star');
				}
				?>
				</div>
			</div>
			<?php
		}
		// guest review message
		if (isset($review['content']['content']) && !empty($review['content']['content']['message'])) {
			?>
			<div class="vbo-booking-guest-review-message">
				<p><?php echo nl2br($review['content']['content']['message']); ?></p>
			</div>
			<?php
		}
		// owner reply
		if (isset($review['content']['reply']) && ((is_string($review['content']['reply']) && !empty($review['content']['reply'])) || (is_array($review['content']['reply']) && !empty($review['content']['reply']['text'])))) {
			$reply_text = is_array($review['content']['reply']) && isset($review['content']['reply']['text']) ? $review['content']['reply']['text'] : $review['content']['reply'];
			?>
			<div class="vbo-booking-guest-review-owner-reply">
				<h5><?php echo JText::translate('VBOGREVOWNREPLY'); ?></h5>
				<p><?php echo nl2br($reply_text); ?></p>
			</div>
			<?php
		}
		?>
		</div>
	</div>
</div>
	<?php
}

/**
 * Upselling Options/Extras block.
 * 
 * @since  1.13 (J) - 1.3.0 (WP)
 */
$tot_upselling = count($this->upselling);
if ($tot_upselling) {
	// at least one room has options that can be up-sold
	$formatparts = explode(':', VikBooking::getNumberFormatData());
	?>
<div class="vbo-hidein-print vbo-booking-upsell-container">
	<h3><?php echo JText::translate('VBOUPSELLTITLE'); ?></h3>
	<form method="post" action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=upsellextras' . (!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>">
		<div class="vbo-booking-upsell-inner <?php echo $tot_upselling > 1 ? 'vbo-booking-upsell-inner-multi' : 'vbo-booking-upsell-inner-single'; ?>">
		<?php
		foreach ($this->upselling as $kor => $uproom) {
			if (!isset($uproom->upsellable) || !count($uproom->upsellable)) {
				// no upsellable options for this room
				continue;
			}
			?>
			<div class="vbo-booking-upsell-room-wrap">
			<?php
			if ($tot_upselling > 1) {
				// write the room name and adults/children in case of multiple rooms booked
				?>
				<h5 class="vbo-booking-upsell-room-details">
					<span class="vbo-booking-upsell-room-name"><?php echo $uproom->name; ?></span>
					<span class="vbo-booking-upsell-room-adults"><?php echo $uproom->adults; ?> <?php echo ($uproom->adults == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?></span>
				<?php
				if ($orderrooms[$kor]['children'] > 0) {
					?>
					<span class="vbo-booking-upsell-room-children"><?php echo $orderrooms[$kor]['children'] . " " . ($orderrooms[$kor]['children'] == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN')); ?></span>
					<?php
				}
				?>
				</h5>
				<?php
			}
				?>
				<div class="vbo-upsell-options-wrap">
				<?php
				// determine proper nights of stay
				$room_stay_nights = $ord['days'];
				if ($ord['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$kor]) && $room_stay_dates[$kor]['idroom'] == $uproom->id) {
					$room_stay_nights = $room_stay_dates[$kor]['nights'];
				}

				foreach ($uproom->upsellable as $o) {
					if ((int)$o['pcentroom']) {
						// make sure we have a cost for the room, or we should skip this type of option for "incomplete" bookings
						if (!isset($orderrooms[$kor]) || (empty($orderrooms[$kor]['cust_cost']) && empty($orderrooms[$kor]['room_cost']))) {
							continue;
						}
						$o['cost'] = ((!empty($orderrooms[$kor]['cust_cost']) ? $orderrooms[$kor]['cust_cost'] : $orderrooms[$kor]['room_cost']) * $o['cost'] / 100);
					}
					$optcost = intval($o['perday']) == 1 ? ($o['cost'] * $room_stay_nights) : $o['cost'];
					if (!empty($o['maxprice']) && $o['maxprice'] > 0 && $optcost > $o['maxprice']) {
						$optcost = $o['maxprice'];
					}
					if ($o['perperson'] == 1) {
						$optcost = $optcost * $orderrooms[$kor]['adults'];
					}
					$optcost = $optcost * 1;
					$optquaninp = '';
					$optquanbtn = '';
					if (intval($o['hmany']) == 1) {
						if (intval($o['maxquant']) > 0) {
							$optquaninp = "<select id=\"vboaddextra-{$kor}-{$o['id']}\">\n";
							for ($ojj = 0; $ojj <= intval($o['maxquant']); $ojj++) {
								$optquaninp .= "<option value=\"" . $ojj . "\">" . $ojj . "</option>\n";
							}
							$optquaninp .= "</select>\n";
						} else {
							$optquaninp = "<input type=\"number\" min=\"0\" step=\"any\" id=\"vboaddextra-{$kor}-{$o['id']}\" value=\"0\" size=\"5\"/>";
						}
						$optquanbtn = '<button type="button" class="btn vbo-pref-color-btn" onclick="vboAddExtra(' . $kor . ', ' . $o['id'] . ', -1);"><i class="' . VikBookingIcons::i('cart-plus') . '"></i> ' . JText::translate('VBOUPSELLADD') . '</button>';
					} else {
						$optquanbtn = '<button type="button" class="btn vbo-pref-color-btn" onclick="vboAddExtra(' . $kor . ', ' . $o['id'] . ', 1);"><i class="' . VikBookingIcons::i('cart-plus') . '"></i> ' . JText::translate('VBOUPSELLADD') . '</button>';
					}
					?>
					<div class="vbo-upsell-option-entry" id="vbo-option-upsell-<?php echo $kor . '-' . $o['id']; ?>">
					<?php
					if (!empty($o['img'])) {
						?>
						<div class="vbo-upsell-option-entry-img">
							<?php echo '<img class="maxthirty" src="' . VBO_SITE_URI . 'resources/uploads/' . $o['img'] . '"/>'; ?>
						</div>
						<?php
					}
					?>
						<div class="vbo-upsell-option-entry-name">
							<span><?php echo $o['name']; ?></span>
						<?php
						if (!empty($o['descr'])) {
							?>
							<div class="vbo-upsell-option-entry-descr">
								<?php echo $o['descr']; ?>
							</div>
							<?php
						}
						$floatoptprice = VikBooking::sayOptionalsPlusIva($optcost, $o['idiva']);
						?>
						</div>
						<div class="vbo-upsell-option-entry-cost" data-currency="<?php echo $currencysymb; ?>" data-floatprice="<?php echo (float)$floatoptprice; ?>">
							<span class="vbo_currency"><?php echo $currencysymb; ?></span>
							<span class="vbo_price"><?php echo VikBooking::numberFormat($floatoptprice); ?></span>
						</div>
						<div class="vbo-upsell-option-entry-input">
							<?php echo $optquaninp; ?>
						</div>
						<div class="vbo-option-upsell-add">
							<?php echo $optquanbtn; ?>
						</div>
					</div>
					<?php
				}
				?>
				</div>
				<div class="vbo-room-upsell-cart" id="vbo-room-upsell-cart-<?php echo $kor; ?>"></div>
			</div>
			<?php
		}
		?>
		</div>
		<input type="hidden" name="task" value="upsellextras">
		<input type="hidden" name="sid" value="<?php echo !empty($ord['idorderota']) && !empty($ord['channel']) ? $ord['idorderota'] : $ord['sid']; ?>">
		<input type="hidden" name="ts" value="<?php echo $ord['ts']; ?>">
		<div class="vbo-booking-upsell-confirm" style="display: none;">
			<div class="vbo-booking-upsell-confirm-inner">
				<div class="vbo-booking-upsell-confirm-total">
					<span class="vbo-booking-upsell-confirm-txt"><?php echo JText::translate('VBTOTAL'); ?></span>
					<span class="vbo_currency vbo-booking-upsell-confirm-currency"><?php echo $currencysymb; ?></span>
					<span class="vbo_price vbo-booking-upsell-confirm-amount"></span>
				</div>
				<div class="vbo-booking-upsell-confirm-btn">
					<button type="submit" class="btn btn-large vbo-pref-color-btn" onclick="return confirm('<?php echo addslashes(JText::translate('VBOUPSELLCONFIRM')); ?>');"><?php VikBookingIcons::e('check-circle'); ?> <?php echo JText::translate('VBOUPSELLUPDATE'); ?></button>
				</div>
			</div>
		</div>
	</form>
</div>

<script type="text/javascript">
var vbototextras = 0;
function vboAddExtra(kor, optid, quant) {
	if (quant < 0) {
		// get the quantity from the input/select tag
		quant = jQuery('#vboaddextra-' + kor + '-' + optid).val();
		if (!quant || isNaN(quant)) {
			console.error('input not found', kor, optid, quant);
			return false;
		}
		if (quant < 1) {
			alert('<?php echo addslashes(JText::translate('VBOUPSELLQUANTOPT0')); ?>');
			return false;
		}
	}
	var cartelem = jQuery('#vbo-room-upsell-option-' + kor + '-' + optid);
	if (cartelem.length) {
		// remove element from cart if already existed for later appending it to the cart again
		vboRemoveExtra(kor, optid);
	}
	// add element to the cart
	var optelem = jQuery('#vbo-option-upsell-' + kor + '-' + optid);
	if (!optelem.length) {
		console.error('option not found', kor, optid);
		return false;
	}
	// price
	var optprice = parseFloat(optelem.find('.vbo-upsell-option-entry-cost').attr('data-floatprice')) * quant;
	//
	var newcartelem = '<div class="vbo-room-upsell-cart-option" id="vbo-room-upsell-option-' + kor + '-' + optid + '" data-floatprice="' + optprice + '">';
	newcartelem += '<div class="vbo-room-upsell-cart-option-name">' + optelem.find('.vbo-upsell-option-entry-name span').text() + (quant > 1 ? ' (x' + quant + ')' : '') + '</div>';
	if (quant < 2) {
		// single quantity can take the formatted option price
		newcartelem += '<div class="vbo-room-upsell-cart-option-cost">' + optelem.find('.vbo-upsell-option-entry-cost').html() + '</div>';
	} else {
		// multiple quantities should multiply the single raw cost not formatted
		var optcurrency = optelem.find('.vbo-upsell-option-entry-cost').attr('data-currency');
		newcartelem += '<div class="vbo-room-upsell-cart-option-cost">' + optcurrency + ' ' + optprice.toFixed(<?php echo (int)$formatparts[0]; ?>) + '</div>';
	}
	// increase global total
	vbototextras += optprice;
	//
	newcartelem += '<div class="vbo-room-upsell-cart-option-rm"><button type="button" class="btn btn-danger" onclick="vboRemoveExtra(' + kor + ', ' + optid + ');"><i class="<?php echo VikBookingIcons::i('trash-alt'); ?>"></i></button></div>';
	// the necessary input field that will be submitted with the form
	newcartelem += '<input type="hidden" name="addopt[' + kor + '][' + optid + ']" value="' + quant + '"/>';
	//
	newcartelem += '</div>';
	// append new element to the cart and make sure the full class is set
	jQuery('#vbo-room-upsell-cart-' + kor).append(newcartelem).addClass('vbo-room-upsell-cart-full');
	// highlight the option so that we can see it was reserved by adding a class to the container
	optelem.addClass('vbo-option-upsell-addedtocart');
	// display the save button
	jQuery('.vbo-booking-upsell-confirm').fadeIn();
	// refresh total
	vboRefreshTotal();
}
function vboRemoveExtra(kor, optid) {
	var cartelem = jQuery('#vbo-room-upsell-option-' + kor + '-' + optid);
	if (!cartelem.length) {
		console.error('could not find option to remove from the cart', kor, optid);
		return false;
	}
	// decrease global total
	vbototextras -= parseFloat(cartelem.attr('data-floatprice'));
	//
	cartelem.remove();
	// remove class that highlights that the option was reserved
	jQuery('#vbo-option-upsell-' + kor + '-' + optid).removeClass('vbo-option-upsell-addedtocart');
	// check if the cart for this room is no longer full
	if (!jQuery('#vbo-room-upsell-cart-' + kor).find('.vbo-room-upsell-cart-option').length) {
		jQuery('#vbo-room-upsell-cart-' + kor).removeClass('vbo-room-upsell-cart-full');
	}
	// hide the save button if no options in the cart
	if (!jQuery('.vbo-room-upsell-cart-option').length) {
		jQuery('.vbo-booking-upsell-confirm').fadeOut();
	}
	// refresh total
	vboRefreshTotal();
}
function vboRefreshTotal() {
	jQuery('.vbo-booking-upsell-confirm-amount').text(vbototextras.toFixed(<?php echo (int)$formatparts[0]; ?>));
}
</script>
	<?php
}

/**
 * Render the chat with the front-end handler 'vikbooking'.
 * OTA bookings cannot be displayed in front-end so we cannot
 * use any other chat handler. Chat can be disabled from config.
 * Using the chat via front-end imposes the guest to be the sender.
 * 
 * @since  1.12 (J) - 1.1.7 (WP)
 */
$messaging = null;
if (VikBooking::chatEnabled() > 0) {
	// -1 means that VCM is not enabled
	$chat_available = true;
	// check if chat can still be displayed to the guest
	$chat_params = VikBooking::getChatParams();
	if (!isset($chat_params->res_status) || !in_array($ord['status'], $chat_params->res_status)) {
		// chat is disabled for this reservation status
		$chat_available = false;
	}
	if (isset($chat_params->av_type) && isset($chat_params->av_days)) {
		// check days of validity after checkin or checkout
		$chat_lim_ts = $chat_params->av_type == 'checkin' ? strtotime("+{$chat_params->av_days} days", $ord['checkin']) : strtotime("+{$chat_params->av_days} days", $ord['checkout']);
		// compare limits at midnight
		$now_midnight = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
		$lim_midnight = mktime(0, 0, 0, date('n', $chat_lim_ts), date('j', $chat_lim_ts), date('Y', $chat_lim_ts));
		if ($now_midnight > $lim_midnight) {
			// limit date is in the past
			$chat_available = false;
		}
	}
	if ($chat_available) {
		// attempt to get the class instance
		$messaging = VikBooking::getVcmChatInstance($ord['id'], 'vikbooking');
	}
}
if (!is_null($messaging)) {
	$tot_unread = VCMChatHandler::countUnreadMessages($ord['id']);
	?>
<div class="vbo-booking-chat-wrap vbo-booking-chat-closed">
	<div class="vbo-booking-chat-inner">
		<div class="vbo-booking-chat-control" data-message-count="<?php echo $tot_unread; ?>"><?php VikBookingIcons::e('commenting'); ?></div>
		<div class="vbo-booking-chat-container" style="display: none;">
			<h4 class="vbo-booking-chat-intro"><?php echo JText::sprintf('VBOCHATWITH', VikBooking::getFrontTitle()); ?></h4>
			<?php echo $messaging->renderChat(); ?>
		</div>
	</div>
</div>
<script type="text/javascript">
jQuery(function() {
	jQuery('.vbo-booking-chat-control').click(function() {
		jQuery(this).remove();
		jQuery('.vbo-booking-chat-wrap').removeClass('vbo-booking-chat-closed');
		jQuery('.vbo-booking-chat-container').fadeIn(400, function() {
			// animate to that position
			jQuery('html,body').animate({scrollTop: jQuery('.vbo-booking-chat-container').offset().top - 20}, {duration: 400});
			if (typeof VCMChat !== 'undefined') {
				VCMChat.getInstance().scrollToBottom();
			}
		});
	});
	jQuery(window).on('chatsync', function(e) {
		// VCMChat event listener to update notifications badge for new messages
		var newNotifications = e.detail.notifications;
		var currentMessages  = parseInt(jQuery('.vbo-booking-chat-control').attr('data-message-count'));
		jQuery('.vbo-booking-chat-control').attr('data-message-count', (newNotifications + currentMessages));
	});
});
</script>
	<?php
}

/**
 * If necessary, move the payment form onto the selected position.
 * 
 * @since 	1.14 (J) - 1.4.0 (WP)
 */
if (is_array($this->payment) && $this->payment['outposition'] != 'bottom') {
	// move the payment window, if available
	?>
<script type="text/javascript">
	
	jQuery(function() {

		var payment_output = jQuery('.vbvordpaybutton').first(),
			payment_notes  = jQuery('.vbvordpaynote').first(),
			payment_ctimer = jQuery('.vbo-timer-payment').first(),
			payment_wrappr = jQuery('.vbo-paycontainer-pos-<?php echo $this->payment['outposition']; ?>').first();

		if (payment_output.length && payment_wrappr.length) {
			// display final target
			payment_wrappr.show();

			if (payment_notes.length) {
				// prepend notes first
				payment_notes.prependTo(payment_wrappr);
			}

			if (payment_ctimer.length) {
				// prepend countdown timer first
				payment_ctimer.prependTo(payment_wrappr);
			}

			// append payment output
			payment_output.appendTo(payment_wrappr);
		}

	});

</script>
	<?php
}
//
