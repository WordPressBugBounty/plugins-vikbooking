<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * This is the layout file for the "task_manager" operator tool.
 * 
 * @var string 	$tool 		   The tool identifier.
 * @var array 	$operator      The operator record accessing the tool.
 * @var object 	$permissions   The operator-tool permissions registry.
 * @var string 	$tool_uri 	   The base URI for rendering this tool.
 */
extract($displayData);

/**
 * Load the VBOCore JS class and chat assets.
 */
$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadCoreJS();
$vbo_app->loadContextMenuAssets();
VBOFactory::getChatMediator()->useAssets();
VikBookingIcons::loadRemoteAssets();

$geo = VikBooking::getGeocodingInstance();

if ($geo->isSupported()) {
    $geo->loadAssets();
}

JHtml::fetch('vbohtml.tmscripts.changestatus');

// access the CMS application
$app = JFactory::getApplication();

// get the current filters
$filters = (array) $app->getUserStateFromRequest("vbo.tm.filters", 'filters', [], 'array');

// determine the current calendar type
$allowedTypes = [
    'month',
    'day',
    'taskdetails',
];
$calendarType = $filters['calendar_type'] ?? $allowedTypes[0];
$calendarType = in_array($calendarType, $allowedTypes) ? $calendarType : $allowedTypes[0];

// make sure to set the proper calendar type filter
$filters['calendar_type'] = $calendarType;

// build operator TM iCal URL
$operator_signature = base64_encode($operator['id'] . ':' . md5($operator['code']));
$tm_ical_uri = VBOFactory::getPlatform()->getUri()->route('index.php?option=com_vikbooking&view=operators&task=operatortool.tm_ical&opsid=' . urlencode($operator_signature));

// get the list of area/project IDs to which the current operator belongs
$areaIds = VBOTaskModelArea::getInstance()->getAssigneeItems($operator['id']);

// get the current operator permissions
$accept_tasks = (bool) ($permissions ? $permissions->get('accept_tasks', 0) : 0);

// check from the permissions whether tasks can be accepted by the operator,
// hence null assigness should be included - use a different filter otherwise
$operatorTasksFilterName = $accept_tasks ? 'operator' : 'assignee';

// build filter options for counting all future tasks (from the right areas/projects if fetching also the unassigned tasks)
$fetchOptions = array_merge($filters, [
    $operatorTasksFilterName => $operator['id'],
    'id_areas' => $accept_tasks ? $areaIds : null,
    'future' => true,
]);
$futureTasksCount = VBOTaskModelTask::getInstance()->filterItems($fetchOptions, 0, 1, $count = true);

// count future tasks assigned and unassigned, if permissions enabled
$assignedTasksCount = 0;
$unassignedTasksCount = 0;
if ($accept_tasks) {
    // build filter options for counting the future tasks assigned
    $fetchOptions = array_merge($filters, [
        'assignee' => $operator['id'],
        'future' => true,
    ]);
    $assignedTasksCount = VBOTaskModelTask::getInstance()->filterItems($fetchOptions, 0, 1, $count = true);

    // build filter options for counting the future tasks unassigned (yet from the right areas/projects)
    $fetchOptions = array_merge($filters, [
        'assignee' => -1,
        'id_areas' => $areaIds,
        'future' => true,
    ]);
    $unassignedTasksCount = VBOTaskModelTask::getInstance()->filterItems($fetchOptions, 0, 1, $count = true);
}

?>

<!--
    Create CSS rule to "hide" the textarea by keeping it
    active to allow the browser to copy its contents.
-->

<style>
    input.keep-active-but-hidden {
        width: 0 !important;
        height: 0 !important;
        opacity: 0 !important;
        float: right;
        cursor: default;
    }
</style>

<div class="vbo-tm-operator-head">
    <div class="vbo-tm-operator-block" data-type="future_tasks">
        <div class="vbo-tm-operator-block-title"><?php echo JText::translate('VBO_FUTURE_TASKS'); ?></div>
        <div class="vbo-tm-operator-block-cont">
            <span><?php echo $futureTasksCount; ?></span>
        </div>
        <div class="vbo-tm-operator-block-icon"><?php VikBookingIcons::e('tasks'); ?></div>
    </div>
<?php
if ($accept_tasks) {
    ?>
    <div class="vbo-tm-operator-block" data-type="assigned_tasks">
        <div class="vbo-tm-operator-block-title"><?php echo JText::translate('VBO_ASSIGNED_TASKS'); ?></div>
        <div class="vbo-tm-operator-block-cont">
            <span><?php echo $assignedTasksCount; ?></span>
        </div>
        <div class="vbo-tm-operator-block-icon"><?php VikBookingIcons::e('user-check'); ?></div>
    </div>
    <div class="vbo-tm-operator-block" data-type="unassigned_tasks">
        <div class="vbo-tm-operator-block-title"><?php echo JText::translate('VBO_UNASSIGNED_TASKS'); ?></div>
        <div class="vbo-tm-operator-block-cont">
            <span><?php echo $unassignedTasksCount; ?></span>
        </div>
        <div class="vbo-tm-operator-block-icon"><?php VikBookingIcons::e('user-plus'); ?></div>
    </div>
    <?php
}
?>
    <div class="vbo-tm-operator-block" data-type="ical">
        <div class="vbo-tm-operator-block-cont">
            <a href="javascript:void(0)" data-url="<?php echo $tm_ical_uri; ?>" id="subscribe-calendar-url">
                <span class="long"><?php echo JText::translate('VBO_SUBSCRIBE_CALENDAR'); ?></span>
                <span class="short"><?php echo JText::translate('VBO_SUBSCRIBE'); ?></span>
                <i class="<?php echo VikBookingIcons::i('chevron-down'); ?>" style="margin-left: 4px"></i>
            </a>
            <input type="text" id="subscribe-calendar-url-input" value="<?php echo $tm_ical_uri; ?>" class="keep-active-but-hidden" readonly />
        </div>
        <div class="vbo-tm-operator-block-icon"><?php VikBookingIcons::e('calendar'); ?></div>
    </div>
</div>

<div class="vbo-tm-calendar-tasks-wrap">
<?php
// display task manager calendar month layout with NO tasks
$layout_data = [
    'tool'        => $tool,
    'operator'    => $operator,
    'permissions' => $permissions,
    'tool_uri'    => $tool_uri,
    'data' => [
        'filters' => $filters,
    ],
    // inject flag for not loading any tasks and display just an empty calendar
    'no_tasks' => 1,
];

echo JLayoutHelper::render('taskmanager.calendar.' . $allowedTypes[0], $layout_data, null, [
    'component' => 'com_vikbooking',
    'client'    => 'site',
]);
?>
</div>

<script type="text/javascript">
    /**
     * Register global taskmanager filters.
     */
    const vboTmFilters = <?php echo json_encode(($filters ?: (new stdClass))); ?>;

    /**
     * Listen to the event for applying new filters.
     */
    document.addEventListener('vbo-tm-apply-filters', (e) => {
        if (!e || !e.detail || !e.detail.filters || typeof e.detail.filters !== 'object') {
            return;
        }

        for (const [type, value] of Object.entries(e.detail.filters)) {
            // set the requested filter value
            vboTmFilters[type] = value;
        }

        // dispatch the filters-changed event
        VBOCore.emitEvent('vbo-tm-filters-changed', {
            filters: vboTmFilters,
        });
    });

    /**
     * Register listener for the filters changed event.
     */
    document.addEventListener('vbo-tm-filters-changed', (e) => {
        // obtain the global filters
        let filters = e?.detail?.filters;

        // re-render tasks calendar
        vboTmCalendarLoadTasks(filters);

        const currentURL = new URL('<?php echo VBOFactory::getPlatform()->getUri()->route('index.php?option=com_vikbooking&view=operators&tool=task_manager'); ?>');

        for (const [key, val] of Object.entries(filters)) {
            if (val) {
                // set the requested filter value in new URL
                currentURL.searchParams.append(`filters[${key}]`, val);
            }
        }

        // change browser URL without performing any refresh
        history.replaceState(null, '', currentURL);
    });

    /**
     * Register listener to refresh the details of a task whenever the status changes.
     */
    document.addEventListener('vbo-task-status-changed', (event) => {
        VBOCore.emitEvent('vbo-tm-apply-filters', {
            filters: {}
        });
    });

    /**
     * Register function to activate the month loading animation.
     */
    const vboTmCalendarSetMonthLoading = (filters) => {
        let month_info = document.querySelector('.vbo-tm-calendar-info');

        if (!month_info) {
            return;
        }

        month_info.innerHTML = '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>';
    };

    /**
     * Register function to load the area tasks.
     */
    const vboTmCalendarLoadTasks = (filters) => {
        // activate loading
        vboTmCalendarSetMonthLoading();

        // detect the calendar layout type to load
        let calendarType = filters && filters?.calendar_type ? filters.calendar_type : '<?php echo $calendarType; ?>';

        // access the tasks wrapper
        let tasksWrapper = document.querySelector('.vbo-tm-calendar-tasks-wrap');

        // destroy the current chat before loading the new task details, if any
        VBOChat.getInstance().destroy();

        // make the request for loading the tasks
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=operatortool.renderLayout'); ?>",
            {
                tool: '<?php echo $tool; ?>',
                type: calendarType == 'taskdetails' ? 'taskmanager.' + calendarType : 'taskmanager.calendar.' + calendarType,
                data: {
                    filters: filters || vboTmFilters || {},
                },
            },
            (resp) => {
                try {
                    // decode the response (if needed)
                    let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;

                    // set the loading result
                    jQuery(tasksWrapper).html(obj_res['html']);
                } catch (err) {
                    console.error('Error decoding the response', err, resp);
                }
            },
            (error) => {
                // display error message
                alert(error.responseText);

                /**
                 * In case of permission errors, the interface may stick to the same page.
                 * For this reason, in case we were trying to access a task, we should reload
                 * the default monthly calendar.
                 */
                if (vboTmFilters.calendar_type === 'taskdetails') {
                    vboTmFilters.calendar_type = 'month';
                    delete vboTmFilters.task_id;

                    VBOCore.emitEvent('vbo-tm-filters-changed', {filters: vboTmFilters});
                }
            }
        );
    };

    VBOCore.DOMLoaded(() => {
        /**
         * Load calendar tasks upon page loading.
         */
        vboTmCalendarLoadTasks();
    });

    (function($) {
        'use strict';

        $(function() {
            $('#subscribe-calendar-url').vboContextMenu({
                placement: 'bottom-right',
                buttons: [
                    // Apple iCal
                    {
                        text: 'Apple iCal',
                        icon: '<?php echo VikBookingIcons::i('fab fa-apple'); ?>',
                        action: (root, event) => {
                            // replace HTTP(S) with WEBCAL protocol
                            let url = $(root).data('url').replace(/^https?:\/\//, 'webcal://');

                            setTimeout(function() {
                                // open subscription URL in a new browser page
                                window.open(url, '_blank');
                            }, 256);
                        },
                    },
                    // Google Calendar
                    {
                        text: 'Google Calendar',
                        icon: '<?php echo VikBookingIcons::i('fab fa-google'); ?>',
                        action: (root, event) => {
                            // replace HTTP(S) with WEBCAL protocol
                            let url = $(root).data('url').replace(/^https?:\/\//, 'webcal://');
                            // encode URL and prepend Google Calendar renderer
                            url = 'https://www.google.com/calendar/render?cid=' + encodeURIComponent(url);

                            setTimeout(function() {
                                // open subscription URL in a new browser page
                                window.open(url, '_blank');
                            }, 256);
                        },
                    },
                    // Other
                    {
                        text: <?php echo json_encode(JText::translate('VBO_OTHER_CALENDAR')); ?>,
                        icon: '<?php echo VikBookingIcons::i('calendar-alt'); ?>',
                        separator: true,
                        action: (root, event) => {
                            // copy URL within the clipboard
                            VBOCore.copyToClipboard(document.getElementById('subscribe-calendar-url-input')).then((success) => {
                                alert(<?php echo json_encode(JText::translate('VBO_CALENDAR_COPIED_OK')); ?>);
                            }).catch((err) => {
                                alert('Copy error!');
                            });
                        },
                    },
                    // Download
                    {
                        text: <?php echo json_encode(JText::translate('VBO_DOWNLOAD')); ?>,
                        icon: '<?php echo VikBookingIcons::i('download'); ?>',
                        action: (root, event) => {
                            // open download link in a new page
                            window.open($(root).data('url'), '_blank');
                        },
                    }
                ],
            });
        });
    })(jQuery);

    (function($) {
        'use strict';

        /**
         * Defines the ratio to scale the size of the elements.
         *
         * @var float
         */
        let TABLE_SCALE_RATIO = 2.5;

        /**
         * Checks whether the specified intervals collide.
         *
         * @param   Date  start1  The initial date time of the first interval.
         * @param   Date  end1    The ending date time of the first interval.
         * @param   Date  start2  The initial date time of the second interval.
         * @param   Date  end1    The ending date time of the second interval.
         *
         * @return  boolean 
         */
        const checkIntersection = (start1, end1, start2, end2) => {
            return (start1 <= start2 && start2 <  end1)
                || (start1 <  end2   && end2   <= end1)
                || (start2 <  start1 && end1   <  end2);
        }

        /**
         * Proxy used to speed up the usage of checkIntersection by passing 2 valid events.
         *
         * @param   object  event1  The first event.
         * @param   object  event2  The second event.
         *
         * @return  boolean 
         */
        const checkEventsIntersection = (event1, event2) => {
            return checkIntersection(
                new Date(event1.start),
                new Date(event1.end),
                new Date(event2.start),
                new Date(event2.end)
            );
        }

        /**
         * Returns a list containing all the events that collide with the specified one.
         *
         * @param   object  event  An object holding the event details.
         * @param   mixed   level  An optional threshold to obtain only the
         *                         events on the left of the specified one.
         *
         * @return  array
         */
        const countIntersections = (event, level) => {
            let list = [];

            $('.vbo-tm-calendar-day-timeline-rows').find('.event').each(function() {
                let event2 = $(this).data('event');

                if (checkEventsIntersection(event, event2)) {
                    if (typeof level === 'undefined' || parseInt($(this).data('index')) < level) {
                        list.push(this);
                    }
                }
            });

            return list;
        }

        /**
         * Recursively adjusts the location and size of all the events that
         * collide with the specified one.
         *
         * @param   object  event  An object holding the event details.
         *
         * @return  void
         */
        const fixSiblingsCount = (event) => {
            let did = [];

            // recursive fix
            _fixSiblingsCount(event, did);
        }

        /**
         * Recursively adjusts the location and size of all the events that
         * collide with the specified one.
         * @visibility protected
         *
         * @param   object  event  An object holding the event details.
         * @param   array   did    An array containing all the events that
         *                         have been already fixed, just to avoid
         *                         increasing them more than once.
         *
         * @return  void
         */
        const _fixSiblingsCount = (event, did) => {
            let index = parseInt($(event).data('index'));

            let intersections = countIntersections($(event).data('event'), index);

            if (intersections.length) {
                intersections.forEach((e) => {
                    let found = false;

                    // make sure we didn't already fetch this event
                    did.forEach((ei) => {
                        found = found || $(e).is(ei);
                    });

                    if (!found) {
                        // get counters
                        let tmp   = parseInt($(e).data('siblings'));
                        let index = parseInt($(e).data('index'));

                        // adjust counter, size and position
                        $(e).data('siblings', tmp + 1);
                        $(e).css('width', 'calc(calc(100% / ' + (tmp + 2) + ') - 2px)');
                        $(e).css('left', 'calc(calc(calc(100% / ' + (tmp + 2) + ') * ' + (index) + ') + 2px)');

                        // flag event as already adjusted
                        did.push(e);

                        // recursively adjust the colliding events
                        _fixSiblingsCount(e, did);
                    }
                });
            }
        }

        /**
         * Adds the specified event into the calendar.
         *
         * @param   object  data  An object holding the event details.
         *
         * @return  void
         */
        const addCalendarEvent = (data) => {
            // search the row matching the hour of the event
            const hourRow = $('.vbo-tm-calendar-day-timeline-rows').find('.vbo-tm-calendar-day-timeline-row[data-hour="' + data.hour + '"]');

            if (!hourRow.length) {
                return false;
            }

            // create event
            const event = $(data.html).addClass('event');
            delete data.html;

            // event.attr('id', 'event-' + data.id);
            // event.attr('data-order-id', data.id);
            event.data('event', data);

            if (data.duration <= 15) {
                event.addClass('xsmall-block');
            } else if (data.duration < 30) {
                event.addClass('small-block');
            }

            // calculate event offset from top
            let offset = (data.hour * 60 + data.min) * TABLE_SCALE_RATIO;
            // calculate the threshold that cannot be exceeded
            let ceil = 1440 * TABLE_SCALE_RATIO;

            // make sure the height doesn't exceed the ceil
            let height = Math.min(data.duration * TABLE_SCALE_RATIO, ceil - offset) - 1;

            // vertically locate and resize the event box
            event.css('top', (data.min * TABLE_SCALE_RATIO) + 'px');
            event.css('height', height + 'px');

            // set color according to the selected service
            // let color = ('' + data.service_color).replace(/^#/, '');

            // event.css('background-color', '#' + color + '80');
            // event.css('border-left-color', '#' + color);

            // count number of events that intersect the appointment
            let intersections = countIntersections(data);

            let count = 0;

            // find the highest index position among the colliding events
            intersections.forEach((e) => {
                count = Math.max(count, parseInt($(e).data('index')) + 1);
            });

            // init siblings counter and index with the amount previously found
            event.data('siblings', count);
            event.data('index', count);

            // recursively adjust the counter of any other colliding event
            fixSiblingsCount(event);

            // locate and size the event before attaching it
            event.css('width', 'calc(calc(100% / ' + (count + 1) + ') - 2px)');
            event.css('left', 'calc(calc(calc(100% / ' + (count + 1) + ') * ' + (count) + ') + 2px)');

            // attach event to calendar
            hourRow.find('.vbo-tm-calendar-day-tasks').append(event);
        }

        /**
         * Configures the calendar by adding all the specified events.
         *
         * @param   array  events  A list of events to append.
         *
         * @return  void
         */
        window.taskManagerSetupCalendar = (events) => {
            $('.vbo-tm-calendar-day-tasks').html('');

            if (!events.length) {
                // do nothing
                return;
            }

            // init events
            events.forEach((event) => {
                event.intersections = [];
            });

            // scan conflicts between times
            for (var i = 0; i < events.length - 1; i++) {
                for (var j = i + 1; j < events.length; j++) {
                    let a = events[i];
                    let b = events[j];

                    if (checkEventsIntersection(a, b)) {
                        a.intersections.push(b);
                        b.intersections.push(a);
                    }
                }
            }

            // sort events by conflicts and ascending time
            events.sort((a, b) => {
                let diff = a.intersections.length - b.intersections.length;

                if (diff == 0) {
                    // same intersections, sort by check-in time
                    diff = (a.hour * 60 + a.min) - (b.hour * 60 + b.min);
                }

                return diff;
            });

            // attach events to calendar one by one
            events.forEach((event) => {
                addCalendarEvent(event);
            });

            let bounds = [24, -1];

            // calculate the minimum and the maximum hours that hold at least a task
            events.forEach((event) => {
                bounds[0] = Math.min(bounds[0], event.hour);
                bounds[1] = Math.max(bounds[1], event.hour + Math.ceil(event.duration / 60));
            });

            // hide all the hours before and after the calculated bounds
            $('.vbo-tm-calendar-day-timeline-row[data-hour]').each(function() {
                const hour = parseInt($(this).data('hour'));

                if (hour < bounds[0] - 1 || hour > bounds[1] + 1) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        }
    })(jQuery);

</script>