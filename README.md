# skeleton-file

## Description

This library makes it easy to handle user uploaded files or files created
by your application. Files will be stored on disk, meta data in database.
This library automatically stores files in a structured way on disk with
unique filenames.

## Installation

Installation via composer:

    composer require tigron/skeleton-file

Create a new table in your database:

    CREATE TABLE `file` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `md5sum` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
        `mime_type` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
        `size` bigint(20) NOT NULL,
        `expiration_date` datetime DEFAULT NULL,
        `created` datetime NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

## Howto

Initialize the file store:

	\Skeleton\File\Config::$file_path = $some_very_cool_path;

	/**
	 * \Skeleton\File\Config::$store_dir is deprecated
	 * $store_dir added directory 'file' to the defined path
	 *
	 * \Skeleton\File\Config::$file_dir is deprecated
	 *
	 * Please use $file_path instead
	 */

Upload a file:

    $file = \Skeleton\File\File::upload($_FILES['upload']);

Create a new file:

    $file = \Skeleton\File\File::store('filename.txt', 'this is the content');

Copy a file:

    $file2 = $file->copy();

Delete a file:

    $file->delete();

Get the content of the file:

    $contents = $file->get_contents();

Get the path of the file on disk:

    $path = $file->get_path();

Send the file to the browser (download):

    $file->client_download();

Get a file by his ID

    $file = File::get_by_id(1);
