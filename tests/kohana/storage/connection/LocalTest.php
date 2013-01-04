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
class Kohana_Storage_Connection_LocalTest extends Kohana_Storage_ConnectionTest
{
	/**
	 * Verify internet and local has required configuration
	 * 
	 * @access	protected
	 * @return	void
	 */
	public function setUp()
	{
		parent::setUp();

		$config = Kohana::$config->load('storage.local');
		
		if ( ! $config['root_path'] OR ! $config['url'])
		{
		    $this->markTestSkipped('Storage Local driver is not configured.');
		}
	}

	/**
	 * Factory using Local configuration
	 * 
	 * @access	public
	 * @return	Storage_Local
	 */
	public function factory()
	{
		return Storage_Connection::factory('local');
	}
}
