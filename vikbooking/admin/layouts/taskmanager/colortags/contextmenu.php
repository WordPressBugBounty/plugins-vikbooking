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
$events['filter_tag'] = $events['filter_tag'] ?? 'vbo-tm-apply-filters';

// get current filters
$filters = (array) ($filters ?? []);

// access the task manager
$taskManager = VBOFactory::getTaskManager();

// load all the available tag colors
$tagColors = array_map(function($color, $hex) {
    return [
        'color' => $color,
        'hex'   => $hex,
    ];
}, $taskManager->getTagColors(true), array_values($taskManager->getTagColors()));

// load all the available color tags
$colorTags = $taskManager->getColorTags();

if (!$colorTags) {
    return;
}

// find the active tag, if any
$activeTag = [];
if ($filters['tag'] ?? null) {
    foreach ($colorTags as $colorTag) {
        if ($colorTag->id == $filters['tag']) {
            // tag found
            $activeTag = (array) $colorTag;
            break;
        }
    }
}

?>

<button type="button" class="btn vbo-context-menu-btn vbo-context-menu-tm-colortags<?php echo $activeTag ? ' vbo-tm-filter-active' : ''; ?>">
<?php
if ($activeTag) {
    // active filter
    ?>
    <span class="vbo-context-menu-lbl<?php echo !empty($activeTag['color']) ? ' ' . $activeTag['color'] : ''; ?>"><?php echo $activeTag['name']; ?></span>
    <span class="vbo-context-menu-lbl-orig" style="display: none;"><?php echo JText::translate('VBO_TAGS'); ?></span>
    <?php
} else {
    // filter not set
    ?>
    <span class="vbo-context-menu-lbl"><?php echo JText::translate('VBO_TAGS'); ?></span>
    <?php
}
?>
    <span class="vbo-context-menu-ico"><?php VikBookingIcons::e('sort-down'); ?></span>
</button>

<div class="vbo-tm-colortag-form-helper" style="display: none;">
    <div class="vbo-tm-colortag-form-wrap">
        <form action="#" id="vbo-tm-managecolortag-form" method="post">
            <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
                <div class="vbo-params-wrap">
                    <div class="vbo-params-container">
                        <div class="vbo-params-block">
                            
                            <div class="vbo-param-container">
                                <div class="vbo-param-label"><?php echo JText::translate('VBO_TAG'); ?></div>
                                <div class="vbo-param-setting">
                                    <input type="text" name="colortag[name]" value="" />
                                </div>
                            </div>

                            <div class="vbo-param-container">
                                <div class="vbo-param-label"><?php echo JText::translate('VBO_COLOR'); ?></div>
                                <div class="vbo-param-setting">
                                    <span class="vbo-tm-managecolortag-color vbo-colortag-circle vbo-tm-colortag-circle">&nbsp;</span>
                                    <input type="hidden" name="colortag[color]" value="" />
                                    <input type="hidden" name="colortag[id]" value="" />
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    jQuery(function() {

        // all tag colors
        const tagColors = <?php echo json_encode($tagColors); ?>;

        // all color tags
        const colorTags = <?php echo json_encode($colorTags); ?>;

        // build buttons
        let btns = [];

        // push unset status filter button
        btns.push({
            tagId: null,
            class: 'vbo-context-menu-entry-secondary',
            searchable: false,
            separator: false,
            text: <?php echo json_encode(JText::translate('VBANYTHING')); ?>,
            icon: '<?php echo VikBookingIcons::i('times'); ?>',
            visible: () => {
                return vboTmFilters && vboTmFilters?.tag;
            },
            action: function(root, config) {
                // remove tag filter
                VBOCore.emitEvent('<?php echo $events['filter_tag']; ?>', {
                    filters: {
                        tag: null,
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

        // push group button
        btns.push({
            tagId: null,
            class: 'btngroup',
            searchable: false,
            text: <?php echo json_encode(JText::translate('VBO_TAGS')); ?>,
            disabled: true,
        });

        colorTags.forEach((tag, index) => {
            // build button icon element
            let btnIconEl = jQuery('<span></span>')
                .addClass('vbo-colortag-circle')
                .addClass('vbo-tm-colortag-circle');

            if (tag?.color) {
                btnIconEl.addClass(tag.color);
            } else if (tag?.hex) {
                btnIconEl.css('background-color', tag.hex);
            }

            // build button text element
            let btnTxtEl = jQuery('<span></span>')
                .addClass('vbo-ctxmenu-entry-icn')
                .html(jQuery('<span></span>').text(tag.name));
            let btnTxtIcn = jQuery('<i></i>')
                .addClass('vbo-tm-colortag-edit')
                .addClass('<?php echo VikBookingIcons::i('edit'); ?>');
            btnTxtEl.append(btnTxtIcn);

            // push tag button
            btns.push({
                tagId: tag.id,
                tagColor: tag.color || null,
                tagName: tag.name,
                class: 'vbo-context-menu-entry-secondary',
                text: btnTxtEl,
                icon: btnIconEl,
                disabled: () => {
                    return vboTmFilters && vboTmFilters?.tag == tag.id;
                },
                action: function(root, event) {
                    if (!event?.target || !jQuery(event.target).hasClass('vbo-tm-colortag-edit')) {
                        // apply tag filter
                        VBOCore.emitEvent('<?php echo $events['filter_tag']; ?>', {
                            filters: {
                                tag: tag.id || this.tagId,
                            }
                        });

                        let color = tag.color || this.tagColor;
                        let name = tag.name || this.tagName;

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

                        // do not proceed
                        return;
                    }

                    // define the modal delete button
                    let delete_btn = jQuery('<button></button>')
                        .attr('type', 'button')
                        .addClass('btn btn-danger')
                        .text(<?php echo json_encode(JText::translate('VBELIMINA')); ?>)
                        .on('click', () => {
                            // disable button to prevent double submissions
                            let submit_btn = jQuery(this);
                            submit_btn.prop('disabled', true);

                            // start loading animation
                            VBOCore.emitEvent('vbo-tm-managecolortag-loading');

                            // get form data
                            const colortagForm = new FormData(document.querySelector('#vbo-tm-managecolortag-form'));

                            // build query parameters for the request
                            let qpRequest = new URLSearchParams(colortagForm).toString();

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.deleteColorTag'); ?>",
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
                                    VBOCore.emitEvent('vbo-tm-managecolortag-loading');
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
                            VBOCore.emitEvent('vbo-tm-managecolortag-loading');

                            // get form data
                            const colortagForm = new FormData(document.querySelector('#vbo-tm-managecolortag-form'));

                            // build query parameters for the request
                            let qpRequest = new URLSearchParams(colortagForm).toString();

                            // make the request
                            VBOCore.doAjax(
                                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=taskmanager.updateColorTag'); ?>",
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
                                    VBOCore.emitEvent('vbo-tm-managecolortag-loading');
                                }
                            );
                        });

                    // display modal
                    let modalBody = VBOCore.displayModal({
                        suffix:        'tm_newarea_modal',
                        title:         tag.name + ' - ' + <?php echo json_encode(JText::translate('VBMAINPAYMENTSEDIT')); ?>,
                        extra_class:   'vbo-modal-rounded vbo-modal-dialog',
                        body_prepend:  true,
                        lock_scroll:   true,
                        footer_left:   delete_btn,
                        footer_right:  save_btn,
                        loading_event: 'vbo-tm-managecolortag-loading',
                        dismiss_event: 'vbo-tm-managecolortag-dismiss',
                        onDismiss:     () => {
                            jQuery('.vbo-tm-colortag-form-wrap').appendTo(jQuery('.vbo-tm-colortag-form-helper'));
                        },
                    });

                    // set modal body
                    jQuery('.vbo-tm-colortag-form-wrap').appendTo(modalBody);

                    // populate color tag values
                    jQuery('.vbo-tm-colortag-form-wrap').find('input[name="colortag[id]"]').val(tag.id);
                    jQuery('.vbo-tm-colortag-form-wrap').find('input[name="colortag[name]"]').val(tag.name);
                    if (tag?.color) {
                        // set current color in input hidden field
                        jQuery('.vbo-tm-colortag-form-wrap').find('input[name="colortag[color]"]').val(tag.color);
                        // get all CSS classes for the current color circle
                        let current_circle = jQuery('.vbo-tm-colortag-form-wrap').find('.vbo-tm-managecolortag-color');
                        let all_cls = current_circle.attr('class').split(' ');
                        all_cls.forEach((cls) => {
                            if (!cls) {
                                return;
                            }
                            if (cls.indexOf('vbo-') !== 0) {
                                // remove the class that may indicate a previous color enum
                                current_circle.removeClass(cls);
                            }
                        });
                        // add the necessary class for the current color
                        current_circle.addClass(tag.color);
                    }
                },
            });
        });

        // start context menu on the proper button element
        jQuery('.vbo-context-menu-tm-colortags').vboContextMenu({
            placement: 'bottom-left',
            buttons: btns,
            search: true,
        });

        // tag color buttons
        let tagColorBtns = [];

        tagColors.forEach((color) => {
            // push tag color button
            tagColorBtns.push({
                colorId:  color.color,
                colorHex: color.hex,
                class: 'vbo-context-menu-entry-secondary',
                text: jQuery('<span></span>').addClass('vbo-colortag-circle ' + color.color),
                action: function(root, config) {
                    // set current color in input hidden field
                    jQuery('.vbo-tm-colortag-form-wrap').find('input[name="colortag[color]"]').val(color.color);
                    // get all CSS classes for the current color circle
                    let current_circle = jQuery('.vbo-tm-colortag-form-wrap').find('.vbo-tm-managecolortag-color');
                    let all_cls = current_circle.attr('class').split(' ');
                    all_cls.forEach((cls) => {
                        if (!cls) {
                            return;
                        }
                        if (cls.indexOf('vbo-') !== 0) {
                            // remove the class that may indicate a previous color enum
                            current_circle.removeClass(cls);
                        }
                    });
                    // add the necessary class for the current color
                    current_circle.addClass(color.color);
                },
            });
        });

        // start context menu for picking a new color for an existing tag
        jQuery('.vbo-tm-managecolortag-color').vboContextMenu({
            class: (jQuery.vboContextMenu.defaults.class || '') + ' vbo-dropdown-cxmenu-horizontal',
            placement: 'right',
            buttons: tagColorBtns,
        });

    });
</script>