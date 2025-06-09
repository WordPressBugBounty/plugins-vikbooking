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
 * VikBookingReport implementation for (Catalonia - Spain) Mossos d'Esquadra.
 * 
 * Property Managers from Catalonia are obliged to upload check-in information
 * in TXT format to Mossos d'Esquadra, not to "SES Hospedajes" like the rest of Spain.
 * 
 * @link 	https://registreviatgers.mossos.gencat.cat/mossos_hotels/AppJava/info.do?reqCode=listDocuments&docIdPare=2#reqCode=listDocuments&docIdPare=2
 * 
 * @since 	1.17.7 (J) - 1.7.7 (WP)
 */
class VikBookingReportEsMossosDesquadra extends VikBookingReport
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
	 * List of booking IDs affected by the export.
	 * 
	 * @var  	array
	 */
	protected $export_booking_ids = [];

	/**
	 * Associative list of RELACIÓN DE PARENTESCO
	 * 
	 * @var 	array
	 */
	protected $relacion_de_parentesco = [
		'AB' => 'Abuelo/a',
		'BA' => 'Bisabuelo/a',
		'BN' => 'Bisnieto/a',
		'CD' => 'Cuñado/a',
		'CY' => 'Cónyuge',
		'HJ' => 'Hijo/a',
		'HR' => 'Hermano/a',
		'NI' => 'Nieto/a',
		'PM' => 'Padre o madre',
		'SB' => 'Sobrino/a',
		'SG' => 'Suegro/a',
		'TI' => 'Tío/a',
		'YN' => 'Yerno o nuera',
		'TU' => 'Tutor/a',
		'OT' => 'Otro',
	];

	/**
	 * Associative list of SEXO
	 * 
	 * @var 	array
	 */
	protected $sexo = [
		'H' => 'Hombre',
		'M' => 'Mujer',
		'O' => 'Otro',
	];

	/**
	 * Associative list of TIPOS DE DOCUMENTO
	 * 
	 * @var 	array
	 */
	protected $tipos_de_documento = [
		'NIF' => 'NIF - Número de Identificación Fiscal',
		'NIE' => 'NIE - Número de Identidad de Extranjero',
		'PAS' => 'Número de pasaporte',
		'OTRO' => 'Otro',
	];

	/**
	 * Associative list of TIPOS DE ESTABLECIMIENTO
	 * 
	 * @var 	array
	 */
	protected $tipos_de_establecimiento = [
		'ALBERGUE' => 'Albergue',
		'APART' => 'Apartamento',
		'APARTHOTEL' => 'Apartahotel',
		'AP_RURAL' => 'Apartamento rural',
		'BALNEARIO' => 'Balneario',
		'CAMPING' => 'Camping',
		'CASA_HUESP' => 'Casa de huéspedes',
		'CASA_RURAL' => 'Casa rural',
		'HOSTAL' => 'Hostal',
		'HOTEL' => 'Hotel',
		'H_RURAL' => 'Hotel rural',
		'MOTEL' => 'Motel',
		'OFIC_VEHIC' => 'Oficina de alquiler de vehículos',
		'OTROS' => 'Otro',
		'PARADOR' => 'Parador de turismo',
		'PENSION' => 'Pensión',
		'RESIDENCIA' => 'Residencia',
	];

	/**
	 * Associative list of TIPOS DE PAGO
	 * 
	 * @var 	array
	 */
	protected $tipos_de_pago = [
		'EFECT' => 'Efectivo',
		'TARJT' => 'Tarjeta de crédito',
		'PLATF' => 'Plataforma de pago',
		'TRANS' => 'Transferencia',
		'MOVIL' => 'Pago por móvil',
		'TREG' => 'Tarjeta regalo',
		'DESTI' => 'Pago en destino',
		'OTRO' => 'Otros medios de pago',
	];

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = 'Mossos d\'Esquadra - Catalonia';
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
	 */
	public function getSettingFields()
	{
		return [
			'codigo' => [
				'type'  => 'text',
				'label' => 'Código del establecimiento',
				'help'  => 'Identificador del establecimiento facilitado por los Mossos d\'Esquadra y que consta de 9 o 10 caracteres.',
			],
			'nombre' => [
				'type'  => 'text',
				'label' => 'Nombre establecimiento',
				'help'  => 'Insertar el nombre del establecimiento.',
			],
			'sequence' => [
				'type'    => 'number',
				'label'   => 'Secuencia numérica transmisión',
				'help'    => 'Secuencia numérica para generar el siguiente archivo. El valor se incrementará automáticamente con cada generación del archivo.',
				'default' => 1,
			],
		];
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
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadMossosDesquadra();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download TXT</span></a>';

		// build the hidden values for the selection of various fields.
		$hidden_vals = '<div id="vbo-report-mossosdesquadra-hidden" style="display: none;">';

		// build params container HTML structure
		$hidden_vals .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$hidden_vals .= '	<div class="vbo-params-wrap">';
		$hidden_vals .= '		<div class="vbo-params-container">';
		$hidden_vals .= '			<div class="vbo-params-block vbo-params-block-noborder">';

		// tipos de pago
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-paytype" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Tipo de pago</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-paytype" onchange="vboReportChosenTipodepago(this);"><option value=""></option>';
		foreach ($this->tipos_de_pago as $code => $pago) {
			$hidden_vals .= '		<option value="' . $code . '">' . $pago . '</option>' . "\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// Nacionalidad
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-nazione" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Pais</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-nazione" onchange="vboReportChosenNacionalidad(this);"><option value=""></option>';
		foreach (VikBooking::getCountriesArray() as $code => $country) {
			$hidden_vals .= '		<option value="' . $code . '">' . $country['country_name'] . '</option>'."\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// tipos de documento
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-doctype" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Tipos de documento</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-documento" onchange="vboReportChosenDocumento(this);"><option value=""></option>';
		foreach ($this->tipos_de_documento as $code => $documento) {
			$hidden_vals .= '		<option value="' . $code . '">' . $documento . '</option>' . "\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// relacion de parentesco
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-parentesco" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Relacion de parentesco</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-parentesco" onchange="vboReportChosenParentesco(this);"><option value=""></option>';
		foreach ($this->relacion_de_parentesco as $code => $val) {
			$hidden_vals .= '		<option value="' . $code . '">' . $val . '</option>' . "\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// sexo
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-sesso" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Sexo</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-sesso" onchange="vboReportChosenSesso(this);"><option value=""></option>';
		foreach ($this->sexo as $code => $ses) {
			$hidden_vals .= '		<option value="' . $code . '">' . $ses . '</option>' . "\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// id number
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-docnum" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Número de documento</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-docnum" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// número de soporte del documento
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-docsoporte" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Número de soporte del documento</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-docsoporte" value="" />';
		$hidden_vals .= '			<span class="vbo-param-setting-comment">Obligatorio si el tipo de documento es NIF, NIE.</span>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// address
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-address" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Direccion</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-address" value="" />';
		$hidden_vals .= '			<span class="vbo-param-setting-comment">Calle, número, escalera, piso, puerta, y demás campos que indiquen la dirección.</span>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// codigo municipio
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-municipio" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Código de municipio</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<select id="choose-municipio" onchange="vboReportChosenMunicipio(this);"><option value=""></option>';
		foreach (VBOCheckinPaxfieldTypeSpainMunicipio::loadMunicipioCodes() as $code => $val) {
			$hidden_vals .= '		<option value="' . $code . '">' . $val . '</option>' . "\n";
		}
		$hidden_vals .= '			</select>';
		$hidden_vals .= '			<span class="vbo-param-setting-comment">Este campo irá codificado con los códigos de municipios del INE (5 dígitos). Obligatorio cuando el país es España.</span>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// city
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-city" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Nombre del municipio</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-city" value="" />';
		$hidden_vals .= '			<span class="vbo-param-setting-comment">Nombre del municipio, ciudad, estado, etc.. Obligatorio cuando el país no es España.</span>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// postal code
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-postalcode" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Código postal</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-postalcode" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// phone
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-phone" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Teléfono</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="tel" size="40" id="choose-phone" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// email
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-email" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Dirección de correo electrónico</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="email" size="40" id="choose-email" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// birth date
		$hidden_vals .= '	<div id="vbo-report-mossosdesquadra-dbirth" class="vbo-report-mossosdesquadra-selcont vbo-param-container" style="display: none;">';
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
			'label' => '<label for="fromdate">' . JText::translate('VBOREPORTSDATEFROM') . '</label>',
			'html' => '<input type="text" id="fromdate" name="fromdate" value="" class="vbo-report-datepicker vbo-report-datepicker-from" />' . $hidden_vals,
			'type' => 'calendar',
			'name' => 'fromdate'
		);
		array_push($this->reportFilters, $filter_opt);

		// To Date Filter
		$filter_opt = array(
			'label' => '<label for="todate">' . JText::translate('VBOREPORTSDATETO') . '</label>',
			'html' => '<input type="text" id="todate" name="todate" value="" class="vbo-report-datepicker vbo-report-datepicker-to" />',
			'type' => 'calendar',
			'name' => 'todate'
		);
		array_push($this->reportFilters, $filter_opt);

		// append button to save the data when creating manual values
		$filter_opt = array(
			'label' => '<label class="vbo-report-mossosdesquadra-manualsave" style="display: none;">Guests data</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-mossosdesquadra-manualsave" style="display: none;" onclick="vboMossosDesquadraSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		// datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$js = 'var reportActiveCell = null, reportObj = {};
		var vbo_mossosdesquadra_ajax_uri = "' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile) . '";
		var vbo_mossosdesquadra_save_icn = "' . VikBookingIcons::i('save') . '";
		var vbo_mossosdesquadra_saving_icn = "' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '";
		var vbo_mossosdesquadra_saved_icn = "' . VikBookingIcons::i('check-circle') . '";
		jQuery(function() {
			//prepare main filters
			jQuery(".vbo-report-datepicker:input").datepicker({
				maxDate: "+7 days",
				dateFormat: "'.$this->getDateFormat('jui').'",
				onSelect: vboReportCheckDates
			});
			'.(!empty($pfromdate) ? 'jQuery(".vbo-report-datepicker-from").datepicker("setDate", "'.$pfromdate.'");' : '').'
			'.(!empty($ptodate) ? 'jQuery(".vbo-report-datepicker-to").datepicker("setDate", "'.$ptodate.'");' : '').'
			//prepare filler helpers
			jQuery("#vbo-report-mossosdesquadra-hidden").children().detach().appendTo(".vbo-info-overlay-report");
			jQuery("#choose-paytype").select2({placeholder: "- Tipo de pago -", width: "200px"});
			jQuery("#choose-nazione").select2({placeholder: "- Seleccione un país -", width: "200px"});
			jQuery("#choose-documento").select2({placeholder: "- Seleccione un tipo de documento -", width: "200px"});
			jQuery("#choose-parentesco").select2({placeholder: "----", width: "200px"});
			jQuery("#choose-municipio").select2({placeholder: "----", width: "200px"});
			jQuery("#choose-sesso").select2({placeholder: "- Seleccione sexo -", width: "200px"});
			jQuery("#choose-dbirth").datepicker({
				maxDate: 0,
				dateFormat: "dd/mm/yy",
				changeMonth: true,
				changeYear: true,
				yearRange: "'.(date('Y') - 100).':'.date('Y').'"
			});
			//click events
			jQuery(".vbo-report-load-nazione, .vbo-report-load-nazione-stay, .vbo-report-load-cittadinanza , .vbo-report-load-countrystay").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-nazione").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-doctype").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-doctype").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-paytype").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-paytype").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-sesso").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-docnum").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-docnum").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenDocnum(document.getElementById(\'choose-docnum\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-docnum").focus();}, 500);
			});
			jQuery(".vbo-report-load-docsoporte").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-docsoporte").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenDocsoporte(document.getElementById(\'choose-docsoporte\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-docsoporte").focus();}, 500);
			});
			jQuery(".vbo-report-load-address").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-address").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenAddress(document.getElementById(\'choose-address\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-address").focus();}, 500);
			});
			jQuery(".vbo-report-load-parentesco").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-parentesco").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenParentesco(document.getElementById(\'choose-parentesco\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-parentesco").focus();}, 500);
			});
			jQuery(".vbo-report-load-municipio").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-municipio").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenMunicipio(document.getElementById(\'choose-municipio\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-municipio").focus();}, 500);
			});
			jQuery(".vbo-report-load-city").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-city").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenCity(document.getElementById(\'choose-city\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-city").focus();}, 500);
			});
			jQuery(".vbo-report-load-postalcode").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-postalcode").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenPostalcode(document.getElementById(\'choose-postalcode\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-postalcode").focus();}, 500);
			});
			jQuery(".vbo-report-load-phone").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-phone").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenPhone(document.getElementById(\'choose-phone\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-phone").focus();}, 500);
			});
			jQuery(".vbo-report-load-email").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-email").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenEmail(document.getElementById(\'choose-email\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-email").focus();}, 500);
			});
			jQuery(".vbo-report-load-dbirth").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-mossosdesquadra-selcont").hide();
				jQuery("#vbo-report-mossosdesquadra-dbirth").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenDbirth(document.getElementById(\'choose-dbirth\').value);\">Guardar</button>",
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
		function vboReportChosenTipodepago(pago) {
			var c_code = pago.value;
			var c_val = pago.options[pago.selectedIndex].text;

			// apply the selected value to all guests of this booking
			if (reportActiveCell !== null) {
				var nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(reportActiveCell).closest("tr"));
				if (isNaN(nowindex) || parseInt(nowindex) < 0) {
					alert("Error, cannot find element to update.");
					return false;
				}
				var rep_act_cell = jQuery(reportActiveCell);
				var rep_guest_bid = rep_act_cell.closest("tr").find("a[data-bid]").attr("data-bid");
				jQuery(\'[data-field="paytype"][data-fieldbid="\' + rep_guest_bid + \'"]\').each(function(k, v) {
					let nowcell = jQuery(v);
					nowindex = jQuery(".vbo-reports-output table tbody tr").index(jQuery(nowcell).closest("tr"));
					nowcell.addClass("vbo-report-load-elem-filled").find("span").text(c_val);
					if (!reportObj.hasOwnProperty(nowindex)) {
						reportObj[nowindex] = {
							bid: rep_guest_bid,
							bid_index: jQuery(".vbo-reports-output table tbody tr").index(jQuery("a[data-bid=\"" + rep_guest_bid + "\"]").first().closest("tr"))
						};
					}
					reportObj[nowindex]["paytype"] = c_code;
				});
			}

			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-paytype").val("").select2("data", null, false);
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		function vboReportChosenNacionalidad(naz) {
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
					} else if (jQuery(reportActiveCell).hasClass("vbo-report-load-countrystay")) {
						reportObj[nowindex]["country_s"] = c_code;
					} else {
						reportObj[nowindex]["country_c"] = c_code;
					}
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-nazione").val("").select2("data", null, false);
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
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
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		function vboReportChosenParentesco(parentesco) {
			var c_code = parentesco.value;
			var c_val = parentesco.options[parentesco.selectedIndex].text;
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
					reportObj[nowindex]["parentesco"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-parentesco").val("").select2("data", null, false);
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
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
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
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
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		function vboReportChosenDocsoporte(val) {
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
					reportObj[nowindex]["docsoporte"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-docsoporte").val("");
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		function vboReportChosenAddress(val) {
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
					reportObj[nowindex]["address"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-address").val("");
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		function vboReportChosenMunicipio(municipio) {
			var c_code = municipio.value;
			var c_val = municipio.options[municipio.selectedIndex].text;
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
					reportObj[nowindex]["municipio"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-municipio").val("").select2("data", null, false);
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
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
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		function vboReportChosenPostalcode(val) {
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
					reportObj[nowindex]["postalcode"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-postalcode").val("");
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		function vboReportChosenPhone(val) {
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
					reportObj[nowindex]["phone"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-phone").val("");
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		function vboReportChosenEmail(val) {
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
					reportObj[nowindex]["email"] = c_code;
				}
			}
			reportActiveCell = null;
			vboHideOverlay();
			jQuery("#choose-email").val("");
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
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
			jQuery(".vbo-report-mossosdesquadra-manualsave").show();
		}
		//download function
		function vboDownloadMossosDesquadra(type, report_type) {
			if (!confirm("¿Estás seguro de que quieres continuar?")) {
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
		function vboMossosDesquadraSaveData() {
			jQuery("button.vbo-report-mossosdesquadra-manualsave").find("i").attr("class", vbo_mossosdesquadra_saving_icn);
			VBOCore.doAjax(
				vbo_mossosdesquadra_ajax_uri,
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
					jQuery("button.vbo-report-mossosdesquadra-manualsave").addClass("btn-success").find("i").attr("class", vbo_mossosdesquadra_saved_icn);
				},
				function(error) {
					alert(error.responseText);
					jQuery("button.vbo-report-mossosdesquadra-manualsave").removeClass("btn-success").find("i").attr("class", vbo_mossosdesquadra_save_icn);
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

		// query to obtain the records (all check-ins or reservations created within the dates filter)
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
			->where($this->dbo->qn('o.checkin') . ' >= ' . $from_ts)
			->where($this->dbo->qn('o.checkin') . ' <= ' . $to_ts)
			->order($this->dbo->qn('o.checkin') . ' ASC')
			->order($this->dbo->qn('o.id') . ' ASC')
			->order($this->dbo->qn('or.id') . ' ASC');

		$this->dbo->setQuery($q);
		$records = $this->dbo->loadAssocList();

		if (!$records) {
			$this->setError(JText::translate('VBOREPORTSERRNORESERV'));
			$this->setError('No hay reservas con los filtros seleccionados.');
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
			// first name
			array(
				'key' => 'first_name',
				'label' => JText::translate('VBTRAVELERNAME'),
			),
			// last name
			array(
				'key' => 'last_name',
				'label' => JText::translate('VBTRAVELERLNAME'),
			),
			// reservation date
			array(
				'key' => 'resdate',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPCHOOSEBUSYORDATE'),
				'ignore_view' => 1,
			),
			// check-in date
			array(
				'key' => 'checkindate',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPICKUPAT'),
				'ignore_view' => 1,
			),
			// check-in time
			array(
				'key' => 'checkintime',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Check-in time',
				'ignore_view' => 1,
			),
			// check-out date
			array(
				'key' => 'checkoutdate',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBRELEASEAT'),
				'ignore_view' => 1,
			),
			// check-out time
			array(
				'key' => 'checkouttime',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Check-out time',
				'ignore_view' => 1,
			),
			// number of guests
			array(
				'key' => 'numguests',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPVIEWORDERSPEOPLE'),
				'ignore_view' => 1,
			),
			// sexo
			array(
				'key' => 'gender',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERGENDER'),
			),
			// id type
			array(
				'key' => 'doctype',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCTYPE'),
			),
			// id number
			array(
				'key' => 'docnum',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERDOCNUM'),
			),
			// número de soporte del documento
			array(
				'key' => 'docsoporte',
				'attr' => array(
					'class="center"'
				),
				'label' => 'Número de soporte',
				'tip' => 'Número de soporte del documento. Obligatorio si el tipo de documento es NIF, NIE.',
			),
			// birth date
			array(
				'key' => 'date_birth',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERBDATE'),
			),
			// parentesco
			array(
				'key' => 'parentesco',
				'label' => 'Parentesco',
				'attr' => array(
					'class="center"'
				),
				'tip' => 'Si alguna de las personas es menor de edad, al menos una de las personas mayores de edad ha de tener informada su relación de parentesco con esta persona menor de edad.',
			),
			// address
			array(
				'key' => 'address',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('ORDER_ADDRESS'),
			),
			// codigo municipio
			array(
				'key' => 'municipio',
				'label' => 'Municipio',
				'attr' => array(
					'class="center"'
				),
				'tip' => 'Código de municipio del INE (5 dígitos). Obligatorio cuando el país es España.',
			),
			// city
			array(
				'key' => 'city',
				'label' => JText::translate('ORDER_CITY'),
			),
			// postal code
			array(
				'key' => 'postalcode',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('ORDER_ZIP'),
			),
			// country of stay
			array(
				'key' => 'country_s',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('ORDER_STATE'),
			),
			// citizenship
			array(
				'key' => 'country_c',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBCUSTOMERNATION'),
			),
			// phone
			array(
				'key' => 'phone',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('ORDER_PHONE'),
			),
			// email
			array(
				'key' => 'email',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('ORDER_EMAIL'),
			),
			// payment type
			array(
				'key' => 'paytype',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBLIBPAYNAME'),
			),
			// check-in
			array(
				'key' => 'checkin',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBPICKUPAT'),
			),
			// check-out
			array(
				'key' => 'checkout',
				'attr' => array(
					'class="center"'
				),
				'label' => JText::translate('VBRELEASEAT'),
			),
			// id booking
			array(
				'key' => 'idbooking',
				'attr' => array(
					'class="center"'
				),
				'label' => 'ID',
			),
		);

		// codigo municipio list
		$municipio_codes_list = VBOCheckinPaxfieldTypeSpainMunicipio::loadMunicipioCodes();

		// codigo municipio 6-digit list
		$municipio_sixdigit_codes_list = VBOCheckinPaxfieldTypeSpainMunicipio::loadMunicipioSixDigitCodes();

		// loop over the bookings to build the rows of the report
		$from_info = getdate($from_ts);
		foreach ($bookings as $gbook) {
			// count the total number of guests for all rooms of this booking
			$tot_booking_guests = 0;
			$tot_booking_adults = 0;
			$tot_booking_children = 0;
			$room_guests = [];
			foreach ($gbook as $rbook) {
				$tot_booking_guests += ($rbook['adults'] + $rbook['children']);
				$tot_booking_adults += $rbook['adults'];
				$tot_booking_children += $rbook['children'];
				$room_guests[] = ($rbook['adults'] + $rbook['children']);
			}

			// make sure to decode the current pax data
			if (!empty($gbook[0]['pax_data'])) {
				$gbook[0]['pax_data'] = (array) json_decode($gbook[0]['pax_data'], true);
			}

			// push a copy of the booking for each guest (adults and children)
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

				// name
				$nome = !empty($guests['t_first_name']) ? $guests['t_first_name'] : $guests['first_name'];
				$pax_nome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'first_name');
				$nome = !empty($pax_nome) ? $pax_nome : $nome;
				array_push($insert_row, array(
					'key' => 'first_name',
					'value' => $nome,
				));

				// last name
				$cognome = !empty($guests['t_last_name']) ? $guests['t_last_name'] : $guests['last_name'];
				$pax_cognome = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'last_name');
				$cognome = !empty($pax_cognome) ? $pax_cognome : $cognome;
				array_push($insert_row, array(
					'key' => 'last_name',
					'value' => $cognome,
				));

				// reservation date
				array_push($insert_row, array(
					'key' => 'resdate',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('Ymd', $val);
					},
					'value' => $guests['ts'],
					'ignore_view' => 1,
				));

				// check-in date
				array_push($insert_row, array(
					'key' => 'checkindate',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('Ymd', $val);
					},
					'value' => $guests['checkin'],
					'ignore_view' => 1,
				));

				// check-in time
				array_push($insert_row, array(
					'key' => 'checkintime',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('Hi', $val);
					},
					'value' => $guests['checkin'],
					'ignore_view' => 1,
				));

				// check-out date
				array_push($insert_row, array(
					'key' => 'checkoutdate',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('Ymd', $val);
					},
					'value' => $guests['checkout'],
					'ignore_view' => 1,
				));

				// check-out time
				array_push($insert_row, array(
					'key' => 'checkouttime',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('Hi', $val);
					},
					'value' => $guests['checkout'],
					'ignore_view' => 1,
				));

				// number of guests
				array_push($insert_row, array(
					'key' => 'numguests',
					'attr' => array(
						'class="center"'
					),
					'value' => $tot_booking_guests,
					'ignore_view' => 1,
				));

				// Sexo
				$gender = !empty($guests['gender']) && $guest_ind < 2 ? strtoupper($guests['gender']) : '';
				$pax_gender = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'gender');
				$gender = !empty($pax_gender) ? $pax_gender : $gender;

				/**
				 * We make sure the gender will be compatible with both back-end and front-end
				 * check-in/registration data collection driver and processes.
				 */
				if (is_numeric($gender)) {
					$gender = (int) $gender;
				} elseif (!strcasecmp($gender, 'H')) {
					$gender = 1;
				} elseif (!strcasecmp($gender, 'F') || !strcasecmp($gender, 'M')) {
					$gender = 2;
				} elseif (!strcasecmp($gender, 'O')) {
					$gender = -1;
				}

				// normalize value to hospedajes codes
				switch ($gender) {
					case 2:
						// mujer
						$spain_gender = 'M';
						break;

					case 1:
						// hombre
						$spain_gender = 'H';
						break;
					
					default:
						// otro
						$spain_gender = 'O';
						break;
				}

				$sexo_elem_class = '';
				if (empty($gender)) {
					// optional selection style
					$sexo_elem_class = ' vbo-report-load-sesso vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$sexo_elem_class = ' vbo-report-load-sesso vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'gender',
					'attr' => array(
						'class="center' . $sexo_elem_class . '"'
					),
					'callback' => function($val) {
						return $this->sexo[$val] ?? 'O';
					},
					'export_callback' => function($val) {
						if ($val == 'H') {
							// for "Hombre" return "M" (Male)
							return 'M';
						}
						if ($val == 'M') {
							// for "Mujer" return "F" (Female)
							return 'F';
						}
						// return "O" (Other) by default
						return 'O';
					},
					'value' => $spain_gender,
				));

				// document type
				$pax_doctype = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'doctype');

				$doctype = $pax_doctype ?: '';

				if (strlen((string) $doctype) === 1) {
					// try to normalize the doc-type value from pre-checkin
					switch ($doctype) {
						case 'D':
						case 'C':
						case 'I':
							$doctype = 'NIF';
							break;

						case 'N':
						case 'X':
							$doctype = 'NIE';
							break;

						case 'P':
							$doctype = 'PAS';
							break;
						
						default:
							$doctype = $doctype;
							break;
					}
				}

				$doctype_elem_class = '';
				if (empty($doctype)) {
					// optional selection style
					$doctype_elem_class = ' vbo-report-load-doctype vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$doctype_elem_class = ' vbo-report-load-doctype vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'doctype',
					'attr' => array(
						'class="center' . $doctype_elem_class . '"'
					),
					'callback' => function($val) {
						return empty($val) ? '?' : $val;
					},
					'export_callback' => function($val) {
						if (empty($val)) {
							// other documents
							return 'O';
						}
						if ($val == 'NIF' || $val == 'DNI' || $val == 'TIE') {
							// NIF / DNI / TIE
							return 'D';
						}
						if ($val == 'NIE') {
							// NIE
							return 'N';
						}
						if ($val == 'PAS') {
							// PAS
							return 'P';
						}
						if ($val == 'OTRO') {
							// OTRO
							return 'O';
						}
						return $val;
					},
					'value' => $doctype,
				));

				// document number
				$pax_docnum = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'docnum');

				$docnum = $pax_docnum ?: '';

				$docnum_elem_class = '';
				if (empty($docnum)) {
					// optional selection style
					$docnum_elem_class = ' vbo-report-load-docnum vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$docnum_elem_class = ' vbo-report-load-docnum vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'docnum',
					'attr' => array(
						'class="center' . $docnum_elem_class . '"'
					),
					'callback' => function($val) {
						return empty($val) ? '?' : $val;
					},
					'no_export_callback' => 1,
					'value' => trim($docnum),
				));

				// número de soporte del documento
				$pax_docsoporte = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'docsoporte');

				$docsoporte = $pax_docsoporte ?: '';

				$docsoporte_elem_class = '';
				if (empty($docsoporte)) {
					// optional selection style
					$docsoporte_elem_class = ' vbo-report-load-docsoporte vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$docsoporte_elem_class = ' vbo-report-load-docsoporte vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'docsoporte',
					'attr' => array(
						'class="center' . $docsoporte_elem_class . '"'
					),
					'callback' => function($val) {
						return empty($val) ? '?' : $val;
					},
					'no_export_callback' => 1,
					'value' => $docsoporte,
				));

				// birth date (attempt to convert it into a timestamp to support any date format)
				$dbirth = !empty($guests['bdate']) && $guest_ind < 2 ? VikBooking::getDateTimestamp($guests['bdate'], 0, 0) : '';
				$pax_dbirth = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'date_birth');
				$dbirth = $pax_dbirth ?: $dbirth;
				$dbirth = !is_numeric($dbirth) ? VikBooking::getDateTimestamp($dbirth) : $dbirth;

				$dbirth_elem_class = '';
				if (empty($dbirth)) {
					// optional selection style
					$dbirth_elem_class = ' vbo-report-load-dbirth vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$dbirth_elem_class = ' vbo-report-load-dbirth vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'date_birth',
					'attr' => array(
						'class="center' . $dbirth_elem_class . '"'
					),
					'callback' => function($val) {
						if (!empty($val) && is_numeric($val)) {
							return date('Ymd', $val);
						}
						return $val ?: '?';
					},
					'export_callback' => function($val) {
						if (!empty($val) && is_numeric($val)) {
							return date('Ymd', $val);
						}
						if (!empty($val) && strlen($val) > 8) {
							return date('Ymd', strtotime($val));
						}
						return $val ?: '?';
					},
					'value' => $dbirth,
				));

				// parentesco
				$pax_parentesco = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'parentesco');

				$parentesco = $pax_parentesco ?: '';

				$parentesco_elem_class = '';
				if (empty($parentesco)) {
					// optional selection style
					$parentesco_elem_class = ' vbo-report-load-parentesco vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$parentesco_elem_class = ' vbo-report-load-parentesco vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'parentesco',
					'attr' => array(
						'class="center' . $parentesco_elem_class . '"'
					),
					'callback' => function($val) use ($tot_booking_children) {
						if (!$tot_booking_children) {
							return '';
						}
						return empty($val) ? '?' : ($this->relacion_de_parentesco[$val] ?? $val);
					},
					'no_export_callback' => 1,
					'value' => $parentesco,
				));

				// address
				$pax_address = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'address');

				$address = $pax_address ?: '';

				$address_elem_class = '';
				if (empty($address)) {
					// optional selection style
					$address_elem_class = ' vbo-report-load-address vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$address_elem_class = ' vbo-report-load-address vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'address',
					'attr' => array(
						'class="center' . $address_elem_class . '"'
					),
					'callback' => function($val) {
						return empty($val) ? '?' : $val;
					},
					'no_export_callback' => 1,
					'value' => $address,
				));

				// codigo municipio
				$pax_municipio = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'municipio');

				$municipio = $pax_municipio ?: '';

				$municipio_elem_class = '';
				if (empty($municipio)) {
					// optional selection style
					$municipio_elem_class = ' vbo-report-load-municipio vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$municipio_elem_class = ' vbo-report-load-municipio vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'municipio',
					'attr' => array(
						'class="center' . $municipio_elem_class . '"'
					),
					'callback' => function($val) use ($municipio_codes_list) {
						return empty($val) ? '?' : ($municipio_codes_list[$val] ?? $val);
					},
					'export_callback' => function($val) use ($municipio_codes_list, $municipio_sixdigit_codes_list) {
						// the 6-digit municipality code is required for export, so we try to map it
						if (strlen((string) $val) === 6 || !isset($municipio_codes_list[$val])) {
							return $val;
						}
						if (strlen((string) $val) === 5) {
							// must be the 5-digit municipality code from SES Hospedajes, try to map it with the 6 digit
							$municipio_name = $municipio_codes_list[$val];
							foreach ($municipio_sixdigit_codes_list as $sixd_code => $sixd_municipio_name) {
								if (!strcasecmp($municipio_name, $sixd_municipio_name)) {
									return $sixd_code;
								}
								if (!strcasecmp(preg_replace('/[^a-z]/i', '', $municipio_name), preg_replace('/[^a-z]/i', '', $sixd_municipio_name))) {
									return $sixd_code;
								}
							}
						}
						return $val;
					},
					'value' => $municipio,
				));

				// city
				$pax_city = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'city');

				$city = $pax_city ?: '';

				$city_elem_class = '';
				if (empty($city)) {
					// optional selection style
					$city_elem_class = ' vbo-report-load-city vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$city_elem_class = ' vbo-report-load-city vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'city',
					'attr' => array(
						'class="center' . $city_elem_class . '"'
					),
					'callback' => function($val) {
						return empty($val) ? '?' : $val;
					},
					'no_export_callback' => 1,
					'value' => $city,
				));

				// postal code
				$pax_postalcode = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'postalcode');

				$postalcode = $pax_postalcode ?: '';

				$postalcode_elem_class = '';
				if (empty($postalcode)) {
					// optional selection style
					$postalcode_elem_class = ' vbo-report-load-postalcode vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$postalcode_elem_class = ' vbo-report-load-postalcode vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'postalcode',
					'attr' => array(
						'class="center' . $postalcode_elem_class . '"'
					),
					'callback' => function($val) {
						return empty($val) ? '?' : $val;
					},
					'no_export_callback' => 1,
					'value' => $postalcode,
				));

				// country of stay
				$pax_country_s = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_s');

				// check country field from pre-checkin
				$pax_citizen = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country');

				$countrystay = !empty($guests['country']) ? $guests['country'] : '';
				$countrystay = $pax_citizen ?: $countrystay;
				$countrystay = $pax_country_s ?: $countrystay;

				$country_elem_class = '';
				if (empty($countrystay)) {
					// optional selection style
					$country_elem_class = ' vbo-report-load-countrystay vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$country_elem_class = ' vbo-report-load-countrystay vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'country_s',
					'attr' => array(
						'class="center' . $country_elem_class . '"'
					),
					'callback' => function($val) {
						return $val ?: '---';
					},
					'no_export_callback' => 1,
					'value' => $countrystay,
				));

				// citizenship
				$pax_country_c = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country_c');

				// check country field from pre-checkin
				$pax_citizen = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'country');

				$citizen = !empty($guests['country']) ? $guests['country'] : '';
				$citizen = $pax_citizen ?: $citizen;
				$citizen = $pax_country_c ?: $citizen;

				$country_elem_class = '';
				if (empty($citizen)) {
					// optional selection style
					$country_elem_class = ' vbo-report-load-cittadinanza vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$country_elem_class = ' vbo-report-load-cittadinanza vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'country_c',
					'attr' => array(
						'class="center' . $country_elem_class . '"'
					),
					'callback' => function($val) {
						return $val ?: '---';
					},
					'no_export_callback' => 1,
					'value' => $citizen,
				));

				// direccion (phone number or email address from pre-checkin fields)
				$pax_direccion = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'direccion');

				$direccion_phone = preg_match("/^[0-9\+\s]+$/", (string) $pax_direccion) ? $pax_direccion : '';

				// phone number
				$pax_phone = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'phone');

				$phone = $direccion_phone;
				$phone = empty($phone) && !empty($guests['phone']) ? $guests['phone'] : $phone;
				$phone = $pax_phone ?: $phone;

				$phone_elem_class = '';
				if (empty($phone)) {
					// optional selection style
					$phone_elem_class = ' vbo-report-load-phone vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$phone_elem_class = ' vbo-report-load-phone vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'phone',
					'attr' => array(
						'class="center' . $phone_elem_class . '"'
					),
					'callback' => function($val) {
						return $val ?: '---';
					},
					'export_callback' => function($val) {
						if (substr((string) $val, 0, 1) == '+') {
							// phone number should not include the + symbol
							$val = substr($val, 1);
						}
						// maximum allowed length is 20 chars
						return substr((string) $val, 0, 20);
					},
					'value' => $phone,
				));

				// direccion (phone number or email address from pre-checkin fields)
				$pax_direccion = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'direccion');

				$direccion_email = preg_match("/^.+@.+\..+$/", (string) $pax_direccion) ? $pax_direccion : '';

				// email
				$pax_email = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'email');

				$email = $direccion_email;
				$email = empty($email) && !empty($guests['custmail']) ? $guests['custmail'] : $email;
				$email = $pax_email ?: $email;

				$email_elem_class = '';
				if (empty($email)) {
					// optional selection style
					$email_elem_class = ' vbo-report-load-email vbo-report-load-field vbo-report-load-field-optional';
				} else {
					// rectify selection style
					$email_elem_class = ' vbo-report-load-email vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'email',
					'attr' => array(
						'class="center' . $email_elem_class . '"'
					),
					'callback' => function($val) {
						return $val ?: '---';
					},
					'no_export_callback' => 1,
					'value' => $email,
				));

				// payment type
				$pax_paytype = $this->getGuestPaxDataValue($guests['pax_data'], $room_guests, $guest_ind, 'paytype') ?: 'TARJT';

				$paytype_elem_class = '';
				if ($guest_room_ind < 2 && empty($pax_paytype)) {
					// mandatory selection style
					$paytype_elem_class = ' vbo-report-load-paytype vbo-report-load-field';
				} elseif (empty($pax_paytype)) {
					// optional selection style
					$paytype_elem_class = ' vbo-report-load-paytype vbo-report-load-field vbo-report-load-field-optional';
				} elseif (!empty($pax_paytype)) {
					// rectify selection style
					$paytype_elem_class = ' vbo-report-load-paytype vbo-report-load-field vbo-report-load-elem-filled';
				}

				array_push($insert_row, array(
					'key' => 'paytype',
					'attr' => array(
						'class="center' . $paytype_elem_class . '"',
						'data-field="paytype"',
						'data-fieldbid="' . $guests['id'] . '"',
					),
					'callback' => function($val) {
						return $this->tipos_de_pago[$val] ?? $val;
					},
					'no_export_callback' => 1,
					'value' => $pax_paytype,
				));

				// check-in
				array_push($insert_row, array(
					'key' => 'checkin',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('Y-m-d\TH:i:s', $val);
					},
					'value' => $guests['checkin'],
				));

				// check-out
				array_push($insert_row, array(
					'key' => 'checkout',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						return date('Y-m-d\TH:i:s', $val);
					},
					'value' => $guests['checkout'],
				));

				// id booking
				array_push($insert_row, array(
					'key' => 'idbooking',
					'attr' => array(
						'class="center"'
					),
					'callback' => function($val) {
						// make sure to keep the data-bid attribute as it's used by JS to identify the booking ID
						return '<a data-bid="' . $val . '" href="index.php?option=com_vikbooking&task=editorder&cid[]=' . $val . '" target="_blank"><i class="' . VikBookingIcons::i('external-link') . '"></i> ' . $val . '</a>';
					},
					'no_export_callback' => 1,
					'value' => $guests['id'],
				));

				// push fields in the rows array as a new row
				array_push($this->rows, $insert_row);

				// increment guest index
				$guest_ind++;
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

	/**
	 * Generates the authority file, then sends it to output for download.
	 * In case of errors, the process is not terminated (exit) to let the View display the
	 * error message(s). The export type argument can eventually determine an action to run.
	 *
	 * @param 	string 	$export_type 	Differentiates the type of export requested.
	 *
	 * @return 	void|bool 				Void in case of script termination, boolean otherwise.
	 */
	public function customExport($export_type = null)
	{
		// build the TXT file
		$txt = $this->buildTXTFile();

		// proceed with the regular export function (write on file through cron or download file through web)

		if (!$txt) {
			// abort
			return false;
		}

		// update history for all bookings affected before exporting
		foreach ($this->export_booking_ids as $bid) {
			VikBooking::getBookingHistoryInstance($bid)->store('RP', $this->reportName . ' - Export');
		}

		// custom export method supports a custom export handler, if previously set.
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, $txt);
			fclose($fp);

			// update sequence for the next download
			$this->increaseTransmissionSequence();

			// return true as data was written
			return true;
		}

		// force text file download in case of regular export
		header("Content-type: text/plain");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="' . $this->getExportCSVFileName() . '"');
		echo $txt;

		// update sequence for the next download
		$this->increaseTransmissionSequence();

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
	 * Builds the TXT file for export.
	 * 
	 * @return 	string 	Empty string in case of errors, TXT otherwise.
	 */
	protected function buildTXTFile()
	{
		if (!$this->getReportData()) {
			return '';
		}

		// load report settings
		$settings = $this->loadSettings();

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// access manually filled values, if any
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? (array) json_decode($pfiller, true) : [];

		// define the lines separator character (carriage return + new line feed)
		$lines_separator = "\r\n";

		// define the data separator character (pipe)
		$data_separator = '|';

		// start building the text file content
		$file_content = '';

		// add first line (LÍNEA AGRUPACIÓN) to text file content
		// use 0 for the line identifier, then the number of establishments, which is 1 by default
		$file_content .= implode($data_separator, [0, 1]) . $lines_separator;

		// add second line (LÍNEA ESTABLECIMIENTO) to text file content
		$file_content .= implode($data_separator, [
			1,
			($settings['codigo'] ?? ''),
			substr(($settings['nombre'] ?? ''), 0, 40),
			date('Ymd'),
			date('Hi'),
			count($this->rows),
			'V24',
		]) . $lines_separator;

		// group all rows by booking id
		$booking_rows = [];
		foreach ($this->rows as $ind => $row) {
			$bid = 0;
			foreach ($row as $row_ind => $field) {
				if ($field['key'] == 'idbooking') {
					// set booking ID
					$bid = (int) $field['value'];

					if (!isset($booking_rows[$bid])) {
						// start booking rows container
						$booking_rows[$bid] = [];
					}

					// check for manual report value
					if (strlen((string) ($pfiller[$ind][$field['key']] ?? ''))) {
						$row[$row_ind]['value'] = $pfiller[$ind][$field['key']];
					}
					
					// break this one loop
					break;
				}
			}
			// push booking row
			$booking_rows[$bid][] = $row;
		}

		// scan all booking rows
		foreach ($booking_rows as $bid => $rows) {
			// scan all guest rows for this reservation to build the various <persona> nodes
			foreach ($rows as $row) {
				// get the associative list of guest data from the current row
				$row_data = $this->getAssocRowData($row);

				// build guest line contents (Tipo registro = 2)
				$guest_line = [2];

				// tell whether the guest is Spanish
				$is_spanish = !empty($row_data['doctype']) && (!strcasecmp($row_data['doctype'], 'NIF') || !strcasecmp($row_data['doctype'], 'D'));

				// Número de documento español
				$guest_line[] = $is_spanish ? $row_data['docnum'] : '';

				// Número de documento estranjero
				$guest_line[] = !$is_spanish ? $row_data['docnum'] : '';

				// Tipo de documento identificador
				$guest_line[] = $row_data['doctype'];

				// document issue date is not mandatory, but we need to fill the empty value
				$guest_line[] = '';

				// last name parts
				$last_name_parts = explode(' ', (string) $row_data['last_name']);
				if (count($last_name_parts) === 1) {
					$last_name_one = $last_name_parts[0];
					$last_name_two = $last_name_parts[0];
				} else {
					$last_name_two = $last_name_parts[count($last_name_parts) - 1];
					unset($last_name_parts[count($last_name_parts) - 1]);
					$last_name_one = implode(' ', $last_name_parts);
				}

				// Primer apellido (main/first last name)
				$guest_line[] = strtoupper($this->transliterateToAscii($last_name_one));

				// Segundo apellido (second last name)
				if ($last_name_one != $last_name_two || $is_spanish) {
					$guest_line[] = strtoupper($this->transliterateToAscii($last_name_two));
				} else {
					$guest_line[] = '';
				}

				// Nombre (first name)
				$guest_line[] = strtoupper($this->transliterateToAscii((string) $row_data['first_name']));

				// Sexo
				$guest_line[] = $row_data['gender'];

				// Fecha Nacimiento
				$guest_line[] = $row_data['date_birth'];

				// Pais nacionalidad
				$guest_line[] = $row_data['country_c'] ?: $row_data['country_s'];

				// Fecha entrada
				$guest_line[] = $row_data['checkindate'];

				// Hora entrada
				$guest_line[] = $row_data['checkintime'];

				// Fecha salida
				$guest_line[] = $row_data['checkoutdate'];

				// Hora salida
				$guest_line[] = $row_data['checkouttime'];

				// Fecha contracto
				$guest_line[] = $row_data['resdate'];

				// Tipo contracte (C = Contrato en curso, R = Reserva)
				$guest_line[] = 'R';

				// Número contracto
				$guest_line[] = $row_data['idbooking'];

				// Número viajeros
				$guest_line[] = $row_data['numguests'];

				// Número habitaciones
				$guest_line[] = 1;

				// El hotel dispone de internet (S = Si, N = No)
				$guest_line[] = 'S';

				// Tipo pago
				$guest_line[] = $row_data['paytype'];

				// Teléfono
				$guest_line[] = $row_data['phone'];

				// Relación de parentesco
				$guest_line[] = $row_data['parentesco'];

				// Email
				$guest_line[] = $row_data['email'];

				// Número soporte documento identificativo
				$guest_line[] = $row_data['docsoporte'];

				// Dirección postal
				$guest_line[] = substr((string) $row_data['address'], 0, 100);

				// Provincia postal
				$guest_line[] = substr((string) $row_data['municipio'], 0, 2);

				// Municipio postal
				$guest_line[] = $row_data['municipio'];

				// Localitat postal
				$guest_line[] = '';

				// Pais postal
				$guest_line[] = $row_data['country_s'] ?: $row_data['country_c'];

				// Código postal
				$guest_line[] = $row_data['postalcode'];

				// last field also needs to be separated by a pipe character so we push an empty string
				$guest_line[] = '';

				// add guest line (LÍNEA VIAJERO) to text file content
				$file_content .= implode($data_separator, $guest_line) . $lines_separator;
			}
		}

		// set pool of booking IDs to update their history
		$this->export_booking_ids = array_keys($booking_rows);

		// return the final TXT file string
		return rtrim($file_content, $lines_separator);
	}

	/**
	 * Increases the current configuration setting for the transmission sequence.
	 * The transmission sequence is needed to give the export file a proper name.
	 * 
	 * @return 	void
	 */
	protected function increaseTransmissionSequence()
	{
		// get report settings
		$settings = $this->loadSettings();

		// get current sequence
		$current_sequence = (int) ($settings['sequence'] ?? 1);

		// update report settings with the increased sequence
		$settings['sequence'] = ++$current_sequence;

		$this->saveSettings($settings);
	}

	/**
	 * Parses a report row into an associative list of key-value pairs.
	 * 
	 * @param 	array 	$row 	The report row to parse.
	 * 
	 * @return 	array
	 */
	protected function getAssocRowData(array $row)
	{
		$row_data = [];

		foreach ($row as $field) {
			$field_val = $field['value'];
			if (!($field['no_export_callback'] ?? 0) && is_callable($field['export_callback'] ?? null)) {
				$field_val = $field['export_callback']($field_val);
			} elseif (!($field['no_export_callback'] ?? 0) && is_callable($field['callback'] ?? null)) {
				$field_val = $field['callback']($field_val);
			}
			$row_data[$field['key']] = $field_val;
		}

		return $row_data;
	}

	/**
	 * Registers the name to give to the file being exported.
	 * 
	 * @return 	void
	 */
	protected function registerExportFileName()
	{
		// load report settings
		$settings = $this->loadSettings();

		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		// build report file name (property code (dot) 3-digit sequence (dot) txt)
		$export_name = ($settings['codigo'] ?? '') ?: '0000000000';

		// build current sequence for progressive transmission without incrementing
		$sequence_int = (int) ($settings['sequence'] ?? 1);
		$sequence_int = $sequence_int > 999 ? 1 : $sequence_int;
		$sequence_int = $sequence_int < 0 ? 1 : $sequence_int;

		$sequence_str = str_pad((string) $sequence_int, 3, '0', STR_PAD_LEFT);

		$this->setExportCSVFileName($export_name . '.' . $sequence_str . '.txt');
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
}
