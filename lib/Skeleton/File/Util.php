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
	public static function number_of_digits($number) {
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
	public static function limit_digits($value, $round = true, $digits = 3) {
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

			return substr($joined, 0, $digits + 1);
		}
	}

	/**
	 * Sanitize filenames
	 *
	 * @access public
	 * @param string $name
	 * @return string $name
	 */
	public static function sanitize_filename($name) {
		$special_chars = ['#','$','%','^','&','*','!','~','‘','"','’','\'','=','?','/','[',']','(',')','|','<','>',';','\\',',','+'];
		$name = preg_replace('/^[.]*/','',$name); // remove leading dots
		$name = preg_replace('/[.]*$/','',$name); // remove trailing dots
		$name = str_replace($special_chars, '', $name);// remove special characters
		$name = str_replace(' ','_',$name); // replace spaces with _

		$name_array = explode('.', $name);

		if (count($name_array) > 1) {
			$extension = array_pop($name_array);
		} else {
			$extension = null;
		}

		$name = implode('.', $name_array);
		$name = substr($name, 0, 50);

		if ($extension != null) {
			$name = $name . '.' . $extension;
		}

		return $name;
	}

	/**
	 * Get the mime_type of a file
	 *
	 * @access public
	 * @param string $file The path to the file
	 * @return string $mime_type
	 */
	public static function detect_mime_type($path) {
		$handle = finfo_open(FILEINFO_MIME);
		$mime_type = finfo_file($handle, $path);

		if (strpos($mime_type, ';')) {
			$mime_type = preg_replace('/;.*/', ' ', $mime_type);
		}

		return trim($mime_type);
	}
}
