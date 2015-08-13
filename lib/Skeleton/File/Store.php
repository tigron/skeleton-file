<?php
/**
 * Store Class
 *
 * Stores and retrieves files
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\File;

class Store {

	/**
	 * Store_path
	 *
	 * @var string $store_path
	 * @access private
	 */
	private $store_path = null;

	/**
	 * Private constructor
	 *
	 * @access private
	 */
	private function __construct() {}

	/**
	 * Set the physical path
	 *
	 * @access public
	 * @param string $path
	 */
	public static function set_path($path) {
		self::$store_path = $path;
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
		if (self::$store_path === null) {
			throw new \Exception('Set a path first by calling "Store::set_path($path)"');
		}

		$file = new File();
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
		$path = self::get_path($file);
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

		return File::get_by_id($file->id);
	}

	/**
	 * Upload a file
	 *
	 * @access public
	 * @param array $_FILES['file']
	 * @return File $file
	 */
	public static function upload($fileinfo) {
		if (self::$store_path === null) {
			throw new \Exception('Set a path first by calling "Store::set_path($path)"');
		}

		$file = new File();
		$file->name = $fileinfo['name'];
		$file->md5sum = hash('md5', file_get_contents($fileinfo['tmp_name']));
		$file->save();

		// create directory if not exist
		$path = self::get_path($file);
		$pathinfo = pathinfo($path);
		if (!is_dir($pathinfo['dirname'])) {
			mkdir($pathinfo['dirname'], 0755, true);
		}

		// store file on disk
		if (!move_uploaded_file($fileinfo['tmp_name'], $path)) {
			throw new Exception('upload failed');
		}

		// set mime type and size
		$file->mime_type = self::detect_mime_type($path);
		$file->size = filesize($path);
		$file->save();

		return File::get_by_id($file->id);
	}

	/**
	 * Delete a file
	 *
	 * @access public
	 * @param File $file
	 */
	public static function delete_file(File $file) {
		if (file_exists($file->get_path())) {
			unlink($file->get_path());
		}
	}

	/**
	 * Get the physical path of a file
	 *
	 * @param File $file
	 * @return string $path
	 */
	public static function get_path(File $file) {
		if (self::$store_path === null) {
			throw new \Exception('Set a path first by calling "Store::set_path($path)"');
		}
		$subpath = substr(base_convert($file->md5sum, 16, 10), 0, 3);
		$subpath = implode('/', str_split($subpath)) . '/';

		$path = STORE_PATH . '/file/' . $subpath . $file->id . '-' . self::sanitize_filename($file->name);

		return $path;
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
		$mime_type = finfo_file($handle,$file);

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
