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

$currencysymb = VikBooking::getCurrencySymb();
$nowdf = VikBooking::getDateFormat();
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator();
$ptmpl = VikRequest::getString('tmpl', '', 'request');
$pitemid = VikRequest::getInt('Itemid', '', 'request');

$room_names = array();
$tot_adults = 0;
$tot_children = 0;
foreach ($this->orderrooms as $v) {
	$room_names[] = $v['name'];
	$tot_adults += $v['adults'];
	$tot_children += $v['children'];
}
$otacurrency = '';
if (!empty($this->ord['channel'])) {
	$otacurrency = strlen($this->ord['chcurrency']) > 0 ? $this->ord['chcurrency'] : '';
}
?>

<div id="vbdialog-overlay" style="display: none;">
	<a class="vbdialog-overlay-close" href="javascript: void(0);"></a>
	<div class="vbdialog-inner vbdialog-reqinfo">
		<h3 id="vbo-overlay-title"></h3>
		<div class="vbo-overlay-checkin-body"></div>
	</div>
</div>

<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=storesignature'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post" name="vbo_sign_form" id="vbo_sign_form">

	<div class="vbo-sign-bookdet-container">
		<div class="vbo-sign-bookdet-wrap">
			<div class="vbo-sign-bookdet-head">
				<span><?php echo JText::translate('VBBOOKINGDATE'); ?></span>
			</div>
			<div class="vbo-sign-bookdet-foot">
				<?php echo date(str_replace("/", $datesep, $df).' H:i', $this->ord['ts']); ?>
			</div>
		</div>
		<?php
		if (count($this->customer)) {
		?>
		<div class="vbo-sign-bookdet-wrap">
			<div class="vbo-sign-bookdet-head">
				<span><?php echo JText::translate('VBOCUSTOMERNOMINATIVE'); ?></span>
			</div>
			<div class="vbo-sign-bookdet-foot">
				<?php echo ltrim($this->customer['first_name'].' '.$this->customer['last_name']); ?>
			</div>
		</div>
		<?php
		}
		?>
		<div class="vbo-sign-bookdet-wrap">
			<div class="vbo-sign-bookdet-head">
				<span><?php echo JText::translate('VBOROOMSBOOKED'); ?></span>
			</div>
			<div class="vbo-sign-bookdet-foot">
				<?php echo implode(', ', $room_names); ?>
			</div>
		</div>
		<div class="vbo-sign-bookdet-wrap">
			<div class="vbo-sign-bookdet-head">
				<span><?php echo JText::translate('VBOSIGNATUREGUESTS'); ?></span>
			</div>
			<div class="vbo-sign-bookdet-foot">
				<?php echo ($tot_adults > 0 ? $tot_adults.' '.($tot_adults > 1 ? JText::translate('VBSEARCHRESADULTS') : JText::translate('VBSEARCHRESADULT')).($tot_children > 0 ? ', ' : '') : ''); ?>
				<?php echo ($tot_children > 0 ? $tot_children.' '.($tot_children > 1 ? JText::translate('VBSEARCHRESCHILDREN') : JText::translate('VBSEARCHRESCHILD')) : ''); ?>
			</div>
		</div>
		<div class="vbo-sign-bookdet-wrap">
			<div class="vbo-sign-bookdet-head">
				<span><?php echo JText::translate('VBDAYS'); ?></span>
			</div>
			<div class="vbo-sign-bookdet-foot">
				<?php echo $this->ord['days']; ?>
			</div>
		</div>
		<div class="vbo-sign-bookdet-wrap">
			<div class="vbo-sign-bookdet-head">
				<span><?php echo JText::translate('VBPICKUP'); ?></span>
			</div>
			<div class="vbo-sign-bookdet-foot">
			<?php
			$checkin_info = getdate($this->ord['checkin']);
			$short_wday = JText::translate('VB'.strtoupper(substr($checkin_info['weekday'], 0, 3)));
			?>
				<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $this->ord['checkin']); ?>
			</div>
		</div>
		<div class="vbo-sign-bookdet-wrap">
			<div class="vbo-sign-bookdet-head">
				<span><?php echo JText::translate('VBRETURN'); ?></span>
			</div>
			<div class="vbo-sign-bookdet-foot">
			<?php
			$checkout_info = getdate($this->ord['checkout']);
			$short_wday = JText::translate('VB'.strtoupper(substr($checkout_info['weekday'], 0, 3)));
			?>
				<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $this->ord['checkout']); ?>
			</div>
		</div>
		<div class="vbo-sign-bookdet-wrap">
			<div class="vbo-sign-bookdet-head">
				<span><?php echo JText::translate('VBTOTAL'); ?></span>
			</div>
			<div class="vbo-sign-bookdet-foot">
				<?php echo (strlen($otacurrency) > 0 ? '('.$otacurrency.') '.$currencysymb : $currencysymb); ?> <?php echo VikBooking::numberFormat($this->ord['total']); ?>
			</div>
		</div>
	</div>

	<?php
	$signpad_style = 'display: flex;';
	if (count($this->customer) && !empty($this->customer['signature'])) {
		$signpad_style = 'display: none;';
		?>
	<div class="vbo-signature-container" id="fake-signature-container">
		<div class="vbo-signature-pad">
			<div class="vbo-signature-pad-head">
				<p class="vbo-current-signature-p"><?php echo JText::translate('VBOCURRENTSIGNATURE'); ?></p>
			</div>
			<div class="vbo-signature-pad-body">
				<div class="vbo-signature-currentimg"><img src="<?php echo VBO_ADMIN_URI; ?>resources/idscans/<?php echo $this->customer['signature'].'?'.time(); ?>"></div>
			</div>
			<div class="vbo-signature-pad-footer">
				<div class="vbo-signature-signabove"></div>
				<div class="vbo-signature-cmds">
					<div class="vbo-signature-cmd"></div>
					<div class="vbo-signature-cmd">
						<button type="button" class="btn btn-large vbo-pref-color-btn" onclick="vboShowSignPad();"><i class="vboicn-quill"></i> <?php echo JText::translate('VBOSIGNATUREAGAIN'); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
		<?php
	}
	$termsconds = VikBooking::getTermsConditions();
	$empty_termsconds = !(strlen(trim(strip_tags($termsconds))) > 0);
	?>
	<div class="vbo-signature-container" id="real-signature-container" style="<?php echo $signpad_style; ?>">
		<div id="vbo-signature-pad" class="vbo-signature-pad">
			<div class="vbo-signature-pad-head">
			<?php
			if (!$empty_termsconds) {
			?>
				<div class="vbo-signature-pad-head-terms">
					<a href="javascript: void(0);" onclick="vboUpdateModal('<?php echo addslashes(JText::translate('VBOTERMSCONDS')); ?>', '.termsconds', true);"><i class="vboicn-link"></i> <?php echo JText::translate('VBOTERMSCONDSIACCEPT'); ?></a>
					<span><input type="checkbox" name="termsconds" id="termsconds" checked="checked"></span>
				</div>
			<?php
			}
			?>
			</div>
			<div class="vbo-signature-pad-body">
				<canvas></canvas>
			</div>
			<div class="vbo-signature-pad-footer">
				<div class="vbo-signature-signabove">
					<span><i class="vboicn-quill"></i> <?php echo JText::translate('VBOSIGNATURESIGNABOVE'); ?></span>
				</div>
				<div class="vbo-signature-cmds">
					<div class="vbo-signature-cmd">
						<button type="button" class="btn btn-large vbo-pref-color-btn" onclick="vboConfirmGenerate(1);"><i class="vboicn-checkmark"></i> <?php echo JText::translate('VBOSIGNATURESAVE'); ?></button>
					</div>
					<div class="vbo-signature-cmd">
						<button type="button" class="btn btn-large vbo-pref-color-btn-secondary" onclick="vboClearSignPad();"><i class="vboicn-bin"></i> <?php echo JText::translate('VBOSIGNATURECLEAR'); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="storesignature" />
	<input type="hidden" name="ts" value="<?php echo $this->ord['ts']; ?>" />
	<input type="hidden" name="sid" value="<?php echo $this->ord['sid']; ?>" />
	<input type="hidden" name="signature" id="signature-data" value="" />
	<input type="hidden" name="pad_width" id="pad_width" value="" />
	<input type="hidden" name="pad_ratio" id="pad_ratio" value="" />
<?php
if (!empty($pitemid)) {
	?>
	<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
	<?php
}
if ($ptmpl == 'component') {
	?>
	<input type="hidden" name="tmpl" value="component" />
	<?php
}
?>
</form>

<script type="text/javascript">
	var vbo_overlay_data = {};
	<?php
	if (!$empty_termsconds) {
		$termsconds = VikBooking::strTrimLiteral($termsconds);
		?>
	vbo_overlay_data['termsconds'] = '<div><?php echo addslashes($termsconds); ?></div><div class="vbo-center"><br /><button type="button" class="btn btn-large vbo-pref-color-btn" onclick="jQuery(\'#termsconds\').prop(\'checked\', true);vboCloseModal();"><?php echo addslashes(JText::translate('VBOTERMSCONDSACCCLOSE')); ?></button></div>';
		<?php
	}
	?>
</script>

<script type="text/javascript">
	/* Global Variables and Functions */
	var vbo_overlay_on = false;

	function vboOpenModal() {
		jQuery("#vbdialog-overlay").fadeIn(400, function() {
			if (jQuery("#vbdialog-overlay").is(":visible")) {
				vbo_overlay_on = true;
			} else {
				vbo_overlay_on = false;
				jQuery('.vbo-overlay-checkin-body').html('');
			}
		});
	}

	function vboCloseModal() {
		jQuery("#vbdialog-overlay").fadeOut();
		vbo_overlay_on = false;
	}

	function vboUpdateModal(title, body, call_toggle) {
		jQuery('#vbo-overlay-title').text(title);
		if (body.substr(0, 1) == '.') {
			//look for this value inside the global array
			body = body.substr(1, (body.length - 1));
			if (vbo_overlay_data.hasOwnProperty(body)) {
				body = vbo_overlay_data[body];
			}
		}
		jQuery('.vbo-overlay-checkin-body').html(body);
		if (call_toggle) {
			vboOpenModal();
		}
	}

	function vboShowSignPad() {
		jQuery('#fake-signature-container').remove();
		document.getElementById('real-signature-container').style.display = 'flex';
		vboResizeCanvas();
	}

	/* Canvas global vars */
	var canvas, signaturePad;

	function vboResizeCanvas() {
		var ratio =  Math.max(window.devicePixelRatio || 1, 1);
		canvas.width = canvas.offsetWidth * ratio;
		canvas.height = canvas.offsetHeight * ratio;
		canvas.getContext("2d").scale(ratio, ratio);
		signaturePad.clear();
		document.getElementById('pad_width').value = canvas.width;
		document.getElementById('pad_ratio').value = ratio;
	}

	function vboClearSignPad() {
		signaturePad.clear();
	}

	function vboConfirmGenerate(action) {
		if (action > 0) {
			if (signaturePad.isEmpty()) {
				alert('<?php echo addslashes(JText::translate('VBOSIGNATUREISEMPTY')); ?>');
				return false;
			}
			if (document.getElementById('termsconds') && !document.getElementById('termsconds').checked) {
				alert('<?php echo addslashes(JText::translate('VBOSIGNMUSTACCEPT')); ?>');
				return false;
			}
			var dataURL = signaturePad.toDataURL();
			document.getElementById('signature-data').value = dataURL;
			document.getElementById('vbo_sign_form').submit();
		} else {
			return false;
		}
	}

	window.onresize = vboResizeCanvas;

	jQuery(function() {
		/* Canvas initialization */
		var sign_wrapper = document.getElementById("vbo-signature-pad");
		// set global vars
		canvas = sign_wrapper.querySelector("canvas");
		signaturePad = new SignaturePad(canvas, {
			backgroundColor: 'rgba(0, 0, 0, 0)'
		});

		/* Canvas adjust rendering */
		setTimeout(() => {
			vboResizeCanvas();
		}, 200);

		/* Overlay for Terms and Conds - Start */
		jQuery(document).mouseup(function(e) {
			if (!vbo_overlay_on) {
				return false;
			}
			var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
			if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
				vboCloseModal();
			}
		});

		jQuery(document).keyup(function(e) {
			if (e.keyCode == 27 && vbo_overlay_on) {
				vboCloseModal();
			}
		});
		/* Overlay for Terms and Conds - End */
	});
</script>
