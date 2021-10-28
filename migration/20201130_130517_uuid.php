<?php
/**
 * Database migration class
 *
 * @author Lionel Laffineur <lionel@tigron.be>
 */
namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20201130_130517_Uuid extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();

		$db->query("ALTER TABLE `file` ADD COLUMN IF NOT EXISTS expiration_date datetime DEFAULT NULL AFTER size;");
		$db->query("ALTER TABLE `file`
					ADD `uuid` varchar(36) NULL
					AFTER expiration_date;");
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {

	}
}
