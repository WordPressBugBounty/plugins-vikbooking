<?php
/** 
 * @package   	VikUpdater
 * @subpackage 	plugin
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

require_once ABSPATH . 'wp-includes/pluggable.php';
require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

JLoader::import('adapter.plugin.installer.interface');

/**
 * Generic class used to install remote plugins without
 * the confirmation of the user.
 * 
 * @since 10.1.57
 */
abstract class JPluginInstaller implements JPluginInstallerInterface
{
    /**
     * @inheritDoc
     */
    final public function isInstalled()
    {
        // get the plugin slug
        $slug = $this->getSlug();

        // construct plugin url
        $pluginPath = JPath::clean(WP_PLUGIN_DIR . '/' . $slug . '.php');

        // validate the presence of the file
        return JFile::exists($pluginPath);
    }

    /**
     * @inheritDoc
     */
    final public function isActive()
    {
        return is_plugin_active($this->getSlug() . '.php');
    }

    /**
     * @inheritDoc
     */
    final public function install()
    {
        /** 
         * Create a custom skin to support a sort of silent installation.
         * 
         * @see WP_Upgrader_Skin
         */
        $skin = new class extends WP_Upgrader_Skin
        {
            /** @var string[] */
            private $trace = [];

            /**
             * @inheritDoc
             */
            public function header()
            {
                // suppress unexpected output
            }

            /**
             * @inheritDoc
             */
            public function footer()
            {
                // suppress unexpected output
            }

            /**
             * @inheritDoc
             */
            public function feedback($feedback, ...$args)
            {
                // suppress any errors
                $this->trace[] = $feedback;
            }

            /**
             * Returns the feedback trace.
             * 
             * @return  string[]
             */
            public function getTrace(): array
            {
                return $this->trace;
            }
        };

        // create installer handler
        $upgrader = new Plugin_Upgrader($skin);

        // check whether the plugin is already installed
        if ($this->isInstalled())
        {
            // plugin already installed, delete the folder first
            JFolder::delete(dirname(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->getSlug() . '.php'));
        }

        // download the plugin from the provided link and install it
        $result = $upgrader->install($this->getUrl());

        if ($result !== true)
        {
            if ($result instanceof WP_Error)
            {
                // propagate the error registered by the WP installer
                $code = $result->get_error_code();
                throw new RuntimeException($result->get_error_message(), is_numeric($code) ? (int) $code : 500);
            }
            else
            {
                // extract the error from our skin
                $trace = $skin->getTrace();
                throw new RuntimeException(end($trace) ?: 'Installation error.', 500);
            }
        }

        // make sure the plugin actually exists
        if (!$this->isInstalled())
        {
            // the plugin hasn't been properly downloaded
            throw new RuntimeException('Plugin [' . $this->getSlug() . '] not found', 404);
        }

        if (!$this->isActive())
        {
            // try to activate the plugin
            $result = activate_plugin($this->getSlug() . '.php');

            if ($result instanceof WP_Error)
            {
                // propagate the error registered during the activation
                $code = $result->get_error_code();
                throw new RuntimeException($result->get_error_message(), is_numeric($code) ? (int) $code : 500);
            }
        }
    }

    /**
     * Returns the plugin slug for the activation.
     * 
     * @return  string
     */
    abstract protected function getSlug();

    /**
     * Returns the remote URL that will be used to download the plugin.
     * 
     * @return  string
     */
    abstract protected function getUrl();
}
