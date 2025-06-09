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
 * Task manager history tracker.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOTaskHistoryTracker extends VBOHistoryTracker
{
    /**
     * Class constructor.
     * 
     * @param  VBOHistoryModel  $model  The storage model.
     */
    public function __construct(VBOHistoryModel $model)
    {
        parent::__construct($model, [
            new VBOTaskHistoryDetectorInsert,
            new VBOHistoryDetectorTitle,
            new VBOHistoryDetectorDescription('notes'),
            new VBOHistoryDetectorBooking,
            new VBOHistoryDetectorRoom,
            new VBOTaskHistoryDetectorStatus,
            new VBOTaskHistoryDetectorTags,
            new VBOTaskHistoryDetectorAssignees,
            new VBOTaskHistoryDetectorDuedate,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getCommitter()
    {
        $committer = parent::getCommitter();

        // check whether we have a guest committer
        if ($committer->getRole() === 'guest' && $committer->getID()) {
            // the tasks cannot be created/updated from guest users, therefore we should
            // replace this user with a "Scheduled activity" mockup
            $this->committer = new VBOHistoryCommitter(0, '', 'schedule');
        }

        return $this->committer;
    }
}
