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
 * @var array  $data  The data for rendering the task area plus the related tasks within the board.
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

?>

<div class="vbo-tm-board-area-wrap" data-area-id="<?php echo $taskArea->getID(); ?>">
    <div class="vbo-tm-board-area-head">
        <div class="vbo-tm-board-area-head-info">
            <span class="vbo-tm-board-area-icn"><?php VikBookingIcons::e($taskArea->getIcon()); ?></span>
            <span class="vbo-tm-board-area-name"><?php echo $taskArea->getName(); ?></span>
            <div class="vbo-tm-board-area-comments">
                <?php echo $taskArea->get('comments', ''); ?>
            </div>
        </div>
    </div>
    <div class="vbo-tm-board-area-newtask">
        <span class="vbo-tm-board-area-cmd" data-area-id="<?php echo $taskArea->getID(); ?>"><?php VikBookingIcons::e('ellipsis-h'); ?></span>
        <button type="button" class="btn vbo-newtask-btn"><?php VikBookingIcons::e('plus'); ?> <?php echo JText::translate('VBO_NEW_TASK'); ?></button>
    </div>
</div>

<script type="text/javascript">

    jQuery(function() {

        /**
         * Build area commands context menu buttons.
         */
        let btns = [
            {
                icon: '<?php echo VikBookingIcons::i('edit'); ?>',
                text: <?php echo json_encode(JText::translate('VBMAINPAYMENTSEDIT')); ?>,
                action: (root, event) => {
                    // define the modal cancel button
                    let cancel_btn = jQuery('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn')
                        .text(<?php echo json_encode(JText::translate('VBANNULLA')); ?>)
                        .on('click', () => {
                            VBOCore.emitEvent('vbo-tm-editarea-dismiss');
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
                            VBOCore.emitEvent('vbo-tm-editarea-loading');

                            // get form data
                            const areaForm = new FormData(document.querySelector('#vbo-tm-area-manage-form'));

                            // build query parameters for the request
                            let qpRequest = new URLSearchParams(areaForm);

                            // make sure the request always includes the tags query parameter, even if the list is empty
                            if (!qpRequest.has('area[tags][]')) {
                                qpRequest.append('area[tags][]', []);
                            }

                            // make sure the request always includes the statuses query parameter, even if the list is empty
                            if (!qpRequest.has('area[status_enums][]')) {
                                qpRequest.append('area[status_enums][]', []);
                            }

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.updateArea'); ?>",
                                qpRequest.toString(),
                                (resp) => {
                                    // reload current page on success
                                    window.location.reload();
                                },
                                (error) => {
                                    // display error message
                                    alert(error.responseText);

                                    // re-enable submit button
                                    submit_btn.prop('disabled', false);

                                    // stop loading
                                    VBOCore.emitEvent('vbo-tm-editarea-loading');
                                }
                            );
                        });

                    // display modal
                    let modalBody = VBOCore.displayModal({
                        suffix:        'tm_editarea_modal',
                        title:         <?php echo json_encode(JText::translate('VBO_PROJECT_AREA')); ?> + ' - ' + <?php echo json_encode(JText::translate('VBMAINPAYMENTSEDIT')); ?>,
                        extra_class:   'vbo-modal-rounded vbo-modal-tall vbo-modal-taller',
                        body_prepend:  true,
                        lock_scroll:   true,
                        footer_left:   cancel_btn,
                        footer_right:  save_btn,
                        loading_event: 'vbo-tm-editarea-loading',
                        dismiss_event: 'vbo-tm-editarea-dismiss',
                    });

                    // start loading animation
                    VBOCore.emitEvent('vbo-tm-editarea-loading');

                    // make the request
                    VBOCore.doAjax(
                        "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
                        {
                            type: 'areas.managearea',
                            data: {
                                id: <?php echo $taskArea->getID(); ?>,
                                form_id: 'vbo-tm-area-manage-form',
                            },
                        },
                        (resp) => {
                            // stop loading
                            VBOCore.emitEvent('vbo-tm-editarea-loading');

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
                            VBOCore.emitEvent('vbo-tm-editarea-loading');
                        }
                    );
                },
            },
            {
                icon: '<?php echo VikBookingIcons::i('trash'); ?>',
                text: <?php echo json_encode(JText::translate('VBELIMINA')); ?>,
                class: 'vbo-context-menu-entry-danger',
                action: (root, event) => {
                    if (!confirm(<?php echo json_encode(JText::translate('VBDELCONFIRM')); ?>)) {
                        return;
                    }

                    try {
                        // add deleting animation class
                        e.target.closest('.vbo-tm-board-area-wrap').classList.add('deleting');
                    } catch (err) {
                        // do nothing
                    }

                    // make the request
                    VBOCore.doAjax(
                        "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.deleteArea'); ?>",
                        {
                            area_id: <?php echo $taskArea->getID(); ?>,
                        },
                        (resp) => {
                            // reload current page on success
                            window.location.reload();
                        },
                        (error) => {
                            // display error message
                            alert(error.responseText);

                            // reload current page also on error
                            window.location.reload();
                        }
                    );
                },
            },
        ];

        /**
         * Register context-menu for area commands.
         */
        jQuery('.vbo-tm-board-area-cmd[data-area-id="<?php echo $taskArea->getID(); ?>"]').vboContextMenu({
            placement: 'bottom-right',
            buttons: btns,
        });

        /**
         * Register listener for creating a new task.
         */
        document
            .querySelector('.vbo-tm-board-area-wrap[data-area-id="<?php echo $taskArea->getID(); ?>"]')
            .querySelector('.vbo-newtask-btn')
            .addEventListener('click', () => {
                // define the modal cancel button
                let cancel_btn = jQuery('<button></button>')
                    .attr('type', 'button')
                    .addClass('btn')
                    .text(<?php echo json_encode(JText::translate('VBANNULLA')); ?>)
                    .on('click', () => {
                        VBOCore.emitEvent('vbo-tm-newtask-dismiss');
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
                        VBOCore.emitEvent('vbo-tm-newtask-loading');

                        // get form data
                        const taskForm = new FormData(document.querySelector('#vbo-tm-task-manage-form'));

                        // build query parameters for the request
                        let qpRequest = new URLSearchParams(taskForm).toString();

                        // make the request
                        VBOCore.doAjax(
                            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.createTask'); ?>",
                            qpRequest,
                            (resp) => {
                                // trigger filters-changed event on success
                                VBOCore.emitEvent('vbo-tm-filters-changed', {
                                    filters: vboTmFilters,
                                });

                                // dismiss the modal
                                VBOCore.emitEvent('vbo-tm-newtask-dismiss');
                            },
                            (error) => {
                                // display error message
                                alert(error.responseText);

                                // re-enable submit button
                                submit_btn.prop('disabled', false);

                                // stop loading
                                VBOCore.emitEvent('vbo-tm-newtask-loading');
                            }
                        );
                    });

                // display modal
                let modalBody = VBOCore.displayModal({
                    suffix:         'tm_newtask_modal',
                    title:          <?php echo json_encode($taskArea->getName() . ' - ' . JText::translate('VBO_NEW_TASK')); ?>,
                    extra_class:    'vbo-modal-rounded vbo-modal-taller vbo-modal-large',
                    body_prepend:   true,
                    lock_scroll:    true,
                    escape_dismiss: false,
                    footer_left:    cancel_btn,
                    footer_right:   save_btn,
                    loading_event:  'vbo-tm-newtask-loading',
                    dismiss_event:  'vbo-tm-newtask-dismiss',
                });

                // start loading animation
                VBOCore.emitEvent('vbo-tm-newtask-loading');

                // make the request
                VBOCore.doAjax(
                    "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
                    {
                        type: 'tasks.managetask',
                        data: {
                            area_id: <?php echo $taskArea->getID(); ?>,
                            form_id: 'vbo-tm-task-manage-form',
                        },
                    },
                    (resp) => {
                        // stop loading
                        VBOCore.emitEvent('vbo-tm-newtask-loading');

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
                        VBOCore.emitEvent('vbo-tm-newtask-loading');
                    }
                );
            });

    });
</script>
