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
 * Declares all task driver methods.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
abstract class VBOTaskDriveraware implements VBOTaskDriverinterface
{
    /**
     * @var  ?VBOTaskArea
     */
    protected $area;

    /**
     * @var  array
     */
    protected $settings = [];

    /**
     * @var  array
     */
    protected $operators = [];

    /**
     * @var  VBOTaskDrivercollector
     */
    protected $collector;

    /**
     * Proxy to construct the task driver object.
     * 
     * @param   ?VBOTaskArea     $area   The task area object.
     * 
     * @return  VBOTaskDriverinterface
     */
    public static function getInstance(?VBOTaskArea $area = null)
    {
        return new static($area);
    }

    /**
     * Class constructor.
     * 
     * @param   ?VBOTaskArea     $area   The task area object.
     */
    public function __construct(?VBOTaskArea $area = null)
    {
        // set task area
        $this->area = $area;

        if ($this->area) {
            // load task settings from current task area
            $this->settings = $this->area->loadSettings();
        }

        // start a new collector registry
        $this->collector = VBOTaskDrivercollector::getInstance();
    }

    /**
     * Returns the name of the task driver.
     * 
     * @return  string  The driver readable name.
     */
    public function getName()
    {
        return ucfirst($this->getID());
    }

    /**
     * Returns the task driver icon.
     * 
     * @return  string  The font-icon class identifier.
     */
    public function getIcon()
    {
        return VikBookingIcons::i('tasks');
    }

    /**
     * Returns the task driver parameters to configure an area.
     * 
     * @return  array   List of driver parameters.
     */
    public function getParams()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function scheduleBookingConfirmation(VBOTaskBooking $booking)
    {
        // no automatic scheduling supported upon booking confirmation
    }

    /**
     * @inheritDoc
     */
    public function scheduleBookingAlteration(VBOTaskBooking $booking)
    {
        // no automatic scheduling supported upon booking alteration
    }

    /**
     * @inheritDoc
     */
    public function scheduleBookingCancellation(VBOTaskBooking $booking)
    {
        // no automatic scheduling supported upon booking cancellation
    }

    /**
     * Returns the task driver settings for the configured area.
     * 
     * @return  array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Sets the task driver settings.
     * 
     * @param   array   $settings   The settings to set.
     * @param   bool    $merge      True for merging the previous settings.
     * 
     * @return  void
     */
    public function setSettings(array $settings, bool $merge = false)
    {
        $this->settings = array_merge(($merge ? $this->settings : []), $settings);
    }

    /**
     * Saves the task driver settings into its current area.
     * 
     * @param   ?array  $settings   Optional settings to save.
     * 
     * @return  void
     */
    public function saveSettings(?array $settings = null)
    {
        $this->area->saveSettings((is_array($settings) ? $settings : $this->settings));
    }

    /**
     * Returns a specific task driver setting.
     * 
     * @param   string  $name       The setting name.
     * @param   mixed   $default    The default setting.
     * 
     * @return  mixed
     */
    public function getSetting(string $name, $default = null)
    {
        return $this->settings[$name] ?? $default;
    }

    /**
     * Sets a value for a specific task driver setting.
     * 
     * @param   string  $name   The setting name.
     * @param   mixed   $value  The value to set.
     * 
     * @return  void
     */
    public function setSetting(string $name, $value)
    {
        $this->settings[$name] = $value;
    }

    /**
     * Returns the current task driver collector.
     * 
     * @param   bool    $reset  True for resetting the collector.
     * 
     * @return  VBOTaskDrivercollector
     */
    public function getCollector(bool $reset = false)
    {
        if ($reset) {
            return $this->collector->reset();
        }

        return $this->collector;
    }

    /**
     * Returns the current project/area ID, if available.
     * 
     * @return  int     The current area ID or 0.
     */
    public function getAreaID()
    {
        return $this->area ? $this->area->getID() : 0;
    }

    /**
     * Returns the current project/area name, if available.
     * 
     * @return  string     The current area name or empty string.
     */
    public function getAreaName()
    {
        return $this->area ? $this->area->getName() : '';
    }

    /**
     * Returns the default status for new tasks for the current project/area, if any.
     * 
     * @return  ?string  The default status enumeration or null.
     */
    public function getDefaultStatus()
    {
        return $this->area ? ($this->area->getDefaultStatus() ?: null) : null;
    }

    /**
     * Returns the default task duration in minutes.
     * 
     * @return  int
     */
    public function getDefaultDuration()
    {
        // the driver may declare a parameter for the task default duration in minutes
        return intval($this->getSetting('taskduration', 0)) ?: 60;
    }

    /**
     * Returns the eligible operator IDs for the task driver.
     * 
     * @return  array   List of eligible operator IDs or empty array.
     */
    public function getOperatorIds()
    {
        // the driver may declare a parameter to filter the eligible operators
        return array_values(array_filter((array) $this->getSetting('operators', [])));
    }

    /**
     * Returns the eligible listing IDs for the task driver.
     * 
     * @return  array   List of eligible listing IDs or empty array.
     */
    public function getListingIds()
    {
        // the driver may declare a parameter to filter the eligible listings
        return array_values(array_filter((array) $this->getSetting('listings', [])));
    }

    /**
     * Tells whether a listing ID is eligible according to the current task driver settings.
     * 
     * @param   int     $listingId  The listing ID to evaluate.
     * 
     * @return  bool
     */
    public function isListingEligible(int $listingId)
    {
        $eligible_ids = array_map('intval', $this->getListingIds());

        return !$eligible_ids || in_array($listingId, $eligible_ids);
    }

    /**
     * Loads the eligible operators for the task driver.
     * 
     * @param   bool    $elements           True for getting the operators as elements to render.
     * @param   array   $activeAssignees    Optional list of active assignee IDs to merge.
     * 
     * @return  array   Associative (by ID) list of operator array records.
     */
    public function getOperators(bool $elements = false, array $activeAssignees = [])
    {
        $operatorIds = array_values(array_unique(array_merge($this->getOperatorIds(), array_filter($activeAssignees))));

        if ($elements) {
            // always avoid caching when element records are requested
            return VikBooking::getOperatorInstance()->getElements($operatorIds);
        }

        if ($this->operators) {
            // return the cached operator records
            return $this->operators;
        }

        // get all the eligible operators
        $operators = VikBooking::getOperatorInstance()->getAll($operatorIds);

        // map some internal properties
        $operators = array_map(function($operator) {
            // decode or set the needed information
            $operator['perms'] = !empty($operator['perms']) ? (is_string($operator['perms']) ? (array) json_decode($operator['perms'], true) : $operator['perms']) : [];
            $operator['work_days_week'] = !empty($operator['work_days_week']) ? (is_string($operator['work_days_week']) ? (array) json_decode($operator['work_days_week'], true) : $operator['work_days_week']) : [];
            $operator['work_days_exceptions'] = !empty($operator['work_days_exceptions']) ? (is_string($operator['work_days_exceptions']) ? (array) json_decode($operator['work_days_exceptions'], true) : $operator['work_days_exceptions']) : [];

            // return the manipulated operator record
            return $operator;
        }, $operators);

        // cache the eligible operator records
        $this->operators = $operators;

        return $operators;
    }

    /**
     * Returns the operator record ID, if any.
     * 
     * @param   int     $operatorId     The operator ID.
     * 
     * @return  array
     */
    public function getOperatorFromId(int $operatorId)
    {
        foreach ($this->getOperators() as $operator) {
            if ($operator['id'] == $operatorId) {
                // return the requested record found
                return $operator;
            }
        }

        return [];
    }

    /**
     * Returns the working hours configured by the specified operator for the requested date.
     * 
     * @param   int|array  $operator  Either the operator ID or its details.
     * @param   DateTime   $date      The requested date.
     * 
     * @return  int        The number of working hours.
     */
    public function getDateWorkingHours($operator, DateTime $date)
    {
        if (is_numeric($operator)) {
            $operator = $this->getOperatorFromId((int) $operator);
        }

        if (empty($operator)) {
            return 0;
        }

        if (!is_array($operator['work_days_week'] ?? null)) {
            $operator['work_days_week'] = [];
        }

        if (!is_array($operator['work_days_exceptions'] ?? null)) {
            $operator['work_days_exceptions'] = [];
        }

        $ymd = $date->format('Y-m-d');

        // scan working day exceptions backward, to give higher priority to rules created last
        for ($i = count($operator['work_days_exceptions']) - 1; $i >= 0; $i--) {
            $rule = $operator['work_days_exceptions'][$i];

            if (empty($rule['from'])) {
                // missing from date, malformed rule, move on
                continue;
            }

            if (empty($rule['to'])) {
                // single date provided, to date same as from date
                $rule['to'] = $rule['from'];
            }

            // check whether the date is contained within the configured range
            if ($rule['from'] <= $ymd && $ymd <= $rule['to']) {
                // yep, return the number of working hours, if any
                return (int) ($rule['hours'] ?? 0);
            }
        }

        // no exceptions for the specified date, fallback to the default week days
        $weekDay = (int) $date->format('w');

        foreach ($operator['work_days_week'] as $rule) {
            if (!isset($rule['wday'])) {
                // missing day of the week, malformed rule, move on
                continue;
            }

            // check whether the day of the week matches the specified date
            if ($rule['wday'] == $weekDay) {
                // yep, return the number of working hours, if any
                return (int) ($rule['hours'] ?? 0);
            }
        }

        // no working hours defined, we have a day off for this operator
        return 0;
    }

    /**
     * Loads the eligible listings for the task driver.
     * 
     * @return  array   List of listing array records.
     */
    public function getListings()
    {
        return VikBooking::getAvailabilityInstance(true)->loadRooms($this->getListingIds(), 0, true);
    }

    /**
     * Given a list of scheduling interval enumerations for a specific booking, builds
     * and returns a list of task schedule objects for when tasks should be scheduled.
     * 
     * @param   array           $scheduling  List of scheduling interval enumerations.
     * @param   VBOTaskBooking  $booking     The current task booking registry.
     * 
     * @return  VBOTaskScheduleInterface[]
     */
    public function getBookingSchedulingDates(array $scheduling, VBOTaskBooking $booking)
    {
        $schedulesList = [];

        foreach ($scheduling as $scheduleEnum) {
            // obtain the schedule data for the current interval type
            $schedule = VBOTaskSchedule::getType($scheduleEnum, $booking);
            if ($schedule) {
                // push the identified schedule data
                $schedulesList[] = $schedule;
            }
        }

        // sort schedule objects by ordering (ascending)
        usort($schedulesList, function($a, $b) {
            return $a->getOrdering() <=> $b->getOrdering();
        });

        return $schedulesList;
    }

    /**
     * Returns the first available operator on the given date to handle the provided booking task.
     * 
     * @param   DateTime  $dt         The date (local timezone) for which the operator should be available.
     * @param   int       $areaId     The area where the new task should be scheduled.
     * 
     * 
     * @return  array     Available operator record or empty array.
     */
    public function getAvailableOperator(DateTime $dt, int $areaId)
    {
        $dbo = JFactory::getDbo();

        // build a list of available operator IDs according to their work days
        $availableOperators = [];

        foreach ($this->getOperators() as $operator) {
            // get operator working hours for the specified date
            $workingHours = $this->getDateWorkingHours($operator, $dt);

            if ($workingHours) {
                // register available operator with available minutes
                $availableOperators[(int) $operator['id']] = $workingHours * 60;
            }
        }

        if (!$availableOperators) {
            // no operators configured to be available for work on this day
            return [];
        }

        // obtain a date object in UTC and related SQL dates
        $utc_dt = JFactory::getDate($dt->format('Y-m-d H:i:s'), $dt->getTimezone()->getName());
        $utc_dt->modify('00:00:00');
        $utc_start_sql = $utc_dt->toSql();
        $utc_dt->modify('23:59:59');
        $utc_end_sql = $utc_dt->toSql();

        $areas = [
            // preload the details for the requested area
            $areaId => VBOTaskArea::getRecordInstance($areaId),
        ];

        // query the database to see what operators have got tasks assigned for this day
        // this would be the right query to eventually implement a number of do-able tasks per day per operator (default to 1)
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select($dbo->qn('ta.id_operator'))
                ->select($dbo->qn('t.id_area'))
                ->select('COUNT(1) AS ' . $dbo->qn('tot_tasks'))
                ->from($dbo->qn('#__vikbooking_tm_tasks', 't'))
                ->innerJoin($dbo->qn('#__vikbooking_tm_task_assignees', 'ta') . ' ON ' . $dbo->qn('t.id') . ' = ' . $dbo->qn('ta.id_task'))
                ->where($dbo->qn('ta.id_operator') . ' IN (' . implode(', ', array_keys($availableOperators)) . ')')
                ->where($dbo->qn('t.dueon') . ' BETWEEN ' . $dbo->q($utc_start_sql) . ' AND ' . $dbo->q($utc_end_sql))
                ->group($dbo->qn('ta.id_operator'))
                ->group($dbo->qn('t.id_area'))
        );

        foreach ($dbo->loadObjectList() as $operatorTasks) {
            if (!isset($availableOperators[$operatorTasks->id_operator])) {
                // operator not found, move on
                continue;
            }

            if (!isset($areas[$operatorTasks->id_area])) {
                // cache task area details
                $areas[$operatorTasks->id_area] = VBOTaskArea::getRecordInstance($operatorTasks->id_area);
            }

            // get default duration per task
            $duration = $areas[$operatorTasks->id_area]->getDefaultDuration();

            // decrease working minutes by the duration of all scheduled tasks
            $availableOperators[$operatorTasks->id_operator] -= $duration * $operatorTasks->tot_tasks;
        }

        // take only the operators that still have enough space to accept the new task
        $availableOperators = array_keys(array_filter($availableOperators, function($minutes) use ($areas, $areaId) {
            return ($minutes - $areas[$areaId]->getDefaultDuration()) >= 0;
        }));

        if (!$availableOperators) {
            // no operators are free on this day
            return [];
        }

        if (count($availableOperators) === 1) {
            // there's only one free operator, so we return it immediately
            return $this->getOperatorFromId($availableOperators[0]);
        }

        // check what operators have worked more on the closest dates (one week less and one week more)
        $utc_dt->modify('00:00:00');
        $utc_dt->modify('-7 days');
        $utc_back_sql = $utc_dt->toSql();
        $utc_dt->modify('+14 days');
        $utc_forth_sql = $utc_dt->toSql();

        // query the database to see what operators have got more tasks assigned on the closest dates
        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select($dbo->qn('ta.id_operator'))
                ->select('COUNT(*) AS ' . $dbo->qn('tot_tasks'))
                ->from($dbo->qn('#__vikbooking_tm_tasks', 't'))
                ->innerJoin($dbo->qn('#__vikbooking_tm_task_assignees', 'ta') . ' ON ' . $dbo->qn('t.id') . ' = ' . $dbo->qn('ta.id_task'))
                ->where($dbo->qn('ta.id_operator') . ' IN (' . implode(', ', $availableOperators) . ')')
                ->where($dbo->qn('t.dueon') . ' BETWEEN ' . $dbo->q($utc_back_sql) . ' AND ' . $dbo->q($utc_forth_sql))
                ->group($dbo->qn('ta.id_operator'))
        );

        $operatorTasks = $dbo->loadAssocList();

        if (!$operatorTasks) {
            // nobody has got tasks assigned on the closest dates, so we return the first operator available
            return $this->getOperatorFromId($availableOperators[0]);
        }

        // build a list of operator IDs and number of assigned tasks
        $workersTaskCount = array_combine(array_column($operatorTasks, 'id_operator'), array_column($operatorTasks, 'tot_tasks'));

        $workersRanking = [];
        foreach ($availableOperators as $operator_id) {
            $workersRanking[] = [
                'id_operator' => $operator_id,
                'tot_tasks' => (int) ($workersTaskCount[$operator_id] ?? 0),
            ];
        }

        // sort the operators task counter in ascending order
        usort($workersRanking, function($a, $b) {
            return $a['tot_tasks'] <=> $b['tot_tasks'];
        });

        // ensure spreading tasks across all the operators by taking the first sorted, hence with less tasks assigned
        return $this->getOperatorFromId($workersRanking[0]['id_operator']);
    }

    /**
     * Common method for all task drivers that support tasks scheduling upon booking confirmation.
     * 
     * @param   VBOTaskBooking  $booking    The current task booking registry.
     * @param   array           $options    Associative list of task scheduling options.
     * 
     * @return  int                         Number of tasks created.
     */
    protected function createBookingConfirmationTasks(VBOTaskBooking $booking, array $options = [])
    {
        // start counter
        $created = 0;

        // access the task model
        $model = VBOTaskModelTask::getInstance();

        // get all records that belong to this project/area and booking ID
        $prevRecords = $model->getItemIds([
            'id_area'  => [
                'value' => $this->getAreaID(),
            ],
            'id_order' => [
                'value' => $booking->getID(),
            ],
        ]);

        if ($prevRecords) {
            // prevent duplicate tasks for the same project/area and booking ID from being created
            return $created;
        }

        // prepare associative task/area information for the task(s) description
        $info = [
            'booking_id' => $booking->getID(),
            'task_enum'  => $this->getID(),
            'area_id'    => $this->getAreaID(),
            'area_name'  => $this->getAreaName(),
        ];

        // iterate over the listings involved in the reservation
        foreach ($booking->getRooms() as $index => $listing) {
            // set current room index
            $booking->setCurrentRoomIndex($index);

            if (!$this->isListingEligible((int) $listing['idroom'])) {
                // listing not eligible in the current project/area settings
                continue;
            }

            // iterate over the task scheduling dates
            foreach ($this->getBookingSchedulingDates((array) ($options['scheduling'] ?? []), $booking) as $schedule) {
                // get the scheduler type (frequency)
                $scheduler = $schedule->getType();

                // iterate over the schedule dates, if any
                foreach ($schedule->getDates() as $scheduleCounter => $dt) {
                    // prepare booking task record
                    $task = [
                        'id_area'     => $this->getAreaID(),
                        'status_enum' => $this->getDefaultStatus(),
                        'scheduler'   => $scheduler,
                        'title'       => $schedule->getDescription($info, $scheduleCounter) . ' - ' . JText::translate('VBDASHBOOKINGID') . ' ' . $booking->getID(),
                        'id_order'    => $booking->getID(),
                        'id_room'     => $listing['idroom'],
                        'room_index'  => $listing['roomindex'] ?: null,
                        'dueon'       => $dt->format('Y-m-d H:i:s'),
                        'assignees'   => [],
                    ];

                    $warnAdmin = false;

                    if ($options['autoassignment'] ?? null) {
                        // fetch the first available operator
                        $assignee = $this->getAvailableOperator($dt, $task['id_area']);

                        if ($assignee) {
                            // push the available operator ID
                            $task['assignees'][] = $assignee['id'];
                        } else {
                            // unable to automatically assign the task to an operator, warn the admin after saving the task
                            $warnAdmin = true;
                        }
                    }

                    /**
                     * Trigger event to allow third-party plugins to manipulate the task payload.
                     */
                    VBOFactory::getPlatform()->getDispatcher()->trigger('onBeforeScheduleBookingConfirmationTask', [&$task, $booking, $options]);

                    // store the task record
                    $taskId = $model->save($task);

                    if (!$taskId) {
                        continue;
                    }

                    // register the new task within the collector by setting the ID obtained
                    $this->getCollector()->register(array_merge($task, ['id' => $taskId]));

                    // increase counter
                    $created++;

                    if ($warnAdmin) {
                        try {
                            // store a notification to warn the administrator that we have a scheduled task without assignee
                            VBOFactory::getNotificationCenter()->store([
                                [
                                    'sender' => 'operators',
                                    'type' => 'task.unassigned',
                                    'title' => JText::translate('VBO_TASK_NOTIF_SCHEDULING_UNASSIGNED_TITLE'),
                                    'summary' => JText::sprintf('VBO_TASK_NOTIF_SCHEDULING_UNASSIGNED_SUMMARY', $task['title']),
                                    'widget' => 'booking_details',
                                    'widget_options' => [
                                        'bid' => $task['id_order'],
                                        'task_id' => $taskId,
                                    ],
                                    // always skip signature check, so that we can allow a duplicate insert
                                    '_signature' => md5(time()),
                                ],
                            ]);
                        } catch (Exception $e) {
                            // silently catch the error
                            return false;
                        }
                    }
                }
            }
        }

        return $created;
    }
}
