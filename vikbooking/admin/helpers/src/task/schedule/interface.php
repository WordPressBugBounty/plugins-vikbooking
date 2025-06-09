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
 * Task schedule interface.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
interface VBOTaskScheduleInterface
{
    /**
     * Returns the task schedule type enumeration.
     * 
     * @return  string
     */
    public function getType();

    /**
     * Returns the task schedule ordering value.
     * 
     * @return  int
     */
    public function getOrdering();

    /**
     * Returns the task schedule due date-time object(s) list, if any.
     * 
     * @return  DateTime[]  The task schedule due date-time object(s) list, if any.
     */
    public function getDates();

    /**
     * Returns the task schedule description.
     * 
     * @param   array  $info     Optional associative list of task info.
     * @param   int    $counter  Optional schedule date counter (index).
     * 
     * @return  string
     */
    public function getDescription(array $info = [], int $counter = 0);
}
