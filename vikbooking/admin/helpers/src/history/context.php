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
 * This interface can be used to detect the area where the changes have been applied.
 * 
 * @since 1.18 (J) - 1.8 (WP)
 */
interface VBOHistoryContext
{
    /**
     * Returns the foreign key to link an item to an external context.
     * 
     * @return  int
     */
    public function getID();

    /**
     * Returns the alias to identify the context type.
     * 
     * @return  string
     */
    public function getAlias();
}
