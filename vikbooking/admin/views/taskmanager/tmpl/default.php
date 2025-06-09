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

JHtml::fetch('vbohtml.tmscripts.changestatus');

// access the VBO application
$vbo_app = VikBooking::getVboApplication();

// load context menu assets
$vbo_app->loadContextMenuAssets();

// access task manager
$taskManager = VBOFactory::getTaskManager();

// get all areas
$allAreas = VBOTaskModelArea::getInstance()->getItems([], 0, 0, ['id', 'name']);

// preload the chat assets
VBOFactory::getChatMediator()->useAssets();

JText::script('VBO_SEARCH_TASKS');

?>

<div class="vbo-tm-wrapper" style="<?php echo $allAreas ? '' : 'display: none;'; ?>">

    <div class="vbo-tm-toolbar">

        <div class="vbo-tm-toolbar-header">

            <div class="vbo-tm-areas-ctx">
                <?php
                // display task manager areas context-menu
                $layout_data = [
                    'caller' => 'taskmanager',
                    'events' => [
                        'render_task'  => 'vbo-tm-area-render-task',
                        'hide_task'    => 'vbo-tm-area-hide-task',
                        'update_areas' => 'vbo-tm-area-update-status',
                    ],
                    'active' => $this->activeAreas,
                ];

                echo JLayoutHelper::render('taskmanager.areas.contextmenu', $layout_data);
                ?>
            </div>

            <div class="vbo-tm-modes">
            <?php
            foreach ($this->allowedModes as $mode => $mode_data) {
                ?>
                <span class="vbo-tm-mode mode-<?php echo $mode . ($mode == $this->mode ? ' mode-active' : ''); ?>" data-mode="<?php echo $mode; ?>"<?php echo ($mode_data['link'] ?? '') ? ' data-link="' . $mode_data['link'] . '"' : ''; ?>>
                <?php
                if ($mode_data['link'] ?? '') {
                    // handle the external view link differently
                    ?>
                    <a href="<?php echo $mode_data['link']; ?>"><?php VikBookingIcons::e($mode_data['icon']); ?> <?php echo $mode_data['name']; ?></a>
                    <?php
                } else {
                    // regular taskmanager view mode
                    ?>
                    <a href="index.php?option=com_vikbooking&view=taskmanager&mode=<?php echo $mode; ?>"><?php VikBookingIcons::e($mode_data['icon']); ?> <?php echo $mode_data['name']; ?></a>
                    <?php
                }
                ?>
                </span>
                <?php
            }
            ?>
            </div>

            <?php
            if ($allAreas) {
            ?>
            <div class="vbo-tm-newtask">
                <button type="button" class="btn vbo-context-menu-tm-newtask">
                    <span class="vbo-context-menu-ico"><?php VikBookingIcons::e('plus'); ?></span>
                    <span class="vbo-context-menu-lbl"><?php echo JText::translate('VBO_NEW_TASK'); ?></span>
                </button>
            </div>
            <?php
            }
            ?>

        </div>

        <div class="vbo-tm-toolbar-filters">

            <div class="vbo-tm-toolbar-filter vbo-tm-statustype-ctx">
                <?php
                // display task manager status-types context-menu
                $layout_data = [
                    'caller' => 'taskmanager',
                    'events' => [
                        'filter_status' => 'vbo-tm-apply-filters',
                    ],
                    'filters' => $this->filters,
                ];

                echo JLayoutHelper::render('taskmanager.statuses.contextmenu', $layout_data);
                ?>
            </div>

            <?php
            // display task manager color-tags context-menu
            $layout_data = [
                'caller'  => 'taskmanager',
                'events' => [
                    'filter_tag' => 'vbo-tm-apply-filters',
                ],
                'filters' => $this->filters,
            ];

            $tagsDropdown = JLayoutHelper::render('taskmanager.colortags.contextmenu', $layout_data);

            if ($tagsDropdown): ?>
                <div class="vbo-tm-toolbar-filter vbo-tm-colortags-ctx">
                    <?php echo $tagsDropdown; ?>
                </div>
            <?php endif; ?>

            <div class="vbo-tm-toolbar-filter vbo-tm-dates-ctx">
                <?php
                // display task manager dates context-menu
                $layout_data = [
                    'caller'  => 'taskmanager',
                    'events' => [
                        'filter_dates' => 'vbo-tm-apply-filters',
                    ],
                    'filters' => $this->filters,
                ];

                echo JLayoutHelper::render('taskmanager.dates.contextmenu', $layout_data);
                ?>
            </div>

            <div class="vbo-tm-toolbar-filter vbo-tm-filter-sel2 vbo-tm-filter-assignee<?php echo ($this->filters['assignee'] ?? null) ? ' vbo-tm-filter-active' : ''; ?>">
                <div class="vbo-singleselect-inline-elems-wrap vbo-search-elems-wrap">
                <?php
                echo $vbo_app->renderElementsDropDown([
                    'id'          => 'vbo-tm-filter-assignee',
                    'placeholder' => JText::translate('VBO_ASSIGNEE'),
                    'allow_clear' => true,
                    'attributes'  => [
                        'name' => 'filters[assignee]',
                        'onchange' => "VBOCore.emitEvent('vbo-tm-apply-filters', {filters: {assignee: this.value}}); this.value ? jQuery('.vbo-tm-filter-assignee').addClass('vbo-tm-filter-active') : jQuery('.vbo-tm-filter-assignee').removeClass('vbo-tm-filter-active')",
                    ],
                    'style_selection' => true,
                    'default_selection_icon' => VikBookingIcons::i('user-tie'),
                    'selected_value' => $this->filters['assignee'] ?? null,
                    'width' => '200px',
                ], VikBooking::getOperatorInstance()->getElements(), [
                    [
                        'text' => JText::translate('VBO_UNASSIGNED'),
                        'elements' => [
                            [
                                'id' => -1,
                                'text' => JText::translate('VBO_UNASSIGNED'),
                            ],
                        ],
                    ],
                ]);
                ?>
                </div>
            </div>

            <div class="vbo-tm-toolbar-filter vbo-tm-filter-sel2 vbo-tm-filter-listing<?php echo ($this->filters['id_room'] ?? null) ? ' vbo-tm-filter-active' : ''; ?>">
                <div class="vbo-singleselect-inline-elems-wrap vbo-search-elems-wrap">
                <?php
                echo $vbo_app->renderElementsDropDown([
                    'id'              => 'vbo-tm-filter-idroom',
                    'elements'        => 'listings',
                    'load_categories' => true,
                    'placeholder'     => JText::translate('VBO_LISTING'),
                    'allow_clear'     => true,
                    'attributes'      => [
                        'name' => 'filters[id_room]',
                        'onchange' => "VBOCore.emitEvent('vbo-tm-apply-filters', {filters: {id_room: this.value}}); this.value ? jQuery('.vbo-tm-filter-listing').addClass('vbo-tm-filter-active') : jQuery('.vbo-tm-filter-listing').removeClass('vbo-tm-filter-active')",
                    ],
                    'style_selection' => true,
                    'default_selection_icon' => VikBookingIcons::i('bed'),
                    'selected_value' => $this->filters['id_room'] ?? null,
                    'width' => '200px',
                ]);
                ?>
                </div>
            </div>

            <div class="vbo-tm-toolbar-filter vbo-tm-filter-sel2 vbo-tm-filter-booking<?php echo ($this->filters['id_order'] ?? null) ? ' vbo-tm-filter-active' : ''; ?>">
                <div class="vbo-singleselect-inline-elems-wrap vbo-search-elems-wrap">
                <?php
                echo $vbo_app->renderSearchElementsDropDown([
                    'id'          => 'vbo-tm-filter-idorder',
                    'elements'    => 'bookings',
                    'placeholder' => JText::translate('VBO_AITOOL_SEARCH_BOOKINGS'),
                    'allow_clear' => true,
                    'attributes'  => [
                        'name' => 'filters[id_order]',
                        'onchange' => "VBOCore.emitEvent('vbo-tm-apply-filters', {filters: {id_order: this.value}}); this.value ? jQuery('.vbo-tm-filter-booking').addClass('vbo-tm-filter-active') : jQuery('.vbo-tm-filter-booking').removeClass('vbo-tm-filter-active')",
                    ],
                    'style_selection' => true,
                    'selected_id'     => true,
                    'selection_class' => 'vbo-sel2-selected-search-elem-full',
                    'selected_value'  => $taskManager->buildBookingElement((int) ($this->filters['id_order'] ?? null)),
                    'width' => '250px',
                ]);
                ?>
                </div>
            </div>

            <div class="vbo-tm-toolbar-filter vbo-tm-filter-search<?php echo ($this->filters['search'] ?? null) ? ' vbo-tm-filter-active' : ''; ?>">
                <a href="javascript:void(0)" id="vbo-tm-filter-search">
                    <?php VikBookingIcons::e('search', 'fa-3x'); ?>
                </a>
                <script>
                    (function($) {
                        'use strict';

                        $(function() {
                            $('.vbo-tm-filter-search').on('click', function() {
                                const searchLink = $(this);

                                const overlay = $('<div class="vbo-tm-filter-search-overlay"></div>');

                                overlay.on('mousedown', (event) => {
                                    if ($(event.target).is(overlay)) {
                                        overlay.remove();
                                    }
                                });

                                const handleEscape = (event) => {
                                    if (event.key === 'Escape' || event.key === 'Enter') {
                                        overlay.remove();
                                        $(window).off('keydown', handleEscape);
                                    }
                                }

                                $(window).on('keydown', handleEscape);

                                const component = $('<div class="finder-box"><?php VikBookingIcons::e('search', 'search'); ?></div>');
                                overlay.append(component);

                                const input = $('<input type="text" />')
                                    .attr('placeholder', Joomla.JText._('VBO_SEARCH_TASKS'))
                                    .val(vboTmFilters.search || '');

                                component.append(input);

                                let lastValue = input.val();

                                const clear = $('<?php VikBookingIcons::e('times-circle', 'clear'); ?>');
                                component.append(clear);

                                clear.on('click', () => {
                                    input.val('').trigger('keydown');
                                    handleEscape({key: 'Escape'});
                                });

                                if (!lastValue) {
                                    clear.hide();
                                }

                                input.on('keydown', VBOCore.debounceEvent((event) => {
                                    const newValue = input.val();

                                    if (event.key === 'Escape') {
                                        // prevent search
                                        return;
                                    }

                                    if (newValue.toLowerCase().trim() == lastValue.toLowerCase().trim()) {
                                        // nothing has changed
                                        return;
                                    }

                                    lastValue = newValue;

                                    VBOCore.emitEvent('vbo-tm-apply-filters', {
                                        filters: {
                                            search: newValue,
                                        },
                                    });

                                    if (newValue) {
                                        searchLink.addClass('vbo-tm-filter-active');
                                        clear.show();
                                    } else {
                                        searchLink.removeClass('vbo-tm-filter-active');
                                        clear.hide();
                                    }
                                }, 300));

                                $('body').append(overlay);

                                input.focus();
                            });
                        });
                    })(jQuery);
                </script>
            </div>

        </div>

    </div>

    <div class="vbo-tm-content">
    <?php
    /**
     * Render the current template mode.
     */
    echo $this->loadTemplate($this->mode);
    ?>
    </div>

</div>

<?php
if (!$allAreas) {
    echo JLayoutHelper::render('taskmanager.areas.blank');
}
?>

<script type="text/javascript">
    /**
     * Register the global filters object.
     */
    const vboTmFilters = <?php echo json_encode(($this->filters ?: (new stdClass))); ?>;

    /**
     * Register all area details.
     */
    const vboTmAllAreas = <?php echo json_encode($allAreas); ?>;

    /**
     * Register the active area IDs.
     */
    const vboTmActiveAreas = <?php echo json_encode($this->activeAreas); ?>;

    /**
     * Listen to the event for applying new filters.
     */
    document.addEventListener('vbo-tm-apply-filters', (e) => {
        if (!e || !e.detail || !e.detail.filters || typeof e.detail.filters !== 'object') {
            return;
        }

        for (const [type, value] of Object.entries(e.detail.filters)) {
            // set the requested filter value
            vboTmFilters[type] = value;
        }

        // handle external button links with dynamic filters
        vboTmApplyLinkFilters(vboTmFilters);

        // dispatch the filters-changed event
        VBOCore.emitEvent('vbo-tm-filters-changed', {
            filters: vboTmFilters,
        });
    });

    /**
     * Listen to the event for showing a new area/project.
     */
    document.addEventListener('vbo-tm-area-render-task', () => {
        // handle external button links with dynamic filters
        vboTmApplyLinkFilters();
    });

    /**
     * Listen to the event for hiding an area/project.
     */
    document.addEventListener('vbo-tm-area-hide-task', () => {
        // handle external button links with dynamic filters
        vboTmApplyLinkFilters();
    });

    /**
     * Listen to the event for updating the areas/projects.
     */
    document.addEventListener('vbo-tm-area-update-status', () => {
        // handle external button links with dynamic filters
        vboTmApplyLinkFilters();
    });

    /**
     * Register function for building external button links with dynamic filters.
     */
    const vboTmApplyLinkFilters = (filters) => {
        // gather the filters to apply
        filters = Object.assign({}, (filters || vboTmFilters));

        // start the search params object
        let searchParams = new URLSearchParams();

        // iterate over all filter object properties
        for (const [type, value] of Object.entries(filters)) {
            // set the search param key and value
            if (value != null && value != '') {
                searchParams.set('tmfilters[' + type + ']', value);
            }
        }

        // set the active areas/projects
        if (Array.isArray(vboTmActiveAreas) && vboTmActiveAreas.length) {
            vboTmActiveAreas.forEach((areaId) => {
                // append the search param key and value
                searchParams.append('tmfilters[area_ids][]', areaId);
            });
        }

        // build the additional query string for the links
        let queryParams = searchParams.toString();

        // iterate over all button with external links
        document.querySelectorAll('.vbo-tm-modes .vbo-tm-mode[data-link]').forEach((btnLink) => {
            let base_link = btnLink.getAttribute('data-link');
            let final_link = base_link + '&' + queryParams;
            let anchor = btnLink.querySelector('a');
            if (!anchor) {
                return;
            }
            anchor.setAttribute('href', final_link);
        });
    };

    /**
     * Register function to silently toggle the task area active state.
     */
    const vboTmToggleAreaDisplay = (areaId, display) => {
        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.toggleAreaDisplay'); ?>",
            {
                area: {
                    id: areaId,
                    display: (typeof display === 'undefined' ? -1 : display),
                },
            },
            (resp) => {
                // do nothing on success
            },
            (error) => {
                // silently log the error
                console.error(error.responseText);
            }
        );
    };

    /**
     * Register listener for creating a new task.
     */
    document.addEventListener('vbo-tm-newtask-trigger', (e) => {
        // obtain the area ID and filters
        const areaId = e?.detail?.areaId;
        const filters = e?.detail?.filters || vboTmFilters;

        if (!areaId) {
            return;
        }

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
            title:          <?php echo json_encode(JText::translate('VBO_NEW_TASK')); ?>,
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
                    area_id: areaId,
                    form_id: 'vbo-tm-task-manage-form',
                    filters: filters,
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

    jQuery(function() {

        // build buttons for new-task context menu
        let btns = [];

        vboTmAllAreas.forEach((area) => {
            // push area button
            btns.push({
                areaId: area.id,
                class: 'vbo-context-menu-entry-secondary',
                icon: '<?php echo VikBookingIcons::i('plus-circle'); ?>',
                text: area.name,
                action: (root, event) => {
                    VBOCore.emitEvent('vbo-tm-newtask-trigger', {
                        areaId: area.id,
                        filters: vboTmFilters,
                    });
                },
            });
        });

        // start context menu on the new task button element
        jQuery('.vbo-context-menu-tm-newtask').vboContextMenu({
            placement: 'bottom-right',
            buttons: btns,
        });

        // populate external button links
        vboTmApplyLinkFilters();

    });
</script>
