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

// check session values for meta search channels
$session = JFactory::getSession();
$channel_disclaimer = false;
$vcmchanneldata = $session->get('vcmChannelData', '');
if (!empty($vcmchanneldata) && is_array($vcmchanneldata) && count($vcmchanneldata) > 0) {
	if (array_key_exists('disclaimer', $vcmchanneldata) && !empty($vcmchanneldata['disclaimer'])) {
		$channel_disclaimer = true;
	}
}

$rooms = $this->rooms;
$roomsnum = $this->roomsnum;
$tars = $this->tars;
$prices = $this->prices;
$arrpeople = $this->arrpeople;
$selopt = $this->selopt;
$days = $this->days;
$coupon = $this->coupon;
$first = $this->first;
$second = $this->second;
$payments = $this->payments;
$cfields = $this->cfields;
$customer_details = $this->customer_details;
$countries = $this->countries;
$pkg = $this->pkg;
$vbo_tn = $this->vbo_tn;

// JS lang def
JText::script('VBO_RM_COUPON_CONFIRM');

$vbo_app = VikBooking::getVboApplication();
$showchildren = VikBooking::showChildrenFront();
$totadults = 0;
$totchildren = 0;
$is_package = is_array($pkg) && count($pkg) > 0 ? true : false;
$pitemid = VikRequest::getInt('Itemid', '', 'request');
// single category filter
$pcategories = VikRequest::getString('categories', '', 'request');
// multiple category filters
$pcategory_ids = VikRequest::getVar('category_ids', array());

$wdays_map = array(
	JText::translate('VBWEEKDAYZERO'),
	JText::translate('VBWEEKDAYONE'),
	JText::translate('VBWEEKDAYTWO'),
	JText::translate('VBWEEKDAYTHREE'),
	JText::translate('VBWEEKDAYFOUR'),
	JText::translate('VBWEEKDAYFIVE'),
	JText::translate('VBWEEKDAYSIX')
);

if (VikBooking::tokenForm()) {
	$vikt = uniqid(rand(17, 1717), true);
	$session->set('vikbtoken', $vikt);
	$tok = "<input type=\"hidden\" name=\"viktoken\" value=\"" . $vikt . "\"/>\n";
} else {
	$tok = "";
}

$document = JFactory::getDocument();
if (VikBooking::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery-ui.min.css');
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery.fancybox.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.min.js');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery.fancybox.js');

foreach ($cfields as $cf) {
	if (!empty($cf['poplink'])) {
		?>
<script type="text/javascript">
jQuery.noConflict();
jQuery(function() {
	jQuery(".vbmodal").fancybox({
		"helpers": {
			"overlay": {
				"locked": false
			}
		},
		"width": "70%",
		"height": "75%",
		"autoScale": false,
		"transitionIn": "none",
		"transitionOut": "none",
		"padding": 0,
		"type": "iframe"
	});
});
</script>
		<?php
		break;
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
$datesep = VikBooking::getDateSeparator();
$checkinforlink = date($df, $first);
$checkoutforlink = date($df, $second);
$in_info = getdate($first);
$out_info = getdate($second);

$no_taxes = VikBooking::noTaxRates();
$hide_tax_class = $no_taxes ? ' vbo-hide-tax' : '';

$peopleforlink = '';
foreach ($arrpeople as $aduchild) {
	$totadults += $aduchild['adults'];
	$totchildren += $aduchild['children'];
	$peopleforlink .= '&adults[]='.$aduchild['adults'].'&children[]='.$aduchild['children'];
}
if (!empty($pcategories)) {
	$peopleforlink .= '&categories=' . $pcategories;
}
if (is_array($pcategory_ids) && count($pcategory_ids)) {
	foreach ($pcategory_ids as $pcid) {
		$peopleforlink .= '&category_ids[]=' . $pcid;
	}
}

$roomoptforlink = '';
foreach ($rooms as $r) {
	$roomoptforlink .= '&roomopt[]=' . $r['id'];
}

$roomindexlink = '';
foreach ($this->roomindex as $rindex) {
	$roomindexlink .= '&roomindex[]=' . $rindex;
}

if (count($this->mod_booking)) {
	//booking modification
	?>
<div class="vbo-booking-modification-helper">
	<div class="vbo-booking-modification-helper-inner">
		<div class="vbo-booking-modification-msg">
			<span><?php echo JText::translate('VBOMODBOOKHELPOCONF'); ?></span>
		</div>
		<div class="vbo-booking-modification-canc">
			<a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=cancelmodification&sid='.$this->mod_booking['sid'].'&id='.$this->mod_booking['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>">
				<?php VikBookingIcons::e('times-circle'); ?>
				<?php echo JText::translate('VBOMODBOOKCANCMOD'); ?>
			</a>
		</div>
	</div>
</div>
	<?php
}

?>
<div class="vbstepsbarcont">
	<ol class="vbo-stepbar" data-vbosteps="4">
	<?php
	if ($is_package === true) {
		?>
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=packageslist'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VBOPKGLINK'); ?></a></li>
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=packagedetails&pkgid='.$pkg['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBSTEPROOMSELECTION'); ?></a></li>
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=showprc&checkin='.$first.'&checkout='.$second.'&roomsnum='.$roomsnum.'&days='.$days.'&pkg_id='.$pkg['id'].$peopleforlink.$roomoptforlink.$roomindexlink.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBSTEPOPTIONS'); ?></a></li>
		<?php
	} else {
		$rq_split_stay = VikRequest::getVar('split_stay', array());
		?>
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=vikbooking&checkin='.$first.'&checkout='.$second.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VBSTEPDATES'); ?></a></li>
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=search&checkindate='.urlencode($checkinforlink).'&checkoutdate='.urlencode($checkoutforlink).'&roomsnum='.$roomsnum.$peopleforlink.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBSTEPROOMSELECTION'); ?></a></li>
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=showprc&checkin='.$first.'&checkout='.$second.'&roomsnum='.$roomsnum.'&days='.$days.$peopleforlink.$roomoptforlink.$roomindexlink.(!empty($this->split_stay) && !empty($rq_split_stay) ? '&' . http_build_query(['split_stay' => $rq_split_stay]) : '').(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBSTEPOPTIONS'); ?></a></li>
		<?php
	}
	?>
		<li class="vbo-step vbo-step-current"><span><?php echo JText::translate('VBSTEPCONFIRM'); ?></span></li>
	</ol>
</div>

<div class="vbo-oconfirm-wrapper">
	<div class="vbo-results-head vbo-results-head-oconfirm">
		<div class="vbo-results-nights">
			<?php VikBookingIcons::e((!empty($this->split_stay) ? 'random' : 'calendar'), 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo ($days == 1 ? JText::translate('VBSEARCHRESNIGHT') : JText::translate('VBSEARCHRESNIGHTS')); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $days; ?></span>
			</div>
		</div>
		<div class="vbo-results-numadults">
			<?php VikBookingIcons::e('male', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo ($totadults == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $totadults; ?></span>
			</div>
		</div>
	<?php
	if ($showchildren && $totchildren > 0) {
		?>
		<div class="vbo-results-numchildren">
			<?php VikBookingIcons::e('child', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo $totchildren == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $totchildren; ?></span>
			</div>
		</div>
		<?php
	}
	?>
		<div class="vbo-summary-date">
			<?php VikBookingIcons::e('plane-arrival', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBPICKUP'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $wdays_map[$in_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $first); ?></span>
			</div>
		</div>
		<div class="vbo-summary-date">
			<?php VikBookingIcons::e('plane-departure', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBRETURN'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $wdays_map[$out_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $second); ?></span>
			</div>
		</div>
	</div>

	<div class="vbo-oconfirm-summary-container">
	<?php
	$imp = 0;
	$totdue = 0;
	$saywithout = 0;
	$saywith = 0;
	$tot_taxes = 0;
	$tot_city_taxes = 0;
	$tot_fees = 0;
	$wop = "";
	foreach ($rooms as $num => $r) {
		$ind = $num - 1;

		// determine values to be considered
		$room_nights   = $this->days;
		$room_checkin  = $this->first;
		$room_checkout = $this->second;
		$is_split_room = false;
		if (!empty($this->split_stay) && isset($this->split_stay[$ind]) && $this->split_stay[$ind]['idroom'] == $r['id']) {
			$room_nights   = $this->split_stay[$ind]['nights'];
			$room_checkin  = $this->split_stay[$ind]['checkin_ts'];
			$room_checkout = $this->split_stay[$ind]['checkout_ts'];
			$is_split_room = true;
		}

		if ($is_package === true) {
			$pkg_cost = $pkg['pernight_total'] == 1 ? ($pkg['cost'] * $days) : $pkg['cost'];
			$pkg_cost = $pkg['perperson'] == 1 ? ($pkg_cost * ($arrpeople[$num]['adults'] > 0 ? $arrpeople[$num]['adults'] : 1)) : $pkg_cost;
			$tmpimp = VikBooking::sayPackageMinusIva($pkg_cost, $pkg['idiva']);
			$tmptotdue = VikBooking::sayPackagePlusIva($pkg_cost, $pkg['idiva']);
			$base_cost = $pkg_cost;
		} else {
			$tmpimp = VikBooking::sayCostMinusIva($tars[$num][0]['cost'], $tars[$num][0]['idprice']);
			$tmptotdue = VikBooking::sayCostPlusIva($tars[$num][0]['cost'], $tars[$num][0]['idprice']);
			$base_cost = $tars[$num][0]['cost'];
		}
		$imp += $tmpimp;
		$totdue += $tmptotdue;
		if ($tmptotdue == $base_cost) {
			$tot_taxes += ($base_cost - $tmpimp);
		} else {
			$tot_taxes += ($tmptotdue - $base_cost);
		}
		$saywithout = $tmpimp;
		$saywith = $tmptotdue;
		if (isset($selopt[$num]) && is_array($selopt[$num])) {
			foreach ($selopt[$num] as $selo) {
				$wop .= $num . "_" . $selo['id'] . ":" . $selo['quan'] . (array_key_exists('chageintv', $selo) ? '-'.$selo['chageintv'] : '') . ";";
				$realcost = (intval($selo['perday']) == 1 ? ($selo['cost'] * $room_nights * $selo['quan']) : ($selo['cost'] * $selo['quan']));
				if (!empty ($selo['maxprice']) && $selo['maxprice'] > 0 && $realcost > $selo['maxprice']) {
					$realcost = $selo['maxprice'];
					if (intval($selo['hmany']) == 1 && intval($selo['quan']) > 1) {
						$realcost = $selo['maxprice'] * $selo['quan'];
					}
				}
				if ($selo['perperson'] == 1) {
					$realcost = $realcost * $arrpeople[$num]['adults'];
				}
				$optbeforetax = VikBooking::sayOptionalsMinusIva($realcost, $selo['idiva']);
				$imp += $optbeforetax;
				$tmpopr = VikBooking::sayOptionalsPlusIva($realcost, $selo['idiva']);
				$totdue += $tmpopr;
				if ($selo['is_citytax'] == 1) {
					$tot_city_taxes += $optbeforetax;
				} elseif ($selo['is_fee'] == 1) {
					$tot_fees += $optbeforetax;
				}
				// VBO 1.11 - always calculate the amount of tax no matter if this is already a tax or a fee
				if ($tmpopr == $realcost) {
					$tot_taxes += ($realcost - $optbeforetax);
				} else {
					$tot_taxes += ($tmpopr - $realcost);
				}
				//
			}
		}
		?>
		<div class="vbo-oconfirm-summary-room-wrapper<?php echo $no_taxes ? ' vbo-oconfirm-summary-room-wrapper-notaxes' : ''; ?>">

			<div class="vbo-oconfirm-summary-room-head">
				<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-descr">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-guests">
					<span><?php echo JText::translate('VBOINVTOTGUESTS'); ?></span>
				</div>
				<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-nights">
					<span><?php echo JText::translate('ORDDD'); ?></span>
				</div>
				<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-net<?php echo $hide_tax_class; ?>">
					<span><?php echo JText::translate('ORDNOTAX'); ?></span>
				</div>
				<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-tax<?php echo $hide_tax_class; ?>">
					<span><?php echo JText::translate('ORDTAX'); ?></span>
				</div>
				<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-tot">
					<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
				</div>
			</div>
			
			<div class="vbo-oconfirm-summary-room-row">
				<div class="vbo-oconfirm-summary-room-cell-descr">
					<div class="vbo-oconfirm-roomname vbo-pref-color-text"><?php echo $r['name']; ?></div>
				<?php
				// check if a sub-unit was requested
				if (count($this->roomindex) && !empty($this->roomindex[($num - 1)])) {
					$room_feature = VikBooking::getRoomUnitDistinctiveFeature($r['params'], $this->roomindex[($num - 1)]);
					if ($room_feature !== false) {
						?>
					<div class="vbo-oconfirm-priceinfo vbo-oconfirm-roomdistfeature">
						<span><?php echo $room_feature[0] . ' ' . $room_feature[1]; ?></span>
					</div>
						<?php
					}
				}
				?>
					<div class="vbo-oconfirm-priceinfo">
					<?php
					if ($is_package === true) {
						echo $pkg['name'];
					} else {
						echo VikBooking::getPriceName($tars[$num][0]['idprice'], $vbo_tn).(!empty($tars[$num][0]['attrdata']) ? "<br/>".VikBooking::getPriceAttr($tars[$num][0]['idprice'], $vbo_tn).": ".$tars[$num][0]['attrdata'] : "");
					}
					?>
					</div>
				<?php
				// print split stay information, if available
				if ($is_split_room) {
					?>
					<div class="vbo-oconfirm-priceinfo vbo-oconfirm-splitstay-dates">
						<span class="vbo-oconfirm-splitstay-checkin"><?php VikBookingIcons::e('sign-in', 'vbo-pref-color-text'); ?> <?php echo date(str_replace("/", $datesep, $df), $room_checkin); ?></span>
						<span class="vbo-oconfirm-splitstay-checkout"><?php VikBookingIcons::e('sign-out', 'vbo-pref-color-text'); ?> <?php echo date(str_replace("/", $datesep, $df), $room_checkout); ?></span>
					</div>
					<?php
				}
				?>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-guests">
					<span><?php echo $arrpeople[$num]['adults']; ?> <?php echo ($arrpeople[$num]['adults'] == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?><?php echo ($showchildren && $arrpeople[$num]['children'] > 0 ? ", ".$arrpeople[$num]['children']." ".($arrpeople[$num]['children'] == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN')) : ""); ?></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-nights">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-head-cell-responsive">
						<span><?php echo JText::translate('ORDDD'); ?></span>
					</div>
					<span><?php echo $room_nights; ?></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-net<?php echo $hide_tax_class; ?>">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-head-cell-responsive">
						<span><?php echo JText::translate('ORDNOTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($saywithout); ?></span>
					</span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tax<?php echo $hide_tax_class; ?>">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-head-cell-responsive">
						<span><?php echo JText::translate('ORDTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($saywith - $saywithout); ?></span>
					</span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tot">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-head-cell-responsive">
						<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($saywith); ?></span>
					</span>
				</div>
			</div>
		<?php
		//write options
		$sf = 2;
		if (isset($selopt[$num]) && is_array($selopt[$num])) {
			foreach ($selopt[$num] as $aop) {
				if (intval($aop['perday']) == 1) {
					$thisoptcost = ($aop['cost'] * $aop['quan']) * $room_nights;
				} else {
					$thisoptcost = $aop['cost'] * $aop['quan'];
				}
				if (!empty ($aop['maxprice']) && $aop['maxprice'] > 0 && $thisoptcost > $aop['maxprice']) {
					$thisoptcost = $aop['maxprice'];
					if (intval($aop['hmany']) == 1 && intval($aop['quan']) > 1) {
						$thisoptcost = $aop['maxprice'] * $aop['quan'];
					}
				}
				if ($aop['perperson'] == 1) {
					$thisoptcost = $thisoptcost * $arrpeople[$num]['adults'];
				}
				$optwithout = (intval($aop['perday']) == 1 ? VikBooking::sayOptionalsMinusIva($thisoptcost, $aop['idiva']) : VikBooking::sayOptionalsMinusIva($thisoptcost, $aop['idiva']));
				$optwith = (intval($aop['perday']) == 1 ? VikBooking::sayOptionalsPlusIva($thisoptcost, $aop['idiva']) : VikBooking::sayOptionalsPlusIva($thisoptcost, $aop['idiva']));
				$opttax = ($optwith - $optwithout);
			?>
			<div class="vbo-oconfirm-summary-room-row vbo-oconfirm-summary-option-row">
				<div class="vbo-oconfirm-summary-room-cell-descr">
					<div class="vbo-oconfirm-optname"><?php echo $aop['name'].($aop['quan'] > 1 ? " <small>(x ".$aop['quan'].")</small>" : ""); ?></div>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-guests">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-nights">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-net<?php echo $hide_tax_class; ?>">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-head-cell-responsive">
						<span><?php echo JText::translate('ORDNOTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($optwithout); ?></span>
					</span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tax<?php echo $hide_tax_class; ?>">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-head-cell-responsive">
						<span><?php echo JText::translate('ORDTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($opttax); ?></span>
					</span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tot">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-head-cell-responsive">
						<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($optwith); ?></span>
					</span>
				</div>
			</div>
			<?php
				$sf++;
			}
		}
		//end write options

		// end room wrapper
		?>
		</div>
		<?php
		//

		if ($roomsnum > 1 && $num < $roomsnum) {
			// we no longer need a separator between the rooms containers
		}
	}

	// store Order Total in session for modules
	$session->set('vikbooking_ordertotal', $totdue);

	// coupon code
	$origtotdue = $totdue;
	$usedcoupon = false;
	if (!empty($coupon)) {
		// check min tot ord
		$coupontotok = true;
		if (strlen((string)$coupon['mintotord'])) {
			if ($totdue < $coupon['mintotord']) {
				$coupontotok = false;
			}
		}
		if ($coupon['maxtotord'] > 0 && $totdue > $coupon['maxtotord']) {
			$coupontotok = false;
		}
		if ($coupontotok) {
			$usedcoupon = true;
			if ($coupon['percentot'] == 1) {
				// percent value
				$minuscoupon = 100 - $coupon['value'];
				/**
				 * We allow coupon codes to be applied on the entire reservation or as always just on the total minus mandatory taxes.
				 * 
				 * @since 	1.13.5 (J) - 1.3.5 (WP)
				 * @since 	1.14.3 (J) - 1.4.3 (WP) we also exclude the amount of taxes beside the mandatory fees.
				 * @since 	1.16.0 (J) - 1.6.0 (WP) taxes are proportionally calculated when coupon before tax.
				 * @since 	1.16.8 (J) - 1.6.8 (WP) with coupon before taxes, discounted amount calculation is an equal subtration.
				 */
				$prev_totdue = $totdue;
				$tot_net = ($totdue - $tot_taxes - $tot_city_taxes - $tot_fees);
				$couponsave = ($coupon['excludetaxes'] ? $tot_net : $totdue) * $coupon['value'] / 100;
				$totdue = ($coupon['excludetaxes'] ? $tot_net : $totdue) * $minuscoupon / 100;
				$tot_taxes = $coupon['excludetaxes'] ? ($tot_taxes * ($tot_net - $couponsave) / $tot_net) : ($tot_taxes * $minuscoupon / 100);
				$totdue += $coupon['excludetaxes'] ? ($tot_taxes + $tot_city_taxes + $tot_fees) : 0;
				$couponsave = abs($prev_totdue - $totdue);
			} else {
				// total value
				$couponsave = $coupon['value'];
				$tax_prop = $tot_taxes * $coupon['value'] / $totdue;
				$tot_taxes -= $tax_prop;
				$tot_taxes = $tot_taxes < 0 ? 0 : $tot_taxes;
				$totdue -= $coupon['value'];
				$totdue = $totdue < 0 ? 0 : $totdue;
			}
		} else {
			if ($coupon['maxtotord'] > 0 && $totdue > $coupon['maxtotord']) {
				VikError::raiseWarning('', JText::translate('VBCOUPONINVMAXTOTORD'));
			} else {
				VikError::raiseWarning('', JText::translate('VBCOUPONINVMINTOTORD'));
			}
		}

		if (!$usedcoupon) {
			// reset the coupon value to allow again to submit a new coupon code
			$coupon = [];
		}
	}

	// inject extra information for tracker
	VikBooking::getTracker()
		->setExtraInfo('total', $totdue);

	?>
		<div class="vbo-oconfirm-summary-total-wrapper<?php echo $no_taxes ? ' vbo-oconfirm-summary-total-wrapper-notaxes' : ''; ?>">

			<div class="vbo-oconfirm-summary-room-row vbo-oconfirm-summary-total-row">
				<div class="vbo-oconfirm-summary-room-cell-descr">
					<div class="vbo-oconfirm-total-block"><?php echo JText::translate('VBTOTAL'); ?></div>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-guests">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-nights">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-net<?php echo $hide_tax_class; ?>">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-net">
						<span><?php echo JText::translate('ORDNOTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($imp); ?></span>
					</span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tax<?php echo $hide_tax_class; ?>">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-tax">
						<span><?php echo JText::translate('ORDTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat(($origtotdue - $imp)); ?></span>
					</span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tot">
					<div class="vbo-oconfirm-summary-room-head-cell vbo-oconfirm-summary-room-cell-tot">
						<span><?php echo JText::translate('ORDWITHTAX'); ?></span>
					</div>
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($origtotdue); ?></span>
					</span>
				</div>
			</div>

		<?php
		if ($usedcoupon == true && !empty($coupon)) {
			// build link to remove the coupon
			$coupon_res_vals = [
				'option' => 'com_vikbooking',
				'task' 	 => 'oconfirm',
				'Itemid' => (!empty($pitemid) ? $pitemid : null),
			];
			$coupon_rq_vals = JFactory::getApplication()->input->getArray();
			foreach ($coupon_rq_vals as $rq_key => $rq_val) {
				if ($rq_key == 'couponcode' || $rq_key == 'applyacoupon') {
					continue;
				}
				if (!isset($coupon_res_vals[$rq_key])) {
					$coupon_res_vals[$rq_key] = $rq_val;
				}
			}
			$coupon_rm_link = JRoute::rewrite('index.php?' . http_build_query($coupon_res_vals), true);
			?>
			<div class="vbo-oconfirm-summary-room-row vbo-oconfirm-summary-total-row vbo-oconfirm-summary-coupon-row">
				<div class="vbo-oconfirm-summary-room-cell-descr">
					<span><?php echo JText::translate('VBCOUPON'); ?> <?php echo $coupon['code']; ?><a class="vbo-remove-coupon" href="<?php echo $coupon_rm_link; ?>" onclick="return confirm(Joomla.JText._('VBO_RM_COUPON_CONFIRM'));"><?php VikBookingIcons::e('times-circle'); ?></a></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-guests">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-nights">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-net<?php echo $hide_tax_class; ?>">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tax<?php echo $hide_tax_class; ?>">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tot">
					<span class="vbcurrency">- <span class="vbo_currency"><?php echo $currencysymb; ?></span></span> 
					<span class="vbprice"><span class="vbo_price"><?php echo VikBooking::numberFormat($couponsave); ?></span></span>
				</div>
			</div>

			<div class="vbo-oconfirm-summary-room-row vbo-oconfirm-summary-total-row vbo-oconfirm-summary-coupon-newtot-row">
				<div class="vbo-oconfirm-summary-room-cell-descr">
					<div class="vbo-oconfirm-total-block"><?php echo JText::translate('VBNEWTOTAL'); ?></div>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-guests">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-nights">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-net<?php echo $hide_tax_class; ?>">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tax<?php echo $hide_tax_class; ?>">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tot">
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($totdue); ?></span>
					</span>
				</div>
			</div>
			<?php
		}

		if (count($this->mod_booking) && $this->mod_booking['total'] > 0) {
			//booking modification
			$modbook_tot_diff = abs($this->mod_booking['total'] - $totdue);
			$modbook_diff_op = $this->mod_booking['total'] <= $totdue ? '+' : '-';
			?>
			<div class="vbo-oconfirm-summary-room-row vbo-oconfirm-summary-total-row vbordrowtotal-prevtot">
				<div class="vbo-oconfirm-summary-room-cell-descr">
					<div class="vbo-oconfirm-total-block vbo-oconfirm-previoustotal-block"><?php echo JText::translate('VBOMODBOOKPREVTOT'); ?></div>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-guests">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-nights">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-net<?php echo $hide_tax_class; ?>">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tax<?php echo $hide_tax_class; ?>">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tot">
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($this->mod_booking['total']); ?></span>
					</span>
				</div>
			</div>

			<div class="vbo-oconfirm-summary-room-row vbo-oconfirm-summary-total-row <?php echo $modbook_diff_op == '+' ? 'vbordrowtotal-negative' : 'vbordrowtotal-positive'; ?>">
				<div class="vbo-oconfirm-summary-room-cell-descr">
					<div class="vbo-oconfirm-total-block vbo-oconfirm-diffmod-block"><?php echo JText::translate('VBOMODBOOKDIFFTOT'); ?></div>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-guests">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-nights">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-net<?php echo $hide_tax_class; ?>">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tax<?php echo $hide_tax_class; ?>">
					<span></span>
				</div>
				<div class="vbo-oconfirm-summary-room-cell-tot">
					<!-- <span class="vbo-modbook-diffop"><?php echo $modbook_diff_op; ?></span> -->
					<span class="vbcurrency">
						<span class="vbo_currency"><?php echo $currencysymb; ?></span>
					</span> 
					<span class="vbprice">
						<span class="vbo_price"><?php echo VikBooking::numberFormat($modbook_tot_diff); ?></span>
					</span>
				</div>
			</div>
			<?php
		}
		?>
		</div>

	</div>
</div>

<div class="vbo-oconfirm-middlep">
<?php
// enter coupon form
if (VikBooking::couponsEnabled() && empty($coupon) && $is_package !== true && !$this->mod_booking) {
	?>
	<div class="vbo-coupon-outer">
		<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" class="vbo-coupon-form">
			<div class="vbentercoupon">
				<span class="vbhaveacoupon"><?php echo JText::translate('VBHAVEACOUPON'); ?></span>
				<div class="vbentercoupon-inner">
					<input type="text" name="couponcode" value="" size="20" class="vbinputcoupon"/>
					<input type="submit" class="btn vbsubmitcoupon vbo-pref-color-btn" name="applyacoupon" value="<?php echo JText::translate('VBSUBMITCOUPON'); ?>"/>
				</div>
			</div>
			<input type="hidden" name="task" value="oconfirm"/>
			<input type="hidden" name="days" value="<?php echo $days; ?>"/>
			<input type="hidden" name="roomsnum" value="<?php echo $roomsnum; ?>"/>
			<input type="hidden" name="checkin" value="<?php echo $first; ?>"/>
			<input type="hidden" name="checkout" value="<?php echo $second; ?>"/>
			<?php
			if (!empty($pitemid)) {
				?>
			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
				<?php
			}
			foreach($rooms as $num => $r) {
				echo '<input type="hidden" name="priceid'.$num.'" value="'.$prices[$num].'"/>'."\n";
				echo '<input type="hidden" name="roomid[]" value="'.$r['id'].'"/>'."\n";
				echo '<input type="hidden" name="adults[]" value="'.$arrpeople[$num]['adults'].'"/>'."\n";
				echo '<input type="hidden" name="children[]" value="'.$arrpeople[$num]['children'].'"/>'."\n";
				if (isset($selopt[$num]) && is_array($selopt[$num])) {
					foreach ($selopt[$num] as $aop) {
						echo '<input type="hidden" name="optid'.$num.$aop['id'].(!empty($aop['ageintervals']) && array_key_exists('chageintv', $aop) ? '[]' : '').'" value="'.(!empty($aop['ageintervals']) && array_key_exists('chageintv', $aop) ? $aop['chageintv'] : $aop['quan']).'"/>'."\n";
					}
				}
			}
			?>
		</form>
	</div>
	<?php
}
//Customers PIN
if (VikBooking::customersPinEnabled() && !VikBooking::userIsLogged() && !(count($customer_details) > 0)) {
	?>
	<div class="vbo-enterpin-block">
		<div class="vbo-enterpin-top">
			<h4><?php echo JText::translate('VBRETURNINGCUSTOMER'); ?></h4>
			<div class="vbo-enterpin-inner">
				<span><?php echo JText::translate('VBENTERPINCODE'); ?></span>
				<div class="vbo-enterpin-btns">
					<input type="text" id="vbo-pincode-inp" value="" size="6"/>
					<button type="button" class="btn vbo-pincode-sbmt vbo-pref-color-btn"><?php echo JText::translate('VBAPPLYPINCODE'); ?></button>
				</div>
			</div>
		</div>
		<div class="vbo-enterpin-response"></div>
	</div>

	<script>
	jQuery(function() {
		jQuery(".vbo-pincode-sbmt").click(function() {
			var pin_code = jQuery("#vbo-pincode-inp").val();
			jQuery(this).prop('disabled', true);
			jQuery(".vbo-enterpin-response").hide();
			jQuery.ajax({
				type: "POST",
				url: "<?php echo VikBooking::ajaxUrl(JRoute::rewrite('index.php?option=com_vikbooking&task=validatepin&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)); ?>",
				data: {
					pin: pin_code
				}
			}).done(function(res) {
				var pinobj = typeof res === 'string' ? JSON.parse(res) : res;
				if (pinobj.hasOwnProperty('success')) {
					jQuery(".vbo-enterpin-top").hide();
					jQuery(".vbo-enterpin-response").removeClass("vbo-enterpin-error").addClass("vbo-enterpin-success").html("<span class=\"vbo-enterpin-welcome\"><?php echo addslashes(JText::translate('VBWELCOMEBACK')); ?></span><span class=\"vbo-enterpin-customer\">"+pinobj.first_name+" "+pinobj.last_name+"</span>").fadeIn();
					jQuery.each(pinobj.cfields, function(k, v) {
						if (jQuery("#vbf-inp"+k).length) {
							jQuery("#vbf-inp"+k).val(v);
						}						
					});
					var user_country = pinobj.country;
					if (jQuery(".vbf-countryinp").length && user_country.length) {
						jQuery(".vbf-countryinp option").each(function(i) {
							var opt_country = jQuery(this).val();
							if (opt_country.substring(0, 3) == user_country) {
								jQuery(this).prop("selected", true);
								jQuery('select.vbf-countryinp').trigger('change');
								return false;
							}
						});
					}
					if (pinobj.hasOwnProperty('has_discounts') && pinobj['has_discounts'] && jQuery('.vbo-coupon-form').length) {
						jQuery('.vbo-coupon-form').submit();
					}
				} else {
					jQuery(".vbo-enterpin-response").addClass("vbo-enterpin-error").html("<p><?php echo addslashes(JText::translate('VBINVALIDPINCODE')); ?></p>").fadeIn();
					jQuery(".vbo-pincode-sbmt").prop('disabled', false);
				}
			}).fail(function() {
				alert('Error validating the PIN. Request failed.');
				jQuery(".vbo-pincode-sbmt").prop('disabled', false);
			});
		});
	});
	</script>
	<?php
}
?>
</div>	

	<script type="text/javascript">
		function checkvbFields() {
			var vbvar = document.vb;
			<?php
foreach ($cfields as $cf) {
	if (intval($cf['required']) == 1) {
		if ($cf['type'] == "text" || $cf['type'] == "textarea" || $cf['type'] == "date" || $cf['type'] == "country" || $cf['type'] == "select") {
			?>
			if (!vbvar.vbf<?php echo $cf['id']; ?>.value.match(/\S/)) {
				document.getElementById('vbf<?php echo $cf['id']; ?>').style.color='#ff0000';
				return false;
			} else {
				document.getElementById('vbf<?php echo $cf['id']; ?>').style.color='';
			}
			<?php
		} elseif ($cf['type'] == "state") {
			?>
			if (!vbvar.vbf<?php echo $cf['id']; ?>.value.match(/\S/) && jQuery('.vbf-stateinp[name="vbf<?php echo $cf['id']; ?>"]').find('option').length > 1) {
				document.getElementById('vbf<?php echo $cf['id']; ?>').style.color='#ff0000';
				return false;
			} else {
				document.getElementById('vbf<?php echo $cf['id']; ?>').style.color='';
			}
			<?php
		} elseif ($cf['type'] == "checkbox") {
			//checkbox
			?>
			if (vbvar.vbf<?php echo $cf['id']; ?>.checked) {
				document.getElementById('vbf<?php echo $cf['id']; ?>').style.color='';
			} else {
				document.getElementById('vbf<?php echo $cf['id']; ?>').style.color='#ff0000';
				return false;
			}
			<?php
		}
	}
}
?>
  			return true;
  		}

  		function validateVbSubmit() {
  			if (!checkvbFields()) {
  				var vbalert_cont = document.getElementById('vbo-alert-container-confirm');
  				if (vbalert_cont !== null) {
  					vbalert_cont.style.display = 'block';
  					vbalert_cont.style.opacity = '1';
  					setTimeout(vbHideAlertFillin, 10000);
  				}
  				return false;
  			}
  			// disable submit button to avoid multiple submissions
  			var subm_btn = document.querySelector('input[name="saveorder"]');
  			if (subm_btn) {
  				subm_btn.disabled = true;
  			}

  			return true;
  		}

  		function vbHideAlertFillin() {
  			var vbalert_cont = document.getElementById('vbo-alert-container-confirm');
  			if (vbalert_cont !== null) {
  				vbalert_cont.style.opacity = '0';
        		setTimeout(function() {
        			vbalert_cont.style.display = 'none';
        		}, 600);
  			}
  		}

  		function vboReloadStates(country_3_code) {
			var states_elem = jQuery('select.vbf-stateinp');

			// get the current state, if any
			var current_state = states_elem.first().attr('data-stateset');

			// unset the current states/provinces
			states_elem.html('');

			if (!country_3_code || !country_3_code.length) {
				return;
			}

			// make a request to load the states/provinces of the selected country
			jQuery.ajax({
				type: "POST",
				url: "<?php echo VikBooking::ajaxUrl(JRoute::rewrite('index.php?option=com_vikbooking&task=states_load_from_country'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false)); ?>",
				data: {
					country_3_code: country_3_code,
					tmpl: "component"
				}
			}).done(function(response) {
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

					if (current_state.length && states_elem.find('option[value="' + current_state + '"]').length) {
						// set the current value
						states_elem.val(current_state);
					}
				} catch(err) {
					console.error('could not parse JSON response', err, response);
				}
			}).fail(function(error) {
				console.error(error);
			});
		}

		jQuery(function() {
			// country selection
			jQuery('select.vbf-countryinp').on('change', function() {
				// trigger event for phone number
				jQuery('input.vbf-phoneinp').trigger('vboupdatephonenumber', jQuery(this).find('option:selected').attr('data-c2code'));
				// reload state/province
				vboReloadStates(jQuery(this).find('option:selected').attr('data-threecode'));
			});

			// check if a value is populated when page loads
			if (jQuery('select.vbf-countryinp').length && jQuery('select.vbf-countryinp').first().val().length) {
				jQuery('select.vbf-countryinp').trigger('change');
			}
		});
	</script>
		
	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" name="vb" method="post" onsubmit="javascript: return validateVbSubmit();">
	<?php

if (count($cfields)) {
	?>
		<div class="vbcustomfields">
	<?php
	$currentUser = JFactory::getUser();
	$useremail = !empty($currentUser->email) ? $currentUser->email : "";
	$useremail = array_key_exists('email', $customer_details) ? $customer_details['email'] : $useremail;
	$nominatives = array();
	if (count($customer_details) > 0) {
		$nominatives[] = $customer_details['first_name'];
		$nominatives[] = $customer_details['last_name'];
	}
	foreach ($cfields as $cf) {
		if (intval($cf['required']) == 1) {
			$isreq = "<span class=\"vbrequired\"><sup>*</sup></span> ";
		} else {
			$isreq = "";
		}
		if (!empty ($cf['poplink'])) {
			$fname = "<a href=\"" . $cf['poplink'] . "\" id=\"vbf" . $cf['id'] . "\" target=\"_blank\" class=\"vbmodal\">" . JText::translate($cf['name']) . "</a>";
		} else {
			$fname = "<label id=\"vbf" . $cf['id'] . "\" for=\"vbf-inp" . $cf['id'] . "\">" . JText::translate($cf['name']) . "</label>";
		}
		if ($cf['type'] == "text") {
			$def_textval = '';
			if ($cf['isemail'] == 1) {
				$def_textval = $useremail;
			} elseif ($cf['isphone'] == 1) {
				if (array_key_exists('phone', $customer_details)) {
					$def_textval = $customer_details['phone'];
				}
			} elseif ($cf['isnominative'] == 1) {
				if (count($nominatives) > 0) {
					$def_textval = array_shift($nominatives);
				}
			} elseif (array_key_exists('cfields', $customer_details)) {
				if (array_key_exists($cf['id'], $customer_details['cfields'])) {
					$def_textval = $customer_details['cfields'][$cf['id']];
				}
			}
			?>
			<div class="vbo-oconfirm-cfield-entry">
				<div class="vbo-oconfirm-cfield-label">
					<?php echo $isreq; ?>
					<?php echo $fname; ?>
				</div>
				<div class="vbo-oconfirm-cfield-input">
				<?php
				if ($cf['isphone'] == 1) {
					echo $vbo_app->printPhoneInputField(array('name' => 'vbf' . $cf['id'], 'id' => 'vbf-inp' . $cf['id'], 'value' => $def_textval, 'class' => 'vbinput vbf-phoneinp', 'size' => '40'));
				} else {
					$input_type = $cf['isemail'] == 1 ? 'email' : 'text';
					?>
					<input type="<?php echo $input_type; ?>" name="vbf<?php echo $cf['id']; ?>" id="vbf-inp<?php echo $cf['id']; ?>" value="<?php echo $def_textval; ?>" size="40" class="vbinput"/>
					<?php
				}
				?>
				</div>
			</div>
			<?php
		} elseif ($cf['type'] == "textarea") {
			$def_textval = '';
			if (isset($customer_details['cfields']) && array_key_exists($cf['id'], $customer_details['cfields'])) {
				$def_textval = $customer_details['cfields'][$cf['id']];
			}
			?>
			<div class="vbo-oconfirm-cfield-entry vbo-oconfirm-cfield-entry-textarea">
				<div class="vbo-oconfirm-cfield-label">
					<?php echo $isreq; ?>
					<?php echo $fname; ?>
				</div>
				<div class="vbo-oconfirm-cfield-input">
					<textarea name="vbf<?php echo $cf['id']; ?>" id="vbf-inp<?php echo $cf['id']; ?>" rows="5" cols="30" class="vbtextarea"><?php echo $def_textval; ?></textarea>
				</div>
			</div>
			<?php
		} elseif ($cf['type'] == "date") {
			?>
			<div class="vbo-oconfirm-cfield-entry">
				<div class="vbo-oconfirm-cfield-label">
					<?php echo $isreq; ?>
					<?php echo $fname; ?>
				</div>
				<div class="vbo-oconfirm-cfield-input">
					<?php echo $vbo_app->getCalendar('', 'vbf'.$cf['id'], 'vbf-inp'.$cf['id'], VikBooking::getDateFormat(), array('class'=>'vbinput', 'size'=>'10',  'maxlength'=>'19')); ?>
				</div>
			</div>
			<?php
		} elseif ($cf['type'] == "country" && is_array($countries)) {
			$usercountry = '';
			if (array_key_exists('country', $customer_details)) {
				$usercountry = !empty($customer_details['country']) ? substr($customer_details['country'], 0, 3) : '';
			}
			$countries_sel = '<select name="vbf'.$cf['id'].'" class="vbf-countryinp"><option value=""></option>'."\n";
			foreach ($countries as $country) {
				$countries_sel .= '<option data-threecode="' . $country['country_3_code'] . '" data-c2code="' . $country['country_2_code'] . '" value="' . $country['country_3_code'] . '::' . $country['country_name'] . '"' . ($country['country_3_code'] == $usercountry || $cf['defvalue'] == $country['country_3_code'] ? ' selected="selected"' : '') . '>' . $country['country_name'] . '</option>' . "\n";
			}
			$countries_sel .= '</select>';
			?>
			<div class="vbo-oconfirm-cfield-entry">
				<div class="vbo-oconfirm-cfield-label">
					<?php echo $isreq; ?>
					<?php echo $fname; ?>
				</div>
				<div class="vbo-oconfirm-cfield-input">
					<?php echo $countries_sel; ?>
				</div>
			</div>
			<?php
		} elseif ($cf['type'] == 'state') {
			?>
			<div class="vbo-oconfirm-cfield-entry">
				<div class="vbo-oconfirm-cfield-label">
					<?php echo $isreq; ?>
					<?php echo $fname; ?>
				</div>
				<div class="vbo-oconfirm-cfield-input">
					<select name="vbf<?php echo $cf['id']; ?>" class="vbf-stateinp" data-stateset="<?php echo !empty($customer_details['state']) ? htmlspecialchars($customer_details['state']) : ''; ?>">
						<option value="">-----</option>
					</select>
				</div>
			</div>
			<?php
		} elseif ($cf['type'] == "select") {
			$answ = explode(";;__;;", $cf['choose']);
			$wcfsel = "<select name=\"vbf" . $cf['id'] . "\">\n";
			foreach ($answ as $aw) {
				if (!empty ($aw)) {
					$wcfsel .= "<option value=\"" . $aw . "\">" . $aw . "</option>\n";
				}
			}
			$wcfsel .= "</select>\n";
			?>
			<div class="vbo-oconfirm-cfield-entry">
				<div class="vbo-oconfirm-cfield-label">
					<?php echo $isreq; ?>
					<?php echo $fname; ?>
				</div>
				<div class="vbo-oconfirm-cfield-input">
					<?php echo $wcfsel; ?>
				</div>
			</div>
			<?php
		} elseif ($cf['type'] == "separator") {
			$cfsepclass = strlen(JText::translate($cf['name'])) > 30 ? "vbseparatorcflong" : "vbseparatorcf";
			?>
			<div class="vbo-oconfirm-cfield-entry vbo-oconfirm-cfield-entry-separator">
				<div class="vbo-oconfirm-cfield-separator <?php echo $cfsepclass; ?>">
					<h4><?php echo JText::translate($cf['name']); ?></h4>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="vbo-oconfirm-cfield-entry vbo-oconfirm-cfield-entry-checkbox">
				<div class="vbo-oconfirm-cfield-label">
					<?php echo $isreq; ?>
					<?php echo $fname; ?>
				</div>
				<div class="vbo-oconfirm-cfield-input">
					<input type="checkbox" name="vbf<?php echo $cf['id']; ?>" id="vbf-inp<?php echo $cf['id']; ?>" value="<?php echo JText::translate('VBYES'); ?>" <?php echo !(bool)$cf['required'] && isset($customer_details['cfields']) && isset($customer_details['cfields'][$cf['id']]) ? 'checked="checked"' : ''; ?>/>
				</div>
			</div>
			<?php
		}
	}
	?>
		</div>
	<?php
}
?>
		<input type="hidden" name="days" value="<?php echo $days; ?>"/>
  		<input type="hidden" name="roomsnum" value="<?php echo $roomsnum; ?>"/>
  		<input type="hidden" name="checkin" value="<?php echo $first; ?>"/>
  		<input type="hidden" name="checkout" value="<?php echo $second; ?>"/>
  		<input type="hidden" name="totdue" value="<?php echo $totdue; ?>"/>
  		<?php
  		if ($is_package === true) {
  			echo '<input type="hidden" name="pkg_id" value="'.$pkg['id'].'"/>'."\n";
  		}

  		foreach ($rooms as $num => $r) {
  			if ($is_package !== true) {
  				echo '<input type="hidden" name="prtar[]" value="'.$tars[$num][0]['id'].'"/>'."\n";
  			}
  			echo '<input type="hidden" name="priceid[]" value="'.$prices[$num].'"/>'."\n";
  			echo '<input type="hidden" name="rooms[]" value="'.$r['id'].'"/>'."\n";
  			echo '<input type="hidden" name="adults[]" value="'.$arrpeople[$num]['adults'].'"/>'."\n";
  			echo '<input type="hidden" name="children[]" value="'.$arrpeople[$num]['children'].'"/>'."\n";
  		}

  		if (!empty($this->split_stay)) {
			// add hidden fields for the split stay booking
			foreach ($this->split_stay as $spsk => $sps_room) {
				foreach ($sps_room as $sps_room_k => $sps_room_v) {
					if ($sps_room_k == 'checkin_ts' || $sps_room_k == 'checkout_ts') {
						// these values are better to be re-calculated
						continue;
					}
					?>
		<input type="hidden" name="split_stay[<?php echo $spsk; ?>][<?php echo $sps_room_k; ?>]" value="<?php echo JHtml::fetch('esc_attr', $sps_room_v); ?>" />
					<?php
				}
			}
		}
  		?>
		
		<input type="hidden" name="optionals" value="<?php echo $wop; ?>"/>
		
		<?php
		if ($usedcoupon == true && !empty($coupon) && $is_package !== true) {
			?>
		<input type="hidden" name="couponcode" value="<?php echo $coupon['code']; ?>"/>
			<?php
		}
		?>
		<?php echo !empty($tok) ? $tok . JHtml::fetch('form.token') : ''; ?>
		<input type="hidden" name="task" value="saveorder"/>
		<?php

if (!empty($payments) && !count($this->mod_booking)) {
	?>
	<script type="text/javascript">
		function vboToggleActivePaymethod(elem) {
			jQuery('.vbo-oconfirm-paymethod-item').removeClass('vbo-oconfirm-paymethod-item-active');
			jQuery(elem).parent('.vbo-oconfirm-paymethod-item').addClass('vbo-oconfirm-paymethod-item-active');
		}
	</script>

	<div class="vbo-oconfirm-paymentopts">
		<h4><?php echo JText::translate('VBCHOOSEPAYMENT'); ?></h4>
		<ul class="vbo-oconfirm-paymethods-list">
	<?php
	$non_ref_rates_found = VikBooking::findNonRefundableRates($tars);
	foreach ($payments as $pk => $pay) {
		if ($pay['hidenonrefund'] > 0 && $non_ref_rates_found) {
			continue;
		}
		if ($pay['onlynonrefund'] > 0 && !$non_ref_rates_found) {
			continue;
		}
		$rcheck = $pk == 0 ? " checked=\"checked\"" : "";
		$saypcharge = "";
		if ($pay['charge'] > 0.00) {
			$decimals = $pay['charge'] - (int)$pay['charge'];
			if ($decimals > 0.00) {
				$okchargedisc = VikBooking::numberFormat($pay['charge']);
			} else {
				$okchargedisc = number_format($pay['charge'], 0);
			}
			$saypcharge .= " (".($pay['ch_disc'] == 1 ? "+" : "-");
			$saypcharge .= ($pay['val_pcent'] == 1 ? "<span class=\"vbcurrency\">".$currencysymb."</span> " : "")."<span class=\"vbprice\">" . $okchargedisc . "</span>" . ($pay['val_pcent'] == 1 ? "" : " <span class=\"vbcurrency\">%</span>");
			$saypcharge .= ")";
		}
		?>
			<li class="vbo-oconfirm-paymethod-item<?php echo $pk == 0 ? ' vbo-oconfirm-paymethod-item-active' : ''; ?>">
				<input type="radio" name="gpayid" value="<?php echo $pay['id']; ?>" onclick="vboToggleActivePaymethod(this);" id="gpay<?php echo $pay['id']; ?>"<?php echo $rcheck; ?>/>
				<label for="gpay<?php echo $pay['id']; ?>">
					<span class="vbo-paymeth-info"><?php echo $pay['name'] . $saypcharge; ?></span>
				</label>
		<?php
		$pay_img_name = '';
		if (strpos($pay['file'], '.') !== false) {
			$fparts = explode('.', $pay['file']);
			$pay_img_name = array_shift($fparts);
		}

		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly  Since the payments may be loaded from external plugins,
			 * 			the logos MUST be retrieved using an apposite filter.
			 *
			 * @since 	1.0.5
			 */
			$logo = array(
				'name' => $pay_img_name,
				'path' => VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . $pay_img_name . '.png',
				'uri'  => VBO_ADMIN_URI . 'payments/' . $pay_img_name . '.png',
			);

			/**
			 * Hook used to filter the array containing the logo's information.
			 * By default, the array contains the standard path and URI, related
			 * to the payment folder of the plugin.
			 *
			 * Plugins attached to this hook are able to filter the payment logo in case
			 * the image is stored somewhere else.
			 *
			 * @param 	array 	An array containing the following keys:
			 * 					- name 	the payment name;
			 * 					- path 	the payment logo absolute path;
			 * 					- uri 	the payment logo image URI.
			 *
			 * @since 	1.0.5
			 */
			$logo = apply_filters('vikbooking_oconfirm_payment_logo', $logo);
		}

		if (!empty($pay['logo'])) {
			/**
			 * Payment methods can have their own custom logo.
			 * 
			 * @since 	1.14 (J) - 1.4.0 (WP)
			 */
			$pay['logo'] = strpos($pay['logo'], 'http') === false ? JUri::root() . $pay['logo'] : $pay['logo'];
			?>
				<span class="vbo-payment-image">
					<label for="gpay<?php echo $pay['id']; ?>">
						<img src="<?php echo $pay['logo']; ?>" alt="<?php echo htmlspecialchars($pay['name']); ?>"/>
					</label>
				</span>
			<?php
		} elseif (VBOPlatformDetection::isWordPress() && !empty($pay_img_name) && file_exists($logo['path'])) {
			?>
				<span class="vbo-payment-image">
					<label for="gpay<?php echo $pay['id']; ?>">
						<img src="<?php echo $logo['uri']; ?>" alt="<?php echo htmlspecialchars($pay['name']); ?>"/>
					</label>
				</span>
			<?php
		} elseif (defined('_JEXEC') && !empty($pay_img_name) && file_exists(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'payments' . DIRECTORY_SEPARATOR . $pay_img_name . '.png')) {
			?>
				<span class="vbo-payment-image">
					<label for="gpay<?php echo $pay['id']; ?>">
						<img src="<?php echo VBO_ADMIN_URI; ?>payments/<?php echo $pay_img_name; ?>.png" alt="<?php echo htmlspecialchars($pay['name']); ?>"/>
					</label>
				</span>
			<?php
		}
		?>
			</li>
		<?php
	}
	?>
		</ul>
	<?php
	//choose deposit (Pay Entire Amount = OFF, Deposit Booking Days in Advance <= Days within Checkin, Customer Choice of Deposit = ON, Deposit Amount > 0, Deposit Non-Refundable=true, Deposit < 100 if %)
	//deposit is always disabled for booking modification
	$dep_amount = VikBooking::getAccPerCent();
	$dep_amount = VikBooking::calcDepositOverride($dep_amount, $days);
	$dep_type = VikBooking::getTypeDeposit();
	$dep_nonrefund_allowed = VikBooking::allowDepositFromRates($tars);
	if (!(count($this->mod_booking) > 0) && !VikBooking::payTotal() && VikBooking::depositAllowedDaysAdv($second) && VikBooking::depositCustomerChoice() && $dep_amount > 0 && $dep_nonrefund_allowed && ($dep_type == "fixed" || ($dep_type != "fixed" && $dep_amount < 100))) {
		$dep_amount = ($dep_amount - abs($dep_amount)) > 0.00 ? VikBooking::numberFormat($dep_amount) : $dep_amount;
		$dep_string = $dep_type == "fixed" ? $currencysymb.' '.$dep_amount : $dep_amount.'%';
		?>
		<div class="vbo-oconfirm-choosedeposit">
			<h4><?php echo JText::translate('VBCHOOSEDEPOSIT'); ?></h4>
			<div class="vbo-oconfirm-choosedeposit-inner">
				<div class="vbo-oconfirm-choosedeposit-payfull">
					<input type="radio" name="nodep" value="1" id="nodepone" checked="checked" />
					<label for="nodepone"><?php echo JText::translate('VBCHOOSEDEPOSITPAYFULL'); ?></label>
				</div>
				<div class="vbo-oconfirm-choosedeposit-paydeposit">
					<input type="radio" name="nodep" value="0" id="nodeptwo" />
					<label for="nodeptwo"><?php echo JText::sprintf('VBCHOOSEDEPOSITPAYDEPOF', $dep_string); ?></label>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	</div>
	<?php
}
?>
		<div class="vboconfirmbottom">
			<input type="submit" name="saveorder" value="<?php echo count($this->mod_booking) ? JText::translate('VBOMODBOOKCONFIRMBTN') : JText::translate('VBORDCONFIRM'); ?>" class="btn booknow vbo-pref-color-btn"/>
			<div class="goback">
				<a class="vbo-goback-link vbo-pref-color-btn-secondary" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=showprc&checkin='.$first.'&checkout='.$second.'&roomsnum='.$roomsnum.'&days='.$days.($is_package === true ? '&pkg_id='.$pkg['id'] : '').$peopleforlink.$roomoptforlink.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBBACK'); ?></a>
			</div>
		</div>
		
		<?php
		if (!empty ($pitemid)) {
			?>
			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
			<?php
		}
		/**
		 * Keep any previously selected room index through the interactive geomap, if any.
		 */
		foreach ($this->roomindex as $rindex) {
			?>
			<input type="hidden" name="roomindex[]" value="<?php echo $rindex; ?>"/>
			<?php
		}
		?>
	</form>

	<div class="vbo-alert-container-confirm" id="vbo-alert-container-confirm" style="display: none;">
		<span class="vbo-alert-close" onclick="vbHideAlertFillin();">&times;</span><?php echo JText::translate('VBOALERTFILLINALLF'); ?>
	</div>

<?php
if ($channel_disclaimer === true) {
	?>
	<script type="text/javascript">
	function vbCloseDisclaimerBox() {
		return (elem=document.getElementById("vb_ch_disclaimer_box")).parentNode.removeChild(elem);
	}
	</script>
	<div class="vb_ch_disclaimer_box" id="vb_ch_disclaimer_box">
		<div class="vb_ch_disclaimer_box_inner">
			<div class="vb_ch_disclaimer_text">
				<?php echo JText::translate($vcmchanneldata['disclaimer']); ?>
			</div>
			<div class="vb_ch_disclaimer_closebtn">
				<a href="javascript: void(0);" onclick="vbCloseDisclaimerBox();"><?php echo JText::translate('VBOKDISCLAIMER'); ?></a>
			</div>
		</div>
	</div>
	<?php
}
