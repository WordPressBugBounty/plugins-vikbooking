<?php
/** 
 * @package   	VikBooking
 * @subpackage 	core
 * @author    	E4J s.r.l.
 * @copyright 	Copyright (C) 2019 E4J s.r.l. All Rights Reserved.
 * @license  	http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link 		https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

JLoader::import('adapter.mvc.controllers.admin');

/**
 * VikBooking Sampledata controller.
 *
 * @since 	1.3.9
 * @see 	JControllerAdmin
 */
class VikBookingControllerSampledata extends JControllerAdmin
{
	/**
	 * AJAX endpoint to load the sample data available.
	 * JSON encoded string outputted upon result.
	 *
	 * @return 	void
	 */
	public function load()
	{
		if (!JFactory::getUser()->authorise('core.admin', 'com_vikbooking')) {
			VBOHttpDocument::getInstance()->close(403, JText::translate('JERROR_ALERTNOAUTHOR'));
		}

		$data = $this->getSampleData();
		
		echo json_encode($data);
		exit;
	}

	/**
	 * AJAX endpoint to install one sample data ID.
	 * JSON encoded result outputted or exception thrown.
	 *
	 * @return 	void
	 * 
	 * @throws 	Exception
	 */
	public function install()
	{
		if (!JSession::checkToken()) {
			throw new Exception(JText::translate('JINVALID_TOKEN'), 403);
		}

		if (!JFactory::getUser()->authorise('core.admin', 'com_vikbooking')) {
			throw new Exception(JText::translate('JERROR_ALERTNOAUTHOR'), 403);
		}

		$sample_data_id = VikRequest::getInt('sample_data_id', 0, 'request');
		if (empty($sample_data_id)) {
			throw new Exception('Empty Sample Data ID', 500);
		}

		// load all sample data available
		$data = $this->getSampleData();

		$sample_data_obj = null;

		foreach ($data as $sdata) {
			if (!is_object($sdata) || empty($sdata->id)) {
				continue;
			}
			if ((int)$sdata->id == $sample_data_id) {
				$sample_data_obj = new JRegistry($sdata);
				break;
			}
		}

		if (!$sample_data_obj) {
			throw new Exception(sprintf('Sample Data ID [%s] not found', $sample_data_id), 404);
		}

		// response object
		$response = new stdClass;
		$response->status = 0;
		$response->error  = '';
		
		// download and install selected sample data
		try {
			$res = $this->downloadSampleData($sample_data_obj);
			if ($res) {
				$response->status = 1;
			}
		} catch (Exception $e) {
			$response->error = $e->getMessage();
		}

		if ($response->status) {
			// silently add shortcodes to new WordPress pages
			$this->addShortcodesToPages($sample_data_obj);
		}
		
		echo json_encode($response);
		exit;
	}

	/**
	 * Returns a list of supported sample data.
	 *
	 * @return 	array  A list of sample data.
	 */
	private function getSampleData()
	{
		// build transient key
		$transient = 'vikbooking_sampledata_' . md5(VIKBOOKING_SOFTWARE_VERSION);

		// get cached sample data list
		$data = get_transient($transient);

		if ($data) {
			// return cached transient
			$data = json_decode($data);
			
			return is_array($data) ? $data : array();
		}

		// default empty list
		$data = array();

		// instantiate HTTP transport
		$http = new JHttp();

		// build end-point URI
		$uri = 'https://vikwp.com/api/?task=sampledata.list';

		// build post data
		$post = array(
			'sku'     => 'vbo',
			'version' => VIKBOOKING_SOFTWARE_VERSION,
		);

		// load sample data from server
		$response = $http->post($uri, $post);

		if ($response->code == 200) {
			// decode response
			$data = json_decode($response->body);

			// cache sample data list for an hour
			set_transient($transient, json_encode($data), HOUR_IN_SECONDS);
		}

		return $data;
	}

	/**
	 * Downloads and installs one sample data.
	 *
	 * @param 	JRegistry  $data  The sample data registry object.
	 *
	 * @return 	boolean
	 * 
	 * @uses 	installSampleData()
	 */
	private function downloadSampleData($data)
	{
		// instantiate HTTP transport
		$http = new JHttp();

		// get selected sample data
		$id = $data->get('id');

		JLoader::import('adapter.filesystem.folder');

		// get temporary dir
		$tmp = get_temp_dir();

		// clean temporary path
		$tmp = rtrim(JPath::clean($tmp), DIRECTORY_SEPARATOR);

		// make sure the folder exists
		if (!is_dir($tmp)) {
			throw new Exception(sprintf('Temporary folder [%s] does not exist', $tmp), 404);
		}

		// make sure the temporary folder is writable
		if (!wp_is_writable($tmp)) {
			throw new Exception(sprintf('Temporary folder [%s] is not writable', $tmp), 403);
		}

		// download end-point
		$url = 'https://vikwp.com/api/?task=sampledata.download';

		// init HTTP transport
		$http = new JHttp();

		// build sample data file name
		$packname = 'sampledata-' . uniqid();

		// build request headers
		$headers = array(
			// turn on stream to push body within a file
			'stream'   => true,
			// define the filepath in which the data will be pushed
			'filename' => $tmp . DIRECTORY_SEPARATOR . $packname . '.zip',
			// make sure the request is non blocking
			'blocking' => true,
			// force timeout to 60 seconds
			'timeout'  => 60,
		);

		// build post data
		$body = array(
			'id' => $id,
		);

		// make connection with VikWP server
		$response = $http->post($url, $body, $headers);

		if ($response->code != 200) {
			// raise error returned by VikWP
			throw new Exception($response->body, $response->code);
		}

		// make sure the file has been saved
		if (!JFile::exists($headers['filename'])) {
			throw new Exception('ZIP package could not be saved on disk', 404);
		}

		// create destination folder for extracted elements
		$dest = $tmp . DIRECTORY_SEPARATOR . $packname;

		// make sure the destination folder doesn't exist
		if (JFolder::exists($dest)) {
			// remove it before proceeding with the extraction
			JFolder::delete($dest);
		}

		// import archive class handler
		JLoader::import('adapter.filesystem.archive');

		// the package was downloaded successfully, let's extract it (onto TMP folder)
		$extracted = JArchive::extract($headers['filename'], $dest);

		// we no longer need the archive
		JFile::delete($headers['filename']);

		if (!$extracted) {
			// an error occurred while extracting the files
			throw new Exception(sprintf('Cannot extract files to [%s]', $tmp), 500);
		}

		// make sure the folder is intact
		if (!JFolder::exists($dest)) {
			// impossible to access the extracted elements
			throw new Exception(sprintf('Cannot access extracted elements from [%s] folder', $dest), 404);
		}

		$error = null;

		try {
			// run sample data installation
			$this->installSampleData($dest);
		} catch (Exception $e) {
			// safely catch error to finalize process
			$error = $e;
		}

		// process complete, clean up the temporary folder before exiting
		JFolder::delete($dest);

		// in case of error, propagate it after deleting the extracted folder
		if ($error) {
			throw $error;
		}

		return true;
	}

	/**
	 * Interprets the manifest contained within the folder
	 * to install the sample data.
	 *
	 * @param 	string 	$folder  The sample data folder.
	 *
	 * @return 	void
	 *
	 * @throws 	Exception
	 * 
	 * @uses 	uninstallShortcodes()
	 * @uses 	installSqlRole()
	 * @uses 	installFilesRole()
	 */
	private function installSampleData($folder)
	{
		// load manifest.json file
		if (!is_file($folder . DIRECTORY_SEPARATOR . 'manifest.json')) {
			// missing JSON manifest
			throw new Exception('Manifest file not found', 404);
		}

		// open manifest file in read mode
		$manifestFile = fopen($folder . DIRECTORY_SEPARATOR . 'manifest.json', 'r');

		$manifest = '';

		// load manifest content
		while (!feof($manifestFile)) {
			$manifest .= fread($manifestFile, 8192);
		}

		// close file
		fclose($manifestFile);

		// decode JSON
		$manifest = json_decode($manifest);

		if (!$manifest) {
			// unable to JSON decode manifest
			throw new Exception('Manifest file contains an invalid JSON', 500);
		}

		// validate every instruction of the manifest before executing any of them,
		// the installation is otherwise rejected as a whole
		$this->validateManifest($manifest, $folder);

		$dbo = JFactory::getDbo();

		// look for uninstall queries
		if (isset($manifest->uninstall)) {
			// iterate queries to clean any existing records
			foreach ($manifest->uninstall as $q) {
				try {
					// check if we are uninstalling the shortcodes
					if (preg_match("/vikbooking_wpshortcodes/i", $q)) {
						// uninstall the existing shortcodes
						$this->uninstallShortcodes();
					}

					// launch query
					$dbo->setQuery($q);
					$dbo->execute();
				} catch (Exception $e) {
					// malformed query, suppress error and go ahead
					if (VIKBOOKING_DEBUG) {
						// propagate error in case of debug mode enabled
						throw $e;
					}
				}
			}
		}

		// look for installers
		if (isset($manifest->installers)) {
			// iterate installers
			foreach ($manifest->installers as $install) {
				try {
					// switch case role to invoke the proper installation method
					switch ($install->role) {
						case 'sql':
						case 'insert':
							$this->installSqlRole($install->data);
							break;

						case 'media':
						case 'folder':
							$this->installFilesRole($install->data->destination, $install->data->files, $folder);
							break;
					}
				} catch (Exception $e) {
					// malformed role, suppress error and go ahead
					if (VIKBOOKING_DEBUG) {
						// propagate error in case of debug mode enabled
						throw $e;
					}
				}
			}
		}
	}

	/**
	 * Executes the specified queries.
	 *
	 * @param 	mixed 	$queries  Either a query string or an array.
	 *
	 * @return 	void
	 */
	private function installSqlRole($queries)
	{
		if (!is_array($queries)) {
			$queries = array($queries);
		}

		$dbo = JFactory::getDbo();

		// iterate queries one by one
		foreach ($queries as $q) {
			$dbo->setQuery($q);
			$dbo->execute();
		}
	}

	/**
	 * Moves the files into the related folders.
	 * The destination and the files are validated again here, even
	 * if the manifest was already validated, as this is the method
	 * that writes on the file system.
	 *
	 * @param 	string 	$dest   The destination folder declared by the manifest.
	 * @param 	mixed 	$files  Either a file or an array.
	 * @param 	string 	$dir    The current directory.
	 *
	 * @return 	void
	 * 
	 * @throws 	Exception
	 */
	private function installFilesRole($dest, $files, $dir)
	{
		// load update manager class for backup/mirroring of files
		VikBookingLoader::import('update.manager');

		// copy files and create a backup copy from each file
		foreach ($this->resolveFilesRole($dest, $files, $dir) as $job) {
			// copy file in its destination
			$res = JFile::copy($job['source'], $job['target']);

			if ($res) {
				// trigger mirroring action for new file uploaded
				VikBookingUpdateManager::triggerUploadBackup($job['target']);
			}
		}
	}

	/**
	 * Returns the only directories in which a sample data package is
	 * allowed to install files. The key is the "destination" declared
	 * by the manifest, the value is the absolute path of the directory.
	 *
	 * @return 	array
	 */
	private function getAllowedFileDestinations()
	{
		return [
			'site/resources/uploads' => VBO_SITE_PATH . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'uploads',
		];
	}

	/**
	 * Returns the only file types (image type constants) that a sample
	 * data package is allowed to install, indexed by file extension.
	 *
	 * @return 	array
	 */
	private function getAllowedImageTypes()
	{
		return [
			'jpg'  => IMAGETYPE_JPEG,
			'jpeg' => IMAGETYPE_JPEG,
			'png'  => IMAGETYPE_PNG,
			'gif'  => IMAGETYPE_GIF,
			'webp' => IMAGETYPE_WEBP,
		];
	}

	/**
	 * Returns the VikBooking tables (without prefix) that a sample
	 * data package is allowed to populate or to empty.
	 *
	 * @return 	array
	 */
	private function getAllowedTables()
	{
		return [
			'adultsdiff',
			'categories',
			'characteristics',
			'dispcost',
			'iva',
			'prices',
			'rooms',
			'wpshortcodes',
		];
	}

	/**
	 * Validates the destination and the files of a "files" role, and
	 * returns the list of the copy operations that can be safely executed.
	 *
	 * - the destination must be one of the allowed directories (no path
	 *   provided by the package is ever used to build a path);
	 * - the source must be a regular file (no symlinks) found within the
	 *   extracted package;
	 * - only real images with an allowed extension are copied, any other
	 *   file (such as the index.html) is ignored.
	 *
	 * @param 	mixed 	$dest   The destination declared by the manifest.
	 * @param 	mixed 	$files  Either a file or an array.
	 * @param 	string 	$dir    The directory of the extracted package.
	 *
	 * @return 	array 	List of associative arrays (source, target).
	 *
	 * @throws 	Exception
	 */
	private function resolveFilesRole($dest, $files, $dir)
	{
		if (!is_string($dest)) {
			throw new Exception('Invalid destination declared by the sample data', 403);
		}

		$allowed = $this->getAllowedFileDestinations();

		// normalize the destination declared by the package
		$key = trim(str_replace('\\', '/', $dest), '/');

		if (!isset($allowed[$key])) {
			throw new Exception(sprintf('Destination [%s] is not allowed for sample data', $dest), 403);
		}

		$realDest = realpath($allowed[$key]);
		$realDir  = realpath($dir);

		if ($realDest === false || $realDir === false || !is_dir($realDest) || !is_dir($realDir)) {
			throw new Exception('Cannot access the sample data directories', 404);
		}

		// make sure to always parse an array
		if (!is_array($files)) {
			$files = array($files);
		}

		$types = $this->getAllowedImageTypes();
		$jobs  = array();

		foreach ($files as $file) {
			if (!is_string($file) || $file === '') {
				throw new Exception('Invalid file declared by the sample data', 403);
			}

			// the file name is the only part that will be used to build the target path
			$name = basename(str_replace('\\', '/', $file));

			// never use a name that is not strictly alphanumeric (plus dot, dash and underscore)
			if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $name)) {
				continue;
			}

			$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

			if (!isset($types[$ext])) {
				// file type not allowed (e.g. index.html), ignore it
				continue;
			}

			// refuse names containing any executable extension, e.g. "image.php.jpg"
			if (preg_match('/\.(php\d*|phtml|pht|phps|phar|cgi|pl|py|sh|asp|aspx|jsp|htaccess)(\.|$)/i', $name)) {
				continue;
			}

			$source = JPath::clean($dir . DIRECTORY_SEPARATOR . $file);

			// regular files only, no symbolic links
			if (is_link($source) || !is_file($source)) {
				throw new Exception(sprintf('Sample data file [%s] not found', $file), 404);
			}

			// the source must be found within the extracted package
			$realSource = realpath($source);

			if ($realSource === false || strpos($realSource, $realDir . DIRECTORY_SEPARATOR) !== 0) {
				throw new Exception(sprintf('Sample data file [%s] is outside the package', $file), 403);
			}

			// the content must be an image of the type declared by the extension
			$info = @getimagesize($realSource);

			if (!$info || $info[2] !== $types[$ext]) {
				throw new Exception(sprintf('Sample data file [%s] is not a valid image', $file), 403);
			}

			$jobs[] = array(
				'source' => $realSource,
				'target' => $realDest . DIRECTORY_SEPARATOR . $name,
			);
		}

		return $jobs;
	}

	/**
	 * Validates all the instructions of the manifest, without executing any.
	 *
	 * @param 	object 	$manifest  The decoded manifest.
	 * @param 	string 	$folder    The directory of the extracted package.
	 *
	 * @return 	void
	 *
	 * @throws 	Exception
	 */
	private function validateManifest($manifest, $folder)
	{
		if (!empty($manifest->uninstall)) {
			if (!is_array($manifest->uninstall)) {
				throw new Exception('Invalid uninstall section declared by the sample data', 403);
			}

			foreach ($manifest->uninstall as $q) {
				$this->assertSafeQuery($q, $install = false);
			}
		}

		if (!empty($manifest->installers)) {
			if (!is_array($manifest->installers)) {
				throw new Exception('Invalid installers section declared by the sample data', 403);
			}

			foreach ($manifest->installers as $install) {
				$role = isset($install->role) ? $install->role : '';
				$data = isset($install->data) ? $install->data : null;

				switch ($role) {
					case 'sql':
					case 'insert':
						foreach (is_array($data) ? $data : array($data) as $q) {
							$this->assertSafeQuery($q, $install = true);
						}
						break;

					case 'media':
					case 'folder':
						if (!is_object($data) || !isset($data->destination) || !isset($data->files)) {
							throw new Exception('Invalid files role declared by the sample data', 403);
						}
						$this->resolveFilesRole($data->destination, $data->files, $folder);
						break;

					default:
						// unknown roles are never executed (see installSampleData)
						break;
				}
			}
		}
	}

	/**
	 * Makes sure the query declared by the package is one of the few
	 * statements that a sample data needs, otherwise an exception is thrown:
	 *
	 * - install: INSERT INTO `#__vikbooking_{table}` (...) VALUES ...
	 * - uninstall: TRUNCATE TABLE or DELETE FROM `#__vikbooking_{table}`
	 *
	 * where {table} is one of the allowed tables. Nothing can follow the
	 * statement: the text outside the string literals is inspected to
	 * detect stacked queries, comments, sub-queries and file operations.
	 *
	 * @param 	mixed 	 $query    The query to validate.
	 * @param 	boolean  $install  True for installation queries.
	 *
	 * @return 	void
	 *
	 * @throws 	Exception
	 */
	private function assertSafeQuery($query, $install)
	{
		if (!is_string($query)) {
			throw new Exception('Invalid query declared by the sample data', 403);
		}

		$query  = trim($query);
		$tables = implode('|', array_map('preg_quote', $this->getAllowedTables()));

		if ($install) {
			$head = '/^INSERT\s+INTO\s+`#__vikbooking_(?:' . $tables . ')`\s*\([^)]*\)\s*VALUES\b/i';
		} else {
			$head = '/^(?:TRUNCATE\s+TABLE|DELETE\s+FROM)\s+`#__vikbooking_(?:' . $tables . ')`\s*;?$/i';
		}

		if (!preg_match($head, $query, $match)) {
			throw new Exception('Query declared by the sample data is not allowed', 403);
		}

		// inspect the text found after the statement head, ignoring the string literals
		$tail    = substr($query, strlen($match[0]));
		$outside = '';
		$inStr   = false;
		$len     = strlen($tail);

		for ($i = 0; $i < $len; $i++) {
			$c = $tail[$i];

			if ($inStr) {
				if ($c === '\\') {
					// escaped character
					$i++;
				} elseif ($c === "'") {
					if ($i + 1 < $len && $tail[$i + 1] === "'") {
						// escaped quote
						$i++;
					} else {
						$inStr = false;
					}
				}
			} elseif ($c === "'") {
				$inStr    = true;
				$outside .= "'";
			} else {
				$outside .= $c;
			}
		}

		if ($inStr) {
			// unterminated string literal
			throw new Exception('Query declared by the sample data is malformed', 403);
		}

		// a single semicolon is tolerated only as last character
		$outside = rtrim($outside);
		$outside = preg_replace('/;$/', '', $outside);

		// no stacked queries, comments, double quoted strings, sub-queries or file operations
		if (preg_match('/;|--|#|\/\*|\*\/|"|\b(?:SELECT|UNION|UPDATE|DROP|ALTER|GRANT|CREATE|REPLACE|OUTFILE|DUMPFILE|LOAD_FILE|SLEEP|BENCHMARK)\b/i', $outside)) {
			throw new Exception('Query declared by the sample data is not allowed', 403);
		}
	}

	/**
	 * Uninstalls all the pages that have been assigned
	 * to the existing shortcodes.
	 *
	 * @return 	void
	 */
	private function uninstallShortcodes()
	{
		// get shortcode admin model
		$model = JModel::getInstance('vikbooking', 'shortcodes');

		// get all existing shortcodes
		$shortcodes = $model->all(array('createdon', 'post_id'));

		// iterate all shortcodes found
		foreach ($shortcodes as $shortcode) {
			// make sure the shortcode has been assigned to a post
			if ($shortcode->post_id) {
				// get post details
				$post = get_post((int) $shortcode->post_id);

				// convert shortcode creation date
				$shortcode->createdon = new JDate($shortcode->createdon);
				// convert post creation date
				$post->post_date_gmt = new JDate($post->post_date_gmt);

				// compare ephocs and make sure the post was not created before the shortcode
				if ((int) $shortcode->createdon->format('U') <= (int) $post->post_date_gmt->format('U')) {
					// permanently delete post
					wp_delete_post($post->ID, $force_delete = true);
				}
			}
		}
	}


	/**
	 * Finalizes the installation of a sample data by creating one
	 * new WordPress page/post for each Shortcode installed.
	 *
	 * @param 	JRegistry  $data  The (optional) sample data chosen registry object.
	 *
	 * @return 	boolean
	 */
	private function addShortcodesToPages($data = null)
	{
		$model = JModel::getInstance('vikbooking', 'shortcodes');

		$shortcodes = $model->all();

		if (!is_array($shortcodes) || !count($shortcodes)) {
			return false;
		}

		foreach ($shortcodes as $item) {
			if (!empty($item->post_id)) {
				// shortcode already linked to a page
				continue;
			}

			/**
			 * Add a new page (we allow a WP_ERROR to be returned in case of failure).
			 * This should automatically trigger the hook that we use to link the Shortcode 
			 * to the new page/post ID, and so there's no need to update the item.
			 */
			$new_page_id = wp_insert_post(array(
				'post_title' => (!empty($item->name) ? $item->name : JText::translate($item->title)),
				'post_content' => $item->shortcode,
				'post_status' => 'publish',
				'post_type' => 'page',
			), true);

			// we ignore if the page was created or if an error occurred
		}

		return true;
	}

}
