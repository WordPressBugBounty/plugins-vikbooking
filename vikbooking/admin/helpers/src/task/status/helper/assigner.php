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
 * Assigner helper trait to use for status change behaviors.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
trait VBOTaskStatusHelperAssigner
{
    /**
     * Attaches the currently logged-in operator to the assignees list
     * of the provided task.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  bool  True whether the user has been assigned, false otherwise.
     */
    public function assignUser(VBOTaskTaskregistry $task)
    {
        // do not go ahead if we are in the back-end
        if (!JFactory::getApplication()->isClient('site')) {
            return false;
        }

        // get logged in operator
        $operator = VikBooking::getOperatorInstance()->getOperatorAccount();

        if (!$operator) {
            return false;
        }

        $assignees = $task->getAssigneeIds();

        // abort in case the operator is already assigned to this task
        if (in_array($operator['id'], $assignees)) {
            return false;
        }

        $assignees[] = $operator['id'];

        try {
            // attach this user to the list of task assignees
            VBOTaskModelTask::getInstance()->update([
                'id' => $task->getID(),
                'assignees' => $assignees,
            ]);
        } catch (Exception $error) {
            // ignore and go ahead
        }

        return true;
    }

    /**
     * Detaches the currently logged-in operator from the assignees list
     * of the provided task.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  bool  True whether the user has been assigned, false otherwise.
     */
    public function unassignUser(VBOTaskTaskregistry $task)
    {
        // do not go ahead if we are in the back-end
        if (!JFactory::getApplication()->isClient('site')) {
            return false;
        }

        // get logged in operator
        $operator = VikBooking::getOperatorInstance()->getOperatorAccount();

        if (!$operator) {
            return false;
        }

        $assignees = $task->getAssigneeIds();

        $index = array_search($operator['id'], $assignees);

        // abort in case the operator is not assigned to this task
        if ($index === false) {
            return false;
        }

        // remove the assignee from the list
        array_splice($assignees, $index, 1);

        try {
            // detach this user from the list of task assignees
            VBOTaskModelTask::getInstance()->update([
                'id' => $task->getID(),
                'assignees' => $assignees,
            ]);
        } catch (Exception $error) {
            // ignore and go ahead
        }

        return true;
    }

    /**
     * Displays some instructions in case the user is interested in having
     * the provided task assigned.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  string
     */
    public function displayAssignable(VBOTaskTaskregistry $task)
    {
        // do not go ahead if we are in the back-end
        if (!JFactory::getApplication()->isClient('site')) {
            return '';
        }

        // get logged in operator
        $operator = VikBooking::getOperatorInstance()->getOperatorAccount();

        if (!$operator) {
            return '';
        }

        $assignees = $task->getAssigneeIds();

        // abort in case the operator is already assigned to this task
        if (in_array($operator['id'], $assignees)) {
            return '';
        }

        /** @var VBOTaskStatusInterface|null */
        $status = $this->findFirstSupportedStatus('accepted', $task);

        if (!$status) {
            // do not display instructions because the "accepted" status is not supported 
            return '';
        }

        return JText::sprintf('VBO_TASK_STATUS_DISPLAY_ASSIGNABLE', $status->getName());
    }

    /**
     * Displays some instructions in case the user is interested in having
     * the provided task no longer assigned.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  string
     */
    public function displayUnassignable(VBOTaskTaskregistry $task)
    {
        // do not go ahead if we are in the back-end
        if (!JFactory::getApplication()->isClient('site')) {
            return '';
        }

        // get logged in operator
        $operator = VikBooking::getOperatorInstance()->getOperatorAccount();

        if (!$operator) {
            return '';
        }

        $assignees = $task->getAssigneeIds();

        // abort in case the operator is not assigned to this task
        if (!in_array($operator['id'], $assignees)) {
            return '';
        }

        /** @var VBOTaskStatusInterface|null */
        $status = $this->findFirstSupportedStatus(['notstarted', 'pending'], $task);

        if (!$status) {
            // do not display instructions because the "notstarted" and "pending" statuses are not supported 
            return '';
        }

        return JText::sprintf('VBO_TASK_STATUS_DISPLAY_UNASSIGNABLE', $status->getName());
    }

    /**
     * Returns an instance of the very first status that is actually supported by the
     * area of the provided task.
     * 
     * @param   array|string         $statuses
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  VBOTaskStatusInterface|null
     */
    protected function findFirstSupportedStatus($statuses, VBOTaskTaskregistry $task)
    {
        $area = $task->getArea();

        if (!$area) {
            return null;
        }

        $statuses = (array) $statuses;

        // obtain all the provided statuses that are actually supported by the area of the task
        $matching = array_intersect($statuses, $area->getStatuses());

        if (!$matching) {
            return null;
        }

        // instantiate the first matching status
        return VBOFactory::getTaskManager()->getStatusTypeInstance(reset($matching));
    }
}
