<?php
/**
 * @package     VikBooking
 * @subpackage  com_vikbooking
 * @author      E4J srl
 * @copyright   Copyright (C) 2026 E4J srl. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 * @link        https://vikwp.com
 */

defined('ABSPATH') or die('No script kiddies please!');

/**
 * Class handler for admin widget "inquiries chat".
 * 
 * @since 1.18.8 (J) - 1.8.8 (WP)
 */
class VikBookingAdminWidgetInquiriesChat extends VBOChatWidgetAdmin
{
    /**
     * A list holding all the supported chat categories.
     * 
     * @var string[]
     * @since 1.8.8
     */
    protected $involvedCategories = ['session'];

    /**
     * Class constructor will define the widget name and identifier.
     */
    public function __construct()
    {
        // call parent constructor
        parent::__construct();

        $this->widgetName = JText::translate('VBO_W_INQUIRIESCHAT_TITLE');
        $this->widgetDescr = JText::translate('VBO_W_INQUIRIESCHAT_DESCR');
        $this->widgetId = basename(__FILE__, '.php');

        $this->widgetIcon = '<i class="' . VikBookingIcons::i('headset') . '"></i>';
        $this->widgetStyleName = 'green';
    }

    /**
     * @inheritDoc
     */
    public function preload()
    {
        // require FontAwesome brands
        VikBookingIcons::loadRemoteAssets();
        
        // then preload parent widget
        return parent::preload();
    }

    /**
     * Checks for new notifications by using the previous preloaded watch-data.
     * 
     * @param   ?VBONotificationWatchdata   $watch_data The preloaded watch-data object.
     * 
     * @return  array                       Data object to watch next and notifications array.
     * 
     * @see     preload()
     */
    public function getNotifications(?VBONotificationWatchdata $watch_data = null)
    {
        // do not observe notifications
        return [null, null];
    }
}
