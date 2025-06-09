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
 * Task tags changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
class VBOTaskHistoryDetectorTags extends VBOHistoryDetectorTags
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        parent::__construct('tags');
    }

    /**
     * @inheritDoc
     */
    protected function describeAddedItems(array $added)
    {
        return parent::describeAddedItems($this->mapTags($added));
    }

    /**
     * @inheritDoc
     */
    protected function describeRemovedItems(array $removed)
    {
        return parent::describeRemovedItems($this->mapTags($removed));
    }

    /**
     * Converts the tag IDs into badges.
     * 
     * @param   int[]  $list
     * 
     * @return  string[]
     */
    private function mapTags(array $list)
    {
        $taskManager = VBOFactory::getTaskManager();

        // convert IDs into badges
        return array_map(function($tagId) use ($taskManager) {
            $tags = $taskManager->getColorTags([(int) $tagId]);

            if ($tags) {
                $tag = (array) array_shift($tags);
                return '<span class="vbo-tm-task-tag vbo-tm-color ' . $tag['color'] . '">' . $tag['name'] . '</span>';
            }

            return '#' . $tagId;
        }, $list);
    }
}
