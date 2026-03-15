<?php

declare(strict_types=1);

/**
 * Database migration class
 */
namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20260315_165003_column_updated extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$db->query("ALTER TABLE `file` ADD `updated` datetime NULL;");
	}
}
