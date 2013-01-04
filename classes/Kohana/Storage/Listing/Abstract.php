<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Listing
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
abstract class Kohana_Storage_Listing_Abstract
{
	/**
	 * Factory pattern
	 * 
	 * @access	public
	 * @param	string
	 * @param	mixed	NULL|Storage_Connection
	 * @return	Storage_Listing
	 */
	public static function factory($path, Storage_Connection $connection = NULL)
	{
		$name = get_called_class();
		
		$class = new $name($path);

		if ($connection !== NULL)
		{
			$class->connection($connection);
		}
		
		return $class;
	}
	
	/**
	 * Whether or not file
	 * 
	 * @access	public
	 * @return	bool
	 */
	abstract public function is_file();
	
	/**
	 * Whether or not directory
	 * 
	 * @access	public
	 * @return	bool
	 */
	abstract public function is_directory();
	
	/**
	 * Path
	 * 
	 * @access	protected
	 * @var		string
	 */
	protected $_path;
	
	/**
	 * Name
	 * 
	 * @access	protected
	 * @var		mixed	bool|NULL|string
	 */
	protected $_name = FALSE;
	
	/**
	 * Connection
	 * 
	 * @access	protected
	 * @var		Storage_Connection
	 */
	protected $_connection;
	
	/**
	 * Initialize
	 * 
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function __construct($path)
	{
		if (FALSE == $path = trim($path, Storage::DELIMITER))
		{
			$path = NULL;
		}
		
		$this->_path = $path;
	}
	
	/**
	 * To string
	 * 
	 * @access	public
	 * @return	string
	 */
	public function __toString()
	{
		return $this->name();
	}
	
	/**
	 * Get path
	 * 
	 * @access	public
	 * @return	mixed	NULL|string
	 */
	public function path()
	{
		return $this->_path;
	}
	
	/**
	 * Name
	 * 
	 * @access	public
	 * @return	string
	 */
	public function name()
	{
		// Cache name
		if ($this->_name === FALSE)
		{
			if ($this->_path)
			{
				$segments = explode(Storage::DELIMITER, $this->_path);

				$this->_name = end($segments);
			}

			$this->_name = ($this->_name) ?: NULL;
		}
		
		return $this->_name;
	}
	
	/**
	 * Set or get connection
	 * 
	 * @access	public
	 * @param	mixed	NULL|Storage_Connection
	 * @return	mixed	$this|NULL|Storage_Connection
	 */
	public function connection(Storage_Connection $connection = NULL)
	{
		if ($connection === NULL)
			return $this->_connection;
			
		$this->_connection = $connection;
		
		return $this;
	}

	/**
	 * Manage local connection.
	 * 
	 * If local connection not set, attempt to load default. If no connection configured, an 
	 * exception is thrown.
	 * 
	 * @access	protected
	 * @return	Storage_Connection
	 * @throws	Storage_Exception
	 */
	protected function _connection()
	{
		if (NULL === $connection = $this->_connection)
		{
			$connection = Storage_Connection::factory();
		}
		
		if ($connection === NULL)
			throw new Storage_Exception('Storage_Listing expecting configured connection.');
			
		return $connection;
	}
}
