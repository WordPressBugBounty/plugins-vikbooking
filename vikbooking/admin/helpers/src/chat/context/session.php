<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Session chat context class.
 * 
 * @since 1.8.8
 */
class VBOChatContextSession extends VBOChatContextaware
{
    /** @var object */
    private $session;

    /**
     * @inheritDoc
     */
    final public function getAlias()
    {
        return 'session';
    }

    /**
     * @inheritDoc
     */
    public function getRecipients()
    {
        $recipients = [];

        // adds support to administrator (create a placeholder to simulate a false login)
        $recipients[] = new VBOChatUserAdmin((object) [
            'id' => 0,
            'guest' => false,
            'name' => '',
        ]);

        if ($session = $this->getSession()) {
            // adds support to guest user
            $recipients[] = new VBOChatUserGuest($session);

            // create AI user for auto-replies (inject session token as thread ID)
            $aiUser = new VBOChatUserAi($session->token);

            // make sure the AI is allowed to participate to a conversation
            if ($aiUser->can('chat.join', $this)) {
                // adds support to AI user
                $recipients[] = $aiUser;
            }
        }

        return $recipients;
    }

    /**
     * @inheritDoc
     */
    public function getSubject()
    {
        $session = $this->getSession();

        if (!$session) {
            return sprintf('<em>Session #%d (deleted)</em>', $this->getID());
        }

        // fetch session (user) name
        $sessionName = $session->name;

        if (!empty($session->phone)) {
            // the user wrote via WhatsApp, append icon after name
            $sessionName .= '&nbsp;<i class="' . VikBookingIcons::i('whatsapp', '', 'fab') . '" style="color: #090;font-size: larger;"></i>';
        }

        // fetch metadata to elaborate an appropriate topic
        $metadata = $this->getMetadata();

        // check if we have an interest on a specific query
        if (!empty($metadata['query'])) {
            $checkin = JFactory::getDate($metadata['query']['checkin'] ?? 'now');
            $checkout = JFactory::getDate($metadata['query']['checkout'] ?? 'now');

            // build the best dates format depending on checkin-checkout
            if ($checkin->format('Y') != $checkout->format('Y')) {
                $dates = $checkin->format('j M Y') . ' - ' . $checkout->format('j M Y');
            } else if ($checkin->format('m') != $checkout->format('m')) {
                $dates = $checkin->format('j M') . ' - ' . $checkout->format('j M') . ' ' . $checkin->format('Y');
            } else {
                $dates = $checkin->format('j') . '-' . $checkout->format('j') . ' ' . $checkin->format('M Y');
            }

            return $sessionName . ' • ' . $dates;
        }

        // use default inquiry
        return JText::sprintf('VBO_CHAT_SESSION_SUMMARY', $sessionName);
    }

    /**
     * @inheritDoc
     */
    public function getURL()
    {
        $url = 'index.php?option=com_vikbooking&view=chat';

        if ($session = $this->getSession()) {
            // inject session token
            $url .= '&token=' . $this->session->token;
        }

        return VBOFactory::getPlatform()->getUri()->route($url);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(bool $public = false)
    {
        $metadata = [
            'use_ai' => true,
            'banned' => false,
        ];

        if ($session = $this->getSession()) {
            // replace default values with session metadata
            $metadata = array_merge($metadata, $session->metadata);

            // normalize values
            $metadata['use_ai'] = (bool) $metadata['use_ai'];
            $metadata['banned'] = (bool) $metadata['banned'];
        }

        return $metadata;
    }

    /**
     * @inheritDoc
     */
    public function setMetadata(string $key, $value)
    {
        try {
            // insert/update metadata
            (new VBOChatSessionModel)->setMetadata($this->getID(), $key, $value);
        } catch (Exception $error) {
            // unable to save the metadata
        }
    }

    /**
     * @inheritDoc
     */
    public function useAssets(VBOChatUser $user)
    {
        $options = [
            'version' => VIKBOOKING_SOFTWARE_VERSION,
        ];

        $document = JFactory::getDocument();

        if (JFactory::getApplication()->isClient('site')) {
            $document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'vbocore.js', $options);
            $document->addScript(VIKBOOKING_ADMIN_ASSETS_URI . 'toast.js', $options);
            $document->addStylesheet(VIKBOOKING_ADMIN_ASSETS_URI . 'toast.css', $options);

            $document->addScriptDeclaration(
<<<JS
(function($) {
    'use strict';

    $(function() {
        VBOToast.create(VBOToast.POSITION_TOP_RIGHT);
    });
})(jQuery);
JS
            );
        }

        if ($user->getID() == -1) {
            JText::script('VBO_WANT_PROCEED');
            JText::script('VBO_CHAT_AI_THINKING');

            // load scripts for guest
            $document->addScript(VBO_SITE_URI . 'resources/chat/session/guest.js', $options);
            $document->addStylesheet(VBO_SITE_URI . 'resources/chat/session/guest.css', $options);
        } else if ($user->getID() == 0) {
            JText::script('VBO_CHAT_BAN_SESSION');
            JText::script('VBO_CHAT_UNBAN_SESSION');
            JText::script('VBO_CHAT_STOP_AI_SESSION');
            JText::script('VBO_CHAT_RESUME_AI_SESSION');
            JText::script('VBO_CHAT_QUERY_SUMMARY_TITLE');
            JText::script('VBO_CHAT_QUERY_SUMMARY_PREF_ROOMS');
            JText::script('VBO_CHAT_SUMMARIZE_TITLE');
            JText::script('VBO_CONDTEXT_RULE_STAYDATES');
            JText::script('VBO_NOTIFS_GROUP_GUESTS');
            JText::script('VBOSEASONCALNUMNIGHT');
            JText::script('VBOSEASONCALNUMNIGHTS');
            JText::script('VBMAILROOMNUM');
            JText::script('VBO_N_ADULTS');
            JText::script('VBO_N_ADULTS_1');
            JText::script('VBO_N_CHILDREN');
            JText::script('VBO_N_CHILDREN_1');
            JText::script('VBO_PET');
            JText::script('VBO_PETS');
            JText::script('VBO_MESSAGE_TEMPLATE');
            JText::script('JGLOBAL_SELECT_AN_OPTION');

            // load scripts for admin
            $document->addScript(VBO_SITE_URI . 'resources/chat/session/admin.js', $options);
            $document->addStylesheet(VBO_SITE_URI . 'resources/chat/session/admin.css', $options);
        }
    }

    /**
     * @inheritDoc
     */
    public function getActions(VBOChatUser $user)
    {
        $actions = [];

        $session = $this->getSession();

        if ($user->getID() == -1) {
            // add button to start a new session
            $actions[] = [
                'namespace' => 'session.restart',
                'text' => JText::translate('VBO_CHAT_RESTART_SESSION'),
                'icon' => VikBookingIcons::i('broom'),
            ];

            // add button to share the conversation
            $actions[] = [
                'namespace' => 'session.share',
                'text' => JText::translate('VBO_CHAT_SHARE_SESSION'),
                'icon' => VikBookingIcons::i('link'),
                'url' => $this->getURL(),
                'separator' => true,
            ];
        } if ($user->getID() == 0) {
            // add button to ban/unban the session
            $actions[] = [
                'namespace' => 'session.ban',
                'separator' => true,
            ];

            if (VBOChatUserAi::isSupported()) {
                // add button to stop/resume AI auto-replies
                $actions[] = [
                    'namespace' => 'session.ai.autoreply',
                ];

                // add button to summarize the conversation
                $actions[] = [
                    'namespace' => 'session.ai.summarize',
                    'text' => JText::translate('VBO_CHAT_SUMMARIZE'),
                    'icon' => VikBookingIcons::i('clipboard-list'),
                    'supported' => method_exists('VCMAiModelService', 'summarizeThread'),
                    'separator' => true,
                ];
            }

            // add button to create a quotation
            $actions[] = [
                'namespace' => 'session.query.quote',
                'text' => JText::translate('VBO_CREATE_QUOTE'),
                'icon' => VikBookingIcons::i('file-invoice-dollar'),
                'url'  => VBOFactory::getPlatform()->getUri()->admin('index.php?option=com_vikbooking&view=managequote&session_id=' . ($session->id ?? null), false),
            ];

            // add button to overview the search query
            $actions[] = [
                'namespace' => 'session.query.see',
                'text' => JText::translate('VBO_CHAT_SESSION_QUERY_SEE'),
                'icon' => VikBookingIcons::i('eye'),
                'separator' => true,
            ];

            // session started via phone, add button to send a template
            if (!empty($session->phone)) {
                // get session metadata
                $metadata = $this->getMetadata();

                // load configured messaging templates
                $templates = VCMMessagingAccountsModel::getInstance()->getConfigurationsData(
                    new VCMMessagingTemplateDecoratorChat($session),
                    [
                        'filter_items' => [
                            'account_id' => $metadata['waba_id'] ?? null,
                            'phone_id' => $metadata['phone_id'] ?? null,
                        ],
                    ]
                );

                $actions[] = [
                    'namespace' => 'session.messaging.sendtmpl',
                    'text' => JText::translate('VBO_CHAT_SESSION_SEND_TMPL'),
                    'icon' => VikBookingIcons::i('comment-alt'),
                    'templates' => $templates,
                ];
            }
        }

        return $actions;
    }

    /**
     * @inheritDoc
     */
    public function can(string $scope, VBOChatUser $user)
    {
        if ($user->getID() != -1) {
            return true;
        }

        // obtain token from cookie
        $token = (new VBOChatSessionModel)->getCookieToken();

        if (!$token) {
            return false;
        }

        // fetch details of the current session
        $session = $this->getSession();

        // validate cookie token against session token
        return !strcmp($session->token ?? '', $token);
    }

    /**
     * Returns the details of this session context.
     * 
     * @return  object|null
     */
    protected function getSession()
    {
        if ($this->session === null) {
            // fetch session details
            $this->session = (new VBOChatSessionModel)->getItem($this->getID()) ?: false;
        }

        return $this->session;
    }
}
