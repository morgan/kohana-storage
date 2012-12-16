<?php defined('SYSPATH') or die('No direct script access.');
/**
 * EMC Atmos driver for Storage Module
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_Connection_Atmos extends Storage_Connection
{
	/**
	 * Default config
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_config = array
	(
		'host'		=> NULL,
		'uid'		=> NULL,
		'secret'	=> NULL,
		'port'		=> 443,
		'preauth'	=> 30
	);

	/**
	 * EsuRestApi from EMC Wrapper
	 * 
	 * @access	protected
	 * @var		EsuRestApi
	 */
	protected $_connection;

	/**
	 * Upload Helper
	 * 
	 * @access	protected
	 * @var		UploadHelper
	 */
	protected $_upload_helper;
	
	/**
	 * Download Helper
	 * 
	 * @access	protected
	 * @var		DownloadHelper
	 */
	protected $_download_helper;
	
	/**
	 * Load connection
	 * 
	 * @access	protected
	 * @return	EsuRestApi
	 */
	protected function _load()
	{
		if ($this->_connection === NULL)
		{
			require_once Kohana::find_file('vendor', 'emc-atmos/src/EsuRestApi');

			$this->_connection = new EsuRestApi
			(
				$this->_config['host'], 
				$this->_config['port'], 
				$this->_config['subtenant_id'] . '/' . $this->_config['uid'], 
				$this->_config['secret']
			);
		}
		
		return $this->_connection;
	}
	
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
		try
		{
			$helper = $this->_get_upload_helper();
			
			$helper->setMimeType($mime);
			
			if ($helper->createObjectFromStreamOnPath($path, $handle) === NULL)
			{
				$helper->updateObjectFromStream($path, $handle);
			}
			
			return TRUE;
		}
		catch (Exception $e)
		{
			return FALSE;
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
		try
		{
			$this->_get_download_helper()->readObjectToStream($path, $handle);
			
			return TRUE;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
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
		try
		{
			$this->_connection->deleteObject($path);
			
			return TRUE;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
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
		try
		{
			return (int) $this->_connection
				->getSystemMetadata($path)
				->getMetadata('size')
				->getValue();
		}
		catch (Exception $e)
		{
			return 0;
		}
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
		try
		{
			$this->_connection->getSystemMetadata($path);
			
			return TRUE;
		}
		catch (Exception $e)
		{
			return FALSE;
		}
	}
	
	/**
	 * Get URL
	 * 
	 * The Atmos SDK generates a preauth URL based on the current protocol and port settings. Given
	 * that most of the class is using private properties and methods (not allowing it to be 
	 * effectively extended), leaves no choice but to perform a string replace in order to 
	 * provide protocol options on a per request basis.
	 * 
	 * @access	protected
	 * @param	string	Path of file 
	 * @param	string	Protocol to prefix to public URL
	 * @return	mixed	string|bool
	 */
	protected function _url($path, $protocol)
	{	
		$url = FALSE;
		
		try
		{
			$url = $this->_connection->getShareableUrl($path, time() + $this->_config['preauth']);
			
			$port = ($protocol == 'https') ? 443 : 80;

			if ($this->_config['port'] != $port)
			{
				$esu = $this->_connection->getProtocolInformation();

				$replace = $protocol . '://' . $this->_config['host'] . ':' . $port;
				
				$url = str_replace($esu['accessScheme'], $replace, $url);
			}
		}
		catch (Exception $e) 
		{
			$url = FALSE;
		}
		
		return $url;
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

		foreach ($this->_connection->listDirectory($this->_filter_path($path)) as $item)
		{
			$_path = $path . Storage::DELIMITER . $item->getName();
			
			if ($item->getType() == 'directory')
			{
				$listing->set(Storage_Directory::factory($_path, $this));
			}
			else
			{
				$meta = $this->_connection->getSystemMetadata($this->_filter_path($_path));

				$file = Storage_File::factory($_path, $this)
					->size($meta->getMetadata('size')->getValue())
					->modified(strtotime($meta->getMetadata('mtime')->getValue()));
				
				$listing->set($file);
			}
		}
		
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
		$this->_load();
		
		return new ObjectPath('/' . parent::_filter_path($path));
	}
	
	/**
	 * Get Download Helper
	 * 
	 * @access	protected
	 * @return	DownloadHelper
	 */
	protected function _get_download_helper()
	{
		$this->_load();
		
		if ($this->_download_helper === NULL)
		{
			require_once Kohana::find_file('vendor', 'emc-atmos/src/EsuHelpers');
			
			$this->_download_helper = new DownloadHelper($this->_connection);
		}
		
		return $this->_download_helper;
	}
	
	/**
	 * Get Download Helper
	 * 
	 * @access	protected
	 * @return	DownloadHelper
	 */
	protected function _get_upload_helper()
	{
		$this->_load();
		
		if ($this->_upload_helper === NULL)
		{
			require_once Kohana::find_file('vendor', 'emc-atmos/src/EsuHelpers');
			
			$this->_upload_helper = new UploadHelper($this->_connection);
		}
		
		return $this->_upload_helper;
	}
}
