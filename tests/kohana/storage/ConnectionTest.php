<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Tests Storage Connection
 *
 * @group		storage
 * @package		Storage
 * @category	Tests
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
abstract class Kohana_Storage_ConnectionTest extends Unittest_TestCase
{
	/**
	 * Root directory for testing
	 * 
	 * @access	protected
	 * @var		string
	 */
	protected $_directory = 'kohana-storage-test';
	
	/**
	 * Factory to return Storage object configured for driver.
	 * 
	 * @access	public
	 * @return	Storage
	 */
	abstract public function factory();

	/**
	 * Check whether or not to skip test
	 * 
	 * @access	protected
	 * @return	void
	 */
	public function setUp()
	{
		parent::setUp();

		if ( ! Kohana::$config->load('storage.unittest.enabled'))
		{
			$this->markTestSkipped('Storage unit test not enabled.');
		}
	}  

	/**
	 * Tests object deletion
	 * 
	 * @covers	Storage_Connection::factory
	 * @covers	Storage_Connection::set
	 * @covers	Storage_Connection::delete
	 * @covers	Storage_Connection::exists
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
	 * @covers	Storage_Connection::factory
	 * @covers	Storage_Connection::set
	 * @covers	Storage_Connection::url
	 * @covers	Storage_Connection::mime
	 * @covers	Storage_Connection::delete
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

		// Use Request to get content and headers
		$response = Request::factory($storage->url($path))->execute();
		
		// Verify random content matches remote file
		$this->assertEquals($content, $response->body());

		// Parse mime from Content-Type header
		$type = current(explode(';', $response->headers('Content-Type')));
		
		// Verify path mime matches Response
		$this->assertEquals(Storage::mime($path), $type);
		
		// Cleanup test file
		$storage->delete($path);
	}
	
	/**
	 * Test file size
	 * 
	 * @covers	Storage_Connection::factory
	 * @covers	Storage_Connection::set
	 * @covers	Storage_Connection::size
	 * @covers	Storage_Connection::delete
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
	 * @covers	Storage_Connection::factory
	 * @covers	Storage_Connection::set
	 * @covers	Storage_Connection::get
	 * @covers	Storage_Connection::delete
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
	 * Test listing
	 * 
	 * @covers	Storage_Connection::set
	 * @covers	Storage_Connection::listing
	 * @covers	Storage_Connection::delete
	 * @covers	Storage_Directory::factory
	 * @covers	Storage_Directory::set
	 * @covers	Storage_Directory::get
	 * @covers	Storage_Directory::parent
	 * @covers	Storage_File::factory
	 * @access	public
	 * @return	void
	 */
	public function test_listing()
	{
		// Root directory for testing listing
		$segment_1 = 'listing';
		
		// Name of directory within listing to look for
		$segment_2 = time() . '_' . rand();
		
		// Get cache busted path
		$path = implode(Storage::DELIMITER, array($this->_directory, $segment_1, $segment_2)) . Storage::DELIMITER;

		// Set file name
		$file = time() . '.txt';

		// Create Storage Object
		$connection = $this->factory();
		
		// Create file
		$connection->set($path . $file, $this->_get_content());
		
		// Create listing
		$listing = $connection->listing($this->_directory . Storage::DELIMITER . $segment_1);
		
		// Verify successful directory listing for path
		$this->assertInstanceOf('Storage_Directory', $listing, 'Expecting directory listing for path.');
		
		// Attempt to retrieve directory from listing
		$this->assertInstanceOf('Storage_Directory', $listing->get($segment_2), 'Expecting new directory to exist within listing.');
		
		// Test directory parent
		$this->assertEquals($this->_directory, $listing->parent()->name(), 'Directory parent does not match.');
		
		// Attempt to retrieve file from listing
		$this->assertInstanceOf('Storage_File', $listing->get($segment_2)->get($file), 'Expecting new file to exist within listing.');
		
		// Cleanup test file
		$this->assertEquals(TRUE, $connection->delete($path . $file), 'Verify file has been deleted.');	
	}
	
	/**
	 * Test Storage::set and Storage::get on sample files.
	 * 
	 * @access	public
	 * @return	void
	 */
	public function test_samples()
	{
		$directory = Kohana::$config->load('storage.unittest.samples');
		
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
		return $this->_directory . Storage::DELIMITER . 'test-' . time() . '.txt';
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
}
