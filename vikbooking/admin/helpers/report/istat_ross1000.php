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
 * ISTAT ROSS 1000 è valido per diverse regioni. Sicuramente per la Romagna, Veneto e Piemonte.
 * Sviluppato e mantenuto da GIES (Repubblica di San Marino). Simile a SITRA. Il sistema
 * supporta diversi URL regionali/cittadini per l'eventuale trasmissione a mezzo WSDL.
 * 
 * @link 	Piemonte https://piemontedatiturismo.regione.piemonte.it/ws/checkinV2?wsdl
 * @link 	Città Metropolitana di Firenze https://turismo5firenze.regione.toscana.it/ws/checkinV2?wsdl
 * @link 	Provincia di Pistoia https://turismo5pistoia.regione.toscana.it/ws/checkinV2?wsdl
 * @link 	Provincia di Prato https://turismo5prato.regione.toscana.it/ws/checkinV2?wsdl
 * @link 	Abruzzo https://app.regione.abruzzo.it/Turismo5/ws/checkinV2?wsdl
 * @link 	Veneto https://flussituristici.regione.veneto.it/ws/checkinV2?wsdl
 * @link 	Emilia-Romagna https://datiturismo.regione.emilia-romagna.it/ws/checkinV2?wsdl
 * @link 	Marche https://istrice-ross1000.turismo.marche.it/ws/checkinV2?wsdl
 * @link 	Lombardia https://www.flussituristici.servizirl.it/Turismo5/app/ws/checkinV2?wsdl
 * @link 	Calabria https://sirdat.regione.calabria.it/ws/checkinV2?wsdl
 * @link 	Sardegna https://sardegnaturismo.ross1000.it/ws/checkinV2?wsdl
 * 
 * @see 	Tracciato_XML-WEBSERVICE-2.4-_-2.pdf
 * 
 * @since 	1.15.4 (J) - 1.5.10 (WP) report introduced.
 * @since 	1.17.2 (J) - 1.7.2 (WP)  report refactoring with settings and custom scoped actions.
 */
class VikBookingReportIstatRoss1000 extends VikBookingReport
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
	 * Property 'customExport' is used by the View to display custom export buttons.
	 */
	public $customExport = '';

	/**
	 * List of municipality and provinces.
	 * 
	 * @var  	array
	 */
	protected $comuniProvince = [];

	/**
	 * List of country codes.
	 * 
	 * @var  	array
	 */
	protected $nazioni = [];

	/**
	 * List of tourism type codes.
	 * 
	 * @var  	array
	 */
	protected $tourismTypes = [
		'CULTURALE' => 'Culturale',
		'BALNEARE' => 'Balneare',
		'CONGRESSUALE/AFFARI' => 'Congressuale/Affari',
		'FIERISTICO' => 'Fieristico',
		'SPORTIVO/FITNESS' => 'Sportivo/Fitness',
		'SCOLASTICO' => 'Scolastico',
		'RELIGIOSO' => 'Religioso',
		'SOCIALE' => 'Sociale',
		'PARCHI TEMATICI' => 'Parchi Tematici',
		'TERMALE/TRATTAMENTI SALUTE' => 'Termale/Trattamenti salute',
		'ENOGASTRONOMICO' => 'Enogastronomico',
		'CICLOTURISMO' => 'Cicloturismo',
		'ESCURSIONISTICO/NATURALISTICO' => 'Escursionistico/Naturalistico',
		'ALTRO MOTIVO' => 'Altro motivo',
		'NON SPECIFICATO' => 'Non specificato',
	];

	/**
	 * List of meanings of transport codes.
	 * 
	 * @var  	array
	 */
	protected $meaningsOfTransport = [
		'AUTO' => 'Auto',
		'AEREO' => 'Aereo',
		'AEREO+PULLMAN' => 'Aereo+Pullman',
		'AEREO+NAVETTA/TAXI/AUTO' => 'Aereo+Navetta/Taxi/Auto',
		'AEREO+TRENO' => 'Aereo+Treno',
		'TRENO' => 'Treno',
		'PULLMAN' => 'Pullman',
		'CARAVAN/AUTOCARAVAN' => 'Caravan/Autocaravan',
		'BARCA/NAVE/TRAGHETTO' => 'Barca/Nave/Traghetto',
		'MOTO' => 'Moto',
		'BICICLETTA' => 'Bicicletta',
		'A PIEDI' => 'A piedi',
		'ALTRO MOTIVO' => 'Altro motivo',
		'NON SPECIFICATO' => 'Non specificato',
	];

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
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = JText::translate('ISTAT Ross 1000');
		$this->reportFilters = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

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
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	public function allowsProfileSettings()
	{
		// allow multiple report profile settings
		return true;
	}

	/**
	 * @inheritDoc
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)
	 */
	public function getSettingFields()
	{
		$example_urls = [
			[
				'name' => 'Piemonte',
				'url' => 'https://piemontedatiturismo.regione.piemonte.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Città Metropolitana di Firenze',
				'url' => 'https://turismo5firenze.regione.toscana.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Provincia di Pistoia',
				'url' => 'https://turismo5pistoia.regione.toscana.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Provincia di Prato',
				'url' => 'https://turismo5prato.regione.toscana.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Abruzzo',
				'url' => 'https://app.regione.abruzzo.it/Turismo5/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Veneto',
				'url' => 'https://flussituristici.regione.veneto.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Emilia-Romagna',
				'url' => 'https://datiturismo.regione.emilia-romagna.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Marche',
				'url' => 'https://istrice-ross1000.turismo.marche.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Lombardia',
				'url' => 'https://www.flussituristici.servizirl.it/Turismo5/app/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Calabria',
				'url' => 'https://sirdat.regione.calabria.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Sardegna',
				'url' => 'https://sardegnaturismo.ross1000.it/ws/checkinV2?wsdl',
			],
			[
				'name' => 'Liguria',
				'url' => 'https://turismows.regione.liguria.it/ws/checkinV2?wsdl',
			],
		];

		$example_urls_str = '';
		foreach ($example_urls as $example_url) {
			$example_urls_str .= '<li><strong>' . $example_url['name'] . '</strong>: ' . $example_url['url'] . '</li>' . "\n";
		}

		// count room units
		$tot_room_units = $this->countRooms();

		return [
			'title' => [
				'type'  => 'custom',
				'label' => '',
				'html'  => '<p class="info">Configura le impostazioni per la generazione dei tracciati record XML e per la trasmissione delle informazioni verso il WebService della tua regione o procinvia.</p>',
			],
			'codstru' => [
				'type'  => 'text',
				'label' => 'Codice Struttura',
				'help'  => 'Codice univoco di identificazione della tua struttura assegnato dall\'Amministrazione competente.',
			],
			'numcamere' => [
				'type'    => 'number',
				'label'   => 'Numero camere disponibili',
				'help'    => 'Numero totale di camere disponibili.',
				'min'     => 1,
				'default' => $tot_room_units,
			],
			'numletti' => [
				'type'    => 'number',
				'label'   => 'Numero letti disponibili',
				'help'    => 'Numero totale di letti disponibili.',
				'min'     => 0,
				'default' => $tot_room_units,
			],
			'user' => [
				'type'  => 'text',
				'label' => 'Utente',
				'help'  => 'Nome utente assegnato dall\'Ufficio Turismo di competenza.',
			],
			'pwd' => [
				'type'  => 'password',
				'label' => 'Password',
				'help'  => 'Password assegnata dall\'Ufficio Turismo di competenza.',
			],
			'endpoint' => [
				'type'  => 'text',
				'label' => 'WS Endpoint URL',
				'help'  => 'Indirizzo URL web-service del sito web dell\'ente al quale trasmettere i dati.',
			],
			'exampleurls' => [
				'type'  => 'custom',
				'label' => '',
				'html'  => '<p class="info">Lista Endpoint web-service:</p><ul>' . $example_urls_str . '</ul><p class="warn">È consigliato verificare tramite il proprio portale ISTAT regionale l\'effettivo URL del Web-Service da utilizzare per la trasmissione dei flussi turistici. In alcuni casi l\'indirizzo URL potrebbe non necessitare della parte finale <code>?wsdl</code>.</p>',
			],
		];
	}

	/**
	 * @inheritDoc
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)
	 */
	public function getScopedActions($scope = null, $visible = true)
	{
		// list of custom actions for this report
		$actions = [
			[
				'id' => 'transmitRecords',
				'name' => 'Trasmetti flussi turistici',
				'help' => 'Trasmette i dati riguardanti i flussi turistici.',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
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
	 * @return 	array
	 */
	public function getFilters()
	{
		if ($this->reportFilters) {
			//do not run this method twice, as it could load JS and CSS files.
			return $this->reportFilters;
		}

		$app = JFactory::getApplication();

		//get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		//load the jQuery UI Datepicker
		$this->loadDatePicker();

		//custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadSchedaIstat();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download File</span></a>';

		// build the hidden values for the selection of Comuni & Province and much more.
		$this->comuniProvince = $this->loadComuniProvince();
		$this->nazioni = $this->loadNazioni();

		// open hidden fields
		$hidden_vals = '<div id="vbo-report-ross1000-hidden" style="display: none;">';

		// build params container HTML structure
		$hidden_vals .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$hidden_vals .= '	<div class="vbo-params-wrap">';
		$hidden_vals .= '		<div class="vbo-params-container">';
		$hidden_vals .= '			<div class="vbo-params-block vbo-params-block-noborder">';

		// comuni
		$hidden_vals .= '	<div id="vbo-report-ross1000-comune" class="vbo-report-ross1000-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Comune</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-comune" onchange="vboReportChosenComune(this);">';
		$hidden_vals .= '				<option value=""></option>'."\n";
		$hidden_vals .= '				<option value="ES">- Estero -</option>'."\n";
		foreach ($this->comuniProvince['comuni'] ?? [] as $code => $comune) {
			if (empty($code)) {
				continue;
			}
			$hidden_vals .= '			<option value="' . $code . '">' . (is_array($comune) ? $comune['name'] : '') . '</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// mezzi di trasporto 
		$hidden_vals .= '	<div id="vbo-report-trasporto" class="vbo-report-ross1000-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Mezzo di trasporto</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-trasporto" onchange="vboReportChosenTrasporto(this);"><option value=""></option>';
		foreach ($this->meaningsOfTransport as $key => $value) {
			$hidden_vals .= '		<option value="' . $value . '">' . $value . '</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// tipo di turismo
		$hidden_vals .= '	<div id="vbo-report-turismo" class="vbo-report-ross1000-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Tipo di turismo</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-turismo" onchange="vboReportChosenTurismo(this);"><option value=""></option>';
		foreach ($this->tourismTypes as $key => $value) {
			$hidden_vals .= '		<option value="' . $value . '">' . $value . '</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// province
		$hidden_vals .= '	<div id="vbo-report-ross1000-provincia" class="vbo-report-ross1000-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Provincia</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-provincia" onchange="vboReportChosenProvincia(this);"><option value=""></option>';
		foreach ($this->comuniProvince['province'] ?? [] as $code => $provincia) {
			$hidden_vals .= '		<option value="' . $code . '">' . $provincia . '</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// nazioni
		$hidden_vals .= '	<div id="vbo-report-ross1000-nazione" class="vbo-report-ross1000-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Paese</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-nazione" onchange="vboReportChosenNazione(this);"><option value=""></option>';
		foreach ($this->nazioni as $code => $nazione) {
			$hidden_vals .= '			<option value="' . $code . '">' . $nazione['name'] . '</option>';
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// sesso
		$hidden_vals .= '	<div id="vbo-report-ross1000-sesso" class="vbo-report-ross1000-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Sesso</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-sesso" onchange="vboReportChosenSesso(this);"><option value=""></option>';
		$sessos = [
			1 => 'M',
			2 => 'F'
		];
		foreach ($sessos as $code => $ses) {
			$hidden_vals .= '		<option value="'.$code.'">'.$ses.'</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// data di nascita
		$hidden_vals .= '	<div id="vbo-report-ross1000-dbirth" class="vbo-report-ross1000-selcont vbo-param-container" style="display: none;">';
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

		// close hidden fields
		$hidden_vals .= '</div>';

		//From Date Filter (with hidden values for the dropdown menus of Comuni, Province, Stati etc..)
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
				'selected_values' => (array) $app->input->get('listings', [], 'array'),
			]) . '</span>',
			'type' => 'select',
			'multiple' => true,
			'name' => 'listings',
		);
		array_push($this->reportFilters, $filter_opt);

		// append button to save the data when creating manual values
		$filter_opt = array(
			'label' => '<label class="vbo-report-ross1000-manualsave" style="display: none;">' . JText::translate('VBOGUESTSDETAILS') . '</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-ross1000-manualsave" style="display: none;" onclick="vboRoss1000SaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		// jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$js_ajax_base  = VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile);
		$js_save_icn   = VikBookingIcons::i('save');
		$js_saving_icn = VikBookingIcons::i('circle-notch', 'fa-spin fa-fw');
		$js_saved_icn  = VikBookingIcons::i('check-circle');

		$js = 'var reportActiveCell = null, reportObj = {};
		var vbo_report_js_ajax_base = "' . $js_ajax_base . '";
		var vbo_ross1000_save_icn = "' . $js_save_icn . '";
		var vbo_ross1000_saving_icn = "' . $js_saving_icn . '";
		var vbo_ross1000_saved_icn = "' . $js_saved_icn . '";
		jQuery(function() {
			// prepare main filters
			jQuery(".vbo-report-datepicker:input").datepicker({
				maxDate: "+1m",
				dateFormat: "'.$this->getDateFormat('jui').'",
				onSelect: vboReportCheckDates
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
			// prepare filler helpers
			jQuery("#vbo-report-ross1000-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-comune").select2({placeholder: "- Seleziona un Comune -", width: "200px"});
			jQuery("#choose-provincia").select2({placeholder: "- Seleziona una Provincia -", width: "200px"});
			jQuery("#choose-nazione").select2({placeholder: "- Seleziona una Nazione -", width: "200px"});
			jQuery("#choose-sesso").select2({placeholder: "- Seleziona Sesso -", width: "200px"});
			jQuery("#choose-turismo").select2({placeholder: "- Seleziona Tipo di Turismo -", width: "300px"});
			jQuery("#choose-trasporto").select2({placeholder: "- Seleziona Mezzo di Trasporto -", width: "300px"});

			jQuery("#choose-dbirth").datepicker({
				maxDate: 0,
				dateFormat: "dd/mm/yy",
				changeMonth: true,
				changeYear: true,
				yearRange: "'.(date('Y') - 100).':'.date('Y').'"
			});
			// click events
			jQuery(".vbo-report-load-comune").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-ross1000-selcont").hide();
				jQuery("#vbo-report-ross1000-comune").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
				});
			});
			jQuery(".vbo-report-load-provincia").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-ross1000-selcont").hide();
				jQuery("#vbo-report-ross1000-provincia").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
				});
			});
			jQuery(".vbo-report-load-nazione, .vbo-report-load-cittadinanza").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-ross1000-selcont").hide();
				jQuery("#vbo-report-ross1000-nazione").show();
				vboShowOverlay();
			});
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-ross1000-selcont").hide();
				jQuery("#vbo-report-ross1000-sesso").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-turismo").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-ross1000-selcont").hide();
				jQuery("#vbo-report-turismo").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
				});
			});
			jQuery(".vbo-report-load-trasporto").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-ross1000-selcont").hide();
				jQuery("#vbo-report-trasporto").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
				});
			});
			jQuery(".vbo-report-load-dbirth").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-ross1000-selcont").hide();
				jQuery("#vbo-report-ross1000-dbirth").show();
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

		function vboReportChosenComune(comune) {
			var c_code = comune.value;
			var c_val = comune.options[comune.selectedIndex].text;
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
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-docplace")) {
						reportObj[nowindex]["docplace"] = c_code;
					} else {
						reportObj[nowindex]["comune_s"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-comune").val("").select2("data", null, false);
			jQuery(".vbo-report-ross1000-manualsave").show();
		}

		function vboReportChosenProvincia(prov) {
			var c_code = prov.value;
			var c_val = prov.options[prov.selectedIndex].text;
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
					reportObj[nowindex]["comune_s"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-provincia").val("").select2("data", null, false);
			jQuery(".vbo-report-ross1000-manualsave").show();
		}

		function vboReportChosenNazione(naz) {
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
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-nazione")) {
						reportObj[nowindex]["country_s"] = c_code;
					} else if (jQuery(reportActiveCell).hasClass("vbo-report-load-docplace")) {
						reportObj[nowindex]["docplace"] = c_code;
					} else {
						reportObj[nowindex]["country_c"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-nazione").val("").select2("data", null, false);
			jQuery(".vbo-report-ross1000-manualsave").show();
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
			jQuery(".vbo-report-ross1000-manualsave").show();
		}

		function vboReportChosenTrasporto(trasporto) {
			var c_code = trasporto.value;
			var c_val = trasporto.options[trasporto.selectedIndex].text;
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
					reportObj[nowindex]["mezzo"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-trasporto").val("").select2("data", null, false);
			jQuery(".vbo-report-ross1000-manualsave").show();
		}

		function vboReportChosenTurismo(turismo) {
			var c_code = turismo.value;
			var c_val = turismo.options[turismo.selectedIndex].text;
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
					reportObj[nowindex]["turismo"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-turismo").val("").select2("data", null, false);
			jQuery(".vbo-report-ross1000-manualsave").show();
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
			jQuery(".vbo-report-ross1000-manualsave").show();
		}

		// download function
		function vboDownloadSchedaIstat(type) {
			if (!confirm("Sei sicuro di aver compilato tutti i dati?")) {
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
		}

		// save data after manual fillers
		function vboRoss1000SaveData() {
			jQuery("button.vbo-report-ross1000-manualsave").find("i").attr("class", vbo_ross1000_saving_icn);
			VBOCore.doAjax(
				vbo_report_js_ajax_base,
				{
					call: "updatePaxData",
					params: reportObj,
					tmpl: "component"
				},
				(response) => {
					if (!response || !response[0]) {
						alert("An error occurred.");
						return false;
					}
					jQuery("button.vbo-report-ross1000-manualsave").addClass("btn-success").find("i").attr("class", vbo_ross1000_saved_icn);
				},
				(error) => {
					alert(error.responseText);
					jQuery("button.vbo-report-ross1000-manualsave").removeClass("btn-success").find("i").attr("class", vbo_ross1000_save_icn);
				}
			);
		}';
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

		$currency_symb = VikBooking::getCurrencySymb();
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

		// set the dates being exported
		$this->exported_checkin_dates = [
			date('Y-m-d', $from_ts),
			date('Y-m-d', $to_ts),
		];

		// load settings
		$settings = $this->loadSettings();

		if (empty($settings['codstru'])) {
			$this->setError('Inserisci il codice della tua Struttura dalle impostazioni.<br/>Si tratta di un codice univoco di identificazione che ti viene assegnato dall\'Amministrazione competente.');
			return false;
		}

		// query to obtain the records (all check-ins, check-outs and reservations created within the dates filter)
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
				$this->dbo->qn('or.t_first_name'),
				$this->dbo->qn('or.t_last_name'),
				$this->dbo->qn('or.cust_cost'),
				$this->dbo->qn('or.cust_idiva'),
				$this->dbo->qn('or.extracosts'),
				$this->dbo->qn('or.room_cost'),
				$this->dbo->qn('co.idcustomer'),
				$this->dbo->qn('co.pax_data'),
				$this->dbo->qn('c.first_name'),
				$this->dbo->qn('c.last_name'),
				$this->dbo->qn('c.country', 'customer_country'),
				$this->dbo->qn('c.address'),
				$this->dbo->qn('c.doctype'),
				$this->dbo->qn('c.docnum'),
				$this->dbo->qn('c.gender'),
				$this->dbo->qn('c.bdate'),
				$this->dbo->qn('c.pbirth'),
			])
			->from($this->dbo->qn('#__vikbooking_orders', 'o'))
			->leftJoin($this->dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $this->dbo->qn('or.idorder') . ' = ' . $this->dbo->qn('o.id'))
			->leftJoin($this->dbo->qn('#__vikbooking_customers_orders', 'co') . ' ON ' . $this->dbo->qn('co.idorder') . ' = ' . $this->dbo->qn('o.id'))
			->leftJoin($this->dbo->qn('#__vikbooking_customers', 'c') . ' ON ' . $this->dbo->qn('c.id') . ' = ' . $this->dbo->qn('co.idcustomer'))
			->where($this->dbo->qn('o.status') . ' = ' . $this->dbo->q('confirmed'))
			->where($this->dbo->qn('o.closure') . ' = 0')
			// fetch all bookings with check-in, check-out or reservation date within date filters
			->andWhere([
				'(' . $this->dbo->qn('o.checkin') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts . ')',
				'(' . $this->dbo->qn('o.checkout') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts . ')',
				'(' . $this->dbo->qn('o.ts') . ' BETWEEN ' . $from_ts . ' AND ' . $to_ts . ')',
			], 'OR')
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
			$this->setError('Nessun check-in, check-out o prenotazione nelle date selezionate.');
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

		// define the columns of the report
		$this->cols = array(
			// booking code idswh for the PMS software
			array(
				'key' => 'idswh',
				'label' => 'IDSWH',
				'tip' => 'Codice identificativo del movimento. Generato automaticamente dal sistema.',
			),
			// booking id (this is not a duplicate value of "idbooking", it should go before "resdate", "checkin" and "checkout")
			array(
				'key' => 'booking_id',
				'label' => 'ID',
				// hide this field in the View
				'ignore_view' => 1,
			),
			// reservation date
			array(
				'key' => 'resdate',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPCHOOSEBUSYORDATE'),
			),
			// check-in
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPICKUPAT'),
			),
			// checkout
			array(
				'key' => 'checkout',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBRELEASEAT'),
			),
			// nome
			array(
				'key' => 'nome',
				'label' => JText::translate('VBTRAVELERNAME'),
			),
			// cognome
			array(
				'key' => 'cognome',
				'label' => JText::translate('VBTRAVELERLNAME'),
			),
			// sesso
			array(
				'key' => 'gender',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERGENDER')
			),
			// data di nascita
			array(
				'key' => 'dbirth',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERBDATE')
			),
			// cittadinanza
			array(
				'key' => 'citizen',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Cittadinanza'
			),
			// residenza
			array(
				'key' => 'stares',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Stato di Residenza'
			),
			// comune di residenza
			array(
				'key' => 'comres',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Comune Residenza',
				'tip' => 'Inserire il comune di residenza solo se il cittadino è di nazionalità italiana.'
			),
			// tipo
			array(
				'key' => 'tipo',
				'attr' => array(
					'class="vbo-report-longlbl"',
				),
				'label' => 'Tipo Alloggiato',
			),
			// tipo di turismo 
			array(
				'key' => 'turismo',
				'attr' => array(
					'class="center"',
				),
				'label' => 'Tipo di Turismo',
			),
			// mezzo di trasporto 
			array(
				'key' => 'mezzo',
				'attr' => array(
					'class="center"',
				),
				'label' => 'Mezzo di Trasporto',
			),
			// numero di ospiti
			array(
				'key' => 'guestsnum',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Numero Ospiti',
				'ignore_view' => 1,
			),
			// occupazione
			array(
				'key' => 'roomsbooked',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Camere',
				'ignore_view' => 1,
			),
			// id booking
			array(
				'key' => 'idbooking',
				'attr' => array(
					'class="center"'
				),
				'label' => 'ID / #',
			),
		);

		// line number (to facilitate identifying a specific guest in case of errors with the file submission)
		$line_number = 0;

		// loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		foreach ($bookings as $gbook) {
			$guestsnum = 0;
			$guests_rows = [$gbook[0]];
			$room_guests = [];
			$tot_guests_rows = 1;
			
			$tipo = 16;
			// Codici Tipo Alloggiato
			// 16 = Ospite Singolo
			// 17 = Capofamiglia
			// 18 = Capogruppo
			// 19 = Familiare
			// 20 = Membro Gruppo

			foreach ($gbook as $book) {
				$guestsnum += $book['adults'] + $book['children'];
				$room_guests[] = ($book['adults'] + $book['children']);
			}
			$pax_data = null;
			if (!empty($gbook[0]['pax_data'])) {
				$pax_data = json_decode($gbook[0]['pax_data'], true);
				if (is_array($pax_data) && count($pax_data)) {
					$guests_rows[0]['pax_data'] = $pax_data;
					$tot_guests_rows = 0;
					foreach ($pax_data as $roomguests) {
						$tot_guests_rows += count($roomguests);
					}
					for ($i = 1; $i < $tot_guests_rows; $i++) {
						array_push($guests_rows, $guests_rows[0]);
					}
					$tipo = count($guests_rows) > 1 ? 17 : $tipo;
				}
			}

			// create one row for each guest
			$guest_ind = 1;
			foreach ($guests_rows as $ind => $guests) {
				// prepare row record
				$insert_row = [];

				// find the actual guest-room-index
				$guest_room_ind = $this->calcGuestRoomIndex($room_guests, $guest_ind);

				// booking code idswh for the PMS software
				array_push($insert_row, array(
					'key' => 'idswh',
					'value' => $guests['id'] . '-' . $guest_ind,
				));

				// booking id
				array_push($insert_row, array(
					'key' => 'booking_id',
					'value' => $guests['id'],
					'ignore_view' => 1,
				));

				// reservation date
				array_push($insert_row, array(
					'key' => 'resdate',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('d/m/Y', $val);
					},
					'callback_export' => function($val) {
						return date('Ymd', $val);
					},
					'value' => $guests['ts'],
				));

				// checkin date
				array_push($insert_row, array(
					'key' => 'checkin',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('d/m/Y', $val);
					},
					'callback_export' => function($val) {
						return date('Ymd', $val);
					},
					'value' => $guests['checkin'],
				));

				// checkout date
				array_push($insert_row,array(
					'key' => 'checkout',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('d/m/Y', $val);
					},
					'callback_export' => function($val) {
						return date('Ymd', $val);
					},
					'value' => $guests['checkout'],
				));

				// nome
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				$pax_nome = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'first_name');
				$nome = !empty($pax_nome) ? $pax_nome : $nome;
				array_push($insert_row, array(
					'key' => 'nome',
					'value' => $nome
				));

				// cognome
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				$pax_cognome = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'last_name');
				$cognome = !empty($pax_cognome) ? $pax_cognome : $cognome;
				array_push($insert_row, array(
					'key' => 'cognome',
					'value' => $cognome
				));

				// sesso
				$gender = !empty($guests['gender']) && $guest_ind < 2 ? strtoupper($guests['gender']) : '';
				$gender = $gender == 'F' ? 2 : ($gender == 'M' ? 1 : $gender);
				$pax_gender = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'gender');
				$gender = !empty($pax_gender) ? $pax_gender : $gender;
				if (is_numeric($gender)) {
					$gender = (int)$gender;
				} elseif (!strcasecmp($gender, 'F')) {
					$gender = 2;
				} elseif (!strcasecmp($gender, 'M')) {
					$gender = 1;
				}
				array_push($insert_row, array(
					'key' => 'gender',
					'attr' => array(
						'class="center'.(empty($gender) ? ' vbo-report-load-sesso' : '').'"'
					),
					'callback' => function($val) {
						return $val == 2 ? 'F' : ($val == 1 ? 'M' : '?');
					},
					'callback_export' => function($val) {
						return $val == 2 ? 'F' : ($val == 1 ? 'M' : '?');
					},
					'value' => $gender
				));

				// data di nascita
				$dbirth = !empty($guests['bdate']) && $guest_ind < 2 ? VikBooking::getDateTimestamp($guests['bdate'], 0, 0) : '';
				$pax_dbirth = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'date_birth');
				$dbirth = !empty($pax_dbirth) ? $pax_dbirth : $dbirth;
				if (!empty($dbirth)) {
					$dbirth = str_replace(['/', '-', '.'], VikBooking::getDateSeparator(), (string) $dbirth);
				}
				$dbirth = (strpos($dbirth, '/') === false && strpos($dbirth, VikBooking::getDateSeparator()) === false) ? $dbirth : VikBooking::getDateTimestamp($dbirth, 0, 0);
				array_push($insert_row, array(
					'key' => 'dbirth',
					'attr' => array(
						'class="center'.(empty($dbirth) ? ' vbo-report-load-dbirth' : '').'"'
					),
					'callback' => function($val) {
						if (!empty($val) && strpos($val, VikBooking::getDateSeparator()) === false) {
							return date('d/m/Y', $val);
						}
						if (!empty($val) && strpos($val, VikBooking::getDateSeparator()) !== false) {
							return $val;
						}
						return '?';
					},
					'callback_export' => function($val) {
						if (!empty($val) && is_numeric($val)) {
							return date('Ymd', $val);
						}
						if (!empty($val) && strlen($val) > 8) {
							return date('Ymd', VikBooking::getDateTimestamp($val));
						}
						return $val ?: '?';
					},
					'value' => $dbirth
				));

				// cittadinanza (compatible with pax data field of driver "Italy")
				$pax_country_c = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'country_c');
				$citizen = !empty($guests['country']) && $guest_ind < 2 ? $guests['country'] : '';
				$citizenval = '';
				if (!empty($citizen) && $guest_ind < 2) {
					$citizenval = $this->checkCountry($citizen);
				}

				// check nationality field from pre-checkin
				$pax_citizen = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'nationality');
				$citizen = !empty($pax_citizen) ? $pax_citizen : $citizen;
				$citizen = !empty($pax_country_c) ? $pax_country_c : $citizen;
				$citizenval = $this->checkCountry((!empty($pax_country_c) ? $pax_country_c : $citizen));
				array_push($insert_row, array(
					'key' => 'citizen',
					'attr' => array(
						'class="center'.(empty($citizen) ? ' vbo-report-load-cittadinanza' : '').'"',
					),
					'callback' => function($val) {
						return !empty($val) && isset($this->nazioni[$val]) ? $this->nazioni[$val]['name'] : '?';
					},
					'no_export_callback' => 1,
					'value' => !empty($citizenval) ? $citizenval : '',
				));

				// stato di residenza
				$provstay = '';
				$pax_provstay = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, ($guest_ind < 2 ? 'country_s' : 'country_b'));
				$provstay = !empty($pax_provstay) ? $pax_provstay : $provstay;
				array_push($insert_row, array(
					'key' => 'stares',
					'attr' => array(
						'class="center'.(empty($provstay) ? ' vbo-report-load-nazione' : '').'"',
					),
					'callback' => function($val) {
						if (!empty($val) && isset($this->nazioni[$val])) {
							return $this->nazioni[$val]['name'];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $provstay,
				));

				// comune di residenza
				$comstay = '';
				$pax_comstay = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, ($guest_ind < 2 ? 'comune_s' : 'comune_b'));
				$pax_comstay = $pax_comstay ?: $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, ($guest_ind < 2 ? 'province_s' : 'province_b'));
				$comstay = !empty($pax_comstay) ? $pax_comstay : $comstay;
				if (empty($comstay) && !empty($provstay) && $provstay != '100000100' && strtoupper(substr((string) $provstay, 0, 2)) != 'IT') {
					// we assume the guest is not Italian
					$comstay = 'ES';
				}
				array_push($insert_row, array(
					'key' => 'comres',
					'attr' => array(
						'class="center'.(empty($comstay) ? ' vbo-report-load-comune' : ' vbo-report-load-comune vbo-report-load-elem-filled').'"'
					),
					'callback' => function($val) {
						if (!empty($val) && isset($this->comuniProvince['comuni'][$val])) {
							return $this->comuniProvince['comuni'][$val]['name'];
						}
						if ($val === 'ES') {
							return 'Estero';
						}
						// information is missing and should be provided
						return $val ?: '?';
					},
					'no_export_callback' => 1,
					'value' => $comstay,
				));

				// tipo alloggiato
				$use_tipo = $ind > 0 && $tipo == 17 ? 19 : $tipo;
				$pax_guest_type = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'guest_type');
				$use_tipo = !empty($pax_guest_type) ? $pax_guest_type : $use_tipo;
				array_push($insert_row, array(
					'key' => 'tipo',
					'callback' => function($val) {
						switch ($val) {
							case 16:
								return 'Ospite Singolo';
							case 17:
								return 'Capofamiglia';
							case 18:
								return 'Capogruppo';
							case 19:
								return 'Familiare';
							case 20:
								return 'Membro Gruppo';
						}
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $use_tipo
				));

				// tipo di turismo
				$pax_turismo = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'turismo');
				array_push($insert_row, array(
					'key' => 'turismo',
					'attr' => array(
						'class="center' . (empty($pax_turismo) ? ' vbo-report-load-turismo vbo-report-load-elem-filled' : ' vbo-report-load-field-optional') . '"',
					),
					'callback' => function($val) {
						return $this->tourismTypes[$val] ?? $val;
					},
					'no_export_callback' => 1,
					'value' => ($pax_turismo ?: 'NON SPECIFICATO'),
				));

				// mezzo di trasporto
				$pax_mezzo = $this->getGuestPaxDataValue($pax_data, $room_guests, $guest_ind, 'mezzo');
				array_push($insert_row, array(
					'key' => 'mezzo',
					'attr' => array(
						'class="center' . (empty($pax_mezzo) ? ' vbo-report-load-trasporto vbo-report-load-elem-filled' : ' vbo-report-load-field-optional') . '"',
					),
					'callback' => function($val) {
						return $this->meaningsOfTransport[$val] ?? $val;
					},
					'no_export_callback' => 1,
					'value' => ($pax_mezzo ?: 'NON SPECIFICATO'),
				));

				// numero di ospiti
				array_push($insert_row, array(
					'key' => 'guestsnum',
					'attr' => array(
						'class="center"',
					),
					'value' => $guestsnum,
					'ignore_view' => 1,
				));

				// camere prenotate 
				array_push($insert_row, array(
					'key' => 'roomsbooked',
					'attr' => array(
						'class="center"',
					),
					'value' => count($gbook),
					'ignore_view' => 1,
				));

				// id booking
				array_push($insert_row, array(
					'key' => 'idbooking',
					'attr' => array(
						'class="center"',
					),
					'callback' => function($val) use ($line_number) {
						// make sure to keep the data-bid attribute as it's used by JS to identify the booking ID
						return '<a data-bid="' . $val . '" href="index.php?option=com_vikbooking&task=editorder&cid[]=' . $val . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i> ' . $val . '</a> / <span>#' . $line_number . '</span>';
					},
					'ignore_export' => 1,
					'value' => $guests['id'],
				));

				// push fields in the rows array as a new row
				array_push($this->rows, $insert_row);

				// increment guest index
				$guest_ind++;

				// increment line number
				$line_number++;
			}
		}

		// do not sort the rows for this report because the lines of the guests of the same booking must be consecutive
		// $this->sortRows($pkrsort, $pkrorder);

		// the footer row will just print the amount of records to export
		array_push($this->footerRow, array(
			array(
				'attr' => array(
					'class="vbo-report-total"',
				),
				'value' => '<h3>' . JText::translate('VBOREPORTSTOTALROW') . '</h3>',
			),
			array(
				'attr' => array(
					'colspan="' . (count($this->cols) - 1) . '"',
				),
				'value' => count($this->rows),
			)
		));

		return true;
	}

	public function formatXML($xml)
	{
		if (!class_exists('DOMDocument')) {
			// we cannot format the XML because DOMDocument is missing
			return $xml;
		}

		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml);
		$dom->formatOutput = true;
		$xml = $dom->saveXML();

		return $xml;
	}

	/**
	 * Generates the authority file, then sends it to output for download.
	 * In case of errors, the process is not terminated (exit) to let the View display the
	 * error message(s). The export type argument can eventually determine an action to run.
	 *
	 * @param 	string 	$export_type 	Differentiates the type of export requested.
	 *
	 * @return 	void|bool 				Void in case of script termination, boolean otherwise.
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP) method's logic refactoring.
	 */
	public function customExport($export_type = null)
	{
		// build the XML file
		$xml = $this->buildXMLFile();

		// build report action data, if needed
		$action_data = array_merge($this->getActionData($registry = false), ['xml' => $xml]);

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
				$this->setError(sprintf('(%s) %s', $e->getCode(), $e->getMessage()));

				// abort
				return false;
			}
		}

		// proceed with the regular export function (write on file through cron or download file through web)

		if (!$xml) {
			// abort
			return false;
		}

		// update history for all bookings affected before exporting
		foreach ($this->export_booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance($bid)->store('RP', $this->reportName);
		}

		/**
		 * Custom export method supports a custom export handler, if previously set.
		 * 
		 * @since 	1.16.1 (J) - 1.6.1 (WP)
		 */
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, $xml);
			fclose($fp);

			// return true as data was written
			return true;
		}

		// force file download in case of regular export
		header("Content-type: text/xml");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="' . $this->getExportCSVFileName() . '"');
		echo $xml;

		exit;
	}

	/**
	 * Builds the XML file for export or transmission.
	 * 
	 * @return 	string 	Empty string in case of errors, XML otherwise.
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP) implemented dedicated method to file generation.
	 */
	protected function buildXMLFile()
	{
		if (!$this->getReportData()) {
			return '';
		}

		// load report settings
		$settings = $this->loadSettings();

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// injected options will replace request variables, if any
		$pfromdate = $options->get('fromdate', VikRequest::getString('fromdate', '', 'request'));
		$ptodate = $options->get('todate', VikRequest::getString('todate', '', 'request'));

		// access manually filled values, if any
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? (array) json_decode($pfiller, true) : [];

		// numero letti
		$total_beds = (int) ($settings['numletti'] ?? 1);

		// count total number of room units
		$total_rooms = (int) ($settings['numcamere'] ?? $this->countRooms());

		// date timestamps
		$from_ts = VikBooking::getDateTimestamp($pfromdate, 0, 0);
		$to_ts = VikBooking::getDateTimestamp($ptodate, 23, 59, 59);

		// suppress warning messages
		libxml_use_internal_errors(true);

		// get the SimpleXMLElement object
		$xml = simplexml_load_string('<movimenti></movimenti>');

		// add property code and PMS name
		$xml->addChild('codice', htmlspecialchars($settings['codstru']));
		$xml->addChild('prodotto', htmlspecialchars('E4jConnect/VikBooking'));

		// scan all given dates
		$date_info = getdate($from_ts);
		while ($date_info[0] <= $to_ts) {
			// get today's date in Y-m-d format
			$today_ymd = date('Y-m-d', $date_info[0]);

			// open movimento node for this day
			$movement = $xml->addChild('movimento');

			// populate movimento date
			$movement->addChild('data', date('Ymd', $date_info[0]));

			// populate movimento property data
			$property = $movement->addChild('struttura');

			// whether the property is open today
			$is_open = (bool) (!VikBooking::validateClosingDates($date_info[0], $date_info[0]));
			$property->addChild('apertura', ($is_open ? 'SI' : 'NO'));

			// number of occupied rooms today, rooms and beds available
			if (!$is_open) {
				// property is closed today, so all values should be set as 0
				$property->addChild('camereoccupate', 0);
				$property->addChild('cameredisponibili', 0);
				$property->addChild('lettidisponibili', 0);

				// go to next day without even checking the bookings or report rows
				$date_info = getdate(mktime(0, 0, 0, $date_info['mon'], $date_info['mday'] + 1, $date_info['year']));

				// do not proceed for today
				continue;
			}

			// check if some listings were filtered
			$filtered_listings = ((array) VikRequest::getVar('listings', array())) ?: ((array) $options->get('listings', []));
			$filtered_listings = array_filter(array_map('intval', $filtered_listings));

			// property is open today
			$property->addChild('camereoccupate', $this->countDayRoomsOccupied($date_info[0], $filtered_listings));
			$property->addChild('cameredisponibili', $total_rooms);
			$property->addChild('lettidisponibili', $total_beds);

			// check for arrivals, departures or reservations dated today
			$today_arrivals = [];
			$today_departures = [];
			$today_reservations = [];

			// scan all report rows to gather the needed record types
			foreach ($this->rows as $ind => $row) {
				// booking ID flags
				$current_bid = 0;
				$current_idswh = '';

				// scan row column values
				foreach ($row as $field) {
					if ($field['key'] == 'idswh') {
						// set booking (software) ID
						$current_idswh = (string) $field['value'];
						continue;
					}

					if ($field['key'] == 'booking_id') {
						// set booking ID
						$current_bid = (int) $field['value'];
						continue;
					}

					// check where this whole booking row should be allocated
					if ($current_bid && $current_idswh) {
						if ($field['key'] == 'resdate' && date('Y-m-d', $field['value']) == $today_ymd && !($today_reservations[$current_bid] ?? null)) {
							// this is a reservation made today (one per booking ID, not one per guest)
							$today_reservations[$current_bid] = $row;
						}

						if ($field['key'] == 'checkin' && date('Y-m-d', $field['value']) == $today_ymd && !($today_arrivals[$current_idswh] ?? null)) {
							// this is a guest arriving today (one row per guest, not per booking)
							$today_arrivals[$current_idswh] = $row;
						}

						if ($field['key'] == 'checkout' && date('Y-m-d', $field['value']) == $today_ymd && !($today_departures[$current_idswh] ?? null)) {
							// this is a guest departing today (one row per guest, not per booking)
							$today_departures[$current_idswh] = $row;
						}
					}
				}

				// push booking ID involved for the export
				if ($current_bid && !in_array($current_bid, $this->export_booking_ids)) {
					$this->export_booking_ids[] = $current_bid;
				}
			}

			// check for arrivals first
			if ($today_arrivals) {
				// open "arrivals" node
				$arrivals = $movement->addChild('arrivi');

				// map of booking guest heads
				$booking_guest_heads = [];

				// scan all arrivals for today
				foreach ($today_arrivals as $today_arrival_row) {
					// open "arrival" node
					$arrival = $arrivals->addChild('arrivo');

					// booking ID flags
					$current_bid = 0;
					$current_idswh = '';

					// guest registration values
					$guest_reg_vals = [];

					// scan row column values
					foreach ($today_arrival_row as $field) {
						// store guest registration value
						$guest_reg_vals[$field['key']] = $this->getExportFieldValue($field);

						if ($field['key'] == 'idswh') {
							// set booking (software) ID
							$current_idswh = (string) $field['value'];
							continue;
						}

						if ($field['key'] == 'booking_id') {
							// set booking ID
							$current_bid = (int) $field['value'];
							continue;
						}

						if ($current_idswh && $current_bid && $field['key'] == 'tipo' && in_array((int) $field['value'], [17, 18])) {
							// this is either a "Capofamiglia" or "Capogruppo", add relation
							$booking_guest_heads[$current_bid] = $current_idswh;
						}
					}

					// add elements
					$arrival->addChild('idswh', htmlspecialchars($guest_reg_vals['idswh']));
					$arrival->addChild('tipoalloggiato', htmlspecialchars($guest_reg_vals['tipo'] ?? ''));
					if (($booking_guest_heads[$current_bid] ?? null) && $booking_guest_heads[$current_bid] != $guest_reg_vals['idswh']) {
						$arrival->addChild('idcapo', htmlspecialchars($booking_guest_heads[$current_bid]));
					}
					$arrival->addChild('cognome', htmlspecialchars($guest_reg_vals['cognome'] ?? ''));
					$arrival->addChild('nome', htmlspecialchars($guest_reg_vals['nome'] ?? ''));
					$arrival->addChild('sesso', htmlspecialchars($guest_reg_vals['gender'] ?? 'M'));
					$arrival->addChild('cittadinanza', htmlspecialchars($guest_reg_vals['citizen'] ?? ''));
					$arrival->addChild('statoresidenza', htmlspecialchars($guest_reg_vals['stares'] ?? ''));
					$arrival->addChild('luogoresidenza', htmlspecialchars($guest_reg_vals['comres'] ?? ''));
					$arrival->addChild('datanascita', htmlspecialchars($guest_reg_vals['dbirth'] ?? ''));
					$arrival->addChild('tipoturismo', htmlspecialchars($guest_reg_vals['turismo'] ?? ''));
					$arrival->addChild('mezzotrasporto', htmlspecialchars($guest_reg_vals['mezzo'] ?? ''));
				}
			}

			// then check for departures
			if ($today_departures) {
				// open "departures" node
				$departures = $movement->addChild('partenze');

				// scan all departures for today
				foreach ($today_departures as $today_departure_row) {
					// open "departure" node
					$departure = $departures->addChild('partenza');

					// guest registration values
					$guest_reg_vals = $this->getAssocRowData($today_departure_row);

					// add elements
					$departure->addChild('idswh', htmlspecialchars($guest_reg_vals['idswh'] ?? ''));
					$departure->addChild('tipoalloggiato', htmlspecialchars($guest_reg_vals['tipo'] ?? ''));
					$departure->addChild('arrivo', htmlspecialchars($guest_reg_vals['checkin'] ?? ''));
				}
			}

			// finally, check for reservations
			if ($today_reservations) {
				// open "reservations" node
				$reservations = $movement->addChild('prenotazioni');

				// scan all reservations for today
				foreach ($today_reservations as $today_reservation_row) {
					// open "reservation" node
					$reservation = $reservations->addChild('prenotazione');

					// guest registration values
					$guest_reg_vals = $this->getAssocRowData($today_reservation_row);

					// add elements
					$reservation->addChild('idswh', htmlspecialchars($guest_reg_vals['idswh'] ?? ''));
					$reservation->addChild('arrivo', htmlspecialchars($guest_reg_vals['checkin'] ?? ''));
					$reservation->addChild('partenza', htmlspecialchars($guest_reg_vals['checkout'] ?? ''));
					$reservation->addChild('ospiti', intval($guest_reg_vals['guestsnum'] ?? 1));
					$reservation->addChild('camere', intval($guest_reg_vals['roomsbooked'] ?? 1));
				}
			}

			// go to next day
			$date_info = getdate(mktime(0, 0, 0, $date_info['mon'], $date_info['mday'] + 1, $date_info['year']));
		}

		// format XML document and return it
		return $this->formatXML($xml->asXML());
	}

	/**
	 * Given a field (row-column) associative list of data, performs
	 * the necessary callback operations to get the value to export.
	 * 
	 * @param 	array 	$field 	The row-column exporting data.
	 * 
	 * @return 	mixed 			The field value to export.
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)
	 */
	protected function getExportFieldValue(array $field)
	{
		$field_val = $field['value'];

		if ($field['callback_export'] ?? null) {
			$field['callback'] = $field['callback_export'];
		}

		if (!($field['no_export_callback'] ?? 0) && is_callable($field['callback'] ?? null)) {
			$field_val = $field['callback']($field_val);
		}

		return $field_val;
	}

	/**
	 * Parses a report row into an associative list of key-value pairs.
	 * 
	 * @param 	array 	$row 	The report row to parse.
	 * 
	 * @return 	array
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)
	 */
	protected function getAssocRowData(array $row)
	{
		$row_data = [];

		foreach ($row as $field) {
			$field_val = $field['value'];

			if ($field['callback_export'] ?? null) {
				$field['callback'] = $field['callback_export'];
			}

			if (!($field['no_export_callback'] ?? 0) && is_callable($field['callback'] ?? null)) {
				$field_val = $field['callback']($field_val);
			}

			$row_data[$field['key']] = $field_val;
		}

		return $row_data;
	}

	/**
	 * Counts the number of occupied rooms on the given day.
	 * 
	 * @param 	int 	$day_ts 	The day timestamp (at midnight).
	 * @param 	array 	$listings 	Optional list of filtered listing IDs.
	 * 
	 * @return 	int 	Number of occupied rooms found.
	 * 
	 * @since 	1.17.2 (J) - 1.7.2 (WP)
	 * @since 	1.18.0 (J) - 1.8.0 (WP) added support for $listings argument.
	 */
	protected function countDayRoomsOccupied($day_ts, array $listings = [])
	{
		// get the timestamp at the last second of the day
		$day_ts = strtotime('23:59:59', $day_ts);

		$dbo = JFactory::getDbo();

		if ($listings) {
			// perform a more accurate query over the listings specified
			$dbo->setQuery(
				$dbo->getQuery(true)
					->select('COUNT(*)')
					->from($dbo->qn('#__vikbooking_orders', 'o'))
					->leftJoin($dbo->qn('#__vikbooking_ordersrooms', 'or') . ' ON ' . $dbo->qn('o.id') . ' = ' . $dbo->qn('or.idorder'))
					->where($dbo->qn('o.status') . ' = ' . $dbo->q('confirmed'))
					->where($dbo->qn('o.closure') . ' = 0')
					->where($dbo->qn('o.checkin') . ' < ' . (int) $day_ts)
					->where($dbo->qn('o.checkout') . ' > ' . (int) $day_ts)
					->where($dbo->qn('or.idroom') . ' IN (' . implode(', ', array_map('intval', $listings)) . ')')
			);
		} else {
			// quick query to fetch the total number of occupied rooms
			$dbo->setQuery(
				$dbo->getQuery(true)
					->select('SUM(' . $dbo->qn('roomsnum') . ')')
					->from($dbo->qn('#__vikbooking_orders'))
					->where($dbo->qn('status') . ' = ' . $dbo->q('confirmed'))
					->where($dbo->qn('closure') . ' = 0')
					->where($dbo->qn('checkin') . ' < ' . (int) $day_ts)
					->where($dbo->qn('checkout') . ' > ' . (int) $day_ts)
			);
		}

		return (int) $dbo->loadResult();
	}

	/**
	 * Custom scoped action to transmit the XML ISTAT file to the local authority.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function transmitRecords($scope = null, array $data = [])
	{
		if (!($data['xml'] ?? '') && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadSchedaIstat(\'transmitRecords\');</script>',
			];
		}

		if (!($data['xml'] ?? '')) {
			// attempt to build the XML file if not set
			$data['xml'] = $this->buildXMLFile();
		}

		if (!$data['xml']) {
			throw new Exception('Missing XML request message.', 500);
		}

		// load report settings
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['codstru']) || empty($settings['user']) || empty($settings['pwd']) || empty($settings['endpoint'])) {
			throw new Exception(sprintf('[%s] error: impostazioni report mancanti.', __METHOD__), 500);
		}

		// build the SOAP XML string for the request body
		$soap_xml = '';
		$soap_xml .= '<?xml version="1.0"?>
<S:Envelope xmlns:S="http://schemas.xmlsoap.org/soap/envelope/">
<S:Body>
<ns2:inviaMovimentazione xmlns:ns2="http://checkin.ws.service.turismo5.gies.it/">' . "\n";

		// adjust regular XML to SOAP format
		$soap_body_xml = $data['xml'];
		$soap_body_xml = preg_replace("/^<\?xml version=\"([0-9\.]+)\"\s?\?>(\r\n|\r|\n)?/", '', $soap_body_xml);
		$soap_body_xml = str_replace(['<movimenti>', '</movimenti>'], ['<movimentazione>', '</movimentazione>'], $soap_body_xml);

		// append SOAP XML body
		$soap_xml .= trim($soap_body_xml);

		// close SOAP XML string
		$soap_xml .= "\n" . '</ns2:inviaMovimentazione>
</S:Body>
</S:Envelope>';

		// start the HTTP transporter
		$http = new JHttp();

		/**
		 * POST requests should be made to the endpoint meant as "location", not to the endpoint
		 * representing the Web-Service (WSDL). In this case we should strip out the ending "?wsdl".
		 */
		$settings['endpoint'] = preg_replace("/\?wsdl$/", '', $settings['endpoint']);

		try {
			// make a POST request to the specified endpoint URL
			$response = $http->post($settings['endpoint'], $soap_xml, [
				'Accept'         => 'text/xml, multipart/related',
				'Authorization'  => 'Basic ' . base64_encode(sprintf('%s:%s', $settings['user'], $settings['pwd'])),
				'Content-Type'   => 'text/xml; charset=utf-8',
				'SOAPAction'     => '',
				// 'User-Agent'     => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.7; rv:133.0) Gecko/20100101 Firefox/133.0',
				'Connection'     => 'keep-alive',
				'Content-Length' => strlen($soap_xml),
			], 20);

			// response text
			$response_txt = strip_tags((string) $response->body);

			if ($response->code != 200) {
				// invalid response, throw an exception
				throw new Exception($response_txt, $response->code);
			}

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Flusso dati turistici inviati con successo.</p>';
			$html .= 'Responso:<br/><pre><code>' . htmlentities($response->body) . '</code></pre>';

		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// get the currently active profile ID, if any
		$activeProfileId = $this->getActiveProfile();

		/**
		 * When the report is executed through a cron-job, the transmission of the guest details
		 * will also dump the data transmitted onto a resource file, useful for sending it via email.
		 * 
		 * @since 	1.18.0 (J) - 1.8.0 (WP)
		 */
		if ($scope === 'cron') {
			// prepare the XML resource file information
			$xml_name = implode('_', array_filter([$this->getFileName(), 'trasmissione', 'flussi', time(), $activeProfileId, rand(100, 999)])) . '.xml';
			$xml_dest = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $xml_name;
			$xml_url  = $this->getDataMediaUrl() . $xml_name;

			// store the XML bytes into a local file on disk
			$stored = JFile::write($xml_dest, $soap_xml);

			if ($stored && VBOPlatformDetection::isWordPress()) {
				/**
				 * Trigger files mirroring operation
				 */
				VikBookingLoader::import('update.manager');
				VikBookingUpdateManager::triggerUploadBackup($xml_dest);
			}

			if ($stored) {
				// define a new report resource for the generated file
				$this->defineResourceFile([
					'summary' => sprintf(
						'%sFlussi turistici generati e trasmessi con successo.',
						($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : '')
					),
					'url'  => $xml_url,
					'path' => $xml_dest,
				]);
			}
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.sendxml.ok',
				'title'   => 'Ross1000 - Trasmissione flussi turistici',
				'summary' => sprintf(
					'%sI flussi turistici sono stati trasmessi con successo per le date dal %s al %s.',
					($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
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
			'html'     => $html,
			'success'  => true,
			'response' => $response->body,
		];
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
			// count guests per room (adults + children)
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
	 * Registers the name to give to the file being exported.
	 * 
	 * @return 	void
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	protected function registerExportFileName()
	{
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$this->setExportCSVFileName(str_replace(' ', '_', $this->reportName) . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.xml');
	}

	/**
	 * Parses the file Comuni.csv and returns two associative
	 * arrays: one for the Comuni and one for the Province.
	 * Every line of the CSV is composed of: Codice, Comune, Provincia.
	 *
	 * @return 	array
	 */
	protected function loadComuniProvince()
	{
		$vals = [
			'comuni' => [
				'-- Estero --',
			],
			'province' => [
				'-- Estero --',
			]
		];

		$csv = dirname(__FILE__).DIRECTORY_SEPARATOR.'Comuni.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 3) {
				continue;
			}

			// trim values
			$v[0] = trim($v[0]);
			$v[1] = trim($v[1]);
			$v[2] = trim($v[2]);

			$vals['comuni'][$v[0]]['name'] = $v[1];
			$vals['comuni'][$v[0]]['province'] = $v[2];
			$vals['province'][$v[2]] = $v[2];
		}

		return $vals;
	}

	/**
	 * Parses the file Nazioni.csv and returns an associative
	 * array with the code and name of the Nazione.
	 * Every line of the CSV is composed of: Codice, Nazione.
	 *
	 * @return 	array
	 */
	protected function loadNazioni()
	{
		$nazioni = [];

		$csv = dirname(__FILE__).DIRECTORY_SEPARATOR.'Nazioni.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}
			$v = explode(';', $row);
			if (count($v) != 3) {
				continue;
			}

			// trim values
			$v[0] = trim($v[0]);
			$v[1] = trim($v[1]);
			$v[2] = trim($v[2]);
			
			$nazioni[$v[0]]['name'] = $v[1];
			$nazioni[$v[0]]['three_code'] = $v[2];

		}

		return $nazioni;
	}

	/**
	 * Returns the key of the state selected by the user, if any.
	 * 
	 * @param 	string 	$country 	The selected country code.
	 *
 	 * @return 	string
	 */
	protected function checkCountry($country)
	{
		$found = false;
		$staval = '';

		if (!$this->nazioni) {
			$this->nazioni = $this->loadNazioni();
		}

		if ($country && isset($this->nazioni[$country])) {
			return $country;
		}

		foreach ($this->nazioni as $key => $value) {
			if (trim($value['three_code']) == trim($country)) {
				$staval = $key;
				$found = true;
				break;
			}
		}

		if ($found !== true) {
			$staval = '';
		}

		return $staval;
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
		if (!is_array($pax_data) || !count($pax_data) || empty($key)) {
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
}
