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

// get admin widgets helper
$widgets_helper = VikBooking::getAdminWidgetsInstance();
$widgets_map = $widgets_helper->getWidgetsMap();

// get all widgets by preloading their assets (if any)
$widgets_names = $widgets_helper->getWidgetNames($preload = true);

$widgets_welcome = $widgets_helper->showWelcome();

// global permissions are necessary to customize the admin widgets
$vbo_auth_global = JFactory::getUser()->authorise('core.vbo.global', 'com_vikbooking');

// check if the notification audio file exists within VCM
$notif_audio_path = implode(DIRECTORY_SEPARATOR, [VCM_ADMIN_PATH, 'assets', 'css', 'audio', 'new_notification.mp3']);
$notif_audio_url  = is_file($notif_audio_path) ? (VCM_ADMIN_URI . implode('/', ['assets', 'css', 'audio', 'new_notification.mp3'])) : null;

// theme color preferences
$color_scheme = VikBooking::getAppearancePref();
$scheme_name  = JText::translate('VBO_APPEARANCE_PREF_AUTO');
$current_mode = 'magic';
if ($color_scheme == 'light') {
	$scheme_name = JText::translate('VBO_APPEARANCE_PREF_LIGHT');
	$current_mode = 'sun';
} elseif ($color_scheme == 'dark') {
	$scheme_name = JText::translate('VBO_APPEARANCE_PREF_DARK');
	$current_mode = 'moon';
}

// load sortable library
JHtml::fetch('jquery.framework', true, true);
JHtml::fetch('script', VBO_SITE_URI.'resources/jquery-ui.sortable.min.js');

// load language vars for JavaScript
JText::script('VBO_WIDGETS_WELCOME');
JText::script('VBO_WIDGETS_ADDWIDGCONT');
JText::script('VBO_WIDGETS_RESTDEFAULT');
JText::script('VBO_WIDGETS_ADDNEWWIDG');
JText::script('VBO_WIDGETS_SAVINGMAP');
JText::script('VBO_WIDGETS_ERRSAVINGMAP');
JText::script('VBO_WIDGETS_LASTUPD');
JText::script('VBO_WIDGETS_ENTERSECTNAME');
JText::script('VBO_WIDGETS_NEWSECT');
JText::script('VBO_WIDGETS_CONFRMELEM');
JText::script('VBO_WIDGETS_SELCONTSIZE');
JText::script('VBO_WIDGETS_UPDWIDGCONT');
JText::script('VBO_WIDGETS_EDITWIDGCONT');
JText::script('VBO_WIDGETS_ERRDISPWIDG');
JText::script('VBO_STICKYN_TITLE');
JText::script('VBO_STICKYN_TEXT');
JText::script('VBO_STICKYN_TEXT2');
JText::script('VBO_STICKYN_CUSTOMURI');
JText::script('VBO_BROWSER_NOTIFS_ON');
JText::script('VBO_BROWSER_NOTIFS_OFF');
JText::script('VBO_BROWSER_NOTIFS_OFF_HELP');
JText::script('VBO_ADMIN_WIDGET');
JText::script('VBO_CONGRATS');
JText::script('VBO_APPEARANCE_PREF_AUTO');
JText::script('VBO_APPEARANCE_PREF_LIGHT');
JText::script('VBO_APPEARANCE_PREF_DARK');

/**
 * Monitor request vars to see if a widget should be loaded within a modal.
 * Only the dashboard at the moment allows to render an admin widget within
 * a modal through query string values, because all other pages will render
 * the multitask panel that can quickly render any admin widget.
 * 
 * @since 	1.16.0 (J) - 1.6.0 (WP)
 * @since 	1.16.5 (J) - 1.6.5 (WP) added payload support for clicked Push notifications.
 */
$app = JFactory::getApplication();

$load_widget   	   = $app->input->get('load_widget', '');
$multitask_data	   = $app->input->get('multitask_data', [], 'array');
$multitask_options = [];
$push_notification = $app->input->get('push_notification', '', 'base64');
if (!$load_widget && $push_notification) {
	// parse the payload to detect the proper type of widget to render
	list($load_widget, $multitask_data, $multitask_options) = VBOMultitaskParser::queryPushData($multitask_data, base64_decode($push_notification));
}

// process widget data, if any
if (!empty($load_widget) && $desired_widget = $widgets_helper->getWidget($load_widget)) {
	$multitask_data['_modalTitle'] = $desired_widget->getName();
	$data_payload 	 = json_encode($multitask_data);
	$options_payload = json_encode($multitask_options);

	// append script to DOM to render the widget within a modal
	JFactory::getDocument()->addScriptDeclaration(
<<<JS
(function($) {
	'use strict';

	$(function() {
		setTimeout(() => {
			VBOCore.renderModalWidget('$load_widget', $data_payload, $options_payload, false);
		}, 500);
	});
})(jQuery);
JS
	);
}

?>
<div class="vbo-admin-widgets-wrap">
<?php
if ($vbo_auth_global) {
?>
	<div class="vbo-admin-widgets-commands">
		<div class="vbo-admin-widgets-commands-info" data-vbomanagewidgets="1" style="display: none;">
			<div class="vbo-admin-widgets-commands-info-inner">
				<span class="vbo-admin-widgets-commands-info-txt"><?php echo JText::translate('VBO_WIDGETS_AUTOSAVE'); ?></span>
				<div class="vbo-admin-widgets-commands-info-restore">
					<a href="index.php?option=com_vikbooking&task=reset_admin_widgets" class="btn btn-secondary" onclick="return vboWidgetsRestoreMap();"><?php echo JText::translate('VBO_WIDGETS_RESTDEFAULTSHORT'); ?></a>
				</div>
				<div class="vbo-sidepanel-colorscheme vbo-dashboard-colorscheme">
					<span class="vbo-tooltip vbo-tooltip-top vbo-sidepanel-colorscheme-current" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $scheme_name); ?>"><?php VikBookingIcons::e($current_mode); ?></span>
					<div class="vbo-sidepanel-colorscheme-list">
						<div class="vbo-sidepanel-colorscheme-option<?php echo $color_scheme == 'auto' ? ' vbo-sidepanel-colorscheme-option-active' : ''; ?>" data-scheme="auto">
							<span><?php VikBookingIcons::e('magic'); ?> <?php echo JText::translate('VBO_APPEARANCE_PREF_AUTO'); ?></span>
						</div>
						<div class="vbo-sidepanel-colorscheme-option<?php echo $color_scheme == 'light' ? ' vbo-sidepanel-colorscheme-option-active' : ''; ?>" data-scheme="light">
							<span><?php VikBookingIcons::e('sun'); ?> <?php echo JText::translate('VBO_APPEARANCE_PREF_LIGHT'); ?></span>
						</div>
						<div class="vbo-sidepanel-colorscheme-option<?php echo $color_scheme == 'dark' ? ' vbo-sidepanel-colorscheme-option-active' : ''; ?>" data-scheme="dark">
							<span><?php VikBookingIcons::e('moon'); ?> <?php echo JText::translate('VBO_APPEARANCE_PREF_DARK'); ?></span>
						</div>
					</div>
				</div>
				<div class="vbo-admin-widgets-suggest-notifications-cont" style="display: none;">
					<span class="vbo-suggest-notifications-wrap">
						<button class="vbo-dash-suggest-notifications-btn vbo-suggest-notifications-btn" type="button"><?php VikBookingIcons::e('bell', 'can-shake') ?></button>
					</span>
				</div>
			</div>
		</div>
		<div class="vbo-admin-widgets-commands-mng">
			<span class="vbo-admin-widgets-commands-mng-toggle"><?php echo $vbo_app->printYesNoButtons('vbocustwidgets', JText::translate('VBYES'), JText::translate('VBNO'), 0, 1, 0, 'vboWidgetsToggleManage(false);'); ?></span>
			<span class="vbo-admin-widgets-commands-mng-lbl" onclick="vboWidgetsToggleManage(true);"><?php VikBookingIcons::e('cogs'); ?> <?php echo JText::translate('VBO_WIDGETS_CUSTWIDGETS'); ?></span>
		</div>
	</div>
<?php
}
?>
	<div class="vbo-admin-widgets-list">
	<?php
	foreach ($widgets_map->sections as $seck => $section) {
		?>
		<div class="vbo-admin-widgets-section">
			<div class="vbo-admin-widgets-section-name" data-vbomanagewidgets="1" style="display: none;">
				<span class="vbo-admin-widgets-elem-cmds-drag"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
				<span class="vbo-admin-widgets-section-name-val"><?php echo $section->name; ?></span>
				<div class="vbo-admin-widget-elem-cmds vbo-admin-widgets-section-cmds">
					<span class="vbo-admin-widgets-elem-cmds-edit" onclick="vboWidgetsEditSection(this);"><?php VikBookingIcons::e('edit'); ?></span>
					<span class="vbo-admin-widgets-elem-cmds-remove" onclick="vboWidgetsRemoveSection(this);"><?php VikBookingIcons::e('trash'); ?></span>
				</div>
			</div>
		<?php
		if (!isset($section->containers)) {
			$section->containers = array();
		}
		$tot_containers = count($section->containers);
		foreach ($section->containers as $conk => $container) {
			$container_css = $widgets_helper->getContainerCssClass($container->size);
			?>
			<div class="vbo-admin-widgets-container <?php echo $container_css; ?>" data-vbowidgetcontsize="<?php echo $container->size; ?>" data-totcontainers="<?php echo $tot_containers; ?>">
				<div class="vbo-admin-widgets-container-name" data-vbomanagewidgets="1" style="display: none;">
					<span class="vbo-admin-widgets-container-name-val"><?php echo $widgets_helper->getContainerName($container->size); ?></span>
					<div class="vbo-admin-widget-elem-cmds vbo-admin-widgets-container-cmds">
						<span class="vbo-admin-widgets-elem-cmds-edit" onclick="vboWidgetsEditContainer(this);"><?php VikBookingIcons::e('edit'); ?></span>
						<span class="vbo-admin-widgets-elem-cmds-remove" onclick="vboWidgetsRemoveContainer(this);"><?php VikBookingIcons::e('trash'); ?></span>
					</div>
				</div>
			<?php
			if (!isset($container->widgets)) {
				$container->widgets = array();
			}
			foreach ($container->widgets as $widk => $widget_id) {
				$widget_instance = $widgets_helper->getWidget($widget_id);
				if ($widget_instance === false) {
					continue;
				}
				?>
				<div class="vbo-admin-widgets-widget" data-vbowidgetid="<?php echo $widget_instance->getIdentifier(); ?>">
					<div class="vbo-admin-widgets-widget-info" data-vbomanagewidgets="1" style="display: none;">
						<div class="vbo-admin-widgets-widget-info-inner">
							<div class="vbo-admin-widgets-widget-details">
								<span class="vbo-admin-widgets-widget-info-drag"><?php VikBookingIcons::e('ellipsis-v'); ?></span>
								<h4 class="vbo-admin-widgets-widget-info-name">
									<span><?php echo $widget_instance->getName(); ?></span>
									<span class="vbo-admin-widgets-widget-remove" onclick="vboWidgetsRemoveWidget(this);"><?php VikBookingIcons::e('trash'); ?></span>
								</h4>
							</div>
							<div class="vbo-admin-widgets-widget-info-descr"><?php echo $widget_instance->getDescription(); ?></div>
						</div>
					</div>
					<div class="vbo-admin-widgets-widget-output">
						<?php $widget_instance->render(); ?>
					</div>
				</div>
				<?php
			}
			?>
				<div class="vbo-admin-widgets-widget vbo-admin-widgets-widget-addnew" data-vbomanagewidgets="1" style="display: none;">
					<div class="vbo-admin-widgets-plus-box" onclick="vboWidgetsAddWidget(this);">
						<span><?php VikBookingIcons::e('plus-circle'); ?></span>
					</div>
				</div>
			</div>
			<?php
		}
		?>
			<div class="vbo-admin-widgets-container vbo-admin-widgets-container-addnew" data-vbomanagewidgets="1" style="display: none;">
				<div class="vbo-admin-widgets-plus-box" onclick="vboWidgetsAddContainer(this);">
					<span><?php VikBookingIcons::e('plus-circle'); ?></span>
				</div>
			</div>
		</div>
		<?php
	}
	?>
		<div class="vbo-admin-widgets-section vbo-admin-widgets-section-addnew" data-vbomanagewidgets="1" style="display: none;">
			<div class="vbo-admin-widgets-plus-box" onclick="vboWidgetsAddSection();">
				<span><?php VikBookingIcons::e('plus-circle'); ?></span>
			</div>
		</div>
	</div>
</div>

<div class="vbo-modal-overlay-block vbo-modal-overlay-block-dashwidgets">
	<a class="vbo-modal-overlay-close" href="javascript: void(0);"></a>
	<div class="vbo-modal-overlay-content vbo-modal-overlay-content-dashwidgets">
		<div class="vbo-modal-overlay-content-head vbo-modal-overlay-content-head-dashwidgets">
			<h3><span id="vbo-modal-widgets-title"></span> <span class="vbo-modal-overlay-close-times" onclick="hideVboModalWidgets();">&times;</span></h3>
		</div>
		<div class="vbo-modal-overlay-content-body vbo-modal-overlay-content-body-scroll">
			<div class="vbo-modal-widgets-newcontainer vbo-modal-widgets-forms" style="display: none;">
				<div class="vbo-modal-widgets-form-data-fields">
					<div class="vbo-modal-widgets-form-data-field">
						<label for="vbo-newcontainer-size"><?php echo JText::translate('VBO_WIDGETS_CONTSIZE'); ?></label>
						<select id="vbo-newcontainer-size">
						<?php
						foreach ($widgets_helper->getContainerClassNames() as $class_key => $class_name_data) {
							?>
							<option value="<?php echo $class_key; ?>" data-cssclass="<?php echo $class_name_data['css']; ?>"><?php echo $class_name_data['name']; ?></option>
							<?php
						}
						?>
						</select>
						<input type="hidden" id="vbo-newcontainer-upd" value="0" />
					</div>
				</div>
			</div>
			<div class="vbo-modal-widgets-newwidget vbo-modal-widgets-forms" style="display: none;">
				<div class="vbo-modal-widgets-form-data-fields">
					<div class="vbo-modal-widgets-form-data-field">
						<label><?php echo JText::translate('VBO_WIDGETS_SELWIDGADD'); ?></label>
					</div>
					<div class="vbo-modal-widgets-form-data-field vbo-modal-widgets-list">
						<input type="hidden" id="vbo-newwidget-id" value="" />
					<?php
					foreach ($widgets_names as $widget_data) {
						?>
						<div class="vbo-modal-widget-wrap vbo-admin-widget-style-<?php echo $widget_data->style; ?>" data-vbowidgetid="<?php echo $widget_data->id; ?>" onclick="vboWidgetsSelectWidget('<?php echo $widget_data->id; ?>');">
							<div class="vbo-modal-widget-cont-top">
								<div class="vbo-modal-widget-icon">
									<span><?php echo $widget_data->icon; ?></span>
								</div>
								<div class="vbo-modal-widget-add">
									<span onclick="vboWidgetsAddWidgetToDoc('<?php echo $widget_data->id; ?>');"><?php VikBookingIcons::e('plus-circle'); ?></span>
								</div>
							</div>
							<div class="vbo-modal-widget-cont-main">
								<div class="vbo-modal-widget-name">
									<span><?php echo $widget_data->name; ?></span>
								</div>
								<div class="vbo-modal-widget-descr">
									<span><?php echo $widget_data->descr; ?></span>
								</div>
							</div>
						</div>
						<?php
					}
					?>
					</div>
					<div class="vbo-modal-widgets-form-data-field vbo-newwidget-descr" style="display: none;"></div>
				</div>
			</div>
		<?php
		if ($widgets_welcome) {
			?>
			<div class="vbo-widgets-welcome-wrap vbo-modal-widgets-forms" style="display: none;">
				<div class="vbo-widgets-welcome-inner">
					<p><?php echo JText::translate('VBO_WIDGETS_WELCOME_DESC1'); ?></p>
					<p><?php echo JText::translate('VBO_WIDGETS_WELCOME_DESC2'); ?></p>
					<div class="vbo-widgets-welcome-demo">
						<div class="vbo-widgets-welcome-demo-section">
							<span class="vbo-widgets-welcome-demo-section-lbl"><?php echo JText::translate('VBO_WIDGETS_NEWSECT'); ?></span>
							<div class="vbo-widgets-welcome-demo-container">
								<span class="vbo-widgets-welcome-demo-container-lbl"><?php echo JText::translate('VBO_WIDGETS_ADDWIDGCONT'); ?></span>
								<div class="vbo-widgets-welcome-demo-widget">
									<span class="vbo-widgets-welcome-demo-widget-lbl"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBO_WIDGETS_ADDNEWWIDG'); ?></span>
								</div>
							</div>
							<div class="vbo-widgets-welcome-demo-container">
								<span class="vbo-widgets-welcome-demo-container-lbl"><?php echo JText::translate('VBO_WIDGETS_ADDWIDGCONT'); ?></span>
								<div class="vbo-widgets-welcome-demo-widget">
									<span class="vbo-widgets-welcome-demo-widget-lbl"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBO_WIDGETS_ADDNEWWIDG'); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		?>
		</div>
		<div class="vbo-modal-overlay-content-footer">
			<div class="vbo-modal-footer-newcontainer vbo-modal-widgets-forms-footer" style="display: none;">
				<div class="vbo-modal-overlay-content-footer-right">
					<button type="button" class="btn btn-success" id="vbo-newcontainer-btn" onclick="vboWidgetsAddContainerToDoc();"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBO_WIDGETS_ADDWIDGCONT'); ?></button>
				</div>
			</div>
			<div class="vbo-modal-footer-newwidget vbo-modal-widgets-forms-footer" style="display: none;">
				<div class="vbo-modal-overlay-content-footer-right">
					<button type="button" class="btn btn-success" onclick="vboWidgetsAddWidgetToDoc();"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBO_WIDGETS_ADDNEWWIDG'); ?></button>
				</div>
			</div>
			<div class="vbo-modal-footer-welcome vbo-modal-widgets-forms-footer" style="display: none;">
				<div class="vbo-modal-overlay-content-footer-left">
					<button type="button" class="btn btn-secondary" onclick="vboWidgetsCloseWelcome(1);"><?php echo JText::translate('VBOBTNDONTREMIND'); ?></button>
				</div>
				<div class="vbo-modal-overlay-content-footer-right">
					<button type="button" class="btn btn-success" onclick="vboWidgetsCloseWelcome(0);"><?php echo JText::translate('VBOBTNKEEPREMIND'); ?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	/**
	 * Declare global scope variables.
	 */
	var vbo_admin_widgets_map = <?php echo json_encode($widgets_map); ?>;
	var vbo_admin_widgets_names = <?php echo json_encode($widgets_names); ?>;

	var vbo_admin_widgets_last_section = null,
		vbo_admin_widgets_last_container = null,
		vbo_admin_widgets_initpos_section = 0,
		vbo_admin_widgets_initpos_container = 0,
		vbo_admin_widgets_initpos_widget = 0,
		vbo_admin_widgets_initinst_widget = -1,
		vbo_admin_widgets_allow_drop = false,
		vbo_admin_widgets_sugg_notifs = false;

	var vbo_modal_widgets_on = false;

	var vbo_admin_widgets_welcome = <?php echo $widgets_welcome ? 'true' : 'false'; ?>;

	/**
	 * Shows the modal window
	 */
	function vboOpenModalWidgets() {
		jQuery('.vbo-modal-overlay-block-dashwidgets').show();
		vbo_modal_widgets_on = true;
	}

	/**
	 * Hides the modal window
	 */
	function hideVboModalWidgets() {
		if (vbo_modal_widgets_on === true) {
			jQuery(".vbo-modal-overlay-block-dashwidgets").fadeOut(400, function() {
				jQuery(".vbo-modal-overlay-content-dashwidgets").show();
				jQuery(".vbo-modal-widgets-forms").hide();
				jQuery(".vbo-modal-widgets-forms-footer").hide();
			});
			// turn flag off
			vbo_modal_widgets_on = false;
		}
	}

	/**
	 * Toggles the widget customizer mode
	 */
	function vboWidgetsToggleManage(trigger) {
		if (trigger === true) {
			jQuery('input[name="vbocustwidgets"]').trigger('click');
			return;
		}
		if (!jQuery('input[name="vbocustwidgets"]').is(':checked')) {
			jQuery('div[data-vbomanagewidgets="1"]').hide();
			jQuery('.vbo-admin-widgets-widget-output').show();
			jQuery('.vbo-admin-widgets-list').removeClass('vbo-admin-widgets-list-customize');
		} else {
			jQuery('.vbo-admin-widgets-widget-output').hide();
			jQuery('div[data-vbomanagewidgets="1"]').show();
			jQuery('.vbo-admin-widgets-list').addClass('vbo-admin-widgets-list-customize');
			// show welcome (if necessary)
			vboWidgetsShowWelcome();
			// handle notification suggestions
			if (!vbo_admin_widgets_sugg_notifs && VBOCore.notificationsEnabled() === false) {
				vbo_admin_widgets_sugg_notifs = true;
				jQuery('.vbo-admin-widgets-suggest-notifications-cont').show();
				VBOCore.suggestNotifications('.vbo-dash-suggest-notifications-btn');
			}
		}
	}

	/**
	 * Opens the modal window with the welcome message for the admin widgets customizer.
	 */
	function vboWidgetsShowWelcome() {
		if (!vbo_admin_widgets_welcome || !jQuery('.vbo-widgets-welcome-wrap').length) {
			return;
		}
		// prevent this from being displayed again in the same page flow
		vbo_admin_widgets_welcome = false;
		// display welcome container
		jQuery('.vbo-widgets-welcome-wrap').show();
		jQuery('.vbo-modal-footer-welcome').show();
		// set modal title
		jQuery('#vbo-modal-widgets-title').text(Joomla.JText._('VBO_WIDGETS_WELCOME'));
		// display modal
		vboOpenModalWidgets();
		// declare timeouts to add the animate class to the welcome elements
		setTimeout(() => {
			// animate container
			jQuery('.vbo-widgets-welcome-demo-section').addClass('vbo-widgets-welcome-animate');
		}, 1000);
		setTimeout(() => {
			// animate first section
			jQuery('.vbo-widgets-welcome-demo-container').first().addClass('vbo-widgets-welcome-animate');
		}, 2000);
		setTimeout(() => {
			// animate last section
			jQuery('.vbo-widgets-welcome-demo-container').last().addClass('vbo-widgets-welcome-animate');
		}, 3000);
		setTimeout(() => {
			// animate widgets
			jQuery('.vbo-widgets-welcome-demo-widget').addClass('vbo-widgets-welcome-animate');
		}, 4000);
	}

	/**
	 * Closes the modal window for the welcome text by storing an action.
	 */
	function vboWidgetsCloseWelcome(hidenext) {
		// dismiss modal
		hideVboModalWidgets();
		// AJAX request to update the welcome status
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=admin_widgets_welcome'); ?>",
			{
				hide_welcome: hidenext,
				tmpl: "component"
			},
			(response) => {
				try {
					var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
					if (!obj_res.hasOwnProperty('status')) {
						// request failed
						console.error('Could not update welcome status', obj_res);
					}
				} catch(err) {
					console.error('could not parse JSON response when updating the welcome status', err, response);
				}
			},
			(error) => {
				console.error(error);
			}
		);
	}

	/**
	 * This will fire during the throttle of the save-map event.
	 * Saves the updated admin widgets map onto the database.
	 */
	function vboHandleMapSaving() {
		// update info status to "saving..."
		jQuery('.vbo-admin-widgets-commands-info-txt').removeClass('vbo-admin-widgets-error').html('<?php VikBookingIcons::e('refresh', 'fa-spin fa-fw'); ?> ' + Joomla.JText._('VBO_WIDGETS_SAVINGMAP'));

		// prepare AJAX request data
		var saving_request = {
			tmpl: "component"
		}
		Object.assign(saving_request, vbo_admin_widgets_map);

		// make the request
		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=save_admin_widgets'); ?>",
			saving_request,
			(response) => {
				try {
					var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
					if (!obj_res.status) {
						// request failed
						console.error('Could not update the map', obj_res);
						// update info status to "error..."
						jQuery('.vbo-admin-widgets-commands-info-txt').addClass('vbo-admin-widgets-error').text(Joomla.JText._('VBO_WIDGETS_ERRSAVINGMAP'));
					} else {
						// set last updated time
						var now = new Date;
						var hours = now.getHours();
						hours = hours < 10 ? '0' + hours : hours;
						var minutes = now.getMinutes();
						minutes = minutes < 10 ? '0' + minutes : minutes;
						var seconds = now.getSeconds();
						seconds = seconds < 10 ? '0' + seconds : seconds;
						var full_time_now = hours + ':' + minutes + ':' + seconds;
						// update info status to "last update time"
						jQuery('.vbo-admin-widgets-commands-info-txt').removeClass('vbo-admin-widgets-error').html('<?php VikBookingIcons::e('check-circle'); ?> ' + Joomla.JText._('VBO_WIDGETS_LASTUPD') + ': ' + full_time_now);
					}
				} catch(err) {
					console.error('could not parse JSON response when updating the map', err, response);
					// update info status to "error..."
					jQuery('.vbo-admin-widgets-commands-info-txt').addClass('vbo-admin-widgets-error').text(Joomla.JText._('VBO_WIDGETS_ERRSAVINGMAP'));
				}
			},
			(error) => {
				console.error(error.responseText);
				// update info status to "error..."
				jQuery('.vbo-admin-widgets-commands-info-txt').addClass('vbo-admin-widgets-error').text(Joomla.JText._('VBO_WIDGETS_ERRSAVINGMAP'));
			}
		);
	}

	/**
	 * Makes all sections sortable. Do not use .disableSelection() or this
	 * will break all [contenteditable] elements and their focus/selection events.
	 */
	function vboMakeSectionsSortable() {
		jQuery('.vbo-admin-widgets-list').sortable({
			axix: 'x',
			cursor: 'move',
			handle: '.vbo-admin-widgets-section-name .vbo-admin-widgets-elem-cmds-drag',
			items: '.vbo-admin-widgets-section:not(.vbo-admin-widgets-section-addnew)',
			revert: false,
			start: function(event, ui) {
				// update global initial position for section
				vbo_admin_widgets_initpos_section = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(jQuery(ui.item));
			},
			update: function(event, ui) {
				var new_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(jQuery(ui.item));
				// update global map object - move originial section to new position
				vbo_admin_widgets_map.sections.splice(new_sect_index, 0, vbo_admin_widgets_map.sections.splice(vbo_admin_widgets_initpos_section, 1)[0]);

				// trigger the save-map event
				document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));
			}
		});
	}

	/**
	 * Makes all widgets sortable. Do not use .disableSelection() or this
	 * will break all [contenteditable] elements and their focus/selection events.
	 */
	function vboMakeWidgetsSortable() {
		jQuery('.vbo-admin-widgets-container').not('.vbo-admin-widgets-container-addnew').sortable({
			connectWith: '.vbo-admin-widgets-container:not(.vbo-admin-widgets-container-addnew)',
			cursor: 'move',
			dropOnEmpty: true,
			handle: '.vbo-admin-widgets-widget-details .vbo-admin-widgets-widget-info-drag',
			helper: 'clone',
			items: '.vbo-admin-widgets-widget:not(.vbo-admin-widgets-widget-addnew)',
			placeholder: 'vbo-admin-widgets-container-tmpdrop',
			revert: false,
			start: function(event, ui) {
				// allow drop
				vbo_admin_widgets_allow_drop = true;
				// calculate initial positions
				var initial_widget = jQuery(ui.item);
				var initial_section = initial_widget.closest('.vbo-admin-widgets-section');
				var initial_container = initial_widget.closest('.vbo-admin-widgets-container');
				// update global initial position for section
				vbo_admin_widgets_initpos_section = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(initial_section);
				// update global initial position for container
				vbo_admin_widgets_initpos_container = initial_section.find('.vbo-admin-widgets-container').not('.vbo-admin-widgets-container-addnew').index(initial_container);
				// update global initial position for widget
				vbo_admin_widgets_initpos_widget = initial_container.find('.vbo-admin-widgets-widget').not('.vbo-admin-widgets-widget-addnew').index(initial_widget);
				// update global initial instance index for this type of widget
				var widget_type = initial_widget.attr('data-vbowidgetid');
				vbo_admin_widgets_initinst_widget = jQuery('.vbo-admin-widgets-widget[data-vbowidgetid="' + widget_type + '"]').index(initial_widget);
			},
			update: function(event, ui) {
				var dropped_widget = jQuery(ui.item);
				var dropped_section = dropped_widget.closest('.vbo-admin-widgets-section');
				var dropped_container = dropped_widget.closest('.vbo-admin-widgets-container');
				// calculate new element positions
				var new_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(dropped_section);
				var new_cont_index = dropped_section.find('.vbo-admin-widgets-container').not('.vbo-admin-widgets-container-addnew').index(dropped_container);
				var new_widg_index = dropped_container.find('.vbo-admin-widgets-widget').not('.vbo-admin-widgets-widget-addnew').index(dropped_widget);

				if (new_sect_index != vbo_admin_widgets_initpos_section || new_cont_index != vbo_admin_widgets_initpos_container) {
					/**
					 * Widget has been moved to a connected list, to a different section or container.
					 * Multiple "update" events will be fired, one for each target, so 2 in total.
					 */
					if (vbo_admin_widgets_allow_drop !== true) {
						// both events contain the same dropped target information (ui), so we skip any later event
						return;
					}

					// disable drop for any sub-sequent event
					vbo_admin_widgets_allow_drop = false;

					// update global map object - remove original widget
					vbo_admin_widgets_map.sections[vbo_admin_widgets_initpos_section]['containers'][vbo_admin_widgets_initpos_container]['widgets'].splice(vbo_admin_widgets_initpos_widget, 1);

					// update global map object - push new widget
					vbo_admin_widgets_map.sections[new_sect_index]['containers'][new_cont_index]['widgets'].splice(new_widg_index, 0, dropped_widget.attr('data-vbowidgetid'));
				} else {
					/**
					 * Widget has been sorted from the same section and container list.
					 * Only one "update" event will be fired.
					 */

					// update global map object - move original widget to new position
					vbo_admin_widgets_map.sections[vbo_admin_widgets_initpos_section]['containers'][vbo_admin_widgets_initpos_container]['widgets'].splice(
						new_widg_index, 
						0, 
						vbo_admin_widgets_map.sections[vbo_admin_widgets_initpos_section]['containers'][vbo_admin_widgets_initpos_container]['widgets'].splice(vbo_admin_widgets_initpos_widget, 1)[0]
					);
				}

				// calculate new instance index for this type of widget
				var widget_type = dropped_widget.attr('data-vbowidgetid');
				var new_instance_index = jQuery('.vbo-admin-widgets-widget[data-vbowidgetid="' + widget_type + '"]').index(dropped_widget);
				if (vbo_admin_widgets_initinst_widget >= 0 && new_instance_index >= 0 && vbo_admin_widgets_initinst_widget != new_instance_index) {
					// widget instance index has changed, and since we have multiple instances of this widget, we may need to update its settings
					
					// the widget method to call
					var call_method = 'sortInstance';
					// make a silent call for the widget in case it needs to perform actions when removing an instance
					VBOCore.doAjax(
						"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_admin_widget'); ?>",
						{
							widget_id: widget_type,
							widget_index_old: vbo_admin_widgets_initinst_widget,
							widget_index_new: new_instance_index,
							call: call_method,
							tmpl: "component"
						},
						(response) => {
							try {
								var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
								if (!obj_res.hasOwnProperty(call_method)) {
									console.error('Unexpected JSON response', obj_res);
								}
							} catch(err) {
								console.error('could not parse JSON response', err, response);
							}
						},
						(error) => {
							console.error(error.responseText);
						}
					);
				}

				// trigger the save-map event
				document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));

			}
		});
	}

	/**
	 * Declares document ready event processes.
	 */
	jQuery(function() {
		
		/**
		 * Dismiss modal window with Esc.
		 */
		jQuery(document).keydown(function(e) {
			if (e.keyCode == 27) {
				if (vbo_modal_widgets_on === true) {
					hideVboModalWidgets();
				}
			}
		});

		/**
		 * Dismiss modal window by clicking on an external element.
		 */
		jQuery(document).mouseup(function(e) {
			if (!vbo_modal_widgets_on) {
				return false;
			}
			if (vbo_modal_widgets_on) {
				var vbo_overlay_cont = jQuery(".vbo-modal-overlay-content-dashwidgets");
				if (!vbo_overlay_cont.is(e.target) && vbo_overlay_cont.has(e.target).length === 0) {
					hideVboModalWidgets();
				}
			}
		});

		/**
		 * Make all current sections and widgets sortable.
		 */
		vboMakeSectionsSortable();
		vboMakeWidgetsSortable();

		/**
		 * Add event listener to the save-map event with debounce handler.
		 */
		document.addEventListener('vbo-admin-widgets-savemap', VBOCore.debounceEvent(vboHandleMapSaving, 2000));

		/**
		 * Setup browser notifications and admin widgets core features.
		 */
		VBOCore.setOptions({
			is_vbo: 			true,
			cms: 				"<?php echo VBOPlatformDetection::isWordPress() ? 'wordpress' : 'joomla'; ?>",
			widget_ajax_uri:    "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_admin_widget'); ?>",
			assets_ajax_uri: 	"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=widgets_get_assets'); ?>",
			multitask_ajax_uri: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_multitask_widgets'); ?>",
			watchdata_ajax_uri: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=widgets_watch_data'); ?>",
			current_page: 	    "dashboard",
			current_page_uri:   "<?php echo htmlspecialchars((string) JUri::getInstance(), ENT_QUOTES); ?>",
			root_uri:     		"<?php echo htmlspecialchars(JUri::root(), ENT_QUOTES); ?>",
			panel_opts: 		{
				notif_on_class:  "vbo-sidepanel-notifications-on",
				notif_off_class: "vbo-sidepanel-notifications-off",
			},
			notif_audio_url: 	"<?php echo $notif_audio_url; ?>",
			tn_texts: 			{
				notifs_enabled: 		Joomla.JText._('VBO_BROWSER_NOTIFS_ON'),
				notifs_disabled: 		Joomla.JText._('VBO_BROWSER_NOTIFS_OFF'),
				notifs_disabled_help: 	Joomla.JText._('VBO_BROWSER_NOTIFS_OFF_HELP'),
				admin_widget: 			Joomla.JText._('VBO_ADMIN_WIDGET'),
				congrats: 				Joomla.JText._('VBO_CONGRATS'),
			},
			default_loading_body: '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>',
			service_worker_path:  '<?php echo VBOWebappServiceworker::getUri(); ?>',
			service_worker_scope: '<?php echo VBOWebappServiceworker::getScope(); ?>',
			push: 				  <?php echo json_encode(VBOWebappPush::getConfig()); ?>,
		}).setupNotifications();

		/**
		 * Install Service Worker
		 */
		VBOCore.installServiceWorker().then((registration) => {
			VBOCore.handlePushSubscription(registration).then((subscription) => {
				console.info('Push notifications are enabled');
			}).catch((error) => {
				console.warn(error);
			});
		}).catch((error) => {
			console.warn(error);
		});

		// color scheme preferences
		jQuery('.vbo-sidepanel-colorscheme-current').on('click', function() {
			jQuery('.vbo-sidepanel-colorscheme-list').toggleClass('vbo-sidepanel-colorscheme-list-show');
		});

		// color scheme selection
		jQuery('.vbo-sidepanel-colorscheme-option').on('click', function() {
			let set_mode = jQuery(this).attr('data-scheme');

			let vbo_css_base_uri = '<?php echo VBO_ADMIN_URI . (VBOPlatformDetection::isWordPress() ? 'resources/' : '') . 'vbo-appearance-%s.css'; ?>';
			let vbo_css_base_id  = 'vbo-css-appearance-';
			let vbo_css_modes 	 = {
				auto: vbo_css_base_uri.replace('%s', 'auto'),
				dark: vbo_css_base_uri.replace('%s', 'dark'),
				light: null,
			};
			let vbo_mode_texts = {
				auto: Joomla.JText._('VBO_APPEARANCE_PREF_AUTO'),
				dark: Joomla.JText._('VBO_APPEARANCE_PREF_DARK'),
				light: Joomla.JText._('VBO_APPEARANCE_PREF_LIGHT'),
			};
			let vbo_mode_icons = {
				auto: '<?php VikBookingIcons::e('magic') ?>',
				dark: '<?php VikBookingIcons::e('moon') ?>',
				light: '<?php VikBookingIcons::e('sun') ?>',
			};

			if (!vbo_css_modes.hasOwnProperty(set_mode)) {
				return false;
			}

			// toggle active class
			jQuery('.vbo-sidepanel-colorscheme-option').removeClass('vbo-sidepanel-colorscheme-option-active');
			jQuery(this).addClass('vbo-sidepanel-colorscheme-option-active');

			// adjust current preference content
			jQuery('.vbo-sidepanel-colorscheme-current')
				.attr('data-tooltiptext', vbo_mode_texts[set_mode])
				.html(vbo_mode_icons[set_mode]);

			// set/unset CSS files from DOM
			for (let app_mode in vbo_css_modes) {
				if (!vbo_css_modes.hasOwnProperty(app_mode) || !vbo_css_modes[app_mode]) {
					continue;
				}
				if (app_mode == set_mode) {
					// set this CSS file
					jQuery('head').append('<link rel="stylesheet" id="' + vbo_css_base_id + app_mode + '" href="' + vbo_css_modes[app_mode] + '" media="all">');
				} else {
					// unset this CSS file
					if (jQuery('link#' + vbo_css_base_id + app_mode).length) {
						jQuery('link#' + vbo_css_base_id + app_mode).remove();
					} else if (jQuery('link#' + vbo_css_base_id + app_mode + '-css').length) {
						// WP framework may add "-css" as suffix to the given ID
						jQuery('link#' + vbo_css_base_id + app_mode + '-css').remove();
					}
				}
			}

			// close menu-list
			jQuery('.vbo-sidepanel-colorscheme-list').removeClass('vbo-sidepanel-colorscheme-list-show');

			// silently update configuration value
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=configuration.update'); ?>",
				{
					settings: {
						appearance_pref: set_mode,
					}
				},
				(success) => {
					// do nothing
				},
				(error) => {
					console.error(error);
				}
			);
		});

	});

	/**
	 * Asks for confirmation to restore the default widgets map
	 */
	function vboWidgetsRestoreMap() {
		if (confirm(Joomla.JText._('VBO_WIDGETS_RESTDEFAULT'))) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Adds a new section to the document
	 */
	function vboWidgetsAddSection() {
		var tot_sections = jQuery('.vbo-admin-widgets-section').length;
		var sect_name_new = prompt(Joomla.JText._('VBO_WIDGETS_ENTERSECTNAME'), Joomla.JText._('VBO_WIDGETS_NEWSECT') + ' #' + (tot_sections + 1));
		if (sect_name_new != null && sect_name_new != '') {
			var html_section_new = '<div class="vbo-admin-widgets-section" id="vbo-admin-widgets-section-' + (tot_sections + 1) + '">';
			html_section_new += '	<div class="vbo-admin-widgets-section-name" data-vbomanagewidgets="1">';
			html_section_new += '		<span class="vbo-admin-widgets-elem-cmds-drag"><?php VikBookingIcons::e('ellipsis-v'); ?></span>';
			html_section_new += '		<span class="vbo-admin-widgets-section-name-val">' + sect_name_new + '</span>';
			html_section_new += '		<div class="vbo-admin-widget-elem-cmds vbo-admin-widgets-section-cmds">';
			html_section_new += '			<span class="vbo-admin-widgets-elem-cmds-edit" onclick="vboWidgetsEditSection(this);"><?php VikBookingIcons::e('edit'); ?></span>';
			html_section_new += '			<span class="vbo-admin-widgets-elem-cmds-remove" onclick="vboWidgetsRemoveSection(this);"><?php VikBookingIcons::e('trash'); ?></span>';
			html_section_new += '		</div>';
			html_section_new += '	</div>';
			html_section_new += '	<div class="vbo-admin-widgets-container vbo-admin-widgets-container-addnew" data-vbomanagewidgets="1">';
			html_section_new += '		<div class="vbo-admin-widgets-plus-box" onclick="vboWidgetsAddContainer(this);">';
			html_section_new += '			<span><?php VikBookingIcons::e('plus-circle'); ?></span>';
			html_section_new += '		</div>';
			html_section_new += '	</div>';
			html_section_new += '</div>';
			jQuery('.vbo-admin-widgets-section-addnew').before(html_section_new);

			// update global map object
			vbo_admin_widgets_map.sections.push({
				name: sect_name_new,
				containers: []
			});

			// trigger the save-map event
			document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));
		}
	}

	/**
	 * Prompts the new section name to update it
	 */
	function vboWidgetsEditSection(elem) {
		var cur_sect_name_elm = jQuery(elem).closest('.vbo-admin-widgets-section-name').find('.vbo-admin-widgets-section-name-val');
		if (!cur_sect_name_elm || !cur_sect_name_elm.length) {
			console.error('Could not find section to edit');
			return false;
		}
		var cur_sect_name_val = cur_sect_name_elm.text();
		// find current index of selected section in the map
		var cur_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(jQuery(elem).closest('.vbo-admin-widgets-section'));
		var sect_name_new = prompt(Joomla.JText._('VBO_WIDGETS_ENTERSECTNAME'), cur_sect_name_val);
		if (sect_name_new != null && sect_name_new != '') {
			cur_sect_name_elm.text(sect_name_new);
			// update global map object
			vbo_admin_widgets_map.sections[cur_sect_index]['name'] = sect_name_new;
			// trigger the save-map event
			document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));
		}
	}

	/**
	 * Asks for confirmation before removing the selected section
	 */
	function vboWidgetsRemoveSection(elem) {
		var cur_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(jQuery(elem).closest('.vbo-admin-widgets-section'));
		if (confirm(Joomla.JText._('VBO_WIDGETS_CONFRMELEM'))) {
			// remove section from document
			jQuery(elem).closest('.vbo-admin-widgets-section').remove();
			// update global map object
			vbo_admin_widgets_map.sections.splice(cur_sect_index, 1);
			// trigger the save-map event
			document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));
		}
	}

	/**
	 * Displays the modal window to create a new widgets container
	 */
	function vboWidgetsAddContainer(elem) {
		// update last section selected
		vbo_admin_widgets_last_section = jQuery(elem).closest('.vbo-admin-widgets-section');
		if (!vbo_admin_widgets_last_section || !vbo_admin_widgets_last_section.length) {
			// parent section not found
			console.error('parent section not found for adding a container');
			return false;
		}
		// turn update flag off
		jQuery('#vbo-newcontainer-upd').val(0);
		// display new container form
		jQuery('.vbo-modal-widgets-newcontainer').show();
		jQuery('.vbo-modal-footer-newcontainer').show();
		// set new container form button
		jQuery('#vbo-newcontainer-btn').html('<?php VikBookingIcons::e('plus-circle'); ?> ' + Joomla.JText._('VBO_WIDGETS_ADDWIDGCONT'));
		// set modal title
		jQuery('#vbo-modal-widgets-title').text(Joomla.JText._('VBO_WIDGETS_ADDWIDGCONT'));
		// display modal
		vboOpenModalWidgets();
	}

	/**
	 * Adds or updates one widgets container in the document.
	 */
	function vboWidgetsAddContainerToDoc() {
		var mode = jQuery('#vbo-newcontainer-upd').val() > 0 ? 'update' : 'new';
		if (mode == 'new' && (!vbo_admin_widgets_last_section || !vbo_admin_widgets_last_section.length)) {
			// parent section not found
			console.error('parent section not found for adding a container');
			return false;
		}
		if (mode == 'update' && (!vbo_admin_widgets_last_container || !vbo_admin_widgets_last_container.length)) {
			// current container not found
			console.error('current container not found');
			return false;
		}

		// get new container size and CSS class
		var cont_size = jQuery('#vbo-newcontainer-size').val();
		var cont_css = jQuery('#vbo-newcontainer-size').find('option:selected').attr('data-cssclass');
		var cont_name = jQuery('#vbo-newcontainer-size').find('option:selected').text();
		if (!cont_size || !cont_size.length || !cont_css.length) {
			console.error('new container size missing');
			alert(Joomla.JText._('VBO_WIDGETS_SELCONTSIZE'));
			return false;
		}

		if (mode == 'update') {
			// update container class, size and title
			vbo_admin_widgets_last_container.removeClass().addClass('vbo-admin-widgets-container ' + cont_css).attr('data-vbowidgetcontsize', cont_size).find('.vbo-admin-widgets-container-name-val').text(cont_name);

			// update global map object
			var cur_cont_index = vbo_admin_widgets_last_container.closest('.vbo-admin-widgets-section').find('.vbo-admin-widgets-container').not('.vbo-admin-widgets-container-addnew').index(vbo_admin_widgets_last_container);
			var cur_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(vbo_admin_widgets_last_container.closest('.vbo-admin-widgets-section'));
			vbo_admin_widgets_map.sections[cur_sect_index]['containers'][cur_cont_index]['size'] = cont_size;

			// trigger the save-map event
			document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));
		} else {
			// update containers count for a better styling
			var all_sect_conts = vbo_admin_widgets_last_section.find('.vbo-admin-widgets-container').not('.vbo-admin-widgets-container-addnew');
			var new_sect_conts = all_sect_conts.length + 1;
			all_sect_conts.attr('data-totcontainers', new_sect_conts);

			// build new container
			var html_container_new = '<div class="vbo-admin-widgets-container ' + cont_css + '" data-vbowidgetcontsize="' + cont_size + '" data-totcontainers="' + new_sect_conts + '">';
			html_container_new += '		<div class="vbo-admin-widgets-container-name" data-vbomanagewidgets="1">';
			html_container_new += '			<span class="vbo-admin-widgets-container-name-val">' + cont_name + '</span>';
			html_container_new += '			<div class="vbo-admin-widget-elem-cmds vbo-admin-widgets-container-cmds">';
			html_container_new += '				<span class="vbo-admin-widgets-elem-cmds-edit" onclick="vboWidgetsEditContainer(this);"><?php VikBookingIcons::e('edit'); ?></span>';
			html_container_new += '				<span class="vbo-admin-widgets-elem-cmds-remove" onclick="vboWidgetsRemoveContainer(this);"><?php VikBookingIcons::e('trash'); ?></span>';
			html_container_new += '			</div>';
			html_container_new += '		</div>';
			html_container_new += '		<div class="vbo-admin-widgets-widget vbo-admin-widgets-widget-addnew" data-vbomanagewidgets="1">';
			html_container_new += '			<div class="vbo-admin-widgets-plus-box" onclick="vboWidgetsAddWidget(this);">';
			html_container_new += '				<span><?php VikBookingIcons::e('plus-circle'); ?></span>';
			html_container_new += '			</div>';
			html_container_new += '		</div>';
			html_container_new += '</div>';

			// append new container HTML
			vbo_admin_widgets_last_section.find('.vbo-admin-widgets-container-addnew').before(html_container_new);

			// update global map object
			var cur_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(vbo_admin_widgets_last_section);
			vbo_admin_widgets_map.sections[cur_sect_index]['containers'].push({
				size: cont_size,
				widgets: []
			});

			// trigger the save-map event
			document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));
		}

		// close modal window
		hideVboModalWidgets();

		// unset last section and container
		vbo_admin_widgets_last_section = null;
		vbo_admin_widgets_last_container = null;
	}

	/**
	 * Displays the modal window for editing the selected container
	 */
	function vboWidgetsEditContainer(elem) {
		// update last container selected
		vbo_admin_widgets_last_container = jQuery(elem).closest('.vbo-admin-widgets-container');
		if (!vbo_admin_widgets_last_container || !vbo_admin_widgets_last_container.length) {
			// parent container not found
			console.error('parent container not found');
			return false;
		}
		// turn update flag on
		jQuery('#vbo-newcontainer-upd').val(1);
		// set current container size
		jQuery('#vbo-newcontainer-size').val(vbo_admin_widgets_last_container.attr('data-vbowidgetcontsize')).trigger('change');
		// display edit container form
		jQuery('.vbo-modal-widgets-newcontainer').show();
		jQuery('.vbo-modal-footer-newcontainer').show();
		// set edit container form button
		jQuery('#vbo-newcontainer-btn').html('<?php VikBookingIcons::e('check'); ?> ' + Joomla.JText._('VBO_WIDGETS_UPDWIDGCONT'));
		// set modal title
		jQuery('#vbo-modal-widgets-title').text(Joomla.JText._('VBO_WIDGETS_EDITWIDGCONT'));
		// display modal
		vboOpenModalWidgets();
	}

	/**
	 * Asks for confirmation before removing the selected container
	 */
	function vboWidgetsRemoveContainer(elem) {
		var all_sect_conts = jQuery(elem).closest('.vbo-admin-widgets-section').find('.vbo-admin-widgets-container').not('.vbo-admin-widgets-container-addnew');
		var new_sect_conts = (all_sect_conts.length - 1);
		var cur_cont_index = all_sect_conts.index(jQuery(elem).closest('.vbo-admin-widgets-container'));
		var cur_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(jQuery(elem).closest('.vbo-admin-widgets-section'));
		if (confirm(Joomla.JText._('VBO_WIDGETS_CONFRMELEM'))) {
			// update containers count for a better styling
			all_sect_conts.attr('data-totcontainers', new_sect_conts);
			// remove container from document
			jQuery(elem).closest('.vbo-admin-widgets-container').remove();
			// update global map object
			vbo_admin_widgets_map.sections[cur_sect_index]['containers'].splice(cur_cont_index, 1);
			// trigger the save-map event
			document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));
		}
	}

	/**
	 * Displays the modal window to create a new widget
	 */
	function vboWidgetsAddWidget(elem) {
		// update last container selected
		vbo_admin_widgets_last_container = jQuery(elem).closest('.vbo-admin-widgets-container');
		if (!vbo_admin_widgets_last_container || !vbo_admin_widgets_last_container.length) {
			// parent container not found
			console.error('parent container not found for adding a widget');
			return false;
		}
		// display new widget form
		jQuery('.vbo-modal-widgets-newwidget').show();
		jQuery('.vbo-modal-footer-newwidget').show();
		// unset any previously selected widget
		jQuery('#vbo-newwidget-id').val('');
		jQuery('.vbo-modal-widget-wrap').removeClass('vbo-modal-widget-selected vbo-modal-widget-notselected');
		// set modal title
		jQuery('#vbo-modal-widgets-title').text(Joomla.JText._('VBO_WIDGETS_ADDNEWWIDG'));
		// display modal
		vboOpenModalWidgets();
	}

	/**
	 * Updates the description in the modal window for the selected widget
	 */
	function vboWidgetSetNewDescr(widget_id) {
		// always empty the description box
		jQuery('.vbo-newwidget-descr').html('');
		if (!widget_id.length) {
			return '';
		}
		// seek for this widget id
		for (var i in vbo_admin_widgets_names) {
			if (!vbo_admin_widgets_names.hasOwnProperty(i)) {
				continue;
			}
			if (vbo_admin_widgets_names[i]['id'] == widget_id) {
				jQuery('.vbo-newwidget-descr').html(vbo_admin_widgets_names[i]['descr']);
				return vbo_admin_widgets_names[i]['descr'];
			}
		}
		return '';
	}

	/**
	 * Makes the current widget selected, ready to be added to the document
	 */
	function vboWidgetsSelectWidget(widget_id) {
		if (!widget_id) {
			return false;
		}

		// remove selected class from all widgets, add un-selected class
		jQuery('.vbo-modal-widget-wrap').removeClass('vbo-modal-widget-selected').not('[data-vbowidgetid="' + widget_id + '"]').addClass('vbo-modal-widget-notselected');
		// add selected class to the current widget
		jQuery('.vbo-modal-widget-wrap[data-vbowidgetid="' + widget_id + '"]').addClass('vbo-modal-widget-selected').removeClass('vbo-modal-widget-notselected');
		// populate hidden field value
		jQuery('#vbo-newwidget-id').val(widget_id);

		return true;
	}

	/**
	 * Adds the new selected widget to the document
	 */
	function vboWidgetsAddWidgetToDoc(force_widget_id) {
		if (!vbo_admin_widgets_last_container || !vbo_admin_widgets_last_container.length) {
			// parent container not found
			console.error('parent container not found for adding a container');
			return false;
		}

		// get new widget id, name and descr
		var widget_id = null,
			widget_name = null,
			widget_descr = null;
		if (force_widget_id) {
			// plus button clicked on widget
			jQuery('#vbo-newwidget-id').val(force_widget_id);
			// remove selected class from all widgets, add un-selected class
			jQuery('.vbo-modal-widget-wrap').removeClass('vbo-modal-widget-selected').not('[data-vbowidgetid="' + force_widget_id + '"]').addClass('vbo-modal-widget-notselected');
			// add selected class to the current widget
			jQuery('.vbo-modal-widget-wrap[data-vbowidgetid="' + force_widget_id + '"]').addClass('vbo-modal-widget-selected').removeClass('vbo-modal-widget-notselected');
			widget_id = force_widget_id;
		} else {
			// selected widget was clicked to be added
			widget_id = jQuery('#vbo-newwidget-id').val();
		}
		widget_name = jQuery('.vbo-modal-widget-wrap[data-vbowidgetid="' + widget_id + '"]').find('.vbo-modal-widget-name').text();
		if (!widget_id || !widget_id.length || !widget_name || !widget_name.length) {
			console.error('new widget id missing');
			return false;
		}
		// get and update the description for the currently selected widget
		widget_descr = vboWidgetSetNewDescr(widget_id);

		// build new widget
		var html_widget_new = '<div class="vbo-admin-widgets-widget" data-vbowidgetid="' + widget_id + '">';
		html_widget_new += '		<div class="vbo-admin-widgets-widget-info" data-vbomanagewidgets="1">';
		html_widget_new += '			<div class="vbo-admin-widgets-widget-info-inner">';
		html_widget_new += '				<div class="vbo-admin-widgets-widget-details">';
		html_widget_new += '					<span class="vbo-admin-widgets-widget-info-drag"><?php VikBookingIcons::e('ellipsis-v'); ?></span>';
		html_widget_new += '					<h4 class="vbo-admin-widgets-widget-info-name">';
		html_widget_new += '						<span>' + widget_name + '</span>';
		html_widget_new += '						<span class="vbo-admin-widgets-widget-remove" onclick="vboWidgetsRemoveWidget(this);"><?php VikBookingIcons::e('trash'); ?></span>';
		html_widget_new += '					</h4>';
		html_widget_new += '				</div>';
		html_widget_new += '				<div class="vbo-admin-widgets-widget-info-descr">' + widget_descr + '</div>';
		html_widget_new += '			</div>';
		html_widget_new += '		</div>';
		html_widget_new += '		<div class="vbo-admin-widgets-widget-output" style="display: none;"></div>';
		html_widget_new += '</div>';

		// wrap the new HTML into a collection object
		var elem_widget_new = jQuery(html_widget_new);

		// append new widget HTML
		vbo_admin_widgets_last_container.find('.vbo-admin-widgets-widget-addnew').before(elem_widget_new);

		// update global map object
		var cur_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(vbo_admin_widgets_last_container.closest('.vbo-admin-widgets-section'));
		var cur_cont_index = vbo_admin_widgets_last_container.closest('.vbo-admin-widgets-section').find('.vbo-admin-widgets-container').not('.vbo-admin-widgets-container-addnew').index(vbo_admin_widgets_last_container);
		vbo_admin_widgets_map.sections[cur_sect_index]['containers'][cur_cont_index]['widgets'].push(widget_id);

		// trigger the save-map event
		document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));

		// close modal window
		hideVboModalWidgets();

		// unset last container
		vbo_admin_widgets_last_container = null;

		// populate widget output via AJAX
		vboWidgetsLoadWidgetContent(elem_widget_new, widget_id);
	}

	/**
	 * Asks for confirmation before removing the selected widget
	 */
	function vboWidgetsRemoveWidget(elem) {
		var vbo_widget_elem = jQuery(elem).closest('.vbo-admin-widgets-widget');
		var cur_widg_id = vbo_widget_elem.attr('data-vbowidgetid');
		var cur_sect_index = jQuery('.vbo-admin-widgets-section').not('.vbo-admin-widgets-section-addnew').index(vbo_widget_elem.closest('.vbo-admin-widgets-section'));
		var cur_cont_index = vbo_widget_elem.closest('.vbo-admin-widgets-section').find('.vbo-admin-widgets-container').not('.vbo-admin-widgets-container-addnew').index(vbo_widget_elem.closest('.vbo-admin-widgets-container'));
		var cur_widg_index = vbo_widget_elem.closest('.vbo-admin-widgets-container').find('.vbo-admin-widgets-widget').not('.vbo-admin-widgets-widget-addnew').index(vbo_widget_elem.closest('.vbo-admin-widgets-widget'));
		if (confirm(Joomla.JText._('VBO_WIDGETS_CONFRMELEM'))) {
			// calculate widget instance index for its type
			var widget_instance_index = jQuery('.vbo-admin-widgets-widget[data-vbowidgetid="' + cur_widg_id + '"]').index(vbo_widget_elem);
			
			// the widget method to call
			var call_method = 'removeInstance';
			// make a silent call for the widget in case it needs to perform actions when removing an instance
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_admin_widget'); ?>",
				{
					widget_id: cur_widg_id,
					widget_instance: widget_instance_index,
					call: call_method,
					tmpl: "component"
				},
				(response) => {
					try {
						var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
						if (!obj_res.hasOwnProperty(call_method)) {
							console.error('Unexpected JSON response', obj_res);
						}
					} catch(err) {
						console.error('could not parse JSON response', err, response);
					}
				},
				(error) => {
					console.error(error.responseText);
				}
			);

			// remove widget from document
			vbo_widget_elem.remove();
			// update global map object
			vbo_admin_widgets_map.sections[cur_sect_index]['containers'][cur_cont_index]['widgets'].splice(cur_widg_index, 1);
			// trigger the save-map event
			document.dispatchEvent(new Event('vbo-admin-widgets-savemap'));
		}
	}

	/**
	 * Populates the content of the newly added widget.
	 */
	function vboWidgetsLoadWidgetContent(container, widget_id) {
		if (!container || !container.find('.vbo-admin-widgets-widget-output').length) {
			console.error('Could not find new widget container');
			return false;
		}

		// the widget method to call
		var call_method = 'render';

		VBOCore.doAjax(
			"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_admin_widget'); ?>",
			{
				widget_id: widget_id,
				call: call_method,
				tmpl: "component"
			},
			(response) => {
				try {
					var obj_res = typeof response === 'string' ? JSON.parse(response) : response;
					if (obj_res.hasOwnProperty(call_method)) {
						// populate new widget content
						container.find('.vbo-admin-widgets-widget-output').html(obj_res[call_method]);
					} else {
						console.error('Unexpected JSON response', obj_res);
					}
				} catch(err) {
					console.error('could not parse JSON response', err, response);
				}
			},
			(error) => {
				console.error(error.responseText);
				alert(Joomla.JText._('VBO_WIDGETS_ERRDISPWIDG'));
			}
		);
	}
</script>
