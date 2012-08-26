<?php
// Copyright © 2008-2012, EMC Corporation.
// Redistribution and use in source and binary forms, with or without modification, 
// are permitted provided that the following conditions are met:
//
//     + Redistributions of source code must retain the above copyright notice, 
//       this list of conditions and the following disclaimer.
//     + Redistributions in binary form must reproduce the above copyright 
//       notice, this list of conditions and the following disclaimer in the 
//       documentation and/or other materials provided with the distribution.
//     + The name of EMC Corporation may not be used to endorse or promote 
//       products derived from this software without specific prior written 
//       permission.
//
//      THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
//      "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED 
//      TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR 
//      PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS 
//      BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR 
//      CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF 
//      SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS 
//      INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
//      CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) 
//      ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
//      POSSIBILITY OF SUCH DAMAGE.


require_once 'EsuRestApi.php';
//require_once 'PHPUnit/Framework.php';
require_once 'EsuHelpers.php';

/**
 * Unit test for the EsuRestApi class.  Uses phpUnit: http://www.phpunit.de
 * Run with:
 * phpunit EsuRestApiTest path/to/EsuRestApiTest.php
 * 
 * Be sure to set the configuration parameters below to connect to your
 * ESU servers.
 */
class EsuRestApiTest extends PHPUnit_Framework_TestCase {
	/**
	 * UID to run tests with.  Change this value to your UID.
	 */
	private $uid = '<your uid>';
	/**
	 * Shared secret for UID.  Change this value to your UID's shared secret
	 */
	private $secret = '<your key>';
	/**
	 * Hostname or IP of ESU server.  Change this value to your server's
	 * hostname or ip address.
	 */
	private $host = 'api.atmosonline.com';
	
	/**
	 * Port of ESU server (usually 80 or 443)
	 */
	private $port = 443;
	
	/**
	 * If true, clean up objects created by the tests.
	 */
	private $cleanUp = true;
	
	/**
	 * If true, enable debug mode in the API
	 */
	private $debug = true;
	
	
	/**
	 * API object used for test cases
	 */
	private $esu; 
	
	/**
	 * Keeps track of created objects so they can be cleaned up
	 */
	private $cleanup = array();
	
	/**
	 * Set up before a test is run.  Initializes the connection object.
	 */
	public function setUp() {
		$this->esu = new EsuRestApi( $this->host, $this->port, $this->uid, 
			$this->secret );
		$this->esu->setDebug( $this->debug );
		//$this->esu->setTimeout( 180.0 );
	}
	
	/**
	 * Tear down after a test is run.  Cleans up objects that were created
	 * during the test.  Set $cleanUp=false to disable this behavior.
	 */
	public function tearDown() {
		foreach( $this->cleanup as $cleanItem ) {
			try {
				$this->esu->deleteObject( $cleanItem );
			} catch( Exception $e ) {
				print 'Failed to delete ' . $cleanItem . ': ' . $e->getMessage();
			}
		}
	}
	
	//
	// TESTS START HERE
	//
	
	/**
	 * Test creating one empty object.  No metadata, no content.
	 */
	public function testCreateEmptyObject() {
		$id = $this->esu->createObject();
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read back the content
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( '', $content, 'object content wrong' );
		
	}
	
	/**
	 * Test creating one empty object on a path.  No metadata, no content.
	 */
	public function testCreateEmptyObjectOnPath() {
		$path = new ObjectPath( '/' . $this->random8() );
		
		$id = $this->esu->createObjectOnPath( $path );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
	}

	/**
	 * Test creating one empty object on a path.  No metadata, no content.
	 */
	public function testCreateObjectOnPathWithParens() {
		$path = new ObjectPath( '/' . $this->random8() . '(' . $this->random8() . ')' );
		
		$id = $this->esu->createObjectOnPath( $path );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
	}
	
//	/**
//	 * Test creating one empty object on a path.  No metadata, no content.
//	 */
//	public function testCreateObjectOnPathWithUnicode() {
//		$path = new ObjectPath( '/' . $this->random8() . "спасибо" );
//		
//		$id = $this->esu->createObjectOnPath( $path );
//		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
//		$this->cleanup[] = $id;
//	}
	
	
	/**
	 * Test creating an object with content but without metadata
	 */
	public function testCreateObjectWithContent() {
		$id = $this->esu->createObject( null, null, 'hello', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read back the content
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'hello', $content, 'object content wrong' );
	}
	
	/**
	 * Test creating an object with content but without metadata using a path
	 */
	public function testCreateObjectWithContentOnPath() {
		$path = new ObjectPath( '/' . $this->random8() );
		
		$id = $this->esu->createObjectOnPath( $path, null, null, 'hello', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $path;
		
		// Read back the content
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'hello', $content, 'object content wrong using id' );
		
		$content = $this->esu->readObject( $path );
		PHPUnit_Framework_Assert::assertEquals( 'hello', $content, 'object content wrong using path' );
	}
	
	/**
	 * Test creating an object with metadata but no content.
	 */
	public function testCreateObjectWithMetadataOnPath() {
		$path = new ObjectPath( '/' . $this->random8() );
		
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObjectOnPath( $path, null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $path;
		
		// Read and validate the metadata
		$meta = $this->esu->getUserMetadata( $path, null );
		PHPUnit_Framework_Assert::assertEquals( 'foo', $meta->getMetadata( 'listable' )->getValue(), "value of 'listable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'foo2 foo2', $meta->getMetadata( 'listable2' )->getValue(), "value of 'listable2' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar', $meta->getMetadata( 'unlistable' )->getValue(), "value of 'unlistable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar2 bar2', $meta->getMetadata( 'unlistable2' )->getValue(), "value of 'unlistable2' wrong" );
		// Check listable flags
//		PHPUnit_Framework_Assert::assertEquals( true, $meta->getMetadata( 'listable' )->isListable(), "'listable' is not listable" );
//		PHPUnit_Framework_Assert::assertEquals( true, $meta->getMetadata( 'listable2' )->isListable(), "'listable2' is not listable" );
//		PHPUnit_Framework_Assert::assertEquals( false, $meta->getMetadata( 'unlistable' )->isListable(), "'unlistable' is listable" );
//		PHPUnit_Framework_Assert::assertEquals( false, $meta->getMetadata( 'unlistable2' )->isListable(), "'unlistable2' is listable" );
		
	}
	
	/**
	 * Test creating an object with metadata but no content.
	 */
	public function testCreateObjectWithMetadata() {
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read and validate the metadata
		$meta = $this->esu->getUserMetadata( $id, null );
		PHPUnit_Framework_Assert::assertEquals( 'foo', $meta->getMetadata( 'listable' )->getValue(), "value of 'listable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'foo2 foo2', $meta->getMetadata( 'listable2' )->getValue(), "value of 'listable2' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar', $meta->getMetadata( 'unlistable' )->getValue(), "value of 'unlistable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar2 bar2', $meta->getMetadata( 'unlistable2' )->getValue(), "value of 'unlistable2' wrong" );
		// Check listable flags
//		PHPUnit_Framework_Assert::assertEquals( true, $meta->getMetadata( 'listable' )->isListable(), "'listable' is not listable" );
//		PHPUnit_Framework_Assert::assertEquals( true, $meta->getMetadata( 'listable2' )->isListable(), "'listable2' is not listable" );
//		PHPUnit_Framework_Assert::assertEquals( false, $meta->getMetadata( 'unlistable' )->isListable(), "'unlistable' is listable" );
//		PHPUnit_Framework_Assert::assertEquals( false, $meta->getMetadata( 'unlistable2' )->isListable(), "'unlistable2' is listable" );
		
	}

	/**
	 * Test handling signature failures.  Should throw an exception with
	 * error code 1032.
	 */
	public function testSignatureFailure() {
		// break the secret key
		$badSecret = strtoupper( $this->secret );
		$this->esu = new EsuRestApi( $this->host, $this->port, $this->uid, $badSecret );
		//$this->esu->setDebug( true );
		
		try {
			// Create an object.  Should fail.
			$id = $this->esu->createObject();
		} catch( EsuException $e ) {
			//print $e . ' (failure expected)';
			PHPUnit_Framework_Assert::assertEquals( 1032, $e->getCode(), 
				'Expected error code 1032 for signature failure' );
			return;
		}
		$this->fail( 'Exception not thrown!' );
	}
	
	/**
	 * Test general HTTP errors by generating a 404.
	 */
	public function testFourOhFour() {
		// break the context root
		$this->esu->setContext( '/restttt' );
		try {
			$id = $this->esu->createObject();
		} catch( EsuException $e ) {
			//print $e . ' (failure expected)' . "\n";
			PHPUnit_Framework_Assert::assertEquals( 404, $e->getCode(), 
				'Expected error code 404 for not found' );
			return;
		}
		$this->fail( 'Exception not thrown!' );
		
	}
	
	/**
	 * Test reading an object's content
	 */
	public function testReadObject() {
		$id = $this->esu->createObject( null, null, 'hello', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read back the content
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'hello', $content, 'object content wrong' );
		
		// Read back only 2 bytes
		$extent = new Extent( 1, 2 );
		$content = $this->esu->readObject( $id, $extent );
		PHPUnit_Framework_Assert::assertEquals( 'el', $content, 'partial object content wrong' );
	}
	
	/**
	 * Test reading an ACL back
	 */
	public function testReadAcl() {
		// Create an object with an ACL
		$acl = new Acl();
		$acl->addGrant( new Grant( new Grantee( $this->uid, Grantee::USER ), Permission::FULL_CONTROL ) );
		$acl->addGrant( new Grant( Grantee::$OTHER, Permission::READ ) );
		$id = $this->esu->createObject( $acl, null, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read back the ACL and make sure it matches
		$newacl = $this->esu->getAcl( $id );
		PHPUnit_Framework_Assert::assertEquals( $acl, $newacl, "ACLs don't match" );
		
	}
	
	/**
	 * Test reading back user metadata
	 */
	public function testGetUserMetadata() {
		// Create an object with user metadata
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read only part of the metadata
		$mtags = new MetadataTags();
		$mtags->addTag( new MetadataTag( 'listable', true ) );
		$mtags->addTag( new MetadataTag( 'unlistable', false ) );
		$meta = $this->esu->getUserMetadata( $id, $mtags );
		PHPUnit_Framework_Assert::assertEquals( 'foo', $meta->getMetadata( 'listable' )->getValue(), "value of 'listable' wrong" );
		PHPUnit_Framework_Assert::assertNull( $meta->getMetadata( 'listable2' ), "value of 'listable2' should not have been returned" );
		PHPUnit_Framework_Assert::assertEquals( 'bar', $meta->getMetadata( 'unlistable' )->getValue(), "value of 'unlistable' wrong" );
		PHPUnit_Framework_Assert::assertNull( $meta->getMetadata( 'unlistable2' ), "value of 'unlistable2' should not have been returned" );
		
	}
	
	/**
	 * Test deleting user metadata
	 */
	public function testDeleteUserMetadata() {
		// Create an object with metadata
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Delete a couple of the metadata entries
		$mtags = new MetadataTags();
		$mtags->addTag( new MetadataTag( 'listable2', true ) );
		$mtags->addTag( new MetadataTag( 'unlistable2', false ) );
		$this->esu->deleteUserMetadata( $id, $mtags );
		
		// Read back the metadata for the object and ensure the deleted
		// entries don't exist
		$meta = $this->esu->getUserMetadata( $id );
		PHPUnit_Framework_Assert::assertEquals( 'foo', $meta->getMetadata( 'listable' )->getValue(), "value of 'listable' wrong" );
		PHPUnit_Framework_Assert::assertNull( $meta->getMetadata( 'listable2' ), "metadata 'listable2' should have been deleted" );
		PHPUnit_Framework_Assert::assertEquals( 'bar', $meta->getMetadata( 'unlistable' )->getValue(), "value of 'unlistable' wrong" );
		PHPUnit_Framework_Assert::assertNull( $meta->getMetadata( 'unlistable2' ), "metadata 'unlistable2' should have been deleted" );
	}
	
	/**
	 * Test creating object versions
	 */
	public function testVersionObject() {
		// Create an object
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Version the object
		$vid = $this->esu->versionObject( $id );
		$this->cleanup[] = $vid;
		PHPUnit_Framework_Assert::assertNotNull( $vid, 'null version ID returned' );
		
		// Fetch the version and read its data
		$meta = $this->esu->getUserMetadata( $vid );
		PHPUnit_Framework_Assert::assertEquals( 'foo', $meta->getMetadata( 'listable' )->getValue(), "value of 'listable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'foo2 foo2', $meta->getMetadata( 'listable2' )->getValue(), "value of 'listable2' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar', $meta->getMetadata( 'unlistable' )->getValue(), "value of 'unlistable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar2 bar2', $meta->getMetadata( 'unlistable2' )->getValue(), "value of 'unlistable2' wrong" );
		
	}
	
	/**
	 * Test listing the versions of an object
	 */
	public function testListVersions() {
		// Create an object
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Version the object
		$vid1 = $this->esu->versionObject( $id );
		$this->cleanup[] = $vid1;
		PHPUnit_Framework_Assert::assertNotNull( $vid1, 'null version ID returned' );
		$vid2 = $this->esu->versionObject( $id );
		$this->cleanup[] = $vid2;
		PHPUnit_Framework_Assert::assertNotNull( $vid2, 'null version ID returned' );
		
		// List the versions and ensure their IDs are correct
		$versions = $this->esu->listVersions( $id );
		PHPUnit_Framework_Assert::assertEquals( 2, count( $versions ), 'Wrong number of versions returned' );
		PHPUnit_Framework_Assert::assertTrue( array_search( $vid1, $versions ) !== false, 'version 1 not found in version list' );
		PHPUnit_Framework_Assert::assertTrue( array_search( $vid2, $versions ) !== false, 'version 2 not found in version list' );
		PHPUnit_Framework_Assert::assertFalse( array_search( $id, $versions ) !== false, 'base object found in version list' );
	}
	
	/**
	 * Test restoring an older version to the base version
	 */
    public function testRestoreVersion() {
        $id = $this->esu->createObject(null, null, 'Base Version Content', 'text/plain');
        PHPUnit_Framework_Assert::assertNotNull($id, 'null ID returned');
        $this->cleanup[] = $id;

        // Version the object
        $vId = $this->esu->versionObject($id);

        // Update the object content
        $this->esu->updateObject($id, null, null, null, 'Child Version Content -- You should never see me', 'text/plain');

        // Restore the original version
        $this->esu->restoreVersion($id, $vId);

        // Read back the content
        $content = $this->esu->readObject($id, null, null);
        PHPUnit_Framework_Assert::assertEquals( 'Base Version Content', $content, 'object content wrong' );

    }
	
	
	/**
	 * Test listing the system metadata on an object
	 */
	public function testGetSystemMetadata() {
		// Create an object
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read only part of the metadata
		$mtags = new MetadataTags();
		$mtags->addTag( new MetadataTag( 'atime', false ) );
		$mtags->addTag( new MetadataTag( 'ctime', false ) );
		$meta = $this->esu->getSystemMetadata( $id, $mtags );
		PHPUnit_Framework_Assert::assertNotNull( 'foo', $meta->getMetadata( 'atime' ), "value of 'atime' missing" );
		PHPUnit_Framework_Assert::assertNull( $meta->getMetadata( 'mtime' ), "value of 'mtime' should not have been returned" );
		PHPUnit_Framework_Assert::assertNotNull( 'bar', $meta->getMetadata( 'ctime' ), "value of 'ctime' missing" );
		PHPUnit_Framework_Assert::assertNull( $meta->getMetadata( 'gid' ), "value of 'gid' should not have been returned" );
		PHPUnit_Framework_Assert::assertNull( $meta->getMetadata( 'listable' ), "value of 'listable' should not have been returned" );
	}
	
	/**
	 * Test listing objects by a tag
	 */
	public function testListObjects() {
		// Create an object
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;

		// List the objects.  Make sure the one we created is in the list
		$objects = $this->esu->listObjects( 'listable' );
		PHPUnit_Framework_Assert::assertTrue( count( $objects ) > 0, 'No objects returned' );
		PHPUnit_Framework_Assert::assertTrue( array_search( $id, $objects ) !== false, 'object not found in list' );
		
		// Check for unlisted
		try {
			$objects = $this->esu->listObjects( 'unlistable' );
			$this->fail( 'Exception not thrown!' );
		} catch( EsuException $e ) {
			// This should happen.
			PHPUnit_Framework_Assert::assertEquals( 1003, $e->getCode(), 'Expected 1003 for not found' );
		}
	}
	
	/**
	 * Test listing objects by a tag with metadata
	 */
	public function testListObjectsWithMetadata() {
		// Create an object
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;

		// List the objects.  Make sure the one we created is in the list
		$objects = $this->esu->listObjectsWithMetadata( 'listable' );
		PHPUnit_Framework_Assert::assertTrue( count( $objects ) > 0, 'No objects returned' );
		
		$found = false;
		foreach( $objects as $object ) {
			if( $object->getId() == $id ) {
				$found = true;
				// Check metadata
				PHPUnit_Framework_Assert::assertNotNull( $object->getMetadata(), 'Metadata is null' );
				PHPUnit_Framework_Assert::assertTrue( $object->getMetadata()->count() > 0, 'no metadata on object' );
				PHPUnit_Framework_Assert::assertNotNull( $object->getMetadata()->getMetadata( 'unlistable' ),
						'Metadata for unlistable is null' );				
				PHPUnit_Framework_Assert::assertEquals( 'bar',
					$object->getMetadata()->getMetadata( 'unlistable' )->getValue(),
					"value of 'unlistable' wrong" );
			}
		}
		PHPUnit_Framework_Assert::assertTrue( $found !== false, 'object not found in list' );
		
		// Check for unlisted
		try {
			$objects = $this->esu->listObjectsWithMetadata( 'unlistable' );
			$this->fail( 'Exception not thrown!' );
		} catch( EsuException $e ) {
			// This should happen.
			PHPUnit_Framework_Assert::assertEquals( 1003, $e->getCode(), 'Expected 1003 for not found' );
		}
	}
	
	/**
	 * Test fetching listable tags
	 */
	public function testGetListableTags() {
		// Create an object
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'list/able/2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'list/able/not', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// List tags.  Ensure our object's tags are in the list.
		$tags = $this->esu->getListableTags();
		PHPUnit_Framework_Assert::assertNotNull( $tags->getTag( 'listable' ), 'listable tag not returned' );
		PHPUnit_Framework_Assert::assertNotNull( $tags->getTag( 'list' ), 'list/able/2 root tag not returned' );
		PHPUnit_Framework_Assert::assertNull( $tags->getTag( 'list/able/not' ), 'list/able/not tag returned' );
		
		// List child tags
		$tags = $this->esu->getListableTags( 'list/able' );
		PHPUnit_Framework_Assert::assertNull( $tags->getTag( 'listable' ), 'non-child returned' );
		PHPUnit_Framework_Assert::assertNotNull( $tags->getTag( '2' ), 'list/able/2 tag not returned' );
		PHPUnit_Framework_Assert::assertNull( $tags->getTag( 'not' ), 'list/able/not tag returned' );
		
	}
	
	/**
	 * Test listing the user metadata tags on an object
	 */
	public function testListUserMetadataTags() {
		// Create an object
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'list/able/2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'list/able/not', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// List tags
		$tags = $this->esu->listUserMetadataTags( $id );
		PHPUnit_Framework_Assert::assertNotNull( $tags->getTag( 'listable' ), 'listable tag not returned' );
		PHPUnit_Framework_Assert::assertNotNull( $tags->getTag( 'list/able/2' ), 'list/able/2 tag not returned' );
		PHPUnit_Framework_Assert::assertNotNull( $tags->getTag( 'unlistable' ), 'unlistable tag not returned' );
		PHPUnit_Framework_Assert::assertNotNull( $tags->getTag( 'list/able/not' ), 'list/able/not tag not returned' );
		PHPUnit_Framework_Assert::assertNull( $tags->getTag( 'unknowntag' ), 'unknown tag returned' );
		
		// Check listable flag
		PHPUnit_Framework_Assert::assertEquals( true, $tags->getTag( 'listable' )->isListable(), "'listable' is not listable" );
		PHPUnit_Framework_Assert::assertEquals( true, $tags->getTag( 'list/able/2' )->isListable(), "'list/able/2' is not listable" );
		PHPUnit_Framework_Assert::assertEquals( false, $tags->getTag( 'unlistable' )->isListable(), "'unlistable' is listable" );
		PHPUnit_Framework_Assert::assertEquals( false, $tags->getTag( 'list/able/not' )->isListable(), "'list/able/not' is listable" );
	}

// Not supported by Atmos 1.2
//	
//	/**
//	 * Test executing a query.
//	 */
//	public function testQueryObjects() {
//		// Create an object
//		$mlist = new MetadataList();
//		$listable = new Metadata( 'listable', 'foo', true );
//		$unlistable = new Metadata( 'unlistable', 'bar', false );
//		$listable2 = new Metadata( 'list/able/2', 'foo2 foo2', true );
//		$unlistable2 = new Metadata( 'list/able/not', 'bar2 bar2', false );
//		$mlist->addMetadata( $listable );
//		$mlist->addMetadata( $unlistable );
//		$mlist->addMetadata( $listable2 );
//		$mlist->addMetadata( $unlistable2 );
//		$id = $this->esu->createObject( null, $mlist, null, null );
//		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
//		$this->cleanup[] = $id;
//		
//		// Query for all objects for the current UID
//		$query = 'for $h in collection() where $h/maui:MauiObject[uid="' . $this->uid . '"] return $h';
//		$objects = $this->esu->queryObjects( $query );
//		
//		// Ensure the search results contains the object we just created
//		PHPUnit_Framework_Assert::assertTrue( array_search( $id, $objects ) !== false, 'object not found in list' );
//		
//	}
	
	/**
	 * Tests updating an object's metadata
	 */
	public function testUpdateObjectMetadata() {
		// Create an object
		$mlist = new MetadataList();
		$unlistable = new Metadata( 'unlistable', 'foo', false );
		$mlist->addMetadata( $unlistable );
		$id = $this->esu->createObject( null, $mlist, 'hello', null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;

		// Update the metadata
		$unlistable->setValue( 'bar' );
		$this->esu->setUserMetadata( $id, $mlist );
		
		// Re-read the metadata
		$meta = $this->esu->getUserMetadata( $id, null );
		PHPUnit_Framework_Assert::assertEquals( 'bar', $meta->getMetadata( 'unlistable' )->getValue(), "value of 'unlistable' wrong" );
		
		// Check to ensure object contents were not modified
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'hello', $content, 'object content wrong' );
		
	}
	
	/**
	 * Tests changing an object's ACL
	 */
	public function testUpdateObjectAcl() {
		// Create an object with an ACL
		$acl = new Acl();
		$acl->addGrant( new Grant( new Grantee( $this->uid, Grantee::USER ), Permission::FULL_CONTROL ) );
		$other = new Grant( Grantee::$OTHER, Permission::READ );
		$acl->addGrant( $other );
		$id = $this->esu->createObject( $acl, null, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read back the ACL and make sure it matches
		$newacl = $this->esu->getAcl( $id );
		PHPUnit_Framework_Assert::assertEquals( $acl, $newacl, "ACLs don't match" );

		// Change the ACL
		$other->setPermission( Permission::NONE );
		$this->esu->setAcl( $id, $acl );
		
		// Read back the ACL and make sure it matches
		$newacl = $this->esu->getAcl( $id );
		PHPUnit_Framework_Assert::assertEquals( $acl, $newacl, "ACLs don't match" );
	}
	
	/**
	 * Tests updating an object's contents
	 */
	public function testUpdateObjectContent() {
		// Create an object
		$id = $this->esu->createObject( null, null, 'hello', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Update part of the content
		$extent = new Extent( 1,1 );
		$this->esu->updateObject( $id, null, null, $extent, 'u', null );
		
		// Read back the content and check it
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'hullo', $content, 'object content wrong' );
	}
	
	/**
	 * Test replacing an object's entire contents
	 */
	public function testReplaceObjectContent() {
		// Create an object
		$id = $this->esu->createObject( null, null, 'hello', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Update all of the content
		$this->esu->updateObject( $id, null, null, null, 'bonjour', null );
		
		// Read back the content and check it
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'bonjour', $content, 'object content wrong' );
	}
	
	/**
	 * Test the UploadHelper's create method
	 */
	public function testCreateHelper() {
		// use a blocksize of 1 to test multiple transfers.
		$uploadHelper = new UploadHelper( $this->esu, 1 );
		$tempFile = tmpfile();
		fprintf( $tempFile, 'hello' );
		fseek( $tempFile, 0 );
		
		// Create an object from our file stream
		$id = $uploadHelper->createObjectFromStream( $tempFile );
		if( $uploadHelper->isFailed() ) {
			throw $uploadHelper->getError();
		}
		$this->cleanup[] = $id;
		
		// Read contents back and check them
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'hello', $content, 'object content wrong' );
	}
	
	public function testCreateHelper2() {
		// use a blocksize of 1 to test multiple transfers.
		$uploadHelper = new UploadHelper( $this->esu, 1 );
		
		$tmpfname = tempnam( sys_get_temp_dir(), 'FOO');

		$handle = fopen($tmpfname, 'w');
		fwrite($handle, 'hello');
		fclose($handle);
		
		// Create an object from our file stream
		$id = $uploadHelper->createObjectFromFile( $tmpfname );
		unlink( $tmpfname );
		if( $uploadHelper->isFailed() ) {
			throw $uploadHelper->getError();
		}
		$this->cleanup[] = $id;
		
		
		// Read contents back and check them
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'hello', $content, 'object content wrong' );
		
		
	}
	
	/**
	 * Test the UploadHelper's update method
	 */
	public function testUpdateHelper() {
		// use a blocksize of 1 to test multiple transfers.
		$uploadHelper = new UploadHelper( $this->esu, 1 );

		// Create an object with content.
		$id = $this->esu->createObject( null, null, 'Four score and twenty years ago', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
	
		// update the object contents
		$tempFile = tmpfile();
		fprintf( $tempFile, 'hello' );
		fseek( $tempFile, 0 );
		
		$uploadHelper->updateObjectFromStream( $id, $tempFile );
		if( $uploadHelper->isFailed() ) {
			throw $uploadHelper->getError();
		}
		
		// Read contents back and check them
		$content = $this->esu->readObject( $id );
		PHPUnit_Framework_Assert::assertEquals( 'hello', $content, 'object content wrong' );
	}
	
	/**
	 * Test the UploadHelper's update method
	 */
//	public function testUpdateHelperLarge() {
//		// use a blocksize of 1 to test multiple transfers.
//		$uploadHelper = new UploadHelper( $this->esu );
//
//		// Create an object with content.
//		$id = $this->esu->createObject( null, null, 'Four score and twenty years ago', 'text/plain' );
//		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
//		$this->cleanup[] = $id;
//	
//		// update the object contents
//		$tempFile = tmpfile();
//		for( $i = 0; $i<200000; $i++ ) {
//			fprintf( $tempFile, 'hellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohello\n' );
//		}
//		fseek( $tempFile, 0 );
//		
//		$uploadHelper->updateObjectFromStream( $id, $tempFile, null, null, false );
//		if( $uploadHelper->isFailed() ) {
//			throw $uploadHelper->getError();
//		}
//				
//		// Download the file
//		$tempFile2 = tmpfile();
//		$downloadHelper = new DownloadHelper( $this->esu );
//		$downloadHelper->readObjectToStream( $id, $tempFile2, false );
//		
//		// Get file sizes
//		fseek( $tempFile, 0, SEEK_END );
//		fseek( $tempFile2, 0, SEEK_END );
//		$sizeIn = ftell( $tempFile );
//		$sizeOut = ftell( $tempFile2 );
//		PHPUnit_Framework_Assert::assertEquals( $sizeIn, $sizeOut, 'File sizes do not match' );
//		
//	}
	
	/**
	 * Tests the download helper.  Tests both single and multiple requests.
	 */
	public function testDownloadHelper() {
		// Create an object with content.
		$id = $this->esu->createObject( null, null, 'Four score and twenty years ago', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Download the content
		$tempFile = tmpfile();
		$downloadHelper = new DownloadHelper( $this->esu );
		$downloadHelper->readObjectToStream( $id, $tempFile, false );
		if( $downloadHelper->isFailed() ) {
			throw $downloadHelper->getError();
		}
		
		// Check the download
		fseek( $tempFile, 0 );
		$data = fgets( $tempFile );
		PHPUnit_Framework_Assert::assertEquals( 'Four score and twenty years ago', $data, 'object content wrong' );
		
		// Download again 1 byte in a request
		fseek( $tempFile, 0 );
		$downloadHelper = new DownloadHelper( $this->esu, 1 );
		$downloadHelper->readObjectToStream( $id, $tempFile, false );
		if( $downloadHelper->isFailed() ) {
			throw $downloadHelper->getError();
		}
		
		// Check the download
		fseek( $tempFile, 0 );
		$data = fgets( $tempFile );
		PHPUnit_Framework_Assert::assertEquals( 'Four score and twenty years ago', $data, 'object content wrong' );
	}
	
	public function testIsDirectory() {
		$op1 = new ObjectPath( '/not/a/dir' );
		$op2 = new ObjectPath( '/is/a/dir/' );
		
		PHPUnit_Framework_Assert::assertFalse( $op1->isDirectory(), 'Should not be a directory' );
		PHPUnit_Framework_Assert::assertTrue( $op2->isDirectory(), 'Should be a directory' );
	}
	
	public function testListDirectory() {
		$dir = $this->random8();
		$file = $this->random8();
		$dir2 = $this->random8();
		
		$dirPath = new ObjectPath( '/' . $dir . '/' );
		$op = new ObjectPath( '/' . $dir . '/' . $file );
		$dirPath2 = new ObjectPath( '/' . $dir . '/' . $dir2 . '/' );
		
		$dirId = $this->esu->createObjectOnPath( $dirPath );
		$id = $this->esu->createObjectOnPath( $op );
		$this->esu->createObjectOnPath( $dirPath2 );
		PHPUnit_Framework_Assert::assertNotNull( $dirId, 'null ID returned' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		
		// Read back the objects
		$data = $this->esu->readObject( $op );
		PHPUnit_Framework_Assert::assertEquals( '', $data, 'object content wrong' );
		
		// List the contents of the directory
		$dirList = $this->esu->listDirectory( $dirPath );
		PHPUnit_Framework_Assert::assertTrue( $this->directoryContains( $dirList, $op ), 'Directory missing file' );
		PHPUnit_Framework_Assert::assertTrue( $this->directoryContains( $dirList, $dirPath2 ), 'Directory missing subdir' );
	}
	
    public function testGetShareableUrl() {
		// Create an object with content.
		$id = $this->esu->createObject( null, null, 'Four score and twenty years ago', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;

        // Now + 4 hours
        $expiration = time() + 3600 * 4;
        $url = $this->esu->getShareableUrl( $id, $expiration );
        
        echo 'Sharable URL: ' . $url . "\n";
        
        // Read the data back
        $req = &new HTTP_Request2( $url );
        $response = $req->send();
		if( $response->getStatus() > 399 ) {
			die( 'HTTP request to ' . $url . ' failed: ' . $response->getReasonPhrase() );
		}
        $content = $response->getBody();
		PHPUnit_Framework_Assert::assertEquals( 'Four score and twenty years ago', $content, 'object content wrong' );
    }
    
    public function testGetShareableUrlWithPath() {
		// Create an object with content.
		$path = new ObjectPath( '/' . $this->random8() );
		
		$id = $this->esu->createObjectOnPath( $path, null, null, 'Four score and twenty years ago', 'text/plain' );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $path;

        // Now + 4 hours
        $expiration = time() + 3600 * 4;
        $url = $this->esu->getShareableUrl( $path, $expiration );
        
        echo 'Sharable URL: ' . $url . "\n";
        
        // Read the data back
        $req = &new HTTP_Request2( $url );
        $response = $req->send();
		if( $response->getStatus() > 399 ) {
			PHPUnit_Framework_Assert::assertTrue( false, 'HTTP status ' . $req->getStatus() );
		}
        $content = $response->getBody();
        echo 'content: ' . $content;
		PHPUnit_Framework_Assert::assertEquals( 'Four score and twenty years ago', $content, 'object content wrong' );
    }
    
    	/**
	 * Test creating an object with metadata but no content.
	 */
	public function testGetAllMetadata() {
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2 foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2 bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$acl = new Acl();
		$acl->addGrant( new Grant( new Grantee( $this->uid, Grantee::USER ), Permission::FULL_CONTROL ) );
		$acl->addGrant( new Grant( Grantee::$OTHER, Permission::READ ) );
		$id = $this->esu->createObject( $acl, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read and validate the metadata
		list ($meta,$newacl) = $this->esu->getAllMetadata( $id );
		PHPUnit_Framework_Assert::assertEquals( 'foo', $meta->getMetadata( 'listable' )->getValue(), "value of 'listable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'foo2 foo2', $meta->getMetadata( 'listable2' )->getValue(), "value of 'listable2' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar', $meta->getMetadata( 'unlistable' )->getValue(), "value of 'unlistable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar2 bar2', $meta->getMetadata( 'unlistable2' )->getValue(), "value of 'unlistable2' wrong" );
		PHPUnit_Framework_Assert::assertEquals( $acl, $newacl, "ACLs don't match" );
	}
	
	public function testChecksum() {
		$data = 'hello world';
		$ck = new Checksum( 'SHA0' );
		$ck->update( $data );
		PHPUnit_Framework_Assert::assertEquals( 'SHA0/11/9fce82c34887c1953b40b3a2883e18850c4fa8a6', "$ck", "value of 'checksum' wrong" );
	}
	
	public function testCreateChecksum() {
		$ck = new Checksum( 'SHA0' );
		$id = $this->esu->createObject( null, null, 'hello', 'text/plain', $ck );
		$this->cleanup[] = $id;
		PHPUnit_Framework_Assert::assertTrue( strlen(''.$ck) > 0, 'Checksum is empty' );
	}

	/**
	 * Tests reading back a checksum.  Note that for this test to operate fully, you
	 * should create a policy that creates an erasure coded replica for objects with
	 * metadata "policy=erasure"
	 */
	public function testReadChecksum()
	{
		$ck = new Checksum( 'SHA0' );
		$mlist = new MetadataList();
		$meta = new Metadata( 'policy', 'erasure', false );
		$mlist->addMetadata( $meta );
		$id = $this->esu->createObject(null, $mlist, 'Four score and seven years ago', 'text/plain', $ck);
		$this->cleanup[] = $id;
		PHPUnit_Framework_Assert::assertTrue( strlen(''.$ck) > 0, 'Checksum is empty' );
		
		// Read back.
		$ck2 = new Checksum( 'SHA0' );
		$content = $this->esu->ReadObject($id, null, null, $ck2);
		PHPUnit_Framework_Assert::assertEquals('Four score and seven years ago', $content, 'object content wrong');
		if( $ck2->getExpectedValue() != null ) {
			PHPUnit_Framework_Assert::assertEquals( ''.$ck, ''.$ck2, 'object checksum wrong');
			PHPUnit_Framework_Assert::assertEquals( $ck2->getExpectedValue(), ''.$ck2, 'expected checksum wrong');
		}
	}
	
	/**
	 * Tests upload and download helpers with checksumming enabled.
     * Note that for this test to operate fully, you
	 * should create a policy that creates an erasure coded replica for objects with
	 * metadata "policy=erasure"	 
	 */
	public function testChecksumming() {
		// use a blocksize of 1 to test multiple transfers.
		$uploadHelper = new UploadHelper( $this->esu );
		$uploadHelper->setComputeChecksums( true );

		$mlist = new MetadataList();
		$meta = new Metadata( 'policy', 'erasure', false );
		$mlist->addMetadata( $meta );
		$ck = new Checksum( 'SHA0' );
		
		// update the object contents
		$tempFile = tmpfile();
		for( $i = 0; $i<200000; $i++ ) {
			fprintf( $tempFile, 'hellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohellohello\n' );
		}
		fseek( $tempFile, 0 );
		
		$id = $uploadHelper->createObjectFromStream( $tempFile, null, $mlist, false );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		if( $uploadHelper->isFailed() ) {
			throw $uploadHelper->getError();
		}
				
		// Download the file
		$tempFile2 = tmpfile();
		$downloadHelper = new DownloadHelper( $this->esu );
		$downloadHelper->setChecksumming( true );
		$downloadHelper->readObjectToStream( $id, $tempFile2, false );
		
		// Get file sizes
		fseek( $tempFile, 0, SEEK_END );
		fseek( $tempFile2, 0, SEEK_END );
		$sizeIn = ftell( $tempFile );
		$sizeOut = ftell( $tempFile2 );
		PHPUnit_Framework_Assert::assertEquals( $sizeIn, $sizeOut, 'File sizes do not match' );
	}	
	
	/**
	 * The signature algorithm declares that spaces in header values should be normalized.
	 */
	public function testNormalizeSpace() {
		$mlist = new MetadataList();
		$listable = new Metadata( 'listable', 'foo', true );
		$unlistable = new Metadata( 'unlistable', 'bar', false );
		$listable2 = new Metadata( 'listable2', 'foo2   foo2', true );
		$unlistable2 = new Metadata( 'unlistable2', 'bar2      bar2', false );
		$mlist->addMetadata( $listable );
		$mlist->addMetadata( $unlistable );
		$mlist->addMetadata( $listable2 );
		$mlist->addMetadata( $unlistable2 );
		$id = $this->esu->createObject( null, $mlist, null, null );
		PHPUnit_Framework_Assert::assertNotNull( $id, 'null ID returned' );
		$this->cleanup[] = $id;
		
		// Read and validate the metadata
		$meta = $this->esu->getUserMetadata( $id, null );
		PHPUnit_Framework_Assert::assertEquals( 'foo', $meta->getMetadata( 'listable' )->getValue(), "value of 'listable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'foo2   foo2', $meta->getMetadata( 'listable2' )->getValue(), "value of 'listable2' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar', $meta->getMetadata( 'unlistable' )->getValue(), "value of 'unlistable' wrong" );
		PHPUnit_Framework_Assert::assertEquals( 'bar2      bar2', $meta->getMetadata( 'unlistable2' )->getValue(), "value of 'unlistable2' wrong" );
		
	}
	
	public function testRename() {
		$op1 = new ObjectPath('/' . $this->random8() . '.tmp');
		$op2 = new ObjectPath('/' . $this->random8() . '.tmp');
		$op3 = new ObjectPath('/' . $this->random8() . '.tmp');
		$op4 = new ObjectPath('/' . $this->random8() . '.tmp');
		$id = $this->esu->createObjectOnPath($op1, null, null, 'Four score and seven years ago', 'text/plain');
		PHPUnit_Framework_Assert::assertNotNull($id, 'null ID returned');
		$this->cleanup[] = $id;

		// Rename the object
		$this->esu->rename($op1, $op2, false);

		// Read back the content
		$content = $this->esu->readObject($op2, null, null);
		PHPUnit_Framework_Assert::assertEquals('Four score and seven years ago', $content, 'object content wrong');

		// Attempt overwrite
		$id = $this->esu->createObjectOnPath($op3, null, null, 'Four score and seven years ago', 'text/plain');
		PHPUnit_Framework_Assert::assertNotNull(id, 'null ID returned');
		$this->cleanup[] = $id;
		$id = $this->esu->createObjectOnPath($op4, null, null, "You shouldn't see me", 'text/plain');
		PHPUnit_Framework_Assert::assertNotNull(id, 'null ID returned');
		$this->cleanup[] = $id;
		$this->esu->rename($op3, $op4, true);

		// Wait for rename to complete
		sleep(5);

		// Read back the content
		$content = $this->esu->readObject($op4, null, null);
		PHPUnit_Framework_Assert::assertEquals('Four score and seven years ago', $content, 'object content wrong (3)');
	}
	
	public function testGetServiceInformation() {
		$si = $this->esu->getServiceInformation();
		
		PHPUnit_Framework_Assert::assertNotNull( $si->getAtmosVersion(), 'Atmos version null' );
	}
	
	public function testGetObjectInfo() {
		$mlist = new MetadataList();
		$meta = new Metadata( 'policy', 'retaindelete', false );
		$mlist->addMetadata( $meta );
		$id = $this->esu->createObject(null, $mlist, 'Four score and seven years ago', 'text/plain');
		$this->cleanup[] = $id;
		
		$info = $this->esu->getObjectInformation($id);
		PHPUnit_Framework_Assert::assertNotNull( $info->objectId, 'Object info ID null' );
		PHPUnit_Framework_Assert::assertNotNull( $info->selection, 'Object info ID null' );
		PHPUnit_Framework_Assert::assertNotNull( $info->expiration, 'Object info ID null' );
		PHPUnit_Framework_Assert::assertNotNull( $info->retention, 'Object info ID null' );
		PHPUnit_Framework_Assert::assertNotNull( $info->replicas, 'Object info ID null' );
		PHPUnit_Framework_Assert::assertTrue( count($info->replicas)>0, 'No replicas in replica array' );
		if( count($info->replicas) > 1 ) {
			PHPUnit_Framework_Assert::assertTrue( $info->replicas[0]->id != $info->replicas[1]->id, 'Replica IDs equal' );
		}
	}

	
	private function directoryContains( $dirList, $op ) {
		print 'Looking for: ' . $op . "\n";
		
		for( $i=0; $i<count($dirList); $i++ ) {
			$entry = $dirList[$i];
			print $entry->getPath() . "\n";
			if( ''.$entry->getPath() == ''.$op  ) {
				return true;
			}
		}
		return false;
	}
	
	private $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 -_';
	private function rand_chars($c, $l, $u = FALSE) {
 		if (!$u) for ($s = '', $i = 0, $z = strlen($c)-1; $i < $l; $x = rand(0,$z), $s .= $c{$x}, $i++);
 		else for ($i = 0, $z = strlen($c)-1, $s = $c{rand(0,$z)}, $i = 1; $i != $l; $x = rand(0,$z), $s .= $c{$x}, $s = ($s{$i} == $s{$i-1} ? substr($s,0,-1) : $s), $i=strlen($s));
 		return $s;
	}
	
	private function random8() {
		return $this->rand_chars( $this->chars, 8, true );
	}
}
