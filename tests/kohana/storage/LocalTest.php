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
class Kohana_Storage_LocalTest extends Kohana_StorageTest
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

    	$config = Kohana::config('storage.local');
    	
        if ( ! $config['root_path'] || ! $config['url'])
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
    	return Storage::factory('local');
    }
}