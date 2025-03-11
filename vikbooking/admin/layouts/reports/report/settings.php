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
 * This layout file should be called once at most per page.
 * 
 * @var string  $report     The report file name (identifier).
 * @var array   $fields     List of setting fields to render.
 * @var array   $settings   List of current report settings.
 * @var object  $instance   The report object instance.
 */
extract($displayData);

?>
<form action="#save-report-settings" method="post" name="vbo-report-custom-settings" id="vbo-report-custom-settings-form">

    <input type="hidden" name="report" value="<?php echo $report; ?>" />

    <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
        <div class="vbo-params-wrap">
            <div class="vbo-params-container">

            <?php
            /**
             * Handle multiple report profile settings.
             * 
             * @since   1.17.7 (J) - 1.7.7 (WP)
             */
            if ($instance->allowsProfileSettings()) {
                // load all the existing profiles
                $report_profiles = $instance->getSettingProfiles();

                // load the active profile
                $active_profile = $instance->getActiveProfile();

                // get the active profile name
                $profile_name = $active_profile ? ($report_profiles[$active_profile] ?? JText::translate('VBO_USE_DEFAULT')) : JText::translate('VBO_USE_DEFAULT');

                ?>
                <div class="vbo-params-block">

                    <div class="vbo-param-container">
                        <div class="vbo-param-label"><?php echo JText::translate('VBO_PROFILE_SETTINGS'); ?></div>
                        <div class="vbo-param-setting">
                            <select name="_profile" onchange="VBOCore.emitEvent('vbo-report-settings-profile-changed', {value: this.value});">
                                <option value="<?php echo JHtml::fetch('esc_attr', $active_profile); ?>"><?php echo $profile_name; ?></option>
                                <option value="_new"><?php echo JText::translate('VBO_PROFILE_NEW'); ?></option>
                            </select>
                            <span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_PROFILE_SETTINGS_HELP'); ?></span>
                        </div>
                    </div>

                    <div class="vbo-param-container" data-profile="_new" style="display: none;">
                        <div class="vbo-param-label"><?php echo JText::translate('VBO_PROFILE_NAME'); ?></div>
                        <div class="vbo-param-setting">
                            <input type="text" name="_newprofile" value="" maxlength="64" />
                        </div>
                    </div>

                </div>
                <?php
            }
            ?>

                <div class="vbo-params-block">

                    <?php
                    // render the report custom settings
                    echo VBOParamsRendering::getInstance($fields, (array) $settings)->setInputName('data')->getHtml();
                    ?>

                </div>

            </div>
        </div>
    </div>

</form>
