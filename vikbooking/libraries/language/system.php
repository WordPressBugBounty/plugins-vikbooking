<?php
/** 
 * @package   	VikBooking - Libraries
 * @subpackage 	language
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2018 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.language.handler');

/**
 * Switcher class to translate the VikBooking plugin system languages.
 *
 * @since 	1.0
 */
class VikBookingLanguageSystem implements JLanguageHandler
{
	/**
	 * Checks if exists a translation for the given string.
	 *
	 * @param 	string 	$string  The string to translate.
	 *
	 * @return 	string 	The translated string, otherwise null.
	 */
	public function translate($string)
	{
		$result = null;

		/**
		 * Translations go here.
		 * @tip Use 'TRANSLATORS:' comment to attach a description of the language.
		 */

		switch ($string)
		{
			/**
			 * MVC ERRORS
			 */

			case 'FATAL_ERROR':
				$result = __('Error', 'vikbooking');
				break;

			case 'CONTROLLER_FILE_NOT_FOUND_ERR':
				$result = __('The controller does not exist.', 'vikbooking');
				break;

			case 'CONTROLLER_CLASS_NOT_FOUND_ERR':
				$result = __('The controller [%s] classname does not exist.', 'vikbooking');
				break;

			case 'CONTROLLER_INVALID_INSTANCE_ERR':
				$result = __('The controller must be an instance of JController.', 'vikbooking');
				break;

			case 'CONTROLLER_PROTECTED_METHOD_ERR':
				$result = __('You cannot call JController reserved methods.', 'vikbooking');
				break;

			case 'TEMPLATE_VIEW_NOT_FOUND_ERR':
				$result = __('Template view not found.', 'vikbooking');
				break;

			case 'RESOURCE_AUTH_ERROR':
				$result = __('You are not authorised to access this resource.', 'vikbooking');
				break;

			/**
			 * Invalid token for CSRF protection.
			 * 
			 * @see  	this key will actually terminate the whole process.
			 * @since 	1.4.1
			 */
			case 'JINVALID_TOKEN':
				wp_nonce_ays(JSession::getFormTokenAction());
				break;

			/**
			 * NATIVE ACL RULES
			 */

			case 'VBOACLMENUTITLE':
				$result = __('Vik Booking - Access Control List', 'vikbooking');
				break;

			case 'JACTION_ADMIN':
				$result = __('Configure ACL & Options', 'vikbooking');
				break;

			case 'JACTION_ADMIN_COMPONENT_DESC':
				$result = __('Allows users in the group to edit the options and permissions of this plugin.', 'vikbooking');
				break;

			case 'JACTION_MANAGE':
				$result = __('Access Administration Interface', 'vikbooking');
				break;

			case 'JACTION_MANAGE_COMPONENT_DESC':
				$result = __('Allows users in the group to access the administration interface for this plugin.', 'vikbooking');
				break;

			case 'JACTION_CREATE':
				$result = __('Create', 'vikbooking');
				break;

			case 'JACTION_CREATE_COMPONENT_DESC':
				$result = __('Allows users in the group to create any content in this plugin.', 'vikbooking');
				break;

			case 'JACTION_DELETE':
				$result = __('Delete', 'vikbooking');
				break;

			case 'JACTION_DELETE_COMPONENT_DESC':
				$result = __('Allows users in the group to delete any content in this plugin.', 'vikbooking');
				break;

			case 'JACTION_EDIT':
				$result = __('Edit', 'vikbooking');
				break;

			case 'JACTION_EDIT_COMPONENT_DESC':
				$result = __('Allows users in the group to edit any content in this plugin.', 'vikbooking');
				break;

			case 'CONNECTION_LOST':
				// translation provided by wordpress
				$result = __('Connection lost or the server is busy. Please try again later.');
				break;

			/**
			 * ACL Form
			 */

			case 'ACL_SAVE_SUCCESS':
				$result = __('ACL saved.', 'vikbooking');
				break;

			case 'ACL_SAVE_ERROR':
				$result = __('An error occurred while saving the ACL.', 'vikbooking');
				break;

			case 'JALLOWED':
				$result = __('Allowed', 'vikbooking');
				break;

			case 'JDENIED':
				$result = __('Denied', 'vikbooking');
				break;

			case 'JACTION':
				$result = __('Action', 'vikbooking');
				break;

			case 'JNEW_SETTING':
				$result = __('New Setting', 'vikbooking');
				break;

			case 'JCURRENT_SETTING':
				$result = __('Current Setting', 'vikbooking');
				break;

			/**
			 * TOOLBAR BUTTONS
			 */

			case 'JTOOLBAR_NEW':
				$result = __('New', 'vikbooking');
				break;

			case 'JTOOLBAR_EDIT':
				$result = __('Edit', 'vikbooking');
				break;

			case 'JTOOLBAR_BACK':
				$result = __('Back', 'vikbooking');
				break;

			case 'JTOOLBAR_PUBLISH':
				$result = __('Publish', 'vikbooking');
				break;

			case 'JTOOLBAR_UNPUBLISH':
				$result = __('Unpublish', 'vikbooking');
				break;

			case 'JTOOLBAR_ARCHIVE':
				$result = __('Archive', 'vikbooking');
				break;

			case 'JTOOLBAR_UNARCHIVE':
				$result = __('UnArchive', 'vikbooking');
				break;

			case 'JTOOLBAR_DELETE':
				$result = __('Delete', 'vikbooking');
				break;

			case 'JTOOLBAR_TRASH':
				$result = __('Trash', 'vikbooking');
				break;

			case 'JTOOLBAR_APPLY':
				$result = __('Save', 'vikbooking');
				break;

			case 'JTOOLBAR_SAVE':
				$result = __('Save & Close', 'vikbooking');
				break;

			case 'JTOOLBAR_SAVE_AND_NEW':
				$result = __('Save & New', 'vikbooking');
				break;

			case 'JTOOLBAR_SAVE_AS_COPY':
				$result = __('Save as Copy', 'vikbooking');
				break;

			case 'JTOOLBAR_CANCEL':
				$result = __('Cancel', 'vikbooking');
				break;

			case 'JTOOLBAR_OPTIONS':
				$result = __('Permissions', 'vikbooking');
				break;

			case 'JTOOLBAR_SHORTCODES':
				$result = __('Shortcodes', 'vikbooking');
				break;

			/**
			 * FILTERS
			 */

			case 'JOPTION_SELECT_LANGUAGE':
				$result = __('- Select Language -', 'vikbooking');
				break;

			case 'JOPTION_SELECT_TYPE':
				$result = __('- Select Type -', 'vikbooking');
				break;

			case 'JSEARCH_FILTER_SUBMIT':
				$result = __('Search', 'vikbooking');
				break;

			/**
			 * PAGINATION
			 */

			case 'JPAGINATION_ITEMS':
				$result = __('%d items', 'vikbooking');
				break;

			case 'JPAGINATION_PAGE_OF_TOT':
				// @TRANSLATORS: e.g. 1 of 12
				$result = _x('%d of %s', 'e.g. 1 of 12', 'vikbooking');
				break;

			/**
			 * MENU ITEMS - FIELDSET TITLES
			 */

			case 'COM_MENUS_REQUEST_FIELDSET_LABEL':
				$result = __('Details', 'vikbooking');
				break;

			case 'COM_MENUS_CONTACTS_FIELDSET_LABEL':
				$result = __('Contacts', 'vikbooking');
				break;

			/**
			 * GENERIC
			 */
			
			case 'JYES':
				$result = __('Yes');
				break;

			case 'JNO':
				$result = __('No');
				break;

			case 'JALL':
				$result = __('All', 'vikbooking');
				break;

			case 'JID':
			case 'JGRID_HEADING_ID':
				$result = __('ID', 'vikbooking');
				break;

			case 'JCREATEDBY':
				$result = __('Created By', 'vikbooking');
				break;

			case 'JCREATEDON':
				$result = __('Created On', 'vikbooking');
				break;

			case 'JNAME':
				$result = __('Name', 'vikbooking');
				break;

			case 'JTYPE':
				$result = __('Type', 'vikbooking');
				break;

			case 'JSHORTCODE':
				$result = __('Shortcode', 'vikbooking');
				break;

			case 'VBO_SHORTCODE_PARENT_FIELD':
				$result = __('Parent Shortcode', 'vikbooking');
				break;

			case 'JLANGUAGE':
				$result = __('Language', 'vikbooking');
				break;

			case 'JPOST':
				$result = __('Post', 'vikbooking');
				break;

			case 'PLEASE_MAKE_A_SELECTION':
				$result = __('Please first make a selection from the list.', 'vikbooking');
				break;

			case 'JSEARCH_FILTER_CLEAR':
				$result = __('Clear', 'vikbooking');
				break;

			case 'JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT':
				$result = __('Maximum upload size: <strong>%s</strong>', 'vikbooking');
				break;

			case 'NO_ROWS_FOUND':
			case 'JGLOBAL_NO_MATCHING_RESULTS':
				$result = __('No rows found.', 'vikbooking');
				break;

			case 'VBOSHORTCDSMENUTITLE':
				$result = __('Vik Booking - Shortcodes', 'vikbooking');
				break;

			case 'VBONEWSHORTCDMENUTITLE':
				$result = __('Vik Booking - New Shortcode', 'vikbooking');
				break;

			case 'VBOEDITSHORTCDMENUTITLE':
				$result = __('Vik Booking - Edit Shortcode', 'vikbooking');
				break;

			case 'VBO_SYS_LIST_LIMIT':
				$result = __('Number of items per page:');
				break;

			case 'JERROR_ALERTNOAUTHOR':
				$result = __('You are not authorised to access this resource', 'vikbooking');
				break;

			case 'JLIB_APPLICATION_SAVE_SUCCESS':
				$result = __('Item saved.', 'vikbooking');
				break;

			case 'JLIB_APPLICATION_ERROR_SAVE_FAILED':
				$result = __('Save failed with the following error: %s', 'vikbooking');
				break;

			/**
			 * Media manager.
			 */

			case 'JMEDIA_PREVIEW_TITLE':
				$result = __('Image preview', 'vikbooking');
				break;

			case 'JMEDIA_CHOOSE_IMAGE':
				$result = __('Choose an image', 'vikbooking');
				break;

			case 'JMEDIA_CHOOSE_IMAGES':
				$result = __('Choose one or more images', 'vikbooking');
				break;

			case 'JMEDIA_SELECT':
				$result = __('Select', 'vikbooking');
				break;

			case 'JMEDIA_UPLOAD_BUTTON':
				$result = __('Pick or upload an image', 'vikbooking');
				break;

			case 'JMEDIA_CLEAR_BUTTON':
				$result = __('Clear selection', 'vikbooking');
				break;

			/**
			 * Relative dates
			 */

			case 'JLIB_HTML_DATE_RELATIVE_LESSTHANAMINUTE':
				$result = __('Less than a minute ago.', 'vikbooking');
				break;

			case 'JLIB_HTML_DATE_RELATIVE_MINUTES':
				$result = __('%d minutes ago.', 'vikbooking');
				break;

			case 'JLIB_HTML_DATE_RELATIVE_MINUTES_1':
				$result = __('a minute ago.', 'vikbooking');
				break;

			case 'JLIB_HTML_DATE_RELATIVE_HOURS':
				$result = __('%d hours ago.', 'vikbooking');
				break;

			case 'JLIB_HTML_DATE_RELATIVE_HOURS_1':
				$result = __('an hour ago.', 'vikbooking');
				break;

			case 'JLIB_HTML_DATE_RELATIVE_DAYS':
				$result = __('%d days ago.', 'vikbooking');
				break;

			case 'JLIB_HTML_DATE_RELATIVE_DAYS_1':
				$result = __('a day ago.', 'vikbooking');
				break;

			case 'JLIB_HTML_DATE_RELATIVE_WEEKS':
				$result = __('%d weeks ago.', 'vikbooking');
				break;

			case 'JLIB_HTML_DATE_RELATIVE_WEEKS_1':
				$result = __('a week ago.', 'vikbooking');
				break;

			/**
			 * FSE / Blocks
			 */

			case 'COM_MENUS_BASIC_FIELDSET_LABEL':
				$result = __('Main Settings', 'vikbooking');
				break;

			case 'COM_MODULES_FIELD_TITLE_LABEL':
				$result = __('Title', 'vikbooking');
				break;

			case 'COM_MODULES_FIELD_TITLE_DESC':
				$result = __('Optional widget title to display.', 'vikbooking');
				break;

			case 'COM_MODULES_PREVIEW_NOT_AVAIL':
				$result = __('Preview is not available', 'vikbooking');
				break;

			/**
			 * Pro version warning
			 */
			
			case 'VBOPROVEXPWARNUPD':
				$result = __('The Pro license for VikBooking has expired. Do not install any updates or you will downgrade the plugin to the Free version.', 'vikbooking');
				break;
		}

		return $result;
	}
}
