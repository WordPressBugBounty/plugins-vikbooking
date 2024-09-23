<?php
/** 
 * @package     VikWP - Libraries
 * @subpackage  adapter.session
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * This interface declares the common actions needed to install a plugin.
 * 
 * @since 10.1.57
 */
interface JPluginInstallerInterface
{
    /**
     * Checks whether the plugin has been installed.
     * 
     * @return  bool
     */
    public function isInstalled();

    /**
     * Checks whether the plugin is already active.
     * 
     * @return  bool
     */
    public function isActive();

    /**
     * Completes the plugin installation and activation.
     * 
     * @return  void
     * 
     * @throws  \Exception
     */
    public function install();
}
