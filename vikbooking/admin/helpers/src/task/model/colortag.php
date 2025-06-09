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
 * Task model colortag implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskModelColortag
{
    /**
     * Proxy for immediately accessing the object.
     * 
     * @return  VBOTaskModelColortag
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
            ->from($dbo->qn('#__vikbooking_tm_task_colortags'));

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
     * @param   array   $clauses    List of associative columns to fetch
     *                              (column => [operator, value])
     * @param   int     $start      Query limit start.
     * @param   int     $lim        Query limit value.
     *
     * @return  array               List of record objects.
     */
    public function getItems(array $clauses = [], $start = 0, $lim = 0)
    {
        $dbo = JFactory::getDbo();

        $q = $dbo->getQuery(true)
            ->select('*')
            ->from($dbo->qn('#__vikbooking_tm_task_colortags'));

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
     * Given a list of color tag IDs and/or new tag names, parses them
     * into a list of color tag IDs by eventually creating the new ones.
     * 
     * @param   array   $ids    List of color tag identifiers.
     * 
     * @return  array           Parsed list of color tag IDs.
     */
    public function parseIds(array $ids)
    {
        // filter out empty or non-scalar values
        $ids = array_filter($ids, function($id) {
            return !empty($id) && is_scalar($id);
        });

        if (!$ids) {
            return [];
        }

        $tagIds = [];

        foreach ($ids as $tag) {
            if (preg_match('/[^0-9]/', (string) $tag)) {
                // check whether the tag naem includes the color (NAME:COLOR)
                if (preg_match("/:[a-z]+$/", $tag)) {
                    list($tag, $color) = explode(':', $tag);
                } else {
                    $color = null;
                }

                // must be a new custom tag
                if ($oldId = $this->getItem(['name' => $tag])) {
                    // a tag with the same name exists
                    $tagIds[] = (int) $oldId->id;
                } else {
                    // attempt to create a new tag
                    $newId = $this->save(['name' => $tag, 'color' => $color]);
                    if ($newId) {
                        // push the newly created tag id
                        $tagIds[] = $newId;
                    }
                }
            } else {
                // must be an existing tag id
                if ($oldId = $this->getItem($tag)) {
                    // the tag exists
                    $tagIds[] = (int) $oldId->id;
                }
            }
        }

        return $tagIds;
    }

    /**
     * Stores a new colortag record.
     * 
     * @param   array|object  $record  The record to store.
     * 
     * @return  int|null               The new record ID or null.
     */
    public function save($record)
    {
        $dbo = JFactory::getDbo();

        $record = (object) $record;

        if (empty($record->name)) {
            return null;
        }

        if (empty($record->color) && empty($record->hex)) {
            // assign a random color enumeration
            $tagColors = VBOFactory::getTaskManager()->getTagColors(true);
            $record->color = $tagColors[rand(0, count($tagColors) - 1)];
        }

        $dbo->insertObject('#__vikbooking_tm_task_colortags', $record, 'id');

        return $record->id ?? null;
    }

    /**
     * Updates an existing task colortag record.
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

        return (bool) $dbo->updateObject('#__vikbooking_tm_task_colortags', $record, 'id');
    }

    /**
     * Deletes a task colortag record.
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

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->delete($dbo->qn('#__vikbooking_tm_task_colortags'))
                ->where($dbo->qn('id') . ' IN (' . implode(', ', $id) . ')')
        );

        $dbo->execute();

        return (bool) $dbo->getAffectedRows();
    }
}