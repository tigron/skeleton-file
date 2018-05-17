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

	/**
	 * Get information related to this object
	 *
	 * @param bool $exclude_content
	 * @return array An array containing the information
	 */
	public function get_info($exclude_content = false) {
		$info = [
			'id' => $this->id,
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
		if (Config::$store_dir === null AND Config::$file_dir === null) {
			throw new \Exception('Set a path first in "Config::$file_dir"');
		}

		if (isset($this->path) and !empty($this->path)) {
			$local_path = $this->path;
		} else {
			$parts = str_split($this->md5sum, 2);
			$subpath = $parts[0] . '/' . $parts[1] . '/' . $parts[2] . '/';
			$local_path = $subpath . $this->id . '-' . Util::sanitize_filename($this->name, 50);
			$this->path = $local_path;
			$this->save();
		}

		if (Config::$file_dir !== null) {
			$path = Config::$file_dir . '/' . $local_path;
		} else {
			$path = Config::$store_dir . '/file/' . $local_path;
		}

		return $path;
	}

	/**
	 * Send this file as a download to the client
	 *
	 * @access public
	 */
	public function client_download() {
		header('Content-type: ' . $this->details['mime_type']);
		header('Content-Disposition: attachment; filename="'.$this->details['name'].'"');
		readfile($this->get_path());
		exit();
	}

	/**
	 * Send this file inline to the client
	 *
	 * @access public
	 */
	public function client_inline() {
		header('Content-type: ' . $this->details['mime_type']);
		header('Content-Disposition: inline; filename="'.$this->details['name'].'"');
		readfile($this->get_path());
		exit();
	}

	/**
	 * Get File by id
	 *
	 * @access public
	 * @param int $id
	 * @return File $file
	 */
	public static function get_by_id($id) {
		$classname = get_called_class();
		$file = new $classname($id);

		if ($file->is_picture() AND class_exists('\Skeleton\File\Picture\Config')) {
			$classname = \Skeleton\File\Picture\Config::$picture_interface;
			if (class_exists($classname)) {
				$file = new $classname($id);
			}
		} elseif ($file->is_pdf() AND class_exists('\Skeleton\File\Pdf\Config')) {
			$classname = \Skeleton\File\Pdf\Config::$pdf_interface;
			if (class_exists($classname)) {
				$file = new $classname($id);
			}
		}

		return $file;
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
		if (Config::$store_dir === null AND Config::$file_dir === null) {
			throw new \Exception('Set a path first in "Config::$file_dir"');
		}

		$file = new self();
		$file->name = $name;
		$file->md5sum = hash('md5', $content);
		$file->save();

		if (is_null($created)) {
			$created = time();
		} else {
			$created = strtotime($created);
		}

		$file->created = date('Y-m-d H:i:s', $created);
		$file->save();

		// create directory if not exist
		$path = $file->get_path();
		$pathinfo = pathinfo($path);
		if (!is_dir($pathinfo['dirname'])) {
			mkdir($pathinfo['dirname'], 0755, true);
		}

		// store file on disk
		file_put_contents($path, $content);

		// set mime type and size
		$file->mime_type = Util::detect_mime_type($path);
		$file->size = filesize($path);
		$file->save();

		return self::get_by_id($file->id);
	}

	/**
	 * Upload a file
	 *
	 * @access public
	 * @param array $_FILES['file']
	 * @return File $file
	 */
	public static function upload($fileinfo) {
		if (Config::$store_dir === null AND Config::$file_dir === null) {
			throw new \Exception('Set a path first in "Config::$file_dir"');
		}

		$file = new self();
		$file->name = $fileinfo['name'];
		$file->md5sum = hash('md5', file_get_contents($fileinfo['tmp_name']));
		$file->save();

		// create directory if not exist
		$path = $file->get_path();
		$pathinfo = pathinfo($path);
		if (!is_dir($pathinfo['dirname'])) {
			mkdir($pathinfo['dirname'], 0755, true);
		}

		// store file on disk
		if (!move_uploaded_file($fileinfo['tmp_name'], $path)) {
			throw new \Exception('upload failed');
		}

		// set mime type and size
		$file->mime_type = Util::detect_mime_type($path);
		$file->size = filesize($path);
		$file->save();

		return self::get_by_id($file->id);
	}

	/**
	 * Merge files
	 *
	 * @access public
	 * @param string $filename
	 * @param array $files
	 * @return File $file
	 */
	public static function merge($filename, $files = []) {
		if (Config::$store_dir === null AND Config::$file_dir === null) {
			throw new \Exception('Set a path first in "Config::$file_dir"');
		}

		$command = 'cat ';
		foreach ($files as $file) {
			$command .= $file->get_path() . ' ';
		}

		$filename = Util::sanitize_filename($filename, 50);

		$command .= ' > ' . \Skeleton\Core\Config::$tmp_dir . $filename;
		exec($command);

		$merged_file = new self();
		$merged_file->name = $filename;
		$merged_file->md5sum = hash('md5', file_get_contents(\Skeleton\Core\Config::$tmp_dir . $filename));
		$merged_file->save();

		$path = $merged_file->get_path();
		$pathinfo = pathinfo($path);
		if (!is_dir($pathinfo['dirname'])) {
			mkdir($pathinfo['dirname'], 0755, true);
		}
		rename(\Skeleton\Core\Config::$tmp_dir . $filename, $path);

		$merged_file->mime_type = Util::detect_mime_type($path);
		$merged_file->size = filesize($path);
		$merged_file->save();

		return self::get_by_id($merged_file->id);
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
			$item_fileinfo = array();
			foreach ($fileinfo as $property => $value) {
				$item_fileinfo[$property] = $value[$key];
			}
			if ($item_fileinfo['size'] > 0) {
				$files[] = self::upload($item_fileinfo);
			}
		}
		return $files;
	}
}
