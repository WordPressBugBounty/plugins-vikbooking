<?php
/**
 * @package     VikBooking
 * @subpackage  mod_vikbooking_chat
 * @author      E4J s.r.l
 * @copyright   Copyright (C) 2026 E4J s.r.l. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// no direct access
defined('ABSPATH') or die('No script kiddies please!');

$app = JFactory::getApplication();
$document = JFactory::getDocument();

if ($app->input->get('option') === 'com_vikbooking' && $app->input->get('view') === 'chat') {
    // do not display the module if we are inside a chat view
    return;
}

require_once VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'lib.vikbooking.php';

$document->addStyleSheet(VIKBOOKING_SITE_ASSETS_URI . 'vikbooking_styles.css', ['version' => VIKBOOKING_SOFTWARE_VERSION]);
VikBooking::loadPreferredColorStyles();
VikBooking::loadFontAwesome();

$options = [
    'version' => '1.0',
];

JHtml::fetch('script', VBO_MODULES_URI . 'modules/mod_vikbooking_chat/assets/script.js', $options);
JHtml::fetch('stylesheet', VBO_MODULES_URI . 'modules/mod_vikbooking_chat/assets/style.css', $options);

// security measure: register a new CSRF token to avoid spammers
JHtml::fetch('vbohtml.scripts.ajaxcsrf');

$document->addStyleDeclaration(
<<<CSS
.vbo-chat-session-widget {
    --vbo-chat-widget-btn-background: {$params->get('background')};
    --vbo-chat-widget-btn-color: {$params->get('color')};
}
CSS
);

// get widget id
$moduleId = $module->id ?? rand(1, 999);

// get session from cookie
$session = (new VBOChatSessionModel)->getFromCookie();

$faqs = [];

// prepare FAQs
for ($i = 1; $i <= 3; $i++) {
    $short = $params->get('faq_' . $i . '_short');
    $long = $params->get('faq_' . $i . '_long');

    if ($short && $long) {
        $faqs[] = (object) [
            'summary' => $short,
            'question' => $long,
        ];
    }
}

// load preferred layout
require JModuleHelper::getLayoutPath('mod_vikbooking_chat', $params->get('layout', 'default'));
