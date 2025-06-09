<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Chat e-mail notification trait.
 * 
 * @since 1.8
 */
trait VBOChatNotificationEmail
{
    /**
     * Sends an e-mail notification to the specified user.
     * 
     * @param   VBOChatMessage  $message  The message instance.
     * @param   string          $email    The e-mail address of the recipient.
     * @param   string|null     $name     An optional name of the recipient.
     * 
     * @return  bool
     */
    public function sendEmailNotification(VBOChatMessage $message, string $email, ?string $name = null)
    {
        // get sender e-mail
        $senderMail = VBOFactory::getConfig()->get('senderemail');

        // create a lookup with all the injectable tags
        $placeholders = [
            'recipient' => $name ?: $email,
            'sender' => $message->getSenderName(),
            'message' => (string) $message->getMessage(),
            'context' => $message->getContext()->getSubject(),
            'url' => $message->getContext()->getUrl(),
        ];

        if (strlen($placeholders['message']) === 0) {
            // message without content, only attachments added
            $placeholders['message'] = '<em>' . JText::translate('VBO_CHAT_MESSAGE_MAIL_NOTIFICATION_NOCONT') . '</em>';
        }

        // create e-mail subject
        $subject = JText::translate('VBO_CHAT_MESSAGE_MAIL_NOTIFICATION_SUBJECT');

        // create e-mail body
        $body = JText::translate('VBO_CHAT_MESSAGE_MAIL_NOTIFICATION_BODY');

        // inject placeholders to both the subject and body
        foreach ([&$subject, &$body] as &$str) {
            // iterate all tags
            foreach ($placeholders as $tagName => $tagValue) {
                // inject tag within the template
                $str = str_ireplace('{' . $tagName . '}', $tagValue, $str);
            }
        }

        // init mail data wrapper
        $mail = VBOMailWrapper::getInstance()
            ->setSender($senderMail, $message->getSenderName())
            ->setRecipient($email)
            ->setReply($senderMail)
            ->setSubject($subject)
            ->setContent($body);

        foreach ($message->getAttachments() as $attachment) {
            $mail->addAttachment($attachment->getPath());
        }

        if ($mail->isHtml()) {
            // replace new lines with <br> tags
            $mail->setContent(nl2br($mail->getContent()));
        }

        try {
            // send e-mail through the current platform service
            $result = VBOFactory::getPlatform()->getMailer()->send($mail);
        } catch (Exception $error) {
            // silently catch the error
            $result = false;
        }

        return $result;
    }
}
