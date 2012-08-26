<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Storage Module
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011 Micheal Morgan
 * @license		MIT
 */
class Storage
{
	/**
	 * Directory delimiter
	 * 
	 * @var		string
	 */
	const DELIMITER = '/';	
	
	/**
	 * Default connection
	 * 
	 * @static
	 * @access	protected
	 * @var		Storage_Connection
	 */
	protected static $_connection;
	
	/**
	 * Get or set default connection
	 * 
	 * @static
	 * @access	public
	 * @param	mixed	NULL|Storage_Connection
	 * @return	Storage_Connection
	 */
	public static function connection(Storage_Connection $connection = NULL)
	{
		if ($connection === NULL)
		{
			if (static::$_connection === NULL)
			{
				static::$_connection = Storage_Connection::factory();
			}
			
			return static::$_connection;
		}
		
		return static::$_connection = $connection;
	}
	
	/**
	 * Namespace helper for Storage_File factory pattern.
	 * 
	 * @static
	 * @access	public
	 * @param	string
	 * @param	mixed	Storage_Connection|NULL
	 * @return	Storage_File
	 */
	public static function factory($path, Storage_Connection $connection = NULL)
	{
		return Storage_File::factory($path, $connection);
	}
	
	/**
	 * Helper: Write content to storage.
	 * 
	 * @access	public
	 * @param	string
	 * @param	mixed	string|resource
	 * @param	bool
	 * @return	$this
	 */
	public static function set($path, $content, $filename = FALSE)
	{
		return static::_connection()->set($path, $content, $filename);
	}
	
	/**
	 * Helper: Read contents of file.
	 * 
	 * @access	public
	 * @param	string
	 * @param	string|resource
	 * @return	bool
	 */
	public static function get($path, $handle)
	{
		return static::_connection()->get($path, $handle);
	}	
	
	/**
	 * Helper: Delete
	 * 
	 * @access	public
	 * @param	string
	 * @return	$this
	 */
	public static function delete($path)
	{
		return static::_connection()->delete($path);
	}	
	
	/**
	 * Helper: File size
	 * 
	 * @access	public
	 * @param	string
	 * @return	int
	 */
	public static function size($path)
	{
		return static::_connection()->size($path);
	}
	
	/**
	 * Helper: Whether or not file exists
	 * 
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public static function exists($path)
	{
		return static::_connection()->exists($path);	
	}
	
	/**
	 * Helper: Get URL
	 * 
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public static function url($path, $protocol = 'http')
	{
		return static::_connection()->url($path, $protocol);
	}
	
	/**
	 * Helper: Get listing
	 * 
	 * @access	public
	 * @param	mixed	NULL|string
	 * @param	mixed	NULL|Storage_Directory
	 * @return	Storage_Directory
	 */
	public static function listing($path = NULL, Storage_Directory $directory = NULL)
	{
		return static::_connection()->listing($path, $directory);
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

	/**
	 * Connection
	 * 
	 * @access	protected
	 * @return	Storage_Connection
	 */
	protected static function _connection()
	{
		if (NULL === $connection = static::connection())
			throw new Storage_Connection('Expecting to be able to load default connection.');
		
		return $connection;
	}
}