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
 * @var string  $caller     The identifier of who's calling this layout file.
 * @var array   $events     The custom event names to trigger for certain actions.
 * @var array   $active 	List of active area IDs by default.
 */
extract($displayData);

// load context menu assets
VikBooking::getVboApplication()->loadContextMenuAssets();

// load all the existing task areas
$taskAreas = VBOTaskModelArea::getInstance()->getItems();

// define the default events
if (!isset($events) || !is_array($events)) {
	$events = [];
}
$events['render_task']  = $events['render_task'] ?? 'vbo-tm-area-render-task';
$events['hide_task']    = $events['hide_task'] ?? 'vbo-tm-area-hide-task';
$events['update_areas'] = $events['update_areas'] ?? 'vbo-tm-area-update-status';
$events['create_areas'] = $events['create_areas'] ?? 'vbo-tm-area-create-trigger';

// define the default active areas
if (!isset($active) || !is_array($active)) {
	$active = [];
}

?>

<button type="button" class="btn vbo-context-menu-btn vbo-context-menu-tm-areas">
	<span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
	<span class="vbo-context-menu-lbl"><?php echo JText::translate('VBO_PROJECTS_AREAS'); ?></span>
</button>

<script type="text/javascript">
	jQuery(function() {

		// all task areas
		const taskAreas = <?php echo json_encode($taskAreas); ?>;

		// active task areas
		const activeAreas = <?php echo json_encode($active); ?>;

		const createNewTaskManagerArea = () => {
			// define the modal cancel button
			let cancel_btn = jQuery('<button></button>')
				.attr('type', 'button')
				.addClass('btn')
				.text(<?php echo json_encode(JText::translate('VBANNULLA')); ?>)
				.on('click', () => {
					VBOCore.emitEvent('vbo-tm-newarea-dismiss');
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
					VBOCore.emitEvent('vbo-tm-newarea-loading');

					// get form data
					const areaForm = new FormData(document.querySelector('#vbo-tm-area-manage-form'));

					// build query parameters for the request
					let qpRequest = new URLSearchParams(areaForm).toString();

					// make the request
					VBOCore.doAjax(
						"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.createArea'); ?>",
						qpRequest,
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
							VBOCore.emitEvent('vbo-tm-newarea-loading');
						}
					);
				});

			// display modal
			let modalBody = VBOCore.displayModal({
				suffix:        'tm_newarea_modal',
				title:         <?php echo json_encode(JText::translate('VBO_PROJECT_AREA')); ?> + ' - ' + <?php echo json_encode(JText::translate('VBO_ADD_NEW')); ?>,
				extra_class:   'vbo-modal-rounded vbo-modal-tall vbo-modal-taller',
				body_prepend:  true,
				lock_scroll:   true,
				footer_left:   cancel_btn,
				footer_right:  save_btn,
				loading_event: 'vbo-tm-newarea-loading',
				dismiss_event: 'vbo-tm-newarea-dismiss',
			});

			// start loading animation
			VBOCore.emitEvent('vbo-tm-newarea-loading');

			// make the request
			VBOCore.doAjax(
				"<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
				{
					type: 'areas.managearea',
					data: {
						form_id: 'vbo-tm-area-manage-form',
					},
				},
				(resp) => {
					// stop loading
					VBOCore.emitEvent('vbo-tm-newarea-loading');

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
					VBOCore.emitEvent('vbo-tm-newarea-loading');
				}
			);
		}

		// build task area buttons
		let btns = [];

		taskAreas.forEach((area, index) => {
			// build button text element
            let btnTxtEl = jQuery('<span></span>')
                .addClass('vbo-ctxmenu-entry-icn')
                .html(jQuery('<span></span>').text(area.name));
            let btnTxtIcn = jQuery('<i></i>')
                .addClass('vbo-tm-area-edit')
                .addClass('<?php echo VikBookingIcons::i('edit'); ?>');
            btnTxtEl.append(btnTxtIcn);

			// push area button
			btns.push({
				activeState: activeAreas.includes(parseInt(area.id)),
				areaId: area.id,
				class: 'vbo-context-menu-entry-secondary',
				text: btnTxtEl,
				separator: (++index == taskAreas.length),
				icon: function() {
					return this.activeState === true ? '<?php echo VikBookingIcons::i('check-square'); ?>' : '<?php echo VikBookingIcons::i('far fa-square'); ?>';
				},
				action: function(root, event) {
					if (!event?.target || !jQuery(event.target).hasClass('vbo-tm-area-edit')) {
						// toggle active state
						this.activeState = !this.activeState;

						if (this.activeState) {
							// push the current area ID to the active list
							if (typeof vboTmActiveAreas !== 'undefined' && Array.isArray(vboTmActiveAreas)) {
								vboTmActiveAreas.push(parseInt(this.areaId));
							}

							// render tasks for the selected area
							VBOCore.emitEvent('<?php echo $events['render_task']; ?>', {
								areaId: this.areaId,
							});
						} else {
							// delete the current area ID from the active list
							if (typeof vboTmActiveAreas !== 'undefined' && Array.isArray(vboTmActiveAreas)) {
								vboTmActiveAreas.forEach((activeAreaId, activeAreaIndex) => {
									if (activeAreaId == this.areaId) {
										// remove the area ID from the active list
										vboTmActiveAreas.splice(activeAreaIndex, 1);
										return;
									}
								});
							}

							// hide tasks for the selected area
							VBOCore.emitEvent('<?php echo $events['hide_task']; ?>', {
								areaId: this.areaId,
							});
						}

						// do not proceed
                        return;
					}

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
                                id: area.id,
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
			});
		});

		if (taskAreas.length) {
			// push show all button
			btns.push({
				class: 'vbo-context-menu-entry-warning',
				text: <?php echo json_encode(JText::translate('VBPARAMSEASONCALENDARPRICESANY')); ?>,
				icon: '<?php echo VikBookingIcons::i('eye'); ?>',
				action: function(root, config) {
					let all_btns = jQuery(root).vboContextMenu('buttons');
					all_btns.forEach((btn) => {
						if (typeof btn.activeState === 'undefined') {
							return;
						}
						if (!btn.activeState) {
							// call button click action
							btn.action(root, config);
						}
					});
				},
				disabled: function(root, config) {
					// disabled when all buttons are active
					return config.buttons.every(btn => btn.activeState !== false);
				},
			});

			// push hide all button
			btns.push({
				class: 'vbo-context-menu-entry-warning',
				text: <?php echo json_encode(JText::translate('VBO_HIDE_ALL')); ?>,
				icon: '<?php echo VikBookingIcons::i('eye-slash'); ?>',
				separator: true,
				action: function(root, config) {
					let all_btns = jQuery(root).vboContextMenu('buttons');
					all_btns.forEach((btn) => {
						if (typeof btn.activeState === 'undefined') {
							return;
						}
						if (btn.activeState) {
							// call button click action
							btn.action(root, config);
						}
					});
				},
				disabled: function(root, config) {
					// disabled when all buttons are inactive
					return config.buttons.every(btn => btn.activeState !== true);
				},
			});
		}

		// push create new area button
		btns.push({
			class: 'vbo-context-menu-entry-success',
			text: <?php echo json_encode(JText::translate('VBO_ADD_NEW')); ?>,
			icon: '<?php echo VikBookingIcons::i('plus-circle'); ?>',
			action: function(root, config) {
				createNewTaskManagerArea();
			}
		});

		// start context menu on the proper button element
		jQuery('.vbo-context-menu-tm-areas').vboContextMenu({
			placement: 'bottom-left',
			buttons: btns,
		});

		// register event for updating the task area status
		document.addEventListener('<?php echo $events['update_areas']; ?>', (e) => {
			let all_btns = jQuery('.vbo-context-menu-tm-areas').vboContextMenu('buttons');
			all_btns.forEach((btn) => {
				if (typeof btn.areaId === 'undefined') {
					return;
				}
				if (!document.querySelector('.vbo-tm-area-wrap[data-area-id="' + btn.areaId + '"]')) {
					// turn off this area active state
					btn.activeState = false;
				}
			});
		});

		// register event for creating a new task area
		document.addEventListener('<?php echo $events['create_areas']; ?>', (e) => {
			createNewTaskManagerArea();
		});

	});
</script>