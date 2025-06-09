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
 * Interface used to look for any matching changes.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
interface VBOHistoryDetector
{
    /**
     * Returns the event identifier.
     * 
     * @return  string
     */
    public function getEvent();

    /**
     * Checks whether the observed target as changed.
     * 
     * @param   object  $prev  The item details before the changes.
     * @param   object  $curr  The item details after the changes.
     * 
     * @return  bool
     */
    public function hasChanged(object $prev, object $curr);

    /**
     * Returns a human-readable string describing the changes made.
     * 
     * @return  string
     */
    public function describe();

    /**
     * Returns an icon to display for the event.
     * 
     * @return  string
     */
    public function getIcon();
}
