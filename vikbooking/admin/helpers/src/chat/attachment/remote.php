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
 * This class holds the information of a chat attachment as remote file.
 * 
 * @since 1.8.8
 */
class VBOChatAttachmentRemote extends VBOChatAttachment
{
    /**
     * Class constructor.
     * 
     * @param  string  $url      The URI of the remote file.
     * @param  string  $path     The path where the attachment should be downloaded.
     * @param  string  $headers  Optional headers to download the file.
     */
    public function __construct(string $url, string $path, array $headers = [])
    {
        // download remote file
        $file = $this->download($url, $headers);

        // construct through parent
        parent::__construct([
            'path' => $path,
            'name' => $file['name'],
        ]);

        // save file locally
        $this->save($file['buffer']);
    }

    /**
     * Downloads the file locally.
     * 
     * @param   string  $url      The URI of the remote file.
     * @param   array   $headers  The request headers.
     * 
     * @return  array   An associative array holding the file details.
     */
    protected function download(string $url, array $headers)
    {
        // default to same file name used by the URL with no query arguments
        $filename = explode('?', basename($url))[0];

        if (preg_match("/\.(php|html)$/i", $filename)) {
            // do not register this kind of files
            throw new \DomainException('Cannot accept files with this extension', 403);
        }

        // download remote file content from URL
        $response = (new JHttp)->get($url, $headers, $timeout = 30);

        if (!in_array($response->code, [200, 201, 202])) {
            // the request returned an invalid status code, propagate error
            throw new UnexpectedValueException($response->body ?: 'Error', $response->code ?: 500);
        }

        // make sure the file name has got a file extension
        if (!preg_match('/\.[a-z0-9]{3,}$/i', $filename)) {
            // get content-type from response headers (try both lower-case and Pascal-Case notations)
            $contentType = $response->headers['content-type'] ?? ($response->headers['Content-Type'] ?? null);

            if (is_array($contentType)) {
                // in case of multiple headers, fetch only the first available one
                $contentType = array_shift($contentType);
            }

            if (!$this->checkMimeType($contentType)) {
                throw new DomainException('Unsupported [' . $contentType . '] MIME type for the provided file.', 415);
            }

            // append file extension to file name from detected mime-type
            $filename .= '.' . $this->getFileExtension($contentType);
        }

        return [
            'name' => $filename,
            'buffer' => (string) $response->body,
        ];
    }

    /**
     * Saves the file locally.
     * 
     * @param   string  $buffer
     * 
     * @return  void
     */
    protected function save(string $buffer)
    {
        if (!JFile::write($this->getPath(), $buffer)) {
            throw new RuntimeException('Unable to save attachment on disk', 403);
        }
    }

    /**
     * Check here if we are downloading a supported file.
     *
     * @param   string  $type  The MIME type to check.
     *
     * @return  string  True if supported, false otherwise.
     */
    protected function checkMimeType(string $type)
    {
        // additional allowed document mime types
        $docMimeTypes = [
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/x-iwork-pages-sffpages',
            'application/vnd.apple.pages',
            'application/x-iwork-numbers-sffnumbers',
            'application/vnd.apple.numbers',
            'audio/ogg; codecs=opus',
        ];

        if (in_array($type, $docMimeTypes)) {
            return true;
        }

        /**
         * Accept images (png, gif, jpg), videos (mp4, quicktime) and documents.
         */
        return preg_match("/^image\/(png|jpe?g|gif)|video\/(mp4|quicktime|mov)|audio\/ogg|application\/(pdf|ms-excel|rtf)$/", $type);
    }

    /**
     * Returns the supported file extension from the given mime type.
     * 
     * @param   string  $type  The file mime type.
     * 
     * @return  string  The supported file extension.
     */
    protected function getFileExtension(string $type)
    {
        // special mime types
        $specialMimeTypes = [
            'text/plain' => 'txt',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-iwork-pages-sffpages' => 'pages',
            'application/vnd.apple.pages' => 'pages',
            'application/x-iwork-numbers-sffnumbers' => 'numbers',
            'application/vnd.apple.numbers' => 'numbers',
            'video/quicktime' => 'mov',
        ];

        if (isset($specialMimeTypes[$type])) {
            // supported special extension
            return $specialMimeTypes[$type];
        }

        // default to second mime type chunk
        $type = explode('/', $type)[1];
        // get rid of secondary header information (e.g. audio/ogg; codecs=opus)
        $type = explode(';', $type)[0];

        return trim($type);
    }
}
