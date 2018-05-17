<?php
/**
 * Database migration class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 * @author Lionel Laffineur <lionel@tigron.be>
 */
namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20171215_124319_Filename_on_disk extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$db->query("
			ALTER TABLE `file`
			ADD `path` varchar(128) COLLATE 'utf8_unicode_ci' NOT NULL AFTER `name`;
		", []);

		$data = $db->get_all('SELECT * FROM file WHERE path = "" AND md5sum != ""', []);
		foreach ($data as $row) {
			$path = $this->get_path($row);
			$db->query('UPDATE file SET path=? WHERE id=?', [ $path, $row['id'] ]);
		}
	}

	/**
	 * Get path
	 *
	 * @access public
	 * @return string $path
	 */
	public function get_path($file) {
		$parts = str_split($file['md5sum'], 2);
		$subpath = $parts[0] . '/' . $parts[1] . '/' . $parts[2] . '/';

		$path = $subpath . $file['id'] . '-' . $this->sanitize_filename($file['name']);

		return $path;
	}

	/**
	 * Sanitize the filename (old way)
	 *
	 * @access public
	 * @param string $name
	 */
	public function sanitize_filename($name, $max_length = 50) {
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
		if ($max_length != null) {
			$name = substr($name, 0, $max_length);
		}

		if ($extension != null) {
			$name = $name . '.' . $extension;
		}

		return $name;
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {

	}
}
