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
$editor = JEditor::getInstance(JFactory::getApplication()->get('editor'));
$sitelogo = VikBooking::getSiteLogo();
$backlogo = VikBooking::getBackendLogo();
$sendemailwhen = VikBooking::getSendEmailWhen();
$attachical = VikBooking::attachIcal();

?>
<fieldset class="adminform">
	<div class="vbo-params-wrap">
		<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMCOMPANY'); ?></legend>
		<div class="vbo-params-container">
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREEONE'); ?></div>
				<div class="vbo-param-setting"><input type="text" name="fronttitle" value="<?php echo JHtml::fetch('esc_attr', VikBooking::getFrontTitle()); ?>" size="30"/></div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGFOURLOGO'); ?></div>
				<div class="vbo-param-setting"><input type="file" name="sitelogo" size="35"/> <?php echo (strlen($sitelogo) > 0 ? '<a href="'.VBO_ADMIN_URI.'resources/'.$sitelogo.'" class="vbomodal" target="_blank">'.$sitelogo.'</a>' : ''); ?></div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGLOGOBACKEND'); ?></div>
				<div class="vbo-param-setting"><input type="file" name="backlogo" size="35"/> <?php echo (strlen($backlogo) > 0 ? '<a href="'.VBO_ADMIN_URI.'resources/'.$backlogo.'" class="vbomodal" target="_blank">'.$backlogo.'</a>' : '<a href="'.VBO_ADMIN_URI.'vikbooking.png" class="vbomodal" target="_blank">vikbooking.png</a>'); ?></div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSENDEMAILWHEN'); ?></div>
				<div class="vbo-param-setting"><select name="sendemailwhen"><option value="1"><?php echo JText::translate('VBCONFIGSMSSENDWHENCONFPEND'); ?></option><option value="2"<?php echo $sendemailwhen > 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSMSSENDWHENCONF'); ?></option></select></div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBCONFIGATTACHICAL'), 'content' => JText::translate('VBCONFIGATTACHICALHELP'))); ?> <?php echo JText::translate('VBCONFIGATTACHICAL'); ?></div>
				<div class="vbo-param-setting">
					<select name="attachical">
						<option value="1"<?php echo $attachical === 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSMSSENDTOADMIN') . ' + ' . JText::translate('VBCONFIGSMSSENDTOCUSTOMER'); ?></option>
						<option value="2"<?php echo $attachical === 2 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSMSSENDTOADMIN'); ?></option>
						<option value="3"<?php echo $attachical === 3 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSMSSENDTOCUSTOMER'); ?></option>
						<option value="0"<?php echo $attachical === 0 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBNO'); ?></option>
					</select>
				</div>
			</div>
			<div class="vbo-param-container vbo-param-container-full">
				<div class="vbo-param-label"><?php echo JText::translate('VBOTERMSCONDS'); ?></div>
				<div class="vbo-param-setting">
					<?php
					if (interface_exists('Throwable')) {
						/**
						 * With PHP >= 7 supporting throwable exceptions for Fatal Errors
						 * we try to avoid issues with third party plugins that make use
						 * of the WP native function get_current_screen().
						 * 
						 * @wponly
						 */
						try {
							echo $editor->display( "termsconds", VikBooking::getTermsConditions(), '100%', 350, 70, 20 );
						} catch (Throwable $t) {
							echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
						}
					} else {
						// we cannot catch Fatal Errors in PHP 5.x
						echo $editor->display( "termsconds", VikBooking::getTermsConditions(), '100%', 350, 70, 20 );
					}
					?>
				</div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGFOURORDMAILFOOTER'); ?></div>
				<div class="vbo-param-setting"><textarea name="footerordmail" rows="5" cols="60" style="min-height: 110px; width: 400px;"><?php echo htmlspecialchars(VikBooking::getFooterOrdMail()); ?></textarea></div>
			</div>
			<div class="vbo-param-container">
				<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGFOURFOUR'); ?></div>
				<div class="vbo-param-setting"><textarea name="disclaimer" rows="5" cols="60" style="min-height: 110px; width: 400px;"><?php echo htmlspecialchars(VikBooking::getDisclaimer()); ?></textarea></div>
			</div>
		</div>
	</div>
</fieldset>
