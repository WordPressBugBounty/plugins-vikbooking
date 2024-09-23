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
 * MydataAade driver edit electronic invoice
 */

$vbo_app = VikBooking::getVboApplication();
$data 	 = !is_array($data) ? array() : $data;

// load codemirror editor
$editor = JEditor::getInstance('codemirror');

// get transaction data, if any
$trans_data = null;
if (!empty($data['trans_data'])) {
	$trans_data = json_decode($data['trans_data']);
}

// check whether it's the main invoice or the correlated one
$is_correlated = !empty($data['correlated_invoice']);

// modal title
$modal_title = 'Edit XML electronic invoice #' . $data['number'] . ' of ' . $data['created_on'];
if ($is_correlated) {
	$modal_title = 'Edit XML for correlated invoice - Booking ID ' . $data['idorder'];
}

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

<input type="hidden" name="driveraction" value="<?php echo $is_correlated ? 'updateCorrelatedXmlEInvoice' : 'updateXmlEInvoice'; ?>" />
<input type="hidden" name="einvid" value="<?php echo $data['id']; ?>" />
<?php
if ($is_correlated) {
	?>
<input type="hidden" name="envfeebid" value="<?php echo $data['idorder']; ?>" />
	<?php
}
?>

<fieldset>
	<legend class="adminlegend"><?php echo $modal_title; ?></legend>
	<div class="vbo-driver-tarea-cont" style="display: inline-block; width: 98%; padding: 5px;">
		<?php
		try {
			echo $editor->display("newxml", ($is_correlated ? $data['correlated_invoice']['xml'] : $data['xml']), '100%', 300, 70, 20);
		} catch (Throwable $t) {
			echo $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
		}
		?>
	</div>
<?php
if (!$is_correlated && is_object($trans_data)) {
	if (!empty($trans_data->trans_dtime)) {
		?>
	<p class="info">Transmitted to myDATA on <?php echo $trans_data->trans_dtime; ?></p>
		<?php
	}
	if (!empty($trans_data->invoice_uid)) {
		?>
	<p class="info">myDATA UID <?php echo $trans_data->invoice_uid; ?></p>
		<?php
	}
	if (!empty($trans_data->invoice_mark)) {
		?>
	<p class="info">myDATA Mark <?php echo $trans_data->invoice_mark; ?></p>
		<?php
	}
	if (!empty($trans_data->invoice_qrcode)) {
		?>
	<p class="info">Invoice QR Code URL <small><?php echo $trans_data->invoice_qrcode; ?></small></p>
		<?php
	}
} elseif ($is_correlated && !empty($data['correlated_invoice']['transmission'])) {
	if (!empty($data['correlated_invoice']['transmission']['ts'])) {
		?>
	<p class="info">Transmitted to myDATA on <?php echo date('Y-m-d H:i:s', $data['correlated_invoice']['transmission']['ts']); ?></p>
		<?php
	}
	if (!empty($data['correlated_invoice']['transmission']['uid'])) {
		?>
	<p class="info">myDATA UID <?php echo $data['correlated_invoice']['transmission']['uid']; ?></p>
		<?php
	}
	if (!empty($data['correlated_invoice']['transmission']['mark'])) {
		?>
	<p class="info">myDATA Mark <?php echo $data['correlated_invoice']['transmission']['mark']; ?></p>
		<?php
	}
	if (!empty($data['correlated_invoice']['transmission']['qrurl'])) {
		?>
	<p class="info">Invoice QR Code URL <small><?php echo $data['correlated_invoice']['transmission']['qrurl']; ?></small></p>
		<?php
	}
}
?>
</fieldset>

<script type="text/javascript">
jQuery(function() {
	vboShowDriverContent();
});
</script>
