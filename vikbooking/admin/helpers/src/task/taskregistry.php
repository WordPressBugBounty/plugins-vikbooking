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
 * Task manager task registry.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskTaskregistry
{
    /**
     * @var  array
     */
    protected $record = [];

    /**
     * @var  ?VBOTaskArea
     */
    protected $area;

    /**
     * @var  array
     */
    protected $assigneeIds = [];

    /**
     * @var  array
     */
    protected static $areaRecords = [];

    /**
     * @var  array
     */
    protected static $areaNamesMap = [];

    /**
     * @var  array
     */
    protected static $listingNamesMap = [];

    /**
     * Proxy to construct the object from a task record ID.
     * 
     * @param   int   $recordId     The task record ID.
     * 
     * @return  VBOTaskTaskregistry
     */
    public static function getRecordInstance(int $recordId)
    {
        $task = $recordId ? VBOTaskModelTask::getInstance()->getItem($recordId) : null;

        return new static((array) $task);
    }

    /**
     * Proxy to construct the object.
     * 
     * @param   array   $record     The task record or an empty array.
     * 
     * @return  VBOTaskTaskregistry
     */
    public static function getInstance(array $record)
    {
        return new static($record);
    }

    /**
     * Class constructor.
     * 
     * @param   array   $record     The task record or an empty array.
     * 
     * @throws  Exception
     */
    public function __construct(array $record)
    {
        if (!empty($record['tags']) && is_string($record['tags'])) {
            // decode task tags
            $record['tags'] = array_filter(array_map('intval', (array) json_decode($record['tags'], true)));
        }

        // set internal record property
        $this->record = $record;
    }

    /**
     * Returns the current task record.
     * 
     * @return  array
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Returns the current task area ID, if any.
     * 
     * @return  int
     */
    public function getAreaID()
    {
        return (int) ($this->area ? $this->area->getID() : $this->get('id_area', 0));
    }

    /**
     * Returns the task area name from the given ID.
     * 
     * @param   int     $areaId     The area/project ID.
     * 
     * @return  string
     */
    public function getAreaName(int $areaId)
    {
        if (!empty(static::$areaNamesMap[$areaId])) {
            // return the cached value
            return static::$areaNamesMap[$areaId];
        }

        if ($this->area && $this->area->getID() == $areaId) {
            // return the current area name
            return $this->area->getName();
        }

        // fetch the requested area/project
        $areaRecord = $this->fetchAreaRecord($areaId);

        if (!$areaRecord) {
            // requested area not found
            return '';
        }

        // cache value and return it
        static::$areaNamesMap[$areaId] = $areaRecord->name ?? '';

        return static::$areaNamesMap[$areaId];
    }

    /**
     * Returns the current task area object.
     * 
     * @return  ?VBOTaskArea
     */
    public function getArea()
    {
        return $this->area ?? null;
    }

    /**
     * Returns the current task area object.
     * 
     * @param  VBOTaskArea  $area   The task area object.
     * 
     * @return  void
     */
    public function setArea(VBOTaskArea $area)
    {
        $this->area = $area;
    }

    /**
     * Attempts to fetch the requested record property.
     * 
     * @param   string  $prop       The record property to get.
     * @param   mixed   $default    The default value to return.
     * 
     * @return  mixed
     */
    public function get(string $prop, $default = null)
    {
        return $this->record[$prop] ?? $default;
    }

    /**
     * Returns the record ID if available, or 0.
     * 
     * @return  int
     */
    public function getID()
    {
        return (int) $this->get('id', 0);
    }

    /**
     * Returns the task title.
     * 
     * @return  string
     */
    public function getTitle()
    {
        return (string) $this->get('title', '');
    }

    /**
     * Returns the task notes.
     * 
     * @return  string
     */
    public function getNotes()
    {
        return (string) $this->get('notes', '');
    }

    /**
     * Returns the list of tag IDs for the current task.
     * 
     * @return  array
     */
    public function getTags()
    {
        return (array) $this->get('tags', []);
    }

    /**
     * Returns the list of tag records for the current task.
     * 
     * @return  array
     */
    public function getTagRecords()
    {
        return VBOFactory::getTaskManager()->getColorTags($this->getTags());
    }

    /**
     * Returns the status enumeration for the current task.
     * 
     * @return  string
     */
    public function getStatus()
    {
        return (string) $this->get('status_enum', '');
    }

    /**
     * Returns the status readable name for the current task.
     * 
     * @return  string
     */
    public function getStatusName()
    {
        $enum = $this->getStatus();

        if (VBOFactory::getTaskManager()->statusTypeExists($enum)) {
            return VBOFactory::getTaskManager()->getStatusTypeInstance($enum)->getName();
        }

        return $enum;
    }

    /**
     * Returns the scheduling enumeration for the current task.
     * 
     * @return  string
     */
    public function getScheduling()
    {
        return (string) $this->get('scheduler', '');
    }

    /**
     * Returns the booking ID assigned to the current task.
     * 
     * @return  string
     */
    public function getBookingId()
    {
        return (int) $this->get('id_order', 0);
    }

    /**
     * Builds the current task booking information for rendering the record as element.
     * 
     * @param   int     $activeBid  Optional active booking ID.
     * 
     * @return  array
     */
    public function buildBookingElement(int $activeBid = 0)
    {
        $bid = $this->getBookingId() ?: $activeBid;
        if (!$bid) {
            return [];
        }

        return VBOFactory::getTaskManager()->buildBookingElement((int) $bid);
    }

    /**
     * Returns the listing ID assigned to the current task.
     * 
     * @return  string
     */
    public function getListingId()
    {
        return (int) $this->get('id_room', 0);
    }

    /**
     * Proxy for getting the current task listing ID.
     * 
     * @return  int
     */
    public function getRoomId()
    {
        return $this->getListingId();
    }

    /**
     * Returns the listing name from the given ID.
     * 
     * @param   int     $listingId     The listing ID.
     * 
     * @return  string
     */
    public function getListingName(int $listingId)
    {
        if (!empty(static::$listingNamesMap[$listingId])) {
            // return the cached value
            return static::$listingNamesMap[$listingId];
        }

        // fetch the requested listing
        $listing = VikBooking::getRoomInfo($listingId, ['name'], true);

        if (!$listing) {
            // requested listing not found
            return '';
        }

        // cache value and return it
        static::$listingNamesMap[$listingId] = $listing['name'] ?? '';

        return static::$listingNamesMap[$listingId];
    }

    /**
     * Returns the current task due date, either in UTC or local timezone.
     * 
     * @param   bool    $local   True to get the date in local timezone for display.
     * @param   string  $format  The date format to return.
     * 
     * @return  string
     */
    public function getDueDate(bool $local = false, string $format = '')
    {
        $dt = (string) $this->get('dueon', '');

        if (!$dt || (!$local && !$format)) {
            // return the raw date value, empty string or UTC date from database
            return $dt;
        }

        return $this->formatDate($dt, $local, $format);
    }

    /**
     * Returns the current task creation date, either in UTC or local timezone.
     * 
     * @param   bool    $local   True to get the date in local timezone for display.
     * @param   string  $format  The date format to return.
     * 
     * @return  string
     */
    public function getCreationDate(bool $local = false, string $format = '')
    {
        $dt = (string) $this->get('createdon', '');

        if (!$dt || (!$local && !$format)) {
            // return the raw date value, empty string or UTC date from database
            return $dt;
        }

        return $this->formatDate($dt, $local, $format);
    }

    /**
     * Returns the current task modification date, either in UTC or local timezone.
     * 
     * @param   bool    $local   True to get the date in local timezone for display.
     * @param   string  $format  The date format to return.
     * 
     * @return  string
     */
    public function getModificationDate(bool $local = false, string $format = '')
    {
        $dt = (string) $this->get('modifiedon', '');

        if (!$dt || (!$local && !$format)) {
            // return the raw date value, empty string or UTC date from database
            return $dt;
        }

        return $this->formatDate($dt, $local, $format);
    }

    /**
     * Returns the task begin date, either in UTC or local timezone.
     * 
     * @param   bool    $local   True to get the date in local timezone for display.
     * @param   string  $format  The date format to return.
     * 
     * @return  string
     */
    public function getBeginDate(bool $local = false, string $format = '')
    {
        $dt = (string) $this->get('beganon', '');

        if (!$dt || (!$local && !$format)) {
            // return the raw date value, empty string or UTC date from database
            return $dt;
        }

        return $this->formatDate($dt, $local, $format);
    }

    /**
     * Returns the task finish date, either in UTC or local timezone.
     * 
     * @param   bool    $local   True to get the date in local timezone for display.
     * @param   string  $format  The date format to return.
     * 
     * @return  string
     */
    public function getFinishDate(bool $local = false, string $format = '')
    {
        $dt = (string) $this->get('finishedon', '');

        if (!$dt || (!$local && !$format)) {
            // return the raw date value, empty string or UTC date from database
            return $dt;
        }

        return $this->formatDate($dt, $local, $format);
    }

    /**
     * Returns the task expected duration (end) date from the due date, either in UTC or local timezone.
     * Should be used when the task "finishedon" date is not available.
     * 
     * @param   bool    $local   True to get the date in local timezone for display.
     * @param   string  $format  The date format to return.
     * 
     * @return  string
     */
    public function getDurationDate(bool $local = false, string $format = '')
    {
        $dt = $this->getDueDate($local, 'Y-m-d H:i:s');

        if (!$dt) {
            // abort
            return $dt;
        }

        return date($format, strtotime(sprintf('+%d minutes', $this->getDuration()), strtotime($dt)));
    }

    /**
     * Tells whether the task is in the future by using the due date.
     * 
     * @return  bool
     */
    public function isFuture()
    {
        $due_midnight = $this->getDueDate(true, 'Y-m-d 00:00:00') ?: date('Y-m-d 00:00:00');
        $due_dt = new DateTime($due_midnight, new DateTimeZone(date_default_timezone_get()));

        return $due_dt->getTimestamp() > time();
    }

    /**
     * Formats a date string in UTC format into local or UTC timezone.
     * 
     * @param   string  $dt      The date string in UTC format.
     * @param   bool    $local   True to get the date in local timezone for display.
     * @param   string  $format  The date format to return.
     * 
     * @return  string
     */
    public function formatDate(string $dt, bool $local = false, string $format = '')
    {
        $user = JFactory::getUser();

        // attempt to get the timezone from the current user, if available, auto-fallback to CMS timezone otherwise
        $tz = $user->getTimezone();
        if (is_object($tz)) {
            // get the timezone identifier from the DateTimeZone object
            $tz = $tz->getName();
        }

        // default date format
        $format = $format ?: ($local ? $this->getLocalDateTimeFormat() : 'c');

        if ($local) {
            // adjust UTC to local timezone
            return JHtml::fetch('date', $dt, $format, $tz);
        }

        // format the current date in UTC
        return JFactory::getDate($dt, 'UTC')->format($format);
    }

    /**
     * Returns a list of assignee (operator) IDs for the current task.
     * 
     * @return  array
     */
    public function getAssigneeIds()
    {
        if ($this->assigneeIds) {
            return $this->assigneeIds;
        }

        if (!$this->getID()) {
            return [];
        }

        $dbo = JFactory::getDbo();

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->select($dbo->qn('id_operator'))
                ->from($dbo->qn('#__vikbooking_tm_task_assignees'))
                ->where($dbo->qn('id_task') . ' = ' . $this->getID())
        );

        $this->assigneeIds = array_values(array_unique(array_column($dbo->loadAssocList(), 'id_operator')));

        return $this->assigneeIds;
    }

    /**
     * Returns a list of details for the assignees.
     * 
     * @param   array   $ids    Optional list of operator IDs.
     * 
     * @return  array
     */
    public function getAssigneeDetails(array $ids = [])
    {
        $ids = $ids ?: $this->getAssigneeIds();

        if (!$ids) {
            return [];
        }

        // get the involved operator details
        $details = VikBooking::getOperatorInstance()->getElements($ids);

        // iterate all details to build the initials and short name
        foreach ($details as &$operator) {
            // build name initials
            $initials = '';
            $name_parts = array_filter(explode(' ', $operator['name']));
            $main_name = array_shift($name_parts);
            $last_name = array_pop($name_parts);

            if (function_exists('mb_substr')) {
                $initials .= $main_name ? mb_substr($main_name, 0, 1, 'UTF-8') : '';
                $initials .= $last_name ? mb_substr($last_name, 0, 1, 'UTF-8') : mb_substr($main_name, 1, 1, 'UTF-8');
            } else {
                $initials .= $main_name ? substr($main_name, 0, 1) : '';
                $initials .= $last_name ? substr($last_name, 0, 1) : substr($main_name, 1, 1);
            }

            // set useful values
            $operator['initials'] = $initials;
            $operator['short_name'] = $main_name;
        }

        // unset last reference
        unset($operator);

        // return the operator details list
        return $details;
    }

    /**
     * Calculates and returns the task duration in minutes.
     * 
     * @return  int
     */
    public function getDuration()
    {
        $began_on = $this->get('beganon');
        $finished_on = $this->get('finishedon');

        if ($began_on && $finished_on) {
            // create date-time objects
            $timezone = new DateTimeZone('UTC');
            $began_date = new DateTime($began_on, $timezone);
            $finished_date = new DateTime($finished_on, $timezone);

            // get the difference in seconds
            $seconds_diff = abs($finished_date->getTimestamp() - $began_date->getTimestamp());

            // convert the duration in minutes
            return (int) ($seconds_diff / 60);
        }

        if (!$this->getArea() && $this->getAreaID()) {
            if ($areaRecord = $this->fetchAreaRecord($this->getAreaID())) {
                // set task area object
                $this->setArea(VBOTaskArea::getInstance((array) $areaRecord));
            }
        }

        if ($this->getArea()) {
            // fallback onto driver default duration taken from area/project
            $taskDriver = VBOFactory::getTaskManager()->getDriverInstance($this->getArea()->getType(), [$this->getArea()]);

            return $taskDriver->getDefaultDuration();
        }

        return 0;
    }

    /**
     * Returns the configured date format and attempts to guess the time format.
     * 
     * @return  string
     */
    private function getLocalDateTimeFormat()
    {
        $datesep = VikBooking::getDateSeparator();
        $nowdf   = VikBooking::getDateFormat();

        if ($nowdf == "%d/%m/%Y") {
            $df = 'd/m/Y';
            $tf = 'H:i';
        } elseif ($nowdf == "%m/%d/%Y") {
            $df = 'm/d/Y';
            $tf = 'h:i A';
        } else {
            $df = 'Y/m/d';
            $tf = 'h:i A';
        }

        return str_replace('/', $datesep, $df) . ' ' . $tf;
    }

    /**
     * Fetches the requested area/project ID.
     * 
     * @param   int     $areaId     The area/project ID to fetch.
     * 
     * @return  ?object
     */
    private function fetchAreaRecord(int $areaId)
    {
        if (static::$areaRecords[$areaId] ?? null) {
            // return the cached value
            return static::$areaRecords[$areaId];
        }

        // fetch the requested area/project
        $area = VBOTaskModelArea::getInstance()->getItem($areaId);

        if (!$area) {
            return null;
        }

        // cache value and return it
        static::$areaRecords[$areaId] = $area;

        return $area;
    }
}
