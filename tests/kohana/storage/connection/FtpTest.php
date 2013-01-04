<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Tests Storage
 *
 * @group		storage
 * @package		Storage
 * @category	Tests
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_Connection_FtpTest extends Kohana_Storage_ConnectionTest
{
	/**
	 * Verify internet and FTP has required configuration
	 * 
	 * @access	protected
	 * @return	void
	 */
	public function setUp()
	{
		parent::setUp();

		$config = Kohana::$config->load('storage.ftp');

		if ( ! $this->hasInternet() OR ! $config['host'] OR ! $config['username'] OR ! $config['password'])
		{
			$this->markTestSkipped('Storage FTP driver is not configured.');
		}
	}

	/**
	 * Factory using FTP configuration
	 * 
	 * @access  public
	 * @return  Storage_Ftp
	 */
	public function factory()
	{
		return Storage_Connection::factory('ftp');
	}
}
