<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2022 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * This layout file should be called once at most per page.
 * 
 * @var string  $vbo_page 	  the name of the current View in VBO.
 * @var string 	$btn_trigger  the CSS selector of the button that opens the panel.
 */
extract($displayData);

// make sure the current page name is set and it's a string
$vbo_page = isset($vbo_page) ? htmlspecialchars($vbo_page, ENT_QUOTES) : '';

// get the current page and root URIs
$vbo_page_uri = htmlspecialchars((string) JUri::getInstance(), ENT_QUOTES);
$root_uri 	  = htmlspecialchars(JUri::root(), ENT_QUOTES);

// get admin widgets helper
$widgets_helper = VikBooking::getAdminWidgetsInstance();

// get all widgets by preloading their assets (if any)
$admin_widgets = $widgets_helper->getWidgetNames($preload = true);

// load current widgets map for this page
$current_map = $widgets_helper->getMultitaskingMap($vbo_page);

// page active widgets list
$active_widgets = [];

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

// JS lang vars
JText::script('VBO_BROWSER_NOTIFS_ON');
JText::script('VBO_BROWSER_NOTIFS_OFF');
JText::script('VBO_BROWSER_NOTIFS_OFF_HELP');
JText::script('VBO_ADMIN_WIDGET');
JText::script('VBO_CONGRATS');
JText::script('VBO_APPEARANCE_PREF_AUTO');
JText::script('VBO_APPEARANCE_PREF_LIGHT');
JText::script('VBO_APPEARANCE_PREF_DARK');

?>

<div class="vbo-sidepanel-wrapper vbo-sidepanel-right vbo-sidepanel-close">

	<div class="vbo-sidepanel-container">

		<div class="vbo-sidepanel-layouts">
			<div class="vbo-sidepanel-dismiss">
				<span class="vbo-sidepanel-dismiss-btn"><?php VikBookingIcons::e('times'); ?></span>
			</div>
			<div class="vbo-sidepanel-notifications">
				<button type="button" class="vbo-sidepanel-notifications-btn"><?php VikBookingIcons::e('bell'); ?></button>
			</div>
			<div class="vbo-sidepanel-colorscheme">
				<span class="vbo-tooltip vbo-tooltip-bottom vbo-sidepanel-colorscheme-current" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $scheme_name); ?>"><?php VikBookingIcons::e($current_mode); ?></span>
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
			<div class="vbo-sidepanel-layout-type">
				<span class="vbo-sidepanel-layout-large">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96.75 75.3"><defs></defs><path d="M87.68,8.65a3.23,3.23,0,0,1,3.23,3.22V66.53a3.23,3.23,0,0,1-3.23,3.23H11.57a3.23,3.23,0,0,1-3.22-3.23V11.87a3.22,3.22,0,0,1,3.22-3.22H87.68m0-7.1H11.57A10.32,10.32,0,0,0,1.25,11.87V66.53A10.32,10.32,0,0,0,11.57,76.85H87.68A10.32,10.32,0,0,0,98,66.53V11.87A10.32,10.32,0,0,0,87.68,1.55Z" transform="translate(-1.25 -1.55)"/><rect id="wide" x="10.15" y="10" width="77.22" height="55.25" rx="4.94"/></svg>
				</span>
				<span class="vbo-sidepanel-layout-small">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96.75 75.3"><defs></defs><path d="M87.68,8.65a3.23,3.23,0,0,1,3.23,3.22V66.53a3.23,3.23,0,0,1-3.23,3.23H11.57a3.23,3.23,0,0,1-3.22-3.23V11.87a3.22,3.22,0,0,1,3.22-3.22H87.68m0-7.1H11.57A10.32,10.32,0,0,0,1.25,11.87V66.53A10.32,10.32,0,0,0,11.57,76.85H87.68A10.32,10.32,0,0,0,98,66.53V11.87A10.32,10.32,0,0,0,87.68,1.55Z" transform="translate(-1.25 -1.55)"/><rect id="right" x="48.1" y="12.42" width="35.75" height="50.93" rx="3.23"/></svg>
				</span>
			</div>
		</div>

		<div class="vbo-sidepanel-body-top">

			<div class="vbo-sidepanel-search">
				<?php VikBookingIcons::e('search', 'vbo-sidepanel-search-input-icn'); ?>
				<input id="vbo-sidepanel-search-input" type="text" placeholder="<?php echo htmlspecialchars(JText::translate('VBO_SEARCH_ADMIN_WIDGETS')); ?>" value="" autocomplete="off" />
			</div>

			<div class="vbo-sidepanel-add-widgets">
			<?php
			foreach ($admin_widgets as $k => $admin_widget) {
				/**
				 * Add widget container must be focusable with "tabindex=-1" so that via JS we will
				 * have the "relatedTarget" event property set to this element when blurring on search field.
				 */
				?>
				<div class="vbo-sidepanel-add-widget" data-vbowidgetid="<?php echo $admin_widget->id; ?>" tabindex="-1">
					<div class="vbo-sidepanel-widget-info">
						<div class="vbo-sidepanel-widget-info-det">
							<span class="vbo-sidepanel-widget-icn vbo-admin-widget-style-<?php echo $admin_widget->style; ?>"><?php echo $admin_widget->icon; ?></span>
							<span class="vbo-sidepanel-widget-name"><?php echo $admin_widget->name; ?></span>
						</div>
						<div class="vbo-sidepanel-widget-add">
							<span class="vbo-widget-render-modal"><?php echo VikBookingIcons::e('far fa-window-restore'); ?></span>
							<span class="vbo-widget-render-regular"><?php echo VikBookingIcons::e('plus-circle'); ?></span>
						</div>
					</div>
					<div class="vbo-sidepanel-widget-tags" style="display: none;"><?php echo strtolower($admin_widget->name . ' ' . $admin_widget->descr); ?></div>
				</div>
				<?php
			}
			?>
				<div class="vbo-sidepanel-add-widgets-nores" style="display: none;">
					<span><?php echo JText::translate('VBONORESULTS'); ?></span>
				</div>
			</div>

		</div>

		<div class="vbo-sidepanel-active-widgets">
		<?php
		// get multitask data object
		$multitask_data = VBOMultitaskParser::getInstance($vbo_page, $vbo_page_uri)->getData();
		foreach ($current_map as $widget_id) {
			$widget_instance = $widgets_helper->getWidget($widget_id);
			if (!$widget_instance) {
				continue;
			}
			// turn on multitask flag
			$widget_instance->setInMultitask(true);
			// build widget info object
			$widget_info = new stdClass;
			$widget_info->id   = $widget_instance->getIdentifier();
			$widget_info->name = $widget_instance->getName();
			// push widget info object to list
			$active_widgets[] = $widget_info;
			?>
			<div class="vbo-admin-widgets-widget-output vbo-admin-widgets-container-small" data-vbowidgetid="<?php echo $widget_info->id; ?>">
				<div class="vbo-admin-widgets-widget-detach"><?php VikBookingIcons::e('window-restore'); ?></div>
				<?php $widget_instance->render($multitask_data); ?>
			</div>
			<?php
		}
		?>
		</div>

		<div class="vbo-sidepanel-edit-widgets">
			<div class="vbo-sidepanel-edit-widgets-wrap">
				<button class="btn btn-small vbo-sidepanel-edit-widgets-trig" style="<?php echo !count($active_widgets) ? 'display: none;' : ''; ?>"><?php echo JText::translate('VBO_WIDGETS_CUSTWIDGETS'); ?></button>
			</div>
		</div>

	</div>

</div>

<script type="text/javascript">
	jQuery(function() {

		// inject core properties
		VBOCore.setOptions({
			is_vbo: 			true,
			cms: 				"<?php echo VBOPlatformDetection::isWordPress() ? 'wordpress' : 'joomla'; ?>",
			widget_ajax_uri:    "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_admin_widget'); ?>",
			assets_ajax_uri: 	"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=widgets_get_assets'); ?>",
			multitask_ajax_uri: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_multitask_widgets'); ?>",
			watchdata_ajax_uri: "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=widgets_watch_data'); ?>",
			current_page: 	    "<?php echo $vbo_page; ?>",
			current_page_uri:   "<?php echo $vbo_page_uri; ?>",
			root_uri:   		"<?php echo $root_uri; ?>",
			admin_widgets: 	    <?php echo json_encode($active_widgets); ?>,
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
		});

		// initialize multitasking events
		VBOCore.prepareMultitasking({
			selector: 		 ".vbo-sidepanel-wrapper",
			sclass_l_small:  "vbo-sidepanel-right",
			sclass_l_large:  "vbo-sidepanel-large",
			btn_trigger: 	 "<?php echo $btn_trigger; ?>",
			search_selector: "#vbo-sidepanel-search-input",
			search_nores: 	 ".vbo-sidepanel-add-widgets-nores",
			close_selector:  ".vbo-sidepanel-dismiss-btn",
			t_layout_small:	 ".vbo-sidepanel-layout-small",
			t_layout_large:  ".vbo-sidepanel-layout-large",
			wclass_base_sel: ".vbo-admin-widgets-widget-output",
			wclass_l_small:  "vbo-admin-widgets-container-small",
			wclass_l_large:  "vbo-admin-widgets-container-large",
			addws_selector:	 ".vbo-sidepanel-add-widgets",
			addw_selector:	 ".vbo-sidepanel-add-widget",
			addwfs_selector: ".vbo-sidepanel-add-widget-focussed",
			wtags_selector:	 ".vbo-sidepanel-widget-tags",
			addw_data_attr:  "data-vbowidgetid",
			actws_selector:  ".vbo-sidepanel-active-widgets",
			editw_selector:  ".vbo-sidepanel-edit-widgets-trig",
			editmode_class:  "vbo-admin-widgets-widget-editing",
			rmwidget_class:  "vbo-admin-widgets-widget-remove",
			rmwidget_icn:  	 '<?php VikBookingIcons::e('times'); ?>',
			dtcwidget_class: "vbo-admin-widgets-widget-detach",
			dtctarget_class: "vbo-admin-widget-head",
			dtcwidget_icn: 	 '<?php VikBookingIcons::e('far fa-window-restore'); ?>',
			notif_selector:  ".vbo-sidepanel-notifications-btn",
			notif_on_class:  "vbo-sidepanel-notifications-on",
			notif_off_class: "vbo-sidepanel-notifications-off",
		});

		// install Service Worker
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

		// subscribe to the multitask-panel-close event to dismiss the color scheme selection menu
		document.addEventListener(VBOCore.multitask_close_event, function() {
			if (jQuery('.vbo-sidepanel-colorscheme-list-show').length) {
				jQuery('.vbo-sidepanel-colorscheme-list-show').removeClass('vbo-sidepanel-colorscheme-list-show');
			}
		});

	});
</script>
