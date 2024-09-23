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

$order = $this->row;
$customer = $this->customer;

$document = JFactory::getDocument();
$document->addScript(VBO_SITE_URI.'resources/signature_pad.js');
$currencysymb = VikBooking::getCurrencySymb();
$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator(true);
$tmpl = VikRequest::getVar('tmpl');
$prefreshing = VikRequest::getInt('refreshing', '', 'request');
if (count($customer) && !empty($customer['signature'])) {
	//the signature exists, stop any kind of refresh
	$prefreshing = 0;
}
$now_info = getdate();
$today_midnight = mktime(0, 0, 0, $now_info['mon'], $now_info['mday'], $now_info['year']);
$colortags = VikBooking::loadBookingsColorTags();
$otachannel = '';
$otachannel_name = '';
$otachannel_bid = '';
$otacurrency = '';
if (!empty($order['channel'])) {
	$channelparts = explode('_', $order['channel']);
	$otachannel = array_key_exists(1, $channelparts) && strlen($channelparts[1]) > 0 ? $channelparts[1] : ucwords($channelparts[0]);
	$otachannel_name = $otachannel;
	$otachannel_bid = $order['idorderota'];
	if (strstr($otachannel, '.') !== false) {
		$otaccparts = explode('.', $otachannel);
		$otachannel = $otaccparts[0];
	}
	$otacurrency = strlen($order['chcurrency']) > 0 ? $order['chcurrency'] : '';
}
		?>
<div class="vbo-info-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content">
		<h3 id="vbo-overlay-title"></h3>
		<div class="vbo-overlay-checkin-body"></div>
	</div>
</div>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">
		<?php
		if ($tmpl == 'component') {
			//back button
			?>
	<a class="btn pull-left" href="index.php?option=com_vikbooking&task=bookingcheckin&cid[]=<?php echo $order['id']; ?>&tmpl=component"><i class="vboicn-undo"></i> <?php echo JText::translate('VBBACK'); ?></a>
			<?php
		}
		?>
	<div class="vbo-bookdet-container">
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span>ID</span>
			<?php
			if (!empty($order['adminnotes'])) {
				?>
				<i class="vboicn-info icn-bigger icn-nomargin icn-float-left icn-clickable" onclick="vboUpdateModal('<?php echo addslashes(JText::translate('VBADMINNOTESTOGGLE')); ?>', '.adminnotes', true);"></i>
				<?php
			}
			?>
			</div>
			<div class="vbo-bookdet-foot">
				<span><a href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $order['id']; ?>" target="_blank"><?php echo $order['id']; ?></a></span>
			</div>
		</div>
		<?php
		if (!empty($order['channel'])) {
			?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<?php echo $otachannel; ?>
			</div>
			<div class="vbo-bookdet-foot">
				<span>ID <?php echo $otachannel_bid; ?></span>
			</div>
		</div>
			<?php
		}
		?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERONE'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
				<?php echo date(str_replace("/", $datesep, $df).' H:i', $order['ts']); ?>
			</div>
		</div>
		<?php
		if (count($customer)) {
		?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></span>
			<?php
			if (!empty($customer['notes'])) {
				?>
				<i class="vboicn-info icn-bigger icn-nomargin icn-float-left icn-clickable" onclick="vboUpdateModal('<?php echo addslashes(JText::translate('VBCUSTOMERNOTES')); ?>', '.customer_notes', true);"></i>
				<?php
			}
			?>
			</div>
			<div class="vbo-bookdet-foot">
				<?php echo (isset($customer['country_img']) ? $customer['country_img'].' ' : '').ltrim($customer['first_name'].' '.$customer['last_name']); ?>
			</div>
		</div>
		<?php
		}
		?>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERROOMSNUM'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
				<?php echo $order['roomsnum']; ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERFOUR'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
				<?php echo $order['days']; ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERFIVE'); ?></span>
			<?php
			if (!empty($customer['comments'])) {
				?>
				<i class="vboicn-info icn-bigger icn-nomargin icn-float-left icn-clickable" onclick="vboUpdateModal('<?php echo addslashes(JText::translate('VBOTOGGLECHECKINNOTES')); ?>', '.comments', true);"></i>
				<?php
			}
			?>
			</div>
			<div class="vbo-bookdet-foot">
			<?php
			$checkin_info = getdate($order['checkin']);
			$short_wday = JText::translate('VB'.strtoupper(substr($checkin_info['weekday'], 0, 3)));
			?>
				<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $order['checkin']); ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERSIX'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
			<?php
			$checkout_info = getdate($order['checkout']);
			$short_wday = JText::translate('VB'.strtoupper(substr($checkout_info['weekday'], 0, 3)));
			?>
				<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $order['checkout']); ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBEDITORDERNINE'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
			<?php
			$bcolortag = VikBooking::applyBookingColorTag($order, $colortags);
			$usectag = '';
			if (count($bcolortag) > 0) {
				$bcolortag['name'] = JText::translate($bcolortag['name']);
				$usectag = '<span class="vbo-colortag-circle hasTooltip" style="background-color: '.$bcolortag['color'].';" title="'.htmlspecialchars($bcolortag['name']).'"></span> ';
			}
			?>
				<?php echo $usectag.(strlen($otacurrency) > 0 ? '('.$otacurrency.') '.$currencysymb : $currencysymb); ?> <?php echo VikBooking::numberFormat($order['total']); ?>
			</div>
		</div>
		<div class="vbo-bookdet-wrap vbo-bookdet-wrap-special">
			<div class="vbo-bookdet-head">
				<span><?php echo JText::translate('VBOCHECKEDSTATUS'); ?></span>
			</div>
			<div class="vbo-bookdet-foot">
			<?php
			if ($order['checked'] < 0) {
				//no show
				$checked_status = '<span class="label label-error">'.JText::translate('VBOCHECKEDSTATUSNOS').'</span>';
			} elseif ($order['checked'] == 1) {
				//checked in
				$checked_status = '<span class="label label-success">'.JText::translate('VBOCHECKEDSTATUSIN').'</span>';
			} elseif ($order['checked'] == 2) {
				//checked out
				$checked_status = '<span class="label label-info">'.JText::translate('VBOCHECKEDSTATUSOUT').'</span>';
			} else {
				//none (0)
				$checked_status = '<span class="label">'.JText::translate('VBOCHECKEDSTATUSZERO').'</span>';
			}
			?>
				<?php echo $checked_status; ?>
			</div>
		</div>
	</div>
	<?php
	$signpad_style = 'display: flex;';
	if (count($customer) && !empty($customer['signature'])) {
		$signpad_style = 'display: none;';
		?>
	<div class="vbo-signature-container" id="fake-signature-container">
		<div class="vbo-signature-pad">
			<div class="vbo-signature-pad-head">
				<p class="vbo-current-signature-p"><?php echo JText::translate('VBOCURRENTSIGNATURE'); ?></p>
			</div>
			<div class="vbo-signature-pad-body">
				<div class="vbo-signature-currentimg"><img src="<?php echo VBO_ADMIN_URI; ?>resources/idscans/<?php echo $customer['signature'].'?'.time(); ?>"></div>
			</div>
			<div class="vbo-signature-pad-footer">
				<div class="vbo-signature-signabove"></div>
				<div class="vbo-signature-cmds">
					<div class="vbo-signature-cmd">
						<button type="button" class="btn btn-large btn-success" onclick="vboConfirmGenerate(-1);"><i class="vboicn-checkmark"></i> <?php echo JText::translate('VBOGENCHECKINDOC'); ?></button>
					</div>
					<div class="vbo-signature-cmd">
						<button type="button" class="btn btn-large btn-success" onclick="vboShowSignPad();"><i class="vboicn-quill"></i> <?php echo JText::translate('VBOSIGNATUREAGAIN'); ?></button>
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
				<div class="pull-left">
					<button type="button" id="signature_link" class="btn <?php echo $prefreshing > 0 ? 'btn-success' : 'btn-primary'; ?>" onclick="vboStopRefresh();vboUpdateModal('<?php echo addslashes(JText::translate('VBOSIGNATURESHARE')); ?>', '.signature_link', true);"><i class="vboicn-link"></i> <?php echo JText::translate('VBOSIGNATURESHARE'); ?></button>
				</div>
				<div class="pull-right">
					<button type="button" class="btn" onclick="vboConfirmGenerate(0);"><i class="vboicn-cross"></i> <?php echo JText::translate('VBOSIGNATURESKIP'); ?></button>
				</div>
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
						<button type="button" class="btn btn-large btn-success" onclick="vboConfirmGenerate(1);"><i class="vboicn-checkmark"></i> <?php echo JText::translate('VBOSIGNATURESAVE'); ?></button>
					</div>
					<div class="vbo-signature-cmd">
						<button type="button" class="btn btn-large btn-danger" onclick="vboClearSignPad();"><i class="vboicn-bin"></i> <?php echo JText::translate('VBOSIGNATURECLEAR'); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="cid[]" value="<?php echo $order['id']; ?>">
	<input type="hidden" name="signature" id="signature-data" value="">
	<input type="hidden" name="pad_width" id="pad_width" value="">
	<input type="hidden" name="pad_ratio" id="pad_ratio" value="">
	<input type="hidden" name="task" value="createcheckindoc" />
	<input type="hidden" name="option" value="com_vikbooking" />
	<?php
	if ($tmpl == 'component') {
	?>
	<input type="hidden" name="tmpl" value="component" />
	<?php
	}
	?>
</form>
<script type="text/javascript">
	var refreshing = null;
	var vbo_overlay_data = {};
	<?php
	if (!empty($order['adminnotes'])) {
		$order['adminnotes'] = VikBooking::strTrimLiteral(nl2br(htmlspecialchars($order['adminnotes'])));
		?>
	vbo_overlay_data['adminnotes'] = '<pre><?php echo addslashes($order['adminnotes']); ?></pre>';
		<?php
	}
	if (count($customer) && !empty($customer['notes'])) {
		$customer['notes'] = VikBooking::strTrimLiteral(nl2br(htmlspecialchars($customer['notes'])));
		?>
	vbo_overlay_data['customer_notes'] = '<pre><?php echo addslashes($customer['notes']); ?></pre>';
		<?php
	}
	if (count($customer) && !empty($customer['comments'])) {
		$customer['comments'] = VikBooking::strTrimLiteral(nl2br(htmlspecialchars($customer['comments'])));
		?>
	vbo_overlay_data['comments'] = '<pre><?php echo addslashes($customer['comments']); ?></pre>';
		<?php
	}

	// attempt to rewrite the share signature URL
	$share_link = JUri::root() . 'index.php?option=com_vikbooking&task=signature&sid=' . (!empty($order['idorderota']) && !empty($order['channel']) ? $order['idorderota'] : $order['sid']) . '&ts=' . $order['ts'];
	if (VBOPlatformDetection::isWordPress()) {
		/**
		 * @wponly 	Rewrite URI for front-end signature
		 */
		$share_link = str_replace(JUri::root(), '', $share_link);
		$model 		= JModel::getInstance('vikbooking', 'shortcodes');
		$itemid 	= $model->all('post_id', $full = true);
		if (count($itemid)) {
			$share_link = JRoute::rewrite($share_link . "&Itemid={$itemid[0]->post_id}", false);
		}
	} else {
		/**
		 * @joomlaonly
		 */
		$best_menuitem_id = VikBooking::findProperItemIdType(['vikbooking', 'booking'], $order['lang']);
		if ($best_menuitem_id) {
			$lang_link = !empty($order['lang']) ? "&lang={$order['lang']}" : '';
			$share_base = str_replace(JUri::root(), '', $share_link);
			$share_base .= $lang_link;
			$share_link = VikBooking::externalroute($share_base, $xhtml = false, $best_menuitem_id);
		}
	}

	?>
	vbo_overlay_data['signature_link'] = '<div class="vbo-sign-share-meth-cont">'+
		'<div class="vbo-sign-share-meth">'+
			'<label for="vbo-share-email"><?php echo addslashes(JText::translate('VBOSIGNSHAREEMAIL')); ?></label>'+
			'<input type="text" id="vbo-share-email" value="<?php echo addslashes((count($customer) && !empty($customer['email']) ? $customer['email'] : $order['custmail'])); ?>" size="35" />'+
			'<button type="button" class="btn btn-primary" onclick="vboSendSignLink(\'email\');"><i class="vboicn-envelop"></i> <?php echo addslashes(JText::translate('VBOSIGNSENDLINK')); ?></button>'+
		'</div>'+
		'<div class="vbo-sign-share-meth">'+
			'<label for="vbo-share-sms"><?php echo addslashes(JText::translate('VBOSIGNSHARESMS')); ?></label>'+
			'<input type="text" id="vbo-share-sms" value="<?php echo addslashes((count($customer) && !empty($customer['phone']) ? $customer['phone'] : $order['phone'])); ?>" size="35" />'+
			'<button type="button" class="btn btn-primary" onclick="vboSendSignLink(\'sms\');"><i class="vboicn-mobile"></i> <?php echo addslashes(JText::translate('VBOSIGNSENDLINK')); ?></button>'+
		'</div>'+
		'<div class="vbo-sign-share-meth">'+
			'<span><a href="<?php echo $share_link; ?>" target="_blank"><i class="vboicn-link"></i> <?php echo $share_link; ?></a></span>'+
		'</div>'+
		'<div class="vbo-sign-share-meth vbo-sign-share-meth-close">'+
			'<span><button type="button" class="btn btn-secondary" onclick="vboCloseModal();"><?php echo addslashes(JText::translate('VBANNULLA')); ?></button></span>'+
		'</div>'+
	'</div>';
	<?php
	if (!$empty_termsconds) {
		$termsconds = VikBooking::strTrimLiteral($termsconds);
		$terms_acc_close = VikBooking::strTrimLiteral(JText::translate('VBOTERMSCONDSACCCLOSE'));
		?>
	vbo_overlay_data['termsconds'] = '<div><?php echo addslashes($termsconds); ?></div><div class="vbo-center"><br /><button type="button" class="btn btn-large btn-success" onclick="jQuery(\'#termsconds\').prop(\'checked\', true);vboCloseModal();"><?php echo addslashes($terms_acc_close); ?></button></div>';
		<?php
	}
	if ($prefreshing > 0) {
		?>
	refreshing = setInterval(function() {
		jQuery('#signature_link').html(jQuery('#signature_link').html().replace('<?php echo addslashes(JText::translate('VBOSIGNATURESHARE')); ?>', '<?php echo addslashes(JText::translate('VBORELOADING')); ?>'));
		setTimeout(function() {
			document.location.href = 'index.php?option=com_vikbooking&task=gencheckindoc&cid[]=<?php echo $order['id'].'&refreshing=1'.($tmpl == 'component' ? '&tmpl=component' : ''); ?>';
		}, 1000);
	}, 8000);
		<?php
	}
	?>
</script>
<script type="text/javascript">
	/* Global Variables and Functions */
	var vbo_overlay_on = false;
	var booking_total = <?php echo (float)$order['total']; ?>;
	var tot_rooms = <?php echo (int)$order['roomsnum']; ?>;
	var current_checked = <?php echo (int)$order['checked']; ?>;
	function vboOpenModal() {
		/**
		 * @wponly - we are inside the same parent page as the modal is loaded via Ajax.
		 * We need to select the values from the modal container.
		 */
		jQuery('.modal-body').find(".vbo-info-overlay-block").fadeIn(400, function() {
			if (jQuery('.modal-body').find(".vbo-info-overlay-block").is(":visible")) {
				vbo_overlay_on = true;
			} else {
				vbo_overlay_on = false;
				jQuery('.modal-body').find('.vbo-overlay-checkin-body').html('');
			}
		});
	}
	function vboCloseModal() {
		/**
		 * @wponly - we are inside the same parent page as the modal is loaded via Ajax.
		 * We need to select the values from the modal container.
		 */
		jQuery('.modal-body').find(".vbo-info-overlay-block").fadeOut();
		vbo_overlay_on = false;
	}
	function vboUpdateModal(title, body, call_toggle) {
		/**
		 * @wponly - we are inside the same parent page as the modal is loaded via Ajax.
		 * We need to select the values from the modal container.
		 */
		jQuery('.modal-body').find('#vbo-overlay-title').text(title);
		if (body.substr(0, 1) == '.') {
			//look for this value inside the global array
			body = body.substr(1, (body.length - 1));
			if (vbo_overlay_data.hasOwnProperty(body)) {
				body = vbo_overlay_data[body];
			}
		}
		jQuery('.modal-body').find('.vbo-overlay-checkin-body').html(body);
		if (call_toggle) {
			vboOpenModal();
		}
	}
	function vboShowSignPad() {
		jQuery('#fake-signature-container').remove();
		document.getElementById('real-signature-container').style.display = 'flex';
		vboResizeCanvas();
	}
	function vboSendSignLink(method) {
		var sendto = document.getElementById('vbo-share-'+method);
		if (!sendto || !sendto.value.length) {
			alert('<?php echo addslashes(JText::translate('VBOSIGNSHAREEMPTY')); ?>');
			return false;
		}
		jQuery.ajax({
			type: 'POST',
			url: 'index.php?option=com_vikbooking&task=sharesignaturelink&tmpl=component',
			data: {bid: '<?php echo $order['id']; ?>', how: method, to: sendto.value, customer: '<?php echo $customer['id']; ?>'}
		}).done(function(resp){
			var obj = JSON.parse(resp);
			if (obj.status > 0) {
				jQuery('#signature_link').addClass('btn-success').removeClass('btn-primary');
				/**
				 * @wponly - We cannot set intervals or timeouts to reload the page as we are loaded via Ajax inside a Modal
				 */
				vboCloseModal();
			} else {
				alert(obj.error);
			}
		}).fail(function(resp){
			console.log(resp);
			alert('Error.');
		});
	}
	function vboStopRefresh() {
		jQuery('#signature_link').addClass('btn-primary').removeClass('btn-success');
		if (refreshing != null) {
			clearInterval(refreshing);
			refreshing = null;
		}
	}
	if (jQuery.isFunction(jQuery.fn.tooltip)) {
		jQuery(".hasTooltip").tooltip();
	} else {
		jQuery.fn.tooltip = function(){};
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
			//var dataURL = signaturePad.toDataURL("image/jpeg");
			//var dataURL = signaturePad.toDataURL('image/svg+xml');
			var dataURL = signaturePad.toDataURL();
			document.getElementById('signature-data').value = dataURL;
			document.getElementById('adminForm').submit();
		} else if (action < 0) {
			document.getElementById('adminForm').submit();
		} else {
			var proceed_txt = '<?php echo addslashes(JText::translate('VBONOSIGNATURECONF')); ?>';
			if (confirm(proceed_txt)) {
				document.getElementById('adminForm').submit();
			}
		}
	}

	// On mobile devices it might make more sense to listen to orientation change, rather than window resize events.
	window.onresize = vboResizeCanvas;

	jQuery(function() {
		/* Overlay for Customer Notes, Booking Notes, Checkin Comments - Start */
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

		/* Canvas initialization */
		var sign_wrapper = document.getElementById("vbo-signature-pad");
		// set global vars
		canvas = sign_wrapper.querySelector("canvas");
		signaturePad = new SignaturePad(canvas, {
			// opaque color only needed when saving image as JPEG (rgb(255, 255, 255))
			backgroundColor: 'rgba(0, 0, 0, 0)'
		});

		/* Canvas adjust rendering */
		setTimeout(() => {
			vboResizeCanvas();
		}, 200);
	});
</script>