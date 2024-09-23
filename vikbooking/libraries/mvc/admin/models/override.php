<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2023 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.mvc.models.form');

/**
 * VikBooking plugin Override model.
 * @wponly
 *
 * @since 1.6.5
 * @see   JModelForm
 */
class VikBookingModelOverride extends JModelForm
{
	/**
	 * @override
	 * Saves the override.
	 *
	 * @param   array  &$data  The array containing the override data.
	 *
	 * @return  bool   True on success, otherwise false.
	 */
	public function save(&$data)
	{
		// make sure we have a destination file
		if (empty($data['file']))
		{
			$this->setError('Missing destination file');

			return false;
		}

		// make sure we have a code to write
		if (!isset($data['code']))
		{
			$this->setError('Missing override code');

			return false;
		}

		// generate override
		return JFile::write($data['file'], $data['code']);
	}

	/**
	 * Deletes the specified records.
	 *
	 * @param   mixed  $ids  The PK value (or a list of values) of the record(s) to remove.
	 *
	 * @return  bool   True if at least a record has been removed, otherwise false.
	 */
	public function delete($ids)
	{
		$deleted = false;

		// iterate files
		foreach ((array) $ids as $file)
		{
			$src = $file;

			// in case the override doesn't exist, try to
			// look for an unpublished override
			if (!JFile::exists($file))
			{
				// use the unpublished version
				$src = JPath::clean(dirname($file) . '/__' . basename($file));
			}

			// try to delete the file
			$deleted = JFile::delete($src) || $deleted;
		}

		return $deleted;
	}

	/**
	 * Changes the state of the specified records.
	 *
	 * @param   mixed  $ids  The PK value (or a list of values) of the record(s) to update.
	 *
	 * @return  mixed  True if at least a record has been affected, otherwise false.
	 *                 In case of a single record, the new path will be returned.
	 */
	public function publish($ids, $state)
	{
		$changed = $dest = false;

		$ids = (array) $ids;

		// iterate files
		foreach ($ids as $file)
		{
			$dest = dirname($file);
			$name = basename($file);

			if ($state)
			{
				// start from unpublished file
				$src  = JPath::clean($dest . '/__' . $name);
				// restore default override
				$dest = $file;
			}
			else
			{
				// start from existing override
				$src  = $file;
				// move to unpublished status
				$dest = JPath::clean($dest . '/__' . $name);
			}

			// try to rename the file
			$changed = rename($src, $dest) || $changed;
		}

		// in case of a single file, return its new path
		if ($changed && count($ids) == 1)
		{
			return $dest;
		}

		return $changed;
	}
}
