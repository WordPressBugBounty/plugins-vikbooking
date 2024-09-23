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

$rooms 		= $this->rooms;
$category 	= $this->category;
$vbo_tn 	= $this->vbo_tn;
$navig 		= $this->navig;

$currencysymb 	= VikBooking::getCurrencySymb();
$pitemid 		= VikRequest::getString('Itemid', '', 'request');
$document 		= JFactory::getDocument();
// load jQuery
if (VikBooking::loadJquery()) {
	JHtml::fetch('jquery.framework', true, true);
}

$playoutstyle = VikRequest::getString('layoutstyle', 'list', 'request');

if (is_array($category)) {
	?>
	<h3 class="vbclistheadt"><?php echo $category['name']; ?></h3>
	<?php
	if (strlen($category['descr']) > 0) {
		?>
		<div class="vbcatdescr">
			<?php
			/**
			 * @wponly  we allow the use of Shortcodes also inside the HTML description of the category.
			 */
			echo do_shortcode(wpautop($category['descr']));
			?>
		</div>
		<?php
	}
}
?>
<div class="vblistcontainer vblistcontainer-<?php echo $playoutstyle; ?>">
<ul class="vblist">
<?php
foreach ($rooms as $r) {
	if (!empty($r['moreimgs'])) {
		$document->addStyleSheet(VBO_SITE_URI.'resources/vikfxgallery.css');
		JHtml::fetch('script', VBO_SITE_URI.'resources/vikfxgallery.js');
		break;
	}
}
$gallery_data = array();
foreach ($rooms as $r) {
	if (empty($r['moreimgs'])) {
		continue;
	}
	$num = $r['id'];
	$gallery_data[$num] = array();
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
	';
}
$vikfx .= '
	jQuery(".vbo-roomslist-opengallery").click(function() {
		var num = jQuery(this).attr("data-roomid");
		if (typeof window["vikfxgallery" + num] !== "undefined") {
			window["vikfxgallery" + num].open();
		}
	});
});';
if (count($gallery_data)) {
	$document->addScriptDeclaration($vikfx);
}

foreach ($rooms as $r) {
	$details_link = JRoute::rewrite('index.php?option=com_vikbooking&view=roomdetails&roomid='.$r['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : ''));
	$carats = VikBooking::getRoomCaratOriz($r['idcarat'], $vbo_tn);
	/**
	 * @wponly 	we try to parse any shortcode inside the short description of the room
	 */
	$r['smalldesc'] = do_shortcode($r['smalldesc']);
	//
	?>
	<li class="room_result">
		<div class="room_result-inner">
			<div class="vblistroomblock">
			<?php
			if (!empty($r['img'])) {
			?>
				<div class="vbimglistdiv">
					<a class="vbo-roomslist-imglink" href="<?php echo $details_link; ?>">
						<img src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $r['img']; ?>" alt="<?php echo htmlspecialchars($r['name']); ?>" class="vblistimg"/>
					</a>
				<?php
				if (isset($gallery_data[$r['id']]) && count($gallery_data[$r['id']])) {
				?>
					<div class="vbmodalrdetails vbo-roomslist-opengallery-cont">
						<a href="javascript: void(0);" class="vbo-roomslist-opengallery" data-roomid="<?php echo $r['id']; ?>"><?php VikBookingIcons::e('camera'); ?></a>
					</div>
					<div class="vikfx-gallery" id="vikfx-gallery<?php echo $r['id']; ?>" style="display: none;">
					<?php
					foreach ($gallery_data[$r['id']] as $mimg) {
						?>
						<a href="<?php echo $mimg['big']; ?>" style="display: none;">
							<img src="<?php echo $mimg['thumb']; ?>" alt="<?php echo htmlspecialchars($mimg['alt']); ?>" title="<?php echo htmlspecialchars($mimg['caption']); ?>" style="display: none;"/>
						</a>
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
				<div class="vbo-info-room">
					<div class="vbdescrlistdiv">
						<h4 class="vbrowcname">
							<a href="<?php echo $details_link; ?>"><?php echo $r['name']; ?></a>
						</h4>
						<span class="vblistroomcat"><?php echo VikBooking::sayCategory($r['idcat'], $vbo_tn); ?></span>
						<div class="vbrowcdescr"><?php echo $r['smalldesc']; ?></div>
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
				</div>
			</div>

			<div class="vbcontdivtot">
				<div class="vbdivtot">
					<div class="vbdivtotinline">
						<div class="vbsrowprice">
							<div class="vbrowroomcapacity">
							<?php
							for ($i = 1; $i <= $r['toadult']; $i++) {
								VikBookingIcons::e('male', 'vbo-pref-color-text');
							}
							?>
							</div>
			<?php
			$custprice = VikBooking::getRoomParam('custprice', $r['params']);
			$custpricetxt = VikBooking::getRoomParam('custpricetxt', $r['params']);
			$custpricetxt = empty($custpricetxt) ? JText::translate('VBLISTPERNIGHT') : JText::translate($custpricetxt);
			$custpricesubtxt = VikBooking::getRoomParam('custpricesubtxt', $r['params']);
			if ($r['cost'] > 0 || !empty($custprice)) {
			?>
							<div class="vbsrowpricediv">
								<span class="room_cost"><span class="vbo_currency"><?php echo $currencysymb; ?></span> <span class="vbo_price"><?php echo (!empty($custprice) ? VikBooking::numberFormat($custprice) : VikBooking::numberFormat($r['cost'])); ?></span></span>
								<span class="vbliststartfrom"><?php echo $custpricetxt; ?></span>
								<?php
								if (!empty($custpricesubtxt)) {
									?>
								<div class="vbliststartfrom-subtxt"><?php echo $custpricesubtxt; ?></div>
									<?php
								}
								?>
							</div>
			<?php
			}
			?>
			
						</div>
						<div class="vbselectordiv">
							<div class="vbselectr">
								<a class="btn vbo-pref-color-btn" href="<?php echo $details_link; ?>"><?php echo JText::translate('VBSEARCHRESDETAILS'); ?></a>
							</div>
						</div>			
					</div>
				</div>
			</div>
		</div>
	</li>
	<?php
}
?>
</ul>
</div>

<?php
// pagination
if (strlen($navig) > 0) {
	?>
	<div class="pagination"><?php echo $navig; ?></div>
	<?php
}
