<?php
/**
 * Database migration class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 */
namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20200129_142200_default_values extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$db->query("
			ALTER TABLE `file`
			CHANGE `path` `path` varchar(255) COLLATE 'utf8_unicode_ci' NULL AFTER `name`;
		", []);

		$db->query("
			ALTER TABLE `file`
			CHANGE `mime_type` `mime_type` varchar(255) COLLATE 'utf8_unicode_ci' NULL AFTER `md5sum`;
		", []);

		$db->query("
			ALTER TABLE `file`
			CHANGE `size` `size` bigint(20) NULL AFTER `mime_type`;
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
