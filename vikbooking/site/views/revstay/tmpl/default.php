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

$document = JFactory::getDocument();
// load jQuery lib e jQuery UI
if (VikBooking::loadJquery()) {
	// JHtml::fetch('jquery.framework', true, true);
	JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-1.12.4.min.js');
}
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery-ui.min.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.min.js');

$datesep = VikBooking::getDateSeparator();
$nowdf 	 = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}

$pitemid 		= VikRequest::getInt('Itemid', '', 'request');
$bestitemid 	= VikBooking::findProperItemIdType(array('booking'));
$rev_minchars 	= VikBooking::guestReviewMinChars();
$rev_type 		= VikBooking::guestReviewsType();
$rev_services 	= $this->grev_services;
$rev_services 	= $rev_type == 'global' ? array(array('global')) : $rev_services;
$ts_info 		= getdate($this->order['ts']);
$checkin_info 	= getdate($this->order['checkin']);
$checkout_info 	= getdate($this->order['checkout']);
$wdays_map 		= array(
	JText::translate('VBWEEKDAYZERO'),
	JText::translate('VBWEEKDAYONE'),
	JText::translate('VBWEEKDAYTWO'),
	JText::translate('VBWEEKDAYTHREE'),
	JText::translate('VBWEEKDAYFOUR'),
	JText::translate('VBWEEKDAYFIVE'),
	JText::translate('VBWEEKDAYSIX')
);

// lang vars for JS
JText::script('VBOREVIEWMESSLIM');

?>
<h3 class="vbo-booking-details-intro"><?php echo JText::translate('VBOLEAVEAREVIEWSTAY'); ?></h3>

<div class="vbo-booking-leavereview-wrap">
	
	<div class="vbo-booking-details-topcontainer">
		<div class="vbo-booking-details-midcontainer">
			<div class="vbo-booking-details-bookinfos">
				<span class="vbvordudatatitle"><?php echo JText::translate('VBORDERDETAILS'); ?></span>
				<div class="vbo-booking-details-bookinfo">
					<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBORDEREDON'); ?></span>
					<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$ts_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $this->order['ts']); ?></span>
				</div>
			<?php
			if (!empty($this->order['idorderota']) && !empty($this->order['channel'])) {
				?>
				<div class="vbo-booking-details-bookinfo">
					<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBORDERNUMBER'); ?></span>
					<span class="vbo-booking-details-bookinfo-val"><?php echo $this->order['idorderota']; ?></span>
				</div>
				<?php
			}
			?>
				<div class="vbo-booking-details-bookinfo">
					<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBDAL'); ?></span>
					<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$checkin_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $this->order['checkin']); ?></span>
				</div>
				<div class="vbo-booking-details-bookinfo">
					<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBAL'); ?></span>
					<span class="vbo-booking-details-bookinfo-val"><?php echo $wdays_map[$checkout_info['wday']].', '.date(str_replace("/", $datesep, $df).' H:i', $this->order['checkout']); ?></span>
				</div>
				<div class="vbo-booking-details-bookinfo">
					<span class="vbo-booking-details-bookinfo-lbl"><?php echo JText::translate('VBDAYS'); ?></span>
					<span class="vbo-booking-details-bookinfo-val"><?php echo $this->order['days']; ?></span>
				</div>
			</div>
		</div>
	</div>

	<div class="vbo-booking-leavereview-content">
		<div class="vbo-booking-leavereview-inner">
			<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=sendreview'); ?>" method="post" onsubmit="return vboValidateReview();">
				<div class="vbo-booking-starrating-wrap">
				<?php
				foreach ($rev_services as $k => $v) {
					?>
					<div class="vbo-booking-starrating-cont">
					<?php
					if (isset($v['service_name']) && !empty($v['service_name'])) {
						?>
						<h5 class="vbo-booking-review-servicename"><?php echo $v['service_name']; ?></h5>
						<?php
					} elseif (is_array($v) && in_array('global', $v)) {
						?>
						<h5 class="vbo-booking-review-servicename vbo-booking-review-global"><?php echo JText::translate('VBOREVIEWRATEEXP'); ?></h5>
						<?php
					}
					?>
						<div class="vbo-booking-starrating-stars" data-starlocked="0">
						<?php
						for ($i = 1; $i <= 5; $i++) {
							?>
							<i class="<?php echo VikBookingIcons::i('star', 'vbo-review-star vbo-review-star-full'); ?>" data-starid="<?php echo $i; ?>" onclick="vboSetStarRating(<?php echo $k; ?>, <?php echo $i; ?>, this);"></i>
							<?php
						}
						?>
							<input type="hidden" name="rating[]" value="5" class="vbo-review-ratinginp" id="vbo-review-ratinginp<?php echo $k; ?>" />
						</div>
					</div>
					<?php
				}
				?>
				</div>
				<?php
				if ($rev_minchars >= 0) {
					// review message is requested
					?>
				<div class="vbo-booking-review-message">
					<label for="vbo-review-message"><?php echo JText::translate('VBOREVIEWLEAVEMESS'); ?></label>
					<div class="vbo-booking-review-message-inner">
						<textarea name="ratingmess" id="vbo-review-message"></textarea>
						<div class="vbo-booking-review-message-privacy"><?php echo JText::translate('VBOREVIEWMESSPRIVACY'); ?></div>
					</div>
				</div>
					<?php
				}
				?>
				<div class="vbo-booking-review-cmds">
					<div class="vbo-booking-review-cmd">
						<a class="btn vbo-pref-color-btn-secondary" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid=' . (!empty($this->order['idorderota']) && !empty($this->order['channel']) ? $this->order['idorderota'] : $this->order['sid']) . '&ts=' . $this->order['ts'] . (!empty($bestitemid) ? '&Itemid='.$bestitemid : (!empty($pitemid) ? '&Itemid='.$pitemid : ''))); ?>"><?php echo JText::translate('VBDIALOGBTNCANCEL'); ?></a>
					</div>
					<div class="vbo-booking-review-cmd">
						<button type="submit" class="btn vbo-pref-color-btn"><?php echo JText::translate('VBOREVIEWSUBMIT'); ?></button>
					</div>
				</div>
				<input type="hidden" name="task" value="sendreview" />
				<input type="hidden" name="Itemid" value="<?php echo (!empty($bestitemid) ? $bestitemid : $pitemid); ?>" />
				<input type="hidden" name="sid" value="<?php echo (!empty($this->order['idorderota']) && !empty($this->order['channel']) ? $this->order['idorderota'] : $this->order['sid']); ?>" />
				<input type="hidden" name="ts" value="<?php echo $this->order['ts']; ?>" />
			</form>
		</div>
	</div>

</div>

<script type="text/javascript">
function vboSetStarRating(service_ind, rating, elem) {
	// update input value
	document.getElementById('vbo-review-ratinginp' + service_ind).value = elem.getAttribute('data-starid');
	// toggle lock/unlock parent node from hovering
	var service = jQuery(elem).closest('.vbo-booking-starrating-stars');
	var islocked = service.attr('data-starlocked');
	service.attr('data-starlocked', (islocked == '1' ? '0' : '1'));
	// unset full class
	service.find('.vbo-review-star').removeClass('vbo-review-star-full');
	// add full class where necessary
	for (var i = 1; i <= rating; i++) {
		service.find('.vbo-review-star[data-starid="' + i + '"]').addClass('vbo-review-star-full');
	}
}
function vboValidateReview() {
	if (jQuery('#vbo-review-message').length) {
		var chars = jQuery('#vbo-review-message').val().length;
		if (chars < <?php echo $rev_minchars; ?>) {
			alert(Joomla.JText._('VBOREVIEWMESSLIM').replace('%d', '<?php echo $rev_minchars; ?>'));
			return false;
		}
	}
	return true;
}
jQuery(document).ready(function() {
	jQuery('.vbo-review-star').hover(
		function() {
			var rating = parseInt(jQuery(this).attr('data-starid'));
			var service = jQuery(this).closest('.vbo-booking-starrating-stars');
			if (service.attr('data-starlocked') == '1') {
				// only click is allowed
				return;
			}
			// update input value
			service.find('.vbo-review-ratinginp').val(rating);
			// unset full class
			service.find('.vbo-review-star').removeClass('vbo-review-star-full');
			// add full class where necessary
			for (var i = 1; i <= rating; i++) {
				service.find('.vbo-review-star[data-starid="' + i + '"]').addClass('vbo-review-star-full');
			}
		}, function() {
			// do nothing when hovering out
		}
	);
});
</script>
