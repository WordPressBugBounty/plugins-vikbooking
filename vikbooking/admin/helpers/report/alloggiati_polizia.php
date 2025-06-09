<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2018 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * AlloggiatiPolizia child Class of VikBookingReport.
 * The Class was designed to export the customers details for the Italian Police.
 * 
 * @link https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/TechSupp.aspx
 * @link https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/CREAFILE.pdf (da pagina 4)
 * @link https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/MANUALEALBERGHI.pdf
 * @link https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/TABELLE.zip
 */
class VikBookingReportAlloggiatiPolizia extends VikBookingReport
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
	 * Debug mode is activated by passing the value 'e4j_debug' > 0
	 * 
	 * @var  bool
	 */
	protected $debug;

	/**
	 * List of "comuni" and "province" codes.
	 * 
	 * @var  array
	 */
	protected $comuniProvince = [];

	/**
	 * List of "nazioni" codes.
	 * 
	 * @var  array
	 */
	protected $nazioni = [];

	/**
	 * List of "documenti" codes.
	 * 
	 * @var  array
	 */
	protected $documenti = [];

	/**
	 * List of booking IDs affected by the export.
	 * 
	 * @var  	array
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected $export_booking_ids = [];

	/**
	 * List of exported check-in dates (range).
	 * 
	 * @var  	array
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected $exported_checkin_dates = [];

	/**
	 * The URL to the WSDL for the SOAP operations.
	 * 
	 * @var  	string
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected $wsdl_url = 'https://alloggiatiweb.poliziadistato.it/service/service.asmx?wsdl';

	/**
	 * The location URL to the WS for the SOAP requests.
	 * 
	 * @var  	string
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected $ws_location = 'https://alloggiatiweb.poliziadistato.it/service/Service.asmx';

	/**
	 * String representation of the PHP constant for the SOAP
	 * protocol version (either SOAP 1.1 or SOAP 1.2).
	 * 
	 * @var  	string
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected $soap_version = 'SOAP_1_2';

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = JText::translate('VBOREPORT'.strtoupper(str_replace('_', '', $this->reportFile)));
		$this->reportFilters = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		$this->comuniProvince = $this->loadComuniProvince();
		$this->nazioni = $this->loadNazioni();
		$this->documenti = $this->loadDocumenti();

		$this->debug = (VikRequest::getInt('e4j_debug', 0, 'request') > 0);

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
	 * @since 	1.17.7 (J) - 1.7.7 (WP)
	 */
	public function allowsProfileSettings()
	{
		// allow multiple report profile settings
		return true;
	}

	/**
	 * @inheritDoc
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	public function getSettingFields()
	{
		return [
			'title' => [
				'type'  => 'custom',
				'label' => '',
				'html'  => '<p class="info">Configura le impostazioni per la trasmissione delle schedine verso il sistema della Polizia di Stato - Alloggiati Web.</p>',
			],
			'apartments' => [
				'type'    => 'checkbox',
				'label'   => 'Gestione Appartamenti',
				'help'    => 'Se abilitato, la trasmissione delle schede alloggiati verrà eseguita secondo il criterio per le utenze della categoria &quot;Gestione Appartamenti&quot;. In alternativa, verrà utilizzato il criterio Hotel.',
				'default' => 0,
			],
			'user' => [
				'type'  => 'text',
				'label' => 'Utente',
				'help'  => 'Nome utente assegnato all\'account della struttura nel portale alloggiati web.',
			],
			'pwd' => [
				'type'  => 'password',
				'label' => 'Password',
				'help'  => 'Password assegnata all\'account della struttura nel portale alloggiati web.',
			],
			'wskey' => [
				'type'  => 'password',
				'label' => 'WsKey',
				'help'  => 'Chiave di sicurezza generata ed assegnata all\'account della struttura nel portale alloggiati web.',
			],
		];
	}

	/**
	 * @inheritDoc
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	public function getScopedActions($scope = null, $visible = true)
	{
		// list of custom actions for this report
		$actions = [
			[
				'id' => 'transmitCards',
				'name' => 'Trasmissione elenco schedine',
				'help' => 'Consente di effettuare il controllo di correttezza e il contestuale invio di un elenco di schedine. Le sole schedine corrette saranno acquisite dal sistema.',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
				],
			],
			[
				'id' => 'testTransmitCards',
				'name' => 'Controllo preliminare schedine',
				'help' => 'Questo metodo consente di effettuare il solo controllo di correttezza di un elenco di schedine; può essere utilizzato per testare il meccanismo di generazione delle stringhe secondo il tracciato record previsto.',
				'icon' => VikBookingIcons::i('spell-check'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
				],
			],
			[
				'id' => 'listApartments',
				'name' => 'Lista appartamenti',
				'help' => 'Ottiene i dati della tabella per la lista degli appartamenti. Da utilizzare soltanto per la categoria "Gestione Appartamenti".',
				'icon' => VikBookingIcons::i('building'),
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'receiptsArchive',
				'name' => 'Archivio ricevute',
				'help' => 'Archivio delle ricevute precedentemente scaricate per gli invii delle schedine alloggiati.',
				'icon' => VikBookingIcons::i('folder-open'),
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'downloadReceipt',
				'name' => 'Download ricevuta',
				'help' => 'Consente di effettuare il download della ricevuta relativa agli invii effettuati in una specifica data.',
				'icon' => VikBookingIcons::i('cloud-download-alt'),
				'scopes' => [
					'web',
					'cron',
				],
				'params' => [
					'receipt_date' => [
						'type'    => 'calendar',
						'label'   => 'Data ricevuta',
						'help'    => 'Seleziona la data per cui scaricare la ricevuta di una trasmissione.',
						'default' => date($this->getDateFormat(), strtotime('yesterday')),
					],
				],
				'params_submit_lbl' => 'Scarica ricevuta',
			],
			[
				'id' => 'receiptExists',
				'name' => 'Verifica duplicati ricevuta',
				'help' => 'Metodo interno per controllare se una ricevuta esiste per una certa data.',
				// flag to indicate that it's callable internally, but not graphically
				'hidden' => true,
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'generateToken',
				'name' => 'Genera token autenticazione',
				'help' => 'Questo metodo consente, date le informazioni di sicurezza di autenticazione legate all\'utente, di ottenere un token temporaneo valido da utilizzare per usufruire delle funzionalità dei servizi.',
				// flag to indicate that it's callable internally, but not graphically
				'hidden' => true,
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'authenticationTest',
				'name' => 'Controllo token autenticazione',
				'help' => 'Consente di effettuare il controllo di correttezza e validità delle informazioni di autenticazione per l\'utilizzo dei servizi. Utilizzabile per controllare la corretta generazione del token.',
				'hidden' => true,
				'scopes' => [
					'web',
				],
			],
			[
				'id' => 'saveApartmentRelation',
				'name' => 'Salva relazione appartamento',
				'help' => 'Salva la relazione tra un ID appartamento letto dal sistema Alloggiati Web ed un ID camera di VikBooking.',
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
	 * @return 	array
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

		// load the jQuery UI Datepicker
		$this->loadDatePicker();

		// custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadSchedaPolizia();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download File</span></a>';

		// load report settings
		$settings = $this->loadSettings();

		// build the hidden values for the selection of Comuni & Province and much more.
		$hidden_vals = '<div id="vbo-report-alloggiati-hidden" style="display: none;">';

		// build params container HTML structure
		$hidden_vals .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$hidden_vals .= '	<div class="vbo-params-wrap">';
		$hidden_vals .= '		<div class="vbo-params-container">';
		$hidden_vals .= '			<div class="vbo-params-block vbo-params-block-noborder">';

		// Comuni
		$hidden_vals .= '	<div id="vbo-report-alloggiati-comune" class="vbo-report-alloggiati-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Comune</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-comune" onchange="vboReportChosenComune(this);"><option value=""></option>';
		if (isset($this->comuniProvince['comuni']) && count($this->comuniProvince['comuni'])) {
			foreach ($this->comuniProvince['comuni'] as $code => $comune) {
				if (!is_array($comune)) {
					continue;
				}
				$hidden_vals .= '		<option value="'.$code.'">'.$comune['name'].'</option>'."\n";
			}
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Province
		$hidden_vals .= '	<div id="vbo-report-alloggiati-provincia" class="vbo-report-alloggiati-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Provincia</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-provincia" onchange="vboReportChosenProvincia(this);"><option value=""></option>';
		if (isset($this->comuniProvince['province']) && count($this->comuniProvince['province'])) {
			foreach ($this->comuniProvince['province'] as $code => $provincia) {
				// sanitize code from line breaks
				$code = str_replace("\r\n", '', $code);
				$code = str_replace("\r", '', $code);
				$code = str_replace("\n", '', $code);
				//
				$hidden_vals .= '		<option value="'.$code.'">'.$provincia.'</option>'."\n";
			}
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Nazioni
		$hidden_vals .= '	<div id="vbo-report-alloggiati-nazione" class="vbo-report-alloggiati-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Nazione</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-nazione" onchange="vboReportChosenNazione(this);"><option value=""></option>';
		if (count($this->nazioni)) {
			foreach ($this->nazioni as $code => $nazione) {
				$hidden_vals .= '		<option value="'.$code.'">'.$nazione['name'].'</option>'."\n";
			}
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Documenti
		$hidden_vals .= '	<div id="vbo-report-alloggiati-doctype" class="vbo-report-alloggiati-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Documento</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-documento" onchange="vboReportChosenDocumento(this);"><option value=""></option>';
		if (count($this->documenti)) {
			foreach ($this->documenti as $code => $documento) {
				$hidden_vals .= '		<option value="'.$code.'">'.$documento.'</option>'."\n";
			}
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Sesso
		$hidden_vals .= '	<div id="vbo-report-alloggiati-sesso" class="vbo-report-alloggiati-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Sesso</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-sesso" onchange="vboReportChosenSesso(this);"><option value=""></option>';
		$sessos = array(
			1 => 'M',
			2 => 'F'
		);
		foreach ($sessos as $code => $ses) {
			$hidden_vals .= '		<option value="'.$code.'">'.$ses.'</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Numero Documento
		$hidden_vals .= '	<div id="vbo-report-alloggiati-docnum" class="vbo-report-alloggiati-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Numero Documento</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-docnum" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Data di Nascita
		$hidden_vals .= '	<div id="vbo-report-alloggiati-dbirth" class="vbo-report-alloggiati-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Data di nascita</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-dbirth" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// ID Appartamento
		$apartments_data = !empty($settings['apartments_list']) ? $settings['apartments_list'] : [];
		$hidden_vals .= '	<div id="vbo-report-alloggiati-idapp" class="vbo-report-alloggiati-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">ID Appartamento</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-idapp" onchange="vboReportChosenIdapp(this);"><option value=""></option>';
		foreach ($apartments_data as $apartment_data) {
			$id_apt = null;
			$apt_name = null;
			foreach ($apartment_data as $apt_key => $apt_data) {
				if (!$id_apt) {
					// should be the first key
					$id_apt = $apt_data;
					continue;
				}
				if (!$apt_name) {
					// should be the second key
					$apt_name = $apt_data;
					continue;
				}
				if ($id_apt && $apt_name) {
					// no more looping
					break;
				}
			}
			$hidden_vals .= '		<option value="' . $id_apt . '">' . $apt_name . '</option>' . "\n";
		}
		$hidden_vals .= '			</select>';
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
			'name' => 'fromdate',
		);
		array_push($this->reportFilters, $filter_opt);

		// To Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">'.JText::translate('VBOREPORTSDATETO').'</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
			'type' => 'calendar',
			'name' => 'todate',
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
			'label' => '<label class="vbo-report-alloggiati-manualsave" style="display: none;">Dati inseriti</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-alloggiati-manualsave" style="display: none;" onclick="vboAlloggiatiSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		//jQuery code for the datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$js = 'var reportActiveCell = null, reportObj = {};
		var vbo_alloggiati_ajax_uri = "' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile) . '";
		var vbo_alloggiati_save_icn = "' . VikBookingIcons::i('save') . '";
		var vbo_alloggiati_saving_icn = "' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '";
		var vbo_alloggiati_saved_icn = "' . VikBookingIcons::i('check-circle') . '";
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
			jQuery("#vbo-report-alloggiati-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-comune").select2({placeholder: "- Seleziona un Comune -", width: "200px"});
			jQuery("#choose-provincia").select2({placeholder: "- Seleziona una Provincia -", width: "200px"});
			jQuery("#choose-nazione").select2({placeholder: "- Seleziona una Nazione -", width: "200px"});
			jQuery("#choose-documento").select2({placeholder: "- Seleziona un Documento -", width: "200px"});
			jQuery("#choose-sesso").select2({placeholder: "- Seleziona Sesso -", width: "200px"});
			if (jQuery("#choose-idapp").length) {
				jQuery("#choose-idapp").select2({placeholder: "- Seleziona Appartamento -", width: "200px"});
			}
			jQuery("#choose-dbirth").datepicker({
				maxDate: 0,
				dateFormat: "dd/mm/yy",
				changeMonth: true,
				changeYear: true,
				yearRange: "'.(date('Y') - 100).':'.date('Y').'"
			});
			//click events
			jQuery(".vbo-report-load-comune, .vbo-report-load-comune-stay").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-comune").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-provincia, .vbo-report-load-provincia-stay").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-provincia").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-nazione, .vbo-report-load-nazione-stay, .vbo-report-load-cittadinanza").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-nazione").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-doctype").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-doctype").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-docplace").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-comune").show();
				jQuery("#vbo-report-alloggiati-nazione").show();
				vboShowOverlay({
					title: "Compila informazioni - Luogo rilascio documento",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-sesso").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-idapp").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-idapp").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-docnum").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-docnum").show();
				vboShowOverlay({
					title: "Compila informazioni",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenDocnum(document.getElementById(\'choose-docnum\').value);\">Applica</button>",
				});
				setTimeout(function(){jQuery("#choose-docnum").focus();}, 500);
			});
			jQuery(".vbo-report-load-dbirth").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-alloggiati-selcont").hide();
				jQuery("#vbo-report-alloggiati-dbirth").show();
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
					} else if (jQuery(reportActiveCell).hasClass("vbo-report-load-comune-stay")) {
						reportObj[nowindex]["comune_s"] = c_code;
					} else {
						reportObj[nowindex]["comune_b"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-comune").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
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
					if (jQuery(reportActiveCell).hasClass("vbo-report-load-provincia-stay")) {
						reportObj[nowindex]["province_s"] = c_code;
					} else {
						reportObj[nowindex]["province_b"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-provincia").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
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
						reportObj[nowindex]["country_b"] = c_code;
					} else if (jQuery(reportActiveCell).hasClass("vbo-report-load-nazione-stay")) {
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
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenDocumento(doctype) {
			var c_code = doctype.value;
			var c_val = doctype.options[doctype.selectedIndex].text;
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
					reportObj[nowindex]["doctype"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-documento").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
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
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenIdapp(idapp) {
			var c_code = idapp.value;
			var c_val = idapp.options[idapp.selectedIndex].text;
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
					reportObj[nowindex]["idapp"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-idapp").val("").select2("data", null, false);
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		function vboReportChosenDocnum(val) {
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
					reportObj[nowindex]["docnum"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-docnum").val("");
			jQuery(".vbo-report-alloggiati-manualsave").show();
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
			jQuery(".vbo-report-alloggiati-manualsave").show();
		}
		//download function
		function vboDownloadSchedaPolizia(type) {
			if (!confirm("Sei sicuro di aver compilato tutti i dati delle schedine alloggiati?")) {
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
		// update apartment-room relation
		function vboAlloggiatiSetAptRel(id_apt, id_room, apt_name) {
			VBOCore.doAjax(
				"' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=report.executeCustomAction') . '",
				{
					report_file:   "' . $this->getFileName() . '",
					report_action: "saveApartmentRelation",
					report_scope:  "web",
					report_data: {
						id_apt: id_apt,
						id_room: id_room,
						apt_name: apt_name,
					},
				},
				(resp) => {
					// do nothing on success
				},
				(error) => {
					// display the error
					alert(error.responseText);
				}
			);
		}
		// save data function
		function vboAlloggiatiSaveData() {
			jQuery("button.vbo-report-alloggiati-manualsave").find("i").attr("class", vbo_alloggiati_saving_icn);
			VBOCore.doAjax(
				vbo_alloggiati_ajax_uri,
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
					jQuery("button.vbo-report-alloggiati-manualsave").addClass("btn-success").find("i").attr("class", vbo_alloggiati_saved_icn);
				},
				function(error) {
					alert(error.responseText);
					jQuery("button.vbo-report-alloggiati-manualsave").removeClass("btn-success").find("i").attr("class", vbo_alloggiati_save_icn);
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

		// load all countries
		$all_countries = VikBooking::getCountriesArray();

		// input fields and other vars
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');
		$pkrsort = VikRequest::getString('krsort', $this->defaultKeySort, 'request');
		$pkrsort = empty($pkrsort) ? $this->defaultKeySort : $pkrsort;
		$pkrorder = VikRequest::getString('krorder', $this->defaultKeyOrder, 'request');
		$pkrorder = empty($pkrorder) ? $this->defaultKeyOrder : $pkrorder;
		$pkrorder = $pkrorder == 'DESC' ? 'DESC' : 'ASC';
		$plistings = (array) VikRequest::getVar('listings', array());
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
		if (empty($pfromdate) || empty($from_ts) || empty($to_ts)) {
			$this->setError(JText::translate('VBOREPORTSERRNODATES'));
			return false;
		}

		// set the dates being exported
		$this->exported_checkin_dates = [
			date('Y-m-d', $from_ts),
			date('Y-m-d', $to_ts),
		];

		// build query to obtain the records (all check-ins within the dates filter)
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
			->where($this->dbo->qn('o.checkin') . ' >= ' . $from_ts)
			->where($this->dbo->qn('o.checkin') . ' <= ' . $to_ts)
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

		// define the columns of the report
		$this->cols = array(
			// tipo
			array(
				'key' => 'tipo',
				'attr' => array(
					'class="vbo-report-longlbl"'
				),
				'label' => 'Tipo Alloggiato'
			),
			// check-in
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPICKUPAT')
			),
			// nights
			array(
				'key' => 'nights',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBDAYS')
			),
			// cognome
			array(
				'key' => 'last_name',
				'label' => JText::translate('VBTRAVELERLNAME')
			),
			// nome
			array(
				'key' => 'first_name',
				'label' => JText::translate('VBTRAVELERNAME')
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
				'key' => 'date_birth',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERBDATE')
			),
			// comune di nascita
			array(
				'key' => 'comune_b',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Comune Nascita'
			),
			// provincia di nascita
			array(
				'key' => 'province_b',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Provincia Nascita'
			),
			// stato di nascita
			array(
				'key' => 'country_b',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Stato Nascita'
			),
			// cittadinanza
			array(
				'key' => 'country_c',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERNATION')
			),
			/**
			 * This is not needed!
			 * 
			 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
			 */
			/*
			// comune di residenza
			array(
				'key' => 'comune_s',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Comune Residenza'
			),
			// provincia di residenza
			array(
				'key' => 'province_s',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Provincia Residenza'
			),
			// stato di residenza
			array(
				'key' => 'country_s',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Stato Residenza'
			),
			// indirizzo
			array(
				'key' => 'address',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('ORDER_ADDRESS'),
				'tip' => 'Campo facoltativo che può essere lasciato vuoto. Viene letto dalle informazioni del cliente associato alla prenotazione.',
			),
			*/
			// tipo documento
			array(
				'key' => 'doctype',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCTYPE')
			),
			// numero documento
			array(
				'key' => 'docnum',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCNUM')
			),
			// luogo rilascio documento
			array(
				'key' => 'docplace',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Luogo Rilascio'
			),
			// id booking
			array(
				'key' => 'idbooking',
				'attr' => array(
					'class="center"'
				),
				'label' => 'ID / #'
			),
		);

		/**
		 * Thanks to the WSDL functionalities, we now also support the "Gestione Appartamenti" category.
		 * Those PMs who belong to this category, rather than hotels, must use a column "ID Appartamento".
		 * 
		 * @since 	1.17.1 (J) - 1.7.1 (WP)
		 */
		$settings = $this->loadSettings();
		if (!empty($settings['apartments']) && !empty($settings['apartments_list'])) {
			// get and unset last column
			$last_col = $this->cols[count($this->cols) - 1];
			unset($this->cols[count($this->cols) - 1]);

			// push the "ID Appartamento" column
			$this->cols[] = [
				'key' => 'idapp',
				'attr' => array(
					'class="center"'
				),
				'label' => 'ID App',
				'tip' => 'Necessario per la categoria Gestione Appartamenti. Assicurati di scaricare la lista appartamenti dalle apposite funzioni per salvare gli ID.',
			];

			// restore the last column by repushing it
			$this->cols[] = $last_col;
		}

		// line number (to facilitate identifying a specific guest in case of errors with the file submission)
		$line_number = 0;

		// loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		foreach ($bookings as $gbook) {
			// count the total number of guests for all rooms of this booking
			$tot_booking_guests = 0;
			$room_guests = [];
			foreach ($gbook as $rbook) {
				$tot_booking_guests += ($rbook['adults'] + $rbook['children']);
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

			/**
			 * Codici Tipo Alloggiato
			 * 
			 * 16 = Ospite Singolo
			 * 17 = Capofamiglia
			 * 18 = Capogruppo
			 * 19 = Familiare
			 * 20 = Membro Gruppo
			 */
			$tipo = 16;
			$tipo = count($guests_rows) > 1 ? 17 : $tipo;

			// create one row for each guest
			$guest_ind = 1;
			foreach ($guests_rows as $ind => $guests) {
				// prepare row record for this room-guest
				$insert_row = [];

				// find the actual guest-room-index
				$guest_room_ind = $this->calcGuestRoomIndex($room_guests, $guest_ind);

				// determine the type of guest, either automatically or from the check-in pax data
				$use_tipo = $ind > 0 && $tipo == 17 ? 19 : $tipo;
				$pax_guest_type = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'guest_type');
				$use_tipo = !empty($pax_guest_type) ? $pax_guest_type : $use_tipo;

				/**
				 * Il calcolo dello stato di nascita è fatto qua per evitare che poi succeda casino col comune. (Belgio -> Belgioioso, vedi cliente)
				 * In questo modo, il calcolo dello stato di nascita permette di escludere poi la provincia di nascita in caso il cliente sia straniero.
				 * 
				 * @since 	1.4.1
				 * 
				 * @see 	the updated integration of this report relies on drivers, and so such controls are no longer
				 * 			needed, if the pax data is collected through the back-end before launching the report.
				 */
				$stabirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country');
				$stabirth = $stabirth || '';
				$staval = $this->checkCountry($stabirth);

				// Tipo Alloggiato
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

				// Data Arrivo
				array_push($insert_row, array(
					'key' => 'checkin',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('d/m/Y', $val);
					},
					'value' => $guests['checkin']
				));

				// Notti di Permanenza
				array_push($insert_row, array(
					'key' => 'nights',
					'attr' => array(
						'class="center"'
					),
					'value' => $guests['days']
				));

				// Cognome
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				$pax_cognome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'last_name');
				$cognome = !empty($pax_cognome) ? $pax_cognome : $cognome;
				array_push($insert_row, array(
					'key' => 'last_name',
					'callback_export' => function($val) {
						$val = str_replace(['-', '.', ',', '?', '!'], ' ', (string) $val);
						return $this->transliterateToAscii(trim($val));
					},
					'value' => $cognome,
				));

				// Nome
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				$pax_nome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'first_name');
				$nome = !empty($pax_nome) ? $pax_nome : $nome;
				array_push($insert_row, array(
					'key' => 'first_name',
					'callback_export' => function($val) {
						$val = str_replace(['-', '.', ',', '?', '!'], ' ', (string) $val);
						return $this->transliterateToAscii(trim($val));
					},
					'value' => $nome,
				));

				// Sesso
				$gender = !empty($guests['gender']) && $guest_ind < 2 ? strtoupper($guests['gender']) : '';
				$pax_gender = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'gender');
				$gender = !empty($pax_gender) ? $pax_gender : $gender;
				/**
				 * We make sure the gender will be compatible with both back-end and front-end
				 * check-in/registration data collection driver and processes.
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
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
						return $val == 2 ? 'F' : ($val === 1 ? 'M' : '?');
					},
					'no_export_callback' => 1,
					'value' => $gender
				));

				// Data di nascita
				$dbirth = !empty($guests['bdate']) && $guest_ind < 2 ? VikBooking::getDateTimestamp($guests['bdate'], 0, 0) : '';
				$pax_dbirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'date_birth');
				$dbirth = !empty($pax_dbirth) ? $pax_dbirth : $dbirth;
				$dbirth = (strpos($dbirth, '/') === false && strpos($dbirth, VikBooking::getDateSeparator()) === false) ? $dbirth : VikBooking::getDateTimestamp($dbirth, 0, 0);
				array_push($insert_row, array(
					'key' => 'date_birth',
					'attr' => array(
						'class="center'.(empty($dbirth) ? ' vbo-report-load-dbirth' : '').'"'
					),
					'callback' => function($val) {
						if (!empty($val) && strpos($val, '/') === false && strpos($val, VikBooking::getDateSeparator()) === false) {
							return is_numeric($val) ? date('d/m/Y', $val) : $val;
						}
						if (!empty($val) && strpos($val, '/') !== false) {
							return $val;
						}
						return '?';
					},
					'value' => $dbirth
				));

				/**
				 * Comune & provincia di nascita.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_comval = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'comune_b');
				$pax_province = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_b');

				//if the country has been set and it's not "Italy", then don't let the user select Comune and Provincia.
				$is_foreign = (empty($staval) || !isset($this->nazioni[$staval]) || strcasecmp($this->nazioni[$staval]['name'], 'ITALIA'));

				// default value for "comune di nascita"
				$combirth = !empty($guests['pbirth']) && $guest_ind < 2 && !$is_foreign ? strtoupper($guests['pbirth']) : '';

				// assign the default value found from pax_data registration
				$comval = $pax_comval;

				$result = [];
				if (empty($pax_comval)) {
					$result = $this->sanitizeComune($combirth);
					if (!empty($combirth) && $guest_ind < 2 && (!$is_foreign && !empty($staval)) ) {
						//if $combirth has been sanitized, then you should just check if the province is the right one
						if (isset($result['combirth'])) {
							$result = $this->checkComune($result['combirth'], true, $result['province']);
						} else {
							$result = $this->checkComune($combirth, false, '');
						}	
						$combirth = $result['combirth'];
					}

					if (!$is_foreign && !empty($staval)) {
						$pax_combirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'place_birth');
						$combirth = !empty($pax_combirth) ? strtoupper($pax_combirth) : $combirth;
						$result = $this->sanitizeComune($combirth);
						//If $combirth have been sanitized, then you should just check if the province is the right one
						if (isset($result['combirth'])) {
							$result = $this->checkComune($result['combirth'], true, $result['province']);
						} else {
							$result = $this->checkComune($combirth, false, '');
						}
						$comval = isset($result['comval']) ? $result['comval'] : $combirth;
					}
				}

				// se il comune di nascita è vuoto o è stato indovinato, allora aggiungi la classe. Altrimenti non rendere selezionabile il comune.
				$comune = empty($combirth);
				$comguessed = (isset($result['similar']) && $result['similar']);
				$addclass = false;
				if (empty($pax_comval) && ($comune || $comguessed)) {
					if (empty($staval)) {
						//se lo stato manca, comunque rendilo selezionabile, sia che sia vuoto, sia che sia stato "indovinato"
						$addclass = true;
					} elseif (!$is_foreign && !empty($staval)) {
						//se lo stato esiste, ed è italiano, allora è selezionabile. Se è straniero, non farlo scegliere in quanto non necessario.
						$addclass = true;
					}
				}
				array_push($insert_row, array(
					'key' => 'comune_b',
					'attr' => array(
						'class="center' . ($addclass ? ' vbo-report-load-comune' : '') . ($pax_province === 'ES' ? ' vbo-report-load-elem-filled' : '') . '"'
					),
					'callback' => function($val) use ($addclass) {
						return !empty($val) && isset($this->comuniProvince['comuni'][$val]) ? $this->comuniProvince['comuni'][$val]['name'] : ($addclass ? '?' : "---");
					},
					'no_export_callback' => 1,
					'value' => $comval
				));

				/**
				 * Provincia di nascita.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$comune = empty($combirth);
				$comguessed = (isset($result['similar']) && $result['similar']);
				$province = (isset($result['provinceok']) && !$result['provinceok']);
				$addclass = false; 
				if (empty($pax_province) && ($comune || $comguessed || $province)) {
					if (empty($staval)) {
						$addclass = true;
					} elseif (!$is_foreign && !empty($staval)) {
						$addclass = true;	
					}
				}

				// check if we have an exact value
				$use_province = $pax_province;
				$use_province = empty($use_province) && !empty($result['province']) ? $result['province'] : $use_province;
				$use_province = empty($use_province) ? ($addclass ? '?' : "---") : $use_province;

				array_push($insert_row, array(
					'key' => 'province_b',
					'attr' => array(
						'class="center'.($addclass ? ' vbo-report-load-provincia' : '').'"'
					),
					'value' => $use_province
				));

				/**
				 * Stato di nascita.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_country = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_b');

				$staval   = !empty($pax_country) ? $pax_country : $staval;
				$stabirth = !empty($pax_country) ? $pax_country : $stabirth;

				array_push($insert_row, array(
					'key' => 'country_b',
					'attr' => array(
						'class="center'.(empty($stabirth) ? ' vbo-report-load-nazione' : '').'"'
					),
					'callback' => function($val) {
						return (!empty($val) ? $this->nazioni[$val]['name'] : '?');
					},
					'no_export_callback' => 1,
					'value' => $staval
				));

				/**
				 * Cittadinanza.
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_country_c = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_c');

				$citizen = !empty($guests['country']) && $guest_ind < 2 ? $guests['country'] : '';
				$citizenval = '';
				if (!empty($citizen) && $guest_ind < 2) {
					$citizenval = $this->checkCountry($citizen);
				}

				// check nationality field from pre-checkin
				$pax_citizen = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'nationality');
				$citizen = !empty($pax_citizen) ? $pax_citizen : $citizen;

				$citizen = !empty($pax_country_c) ? $pax_country_c : $citizen;
				$citizenval = !empty($pax_country_c) ? $pax_country_c : $this->checkCountry($citizen);

				array_push($insert_row, array(
					'key' => 'country_c',
					'attr' => array(
						'class="center'.(empty($citizen) ? ' vbo-report-load-cittadinanza' : '').'"'
					),
					'callback' => function($val) {
						return !empty($val) ? ($this->nazioni[$val]['name'] ?? '?') : '?';
					},
					'no_export_callback' => 1,
					'value' => !empty($citizenval) ? $citizenval : ''
				));

				/**
				 * Comune di residenza.
				 * Check compatibility with pax_data field of driver for "Italy".
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				$pax_comstay = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'comune_s');
				/**
				 * This is not needed!
				 * 
				 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
				 */
				/*
				array_push($insert_row, array(
					'key' => 'comune_s',
					'attr' => array(
						'class="center'.(empty($pax_comstay) && $guest_ind < 2 ? ' vbo-report-load-field vbo-report-load-comune-stay' : '').'"'
					),
					'callback' => function($val) use ($guest_ind) {
						if ($guest_ind > 1) {
							// not needed for the Nth guest
							return "---";
						}
						if (!empty($val) && isset($this->comuniProvince['comuni'][$val])) {
							return $this->comuniProvince['comuni'][$val]['name'];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $pax_comstay,
				));
				*/

				/**
				 * Provincia di residenza.
				 * Check compatibility with pax_data field of driver for "Italy".
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				$pax_provstay = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'province_s');
				/**
				 * This is not needed!
				 * 
				 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
				 */
				/*
				array_push($insert_row, array(
					'key' => 'province_s',
					'attr' => array(
						'class="center'.(empty($pax_provstay) && $guest_ind < 2 ? ' vbo-report-load-field vbo-report-load-provincia-stay' : '').'"'
					),
					'callback' => function($val) use ($guest_ind) {
						if ($guest_ind > 1) {
							// not needed for the Nth guest
							return "---";
						}
						if (!empty($val) && isset($this->comuniProvince['province'][$val])) {
							return $this->comuniProvince['province'][$val];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $pax_provstay,
				));
				*/

				/**
				 * Stato di residenza.
				 * Check compatibility with pax_data field of driver for "Italy".
				 * 
				 * @since 	1.15.0 (J) - 1.5.0 (WP)
				 */
				$pax_provstay = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_s');
				/**
				 * This is not needed!
				 * 
				 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
				 */
				/*
				array_push($insert_row, array(
					'key' => 'country_s',
					'attr' => array(
						'class="center'.(empty($pax_provstay) && $guest_ind < 2 ? ' vbo-report-load-field vbo-report-load-nazione-stay' : '').'"'
					),
					'callback' => function($val) use ($guest_ind) {
						if ($guest_ind > 1) {
							// not needed for the Nth guest
							return "---";
						}
						if (!empty($val) && isset($this->nazioni[$val])) {
							return $this->nazioni[$val]['name'];
						}
						// information is missing and should be provided
						return '?';
					},
					'no_export_callback' => 1,
					'value' => $pax_provstay,
				));
				*/

				/**
				 * Indirizzo.
				 * Optional information that is not collected through any pax_data field.
				 * We try to fill in with the customer information, if available.
				 */
				$address = !empty($guests['address']) ? $guests['address'] : '';
				/**
				 * This is not needed!
				 * 
				 * @see 	https://alloggiatiweb.poliziadistato.it/PortaleAlloggiati/Download/Manuali/MANUALEALBERGHI.pdf
				 */
				/*
				array_push($insert_row, array(
					'key' => 'address',
					'attr' => array(
						'class="center"'
					),
					'value' => $address
				));
				*/

				//Tipo documento

				/**
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_doctype = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'doctype');

				$doctype = $pax_doctype ?: '';

				$doctype_elem_class = '';
				if ($guest_room_ind < 2 && empty($doctype)) {
					// mandatory selection style
					$doctype_elem_class = ' vbo-report-load-doctype';
				} elseif (empty($doctype)) {
					// optional selection style
					$doctype_elem_class = ' vbo-report-load-doctype vbo-report-load-field-optional';
				} elseif (!empty($doctype)) {
					// rectify selection style
					$doctype_elem_class = ' vbo-report-load-doctype vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'doctype',
					'attr' => array(
						'class="center' . $doctype_elem_class . '"'
					),
					'callback' => function($val) use ($guest_room_ind) {
						if ($guest_room_ind > 1 && empty($val)) {
							return '---';
						}
						return empty($val) ? '?' : $val;
					},
					'no_export_callback' => 1,
					'value' => $doctype
				));

				//Numero documento

				/**
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_docnum = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'docnum');

				$docnum = $pax_docnum ?: '';

				if (empty($pax_docnum) && $guest_room_ind < 2) {
					if (is_array($guests['pax_data']) && !empty($guests['pax_data'][0][1]['docnum'])) {
						$docnum = $guests['pax_data'][0][1]['docnum'];
					} elseif (!empty($guests['docnum'])) {
						$docnum = $guests['docnum'];
					}
				}

				$docnum_elem_class = '';
				if ($guest_room_ind < 2 && empty($docnum)) {
					// mandatory selection style
					$docnum_elem_class = ' vbo-report-load-docnum';
				} elseif (empty($docnum)) {
					// optional selection style
					$docnum_elem_class = ' vbo-report-load-docnum vbo-report-load-field-optional';
				} elseif (!empty($docnum)) {
					// rectify selection style
					$docnum_elem_class = ' vbo-report-load-docnum vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'docnum',
					'attr' => array(
						'class="center' . $docnum_elem_class . '"'
					),
					'callback' => function($val) use ($guest_room_ind) {
						if ($guest_room_ind > 1 && empty($val)) {
							return '---';
						}
						return empty($val) ? '?' : $val;
					},
					'value' => $docnum
				));

				//Luogo rilascio documento

				/**
				 * Check compatibility with pax_data field of driver for "Italy".
				 */
				$pax_docplace = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'docplace');

				$docplace = $pax_docplace ?: '';

				$docplace_elem_class = '';
				if ($guest_room_ind < 2 && empty($docplace)) {
					// mandatory selection style
					$docplace_elem_class = ' vbo-report-load-docplace';
				} elseif (empty($docplace)) {
					// optional selection style
					$docplace_elem_class = ' vbo-report-load-docplace vbo-report-load-field-optional';
				}

				array_push($insert_row, array(
					'key' => 'docplace',
					'attr' => array(
						'class="center' . $docplace_elem_class . '"'
					),
					'callback' => function($val) use ($guest_room_ind) {
						if ($guest_room_ind > 1 && empty($val)) {
							return '---';
						}
						if (!empty($val) && isset($this->nazioni[$val])) {
							return $this->nazioni[$val]['name'];
						}
						if (!empty($val) && isset($this->comuniProvince['comuni'][$val]['name'])) {
							return $this->comuniProvince['comuni'][$val]['name'];
						}
						return $val ?: '?';
					},
					'no_export_callback' => 1,
					'value' => $docplace
				));

				/**
				 * Thanks to the WSDL functionalities, we now also support the "Gestione Appartamenti" category.
				 * Those PMs who belong to this category, rather than hotels, must use a column "ID Appartamento".
				 * 
				 * @since 	1.17.1 (J) - 1.7.1 (WP)
				 */
				if (!empty($settings['apartments']) && !empty($settings['apartments_list'])) {
					// current pax value for apartment ID
					$pax_idapp = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'idapp');

					if (empty($pax_idapp) && !empty($settings['apartment_relations'])) {
						// set apartment ID from the saved relations
						$pax_idapp = isset($settings['apartment_relations'][$guests['idroom']]) ? $settings['apartment_relations'][$guests['idroom']]['id'] : $pax_idapp;
					}

					if (empty($pax_idapp)) {
						// mandatory selection style
						$id_apt_elem_class = ' vbo-report-load-field vbo-report-load-idapp';
					} else {
						// rectify selection style
						$id_apt_elem_class = ' vbo-report-load-idapp vbo-report-load-elem-filled';
					}

					// id appartamento
					array_push($insert_row, array(
						'key' => 'idapp',
						'attr' => array(
							'class="center' . $id_apt_elem_class . '"'
						),
						'value' => (int) $pax_idapp,
					));
				}

				//id booking
				array_push($insert_row, array(
					'key' => 'idbooking',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) use ($line_number) {
						// make sure to keep the data-bid attribute as it's used by JS to identify the booking ID
						return '<a data-bid="' . $val . '" href="index.php?option=com_vikbooking&task=editorder&cid[]=' . $val . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i> ' . $val . '</a> / <span>#' . $line_number . '</span>';
					},
					'ignore_export' => 1,
					'value' => $guests['id']
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
					'class="vbo-report-total"'
				),
				'value' => '<h3>'.JText::translate('VBOREPORTSTOTALROW').'</h3>'
			),
			array(
				'attr' => array(
					'colspan="'.(count($this->cols) - 1).'"'
				),
				'value' => count($this->rows)
			)
		));

		//Debug
		if ($this->debug) {
			$this->setWarning('path to report file = '.urlencode(dirname(__FILE__)).'<br/>');
			$this->setWarning('$total_rooms_units = '.$total_rooms_units.'<br/>');
			$this->setWarning('$bookings:<pre>'.print_r($bookings, true).'</pre><br/>');
		}

		return true;
	}

	/**
	 * Builds the report (card) lines for export or transmission.
	 * 
	 * @return 	array 	Empty array in case of errors, or list of rows.
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function buildCardLines()
	{
		if (!$this->getReportData()) {
			return [];
		}

		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? json_decode($pfiller, true) : [];
		$pfiller = !is_array($pfiller) ? [] : $pfiller;

		// map of the rows keys with their related length
		$keys_length_map = [
			'tipo' 		 => 2,
			'checkin' 	 => 10,
			'nights' 	 => 2,
			'last_name'  => 50,
			'first_name' => 30,
			'gender' 	 => 1,
			'date_birth' => 10,
			'comune_b' 	 => 9,
			'province_b' => 2,
			'country_b'  => 9,
			'country_c'  => 9,
			// 'comune_s' 	 => 9,
			// 'province_s' => 2,
			// 'country_s'  => 9,
			// the field "address" is optional
			// 'address' 	 => 50,
			//
			'doctype' 	 => 5,
			'docnum' 	 => 20,
			'docplace' 	 => 9,
			'idapp' 	 => 6,
		];

		// pool of booking IDs to update their history
		$this->export_booking_ids = [];

		// array of lines (one line for each guest)
		$lines = [];

		// push the lines of the Text file
		foreach ($this->rows as $ind => $row) {
			$line_cont = '';
			foreach ($row as $field) {
				if ($field['key'] == 'idbooking' && !in_array($field['value'], $this->export_booking_ids)) {
					array_push($this->export_booking_ids, $field['value']);
				}
				if (isset($field['ignore_export'])) {
					continue;
				}
				// report value
				if (is_array($pfiller) && isset($pfiller[$ind]) && isset($pfiller[$ind][$field['key']])) {
					if (strlen($pfiller[$ind][$field['key']])) {
						$field['value'] = $pfiller[$ind][$field['key']];
					}
				}
				
				// values set to -1 are usually empty and should have been filled in manually
				if ($field['value'] === -1) {
					// we raise a warning in this case without stopping the process
					$field['value'] = 0;
					$warn_message = 'La riga #' . $ind . ' ha un valore vuoto che doveva essere riempito manualmente cliccando sul blocco in rosso. Il file potrebbe contenere valori invalidi per questa riga.';
					if ($this->getScope() === 'web') {
						VikError::raiseWarning('', $warn_message);
					} else {
						$this->setWarning($warn_message);
					}
				}

				if (isset($field['callback_export'])) {
					$field['callback'] = $field['callback_export'];
				}
				$value = !isset($field['no_export_callback']) && isset($field['callback']) && is_callable($field['callback']) ? $field['callback']($field['value']) : $field['value'];
				// 0 or '---' should be changed to an empty string (case of "-- Estero --" or field to be filled with Blank)
				$value = empty($value) || $value == '---' ? '' : $value;
				// concatenate the field to the current line
				$line_cont .= $this->valueFiller($value, $keys_length_map[$field['key']]);
			}

			// push the line in the array of lines
			array_push($lines, $line_cont);
		}

		return $lines;
	}

	/**
	 * Generates the text file for the Italian Police, then sends it to output for download.
	 * In case of errors, the process is not terminated (exit) to let the View display the
	 * error message(s). The export type argument can eventually determine an action to run.
	 *
	 * @param 	string 	$export_type 	Differentiates the type of export requested.
	 *
	 * @return 	void|bool 				Void in case of script termination, boolean otherwise.
	 */
	public function customExport($export_type = null)
	{
		// build the card lines
		$lines = $this->buildCardLines();

		// build report action data, if needed
		$action_data = array_merge($this->getActionData($registry = false), ['cards' => $lines]);

		/**
		 * Custom export method can run a custom action.
		 * 
		 * @since 	1.17.1 (J) - 1.7.1 (WP)
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
			// set the error
			$this->setError('No valid guest registration lines to export.');

			// check if some warnings were set
			if ($warnings = $this->getWarning()) {
				// append the warnings to the error
				$this->setError('Warnings: ' . $warnings);
			}

			// abort
			return false;
		}

		// update history for all bookings affected before exporting
		foreach ($this->export_booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance($bid)->store('RP', $this->reportName . ' - Export');
		}

		/**
		 * Custom export method supports a custom export handler, if previously set.
		 * 
		 * @since 	1.16.1 (J) - 1.6.1 (WP)
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
	 * Custom scoped action to transmit the guest cards.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function transmitCards($scope = null, array $data = [])
	{
		if (!($data['cards'] ?? []) && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadSchedaPolizia(\'transmitCards\');</script>',
			];
		}

		if (!($data['cards'] ?? [])) {
			// attempt to build the card lines if not set
			$data['cards'] = $this->buildCardLines();
		}

		if (!$data['cards']) {
			throw new Exception('Nessun dato presente per la trasmissione delle schedine alloggiati.', 500);
		}

		$settings = $this->loadSettings();

		if (!$settings || empty($settings['user']) || empty($settings['pwd']) || empty($settings['wskey'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// get the token to perform the request
		$token = $settings['token'] ?: '';

		if (empty($token) || !$this->_callActionReturn('authenticationTest', 'valid', $scope, $data)) {
			// generate a new token
			$token = $this->_callActionReturn('generateToken', 'token', $scope, $data);
		}

		// build card string XML nodes
		$card_string_nodes = [];
		foreach ($data['cards'] as $card_string) {
			$card_string_nodes[] = '<string>' . htmlspecialchars($card_string) . '</string>';
		}

		// detect the type of request to use (Gestione Appartamenti or Hotel)
		$rqType = 'Send';
		$responseNode = 'SendResponse';
		$resultNode = 'SendResult';
		if (!empty($settings['apartments']) && !empty($settings['apartments_list'])) {
			$rqType = 'GestioneAppartamenti_FileUnico_Send';
			$responseNode = 'GestioneAppartamenti_FileUnico_SendResponse';
			$resultNode = 'GestioneAppartamenti_FileUnico_SendResult';
		}

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <' . $rqType . ' xmlns="AlloggiatiService">
      <Utente>' . $settings['user'] . '</Utente>
      <token>' . $token . '</token>
      <ElencoSchedine>
      	' . implode("\n", $card_string_nodes) . '
      </ElencoSchedine>
    </' . $rqType . '>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient();

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, $rqType, SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->{$responseNode}->{$resultNode}->esito)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = !strcasecmp((string) $xmlBody->{$responseNode}->{$resultNode}->esito, 'false');

			if ($is_error || !($xmlBody->{$responseNode}->result ?? '')) {
				$error_code = (string) ($xmlBody->{$responseNode}->{$resultNode}->ErroreCod ?? '0');
				$error_desc = (string) ($xmlBody->{$responseNode}->{$resultNode}->ErroreDes ?? 'ERR');
				$error_dets = (string) ($xmlBody->{$responseNode}->{$resultNode}->ErroreDettaglio ?? 'Generico');
				// terminate the execution in case of errors or empty result
				throw new Exception(sprintf("[%s] Errore trasmissione schedine (%s):\n%s", $error_code, $error_desc, $error_dets), 500);
			}

			// get the number of valid cards
			$tot_submitted_cards = count($card_string_nodes);
			$tot_valid_cards = (int) ($xmlBody->{$responseNode}->result->SchedineValide ?? 0);

			// get the cards with errors
			$error_cards = [];
			$index = 0;
			foreach ($xmlBody->{$responseNode}->result->Dettaglio->EsitoOperazioneServizio ?? [] as $cardResult) {
				if (!strcasecmp((string) $cardResult->esito, 'false')) {
					// error found
					$error_code = (string) ($cardResult->ErroreCod ?? '0');
					$error_desc = (string) ($cardResult->ErroreDes ?? 'ERR');
					$error_dets = (string) ($cardResult->ErroreDettaglio ?? 'Generico');
					// push error details
					$error_cards[] = [
						'code'    => $error_code,
						'index'   => $index,
						'desc'    => $error_desc,
						'dets'    => $error_dets,
						'message' => sprintf('[%s] Errore trasmissione schedina #%s (%s): %s', $error_code, $index, $error_desc, $error_dets),
					];
				}
				$index++;
			}

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($tot_submitted_cards == $tot_valid_cards ? 'successmade' : ($tot_valid_cards > 0 ? 'warn' : 'err')) . '">Totale schedine valide: ' . $tot_valid_cards . '/' . $tot_submitted_cards . '</p>';

			if ($error_cards) {
				$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
				$html .= '	<div class="vbo-params-wrap">';
				$html .= '		<div class="vbo-params-container">';
				$html .= '			<div class="vbo-params-block">';

				foreach ($error_cards as $error_card) {
					$html .= '			<div class="vbo-param-container">';
					$html .= '				<div class="vbo-param-label"><i class="' . VikBookingIcons::i('times-circle') . '"></i> Errore</div>';
					$html .= '				<div class="vbo-param-setting"><span>' . $error_card['message'] . '</span></div>';
					$html .= '			</div>';
				}

				$html .= '			</div>';
				$html .= '		</div>';
				$html .= '	</div>';
				$html .= '</div>';
			}
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// get the currently active profile ID, if any
		$activeProfileId = $this->getActiveProfile();

		// update history for all bookings affected
		foreach ($this->export_booking_ids as $bid) {
			// build extra data payload for the history event
			$extradata = [
				'transmitted' => 1,
				'method'      => 'transmitCards',
				'report'      => $this->getFileName(),
			];
			// store booking history event
			VikBooking::getBookingHistoryInstance($bid)
				->setExtraData($extradata)
				->store('RP', $this->reportName . ' - Trasmissione schedine');
		}

		/**
		 * When the report is executed through a cron-job, the transmission of the guest details
		 * will also dump the data transmitted onto a resource file, useful for sending it via email.
		 * 
		 * @since 	1.18.0 (J) - 1.8.0 (WP)
		 */
		if ($scope === 'cron') {
			// prepare the TXT resource file information
			$txt_name = implode('_', array_filter([$this->getFileName(), 'trasmissione', 'schedine', time(), $activeProfileId, rand(100, 999)])) . '.txt';
			$txt_dest = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $txt_name;
			$txt_url  = $this->getDataMediaUrl() . $txt_name;

			// store the TXT bytes into a local file on disk
			$stored = JFile::write($txt_dest, implode("\r\n", $data['cards']));

			if ($stored && VBOPlatformDetection::isWordPress()) {
				/**
				 * Trigger files mirroring operation
				 */
				VikBookingLoader::import('update.manager');
				VikBookingUpdateManager::triggerUploadBackup($txt_dest);
			}

			if ($stored) {
				// define a new report resource for the generated file
				$this->defineResourceFile([
					'summary' => sprintf(
						'%sSchedine alloggiati generate con successo: %d.',
						($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
						$tot_submitted_cards
					),
					'url'  => $txt_url,
					'path' => $txt_dest,
				]);
			}
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.transmit.' . ($error_cards ? 'error' : 'ok'),
				'title'   => $this->reportName . ' - Trasmissione schedine',
				'summary' => sprintf(
					'%sSono stati trasmessi i dati degli ospiti con check-in dal %s al %s.',
					($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
					$this->exported_checkin_dates[0] ?? '',
					$this->exported_checkin_dates[1] ?? ''
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
		}

		return [
			'html'            => $html,
			'success'         => ($tot_valid_cards > 0),
			'valid_cards'     => $tot_valid_cards,
			'submitted_cards' => $tot_submitted_cards,
			'error_cards'     => $error_cards,
		];
	}

	/**
	 * Custom scoped action to test the transmission of the guest cards.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function testTransmitCards($scope = null, array $data = [])
	{
		if (!($data['cards'] ?? []) && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadSchedaPolizia(\'testTransmitCards\');</script>',
			];
		}

		if (!($data['cards'] ?? [])) {
			// attempt to build the card lines if not set
			$data['cards'] = $this->buildCardLines();
		}

		if (!$data['cards']) {
			throw new Exception('Nessun dato presente per il controllo preliminare delle schedine alloggiati.', 500);
		}

		$settings = $this->loadSettings();

		if (!$settings || empty($settings['user']) || empty($settings['pwd']) || empty($settings['wskey'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// get the token to perform the request
		$token = $settings['token'] ?: '';

		if (empty($token) || !$this->_callActionReturn('authenticationTest', 'valid', $scope, $data)) {
			// generate a new token
			$token = $this->_callActionReturn('generateToken', 'token', $scope, $data);
		}

		// build card string XML nodes
		$card_string_nodes = [];
		foreach ($data['cards'] as $card_string) {
			$card_string_nodes[] = '<string>' . htmlspecialchars($card_string) . '</string>';
		}

		// detect the type of request to use (Gestione Appartamenti or Hotel)
		$rqType = 'Test';
		$responseNode = 'TestResponse';
		$resultNode = 'TestResult';
		if (!empty($settings['apartments']) && !empty($settings['apartments_list'])) {
			$rqType = 'GestioneAppartamenti_FileUnico_Test';
			$responseNode = 'GestioneAppartamenti_FileUnico_TestResponse';
			$resultNode = 'GestioneAppartamenti_FileUnico_TestResult';
		}

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <' . $rqType . ' xmlns="AlloggiatiService">
      <Utente>' . $settings['user'] . '</Utente>
      <token>' . $token . '</token>
      <ElencoSchedine>
      	' . implode("\n", $card_string_nodes) . '
      </ElencoSchedine>
    </' . $rqType . '>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient();

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, $rqType, SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->{$responseNode}->{$resultNode}->esito)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = !strcasecmp((string) $xmlBody->{$responseNode}->{$resultNode}->esito, 'false');

			if ($is_error || !($xmlBody->{$responseNode}->result ?? '')) {
				$error_code = (string) ($xmlBody->{$responseNode}->{$resultNode}->ErroreCod ?? '0');
				$error_desc = (string) ($xmlBody->{$responseNode}->{$resultNode}->ErroreDes ?? 'ERR');
				$error_dets = (string) ($xmlBody->{$responseNode}->{$resultNode}->ErroreDettaglio ?? 'Generico');
				// terminate the execution in case of errors or empty result
				throw new Exception(sprintf("[%s] Errore validazione schedine (%s):\n%s", $error_code, $error_desc, $error_dets), 500);
			}

			// get the number of valid cards
			$tot_submitted_cards = count($card_string_nodes);
			$tot_valid_cards = (int) ($xmlBody->{$responseNode}->result->SchedineValide ?? 0);

			// get the cards with errors
			$error_cards = [];
			$index = 0;
			foreach ($xmlBody->{$responseNode}->result->Dettaglio->EsitoOperazioneServizio ?? [] as $cardResult) {
				if (!strcasecmp((string) $cardResult->esito, 'false')) {
					// error found
					$error_code = (string) ($cardResult->ErroreCod ?? '0');
					$error_desc = (string) ($cardResult->ErroreDes ?? 'ERR');
					$error_dets = (string) ($cardResult->ErroreDettaglio ?? 'Generico');
					// push error details
					$error_cards[] = [
						'code'    => $error_code,
						'index'   => $index,
						'desc'    => $error_desc,
						'dets'    => $error_dets,
						'message' => sprintf('[%s] Errore validazione schedina #%s (%s): %s', $error_code, $index, $error_desc, $error_dets),
					];
				}
				$index++;
			}

			// build HTML response string
			$html = '';
			$html .= '<p class="' . ($tot_submitted_cards == $tot_valid_cards ? 'successmade' : ($tot_valid_cards > 0 ? 'warn' : 'err')) . '">Totale schedine valide: ' . $tot_valid_cards . '/' . $tot_submitted_cards . '</p>';

			if ($error_cards) {
				$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
				$html .= '	<div class="vbo-params-wrap">';
				$html .= '		<div class="vbo-params-container">';
				$html .= '			<div class="vbo-params-block">';

				foreach ($error_cards as $error_card) {
					$html .= '			<div class="vbo-param-container">';
					$html .= '				<div class="vbo-param-label"><i class="' . VikBookingIcons::i('times-circle') . '"></i> Errore</div>';
					$html .= '				<div class="vbo-param-setting"><span>' . $error_card['message'] . '</span></div>';
					$html .= '			</div>';
				}

				$html .= '			</div>';
				$html .= '		</div>';
				$html .= '	</div>';
				$html .= '</div>';
			}
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// get the currently active profile ID, if any
		$activeProfileId = $this->getActiveProfile();

		/**
		 * When the report is executed through a cron-job, the verification of the guest details
		 * will also dump the data verified onto a resource file, useful for sending it via email.
		 * 
		 * @since 	1.18.0 (J) - 1.8.0 (WP)
		 */
		if ($scope === 'cron') {
			// prepare the TXT resource file information
			$txt_name = implode('_', array_filter([$this->getFileName(), 'controllo', 'schedine', time(), $activeProfileId, rand(100, 999)])) . '.txt';
			$txt_dest = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $txt_name;
			$txt_url  = $this->getDataMediaUrl() . $txt_name;

			// store the TXT bytes into a local file on disk
			$stored = JFile::write($txt_dest, implode("\r\n", $data['cards']));

			if ($stored && VBOPlatformDetection::isWordPress()) {
				/**
				 * Trigger files mirroring operation
				 */
				VikBookingLoader::import('update.manager');
				VikBookingUpdateManager::triggerUploadBackup($txt_dest);
			}

			if ($stored) {
				// define a new report resource for the generated file
				$this->defineResourceFile([
					'summary' => sprintf(
						"%sControllo schedine alloggiati completato con %s.\nTotale schedine analizzate: %d.\nTotale schedine valide: %d.\n%s",
						($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
						($tot_submitted_cards == $tot_valid_cards ? 'successo' : 'degli errori'),
						$tot_submitted_cards,
						$tot_valid_cards,
						($error_cards ? "\n" . implode("\n", array_column($error_cards, 'message')) : '')
					),
					'url'  => $txt_url,
					'path' => $txt_dest,
				]);
			}
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.testtransmit.' . ($error_cards ? 'error' : 'ok'),
				'title'   => $this->reportName . ' - Controllo schedine',
				'summary' => sprintf(
					'%sSono stati verificati i dati degli ospiti con check-in dal %s al %s.',
					($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
					$this->exported_checkin_dates[0] ?? '',
					$this->exported_checkin_dates[1] ?? ''
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
		}

		return [
			'html'            => $html,
			'success'         => ($tot_valid_cards > 0),
			'valid_cards'     => $tot_valid_cards,
			'submitted_cards' => $tot_submitted_cards,
			'error_cards'     => $error_cards,
		];
	}

	/**
	 * Custom scoped action to obtain the list of apartments.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function listApartments($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['user']) || empty($settings['pwd']) || empty($settings['wskey'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// response properties
		$html 		= '';
		$apartments = [];

		// get the token to perform the request
		$token = $settings['token'] ?: '';

		if (empty($token) || !$this->_callActionReturn('authenticationTest', 'valid', $scope, $data)) {
			// generate a new token
			$token = $this->_callActionReturn('generateToken', 'token', $scope, $data);
		}

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <Tabella xmlns="AlloggiatiService">
      <Utente>' . $settings['user'] . '</Utente>
      <token>' . $token . '</token>
      <tipo>ListaAppartamenti</tipo>
    </Tabella>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient();

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'Tabella', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->TabellaResponse->TabellaResult->esito)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = !strcasecmp((string) $xmlBody->TabellaResponse->TabellaResult->esito, 'false');

			if ($is_error || !($xmlBody->TabellaResponse->CSV ?? '')) {
				$error_code = (string) ($xmlBody->TabellaResponse->TabellaResult->ErroreCod ?? '0');
				$error_desc = (string) ($xmlBody->TabellaResponse->TabellaResult->ErroreDes ?? 'ERR');
				$error_dets = (string) ($xmlBody->TabellaResponse->TabellaResult->ErroreDettaglio ?? 'Generico');
				// terminate the execution in case of errors or empty CSV
				throw new Exception(sprintf("[%s] Errore lettura tabella appartamenti (%s):\n%s", $error_code, $error_desc, $error_dets), 500);
			}

			// parse the CSV string into a list of data
			$csv_list = array_map(function($csv_line) {
				return str_getcsv($csv_line, $separator = ';');
			}, array_values(
				array_filter(
					preg_split("/[\r\n]/", (string) $xmlBody->TabellaResponse->CSV)
				)
			));

			if (!$csv_list) {
				throw new Exception(sprintf("Impossibile leggere la lista degli appartamenti in formato CSV:\n%s", (string) $xmlBody->TabellaResponse->CSV), 500);
			}

			// get the table head columns and remove it from the list
			$table_head_cols = array_shift($csv_list);

			if (!$csv_list) {
				throw new Exception('Nessun appartamento presente.', 500);
			}

			// load the rooms from VikBooking
			$vbo_rooms = VikBooking::getAvailabilityInstance(true)->loadRooms();

			// build the option tags for every VBO room
			$vbo_room_options = [];
			foreach ($vbo_rooms as $vbo_room) {
				$vbo_room_options[] = '<option value="' . $vbo_room['id'] . '" data-selected="' . $vbo_room['id'] . '">' . $vbo_room['name'] . '</option>';
			}

			// build HTML response string
			$html .= '<div class="table-responsive">';
			$html .= '	<table class="table">';
			$html .= '		<thead>';
			$html .= '			<tr>';

			foreach ($table_head_cols as $col_k => $table_head_col) {
				$html .= '			<td>' . $table_head_col . '</td>';
				if ($col_k === 1) {
					// add static cell for the VBO room relation
					$html .= '		<td>Relazione</td>';
				}
			}

			$html .= '			</tr>';
			$html .= '		</thead>';
			$html .= '		<tbody>';

			foreach ($csv_list as $csv_row) {
				// push new apartment
				if (count($table_head_cols) === count($csv_row)) {
					// set an associative list of apartment details
					$apartments[] = array_combine($table_head_cols, $csv_row);
				} else {
					// fallback onto a numeric array of apartment details
					$apartments[] = $csv_row;
				}

				// display apartment details
				$html .= '		<tr>';

				// gather remote apartment details
				$id_apt = $csv_row[0] ?? null;
				$apt_name = $csv_row[1] ?? null;

				// find the VBO matching room ID
				$vbo_matching_rid = $this->findVboMatchingRoomId($id_apt, $apt_name, $vbo_rooms, $settings['apartment_relations'] ?? []);

				foreach ($csv_row as $col_k => $csv_col) {
					$html .= '		<td>' . $csv_col . '</td>';
					if ($col_k === 1) {
						// add static cell for the room relation
						$relation_opts = implode("\n", $vbo_room_options);

						if ($vbo_matching_rid) {
							$relation_opts = str_replace('data-selected="' . $vbo_matching_rid . '"', 'selected="selected"', $relation_opts);
						}

						// set HTML string
						$html .= '		<td><select onchange="vboAlloggiatiSetAptRel(\'' . $id_apt . '\', this.value, \'' . htmlspecialchars((string) $apt_name, ENT_QUOTES, 'UTF-8') . '\');"><option value="">---</option>' . $relation_opts . '</select></td>';
					}
				}

				$html .= '		</tr>';
			}

			$html .= '		</tbody>';
			$html .= '	</table>';
			$html .= '</div>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// update settings with the apartment details obtained
		$settings['apartments_list'] = $apartments;

		/**
		 * Since this report supports multiple profiles, settings should be saved
		 * on the active profile, and if not empty, saving will be successful.
		 * 
		 * @since 	1.18.0 (J) - 1.8.0 (WP)
		 */
		$this->saveSettings($settings, true, $this->getActiveProfile());

		return [
			'html'       => $html,
			'apartments' => $apartments,
		];
	}

	/**
	 * Custom scoped action to list all the previously downloaded PDF receipts.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function receiptsArchive($scope = null, array $data = [])
	{
		// response properties
		$html = '';

		// read all the downloaded receipts (we ignore the eventually configure setting profiles by listing all receipts)
		$match_name = $this->getFileName() . '_receipt';
		$receipts = JFolder::files($this->getDataMediaPath(), "^{$match_name}.+\.pdf$", $recurse = false, $full = false);

		if (!$receipts) {
			$html .= '<p class="warn">Nessuna ricevuta scaricata.</p>';
		} else {
			$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
			$html .= '	<div class="vbo-params-wrap">';
			$html .= '		<div class="vbo-params-container">';
			$html .= '			<div class="vbo-params-block">';

			foreach ($receipts as $receipt) {
				$name_parts = explode('_', preg_replace("/^{$match_name}_/", '', $receipt));
				$receipt_date = preg_replace("/T[0-9]{2}:[0-9]{2}:[0-9]{2}$/", '', $name_parts[0]);
				// the file creation timestamp in the name parts can be shifted when using setting profiles
				$creation_ts = is_numeric($name_parts[1]) && strlen((string) $name_parts[1]) > 9 ? $name_parts[1] : $name_parts[2];
				$creation_ts = is_numeric($creation_ts) ? $creation_ts : time();

				$html .= '			<div class="vbo-param-container">';
				$html .= '				<div class="vbo-param-label">Ricevuta del ' . ($receipt_date ?: $receipt) . '</div>';
				$html .= '				<div class="vbo-param-setting">';
				$html .= '					<a href="' . $this->getDataMediaUrl() . $receipt . '" target="_blank">Scarica PDF</a>';
				$html .= '					<span class="vbo-param-setting-comment">Scaricata in data ' . date('Y-m-d H:i:s', $creation_ts) . '</span>';
				$html .= '				</div>';
				$html .= '			</div>';
			}

			$html .= '			</div>';
			$html .= '		</div>';
			$html .= '	</div>';
			$html .= '</div>';
		}

		return [
			'html'  => $html,
			'total' => count($receipts),
		];
	}

	/**
	 * Custom scoped action to check if a receipt exists for a given date.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function receiptExists($scope = null, array $data = [])
	{
		// response properties
		$html = '';
		$pdf_url = '';
		$pdf_path = '';
		$exists = false;

		// receipt date to check
		$receipt_date = $data['receipt_date'] ?? date('Y-m-d');

		if (strlen($receipt_date) > 10) {
			// get only the date with no time
			$receipt_date = substr($receipt_date, 0, 10);
		}

		if ($receipt_date && preg_match("/^[0-9]+/", $receipt_date)) {
			// ensure the date received is in military format
			$receipt_date = date('Y-m-d', VikBooking::getDateTimestamp($receipt_date));
		}

		// find the downloaded receipt, if any, for the currently active profile (if any)
		$current_profile = $this->getActiveProfile();
		$match_name = $this->getFileName() . '_receipt_' . $receipt_date . (!empty($current_profile) ? '_' . $current_profile : '');
		$receipts = JFolder::files($this->getDataMediaPath(), "^{$match_name}.+\.pdf$", $recurse = false, $full = false);

		if (!$receipts) {
			$html .= '<p class="warn">Nessuna ricevuta scaricata per la data ' . $receipt_date . '.</p>';
		} else {
			$html .= '<p class="successmade">Ricevuta scaricata per la data ' . $receipt_date . '.</p>';

			// turn flag on
			$exists = true;

			// set receipt URL
			$pdf_url = $this->getDataMediaUrl() . $receipts[0];

			// set receipt path
			$pdf_path = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $receipts[0];
		}

		return [
			'html'     => $html,
			'pdf_url'  => $pdf_url,
			'pdf_path' => $pdf_path,
			'pdf_date' => $receipt_date,
			'success'  => $exists,
		];
	}

	/**
	 * Custom scoped action to download the PDF receipt of a previous transmission.
	 * A date is required for downloading a receipt, and the following formats are
	 * accepted: {today}, today, {now}, now, Y-m-d, Y-m-d\TH:i:s, alternatively any
	 * valid Date and Time Format modifier, either with or without curly brackets,
	 * such as {-1 day}, {-14 days}, -1 day, -14 days.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function downloadReceipt($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['user']) || empty($settings['pwd']) || empty($settings['wskey'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// response properties
		$html 		  = '';
		$pdf_dest     = '';
		$pdf_url      = '';
		$receipt_date = '';

		// get the token to perform the request
		$token = $settings['token'] ?: '';

		if (empty($token) || !$this->_callActionReturn('authenticationTest', 'valid', $scope, $data)) {
			// generate a new token
			$token = $this->_callActionReturn('generateToken', 'token', $scope, $data);
		}

		// build the date for fetching the receipt
		$receipt_date = $data['receipt_date'] ?? '';
		if ($receipt_date && preg_match("/^[0-9]+/", $receipt_date)) {
			// ensure the date received is in military format
			$receipt_date = date('Y-m-d', VikBooking::getDateTimestamp($receipt_date));
		}
		if (!$receipt_date || stripos((string) $receipt_date, 'now') !== false || stripos((string) $receipt_date, 'today') !== false) {
			// default to today's date
			$receipt_date = date('Y-m-d\TH:i:s');
		} elseif (stripos((string) $receipt_date, 'yesterday') !== false || preg_match("/[+-][0-9]+\s?days?/i", (string) $receipt_date)) {
			// calculate the requested date
			$receipt_date = trim(str_replace(['{', '}'], '', $receipt_date));
			$receipt_date = date('Y-m-d\TH:i:s', strtotime($receipt_date));
		}

		if (!preg_match("/T[0-9]{2}:[0-9]{2}:[0-9]{2}$/", $receipt_date)) {
			// append the time to an expected Y-m-d date string format
			$receipt_date .= 'T00:00:00';
		}

		// get the currently active profile ID, if any
		$activeProfileId = $this->getActiveProfile();

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <Ricevuta xmlns="AlloggiatiService">
      <Utente>' . $settings['user'] . '</Utente>
      <token>' . $token . '</token>
      <Data>' . $receipt_date . '</Data>
    </Ricevuta>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient();

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'Ricevuta', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->RicevutaResponse->RicevutaResult->esito)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = !strcasecmp((string) $xmlBody->RicevutaResponse->RicevutaResult->esito, 'false');

			if ($is_error || !($xmlBody->RicevutaResponse->PDF ?? '')) {
				$error_code = (string) ($xmlBody->RicevutaResponse->RicevutaResult->ErroreCod ?? '0');
				$error_desc = (string) ($xmlBody->RicevutaResponse->RicevutaResult->ErroreDes ?? 'ERR');
				$error_dets = (string) ($xmlBody->RicevutaResponse->RicevutaResult->ErroreDettaglio ?? 'Generico');
				// terminate the execution in case of errors or empty PDF
				throw new Exception(sprintf("[%s] Errore lettura ricevuta (%s):\n%s", $error_code, $error_desc, $error_dets), 500);
			}

			// prepare the PDF file information
			$pdf_name = implode('_', array_filter([$this->getFileName(), 'receipt', $receipt_date, $activeProfileId, time(), rand(100, 999)])) . '.pdf';
			$pdf_dest = $this->getDataMediaPath() . DIRECTORY_SEPARATOR . $pdf_name;
			$pdf_url  = $this->getDataMediaUrl() . $pdf_name;

			// check whether a receipt for the same day exists (and for the same active profile, if any)
			$old_pdf_path = $this->_callActionReturn('receiptExists', 'pdf_path', $scope, $data);

			// store the PDF bytes into a local file on disk
			$stored = JFile::write($pdf_dest, base64_decode((string) $xmlBody->RicevutaResponse->PDF));

			if (!$stored) {
				// terminate the process
				throw new Exception(sprintf('Impossibile scrivere il file PDF della ricevuta su disco: %s', $pdf_dest), 500);
			}

			if ($old_pdf_path) {
				// get rid of the previously downloaded receipt for the same dates (and active profile, if any)
				unlink($old_pdf_path);
			}

			if (VBOPlatformDetection::isWordPress()) {
				/**
				 * Trigger files mirroring operation
				 */
				VikBookingLoader::import('update.manager');
				VikBookingUpdateManager::triggerUploadBackup($pdf_dest);
			}

			// build HTML response string
			$html .= '<p class="successmade">Successo!</p>';
			$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
			$html .= '	<div class="vbo-params-wrap">';
			$html .= '		<div class="vbo-params-container">';
			$html .= '			<div class="vbo-params-block">';
			$html .= '				<div class="vbo-param-container">';
			$html .= '					<div class="vbo-param-label">PDF ricevuta</div>';
			$html .= '					<div class="vbo-param-setting"><a href="' . $pdf_url . '" target="_blank">' . basename($pdf_url) . '</a></div>';
			$html .= '				</div>';
			$html .= '			</div>';
			$html .= '		</div>';
			$html .= '	</div>';
			$html .= '</div>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// define a new report resource for the downloaded file
		$this->defineResourceFile([
			'summary' => sprintf(
				'%sRicevuta scaricata per la trasmissione con data %s.',
				($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
				$receipt_date
			),
			'url'  => $pdf_url,
			'path' => $pdf_dest,
		]);

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.dwnreceipt.' . ($error_cards ? 'error' : 'ok'),
				'title'   => $this->reportName . ' - Download ricevuta',
				'summary' => sprintf(
					'%sÈ stata scaricata la ricevuta per la trasmissione delle schedine in data %s.',
					($activeProfileId ? '(' . ucwords($activeProfileId) . ') ' : ''),
					date('Y-m-d', strtotime($receipt_date))
				),
				'label'   => 'Scarica PDF',
				'url'     => $pdf_url,
			];

			try {
				// store the notification record
				VBOFactory::getNotificationCenter()->store([$notification]);
			} catch (Exception $e) {
				// silently catch the error without doing anything
			}
		}

		return [
			'html'         => $html,
			'success'      => true,
			'receipt_path' => $pdf_dest,
			'receipt_url'  => $pdf_url,
			'receipt_date' => $receipt_date,
		];
	}

	/**
	 * Custom scoped action to generate a token for executing the requests.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function generateToken($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['user']) || empty($settings['pwd']) || empty($settings['wskey'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// prepare the response properties
		$html   = '';
		$token  = '';
		$issued = '';
		$expiry = '';

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <GenerateToken xmlns="AlloggiatiService">
      <Utente>' . $settings['user'] . '</Utente>
      <Password>' . $settings['pwd'] . '</Password>
      <WsKey>' . $settings['wskey'] . '</WsKey>
    </GenerateToken>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient();

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'GenerateToken', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->GenerateTokenResponse->result->esito)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = !strcasecmp((string) $xmlBody->GenerateTokenResponse->result->esito, 'false');

			if ($is_error || !($xmlBody->GenerateTokenResponse->GenerateTokenResult->token ?? '')) {
				$error_code = (string) ($xmlBody->GenerateTokenResponse->result->ErroreCod ?? '0');
				$error_desc = (string) ($xmlBody->GenerateTokenResponse->result->ErroreDes ?? 'ERR');
				$error_dets = (string) ($xmlBody->GenerateTokenResponse->result->ErroreDettaglio ?? 'Generico');
				// terminate the execution in case of errors or empty token
				throw new Exception(sprintf("[%s] Errore generazione token (%s):\n%s", $error_code, $error_desc, $error_dets), 500);
			}

			// set token and expiration date
			$token  = (string) $xmlBody->GenerateTokenResponse->GenerateTokenResult->token;
			$issued = (string) $xmlBody->GenerateTokenResponse->GenerateTokenResult->issued ?? '';
			$expiry = (string) $xmlBody->GenerateTokenResponse->GenerateTokenResult->expires ?? '';

			// build HTML response string
			$html .= '<p class="successmade">Successo!</p>';
			$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
			$html .= '	<div class="vbo-params-wrap">';
			$html .= '		<div class="vbo-params-container">';
			$html .= '			<div class="vbo-params-block">';

			foreach ($xmlBody->GenerateTokenResponse->GenerateTokenResult->children() as $node_name => $element) {
				$html .= '<div class="vbo-param-container">';
				$html .= '	<div class="vbo-param-label">' . $node_name . '</div>';
				$html .= '	<div class="vbo-param-setting">' . (string) $element . '</div>';
				$html .= '</div>';
			}

			$html .= '			</div>';
			$html .= '		</div>';
			$html .= '	</div>';
			$html .= '</div>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// update settings with the token details obtained
		$settings['token'] = $token;
		$settings['token_issue_dt'] = $issued;
		$settings['token_expiry_dt'] = $expiry;

		/**
		 * Since this report supports multiple profiles, settings should be saved
		 * on the active profile, and if not empty, saving will be successful.
		 * 
		 * @since 	1.18.0 (J) - 1.8.0 (WP)
		 */
		$this->saveSettings($settings, true, $this->getActiveProfile());

		return [
			'html'   => $html,
			'token'  => $token,
			'issued' => $issued,
			'expiry' => $expiry,
		];
	}

	/**
	 * Custom scoped action (hidden) to test if the authentication data is valid.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function authenticationTest($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!$settings || empty($settings['user']) || empty($settings['pwd']) || empty($settings['wskey'])) {
			throw new Exception(sprintf('[%s] error: missing settings.', __METHOD__), 500);
		}

		// prepare the response properties
		$html = '';
		$valid_token = false;

		// build the Soap request message
		$request = '<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <Authentication_Test xmlns="AlloggiatiService">
      <Utente>' . $settings['user'] . '</Utente>
      <token>' . ($settings['token'] ?? '') . '</token>
    </Authentication_Test>
  </soap12:Body>
</soap12:Envelope>';

		try {
			// get the SoapClient object from WSDL
			$ws_client = $this->getWebServiceClient();

			// do the request and get the Soap XML response message
			$response = $ws_client->__doRequest($request, $this->ws_location, 'Authentication_Test', SOAP_1_2);

			// parse the response message and get the body
			$xmlBody = $this->loadXmlSoap($response)->getSoapBody();

			if (!isset($xmlBody->Authentication_TestResponse->Authentication_TestResult->esito)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = !strcasecmp((string) $xmlBody->Authentication_TestResponse->Authentication_TestResult->esito, 'false');
			$valid_token = (bool) (!strcasecmp((string) $xmlBody->Authentication_TestResponse->Authentication_TestResult->esito, 'true'));

			if ($valid_token) {
				// build response string
				$html .= '<p class="successmade">Il token è valido.</p>';
			} else {
				// build response string values
				$html .= '<p class="' . ($is_error ? 'err' : 'warn') . '">';
				foreach ($xmlBody->Authentication_TestResponse->Authentication_TestResult->children() as $node_name => $element) {
					$html .= $node_name . ': ' . (string) $element . '<br/>';
				}
				$html .= '</p>';
			}
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		return [
			'html'  => $html,
			'valid' => $valid_token,
		];
	}

	/**
	 * Custom scoped action (hidden) to save a relation between an apartment and a room.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function saveApartmentRelation($scope = null, array $data = [])
	{
		$settings = $this->loadSettings();

		if (!strlen((string) $data['id_apt']) || empty($data['id_room'])) {
			throw new InvalidArgumentException('Missing apartment and room ID.', 400);
		}

		// update current relations, if any
		$relations = $settings['apartment_relations'] ?? [];

		// set apartment-room relation
		$relations[$data['id_room']] = [
			'id'   => $data['id_apt'],
			'name' => $data['apt_name'] ?? null,
		];

		// update settings
		$settings['apartment_relations'] = $relations;
		
		/**
		 * Since this report supports multiple profiles, settings should be saved
		 * on the active profile, and if not empty, saving will be successful.
		 * 
		 * @since 	1.18.0 (J) - 1.8.0 (WP)
		 */
		$this->saveSettings($settings, true, $this->getActiveProfile());

		// return the current relations
		return ['relations' => $settings['apartment_relations']];
	}

	/**
	 * Establishes a SOAP connection with the remote WSDL and returns the client.
	 * 
	 * @return 	SoapClient
	 * 
	 * @throws 	Exception
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	protected function getWebServiceClient()
	{
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
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
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
	 * 
	 * @since 	1.16.1 (J) - 1.6.1 (WP)
	 */
	protected function registerExportFileName()
	{
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$this->setExportCSVFileName($this->reportName . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.txt');
	}

	/**
	 * This method adds blank spaces to the string
	 * until the passed length of string is reached.
	 *
	 * @param 	string 		$val
	 * @param 	int 		$len
	 *
	 * @return 	string
	 */
	protected function valueFiller($val, $len)
	{
		$val = (string) $val;

		$len = empty($len) || (int) $len <= 0 ? $this->stringLength($val) : (int) $len;

		//clean up $val in case there is still a CR or LF
		$val = str_replace(array("\r\n", "\r", "\n"), '', $val);
		
		if ($this->stringLength($val) < $len) {
			while ($this->stringLength($val) < $len) {
				$val .= ' ';
			}
		} elseif ($this->stringLength($val) > $len) {
			$val = $this->subString($val, 0, $len);
		}

		return $val;
	}

	/**
	 * Ensures a proper calculation of a string length with multi-byte chars.
	 * 
	 * @param 	string 	$str 	The string to measure.
	 * 
	 * @return 	int
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	protected function stringLength(string $str)
	{
		if (function_exists('mb_strlen')) {
			return mb_strlen($str, 'UTF-8');
		}

		return strlen($str);
	}

	/**
	 * Ensures to obtain a proper sub-string with multi-byte chars.
	 * 
	 * @param 	string 	$str 		The string to measure.
	 * @param 	int 	$offset 	The string reading offset.
	 * @param 	?int 	$length 	Optional length of string.
	 * 
	 * @return 	string
	 * 
	 * @since 	1.18.0 (J) - 1.8.0 (WP)
	 */
	protected function subString(string $str, int $offset, ?int $length = null)
	{
		if (function_exists('mb_substr')) {
			return mb_substr($str, $offset, $length, 'UTF-8');
		}

		return substr($str, $offset, $length);
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
		$comprov_codes = array(
			'comuni' => array(
				0 => '-- Estero --'
			),
			'province' => array(
				0 => '-- Estero --'
			)
		);

		$csv = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Comuni.csv';
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

			if (!isset($comprov_codes['comuni'][$v[0]])) {
				$comprov_codes['comuni'][$v[0]] = [];
			}

			$comprov_codes['comuni'][$v[0]]['name'] = $v[1];
			$comprov_codes['comuni'][$v[0]]['province'] = $v[2];
			$comprov_codes['province'][$v[2]] = $v[2];
		}

		return $comprov_codes;
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

			$nazioni[$v[0]]['name'] = $v[1];
			$nazioni[$v[0]]['three_code'] = $v[2];
		}

		return $nazioni;
	}

	/**
	 * Parses the file Documenti.csv and returns an associative
	 * array with the code and name of the Documento.
	 * Every line of the CSV is composed of: Codice, Documento.
	 *
	 * @return 	array
	 */
	protected function loadDocumenti()
	{
		$documenti = [];

		$csv = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Documenti.csv';
		$rows = file($csv);
		foreach ($rows as $row) {
			if (empty($row)) {
				continue;
			}

			$v = explode(';', $row);
			if (count($v) != 2) {
				continue;
			}

			// trim values
			$v[0] = trim($v[0]);
			$v[1] = trim($v[1]);

			$documenti[$v[0]] = $v[1];
		}

		return $documenti;
	}

	/**
	 * Returns an array that contains both name and key of the comune
	 * selected, plus the associated province.
	 * 
	 * @deprecated 	1.5.0 there is no need to rely on this method.
	 *
	 * @return 		array
	 */
	protected function checkComune($combirth, $checked, $province)
	{
		$result = [];
		$first_found = '';

		if (empty($combirth)) {
			return $result;
		}

		foreach ($this->comuniProvince['comuni'] as $key => $value) {
			if (!isset($value['name'])) {
				continue;
			}
			if ($value['name'] == $combirth) {
				$result['found'] = true;
				$result['combirth'] = $value['name'];
				$result['province'] = substr($value['province'], 0, 2);
				$result['comval'] = $key;
				$result['similar'] = false;
				break;
			} elseif (strpos($value['name'], trim($combirth)) !== false && empty($first_found)) {
				$result['found'] = true;
				$result['combirth'] = $value['name'];
				$first_found = $key;
				$result['similar'] = true;
				$result['province'] = substr($value['province'], 0, 2);
			}
		}
		if (!$result['found']) {
			$result['combirth'] = '';
		} 

		if ($checked === true && strlen($province) > 0  && $result['found']) {
			$result['province'] = $province;
			if ($province == $value['province']) {
				$result['provinceok'] = true;
				$result['province'] = substr($province, 0, 2);
			} else {
				$result['provinceok'] = false;
			}
		}
		if ($result['similar'] && $result['found']) {
			$result['comval'] = $first_found;
		}

		return $result;
	}

	/**
	 * Returns the key of the state selected by the user.
	 * 
	 * @deprecated 	1.5.0 there is no need to rely on this method.
	 *
 	 * @return string
	 */
	protected function checkCountry($country)
	{
		$found = false;
		$staval = '';

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
	 * Sanitizes the "Comune" if value also contains also the province. Example "PRATO (PO)".
	 * 
	 * @deprecated 	1.5.0 there is no need to rely on this method.
	 * 
	 * @param 	string 	$combirth 	the current value to check.
	 *
	 * @return 	array 				empty array or comune/province info.
	 */
	protected function sanitizeComune($combirth)
	{
		if (empty($combirth)) {
			return [];
		}

		$result = [];

		if (strlen($combirth) > 2) {
			if (strpos($combirth, "(") !== false) {
				$comnas = explode("(", $combirth);
				$result['combirth'] = trim($comnas[0]);
				$result['province'] = $comnas[1];
				$result['province'] = str_replace(')', '', $result['province']);
			}
		} elseif(strlen($combirth) > 0) {
			$result['province'] = trim($combirth);
			$result['similar'] = true;
		}

		return $result; 
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
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
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
	 * 
	 * @since 	1.15.0 (J) - 1.5.0 (WP)
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
	 * Given a remote apartment ID and name, attempts to find a match among the
	 * VikBooking rooms or within the currently saved room relations.
	 * 
	 * @param 	?string 	$id_apt 	The remote apartment ID.
	 * @param 	?string 	$apt_name 	The remote apartment name.
	 * @param 	array 		$vbo_rooms 	The VikBooking rooms list.
	 * @param 	array 		$relations 	The currently saved apartment-room relations.
	 * 
	 * @return 	int
	 * 
	 * @since 	1.17.1 (J) - 1.7.1 (WP)
	 */
	private function findVboMatchingRoomId($id_apt, $apt_name, array $vbo_rooms, array $relations)
	{
		if (!strlen((string) $id_apt)) {
			return 0;
		}

		if ($relations) {
			// current relations get a higher priority
			foreach ($relations as $vbo_rid => $relation) {
				if ($relation && ($relation['id'] ?? 0) == $id_apt) {
					// relation found
					return $vbo_rid;
				}
			}
		}

		if ($apt_name && $vbo_rooms) {
			// attempt to find a matching name with no similarity
			foreach ($vbo_rooms as $vbo_room) {
				if (stripos($vbo_room['name'], $apt_name) !== false || stripos($apt_name, $vbo_room['name']) !== false) {
					// match found
					return $vbo_room['id'];
				}
			}
		}

		return 0;
	}
}
