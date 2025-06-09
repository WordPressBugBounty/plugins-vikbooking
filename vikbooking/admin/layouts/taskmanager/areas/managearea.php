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
 * @var array  $data    The data for rendering the area management form.
 */
extract($displayData);

// access the task manager object
$taskManager = VBOFactory::getTaskManager();

// get all task driver names
$taskDrivers = $taskManager->getDriverNames();

// access the current area object, if any
$currentArea = !empty($data['id']) ? VBOTaskModelArea::getInstance()->getItem($data['id']) : null;

// attempt to get the current task area object
$taskArea = $currentArea ? VBOTaskArea::getInstance((array) $currentArea) : null;

?>

<form method="post" action="#" id="<?php echo $data['form_id'] ?? 'vbo-tm-area-manage-form'; ?>">
    <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
        <div class="vbo-params-wrap">
            <div class="vbo-params-container">

                <div class="vbo-params-block vbo-params-block-full-setting">

                    <div class="vbo-param-container">
                        <div class="vbo-param-label"><?php echo JText::translate('VBPSHOWSEASONSTHREE'); ?></div>
                        <div class="vbo-param-setting">
                        <?php
                        if (!$currentArea) {
                            ?>
                            <select name="area[instanceof]" id="vbo-tm-area-instanceof">
                                <option value=""></option>
                            <?php
                            foreach ($taskDrivers as $driverId => $driverName) {
                                ?>
                                <option value="<?php echo JHtml::fetch('esc_attr', $driverId); ?>"><?php echo $driverName; ?></option>
                                <?php
                            }
                            ?>
                            </select>
                            <?php
                        } else {
                            ?>
                            <span class="label label-info"><?php echo $taskDrivers[$currentArea->instanceof] ?? $currentArea->instanceof; ?></span>
                            <input type="hidden" name="area[id]" value="<?php echo (int) $currentArea->id; ?>" />
                            <input type="hidden" name="area[instanceof]" value="<?php echo JHtml::fetch('esc_attr', $currentArea->instanceof); ?>" />
                            <?php
                        }
                        ?>
                        </div>
                    </div>

                    <div class="vbo-param-container">
                        <div class="vbo-param-label"><?php echo JText::translate('VBPVIEWROOMONE'); ?></div>
                        <div class="vbo-param-setting">
                            <input type="text" name="area[name]" value="<?php echo $currentArea ? JHtml::fetch('esc_attr', $currentArea->name) : ''; ?>" />
                        </div>
                    </div>

                    <div class="vbo-param-container">
                        <div class="vbo-param-label"><?php echo JText::translate('VBO_TAGS'); ?></div>
                        <div class="vbo-param-setting">
                            <div class="vbo-multiselect-inline-elems-wrap vbo-tagcolors-elems-wrap">
                            <?php
                            echo VikBooking::getVboApplication()->renderTagsDropDown([
                                'id'          => 'vbo-tm-editarea-tags',
                                'placeholder' => JText::translate('VBO_TAGS_PLACEHOLDER'),
                                'allow_clear' => false,
                                'attributes'  => [
                                    'name'     => 'area[tags][]',
                                    'multiple' => 'multiple',
                                ],
                                'selected_values' => ($taskArea ? $taskArea->getTags() : []),
                                'colors' => $taskManager->getTagColors(true),
                            ], $taskManager->getColorTags());
                            ?>
                            </div>
                            <span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_TASK_TAGS_DESC'); ?></span>
                        </div>
                    </div>

                    <div class="vbo-param-container">
                        <div class="vbo-param-label"><?php echo JText::translate('VBO_TASK_STATUSES'); ?></div>
                        <div class="vbo-param-setting">
                            <div class="vbo-multiselect-inline-elems-wrap vbo-tagcolors-elems-wrap vbo-statuscolors-elems-wrap">
                            <?php
                            echo VikBooking::getVboApplication()->renderTagsDropDown([
                                'id'          => 'vbo-tm-editarea-statuses',
                                'allow_clear' => false,
                                'allow_tags'  => false,
                                'attributes'  => [
                                    'name'     => 'area[status_enums][]',
                                    'multiple' => 'multiple',
                                ],
                                'selected_values' => ($taskArea ? $taskArea->getStatuses() : $taskManager->getStatusTypes(true)),
                            ], [], $taskManager->getStatusGroupElements());
                            ?>
                            </div>
                            <span class="vbo-param-setting-comment"><?php echo JText::translate('VBO_TASK_STATUSES_DESC'); ?></span>
                        </div>
                    </div>

                    <div class="vbo-param-container">
                        <div class="vbo-param-label"><?php echo JText::translate('VBPVIEWOPTIONALSTWO'); ?></div>
                        <div class="vbo-param-setting">
                            <textarea name="area[comments]"><?php echo $taskArea ? JHtml::fetch('esc_textarea', $taskArea->get('comments', '')) : ''; ?></textarea>
                        </div>
                    </div>

                </div>

            <?php
            if (!$currentArea) {
                // display setting blocks for each task driver
                foreach ($taskDrivers as $driverId => $driverName) {
                ?>
                <div class="vbo-params-block vbo-params-block-full-setting vbo-tm-area-mng-settings" data-area-settings="<?php echo JHtml::fetch('esc_attr', $driverId); ?>" style="display: none;">
                <?php
                    $taskDriver = $taskManager->getDriverInstance($driverId);

                    echo VBOParamsRendering::getInstance(
                        $taskDriver->getParams(),
                        $taskDriver->getSettings()
                    )->setInputName('area_settings[' . $driverId . ']')->getHtml();
                ?>
                </div>
                <?php
                }
            } else {
                // display current area's task driver settings
                $taskDriver = $taskManager->getDriverInstance($currentArea->instanceof, [$taskArea]);
                ?>
                <div class="vbo-params-block vbo-params-block-full-setting vbo-tm-area-mng-settings" data-area-settings="<?php echo JHtml::fetch('esc_attr', $currentArea->instanceof); ?>" style="<?php echo !$taskDriver->getParams() ? 'display: none;' : ''; ?>">
                <?php
                    echo VBOParamsRendering::getInstance(
                        $taskDriver->getParams(),
                        $taskDriver->getSettings()
                    )->setInputName('area_settings[' . $currentArea->instanceof . ']')->getHtml();
                ?>
                </div>
                <?php
            }
            ?>

            </div>
        </div>
    </div>
</form>

<script type="text/javascript">
    jQuery(function() {

        /**
         * Handle driver instance type change event.
         */
        let driverInstanceType = document.querySelector('#vbo-tm-area-instanceof');
        if (driverInstanceType) {
            driverInstanceType.addEventListener('change', () => {
                // get current value
                let currentType = driverInstanceType.value;

                // scan all settings and display only the selected block type
                document.querySelectorAll('.vbo-tm-area-mng-settings').forEach((settings) => {
                    if (settings.getAttribute('data-area-settings') == currentType) {
                        if (settings.querySelector('div')) {
                            // show element when settings are available
                            settings.style.display = '';
                        }
                    } else {
                        // hide element
                        settings.style.display = 'none';
                    }
                });
            });
        }

    });
</script>
