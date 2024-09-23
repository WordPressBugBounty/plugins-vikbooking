<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Register CMS HTML helpers.
 * 
 * @since 1.5
 */
JHtml::addIncludePath(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'html');

/**
 * Libraries autoloader.
 * 
 * @since 1.5
 */
spl_autoload_register(function($class)
{
	// handle base VCM library
	if ($class === 'VikChannelManager')
	{
		return (bool) @include_once implode(DIRECTORY_SEPARATOR, [VCM_SITE_PATH, 'helpers', 'lib.vikchannelmanager.php']);
	}

	// handle config VCM library
	if ($class === 'VikChannelManagerConfig')
	{
		return (bool) @include_once implode(DIRECTORY_SEPARATOR, [VCM_SITE_PATH, 'helpers', 'vcm_config.php']);
	}

	// handle base VBO library
	if ($class === 'VikBooking')
	{
		return (bool) require_once implode(DIRECTORY_SEPARATOR, [VBO_SITE_PATH, 'helpers', 'lib.vikbooking.php']);
	}

	$guess_vbo = stripos($class, 'VBO');
	$guess_vcm = stripos($class, 'VCM');

	if ($guess_vbo !== 0 && $guess_vcm !== 0)
	{
		// ignore if we are loading an outsider
		return false;
	}

	// get the class prefix and base path
	if ($guess_vbo === 0)
	{
		$class_prefix = 'VBO';
		$class_bpath  = dirname(__FILE__);
	}
	else
	{
		$class_prefix = 'VCM';
		$class_bpath  = str_replace('vikbooking', 'vikchannelmanager', dirname(__FILE__));
	}

	// remove prefix from class
	$tmp = preg_replace("/^$class_prefix/", '', $class);
	// separate camel-case intersections
	$tmp = preg_replace("/([a-z])([A-Z])/", addslashes('$1' . DIRECTORY_SEPARATOR . '$2'), $tmp);

	// build path from which the class should be loaded
	$path = $class_bpath . DIRECTORY_SEPARATOR . strtolower($tmp) . '.php';

	// make sure the file exists
	if (is_file($path))
	{
		// include file and check if the class is now available
		if ((include_once $path) && (class_exists($class) || interface_exists($class) || trait_exists($class)))
		{
			return true;
		}
	}

	return false;
});
