<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2026 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking Apps controller for third-party applications.
 *
 * @since   1.18.6 (J) - 1.8.6 (WP)
 */
class VikBookingControllerApps extends JControllerAdmin
{
    /**
     * OAuth endpoint that will spawn the requested environment.
     * To be used as OAuth redirect URL for third-party Apps.
     */
    public function oauth()
    {
        $app = JFactory::getApplication();

        // access the involved environment of VikBooking to spawn
        $environment = $app->input->getString('env');

        // spawn the requested environment
        switch ($environment) {
            case 'dac':
                // spawn Door Access Control framework
                try {
                    VBODooraccessFactory::getInstance()->spawnOAuthCallback();
                } catch (Exception $e) {
                    // send error caught to output
                    VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
                }
                break;

            default:
                // spawn nothing
                break;
        }

        // trigger event to allow third-party plugins to spawn
        VBOFactory::getPlatform()->getDispatcher()->trigger('onAppsOauthSpawn', [$environment]);

        // close the request if unhandled
        VBOHttpDocument::getInstance($app)->close(200, 'OAuth link was reached.');
    }

    /**
     * Webhook endpoint that will spawn the requested environment.
     * To be used as Webhook endpoint URL for third-party Apps.
     */
    public function webhook()
    {
        $app = JFactory::getApplication();

        // access the involved environment of VikBooking to spawn
        $environment = $app->input->getString('env');

        // spawn the requested environment
        switch ($environment) {
            case 'dac':
                // spawn Door Access Control framework
                try {
                    VBODooraccessFactory::getInstance()->spawnWebhookCallback();
                } catch (Exception $e) {
                    // send error caught to output
                    VBOHttpDocument::getInstance($app)->close($e->getCode(), $e->getMessage());
                }
                break;

            default:
                // spawn nothing
                break;
        }

        // trigger event to allow third-party plugins to spawn
        VBOFactory::getPlatform()->getDispatcher()->trigger('onAppsWebhookSpawn', [$environment]);

        // close the request if unhandled
        VBOHttpDocument::getInstance($app)->close(200, 'Webhook endpoint URL was reached.');
    }
}
