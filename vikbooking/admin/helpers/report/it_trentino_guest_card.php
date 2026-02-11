<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2025 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Trentino Guest Card (Italy) report implementation.
 * 
 * @see     EMISSIONE CON GESTIONALI ALBERGHI_rev_06.docx.pdf
 * 
 * @since   1.18.2 (J) - 1.8.2 (WP)
 */
class VikBookingReportItTrentinoGuestCard extends VikBookingReport
{
    /**
     * Property 'defaultKeySort' is used by the View that renders the report.
     */
    public $defaultKeySort = 'idbooking';

    /**
     * Property 'defaultKeyOrder' is used by the View that renders the report.
     */
    public $defaultKeyOrder = 'ASC';

    /**
     * @var  string  The endpoint base URL (test mode).
     */
    public $endpointBaseTest = 'https://demoricettivo.hi-logic.it/';

    /**
     * @var  string  The endpoint base URL (live - production mode).
     */
    public $endpointBaseLive = 'https://ricettivo.guestcard.info/';

    /**
     * @var  array  List of cached countries from remote WebService.
     */
    protected $countries_list = [];

    /**
     * @var  array  Associative list of cached card types from remote WebService (test and/or live).
     */
    protected $card_types_list = [];

    /**
     * Class constructor should define the name of the report and
     * other vars. Call the parent constructor to define the DB object.
     */
    public function __construct()
    {
        $this->reportFile = basename(__FILE__, '.php');
        $this->reportName = 'Trentino Guest Card';
        $this->reportFilters = [];

        $this->cols = [];
        $this->rows = [];
        $this->footerRow = [];

        parent::__construct();
    }

    /**
     * Returns the name of this report.
     *
     * @return  string
     */
    public function getName()
    {
        return $this->reportName;
    }

    /**
     * Returns the name of this file without .php.
     *
     * @return  string
     */
    public function getFileName()
    {
        return $this->reportFile;
    }

    /**
     * @inheritDoc
     */
    public function allowsProfileSettings()
    {
        // allow multiple report profile settings
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getSettingFields()
    {
        $card_types_err = null;
        try {
            // fetch or download the list of supported card types
            $card_types_test = $this->_callActionReturn('tipologieCard', 'card_types', $this->getScope(), ['mode' => 'test']);
            $card_types_live = $this->_callActionReturn('tipologieCard', 'card_types', $this->getScope(), ['mode' => 'live']);
        } catch (Exception $e) {
            // silently catch the error
            $card_types_test = [];
            $card_types_live = [];
            $card_types_err  = $e->getMessage();
        }

        $driverSettings = [
            'title' => [
                'type'  => 'custom',
                'label' => '',
                'html'  => '<p class="info">Configura le impostazioni per la generazione delle Trentino Guest Cards, inserendo le credenziali di accesso della struttura al sistema Guest Card.</p>',
            ],
            'test_mode' => [
                'type'    => 'checkbox',
                'label'   => 'Ambiente di Test',
                'help'    => 'Abilitando questa impostazione, la comunicazione dei dati avverrà con l\'ambiente di test. Disabilitarla per lavorare in ambiente di produzione.',
                'default' => 1,
            ],
            'auth_title' => [
                'type'  => 'custom',
                'label' => '',
                'html'  => '<p class="info">Configura le credenziali di autenticazione al sistema in base all\'ambiente (test o produzione). Le credenziali di produzione sono comunicate da E4jConnect.</p>',
            ],
            'username_auth_test' => [
                'type'    => 'text',
                'label'   => 'Username Autenticazione (Test)',
                'help'    => 'Inserisci lo username da utilizzare per l\'autenticazione al sistema (ambiente di test).',
                'default' => 'Gestionali',
                'conditional' => 'test_mode:1',
            ],
            'password_auth_test' => [
                'type'    => 'password',
                'label'   => 'Password Autenticazione (Test)',
                'help'    => 'Inserisci la password da utilizzare per l\'autenticazione al sistema (ambiente di test).',
                'default' => '12345678',
                'conditional' => 'test_mode:1',
            ],
            'username_auth_live' => [
                'type'    => 'text',
                'label'   => 'Username Autenticazione (Produzione)',
                'help'    => 'Inserisci lo username da utilizzare per l\'autenticazione al sistema (ambiente di produzione - comunicata da E4jConnect).',
                'default' => '',
                'conditional' => 'test_mode:0',
            ],
            'password_auth_live' => [
                'type'    => 'password',
                'label'   => 'Password Autenticazione (Produzione)',
                'help'    => 'Inserisci la password da utilizzare per l\'autenticazione al sistema (ambiente di produzione - comunicata da E4jConnect).',
                'default' => '',
                'conditional' => 'test_mode:0',
            ],
            'account_title' => [
                'type'  => 'custom',
                'label' => '',
                'html'  => '<p class="info">Configura le credenziali di accesso al sistema Guest Card della tua struttura in base all\'ambiente (test o produzione).</p>',
            ],
            'username_account_test' => [
                'type'    => 'text',
                'label'   => 'Username Guest Card (Test)',
                'help'    => 'Inserisci lo username della struttura al sistema Guest Card (ambiente di test).',
                'default' => 'Belvedere',
                'conditional' => 'test_mode:1',
            ],
            'password_account_test' => [
                'type'    => 'password',
                'label'   => 'Password Guest Card (Test)',
                'help'    => 'Inserisci la password della struttura al sistema Guest Card (ambiente di test).',
                'default' => 'Belvedere',
                'conditional' => 'test_mode:1',
            ],
            'username_account_live' => [
                'type'    => 'text',
                'label'   => 'Username Guest Card (Produzione)',
                'help'    => 'Inserisci lo username della struttura al sistema Guest Card (ambiente di produzione).',
                'default' => '',
                'conditional' => 'test_mode:0',
            ],
            'password_account_live' => [
                'type'    => 'password',
                'label'   => 'Password Guest Card (Produzione)',
                'help'    => 'Inserisci la password della struttura al sistema Guest Card (ambiente di produzione).',
                'default' => '',
                'conditional' => 'test_mode:0',
            ],
            'def_card_title' => [
                'type'  => 'custom',
                'label' => '',
                'html'  => '<p class="info">Seleziona la tipologia di card predefinita in base all\'ambiente (test o produzione).</p>',
            ],
        ];

        if ($card_types_test || $card_types_live) {
            // card types test mode
            $cards_available_test = [];
            foreach ($card_types_test as $card_type) {
                $card_type = (array) $card_type;
                if (empty($card_type['idTipologiaCard']) || empty($card_type['TipologiaCard'])) {
                    continue;
                }
                $cards_available_test[$card_type['idTipologiaCard']] = sprintf('%s (ID %s)', $card_type['TipologiaCard'], $card_type['idTipologiaCard']);
            }

            // card types live mode
            $cards_available_live = [];
            foreach ($card_types_live as $card_type) {
                $card_type = (array) $card_type;
                if (empty($card_type['idTipologiaCard']) || empty($card_type['TipologiaCard'])) {
                    continue;
                }
                $cards_available_live[$card_type['idTipologiaCard']] = sprintf('%s (ID %s)', $card_type['TipologiaCard'], $card_type['idTipologiaCard']);
            }

            // push card types for test mode
            $driverSettings['card_type_test'] = [
                'type'    => 'select',
                'label'   => 'Tipologia card predefinita (Test)',
                'options' => $cards_available_test ?: $cards_available_live,
            ];

            // push card types for live mode
            $driverSettings['card_type_live'] = [
                'type'    => 'select',
                'label'   => 'Tipologia card predefinita (Produzione)',
                'options' => $cards_available_live ?: $cards_available_test,
            ];
        } else {
            $driverSettings['card_types_err'] = [
                'type'  => 'custom',
                'label' => '',
                'html'  => '<p class="err">' . ($card_types_err ?: 'Impossibile caricare la lista di tipologie card supportate.') . '</p>',
            ];
        }

        return $driverSettings;
    }

    /**
     * @inheritDoc
     */
    public function getScopedActions($scope = null, $visible = true)
    {
        // fetch or download the list of supported card types to build an associative list
        try {
            $card_types = $this->_callActionReturn('tipologieCard', 'card_types', $this->getScope(), []);
        } catch (Exception $e) {
            $card_types = [];
        }
        
        $available_card_types = [];
        foreach ($card_types as $k => $card_type) {
            $card_type = (array) $card_type;
            $available_card_types[($card_type['idTipologiaCard'] ?? $k)] = $card_type['TipologiaCard'] ?? '---';
        }

        // list of custom actions for this report
        $actions = [
            [
                'id' => 'tipologieCard',
                'name' => 'Tipologie Card',
                'help' => 'Ottieni la lista delle tipologie di card che la struttura può emettere.',
                'icon' => VikBookingIcons::i('user-check'),
                'scopes' => [
                    'web',
                ],
            ],
            [
                'id' => 'emissioneGuestCards',
                'name' => 'Genera Guest Cards',
                'icon' => VikBookingIcons::i('id-card'),
                // flag to indicate that it requires the report data (lines)
                'export_data' => true,
                'scopes' => [
                    'web',
                    'cron',
                ],
            ],
            [
                'id' => 'attivaDisattivaCard',
                'name' => 'Attiva/Disattiva Card',
                'help' => 'Attiva o disattiva una determinata card.',
                'icon' => VikBookingIcons::i('plug'),
                'scopes' => [
                    'web',
                ],
                'params' => [
                    'card_qr_code' => [
                        'type'    => 'text',
                        'label'   => 'QR Code',
                        'help'    => 'Inserisci il codice della card in questione.',
                    ],
                    'card_action' => [
                        'type'    => 'select',
                        'label'   => 'Azione',
                        'help'    => 'Scegli se attivare o disattivare la card.',
                        'options' => [
                            'activate'   => 'Attiva Card',
                            'deactivate' => 'Disattiva Card',
                        ],
                    ],
                ],
                'params_submit_lbl' => 'Esegui operazione',
            ],
            [
                'id' => 'attributiCard',
                'name' => 'Attributi Card',
                'help' => 'Consulta le caratteristiche disponibili per una certa tipologia di card.',
                'icon' => VikBookingIcons::i('tags'),
                'scopes' => [
                    'web',
                ],
                'params' => [
                    'type_id' => [
                        'type'    => $available_card_types ? 'select' : 'text',
                        'options' => $available_card_types ?: null,
                        'label'   => $available_card_types ? 'Tipologia card' : 'ID Tipologia card',
                        'help'    => $available_card_types ? 'Seleziona la tipologia della card.' : 'Inserisci l\'id della tipologia di card.',
                    ],
                ],
                'params_submit_lbl' => 'Controlla attributi card',
            ],
            [
                'id' => 'emissioniEseguite',
                'name' => 'Guest Cards generate',
                'help' => 'Consulta un riepilogo delle emissioni eseguite in varie date con dei filtri di ricerca.',
                'icon' => VikBookingIcons::i('address-book'),
                'scopes' => [
                    'web',
                ],
                'params' => [
                    'em_es_from_dt' => [
                        'type'    => 'calendar',
                        'label'   => 'Dalla data',
                        'help'    => 'Filtro data di inizio validità delle card.',
                        'default' => date('Y-m-d'),
                    ],
                    'em_es_to_dt' => [
                        'type'    => 'calendar',
                        'label'   => 'Alla data',
                        'help'    => 'Filtro data di fine validità delle card.',
                        'default' => date('Y-m-d', strtotime('+1 month')),
                    ],
                    'em_es_bid' => [
                        'type'    => 'text',
                        'label'   => 'ID Prenotazione',
                        'help'    => 'Filtro opzionale per ricercare le card per ID prenotazione.',
                    ],
                    'record_pp' => [
                        'type'    => 'number',
                        'label'   => 'Record per pagina',
                        'help'    => 'Seleziona il numero di record (card) per pagina da ottenere.',
                        'min'     => 1,
                        'max'     => 1000,
                        'default' => 25,
                    ],
                    'page_number' => [
                        'type'    => 'number',
                        'label'   => 'Numero pagina',
                        'help'    => 'Seleziona il numero di pagina dei risultati.',
                        'min'     => 1,
                        'max'     => 1000,
                        'default' => 1,
                    ],
                ],
                'params_submit_lbl' => 'Ricerca le card emesse',
            ],
            [
                'id' => 'dettaglioCard',
                'name' => 'Dettaglio Card',
                'help' => 'Ricerca una card per QR Code per ottenerne i dettagli.',
                'icon' => VikBookingIcons::i('search'),
                'scopes' => [
                    'web',
                ],
                'params' => [
                    'card_qr_code' => [
                        'type'    => 'text',
                        'label'   => 'QR Code',
                        'help'    => 'Inserisci il codice della card da cercare.',
                    ],
                ],
                'params_submit_lbl' => 'Ricerca card',
            ],
            [
                'id' => 'fetchCountries',
                'name' => 'Lista nazioni',
                'help' => 'Metodo interno per scaricare la lista delle nazioni ed i loro relativi codici.',
                // flag to indicate that it's callable internally, but not graphically
                'hidden' => true,
                'scopes' => [
                    'web',
                ],
            ],
        ];

        // filter actions by scope
        if ($scope && (!strcasecmp($scope, 'cron') || !strcasecmp($scope, 'web'))) {
            $actions = array_filter($actions, function($action) use ($scope) {
                if (!($action['scopes'] ?? [])) {
                    return true;
                }

                return in_array(strtolower($scope), $action['scopes']);
            });
        }

        // filter by visibility
        if ($visible) {
            $actions = array_filter($actions, function($action) {
                return !($action['hidden'] ?? false);
            });
        }

        return array_values($actions);
    }

    /**
     * Returns the filters of this report.
     *
     * @return  array
     */
    public function getFilters()
    {
        if ($this->reportFilters) {
            // do not run this method twice, as it could load JS and CSS files.
            return $this->reportFilters;
        }

        $app = JFactory::getApplication();

        // get VBO Application Object
        $vbo_app = VikBooking::getVboApplication();

        // get the possibly injected report options
        $options = $this->getReportOptions();

        // load the jQuery UI Datepicker
        $this->loadDatePicker();

        // fetch or download the list of countries from the Guest Card web service
        $countries_list = $this->countries_list ?: $this->_callActionReturn('fetchCountries', 'countries', $this->getScope(), []);

        // fetch or download the list of supported card types
        try {
            $card_types = $this->_callActionReturn('tipologieCard', 'card_types', $this->getScope(), []);
        } catch (Exception $e) {
            $card_types = [];
        }

        // make the card types list associative
        $card_types_assoc = $card_types ? array_combine(array_column($card_types, 'idTipologiaCard'), array_column($card_types, 'TipologiaCard')) : [];

        // fetch the card attributes list
        $card_attributes_list = $this->fetchCardAttributesList();

        // open hidden fields
        $hidden_vals = '<div id="vbo-report-tgc-hidden" style="display: none;">';

        // build params container HTML structure
        $hidden_vals .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
        $hidden_vals .= '   <div class="vbo-params-wrap">';
        $hidden_vals .= '       <div class="vbo-params-container">';
        $hidden_vals .= '           <div class="vbo-params-block vbo-params-block-noborder">';

        // card type
        $hidden_vals .= '   <div id="vbo-report-tgc-card-type" class="vbo-report-tgc-selcont vbo-param-container" style="display: none;">';
        $hidden_vals .= '       <div class="vbo-param-label">Tipologia di card</div>';
        $hidden_vals .= '       <div class="vbo-param-setting">';
        $hidden_vals .= '           <select id="choose-card-type" onchange="vboReportChosenCardtype(this);"><option value=""></option>';
        foreach ($card_types as $card_type) {
            $card_type = (array) $card_type;
            $hidden_vals .= '       <option value="' . ($card_type['idTipologiaCard'] ?? 0) . '">' . ($card_type['TipologiaCard'] ?? '---') . '</option>' . "\n";
        }
        $hidden_vals .= '           </select>';
        $hidden_vals .= '       </div>';
        $hidden_vals .= '   </div>';

        // card attribute
        $hidden_vals .= '   <div id="vbo-report-tgc-card-attribute" class="vbo-report-tgc-selcont vbo-param-container" style="display: none;">';
        $hidden_vals .= '       <div class="vbo-param-label">Attributo card</div>';
        $hidden_vals .= '       <div class="vbo-param-setting">';
        $hidden_vals .= '           <select id="choose-card-attribute" onchange="vboReportChosenCardattribute(this);"><option value=""></option>';
        foreach ($card_attributes_list as $card_type_id => $card_type_attributes) {
            if (!is_array($card_type_attributes) || !$card_type_attributes) {
                continue;
            }
            $hidden_vals .= '           <optgroup label="' . ($card_types_assoc[$card_type_id] ?? $card_type_id) . '">' . "\n";
            foreach ($card_type_attributes as $card_attribute) {
                $hidden_vals .= '           <option value="' . ($card_attribute['idAttributo'] ?? 0) . '">' . ($card_attribute['AttributoEmissione'] ?? '---') . '</option>' . "\n";
            }
            $hidden_vals .= '           </optgroup>' . "\n";
        }
        $hidden_vals .= '           </select>';
        $hidden_vals .= '       </div>';
        $hidden_vals .= '   </div>';

        // card status
        $hidden_vals .= '   <div id="vbo-report-tgc-card-status" class="vbo-report-tgc-selcont vbo-param-container" style="display: none;">';
        $hidden_vals .= '       <div class="vbo-param-label">Stato generazione card</div>';
        $hidden_vals .= '       <div class="vbo-param-setting">';
        $hidden_vals .= '           <select id="choose-card-status" onchange="vboReportChosenCardstatus(this);"><option value=""></option>';
        $hidden_vals .= '               <option value="0">Genera guest card</option>' . "\n";
        $hidden_vals .= '               <option value="1">Non generare guest card</option>' . "\n";
        $hidden_vals .= '           </select>';
        $hidden_vals .= '       </div>';
        $hidden_vals .= '   </div>';

        // country id
        $hidden_vals .= '   <div id="vbo-report-tgc-country-id" class="vbo-report-tgc-selcont vbo-param-container" style="display: none;">';
        $hidden_vals .= '       <div class="vbo-param-label">Nazionalità ospite</div>';
        $hidden_vals .= '       <div class="vbo-param-setting">';
        $hidden_vals .= '           <select id="choose-country-id" onchange="vboReportChosenCountryid(this);"><option value=""></option>';
        foreach ($countries_list as $country_data) {
            $country_data = (array) $country_data;
            $hidden_vals .= '       <option value="' . ($country_data['idNazione'] ?? 0) . '">' . ($country_data['NomeNazione'] ?? '---') . '</option>' . "\n";
        }
        $hidden_vals .= '           </select>';
        $hidden_vals .= '       </div>';
        $hidden_vals .= '   </div>';

        // close params container HTML structure
        $hidden_vals .= '           </div>';
        $hidden_vals .= '       </div>';
        $hidden_vals .= '   </div>';
        $hidden_vals .= '</div>';

        // close hidden fields
        $hidden_vals .= '</div>';

        // From Date Filter (with hidden values)
        $filter_opt = array(
            'label' => '<label for="fromdate">'.JText::translate('VBOREPORTSDATEFROM').'</label>',
            'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />' . $hidden_vals,
            'type' => 'calendar',
            'name' => 'fromdate',
        );
        array_push($this->reportFilters, $filter_opt);

        // To Date Filter
        $filter_opt = array(
            'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
            'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
            'type' => 'calendar',
            'name' => 'todate'
        );
        array_push($this->reportFilters, $filter_opt);

        // Listings Filter
        $filter_opt = array(
            'label' => '<label for="listingsfilt">' . JText::translate('VBO_LISTINGS') . '</label>',
            'html' => '<span class="vbo-toolbar-multiselect-wrap">' . $vbo_app->renderElementsDropDown([
                'id'              => 'listingsfilt',
                'elements'        => 'listings',
                'placeholder'     => JText::translate('VBO_LISTINGS'),
                'allow_clear'     => 1,
                'attributes'      => [
                    'name' => 'listings[]',
                    'multiple' => 'multiple',
                ],
                'selected_values' => (array) ($app->input->get('listings', [], 'array') ?: $options->get('listings', [])),
            ]) . '</span>',
            'type' => 'select',
            'multiple' => true,
            'name' => 'listings',
        );
        array_push($this->reportFilters, $filter_opt);

        // append button to save the data when creating manual values
        $filter_opt = array(
            'label' => '<label class="vbo-report-tgc-manualsave" style="display: none;">' . JText::translate('VBCRONACTIONS') . '</label>',
            'html' => '<button type="button" class="btn vbo-config-btn vbo-report-tgc-manualsave" style="display: none;" onclick="vboTgcSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
        );
        array_push($this->reportFilters, $filter_opt);

        // jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
        $pfromdate = $app->input->getString('fromdate', '');
        $ptodate = $app->input->getString('todate', '');

        $js = 'var reportActiveCell = null, reportObj = {};
        var vbo_tgc_ajax_uri = "' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile) . '";
        var vbo_tgc_save_icn = "' . VikBookingIcons::i('save') . '";
        var vbo_tgc_saving_icn = "' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '";
        var vbo_tgc_saved_icn = "' . VikBookingIcons::i('check-circle') . '";
        jQuery(function() {
            // prepare main filters
            jQuery(".vbo-report-datepicker:input").datepicker({
                maxDate: "+1m",
                dateFormat: "'.$this->getDateFormat('jui').'",
                onSelect: vboReportCheckDates
            });
            ' . (!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "' . $pfromdate . '");' : '') . '
            ' . (!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "' . $ptodate . '");' : '') . '
            //prepare filler helpers
            jQuery("#vbo-report-tgc-hidden").children().detach().appendTo(".vbo-info-overlay-report");
            jQuery(".vbo-report-load-card-type").click(function() {
                reportActiveCell = this;
                jQuery(".vbo-report-tgc-selcont").hide();
                jQuery("#vbo-report-tgc-card-type").show();
                vboShowOverlay({
                    title: "Tipologia di card",
                    extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
                });
            });
            jQuery(".vbo-report-load-card-attribute").click(function() {
                reportActiveCell = this;
                jQuery(".vbo-report-tgc-selcont").hide();
                jQuery("#vbo-report-tgc-card-attribute").show();
                vboShowOverlay({
                    title: "Attributi card",
                    extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
                });
            });
            jQuery(".vbo-report-load-card-status").click(function() {
                reportActiveCell = this;
                jQuery(".vbo-report-tgc-selcont").hide();
                jQuery("#vbo-report-tgc-card-status").show();
                vboShowOverlay({
                    title: "Stato generazione card",
                    extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
                });
            });
            jQuery(".vbo-report-load-country-id").click(function() {
                reportActiveCell = this;
                jQuery(".vbo-report-tgc-selcont").hide();
                jQuery("#vbo-report-tgc-country-id").show();
                vboShowOverlay({
                    title: "Nazionalità ospite",
                    extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
                });
            });
        });

        function vboReportChosenCardtype(card) {
            var c_code = card.value;
            var c_val = card.options[card.selectedIndex].text;

            // apply the selected value to all guests of this booking
            if (reportActiveCell !== null) {
                var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
                if (isNaN(nowindex) || parseInt(nowindex) < 0) {
                    alert("Error, cannot find element to update.");
                    return false;
                }
                var rep_act_cell = jQuery(reportActiveCell);
                var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
                jQuery(\'[data-field="card-type"][data-fieldbid="\' + rep_guest_bid + \'"]\').each(function(k, v) {
                    let nowcell = jQuery(v);
                    nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(nowcell).closest("tr"));
                    nowcell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
                    if (!reportObj.hasOwnProperty(nowindex)) {
                        reportObj[nowindex] = {
                            bid: rep_guest_bid,
                            bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
                        };
                    }
                    reportObj[nowindex]["type_id"] = c_code;
                });
            }

            reportActiveCell = null;
            vboHideOverlay();
            jQuery("#choose-card-type").val("");
            jQuery(".vbo-report-tgc-manualsave").show();
        }

        function vboReportChosenCardattribute(card) {
            var c_code = card.value;
            var c_val = card.options[card.selectedIndex].text;

            // apply the selected value to all guests of this booking
            if (reportActiveCell !== null) {
                var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
                if (isNaN(nowindex) || parseInt(nowindex) < 0) {
                    alert("Error, cannot find element to update.");
                    return false;
                }
                var rep_act_cell = jQuery(reportActiveCell);
                var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
                jQuery(\'[data-field="card-attribute"][data-fieldbid="\' + rep_guest_bid + \'"]\').each(function(k, v) {
                    let nowcell = jQuery(v);
                    nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(nowcell).closest("tr"));
                    nowcell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
                    if (!reportObj.hasOwnProperty(nowindex)) {
                        reportObj[nowindex] = {
                            bid: rep_guest_bid,
                            bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
                        };
                    }
                    reportObj[nowindex]["attr_id"] = c_code;
                });
            }

            reportActiveCell = null;
            vboHideOverlay();
            jQuery("#choose-card-attribute").val("");
            jQuery(".vbo-report-tgc-manualsave").show();
        }

        function vboReportChosenCardstatus(status) {
            var c_code = status.value;
            var c_val = status.options[status.selectedIndex].text;

            // apply the selected value to all guests of this booking
            if (reportActiveCell !== null) {
                var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
                if (isNaN(nowindex) || parseInt(nowindex) < 0) {
                    alert("Error, cannot find element to update.");
                    return false;
                }
                var rep_act_cell = jQuery(reportActiveCell);
                var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
                jQuery(\'[data-field="card-status"][data-fieldbid="\' + rep_guest_bid + \'"]\').each(function(k, v) {
                    let nowcell = jQuery(v);
                    nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(nowcell).closest("tr"));

                    // format card status (skip generation) label depending on the choice
                    if (c_code == 1) {
                        // skip guest card generation
                        nowcell.addClass("vbo-report-load-elem-filled").find("span").html("<span class=\"label label-warning\">Da NON emettere</span>");
                    } else {
                        // generate guest card
                        nowcell.addClass("vbo-report-load-elem-filled").find("span").html("<span class=\"label label-info\">Da emettere</span>");
                    }

                    if (!reportObj.hasOwnProperty(nowindex)) {
                        reportObj[nowindex] = {
                            bid: rep_guest_bid,
                            bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
                        };
                    }
                    reportObj[nowindex]["skip_gen"] = c_code;
                });
            }

            reportActiveCell = null;
            vboHideOverlay();
            jQuery("#choose-card-status").val("");
            jQuery(".vbo-report-tgc-manualsave").show();
        }

        function vboReportChosenCountryid(country) {
            var c_code = country.value;
            var c_val = country.options[country.selectedIndex].text;

            // apply the selected value to all guests of this booking
            if (reportActiveCell !== null) {
                var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
                if (isNaN(nowindex) || parseInt(nowindex) < 0) {
                    alert("Error, cannot find element to update.");
                    return false;
                }
                var rep_act_cell = jQuery(reportActiveCell);
                var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
                jQuery(\'[data-field="country-id"][data-fieldbid="\' + rep_guest_bid + \'"]\').each(function(k, v) {
                    let nowcell = jQuery(v);
                    nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(nowcell).closest("tr"));
                    nowcell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
                    if (!reportObj.hasOwnProperty(nowindex)) {
                        reportObj[nowindex] = {
                            bid: rep_guest_bid,
                            bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
                        };
                    }
                    reportObj[nowindex]["country_id"] = c_code;
                });
            }

            reportActiveCell = null;
            vboHideOverlay();
            jQuery("#choose-country-id").val("");
            jQuery(".vbo-report-tgc-manualsave").show();
        }

        function vboReportCheckDates(selectedDate, inst) {
            if (selectedDate === null || inst === null) {
                return;
            }
            var cur_from_date = jQuery(this).val();
            if (jQuery(this).hasClass("vbo-report-datepicker-from") && cur_from_date.length) {
                var nowstart = jQuery(this).datepicker("getDate");
                var nowstartdate = new Date(nowstart.getTime());
                jQuery(".vbo-report-datepicker-to").datepicker("option", {minDate: nowstartdate});
            }
        }

        function vboTgcSaveData() {
            jQuery("button.vbo-report-tgc-manualsave").find("i").attr("class", vbo_tgc_saving_icn);
            VBOCore.doAjax(
                vbo_tgc_ajax_uri,
                {
                    call: "updateCardsData",
                    params: reportObj,
                    tmpl: "component"
                },
                function(response) {
                    if (!response || !response[0]) {
                        alert("An error occurred.");
                        return false;
                    }
                    jQuery("button.vbo-report-tgc-manualsave").addClass("btn-success").find("i").attr("class", vbo_tgc_saved_icn);
                },
                function(error) {
                    alert(error.responseText);
                    jQuery("button.vbo-report-tgc-manualsave").removeClass("btn-success").find("i").attr("class", vbo_tgc_save_icn);
                }
            );
        }

        // generate (download) function
        function vboGenerateGuestCards(type) {
            if (!confirm("Confermi la generazione delle guest cards per tutte le nuove prenotazioni idonee?")) {
                return false;
            }

            let use_blank = true;
            if (typeof type === "undefined") {
                type = 1;
            } else {
                use_blank = false;
            }

            if (use_blank) {
                document.adminForm.target = "_blank";
                document.adminForm.action += "&tmpl=component";
            }

            vboSetFilters({exportreport: type, filler: JSON.stringify(reportObj)}, true);

            setTimeout(function() {
                document.adminForm.target = "";
                document.adminForm.action = document.adminForm.action.replace("&tmpl=component", "");
                vboSetFilters({exportreport: "0", filler: ""}, false);
            }, 1000);
        }';

        $this->setScript($js);

        return $this->reportFilters;
    }

    /**
     * Loads the report data from the DB.
     * Returns true in case of success, false otherwise.
     * Sets the columns and rows for the report to be displayed.
     *
     * @return  bool
     */
    public function getReportData()
    {
        if ($this->getError()) {
            // export functions may set errors rather than exiting the process, and
            // the View may continue the execution to attempt to render the report.
            return false;
        }

        if ($this->rows) {
            // method must have run already
            return true;
        }

        // get the possibly injected report options
        $options = $this->getReportOptions();

        // injected options will replace request variables, if any
        $opt_fromdate = $options->get('fromdate', '');
        $opt_todate   = $options->get('todate', '');

        // input fields and other vars
        $pfromdate = $opt_fromdate ?: VikRequest::getString('fromdate', '', 'request');
        $ptodate = $opt_todate ?: VikRequest::getString('todate', '', 'request');

        $pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
        $pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
        $pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
        $pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
        $pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
        $plistings = ((array) VikRequest::getVar('listings', array())) ?: ((array) $options->get('listings', []));
        $plistings = array_filter(array_map('intval', $plistings));

        $df = $this->getDateFormat();
        $datesep = VikBooking::getDateSeparator();
        if (empty($ptodate)) {
            $ptodate = $pfromdate;
        }

        // get date timestamps
        $from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
        $to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
        if (empty($pfromdate) || empty($from_ts) || empty($to_ts) || $from_ts > $to_ts) {
            $this->setError(JText::translate('VBOREPORTSERRNODATES'));
            return false;
        }

        // load settings
        $settings = $this->loadSettings();

        // default card type id
        $def_card_type_id = null;

        if (!empty($settings['test_mode'])) {
            // test mode data validation
            if (empty($settings['username_auth_test']) || empty($settings['password_auth_test']) || empty($settings['username_account_test']) || empty($settings['password_account_test'])) {
                $this->setError('Inserisci i dati di autenticazione per la modalità di test dalle impostazioni.');
                return false;
            }

            // set default card type
            $def_card_type_id = $settings['card_type_test'] ?? null;
        } else {
            // live mode data validation
            if (empty($settings['username_auth_live']) || empty($settings['password_auth_live']) || empty($settings['username_account_live']) || empty($settings['password_account_live'])) {
                $this->setError('Inserisci i dati di autenticazione della tua struttura dalle impostazioni (modalità produzione).');
                return false;
            }

            // set default card type
            $def_card_type_id = $settings['card_type_live'] ?? null;
        }

        // query to obtain the records (all check-ins within the dates filter)
        $q = $this->dbo->getQuery(true)
            ->select([
                $this->dbo->qn('o.id'),
                $this->dbo->qn('o.custdata'),
                $this->dbo->qn('o.ts'),
                $this->dbo->qn('o.days'),
                $this->dbo->qn('o.checkin'),
                $this->dbo->qn('o.checkout'),
                $this->dbo->qn('o.totpaid'),
                $this->dbo->qn('o.roomsnum'),
                $this->dbo->qn('o.total'),
                $this->dbo->qn('o.idorderota'),
                $this->dbo->qn('o.channel'),
                $this->dbo->qn('o.country'),
                $this->dbo->qn('o.custmail'),
                $this->dbo->qn('o.phone'),
                $this->dbo->qn('or.idorder'),
                $this->dbo->qn('or.idroom'),
                $this->dbo->qn('or.adults'),
                $this->dbo->qn('or.children'),
                $this->dbo->qn('or.childrenage'),
                $this->dbo->qn('or.t_first_name'),
                $this->dbo->qn('or.t_last_name'),
                $this->dbo->qn('or.cust_cost'),
                $this->dbo->qn('or.cust_idiva'),
                $this->dbo->qn('or.extracosts'),
                $this->dbo->qn('or.room_cost'),
                $this->dbo->qn('co.idcustomer'),
                $this->dbo->qn('co.pax_data'),
                $this->dbo->qn('co.identity'),
                $this->dbo->qn('c.first_name'),
                $this->dbo->qn('c.last_name'),
                $this->dbo->qn('c.country', 'customer_country'),
                $this->dbo->qn('c.email', 'customer_email'),
                $this->dbo->qn('c.gender'),
                $this->dbo->qn('c.bdate'),
            ])
            ->from($this->dbo->qn('#__vikbooking_orders', 'o'))
            ->leftJoin($this->dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $this->dbo->qn('or.idorder') . ' = ' . $this->dbo->qn('o.id'))
            ->leftJoin($this->dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $this->dbo->qn('co.idorder') . ' = ' . $this->dbo->qn('o.id'))
            ->leftJoin($this->dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $this->dbo->qn('c.id') . ' = ' . $this->dbo->qn('co.idcustomer'))
            ->where($this->dbo->qn('o.status') . ' = ' . $this->dbo->q('confirmed'))
            ->where($this->dbo->qn('o.closure') . ' = 0')
            ->where($this->dbo->qn('o.checkin') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts)
            ->order($this->dbo->qn('o.checkin') . ' ASC')
            ->order($this->dbo->qn('o.id') . ' ASC')
            ->order($this->dbo->qn('or.id') . ' ASC');

        if ($plistings) {
            $q->where($this->dbo->qn('or.idroom') . ' IN (' . implode(', ', $plistings) . ')');
        }

        $this->dbo->setQuery($q);
        $records = $this->dbo->loadAssocList();
        if (!$records) {
            $this->setError(JText::translate('VBOREPORTSERRNORESERV'));
            $this->setError('Nessun check-in nelle date selezionate.');
            return false;
        }

        // nest records with multiple rooms booked inside sub-array
        $bookings = [];
        foreach ($records as $v) {
            if (!isset($bookings[$v['id']])) {
                $bookings[$v['id']] = [];
            }
            array_push($bookings[$v['id']], $v);
        }

        // free some memory up
        unset($records);

        try {
            // fetch or download the list of countries from the Guest Card web service
            $countries_list = $this->countries_list ?: $this->_callActionReturn('fetchCountries', 'countries', $this->getScope(), []);

            // fetch or download the list of supported card types
            $card_types = $this->_callActionReturn('tipologieCard', 'card_types', $this->getScope(), []);

            // fetch the card attributes list
            $card_attributes_list = $this->fetchCardAttributesList();
        } catch (Exception $e) {
            // abort the process
            $this->setError(sprintf('(%d) %s', $e->getCode() ?: 500, $e->getMessage()));
            return false;
        }

        // define the columns of the report
        $this->cols = [
            [
                'key' => 'idbooking',
                'label' => 'ID Prenotazione',
            ],
            [
                'key' => 'software_ref',
                'label' => 'Software Reference',
                'ignore_view' => 1,
            ],
            [
                'key' => 'first_name',
                'label' => 'Nome',
            ],
            [
                'key' => 'last_name',
                'label' => 'Cognome',
            ],
            [
                'key' => 'email',
                'label' => 'e-Mail',
            ],
            [
                'key' => 'country_id',
                'attr' => [
                    'class="center"',
                ],
                'label' => 'Nazione',
            ],
            [
                'key' => 'adults',
                'attr' => [
                    'class="center"',
                ],
                'label' => 'Adulti',
            ],
            [
                'key' => 'children',
                'label' => 'Bambini',
                'ignore_view' => 1,
            ],
            [
                'key' => 'children_age',
                'attr' => [
                    'class="center"',
                ],
                'label' => 'Età Minori',
            ],
            [
                'key' => 'card_from_dt',
                'attr' => [
                    'class="center"',
                ],
                'label' => 'Inizio Validità',
            ],
            [
                'key' => 'card_to_dt',
                'attr' => [
                    'class="center"',
                ],
                'label' => 'Fine Validità',
            ],
            [
                'key' => 'card_type',
                'attr' => [
                    'class="center"',
                ],
                'label' => 'Tipologia Card',
            ],
            [
                'key' => 'card_type_id',
                'label' => 'ID Tipologia Card',
                'ignore_view' => 1,
            ],
            [
                'key' => 'card_attr_id',
                'attr' => [
                    'class="center"',
                ],
                'label' => 'Attributo Card',
            ],
            [
                'key' => 'card_status',
                'attr' => [
                    'class="center"',
                ],
                'label' => 'Stato Card',
            ],
        ];

        // loop over the bookings to build the rows of the report
        foreach ($bookings as $booking_rooms) {
            // build card row for the current booking (one card per reservation, not for each room booked)
            $card_row = [];

            // attempt to decode the current booking-customer-identity information
            $tgc_card = [];
            if (!empty($booking_rooms[0]['identity'])) {
                $identity_data = (array) json_decode($booking_rooms[0]['identity'], true);
                $tgc_card = (array) ($identity_data['tgc'] ?? []);
            }

            // check if the card can be generated for the current booking
            $can_be_generated = empty($tgc_card['qrcode']) && $booking_rooms[0]['checkout'] > time();

            // booking ID
            $card_row[] = [
                'key' => 'idbooking',
                'callback' => function($val) {
                    // make sure to keep the data-bid attribute as it's used by JS to identify the booking ID
                    return '<a data-bid="' . $val . '" href="index.php?option=com_vikbooking&task=editorder&cid[]=' . $val . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i> ' . $val . '</a>';
                },
                'value' => $booking_rooms[0]['id'],
            ];

            // software reference (booking ID)
            $card_row[] = [
                'key' => 'software_ref',
                'value' => $booking_rooms[0]['id'],
                'ignore_view' => 1,
            ];

            // first name
            $card_row[] = [
                'key' => 'first_name',
                'value' => $booking_rooms[0]['t_first_name'] ?: $booking_rooms[0]['first_name'],
            ];

            // last name
            $card_row[] = [
                'key' => 'last_name',
                'value' => $booking_rooms[0]['t_last_name'] ?: $booking_rooms[0]['last_name'],
            ];

            // email
            $card_row[] = [
                'key' => 'email',
                'value' => $booking_rooms[0]['custmail'] ?: $booking_rooms[0]['customer_email'],
            ];

            // country ID
            $guest_country_id = $tgc_card['country_id'] ?? '';
            $guest_country = $booking_rooms[0]['country'] ?: $booking_rooms[0]['customer_country'];
            $card_row[] = [
                'key' => 'country_id',
                'attr' => [
                    'class="center vbo-report-load-country-id vbo-report-load-field ' . (empty($guest_country) ? 'vbo-report-load-elem-filled' : 'vbo-report-load-field-optional') . '"',
                    'data-field="country-id"',
                    'data-fieldbid="' . $booking_rooms[0]['id'] . '"',
                ],
                'callback' => function($val) use ($countries_list, $guest_country_id) {
                    if (empty($val) && empty($guest_country_id)) {
                        return '?';
                    }

                    foreach ($countries_list as $country_data) {
                        $country_data = (array) $country_data;
                        if (empty($guest_country_id) && stripos($country_data['NomeNazione'] ?? '', $val) === 0) {
                            return ucwords(strtolower($country_data['NomeNazione']));
                        }
                        if (!empty($guest_country_id) && $country_data['idNazione'] == $guest_country_id) {
                            return ucwords(strtolower($country_data['NomeNazione']));
                        }
                    }

                    // attempt to find the country data from the english name
                    $country_data = VBOStateHelper::getCountryData((int) VBOStateHelper::getCountryId($val));
                    if ($country_data) {
                        return $country_data['country_name'];
                    }

                    return $val;
                },
                'callback_export' => function($val) use ($countries_list, $guest_country_id) {
                    if (!empty($guest_country_id)) {
                        return $guest_country_id;
                    }

                    if (empty($val)) {
                        return '';
                    }

                    foreach ($countries_list as $country_data) {
                        $country_data = (array) $country_data;
                        if (stripos($country_data['NomeNazione'] ?? '', $val) === 0) {
                            return $country_data['idNazione'];
                        }
                    }

                    // attempt to find the country data from the english name
                    $country_data = VBOStateHelper::getCountryData((int) VBOStateHelper::getCountryId($val));
                    if ($country_data) {
                        $match_country_name = $country_data['country_name'];
                        // re-scan countries list
                        foreach ($countries_list as $country_data) {
                            $country_data = (array) $country_data;
                            if (stripos($country_data['NomeNazione'] ?? '', $match_country_name) === 0) {
                                return $country_data['idNazione'];
                            }
                        }
                    }

                    return '';
                },
                'value' => $guest_country,
            ];

            // adults
            $card_row[] = [
                'key' => 'adults',
                'attr' => [
                    'class="center"',
                ],
                'value' => array_sum(array_column($booking_rooms, 'adults')),
            ];

            // children
            $card_row[] = [
                'key' => 'children',
                'attr' => [
                    'class="center"',
                ],
                'value' => array_sum(array_column($booking_rooms, 'children')),
                'ignore_view' => 1,
            ];

            // gather children age
            $children_age = [];
            foreach ($booking_rooms as $booking_room) {
                if (empty($booking_room['childrenage'])) {
                    continue;
                }
                $room_ch_age = (array) json_decode($booking_room['childrenage'], true);
                if (empty($room_ch_age['age']) || !is_array($room_ch_age['age'])) {
                    continue;
                }
                // merge children age values
                $children_age = array_merge($children_age, array_map('intval', $room_ch_age['age']));
            }

            // children age
            $card_row[] = [
                'key' => 'children_age',
                'attr' => [
                    'class="center"',
                ],
                'callback' => function($val) {
                    if (empty($val)) {
                        return '0';
                    }

                    return implode(', ', $val);
                },
                'callback_export' => function($val) {
                    if (empty($val)) {
                        return '';
                    }
                    // comma separated age-numbers with no spaces
                    return implode(',', array_map('trim', $val));
                },
                'value' => $children_age,
            ];

            // card validity from date
            $card_row[] = [
                'key' => 'card_from_dt',
                'attr' => [
                    'class="center"',
                ],
                'callback' => function($val) use ($datesep, $df) {
                    return date(str_replace('/', $datesep, $df), $val);
                },
                'callback_export' => function($val) {
                    return date('Ymd', $val);
                },
                'value' => $booking_rooms[0]['checkin'],
            ];

            // card validity to date (inclusive)
            $card_row[] = [
                'key' => 'card_to_dt',
                'attr' => [
                    'class="center"',
                ],
                'callback' => function($val) use ($datesep, $df) {
                    return date(str_replace('/', $datesep, $df), $val);
                },
                'callback_export' => function($val) {
                    return date('Ymd', $val);
                },
                'value' => $booking_rooms[0]['checkout'],
            ];

            // card type
            $guest_card_id = $tgc_card['type_id'] ?? '';
            $card_row[] = [
                'key' => 'card_type',
                'attr' => [
                    'class="center vbo-report-load-card-type vbo-report-load-field ' . (empty($guest_card_id) && empty($def_card_type_id) ? 'vbo-report-load-elem-filled' : 'vbo-report-load-field-optional') . '"',
                    'data-field="card-type"',
                    'data-fieldbid="' . $booking_rooms[0]['id'] . '"',
                ],
                'callback' => function($val) use ($card_types, $def_card_type_id) {
                    if (empty($val)) {
                        if (!empty($def_card_type_id)) {
                            foreach ($card_types as $card_type) {
                                $card_type = (array) $card_type;
                                if (($card_type['idTipologiaCard'] ?? '') == $def_card_type_id) {
                                    return $card_type['TipologiaCard'] ?? '---';
                                }
                            }
                        }
                        return '?';
                    }

                    foreach ($card_types as $card_type) {
                        $card_type = (array) $card_type;
                        if (($card_type['idTipologiaCard'] ?? '') == $val) {
                            return $card_type['TipologiaCard'] ?? '---';
                        }
                    }

                    return '??';
                },
                'callback_export' => function($val) use ($card_types, $def_card_type_id) {
                    if (empty($val)) {
                        if (!empty($def_card_type_id)) {
                            foreach ($card_types as $card_type) {
                                $card_type = (array) $card_type;
                                if (($card_type['idTipologiaCard'] ?? '') == $def_card_type_id) {
                                    return $card_type['TipologiaCard'] ?? '---';
                                }
                            }
                        }
                        return '';
                    }

                    foreach ($card_types as $card_type) {
                        $card_type = (array) $card_type;
                        if (($card_type['idTipologiaCard'] ?? '') == $val) {
                            return $card_type['idTipologiaCard'] ?? '';
                        }
                    }

                    return '';
                },
                'value' => $guest_card_id,
            ];

            // card type ID
            $use_card_type_id = $guest_card_id ?: $def_card_type_id ?: 0;
            $card_row[] = [
                'key' => 'card_type_id',
                'value' => $use_card_type_id,
                'ignore_view' => 1,
            ];

            // card attribute ID
            $card_row[] = [
                'key' => 'card_attr_id',
                'attr' => [
                    'class="center' . (empty($tgc_card['qrcode']) && $can_be_generated ? ' vbo-report-load-card-attribute vbo-report-load-field vbo-report-load-elem-filled' : '') . '"',
                    'data-field="card-attribute"',
                    'data-fieldbid="' . $booking_rooms[0]['id'] . '"',
                ],
                'callback' => function($val) use ($use_card_type_id, $card_attributes_list) {
                    if ($val) {
                        foreach (($card_attributes_list[$use_card_type_id] ?? []) as $card_attribute) {
                            if (($card_attribute['idAttributo'] ?? '') == $val) {
                                return $card_attribute['AttributoEmissione'] ?? $val;
                            }
                        }

                        return $val;
                    }

                    return '----';
                },
                'callback_export' => function($val) {
                    return $val ?: null;
                },
                'value' => $tgc_card['attr_id'] ?? null,
            ];

            // card status
            $card_row[] = [
                'key' => 'card_status',
                'attr' => [
                    'class="center' . (empty($tgc_card['qrcode']) && $can_be_generated ? ' vbo-report-load-card-status vbo-report-load-field vbo-report-load-elem-filled' : '') . '"',
                    'data-field="card-status"',
                    'data-fieldbid="' . $booking_rooms[0]['id'] . '"',
                ],
                'callback' => function($val) use ($can_be_generated, $tgc_card) {
                    if (empty($val)) {
                        if ($can_be_generated) {
                            if (!empty($tgc_card['skip_gen'])) {
                                // card will not be generated for this booking as per admin choice
                                return '<span class="label label-warning">Da NON emettere</span>';
                            }

                            // card can be generated for this booking
                            return '<span class="label label-info">Da emettere</span>';
                        }

                        // card cannot be generated for this booking
                        return '<span class="label label-error">Non generabile</span>';
                    }

                    // card was already generated for this booking
                    return '<span class="label label-success">' . $val . '</span>';
                },
                'callback_export' => function($val) use ($can_be_generated, $tgc_card) {
                    if (empty($val)) {
                        return empty($tgc_card['skip_gen']) && boolval($can_be_generated);
                    }

                    return $val;
                },
                'value' => $tgc_card['qrcode'] ?? '',
            ];

            // push fields in the rows array as a new row
            $this->rows[] = $card_row;
        }

        return true;
    }

    /**
     * Generates the authority file, then sends it to output for download.
     * In case of errors, the process is not terminated (exit) to let the View display the
     * error message(s). The export type argument can eventually determine an action to run.
     *
     * @param   string  $export_type    Differentiates the type of export requested.
     *
     * @return  void|bool               Void in case of script termination, boolean otherwise.
     */
    public function customExport($export_type = null)
    {
        // build the guest cards data
        $guest_cards = $this->buildGuestCardsData();

        // build report action data, if needed
        $action_data = array_merge($this->getActionData($registry = false), ['cards' => $guest_cards]);

        /**
         * Custom export method can run a custom action.
         */
        if ($export_type && !is_numeric($export_type)) {
            try {
                // ensure the type of export is a callable scoped action, hidden or visible
                $actions = $this->getScopedActions($this->getScope(), $visible = false);
                $action_ids = array_column($actions, 'id');
                $action_names = array_column($actions, 'name');
                if (!in_array($export_type, $action_ids)) {
                    throw new Exception(sprintf('Cannot invoke the requested type of export [%s].', $export_type), 403);
                }

                // get the requested action readable name
                $action_name = $action_names[array_search($export_type, $action_ids)];

                if ($this->getScope() === 'web') {
                    // run the action and output the HTML response string
                    $html_result = $this->_callActionReturn($export_type, 'html', $this->getScope(), $action_data);

                    // build the action result data object
                    $js_result = json_encode([
                        'actionName' => $action_name,
                        'actionHtml' => $html_result,
                    ]);

                    // render modal script with the action result
                    JFactory::getDocument()->addScriptDeclaration(
<<<JS
;(function($) {
    $(function() {
        let result = $js_result;
        VBOCore.displayModal({
            suffix:      'report-custom-scopedaction-result',
            extra_class: 'vbo-modal-rounded vbo-modal-tall vbo-modal-nofooter',
            title:       result.actionName,
            body:        result.actionHtml,
        });
    });
})(jQuery);
JS
                    );

                    // abort and let the View render the result within a modal
                    return;
                }

                // let the report custom action run and return a boolean value if invoked by a cron
                return (bool) $this->_callActionReturn($export_type, 'success', $this->getScope(), $action_data);

            } catch (Exception $e) {
                // silently catch the error and set it
                $this->setError(sprintf('(%s) %s', $e->getCode() ?: 500, $e->getMessage()));

                // abort
                return false;
            }
        }

        // this report does not actually allow to download any files
        $this->setError('Nessun file da scaricare per le guest cards.');

        return false;
    }

    /**
     * Helper method invoked via AJAX by the controller.
     * Needed to save the manual entries for the cards data.
     * 
     * @param   array   $manual_data    the object representation of the manual entries.
     * 
     * @return  array                   one boolean array with the operation result.
     */
    public function updateCardsData($manual_data = [])
    {
        if (!is_array($manual_data) || !$manual_data) {
            VBOHttpDocument::getInstance()->close(400, 'Nothing to save!');
        }

        // re-build manual entries object representation
        $bids_guests = [];
        foreach ($manual_data as $guest_ind => $guest_data) {
            if (!is_numeric($guest_ind) || !is_array($guest_data) || empty($guest_data['bid']) || !isset($guest_data['bid_index']) || count($guest_data) < 2) {
                // empty or invalid manual entries array
                continue;
            }
            // the guest index in the reportObj starts from 0
            $use_guest_ind = ($guest_ind + 1 - (int)$guest_data['bid_index']);
            if (!isset($bids_guests[$guest_data['bid']])) {
                $bids_guests[$guest_data['bid']] = [];
            }
            // set manual entries for this guest number
            $bids_guests[$guest_data['bid']][$use_guest_ind] = $guest_data;
            // remove the "bid" and "bid_index" keys
            unset($bids_guests[$guest_data['bid']][$use_guest_ind]['bid'], $bids_guests[$guest_data['bid']][$use_guest_ind]['bid_index']);
        }

        if (!$bids_guests) {
            VBOHttpDocument::getInstance()->close(400, 'No manual entries to save found');
        }

        // access the object for managing the booking identity values
        $requestor = VikBooking::getCPinInstance();

        // loop through all bookings to update the cards data (one per booking)
        $bids_updated = 0;
        foreach ($bids_guests as $bid => $entries) {
            // get current booking identity data
            $identity_data = $requestor->getBookingIdentityData((int) $bid);

            // access the booking TGC information
            $tgc_card = (array) ($identity_data['tgc'] ?? []);

            foreach ($entries as $guest_ind => $guest_data) {
                // merge manual entries with existing card values
                $tgc_card = array_merge($tgc_card, (array) $guest_data);

                // update booking identity data
                $identity_data['tgc'] = $tgc_card;
                $requestor->setBookingIdentityData((int) $bid, $identity_data);

                // increase counter
                $bids_updated++;

                // one card per booking, not per guest
                break;
            }
        }

        return $bids_updated ? [true] : [false];
    }

    /**
     * Custom scoped action to fetch the list of countries and related information.
     * 
     * @param   string  $scope  Optional scope identifier (cron, web, etc..).
     * @param   array   $data   Optional associative list of data to process.
     * 
     * @return  array           The execution result properties.
     */
    protected function fetchCountries($scope = null, array $data = [])
    {
        $countries_list = [];

        // check if the cached list exists
        $cached_list_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tgc_nazioni.json';

        if (is_file($cached_list_path)) {
            // read and decode the cached list
            $countries_list = (array) json_decode((file_get_contents($cached_list_path) ?: ''), true);
        }

        if ($countries_list) {
            // returned the previously cached information
            return [
                'countries' => $countries_list,
            ];
        }

        // download the list from the public web service URL via GET
        try {
            $response = (new JHttp)->get('https://wscard.guestcard.info/datax/nazioni');

            if ($response->code != 200) {
                throw new Exception($response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
            }

            $countries_list = (array) json_decode($response->body ?: '[]', true);
        } catch (Exception $e) {
            // silently catch the error
            $countries_list = [];
        }

        if ($countries_list) {
            // cache the list internally
            file_put_contents($cached_list_path, json_encode($countries_list));
        }

        // cache list property internally
        $this->countries_list = $countries_list;

        return [
            'countries' => $countries_list,
        ];
    }

    /**
     * Custom scoped action to fetch the types (and IDs) of card supported by the property.
     * 
     * @param   string  $scope  Optional scope identifier (cron, web, etc..).
     * @param   array   $data   Optional associative list of data to process.
     * 
     * @return  array           The execution result properties.
     * 
     * @throws  Exception
     */
    protected function tipologieCard($scope = null, array $data = [])
    {
        // response properties
        $html = '';
        $card_types = [];

        // load settings
        $settings = $this->loadSettings();

        // determine the mode, test or live
        if ($data['mode'] ?? '') {
            // forced mode
            $mode = !strcasecmp($data['mode'], 'test') ? 'test' : 'live';
        } else {
            // read mode from settings
            $mode = !empty($settings['test_mode']) ? 'test' : 'live';
        }

        // check if the card types were already loaded for the current environment
        if ($this->card_types_list[$mode] ?? []) {
            $card_types = $this->card_types_list[$mode];
        }

        // validate settings because this method can be called when launching the parameters for the first time
        if ($mode === 'test') {
            // test mode data validation
            if (empty($settings['username_auth_test']) || empty($settings['password_auth_test']) || empty($settings['username_account_test']) || empty($settings['password_account_test'])) {
                throw new Exception('Inserisci i dati di autenticazione al sistema e salva per ottenere una lista di card supportate.', 500);
            }
        } else {
            // live mode data validation
            if (empty($settings['username_auth_live']) || empty($settings['password_auth_live']) || empty($settings['username_account_live']) || empty($settings['password_account_live'])) {
                throw new Exception('Inserisci i dati di autenticazione al sistema e salva per ottenere una lista di card supportate.', 500);
            }
        }

        // build cached list file name
        $cached_list_name = $mode === 'test' ? 'tgc_card_types_test.json' : 'tgc_card_types_live.json';

        // check if the cached list exists
        $cached_list_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $cached_list_name;

        // make sure the cached file is not older than 3 days
        if (!$card_types && is_file($cached_list_path) && (time() - (filemtime($cached_list_path) ?: time())) < (86400 * 3)) {
            // read and decode the cached list
            $card_types = (array) json_decode((file_get_contents($cached_list_path) ?: ''), true);
        }

        // check if we should download the card types
        if (!$card_types) {
            // build endpoint environment details
            if ($mode === 'test') {
                // test mode
                $clientId = $settings['username_auth_test'];
                $clientSecret = $settings['password_auth_test'];
                $username = $settings['username_account_test'];
                $password = $settings['password_account_test'];
                // endpoint
                $endpoint = sprintf(
                    $this->endpointBaseTest . 'ws/SoftwareGestionali/TipologieCard.ashx?username=%s&password=%s',
                    $username,
                    $password
                );
            } else {
                // production mode
                $clientId = $settings['username_auth_live'];
                $clientSecret = $settings['password_auth_live'];
                $username = $settings['username_account_live'];
                $password = $settings['password_account_live'];
                // endpoint
                $endpoint = sprintf(
                    $this->endpointBaseLive . 'ws/SoftwareGestionali/TipologieCard.ashx?username=%s&password=%s',
                    $username,
                    $password
                );
            }

            // download the list from the web service URL behind authentication
            try {
                $response = (new JHttp)->get($endpoint, [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                ]);

                if ($response->code != 200) {
                    throw new Exception($response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
                }

                $card_types = (array) json_decode($response->body ?: '[]', true);
            } catch (Exception $e) {
                // silently catch the error
                $card_types = [];
            }

            if ($card_types) {
                // cache the list internally
                file_put_contents($cached_list_path, json_encode($card_types));
            }
        }

        // scan all supported card types
        $html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
        $html .= '  <div class="vbo-params-wrap">';
        $html .= '      <div class="vbo-params-container">';
        $html .= '          <div class="vbo-params-block">';

        foreach ($card_types as $card_type) {
            // cast card-type object to array
            $card_type = (array) $card_type;

            // build card type HTML
            $html .= '          <div class="vbo-param-container">';
            $html .= '              <div class="vbo-param-label">ID ' . ($card_type['idTipologiaCard'] ?? '') . '</div>';
            $html .= '              <div class="vbo-param-setting">';
            $html .= '                  <span>' . ($card_type['TipologiaCard'] ?? '') . '</span>';
            $html .= '              </div>';
            $html .= '          </div>';
        }

        $html .= '          </div>';
        $html .= '      </div>';
        $html .= '  </div>';
        $html .= '</div>';

        // cache the list internally
        $this->card_types_list[$mode] = $card_types;

        return [
            'html'       => $html,
            'card_types' => $card_types,
        ];
    }

    /**
     * Custom scoped action to fetch the attributes of a given card type id.
     * 
     * @param   string  $scope  Optional scope identifier (cron, web, etc..).
     * @param   array   $data   Optional associative list of data to process.
     * 
     * @return  array           The execution result properties.
     * 
     * @throws  Exception
     */
    protected function attributiCard($scope = null, array $data = [])
    {
        // response properties
        $html = '';
        $card_attributes = [];

        // get the card type id
        $card_type_id = $data['type_id'] ?? null;

        if (empty($card_type_id)) {
            throw new Exception('ID tipologia card mancante.', 400);
        }

        // load settings
        $settings = $this->loadSettings();

        // determine the mode, test or live
        if ($data['mode'] ?? '') {
            // forced mode
            $mode = !strcasecmp($data['mode'], 'test') ? 'test' : 'live';
        } else {
            // read mode from settings
            $mode = !empty($settings['test_mode']) ? 'test' : 'live';
        }

        // build endpoint environment details
        if ($mode === 'test') {
            // test mode
            $clientId = $settings['username_auth_test'];
            $clientSecret = $settings['password_auth_test'];
            $username = $settings['username_account_test'];
            $password = $settings['password_account_test'];
            // endpoint
            $endpoint = sprintf(
                $this->endpointBaseTest . 'ws/SoftwareGestionali/AttributiCard.ashx?username=%s&password=%s&idTipologiaCard=%s',
                $username,
                $password,
                $card_type_id
            );
        } else {
            // production mode
            $clientId = $settings['username_auth_live'];
            $clientSecret = $settings['password_auth_live'];
            $username = $settings['username_account_live'];
            $password = $settings['password_account_live'];
            // endpoint
            $endpoint = sprintf(
                $this->endpointBaseLive . 'ws/SoftwareGestionali/AttributiCard.ashx?username=%s&password=%s&idTipologiaCard=%s',
                $username,
                $password,
                $card_type_id
            );
        }

        // download the attributes list from the web service URL behind authentication
        try {
            $response = (new JHttp)->get($endpoint, [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
            ]);

            if ($response->code != 200) {
                throw new Exception($response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
            }

            $card_attributes = (array) json_decode($response->body ?: '[]', true);
        } catch (Exception $e) {
            // propagate the error
            throw $e;
        }

        // update internal cache for card attributes
        $card_attributes_list = $this->fetchCardAttributesList();
        $card_attributes_list[$card_type_id] = $card_attributes;
        $this->updateCardAttributesList($card_attributes_list);

        // scan all supported card attributes, if any
        $html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
        $html .= '  <div class="vbo-params-wrap">';
        $html .= '      <div class="vbo-params-container">';
        $html .= '          <div class="vbo-params-block">';

        if (!$card_attributes) {
            $html .= '<p class="warn">La tipologia di card fornita non supporta attributi.</p>';
        }

        foreach ($card_attributes as $card_attribute) {
            // build card attribute HTML
            $html .= '          <div class="vbo-param-container">';
            $html .= '              <div class="vbo-param-label">ID ' . ($card_attribute['idAttributo'] ?? '') . '</div>';
            $html .= '              <div class="vbo-param-setting">';
            $html .= '                  <span>' . ($card_attribute['AttributoEmissione'] ?? '') . '</span>';
            $html .= '              </div>';
            $html .= '          </div>';
        }

        $html .= '          </div>';
        $html .= '      </div>';
        $html .= '  </div>';
        $html .= '</div>';

        return [
            'html'            => $html,
            'card_attributes' => $card_attributes,
        ];
    }

    /**
     * Custom scoped action to get the details of a given card QR Code identifier.
     * 
     * @param   string  $scope  Optional scope identifier (cron, web, etc..).
     * @param   array   $data   Optional associative list of data to process.
     * 
     * @return  array           The execution result properties.
     * 
     * @throws  Exception
     */
    protected function dettaglioCard($scope = null, array $data = [])
    {
        // response properties
        $html = '';
        $card_details = [];

        $card_qr_code = $data['card_qr_code'] ?? null;

        if (empty($card_qr_code)) {
            throw new Exception('Card QR code mancante per eseguire la ricerca.', 400);
        }

        // load settings
        $settings = $this->loadSettings();

        // build endpoint environment details
        if (!empty($settings['test_mode'])) {
            // test mode
            $clientId = $settings['username_auth_test'];
            $clientSecret = $settings['password_auth_test'];
            $username = $settings['username_account_test'];
            $password = $settings['password_account_test'];
            // endpoint
            $endpoint = sprintf(
                $this->endpointBaseTest . 'ws/SoftwareGestionali/GetCard.ashx?username=%s&password=%s&qrcode=%s',
                $username,
                $password,
                $card_qr_code
            );
        } else {
            // production mode
            $clientId = $settings['username_auth_live'];
            $clientSecret = $settings['password_auth_live'];
            $username = $settings['username_account_live'];
            $password = $settings['password_account_live'];
            // endpoint
            $endpoint = sprintf(
                $this->endpointBaseLive . 'ws/SoftwareGestionali/GetCard.ashx?username=%s&password=%s&qrcode=%s',
                $username,
                $password,
                $card_qr_code
            );
        }

        // download the list from the web service URL behind authentication
        try {
            $response = (new JHttp)->get($endpoint, [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
            ]);

            if ($response->code != 200) {
                if ($response->code == 404) {
                    throw new Exception('Nessuna card trovata con i valori immessi.', $response->code);
                }
                throw new Exception($response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
            }

            $card_details = (array) json_decode($response->body ?: '[]', true);

            if (empty($card_details['QrCode'])) {
                throw new Exception('Unexpected response from GetCard while fetching the card details.', 500);
            }
        } catch (Exception $e) {
            // propagate the error
            throw $e;
        }

        // scan all card details properties
        $html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
        $html .= '  <div class="vbo-params-wrap">';
        $html .= '      <div class="vbo-params-container">';
        $html .= '          <div class="vbo-params-block">';

        foreach ($card_details as $detail_type => $detail_value) {
            // build readable card detail value
            $read_value = is_null($detail_value) || is_scalar($detail_value) ? $detail_value : '[Non-Scalar value]';
            if (is_null($detail_value)) {
                $read_value = 'null';
            } elseif (is_bool($detail_value)) {
                $read_value = $detail_value ? 'true' : 'false';
            } elseif (is_array($detail_value) && $detail_value) {
                // in case of array values (i.e. "elEtaBambini"), make sure all entries are scalar
                $scalar_values = array_filter($detail_value, function($dv) {
                    return is_scalar($dv);
                });
                if ($scalar_values) {
                    $read_value = implode(', ', $scalar_values);
                }
            }

            // check if the detail requires a specific class
            $detail_cls = '';
            if ($detail_type == 'QrCode') {
                $detail_cls = 'badge badge-info';
            } elseif ($detail_type == 'Attiva') {
                $detail_cls = boolval($detail_value) ? 'badge badge-success' : 'badge badge-error';
            }

            // build card type HTML
            $html .= '          <div class="vbo-param-container">';
            $html .= '              <div class="vbo-param-label">' . $detail_type . '</div>';
            $html .= '              <div class="vbo-param-setting">';
            $html .= '                  <span class="' . $detail_cls . '">' . $read_value . '</span>';
            $html .= '              </div>';
            $html .= '          </div>';
        }

        $html .= '          </div>';
        $html .= '      </div>';
        $html .= '  </div>';
        $html .= '</div>';

        return [
            'html'         => $html,
            'card_details' => $card_details,
        ];
    }

    /**
     * Custom scoped action to activate or de-activate a guest card.
     * 
     * @param   string  $scope  Optional scope identifier (cron, web, etc..).
     * @param   array   $data   Optional associative list of data to process.
     * 
     * @return  array           The execution result properties.
     * 
     * @throws  Exception
     */
    protected function attivaDisattivaCard($scope = null, array $data = [])
    {
        // response properties
        $html = '';
        $result_action = 0;

        $card_qr_code = $data['card_qr_code'] ?? null;
        $card_action = !strcasecmp(($data['card_action'] ?? ''), 'activate') ? 1 : 0;

        if (empty($card_qr_code)) {
            throw new Exception('Card QR code mancante per eseguire l\'operazione.', 400);
        }

        // load settings
        $settings = $this->loadSettings();

        // build endpoint environment details
        if (!empty($settings['test_mode'])) {
            // test mode
            $clientId = $settings['username_auth_test'];
            $clientSecret = $settings['password_auth_test'];
            $username = $settings['username_account_test'];
            $password = $settings['password_account_test'];
            // endpoint
            $endpoint = $this->endpointBaseTest . 'ws/SoftwareGestionali/AttivaDisattivaCard.ashx';
        } else {
            // production mode
            $clientId = $settings['username_auth_live'];
            $clientSecret = $settings['password_auth_live'];
            $username = $settings['username_account_live'];
            $password = $settings['password_account_live'];
            // endpoint
            $endpoint = $this->endpointBaseLive . 'ws/SoftwareGestionali/AttivaDisattivaCard.ashx';
        }

        // execute the operation on the web service URL behind authentication
        try {
            $response = (new JHttp)->post(
                $endpoint,
                [
                    'QrCode' => $card_qr_code,
                    'Stato'  => $card_action,
                    'username' => $username,
                    'password' => $password,
                ],
                [
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                ]
            );

            // always attempt to decode the response body no matter what was the response code
            $card_result = (array) json_decode($response->body ?: '[]', true);

            // check if an error reason is available
            $error_reason = !empty($card_result['motivo']) && is_array($card_result['motivo']) ? implode(', ', $card_result['motivo']) : '';

            if ($response->code != 200) {
                throw new Exception($error_reason ?: $response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
            }

            if (!$card_result || empty($card_result['esito'])) {
                throw new Exception(sprintf('Errore durante %s della guest card con codice %s: %s.', ($card_action ? 'l\'attivazione' : 'la disattivazione'), $card_qr_code, ($error_reason ?: $response->body ?: 'unknown')), 500);
            }

            if ($response->code != 200) {
                throw new Exception($response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
            }
        } catch (Exception $e) {
            // propagate the error
            throw $e;
        }

        // set the HTML response
        $html .= '<p class="successmade">' . sprintf('Operazione di <strong>%s</strong> della guest card con codice <strong>%s</strong> eseguita con successo.', ($card_action ? 'attivazione' : 'disattivazione'), $card_qr_code) . '</p>';

        // set the result action value
        $result_action = $card_action;

        return [
            'html'          => $html,
            'result_action' => $result_action,
        ];
    }

    /**
     * Custom scoped action to get the list of the issued guest cards.
     * 
     * @param   string  $scope  Optional scope identifier (cron, web, etc..).
     * @param   array   $data   Optional associative list of data to process.
     * 
     * @return  array           The execution result properties.
     * 
     * @throws  Exception
     */
    protected function emissioniEseguite($scope = null, array $data = [])
    {
        // response properties
        $html = '';
        $cards_list = [];

        $from_dt = $data['em_es_from_dt'] ?? null;
        $to_dt   = $data['em_es_to_dt'] ?? null;
        $bid     = (int) ($data['em_es_bid'] ?? null);

        if (empty($from_dt) && empty($to_dt) && empty($bid)) {
            throw new Exception('Popolare dei filtri per eseguire la ricerca delle cards emesse.', 400);
        }

        // check page number and list limit
        $page_num = max(1, intval($data['page_number'] ?? 1));
        $list_lim = max(10, intval($data['record_pp'] ?? 0));

        // normalize date filters, if set
        $from_dt_ymd = $from_dt ? date('Ymd', VikBooking::getDateTimestamp($from_dt)) : '';
        $to_dt_ymd = $to_dt ? date('Ymd', VikBooking::getDateTimestamp($to_dt)) : '';

        // load settings
        $settings = $this->loadSettings();

        // build endpoint environment details
        if (!empty($settings['test_mode'])) {
            // test mode
            $clientId = $settings['username_auth_test'];
            $clientSecret = $settings['password_auth_test'];
            $username = $settings['username_account_test'];
            $password = $settings['password_account_test'];
            // endpoint
            $endpoint = sprintf(
                $this->endpointBaseTest . 'ws/SoftwareGestionali/EmissioniEseguite.ashx?username=%s&password=%s&dal=%s&al=%s&page=%d&recordpp=%d',
                $username,
                $password,
                $from_dt_ymd,
                $to_dt_ymd,
                $page_num,
                $list_lim
            );
        } else {
            // production mode
            $clientId = $settings['username_auth_live'];
            $clientSecret = $settings['password_auth_live'];
            $username = $settings['username_account_live'];
            $password = $settings['password_account_live'];
            // endpoint
            $endpoint = sprintf(
                $this->endpointBaseLive . 'ws/SoftwareGestionali/EmissioniEseguite.ashx?username=%s&password=%s&dal=%s&al=%s&page=%d&recordpp=%d',
                $username,
                $password,
                $from_dt_ymd,
                $to_dt_ymd,
                $page_num,
                $list_lim
            );
        }

        if ($bid) {
            // add software reference filter (booking ID)
            $endpoint .= '&ExtraSftAlbergatori=' . $bid;
        }

        // download the list from the web service URL behind authentication
        try {
            $response = (new JHttp)->get($endpoint, [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
            ]);

            if ($response->code != 200) {
                throw new Exception($response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
            }

            $cards_list = (array) json_decode($response->body ?: '[]', true);

            if (!$cards_list || !isset($cards_list['data'])) {
                throw new Exception('Unexpected response from EmissioniEseguite while searching for the cards.', 500);
            }

            // ensure to cast the data property to an array
            $cards_list['data'] = (array) $cards_list['data'];
        } catch (Exception $e) {
            // propagate the error
            throw $e;
        }

        // scan all cards list properties
        $html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
        $html .= '  <div class="vbo-params-wrap">';
        $html .= '      <div class="vbo-params-container">';

        // open summary block
        $html .= '          <div class="vbo-params-block">';

        // build cards list summary HTML
        $html .= '              <div class="vbo-param-container">';
        $html .= '                  <div class="vbo-param-label">Numero di record totali</div>';
        $html .= '                  <div class="vbo-param-setting">';
        $html .= '                      <span class="label label-' . (($cards_list['nRecord'] ?? 0) > 0 ? 'info' : 'warn') . '">' . ($cards_list['nRecord'] ?? '??') . '</span>';
        $html .= '                  </div>';
        $html .= '              </div>';
        $html .= '              <div class="vbo-param-container">';
        $html .= '                  <div class="vbo-param-label">Records per pagina</div>';
        $html .= '                  <div class="vbo-param-setting">';
        $html .= '                      <span class="label">' . ($cards_list['RecordPP'] ?? '??') . '</span>';
        $html .= '                  </div>';
        $html .= '              </div>';
        $html .= '              <div class="vbo-param-container">';
        $html .= '                  <div class="vbo-param-label">Pagina attuale</div>';
        $html .= '                  <div class="vbo-param-setting">';
        $html .= '                      <span class="label">' . ($cards_list['CurrentPage'] ?? 1) . '</span>';
        $html .= '                  </div>';
        $html .= '              </div>';
        $html .= '              <div class="vbo-param-container">';
        $html .= '                  <div class="vbo-param-label">Pagine totali</div>';
        $html .= '                  <div class="vbo-param-setting">';
        $html .= '                      <span class="label label-' . (($cards_list['TotalPages'] ?? 1) > 0 ? 'info' : 'warn') . '">' . ($cards_list['TotalPages'] ?? 1) . '</span>';
        $html .= '                  </div>';
        $html .= '              </div>';

        // close summary block
        $html .= '          </div>';

        // scan all cards found, if any
        foreach ($cards_list['data'] as $card_data) {
            // open card details block
            $html .= '      <div class="vbo-params-block">';

            // iterate over the card object properties
            foreach ($card_data as $detail_type => $detail_value) {
                // build readable card detail value
                $read_value = is_null($detail_value) || is_scalar($detail_value) ? $detail_value : '[Non-Scalar value]';
                if (is_null($detail_value)) {
                    $read_value = 'null';
                } elseif (is_bool($detail_value)) {
                    $read_value = $detail_value ? 'true' : 'false';
                } elseif (is_array($detail_value) && $detail_value) {
                    // in case of array values (i.e. "elEtaBambini"), make sure all entries are scalar
                    $scalar_values = array_filter($detail_value, function($dv) {
                        return is_scalar($dv);
                    });
                    if ($scalar_values) {
                        $read_value = implode(', ', $scalar_values);
                    }
                }

                // check if the detail requires a specific class
                $detail_cls = '';
                if ($detail_type == 'QrCode') {
                    $detail_cls = 'badge badge-info';
                } elseif ($detail_type == 'Attiva') {
                    $detail_cls = boolval($detail_value) ? 'badge badge-success' : 'badge badge-error';
                }

                // build card type HTML
                $html .= '      <div class="vbo-param-container">';
                $html .= '          <div class="vbo-param-label">' . $detail_type . '</div>';
                $html .= '          <div class="vbo-param-setting">';
                $html .= '              <span class="' . $detail_cls . '">' . $read_value . '</span>';
                $html .= '          </div>';
                $html .= '      </div>';
            }

            // close card details block
            $html .= '      </div>';
        }

        // finalize HTML
        $html .= '      </div>';
        $html .= '  </div>';
        $html .= '</div>';

        return [
            'html'       => $html,
            'cards_list' => $cards_list,
        ];
    }

    /**
     * Custom scoped action to generate guest cards from the exported list.
     * This is the method "Emissione Lunga Card", which is NOT being used by default.
     * Accepted scopes are "web" and "cron", so the "success" property must be returned.
     * 
     * @param   string  $scope  Optional scope identifier (cron, web, etc..).
     * @param   array   $data   Optional associative list of data to process.
     * 
     * @return  array           The execution result properties.
     * 
     * @throws  Exception
     */
    protected function emissioneLungaGuestCards($scope = null, array $data = [])
    {
        if (!($data['cards'] ?? []) && $scope === 'web') {
            // start the process through the interface by submitting the current data
            return [
                'html' => '<script type="text/javascript">vboGenerateGuestCards(\'emissioneGuestCards\');</script>',
            ];
        }

        if (!($data['cards'] ?? [])) {
            // attempt to build the guest cards data if not set
            $data['cards'] = $this->buildGuestCardsData();
        }

        if (!$data['cards']) {
            throw new Exception('Nessuna prenotazione idonea alla generazione delle guest cards.', 500);
        }

        // load settings
        $settings = $this->loadSettings();

        // build endpoint environment details
        if (!empty($settings['test_mode'])) {
            // test mode
            $clientId = $settings['username_auth_test'];
            $clientSecret = $settings['password_auth_test'];
            $username = $settings['username_account_test'];
            $password = $settings['password_account_test'];
            // endpoint
            $endpoint = $this->endpointBaseTest . 'ws/SoftwareGestionali/emissionecard.ashx';
        } else {
            // production mode
            $clientId = $settings['username_auth_live'];
            $clientSecret = $settings['password_auth_live'];
            $username = $settings['username_account_live'];
            $password = $settings['password_account_live'];
            // endpoint
            $endpoint = $this->endpointBaseLive . 'ws/SoftwareGestionali/emissionecard.ashx';
        }

        // build result pools
        $success_cards = [];
        $error_cards = [];

        // access the object for managing the booking identity values
        $requestor = VikBooking::getCPinInstance();

        // scan all cards to generate
        foreach ($data['cards'] as $card) {
            // generate the guest card for the current booking report row
            try {
                $response = (new JHttp)->post(
                    $endpoint,
                    [
                        'Nome'                => $card['first_name'],
                        'Cognome'             => $card['last_name'],
                        'NAdulti'             => (int) $card['adults'],
                        'idNazione'           => (int) $card['country_id'],
                        'Dal'                 => $card['card_from_dt'],
                        'Al'                  => $card['card_to_dt'],
                        'username'            => $username,
                        'password'            => $password,
                        'email'               => $card['email'],
                        'AnniMinori'          => (string) $card['children_age'],
                        'idTipologiaCard'     => (int) $card['card_type_id'],
                        'ExtraSftAlbergatori' => $card['software_ref'],
                    ],
                    [
                        'Content-Type'  => 'application/x-www-form-urlencoded',
                        'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                    ]
                );

                // always attempt to decode the response body no matter what was the response code
                $card_result = (array) json_decode($response->body ?: '[]', true);

                // check if an error reason is available
                $error_reason = !empty($card_result['motivo']) && is_array($card_result['motivo']) ? implode(', ', $card_result['motivo']) : '';

                if ($response->code != 200) {
                    throw new Exception($error_reason ?: $response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
                }

                if (!$card_result || empty($card_result['QrCode'])) {
                    if (!empty($error_reason)) {
                        throw new Exception(sprintf('Errore con la generazione della guest card per la prenotazione ID %d: %s.', $card['idbooking'], ($error_reason ?: 'unknown')), 500);
                    }
                    throw new Exception(sprintf('Unexpected response from EmissioneCard while generating a new card for the booking ID %d.', $card['idbooking']), 500);
                }

                // push successful card
                $success_cards[$card['idbooking']] = $card_result['QrCode'];

                // get current booking identity data
                $identity_data = $requestor->getBookingIdentityData((int) $card['idbooking']);

                // access the booking TGC information
                $tgc_card = (array) ($identity_data['tgc'] ?? []);

                // set the guest card QR Code obtained for this booking
                $tgc_card['qrcode'] = $card_result['QrCode'];

                // update booking identity data
                $identity_data['tgc'] = $tgc_card;
                $requestor->setBookingIdentityData((int) $card['idbooking'], $identity_data);
            } catch (Exception $e) {
                // catch and push the error for the current card
                $error_cards[$card['idbooking']] = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ];
            }
        }

        // get the currently active profile ID, if any
        $activeProfileId = $this->getActiveProfile();

        // build HTML response string
        $html = '<p class="info">' . sprintf('Totale guest cards processate: %d.', count($data['cards'])) . '</p>';
        if ($success_cards) {
            $html .= '<p class="successmade">' . sprintf('Guest cards generate con successo: %d.', count($success_cards)) . '</p>';
            $html .= '<ul>';
            foreach ($success_cards as $bid => $card_qr_code) {
                $html .= '<li>' . sprintf('Booking ID %d - Guest Card QR Code: %s.', $bid, $card_qr_code) . '</li>';
            }
            $html .= '</ul>';
        }
        if ($error_cards) {
            $html .= '<p class="err">' . sprintf('Errori durante la generazione di Guest cards: %d.', count($error_cards)) . '</p>';
            $html .= '<ul>';
            foreach ($error_cards as $bid => $error_card) {
                $html .= '<li>' . sprintf('Booking ID %d - (%s) %s.', $bid, $error_card['code'], $error_card['message']) . '</li>';
            }
            $html .= '</ul>';
        }

        // when executed through a cron, store an event in the Notifications Center
        if ($scope === 'cron') {
            // build the notification record
            $notification = [
                'sender'  => 'reports',
                'type'    => 'pmsreport.transmit.' . ($error_cards ? 'error' : 'ok'),
                'title'   => 'Trentino Guest Card - Generazione Cards',
                'summary' => sprintf(
                    '%sGuest cards processate: %d. Cards generate con successo: %d. Cards con errori: %d.',
                    ($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
                    count($data['cards']),
                    count($success_cards),
                    count($error_cards)
                ),
            ];

            if ($error_cards) {
                // append errors to summary text
                $notification['summary'] .= "\n" . implode("\n", array_column($error_cards, 'message'));
            }

            try {
                // store the notification record
                VBOFactory::getNotificationCenter()->store([$notification]);
            } catch (Exception $e) {
                // silently catch the error without doing anything
            }

            if (!(!$error_cards && $success_cards)) {
                // ensure to set the error that will be logged for the cron execution
                $this->setError($notification['summary']);
            }
        }

        return [
            'html'     => $html,
            'success'  => (!$error_cards && $success_cards),
            'response' => [
                'success_cards' => $success_cards,
                'error_cards'   => $error_cards,
            ],
        ];
    }

    /**
     * Custom scoped action to generate guest cards from the exported list.
     * This is the method "Emissione Essenziale", which is the PREFERRED method.
     * Accepted scopes are "web" and "cron", so the "success" property must be returned.
     * 
     * @param   string  $scope  Optional scope identifier (cron, web, etc..).
     * @param   array   $data   Optional associative list of data to process.
     * 
     * @return  array           The execution result properties.
     * 
     * @throws  Exception
     */
    protected function emissioneGuestCards($scope = null, array $data = [])
    {
        if (!($data['cards'] ?? []) && $scope === 'web') {
            // start the process through the interface by submitting the current data
            return [
                'html' => '<script type="text/javascript">vboGenerateGuestCards(\'emissioneGuestCards\');</script>',
            ];
        }

        if (!($data['cards'] ?? [])) {
            // attempt to build the guest cards data if not set
            $data['cards'] = $this->buildGuestCardsData();
        }

        if (!$data['cards']) {
            throw new Exception('Nessuna prenotazione idonea alla generazione delle guest cards.', 500);
        }

        // load settings
        $settings = $this->loadSettings();

        // build endpoint environment details
        if (!empty($settings['test_mode'])) {
            // test mode
            $clientId = $settings['username_auth_test'];
            $clientSecret = $settings['password_auth_test'];
            $username = $settings['username_account_test'];
            $password = $settings['password_account_test'];
            // endpoint
            $endpoint = $this->endpointBaseTest . 'ws/SoftwareGestionali/EmissioneEssenzialeCard.ashx';
        } else {
            // production mode
            $clientId = $settings['username_auth_live'];
            $clientSecret = $settings['password_auth_live'];
            $username = $settings['username_account_live'];
            $password = $settings['password_account_live'];
            // endpoint
            $endpoint = $this->endpointBaseLive . 'ws/SoftwareGestionali/EmissioneEssenzialeCard.ashx';
        }

        // build result pools
        $success_cards = [];
        $error_cards = [];

        // access the object for managing the booking identity values
        $requestor = VikBooking::getCPinInstance();

        // scan all cards to generate
        foreach ($data['cards'] as $card) {
            // generate the guest card for the current booking report row
            try {
                $response = (new JHttp)->post(
                    $endpoint,
                    [
                        'idTipologiaCard'     => (int) $card['card_type_id'],
                        'Dal'                 => $card['card_from_dt'],
                        'Al'                  => $card['card_to_dt'],
                        'email'               => $card['email'],
                        'personeMax'          => ($card['adults'] + $card['children']),
                        'username'            => $username,
                        'password'            => $password,
                        'IdAttributo'         => $card['card_attr_id'] ?: null,
                        'ExtraSftAlbergatori' => $card['software_ref'],
                    ],
                    [
                        'Content-Type'  => 'application/x-www-form-urlencoded',
                        'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
                    ]
                );

                // always attempt to decode the response body no matter what was the response code
                $card_result = (array) json_decode($response->body ?: '[]', true);

                // check if an error reason is available
                $error_reason = !empty($card_result['motivo']) && is_array($card_result['motivo']) ? implode(', ', $card_result['motivo']) : '';

                if ($response->code != 200) {
                    throw new Exception($error_reason ?: $response->body ?: 'A generic HTTP error occurred.', $response->code ?: 500);
                }

                if (!$card_result || empty($card_result['QrCode'])) {
                    if (!empty($error_reason)) {
                        throw new Exception(sprintf('Errore con la generazione della guest card per la prenotazione ID %d: %s.', $card['idbooking'], ($error_reason ?: 'unknown')), 500);
                    }
                    throw new Exception(sprintf('Unexpected response from EmissioneCard while generating a new card for the booking ID %d.', $card['idbooking']), 500);
                }

                // push successful card
                $success_cards[$card['idbooking']] = $card_result['QrCode'];

                // get current booking identity data
                $identity_data = $requestor->getBookingIdentityData((int) $card['idbooking']);

                // access the booking TGC information
                $tgc_card = (array) ($identity_data['tgc'] ?? []);

                // set the guest card QR Code obtained for this booking
                $tgc_card['qrcode'] = $card_result['QrCode'];

                // update booking identity data
                $identity_data['tgc'] = $tgc_card;
                $requestor->setBookingIdentityData((int) $card['idbooking'], $identity_data);
            } catch (Exception $e) {
                // catch and push the error for the current card
                $error_cards[$card['idbooking']] = [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ];
            }
        }

        // get the currently active profile ID, if any
        $activeProfileId = $this->getActiveProfile();

        // build HTML response string
        $html = '<p class="info">' . sprintf('Totale guest cards processate: %d.', count($data['cards'])) . '</p>';
        if ($success_cards) {
            $html .= '<p class="successmade">' . sprintf('Guest cards generate con successo: %d.', count($success_cards)) . '</p>';
            $html .= '<ul>';
            foreach ($success_cards as $bid => $card_qr_code) {
                $html .= '<li>' . sprintf('Booking ID %d - Guest Card QR Code: <strong>%s</strong>.', $bid, $card_qr_code) . '</li>';
            }
            $html .= '</ul>';
        }
        if ($error_cards) {
            $html .= '<p class="err">' . sprintf('Errori durante la generazione di Guest cards: %d.', count($error_cards)) . '</p>';
            $html .= '<ul>';
            foreach ($error_cards as $bid => $error_card) {
                $html .= '<li>' . sprintf('Booking ID %d - (%s) %s.', $bid, $error_card['code'], $error_card['message']) . '</li>';
            }
            $html .= '</ul>';
        }

        // when executed through a cron, store an event in the Notifications Center
        if ($scope === 'cron') {
            // build the notification record
            $notification = [
                'sender'  => 'reports',
                'type'    => 'pmsreport.transmit.' . ($error_cards ? 'error' : 'ok'),
                'title'   => 'Trentino Guest Card - Generazione Cards',
                'summary' => sprintf(
                    '%sGuest cards processate: %d. Cards generate con successo: %d. Cards con errori: %d.',
                    ($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
                    count($data['cards']),
                    count($success_cards),
                    count($error_cards)
                ),
            ];

            if ($error_cards) {
                // append errors to summary text
                $notification['summary'] .= "\n" . implode("\n", array_column($error_cards, 'message'));
            }

            try {
                // store the notification record
                VBOFactory::getNotificationCenter()->store([$notification]);
            } catch (Exception $e) {
                // silently catch the error without doing anything
            }

            if (!(!$error_cards && $success_cards)) {
                // ensure to set the error that will be logged for the cron execution
                $this->setError($notification['summary']);
            }
        }

        return [
            'html'     => $html,
            'success'  => (!$error_cards && $success_cards),
            'response' => [
                'success_cards' => $success_cards,
                'error_cards'   => $error_cards,
            ],
        ];
    }

    /**
     * Parses a report row into an associative list of key-value pairs.
     * 
     * @param   array   $row    The report row to parse.
     * 
     * @return  array           Associative list of row keys and values.
     */
    protected function getAssocRowData(array $row)
    {
        $row_data = [];

        foreach ($row as $field) {
            $field_val = $field['value'];
            if (!($field['no_export_callback'] ?? 0) && is_callable($field['callback_export'] ?? null)) {
                $field_val = $field['callback_export']($field_val);
            }
            $row_data[$field['key']] = $field_val;
        }

        return $row_data;
    }

    /**
     * Attempts to read the full card attributes list for each card type id from an internal cache.
     * 
     * @return  array   Associative list of card-type-id and related attribute objects list, if any.
     */
    protected function fetchCardAttributesList()
    {
        // build cached list file name
        $cached_list_name = 'tgc_card_attributes.json';

        // check if the cached list exists
        $cached_list_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $cached_list_name;

        if (is_file($cached_list_path)) {
            // read and decode the cached list
            return (array) json_decode((file_get_contents($cached_list_path) ?: ''), true);
        }

        return [];
    }

    /**
     * Updates the cached list of card attributes.
     * 
     * @param   array   $list   The associative list of card type id and related attributes.
     * 
     * @return  void
     */
    protected function updateCardAttributesList(array $list)
    {
        // build cached list file name
        $cached_list_name = 'tgc_card_attributes.json';

        // check if the cached list exists
        $cached_list_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . $cached_list_name;

        // update the cached list
        file_put_contents($cached_list_path, json_encode($list));
    }

    /**
     * Builds the information about the guest cards that should be generated.
     * 
     * @return  array   List of eligible guest cards data, or empty array.
     */
    protected function buildGuestCardsData()
    {
        $cards_data = [];

        if (!$this->getReportData()) {
            return $cards_data;
        }

        // scan all report rows to gather the list of eligible cards
        foreach ($this->rows as $row) {
            // get the row associative data
            $row_data = $this->getAssocRowData($row);

            if (empty($row_data['country_id']) || !is_numeric($row_data['country_id'])) {
                // invalid guest country ID
                continue;
            }

            if (($row_data['card_status'] ?? null) === true) {
                // the card can be generated
                $cards_data[] = $row_data;
            }
        }

        return $cards_data;
    }
}
