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

/**
 * Task manager chat context class.
 * 
 * @since 1.8
 */
class VBOChatContextTask extends VBOChatContextaware
{
    /** @var object */
    private $task;

    /**
     * @inheritDoc
     */
    final public function getAlias()
    {
        return 'task';
    }

    /**
     * @inheritDoc
     */
    public function getRecipients()
    {
        $recipients = [];

        // adds support to administrator (create a placeholder to simulate a false login)
        $recipients[] = new VBOChatUserAdmin((object) [
            'id' => 0,
            'guest' => false,
            'name' => '',
        ]);

        if ($task = $this->getTask()) {
            // obtain all the IDs of the operators involved in this context
            $ids = $task->getAssigneeIds();

            // fetch information for all the operators
            foreach (VikBooking::getOperatorInstance()->getAll($ids) as $operator) {
                $recipients[] = new VBOChatUserOperator($operator);
            }
        }

        return $recipients;
    }

    /**
     * @inheritDoc
     */
    public function useAssets()
    {
        $document = JFactory::getDocument();
        $document->addScript(VBO_SITE_URI . 'resources/chat/task.js');

        JText::script('VBSAVE');
        JText::script('VBO_TASK');
        JText::script('VBO_NEW_TASK');
        JText::script('VBO_PROJECTS_AREAS');
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        if ($task = $this->getTask()) {
            return $task->getTitle();
        }

        return sprintf('<em>Task #%d (deleted)</em>', $this->getID());
    }

    /**
     * @inheritDoc
     */
    public function getActions()
    {
        $actions = [];

        if (JFactory::getApplication()->isClient('site')) {
            return $actions;
        }

        // fetch task details
        $task = $this->getTask();

        if (!$task) {
            return $actions;
        }

        // obtain all the statuses supported by the area to which the task belongs
        $statuses = VBOTaskArea::getRecordInstance($task->getAreaID())->getStatusElements($task->getStatus());

        // add option group label
        $actions[] = [
            'namespace' => 'task.btngroup',
            'text' => JText::translate('VBSTATUS'),
            'disabled' => true,
            'class' => 'btngroup',
        ];

        foreach ($statuses as $group) {
            // iterate over the statuses of this group
            foreach ($group['elements'] as $statusType) {
                // push status element
                $actions[] = [
                    'namespace' => 'task.status',
                    'id' => $statusType['id'],
                    'text' => $statusType['text'],
                    'color' => $statusType['color'],
                    'selected' => $statusType['id'] == $task->getStatus(),
                ];
            }
        }

        $actions[count($actions) - 1]['separator'] = true;

        if ($bookingId = $task->getBookingId()) {
            // See booking details
            $actions[] = [
                'namespace' => 'task.booking',
                'text' => JText::translate('VBO_CTA_SEE'),
                'icon' => VikBookingIcons::i('calendar'),
                'booking' => (int) $bookingId,
            ];
        }

        return array_merge($actions, [
            // See task details
            [
                'namespace' => 'task.see',
                'text' => JText::translate('VBO_CHAT_SEE_TASK'),
                'icon' => VikBookingIcons::i('eye'),
            ],
            // Create new task
            [
                'namespace' => 'task.new',
                'text' => JText::translate('VBO_NEW_TASK'),
                'icon' => VikBookingIcons::i('plus'),
                'separator' => true,
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getURL()
    {
        return VBOFactory::getPlatform()->getUri()->route('index.php?option=com_vikbooking&view=operators&tool=task_manager&filters[calendar_type]=taskdetails&filters[task_id]=' . $this->getID());
    }

    /**
     * @inheritDoc
     */
    public function can(string $scope, VBOChatUser $user)
    {
        $task = $this->getTask();

        if (!$task) {
            // task not found...
            return false;
        }

        // obtain all the users currently assigned to this task
        $assignees = $task->getAssigneeIds();

        // can perform the action only if the task has no assignee or
        // whethet the current user has been already assigned
        return !$assignees || in_array($user->getID(), $assignees);
    }

    /**
     * Returns the details of this task context.
     * 
     * @return  object|null
     */
    protected function getTask()
    {
        if ($this->task === null) {
            // fetch the details of the task and internally cache them
            $this->task = VBOTaskTaskregistry::getRecordInstance($this->getID());

            if (!$this->task->getID()) {
                // the task doesn't exist any longer
                $this->task = false;
            }
        }

        return $this->task;
    }
}
