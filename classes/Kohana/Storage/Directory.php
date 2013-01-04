<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Storage Directory Listing
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_Directory extends Storage_Listing_Abstract implements Iterator, Countable
{
	/**
	 * Cache listing
	 * 
	 * @access	protected
	 * @var		mixed	NULL|array
	 */
	protected $_listing;
	
	/**
	 * Directories
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_directories = array();
	
	/**
	 * Files
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_files = array();

	/**
	 * Whether or not directory has been loaded
	 * 
	 * @access	protected
	 * @var		bool
	 */
	protected $_loaded = FALSE;

	/**
	 * Whether or not file
	 * 
	 * @access	public
	 * @return	bool
	 */
	public function is_file()
	{
		return FALSE;
	}
	
	/**
	 * Whether or not file
	 * 
	 * @access	public
	 * @return	bool
	 */
	public function is_directory()
	{
		return TRUE;
	}
	
	/**
	 * Load
	 * 
	 * @access	public
	 * @return	$this
	 */
	public function load()
	{
		if ( ! $this->_loaded)
		{
			$this->_connection()->listing($this->path(), $this);

			$this->_loaded = TRUE;
		}
		
		return $this;
	}
	
	/**
	 * Get or set directories
	 * 
	 * @access	public
	 * @param	mixed	array|NULL
	 * @return	mixed	$this|array
	 */
	public function directories(array $directories = NULL)
	{
		$this->load();
		
		if ($directories === NULL)
			return $this->_directories;
		
		$this->_directories = $directories;
		
		// clear cache
		$this->_listing = NULL;
		
		return $this;
	}
	
	/**
	 * Get or set files
	 * 
	 * @access	public
	 * @param	mixed	array|NULL
	 * @return	mixed	$this|array
	 */
	public function files(array $files = NULL)
	{
		$this->load();
		
		if ($files === NULL)
			return $this->_files;
			
		$this->_files = $files;
		
		// clear cache
		$this->_listing = NULL;
		
		return $this;
	}
	
	/**
	 * Merge directories and files into single array
	 * 
	 * @access	public
	 * @return	array
	 */
	public function as_array()
	{
		$this->load();
		
		if ($this->_listing === NULL)
		{
			$this->_listing = array_merge($this->_directories, $this->_files);
		}

		return $this->_listing;
	}
	
	/**
	 * Set Resource
	 * 
	 * @access	public
	 * @return	$this
	 * @throws	Storage_Exception
	 */
	public function set($listing)
	{
		// If setting occurs, mark directory as loaded
		$this->_loaded = TRUE;
		
		if ($listing instanceof Storage_Directory)
		{
			$this->_directories[$listing->name()] = $listing;
		}
		else if ($listing instanceof Storage_File)
		{
			$this->_files[$listing->name()] = $listing;
		}
		else
			throw new Storage_Exception('Unsupported type.');

		// clear cache
		$this->_listing = NULL;
		
		return $this;
	}
	
	/**
	 * Get Resource
	 * 
	 * @access	public
	 * @return	Storage_Listing|bool
	 */
	public function get($name)
	{
		$this->as_array();
		
		if (isset($this->_listing[$name]))
			return $this->_listing[$name];
		
		return FALSE;
	}
	
	/**
	 * Whether or not loaded
	 * 
	 * @access	public
	 * @return	bool
	 */
	public function is_loaded()
	{
		return $this->_loaded;
	}
	
	/**
	 * Clear object
	 * 
	 * @access	public
	 * @return	$this
	 */
	public function clear()
	{
		$this->_loaded = FALSE;
		
		$this->_listing = NULL;
		
		$this->_directories = $this->_files = array();
	}
	
	/**
	 * Parent
	 * 
	 * @access	public
	 * @return	mixed	Storage_Directory|bool
	 */
	public function parent()
	{
		if (NULL === $path = $this->path())
			return FALSE;
		
		$segments = explode(Storage::DELIMITER, $path);
		
		unset($segments[count($segments) - 1]);
		
		$path = implode(Storage::DELIMITER, $segments);
		
		return Storage_Directory::factory($path, $this->_connection());
	}

	/**
	 * Iterator interface
	 * 
	 * @access	public
	 * @return	mixed
	 */
	public function rewind()
	{
		$this->as_array();
		
		return reset($this->_listing);
	}
	
	/**
	 * Iterator interface
	 * 
	 * @access	public
	 * @return	mixed
	 */
	public function current()
	{
		$this->as_array();
		
		return current($this->_listing);
	}

	/**
	 * Iterator interface
	 * 
	 * @access	public
	 * @return	mixed
	 */
	public function key()
	{
		$this->as_array();
		
		return key($this->_listing);
	}
	
	/**
	 * Iterator interface
	 * 
	 * @access	public
	 * @return	mixed
	 */
	public function next()
	{
		$this->as_array();
		
		return next($this->_listing);
	}
	
	/**
	 * Iterator interface
	 * 
	 * @access	public
	 * @return	mixed
	 */
	public function valid()
	{
		$this->as_array();
		
		return key($this->_listing) !== NULL;
	}
	
	/**
	 * Countable interface
	 * 
	 * @access	public
	 * @return	mixed
	 */
	public function count()
	{
		$this->as_array();
		
		return count($this->_listing);
	}
}
