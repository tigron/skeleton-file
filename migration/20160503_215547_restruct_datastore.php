<?php
/**
 * Database migration class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */
namespace Skeleton\File;
use \Skeleton\Database\Database;
use \Skeleton\File\File;

class Migration_20160503_215547_Restruct_datastore extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$table = File::trait_get_database_table();

		$db = Database::get();
		$ids = $db->get_column('SELECT id FROM ' . $table, []);
		foreach ($ids as $id) {
			$file = File::get_by_id($id);
			$old_path = $this->get_old_path($file);
			if (!file_exists($old_path)) {
				continue;
			}
			$new_path = $file->get_path();

			// create directory if not exist
			$pathinfo = pathinfo($new_path);
			if (!is_dir($pathinfo['dirname'])) {
				mkdir($pathinfo['dirname'], 0755, true);
			}

			rename($old_path, $new_path);
		}

		/**
		 * Run this to cleanup empty directories
		 */
//		echo 'find ' . $path . ' -type d -empty -delete';
	}

	/**
	 * Get the old path
	 *
	 * @access private
	 * @param string $md5sum
	 * @return string $path
	 */
	private function get_old_path(File $file) {
		if (Config::$file_dir !== null) {
			Config::$file_path = Config::$file_dir;
		} elseif (Config::$store_dir !== null) {
			Config::$file_path = Config::$store_dir . '/file';
		} else {
			throw new \Exception('Set a path first in "Config::$file_path"');
		}

		$subpath = substr(base_convert($file->md5sum, 16, 10), 0, 3);
		$subpath = implode('/', str_split($subpath)) . '/';

		return \Skeleton\File\Config::$file_path . '/' . $subpath . $file->id . '-' . \Skeleton\File\Util::sanitize_filename($file->name);
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {

	}
}
