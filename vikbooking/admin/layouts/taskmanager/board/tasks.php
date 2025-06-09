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
 * @var array  $data  The data for rendering the tasks of a given area within the board.
 */
extract($displayData);

// get the task area object
if (($data['taskArea'] ?? null) && $data['taskArea'] instanceof VBOTaskArea) {
    // rendering during page loading
    $taskArea = $data['taskArea'];
} elseif ($data['area_id'] ?? null) {
    // probably rendering through AJAX
    $taskArea = VBOTaskArea::getRecordInstance((int) $data['area_id']);
} else {
    // raise an error
    throw new InvalidArgumentException('Could not load task area object.', 400);
}

// get fetching start and limit values
$start = abs(intval($data['start'] ?? 0));
$limit = abs(intval($data['limit'] ?? 10));

// get the filters for rendering the area tasks
$filters = (array) ($data['filters'] ?? []);

// save current filters in the user state
JFactory::getApplication()->setUserState('vbo.tm.filters', $filters);

// access the task manager object
$taskManager = VBOFactory::getTaskManager();

// get the first area tasks according to filters, by always forcing/injecting the area ID
$tasks = VBOTaskModelTask::getInstance()->filterItems(array_merge($filters, ['id_area' => $taskArea->getID()]), $start, $limit);

foreach ($tasks as $taskRecord) {
    // wrap task record into a registry
    $task = VBOTaskTaskregistry::getInstance((array) $taskRecord);

    // load task tags
    $tags = $task->getTags() ? $task->getTagRecords() : [];
    ?>
    <div class="vbo-tm-board-area-task-wrap" data-task-id="<?php echo $task->getID(); ?>">
        <div class="vbo-tm-board-area-task-head">
        <?php
        if ($tags) {
            ?>
            <div class="vbo-tm-board-area-task-tags">
            <?php
            foreach ($tags as $tag) {
                $tag = (array) $tag;
                ?>
                <span class="vbo-tm-board-area-task-tag vbo-tm-task-tag vbo-tm-color <?php echo $tag['color']; ?>"><?php echo $tag['name']; ?></span>
                <?php
            }
            ?>
            </div>
            <?php
        }
        ?>
            <div class="vbo-tm-board-area-task-title">
                <?php echo $task->getTitle(); ?>

                <?php if ($task->get('hasUnreadMessages', false)): ?>
                    <span class="unread-message-dot">
                        <?php VikBookingIcons::e('comment'); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="vbo-tm-board-area-task-notes"><?php echo JHtml::fetch('vikbooking.shorten', $task->getNotes(), 120); ?></div>
            <div class="vbo-tm-board-area-task-due">
                <?php VikBookingIcons::e('clock'); ?>
                <span><?php echo $task->getDueDate(true); ?></span>
            </div>
        </div>
        <div class="vbo-tm-board-area-task-body">
            <div class="vbo-tm-board-area-task-status">
            <?php
            if ($taskManager->statusTypeExists($task->getStatus())) {
                $status = $taskManager->getStatusTypeInstance($task->getStatus());
                echo JHtml::fetch('vbohtml.taskmanager.status', $status);
            }
            ?>
            </div>
            <div class="vbo-tm-board-area-task-assignees">
            <?php
            foreach ($task->getAssigneeDetails() as $operator) {
                ?>
                <span class="vbo-tm-board-area-task-assignee vbo-tm-task-assignee">
                    <span class="vbo-tm-board-area-task-assignee-avatar vbo-tm-task-assignee-avatar" title="<?php echo JHtml::fetch('esc_attr', $operator['name']); ?>">
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
            </div>
        </div>
    </div>
    <?php
}

if (!$tasks) {
    ?>
    <p class="info" data-no-results="1"><?php echo !$start ? JText::translate('VBO_NO_RECORDS_FOUND') : JText::translate('VBO_NO_MORE_RECORDS'); ?></p>
    <?php
}
?>

<script type="text/javascript">

    jQuery(function() {

        /**
         * Register listener for editing an existing task.
         */
        document
            .querySelector('.vbo-tm-board-area-container[data-area-id="<?php echo $taskArea->getID(); ?>"]')
            .querySelectorAll('.vbo-tm-board-area-task-title')
            .forEach((taskElement) => {
                if (taskElement.clickListener) {
                    // listener added already
                    return;
                }

                taskElement.addEventListener('click', () => {
                    // get the clicked task ID
                    const taskId = taskElement.closest('.vbo-tm-board-area-task-wrap').getAttribute('data-task-id');

                    // define the modal delete button
                    let delete_btn = jQuery('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn btn-danger')
                        .text(<?php echo json_encode(JText::translate('VBELIMINA')); ?>)
                        .on('click', function() {
                            if (!confirm(<?php echo json_encode(JText::translate('VBDELCONFIRM')); ?>)) {
                                return false;
                            }

                            // disable button to prevent double submissions
                            let submit_btn = jQuery(this);
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
                    let save_btn = jQuery('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn btn-success')
                        .text(<?php echo json_encode(JText::translate('VBSAVE')); ?>)
                        .on('click', function() {
                            // disable button to prevent double submissions
                            let submit_btn = jQuery(this);
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
                                area_id: <?php echo $taskArea->getID(); ?>,
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

        /**
         * Dispatch the delayed event to setup the infinite scroll loading.
         */
        setTimeout(() => {
            VBOCore.emitEvent('vbo-tm-board-tasks-loaded', {
                areaId: <?php echo $taskArea->getID(); ?>,
                start:  <?php echo $start; ?>,
                limit:  <?php echo $limit; ?>,
                count:  <?php echo count($tasks); ?>,
            });
        }, 0);
    });

</script>
