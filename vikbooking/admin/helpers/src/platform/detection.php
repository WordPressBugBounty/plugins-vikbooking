<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Detects the platform on which the system is running.
 * 
 * @since 	1.16.1 (J) - 1.6.1 (WP)
 */
final class VBOPlatformDetection
{
	/**
	 * @var 	string
	 */
	private static $platform = '';

	/**
	 * Tells whether the current platform is WordPress.
	 * 
	 * @return 	bool
	 */
	public static function isWordPress()
	{
		if (!static::$platform) {
			static::detect();
		}

		return (static::$platform === 'wordpress');
	}

	/**
	 * Tells whether the current platform is Joomla.
	 * 
	 * @return 	bool
	 */
	public static function isJoomla()
	{
		if (!static::$platform) {
			static::detect();
		}

		return (static::$platform === 'joomla');
	}

	/**
	 * Tells whether the current execution is made by the WordPress REST API framework.
	 * Useful to tell, for example, if the Gutenberg preview is calling the REST endpoint.
	 * 
	 * @return 	bool
	 * 
	 * @since 	1.16.7 (J) - 1.6.7 (WP)
	 */
	public static function isWordPressRestApi()
	{
		$requestUri = JFactory::getApplication()->input->server->getString('REQUEST_URI', '');

		if (static::isWordPress()) {
			if (strpos($requestUri, trailingslashit(rest_get_url_prefix())) !== false || JUri::getInstance($requestUri)->hasVar('rest_route')) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detects the name of the platform on which we are running the software.
	 * 
	 * @return 	void
	 */
	private static function detect()
	{
		if (defined('ABSPATH') && function_exists('wp_die')) {
			static::$platform = 'wordpress';
		} else {
			static::$platform = 'joomla';
		}
	}
}
