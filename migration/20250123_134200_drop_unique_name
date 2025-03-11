<?php

declare(strict_types=1);

/**
 * Database migration class
 */

namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20250123_134200_drop_unique_name extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$db->query("ALTER TABLE `file` DROP `unique_name`;");
	}

}
