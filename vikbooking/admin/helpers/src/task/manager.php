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
 * Task manager implementation.
 * 
 * @since   1.18.0 (J) - 1.8.0 (WP)
 */
final class VBOTaskManager
{
    /**
     * @var  array
     */
    private $drivers = [];

    /**
     * @var  array
     */
    private $errors = [];

    /**
     * @var  array
     */
    private $statusGroupTypes = [];

    /**
     * @var  array
     */
    private $statusTypes = [];

    /**
     * @var  string
     */
    private $taskClassPrefix = 'VBOTaskDriver';

    /**
     * @var  string
     */
    private $taskStatusGroupClassPrefix = 'VBOTaskStatusGroupType';

    /**
     * @var  string
     */
    private $taskStatusClassPrefix = 'VBOTaskStatusType';

    /**
     * Class constructor.
     */
    public function __construct()
    {
        // pre-load the available task drivers
        $this->loadDrivers();

        // pre-load the available task status group type implementations
        $this->loadStatusGroupTypes();

        // pre-load the available task status type implementations
        $this->loadStatusTypes();
    }

    /**
     * Triggers the operations for scheduling tasks across all
     * projects/areas upon a booking confirmation event.
     * 
     * @param   array   $booking        The booking record.
     * @param   array   $booking_rooms  The booking room records.
     * 
     * @return  bool
     */
    public function processBookingConfirmation(array $booking, array $booking_rooms = [])
    {
        // reset the errors pool before starting
        $this->errors = [];

        // wrap the booking information into a registry
        $taskBooking = VBOTaskBooking::getInstance($booking, $booking_rooms);

        // iterate over all the active projects/areas, if any
        foreach ($this->getAreas() as $area) {
            // wrap the execution within a try-catch statement
            try {
                // bind the area record within a task-area registry
                $area = VBOTaskArea::getInstance((array) $area);

                // invoke the task area driver
                $taskDriver = $this->getDriverInstance($area->getType(), [$area]);

                // schedule tasks upon booking confirmation
                $taskDriver->scheduleBookingConfirmation($taskBooking);

                if ($newTasks = $taskDriver->getCollector()->getCreated()) {
                    // store booking history record
                    VikBooking::getBookingHistoryInstance($taskBooking->getID())
                        ->setBookingData($booking, $booking_rooms)
                        ->setExtraData(array_column($newTasks, 'id'))
                        ->store('NT', implode(', ', array_map(function($id) {
                            return sprintf('#%d', $id);
                        }, array_column($newTasks, 'id'))));
                }
            } catch (Throwable $e) {
                // push the error caught
                $this->errors[] = $e;
            }
        }

        return (bool) (!$this->errors);
    }

    /**
     * Triggers the operations for re-scheduling tasks across all
     * projects/areas upon a booking modification event.
     * 
     * @param   array   $booking        The booking record.
     * @param   array   $booking_rooms  The booking room records.
     * @param   array   $prev_booking   The previous booking record.
     * 
     * @return  bool
     */
    public function processBookingModification(array $booking, array $booking_rooms = [], array $prev_booking = [])
    {
        // reset the errors pool before starting
        $this->errors = [];

        // wrap the booking information into a registry
        $taskBooking = VBOTaskBooking::getInstance($booking, $booking_rooms, $prev_booking);

        // iterate over all the active projects/areas, if any
        foreach ($this->getAreas() as $area) {
            // wrap the execution within a try-catch statement
            try {
                // bind the area record within a task-area registry
                $area = VBOTaskArea::getInstance((array) $area);

                // invoke the task area driver
                $taskDriver = $this->getDriverInstance($area->getType(), [$area]);

                // re-schedule tasks upon booking alteration, if needed
                $taskDriver->scheduleBookingAlteration($taskBooking);

                if ($modifiedTasks = $taskDriver->getCollector()->getModified()) {
                    // store booking history record
                    VikBooking::getBookingHistoryInstance($taskBooking->getID())
                        ->setBookingData($booking, $booking_rooms)
                        ->setExtraData(array_column($modifiedTasks, 'id'))
                        ->store('MT', implode(', ', array_map(function($id) {
                            return sprintf('#%d', $id);
                        }, array_column($modifiedTasks, 'id'))));
                }
            } catch (Throwable $e) {
                // push the error caught
                $this->errors[] = $e;
            }
        }

        return (bool) (!$this->errors);
    }

    /**
     * Triggers the operations for un-scheduling tasks across all
     * projects/areas upon a booking cancellation event.
     * 
     * @param   array   $booking        The booking record.
     * @param   array   $booking_rooms  The booking room records.
     * 
     * @return  bool
     */
    public function processBookingCancellation(array $booking, array $booking_rooms = [])
    {
        // reset the errors pool before starting
        $this->errors = [];

        // wrap the booking information into a registry
        $taskBooking = VBOTaskBooking::getInstance($booking, $booking_rooms);

        // iterate over all the active projects/areas, if any
        foreach ($this->getAreas() as $area) {
            // wrap the execution within a try-catch statement
            try {
                // bind the area record within a task-area registry
                $area = VBOTaskArea::getInstance((array) $area);

                // invoke the task area driver
                $taskDriver = $this->getDriverInstance($area->getType(), [$area]);

                // un-schedule tasks upon booking cancellation
                $taskDriver->scheduleBookingCancellation($taskBooking);

                if ($oldTasks = $taskDriver->getCollector()->getCancelled()) {
                    // store booking history record
                    VikBooking::getBookingHistoryInstance($taskBooking->getID())
                        ->setBookingData($booking, $booking_rooms)
                        ->setExtraData(array_column($oldTasks, 'id'))
                        ->store('CT', implode(', ', array_map(function($id) {
                            return sprintf('#%d', $id);
                        }, array_column($oldTasks, 'id'))));
                }
            } catch (Throwable $e) {
                // push the error caught
                $this->errors[] = $e;
            }
        }

        return (bool) (!$this->errors);
    }

    /**
     * Returns the current execution errors, if any.
     * 
     * @return  array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Resets the current execution errors.
     * 
     * @return  VBOTaskManager
     */
    public function resetErrors()
    {
        $this->errors = [];

        return $this;
    }

    /**
     * Returns all the active task area objects.
     * 
     * @return  array
     */
    public function getAreas()
    {
        return VBOTaskModelArea::getInstance()->getItems();
    }

    /**
     * Returns all areas with an active visibility by default.
     * Relies on the current session by default, then on the db.
     * 
     * @param   int     $start  Query limit start.
     * @param   int     $lim    Query records limit.
     * 
     * @return  array   List of visible area items, if any.
     */
    public function getVisibleAreas(int $start = 0, int $lim = 0)
    {
        $active_area_ids = (array) JFactory::getSession()->get('tm.active_area_ids', [], 'vikbooking');

        if ($active_area_ids) {
            // get all areas stored in the session
            return VBOTaskModelArea::getInstance()->getItems([
                'id' => [
                    'value' => $active_area_ids,
                ],
            ], 0, 0);
        }

        // read the active areas from the db
        $active_areas = VBOTaskModelArea::getInstance()->getItems([
            'display' => [
                'value' => 1,
            ],
        ], $start, $lim);

        // set them as active
        $this->setVisibleArea(array_column($active_areas, 'id'));

        return $active_areas;
    }

    /**
     * Sets visible areas in the PHP Session.
     * 
     * @param   int|array   $id     The area ID(s) to set as visible.
     * 
     * @return  void
     */
    public function setVisibleArea($id)
    {
        $session = JFactory::getSession();

        if (!is_array($id)) {
            $id = (array) $id;
        }

        $id = array_map('intval', $id);

        $active_area_ids = array_map('intval', (array) $session->get('tm.active_area_ids', [], 'vikbooking'));
        $active_area_ids = array_values(array_unique(array_merge($active_area_ids, $id)));

        $session->set('tm.active_area_ids', $active_area_ids, 'vikbooking');
    }

    /**
     * Unsets visible areas in the PHP Session.
     * 
     * @param   int|array   $id     The area ID(s) to unset as visible.
     * 
     * @return  void
     */
    public function unsetVisibleArea($id)
    {
        $session = JFactory::getSession();

        if (!is_array($id)) {
            $id = (array) $id;
        }

        $id = array_map('intval', $id);

        $active_area_ids = array_map('intval', (array) $session->get('tm.active_area_ids', [], 'vikbooking'));
        $active_area_ids = array_values(array_unique(array_diff($active_area_ids, $id)));

        $session->set('tm.active_area_ids', $active_area_ids, 'vikbooking');
    }

    /**
     * Returns a list of areas that were configured as private,
     * hence not visible to operators within the front-end.
     * 
     * @return  array   List of private area IDs, or empty array.
     */
    public function getPrivateAreas()
    {
        $privateAreaIds = [];

        foreach ($this->getAreas() as $areaRecord) {
            $area = VBOTaskArea::getInstance((array) $areaRecord);
            if ($area->isPrivate()) {
                $privateAreaIds[] = $area->getID();
            }
        }

        return array_values(array_filter($privateAreaIds));
    }

    /**
     * Returns a list of default tag colors.
     * 
     * @param   bool    $keys   True to return only the color identifiers.
     * 
     * @return  array
     */
    public function getTagColors(bool $keys = false)
    {
        $def_tag_colors = [
            'red'    => '#fbdcd9',
            'green'  => '#daebdc',
            'olive'  => '#c7d8b4',
            'blue'   => '#bed6fb',
            'ocean'  => '#d2e5f2',
            'brown'  => '#f0dfd7',
            'yellow' => '#f8e5b3',
            'orange' => '#ffe3ca',
            'purple' => '#e8ddee',
            'pink'   => '#f6dfe9',
            'black'  => '#d0d0d0',
            'gray'   => '#e5e4e0',
        ];

        return $keys ? array_keys($def_tag_colors) : $def_tag_colors;
    }

    /**
     * Returns a default list of color tags to be used when none is available.
     * 
     * @return  object[]   List of dummy color tag objects.
     */
    public function buildDefaultColorTags()
    {
        // build the default list of color tags
        $defaultTags = [
            [
                'name' => JText::translate('VBO_IMPORTANT'),
                'color' => 'red',
            ],
            [
                'name' => JText::translate('VBO_SUPERVISOR_REVIEW'),
                'color' => 'yellow',
            ],
            [
                'name' => JText::translate('VBO_TM_SCHED_CLEANING_TURNOVER'),
                'color' => 'blue',
            ],
            [
                'name' => JText::translate('VBO_TM_SCHED_CLEANING_DAILY'),
                'color' => 'green',
            ],
            [
                'name' => JText::translate('VBO_CHANGE_LINENS'),
                'color' => 'ocean',
            ],
            [
                'name' => JText::translate('VBO_TM_SCHED_CLEANING_WEEKLY'),
                'color' => 'olive',
            ],
            [
                'name' => JText::translate('VBO_DEEP_CLEANING'),
                'color' => 'purple',
            ],
            [
                'name' => JText::translate('VBO_INSPECTION_NEEDED'),
                'color' => 'orange',
            ],
            [
                'name' => JText::translate('VBO_GUEST_REQUEST'),
                'color' => 'pink',
            ],
            [
                'name' => JText::translate('VBO_MAINTENANCE_ALERT'),
                'color' => 'brown',
            ],
            [
                'name' => JText::translate('VBO_NO_SERVICE_REQUESTED'),
                'color' => 'gray',
            ],
            [
                'name' => JText::translate('VBO_HIGH_PRIORITY'),
                'color' => 'black',
            ],
        ];

        // cast to objects
        foreach ($defaultTags as &$tag) {
            $tag = (object) $tag;
        }

        unset($tag);

        return $defaultTags;
    }

    /**
     * Returns all the available or requested color tags.
     * 
     * @param   array   $ids    Optional list of tag IDs to fetch.
     * 
     * @return  array
     */
    public function getColorTags(array $ids = [])
    {
        // access the color tags model
        $ctagModel = VBOTaskModelColortag::getInstance();

        if ($ids) {
            // return the requested tag IDs
            return $ctagModel->getItems([
                'id' => [
                    'value' => $ids,
                ],
            ]);
        }

        // load all items
        $tags = $ctagModel->getItems();

        if (!$tags) {
            // create at runtime the default tags for the first time
            $tags = $this->buildDefaultColorTags();

            // store the default tags
            foreach ($tags as $tag) {
                $tag->id = $ctagModel->save($tag);
            }
        }

        return $tags;
    }

    /**
     * Attempts to instantiate the requested driver by passing the provided constructor arguments.
     * 
     * @param   string  $driver     The driver file key identifier.
     * @param   array   $args       List of arguments for constructing the object.
     * 
     * @return  VBOTaskDriverinterface
     * 
     * @throws  InvalidArgumentException
     */
    public function getDriverInstance(string $driver, array $args = [])
    {
        $className = $this->buildDriverClassName($driver);

        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Could not load task driver [%s]', $driver), 500);
        }

        // construct the task driver object by passing the args through the splat operator
        return new $className(...$args);
    }

    /**
     * Returns the associative list of the available driver names.
     * 
     * @param   array   $args       Optional list of arguments for constructing the objects.
     * 
     * @return  array
     */
    public function getDriverNames(array $args = [])
    {
        $list = [];

        foreach ($this->drivers as $key => $path) {
            try {
                $taskDriver = $this->getDriverInstance($key, $args);
                $driverId = $taskDriver->getID() ?: $key;
                $list[$driverId] = $taskDriver->getName();
            } catch (Exception $e) {
                // silently catch the error
            }
        }

        return $list;
    }

    /**
     * Returns the list of the drivers loaded so far.
     * 
     * @return  array
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * Tells whether a driver exists, meaning that it was loaded.
     * 
     * @param   string  $driver     The driver file key identifier.
     * 
     * @return  bool
     */
    public function driverExists(string $driver)
    {
        return isset($this->drivers[$driver]);
    }

    /**
     * Builds the current task booking information for rendering the record as element.
     * 
     * @param   int     $bid    The booking record ID.
     * 
     * @return  array
     */
    public function buildBookingElement(int $bid)
    {
        if (!$bid) {
            return [];
        }

        $booking = VikBooking::getBookingInfoFromID($bid);
        if (!$booking) {
            return [];
        }

        $customer = VikBooking::getCPinInstance()->getCustomerFromBooking($booking['id']);

        // build booking element
        $element = [
            'id'         => $booking['id'],
            'text'       => $booking['id'],
            'img'        => '',
            'icon_class' => VikBookingIcons::i('hotel'),
        ];

        if (!empty($customer['first_name'])) {
            // use customer nominative when available
            $element['text'] = trim($customer['first_name'] . ' ' . $customer['last_name']);
        } elseif (!empty($booking['custdata'])) {
            $element['text'] = VikBooking::getFirstCustDataField($booking['custdata']);
        }

        // build "img" property
        if (!empty($customer['pic'])) {
            // use guest profile picture
            $element['img'] = strpos($customer['pic'], 'http') === 0 ? $customer['pic'] : VBO_SITE_URI . 'resources/uploads/' . $customer['pic'];
        } elseif (!empty($booking['channel'])) {
            // use channel logo
            $ch_logo_obj = VikBooking::getVcmChannelsLogo($booking['channel'], true);
            $element['img'] = is_object($ch_logo_obj) ? $ch_logo_obj->getTinyLogoURL() : '';
        }

        if (!empty($element['img'])) {
            // unset the default icon class
            unset($element['icon_class']);
        }

        return $element;
    }

    /**
     * Returns a list of task statuses sorted by group types to be rendered as elements.
     * 
     * @param   array   $statuses   Optional list of task status enumerations.
     * @param   bool    $flatten    True to ignore the groups and return a linear list of statuses.
     * 
     * @return  array
     */
    public function getStatusGroupElements(array $statuses = [], bool $flatten = false)
    {
        $groupElements = [];

        if (!$statuses) {
            $statuses = $this->getStatusTypes(true);
        }

        foreach ($statuses as $statusId) {
            // get status type object
            $statusType = $this->getStatusTypeInstance($statusId);

            // get status type values
            $statusEnum = $statusType->getEnum();
            $statusName = $statusType->getName();
            $statusColor = $statusType->getColor();
            $statusGroup = $statusType->getGroupEnum();
            $statusOrdering = $statusType->getOrdering();

            // get status group details
            $groupName = $statusGroup;
            $groupOrdering = 1;
            if ($this->statusGroupTypeExists($statusGroup)) {
                // get status group type object
                $groupType = $this->getStatusGroupTypeInstance($statusGroup);

                // set status group details
                $groupName = $groupType->getName();
                $groupOrdering = $groupType->getOrdering();
            }

            if (!isset($groupElements[$statusGroup])) {
                // start group container
                $groupElements[$statusGroup] = [
                    'text'     => $groupName,
                    'ordering' => $groupOrdering,
                    'elements' => [],
                ];
            }

            // push status
            $groupElements[$statusGroup]['elements'][] = [
                'id'       => $statusEnum,
                'text'     => $statusName,
                'color'    => $statusColor,
                'ordering' => $statusOrdering,
            ];
        }

        // sort groups by ordering value ascending
        uasort($groupElements, function($a, $b) {
            return $a['ordering'] <=> $b['ordering'];
        });

        // iterate all status groups to sort the statuses by ordering
        foreach ($groupElements as &$statusGroup) {
            // sort statuses by ordering value ascending
            usort($statusGroup['elements'], function($a, $b) {
                return $a['ordering'] <=> $b['ordering'];
            });
        }

        // unset last reference
        unset($statusGroup);

        if ($flatten) {
            $statuses = [];

            foreach ($groupElements as $group) {
                foreach ($group['elements'] as $status) {
                    $statuses[] = $status;
                }
            }

            $groupElements = $statuses;
        }

        // return the sorted list
        return $groupElements;
    }

    /**
     * Attempts to instantiate the requested status group type.
     * 
     * @param   string  $group     The group file key identifier.
     * 
     * @return  VBOTaskStatusGroupInterface
     * 
     * @throws  InvalidArgumentException
     */
    public function getStatusGroupTypeInstance(string $group)
    {
        $className = $this->buildStatusGroupTypeClassName($group);

        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Could not load task status group type [%s]', $group), 500);
        }

        return new $className;
    }

    /**
     * Returns the list of the task status group types loaded so far.
     * 
     * @return  array
     */
    public function getStatusGroupTypes()
    {
        return $this->statusGroupTypes;
    }

    /**
     * Tells whether a status group type exists, meaning that it was loaded.
     * 
     * @param   string  $group     The group file key identifier.
     * 
     * @return  bool
     */
    public function statusGroupTypeExists(string $group)
    {
        return isset($this->statusGroupTypes[$group]);
    }

    /**
     * Attempts to instantiate the requested status type.
     * 
     * @param   string  $status     The status file key identifier.
     * 
     * @return  VBOTaskStatusInterface
     * 
     * @throws  InvalidArgumentException
     */
    public function getStatusTypeInstance(string $status)
    {
        $className = $this->buildStatusTypeClassName($status);

        if (!class_exists($className)) {
            throw new InvalidArgumentException(sprintf('Could not load task status type [%s]', $status), 500);
        }

        return new $className;
    }

    /**
     * Returns the list of the task status status types loaded so far.
     * 
     * @param   bool    $enums  True to get a list of status enumerations.
     * 
     * @return  array
     */
    public function getStatusTypes(bool $enums = false)
    {
        return $enums ? array_keys($this->statusTypes) : $this->statusTypes;
    }

    /**
     * Tells whether a status type exists, meaning that it was loaded.
     * 
     * @param   string  $status     The status file key identifier.
     * 
     * @return  bool
     */
    public function statusTypeExists(string $status)
    {
        return isset($this->statusTypes[$status]);
    }

    /**
     * Builds the task status status type class name.
     * 
     * @param   string  $status     The status file key identifier.
     * 
     * @return  string              Status type class name or empty string.
     */
    private function buildStatusTypeClassName(string $status)
    {
        if (!$this->statusTypeExists($status)) {
            return '';
        }

        return $this->taskStatusClassPrefix . ucfirst(strtolower($status));
    }

    /**
     * Builds the task status group type class name.
     * 
     * @param   string  $group  The group file key identifier.
     * 
     * @return  string          Status group type class name or empty string.
     */
    private function buildStatusGroupTypeClassName(string $group)
    {
        if (!$this->statusGroupTypeExists($group)) {
            return '';
        }

        return $this->taskStatusGroupClassPrefix . ucfirst(strtolower($group));
    }

    /**
     * Builds the task driver class name.
     * 
     * @param   string  $driver     The driver file key identifier.
     * 
     * @return  string              Driver class name or empty string.
     */
    private function buildDriverClassName(string $driver)
    {
        if (!$this->driverExists($driver)) {
            return '';
        }

        return $this->taskClassPrefix . ucfirst(strtolower($driver));
    }

    /**
     * Pre-loads all the available task driver implementations.
     * 
     * @return  void
     */
    private function loadDrivers()
    {
        $drivers_base  = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'helpers', 'src', 'task', 'driver', '']);
        $drivers_files = glob($drivers_base . '*.php');

        /**
         * Trigger event to let other plugins register additional drivers.
         *
         * @return  array   A list of supported drivers.
         */
        $list = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadTaskManagerDrivers');
        foreach ($list as $chunk) {
            // merge default driver files with the returned ones
            $drivers_files = array_merge($drivers_files, (array) $chunk);
        }

        foreach ($drivers_files as $df) {
            // push driver file key identifier and set related path
            $driver_base_name = basename($df, '.php');
            $this->drivers[$driver_base_name] = $df;
        }
    }

    /**
     * Pre-loads all the available task status group type implementations.
     * 
     * @return  void
     */
    private function loadStatusGroupTypes()
    {
        $drivers_base  = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'helpers', 'src', 'task', 'status', 'group', 'type', '']);
        $drivers_files = glob($drivers_base . '*.php');

        /**
         * Trigger event to let other plugins register additional status group types.
         *
         * @return  array   A list of supported status group types.
         */
        $list = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadTaskManagerStatusGroupTypes');
        foreach ($list as $chunk) {
            // merge default driver files with the returned ones
            $drivers_files = array_merge($drivers_files, (array) $chunk);
        }

        foreach ($drivers_files as $df) {
            // push driver file key identifier and set related path
            $driver_base_name = basename($df, '.php');
            $this->statusGroupTypes[$driver_base_name] = $df;
        }
    }

    /**
     * Pre-loads all the available task status type implementations.
     * 
     * @return  void
     */
    private function loadStatusTypes()
    {
        $drivers_base  = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'helpers', 'src', 'task', 'status', 'type', '']);
        $drivers_files = glob($drivers_base . '*.php');

        /**
         * Trigger event to let other plugins register additional status types.
         *
         * @return  array   A list of supported status types.
         */
        $list = VBOFactory::getPlatform()->getDispatcher()->filter('onLoadTaskManagerStatusTypes');
        foreach ($list as $chunk) {
            // merge default driver files with the returned ones
            $drivers_files = array_merge($drivers_files, (array) $chunk);
        }

        foreach ($drivers_files as $df) {
            // push driver file key identifier and set related path
            $driver_base_name = basename($df, '.php');
            $this->statusTypes[$driver_base_name] = $df;
        }
    }
}
