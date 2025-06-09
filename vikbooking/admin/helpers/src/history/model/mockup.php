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
 * Mockup history model implementor.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
final class VBOHistoryModelMockup implements VBOHistoryModel
{
    /** @var array */
    protected $changes = [];

    /**
     * @inheritDoc
     */
    public function getItems()
    {
        return $this->changes;
    }

    /**
     * @inheritDoc
     */
    public function save(array $events, VBOHistoryCommitter $committer)
    {
        $change = new stdClass;
        $change->date = JFactory::getDate()->toSql();

        $change->id_user = $committer->getID();
        $change->username = $committer->getName();
        $change->events = [];

        // iterate all the detected change details
        foreach ($events as $event) {
            if (!$event instanceof VBOHistoryDetector) {
                continue;
            }

            $change->events[] = $event;
        }

        $this->changes[] = $change;
    }
}
