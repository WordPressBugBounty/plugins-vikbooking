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
 * Wizard report ISTAT Ross 1000 help instruction.
 * 
 * @since 1.18.2 (J) - 1.8.2 (WP)
 */
class VBOHelpWizardDriverReportsItalyRoss1000 extends VBOHelpWizardInstructionaware
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
        $this->reportId = 'istat_ross1000';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportFormat
         */
        $this->autoExportFormat = 'transmitRecords';

        /**
         * @see VBOHelpWizardTraitReportConfigurable::$autoExportPayload
         */
        $this->autoExportPayload = [
            'fromdate' => '{Y-m-d -1 day}',
            'todate' => '{Y-m-d -1 day}',
            'listings' => [],
            '_reportAction' => 'transmitRecords',
        ];
    }
    
    /**
     * @inheritDoc
     */
    public function getID()
    {
        return 'reports.italy.ross1000';
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return 'ISTAT - ROSS1000';
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

        // check whether the main country is Italy and the main province is included within the list of supported cities
        return $config->get('maincountry') === 'IT' && in_array($config->get('mainstate'), array_keys($this->getSupportedCities()));
    }

    /**
     * @inheritDoc
     * 
     * @see VBOHelpWizardTraitReportConfigurable::checkSettingsConfigured()
     */
    protected function checkSettingsConfigured()
    {
        $config = VBOFactory::getConfig();

        $settings = $config->getArray('report_settings_istat_ross1000', []);

        if (empty($settings['endpoint'])) {
            $state = $config->get('mainstate');
            $cities = $this->getSupportedCities();

            switch ($cities[$state] ?? null) {
                case 'piemonte':
                    $url = 'https://piemontedatiturismo.regione.piemonte.it/ws/checkinV2?wsdl';
                    break;

                case 'firenze': 
                    $url = 'https://turismo5firenze.regione.toscana.it/ws/checkinV2?wsdl';
                    break;

                case 'pistoia':
                    $url = 'https://turismo5pistoia.regione.toscana.it/ws/checkinV2?wsdl';
                    break;

                case 'prato':
                    $url = 'https://turismo5prato.regione.toscana.it/ws/checkinV2?wsdl';
                    break;

                case 'abruzzo':
                    $url = 'https://app.regione.abruzzo.it/Turismo5/ws/checkinV2?wsdl';
                    break;

                case 'veneto':
                    $url = 'https://flussituristici.regione.veneto.it/ws/checkinV2?wsdl';
                    break;

                case 'emilia':
                    $url = 'https://datiturismo.regione.emilia-romagna.it/ws/checkinV2?wsdl';
                    break;

                case 'marche':
                    $url = 'https://istrice-ross1000.turismo.marche.it/ws/checkinV2?wsdl';
                    break;

                case 'lombardia':
                    $url = 'https://www.flussituristici.servizirl.it/Turismo5/app/ws/checkinV2?wsdl';
                    break;

                case 'calabria':
                    $url = 'https://sirdat.regione.calabria.it/ws/checkinV2?wsdl';
                    break;

                case 'sardegna':
                    $url = 'https://sardegnaturismo.ross1000.it/ws/checkinV2?wsdl';
                    break;

                case 'liguria':
                    $url = 'https://turismows.regione.liguria.it/ws/checkinV2?wsdl';
                    break;

                case 'lazio':
                    $url = 'https://lazioturismo.ross1000.it/ws/checkinV2?wsdl';
                    break;

                default:
                    $url = null;
            }

            $settings['endpoint'] = $url;
            $config->set('report_settings_istat_ross1000', $settings);
        }

        return !empty($settings['codstru']) && !empty($settings['user']) && !empty($settings['pwd']);
    }

    /**
     * Returns a lookup holding all the supported cities.
     * The keys of the array include the state 2 code.
     * The values of the array include the parent group (region or city).
     * 
     * @return  array
     */
    protected function getSupportedCities()
    {
        return [
            // Abruzzo

            'CH' => 'abruzzo', // Chieti
            'AQ' => 'abruzzo', // L'Aquila
            'PR' => 'abruzzo', // Pescara
            'TE' => 'abruzzo', // Teramo

            // Calabria

            'CZ' => 'calabria', // Catanzaro
            'CS' => 'calabria', // Cosenza
            'KR' => 'calabria', // Crotone
            'RC' => 'calabria', // Reggio Calabria
            'VV' => 'calabria', // Vibo Valentia

            // Emilia-Romagna

            'BO' => 'emilia', // Bologna
            'FE' => 'emilia', // Ferrara
            'FC' => 'emilia', // Forlì-Cesena
            'MO' => 'emilia', // Modena
            'PR' => 'emilia', // Parma
            'PC' => 'emilia', // Piacenza
            'RA' => 'emilia', // Ravenna
            'RE' => 'emilia', // Reggio Emilia
            'RN' => 'emilia', // Rimini

            // Lazio

            'FR' => 'lazio', // Frosinone
            'LT' => 'lazio', // Latina
            'RI' => 'lazio', // Rieti
            'RM' => 'lazio', // Roma
            'VT' => 'lazio', // Viterbo

            // Liguria

            'GE' => 'liguria', // Genova
            'IM' => 'liguria', // Imperia
            'SP' => 'liguria', // La Spezia
            'SV' => 'liguria', // Savona

            // Lombardia

            'BG' => 'lombardia', // Bergamo
            'BS' => 'lombardia', // Brescia
            'CO' => 'lombardia', // Como
            'CR' => 'lombardia', // Cremona
            'LC' => 'lombardia', // Lecco
            'LO' => 'lombardia', // Lodi
            'MN' => 'lombardia', // Mantova
            'MI' => 'lombardia', // Milano
            'MB' => 'lombardia', // Monza e Brianza
            'PV' => 'lombardia', // Pavia
            'SO' => 'lombardia', // Sondrio
            'VA' => 'lombardia', // Varese

            // Marche

            'AN' => 'marche', // Ancona
            'AP' => 'marche', // Ascoli Piceno
            'FM' => 'marche', // Fermo
            'MC' => 'marche', // Macerata
            'PU' => 'marche', // Pesaro e Urbino

            // Piemonte

            'AL' => 'piemonte', // Alessandria
            'AT' => 'piemonte', // Asti
            'BI' => 'piemonte', // Biella
            'CN' => 'piemonte', // Cuneo
            'NO' => 'piemonte', // Novara
            'TO' => 'piemonte', // Torino
            'VB' => 'piemonte', // Verbano-Cusio-Ossola
            'VC' => 'piemonte', // Vercelli

            // Sardegna

            'CA' => 'sardegna', // Cagliari
            'CI' => 'sardegna', // Carbonia-Iglesias
            'VS' => 'sardegna', // Medio Campidano
            'NU' => 'sardegna', // Nuoro
            'OG' => 'sardegna', // Ogliastra
            'OR' => 'sardegna', // Oristano
            'OT' => 'sardegna', // Olbia-Tempio
            'SS' => 'sardegna', // Sassari
            'SU' => 'sardegna', // Sud Sardegna
            
            // Toscana

            'FI' => 'firenze', // Firenze
            'PT' => 'pistoia', // Pistoia
            'PO' => 'prato',   // Prato
            
            // Veneto

            'BL' => 'veneto', // Belluno
            'PD' => 'veneto', // Padova
            'RO' => 'veneto', // Rovigo
            'TV' => 'veneto', // Treviso
            'VE' => 'veneto', // Venezia
            'VR' => 'veneto', // Verona
            'VI' => 'veneto', // Vicenza
        ];
    }
}
