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

// collect a list of active area IDs
$activeAreaIds = [];

foreach ($this->visibleAreas as $areaRecord) {
    // wrap the area record into a task area object
    $taskArea = VBOTaskArea::getInstance((array) $areaRecord);

    // push active area ID
    $activeAreaIds[] = $taskArea->getID();
}

// determine the current calendar type
$allowedTypes = [
	'month',
	'day',
];
$calendarType = $this->filters['calendar_type'] ?? $allowedTypes[0];
$calendarType = in_array($calendarType, $allowedTypes) ? $calendarType : $allowedTypes[0];

// make sure to set the proper calendar type filter
$this->filters['calendar_type'] = $calendarType;

?>

<div class="vbo-tm-calendar-tasks-wrap">
<?php
// display task manager calendar layout type with NO task areas
$layout_data = [
	'data' => [
		'filters'  => $this->filters,
		// empty list for task area IDs to load just an empty calendar
		'area_ids' => [],
	],
];

echo JLayoutHelper::render('taskmanager.calendar.' . $calendarType, $layout_data);
?>
</div>

<script type="text/javascript">
	/**
     * Register the global active area IDs.
     */
    const vboTmCalendarAreaIds = <?php echo json_encode($activeAreaIds); ?>;

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

    	// make the request for loading the tasks
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
            {
                type: 'calendar.' + calendarType,
                data: {
                    area_ids: vboTmCalendarAreaIds,
                    filters: filters || vboTmFilters || {},
                },
            },
            (resp) => {
                try {
                    // decode the response (if needed)
                    let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;

                    // set the loading result
                    jQuery(tasksWrapper).html(obj_res['html']);

                    // dispatch event for TM contents loaded
                    VBOCore.emitEvent('vbo-tm-contents-loaded', {
                        element: tasksWrapper,
                    });
                } catch (err) {
                    console.error('Error decoding the response', err, resp);
                }
            },
            (error) => {
                // display error message
                alert(error.responseText);
            }
        );
    };

    jQuery(function() {

        /**
         * Load calendar tasks upon page loading.
         */
        vboTmCalendarLoadTasks();

        /**
         * Register to the event for rendering the tasks of an area.
         */
        document.addEventListener('vbo-tm-area-render-task', (e) => {
            if (!e || !e?.detail?.areaId) {
                return;
            }

            let areaId = parseInt(e.detail.areaId);

            if (!vboTmCalendarAreaIds.includes(areaId)) {
                // push new area ID
                vboTmCalendarAreaIds.push(areaId);
            }

            // reload tasks
            vboTmCalendarLoadTasks();
            // toggle area visibility
            vboTmToggleAreaDisplay(areaId, 1);
        });

        /**
         * Register to the event for hiding the tasks of an area.
         */
        document.addEventListener('vbo-tm-area-hide-task', (e) => {
            if (!e || !e?.detail?.areaId) {
                return;
            }

            let areaId = parseInt(e.detail.areaId);

            let index = vboTmCalendarAreaIds.indexOf(areaId);
            if (index >= 0) {
                // remove the requested area ID
                vboTmCalendarAreaIds.splice(index, 1);
            }

            // reload tasks
            vboTmCalendarLoadTasks();
            // toggle area visibility
            vboTmToggleAreaDisplay(areaId, 0);
        });

        /**
         * Register listener for the filters changed event.
         */
        document.addEventListener('vbo-tm-filters-changed', (e) => {
            // obtain the global filters
            let filters = e?.detail?.filters;

            // re-render tasks calendar
            vboTmCalendarLoadTasks(filters);
        });

    });

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
