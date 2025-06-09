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
 * Obtain vars from arguments received in the layout file.
 * 
 * @var array  $data  The data for rendering the tasks of a given day within a calendar.
 */
extract($displayData);

// get the task area IDs
$areaIds = [];

if (is_array($data['area_ids'] ?? null)) {
    $areaIds = $data['area_ids'];
}

// get the filters for rendering the calendar tasks
$filters = (array) ($data['filters'] ?? []);

// determine the day to load
$dayYmd = $filters['calendar_day'] ?? date('Y-m-d');
if (($filters['dates'] ?? null) && !($filters['calendar_day'] ?? null)) {
    // when no calendar day filter set, use the current dates filter
    list($fromDt, $toDt) = VBOTaskModelTask::getInstance()->getFilterDatesInterval((string) $filters['dates'], $local = true, $sql = false);
    if ($fromDt) {
        $dayYmd = date('Y-m-d', strtotime($fromDt));
    }
}

// build the pool of day tasks
$dayTasks = [];

if ($areaIds) {
    // save current filters in the user state
    JFactory::getApplication()->setUserState('vbo.tm.filters', $filters);

    $filterOptions = array_merge($filters, [
        'id_areas' => $areaIds,
        'dates' => $dayYmd . ':' . $dayYmd,
        'calendar' => true,
    ]);

    // load tasks according to filters, by always forcing/injecting the area IDs and the dates
    foreach (VBOTaskModelTask::getInstance()->filterItems($filterOptions, 0, 0) as $taskRecord) {
        // push task registry
        $dayTasks[] = VBOTaskTaskregistry::getInstance((array) $taskRecord);
    }
}

// obtain the current day information
$dayDate = getdate(strtotime($dayYmd));

// get navigation dates
$todayYmd = date('Y-m-d');
$dayBack  = date('Y-m-d', strtotime('-1 day', $dayDate[0]));
$dayNext  = date('Y-m-d', strtotime('+1 day', $dayDate[0]));

// build the task objects, if any, for JS positioning
$taskObjects = [];
foreach ($dayTasks as $dayTask) {
    $duration = $dayTask->getDuration();

    $start = $dayTask->getDueDate(true, 'Y-m-d\TH:i:s');
    $begin = $dayTask->getBeginDate(true, 'Y-m-d\TH:i:s');

    if ($begin) {
        $start = $begin;
    }

    $startDate = JFactory::getDate($start);

    // get selected date at midnight
    $dateMidnight = JFactory::getDate($dayYmd . ' 00:00:00');

    // check whether the event starts on a previous date
    if ($startDate < $dateMidnight) {
        // decrease duration by the difference between today and the real start date
        $diff = $startDate->diff($dateMidnight);
        $duration -= $diff->days * 1440 + $diff->h * 60 + $diff->i;

        // use today at midnight
        $startDate = clone $dateMidnight;
    }

    if ($duration <= 0) {
        // ignore the event in case of invalid duration
        continue;
    }

    $endDate = clone $startDate;
    $endDate->modify('+' . $duration . ' minutes');

    // extract hours and minutes
    list($hour, $min) = explode(':', $startDate->format('G:i'));

    $taskObjects[] = [
        'id'       => $dayTask->getID(),
        'start'    => $startDate->format('Y-m-d\TH:i:s'),
        'end'      => $endDate->format('Y-m-d\TH:i:s'),
        'hour'     => (int) $hour,
        'min'      => (int) $min,
        'duration' => (int) $duration,
        'html'     => JLayoutHelper::render('taskmanager.calendar.day.task', ['task' => $dayTask]),
    ];
}

?>

<div class="vbo-tm-calendar-wrap">
    <div class="vbo-tm-calendar-head">
        <div class="vbo-tm-calendar-info">
            <a href="javascript:void(0)" class="vbo-tm-calendar-day-back" data-month="<?php echo date('Y-m-01', $dayDate[0]); ?>">
                <?php echo VikBookingIcons::e('chevron-left'); ?>
            </a>
            <span class="vbo-tm-calendar-day-day"><?php echo VikBooking::sayWeekDay($dayDate['wday']) . ', ' . $dayDate['mday']; ?></span>
            <span class="vbo-tm-calendar-day-month"><?php echo VikBooking::sayMonth($dayDate['mon']); ?></span>
            <span class="vbo-tm-calendar-day-year"><?php echo $dayDate['year']; ?></span>
        </div>

        <div class="vbo-tm-calendar-nav">
            <div class="vbo-tm-calendar-nav-btns">
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-nav-back" data-day="<?php echo $dayBack; ?>"><?php VikBookingIcons::e('chevron-left'); ?></span>
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-nav-today" data-day="<?php echo $todayYmd; ?>"><?php echo JText::translate('VBTODAY'); ?></span>
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-nav-next" data-day="<?php echo $dayNext; ?>"><?php VikBookingIcons::e('chevron-right'); ?></span>
            </div>
        </div>
    </div>

    <div class="vbo-tm-calendar-day-timeline">
        <div class="vbo-tm-calendar-day-timeline-week">

        <?php
        // display up to 3 week-days in the past
        for ($d = 3; $d > 0; $d--) {
            $timeline_date = getdate(strtotime(sprintf('-%d %s', $d, ($d == 1 ? 'day' : 'days')), $dayDate[0]));
            ?>
            <div class="vbo-tm-calendar-day-timeline-week-day">
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-day-timeline-week-nav" data-day="<?php echo date('Y-m-d', $timeline_date[0]); ?>">
                    <span class="vbo-tm-calendar-day-timeline-week-nav-mday"><?php echo $timeline_date['mday']; ?></span>
                    <span class="vbo-tm-calendar-day-timeline-week-nav-wday"><?php echo VikBooking::sayWeekDay($timeline_date['wday'], true); ?></span>
                </span>
            </div>
            <?php
        }
        ?>
            <div class="vbo-tm-calendar-day-timeline-week-day">
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-day-timeline-week-nav vbo-tm-calendar-day-timeline-week-today" data-day="<?php echo $todayYmd; ?>">
                    <span class="vbo-tm-calendar-day-timeline-week-nav-mday"><?php echo $dayDate['mday']; ?></span>
                    <span class="vbo-tm-calendar-day-timeline-week-nav-wday"><?php echo VikBooking::sayWeekDay($dayDate['wday'], true); ?></span>
                </span>
            </div>
        <?php
        // display up to 3 week-days ahead
        for ($d = 1; $d < 4; $d++) {
            $timeline_date = getdate(strtotime(sprintf('+%d %s', $d, ($d == 1 ? 'day' : 'days')), $dayDate[0]));
            ?>
            <div class="vbo-tm-calendar-day-timeline-week-day">
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-day-timeline-week-nav" data-day="<?php echo date('Y-m-d', $timeline_date[0]); ?>">
                    <span class="vbo-tm-calendar-day-timeline-week-nav-mday"><?php echo $timeline_date['mday']; ?></span>
                    <span class="vbo-tm-calendar-day-timeline-week-nav-wday"><?php echo VikBooking::sayWeekDay($timeline_date['wday'], true); ?></span>
                </span>
            </div>
            <?php
        }
        ?>
        </div>
        <div class="vbo-tm-calendar-day-timeline-rows">
        <?php
        for ($h = 0; $h < 24; $h++) {
            ?>
            <div class="vbo-tm-calendar-day-timeline-row" data-hour="<?php echo $h; ?>">
                <div class="vbo-tm-calendar-day-hour">
                    <strong><?php echo str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . '00'; ?></strong>
                </div>
                <div class="vbo-tm-calendar-day-tasks"></div>
            </div>
            <?php
        }
        ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(function($) {
        // setup the task (events) within the calendar daily layout
        window.taskManagerSetupCalendar(<?php echo json_encode($taskObjects); ?>);

        /**
         * Register listeners for day navigation buttons.
         */
        document
            .querySelectorAll('.vbo-tm-calendar-nav-btn')
            .forEach((nav) => {
                // get the navigation day
                const day = nav.getAttribute('data-day');

                nav.addEventListener('click', () => {
                    VBOCore.emitEvent('vbo-tm-apply-filters', {
                        filters: {
                            calendar_day: day,
                        },
                    });
                });
            });

        /**
         * Register listeners for month calendar type.
         */
        document
            .querySelectorAll('.vbo-tm-calendar-day-back')
            .forEach((mname) => {
                // get the navigation month
                const month = mname.getAttribute('data-month');

                mname.addEventListener('click', () => {
                    VBOCore.emitEvent('vbo-tm-apply-filters', {
                        filters: {
                            calendar_type: 'month',
                            calendar_month: month,
                            calendar_day: '',
                        },
                    });
                });
            });

        /**
         * Register listeners for editing an existing task.
         */
        document
            .querySelectorAll('.vbo-tm-calendar-day-task[data-task-id]')
            .forEach((taskElement) => {
                if (taskElement.clickListener) {
                    // listener added already
                    return;
                }

                // get the clicked task and area IDs
                const taskId = taskElement.getAttribute('data-task-id');
                const areaId = taskElement.getAttribute('data-area-id');

                if (!taskId || !areaId) {
                    // missing task details
                    return;
                }

                taskElement.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();

                    // define the modal delete button
                    let delete_btn = $('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn btn-danger')
                        .text(<?php echo json_encode(JText::translate('VBELIMINA')); ?>)
                        .on('click', function() {
                            if (!confirm(<?php echo json_encode(JText::translate('VBDELCONFIRM')); ?>)) {
                                return false;
                            }

                            // disable button to prevent double submissions
                            let submit_btn = $(this);
                            submit_btn.prop('disabled', true);

                            // start loading animation
                            VBOCore.emitEvent('vbo-tm-edittask-loading');

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.deleteTask'); ?>",
                                {
                                    data: {
                                        id: taskId,
                                    },
                                },
                                (resp) => {
                                    // trigger filters-changed event on success
                                    VBOCore.emitEvent('vbo-tm-filters-changed', {
                                        filters: vboTmFilters,
                                    });

                                    // dismiss the modal
                                    VBOCore.emitEvent('vbo-tm-edittask-dismiss');
                                },
                                (error) => {
                                    // display error message
                                    alert(error.responseText);

                                    // re-enable submit button
                                    submit_btn.prop('disabled', false);

                                    // stop loading
                                    VBOCore.emitEvent('vbo-tm-edittask-loading');
                                }
                            );
                        });

                    // define the modal save button
                    let save_btn = $('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn btn-success')
                        .text(<?php echo json_encode(JText::translate('VBSAVE')); ?>)
                        .on('click', function() {
                            // disable button to prevent double submissions
                            let submit_btn = $(this);
                            submit_btn.prop('disabled', true);

                            // start loading animation
                            VBOCore.emitEvent('vbo-tm-edittask-loading');

                            // get form data
                            const taskForm = new FormData(document.querySelector('#vbo-tm-task-manage-form'));

                            // build query parameters for the request
                            let qpRequest = new URLSearchParams(taskForm);

                            // make sure the request always includes the assignees query parameter, even if the list is empty
                            if (!qpRequest.has('data[assignees][]')) {
                                qpRequest.append('data[assignees][]', []);
                            }

                            // make sure the request always includes the tags query parameter, even if the list is empty
                            if (!qpRequest.has('data[tags][]')) {
                                qpRequest.append('data[tags][]', []);
                            }

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.updateTask'); ?>",
                                qpRequest.toString(),
                                (resp) => {
                                    // trigger filters-changed event on success
                                    VBOCore.emitEvent('vbo-tm-filters-changed', {
                                        filters: vboTmFilters,
                                    });

                                    // dismiss the modal
                                    VBOCore.emitEvent('vbo-tm-edittask-dismiss');
                                },
                                (error) => {
                                    // display error message
                                    alert(error.responseText);

                                    // re-enable submit button
                                    submit_btn.prop('disabled', false);

                                    // stop loading
                                    VBOCore.emitEvent('vbo-tm-edittask-loading');
                                }
                            );
                        });

                    // display modal
                    let modalBody = VBOCore.displayModal({
                        suffix:         'tm_edittask_modal',
                        title:          <?php echo json_encode(JText::translate('VBO_TASK')); ?> + ' #' + taskId,
                        extra_class:    'vbo-modal-rounded vbo-modal-taller vbo-modal-large',
                        body_prepend:   true,
                        lock_scroll:    true,
                        escape_dismiss: false,
                        footer_left:    delete_btn,
                        footer_right:   save_btn,
                        loading_event:  'vbo-tm-edittask-loading',
                        dismiss_event:  'vbo-tm-edittask-dismiss',
                    });

                    // start loading animation
                    VBOCore.emitEvent('vbo-tm-edittask-loading');

                    // make the request
                    VBOCore.doAjax(
                        "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
                        {
                            type: 'tasks.managetask',
                            data: {
                                task_id: taskId,
                                area_id: areaId,
                                form_id: 'vbo-tm-task-manage-form',
                            },
                        },
                        (resp) => {
                            // stop loading
                            VBOCore.emitEvent('vbo-tm-edittask-loading');

                            try {
                                // decode the response (if needed), and append the content to the modal body
                                let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
                                modalBody.append(obj_res['html']);
                            } catch (err) {
                                console.error('Error decoding the response', err, resp);
                            }
                        },
                        (error) => {
                            // display error message
                            alert(error.responseText);

                            // stop loading
                            VBOCore.emitEvent('vbo-tm-edittask-loading');
                        }
                    );
                });

                // turn flag on for listener set
                taskElement.clickListener = true;
            });

        let newTaskButtons = [], selectedTime = null;

        newTaskButtons.push({
            text: <?php echo json_encode(JText::translate('VBO_NEW_TASK')); ?>,
            class: 'btngroup',
            disabled: true,
        });

        vboTmAllAreas.forEach((area) => {
            // push area button
            newTaskButtons.push({
                areaId: area.id,
                class: 'vbo-context-menu-entry-secondary',
                icon: '<?php echo VikBookingIcons::i('plus-circle'); ?>',
                text: area.name,
                action: (root, event) => {
                    VBOCore.emitEvent('vbo-tm-newtask-trigger', {
                        areaId: area.id,
                        filters: Object.assign({}, vboTmFilters, {
                            calendar_time: selectedTime,
                        }),
                    });
                },
            });
        });

        /**
         * Register context menu on each hour.
         */
        document
            .querySelectorAll('.vbo-tm-calendar-day-tasks')
            .forEach((tasks) => {
                $(tasks).vboContextMenu({
                    buttons: newTaskButtons,
                    class: 'vbo-dropdown-cxmenu',
                    onShow: (root, popup, event) => {
                        // calculate the time close to the clicked point
                        let hour = parseInt($(root).closest('[data-hour]').attr('data-hour')) || 0;
                        let min = Math.round(Math.floor(event.originalEvent.offsetY / $(root).height() * 60) / 15) * 15;

                        if (min == 60) {
                            hour++;
                            min = 0;
                        }

                        // format time
                        selectedTime = (hour.toString().padStart(2, '0')) + ':' + (min.toString().padStart(2, '0'));
                    },
                });
            });

    });
</script>
