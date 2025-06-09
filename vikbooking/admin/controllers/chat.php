<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking generic chat controller.
 *
 * @since 1.8
 */
class VikBookingControllerChat extends JControllerAdmin
{
    /**
     * Task used to render the chat asynchronously.
     * 
     * @return  void
     */
    public function render_chat()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // fetch request data
        $contextId = $app->input->getUint('id_context', 0);
        $context = $app->input->get('context', '');

        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            /** @var VBOChatContext */
            $context = $chat->createContext($context, $contextId);

            // render the chat
            $html = $chat->render($context, [
                'assets' => false,
            ]);
        } catch (Exception $error) {
            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage());
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json(['html' => $html]);
    }

    /**
     * Task used to periodically search for new messages under a given context.
     * 
     * @return  void
     */
    public function sync_messages()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // fetch request data
        $contextId = $app->input->getUint('id_context', 0);
        $context = $app->input->get('context', '');
        $threshold = $app->input->getUint('threshold', 0);

        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            /** @var VBOChatContext */
            $context = $chat->createContext($context, $contextId);

            // obtain all the messages under the created context with an ID higher than the specified threshold
            $messages = $chat->getMessages(
                (new VBOChatSearch)
                    ->withContext($context)
                    ->message($threshold, '>')
            );
        } catch (Exception $error) {
            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage());
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json($messages);
    }

    /**
     * Task used to scan the pagination of a specified chat.
     * 
     * @return  void
     */
    public function load_older_messages()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // fetch request data
        $contextId = $app->input->getUint('id_context', 0);
        $context = $app->input->get('context', '');
        $start = $app->input->getUint('start', 0);
        $limit = $app->input->getUint('limit', 20);
        $datetime = $app->input->getString('datetime', null);

        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            /** @var VBOChatContext */
            $context = $chat->createContext($context, $contextId);

            // obtain all the messages under the created context with a creation date equal or lower than the specified threshold
            $messages = $chat->getMessages(
                (new VBOChatSearch)
                    ->start($start)
                    ->limit($limit)
                    ->withContext($context)
                    ->date($datetime, '<=')
            );
        } catch (Exception $error) {
            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage());
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json($messages);
    }

    /**
     * Task used to send a chat message under a given context.
     * 
     * @return  void
     */
    public function send()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // fetch request data
        $contextId = $app->input->getUint('id_context', 0);
        $context = $app->input->get('context', '');
        $message = $app->input->getString('message', '');
        $createdon = $app->input->getString('createdon', '');
        $attachments = $app->input->get('attachments', [], 'array');

        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            foreach ($attachments as &$attachment) {
                // create attachment from JSON
                $attachment = new VBOChatAttachment($attachment);
            }

            /** @var VBOChatMessage */
            $message = $chat->createMessage([
                'context' => $context,
                'id_context' => $contextId,
                'message' => $message,
                'attachments' => $attachments,
                'createdon' => $createdon,
            ]);

            // deliver the message
            $chat->send($message);
        } catch (Exception $error) {
            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage());
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json($message);
    }

    /**
     * AJAX end-point used to upload attachments before sending the message.
     * Files are uploaded onto the attachments folder of the front-end and a
     * JSON encoded objects array is returned with the details of each file.
     *
     * @return  void
     */
    public function upload_attachments()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // fetch request data
        $files = $app->input->files->get('attachments', [], 'raw');

        if (isset($files['name'])) {
            // we have a single associative array, we need to push it within a list,
            // because the upload iterates the $files array
            $files = [$files];
        }

        $attachments = [];

        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            foreach ($files as $file) {
                /** @var VBOChatAttachment */
                $attachment = $chat->uploadAttachment($file);

                // register attachment within the list
                $attachments[] = $attachment;
            }
        } catch (Exception $error) {
            // iterate all uploaded attachments and unlink them
            foreach ($attachments as $attachment) {
                $chat->removeAttachment($attachment);
            }

            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage());
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json($attachments);
    }

    /**
     * AJAX end-point used to remove the selected attachment.
     *
     * @return  void
     */
    public function remove_attachment()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // fetch request data
        $file = $app->input->get('attachment', [], 'array');

        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            // create attachment
            $attachment = new VBOChatAttachment($file);

            // attempt to remove the attachment
            if (!$chat->removeAttachment($attachment)) {
                throw new RuntimeException('Unable to remove the attachment: ' . $attachment->getName(), 403);
            }
        } catch (Exception $error) {
            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage());
        }

        $app->close();
    }

    /**
     * Task used to read the unread messages of a specified chat.
     * 
     * @return  void
     */
    public function read_messages()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // fetch request data
        $contextId = $app->input->getUint('id_context', 0);
        $context = $app->input->get('context', '');
        $datetime = $app->input->getString('datetime', null);

        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            /** @var VBOChatContext */
            $context = $chat->createContext($context, $contextId);

            // obtain a list holding the ID of all the read messages
            $messages = $chat->readMessages($context, $datetime);
        } catch (Exception $error) {
            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage());
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json($messages);
    }

    /**
     * Task used to scan the pagination of all the existing chats.
     * 
     * @return  void
     */
    public function load_chats()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        // fetch request data
        $start = $app->input->getUint('start', 0);
        $limit = $app->input->getUint('limit', 20);
        $options = $app->input->get('options', [], 'array');

        try {
            /** @var VBOChatMediator */
            $chat = VBOFactory::getChatMediator();

            // obtain all the chats
            $messages = $chat->getMessages(
                (new VBOChatSearch)
                    ->start($start)
                    ->limit($limit)
                    ->aggregate()
            );
        } catch (Exception $error) {
            VBOHttpDocument::getInstance($app)->close($error->getCode() ?: 500, $error->getMessage());
        }

        $layoutFile = new JLayoutFile('chat.threads.thread');

        $chats = [];

        // create thread layout here
        foreach ($messages as $thread) {
            $chats[] = [
                'alias' => $thread->getContext()->getAlias(),
                'id' => $thread->getContext()->getID(),
                'html' => $layoutFile->render([
                    'thread' => $thread,
                    'options' => $options,
                ]),
            ];
        }

        // output the result content
        VBOHttpDocument::getInstance($app)->json($chats);
    }
}
