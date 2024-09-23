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

/**
 * AgenziaEntrate driver edit electronic invoice
 */

$vbo_app = VikBooking::getVboApplication();
$data 	 = !is_array($data) ? array() : $data;

// load codemirror editor
$editor = JEditor::getInstance('codemirror');

?>
<style type="text/css">
/**
 * We need this CSS hack for Codemirror to properly render the XML code.
 * Do not use !important or JavaScript will not be able to hide the modal window.
 */
.vbo-modal-overlay-block-einvoicing, .vbo-info-overlay-driver-content {
	display: block;
}
</style>

<input type="hidden" name="driveraction" value="updateXmlEInvoice" />
<input type="hidden" name="einvid" value="<?php echo $data['id']; ?>" />

<fieldset>
	<legend class="adminlegend">Modifica XML fattura elettronica #<?php echo $data['number']; ?> del <?php echo $data['created_on']; ?></legend>
	<div class="vbo-driver-tarea-cont" style="display: inline-block; width: 98%; padding: 5px;">
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
				echo $editor->display("newxml", $data['xml'], '100%', 300, 70, 20);
			} catch (Throwable $t) {
				echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
			}
		} else {
			// we cannot catch Fatal Errors in PHP 5.x
			echo $editor->display("newxml", $data['xml'], '100%', 300, 70, 20);
		}
		?>
	</div>
</fieldset>

<script type="text/javascript">
jQuery(function() {
	vboShowDriverContent();
});
</script>
