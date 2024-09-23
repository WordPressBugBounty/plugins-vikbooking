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

JPluginHelper::importPlugin('vikbooking');

/**
 * Factory application class.
 *
 * @since 1.5
 */
final class VBOFactory
{
	/**
	 * Application configuration handler.
	 *
	 * @var VBOConfigRegistry
	 */
	private static $config;

	/**
	 * Application platform handler.
	 *
	 * @var VBOPlatformInterface
	 */
	private static $platform;

	/**
	 * Cron jobs factory instance.
	 * 
	 * @var VBOCronFactory
	 */
	private static $cronFactory;

	/**
	 * Notification Center handler instance.
	 * 
	 * @var VBONotificationCenter
	 */
	private static $notificationCenter;

	/**
	 * Class constructor.
	 * @private This object cannot be instantiated. 
	 */
	private function __construct()
	{
		// never called
	}

	/**
	 * Class cloner.
	 * @private This object cannot be cloned.
	 */
	private function __clone()
	{
		// never called
	}

	/**
	 * Returns the current configuration object.
	 *
	 * @return 	VBOConfigRegistry
	 */
	public static function getConfig()
	{
		// check if config class is already instantiated
		if (is_null(static::$config))
		{
			// cache instantiation
			static::$config = new VBOConfigRegistryDatabase([
				'db' => JFactory::getDbo(),
			]);
		}

		return static::$config;
	}

	/**
	 * Returns the current platform handler.
	 *
	 * @return 	VBOPlatformInterface
	 */
	public static function getPlatform()
	{
		// check if platform class is already instantiated
		if (is_null(static::$platform))
		{
			if (VBOPlatformDetection::isWordPress())
			{
				// running WordPress platform
				static::$platform = new VBOPlatformOrgWordpress();
			}
			else
			{
				// running Joomla platform
				static::$platform = new VBOPlatformOrgJoomla();
			}
		}

		return static::$platform;
	}

	/**
	 * Returns the current cron factory.
	 *
	 * @return 	VBOCronFactory
	 * 
	 * @since   1.5.10
	 */
	public static function getCronFactory()
	{
		// check if cron factory class is already instantiated
		if (is_null(static::$cronFactory))
		{
			// create cron factory class and register the default folder
			static::$cronFactory = new VBOCronFactory;
			static::$cronFactory->setIncludePaths(VBO_ADMIN_PATH . DIRECTORY_SEPARATOR . 'cronjobs');

			/**
			 * Trigger hook to allow third-party plugin to register custom folders in which
			 * VikBooking should look for the creation of new cron job instances.
			 * 
			 * In example:
			 * $factory->addIncludePath($path);
			 * $factory->addIncludePaths([$path1, $path2, ...]);
			 * 
			 * @param   VBOCronFactory  $factory  The cron jobs factory.
			 * 
			 * @return  void
			 * 
			 * @since   1.5.10
			 */
			VBOFactory::getPlatform()->getDispatcher()->trigger('onCreateCronJobsFactoryVikBooking', [static::$cronFactory]);
		}

		return static::$cronFactory;
	}

	/**
	 * Returns the current cron notification center instance.
	 *
	 * @return 	VBONotificationCenter
	 * 
	 * @since   1.6.8
	 */
	public static function getNotificationCenter()
	{
		// check if the notification center class is already instantiated
		if (is_null(static::$notificationCenter))
		{
			// create Notification Center class
			static::$notificationCenter = new VBONotificationCenter;
		}

		return static::$notificationCenter;
	}
}
