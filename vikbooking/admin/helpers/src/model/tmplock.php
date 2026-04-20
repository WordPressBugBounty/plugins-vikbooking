<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2026 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking temporary locked records model.
 *
 * @since   1.18.8 (J) - 1.8.8 (WP)
 */
class VBOModelTmplock extends VBOMvcModel
{
    /**
     * The database table name.
     * 
     * @var string
     */
    protected $tableName = '#__vikbooking_tmplock';

    /**
     * Deletes temporarily locked records for a given booking ID.
     * 
     * @param   int    $bookingId    The booking ID for which records should be deleted.
     * 
     * @return  bool   True on success, false otherwise.
     */
    public function deleteFromBooking(int $bookingId)
    {
        $db = JFactory::getDbo();

        $db->setQuery(
            $db->getQuery(true)
                ->delete($db->qn('#__vikbooking_tmplock'))
                ->where($db->qn('idorder') . ' = ' . $bookingId)
        );
        $db->execute();

        return (bool) $db->getAffectedRows();
    }
}
