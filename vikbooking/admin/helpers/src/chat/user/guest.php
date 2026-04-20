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
 * Chat guest user wrapper.
 * 
 * @since 1.8.8
 */
class VBOChatUserGuest extends VBOChatUseraware implements VBOChatNotifiable
{
    use VBOChatNotificationEmail;
    use VBOChatNotificationWhatsapp;

    /** @var object|null */
    protected $session;

    /**
     * Class constructor.
     * 
     * @param  object|null  $session
     */
    public function __construct(?object $session = null)
    {
        if ($session === null) {
            // load current session from cookie
            $session = (new VBOChatSessionModel)->getFromCookie();
        }

        $this->session = $session;
    }

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return -1;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return ($this->session->name ?? '') ?: 'Guest';
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function can(string $scope, ?VBOChatContext $context = null)
    {
        // do not go ahead in case the context alias is different than "session"
        if (!$context || $context->getAlias() !== 'session') {
            return false;
        }

        // delegate the validation to the context
        if (!$context->can($scope, $this)) {
            return false;
        }

        if (in_array($scope, ['chat.sync', 'chat.render'])) {
            // bypass ban limitation for reading scopes
            return true;
        }

        // load context metadata
        $metadata = $context->getMetadata();

        if ($metadata['banned'] ?? false) {
            // session manually banned
            throw new RuntimeException(JText::translate('VBO_CHAT_SESSION_BANNED'), 403);
        }

        // observe write scopes
        if (in_array($scope, ['chat.send', 'chat.attachment.add', 'chat.session.end'])) {
            // load the last 3 messages under this conversation
            $messages = VBOFactory::getChatMediator()->getMessages(
                (new VBOChatSearch)->limit(3)->withContext($context)
            );

            if (count($messages) == 3) {
                // check whether all the messages has been wrote by the guest
                $messages = array_filter($messages, fn($msg) => $msg->getSenderID() != -1);

                if (!$messages) {
                    // all last 3 messages wrote by the guest, abort with an exception to present a specific error
                    throw new RuntimeException(JText::translate('VBO_CHAT_GUEST_REPLY_WAIT'), 403);
                }
            }
        }

        // allow attachments only after the first reply of the admin
        if ($scope === 'chat.attachment.add') {
            // obtain the first message wrote by a user different then guest
            $messages = VBOFactory::getChatMediator()->getMessages(
                (new VBOChatSearch)->limit(1)->withContext($context)->sender(-1, $equal = false)
            );

            // in case there are no host/operator/ai messages, prevent attachment upload
            if (!$messages) {
                // abort with an exception to present a specific message to the user
                throw new RuntimeException(JText::translate('VBO_CHAT_ATTACHMENT_GUEST_TOO_EARLY'), 403);
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function keepAlive(?VBOChatContext $context = null)
    {
        if ($this->session) {
            // update the session to extend the logout date time
            (new VBOChatSessionModel)->save([
                'id' => $this->session->id,
            ]);
        }
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatNotifiable
     */
    public function scheduleNotification(VBOChatMessage $message, VBOChatUser $user)
    {
        if (!empty($this->session->email)) {
            // in case the user is currently logged in, ignore notification
            if (JFactory::getDate()->toSql() < $this->session->logout) {
                return;
            }

            // deliver notification to guest
            $this->sendEmailNotification($message, $this->session->email, $this->session->name);
        }

        if (!empty($this->session->metadata['waba_id']) && !empty($this->session->phone)) {
            // deliver notification to WhatsApp
            $this->sendWhatsAppNotification($message, $this->session->metadata, $this->session->phone);
        }
    }
}
