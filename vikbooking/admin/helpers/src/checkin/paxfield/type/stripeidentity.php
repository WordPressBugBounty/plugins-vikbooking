<?php
/** 
 * @package     VikBooking
 * @subpackage  checkin_paxfield_type_stripeidentity
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Defines the handler for a pax field of type "stripeidentity".
 * 
 * @since 	1.18.2 (J) - 1.8.2 (WP)
 */
final class VBOCheckinPaxfieldTypeStripeidentity extends VBOCheckinPaxfieldType
{
	/**
	 * The container of this field should have a precise class.
	 * 
	 * @var 	string
	 */
	protected $container_class_attr = 'vbo-checkinfield-stripeidentity-wrap';

	/** @var array */
	private $images = [];

	/**
	 * Returns the payment parameters for most recent Stripe instance found, if any.
	 * 
	 * @return 	array 	Stripe params or empty array.
	 */
	public function getStripeAPIKeys()
	{
		$dbo = JFactory::getDbo();
		$dbo->setQuery(
			$dbo->getQuery(true)
				->select($dbo->qn('params'))
				->from($dbo->qn('#__vikbooking_gpayments'))
				->where($dbo->qn('file') . ' = ' . $dbo->q('stripe'))
				->order($dbo->qn('id') . ' DESC')
		);

		$data = $dbo->loadResult();

		if (!$data) {
			return [];
		}

		return (array) json_decode($data, true);
	}
	
	/**
	 * Retrieves data from the API using the provided URL and configuration.
	 * Enqueues a message if there is an error during the request.
	 *
	 * @param string $api_url The API endpoint URL to retrieve data from.
	 * @param array  $config  The configuration for the API request, including headers and post data.
	 *
	 * @return mixed The response from the API.
	 */
	private function getAPIData($api_url, $config)
	{
		$http = new JHttp;

		$headers = $config['headers'] ?? [];
		$method = 'get';
		$body = null;

		if (isset($config['post'])) {
			$method = 'post';
			
			// if the header indicates JSON, encode the body as JSON
			if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'application/json') !== false) {
				$body = json_encode($config['post']);
			} else {
				// Otherwise, use the body as form-encoded
				$body = $config['post'];
			}
		}

		try {
			$response = ($method == 'get') ? $http->get($api_url, $headers) : $http->post($api_url, $body, $headers);
		} catch (Exception $e) {
			JFactory::getApplication()->enqueueMessage(
				sprintf("Error in request %s to %s: %s", $method, $api_url, $e->getMessage()),
				'error'
			);
			return;
		}

		return $response;
	}

	/**
	 * Validates the file extension of the identity file object.
	 * Enqueues a message if the file extension is invalid.
	 *
	 * @param string $file_extension The file extension to validate.
	 */
	private function validateIdentityFileObjExtension($file_extension)
	{
		$allowed_types = ['jpg', 'jpeg', 'png'];

		// check if the file extension is valid and accepted
		if (!in_array($file_extension, $allowed_types)) {
			// if not, show the error message to the frontend
			JFactory::getApplication()->enqueueMessage(
				sprintf("File type not valid: %s. Allowed types are: %s", $file_extension, implode(', ', $allowed_types)),
				'error'
			);
			return;
		}
	}

	/**
	 * Downloads the identity data from the given URL using the provided configuration.
	 * Enqueues a message if there is an error during the download or if the JSON is invalid.
	 *
	 * @param string $url               The API endpoint URL to download the identity data from.
	 * @param array  $config            The configuration for the API request, including headers and post data.
	 * @param bool   $decode_as_array   Whether to decode the JSON response as an associative array (default: false).
	 *
	 * @return mixed The decoded identity data.
	 */
	private function downloadIdentityData($url, $config, $decode_as_array = false)
	{
		try {
			// retrieve the data from the API endpoint
			$identity_data = json_decode($this->getAPIData($url, $config)->body, $decode_as_array);
			
			// check if the JSON was decoded successfully
			if (json_last_error() !== JSON_ERROR_NONE) {
				// Invalid JSON received: %s Data: %s
				JFactory::getApplication()->enqueueMessage(
					sprintf("Invalid JSON received: %s Data: %s", json_last_error_msg(), print_r($this->getAPIData($url, $config)->body, true)),
					'error'
				);
				return;
			}

			return $identity_data;
		} catch (Exception $e) {
			// adding the $e to the Exception constructor to preserve the stack trace
			// Error downloading identity data: %s
			JFactory::getApplication()->enqueueMessage(
				sprintf("Error downloading identity data: %s", $e->getMessage()),
				'error'
			);
			return;
		}
	}


	/**
	 * Generates a unique file name for the identity document based on the booking, customer, and document data.
	 * Enqueues a message if required data is missing or if the file extension is not valid.
	 *
	 * @param array  $booking    The booking data.
	 * @param array  $customer   The customer data.
	 * @param string $document   The document identifier.
	 * @param array  $file_obj   The file object containing the type and size of the file.
	 *
	 * @return string The generated file name.
	 */
	private function generateFileName($booking, $customer, $document, $file_obj)
	{
		// retrieve the session or order ID
		$sid = empty($booking['sid']) && !empty($booking['idorderota']) ? $booking['idorderota'] : $booking['sid'];

		// check if the required data is present
		if (empty($booking['id']) || empty($customer['id']) || empty($sid) || empty($document) || empty($file_obj['type'])) {
			JFactory::getApplication()->enqueueMessage('Missing required data for filename generation.');
			return;
		}

		// build a unique file name using the hash of the document
		$hash = substr(md5($document), 0, 8);

		// get the extension of the file
		$file_extension = $file_obj['type'];

		// make sure the file extension is valid and accepted
		$this->validateIdentityFileObjExtension($file_extension);

		/**
		 * build the file name
		 * the file name will be in the format: booking_id_sid_customer_id_hash.file
		 * e.g. 20_67890_54321_abcd1234.jpg
		 * where 20 is the booking ID, 67890 is the session ID
		 * 54321 is the customer ID, and abcd1234 is the hash of the document
		 */	
		$file_name = implode('_', [$booking['id'], $sid, $customer['id'], $hash]) . '.' . $file_extension;

		return $file_name;
	}

	/**
	 * Stores the Identity photos in the database and saves them locally.
	 * Enqueues a message if there is an error during the download or saving process.
	 *
	 * @param string $report_id The ID of the verification report from Stripe.
	 */
	private function storeStripePhotos($report_id)
	{
		$app = JFactory::getApplication();
		$stripe_keys = $this->getStripeAPIKeys();
		$booking = $this->field->getBooking();
		$customer = VikBooking::getCPinInstance()->getCustomerFromBooking($booking['id']);

		// Set the Authorization header
		$config = [
			'headers' => [
				'Authorization' => 'Bearer ' . $stripe_keys['restricted_key'],
			]
		];
				
		try {
			// Download the verification report from Stripe
			$report_data = $this->downloadIdentityData("https://api.stripe.com/v1/identity/verification_reports/$report_id", $config, true);
			$documents = $report_data['document']['files'];

			// get the existing identity URIs for the booking
			$existing_identity = VikBooking::getCPinInstance()->getBookingIdentityData($booking['id']);

			// initialize the identity URIs array
			$identity_uris = [
				'identity' => (array) ($existing_identity['identity'] ?? []),
			];

			// Each file ID from the Stripe Report must be downloaded and saved locally
			foreach ($documents as $document) {
				// retrieve the file object from the Stripe API
				$file_obj_endpoint = "https://api.stripe.com/v1/files/{$document}";
				$file_obj = $this->downloadIdentityData($file_obj_endpoint, $config, true);

				// build a unique name for the image file
				$image_fname = $this->generateFileName($booking, $customer, $document, $file_obj);
				
				// build the file path and URI
				$file_path   = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'resources', 'idscans', $image_fname]);
				$file_uri    = 'resources/idscans/' . $image_fname;

				if (file_exists($file_path)) {
					// if the file already exists, skip the download
					// and add the file URI to the images array
					$this->images[] = $file_uri;
					continue;
				}

				// If the file does not exist, we need to download it
				// Retrieve the image data from the file object
				$file_data = $this->getAPIData($file_obj['url'], $config);

				if ($file_data->code != 200) {
					$file_data = json_decode($file_data->body, true);
					/** 
					 * If the file data contains an error, silently print the error message
					 * and return to avoid further processing
					 * this could happen if the file was not found or if there was an error during the download
					 * we also print the request log URL to help the user to debug the issue
					 * the request log URL is provided by the Stripe API to help debug issues with the API requests
					 */
					$message = sprintf("Error downloading file: %s \nType error: %s. \nTo consult the log, please visit: %s", 
						$file_data['error']['message'], 
						$file_data['error']['type'], 
						$file_data['error']['request_log_url']
					);

					JFactory::getApplication()->enqueueMessage($message, 'error');
					return;
				}

				// get the file size
				$file_data = $file_data->body;
				$file_size = strlen($file_data);

				if ( empty($file_data) || ($file_size < $file_obj['size'] || $file_size > $file_obj['size']) || $file_size < 1024 ) {
					/** 
					 * If the file data is empty or the file size is not equal to the expected size, show the error message
					 * this could happen if the file was not downloaded correctly or if the file is corrupted
					 * we also check that the file size is not smaller than 1KB (1024 bytes) to avoid saving empty files
					*/
					JFactory::getApplication()->enqueueMessage(
						sprintf("Downloaded file data seems invalid. Size expected: %s bytes. Size received: %s bytes.", $file_obj['size'], $file_size),
						'error'
					);
					return;
				}

				// Save the file locally
				if (!JFile::write($file_path, $file_data)) {
					// If the file could not be saved, show an error message
					$error_msg = sprintf("An error occurred while saving %s in %s", $image_fname, $file_path);
					JFactory::getApplication()->enqueueMessage($error_msg, 'error');
					return;
				}
				
				// To avoid duplicates, check if the file URI is already in the booking identity data
				// If not, add it to the booking identity data
				if (!in_array($file_uri, $identity_uris['identity'])) {
					$identity_uris['identity'][] = $file_uri;
				}

				// Save the file URI in the booking identity data
				VikBooking::getCPinInstance()->setBookingIdentityData(
					$booking['id'],
					$identity_uris,
					true, // merge = true
				);

				// Add the file URI to the images array
				$this->images[] = $file_uri;
			}
		} catch (Exception $e) {
			JFactory::getApplication()->enqueueMessage(
				sprintf("An error occurred while storing the identity photos: %s", $e->getMessage()),
				'error'
			);
			return;
		}

	}

	/**
	 * Creates a new Stripe identity verification session for the current booking and room number.
	 * Enqueues a message if there is an error creating the session or if the room number is not set.
	 *
	 * @param int   $room_number  The room number to associate with the session.
	 *
	 * @return ?\Stripe\VerificationSession The created verification session.
	 */
	private function createIdentitySession($room_number) {
		// get the booking record involved
		$booking = $this->field->getBooking();

		// retrive the api keys to create a new Stripe client
		$api_keys = $this->getStripeAPIKeys();
		if (!$api_keys) {
			return;
		}

		// get the room info for the current session
		$room_info = $this->field->getBookingRooms()[$room_number];

		// prepare the email and  phone to use for the verification session
		// if the guest customer data is not available, use a default email address and phone number
		// since the verification session requires them
		$customer_email = ($booking['custmail'] ?? '') ?: 'user@example.com';
		$customer_phone = ($booking['phone'] ?? '') ?: '3333333333';
		
		// get the customer data
		$guest_customer = $this->field->getGuestData();

		// if the guest customer data is available, use it to set the email and phone fields
		if ($guest_customer) {
			if (!empty($guest_customer['email'])) {
				$customer_email = $guest_customer['email'];
			} else if (!empty($guest_customer['phone'])) {
				$customer_phone = $guest_customer['phone'];
			}
		}

		// initialize the Stripe client with the secret key
		$stripe = new \Stripe\StripeClient($api_keys['secretkey']);

		// get the already existing sessions for the booking
		$sessions = VikBooking::getCPinInstance()->getBookingVerificationData($booking['id']);

		// check the session for the current room
		if ($sessions || !empty($sessions['verification'][$room_number])) {
			try {
				// if the session exists, retrieve it
				$session = $stripe->identity->verificationSessions->retrieve($sessions['verification'][$room_number]);
				return $session;
			} catch (Exception $e) {
				// Silently ignore if the session does not exist as the retrieve method will throw an exception
			}
		}

		try {
			// otherwise, create a new session
			$session = $stripe->identity->verificationSessions->create([
				// provide the type of check to perform
				'type' => 'document',
				// set the metadata to associate the session with the booking
				'metadata' => [
					'booking_id'  => $booking['id'] . '#pre-checkin',
					'room_id'     => $room_info['idroom'],
					'room_name'   => $room_info['name'],
					'room_index'  => $room_number,
				],
				// pass along the customer email and phone number
				'provided_details' => [
					'email' => $customer_email,
					'phone' => $customer_phone,
				],
			]);

			// check if the room number is set (it could be 0, so we check if it is not empty or null)
			if (isset($room_number) && $room_number !== '') {
				// and save it into the databae
				$sessions['verification'][$room_number] = $session->id;
			} else {
				// without the room number, the session cannot be associated with a specific room
				// so we show a message to avoid saving a not-associated session
				JFactory::getApplication()->enqueueMessage('Missing the room number. It is required to save the session.');
				return;
			}

			// save the session in the database
			VikBooking::getCPinInstance()->setBookingVerificationData($booking['id'], $sessions);
		} catch (Exception $e) {
			JFactory::getApplication()->enqueueMessage(
				sprintf("Error creating a new session: %s.", $e->getMessage()),
				'error'
			);
			return;
		}

		return $session;
	}
	
	
    /**
     * @inheritDoc
     */
    public function render()
    {
		// get the admin URI
		$admin_uri = VBO_ADMIN_URI;

        // get the field unique ID
        $field_id = $this->getFieldIdAttr();

        // get the current guest number (fields could be displayed only to a specific guest number)
        $guest_number = $this->field->getGuestNumber();

        if ($guest_number > 1) {
            // we are parsing the Nth guest of a room, and we only want this field for the room main guest
            // return an empty string to let the system understand that this field should not be displayed
            return '';
        }

        // get the Stripe configuration
		$api_keys = $this->getStripeAPIKeys();
		if (!$api_keys) {
			// no configuration available
			return '';
		}

		// load the Identify SDK depending on the platform
		if (VBOPlatformDetection::isWordPress()) {
			JLoader::import('Stripe.Stripe', VIKSTRIPE_DIR);	
		} else {
			$path = JPath::clean(JPATH_SITE . '/plugins/e4j/stripe/libraries/Stripe/init.php');
			if (is_file($path)) {
				include_once $path;
			}
		}
		

        // get the field class attribute
		$pax_field_class = $this->getFieldClassAttr();

		// push an additional class name for the verification pax
		$all_field_class = explode(' ', $pax_field_class);
		$all_field_class[] = 'stripe__identity';
		$pax_field_class = implode(' ', $all_field_class);

        // get field name attribute
        $name = $this->getFieldNameAttr();

		// load the JS Stripe SDK
		JHtml::fetch('script', 'https://js.stripe.com/v3/');
		
		// get the room number for the current session
		$room_number = $this->field->getRoomIndex();

		// create the Stripe identity session for the current room
		$identity_session = $this->createIdentitySession($room_number);

		// get the public key to use for the Stripe SDK
		$public_key = $api_keys['pubkey'];

		// check the status of the identity session
		switch ($identity_session->status) {
			// the session is still being processed
			case 'processing':
				// check if the verification session has been attempted before
				if (JFactory::getApplication()->input->getBool('stripe_identity_attempt')) {
					// if the user has already attempted to verify their identity, we display a message
					JFactory::getApplication()->enqueueMessage(
						sprintf('Room #%d: it looks like it will take some time to process your request. Please wait a few minutes and try again.', $room_number),
						'warning'
					);
				}

				// define the HTML content for the field
				$processing_title = 'Processing your identity verification request';
				$processing_paragraph = 'We are currently processing your request. This may take a few minutes. Please do not close this page or refresh it until the process is complete.';
				$processing_click_button = 'Click the button below to refresh the page and check the status of your request.';
				$button_value = JText::translate('VBO_IDVERIF_PROCESSING_BUTTON_VALUE');

				$field_html = <<<HTML
					<div class="identity-processing-box">
						<div class="identity-status-icon">⏳</div>
						<h3>$processing_title</h3>
						<p>$processing_paragraph</p>
						<p>$processing_click_button</p>

						<button class="refresh-button" onclick="location.reload()">$button_value</button>
					</div>

					<style>
						.identity-processing-box {
							background-color: #f9f9f9;
							border: 1px solid #ddd;
							padding: 20px;
							border-radius: 12px;
							text-align: center;
							max-width: 400px;
							margin: 30px auto;
							font-family: 'Poppins', sans-serif;
							box-shadow: 0 4px 12px rgba(0,0,0,0.05);
						}

						.identity-status-icon {
							font-size: 32px;
							margin-bottom: 10px;
						}

						.identity-processing-box h3 {
							margin: 10px 0;
							color: #333;
						}

						.identity-processing-box p {
							font-size: 14px;
							color: #666;
						}

						.refresh-button {
							background-color: #5b8677;
							color: white;
							border: none;
							padding: 10px 20px;
							border-radius: 25px;
							font-size: 14px;
							cursor: pointer;
							margin-top: 15px;
							transition: background 0.3s ease;
						}

						.refresh-button:hover {
							background-color: #3d6357;
						}
					</style>

HTML;
				break;
			case 'requires_input':
				// the session requires input from the user
				$button_value = JText::translate('VBO_IDVERIF_VERIFY_IDENTITY_BUTTON_VALUE');

				if (JFactory::getApplication()->input->getBool('stripe_identity_attempt')) {
					// if the user has already attempted to verify their identity, we display a message
					JFactory::getApplication()->enqueueMessage(
						'Room #' . $room_number . ': An error occurred while verifying your identity, or the verification process has not yet been attempted. Please try again.',
						'error'
					);
				}

				// compose HTML content for the field
				$field_html = <<<HTML
					<div class="pax-field {$pax_field_class} identity__block" id="{$field_id}">
						 <div class="verify-button-wrapper">
							<button type="button" class="stripe__identity__btn verify-button" data-session-id="{$identity_session->id}" data-room-number="{$room_number}">
								<span class="icon">🔒</span> $button_value
							</button>
						</div>
					</div>

					<script type="text/javascript">
						VBOCore.DOMLoaded(function() {
							const button = document.querySelector('#{$field_id} .stripe__identity__btn');
							button.addEventListener('click', async function() {

								// create a new Stripe instance with the public key
								let stripe   = Stripe('{$public_key}');

								// start the verification process
                				let result   = await stripe.verifyIdentity('{$identity_session->client_secret}');
								
								// check if the result contains an error
								if (result.error) 
								{
									alert(new Error('Invalid response from server:' + result.error.code));
									location.reload();
								};

								// if the verification process did not faced errors, redirect to the same page with an attempt parameter
								let url = new URL(window.location);
								url.searchParams.set('stripe_identity_attempt', 1);
								window.location.href = url.toString();
								
							});
						});
					</script>

					<style>
						.verify-button{
							background-color:rgb(0, 0, 0);
							color: white;
							padding: 12px 22px;
							border: none;
							border-radius: 20px;
							font-family: 'Poppins', sans-serif;
							font-size: 15px;
							display: inline-flex;
							align-items: center;
							gap: 8px;
							cursor: pointer;
							transition: background 0.3s ease;
						}
						.verify-button:hover {
							background-color: #5B8677;
						}

					</style>
HTML;
				break;
			case 'verified':
				// check if the verification session has been attempted before
				if (JFactory::getApplication()->input->getBool('stripe_identity_attempt')) {
					// if the user has already attempted to verify their identity, we display a message
					JFactory::getApplication()->enqueueMessage(
						'Room #' . $room_number . ': Your identity has been verified successfully.',
						'success'
					);
				}

				// the session has been verified
				$button_value = JText::translate('VBO_IDVERIF_SEE_DOCUMENTS_BUTTON_VALUE');
				$modal_title = json_encode(JText::translate('VBO_IDVERIF_MODAL_TITLE'));
				try {
					/**
					 * store the Stripe photos in the database
					 * and populate the $this->images array with the URIs of the images
					 * that will be displayed in the modal
					 */
					$this->storeStripePhotos($identity_session['last_verification_report']);
				} catch (Exception $e) {
					// if an error occurs, close the document and display the error message
					VBOHttpDocument::getInstance(JFactory::getApplication())->close(500, $e->getMessage());
				}

				// prepare the images to be displayed in the modal
				$images = json_encode($this->images);

				$field_html = <<<HTML
					<div class="pax-field {$pax_field_class}" id="{$field_id}">
						<input type="hidden" name="{$name}" value="{$identity_session->id}" />
						<a class="verified-button" onclick='vboStripeIdentityOpenDocumentModal({$images})'>
							<span class="icon">✅</span> $button_value
						</a>
					</div>

					<script type="text/javascript">

						/*	
						 * Creates a DOM element based on the provided data.
						 * 
						 * @param {Object} data - The data to create the DOM element.
						 * @returns {HTMLElement|Text} The created DOM element or text node.
						 */
						function vboStripeIdentityCreateNodoElement(data) {
							if (!data.tag && data.text) 
							{
								return document.createTextNode(data.text);
							}

							let nodo = document.createElement(data.tag);
							
							if (!data.attributes || data.attributes.length == 0) {
								return nodo;
							}

							for (let i = 0; i < data.attributes.length; i++) {
								nodo.setAttribute(data.attributes[i].name, data.attributes[i].value);
							}
							
							return nodo;
						}

						/**
						 * Opens a modal to display the identity documents.
						 * 
						 * @param {Array} session_images - An array of image URIs to be displayed in the modal.
						 */
						function vboStripeIdentityOpenDocumentModal(session_images) {
							// create a wrapper for the modal content
							let nodoWrapper = vboStripeIdentityCreateNodoElement( {tag: 'DIV', attributes: [ {name: 'class', value: 'document-modal-wrapper'} ] } );
							nodoWrapper.style.textAlign = 'center';

							// create an IMG element for each image in the session_images array
							for (const image of session_images) {
								let img = vboStripeIdentityCreateNodoElement( {tag: 'IMG', attributes: [ {name: 'src', value: '$admin_uri' + image} ] } );
								img.style.margin = '0px 5px';
								nodoWrapper.appendChild(img);
							}

							// create a modal body with the nodoWrapper
							let modalBody = VBOCore.displayModal({
								suffix: 'vbo-pax-field-identity',
								extra_class: 'vbo-modal-large',
								title: $modal_title,
								lock_scroll: true,
								loading_event: 'vbo-pax-field-identity-loading',
								dismiss_event: 'vbo-pax-field-identity-dismiss',
								onDismiss: () => {
									if (nodoWrapper) {
										nodoWrapper.remove();
									}
								},
							});
							modalBody.append(nodoWrapper);
						}
					</script>

					<style>
						.verified-button {
							background-color:rgb(127, 196, 164);
							color: white;
							padding: 12px 22px;
							border: none;
							border-radius: 20px;
							font-family: 'Poppins', sans-serif;
							font-size: 15px;
							display: inline-flex;
							align-items: center;
							gap: 8px;
							cursor: pointer;
							transition: background 0.3s ease;
						}

						.verified-button:hover {
							background-color: #5B8677;
						}

					</style>

HTML;
				break;
		}

		// return the necessary HTML string to display the field
        return $field_html;
    }

	/**
     * Validates the current pax field data.
     * 
     * @override
     */
	public function validateGuestRegistrationData()
	{
		$app = JFactory::getApplication();
		$info = $app->input->get('guests', [], 'array');

		// get the current guest number (fields could be displayed only to a specific guest number)
        $guest_number = $this->field->getGuestNumber();

        if ($guest_number > 1) {
            // all is good
            return;
        }

        // get the Stripe configuration
		$api_keys = $this->getStripeAPIKeys();
		if (!$api_keys) {
			// no configuration available, all good with the validation
			return;
		}
		
		// get the booking record involved
		$booking = $this->field->getBooking();
		
		// get the total number of the rooms involved
		$rooms_number = $this->field->getTotalRooms();
				
		if (!$booking) {
			// do not raise any exception if data is missing
			return;
		}
	
		// get the identity sessions ids
		$stripe_session = VikBooking::getCPinInstance()->getBookingVerificationData($booking['id']);

		if (empty($stripe_session)) {
			// Please, complete the Stripe verification process.
			$message = JText::translate('VBO_IDVERIF_ERROR_MISSING_VERIFICATION_SESSION');
			VBOHttpDocument::getInstance($app)->close(500, $message);
		}
		
		// check if the Stripe session is in array
		for ($i = 0; $i < $rooms_number; $i++) {
			// check if the current room has a verification session
			$verification_session = $info[$i][1]['verification'];
			if (!in_array($verification_session, $stripe_session['verification'])) {
				// the Stripe value was manipulated in some way and it is not possible to find it among the saved ones
				$message = JText::translate('VBO_IDVERIF_ERROR_INVALID_SESSION');
				VBOHttpDocument::getInstance($app)->close(500, $message);
			}
		}
				
		// all good
		return;
	}
}
