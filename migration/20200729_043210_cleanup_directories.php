<?php
/**
 * Database migration class
 */

namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20200729_043210_cleanup_directories extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		// Skeleton\File has not been configured properly, and is probably not being used
		if (Config::$store_dir === null AND Config::$file_dir === null) {
			return;
		}

		if (Config::$file_dir !== null) {
			$store_path = Config::$file_dir . '/';
		} else {
			$store_path = Config::$store_dir . '/file/';
		}

		// Loop over all nodes and remove empty directories recursively
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(realpath($store_path), \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($iterator as $node) {
			if ($node->isDir() && !(new \FilesystemIterator($node->getPathname()))->valid()) {
				rmdir((string)$node);
			}
		}
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {}
}



