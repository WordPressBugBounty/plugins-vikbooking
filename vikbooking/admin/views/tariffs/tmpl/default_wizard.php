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

$vbo_app = VikBooking::getVboApplication();
$name = $this->roomrows['name'];
$currencysymb = VikBooking::getCurrencySymb(true);

?>
<div class="vbo-info-overlay-block vbo-info-overlay-block-animation">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content vbo-info-overlay-content-wizard vbo-info-overlay-content-hidden">
		<h3><?php echo "{$name} - " . JText::translate('VBINSERTFEE'); ?></h3>
		<div class="vbo-overlay-wizard-wrap">
			<form method="post" action="index.php?option=com_vikbooking">
				<div class="vbo-tariffs-wizard-help-wrap">
					<p>
						<span><?php echo JText::translate('VBOWIZARDTARIFFSMESS'); ?></span>
						<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBINSERTFEE'), 'content' => JText::translate('VBOWIZARDTARIFFSHELP'), 'placement' => 'bottom')); ?>
					</p>
					<h4><?php echo JText::translate('VBOWIZARDTARIFFSWHTC'); ?></h4>
				</div>
				<div class="vbo-tariffs-wizard-prices-wrap">
				<?php
				foreach ($this->prices as $pr) {
					?>
					<div class="vbo-tariffs-wizard-price">
						<span class="vbo-tariffs-wizard-price-name"><?php echo $pr['name']; ?></span>
						<span class="vbo-tariffs-wizard-price-cost">
							<span class="vbo-tariffs-wizard-price-cost-currency"><?php echo $currencysymb; ?></span>
							<span class="vbo-tariffs-wizard-price-cost-amount">
								<input type="number" min="1" step="any" name="dprice<?php echo $pr['id']; ?>" value=""/>
							</span>
						</span>
					</div>
					<?php
				}
				?>
				</div>
				<div class="vbo-tariffs-wizard-prices-submit">
					<input type="submit" class="btn btn-success" name="newdispcost" value="<?php echo JText::translate('VBINSERTFEE'); ?>"/>
				</div>
				<input type="hidden" name="task" value="tariffs" />
				<input type="hidden" name="ddaysfrom" value="1" />
				<input type="hidden" name="ddaysto" value="30" />
				<input type="hidden" name="cid[]" value="<?php echo $this->roomrows['id']; ?>" />
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
var vbodialog_on = false;
function hideVboWizard() {
	if (vbodialog_on === true) {
		jQuery(".vbo-info-overlay-block").fadeOut(400, function () {
			jQuery(".vbo-info-overlay-content").hide().addClass("vbo-info-overlay-content-hidden").removeClass("vbo-info-overlay-content-animated");
		});
		vbodialog_on = false;
	}
}
function showVboWizard() {
	jQuery(".vbo-info-overlay-block").fadeIn(400, function () {
		jQuery(".vbo-info-overlay-content").show().addClass("vbo-info-overlay-content-animated").removeClass("vbo-info-overlay-content-hidden");
	});
	vbodialog_on = true;
}
jQuery(document).ready(function() {
<?php
if (empty($this->rows)) {
	?>
	showVboWizard();
	<?php
}
?>
	// modal handling
	jQuery(document).keydown(function(e) {
		if (e.keyCode == 27) {
			hideVboWizard();
		}
	});
	jQuery(document).mouseup(function(e) {
		if (!vbodialog_on) {
			return false;
		}
		var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
		if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
			hideVboWizard();
		}
	});
});
</script>
