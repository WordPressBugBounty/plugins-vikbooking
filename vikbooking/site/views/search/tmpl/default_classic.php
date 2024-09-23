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

$vat_included = VikBooking::ivaInclusa();
$tax_summary = !$vat_included && VikBooking::showTaxOnSummaryOnly() ? true : false;

$currencysymb = VikBooking::getCurrencySymb();
$pitemid = VikRequest::getInt('Itemid', '', 'request');

/**
 * Interactive map booking. Only for classic booking layout.
 * 
 * @since 	1.14 (J) - 1.4.0 (WP)
 */
if (VikBooking::interactiveMapEnabled()) {
	echo $this->loadTemplate('interactive_map');
}
//

?>
<div class="vbo-searchresults-classic-wrap">
<?php
$writeroomnum = array();
foreach ($this->res as $indroom => $rooms) {
	foreach ($rooms as $room) {
		if ($this->roomsnum > 1 && !in_array($indroom, $writeroomnum)) {
			$writeroomnum[] = $indroom;
			?>
			<div id="vbpositionroom<?php echo $indroom; ?>"></div>
			<div class="vbsearchproominfo">
				<span class="vbsearchnroom"><?php echo JText::translate('VBSEARCHROOMNUM'); ?> <?php echo $indroom; ?></span>
				<span class="vbsearchroomparty"><?php VikBookingIcons::e('users', 'vbo-pref-color-text'); ?> <?php echo $this->arrpeople[$indroom]['adults']; ?> <?php echo ($this->arrpeople[$indroom]['adults'] == 1 ? JText::translate('VBSEARCHRESADULT') : JText::translate('VBSEARCHRESADULTS')); ?> <?php echo ($this->showchildren && $this->arrpeople[$indroom]['children'] > 0 ? ", ".$this->arrpeople[$indroom]['children']." ".($this->arrpeople[$indroom]['children'] == 1 ? JText::translate('VBSEARCHRESCHILD') : JText::translate('VBSEARCHRESCHILDREN')) : ""); ?></span>
			</div>
			<?php
		}
		//set a different class to the main div in case the rooms usage is for less people than the capacity
		$rdiffusage = array_key_exists('diffusage', $room[0]) && $this->arrpeople[$indroom]['adults'] < $room[0]['toadult'] ? true : false;
		$has_promotion = array_key_exists('promotion', $room[0]) ? true : false;
		$maindivclass = $rdiffusage ? "room_resultdiffusage" : "room_result";
		$carats = VikBooking::getRoomCaratOriz($room[0]['idcarat'], $this->vbo_tn);
		/**
		 * @wponly 	we try to parse any shortcode inside the short description of the room
		 */
		$room[0]['smalldesc'] = do_shortcode($room[0]['smalldesc']);
		//
		$saylastavail = false;
		$showlastavail = (int)VikBooking::getRoomParam('lastavail', $room[0]['params']);
		if (!empty($showlastavail) && $showlastavail > 0) {
			if ($room[0]['unitsavail'] <= $showlastavail) {
				$saylastavail = true;
			}
		}
		$searchdet_link = JRoute::rewrite('index.php?option=com_vikbooking&view=searchdetails&roomid='.$room[0]['idroom'].'&checkin='.$this->checkin.'&checkout='.$this->checkout.'&adults='.$this->arrpeople[$indroom]['adults'].'&children='.$this->arrpeople[$indroom]['children'].'&tmpl=component'.(!empty($pitemid) ? '&Itemid='.$pitemid : ''));

		/**
		 * Build image gallery, if available
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */
		$gallery_data = array();
		if (!empty($room[0]['moreimgs'])) {
			$moreimages = explode(';;', $room[0]['moreimgs']);
			foreach ($moreimages as $mimg) {
				if (empty($mimg)) {
					continue;
				}
				// push thumb URL
				array_push($gallery_data, $mimg);
			}
		}
		//
		?>
		<div class="room_item <?php echo $maindivclass; ?><?php echo $has_promotion === true ? ' vbo-promotion-price' : ''; ?>" id="vbcontainer<?php echo $indroom.'_'.$room[0]['idroom']; ?>">
			<div class="vblistroomblock">
				<div class="vbimglistdiv">
					<div class="vbo-dots-slider-selector">
						<a href="<?php echo $searchdet_link; ?>" class="vbmodalframe" target="_blank" data-gallery="<?php echo implode('|', $gallery_data); ?>">
						<?php
						if (!empty($room[0]['img']) && is_file(VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $room[0]['img'])) {
							?>
							<img class="vblistimg" alt="<?php echo htmlspecialchars($room[0]['name']); ?>" id="vbroomimg<?php echo $indroom.'_'.$room[0]['idroom']; ?>" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $room[0]['img']; ?>"/>
							<?php
						}
						?>
						</a>
					</div>
					<div class="vbmodalrdetails">
						<a href="<?php echo $searchdet_link; ?>" class="vbmodalframe" target="_blank"><?php VikBookingIcons::e('plus'); ?></a>
					</div>
				</div>
				<div class="vbo-info-room">
					<div class="vbdescrlistdiv">
						<h4 class="vbrowcname" id="vbroomname<?php echo $indroom.'_'.$room[0]['idroom']; ?>"><?php echo $room[0]['name']; ?></h4>
						<div class="vbrowcdescr"><?php echo $room[0]['smalldesc']; ?></div>
					</div>
				<?php
				if (!empty($carats)) {
					?>
					<div class="roomlist_carats">
						<?php echo $carats; ?>
					</div>
					<?php
				}
				?>
				<?php
				if ($has_promotion === true && !empty($room[0]['promotion']['promotxt'])) {
					?>
					<div class="vbo-promotion-block">
						<div class="vbo-promotion-icon"><?php VikBookingIcons::e('percentage'); ?></div>
						<div class="vbo-promotion-description">
							<?php echo $room[0]['promotion']['promotxt']; ?>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div>
			<div class="vbcontdivtot">
				<div class="vbdivtot">
					<div class="vbdivtotinline">
						<div class="vbsrowprice">
							<div class="vbrowroomcapacity">
							<?php
							for ($i = 1; $i <= $room[0]['toadult']; $i++) {
								if ($i <= $this->arrpeople[$indroom]['adults']) {
									VikBookingIcons::e('male', 'vbo-pref-color-text');
								} else {
									VikBookingIcons::e('male', 'vbo-empty-personicn');
								}
							}
							$raw_roomcost = $tax_summary ? $room[0]['cost'] : VikBooking::sayCostPlusIva($room[0]['cost'], $room[0]['idprice']);
							?>
							</div>
							<div class="vbsrowpricediv">
								<span class="room_cost">
									<span class="vbo_currency"><?php echo $currencysymb; ?></span> 
									<span class="vbo_price"><?php echo VikBooking::numberFormat($raw_roomcost); ?></span>
								</span>
						<?php
						if (isset($room[0]['promotion']) && isset($room[0]['promotion']['discount'])) {
							if ($room[0]['promotion']['discount']['pcent']) {
								/**
								 * Do not make an upper-cent operation, but rather calculate the original price proportionally:
								 * final price : (100 - discount amount) = x : 100
								 * 
								 * @since 	1.13.5
								 */
								$prev_amount = $raw_roomcost * 100 / (100 - $room[0]['promotion']['discount']['amount']);
							} else {
								$prev_amount = $raw_roomcost + $room[0]['promotion']['discount']['amount'];
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
								if ($room[0]['promotion']['discount']['pcent']) {
									// hide by default the DIV containing the percent of discount
									?>
								<div class="vbo-room-result-price-before-discount-percent" style="display: none;">
									<span class="room_cost">
										<span><?php echo '-' . (float)$room[0]['promotion']['discount']['amount'] . ' %'; ?></span>
									</span>
								</div>
									<?php
								}
							}
						}
						?>
							</div>
						<?php
						if ($saylastavail === true) {
							?>
							<span class="vblastavail"><?php echo JText::sprintf('VBLASTUNITSAVAIL', $room[0]['unitsavail']); ?></span>
							<?php
						}
						?>
						</div>
						<div class="vbselectordiv">
							<button type="button" id="vbselector<?php echo $indroom.'_'.$room[0]['idroom']; ?>" class="btn vbselectr-result vbo-pref-color-btn" onclick="vbSelectRoom('<?php echo $indroom; ?>', '<?php echo $room[0]['idroom']; ?>');"><?php echo JText::translate('VBSELECTR'); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
?>
</div>

<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('.vbo-dots-slider-selector').each(function() {
			var sliderLink = jQuery(this).find('a').first();
			if (!sliderLink.length) {
				return;
			}
			var gallery = sliderLink.data('gallery');
			if (!gallery || !gallery.length) {
				return;
			}
			var thumbs_base_uri = '<?php echo VBO_SITE_URI . 'resources/uploads/thumb_'; ?>';
			var gallery_data = gallery.split('|');
			var images = [];
			for (var i = 0; i < gallery_data.length; i++) {
				if (!gallery_data[i].length) {
					continue;
				}
				images.push(thumbs_base_uri + gallery_data[i]);
			}
			if (!images.length) {
				return;
			}
			// move original main photo and make it hidden so that the dialog will keep showing it
			var room_main_photo = jQuery(this).find('img.vblistimg');
			if (room_main_photo.length) {
				room_main_photo.hide().appendTo(jQuery(this).parent());
			}
			// render slider
			var slideWrap = sliderLink.clone();
			jQuery(this).html('').vikDotsSlider({
				images: images,
				navButPrevContent: '<?php VikBookingIcons::e('chevron-left'); ?>',
				navButNextContent: '<?php VikBookingIcons::e('chevron-right'); ?>',
				onDisplaySlide: function() {
					var content = jQuery(this).children().clone(true, true);
					/**
					 * @wponly 	In order to avoid delays with Fancybox, we do not re-construct the A tag.
					 * 			We just append the slide image.
					 * var link = jQuery('<a target="_blank"></a>').attr('href', slideWrap.attr('href')).attr('class', slideWrap.attr('class')).append(content);
					 * jQuery(this).html('').append(link);
					 */
					jQuery(this).html('').append(content);
				}
			});
		});
	});
</script>
