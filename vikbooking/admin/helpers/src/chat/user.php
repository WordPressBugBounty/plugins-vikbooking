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
 * Chat user interface.
 * 
 * @since 1.8
 */
interface VBOChatUser
{
    /**
     * Returns the identifier of the user.
     * 
     * @return  int
     */
    public function getID();

    /**
     * Returns the name of the user.
     * 
     * @return  string
     */
    public function getName();

    /**
     * Returns the avatar image (full URL) of the user.
     * 
     * @return  string
     */
    public function getAvatar();

    /**
     * Checks whether the user is allowed to perform the specified action
     * for the given context (if any).
     * 
     * @param   string               $scope    The action identifier.
     * @param   VBOChatContext|null  $context  The involved context, if any.
     * 
     * @return  bool  True if allowed, false otherwise.
     */
    public function can(string $scope, ?VBOChatContext $context = null);

    /**
     * Useful to mantain the user logged in.
     * Fires every time the chat is refreshed (synced)
     * 
     * @param   VBOChatContext|null  $context  The involved context, if any.
     * 
     * @return  void
     */
    public function keepAlive(?VBOChatContext $context = null);
}
