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
 * Item array property changes detector class.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
abstract class VBOHistoryDetectorArray extends VBOHistoryDetectoraware
{
    /**
     * @inheritDoc
     */
    protected function checkChanges($previousValue, $currentValue)
    {
        if (is_string($previousValue)) {
            // decode JSON string (update internal property too)
            $this->previousValue = $previousValue = (array) json_decode($previousValue, true);
        }

        if (is_string($currentValue)) {
            // decode JSON string (update internal property too)
            $this->currentValue = $currentValue = (array) json_decode($currentValue, true);
        }

        if (!is_array($previousValue) || !is_array($currentValue)) {
            // both values must be arrays
            return false;
        }

        // convert all the elements of the arrays into strings
        $previousValue = array_map('strval', $previousValue);
        $currentValue = array_map('strval', $currentValue);

        // sort arrays to make sure the position of the elements is the same
        sort($previousValue);
        sort($currentValue);

        // the arrays must be different
        return $previousValue !== $currentValue;
    }

    /**
     * @inheritDoc
     */
    protected function doDescribe($previousValue, $currentValue)
    {
        $chunks = [];

        $added = array_diff($currentValue, $previousValue);

        if ($added) {
            $chunks[] = $this->describeAddedItems($added);
        }

        $removed = array_diff($previousValue, $currentValue);

        if ($removed) {
            $chunks[] = $this->describeRemovedItems($removed);
        }

        return implode(' ', $chunks);
    }

    /**
     * Describes the added items.
     * 
     * @param   string[]  $added
     * 
     * @return  string
     */
    abstract protected function describeAddedItems(array $added);

    /**
     * Describes the removed items.
     * 
     * @param   string[]  $removed
     * 
     * @return  string
     */
    abstract protected function describeRemovedItems(array $removed);
}
