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
$breakdown = array();
if (count($row) && !empty($row['breakdown'])) {
	$get_breakdown = json_decode($row['breakdown'], true);
	if (is_array($get_breakdown) && count($get_breakdown) > 0) {
		$breakdown = $get_breakdown;
	}
}
$breakdown_str = '';
if (count($breakdown) > 0) {
	foreach ($breakdown as $bkey => $subtax) {
		$breakdown_str .= '<div class="add-tax-breakdown-cont">'."\n";
		$breakdown_str .= '<div class="add-tax-breakdown-remove"><button type="button" class="btn btn-danger">&times;</button></div>'."\n";
		$breakdown_str .= '<div class="add-tax-breakdown-name">'."\n";
		$breakdown_str .= '<span>'.JText::translate('VBOTAXNAMEBKDWN').'</span>'."\n";
		$breakdown_str .= '<input type="text" name="breakdown_name[]" value="'.$subtax['name'].'" size="30" placeholder="'.JText::translate('VBOTAXNAMEBKDWNEX').'"/>'."\n";
		$breakdown_str .= '</div>'."\n";
		$breakdown_str .= '<div class="add-tax-breakdown-rate">'."\n";
		$breakdown_str .= '<span>'.JText::translate('VBOTAXRATEBKDWN').'</span>'."\n";
		$breakdown_str .= '<input type="number" step="any" min="0" name="breakdown_rate[]" value="'.$subtax['aliq'].'" placeholder="0.00"/>'."\n";
		$breakdown_str .= '</div>'."\n";
		$breakdown_str .= '</div>'."\n";
	}
}
?>
<form name="adminForm" id="adminForm" action="index.php" method="post">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOADMINLEGENDDETAILS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWIVAONE'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="aliqname" value="<?php echo count($row) ? htmlspecialchars($row['name']) : ''; ?>" size="30"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWIVATWO'); ?></div>
							<div class="vbo-param-setting"><input type="number" step="any" min="0" name="aliqperc" value="<?php echo count($row) ? $row['aliq'] : ''; ?>" /> %</div>
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
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWIVATAXCAP'); ?> <?php echo $vbo_app->createPopover(array('title' => JText::translate('VBNEWIVATAXCAP'), 'content' => JText::translate('VBNEWIVATAXCAPHELP'))); ?></div>
							<div class="vbo-param-setting"><input type="number" step="any" min="0" name="taxcap" value="<?php echo count($row) ? $row['taxcap'] : ''; ?>" /> (<?php echo VikBooking::getCurrencySymb(); ?>)</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label">
								<a href="javascript: void(0);" class="vbo-link-add"><i class="<?php echo VikBookingIcons::i('plus-circle'); ?>" style="float: none;"></i> <?php echo JText::translate('VBOADDTAXBKDWN'); ?></a>
							</div>
							<div class="vbo-param-setting">
								<div id="breakdown-cont"><?php echo $breakdown_str; ?></div>
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

<div style="display: none;" id="add-breakdown">
	<div class="add-tax-breakdown-cont">
		<div class="add-tax-breakdown-remove"><button type="button" class="btn btn-danger">&times;</button></div>
		<div class="add-tax-breakdown-name">
			<span><?php echo JText::translate('VBOTAXNAMEBKDWN'); ?></span>
			<input type="text" name="breakdown_name[]" value="" size="30" placeholder="<?php echo JText::translate('VBOTAXNAMEBKDWNEX'); ?>"/>
		</div>
		<div class="add-tax-breakdown-rate">
			<span><?php echo JText::translate('VBOTAXRATEBKDWN'); ?></span>
			<input type="number" step="any" min="0" name="breakdown_rate[]" value="" placeholder="0.00"/>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(".vbo-link-add").click(function() {
		jQuery("#breakdown-cont").append(jQuery("#add-breakdown").html());
	});
	jQuery("body").on("click", ".add-tax-breakdown-remove", function() {
		jQuery(this).parent().remove();
	});
});
</script>
