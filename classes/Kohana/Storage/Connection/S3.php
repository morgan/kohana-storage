<?php defined('SYSPATH') or die('No direct script access.');
/**
 * AWS S3 driver for Storage Module
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_Connection_S3 extends Storage_Connection
{
	/**
	 * Default config
	 * 
	 * AWS access credentials are located in the AWS Portal. Reference "config/storage.php" for 
	 * information on config.
	 * 
	 * @access	protected
	 * @var		array
	 */
	protected $_config = array
	(
		'key'					=> NULL,
		'secret'				=> NULL,
		'bucket'				=> NULL,
		'cname'					=> NULL,
		'public'				=> FALSE, 
		'preauth'				=> 30,
		'path_style'			=> FALSE,
		'certificate_authority'	=> FALSE
	);
	
	/**
	 * AmazonS3 from AWS SDK
	 * 
	 * @access	protected
	 * @var		AmazonS3
	 */
	protected $_driver;

	/**
	 * Default S3 URL
	 * 
	 * @access	protected
	 * @var		string
	 */
	protected $_url = '.s3.amazonaws.com/';
	
	/**
	 * Load connection
	 * 
	 * @access	protected
	 * @return	AmazonS3
	 */
	protected function _load()
	{
		if ($this->_driver === NULL)
		{
			require_once Kohana::find_file('vendor', 'aws-sdk/sdk.class');

			$this->_driver = new AmazonS3(Arr::extract($this->_config, 
				array('key', 'secret', 'certificate_authority')));

			$this->_driver->enable_path_style($this->_config['path_style']);
		}
		
		return $this->_driver;
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
		$this->_load();
		
		$acl = $this->_config['public'] ? AmazonS3::ACL_PUBLIC : AmazonS3::ACL_PRIVATE;
		
		$this->_driver->create_object($this->_config['bucket'], $path, 
			array('fileUpload' => $handle, 'acl' => $acl, 'contentType' => $mime));
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
		$this->_load();
		
		$this->_driver->get_object($this->_config['bucket'], $path, 
			array('fileDownload' => $handle));
		
		return TRUE;
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
		$this->_load();
		
		$this->_driver->delete_object($this->_config['bucket'], $path);
		
		return TRUE;
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
		$this->_load();
		
		return $this->_driver->get_object_filesize($this->_config['bucket'], $path, FALSE);
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
		$this->_load();
		
		return $this->_driver->if_object_exists($this->_config['bucket'], $path);
	}
	
	/**
	 * Get URL
	 * 
	 * @access	protected
	 * @param	string	Path of file 
	 * @param	string	Protocol to prefix to public URL
	 * @return	string
	 */
	protected function _url($path, $protocol)
	{
		if ( ! $this->_config['public'])
		{
			$this->_load();
			
			return $this->_driver->get_object($this->_config['bucket'], $path, 
				array('preauth' => time() + $this->_config['preauth']));
		}
		else
			return $protocol . '://' . $this->_get_url() . $path;
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
		
		$config = array('delimiter' => Storage::DELIMITER);

		if ($path !== NULL)
		{
			// Prefix requires trailing delimiter "path/"
			$config['prefix'] = $path . Storage::DELIMITER;
		}
		
		$response = $this->_driver->list_objects($this->_config['bucket'], $config);

		foreach ($response->body->CommonPrefixes as $directory)
		{
			$name = (string) $directory->Prefix;

			$object = Storage_Directory::factory($name, $this);
			
			$listing->set($object);
		}
		
		foreach ($response->body->Contents as $file)
		{
			$name = (string) $file->Key;
			
			$object = Storage_File::factory($name, $this)
				->size((int) $file->Size)
				->modified(strtotime($file->LastModified));
		
			$listing->set($object);
		}
		
		return $listing;
	}
	
	/**
	 * Get URL based on whether CNAME is defined or default to S3 bucket URL
	 * 
	 * @access	protected
	 * @return	string
	 */
	protected function _get_url()
	{
		return ($this->_config['cname']) 
			? $this->_config['cname'] 
			: $this->_config['bucket'] . $this->_url;
	}
}
