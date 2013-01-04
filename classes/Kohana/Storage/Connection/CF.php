<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Rackspace Cloud Files driver for Storage Module
 * 
 * Public or Private is managed at a container level with Cloud Files. At this time, there is no 
 * way to authenticate individual objects (like preauth with AWS S3). For this reason, I have 
 * opted to not creating containers based on the root segment of the path. Containers are 
 * conceptually used like AWS S3 buckets.
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_Connection_Cf extends Storage_Connection
{	
	/**
	 * Default config
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_config = array
	(
		'username'	=> NULL,
		'api_key'	=> NULL,
		'container'	=> NULL,
		'public'	=> FALSE
	);

	/**
	 * Cloud Files container
	 * 
	 * @access	protected
	 * @var		CF_Container
	 */
	protected $_container;
	
	/**
	 * Load connection
	 * 
	 * @access	protected
	 * @return	CF_Authentication
	 */
	protected function _load()
	{
		if ($this->_container === NULL)
		{
			require_once Kohana::find_file('vendor', 'rs-cf/cloudfiles');
			
			$auth = new CF_Authentication($this->_config['username'], $this->_config['api_key']);
			$auth->authenticate();
			
			$connection = new CF_Connection($auth);

			try
			{
				$this->_container = $connection->get_container($this->_config['container']);
			}
			catch (Exception $e)
			{
				$this->_container = $connection->create_container($this->_config['container']);
				
				if ($this->_config['public'])
				{
					$this->_container->make_public();
				}
			}
		}

		return $this->_container;
	}
	
	/**
	 * Set
	 * 
	 * @access	protected
	 * @param	string
	 * @param	resource
	 * @param	string
	 * @return	$this
	 */
	protected function _set($path, $handle, $mime)
	{
		if ($object = $this->_get_object($path, TRUE))
		{
			$object->content_type = $mime;
			
			$stat = fstat($handle);
			
			$object->write($handle, $stat['size']);
		}
	}
	
	/**
	 * Read
	 * 
	 * @access	protected
	 * @param	string
	 * @param	string
	 * @return	bool
	 */
	protected function _get($path, $handle)
	{
		if ($object = $this->_get_object($path))
		{
			try
			{
				return $object->stream($handle);
			}
			catch (Exception $e)
			{
				return FALSE;
			}
		}
		
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
		if ($object = $this->_get_object($path))
		{
			try
			{
				return $this->_container->delete_object($object);
			}
			catch (Exception $e)
			{
				return FALSE;
			}
		}
		
		return FALSE;
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
		if ($object = $this->_get_object($path))
			return $object->content_length;
		else
			return 0;
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
		if ($this->_get_object($path))
			return TRUE;
		else
			return FALSE;
	}
	
	/**
	 * Get URL
	 * 
	 * @access	protected
	 * @param	string	Path of file 
	 * @return	string|NULL
	 */
	protected function _url($path, $protocol)
	{
		if ($object = $this->_get_object($path))
		{
			if ($this->_container->is_public())
			{
				if ($protocol == 'https')
					return $object->public_ssl_uri();
				else
					return $object->public_uri();
			}
		}
		
		return NULL;
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
		$this->_load();

		$marker = ($path) ? $path . Storage::DELIMITER : NULL;
		
		foreach ($this->_container->get_objects(0, $marker, $marker, NULL, Storage::DELIMITER) as $item)
		{
			if ($item instanceof CF_Object)
			{
				$segments = explode(Storage::DELIMITER, $item->name);

				$name = end($segments);
				
				$object = Storage_File::factory($path . Storage::DELIMITER . $name, $this)
					->size($item->content_length)
					->modified(strtotime($item->last_modified));
				
				$listing->set($object);
			}
			else if (isset($item['subdir']))
			{
				$segments = explode(Storage::DELIMITER, trim($item['subdir'], Storage::DELIMITER));

				$_path = $path . Storage::DELIMITER . end($segments);
				
				$listing->set(Storage_Directory::factory($_path, $this));
			}
		}
		
		return $listing;
	}
	
	/**
	 * Get Clould Files object
	 * 
	 * @access	protected
	 * @param	string
	 * @param	bool
	 * @return	CF_Object|bool
	 */
	protected function _get_object($path, $create = FALSE)
	{
		$this->_load();

		try 
		{
			return $this->_container->get_object($path);
		}
		catch (Exception $e)
		{
			if ($create)
				return $this->_container->create_object($path);
		}
		
		return FALSE;
	}
}
