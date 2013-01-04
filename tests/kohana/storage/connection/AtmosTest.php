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
class Kohana_Storage_Connection_AtmosTest extends Kohana_Storage_ConnectionTest
{
	/**
	 * Verify internet and Atmos has required configuration
	 * 
	 * @access	protected
	 * @return	void
	 */
	public function setUp()
	{
		parent::setUp();

		$config = Kohana::$config->load('storage.atmos');
		
		if ( ! $this->hasInternet() OR ! $config['host'] OR ! $config['uid'] 
			OR ! $config['subtenant_id'] OR ! $config['secret'])
		{
		    $this->markTestSkipped('Storage Atmos driver is not configured.');
		}
	}

	/**
	 * Factory using Atmos configuration
	 * 
	 * @access	public
	 * @return	Storage_Atmos
	 */
	public function factory()
	{
		return Storage_Connection::factory('atmos');
	}
}
