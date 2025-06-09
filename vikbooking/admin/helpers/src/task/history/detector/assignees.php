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
 * Task assignees changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOTaskHistoryDetectorAssignees extends VBOHistoryDetectorAssignees
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct('assignees');
    }

    /**
     * @inheritDoc
     */
    protected function describeAddedItems(array $added)
    {
        return parent::describeAddedItems($this->mapAssignees($added));
    }

    /**
     * @inheritDoc
     */
    protected function describeRemovedItems(array $removed)
    {
        return parent::describeRemovedItems($this->mapAssignees($removed));
    }

    /**
     * Converts the assignee IDs into names.
     * 
     * @param   int[]  $list
     * 
     * @return  string[]
     */
    private function mapAssignees(array $list)
    {
        $operatorInstance = VikBooking::getOperatorInstance();

        // convert IDs into names
        return array_map(function($assigneeId) use ($operatorInstance) {
            $operator = $operatorInstance->getOne((int) $assigneeId);

            if ($operator) {
                return '<strong>' . trim($operator['first_name'] . ' ' . $operator['last_name']) . '</strong>';
            }

            return '#' . $assigneeId;
        }, $list);
    }
}
