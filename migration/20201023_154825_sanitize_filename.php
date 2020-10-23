<?php
/**
 * Database migration class
 *
 */
namespace Skeleton\File;


use \Skeleton\Database\Database;

class Migration_20201023_154825_Sanitize_filename extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$db->query('
			UPDATE file
			SET name = REPLACE(name,"/","-")
			WHERE name LIKE "%/%"
		');
	}

	/**
	 * Migrate down
	 *
	 * @access public
	 */
	public function down() {

	}
}
