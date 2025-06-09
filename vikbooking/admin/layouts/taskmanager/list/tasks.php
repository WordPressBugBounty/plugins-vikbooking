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
 * @var array  $data  The data for rendering the tasks of given areas within a list.
 */
extract($displayData);

// get the task area IDs
$areaIds = [];

if (is_array($data['area_ids'] ?? null)) {
    $areaIds = $data['area_ids'];
}

if (!$areaIds) {
    // we need to make the query return no results with an empty area ID
    $areaIds = [0];
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

// get the list of tasks according to filters, by always forcing/injecting the area IDs
$tasks = VBOTaskModelTask::getInstance()->filterItems(array_merge($filters, ['id_areas' => $areaIds]), $start, $limit);

foreach ($tasks as $taskRecord) {
    // wrap task record into a registry
    $task = VBOTaskTaskregistry::getInstance((array) $taskRecord);

    // load task tags
    $tags = $task->getTags() ? $task->getTagRecords() : [];

    ?>
    <div class="vbo-tm-list-task-row" data-task-id="<?php echo $task->getID(); ?>" data-area-id="<?php echo $task->getAreaID(); ?>">

        <div class="vbo-tm-list-task-cell edit-trigger" data-type="id">
            <span><?php VikBookingIcons::e('hashtag'); ?> <?php echo $task->getID(); ?></span>
        </div>

        <div class="vbo-tm-list-task-cell" data-type="title">
            <a href="JavaScript: void(0);" class="edit-trigger"><?php echo $task->getTitle(); ?></a>
            <div class="secondary">
                <small><?php echo JHtml::fetch('date.relative', $task->get('modifiedon') ?: $task->get('createdon')); ?></small>
            </div>
        </div>

        <div class="vbo-tm-list-task-cell" data-type="assignees">
        <?php
        foreach ($task->getAssigneeDetails() as $operator) {
            ?>
            <span class="vbo-tm-list-area-task-assignee vbo-tm-task-assignee">
                <span class="vbo-tm-list-area-task-assignee-avatar vbo-tm-task-assignee-avatar" title="<?php echo JHtml::fetch('esc_attr', $operator['name']); ?>">
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

        <div class="vbo-tm-list-task-cell" data-type="due">
            <?php if ($dueDate = $task->getDueDate()): ?>
                <div><?php echo JHtml::fetch('date', $dueDate, 'd F Y'); ?></div>
                <div class="secondary"><small><?php echo JHtml::fetch('date', $dueDate, 'H:i'); ?></small></div>
            <?php endif; ?>
        </div>

        <div class="vbo-tm-list-task-cell" data-type="status">
        <?php
        if ($taskManager->statusTypeExists($task->getStatus())) {
            $status = $taskManager->getStatusTypeInstance($task->getStatus());
            echo JHtml::fetch('vbohtml.taskmanager.status', $status);
        }

        if ($task->get('hasUnreadMessages', false)): ?>
            <a href="javascript:void(0)" class="unread-message-dot mini edit-trigger">
                <?php VikBookingIcons::e('comment'); ?>
            </a>
        <?php endif; ?>
        </div>

        <div class="vbo-tm-list-task-cell" data-type="id_order">

            <div class="order-summary-flex">
                
                <?php
                if ($task->getBookingId() && $bookingElement = $task->buildBookingElement()) {
                    if (!empty($bookingElement['img'])) {
                        $img = '<img src="' . $bookingElement['img'] . '" class="vbo-booking-badge-avatar" decoding="async" loading="lazy" />';
                    } else {
                        $img = '<span class="vbo-booking-badge-avatar"><i class="' . VikBookingIcons::i($bookingElement['icon_class'] ?? 'hotel') . '"></i></span>';
                    }

                    $img = '<div class="see-booking-details" data-bid="' . $task->getBookingId() . '">'
                        . $img
                        . '<i class="' . VikBookingIcons::i('eye') . ' effect"></i>'
                        . '</div>';

                    $bookingText = $bookingElement['text'] . ' #' . $bookingElement['id'];
                } else {
                    $img = '<span class="vbo-booking-badge-avatar edit-trigger"><i class="' . VikBookingIcons::i('plus') . '"></i></span>';
                    $bookingText = '<em>' . JText::translate('VBO_UNASSIGNED') . '</em>';
                }
                ?>

                <?php echo $img; ?>

                <div class="order-room-booking-details">
                    <?php if ($task->getListingId()): ?>
                        <div class="order-room"><?php echo $task->getListingName($task->getListingId()); ?></div>
                    <?php endif; ?>

                    <div class="order-booking"><?php echo $bookingText; ?></div>
                </div>

            </div>

        </div>

        <div class="vbo-tm-list-task-cell" data-type="id_area">
            <span><?php echo $task->getAreaName($task->getAreaID()); ?></span>
        </div>

        <div class="vbo-tm-list-task-cell" data-type="tags">
        <?php
        if ($tags) {
            ?>
            <div class="vbo-tm-list-task-tags">
            <?php
            foreach ($tags as $tag) {
                $tag = (array) $tag;
                ?>
                <span class="vbo-tm-task-tag vbo-tm-color <?php echo $tag['color']; ?>"><?php echo $tag['name']; ?></span>
                <?php
            }
            ?>
            </div>
            <?php
        }
        ?>
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

    jQuery(function($) {

        /**
         * Register listeners for editing an existing task.
         */
        document
            .querySelectorAll('.vbo-tm-list-task-row')
            .forEach((taskElement) => {
                if (taskElement.clickListener) {
                    // listener added already
                    return;
                }

                // get the clicked task and area IDs
                const taskId = taskElement.getAttribute('data-task-id');
                const areaId = taskElement.getAttribute('data-area-id');

                // build the click-able elements
                let clickableElements = taskElement.querySelectorAll('.edit-trigger');

                clickableElements.forEach((element) => {
                    element.addEventListener('click', () => {
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
                });

                // turn flag on for listener set
                taskElement.clickListener = true;
            });

        /**
         * Register listeners for display the reservation details.
         */
        document
            .querySelectorAll('.see-booking-details[data-bid]')
            .forEach((bookingEl) => {
                if (bookingEl.clickListener) {
                    // listener added already
                    return;
                }

                // get the clicked booking ID
                const bookingId = bookingEl.getAttribute('data-bid');

                bookingEl.addEventListener('click', () => {
                    VBOCore.handleDisplayWidgetNotification({
                        widget_id: 'booking_details',
                    }, {
                        booking_id: bookingId,
                        modal_options: {
                            body_prepend: false,
                            enlargeable:  false,
                            minimizeable: false,
                        },
                    });
                });

                // turn flag on for listener set
                bookingEl.clickListener = true;
            });
    });

</script>