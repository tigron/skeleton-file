<?php
/**
 * File class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\File;

class File {
	use \Skeleton\Object\Model;
	use \Skeleton\Object\Save;
	use \Skeleton\Object\Delete;
	use \Skeleton\Object\Get;
	use \Skeleton\Object\Uuid;

	/**
	 * Get information related to this object
	 *
	 * @param bool $exclude_content
	 * @return array An array containing the information
	 */
	public function get_info($exclude_content = false) {
		$info = [
			'id' => $this->id,
			'uuid' => $this->uuid,
			'name' => $this->name,
			'mime_type' => $this->mime_type,
			'size' => $this->size,
			'created' => $this->created,
			'human_size' => $this->get_human_size(),
		];

		if ($exclude_content === false) {
			$info['content'] = base64_encode($this->get_contents());
		}

		return $info;
	}

	/**
	 * Is this a picture
	 *
	 * @access public
	 * @return bool $is_picture
	 */
	public function is_picture() {
		$mime_types = [
			'image/jpeg',
			'image/jpg',
			'image/png',
			'image/gif',
			'image/tiff',
			'image/svg+xml',
			'image/webp'
		];

		if (in_array($this->mime_type, $mime_types)) {
			return true;
		}

		return false;
	}

	/**
	 * Is this a pdf
	 *
	 * @access public
	 * @return bool $is_pdf
	 */
	public function is_pdf() {
		$mime_types = [
			'application/pdf',
		];

		if (in_array($this->mime_type, $mime_types)) {
			return true;
		}

		return false;
	}

	/**
	 * Is this an email
	 *
	 * @access public
	 * @return bool $is_email
	 */
	public function is_email() {
		$mime_types = [
			'message/rfc822',
		];

		if (in_array($this->mime_type, $mime_types)) {
			return true;
		}

		return false;
	}

	/**
	 * Copy
	 *
	 * @access public
	 * @param string $new_name
	 * @return File $file
	 */
	public function copy($new_name = null) {
		if ($new_name === null) {
			$new_name = $this->name;
		}
		$file = self::store($new_name, $this->get_contents());
		return $file;
	}

	/**
	 * Delete a file
	 *
	 * @access public
	 */
	public function delete() {
		$db = \Skeleton\Database\Database::get();
		$db->query('DELETE FROM file WHERE id=?', [$this->id]);

		if (file_exists($this->get_path())) {
			unlink($this->get_path());
		}

		// resolve the parent paths first, as they won't be resolvable anymore after removal of the first one
		$parent_paths = [];
		for ($i = 0; $i <= 2; $i++) {
			$parent_paths[] = realpath(dirname($this->get_path()) . str_repeat('/..', $i));
		}

		foreach ($parent_paths as $parent_path) {
			if (!(new \FilesystemIterator($parent_path))->valid()) {
				rmdir($parent_path);
			}
		}
	}

	/**
	* Set expiration date
	*
	* @access public
	*/
	public function expire($delay = '+2 hours') {
		$this->expiration_date = date('Y-m-d H:i:s', strtotime($delay));
		$this->save();
	}

	/**
	* Clear expiration mark
	*
	* @access public
	*/
	public function cancel_expiration() {
		$this->expiration_date = NULL;
		$this->save();
	}

	/**
	 * Get a human readable version of the size
	 *
	 * @access public
	 */
	public function get_human_size() {
		if ($this->size < 1024) {
			return $this->size . ' B';
		}

		$units = ['KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB'];

		foreach ($units as $i => $unit) {
			$multiplier = pow(1024, $i + 1);
			$threshold = $multiplier * 1000;

			if ($this->size < $threshold) {
				$size = Util::limit_digits($this->size / $multiplier, false);
				return $size . ' ' . $unit;
			}
		}
	}

	/**
	 * Get content of the file
	 *
	 * @access public
	 */
	public function get_contents() {
		return file_get_contents($this->get_path());
	}

	/**
	 * Get path
	 *
	 * @access public
	 * @return string $path
	 */
	public function get_path() {
		if (Config::$file_dir !== null) {
			Config::$file_path = Config::$file_dir;
		} elseif (Config::$store_dir !== null) {
			Config::$file_path = Config::$store_dir . '/file';
		} elseif (Config::$file_path === null) {
			throw new \Exception('Set a path first in "Config::$file_path"');
		}

		if (isset($this->path) && !empty($this->path)) {
			$local_path = $this->path;
		} else {
			$parts = str_split($this->md5sum, 2);
			$subpath = $parts[0] . '/' . $parts[1] . '/' . $parts[2] . '/';
			$local_path = $subpath . $this->id . '-' . Util::sanitize_filename($this->name, 128);
			$this->path = $local_path;
			$this->save();
		}

		return Config::$file_path . '/' . $local_path;
	}

	/**
	 * Send this file as a download to the client
	 *
	 * @access public
	 * @param int $seconds_to_cache
	 */
	public function client_download($seconds_to_cache = null) {
		$this->send_file('attachment', $seconds_to_cache);
	}

	/**
	 * Send this file inline to the client
	 *
	 * @access public
	 * @param int $seconds_to_cache
	 */
	public function client_inline($seconds_to_cache = null) {
		$this->send_file('inline', $seconds_to_cache);
	}

	/**
	 * Get File by id
	 *
	 * @access public
	 * @param int $id
	 * @return File $file
	 */
	public static function get_by_id($id) {
		$file = new self($id);

		if ($file->is_picture() && class_exists('\Skeleton\File\Picture\Config')) {
			$classname = \Skeleton\File\Picture\Config::$picture_interface;
			if (class_exists($classname)) {
				$file = new $classname($id);
			}
		} elseif ($file->is_pdf() && class_exists('\Skeleton\File\Pdf\Config')) {
			$classname = \Skeleton\File\Pdf\Config::$pdf_interface;
			if (class_exists($classname)) {
				$file = new $classname($id);
			}
		} elseif ($file->is_email() && class_exists('\Skeleton\File\Email\Config')) {
			$classname = \Skeleton\File\Email\Config::$email_interface;
			if (class_exists($classname)) {
				$file = new $classname($id);
			}
		} elseif (\Skeleton\File\Config::$file_interface != '\Skeleton\File\File') {
			$classname = \Skeleton\File\Config::$file_interface;
			if (class_exists($classname)) {
				$file = new $classname($id);
			}
		}

		return $file;
	}

	/**
	 * Get expired files
	 *
	 * @access public
	 * @return array File $items
	 */
	public static function get_expired() {
		$db = \Skeleton\Database\Database::get();
		$ids = $db->get_column('SELECT id FROM file WHERE expiration_date IS NOT NULL AND expiration_date < NOW()');

		$items = [];
		foreach ($ids as $id) {
			$items[] = self::get_by_id($id);
		}

		return $items;
	}

	/**
	 * Store a file
	 *
	 * @access public
	 * @param string $name
	 * @param mixed $content
	 * @param datetime $created
	 * @return File $file
	 */
	public static function store($name, $content, $created = null) {
		return self::create(
			'store',
			Util::beautify_string($name),
			$content,
			$created
		);
	}

	/**
	 * Upload a file
	 *
	 * @access public
	 * @param array $_FILES['file']
	 * @return File $file
	 */
	public static function upload($fileinfo) {
		if (empty($fileinfo['tmp_name'])) {
			throw new \Exception('Upload failed');
		}

		return self::create(
			'store',
			Util::beautify_string($fileinfo['name']),
			file_get_contents($fileinfo['tmp_name'])
		);
	}

	/**
	 * Merge files
	 *
	 * @access public
	 * @param string $name
	 * @param array $files
	 * @return File $file
	 */
	public static function merge($name, $files = []) {
		$config = \Skeleton\Core\Config::Get();
		if (empty($config->tmp_dir) === false) {
			$config->tmp_path = $config->tmp_dir;
		} elseif (empty($config->tmp_path) === true) {
			throw new \Exception('No tmp path defined');
		}

		// Sanitize name to use as a filename
		$filename = Util::sanitize_filename($name);

		// Merge files
		$command = 'cat ';
		foreach ($files as $file) {
			$command .= $file->get_path() . ' ';
		}
		$command .= ' > ' . $config->tmp_path . $filename;
		exec($command);

		return self::create(
			'merge',
			Util::sanitize_filename($name),
			file_get_contents($config->tmp_path . $filename)
		);
	}

	/**
	 * Upload multiple
	 *
	 * @access public
	 * @param array $files
	 */
	public static function upload_multiple($fileinfo) {
		$files = [];
		foreach ($fileinfo['name'] as $key => $value) {
			$item_fileinfo = [];
			foreach ($fileinfo as $property => $value) {
				$item_fileinfo[$property] = $value[$key];
			}

			if ($item_fileinfo['size'] > 0) {
				$files[] = self::upload($item_fileinfo);
			}
		}

		return $files;
	}

	/**
	 * Send this file
	 *
	 * @access public
	 * @param string $content_disposition
	 * @param int $seconds_to_cache
	 */
	private function send_file($content_disposition, $seconds_to_cache = null) {
		header('Content-type: ' . $this->mime_type);
		header('Content-Disposition: ' . $content_disposition . '; filename="' . $this->name . '"');

		$filename = $this->get_path();
		$gmt_mtime = gmdate('D, d M Y H:i:s', filemtime($filename)).' GMT';
		header('Last-Modified: ' . $gmt_mtime);

		if (!empty($seconds_to_cache)) {
			if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $gmt_mtime) {
				header('HTTP/1.1 304 Not Modified');
				return;
			}

			header('Cache-Control: public');
			header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+' . $seconds_to_cache . ' seconds')) . ' GMT');
		}

		$gmt_mtime = gmdate('D, d M Y H:i:s', filemtime($filename)).' GMT';
		readfile($filename);
	}

	/**
	 * Create File
	 * Store file on disk
	 *
	 * @access private
	 * @param string $action
	 * @param string $name
	 * @param string $content
	 * @param string $created
	 * @return File
	 */
	private static function create($action, $name, $content, $created = null) {
		if (empty($created)) {
			$created = time();
		} else {
			$created = strtotime($created);
		}

		$file = new self();
		$file->name = $name;
		$file->md5sum = hash('md5', $content);
		$file->created = date('YmdHis', $created);
		$file->save();

		// create directory if not exist
		$path = $file->get_path();
		$pathinfo = pathinfo($path);
		if (!is_dir($pathinfo['dirname'])) {
			mkdir($pathinfo['dirname'], 0755, true);
		}

		// store file on disk
		if ($action == 'upload') {
			if (!move_uploaded_file($fileinfo['tmp_name'], $path)) {
				throw new \Exception('Upload failed');
			}
		} elseif ($action == 'merge') {
			$config = \Skeleton\Core\Config::get();
			if (empty($config->tmp_dir) === false) {
				$config->tmp_path = $config->tmp_dir;
			} elseif (empty($config->tmp_path) === true) {
				throw new \Exception('No tmp path defined');
			}

			rename($config->tmp_path . $name, $path);
		} else {
			file_put_contents($path, $content);
		}

		// set mime type and size
		$file->mime_type = Util::detect_mime_type($path);
		$file->size = filesize($path);
		$file->save();

		$file_classname = get_called_class();

		// this is the first time the file will be available with it's resolved
		// classname, so we can only call validate reliably from now on.
		$file = $file_classname::get_by_id($file->id);

		if (
			method_exists($file, 'validate') &&
			is_callable([$file, 'validate'])
		) {
			$errors = [];
			if ($file->validate($errors) === false) {
				$file->delete();
				throw new \Skeleton\Object\Exception_Validation($errors);
			}
		}

		return $file;
	}
}
