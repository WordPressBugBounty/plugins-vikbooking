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
$vbo_app->prepareModalBox();

// JS lang def
JText::script('VBCONFIGFLUSHSESSIONCONF')

?>
<div class="vbo-admin-body vbo-config-body">
	
	<form name="adminForm" id="adminForm" action="index.php" method="post" enctype="multipart/form-data">

		<div class="vbo-config-tabs-wrap">
			<dl class="tabs" id="tab_group_id">
				<dt class="vbo-renewsession-dt">
					<a href="javascript: void(0);" class="vbflushsession" onclick="vbFlushSession();"><?php echo JText::translate('VBCONFIGFLUSHSESSION'); ?></a>
				</dt>
				<dt class="tabs <?php echo $this->curtabid == 1 ? 'open' : 'closed'; ?>" data-ptid="1" style="cursor: pointer;">
					<span>
						<h3>
							<?php VikBookingIcons::e('sliders-h'); ?>
							<a href="javascript:void(0);"><?php echo JText::translate('VBPANELONE'); ?></a>
						</h3>
					</span>
				</dt><dt class="tabs <?php echo $this->curtabid == 2 ? 'open' : 'closed'; ?>" data-ptid="2" style="cursor: pointer;">
					<span>
						<h3>
							<?php VikBookingIcons::e('funnel-dollar'); ?>
							<a href="javascript:void(0);"><?php echo JText::translate('VBPANELTWO'); ?></a>
						</h3>
					</span>
				</dt><dt class="tabs <?php echo $this->curtabid == 3 ? 'open' : 'closed'; ?>" data-ptid="3" style="cursor: pointer;">
					<span>
						<h3>
							<?php VikBookingIcons::e('pencil-alt'); ?>
							<a href="javascript:void(0);"><?php echo JText::translate('VBPANELTHREE'); ?></a>
						</h3>
					</span>
				</dt><dt class="tabs <?php echo $this->curtabid == 4 ? 'open' : 'closed'; ?>" data-ptid="4" style="cursor: pointer;">
					<span>
						<h3>
							<?php VikBookingIcons::e('user-cog'); ?>
							<a href="javascript:void(0);"><?php echo JText::translate('VBPANELFOUR'); ?></a>
						</h3>
					</span>
				</dt><dt class="tabs <?php echo $this->curtabid == 6 ? 'open' : 'closed'; ?>" data-ptid="6" style="cursor: pointer;">
					<span>
						<h3>
							<?php VikBookingIcons::e('star'); ?>
							<a href="javascript:void(0);"><?php echo JText::translate('VBOPANELREVIEWS'); ?></a>
						</h3>
					</span>
				</dt><dt class="tabs <?php echo $this->curtabid == 5 ? 'open' : 'closed'; ?>" data-ptid="5" style="cursor: pointer;">
					<span>
						<h3>
							<?php VikBookingIcons::e('comment'); ?>
							<a href="javascript:void(0);"><?php echo JText::translate('VBPANELFIVE'); ?></a>
						</h3>
					</span>
				</dt><dt class="tabs <?php echo $this->curtabid == 7 ? 'open' : 'closed'; ?>" data-ptid="7" style="cursor: pointer;">
					<span>
						<h3>
							<?php VikBookingIcons::e('quote-right'); ?>
							<a href="javascript:void(0);"><?php echo JText::translate('VBO_COND_TEXTS'); ?></a>
						</h3>
					</span>
				</dt>
			</dl>
		</div>

		<div class="current">
			<dd class="tabs" id="pt1" style="display: <?php echo $this->curtabid == 1 ? 'block' : 'none'; ?>;">
				<div class="vbo-admin-container vbo-config-tab-container">
					<?php echo $this->loadTemplate('one'); ?>
				</div>
			</dd>
			<dd class="tabs" id="pt2" style="display: <?php echo $this->curtabid == 2 ? 'block' : 'none'; ?>;">
				<div class="vbo-admin-container vbo-config-tab-container">
					<?php echo $this->loadTemplate('two'); ?>
				</div>
			</dd>
			<dd class="tabs" id="pt3" style="display: <?php echo $this->curtabid == 3 ? 'block' : 'none'; ?>;">
				<div class="vbo-admin-container vbo-config-tab-container">
					<?php echo $this->loadTemplate('three'); ?>
				</div>
			</dd>
			<dd class="tabs" id="pt4" style="display: <?php echo $this->curtabid == 4 ? 'block' : 'none'; ?>;">
				<div class="vbo-admin-container vbo-config-tab-container">
					<?php echo $this->loadTemplate('four'); ?>
				</div>
			</dd>
			<dd class="tabs" id="pt6" style="display: <?php echo $this->curtabid == 6 ? 'block' : 'none'; ?>;">
				<div class="vbo-admin-container vbo-config-tab-container">
					<?php echo $this->loadTemplate('six'); ?>
				</div>
			</dd>
			<dd class="tabs" id="pt5" style="display: <?php echo $this->curtabid == 5 ? 'block' : 'none'; ?>;">
				<div class="vbo-admin-container vbo-config-tab-container">
					<?php echo $this->loadTemplate('five'); ?>
				</div>
			</dd>
			<dd class="tabs" id="pt7" style="display: <?php echo $this->curtabid == 7 ? 'block' : 'none'; ?>;">
				<div class="vbo-admin-container vbo-config-tab-container">
					<?php echo $this->loadTemplate('seven'); ?>
				</div>
			</dd>
		</div>

		<input type="hidden" name="task" value="config">
		<input type="hidden" name="option" value="com_vikbooking"/>
		<?php echo JHtml::fetch('form.token'); ?>
	</form>
	
</div>

<script type="text/javascript">
function vbFlushSession() {
	if (confirm(Joomla.JText._('VBCONFIGFLUSHSESSIONCONF'))) {
		window.location.href = 'index.php?option=com_vikbooking&task=renewsession';
	} else {
		return false;
	}
}

jQuery(function() {
	jQuery('dt.tabs').click(function() {
		var clicked_tab = jQuery(this);
		var ptid = clicked_tab.attr('data-ptid');
		jQuery('dt.tabs').removeClass('open').addClass('closed');
		clicked_tab.removeClass('closed').addClass('open');
		jQuery('dd.tabs').hide();
		jQuery('dd#pt'+ptid).show();

		var nd = new Date();
		nd.setTime(nd.getTime() + (365*24*60*60*1000));
		document.cookie = "vbConfPt="+ptid+"; expires=" + nd.toUTCString() + "; path=/; SameSite=Lax";

		try {
			// build proper URL
			let re = new RegExp(/&tab=([0-9])$/, 'i');
			let base_uri = window.location.href;
			base_uri = base_uri.replace(re, '');

			// register clicked tab
			VBOCore.registerAdminMenuAction({
				name: clicked_tab.find('a').text(),
				href: base_uri + '&tab=' + ptid,
			}, 'global');
		} catch(e) {
			console.error(e);
		}
	});
});
</script>
