<?php
/**
 * Util class
 *
 * Contains utilities for calculations
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\File;

class Util {

	/**
	 * Efficiently calculates how many digits the integer portion of a number has.
	 *
	 * @access public
	 * @param int $number
	 * @return int $digits
	 */
	public static function number_of_digits($number): int {
		$log = log10($number);

		if ($log < 0) {
			return 1;
		} else {
			return floor($log) + 1;
		}
	}

	/**
	 * Formats a number to a minimum amount of digits.
	 * In other words, makes sure that a number has at least $digits on it, even if
	 * that means introducing redundant decimal zeroes at the end, or rounding the
	 * ones present exceeding the $digits count when combined with the integers.
	 * This is primarily useful for generating human-friendly numbers.
	 *
	 * @access public
	 * @param double $value
	 * @param bool $round
	 * @param int $digits
	 */
	public static function limit_digits($value, $round = true, $digits = 3): int {
		$integers = floor($value);

		$decimals_needed = $digits - self::number_of_digits($integers);

		if ($decimals_needed < 1) {
			return $integers;
		} else {
			if ($round) {
				$parts = explode('.', round($value, $decimals_needed));
				$integers = $parts[0];
			} else {
				$parts = explode('.', $value);
			}

			if (isset($parts[1])) {
				$decimals = $parts[1];
			} else {
				$decimals = 0;
			}

			$joined = $integers . '.' . $decimals . str_repeat('0', $digits);

			return (int)substr($joined, 0, $digits + 1);
		}
	}

	/**
	 * Sanitize will return strings only containing alphanumeric characters and
	 * dashes. It will try to preserve as much information as it can and represent
	 * it with a limited character set.
	 *
	 * The result can safely be used as a filename on disk, or as a slug.
	 *
	 * This requires the intl extension: https://www.php.net/manual/en/book.intl.php
	 *
	 * @access public
	 * @param string $string
	 * @return string
	 */
	public static function sanitize_string(string $string): string {
		// "Any-Latin": transliterate to latin while preserving what we can
		// "NFD; [:Nonspacing Mark:] Remove; NFC": move accents into separate characters, remove the accents
		// "Lower()": lowercase the end result
		$string = transliterator_transliterate('Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; Lower()', $string);

		// "[:Punctuation:] Remove": replace any character in the unicode punctuation category with dashes
		$string = preg_replace('/\p{P}/', '-', $string);

		// Replace leftover non-alphanumerics with dashes
		$string = preg_replace('/[^A-Za-z0-9 ]/', '-', $string);

		// Replace spaces and consecutive dashes with single dashes
		$string = preg_replace('/[-\s]+/', '-', $string);

		// Remove any leading or trailing dashes
		return trim($string, '-');
	}

	/**
	 * Sanitize filename will sanitize the filename, while trying to preserve the
	 * file extension if one is present.
	 *
	 * @access public
	 * @param string $string
	 * @return string
	 */
	public static function sanitize_filename(string $filename): string {
		// We only expect a filename, not a path; replace directory separators first
		$filename = str_replace(DIRECTORY_SEPARATOR, '-', $filename);

		$extension = self::sanitize_string(pathinfo($filename, PATHINFO_EXTENSION));
		$name = self::sanitize_string(pathinfo($filename, PATHINFO_FILENAME));

		$filename = $name;

		if ($extension !== '') {
			$filename = $name . '.' . $extension;
		}

		// We prefer underscores for filenames
		return str_replace('-', '_', $filename);
	}

	/**
	 * Beautify will only remove "annoying" characters and characters prohibited on
	 * a filesystem, preserving anything else.
	 *
	 * The result can be used as a filename for files returned to users.
	 *
	 * @access public
	 * @param string $string
	 * @return string
	 */
	public static function beautify_string(string $string): string {
		// Filter out three groups of characters:
		// - any relevant characters reserved on common filesystems https://en.wikipedia.org/wiki/Filename#Reserved_characters_and_words
		// - control characters https://en.wikipedia.org/wiki/Control_character
		// - non-printing characters (DEL, NO-BREAK SPACE, SOFT HYPHEN)
		$string = preg_replace('~[<>:"/\\\|?*%] | [\x00-\x1F] | [\x7F\xA0\xAD]~xu', '-', $string);

		// trim anything resembling a space
		return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $string);
	}

	/**
	 * Get the mime_type of a file
	 *
	 * @access public
	 * @param string $file The path to the file
	 * @return string $mime_type
	 */
	public static function detect_mime_type($path): string {
		$handle = finfo_open(FILEINFO_MIME);
		$mime_type = finfo_file($handle, $path);

		if (strpos($mime_type, ';')) {
			$mime_type = preg_replace('/;.*/', ' ', $mime_type);
		}

		return trim($mime_type);
	}
}
