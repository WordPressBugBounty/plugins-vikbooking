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

if (VBOPlatformDetection::isWordPress() && function_exists('wp_enqueue_code_editor')) {
	/**
	 * @wponly - cannot load iFrame with FancyBox, so we use the BS's Modal
	 * WP >= 4.9.0
	 */
	wp_enqueue_code_editor(array('type' => 'php'));
}

echo $vbo_app->getJmodalScript($suffix = 'VboTplfiles', $hide_js = "VBOCore.emitEvent('vbo-dismiss-modal-tmplfile');");
echo $vbo_app->getJmodalHtml('VboTplfiles', JText::translate('VBOCONFIGEDITTMPLFILE'));

$editor = JEditor::getInstance(JFactory::getApplication()->get('editor'));
$document = JFactory::getDocument();
$document->addStyleSheet(VBO_SITE_URI.'resources/jquery.fancybox.css');
$document->addStyleSheet(VBO_ADMIN_URI.'resources/js_upload/colorpicker.css');
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery.fancybox.js');
JHtml::fetch('script', VBO_ADMIN_URI.'resources/js_upload/colorpicker.js');
JHtml::fetch('script', VBO_ADMIN_URI.'resources/js_upload/eye.js');
JHtml::fetch('script', VBO_ADMIN_URI.'resources/js_upload/utils.js');

$themesel = '<select name="theme">';
$themesel .= '<option value="default">default</option>';
$themes = glob(VBO_SITE_PATH.DS.'themes'.DS.'*');
$acttheme = VikBooking::getTheme();
if (count($themes) > 0) {
	$strip = VBO_SITE_PATH.DS.'themes'.DS;
	foreach ($themes as $th) {
		if (is_dir($th)) {
			$tname = str_replace($strip, '', $th);
			if ($tname != 'default') {
				$themesel .= '<option value="'.$tname.'"'.($tname == $acttheme ? ' selected="selected"' : '').'>'.$tname.'</option>';
			}
		}
	}
}
$themesel .= '</select>';
$firstwday = VikBooking::getFirstWeekDay();
?>
<div class="vbo-config-maintab-left">
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMLAYOUT'); ?></legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGFIRSTWDAY'); ?></div>
					<div class="vbo-param-setting"><select name="firstwday"><option value="0"<?php echo $firstwday == '0' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBSUNDAY'); ?></option><option value="1"<?php echo $firstwday == '1' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBMONDAY'); ?></option><option value="2"<?php echo $firstwday == '2' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBTUESDAY'); ?></option><option value="3"<?php echo $firstwday == '3' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBWEDNESDAY'); ?></option><option value="4"<?php echo $firstwday == '4' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBTHURSDAY'); ?></option><option value="5"<?php echo $firstwday == '5' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBFRIDAY'); ?></option><option value="6"<?php echo $firstwday == '6' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBSATURDAY'); ?></option></select></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREETEN'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="numcalendars" value="<?php echo VikBooking::numCalendars(); ?>" min="0" max="24"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGTHUMBSIZE'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="thumbsize" value="<?php echo VikBooking::getThumbSize(true); ?>" min="20" max="1000"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREENINE'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('showpartlyreserved', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::showPartlyReserved() ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREECHECKINOUTSTAT'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('showcheckinoutonly', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::showStatusCheckinoutOnly() ? 1 : 0), 1, 0); ?></div>
				</div>

				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGEMAILTEMPLATE'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-wrapper input-append">
							<button type="button" class="btn vbo-edit-tmpl" data-tmpl-path="<?php echo urlencode(VBO_SITE_PATH.DS.'helpers'.DS.'email_tmpl.php'); ?>"><i class="icon-edit"></i> <?php echo JText::translate('VBOCONFIGEDITTMPLFILE'); ?></button>
							<button type="button" class="btn vbo-inspector-btn" title="<?php echo addslashes(JText::translate('VBO_INSPECTOR_START')); ?>" data-inspectfile="email_tmpl.php"><?php VikBookingIcons::e('paint-brush'); ?></button>
							<button type="button" class="btn vbo-edit-tmpl vbo-preview-btn" title="<?php echo addslashes(JText::translate('VBOPREVIEW')); ?>" data-prew-type="email_tmpl.php" data-prew-path="<?php echo urlencode(VBO_SITE_PATH.DS.'helpers'.DS.'email_tmpl.php'); ?>"><?php VikBookingIcons::e('eye'); ?></button>
						</div>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGINVOICETEMPLATE'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-wrapper input-append">
							<button type="button" class="btn vbo-edit-tmpl" data-tmpl-path="<?php echo urlencode(VBO_SITE_PATH.DS.'helpers'.DS.'invoices'.DS.'invoice_tmpl.php'); ?>"><i class="icon-edit"></i> <?php echo JText::translate('VBOCONFIGEDITTMPLFILE'); ?></button>
							<button type="button" class="btn vbo-inspector-btn" title="<?php echo addslashes(JText::translate('VBO_INSPECTOR_START')); ?>" data-inspectfile="invoice_tmpl.php"><?php VikBookingIcons::e('paint-brush'); ?></button>
						</div>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOMANUALINVOICE'); ?></div>
					<div class="vbo-param-setting"><button type="button" class="btn vbo-edit-tmpl" data-tmpl-path="<?php echo urlencode(VBO_SITE_PATH.DS.'helpers'.DS.'invoices'.DS.'custom_invoice_tmpl.php'); ?>"><i class="icon-edit"></i> <?php echo JText::translate('VBOCONFIGEDITTMPLFILE'); ?></button></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGCHECKINTEMPLATE'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-wrapper input-append">
							<button type="button" class="btn vbo-edit-tmpl" data-tmpl-path="<?php echo urlencode(VBO_SITE_PATH.DS.'helpers'.DS.'checkins'.DS.'checkin_tmpl.php'); ?>"><i class="icon-edit"></i> <?php echo JText::translate('VBOCONFIGEDITTMPLFILE'); ?></button>
							<button type="button" class="btn vbo-inspector-btn" title="<?php echo addslashes(JText::translate('VBO_INSPECTOR_START')); ?>" data-inspectfile="checkin_tmpl.php"><?php VikBookingIcons::e('paint-brush'); ?></button>
						</div>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGCUSTCSSTPL'); ?></div>
					<div class="vbo-param-setting">
					<?php
					if (VBOPlatformDetection::isWordPress()) {
						/**
						 * @wponly  the path of the file is different in WP, it's inside /resources
						 */
						?>
						<button type="button" class="btn vbo-edit-tmpl" data-tmpl-path="<?php echo urlencode(VBO_SITE_PATH.DS.'resources'.DS.'vikbooking_custom.css'); ?>"><i class="icon-edit"></i> <?php echo JText::translate('VBOCONFIGEDITTMPLFILE'); ?></button>
						<?php
					} else {
						?>
						<button type="button" class="btn vbo-edit-tmpl" data-tmpl-path="<?php echo urlencode(VBO_SITE_PATH.DS.'vikbooking_custom.css'); ?>"><i class="icon-edit"></i> <?php echo JText::translate('VBOCONFIGEDITTMPLFILE'); ?></button>
						<?php
					}
					?>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFIGCUSTBACKCSSTPL'); ?></div>
					<div class="vbo-param-setting"><button type="button" class="btn vbo-edit-tmpl" data-tmpl-path="<?php echo urlencode(VBO_ADMIN_PATH.DS.'resources'.DS.'vikbooking_backendcustom.css'); ?>"><i class="icon-edit"></i> <?php echo JText::translate('VBOCONFIGEDITTMPLFILE'); ?></button></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREESIX'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('showfooter', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::showFooter() ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHEME'); ?></div>
					<div class="vbo-param-setting"><?php echo $themesel; ?></div>
				</div>
				<div class="vbo-param-container vbo-param-container-full">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREESEVEN'); ?></div>
					<div class="vbo-param-setting">
						<?php
						if (interface_exists('Throwable')) {
							/**
							 * With PHP >= 7 supporting throwable exceptions for Fatal Errors
							 * we try to avoid issues with third party plugins that make use of
							 * the WP native function get_current_screen() or any Joomla plugin.
							 */
							try {
								echo $editor->display( "intromain", VikBooking::getIntroMain(), '100%', 350, 70, 20 );
							} catch (Throwable $t) {
								echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
							}
						} else {
							// we cannot catch Fatal Errors in PHP 5.x
							echo $editor->display( "intromain", VikBooking::getIntroMain(), '100%', 350, 70, 20 );
						}
						?>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREEEIGHT'); ?></div>
					<div class="vbo-param-setting"><textarea name="closingmain" rows="5" cols="60" style="min-width: 400px;"><?php echo htmlspecialchars(VikBooking::getClosingMain()); ?></textarea></div>
				</div>
			</div>
		</div>
	</fieldset>
</div>

<div class="vbo-config-maintab-right">

	<?php
	/**
	 * Preferred colors for CSS styling
	 * 
	 * @since 	1.14 (J) - 1.4.0 (WP)
	 */
	$preferred_colors = VikBooking::getPreferredColors();
	?>
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend">
				<?php
				echo $vbo_app->createPopover(array('title' => JText::translate('VBO_PREF_COLORS'), 'content' => JText::translate('VBO_PREF_COLORS_HELP')));
				echo ' ' . JText::translate('VBO_PREF_COLORS');
				?>
			</legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_PREF_COLOR_TEXTS'); ?></div>
					<div class="vbo-param-setting">
						<span class="vbo-inspector-colorpicker-wrap">
							<span class="vbo-inspector-colorpicker vbo-prefcolorpicker-trig" data-prefcolortype="textcolor" style="background-color: <?php echo !empty($preferred_colors['textcolor']) ? $preferred_colors['textcolor'] : '#ffffff'; ?>;"><?php VikBookingIcons::e('palette'); ?></span>
						</span>
						<input type="hidden" name="pref_textcolor" id="vbo-pref-textcolor" value="<?php echo $preferred_colors['textcolor']; ?>" />
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_PREF_COLOR_BKGROUND'); ?></div>
					<div class="vbo-param-setting">
						<div class="vbo-param-setting-top">
							<span class="vbo-inspector-colorpicker-wrap">
								<span class="vbo-inspector-colorpicker vbo-prefcolorpicker-trig" data-prefcolortype="bgcolor" style="background-color: <?php echo !empty($preferred_colors['bgcolor']) ? $preferred_colors['bgcolor'] : '#ffffff'; ?>;"><?php VikBookingIcons::e('palette'); ?></span>
							</span>
							<span class="vbo-colorpicker-label"><?php echo JText::translate('VBO_BKGROUND_COL'); ?></span>
							<input type="hidden" name="pref_bgcolor" id="vbo-pref-bgcolor" value="<?php echo $preferred_colors['bgcolor']; ?>" />
						</div>
						<div class="vbo-param-setting-bottom">
							<span class="vbo-inspector-colorpicker-wrap">
								<span class="vbo-inspector-colorpicker vbo-prefcolorpicker-trig" data-prefcolortype="fontcolor" style="background-color: <?php echo !empty($preferred_colors['fontcolor']) ? $preferred_colors['fontcolor'] : '#ffffff'; ?>;"><?php VikBookingIcons::e('palette'); ?></span>
							</span>
							<span class="vbo-colorpicker-label"><?php echo JText::translate('VBO_FONT_COL'); ?></span>
							<input type="hidden" name="pref_fontcolor" id="vbo-pref-fontcolor" value="<?php echo $preferred_colors['fontcolor']; ?>" />
						</div>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_PREF_COLOR_BKGROUNDHOV'); ?></div>
					<div class="vbo-param-setting">
						<div class="vbo-param-setting-top">
							<span class="vbo-inspector-colorpicker-wrap">
								<span class="vbo-inspector-colorpicker vbo-prefcolorpicker-trig" data-prefcolortype="bgcolorhov" style="background-color: <?php echo !empty($preferred_colors['bgcolorhov']) ? $preferred_colors['bgcolorhov'] : '#ffffff'; ?>;"><?php VikBookingIcons::e('palette'); ?></span>
							</span>
							<span class="vbo-colorpicker-label"><?php echo JText::translate('VBO_BKGROUND_COL'); ?></span>
							<input type="hidden" name="pref_bgcolorhov" id="vbo-pref-bgcolorhov" value="<?php echo $preferred_colors['bgcolorhov']; ?>" />
						</div>
						<div class="vbo-param-setting-bottom">
							<span class="vbo-inspector-colorpicker-wrap">
								<span class="vbo-inspector-colorpicker vbo-prefcolorpicker-trig" data-prefcolortype="fontcolorhov" style="background-color: <?php echo !empty($preferred_colors['fontcolorhov']) ? $preferred_colors['fontcolorhov'] : '#ffffff'; ?>;"><?php VikBookingIcons::e('palette'); ?></span>
							</span>
							<span class="vbo-colorpicker-label"><?php echo JText::translate('VBO_FONT_COL'); ?></span>
							<input type="hidden" name="pref_fontcolorhov" id="vbo-pref-fontcolorhov" value="<?php echo $preferred_colors['fontcolorhov']; ?>" />
						</div>
					</div>
				</div>
				<div id="vbo-pref-color-examples" class="vbo-param-container" style="display: none;">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_PREF_COLOR_EXAMPLERES'); ?></div>
					<div class="vbo-param-setting">
						<div class="vbo-pref-color-example">
							<h4><?php echo JText::translate('VBO_PREF_COLOR_TEXTS'); ?></h4>
						</div>
						<div class="vbo-pref-color-example">
							<button type="button" class="btn btn-small"><?php echo JText::translate('VBO_PREF_COLOR_BKGROUND'); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</fieldset>

	<script type="text/javascript">
		
		/**
		 * Composes the necessary inline style tag for the styles examples.
		 */
		function vboPrefColorsApplyExample() {
			// compose inline style tag
			var style = '';
			style += '<style type="text/css" id="vbo-pref-color-livecss">';
			style += '.vbo-pref-color-example h4 { color: ' + jQuery('#vbo-pref-textcolor').val() + '; }' + "\n";
			style += '.vbo-pref-color-example button { background-color: ' + jQuery('#vbo-pref-bgcolor').val() + '; color: ' + jQuery('#vbo-pref-fontcolor').val() + '; }' + "\n";
			style += '.vbo-pref-color-example button:hover { background-color: ' + jQuery('#vbo-pref-bgcolorhov').val() + '; color: ' + jQuery('#vbo-pref-fontcolorhov').val() + '; }' + "\n";
			style += '</style>';
			if (jQuery('#vbo-pref-color-livecss').length) {
				// remove current inline style tag
				jQuery('#vbo-pref-color-livecss').remove();
			}
			// append styling to example window and show it
			jQuery('#vbo-pref-color-examples').append(style).show();
		}

		jQuery(function() {
			/**
			 * Register color-picker for preferred colors.
			 */
			jQuery('.vbo-prefcolorpicker-trig').ColorPicker({
				color: '#ffffff',
				onShow: function(colpkr, el) {
					var cur_color = jQuery(el).css('backgroundColor');
					jQuery(el).ColorPickerSetColor(vboRgb2Hex(cur_color));
					jQuery(colpkr).show();
					return false;
				},
				onChange: function(hsb, hex, rgb, el) {
					var element = jQuery(el);
					var el_type = element.attr('data-prefcolortype');
					element.css('backgroundColor', '#'+hex);
					if (el_type && jQuery('#vbo-pref-' + el_type).length) {
						jQuery('#vbo-pref-' + el_type).val('#'+hex);
					}
					vboPrefColorsApplyExample();
				},
				onSubmit: function(hsb, hex, rgb, el) {
					var element = jQuery(el);
					var el_type = element.attr('data-prefcolortype');
					element.css('backgroundColor', '#'+hex);
					if (el_type && jQuery('#vbo-pref-' + el_type).length) {
						jQuery('#vbo-pref-' + el_type).val('#'+hex);
					}
					element.ColorPickerHide();
					vboPrefColorsApplyExample();
				}
			});
		});

	</script>

<?php
$colortags = VikBooking::loadBookingsColorTags();
$tagsrules = VikBooking::loadColorTagsRules();
$opt_js_rules = '';
foreach ($tagsrules as $tagk => $tagv) {
	$opt_js_rules .= '<option value=\"'.$tagk.'\">'.addslashes(JText::translate($tagv)).'</option>';
}
?>
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMBOOKTAGS'); ?></legend>
			<div class="vbo-params-container">
			<?php
			foreach ($colortags as $ctagk => $ctagv) {
				?>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate($ctagv['name']); ?></div>
					<div class="vbo-param-setting">
						<div class="vbo-colortag-square" style="background-color: <?php echo $ctagv['color']; ?>; color: <?php echo VikBooking::getBestColorContrast($ctagv['color']); ?>;"><i class="vboicn-price-tags"></i></div>
						<input type="hidden" name="bctagname[]" class="bctagname" value="<?php echo $ctagv['name']; ?>" />
						<input type="hidden" name="bctagcolor[]" class="bctagcolor" value="<?php echo $ctagv['color']; ?>" />
						<select name="bctagrule[]" style="margin: 0; vertical-align: top;">
						<?php
						foreach ($tagsrules as $tagk => $tagv) {
							?>
							<option value="<?php echo $tagk; ?>"<?php echo !empty($tagk) && $tagk == $ctagv['rule'] ? ' selected="selected"' : ''; ?>><?php echo JText::translate($tagv); ?></option>
							<?php
						}
						?>
						</select>
						<div style="float: right;"><button class="btn btn-danger vbo-colortag-rm" type="button">&times;</button></div>
					</div>
				</div>
				<?php
			}
			?>
				<div class="vbo-param-container" id="vbo-colortag-lasttr">
					<div class="vbo-param-label"><button class="btn vbo-colortag-add" type="button"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBOCOLORTAGADD'); ?></button></div>
					<div class="vbo-param-setting"> </div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><a class="btn btn-danger" href="index.php?option=com_vikbooking&amp;task=config&amp;reset_tags=1" onclick="return confirm('<?php echo addslashes(JText::translate('VBDELCONFIRM')); ?>');"><i class="icon-remove"></i> <?php echo JText::translate('VBOCOLORTAGRMALL'); ?></a></div>
					<div class="vbo-param-setting"> </div>
				</div>
			</div>
		</a>
	</fieldset>
</div>

<script type="text/javascript">
var vboHexDigits = new Array ("0","1","2","3","4","5","6","7","8","9","a","b","c","d","e","f");

function vboRgb2Hex(rgb) {
	var rgb_match = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([0-9.]+))?\)$/);
	if (!rgb_match) {
		return rgb;
	}
	return "#" + vboHex(rgb_match[1]) + vboHex(rgb_match[2]) + vboHex(rgb_match[3]);
}

function vboHex(x) {
	return isNaN(x) ? "00" : vboHexDigits[(x - x % 16) / 16] + vboHexDigits[x % 16];
}

jQuery(function() {

	jQuery(".vbo-edit-tmpl").click(function() {
		var vbo_tmpl_path = jQuery(this).attr("data-tmpl-path");
		var vbo_prew_path = jQuery(this).attr("data-prew-path");
		if (!vbo_tmpl_path && !vbo_prew_path) {
			return;
		}
		var basetask = !vbo_tmpl_path ? 'tmplfileprew' : 'edittmplfile';
		var basepath = !vbo_tmpl_path ? vbo_prew_path : vbo_tmpl_path;
		// we use the BS's Modal to open the template files editing page
		vboOpenJModalVboTplfiles('VboTplfiles', "index.php?option=com_vikbooking&task=" + basetask + "&path=" + basepath + "&tmpl=component");
	});

	jQuery(".vbo-colortag-add").click(function() {
		jQuery("#vbo-colortag-lasttr").before(
			"<div class=\"vbo-param-container\">"+
			"<div class=\"vbo-param-label\"> <input type=\"text\" name=\"bctagname[]\" class=\"bctagname\" value=\"\" placeholder=\"<?php echo addslashes(JText::translate('VBOCOLORTAGADDPLCHLD')); ?>\" size=\"25\" /> </div>"+
			"<div class=\"vbo-param-setting\">"+
			"<div class=\"vbo-colortag-square\" style=\"\"><i class=\"vboicn-price-tags\"></i></div>"+
			"<input type=\"hidden\" name=\"bctagcolor[]\" class=\"bctagcolor\" value=\"#ffffff\" />"+
			"<select name=\"bctagrule[]\" style=\"margin: 0; vertical-align: top;\"><?php echo $opt_js_rules; ?></select>"+
			"<div style=\"float: right;\"><button class=\"btn btn-danger vbo-colortag-rm\" type=\"button\">&times;</button></div>"+
			"</div>"+
			"</div>"
		);
		jQuery('.vbo-colortag-square').ColorPicker({
			color: '#ffffff',
			onShow: function (colpkr, el) {
				var cur_color = jQuery(el).css('backgroundColor');
				jQuery(el).ColorPickerSetColor(vboRgb2Hex(cur_color));
				jQuery(colpkr).show();
				return false;
			},
			onChange: function (hsb, hex, rgb, el) {
				jQuery(el).css('backgroundColor', '#'+hex);
				jQuery(el).parent().find('.bctagcolor').val('#'+hex);
			},
			onSubmit: function(hsb, hex, rgb, el) {
				jQuery(el).css('backgroundColor', '#'+hex);
				jQuery(el).parent().find('.bctagcolor').val('#'+hex);
				jQuery(el).ColorPickerHide();
			},
		});
	});

	jQuery(document.body).on('click', '.vbo-colortag-rm', function() {
		jQuery(this).closest('.vbo-param-container').remove();
	});

	jQuery('.vbo-colortag-square').ColorPicker({
		color: '#ffffff',
		onShow: function (colpkr, el) {
			var cur_color = jQuery(el).css('backgroundColor');
			jQuery(el).ColorPickerSetColor(vboRgb2Hex(cur_color));
			jQuery(colpkr).show();
			return false;
		},
		onChange: function (hsb, hex, rgb, el) {
			jQuery(el).css('backgroundColor', '#'+hex);
			jQuery(el).parent().find('.bctagcolor').val('#'+hex);
		},
		onSubmit: function(hsb, hex, rgb, el) {
			jQuery(el).css('backgroundColor', '#'+hex);
			jQuery(el).parent().find('.bctagcolor').val('#'+hex);
			jQuery(el).ColorPickerHide();
		},
	});
});
</script>
