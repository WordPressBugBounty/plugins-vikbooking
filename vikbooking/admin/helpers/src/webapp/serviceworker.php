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
 * VikBooking Web App - JS Service Worker handling class.
 * 
 * @since 	1.16.5 (J) - 1.6.5 (WP)
 */
final class VBOWebappServiceworker
{
	/**
	 * Returns the URI to the service worker file path.
	 * 
	 * @param   bool    $rel_origin     true to get the relative path to the origin.
	 * 
	 * @return  string
	 */
	public static function getUri($rel_origin = true)
	{
		$uri = VBO_ADMIN_URI . 'resources/service_worker.js';

		if ($rel_origin) {
			$uri = '/' . ltrim(str_replace(JUri::root(), '', $uri), '/');

			if (VBOPlatformDetection::isWordPress() && VikBooking::isAdmin()) {
				// /wp-content is on the same level as /wp-admin, not inside it
				$uri = '..' . $uri;
			}
		}

		return $uri;
	}

	/**
	 * Returns the scope URI for the service worker.
	 * 
	 * @return  string
	 */
	public static function getScope()
	{
		if (!VikBooking::isAdmin()) {
			return '/';
		}

		return VBO_ADMIN_URI . 'resources/service_worker.js';
	}
}
