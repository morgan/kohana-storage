<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Tests Storage Helpers
 *
 * @group		storage
 * @package		Storage
 * @category	Tests
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */
class Kohana_StorageTest extends Unittest_TestCase
{
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
