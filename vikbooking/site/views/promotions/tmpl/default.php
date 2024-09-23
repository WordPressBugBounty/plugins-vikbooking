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

$promotions = $this->promotions;
$rooms = $this->rooms;
$showrooms = $this->showrooms == 1 ? true : false;
$vbo_tn = $this->vbo_tn;

$currencysymb = VikBooking::getCurrencySymb();
$vbodateformat = VikBooking::getDateFormat();
if ($vbodateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
}elseif ($vbodateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
}else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();
$pitemid = VikRequest::getString('Itemid', '', 'request');

$days_labels = array(
	JText::translate('VBJQCALSUN'),
	JText::translate('VBJQCALMON'),
	JText::translate('VBJQCALTUE'),
	JText::translate('VBJQCALWED'),
	JText::translate('VBJQCALTHU'),
	JText::translate('VBJQCALFRI'),
	JText::translate('VBJQCALSAT')
);

if(count($promotions) > 0) {
	?>
	<div class="vbo-promotions-container">
	<?php
	foreach ($promotions as $k => $promo) {
		?>
		<div class="vbo-promotion-details">
			<div class="vbo-promotion-det-wrapper">
				<div class="vbo-promotion-name">
					<h4><?php echo $promo['spname']; ?></h4>
				</div>
				<div class="vbo-promotion-description">
					<?php echo $promo['promotxt']; ?>
				</div>
			</div>
			<div class="vbo-promotion-wrapper">
				<div class="vbo-promotion-dates">
					<div class="vbo-promotion-dates-left">
						<div class="vbo-promotion-date-from">
							<span class="vbo-promotion-date-label"><?php echo JText::translate('VBOPROMORENTFROM'); ?></span>
							<span class="vbo-promotion-date-from-sp"><?php echo date(str_replace("/", $datesep, $df), $promo['promo_from_ts']); ?></span>
						</div>
						<div class="vbo-promotion-date-to">
							<span class="vbo-promotion-date-label"><?php echo JText::translate('VBOPROMORENTTO'); ?></span>
							<span class="vbo-promotion-date-to-sp"><?php echo date(str_replace("/", $datesep, $df), $promo['promo_to_ts']); ?></span>
						</div>
					</div>
					<div class="vbo-promotion-dates-right vbo-pref-color-element">
						<?php VikBookingIcons::e('clock-o'); ?>
					<?php
					if($promo['promo_to_ts'] != $promo['promo_valid_ts'] || ($promo['promo_to_ts'] == $promo['promo_valid_ts'] && empty($promo['promodaysadv']))) {
					?>
						<div class="vbo-promotion-date-validuntil">
							<span class="vbo-promotion-date-label"><?php echo JText::translate('VBOPROMOVALIDUNTIL'); ?></span>
							<span><?php echo date(str_replace("/", $datesep, $df), $promo['promo_valid_ts']); ?></span>
						</div>
					<?php
					}
					if(!empty($promo['wdays'])) {
						$wdays = explode(';', $promo['wdays']);
					?>
						<div class="vbo-promotion-date-weekdays">
						<?php
						foreach ($wdays as $wday) {
							if(!(strlen($wday) > 0)) {
								continue;
							}
							?>
							<span class="vbo-promotion-date-weekday"><?php echo $days_labels[$wday]; ?></span>
							<?php
						}
						?>
						</div>
					<?php
					}
					?>
					</div>
				</div>
				<div class="vbo-promotion-bottom-block">
				<?php
				//Rooms List
				if($showrooms === true && count($rooms) > 0 && !empty($promo['idrooms'])) {
					$promo_room_ids = explode(',', $promo['idrooms']);
					$promo_rooms = array();
					foreach ($promo_room_ids as $promo_room_id) {
						$promo_room_id = intval(str_replace("-", "", trim($promo_room_id)));
						if($promo_room_id > 0) {
							$promo_rooms[$promo_room_id] = $promo_room_id;
						}
					}
					if(count($promo_rooms) > 0) {
					?>
					<div class="vbo-promotion-rooms-list">
					<?php
						foreach ($rooms as $idroom => $room) {
							if (!array_key_exists($idroom, $promo_rooms)) {
								continue;
							}
							?>
						<div class="vbo-promotion-room-block">
							<div class="vbo-promotion-room-img">
							<?php
							if(!empty($room['img'])) {
								?>
								<img alt="<?php echo htmlspecialchars($room['name']); ?>" src="<?php echo VBO_SITE_URI; ?>resources/uploads/<?php echo $room['img']; ?>"/>
								<?php
							}
							?>
							</div>
							<div class="vbo-promotion-room-name">
								<?php echo $room['name']; ?>
							</div>
							<div class="vbo-promotion-room-book-block">
								<a class="btn vbo-promotion-room-book-link vbo-pref-color-btn" href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=roomdetails&roomid='.$room['id'].'&checkin='.$promo['promo_from_ts'].'&promo='.$promo['id'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo JText::translate('VBOPROMOROOMBOOKNOW'); ?></a>
							</div>
						</div>
							<?php
						}
					}
					?>
					</div>
					<?php
				} 
				//
				if($promo['type'] == 2) {
					?>
					<div class="vbo-promotion-discount">
						<div class="vbo-promotion-discount-details vbo-pref-bordertext">
					<?php
					if($promo['val_pcent'] == 2) {
						//Percentage
						$disc_amount = ($promo['diffcost'] - abs($promo['diffcost'])) > 0 ? $promo['diffcost'] : abs($promo['diffcost']);
						?>
							<span class="vbo-promotion-discount-percent-amount"><?php echo $disc_amount; ?>%</span>
							<span class="vbo-promotion-discount-percent-txt"><?php echo JText::translate('VBOPROMOPERCENTDISCOUNT'); ?></span>
						<?php
					}else {
						//Fixed
						?>
							<span class="vbo-promotion-discount-percent-amount"><span class="vbo_currency"><?php echo $currencysymb; ?></span><span class="vbo_price"><?php echo VikBooking::numberFormat($promo['diffcost']); ?></span></span>
							<span class="vbo-promotion-discount-percent-txt"><?php echo JText::translate('VBOPROMOFIXEDDISCOUNT'); ?></span>
						<?php
					}
					?>
						</div>
					</div>
					<?php
				}
				?>
				</div>
			</div>
		</div>
		<?php
	}
	?>
	</div>
	<?php
} else {
	?>
	<h3><?php echo JText::translate('VBONOPROMOTIONSFOUND'); ?></h3>
	<?php
}
