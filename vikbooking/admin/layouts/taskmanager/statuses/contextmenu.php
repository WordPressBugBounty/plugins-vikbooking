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
 * @var array   $events     List of JS events to trigger.
 * @var array   $filters    The current View filters.
 */
extract($displayData);

// load context menu assets
VikBooking::getVboApplication()->loadContextMenuAssets();

// define the default events
if (!isset($events) || !is_array($events)) {
    $events = [];
}
$events['filter_status'] = $events['filter_status'] ?? 'vbo-tm-apply-filters';

// get current filters
$filters = (array) ($filters ?? []);

// access the task manager
$taskManager = VBOFactory::getTaskManager();

// make TASK_MANAGER_STATUSES var global
JHtml::fetch('vbohtml.tmscripts.statuses');

// find the active status, if any
$activeStatus = [];

foreach ($taskManager->getStatusGroupElements() as $groupId => $group) {
    // iterate over the statuses of this group
    foreach ($group['elements'] as $statusType) {
        // check if it's the active one
        if ($statusType['id'] == ($filters['statusId'] ?? null)) {
            // active status found
            $activeStatus = $statusType;
        }
    }
}

?>

<button type="button" class="btn vbo-context-menu-btn vbo-context-menu-tm-statuses<?php echo $activeStatus ? ' vbo-tm-filter-active' : ''; ?>">
<?php
if ($activeStatus) {
    // active filter
    ?>
    <span class="vbo-context-menu-lbl"><?php echo $activeStatus['text']; ?></span>
    <span class="vbo-context-menu-lbl-orig" style="display: none;"><?php echo JText::translate('VBSTATUS'); ?></span>
    <?php
} else {
    // filter not set
    ?>
    <span class="vbo-context-menu-lbl"><?php echo JText::translate('VBSTATUS'); ?></span>
    <?php
}
?>
    <span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
</button>

<script type="text/javascript">
    jQuery(function() {

        // build buttons
        let btns = [];

        // push unset status filter button
        btns.push({
            statusId: null,
            class: 'vbo-context-menu-entry-secondary',
            searchable: false,
            separator: false,
            text: <?php echo json_encode(JText::translate('VBANYTHING')); ?>,
            icon: '<?php echo VikBookingIcons::i('times'); ?>',
            visible: () => {
                return vboTmFilters && vboTmFilters?.statusId;
            },
            action: function(root, config) {
                // remove status filter
                VBOCore.emitEvent('<?php echo $events['filter_status']; ?>', {
                    filters: {
                        statusId: null,
                    },
                });

                if (jQuery(root).find('.vbo-context-menu-lbl-orig').length) {
                    // restore default label
                    jQuery(root)
                        .find('.vbo-context-menu-lbl')
                        .remove();

                    jQuery(root)
                        .find('.vbo-context-menu-lbl-orig')
                        .attr('class', '')
                        .addClass('vbo-context-menu-lbl')
                        .show();
                }

                jQuery(root).removeClass('vbo-tm-filter-active');
            },
        });

        window.TASK_MANAGER_STATUSES.forEach((elem, index) => {
            // build button icon element
            let btnIconEl = jQuery('<span></span>')
                .addClass('vbo-colortag-circle')
                .addClass('vbo-tm-colortag-circle')
                .addClass('vbo-tm-statustype-circle');

            if (elem?.color) {
                btnIconEl.addClass(elem.color);
            }

            // push status button
            btns.push({
                statusId: elem.id,
                class: elem.id ? 'vbo-context-menu-entry-secondary' : 'btngroup',
                searchable: elem.id ? true : false,
                text: elem.text,
                icon: elem.id ? btnIconEl : null,
                disabled: () => {
                    return !elem.id || (vboTmFilters && vboTmFilters?.statusId == elem.id);
                },
                action: function(root, config) {
                    if (this.statusId) {
                        // apply status filter
                        VBOCore.emitEvent('<?php echo $events['filter_status']; ?>', {
                            filters: {
                                statusId: this.statusId,
                            },
                        });

                        let color = elem?.color || '';
                        let name = elem.text || this.text;

                        if (jQuery(root).find('.vbo-context-menu-lbl-orig').length) {
                            // filter changed
                            jQuery(root)
                                .find('.vbo-context-menu-lbl')
                                .attr('class', '')
                                .addClass('vbo-context-menu-lbl')
                                .text(name);
                        } else {
                            // filter set for the first time
                            jQuery(root)
                                .find('.vbo-context-menu-lbl')
                                .attr('class', '')
                                .addClass('vbo-context-menu-lbl-orig')
                                .hide();

                            jQuery(root)
                                .find('.vbo-context-menu-lbl-orig')
                                .parent()
                                .prepend(
                                    jQuery('<span></span>')
                                        .addClass('vbo-context-menu-lbl')
                                        .text(name)
                                );
                        }

                        jQuery(root).addClass('vbo-tm-filter-active');
                    }
                },
            });
        });

        // start context menu on the proper button element
        jQuery('.vbo-context-menu-tm-statuses').vboContextMenu({
            placement: 'bottom-left',
            buttons: btns,
            search: true,
        });

    });
</script>
