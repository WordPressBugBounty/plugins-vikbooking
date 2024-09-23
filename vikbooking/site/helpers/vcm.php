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
 * Helper class to invoke the Channel Manager update requests.
 */
class VboVcmInvoker
{
	/**
	 * @var  array
	 */
	public $oids = [];

	/**
	 * @var  array
	 */
	public $orig_statuses = [];

	/**
	 * @var  string
	 */
	public $sync_type = 'new';

	/**
	 * @var  array
	 */
	public $orig_booking;

	/**
	 * @var  string
	 */
	private $error = '';

	/**
	 * @var  bool
	 */
	private $result = false;

	/**
	 * Class constructor will attempt to require a class from VCM.
	 */
	public function __construct()
	{
		if (!class_exists('synchVikBooking')) {
			require_once(VCM_SITE_PATH . DIRECTORY_SEPARATOR . "helpers" . DIRECTORY_SEPARATOR . "synch.vikbooking.php");
		}
	}

	/**
	 * Sets the booking IDs for the sync
	 * 
	 * @param 	array 	$oids
	 * 
	 * @return 	self
	 */
	public function setOids($oids)
	{
		$this->oids = $oids;

		return $this;
	}
	
	/**
	 * Sets the type of synchronization for VCM
	 * 
	 * @param 	string	$set_sync_type
	 * 
	 * @return 	self
	 */
	public function setSyncType($set_sync_type)
	{
		$this->sync_type = !in_array($set_sync_type, array('new', 'modify', 'cancel')) ? 'new' : $set_sync_type;

		return $this;
	}

	/**
	 * Method needed to inject the previous/original status of each booking
	 * before making any sort of update. Useful to inform VCM that the booking was
	 * pending (stand-by) to support Request to Book reservations with some channels.
	 * 
	 * @param 		array 	$orig_statuses 	array of original booking statuses.
	 * 
	 * @return 		self
	 * 
	 * @since 		1.14 (J) - 1.4.0 (WP)
	 * @requires 	VCM >= 1.8.0
	 */
	public function setOriginalStatuses($orig_statuses)
	{
		if (empty($orig_statuses)) {
			return $this;
		}

		if (is_scalar($orig_statuses)) {
			$orig_statuses = [$orig_statuses];
		}

		if (!is_array($orig_statuses)) {
			return $this;
		}

		$this->orig_statuses = $orig_statuses;

		return $this;
	}

	/**
	 * Sets the original booking array
	 * 
	 * @param mixed 	$obooking 	JSON+URL encoded string or array
	 * @param boolean 	$decode
	 */
	public function setOriginalBooking($obooking, $decode = false)
	{
		if (!empty($obooking)) {
			$original_booking = $decode === true ? json_decode(urldecode($obooking), true) : $obooking;
			if (is_array($original_booking) && $original_booking) {
				$this->orig_booking = $original_booking;
			}
		}
		return $this;
	}

	/**
	 * Launch the synchronization with VCM
	 */
	public function doSync()
	{
		if (!is_array($this->oids) || !$this->oids) {
			$this->setError('oids is empty.');
			return $this->result;
		}
		
		if ($this->sync_type == 'new') {
			foreach ($this->oids as $okey => $oid) {
				if (empty($oid)) {
					continue;
				}
				$vcm = new SynchVikBooking($oid);
				$vcm->setSkipCheckAutoSync();

				/**
				 * We attempt to inject the previous status of the booking, if set.
				 * For example, bookings that become confirmed could have been in a
				 * pending (stand-by) status, and VCM should be aware of this change
				 * because some channels may need extra actions.
				 * 
				 * @since 		1.14 (J) - 1.4.0 (WP)
				 * @requires 	VCM >= 1.8.0
				 */
				if (!empty($this->orig_statuses[$okey]) && method_exists($vcm, 'setBookingPreviousStatus')) {
					$vcm->setBookingPreviousStatus($this->orig_statuses[$okey]);
				}

				$rq_rs = $vcm->sendRequest();
				$this->result = $this->result || $rq_rs ? true : $this->result;
			}
		} elseif ($this->sync_type == 'modify') {
			// only one Booking ID per request as the original booking is transmitted in JSON format or as an array if called via PHP execution.
			if (is_array($this->orig_booking) && $this->orig_booking) {
				foreach ($this->oids as $okey => $oid) {
					if (empty($oid)) {
						continue;
					}
					$vcm = new SynchVikBooking($oid);
					$vcm->setSkipCheckAutoSync();
					$vcm->setFromModification($this->orig_booking);
					$this->result = $vcm->sendRequest();
					break;
				}
			} else {
				$this->setError('orig_booking is empty.');
			}
		} elseif ($this->sync_type == 'cancel') {
			foreach ($this->oids as $okey => $oid) {
				if (empty($oid)) {
					continue;
				}
				$vcm = new SynchVikBooking($oid);
				$vcm->setSkipCheckAutoSync();
				$vcm->setFromCancellation(['id' => $oid]);
				$rq_rs = $vcm->sendRequest();
				$this->result = $this->result || $rq_rs ? true : $this->result;
			}
		}

		if ($this->result !== true && !$this->getError()) {
			$this->setError('VCM returned errors');
		}

		return $this->result;
	}

	/**
	 * Sets the class error variable
	 * 
	 * @param 	string 	$err_str
	 */
	private function setError($err_str)
	{
		$this->error .= (string)$err_str;
	}

	/**
	 * Returns the class error variable
	 */
	public function getError()
	{
		return $this->error;
	}
}
