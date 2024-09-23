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
 * VikBooking Web App Manifest handling class.
 * 
 * @since 	1.16.5 (J) - 1.6.5 (WP)
 */
final class VBOWebappManifest
{
	/**
	 * Loads the Web App Manifest JSON file and attaches it to the document.
	 * 
	 * @return 	bool
	 */
	public static function load()
	{
		if (!is_file(self::getPath())) {
			// attempt to build the manifest
			try {
				self::build();
			} catch (Exception $e) {
				// manifest shall not be built or loaded
				return false;
			}
		}

		// access the document
		$document = JFactory::getDocument();

		// prevent conflicts
		if (!method_exists($document, 'addHeadLink')) {
			return false;
		}

		// attach the link tag to the document head
		$document->addHeadLink(self::getUri(), 'manifest');

		return true;
	}

	/**
	 * Attempts to build the manifest file. This will run either when the manifest file is missing
	 * (could be manually deleted), or when the configuration settings get saved. This is to allow
	 * third-party plugins to manipulate the manifest file easily.
	 * 
	 * @return 	bool
	 * 
	 * @throws 	Exception
	 */
	public static function build()
	{
		// default Web App name
		$web_app_name = JText::translate('COM_VIKBOOKING');
		if ($web_app_name == 'COM_VIKBOOKING') {
			$web_app_name = 'VikBooking';
		}

		// host name
		$host_name = JURI::getInstance()->toString(['host']);

		// manifest default payload
		$data = [
			'name' 		  => $web_app_name,
			'display' 	  => 'standalone',
			'short_name'  => $host_name,
			'description' => $web_app_name . ' - ' . $host_name,
			'start_url'   => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&webapp=1', $xhtml = false),
			'icons' 	  => [
				[
					'src' 	=> VBO_ADMIN_URI . 'resources/channels/touch-icon-192.png',
					'sizes' => '192x192',
					'type' 	=> 'image/png',
				],
			],
		];

		/**
		 * Trigger event to allow third party plugins to overwrite the Web App manifest payload.
		 * It is possible to throw an Exception in order to prevent the Web App manifest from being generated.
		 */
		VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeBuildWebAppManifestVikBooking', [&$data]);

		if (!$data) {
			throw new Exception('Invalid manifest payload', 500);
		}

		if (!JFile::write(self::getPath(), json_encode($data, JSON_PRETTY_PRINT))) {
			throw new Exception('Could not write the Web App manifest file', 500);
		}

		return true;
	}

	/**
	 * Returns the path to the manifest file according to the platform.
	 * 
	 * @return 	string
	 */
	private static function getPath()
	{
		static $path = null;

		if ($path !== null) {
			return $path;
		}

		if (VBOPlatformDetection::isWordPress()) {
			$path = implode(DIRECTORY_SEPARATOR, [VBO_MEDIA_ASSETS_PATH, 'manifest.json']);
		} else {
			$path = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'resources', 'manifest.json']);
		}

		return $path;
	}

	/**
	 * Returns the URI to the manifest file according to the platform.
	 * 
	 * @param 	bool 	$cached 	true to append the file modification timestamp to the URL for caching.
	 * 
	 * @return 	string
	 */
	private static function getUri($cached = true)
	{
		$cache_id = '';

		if ($cached) {
			$cache_id = '?' . @filemtime(self::getPath());
		}

		if (VBOPlatformDetection::isWordPress()) {
			return VBO_MEDIA_ASSETS_URI . 'manifest.json' . $cache_id;
		}

		return VBO_ADMIN_URI . 'resources/manifest.json' . $cache_id;
	}
}
