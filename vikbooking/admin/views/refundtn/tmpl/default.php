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

$currencysymb = VikBooking::getCurrencySymb();
$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator(true);
$tmpl = VikRequest::getString('tmpl', '', 'request');
$success = VikRequest::getInt('success', 0, 'request');

?>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBO_REFUND'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBLIBPAYNAME'); ?></div>
							<div class="vbo-param-setting">
								<strong><?php echo $this->payment['name']; ?></strong>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_REFUND_AMOUNT'); ?></div>
							<div class="vbo-param-setting">
								<?php
								$max_refund = $this->row['totpaid'] > 0 ? $this->row['totpaid'] : $this->row['total'];
								?>
								<span><?php echo $currencysymb; ?></span> 
								<input type="number" id="vbo-refund-amount" name="amount" class="vbo-input-number-large" value="<?php echo $max_refund; ?>" step="any" min="0" max="<?php echo $this->row['total']; ?>" />
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_REFUND_REASON'); ?></div>
							<div class="vbo-param-setting">
								<textarea name="refund_reason" rows="4" cols="60"></textarea>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label">&nbsp;</div>
							<div class="vbo-param-setting">
								<button type="submit" class="btn btn-large btn-danger" onclick="return vboValidateDoRefund();"><?php VikBookingIcons::e('credit-card'); ?> <?php echo JText::translate('VBO_REFUND'); ?></button>
							</div>
						</div>
					<?php
					// display any previous refund
					foreach ($this->refunds as $prev_refund_event) {
						?>
						<div class="vbo-param-container vbo-param-container-log">
							<div class="vbo-param-label">
							<?php
							/**
							 * @wponly 	Format the datetime string
							 */
							echo JHtml::fetch('date', $prev_refund_event['dt']);
							?>
							</div>
							<div class="vbo-param-setting">
								<div class="vbo-refund-log"><?php echo nl2br($prev_refund_event['descr']); ?></div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
	<input type="hidden" name="bid" value="<?php echo $this->row['id']; ?>">
	<input type="hidden" name="task" value="do_refundtn" />
	<input type="hidden" name="option" value="com_vikbooking" />
<?php
if ($tmpl == 'component') {
	?>
	<input type="hidden" name="tmpl" value="component" />
	<?php
}
?>
</form>

<script type="text/javascript">
	function vboValidateDoRefund() {
		var amount = document.getElementById('vbo-refund-amount').value;
		if (!amount.length || isNaN(amount) || amount <= 0) {
			alert("<?php echo addslashes(JText::translate('VBO_PLEASE_FILL_FIELDS')); ?>");
			return false;
		}
		return confirm("<?php echo addslashes(JText::translate('VBO_DOREFUND_CONFIRM')); ?>");
	}
<?php
if ($success > 0) {
	?>
	setTimeout(function() {
		if (window.parent['vbo_refund_performed'] != undefined) {
			window.parent['vbo_refund_performed'] = true;
		}
	}, 500);
	<?php
}
?>
</script>
