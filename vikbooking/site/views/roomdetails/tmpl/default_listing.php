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

// JSON-decode room parameters
$rparams = json_decode($this->room['params'], true);

$currencysymb = VikBooking::getCurrencySymb();

// photo gallery
$gallery_data = [];
if (!empty($this->room['moreimgs'])) {
	$moreimages = explode(';;', $this->room['moreimgs']);
	$imgcaptions = json_decode($this->room['imgcaptions'], true);
	$usecaptions = is_array($imgcaptions);
	foreach ($moreimages as $iind => $mimg) {
		if (empty($mimg)) {
			continue;
		}
		$img_alt = $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : substr($mimg, 0, strpos($mimg, '.'));
		array_push($gallery_data, [
			'big' => VBO_SITE_URI . 'resources/uploads/big_' . $mimg,
			'thumb' => VBO_SITE_URI . 'resources/uploads/thumb_' . $mimg,
			'alt' => $img_alt,
			'caption' => $usecaptions && !empty($imgcaptions[$iind]) ? $imgcaptions[$iind] : "",
		]);
	}
}

?>

<div class="vbrdetboxtop vbo-listing-details-wrap">

	<div class="vblistroomnamediv">
		<h3><?php echo $this->room['name']; ?></h3>
		<span class="vblistroomcat"><?php echo VikBooking::sayCategory($this->room['idcat'], $this->vbo_tn); ?></span>
	</div>
	
	<div class="vbroomimgdesc">
	<?php 
	if (!empty($this->room['img'])) {
		?>
		<div class="vikfx-gallery-container vikfx-roomdetails-gallery-container">
			<div class="vikfx-gallery-fade-container">
				<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $this->room['img']; ?>" alt="<?php echo htmlspecialchars($this->room['name']); ?>" class="vikfx-gallery-image vblistimg"/>
			<?php
			if ($gallery_data) {
				?>
				<div class="vikfx-gallery-navigation-controls">
					<div class="vikfx-gallery-navigation-controls-prevnext">
						<a href="javascript: void(0);" class="vikfx-gallery-previous-image"><?php VikBookingIcons::e('chevron-left'); ?></a>
						<a href="javascript: void(0);" class="vikfx-gallery-next-image"><?php VikBookingIcons::e('chevron-right'); ?></a>
					</div>
				</div>
				<?php
			}
			?>
			</div>
		<?php
		if ($gallery_data) {
			?>
			<div class="vikfx-gallery">
			<?php
			$printed = 0;
			$lim_photos = 4;
			foreach ($gallery_data as $mimg) {
				?>
				<a href="<?php echo $mimg['big']; ?>" target="_blank" style="<?php echo ++$printed > $lim_photos ? 'display: none;' : ''; ?>">
					<img src="<?php echo $mimg['thumb']; ?>" alt="<?php echo $this->escape($mimg['alt']); ?>" title="<?php echo $this->escape($mimg['caption']); ?>"/>
				</a>
				<?php
			}

			if (count($gallery_data) > $lim_photos) {
				?>
				<div class="vbo-listing-details-allphotos">
					<a href="JavaScript: void(0);" class="vikfx-gallery-open"><?php VikBookingIcons::e('images'); ?> <?php echo JText::translate('VBO_ALL_PHOTOS'); ?></a>
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

	<div class="vbo-rdet-descprice-block vbo-listing-details-descprice-block">
		<div class="vbo-rdet-desc-cont vbo-listing-details-desc-cont">
		<?php
		// description
		if (VBOPlatformDetection::isWordPress()) {
			/**
			 * @wponly 	we try to parse any shortcode inside the HTML description of the room
			 */
			echo do_shortcode(wpautop($this->room['info']));
		} else {
			//BEGIN: Joomla Content Plugins Rendering
			JPluginHelper::importPlugin('content');
			$myItem = JTable::getInstance('content');
			$myItem->text = $this->room['info'];
			$objparams = [];
			if (class_exists('JEventDispatcher')) {
				$dispatcher = JEventDispatcher::getInstance();
				$dispatcher->trigger('onContentPrepare', ['com_vikbooking.roomdetails', &$myItem, &$objparams, 0]);
			} else {
				/**
				 * @joomla4only
				 */
				$dispatcher = JFactory::getApplication();
				if (method_exists($dispatcher, 'triggerEvent')) {
					$dispatcher->triggerEvent('onContentPrepare', ['com_vikbooking.roomdetails', &$myItem, &$objparams, 0]);
				}
			}
			$this->room['info'] = $myItem->text;
			//END: Joomla Content Plugins Rendering
			echo $this->room['info'];
		}

		// render "reqinfo" template
		echo $this->loadTemplate('reqinfo');

		// amenities
		$amenities = VikBooking::loadRoomAmenities($this->room['idcarat'], $this->vbo_tn);
		if ($amenities) {
			?>
			<div class="vbo-listing-amenities-wrap">
				<h4><?php echo JText::translate('VBO_AMENITIES'); ?></h4>
				<div class="vbo-listing-amenities">
			<?php
			foreach ($amenities as $a) {
				$amenity = '';
				if (!empty($a['textimg'])) {
					// tooltip icon text is not empty
					if (!empty($a['icon'])) {
						// an icon has been uploaded: display the image
						$amenity .= "<span class=\"vbo-listing-amenity\"><span class=\"vbo-listing-amenity-icon\"><img src=\"".VBO_SITE_URI."resources/uploads/" . $a['icon'] . "\" alt=\"" . htmlspecialchars((string) $a['name'], ENT_QUOTES, 'UTF-8') . "\" /></span> <span class=\"vbo-listing-amenity-name\">" . $a['textimg'] . "</span></span>\n";
					} else {
						if (strpos($a['textimg'], '</i>') !== false || strpos($a['textimg'], '<svg') !== false) {
							// the tooltip icon text is a font-icon or an SVG field, we can use the name as tooltip
							$amenity .= "<span class=\"vbo-listing-amenity\"><span class=\"vbo-listing-amenity-textimg\">" . $a['textimg'] . "</span> <span class=\"vbo-listing-amenity-name\">" . $a['name'] . "</span></span>\n";
						} else {
							// display just the text
							$amenity .= "<span class=\"vbo-listing-amenity\">" . $a['textimg'] . "</span>\n";
						}
					}
				} else {
					$amenity .= (!empty($a['icon']) ? "<span class=\"vbo-listing-amenity\"><img src=\"".VBO_SITE_URI."resources/uploads/" . $a['icon'] . "\" alt=\"" . htmlspecialchars((string) $a['name'], ENT_QUOTES, 'UTF-8') . "\" title=\"" . htmlspecialchars((string) $a['name'], ENT_QUOTES, 'UTF-8') . "\"/></span>\n" : "<span class=\"vbo-listing-amenity\">" . $a['name'] . "</span>\n");
				}

				echo $amenity;
			}
			?>
				</div>
			</div>
			<?php
		}
		?>

			<div class="vbo-roomdet-calscontainer">
				<div class="vbo-roomdet-calscontainer-inner">
					<?php
					// seasons calendar
					echo $this->loadTemplate('seasonscal');

					// availability calendars
					echo $this->loadTemplate('avcalendars');
					?>
				</div>
			</div>

		</div>
	<?php
	$custprice = VikBooking::getRoomParam('custprice', $rparams);
	$custpricetxt = VikBooking::getRoomParam('custpricetxt', $rparams);
	$custpricetxt = empty($custpricetxt) ? '' : JText::translate($custpricetxt);
	$custpricesubtxt = VikBooking::getRoomParam('custpricesubtxt', $rparams);
	if ($this->room['cost'] > 0 || !empty($custprice)) {
		?>
		<div class="vb_detcostroomdet vbo-listing-details-costroomdet">
			<div class="vbo-listing-details-costroomdet-inner">
				<div class="vb_detcostroom">
					<div class="vblistroomnamedivprice">
						<div class="vblistroomname vbo-pref-color-text">
							<span class="vbliststartfromrdet"><?php echo JText::translate('VBLISTSFROM'); ?></span>
							<span class="room_cost"><span class="vbo_currency"><?php echo $currencysymb; ?></span> <span class="vbo_price"><?php echo (!empty($custprice) ? VikBooking::numberFormat($custprice) : VikBooking::numberFormat($this->room['cost'])); ?></span></span>
						<?php
						if (!empty($custpricetxt)) {
							?>
							<span class="roomcustcostlabel"><?php echo $custpricetxt; ?></span>
							<?php
						}
						if (!empty($custpricesubtxt)) {
							?>
							<div class="roomcustcost-subtxt"><?php echo $custpricesubtxt; ?></div>
							<?php
						}
						?>
						</div>
					</div>
				</div>

				<?php
				// booking form
				echo $this->loadTemplate('bookingform');
				?>

			</div>
		</div>
	<?php
	}
	?>

	</div>
	<?php

	/**
	 * Room geocoding information.
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	echo $this->loadTemplate('geomap');
?>

</div>
