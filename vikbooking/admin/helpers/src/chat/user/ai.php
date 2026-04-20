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
 * Chat AI user.
 * 
 * @since 1.8.8
 */
class VBOChatUserAi extends VBOChatUseraware implements VBOChatNotifiable
{
    /**
     * The unique thread ID.
     * 
     * @var string|null
     */
    protected $threadId;

    /**
     * Class constructor.
     * 
     * @param  string|null  $threadId
     */
    public function __construct($threadId)
    {
        $this->threadId = $threadId;
    }

    /**
     * @inheritDoc
     */
    public function getID()
    {
        return -2;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'AI';
    }

    /**
     * @inheritDoc
     */
    public function getAvatar()
    {
        return VCM_ADMIN_URI . 'assets/css/channels/ai-avatar.png';
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function can(string $scope, ?VBOChatContext $context = null)
    {
        // enabled by default, only if the Channel Manager is installed with active AI support
        if (!static::isSupported()) {
            return false;
        }

        if (!$context) {
            return true;
        }

        if ($scope === 'chat.send') {
            // get context metadata
            $metadata = $context->getMetadata();

            // check whether the AI is still active for this conversation
            return (bool) ($metadata['use_ai'] ?? true);
        }

        return true;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatNotifiable
     */
    public function scheduleNotification(VBOChatMessage $message, VBOChatUser $user)
    {
        // ignore auto-replies in case the message wasn't wrote by a guest
        if ($user->getID() != -1) {
            return;
        }

        /** @var VCMChatContext */
        $context = $message->getContext();

        if (!$this->can('chat.send', $context)) {
            // prevent automatic replies
            return;
        }

        try {
            // clone the global chat mediator
            $chat = clone VBOFactory::getChatMediator();
            // authenticate as AI
            $chat->authenticate($this);

            // obtain the last 30 messages
            $messages = $chat->getMessages((new VBOChatSearch)->limit(30)->withContext($context));

            // map the messages for AI compliance
            $messages = array_map(function($message) {
                if ($message->getSenderID() == -1) {
                    $role = 'user';
                } else {
                    $role = 'assistant';
                }

                $data = [
                    'role' => $role,
                    'content' => $message->getMessage(),
                    'attachments' => [],
                    'user' => $message->getSenderName(),
                ];

                // include the attachments within the message data object
                foreach ($message->getAttachments() as $attachment) {
                    $mime = $attachment->getMimeType();

                    $supportedMimeTypes = [
                        'image/png',
                        'image/jpg',
                        'image/jpeg',
                        'application/pdf',
                    ];

                    // in case the mime type of the attachment is not supported,
                    // do not pass it to the AI service
                    if (!in_array($mime, $supportedMimeTypes)) {
                        continue;
                    }

                    $data['attachments'][] = [
                        'name' => $attachment->getName(),
                        'base64' => $attachment->getBase64(),
                    ];
                }

                return $data;
            }, $messages);

            // ask AI assistant to elaborate an answer (reverse the messages to have the oldest first)
            $answer = $this->generateAnswer(array_reverse($messages), $context);

            /** @var VBOChatMessage */
            $reply = $chat->createMessage([
                'context' => $context->getAlias(),
                'id_context' => $context->getID(),
                'message' => $answer->result, /* use `$reply->text` to get rid of HTML tags */
            ]);

            // iterate all attachments one by one
            foreach ($answer->attachments ?? [] as $attachment) {
                $reply->addAttachment(new VBOChatAttachment([
                    'path' => JPath::clean(VCM_SITE_PATH . '/helpers/chat/attachments/docs/'),
                    'name' => $attachment->name,
                    'filename' => $attachment->filename,
                    'extension' => $attachment->extension, 
                    'type' => $attachment->type,
                ]));
            }

            // send the AI reply to the guest
            $chat->send($reply);
        } catch (Throwable $error) {
            // make sure the VCM logger is supported
            if (class_exists('VCMLogDriverJsonlines')) {
                // create internal logger
                $logger = new VCMLogDriverJsonlines(
                    // groups logs by day
                    VBO_MEDIA_PATH . '/logs/ai/chatbot/' . JHtml::fetch('date', 'now', 'Y-m-d') . '.php',
                    [
                        // auto-delete log files that were updated more than 4 weeks ago
                        'gc_threshold' => '-4 weeks',
                    ]
                );

                // log the faced error
                $logger->error("An error occurred while the AI was trying to reply to the guest.\n\n> {$error}", [
                    'error' => $error->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generates an answer according to the provided conversation.
     * 
     * @param   object[]        $messages
     * @param   VBOChatContext  $context
     * 
     * @return  object
     */
    protected function generateAnswer(array $messages, VBOChatContext $context)
    {
        try {
            // ask AI assistant to elaborate an answer
            $reply = (new VCMAiModelService)->assistant($messages, $this->threadId, 'chatbot');
        } catch (Exception $error) {
            if ($error->getCode() == 423) {
                // we received a 423 error (Locked), meaning that the customer prefers to
                // talk with a human and doesn't want to receive further AI messages
                $context->setMetadata('use_ai', 0);

                // inform the user that an administrator has been warned
                $warning = JText::translate('VBO_CHAT_AI_LOCKED_REPLY');
            } else if ($error->getCode() == 451) {
                // we received a 451 error (Unavailable for legal reasons), meaning that AI detected
                // SPAM or phishing attempts under this conversation
                $context->setMetadata('banned', 1);

                // inform the user that an administrator has been warned
                $warning = JText::translate('VBO_CHAT_USER_BANNED_BY_AI');
            } else {
                // unknown error detected (timeout, hallucination, etc...), stop the AI to prevent loop errors
                $context->setMetadata('use_ai', 0);
                
                // inform the user that an administrator will reply soon
                $warning = JText::translate('VBO_CHAT_AI_REPLY_DOWNTIME_ERROR');
            }

            // normalize answer
            $reply = new stdClass;
            $reply->result = $warning;
            $reply->text = strip_tags($warning);
        }
        
        return $reply;
    }

    /**
     * Checks whether the VCM install owns AI capabilities.
     * 
     * @return  bool
     */
    public static function isSupported()
    {
        // enabled by default, only if the Channel Manager is installed with active AI support
        return class_exists('VikChannelManager')
            && defined('VikChannelManagerConfig::AI')
            && VikChannelManager::getChannel(VikChannelManagerConfig::AI);
    }
}
