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

// Software version
define('VIKBOOKING_SOFTWARE_VERSION', '1.6.9');

// Base path
define('VIKBOOKING_BASE', dirname(__FILE__));

// Libraries path
define('VIKBOOKING_LIBRARIES', VIKBOOKING_BASE . DIRECTORY_SEPARATOR . 'libraries');

// Languages path
defined('VIKBOOKING_LANG') or define('VIKBOOKING_LANG', basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'languages');
/**
 * The admin and site languages are no more used by the plugin.
 *
 * @deprecated 	1.0.2
 * @see 		these constants won't be removed as some classes of VCM may need them.
 */
defined('VIKBOOKING_SITE_LANG') or define('VIKBOOKING_SITE_LANG', basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'language');
defined('VIKBOOKING_ADMIN_LANG') or define('VIKBOOKING_ADMIN_LANG', basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'language');

// Assets URI
define('VIKBOOKING_SITE_ASSETS_URI', plugin_dir_url(__FILE__) . 'site/resources/');
define('VIKBOOKING_ADMIN_ASSETS_URI', plugin_dir_url(__FILE__) . 'admin/resources/');

// Debug flag
define('VIKBOOKING_DEBUG', false);

// URI Constants for admin and site sections (with trailing slash)
defined('VBO_ADMIN_URI') or define('VBO_ADMIN_URI', plugin_dir_url(__FILE__).'admin/');
defined('VBO_SITE_URI') or define('VBO_SITE_URI', plugin_dir_url(__FILE__).'site/');
defined('VBO_BASE_URI') or define('VBO_BASE_URI', plugin_dir_url(__FILE__));
defined('VBO_MODULES_URI') or define('VBO_MODULES_URI', plugin_dir_url(__FILE__));
defined('VBO_ADMIN_URI_REL') or define('VBO_ADMIN_URI_REL', plugin_dir_url(__FILE__).'admin/');
defined('VBO_SITE_URI_REL') or define('VBO_SITE_URI_REL', plugin_dir_url(__FILE__).'site/');
defined('VCM_ADMIN_URI') or define('VCM_ADMIN_URI', str_replace('vikbooking/admin', 'vikchannelmanager/admin', VBO_ADMIN_URI));
defined('VCM_SITE_URI') or define('VCM_SITE_URI', str_replace('vikbooking/site', 'vikchannelmanager/site', VBO_SITE_URI));

// Path Constants for admin and site sections (with NO trailing directory separator)
defined('VBO_ADMIN_PATH') or define('VBO_ADMIN_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'admin');
defined('VBO_SITE_PATH') or define('VBO_SITE_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'site');
defined('VCM_ADMIN_PATH') or define('VCM_ADMIN_PATH', str_replace('vikbooking' . DIRECTORY_SEPARATOR . 'admin', 'vikchannelmanager' . DIRECTORY_SEPARATOR . 'admin', VBO_ADMIN_PATH));
defined('VCM_SITE_PATH') or define('VCM_SITE_PATH', str_replace('vikbooking' . DIRECTORY_SEPARATOR . 'site', 'vikchannelmanager' . DIRECTORY_SEPARATOR . 'site', VBO_SITE_PATH));

// Other Constants that may not be available in the framework
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

// default paths and URIs
$customer_upload_base_path = VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'resources';
$customer_upload_base_uri  = VBO_ADMIN_URI . 'resources/';
$media_upload_base_path    = $customer_upload_base_path;
$media_upload_base_uri 	   = $customer_upload_base_uri;
$media_assets_base_path    = $customer_upload_base_path;
$media_assets_base_uri 	   = $customer_upload_base_uri;

$upload_dir = wp_upload_dir();
if (is_array($upload_dir) && !empty($upload_dir['basedir']) && !empty($upload_dir['baseurl'])) {
	// define proper values for the customer documents
	$customer_upload_base_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'vikbooking' . DIRECTORY_SEPARATOR . 'customerdocs';
	$customer_upload_base_uri  = rtrim($upload_dir['baseurl'], '/') . '/' . 'vikbooking' . '/' . 'customerdocs' . '/';

	// define proper values for the media directory
	$media_upload_base_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'vikbooking' . DIRECTORY_SEPARATOR . 'media';
	$media_upload_base_uri 	= rtrim($upload_dir['baseurl'], '/') . '/' . 'vikbooking' . '/' . 'media' . '/';

	// define proper values for the media assets (i.e. Web App Manifest)
	$media_assets_base_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'vikbooking';
	$media_assets_base_uri  = rtrim($upload_dir['baseurl'], '/') . '/' . 'vikbooking' . '/';
}

/**
 * We define the base path constant for the upload dir
 * used to upload the customer documents onto the sub-dirs.
 * 
 * @since 	1.3.0
 */
defined('VBO_CUSTOMERS_PATH') or define('VBO_CUSTOMERS_PATH', $customer_upload_base_path);
defined('VBO_CUSTOMERS_URI') or define('VBO_CUSTOMERS_URI', $customer_upload_base_uri);

/**
 * We define the base path and URI for the media dir.
 * 
 * @since 	1.5.0
 */
defined('VBO_MEDIA_PATH') or define('VBO_MEDIA_PATH', $media_upload_base_path);
defined('VBO_MEDIA_URI') or define('VBO_MEDIA_URI', $media_upload_base_uri);

/**
 * We define the base path and URI for the media assets.
 * 
 * @since 	1.6.5
 */
defined('VBO_MEDIA_ASSETS_PATH') or define('VBO_MEDIA_ASSETS_PATH', $media_assets_base_path);
defined('VBO_MEDIA_ASSETS_URI') or define('VBO_MEDIA_ASSETS_URI', $media_assets_base_uri);

/**
 * Site pre-process flag.
 * When this flag is enabled, the plugin will try to dispatch the
 * site controller within the "init" action. This is made by 
 * fetching the shortcode assigned to the current URI.
 *
 * By disabling this flag, the site controller will be dispatched 
 * with the headers already sent.
 */
define('VIKBOOKING_SITE_PREPROCESS', true);
