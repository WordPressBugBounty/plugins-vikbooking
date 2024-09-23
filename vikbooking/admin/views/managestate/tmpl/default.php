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

$row = $this->row;
$countries = $this->countries;

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();

$pidcountry = VikRequest::getInt('idcountry', 0, 'request');
?>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOADMINLEGENDDETAILS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCUSTOMFCOUNTRY'); ?>*</div>
							<div class="vbo-param-setting">
								<select name="idcountry" id="idcountry">
									<option value=""></option>
								<?php
								foreach ($countries as $country) {
									?>
									<option value="<?php echo $country['id']; ?>"<?php echo ((!count($row) && $country['id'] == $pidcountry) || (count($row) && $row['id_country'] == $country['id'])) ? ' selected="selected"' : ''; ?>><?php echo $country['country_name']; ?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_STATE_PROVINCE'); ?>*</div>
							<div class="vbo-param-setting"><input type="text" name="state_name" value="<?php echo count($row) ? htmlspecialchars($row['state_name']) : ''; ?>" /></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_3CHAR_CODE'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="state_3_code" value="<?php echo count($row) ? htmlspecialchars($row['state_3_code']) : ''; ?>" maxlength="3" /></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_2CHAR_CODE'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="state_2_code" value="<?php echo count($row) ? htmlspecialchars($row['state_2_code']) : ''; ?>" maxlength="2" /></div>
						</div>
					</div>
				</div>
			</fieldset>
		</div>
		<div class="vbo-config-maintab-right">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOADMINLEGENDSETTINGS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBPSHOWPAYMENTSFIVE'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->printYesNoButtons('published', JText::translate('VBYES'), JText::translate('VBNO'), (!count($row) || $row['published'] == 1 ? 1 : 0), 1, 0); ?>
							</div>
						</div>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
	<input type="hidden" name="task" value="">
<?php
if (count($row)) {
?>
	<input type="hidden" name="id" value="<?php echo $row['id']; ?>">
<?php
}
?>
	<input type="hidden" name="option" value="com_vikbooking">
	<?php echo JHtml::fetch('form.token'); ?>
</form>

<script type="text/javascript">
jQuery(function() {
	jQuery('#idcountry').select2();
});
</script>
