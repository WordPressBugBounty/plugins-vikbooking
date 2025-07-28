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
 * Wizard report Spain Mossos d'Esquadra (inside Catalunya) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsSpainMossosEsquadra extends VBOHelpWizardInstructionaware
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
        $this->reportId = 'es_mossos_desquadra';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'auto';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d -1 day}',
            'todate' => '{Y-m-d -1 day}',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.spain.mossos_esquadra';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'Mossos d\'Esquadra';
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

        // make sure the state is under "Catalunya"
        return $config->get('maincountry') === 'ES' && in_array($config->get('mainstate'), ['08', '17', '25', '43']);
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::checkSettingsConfigured()
     */
    protected function checkSettingsConfigured()
    {
        $settings = VBOFactory::getConfig()->getArray('report_settings_es_mossos_desquadra', []);

        return !empty($settings['codigo']) && !empty($settings['nombre']);
    }
}
