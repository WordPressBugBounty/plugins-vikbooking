<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2025 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking service controller (admin).
 *
 * @since   1.18.3 (J) - 1.8.3 (WP)
 */
class VikBookingControllerService extends JControllerAdmin
{
    /**
     * AJAX endpoint to upload one or more files.
     * 
     * @return  void
     * 
     * @see     VBOParamsRendering
     */
    public function upload()
    {
        $app = JFactory::getApplication();

        if (!JSession::checkToken()) {
            // missing CSRF-proof token
            VBOHttpDocument::getInstance($app)->close(403, JText::translate('JINVALID_TOKEN'));
        }

        $files        = $app->input->files->get('vbo_files', [], 'array');
        $allowed_exts = $app->input->getString('allowed_types', '');
        $safe_name    = $app->input->getBool('safe_file_name', false);

        if (!$files) {
            VBOHttpDocument::getInstance($app)->close(400, 'No files to process for upload.');
        }

        if (VBOPlatformDetection::isWordPress()) {
            VikBookingLoader::import('update.manager');
        }

        if ($files['tmp_name'] ?? null) {
            // unusual single-array structure
            $files = [$files];
        }

        // result default properties
        $result = [
            'processed' => 0,
            'paths'     => [],
            'urls'      => [],
            'fileNames' => [],
        ];

        // upload base path and URI
        $upload_base_path = implode(DIRECTORY_SEPARATOR, [VBO_ADMIN_PATH, 'resources', 'pmsdata', '']);
        $upload_base_uri  = VBO_ADMIN_URI . 'resources/pmsdata/';

        // file type and extension default filtering rule
        $allowed_types_list = [
            // images
            'png',
            'jpg',
            'jpeg',
            'webp',
            'bmp',
            'heic',
            // archives
            'zip',
            'rar',
            // documents
            'pdf',
            'doc',
            'docx',
            'rtf',
            'odt',
            'pages',
            'xls',
            'xlsx',
            'csv',
            'ods',
            'numbers',
            'txt',
            'md',
            // public certificates
            'crt',
            'cer',
            'pem',
            'der',
            'p7b',
            // private keys/chains
            'key',
            'pem',
            'p12',
            'pfx',
            // certificate requests
            'csr',
            'req',
        ];

        // check if only specific file types must be accepted
        if ($allowed_exts) {
            // convert the filtering rule string into an array
            $allowed_exts = array_values(array_filter(explode(',', $allowed_exts)));
            // get the known (safe) file types requested
            $known_exts = array_intersect($allowed_exts, $allowed_types_list);
            if ($known_exts) {
                // given filtering rule is accepted
                $allowed_types_list = $known_exts;
            }
        }

        // stringify files filtering rule
        $allowed_types_str = implode(',', $allowed_types_list);

        // process the file(s) to upload
        foreach ($files as $file) {
            try {
                // increase counter
                $result['processed']++;

                if ($safe_name) {
                    // modify at runtime the original file name
                    if (preg_match("/(.*?)(\.[0-9a-z]{2,})$/i", basename($file['name']), $match)) {
                        // extract file name and extension
                        $orig_filename = $match[1];
                        $orig_fileext  = $match[2];
                        // generate a new safe file name
                        $file['name'] = uniqid(rand(1, 99999) . '_') . $orig_fileext;
                    }
                }

                // attempt to upload the file and obtain the result information
                $uploaded = (array) VikBooking::uploadFileFromRequest($file, $upload_base_path, $allowed_types_str);

                // push uploaded file path
                $result['paths'][] = $uploaded['path'];

                // push uploaded file uri
                $result['urls'][] = $upload_base_uri . $uploaded['filename'];

                // push uploaded file name
                $result['fileNames'][] = $uploaded['filename'];

                if (VBOPlatformDetection::isWordPress()) {
                    VikBookingUpdateManager::triggerUploadBackup($uploaded['path']);
                }
            } catch (Exception $e) {
                // do nothing
            }
        }

        // send the response to output
        VBOHttpDocument::getInstance($app)->json($result);
    }
}
