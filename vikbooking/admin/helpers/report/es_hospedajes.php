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
 * VikBookingReport implementation for (Spain) Hospedajes - Ministerio del Interior.
 * 
 * This report supports two different types of registration, one based on the reservation
 * date (Reservas de hospedaje) and another based on the check-in date (Partes de viajeros).
 * This report also provides some custom scoped actions for the electronic operations.
 * 
 * @see 	Instrucciones-hospedajes.pdf
 * @see 	MIR-HOSPE-DSI-WS-Servicio-de-Hospedajes---Comunicaciones-v3.1.2.pdf
 * 
 * @since 	1.17.2 (J) - 1.7.2 (WP)
 */
class VikBookingReportEsHospedajes extends VikBookingReport
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
	 * List of exported check-in dates (range).
	 * 
	 * @var  	array
	 */
	protected $exported_checkin_dates = [];

	/**
	 * The development (test, pruebas) endpoint.
	 * 
	 * @var  	string
	 */
	protected $endpoint_test = 'https://hospedajes.pre-ses.mir.es/hospedajes-web/ws/v1/comunicacion';

	/**
	 * The production (live, producción) endpoint.
	 * 
	 * @var  	string
	 */
	protected $endpoint_production = 'https://hospedajes.ses.mir.es/hospedajes-web/ws/v1/comunicacion';

	/**
	 * The path to the temporary directory used by this report.
	 * 
	 * @var  	string
	 */
	protected $report_tmp_path = '';

	/**
	 * The software application name.
	 * 
	 * @var 	string
	 */
	protected $software_application_name = 'VikBooking - E4jConnect';

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
	 * Associative list of guest types.
	 * 
	 * @var 	array
	 */
	protected $guest_types = [
		'TI' => 'Titular',
		'VI' => 'Viajero',
	];

	/**
	 * Class constructor should define the name of the report and
	 * other vars. Call the parent constructor to define the DB object.
	 */
	public function __construct()
	{
		$this->reportFile = basename(__FILE__, '.php');
		$this->reportName = 'SES Hospedajes - Ministerio del Interior';
		$this->reportFilters = [];

		$this->cols = [];
		$this->rows = [];
		$this->footerRow = [];

		$this->registerExportFileName();

		// set the temporary report directory path
		$this->report_tmp_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'hospedajes_tmp';

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
	 */
	public function getSettingFields()
	{
		return [
			'codigo' => [
				'type'  => 'text',
				'label' => 'Código del establecimiento',
				'help'  => 'Se deberá informar, si se conoce, el código del establecimiento asignado por el Sistema de Hospedajes en el momento del registro.',
			],
			'tipo_establecimiento' => [
				'type'    => 'select',
				'label'   => 'Tipo de establecimiento',
				'help'    => 'Datos del establecimento en caso de no disponer de un código de establecimiento en el Sistema de Hospedajes.',
				'options' => $this->tipos_de_establecimiento,
				'default' => 'OTROS',
			],
			'nombre_establecimiento' => [
				'type'  => 'text',
				'label' => 'Nombre del establecimiento',
				'help'  => 'Datos del establecimento en caso de no disponer de un código de establecimiento en el Sistema de Hospedajes.',
				'attributes' => [
					'maxlength' => 50,
				],
				'default' => strip_tags((string) VikBooking::getFrontTitle()),
			],
			'direccion' => [
				'type'  => 'text',
				'label' => 'Dirección del establecimiento',
				'help'  => 'Datos del establecimento en caso de no disponer de un código de establecimiento en el Sistema de Hospedajes.',
			],
			'codigo_postal' => [
				'type'  => 'text',
				'label' => 'Código postal',
				'help'  => 'Datos del establecimento en caso de no disponer de un código de establecimiento en el Sistema de Hospedajes.',
				'attributes' => [
					'maxlength' => 20,
				],
			],
			'pais' => [
				'type'    => 'text',
				'label'   => 'Código del país',
				'help'    => 'Este campo va codificado según la norma ISO 3166-1 Alfa-3.',
				'default' => 'ESP',
				'attributes' => [
					'maxlength' => 3,
				],
			],
			'codigo_municipio' => [
				'type'  => 'text',
				'label' => 'Código municipio',
				'help'  => 'Este campo irá codificado con los códigos de municipios del INE (5 dígitos). Obligatorio cuando el país es España.',
				'attributes' => [
					'maxlength' => 5,
				],
			],
			'nombre_municipio' => [
				'type'  => 'text',
				'label' => 'Nombre municipio',
				'help'  => 'Nombre del municipio, ciudad, estado, etc.. Obligatorio cuando el país no es España.',
				'attributes' => [
					'maxlength' => 100,
				],
			],
			'service_test_mode' => [
				'type'    => 'checkbox',
				'label'   => 'Endpoint pruebas',
				'help'    => 'Datos de acceso al Servicio de Comunicación. Pruebas o Producción.',
				'default' => 0,
			],
			'service_arrendador' => [
				'type'    => 'text',
				'label'   => 'Arrendador',
				'help'    => 'Código del arrendador asignado por el Sistema de Hospedajes en el momento del registro.',
			],
			'service_username' => [
				'type'    => 'text',
				'label'   => 'Usuario',
				'help'    => 'Datos de acceso al Servicio de Comunicación. Usuario.',
			],
			'service_password' => [
				'type'    => 'password',
				'label'   => 'Contraseña',
				'help'    => 'Datos de acceso al Servicio de Comunicación. Contraseña.',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getScopedActions($scope = null, $visible = true)
	{
		// list of custom actions for this report
		$actions = [
			[
				'id' => 'registerTravelerParts',
				'name' => 'Alta de partes de viajeros',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
				],
			],
			[
				'id' => 'registerAccommodationReservations',
				'name' => 'Alta de reservas de hospedaje',
				'icon' => VikBookingIcons::i('cloud-upload-alt'),
				// flag to indicate that it requires the report data (lines)
				'export_data' => true,
				'scopes' => [
					'web',
					'cron',
				],
			],
			[
				'id' => 'batchArchive',
				'name' => 'Archivo lotes',
				'help' => 'Archivo de lotes obtenidos como resultado de transmisiones de datos de huéspedes.',
				'icon' => VikBookingIcons::i('folder-open'),
				'scopes' => [
					'web',
				],
				'params' => [
					'op_month' => [
						'type'    => 'calendar',
						'label'   => 'Mes de la comunicación',
						'help'    => 'Seleccione el mes en el que se produjo la comunicación.',
						'default' => date('Y-m-01'),
					],
					'rp_type' => [
						'type'    => 'select',
						'label'   => 'Tipo de comunicación',
						'help'    => 'Filtro opcional por tipo de comunicación.',
						'options' => [
							'0'  => '',
							'PV' => 'Alta de partes de viajeros',
							'HR' => 'Alta de reservas de hospedaje',
						],
						'default' => '0',
					],
				],
				'params_submit_lbl' => 'Búsqueda lotes',
			],
			[
				'id' => 'checkBatchStatus',
				'name' => 'Consulta de lotes',
				'help' => 'Consultar el estado de los lotes obtenidos de transmisiones de datos anteriores.',
				'icon' => VikBookingIcons::i('user-check'),
				'scopes' => [
					'web',
					'cron',
				],
				'params' => [
					'batch_sequence' => [
						'type'    => 'text',
						'label'   => 'Lote',
						'help'    => 'Ingrese el número de lote a buscar.',
					],
					'op_date' => [
						'type'    => 'calendar',
						'label'   => 'Fecha de la comunicación',
						'help'    => 'Seleccione la fecha en el que se produjo la comunicación.',
						'default' => date('Y-m-d'),
					],
				],
				'params_submit_lbl' => 'Consulta lotes',
			],
			[
				'id' => 'prepareCommunicationMessage',
				'name' => 'Prepare communication mesage',
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

		// get VBO Application Object
		$vbo_app = VikBooking::getVboApplication();

		// load the jQuery UI Datepicker
		$this->loadDatePicker();

		// custom export button
		$this->customExport = '<a href="JavaScript: void(0);" onclick="vboDownloadHospedajes();" class="vbcsvexport"><i class="'.VikBookingIcons::i('download').'"></i> <span>Download XML</span></a>';

		// build the hidden values for the selection of various fields.
		$hidden_vals = '<div id="vbo-report-hospedajes-hidden" style="display: none;">';

		// build params container HTML structure
		$hidden_vals .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
		$hidden_vals .= '	<div class="vbo-params-wrap">';
		$hidden_vals .= '		<div class="vbo-params-container">';
		$hidden_vals .= '			<div class="vbo-params-block vbo-params-block-noborder">';

		// tipos de pago
		$hidden_vals .= '	<div id="vbo-report-hospedajes-paytype" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
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
		$hidden_vals .= '	<div id="vbo-report-hospedajes-nazione" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
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
		$hidden_vals .= '	<div id="vbo-report-hospedajes-doctype" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
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
		$hidden_vals .= '	<div id="vbo-report-hospedajes-parentesco" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
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
		$hidden_vals .= '	<div id="vbo-report-hospedajes-sesso" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
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
		$hidden_vals .= '	<div id="vbo-report-hospedajes-docnum" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Número de documento</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-docnum" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// número de soporte del documento
		$hidden_vals .= '	<div id="vbo-report-hospedajes-docsoporte" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Número de soporte del documento</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-docsoporte" value="" />';
		$hidden_vals .= '			<span class="vbo-param-setting-comment">Obligatorio si el tipo de documento es NIF, NIE.</span>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// address
		$hidden_vals .= '	<div id="vbo-report-hospedajes-address" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Direccion</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-address" value="" />';
		$hidden_vals .= '			<span class="vbo-param-setting-comment">Calle, número, escalera, piso, puerta, y demás campos que indiquen la dirección.</span>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// codigo municipio
		$hidden_vals .= '	<div id="vbo-report-hospedajes-municipio" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
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
		$hidden_vals .= '	<div id="vbo-report-hospedajes-city" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Nombre del municipio</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-city" value="" />';
		$hidden_vals .= '			<span class="vbo-param-setting-comment">Nombre del municipio, ciudad, estado, etc.. Obligatorio cuando el país no es España.</span>';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// postal code
		$hidden_vals .= '	<div id="vbo-report-hospedajes-postalcode" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Código postal</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="text" size="40" id="choose-postalcode" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// phone
		$hidden_vals .= '	<div id="vbo-report-hospedajes-phone" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Teléfono</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="tel" size="40" id="choose-phone" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// email
		$hidden_vals .= '	<div id="vbo-report-hospedajes-email" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
		$hidden_vals .= '		<div class="vbo-param-label">Dirección de correo electrónico</div>';
		$hidden_vals .= '		<div class="vbo-param-setting">';
		$hidden_vals .= '			<input type="email" size="40" id="choose-email" value="" />';
		$hidden_vals .= '		</div>';
		$hidden_vals .= '	</div>';

		// birth date
		$hidden_vals .= '	<div id="vbo-report-hospedajes-dbirth" class="vbo-report-hospedajes-selcont vbo-param-container" style="display: none;">';
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

		// export type filter
		$types = [
			'checkin' 	  => sprintf('%s (%s)', JText::translate('VBPICKUPAT'), 'Partes de viajeros'),
			'reservation' => sprintf('%s (%s)', JText::translate('VBPCHOOSEBUSYORDATE'), 'Reservas de hospedaje'),
		];
		$ptype = VikRequest::getString('type', 'checkin', 'request');
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
			'label' => '<label class="vbo-report-hospedajes-manualsave" style="display: none;">Guests data</label>',
			'html' => '<button type="button" class="btn vbo-config-btn vbo-report-hospedajes-manualsave" style="display: none;" onclick="vboHospedajesSaveData();"><i class="' . VikBookingIcons::i('save') . '"></i> ' . JText::translate('VBSAVE') . '</button>',
		);
		array_push($this->reportFilters, $filter_opt);

		// datepicker calendars, select2 and triggers for the dropdown menus
		$pfromdate = VikRequest::getString('fromdate', '', 'request');
		$ptodate = VikRequest::getString('todate', '', 'request');

		$js = 'var reportActiveCell = null, reportObj = {};
		var vbo_hospedajes_ajax_uri = "' . VikBooking::ajaxUrl('index.php?option=com_vikbooking&task=invoke_report&report=' . $this->reportFile) . '";
		var vbo_hospedajes_save_icn = "' . VikBookingIcons::i('save') . '";
		var vbo_hospedajes_saving_icn = "' . VikBookingIcons::i('circle-notch', 'fa-spin fa-fw') . '";
		var vbo_hospedajes_saved_icn = "' . VikBookingIcons::i('check-circle') . '";
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
			jQuery("#vbo-report-hospedajes-hidden").children().detach().appendTo(".vbo-info-overlay-report");
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
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-nazione").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-doctype").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-doctype").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-paytype").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-paytype").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-sesso").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-sesso").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog vbo-modal-nofooter",
				});
			});
			jQuery(".vbo-report-load-docnum").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-docnum").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenDocnum(document.getElementById(\'choose-docnum\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-docnum").focus();}, 500);
			});
			jQuery(".vbo-report-load-docsoporte").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-docsoporte").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenDocsoporte(document.getElementById(\'choose-docsoporte\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-docsoporte").focus();}, 500);
			});
			jQuery(".vbo-report-load-address").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-address").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenAddress(document.getElementById(\'choose-address\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-address").focus();}, 500);
			});
			jQuery(".vbo-report-load-parentesco").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-parentesco").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenParentesco(document.getElementById(\'choose-parentesco\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-parentesco").focus();}, 500);
			});
			jQuery(".vbo-report-load-municipio").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-municipio").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenMunicipio(document.getElementById(\'choose-municipio\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-municipio").focus();}, 500);
			});
			jQuery(".vbo-report-load-city").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-city").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenCity(document.getElementById(\'choose-city\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-city").focus();}, 500);
			});
			jQuery(".vbo-report-load-postalcode").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-postalcode").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenPostalcode(document.getElementById(\'choose-postalcode\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-postalcode").focus();}, 500);
			});
			jQuery(".vbo-report-load-phone").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-phone").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenPhone(document.getElementById(\'choose-phone\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-phone").focus();}, 500);
			});
			jQuery(".vbo-report-load-email").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-email").show();
				vboShowOverlay({
					title: "Completa la información",
					extra_class: "vbo-modal-rounded vbo-modal-dialog",
					footer_right: "<button type=\"button\" class=\"btn btn-success\" onclick=\"vboReportChosenEmail(document.getElementById(\'choose-email\').value);\">Guardar</button>",
				});
				setTimeout(function(){jQuery("#choose-email").focus();}, 500);
			});
			jQuery(".vbo-report-load-dbirth").click(function() {
				reportActiveCell = this;
				jQuery(".vbo-report-hospedajes-selcont").hide();
				jQuery("#vbo-report-hospedajes-dbirth").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
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
			jQuery(".vbo-report-hospedajes-manualsave").show();
		}
		//download function
		function vboDownloadHospedajes(type, report_type) {
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
		function vboHospedajesSaveData() {
			jQuery("button.vbo-report-hospedajes-manualsave").find("i").attr("class", vbo_hospedajes_saving_icn);
			VBOCore.doAjax(
				vbo_hospedajes_ajax_uri,
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
					jQuery("button.vbo-report-hospedajes-manualsave").addClass("btn-success").find("i").attr("class", vbo_hospedajes_saved_icn);
				},
				function(error) {
					alert(error.responseText);
					jQuery("button.vbo-report-hospedajes-manualsave").removeClass("btn-success").find("i").attr("class", vbo_hospedajes_save_icn);
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
		$opt_type     = $options->get('type', '');

		// input fields and other vars
		$pfromdate = $opt_fromdate ?: VikRequest::getString('fromdate', '', 'request');
		$ptodate = $opt_todate ?: VikRequest::getString('todate', '', 'request');
		$ptype = $opt_type ?: VikRequest::getString('type', 'checkin', 'request');

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

		// set the dates being exported
		$this->exported_checkin_dates = [
			date('Y-m-d', $from_ts),
			date('Y-m-d', $to_ts),
		];

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
			->order($this->dbo->qn('o.checkin') . ' ASC')
			->order($this->dbo->qn('o.id') . ' ASC')
			->order($this->dbo->qn('or.id') . ' ASC');

		if ($ptype === 'reservation') {
			$q->where($this->dbo->qn('o.ts') . ' >= ' . $from_ts);
			$q->where($this->dbo->qn('o.ts') . ' <= ' . $to_ts);
		} else {
			$q->where($this->dbo->qn('o.checkin') . ' >= ' . $from_ts);
			$q->where($this->dbo->qn('o.checkin') . ' <= ' . $to_ts);
		}

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
			// guest type (rol)
			array(
				'key' => 'guest_type',
				'label' => 'Rol',
			),
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

				// guest type (rol)
				$pax_guesttype = $ptype === 'reservation' && $guest_room_ind < 2 ? 'TI' : 'VI';

				array_push($insert_row, array(
					'key' => 'guest_type',
					'callback' => function($val) {
						return $this->guest_types[$val] ?? $val;
					},
					'no_export_callback' => 1,
					'value' => $pax_guesttype,
				));

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
						return date('Y-m-d', $val);
					},
					'value' => $guests['ts'],
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
					'no_export_callback' => 1,
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
					'no_export_callback' => 1,
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
					'value' => $docnum,
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
							return date('Y-m-d', $val);
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
					'no_export_callback' => 1,
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
					'no_export_callback' => 1,
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
			VikBooking::getBookingHistoryInstance($bid)->store('RP', $this->reportName . ' - Export');
		}

		// custom export method supports a custom export handler, if previously set.
		if ($this->hasExportHandler()) {
			// write data onto the custom file handler
			$fp = $this->getExportCSVHandler();
			fwrite($fp, $xml);
			fclose($fp);

			// return true as data was written
			return true;
		}

		// force text file download in case of regular export
		header("Content-type: text/xml");
		header("Cache-Control: no-store, no-cache");
		header('Content-Disposition: attachment; filename="' . $this->getExportCSVFileName() . '"');
		echo $xml;

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
	 * Builds the XML file for export or transmission.
	 * 
	 * @param 	string 	$type 	The XML export type (checkin or reservation).
	 * 
	 * @return 	string 			Empty string in case of errors, XML otherwise.
	 */
	protected function buildXMLFile($type = '')
	{
		if (!$this->getReportData()) {
			return '';
		}

		// load report settings
		$settings = $this->loadSettings();

		// get the possibly injected report options
		$options = $this->getReportOptions();

		// injected options will replace request variables, if any
		$export_type = $type ?: $options->get('type', VikRequest::getString('type', 'checkin', 'request'));

		// access manually filled values, if any
		$pfiller = VikRequest::getString('filler', '', 'request', VIKREQUEST_ALLOWRAW);
		$pfiller = !empty($pfiller) ? (array) json_decode($pfiller, true) : [];

		// determine the XML namespace to use
		if ($export_type === 'checkin') {
			$xmlns = 'http://www.neg.hospedajes.mir.es/altaParteHospedaje';
		} else {
			$xmlns = 'http://www.neg.hospedajes.mir.es/altaReservaHospedaje';
		}

		// start building the XML
		$xml = $this->loadXmlSoap('<alt:peticion xmlns:alt="' . $xmlns . '"></alt:peticion>');

		// add main child common node (no value, empty namespace)
		$solicitud = $xml->addChild('solicitud', null, '');

		if ($export_type === 'checkin') {
			// partes de viajeros (check-in report)
			$solicitud->addChild('codigoEstablecimiento', htmlspecialchars($settings['codigo'] ?? ''));
		}

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
			// add <comunicacion> node for this reservation
			$comunicacion = $solicitud->addChild('comunicacion');

			// check if <establecimiento> node is needed
			if ($export_type !== 'checkin') {
				// reservas de hospedaje (reservation report)
				$establecimiento = $comunicacion->addChild('establecimiento');
				if (!empty($settings['codigo'])) {
					// property code, if known
					$establecimiento->addChild('codigo', htmlspecialchars($settings['codigo']));
				} else {
					// property details otherwise
					$datos = $establecimiento->addChild('datosEstablecimiento');
					$datos->addChild('tipo', htmlspecialchars($settings['tipo_establecimiento'] ?? ''));
					$datos->addChild('nombre', htmlspecialchars($settings['nombre_establecimiento'] ?? ''));
					$direccion = $datos->addChild('direccion');
					$direccion->addChild('direccion', htmlspecialchars($settings['direccion'] ?? ''));
					$direccion->addChild('codigoMunicipio', htmlspecialchars($settings['codigo_municipio'] ?? ''));
					$direccion->addChild('nombreMunicipio', htmlspecialchars($settings['nombre_municipio'] ?? ''));
					$direccion->addChild('codigoPostal', htmlspecialchars($settings['codigo_postal'] ?? ''));
					$direccion->addChild('pais', htmlspecialchars($settings['pais'] ?? ''));
				}
			}

			// build an associative list of data for the first guest row of this booking
			$main_res_data = $this->getAssocRowData($rows[0]);

			// add <contrato> node
			$contrato = $comunicacion->addChild('contrato');
			$contrato->addChild('referencia', $main_res_data['idbooking']);
			$contrato->addChild('fechaContrato', htmlspecialchars($main_res_data['resdate']));
			$contrato->addChild('fechaEntrada', htmlspecialchars($main_res_data['checkin']));
			$contrato->addChild('fechaSalida', htmlspecialchars($main_res_data['checkout']));
			$contrato->addChild('numPersonas', htmlspecialchars($main_res_data['numguests']));
			$pago = $contrato->addChild('pago');
			$pago->addChild('tipoPago', htmlspecialchars($main_res_data['paytype']));

			// scan all guest rows for this reservation to build the various <persona> nodes
			foreach ($rows as $row) {
				// get the associative list of guest data from the current row
				$row_data = $this->getAssocRowData($row);

				// add <persona> node for this guest
				$persona = $comunicacion->addChild('persona');

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

				// set persona details
				$persona->addChild('rol', htmlspecialchars($row_data['guest_type']));
				$persona->addChild('nombre', htmlspecialchars($row_data['first_name']));
				$persona->addChild('apellido1', htmlspecialchars($last_name_one));
				if ($last_name_one != $last_name_two || (!empty($row_data['doctype']) && !strcasecmp($row_data['doctype'], 'NIF'))) {
					// apellido2 is mandatory in this case
					$persona->addChild('apellido2', htmlspecialchars($last_name_two));
				}
				if (!empty($row_data['doctype'])) {
					$persona->addChild('tipoDocumento', htmlspecialchars($row_data['doctype']));
				}
				if (!empty($row_data['docnum'])) {
					$persona->addChild('numeroDocumento', htmlspecialchars($row_data['docnum']));
				}
				if (!empty($row_data['docsoporte'])) {
					$persona->addChild('soporteDocumento', htmlspecialchars($row_data['docsoporte']));
				}
				$persona->addChild('fechaNacimiento', htmlspecialchars($row_data['date_birth']));
				$persona->addChild('nacionalidad', htmlspecialchars(($row_data['country_c'] ?: $row_data['country_s'])));
				$persona->addChild('sexo', htmlspecialchars($row_data['gender']));

				// direccion
				$direccion = $persona->addChild('direccion');
				if ($row_data['address']) {
					$direccion->addChild('direccion', htmlspecialchars($row_data['address']));
				}
				if ($row_data['municipio']) {
					$direccion->addChild('codigoMunicipio', htmlspecialchars($row_data['municipio']));
				}
				if ($row_data['city']) {
					$direccion->addChild('nombreMunicipio', htmlspecialchars($row_data['city']));
				}
				if ($row_data['postalcode']) {
					$direccion->addChild('codigoPostal', htmlspecialchars($row_data['postalcode']));
				}
				if ($row_data['country_s'] || $row_data['country_c']) {
					$direccion->addChild('pais', htmlspecialchars(($row_data['country_s'] ?: $row_data['country_c'])));
				}

				if (!empty($row_data['phone'])) {
					$persona->addChild('telefono', htmlspecialchars($row_data['phone']));
				}
				if (!empty($row_data['email'])) {
					$persona->addChild('correo', htmlspecialchars($row_data['email']));
				}
				if ($export_type === 'checkin' && !empty($row_data['parentesco'])) {
					$persona->addChild('parentesco', htmlspecialchars($row_data['parentesco']));
				}
			}
		}

		// set pool of booking IDs to update their history
		$this->export_booking_ids = array_keys($booking_rows);

		// get the formatted XML file string
		$formatted_xml = $xml->formatXml();

		// make sure to get rid of empty namespace attributes
		$formatted_xml = str_replace(' xmlns=""', '', $formatted_xml);

		// return the final XML file string
		return $formatted_xml;
	}

	/**
	 * Custom scoped action to transmit the registration of the traveler parts (Alta de partes de viajeros).
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function registerTravelerParts($scope = null, array $data = [])
	{
		if (!($data['xml'] ?? '') && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadHospedajes(\'registerTravelerParts\', \'checkin\');</script>',
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

		if (!$settings || empty($settings['service_username']) || empty($settings['service_password']) || empty($settings['service_arrendador'])) {
			throw new Exception(sprintf('[%s] error: missing service settings.', __METHOD__), 500);
		}

		// set the type of transmission
		$tn_type = 'PV';

		// prepare the communication message data
		$message_data = array_merge($data, [
			'operation_type' => 'A',
			'report_type'    => $tn_type,
		]);

		// get the communication message
		$message = $this->_callActionReturn('prepareCommunicationMessage', 'message', $scope, $message_data);

		try {
			// post the message to the electronic service and get the Soap XML response message
			$response = $this->postMessage($message, $settings);

			// parse the response message and get the main node across multiple namespaces
			$xmlBody = $this->loadXmlSoap($response)->getSoapRecursiveNsElement([
				[
					'SOAP-ENV',
					true,
					'Body',
				],
				[
					'http://www.soap.servicios.hospedajes.mir.es/comunicacion',
					false,
					'respuesta',
				],
			]);

			// access the desired XML element
			$xmlBody = $xmlBody->respuesta ?? $xmlBody;

			if (!isset($xmlBody->codigoRetorno)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = (int) $xmlBody->codigoRetorno !== 0;

			if ($is_error || !($xmlBody->lote ?? '')) {
				$error_code = (string) ($xmlBody->codigoRetorno ?? '500');
				$error_dets = (string) ($xmlBody->descripcion ?? 'Unknown');
				// terminate the execution in case of errors or empty result
				throw new Exception(sprintf("[%s] Response error:\n%s", $error_code, $error_dets), 500);
			}

			// the batch value obtained with the transmission
			$batch_value = (string) $xmlBody->lote;

			// store the batch value on the DB for later fetching
			$this->storeBatchData([
				'batch'   => $batch_value,
				'op_date' => date('Y-m-d H:i:s'),
				'from_dt' => $this->exported_checkin_dates[0] ?? date('Y-m-d'),
				'to_dt'   => $this->exported_checkin_dates[1] ?? date('Y-m-d'),
				'op_type' => 'A',
				'rp_type' => $tn_type,
			], 'hospedajes_batches_' . date('Yn'));

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Lote: ' . $batch_value . '</p>';

		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.travelparts.ok',
				'title'   => 'Hospedajes - Alta de partes de viajeros',
				'summary' => sprintf(
					'Las partes de viajeros se han registrado correctamente para las fechas de check-in del %s al %s. Número de lote obtenido: %s.',
					$this->exported_checkin_dates[0] ?? '',
					$this->exported_checkin_dates[1] ?? '',
					$batch_value
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
			'success' => !empty($batch_value),
			'batch'   => $batch_value,
		];
	}

	/**
	 * Custom scoped action to transmit the registration of the accommodation reservation (Alta de reservas de hospedaje).
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function registerAccommodationReservations($scope = null, array $data = [])
	{
		if (!($data['xml'] ?? '') && $scope === 'web') {
			// start the process through the interface by submitting the current data
			return [
				'html' => '<script type="text/javascript">vboDownloadHospedajes(\'registerAccommodationReservations\', \'reservation\');</script>',
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

		if (!$settings || empty($settings['service_username']) || empty($settings['service_password']) || empty($settings['service_arrendador'])) {
			throw new Exception(sprintf('[%s] error: missing service settings.', __METHOD__), 500);
		}

		// set the type of transmission
		$tn_type = 'RH';

		// prepare the communication message data
		$message_data = array_merge($data, [
			'operation_type' => 'A',
			'report_type'    => $tn_type,
		]);

		// get the communication message
		$message = $this->_callActionReturn('prepareCommunicationMessage', 'message', $scope, $message_data);

		try {
			// post the message to the electronic service and get the Soap XML response message
			$response = $this->postMessage($message, $settings);

			// parse the response message and get the main node across multiple namespaces
			$xmlBody = $this->loadXmlSoap($response)->getSoapRecursiveNsElement([
				[
					'SOAP-ENV',
					true,
					'Body',
				],
				[
					'http://www.soap.servicios.hospedajes.mir.es/comunicacion',
					false,
					'respuesta',
				],
			]);

			// access the desired XML element
			$xmlBody = $xmlBody->respuesta ?? $xmlBody;

			if (!isset($xmlBody->codigoRetorno)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlBody->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = (int) $xmlBody->codigoRetorno !== 0;

			if ($is_error || !($xmlBody->lote ?? '')) {
				$error_code = (string) ($xmlBody->codigoRetorno ?? '500');
				$error_dets = (string) ($xmlBody->descripcion ?? 'Unknown');
				// terminate the execution in case of errors or empty result
				throw new Exception(sprintf("[%s] Response error:\n%s", $error_code, $error_dets), 500);
			}

			// the batch value obtained with the transmission
			$batch_value = (string) $xmlBody->lote;

			// store the batch value on the DB for later fetching
			$this->storeBatchData([
				'batch'   => $batch_value,
				'op_date' => date('Y-m-d H:i:s'),
				'from_dt' => $this->exported_checkin_dates[0] ?? date('Y-m-d'),
				'to_dt'   => $this->exported_checkin_dates[1] ?? date('Y-m-d'),
				'op_type' => 'A',
				'rp_type' => $tn_type,
			], 'hospedajes_batches_' . date('Yn'));

			// build HTML response string
			$html = '';
			$html .= '<p class="successmade">Lote: ' . $batch_value . '</p>';

		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.reshospedaje.ok',
				'title'   => 'Hospedajes - Alta de reservas de hospedaje',
				'summary' => sprintf(
					'Las reservas de hospedaje se han registrado correctamente para las fechas del %s al %s. Número de lote obtenido: %s.',
					$this->exported_checkin_dates[0] ?? '',
					$this->exported_checkin_dates[1] ?? '',
					$batch_value
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
			'success' => !empty($batch_value),
			'batch'   => $batch_value,
		];
	}

	/**
	 * Custom scoped action to list all the previously obtained batches.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function batchArchive($scope = null, array $data = [])
	{
		// response properties
		$html = '';

		// optional filter by exact date
		$date_filter = '';

		// optional filter by report type
		$rptype_filter = $data['rp_type'] ?? '';

		// read exact or current period (4-digit year + month without leading zero)
		$read_period = $data['yearmon'] ?? date('Yn');

		if (!empty($data['op_month'])) {
			// date in Y-m-d format to be converted into year+mon for reading a specific month
			$read_period = date('Yn', strtotime($data['op_month']));
		}

		if (!empty($data['op_date'])) {
			// full date in Y-m-d format for reading the operations of a specific date
			$date_filter = $data['op_date'];
			// set the month to read from the database
			$read_period = date('Yn', strtotime($data['op_date']));
		}

		// read batches for the requested period
		$batches = $this->readBatchData('hospedajes_batches_' . $read_period);

		if ($date_filter) {
			// filter batches by exact date
			$batches = $this->filterBatchData($batches, $date_filter);
		}

		if (strlen((string) $rptype_filter) === 2) {
			// filter batches by report type
			$batches = $this->filterBatchData($batches, '', $rptype_filter);
		}

		if (!$batches) {
			$html .= '<p class="warn">No se encontraron lotes para el período solicitado.</p>';
		} else {
			$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
			$html .= '	<div class="vbo-params-wrap">';
			$html .= '		<div class="vbo-params-container">';
			$html .= '			<div class="vbo-params-block">';

			foreach ($batches as $batch) {
				$report_type = $batch['rp_type'] === 'PV' ? 'Partes de viajeros' : 'Reservas de hospedaje';

				$html .= '			<div class="vbo-param-container">';
				$html .= '				<div class="vbo-param-label">' . $report_type . '<br/>' . $batch['op_date'] . '</div>';
				$html .= '				<div class="vbo-param-setting">';
				$html .= '					<span>' . $batch['batch'] . '</span>';
				$html .= '					<span class="vbo-param-setting-comment">' . sprintf('(%s) %s - %s', $batch['op_type'], $batch['from_dt'], $batch['to_dt']) . '</span>';
				$html .= '				</div>';
				$html .= '			</div>';
			}

			$html .= '			</div>';
			$html .= '		</div>';
			$html .= '	</div>';
			$html .= '</div>';
		}

		return [
			'html'    => $html,
			'total'   => count($batches),
			'batches' => $batches,
			'period'  => $read_period,
			'date'    => $date_filter,
		];
	}

	/**
	 * Custom scoped action to check the status of batches obtained from previous transmissions.
	 * Accepted scopes are "web" and "cron", so the "success" property must be returned.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function checkBatchStatus($scope = null, array $data = [])
	{
		// response properties
		$html = '';
		$communication_ok = 0;
		$communication_err = 0;

		// list of batch strings (sequences) to consult
		$batches = [];

		if ($data['batch_sequence'] ?? '') {
			// push a single batch to check
			$batches[] = $data['batch_sequence'];
		} elseif ($data['op_date'] ?? '') {
			// get the batches from a specific date
			$list = $this->_callActionReturn('batchArchive', 'batches', $scope, ['op_date' => $data['op_date']]);
			$batches = array_column($list, 'batch');
		}

		if (!$batches) {
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, 'No batch values to check according to filters.'), 404);
		}

		// build the communication XML string
		$batch_nodes = implode("\n", array_map(function($batch) {
			return "\t" . '<con:lote>' . $batch . '</con:lote>';
		}, $batches));

		$xml_comunication = <<<XML
<con:lotes xmlns:con="http://www.neg.hospedajes.mir.es/consultarComunicacion">
$batch_nodes
</con:lotes>
XML;

		// prepare the communication message data
		$message_data = [
			'xml' => $xml_comunication,
			'operation_type' => 'C',
		];

		// get the communication message
		$message = $this->_callActionReturn('prepareCommunicationMessage', 'message', $scope, $message_data);

		try {
			// post the message to the electronic service and get the Soap XML response message
			$response = $this->postMessage($message, $settings);

			// parse the response message and get the main node across multiple namespaces
			$xmlResponseNode = $this->loadXmlSoap($response)->getSoapRecursiveNsElement([
				[
					'SOAP-ENV',
					true,
					'Body',
				],
				[
					'http://www.soap.servicios.hospedajes.mir.es/comunicacion',
					false,
					'respuesta',
				],
			]);

			// access the "response" node for validation
			$xmlResponseNode = $xmlResponseNode->respuesta ?? $xmlResponseNode;

			if (!isset($xmlResponseNode->codigo)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response [%s]', $xmlResponseNode->formatXml() ?: $response), 500);
			}

			// validate the response
			$is_error = (int) $xmlResponseNode->codigo !== 0;

			if ($is_error) {
				$error_code = (string) ($xmlBody->codigo ?? '500');
				$error_dets = (string) ($xmlBody->descripcion ?? 'Unknown');
				// terminate the execution in case of errors or empty result
				throw new Exception(sprintf("[%s] Response error:\n%s", $error_code, $error_dets), 500);
			}

			// now access the "result" node across multiple namespaces
			$xmlResultNode = $this->loadXmlSoap($response)->getSoapRecursiveNsElement([
				[
					'SOAP-ENV',
					true,
					'Body',
				],
				[
					'http://www.soap.servicios.hospedajes.mir.es/comunicacion',
					false,
					'resultado',
				],
			]);

			// access the "result" node for validation
			$xmlResultNode = $xmlResultNode->resultado ?? $xmlResultNode;

			if (!isset($xmlResultNode->resultadoComunicaciones->resultadoComunicacion)) {
				// unexpected response
				throw new UnexpectedValueException(sprintf('Unexpected response with no results [%s]', $xmlResultNode->formatXml() ?: $response), 500);
			}

			// build HTML response string
			$html .= '<div class="vbo-admin-container vbo-admin-container-full vbo-admin-container-compact">';
			$html .= '	<div class="vbo-params-wrap">';
			$html .= '		<div class="vbo-params-container">';
			$html .= '			<div class="vbo-params-block">';

			$html .= '				<div class="vbo-param-container">';
			$html .= '			 		<div class="vbo-param-label">Lote</div>';
			$html .= '					<div class="vbo-param-setting">';
			$html .= '						<span>' . (string) $xmlResultNode->lote . '</span>';
			$html .= '					</div>';
			$html .= '				</div>';

			$html .= '				<div class="vbo-param-container">';
			$html .= '			 		<div class="vbo-param-label">Tipo de comunicaciones enviadas</div>';
			$html .= '					<div class="vbo-param-setting">';
			$html .= '						<span>' . (string) $xmlResultNode->tipoComunicacion . ' - ' . (string) ($xmlResultNode->tipoOperacion ?? '') . '</span>';
			$html .= '					</div>';
			$html .= '				</div>';

			$html .= '				<div class="vbo-param-container">';
			$html .= '			 		<div class="vbo-param-label">Fecha Peticion</div>';
			$html .= '					<div class="vbo-param-setting">';
			$html .= '						<span>' . (string) $xmlResultNode->fechaPeticion . '</span>';
			$html .= '						<span class="vbo-param-setting-comment">Fecha Procesamiento ' . (string) ($xmlResultNode->fechaProcesamiento ?? '') . '</span>';
			$html .= '					</div>';
			$html .= '				</div>';

			$html .= '				<div class="vbo-param-container">';
			$html .= '			 		<div class="vbo-param-label">Estado</div>';
			$html .= '					<div class="vbo-param-setting">';
			$html .= '						<span>(' . (string) $xmlResultNode->codigoEstado . ') ' . (string) $xmlResultNode->descEstado . '</span>';
			$html .= '					</div>';
			$html .= '				</div>';

			// scan all results and increase success/error counters
			foreach ($xmlResultNode->resultadoComunicaciones->resultadoComunicacion as $rescom) {
				$html .= '			<div class="vbo-param-container">';
				$html .= '		 		<div class="vbo-param-label">Resultado #' . (string) $rescom->orden . '</div>';
				$html .= '				<div class="vbo-param-setting">';
				if ((string) ($rescom->codigoComunicacion ?? '')) {
					// expected communication code (success)
					$html .= '				<span>' . (string) $rescom->codigoComunicacion . '</span>';
					$html .= '				<span class="vbo-param-setting-comment">Codigo comunicacion.</span>';
					// increase counter
					$communication_ok++;
				} else {
					// expected error details
					$html .= '				<span>' . (string) ($rescom->tipoError ?? '') . '</span>';
					$html .= '				<span class="vbo-param-setting-comment">' . (string) ($rescom->error ?? '') . '</span>';
					// increase counter
					$communication_err++;
				}
				$html .= '				</div>';
				$html .= '			</div>';
			}

			$html .= '			</div>';
			$html .= '		</div>';
			$html .= '	</div>';
			$html .= '</div>';
		} catch (Exception $e) {
			// propagate the error caught
			throw new Exception(sprintf('[%s] error: %s', __METHOD__, $e->getMessage()), $e->getCode() ?: 500);
		}

		// when executed through a cron, store an event in the Notifications Center
		if ($scope === 'cron') {
			// build the notification record
			$notification = [
				'sender'  => 'reports',
				'type'    => 'pmsreport.checkbatchstatus.' . ($communication_err ? 'error' : 'ok'),
				'title'   => 'Hospedajes - Consulta de lotes',
				'summary' => sprintf(
					'Consulta de lotes finalizados. Resultados totales: %d. Éxito: %d. Error: %d.',
					($communication_ok + $communication_err),
					$communication_ok,
					$communication_err
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
			'html'      => $html,
			'success'   => true,
			'count_ok'  => $communication_ok,
			'count_err' => $communication_err,
		];
	}

	/**
	 * Custom scoped action to prepare a communication request towards the Electronic Service.
	 * 
	 * @param 	string 	$scope 	Optional scope identifier (cron, web, etc..).
	 * @param 	array 	$data 	Optional associative list of data to process.
	 * 
	 * @return 	array 			The execution result properties.
	 * 
	 * @throws 	Exception
	 */
	protected function prepareCommunicationMessage($scope = null, array $data = [])
	{
		// load report settings
		$settings = $this->loadSettings();

		if (empty($data['operation_type'])) {
			throw new Exception('Missing mandatory Operation Type.', 500);
		}

		// ensure the operation type is upper-case (A, C or B)
		$data['operation_type'] = strtoupper($data['operation_type']);

		if ($data['operation_type'] === 'A' && empty($data['report_type'])) {
			// either "PV" (checkin) or "HR" (reservation) is mandatory
			throw new Exception('Missing mandatory Report Type.', 500);
		}

		// ensure the report type is upper-case ("PV" or "HR")
		$data['report_type'] = strtoupper($data['report_type'] ?? '');

		if (!($data['xml'] ?? '')) {
			// attempt to build the XML file if not set
			$data['xml'] = $this->buildXMLFile();
		}

		if (!$data['xml']) {
			throw new Exception('Missing XML request (solicitud).', 500);
		}

		// first off, clean up the report temporary directory to ensure we've got no other files
		JFolder::delete($this->report_tmp_path);

		// build the temporary XML file name
		$xml_fname = uniqid('hospedajes_') . '.xml';

		// store the XML file internally
		$xml_stored = JFile::write($this->report_tmp_path . DIRECTORY_SEPARATOR . $xml_fname, $data['xml']);

		if (!$xml_stored) {
			throw new Exception('Could not store the XML file internally for the data transmission.', 500);
		}

		// build the zip file destination path
		$zip_destination = $this->report_tmp_path . DIRECTORY_SEPARATOR . preg_replace("/\.xml$/", '.zip', $xml_fname);

		// compress the folder (should contain a single XML file) into an archive
		$xml_compressed = VBOArchiveFactory::compress($this->report_tmp_path, $zip_destination);
		
		if (!$xml_compressed) {
			throw new Exception('Could not compress the XML file in zip format for the data transmission.', 500);
		}

		// read the archive into a base64-encoded string
		$base64_request = base64_encode(file_get_contents($zip_destination));

		if (!$base64_request) {
			throw new Exception('Could not read the zip archive containing the XML file into a base64-encoded string for the data transmission.', 500);
		}

		// build SOAP message values
		$codigo_arrendador = $settings['service_arrendador'] ?? '';
		$application_name  = $this->software_application_name;
		$operation_type_code = $data['operation_type'];
		$communication_type_node = '';
		if ($data['operation_type'] === 'A') {
			// communication type node is mandatory
			$communication_type_node = '<tipoComunicacion>' . $data['report_type'] . '</tipoComunicacion>';
		}

		// build SOAP XML message
		$soap_xml = <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://www.soap.servicios.hospedajes.mir.es/comunicacion">
	<soapenv:Header/>
	<soapenv:Body>
		<com:comunicacionRequest>
			<peticion>
				<cabecera>
					<codigoArrendador>$codigo_arrendador</codigoArrendador>
					<aplicacion>$application_name</aplicacion>
					<tipoOperacion>$operation_type_code</tipoOperacion>
					$communication_type_node
				</cabecera>
				<solicitud>$base64_request</solicitud>
			</peticion>
		</com:comunicacionRequest>
	</soapenv:Body>
</soapenv:Envelope>
XML;

		return [
			'message'   => $soap_xml,
			'solicitud' => $data['xml'],
		];
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
			if (!($field['no_export_callback'] ?? 0) && is_callable($field['callback'] ?? null)) {
				$field_val = $field['callback']($field_val);
			}
			$row_data[$field['key']] = $field_val;
		}

		return $row_data;
	}

	/**
	 * Makes a POST request towards the electronic service by posting a SOAP XML message.
	 * 
	 * @param 	string 	$message 	The SOAP XML message to post.
	 * @param 	array 	$settings 	The report settings.
	 * 
	 * @return 	string 				The response obtained.
	 * 
	 * @throws 	Exception
	 */
	protected function postMessage($message, array $settings = [])
	{
		if (!$settings) {
			// load report settings
			$settings = $this->loadSettings();
		}

		/**
		 * @todo  start debug responses
		 */
		/*
		$ok = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
	<SOAP-ENV:Header/>
	<SOAP-ENV:Body>
		<ns3:comunicacionResponse xmlns:ns3="http://www.soap.servicios.hospedajes.mir.es/comunicacion">
			<respuesta>
				<codigoRetorno>0</codigoRetorno>
				<descripcion>Ok</descripcion>
				<lote>00000000-1234-5678-0000-00000000' . rand(1000, 9999) . '</lote>
			</respuesta>
		</ns3:comunicacionResponse>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

		$nok = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
	<SOAP-ENV:Header/>
	<SOAP-ENV:Body>
		<ns3:comunicacionResponse xmlns:ns3="http://www.soap.servicios.hospedajes.mir.es/comunicacion">
			<respuesta>
				<codigoRetorno>109</codigoRetorno>
				<descripcion>Formato de solicitud incorrecto. Ha de ir comprimido (zip) y codificado en Base64.</descripcion>
			</respuesta>
		</ns3:comunicacionResponse>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

		$okc = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
	<SOAP-ENV:Header/>
	<SOAP-ENV:Body>
		<ns3:comunicacionResponse xmlns:ns3="http://www.soap.servicios.hospedajes.mir.es/comunicacion">
			<respuesta>
				<codigo>0</codigo>
				<descripcion>Ok</descripcion>
			</respuesta>
			<resultado>
				<lote>00000000-0000-0000-0000-000000000000</lote>
				<tipoComunicacion>PV</tipoComunicacion>
				<tipoOperacion>A</tipoOperacion>
				<fechaPeticion>2023-02-23T11:37:13.000+01:00</fechaPeticion>
				<fechaProcesamiento>2023-02-23T11:37:33.000+01:00</fechaProcesamiento>
				<codigoEstado>6</codigoEstado>
				<descEstado>Se ha ejecutado pero hay errores en algunas comunicaciones</descEstado>
				<identificadorUsuario>B00000000</identificadorUsuario>
				<nombreUsuario>Prueba</nombreUsuario>
				<codigoArrendador>0000000001</codigoArrendador>
				<aplicacion>Sistema de pruebas</aplicacion>
				<resultadoComunicaciones>
					<resultadoComunicacion>
						<orden>1</orden>
						<codigoComunicacion>12345678-4321-3210-6789-210987654321</codigoComunicacion>
					</resultadoComunicacion>
				</resultadoComunicaciones>
			</resultado>
		</ns3:comunicacionResponse>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

		$nokc = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
	<SOAP-ENV:Header/>
	<SOAP-ENV:Body>
		<ns3:comunicacionResponse xmlns:ns3="http://www.soap.servicios.hospedajes.mir.es/comunicacion">
			<respuesta>
				<codigo>0</codigo>
				<descripcion>Ok</descripcion>
			</respuesta>
			<resultado>
				<lote>00000000-0000-0000-0000-000000000000</lote>
				<tipoComunicacion>PV</tipoComunicacion>
				<tipoOperacion>A</tipoOperacion>
				<fechaPeticion>2023-02-23T11:37:13.000+01:00</fechaPeticion>
				<fechaProcesamiento>2023-02-23T11:37:33.000+01:00</fechaProcesamiento>
				<codigoEstado>6</codigoEstado>
				<descEstado>Se ha ejecutado pero hay errores en algunas comunicaciones</descEstado>
				<identificadorUsuario>B00000000</identificadorUsuario>
				<nombreUsuario>Prueba</nombreUsuario>
				<codigoArrendador>0000000001</codigoArrendador>
				<aplicacion>Sistema de pruebas</aplicacion>
				<resultadoComunicaciones>
					<resultadoComunicacion>
						<orden>1</orden>
						<tipoError>Error validación de datos</tipoError>
						<error>Error de validación en la comunicación [1]: [No existe un establecimiento para ese código. codigoEstablecimiento: [1234567890]]</error>
					</resultadoComunicacion>
				</resultadoComunicaciones>
			</resultado>
		</ns3:comunicacionResponse>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

		if (strpos($message, '<tipoOperacion>C</tipoOperacion>')) {
			// consulta de lotes response simulation
			return rand(0, 1) ? $okc : $nokc;
		}

		return rand(0, 1) ? $ok : $nok;
		*/
		// end debug responses


		// build endpoint URL
		if ($settings['service_test_mode'] ?? 1) {
			$endpoint = $this->endpoint_test;
		} else {
			$endpoint = $this->endpoint_production;
		}

		// build basic-auth value
		$basic_auth = base64_encode(($settings['service_username'] ?? '') . ':' . ($settings['service_password'] ?? ''));

		// start HTTP transporter
		$http = new JHttp();

		// build connection request headers
		$headers = [
			'Authorization' => 'Basic ' . $basic_auth,
			'Content-Type'  => 'text/xml',
		];

		// check SSL request header settings
		if (is_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . '_.ses.mir.es_1.crt')) {
			// register custom SSL certificate path
			$headers['sslcertificates'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_.ses.mir.es_1.crt';
		} elseif (is_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . '_.ses.mir.es.crt')) {
			// register custom SSL certificate path
			$headers['sslcertificates'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . '_.ses.mir.es.crt';
		} else {
			// disable SSL peer validation for using an unsecure connection
			$headers['sslverify'] = false;
		}

		// make POST request to the specified URL
		$response = $http->post($endpoint, $message, $headers);

		if ($response->code != 200) {
			// invalid response, throw an exception
			throw new Exception(strip_tags((string) $response->body), $response->code);
		}

		return $response->body;
	}

	/**
	 * Stores batch data under the given batch field name.
	 * 
	 * @param 	array 	$data 	The batch data to store.
	 * @param 	string 	$field 	The batch field name.
	 * 
	 * @return 	void
	 */
	protected function storeBatchData(array $data, $field = '')
	{
		// the configuration field name
		$field = $field ?: 'hospedajes_batches_' . date('Yn');

		// access batches from the current field
		$current_batches = VBOFactory::getConfig()->getArray($field, []);

		// prepend new batch data
		array_unshift($current_batches, $data);

		// save new batches
		VBOFactory::getConfig()->set($field, $current_batches);
	}

	/**
	 * Reads batch data under the given batch field name.
	 * 
	 * @param 	string 	$field 	The batch field name.
	 * 
	 * @return 	array
	 */
	protected function readBatchData($field = '')
	{
		// the configuration field name
		$field = $field ?: 'hospedajes_batches_' . date('Yn');

		// return batches from the current field
		return VBOFactory::getConfig()->getArray($field, []);
	}

	/**
	 * Filters the given list of batch data by date and/or type.
	 * 
	 * @param 	string 	$date 	The exact date to filter for.
	 * @param 	string 	$type 	Filter by report type ("PV", "HR").
	 * 
	 * @return 	array
	 */
	protected function filterBatchData(array $data, $date = '', $type = '')
	{
		// return the filtered batches, if any
		return array_filter($data, function($batch) use ($date, $type) {
			$date_valid = true;
			$type_valid = true;

			if ($date) {
				$date_valid = (bool) preg_match("/^" . preg_quote($date) . "/", $batch['op_date'] ?? '');
			}

			if ($type) {
				$type_valid = !strcasecmp($type, $batch['rp_type'] ?? '');
			}

			return $date_valid && $type_valid;
		});
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
		$ptype = VikRequest::getString('type', '', 'request');

		// build report type name
		if ($ptype === 'checkin') {
			$export_name = 'Partes_de_viajeros';
		} else {
			$export_name = 'Reservas_de_hospedaje';
		}

		$this->setExportCSVFileName($export_name . '-' . $this->reportName . '-' . str_replace('/', '_', $pfromdate) . '-' . str_replace('/', '_', $ptodate) . '.xml');
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
