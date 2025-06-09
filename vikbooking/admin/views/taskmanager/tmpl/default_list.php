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

?>

<div class="vbo-tm-list-tasks-wrap">
    <div class="vbo-tm-list-tasks-container">
        <div class="vbo-tm-list-tasks-columns">
            <div class="vbo-tm-list-tasks-column" data-type="id">
                <span><?php echo JText::translate('VBO_TASK'); ?></span>
            </div>
            <div class="vbo-tm-list-tasks-column" data-type="title">
                <span><?php echo JText::translate('VBO_TITLE'); ?></span>
            </div>
            <div class="vbo-tm-list-tasks-column" data-type="assignees">
                <span><?php echo JText::translate('VBO_ASSIGNEES'); ?></span>
            </div>
            <div class="vbo-tm-list-tasks-column" data-type="due">
                <span><?php echo JText::translate('VBO_REMINDER_DUE_DATE'); ?></span>
            </div>
            <div class="vbo-tm-list-tasks-column" data-type="status">
                <span><?php echo JText::translate('VBSTATUS'); ?></span>
            </div>
            <div class="vbo-tm-list-tasks-column" data-type="id_order">
                <span><?php echo JText::translate('VBRENTALORD'); ?></span>
            </div>
            <div class="vbo-tm-list-tasks-column" data-type="id_area">
                <span><?php echo JText::translate('VBO_PROJECT_AREA'); ?></span>
            </div>
            <div class="vbo-tm-list-tasks-column" data-type="tags">
                <span><?php echo JText::translate('VBO_TAGS'); ?></span>
            </div>
        </div>
        <div class="vbo-tm-list-tasks-body"></div>
    </div>
    <div class="vbo-tm-list-tasks-footer">
        <div class="vbo-tm-list-tasks-loadmore" style="display: none;">
            <button type="button" class="btn btn-secondary vbo-tm-list-tasks-loadmore-btn"><?php VikBookingIcons::e('ellipsis-h'); ?> <?php echo JText::translate('VBO_LOAD_MORE'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
    /**
     * Register the global active area IDs.
     */
    const vboTmListAreaIds = <?php echo json_encode($activeAreaIds); ?>;

    /**
     * Register function to load the area tasks.
     */
    const vboTmListLoadTasks = (start, filters) => {
        let tasksBody = document.querySelector('.vbo-tm-list-tasks-body');
        let hasTasks = tasksBody.querySelectorAll('.vbo-tm-list-task-row').length;

        // inject custom object property
        tasksBody.offsetStart = start || 0;

        if (!start) {
            // empty the tasks body element
            tasksBody.innerHTML = '';

            // reset flag
            hasTasks = 0;

            // hide button to load more tasks
            jQuery('.vbo-tm-list-tasks-loadmore').hide();
        }

        // populate loading skeletons
        vboTmListSetSkeletons(10);

        // make the request for loading the tasks
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.renderLayout'); ?>",
            {
                type: 'list.tasks',
                data: {
                    area_ids: vboTmListAreaIds,
                    filters: filters || vboTmFilters || {},
                    start: start || 0,
                    limit: 20,
                },
            },
            (resp) => {
                try {
                    // decode the response (if needed)
                    let obj_res = typeof resp === 'string' ? JSON.parse(resp) : resp;

                    // set HTML response (use jQuery rather than plain JS to avoid issues with injected scripts execution)
                    if (!hasTasks) {
                        // set the loading result for the first page of tasks
                        jQuery(tasksBody).html(obj_res['html']);
                    } else {
                        // delete loading skeletons
                        vboTmListUnsetSkeletons();

                        // append the loading result of the next tasks
                        jQuery(tasksBody).append(obj_res['html']);
                    }

                    if ((obj_res['html'] + '').indexOf('data-no-results="1"') < 0 && (tasksBody.querySelectorAll('.vbo-tm-list-task-row').length - hasTasks) >= 20) {
                        // show button to load more tasks
                        jQuery('.vbo-tm-list-tasks-loadmore').show();
                    } else {
                        // hide button to load more tasks
                        jQuery('.vbo-tm-list-tasks-loadmore').hide();
                    }

                    // dispatch event for TM contents loaded
                    VBOCore.emitEvent('vbo-tm-contents-loaded', {
                        element: tasksBody,
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

    /**
     * Register function to set some task loading skeletons.
     */
    const vboTmListSetSkeletons = (quantity) => {
        let tasksBody = document.querySelector('.vbo-tm-list-tasks-body');

        if (!tasksBody) {
            return;
        }

        // ensure the quantity is a valid number greater than zero
        quantity = !isNaN(quantity) && quantity > 0 ? quantity : 1;

        for (let i = 1; i <= quantity; i++) {
            // build skeleton HTML string
            let skeletonHtml = '';
            skeletonHtml += '<div class="vbo-tm-list-task-row vbo-tm-list-task-row-skeleton">\n';
            skeletonHtml += '   <div class="vbo-tm-list-task-cell vbo-skeleton-loading" data-type="id"></div>\n';
            skeletonHtml += '   <div class="vbo-tm-list-task-cell vbo-skeleton-loading" data-type="title" style="width:' + (Math.floor(Math.random() * 45) + 50) + '%;"></div>\n';
            skeletonHtml += '   <div class="vbo-tm-list-task-cell" data-type="assignees">\n';
            skeletonHtml += '       <span class="vbo-skeleton-loading vbo-tm-board-area-task-assignee"></span>\n'.repeat(Math.floor(Math.random() * 3) + 1);
            skeletonHtml += '   </div>\n';
            skeletonHtml += '   <div class="vbo-tm-list-task-cell vbo-skeleton-loading" data-type="due"></div>\n';
            skeletonHtml += '   <div class="vbo-tm-list-task-cell vbo-skeleton-loading" data-type="status"></div>\n';
            skeletonHtml += '   <div class="vbo-tm-list-task-cell" data-type="id_order">\n';
            skeletonHtml += '       <span class="vbo-skeleton-loading vbo-tm-board-area-task-assignee"></span>\n';
            skeletonHtml += '       <div class="order-room-booking-wrapper">\n';
            skeletonHtml += '           <div class="vbo-skeleton-loading room-info"></div>\n';
            skeletonHtml += '           <div class="vbo-skeleton-loading booking-info"></div>\n';
            skeletonHtml += '       </div>\n';
            skeletonHtml += '   </div>\n';
            skeletonHtml += '   <div class="vbo-tm-list-task-cell vbo-skeleton-loading" data-type="id_area"></div>\n';
            skeletonHtml += '   <div class="vbo-tm-list-task-cell" data-type="tags">\n';
            skeletonHtml += '       <span class="vbo-skeleton-loading"></span>\n'.repeat(Math.floor(Math.random() * 3) + 1);
            skeletonHtml += '   </div>\n';
            skeletonHtml += '</div>';

            tasksBody.insertAdjacentHTML('beforeend', skeletonHtml);
        }
    };

    /**
     * Register function to unset the task loading skeletons.
     */
    const vboTmListUnsetSkeletons = (areaId) => {
        let tasksBody = document.querySelector('.vbo-tm-list-tasks-body');

        if (!tasksBody) {
            return;
        }

        tasksBody
            .querySelectorAll('.vbo-tm-list-task-row.vbo-tm-list-task-row-skeleton')
            .forEach((skeleton) => {
                skeleton.remove();
            });
    };

    jQuery(function() {

        /**
         * Load all area tasks upon page loading.
         */
        vboTmListLoadTasks(0);

        /**
         * Register click event on the button to load more tasks.
         */
        document
            .querySelector('button.vbo-tm-list-tasks-loadmore-btn')
            .addEventListener('click', function() {
                let tasksBody = document.querySelector('.vbo-tm-list-tasks-body');

                // get current offset
                let nextStart = (tasksBody.offsetStart || 0) + 20;

                // hide button
                this.parentNode.style.display = 'none';

                // load more tasks
                vboTmListLoadTasks(nextStart);
            });

        /**
         * Register to the event for rendering the tasks of an area.
         */
        document.addEventListener('vbo-tm-area-render-task', (e) => {
            if (!e || !e?.detail?.areaId) {
                return;
            }

            let areaId = parseInt(e.detail.areaId);

            if (!vboTmListAreaIds.includes(areaId)) {
                // push new area ID
                vboTmListAreaIds.push(areaId);
            }

            // reload tasks
            vboTmListLoadTasks(0);
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

            let index = vboTmListAreaIds.indexOf(areaId);
            if (index >= 0) {
                // remove the requested area ID
                vboTmListAreaIds.splice(index, 1);
            }

            // reload tasks
            vboTmListLoadTasks(0);
            // toggle area visibility
            vboTmToggleAreaDisplay(areaId, 0);
        });

        /**
         * Register listener for the filters changed event.
         */
        document.addEventListener('vbo-tm-filters-changed', (e) => {
            // obtain the global filters
            let filters = e?.detail?.filters;

            // re-render tasks list
            vboTmListLoadTasks(0, filters);
        });

    });
</script>
