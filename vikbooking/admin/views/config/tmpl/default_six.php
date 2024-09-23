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
$vcm_installed = is_file(VCM_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikchannelmanager.php');

$gr_approval = VikBooking::guestReviewsApproval();
$gr_type = VikBooking::guestReviewsType();
$gr_services = VikBooking::guestReviewsServices();
?>
<fieldset class="adminform">
	<div class="vbo-params-wrap">
		<legend class="adminlegend"><?php echo JText::translate('VBOPANELREVIEWS'); ?></legend>
		<div class="vbo-params-container">
		<?php
		if (!$vcm_installed) {
			?>
			<p class="err"><?php echo JText::translate('VBOGUESTREVVCMNOT'); ?></p>
			<?php
		} else {
			?>
			<div class="vbo-param-container">
				<div class="vbo-param-label">
					<?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOCONFIGGRENABLE'), 'content' => JText::translate('VBOCONFIGGRENABLEHELP'))); ?>
					<?php echo JText::translate('VBOCONFIGGRENABLE'); ?>
				</div>
				<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('grenabled', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::allowGuestReviews(), 1, 0); ?></div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGGRMINCHARS'); ?></div>
				<div class="vbo-param-setting">
					<input type="number" name="grminchars" value="<?php echo VikBooking::guestReviewMinChars(); ?>" min="-1" />
					<span class="vbo-param-setting-comment"><?php echo JText::translate('VBOCONFIGGRMINCHARSHELP'); ?></span>
				</div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGGRAPPROVAL'); ?></div>
				<div class="vbo-param-setting">
					<select name="grappr">
						<option value="auto"<?php echo $gr_approval == 'auto' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGGRAPPRAUTO'); ?></option>
						<option value="manual"<?php echo $gr_approval == 'manual' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGGRAPPRMANUAL'); ?></option>
					</select>
					<span class="vbo-param-setting-comment"><?php echo JText::translate('VBOCONFIGGRAPPROVALHELP'); ?></span>
				</div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGGRTYPE'); ?></div>
				<div class="vbo-param-setting">
					<select name="grtype" onchange="vboGuestReviewType(this.value);">
						<option value="global"<?php echo $gr_type == 'global' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGGRTYPEGLOBAL'); ?></option>
						<option value="service"<?php echo $gr_type == 'service' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBOCONFIGGRTYPESERVICE'); ?></option>
					</select>
				</div>
			</div>
			<div class="vbo-param-container vbo-param-nested vbo-param-gr-service" style="<?php echo $gr_type == 'global' ? 'display: none;' : ''; ?>">
				<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGGRSERVICES'); ?></div>
				<div class="vbo-param-setting">
					<div class="vbo-config-gr-services">
					<?php
					foreach ($gr_services as $service) {
						?>
						<div class="vbo-config-gr-service">
							<span><input type="text" name="grsrv[]" value="<?php echo $this->escape($service['service_name']); ?>" /></span>
							<span><button type="button" class="btn btn-danger" onclick="vboRemoveRevService(this);"><?php VikBookingIcons::e('trash-alt'); ?></button></span>
						</div>
						<?php
					}
					?>
					</div>
					<div class="vbo-config-gr-service-add">
						<button type="button" class="btn vbo-config-btn" onclick="vboAddRevService();"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBCONFIGCLOSINGDATEADD'); ?></button>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		</div>
	</div>
</fieldset>

<script type="text/javascript">
function vboGuestReviewType(val) {
	if (val == 'service') {
		jQuery('.vbo-param-gr-service').show();
	} else {
		jQuery('.vbo-param-gr-service').hide();
	}
}
function vboRemoveRevService(elem) {
	jQuery(elem).closest('.vbo-config-gr-service').remove();
}
function vboAddRevService() {
	var grserv = '';
	grserv += '<div class="vbo-config-gr-service">' + "\n";
	grserv += '<span><input type="text" name="grsrv[]" value="" /></span>' + "\n";
	grserv += '<span><button type="button" class="btn btn-danger" onclick="vboRemoveRevService(this);"><?php VikBookingIcons::e('trash-alt'); ?></button></span>' + "\n";
	grserv += '</div>' + "\n";
	jQuery('.vbo-config-gr-services').append(grserv);
}
</script>
