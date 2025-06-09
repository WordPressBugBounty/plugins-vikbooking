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
 * Interface used to store the detected changes.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
interface VBOHistoryModel
{
    /**
     * Returns all the changes applied to the constructed item.
     * 
     * @return  object[]
     */
    public function getItems();

    /**
     * A list of detected changes.
     * 
     * @param   VBOHistoryDetector[]  $events
     * @param   VBOHistoryCommitter   $committer
     * 
     * @return  void
     */
    public function save(array $events, VBOHistoryCommitter $committer);
}
