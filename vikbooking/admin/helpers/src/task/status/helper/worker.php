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
 * Worker helper trait to use for status change behaviors.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
trait VBOTaskStatusHelperWorker
{
    /**
     * Mark the task as in progress.
     * 
     * @param   VBOTaskTaskregistry $task
     * 
     * @return  void
     */
    public function startWork(VBOTaskTaskregistry $task)
    {
        $now = JFactory::getDate();

        $data = [
            'id' => $task->getID(),
            'workstartedon' => $now->toSql(),
            'finishedby' => 0,
        ];

        if (!$task->get('beganon')) {
            // work started right now
            $data['beganon'] = $now->toSql();
        }

        if (!$task->get('beganby')) {
            // work started by the current user
            $data['beganby'] = $this->getWorkerID();
        }

        try {
            // update the task
            VBOTaskModelTask::getInstance()->update($data);
        } catch (Exception $error) {
            // ignore and go ahead
        }
    }

    /**
     * Mark the task as in pause.
     * 
     * @param   VBOTaskTaskregistry $task
     * 
     * @return  void
     */
    public function pauseWork(VBOTaskTaskregistry $task)
    {
        try {
            // update the task
            VBOTaskModelTask::getInstance()->update([
                'id' => $task->getID(),
                'workstartedon' => JFactory::getDate()->toSql(),
                'realduration' => $this->calculateTotalDuration($task),
            ]);
        } catch (Exception $error) {
            // ignore and go ahead
        }
    }

    /**
     * Mark the task as finished.
     * 
     * @param   VBOTaskTaskregistry $task
     * 
     * @return  void
     */
    public function finishWork(VBOTaskTaskregistry $task)
    {
        $now = JFactory::getDate();

        $data = [
            'id' => $task->getID(),
            'workstartedon' => $now->toSql(),
            'realduration' => $this->calculateTotalDuration($task),
        ];

        if (!$task->get('finishedon')) {
            // work finished right now
            $data['finishedon'] = $now->toSql();
        }

        if (!$task->get('finishedby')) {
            // work finished by the current user
            $data['finishedby'] = $this->getWorkerID();
        }

        try {
            // update the task
            VBOTaskModelTask::getInstance()->update($data);
        } catch (Exception $error) {
            // ignore and go ahead
        }
    }

    /**
     * Displays the elsapsed duration for the ongoing work.
     * 
     * @param   VBOTaskTaskregistry  $task
     * @param   bool                 $paused
     * 
     * @return  string
     */
    public function displayOngoingWork(VBOTaskTaskregistry $task, bool $paused = false)
    {
        if ($paused) {
            // use elapsed duration
            $duration = $task->get('realduration');
        } else {
            // calculate duration on the fly
            $duration = $this->calculateTotalDuration($task);
        }

        if ($duration < 60) {
            return JText::translate('VBO_TASK_STATUS_DISPLAY_STARTED');
        }

        return JText::sprintf('VBO_TASK_STATUS_DISPLAY_ONGOING', $this->formatSeconds($duration));
    }

    /**
     * Displays the elsapsed duration for the finished work.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  string
     */
    public function displayFinishedWork(VBOTaskTaskregistry $task)
    {
        // use elapsed duration
        $duration = $task->get('realduration');

        if ($duration < 60) {
            // do not display anything in case the duration is less than a minute
            return '';
        }

        return JText::sprintf('VBO_TASK_STATUS_DISPLAY_FINISHED', $this->formatSeconds($duration));
    }

    /**
     * Calculate the total elapsed seconds since the work started.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  int
     */
    protected function calculateTotalDuration(VBOTaskTaskregistry $task)
    {
        $duration = (int) $task->get('realduration', 0);

        if ($task->get('finishedby')) {
            // task already finished, do not calculate extra time
            return $duration;
        }

        // obtain the date and time when the work started
        $workStartedOn = $task->get('workstartedon');

        if (!$workStartedOn) {
            // work never started, use the began date and time
            $workStartedOn = $task->get('beganon');
        }

        if (!$workStartedOn) {
            // work never began, use the due date and time
            $workStartedOn = $task->get('dueon');
        }

        if ($workStartedOn) {
            $workStartedOn = JFactory::getDate($workStartedOn);
            $now = JFactory::getDate('now');

            // calculate the difference between the current time and the start working time
            if ($now > $workStartedOn) {
                $diff = $workStartedOn->diff($now);
                $duration += $diff->s + $diff->i * 60 + $diff->h * 3600 + $diff->days * 86400;
            }
        }

        return $duration;
    }

    /**
     * Returns the identifier of the currently logged-in user.
     * 
     * @return  int  The user ID.
     */
    protected function getWorkerID()
    {
        if (JFactory::getApplication()->isClient('administrator')) {
            // return the CMS user ID
            return JFactory::getUser()->id;
        }

        // get logged in operator
        $operator = VikBooking::getOperatorInstance()->getOperatorAccount();

        if (!$operator) {
            // return the CMS user ID, if any
            return JFactory::getUser()->id;
        }

        return (int) $operator['id'];
    }

    /**
     * Helper method to format the specified seconds to the closest unit.
     * For example, 150 minutes will be formatted as "2 hours, 30 minutes".
     *
     * @param   int     $seconds
     *
     * @return  string
     */
    protected function formatSeconds(int $seconds)
    {
        // convert seconds in minutes
        $minutes = floor($seconds / 60);
        
        $units = [];

        while ($minutes >= 60) {
            if ($minutes >= 1440) {
                // calculate days
                $units[] = JText::plural('VBO_N_DAYS', floor($minutes / 1440));

                // recalculate remaining minutes
                $minutes = $minutes % 1440;
            } else {
                // calculate hours
                $units[] = JText::plural('VBO_N_HOURS', floor($minutes / 60));

                // recalculate remaining minutes
                $minutes = $minutes % 60;
            }
        }
        
        if ($minutes > 0) {
            $units[] = JText::plural('VBO_N_MINUTES', $minutes);
        }
            
        return implode(', ', $units);
    }
}
