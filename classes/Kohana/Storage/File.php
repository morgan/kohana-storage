<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Storage File
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_File extends Storage_Listing_Abstract
{
	/**
	 * Extension
	 * 
	 * @access	protected
	 * @var		mixed	bool|NULL|string
	 */
	protected $_extension = FALSE;
	
	/**
	 * Size
	 * 
	 * @access	protected
	 * @var		string
	 */
	protected $_size;

	/**
	 * Modified Time
	 * 
	 * @access	protected
	 * @var		int
	 */
	protected $_modified = 0;
	
	/**
	 * Meta
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_meta = array();
	
	/**
	 * Whether or not file
	 * 
	 * @access	public
	 * @return	bool
	 */
	public function is_file()
	{
		return TRUE;
	}
	
	/**
	 * Whether or not file
	 * 
	 * @access	public
	 * @return	bool
	 */
	public function is_directory()
	{
		return FALSE;
	}
	
	/**
	 * Extension
	 * 
	 * @access	public
	 * @return	mixed	NULL|string
	 */
	public function extension()
	{
		// Cache extension
		if ($this->_extension === FALSE)
		{
			if ($this->_path !== NULL)
			{
				$this->_extension = strtolower(pathinfo($this->_path, PATHINFO_EXTENSION));
			}
			else
			{
				$this->_extension = NULL;
			}
		}
		
		return $this->_extension;
	}
	
	/**
	 * MIME type
	 * 
	 * @access	public
	 * @return	string
	 */
	public function mime()
	{
		return Storage::mime($this->_path);
	}
	
	/**
	 * Set or get time modified
	 * 
	 * @access	public
	 * @param	int
	 * @return	mixed	int|$this
	 */
	public function modified($time = NULL)
	{
		if ($time === NULL)
			return $this->_modified;
		
		$this->_modified = (int) $time;
		
		return $this;
	}
	
	/**
	 * Write content to storage.
	 * 
	 * @access	public
	 * @param	mixed	string|resource
	 * @param	bool
	 * @return	$this
	 */
	public function set($content, $filename = FALSE)
	{
		$this->_connection()->set($this->_path, $content, $filename);
		
		return $this;
	}
	
	/**
	 * Read contents of file.
	 * 
	 * @access	public
	 * @param	string|resource
	 * @return	bool
	 */
	public function get($handle)
	{
		return $this->_connection()->get($this->_path, $handle);
	}
	
	/**
	 * Delete
	 * 
	 * @access	public
	 * @return	$this
	 */
	public function delete()
	{
		$this->_connection()->delete($this->_path);
		
		return $this;
	}
	
	/**
	 * Get or set size
	 * 
	 * @access	public
	 * @param	string
	 * @return	mixed	$this|NULL|int
	 */
	public function size($size = NULL)
	{
		if ($size === NULL)
		{
			if ($this->_size === NULL)
			{
				$this->_size = $this->_connection()->size($this->_path);
			}
			
			return $this->_size;
		}	
		
		$this->_size = $size;
		
		return $this;
	}
	
	/**
	 * Whether or not file exists
	 * 
	 * @access	public
	 * @return	bool
	 */
	public function exists()
	{
		return $this->_connection()->exists($this->_path);
	}
	
	/**
	 * Get URL
	 * 
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function url($protocol = 'http')
	{
		return $this->_connection()->url($this->_path, $protocol);
	}
	
	/**
	 * Get or set meta, array as first param overwrites all meta
	 * 
	 * @access	protected
	 * @param	mixed	NULL|array
	 * @param	mixed	NULL
	 * @return	mixed	NULL|array
	 */
	public function meta($key = NULL, $value = NULL)
	{
		if ($key === NULL)
			return $this->_meta;
		else if ( ! is_array($key) && $value === NULL && isset($this->_meta[$key]))
			return $this->_meta[$key];
		
		if (is_array($key))
		{
			$this->_meta = $key;
		}
		else
		{
			$this->_meta[$key] = $value;
		}
		
		return $this;
	}
}
