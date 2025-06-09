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
 * Task model task implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskModelTask
{
    /**
     * Proxy for immediately accessing the object.
     * 
     * @return  VBOTaskModelTask
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
            ->from($dbo->qn('#__vikbooking_tm_tasks'));

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
        $app = JFactory::getApplication();
        $dbo = JFactory::getDbo();

        // tell whether we are actually counting the items
        $counting = $cols && substr($cols[0] ?? '', 0, 5) === 'COUNT' && !$start && $lim === 1; 

        // start query object
        $q = $dbo->getQuery(true);

        if (!$cols) {
            $q->select($dbo->qn('t') . '.*');
        } else {
            $q->select(array_map(function($column) use ($dbo) {
                if (preg_match('/^[A-Z]/', $column)) {
                    // no quoting needed when column name starts with an upper case letter (i.e "COUNT(*)")
                    return $column;
                }
                if (!preg_match('/^t\./', $column)) {
                    $column = 't.' . $column;
                }
                return $dbo->qn($column);
            }, $cols));
        }

        $q->from($dbo->qn('#__vikbooking_tm_tasks', 't'));

        if (($clauses['assignee'] ?? null) || ($clauses['assignees'] ?? null) || ($clauses['operator'] ?? null)) {
            $q->leftJoin($dbo->qn('#__vikbooking_tm_task_assignees', 'ta') . ' ON ' . $dbo->qn('ta.id_task') . ' = ' . $dbo->qn('t.id'));
        }

        if (is_array($clauses['fulltext'] ?? null) && is_string($clauses['fulltext']['value'] ?? null)) {
            // full-text special clause to search over task titles and notes
            // select full-text match score (relevance)
            $q->select('MATCH(' . $dbo->qn('title') . ', ' . $dbo->qn('notes') . ') AGAINST(' . $dbo->q($clauses['fulltext']['value']) . ') AS ' . $dbo->qn('relevance'));
            // add where statement to only include matches
            $q->where('MATCH(' . $dbo->qn('title') . ', ' . $dbo->qn('notes') . ') AGAINST(' . $dbo->q($clauses['fulltext']['value']) . ') > 0');
            // add order by match relevance
            $q->order($dbo->qn('relevance') . ' DESC');
            // unset this special clause
            unset($clauses['fulltext']);
        }

        foreach ($clauses as $column => $data) {
            if (!is_array($data) || !array_key_exists('value', $data)) {
                // null values are also accepted for "value"
                continue;
            }

            if (in_array($column, ['assignee', 'assignees', 'operator'])) {
                $column = 'ta.id_operator';
            } elseif (!preg_match('/^t\./', $column)) {
                $column = 't.' . $column;
            }

            if (is_array($data['value'])) {
                if (preg_match('/[a-z]/i', ($data['value'][0] ?? '0'))) {
                    // use "IN" for a list of quoted strings
                    $q->where($dbo->qn($column) . ' IN (' . implode(', ', array_map([$dbo, 'q'], $data['value'])) . ')');
                } else {
                    // default to "IN" for a list of integers
                    $q->where($dbo->qn($column) . ' IN (' . implode(', ', array_map('intval', $data['value'])) . ')');
                }
            } else {
                // singular fetching value
                if (is_null($data['value'])) {
                    // look for a null (or not null) value
                    $q->where($dbo->qn($column) . ' IS' . (($data['operator'] ?? '=') == '!=' ? ' NOT' : '') . ' NULL');
                } else {
                    // look for a real value
                    if ($data['instruction'] ?? null) {
                        // raw clause instruction given
                        $q->where($data['instruction']);
                    } else {
                        // match value
                        $q->where($dbo->qn($column) . ' ' . ($data['operator'] ?? '=') . ' ' . $dbo->q($data['value']));
                    }
                }
            }
        }

        if (!isset($clauses['dueon'])) {
            // default ordering is by current date to list the upcoming tasks
            $q->order('IF(' . $dbo->qn('t.dueon') . ' >= ' . $dbo->q(JFactory::getDate('now', $app->get('offset'))->toSql()) . ', 1, 0)' . ' DESC');
        }
        $q->order($dbo->qn('t.dueon') . ' ASC');
        $q->order($dbo->qn('t.id') . ' ASC');

        $dbo->setQuery($q, $start, $lim);

        if ($counting) {
            // count items
            return $dbo->loadResult();
        }

        // fetch items
        $tasks = $dbo->loadObjectList();

        try {
            // take the latest 20 unread threads
            $threads = VBOFactory::getChatMediator()->getMessages(
                (new VBOChatSearch)
                    ->aggregate()
                    ->unread()
                    ->limit(20)
            );
        } catch (Exception $e) {
            // silently catch any possible authentication error
            $threads = [];
        }

        $threadsLookup = [];

        // map threads by context ID
        foreach ($threads as $message) {
            $threadsLookup[$message->getContext()->getID()] = $message;
        }

        // check whether the loaded tasks have at least an unread message
        foreach ($tasks as $task) {
            $task->hasUnreadMessages = (bool) ($threadsLookup[$task->id] ?? null);
        }

        return $tasks;
    }

    /**
     * Item IDs loading implementation.
     *
     * @param   array   $clauses    List of associative columns to filter
     *                              (column => [operator, value])
     * @param   int     $start      Query limit start.
     * @param   int     $lim        Query limit value.
     *
     * @return  array               List of record objects.
     */
    public function getItemIds(array $clauses = [], $start = 0, $lim = 0)
    {
        return $this->getItems($clauses, $start, $lim, ['id']);
    }

    /**
     * Items loading through filtering implementation.
     *
     * @param   array   $filters    Associative list filters to apply.
     * @param   int     $start      Query limit start.
     * @param   int     $lim        Query limit value.
     * @param   bool    $count      True for counting rather than fetching.
     *
     * @return  array               List of record objects.
     */
    public function filterItems(array $filters, $start = 0, $lim = 0, bool $count = false)
    {
        $app = JFactory::getApplication();
        $dbo = JFactory::getDbo();

        // filter out empty filters
        $filters = array_filter($filters);

        // build fetching clauses by normalizing filter names
        $clauses = [];

        if ($filters['id_area'] ?? null) {
            // filter by area/project ID
            $clauses['id_area'] = [
                'value' => (int) $filters['id_area'],
            ];
        } elseif (is_array($filters['id_areas'] ?? null)) {
            // filter by area/project IDs
            $clauses['id_area'] = [
                'value' => array_map('intval', $filters['id_areas']),
            ];
        }

        if ($filters['statusId'] ?? null) {
            // filter by status(es)
            $clauses['status_enum'] = [
                'value' => $filters['statusId'],
            ];
        } else {
            // status not specified, ignore archived tasks by default
            $clauses['archived'] = [
                'value' => 0,
            ];
        }

        if ($filters['tag'] ?? null) {
            // filter by tag requires multiple conditions
            $qpieces = [
                $dbo->qn('t.tags') . ' = ' . $dbo->q('[' . $filters['tag'] . ']'),
                $dbo->qn('t.tags') . ' LIKE ' . $dbo->q('[' . $filters['tag'] . ',%'),
                $dbo->qn('t.tags') . ' LIKE ' . $dbo->q('%,' . $filters['tag'] . ']'),
                $dbo->qn('t.tags') . ' LIKE ' . $dbo->q('%,' . $filters['tag'] . ',%'),
            ];

            $clauses['tags'] = [
                'instruction' => '(' . implode(' OR ', $qpieces) . ')',
                'value' => (int) $filters['tag'],
            ];
        }

        if ($filters['assignee'] ?? null) {
            // filter by a single assignee ID or null (-1) for tasks not assigned to any operator
            $clauses['assignee'] = [
                'value' => $filters['assignee'] == -1 ? null : intval($filters['assignee']),
            ];
        } elseif (is_array($filters['assignees'] ?? null)) {
            // filter by assignee IDs
            $clauses['assignees'] = [
                'value' => $filters['assignees'],
            ];
        }

        if (is_numeric($filters['operator'] ?? null)) {
            // unlike the "assignee(s)" filter, this filter will get the tasks assigned
            // to the given operator ID, OR, those who are not yet assigned to any operator
            // by excluding the tasks that belong to private areas

            // cast filter to integer
            $filters['operator'] = (int) $filters['operator'];

            // build SQL instruction for the operator assignments
            $instructions = [
                $dbo->qn('ta.id_operator') . ' = ' . $filters['operator'],
                $dbo->qn('ta.id_operator') . ' IS NULL',
            ];
            $instruction = '(' . implode(' OR ', array_map(function($q) {
                return '(' . $q . ')';
            }, $instructions)) . ')';

            // get a list of private area IDs, if any
            $privateAreaIds = VBOFactory::getTaskManager()->getPrivateAreas();

            // prepend SQL instruction to exclude the private areas
            if ($privateAreaIds) {
                $instruction = '(' . $dbo->qn('t.id_area') . ' NOT IN (' . implode(', ', $privateAreaIds) . ') AND ' . $instruction . ')';
            }

            // set final filter
            $clauses['operator'] = [
                'instruction' => $instruction,
                'value' => $filters['operator'],
            ];
        }

        if (is_numeric($filters['id_room'] ?? null)) {
            // cast filter to integer
            $filters['id_room'] = (int) $filters['id_room'];

            // filter by room ID or category ID
            if ($filters['id_room'] > 0) {
                // room ID given
                $clauses['id_room'] = [
                    'value' => $filters['id_room'],
                ];
            } else {
                // category ID given
                $room_ids = VikBooking::getAvailabilityInstance(true)->filterRoomCategories((array) $filters['id_room']);
                if ($room_ids) {
                    // filter by multiple room IDs
                    $clauses['id_room'] = [
                        'value' => $room_ids,
                    ];
                }
            }
        } elseif (is_array($filters['id_rooms'] ?? null) && ($id_rooms = array_filter($filters['id_rooms']))) {
            // filter by multiple room IDs
            $clauses['id_room'] = [
                'value' => array_values($id_rooms),
            ];
        }

        if ($filters['id_order'] ?? null) {
            // filter by booking ID
            $clauses['id_order'] = [
                'value' => (int) $filters['id_order'],
            ];
        } elseif ($filters['with_order'] ?? null) {
            // filter by tasks assigned to a booking ID (NOT NULL)
            $clauses['id_order'] = [
                'operator' => '!=',
                'value' => null,
            ];
        }

        if ($filters['dates'] ?? null) {
            // filter by date(s) by converting the local date-time to UTC
            list($fromDt, $toDt) = $this->getFilterDatesInterval((string) $filters['dates'], $local = false, $sql = true);

            if ($fromDt) {
                // build SQL instruction
                $instruction = $dbo->qn('t.dueon') . ' BETWEEN ' . $dbo->q($fromDt) . ' AND ' . $dbo->q($toDt);

                // check if the same dates filter should be applied on the begin date
                if ($filters['calendar'] ?? false) {
                    // modify SQL instruction to include the begin date and the finish date
                    $instructions = [
                        $instruction,
                        $dbo->qn('t.beganon') . ' BETWEEN ' . $dbo->q($fromDt) . ' AND ' . $dbo->q($toDt),
                        $dbo->qn('t.finishedon') . ' IS NOT NULL AND (' . $dbo->q($fromDt) . ' BETWEEN IFNULL(' . $dbo->qn('t.beganon') . ', ' . $dbo->qn('t.dueon') . ') AND ' . $dbo->qn('t.finishedon') . ')',
                    ];
                    $instruction = '(' . implode(' OR ', array_map(function($q) {
                        return '(' . $q . ')';
                    }, $instructions)) . ')';
                }

                // add clause
                $clauses['dueon'] = [
                    'instruction' => $instruction,
                    'value' => $filters['dates'],
                ];
            }
        }

        if ($filters['future'] ?? null) {
            // filter by due date in the future
            $today_midnight = JFactory::getDate('now', $app->get('offset'))->modify('00:00:00')->toSql();
            $clauses['dueon'] = [
                'instruction' => $dbo->qn('t.dueon') . ' >= ' . $dbo->q($today_midnight),
                'value' => $today_midnight,
            ];
        }

        if ($filters['search'] ?? null) {
            if (preg_match('/^id:\s?[0-9]+$/i', $filters['search'])) {
                // search task by ID
                $clauses['id'] = [
                    'value' => (int) preg_replace('/[^0-9]/', '', $filters['search']),
                ];
            } else {
                // full-text tasks search by title and notes
                $clauses['fulltext'] = [
                    'value' => $filters['search'],
                ];
            }
        }

        if ($count) {
            // count items
            return $this->getItems($clauses, 0, 1, ['COUNT(*)']);
        }

        // fetch items
        return $this->getItems($clauses, $start, $lim);
    }

    /**
     * Stores a new task record.
     * 
     * @param   array|object  $record  The record to store.
     * 
     * @return  int|null               The new record ID or null.
     */
    public function save($record)
    {
        $app = JFactory::getApplication();
        $dbo = JFactory::getDbo();

        $taskManager = VBOFactory::getTaskManager();

        $record = (object) $record;

        // normalize received notes HTML; if any
        $this->normalizeNotesHtml($record);

        if (is_array(($record->tags ?? null))) {
            // parse all tags, even custom ones, into a list of IDs
            $record->tags = json_encode(VBOTaskModelColortag::getInstance()->parseIds($record->tags));
        }

        if (empty($record->status_enum) && !empty($record->id_area)) {
            // fallback to the first area status enumeration found
            $statuses = $taskManager->getStatusGroupElements(VBOTaskArea::getRecordInstance($record->id_area)->getStatuses(), $flatten = true);
            $record->status_enum = $statuses[0]['id'];
        }

        // check due date
        if (empty($record->dueon)) {
            // default to current date-time because the due date cannot be empty
            $record->dueon = JFactory::getDate('now', $app->get('offset'))->toSql();
        } else {
            // convert the given (and expected) local date-time to UTC
            $record->dueon = JFactory::getDate($record->dueon, $app->get('offset'))->toSql();
        }

        // force creation date
        $record->createdon = JFactory::getDate('now', $app->get('offset'))->toSql();

        // always attempt to get and unset the assignee IDs as they do not belong to the task record
        $assigneesList = $record->assignees ?? [];
        unset($record->assignees);

        /**
         * Trigger event to allow third-party plugins to manipulate the task payload before it gets saved
         */
        VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeSaveTaskManagerTask', [$record, $isNewTask = true]);

        // store task record
        $dbo->insertObject('#__vikbooking_tm_tasks', $record, 'id');

        /**
         * Trigger event to allow third-party plugins to operate once the task record has been saved
         */
        VBOFactory::getPlatform()->getDispatcher()->trigger('onAfterSaveTaskManagerTask', [$record, $isNewTask = true]);

        $taskId = ($record->id ?? null) ?: null;

        if ($taskId && $assigneesList) {
            $assignees = array_filter(array_map('intval', (array) $assigneesList));
            foreach ($assignees as $assigneeId) {
                $relRecord = [
                    'id_task'     => $taskId,
                    'id_operator' => $assigneeId,
                ];
                $relRecord = (object) $relRecord;
                $dbo->insertObject('#__vikbooking_tm_task_assignees', $relRecord, 'id');
            }
        }

        // track the task creation
        (new VBOTaskHistoryTracker(
            new VBOHistoryModelDatabase(
                new VBOTaskHistoryContext($record->id)
            )
        ))->track(null, $record);

        // make sure the new task exists
        if ($taskManager->statusTypeExists($record->status_enum)) {
            // execute the extra rules that the new status should apply
            $taskManager->getStatusTypeInstance($record->status_enum)->apply((int) $record->id);
        }

        return $taskId;
    }

    /**
     * Updates an existing task record.
     * 
     * @param   array|object  $record  The record details to update.
     * 
     * @return  bool
     */
    public function update($record)
    {
        $app = JFactory::getApplication();
        $dbo = JFactory::getDbo();

        $record = (object) $record;

        if (empty($record->id)) {
            return false;
        }

        // get previous item
        $prev = $this->getItem($record->id);

        if (!$prev) {
            throw new UnexpectedValueException('The task [' . $record->id . '] you are trying to update does not exist.', 404);
        }

        // normalize received notes HTML; if any
        $this->normalizeNotesHtml($record);

        if (is_array(($record->tags ?? null))) {
            // parse all tags, even custom ones, into a list of IDs
            $record->tags = json_encode(VBOTaskModelColortag::getInstance()->parseIds($record->tags));
        }

        // check due date
        if (!empty($record->dueon)) {
            // convert the given (and expected) local date-time to UTC
            $record->dueon = JFactory::getDate($record->dueon, $app->get('offset'))->toSql();
        } else {
            // prevent the system from saving NULL dates
            unset($record->dueon);
        }

        // check begin date
        if (!empty($record->beganon)) {
            // convert the given (and expected) local date-time to UTC
            $record->beganon = JFactory::getDate($record->beganon, $app->get('offset'))->toSql();
        } else {
            // prevent the system from saving NULL dates
            unset($record->beganon);
        }

        // check finish date
        if (!empty($record->finishedon)) {
            // convert the given (and expected) local date-time to UTC
            $record->finishedon = JFactory::getDate($record->finishedon, $app->get('offset'))->toSql();
        } else {
            // prevent the system from saving NULL dates
            unset($record->finishedon);
        }

        // always unset the creation date-time and force the modification date-time
        unset($record->createdon);
        $record->modifiedon = JFactory::getDate('now', $app->get('offset'))->toSql();

        // always attempt to get and unset the assignee IDs as they do not belong to the task record
        $assigneesList = $record->assignees ?? null;
        unset($record->assignees);

        /**
         * Trigger event to allow third-party plugins to manipulate the task payload before it gets saved
         */
        VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeSaveTaskManagerTask', [$record, $isNewTask = false]);

        // update task record
        $updated = (bool) $dbo->updateObject('#__vikbooking_tm_tasks', $record, 'id');

        // inject assignees again
        $record->assignees = $assigneesList;

        /**
         * Trigger event to allow third-party plugins to operate once the task record has been saved
         */
        VBOFactory::getPlatform()->getDispatcher()->trigger('onAfterSaveTaskManagerTask', [$record, $isNewTask = false]);

        if ($assigneesList !== null) {
            // sanitize the assignees list
            $assigneesList = array_filter(array_map('intval', (array) $assigneesList));

            if ($assigneesList) {
                // update task-operator relations

                // get the current task-operator relations
                $dbo->setQuery(
                    $dbo->getQuery(true)
                        ->select($dbo->qn('id_operator'))
                        ->from($dbo->qn('#__vikbooking_tm_task_assignees'))
                        ->where($dbo->qn('id_task') . ' = ' . (int) $record->id)
                );

                $prev->assignees = array_filter(array_map('intval', $dbo->loadColumn()));

                // find the relations to eventually add or delete
                $addingOperators  = array_diff($assigneesList, $prev->assignees);
                $missingOperators = array_diff($prev->assignees, $assigneesList);

                foreach ($missingOperators as $operatorId) {
                    // delete task-operator relation
                    $dbo->setQuery(
                        $dbo->getQuery(true)
                            ->delete($dbo->qn('#__vikbooking_tm_task_assignees'))
                            ->where($dbo->qn('id_task') . ' = ' . (int) $record->id)
                            ->where($dbo->qn('id_operator') . ' = ' . (int) $operatorId)
                    );
                    $dbo->execute();
                }

                foreach ($addingOperators as $operatorId) {
                    // add task-operator relation
                    $relRecord = [
                        'id_task'     => (int) $record->id,
                        'id_operator' => (int) $operatorId,
                    ];
                    $relRecord = (object) $relRecord;
                    $dbo->insertObject('#__vikbooking_tm_task_assignees', $relRecord, 'id');
                }
            } else {
                // delete all previous task-operator relations, if any
                $dbo->setQuery(
                    $dbo->getQuery(true)
                        ->delete($dbo->qn('#__vikbooking_tm_task_assignees'))
                        ->where($dbo->qn('id_task') . ' = ' . (int) $record->id)
                );
                $dbo->execute();
            }
        }

        // track any changes
        (new VBOTaskHistoryTracker(
            new VBOHistoryModelDatabase(
                new VBOTaskHistoryContext($record->id)
            )
        ))->track($prev, $record);

        // check whether the status has changed
        if ((new VBOTaskHistoryDetectorStatus)->hasChanged((object) $prev, (object) $record)) {
            $taskManager = VBOFactory::getTaskManager();

            // make sure the new task exists
            if ($taskManager->statusTypeExists($record->status_enum)) {
                // execute the extra rules that the new status should apply
                $taskManager->getStatusTypeInstance($record->status_enum)->apply((int) $record->id);
            }
        }

        return $updated;
    }

    /**
     * Deletes a task record.
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
                ->delete($dbo->qn('#__vikbooking_tm_tasks'))
                ->where($dbo->qn('id') . ' IN (' . implode(', ', $id) . ')')
        );

        $dbo->execute();
        $result = (bool) $dbo->getAffectedRows();

        if ($result) {
            // delete the task-operator relations
            $dbo->setQuery(
                $dbo->getQuery(true)
                    ->delete($dbo->qn('#__vikbooking_tm_task_assignees'))
                    ->where($dbo->qn('id_task') . ' IN (' . implode(', ', $id) . ')')
            );
            $dbo->execute();
        }

        return $result;
    }

    /**
     * Given a dates filter identifier, returns the interval of dates in "Y-m-d H:i:s" or "SQL" format.
     * 
     * @param   string  $dates  The dates filter identifier.
     * @param   bool    $local  Whether to obtain dates in local or UTC timezone.
     * @param   bool    $sql    Whether to obtain dates in "SQL" or "Y-m-d H:i:s" format.
     * 
     * @return  array           List of dates, from-to, for the interval, or array of null values.
     */
    public function getFilterDatesInterval(string $dates, bool $local = true, bool $sql = true)
    {
        $useTz = $local ? date_default_timezone_get() : JFactory::getApplication()->get('offset');

        if (!strcasecmp($dates, 'today')) {
            // filter by today's date
            $fromDt = JFactory::getDate(date('Y-m-d 00:00:00'), $useTz);
            $toDt   = JFactory::getDate(date('Y-m-d 23:59:59'), $useTz);
            if ($sql) {
                return [
                    $fromDt->toSql(),
                    $toDt->toSql(),
                ];
            }
            return [
                $fromDt->format('Y-m-d H:i:s'),
                $toDt->format('Y-m-d H:i:s'),
            ];
        }

        if (!strcasecmp($dates, 'tomorrow')) {
            // filter by tomorrow's date
            $tomorrowTs = strtotime('+1 day');
            $fromDt = JFactory::getDate(date('Y-m-d 00:00:00', $tomorrowTs), $useTz);
            $toDt   = JFactory::getDate(date('Y-m-d 23:59:59', $tomorrowTs), $useTz);
            if ($sql) {
                return [
                    $fromDt->toSql(),
                    $toDt->toSql(),
                ];
            }
            return [
                $fromDt->format('Y-m-d H:i:s'),
                $toDt->format('Y-m-d H:i:s'),
            ];
        }

        if (!strcasecmp($dates, 'yesterday')) {
            // filter by yesterday's date
            $yesterdayTs = strtotime('-1 day');
            $fromDt = JFactory::getDate(date('Y-m-d 00:00:00', $yesterdayTs), $useTz);
            $toDt   = JFactory::getDate(date('Y-m-d 23:59:59', $yesterdayTs), $useTz);
            if ($sql) {
                return [
                    $fromDt->toSql(),
                    $toDt->toSql(),
                ];
            }
            return [
                $fromDt->format('Y-m-d H:i:s'),
                $toDt->format('Y-m-d H:i:s'),
            ];
        }

        if (!strcasecmp($dates, 'week')) {
            // filter by this week's date
            $fromDt = JFactory::getDate(date('Y-m-d 00:00:00'), $useTz);
            $toDt   = JFactory::getDate(date('Y-m-d 23:59:59', strtotime('+1 week')), $useTz);
            if ($sql) {
                return [
                    $fromDt->toSql(),
                    $toDt->toSql(),
                ];
            }
            return [
                $fromDt->format('Y-m-d H:i:s'),
                $toDt->format('Y-m-d H:i:s'),
            ];
        }

        if (!strcasecmp($dates, 'month')) {
            // filter by this month's date
            $fromDt = JFactory::getDate(date('Y-m-01 00:00:00'), $useTz);
            $toDt   = JFactory::getDate(date('Y-m-t 23:59:59'), $useTz);
            if ($sql) {
                return [
                    $fromDt->toSql(),
                    $toDt->toSql(),
                ];
            }
            return [
                $fromDt->format('Y-m-d H:i:s'),
                $toDt->format('Y-m-d H:i:s'),
            ];
        }

        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\s?:\s?[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $dates)) {
            // filter by custom range of dates
            $parts = explode(':', $dates);
            $fromDt = JFactory::getDate(date('Y-m-d 00:00:00', strtotime(trim($parts[0]))), $useTz);
            $toDt   = JFactory::getDate(date('Y-m-d 23:59:59', strtotime(trim($parts[1]))), $useTz);
            if ($sql) {
                return [
                    $fromDt->toSql(),
                    $toDt->toSql(),
                ];
            }
            return [
                $fromDt->format('Y-m-d H:i:s'),
                $toDt->format('Y-m-d H:i:s'),
            ];
        }

        // unrecognized dates filter
        return [null, null];
    }

    /**
     * Updates a checklist element of a specific task.
     * 
     * @param   int        $taskId  The ID of the task to update.
     * @param   int        $n       The N-th checkbox to update.
     * @param   bool|null  $status  The status to assign. Null to toggle the current status.
     * 
     * @return  void
     */
    public function updateChecklist(int $taskId, int $n, ?bool $status = null)
    {
        // get updated item
        $task = $this->getItem($taskId);

        if (!$task) {
            throw new UnexpectedValueException('The task [' . $taskId . '] you are trying to update does not exist.', 404);
        }

        $index = 0;

        // scan the notes HTML in search of the element to update
        $task->notes = preg_replace_callback(
            // take all the ULs holding the data-checked attribute
            "/<ul[^>]+data-checked=\"(true|false)\"[^>]*>(.*?)<\/ul>/s", function($matches) use ($n, &$index, $status) {
                // make sure the matches the requested index
                if (++$index === $n) {
                    if ($status === null) {
                        // toggle the current status
                        $status = $matches[1] !== 'true';
                    }

                    // replace the current status with the new one
                    $matches[0] = preg_replace("/data-checked=\"(true|false)\"/", 'data-checked="' . ($status ? 'true' : 'false') . '"', $matches[0]);
                }

                return $matches[0];
            },
            $task->notes
        );

        // finally update the task
        $this->update([
            'id' => $task->id,
            'notes' => $task->notes,
        ]);
    }

    /**
     * Normalizes the HTML content generated by the preferred WYSIWYG editor.
     * 
     * @param   object  $record
     * 
     * @return  void
     */
    private function normalizeNotesHtml(object $task) {
        if (empty($task->notes)) {
            // task missing, nothing to normalize
            return;
        }

        /**
         * Quill editor supports the checklist feature. However, instead of having the checked status
         * on the LIs, Quill groups the elements per status under the same UL. Therefore we need to
         * refactor the following structure:
         *
         * ```html
         * <ul data-checked="true"><li>a</li><li>b</li></ul>
         * <ul data-checked="false"><li>c</li></ul>
         * <ul data-checked="true"><li>d</li></ul>
         * ```
         * 
         * into this one:
         * 
         * ```html
         * <ul data-checked="true"><li>a</li></ul>
         * <ul data-checked="true"><li>b</li></ul>
         * <ul data-checked="false"><li>c</li></ul>
         * <ul data-checked="true"><li>d</li></ul>
         * ```
         */
        $task->notes = preg_replace_callback(
            // take all the ULs holding the data-checked attribute
            "/<ul[^>]+data-checked=\"(true|false)\"[^>]*>(.*?)<\/ul>\s*/s",
            function($ulMatches) {
                // extract the current status and all the LIs
                $checked = $ulMatches[1];
                $lis = $ulMatches[2];

                // wrap all the LIs into different ULs
                return preg_replace_callback(
                    "/\s*<li[^>]*>(.*?)<\/li>\s*/s",
                    function($liMatches) use ($checked) {
                        return '<ul data-checked="' . $checked . '"><li>' . $liMatches[1] . '</li></ul>';
                    },
                    $lis
                );
            },
            $task->notes
        );
    }
}
