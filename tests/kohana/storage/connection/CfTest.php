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
class Kohana_Storage_Connection_CfTest extends Kohana_Storage_ConnectionTest
{
	/**
	 * Verify internet and Cloud Files has required configuration
	 * 
	 * @access	protected
	 * @return	void
	 */
	public function setUp()
	{
		parent::setUp();

		$config = Kohana::$config->load('storage.cf');
		
		if ( ! $this->hasInternet() || ! $config['username'] || ! $config['api_key'])
		{
			$this->markTestSkipped('Storage Cloud Files driver is not configured.');
		}
	}

	/**
	 * Factory using Cloud Files configuration
	 * 
	 * @access	public
	 * @return	Storage_Cf
	 */
	public function factory()
	{
		return Storage_Connection::factory('cf', array('public' => TRUE));
	}
}
