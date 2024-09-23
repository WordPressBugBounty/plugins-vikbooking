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
?>
<script type="text/javascript">
function showResizeSel() {
	if (document.adminForm.autoresize.checked == true) {
		document.getElementById('resizesel').style.display='inline-block';
	} else {
		document.getElementById('resizesel').style.display='none';
	}
	return true;
}
function vboApplyPresetIcon(elem) {
	if (!elem.value || !elem.value.length) {
		return;
	}
	var fontclass = elem.value;
	jQuery('#carattextimg').val('<i class="' + fontclass + '"></i>').trigger('keyup');
}
function vboUpdateIconPreview() {
	var cont = jQuery('#carattextimg').val();
	var preview_cont = '';
	if (cont.length && cont.indexOf('<i') >= 0) {
		preview_cont = '<span class="vbo-carat-fonticon-preview-inner">' + cont + '</span>';
	}
	jQuery('.vbo-carat-fonticon-preview').html(preview_cont);
}
jQuery(document).ready(function() {
	jQuery('#idrooms').select2();
	if (jQuery('#vbo-preset-icons').length) {
		jQuery('#vbo-preset-icons').select2();
	}
	jQuery('.vbo-select-all').click(function() {
		var nextsel = jQuery(this).next("select");
		nextsel.find("option").prop('selected', true);
		nextsel.trigger('change');
	});
	jQuery('#carattextimg').trigger('keyup');
});
</script>
<form name="adminForm" id="adminForm" action="index.php" method="post" enctype="multipart/form-data">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOADMINLEGENDDETAILS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCARATONE'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="caratname" value="<?php echo count($row) ? htmlspecialchars($row['name']) : ''; ?>" size="40"/></div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOCARATIMGORICON'), 'content' => JText::translate('VBOCARATIMGORICONHELP'))); ?> <?php echo JText::translate('VBNEWCARATTWO'); ?></div>
							<div class="vbo-param-setting">
								<div class="vbo-param-setting-block">
									<?php echo (count($row) && !empty($row['icon']) && is_file(VBO_SITE_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$row['icon']) ? "<img src=\"".VBO_SITE_URI."resources/uploads/".$row['icon']."\"/>" : ""); ?>
									<input type="file" name="caraticon" size="35"/>
								</div>
								<div class="vbo-param-setting-block">
									<span class="vbo-resize-lb-cont">
										<label style="display: inline;" for="autoresize"><?php echo JText::translate('VBNEWOPTNINE'); ?></label> 
										<input type="checkbox" id="autoresize" name="autoresize" value="1" onclick="showResizeSel();"/> 
									</span>
									<span id="resizesel" style="display: none;"><span><?php echo JText::translate('VBNEWOPTTEN'); ?></span><input type="number" name="resizeto" value="250" min="0" class="vbo-medium-input"/> px</span>
								</div>
							</div>
						</div>
					<?php
					if (count($this->preset_icons)) {
						?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBOFONTICNSPREINST'); ?></div>
							<div class="vbo-param-setting">
								<select id="vbo-preset-icons" onchange="vboApplyPresetIcon(this);">
									<option value="">-----</option>
								<?php
								foreach ($this->preset_icons as $preset_icon) {
									?>
									<option value="<?php echo $preset_icon['class']; ?>"><?php echo $preset_icon['name']; ?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
						<?php
					}
					?>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCARATTHREE'); ?></div>
							<div class="vbo-param-setting">
								<input type="text" name="carattextimg" id="carattextimg" value="<?php echo count($row) ? htmlspecialchars($row['textimg']) : ''; ?>" size="40" onkeyup="vboUpdateIconPreview();"/>
								<span class="vbo-carat-fonticon-preview"></span>
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
							<div class="vbo-param-label"><?php echo JText::translate('VBOROOMSASSIGNED'); ?></div>
							<div class="vbo-param-setting">
								<span class="vbo-select-all"><?php echo JText::translate('VBOSELECTALL'); ?></span>
								<select name="idrooms[]" multiple="multiple" id="idrooms">
								<?php
								foreach ($this->allrooms as $rid => $room) {
									$is_room_assigned = (count($row) && is_array($room['idcarat']) && in_array((string)$row['id'], $room['idcarat']));
									?>
									<option value="<?php echo $rid; ?>"<?php echo $is_room_assigned ? ' selected="selected"' : ''; ?>><?php echo $room['name']; ?></option>
									<?php
								}
								?>
								</select>
							</div>
						</div>
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBOPOSITIONORDERING'); ?></div>
							<div class="vbo-param-setting">
								<input type="number" name="ordering" value="<?php echo count($row) ? $row['ordering'] : ''; ?>"/>
							<?php
							if (!count($row)) {
								?>
								<span class="vbo-param-setting-comment"><?php echo JText::translate('VBOPOSITIONORDERINGHELP'); ?></span>
								<?php
							}
							?>
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
