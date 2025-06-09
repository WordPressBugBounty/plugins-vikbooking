(function($, w) {
    'use strict';

    const insideTaskManagementForm = () => {
        return $('.vbo-managetask-modal-form').length ? true : false;
    }

    /***************
     * SEE BOOKING *
     ***************/

    /**
     * See task booking button action handler.
     */
    $(w).on('chat.task.booking.action', (event) => {
        const [root, parentEvent, button, chat] = event.args;

        // display booking details on a new widget
        VBOCore.handleDisplayWidgetNotification({
            widget_id: 'booking_details',
        }, {
            booking_id: button.booking,
            modal_options: {
                suffix: 'vbo-task-booking-details',
                body_prepend: false,
                enlargeable: false,
                minimizeable: false,
            },
        });
    });

    /************
     * SEE TASK *
     ************/

    /**
     * See task button visibility handler.
     */
    $(w).on('chat.task.see.visible', async (event) => {
        event.shouldDisplay = !insideTaskManagementForm();
    });

    /**
     * See task button action handler.
     */
    $(w).on('chat.task.see.action', (event) => {
        const [root, parentEvent, button, chat] = event.args;

        // define the modal save button
        const saveButton = $('<button></button>')
            .attr('type', 'button')
            .addClass('btn btn-success')
            .text(Joomla.JText._('VBSAVE'))
            .on('click', function() {
                $(this).prop('disabled', true);

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

                qpRequest.append('task', 'taskmanager.updateTask');

                // make the request
                VBOCore.doAjax(
                    chat.data.environment.url,
                    qpRequest.toString(),
                    (resp) => {
                        // dismiss the modal
                        VBOCore.emitEvent('vbo-tm-edittask-dismiss');

                        // refresh the task manager filters, if any
                        VBOCore.emitEvent('vbo-tm-filters-changed', {
                            filters: typeof vboTmFilters !== 'undefined' ? vboTmFilters : {},
                        });
                    },
                    (error) => {
                        // display error message
                        alert(error.responseText);

                        // re-enable submit button
                        $(this).prop('disabled', false);

                        // stop loading
                        VBOCore.emitEvent('vbo-tm-edittask-loading');
                    }
                );
            });

        // display modal
        let modalBody = VBOCore.displayModal({
            suffix:         'tm_edittask_modal',
            title:          Joomla.JText._('VBO_TASK') + ' #' + chat.data.environment.context.id,
            extra_class:    'vbo-modal-rounded vbo-modal-taller vbo-modal-large',
            body_prepend:   true,
            lock_scroll:    true,
            escape_dismiss: false,
            footer_right:   saveButton,
            loading_event:  'vbo-tm-edittask-loading',
            dismiss_event:  'vbo-tm-edittask-dismiss',
        });

        // start loading animation
        VBOCore.emitEvent('vbo-tm-edittask-loading');

        // make the request
        VBOCore.doAjax(
            chat.data.environment.url,
            {
                task: 'taskmanager.renderLayout',
                type: 'tasks.managetask',
                data: {
                    task_id: chat.data.environment.context.id,
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

    /************
     * NEW TASK *
     ************/

    const chooseArea = (chat) => {
        return new Promise((resolve, reject) => {
            let areaSelected = false;

            const pickArea = (event) => {
                areaSelected = true;

                // auto-close modal
                VBOCore.emitEvent('vbo-tm-pickarea-dismiss');

                setTimeout(() => {
                    resolve(event?.detail?.area);
                }, 500);
            }

            // display modal
            let modalBody = VBOCore.displayModal({
                suffix:         'tm_areapicker_modal',
                title:          Joomla.JText._('VBO_PROJECTS_AREAS'),
                extra_class:    'vbo-modal-rounded vbo-modal-nofooter',
                body_prepend:   true,
                lock_scroll:    true,
                escape_dismiss: false,
                loading_event:  'vbo-tm-pickarea-loading',
                dismiss_event:  'vbo-tm-pickarea-dismiss',
                onDismiss:      () => {
                    document.removeEventListener('vbo-tm-area-id-selected', pickArea);

                    if (!areaSelected) {
                        reject('Task area selection aborted.');
                    }
                }
            });

            // start loading animation
            VBOCore.emitEvent('vbo-tm-pickarea-loading');

            // make the request
            VBOCore.doAjax(
                chat.data.environment.url,
                {
                    task: 'taskmanager.renderLayout',
                    type: 'tasks.selectarea',
                },
                (resp) => {
                    // stop loading
                    VBOCore.emitEvent('vbo-tm-pickarea-loading');

                    try {
                        // decode the response (if needed), and append the content to the modal body
                        let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;
                        modalBody.append(obj_res['html']);
                    } catch (err) {
                        console.error('Error decoding the response', err, resp);
                    }

                    // observe area selection
                    document.addEventListener('vbo-tm-area-id-selected', pickArea, {
                        once: true,
                    });
                },
                (error) => {
                    // display error message
                    alert(error.responseText);

                    // stop loading
                    VBOCore.emitEvent('vbo-tm-pickarea-loading');
                }
            );
        });
    }

    /**
     * New task button visibility handler.
     */
    $(w).on('chat.task.new.visible', async (event) => {
        event.shouldDisplay = !insideTaskManagementForm();
    });

    /**
     * New task button action handler.
     */
    $(w).on('chat.task.new.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        let area = null;

        try {
            area = await chooseArea(chat);
        } catch (err) {
            if (err) {
                console.warn(err);
            }

            // selection aborted
            return;
        }

        // define the modal save button
        let saveButton = jQuery('<button></button>')
            .attr('type', 'button')
            .addClass('btn btn-success')
            .text(Joomla.JText._('VBSAVE'))
            .on('click', function() {
               $(this).prop('disabled', true);

                // start loading animation
                VBOCore.emitEvent('vbo-tm-newtask-loading');

                // get form data
                const taskForm = new FormData(document.querySelector('#vbo-tm-task-manage-form'));

                // build query parameters for the request
                let qpRequest = new URLSearchParams(taskForm);

                qpRequest.append('task', 'taskmanager.createTask');

                // make the request
                VBOCore.doAjax(
                    chat.data.environment.url,
                    qpRequest.toString(),
                    (resp) => {
                        // dismiss the modal
                        VBOCore.emitEvent('vbo-tm-newtask-dismiss');

                        // refresh the task manager filters, if any
                        VBOCore.emitEvent('vbo-tm-filters-changed', {
                            filters: typeof vboTmFilters !== 'undefined' ? vboTmFilters : {},
                        });
                    },
                    (error) => {
                        // display error message
                        alert(error.responseText);

                        // re-enable submit button
                        $(this).prop('disabled', false);

                        // stop loading
                        VBOCore.emitEvent('vbo-tm-newtask-loading');
                    }
                );
            });

        // display modal
        let modalBody = VBOCore.displayModal({
            suffix:         'tm_newtask_modal',
            title:          area.name + ' - ' + Joomla.JText._('VBO_NEW_TASK'),
            extra_class:    'vbo-modal-rounded vbo-modal-taller vbo-modal-large',
            body_prepend:   true,
            lock_scroll:    true,
            escape_dismiss: false,
            footer_right:   saveButton,
            loading_event:  'vbo-tm-newtask-loading',
            dismiss_event:  'vbo-tm-newtask-dismiss',
        });

        // start loading animation
        VBOCore.emitEvent('vbo-tm-newtask-loading');

        // make the request
        VBOCore.doAjax(
            chat.data.environment.url,
            {
                task: 'taskmanager.renderLayout',
                type: 'tasks.managetask',
                data: {
                    area_id: area.id,
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

    /*****************
     * CHANGE STATUS *
     *****************/

     const changeStatus = (status, chat) => {
         return new Promise((resolve, reject) => {
            VBOCore.doAjax(
                chat.data.environment.url,
                {
                    task: 'taskmanager.updateTask',
                    data: {
                        id: chat.data.environment.context.id,
                        status_enum: status,
                    }
                },
                (resp) => {
                    resolve();
                },
                (error) => {
                    reject(error.responseText || error.statusText || 'Error');
                }
            );
         });
     }

    /**
     * Status task button icon handler.
     */
    $(w).on('chat.task.status.icon', (event) => {
        const [root, menu, button, chat] = event.args;

        // build button icon element
        const btnIconEl = $('<span></span>')
            .addClass('vbo-colortag-circle')
            .addClass('vbo-tm-colortag-circle')
            .addClass('vbo-tm-statustype-circle');

        if (button?.color) {
            btnIconEl.addClass(button.color);
        }

        event.displayIcon = btnIconEl;
    });

    /**
     * Status task button disable handler.
     */
    $(w).on('chat.task.status.disabled', (event) => {
        const [root, menu, button, chat] = event.args;

        // iterate all the contextual menu buttons
        $(root).vboContextMenu('buttons').forEach((btn) => {
            // flag the button as disabled in case it is selected
            if (button.id == btn.id && btn.selected) {
                event.shouldDisable = true;
            }
        });
    });

    /**
     * Status task button action handler.
     */
    $(w).on('chat.task.status.action', async (event) => {
        const [root, parentEvent, button, chat] = event.args;

        try {
            // make request to change the status
            await changeStatus(button.id, chat);

            // obtain all the contextual menu buttons
            const buttons = $(root).vboContextMenu('buttons');

            buttons.forEach((btn) => {
                // flag the clicked button as selected
                btn.selected = button.id == btn.id;
            });

            // update buttons
            $(root).vboContextMenu('buttons', buttons);
        } catch (error) {
            alert(error);
        }
    });

    /**
     * Status task button visibility handler.
     */
    $(w).on('chat.task.status.visible', async (event) => {
        event.shouldDisplay = !insideTaskManagementForm();
    });

    /*****************
     * BUTTON GROUPS *
     *****************/

    /**
     * Task button group visibility handler.
     */
    $(w).on('chat.task.btngroup.visible', async (event) => {
        event.shouldDisplay = !insideTaskManagementForm();
    });

})(jQuery, window);