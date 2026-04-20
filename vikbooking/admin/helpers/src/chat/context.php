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
     * Returns a short description to identify the context.
     * 
     * @return  string
     */
    public function getSubject();

    /**
     * Returns the URL that can be used to access the chat interface.
     * 
     * @return  string
     */
    public function getURL();

    /**
     * Returns an associative array of metadata related to this context.
     * 
     * @param   bool  $public  When true, skip sensistive metadata.
     * 
     * @return  array
     */
    public function getMetadata(bool $public = false);

    /**
     * Sets or updates the specified metadata into the context.
     * 
     * @param   string  $key    The metadata key.
     * @param   mixed   $value  The metadata value.
     * 
     * @return  void
     */
    public function setMetadata(string $key, $value);

    /**
     * Forces the pre-loading of the resources to make the context scripts work.
     * 
     * @param   VBOChatUser  $user  Useful to differentiate the scripts to use
     *                              depending on the authenticated user.
     * 
     * @return  void
     */
    public function useAssets(VBOChatUser $user);

    /**
     * Returns an array of supported actions, which will be added to the
     * contextual menu displayed within the chat interface.
     * 
     * @param   VBOChatUser  $user  Useful to differentiate the actions to use
     *                              depending on the authenticated user.
     * 
     * @return  array
     */
    public function getActions(VBOChatUser $user);

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
