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

		$database = $db->get_one('SELECT DATABASE()');
		if ($db->get_one('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE 1 AND TABLE_SCHEMA = "' . $database . '" AND TABLE_NAME="file" AND COLUMN_NAME = "expiration_date"') === null) {
			$db->query("ALTER TABLE `file` ADD COLUMN expiration_date datetime DEFAULT NULL AFTER size;");
		}

		if ($db->get_one('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE 1 AND TABLE_SCHEMA = "' . $database . '" AND TABLE_NAME="file" AND COLUMN_NAME = "uuid"') === null) {
			$db->query("ALTER TABLE `file` ADD COLUMN uuid varchar(36) DEFAULT NULL	AFTER expiration_date;");
		}
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {

	}
}
