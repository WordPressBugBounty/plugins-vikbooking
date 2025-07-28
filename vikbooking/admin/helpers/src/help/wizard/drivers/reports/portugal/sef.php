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
 * Wizard report Portugal SEF (Servico de Estrangeiros e Fronteiras) help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsPortugalSef extends VBOHelpWizardInstructionaware
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
        $this->reportId = 'pt_sef';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'auto';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d}',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.portugal.sef';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'Portugal - SEF';
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
        return VBOFactory::getConfig()->get('maincountry') === 'PT';
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::checkSettingsConfigured()
     */
    protected function checkSettingsConfigured()
    {
        return VBOFactory::getConfig()->getBool('report_pt_sef_settings');
    }
}
