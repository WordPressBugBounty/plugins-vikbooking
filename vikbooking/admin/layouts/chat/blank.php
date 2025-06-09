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
 * Display data attributes.
 * 
 * @var string  $icon
 * @var string  $title
 * @var string  $subtitle
 */
extract($displayData);

$icon = $icon ?? VikBookingIcons::i('comments');
$title = $title ?? JText::translate('VBO_CHAT_THREADS_EMPTY_TITLE');
$subtitle = $subtitle ?? JText::translate('VBO_CHAT_THREADS_EMPTY_SUBTITLE');

?>

<style>
    .vbo-chat-blank-pane {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        max-width: 600px;
        margin: auto;
        gap: 20px;
        text-align: center;
    }
    .vbo-chat-blank-pane .blank-icon {
        font-size: 128px;
        margin-bottom: 25px;
    }
    .vbo-chat-blank-pane .blank-title {
        font-size: 2.5em;
        line-height: 1em;
        font-weight: 500;
        margin-bottom: 10px;
    }
    .vbo-chat-blank-pane .blank-subtitle {
        font-size: 1.5em;
        line-height: 1.5em;
    }
</style>

<div class="vbo-chat-blank-pane">

    <?php if ($icon): ?>
        <div class="blank-icon">
            <i class="<?php echo $icon; ?>"></i>
        </div>
    <?php endif; ?>

    <?php if ($title): ?>
        <div class="blank-title">
            <?php echo $title; ?>
        </div>
    <?php endif; ?>

    <?php if ($subtitle): ?>
        <div class="blank-subtitle">
            <?php echo $subtitle; ?>
        </div>
    <?php endif; ?>

</div>
