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

// load assets
$vbo_app = VikBooking::getVboApplication();
$vbo_app->loadContextMenuAssets();
$vbo_app->loadDatePicker();
$vbo_app->loadDatesRangePicker();

// define the default events
if (!isset($events) || !is_array($events)) {
    $events = [];
}
$events['filter_dates'] = $events['filter_dates'] ?? 'vbo-tm-apply-filters';

// get current filters
$filters = (array) ($filters ?? []);

// list of allowed date filter types
$allowedTypes = [
    'today'     => JText::translate('VBTODAY'),
    'tomorrow'  => JText::translate('VBOTOMORROW'),
    'yesterday' => JText::translate('VBOYESTERDAY'),
    'week'      => JText::translate('VBO_THIS_WEEK'),
    // month needs to be the second last type, because it will use a separator
    'month'     => JText::translate('VBO_THIS_MONTH'),
    'custom'    => JText::translate('VBO_CUSTOM'),
];

// the currently active dates filter
$activeDates = $filters['dates'] ?? null;

?>

<button type="button" class="btn vbo-context-menu-btn vbo-context-menu-tm-dates<?php echo $activeDates ? ' vbo-tm-filter-active' : ''; ?>">
<?php
if ($activeDates) {
    // active filter
    $dt_filter_name = isset($allowedTypes[$activeDates]) ? $allowedTypes[$activeDates] : $activeDates;
    ?>
    <span class="vbo-context-menu-lbl" title="<?php echo JHtml::fetch('esc_attr', $dt_filter_name); ?>"><?php echo $dt_filter_name; ?></span>
    <span class="vbo-context-menu-lbl-orig" style="display: none;"><?php echo JText::translate('VBPVIEWORDERSONE'); ?></span>
    <?php
} else {
    // filter not set
    ?>
    <span class="vbo-context-menu-lbl"><?php echo JText::translate('VBPVIEWORDERSONE'); ?></span>
    <?php
}
?>
    <span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
</button>

<div class="vbo-tm-dates-filter-helper-wrap" style="display: none;">
    <div class="vbo-tm-dates-filter-helper">
        <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
            <div class="vbo-params-wrap">
                <div class="vbo-params-container">
                    <div class="vbo-params-block">
                        <div class="vbo-param-container">
                            <div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDFROMRANGE'); ?></div>
                            <div class="vbo-param-setting">
                                <div class="vbo-field-calendar">
                                    <div class="input-append">
                                        <input type="text" id="vbo-tm-dates-filter-dt-from" value="" autocomplete="off" />
                                        <button type="button" class="btn btn-secondary vbo-tm-dates-filter-dt-from-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbo-param-container">
                            <div class="vbo-param-label"><?php echo JText::translate('VBNEWRESTRICTIONDTORANGE'); ?></div>
                            <div class="vbo-param-setting">
                                <div class="vbo-field-calendar">
                                    <div class="input-append">
                                        <input type="text" id="vbo-tm-dates-filter-dt-to" value="" autocomplete="off" />
                                        <button type="button" class="btn btn-secondary vbo-tm-dates-filter-dt-to-trigger"><?php VikBookingIcons::e('calendar'); ?></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(function() {

        // all date types
        const dateTypes = <?php echo json_encode($allowedTypes); ?>;

        // build buttons
        let btns = [];

        // push unset date filter button
        btns.push({
            statusId: null,
            class: 'vbo-context-menu-entry-secondary',
            separator: true,
            text: <?php echo json_encode(JText::translate('VBANYTHING')); ?>,
            icon: '<?php echo VikBookingIcons::i('times'); ?>',
            visible: () => {
                return vboTmFilters && vboTmFilters?.dates;
            },
            action: function(root, config) {
                // remove date filter
                VBOCore.emitEvent('<?php echo $events['filter_dates']; ?>', {
                    filters: {
                        dates: null,
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
                        .attr('title', '')
                        .addClass('vbo-context-menu-lbl')
                        .show();
                }

                jQuery(root).removeClass('vbo-tm-filter-active');
            },
        });

        for (const [dateId, dateName] of Object.entries(dateTypes)) {
            // push date button
            btns.push({
                dateId: dateId,
                class: 'vbo-context-menu-entry-secondary',
                text: dateName,
                icon: '<?php echo VikBookingIcons::i('calendar'); ?>',
                separator: dateId == 'month',
                disabled: () => {
                    return vboTmFilters && vboTmFilters?.dates && vboTmFilters.dates == dateId;
                },
                action: function(root, config) {
                    if (dateId !== 'custom') {
                        // apply status filter
                        VBOCore.emitEvent('<?php echo $events['filter_dates']; ?>', {
                            filters: {
                                dates: dateId,
                            },
                        });

                        if (jQuery(root).find('.vbo-context-menu-lbl-orig').length) {
                            // filter changed
                            jQuery(root)
                                .find('.vbo-context-menu-lbl')
                                .attr('class', '')
                                .addClass('vbo-context-menu-lbl')
                                .text(dateName)
                                .attr('title', dateName);
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
                                        .text(dateName)
                                        .attr('title', dateName)
                                );
                        }

                        jQuery(root).addClass('vbo-tm-filter-active');
                    } else {
                        // display modal to apply a custom dates filter

                        // define the modal cancel button
                        let cancel_btn = jQuery('<button></button>')
                            .attr('type', 'button')
                            .addClass('btn')
                            .text(<?php echo json_encode(JText::translate('VBANNULLA')); ?>)
                            .on('click', () => {
                                VBOCore.emitEvent('vbo-tm-filter-dates-dismiss');
                            });

                        // define the modal apply button
                        let apply_btn = jQuery('<button></button>')
                            .attr('type', 'button')
                            .addClass('btn btn-success')
                            .text(<?php echo json_encode(JText::translate('VBAPPLY')); ?>)
                            .on('click', function() {
                                // disable button to prevent double submissions
                                let submit_btn = jQuery(this);
                                submit_btn.prop('disabled', true);

                                // dismiss the modal
                                VBOCore.emitEvent('vbo-tm-filter-dates-dismiss');

                                let dtFrom = jQuery('#vbo-tm-dates-filter-dt-from').val();
                                let dtTo = jQuery('#vbo-tm-dates-filter-dt-to').val();
                                if (!dtFrom || !dtTo) {
                                    alert(<?php echo json_encode(JText::translate('VBO_PLEASE_SELECT')); ?>);
                                    return false;
                                }

                                let readFilter = dtFrom + ' : ' + dtTo;

                                // apply the dates filter
                                VBOCore.emitEvent('<?php echo $events['filter_dates']; ?>', {
                                    filters: {
                                        dates: readFilter,
                                    },
                                });

                                if (jQuery(root).find('.vbo-context-menu-lbl-orig').length) {
                                    // filter changed
                                    jQuery(root)
                                        .find('.vbo-context-menu-lbl')
                                        .attr('class', '')
                                        .addClass('vbo-context-menu-lbl')
                                        .text(readFilter)
                                        .attr('title', readFilter);
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
                                                .text(readFilter)
                                                .attr('title', readFilter)
                                        );
                                }

                                jQuery(root).addClass('vbo-tm-filter-active');
                            });

                        // display modal
                        let modalBody = VBOCore.displayModal({
                            suffix:        'tm_filter_dates',
                            title:         <?php echo json_encode(JText::translate('VBOFILTERBYDATES')); ?>,
                            extra_class:   'vbo-modal-rounded vbo-modal-dialog',
                            body_prepend:  true,
                            lock_scroll:   true,
                            footer_left:   cancel_btn,
                            footer_right:  apply_btn,
                            dismiss_event: 'vbo-tm-filter-dates-dismiss',
                            onDismiss:     () => {
                                jQuery('.vbo-tm-dates-filter-helper').appendTo(jQuery('.vbo-tm-dates-filter-helper-wrap'));
                            },
                        });

                        jQuery('.vbo-tm-dates-filter-helper').appendTo(modalBody);
                    }
                },
            });
        }

        // start context menu on the proper button element
        jQuery('.vbo-context-menu-tm-dates').vboContextMenu({
            placement: 'bottom-left',
            buttons: btns,
        });

        // start date-range-picker calendar
        jQuery('#vbo-tm-dates-filter-dt-from').vboDatesRangePicker({
            checkout: jQuery('#vbo-tm-dates-filter-dt-to'),
            dateFormat: 'yy-mm-dd',
            numberOfMonths: 1,
            changeMonth: true,
            changeYear: true,
            minDate: '-10y',
            maxDate: '+5y',
            onSelect: {
                checkin: (selectedDate) => {
                    if (!selectedDate) {
                        return;
                    }
                    let nowstart = jQuery('#vbo-tm-dates-filter-dt-from').vboDatesRangePicker('getCheckinDate');
                    let nowstartdate = new Date(nowstart.getTime());
                    jQuery('#vbo-tm-dates-filter-dt-from').vboDatesRangePicker('checkout', 'minDate', nowstartdate);
                },
                checkout: function(selectedDate) {
                    // do nothing
                },
            },
            labels: {
                checkin: <?php echo json_encode(JText::translate('VBNEWRESTRICTIONDFROMRANGE')); ?>,
                checkout: <?php echo json_encode(JText::translate('VBNEWRESTRICTIONDTORANGE')); ?>,
            },
            environment: {
                section: 'admin',
                autoHide: true,
            },
        });

        // register click events on calendar triggers
        jQuery('.vbo-tm-dates-filter-dt-from-trigger, .vbo-tm-dates-filter-dt-to-trigger').on('click', () => {
            jQuery('#vbo-tm-dates-filter-dt-from').trigger('focus');
        });

    });
</script>
