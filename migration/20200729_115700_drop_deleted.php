<?php
/**
 * Database migration class
 */

namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20200729_115700_drop_deleted extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$db->query("ALTER TABLE `file` DROP `deleted`");
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {}
}
