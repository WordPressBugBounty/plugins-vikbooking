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
 * This interface can be used to differentiate the behavior depending on
 * the context where a message may be sent.
 * 
 * @since 1.8
 */
interface VBOChatContext
{
    /**
     * Returns the foreign key to link a message to an external context.
     * 
     * @return  int
     */
    public function getID();

    /**
     * Returns the alias to identify the context type.
     * 
     * @return  string
     */
    public function getAlias();

    /**
     * Returns a list of recipients that may receive notifications
     * about new messages under this context.
     * 
     * @return  VBOChatUser[]
     */
    public function getRecipients();

    /**
     * Forces the pre-loading of the resources to make the context scripts work.
     * 
     * @return  void
     */
    public function useAssets();

    /**
     * Returns a short description to identify the context.
     * 
     * @return  string
     */
    public function getSubject();

    /**
     * Returns an array of supported actions, which will be added to the
     * contextual menu displayed within the chat interface.
     * 
     * @return  array
     */
    public function getActions();

    /**
     * Returns the URL that can be used to access the chat interface.
     * 
     * @return  string
     */
    public function getURL();

    /**
     * Checks whether the provided user is allowed to perform the given action
     * under the current context.
     * 
     * NOTE: calling `$user->can()` in this method will result in recursion.
     * 
     * @param   string       $scope  The action identifier.
     * @param   VBOChatUser  $user   The involved user.
     * 
     * @return  bool  True if allowed, false otherwise.
     */
    public function can(string $scope, VBOChatUser $user);
}
