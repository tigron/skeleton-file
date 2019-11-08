<?php
/**
 * Database migration class
 *
 * @author David Vandemaele <david@tigron.be>
 */
namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20191108_095632_Allow_higher_file_length extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$db->query("
			ALTER TABLE `file`
				CHANGE `name` `name` varchar(255) COLLATE 'utf8_unicode_ci' NOT NULL AFTER `id`,
				CHANGE `path` `path` varchar(255) COLLATE 'utf8_unicode_ci' NOT NULL AFTER `name`;
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
