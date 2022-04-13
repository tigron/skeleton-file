<?php
/**
 * Config class
 * Configuration for Skeleton\File
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
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
	 * Deprecated: Please use \Skeleton\File\Config::$file_path;
	 *
	 * @access public
	 * @deprecated
	 * @var string $file_dir
	 */
	public static $file_dir = null;

	/**
	 * File path
	 *
	 * This folder will be used to store all files
	 *
	 * @access public
	 * @var string $file_path
	 */
	public static $file_path = null;

	/**
	 * File interface class
	 *
	 * This class will provide the File functionality, by default a class is defined
	 */
	public static $file_interface = '\Skeleton\File\File';

}
