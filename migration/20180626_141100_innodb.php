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

class Migration_20180626_141100_Innodb extends \Skeleton\Database\Migration {

	/**
	 * Migrate up
	 *
	 * @access public
	 */
	public function up() {
		$db = Database::get();
		$result = $db->get_row('show table status where NAME="file"', []);

		if (isset($result['engine']) and $result['engine'] == 'InnoDB') {
			return;
		}

		if (isset($result['Engine']) and $result['Engine'] == 'InnoDB') {
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
