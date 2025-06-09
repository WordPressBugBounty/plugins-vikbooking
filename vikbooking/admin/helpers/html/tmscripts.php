<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking HTML tasl manager scripts helper.
 *
 * @since 1.8
 */
abstract class VBOHtmlTmscripts
{
    /**
     * Creates a javascript variable holding all the available status codes.
     * 
     * @param   VBOTaskManager|null  $taskManager
     * 
     * @return  void
     */
    public static function statuses(?VBOTaskManager $taskManager = null)
    {
        static $loaded = 0;

        if ($loaded) {
            // do not load again
            return;
        }

        $loaded = 1;

        if (!$taskManager) {
            // access the task manager
            $taskManager = VBOFactory::getTaskManager();
        }

        // load all the available status types
        $statusTypes = [];

        foreach ($taskManager->getStatusGroupElements() as $groupId => $group) {
            // push group button
            $statusTypes[] = [
                'id' => null,
                'text' => $group['text'],
            ];

            // iterate over the statuses of this group
            foreach ($group['elements'] as $statusType) {
                // push status button
                $statusTypes[] = [
                    'id' => $statusType['id'],
                    'text' => $statusType['text'],
                    'color' => $statusType['color'],
                ];
            }
        }

        /** @var object[] TASK_MANAGER_STATUSES */
        JFactory::getDocument()->addScriptDeclaration('window.TASK_MANAGER_STATUSES = ' . json_encode($statusTypes) . ';');
    }

    /**
     * Script used to handle the status change event.
     * 
     * @param   string  $selector  The selector to use for auto-init.
     * 
     * @return  void
     */
    public static function changestatus(string $selector = '.change-status-trigger')
    {
        static $loaded = 0;

        if ($loaded) {
            // do not load again
            return;
        }

        $loaded = 1;

        // make the status codes accessible
        static::statuses();

        $ajaxUrl = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.updateTask');

        JFactory::getDocument()->addScriptDeclaration(
<<<JAVASCRIPT
(function($, w) {
    'use strict';

    $(function() {
        // automatically initialize the context menu for the status changes every time the contents are loaded
        document.addEventListener('vbo-tm-contents-loaded', (event) => {
            // take only the statuses inside the element actually updated
            w.setupStatusHandler($(event.detail.element).find('{$selector}'), event.detail?.statuses);
        });
    });

    /**
     * Set up the context menu used to change status for a given task.
     */
    w.setupStatusHandler = (selector, areaStatuses) => {
        if (!w.TASK_MANAGER_STATUSES) {
            return;
        }

        // obtain only the statuses that are actually supported
        const iterableStatuses = w.TASK_MANAGER_STATUSES.filter((s) => {
            return typeof areaStatuses === 'undefined' || s.id == null || areaStatuses.length === 0 || areaStatuses.indexOf(s.id) != -1;
        });

        // build buttons
        let statusButtons = [];

        iterableStatuses.forEach((elem, index) => {
            // build button icon element
            let btnIconEl = $('<span></span>')
                .addClass('vbo-colortag-circle')
                .addClass('vbo-tm-colortag-circle')
                .addClass('vbo-tm-statustype-circle');

            if (elem?.color) {
                btnIconEl.addClass(elem.color);
            }

            // push status button
            statusButtons.push({
                statusId: elem.id,
                color: elem.color,
                class: elem.id ? 'vbo-context-menu-entry-secondary' : 'btngroup',
                searchable: elem.id ? true : false,
                text: elem.text,
                icon: elem.id ? btnIconEl : null,
                disabled: () => {
                    return !elem.id || (vboTmFilters && vboTmFilters?.statusId == elem.id);
                },
                visible: (root) => {
                    return $(root).attr('data-status') != elem.id;
                },
                action: function(root, event) {
                    const taskId = parseInt($(root).closest('[data-task-id]').attr('data-task-id'));

                    // make the request
                    VBOCore.doAjax(
                        "{$ajaxUrl}",
                        {
                            data: {
                                id: taskId,
                                status_enum: this.statusId,
                            }
                        },
                        (resp) => {
                            $(root).removeClass($(root).attr('data-color'))
                                .addClass(this.color)
                                .attr('data-status', this.statusId)
                                .attr('data-color', this.color)
                                .text(this.text);

                            if (typeof VBOCore !== 'undefined') {
                                VBOCore.emitEvent('vbo-task-status-changed', {
                                    task: {
                                        id: taskId,
                                        status: this.statusId,
                                    }
                                });
                            }
                        },
                        (error) => {
                            // display error message
                            alert(error.responseText);
                        }
                    );
                },
            });
        });

        // start context menu on the proper button element
        $(selector).not('.initialized').vboContextMenu({
            placement: 'bottom-left',
            buttons: statusButtons,
            search: true,
        }).addClass('initialized');
    }
})(jQuery, window);
JAVASCRIPT
        );
    }
}
