<?php
/** 
 * @package     VikBooking
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2022 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

/**
 * VikBooking HTML main helper.
 *
 * @since 1.5
 */
abstract class JHtmlVikbooking
{
	/**
	 * Helper method used to obtain a shorten version of the given text.
	 * 
	 * Even if a maximum threshold is provided, this doesn't mean that the
	 * length of the resulting text will be exactly equal to that amount.
	 * This beacuse the function doesn't break words.
	 * 
	 * @param   string    $text  The text to shorten.
	 * @param   int|null  $max   The maximum number of allowed characters.
	 * 
	 * @return  string
	 * 
	 * @since   1.8
	 */
	public static function shorten(string $text, ?int $max = null): string
	{
		// add a white space after the closure of any tag
		$text = preg_replace_callback("/(<\/[a-z0-9\-]+>)|(\s*\/\s*>)/", function($match) {
			return $match[0] . " ";
		}, $text);

		// get rid of HTML tags
		$text = strip_tags($text);

		// remove duplicate white spaces and replace new lines with spaces
		$text = preg_replace("/(\s{2,})|(\R+)/", ' ', $text);

		// calculate total length of the text
		$len = strlen($text);

		// Check whether we should take a substring of the text.
		// Reserve an additional 25% of characters to avoid breaking the
		// text too close to the end of the string.
		if ($max && $len > $max * 1.25) {
			// explode the string in words
			$chunks = explode(' ', $text);

			$text = '';

			// keep adding words until we reach the maximum threshold
			while ($chunks && strlen($text) < $max) {
				$text .= array_shift($chunks) . ' ';
			}

			// get rid of trailing special characters and add the ellipsis
			$text = rtrim($text, '.,?!;:#\'"([{ ') . '...';
		}

		return $text;
	}

	/**
	 * Calculates the maximum upload file size and returns string with unit or the size in bytes.
	 *
	 * @param   bool          $unitOutput  This parameter determines whether the return value
	 *                                     should be a string with a unit.
	 *
	 * @return  float|string  The maximum upload size of files with the appropriate unit or in bytes.
	 */
	public static function maxuploadsize($unitOutput = true)
	{
		static $max_size = false;
		
		if ($max_size === false)
		{
			$max_size   = self::parseSize(ini_get('post_max_size'));
			$upload_max = self::parseSize(ini_get('upload_max_filesize'));

			// check what is the highest value between post and upload max sizes
			if ($upload_max > 0 && ($upload_max < $max_size || $max_size == 0))
			{
				$max_size = $upload_max;
			}
		}

		if (!$unitOutput)
		{
			// return numerical max size
			return $max_size;
		}

		// format max size
		return JHtml::fetch('number.bytes', $max_size, 'auto', 0);
	}

	/**
	 * Returns the size in bytes without the unit for the comparison.
	 *
	 * @param   string  $size  The size which is received from the PHP settings.
	 *
	 * @return  float   The size in bytes without the unit.
	 */
	private static function parseSize($size)
	{
		// extract the size unit
		$unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
		// take only the size numbers
		$size = preg_replace('/[^0-9\.]/', '', $size);

		$return = round($size);

		if ($unit)
		{
			// calculate the correct size according to the specified unit
			$return = round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
		}

		return $return;
	}
}
