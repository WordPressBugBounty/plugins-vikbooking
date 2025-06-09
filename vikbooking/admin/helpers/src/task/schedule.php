<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Task schedule helper implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskSchedule
{
    /**
     * @var  array
     */
    private static $types = [];

    /**
     * @var  string
     */
    private static $taskScheduleClassPrefix = 'VBOTaskScheduleType';

    /**
     * Attempts to return the task schedule for the requested interval and booking.
     * 
     * @param   string          $schedule   The schedule enumeration type.
     * @param   VBOTaskBooking  $booking    The current task booking registry.
     * 
     * @return  ?VBOTaskScheduleInterface
     */
    public static function getType(string $scheduleEnum, VBOTaskBooking $booking)
    {
        if ($scheduleClassName = static::exists($scheduleEnum)) {
            return new $scheduleClassName($scheduleEnum, $booking);
        }

        return null;
    }

    /**
     * Tells whether the requested task schedule type exists.
     * 
     * @param   string  $schedule   The schedule enumeration type.
     * 
     * @return  string  The schedule type class name or empty string.
     */
    public static function exists(string $scheduleEnum)
    {
        if (!static::$types) {
            // load supported task schedule types
            static::$types = static::load();
        }

        // build class name
        $scheduleClassName = static::$taskScheduleClassPrefix . ucfirst(strtolower($scheduleEnum));

        if (isset(static::$types[$scheduleEnum]) && class_exists($scheduleClassName)) {
            return $scheduleClassName;
        }

        return '';
    }

    /**
     * Loads and returns all the supported task schedule types.
     * 
     * @return  array
     */
    private static function load()
    {
        $types_list  = [];
        $types_base  = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'helpers', 'src', 'task', 'schedule', 'type', '']);
        $types_files = glob($types_base . '*.php');

        /**
         * Trigger event to let other plugins register additional task schedule types.
         *
         * @return  array   A list of supported task schedule types.
         */
        $list = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadTaskManagerScheduleTypes');
        foreach ($list as $chunk) {
            // merge default type files with the returned ones
            $types_files = array_merge($types_files, (array) $chunk);
        }

        foreach ($types_files as $df) {
            // push type file key identifier and set related path
            $type_base_name = basename($df, '.php');
            $types_list[$type_base_name] = $df;
        }

        return $types_list;
    }
}
