<?php
/**
 * Database migration class
 *
 * @author Jochen Timmermans <jochen@tigron.be>
 */
namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20200520_162100_update_default_value_for_deleted extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();

		$db->query("
			ALTER TABLE `file`
				CHANGE `deleted` `deleted` DATETIME NULL AFTER `created`;
		", []);

		$db->query("
			UPDATE `file`
				SET `deleted` = NULL
				WHERE `deleted` = '0000-00-00 00:00:00'
		", []);
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {

	}
}



