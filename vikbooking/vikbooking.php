<?php
/*
Plugin Name:  VikBooking
Plugin URI:   https://vikwp.com/plugin/vikbooking
Description:  Certified Booking Engine for Hotels and Accommodations.
Version:      1.6.9
Author:       E4J s.r.l.
Author URI:   https://vikwp.com
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  vikbooking
Domain Path:  /languages
*/

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

// autoload dependencies
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'autoload.php';

// handle install/uninstall
register_activation_hook(__FILE__, array('VikBookingInstaller', 'activate'));
register_deactivation_hook(__FILE__, array('VikBookingInstaller', 'deactivate'));
register_uninstall_hook(__FILE__, array('VikBookingInstaller', 'delete'));

// init Installer
add_action('init', array('VikBookingInstaller', 'onInit'));

/**
 * Fires after all automatic updates have run.
 * Completes the update scheduled in background.
 *
 * @param  array  $results  The results of all attempted updates.
 *
 * @since  1.3.12
 */
add_action('automatic_updates_complete', array('VikBookingInstaller', 'automaticUpdate'));

/**
 * Filters whether to automatically update core, a plugin, a theme, or a language.
 * Used to automatically turn off the update in case a PRO version expired.
 *
 * @param  bool|null  $update  Whether to update. The value of null is internally used
 *                             to detect whether nothing has hooked into this filter.
 * @param  object     $item    The update offer.
 *
 * @since  1.3.12
 */
add_filter('auto_update_plugin', array('VikBookingInstaller', 'useAutoUpdate'), 10, 2);

/**
 * Fires at the end of the update message container in each
 * row of the plugins list table.
 *
 * The dynamic portion of the hook name, `$file`, refers to the path
 * of the plugin's primary file relative to the plugins directory.
 *
 * @link   https://developer.wordpress.org/reference/hooks/in_plugin_update_message-file/
 *
 * @param  array  $data      An array of plugin metadata.
 * @param  array  $response  An array of metadata about the available plugin update.
 *
 * @since  1.3.12
 */
add_action('in_plugin_update_message-vikbooking/vikbooking.php', array('VikBookingInstaller', 'getUpdateMessage'), 10, 2);

// init pagination layout
VikBookingBuilder::setupPaginationLayout();
// init html helpers
VikBookingBuilder::setupHtmlHelpers();
// init payment framework
VikBookingBuilder::configurePaymentFramework();
// setup hooks to extend the backup functionalities
VikBookingBuilder::setupBackupSystem();

// setup plugin overrides management
add_action('plugins_loaded', array('VikBookingBuilder', 'setupOverridesManager'));

// setup lite system
add_action('plugins_loaded', array('VikBookingLiteManager', 'setup'));

/**
 * Added support for screen options.
 * Parameters such as the list limit can be changed from there.
 *
 * @since 1.2.5
 */
add_action('current_screen', array('VikBookingScreen', 'options'));
add_filter('set-screen-option', array('VikBookingScreen', 'saveOption'), 10, 3);
/**
 * Due to WordPress 5.4.2 changes, we need to attach
 * VikBooking to a dedicated hook in order to 
 * allow the update of the list limit.
 *
 * @since 	1.3.5
 */
add_filter('set_screen_option_vikbooking_list_limit', array('VikBookingScreen', 'saveOption'), 10, 3);

// init Session
add_action('init', array('JSessionHandler', 'start'), 1);
add_action('wp_logout', array('JSessionHandler', 'destroy'));

// filter page link to rewrite URI
add_action('plugins_loaded', function()
{
	// installer class will check the update status
	VikBookingInstaller::update();

	/**
	 * 
	 * Fires once the plugins have loaded.
	 * 
	 * Language is loaded through the filter 'plugins_loaded' in order to avoid to load language 
	 * before the correct one has been set by WordPress or by another third-party plugin. 
	 * 
	 * @link https://developer.wordpress.org/reference/hooks/plugins_loaded/
	 * 
	 * @since 1.4.2 
	 */
	VikBookingBuilder::loadLanguage();

	global $pagenow;

	$app   = JFactory::getApplication();
	$input = $app->input;

	// check if the URI contains option=com_vikbooking
	if ($input->get('option') == 'com_vikbooking')
	{
		// make sure we are not contacting the AJAX and POST end-points
		if (!wp_doing_ajax() && $pagenow != 'admin-post.php')
		{
			/**
			 * Include page in query string only if we are in the back-end,
			 * because WordPress 5.5 seems to break the page loading in case
			 * that argument has been included in query string.
			 *
			 * It is not needed to include this argument in the front-end
			 * as the page should lean on the reached shortcode only.
			 *
			 * @since 1.3.7
			 */
			if ($app->isAdmin())
			{
				// inject page=vikbooking in GET superglobal
				$input->get->set('page', 'vikbooking');
			}
		}
		else
		{
			// inject action=vikbooking in GET superglobal for AJAX and POST requests
			$_GET['action'] = 'vikbooking';
		}
	}
	elseif ($input->get('page') == 'vikbooking' || $input->get('action') == 'vikbooking')
	{
		// inject option=com_vikbooking in GET superglobal
		$_GET['option'] = 'com_vikbooking';
	}
});

// process the request and obtain the response
add_action('init', function()
{
	$app 	= JFactory::getApplication();
	$input 	= $app->input;

	// if we are in the front-end, try to parse the URL to inject
	// option, view and args in the input request
	if ($app->isSite() && VIKBOOKING_SITE_PREPROCESS)
	{
		// get post ID from current URL
		$id = url_to_postid(JUri::current());

		if ($id)
		{
			// get shortcode admin model
			$model = JModel::getInstance('vikbooking', 'shortcode', 'admin');
			// get shortcode searching by post ID (false to avoid returning a new item)
			$shortcode = $model->getItem(array('post_id' => $id), false);

			if ($shortcode)
			{
				// build args array using the shortcode attributes
				$args = (array) json_decode($shortcode->json, true);
				$args['view'] 	= $shortcode->type;
				$args['option'] = 'com_vikbooking';

				// inject the shortcode args into the input request
				foreach ($args as $k => $v)
				{
					// inject only if not defined
					$input->def($k, $v);
				}
			}
		}
	}

	// process VikBooking only if it has been requested via GET or POST
	if ($input->get('option') == 'com_vikbooking' || $input->get('page') == 'vikbooking')
	{
		VikBookingBody::process();
	}
});

// handle AJAX requests
add_action('wp_ajax_vikbooking', 'handle_vikbooking_ajax');
add_action('wp_ajax_nopriv_vikbooking', 'handle_vikbooking_ajax');

function handle_vikbooking_ajax()
{
	VikBookingBody::getHtml();

	// die to get a valid response
	wp_die();
}

// setup admin menu
add_action('admin_menu', array('VikBookingBuilder', 'setupAdminMenu'));

// register widgets
add_action('widgets_init', array('VikBookingBuilder', 'setupWidgets'));

// handle shortcodes (SITE controller dispatcher)
add_shortcode('vikbooking', function($atts, $content = null)
{
	$app = JFactory::getApplication();

	/**
	 * Force the application client to "site" every time a shortcode is executed.
	 * 
	 * @since 1.5.5
	 */
	$app->setClient('site');

	// wrap attributes in a registry
	$args = new JObject($atts);

	// get the VIEW (empty if not set)
	$view = $args->get('view', '');

	// load the FORM of the view
	JLoader::import('adapter.form.form');
	$path = implode(DIRECTORY_SEPARATOR, array(VBO_SITE_PATH, 'views', $view, 'tmpl', 'default.xml'));
	// raises an exception if the VIEW is not set
	$form = JForm::getInstance($view, $path);
	
	// get all the XML form fields
	$fields = $form->getFields();

	// filter the fields to get a list of allowed names
	$fields = array_map(function($f)
	{
		return (string) $f->attributes()->name;
	}, $fields);

	// inject query vars
	$input = $app->input;
	// since we are going to render the controller manually,
	// we don't need to push the option into $_REQUEST pool.
	// $input->set('option', 'com_vikbooking');
	
	// Inject shortcode vars only if they are not set 
	// in the request. This is used to allow the navigation
	// between the pages.
	$input->def('view', $view);
	
	foreach ($fields as $k)
	{
		$input->def($k, $args->get($k));
	}

	// dispatch the controller
	return VikBookingBody::getHtml(true);
});

// the callback is fired before the VBO controller is dispatched
add_action('vikbooking_before_dispatch', function()
{
	$app 	= JFactory::getApplication();
	$user 	= Jfactory::getUser();

	// initialize timezone handler
	JDate::getDefaultTimezone();
	date_default_timezone_set($app->get('offset', 'UTC'));

	// check if the user is authorised to access the back-end (only if the client is 'admin')
	if ($app->isAdmin() && !$user->authorise('core.manage', 'com_vikbooking'))
	{
		if ($user->guest)
		{
			// if the user is not logged, redirect to login page
			$app->redirect('index.php');
			exit;
		}
		else
		{
			// otherwise raise an exception
			wp_die(
				'<h1>' . JText::translate('FATAL_ERROR') . '</h1>' .
				'<p>' . JText::translate('RESOURCE_AUTH_ERROR') . '</p>',
				403
			);
		}
	}

	// main library
	require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikbooking.php';

	if ($app->isAdmin())
	{
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'vikbooking.php';
		require_once VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'jv_helper.php';
	}
	else
	{
		// Invoke VCM before the rendering
		VikBooking::detectUserAgent();
		VikBooking::invokeChannelManager();
		VikBooking::getTracker();
		VikBooking::loadPreferredColorStyles();
	}

});

// instead using the default server timezone, try to use the one
// specified within the WordPress configuration
add_filter('vik_date_default_timezone', function($timezone)
{
	return JFactory::getApplication()->get('offset', $timezone);
});

// the callback is fired once the VBO controller has been dispatched
add_action('vikbooking_after_dispatch', function()
{
	// load assets after dispatching the controller to avoid
	// including JS and CSS when an AJAX function exits or dies
	VikBookingAssets::load();

	/**
	 * Load javascript core.
	 *
	 * @since 1.1.8
	 */
	JHtml::fetch('behavior.core');

	// restore standard timezone
	date_default_timezone_set(JDate::getDefaultTimezone());

	/**
	 * @note 	when the headers have been sent or when 
	 * 			the request is AJAX, the assets (CSS and JS) are
	 * 			appended to the document after the 
	 * 			response dispatched by the controller.
	 */
});

// End-point for front-end post actions.
// The end-point URL must be built as .../wp-admin/admin-post.php
// and requires $_POST['action'] == 'vikbooking' to be submitted through a form or GET.
add_action('admin_post_vikbooking', 'handle_vikbooking_endpoint');			// if the user is logged in
add_action('admin_post_nopriv_vikbooking', 'handle_vikbooking_endpoint');	// if the user in not logged in

// handle POST end-point
function handle_vikbooking_endpoint()
{
	// get PLAIN response
	echo VikBookingBody::getResponse();
}

// Hook used to access the PAGE details when a user is 
// creating or updating it. This is helpful to make a relation
// between the page and the injected shortcode.
add_action('save_post', function($post_id)
{
	// get model to access all the existing shortcodes
	$model = JModel::getInstance('vikbooking', 'shortcodes', 'admin');
	$shortcodes = $model->all(array('id', 'shortcode', 'post_id'));

	// get post data
	$post = get_post($post_id);

	/**
	 * Check if we are editing a child post as Gutenberg 
	 * seems to use always the inherit status, which 
	 * refers to a post parent.
	 *
	 * @since 1.0.17
	 */
	if ($post->post_status != 'publish' && !empty($post->post_parent) && $post->post_parent != $post_id)
	{
		// fallback to obtain parent post data
		$post = get_post($post->post_parent);

		/**
		 * Use new post ID.
		 *
		 * @since 	1.2.7  Fixed post ID property name.
		 */
		$post_id = $post->ID;
	}

	if ($post->post_status != 'publish')
	{
		// ignore drafts auto-save
		return;
	}

	// get shortcode model
	$shortcodeModel = JModel::getInstance('vikbooking', 'shortcode', 'admin');

	/**
	 * Since we need unique post IDs, all the shortcodes
	 * that are assigned to the specified $post_id should
	 * be detached.
	 *
	 * @since 	1.0.17
	 */
	foreach ($shortcodes as $data)
	{
		if ($data->post_id == $post_id)
		{
			// The post is already assigned to a shortcode.
			// Unset it to avoid duplicated.
			$data->post_id = 0;
			$shortcodeModel->save($data);
		}
	}
	
	// iterate the shortcodes
	foreach ($shortcodes as $data)
	{
		// check if the content of the post contains the shortcode
		if (strpos($post->post_content, html_entity_decode($data->shortcode)) !== false)
		{
			// inject the POST ID
			$data->post_id = $post_id;

			// update shortcode
			$shortcodeModel->save($data);

			// stop iterating
			return;
		}
	}
});

// Hook used to unset temporarily the relationship
// between the trashed post and the shortcode.
add_action('trashed_post', function($post_id)
{
	// get shortcode model
	$model = JModel::getInstance('vikbooking', 'shortcode', 'admin');

	// get the shortcode attached to the trashed post ID
	$item = $model->getItem(array('post_id' => $post_id), false);

	// if the item exists, temporarily detach the relationship
	if ($item)
	{
		$item->post_id 		= 0;
		$item->tmp_post_id 	= $post_id;

		$model->save($item);
	}
});

// Hook used to restore permanently the relationship
// between the untrashed post and the shortcode.
add_action('untrashed_post', function($post_id)
{
	// get shortcode model
	$model = JModel::getInstance('vikbooking', 'shortcode', 'admin');

	// get the shortcode attached to the untrashed post ID
	$item = $model->getItem(array('tmp_post_id' => $post_id), false);

	// if the item exists, re-attach the relationship
	if ($item)
	{
		$item->post_id 		= $post_id;
		$item->tmp_post_id 	= 0;

		$model->save($item);
	}
});

// Hook used to temporarily detach the relationship
// between the deleted post and the shortcode.
add_action('deleted_post', function($post_id)
{
	// get shortcode model
	$model = JModel::getInstance('vikbooking', 'shortcode', 'admin');

	// get the shortcode attached to the trashed post ID
	$item = $model->getItem(array('tmp_post_id' => $post_id), false);

	// If no item found, the "trash" feature is probably disabled.
	// Try to take a look for a shortcode with an active relationship.
	if (!$item)
	{
		$item = $model->getItem(array('post_id' => $post_id), false);
	}

	// if the item exists, permanently detach the relationship
	if ($item)
	{
		$item->post_id 		= 0;
		$item->tmp_post_id 	= 0;

		$model->save($item);
	}
});

if (JFactory::getApplication()->isAdmin() && !wp_doing_ajax())
{
	/**
	 * @todo should we restrict these filters to the post managements pages only?
	 */

	VikBookingLoader::import('system.mce');

	// add new buttons
	add_filter('mce_buttons', array('VikBookingTinyMCE', 'addShortcodesButton'));

	// load the button handlers
	add_filter('mce_external_plugins', array('VikBookingTinyMCE', 'registerShortcodesScript'));
}

/**
 * Always load the Gutenberg shortcodes block to support the preview rendering.
 * 
 * @since 1.6.7
 */
VikBookingLoader::import('system.gutenberg');
add_action('init', ['VikBookingGutenberg', 'registerShortcodesScript']);

/**
 * Dispatch the uninstallation of VikBooking
 * every time a new blog (multisite) is deleted.
 *
 * Fires after the site is deleted from the network (WP 4.8.0 or higher).
 *
 * @param 	integer  $blog_id 	The site ID.
 * @param 	boolean  $drop 		True if site's tables should be dropped. Default is false.
 *
 * @since 	1.0.6
 */
add_action('deleted_blog', function($blog_id, $drop)
{
	VikBookingInstaller::uninstall($drop);
}, 10, 2);

/**
 * Once the plugins have been loaded, evaluates to execute the
 * scheduled cron jobs.
 *
 * Scheduling is processed in case a cron job is hitting wp-cron file
 * or in case a user is visiting the website.
 * 
 * @since 	1.5.10  Schedules a different hook for each cron.
 * @since 	1.6.5 	Added priority to PHP_INT_MAX - 1 to allow Vik Channel Manager
 * 					to have all the intervals scheduled by Vik Booking.
 */
add_action('plugins_loaded', array('VikBookingCron', 'setup'), (PHP_INT_MAX - 1));

/**
 * Filters the action links displayed for each plugin in the Plugins list table.
 * Hook used to filter the "deactivation" link and ask a feedback every time that
 * button is clicked.
 *
 * @param 	array   $actions      An array of plugin action links. By default this can include 'activate',
 *                                'deactivate', and 'delete'. With Multisite active this can also include
 *                                'network_active' and 'network_only' items.
 * @param 	string  $plugin_file  Path to the plugin file relative to the plugins directory.
 * @param 	array   $plugin_data  An array of plugin data. See `get_plugin_data()`.
 * @param 	string  $context      The plugin context. By default this can include 'all', 'active', 'inactive',
 *                                'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
 * 
 * @since 	1.2.13
 */
add_filter('plugin_action_links', array('VikBookingFeedback', 'deactivate'), 10, 4);

/**
 * Adjusts the timezone of the website before dispatching
 * a widget as we are currently outside of the main plugin and
 * the timezone have probably been restored to the default one.
 *
 * @param 	string 	 $id       The widget ID (path name).
 * @param 	JObject  &$params  The widget configuration registry.
 *
 * @since 	1.2.10
 */
add_action('vik_widget_before_dispatch_site', function($id, &$params)
{
	// initialize timezone handler
	JDate::getDefaultTimezone();
	date_default_timezone_set(JFactory::getApplication()->get('offset', 'UTC'));
}, 10, 2);

/**
 * Restores the timezone of the website after dispatching
 * a widget in order to avoid strange behaviors with other plugins.
 *
 * @param 	string 	$id     The widget ID (path name).
 * @param 	string  &$html  The HTML of the widget to display.
 *
 * @since 	1.2.10
 */
add_action('vik_widget_after_dispatch_site', function($id, &$html)
{
	// restore standard timezone
	date_default_timezone_set(JDate::getDefaultTimezone());	
}, 10, 2);

/**
 * Added support for Loco Translate.
 * In case some translations have been edited by using this plugin,
 * we should look within the Loco Translate folder to check whether
 * the requested translation is available.
 *
 * @param 	boolean  $loaded  True if the translation has been already loaded.
 * @param 	string 	 $domain  The plugin text domain to load.
 *
 * @return 	boolean  True if a new translation is loaded.
 *
 * @since 	1.6.0
 */
add_filter('vik_plugin_load_language', function($loaded, $domain)
{
	// proceed only in case the translation hasn't been loaded
	// and Loco Translate plugin is installed
	if (!$loaded && is_dir(WP_LANG_DIR . DIRECTORY_SEPARATOR . 'loco'))
	{
		// Build LOCO path.
		// Since load_plugin_textdomain accepts only relative paths, 
		// we should go back to the /wp-contents/ folder first.
		$loco = implode(DIRECTORY_SEPARATOR, array('..', 'languages', 'loco', 'plugins'));

		// try to load the plugin translation from Loco folder
		$loaded = load_plugin_textdomain($domain, false, $loco);
	}

	return $loaded;
}, 10, 2);

/**
 * Display notice messages in third party plugins
 * to suggest the import of the reservations.
 * 
 * @since 	1.3.5
 */
add_action('admin_notices', function()
{
	// main library
	require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikbooking.php';

	// load supported plugins
	$supported_plugins = VikBooking::canImportBookingsFromThirdPartyPlugins();

	if (wp_doing_ajax() || $supported_plugins === false)
	{
		return;
	}

	$lookup = array(
		'admin.php' => 'page',
		'edit.php'  => 'post_type',
	);

	global $pagenow;

	if (!isset($lookup[$pagenow]))
	{
		// page not observed
		return;
	}

	$input = JFactory::getApplication()->input;

	if (!preg_match("/^mphb_/i", $input->get($lookup[$pagenow])))
	{
		return;
	}

	// get logo URI
	$backlogo = VikBooking::getBackendLogo();
	?>
	<style>
		#mphb-vbo-import-notice {
			display: inline-block;
			width: 100%;
			box-sizing: border-box;
			border-left-color: #cc9907;
			box-shadow: 0 5px 10px rgba(0,0,0,.05);
		}
		#mphb-vbo-import-notice a.vbo-import-button {
			float: right;
			border: none;
			font-size: 14px;
			margin: 18px 10px;
			padding: 12px 29px;
			color: #FFF;
			text-shadow: none;
			font-weight: bold;
			background: #3AA03C;
			-moz-border-radius: 3px;
			border-radius: 3px;
			-webkit-border-radius: 3px;
			text-decoration: none;
			height: 50px;
			text-align: center;
			text-transform: uppercase;
			box-shadow: none;
			line-height: 26px;
		}
		a.vbo-import-button:hover {
			background: #43BD45 !important;
		}
		.vbo-import-logo {
			display: inline-block;
			margin-top: 21px;
			max-width: 49px;
		}
		.vbo-import-text {
			font-size: 18px;
			display: inline-block;
			vertical-align: top;
			margin: 31px 10px 10px 10px;
		}
	</style>

	<div class="notice is-dismissible notice-info" id="mphb-vbo-import-notice">
		<div class="vbo-import-wrap">
			<p>
				<span class="vbo-import-logo">
					<img src="<?php echo VBO_ADMIN_URI . (!empty($backlogo) ? "resources/{$backlogo}" : 'vikbooking.png'); ?>" alt="VikBooking Logo" />
				</span>
				<span class="vbo-import-text"><?php echo JText::sprintf('VBO_IMPBFROM_INTO_VBO', $supported_plugins['mphb']); ?></span>
				<a class="button vbo-import-button" href="admin.php?option=com_vikbooking&view=importbftpp"><?php echo JText::translate('VBO_IMPBFTPP_DOIMPORT_SHORT'); ?></a>
			</p>
		</div>
	</div>
	<?php
});

/**
 * Downloads the RSS feeds after loading the dashboard of VikBooking.
 *
 * @since 1.3.9
 */
add_action('vikbooking_after_display_dashboard', array('VikBookingRssFeeds', 'download'));

/**
 * Trigger event to allow the plugins to include custom HTML within the view. 
 * It is possible to return an associative array to group the HTML strings
 * under different fieldsets. Plain/html string will be always pushed within
 * the "custom" fieldset instead.
 *
 * Displays the RSS configuration.
 *
 * @param 	mixed   $forms  The HTML to display.
 * @param 	mixed   $view 	The current view instance.
 *
 * @return 	mixed 	The HTML to display.
 *
 * @since 	1.3.9
 */
add_filter('vikbooking_display_view_config_global', array('VikBookingRssFeeds', 'config'), 10, 2);

/**
 * Save the RSS configuration every time "saveconfig" task is reached.
 *
 * @since 	1.3.9
 */
add_action('vikbooking_before_dispatch', function()
{
	$input = JFactory::getApplication()->input;

	if ($input->get('task') == 'saveconfig')
	{
		VikBookingRssFeeds::save();
	}
});

/**
 * Hook used to manipulate the RSS channels to which the plugin is subscribed.
 *
 * @param 	array    $channels  A list of RSS permalinks.
 * @param 	boolean  $status    True to return only the published channels.
 *
 * @return 	array    A list of supported channels.
 *
 * @since 	1.3.9
 */
add_filter('vikbooking_fetch_rss_channels', array('VikBookingRssFeeds', 'getChannels'), 10, 2);

/**
 * Hook used to apply some stuff before returning the RSS reader.
 *
 * @param 	JRssReader  &$rss  The RSS reader handler.
 *
 * @since 	1.3.9
 */
add_action('vikbooking_before_use_rss', array('VikBookingRssFeeds', 'ready'));

/**
 * Hook used to support browser notifications on any /wp-admin
 * page that doesn't belong to Vik Booking. Note that the hook
 * should be 'admin_footer' and not 'admin_print_footer_scripts'.
 * 
 * @since 	1.5.0
 */
add_action('admin_footer', function()
{
	$app   = JFactory::getApplication();
	$input = $app->input;

	// make sure we are not inside Vik Booking
	if ($input->get('option') == 'com_vikbooking' || $input->get('page') == 'vikbooking' || $input->get('action') == 'vikbooking')
	{
		return;
	}

	/**
	 * Let third party plugins stop Vik Booking from loading assets on other wp-admin pages.
	 * 
	 * @since 	1.6.1
	 */
	$allowed = apply_filters('vikbooking_load_external_assets', true);
	if (!$allowed)
	{
		return;
	}

	// initialize timezone handler
	JDate::getDefaultTimezone();
	date_default_timezone_set($app->get('offset', 'UTC'));

	// load the necessary assets for external pages
	VikBookingAssets::loadForExternal();

	// restore standard timezone
	date_default_timezone_set(JDate::getDefaultTimezone());

	/**
	 * Reload system configuration scripts to allow
	 * Vik Booking to preload texts also on VCM.
	 * 
	 * @since 	1.6.0
	 */
	JHtml::fetch('behavior.core');
});

/**
 * Prevent WordPress Themes from running wptexturize() that may erroneously
 * detect HTML tags among raw JavaScript code, by encoding ampersand symbols.
 * 
 * @since 	1.6.9
 */
add_filter('run_wptexturize', function($run_texturize)
{
	return is_admin() ? $run_texturize : false;
}, PHP_INT_MAX);
