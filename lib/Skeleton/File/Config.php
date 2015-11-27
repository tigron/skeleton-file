<?php
/**
 * Config class
 * Configuration for Skeleton\File
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\File;

class Config {

	/**
	 * Store directory
	 *
	 * This folder will be used to store all files
	 * A folder 'file' will be created in this folder to store the files
	 *
	 * Deprecated: Please use \Skeleton\File\Config::$file_dir;
	 *
	 * @access public
	 * @deprecated
	 * @var string $store_dir
	 */
	public static $store_dir = null;

	/**
	 * File directory
	 *
	 * This folder will be used to store all files
	 *
	 * @access public
	 * @var string $file_dir
	 */
	public static $file_dir = null;

}