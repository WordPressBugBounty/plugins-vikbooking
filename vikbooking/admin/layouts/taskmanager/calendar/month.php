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
 * @var array  $data  The data for rendering the tasks of a given month within a calendar.
 */
extract($displayData);

// access the task manager object
$taskManager = VBOFactory::getTaskManager();

// get the task area IDs
$areaIds = [];

if (is_array($data['area_ids'] ?? null)) {
    $areaIds = $data['area_ids'];
}

// get the filters for rendering the calendar tasks
$filters = (array) ($data['filters'] ?? []);

// determine the month to load
$fromYmd = $filters['calendar_month'] ?? date('Y-m-01');
$toYmd = date('Y-m-t', strtotime($fromYmd));
if (($filters['dates'] ?? null) && !($filters['calendar_month'] ?? null)) {
    // when no calendar month filter set, use the current dates filter
    list($fromDt, $toDt) = VBOTaskModelTask::getInstance()->getFilterDatesInterval((string) $filters['dates'], $local = true, $sql = false);
    if ($fromDt && $toDt) {
        $fromYmd = date('Y-m-01', strtotime($fromDt));
        $toYmd = date('Y-m-t', strtotime($toDt));
    }
}

// build the pool of month tasks
$monthTasks = [];

if ($areaIds) {
    // save current filters in the user state
    JFactory::getApplication()->setUserState('vbo.tm.filters', $filters);

    // load tasks according to filters, by always forcing/injecting the area IDs and the dates
    foreach (VBOTaskModelTask::getInstance()->filterItems(array_merge($filters, ['id_areas' => $areaIds, 'dates' => $fromYmd . ':' . $toYmd]), 0, 0) as $taskRecord) {
        // wrap task record into a registry
        $task = VBOTaskTaskregistry::getInstance((array) $taskRecord);

        // get task due date in Y-m-d format
        $due_date_key = $task->getDueDate($local = true, $format = 'Y-m-d');

        if ($due_date_key) {
            // start a container for this date
            $monthTasks[$due_date_key] = $monthTasks[$due_date_key] ?? [];

            // push task registry for this day
            $monthTasks[$due_date_key][] = $task;
        }
    }
}

// obtain the current month information
$monthDate = getdate(strtotime($fromYmd));
$monthIter = $monthDate;

// get navigation dates
$todayYmd   = date('Y-m-d');
$monthBack  = date('Y-m-01', strtotime('-1 month', $monthDate[0]));
$monthNext  = date('Y-m-01', strtotime('+1 month', $monthDate[0]));
$monthToday = date('Y-m-01');

// build week-day indexes
$firstwday    = (int) VikBooking::getFirstWeekDay();
$days_indexes = [];
$wday_labels  = [
    JText::translate('VBSUN'),
    JText::translate('VBMON'),
    JText::translate('VBTUE'),
    JText::translate('VBWED'),
    JText::translate('VBTHU'),
    JText::translate('VBFRI'),
    JText::translate('VBSAT'),
];

for ($d = 0; $d < 7; $d++) {
    $days_indexes[$d] = (6-($firstwday-$d)+1)%7;
}

?>

<div class="vbo-tm-calendar-wrap">
    <div class="vbo-tm-calendar-head">
        <div class="vbo-tm-calendar-info">
            <span class="vbo-tm-calendar-month-name"><?php echo VikBooking::sayMonth($monthDate['mon']); ?></span>
            <span class="vbo-tm-calendar-month-year"><?php echo $monthDate['year']; ?></span>
        </div>

        <div class="vbo-tm-calendar-nav">
            <div class="vbo-tm-calendar-nav-btns">
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-nav-back" data-month="<?php echo $monthBack; ?>"><?php VikBookingIcons::e('chevron-left'); ?></span>
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-nav-today" data-month="<?php echo $monthToday; ?>"><?php echo JText::translate('VBTODAY'); ?></span>
                <span class="vbo-tm-calendar-nav-btn vbo-tm-calendar-nav-next" data-month="<?php echo $monthNext; ?>"><?php VikBookingIcons::e('chevron-right'); ?></span>
            </div>
        </div>
    </div>

    <div class="vbo-tm-calendar-month-container">

        <div class="vbo-tm-calendar-month-row vbo-tm-calendar-month-weekdays">
        <?php
        // display days of week
        for ($d = 0; $d < 7; $d++) {
            $d_ind = ($d + $firstwday) < 7 ? ($d + $firstwday) : ($d + $firstwday - 7);
            ?>
            <div class="vbo-tm-calendar-month-day vbo-tm-calendar-month-weekday">
                <span><?php echo $wday_labels[$d_ind]; ?></span>
            </div>
            <?php
        }
        ?>
        </div>

        <div class="vbo-tm-calendar-month-row">
        <?php
        // start month-day counter
        $d_count = 0;

        // display the initial empty week-days, if any
        for ($i = 0, $n = $days_indexes[$monthDate['wday']]; $i < $n; $i++, $d_count++) {
            ?>
            <div class="vbo-tm-calendar-month-day vbo-tm-calendar-month-day-empty"></div>
            <?php
        }

        // loop through all dates of the current month
        while ($monthIter['mon'] == $monthDate['mon']) {
            if ($d_count > 6) {
                // week is over, reset counter and start a new row
                $d_count = 0;
                echo '</div>' . "\n" . '<div class="vbo-tm-calendar-month-row">' . "\n";
            }

            // build date key
            $date_key = date('Y-m-d', $monthIter[0]);

            ?>
            <div class="vbo-tm-calendar-month-day<?php echo $date_key == $todayYmd ? ' vbo-tm-calendar-month-today' : ''; ?>" data-date="<?php echo $date_key; ?>">
                <span class="vbo-tm-calendar-mday"><?php echo $monthIter['mday']; ?></span>
            <?php
            if ($monthTasks[$date_key] ?? []) {
                ?>
                <div class="vbo-tm-calendar-month-day-tasks">
                <?php
                foreach ($monthTasks[$date_key] as $taskIndex => $task) {
                    // get task status
                    $statusColor = '';
                    $statusName = '';
                    if ($taskManager->statusTypeExists($task->getStatus())) {
                        $status = $taskManager->getStatusTypeInstance($task->getStatus());
                        $statusColor = $status->getColor();
                        $statusName = $status->getName();
                    }

                    // get task assignee details
                    $assignees = $task->getAssigneeDetails();
                    ?>
                    <div class="vbo-tm-calendar-month-day-task vbo-tm-color <?php echo $statusColor ?: 'gray'; ?> <?php echo $statusName ? 'vbo-tooltip vbo-tooltip-top' : ''; ?>" data-task-id="<?php echo $task->getID(); ?>" data-area-id="<?php echo $task->getAreaID(); ?>" data-tooltiptext="<?php echo JHtml::fetch('esc_attr', $statusName); ?>">
                        <div class="vbo-tm-calendar-month-day-task-wrap">
                            <div class="vbo-tm-calendar-task-title">
                                <?php echo $task->getTitle(); ?>

                                <?php if ($task->get('hasUnreadMessages', false)): ?>
                                    <span class="unread-message-dot">
                                        <?php VikBookingIcons::e('comment'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php
                        if ($assignees) {
                            ?>
                            <span class="vbo-tm-calendar-task-assignees">
                            <?php
                            foreach ($assignees as $operator) {
                                ?>
                                <span class="vbo-tm-calendar-task-assignee vbo-tm-task-assignee">
                                    <span class="vbo-tm-calendar-task-assignee-avatar vbo-tm-task-assignee-avatar" title="<?php echo JHtml::fetch('esc_attr', $operator['name']); ?>">
                                    <?php
                                    if (!empty($operator['img_uri'])) {
                                        ?>
                                        <img src="<?php echo $operator['img_uri']; ?>" alt="<?php echo JHtml::fetch('esc_attr', $operator['initials']); ?>" decoding="async" loading="lazy" />
                                        <?php
                                    } else {
                                        ?>
                                        <span><?php echo $operator['initials']; ?></span>
                                        <?php
                                    }
                                    ?>
                                    </span>
                                </span>
                                <?php
                            }
                            ?>
                            </span>
                            <?php
                        }
                        ?>
                        </div>
                    </div>
                    <?php
                    if ($taskIndex > 1 && $exceeding = (count($monthTasks[$date_key]) - ++$taskIndex)) {
                        // we display at most 3 tasks per day
                        ?>
                    <div class="vbo-tm-calendar-month-day-task vbo-tm-calendar-month-day-more" data-date="<?php echo $date_key; ?>">
                        <span><?php echo sprintf('+%d %s', $exceeding, strtolower($exceeding > 1 ? JText::translate('VBO_TASKS') : JText::translate('VBO_TASK'))); ?></span>
                    </div>
                        <?php

                        // break the execution of the tasks for this day
                        break;
                    }
                }
                ?>
                </div>
                <?php
            }
            ?>
            </div>
            <?php
            // increase month-day counter
            $d_count++;

            // go to next day
            $monthIter = getdate(mktime(0, 0, 0, $monthIter['mon'], $monthIter['mday'] + 1, $monthIter['year']));
        }

        // display the ending empty week-days, if any
        for ($i = $d_count; $i <= 6; $i++) {
            ?>
            <div class="vbo-tm-calendar-month-day vbo-tm-calendar-month-day-empty"></div>
            <?php
        }
        ?>
        </div>

    </div>
</div>

<script type="text/javascript">
    jQuery(function($) {

        let newTaskButtons = [];

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
                            calendar_type: 'day',
                            calendar_day: $(root).attr('data-date'),
                        }),
                    });
                },
            });
        });

        newTaskButtons[newTaskButtons.length - 1].separator = true;

        newTaskButtons.push({
            text: <?php echo json_encode(JText::translate('VBO_SEE_ALL')); ?>,
            class: 'vbo-context-menu-entry-secondary',
            icon: '<?php echo VikBookingIcons::i('eye'); ?>',
            visible: (root, config) => {
                return $(root).find('.vbo-tm-calendar-month-day-task').length ? true : false;
            },
            action: (root, event) => {
                VBOCore.emitEvent('vbo-tm-apply-filters', {
                    filters: {
                        calendar_type: 'day',
                        calendar_day: $(root).attr('data-date'),
                    },
                });
            }
        });

        /**
         * Register listeners for month navigation buttons.
         */
        document
            .querySelectorAll('.vbo-tm-calendar-nav-btn')
            .forEach((nav) => {
                // get the navigation month
                const month = nav.getAttribute('data-month');

                nav.addEventListener('click', () => {
                    VBOCore.emitEvent('vbo-tm-apply-filters', {
                        filters: {
                            calendar_month: month,
                        },
                    });
                });
            });

        /**
         * Register listeners for day calendar type.
         */
        document
            .querySelectorAll('.vbo-tm-calendar-month-day[data-date]')
            .forEach((day) => {
                // get the navigation month
                const ymd = day.getAttribute('data-date');

                day
                    .querySelector('.vbo-tm-calendar-mday')
                    .addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();

                        VBOCore.emitEvent('vbo-tm-apply-filters', {
                            filters: {
                                calendar_type: 'day',
                                calendar_day: ymd,
                            },
                        });
                    });

                $(day).vboContextMenu({
                    buttons: newTaskButtons,
                    class: 'vbo-dropdown-cxmenu',
                });
            });

        /**
         * Register listeners for editing an existing task.
         */
        document
            .querySelectorAll('.vbo-tm-calendar-month-day-task')
            .forEach((taskElement) => {
                if (taskElement.clickListener) {
                    // listener added already
                    return;
                }

                // get the clicked task and area IDs
                const taskId = taskElement.getAttribute('data-task-id');
                const areaId = taskElement.getAttribute('data-area-id');
                const date = taskElement.getAttribute('data-date');

                if ((!taskId || !areaId) && !date) {
                    // missing task details
                    return;
                }

                if (!taskId && !areaId && date) {
                    // see more day tasks
                    taskElement.addEventListener('click', (event) => {
                        event.preventDefault();
                        event.stopPropagation();

                        VBOCore.emitEvent('vbo-tm-apply-filters', {
                            filters: {
                                calendar_type: 'day',
                                calendar_day: date,
                            },
                        });
                    });

                    // abort
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

    });
</script>
