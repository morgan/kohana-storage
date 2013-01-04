<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Tests Storage
 *
 * @group		storage
 * @package		Storage
 * @category	Tests
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MITw
 */
class Kohana_Storage_Connection_S3Test extends Kohana_Storage_ConnectionTest
{
	/**
	 * Verify internet and S3 has required configuration
	 * 
	 * @access	protected
	 * @return	void
	 */
	public function setUp()
	{
		parent::setUp();
		
		$config = Kohana::$config->load('storage.s3');

		if ( ! $this->hasInternet() OR ! $config['key'] OR ! $config['secret'] OR ! $config['bucket'])
		{
		    $this->markTestSkipped('Storage S3 driver is not configured.');
		}
	}

	/**
	 * Factory using S3 configuration
	 * 
	 * @access	public
	 * @return	Storage_S3
	 */
	public function factory()
	{
		return Storage_Connection::factory('s3');
	}
}
