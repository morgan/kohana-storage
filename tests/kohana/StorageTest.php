<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Tests Storage Module
 *
 * @package		Storage
 * @category	Tests
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011 Micheal Morgan
 * @license		MIT
 */
abstract class Kohana_StorageTest extends Unittest_TestCase
{
    /**
     * Factory to return Storage object configured for driver.
     * 
     * @access	public
     * @return	Storage
     */
    abstract public function factory();
    
	/**
	 * Tests object deletion
	 * 
	 * @covers	Storage::factory
	 * @covers	Storage::set
	 * @covers	Storage::delete
	 * @covers	Storage::exists
	 * @access	public
	 * @return	void
	 */
	public function test_delete()
	{
		// Get cache busted path
		$path = $this->_get_path();
		
		// Create Storage object
		$storage = $this->factory();

		// Verify file does not exist
		$this->assertEquals(FALSE, $storage->exists($path));
		
		// Write test file
		$storage->set($path, $this->_get_content());
		
		// Verify file exists
		$this->assertEquals(TRUE, $storage->exists($path));	

		// Delete file
		$storage->delete($path);
		
		// Verify file does not exist
		$this->assertEquals(FALSE, $storage->exists($path));
	}
	
	/**
	 * Tests creation
	 * 
	 * @covers	Storage::factory
	 * @covers	Storage::set
	 * @covers	Storage::url
	 * @covers	Storage::delete
	 * @access	public
	 * @return	void
	 */
	public function test_set()
	{
		// Get cache busted path
		$path = $this->_get_path();

		// Create random content
		$content = $this->_get_content();
		
		// Create Storage object
		$storage = $this->factory();
		
		// Create file
		$storage->set($path, $content);

		// Verify random content matches remote file
		$this->assertEquals($content, file_get_contents($storage->url($path)));
		
		// Cleanup test file
		$storage->delete($path);
	}
	
	/**
	 * Test file size
	 * 
	 * @covers	Storage::factory
	 * @covers	Storage::set
	 * @covers	Storage::size
	 * @covers	Storage::delete
	 * @access	public
	 * @return	void
	 */
	public function test_size()
	{
		// Get cache busted path
		$path = $this->_get_path();
		
		// Create temp file
		$handle = tmpfile();
		
		// Write test content
		fwrite($handle, $this->_get_content());

		// Reset handle back to the beginning
		rewind($handle);
		
		// Stat handle
		$stat = fstat($handle);
		
		// Create Storage object
		$storage = $this->factory();
		
		// Create file
		$storage->set($path, $handle);
		
		// Assert file size equals stat size
		$this->assertEquals($storage->size($path), $stat['size']);
		
		// Delete remote file
		$storage->delete($path);
	}
	
	/**
	 * Tests get
	 * 
	 * @covers	Storage::factory
	 * @covers	Storage::set
	 * @covers	Storage::get
	 * @covers	Storage::delete
	 * @access	public
	 * @return	void
	 */
	public function test_get()
	{
		// Get cache busted path
		$path = $this->_get_path();
		
		// Create random content
		$content = $this->_get_content();
		
		// Create Storage Object
		$storage = $this->factory();
		
		// Create file
		$storage->set($path, $content);
		
		// Temp path
		$local = tempnam(sys_get_temp_dir(), 'test');

		// Download to temp file
		$this->assertEquals(TRUE, $storage->get($path, $local));		
		
		// Compare content
		$this->assertEquals($content, file_get_contents($local));		
		
		// Delete local file
		unset($local);
		
		// Cleanup test file
		$storage->delete($path);		
	}
	
	/**
	 * Test Storage::set and Storage::get on sample files.
	 * 
	 * @access	public
	 * @return	void
	 */
	public function test_samples()
	{
		$directory = Kohana::config('storage.unittest.samples');
		
		if (is_dir($directory))
		{
			$storage = $this->factory();
			
			foreach (scandir($directory, 1) as $file)
			{
				if (substr($file, 0, 1) != '.' AND is_file($directory . $file))
				{
					$file = $directory . $file;
					
					$handle = fopen($file, 'r');
					
					$stat = fstat($handle);
			
					$path = $this->_get_path();

					$storage->set($path, $handle);

					// Compare remote file sizes
					$this->assertEquals($stat['size'], $storage->size($path));					
					
					// Temp path
					$local = tempnam(sys_get_temp_dir(), 'test');
			
					// Verify get success
					$this->assertEquals(TRUE, $storage->get($path, $local));

					// Compare local file sizes
					$this->assertEquals($stat['size'], filesize($local));					
					
					// Delete local file
					unset($local);
					
					// Cleanup test file
					$storage->delete($path);			
				}
			}
		}
	}
	
	/**
	 * Get test path. Including timestamp in filename for cache busting CDNs.
	 * 
	 * @access	protected
	 * @return	string
	 */
	protected function _get_path()
	{
		return 'kohana-storage-test/test-' . time() . '.txt';
	}
	
	/**
	 * Generate sample content
	 * 
	 * @access	protected
	 * @return	string
	 */
	protected function _get_content()
	{
		return str_repeat('storage-test+', rand(1, 50));
	}
	
    /**
     * Provider for test_hash
     * 
     * @access	public
     * @return	array
     */
    public function provider_hash()
    {
    	return array
    	(
    		// Test directory path and extension
    		array
    		(
    			'path/to/file.txt',
    			'path/to/35/14/3514e48cde714107b7e26e82dfa49e48.txt'
    		),
    		// Test extension
    		array
    		(
    			'file.txt',
    			'3d/8e/3d8e577bddb17db339eae0b3d9bcf180.txt'
    		),
    		// Test no extension
    		array
    		(
    			'file',
    			'8c/7d/8c7dd922ad47494fc02c388e12c00eac'
    		),
    		// Test directory path and no extension
    		array
    		(
    			'path/to/file',
    			'path/to/e0/00/e000689eedd2eb7cbc8a547da64983e8'
    		)
    	);
    } 
    
    /**
     * Test hash
     * 
     * @covers			Storage::hash
     * @dataProvider	provider_hash
     * @access			public
     * @return			void
     */
    public function test_hash($set, $expected)
    {	
    	$this->assertEquals(Storage::hash($set), $expected);
    }	
}