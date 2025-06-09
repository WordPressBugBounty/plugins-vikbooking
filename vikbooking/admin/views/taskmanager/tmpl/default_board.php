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

?>

<div class="vbo-tm-board-areas-list">
    <?php
    foreach ($this->visibleAreas as $areaRecord) {
        // wrap the area record into a task area object
        $taskArea = VBOTaskArea::getInstance((array) $areaRecord);
        ?>
        <div class="vbo-tm-board-area-container" data-area-id="<?php echo $taskArea->getID(); ?>">
        <?php
        // build layout data
        $layout_data = [
            'data' => [
                'taskArea' => $taskArea,
                'filters'  => $this->filters,
            ],
        ];

        // render task list area
        echo JLayoutHelper::render('taskmanager.board.area', $layout_data);
        ?>
        </div>
        <?php
    }
    ?>
</div>

<script type="text/javascript">
    /**
     * Register function for rendering a task area.
     */
    const vboTmBoardRenderArea = (areaId) => {
        let areaContainer = jQuery('<div></div>')
            .addClass('vbo-tm-board-area-container')
            .attr('data-area-id', areaId);

        areaContainer.append(
            jQuery('<div></div>')
                .addClass('vbo-tm-board-area-loading')
                .html('<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>')
        );

        areaContainer.appendTo('.vbo-tm-board-areas-list');

        // make the request to display the requested task area
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
            {
                type: 'board.area',
                data: {
                    area_id: areaId,
                    filters: vboTmFilters || {},
                },
            },
            (resp) => {
                try {
                    // decode the response (if needed)
                    let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;

                    // set HTML response (use jQuery rather than plain JS to avoid issues with injected scripts execution)
                    areaContainer.html(obj_res['html']);

                    // silently update task area status
                    vboTmToggleAreaDisplay(areaId, 1);

                    // load area tasks list
                    vboTmBoardRenderAreaTasks(areaId);
                } catch (err) {
                    console.error('Error decoding the response', err, resp);
                }
            },
            (error) => {
                // display error message
                alert(error.responseText);

                // dispatch the event for updating the active areas
                VBOCore.emitEvent('vbo-tm-area-update-status');
            }
        );
    };

    /**
     * Register function for rendering the given area tasks with filters and start offset.
     */
    const vboTmBoardRenderAreaTasks = (areaId, filters, start) => {
        let tasksList;
        let loadingList;
        let nextPage = false;

        if (start) {
            // existing tasks list for loading a new page
            tasksList = jQuery('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]')
                .find('.vbo-tm-board-area-tasks-list');

            if (tasksList.length) {
                // set the current loading list
                loadingList = document
                    .querySelector('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]')
                    .querySelector('.vbo-tm-board-area-tasks-list');

                // populate loading skeletons
                vboTmBoardSetSkeletons(areaId, 10);
            } else {
                // start a new list
                tasksList = null;
            }
        }

        if (!tasksList) {
            // load the first page of tasks
            tasksList = jQuery('<div></div>')
                .addClass('vbo-tm-board-area-tasks-list');

            tasksList.append(
                jQuery('<div></div>')
                    .addClass('vbo-tm-board-area-tasks-loading')
                    .html('<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>')
            );

            tasksList.appendTo('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]');
        } else {
            // turn flag on for loading a next page
            nextPage = true;
        }

        // make the request to display the requested task area
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
            {
                type: 'board.tasks',
                data: {
                    area_id: areaId,
                    filters: filters || vboTmFilters || {},
                    start: start || 0,
                    limit: 10,
                },
            },
            (resp) => {
                try {
                    // decode the response (if needed)
                    let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;

                    // set HTML response (use jQuery rather than plain JS to avoid issues with injected scripts execution)
                    if (!nextPage) {
                        // set the loading result for the first page of tasks
                        tasksList.html(obj_res['html']);
                    } else {
                        if (loadingList) {
                            // turn flag off for loading a new page
                            loadingList.pageLoading = false;

                            // delete loading skeletons
                            vboTmBoardUnsetSkeletons(areaId);
                        }

                        if ((obj_res['html'] + '').indexOf('data-no-results="1"') < 0 || !tasksList.find('[data-no-results="1"]').length) {
                            // append the loading result of the next tasks by preventing multiple "no-results" elements due to infinite scroll loading
                            tasksList.append(obj_res['html']);
                        }
                    }

                    // dispatch event for TM contents loaded
                    VBOCore.emitEvent('vbo-tm-contents-loaded', {
                        element: tasksList,
                    });
                } catch (err) {
                    console.error('Error decoding the response', err, resp);
                }
            },
            (error) => {
                // display error message
                alert(error.responseText);

                if (loadingList) {
                    // turn flag off for loading a new page
                    loadingList.pageLoading = false;
                }
            }
        );
    };

    /**
     * Register function to set some task loading skeletons.
     */
    const vboTmBoardSetSkeletons = (areaId, quantity) => {
        let container = document.querySelector('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]');
        let tasksList = container ? container.querySelector('.vbo-tm-board-area-tasks-list') : null;

        if (!tasksList) {
            return;
        }

        // ensure the quantity is a valid number greater than zero
        quantity = !isNaN(quantity) && quantity > 0 ? quantity : 1;

        // build skeleton HTML string
        let skeletonHtml = '';
        skeletonHtml += '<div class="vbo-tm-board-area-task-wrap vbo-tm-board-area-task-wrap-skeleton">';
        skeletonHtml += '    <div class="vbo-tm-board-area-task-head">';
        skeletonHtml += '        <div class="vbo-skeleton-loading vbo-tm-board-area-task-title"></div>';
        skeletonHtml += '        <div class="vbo-tm-board-area-task-notes">';
        skeletonHtml += '            <div class="vbo-skeleton-loading vbo-tm-board-area-task-notes-line"></div>';
        skeletonHtml += '            <div class="vbo-skeleton-loading vbo-tm-board-area-task-notes-line"></div>';
        skeletonHtml += '        </div>';
        skeletonHtml += '    </div>';
        skeletonHtml += '    <div class="vbo-tm-board-area-task-body">';
        skeletonHtml += '        <div class="vbo-tm-board-area-task-status">';
        skeletonHtml += '            <span class="vbo-skeleton-loading vbo-tm-board-area-task-status-badge"></span>';
        skeletonHtml += '        </div>';
        skeletonHtml += '        <div class="vbo-tm-board-area-task-assignees">';
        skeletonHtml += '            <span class="vbo-skeleton-loading vbo-tm-board-area-task-assignee"></span>';
        skeletonHtml += '            <span class="vbo-skeleton-loading vbo-tm-board-area-task-assignee"></span>';
        skeletonHtml += '        </div>';
        skeletonHtml += '    </div>';
        skeletonHtml += '</div>';

        for (let i = 1; i <= quantity; i++) {
            tasksList.insertAdjacentHTML('beforeend', skeletonHtml);
        }
    };

    /**
     * Register function to unset the task loading skeletons.
     */
    const vboTmBoardUnsetSkeletons = (areaId) => {
        let container = document.querySelector('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]');
        let tasksList = container ? container.querySelector('.vbo-tm-board-area-tasks-list') : null;

        if (!tasksList) {
            return;
        }

        tasksList
            .querySelectorAll('.vbo-tm-board-area-task-wrap.vbo-tm-board-area-task-wrap-skeleton')
            .forEach((skeleton) => {
                skeleton.remove();
            });
    };

    /**
     * Register function for hiding a task area.
     */
    const vboTmBoardHideArea = (areaId) => {
        // remove element blocks from DOM
        document
            .querySelector('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]')
            .remove();

        // silently update task area status
        vboTmToggleAreaDisplay(areaId, 0);
    };

    /**
     * Register function for hiding the given area tasks.
     */
    const vboTmBoardHideAreaTasks = (areaId) => {
        // remove tasks list element from DOM
        let list = document
            .querySelector('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]')
            .querySelector('.vbo-tm-board-area-tasks-list');

        if (list) {
            list.remove();
        }
    };

    /**
     * Register function for setting up the infinite scroll loading for an area.
     */
    const vboTmBoardSetupInfiniteScroll = (areaId, start, count) => {
        let tasksList = document
            .querySelector('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]')
            .querySelector('.vbo-tm-board-area-tasks-list');

        if (!tasksList) {
            return;
        }

        if (count < 10) {
            // no extra loading needed
            tasksList
                .removeEventListener('scroll', vboTmBoardHandleInfiniteScroll);
            return;
        }

        // get wrapper dimensions
        let listViewHeight = tasksList.offsetHeight;
        let listGlobHeight = tasksList.scrollHeight;

        if (listViewHeight >= listGlobHeight) {
            // no scrolling detected
            return;
        }

        // inject/update custom object properties
        tasksList.areaId = areaId;
        tasksList.offsetStart = start;

        if (!start && !tasksList.scrollListener) {
            // inject custom object property to identify the scroll event listener
            tasksList.scrollListener = true;

            // register infinite scroll event handler
            tasksList
                .addEventListener('scroll', vboTmBoardHandleInfiniteScroll);
        }
    };

    /**
     * Register function for infinite scroll loading handler.
     */
    const vboTmBoardHandleInfiniteScroll = (e) => {
        // access the injected area ID property
        let areaId = e.currentTarget.areaId;

        if (!areaId) {
            return;
        }

        // access the involved tasks list
        let tasksList = document
            .querySelector('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]')
            .querySelector('.vbo-tm-board-area-tasks-list');

        if (!tasksList) {
            return;
        }

        // make sure the loading of a next page isn't running
        if (tasksList.pageLoading) {
            // abort
            return;
        }

        // register throttling callback
        VBOCore.throttleTimer(() => {
            // get wrapper dimensions
            let listViewHeight = tasksList.offsetHeight;
            let listGlobHeight = tasksList.scrollHeight;
            let listScrollTop  = tasksList.scrollTop;

            if (!listScrollTop || listViewHeight >= listGlobHeight) {
                // no scrolling detected at all
                return;
            }

            // calculate missing distance to the end of the list
            let listEndDistance = listGlobHeight - (listViewHeight + listScrollTop);

            if (listEndDistance < 140) {
                // inject custom property to identify a next page is loading
                tasksList.pageLoading = true;

                // load the next page of notifications
                vboTmBoardRenderAreaTasks(areaId, (vboTmFilters || {}), (tasksList.offsetStart || 0) + 10);
            }
        }, 100);
    };

    jQuery(function() {

        /**
         * Load all area tasks upon page loading.
         */
        document.querySelectorAll('.vbo-tm-board-area-container').forEach((area) => {
            let areaId = area.getAttribute('data-area-id');
            // render area tasks
            vboTmBoardRenderAreaTasks(areaId);
        });

        /**
         * Register to the event for rendering a task area.
         */
        document.addEventListener('vbo-tm-area-render-task', (e) => {
            if (!e || !e?.detail?.areaId) {
                return;
            }

            let areaId = e.detail.areaId;

            vboTmBoardRenderArea(areaId);
        });

        /**
         * Register to the event for hiding a task area.
         */
        document.addEventListener('vbo-tm-area-hide-task', (e) => {
            if (!e || !e?.detail?.areaId) {
                return;
            }

            let areaId = e.detail.areaId;

            vboTmBoardHideArea(areaId);
        });

        /**
         * Register listener for the filters changed event.
         */
        document.addEventListener('vbo-tm-filters-changed', (e) => {
            // obtain the global filters
            let filters = e?.detail?.filters;

            // iterate over all the visible areas
            document.querySelectorAll('.vbo-tm-board-area-container').forEach((area) => {
                // get area ID
                let areaId = area.getAttribute('data-area-id');

                // hide the area tasks
                vboTmBoardHideAreaTasks(areaId);

                // re-render area tasks
                vboTmBoardRenderAreaTasks(areaId, filters);
            });
        });

        /**
         * Register listener for the tasks loaded event to configure the infinite scroll loading.
         */
        document.addEventListener('vbo-tm-board-tasks-loaded', (e) => {
            let areaId = e?.detail?.areaId || 0;
            let start  = e?.detail?.start || 0;
            let count  = e?.detail?.count || 0;

            if (document.querySelector('.vbo-tm-board-area-container[data-area-id="' + areaId + '"]')) {
                // setup infinite scroll loading for the current area
                vboTmBoardSetupInfiniteScroll(areaId, start, count);
            }
        });

    });
</script>
