<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      Alessio Gaggii - e4j - Extensionsforjoomla.com
 * @copyright   Copyright (C) 2020 e4j - Extensionsforjoomla.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Critical Dates handler class for Vik Booking.
 *
 * @since 	1.13.5
 */
class VikBookingCriticalDates
{
	/**
	 * The singleton instance of the class.
	 *
	 * @var VikBookingCriticalDates
	 */
	protected static $instance = null;

	/**
	 * Class constructor is protected.
	 *
	 * @see 	getInstance()
	 */
	protected function __construct()
	{
		
	}

	/**
	 * Returns the global Critical Dates object, either
	 * a new instance or the existing instance
	 * if the class was already instantiated.
	 *
	 * @return 	self 	a new instance of the class.
	 */
	public static function getInstance()
	{
		if (is_null(static::$instance)) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Checks whether a precise note exists on the given date.
	 * 
	 * @param 	string 		$ymd 		the date string in Y-m-d format.
	 * @param 	int 		$idroom 	the ID of the room involved.
	 * @param 	int 		$subunit 	the index of the sub-unit (0 by default).
	 * @param 	string 		$key 		the key name of the note.
	 * @param 	boolean 	$get 		whether to get the found record.
	 *
	 * @return 	mixed 		True/array if festivity exists, false otherwise.
	 */
	public function dayNoteExists($ymd, $idroom, $subunit = 0, $key = '', $get = false)
	{
		// vars validation
		$idroom  = intval($idroom);
		$subunit = intval($subunit);

		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_critical_dates` WHERE `dt`=" . $dbo->quote($ymd) . " AND `idroom`={$idroom} AND `subunit`={$subunit};";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			return false;
		}
		$note = $dbo->loadAssoc();
		
		$notes_info = json_decode($note['info']);
		if (!$notes_info) {
			return false;
		}
		
		// update this information in the array in case it needs to be returned
		$note['info'] = $notes_info;

		// seek for the requested note
		foreach ($notes_info as $n) {
			if (empty($key) || (!empty($n->type) && $n->type == $key)) {
				return $get ? $note : true;
			}
		}
		return false;
	}

	/**
	 * Stores/updates a note for the given date, idroom and subunit. If a record for the given data exists, then the
	 * note is added to or updated in the current record. Otherwise a new record is created.
	 * 
	 * @param 	array 		$note 		the array note to store.
	 * @param 	string 		$ymd 		the date string in Y-m-d format.
	 * @param 	int 		$idroom 	the ID of the room involved.
	 * @param 	int 		$subunit 	the index of the sub-unit (0 by default).
	 *
	 * @return 	boolean 	True if the new record is stored or updated, false otherwise.
	 */
	public function storeDayNote($note, $ymd, $idroom = 0, $subunit = 0)
	{
		// vars validation
		$idroom  = intval($idroom);
		$subunit = intval($subunit);
		if (!is_array($note) || !count($note) || (empty($note['name']) && empty($note['descr']))) {
			return false;
		}
		if (!isset($note['type'])) {
			$note['type'] = 'custom';
		}
		if (!isset($note['ts'])) {
			$note['ts'] = time();
		}
		if (!empty($note['name'])) {
			// make sure the "name" does not contain commas, which may be used by JS as separator in the readable notes data attribute
			$note['name'] = trim(str_replace(',', '', $note['name']));
		}

		$dbo = JFactory::getDbo();
		$q = "SELECT * FROM `#__vikbooking_critical_dates` WHERE `dt`=" .$dbo->quote($ymd) . " AND `idroom`={$idroom} AND `subunit`={$subunit}";
		$dbo->setQuery($q);
		$dbo->execute();
		if (!$dbo->getNumRows()) {
			// create a new record
			$q = "INSERT INTO `#__vikbooking_critical_dates` (`dt`, `idroom`, `subunit`, `info`) VALUES (".$dbo->quote($ymd).", ".$dbo->quote($idroom).", ".$dbo->quote($subunit).", ".$dbo->quote(json_encode(array($note))).");";
			$dbo->setQuery($q);
			$dbo->execute();

			return ((int)$dbo->insertid() > 0);
		}
		
		// update current record by pushing the note into the existing info array of objects
		$notes  = $dbo->loadAssoc();
		$notes_arr  = json_decode($notes['info']);
		array_push($notes_arr, (object)$note);
		$q = "UPDATE `#__vikbooking_critical_dates` SET `info`=".$dbo->quote(json_encode($notes_arr))." WHERE `id`={$notes['id']};";
		$dbo->setQuery($q);
		$dbo->execute();

		return ((int)$dbo->getAffectedRows() > 0);
	}

	/**
	 * Deletes a specific note for the given date, room ID and subunit.
	 * If the day will not contain anymore notes, then
	 * the whole record will be deleted.
	 * 
	 * @param 	int 		$index 		the array index of the note.
	 * @param 	string 		$ymd 		the date string in Y-m-d format.
	 * @param 	int 		$idroom 	the room ID involved.
	 * @param 	int 		$subunit 	the subunit of the room ID.
	 * @param 	string 		$type 		the key name of the note ('custom' for manual entries).
	 *
	 * @return 	boolean 	True if the fest is removed, false otherwise.
	 */
	public function deleteDayNote($index, $ymd, $idroom = 0, $subunit = 0, $type = 'custom')
	{
		$record = $this->dayNoteExists($ymd, $idroom, $subunit, $type, true);
		if (!$record) {
			return false;
		}

		$drop_record = false;
		if (isset($record['info'][$index])) {
			// note was found by array-index
			array_splice($record['info'], $index, 1);
			if (!count($record['info'])) {
				// this day has got no more notes
				$drop_record = true;
			}
		} else {
			// seek for the requested note by type
			foreach ($record['info'] as $k => $note) {
				if (empty($type) || (!empty($note->type) && $note->type == $type)) {
					// note found
					array_splice($record['info'], $k, 1);
					if (!count($record['info'])) {
						// this day has got no more notes
						$drop_record = true;
					}
					break;
				}
			}
		}
		
		$dbo = JFactory::getDbo();
		if ($drop_record) {
			$q = "DELETE FROM `#__vikbooking_critical_dates` WHERE `dt`=" . $dbo->quote($ymd) . " AND `idroom`={$idroom} AND `subunit`={$subunit}";
		} else {
			$q = "UPDATE `#__vikbooking_critical_dates` SET `info`=".$dbo->quote(json_encode($record['info']))." WHERE `dt`=" . $dbo->quote($ymd) . " AND `idroom`={$idroom} AND `subunit`={$subunit}";
		}
		$dbo->setQuery($q);
		$dbo->execute();

		return true;
	}

	/**
	 * Loads the room day notes of any type and returns and associative
	 * array indexed by date with decoded notes information.
	 * 
	 * @param 	string 		$from_ymd 	the optional minimum date in Y-m-d (defaults to today).
	 * @param 	string 		$to_ymd 	the optional maximum date in Y-m-d (defaults to none).
	 * @param 	int 		$idroom 	the optional room id filter
	 * @param 	int 		$subunit 	the optional room index filter
	 *
	 * @return 	array 		the list of festivities found in Vik Booking.
	 */
	public function loadRoomDayNotes($from_ymd = null, $to_ymd = null, $idroom = null, $subunit = null)
	{
		$dbo = JFactory::getDbo();

		$rdnotes = array();
		if (empty($from_ymd)) {
			$from_ymd = date('Y-m-d');
		}

		$clauses = array();
		array_push($clauses, "`dt`>=" . $dbo->quote($from_ymd));

		if (!empty($to_ymd)) {
			array_push($clauses, "`dt`<=" . $dbo->quote($to_ymd));
		}
		if ($idroom !== null && is_int($idroom)) {
			array_push($clauses, "`idroom`=" . (int)$idroom);
		}
		if ($subunit !== null && is_int($subunit)) {
			array_push($clauses, "`subunit`=" . (int)$subunit);
		}
		
		$q = "SELECT * FROM `#__vikbooking_critical_dates` WHERE " . implode(' AND ', $clauses) . " ORDER BY `dt` ASC;";
		$dbo->setQuery($q);
		$dbo->execute();
		if ($dbo->getNumRows()) {
			$all_notes = $dbo->loadAssocList();
			// make sure to decode all notes infos
			foreach ($all_notes as $k => $v) {
				$v['info'] = json_decode($v['info']);

				$keyid = $v['dt'] . '_' . (int)$v['idroom'] . '_' . (int)$v['subunit'];
				
				$rdnotes[$keyid] = $v;
			}
		}

		return $rdnotes;
	}

}
