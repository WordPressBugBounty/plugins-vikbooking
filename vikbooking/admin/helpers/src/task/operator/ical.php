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
 * Task operator iCal implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskOperatorIcal
{
    /**
     * @var string
     */
    private $calendarComponent = 'VCALENDAR';

    /**
     * @var string
     */
    private $calendarEvent = 'VEVENT';

    /**
     * @var string
     */
    private $calendarProdId = '-//e4j//VikBooking//EN';

    /**
     * @var string
     */
    private $calendarVersion = '2.0';

    /**
     * @var string
     */
    private $calendarScale = 'GREGORIAN';

    /**
     * @var array
     */
    private $operator = [];

    /**
     * @var ?object
     */
    private $permissions;

    /**
     * @var string
     */
    private $tool = '';

    /**
     * @var string
     */
    private $toolUri = '';

    /**
     * @var array
     */
    private $events = [];

    /**
     * Proxy for immediately accessing the object.
     * 
     * @return  VBOTaskOperatorIcal
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
     * Sets the list of event objects.
     * 
     * @param   object[]    $events     List of event objects.
     * 
     * @return  VBOTaskOperatorIcal
     */
    public function setEvents(array $events)
    {
        $this->events = $events;

        return $this;
    }

    /**
     * Sets the current operator record.
     * 
     * @param   array|object    $operator   The operator information record.
     * 
     * @return  VBOTaskOperatorIcal
     */
    public function setOperator($operator)
    {
        if (is_array($operator) || is_object($operator)) {
            $this->operator = (array) $operator;
        }

        return $this;
    }

    /**
     * Sets the current operator permissions object.
     * 
     * @param   object  $permissions    The operator permissions object.
     * 
     * @return  VBOTaskOperatorIcal
     */
    public function setPermissions($permissions)
    {
        if (is_object($permissions)) {
            $this->permissions = $permissions;
        }

        return $this;
    }

    /**
     * Sets the name of the current operator tool.
     * 
     * @param   string  $tool   The operator tool identifier.
     * 
     * @return  VBOTaskOperatorIcal
     */
    public function setTool(string $tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * Sets the URI for the current operator tool.
     * 
     * @param   string  $uri   The operator tool URI.
     * 
     * @return  VBOTaskOperatorIcal
     */
    public function setToolUri(string $uri)
    {
        $this->toolUri = VBOFactory::getPlatform()->getUri()->route($uri);

        return $this;
    }

    /**
     * Builds the event UID.
     * 
     * @param   VBOTaskTaskregistry   $task  The task registry.
     * 
     * @return  string
     */
    public function getEventUid(VBOTaskTaskregistry $task)
    {
        return md5($task->getID() ?: rand());
    }

    /**
     * Builds up the iCal calendar file content.
     * 
     * @return  string  The full iCal calendar file content.
     */
    public function toString()
    {
        return implode('', [
            $this->buildCalendarHead(),
            $this->buildCalendarContent(),
            $this->buildCalendarFooter(),
        ]);
    }

    /**
     * Escapes the characters of the given content.
     * 
     * @param   string  $content    The content string to make safe.
     * 
     * @return  string
     */
    private function safeContent(string $content)
    {
        return preg_replace('/([\,;])/', '\\\$1', $content);
    }

    /**
     * Builds and returns the iCal calendar head string section.
     * 
     * @return  string
     */
    private function buildCalendarHead()
    {
        return "BEGIN:{$this->calendarComponent}\r\n" .
            "PRODID:{$this->calendarProdId}\r\n" .
            "CALSCALE:{$this->calendarScale}\r\n" .
            "VERSION:{$this->calendarVersion}\r\n";
    }

    /**
     * Builds and returns the iCal calendar footer string section.
     * 
     * @return  string
     */
    private function buildCalendarFooter()
    {
        return "END:{$this->calendarComponent}";
    }

    /**
     * Builds and returns the iCal calendar content string.
     * 
     * @return  string
     */
    private function buildCalendarContent()
    {
        $content = '';

        foreach ($this->events as $event) {
            $content .= $this->buildCalendarEvent((array) $event);
        }

        return $content;
    }

    /**
     * Builds and returns the iCal content string for the given event data.
     * 
     * @param   array   $event  The event (task) information record.
     * 
     * @return  string
     */
    private function buildCalendarEvent(array $event)
    {
        // wrap the event (task) record into a registry
        $task = VBOTaskTaskregistry::getInstance($event);

        // check if the task is currently un-assigned
        $assigneeIds = $task->getAssigneeIds();
        $unassigned_label = !$assigneeIds ? sprintf(' (%s)', JText::translate('VBO_UNASSIGNED')) : '';

        $uri = null;

        if ($this->toolUri) {
            // use task direct link
            $uri = new JUri($this->toolUri);
            $uri->setVar('filters[calendar_type]', 'taskdetails');
            $uri->setVar('filters[task_id]', $task->getID());
        }

        // build task event properties
        $booking_cal_event = [
            // begin event
            'BEGIN'              => $this->calendarEvent,
            // task creation date
            'DTSTAMP'            => $task->getCreationDate(true, 'Ymd\THis\Z'),
            // task finished date, or task expected completion date
            'DTEND;VALUE=DATE'   => $task->getFinishDate(true, 'Ymd\THis\Z') ?: $task->getDurationDate(true, 'Ymd\THis\Z'),
            // task due date
            'DTSTART;VALUE=DATE' => $task->getDueDate(true, 'Ymd\THis\Z'),
            // unique event identifier
            'UID'                => $this->getEventUid($task),
            // event description is built through various task values separated by a safe new-line
            'DESCRIPTION'        => implode('\n', array_filter([
                // task status
                $task->getStatusName() . $unassigned_label,
                // listing name
                $task->getListingName($task->getListingId()),
                // listing notes (plain text)
                strip_tags($task->getNotes()),
                // tool URI
                (string) $uri,
            ])),
            // event summary is just the task title
            'SUMMARY'            => $task->getTitle(),
            // end event
            'END'                => $this->calendarEvent,
        ];

        // start event content string
        $ev_content = '';

        // scan all the event properties and related values to build the event content
        foreach (array_filter($booking_cal_event) as $cal_prop => $cal_val) {
            $event_line = sprintf('%s:%s', $cal_prop, $this->safeContent($cal_val));
            $ev_content .= implode("\r\n ", str_split($event_line, 75)) . "\r\n";
        }

        return $ev_content;
    }
}
