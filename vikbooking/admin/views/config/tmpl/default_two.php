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
$formatvals = VikBooking::getNumberFormatData(true);
$formatparts = explode(':', $formatvals);
?>
<div class="vbo-config-maintab-left">
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMCURRENCY'); ?></legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREECURNAME'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="currencyname" value="<?php echo JHtml::fetch('esc_attr', VikBooking::getCurrencyName()); ?>" size="10"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREECURSYMB'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="currencysymb" value="<?php echo JHtml::fetch('esc_attr', VikBooking::getCurrencySymb(true)); ?>" size="10"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTHREECURCODEPP'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="currencycodepp" value="<?php echo JHtml::fetch('esc_attr', VikBooking::getCurrencyCodePp()); ?>" size="10"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGNUMDECIMALS'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="numdecimals" value="<?php echo $formatparts[0]; ?>" min="0" max="9"/></div>
				</div>
				<div class="vbo-param-container vbo-param-nested">
					<div class="vbo-param-label"><?php echo JText::translate('VBO_NO_EMPTY_DECIMALS'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('noemptydecimals', JText::translate('VBYES'), JText::translate('VBNO'), VikBooking::hideEmptyDecimals(), 1, 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGNUMDECSEPARATOR'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="decseparator" value="<?php echo JHtml::fetch('esc_attr', $formatparts[1]); ?>" size="2"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGNUMTHOSEPARATOR'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="thoseparator" value="<?php echo JHtml::fetch('esc_attr', $formatparts[2]); ?>" size="2"/></div>
				</div>
			</div>
		</div>
	</fieldset>

	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMTAXPAY'); ?></legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTWOFIVE'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('ivainclusa', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::ivaInclusa(true) ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTAXSUMMARY'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('taxsummary', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::showTaxOnSummaryOnly(true) ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGMULTIPAY'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('multipay', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::multiplePayments() ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTWOSIX'); ?></div>
					<div class="vbo-param-setting"><input type="text" name="paymentname" value="<?php echo JHtml::fetch('esc_attr', VikBooking::getPaymentName()); ?>" size="25"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGONETENSEVEN'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="minuteslock" value="<?php echo VikBooking::getMinutesLock(); ?>" min="0"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGMINAUTOREMOVE'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="minautoremove" value="<?php echo VikBooking::getMinutesAutoRemove(); ?>" min="0"/></div>
				</div>
			</div>
		</div>
	</fieldset>
</div>

<?php
$dep_overrides = VikBooking::getDepositOverrides(true);
?>
<div class="vbo-info-overlay-block">
	<a class="vbo-info-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-info-overlay-content vbo-info-overlay-content-depovr"></div>
</div>

<div class="vbo-config-maintab-right">
	<fieldset class="adminform">
		<div class="vbo-params-wrap">
			<legend class="adminlegend"><?php echo JText::translate('VBOCPARAMDEPOSITPAY'); ?></legend>
			<div class="vbo-params-container">
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTWOTHREE'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('paytotal', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::payTotal() ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBCONFIGTWOFOUR'); ?></div>
					<div class="vbo-param-setting"><input type="number" name="payaccpercent" value="<?php echo VikBooking::getAccPerCent(); ?>" min="0"/> <select id="typedeposit" name="typedeposit"><option value="pcent">%</option><option value="fixed"<?php echo (VikBooking::getTypeDeposit(true) == "fixed" ? ' selected="selected"' : ''); ?>><?php echo VikBooking::getCurrencySymb(); ?></option></select></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFDEPOSITOVRDS'); ?></div>
					<div class="vbo-param-setting">
						<input type="hidden" id="depoverrides" name="depoverrides" value="<?php echo htmlspecialchars($dep_overrides); ?>"/>
						<div id="cur_depoverrides" class="cur_depoverrides"></div>
						<button type="button" class="btn" onclick="vboDisplayDepositOverrides();"><i class="vboicn-pencil2 icn-nomargin"></i></button>
					</div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFDEPONLYIFDADV'); ?></div>
					<div class="vbo-param-setting"><input type="number" style="max-width: 60px;" name="depifdaysadv" min="0" value="<?php echo VikBooking::getDepositIfDays(); ?>"/></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo JText::translate('VBOCONFDEPCUSTCHOICE'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('depcustchoice', JText::translate('VBYES'), JText::translate('VBNO'), (VikBooking::depositCustomerChoice() ? 'yes' : 0), 'yes', 0); ?></div>
				</div>
				<div class="vbo-param-container">
					<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBOCONFNODEPNONREFUND'), 'content' => JText::translate('VBOCONFNODEPNONREFUNDHELP'))); ?> <?php echo JText::translate('VBOCONFNODEPNONREFUND'); ?></div>
					<div class="vbo-param-setting"><?php echo $vbo_app->printYesNoButtons('nodepnonrefund', JText::translate('VBYES'), JText::translate('VBNO'), (int)VikBooking::noDepositForNonRefund(), 1, 0); ?></div>
				</div>
			</div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
var vbo_overlay_on = false;
var vbo_depovr_defs = {
	"nights": "<?php echo addslashes(JText::translate('VBDAYS')); ?>",
	"more": "<?php echo addslashes(JText::translate('VBOCONFDEPOSITOVRDSMORE')); ?>",
	"add": "<?php echo addslashes(JText::translate('VBCONFIGCLOSINGDATEADD')); ?>",
	"apply": "<?php echo addslashes(JText::translate('VBAPPLY')); ?>"
}
jQuery(document).ready(function() {
	vboPopulateDepositOverrides();
	jQuery(document).mouseup(function(e) {
		if (!vbo_overlay_on) {
			return false;
		}
		var vbo_overlay_cont = jQuery(".vbo-info-overlay-content");
		if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
			vboHideDepositOverrides();
		}
	});
	jQuery(document).keyup(function(e) {
		if (e.keyCode == 27 && vbo_overlay_on) {
			vboHideDepositOverrides();
		}
	});
});
function vboDisplayDepositOverrides() {
	jQuery(".vbo-info-overlay-block").fadeIn(400, function() {
		if (jQuery(".vbo-info-overlay-block").is(":visible")) {
			vbo_overlay_on = true;
		} else {
			vbo_overlay_on = false;
		}
	});
}
function vboHideDepositOverrides() {
	jQuery(".vbo-info-overlay-block").fadeOut();
	vbo_overlay_on = false;
}
function vboPopulateDepositOverrides() {
	var vbo_dep_overrides = jQuery('#depoverrides').val();
	var vbo_dep_type = document.getElementById('typedeposit');
	var vbo_dep_oper = vbo_dep_type.options[vbo_dep_type.selectedIndex].text;
	try {
		var dep_ovr_obj = JSON.parse(vbo_dep_overrides);
		var tot_ovr = Object.keys(dep_ovr_obj).length;
		var cur_ovr_str  = '',
			cur_ovr_cont = '<div class="vbo-info-overlay-header">';
		cur_ovr_cont += '<h3><?php echo addslashes(JText::translate('VBOCONFDEPOSITOVRDS')); ?></h3>';
		cur_ovr_cont += '<div class="vbo-info-overlay-buttons-wrap">';
		// always include the add and apply buttons
		cur_ovr_cont += '<button type="button" class="btn vbo-config-btn" onclick="vboAddDepositeOverride();"><?php VikBookingIcons::e('plus-circle'); ?> '+vbo_depovr_defs.add+'</button>';
		cur_ovr_cont += '<button type="button" class="btn btn-success" onclick="vboApplyDepositeOverrides();"><i class="vboicn-checkmark"></i> '+vbo_depovr_defs.apply+'</button>';
		//
		cur_ovr_cont += '</div>';
		cur_ovr_cont += '</div>';
		if (tot_ovr > 1) {
			for (var prop in dep_ovr_obj) {
				if (!dep_ovr_obj.hasOwnProperty(prop) || prop == 'more') {
					continue;
				}
				cur_ovr_str += '<span>'+vbo_depovr_defs.nights+': '+prop+(dep_ovr_obj['more'] == prop ? ' '+vbo_depovr_defs.more : '')+', '+(vbo_dep_oper != '%' ? vbo_dep_oper+' ' : '')+dep_ovr_obj[prop]+(vbo_dep_oper == '%' ? vbo_dep_oper : '')+'</span>';
				cur_ovr_cont += '<div class="new_depovr_container">'+
									'<span><span>'+vbo_depovr_defs.nights+'</span><input type="number" min="0" class="new_depovr_nights" value="'+prop+'"/></span>'+
									'<span>' + (vbo_dep_oper != '%' ? '<span>'+vbo_dep_oper+'</span>' : '') + '<input type="number" min="0" step="any" class="new_depovr_amounts" value="'+dep_ovr_obj[prop]+'"/>' + (vbo_dep_oper == '%' ? '<span>'+vbo_dep_oper+'</span>' : '') + '</span>'+
									'<span><select class="new_depovr_more"><option value="">---</option><option value="more">'+vbo_depovr_defs.more+'</option></select></span>'+
									'<span><button type="button" class="btn btn-danger" onclick="jQuery(this).closest(\'.new_depovr_container\').remove();">&times;</button></span>'+
								'</div>';
			}
			jQuery('#cur_depoverrides').html(cur_ovr_str);
		} else {
			//no overrides defined: make the boxes empty
			jQuery('#cur_depoverrides').html('');
		}
		jQuery('.vbo-info-overlay-content-depovr').html(cur_ovr_cont);
	} catch(e) {
		console.log('cannot parse JSON');
		console.log(e);
	}
}
function vboAddDepositeOverride() {
	var vbo_dep_type = document.getElementById('typedeposit');
	var vbo_dep_oper = vbo_dep_type.options[vbo_dep_type.selectedIndex].text;
	var add_ovr_cont = '<div class="new_depovr_container">'+
							'<span><span>'+vbo_depovr_defs.nights+'</span><input type="number" min="0" class="new_depovr_nights" value=""/></span>'+
							'<span>' + (vbo_dep_oper != '%' ? '<span>'+vbo_dep_oper+'</span>' : '') + '<input type="number" min="0" step="any" class="new_depovr_amounts" value=""/>' + (vbo_dep_oper == '%' ? '<span>'+vbo_dep_oper+'</span>' : '') + '</span>'+
							'<span><select class="new_depovr_more"><option value="">---</option><option value="more">'+vbo_depovr_defs.more+'</option></select></span>'+
							'<span><button type="button" class="btn btn-danger" onclick="jQuery(this).closest(\'.new_depovr_container\').remove();">&times;</button></span>'+
						'</div>';
	var cur_ovrs = jQuery('.new_depovr_container');
	if (cur_ovrs.length > 0) {
		cur_ovrs.last().after(add_ovr_cont);
	} else {
		jQuery('.vbo-info-overlay-content-depovr').append(add_ovr_cont);
	}
}
function vboApplyDepositeOverrides() {
	var respval = {"more": ""};
	var nights_arr = jQuery('.new_depovr_nights');
	var amounts_arr = jQuery('.new_depovr_amounts');
	var more_arr = jQuery('.new_depovr_more');
	nights_arr.each(function(k, v) {
		var use_nights = jQuery(this).val();
		var use_amounts = jQuery(amounts_arr[k]).val();
		if (isNaN(use_nights) || isNaN(use_amounts)) {
			console.log('skipping loop #'+k);
			return true;
		}
		respval[parseInt(use_nights)] = parseFloat(use_amounts);
		if (jQuery(more_arr[k]).val() == 'more') {
			respval['more'] = parseInt(use_nights);
		}
	});
	jQuery('#depoverrides').val(JSON.stringify(respval));
	vboHideDepositOverrides();
	vboPopulateDepositOverrides();
}
</script>
