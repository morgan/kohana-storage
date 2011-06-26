<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Local file system driver for Storage Module
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_Local extends Storage
{	
	/**
	 * Default config
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_config = array
	(
		'root_path'	=> NULL,
		'url'		=> NULL
	);

	/**
	 * Set
	 * 
	 * @todo	Write in chunks
	 * @access	protected
	 * @param	string
	 * @param	resource
	 * @return	void
	 */
	protected function _set($path, $handle)
	{
		$this->_create_directory($path);		
		
		$path = $this->_config['root_path'] . $path;
		
		if ($target = fopen($path, 'w+'))
		{
			stream_copy_to_stream($handle, $target);
		}
	}

	/**
	 * Get
	 * 
	 * @access	protected
	 * @param	string
	 * @param	resource
	 * @return	bool
	 */
	protected function _get($path, $handle)
	{
		$path = $this->_config['root_path'] . $path;

		if ($source = fopen($path, 'r'))
			return (bool) stream_copy_to_stream($source, $handle);
		else
			return FALSE;
	}	
	
	/**
	 * Delete
	 * 
	 * @access	protected
	 * @param	string
	 * @return	bool
	 */
	protected function _delete($path)
	{
		return unlink($this->_config['root_path'] . $path);
	}	
	
	/**
	 * Size
	 * 
	 * @access	protected
	 * @param	string
	 * @return	int
	 */
	protected function _size($path)
	{
		return (int) @filesize($this->_config['root_path'] . $path);
	}	
	
	/**
	 * Whether or not file exists
	 * 
	 * @access	protected
	 * @param	string
	 * @return	bool
	 */
	protected function _exists($path)
	{
		return file_exists($this->_config['root_path'] . $path);
	}
	
	/**
	 * Get URL
	 * 
	 * @access	protected
	 * @param	string	Path of file 
	 * @param	string	Protocol to prefix to public URL
	 * @return	mixed	string|bool
	 */
	protected function _url($path, $protocol)
	{	
		if (isset($this->_config['url']))
		{
			return $protocol . '://' . $this->_config['url'] . $path;
		}
		
		return FALSE;
	}
	
	/**
	 * Create directory based on current location
	 * 
	 * @access	protected
	 * @param	string
	 * @return	bool
	 */
	protected function _create_directory($path)
	{
		$result = TRUE;

		$segments = explode('/', $path);		
		
		$path = $this->_config['root_path'];
		
		foreach ($segments as $segment)
		{
			// Skip files
			if (strpos($segment, '.'))
				break;
			
			$path .= '/' . $segment;

			// Create directory in relation to root if unable to change directory.
			if ( ! is_dir($path))
			{
				if ( ! mkdir($path))
				{
					$result = FALSE;
				}
			}
		}

		return $result;
	}	
}