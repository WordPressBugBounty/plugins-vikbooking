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

$trksettings = $this->trksettings;

$trksettings['trkcampaigns'] = empty($trksettings['trkcampaigns']) ? array() : json_decode($trksettings['trkcampaigns'], true);
$trksettings['trkcampaigns'] = !is_array($trksettings['trkcampaigns']) ? array() : $trksettings['trkcampaigns'];

$vbo_app 	= new VboApplication();
$vbobaseuri = JUri::root();

?>
<script type="text/javascript">
var randspool  = new Array;
var vbobaseuri = '<?php echo $vbobaseuri; ?>';
jQuery(document).ready(function() {
	jQuery('#vbo-add-trkcampaign').click(function() {
		var randkey = Math.floor(Math.random() * (9999 - 1000)) + 1000;
		if (randspool.indexOf(randkey) > -1) {
			while (randspool.indexOf(randkey) > -1) {
				randkey = Math.floor(Math.random() * (9999 - 1000)) + 1000;
			}
		}
		randspool.push(randkey);
		// for Nginx compatibility, we concatenate to the numeric key a random 3 char string
		randkey += vboGetRandString(3);
		//
		var ind = jQuery('.vbo-trackings-custcampaign').length + 1;
		var campcont = '<div class="vbo-trackings-custcampaign">' + "\n" +
							'<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-name">' + "\n" +
								'<label for="vbo-name-'+ind+'"><?php echo addslashes(JText::translate('VBTRKCAMPAIGNNAME')); ?></label>' + "\n" +
								'<input type="text" name="trkcampname[]" id="vbo-name-'+ind+'" value="" size="30" placeholder="<?php echo addslashes(JText::translate('VBTRKCAMPAIGNNAME')); ?>" />' + "\n" +
							'</div>' + "\n" +
							'<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-key">' + "\n" +
								'<label for="vbo-key-'+ind+'"><?php echo addslashes(JText::translate('VBTRKCAMPAIGNKEY')); ?></label>' + "\n" +
								'<input type="text" name="trkcampkey[]" id="vbo-key-'+ind+'" onkeyup="vboCustCampaignUri(this);" value="'+randkey+'" size="10" />' + "\n" +
							'</div>' + "\n" +
							'<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-val">' + "\n" +
								'<label for="vbo-val-'+ind+'"><?php echo addslashes(JText::translate('VBTRKCAMPAIGNVAL')); ?></label>' + "\n" +
								'<input type="text" name="trkcampval[]" id="vbo-val-'+ind+'" onkeyup="vboCustCampaignUri(this);" value="" size="10" />' + "\n" +
							'</div>' + "\n" +
							'<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-rm">' + "\n" +
								'<a class="btn btn-danger" href="javascript: void(0);" onclick="vboRmCustCampaign(this);">&times;</a>' + "\n" +
							'</div>' + "\n" +
							'<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-uri"></div>' + "\n" +
						'</div>';
		jQuery('.vbo-trackings-custcampaigns').append(campcont);
		setTimeout(function() {
			vboCustCampaignUri(document.getElementById('vbo-key-'+ind));
		}, 300);
	});
});
function vboGetRandString(len) {
	var randstr = "";
	var charsav = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	for (var i = 0; i < len; i++) {
		randstr += charsav.charAt(Math.floor(Math.random() * charsav.length));
	}
	return randstr;
}
function vboRmCustCampaign(elem) {
	jQuery(elem).closest('.vbo-trackings-custcampaign').remove();
}
function vboCustCampaignUri(elem) {
	var cont = jQuery(elem);
	var sval = cont.val();
	if (/\s/g.test(sval)) {
		sval = sval.replace(/\s/g, '');
		cont.val(sval);
	}
	var rkey = '';
	var rval = '';
	if (cont.parent('.vbo-trackings-custcampaign-box').hasClass('vbo-trackings-custcampaign-key')) {
		rkey = sval;
		rval = cont.closest('.vbo-trackings-custcampaign').find('.vbo-trackings-custcampaign-val').find('input').val();
	} else {
		rval = sval;
		rkey = cont.closest('.vbo-trackings-custcampaign').find('.vbo-trackings-custcampaign-key').find('input').val();
	}
	cont.closest('.vbo-trackings-custcampaign').find('.vbo-trackings-custcampaign-uri').text(vbobaseuri+'?'+rkey+(rval.length ? '='+rval : ''));
}
</script>

<form action="index.php?option=com_vikbooking" method="post" name="adminForm" id="adminForm">

	<div class="vbo-admin-container vbo-config-tab-container">

		<fieldset class="adminform">
			<div class="vbo-params-wrap vbo-params-wrap-fullwidth">
				<legend class="adminlegend"><?php echo JText::translate('VBTRKSETTINGS'); ?></legend>
				<div class="vbo-params-container">

					<div class="vbo-param-container">
						<div class="vbo-param-label"><?php echo JText::translate('VBTRKENABLED'); ?></div>
						<div class="vbo-param-setting">
							<?php echo $vbo_app->printYesNoButtons('trkenabled', JText::translate('VBYES'), JText::translate('VBNO'), (int)$trksettings['trkenabled'], 1, 0); ?>
						</div>
					</div>

					<div class="vbo-param-container">
						<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBTRKCOOKIERFRDUR'), 'content' => JText::translate('VBTRKCOOKIERFRDURHELP'))); ?> <?php echo JText::translate('VBTRKCOOKIERFRDUR'); ?></div>
						<div class="vbo-param-setting">
							<input type="number" step="any" min="0" name="trkcookierfrdur" value="<?php echo JHtml::fetch('esc_attr', $trksettings['trkcookierfrdur']); ?>" /> (<?php echo strtolower(JText::translate('VBCONFIGSEARCHPMAXDATEDAYS')); ?>)
						</div>
					</div>

					<div class="vbo-param-container">
						<div class="vbo-param-label"><?php echo $vbo_app->createPopover(array('title' => JText::translate('VBTRKCAMPAIGNS'), 'content' => JText::translate('VBTRKCAMPAIGNSHELP'))); ?> <?php echo JText::translate('VBTRKCAMPAIGNS'); ?></div>
						<div class="vbo-param-setting">
							<button class="btn vbo-config-btn" type="button" id="vbo-add-trkcampaign"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBTRKADDCAMPAIGN'); ?></button>
						</div>
					</div>

					<div class="vbo-param-container vbo-param-container-full">
						<div class="vbo-param-setting">
							<div class="vbo-trackings-custcampaigns">
							<?php
							$i = 0;
							foreach ($trksettings['trkcampaigns'] as $rkey => $rvalue) {
								?>
								<div class="vbo-trackings-custcampaign">
									<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-name">
										<label for="vbo-name-<?php echo $i; ?>"><?php echo JText::translate('VBTRKCAMPAIGNNAME'); ?></label>
										<input type="text" name="trkcampname[]" id="vbo-name-<?php echo $i; ?>" value="<?php echo JHtml::fetch('esc_attr', $rvalue['name']); ?>" size="30" />
									</div>
									<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-key">
										<label for="vbo-key-<?php echo $i; ?>"><?php echo JText::translate('VBTRKCAMPAIGNKEY'); ?></label>
										<input type="text" name="trkcampkey[]" id="vbo-key-<?php echo $i; ?>" onkeyup="vboCustCampaignUri(this);" value="<?php echo JHtml::fetch('esc_attr', $rkey); ?>" size="10" />
									</div>
									<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-val">
										<label for="vbo-val-<?php echo $i; ?>"><?php echo JText::translate('VBTRKCAMPAIGNVAL'); ?></label>
										<input type="text" name="trkcampval[]" id="vbo-val-<?php echo $i; ?>" onkeyup="vboCustCampaignUri(this);" value="<?php echo JHtml::fetch('esc_attr', $rvalue['value']); ?>" size="10" />
									</div>
									<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-rm">
										<a class="btn btn-danger" href="javascript: void(0);" onclick="vboRmCustCampaign(this);">&times;</a>
									</div>
									<div class="vbo-trackings-custcampaign-box vbo-trackings-custcampaign-uri"><?php echo $vbobaseuri.'?'.$rkey.(!empty($rvalue['value']) ? '='.$rvalue['value'] : ''); ?></div>
								</div>
								<?php
								$i++;
							}
							?>
							</div>
						</div>
					</div>

					<div class="vbo-param-container vbo-param-container-full">
						<div class="vbo-param-setting">
							<span class="vbo-param-setting-comment"><?php VikBookingIcons::e('info-circle'); ?> <?php echo JText::translate('VBTRKCOOKIEEXPL'); ?></span>
						</div>
					</div>

				</div>
			</div>
		</fieldset>

	</div>

	<input type="hidden" name="option" value="com_vikbooking" />
	<input type="hidden" name="task" value="" />
	<?php echo JHtml::fetch('form.token'); ?>
</form>
