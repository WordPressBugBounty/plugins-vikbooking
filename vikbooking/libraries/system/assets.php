<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	system
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class used to provide support for the <head> of the page.
 *
 * @since 1.0
 */
class VikBookingAssets
{
	/**
	 * A list containing all the methods already used.
	 *
	 * @var array
	 */
	protected static $loaded = array();

	/**
	 * Loads all the assets required for the plugin.
	 *
	 * @return 	void
	 */
	public static function load()
	{
		// loads only once
		if (static::isLoaded(__METHOD__))
		{
			return;
		}

		$document = JFactory::getDocument();

		$internalFilesOptions = array('version' => VIKBOOKING_SOFTWARE_VERSION);

		// include localised strings for script files
		JText::script('CONNECTION_LOST');

		// system.js must be loaded on both front-end and back-end for tmpl=component support
		$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/system.js', $internalFilesOptions, array('id' => 'vbo-sys-script'));

		if (JFactory::getApplication()->isAdmin())
		{
			/* Load assets for CSS and JS */
			VikBooking::loadFontAwesome(true);
			
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'vikbooking.css', $internalFilesOptions, array('id' => 'vbo-style'));
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'fonts/vboicomoon.css', $internalFilesOptions, array('id' => 'vbo-icomoon-style'));
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'vikbooking_backendcustom.css', $internalFilesOptions, array('id' => 'vbo-custom-style'));

			VikBooking::getVboApplication()->normalizeBackendStyles();

			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'css/system.css', $internalFilesOptions, array('id' => 'vbo-sys-style'));
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'css/bootstrap.lite.css', $internalFilesOptions, array('id' => 'bootstrap-lite-style'));
			$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/bootstrap.min.js', $internalFilesOptions, array('id' => 'bootstrap-script'));

			$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'js/admin.js', $internalFilesOptions, array('id' => 'vbo-admin-script'));

			/**
			 * Include the VBOCore JS class.
			 * 
			 * @since 	1.5.0
			 */
			$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'vbocore.js', $internalFilesOptions, array('id' => 'vbo-core-script'));

			/**
			 * Always prepare AJAX requests to pass a CSRF token.
			 * 
			 * @since 	1.6.0
			 */
			JHtml::fetch('vbohtml.scripts.ajaxcsrf');

			/**
			 * Include the Toast JS class.
			 * 
			 * @since 	1.5.0
			 */
			$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'toast.js', $internalFilesOptions, array('id' => 'vbo-toast-script'));
			$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'toast.css', $internalFilesOptions, array('id' => 'vbo-toast-style'));

			$document->addScriptDeclaration(
<<<JS
(function($) {
	'use strict';

	$(function() {
		VBOToast.create(VBOToast.POSITION_TOP_RIGHT);
	});
})(jQuery);
JS
			);

			/**
			 * Load necessary assets for WordPress >= 5.3
			 * 
			 * @since 	1.2.10
			 */
			JLoader::import('adapter.application.version');
			$wpv = new JVersion;
			if (version_compare($wpv->getShortVersion(), '5.3', '>=')) {
				$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'css/bc/wp5.3.css', $internalFilesOptions, array('id' => 'vbo-wp-bc-style'));
			}

			/**
			 * Load the proper CSS file according to the appearance preferences.
			 * 
			 * @since 	1.5.0
			 */
			VikBooking::loadAppearancePreferenceAssets();

			/**
			 * Load the Web App Manifest JSON file.
			 * 
			 * @since 	1.6.5
			 */
			VBOWebappManifest::load();
		}
		else
		{
			if (VikBooking::loadBootstrap())
			{
				$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI.'bootstrap.min.css', $internalFilesOptions, array('id' => 'vbo-bs-style'));
				$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI.'bootstrap-theme.min.css', $internalFilesOptions, array('id' => 'vbo-bstheme-style'));
			}
			
			VikBooking::loadFontAwesome();
			$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI.'vikbooking_styles.css', $internalFilesOptions, array('id' => 'vbo-style'));

			/**
			 * Load the proper CSS file according to the appearance preferences.
			 * This is made after the main stylesheet and before the custom one.
			 * 
			 * @since 	1.5.0
			 */
			VikBooking::loadAppearancePreferenceAssets();

			$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI.'vikbooking_custom.css', $internalFilesOptions, array('id' => 'vbo-custom-style'));
		}
	}

	/**
	 * This method should be called only within the wp-admin section when
	 * the active page does not belong to the plugin Vik Booking. It loads
	 * the necessary CSS and JS assets to support browser notifications.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.5.0
	 */
	public static function loadForExternal()
	{
		// loads only once
		if (static::isLoaded(__METHOD__))
		{
			return;
		}

		// main library
		require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikbooking.php';

		// load the necessary JS and CSS assets
		$document = JFactory::getDocument();
		$internalFilesOptions = array('version' => VIKBOOKING_SOFTWARE_VERSION);

		$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'vbocore.js', $internalFilesOptions, array('id' => 'vbo-core-script'));
		$document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'toast.js', $internalFilesOptions, array('id' => 'vbo-toast-script'));
		$document->addStyleSheet(VIKBOOKING_ADMIN_ASSETS_URI . 'toast.css', $internalFilesOptions, array('id' => 'vbo-toast-style'));

		/**
		 * Always prepare AJAX requests to pass a CSRF token.
		 * 
		 * @since 	1.6.0
		 */
		JHtml::fetch('vbohtml.scripts.ajaxcsrf');

		// build AJAX uri endpoints and CMS
		$widget_ajax_uri 	= VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_admin_widget');
		$assets_ajax_uri 	= VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=widgets_get_assets');
		$multitask_ajax_uri = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=exec_multitask_widgets');
		$watchdata_ajax_uri = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=widgets_watch_data');
		$current_page_uri 	= htmlspecialchars((string) JUri::getInstance(), ENT_QUOTES);
		$root_uri 			= htmlspecialchars(JUri::root(), ENT_QUOTES);
		$current_cms 		= VBOPlatformDetection::isWordPress() ? 'wordpress' : 'joomla';

		// check if the notification audio file exists within VCM
		$notif_audio_path = implode(DIRECTORY_SEPARATOR, [VCM_ADMIN_PATH, 'assets', 'css', 'audio', 'new_notification.mp3']);
		$notif_audio_url  = is_file($notif_audio_path) ? (VCM_ADMIN_URI . implode('/', ['assets', 'css', 'audio', 'new_notification.mp3'])) : null;

		// service worker and push data
		$service_worker_path  = VBOWebappServiceworker::getUri();
		$service_worker_scope = VBOWebappServiceworker::getScope();
		$push_config 		  = json_encode(VBOWebappPush::getConfig());

		// add the necessary script declaration
		$document->addScriptDeclaration(
<<<JS
(function($) {
	'use strict';

	$(function() {

		VBOToast.create(VBOToast.POSITION_TOP_RIGHT);

		VBOCore.setOptions({
			cms: 				  "$current_cms",
			widget_ajax_uri:      "$widget_ajax_uri",
			assets_ajax_uri: 	  "$assets_ajax_uri",
			multitask_ajax_uri:   "$multitask_ajax_uri",
			watchdata_ajax_uri:   "$watchdata_ajax_uri",
			current_page: 	      "wp-admin",
			current_page_uri:     "$current_page_uri",
			root_uri:     		  "$root_uri",
			notif_audio_url: 	  "$notif_audio_url",
			service_worker_path:  "{$service_worker_path}",
			service_worker_scope: "{$service_worker_scope}",
			push: 				  $push_config,
		});

		VBOCore.listenServiceWorkerMessages();

	});
})(jQuery);
JS
		);

		// finally, preload the admin widgets
		VikBooking::getAdminWidgetsInstance()->getWidgetNames($preload = true);

		return;
	}

	/**
	 * Checks if the method has been already loaded.
	 * This function assumes that after this check we are going
	 * to use the specified method.
	 *
	 * A method is considered loaded only if the arguments used are the same.
	 *
	 * @param 	string 	 $method 	The method to check for.
	 * @param 	array 	 $args 		The list of arguments.
	 * 
	 * @return 	boolean  True if already used, otherwise false.
	 */
	protected static function isLoaded($method, array $args = array())
	{
		// generate a unique signature containing the method name
		// and the list of arguments to use
		$sign = serialize(array($method, $args));

		// check if the method has been already loaded
		if (isset(static::$loaded[$sign]))
		{
			// already loaded
			return true;
		}

		// mark the method as loaded
		static::$loaded[$sign] = 1;

		// not loaded
		return false;
	}
}
