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

$session = JFactory::getSession();
$search_tpl = VikBooking::searchResultsTmpl();
$vat_included = VikBooking::ivaInclusa();
$tax_summary = !$vat_included && VikBooking::showTaxOnSummaryOnly() ? true : false;
$is_package = false;
//packages may skip some room options
$skip_all_opt = false;
$only_forced_opt = false;
if (is_array($this->pkg) && count($this->pkg) > 0) {
	$skip_all_opt = $this->pkg['showoptions'] == 3 ? true : $skip_all_opt;
	$only_forced_opt = $this->pkg['showoptions'] == 2 ? true : $only_forced_opt;
	$is_package = true;
}
//
$deftar_costs = [];
$deftar_basecosts = [];
$children_agepcent = false;
$pitemid = VikRequest::getInt('Itemid', '', 'request');
// single category filter
$pcategories = VikRequest::getString('categories', '', 'request');
// multiple category filters
$pcategory_ids = VikRequest::getVar('category_ids', []);
// pre-defined children age
$children_age = VikRequest::getVar('children_age', []);

$infocheckin = getdate($this->checkin);
$infocheckout = getdate($this->checkout);

$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();

$checkinforlink = date($df, $this->checkin);
$checkoutforlink = date($df, $this->checkout);

$discl = VikBooking::getDisclaimer();
$currencysymb = VikBooking::getCurrencySymb();
$showchildren = VikBooking::showChildrenFront();
$totadults = 0;
$totchildren = 0;

$peopleforlink = '';
foreach ($this->arrpeople as $aduchild) {
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

$document = JFactory::getDocument();
//load jQuery
if (VikBooking::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}

foreach ($this->rooms as $num => $r) {
	if (strlen($r['moreimgs']) > 0) {
		$document->addStyleSheet(VBO_SITE_URI.'resources/vikfxgallery.css');
		JHtml::fetch('script', VBO_SITE_URI.'resources/vikfxgallery.js');
		break;
	}
}

$gallery_data = [];
foreach ($this->rooms as $num => $r) {
	if (empty($r['moreimgs'])) {
		continue;
	}
	$gallery_data[$num] = [];
	$moreimages = explode(';;', $r['moreimgs']);
	$imgcaptions = json_decode($r['imgcaptions'], true);
	$usecaptions = is_array($imgcaptions);
	foreach ($moreimages as $iind => $mimg) {
		if (empty($mimg)) {
			continue;
		}
		$img_alt = $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : substr($mimg, 0, strpos($mimg, '.'));
		array_push($gallery_data[$num], array(
			'big' => VBO_SITE_URI . 'resources/uploads/big_' . $mimg,
			'thumb' => VBO_SITE_URI . 'resources/uploads/thumb_' . $mimg,
			'alt' => $img_alt,
			'caption' => $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : "",
		));
	}
}
$vikfx = '
jQuery(document).ready(function() {
';
foreach ($gallery_data as $num => $gallery) {
	$vikfx .= '
	window["vikfxgallery'.$num.'"] = jQuery("#vikfx-gallery'.$num.' a").vikFxGallery();
	jQuery("#vikfx-gallery-previous-image'.$num.', #vikfx-gallery-next-image'.$num.'").click(function() {
		if (typeof window["vikfxgallery'.$num.'"] !== "undefined") {
			window["vikfxgallery'.$num.'"].open();
		}
	});';
}
$vikfx .= '
});
';
if (count($gallery_data)) {
	$document->addScriptDeclaration($vikfx);
}

$mod_rooms_data = [];
if (count($this->mod_booking)) {
	// booking modification
	$mod_rooms_data = VikBooking::loadOrdersRoomsData($this->mod_booking['id']);
	?>
<div class="vbo-booking-modification-helper">
	<div class="vbo-booking-modification-helper-inner">
		<div class="vbo-booking-modification-msg">
			<span><?php echo JText::translate('VBOMODBOOKHELPSHOWPRC'); ?></span>
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
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=packagedetails&pkgid='.$this->pkg['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBSTEPROOMSELECTION'); ?></a></li>
		<?php
	} else {
		if ($this->roomsnum < 2 && (int)$session->get('vboSearchRoomId', 0) == (int)$this->rooms[1]['id']) {
			// we started the search from the room details page, so the dates link should point there
			$dateslink = JRoute::rewrite('index.php?option=com_vikbooking&view=roomdetails&roomid='.$this->rooms[1]['id'].'&checkin='.$this->checkin.'&checkout='.$this->checkout.(!empty($pitemid) ? '&Itemid='.$pitemid : ''));
		} else {
			// link to general search form
			$dateslink = JRoute::rewrite('index.php?option=com_vikbooking&view=vikbooking&checkin='.$this->checkin.'&checkout='.$this->checkout.(!empty($pitemid) ? '&Itemid='.$pitemid : ''));
		}
		?>
		<li class="vbo-step vbo-step-complete"><a href="<?php echo $dateslink; ?>"><?php echo JText::translate('VBSTEPDATES'); ?></a></li>
		<li class="vbo-step vbo-step-complete"><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=search&checkindate='.urlencode($checkinforlink).'&checkoutdate='.urlencode($checkoutforlink).'&roomsnum='.$this->roomsnum.$peopleforlink.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBSTEPROOMSELECTION'); ?></a></li>
		<?php
	}
	?>
		<li class="vbo-step vbo-step-current"><span><?php echo JText::translate('VBSTEPOPTIONS'); ?></span></li>
		<li class="vbo-step vbo-step-next"><span><?php echo JText::translate('VBSTEPCONFIRM'); ?></span></li>
	</ol>
</div>

<div class="vbo-showprc-head-wrapper">
	<div class="vbo-results-head vbo-results-head-showprc">
		<div class="vbo-results-nights">
			<?php VikBookingIcons::e((!empty($this->split_stay) ? 'random' : 'calendar'), 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBSEARCHRESNIGHTS'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $this->days; ?></span>
			</div>
		</div>
	<?php
	if ($this->roomsnum > 1) {
		?>
		<div class="vbo-results-numrooms">
			<?php VikBookingIcons::e('bed', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBSEARCHRESROOMS'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $this->roomsnum; ?></span>
			</div>
		</div>
		<?php
	}
	?>
		<div class="vbo-results-numadults">
			<?php VikBookingIcons::e('male', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBSEARCHRESADULTS'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $totadults; ?></span>
			</div>
		</div>
	<?php
	if ($showchildren && $totchildren > 0) {
		?>
		<div class="vbo-results-numchildren">
			<?php VikBookingIcons::e('child', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBSEARCHRESCHILDREN'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo $totchildren; ?></span>
			</div>
		</div>
		<?php
	}
	?>
		<div class="vbcheckinroom">
			<?php VikBookingIcons::e('sign-in', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBCHECKINONTHE'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo JText::sprintf('VBCHECKINOUTOF', VikBooking::sayDayMonth($infocheckin['mday']), VikBooking::sayMonth($infocheckin['mon'])); ?></span>
			</div>
		</div>
		<div class="vbcheckoutroom">
			<?php VikBookingIcons::e('sign-out', 'vbo-pref-color-text'); ?>
			<div class="vbo-results-head-det">
				<span class="vbo-results-head-det-lbl"><?php echo JText::translate('VBCHECKOUTONTHE'); ?></span>
				<span class="vbo-results-head-det-val"><?php echo JText::sprintf('VBCHECKINOUTOF', VikBooking::sayDayMonth($infocheckout['mday']), VikBooking::sayMonth($infocheckout['mon'])); ?></span>
			</div>
		</div>
	</div>

	<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=oconfirm'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
	<div class="vbo-showprc-wrapper vbo-showprc-wrapper-<?php echo $search_tpl; ?>">
	<?php
	foreach ($this->rooms as $num => $r) {
		$ind = $num - 1;

		// room amenities
		$carats = VikBooking::getRoomCaratOriz($r['idcarat'], $this->vbo_tn);

		// determine values to be considered
		$room_nights   = $this->days;
		$room_checkin  = $this->checkin;
		$room_checkout = $this->checkout;
		$is_split_room = false;
		if (!empty($this->split_stay) && isset($this->split_stay[$ind]) && $this->split_stay[$ind]['idroom'] == $r['id']) {
			$room_nights   = $this->split_stay[$ind]['nights'];
			$room_checkin  = $this->split_stay[$ind]['checkin_ts'];
			$room_checkout = $this->split_stay[$ind]['checkout_ts'];
			$is_split_room = true;
		}
		
		// room options
		$optionals = [];
		if (!empty($r['idopt'])) {
			$optionals = VikBooking::getRoomOptionals($r['idopt'], $this->vbo_tn);
			VikBooking::filterOptionalsByDate($optionals, $room_checkin, $room_checkout);
			VikBooking::filterOptionalsByParty($optionals, $this->arrpeople[$num]['adults'], $this->arrpeople[$num]['children']);
		}
		?>
		<div class="room_container">
			<div class="vbo-showprc-room-head">
			<?php
			if ($this->roomsnum > 1) {
				?>
				<div class="vbshowprcroomnum">
				<?php
				if ($is_split_room) {
					?>
					<span class="vbo-showprc-roomnum-icn"><?php VikBookingIcons::e('random'); ?></span>
					<?php
				}
				?>
					<span class="vbo-showprc-roomnum-num"><?php echo JText::translate('VBSEARCHROOMNUM') . ' ' . $num; ?></span>
				</div>
				<?php
			}
			?>
				<div class="vbo-showprc-staydetails">
					<div class="vbo-showprc-staydetails-party">
						<?php VikBookingIcons::e('users', 'vbo-pref-color-text'); ?> 
						<?php echo $this->arrpeople[$num]['adults']; ?> <?php echo ($this->arrpeople[$num]['adults'] == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?><?php echo ($showchildren && $this->arrpeople[$num]['children'] > 0 ? ", ".$this->arrpeople[$num]['children']." ".($this->arrpeople[$num]['children'] == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN')) : ""); ?>
					</div>
				<?php
				if ($is_split_room) {
					?>
					<div class="vbo-showprc-staydetails-splitstay vbo-showprc-staydetails-nights">
						<?php VikBookingIcons::e('moon', 'vbo-pref-color-text'); ?> 
						<?php echo $room_nights . ' ' . ($room_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?>
					</div>
					<div class="vbo-showprc-staydetails-splitstay vbo-showprc-staydetails-checkin">
						<?php VikBookingIcons::e('plane-arrival', 'vbo-pref-color-text'); ?> 
						<?php echo date(str_replace("/", $datesep, $df), $room_checkin); ?>
					</div>
					<div class="vbo-showprc-staydetails-splitstay vbo-showprc-staydetails-checkout">
						<?php VikBookingIcons::e('plane-departure', 'vbo-pref-color-text'); ?> 
						<?php echo date(str_replace("/", $datesep, $df), $room_checkout); ?>
					</div>
					<?php
				}
				?>
				</div>
			</div>
			<div class="vbo-showprc-room-block<?php echo $this->roomsnum > 1 ? ' vbo-showprc-room-block-multi' : ''; ?>">
				<div class="vbo-showprc-roomname">
					<?php
					$room_name = $r['name'];
					// check if a sub-unit was requested
					if (count($this->roomindex) && !empty($this->roomindex[($num - 1)])) {
						$room_feature = VikBooking::getRoomUnitDistinctiveFeature($r['params'], $this->roomindex[($num - 1)]);
						if ($room_feature !== false) {
							$room_name .= ' - ' . $room_feature[0] . ' ' . $room_feature[1];
						}
					}
					?>
					<h3><?php echo $room_name; ?></h3>
				</div>
				<div class="vbroomimgdesc">
					<div class="vikfx-gallery-container vikfx-showprc-gallery-container">
						<div class="vikfx-gallery-fade-container">
						<?php
						if (!empty($r['img']) && is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $r['img'])) {
							?>
							<img alt="<?php echo htmlspecialchars($r['name']); ?>" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $r['img']; ?>" class="vikfx-gallery-image vblistimg"/>
							<?php
						}
						if (isset($gallery_data[$num]) && count($gallery_data[$num])) {
							?>
							<div class="vikfx-gallery-navigation-controls">
								<div class="vikfx-gallery-navigation-controls-prevnext">
									<a href="javascript: void(0);" class="vikfx-gallery-previous-image" id="vikfx-gallery-previous-image<?php echo $num; ?>"><?php VikBookingIcons::e('chevron-left'); ?></a>
									<a href="javascript: void(0);" class="vikfx-gallery-next-image" id="vikfx-gallery-next-image<?php echo $num; ?>"><?php VikBookingIcons::e('chevron-right'); ?></a>
								</div>
							</div>
							<?php
						}
						?>
						</div>
				<?php
				if (isset($gallery_data[$num]) && count($gallery_data[$num])) {
					?>
						<div class="vikfx-gallery" id="vikfx-gallery<?php echo $num; ?>">
					<?php
					foreach ($gallery_data[$num] as $mimg) {
						?>
							<a href="<?php echo $mimg['big']; ?>">
								<img src="<?php echo $mimg['thumb']; ?>" alt="<?php echo htmlspecialchars($mimg['alt']); ?>" title="<?php echo htmlspecialchars($mimg['caption']); ?>"/>
							</a>
						<?php
					}
					?>
						</div>
				<?php
				}
				?>
					</div>
					<div class="room_description_box">
					<?php
					/**
					 * @wponly 		we try to parse any shortcode inside the HTML description of the room.
					 * @joomlaonly 	trigger onContentPrepare event for plugins.
					 */
					if (VBOPlatformDetection::isWordPress()) {
						echo do_shortcode(wpautop($r['info']));
					} else {
						JPluginHelper::importPlugin('content');
						$myItem = JTable::getInstance('content');
						$myItem->text = $r['info'];
						$objparams = [];
						if (class_exists('JEventDispatcher')) {
							$dispatcher = JEventDispatcher::getInstance();
							$dispatcher->trigger('onContentPrepare', array('com_vikbooking.roomdetails', &$myItem, &$objparams, 0));
						} else {
							/**
							 * @joomla4only
							 */
							$dispatcher = JFactory::getApplication();
							if (method_exists($dispatcher, 'triggerEvent')) {
								$dispatcher->triggerEvent('onContentPrepare', array('com_vikbooking.roomdetails', &$myItem, &$objparams, 0));
							}
						}
						$r['info'] = $myItem->text;
						echo $r['info'];
					}
					?>
					</div>
				</div>
			<?php 
			if (!empty($carats)) {
				?>
				<div class="room_carats">
					<h4><?php echo JText::translate('VBCHARACTERISTICS'); ?></h4>
					<?php echo $carats; ?>
				</div>
				<?php
			}
			?>
				<div class="room_prices">
					<h4><?php echo JText::translate('VBPRICE'); ?></h4>
					<div class="vbo-showprc-rateplans-wrapper">
						<div class="vbo-showprc-pricetable">
			<?php
			$rate_plan_id = VikRequest::getInt('rate_plan_id', 0, 'request');
			foreach ($this->tars[$num] as $k => $t) {
				if ($is_package === true) {
					// do not print the regular prices if a package was requested.
					break;
				}
				$priceinfo = VikBooking::getPriceInfo($t['idprice'], $this->vbo_tn);
				$priceinfostr = '';
				$cancpolicy = '';
				//
				$priceinfostr = '<div class="vbpricedetails">';
				if ($priceinfo['breakfast_included'] == 1) {
					$priceinfostr .= '<span class="vbprice_breakfast">'.JText::translate('VBBREAKFASTINCLUDED').'</span>';
				}
				if ($priceinfo['free_cancellation'] == 1) {
					if ((int)$priceinfo['canc_deadline'] > 0) {
						$priceinfostr .= '<span class="vbprice_freecanc">'.JText::sprintf('VBFREECANCELLATIONWITHIN', $priceinfo['canc_deadline']).'</span>';
					} else {
						$priceinfostr .= '<span class="vbprice_freecanc">'.JText::translate('VBFREECANCELLATION').'</span>';
					}
					if (!empty($priceinfo['canc_policy'])) {
						$priceinfostr .= '<span class="vbo-cancpolicy-trig" onclick="var cancelem=document.getElementById(\'vbo-cancpolicy-cont'.$priceinfo['id'].'\').style.display;if(cancelem == \'block\'){document.getElementById(\'vbo-cancpolicy-cont'.$priceinfo['id'].'\').style.display = \'none\';}else{document.getElementById(\'vbo-cancpolicy-cont'.$priceinfo['id'].'\').style.display = \'block\';}"><i class="'.VikBookingIcons::i('question-circle').'"></i></span>';
						$cancpolicy = '<div class="vbo-cancpolicy-cont" id="vbo-cancpolicy-cont'.$priceinfo['id'].'" style="display: none;">'.(strpos($priceinfo['canc_policy'], '<') !== false ? $priceinfo['canc_policy'] : nl2br($priceinfo['canc_policy'])).'</div>';
					}
				} else {
					$priceinfostr .= '<span class="vbprice_freecanc vbprice_freecanc_no">'.JText::translate('VBONONREFUNDRATE').'</span>';
				}
				$priceinfostr .= '</div>';
				$priceinfostr .= $cancpolicy;
				//
				$rplan_cost = ($tax_summary ? $t['cost'] : VikBooking::sayCostPlusIva($t['cost'], $t['idprice']));
				$room_basecost = isset($t['room_base_cost']) ? $t['room_base_cost'] : $t['cost'];
				$rplan_basecost = ($tax_summary ? $room_basecost : VikBooking::sayCostPlusIva($room_basecost, $t['idprice']));
				if (!array_key_exists($num, $deftar_costs)) {
					$deftar_costs[$num] = $rplan_cost;
				}
				if (!array_key_exists($num, $deftar_basecosts)) {
					$deftar_basecosts[$num] = $rplan_basecost;
				}
				?>
							<div class="vbo-showprc-price-entry">
								<div class="vbo-showprc-price-entry-radio">
									<input type="radio" class="vbo-radio" data-roomnum="<?php echo $num; ?>" data-ratecost="<?php echo $rplan_cost; ?>" data-ratecostbase="<?php echo $rplan_basecost; ?>" name="priceid<?php echo $num; ?>" id="pid<?php echo $num.$t['idprice']; ?>" value="<?php echo $t['idprice']; ?>"<?php echo (($k == 0 && empty($rate_plan_id)) || $rate_plan_id == $t['idprice'] ? " checked=\"checked\"" : ""); ?>/>
								</div>
								<div class="vbo-showprc-price-entry-rateplan">
									<label for="pid<?php echo $num.$t['idprice']; ?>"><?php echo $priceinfo['name']; ?></label>
								<?php
								if (strlen($t['attrdata'])) {
									?>
									<div class="vbo-showprc-price-entry-rateattribute">
										<span><?php echo VikBooking::getPriceAttr($t['idprice'], $this->vbo_tn); ?></span>
										<?php echo $t['attrdata']; ?>
									</div>
									<?php
								}
								?>
									<?php echo $priceinfostr; ?>
								</div>
								<div class="vbo-showprc-price-entry-cost vbo-pref-color-text">
									<span class="room_cost">
										<span class="vbo_currency"><?php echo $currencysymb; ?></span>
										<span class="vbo_price"><?php echo VikBooking::numberFormat($rplan_cost); ?></span>
									</span>
							<?php
							if (isset($t['promotion']) && isset($t['promotion']['discount'])) {
								if ($t['promotion']['discount']['pcent']) {
									/**
									 * Do not make an upper-cent operation, but rather calculate the original price proportionally:
									 * final price : (100 - discount amount) = x : 100
									 */
									$prev_amount = $rplan_cost * 100 / (100 - $t['promotion']['discount']['amount']);
								} else {
									$prev_amount = $rplan_cost + $t['promotion']['discount']['amount'];
								}
								if ($prev_amount > 0) {
									?>
									<div class="vbo-room-result-price-before-discount">
										<span class="room_cost">
											<span class="vbo_currency"><?php echo $currencysymb; ?></span> 
											<span class="vbo_price"><?php echo VikBooking::numberFormat($prev_amount); ?></span>
										</span>
									</div>
									<?php
									if ($t['promotion']['discount']['pcent']) {
										// hide by default the DIV containing the percent of discount
										?>
									<div class="vbo-room-result-price-before-discount-percent" style="display: none;">
										<span class="room_cost">
											<span><?php echo '-' . (float)$t['promotion']['discount']['amount'] . ' %'; ?></span>
										</span>
									</div>
										<?php
									}
								}
							}
							?>
								</div>
							</div>
				<?php
			}
			if ($is_package === true) {
				$pkg_cost = $this->pkg['pernight_total'] == 1 ? ($this->pkg['cost'] * $this->days) : $this->pkg['cost'];
				$base_pkg_cost = ($tax_summary ? $pkg_cost : VikBooking::sayPackagePlusIva($pkg_cost, $this->pkg['idiva']));
				if (!array_key_exists($num, $deftar_costs)) {
					$deftar_costs[$num] = $base_pkg_cost;
				}
				if (!array_key_exists($num, $deftar_basecosts)) {
					$deftar_basecosts[$num] = $base_pkg_cost;
				}
				$pkg_cost = $this->pkg['perperson'] == 1 ? ($pkg_cost * ($this->arrpeople[$num]['adults'] > 0 ? $this->arrpeople[$num]['adults'] : 1)) : $pkg_cost;
				?>
							<div class="vbo-showprc-price-entry vbo-showprc-price-pkg">
								<div class="vbo-showprc-price-entry-radio">
									<input type="radio" class="vbo-radio" data-roomnum="<?php echo $num; ?>" data-ratecost="<?php echo $base_pkg_cost; ?>" data-ratecostbase="<?php echo $base_pkg_cost; ?>" name="priceid<?php echo $num; ?>" id="pid<?php echo $num.$this->pkg['id']; ?>" value="<?php echo $this->pkg['id']; ?>" checked="checked"/>
								</div>
								<div class="vbo-showprc-price-entry-rateplan">
									<label for="pid<?php echo $num.$this->pkg['id']; ?>"><?php echo $this->pkg['name']; ?></label>
								</div>
								<div class="vbo-showprc-price-entry-cost vbo-pref-color-text">
									<span class="vbo_currency"><?php echo $currencysymb; ?></span>
									<span class="vbo_price"><?php echo ($tax_summary ? VikBooking::numberFormat($pkg_cost) : VikBooking::numberFormat(VikBooking::sayPackagePlusIva($pkg_cost, $this->pkg['idiva']))); ?></span>
								</div>
							</div>
				<?php
			}
			?>
						</div>
			<?php
			// BEGIN: Children Age Intervals
			if (!empty($r['idopt']) && !empty($optionals)) {
				list($optionals, $ageintervals) = VikBooking::loadOptionAgeIntervals($optionals, $this->arrpeople[$num]['adults'], $this->arrpeople[$num]['children']);
				if ($ageintervals && $this->arrpeople[$num]['children'] > 0 && $skip_all_opt !== true) {
					?>
						<div class="vbageintervals">
							<?php
							/**
							 * @wponly 	we need to let WordPress parse the paragraphs in the message.
							 */
							if (VBOPlatformDetection::isWordPress()) {
								echo wpautop($ageintervals['descr']);
							} else {
								echo $ageintervals['descr'];
							}
							?>
							<div class="vbo-showprc-child-fees-wrapper">
							<?php
							for ($ch = 1; $ch <= $this->arrpeople[$num]['children']; $ch++) {
								/**
								 * Age intervals may be overridden per child number.
								 * 
								 * @since 	1.13.5
								 */
								$intervals = explode(';;', (isset($ageintervals['ageintervals_child' . $ch]) ? $ageintervals['ageintervals_child' . $ch] : $ageintervals['ageintervals']));
								//
								?>
								<div class="vbo-showprc-child-fee">
									<span><?php echo JText::translate('VBSEARCHRESCHILD') . ' #' . $ch; ?></span>
									<select name="optid<?php echo $num . $ageintervals['id']; ?>[]">
									<?php
									foreach ($intervals as $kintv => $intv) {
										if (empty($intv)) {
											continue;
										}
										$intvparts = explode('_', $intv);
										$intvparts[2] = intval($ageintervals['perday']) == 1 ? ($intvparts[2] * $this->tars[$num][0]['days']) : $intvparts[2];
										if (!empty($ageintervals['maxprice']) && $ageintervals['maxprice'] > 0 && $intvparts[2] > $ageintervals['maxprice']) {
											$intvparts[2] = $ageintervals['maxprice'];
										}
										$intvparts[2] = $tax_summary ? $intvparts[2] : VikBooking::sayOptionalsPlusIva($intvparts[2], $ageintervals['idiva']);
										$pricestr = floatval($intvparts[2]) >= 0 ? '+ '.VikBooking::numberFormat($intvparts[2]) : '- '.VikBooking::numberFormat($intvparts[2]);
										$pcent_interval = false;
										$opt_suffix = '';
										if (array_key_exists(3, $intvparts) && strpos($intvparts[3], '%') !== false && array_key_exists($num, $deftar_costs) && floatval($intvparts[2]) >= 0) {
											$children_agepcent = true;
											$pcent_interval = true;
											if (strpos($intvparts[3], '%b') !== false) {
												// VBO 1.10 - percentage value of room base cost
												$opt_suffix = 'base';
												$pcent_cost = $deftar_basecosts[$num] * (float)$intvparts[2] / 100;
												$pricestr = '+ '.VikBooking::numberFormat($pcent_cost);
											} else {
												// percentage value of adults tariff
												$pcent_cost = $deftar_costs[$num] * (float)$intvparts[2] / 100;
												$pricestr = '+ '.VikBooking::numberFormat($pcent_cost);
											}
										}
										/**
										 * Check if this age interval should be pre-selected for the given children age (if any).
										 * 
										 * @since 	1.15.0 (J) - 1.5.0 (WP)
										 */
										$age_intval_selected = false;
										if (is_array($children_age) && isset($children_age[($ch - 1)]) && is_numeric($children_age[($ch - 1)])) {
											$age_intval_selected = ($intvparts[0] <= $children_age[($ch - 1)] && $intvparts[1] >= $children_age[($ch - 1)]);
										}
										?>
										<option value="<?php echo ($kintv + 1); ?>"<?php echo ($age_intval_selected ? ' selected="selected"' : '').($pcent_interval === true ? ' data-pcentintvroom="'.$num.'" data-ratetype="'.$opt_suffix.'" data-pcentintv="'.floatval($intvparts[2]).'" data-ageintv="'.$intvparts[0].' - '.$intvparts[1].'"' : ''); ?>><?php echo "{$intvparts[0]} - {$intvparts[1]} ({$pricestr} {$currencysymb})"; ?></option>
										<?php
									}
									?>
									</select>
								</div>
								<?php
							}
							?>
							</div>
						</div>
			<?php
				}
			}
			// END: Children Age Intervals
			?>
					</div>
				</div>
		
		<?php
		if (!empty($r['idopt']) && !empty($optionals) && $skip_all_opt !== true) {
			$optechoed = 0;
			$titlewritten = false;
			$arrforcesummary = [];
			foreach ($optionals as $k => $o) {
				$showoptional = true;
				if (intval($o['ifchildren']) == 1 && $this->arrpeople[$num]['children'] < 1) {
					$showoptional = false;
				}
				if ($only_forced_opt === true && intval($o['forcesel']) != 1) {
					$showoptional = false;
				}
				if ($showoptional !== true) {
					continue;
				}
				// VBO 1.11 - options percentage cost of the room total fee
				$o['cost'] = (int)$o['pcentroom'] ? ($deftar_costs[$num] * $o['cost'] / 100) : $o['cost'];
				//
				$optcost = intval($o['perday']) == 1 ? ($o['cost'] * $this->tars[$num][0]['days']) : $o['cost'];
				if (!empty($o['maxprice']) && $o['maxprice'] > 0 && $optcost > $o['maxprice']) {
					$optcost = $o['maxprice'];
				}
				if ($o['perperson'] == 1) {
					$optcost = $optcost * $this->arrpeople[$num]['adults'];
				}
				$optcost = $optcost * 1;

				$forcesummary = false;
				if (intval($o['forcesel']) == 1) {
					$forcedquan = 1;
					$forceperday = false;
					$forceperchild = false;
					if (strlen($o['forceval']) > 0) {
						$forceparts = explode("-", $o['forceval']);
						$forcedquan = intval($forceparts[0]);
						$forceperday = intval($forceparts[1]) == 1 ? true : false;
						$forceperchild = intval($forceparts[2]) == 1 ? true : false;
						$forcesummary = intval($forceparts[3]) == 1 ? true : false;
					}
					$setoptquan = $forceperday == true ? ($forcedquan * $this->tars[$num][0]['days']) : $forcedquan;
					$setoptquan = $forceperchild == true ? ($setoptquan * $this->arrpeople[$num]['children']) : $setoptquan;
					if ($forcesummary === true) {
						$optquaninp = "<input type=\"hidden\" name=\"optid".$num.$o['id']."\" value=\"".$setoptquan."\"/>";
						$arrforcesummary[] = $optquaninp;
					} else {
						if (intval($o['hmany']) == 1) {
							$optquaninp = "<input type=\"hidden\" name=\"optid".$num.$o['id']."\" value=\"".$setoptquan."\"/><span class=\"vboptionforcequant\"><small>x</small> ".$setoptquan."</span>";
						} else {
							$optquaninp = "<input type=\"hidden\" name=\"optid".$num.$o['id']."\" value=\"".$setoptquan."\"/><span class=\"vboptionforcequant\"><small>x</small> ".$setoptquan."</span>";
						}
					}
				} else {
					/**
					 * During a booking modification, we pre-select the previous options for this party, if any.
					 * 
					 * @since 	1.15.0 (J) - 1.5.0 (WP)
					 */
					$opt_selected = false;
					if (count($mod_rooms_data) && isset($mod_rooms_data[($num - 1)]) && !empty($mod_rooms_data[($num - 1)]['optionals'])) {
						$prev_party_opts = explode(';', $mod_rooms_data[($num - 1)]['optionals']);
						foreach ($prev_party_opts as $prev_party_opt) {
							if (empty($prev_party_opt)) {
								continue;
							}
							$prev_party_opt_parts = explode(':', $prev_party_opt);
							if (count($prev_party_opt_parts) < 2 || empty($prev_party_opt_parts[0]) || $prev_party_opt_parts[0] != $o['id']) {
								continue;
							}
							$opt_sep = strpos($prev_party_opt_parts[1], '-');
							$opt_prev_quant = $opt_sep !== false ? substr($prev_party_opt_parts[1], 0, $opt_sep) : $prev_party_opt_parts[1];
							$opt_selected = $opt_prev_quant > 0 ? $opt_prev_quant : $opt_selected;
						}
					}

					if (intval($o['hmany']) == 1) {
						if (intval($o['maxquant']) > 0) {
							$optquaninp = "<select name=\"optid".$num.$o['id']."\">\n";
							for ($ojj = 0; $ojj <= intval($o['maxquant']); $ojj++) {
								$optquaninp .= "<option value=\"".$ojj."\"" . ($opt_selected !== false && $opt_selected == $ojj ? ' selected="selected"' : '') . ">".$ojj."</option>\n";
							}
							$optquaninp .= "</select>\n";
						} else {
							$optquaninp = "<input type=\"number\" min=\"0\" step=\"any\" name=\"optid".$num.$o['id']."\" value=\"" . ($opt_selected !== false ? $opt_selected : '0') . "\"/>";
						}
					} else {
						$optquaninp = "<input type=\"checkbox\" name=\"optid".$num.$o['id']."\" value=\"1\"" . ($opt_selected !== false ? ' checked="checked"' : '') . "/>";
					}
				}

				if ($forcesummary === false) {
					if (!$titlewritten) {
						$titlewritten = true;
						?>
				<div class="room_options">
					<h4><?php echo JText::translate('VBACCOPZ'); ?></h4>
					<div class="vbo-showprc-optionstable">
						<?php
					}
					$optechoed++;
					?>
						<div class="vbo-showprc-option-entry">
							<div class="vbo-showprc-option-entry-img">
								<?php echo (!empty($o['img']) ? '<img class="maxthirty" src="'.VBO_SITE_URI.'resources/uploads/'.$o['img'].'"/>' : '&nbsp;'); ?>
							</div>
							<div class="vbo-showprc-option-entry-name">
								<?php echo $o['name']; ?>
							<?php
							if (strlen(strip_tags( trim($o['descr'] )))) {
								?>
								<div class="vbo-showprc-option-entry-descr">
									<?php
									/**
									 * @wponly 	we need to let WordPress parse the paragraphs in the message.
									 */
									if (VBOPlatformDetection::isWordPress()) {
										echo wpautop($o['descr']);
									} else {
										echo $o['descr'];
									}
									?>
								</div>
								<?php
							}
							?>
							</div>
							<div class="vbo-showprc-option-entry-cost">
								<span class="vbo_currency"><?php echo $currencysymb; ?></span>
								<span class="vbo_price"><?php echo $tax_summary ? VikBooking::numberFormat($optcost) : VikBooking::numberFormat(VikBooking::sayOptionalsPlusIva($optcost, $o['idiva'])); ?></span>
							</div>
							<div class="vbo-showprc-option-entry-input">
								<?php echo $optquaninp; ?>
							</div>
						</div>
						<?php
				}
			}
			if ($optechoed > 0) {
			?>
					</div>
			<?php
			}
			if (count($arrforcesummary) > 0) {
				echo implode("\n", $arrforcesummary);
			}
			if ($optechoed > 0) {
			?>
				</div>
			<?php
			}
		}
		?>
			</div>

		</div>
		<input type="hidden" name="roomid[]" value="<?php echo $r['id']; ?>"/>
		<?php
	}
	?>
	</div>
	<?php

	foreach ($this->arrpeople as $indroom => $aduch) {
		?>
		<input type="hidden" name="adults[]" value="<?php echo $aduch['adults']; ?>"/>
		<?php
		if ($showchildren) {
			?>
			<input type="hidden" name="children[]" value="<?php echo $aduch['children']; ?>"/>
			<?php	
		}
	}
		?>
		<input type="hidden" id="roomsnum" name="roomsnum" value="<?php echo $this->roomsnum; ?>"/>
		<input type="hidden" name="days" value="<?php echo $this->days; ?>"/>
		<input type="hidden" name="checkin" value="<?php echo $this->checkin; ?>"/>
		<input type="hidden" name="checkout" value="<?php echo $this->checkout; ?>"/>
		<input type="hidden" name="categories" value="<?php echo $pcategories; ?>"/>
		<input type="hidden" name="task" value="oconfirm"/>
		<?php
		if (is_array($pcategory_ids) && count($pcategory_ids)) {
			foreach ($pcategory_ids as $pcid) {
				?>
			<input type="hidden" name="category_ids[]" value="<?php echo $pcid; ?>"/>
				<?php
			}
		}
		if (!empty($pitemid)) {
			?>
			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>"/>
			<?php
		}
		if ($is_package === true) {
			?>
			<input type="hidden" name="pkg_id" value="<?php echo $this->pkg['id']; ?>"/>
			<?php
		}
		if ($is_package === true && !empty($this->pkg['benefits'])) {
		?>
		<div class="vbo-pkg-showprc-benefits"><?php echo $this->pkg['benefits']; ?></div>
		<?php
		}
		if (strlen($discl)) {
		?>
		<div class="room_disclaimer"><?php echo $discl; ?></div>
		<?php
		}
		if ($is_package === true && !empty($this->pkg['conditions'])) {
		?>
		<div class="room_disclaimer vbo-pkg-showprc-conditions"><?php echo $this->pkg['conditions']; ?></div>
		<?php
		}
		?>
		<div class="room_buttons_box">
			<div class="goback">
			<?php
			if ($is_package === true) {
				?>
				<a class="vbo-goback-link vbo-pref-color-btn-secondary" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=packagedetails&pkgid='.$this->pkg['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBBACK'); ?></a>
				<?php
			} else {
				?>
				<a class="vbo-goback-link vbo-pref-color-btn-secondary" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=search&checkindate='.urlencode($checkinforlink).'&checkoutdate='.urlencode($checkoutforlink).'&roomsnum='.$this->roomsnum.$peopleforlink.(!empty($pitemid) ? '&Itemid='.$pitemid : ''), false); ?>"><?php echo JText::translate('VBBACK'); ?></a>
				<?php
			}
			?>
			</div>
			<input type="submit" name="goon" value="<?php echo JText::translate('VBBOOKNOW'); ?>" class="btn booknow vbo-pref-color-btn"/>
		</div>
	<?php
	/**
	 * Keep any previously selected room index through the interactive geomap, if any.
	 */
	foreach ($this->roomindex as $rindex) {
		?>
		<input type="hidden" name="roomindex[]" value="<?php echo $rindex; ?>"/>
		<?php
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
	</form>
</div>
<?php
if ($children_agepcent === true) {
	$formatvals = VikBooking::getNumberFormatData();
	$formatparts = explode(':', $formatvals);
	?>
<script type="text/javascript">
Number.prototype.numFormat = function(c, d, t) {
	var n = this, 
		c = isNaN(c = Math.abs(c)) ? 2 : c, 
		d = d == undefined ? "." : d, 
		t = t == undefined ? "," : t, 
		i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", 
		j = (j = i.length) > 3 ? j % 3 : 0;
	return (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

jQuery(function() {
	jQuery(".vbo-radio").change(function() {
		var rnum = jQuery(this).attr("data-roomnum");
		var rcost = parseFloat(jQuery(this).attr("data-ratecost"));
		var rcostbase = parseFloat(jQuery(this).attr("data-ratecostbase"));
		if (!isNaN(rcost) && jQuery("option[data-pcentintvroom='"+rnum+"']").length > 0) {
			jQuery("option[data-pcentintvroom='"+rnum+"']").each(function() {
				var usecost = rcost;
				var ratetype = jQuery(this).attr('data-ratetype');
				if (ratetype == 'base') {
					usecost = rcostbase;
				}
				var pcentval = parseFloat(jQuery(this).attr("data-pcentintv"));
				var agesval = jQuery(this).attr("data-ageintv");
				if (!isNaN(pcentval) && agesval.length) {
					var intvcost = usecost * pcentval / 100;
					jQuery(this).html(agesval+" (+ "+(intvcost).numFormat(<?php echo $formatparts[0]; ?>, '<?php echo $formatparts[1]; ?>', '<?php echo $formatparts[2]; ?>')+" <?php echo $currencysymb; ?>)");
				}
			});
		}
	});
});
</script>
<?php
}
