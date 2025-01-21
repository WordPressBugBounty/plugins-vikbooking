<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * VitAlbergatori child Class of VikBookingReport.
 * The integration will generate and transmit documents to ISTAT for Valle d'Aosta (Vit Albergatori).
 * 
 * @see 	WS VIT Albergatori v13.pdf (supportoweb@invallee.it).
 * @see 	Flusso dati in formato C-59 ISTAT.
 * @see 	https://wwwtest.regione.vda.it/gestione/vit_albergatori/Services/UploadDettaglioPresenze.asmx
 * 
 * @since 	1.17.2 (J) - 1.7.2 (WP)
 */
class VikBookingReportIstatVitAlbergatori extends VikBookingReport
{
	/**
	 * Property 'defaultKeySort' is used by the View that renders the report.
	 * 
	 * @var  string
	 */
	public $defaultKeySort = 'idbooking';

	/**
	 * Property 'defaultKeyOrder' is used by the View that renders the report.
	 * 
	 * @var  string
	 */
	public $defaultKeyOrder = 'ASC';

	/**
	 * Property 'customExport' is used by the View to display custom export buttons.
	 * 
	 * @var  string
	 */
	public $customExport = '';

	/**
	 * List of foreign country names and codes.
	 * 
	 * @var  array
	 */
	protected $foreignCountries = [];

	/**
	 * List of foreign country 3-char values and codes.
	 * 
	 * @var  array
	 */
	protected $foreignCountryCodes = [];

	/**
	 * List of Italian province codes.
	 * 
	 * @var  array
	 */
	protected $italianProvinces = [];

	/**
	 * List of Italian Police countries.
	 * 
	 * @var  array
	 */
	protected $italianPoliceCountries = [];

	/**
	 * The path to the temporary directory used by this report.
	 * 
	 * @var  	string
	 */
	protected $report_tmp_path = '';

	/**
	 * List of booking IDs affected by the export.
	 * 
	 * @var  	array
	 */
	protected $export_booking_ids = [];

	/**
	 * List of exported check-in dates (range).
	 * 
	 * @var  	array
	 */
	protected $exported_checkin_dates = [];

	/**
	 * The URL to the WSDL for the SOAP operations.
	 * 
	 * @var  	string
	 */
	protected $wsdl_url = 'https://wwwtest.regione.vda.it/gestione/vit_albergatori/Services/UploadDettaglioPresenze.asmx?WSDL';

	/**
	 * The location URL to the WS for the SOAP requests.
	 * 
	 * @var  	string
	 */
	protected $ws_location = 'https://wwwtest.regione.vda.it/gestione/vit_albergatori/Services/UploadDettaglioPresenze.asmx';

	/**
	 * String representation of the PHP constant for the SOAP
	 * protocol version (either SOAP 1.1 or SOAP 1.2).
	 * 
	 * @var  	string
	 */
	protected $soap_version = 'SOAP_1_2';

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = 'ISTAT Valle d\'Aosta (Vit Albergatori)';
		$this->reportFilters = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		$this->foreignCountries = $this->loadIstatForeignCountries();
		$this->foreignCountryCodes = $this->loadIstatForeignCountries('3');
		$this->italianProvinces = $this->loadCountryStates('ITA');
		$this->italianPoliceCountries = $this->loadItalianPoliceCountries();

		// set the temporary report directory path
		$this->report_tmp_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vitalbergatori_tmp';

		$this->registerExportFileName();

		parent::__construct();
	}

	/**
	 * Returns the name of this report.
	 *
	 * @return 	string
	 */
	public function getName()
	{
		return $this->reportName;
	}

	/**
	 * Returns the name of this file without .php.
	 *
	 * @return 	string
	 */
	public function getFileName()
	{
		return $this->reportFile;
	}

	/**
	 * @inheritDoc
	 */
	public function getSettingFields()
	{
		return [
			'title' => [
				'type'  => 'custom',
				'label' => '',
				'html'  => '<p class="info">Configura le impostazioni per la trasmissione dei dati ISTAT verso il sistema informativo regionale della Regione Autonoma Valle d\'Aosta (Vit Albergatori).<br/>Le informazioni su ID Struttura, Utente, Password ed Endpoint URL del WS in modalità produzione sono da richiedere all\'Office du Tourisme competente di zona, se non ne siete in possesso. Vi verrà inviata una mail con tutte le informazioni necessarie a configurare le impostazioni del report.</p>',
			],
			'propertyid' => [
				'type'  => 'text',
				'label' => 'ID Struttura',
				'help'  => 'ID della struttura ricettiva a cui i dati si riferiscono. Ogni file trasmesso può contenere i dati di una singola struttura ricettiva.',
			],
			'testmode' => [
				'type'    => 'checkbox',
				'label'   => 'Modalità Test',
				'help'    => 'Se abilitato, la trasmissione dei dati ISTAT verrà eseguita verso l\'endpoint di test, usando le credenziali fornite. Lasciare disabilitato per l\'ambiente produzione ed inserire WS Endpoint URL corretto.',
				'default' => 0,
			],
			'endpoint' => [
				'type'  => 'text',
				'label' => 'WS Endpoint URL',
				'help'  => 'Indirizzo URL per l\'endpoint del Web Service in modalità produzione (non test). Ricevuto insieme ad utente, password ed ID struttura.',
			],
			'user' => [
				'type'  => 'text',
				'label' => 'Utente',
				'help'  => 'Username dell\'utente, utilizzato per l\'autenticazione al servizio di trasmissione dati.',
			],
			'pwd' => [
				'type'  => 'password',
				'label' => 'Password',
				'help'  => 'Password dell\'utente, utilizzata per l\'autenticazione al servizio di trasmissione dati.',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getScopedActions($scope = null, $visible = true)
	{
		// count the total number of rooms
		$tot_room_units = $this->countRooms();

		// list of custom actions for this report
		$actions = [
			[
				'id' => 'uploadPresences',
				'name' => 'Carica presenze turistiche',
				'help' => 'Carica il file sul web-service per le presenze turistiche.',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
				],
			],
			[
				'id' => 'uploadOccupancy',
				'name' => 'Carica occupazione camere',
				'help' => 'Carica il file sul web-service per l\'occupazione delle camere.',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
				],
			],
			[
				'id' => 'getFirstClosingMonth',
				'name' => 'Primo mese in chiusura',
				'help' => 'Fornisce il mese e l\'anno del primo mese per il quale è possibile immettere una chiusura C59-M, rispettando la cronologia delle chiusure precedenti e il blocco movimenti in essere nel sistema. Il metodo ritorna un campo Chiusura con il mese e l\'anno del primo mese in ordine cronologico per il quale è possibile immettere una chiusura C59-M.',
				'icon' => VikBookingIcons::i('business-time'),
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'getLastMonthClosed',
				'name' => 'Ultimo mese chiuso',
				'help' => 'Il metodo fornisce il mese e l\'anno dell\'ultimo mese per il quale è stata immessa una chiusura C59-M che è ancora possibile eliminare in base al blocco movimenti in essere nel sistema. Il metodo ritorna un campo Chiusura con il mese e l\'anno dell\'ultimo mese in ordine cronologico per il quale è possibile eliminare la chiusura C59-M immessa in precedenza. La risposta del web service tiene conto della data di blocco movimenti globale gestita dal sistema.',
				'icon' => VikBookingIcons::i('user-clock'),
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'insertClosureC59M',
				'name' => 'Inserimento chiusura C59-M',
				'help' => 'Il metodo permette di inserire una chiusura C59-M per il mese/anno fornito, ottenuto in precedenza dal metodo "Primo mese in chiusura". In seguito a questa immissione, nessun dato di presenza con partenza o arrivo nel periodo chiuso potrà essere ulteriormente modificato, a meno che non si elimini la chiusura C59-M per quel periodo usando l\'apposito metodo.',
				'icon' => VikBookingIcons::i('calendar-check'),
				'scopes' => [
					'web',
					'cron',
				],
				'params' => [
					'month' => [
						'type'    => 'select',
						'label'   => 'Mese di chiusura',
						'help'    => 'Seleziona il mese della chiusura.',
						'options' => [
							1  => 'Gennaio',
							2  => 'Febbraio',
							3  => 'Marzo',
							4  => 'Aprile',
							5  => 'Maggio',
							6  => 'Giugno',
							7  => 'Luglio',
							8  => 'Agosto',
							9  => 'Settembre',
							10 => 'Ottobre',
							11 => 'Novembre',
							12 => 'Dicembre',
						],
						'default' => date('n'),
					],
					'year' => [
						'type'    => 'select',
						'label'   => 'Anno di chiusura',
						'help'    => 'Seleziona l\'anno della chiusura.',
						'options' => range(date('Y') - 2, date('Y') + 2),
						'default' => date('Y'),
					],
					'camere' => [
						'type'    => 'number',
						'min'     => 0,
						'label'   => 'Camere',
						'help'    => 'Numero di camere a disposizione nella struttura.',
						'default' => $tot_room_units,
					],
					'letti' => [
						'type'    => 'number',
						'min'     => 0,
						'label'   => 'Letti',
						'help'    => 'Numero di letti a disposizione nella struttura.',
						'default' => $tot_room_units,
					],
					'bagni' => [
						'type'    => 'number',
						'min'     => 0,
						'label'   => 'Bagni',
						'help'    => 'Numero di bagni a disposizione nella struttura.',
						'default' => $tot_room_units,
					],
				],
				'params_submit_lbl' => 'Inserisci chiusura',
			],
			[
				'id' => 'modifyClosureC59M',
				'name' => 'Modifica chiusura C59-M',
				'help' => 'Il metodo permette di modificare una chiusura C59-M per il mese/anno fornito. La modifica è possibile solo se rispetta il blocco movimenti in essere nel sistema.',
				'icon' => VikBookingIcons::i('calendar'),
				'scopes' => [
					'web',
				],
				'params' => [
					'month' => [
						'type'    => 'select',
						'label'   => 'Mese di chiusura',
						'help'    => 'Seleziona il mese della chiusura.',
						'options' => [
							1  => 'Gennaio',
							2  => 'Febbraio',
							3  => 'Marzo',
							4  => 'Aprile',
							5  => 'Maggio',
							6  => 'Giugno',
							7  => 'Luglio',
							8  => 'Agosto',
							9  => 'Settembre',
							10 => 'Ottobre',
							11 => 'Novembre',
							12 => 'Dicembre',
						],
						'default' => date('n'),
					],
					'year' => [
						'type'    => 'select',
						'label'   => 'Anno di chiusura',
						'help'    => 'Seleziona l\'anno della chiusura.',
						'options' => range(date('Y') - 2, date('Y') + 2),
						'default' => date('Y'),
					],
					'camere' => [
						'type'    => 'number',
						'min'     => 0,
						'label'   => 'Camere',
						'help'    => 'Numero di camere a disposizione nella struttura.',
						'default' => $tot_room_units,
					],
					'letti' => [
						'type'    => 'number',
						'min'     => 0,
						'label'   => 'Letti',
						'help'    => 'Numero di letti a disposizione nella struttura.',
						'default' => $tot_room_units,
					],
					'bagni' => [
						'type'    => 'number',
						'min'     => 0,
						'label'   => 'Bagni',
						'help'    => 'Numero di bagni a disposizione nella struttura.',
						'default' => $tot_room_units,
					],
				],
				'params_submit_lbl' => 'Modifica chiusura',
			],
			[
				'id' => 'removeClosureC59M',
				'name' => 'Rimuovi chiusura C59-M',
				'help' => 'Il metodo permette di eliminare una chiusura C59-M per il mese/anno fornito, ottenuto in precedenza dal metodo UltimoMeseChiuso. In seguito a questa immissione sarà nuovamente possibile modificare dati di presenza con partenza o arrivo nel periodo chiuso in precedenza.',
				'icon' => VikBookingIcons::i('calendar-times'),
				'scopes' => [
					'web',
				],
				'params' => [
					'month' => [
						'type'    => 'select',
						'label'   => 'Mese di chiusura',
						'help'    => 'Seleziona il mese della chiusura.',
						'options' => [
							1  => 'Gennaio',
							2  => 'Febbraio',
							3  => 'Marzo',
							4  => 'Aprile',
							5  => 'Maggio',
							6  => 'Giugno',
							7  => 'Luglio',
							8  => 'Agosto',
							9  => 'Settembre',
							10 => 'Ottobre',
							11 => 'Novembre',
							12 => 'Dicembre',
						],
						'default' => date('n'),
					],
					'year' => [
						'type'    => 'select',
						'label'   => 'Anno di chiusura',
						'help'    => 'Seleziona l\'anno della chiusura.',
						'options' => range(date('Y') - 2, date('Y') + 2),
						'default' => date('Y'),
					],
				],
				'params_submit_lbl' => 'Rimuovi chiusura',
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
	 * @return 	array
	 */
	public function getFilters()
	{
		if ($this->reportFilters) {
			// do not run this method twice, as it could load JS and CSS files.
			return $this->reportFilters;
		}

		// get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		// load the jQuery UI Datepicker
		$this->loadDatePicker();

		// custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadRecordVitAlbergatori();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download File</span></a>';

		// load report settings
		$settings = $this->loadSettings();

		// build the hidden values for the selection of Comuni & Province and much more.
		$hidden_vals = '<div id="vbo-report-vitalbergatori-hidden" style="display: none;">';

		// build params container HTML structure
		$hidden_vals .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$hidden_vals .= '	<div class="vbo-params-wrap">';
		$hidden_vals .= '		<div class="vbo-params-container">';
		$hidden_vals .= '			<div class="vbo-params-block vbo-params-block-noborder">';

		// provenienza
		$hidden_vals .= '	<div id="vbo-report-vitalbergatori-provenience" class="vbo-report-vitalbergatori-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Provincia Italiana o Stato Estero</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-provenience" onchange="vboReportChosenProvenience(this);"><option value=""></option>';
		$hidden_vals .= '				<optgroup label="Province Italiane">';
		foreach ($this->italianProvinces as $code => $province) {
			$hidden_vals .= '				<option value="' . $code . '">' . $province . '</option>'."\n";
		}
		$hidden_vals .= '				</optgroup>';
		$hidden_vals .= '				<optgroup label="Stati Esteri">';
		foreach ($this->foreignCountries as $code => $fcountry) {
			$hidden_vals .= '				<option value="' . $code . '">' . $fcountry . '</option>'."\n";
		}
		$hidden_vals .= '				</optgroup>';
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// foreign city
		$hidden_vals .= '	<div id="vbo-report-vitalbergatori-city" class="vbo-report-vitalbergatori-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Città estera</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-city" value="" />';
		$hidden_vals .= '			<span class="vbo-param-setting-comment">Compilare solo in caso di cliente con provenienza estera.</span>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Sesso
		$hidden_vals .= '	<div id="vbo-report-vitalbergatori-sesso" class="vbo-report-vitalbergatori-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Sesso</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-sesso" onchange="vboReportChosenSesso(this);"><option value=""></option>';
		$sessos = array(
			1 => 'M',
			2 => 'F'
		);
		foreach ($sessos as $code => $ses) {
			$hidden_vals .= '		<option value="' . $code . '">' . $ses . '</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Data di Nascita
		$hidden_vals .= '	<div id="vbo-report-vitalbergatori-dbirth" class="vbo-report-vitalbergatori-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Data di nascita</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-dbirth" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// close params container HTML structure
		$hidden_vals .= '			</div>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';
		$hidden_vals .= '</div>';

		// close hidden values container
		$hidden_vals .= '</div>';

		// From Date Filter (with hidden values for the dropdown menus of Comuni, Province, Stati etc..)
		$filter_opt = array(
			'label' => '<label for="fromdate">'.JText::translate('VBOREPORTSDATEFROM').'</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />'.$hidden_vals,
			'type' => 'calendar',
			'name' => 'fromdate'
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

		// export type filter
		$types = [
			'presenze'    => sprintf('%s (%s)', JText::translate('VBPICKUPAT'), 'Presenze turistiche'),
			'occupazione' => sprintf('%s (%s)', JText::translate('VBOGRAPHTOTOCCUPANCYLBL'), 'Occupazione camere'),
		];
		$ptype = VikRequest::getString('type', 'presenze', 'request');
		$types_sel_html = $vbo_app->getNiceSelect($types, $ptype, 'type', '', '', '', '', 'type');
		$filter_opt = array(
			'label' => '<label for="type">' . JText::translate('VBPSHOWSEASONSTHREE') . '</label>',
			'html' => $types_sel_html,
			'type' => 'select',
			'name' => 'type'
		);
		array_push($this->reportFilters, $filter_opt);

		// append button to save the data when creating manual values
		$filter_opt = array(
			'label' => '<label class="vbo-report-vitalbergatori-manualsave" style="display: none;">Dati inseriti</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-vitalbergatori-manualsave" style="display: none;" onclick="vboVitAlbergatoriSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		//jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$js = 'var reportActiveCell = null, reportObj = {};
		var vbo_vitalbergatori_ajax_uri = "' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile) . '";
		var vbo_vitalbergatori_save_icn = "' . VikBookingIcons::i('save') . '";
		var vbo_vitalbergatori_saving_icn = "' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '";
		var vbo_vitalbergatori_saved_icn = "' . VikBookingIcons::i('check-circle') . '";
		jQuery(function() {
			//prepare main filters
			jQuery(".vbo-report-datepicker:input").datepicker({
				maxDate: 0,
				dateFormat: "'.$this->getDateFormat('jui').'",
				onSelect: vboReportCheckDates
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
			//prepare filler helpers
			jQuery("#vbo-report-vitalbergatori-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-provenience").select2({placeholder: "- Provincia o Stato Estero -", width: "200px"});
			jQuery("#choose-sesso").select2({placeholder: "- Seleziona Sesso -", width: "200px"});
			jQuery("#choose-dbirth").datepicker({
				maxDate: 0,
				dateFormat: "dd/mm/yy",
				changeMonth: true,
				changeYear: true,
				yearRange: "'.(date('Y') - 100).':'.date('Y').'"
			});
			//click events
			jQuery(".vbo-report-load-provenience").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-vitalbergatori-selcont").hide();
				jQuery("#vbo-report-vitalbergatori-provenience").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-vitalbergatori-selcont").hide();
				jQuery("#vbo-report-vitalbergatori-sesso").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-city").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-vitalbergatori-selcont").hide();
				jQuery("#vbo-report-vitalbergatori-city").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenCity(document.getElementById(\'choose-city\').value);\">Applica</button>",
				});
				setTimeout(function(){jQuery("#choose-city").focus();}, 500);
			});
			jQuery(".vbo-report-load-dbirth").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-vitalbergatori-selcont").hide();
				jQuery("#vbo-report-vitalbergatori-dbirth").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenDbirth(document.getElementById(\'choose-dbirth\').value);\">Applica</button>",
				});
			});
		});
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
		function vboReportChosenProvenience(naz) {
			var c_code = naz.value;
			var c_val = naz.options[naz.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-provenience")) {
						reportObj[nowindex]["state"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-provenience").val("").select2("data", null, false);
			jQuery(".vbo-report-vitalbergatori-manualsave").show();
		}
		function vboReportChosenSesso(sesso) {
			var c_code = sesso.value;
			var c_val = sesso.options[sesso.selectedIndex].text;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					reportObj[nowindex]["gender"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-sesso").val("").select2("data", null, false);
			jQuery(".vbo-report-vitalbergatori-manualsave").show();
		}
		function vboReportChosenCity(val) {
			var c_code = val, c_val = val;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					reportObj[nowindex]["city"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-city").val("");
			jQuery(".vbo-report-vitalbergatori-manualsave").show();
		}
		function vboReportChosenDbirth(val) {
			var c_code = val, c_val = val;
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
				} else {
					var rep_act_cell = jQuery(reportActiveCell);
					rep_act_cell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					reportObj[nowindex]["date_birth"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-dbirth").val("");
			jQuery(".vbo-report-vitalbergatori-manualsave").show();
		}
		//download function
		function vboDownloadRecordVitAlbergatori(type, report_type) {
			if (!confirm("Sei sicuro di aver compilato eventuali informazioni mancanti o incorrette?")) {
				return false;
			}

			let use_blank = true;
			if (typeof type === "undefined") {
				type = 1;
			} else {
				use_blank = false;
			}

			if (typeof report_type !== "undefined") {
				jQuery(\'#adminForm\').find(\'select[name="type"]\').val(report_type).trigger(\'change\');
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
		}
		// save data function
		function vboVitAlbergatoriSaveData() {
			jQuery("button.vbo-report-vitalbergatori-manualsave").find("i").attr("class", vbo_vitalbergatori_saving_icn);
			VBOCore.doAjax(
				vbo_vitalbergatori_ajax_uri,
				{
					call: "updatePaxData",
					params: reportObj,
					tmpl: "component"
				},
				function(response) {
					if (!response || !response[0]) {
						alert("An error occurred.");
						return false;
					}
					jQuery("button.vbo-report-vitalbergatori-manualsave").addClass("btn-success").find("i").attr("class", vbo_vitalbergatori_saved_icn);
				},
				function(error) {
					alert(error.responseText);
					jQuery("button.vbo-report-vitalbergatori-manualsave").removeClass("btn-success").find("i").attr("class", vbo_vitalbergatori_save_icn);
				}
			);
		}
		';
		$this->setScript($js);

		return $this->reportFilters;
	}

	/**
	 * Loads the report data from the DB.
	 * Returns true in case of success, false otherwise.
	 * Sets the columns and rows for the report to be displayed.
	 *
	 * @return 	boolean
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

		$dbo = JFactory::getDbo();

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// injected options will replace request variables, if any
		$opt_fromdate = $options->get('fromdate', '');
		$opt_todate   = $options->get('todate', '');
		$opt_type     = $options->get('type', '');

		// input fields and other vars
		$pfromdate = $opt_fromdate ?: VikRequest::getString('fromdate', '', 'request');
		$ptodate = $opt_todate ?: VikRequest::getString('todate', '', 'request');
		$ptype = $opt_type ?: VikRequest::getString('type', 'presenze', 'request');

		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';

		$currency_symb = VikBooking::getCurrencySymb();
		$df = $this->getDateFormat();
		$datesep = VikBooking::getDateSeparator();
		if (empty($ptodate)) {
			$ptodate = $pfromdate;
		}

		// get translator
		$vbo_tn = VikBooking::getTranslator();

		// load all countries
		$all_countries = VikBooking::getCountriesArray();

		// load all rooms
		$all_rooms = VikBooking::getAvailabilityInstance(true)->loadRooms();

		// get date timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		// set the dates being exported
		$this->exported_checkin_dates = [
			date('Y-m-d', $from_ts),
			date('Y-m-d', $to_ts),
		];

		// query to obtain the records (all reservations or stays within the dates filter)
		$q = $dbo->getQuery(true)
			->select([
				$dbo->qn('o.id'),
				$dbo->qn('o.custdata'),
				$dbo->qn('o.ts'),
				$dbo->qn('o.days'),
				$dbo->qn('o.status'),
				$dbo->qn('o.checkin'),
				$dbo->qn('o.checkout'),
				$dbo->qn('o.totpaid'),
				$dbo->qn('o.roomsnum'),
				$dbo->qn('o.total'),
				$dbo->qn('o.idorderota'),
				$dbo->qn('o.channel'),
				$dbo->qn('o.country'),
				$dbo->qn('or.idorder'),
				$dbo->qn('or.idroom'),
				$dbo->qn('or.adults'),
				$dbo->qn('or.children'),
				$dbo->qn('or.t_first_name'),
				$dbo->qn('or.t_last_name'),
				$dbo->qn('or.cust_cost'),
				$dbo->qn('or.cust_idiva'),
				$dbo->qn('or.extracosts'),
				$dbo->qn('or.room_cost'),
				$dbo->qn('co.idcustomer'),
				$dbo->qn('co.pax_data'),
				$dbo->qn('c.first_name'),
				$dbo->qn('c.last_name'),
				$dbo->qn('c.country', 'customer_country'),
				$dbo->qn('c.address'),
				$dbo->qn('c.city'),
				$dbo->qn('c.state'),
				$dbo->qn('c.doctype'),
				$dbo->qn('c.docnum'),
				$dbo->qn('c.gender'),
				$dbo->qn('c.bdate'),
				$dbo->qn('c.pbirth'),
			])
			->from($dbo->qn('#__vikbooking_orders', 'o'))
			->leftJoin($dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $dbo->qn('or.idorder') . ' = ' . $dbo->qn('o.id'))
			->leftJoin($dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $dbo->qn('co.idorder') . ' = ' . $dbo->qn('o.id'))
			->leftJoin($dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $dbo->qn('c.id') . ' = ' . $dbo->qn('co.idcustomer'))
			->where($dbo->qn('o.closure') . ' = 0')
			->order($dbo->qn('o.checkin') . ' ASC')
			->order($dbo->qn('o.id') . ' ASC')
			->order($dbo->qn('or.id') . ' ASC');

		// fetch all bookings with a stay date between the interval of dates requested
		$q->andWhere([
			'(' . $dbo->qn('o.checkin') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts . ')',
			'(' . $dbo->qn('o.checkout') . ' BETWEEN ' . strtotime('+1 day', $from_ts) . ' AND ' . strtotime('+1 day', $to_ts) . ')',
			'(' . $dbo->qn('o.checkin') . ' < ' . $from_ts . ' AND ' . $dbo->qn('o.checkout') . ' > ' . $to_ts . ')',
		], 'OR');

		if ($ptype === 'occupazione') {
			// occupazione camere
			$q->where($dbo->qn('o.status') . ' = ' . $dbo->q('confirmed'));
		} else {
			// presenze turistiche
			$q->andWhere([
				$dbo->qn('o.status') . ' = ' . $dbo->q('confirmed'),
				$dbo->qn('o.status') . ' = ' . $dbo->q('cancelled'),
			], 'OR');
		}

		$dbo->setQuery($q);
		$records = $dbo->loadAssocList();

		if (!$records) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			$this->setError('Nessuna prenotazione trovata nelle date selezionate.');
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

		// define the columns of the report
		$this->cols = [
			// tipo di record
			[
				'key'   => 'tipo',
				'label' => 'Tipo',
				'tip'   => 'Tipologia del record relativo alle presenze turistiche. A indica che una presenza verrà aggiunta o modificata, se esistente. E indica che una presenza verrà eliminata.',
			],
			// identificativo record
			[
				'key'   => 'identificativo',
				'attr' => [
					'class="center"',
				],
				'label' => 'Identificativo',
				'tip'   => 'Codice univoco identificativo della registrazione del record. Generato automaticamente.',
			],
			// first name
			[
				'key' => 'first_name',
				'label' => JText::translate('VBTRAVELERNAME'),
			],
			// last name
			[
				'key' => 'last_name',
				'label' => JText::translate('VBTRAVELERLNAME'),
			],
			// booking status
			[
				'key' => 'status',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBSTATUS'),
			],
			// camera
			[
				'key' => 'idroom',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBEDITORDERTHREE'),
			],
			// sesso
			[
				'key' => 'sesso',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBCUSTOMERGENDER'),
			],
			// data di nascita
			[
				'key' => 'date_birth',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBCUSTOMERBDATE'),
			],
			// provenienza
			[
				'key' => 'provenience',
				'attr' => [
					'class="center"',
				],
				'label' => 'Provenienza',
			],
			// città estera
			[
				'key' => 'foreigncity',
				'attr' => [
					'class="center"',
				],
				'label' => 'Città estera',
			],
			// check-in
			[
				'key' => 'checkin',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBPICKUPAT'),
			],
			// check-out
			[
				'key' => 'checkout',
				'attr' => [
					'class="center"',
				],
				'label' => JText::translate('VBRELEASEAT'),
			],
			// id booking
			[
				'key' => 'idbooking',
				'attr' => [
					'class="center"'
				],
				'label' => 'ID / #'
			],
		];

		if ($ptype === 'occupazione') {
			// define the proper columns for this type of report
			$this->cols = [
				// tipo di record
				[
					'key'   => 'tipo',
					'label' => 'Tipo',
					'tip'   => 'Tipologia del record relativo all\'occupazione delle camere.',
				],
				// data di riferimento
				[
					'key'   => 'dateref',
					'attr' => [
						'class="center"',
					],
					'label' => 'Data riferimento',
					'tip'   => 'Data a cui il valore di occupazione fa riferimento.',
				],
				// camere occupate
				[
					'key' => 'busy_rooms',
					'attr' => [
						'class="center"',
					],
					'label' => JText::translate('VBOGRAPHTOTOCCUPANCYLBL'),
				],
			];
		}

		// line number (to facilitate identifying a specific guest in case of errors with the file submission)
		$line_number = 0;

		// loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		foreach ($bookings as $gbook) {
			// count the total number of guests and adults for all rooms of this booking
			$tot_booking_guests = 0;
			$tot_booking_adults = 0;
			$room_guests = [];
			foreach ($gbook as $rbook) {
				$tot_booking_guests += ($rbook['adults'] + $rbook['children']);
				$tot_booking_adults += $rbook['adults'];
				$room_guests[] = ($rbook['adults'] + $rbook['children']);
			}

			// make sure to decode the current pax data
			if (!empty($gbook[0]['pax_data'])) {
				$gbook[0]['pax_data'] = (array) json_decode($gbook[0]['pax_data'], true);
			}

			// push a copy of the booking for each guest
			$guests_rows = [];
			for ($i = 1; $i <= $tot_booking_guests; $i++) {
				array_push($guests_rows, $gbook[0]);
			}

			// create one row for each guest
			$guest_ind = 1;
			foreach ($guests_rows as $ind => $guests) {
				// prepare row record for this room-guest
				$insert_row = [];

				// find the actual guest-room-index
				$guest_room_ind = $this->calcGuestRoomIndex($room_guests, $guest_ind);

				// tipo record
				$tipo_record = !strcasecmp($guests['status'], 'confirmed') ? 'A' : 'E';
				array_push($insert_row, [
					'key' => 'tipo',
					'callback' => function($val) {
						return $val === 'E' ? 'Elimina' : 'Aggiungi/Modifica';
					},
					'no_export_callback' => 1,
					'value' => $tipo_record,
				]);

				// identificativo record
				$record_id = $guests['id'] . '-' . $ind;
				array_push($insert_row, [
					'key' => 'identificativo',
					'attr' => [
						'class="center"',
					],
					'value' => $record_id,
				]);

				// nome
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				$pax_nome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'first_name');
				$nome = !empty($pax_nome) ? $pax_nome : $nome;
				array_push($insert_row, [
					'key' => 'first_name',
					'value' => $nome,
					'ignore_export' => 1,
				]);

				// cognome
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				$pax_cognome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'last_name');
				$cognome = !empty($pax_cognome) ? $pax_cognome : $cognome;
				array_push($insert_row, [
					'key' => 'last_name',
					'value' => $cognome,
					'ignore_export' => 1,
				]);

				// status
				$booking_status = $guests['status'];
				array_push($insert_row, [
					'key' => 'status',
					'attr' => [
						'class="center"',
					],
					'callback' => function($val) {
						$lbl_name  = !strcasecmp($val, 'confirmed') ? JText::translate('VBCONFIRMED') : JText::translate('VBCANCELLED');
						$css_class = !strcasecmp($val, 'confirmed') ? 'success' : 'danger';
						return '<span class="label label-' . $css_class . '">' . $lbl_name . '</span>';
					},
					'ignore_export' => 1,
					'value' => $booking_status,
				]);

				// camera
				array_push($insert_row, [
					'key' => 'idroom',
					'attr' => [
						'class="center"',
					],
					'callback' => function($val) use ($all_rooms) {
						return $all_rooms[$val]['name'] ?? $val;
					},
					'no_export_callback' => 1,
					'value' => $guests['idroom'],
				]);

				// Sesso
				$gender = !empty($guests['gender']) && $guest_ind < 2 ? strtoupper($guests['gender']) : '';
				$pax_gender = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'gender');
				$gender = !empty($pax_gender) ? $pax_gender : $gender;
				/**
				 * We make sure the gender will be compatible with both back-end and front-end
				 * check-in/registration data collection driver and processes.
				 */
				if (is_numeric($gender)) {
					$gender = (int)$gender;
				} elseif (!strcasecmp($gender, 'F')) {
					$gender = 2;
				} elseif (!strcasecmp($gender, 'M')) {
					$gender = 1;
				}
				array_push($insert_row, [
					'key' => 'gender',
					'attr' => [
						'class="center'.(empty($gender) ? ' vbo-report-load-sesso' : '').'"',
					],
					'callback' => function($val) {
						return $val == 2 ? 'F' : ($val === 1 ? 'M' : '?');
					},
					'value' => $gender,
				]);

				// Data di nascita
				$dbirth = !empty($guests['bdate']) && $guest_ind < 2 ? VikBooking::getDateTimestamp($guests['bdate'], 0, 0) : '';
				$pax_dbirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'date_birth');
				$dbirth = !empty($pax_dbirth) ? $pax_dbirth : $dbirth;
				$dbirth = (strpos($dbirth, '/') === false && strpos($dbirth, VikBooking::getDateSeparator()) === false) ? $dbirth : VikBooking::getDateTimestamp($dbirth, 0, 0);
				array_push($insert_row, [
					'key' => 'date_birth',
					'attr' => [
						'class="center'.(empty($dbirth) ? ' vbo-report-load-dbirth' : '').'"',
					],
					'callback' => function($val) {
						if (!empty($val) && strpos($val, '/') === false && strpos($val, VikBooking::getDateSeparator()) === false) {
							return is_numeric($val) ? date('Y-m-d', $val) : $val;
						}
						if (!empty($val) && strpos($val, '/') !== false) {
							return $val;
						}
						return '?';
					},
					'value' => $dbirth,
				]);

				// provenience (Provenienza)
				$provenience = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'state') ?: $guests['state'] ?: '';
				$provenience = $provenience ?: $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_s');
				$provenience = $provenience ?: $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_b');
				$provenience = (string) ($provenience == 'ES' ? '' : $provenience);

				// access any possible country value
				$pax_country_list = [
					$this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country'),
					$this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_c'),
					$this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_b'),
					$guests['customer_country'],
					$guests['country'],
				];
				
				// filter empty countries
				$pax_country_list = array_values(array_filter($pax_country_list));

				// map italian police country codes to 3-char codes
				$pax_country_list = array_map(function($country_val) {
					if ($this->italianPoliceCountries[$country_val]['three_code'] ?? null) {
						return $this->italianPoliceCountries[$country_val]['three_code'];
					}
					return $country_val;
				}, $pax_country_list);

				if (!$provenience && $pax_country_list && stripos($pax_country_list[0], 'ITA') === false) {
					// for non-italian guests we can try to guess the ISTAT foreign country code
					$foreign_matches = [];

					// determine the value to match
					$match_name = $pax_country_list[0];
					$country_data = VBOStateHelper::getCountryData((int) VBOStateHelper::getCountryId($pax_country_list[0]));
					if ($country_data) {
						// try to forse the record conversion into Italian for a more accurate matching of the foreign country
						$country_data = $vbo_tn->translateRecord($country_data, '#__vikbooking_countries', 'it-IT');

						// assign the country name to match
						$match_name = $country_data['country_name'];
					}

					// calculate the similarity for each country ISTAT code
					foreach ($this->foreignCountries as $fc_code => $fc_name) {
						// calculate similarity
						similar_text(strtolower($fc_name), strtolower($match_name), $similarity);

						// assign similarity to country ISTAT code
						$foreign_matches[$fc_code] = $similarity;
					}

					// sort similarity in descending order
					arsort($foreign_matches);

					// assign the first match found, the most similar one
					foreach ($foreign_matches as $fc_code => $similarity_score) {
						// we trust the first match to be valid
						$provenience = $fc_code;
						break;
					}

					// at last, check if we can match an exact 3-char known country code
					if (strlen($pax_country_list[0]) === 3 && $matched_code = array_search($pax_country_list[0], $this->foreignCountryCodes)) {
						// do not use similarity, but rather the exact country code
						$provenience = $matched_code;
					}
				}

				$provenience_elem_class = '';
				if (empty($provenience)) {
					// optional selection style
					$provenience_elem_class = ' vbo-report-load-provenience vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$provenience_elem_class = ' vbo-report-load-provenience vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, [
					'key' => 'provenience',
					'attr' => [
						'class="center' . $provenience_elem_class . '"',
					],
					'callback' => function($val) {
						return $this->italianProvinces[$val] ?? $this->foreignCountries[$val] ?? $val ?: '?';
					},
					'no_export_callback' => 1,
					'value' => $provenience,
				]);

				// foreigncity (città estera)
				$foreigncity = (string) ($this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'city') ?: $guests['city'] ?: '');

				// check if the guest may be Italian
				$maybe_italian = false;
				if ($provenience && !($this->foreignCountries[$provenience] ?? null)) {
					// if guest is NOT a foreigner, make the foreign city empty
					$foreigncity = '';
					$maybe_italian = true;
				} elseif (!$provenience && $pax_country_list && stripos($pax_country_list[0], 'ITA') !== false) {
					// if guest is NOT a foreigner, make the foreign city empty
					$foreigncity = '';
					$maybe_italian = true;
				}

				$foreigncity_elem_class = '';
				if (empty($foreigncity)) {
					// optional selection style
					$foreigncity_elem_class = ' vbo-report-load-city vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$foreigncity_elem_class = ' vbo-report-load-city vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, [
					'key' => 'foreigncity',
					'attr' => [
						'class="center' . $foreigncity_elem_class . '"',
					],
					'callback' => function($val) use ($maybe_italian) {
						if (empty($val) && !$maybe_italian) {
							return '?';
						}
						return $val;
					},
					'no_export_callback' => 1,
					'value' => $foreigncity,
				]);

				// checkin
				array_push($insert_row, [
					'key' => 'checkin',
					'attr' => [
						'class="center"',
					],
					'callback' => function($val) {
						return date('Y-m-d', $val);
					},
					'value' => $guests['checkin'],
				]);

				// checkout
				array_push($insert_row, [
					'key' => 'checkout',
					'attr' => [
						'class="center"',
					],
					'callback' => function($val) {
						return date('Y-m-d', $val);
					},
					'value' => $guests['checkout'],
				]);

				// id booking
				array_push($insert_row, [
					'key' => 'idbooking',
					'attr' => [
						'class="center"',
					],
					'callback' => function($val) use ($line_number) {
						// make sure to keep the data-bid attribute as it's used by JS to identify the booking ID
						return '<a data-bid="' . $val . '" href="index.php?option=com_vikbooking&task=editorder&cid[]=' . $val . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i> ' . $val . '</a> / <span>#' . $line_number . '</span>';
					},
					'ignore_export' => 1,
					'value' => $guests['id'],
				]);

				// push fields in the rows array as a new row
				array_push($this->rows, $insert_row);

				// increment guest index
				$guest_ind++;

				// increment line number
				$line_number++;
			}
		}

		if ($ptype === 'occupazione') {
			// we need a completely different set of rows, reset what was done
			$this->rows = [];

			$start_info = getdate($from_ts);
			while ($start_info[0] <= $to_ts) {
				// start a new row
				$insert_row = [];

				// count occupied rooms on this day
				$day_busy_rooms_count = 0;

				foreach ($bookings as $gbook) {
					if (date('Y-m-d', $gbook[0]['checkin']) === date('Y-m-d', $start_info[0])) {
						// check-in date
						$day_busy_rooms_count += count($gbook);
						continue;
					}

					if ($gbook[0]['checkin'] < $start_info[0] && $gbook[0]['checkout'] > $start_info[0]) {
						if (date('Y-m-d', $gbook[0]['checkout']) !== date('Y-m-d', $start_info[0])) {
							// this must be a stay date
							$day_busy_rooms_count += count($gbook);
							continue;
						}
					}
				}

				// tipo record
				$tipo_record = 'CAMERE';
				array_push($insert_row, [
					'key' => 'tipo',
					'callback' => function($val) {
						return 'Camere';
					},
					'no_export_callback' => 1,
					'value' => $tipo_record,
				]);

				// data di riferimento
				array_push($insert_row, [
					'key' => 'dateref',
					'attr' => [
						'class="center"',
					],
					'value' => date('Y-m-d', $start_info[0]),
				]);

				// camere occupate
				array_push($insert_row, [
					'key' => 'busy_rooms',
					'attr' => [
						'class="center"',
					],
					'value' => $day_busy_rooms_count,
				]);

				// push fields in the rows array as a new row
				array_push($this->rows, $insert_row);

				// go to next day
				$start_info = getdate(mktime(0, 0, 0, $start_info['mon'], $start_info['mday'] + 1, $start_info['year']));
			}
		}
		
		// do not sort the rows for this report because the lines of the guests of the same booking must be consecutive
		// $this->sortRows($pkrsort, $pkrorder);

		// the footer row will just print the amount of records to export
		array_push($this->footerRow, [
			[
				'attr' => [
					'class="vbo-report-total"',
				],
				'value' => '<h3>' . JText::translate('VBOREPORTSTOTALROW') . '</h3>',
			],
			[
				'attr' => [
					'colspan="' . (count($this->cols) - 1) . '"',
				],
				'value' => count($this->rows),
			],
		]);

		return true;
	}

	/**
	 * Determines the current type of report ("presenze turistiche" or "occupazione camere").
	 * 
	 * @return 	string 	Either "presenze" or "occupazione".
	 */
	protected function determineReportType()
	{
		// determine the type of report ("presenze turistiche" or "occupazione camere")
		$report_type = 'presenze';

		if (!$this->getReportData()) {
			return $report_type;
		}

		foreach ($this->rows[0] ?? [] as $field) {
			if ($field['key'] === 'busy_rooms') {
				// report type "occupazione camere" identified
				$report_type = 'occupazione';
				break;
			}
		}

		return $report_type;
	}

	/**
	 * Builds the report lines for export or transmission.
	 * 
	 * @return 	array 	Empty array in case of errors, or list of rows.
	 */
	protected function buildRecordLines()
	{
		if (!$this->getReportData()) {
			return [];
		}

		// determine the type of report ("presenze turistiche" or "occupazione camere")
		$report_type = $this->determineReportType();

		// custom data manually filled before saving and reloading
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? (array) json_decode($pfiller, true) : [];

		// pool of booking IDs to update their history
		$this->export_booking_ids = [];

		// array of lines (one line for each guest)
		$lines = [];

		// push the lines of the Text file
		foreach ($this->rows as $ind => $row) {
			// build record string
			$line_cont = '';

			// check if it's a record cancellation
			$cancel_record = false;
			$cancel_id = false;

			foreach ($row as $field) {
				if ($field['key'] == 'idbooking' && !in_array($field['value'], $this->export_booking_ids)) {
					array_push($this->export_booking_ids, $field['value']);
				}

				if (isset($field['ignore_export'])) {
					continue;
				}

				if ($cancel_record && $cancel_id) {
					// we've got enough information for this cancellation record
					break;
				}

				// report value
				if (is_array($pfiller) && isset($pfiller[$ind]) && isset($pfiller[$ind][$field['key']])) {
					if (strlen($pfiller[$ind][$field['key']])) {
						$field['value'] = $pfiller[$ind][$field['key']];
					}
				}

				if (isset($field['callback_export'])) {
					$field['callback'] = $field['callback_export'];
				}

				// get field value
				$value = !isset($field['no_export_callback']) && isset($field['callback']) && is_callable($field['callback']) ? $field['callback']($field['value']) : $field['value'];

				if ($report_type !== 'occupazione') {
					// placeholders should be turned into empty strings
					$value = (empty($value) && $value !== 0) || $value == '---' || $value == '?' ? '' : $value;
				}

				if ($field['key'] == 'tipo' && $field['value'] === 'E') {
					// turn flag on
					$cancel_record = true;
				}

				if ($field['key'] == 'identificativo' && $report_type !== 'occupazione') {
					// turn flag on
					$cancel_id = true;
				}

				// concatenate the field to the current line and add the tab separator
				$line_cont .= $value . "\t";
			}

			// push the line in the array of lines
			array_push($lines, trim($line_cont));
		}

		return $lines;
	}

	/**
	 * Generates the text file for ISTAT Valle d'Aosta, then sends it to output for download.
	 * In case of errors, the process is not terminated (exit) to let the View display the
	 * error message(s). The export type argument can eventually determine an action to run.
	 *
	 * @param 	string 	$export_type 	Differentiates the type of export requested.
	 *
	 * @return 	void|bool 				Void in case of script termination, boolean otherwise.
	 */
	public function customExport($export_type = null)
	{
		// build the record lines
		$lines = $this->buildRecordLines();

		// determine the type of report ("presenze turistiche" or "occupazione camere")
		$report_type = $this->determineReportType();

		// build report action data, if needed
		$action_data = array_merge($this->getActionData($registry = false), ['lines' => $lines]);

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
				$this->setError($e->getMessage());

				// abort
				return false;
			}
		}

		// proceed with the regular export function (write on file through cron or download file through web)

		if (!$lines) {
			// abort
			return false;
		}

		// update history for all bookings affected before exporting
		foreach ($this->export_booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance($bid)->store('RP', $this->reportName . ' - Export (' . $report_type . ')');
		}

		/**
		 * Custom export method supports a custom export handler, if previously set.
		 */
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, implode("\r\n", $lines));
			fclose($fp);

			// return true as data was written
			return true;
		}

		// force text file download in case of regular export
		header("Content-type: text/plain");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="' . $this->getExportCSVFileName() . '"');
		echo implode("\r\n", $lines);

		exit;
	}

	/**
	 * Helper method invoked via AJAX by the controller.
	 * Needed to save the manual entries for the pax data.
	 * 
	 * @param 	array 	$manual_data 	the object representation of the manual entries.
	 * 
	 * @return 	array 					one boolean value array with the operation result.
	 */
	public function updatePaxData($manual_data = [])
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

		// loop through all bookings to update the data for the various rooms and guests
		$bids_updated = 0;
		foreach ($bids_guests as $bid => $entries) {
			$b_rooms = VikBooking::loadOrdersRoomsData($bid);
			if (empty($b_rooms)) {
				continue;
			}
			// count guests per room
			$room_guests = [];
			foreach ($b_rooms as $b_room) {
				$room_guests[] = $b_room['adults'] + $b_room['children'];
			}
			// get current booking pax data
			$pax_data = VBOCheckinPax::getBookingPaxData($bid);
			$pax_data = empty($pax_data) ? [] : $pax_data;
			foreach ($entries as $guest_ind => $guest_data) {
				// find room index for this guest
				$room_num = 0;
				$use_guest_ind = $guest_ind;
				foreach ($room_guests as $room_index => $tot_guests) {
					// find the proper guest index for the room to which this belongs
					if ($use_guest_ind <= $tot_guests) {
						// proper room index found for this guest
						$room_num = $room_index;
						break;
					} else {
						// it's probably in a next room
						$use_guest_ind -= $tot_guests;
					}
				}
				// push new pax data for this room and guest
				if (!isset($pax_data[$room_num])) {
					$pax_data[$room_num] = [];
				}
				if (!isset($pax_data[$room_num][$use_guest_ind])) {
					$pax_data[$room_num][$use_guest_ind] = $guest_data;
				} else {
					$pax_data[$room_num][$use_guest_ind] = array_merge($pax_data[$room_num][$use_guest_ind], $guest_data);
				}
			}
			// update booking pax data
			if (VBOCheckinPax::setBookingPaxData($bid, $pax_data)) {
				$bids_updated++;
			}
		}

		return $bids_updated ? [true] : [false];
	}

	/**
	 * Returns an associative list of states/provinces for the given country.
	 * Visibility should be public in case other ISTAT reports for Italy may
	 * require to load the same provinces.
	 * 
	 * @return 	array
	 */
	public function loadCountryStates($country)
	{
		$country_states = [];

		foreach (VBOStateHelper::getCountryStates((int) VBOStateHelper::getCountryId($country)) as $state_record) {
			$country_states[$state_record['state_2_code']] = $state_record['state_name'];
		}

		return $country_states;
	}

	/**
	 * Returns an associative list of ISTAT foreign country codes.
	 * Visibility should be public in case other ISTAT reports for
	 * Italy may require to load the same foreign country codes.
	 * 
	 * @param 	string 	$type 	The string "3" can be passed to get the known 3-char country codes.
	 * 
	 * @return 	array
	 */
	public function loadIstatForeignCountries($type = '')
	{
		if ($type === '3') {
			// return the list for only the known country ISO code (3-char)
			return [
				'528' => "ARG",
				'800' => "AUS",
				'038' => "AUT",
				'017' => "BEL",
				'508' => "BRA",
				'068' => "BGR",
				'404' => "CAN",
				'720' => "CHN",
				'600' => "CYP",
				'728' => "KOR",
				'092' => "HRV",
				'008' => "DNK",
				'220' => "EGY",
				'053' => "EST",
				'032' => "FIN",
				'001' => "FRA",
				'004' => "DEU",
				'732' => "JPN",
				'009' => "GRC",
				'664' => "IND",
				'007' => "IRL",
				'024' => "ISL",
				'624' => "ISR",
				'999' => "ITA",
				'054' => "LVA",
				'055' => "LTU",
				'018' => "LUX",
				'046' => "MLT",
				'412' => "MEX",
				'028' => "NOR",
				'804' => "NZL",
				'230' => "MED",
				'003' => "NLD",
				'060' => "POL",
				'010' => "PRT",
				'006' => "GBR",
				'061' => "CZE",
				'066' => "ROU",
				'075' => "RUS",
				'063' => "SVK",
				'091' => "SVN",
				'011' => "ESP",
				'400' => "USA",
				'388' => "ZAF",
				'030' => "SWE",
				'036' => "CHE",
				'052' => "TUR",
				'072' => "UKR",
				'064' => "HUN",
				'484' => "VEN",
			];
		}

		// return the whole ISTAT list
		return [
			'777' => "ALTRI PAESI",
			'300' => "ALTRI PAESI AFRICA",
			'530' => "ALTRI PAESI AMERICA LATINA",
			'760' => "ALTRI PAESI DELL'ASIA",
			'100' => "ALTRI PAESI EUROPEI",
			'750' => "ALTRI PAESI MEDIO ORIENTE",
			'410' => "ALTRI PAESI NORD AMERICA",
			'810' => "ALTRI PAESI OCEANIA",
			'528' => "ARGENTINA",
			'800' => "AUSTRALIA",
			'038' => "AUSTRIA",
			'017' => "BELGIO",
			'508' => "BRASILE",
			'068' => "BULGARIA",
			'404' => "CANADA",
			'720' => "CINA",
			'600' => "CIPRO",
			'728' => "COREA DEL SUD",
			'092' => "CROAZIA",
			'008' => "DANIMARCA",
			'220' => "EGITTO",
			'053' => "ESTONIA",
			'032' => "FINLANDIA",
			'001' => "FRANCIA",
			'004' => "GERMANIA",
			'732' => "GIAPPONE",
			'009' => "GRECIA",
			'664' => "INDIA",
			'007' => "IRLANDA",
			'024' => "ISLANDA",
			'624' => "ISRAELE",
			'999' => "ITALIA",
			'054' => "LETTONIA",
			'055' => "LITUANIA",
			'018' => "LUSSEMBURGO",
			'046' => "MALTA",
			'412' => "MESSICO",
			'028' => "NORVEGIA",
			'804' => "NUOVA ZELANDA",
			'230' => "PAESI AFRICA MEDITERRANEA",
			'003' => "PAESI BASSI",
			'060' => "POLONIA",
			'010' => "PORTOGALLO",
			'006' => "REGNO UNITO",
			'061' => "REPUBBLICA CECA",
			'066' => "ROMANIA",
			'075' => "RUSSIA",
			'063' => "SLOVACCHIA",
			'091' => "SLOVENIA",
			'011' => "SPAGNA",
			'400' => "STATI UNITI D'AMERICA",
			'388' => "SUD AFRICA",
			'030' => "SVEZIA",
			'036' => "SVIZZERA E LIECHTENSTEIN",
			'052' => "TURCHIA",
			'072' => "UCRAINA",
			'064' => "UNGHERIA",
			'484' => "VENEZUELA",
		];
	}

	/**
	 * Given a list of line records, stores them onto a file and reads
	 * its content in Base64. This is required to upload files onto the WS.
	 * 
	 * @param 	array 	$lines 	List of record lines to write and upload.
	 * 
	 * @return 	string 			The Base64 encoded content of the file written.
	 */
	protected function buildFileContent(array $lines)
	{
		// first off, clean up the report temporary directory to ensure we've got no other files
		JFolder::delete($this->report_tmp_path);

		// build the temporary TXT file name
		$txt_fname = uniqid('vitalbergatori_') . '.txt';

		// build the TXT file destination
		$txt_destination = $this->report_tmp_path . DIRECTORY_SEPARATOR . $txt_fname;

		// store the TXT file internally
		$txt_stored = JFile::write($txt_destination, implode("\r\n", $lines));

		if (!$txt_stored) {
			return '';
		}

		// read the file into a base64-encoded string
		$base64_content = base64_encode(file_get_contents($txt_destination));

		return (string) $base64_content;
	}

	/**
	 * Custom scoped action to upload the guest presence records.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function uploadPresences($scope = null, array $data = [])
	{
		if (!($data['lines'] ?? []) && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadRecordVitAlbergatori(\'uploadPresences\', \'presenze\');</script>',
			];
		}

		if (!($data['lines'] ?? [])) {
			// attempt to build the card lines if not set
			$data['lines'] = $this->buildRecordLines();
		}

		if (!$data['lines']) {
			throw new Exception('Nessun dato presente per la trasmissione dei record delle presenze turistiche.', 500);
		}

		$settings = $this->loadSettings();

		if (!$settings || empty($settings['propertyid']) || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// build file content in Base64
		$file_content = $this->buildFileContent($data['lines']);

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <CaricaFile xmlns="UploadDettaglioPresenze">
      <Utente>' . $settings['user'] . '</Utente>
      <Password>' . $settings['pwd'] . '</Password>
      <Struttura>' . $settings['propertyid'] . '</Struttura>
      <ContenutoFile>' . $file_content . '</ContenutoFile>
    </CaricaFile>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'CaricaFile', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->CaricaFileResponse->CaricaFileResult->Messaggio)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = stripos((string) $xmlBody->CaricaFileResponse->CaricaFileResult->Messaggio, 'error') !== false;

			if ($is_error) {
				$error_desc = (string) $xmlBody->CaricaFileResponse->CaricaFileResult->Messaggio;
				// terminate the execution in case of errors
				throw new Exception(sprintf("Errore caricamento tracciati record:\n%s", $error_desc), 500);
			}

			// get the number of line records
			$tot_submitted_lines = count($data['lines']);
			$tot_valid_lines = $tot_submitted_lines;

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($tot_submitted_lines == $tot_valid_lines ? 'successmade' : ($tot_valid_lines > 0 ? 'warn' : 'err')) . '">Totale record: ' . $tot_valid_lines . '</p>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// update history for all bookings affected
		foreach ($this->export_booking_ids as $bid) {
			// build extra data payload for the history event
			$data = [
				'transmitted' => 1,
				'method'      => 'uploadPresences',
				'report'      => $this->getFileName(),
			];
			// store booking history event
			VikBooking::getBookingHistoryInstance($bid)
				->setExtraData($data)
				->store('RP', $this->reportName . ' - Caricamento presenze turistiche');
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.transmit.ok',
				'title'   => $this->reportName . ' - Trasmissione presenze turistiche',
				'summary' => sprintf(
					'Sono stati trasmessi i dati degli ospiti con date dal %s al %s.',
					$this->exported_checkin_dates[0] ?? '',
					$this->exported_checkin_dates[1] ?? ''
				),
			];

			try {
				// store the notification record
				VBOFactory::getNotificationCenter()->store([$notification]);
			} catch (Exception $e) {
				// silently catch the error without doing anything
			}
		}

		return [
			'html'            => $html,
			'success'         => ($tot_valid_lines > 0),
			'valid_lines'     => $tot_valid_lines,
			'submitted_lines' => $tot_submitted_lines,
		];
	}

	/**
	 * Custom scoped action to upload the room occupancy records.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function uploadOccupancy($scope = null, array $data = [])
	{
		if (!($data['lines'] ?? []) && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadRecordVitAlbergatori(\'uploadOccupancy\', \'occupazione\');</script>',
			];
		}

		if (!($data['lines'] ?? [])) {
			// attempt to build the card lines if not set
			$data['lines'] = $this->buildRecordLines();
		}

		if (!$data['lines']) {
			throw new Exception('Nessun dato presente per la trasmissione dei record delle presenze turistiche.', 500);
		}

		$settings = $this->loadSettings();

		if (!$settings || empty($settings['propertyid']) || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// build file content in Base64
		$file_content = $this->buildFileContent($data['lines']);

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <CaricaFile xmlns="UploadDettaglioPresenze">
      <Utente>' . $settings['user'] . '</Utente>
      <Password>' . $settings['pwd'] . '</Password>
      <Struttura>' . $settings['propertyid'] . '</Struttura>
      <ContenutoFile>' . $file_content . '</ContenutoFile>
    </CaricaFile>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'CaricaFile', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->CaricaFileResponse->CaricaFileResult->Messaggio)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = stripos((string) $xmlBody->CaricaFileResponse->CaricaFileResult->Messaggio, 'error') !== false;

			if ($is_error) {
				$error_desc = (string) $xmlBody->CaricaFileResponse->CaricaFileResult->Messaggio;
				// terminate the execution in case of errors
				throw new Exception(sprintf("Errore caricamento tracciati record:\n%s", $error_desc), 500);
			}

			// get the number of line records
			$tot_submitted_lines = count($data['lines']);
			$tot_valid_lines = $tot_submitted_lines;

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($tot_submitted_lines == $tot_valid_lines ? 'successmade' : ($tot_valid_lines > 0 ? 'warn' : 'err')) . '">Totale record: ' . $tot_valid_lines . '</p>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// update history for all bookings affected
		foreach ($this->export_booking_ids as $bid) {
			// build extra data payload for the history event
			$data = [
				'transmitted' => 1,
				'method'      => 'uploadOccupancy',
				'report'      => $this->getFileName(),
			];
			// store booking history event
			VikBooking::getBookingHistoryInstance($bid)
				->setExtraData($data)
				->store('RP', $this->reportName . ' - Caricamento occupazione camere');
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.transmit.ok',
				'title'   => $this->reportName . ' - Trasmissione occupazione camere',
				'summary' => sprintf(
					'Sono stati trasmessi i dati di occupazione con date dal %s al %s.',
					$this->exported_checkin_dates[0] ?? '',
					$this->exported_checkin_dates[1] ?? ''
				),
			];

			try {
				// store the notification record
				VBOFactory::getNotificationCenter()->store([$notification]);
			} catch (Exception $e) {
				// silently catch the error without doing anything
			}
		}

		return [
			'html'            => $html,
			'success'         => ($tot_valid_lines > 0),
			'valid_lines'     => $tot_valid_lines,
			'submitted_lines' => $tot_submitted_lines,
		];
	}

	/**
	 * Custom scoped action to get the first closing month ("Primo mese in chiusura").
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function getFirstClosingMonth($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['propertyid']) || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <PrimoMeseInChiusura xmlns="UploadDettaglioPresenze">
      <Utente>' . $settings['user'] . '</Utente>
      <Password>' . $settings['pwd'] . '</Password>
      <Struttura>' . $settings['propertyid'] . '</Struttura>
    </PrimoMeseInChiusura>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'PrimoMeseInChiusura', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->PrimoMeseInChiusuraResponse->PrimoMeseInChiusuraResult->Messaggio)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = stripos((string) $xmlBody->PrimoMeseInChiusuraResponse->PrimoMeseInChiusuraResult->Messaggio, 'ok') === false;

			if ($is_error) {
				$error_desc = (string) $xmlBody->PrimoMeseInChiusuraResponse->PrimoMeseInChiusuraResult->Messaggio;
				// terminate the execution in case of errors
				throw new Exception(sprintf("Errore lettura primo mese in chiusura:\n%s", $error_desc), 500);
			}

			// get the datetime value
			$value = $xmlBody->PrimoMeseInChiusuraResponse->PrimoMeseInChiusuraResult->Chiusura ?? '';

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($value ? 'successmade' : 'err') . '">Primo mese per cui è possibile inserire chiusure: ' . $value . '</p>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		return [
			'html'  => $html,
			'value' => $value,
		];
	}

	/**
	 * Custom scoped action to get the last closed month ("Ultimo mese chiuso").
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function getLastMonthClosed($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['propertyid']) || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <UltimoMeseChiuso xmlns="UploadDettaglioPresenze">
      <Utente>' . $settings['user'] . '</Utente>
      <Password>' . $settings['pwd'] . '</Password>
      <Struttura>' . $settings['propertyid'] . '</Struttura>
    </UltimoMeseChiuso>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'UltimoMeseChiuso', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->UltimoMeseChiusoResponse->UltimoMeseChiusoResult->Messaggio)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = stripos((string) $xmlBody->UltimoMeseChiusoResponse->UltimoMeseChiusoResult->Messaggio, 'ok') === false;

			if ($is_error) {
				$error_desc = (string) $xmlBody->UltimoMeseChiusoResponse->UltimoMeseChiusoResult->Messaggio;
				// terminate the execution in case of errors
				throw new Exception(sprintf("Errore lettura ultimo mese chiuso:\n%s", $error_desc), 500);
			}

			// get the datetime value
			$value = $xmlBody->UltimoMeseChiusoResponse->UltimoMeseChiusoResult->Chiusura ?? '';

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($value ? 'successmade' : 'err') . '">Ultimo mese chiuso: ' . $value . '</p>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		return [
			'html'  => $html,
			'value' => $value,
		];
	}

	/**
	 * Custom scoped action to insert a closure for a period (year-month).
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function insertClosureC59M($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['propertyid']) || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		if (empty($data['month']) || empty($data['year'])) {
			throw new Exception(sprintf('[%s] error: mese ed anno di chiusura mancanti.', __METHOD__), 400);
		}

		// closing date time (month-year)
		$closing_mon_dtime = date('Y-m-d\TH:i:s', mktime(0, 0, 0, $data['month'], 1, $data['year']));

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <InserisciC59M xmlns="UploadDettaglioPresenze">
      <Utente>' . $settings['user'] . '</Utente>
      <Password>' . $settings['pwd'] . '</Password>
      <Struttura>' . $settings['propertyid'] . '</Struttura>
      <MeseAnno>' . $closing_mon_dtime . '</MeseAnno>
      <Camere>' . (int) ($data['camere'] ?? 1) . '</Camere>
      <Letti>' . (int) ($data['letti'] ?? 1) . '</Letti>
      <Bagni>' . (int) ($data['bagni'] ?? 1) . '</Bagni>
    </InserisciC59M>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'InserisciC59M', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->InserisciC59MResponse->InserisciC59MResult->Messaggio)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = stripos((string) $xmlBody->InserisciC59MResponse->InserisciC59MResult->Messaggio, 'ok') === false;

			if ($is_error) {
				$error_desc = (string) $xmlBody->InserisciC59MResponse->InserisciC59MResult->Messaggio;
				// terminate the execution in case of errors
				throw new Exception(sprintf("Errore inserimento chiusura:\n%s", $error_desc), 500);
			}

			// get the datetime value
			$value = $xmlBody->InserisciC59MResponse->InserisciC59MResult->Chiusura ?? $closing_mon_dtime;

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($value ? 'successmade' : 'err') . '">Chiusura inserita per: ' . $closing_mon_dtime . ' (' . $value . ')</p>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.closure.ok',
				'title'   => $this->reportName . ' - Inserimento chiusura C59-M',
				'summary' => sprintf(
					'Chiusura C59-M inserita per il mese %s.',
					$closing_mon_dtime
				),
			];

			try {
				// store the notification record
				VBOFactory::getNotificationCenter()->store([$notification]);
			} catch (Exception $e) {
				// silently catch the error without doing anything
			}
		}

		return [
			'html'    => $html,
			'success' => true,
			'value'   => $value,
		];
	}

	/**
	 * Custom scoped action to modify a closure for a period (year-month).
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function modifyClosureC59M($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['propertyid']) || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		if (empty($data['month']) || empty($data['year'])) {
			throw new Exception(sprintf('[%s] error: mese ed anno di chiusura mancanti.', __METHOD__), 400);
		}

		// closing date time (month-year)
		$closing_mon_dtime = date('Y-m-d\TH:i:s', mktime(0, 0, 0, $data['month'], 1, $data['year']));

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <ModificaC59M xmlns="UploadDettaglioPresenze">
      <Utente>' . $settings['user'] . '</Utente>
      <Password>' . $settings['pwd'] . '</Password>
      <Struttura>' . $settings['propertyid'] . '</Struttura>
      <MeseAnno>' . $closing_mon_dtime . '</MeseAnno>
      <Camere>' . (int) ($data['camere'] ?? 1) . '</Camere>
      <Letti>' . (int) ($data['letti'] ?? 1) . '</Letti>
      <Bagni>' . (int) ($data['bagni'] ?? 1) . '</Bagni>
    </ModificaC59M>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'ModificaC59M', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->ModificaC59MResponse->ModificaC59MResult->Messaggio)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = stripos((string) $xmlBody->ModificaC59MResponse->ModificaC59MResult->Messaggio, 'ok') === false;

			if ($is_error) {
				$error_desc = (string) $xmlBody->ModificaC59MResponse->ModificaC59MResult->Messaggio;
				// terminate the execution in case of errors
				throw new Exception(sprintf("Errore modifica chiusura:\n%s", $error_desc), 500);
			}

			// get the datetime value
			$value = $xmlBody->ModificaC59MResponse->ModificaC59MResult->Chiusura ?? $closing_mon_dtime;

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($value ? 'successmade' : 'err') . '">Chiusura modificata per: ' . $closing_mon_dtime . ' (' . $value . ')</p>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		return [
			'html'    => $html,
			'success' => true,
			'value'   => $value,
		];
	}

	/**
	 * Custom scoped action to remove a closure for a period (year-month).
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function removeClosureC59M($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['propertyid']) || empty($settings['user']) || empty($settings['pwd'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		if (empty($data['month']) || empty($data['year'])) {
			throw new Exception(sprintf('[%s] error: mese ed anno di chiusura mancanti.', __METHOD__), 400);
		}

		// closing date time (month-year)
		$closing_mon_dtime = date('Y-m-d\TH:i:s', mktime(0, 0, 0, $data['month'], 1, $data['year']));

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <EliminaC59M xmlns="UploadDettaglioPresenze">
      <Utente>' . $settings['user'] . '</Utente>
      <Password>' . $settings['pwd'] . '</Password>
      <Struttura>' . $settings['propertyid'] . '</Struttura>
      <MeseAnno>' . $closing_mon_dtime . '</MeseAnno>
    </EliminaC59M>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient($settings);

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'EliminaC59M', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->EliminaC59MResponse->EliminaC59MResult->Messaggio)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = stripos((string) $xmlBody->EliminaC59MResponse->EliminaC59MResult->Messaggio, 'ok') === false;

			if ($is_error) {
				$error_desc = (string) $xmlBody->EliminaC59MResponse->EliminaC59MResult->Messaggio;
				// terminate the execution in case of errors
				throw new Exception(sprintf("Errore rimozione chiusura:\n%s", $error_desc), 500);
			}

			// get the datetime value
			$value = $xmlBody->EliminaC59MResponse->EliminaC59MResult->Chiusura ?? $closing_mon_dtime;

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($value ? 'successmade' : 'err') . '">Chiusura eliminata per: ' . $closing_mon_dtime . ' (' . $value . ')</p>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		return [
			'html'    => $html,
			'success' => true,
			'value'   => $value,
		];
	}

	/**
	 * Establishes a SOAP connection with the remote WSDL and returns the client.
	 * Updates the internal properties according to settings related to the WS.
	 * 
	 * @param 	array 	$settings 	List of report settings for production or test mode.
	 * 
	 * @return 	SoapClient
	 * 
	 * @throws 	Exception
	 */
	protected function getWebServiceClient(array $settings = [])
	{
		if (($settings['endpoint'] ?? '') && !($settings['testmode'] ?? 1)) {
			// overwrite WS URL and location to production mode
			if (preg_match("/\.asmx$/i", $settings['endpoint'])) {
				// location given, rather than WS endpoint URL
				$this->ws_location = $settings['endpoint'];
				$this->wsdl_url = $settings['endpoint'] . '?WSDL';
			} elseif (preg_match("/\?WSDL$/i", $settings['endpoint'])) {
				// WS endpoint URL given, build location
				$this->ws_location = preg_replace("/\?WSDL$/i", '', $settings['endpoint']);
				$this->wsdl_url = $settings['endpoint'];
			} else {
				// trust the endpoint as both location and URL (should not really happen)
				$this->ws_location = $settings['endpoint'];
				$this->wsdl_url = $settings['endpoint'];
			}
		}

		try {
			return new SoapClient($this->wsdl_url, [
				'soap_version' => constant($this->soap_version),
			]);
		} catch (Throwable $e) {
			// prevent PHP fatal errors by catching and propagating them as Exceptions
			throw new Exception(sprintf('PHP Fatal Error: %s', $e->getMessage()), $e->getCode() ?: 500);
		}
	}

	/**
	 * Parses a SOAP XML message into a VBOXmlSoap object.
	 * 
	 * @param 	string 	$xmlMessage 	The SOAP XML message to load.
	 * 
	 * @return 	VBOXmlSoap
	 * 
	 * @throws 	Exception
	 */
	protected function loadXmlSoap($xmlMessage)
	{
		if (empty($xmlMessage)) {
			throw new Exception('Empty Soap XML message.', 500);
		}

		// suppress warning messages
		libxml_use_internal_errors(true);

		// parse the Soap XML message
		return simplexml_load_string($xmlMessage, VBOXmlSoap::class);
	}

	/**
	 * Registers the name to give to the file being exported.
	 * 
	 * @return 	void
	 */
	protected function registerExportFileName()
	{
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$ptype = VikRequest::getString('type', 'presenze', 'request');

		$this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '-' . $ptype . '.txt');
	}

	/**
	 * Helper method to quickly get a pax_data property for the guest.
	 * 
	 * @param 	array 	$pax_data 	the current pax_data stored.
	 * @param 	array 	$guests 	list of total guests per room.
	 * @param 	int 	$guest_ind 	the guest index.
	 * @param 	string 	$key 		the pax_data key to look for.
	 * 
	 * @return 	mixed 				null on failure or value fetched.
	 */
	protected function getGuestPaxDataValue($pax_data, $guests, $guest_ind, $key)
	{
		if (!is_array($pax_data) || !$pax_data || empty($key)) {
			return null;
		}

		// find room index for this guest number
		$room_num = 0;
		$use_guest_ind = $guest_ind;
		foreach ($guests as $room_index => $room_tot_guests) {
			// find the proper guest index for the room to which this belongs
			if ($use_guest_ind <= $room_tot_guests) {
				// proper room index found for this guest
				$room_num = $room_index;
				break;
			} else {
				// it's probably in a next room
				$use_guest_ind -= $room_tot_guests;
			}
		}

		// check if a value exists for the requested key in the found room and guest indexes
		if (isset($pax_data[$room_num]) && isset($pax_data[$room_num][$use_guest_ind])) {
			if (isset($pax_data[$room_num][$use_guest_ind][$key])) {
				// we've got a value previously stored
				return $pax_data[$room_num][$use_guest_ind][$key];
			}
		}

		// nothing was found
		return null;
	}

	/**
	 * Helper method to determine the exact number for this guest in the room booked.
	 * 
	 * @param 	array 	$guests 	list of total guests per room.
	 * @param 	int 	$guest_ind 	the guest index.
	 * 
	 * @return 	int 				the actual guest room index starting from 1.
	 */
	protected function calcGuestRoomIndex($guests, $guest_ind)
	{
		// find room index for this guest number
		$room_num = 0;
		$use_guest_ind = $guest_ind;
		foreach ($guests as $room_index => $room_tot_guests) {
			// find the proper guest index for the room to which this belongs
			if ($use_guest_ind <= $room_tot_guests) {
				// proper room index found for this guest
				$room_num = $room_index;
				break;
			} else {
				// it's probably in a next room
				$use_guest_ind -= $room_tot_guests;
			}
		}

		return $use_guest_ind;
	}

	/**
	 * Parses the file Nazioni.csv and returns an associative
	 * array with the code and name of the Nazione.
	 * Every line of the CSV is composed of: Codice, Nazione.
	 *
	 * @return 	array
	 */
	protected function loadItalianPoliceCountries()
	{
		$nazioni = [];

		$csv = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Nazioni.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 3) {
				continue;
			}

			$country_id = trim($v[0]);
			$country_name = trim($v[1]);
			$country_code = trim($v[2]);

			$nazioni[$country_id]['name'] = $country_name;
			$nazioni[$country_id]['three_code'] = $country_code;
		}

		return $nazioni;
	}
}
