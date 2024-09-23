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

$current_smsapi = VikBooking::getSMSAPIClass();

$allf = glob(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'smsapi' . DIRECTORY_SEPARATOR . '*.php');
$allf = is_array($allf) ? $allf : [];

$psel = "<select name=\"smsapi\" id=\"smsapifile\" onchange=\"vikLoadSMSParameters(this.value);\">\n<option value=\"\"></option>\n";
$classfiles = [];
foreach ($allf as $af) {
	$classfiles[] = str_replace(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'smsapi' . DIRECTORY_SEPARATOR, '', $af);
}
sort($classfiles);

foreach ($classfiles as $cf) {
	$psel .= "<option value=\"".$cf."\"".($cf == $current_smsapi ? ' selected="selected"' : '').">".$cf."</option>\n";
}
$psel .= "</select>";

$sendsmsto = VikBooking::getSendSMSTo();
$sendsmswhen = VikBooking::getSendSMSWhen();

?>
<div class="vbo-config-maintab-left">
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMSMS'); ?></legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSCLASS'); ?></div>
					<div class="vbo-param-setting"><?php echo $psel; ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSAUTOSEND'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('smsautosend', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::autoSendSMSEnabled() ? 1 : 0), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSSENDTO'); ?></div>
					<div class="vbo-param-setting">
						<span class="vbo-spblock-inline"><input type="checkbox" name="smssendto[]" value="admin" id="smssendtoadmin"<?php echo in_array('admin', $sendsmsto) ? ' checked="checked"' : ''; ?> /> <label for="smssendtoadmin"><?php echo JText::translate('VBCONFIGSMSSENDTOADMIN'); ?></label></span>
						<span class="vbo-spblock-inline"><input type="checkbox" name="smssendto[]" value="customer" id="smssendtocustomer"<?php echo in_array('customer', $sendsmsto) ? ' checked="checked"' : ''; ?> /> <label for="smssendtocustomer"><?php echo JText::translate('VBCONFIGSMSSENDTOCUSTOMER'); ?></label></span>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSSENDWHEN'); ?></div>
					<div class="vbo-param-setting">
						<select name="smssendwhen" onchange="displaySMSTexts(this.value);">
							<option value="1"<?php echo $sendsmswhen <= 1 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSMSSENDWHENCONF'); ?></option>
							<option value="2"<?php echo $sendsmswhen >= 2 ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBCONFIGSMSSENDWHENCONFPEND'); ?></option>
						</select>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSSADMINPHONE'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="smsadminphone" size="20" value="<?php echo JHtml::fetch('esc_attr', VikBooking::getSMSAdminPhone()); ?>" /></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSADMTPL'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-toolbar vbo-smstpl-toolbar">
							<div class="btn-group pull-left vbo-smstpl-bgroup">
								<button onclick="setSmsTplTag('smsadmintpl', '{customer_name}');" class="btn" type="button">{customer_name}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{booking_id}');" class="btn" type="button">{booking_id}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{checkin_date}');" class="btn" type="button">{checkin_date}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{checkout_date}');" class="btn" type="button">{checkout_date}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{num_nights}');" class="btn" type="button">{num_nights}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{rooms_booked}');" class="btn" type="button">{rooms_booked}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{rooms_names}');" class="btn" type="button">{rooms_names}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{customer_country}');" class="btn" type="button">{customer_country}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{customer_email}');" class="btn" type="button">{customer_email}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{customer_phone}');" class="btn" type="button">{customer_phone}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{tot_adults}');" class="btn" type="button">{tot_adults}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{tot_children}');" class="btn" type="button">{tot_children}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{tot_guests}');" class="btn" type="button">{tot_guests}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{total}');" class="btn" type="button">{total}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{total_paid}');" class="btn" type="button">{total_paid}</button>
								<button onclick="setSmsTplTag('smsadmintpl', '{remaining_balance}');" class="btn" type="button">{remaining_balance}</button>
							</div>
						</div>
						<div class="control vbo-smstpl-control">
							<textarea name="smsadmintpl" id="smsadmintpl" style="width: 90%; min-width: 90%; max-width: 100%; height: 100px;"><?php echo VikBooking::getSMSAdminTemplate(); ?></textarea>
						</div>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSCUSTOTPL'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-toolbar vbo-smstpl-toolbar">
							<div class="btn-group pull-left vbo-smstpl-bgroup">
								<button onclick="setSmsTplTag('smscustomertpl', '{customer_name}');" class="btn" type="button">{customer_name}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{customer_pin}');" class="btn" type="button">{customer_pin}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{booking_id}');" class="btn" type="button">{booking_id}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{checkin_date}');" class="btn" type="button">{checkin_date}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{checkout_date}');" class="btn" type="button">{checkout_date}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{num_nights}');" class="btn" type="button">{num_nights}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{rooms_booked}');" class="btn" type="button">{rooms_booked}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{rooms_names}');" class="btn" type="button">{rooms_names}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{tot_adults}');" class="btn" type="button">{tot_adults}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{tot_children}');" class="btn" type="button">{tot_children}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{tot_guests}');" class="btn" type="button">{tot_guests}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{total}');" class="btn" type="button">{total}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{total_paid}');" class="btn" type="button">{total_paid}</button>
								<button onclick="setSmsTplTag('smscustomertpl', '{remaining_balance}');" class="btn" type="button">{remaining_balance}</button>
							</div>
						</div>
						<div class="control vbo-smstpl-control">
							<textarea name="smscustomertpl" id="smscustomertpl" style="width: 90%; min-width: 90%; max-width: 100%; height: 100px;"><?php echo VikBooking::getSMSCustomerTemplate(); ?></textarea>
						</div>
					</div>
				</div>
				<div class="vbo-param-container" id="smsadmintplpend-tr" style="display: <?php echo $sendsmswhen <= 1 ? 'none' : 'flex'; ?>;">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSADMTPLPEND'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-toolbar vbo-smstpl-toolbar">
							<div class="btn-group pull-left vbo-smstpl-bgroup">
								<button onclick="setSmsTplTag('smsadmintplpend', '{customer_name}');" class="btn" type="button">{customer_name}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{booking_id}');" class="btn" type="button">{booking_id}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{checkin_date}');" class="btn" type="button">{checkin_date}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{checkout_date}');" class="btn" type="button">{checkout_date}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{num_nights}');" class="btn" type="button">{num_nights}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{rooms_booked}');" class="btn" type="button">{rooms_booked}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{rooms_names}');" class="btn" type="button">{rooms_names}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{customer_country}');" class="btn" type="button">{customer_country}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{customer_email}');" class="btn" type="button">{customer_email}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{customer_phone}');" class="btn" type="button">{customer_phone}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{tot_adults}');" class="btn" type="button">{tot_adults}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{tot_children}');" class="btn" type="button">{tot_children}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{tot_guests}');" class="btn" type="button">{tot_guests}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{total}');" class="btn" type="button">{total}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{total_paid}');" class="btn" type="button">{total_paid}</button>
								<button onclick="setSmsTplTag('smsadmintplpend', '{remaining_balance}');" class="btn" type="button">{remaining_balance}</button>
							</div>
						</div>
						<div class="control vbo-smstpl-control">
							<textarea name="smsadmintplpend" id="smsadmintplpend" style="width: 90%; min-width: 90%; max-width: 100%; height: 100px;"><?php echo VikBooking::getSMSAdminTemplate(null, 'standby'); ?></textarea>
						</div>
					</div>
				</div>
				<div class="vbo-param-container" id="smscustomertplpend-tr" style="display: <?php echo $sendsmswhen <= 1 ? 'none' : 'flex'; ?>;">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSCUSTOTPLPEND'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-toolbar vbo-smstpl-toolbar">
							<div class="btn-group pull-left vbo-smstpl-bgroup">
								<button onclick="setSmsTplTag('smscustomertplpend', '{customer_name}');" class="btn" type="button">{customer_name}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{customer_pin}');" class="btn" type="button">{customer_pin}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{booking_id}');" class="btn" type="button">{booking_id}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{checkin_date}');" class="btn" type="button">{checkin_date}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{checkout_date}');" class="btn" type="button">{checkout_date}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{num_nights}');" class="btn" type="button">{num_nights}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{rooms_booked}');" class="btn" type="button">{rooms_booked}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{rooms_names}');" class="btn" type="button">{rooms_names}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{tot_adults}');" class="btn" type="button">{tot_adults}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{tot_children}');" class="btn" type="button">{tot_children}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{tot_guests}');" class="btn" type="button">{tot_guests}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{total}');" class="btn" type="button">{total}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{total_paid}');" class="btn" type="button">{total_paid}</button>
								<button onclick="setSmsTplTag('smscustomertplpend', '{remaining_balance}');" class="btn" type="button">{remaining_balance}</button>
							</div>
						</div>
						<div class="control vbo-smstpl-control">
							<textarea name="smscustomertplpend" id="smscustomertplpend" style="width: 90%; min-width: 90%; max-width: 100%; height: 100px;"><?php echo VikBooking::getSMSCustomerTemplate(null, 'standby'); ?></textarea>
						</div>
					</div>
				</div>
				<div class="vbo-param-container" id="smsadmintplcanc-tr">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSADMTPLCANC'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-toolbar vbo-smstpl-toolbar">
							<div class="btn-group pull-left vbo-smstpl-bgroup">
								<button onclick="setSmsTplTag('smsadmintplcanc', '{customer_name}');" class="btn" type="button">{customer_name}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{booking_id}');" class="btn" type="button">{booking_id}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{checkin_date}');" class="btn" type="button">{checkin_date}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{checkout_date}');" class="btn" type="button">{checkout_date}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{num_nights}');" class="btn" type="button">{num_nights}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{rooms_booked}');" class="btn" type="button">{rooms_booked}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{rooms_names}');" class="btn" type="button">{rooms_names}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{customer_country}');" class="btn" type="button">{customer_country}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{customer_email}');" class="btn" type="button">{customer_email}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{customer_phone}');" class="btn" type="button">{customer_phone}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{tot_adults}');" class="btn" type="button">{tot_adults}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{tot_children}');" class="btn" type="button">{tot_children}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{tot_guests}');" class="btn" type="button">{tot_guests}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{total}');" class="btn" type="button">{total}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{total_paid}');" class="btn" type="button">{total_paid}</button>
								<button onclick="setSmsTplTag('smsadmintplcanc', '{remaining_balance}');" class="btn" type="button">{remaining_balance}</button>
							</div>
						</div>
						<div class="control vbo-smstpl-control">
							<textarea name="smsadmintplcanc" id="smsadmintplcanc" style="width: 90%; min-width: 90%; max-width: 100%; height: 100px;"><?php echo VikBooking::getSMSAdminTemplate(null, 'cancelled'); ?></textarea>
						</div>
					</div>
				</div>
				<div class="vbo-param-container" id="smscustomertplcanc-tr">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSCUSTOTPLCANC'); ?></div>
					<div class="vbo-param-setting">
						<div class="btn-toolbar vbo-smstpl-toolbar">
							<div class="btn-group pull-left vbo-smstpl-bgroup">
								<button onclick="setSmsTplTag('smscustomertplcanc', '{customer_name}');" class="btn" type="button">{customer_name}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{customer_pin}');" class="btn" type="button">{customer_pin}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{booking_id}');" class="btn" type="button">{booking_id}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{checkin_date}');" class="btn" type="button">{checkin_date}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{checkout_date}');" class="btn" type="button">{checkout_date}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{num_nights}');" class="btn" type="button">{num_nights}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{rooms_booked}');" class="btn" type="button">{rooms_booked}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{rooms_names}');" class="btn" type="button">{rooms_names}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{tot_adults}');" class="btn" type="button">{tot_adults}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{tot_children}');" class="btn" type="button">{tot_children}</button>
								<button onclick="setSmsTplTag('smscustomertplcanc', '{tot_guests}');" class="btn" type="button">{tot_guests}</button>
							</div>
						</div>
						<div class="control vbo-smstpl-control">
							<textarea name="smscustomertplcanc" id="smscustomertplcanc" style="width: 90%; min-width: 90%; max-width: 100%; height: 100px;"><?php echo VikBooking::getSMSCustomerTemplate(null, 'cancelled'); ?></textarea>
						</div>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
</div>

<div class="vbo-config-maintab-right">
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBCONFIGSMSPARAMETERS'); ?></legend>
			<div class="vbo-params-container">
				<div id="vbo-sms-params"><?php echo !empty($current_smsapi) ? VikBooking::displaySMSParameters($current_smsapi, VikBooking::getSMSParams(false)) : ''; ?></div>
		<?php
		if (!empty($current_smsapi)) {
			require_once(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'smsapi' . DIRECTORY_SEPARATOR . $current_smsapi);
			if (method_exists('VikSmsApi', 'estimate')) {
				?>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGSMSREMAINBAL'); ?></div>
					<div class="vbo-param-setting">
						<button type="button" class="btn vbo-config-btn" onclick="vboEstimateCredit();"><i class="vboicn-coin-euro"></i><?php echo JText::translate('VBCONFIGSMSESTCREDIT'); ?></button>
						<div id="vbo-sms-balance"></div>
					</div>
				</div>
				<?php
			}
		}
		?>
			</div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
if (jQuery.isFunction(jQuery.fn.tooltip)) {
	jQuery(".hasTooltip").tooltip();
}
function displaySMSTexts(sval) {
	if (parseInt(sval) <= 1) {
		document.getElementById('smsadmintplpend-tr').style.display = 'none';
		document.getElementById('smscustomertplpend-tr').style.display = 'none';
	} else {
		document.getElementById('smsadmintplpend-tr').style.display = 'flex';
		document.getElementById('smscustomertplpend-tr').style.display = 'flex';
	}
}
function setSmsTplTag(taid, tpltag) {
	var tplobj = document.getElementById(taid);
	if (tplobj != null) {
		var start = tplobj.selectionStart;
		var end = tplobj.selectionEnd;
		tplobj.value = tplobj.value.substring(0, start) + tpltag + tplobj.value.substring(end);
		tplobj.selectionStart = tplobj.selectionEnd = start + tpltag.length;
		tplobj.focus();
	}
}
function vikLoadSMSParameters(pfile) {
	if (pfile.length > 0) {
		jQuery("#vbo-sms-params").html('<?php echo addslashes(JText::translate('VIKLOADING')); ?>');
		jQuery.ajax({
			type: "POST",
			url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=loadsmsparams'); ?>",
			data: { phpfile: pfile }
		}).done(function(res) {
			jQuery("#vbo-sms-params").html(res);
		});
	} else {
		jQuery("#vbo-sms-params").html('--------');
	}
}
function vboEstimateCredit() {
	jQuery("#vbo-sms-balance").html('<?php echo addslashes(JText::translate('VIKLOADING')); ?>');
	jQuery.ajax({
		type: "POST",
		url: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=loadsmsbalance'); ?>",
		data: { vbo: '1' }
	}).done(function(res) {
		jQuery("#vbo-sms-balance").html(res);
	});
}
</script>
