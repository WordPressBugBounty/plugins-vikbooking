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
 * Task status changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOTaskHistoryDetectorStatus extends VBOHistoryDetectorStatus
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct('status_enum');
    }

    /**
     * @inheritDoc
     */
    protected function doDescribe($previousValue, $currentValue)
    {
        $taskManager = VBOFactory::getTaskManager();

        if ($previousValue && $taskManager->statusTypeExists($previousValue)) {
            // render status as badge
            $previousValue = JHtml::fetch('vbohtml.taskmanager.status', $taskManager->getStatusTypeInstance($previousValue), ['editable' => false]);
        }

        if ($currentValue && $taskManager->statusTypeExists($currentValue)) {
            // render status as badge
            $currentValue = JHtml::fetch('vbohtml.taskmanager.status', $taskManager->getStatusTypeInstance($currentValue), ['editable' => false]);
        }

        return parent::doDescribe($previousValue, $currentValue);
    }
}
