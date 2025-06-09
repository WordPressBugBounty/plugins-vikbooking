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
 * Chat user admin wrapper.
 * 
 * @since 1.8
 */
class VBOChatUserAdmin extends VBOChatUseraware implements VBOChatNotifiable
{
    use VBOChatNotificationWebpush;

    /** @var JUser */
    protected $user;

    /**
     * Class constructor.
     * 
     * @param  JUser|null  The user instance.
     */
    public function __construct($user = null)
    {
        $this->user = $user ?: JFactory::getUser();

        if ($this->user->guest) {
            throw new RuntimeException('The logged in user is not an administrator.', 403);
        }
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function getID()
    {
        // 0 always identifies an administrator
        return 0;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function getName()
    {
        return $this->user->name;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function getAvatar()
    {
        $config = VBOFactory::getConfig();

        // prefer the back-end logo image
        $logo = $config->getString('backlogo');

        if (!$logo) {
            // back-end image not configured, use the front-end logo image
            $logo = $config->getString('sitelogo');
        }

        if ($logo)
        {
            // construct full image URI
            $logo = VBO_ADMIN_URI . 'resources/' . $logo;
        }

        return $logo;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatUser
     */
    public function can(string $scope, ?VBOChatContext $context = null)
    {
        return true;
    }

    /**
     * @inheritDoc
     * 
     * @see VBOChatNotifiable
     */
    public function scheduleNotification(VBOChatMessage $message, VBOChatUser $user)
    {
        /**
         * Enqueues a message for the admin within the notification center.
         * 
         * @see VBOChatNotificationWebpush
         */
        $this->sendWebPushNotification($message, $user);
    }
}
