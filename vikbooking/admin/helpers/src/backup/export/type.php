<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Backup export type interface.
 * 
 * @since 1.5
 */
interface VBOBackupExportType
{
	/**
	 * Returns a readable name of the export type.
	 * 
	 * @return 	string
	 */
	public function getName();

	/**
	 * Returns a readable description of the export type.
	 * 
	 * @return 	string
	 */
	public function getDescription();

	/**
	 * Configures the backup director.
	 * 
	 * @param 	VBOBackupExportDirector  $director
	 * 
	 * @return 	void
	 */
	public function build(VBOBackupExportDirector $director);
}
