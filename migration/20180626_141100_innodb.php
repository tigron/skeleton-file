<?php
/**
 * Database migration class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 * @author Lionel Laffineur <lionel@tigron.be>
 */
namespace Skeleton\File;

use \Skeleton\Database\Database;

class Migration_20171215_124319_Filename_on_disk extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$result = $db->get_row('show table status where NAME="file"', []);

		if ($result['Engine'] == 'InnoDB') {
			return;
		}

		$db->query("
			ALTER TABLE `file` ENGINE='InnoDB';
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
