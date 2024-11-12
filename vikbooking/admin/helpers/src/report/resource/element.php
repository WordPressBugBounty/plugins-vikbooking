<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      Alessio Gaggii - E4J srl
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * Report resource element implementation to define a file obtained through
 * the execution of a report custom action.
 * 
 * @since   1.17.1 (J) - 1.7.1 (WP)
 */
class VBOReportResourceElement extends JObject implements VBOReportResource
{
    /**
     * @inheritDoc
     */
    public function getUrl()
    {
        return (string) $this->get('url', '');
    }

    /**
     * @inheritDoc
     */
    public function getPath()
    {
        return (string) $this->get('path', '');
    }

    /**
     * @inheritDoc
     */
    public function getSummary()
    {
        return (string) $this->get('summary', '');
    }
}
