<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * It is possible to manage either all tool permissions for
 * one operator, or the permissions of one tool for all operators.
 * It is eventually allowed to manage one tool for one operator,
 * but it is NOT possible to manage all tools for all operators.
 * 
 * @var string  $tool 	       Optional tool type (i.e. "tableaux").
 * @var int     $operator_id   Optional operator ID.
 * @var string  $toggle_event  Optional JS event name to toggle the modal.
 */
extract($displayData);

// define the tool to manage and/or the operator ID
$tool        = $tool ?? null;
$operator_id = $operator_id ?? null;

// js event to toggle the modal window
$toggle_event  = $toggle_event ?? 'vbo-tool-permissions-modal-toggle';
$loading_event = 'vbo-tool-permissions-modal-loading';
$dismiss_event = 'vbo-tool-permissions-modal-dismiss';

// language definitions
JText::script('VBOPERMSOPERATORS');
JText::script('VBO_WANT_PROCEED');
JText::script('VBSAVE');
JText::script('VBO_PLEASE_SELECT');

// access the global operators object
$oper_obj = VikBooking::getOperatorInstance();

// get the permission types for the request tool, or for all tools
$permission_types = $oper_obj->getToolPermissionTypes($tool);

if (!$permission_types || (!$tool && !$operator_id)) {
    // invalid layout setup, abort
    return;
}

if ($tool) {
    // always convert the permission types into an associative list of tools-permissions
    $permission_types = [$tool => $permission_types];
}

// check if we should load the details of one exact operator ID or all
$operator        = [];
$operators_list  = [];
$operators_assoc = [];

if ($operator_id) {
    $operator = $oper_obj->getOne($operator_id);
    if (!$operator) {
        // abort
        VBOHttpDocument::getInstance()->close(404, 'Operator not found');
    }
} else {
    // load all the operators
    $operators_list = $oper_obj->getAll();
    foreach ($operators_list as $operator_info) {
        $operators_assoc[$operator_info['id']] = implode(' ', array_filter([$operator_info['first_name'], $operator_info['last_name']]));
    }
}

?>

<div class="vbo-operator-tool-permissions-container" style="display: none;">
    <div class="vbo-operator-tool-permissions-wrap">
        <div class="vbo-operator-tools-list">
            <div class="vbo-operator-tool-tab vbo-operator-tool-tab-activeperms vbo-operator-tool-tab-active" data-tool-id="">
                <span class="vbo-operator-tool-name"><?php echo JText::translate('VBO_ACTIVE_PERMS'); ?></span>
            </div>
        <?php
        foreach ($permission_types as $tool_id => $tool_data) {
            ?>
            <div class="vbo-operator-tool-tab" data-tool-id="<?php echo JHtml::fetch('esc_attr', $tool_id); ?>">
                <span class="vbo-operator-tool-name"><?php echo $tool_data['name']; ?></span>
            </div>
            <?php
        }
        ?>
        </div>
        <div class="vbo-operator-tools-permissions">
            <div class="vbo-operator-tool-permissions vbo-operator-tool-permissions-activeperms" data-tool-id="">
                <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
                    <div class="vbo-params-wrap">
                        <div class="vbo-params-container">
                    <?php
                    if ($operator_id) {
                        // display the active permissions for the current operator
                        foreach ($operator['perms'] as $tool_perms) {
                            $tool_name = $oper_obj->getToolName($tool_perms['type']);
                            ?>
                            <div class="vbo-params-block">
                                <div class="vbo-param-container">
                                    <div class="vbo-param-label">
                                        <span class="label label-info"><?php echo $tool_name; ?></span>
                                    </div>
                                    <div class="vbo-param-setting">
                                        <button type="button" class="btn vbo-tool-permissions-edit-btn" data-operator-id="<?php echo $operator['id']; ?>" data-tool-id="<?php echo $tool_perms['type']; ?>"><?php VikBookingIcons::e('edit'); ?> <?php echo JText::translate('VBMAINPAYMENTSEDIT'); ?></button>
                                        <button type="button" class="btn btn-danger vbo-permissions-del-btn" data-operator-id="<?php echo $operator['id']; ?>" data-tool-id="<?php echo $tool_perms['type']; ?>"><?php VikBookingIcons::e('times'); ?> <?php echo JText::translate('VBELIMINA'); ?></button>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        if (!$operator['perms']) {
                            ?>
                            <p class="info"><?php echo JText::translate('VBO_NO_RECORDS_FOUND'); ?></p>
                            <?php
                        }
                    } elseif ($tool) {
                        // display all operators with an active permission for this tool
                        $active_operators = $oper_obj->getOperatorsFromPermissions($tool, $operators_list);
                        foreach ($active_operators as $active_operator) {
                            $operator_tool_perms = json_encode($active_operator['perms']);
                            ?>
                            <div class="vbo-params-block">
                                <div class="vbo-param-container">
                                    <div class="vbo-param-label">
                                        <div class="vbo-customer-info-box">
                                            <div class="vbo-customer-info-box-avatar vbo-customer-avatar-small">
                                                <span>
                                                <?php
                                                if (!empty($active_operator['pic'])) {
                                                    ?>
                                                    <img class="no-click" src="<?php echo strpos($active_operator['pic'], 'http') === 0 ? $active_operator['pic'] : VBO_SITE_URI . 'resources/uploads/' . $active_operator['pic']; ?>" />
                                                    <?php
                                                } else {
                                                    VikBookingIcons::e('user-circle');
                                                }
                                                ?>
                                                </span>
                                            </div>
                                            <div class="vbo-customer-info-box-name">
                                                <a href="index.php?option=com_vikbooking&task=editoperator&cid[]=<?php echo $active_operator['id']; ?>" target="_blank">
                                                    <?php echo implode(' ', array_filter([$active_operator['first_name'], $active_operator['last_name']])); ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="vbo-param-setting">
                                        <input type="hidden" class="vbo-operator-tool-permissions-json" value="<?php echo JHtml::fetch('esc_attr', $operator_tool_perms); ?>" />
                                        <button type="button" class="btn vbo-oper-permissions-edit-btn" data-operator-id="<?php echo $active_operator['id']; ?>" data-tool-id="<?php echo $tool; ?>"><?php VikBookingIcons::e('edit'); ?> <?php echo JText::translate('VBMAINPAYMENTSEDIT'); ?></button>
                                        <button type="button" class="btn btn-danger vbo-permissions-del-btn" data-operator-id="<?php echo $active_operator['id']; ?>" data-tool-id="<?php echo $tool; ?>"><?php VikBookingIcons::e('times'); ?> <?php echo JText::translate('VBELIMINA'); ?></button>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        if (!$active_operators) {
                            ?>
                            <p class="<?php echo !$operators_list ? 'error' : 'info' ?>"><?php echo JText::translate('VBNOOPERATORS'); ?></p>
                            <?php
                        }
                    }
                    ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        foreach ($permission_types as $tool_id => $tool_data) {
            $tool_permissions = ($tool_data['permissions'] ?? []);
            $tool_settings    = [];
            if ($tool) {
                // in case of a single tool being rendered, push the (protected) parameter to choose the operator ID
                $tool_permissions = array_merge([
                    '_operator_id' => [
                        'type'          => 'select',
                        'label'         => JText::translate('VBOOPERATOR'),
                        'help'          => JText::translate('VBOADDOPERATORPERM'),
                        'assets'        => true,
                        'asset_options' => [
                            'placeholder' => '',
                            'allowClear'  => true,
                        ],
                        // do NOT merge or unshift to keep the associative list on numeric keys, use the array union operator instead!
                        'options' => (['' => ''] + $operators_assoc),
                    ],
                ], $tool_permissions);
            } elseif (($operator['perms'] ?? [])) {
                // in case of a single operator and related tools being rendered, populate the existing permissions for each tool
                foreach ($operator['perms'] as $tool_perms) {
                    if (!strcasecmp($tool_perms['type'], $tool_id)) {
                        // operator-tool permissions found
                        $tool_settings = $tool_perms['perms'] ?: [];
                        break;
                    }
                }
            }
            ?>
            <div class="vbo-operator-tool-permissions vbo-operator-tool-tab-selector" data-tool-id="<?php echo JHtml::fetch('esc_attr', $tool_id); ?>" style="display: none;">
                <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
                    <div class="vbo-params-wrap">
                        <div class="vbo-params-container">
                            <?php echo VBOParamsRendering::getInstance($tool_permissions, $tool_settings)->setInputName('tool_perms[' . $tool_id . ']')->getHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    // save button handler
    function vboHandleSaveToolPermissions(e) {
        // disable the saving button
        let button = e.target;
        if (button.tagName.toLowerCase() === 'i') {
            button = button.parentNode;
        }
        button.disabled = true;

        // find the active tool-tab
        let active_tab = document.querySelector('.vbo-operator-tool-tab.vbo-operator-tool-tab-active');

        if (!active_tab) {
            // enable the button
            button.disabled = false;

            // dismiss the modal
            VBOCore.emitEvent('<?php echo $toggle_event; ?>');

            // abort (nothing to save)
            return false;
        }

        let tool_id = active_tab.getAttribute('data-tool-id');
        let active_params = document.querySelector('.vbo-operator-tool-permissions[data-tool-id="' + (tool_id || '') + '"]');

        if (!tool_id || !active_params) {
            // enable the button
            button.disabled = false;

            // dismiss the modal
            VBOCore.emitEvent('<?php echo $dismiss_event; ?>');

            // abort (nothing to save)
            return false;
        }

        // detect the saving mode, either one tool for various operators,
        // or various tools for one operator, to match mandatory values.
        let manage_mode = '<?php echo $tool ? 'tool_to_operators' : 'tools_to_operator'; ?>';
        let operator_id = null;

        if (manage_mode === 'tool_to_operators') {
            // one tool for various operators

            // make sure the operator ID param was selected
            operator_id = active_params.querySelector('select[name="tool_perms[tableaux][_operator_id]"]')?.value;
            if (!operator_id) {
                // missing operator
                alert(Joomla.JText._('VBO_PLEASE_SELECT'));

                // enable the button
                button.disabled = false;

                // abort
                return false;
            }
        } else {
            // various tools for one operator
            operator_id = '<?php echo $operator_id; ?>';
        }

        // build the request data object
        let permsData = {};

        // scan all permission params to collect the values
        active_params.querySelectorAll('input, select, textarea').forEach((input_el) => {
            // use a regex to get the proper input name, array values will be supported as long as the value is an array
            let first_rx = new RegExp("^tool_perms\\[" + tool_id + "\\]\\[", 'g');
            let input_name = input_el.getAttribute('name')?.replace(first_rx, '')?.replace(/\]?\[?\]$/, '');
            if (!input_name || input_name === '_operator_id') {
                // invalid or protected param field (i.e. select2 helper elements)
                return;
            }

            // get the param value
            let input_value = input_el.value;

            // check if an array, only supported on multiple-select
            if (input_el.tagName.toLowerCase() === 'select' && input_el.multiple) {
                // start an array
                input_value = [];
                // scan all selected (use ":checked" pseudoselector) options
                input_el.querySelectorAll('option:checked').forEach((opt) => {
                    input_value.push(opt.value);
                });
            }

            // set request property and value
            permsData[input_name] = input_value;
        });

        // start loading and make the request
        VBOCore.emitEvent('<?php echo $loading_event; ?>');

        VBOCore.doAjax(
            "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=operators.savePermission'); ?>",
            {
                operator_id: operator_id,
                tool_id: tool_id,
                perms: permsData,
                tmpl: "component",
            },
            (response) => {
                // stop loading
                VBOCore.emitEvent('<?php echo $loading_event; ?>');

                // dismiss the modal
                VBOCore.emitEvent('<?php echo $dismiss_event; ?>');

                // reload the current page
                window.location.reload();
            },
            (error) => {
                // display error
                alert(error.responseText);

                // stop loading
                VBOCore.emitEvent('<?php echo $loading_event; ?>');

                // enable the button
                button.disabled = false;
            }
        );
    }

    jQuery(function() {

        // handle tool tabs switching
        jQuery('.vbo-operator-tool-tab').on('click', function(e) {
            let tool_id = jQuery(this).attr('data-tool-id');
            jQuery('.vbo-operator-tool-tab').removeClass('vbo-operator-tool-tab-active');
            jQuery('.vbo-operator-tool-permissions').hide().removeClass('vbo-operator-tool-permissions-active');
            jQuery(this).addClass('vbo-operator-tool-tab-active');
            jQuery('.vbo-operator-tool-permissions[data-tool-id="' + tool_id + '"]').show().addClass('vbo-operator-tool-permissions-active');
            if (tool_id && <?php echo $tool ? 'true' : 'false'; ?>) {
                // when clicking a tool-tab during the tool permissions management, reset the current operator id and other fields
                if (jQuery(e.target).hasClass('vbo-operator-tool-name')) {
                    // not a JS event triggered, but a real click on this tool-tab
                    let container = jQuery('.vbo-operator-tool-permissions[data-tool-id="' + tool_id + '"]');
                    container.find('select[name="tool_perms[' + tool_id + '][_operator_id]"]').find('option:checked').prop('selected', false);
                    container.find('select[name="tool_perms[' + tool_id + '][_operator_id]"]').trigger('change');
                    // reset additional fields
                    container.find('input, textarea').val('').trigger('change');
                    container.find('select[multiple]').find('option:checked').prop('selected', false);
                    container.find('select[multiple]').trigger('change');
                }
            }
        });

        // handle button to edit the tool permissions for a specific operator
        jQuery('.vbo-oper-permissions-edit-btn').on('click', function() {
            let operator_id = jQuery(this).attr('data-operator-id');
            let tool_id = jQuery(this).attr('data-tool-id');
            let oper_tool_perms_json = jQuery(this).parent().find('input.vbo-operator-tool-permissions-json').val();

            if (!operator_id || !tool_id) {
                return false;
            }

            // attempt to decode the current operator tool permissions
            let oper_tool_perms = {};
            try {
                oper_tool_perms = oper_tool_perms_json ? JSON.parse(oper_tool_perms_json) : {};
            } catch(err) {
                oper_tool_perms = {};
                console.error('could not parse JSON permissions', err, oper_tool_perms_json);
            }

            // scan all tool permission fields
            document.querySelector('.vbo-operator-tool-permissions[data-tool-id="' + tool_id + '"]').querySelectorAll('input, select, textarea').forEach((input_el) => {
                let first_rx = new RegExp("^tool_perms\\[" + tool_id + "\\]\\[", 'g');
                let input_name = input_el.getAttribute('name')?.replace(first_rx, '')?.replace(/\]?\[?\]$/, '');

                if (!input_name) {
                    // invalid param field (i.e. select2 helper elements)
                    return;
                }

                if (input_name === '_operator_id') {
                    // ensure to populate the operator ID
                    (input_el.querySelector('option[value="' + operator_id + '"]') || {}).selected = true;

                    // trigger the element change event
                    input_el.dispatchEvent(new Event('change'));

                    // parse next
                    return;
                }

                if (oper_tool_perms.hasOwnProperty(input_name)) {
                    if (Array.isArray(oper_tool_perms[input_name])) {
                        // multiple values
                        oper_tool_perms[input_name].forEach((cur_value) => {
                            (input_el.querySelector('option[value="' + cur_value + '"]') || {}).selected = true;
                        });
                    } else {
                        // single value
                        input_el.value = oper_tool_perms[input_name];
                    }
                } else {
                    // reset this setting to the initial empty state

                    // check if an array, only supported on multiple-select
                    if (input_el.tagName.toLowerCase() === 'select' && input_el.multiple) {
                        // scan all selected (use ":checked" pseudoselector) options
                        input_el.querySelectorAll('option:checked').forEach((opt) => {
                            // un-select this option
                            opt.selected = false;
                        });
                    } else {
                        input_el.value = '';
                    }
                }

                // trigger the element change event
                input_el.dispatchEvent(new Event('change'));
            });

            // trigger the event to open the requested tool tab
            jQuery('.vbo-operator-tool-tab[data-tool-id="' + tool_id + '"]').trigger('click');
        });

        // handle button to edit the operator permissions for a specific tool
        jQuery('.vbo-tool-permissions-edit-btn').on('click', function() {
            let operator_id = jQuery(this).attr('data-operator-id');
            let tool_id = jQuery(this).attr('data-tool-id');

            // trigger the event to open the requested tool tab
            jQuery('.vbo-operator-tool-tab[data-tool-id="' + tool_id + '"]').trigger('click');

            // we do not really need to inject or populate the fields, because they must have been populated already
        });

        // handle button to remove an operator-tool-permission
        jQuery('.vbo-permissions-del-btn').on('click', function() {
            if (!confirm(Joomla.JText._('VBO_WANT_PROCEED'))) {
                return false;
            }

            let operator_id = jQuery(this).attr('data-operator-id');
            let tool_id = jQuery(this).attr('data-tool-id');

            let element = jQuery(this).closest('.vbo-params-block');

            // start loading and make the request
            VBOCore.emitEvent('<?php echo $loading_event; ?>');

            VBOCore.doAjax(
                "<?php echo VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=operators.removePermission'); ?>",
                {
                    operator_id: operator_id,
                    tool_id: tool_id,
                    tmpl: "component"
                },
                (response) => {
                    // stop loading
                    VBOCore.emitEvent('<?php echo $loading_event; ?>');
                    
                    // delete the element from the DOM
                    element.remove();
                },
                (error) => {
                    console.error(error);
                    alert(error.responseText);
                    // stop loading
                    VBOCore.emitEvent('<?php echo $loading_event; ?>');
                }
            );
        });

        // build footer save button
        const permissionsButtonSave = document.createElement('button');
        permissionsButtonSave.classList.add('btn', 'btn-success');
        permissionsButtonSave.setAttribute('type', 'button');
        permissionsButtonSave.innerHTML = '<?php VikBookingIcons::e('save'); ?> ' + Joomla.JText._('VBSAVE');
        permissionsButtonSave.addEventListener('click', vboHandleSaveToolPermissions);

        // handle modal toggle (show/hide)
        document.addEventListener('<?php echo $toggle_event; ?>', () => {
            // always enable the footer saving button
            permissionsButtonSave.disabled = false;

            // render modal
            let modalBody = VBOCore.displayModal({
                suffix: 'operator_permissions_modal',
                extra_class: 'vbo-modal-rounded vbo-modal-tall',
                title: Joomla.JText._('VBOPERMSOPERATORS'),
                body_prepend: true,
                lock_scroll: true,
                footer_right: permissionsButtonSave,
                loading_event: '<?php echo $loading_event; ?>',
                dismiss_event: '<?php echo $dismiss_event; ?>',
                onDismiss: () => {
                    // move modal content back
                    jQuery('.vbo-operator-tool-permissions-wrap').appendTo(jQuery('.vbo-operator-tool-permissions-container'));
                }
            });

            // set modal content
            jQuery('.vbo-operator-tool-permissions-wrap').appendTo(modalBody);
        });

    });
</script>
