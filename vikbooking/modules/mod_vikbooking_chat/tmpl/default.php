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

?>

<div class="vbo-chat-session-widget" id="vbo-chat-widget<?php echo (int) $moduleId; ?>">

    <div class="vbo-chat-widget-container">

        <div class="vbo-chat-widget-head">
            <span>
                <?php echo $params->get('title') ?: JText::translate('MOD_VIKBOOKING_CHAT_TITLE'); ?>
            </span>

            <a href="javascript:void(0)" data-role="widget.chat.dismiss" aria-label="Hide chat">
                <i class="<?php echo VikBookingIcons::i('chevron-down'); ?>" aria-hidden="true"></i>
            </a>
        </div>

        <div class="vbo-chat-widget-body">

            <?php
            // display session chat through layout
            echo JLayoutHelper::render(
                'chat.session',
                [
                    'session' => $session,
                    'suffix' => 'widget',
                    'options' => [
                        'autoread' => false,
                    ],
                ],
                null,
                [
                    'component' => 'com_vikbooking',
                    'client' => 'site',
                ]
            );
            
            if (!$session) {
                // display form to start a session
                ?>
                <div class="vbo-chat-session-start-form">

                    <div class="vbo-chat-session-form-intro"><?php echo JText::translate('MOD_VIKBOOKING_CHAT_SESSION_INTRO'); ?></div>
                    
                    <div class="vbo-chat-session-form-control">
                        <label for="vbo-chat-session-user-name<?php echo (int) $moduleId; ?>"><?php echo JText::translate('MOD_VIKBOOKING_CHAT_SESSION_NAME_LABEL'); ?></label>
                        <input type="text" id="vbo-chat-session-user-name<?php echo (int) $moduleId; ?>" name="name" required pattern=".{2,}" placeholder=" " />
                        <div class="vbo-chat-session-form-help"><?php echo JText::translate('MOD_VIKBOOKING_CHAT_SESSION_NAME_DESC'); ?></div>
                    </div>

                    <div class="vbo-chat-session-form-control">
                        <label for="vbo-chat-session-user-email<?php echo (int) $moduleId; ?>"><?php echo JText::translate('MOD_VIKBOOKING_CHAT_SESSION_MAIL_LABEL'); ?></label>
                        <input type="email" id="vbo-chat-session-user-email<?php echo (int) $moduleId; ?>" name="email" />
                        <div class="vbo-chat-session-form-help"><?php echo JText::translate('MOD_VIKBOOKING_CHAT_SESSION_MAIL_DESC'); ?></div>
                    </div>

                    <div class="vbo-chat-session-form-control">
                        <button type="button" class="btn vbo-pref-color-btn start-chat-session">
                            <?php echo JText::translate('MOD_VIKBOOKING_CHAT_SESSION_START_BTN'); ?>
                        </button>
                    </div>

                </div>
                <?php
            }
            ?>

            <div class="vbo-chat-session-placeholder" style="display: none;">
                
                <div class="vbo-chat-session-placeholder-inner">

                    <div class="vbo-chat-placeholder-greetings">
                        <div class="greetings-title">
                            <?php echo $params->get('greetings_title') ?: JText::translate('MOD_VIKBOOKING_CHAT_GREETINGS_TITLE'); ?>
                        </div>
                        
                        <div class="greetings-subtitle">
                            <?php echo $params->get('greetings_subtitle') ?: JText::translate('MOD_VIKBOOKING_CHAT_GREETINGS_SUBTITLE'); ?>
                        </div>
                    </div>

                    <?php if ($faqs): ?>
                        <div class="vbo-chat-placeholder-faqs">
                            <ul>
                                <?php foreach ($faqs as $faq): ?>
                                    <li>
                                        <a href="javascript:void(0)" data-question="<?php echo htmlspecialchars($faq->question); ?>">
                                            <i class="<?php echo VikBookingIcons::i('life-ring'); ?>" aria-hidden="true"></i>
                                            <?php echo $faq->summary; ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

    <div class="vbo-chat-widget-toggle">
        <a href="javascript:void(0)" data-role="widget.chat.show" data-unread="0" aria-label="Unread messages">
            <i class="<?php echo VikBookingIcons::i('comment'); ?>" aria-hidden="true"></i>
        </a>    
    </div>

</div>

<script>
    (function(w) {
        'use strict';

        if (typeof VBO_CHAT_WIDGET_CONFIG === 'undefined') {
            w['VBO_CHAT_WIDGET_CONFIG'] = {
                /**
                 * The selector to access the chat widget element.
                 * 
                 * @var string
                 */
                selector: '#vbo-chat-widget<?php echo (int) $moduleId; ?>',
                /**
                 * The AJAX end-point used to start a new session.
                 * 
                 * @var string
                 */
                startSessionUrl: '<?php echo VBOFactory::getPlatform()->getUri()->ajax('index.php?option=com_vikbooking&task=chat.start_session'); ?>',

                /**
                 * The AJAX end-point used to render a chat.
                 * 
                 * @var string
                 */
                renderChatUrl: '<?php echo VBOFactory::getPlatform()->getUri()->ajax('index.php?option=com_vikbooking&task=chat.render_chat'); ?>',
            };
        }
    })(window);
</script>