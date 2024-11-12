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
