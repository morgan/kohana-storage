<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Native FTP driver for Storage Module
 *
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_Connection_FTP extends Storage_Connection
{
	/**
	 * Default config2
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_config = array
	(
		'host'		=> NULL,
		'username'	=> NULL,
		'password'	=> NULL,
		'url'		=> NULL,
		'port'		=> 21,
		'timeout'	=> 90,
		'passive'	=> TRUE,
		'ssl'		=> TRUE,
		'transfer'	=> FTP_BINARY
	);

	/**
	 * FTP Connection
	 *
	 * @access	protected
	 * @var		ftp_stream
	 */
	protected $_connection;

	/**
	 * Cache origin directory for resetting back after directory operations.
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_origin = Storage::DELIMITER;

	/**
	 * Load connection
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _load()
	{
		if ($this->_connection === NULL)
		{
			$call = $this->_config['ssl'] ? 'ftp_ssl_connect' : 'ftp_connect';

			if ( ! $this->_connection = $call($this->_config['host'], $this->_config['port'], $this->_config['timeout']))
				throw new Storage_Exception('Storage_Connection_FTP unable to establish connection.');

			if ($this->_config['username'] !== NULL && $this->_config['password'] !== NULL)
			{
				if ( ! ftp_login($this->_connection, $this->_config['username'], $this->_config['password']))
					throw new Storage_Exception('Storage FTP driver failed to authenticate.');
			}

			if ($this->_config['passive'])
			{
				ftp_pasv($this->_connection, TRUE);
			}

			$this->_origin = ftp_pwd($this->_connection);
		}

		return $this->_connection;
	}

	/**
	 * If set, terminate connection during destruct
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
		if ($this->_connection)
		{
			ftp_close($this->_connection);
		}
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

		$this->_create_directory($path);

		ftp_fput($this->_connection, $path, $handle, $this->_config['transfer']);
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

		return ftp_fget($this->_connection, $handle, $path, $this->_config['transfer']);
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

		return ftp_delete($this->_connection, $path);
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

		$size = ftp_size($this->_connection, $path);

		if ($size < 0)
			return 0;
		else
			return $size;
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

		return ftp_size($this->_connection, $path) > -1;
	}

	/**
	 * Get URL
	 *
	 * FTP only supports public files (in contrast to drivers like S3 and Cloud Files)
	 *
	 * @access	protected
	 * @param	string	Path of file
	 * @return	bool|string
	 */
	protected function _url($path, $protocol)
	{
		$this->_load();

		if ( ! $this->_config['url'])
			return FALSE;

		return $protocol . '://' . $this->_config['url'] . $path;
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

		/*
		ftp_chdir($this->_connection, $path);

		foreach (ftp_nlist($this->_connection, NULL) as $name)
		{
			if ($name[0] === '.' OR $name[strlen($name) - 1] === '~')
				continue;

			$item = ftp_raw($this->_connection, 'MLST ' . $name);
			$item = explode(';', $item[1]);

			$name = trim($item[7]);

			$_path = $path . Storage::DELIMITER . $name;

			$segments = explode('=', $item[0]);

			$type = end($segments);

			if ($type == 'dir')
			{
				$object = Storage_Directory::factory($_path, $this);
			}
			else if ($type == 'file')
			{
				$size = explode('=', $item[1]);

				$modified = explode('=', $item[2]);

				$object = Storage_File::factory($_path, $this)
					->size(end($size))
					->modified(strtotime(end($modified)));
			}
			else
				throw new Storage_Exception('Unkown type: ' . $type);

			$listing->set($object);
		}
		*/

		$filetypes = array(
			'-' => 'file',
			'd' => 'directory',
			'l' => 'link'
		);

		$data = ftp_rawlist($this->_connection, $path);

		foreach($data as $line)
		{
			// first line, skip it
			if (substr(strtolower($line), 0, 5) == 'total')
				continue;

			preg_match('/'. str_repeat('([^\s]+)\s+', 7) .'([^\s]+) (.*)/', $line, $matches); # Here be Dragons

			list($permissions, $children, $owner, $group, $size, $month, $day, $time, $name) = array_slice($matches, 1);

			// if it's not a file, directory or link, I don't really care to know about it :-) comment out the next line if you do
			if ( ! in_array($permissions[0], array_keys($filetypes)))
				continue;

			$type = $filetypes[$permissions[0]];

			if (strpos($time, ':'))
			{
				if (strlen($month) === 3)
				{
					$month = date_parse_from_format('M', $month);
					$month = $month['month'];
				}

				$_time = mktime(substr($time, 0, 2), substr($time, -2), 0, $month, $day);
			}
			else
			{
				$_time = mktime(0,0,0,$month, $day, $time);
			}

			$date = date('d/m/y H:i', $_time);

			/*
			$files[$name] = array(
				'type'        => $type,
				'permissions' => substr($permissions, 1),
				'children'    => $children,
				'owner'       => $owner,
				'group'       => $group,
				'size'        => $size,
				'date'        => $date
			);
			*/

			$_path = $path.Storage::DELIMITER. $name;

			$object = Storage_File::factory($_path, $this);

			if ($type === 'file')
			{
				$object->size($size)
					->modified(strtotime($date));
			}

			$listing->set($object);
		}

		// ftp_chdir($this->_connection, $this->_origin);

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

		$segments = explode(Storage::DELIMITER, $path);

		$path = '';

		foreach ($segments as $segment)
		{
			// Skip files
			if (strpos($segment, '.'))
				break;

			$path .= Storage::DELIMITER . $segment;

			// Create directory in relation to root if unable to change directory.
			if ( ! @ftp_chdir($this->_connection, $path))
			{
				ftp_chdir($this->_connection, Storage::DELIMITER);

				if ( ! ftp_mkdir($this->_connection, $path))
				{
					$result = FALSE;
				}
			}
		}

		ftp_chdir($this->_connection, $this->_origin);

		return $result;
	}
}
