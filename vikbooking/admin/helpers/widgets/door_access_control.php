<?php
/**
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "Door Access Control".
 * 
 * @since  1.18.4 (J) - 1.8.4 (WP)
 */
class VikBookingAdminWidgetDoorAccessControl extends VikBookingAdminWidget
{
    /**
     * The instance counter of this widget. Since we do not load individual parameters
     * for each widget's instance, we use a static counter to determine its settings.
     *
     * @var     int
     */
    protected static $instance_counter = -1;

    /**
     * @inheritDOc
     */
    public function __construct()
    {
        // call parent constructor
        parent::__construct();

        $this->widgetName = JText::translate('VBO_W_DOORACCESSCONTROL_TITLE');
        $this->widgetDescr = JText::translate('VBO_W_DOORACCESSCONTROL_DESCR');
        $this->widgetId = basename(__FILE__, '.php');

        $this->widgetIcon = '<i class="' . VikBookingIcons::i('door-open') . '"></i>';
        $this->widgetStyleName = 'orange';

        // load widget's settings
        $this->widgetSettings = (array) $this->loadSettings();
    }

    /**
     * @inheritDoc
     */
    public function preflight()
    {
        if (!JFactory::getUser()->authorise('core.vbo.pms', 'com_vikbooking')) {
            // insufficient user capabilities
            return false;
        }

        return parent::preflight();
    }

    /**
     * @inheritDoc
     */
    public function preload()
    {
        // load assets
        $this->vbo_app->loadSelect2();
    }

    /**
     * @inheritDoc
     */
    public function getWidgetDetails()
    {
        // get common widget details from parent abstract class
        $details = parent::getWidgetDetails();

        // append the modal rendering information
        $details['modal'] = [
            'add_class' => 'vbo-modal-nopadding vbo-modal-large',
        ];

        return $details;
    }

    /**
     * @inheritDoc
     */
    public function render(?VBOMultitaskData $data = null)
    {
        // increase widget's instance counter
        static::$instance_counter++;

        // check whether the widget is being rendered via AJAX when adding it through the customizer
        $is_ajax = $this->isAjaxRendering();

        // generate a unique ID for the sticky notes wrapper instance
        $wrapper_instance = !$is_ajax ? static::$instance_counter : rand();
        $wrapper_id = 'vbo-widget-door-access-control-' . $wrapper_instance;

        // default provider, profile and device values (profile and device start from null)
        $defaultProvider = $this->widgetSettings['id_provider'] ?? null;
        $defaultProfile  = null;
        $defaultDevice   = null;
        $defaultTab      = null;

        // check for multitask data values
        $js_intvals_id = '';
        if ($data) {
            // access Multitask data
            if ($data->isModalRendering()) {
                // get modal JS identifier
                $js_intvals_id = $data->getModalJsIdentifier();
            }
            // check if provider, profile, device or tab values were set
            $defaultProvider = $this->getOption('provider') ?: $data->get('provider') ?: $defaultProvider;
            $defaultProfile  = $this->getOption('profile') ?: $data->get('profile') ?: $defaultProfile;
            $defaultDevice   = $this->getOption('device') ?: $data->get('device') ?: $defaultDevice;
            $defaultTab      = $this->getOption('tab') ?: $data->get('tab') ?: $defaultTab;
        }

        // access the door access factory object
        $factory = VBOFactory::getDoorAccessControl();

        // load the list of available integration providers
        $providers = $factory->getIntegrationProviders($assoc = true);

        if (!$providers) {
            // abort
            return;
        }

        ?>
        <div id="<?php echo $wrapper_id; ?>" class="vbo-admin-widget-wrapper" data-instance="<?php echo $wrapper_instance; ?>">
            <div class="vbo-admin-widget-head">
                <div class="vbo-admin-widget-head-inline">
                    <h4><?php echo $this->widgetIcon; ?> <span><?php echo $this->widgetName; ?></span></h4>
                    <div class="vbo-admin-widget-head-commands">
                        <span class="vbo-admin-widget-loading-entry"></span>
                        <select class="vbo-door-access-control-setting" data-setting="id_provider">
                            <option value="">- <?php echo JText::translate('VBO_PROVIDER'); ?></option>
                        <?php
                        foreach ($providers as $providerAlias => $providerName) {
                            ?>
                            <option value="<?php echo $providerAlias; ?>"<?php echo $providerAlias === $defaultProvider ? ' selected="selected"' : ''; ?>><?php echo $providerName; ?></option>
                            <?php
                        }
                        ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="vbo-widget-door-access-control-wrap"></div>
            <div class="vbo-widget-dac-add-listing-helper" style="display: none;">
                <div class="vbo-widget-dac-add-listing-wrap">
                    <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
                        <div class="vbo-params-wrap">
                            <div class="vbo-params-container">
                                <div class="vbo-params-block">
                                    <div class="vbo-param-container">
                                        <div class="vbo-param-setting">
                                        <?php
                                        echo VikBooking::getVboApplication()->renderElementsDropDown([
                                            'elements'    => 'listings',
                                            'subunits'    => [
                                                'entire_listing' => true,
                                                'value_format'   => '%d-%d',
                                            ],
                                            'placeholder' => JText::translate('VBO_LISTING'),
                                            'attributes'  => [
                                                'class' => 'vbo-door-access-control-add-listing-id',
                                            ],
                                        ]);
                                        ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="vbo-widget-dac-cap-params-helper" style="display: none;">
                <div class="vbo-widget-dac-cap-params-wrap">
                    <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
                        <div class="vbo-params-wrap">
                            <div class="vbo-params-container">
                                <div class="vbo-params-block"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        if (static::$instance_counter === 0 || $is_ajax) {
            /**
             * JavaScript code that should be declared only once per
             * widget instance, unless we are within AJAX rendering.
             */
            ?>
        <script>
            /**
             * Toggle loading animation state.
             */
            function vboWidgetDACSetLoading(wrapper_id, state) {
                let widget_wrapper = document.getElementById(wrapper_id);

                if (!widget_wrapper) {
                    throw new Error('Widget instance not found');
                }

                let loadingEl = widget_wrapper.querySelector('.vbo-admin-widget-loading-entry');

                if (state) {
                    loadingEl.innerHTML = '<?php VikBookingIcons::e('circle-notch', 'fa-spin fa-fw'); ?>';
                } else {
                    loadingEl.innerHTML = '';
                }
            }

            /**
             * Register function for loading the provider settings.
             */
            function vboWidgetDACLoadProvider(wrapper_id, id_provider, id_profile, tab_redirect, id_device) {
                let widget_element = document.getElementById(wrapper_id);

                if (!widget_element) {
                    throw new Error('Widget instance not found');
                }

                let widget_wrapper = widget_element.querySelector('.vbo-widget-door-access-control-wrap');

                // empty the current widget body
                widget_wrapper.innerHTML = '';

                if (!id_provider) {
                    // abort in case of empty provider selection
                    return;
                }

                // gather options, if any
                let options = vbo_widget_dac_options_oo || {};
                if (vbo_widget_dac_options_oo) {
                    // multitask data options should be used only once ("oo")
                    vbo_widget_dac_options_oo = null;
                }

                // start loading animation
                vboWidgetDACSetLoading(wrapper_id, true);

                // the widget method to call
                let call_method = 'loadProviderSettings';

                // make a request to load the selected provider
                VBOCore.doAjax(
                    "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                    {
                        widget_id: "<?php echo $this->getIdentifier(); ?>",
                        call:      call_method,
                        return:    1,
                        provider:  id_provider,
                        profile:   id_profile,
                        tab:       tab_redirect,
                        id_device: id_device,
                        _options:  options,
                        wrapper:   wrapper_id,
                        tmpl:      "component"
                    },
                    (response) => {
                        try {
                            let obj_res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (!obj_res.hasOwnProperty(call_method)) {
                                console.error('Unexpected JSON response', obj_res);
                                return false;
                            }

                            // stop loading animation
                            vboWidgetDACSetLoading(wrapper_id, false);

                            // set response HTML body
                            jQuery(widget_wrapper).html(obj_res[call_method]['html']);

                            // check if retry-data options were set
                            if (options?.retry_data?.callback) {
                                // trigger click on device capability for retry
                                setTimeout(() => {
                                    let deviceCap = widget_wrapper.querySelector('.vbo-dac-device-capability-execute-btn[data-callback="' + options.retry_data.callback + '"]');
                                    if (deviceCap) {
                                        // inject the retry-data options, if any
                                        vbo_widget_dac_retry_data_oo = options?.retry_data?.options || null;
                                        // trigger click on capability
                                        deviceCap.click();
                                    }
                                }, 100);
                            }
                        } catch(err) {
                            // log and display error
                            console.error('could not parse JSON response', err, response);
                            alert('could not parse JSON response');
                        }
                    },
                    (error) => {
                        // log and display error
                        console.error(error);
                        alert(error.responseText);

                        // stop loading animation
                        vboWidgetDACSetLoading(wrapper_id, false);
                    }
                );
            }

            /**
             * Register function for saving the integration profile settings.
             */
            function vboWidgetDACSaveProfileSettings(wrapper_id) {
                let widget_element = document.getElementById(wrapper_id);

                if (!widget_element) {
                    throw new Error('Widget instance not found');
                }

                // gather profile record information
                let profileData = {};
                widget_element.querySelectorAll('.vbo-door-access-control-setting[data-setting]').forEach((el) => {
                    let settingType = el.getAttribute('data-setting');
                    profileData[settingType] = el.value;
                });

                if (!profileData.id_provider) {
                    throw new Error('Could not find provider alias identifier.');
                }

                if (!profileData.gentype) {
                    alert(<?php echo json_encode('Please select: ' . JText::translate('VBO_PASSCODE_GENERATION')); ?>);
                    return false;
                }

                // gather provider integration settings
                let providerSettings = {};
                widget_element.querySelectorAll('[name*="integration[' + profileData.id_provider + ']"]').forEach((settingEl) => {
                    let settingName = settingEl.getAttribute('name');
                    let reSafe = (profileData.id_provider + '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    let reFull = new RegExp(`^integration\\[${reSafe}\\]`);
                    let paramName = settingName.replace(reFull, '').replace(/^\[|\]$/g, '');
                    if (settingEl.matches('select') && settingEl.getAttribute('multiple')) {
                        // array-value element
                        let selectedValues = Array.from(settingEl.options).filter((option) => option.selected).map((option) => option.value);
                        providerSettings[paramName] = selectedValues;
                    } else if (settingEl.matches('input[type="checkbox"]')) {
                        // ensure it's checked
                        providerSettings[paramName] = settingEl.checked ? 1 : 0;
                    } else {
                        // single-value element
                        providerSettings[paramName] = settingEl.value;
                    }
                });

                // start loading animation
                vboWidgetDACSetLoading(wrapper_id, true);

                // the widget method to call
                let call_method = 'saveProviderSettings';

                // make a request to load the selected provider
                VBOCore.doAjax(
                    "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                    {
                        widget_id: "<?php echo $this->getIdentifier(); ?>",
                        call:      call_method,
                        return:    1,
                        profile:   profileData,
                        settings:  providerSettings,
                        wrapper:   wrapper_id,
                        tmpl:      "component"
                    },
                    (response) => {
                        try {
                            let obj_res = typeof response === 'string' ? JSON.parse(response) : response;
                            if (!obj_res.hasOwnProperty(call_method)) {
                                console.error('Unexpected JSON response', obj_res);
                                return false;
                            }

                            // stop loading animation
                            vboWidgetDACSetLoading(wrapper_id, false);

                            // reload provider settings
                            vboWidgetDACLoadProvider(wrapper_id, profileData.id_provider, obj_res[call_method]['id_profile'] || 0);
                        } catch(err) {
                            // log and display error
                            console.error('could not parse JSON response', err, response);
                            alert('could not parse JSON response');
                        }
                    },
                    (error) => {
                        // log and display error
                        console.error(error);
                        alert(error.responseText);

                        // stop loading animation
                        vboWidgetDACSetLoading(wrapper_id, false);
                    }
                );
            }

            /**
             * Register function for changing the generation type setting.
             */
            function vboWidgetDACSwitchGenType(wrapper_id) {
                let widget_element = document.getElementById(wrapper_id);

                if (!widget_element) {
                    throw new Error('Widget instance not found');
                }

                const gentypeVal = widget_element.querySelector('.vbo-door-access-control-setting[data-setting="gentype"]').value;
                const genperiodEl = widget_element.querySelector('.vbo-door-access-control-setting[data-setting="genperiod"]');

                if (gentypeVal == 'checkin') {
                    genperiodEl.style.display = '';
                } else {
                    genperiodEl.style.display = 'none';
                }
            }

            /**
             * Register function for changing profile record ID.
             */
            function vboWidgetDACSwitchProfileSettings(wrapper_id) {
                let widget_element = document.getElementById(wrapper_id);

                if (!widget_element) {
                    throw new Error('Widget instance not found');
                }

                let id_profile_el = widget_element.querySelector('.vbo-door-access-control-setting[data-setting="id_profile"]');
                if (!id_profile_el) {
                    throw new Error('Could not find id profile element');
                }

                let id_provider_el = widget_element.querySelector('.vbo-door-access-control-setting[data-setting="id_provider"]');
                if (!id_provider_el) {
                    throw new Error('Could not find id provider element');
                }

                let id_profile = id_profile_el.value;
                let id_provider = id_provider_el.value;

                if (id_profile == '-1') {
                    // create new profile
                    widget_element.querySelector('.vbo-door-access-control-setting[data-setting="profile_name"]').value = '';
                    widget_element.querySelector('.vbo-door-access-control-setting[data-setting="gentype"]').value = '';

                    // make most of the currently displayed settings empty when asking to create a new profile
                    widget_element.querySelectorAll('[name*="integration[' + id_provider + ']"]').forEach((settingEl) => {
                        if (!settingEl.matches('select') || !settingEl.getAttribute('multiple')) {
                            // single-value element
                            settingEl.value = '';
                        }
                    });

                    // abort
                    return;
                }

                // reload settings for the selected and existing profile ID
                vboWidgetDACLoadProvider(wrapper_id, id_provider, id_profile);
            }

            /**
             * Register function for changing device ID.
             */
            function vboWidgetDACSwitchDeviceID(wrapper_id) {
                let widget_element = document.getElementById(wrapper_id);

                if (!widget_element) {
                    throw new Error('Widget instance not found');
                }

                let id_profile_el = widget_element.querySelector('.vbo-door-access-control-setting[data-setting="id_profile"]');
                if (!id_profile_el) {
                    throw new Error('Could not find id profile element');
                }

                let id_provider_el = widget_element.querySelector('.vbo-door-access-control-setting[data-setting="id_provider"]');
                if (!id_provider_el) {
                    throw new Error('Could not find id provider element');
                }

                let id_device_el = widget_element.querySelector('.vbo-door-access-control-device-id');
                if (!id_device_el) {
                    throw new Error('Could not find id device element');
                }

                let id_device = id_device_el.value;
                let id_profile = id_profile_el.value;
                let id_provider = id_provider_el.value;

                if (!id_device) {
                    // display message to choose a device from the list
                    widget_element.querySelector('.vbo-dac-capabilities-list').innerHTML = '<p class="info">' + <?php echo json_encode(JText::translate('VBO_CHOOSE_DEVICE')); ?> + '</p>';

                    // abort
                    return;
                }

                // reload widget with the selected device
                vboWidgetDACLoadProvider(wrapper_id, id_provider, id_profile, 'dashboard', id_device);
            }

            /**
             * Register function for handling a global body click event delegation.
             */
            function vbo_w_dac_click_delegation(e) {
                // tabs-panels switching
                if (e.target.matches('.vbo-widget-door-access-control-tab') || e.target.closest('.vbo-widget-door-access-control-tab')) {
                    const tabEl = !e.target.matches('.vbo-widget-door-access-control-tab') ? e.target.closest('.vbo-widget-door-access-control-tab') : e.target;
                    const tabType = tabEl.getAttribute('data-type');
                    const content = tabEl.closest('.vbo-widget-door-access-control-content');
                    content.querySelectorAll('.vbo-widget-door-access-control-panel[data-type]').forEach((panel) => {
                        const panelType = panel.getAttribute('data-type');
                        if (panelType === tabType) {
                            // show panel
                            panel.style.display = '';
                            tabEl.classList.add('vbo-widget-tab-active');
                        } else {
                            // hide panel
                            panel.style.display = 'none';
                            let controlTab = content.querySelector('.vbo-widget-door-access-control-tab[data-type="' + panelType + '"]');
                            if (controlTab) {
                                controlTab.classList.remove('vbo-widget-tab-active');
                            }
                        }
                    });

                    // do not proceed
                    return;
                }

                // update devices button
                if (e.target.matches('.vbo-widget-dac-update-devices') || e.target.closest('.vbo-widget-dac-update-devices')) {
                    const buttonEl = !e.target.matches('.vbo-widget-dac-update-devices') ? e.target.closest('.vbo-widget-dac-update-devices') : e.target;
                    const wrapper_id = buttonEl.closest('.vbo-admin-widget-wrapper').getAttribute('id');
                    const id_provider = document.getElementById(wrapper_id).querySelector('.vbo-door-access-control-setting[data-setting="id_provider"]').value;
                    const id_profile = document.getElementById(wrapper_id).querySelector('.vbo-door-access-control-setting[data-setting="id_profile"]').value;

                    // disable button
                    buttonEl.disabled = true;

                    // change button icon for loading
                    buttonEl.querySelector('i').setAttribute('class', '<?php echo VikBookingIcons::i('sync', 'fa-spin fa-fw'); ?>');

                    // start loading animation
                    vboWidgetDACSetLoading(wrapper_id, true);

                    // the widget method to call
                    let call_method = 'updateProviderDevices';

                    // make a request to update the provider devices
                    VBOCore.doAjax(
                        "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                        {
                            widget_id: "<?php echo $this->getIdentifier(); ?>",
                            call:      call_method,
                            return:    1,
                            provider:  id_provider,
                            profile:   id_profile,
                            wrapper:   wrapper_id,
                            tmpl:      "component"
                        },
                        (response) => {
                            // stop loading animation
                            vboWidgetDACSetLoading(wrapper_id, false);

                            // change button icon for non-loading
                            buttonEl.querySelector('i').setAttribute('class', '<?php echo VikBookingIcons::i('sync'); ?>');

                            try {
                                let obj_res = typeof response === 'string' ? JSON.parse(response) : response;
                                if (!obj_res.hasOwnProperty(call_method)) {
                                    console.error('Unexpected JSON response', obj_res);
                                    return false;
                                }

                                if (obj_res[call_method]['tot_devices']) {
                                    // reload the provider with the new data updated
                                    vboWidgetDACLoadProvider(wrapper_id, id_provider, id_profile, 'devices');
                                } else {
                                    // re-enable button
                                    buttonEl.disabled = false;
                                }
                            } catch(err) {
                                // log and display error
                                console.error('could not parse JSON response', err, response);
                                alert('could not parse JSON response');

                                // re-enable button
                                buttonEl.disabled = false;
                            }
                        },
                        (error) => {
                            // log and display error
                            console.error(error);
                            alert(error.responseText);

                            // change button icon for non-loading
                            buttonEl.querySelector('i').setAttribute('class', '<?php echo VikBookingIcons::i('sync'); ?>');

                            // stop loading animation
                            vboWidgetDACSetLoading(wrapper_id, false);

                            // re-enable button
                            buttonEl.disabled = false;
                        }
                    );

                    // do not proceed
                    return;
                }

                // add device connected listing button
                if (e.target.matches('.vbo-widget-dac-device-add-listing') || e.target.closest('.vbo-widget-dac-device-add-listing')) {
                    const buttonEl = !e.target.matches('.vbo-widget-dac-device-add-listing') ? e.target.closest('.vbo-widget-dac-device-add-listing') : e.target;
                    const wrapper_id = buttonEl.closest('.vbo-admin-widget-wrapper').getAttribute('id');
                    const wrapper_el = document.getElementById(wrapper_id);
                    const id_provider = wrapper_el.querySelector('.vbo-door-access-control-setting[data-setting="id_provider"]').value;
                    const id_profile = wrapper_el.querySelector('.vbo-door-access-control-setting[data-setting="id_profile"]').value;
                    const id_device = buttonEl.closest('.vbo-widget-dac-device-listings-data').getAttribute('data-device-id');
                    const device_name = buttonEl.closest('.vbo-widget-dac-device-listings-data').getAttribute('data-device-name');
                    const add_listing_section = wrapper_el.querySelector('.vbo-widget-dac-add-listing-wrap');
                    const add_listing_select = wrapper_el.querySelector('select.vbo-door-access-control-add-listing-id');

                    // build modal buttons
                    let cancel_btn = document.createElement('button');
                    cancel_btn.setAttribute('type', 'button');
                    cancel_btn.classList.add('btn');
                    cancel_btn.innerText = <?php echo json_encode(JText::translate('VBANNULLA')); ?>;
                    cancel_btn.addEventListener('click', () => {
                        VBOCore.emitEvent('wdac-assign-listing-dismiss');
                    });

                    let apply_btn = document.createElement('button');
                    apply_btn.setAttribute('type', 'button');
                    apply_btn.classList.add('btn', 'btn-success');
                    apply_btn.innerText = <?php echo json_encode(JText::translate('VBAPPLY')); ?>;
                    apply_btn.addEventListener('click', (e) => {
                        // save the new device-listing connection
                        const listingId = add_listing_select.value;

                        // check if we have to connect a specific sub-unit index
                        let listingSubUnit = null;
                        let idParts = (listingId + '').split('-');
                        if (idParts[1]) {
                            listingSubUnit = idParts[1];
                        }

                        // disable button
                        e.target.disabled = true;

                        // start loading animation
                        VBOCore.emitEvent('wdac-assign-listing-loading');

                        // the widget method to call
                        let call_method = 'setDeviceListingConnection';

                        // make a request to set the device listing connection
                        VBOCore.doAjax(
                            "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                            {
                                widget_id: "<?php echo $this->getIdentifier(); ?>",
                                call:      call_method,
                                return:    1,
                                provider:  id_provider,
                                profile:   id_profile,
                                device:    id_device,
                                listing:   listingId,
                                subunit:   listingSubUnit,
                                wrapper:   wrapper_id,
                                tmpl:      "component"
                            },
                            (response) => {
                                // stop loading animation
                                VBOCore.emitEvent('wdac-assign-listing-loading');

                                try {
                                    let obj_res = typeof response === 'string' ? JSON.parse(response) : response;
                                    if (!obj_res.hasOwnProperty(call_method)) {
                                        console.error('Unexpected JSON response', obj_res);
                                        return false;
                                    }

                                    if (obj_res[call_method]['device_name']) {
                                        // reload the provider with the new data updated
                                        vboWidgetDACLoadProvider(wrapper_id, id_provider, id_profile, 'devices');

                                        // reset listing selection
                                        add_listing_select.value = '';
                                        add_listing_select.dispatchEvent(new Event('change'));

                                        // dismiss the modal
                                        VBOCore.emitEvent('wdac-assign-listing-dismiss');
                                    } else {
                                        // re-enable button
                                        e.target.disabled = false;
                                    }
                                } catch(err) {
                                    // log and display error
                                    console.error('could not parse JSON response', err, response);
                                    alert('could not parse JSON response');

                                    // re-enable button
                                    e.target.disabled = false;
                                }
                            },
                            (error) => {
                                // log and display error
                                console.error(error);
                                alert(error.responseText);

                                // stop loading animation
                                VBOCore.emitEvent('wdac-assign-listing-loading');

                                // re-enable button
                                e.target.disabled = false;
                            }
                        );
                    });

                    // display modal
                    let modalBody = VBOCore.displayModal({
                        suffix: 'wdac-assign-listing',
                        extra_class: 'vbo-modal-rounded vbo-modal-prompt',
                        title: device_name + ' - ' + <?php echo json_encode(JText::translate('VBO_CONNECTED_LISTINGS')); ?>,
                        body_prepend: true,
                        draggable: true,
                        footer_left: cancel_btn,
                        footer_right: apply_btn,
                        loading_event: 'wdac-assign-listing-loading',
                        dismiss_event: 'wdac-assign-listing-dismiss',
                        onDismiss: () => {
                            // move back the helper section
                            wrapper_el.querySelector('.vbo-widget-dac-add-listing-helper').append(add_listing_section);
                        },
                    });

                    // append helper to modal body
                    (modalBody[0] || modalBody).append(add_listing_section);

                    // do not proceed
                    return;
                }

                // delete device connected listing button
                if (e.target.matches('.vbo-widget-dac-device-cn-listing') || e.target.closest('.vbo-widget-dac-device-cn-listing')) {
                    const buttonEl = !e.target.matches('.vbo-widget-dac-device-cn-listing') ? e.target.closest('.vbo-widget-dac-device-cn-listing') : e.target;
                    const wrapper_id = buttonEl.closest('.vbo-admin-widget-wrapper').getAttribute('id');
                    const wrapper_el = document.getElementById(wrapper_id);
                    const id_provider = wrapper_el.querySelector('.vbo-door-access-control-setting[data-setting="id_provider"]').value;
                    const id_profile = wrapper_el.querySelector('.vbo-door-access-control-setting[data-setting="id_profile"]').value;
                    const id_device = buttonEl.closest('.vbo-widget-dac-device-listings-data').getAttribute('data-device-id');
                    const listingId = buttonEl.getAttribute('data-listing-id');
                    const subunitId = buttonEl.getAttribute('data-subunit-id') || 0;

                    if (confirm(<?php echo json_encode(JText::translate('VBDELCONFIRM')); ?>)) {
                        // start loading animation
                        vboWidgetDACSetLoading(wrapper_id, true);

                        // the widget method to call
                        let call_method = 'unsetDeviceListingConnection';

                        // make a request to unset the device listing connection
                        VBOCore.doAjax(
                            "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                            {
                                widget_id: "<?php echo $this->getIdentifier(); ?>",
                                call:      call_method,
                                return:    1,
                                provider:  id_provider,
                                profile:   id_profile,
                                device:    id_device,
                                listing:   listingId,
                                subunit:   subunitId,
                                wrapper:   wrapper_id,
                                tmpl:      "component"
                            },
                            (response) => {
                                // stop loading animation
                                vboWidgetDACSetLoading(wrapper_id, false);

                                try {
                                    let obj_res = typeof response === 'string' ? JSON.parse(response) : response;
                                    if (!obj_res.hasOwnProperty(call_method)) {
                                        console.error('Unexpected JSON response', obj_res);
                                        return false;
                                    }

                                    // delete element
                                    buttonEl.remove();

                                    // reload the provider with the new data updated
                                    vboWidgetDACLoadProvider(wrapper_id, id_provider, id_profile, 'devices');
                                } catch(err) {
                                    // log and display error
                                    console.error('could not parse JSON response', err, response);
                                    alert('could not parse JSON response');
                                }
                            },
                            (error) => {
                                // log and display error
                                console.error(error);
                                alert(error.responseText);

                                // stop loading animation
                                vboWidgetDACSetLoading(wrapper_id, false);
                            }
                        );
                    }

                    // do not proceed
                    return;
                }

                // execute device capability button
                if (e.target.matches('.vbo-dac-device-capability-execute-btn') || e.target.closest('.vbo-dac-device-capability-execute-btn')) {
                    const buttonEl = !e.target.matches('.vbo-dac-device-capability-execute-btn') ? e.target.closest('.vbo-dac-device-capability-execute-btn') : e.target;
                    const wrapper_id = buttonEl.closest('.vbo-admin-widget-wrapper').getAttribute('id');
                    const wrapper_el = document.getElementById(wrapper_id);
                    const id_provider = wrapper_el.querySelector('.vbo-door-access-control-setting[data-setting="id_provider"]').value;
                    const id_profile = wrapper_el.querySelector('.vbo-door-access-control-setting[data-setting="id_profile"]').value;
                    const device_el = wrapper_el.querySelector('.vbo-door-access-control-device-id');
                    const id_device = device_el.value;
                    const device_name = device_el.options[device_el.selectedIndex].text || '';
                    const id_capability = buttonEl.getAttribute('data-cap-id');
                    const capabilityNameEl = buttonEl.querySelector('.vbo-dac-device-capability-btn-name');
                    const capabilityName = capabilityNameEl.innerText;
                    const capabilityParamsHelper = wrapper_el.querySelector('.vbo-widget-dac-cap-params-wrap');
                    const capabilityParamsContent = capabilityParamsHelper.querySelector('.vbo-params-block');

                    // disable button to prevent double clicks
                    buttonEl.disabled = true;

                    // check if we have retry-data options to be used only once ("oo")
                    let retry_data_options = vbo_widget_dac_retry_data_oo;
                    if (vbo_widget_dac_retry_data_oo) {
                        vbo_widget_dac_retry_data_oo = null;
                    }

                    // start button animation
                    capabilityNameEl.innerHTML = '<?php VikBookingIcons::e('sync', 'fa-spin fa-fw'); ?>';

                    // the widget method to call
                    let call_method = 'executeDeviceCapability';

                    // make a request to execute the device capability
                    VBOCore.doAjax(
                        "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                        {
                            widget_id:  "<?php echo $this->getIdentifier(); ?>",
                            call:       call_method,
                            return:     1,
                            provider:   id_provider,
                            profile:    id_profile,
                            device:     id_device,
                            capability: id_capability,
                            noparams:   1,
                            retry_data: retry_data_options,
                            wrapper:    wrapper_id,
                            tmpl:       "component"
                        },
                        (response) => {
                            // stop button animation
                            capabilityNameEl.innerHTML = '';
                            capabilityNameEl.innerText = capabilityName;

                            // re-enable button
                            buttonEl.disabled = false;

                            // init params modal body
                            var paramsModalBody;

                            try {
                                let obj_res = typeof response === 'string' ? JSON.parse(response) : response;
                                if (!obj_res.hasOwnProperty(call_method)) {
                                    console.error('Unexpected JSON response', obj_res);
                                    return false;
                                }

                                if (obj_res[call_method]['result_html']) {
                                    // capability was executed, render the HTML result

                                    // set capability name
                                    wrapper_el.querySelector('.vbo-dac-capability-result-name').innerText = capabilityName;

                                    // set capability result
                                    wrapper_el.querySelector('.vbo-dac-capability-result-body').innerHTML = obj_res[call_method]['result_html'];

                                    // switch visible section
                                    wrapper_el.querySelector('.vbo-dac-capabilities-list').classList.remove('vbo-dac-cap-section-active');
                                    wrapper_el.querySelector('.vbo-dac-capability-result-wrap').classList.add('vbo-dac-cap-section-active');
                                    wrapper_el.querySelector('.vbo-dac-device-capability-sections').classList.add('vbo-dac-cap-section-active-result');

                                    if (obj_res[call_method]['device_updated']) {
                                        // set data-attribute flag to navigation back button
                                        let backBtn = wrapper_el.querySelector('.vbo-dac-capability-result-back');
                                        if (backBtn) {
                                            backBtn.setAttribute('data-device-updated', 1);
                                        }
                                    }
                                } else if (obj_res[call_method]['capability_params']) {
                                    // capability execution provides parameters
                                    let cancel_btn = document.createElement('button');
                                    cancel_btn.setAttribute('type', 'button');
                                    cancel_btn.classList.add('btn');
                                    cancel_btn.innerText = <?php echo json_encode(JText::translate('VBANNULLA')); ?>;
                                    cancel_btn.addEventListener('click', () => {
                                        VBOCore.emitEvent('wdac-capability-params-dismiss');
                                    });

                                    let apply_btn = document.createElement('button');
                                    apply_btn.setAttribute('type', 'button');
                                    apply_btn.classList.add('btn', 'btn-primary');
                                    apply_btn.innerText = capabilityName;
                                    apply_btn.addEventListener('click', () => {
                                        // modal params start loading animation
                                        VBOCore.emitEvent('wdac-capability-params-loading');

                                        // collect settings from capability parameters
                                        let capSettings = {};
                                        (paramsModalBody[0] || paramsModalBody).querySelectorAll('[name*="capability_settings[' + id_capability + ']"]').forEach((settingEl) => {
                                            let settingName = settingEl.getAttribute('name');
                                            let reSafe = (id_capability + '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                                            let reFull = new RegExp(`^capability_settings\\[${reSafe}\\]`);
                                            let paramName = settingName.replace(reFull, '').replace(/^\[|\]$/g, '');
                                            if (settingEl.matches('select') && settingEl.getAttribute('multiple')) {
                                                // array-value element
                                                let selectedValues = Array.from(settingEl.options).filter((option) => option.selected).map((option) => option.value);
                                                capSettings[paramName] = selectedValues;
                                            } else {
                                                // single-value element
                                                capSettings[paramName] = settingEl.value;
                                            }
                                        });

                                        // make a request to execute the device capability
                                        VBOCore.doAjax(
                                            "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                                            {
                                                widget_id:  "<?php echo $this->getIdentifier(); ?>",
                                                call:       call_method,
                                                return:     1,
                                                provider:   id_provider,
                                                profile:    id_profile,
                                                device:     id_device,
                                                capability: id_capability,
                                                capability_settings: capSettings,
                                                wrapper:    wrapper_id,
                                                tmpl:       "component"
                                            },
                                            (response) => {
                                                // modal params stop loading animation
                                                VBOCore.emitEvent('wdac-capability-params-loading');

                                                // dismiss modal params
                                                VBOCore.emitEvent('wdac-capability-params-dismiss');

                                                try {
                                                    let obj_res = typeof response === 'string' ? JSON.parse(response) : response;
                                                    if (!obj_res.hasOwnProperty(call_method)) {
                                                        console.error('Unexpected JSON response', obj_res);
                                                        return false;
                                                    }

                                                    if (obj_res[call_method]['result_html']) {
                                                        // capability was executed, render the HTML result

                                                        // set capability name
                                                        wrapper_el.querySelector('.vbo-dac-capability-result-name').innerText = capabilityName;

                                                        // set capability result
                                                        wrapper_el.querySelector('.vbo-dac-capability-result-body').innerHTML = obj_res[call_method]['result_html'];

                                                        // switch visible section
                                                        wrapper_el.querySelector('.vbo-dac-capabilities-list').classList.remove('vbo-dac-cap-section-active');
                                                        wrapper_el.querySelector('.vbo-dac-capability-result-wrap').classList.add('vbo-dac-cap-section-active');
                                                        wrapper_el.querySelector('.vbo-dac-device-capability-sections').classList.add('vbo-dac-cap-section-active-result');
                                                    } else {
                                                        // capability was executed, display the result text
                                                        alert(obj_res[call_method]['result_text'] || (capabilityName + ' operation completed'));
                                                    }
                                                } catch(err) {
                                                    // log and display error
                                                    console.error('could not parse JSON response', err, response);
                                                    alert('could not parse JSON response');
                                                }
                                            },
                                            (error) => {
                                                // log and display error
                                                console.error(error);
                                                alert(error.responseText);

                                                // modal params stop loading animation
                                                VBOCore.emitEvent('wdac-capability-params-loading');
                                            }
                                        );
                                    });

                                    // display modal
                                    let paramsModalBody = VBOCore.displayModal({
                                        suffix: 'wdac-capability-params',
                                        extra_class: 'vbo-modal-rounded',
                                        title: capabilityName + ' - ' + device_name,
                                        body_prepend: true,
                                        draggable: true,
                                        footer_left: cancel_btn,
                                        footer_right: apply_btn,
                                        loading_event: 'wdac-capability-params-loading',
                                        dismiss_event: 'wdac-capability-params-dismiss',
                                        onDismiss: () => {
                                            // empty the helper element
                                            capabilityParamsContent.innerHTML = '';
                                            // move back the helper section
                                            wrapper_el.querySelector('.vbo-widget-dac-cap-params-helper').append(capabilityParamsHelper);
                                        },
                                    });

                                    // append helper to modal body
                                    jQuery(capabilityParamsContent).html(obj_res[call_method]['capability_params']);
                                    jQuery(capabilityParamsHelper).appendTo(paramsModalBody);
                                } else {
                                    // capability was executed, display the result text
                                    alert(obj_res[call_method]['result_text'] || (capabilityName + ' operation completed'));

                                    if (obj_res[call_method]['device_updated']) {
                                        // reload widget with the selected device upon device update
                                        vboWidgetDACLoadProvider(wrapper_id, id_provider, id_profile, 'dashboard', id_device);
                                    }
                                }
                            } catch(err) {
                                // log and display error
                                console.error('could not parse JSON response', err, response);
                                alert('could not parse JSON response');
                            }
                        },
                        (error) => {
                            // log and display error
                            console.error(error);
                            alert(error.responseText);

                            // stop button animation
                            capabilityNameEl.innerHTML = '';
                            capabilityNameEl.innerText = capabilityName;

                            // re-enable button
                            buttonEl.disabled = false;
                        }
                    );

                    // do not proceed
                    return;
                }

                // delete profile button
                if (e.target.matches('.vbo-dac-delete-profile-btn') || e.target.closest('.vbo-dac-delete-profile-btn')) {
                    const buttonEl = !e.target.matches('.vbo-dac-delete-profile-btn') ? e.target.closest('.vbo-dac-delete-profile-btn') : e.target;
                    const wrapper_id = buttonEl.closest('.vbo-admin-widget-wrapper').getAttribute('id');
                    const wrapper_el = document.getElementById(wrapper_id);
                    const id_provider = wrapper_el.querySelector('.vbo-door-access-control-setting[data-setting="id_provider"]').value;
                    const id_profile = wrapper_el.querySelector('.vbo-door-access-control-setting[data-setting="id_profile"]').value;

                    if (!id_profile || id_profile == '-1') {
                        // cannot delete an empty profile record ID
                        return;
                    }

                    if (confirm(<?php echo json_encode(JText::translate('VBDELCONFIRM')); ?>)) {
                        // start loading animation and disable button
                        buttonEl.innerHTML = '<?php VikBookingIcons::e('sync', 'fa-spin fa-fw icn-nomargin'); ?>';
                        buttonEl.disabled = true;

                        // make a request
                        VBOCore.doAjax(
                            "<?php echo $this->getExecWidgetAjaxUri(); ?>",
                            {
                                widget_id:  "<?php echo $this->getIdentifier(); ?>",
                                call:       'deleteProfileRecord',
                                return:     1,
                                provider:   id_provider,
                                profile:    id_profile,
                                wrapper:    wrapper_id,
                                tmpl:       "component"
                            },
                            (response) => {
                                // we trust the response was successful and we reload the provider
                                vboWidgetDACLoadProvider(wrapper_id, id_provider, id_profile);
                            },
                            (error) => {
                                // log and display error
                                console.error(error);
                                alert(error.responseText);

                                // stop loading animation and re-enable button
                                buttonEl.innerHTML = '<?php VikBookingIcons::e('trash'); ?>';
                                buttonEl.disabled = false;
                            }
                        );
                    }

                    // do not proceed
                    return;
                }

                // execute device capability back button
                if (e.target.matches('.vbo-dac-capability-result-back') || e.target.closest('.vbo-dac-capability-result-back')) {
                    const buttonEl = !e.target.matches('.vbo-dac-capability-result-back') ? e.target.closest('.vbo-dac-capability-result-back') : e.target;
                    const wrapper_id = buttonEl.closest('.vbo-admin-widget-wrapper').getAttribute('id');
                    const wrapper_el = document.getElementById(wrapper_id);

                    // switch visible section
                    wrapper_el.querySelector('.vbo-dac-capability-result-wrap').classList.remove('vbo-dac-cap-section-active');
                    wrapper_el.querySelector('.vbo-dac-capabilities-list').classList.add('vbo-dac-cap-section-active');
                    wrapper_el.querySelector('.vbo-dac-device-capability-sections').classList.remove('vbo-dac-cap-section-active-result');

                    // empty capability name
                    wrapper_el.querySelector('.vbo-dac-capability-result-name').innerText = '';

                    // empty result
                    wrapper_el.querySelector('.vbo-dac-capability-result-body').innerHTML = '';

                    // check if device was updated
                    if (buttonEl.getAttribute('data-device-updated') == '1') {
                        // unset data-attribute
                        buttonEl.setAttribute('data-device-updated', 0);
                        // reload widget with the selected device upon device update
                        vboWidgetDACLoadProvider(wrapper_id, buttonEl.getAttribute('data-provider-id'), buttonEl.getAttribute('data-profile-id'), 'dashboard', buttonEl.getAttribute('data-device-id'));
                    }

                    // do not proceed
                    return;
                }
            }
        </script>
            <?php
        }
        ?>
        <script>
            // store widget options, if any
            var vbo_widget_dac_options_oo = <?php echo json_encode($this->getOptions()); ?>;

            // start container for retry-data options
            var vbo_widget_dac_retry_data_oo = null;

            VBOCore.DOMLoaded(() => {
                let widget_wrapper = document.querySelector('#<?php echo $wrapper_id; ?>');

                /**
                 * Add event listener for the provider alias selection.
                 */
                widget_wrapper
                    .querySelector('.vbo-door-access-control-setting[data-setting="id_provider"]')
                    .addEventListener('change', (e) => {
                        vboWidgetDACLoadProvider('<?php echo $wrapper_id; ?>', e.target.value);
                    });

                /**
                 * Add body click event delegation for elements that will be later added to the DOM.
                 */
                if (!VBOCore.wasEventDelegated('dac.body')) {
                    document.body.addEventListener('click', vbo_w_dac_click_delegation);
                    VBOCore.setEventDelegated('dac.body');
                }

            <?php
            if ($js_intvals_id) {
                // widget can be dismissed through the modal
                ?>
                document.addEventListener(VBOCore.widget_modal_dismissed + '<?php echo $js_intvals_id; ?>', (e) => {
                    // remove body click events delegation for this widget
                    document.body.removeEventListener('click', vbo_w_dac_click_delegation);
                    VBOCore.unsetEventDelegated('dac.body');
                });
                <?php
            }

            if (!empty($defaultProvider)) {
                // trigger instant loading upon document ready
                ?>
                vboWidgetDACLoadProvider('<?php echo $wrapper_id; ?>', '<?php echo $defaultProvider; ?>', '<?php echo $defaultProfile; ?>', '<?php echo $defaultTab; ?>', '<?php echo $defaultDevice; ?>');
                <?php
            }
            ?>
            });
        </script>
        <?php
    }

    /**
     * Custom method for this widget only.
     * Loads the settings for the given provider alias.
     * 
     * @return  array
     */
    public function loadProviderSettings()
    {
        $app = JFactory::getApplication();

        $wrapper = $app->input->getString('wrapper', '');
        $provider = $app->input->getString('provider', '');
        $profile = $app->input->getUInt('profile', 0);
        $id_device = $app->input->getString('id_device', '');
        $tab = $app->input->getString('tab', '');

        if (empty($provider)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control provider identifier.');
        }

        // access the door access factory object
        $factory = VBOFactory::getDoorAccessControl();

        // get the requested integration provider
        $integration = $factory->getIntegrationProvider($provider);

        if (!$integration) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control provider identifier.');
        }

        if (empty($this->widgetSettings['id_provider']) || $this->widgetSettings['id_provider'] != $integration->getAlias()) {
            // set provider alias in widget settings
            $this->widgetSettings['id_provider'] = $integration->getAlias();
            // set no active profile in widget settings
            $this->widgetSettings['id_profile'] = null;
            // set no active device in widget settings
            $this->widgetSettings['id_device'] = null;
        }

        // determine the optional profile ID to get, if any
        $idProfile = (int) ($profile ?: $this->widgetSettings['id_profile'] ?? 0);

        // load the active integration profiles, if any
        $profiles = $factory->loadIntegrationRecords($integration->getAlias(), $idProfile);

        if ($profiles) {
            if ($profiles[0]['id'] != ($this->widgetSettings['id_profile'] ?? null)) {
                // set no active device in widget settings
                $this->widgetSettings['id_device'] = null;
            }
            // set active profile in widget settings
            $this->widgetSettings['id_profile'] = $profiles[0]['id'];

            // inject first profile record within the integration
            $integration->setProfileRecord($profiles[0]);
        }

        // determine the optional device ID to get, if any
        $loadIdDevice = (string) ($id_device ?: $this->widgetSettings['id_device'] ?? '');

        // ensure the device ID exists within the current profile record
        if ($loadIdDevice && !$integration->deviceExists($loadIdDevice)) {
            // invalid device ID
            $loadIdDevice = '';
        }

        if ($loadIdDevice) {
            // set active device in widget settings
            $this->widgetSettings['id_device'] = $loadIdDevice;
        }

        // update widget settings
        $this->updateSettings(json_encode($this->widgetSettings));

        // access the integration icon
        $integrationIcon = $integration->getIcon() ?: '<i class="' . VikBookingIcons::i('lock') . '"></i>';

        // determine the active tab to display
        $hasParams = (bool) count($integration->getParams());
        $hasSettings = (bool) count($integration->getSettings());
        $hasDevices = (bool) count($integration->getDevices());
        if ($hasParams && !$hasSettings) {
            // default to settings tab
            $activeTab = 'settings';
        } elseif ($hasSettings && !$hasDevices) {
            // default to devices tab
            $activeTab = 'devices';
        } else {
            // default to dashboard tab
            $activeTab = 'dashboard';
        }
        $activeTab = $tab ?: $activeTab;

        // load all listing names and related IDs
        $listingsPool = VikBooking::getAvailabilityInstance(true)->loadRooms([], 0, true);
        $listingsPool = array_combine(array_column($listingsPool, 'id'), array_column($listingsPool, 'name'));

        // start output buffering
        ob_start();

        ?>
        <div class="vbo-widget-door-access-control-content">
            <div class="vbo-widget-door-access-control-tabs">
            <?php
            if ($hasSettings && $hasDevices) {
                ?>
                <div class="vbo-widget-door-access-control-tab<?php echo $activeTab == 'dashboard' ? ' vbo-widget-tab-active' : ''; ?>" data-type="dashboard">
                    <span><?php VikBookingIcons::e('home'); ?> <?php echo JText::translate('VBMENUDASHBOARD'); ?></span>
                </div>
                <?php
            }
            if ($hasSettings) {
                ?>
                <div class="vbo-widget-door-access-control-tab<?php echo $activeTab == 'devices' ? ' vbo-widget-tab-active' : ''; ?>" data-type="devices">
                    <span><?php VikBookingIcons::e('microchip'); ?> <?php echo JText::translate('VBO_DEVICES'); ?></span>
                </div>
                <?php
            }
            ?>
                <div class="vbo-widget-door-access-control-tab<?php echo $activeTab == 'settings' ? ' vbo-widget-tab-active' : ''; ?>" data-type="settings">
                    <span><?php VikBookingIcons::e('cogs'); ?> <?php echo JText::translate('VBMENUTWELVE'); ?></span>
                </div>
            </div>
            <div class="vbo-widget-door-access-control-panels">
                <div class="vbo-widget-door-access-control-panel" data-type="dashboard" style="<?php echo $activeTab != 'dashboard' ? 'display: none;' : ''; ?>">
                    <div class="vbo-dac-integration-head">
                        <span class="vbo-dac-integration-icon">
                        <?php
                        if (preg_match('/^http/', (string) $integrationIcon)) {
                            // image URI is expected
                            ?>
                            <img src="<?php echo JHtml::fetch('esc_attr', $integrationIcon); ?>" />
                            <?php
                        } else {
                            // icon HTML is expected
                            echo $integrationIcon;
                        }
                        ?>
                        </span>
                        <span><?php echo $profiles[0]['name'] ?? ''; ?></span>
                    </div>
                    <div class="vbo-dac-integration-body">
                    <?php
                    if ($hasDevices) {
                        // build device elements
                        $deviceElements = array_map(function($device) {
                            $icon = $device->getIcon();
                            $icon = $icon && !preg_match('/^http/', (string) $icon) ? $icon : '';
                            return [
                                'id'   => $device->getID(),
                                'name' => sprintf('%s (%s)', $device->getName(), $device->getID()),
                                'html' => $icon,
                            ];
                        }, $integration->getDevices());
                        ?>
                        <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact vbo-dac-devices-info">
                            <div class="vbo-params-wrap">
                                <div class="vbo-params-container">
                                    <div class="vbo-params-block">
                                        <div class="vbo-param-container">
                                            <div class="vbo-param-label"><?php echo JText::translate('VBTRKDEVICE'); ?></div>
                                            <div class="vbo-param-setting">
                                            <?php
                                            echo VikBooking::getVboApplication()->renderElementsDropDown([
                                                'placeholder' => JText::translate('VBTRKDEVICE'),
                                                'attributes'  => [
                                                    'class' => 'vbo-door-access-control-device-id',
                                                    'onchange' => 'vboWidgetDACSwitchDeviceID("' . $wrapper . '")',
                                                ],
                                                'selected_value' => $loadIdDevice,
                                                'style_selection' => true,
                                                'default_selection_icon' => VikBookingIcons::i('fingerprint'),
                                            ], $deviceElements);
                                            ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="vbo-dac-device-capabilities">
                            <div class="vbo-dac-device-capability-sections">
                                <div class="vbo-dac-capabilities-list vbo-dac-cap-section-active">
                            <?php
                            if ($loadIdDevice) {
                                // render device dashboard
                                $integrationDevice = $integration->getDeviceById($loadIdDevice);

                                // iterate all device capabilities
                                foreach ($integrationDevice->getCapabilities() as $cap) {
                                    $capIcon = (string) $cap->getIcon();
                                    $isHtmlIcon = false;
                                    ?>
                                    <div class="vbo-dac-device-capability">
                                        <div class="vbo-dac-device-capability-head">
                                            <div class="vbo-dac-device-capability-icn">
                                            <?php
                                            if (preg_match('/^http/', (string) $capIcon)) {
                                                // image URI is expected
                                                ?>
                                                <img src="<?php echo JHtml::fetch('esc_attr', $capIcon); ?>" />
                                                <?php
                                            } else {
                                                // icon HTML is expected
                                                echo $capIcon;
                                                $isHtmlIcon = true;
                                            }
                                            ?>
                                            </div>
                                            <div class="vbo-dac-device-capability-info">
                                                <div class="vbo-dac-device-capability-name">
                                                    <span><?php echo $cap->getTitle(); ?></span>
                                                </div>
                                                <div class="vbo-dac-device-capability-descr">
                                                    <?php echo $cap->getDescription(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                    if ($cap->getCallback()) {
                                        ?>
                                        <div class="vbo-dac-device-capability-action">
                                            <button type="button" class="btn vbo-dac-device-capability-execute-btn" data-cap-id="<?php echo $cap->getID(); ?>" data-callback="<?php echo is_string($cap->getCallback()) ? $cap->getCallback() : ''; ?>"><?php echo $isHtmlIcon ? $capIcon : ''; ?><span class="vbo-dac-device-capability-btn-name"><?php echo $cap->getTitle(); ?></span></button>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    </div>
                                    <?php
                                }
                            } else {
                                ?>
                                    <p class="info"><?php echo JText::translate('VBO_CHOOSE_DEVICE'); ?></p>
                                <?php
                            }
                            ?>
                                </div>
                                <div class="vbo-dac-capability-result-wrap">
                                    <div class="vbo-dac-capability-result-head">
                                        <span class="vbo-dac-capability-result-back" data-provider-id="<?php echo JHtml::fetch('esc_attr', $integration->getAlias()); ?>" data-profile-id="<?php echo JHtml::fetch('esc_attr', $integration->getProfileID()); ?>" data-device-id="<?php echo JHtml::fetch('esc_attr', (($integrationDevice ?? null) ? $integrationDevice->getID() : '')); ?>"><?php VikBookingIcons::e('arrow-left'); ?> <?php echo JText::translate('VBBACK'); ?></span>
                                        <span class="vbo-dac-capability-result-name"></span>
                                    </div>
                                    <div class="vbo-dac-capability-result-body"></div>
                                </div>
                            </div>
                        </div>
                        <?php
                    } else {
                        ?>
                        <p class="warn"><?php echo JText::translate('VBO_DEVICES'); ?>: 0</p>
                        <?php
                    }
                    ?>
                    </div>
                </div>
                <div class="vbo-widget-door-access-control-panel" data-type="devices" style="<?php echo $activeTab != 'devices' ? 'display: none;' : ''; ?>">
                    <div class="vbo-dac-integration-head">
                        <span class="vbo-dac-integration-icon">
                        <?php
                        if (preg_match('/^http/', (string) $integrationIcon)) {
                            // image URI is expected
                            ?>
                            <img src="<?php echo JHtml::fetch('esc_attr', $integrationIcon); ?>" />
                            <?php
                        } else {
                            // icon HTML is expected
                            echo $integrationIcon;
                        }
                        ?>
                        </span>
                        <span><?php echo $profiles[0]['name'] ?? ''; ?></span>
                    </div>
                    <div class="vbo-dac-integration-body">
                        <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact vbo-dac-devices-info">
                            <div class="vbo-params-wrap">
                                <div class="vbo-params-container">
                                    <div class="vbo-params-block">
                                        <div class="vbo-param-container">
                                            <div class="vbo-param-label"><?php echo JText::translate('VBO_DEVICES'); ?></div>
                                            <div class="vbo-param-setting">
                                                <span class="badge badge-<?php echo $hasDevices ? 'info' : 'error'; ?>"><?php echo count($integration->getDevices()); ?></span>
                                                <button type="button" class="btn vbo-config-btn vbo-widget-dac-update-devices"><?php VikBookingIcons::e('sync'); ?> <?php echo JText::translate('VBO_SYNC'); ?></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php
                    if ($hasDevices) {
                        // display the current devices
                        ?>
                        <div class="vbo-dac-devices-wrap">
                        <?php
                        foreach ($integration->getDevices() as $device) {
                            $deviceIcon = $device->getIcon();
                            ?>
                            <div class="vbo-dac-device-wrap">
                                <div class="vbo-dac-device-head">
                                    <div class="vbo-dac-device-info">
                                        <div class="vbo-dac-device-data">
                                            <span class="vbo-dac-device-icn"><?php
                                            if (preg_match('/^http/', (string) $deviceIcon)) {
                                                // image URI is expected
                                                ?>
                                                <img src="<?php echo JHtml::fetch('esc_attr', $deviceIcon); ?>" />
                                                <?php
                                            } else {
                                                // icon HTML is expected
                                                echo $deviceIcon ?: '<i class="' . VikBookingIcons::i('microchip') . '"></i>';
                                            }
                                            ?></span>
                                            <div class="vbo-dac-device-subinfo">
                                                <div class="vbo-dac-device-name"><?php echo $device->getName(); ?></div>
                                                <span class="vbo-dac-device-id"><?php echo $device->getID(); ?></span>
                                            <?php
                                            if ($device->getModel() || $device->getDescription()) {
                                                ?>
                                                <span class="vbo-dac-device-model"><?php echo implode(' - ', array_filter([$device->getModel(), $device->getDescription()])); ?></span>
                                                <?php
                                            }
                                            ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php
                                if ($device->getBatteryLevel() !== null) {
                                    if ($device->getBatteryLevel() > 65) {
                                        $battery_class = 'success';
                                    } elseif ($device->getBatteryLevel() > 35) {
                                        $battery_class = 'warning';
                                    } else {
                                        $battery_class = 'error';
                                    }
                                    ?>
                                    <div class="vbo-dac-device-battery">
                                        <span class="vbo-dac-device-battery-level vbo-dac-level-<?php echo $battery_class; ?>"><?php VikBookingIcons::e('battery-full'); ?> <?php echo floor($device->getBatteryLevel()); ?>%</span>
                                    </div>
                                    <?php
                                }
                                ?>
                                </div>
                                <div class="vbo-dac-device-content vbo-widget-dac-device-listings-data" data-device-id="<?php echo $device->getID(); ?>" data-device-name="<?php echo JHtml::fetch('esc_attr', $device->getName()); ?>">
                                    <div class="vbo-dac-device-listings">
                                        <div class="vbo-dac-device-listings-count">
                                            <span><?php echo JText::translate('VBO_CONNECTED_LISTINGS'); ?> (<?php echo $device->countConnectedListings(); ?>)</span>
                                        </div>
                                        <div class="vbo-dac-device-listings-list">
                                    <?php
                                    foreach ($device->getConnectedListings() as $listingId) {
                                        // check if the listing was connected at subunit-level
                                        $listingSubunitIds = $device->getConnectedListingSubunits($listingId);
                                        if ($listingSubunitIds) {
                                            // display only the listing subunit connections
                                            foreach ($listingSubunitIds as $subunitId) {
                                                ?>
                                            <span class="vbo-widget-dac-device-cn-listing" data-listing-id="<?php echo $listingId; ?>" data-subunit-id="<?php echo $subunitId; ?>"><?php VikBookingIcons::e('home'); ?><?php echo sprintf('%s #%d', ($listingsPool[$listingId] ?? $listingId), $subunitId); ?></span>
                                                <?php
                                            }
                                        } else {
                                            // display the listing connection
                                            ?>
                                            <span class="vbo-widget-dac-device-cn-listing" data-listing-id="<?php echo $listingId; ?>"><?php VikBookingIcons::e('home'); ?><?php echo $listingsPool[$listingId] ?? $listingId; ?></span>
                                            <?php
                                        }
                                    }
                                    ?>
                                        </div>
                                    </div>
                                    <div class="vbo-dac-device-actions">
                                        <div class="vbo-dac-device-action">
                                            <button type="button" class="btn vbo-widget-dac-device-add-listing"><?php VikBookingIcons::e('plus-circle'); ?> <?php echo JText::translate('VBCONFIGCLOSINGDATEADD'); ?></button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <?php
                        }
                        ?>
                        </div>
                        <?php
                    }
                    ?>
                    </div>
                </div>
                <div class="vbo-widget-door-access-control-panel" data-type="settings" style="<?php echo $activeTab != 'settings' ? 'display: none;' : ''; ?>">
                    <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
                        <div class="vbo-params-wrap">
                            <div class="vbo-params-container">
                                <div class="vbo-params-block">
                                <?php
                                if ($profiles) {
                                    // some profile records exist
                                    ?>
                                    <div class="vbo-param-container">
                                        <div class="vbo-param-label"><?php echo JText::translate('VBO_PROFILE_SETTINGS'); ?></div>
                                        <div class="vbo-param-setting">
                                            <select class="vbo-door-access-control-setting" data-setting="id_profile" onchange="vboWidgetDACSwitchProfileSettings('<?php echo $wrapper; ?>');">
                                            <?php
                                            foreach ($profiles as $profile) {
                                                ?>
                                                <option value="<?php echo $profile['id']; ?>"<?php echo $profile['id'] == $this->widgetSettings['id_profile'] ? ' selected="selected"' : ''; ?>><?php echo $profile['name']; ?></option>
                                                <?php
                                            }
                                            ?>
                                                <option value="-1">- <?php echo JText::translate('VBO_PROFILE_NEW'); ?></option>
                                            </select>
                                        <?php
                                        if (!empty($this->widgetSettings['id_profile'])) {
                                            ?>
                                            <span class="vbo-dac-delete-profile-wrap">
                                                <button class="btn btn-small btn-danger vbo-dac-delete-profile-btn"><?php VikBookingIcons::e('trash', 'icn-nomargin'); ?></button>
                                            </span>
                                            <?php
                                        }
                                        ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                                    <div class="vbo-param-container">
                                        <div class="vbo-param-label"><?php echo JText::translate('VBO_PROFILE_NAME'); ?></div>
                                        <div class="vbo-param-setting">
                                            <input class="vbo-door-access-control-setting" type="text" value="<?php echo JHtml::fetch('esc_attr', $integration->getProfileName() ?: ''); ?>" data-setting="profile_name" maxlength="64" />
                                        </div>
                                    </div>
                                    <div class="vbo-param-container">
                                        <div class="vbo-param-label"><?php echo JText::translate('VBO_PASSCODE_GENERATION'); ?></div>
                                        <div class="vbo-param-setting">
                                            <div class="vbo-dac-gen-type-period">
                                                <select class="vbo-door-access-control-setting" data-setting="gentype" onchange="vboWidgetDACSwitchGenType('<?php echo $wrapper; ?>');">
                                                    <option value=""></option>
                                                    <option value="booking"<?php echo $integration->getProfileGenerationType() == 'booking' && $integration->hasProfileRecord() ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_AT_TIME_BOOKING'); ?></option>
                                                    <option value="precheckin"<?php echo $integration->getProfileGenerationType() == 'precheckin' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_PRECHECKIN_COMPLETED'); ?></option>
                                                    <option value="checkin"<?php echo $integration->getProfileGenerationType() == 'checkin' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBO_BEFORE_CHECKIN'); ?></option>
                                                    <option value="disabled"<?php echo $integration->getProfileGenerationType() == 'disabled' ? ' selected="selected"' : ''; ?>><?php echo JText::translate('VBPARAMPRICECALENDARDISABLED'); ?></option>
                                                </select>
                                                <select class="vbo-door-access-control-setting" data-setting="genperiod" style="<?php echo $integration->getProfileGenerationType() != 'checkin' ? 'display: none;' : ''; ?>">
                                                <?php
                                                $genPeriodHoursSingular = JText::translate('VBO_HOUR');
                                                $genPeriodHoursPlural = JText::translate('VBCONFIGONETENEIGHT');
                                                $hoursIntvals = array_merge(range(0, 6), [12, 24]);
                                                foreach ($hoursIntvals as $h) {
                                                    $curGenPeriodVal = sprintf('%dH', $h);
                                                    $curGenPeriodTxt = sprintf('%d %s', $h, ($h === 1 ? $genPeriodHoursSingular : $genPeriodHoursPlural));
                                                    ?>
                                                    <option value="<?php echo $curGenPeriodVal; ?>"<?php echo $integration->getProfileGenerationPeriod() == $curGenPeriodVal ? ' selected="selected"' : ''; ?>><?php echo $curGenPeriodTxt; ?></option>
                                                    <?php
                                                }
                                                ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="vbo-params-block">
                                    <?php
                                    echo VBOParamsRendering::getInstance(
                                        $integration->getParams(),
                                        $integration->getSettings()
                                    )->setInputName('integration[' . $integration->getAlias() . ']')->getHtml();
                                    ?>
                                    <div class="vbo-param-container">
                                        <div class="vbo-param-label">&nbsp;</div>
                                        <div class="vbo-param-setting">
                                            <button type="button" class="btn btn-success vbo-btn-wide" onclick="vboWidgetDACSaveProfileSettings('<?php echo $wrapper; ?>');"><?php echo JText::translate('VBSAVE'); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php

        // get the HTML buffer
        $html_content = ob_get_contents();
        ob_end_clean();

        // return an associative array of values
        return [
            'html'         => $html_content,
            'tot_profiles' => count($profiles),
        ];
    }

    /**
     * Custom method for this widget only.
     * Saves or updates the settings for a given provider and profile.
     * 
     * @return  array
     */
    public function saveProviderSettings()
    {
        $app = JFactory::getApplication();

        $wrapper = $app->input->getString('wrapper', '');
        $profile = $app->input->get('profile', [], 'array');
        $settings = $app->input->get('settings', [], 'array');

        if (empty($profile['id_provider'])) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control provider identifier for saving the settings.');
        }

        // access the door access factory object
        $factory = VBOFactory::getDoorAccessControl();

        // get the requested integration provider
        $integration = $factory->getIntegrationProvider($profile['id_provider']);

        if (!$integration) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control provider identifier.');
        }

        // determine if we are updating an existing profile ID or if we are creating a new one (0 or -1)
        $idProfile = (int) ($profile['id_profile'] ?? 0);
        $idProfile = $idProfile > 0 ? $idProfile : 0;

        // normalize default provider settings
        $defaultSettings = array_combine(array_keys($integration->getParams()), array_map(function($paramName) {
            // param settings will get an empty string by default to support checkbox or other param types
            return '';
        }, array_keys($integration->getParams())));

        // build record options
        $recordOptions = [
            'id_profile'   => $idProfile,
            'profile_name' => $profile['profile_name'] ?? '',
            'gentype'      => $profile['gentype'] ?? null,
            'genperiod'    => $profile['genperiod'] ?? null,
            'settings'     => array_merge($defaultSettings, $settings),
        ];

        try {
            // save or update the integration record name and settings
            $factory->saveIntegrationRecord($integration, $recordOptions);
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage() ?: 'Error saving provider settings.');
        }

        return [
            'id_profile' => $integration->getProfileID(),
        ];
    }

    /**
     * Custom method for this widget only.
     * Fetches the remote provider devices and saves them internally.
     * 
     * @return  array
     */
    public function updateProviderDevices()
    {
        $app = JFactory::getApplication();

        $wrapper = $app->input->getString('wrapper', '');
        $provider = $app->input->getString('provider', '');
        $idProfile = $app->input->getUInt('profile', 0);

        if (empty($provider)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control provider identifier.');
        }

        // access the door access factory object
        $factory = VBOFactory::getDoorAccessControl();

        // get the requested integration provider
        $integration = $factory->getIntegrationProvider($provider);

        if (!$integration) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control provider identifier.');
        }

        // load the requested integration profile
        $profile = $factory->loadIntegrationRecord($idProfile);

        if (!$profile) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control profile identifier.');
        }

        // inject profile record within the integration
        $integration->setProfileRecord($profile);

        try {
            // fetch and update the provider devices
            $tot_devices = $factory->updateProviderDevices($integration);
        } catch (Exception $e) {
            // raise an error
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        return [
            'tot_devices' => $tot_devices,
        ];
    }

    /**
     * Custom method for this widget only.
     * Adds a new listing ID connection with a device.
     * 
     * @return  array
     */
    public function setDeviceListingConnection()
    {
        $app = JFactory::getApplication();

        $wrapper   = $app->input->getString('wrapper', '');
        $provider  = $app->input->getString('provider', '');
        $idProfile = $app->input->getUInt('profile', 0);
        $idDevice  = $app->input->getString('device', '');
        $idListing = $app->input->getUInt('listing', 0);
        $idSubunit = $app->input->getUInt('subunit', 0);

        if (empty($provider)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control provider identifier.');
        }

        if (empty($idDevice)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control device identifier.');
        }

        if (empty($idListing)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing listing ID.');
        }

        // access the door access factory object
        $factory = VBOFactory::getDoorAccessControl();

        // get the requested integration provider
        $integration = $factory->getIntegrationProvider($provider);

        if (!$integration) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control provider identifier.');
        }

        // load the requested integration profile
        $profile = $factory->loadIntegrationRecord($idProfile);

        if (!$profile) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control profile identifier.');
        }

        // inject profile record within the integration
        $integration->setProfileRecord($profile);

        // attempt to find the requested device
        $deviceFound = null;
        foreach ($integration->getDevices() as $device) {
            if ($device->getID() === $idDevice) {
                // device ID found, add listing relation
                $device->addConnectedListing($idListing, $idSubunit);

                // turn flag on and abort
                $deviceFound = $device;
                break;
            }
        }

        if (!$deviceFound) {
            VBOHttpDocument::getInstance($app)->close(400, 'Invalid door access control device identifier.');
        }

        try {
            // update the integration record devices
            $integration->setProfileRecordProp('devices', $integration->getDevices());

            // ensure the required device-listing relation is saved on E4jConnect first
            if (!class_exists('VCMDacDevicesRequestor')) {
                throw new Exception('Please update the Channel Manager.', 426);
            }
            (new VCMDacDevicesRequestor)->add($deviceFound);

            // update integration devices onto the current profile record
            $factory->saveIntegrationRecord($integration, ['devices' => $integration->getDevices()]);
        } catch (Exception $e) {
            // raise an error
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        return [
            'device_name' => $deviceFound->getName(),
        ];
    }

    /**
     * Custom method for this widget only.
     * Removes an existing listing ID connection with a device.
     * 
     * @return  array
     */
    public function unsetDeviceListingConnection()
    {
        $app = JFactory::getApplication();

        $wrapper = $app->input->getString('wrapper', '');
        $provider = $app->input->getString('provider', '');
        $idProfile = $app->input->getUInt('profile', 0);
        $idDevice = $app->input->getString('device', '');
        $idListing = $app->input->getUInt('listing', 0);
        $idSubunit = $app->input->getUInt('subunit', 0);

        if (empty($provider)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control provider identifier.');
        }

        if (empty($idDevice)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control device identifier.');
        }

        if (empty($idListing)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing listing ID.');
        }

        // access the door access factory object
        $factory = VBOFactory::getDoorAccessControl();

        // get the requested integration provider
        $integration = $factory->getIntegrationProvider($provider);

        if (!$integration) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control provider identifier.');
        }

        // load the requested integration profile
        $profile = $factory->loadIntegrationRecord($idProfile);

        if (!$profile) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control profile identifier.');
        }

        // inject profile record within the integration
        $integration->setProfileRecord($profile);

        // attempt to find the requested device ID
        $deviceFound = null;
        foreach ($integration->getDevices() as $device) {
            if ($device->getID() === $idDevice) {
                // device ID found, remove listing relation
                $device->removeConnectedListing($idListing, $idSubunit);

                // turn flag on and abort
                $deviceFound = $device;
                break;
            }
        }

        if (!$deviceFound) {
            VBOHttpDocument::getInstance($app)->close(400, 'Invalid door access control device identifier.');
        }

        try {
            // update the integration record devices
            $integration->setProfileRecordProp('devices', $integration->getDevices());

            if (!$deviceFound->getConnectedListings()) {
                // ensure the device is deleted from E4jConnect when no more listings are connected
                if (!class_exists('VCMDacDevicesRequestor')) {
                    throw new Exception('Please update the Channel Manager.', 426);
                }
                (new VCMDacDevicesRequestor)->delete($deviceFound);
            }

            // update integration devices onto the current profile record
            $factory->saveIntegrationRecord($integration, ['devices' => $integration->getDevices()]);
        } catch (Exception $e) {
            // raise an error
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        return [
            'device_name' => $deviceFound->getName(),
        ];
    }

    /**
     * Custom method for this widget only.
     * Deletes a specific integration profile record.
     * 
     * @return  array
     */
    public function deleteProfileRecord()
    {
        $app = JFactory::getApplication();

        $wrapper = $app->input->getString('wrapper', '');
        $provider = $app->input->getString('provider', '');
        $idProfile = $app->input->getUInt('profile', 0);

        if (empty($provider)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control provider identifier.');
        }

        // access the door access factory object
        $factory = VBOFactory::getDoorAccessControl();

        // get the requested integration provider
        $integration = $factory->getIntegrationProvider($provider);

        if (!$integration) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control provider identifier.');
        }

        // load the requested integration profile
        $profile = $factory->loadIntegrationRecord($idProfile);

        if (!$profile) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control profile identifier.');
        }

        // inject profile record within the integration
        $integration->setProfileRecord($profile);

        try {
            // ensure all devices are deleted from E4jConnect first
            if (!class_exists('VCMDacDevicesRequestor')) {
                throw new Exception('Please update the Channel Manager.', 426);
            }
            (new VCMDacDevicesRequestor)->delete($integration->getDevices());

            // destroy (delete) the current profile record
            $integration->destroyProfileRecord();

            // set no active profile in widget settings
            $this->widgetSettings['id_profile'] = null;
            // set no active device in widget settings
            $this->widgetSettings['id_device'] = null;

            // update widget settings
            $this->updateSettings(json_encode($this->widgetSettings));
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        // process successfully completed
        return [
            'success' => 1,
        ];
    }

    /**
     * Custom method for this widget only.
     * Executes a capability for a specific device.
     * 
     * @return  array
     */
    public function executeDeviceCapability()
    {
        $app = JFactory::getApplication();

        $wrapper = $app->input->getString('wrapper', '');
        $provider = $app->input->getString('provider', '');
        $idProfile = $app->input->getUInt('profile', 0);
        $idDevice = $app->input->getString('device', '');
        $idCapability = $app->input->getString('capability', '');
        $noParams = $app->input->getBool('noparams', false);
        $capabilitySettings = $app->input->get('capability_settings', [], 'array');
        $retryDataOptions = $app->input->get('retry_data', [], 'array');

        if (empty($provider)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control provider identifier.');
        }

        if (empty($idDevice)) {
            VBOHttpDocument::getInstance($app)->close(400, 'Missing door access control device identifier.');
        }

        // access the door access factory object
        $factory = VBOFactory::getDoorAccessControl();

        // get the requested integration provider
        $integration = $factory->getIntegrationProvider($provider);

        if (!$integration) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control provider identifier.');
        }

        // load the requested integration profile
        $profile = $factory->loadIntegrationRecord($idProfile);

        if (!$profile) {
            VBOHttpDocument::getInstance($app)->close(404, 'Invalid door access control profile identifier.');
        }

        // inject profile record within the integration
        $integration->setProfileRecord($profile);

        try {
            // access the requested device
            $device = $integration->getDeviceById($idDevice);

            // access the requested device capability
            $capability = $device->getCapabilityById($idCapability);
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        if ($capability->providesParams() && $noParams && !$capabilitySettings) {
            // capability provides parameters that require settings for its execution
            return [
                'capability_params' => VBOParamsRendering::getInstance($capability->getParams(), $retryDataOptions)->setInputName('capability_settings[' . $capability->getID() . ']')->getHtml(),
            ];
        }

        try {
            // execute the device capability
            $capabilityResult = $capability->execute($integration, $device, $capabilitySettings);
        } catch (Exception $e) {
            VBOHttpDocument::getInstance($app)->close($e->getCode() ?: 500, $e->getMessage());
        }

        if ($capabilityResult->getOutput()) {
            // display the capability execution result output (HTML)
            return [
                'result_html' => (string) $capabilityResult,
                'device_updated' => (int) $device->getDataChanged(),
            ];
        }

        if ($capabilityResult->getText()) {
            // display the capability execution result text
            return [
                'result_text' => (string) $capabilityResult,
                'device_updated' => (int) $device->getDataChanged(),
            ];
        }

        // no result information, yet successful
        return [
            'success' => 1,
            'device_updated' => (int) $device->getDataChanged(),
        ];
    }
}
