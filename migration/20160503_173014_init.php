<?php
/**
 * Database migration class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */
namespace Skeleton\File;
use \Skeleton\Database\Database;

class Migration_20160503_173014_Init extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$table = File::trait_get_database_table();
		$tables = $db->get_column("SHOW TABLES LIKE '" . $table . "'", []);
		if (count($tables) == 0) {
			$db->query("
				CREATE TABLE IF NOT EXISTS `" . $table . "` (
				   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				   `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
				   `unique_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
				   `md5sum` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
				   `mime_type` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
				   `size` int(11) NOT NULL,
				   `expiration_date` datetime DEFAULT NULL,
				   `created` datetime NOT NULL,
				   `deleted` datetime NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
			", []);
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
