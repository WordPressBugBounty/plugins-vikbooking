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

$fpath = $this->fpath;

$editor = JEditor::getInstance('codemirror');
$fcode = '';
$fp = !empty($fpath) ? fopen($fpath, "rb") : false;
if (empty($fpath) || $fp === false) {
	?>
	<p class="err"><?php echo JText::translate('VBOTMPLFILENOTREAD'); ?></p>
	<?php
} else {
	while (!feof($fp)) {
		$fcode .= fread($fp, 8192);
	}
	fclose($fp);
?>
<form name="adminForm" id="adminForm" action="index.php" method="post">
	<fieldset class="adminform">
		<legend class="adminlegend"><?php echo JText::translate('VBOEDITTMPLFILE'); ?></legend>
		<p class="vbo-path-tmpl-file"><?php echo $fpath; ?></p>
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
				echo $editor->display("cont", $fcode, '100%', 300, 70, 20, $buttons = false, $id = "vik_editor_cont", $asset = null, $author = null, $params = ['syntax' => 'php']);
			} catch (Throwable $t) {
				echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
			}
		} else {
			// we cannot catch Fatal Errors in PHP 5.x
			echo $editor->display("cont", $fcode, '100%', 300, 70, 20, $buttons = false, $id = "vik_editor_cont", $asset = null, $author = null, $params = ['syntax' => 'php']);
		}
		?>
		<br clear="all" />
		<p style="text-align: center;">
			<button type="button" class="btn btn-success" onclick="vboSubmitTmplFile(this);"><?php echo JText::translate('VBOSAVETMPLFILE'); ?></button>
		</p>
	</fieldset>
	<input type="hidden" name="path" value="<?php echo $fpath; ?>">
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="savetmplfile" />
	<?php echo JHtml::fetch('form.token'); ?>
</form>

<script type="text/javascript">
	function vboSubmitTmplFile(elem) {
		/**
		 * @wponly 	the real textarea may not update with the new codemirror content
		 * 			and so we force the update of the textarea before submitting the form.
		 */
		try {
			Joomla.editors.instances['vik_editor_cont'].element.codemirror.save();
		} catch (err) {
			console.error(err);
		}
		jQuery(elem).closest('form').submit();
	}
</script>
<?php
}
