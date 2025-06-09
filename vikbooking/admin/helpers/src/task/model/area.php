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
 * Task model area implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskModelArea
{
    /**
     * Proxy for immediately accessing the object.
     * 
     * @return  VBOTaskModelArea
     */
    public static function getInstance()
    {
        return new static;
    }

    /**
     * Class constructor.
     */
    public function __construct()
    {}

    /**
     * Item loading implementation.
     *
     * @param   mixed  $pk   An optional primary key value to load the row by,
     *                       or an associative array of fields to match.
     *
     * @return  object|null  The record object on success, null otherwise.
     */
    public function getItem($pk)
    {
        $dbo = JFactory::getDbo();

        $q = $dbo->getQuery(true)
            ->select('*')
            ->from($dbo->qn('#__vikbooking_tm_areas'));

        if (is_array($pk)) {
            foreach ($pk as $column => $value) {
                $q->where($dbo->qn($column) . ' = ' . $dbo->q($value));
            }
        } else {
            $q->where($dbo->qn('id') . ' = ' . (int) $pk);
        }

        $dbo->setQuery($q, 0, 1);

        return $dbo->loadObject();
    }

    /**
     * Items loading implementation.
     *
     * @param   array   $clauses    List of associative columns to filter
     *                              (column => [operator, value])
     * @param   int     $start      Query limit start.
     * @param   int     $lim        Query limit value.
     * @param   array   $cols       Optional list of columns to fetch.
     *
     * @return  array               List of record objects.
     */
    public function getItems(array $clauses = [], $start = 0, $lim = 0, array $cols = [])
    {
        $dbo = JFactory::getDbo();

        $q = $dbo->getQuery(true);

        if (!$cols) {
            $q->select('*');
        } else {
            $q->select(array_map([$dbo, 'qn'], $cols));
        }

        $q->from($dbo->qn('#__vikbooking_tm_areas'));

        foreach ($clauses as $column => $data) {
            if (!is_array($data) || !isset($data['value'])) {
                continue;
            }

            if (is_array($data['value'])) {
                // default to "IN" for a list of integers
                $q->where($dbo->qn($column) . ' IN (' . implode(', ', array_map('intval', $data['value'])) . ')');
            } else {
                // singular fetching value
                $q->where($dbo->qn($column) . ' ' . ($data['operator'] ?? '=') . ' ' . $dbo->q($data['value']));
            }
        }

        $q->order($dbo->qn('name') . ' ASC');
        $q->order($dbo->qn('id') . ' ASC');

        $dbo->setQuery($q, $start, $lim);

        return $dbo->loadObjectList();
    }

    /**
     * Returns a list of area/project IDs to which the given assignee ID is assigned.
     * 
     * @param   int     $assigneeId     The operator ID.
     * 
     * @return  array                   List of supported area IDs or empty array.
     */
    public function getAssigneeItems(int $assigneeId)
    {
        $areaIds = [];

        foreach ($this->getItems() as $areaRecord) {
            $area = VBOTaskArea::getInstance((array) $areaRecord);
            $areaAssignees = $area->getOperatorIds();
            if (!$areaAssignees || in_array($assigneeId, $areaAssignees)) {
                // push supported area/project
                $areaIds[] = $area->getID();
            }
        }

        return $areaIds;
    }

    /**
     * Stores a new task area record.
     * 
     * @param   array|object  $record  The record to store.
     * 
     * @return  int|null               The new record ID or null.
     */
    public function save($record)
    {
        $dbo = JFactory::getDbo();

        $record = (object) $record;

        if (is_array(($record->settings ?? null)) || is_object(($record->settings ?? null))) {
            $record->settings = json_encode($record->settings);
        }

        if (is_array(($record->tags ?? null))) {
            // parse all tags, even custom ones, into a list of IDs
            $record->tags = json_encode(VBOTaskModelColortag::getInstance()->parseIds($record->tags));
        }

        if (is_array(($record->status_enums ?? null))) {
            $record->status_enums = json_encode($record->status_enums);
        }

        $dbo->insertObject('#__vikbooking_tm_areas', $record, 'id');

        return $record->id ?? null;
    }

    /**
     * Updates an existing task area record.
     * 
     * @param   array|object  $record  The record details to update.
     * 
     * @return  bool
     */
    public function update($record)
    {
        $dbo = JFactory::getDbo();

        $record = (object) $record;

        if (empty($record->id)) {
            return false;
        }

        if (is_array(($record->settings ?? null)) || is_object(($record->settings ?? null))) {
            $record->settings = json_encode($record->settings);
        }

        if (is_array(($record->tags ?? null))) {
            // parse all tags, even custom ones, into a list of IDs
            $record->tags = json_encode(VBOTaskModelColortag::getInstance()->parseIds($record->tags));
        }

        if (is_array(($record->status_enums ?? null))) {
            $record->status_enums = json_encode(array_values(array_filter($record->status_enums)));
        }

        return (bool) $dbo->updateObject('#__vikbooking_tm_areas', $record, 'id');
    }

    /**
     * Deletes a task area record.
     * 
     * @param   array|int   $id     The record(s) to delete.
     * 
     * @return  bool
     */
    public function delete($id)
    {
        $dbo = JFactory::getDbo();

        if (!is_array($id)) {
            $id = (array) $id;
        }

        $id = array_map('intval', $id);

        if (!$id) {
            return false;
        }

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->delete($dbo->qn('#__vikbooking_tm_areas'))
                ->where($dbo->qn('id') . ' IN (' . implode(', ', $id) . ')')
        );

        $dbo->execute();
        $result = (bool) $dbo->getAffectedRows();

        if ($result) {
            // fetch all the tasks assigned to the deleted areas
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->select($dbo->qn('id'))
                    ->from($dbo->qn('#__vikbooking_tm_tasks'))
                    ->where($dbo->qn('id_area') . ' IN (' . implode(', ', $id) . ')')
            );

            // delete all the tasks found on cascade
            VBOTaskModelTask::getInstance()->delete($dbo->loadColumn());
        }

        return $result;
    }
}
