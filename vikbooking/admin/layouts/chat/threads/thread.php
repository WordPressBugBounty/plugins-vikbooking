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
 * @var VBOChatMessage  $thread
 * @var array           $options
 */
extract($displayData);

/** @var VBOChatContext */
$context = $thread->getContext();

/** @var VBOChatUser[] */
$recipients = array_filter($context->getRecipients(), function($user) use ($thread) {
    return $user->getID() == $thread->getSenderID();
});

/** @var VBOChatUser */
$user = array_shift($recipients) ?: new VBOChatUserNull($thread->getSenderID(), $thread->getSenderName());

// obtain contents of last message
$message = $thread->getMessage();

// stringify attachments
$attachments = implode(', ', array_map(function($a) {
    return $a->getName();
}, $thread->getAttachments()));

// thread last update
$lastUpdate = $thread->getCreationDate();

$dateFormat = $options['dateformat'] ?? 'Y-m-d';

?>

<div
    class="chat-thread"
    data-context="<?php echo htmlspecialchars($context->getAlias()); ?>"
    data-id="<?php echo (int) $context->getID(); ?>"
    data-date="<?php echo htmlspecialchars($lastUpdate); ?>"
    data-read="<?php echo (int) $thread->isRead(); ?>"
>

    <div class="chat-thread-avatar">
        <?php if ($url = $user->getAvatar()): ?>
            <img src="<?php echo $url; ?>" decoding="async" loading="lazy" />
        <?php else:
            $names = preg_split("/\s+/", $user->getName() ?: $thread->getSenderName());
            ?>
            <span><?php echo strtoupper(substr((string) array_shift($names), 0, 1) . substr((string) array_pop($names), 0, 1)) ?></span>
        <?php endif; ?>
    </div>

    <div class="chat-thread-content">

        <div class="chat-thread-head">
            
            <div class="chat-thread-context">
                <strong class="context-subject"><?php echo $context->getSubject(); ?></strong>
                <span class="message-author">
                    <?php echo $user->getName() ?: $thread->getSenderName(); ?>
                </span>
            </div>
            
            <div class="chat-thread-datetime">
                <span class="last-update-time"><?php echo JHtml::fetch('date', $lastUpdate, $options['timeformat'] ?? 'H:i'); ?></span>
                <span class="last-update-date">
                    <?php
                    $date = JHtml::fetch('date', $lastUpdate, $dateFormat);

                    if ($date === JHtml::fetch('date', 'now', $dateFormat)) {
                        echo JText::translate('VBTODAY');
                    } else if ($date === JHtml::fetch('date', '-1 day', $dateFormat)) {
                        echo JText::translate('VBOYESTERDAY');
                    } else {
                        echo $date;
                    }
                    ?>
                </span>
            </div>

        </div>

        <div class="chat-thread-message-body" data-length="<?php echo strlen((string) $message); ?>">
            <?php echo JHtml::fetch('vikbooking.shorten', $message, 80); ?>
        </div>

        <div class="chat-thread-message-attachments" data-length="<?php echo strlen($attachments); ?>">
            <i class="fas fa-paperclip"></i>&nbsp;<span>
            <?php echo JHtml::fetch('vikbooking.shorten', $attachments, 80); ?></span>
        </div>

    </div>

</div>
        