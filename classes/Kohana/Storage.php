<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Storage Module
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage
{
	/**
	 * Directory delimiter
	 * 
	 * @var		string
	 */
	const DELIMITER = '/';

	/**
	 * Namespace helper for `Storage_Connection` factory pattern.
	 * 
	 * @static
	 * @access	public
	 * @param	mixed	string|NULL
	 * @param	array
	 * @return	Storage_Connection
	 */
	public static function factory($connection = NULL, array $config = array())
	{
		return Storage_Connection::factory($connection, $config);
	}

	/**
	 * Trim path
	 * 
	 * @access	public
	 * @param	string
	 * @return	mixed	string|NULL
	 */
	public static function trim($path)
	{
		return trim(trim($path), static::DELIMITER);
	}
	
	/**
	 * Convert path to hash structure while preserving directory and extension.
	 * 
	 * @static
	 * @access	public
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	public static function hash($path, $random = FALSE)
	{
		$hash = ($random) ? md5(time() + rand()) : md5($path);
		
		$path = pathinfo($path);

		$segments = array();
		
		if ($path['dirname'] != '.')
		{
			$segments[] = $path['dirname'];
		}
		
		$segments[] = substr($hash, 0, 2);
		$segments[] = substr($hash, 2, 2);
		$segments[]	= $hash . ((isset($path['extension'])) ? '.' . $path['extension'] : '');
		
		return implode('/', $segments);
	}
	
	/**
	 * Wrapper for File::mime_by_ext
	 * 
	 * @static
	 * @access	public
	 * @param	resource
	 * @param	string	Default mime if unable to derive
	 * @return	string
	 */
	public static function mime($path, $default = 'application/octet-stream')
	{
		$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		
		if ($mime = File::mime_by_ext($extension))
			return $mime;
		
		return $default;
	}
}
