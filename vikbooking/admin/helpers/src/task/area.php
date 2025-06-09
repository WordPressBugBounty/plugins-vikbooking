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
 * Task area implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskArea
{
    /**
     * @var  array
     */
    protected $record = [];

    /**
     * Proxy to construct the object from a task area record ID.
     * 
     * @param   int   $recordId     The task area record ID.
     * 
     * @return  VBOTaskArea
     */
    public static function getRecordInstance(int $recordId)
    {
        $area = VBOTaskModelArea::getInstance()->getItem($recordId);

        return new static((array) $area);
    }

    /**
     * Proxy to construct the object.
     * 
     * @param   array   $record     The task area record.
     * 
     * @return  VBOTaskArea
     */
    public static function getInstance(array $record)
    {
        return new static($record);
    }

    /**
     * Class constructor.
     * 
     * @param   array   $record     The task area record.
     * 
     * @throws  Exception
     */
    public function __construct(array $record)
    {
        if (empty($record['id'])) {
            throw new Exception('Missing task area record ID.', 500);
        }

        if (!empty($record['settings']) && is_string($record['settings'])) {
            // decode task area params into settings
            $record['settings'] = json_decode($record['settings'], true);
        }

        if (!empty($record['tags']) && is_string($record['tags'])) {
            // decode task area tags
            $record['tags'] = array_filter(array_map('intval', (array) json_decode($record['tags'], true)));
        }

        if (!empty($record['status_enums']) && is_string($record['status_enums'])) {
            // decode task status enumerations
            $record['status_enums'] = array_filter((array) json_decode($record['status_enums'], true));
        }

        // always cast settings to an array
        $record['settings'] = (array) $record['settings'];

        // set internal record property
        $this->record = $record;
    }

    /**
     * Loads the current task area settings.
     * 
     * @return  array
     */
    public function loadSettings()
    {
        return $this->record['settings'];
    }

    /**
     * Saves the current task area settings.
     * 
     * @param   array   $settings   List of settings to save.
     * @param   bool    $merge      True for merging the settings.
     * 
     * @return  bool
     */
    public function saveSettings(array $settings, $merge = true)
    {
        $dbo = JFactory::getDbo();

        if ($merge && !empty($this->record['settings'])) {
            $settings = array_merge($this->record['settings'], $settings);
        }

        $dbo->setQuery(
            $dbo->getQuery(true)
                ->update($dbo->qn('#__vikbooking_tm_areas'))
                ->set($dbo->qn('settings') . ' = ' . $dbo->q(json_encode($settings)))
                ->where($dbo->qn('id') . ' = ' . $this->record['id'])
        );

        return (bool) $dbo->execute();
    }

    /**
     * Returns the current task area record.
     * 
     * @return  array
     */
    public function getRecord()
    {
        return $this->record;
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
     * Returns the task area ID.
     * 
     * @return  int
     */
    public function getID()
    {
        return (int) $this->get('id');
    }

    /**
     * Returns the task area name.
     * 
     * @return  string
     */
    public function getName()
    {
        return $this->get('name', '');
    }

    /**
     * Returns the list of tag IDs for the current task area.
     * 
     * @return  array
     */
    public function getTags()
    {
        return (array) $this->get('tags', []);
    }

    /**
     * Returns the list of tag records for the current task area.
     * 
     * @return  array
     */
    public function getTagRecords()
    {
        return VBOFactory::getTaskManager()->getColorTags($this->getTags());
    }

    /**
     * Returns the list of status enumerations for the current task area.
     * 
     * @return  array
     */
    public function getStatuses()
    {
        return (array) $this->get('status_enums', []);
    }

    /**
     * Returns a list of statuses to be rendered as elements for the current task area.
     * 
     * @param   string  $activeStatus     Optional task active status to ensure it exists.
     * 
     * @return  array
     */
    public function getStatusElements(string $activeStatus = '')
    {
        $statuses = $this->getStatuses();

        if ($activeStatus && !in_array($activeStatus, $statuses)) {
            // append the active status that would not be available otherwise
            $statuses[] = $activeStatus;
        }

        return VBOFactory::getTaskManager()->getStatusGroupElements($statuses);
    }

    /**
     * Returns the task area default status for new tasks.
     * 
     * @return  string  The default status enumeration.
     */
    public function getDefaultStatus()
    {
        // get all area status types sorted by group
        $statuses = $this->getStatusElements();

        // iterate over the status groups
        foreach ($statuses as $statusGroup) {
            // iterate over the first group of elements
            foreach ($statusGroup['elements'] as $status) {
                // return the first status enumeration
                return $status['id'];
            }
        }

        return '';
    }

    /**
     * Returns the task area icon (class).
     * 
     * @return  string
     */
    public function getIcon()
    {
        $area_icon = $this->get('icon', '');

        if (!empty($area_icon)) {
            // custom area icon was set
            return $area_icon;
        }

        $taskManager = VBOFactory::getTaskManager();
        if ($taskManager->driverExists($this->getType())) {
            // get the icon implemented by the task driver
            $task_icon = $taskManager->getDriverInstance($this->getType(), [$this])->getIcon();

            if (!empty($task_icon)) {
                return $task_icon;
            }
        }

        return 'tasks';
    }

    /**
     * Returns the task area type (task driver instance id).
     * 
     * @return  string
     */
    public function getType()
    {
        return $this->get('instanceof', '');
    }

    /**
     * Returns the default area duration in minutes.
     * 
     * @return  int
     */
    public function getDefaultDuration()
    {
        // the driver may declare a parameter within the area/project for the task default duration in minutes
        return intval(($this->record['settings']['taskduration'] ?? 0)) ?: 60;
    }

    /**
     * Returns the eligible operator IDs for the area.
     * 
     * @return  array   List of eligible operator IDs or empty array.
     */
    public function getOperatorIds()
    {
        // the driver may declare a parameter within the area/project to filter the eligible operators
        return array_values(array_filter((array) ($this->record['settings']['operators'] ?? [])));
    }

    /**
     * Returns the eligible listing IDs for the area.
     * 
     * @return  array   List of eligible listing IDs or empty array.
     */
    public function getListingIds()
    {
        // the driver may declare a parameter within the area/project to filter the eligible listings
        return array_values(array_filter((array) ($this->record['settings']['listings'] ?? [])));
    }

    /**
     * Tells whether the area is private, hence not visible to operators.
     * 
     * @return  bool   True if private, false otherwise.
     */
    public function isPrivate()
    {
        // the driver may declare a parameter within the area/project to define the private visibility
        return (bool) ($this->record['settings']['private'] ?? 0);
    }
}
