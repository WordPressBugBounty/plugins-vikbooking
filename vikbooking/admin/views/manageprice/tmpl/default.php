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
		$wiva .= "<option value=\"".$iv['id']."\"".(count($row) && $iv['id'] == $row['idiva'] ? " selected=\"selected\"" : "").">".(empty($iv['name']) ? $iv['aliq']."%" : $iv['name']."-".$iv['aliq']."%")."</option>\n";
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
							<div class="vbo-param-setting"><input type="text" name="price" value="<?php echo count($row) ? htmlspecialchars($row['name']) : ''; ?>" size="40"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBNEWPRICETWO'), 'content' => JText::translate('VBOPRICEATTRHELP'))); ?> <?php echo JText::translate('VBNEWPRICETWO'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="attr" value="<?php echo count($row) ? htmlspecialchars((string)$row['attr']) : ''; ?>" size="40"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWPRICETHREE'); ?></div>
							<div class="vbo-param-setting"><?php echo $wiva; ?></div>
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
							<div class="vbo-param-setting"><input type="number" name="minlos" min="0" value="<?php echo count($row) ? $row['minlos'] : '0'; ?>" /></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOPRICETYPEMINHADV'), 'content' => JText::translate('VBOPRICETYPEMINHADVHELP'))); ?> <?php echo JText::translate('VBOPRICETYPEMINHADV'); ?></div>
							<div class="vbo-param-setting"><input type="number" name="minhadv" min="0" value="<?php echo count($row) ? $row['minhadv'] : '0'; ?>" /></div>
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
								<?php echo $vbo_app->printYesNoButtons('free_cancellation', JText::translate('VBYES'), JText::translate('VBNO'), (count($row) && $row['free_cancellation'] == 1 ? 1 : 0), 1, 0, 'toggleFreeCancellation();'); ?>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" id="canc_deadline" style="display: <?php echo count($row) && $row['free_cancellation'] == 1 ? 'flex' : 'none'; ?>;">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWPRICEFREECANCDLINE'); ?></div>
							<div class="vbo-param-setting">
								<input type="number" min="0" name="canc_deadline" value="<?php echo count($row) ? $row['canc_deadline'] : '7'; ?>" size="5"/>
							</div>
						</div>
						<div class="vbo-param-container vbo-param-nested" id="canc_policy" style="display: <?php echo count($row) && $row['free_cancellation'] == 1 ? 'flex' : 'none'; ?>;">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBNEWPRICECANCPOLICY'), 'content' => JText::translate('VBNEWPRICECANCPOLICYHELP'))); ?> <?php echo JText::translate('VBNEWPRICECANCPOLICY'); ?></div>
							<div class="vbo-param-setting">
								<textarea name="canc_policy" rows="5" cols="200" style="width: 350px; height: 130px;"><?php echo count($row) ? htmlspecialchars((string)$row['canc_policy']) : ''; ?></textarea>
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
	<input type="hidden" name="whereup" value="<?php echo $row['id']; ?>">
<?php
}
?>
	<input type="hidden" name="option" value="com_vikbooking">
	<?php echo JHtml::fetch('form.token'); ?>
</form>
