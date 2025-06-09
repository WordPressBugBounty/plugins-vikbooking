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
 * Archiver helper trait to use for status change behaviors.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
trait VBOTaskStatusHelperArchiver
{
    /**
     * Marks the provided task as archived.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  void
     */
    public function archive(VBOTaskTaskregistry $task)
    {
        $this->changeArchivedStatus($task, 1);
    }

    /**
     * Marks the provided task as unarchived.
     * 
     * @param   VBOTaskTaskregistry  $task
     * 
     * @return  void
     */
    public function unarchive(VBOTaskTaskregistry $task)
    {
        $this->changeArchivedStatus($task, 0);
    }

    /**
     * Marks the provided task as archived or unarchived.
     * 
     * @param   VBOTaskTaskregistry  $task
     * @param   int                  $status  Use 1 to archive, 0 to unarchive.
     * 
     * @return  void
     */
    protected function changeArchivedStatus(VBOTaskTaskregistry $task, int $status)
    {
        $isArchived = (int) $task->get('archived');

        if ($isArchived === $status) {
            // nothing to change
            return;
        }

        try {
            // change the archived status
            VBOTaskModelTask::getInstance()->update([
                'id' => $task->getID(),
                'archived' => $status,
            ]);
        } catch (Exception $error) {
            // ignore and go ahead
        }
    }
}
