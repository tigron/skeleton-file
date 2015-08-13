# skeleton-file

## Description

This library makes it easy to handle user uploaded files or files created
by your application. Files will be stored on disk, meta data in database.
This library automatically stores files in a structured way on disk with
unique filenames.

## Installation

Installation via composer:

    composer require tigron/skeleton-file

## Howto

Create a new table in your database:

    CREATE TABLE IF NOT EXISTS `file` (
	   `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	   `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
	   `unique_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
	   `mime_type` varchar(128) COLLATE utf8_unicode_ci NOT NULL,
	   `size` int(11) NOT NULL,
	   `created` datetime NOT NULL,
		PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


Upload a file:

    $file = \Skeleton\File\Store::upload($_FILES['upload']);

Create a new file:

    $file = \Skeleton\File\Store::store('filename.txt', 'this is the content');

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
