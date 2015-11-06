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
			'deleted' => $this->deleted,
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
	 * Copy
	 *
	 * @access public
	 * @return File $file
	 */
	public function copy() {
		$file = self::store($this->name, $this->get_contents());
		return $file;
	}

	/**
	 * Delete a file
	 *
	 * @access public
	 */
	public function delete() {
		if (file_exists($this->get_path())) {
			unlink($this->get_path());
		}

		$db = \Skeleton\Database\Database::get();
		$db->query('DELETE FROM file WHERE id=?', [$this->id]);
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
		$ids = $db->get_column('SELECT id FROM file WHERE deleted = 0 AND expiration_date IS NOT NULL AND expiration_date < NOW()');

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
		$subpath = substr(base_convert($this->md5sum, 16, 10), 0, 3);
		$subpath = implode('/', str_split($subpath)) . '/';

		if (Config::$file_dir !== null) {
			$path = Config::$file_dir . '/' . $subpath . $this->id . '-' . self::sanitize_filename($this->name);
		} else {
			$path = Config::$store_dir . '/file/' . $subpath . $this->id . '-' . self::sanitize_filename($this->name);
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
		if ($file->is_picture() and class_exists('\\Skeleton\\File\\Picture\\Picture')) {
			return \Skeleton\File\Picture\Picture::get_by_id($id);
		} else {
			return $file;
		}
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
		$file->mime_type = self::detect_mime_type($path);
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
		$file->mime_type = self::detect_mime_type($path);
		$file->size = filesize($path);
		$file->save();

		return self::get_by_id($file->id);
	}

	/**
	 * Get the mime_type of a file
	 *
	 * @access private
	 * @param string $file The path to the file
	 * @return string $mime_type
	 */
	private static function detect_mime_type($path) {
		$handle = finfo_open(FILEINFO_MIME);
		$mime_type = finfo_file($handle, $path);

		if (strpos($mime_type, ';')) {
			$mime_type = preg_replace('/;.*/', ' ', $mime_type);
		}

		return trim($mime_type);
	}

	/**
	 * Sanitize filenames
	 *
	 * @access public
	 * @param string $name
	 * @return string $name
	 */
	private static function sanitize_filename($name) {
		$special_chars = ['#','$','%','^','&','*','!','~','‘','"','’','\'','=','?','/','[',']','(',')','|','<','>',';','\\',',','+'];
		$name = preg_replace('/^[.]*/','',$name); // remove leading dots
		$name = preg_replace('/[.]*$/','',$name); // remove trailing dots
		$name = str_replace($special_chars, '', $name);// remove special characters
		$name = str_replace(' ','_',$name); // replace spaces with _

		$name_array = explode('.', $name);

		if (count($name_array) > 1) {
			$extension = array_pop($name_array);
		} else {
			$extension = null;
		}

		$name = implode('.', $name_array);
		$name = substr($name, 0, 50);

		if ($extension != null) {
			$name = $name . '.' . $extension;
		}

		return $name;
	}
}
