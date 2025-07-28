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
 * Wizard main country (and state) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverGeneralBaseCountry extends VBOHelpWizardInstructionaware
{
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'general.base_country';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('map-signs');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return PHP_INT_MAX; // highest priority
    }

    /**
     * @inheritDoc
     */
    public function isConfigured()
    {
        return VBOFactory::getConfig()->getBool('maincountry');
    }

    /**
     * @inheritDoc
     */
    public function isDismissible()
    {
        // users cannot skip this instruction
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isProcessable(?string &$btnText = null)
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function process(array $args = [])
    {
        $countryCode = $args['country'] ?? null;

        if (empty($countryCode)) {
            // country not selected
            throw new InvalidArgumentException(JText::translate('VBOREPORTERRNODATA'), 400);
        }

        // make sure the country exists
        $countryId = VBOStateHelper::getCountryId($countryCode);

        if (!$countryId) {
            throw new UnexpectedValueException('The selected country (' . $countryCode . ') does not exist!', 404);
        }

        // get country states
        $states = VBOStateHelper::getCountryStates($countryId);

        if (!strcasecmp($args['scope'] ?? '', 'states')) {
            // return the available states to the caller
            return [
                'states' => $states,
            ];
        }

        $stateCode = $args['state'] ?? null;

        if ($states) {
            // take only the state matching the selected code
            $state = array_filter($states, function($state) use ($stateCode) {
                return $state['state_2_code'] == $stateCode;
            });

            if (!$state) {
                // state not selected
                throw new InvalidArgumentException(JText::translate('VBOREPORTERRNODATA'), 400);
            }
        }

        // save main country and state on VBO configuration
        $config = VBOFactory::getConfig();
        $config->set('maincountry', $countryCode);
        $config->set('mainstate', (string) $stateCode);
    }

    /**
     * Returns the most probable country where the business is based.
     * 
     * @return  string  Country 2 code.
     */
    public function guessCountry()
    {
        $host = JUri::getInstance()->getHost();

        // extract TLD from domain (only if 2 chars)
        if (preg_match("/\.([a-z]{2,2})\/?$/i", $host, $match)) {
            $countryCode = strtoupper(end($match));

            $lookup = [
                'UK' => 'GB',
            ];

            $countryCode = $lookup[$countryCode] ?? $countryCode;

            // check whether the TLD matches a country code (IT, DE, FR and so on)
            if (VBOStateHelper::getCountryId($countryCode)) {
                return $countryCode;
            }
        }

        $timezone = JFactory::getApplication()->get('offset');

        switch ($timezone) {
            case 'Europe/Rome':
                return 'IT';

            case 'Europe/Athens':
                return 'GR';

            case 'Europe/Madrid':
                return 'ES';

            case 'Europe/London':
                return 'GB';

            case 'Europe/Paris':
                return 'FR';

            case 'Europe/Berlin':
                return 'DE';
        }

        return '';
    }
}
