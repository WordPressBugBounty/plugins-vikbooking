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

$editor = JEditor::getInstance(JFactory::getApplication()->get('editor'));
?>
<form name="adminForm" id="adminForm" action="index.php" method="post">
	<div class="vbo-admin-container">
		<div class="vbo-config-maintab-left">
			<fieldset class="adminform">
				<div class="vbo-params-wrap">
					<legend class="adminlegend"><?php echo JText::translate('VBOADMINLEGENDDETAILS'); ?></legend>
					<div class="vbo-params-container">
						<div class="vbo-param-container">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCATONE'); ?></div>
							<div class="vbo-param-setting"><input type="text" name="catname" value="<?php echo count($row) ? htmlspecialchars($row['name']) : ''; ?>" size="40"/></div>
						</div>
						<div class="vbo-param-container vbo-param-container-full">
							<div class="vbo-param-label"><?php echo JText::translate('VBNEWCATDESCR'); ?></div>
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
										echo $editor->display( "descr", (count($row) ? $row['descr'] : ""), '100%', 300, 70, 20 );
									} catch (Throwable $t) {
										echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
									}
								} else {
									// we cannot catch Fatal Errors in PHP 5.x
									echo $editor->display( "descr", (count($row) ? $row['descr'] : ""), '100%', 300, 70, 20 );
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
