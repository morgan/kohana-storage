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
abstract class Kohana_Storage_Connection 
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

		$class = 'Storage_Connection_' . ucfirst(isset($config['driver']) ? $config['driver'] : $connection);
		
		return new $class($config);
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
		$this->_config = Arr::merge(array('directory' => NULL), $this->_config, $config);
	}
	
	/**
	 * Write content to file. If file already exists, it will be overwritten.
	 * 
	 * @access	protected
	 * @param	string
	 * @param	resource
	 * @param	string
	 * @return	void
	 */
	abstract protected function _set($path, $handle, $mime);
	
	/**
	 * Read contents of file.
	 * 
	 * @access	protected
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
	 * @access	protected
	 * @param	string
	 * @return	bool
	 */
	abstract protected function _exists($path);
	
	/**
	 * Get URL
	 * 
	 * @access	protected
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	abstract protected function _url($path, $protocol);	
	
	/**
	 * Get list based on path
	 * 
	 * @access	protected
	 * @param	string
	 * @return	mixed
	 */
	abstract protected function _listing($path, $directory);
	
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
		
		$this->_set($this->_filter_path($path), $handle, Storage::mime($path));
		
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
	 * Get listing
	 * 
	 * @access	public
	 * @param	mixed	NULL|string
	 * @param	mixed	NULL|Storage_Directory
	 * @return	Storage_Directory
	 * @throws	Storage_Exception
	 */
	public function listing($path = NULL, Storage_Directory $directory = NULL)
	{
		$directory = ($directory) ?: Storage_Directory::factory($path, $this);
		
		$listing = $this->_listing($this->_filter_path($path), $directory);
		
		if ( ! $listing instanceof Storage_Directory)
			throw new Storage_Exception('Storage_Connection::listing expecting instance of Storage_Directory.');
		
		return $listing;
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
		$path = $this->_config['directory'] . trim($path, '/');
		
		return ($path) ?: NULL;
	}
}
