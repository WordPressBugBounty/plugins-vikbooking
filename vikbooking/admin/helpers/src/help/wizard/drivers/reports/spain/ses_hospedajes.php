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
 * Wizard report Spain SES Hospedajes (outside Catalunya) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsSpainSesHospedajes extends VBOHelpWizardInstructionaware
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
        $this->reportId = 'es_hospedajes';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'registerTravelerParts';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d}',
            'todate' => '{Y-m-d}',
            '_reportAction' => 'registerTravelerParts',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.spain.ses_hospedajes';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'SES Hospedajes';
    }

    /**
     * @inheritDoc
     */
    public function getIcon()
    {
        return VikBookingIcons::i('user-secret');
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return 999;
    }

    /**
     * @inheritDoc
     */
    public function isSupported()
    {
        $config = VBOFactory::getConfig();

        // make sure the state is not under "Catalunya"
        return $config->get('maincountry') === 'ES' && !in_array($config->get('mainstate'), ['08', '17', '25', '43']);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::checkSettingsConfigured()
     */
    protected function checkSettingsConfigured()
    {
        $settings = VBOFactory::getConfig()->getArray('report_settings_es_hospedajes', []);

        return !empty($settings['codigo']) && !empty($settings['service_arrendador']) && !empty($settings['service_username']) && !empty($settings['service_password']);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::postflight()
     */
    protected function postflight()
    {
        $payload = $this->autoExportPayload;
        $payload['_reportAction'] = 'registerAccommodationReservations';

        $this->saveAutoExport($payload['_reportAction'], $payload);
    }
}
