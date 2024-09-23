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
$rooms = $this->rooms;
$busy = $this->busy;
$customer = $this->customer;
$payments = $this->payments;

$history_obj = VikBooking::getBookingHistoryInstance($row['id']);

// availability helper
$av_helper = VikBooking::getAvailabilityInstance();

// load datepicker for all CMS compatibility
JHtml::fetch('stylesheet', VBO_SITE_URI.'resources/jquery-ui.min.css');
JHtml::fetch('jquery.framework', true, true);
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.min.js');

// js lang def
JText::script('VBO_CONV_RES_OTA_CURRENCY_APPLY');
JText::script('VBDELCONFIRM');
JText::script('VBSENDCANCORDEMAIL');
JText::script('VBO_SET_STANDBY');
JText::script('VBO_CONF_RM_OVERBOOKING_FLAG');

$dbo = JFactory::getDbo();

$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadVisualEditorAssets();

/**
 * This View can render within a modal the "bookingcheckin" View, and with WP
 * this is loaded via AJAX. In order to not interfere or reset the contextual menu,
 * we load the assets from the parent View. Context menu may also be used in this View.
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 */
$vbo_app->loadContextMenuAssets();
if (VBOPlatformDetection::isWordPress()) {
	JText::script('VBRENTALORD');
	JText::script('VBPVIEWORDERSPEOPLE');
}

$canDo = JFactory::getUser();
$vbo_auth_bookings = $canDo->authorise('core.vbo.bookings', 'com_vikbooking');

$currencyname = VikBooking::getCurrencyName();
$currencysymb = VikBooking::getCurrencySymb();
$after_tax = VikBooking::ivaInclusa();
$nowdf = VikBooking::getDateFormat(true);
if ($nowdf == "%d/%m/%Y") {
	$df = 'd/m/Y';
} elseif ($nowdf == "%m/%d/%Y") {
	$df = 'm/d/Y';
} else {
	$df = 'Y/m/d';
}
$datesep = VikBooking::getDateSeparator(true);
$vbo_modals_html = array();
$payment = VikBooking::getPayment($row['idpayment']);
$pactive_tab = VikRequest::getString('vbo_active_tab', 'vbo-tab-details', 'request');
$printreceipt = VikRequest::getInt('print', 0, 'request');
$printreceipt = ($printreceipt > 0);
if ($printreceipt) {
	//we set a different page title when printing the receipt for the "PDF Printer" to give the file a good name.
	JFactory::getDocument()->setTitle(VikBooking::getFrontTitle()." - ".JText::translate('VBOFISCRECEIPT')." #".$row['id']);
}
$gotouri = 'index.php?option=com_vikbooking&task=editorder&cid[]='.$row['id'];

if ($row['status'] == "confirmed") {
	$use_js_bid = $row['id'];
	JFactory::getDocument()->addScriptDeclaration(
<<<JS
window["vbo_current_bid"] = $use_js_bid;
JS
	);
}

$tars = array();
$arrpeople = array();
$is_package = !empty($row['pkg']) ? true : false;
$is_cust_cost = false;
foreach ($rooms as $ind => $or) {
	$num = $ind + 1;
	$arrpeople[$num]['adults'] = $or['adults'];
	$arrpeople[$num]['children'] = $or['children'];
	$arrpeople[$num]['children_age'] = $or['childrenage'];
	$arrpeople[$num]['t_first_name'] = $or['t_first_name'];
	$arrpeople[$num]['t_last_name'] = $or['t_last_name'];
	$arrpeople[$num]['pets'] = $or['pets'];
	if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
		//package or custom cost set from the back-end
		$is_cust_cost = true;
		continue;
	}
	$q = "SELECT * FROM `#__vikbooking_dispcost` WHERE `id`=".(int)$or['idtar'].";";
	$dbo->setQuery($q);
	$tar = $dbo->loadAssocList();
	$tar = VikBooking::applySeasonsRoom($tar, $row['checkin'], $row['checkout']);

	// apply OBP rules
	$tar = VBORoomHelper::getInstance()->applyOBPRules($tar, $or, $or['adults']);

	$tars[$num] = $tar;
}
$pcheckin = $row['checkin'];
$pcheckout = $row['checkout'];
$secdiff = $pcheckout - $pcheckin;
$daysdiff = $secdiff / 86400;
if (is_int($daysdiff)) {
	if ($daysdiff < 1) {
		$daysdiff = 1;
	}
} else {
	if ($daysdiff < 1) {
		$daysdiff = 1;
	} else {
		$sum = floor($daysdiff) * 86400;
		$newdiff = $secdiff - $sum;
		$maxhmore = VikBooking::getHoursMoreRb() * 3600;
		if ($maxhmore >= $newdiff) {
			$daysdiff = floor($daysdiff);
		} else {
			$daysdiff = ceil($daysdiff);
		}
	}
}
$otachannel = '';
$otachannel_name = '';
$otachannel_bid = '';
$otacurrency = '';
if (!empty($row['channel'])) {
	$channelparts = explode('_', $row['channel']);
	$otachannel = array_key_exists(1, $channelparts) && strlen($channelparts[1]) > 0 ? $channelparts[1] : ucwords($channelparts[0]);
	$otachannel_name = $otachannel;
	$otachannel_bid = $otachannel.(!empty($row['idorderota']) ? ' - Booking ID: '.$row['idorderota'] : '');
	if (strstr($otachannel, '.') !== false) {
		$otaccparts = explode('.', $otachannel);
		$otachannel = $otaccparts[0];
	}
	$otacurrency = strlen($row['chcurrency']) > 0 ? $row['chcurrency'] : '';
}

$status_type = '';
if (!empty($row['type'])) {
	$status_type = JText::translate('VBO_BTYPE_' . strtoupper($row['type']));
	if (!strcasecmp($row['type'], 'overbooking')) {
		$status_type = '<span class="label label-error vbo-label-nested vbo-label-overbooking">' . $status_type . '</span>';
	}
	$status_type .= ' / ';
}
$extra_status = $row['refund'] > 0 ? ' / ' . JText::translate('VBO_STATUS_REFUNDED') : '';
if ($row['status'] == "confirmed") {
	$saystaus = '<span class="label label-success">' . $status_type . JText::translate('VBCONFIRMED') . $extra_status . '</span>';
} elseif ($row['status'] == "standby") {
	$saystaus = '<span class="label label-warning">' . $status_type . JText::translate('VBSTANDBY') . $extra_status . '</span>';
} else {
	$saystaus = '<span class="label label-error">' . $status_type . JText::translate('VBCANCELLED') . $extra_status . '</span>';
}

// extend booking details main panel
$bookingPanelForms = $this->onDisplayView();

// extend booking details sidebar
$bookingSidebarForms = $this->onDisplayView('Sidebar');

// extend administration main panel
$adminFormsSetup = new stdClass;
$adminFormsSetup->icons = [];
$adminPanelForms = $this->onDisplayView('Administration', $adminFormsSetup);

// extend administration sidebar
$adminSidebarForms = $this->onDisplayView('AdministrationSidebar');

//Prepare modal (used for the Registration and for reconstructing the credit card details through the channel manager)
echo $vbo_app->getJmodalScript();
//end Prepare modal
?>
<script type="text/javascript">
function changePayment() {
	var newpayment = document.getElementById('newpayment').value;
	if (newpayment != '') {
		var paymentname = document.getElementById('newpayment').options[document.getElementById('newpayment').selectedIndex].text;
		if (confirm('<?php echo addslashes(JText::translate('VBCHANGEPAYCONFIRM')); ?>' + paymentname + '?')) {
			document.adminForm.submit();
		} else {
			document.getElementById('newpayment').selectedIndex = 0;
		}
	}
}
function vbToggleLog(elem) {
	vbToggleDynamicPanel(elem, '#vbpaymentlogdiv');
}
function vbToggleNotes(elem) {
	vbToggleDynamicPanel(elem, '#vbadminnotesdiv');
}
function vbToggleHistory(elem) {
	vbToggleDynamicPanel(elem, '#vbhistorydiv');
}
function vbToggleInvNotes(elem) {
	vbToggleDynamicPanel(elem, '#vbinvnotesdiv');
}
function vbToggleMessaging(elem) {
	vbToggleDynamicPanel(elem, '#vbmessagingdiv');
	if (typeof VCMChat !== 'undefined') {
		VCMChat.getInstance().scrollToBottom();
	}
}
function vbToggleDynamicPanel(elem, target) {
	if (jQuery(target).is(':visible')) {
		return;
	}
	
	jQuery(".vbo-bookingdet-noteslogs-btn-active").removeClass("vbo-bookingdet-noteslogs-btn-active");

	jQuery('.vbo-extra-panel').hide();
	jQuery(target).show();
	
	jQuery(elem).parent(".vbo-bookingdet-noteslogs-btn").addClass("vbo-bookingdet-noteslogs-btn-active");

	if (typeof sessionStorage !== 'undefined') {
		sessionStorage.setItem('vboEditOrderTab<?php echo $row['id']; ?>', jQuery(elem).parent().data('id'));
	}
}
function toggleDiscount(elem) {
	var discsp = document.getElementById('vbdiscenter').style.display;
	if (discsp == 'block') {
		document.getElementById('vbdiscenter').style.display = 'none';
		jQuery(elem).find('i').attr('class', '<?php echo VikBookingIcons::i('chevron-down'); ?>');
	} else {
		document.getElementById('vbdiscenter').style.display = 'block';
		jQuery(elem).find('i').attr('class', '<?php echo VikBookingIcons::i('chevron-up'); ?>');
	}
}
</script>

<div class="vbo-bookingdet-topcontainer">
	<form name="adminForm" id="adminForm" action="<?php echo VBOPlatformDetection::isWordPress() ? 'admin.php' : 'index.php'; ?>?option=com_vikbooking&amp;task=editorder&amp;cid[]=<?php echo $row['id']; ?>" method="post">
	<?php
	if ($printreceipt) {
		//print the company details
		$companylogo = VikBooking::getSiteLogo();
		$next_receipt = VikBooking::getNextReceiptNumber($row['id']);
		?>
		<div class="vbo-receipt-company-block-outer">
			<div class="vbo-receipt-company-block">
			<?php
			if (!empty($companylogo)) {
				?>
				<div class="vbo-receipt-company-logo"><img src="<?php echo VBO_ADMIN_URI.'resources/'.$companylogo; ?>" /></div>
				<?php
			}
			?>
				<div class="vbo-receipt-company-info"><?php echo VikBooking::getInvoiceCompanyInfo(); ?></div>
			</div>
			<div class="vbo-receipt-numdate-block">
				<div class="vbo-receipt-numdate-inner">
					<div class="vbo-receipt-numdate-title">
						<span><?php echo JText::translate('VBOFISCRECEIPT'); ?></span>
						<span style="float: right; cursor: pointer; color: #ff0000;" onclick="vboMakePrintOnly();"><?php VikBookingIcons::e('times-circle'); ?></span>
					</div>
					<div class="vbo-receipt-numdate-num">
						<span class="vbo-receipt-numdate-num-lbl"><?php echo JText::translate('VBOFISCRECEIPTNUM'); ?></span>
						<span class="vbo-receipt-numdate-num-val">
							<span class="vbo-showin-print" id="vbo-receipt-num"><?php echo $next_receipt; ?></span>
							<input class="vbo-hidein-print" id="vbo-receipt-num-inp" type="number" min="0" value="<?php echo $next_receipt; ?>" onchange="document.getElementById('vbo-receipt-num').innerText = this.value;" />
						</span>
					</div>
					<div class="vbo-receipt-numdate-date">
						<span class="vbo-receipt-numdate-date-lbl"><?php echo JText::translate('VBOFISCRECEIPTDATE'); ?></span>
						<span class="vbo-receipt-numdate-date-val"><?php echo date(str_replace("/", $datesep, $df)); ?></span>
					</div>
				</div>
			</div>
			<div class="vbo-receipt-print-confirm vbo-hidein-print">
				<div class="vbo-receipt-print-btn">
					<span onclick="vboLaunchPrintReceipt();">
						<?php VikBookingIcons::e('print'); ?> 
						<span id="vbo-receipt-print-btn-name"><?php echo JText::translate('VBOPRINTRECEIPT'); ?></span>
					</span>
				</div>
			</div>
		</div>
		<?php
	}
	?>
		
		<div class="vbo-bookdet-container">
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span>ID</span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $row['id']; ?></span>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERONE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo date(str_replace("/", $datesep, $df).' H:i', $row['ts']); ?></span>
				</div>
			</div>
		<?php
		if (!$printreceipt && count($customer)) {
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBCUSTOMERNOMINATIVE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<div class="vbo-customer-info-box">
						<div class="vbo-customer-info-box-name">
							<?php echo (isset($customer['country_img']) ? $customer['country_img'] . ' ' : ''); ?>

							<?php if ($canDo->authorise('core.edit', 'com_vikbooking') && $canDo->authorise('core.vbo.management', 'com_vikbooking')): ?>
								<a href="index.php?option=com_vikbooking&task=editcustomer&cid[]=<?php echo $customer['id']; ?>&goto=<?php echo base64_encode($gotouri); ?>"><?php echo ltrim($customer['first_name'] . ' ' . $customer['last_name']); ?></a>
							<?php else: ?>
								<?php echo ltrim($customer['first_name'] . ' ' . $customer['last_name']); ?>
							<?php endif; ?>
						</div>
					<?php
					if (!empty($customer['pic'])) {
						$avatar_caption = ltrim($customer['first_name'] . ' ' . $customer['last_name']);
						?>
						<div class="vbo-customer-info-box-avatar vbo-customer-avatar-small">
							<span>
								<img src="<?php echo strpos($customer['pic'], 'http') === 0 ? $customer['pic'] : VBO_SITE_URI . 'resources/uploads/' . $customer['pic']; ?>" data-caption="<?php echo htmlspecialchars($avatar_caption); ?>" />
							</span>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</div>
		<?php
		}
		if (!$printreceipt) {
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERROOMSNUM'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<?php echo $row['roomsnum']; ?>
				</div>
			</div>
			<?php
		}
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERFOUR'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $row['days']; ?></span>
				<?php
				if ($row['split_stay']) {
					?>
					<span class="hasTooltip" title="<?php echo JHtml::fetch('esc_attr', JText::translate('VBO_SPLIT_STAY')); ?>"><?php VikBookingIcons::e('random'); ?></span>
					<?php
				}
				?>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERFIVE'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
				<?php
				$checkin_info = getdate($row['checkin']);
				$short_wday = JText::translate('VB'.strtoupper(substr($checkin_info['weekday'], 0, 3)));
				?>
					<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $row['checkin']); ?>
				</div>
			</div>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBEDITORDERSIX'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
				<?php
				$checkout_info = getdate($row['checkout']);
				$short_wday = JText::translate('VB'.strtoupper(substr($checkout_info['weekday'], 0, 3)));
				?>
					<?php echo $short_wday.', '.date(str_replace("/", $datesep, $df).' H:i', $row['checkout']); ?>
				</div>
			</div>
		<?php
		if (!$printreceipt && $vbo_auth_bookings && $row['closure'] != 1 && $row['status'] == 'confirmed') {
			// we don't need to check in the IF above "&& $row['checked'] != 0" because the registration is useful for all bookings.
			$checked_btn = 'btn-primary';
			switch ($row['checked']) {
				case -1:
					$checked_status = JText::translate('VBOCHECKEDSTATUSNOS');
					$checked_btn = 'btn-danger';
					break;
				case 1:
					$checked_status = JText::translate('VBOCHECKEDSTATUSIN');
					$checked_btn = 'btn-success';
					break;
				case 2:
					$checked_status = JText::translate('VBOCHECKEDSTATUSOUT');
					$checked_btn = 'btn-warning';
					break;
				default:
					$checked_status = JText::translate('VBOCHECKEDSTATUSZERO');
					if (count($customer) && isset($customer['pax_data']) && !empty($customer['pax_data'])) {
						// pre check-in performed via front-end
						$checked_status = JText::translate('VBOCHECKEDSTATUSPRECHECKIN');
					}
					break;
			}
			?>
			<div class="vbo-bookdet-wrap pro-feature">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBOCHECKEDSTATUS'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<button type="button" class="btn btn-small <?php echo $checked_btn; ?>" onclick="vboOpenJModal('vbo-checkin-booking', 'index.php?option=com_vikbooking&task=bookingcheckin&cid[]=<?php echo $row['id']; ?>&tmpl=component');">
						<?php echo $checked_status; ?>
					</button>
				</div>
			</div>
		<?php
			//Prepare modal (Registration)
			array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-checkin-booking', JText::translate('VBOMANAGECHECKSINOUT')));
			//end Prepare modal
		}

		/**
		 * Check whether the booking can be refunded.
		 * 
		 * @since 	1.14 (J) - 1.4.0 (WP)
		 */

		// refundable up to 7 days after check-out day
		$max_refund_ts = mktime(23, 59, 59, $checkout_info['mon'], ($checkout_info['mday'] + 7), $checkout_info['year']);
		// current payment driver must be set
		$tn_driver = is_array($payment) ? $payment['file'] : null;
		// transaction data validation callback
		$tn_data_callback = function($data) use ($tn_driver) {
			return (is_object($data) && isset($data->driver) && basename($data->driver, '.php') == basename($tn_driver, '.php'));
		};
		$prev_tn_data = $history_obj->getEventsWithData(array('P0', 'PN'), $tn_data_callback);
		$refundable = ($row['status'] != 'standby' && $max_refund_ts >= time() && is_array($prev_tn_data) && count($prev_tn_data));
		if ($refundable) {
			// prepare modal (refundtn) with a custom function to trigger the opening
			array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-refund-tn', JText::translate('VBO_ISSUE_REFUND')));
			echo $vbo_app->getJmodalScript('Refund', 'vboDetectRefundChanges();');
			// count the number of refunds made
			$refunds = $history_obj->getEventsWithData('RF', null, false);
			$refunds = !is_array($refunds) ? array() : $refunds;
			?>
			<script type="text/javascript">
				var vbo_refund_performed = false;
				function vboDetectRefundChanges() {
					if (vbo_refund_performed === true) {
						location.reload();
					}
				}
			</script>

			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBO_ISSUE_REFUND'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<button type="button" class="btn btn-small btn-danger vbo-dorefund-btn"<?php echo count($refunds) ? ' data-totrefunds="' . count($refunds) . '"' : ''; ?> onclick="vboOpenJModalRefund('vbo-refund-tn', 'index.php?option=com_vikbooking&task=refundtn&cid[]=<?php echo $row['id']; ?>&tmpl=component');">
						<?php echo JText::translate('VBO_REFUND'); ?>
					</button>
				</div>
			</div>
			<?php
		}
		//

		if (!$printreceipt && !empty($row['channel'])) {
			$ota_logo_img = VikBooking::getVcmChannelsLogo($row['channel']);
			if ($ota_logo_img === false) {
				$ota_logo_img = $otachannel_name;
			} else {
				$ota_logo_img = '<img src="'.$ota_logo_img.'" class="vbo-channelimg-medium"/>';
			}
			?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBPVIEWORDERCHANNEL'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $ota_logo_img; ?></span>
				</div>
			</div>
			<?php
		} elseif (!$printreceipt && class_exists('VCMOtaBooking')) {
			/**
			 * Vik Channel Manager booking assignable channels.
			 * 
			 * @since 	1.16.3 (J) - 1.6.3 (WP)
			 */
			$assignable_channels = VCMOtaBooking::getInstance($row, true)->getChannelsAssignable();
			if ($assignable_channels) {
				JText::script('VBO_ASSIGN_BOOKING_TO_OTA_CONFIRM');
				JFactory::getDocument()->addScriptDeclaration(
<<<JS
function vboConfirmAssignChannel(ch_key, ch_name) {
	if (!ch_key || !ch_name) {
		return true;
	}
	if (confirm(Joomla.JText._('VBO_ASSIGN_BOOKING_TO_OTA_CONFIRM').replace('%s', ch_name))) {
		document.adminForm.submit();
		return true;
	} else {
		return false;
	}
}
JS
				);
				?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBPVIEWORDERCHANNEL'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<select name="vcm_assign_channel" class="vbo-select-small" onchange="return vboConfirmAssignChannel(this.value, this.options[this.selectedIndex].text);">
						<option value=""><?php echo JText::translate('VBORDFROMSITE'); ?></option>
					<?php
					foreach ($assignable_channels as $assign_ch_key => $assign_ch_name) {
						?>
						<option value="<?php echo JHtml::fetch('esc_attr', $assign_ch_key); ?>"><?php echo $assign_ch_name; ?></option>
						<?php
					}
					?>
					</select>
				</div>
			</div>
				<?php
			}
		}
		if (!$printreceipt) {
		?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBSTATUS'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span><?php echo $saystaus; ?></span>
				</div>
			</div>
			<?php
			if (is_array($this->vcm_pre_approval)) {
				?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo $this->vcm_pre_approval[1]; ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<span>
					<?php
					if (empty($this->vcm_pre_approval[2]) || !empty($this->vcm_pre_approval[3])) {
						// no pre-approvals before, or withdrawn before
						?>
						<a href="index.php?option=com_vikchannelmanager&task=sendSpecialOffer&vbo_oid=<?php echo $row['id']; ?>&spo_type=preapproval&spo_action=send&ota_thread_id=<?php echo $this->vcm_pre_approval[0]; ?>" class="btn btn-small btn-primary vcm-btn-channel-<?php echo strtolower($this->vcm_pre_approval[1]); ?>"><?php VikBookingIcons::e('thumbs-up'); ?> <?php echo JText::translate('VBO_PREAPPROVE_INQUIRY'); ?></a>
						<?php
					} else {
						// pre-approved before, pass the ID
						?>
						<a href="index.php?option=com_vikchannelmanager&task=sendSpecialOffer&vbo_oid=<?php echo $row['id']; ?>&spo_type=preapproval&spo_action=withdraw&ota_thread_id=<?php echo $this->vcm_pre_approval[0]; ?>&spo_id=<?php echo $this->vcm_pre_approval[2]; ?>" class="btn btn-small btn-danger"><?php VikBookingIcons::e('thumbs-down'); ?> <?php echo JText::translate('VBO_WITHDRAW_PREAPPROVAL'); ?></a>
						<?php
					}
					?>
					</span>
				</div>
			</div>
				<?php
			}
			if (!empty($row['channel']) && stripos($row['channel'], 'booking.com') !== false && $row['checkin'] <= time() && strtotime('+ 7 days', $row['checkout']) >= time()) {
				/**
				 * Guest misconduct reporting API for Booking.com.
				 * Only after reservation check-in date and no later than 7 days after check-out date. 
				 * 
				 * @since 	1.13.5 - VCM 1.7.2
				 */
				$misconduct_reported = $history_obj->hasEvent('GM');
				?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBOGUESTMISCONDUCT'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
				<?php
				if ($misconduct_reported === false) {
					// allow to report the guest misconduct
					?>
					<button type="button" class="btn btn-small btn-danger" onclick="vboRenderGuestMisconduct();"><?php VikBookingIcons::e('user-slash'); ?> <?php echo JText::translate('VBOREPORTMISCONDUCT'); ?></button>
					<?php
				} else {
					// guest misconduct already reported
					?>
					<button type="button" class="btn btn-small btn-warning" onclick="vboShowGuestMisconductReported();"><?php VikBookingIcons::e('user-slash'); ?> <?php echo $misconduct_reported; ?></button>
					<?php
				}
				?>
					<a id="vbo-gm-reloadlink" style="display: none;" href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $row['id']; ?>"></a>
				</div>

				<div class="vbo-modal-overlay-block vbo-modal-overlay-block-guestmisconduct">
					<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
					<div class="vbo-modal-overlay-content vbo-modal-overlay-content-guestmisconduct">
						<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-head-guestmisconduct">
							<h3><?php VikBookingIcons::e('exclamation-circle'); ?> <?php echo JText::sprintf('VBOREPGUESTMISCONDUCTTO', 'Booking.com'); ?> <span class="vbo-modal-overlay-close-times" onclick="hideVboDialogGuestMisconduct();">&times;</span></h3>
						</div>
						<div class="vbo-modal-overlay-content-body">
							<div class="vbo-modal-guestmisconduct-addnew" data-bid="<?php echo $row['id']; ?>" data-otabid="<?php echo $row['idorderota']; ?>">
								<div class="vbo-modal-guestmisconduct-report-elems"></div>
								<div class="vbo-modal-guestmisconduct-addnew-save">
									<button type="button" id="vbo-guest-misconduct-submit-btn" class="btn btn-success" onclick="vboSubmitGuestMisconduct();" style="display: none;"><?php echo JText::translate('VBCHANNELMANAGERSENDRQ'); ?></button>
									<button type="button" class="btn btn-secondary" onclick="hideVboDialogGuestMisconduct();"><?php echo JText::translate('VBOCLOSE'); ?></button>
								</div>
							</div>
						</div>
					</div>
				</div>

				<script type="text/javascript">
				var vbodialogguestmisconduct_on = false;
				var vbo_guest_misconduct_fields = new Array;

				function vboShowGuestMisconductReported() {
					// this is like simulating the navigation to the booking history tab
					jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
					vbToggleHistory(document.getElementById('vbo-trig-bookhistory'));
				}

				function vboRenderGuestMisconduct() {
					// empty content
					jQuery('.vbo-modal-guestmisconduct-report-elems').html('<?php echo $this->escape(JText::translate('VIKLOADING') . '...'); ?>');
					vbo_guest_misconduct_fields = new Array;

					// display modal
					jQuery('.vbo-modal-overlay-block-guestmisconduct').fadeIn();
					vbodialogguestmisconduct_on = true;

					// make AJAX request to VCM to always reload the form fields
					VBOCore.doAjax(
						"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=get_bcom_misconduct_categories'); ?>",
						{
							tmpl: "component"
						},
						(res) => {
							// parse the JSON response that contains the categories object with all fields for the reporting
							try {
								var misconduct_fields_rs = JSON.parse(res);

								// make sure the request was successful
								if (!misconduct_fields_rs.status) {
									alert(misconduct_fields_rs.error);
									hideVboDialogGuestMisconduct();
									return false;
								}

								// render form from response obtained
								var misconduct_html = '';
								var misconduct_fields = misconduct_fields_rs.data;
								if (!misconduct_fields.hasOwnProperty('categories') || !misconduct_fields.hasOwnProperty('fields')) {
									alert('Unexptected response. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
									hideVboDialogGuestMisconduct();
									return false;
								}
								
								// main category of misconduct
								misconduct_html += '<div class="vbo-modal-form-addnew-elem vbo-modal-guestmisconduct-addnew-elem">' + "\n";
								misconduct_html += '<label for="vbo-gm-category">' + misconduct_fields['labels']['category'] + '</label>';
								misconduct_html += '<select id="vbo-gm-category" data-gmname="category_id" onchange="vboGuestMisconductChangeCategory(this.value);">' + "\n";
								for (var category in misconduct_fields['categories']) {
									if (!misconduct_fields['categories'].hasOwnProperty(category)) {
										continue;
									}
									misconduct_html += '<option value="' + misconduct_fields['categories'][category]['id'] + '">' + misconduct_fields['categories'][category]['title'] + '</option>' + "\n";
								}
								misconduct_html += '</select>' + "\n";
								misconduct_html += '</div>' + "\n";
								// push category field in fields list for later submit
								vbo_guest_misconduct_fields.push('vbo-gm-category');

								// sub-categories or additional fields of misconduct categories
								for (var category in misconduct_fields['categories']) {
									if (!misconduct_fields['categories'].hasOwnProperty(category)) {
										continue;
									}
									if (!misconduct_fields['categories'][category].hasOwnProperty('subcategories') && !misconduct_fields['categories'][category].hasOwnProperty('additional_fields')) {
										// at least one of these two properties must be set
										continue;
									}
									if (misconduct_fields['categories'][category].hasOwnProperty('subcategories') && misconduct_fields['categories'][category]['subcategories'].length) {
										// sub-categories in drop down
										var subcat_field_id = 'vbo-gm-subcategory' + misconduct_fields['categories'][category]['id'];
										misconduct_html += '<div class="vbo-modal-form-addnew-elem vbo-modal-guestmisconduct-addnew-elem vbo-modal-guestmisconduct-addnew-cond" data-gmcid="' + misconduct_fields['categories'][category]['id'] + '" style="display: none;">' + "\n";
										misconduct_html += '<label for="' + subcat_field_id + '">' + misconduct_fields['labels']['subcategory'] + '</label>';
										misconduct_html += '<select id="' + subcat_field_id + '" data-gmname="subcategory_id">' + "\n";
										for (var subcategory in misconduct_fields['categories'][category]['subcategories']) {
											if (!misconduct_fields['categories'][category]['subcategories'].hasOwnProperty(subcategory)) {
												continue;
											}
											misconduct_html += '<option value="' + misconduct_fields['categories'][category]['subcategories'][subcategory]['id'] + '">' + misconduct_fields['categories'][category]['subcategories'][subcategory]['title'] + '</option>' + "\n";
										}
										misconduct_html += '</select>' + "\n";
										misconduct_html += '</div>' + "\n";
										// push sub-category field in fields list for later submit
										if (vbo_guest_misconduct_fields.indexOf(subcat_field_id) < 0) {
											vbo_guest_misconduct_fields.push(subcat_field_id);
										}
									} else if (misconduct_fields['categories'][category].hasOwnProperty('additional_fields') && misconduct_fields['categories'][category]['additional_fields'].length) {
										// additional fields
										for (var extra_field in misconduct_fields['categories'][category]['additional_fields']) {
											if (!misconduct_fields['categories'][category]['additional_fields'].hasOwnProperty(extra_field)) {
												continue;
											}
											var field_id = 'vbo-gm-extrafield-' + misconduct_fields['categories'][category]['additional_fields'][extra_field]['name'];
											var field_name = misconduct_fields['categories'][category]['additional_fields'][extra_field]['name'];
											misconduct_html += '<div class="vbo-modal-form-addnew-elem vbo-modal-guestmisconduct-addnew-elem vbo-modal-guestmisconduct-addnew-cond" data-gmcid="' + misconduct_fields['categories'][category]['id'] + '" style="display: none;">' + "\n";
											misconduct_html += '<label for="' + field_id + '">' + misconduct_fields['categories'][category]['additional_fields'][extra_field]['label'] + '</label>';
											if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'number') {
												misconduct_html += '<input data-gmname="' + field_name + '" type="number" id="' + field_id + '" value="" step="any" min="0" />';
											} else if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'text') {
												misconduct_html += '<input data-gmname="' + field_name + '" type="text" id="' + field_id + '" value="" />';
											} else if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'text') {
												misconduct_html += '<input data-gmname="' + field_name + '" type="text" id="' + field_id + '" value="" />';
											} else if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'textarea') {
												misconduct_html += '<textarea data-gmname="' + field_name + '" id="' + field_id + '" rows="5" cols="50" maxlength="240"></textarea>';
											} else if (misconduct_fields['categories'][category]['additional_fields'][extra_field]['type'] == 'select') {
												misconduct_html += '<select data-gmname="' + field_name + '" id="' + field_id + '">';
												for (var choicekey in misconduct_fields['categories'][category]['additional_fields'][extra_field]['choices']) {
													// this is either an array when keys are numeric from 0, or an object when keys start from like 1, but with choicekey we take the proper key in the object
													if (!misconduct_fields['categories'][category]['additional_fields'][extra_field]['choices'].hasOwnProperty(choicekey)) {
														continue;
													}
													misconduct_html += '<option value="' + choicekey + '">' + misconduct_fields['categories'][category]['additional_fields'][extra_field]['choices'][choicekey] + '</option>';
												}
												misconduct_html += '</select>';
											}
											misconduct_html += '</div>' + "\n";
											// push additional field in fields list for later submit
											vbo_guest_misconduct_fields.push(field_id);
										}
									}
								}

								// mandatory fields for all categories of guest misconduct reporting
								for (var mandfield in misconduct_fields['fields']) {
									if (!misconduct_fields['fields'].hasOwnProperty(mandfield)) {
										continue;
									}
									var field_id = 'vbo-gm-mandfield-' + misconduct_fields['fields'][mandfield]['name'];
									var field_name = misconduct_fields['fields'][mandfield]['name'];
									misconduct_html += '<div class="vbo-modal-form-addnew-elem vbo-modal-guestmisconduct-addnew-elem">' + "\n";
									misconduct_html += '<label for="' + field_id + '">' + misconduct_fields['fields'][mandfield]['label'] + '</label>';
									if (misconduct_fields['fields'][mandfield]['type'] == 'number') {
										misconduct_html += '<input data-gmname="' + field_name + '" type="number" id="' + field_id + '" value="" step="any" min="0" />';
									} else if (misconduct_fields['fields'][mandfield]['type'] == 'text') {
										misconduct_html += '<input data-gmname="' + field_name + '" type="text" id="' + field_id + '" value="" />';
									} else if (misconduct_fields['fields'][mandfield]['type'] == 'text') {
										misconduct_html += '<input data-gmname="' + field_name + '" type="text" id="' + field_id + '" value="" />';
									} else if (misconduct_fields['fields'][mandfield]['type'] == 'textarea') {
										misconduct_html += '<textarea data-gmname="' + field_name + '" id="' + field_id + '" rows="5" cols="50" maxlength="240"></textarea>';
									} else if (misconduct_fields['fields'][mandfield]['type'] == 'select') {
										misconduct_html += '<select data-gmname="' + field_name + '" id="' + field_id + '">';
										for (var choicekey in misconduct_fields['fields'][mandfield]['choices']) {
											if (!misconduct_fields['fields'][mandfield]['choices'].hasOwnProperty(choicekey)) {
												continue;
											}
											misconduct_html += '<option value="' + choicekey + '">' + misconduct_fields['fields'][mandfield]['choices'][choicekey] + '</option>';
										}
										misconduct_html += '</select>';
									}
									misconduct_html += '</div>' + "\n";
									// push mandatory field in fields list for later submit
									vbo_guest_misconduct_fields.push(field_id);
								}

								// display HTML content and submit button
								jQuery('.vbo-modal-guestmisconduct-report-elems').html(misconduct_html);
								jQuery('#vbo-guest-misconduct-submit-btn').show();
								// trigger change of main category drop down
								jQuery('#vbo-gm-category').trigger('change');
							} catch (e) {
								console.log(res);
								alert('Invalid response. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
								hideVboDialogGuestMisconduct();
								return false;
							}
						},
						(err) => {
							hideVboDialogGuestMisconduct();
							alert('Request failed. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
						}
					);
				}

				function vboSubmitGuestMisconduct() {
					if (!vbo_guest_misconduct_fields.length) {
						alert('Empty data to submit. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
						return false;
					}
					
					// prevent double submissions
					vboGuestMisconductDisableSubmit();
					//

					var submit_fields = {
						tmpl: "component",
						bid: jQuery('.vbo-modal-guestmisconduct-addnew').attr('data-bid'),
						otabid: jQuery('.vbo-modal-guestmisconduct-addnew').attr('data-otabid'),
						bcom_keys: new Array
					};
					for (var elemid in vbo_guest_misconduct_fields) {
						if (!vbo_guest_misconduct_fields.hasOwnProperty(elemid)) {
							continue;
						}
						if (!jQuery('#' + vbo_guest_misconduct_fields[elemid]).length || !jQuery('#' + vbo_guest_misconduct_fields[elemid]).closest('.vbo-modal-guestmisconduct-addnew-elem').length) {
							// form element not found, or parent container not found
							continue;
						}
						if (jQuery('#' + vbo_guest_misconduct_fields[elemid]).closest('.vbo-modal-guestmisconduct-addnew-elem').is(':visible')) {
							// we include this value in the AJAX request
							var field_name = jQuery('#' + vbo_guest_misconduct_fields[elemid]).attr('data-gmname');
							var submit_key = 'bcom_' + field_name;
							submit_fields[submit_key] = jQuery('#' + vbo_guest_misconduct_fields[elemid]).val();
							submit_fields['bcom_keys'].push(submit_key);
						}
					}

					// make the AJAX request to VCM
					var jqxhr = jQuery.ajax({
						type: "POST",
						url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikchannelmanager&task=submit_bcom_guestmisconduct'); ?>",
						data: submit_fields
					}).done(function(res) {
						try {
							var misconduct_rs = JSON.parse(res);
							console.log(submit_fields, misconduct_rs);

							// make sure the request was successful
							if (!misconduct_rs.status) {
								alert(misconduct_rs.error);
								vboGuestMisconductEnableSubmit();
								return false;
							}

							// make another AJAX request (now on VBO) to update the booking history
							var event_descr = jQuery('#vbo-gm-mandfield-details_text').length ? jQuery('#vbo-gm-mandfield-details_text').val() : '';
							jQuery.ajax({
								type: "POST",
								url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=store_booking_history_event'); ?>",
								data: {
									tmpl: "component",
									bid: jQuery('.vbo-modal-guestmisconduct-addnew').attr('data-bid'),
									event: 'GM',
									descr: event_descr
								}
							}).done(function(res) {
								// just reload the page in case of success
								document.location.href = jQuery('#vbo-gm-reloadlink').attr('href');
							}).fail(function() {
								// we reload the page even in case of failure
								document.location.href = jQuery('#vbo-gm-reloadlink').attr('href');
							});
						} catch (e) {
							console.log(res);
							alert('Invalid update response. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
							vboGuestMisconductEnableSubmit();
							hideVboDialogGuestMisconduct();
							return false;
						}
					}).fail(function() {
						alert('Update Request failed. Vik Channel Manager needs to be installed, configured and updated to the latest version.');
						vboGuestMisconductEnableSubmit();
						return false;
					});
				}

				function hideVboDialogGuestMisconduct() {
					if (vbodialogguestmisconduct_on === true) {
						jQuery(".vbo-modal-overlay-block-guestmisconduct").fadeOut(400, function () {
							jQuery(".vbo-modal-overlay-content-guestmisconduct").show();
						});
						// turn flag off
						vbodialogguestmisconduct_on = false;
					}
				}

				function vboGuestMisconductChangeCategory(gmcid) {
					jQuery('.vbo-modal-guestmisconduct-addnew-cond').each(function() {
						if (jQuery(this).attr('data-gmcid') == gmcid) {
							jQuery(this).show();
						} else {
							jQuery(this).hide();
						}
					});
				}

				function vboGuestMisconductDisableSubmit() {
					jQuery('#vbo-guest-misconduct-submit-btn').prepend('<i class="<?php echo VikBookingIcons::i('refresh', 'fa-spin fa-fw'); ?>"></i> ').prop('disabled', true);
				}

				function vboGuestMisconductEnableSubmit() {
					jQuery('#vbo-guest-misconduct-submit-btn').prop('disabled', false).find('i').remove();
				}
				</script>

			</div>
				<?php
			}
			if ($this->vcm_host_to_guest_review) {
				?>
			<div class="vbo-bookdet-wrap">
				<div class="vbo-bookdet-head">
					<span><?php echo JText::translate('VBO_REVIEW_YOUR_GUEST'); ?></span>
				</div>
				<div class="vbo-bookdet-foot">
					<button type="button" class="btn btn-small btn-primary vcm-btn-channel-airbnbapi" onclick="vboOpenJModal('vbo-vcm-host-to-guest-review', 'index.php?option=com_vikchannelmanager&task=hostguestreview&cid[]=<?php echo $row['id']; ?>&tmpl=component');">
						<?php VikBookingIcons::e('smile'); ?>
						<?php echo JText::translate('VBO_WRITE_REVIEW'); ?>
					</button>
				</div>
			</div>
				<?php
				// prepare modal (host-to-guest review)
				array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-host-to-guest-review', JText::translate('VBO_REVIEW_YOUR_GUEST')));

				// check if a notification was clicked
				if (VikRequest::getString('notif_action', '', 'request') == 'airbnb_host_guest_review') {
					JFactory::getDocument()->addScriptDeclaration(
<<<JS
jQuery(function() {
	setTimeout(() => {
		jQuery('.vcm-btn-channel-airbnbapi').trigger('click');
	}, 500);
});
JS
					);
				}
			}
		}
		?>
		</div>

		<?php
		if (!$printreceipt && $this->allows_conversion !== false) {
			?>
		<div class="vbo-bookdet-container vbo-bookdet-conv-currency">
			<div class="vbo-bookdet-conv-currency-inner">
				<div class="vbo-bookdet-conv-currency-info">
					<span><?php echo JText::sprintf('VBO_CONV_RES_OTA_CURRENCY', $this->conv_from_currency, $this->conv_to_currency); ?></span>
					<span><?php echo JText::sprintf('VBO_CONV_RES_OTA_CURRENCY_EXC', ($this->conv_from_currency . ' ' . VikBooking::numberFormat($row['total'])), ($this->conv_to_currency . ' ' . VikBooking::numberFormat($this->allows_conversion))); ?></span>
				</div>
				<div class="vbo-bookdet-conv-currency-apply">
					<a class="btn vbo-config-btn" onclick="return confirm(Joomla.JText._('VBO_CONV_RES_OTA_CURRENCY_APPLY') + '?');" href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $row['id']; ?>&do_ota_curr_conv=1"><?php VikBookingIcons::e('exchange-alt'); ?> <?php echo JText::translate('VBO_CONV_RES_OTA_CURRENCY_APPLY'); ?></a>
				</div>
			</div>
		</div>
			<?php
		}

		if (!$printreceipt && $row['status'] == "standby" && empty($row['idorderota']) && !strcasecmp((string)$row['type'], 'Inquiry')) {
			// website pending reservation inquiry
			$ir_data_callback = function($data) {
				return (is_object($data) && !empty($data->av_type));
			};
			$prev_ir_data = $history_obj->getEventsWithData('IR', $ir_data_callback, true);
			if (is_array($prev_ir_data) && count($prev_ir_data)) {
				// display a message about the availability type at the time of creation of the inquiry reservation

				// parse original stay dates into DateTime objects
				$orig_checkin_dt_obj  = new JDate($prev_ir_data[0]->checkin_date);
				$orig_checkout_dt_obj = new JDate($prev_ir_data[0]->checkout_date);
				?>
		<div class="vbo-bookdet-container vbo-bookdet-inquiry-alert">
			<div class="vbo-bookdet-inquiry-alert-dismiss">
				<button type="button" class="btn btn-warning" onclick="jQuery('.vbo-bookdet-inquiry-alert').remove();"><?php VikBookingIcons::e('times-circle'); ?> <?php echo JText::translate('VBOBTNKEEPREMIND'); ?></button>
			</div>
			<div class="vbo-bookdet-inquiry-alert-message">
				<div class="vbo-bookdet-inquiry-alert-top">
					<span><?php echo JText::translate('VBO_WEB_INQUIRY_ALERT'); ?></span>
				</div>
				<div class="vbo-bookdet-inquiry-alert-avtype">
					<span class="vbo-bookdet-inquiry-alert-mess-avtype"><?php
					// explain what was the availability at the time of inquiry
					if ($prev_ir_data[0]->av_type === 1) {
						echo JText::translate('VBO_WEB_INQUIRY_AVTYPE_1');
					} elseif ($prev_ir_data[0]->av_type === 2) {
						echo JText::translate('VBO_WEB_INQUIRY_AVTYPE_2');
					} elseif ($prev_ir_data[0]->av_type === 3) {
						echo JText::translate('VBO_WEB_INQUIRY_AVTYPE_3');
					} else {
						echo JText::translate('VBO_WEB_INQUIRY_AVTYPE_4');
					}
					?></span>
					<span class="vbo-bookdet-inquiry-alert-mess-origrq">
						<strong><?php echo JText::translate('VBO_WEB_INQUIRY_ORIGRQ') . ':'; ?></strong>
						<span class="badge"><?php echo JText::translate('VBPICKUPAT') . ': ' . $orig_checkin_dt_obj->format('D, d M Y', true); ?></span>
						<span class="badge"><?php echo JText::translate('VBRELEASEAT') . ': ' . $orig_checkout_dt_obj->format('D, d M Y', true); ?></span>
						<span class="badge"><?php echo JText::translate('VBEDITORDERADULTS') . ': ' . $prev_ir_data[0]->adults; ?></span>
					<?php
					if (!empty($prev_ir_data[0]->children)) {
						?>
						<span class="badge"><?php echo JText::translate('VBEDITORDERCHILDREN') . ': ' . $prev_ir_data[0]->children; ?></span>
						<?php
					}
					?>
					</span>
				</div>
				<div class="vbo-bookdet-inquiry-alert-bottom">
					<span><?php echo JText::translate('VBO_WEB_INQUIRY_SUGG'); ?></span>
				</div>
			</div>
		</div>
				<?php
			}
		}
		?>

		<div class="vbo-bookingdet-innertop">
			<div class="vbo-bookingdet-commands">
			<?php
			if ($busy || $row['status'] == "standby") {
				?>
				<div class="vbo-bookingdet-command">
					<button onclick="document.location.href='index.php?option=com_vikbooking&task=editbusy&cid[]=<?php echo $row['id']; ?>';" class="btn vbo-config-btn" type="button"><i class="icon-pencil"></i> <?php echo JText::translate('VBMODRES'); ?></button>
				</div>
				<?php
			}
			if ((array_key_exists(1, $tars) && count($tars[1]) > 0) || ($is_package || $is_cust_cost)) {

				if (VBOPlatformDetection::isWordPress()) {
					/**
					 * @wponly 	display warning message if no valid Shortcodes found
					 */
					$model 		= JModel::getInstance('vikbooking', 'shortcodes');
					$itemid 	= $model->best('booking');
					if (!$itemid) {
						VikError::raiseWarning('', 'No Shortcodes found, or no Shortcodes being used in Pages/Posts.');
					}
				}

				?>
				<div class="vbo-bookingdet-command">
					<button onclick="window.open('<?php echo VikBooking::externalroute('index.php?option=com_vikbooking&view=booking&sid='.(!empty($row['sid']) ? $row['sid'] : $row['idorderota']).'&ts='.$row['ts'], false); ?>', '_blank');" type="button" class="btn vbo-config-btn"><?php VikBookingIcons::e('external-link'); ?> <?php echo JText::translate('VBVIEWORDFRONT'); ?></button>
				</div>
				<?php
			}
			if (($row['status'] == "confirmed" || ($row['status'] == "standby" && !empty($row['custmail']))) && ((array_key_exists(1, $tars) && count($tars[1]) > 0) || ($is_package || $is_cust_cost))) {
				?>
				<div class="vbo-bookingdet-command">
					<button class="btn vbo-config-btn" type="button" onclick="document.location.href='index.php?option=com_vikbooking&task=resendordemail&cid[]=<?php echo $row['id']; ?>';"><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBRESENDORDEMAIL'); ?></button>
				</div>
				<?php
			}
			if (is_array($this->vcm_special_offer)) {
				if (empty($this->vcm_special_offer[3]) || !empty($this->vcm_special_offer[4])) {
					// no special offer sent before, or withdrawn
				?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-primary vcm-btn-channel-<?php echo strtolower($this->vcm_special_offer[2]); ?>" onclick="vboOpenJModal('vbo-vcm-special-offer', 'index.php?option=com_vikchannelmanager&task=specialoffer&bid=<?php echo $row['id']; ?>&listing_id=<?php echo $this->vcm_special_offer[0]; ?>&ota_thread_id=<?php echo $this->vcm_special_offer[1]; ?>&tmpl=component');"><?php VikBookingIcons::e('certificate'); ?> <?php echo JText::sprintf('VBO_VCM_SPECIAL_OFFER', $this->vcm_special_offer[2]); ?></button>
				</div>
				<?php
					// prepare modal (VCM prepare special offer)
					array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-special-offer', JText::sprintf('VBO_VCM_SPECIAL_OFFER', $this->vcm_special_offer[2])));
					// end prepare modal
				} else {
					// special offer sent before, link to withdraw it
					?>
				<div class="vbo-bookingdet-command">
					<a href="index.php?option=com_vikchannelmanager&task=sendSpecialOffer&vbo_oid=<?php echo $row['id']; ?>&spo_type=special_offer&spo_action=withdraw&ota_thread_id=<?php echo $this->vcm_special_offer[1]; ?>&spo_id=<?php echo $this->vcm_special_offer[3]; ?>" class="btn btn-danger"><?php VikBookingIcons::e('ban'); ?> <?php echo JText::translate('VBO_WITHDRAW_SPOFFER'); ?></a>
				</div>
					<?php
				}
			}
			if ($row['status'] == "standby" || ($row['status'] == "cancelled" && $row['checkout'] >= time())) {
				?>
				<div class="vbo-bookingdet-command">
					<button class="btn btn-success" type="button" onclick="if (confirm('<?php echo htmlspecialchars(JText::translate('VBSETORDCONFIRMED')); ?> ?')) {document.location.href='index.php?option=com_vikbooking&task=setordconfirmed&cid[]=<?php echo $row['id'] . ($row['status'] == "cancelled" ? '&skip_notification=1' : ''); ?>';}"><?php VikBookingIcons::e('check'); ?> <?php echo JText::translate('VBSETORDCONFIRMED'); ?></button>
				</div>
				<?php
			}
			if ($row['status'] == "cancelled" && !$row['closure']) {
				?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-warning vbo-context-menu-btn vbo-context-menu-actionscanc">
						<span class="vbo-context-menu-lbl"><?php echo JText::translate('VBCRONACTIONS'); ?> &#8230;</span>
						<span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
					</button>
				</div>

				<script type="text/javascript">
					jQuery(function() {
						jQuery('.vbo-context-menu-actionscanc').vboContextMenu({
							placement: 'bottom-right',
							buttons: [
								<?php
								if (!empty($row['custmail'])) :
								?>
								{
									icon: '<?php echo VikBookingIcons::i('envelope'); ?>',
									text: Joomla.JText._('VBSENDCANCORDEMAIL'),
									class: 'vbo-context-menu-entry-danger',
									separator: true,
									action: (root, config) => {
										document.location.href = 'index.php?option=com_vikbooking&task=sendcancordemail&cid[]=<?php echo $row['id']; ?>';
									},
								},
								<?php
								endif;
								?>
								{
									icon: '<?php echo VikBookingIcons::i('hourglass-half'); ?>',
									text: Joomla.JText._('VBO_SET_STANDBY'),
									class: 'vbo-context-menu-entry-warning',
									separator: false,
									action: (root, config) => {
										document.location.href = '<?php echo VBOFactory::getPlatform()->getUri()->addCSRF('index.php?option=com_vikbooking&task=bookings.set_to_pending&bid=' . $row['id']); ?>';
									},
								},
							],
						});
					});
				</script>
				<?php
			}
			if ($row['status'] == 'confirmed' && $row['closure'] < 1) {
				?>
				<div class="vbo-bookingdet-command pro-feature">
					<button type="button" class="btn vbo-config-btn" onclick="document.getElementById('invnotes-hid').value=document.getElementById('invnotes').value;document.getElementById('vbo-gen-invoice').submit();"><?php VikBookingIcons::e('file-text-o'); ?> <?php echo JText::translate('VBOGENBOOKINGINVOICE'); ?></button>
				</div>
				<?php
			}
			if ($row['status'] == 'confirmed' && $row['closure'] < 1) {
				?>
				<div class="vbo-bookingdet-command pro-feature">
					<button type="button" class="btn vbo-config-btn" onclick="window.open('index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $row['id']; ?>&print=1&tmpl=component', '_blank');"><?php VikBookingIcons::e('print'); ?> <?php echo JText::translate('VBOPRINTRECEIPT'); ?></button>
				</div>
				<?php
			}
			if ($row['status'] != 'confirmed' || $row['closure'] > 0 || $this->vcm_cancel_active_res) {
				if ($this->vcm_decline_actions) {
					// this booking requires some decline actions before being removed
					?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-danger" onclick="vboOpenJModal('vbo-vcm-decline-booking', 'index.php?option=com_vikchannelmanager&task=declinebooking&cid[]=<?php echo $row['id']; ?>&tmpl=component');"><?php VikBookingIcons::e('trash'); ?> <?php echo JText::translate('VBMAINEBUSYDEL'); ?></button>
				</div>
					<?php
					// prepare modal (VCM decline booking)
					array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-decline-booking', JText::translate('VBMAINEBUSYDEL')));
					// end prepare modal
				} elseif ($this->vcm_cancel_active_res) {
					// the active OTA booking can be cancelled
					?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-danger" onclick="vboOpenJModal('vbo-vcm-cancel-reservation', 'index.php?option=com_vikchannelmanager&task=cancelreservation&vbo_oid=<?php echo $row['id']; ?>&tmpl=component');"><?php VikBookingIcons::e('trash'); ?> <?php echo JText::translate('VBMAINEBUSYDEL'); ?></button>
				</div>
					<?php
					// prepare modal (VCM cancel reservation)
					array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-cancel-reservation', JText::translate('VBMAINEBUSYDEL')));
					// end prepare modal
				} else {
					// regular remove button with confirmation
					?>
				<div class="vbo-bookingdet-command">
					<button type="button" class="btn btn-danger" onclick="if (confirm(Joomla.JText._('VBDELCONFIRM'))){document.location.href='index.php?option=com_vikbooking&task=removeorders&cid[]=<?php echo $row['id']; ?>&goto=<?php echo $row['id']; ?>';}"><?php VikBookingIcons::e('trash'); ?> <?php echo $row['status'] == 'cancelled' ? JText::translate('VBO_PURGERM_RESERVATION') : JText::translate('VBMAINEBUSYDEL'); ?></button>
				</div>
					<?php
				}
			}
			?>

			<!-- Define role to detect the supported hook -->
			<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorder","key":"buttons","type":"field"} -->

			<?php	
			if (isset($bookingPanelForms['buttons']))
			{
				echo $bookingPanelForms['buttons'];
			}
			?>
			</div>

			<div class="vbo-bookingdet-tabs">
				<div class="vbo-bookingdet-tab vbo-bookingdet-tab-active" data-vbotab="vbo-tab-details"><?php echo JText::translate('VBOBOOKDETTABDETAILS'); ?></div>
				<div class="vbo-bookingdet-tab" data-vbotab="vbo-tab-admin"><?php echo JText::translate('VBOBOOKDETTABADMIN'); ?></div>
			</div>
		</div>

		<div class="vbo-bookingdet-tab-cont" id="vbo-tab-details" style="display: block;">
			<div class="vbo-bookingdet-innercontainer">
				<div class="vbo-bookingdet-customer">
					<div class="vbo-bookingdet-detcont<?php echo $row['closure'] > 0 ? ' vbo-bookingdet-closure' : ''; ?>">
					<?php
					$custdata_parts = explode("\n", $row['custdata']);
					if (count($custdata_parts) > 2 && strpos($custdata_parts[0], ':') !== false && strpos($custdata_parts[1], ':') !== false) {
						//attempt to format labels and values
						foreach ($custdata_parts as $custdet) {
							if (strlen($custdet) < 1) {
								continue;
							}
							$custdet_parts = explode(':', $custdet);
							$custd_lbl = '';
							$custd_val = '';
							if (count($custdet_parts) < 2) {
								$custd_val = $custdet;
							} else {
								$custd_lbl = $custdet_parts[0];
								if ($custd_lbl == 'http' || $custd_lbl == 'https') {
									// shift values
									$custdet_parts[2] = $custdet_parts[1];
									$custdet_parts[1] = $custdet_parts[0];
									$custd_lbl = '';
								}
								unset($custdet_parts[0]);
								if ((!strcasecmp(trim($custdet_parts[1]), 'http') || !strcasecmp(trim($custdet_parts[1]), 'https')) && !empty($custdet_parts[2]) && strpos($custdet_parts[2], '//') !== false) {
									// this is a URI
									$custd_val = '<a href="' . trim(implode(':', $custdet_parts)) . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i></a>';
								} else {
									$custd_val = trim(implode(':', $custdet_parts));
								}
							}
							?>
						<div class="vbo-bookingdet-userdetail">
							<?php
							if (strlen($custd_lbl)) {
								?>
							<span class="vbo-bookingdet-userdetail-lbl"><?php echo VikBooking::tnCustomerRawDataLabel($custd_lbl); ?></span>
								<?php
							}
							if (strlen($custd_val)) {
								?>
							<span class="vbo-bookingdet-userdetail-val"><?php echo $custd_val; ?></span>
								<?php
							}
							?>
						</div>
							<?php
						}
					} else {
						if ($row['closure'] > 0) {
							?>
						<div class="vbo-bookingdet-userdetail">
							<span class="vbo-bookingdet-userdetail-val"><?php echo nl2br($row['custdata']); ?></span>
						</div>
							<?php
						} else {
							echo nl2br($row['custdata']);
							?>
						<div class="vbo-bookingdet-userdetail">
							<span class="vbo-bookingdet-userdetail-val">&nbsp;</span>
						</div>
							<?php
						}
					}
					if (!empty($row['ujid'])) {
						$orig_user = JFactory::getUser($row['ujid']);
						$author_name = is_object($orig_user) && property_exists($orig_user, 'name') && !empty($orig_user->name) ? $orig_user->name : '';
						?>
						<div class="vbo-bookingdet-userdetail">
							<span class="vbo-bookingdet-userdetail-val"><?php echo JText::sprintf('VBOBOOKINGCREATEDBY', $row['ujid'].(!empty($author_name) ? ' ('.$author_name.')' : '')); ?></span>
						</div>
						<?php
					}
					?>

					<!-- Define role to detect the supported hook -->
					<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderSidebar","key":"userdetails","type":"field"} -->

					<?php	
					if (isset($bookingSidebarForms['userdetails']))
					{
						echo $bookingSidebarForms['userdetails'];
					}
					?>

					</div>

				<?php
				// build list of invoice details (path/uri & label/button), one by default
				$invoice_details = [
					[
						'path'  => implode(DIRECTORY_SEPARATOR, [VBO_SITE_PATH, 'helpers', 'invoices', 'generated', "{$row['id']}_{$row['sid']}.pdf"]),
						'uri'   => VBO_SITE_URI . 'helpers/invoices/generated/' . "{$row['id']}_{$row['sid']}.pdf",
						'label' => JText::translate('VBOCOLORTAGRULEINVONE'),
						'btn'   => JText::translate('VBOINVDOWNLOAD'),
					]
				];

				/**
				 * Merge the additional invoices generated by the e-invoicing drivers for this booking.
				 * 
				 * @since 	1.16.7 (J) - 1.6.7 (WP)
				 */
				$invoice_details = array_merge($invoice_details, VBOEinvoicingFactory::getInstance()->getBookingExtraInvoices($row['id']));

				/**
				 * Trigger event to allow third-party plugins to define additional invoices or paths/urls.
				 * 
				 * @since 	1.16.7 (J) - 1.6.7 (WP)
				 */
				VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeListInvoices', [$row, &$invoice_details]);

				// check if an invoice exists
				$invoiced = false;
				foreach ((array) $invoice_details as $invoice_detail) {
					$invoiced = $invoiced || is_file($invoice_detail['path']);
				}

				// extra booking details
				if (!$printreceipt && ((!empty($row['channel']) && !empty($row['idorderota'])) || strlen((string)$row['confirmnumber']) || $invoiced)) {
					?>
					<div class="vbo-bookingdet-detcont vbo-bookingdet-detcont-labels-wrap vbo-hidein-print">
					<?php
					if (!empty($row['channel']) && !empty($row['idorderota'])) {
						?>
						<div class="vbo-bookingdet-detcont-label vbo-bookingdet-detcont-label-idorderota">
							<span class="label label-info">
								<span><?php echo $otachannel_name . ' ID'; ?></span>
								<span class="badge"><?php echo !strcasecmp((string)$row['type'], 'Inquiry') && $row['status'] == 'standby' ? '-----' : $row['idorderota']; ?></span>
							</span>
						</div>
						<?php
					}
					if (strlen((string)$row['confirmnumber'])) {
						?>
						<div class="vbo-bookingdet-detcont-label vbo-bookingdet-detcont-label-confirmnumber">
							<span class="label label-success">
								<span><?php echo JText::translate('VBCONFIRMNUMB'); ?></span>
								<span class="badge"><?php echo $row['confirmnumber']; ?></span>
							</span>
						</div>
						<?php
					}
					if ($invoiced) {
						foreach ($invoice_details as $invoice_detail) {
							?>
						<div class="vbo-bookingdet-detcont-label vbo-bookingdet-detcont-label-invoice">
							<span class="label label-success">
								<span><?php echo $invoice_detail['label'] ?? JText::translate('VBOCOLORTAGRULEINVONE'); ?></span>
								<span class="badge"><a href="<?php echo $invoice_detail['uri'] . (strpos($invoice_detail['uri'], '?') === false ? '?' . rand() . '=1' : ''); ?>" target="_blank"><?php VikBookingIcons::e('download'); ?> <?php echo $invoice_detail['btn'] ?? JText::translate('VBOINVDOWNLOAD'); ?></a></span>
							</span>
						</div>
							<?php
						}
					}
					if ($this->vcm_review) {
						?>
						<div class="vbo-bookingdet-detcont-label vbo-bookingdet-detcont-label-review">
							<span class="label label-warning">
								<a href="index.php?option=com_vikchannelmanager&task=reviews&revid=<?php echo $this->vcm_review['id']; ?>" target="_blank" style="color: #fff;"><i class="vboicn-star-full"></i> <?php echo JText::translate('VBOSEEGUESTREVIEW'); ?></a>
							</span>
						</div>
						<?php
						// check if a notification was clicked
						if (VikRequest::getString('notif_action', '', 'request') == 'see_guest_review') {
							$vcm_review_link = VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikchannelmanager&task=reviews&revid=' . $this->vcm_review['id'], false);
							JFactory::getDocument()->addScriptDeclaration(
<<<JS
jQuery(function() {
	setTimeout(() => {
		document.location.href = '$vcm_review_link';
	}, 200);
});
JS
							);
						}
					}
					?>

					<!-- Define role to detect the supported hook -->
					<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderSidebar","key":"invoice","type":"field"} -->

					<?php	
					if (isset($bookingSidebarForms['invoice']))
					{
						echo $bookingSidebarForms['invoice'];
					}
					?>

					</div>
					<?php
				}
				if (!$printreceipt && $row['closure'] < 1) {
				?>
					<div class="vbo-bookingdet-detcont vbo-hidein-print">
						<div class="vbo-bookingdet-lblcont">
							<label for="custmail"><?php echo JText::translate('VBCUSTEMAIL'); ?></label>
						</div>
						<div class="vbo-bookingdet-inpwrap">
							<div class="vbo-bookingdet-inpcont">
								<input type="text" name="custmail" id="custmail" value="<?php echo $this->escape($row['custmail']); ?>" size="25" onkeyup="vboKeyupEmail(event);"/>
							</div>
							<div class="vbo-bookingdet-btncont vbo-bookingdet-save-email" style="display: none;">
								<button type="button" class="btn btn-success" onclick="document.adminForm.submit();"><?php VikBookingIcons::e('save'); ?> <?php echo JText::translate('VBSAVE'); ?></button>
							</div>
						<?php if (!empty($row['custmail'])) : ?>
							<div class="vbo-bookingdet-btncont">
								<button type="button" class="btn vbo-config-btn" onclick="vboToggleSendEmail();" style="vertical-align: top;"><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBSENDEMAILACTION'); ?></button>
							</div>
						<?php endif; ?>
						</div>

						<!-- Define role to detect the supported hook -->
						<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderSidebar","key":"email","type":"field"} -->

						<?php	
						if (isset($bookingSidebarForms['email']))
						{
							echo $bookingSidebarForms['email'];
						}
						?>

					</div>
					<div class="vbo-bookingdet-detcont vbo-hidein-print">
						<div class="vbo-bookingdet-lblcont">
							<label for="custphone"><?php echo JText::translate('VBCUSTOMERPHONE'); ?></label>
						</div>
						<div class="vbo-bookingdet-inpwrap">
							<div class="vbo-bookingdet-inpcont">
								<?php echo $vbo_app->printPhoneInputField(array('name' => 'custphone', 'id' => 'custphone', 'value' => $this->escape($row['phone'])), array('nationalMode' => false, 'fullNumberOnBlur' => true)); ?>
							</div>
						<?php if (!empty($row['phone'])) : ?>
							<div class="vbo-bookingdet-btncont">
								<button type="button" class="btn vbo-config-btn" onclick="vboToggleSendSMS();" style="vertical-align: top;"><?php VikBookingIcons::e('comment-dots'); ?> <?php echo JText::translate('VBSENDSMSACTION'); ?></button>
							</div>
						<?php endif; ?>
						</div>

						<!-- Define role to detect the supported hook -->
						<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderSidebar","key":"phone","type":"field"} -->

						<?php	
						if (isset($bookingSidebarForms['phone']))
						{
							echo $bookingSidebarForms['phone'];
						}
						?>

					</div>

					<!-- Define role to detect the supported hook -->
					<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderSidebar","type":"fieldset"} -->

					<?php
					foreach ($bookingSidebarForms as $legend => $form)
					{
						if (in_array($legend, ['userdetails', 'invoice', 'email', 'phone']))
						{
							// skip default forms
							continue;
						}
						?>
						<div class="vbo-bookingdet-detcont vbo-hidein-print">
							<h3><?php echo JText::translate($legend); ?></h3>
							
							<?php echo $form; ?>
						</div>
						<?php
					}
					?>

				<?php
				}
				?>
				</div>

				<?php
				$all_id_prices = array();
				$used_indexes_map = array();
				?>
				<div class="vbo-bookingdet-summary">
					<div class="table-responsive">
						<table class="table">
						<?php
						// room stay dates in case of split stay (or modified room nights)
						$room_stay_dates   = [];
						$room_stay_records = [];
						if ($row['split_stay']) {
							if ($row['status'] == 'confirmed') {
								$room_stay_dates = $av_helper->loadSplitStayBusyRecords($row['id']);
							} else {
								$room_stay_dates = VBOFactory::getConfig()->getArray('split_stay_' . $row['id'], []);
							}
						} elseif (!$row['split_stay'] && $row['roomsnum'] > 1 && $row['days'] > 1 && $row['status'] == 'confirmed') {
							// load the occupied stay dates for each room in case they were modified
							$room_stay_records = $av_helper->loadSplitStayBusyRecords($row['id']);
						}

						// parse all rooms booked
						foreach ($rooms as $ind => $or) {
							$num = $ind + 1;
							$room_icon = $row['split_stay'] && $ind > 0 ? 'random' : 'bed';
							$room_stay_nights = $row['days'];
							?>
							<tr class="vbo-bookingdet-summary-room">
								<td class="vbo-bookingdet-summary-room-firstcell">
									<div class="vbo-bookingdet-summary-room-wrap">
										<div class="vbo-bookingdet-summary-roomnum"><?php VikBookingIcons::e($room_icon); ?> <?php echo JText::translate('VBEDITORDERTHREE').' '.$num; ?></div>
									<?php
									//Room Specific Unit
									if (!$row['closure'] && $row['status'] == "confirmed" && !empty($or['params'])) {
										$room_params = json_decode($or['params'], true);
										$arr_features = array();
										$unavailable_indexes = VikBooking::getRoomUnitNumsUnavailable($row, $or['idroom']);
										if (is_array($room_params) && array_key_exists('features', $room_params) && !empty($room_params['features'])) {
											foreach ($room_params['features'] as $rind => $rfeatures) {
												if (in_array($rind, $unavailable_indexes) || (isset($used_indexes_map[$or['idroom']]) && in_array($rind, $used_indexes_map[$or['idroom']]))) {
													continue;
												}
												foreach ($rfeatures as $fname => $fval) {
													if (strlen($fval)) {
														$arr_features[$rind] = '#'.$rind.' - '.JText::translate($fname).': '.$fval;
														break;
													}
												}
											}
										}
										if (count($arr_features)) {
											// $or['id'] equals to the ID of each matching record in _ordersrooms
											?>
										<div class="vbo-bookingdet-summary-roomnum-chunit">
											<?php echo !$printreceipt ? $vbo_app->getNiceSelect($arr_features, $or['roomindex'], 'roomindex['.$or['id'].']', JText::translate('VBOFEATASSIGNUNIT'), JText::translate('VBOFEATASSIGNUNITEMPTY'), '', 'document.adminForm.submit();', $or['id']) : (!empty($or['roomindex']) && isset($arr_features[$or['roomindex']]) ? $arr_features[$or['roomindex']] : ''); ?>
										</div>
											<?php
											if (!empty($or['idroom']) && !empty($or['roomindex'])) {
												if (!array_key_exists($or['idroom'], $used_indexes_map)) {
													$used_indexes_map[$or['idroom']] = array();
												}
												$used_indexes_map[$or['idroom']][] = $or['roomindex'];
											}
										}
									}
									//
									?>
									</div>
									<div class="vbo-bookingdet-summary-roomguests">
										<?php VikBookingIcons::e('male'); ?>
										<div class="vbo-bookingdet-summary-roomadults">
											<span><?php echo JText::translate('VBEDITORDERADULTS'); ?>:</span> <?php echo $arrpeople[$num]['adults']; ?>
										</div>
									<?php
									if ($arrpeople[$num]['children'] > 0) {
										$age_str = '';
										if (!empty($arrpeople[$num]['children_age'])) {
											$json_child = json_decode($arrpeople[$num]['children_age'], true);
											if (is_array($json_child) && isset($json_child['age']) && is_array($json_child['age']) && count($json_child['age'])) {
												$age_str = ' '.JText::sprintf('VBORDERCHILDAGES', implode(', ', $json_child['age']));
											}
										}
										?>
										<div class="vbo-bookingdet-summary-roomchildren">
											<span><?php echo JText::translate('VBEDITORDERCHILDREN'); ?>:</span> <?php echo $arrpeople[$num]['children'].$age_str; ?>
										</div>
										<?php
									}
									if ($arrpeople[$num]['pets'] > 0) {
										?>
										<div class="vbo-bookingdet-summary-roompets">
											<span><?php VikBookingIcons::e('dog'); ?> <?php echo JText::translate('VBO_PETS'); ?>:</span> <?php echo $arrpeople[$num]['pets']; ?>
										</div>
										<?php
									}
									?>
									</div>
									<?php
									if (!empty($arrpeople[$num]['t_first_name'])) {
									?>
									<div class="vbo-bookingdet-summary-guestname">
										<span><?php echo $arrpeople[$num]['t_first_name'].' '.$arrpeople[$num]['t_last_name']; ?></span>
									</div>
									<?php
									}
									?>
								</td>
								<td>
									<div class="vbo-bookingdet-summary-roomname"><?php echo $or['name']; ?></div>
									<div class="vbo-bookingdet-summary-roomrate">
									<?php
									$active_rplan_id = 0;
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										if (!empty($or['pkg_name'])) {
											//package
											echo $or['pkg_name'];
										} else {
											//custom cost can have an OTA Rate Plan name
											if (!empty($or['otarplan'])) {
												echo ucwords($or['otarplan']);
											} else {
												echo JText::translate('VBOROOMCUSTRATEPLAN');
											}
										}
									} elseif (!empty($tars[$num]) && !empty($tars[$num][0]['idprice'])) {
										$active_rplan_id = $tars[$num][0]['idprice'];
										$all_id_prices[] = $tars[$num][0]['idprice'];
										echo VikBooking::getPriceName($tars[$num][0]['idprice']);
										if (!empty($tars[$num][0]['attrdata'])) {
											?>
										<div>
											<?php echo VikBooking::getPriceAttr($tars[$num][0]['idprice']).": ".$tars[$num][0]['attrdata']; ?>
										</div>
											<?php
										}
									} elseif (!empty($or['otarplan'])) {
										echo ucwords($or['otarplan']);
									} elseif ($row['closure'] < 1) {
										echo JText::translate('VBOROOMNORATE');
									}
									?>
									</div>
								<?php
								// meals included in the room rate
								if (!empty($or['meals'])) {
									// display included meals defined at room-reservation record
									$included_meals = VBOMealplanManager::getInstance()->roomRateIncludedMeals($or);
								} else {
									// fetch default included meals in the selected rate plan
									$included_meals = $active_rplan_id ? VBOMealplanManager::getInstance()->ratePlanIncludedMeals($active_rplan_id) : [];
								}
								if (!$included_meals && empty($or['meals']) && !empty($row['idorderota']) && !empty($row['channel']) && !empty($row['custdata'])) {
									// attempt to fetch the included meal plans from the raw customer data or OTA reservation and room
									$included_meals = VBOMealplanManager::getInstance()->otaDataIncludedMeals($row, $or);
								}
								if ($included_meals) {
									?>
									<div class="vbo-bookingdet-summary-roomrate vbo-bookingdet-summary-roommeals vbo-wider-badges-wrap">
									<?php
									foreach ($included_meals as $included_meal) {
										?>
										<span class="badge badge-info"><?php echo $included_meal; ?></span>
										<?php
									}
									?>
									</div>
									<?php
								}

								if ($row['split_stay'] && count($room_stay_dates) && isset($room_stay_dates[$ind]) && $room_stay_dates[$ind]['idroom'] == $or['idroom']) {
									// print split stay information for this room
									$room_stay_checkin  = !empty($room_stay_dates[$ind]['checkin_ts']) ? $room_stay_dates[$ind]['checkin_ts'] : $room_stay_dates[$ind]['checkin'];
									$room_stay_checkout = !empty($room_stay_dates[$ind]['checkout_ts']) ? $room_stay_dates[$ind]['checkout_ts'] : $room_stay_dates[$ind]['checkout'];
									$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
									?>
									<div class="vbo-cal-splitstay-details vbo-bookdet-splitstay-details">
										<div class="vbo-cal-splitstay-dates">
											<span class="vbo-cal-splitstay-room-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo $room_stay_nights . ' ' . ($room_stay_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?></span>
											<span class="vbo-cal-splitstay-dates-in" title="<?php echo date('H:i', $room_stay_checkin); ?>"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo date(str_replace("/", $datesep, $df), $room_stay_checkin); ?></span>
											<span class="vbo-cal-splitstay-dates-out" title="<?php echo date('H:i', $room_stay_checkout); ?>"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo date(str_replace("/", $datesep, $df), $room_stay_checkout); ?></span>
										</div>
									</div>
									<?php
								} elseif (!$row['split_stay'] && count($room_stay_records) && isset($room_stay_records[$ind]) && $room_stay_records[$ind]['idroom'] == $or['idroom']) {
									// print modified stay dates information for this room
									$room_stay_checkin  = $room_stay_records[$ind]['checkin'];
									$room_stay_checkout = $room_stay_records[$ind]['checkout'];
									$room_stay_nights 	= $av_helper->countNightsOfStay($room_stay_checkin, $room_stay_checkout);
									if ($room_stay_checkin != $row['checkin'] || $room_stay_checkout != $row['checkout']) {
										?>
									<div class="vbo-cal-splitstay-details vbo-bookdet-splitstay-details vbo-bookdet-roomdatesmod-details">
										<div class="vbo-cal-splitstay-dates">
											<span class="vbo-cal-splitstay-room-nights"><?php VikBookingIcons::e('moon'); ?> <?php echo $room_stay_nights . ' ' . ($room_stay_nights > 1 ? JText::translate('VBDAYS') : JText::translate('VBDAY')); ?></span>
											<span class="vbo-cal-splitstay-dates-in" title="<?php echo date('H:i', $room_stay_checkin); ?>"><?php VikBookingIcons::e('plane-arrival'); ?> <?php echo date(str_replace("/", $datesep, $df), $room_stay_checkin); ?></span>
											<span class="vbo-cal-splitstay-dates-out" title="<?php echo date('H:i', $room_stay_checkout); ?>"><?php VikBookingIcons::e('plane-departure'); ?> <?php echo date(str_replace("/", $datesep, $df), $room_stay_checkout); ?></span>
										</div>
									</div>
									<?php
									}
								}
								?>
								</td>
								<td>
									<div class="vbo-bookingdet-summary-price">
									<?php
									if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
										echo $currencyname.' '.VikBooking::numberFormat($or['cust_cost']);
									} elseif (!empty($tars[$num]) && !empty($tars[$num][0]['idprice'])) {
										$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost'];
										echo $currencyname.' '.VikBooking::numberFormat(($after_tax ? VikBooking::sayCostPlusIva($display_rate, $tars[$num][0]['idprice']) : $display_rate));
									}
									?>
									</div>
								</td>
							</tr>
							<?php
							//Options
							if (!empty($or['optionals'])) {
								$stepo = explode(";", $or['optionals']);
								$counter = 0;
								foreach ($stepo as $roptkey => $oo) {
									if (empty($oo)) {
										continue;
									}
									$stept = explode(":", $oo);
									$q = "SELECT * FROM `#__vikbooking_optionals` WHERE `id`=".(int)$stept[0].";";
									$dbo->setQuery($q);
									$actopt = $dbo->loadAssocList();
									if (!$actopt) {
										continue;
									}
									$counter++;
									$chvar = '';
									if (!empty($actopt[0]['ageintervals']) && $or['children'] > 0 && strstr($stept[1], '-') != false) {
										$optagenames = VikBooking::getOptionIntervalsAges($actopt[0]['ageintervals']);
										$optagepcent = VikBooking::getOptionIntervalsPercentage($actopt[0]['ageintervals']);
										$optageovrct = VikBooking::getOptionIntervalChildOverrides($actopt[0], $or['adults'], $or['children']);
										$child_num 	 = VikBooking::getRoomOptionChildNumber($or['optionals'], $actopt[0]['id'], $roptkey, $or['children']);
										$optagecosts = VikBooking::getOptionIntervalsCosts(isset($optageovrct['ageintervals_child' . ($child_num + 1)]) ? $optageovrct['ageintervals_child' . ($child_num + 1)] : $actopt[0]['ageintervals']);
										$agestept = explode('-', $stept[1]);
										$stept[1] = $agestept[0];
										$chvar = $agestept[1];
										if (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 1) {
											//percentage value of the adults tariff
											if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
												$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
											} else {
												$display_rate = !empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost'];
												$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
											}
										} elseif (array_key_exists(($chvar - 1), $optagepcent) && $optagepcent[($chvar - 1)] == 2) {
											//VBO 1.10 - percentage value of room base cost
											if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
												$optagecosts[($chvar - 1)] = $or['cust_cost'] * $optagecosts[($chvar - 1)] / 100;
											} else {
												$display_rate = isset($tars[$num][0]['room_base_cost']) ? $tars[$num][0]['room_base_cost'] : (!empty($or['room_cost']) ? $or['room_cost'] : $tars[$num][0]['cost']);
												$optagecosts[($chvar - 1)] = $display_rate * $optagecosts[($chvar - 1)] / 100;
											}
										}
										if (!isset($optagenames[($chvar - 1)])) {
											$optagenames[($chvar - 1)] = '';
										}
										if (!isset($optagecosts[($chvar - 1)])) {
											$optagecosts[($chvar - 1)] = 0;
										}
										$actopt[0]['chageintv'] = $chvar;
										$actopt[0]['name'] .= ' ('.$optagenames[($chvar - 1)].')';
										$realcost = (intval($actopt[0]['perday']) == 1 ? (floatval($optagecosts[($chvar - 1)]) * $room_stay_nights * $stept[1]) : (floatval($optagecosts[($chvar - 1)]) * $stept[1]));
									} else {
										// VBO 1.11 - options percentage cost of the room total fee
										if ($is_package === true || (!empty($or['cust_cost']) && $or['cust_cost'] > 0.00)) {
											$deftar_basecosts = $or['cust_cost'];
										} else {
											$use_def_tar = isset($tars[$num]) && isset($tars[$num][0]) ? $tars[$num][0]['cost'] : 0;
											$deftar_basecosts = !empty($or['room_cost']) ? $or['room_cost'] : $use_def_tar;
										}
										$actopt[0]['cost'] = (int)$actopt[0]['pcentroom'] ? ($deftar_basecosts * $actopt[0]['cost'] / 100) : $actopt[0]['cost'];
										//
										$realcost = (intval($actopt[0]['perday']) == 1 ? ($actopt[0]['cost'] * $room_stay_nights * $stept[1]) : ($actopt[0]['cost'] * $stept[1]));
									}
									if ($actopt[0]['maxprice'] > 0 && $realcost > $actopt[0]['maxprice']) {
										$realcost = $actopt[0]['maxprice'];
										if (intval($actopt[0]['hmany']) == 1 && intval($stept[1]) > 1) {
											$realcost = $actopt[0]['maxprice'] * $stept[1];
										}
									}
									$realcost = $actopt[0]['perperson'] == 1 ? ($realcost * $arrpeople[$num]['adults']) : $realcost;
									$tmpopr = VikBooking::sayOptionalsPlusIva($realcost, $actopt[0]['idiva']);
									?>
							<tr class="vbo-bookingdet-summary-options">
								<td class="vbo-bookingdet-summary-options-title"><?php echo $counter == 1 ? JText::translate('VBEDITORDEREIGHT') : '&nbsp;'; ?></td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo ($stept[1] > 1 ? $stept[1]." " : "").$actopt[0]['name']; ?></span>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost"><?php echo $currencyname." ".VikBooking::numberFormat(($after_tax ? $tmpopr : $realcost)); ?></span>
								</td>
							</tr>
								<?php
								}
							}
							//Custom extra costs
							if (!empty($or['extracosts'])) {
								$counter = 0;
								$cur_extra_costs = json_decode($or['extracosts'], true);
								foreach ($cur_extra_costs as $eck => $ecv) {
									$counter++;
									$ecv['idtax'] = isset($ecv['idtax']) ? $ecv['idtax'] : 0;
									?>
							<tr class="vbo-bookingdet-summary-custcosts">
								<td class="vbo-bookingdet-summary-custcosts-title"><?php echo $counter == 1 ? JText::translate('VBPEDITBUSYEXTRACOSTS') : '&nbsp;'; ?></td>
								<td>
								<?php
								if (!empty($ecv['type'])) {
									?>
									<span class="vbo-tooltip vbo-tooltip-top" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', JText::translate('VBPSHOWSEASONSTHREE') . ': ' . $ecv['type']); ?>">
										<span class="vbo-bookingdet-summary-lbl"><?php echo $ecv['name']; ?></span>
									</span>
									<?php
								} else {
									?>
									<span class="vbo-bookingdet-summary-lbl"><?php echo $ecv['name']; ?></span>
									<?php
								}
								?>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost"><?php echo $currencyname." ".VikBooking::numberFormat(($after_tax ? VikBooking::sayOptionalsPlusIva($ecv['cost'], $ecv['idtax']) : $ecv['cost'])); ?></span>
								</td>
							</tr>
									<?php
								}
							}
						}
						//vikbooking 1.1 coupon
						if (strlen((string)$row['coupon']) > 0) {
							$expcoupon = explode(";", $row['coupon']);
							?>
							<tr class="vbo-bookingdet-summary-coupon">
								<td><?php echo JText::translate('VBCOUPON'); ?></td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo $expcoupon[2]; ?></span>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost">- <?php echo $currencyname; ?> <?php echo VikBooking::numberFormat($expcoupon[1]); ?></span>
								</td>
							</tr>
							<?php
						}
						if ($row['refund'] > 0) {
							?>
							<tr class="vbo-bookingdet-summary-totpaid vbo-bookingdet-summary-totrefunded">
								<td>
									<strong><?php echo JText::translate('VBOBOOKHISTORYTRF'); ?></strong>
								</td>
								<td>
									<span class="vbo-amount-refunded-lbl"><?php echo JText::translate('VBO_AMOUNT_REFUNDED'); ?></span>
								</td>
								<td>
									<div id="vbo-amountrefunded-cont">
										<span id="vbo-amountrefunded-current"><?php echo $currencyname.' '.VikBooking::numberFormat($row['refund']); ?></span>
										<span id="vbo-amountrefunded-edit" style="margin-left: 5px; cursor: pointer;"><?php VikBookingIcons::e('edit'); ?></span>
									</div>
									<div id="vbo-amountrefunded-modcont" style="display: none;">
										<span id="vbo-amountrefunded-cancedit" style="margin-right: 5px; cursor: pointer;"><?php VikBookingIcons::e('times'); ?></span>
										<span id="vbo-amountrefunded-new"><input type="number" step="any" name="newamountrefunded" value="" min="0" style="margin: 0;" placeholder="<?php echo $row['refund']; ?>" disabled /></span>
										<span id="vbo-amountrefunded-save"><button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button></span>
									</div>
								</td>
							</tr>
							<?php
						}
						//Reservation Total
						//Taxes Breakdown (only if tot_taxes is greater than 0)
						$tax_breakdown = array();
						$base_aliq = 0;
						if (count($all_id_prices) > 0 && $row['tot_taxes'] > 0) {
							//only last type of price assuming that the tax breakdown is equivalent in case of different rates
							$q = "SELECT `p`.`id`,`p`.`name`,`p`.`idiva`,`t`.`aliq`,`t`.`breakdown` FROM `#__vikbooking_prices` AS `p` LEFT JOIN `#__vikbooking_iva` `t` ON `p`.`idiva`=`t`.`id` WHERE `p`.`id`=".intval(array_pop($all_id_prices))." LIMIT 1;";
							$dbo->setQuery($q);
							$breakdown_info = $dbo->loadAssoc();
							if ($breakdown_info) {
								if (!empty($breakdown_info['breakdown']) && !empty($breakdown_info['aliq'])) {
									$tax_breakdown = json_decode($breakdown_info['breakdown'], true);
									$tax_breakdown = is_array($tax_breakdown) && count($tax_breakdown) > 0 ? $tax_breakdown : array();
									$base_aliq = $breakdown_info['aliq'];
								}
							}
						}
						//
						if ($printreceipt && $row['tot_taxes'] > 0) {
							// only when printing the receipt we display an additional row for the total amount of tax
							?>
							<tr class="vbo-bookingdet-summary-total vbo-bookingdet-summary-totaltax">
								<td>
									
								</td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo JText::translate('VBTOTALVAT'); ?></span>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost"><?php echo $currencyname; ?> <?php echo VikBooking::numberFormat($row['tot_taxes']); ?></span>
								</td>
							</tr>
							<?php
						}
						?>
							<tr class="vbo-bookingdet-summary-total">
								<td>
								<?php
								if (!$printreceipt) {
									?>
									<span class="vbapplydiscsp" onclick="toggleDiscount(this);">
										<i class="<?php echo VikBookingIcons::i('chevron-down'); ?>" title="<?php echo htmlspecialchars(JText::translate('VBAPPLYDISCOUNT')); ?>"></i>
									</span>
									<?php
								}
								?>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-lbl"><?php echo JText::translate('VBEDITORDERNINE'); ?></span>

									<div class="vbdiscenter" id="vbdiscenter" style="display: none;">
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label"><?php echo JText::translate('VBTOTALVAT'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="tot_taxes" value="<?php echo $row['tot_taxes']; ?>" size="4"/></span>
										</div>
									<?php
									if (count($tax_breakdown)) {
										foreach ($tax_breakdown as $tbkk => $tbkv) {
											$tax_break_cost = $row['tot_taxes'] * floatval($tbkv['aliq']) / $base_aliq;
											?>
										<div class="vbdiscenter-entry vbdiscenter-entry-breakdown">
											<span class="vbdiscenter-label"><?php echo $tbkv['name']; ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <?php echo VikBooking::numberFormat($tax_break_cost); ?></span>
										</div>
											<?php
										}
									}
									?>
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label"><?php echo JText::translate('VBTOTALCITYTAX'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="tot_city_taxes" value="<?php echo $row['tot_city_taxes']; ?>" size="4"/></span>
										</div>
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label"><?php echo JText::translate('VBTOTALFEES'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="tot_fees" value="<?php echo $row['tot_fees']; ?>" size="4"/></span>
										</div>
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label hasTooltip"<?php echo !empty($otachannel_name) ? ' title="'.$otachannel_name.'"' : ''; ?>><?php echo JText::translate('VBTOTALCOMMISSIONS'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="cmms" value="<?php echo $row['cmms']; ?>" size="4"/></span>
										</div>
										<div class="vbdiscenter-entry">
											<span class="vbdiscenter-label"><?php echo JText::translate('VBAPPLYDISCOUNT'); ?>:</span><span class="vbdiscenter-value"><?php echo $currencyname; ?> <input type="number" step="any" name="admindisc" value="<?php echo isset($expcoupon) ? (float)$expcoupon[1] : ''; ?>" size="4"/></span>
										</div>
										<div class="vbdiscenter-entrycentered">
											<button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button>
										</div>
									</div>
								</td>
								<td>
									<span class="vbo-bookingdet-summary-cost"><?php echo (strlen($otacurrency) > 0 ? '('.$otacurrency.') '.$currencyname : $currencyname); ?> <?php echo VikBooking::numberFormat($row['total']); ?></span>
								</td>
							</tr>
						<?php
						if ($row['total'] > 0 && !$row['closure']) {
							$has_paid = ($row['totpaid'] > 0);
							$diff_to_pay = $has_paid ? ($row['total'] - $row['totpaid']) : 0;
							if ($has_paid || $row['status'] != 'standby') {
							?>
							<tr class="vbo-bookingdet-summary-totpaid">
								<td>&nbsp;</td>
								<td><?php echo JText::translate('VBAMOUNTPAID'); ?></td>
								<td>
									<div id="vbo-amountpaid-cont">
										<span id="vbo-amountpaid-current"><?php echo $has_paid ? ($currencyname . ' ' . VikBooking::numberFormat($row['totpaid'])) : ''; ?></span>
										<span id="vbo-amountpaid-edit" style="margin-left: 5px; cursor: pointer;"><?php VikBookingIcons::e('edit'); ?></span>
									</div>
									<div id="vbo-amountpaid-modcont" style="display: none;">
										<span id="vbo-amountpaid-cancedit" style="margin-right: 5px; cursor: pointer;"><?php VikBookingIcons::e('times'); ?></span>
										<span id="vbo-amountpaid-new"><input type="number" step="any" name="newamountpaid" value="" min="0" style="margin: 0;" placeholder="<?php echo $has_paid ? $row['totpaid'] : ''; ?>" disabled /></span>
									<?php
									if ($payments) {
										?>
										<span id="vbo-amountpaid-paymeth" style="margin: 0 5px; max-width: 160px;">
											<select name="newamountpaymeth" style="margin: 0;">
												<option value=""><?php echo JText::translate('VBPAYMENTMETHOD'); ?></option>
											<?php
											foreach ($payments as $pay) {
												?>
												<option value="<?php echo JHtml::fetch('esc_attr', $pay['id'] . '_' . $pay['name']); ?>"><?php echo $pay['name']; ?></option>
												<?php
											}
											?>
											</select>
										</span>
										<?php
									}
									?>
										<span id="vbo-amountpaid-save"><button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button></span>
									</div>
								</td>
							</tr>
							<?php
							}
							if ($diff_to_pay > 1 && $row['status'] != 'cancelled') {
							?>
							<tr class="vbo-bookingdet-summary-totpaid vbo-bookingdet-summary-totremaining">
								<td>&nbsp;</td>
								<td>
									<div><?php echo JText::translate('VBTOTALREMAINING'); ?></div>
									<?php
									// enable second payment
									if (!$printreceipt && $row['status'] == 'confirmed' && !($row['paymcount'] > 0) && VikBooking::multiplePayments() && is_array($payment) && !empty($payment['id'])) {
										?>
										<div style="margin-top: 5px;">
											<a href="index.php?option=com_vikbooking&amp;task=editorder&amp;makepay=1&amp;cid[]=<?php echo $row['id']; ?>" class="vbo-makepayable-link"><?php VikBookingIcons::e('credit-card'); ?> <?php echo JText::translate('VBMAKEORDERPAYABLE'); ?></a>
										</div>
										<?php
									}
									?>
								</td>
								<td><?php echo $currencyname . ' ' . VikBooking::numberFormat($diff_to_pay); ?></td>
							</tr>
							<?php
							}
						}
						if (!$printreceipt && $row['status'] == 'confirmed' && VikBooking::multiplePayments() && is_array($payment) && !empty($payment['id']) && $row['checkout'] > time()) {
							/**
							 * The amount payable can be modified by the admin.
							 * 
							 * @since 	1.14 (J) - 1.4.0 (WP)
							 */
							?>
							<tr class="vbo-bookingdet-summary-totpaid vbo-bookingdet-summary-totpayable">
								<td>&nbsp;</td>
								<td>
									<span class="vbo-amount-payable-lbl<?php echo $row['payable'] > 0 ? ' vbo-amount-payable-lbl-requested' : ''; ?>"><?php echo $row['payable'] > 0 ? JText::translate('VBO_AMOUNT_PAYABLE') : JText::translate('VBO_AMOUNT_PAYABLE_RQ'); ?></span>
								</td>
								<td>
									<div id="vbo-amountpayable-cont">
									<?php
									if ($row['payable'] > 0) {
										?>
										<span id="vbo-amountpayable-current"><?php echo $currencyname . ' ' . VikBooking::numberFormat($row['payable']); ?></span>
										<?php
									}
									?>
										<span id="vbo-amountpayable-edit" style="margin-left: 5px; cursor: pointer;"><?php VikBookingIcons::e('edit'); ?></span>
									</div>
									<div id="vbo-amountpayable-modcont" style="display: none;">
										<span id="vbo-amountpayable-cancedit" style="margin-right: 5px; cursor: pointer;"><?php VikBookingIcons::e('times'); ?></span>
										<span id="vbo-amountpayable-new"><input type="number" step="any" name="newamountpayable" value="" min="0" style="margin: 0;" placeholder="<?php echo $row['payable']; ?>" disabled /></span>
										<span id="vbo-amountpayable-save"><button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button></span>
									</div>
								</td>
							</tr>
							<?php
						}

						/**
						 * Cancellation fee for cancelled bookings after the deadline.
						 * 
						 * @since 	1.16.0 (J) - 1.6.0 (WP)
						 */
						if ($row['status'] == 'cancelled' && !$row['closure'] && $row['total'] > 0) {
							?>
							<tr class="vbo-bookingdet-summary-cancfee">
								<td>&nbsp;</td>
								<td><?php echo JText::translate('VBO_CANC_FEE'); ?></td>
								<td>
									<div id="vbo-cancfee-cont">
										<span id="vbo-cancfee-current"><?php echo $currencyname.' '.VikBooking::numberFormat($row['canc_fee']); ?></span>
										<span id="vbo-cancfee-edit" style="margin-left: 5px; cursor: pointer;"><?php VikBookingIcons::e('edit'); ?></span>
									</div>
									<div id="vbo-cancfee-modcont" style="display: none;">
										<span id="vbo-cancfee-cancedit" style="margin-right: 5px; cursor: pointer;"><?php VikBookingIcons::e('times'); ?></span>
										<span id="vbo-cancfee-new"><input type="number" step="any" name="newcancfee" value="" min="0" style="margin: 0;" placeholder="<?php echo $row['canc_fee']; ?>" disabled /></span>
										<span id="vbo-cancfee-save"><button type="submit" class="btn btn-success"><?php echo JText::translate('VBAPPLYDISCOUNTSAVE'); ?></button></span>
									</div>
								</td>
							</tr>
							<?php
						}
						?>
						</table>
					</div>

					<!-- Define role to detect the supported hook -->
					<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorder","type":"fieldset"} -->

					<?php
					foreach ($bookingPanelForms as $legend => $form)
					{
						if (in_array($legend, ['buttons']))
						{
							// skip default forms
							continue;
						}
						?>
						<div class="table-responsive">
							<h3><?php echo JText::translate($legend); ?></h3>
							
							<?php echo $form; ?>
						</div>
						<?php
					}
					?>

				</div>
			</div>
		<?php
		if ($printreceipt) {
			$receipt_notes = VikBooking::getReceiptNotes();
			?>
			<div class="vbo-receipt-notes-container">
				<div class="vbo-receipt-notes-inner">
					<div class="vbo-receipt-notes-val vbo-showin-print" id="vbo-receipt-notes-val"><?php echo $receipt_notes; ?></div>
					<div class="vbo-receipt-notes-tarea vbo-hidein-print">
						<textarea id="vbo-receipt-notes" placeholder="<?php echo JText::translate('VBORECEIPTNOTESDEF'); ?>"><?php echo htmlspecialchars($receipt_notes); ?></textarea>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		</div>

		<div class="vbo-bookingdet-tab-cont" id="vbo-tab-admin" style="display: none;">
			<div class="vbo-bookingdet-innercontainer">
				<div class="vbo-bookingdet-admindata">
				<?php
				if (!$row['closure']) {
					?>
					<div class="vbo-bookingdet-admin-entry">
						<label for="vbo-searchcust"><?php echo JText::translate(count($customer) ? 'VBOASSIGNNEWCUST' : 'VBFILLCUSTFIELDS'); ?></label>
						<span style="display: block;"><?php echo JText::translate('VBOSEARCHEXISTCUST'); ?></span>
						<span class="vbo-eorder-assigncust" style="margin-bottom: 1px;">
							<input type="text" id="vbo-searchcust" autocomplete="off" value="" placeholder="<?php echo JText::translate('VBOSEARCHCUSTBY'); ?>" size="30" style="margin-bottom: 0;" />
						</span>
						<span id="vbo-searchcust-loading">
							<?php VikBookingIcons::e('hourglass-half'); ?>
						</span>
						<input type="hidden" name="newcustid" id="newcustid" value="" />
						<div id="vbo-searchcust-res" style="position: absolute; background-color: #fff;"></div>
						<span class="vbo-eorder-assignnewcust" style="display: block; margin-top: 10px;">
							<a class="vbo-assign-customer" href="index.php?option=com_vikbooking&task=newcustomer&bid=<?php echo $row['id']; ?>&goto=<?php echo base64_encode($gotouri.'#tab-admin'); ?>">
								<?php VikBookingIcons::e('user-circle'); ?>
								<span><?php echo JText::translate('VBOCREATENEWCUST'); ?></span>
							</a>
						</span>

						<?php	
						if (isset($adminSidebarForms['customer']))
						{
							echo $adminSidebarForms['customer'];
						}
						?>

					</div>
				<?php
				}
				?>
					<div class="vbo-bookingdet-admin-entry">
						<label for="newpayment"><?php echo JText::translate('VBPAYMENTMETHOD'); ?></label>
					<?php
					if (is_array($payment)) {
						?>
						<span><?php echo $payment['name']; ?></span>
						<?php
					}
					$chpayment = '';
					if ($payments) {
						$chpayment = '<div><select name="newpayment" id="newpayment" onchange="changePayment();"><option value="">'.JText::translate('VBCHANGEPAYLABEL').'</option>';
						foreach ($payments as $pay) {
							$chpayment .= '<option value="'.$pay['id'].'">'.(is_array($payment) && $payment['id'] == $pay['id'] ? ' ::' : '').$pay['name'].'</option>';
						}
						$chpayment .= '</select></div>';
					}
					echo $chpayment;
					?>

					<!-- Define role to detect the supported hook -->
					<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderAdministrationSidebar","key":"payment","type":"field"} -->

					<?php	
					if (isset($adminSidebarForms['payment']))
					{
						echo $adminSidebarForms['payment'];
					}
					?>

					</div>
				<?php
				$tn = VikBooking::getTranslator();
				$all_langs = $tn->getLanguagesList();
				if (count($all_langs) > 1) {
				?>
					<div class="vbo-bookingdet-admin-entry">
						<label for="newlang"><?php echo JText::translate('VBOBOOKINGLANG'); ?></label>
						<select name="newlang" id="newlang" onchange="document.adminForm.submit();">
						<?php
						foreach ($all_langs as $lk => $lv) {
							?>
							<option value="<?php echo $lk; ?>"<?php echo $row['lang'] == $lk ? ' selected="selected"' : ''; ?>><?php echo isset($lv['nativeName']) ? $lv['nativeName'] : $lv['name']; ?></option>
							<?php
						}
						?>
						</select>

						<!-- Define role to detect the supported hook -->
						<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderAdministrationSidebar","key":"language","type":"field"} -->

						<?php	
						if (isset($adminSidebarForms['language']))
						{
							echo $adminSidebarForms['language'];
						}
						?>
					</div>
				<?php
				}
				?>

				<!-- Define role to detect the supported hook -->
				<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderAdministrationSidebar","type":"fieldset"} -->

				<?php
				foreach ($adminSidebarForms as $legend => $form)
				{
					if (in_array($legend, ['customer', 'payment', 'language']))
					{
						// skip default forms
						continue;
					}
					?>
					<div class="vbo-bookingdet-admin-entry">
						<h3><?php echo JText::translate($legend); ?></h3>
						
						<?php echo $form; ?>
					</div>
					<?php
				}
				?>

				</div>
				<div class="vbo-bookingdet-noteslogs">
					<?php
					$history = $history_obj->loadHistory();
					?>
					<div class="vbo-bookingdet-noteslogs-btns">
						<div class="vbo-bookingdet-noteslogs-btn vbo-bookingdet-noteslogs-btn-active" data-id="notes">
							<a href="javascript: void(0);" id="vbo-trig-notes" onclick="javascript: vbToggleNotes(this);">
								<?php VikBookingIcons::e('user-lock'); ?>
								<span class="vbo-bookingdet-admintab-lbl"><?php echo JText::translate('VBADMINNOTESTOGGLE'); ?></span>
							</a>
						</div>
					<?php
					/**
					 * Initialize chat instance by getting the proper channel name
					 * 
					 * @since  1.12
					 */
					if (empty($row['channel'])) {
						// front-end reservation chat handler
						$chat_channel = 'vikbooking';
					} else {
						$channelparts = explode('_', $row['channel']);
						// check if this is a meta search channel
						$is_meta_search = false;
						if (preg_match("/(customer).*[0-9]$/", $channelparts[0]) || !strcasecmp($channelparts[0], 'googlehotel') || !strcasecmp($channelparts[0], 'trivago')) {
							$is_meta_search = empty($row['idorderota']);
						}
						if ($is_meta_search) {
							// customer of type sales channel should use front-end reservation chat handler
							$chat_channel = 'vikbooking';
						} else {
							// let the getInstance method validate the channel chat handler
							$chat_channel = $row['channel'];
						}
					}
					$messaging = VikBooking::getVcmChatInstance($row['id'], $chat_channel);
					//
					if (!$printreceipt && !is_null($messaging)) {
						?>
						<div class="vbo-bookingdet-noteslogs-btn" data-id="messaging">
							<a name="messaging" href="javascript: void(0);" id="vbo-trig-messaging" onclick="javascript: vbToggleMessaging(this);">
								<?php VikBookingIcons::e('comment-dots'); ?>
								<span class="vbo-bookingdet-admintab-lbl"><?php echo JText::translate('VBO_GUEST_MESSAGING'); ?></span>
							</a>
						</div>
						<?php
					}

					/**
					 * Build up a tab to summarize the "takings" for this sole booking in case of relevant events.
					 * 
					 * @since 	1.16.9 (J) - 1.6.9 (WP)
					 */
					$takings = [];
					if ($history) {
						// payment related events
						$h_payment_group = $history_obj->getTypeGroups($group = 'GPM');

						// merge default types with 'PU' for the manually set "new amount paid" and with 'RU' (refunded amount updated)
						$takings_events = array_unique(array_merge(($h_payment_group['types'] ?? []), ['PU', 'RU']));

						// load the relevant events with data
						$takings = $history_obj->getEventsWithData($takings_events, null, false);

						if ($takings) {
							?>
							<div class="vbo-bookingdet-noteslogs-btn pro-feature" data-id="takings">
								<a href="javascript: void(0);" id="vbo-trig-bookhistory" onclick="javascript: vbToggleDynamicPanel(this, '#vboextrapanel-takings');">
									<?php VikBookingIcons::e('wallet'); ?>
									<span class="vbo-bookingdet-admintab-lbl"><?php echo JText::translate('VBO_TAKINGS'); ?></span>
								</a>
							</div>
							<?php
						}
					}

					if ($history) {
						?>
						<div class="vbo-bookingdet-noteslogs-btn" data-id="history">
							<a href="javascript: void(0);" id="vbo-trig-bookhistory" onclick="javascript: vbToggleHistory(this);">
								<?php VikBookingIcons::e('history'); ?>
								<span class="vbo-bookingdet-admintab-lbl"><?php echo JText::translate('VBOBOOKHISTORYTAB'); ?></span>
							</a>
						</div>
						<script type="text/javascript">
						if (window.location.hash == '#bookhistory') {
							setTimeout(function() {
								jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
								vbToggleHistory(document.getElementById('vbo-trig-bookhistory'));
							}, 500);
						}
						</script>
						<?php
					}
					?>
						<div class="vbo-bookingdet-noteslogs-btn pro-feature" data-id="invnotes">
							<a href="javascript: void(0);" class="hasTooltip" id="vbo-trig-invnotes" onclick="javascript: vbToggleInvNotes(this);" title="<?php echo addslashes(JText::translate('VBBOOKINGINVNOTESHELP')); ?>">
								<?php VikBookingIcons::e('file-invoice'); ?>
								<span class="vbo-bookingdet-admintab-lbl"><?php echo JText::translate('VBBOOKINGINVNOTES'); ?></span>
							</a>
						</div>

						<!-- Define role to detect the supported hook -->
						<!-- {"rule":"customizer","event":"onDisplayViewVikBookingEditorderAdministration","type":"fieldset"} -->

						<?php
						foreach ($adminPanelForms as $legend => $form)
						{
							$legendId = preg_replace("/[^a-zA-Z0-9_\-]+/", '', $legend);
							?>
						<div class="vbo-bookingdet-noteslogs-btn" data-id="<?php echo $legendId; ?>">
							<a href="javascript: void(0);" class="hasTooltip" id="vbo-trig-<?php echo $legendId; ?>" onclick="javascript: vbToggleDynamicPanel(this, '#vboextrapanel-<?php echo $legendId; ?>');">
								<?php VikBookingIcons::e(!empty($adminFormsSetup->icons[$legend]) ? $adminFormsSetup->icons[$legend] : 'plug'); ?>
								<span class="vbo-bookingdet-admintab-lbl"><?php echo JText::translate($legend); ?></span>
							</a>
						</div>
							<?php
						}

						if (!empty($row['paymentlog'])) {
							$paylogs_lbl = JText::translate('VBPAYMENTLOGTOGGLE');
							if (stripos($row['paymentlog'], 'card number') !== false && strpos($row['paymentlog'], '****') !== false) {
								$paylogs_lbl = JText::translate('VBO_CREDIT_CARD');
							}
							?>
						<div class="vbo-bookingdet-noteslogs-btn" data-id="paylogs">
							<a href="javascript: void(0);" id="vbo-trig-paylogs" onclick="javascript: vbToggleLog(this);">
								<?php VikBookingIcons::e('credit-card'); ?>
								<span class="vbo-bookingdet-admintab-lbl"><?php echo $paylogs_lbl; ?></span>
							</a>
							<a name="paymentlog" href="javascript: void(0);"></a>
						</div>
							<?php
						}
						?>
					</div>
					<div class="vbo-bookingdet-noteslogs-cont">
						<div id="vbadminnotesdiv" class="vbo-extra-panel" style="display: block;">
							<textarea name="adminnotes" class="vbadminnotestarea"><?php echo strip_tags((string)$row['adminnotes']); ?></textarea>
							<input type="submit" name="updadmnotes" value="<?php echo JText::translate('VBADMINNOTESUPD'); ?>" class="btn btn-success" />
						</div>
					<?php
					if ($takings) {
						?>
						<div class="vbo-extra-panel" id="vboextrapanel-takings" style="display: none;">
							<div class="vbo-booking-takings-container table-responsive">
								<table class="table">
									<thead>
										<tr class="vbo-booking-takings-firstrow">
											<td class="vbo-booking-takings-td-totpaid"><?php echo JText::translate('VBO_TAKINGS'); ?></td>
											<td class="vbo-booking-takings-td-date"><?php echo JText::translate('VBOBOOKHISTORYLBLDATE'); ?></td>
											<td class="vbo-booking-takings-td-type"><?php echo JText::translate('VBOBOOKHISTORYLBLTYPE'); ?></td>
											<td class="vbo-booking-takings-td-fees"><?php echo JText::translate('VBO_PROCESSING_FEES'); ?></td>
											<td class="vbo-booking-takings-td-descr"><?php echo JText::translate('VBOBOOKHISTORYLBLDESC'); ?></td>
										</tr>
									</thead>
									<tbody>
									<?php
									$booking_total_taking = 0;
									$booking_total_prfees = 0;
									foreach (array_reverse($takings) as $hist) {
										// calculate amount paid and description
										$tn_amount_paid = 0;
										$tn_amount_fees = 0;
										$tn_description = strpos($hist['descr'], '<') !== false ? $hist['descr'] : preg_replace("/\R/", ' - ', $hist['descr']);
										if (is_object($hist['data'])) {
											// calculate amount paid or amount refunded
											if (($hist['data']->amount_paid ?? 0)) {
												$tn_amount_paid = $hist['data']->amount_paid;
												if (in_array($hist['type'], ['RF', 'RU'])) {
													// turn the amount paid to negative
													$tn_amount_paid = $tn_amount_paid < 0 ? $tn_amount_paid : ($tn_amount_paid - ($tn_amount_paid * 2));
													if ($hist['type'] === 'RU' && $hist['data']->amount_paid < 0) {
														// this indicates a lower amount refunded, so we double it to make it positive
														$tn_amount_paid = abs($hist['data']->amount_paid);
													}
												}
											}

											// calculate processing fees, if set and if any
											if (($hist['data']->processing_fees ?? 0)) {
												$tn_amount_fees += $hist['data']->processing_fees;
											}

											// build the event description
											if (($hist['data']->payment_method ?? '')) {
												$tn_description = ucwords($hist['data']->payment_method);
											} elseif (($hist['data']->driver ?? '')) {
												$tn_description = ucwords(str_replace('_', ' ', basename($hist['data']->driver, '.php')));
											}
										}

										// increase (or decrease) booking total takings and processing fees amounts
										$booking_total_taking += $tn_amount_paid;
										$booking_total_prfees += $tn_amount_fees;
										?>
										<tr class="vbo-booking-takings-row">
											<td><?php echo $currencysymb . ' ' . VikBooking::numberFormat($tn_amount_paid); ?></td>
											<td><?php echo JHtml::fetch('date', $hist['dt'], 'Y-m-d H:i:s'); ?></td>
											<td><?php echo $history_obj->validType($hist['type'], true); ?></td>
											<td><?php echo $currencysymb . ' ' . VikBooking::numberFormat($tn_amount_fees); ?></td>
											<td><?php echo $tn_description; ?></td>
										</tr>
										<?php
									}
									?>
									</tbody>
									<tfoot>
										<tr>
											<td colspan="2">
												<span><?php echo $currencysymb . ' ' . VikBooking::numberFormat($booking_total_taking); ?></span>
											</td>
											<td></td>
											<td colspan="2">
												<span><?php echo $currencysymb . ' ' . VikBooking::numberFormat($booking_total_prfees); ?></span>
											</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
						<?php
					}
					if ($history) {
						?>
						<div id="vbhistorydiv" class="vbo-extra-panel" style="display: none;">
							<div class="vbo-booking-history-container table-responsive">
								<table class="table">
									<thead>
										<tr class="vbo-booking-history-firstrow">
											<td class="vbo-booking-history-td-type"><?php echo JText::translate('VBOBOOKHISTORYLBLTYPE'); ?></td>
											<td class="vbo-booking-history-td-date"><?php echo JText::translate('VBOBOOKHISTORYLBLDATE'); ?></td>
											<td class="vbo-booking-history-td-descr"><?php echo JText::translate('VBOBOOKHISTORYLBLDESC'); ?></td>
											<td class="vbo-booking-history-td-totpaid"><?php echo JText::translate('VBOBOOKHISTORYLBLTPAID'); ?></td>
											<td class="vbo-booking-history-td-tot"><?php echo JText::translate('VBOBOOKHISTORYLBLTOT'); ?></td>
										</tr>
									</thead>
									<tbody>
									<?php
									foreach ($history as $hist) {
										$hdescr = strpos($hist['descr'], '<') !== false ? $hist['descr'] : nl2br($hist['descr']);
										?>
										<tr class="vbo-booking-history-row">
											<td><?php echo $history_obj->validType($hist['type'], true); ?></td>
											<td><?php echo JHtml::fetch('date', $hist['dt'], 'Y-m-d H:i:s'); ?></td>
											<td><?php echo $hdescr; ?></td>
											<td><?php echo $currencyname.' '.VikBooking::numberFormat($hist['totpaid']); ?></td>
											<td><?php echo $currencyname.' '.VikBooking::numberFormat($hist['total']); ?></td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							</div>
						</div>
						<?php
					}
					?>
						<div id="vbinvnotesdiv" class="vbo-extra-panel" style="display: none;">
							<textarea name="invnotes" id="invnotes" class="vbadminnotestarea"><?php echo $row['inv_notes']; ?></textarea>
							<input type="submit" name="updinvnotes" value="<?php echo JText::translate('VBADMINNOTESUPD'); ?>" class="btn btn-success" />
							<button type="button" class="btn vbo-config-btn btn-secondary pull-right" onclick="document.getElementById('invnotes-hid').value=document.getElementById('invnotes').value;document.getElementById('vbo-gen-invoice').submit();"><?php VikBookingIcons::e('file-invoice'); ?> <?php echo JText::translate('VBOGENBOOKINGINVOICE'); ?></button>
						</div>
					<?php
					if (!empty($row['paymentlog'])) {
						?>
						<div id="vbpaymentlogdiv" class="vbo-extra-panel" style="display: none;">
							<div class="vbo-eorder-paymentlog-buttons">
						<?php
						// PCI Data Retrieval
						$cc_acl_denied = false;
						if (!empty($row['idorderota']) && !empty($row['channel'])) {
							$channel_source = $row['channel'];
							if (strpos($row['channel'], '_') !== false) {
								$channelparts = explode('_', $row['channel']);
								$channel_source = $channelparts[0];
							}
							$checkout_info = getdate($row['checkout']);
							
							// validate ACL for credit card details
							if (!$canDo->authorise('core.admin', 'com_vikchannelmanager')) {
								// permissions to access VCM are required
								$cc_acl_denied = true;
								?>
								<p class="err"><?php echo JText::translate('JERROR_ALERTNOAUTHOR'); ?></p>
								<?php
							} else {
								/**
								 * Limit for accessing the credit card details has been changed to check-out
								 * day at 23:59:59 + 10 extra days. It used to be at 23:59:59 on check-out day.
								 * 
								 * @since 	1.13
								 */
								$cardlimit = mktime(23, 59, 59, $checkout_info['mon'], ($checkout_info['mday'] + 10), $checkout_info['year']);
								if (time() < $cardlimit) {
									$plain_log = htmlspecialchars($row['paymentlog']);
									if (stripos($plain_log, 'card number') !== false && strpos($plain_log, '****') !== false) {
										// log contains credit card details
										// Prepare modal (Credit Card Details)
										array_push($vbo_modals_html, $vbo_app->getJmodalHtml('vbo-vcm-pcid', JText::translate('GETFULLCARDDETAILS'), '', 'width: 80%; height: 60%; margin-left: -40%; top: 20% !important;'));
										// end Prepare modal
									?>
								<div class="vcm-notif-pcidrq-container">
									<button type="button" class="btn vbo-config-btn vcm-pcid-launch" onclick="vboOpenJModal('vbo-vcm-pcid', 'index.php?option=com_vikchannelmanager&task=execpcid&channel_source=<?php echo $channel_source; ?>&otaid=<?php echo $row['idorderota']; ?>&tmpl=component');"><?php VikBookingIcons::e('credit-card'); ?> <?php echo JText::translate('GETFULLCARDDETAILS'); ?></button>
								</div>
									<?php
									}
								}
							}
						} elseif (empty($row['idorderota']) && !empty($row['paymentlog']) && $row['status'] == 'standby') {
							?>
								<div class="vbo-eorder-emptyccdata">
									<a class="btn btn-warning" href="index.php?option=com_vikbooking&task=editorder&cid[]=<?php echo $row['id']; ?>&unset_cc=1" onclick="return confirm(Joomla.JText._('VBDELCONFIRM'));"><?php VikBookingIcons::e('broom'); ?> <?php echo JText::translate('VBO_EMPTY_DATA'); ?></a>
								</div>
							<?php
						}

						/**
						 * Attempt to display the reporting invalid credit card layout from VCM.
						 * 
						 * @requires  VCM >= 1.8.27
						 * 
						 * @since 	  1.16.9 (J) - 1.6.9 (WP)
						 */
						try {
							// prepare the layout data array
							$layout_data = [
								'caller'  => 'vikbooking',
								'booking' => $row,
							];

							?>
								<div class="vbo-eorder-vcm-reporting-invalidcc">
							<?php
							// render the reporting-invalidcc layout
							echo JLayoutHelper::render('reporting.invalidcc', $layout_data, null, [
								'component' => 'com_vikchannelmanager',
								'client' 	=> 'administrator',
							]);
							?>
								</div>
							<?php
						} catch(Throwable $e) {
							// do nothing
						}

						?>
							</div>
						<?php

						if (!$cc_acl_denied) {
							?>
							<pre><?php echo htmlspecialchars($row['paymentlog']); ?></pre>
							<?php
						}
						?>
						</div>

						<script type="text/javascript">
						if (window.location.hash == '#paymentlog') {
							setTimeout(function() {
								jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
								vbToggleLog(document.getElementById('vbo-trig-paylogs'));
							}, 500);
						}
						</script>
						<?php
					}
					if (!$printreceipt && !is_null($messaging)) {
						?>
						<div id="vbmessagingdiv" class="vbo-extra-panel" style="display: none;">
							<?php echo $messaging->renderChat(); ?>
						</div>
						<script type="text/javascript">
						jQuery(function() {
							if (window.location.hash == '#messaging') {
								setTimeout(function() {
									jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
									vbToggleMessaging(document.getElementById('vbo-trig-messaging'));
								}, 100);
							}
						});
						</script>
						<?php
					}
					
					foreach ($adminPanelForms as $legend => $form)
					{
						$legendId = preg_replace("/[^a-zA-Z0-9_\-]+/", '', $legend);
						?>
						<div class="vbo-extra-panel" id="vboextrapanel-<?php echo $legendId; ?>" style="display: none;">
							<?php echo $form; ?>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</div>
		</div>

		<input type="hidden" name="task" value="editorder">
		<input type="hidden" name="vbo_active_tab" id="vbo_active_tab" value="">
		<input type="hidden" name="whereup" value="<?php echo $row['id']; ?>">
		<input type="hidden" name="cid[]" value="<?php echo $row['id']; ?>">
		<input type="hidden" name="option" value="com_vikbooking">
		<?php
		$tmpl = VikRequest::getVar('tmpl');
		if ($tmpl == 'component') {
			echo '<input type="hidden" name="tmpl" value="component" />';
		}
		$pgoto = VikRequest::getString('goto', '', 'request');
		if (in_array($pgoto, ['overv', 'ratesoverv'])) {
			echo '<input type="hidden" name="goto" value="' . $pgoto . '" />';
		}
		?>
	</form>
</div>
<?php
foreach ($vbo_modals_html as $modalhtml) {
	echo $modalhtml;
}
?>
<form action="index.php?option=com_vikbooking&amp;task=orders" method="post" id="vbo-gen-invoice">
	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="orders" />
	<input type="hidden" name="cid[]" value="<?php echo $row['id']; ?>" />
	<input type="hidden" name="invnotes" id="invnotes-hid" value="" />
</form>

<div class="vbo-modal-overlay-block vbo-modal-overlay-block-sms-email">
	<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-modal-overlay-content vbo-modal-overlay-content-large vbo-modal-overlay-content-sms-email">

		<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-sms-email-head">
			<h3>
				<span></span>
				<span class="vbo-modal-overlay-close-times" onclick="vboToggleSendEmail();">&times;</span>
			</h3>
		</div>

		<div class="vbo-modal-overlay-content-body vbo-modal-overlay-content-body-scroll">

			<div id="vbo-overlay-sms-cont" style="display: none;">
				<h4 style="display: none;"><?php echo JText::translate('VBSENDSMSACTION'); ?>: <span id="smstophone-lbl"><?php echo $row['phone']; ?></span></h4>
				<form action="index.php?option=com_vikbooking" method="post" id="vbo-modal-form-sms">
					<div class="vbo-calendar-cfield-entry">
						<label for="smscont"><?php echo JText::translate('VBSENDSMSCUSTCONT'); ?></label>
						<span><textarea name="smscont" id="smscont" style="width: 99%; min-width: 99%;max-width: 99%; height: 120px;"></textarea></span>
					</div>
					<input type="hidden" name="phone" id="smstophone" value="<?php echo $row['phone']; ?>" />
					<input type="hidden" name="goto" value="<?php echo urlencode('index.php?option=com_vikbooking&task=editorder&cid[]='.$row['id']); ?>" />
					<input type="hidden" name="task" value="sendcustomsms" />
				</form>
			</div>

			<div id="vbo-overlay-email-cont" style="display: none;">
				<h4 style="display: none;"><?php echo JText::translate('VBSENDEMAILACTION'); ?>: <span id="emailto-lbl"><?php echo $row['custmail']; ?></span></h4>
				<form action="index.php?option=com_vikbooking" method="post" enctype="multipart/form-data" id="vbo-modal-form-email">
					<input type="hidden" name="bid" value="<?php echo $row['id']; ?>" />
				<?php
				$q = "SELECT `setting` FROM `#__vikbooking_config` WHERE `param`='customemailtpls';";
				$dbo->setQuery($q);
				$cur_emtpl = $dbo->loadResult();

				$cur_emtpl = empty($cur_emtpl) ? array() : json_decode($cur_emtpl, true);
				$cur_emtpl = is_array($cur_emtpl) ? $cur_emtpl : array();

				if ($cur_emtpl) {
					?>
					<div class="vbo-calendar-custmail-tpls-wrap">
						<select id="emtpl-customemail" onchange="vboLoadEmailTpl(this.value);">
							<option value=""><?php echo JText::translate('VBEMAILCUSTFROMTPL'); ?></option>
						<?php
						foreach ($cur_emtpl as $emk => $emv) {
							?>
							<optgroup label="<?php echo $emv['emailsubj']; ?>">
								<option value="<?php echo $emk; ?>"><?php echo JText::translate('VBEMAILCUSTFROMTPLUSE'); ?></option>
								<option value="rm<?php echo $emk; ?>"><?php echo JText::translate('VBEMAILCUSTFROMTPLRM'); ?></option>
							</optgroup>
							<?php
						}
						?>
						</select>
					</div>
					<?php
				}

				/**
				 * Load all conditional text special tags.
				 * 
				 * @since 	1.14 (J) - 1.4.0 (WP)
				 */
				$extra_btns = array();
				$condtext_tags = VikBooking::getConditionalRulesInstance()->getSpecialTags();
				if (count($condtext_tags)) {
					$condtext_tags = array_keys($condtext_tags);
					foreach ($condtext_tags as $tag) {
						array_push($extra_btns, '<button type="button" class="btn btn-secondary btn-small vbo-condtext-specialtag-btn" onclick="setSpecialTplTag(\'emailcont\', \'' . $tag . '\');">' . $tag . '</button>');
					}
				}
				//
				?>
					<div class="vbo-calendar-cfields-wrap">
						<div class="vbo-calendar-cfield-entry">
							<label for="emailsubj"><?php echo JText::translate('VBSENDEMAILCUSTSUBJ'); ?></label>
							<span><input type="text" name="emailsubj" id="emailsubj" value="" size="30" /></span>
						</div>
						<div class="vbo-calendar-cfield-entry">
							<label for="emailcont"><?php echo JText::translate('VBSENDEMAILCUSTCONT'); ?></label>
							<?php
							$special_tags_base = array(
								'{customer_name}',
								'{booking_id}',
								'{checkin_date}',
								'{checkout_date}',
								'{num_nights}',
								'{rooms_booked}',
								'{rooms_names}',
								'{tot_adults}',
								'{tot_children}',
								'{tot_guests}',
								'{total}',
								'{total_paid}',
								'{remaining_balance}',
								'{booking_link}',
							);

							$special_tags_base_html = '';
							foreach ($special_tags_base as $sp_tag) {
								$special_tags_base_html .= '<button type="button" class="btn btn-secondary btn-small" onclick="setSpecialTplTag(\'emailcont\', \'' . $sp_tag . '\');">' . $sp_tag . '</button>' . "\n";
							}

							/**
							 * Use the rich text editor (visual editor) to build custom email messages.
							 * 
							 * @since 	1.15.0 (J) - 1.5.0 (WP)
							 */
							$tarea_attr = array(
								'id' => 'emailcont',
								'rows' => '7',
								'cols' => '170',
								'style' => 'width: 99%; min-width: 99%; max-width: 99%; height: 120px; margin-bottom: 1px;',
							);
							$editor_opts = array(
								'modes' => array(
									'text',
									'visual',
								),
							);
							$editor_btns = $special_tags_base;
							if (count($condtext_tags)) {
								$editor_btns = array_merge($editor_btns, $condtext_tags);
							}
							echo $vbo_app->renderVisualEditor('emailcont', '', $tarea_attr, $editor_opts, $editor_btns);
							?>
							<div class="btn-group pull-left vbo-smstpl-bgroup vbo-custmail-bgroup vik-contentbuilder-textmode-sptags">
								<?php echo $special_tags_base_html . "\n" . implode("\n", $extra_btns); ?>
							</div>
						</div>
						<div class="vbo-calendar-cfield-entry">
							<label for="emailattch"><?php echo JText::translate('VBSENDEMAILCUSTATTCH'); ?></label>
							<span><input type="file" name="emailattch" id="emailattch" /></span>
						</div>
						<div class="vbo-calendar-cfield-entry">
							<label for="emailfrom"><?php echo JText::translate('VBSENDEMAILCUSTFROM'); ?></label>
							<span><input type="text" name="emailfrom" id="emailfrom" value="<?php echo VikBooking::getSenderMail(); ?>" size="30" /></span>
						</div>
					</div>
					<input type="hidden" name="email" id="emailto" value="<?php echo $row['custmail']; ?>" />
					<input type="hidden" name="goto" value="<?php echo urlencode('index.php?option=com_vikbooking&task=editorder&cid[]='.$row['id']); ?>" />
					<input type="hidden" name="task" value="sendcustomemail" />
				</form>
			</div>

		</div>

		<div class="vbo-modal-overlay-content-footer">
			<div class="vbo-modal-overlay-content-footer-right">
				<div id="vbo-modal-footer-sms" style="display: none;">
					<button type="submit" class="btn vbo-config-btn" onclick="document.getElementById('vbo-modal-form-sms').submit();"><?php VikBookingIcons::e('comment'); ?> <?php echo JText::translate('VBSENDSMSACTION'); ?></button>
				</div>
				<div id="vbo-modal-footer-email" style="display: none;">
					<button type="submit" class="btn vbo-config-btn" onclick="document.getElementById('vbo-modal-form-email').submit();"><?php VikBookingIcons::e('envelope'); ?> <?php echo JText::translate('VBSENDEMAILACTION'); ?></button>
				</div>
			</div>
		</div>

	</div>
</div>

<script type="text/javascript">
var vbo_overlay_on = false;
var vbo_print_only = false;
if (jQuery.isFunction(jQuery.fn.tooltip)) {
	jQuery(".hasTooltip").tooltip();
}
function vboToggleSendSMS() {
	var cur_phone = jQuery("#smstophone").val();
	var phone_set = jQuery("#custphone").trigger('vboupdatephonenumber').val();
	if (phone_set.length && phone_set != cur_phone) {
		jQuery("#smstophone").val(phone_set);
		jQuery("#smstophone-lbl").text(phone_set);
	}

	// toggle email/sms contents
	jQuery("#vbo-overlay-email-cont, #vbo-modal-footer-email").hide();
	jQuery("#vbo-overlay-sms-cont, #vbo-modal-footer-sms").show();
	var use_modal_title = jQuery("#vbo-overlay-sms-cont").find('h4').first().html();
	jQuery('.vbo-modal-overlay-content-sms-email-head').find('h3').find('span').first().html(use_modal_title);

	jQuery(".vbo-modal-overlay-block-sms-email").fadeToggle(400, function() {
		if (jQuery(".vbo-modal-overlay-block-sms-email").is(":visible")) {
			vbo_overlay_on = true;
		} else {
			vbo_overlay_on = false;
		}
	});
}
function vboToggleSendEmail() {
	var cur_email = jQuery("#emailto").val();
	var email_set = jQuery("#custmail").val();
	if (email_set.length && email_set != cur_email) {
		jQuery("#emailto").val(email_set);
		jQuery("#emailto-lbl").text(email_set);
	}

	// toggle email/sms contents
	jQuery("#vbo-overlay-sms-cont, #vbo-modal-footer-sms").hide();
	jQuery("#vbo-overlay-email-cont, #vbo-modal-footer-email").show();
	var use_modal_title = jQuery("#vbo-overlay-email-cont").find('h4').first().html();
	jQuery('.vbo-modal-overlay-content-sms-email-head').find('h3').find('span').first().html(use_modal_title);

	jQuery(".vbo-modal-overlay-block-sms-email").fadeToggle(400, function() {
		if (jQuery(".vbo-modal-overlay-block-sms-email").is(":visible")) {
			vbo_overlay_on = true;
		} else {
			vbo_overlay_on = false;
		}
	});
}
function vboKeyupEmail(event) {
	if (event.key && event.key == 'Enter') {
		event.preventDefault();
		document.adminForm.submit();
		return;
	}
	jQuery('.vbo-bookingdet-save-email').show();
}
function setSpecialTplTag(taid, tpltag) {
	var tplobj = document.getElementById(taid);
	if (tplobj != null) {
		var start = tplobj.selectionStart;
		var end = tplobj.selectionEnd;
		tplobj.value = tplobj.value.substring(0, start) + tpltag + tplobj.value.substring(end);
		tplobj.selectionStart = tplobj.selectionEnd = start + tpltag.length;
		tplobj.focus();
	}
}
jQuery(function() {
	// register to the event emitted when a new payment is made through the virtual terminal widget
	document.addEventListener('vbo_new_payment_transaction', (e) => {
		if (!e || !e.detail || !e.detail.hasOwnProperty('tn') || !e.detail['tn']) {
			// do nothing
			return;
		}
		// reload the page to display the updated information
		location.reload();
	});
	// register to the event emitted when a booking is modified through an admin widget
	document.addEventListener('vbo_booking_modified', (e) => {
		if (!e || !e.detail || !e.detail.hasOwnProperty('bid') || !e.detail['bid']) {
			// do nothing
			return;
		}
		// reload the page to display the new information about the booking just modified
		location.reload();
	});
	// remove overbooking flag
	jQuery('.vbo-label-overbooking').on('click', function() {
		if (confirm(Joomla.JText._('VBO_CONF_RM_OVERBOOKING_FLAG'))) {
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=bookings.delete_type_flag'); ?>",
				{
					bid: '<?php echo $row['id']; ?>',
					flag: 'overbooking',
				},
				(success) => {
					// remove "overbooking" label
					let parent_label = jQuery('.vbo-bookdet-container').find('.vbo-label-overbooking').parent('.label');
					jQuery('.vbo-bookdet-container').find('.vbo-label-overbooking').remove();
					parent_label.text(parent_label.text().replace(' / ', ''));
				},
				(error) => {
					alert(error.responseText);
				}
			);
		}
	});
	// sessionStorage for current tab
	if (typeof sessionStorage !== 'undefined' && !window.location.hash && <?php echo $printreceipt ? 'false' : 'true'; ?>) {
		var curtab = sessionStorage.getItem('vboEditOrderTab<?php echo $row['id']; ?>');

		setTimeout(() => {
			let link = jQuery('.vbo-bookingdet-noteslogs-btn[data-id="' + curtab + '"] a');

			if (link.length) {
				jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
				link.trigger('click');
			}
		}, 128);
	}
	if (window.location.hash == '#tab-admin') {
		setTimeout(function() {
			jQuery(".vbo-bookingdet-tab[data-vbotab='vbo-tab-admin']").trigger('click');
		}, 100);
	}
	// Search customer - Start
	var vbocustsdelay = (function() {
		var timer = 0;
		return function(callback, ms) {
			clearTimeout(timer);
			timer = setTimeout(callback, ms);
		};
	})();
	function vboCustomerSearch(words) {
		jQuery("#vbo-searchcust-res").hide().html("");
		jQuery("#vbo-searchcust-loading").show();
		var jqxhr = jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=searchcustomer'); ?>",
			data: {
				kw: words,
				tmpl: "component"
			}
		}).done(function(cont) {
			if (cont) {
				var obj_res = typeof cont === 'string' ? JSON.parse(cont) : cont;
				customers_search_vals = obj_res[0];
				jQuery("#vbo-searchcust-res").html(obj_res[1]);
			} else {
				customers_search_vals = "";
				jQuery("#vbo-searchcust-res").html("----");
			}
			jQuery("#vbo-searchcust-res").show();
			jQuery("#vbo-searchcust-loading").hide();
		}).fail(function() {
			jQuery("#vbo-searchcust-loading").hide();
			alert("Error Searching.");
		});
	}
	jQuery("#vbo-searchcust").keyup(function(event) {
		vbocustsdelay(function() {
			var keywords = jQuery("#vbo-searchcust").val();
			var chars = keywords.length;
			if (chars > 1) {
				if ((event.which > 96 && event.which < 123) || (event.which > 64 && event.which < 91) || event.which == 13) {
					vboCustomerSearch(keywords);
				}
			} else {
				if (jQuery("#vbo-searchcust-res").is(":visible")) {
					jQuery("#vbo-searchcust-res").hide();
				}
			}
		}, 600);
	});
	jQuery("body").on("click", ".vbo-custsearchres-entry", function() {
		var custid = jQuery(this).attr("data-custid");
		if (confirm('<?php echo addslashes(JText::translate('VBOASSIGNNEWCUSTCONF')); ?>')) {
			jQuery('#newcustid').val(custid);
			document.adminForm.submit();
			return;
		}
	});
	// Search customer - End
	jQuery(document).mouseup(function(e) {
		if (!vbo_overlay_on) {
			return false;
		}
		var vbo_overlay_cont = jQuery(".vbo-modal-overlay-content-sms-email");
		if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0 && !jQuery(e.target).is('svg')) {
			jQuery(".vbo-modal-overlay-block-sms-email").fadeOut();
			vbo_overlay_on = false;
		}
	});
	jQuery(document).keyup(function(e) {
		if (e.keyCode == 27 && vbo_overlay_on) {
			jQuery(".vbo-modal-overlay-block-sms-email").fadeOut();
			vbo_overlay_on = false;
		}
	});
	jQuery(".vbo-bookingdet-tab").click(function() {
		var newtabrel = jQuery(this).attr('data-vbotab');
		var oldtabrel = jQuery(".vbo-bookingdet-tab-active").attr('data-vbotab');
		if (newtabrel == oldtabrel) {
			return;
		}
		if (newtabrel == 'vbo-tab-details' && typeof sessionStorage !== 'undefined') {
			sessionStorage.setItem('vboEditOrderTab<?php echo $row['id']; ?>', 'details');
		}
		jQuery(".vbo-bookingdet-tab").removeClass("vbo-bookingdet-tab-active");
		jQuery(this).addClass("vbo-bookingdet-tab-active");
		jQuery("#"+oldtabrel).hide();
		jQuery("#"+newtabrel).fadeIn();
		jQuery("#vbo_active_tab").val(newtabrel);
	});
	jQuery(".vbo-bookingdet-tab[data-vbotab='<?php echo $pactive_tab; ?>']").trigger('click');
	// edit amount paid
	jQuery('#vbo-amountpaid-edit').click(function() {
		jQuery('#vbo-amountpaid-cont').hide();
		jQuery('#vbo-amountpaid-modcont').show();
		jQuery('input[name="newamountpaid"]').prop('disabled', false);
		if (jQuery('input[name="newamountrefunded"]').length) {
			jQuery('input[name="newamountrefunded"]').val('');
		}
	});
	jQuery('#vbo-amountpaid-cancedit').click(function() {
		jQuery('#vbo-amountpaid-modcont').hide();
		jQuery('#vbo-amountpaid-cont').show();
	});
	// edit amount refunded
	jQuery('#vbo-amountrefunded-edit').click(function() {
		jQuery('#vbo-amountrefunded-cont').hide();
		jQuery('#vbo-amountrefunded-modcont').show();
		jQuery('input[name="newamountrefunded"]').prop('disabled', false);
		if (jQuery('input[name="newamountpaid"]').length) {
			jQuery('input[name="newamountpaid"]').val('');
		}
	});
	jQuery('#vbo-amountrefunded-cancedit').click(function() {
		jQuery('#vbo-amountrefunded-modcont').hide();
		jQuery('#vbo-amountrefunded-cont').show();
	});
	// edit amount payable
	jQuery('#vbo-amountpayable-edit').click(function() {
		jQuery('#vbo-amountpayable-cont').hide();
		jQuery('#vbo-amountpayable-modcont').show();
		jQuery('input[name="newamountpayable"]').prop('disabled', false);
	});
	jQuery('#vbo-amountpayable-cancedit').click(function() {
		jQuery('#vbo-amountpayable-modcont').hide();
		jQuery('#vbo-amountpayable-cont').show();
	});
	// edit cancellation fee
	jQuery('#vbo-cancfee-edit').click(function() {
		jQuery('#vbo-cancfee-cont').hide();
		jQuery('#vbo-cancfee-modcont').show();
		jQuery('input[name="newcancfee"]').prop('disabled', false);
	});
	jQuery('#vbo-cancfee-cancedit').click(function() {
		jQuery('#vbo-cancfee-modcont').hide();
		jQuery('#vbo-cancfee-cont').show();
	});
	// chat listener
	if (typeof VCMChat !== 'undefined') {
		jQuery(window).on('chatsync', function(e) {
			if (!jQuery("#vcm-chat-audio-notification").length) {
				jQuery("body").append("<audio id=\"vcm-audio-notification\" preload=\"auto\"><source type=\"audio/mp3\" src=\"<?php echo VCM_ADMIN_URI; ?>assets/css/audio/new_chat_message.mp3\"></source></audio>");
			}
			try {
				var promise = document.getElementById('vcm-audio-notification').play();
				console.log(promise);
			} catch (err) {
				console.warn('Could not play sound', err);
			}
		});
	}

	// zoom-able avatars
	jQuery('.vbo-customer-info-box-avatar').each(function() {
		var img = jQuery(this).find('img');
		if (!img.length) {
			return;
		}
		// register click listener
		img.on('click', function(e) {
			// stop events propagation
			e.preventDefault();
			e.stopPropagation();

			// check for caption
			var caption = jQuery(this).attr('data-caption');

			// build modal content
			var zoom_modal = jQuery('<div></div>').addClass('vbo-modal-overlay-block vbo-modal-overlay-zoom-image').css('display', 'block');
			var zoom_dismiss = jQuery('<a></a>').addClass('vbo-modal-overlay-close');
			zoom_dismiss.on('click', function() {
				jQuery('.vbo-modal-overlay-zoom-image').fadeOut();
			});
			zoom_modal.append(zoom_dismiss);
			var zoom_content = jQuery('<div></div>').addClass('vbo-modal-overlay-content vbo-modal-overlay-content-zoom-image');
			var zoom_head = jQuery('<div></div>').addClass('vbo-modal-overlay-content-head');
			var zoom_head_title = jQuery('<span></span>');
			if (caption) {
				zoom_head_title.text(caption);
			}
			var zoom_head_close = jQuery('<span></span>').addClass('vbo-modal-overlay-close-times').html('&times;');
			zoom_head_close.on('click', function() {
				jQuery('.vbo-modal-overlay-zoom-image').fadeOut();
			});
			zoom_head.append(zoom_head_title).append(zoom_head_close);
			var zoom_body = jQuery('<div></div>').addClass('vbo-modal-overlay-content-body vbo-modal-overlay-content-body-scroll');
			var zoom_image = jQuery('<div></div>').addClass('vbo-modal-zoom-image-wrap');
			zoom_image.append(jQuery(this).clone());
			zoom_body.append(zoom_image);
			zoom_content.append(zoom_head).append(zoom_body);
			zoom_modal.append(zoom_content);
			// append modal to body
			if (jQuery('.vbo-modal-overlay-zoom-image').length) {
				jQuery('.vbo-modal-overlay-zoom-image').remove();
			}
			jQuery('body').append(zoom_modal);
		});
	});

	// emit the event to read all notifications in the current context
	VBOCore.emitEvent('vbo-nc-read-notifications', {
		criteria: {
			group:   '<?php echo !empty($row['idorderota']) && !empty($row['channel']) ? 'otas' : 'website'; ?>',
			idorder: '<?php echo $row['id']; ?>',
		}
	});

});
var cur_emtpl = <?php echo json_encode($cur_emtpl); ?>;
function vboLoadEmailTpl(tplind) {
	if (!(tplind.length > 0)) {
		jQuery('#emailsubj').val('');
		jQuery('#emailcont').val('').trigger('change');
		return true;
	}
	if (tplind.substr(0, 2) == 'rm') {
		if (confirm(Joomla.JText._('VBDELCONFIRM'))) {
			document.location.href = 'index.php?option=com_vikbooking&task=rmcustomemailtpl&cid[]=<?php echo $row['id']; ?>&tplind='+tplind.substr(2);
		}
		return false;
	}
	if (!cur_emtpl.hasOwnProperty(tplind)) {
		jQuery('#emailsubj').val('');
		jQuery('#emailcont').val('').trigger('change');
		return true;
	}
	jQuery('#emailsubj').val(cur_emtpl[tplind]['emailsubj']);
	jQuery('#emailcont').val(cur_emtpl[tplind]['emailcont']).trigger('change');
	jQuery('#emailfrom').val(cur_emtpl[tplind]['emailfrom']);
	return true;
}
<?php
$pcustomemail = VikRequest::getInt('customemail', '', 'request');
if ($pcustomemail > 0) {
	?>
	vboToggleSendEmail();
	<?php
}
if ($printreceipt) {
	?>
jQuery(function() {
	jQuery('button, .vbo-bookingdet-innertop').hide();
	jQuery('body').find('a').each(function(k, v) {
		jQuery(this).replaceWith(jQuery(this).html());
	});
	jQuery('body').find("input[type='text']").each(function(k, v) {
		jQuery(this).replaceWith(jQuery(this).val());
	});
});
function vboMakePrintOnly() {
	vbo_print_only = true;
	jQuery(".vbo-receipt-numdate-block").remove();
	jQuery("#vbo-receipt-print-btn-name").text("<?php echo addslashes(JText::translate('VBOPRINT')); ?>");
}
function vboLaunchPrintReceipt() {
	var rcnotes = jQuery('#vbo-receipt-notes').val();
	var rnewnum = jQuery('#vbo-receipt-num-inp').val();
	if (rcnotes.length) {
		if (rcnotes.indexOf('<') < 0) {
			rcnotes = rcnotes.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br />$2');
		}
		jQuery('#vbo-receipt-notes-val').html(rcnotes);
	} else {
		jQuery('.vbo-receipt-notes-container').remove();
	}
	if (vbo_print_only === true) {
		window.print();
		return;
	}
	jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=updatereceiptnum'); ?>",
		data: {
			newnum: rnewnum,
			newnotes: rcnotes,
			oid: "<?php echo $row['id']; ?>"
		}
	}).done(function(res) {
		window.print();
	}).fail(function() {
		alert('Could not update the next receipt number.')
		window.print();
	});
}
	<?php
}
?>
</script>
