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
 * Display data attributes.
 * 
 * @var VBOHelpWizardInstruction  $instruction
 */
extract($displayData);

$countries = [];

foreach (VikBooking::getCountriesArray($tn = false, $no_id = false) as $country) {
    $country = (object) $country;
    $countries[$country->country_2_code] = $country;
}

$defaultCountry = $instruction->guessCountry();

$states = [];

if ($defaultCountry) {
    // load all the states of the pre-selected country
    $states = array_map(function($state) {
        return (object) $state;
    }, VBOStateHelper::getCountryStates($countries[$defaultCountry]->id ?? null));
}

?>

<div class="vbo-help-wizard-instruction-description" style="margin-bottom: 20px;">
    <?php echo JText::translate('VBO_HELP_WIZARD_GENERAL_BASE_COUNTRY_SUMMARY'); ?>
</div>

<form id="help-wizard-instruction-form" class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">

    <div class="vbo-params-wrap">

        <div class="vbo-params-container">

            <div class="vbo-param-container country-field">
                <div class="vbo-param-label"><?php echo JText::translate('VBCUSTOMERCOUNTRY'); ?></div>
                <div class="vbo-param-setting">
                    <select name="country">
                        <option value=""><?php echo JText::translate('VBO_SELECT_COUNTRY'); ?></option>
                        <?php echo JHtml::fetch('select.options', $countries, 'country_2_code', 'country_name', $defaultCountry); ?>
                    </select>
                    <span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_HELP_WIZARD_GENERAL_BASE_COUNTRY_VALUE_DESC'); ?></span>
                </div>
            </div>

            <div class="vbo-param-container state-field" style="<?php echo $states ? '' : 'display: none;'; ?>">
                <div class="vbo-param-label"><?php echo JText::translate('VBO_STATE_PROVINCE'); ?></div>
                <div class="vbo-param-setting">
                    <select name="state">
                        <option value=""><?php echo JText::translate('JGLOBAL_SELECT_AN_OPTION'); ?></option>
                        <?php echo JHtml::fetch('select.options', $states, 'state_2_code', 'state_name'); ?>
                    </select>
                    <span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_HELP_WIZARD_GENERAL_BASE_COUNTRY_STATE_DESC'); ?></span>
                </div>
            </div>

        </div>

    </div>

</form>

<script>
    (function($) {
        'use strict';

        $(function() {
            const form = $('#help-wizard-instruction-form');

            const countrySelect = form.find('select[name="country"]');
            const stateSelect = form.find('select[name="state"]');

            countrySelect.on('change', async function() {
                const countryCode = $(this).val();

                // remove all the options (except for the first one)
                stateSelect.find('option:not(:first)').remove();

                if (!countryCode) {
                    stateSelect.closest('.state-field').hide();
                    return;
                }

                try {
                    // get states for the selected country
                    let response = await VBOHelpWizard.processInstruction('<?php echo $instruction->getID(); ?>', {
                        scope: 'states',
                        country: countryCode,
                    });

                    if (!response?.states?.length) {
                        throw 'no_states';
                    }

                    response.states.forEach((state) => {
                        stateSelect.append($('<option></option>').val(state.state_2_code).text(state.state_name));
                    });

                    stateSelect.closest('.state-field').show();
                } catch (err) {
                    stateSelect.closest('.state-field').hide();
                }
            });
        });
    })(jQuery);
</script>