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
 * Wizard report OTRS help instruction.
 * 
 * @since 1.18.3 (J) - 1.8.3 (WP)
 */
class VBOHelpWizardDriverReportsItalyOtrs extends VBOHelpWizardInstructionaware
{
    use VBOHelpWizardTraitReportConfigurable;

    /**
     * Class contructor.
     */
    public function __construct()
    {
        /**
         * @see VBOHelpWizardTraitReportConfigurable::$reportId
         */
        $this->reportId = 'istat_sicilia_otrs';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'registerCheckins';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d}',
            'listings' => [],
            '_reportAction' => 'registerCheckins',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.italy.otrs';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'Osservatorio Turistico Regione Sicilia (OTRS)';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('id-card');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return 998;
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        $config = VBOFactory::getConfig();

        // check whether the main country is Italy and the main province belongs to Sicily
        return $config->get('maincountry') === 'IT' && in_array($config->get('mainstate'), [
            'AG', // Agrigento
            'CL', // Caltanissetta
            'CT', // Catania
            'EN', // Enna
            'ME', // Messina
            'PA', // Palermo
            'RG', // Ragusa
            'SR', // Siracusa
            'TP', // Trapani
        ]);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::checkSettingsConfigured()
     */
    protected function checkSettingsConfigured()
    {
        $settings = VBOFactory::getConfig()->getArray('report_settings_istat_sicilia_otrs', []);

        return !empty($settings['hotelcode']) && !empty($settings['userid']) && !empty($settings['password']);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::postflight()
     */
    protected function postflight()
    {
        $payload = $this->autoExportPayload;
        $payload['_reportAction'] = 'registerCheckouts';

        $this->saveAutoExport($payload['_reportAction'], $payload);

        $payload = $this->autoExportPayload;
        $payload['_reportAction'] = 'registerEndDay';
        $payload['_reportData'] = [
            'end_date' => '{Y-m-d}',
        ];

        $this->saveAutoExport($payload['_reportAction'], $payload);
    }
}
