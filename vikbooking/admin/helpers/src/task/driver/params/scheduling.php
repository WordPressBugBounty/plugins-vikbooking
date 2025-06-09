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
 * Task driver scheduling params trait.
 * 
 * @since 1.18.0 (J) - 1.8.0 (WP)
 */
trait VBOTaskDriverParamsScheduling
{
    /**
     * Returns the scheduling params:
     * 
     * - auto schedule
     * - scheduling frequency
     * - auto assignment
     * 
     * @return  array
     * 
     * @see VBOTaskDriverinterface::getParams()
     */
    public function useSchedulingParams()
    {
        return [
            'autoschedule' => [
                'type'    => 'checkbox',
                'default' => 1,
                'label'   => JText::translate('VBO_AUTO_SCHEDULING'),
                'help'    => JText::translate('VBO_AUTO_SCHEDULING_BOOKINGS_HELP'),
            ],
            'scheduling' => [
                'type'    => 'select',
                'label'   => JText::translate('VBO_SCHEDULING') . ' / ' . JText::translate('VBO_FREQUENCY'),
                'options' => [
                    'turnover' => JText::translate('VBO_TURNOVER') . ' (' . JText::translate('VBRELEASEAT') . ')',
                    'daily'    => JText::translate('VBO_DAILY'),
                    'every2'   => JText::translate('VBO_EVERY_2_DAYS'),
                    'every3'   => JText::translate('VBO_EVERY_3_DAYS'),
                    'weekly'   => JText::translate('VBO_WEEKLY'),
                    'monthly'  => JText::translate('VBO_MONTHLY'),
                ],
                'default' => ['turnover'],
                'multiple' => true,
                'assets' => true,
                'asset_options' => [
                    'allowClear'  => true,
                ],
            ],
            'autoassignment' => [
                'type'    => 'checkbox',
                'default' => 1,
                'label'   => JText::translate('VBO_AUTO_ASSIGNMENT'),
                'help'    => JText::translate('VBO_AUTO_ASSIGNMENT_HELP'),
            ],
        ];
    }

    /**
     * @inheritDoc
     * 
     * @see VBOTaskDriverinterface::scheduleBookingConfirmation()
     */
    public function scheduleBookingConfirmation(VBOTaskBooking $booking)
    {
        if ($booking->isClosure() || $booking->isOverbooking() || !$booking->isConfirmed()) {
            // do nothing when we're not dealing with a real confirmed and accepted reservation
            return;
        }

        // get task scheduling and assignment settings
        $scheduling = (array) $this->getSetting('scheduling');
        $autoassignment = (bool) $this->getSetting('autoassignment');

        if (!((bool) $this->getSetting('autoschedule')) || !$scheduling) {
            // automatic scheduling is disabled, or no scheduling intervals defined
            return;
        }

        // schedule tasks upon booking confirmation
        $created = $this->createBookingConfirmationTasks($booking, [
            'scheduling'     => $scheduling,
            'autoassignment' => $autoassignment,
        ]);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOTaskDriverinterface::scheduleBookingAlteration()
     */
    public function scheduleBookingAlteration(VBOTaskBooking $booking)
    {
        if ($booking->isClosure() || $booking->isOverbooking() || !$booking->isConfirmed()) {
            // do nothing when we're not dealing with a real confirmed and accepted reservation
            return;
        }

        if (!$booking->detectAlterations()) {
            // do nothing when no significant changes were made to the booking
            return;
        }

        // re-scheduling tasks during a booking modification for nights and/or listings
        // requires a cancellation and a re-creation of all tasks for better accuracy

        // delete all previously scheduled tasks within the current project/area and driver
        $this->scheduleBookingCancellation($booking);

        // re-schedule the proper tasks as new
        $this->scheduleBookingConfirmation($booking);
    }

    /**
     * @inheritDoc
     * 
     * This driver will always cancel its previously scheduled cleaning tasks for a booking.
     * 
     * @see VBOTaskDriverinterface::scheduleBookingCancellation()
     */
    public function scheduleBookingCancellation(VBOTaskBooking $booking)
    {
        if ($booking->isClosure() || (!$booking->isCancelled() && !$booking->getPrevious())) {
            // do nothing when we're not dealing with a real booking cancellation or modification
            return;
        }

        // access the task model
        $model = VBOTaskModelTask::getInstance();

        // get all records that belong to this project/area and booking ID
        $records = $model->getItems([
            'id_area'  => [
                'value' => $this->getAreaID(),
            ],
            'id_order' => [
                'value' => $booking->getID(),
            ],
        ]);

        $taskIds = array_column($records, 'id');
        if (!$taskIds) {
            // no tasks to delete
            return;
        }

        // delete all the involved tasks
        if ($model->delete($taskIds)) {
            // some records were deleted
            foreach ($records as $record) {
                // register the deleted task within the collector
                $this->getCollector()->register((array) $record, 'cancelled');
            }
        }
    }
}
