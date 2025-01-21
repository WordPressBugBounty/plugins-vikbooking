<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 e4j - E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Request information form.
 */

$vbo_app = VikBooking::getVboApplication();

if ((bool) VikBooking::getRoomParam('reqinfo', $this->room['params'])) {
	$pitemid = VikRequest::getInt('Itemid', '', 'request');
	$reqinfotoken = rand(1, 999);
	JFactory::getSession()->set('vboreqinfo' . $this->room['id'], $reqinfotoken);
	$cur_user = JFactory::getUser();
	$cur_email = '';
	if (property_exists($cur_user, 'email') && !empty($cur_user->email)) {
		$cur_email = $cur_user->email;
	}
	?>
<div class="vbo-reqinfo-cont">
	<span><a href="Javascript: void(0);" onclick="vboShowRequestInfo();" class="vbo-reqinfo-opener vbo-pref-color-btn"><?php echo JText::translate('VBOROOMREQINFOBTN'); ?></a></span>
</div>
<div id="vbdialog-overlay" style="display: none;">
	<a class="vbdialog-overlay-close" href="javascript: void(0);"></a>
	<div class="vbdialog-inner vbdialog-reqinfo">
		<h3><?php echo JText::sprintf('VBOROOMREQINFOTITLE', $this->room['name']); ?></h3>
		<form action="<?php echo JRoute::rewrite('index.php?option=com_vikbooking&task=reqinfo'.(!empty($pitemid) ? '&Itemid='.$pitemid : '')); ?>" method="post">
			<input type="hidden" name="roomid" value="<?php echo $this->room['id']; ?>" />
			<input type="hidden" name="reqinfotoken" value="<?php echo $reqinfotoken; ?>" />
			<input type="hidden" name="Itemid" value="<?php echo $pitemid; ?>" />
			<div class="vbdialog-reqinfo-formcont">
				<div class="vbdialog-reqinfo-formentry">
					<label for="reqname"><?php echo JText::translate('VBOROOMREQINFONAME'); ?></label>
					<input type="text" name="reqname" id="reqname" value="" placeholder="<?php echo JText::translate('VBOROOMREQINFONAME'); ?>" required />
				</div>
				<div class="vbdialog-reqinfo-formentry">
					<label for="reqemail"><?php echo JText::translate('VBOROOMREQINFOEMAIL'); ?></label>
					<input type="email" name="reqemail" id="reqemail" value="<?php echo $cur_email; ?>" placeholder="<?php echo JText::translate('VBOROOMREQINFOEMAIL'); ?>" required />
				</div>
				<div class="vbdialog-reqinfo-formentry">
					<label for="reqmess"><?php echo JText::translate('VBOROOMREQINFOMESS'); ?></label>
					<textarea name="reqmess" id="reqmess" placeholder="<?php echo JText::translate('VBOROOMREQINFOMESS'); ?>"></textarea>
				</div>
			<?php
			if ($this->terms_fields) {
				foreach ($this->terms_fields as $k => $terms_field) {
					if (!empty($terms_field['poplink'])) {
						$fname = "<a href=\"" . $terms_field['poplink'] . "\" id=\"vbof{$k}\" rel=\"{handler: 'iframe', size: {x: 750, y: 600}}\" target=\"_blank\" class=\"vbomodalframe\">" . JText::translate($terms_field['name']) . "</a>";
					} else {
						$fname = "<label id=\"vbof{$k}\" for=\"vbof-inp{$k}\" style=\"display: inline-block;\">" . JText::translate($terms_field['name']) . "</label>";
					}
					?>
					<div class="vbdialog-reqinfo-formentry vbdialog-reqinfo-formentry-ckbox">
						<?php echo $fname; ?>
						<input type="checkbox" name="vbof" id="vbof-inp<?php echo $k; ?>" value="<?php echo JText::translate('VBYES'); ?>" required />
					</div>
					<?php
				}
			} else {
				?>
				<div class="vbdialog-reqinfo-formentry vbdialog-reqinfo-formentry-ckbox">
					<label id="vbof" for="vbof-inp" style="display: inline-block;"><?php echo JText::translate('ORDER_TERMSCONDITIONS'); ?></label>
					<input type="checkbox" name="vbof" id="vbof-inp" value="<?php echo JText::translate('VBYES'); ?>" required />
				</div>
				<?php
			}
			if ($vbo_app->isCaptcha()) {
				?>
				<div class="vbdialog-reqinfo-formentry vbdialog-reqinfo-formentry-captcha">
					<div><?php echo $vbo_app->reCaptcha(); ?></div>
				</div>
				<?php
			}
			?>
				<div class="vbdialog-reqinfo-formentry vbdialog-reqinfo-formsubmit">
					<button type="submit" class="btn vbo-pref-color-btn"><?php echo JText::translate('VBOROOMREQINFOSEND'); ?></button>
				</div>
			</div>
		</form>
	</div>
</div>

<script type="text/javascript">
var vbdialog_on = false;
function vboShowRequestInfo() {
	jQuery("#vbdialog-overlay").fadeIn();
	vbdialog_on = true;
}
function vboHideRequestInfo() {
	jQuery("#vbdialog-overlay").fadeOut();
	vbdialog_on = false;
}
jQuery(function() {
	jQuery(document).mouseup(function(e) {
		if (!vbdialog_on) {
			return false;
		}
		var vbdialog_cont = jQuery(".vbdialog-inner");
		if (!vbdialog_cont.is(e.target) && vbdialog_cont.has(e.target).length === 0) {
			vboHideRequestInfo();
		}
	});
	jQuery(document).keyup(function(e) {
		if (e.keyCode == 27 && vbdialog_on) {
			vboHideRequestInfo();
		}
	});
});
</script>
	<?php
}
