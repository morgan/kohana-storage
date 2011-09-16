<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Storage Module
 * 
 * Supports PHP 5.2.3 or greater.
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011 Micheal Morgan
 * @license		MIT
 */
abstract class Kohana_Storage 
{	
	/**
	 * Default driver
	 * 
	 * @access	public
	 * @var		string
	 */
	public static $driver = 'local';
	
	/**
	 * Factory pattern
	 * 
	 * @static
	 * @access	public
	 * @param	mixed	string|NULL
	 * @param	array
	 * @return	Storage
	 */
	public static function factory($connection = NULL, array $config = array())
	{
		$connection = $connection ? strtolower($connection) : self::$driver;

		$config = $config + Kohana::$config->load('storage.' . $connection);

		$class = 'Storage_' . ucfirst(isset($config['driver']) ? $config['driver'] : $connection);
		
		return new $class($config);
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
	 * Config
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_config = array();
	
	/**
	 * Initialization
	 * 
	 * @access	public
	 * @return	void
	 */
	public function __construct(array $config = array())
	{
		$this->_config = $config + $this->_config + array('directory' => NULL);
	}
	
	/**
	 * Write content to file. If file already exists, it will be overwritten.
	 * 
	 * @access	protected
	 * @param	string
	 * @param	resource
	 * @return	void
	 */
	abstract protected function _set($path, $handle);	
	
	/**
	 * Read contents of file.
	 * 
	 * @access	public
	 * @param	string
	 * @param	resource
	 * @return	bool
	 */
	abstract protected function _get($path, $handle);		
	
	/**
	 * Delete
	 * 
	 * @access	protected
	 * @param	string
	 * @return	void
	 */
	abstract protected function _delete($path);
	
	/**
	 * File size
	 * 
	 * @access	protected
	 * @param	string
	 * @return	int
	 */
	abstract protected function _size($path);
	
	/**
	 * Whether or not file exists
	 * 
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	abstract protected function _exists($path);
	
	/**
	 * Get URL
	 * 
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	abstract protected function _url($path, $protocol);	
	
	/**
	 * Write content to storage.
	 * 
	 * @access	public
	 * @param	string
	 * @param	mixed	string|resource
	 * @param	bool
	 * @return	$this
	 */
	public function set($path, $content, $filename = FALSE)
	{
		if ( ! is_resource($content))
		{
			if ($filename)
			{
				$handle = fopen($content, 'r');
			}
			else
			{
				$handle = tmpfile();

				fwrite($handle, $content);
	
				rewind($handle); 
			}
		}
		else
		{
			$handle = $content;
		}
		
		$this->_set($this->_filter_path($path), $handle);
		
		if (is_resource($handle))
		{
			fclose($handle);
		}
		
		return $this;
	}
	
	/**
	 * Read contents of file.
	 * 
	 * @access	public
	 * @param	string
	 * @param	string|resource
	 * @return	bool
	 */
	public function get($path, $handle)
	{
		if ( ! is_resource($handle))
		{
			if ( ! $handle = fopen($handle, 'w'))
				return FALSE;
		}

		$result = $this->_get($this->_filter_path($path), $handle);	
			
		if (is_resource($handle))
		{
			fclose($handle);
		}
			
		return $result;
	}	
	
	/**
	 * Delete
	 * 
	 * @access	public
	 * @param	string
	 * @return	$this
	 */
	public function delete($path)
	{
		return $this->_delete($this->_filter_path($path));
	}	
	
	/**
	 * File size
	 * 
	 * @access	public
	 * @param	string
	 * @return	int
	 */
	public function size($path)
	{
		return $this->_size($this->_filter_path($path));
	}
	
	/**
	 * Whether or not file exists
	 * 
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function exists($path)
	{
		return $this->_exists($this->_filter_path($path));
	}
	
	/**
	 * Get URL
	 * 
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function url($path, $protocol = 'http')
	{
		return $this->_url($this->_filter_path($path), $protocol);
	}	
	
	/**
	 * Filter path
	 * 
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function _filter_path($path)
	{
		return $this->_config['directory'] . trim($path, '/');
	}	
}