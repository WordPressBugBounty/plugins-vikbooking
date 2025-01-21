<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Obtain vars from arguments received in the layout file.
 * 
 * @var string  $type    Type identifier for the content to generate.
 * @var string  $prefix  The class prefix for the various elements.
 * @var string  $ecls    Optional extra class list to append.
 * @var string  $info    Optional information to be used by default.
 * @var string  $help    Optional help text to enter the information.
 * @var string  $fields  Optional extra fields or content to display.
 * @var array   $room    Optional room record for generating room content.
 */
extract($displayData);

// define the default argument values
$type   = $type ?? 'mail_content';
$prefix = $prefix ?? 'vbo-content-genai';
$info   = $info ?? '';
$help   = $help ?? '';
$ecls   = $ecls ?? null;

?>

<div class="<?php echo $prefix; ?>-helper<?php echo $ecls ? ' ' . $ecls : ''; ?>" style="display: none;">
    <div class="<?php echo $prefix; ?>-wrap<?php echo $ecls ? ' ' . $ecls : ''; ?>">
        <div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">
            <div class="vbo-params-wrap">
                <div class="vbo-params-container">
                    <div class="vbo-params-block">

                        <div class="vbo-param-container">
                            <div class="vbo-param-label"><?php echo JText::translate('VBOBOOKINGLANG'); ?></div>
                            <div class="vbo-param-setting">
                                <select class="<?php echo $prefix; ?>-field" data-field="language">
                                    <option value="">- <?php echo JText::translate('VBCONFIGSEARCHPSMARTSEARCHAUTO'); ?> -</option>
                                <?php
                                foreach (VikBooking::getVboApplication()->getKnownLanguages() as $tag => $lang) {
                                    ?>
                                    <option value="<?php echo JHtml::fetch('esc_attr', $lang['nativeName'] . '__' . $tag); ?>"><?php echo JHtml::fetch('esc_html', $lang['nativeName']); ?></option>
                                    <?php
                                }
                                ?>
                                </select>
                            </div>
                        </div>

                        <?php
                        // optional extra fields in HTML format
                        if (($fields ?? null) && is_string($fields)) {
                            echo $fields;
                        }

                        // information string
                        $room_def_ai_info = $info;
                        if (($room ?? []) && !$info) {
                            // build the basic room information string
                            $geo = VikBooking::getGeocodingInstance();
                            $room_params = json_decode(($room['params'] ?? '[]'), true);
                            $address = $geo->getRoomGeoParams($room_params, 'address', '');
                            $latitude = $geo->getRoomGeoParams($room_params, 'latitude', '');
                            $longitude = $geo->getRoomGeoParams($room_params, 'longitude', '');

                            $room_ai_infos = [
                                $room['name'] ?? '',
                                JText::translate('VBEDITORDERADULTS') . ': ' . ($room['toadult'] ?? 1),
                                (($room['tochild'] ?? 0) > 0 ? (JText::translate('VBEDITORDERCHILDREN') . ': ' . $room['tochild']) : ''),
                                ($address ? (JText::translate('VBO_GEO_ADDRESS') . ': ' . $address) : ''),
                                (!$address && $latitude ? (JText::translate('VBPLACELAT') . ': ' . $latitude) : ''),
                                (!$address && $longitude ? (JText::translate('VBPLACELNG') . ': ' . $longitude) : ''),
                            ];

                            $room_def_ai_info = implode(', ', array_filter($room_ai_infos));
                        }
                        ?>
                        <div class="vbo-param-container">
                            <div class="vbo-param-label"><?php echo JText::translate('VBOADMINLEGENDDETAILS'); ?></div>
                            <div class="vbo-param-setting">
                                <textarea rows="6" class="<?php echo $prefix; ?>-field" data-field="information"><?php echo JHtml::fetch('esc_textarea', $room_def_ai_info); ?></textarea>
                                <span class="vbo-param-setting-comment"><?php echo $help ?: JText::translate('VBO_AI_GEN_CONTENT_INFO_HELP'); ?></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
