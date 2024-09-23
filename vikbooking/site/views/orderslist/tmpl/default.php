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

$userorders = $this->userorders;
$customer_details = $this->customer_details;
$navig = $this->navig;

$vbdateformat = VikBooking::getDateFormat();
if ($vbdateformat == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($vbdateformat == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();
$pitemid = VikRequest::getString('Itemid', '', 'request');
?>

<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=orderslist'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
	<div class="vbsearchorderdiv">
		<div class="vbsearchorderinner">
			<span class="vbsearchordertitle"><?php echo JText::translate('VBSEARCHCONFIRMNUMB'); ?></span>
		</div>
		<div class="vbo-bookings-list-search">
			<span><?php echo JText::translate('VBCONFIRMNUMBORPIN'); ?></span>
			<input type="text" name="confirmnumber" value="<?php echo is_array($customer_details) && array_key_exists('pin', $customer_details) ? $customer_details['pin'] : ''; ?>" size="12"/> 
			<input type="submit" class="btn vbsearchordersubmit vbo-pref-color-btn" name="vbsearchorder" value="<?php echo JText::translate('VBSEARCHCONFIRMNUMBBTN'); ?>"/>
		</div>
	</div>
</form>

<?php
if ($userorders) {
	?>
<div class="vbo-bookings-list-container">
	<div class="vbo-bookings-list-table">
		<div class="vbo-bookings-list-table-head">
			<div class="vbo-bookings-list-table-row vbo-bookings-list-table-head-row">
				<div class="vbo-bookings-list-table-cell">
					&nbsp;
				</div>
				<div class="vbo-bookings-list-table-cell">
					<span><?php echo JText::translate('VBCONFIRMNUMB'); ?></span>
				</div>
				<div class="vbo-bookings-list-table-cell">
					<span><?php echo JText::translate('VBBOOKINGDATE'); ?></span>
				</div>
				<div class="vbo-bookings-list-table-cell">
					<span><?php echo JText::translate('VBPICKUP'); ?></span>
				</div>
				<div class="vbo-bookings-list-table-cell">
					<span><?php echo JText::translate('VBRETURN'); ?></span>
				</div>
				<div class="vbo-bookings-list-table-cell">
					<span><?php echo JText::translate('VBDAYS'); ?></span>
				</div>
			</div>
		</div>
		<div class="vbo-bookings-list-table-body">
	<?php
	foreach ($userorders as $ord) {
		$status_icn = VikBookingIcons::i('check-circle');
		if ($ord['status'] == 'standby') {
			$status_icn = VikBookingIcons::i('exclamation-circle');
		} elseif ($ord['status'] == 'cancelled') {
			$status_icn = VikBookingIcons::i('times-circle');
		}
		?>
		<div class="vbo-bookings-list-table-row vbo-bookings-list-table-body-row vbo-bookings-list-table-body-row-<?php echo $ord['status']; ?>">
			<div class="vbo-bookings-list-table-cell vbo-bookings-list-table-cell-bstatus">
				<span><i class="<?php echo $status_icn; ?>"></i></span>
			</div>
			<div class="vbo-bookings-list-table-cell">
				<span class="vbo-bookings-list-table-cell-lbl"><?php echo JText::translate('VBCONFIRMNUMB'); ?></span>
				<span><a href="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&view=booking&sid='.(!empty($ord['sid']) ? $ord['sid'] : $ord['idorderota']).'&ts='.$ord['ts'].(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>"><?php echo (!empty($ord['confirmnumber']) ? $ord['confirmnumber'] : ($ord['status'] == 'standby' ? JText::translate('VBINATTESA') : '--------')); ?></a></span>
			</div>
			<div class="vbo-bookings-list-table-cell">
				<span class="vbo-bookings-list-table-cell-lbl"><?php echo JText::translate('VBBOOKINGDATE'); ?></span>
				<span><?php echo date(str_replace("/", $datesep, $df).' H:i', $ord['ts']); ?></span>
			</div>
			<div class="vbo-bookings-list-table-cell">
				<span class="vbo-bookings-list-table-cell-lbl"><?php echo JText::translate('VBPICKUP'); ?></span>
				<span><?php echo date(str_replace("/", $datesep, $df), $ord['checkin']); ?></span>
			</div>
			<div class="vbo-bookings-list-table-cell">
				<span class="vbo-bookings-list-table-cell-lbl"><?php echo JText::translate('VBRETURN'); ?></span>
				<span><?php echo date(str_replace("/", $datesep, $df), $ord['checkout']); ?></span>
			</div>
			<div class="vbo-bookings-list-table-cell">
				<span class="vbo-bookings-list-table-cell-lbl"><?php echo JText::translate('VBDAYS'); ?></span>
				<span><?php echo $ord['days']; ?></span>
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

// pagination
if (strlen($navig) > 0) {
	?>
<div class="pagination"><?php echo $navig; ?></div>
	<?php
}
