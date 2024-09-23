<?php
/** 
 * @package   	VikBooking
 * @subpackage 	core
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

// include defines
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'defines.php';

/**
 * @since 	1.0.2 	It is possible to inject debug=on in query
 * 					string to force the error reporting to MAXIMUM.
 */
if (VIKBOOKING_DEBUG || (isset($_GET['debug']) && $_GET['debug'] == 'on'))
{
	error_reporting(E_ALL);
	ini_set('display_errors', true);
}

// include internal loader if not exists
if (!class_exists('JLoader'))
{
	require_once implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'libraries', 'adapter', 'loader', 'loader.php']);

	// setup auto-loader
	JLoader::setup();

	// setup base path
	JLoader::$base = VIKBOOKING_LIBRARIES;
}

// load framework dependencies
JLoader::import('adapter.acl.access');
JLoader::import('adapter.loader.utils');
JLoader::import('adapter.mvc.view');
JLoader::import('adapter.mvc.controller');
JLoader::import('adapter.factory.factory');
JLoader::import('adapter.html.html');
JLoader::import('adapter.http.http');
JLoader::import('adapter.input.input');
JLoader::import('adapter.output.filter');
JLoader::import('adapter.language.text');
JLoader::import('adapter.layout.helper');
JLoader::import('adapter.session.handler');
JLoader::import('adapter.session.session');
JLoader::import('adapter.application.route');
JLoader::import('adapter.application.version');
JLoader::import('adapter.uri.uri');
JLoader::import('adapter.toolbar.helper');
JLoader::import('adapter.editor.editor');
JLoader::import('adapter.date.date');
JLoader::import('adapter.event.dispatcher');
JLoader::import('adapter.event.pluginhelper');
JLoader::import('adapter.component.helper');
JLoader::import('adapter.database.table');

// import internal loader
JLoader::import('loader.loader', VIKBOOKING_LIBRARIES);

// load plugin dependencies
VikBookingLoader::import('bc.error');
VikBookingLoader::import('bc.mvc');
VikBookingLoader::import('layout.helper');
VikBookingLoader::import('lite.manager');
VikBookingLoader::import('system.body');
VikBookingLoader::import('system.builder');
VikBookingLoader::import('system.cron');
VikBookingLoader::import('system.install');
VikBookingLoader::import('system.screen');
VikBookingLoader::import('system.feedback');
VikBookingLoader::import('system.rssfeeds');
VikBookingLoader::import('system.assets');
/**
 * @since 	1.4.1 class VikRequest is no longer an adapter.
 */
VikBookingLoader::import('system.request');
//
VikBookingLoader::import('wordpress.application');

/**
 * @since 	1.2.0 	include class JViewVikBooking that extends JViewBaseVikBooking
 * 					to provide methods for any view instances.
 */
VikBookingLoader::registerAlias('view.vbo', 'viewvbo');
VikBookingLoader::import('helpers.viewvbo', VBO_SITE_PATH);
    
/**
 * Added support to the plugin libraries autoloader.
 * 
 * @since 1.5
 */
VikBookingLoader::import('helpers.src.autoload', VBO_ADMIN_PATH);
