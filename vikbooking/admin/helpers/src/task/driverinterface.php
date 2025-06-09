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
 * Task driver interface.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
interface VBOTaskDriverinterface
{
    /**
     * Returns the ID of the task driver used to build the class name.
     * 
     * @return  string  A unique identifier.
     */
    public function getID();

    /**
     * Returns the name of the task driver.
     * 
     * @return  string  The driver readable name.
     */
    public function getName();

    /**
     * Returns the task driver icon.
     * 
     * @return  string  The font-icon class identifier.
     */
    public function getIcon();

    /**
     * Returns the task driver parameters.
     * 
     * @return  array   List of driver parameters.
     */
    public function getParams();

    /**
     * Executes and schedules (eventually) the task(s) upon a booking confirmation.
     * 
     * @param   VBOTaskBooking   $booking    The task booking object.
     * 
     * @return  void
     */
    public function scheduleBookingConfirmation(VBOTaskBooking $booking);

    /**
     * Executes and schedules (eventually) the task(s) upon a booking alteration.
     * 
     * @param   VBOTaskBooking   $booking    The task booking object.
     * 
     * @return  void
     */
    public function scheduleBookingAlteration(VBOTaskBooking $booking);

    /**
     * Executes and schedules (eventually) the task(s) upon a booking cancellation.
     * 
     * @param   VBOTaskBooking   $booking    The task booking object.
     * 
     * @return  void
     */
    public function scheduleBookingCancellation(VBOTaskBooking $booking);
}
