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
?>
<div class="vbo-info-overlay-block vbo-info-overlay-block-animation">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content vbo-info-overlay-content-wizard vbo-info-overlay-content-hidden">
		<h3><?php echo JText::translate('VBMENUFIVE'); ?></h3>
		<div class="vbo-overlay-wizard-wrap">
			<form method="post" action="index.php?option=com_vikbooking">
				<div class="vbo-tariffs-wizard-help-wrap">
					<p>
						<span><?php echo JText::translate('VBOWIZARDRPLANSMESS'); ?></span>
					</p>
					<p>
						<?php echo JText::translate('VBOWIZARDRPLANSMESS2'); ?>
					</p>
					<h4><?php echo JText::translate('VBOSUGGRATEPLANS'); ?></h4>
				</div>
				<div class="vbo-rplans-wizard-wrap">
					<div class="vbo-rplans-wizard-tprice-wrap">
						<div class="vbo-rplans-wizard-tprice-name">
							<label for="rplanpub1-on"><?php echo JText::translate('VBOSTANDARDRATE'); ?></label>
							<?php echo $vbo_app->printYesNoButtons('rplanpub1', JText::translate('VBYES'), JText::translate('VBNO'), 1, 1, 0); ?>
						</div>
						<div class="vbo-rplans-wizard-tprice-bkincl">
							<label for="rplanbk1"><?php echo JText::translate('VBNEWPRICEBREAKFAST'); ?></label>
							<input type="checkbox" name="rplanbk1" id="rplanbk1" value="1" checked="checked" />
						</div>
					</div>
					<div class="vbo-rplans-wizard-tprice-wrap">
						<div class="vbo-rplans-wizard-tprice-name">
							<label for="rplanpub2-on"><?php echo JText::translate('VBONONREFRATE'); ?></label>
							<?php echo $vbo_app->printYesNoButtons('rplanpub2', JText::translate('VBYES'), JText::translate('VBNO'), 1, 1, 0); ?>
						</div>
						<div class="vbo-rplans-wizard-tprice-bkincl">
							<label for="rplanbk2"><?php echo JText::translate('VBNEWPRICEBREAKFAST'); ?></label>
							<input type="checkbox" name="rplanbk2" id="rplanbk2" value="1" />
						</div>
					</div>
				</div>
				<div class="vbo-tariffs-wizard-prices-submit">
					<button type="button" class="btn btn-danger" onclick="hideVboWizard();"><?php echo JText::translate('VBOCLOSE'); ?></button>
					<button type="submit" class="btn btn-success"><?php echo JText::translate('VBOCREATERATEPLANS'); ?></button>
				</div>
				<input type="hidden" name="task" value="prices" />
				<input type="hidden" name="wizard" value="1" />
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
	showVboWizard();
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
