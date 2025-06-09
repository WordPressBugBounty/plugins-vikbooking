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
 * @since   1.15 (J) - 1.5 (WP)
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
     * Crontab simulator instance.
     * 
     * @var VBOCrontabSimulator
     */
    private static $crontabSimulator;

    /**
     * Task Manager instance.
     * 
     * @var VBOTaskManager
     */
    private static $taskManager;

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
     * @return  VBOConfigRegistry
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
     * @return  VBOPlatformInterface
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
     * @return  VBOCronFactory
     * 
     * @since   1.15.10 (J) - 1.5.10 (WP)
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
             * @since   1.15.10 (J) - 1.5.10 (WP)
             */
            VBOFactory::getPlatform()->getDispatcher()->trigger('onCreateCronJobsFactoryVikBooking', [static::$cronFactory]);
        }

        return static::$cronFactory;
    }

    /**
     * Returns the current notification center instance.
     *
     * @return  VBONotificationCenter
     * 
     * @since   1.16.8 (J) - 1.6.8 (WP)
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

    /**
     * Returns the current crontab simulator.
     *
     * @return  VBOCrontabSimulator
     * 
     * @since   1.17 (J) - 1.7 (WP)
     */
    public static function getCrontabSimulator()
    {
        // check if cron factory class is already instantiated
        if (is_null(static::$crontabSimulator)) {
            // set up the preferred semaphore instance
            $semaphore = new VBOCrontabSemaphoreConfig;

            // set up the preferred logger instance
            $logger = new VBOCrontabLoggerFile;

            // instantiate simulator
            static::$crontabSimulator = new VBOCrontabSimulator($semaphore, $logger);

            if (VBOPlatformDetection::isJoomla()) {
                /**
                 * Watch the automatic payments scheduled, if any.
                 * Should run every 5 minutes.
                 */
                static::$crontabSimulator->schedule(new VBOCrontabRunnerAware('pay_schedules_watcher', 5 * 60, function(VBOCrontabLogger $logger) {
                    VBOModelPayschedules::getInstance()->watch();
                }));

                /**
                 * Run performances cleaner.
                 * Should run every week.
                 */
                static::$crontabSimulator->schedule(new VBOCrontabRunnerAware('performance_cleaner', 60 * 60 * 24 * 7, function(VBOCrontabLogger $logger) {
                    VBOPerformanceCleaner::runCheck();
                }));
            }

            if (class_exists('VCMCrontabProvider')) {
                // in case VikChannelManager is installed, schedule the native background tasks
                (new VCMCrontabProvider)->provide(static::$crontabSimulator);
            }

            /**
             * Trigger hook to allow third-party plugins to register custom background tasks.
             * 
             * The following example explains how to schedule a task that runs every hour.
             * 
             * ```
             * $crontab->schedule(new VBOCrontabRunnerAware('unique_id', 60 * 60, function($logger) {
             *   // do stuff
             * }));
             * ```
             * 
             * @param   VBOCrontabSimulator  $crontab  The crontab simulator instance.
             * 
             * @return  void
             * 
             * @since   1.17 (J) - 1.7 (WP)
             */
            VBOFactory::getPlatform()->getDispatcher()->trigger('onSetupCrontabSimulator', [static::$crontabSimulator]);

            // do not run in case we are doing an AJAX request
            if (!strcasecmp(JFactory::getApplication()->input->server->get('HTTP_X_REQUESTED_WITH', ''), 'xmlhttprequest')) {
                static::$crontabSimulator->stop();
            }
        }

        return static::$crontabSimulator;
    }

    /**
     * Returns the current task manager instance.
     *
     * @return  VBOTaskManager
     * 
     * @since   1.18.0 (J) - 1.8.0 (WP)
     */
    public static function getTaskManager()
    {
        // check if the object is already instantiated
        if (is_null(static::$taskManager))
        {
            // create object instance
            static::$taskManager = new VBOTaskManager;
        }

        return static::$taskManager;
    }

    /**
     * Returns a new chat mediator instance.
     *
     * @return  VBOChatMediator
     * 
     * @since   1.18 (J) - 1.8 (WP)
     */
    public static function getChatMediator()
    {
        return new VBOChatMediator(new VBOChatStorageDatabase);
    }
}
