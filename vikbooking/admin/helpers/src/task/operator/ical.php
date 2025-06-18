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
 * @since 1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskOperatorIcal
{
    /** @var array */
    protected $operator = [];

    /** @var object|null */
    protected $permissions;

    /** @var string */
    protected $tool = '';

    /** @var string */
    protected $toolUri = '';

    /** @var string|null */
    protected $calendarSubscriber = null;

    /** @var array */
    private $events = [];

    /** @var VBOPlatformDispatcherInterface */
    private $dispatcher;

    /**
     * Proxy for immediately accessing the object.
     * 
     * @return  self
     */
    public static function getInstance()
    {
        return new static;
    }

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->dispatcher = VBOFactory::getPlatform()->getDispatcher();
    }

    /**
     * Magic method used to access protected properties.
     * Private properties are still not accessible.
     * 
     * @inheritDoc
     * 
     * @since 1.18.1 (J) - 1.8.1 (WP)
     */
    public function __get(string $name)
    {
        try {
            // obtain class property details
            $prop = (new ReflectionClass($this))->getProperty($name);

            if ($prop->isPrivate()) {
                // cannot access a private property
                throw new DomainException;
            }
        } catch (Exception $error) {
            // the property doesn't exist or is private
            return null;
        }

        // grant access to protected properties instead
        return $prop->getValue($this);
    }

    /**
     * Sets the list of event objects.
     * 
     * @param   object[]  $events  List of event objects.
     * 
     * @return  self
     */
    public function setEvents(array $events)
    {
        $this->events = $events;

        return $this;
    }

    /**
     * Sets the current operator record.
     * 
     * @param   array|object  $operator  The operator information record.
     * 
     * @return  self
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
     * @param   object  $permissions  The operator permissions object.
     * 
     * @return  self
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
     * @param   string  $tool  The operator tool identifier.
     * 
     * @return  self
     */
    public function setTool(string $tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * Sets the URI for the current operator tool.
     * 
     * @param   string  $uri  The operator tool URI.
     * 
     * @return  self
     */
    public function setToolUri(string $uri)
    {
        $this->toolUri = VBOFactory::getPlatform()->getUri()->route($uri);

        return $this;
    }

    /**
     * Internally sets the calendar that will subscribe to the ICS.
     * Useful to generate different contents depending on the receiver.
     * 
     * @param   ?string  $calendarId  Such as google, apple and so on.
     * 
     * @return  self
     * 
     * @since   1.18.1 (J) - 1.8.1 (WP)
     */
    public function setCalendarSubscriber(?string $calendarId)
    {
        $this->calendarSubscriber = $calendarId ? strtolower($calendarId) : null;

        return $this;
    }

    /**
     * Builds the event UID.
     * 
     * @param   VBOTaskTaskregistry  $task  The task registry.
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
        /**
         * Starts the calendar declaration and build header and events.
         * 
         * @link https://icalendar.org/iCalendar-RFC-5545/3-4-icalendar-object.html
         */
        return implode('', [
            $this->addLine('BEGIN', 'VCALENDAR'),
            $this->buildCalendarHead(),
            $this->buildCalendarContent(),
            $this->addLine('END', 'VCALENDAR')
        ]);
    }

    /**
     * Downloads the iCal calendar file content.
     * 
     * @param   mixed    $app       The CMS application.
     * @param   ?string  $filename  The file name to use for the download.
     * 
     * @return  void
     * 
     * @since   1.18.1 (J) - 1.8.1 (WP)
     */
    public function download($app = null, ?string $filename = null)
    {
        // use default application if not provided
        $app = $app ?: JFactory::getApplication();

        if (!$filename) {
            // use default name format: {ID}-{FIRST_NAME}-{TODAY}
            $filename = sprintf(
                '%d-%s-%s',
                $this->operator['id'],
                (string) $this->operator['first_name'],
                date('Y-m-d')
            );
        }

        // remove .ics extenion from file name
        $filename = preg_replace("/\.ics$/i", '', $filename);

        // generate ICS output
        $ics = $this->toString();

        // declare headers
        $app->setHeader('Content-Type', 'text/calendar; charset=utf-8');
        $app->setHeader('Content-Disposition', 'attachment; filename=' . $filename . '.ics');
        $app->setHeader('Content-Length', strlen($ics));
        $app->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
        $app->setHeader('Pragma', 'no-cache');
        $app->setHeader('Expires', '0');
        $app->sendHeaders();

        // start the ICS download
        echo $ics;
    }

    /**
     * Builds and returns the iCal calendar head string section.
     * 
     * @return  string
     */
    private function buildCalendarHead()
    {
        $ics = '';

        // set up default head information
        $head = [
            'version' => '2.0',
            'prodid' => '-//e4j//VikBooking ' . VIKBOOKING_SOFTWARE_VERSION . '//EN',
            'calscale' => 'GREGORIAN',
            'calname' => JText::translate('VBO_TASK_MANAGER') . ' - ' . trim($this->operator['first_name'] . ' ' . $this->operator['last_name']),
        ];

        /**
         * Trigger event to allow the plugins to include custom options within the
         * head of the ICS file.
         *
         * @param   array    &$head    The default head data.
         * @param   mixed    $handler  The current handler instance.
         *
         * @return  string   Some extra rules to include at the end of the head.
         *
         * @since   1.18.1 (J) - 1.8.1 (WP)
         */
        $extra = $this->dispatcher->filter('onBuildHeadExportICS', [&$head, $this]);

        /**
         * This property specifies the identifier corresponding to the highest version number
         * or the minimum and maximum range of the iCalendar specification that is required
         * in order to interpret the iCalendar object.
         * 
         * @link https://icalendar.org/iCalendar-RFC-5545/3-7-4-version.html
         */
        $ics .= $this->addLine('VERSION', $head['version']);

        /**
         * This property specifies the identifier for the product that created the iCalendar object.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-7-3-product-identifier.html
         */
        $ics .= $this->addLine('PRODID', $head['prodid']);

        /**
         * This property defines the calendar scale used for the calendar information
         * specified in the iCalendar object.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-7-1-calendar-scale.html
         */
        $ics .= $this->addLine('CALSCALE', $head['calscale']);

        /**
         * This non standard property defines the default name that will be used
         * when creating a new subscription.
         *
         * @since 1.18.1 (J) - 1.8.1 (WP)
         */
        $ics .= $this->addLine('X-WR-CALNAME', $head['calname']);

        // append also the values that have been returned by the plugins
        $ics .=  implode('', array_filter($extra));

        return $ics;
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

        // fetch room name and geo details
        $roomInfo = VikBooking::getRoomInfo($task->getListingId(), $columns = ['name', 'params']);
        if (!empty($roomInfo['params'])) {
            $roomInfo['params'] = json_decode($roomInfo['params']);
        }

        $uri = null;

        if ($this->toolUri) {
            // use task direct link
            $uri = new JUri($this->toolUri);
            $uri->setVar('filters[calendar_type]', 'taskdetails');
            $uri->setVar('filters[task_id]', $task->getID());
        }

        // add a new line at the end of each paragraph
        $notes = preg_replace("/<\/p></", "</p>\n<", $task->getNotes());

        // event description is built through various task values separated by a safe new-line
        $description = implode('\n', array_filter([
            // task status
            $task->getStatusName() . $unassigned_label,
            // listing name
            $roomInfo['name'] ?? '',
            // listing notes (plain text)
            preg_replace("/\R/", "\\n", strip_tags($notes)),
        ]));

        if ($this->calendarSubscriber === 'google') {
            // Google Calendar doesn't support the URI rule, therefore the task URL
            // should be included directly within the description.
            $description .= '\n\n' . $uri;
        }

        $data = [
            'uid' => $this->getEventUid($task),
            'created' => $task->getCreationDate(true, 'Ymd\THis\Z'),
            'modified' => $task->getModificationDate(true, 'Ymd\THis\Z'),
            'start' => $task->getDueDate(true, 'Ymd\THis\Z'),
            'end' => $task->getFinishDate(true, 'Ymd\THis\Z') ?: $task->getDurationDate(true, 'Ymd\THis\Z'),
            'summary' => $task->getTitle(),
            'description' => $description,
            'location' => $roomInfo['params']->geo->address ?? null,
            'url' => (string) $uri,
            'status' => 'CONFIRMED',
        ];

        // adjust ics status depending on task current status
        if (in_array($task->getStatus(), ['notstarted', 'pending'])) {
            $data['status'] = 'TENTATIVE';
        } else if (in_array($task->getStatus(), ['cancelled', 'archived'])) {
            $data['status'] = 'CANCELLED';
        }

        $ics = '';

        /**
         * Trigger event to allow the plugins to manipulate the event details before being included.
         *
         * @param   array   &$data   The event data.
         * @param   mixed   $task     The task registry.
         * @param   mixed   $handler  The current handler instance.
         *
         * @return  string   Some extra rules to include at the end of the event body.
         *
         * @since   1.18.1 (J) - 1.8.1 (WP)
         */
        $extra = $this->dispatcher->filter('onBeforeBuildEventICS', [&$data, $task, $this]);

        /**
         * Provide a grouping of component properties that describe an event.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-6-1-event-component.html
         */
        $ics .= $this->addLine('BEGIN', 'VEVENT');

        /**
         * This property specifies the persistent, globally unique identifier for the
         * iCalendar object. This can be used, for example, to identify duplicate calendar
         * streams that a client may have been given access to.
         *
         * Generate a md5 string of the order number because "UID" values MUST NOT include any 
         * data that might identify a user, host, domain, or any other private sensitive information.
         *
         * @link https://icalendar.org/New-Properties-for-iCalendar-RFC-7986/5-3-uid-property.html
         */
        $ics .= $this->addLine('UID', $data['uid']);

        /**
         * This property specifies when the calendar component begins.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-2-4-date-time-start.html
         * 
         * @since 1.18.1 (J) - 1.8.1 (WP)  Changed from VALUE=DATE.
         */
        $ics .= $this->addLine(['DTSTART', 'VALUE=DATE-TIME'], $data['start']);

        /**
         * This property specifies the date and time that a calendar component ends.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-2-2-date-time-end.html
         * 
         * @since 1.18.1 (J) - 1.8.1 (WP)  Changed from VALUE=DATE.
         */
        $ics .= $this->addLine(['DTEND', 'VALUE=DATE-TIME'], $data['end']);

        /**
         * In the case of an iCalendar object that specifies a "METHOD" property, this property
         * specifies the date and time that the instance of the iCalendar object was created.
         * In the case of an iCalendar object that doesn't specify a "METHOD" property, this
         * property specifies the date and time that the information associated with the calendar
         * component was last revised in the calendar store.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-7-2-date-time-stamp.html
         */
        $ics .= $this->addLine('DTSTAMP', $data['created']);

        /**
         * In case an event is modified through a client, it updates the Last-Modified property to the
         * current time. When the calendar is going to refresh an event, in case the Last-Modified is
         * not specified or it is lower than the current one, the changes will be discarded.
         * For this reason, it is needed to specify our internal modified date in order to refresh
         * any existing events with the updated details.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-7-3-last-modified.html
         * 
         * @since 1.18.1 (J) - 1.8.1 (WP)
         */
        if ($data['modified']) {
            $ics .= $this->addLine('LAST-MODIFIED', $data['modified']);
        }

        /**
         * This property may be used to convey a location where a more dynamic
         * rendition of the calendar information can be found.
         * 
         * Google Calendar DOES NOT support this rule.
         *
         * @link https://icalendar.org/New-Properties-for-iCalendar-RFC-7986/5-5-url-property.html
         * 
         * @since 1.18.1 (J) - 1.8.1 (WP)
         */
        if ($this->calendarSubscriber !== 'google') {
            $ics .= $this->addLine(['URL', 'VALUE=URI'], $data['url']);
        }

        /**
         * This property defines a short summary or subject for the calendar component.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-1-12-summary.html
         */
        $ics .= $this->addLine('SUMMARY', $this->safeContent($data['summary']));
        
        /**
         * This property provides a more complete description of the calendar component
         * than that provided by the "SUMMARY" property.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-1-5-description.html
         */
        if ($data['description']) {
            $ics .= $this->addLine('DESCRIPTION', $this->safeContent($data['description']));
        }

        /**
         * This property defines the intended venue for the activity defined by a calendar component.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-1-7-location.html
         */
        if ($data['location']) {
            $ics .= $this->addLine('LOCATION', $this->safeContent($data['location']));
        }

        /**
         * This property defines whether or not an event is transparent to busy time searches.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-2-7-time-transparency.html
         * 
         * @since 1.18.1 (J) - 1.8.1 (WP)
         */
        $ics .= $this->addLine('TRANSP', 'OPAQUE');

        /**
         * This property defines the overall status or confirmation for the calendar component.
         *
         * @link https://icalendar.org/iCalendar-RFC-5545/3-8-1-11-status.html
         * 
         * @since 1.18.1 (J) - 1.8.1 (WP)
         */
        $ics .= $this->addLine('STATUS', $data['status']);

        // append also the values that have been returned by the plugins
        $ics .=  implode('', array_filter($extra));

        /**
         * Closes the event properties.
         *
         * @see BEGIN:VEVENT
         */
        $ics .= $this->addLine('END', 'VEVENT');

        return $ics;
    }

    /**
     * Adds a line within the ICS buffer by caring of the iCalendar standards.
     *
     * @param   mixed   $rule     Either the rule command or an array of commands to be concatenated (;).
     * @param   mixed   $content  Either the rule content or an array of contents to be concatenated (,).
     *
     * @return  string  The compliant ICS declaration.
     * 
     * @since   1.18.1 (J) - 1.8.1 (WP)
     */
    public function addLine($rule, $content = null)
    {
        // concat rules in case of array
        if (is_array($rule))
        {
            // rule with multiple parts, use semi-colon
            $rule = implode(';', $rule);
        }

        // concat contents in case of array
        if (is_array($content))
        {
            // multi-contents list, use comma
            $content = implode(',', $content);
        }

        // create line
        if (is_null($content))
        {
            // we had the full line within the rule
            $line = $rule;
        }
        else
        {
            // merge rule and content
            $line = $rule . ':' . $content;
        }

        // split string every 73 characters (reserve 2 chars to include new line and space)
        $chunks = str_split($line, 73);

        // merge lines togheter by using indentation technique,
        // then add the line to the buffer
        return implode("\n ", $chunks) . "\n";
    }

    /**
     * Escapes the characters of the given content.
     * 
     * @param   string  $content  The content string to make safe.
     * 
     * @return  string
     * 
     * @since   1.18.1 (J) - 1.8.1 (WP)  Changed visibility from private.
     */
    public function safeContent(string $content)
    {
        return preg_replace('/([\,;])/', '\\\$1', $content);
    }
}
