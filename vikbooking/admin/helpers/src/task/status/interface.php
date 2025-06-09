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
 * Task status interface.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
interface VBOTaskStatusInterface
{
    /**
     * Returns the enum value for the task status used to build the class name.
     * 
     * @return  string  A unique enum identifier.
     */
    public function getEnum();

    /**
     * Returns the group enum value to which the task status belong.
     * 
     * @return  string  The group enumeration value.
     */
    public function getGroupEnum();

    /**
     * Returns the name of the task status.
     * 
     * @return  string  The status readable name.
     */
    public function getName();

    /**
     * Returns the color enum for the current task status.
     * 
     * @return  string  The task status color enumeration.
     */
    public function getColor();

    /**
     * Returns the task status group ordering value.
     * 
     * @return  int  The status group ordering value.
     */
    public function getOrdering();

    /**
     * This function should be invoked whenever the provided task applies this status.
     * 
     * @param   int  $taskId
     * 
     * @return  void
     */
    public function apply(int $taskId);

    /**
     * Displays some extra information about this status.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  string
     */
    public function display(VBOTaskTaskregistry $task);
}
