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

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadSelect2();

$dbo = JFactory::getDbo();

$q = "SELECT * FROM `#__vikbooking_iva`;";
$dbo->setQuery($q);
$ivas = $dbo->loadAssocList();
if ($ivas) {
	$wiva = "<select name=\"praliq\">\n";
	$wiva .= "<option value=\"\">-----</option>\n";
	foreach ($ivas as $iv) {
		$wiva .= "<option value=\"".$iv['id']."\"".($row && $iv['id'] == $row['idiva'] ? " selected=\"selected\"" : "").">".(empty($iv['name']) ? $iv['aliq']."%" : $iv['name']."-".$iv['aliq']."%")."</option>\n";
	}
	$wiva .= "</select>\n";
} else {
	$wiva = "<a href=\"index.php?option=com_vikbooking&task=iva\">".JText::translate('NESSUNAIVA')."</a>";
}

/**
 * Rate plans support included meal plans.
 * 
 * @since 	1.16.1 (J) - 1.6.1 (WP)
 */
$meal_plan_manager = VBOMealplanManager::getInstance();
$meal_plans = $meal_plan_manager->getPlans();

/**
 * Derived Rate Plans.
 * 
 * @since 	1.16.10 (J) - 1.6.10 (WP)
 */
$is_derived = !empty($row['derived_id']) && isset($this->parent_rates[$row['derived_id']]);
$derived_data = $is_derived ? json_decode($row['derived_data'], true) : [];
$derived_data = is_array($derived_data) ? $derived_data : [];

?>

<script type="text/javascript">
	function toggleFreeCancellation() {
		if (jQuery('input[name="free_cancellation"]').is(':checked')) {
			jQuery('#canc_deadline, #canc_policy').fadeIn();
		} else {
			jQuery('#canc_deadline, #canc_policy').hide();
		}
		return true;
	}

	function toggleDerivedData() {
		if (jQuery('input[name="is_derived"]').is(':checked')) {
			jQuery('[data-type="derived-info"]').fadeIn();
		} else {
			jQuery('[data-type="derived-info"]').hide();
		}
	}

	jQuery(function() {
		jQuery('#vbo-meal-plans').select2();
	});
</script>

<form name="adminForm" id="adminForm" action="index.php" method="post">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOADMINLEGENDDETAILS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWPRICEONE'); ?>*</div>
							<div class="vbo-param-setting"><input type="text" name="price" value="<?php echo $row ? htmlspecialchars($row['name']) : ''; ?>" size="40"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBNEWPRICETWO'), 'content' => JText::translate('VBOPRICEATTRHELP'))); ?> <?php echo JText::translate('VBNEWPRICETWO'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="attr" value="<?php echo $row ? htmlspecialchars((string)$row['attr']) : ''; ?>" size="40"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWPRICETHREE'); ?></div>
							<div class="vbo-param-setting"><?php echo $wiva; ?></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_IS_DERIVED_RATE'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->printYesNoButtons('is_derived', JText::translate('VBYES'), JText::translate('VBNO'), ($is_derived ? 1 : 0), 1, 0, 'toggleDerivedData();'); ?>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_DERIVED_RATE_HELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" data-type="derived-info" style="<?php echo !$is_derived ? 'display: none;' : ''; ?>;">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_PARENT_RATE'); ?></div>
							<div class="vbo-param-setting">
								<select name="derived_id">
									<option value=""></option>
								<?php
								foreach ($this->parent_rates as $rpid => $rpname) {
									?>
									<option value="<?php echo $rpid; ?>"<?php echo $is_derived && $rpid == $row['derived_id'] ? ' selected="selected"' : ''; ?>><?php echo $rpname; ?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" data-type="derived-info" style="<?php echo !$is_derived ? 'display: none;' : ''; ?>;">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_MODIFICATION_MODE'); ?></div>
							<div class="vbo-param-setting">
								<select name="derived_data[mode]">
									<option value="discount"<?php echo $is_derived && ($derived_data['mode'] ?? '') == 'discount' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPSHOWSEASONSSIX'); ?></option>
									<option value="charge"<?php echo $is_derived && ($derived_data['mode'] ?? '') == 'charge' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPSHOWSEASONSFIVE'); ?></option>
								</select>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_MODIFICATION_MODE_HELP'); ?></span>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" data-type="derived-info" style="<?php echo !$is_derived ? 'display: none;' : ''; ?>;">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_MODIFICATION_TYPE'); ?></div>
							<div class="vbo-param-setting">
								<select name="derived_data[type]">
									<option value="percent"<?php echo $is_derived && ($derived_data['type'] ?? '') == 'percent' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_PERCENT'); ?> (%)</option>
									<option value="absolute"<?php echo $is_derived && ($derived_data['type'] ?? '') == 'absolute' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_ABSOLUTE'); ?> (<?php echo VikBooking::getCurrencySymb(); ?>)</option>
								</select>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" data-type="derived-info" style="<?php echo !$is_derived ? 'display: none;' : ''; ?>;">
							<div class="vbo-param-label"><?php echo JText::translate('VBPSHOWSEASONSFOUR'); ?></div>
							<div class="vbo-param-setting">
								<input name="derived_data[value]" type="number" step="any" value="<?php echo $is_derived ? ($derived_data['value'] ?? 0) : 0; ?>" min="0" />
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" data-type="derived-info" style="<?php echo !$is_derived ? 'display: none;' : ''; ?>;">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_FOLLOW_RESTRICTIONS'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->printYesNoButtons('derived_data[follow_restr]', JText::translate('VBYES'), JText::translate('VBNO'), ($is_derived ? (int) ($derived_data['follow_restr'] ?? 1) : 1), 1, 0); ?>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_FOLLOW_RESTRICTIONS_HELP'); ?></span>
							</div>
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
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOPRICETYPEMINLOS'), 'content' => JText::translate('VBOPRICETYPEMINLOSHELP'))); ?> <?php echo JText::translate('VBOPRICETYPEMINLOS'); ?></div>
							<div class="vbo-param-setting"><input type="number" name="minlos" min="0" value="<?php echo $row ? $row['minlos'] : '0'; ?>" /></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOPRICETYPEMINHADV'), 'content' => JText::translate('VBOPRICETYPEMINHADVHELP'))); ?> <?php echo JText::translate('VBOPRICETYPEMINHADV'); ?></div>
							<div class="vbo-param-setting"><input type="number" name="minhadv" min="0" value="<?php echo $row ? $row['minhadv'] : '0'; ?>" /></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBO_MEAL_PLANS_INCL'); ?></div>
							<div class="vbo-param-setting">
								<select name="meal_plans[]" id="vbo-meal-plans" multiple="multiple">
								<?php
								foreach ($meal_plans as $meal_enum => $meal_name) {
									$meal_included = false;
									if ($row && $meal_plan_manager->ratePlanMealIncluded($row, $meal_enum)) {
										$meal_included = true;
									}
									?>
									<option value="<?php echo $meal_enum; ?>"<?php echo $meal_included ? ' selected="selected"' : ''; ?>><?php echo $meal_name; ?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWPRICEFREECANC'); ?></div>
							<div class="vbo-param-setting">
								<?php echo $vbo_app->printYesNoButtons('free_cancellation', JText::translate('VBYES'), JText::translate('VBNO'), ($row && $row['free_cancellation'] == 1 ? 1 : 0), 1, 0, 'toggleFreeCancellation();'); ?>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" id="canc_deadline" style="display: <?php echo $row && $row['free_cancellation'] == 1 ? 'flex' : 'none'; ?>;">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWPRICEFREECANCDLINE'); ?></div>
							<div class="vbo-param-setting">
								<input type="number" min="0" name="canc_deadline" value="<?php echo $row ? $row['canc_deadline'] : '7'; ?>" size="5"/>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" id="canc_policy" style="display: <?php echo $row && $row['free_cancellation'] == 1 ? 'flex' : 'none'; ?>;">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBNEWPRICECANCPOLICY'), 'content' => JText::translate('VBNEWPRICECANCPOLICYHELP'))); ?> <?php echo JText::translate('VBNEWPRICECANCPOLICY'); ?></div>
							<div class="vbo-param-setting">
								<textarea name="canc_policy" rows="5" cols="200" style="width: 350px; height: 130px;"><?php echo $row ? htmlspecialchars((string)$row['canc_policy']) : ''; ?></textarea>
							</div>
						</div>
					</div>
				</div>
			</fieldset>
		</div>
	</div>
	<input type="hidden" name="task" value="">
<?php
if ($row) {
?>
	<input type="hidden" name="whereup" value="<?php echo $row['id']; ?>">
<?php
}
?>
	<input type="hidden" name="option" value="com_vikbooking">
	<?php echo JHtml::fetch('form.token'); ?>
</form>
