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
 * Wizard help instruction interface.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
interface VBOHelpWizardInstruction
{
    /**
     * Returns the instruction unique identifier.
     * 
     * @return  string
     */
    public function getID();

    /**
     * Returns the instruction title.
     * 
     * @return  string
     */
    public function getTitle();

    /**
     * Returns the instruction icon (FontAwesome).
     * 
     * @return  string
     */
    public function getIcon();

    /**
     * Instructions with higher priority will be prompted first.
     * 
     * @return  int
     */
    public function getPriority();

    /**
     * Checks whether the instruction is supported by the current customer.
     * 
     * @return  bool
     */
    public function isSupported();

    /**
     * Checks whether the (supported) instruction has been already configured.
     * 
     * @return  bool
     */
    public function isConfigured();

    /**
     * Shows the instruction details.
     * 
     * @return  string
     */
    public function show();

    /**
     * Checks whether the instruction can be dismissed.
     * 
     * @return  bool
     */
    public function isDismissible();

    /**
     * Checks whether the instruction can be processed.
     * 
     * @param   string|null  &$btnText  A custom text to display for the "Process" button.
     * 
     * @return  bool
     */
    public function isProcessable(?string &$btnText = null);

    /**
     * Processes the instruction.
     * 
     * @param   array  $args
     * 
     * @return  mixed  The returned value will be propagated to the caller.
     */
    public function process(array $args = []);
}
