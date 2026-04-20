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
 * Display vars.
 * 
 * @var VBOChatMediator|null  $chat     The chat mediator instance.
 * @var object|null           $session  The chat session.
 * @var string                $suffix   The selector suffix.
 * @var array                 $options  The chat settings.
 */
extract($displayData);

if (empty($chat)) {
    // get the chat mediator
    $chat = VBOFactory::getChatMediator();
}

// load the assets only
$chat->useAssets();

if (!array_key_exists('session', $displayData)) { 
    // get session from cookie
    $session = (new VBOChatSessionModel)->getFromCookie();
}

// sanitize suffix
$suffix = preg_replace("/[^a-zA-Z0-9_\-]+/", '', (string) ($suffix ?? ''));

?>

<div id="chat-session-target<?php echo $this->escape($suffix); ?>" class="chat-session-target">
    <?php
    if ($session) {
        // build chat for the given context
        $context = $chat->createContext('session', $session->id);

        // display the chat for the current session
        echo $chat->render($context, array_merge($options ?? [], [
            'assets' => false,
        ]));
    }
    ?>
</div>
