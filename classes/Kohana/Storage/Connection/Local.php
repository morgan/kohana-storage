<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Local file system driver for Storage Module
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_Connection_Local extends Storage_Connection
{	
	/**
	 * Default config
	 * 
	 * "root_path" is a pre-existing directory. Any additional pathing will be created if it does 
	 * not exist.
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
	 * @access	protected
	 * @param	string
	 * @param	resource
	 * @param	string
	 * @return	void
	 */
	protected function _set($path, $handle, $mime)
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
	 * Get listing
	 * 
	 * @access	protected
	 * @param	string	Path of file 
	 * @return	mixed
	 */
	protected function _listing($path, $listing)
	{
		$directory = new DirectoryIterator($this->_config['root_path'] . $path);
		
		$path = $listing->path();
		
		foreach ($directory as $item)
		{
			$name = $item->getFilename();

			if ($name[0] === '.' OR $name[strlen($name) - 1] === '~')
				continue;

			$_path = $path . Storage::DELIMITER . $name;
			
			if ($item->isFile())
			{
				$object = Storage_File::factory($_path, $this)
					->size($item->getSize())
					->modified($item->getMTime());
			}
			else if ($item->isDir())
			{
				$object = Storage_Directory::factory($_path, $this);
			}
			
			$listing->set($object);
		}
		
		return $listing;
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
