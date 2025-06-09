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
 * Chat user operator wrapper.
 * 
 * @since 1.8
 */
class VBOChatUserOperator extends VBOChatUseraware implements VBOChatNotifiable
{
    use VBOChatNotificationEmail;

    /** @var object */
    protected $operator;

    /**
     * Class constructor.
     * 
     * @param  mixed  The operator details.
     */
    public function __construct($operator = null)
    {
        if (!$operator) {
            // fetch details of the logged in operator
            $operator = VikBooking::getOperatorInstance()->getOperatorAccount();
        }

        if (is_numeric($operator)) {
            // fetch details of the specified operator
            $operator = VikBooking::getOperatorInstance()->getOne((int) $operator);
        }

        if (!$operator) {
            throw new RuntimeException('The logged in user is not an operator.', 403);
        }

        $this->operator = (object) $operator;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function getID()
    {
        return (int) $this->operator->id;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function getName()
    {
        return trim($this->operator->first_name . ' ' . $this->operator->last_name);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function getAvatar()
    {
        $image = $this->operator->pic ?? '';

        if ($image) {
            // check whether we have an image name or a full URI
            $image = preg_match("/^https?:\/\//i", $image) ? $image : VBO_SITE_URI . 'resources/uploads/' . $image;
        }

        return $image;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function can(string $scope, ?VBOChatContext $context = null)
    {
        if ($context) {
            // delegate the validation to the context
            return $context->can($scope, $this);
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
        if (!empty($this->operator->email)) {
            /**
             * If the e-mail address exists, send a notification to this operator.
             * 
             * @see VBOChatNotificationWebpush
             */
            $this->sendEmailNotification($message, $this->operator->email, $this->getName());
        }
    }
}
