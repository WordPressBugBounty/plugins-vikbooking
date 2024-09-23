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
 * Helper class to render specific param types.
 * 
 * @since 	1.16.9 (J) - 1.6.9 (WP)
 */
final class VBOParamsRendering
{
    /**
     * @var  array
     */
    private $params = [];

    /**
     * @var  array
     */
    private $settings = [];

    /**
     * @var  array
     */
    private $scripts = [];

    /**
     * @var  array
     */
    private $assets = [];

    /**
     * @var  string
     */
    private $inputName = 'vboparams';

    /**
     * @var     int
     */
    private static $instance_counter = -1;

    /**
     * Class constructor is protected.
     * 
     * @param   array    $params   The form params to bind.
     * @param   array    $settings The form settings to bind.
     * 
     * @see     getInstance()
     */
    private function __construct(array $params, array $settings)
    {
        // bind values
        $this->params   = $params;
        $this->settings = $settings;

        // increase instance counter
        static::$instance_counter++;
    }

    /**
     * Proxy for immediately accessing the object and bind data.
     * 
     * @param   array    $params   The form params to bind.
     * @param   array    $settings The form settings to bind.
     * 
     * @return  VBOParamsRendering
     */
    public static function getInstance(array $params = [], array $settings = [])
    {
        return new static($params, $settings);
    }

    /**
     * Sets the name to be used for rendering the param fields.
     * 
     * @param   string  $name   The name to use.
     * 
     * @return  self
     */
    public function setInputName($name)
    {
        $this->inputName = (string) $name;

        return $this;
    }

    /**
     * Renders the injected form params and returns the HTML code.
     * 
     * @param   bool    $load_assets    Whether to load the necessary assets.
     * 
     * @return  string
     */
    public function getHtml($load_assets = true)
    {
        if (!$this->params) {
            return '';
        }

        $html = '';

        foreach ($this->params as $param_name => $param_config) {
            if (empty($param_name)) {
                continue;
            }

            $labelparts = explode('//', (isset($param_config['label']) ? $param_config['label'] : ''));
            $label = $labelparts[0];
            $labelhelp = isset($labelparts[1]) ? $labelparts[1] : '';
            if (!empty($param_config['help'])) {
                $labelhelp = $param_config['help'];
            }

            $nested_style   = (isset($param_config['nested']) && $param_config['nested']);
            $hidden_wrapper = false;
            if (isset($param_config['conditional']) && isset($this->params[$param_config['conditional']])) {
                $check_cond = isset($this->params[$param_config['conditional']]['default']) ? $this->params[$param_config['conditional']]['default'] : null;
                $check_cond = isset($this->settings[$param_config['conditional']]) ? $this->settings[$param_config['conditional']] : $check_cond;
                if (!is_null($check_cond) && (!$check_cond || !strcasecmp($check_cond, 'off'))) {
                    // hide current field because the field to who this is dependant is "off" or disabled (i.e. 0)
                    $hidden_wrapper = true;
                }
            }

            $html .= '<div class="vbo-param-container' . (in_array($param_config['type'], ['textarea', 'visual_html']) ? ' vbo-param-container-full' : '') . ($nested_style ? ' vbo-param-nested' : '') . '"' . ($hidden_wrapper ? ' style="display: none;"' : '') . '>';
            if (strlen($label) > 0 && (!isset($param_config['hidden']) || $param_config['hidden'] != true)) {
                $html .= '<div class="vbo-param-label">' . $label . '</div>';
            }
            $html .= '<div class="vbo-param-setting">';

            // render field
            $html .= $this->getField($param_name, $param_config);

            // check for assets to be loaded, only once to obtain individual setups
            if ($load_assets) {
                $this->loadAssets($load_once = true);
            }

            if ($labelhelp) {
                $html .= '<span class="vbo-param-setting-comment">' . $labelhelp . '</span>';
            }

            $html .= '</div>';
            $html .= '</div>';
        }

        // JS helper functions
        $html .= $this->getScripts();

        return $html;
    }

    /**
     * Loads the requested assets, if any.
     * 
     * @param   bool    $load_once  True to unset the assets after loading.
     * 
     * @return  void
     */
    public function loadAssets($load_once = false)
    {
        $doc = JFactory::getDocument();
        foreach ($this->assets as $asset_type => $asset_elements) {
            if ($asset_type === 'select2') {
                // build list of selectors
                $ids_list = implode(', ', array_map(function($el) {
                    return "#{$el}";
                }, $asset_elements));

                // check for asset options
                $asset_options = $this->assets['select2_options'] ?? null;
                $asset_options_str = $asset_options ? json_encode($asset_options) : '';

                // add script declaration
                VikBooking::getVboApplication()->loadSelect2();
                $doc->addScriptDeclaration(
<<<JS
jQuery(function() {
    jQuery('$ids_list').select2($asset_options_str);
});
JS
                );
            }

            if ($load_once) {
                unset($this->assets[$asset_type]);
            }
        }
    }

    /**
     * Gets the necessary script tags.
     * 
     * @return  string
     */
    public function getScripts()
    {
        $html = '';

        if (in_array('password', $this->scripts)) {
            // toggle the password fields
            $html .= "\n" . '<script>' . "\n";
            $html .= 'function vboParamTogglePwd(elem) {' . "\n";
            $html .= '  var btn = jQuery(elem), inp = btn.parent().find("input").first();' . "\n";
            $html .= '  if (!inp || !inp.length) {return false;}' . "\n";
            $html .= '  var inp_type = inp.attr("type");' . "\n";
            $html .= '  inp.attr("type", (inp_type == "password" ? "text" : "password"));' . "\n";
            $html .= '}' . "\n";
            $html .= "\n" . '</script>' . "\n";
        }

        return $html;
    }

    /**
     * Renders the given param name according to config.
     * Eventually populates the assets and scripts to be loaded.
     * 
     * @param   string  $param_name     The param name.
     * @param   array   $param_config   The param configuration.
     * 
     * @return  string
     */
    public function getField($param_name, $param_config)
    {
        $html = '';

        $inp_attr = '';
        if (isset($param_config['attributes']) && is_array($param_config['attributes'])) {
            foreach ($param_config['attributes'] as $inpk => $inpv) {
                $inp_attr .= $inpk . '="' . $inpv . '" ';
            }
            $inp_attr = ' ' . rtrim($inp_attr);
        }

        $default_paramv = $param_config['default'] ?? null;

        switch ($param_config['type']) {
            case 'custom':
                $html .= $param_config['html'];
                break;
            case 'select':
                $options    = isset($param_config['options']) && is_array($param_config['options']) ? $param_config['options'] : [];
                $is_assoc   = (array_keys($options) !== range(0, count($options) - 1));
                $element_id = 'vik-select-' . static::$instance_counter . '-' . preg_replace("/[^A-Z0-9]+/i", '', $param_name);
                $set_attr   = true;
                if (isset($param_config['attributes']) && is_array($param_config['attributes']) && isset($param_config['attributes']['id'])) {
                    $element_id = $param_config['attributes']['id'];
                    $set_attr   = false;
                }
                if (isset($param_config['assets']) && $param_config['assets']) {
                    if (!isset($this->assets['select2'])) {
                        $this->assets['select2'] = [];
                    }
                    $this->assets['select2'][] = $element_id;
                    $this->assets['select2_options'] = $param_config['asset_options'] ?? null;
                }
                if (isset($param_config['multiple']) && $param_config['multiple']) {
                    $html .= '<select name="' . $this->inputName . '[' . $param_name . '][]" multiple="multiple"' . $inp_attr . ($set_attr ? ' id="' . $element_id . '"' : '') . '>' . "\n";
                } else {
                    $html .= '<select name="' . $this->inputName . '[' . $param_name . ']"' . $inp_attr . ($set_attr ? ' id="' . $element_id . '"' : '') . '>' . "\n";
                }
                foreach ($options as $optind => $optval) {
                    // support nested array values for the option-group tags
                    $group = null;
                    $sel_opts = [$optind => $optval];
                    if (is_array($optval)) {
                        $group = $optind;
                        $sel_opts = $optval;
                    }
                    if ($group) {
                        $html .= '<optgroup label="' . JHtml::fetch('esc_attr', JText::translate($group)) . '">' . "\n";
                    }
                    foreach ($sel_opts as $optkey => $poption) {
                        $checkval = $is_assoc ? $optkey : $poption;
                        $selected = false;
                        if (isset($this->settings[$param_name])) {
                            if (is_array($this->settings[$param_name])) {
                                $selected = in_array($checkval, $this->settings[$param_name]);
                            } else {
                                $selected = ($checkval == $this->settings[$param_name]);
                            }
                        } elseif (isset($default_paramv)) {
                            if (is_array($default_paramv)) {
                                $selected = in_array($checkval, $default_paramv);
                            } else {
                                $selected = ($checkval == $default_paramv);
                            }
                        }
                        $html .= '<option value="' . ($is_assoc ? $optkey : $poption) . '"'.($selected ? ' selected="selected"' : '').'>'.$poption.'</option>' . "\n";
                    }
                    if ($group) {
                        $html .= '</optgroup>' . "\n";
                    }
                }
                $html .= '</select>' . "\n";
                break;
            case 'password':
                $html .= '<div class="btn-wrapper input-append">';
                $html .= '<input type="password" name="' . $this->inputName . '[' . $param_name . ']" value="'.(isset($this->settings[$param_name]) ? JHtml::fetch('esc_attr', $this->settings[$param_name]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"' . $inp_attr . '/>';
                $html .= '<button type="button" class="btn btn-primary" onclick="vboParamTogglePwd(this);"><i class="' . VikBookingIcons::i('eye') . '"></i></button>';
                $html .= '</div>';
                // set flag for JS helper
                $$this->scripts[] = $param_config['type'];
                break;
            case 'number':
                $number_attr = [];
                if (isset($param_config['min'])) {
                    $number_attr[] = 'min="' . JHtml::fetch('esc_attr', $param_config['min']) . '"';
                }
                if (isset($param_config['max'])) {
                    $number_attr[] = 'max="' . JHtml::fetch('esc_attr', $param_config['max']) . '"';
                }
                if (isset($param_config['step'])) {
                    $number_attr[] = 'step="' . JHtml::fetch('esc_attr', $param_config['step']) . '"';
                }
                $html .= '<input type="number" name="' . $this->inputName . '[' . $param_name . ']" value="'.(isset($this->settings[$param_name]) ? JHtml::fetch('esc_attr', $this->settings[$param_name]) : JHtml::fetch('esc_attr', $default_paramv)).'" ' . implode(' ', $number_attr) . $inp_attr . '/>';
                break;
            case 'textarea':
                $html .= '<textarea name="' . $this->inputName . '[' . $param_name . ']"' . $inp_attr . '>'.(isset($this->settings[$param_name]) ? JHtml::fetch('esc_textarea', $this->settings[$param_name]) : JHtml::fetch('esc_textarea', $default_paramv)).'</textarea>';
                break;
            case 'visual_html':
                $tarea_cont = isset($this->settings[$param_name]) ? JHtml::fetch('esc_textarea', $this->settings[$param_name]) : JHtml::fetch('esc_textarea', $default_paramv);
                $tarea_attr = isset($param_config['attributes']) && is_array($param_config['attributes']) ? $param_config['attributes'] : [];
                $editor_opts = isset($param_config['editor_opts']) && is_array($param_config['editor_opts']) ? $param_config['editor_opts'] : [];
                $editor_btns = isset($param_config['editor_btns']) && is_array($param_config['editor_btns']) ? $param_config['editor_btns'] : [];
                $html .= VikBooking::getVboApplication()->renderVisualEditor($this->inputName . '[' . $param_name . ']', $tarea_cont, $tarea_attr, $editor_opts, $editor_btns);
                break;
            case 'codemirror':
                $editor = JEditor::getInstance('codemirror');
                $e_options = isset($param_config['options']) && is_array($param_config['options']) ? $param_config['options'] : [];
                $e_name = $this->inputName . '[' . $param_name . ']';
                $e_value = isset($this->settings[$param_name]) ? $this->settings[$param_name] : $default_paramv;
                $e_width = isset($e_options['width']) ? $e_options['width'] : '100%';
                $e_height = isset($e_options['height']) ? $e_options['height'] : 300;
                $e_col = isset($e_options['col']) ? $e_options['col'] : 70;
                $e_row = isset($e_options['row']) ? $e_options['row'] : 20;
                $e_buttons = isset($e_options['buttons']) ? (bool)$e_options['buttons'] : true;
                $e_id = isset($e_options['id']) ? $e_options['id'] : $this->inputName . '_' . $param_name;
                $e_params = isset($e_options['params']) && is_array($e_options['params']) ? $e_options['params'] : [];
                if (interface_exists('Throwable')) {
                    /**
                     * With PHP >= 7 supporting throwable exceptions for Fatal Errors
                     * we try to avoid issues with third party plugins that make use
                     * of the WP native function get_current_screen().
                     * 
                     * @wponly
                     */
                    try {
                        $html .= $editor->display($e_name, $e_value, $e_width, $e_height, $e_col, $e_row, $e_buttons, $e_id, $e_asset = null, $e_autor = null, $e_params);
                    } catch (Throwable $t) {
                        $html .= $t->getMessage() . ' in ' . $t->getFile() . ':' . $t->getLine() . '<br/>';
                        $html .= '<textarea name="' . $this->inputName . '[' . $param_name . ']"' . $inp_attr . '>' . (isset($this->settings[$param_name]) ? JHtml::fetch('esc_textarea', $this->settings[$param_name]) : JHtml::fetch('esc_textarea', $default_paramv)) . '</textarea>';
                    }
                } else {
                    $html .= $editor->display($e_name, $e_value, $e_width, $e_height, $e_col, $e_row, $e_buttons, $e_id, $e_asset = null, $e_autor = null, $e_params);
                }
                break;
            case 'hidden':
                $html .= '<input type="hidden" name="' . $this->inputName . '[' . $param_name . ']" value="'.(isset($this->settings[$param_name]) ? JHtml::fetch('esc_attr', $this->settings[$param_name]) : JHtml::fetch('esc_attr', $default_paramv)).'"' . $inp_attr . '/>';
                break;
            case 'checkbox':
                // always display a hidden input value turned off before the actual checkbox to support the "off" (0) status
                $html .= '<input type="hidden" name="' . $this->inputName . '[' . $param_name . ']" value="0" />';
                $html .= VikBooking::getVboApplication()->printYesNoButtons($this->inputName . '['.$param_name.']', JText::translate('VBYES'), JText::translate('VBNO'), (isset($this->settings[$param_name]) ? (int)$this->settings[$param_name] : (int)$default_paramv), 1, 0);
                break;
            default:
                $html .= '<input type="text" name="' . $this->inputName . '[' . $param_name . ']" value="'.(isset($this->settings[$param_name]) ? JHtml::fetch('esc_attr', $this->settings[$param_name]) : JHtml::fetch('esc_attr', $default_paramv)).'" size="20"' . $inp_attr . '/>';
                break;
        }

        return $html;
    }
}
