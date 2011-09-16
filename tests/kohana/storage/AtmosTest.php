<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Tests Storage
 *
 * @package		Storage
 * @category	Tests
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011 Micheal Morgan
 * @license		MIT
 */
class Kohana_Storage_AtmosTest extends Kohana_StorageTest
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
    	
        if ( ! $this->hasInternet() || ! $config['host'] || ! $config['uid'] || ! $config['subtenant_id'] || ! $config['secret'])
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
    	return Storage::factory('atmos');
    }
}