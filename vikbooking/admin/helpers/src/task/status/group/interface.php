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
 * Task status group interface.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
interface VBOTaskStatusGroupInterface
{
    /**
     * Returns the enum value for the task status group used to build the class name.
     * 
     * @return  string  A unique enum identifier.
     */
    public function getEnum();

    /**
     * Returns the task status group ordering value.
     * 
     * @return  int  The status group ordering value.
     */
    public function getOrdering();

    /**
     * Returns the name of the task status group.
     * 
     * @return  string  The status group readable name.
     */
    public function getName();
}
