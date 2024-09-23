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
 * Class handler for admin widget "orphan dates".
 * 
 * @since 	1.4.0
 */
class VikBookingAdminWidgetOrphanDates extends VikBookingAdminWidget
{
	/**
	 * Class constructor will define the widget name and identifier.
	 */
	public function __construct()
	{
		// call parent constructor
		parent::__construct();

		$this->widgetName = JText::translate('VBO_W_ORPHDATES_TITLE');
		$this->widgetDescr = JText::translate('VBO_W_ORPHDATES_DESCR');
		$this->widgetId = basename(__FILE__, '.php');

		/**
		 * Define widget and icon and style name.
		 * 
		 * @since 	1.15.0 (J) - 1.5.0 (WP)
		 */
		$this->widgetIcon = '<i class="' . VikBookingIcons::i('exclamation-triangle') . '"></i>';
		$this->widgetStyleName = 'red';
	}

	public function render(VBOMultitaskData $data = null)
	{
		$vbo_auth_pricing = JFactory::getUser()->authorise('core.vbo.pricing', 'com_vikbooking');
		if (!$vbo_auth_pricing) {
			return;
		}

		// check whether we are in the multitask panel
		$is_multitask = $this->isMultitaskRendering();

		?>
		<div class="vbo-admin-widget-wrapper vbo-admin-widget-wrapper-orphandates" style="<?php echo !$is_multitask ? 'display: none;' : ''; ?>">
			<div class="vbo-admin-widget-head">
				<h4><?php echo $this->vbo_app->createPopover(array('title' => JText::translate('VBORPHANSFOUND'), 'content' => JText::translate('VBORPHANSFOUNDSHELP'), 'icon_class' => VikBookingIcons::i('exclamation-triangle'))); ?> <span><?php echo JText::translate('VBORPHANSFOUND'); ?></span></h4>
			</div>
			<div class="vbo-orphans-info-list">
				<div style="min-height: 152px;">
					<div class="vbo-orphans-info-room">
						<h4>&nbsp;&nbsp; ----- </h4>
					</div>
				</div>
			</div>
		</div>

		<script type="text/JavaScript">
		jQuery(function() {
			// check orphans (only if not disabled through the original cookie of the previous versions of Vik Booking)
			var hideorphans = false;
			var buiscuits = document.cookie;
			if (buiscuits.length) {
				var hideorphansck = "vboHideOrphans=1";
				if (buiscuits.indexOf(hideorphansck) >= 0) {
					hideorphans = true;
				}
			}
			if (!hideorphans) {
				// make the request
				var jqxhr = jQuery.ajax({
					type: "POST",
					url: "<?php echo $this->getExecWidgetAjaxUri('index.php?option=com_vikbooking&task=orphanscount'); ?>",
					data: {
						tmpl: "component"
					}
				}).done(function(res) {
					var obj_res = typeof res === 'string' ? JSON.parse(res) : res;
					var orphans_list = '';
					for (var rid in obj_res) {
						if (!obj_res.hasOwnProperty(rid)) {
							continue;
						}
						orphans_list += '<div class="vbo-orphans-info-room">';
						orphans_list += '	<h4 class="vbo-orphans-roomname">'+obj_res[rid]['name']+'</h4>';
						orphans_list += '	<div class="vbo-orphans-info-dates">';
						for (var dind in obj_res[rid]['rdates']) {
							if (!obj_res[rid]['rdates'].hasOwnProperty(dind)) {
								continue;
							}
							orphans_list += '	<div class="vbo-orphans-info-date">'+obj_res[rid]['rdates'][dind]+'</div>';
						}
						orphans_list += '	</div>';
						orphans_list += '	<div class="vbo-orphans-info-btn">';
						orphans_list += '		<a href="index.php?option=com_vikbooking&task=ratesoverv&cid[]='+rid+'&startdate='+obj_res[rid]['linkd']+'" class="btn btn-primary" target="_blank"><?php echo addslashes(JText::translate('VBORPHANSCHECKBTN')); ?></a>';
						orphans_list += '	</div>';
						orphans_list += '</div>';
					}
					// populate content
					if (orphans_list.length) {
						jQuery('.vbo-orphans-info-list').html(orphans_list);
					}
					jQuery('.vbo-admin-widget-wrapper-orphandates').show();
				}).fail(function() {
					console.log("orphanscount Request Failed");
				});
			}
			//
		});
		</script>
		<?php
	}
}
