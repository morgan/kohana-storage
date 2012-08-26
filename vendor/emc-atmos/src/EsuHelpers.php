<?php
// Copyright © 2008 - 2012 EMC Corporation.
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


/**
 * The progress listener interface is used to report upload and download
 * progress.  Create a class that implements this interface and register it
 * with the helper by calling setListener().
 */
interface ProgressListener {
	/**
	 * This callback will be invoked after a chunk has been transferred.
	 * @param array $progress        the progress to report.
	 * @param array $results         this operation's results.
	 *
	 */
	public function notifyProgress( array $progress, array &$results = null );

	/**
	 * This callback will be invoked after the transfer has completed.
	 * @param array $results         this operation's results.
	 */
	public function notifyDone( array $progress = null, array &$results = null );
	/**
	 * This callback will be invoked if there is an error during the transfer.
	 * @param array $progress        the progress causing the error.
	 * @param Exception $exception   the exception that caused the error.
	 */
	public function notifyError( array $progress = null, $exception );
}


/**
 * Helper class to create and update objects.  For large transfers, the content
 * generally needs to be transferred to the server in smaller chunks.  This
 * class reads data from either a file or a stream and incrementally uploads it
 * to the server.  The class also supports the registering of a listener object
 * to report status back to the calling application.
 */
class UploadHelper {
	private $esu;
	private $buffSize;
	const   DEFAULT_BUFFSIZE = 4194304; // 4MB
	private $startTime;
	private $currentBytes;
	private $totalBytes;
	private $complete;
	private $failed = false;
	private $error;
	private $closeStream;
	private $stream;
	private $listener;
	private $checksum;
	private $computeChecksums = false;
	private $mimeType = 'application/octet-stream';
	private $debug = false;

	/**
	 * Creates a new upload helper.
	 * @param EsuApi $esuApi the API connection object to use to communicate
	 * with the server
	 * @param integer $buffSize the size of the buffer to use for transfers.  By
	 * default, data will be transferred to the server in 4MB chunks.
	 */
	public function __construct( $esuApi, $buffSize = UploadHelper::DEFAULT_BUFFSIZE ) {
		$this->esu = $esuApi;
		$this->buffSize = $buffSize;
	}

	/**
	 * Creates a new object on the server with the contents of the given file,
	 * acl and metadata.
	 * @param string $file the path to the file to upload
	 * @param Acl $acl the ACL to assign to the new object.  Optional.  If not
	 * specified, the server will generate a default ACL for the file.
	 * @param MetadataList $metadata The metadata to assign to the new object.
	 * Optional.  If null, no user metadata will be assigned to the new object.
	 * @return ObjectId the identifier of the newly-created object.
	 */
	public function createObjectFromFile( $file, $acl = null, $metadata = null ) {
		$fd = @fopen( $file, 'rb' );
		if( $fd === false ) {
			throw new EsuException( "Could not open file \"$file\"" );
		}
		$b = filesize( $file );
		if( $b > 0 ) { // PHP currently fails >2GB
			$this->totalBytes = $b;
		}
		return $this->createObjectFromStream( $fd, $acl, $metadata, true );
	}

	/**
	 * Creates a new object on the server with the contents of the given file,
	 * acl and metadata.
	 * @param ObjectPath $path the path to create the file on
	 * @param string $file the path to the file to upload
	 * @param Acl $acl the ACL to assign to the new object.  Optional.  If not
	 * specified, the server will generate a default ACL for the file.
	 * @param MetadataList $metadata The metadata to assign to the new object.
	 * Optional.  If null, no user metadata will be assigned to the new object.
	 * @return ObjectId the identifier of the newly-created object.
	 */
	public function createObjectFromFileOnPath( $path, $file, $acl = null, $metadata = null ) {
		$fd = @fopen( $file, 'rb' );
		if( $fd === false ) {
			throw new EsuException( "Could not open file \"$file\"" );
		}
		$b = filesize( $file );
		if( $b > 0 ) { // PHP currently fails >2GB
			$this->totalBytes = $b;
		}
		return $this->createObjectFromStreamOnPath( $path, $fd, $acl, $metadata, true );
	}

	/**
	 * Creates a new object on the server with the contents of the given stream,
	 * acl and metadata.
	 * @param resource $fd the stream to upload.  The stream will be read until
	 * an EOF is encountered.
	 * @param Acl $acl the ACL to assign to the new object.  Optional.  If not
	 * specified, the server will generate a default ACL for the file.
	 * @param MetadataList $metadata The metadata to assign to the new object.
	 * Optional.  If null, no user metadata will be assigned to the new object.
	 * @param boolean closeStream if true, the stream will be closed after
	 * the transfer completes.  If false, the stream will not be closed.
	 * @return ObjectId the identifier of the newly-created object.
	 */
	public function createObjectFromStream( $fd, $acl = null, $metadata = null,
		$closeStream = true ) {

		$this->startTime = time();
		$this->currentBytes = 0;
		$this->complete = false;
		$this->failed = false;
		$this->error = null;
		$this->closeStream = $closeStream;
		$this->stream = $fd;

		// Initialize
		if( $this->computeChecksums ) {
			$this->checksum = new Checksum( 'SHA0' );
		}
		$id = null;

		// First call should be to create object
		try {
			$data = $this->readChunk();
			$this->trace( 'Creating object with initial chunk of ' . strlen( $data ) . ' bytes\n' );
			$this->trace( "Checksum: $this->checksum\n" );
			$id = $this->esu->createObject( $acl, $metadata, $data, $this->mimeType, $this->checksum );
			$this->notifyObjectCreation( $id );
			$this->notifyProgress( 0 );
			if( $data != null ) {
				$this->notifyProgress( strlen( $data ) );
			} else {
				// No data in file? Complete
				$this->notifyDone();
				return $id;
			}

			// Continue appending
			$this->appendChunks( $id );

		} catch( EsuException $e ) {
			$this->notifyError( $e );
			return null;
		}

		return $id;
	}

	/**
	 * Creates a new object on the server with the contents of the given stream,
	 * acl and metadata.
	 * @param ObjectPath $path the path to create the resource on
	 * @param resource $fd the stream to upload.  The stream will be read until
	 * an EOF is encountered.
	 * @param Acl $acl the ACL to assign to the new object.  Optional.  If not
	 * specified, the server will generate a default ACL for the file.
	 * @param MetadataList $metadata The metadata to assign to the new object.
	 * Optional.  If null, no user metadata will be assigned to the new object.
	 * @param boolean closeStream if true, the stream will be closed after
	 * the transfer completes.  If false, the stream will not be closed.
	 * @return ObjectId the identifier of the newly-created object.
	 */
	public function createObjectFromStreamOnPath( $path, $fd, $acl = null, $metadata = null,
		$closeStream = true ) {

		$this->startTime = time();
		$this->currentBytes = 0;
		$this->complete = false;
		$this->failed = false;
		$this->error = null;
		$this->closeStream = $closeStream;
		$this->stream = $fd;

		// Initialize
		if( ! is_a( $path, 'ObjectPath' ) ) {
			throw new EsuException( 'invalid object path' );
		}
		$id = null;
 		if( $this->computeChecksums ) {
			$this->checksum = new Checksum( 'SHA0' );
		}

		// First call should be to create object
		try {
			$data = $this->readChunk();
			$id = $this->esu->createObjectOnPath( $path, $acl, $metadata, $data, $this->mimeType, $this->checksum );
			$this->notifyObjectCreation( $id );
			$this->notifyProgress( 0 );
			if( $data != null ) {
				$this->notifyProgress( strlen( $data ) );
			} else {
				// No data in file? Complete
				$this->notifyDone();
				return $id;
			}

			// Continue appending
			$this->appendChunks( $id );

		} catch( EsuException $e ) {
			$this->notifyError( $e );
			return null;
		}

		return $id;
	}

	/**
	 * Updates an existing object with the contents of the given file, ACL, and
	 * metadata.
	 * @param Identifier $id the identifier of the object to update.
	 * @param string $file the path to the file to replace the object's current
	 * contents with
	 * @param Acl $acl the ACL to update the object with.  Optional.  If not
	 * specified, the ACL will not be modified.
	 * @param MetadataList $metadata The metadata to assign to the object.
	 * Optional.  If null, no user metadata will be modified.
	 */
	public function updateObjectFromFile( $id, $file, $acl = null, $metadata = null ) {
		$fd = @fopen( $file, 'rb' );
		if( $fd === false ) {
			throw new EsuException( "Could not open file \"$file\"" );
		}
		$this->updateObjectFromStream( $id, $fd, $acl, $metadata, true );
	}

	/**
	 * Updates an existing object with the contents of the given stream, ACL, and
	 * metadata.
	 * @param Identifier $id the identifier of the object to update.
	 * @param resource $fd the stream to replace the object's current
	 * contents with.  The stream will be read until an EOF is encountered.
	 * @param Acl $acl the ACL to update the object with.  Optional.  If not
	 * specified, the ACL will not be modified.
	 * @param MetadataList $metadata The metadata to assign to the object.
	 * Optional.  If null, no user metadata will be modified.
	 */
	public function updateObjectFromStream( $id, $fd, $acl = null, $metadata = null,
		$closeStream = true ) {

		$this->startTime = time();
		$this->currentBytes = 0;
		$this->complete = false;
		$this->failed = false;
		$this->error = null;
		$this->closeStream = $closeStream;
		$this->stream = $fd;

		if( $this->computeChecksums ) {
			$this->checksum = new Checksum( 'SHA0' );
		}

		// The first call doesn't have extent so we truncate the remote file
		try {
			$data = $this->readChunk();
			$this->esu->updateObject( $id, $acl, $metadata, null, $data, $this->mimeType, $this->checksum );
  			$this->notifyProgress( 0 );
			if( $data != null ) {
				$this->notifyProgress( strlen( $data ) );
			} else {
				// No data in file? Complete
				$this->notifyDone();
				return $id;
			}

			// Continue appending
			$this->appendChunks( $id );

		} catch( EsuException $e ) {
			$this->notifyError( $e );
			throw $e;
		}

	}

	/**
	 * Gets the current number of bytes that have been uploaded.  Note that
	 * the value returned is a string and not an integer since it may be
	 * >2GB.  You can use the bcmath package to manipulate these values.
	 * @return string the current number of bytes uploaded.
	 */
	public function getCurrentBytes() {
		return $this->currentBytes;
	}

	/**
	 * Gets the total number of bytes to uploaded.  If the total number of bytes
	 * is unknown, the method will return -1.  Note that
	 * the value returned is a string and not an integer since it may be
	 * >2GB.  You can use the bcmath package to manipulate these values.
	 * @return string the total number of bytes to upload.
	 */
	public function getTotalBytes() {
		return $this->totalBytes;
	}

	/**
	 * Returns true if the transfer has completed.
	 * @return boolean true if the transfer has completed, false otherwise.
	 */
	public function isComplete() {
		return $this->complete;
	}


	/**
	 * Returns true if the transfer has failed.
	 * @return boolean true if the transfer has failed, false otherwise.
	 */
	public function isFailed() {
		return $this->failed;
	}

	/**
	 * If the transfer has failed, return the error that caused the failure.
	 * @return Exception the error that caused the transfer to fail.
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Sets a listener to provide feedback on the transfer's progress.
	 * @param ProgressListener $listener the listener to use for feedback.  Set
	 * to null to disable progress notifications.
	 */
	public function setListener( $listener ) {
		$this->listener = $listener;
	}

	/**
	 * If true, checksums will be used when creating/updating blocks.
	 * @param boolean $value
	 */
	public function setComputeChecksums( $value ) {
		$this->computeChecksums = $value;
	}

	/**
	 * Sets the mime type of the object being created/updated.
	 * @param string $mime
	 */
	public function setMimeType( $mime ) {
		$this->mimeType = $mime;
	}

	/**
	 * Returns the mimeType currently set.
	 */
	public function getMimeType() {
		return $this->mimeType;
	}

	/**
	 * Turns debug messages on and off.
	 */
	public function setDebug( $state ) {
		$this->debug = $state;
	}

	/////////////////////
	// Private methods //
	/////////////////////

	/**
	 * Completes the transfer (create or update) by appending chunks until EOF
	 */
	private function appendChunks( $id ) {
		while( ( $data = $this->readChunk() ) != null ) {

			$extent = new Extent( $this->currentBytes, strlen( $data ) );
			$this->trace( "Extent: $extent\n" );
			$this->esu->updateObject( $id, null, null, $extent, $data, $this->mimeType, $this->checksum );
			unset( $extent );
			$this->notifyProgress( strlen( $data ) );
		}
		$this->notifyDone();
	}

	/**
	 * Reads a chunk from the current stream.
	 */
	private function readChunk() {
		if( feof( $this->stream ) ) {
			return null;
		}

		$data = fread( $this->stream, $this->buffSize );
		if( $data === false ) {
			throw new EsuException( 'Read failed at offset ' . $this->currentBytes );
		}

		return $data;
	}

	/**
	 * Used to output debug messages.
	 */
	private function trace( $str ) {
		if( $this->debug ) {
			echo $str;
			echo "\n";
		}
	}

	/**
	 * Tell the listener that an object has been created.

	 * Provides notification about internal state information.
	 * @param $property       the given stateful property.
	 * @param $value          the property's new state.
	 */
	private function notifyObjectCreation( $id ) {
		if( $this->listener != null ) {
			$progress = array( 'objectId' => $id->__toString() );
			$this->listener->notifyProgress( $progress );
		}
	}

	/**
	 * Updates progress on the current transfer and notifies the listener if
	 * required.
	 * @param integer $bytes     the number of bytes transferred.
	 */
	private function notifyProgress( $bytes ) {
		$this->currentBytes = bcadd( $this->currentBytes, $bytes );
		if( $this->listener != null ) {
			$progress = array(
				'currentBytes' => $this->currentBytes,
				'totalBytes' => $this->totalBytes,
				'startTime' => $this->startTime,
			);
			$this->listener->notifyProgress( $progress );
		}
	}

	/**
	 * Marks the current transfer as complete, closes the stream if required,
	 * and notifies the listener.
	 * @param int $flags         the completion flags.
	 */
	private function notifyDone( array &$results = null ) {
		$this->complete = true;

		if( $this->closeStream ) {
			fclose( $this->stream );
			$this->stream = null;
		}

		if( $this->listener != null ) {
			$this->listener->notifyDone( null, $results );
		}
	}

	/**
	 * Fails the current transfer.  Sets the failed flag and notifies the
	 * listener if required.
	 * @param Exception $exception      the failure information.
	 */
	private function notifyError( $exception ) {
		$this->failed = true;
		$this->error = $exception;

		if( $this->closeStream ) {
			fclose( $this->stream );
			$this->stream = null;
		}
		if( $this->listener != null ) {
			$this->listener->notifyError( null, $exception );
		}
	}

} // class UploadHelper


/**
 * Helper class to download objects.  For large transfers, the content
 * generally needs to be transferred from the server in smaller chunks.  This
 * helper class reads an object's contents incrementally from the server and
 * writes it to a file or stream.
 */
class DownloadHelper {
	private $esu;
	private $buffSize;
	const DEFAULT_BUFFSIZE = 4194304; // 4MB
	private $startTime;
	private $currentBytes;
	private $totalBytes;
	private $complete;
	private $failed;
	private $error;
	private $closeStream;
	private $stream;
	private $listener;
	private $checksum;
	private $checksumming;
	private $debug = false;

	/**
	 * Creates a new download helper.
	 * @param EsuApi $esuApi the API connection object to use to communicate
	 * with the server.
	 * @param integer $buffSize the amount of data to transfer with each
	 * request.  By default, this value is 4MB.
	 */
	public function __construct( $esuApi, $buffSize = DownloadHelper::DEFAULT_BUFFSIZE ) {
		$this->esu = $esuApi;
		$this->buffSize = $buffSize;
	}

	/**
	 * Downloads the given object's contents to a file.
	 * @param ObjectId $id the identifier of the object to download
	 * @param string $file the file to write the object's contents to.
	 */
	public function readObjectToFile( $id, $file ) {
		$stream = @fopen( $file, 'wb' );
		if( $stream === false ) {
			throw new EsuException( "Could not open file \"$file\"" );
		}
		try {
			$this->readObjectToStream( $id, $stream, true );
			} catch( EsuException $e ) {
				@fclose( $stream );
				if ( ( filesize( $file ) ) <= 0 ) {
					@unlink( $file );
				}
				throw $e;
			}
	}

	/**
	 * Downloads the given object's contents to a stream.
	 * @param ObjectId $id the identifier of the object to download.
	 * @param resource $stream the stream to write the object's contents to.
	 * @param boolean closeStream specifies whether to close the stream after
	 * the transfer is complete.  Defaults to true.
	 */
	public function readObjectToStream( $id, $stream, $closeStream = true ) {
		$this->startTime = time();
		$this->currentBytes = 0;
		$this->complete = false;
		$this->failed = false;
		$this->error = null;
		$this->closeStream = $closeStream;
		$this->stream = $stream;

		if( $this->checksumming ) {
			$this->checksum = new Checksum( 'SHA0' );
		}

		// Get the file size.  Set to -1 if unknown.
		try {
			$sMeta = $this->esu->getSystemMetadata( $id );
			if( $sMeta->getMetadata( 'size' ) != null ) {
				$size = $sMeta->getMetadata( 'size' )->getValue();
				if( strlen( $size ) > 0 ) {
					$this->totalBytes = $size;
				} else {
					$this->totalBytes = -1;
				}
			} else {
				$this->totalBytes = -1;
			}
		} catch( EsuException $e ) {
			$this->notifyError( $e );
			throw $e;
		}

		// We need to know how big the object is to download it.  Fail the
		// transfer if we can't determine the object size.
		if( $this->totalBytes == -1 ) {
			$e = new EsuException( 'Failed to get object size' );
			$this->notifyError( $e );
			throw $e;
		}

		// Loop, downloading chunks until the transfer is complete.
		$this->notifyProgress( 0 );
		while( true ) {
			try {
				$extent = null;

				// Determine how much data to download.  If we're at the last
				// request in the transfer, only request as many bytes as needed
				// to get to the end of the file.  Use bcmath since these values
				// can easily exceed 2GB.
				if ($size > 0) {
					if( bccomp( bcadd( $this->currentBytes, $this->buffSize ), $this->totalBytes ) > 0 ) {
						// Would go past end of file.  Request less bytes.
						$extent = new Extent( $this->currentBytes, bcsub( $this->totalBytes, $this->currentBytes ) );
					} else {
						$extent = new Extent( $this->currentBytes, $this->buffSize );
					}
				}
				// Read data from the server
				$data = $this->esu->readObject( $id, $extent, $this->checksum );

				// Write to the stream
				fwrite( $this->stream, $data );

				// Update progress
				$this->notifyProgress( strlen( $data ) );

				// See if we're done
				if( $this->currentBytes == $this->totalBytes ) {
					if( $this->checksumming ) {
						if( $this->checksum->getExpectedValue() === null ) {
							$this->notifyError( new EsuException( "Missing checksum (should be \"$this->checksum\")") );
						} elseif( $this->checksum->getExpectedValue() != ''.$this->checksum ) {
							$this->notifyError( new EsuException( 'Checksum failed (expected "' . $this->checksum->getExpectedValue() . "\" but got \"$this->checksum\")" ) );
						}
						else {
							$results = array ( 'checksumPassed' => true );
							$this->notifyDone( $results ); // Successful download, passed checksum
						}
						return;
					}

				// Successful download
				$this->notifyDone();
				return;
				}
			} catch( EsuException $e ) {
				$this->notifyError( $e );
				throw $e;
			}
		}
	}

	/**
	 * Gets the current number of bytes that have been downloaded.  Note that
	 * the value returned is a string and not an integer since it may be
	 * >2GB.  You can use the bcmath package to manipulate these values.
	 * @return string the current number of bytes downloaded.
	 */
	public function getCurrentBytes() {
		return $this->currentBytes;
	}

	/**
	 * Gets the total number of bytes to download.  Note that
	 * the value returned is a string and not an integer since it may be
	 * >2GB.  You can use the bcmath package to manipulate these values.
	 * @return string the total number of bytes to download.
	 */
	public function getTotalBytes() {
		return $this->totalBytes;
	}

	/**
	 * Returns true if the transfer has completed.
	 * @return boolean true if the transfer has completed, false otherwise.
	 */
	public function isComplete() {
		return $this->complete;
	}

	/**
	 * Returns true if the transfer has failed.
	 * @return boolean true if the transfer has failed, false otherwise.
	 */
	public function isFailed() {
		return $this->failed;
	}

	/**
	 * If the transfer has failed, return the error that caused the failure.
	 * @return Exception the error that caused the transfer to fail.
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Sets a listener to provide feedback on the transfer's progress.
	 * @param ProgressListener $listener the listener to use for feedback.  Set
	 * to null to disable progress notifications.
	 */
	public function setListener( $listener ) {
		$this->listener = $listener;
	}

	/**
	 * Sets checksum support for this download helper
	 * @param boolean $check if true, checksumming will be enabled.
	 */
	public function setChecksumming( $check ) {
		$this->checksumming = $check;
	}

	/**
	 * returns true if this download helper is checksumming
	 */
	public function isChecksumming() {
		return $this->checksumming;
	}

	/**
	 * Turns debug messages on and off.
	 */
	public function setDebug( $state ) {
		$this->debug = $state;
	}

	/////////////////////
	// Private methods //
	/////////////////////

	/**
	 * Used to output debug messages.
	 */
	private function trace( $str ) {
		if( $this->debug ) {
			echo $str;
			echo "\n";
		}
	}

	/**
	 * Updates progress on the current transfer and notifies the listener if
	 * required.
	 * @param integer $bytes     the number of bytes transferred.
	 */
	private function notifyProgress( $bytes ) {
		$this->currentBytes = bcadd( $this->currentBytes, $bytes );
		if( $this->listener != null ) {
			$progress = array(
				'currentBytes' => $this->currentBytes,
				'totalBytes' => $this->totalBytes,
				'startTime' => $this->startTime,
			);
			$this->listener->notifyProgress( $progress );
		}
	}

	/**
	 * Marks the current transfer as complete, closes the stream if required,
	 * and notifies the listener.
	 * @param int $flags         the completion flags.
	 */
	private function notifyDone( array &$results = null ) {
		$this->complete = true;

		if( $this->closeStream ) {
			fclose( $this->stream );
			$this->stream = null;
		}

		if( $this->listener != null ) {
			$this->listener->notifyDone( null, $results );
		}
	}

	/**
	 * Fails the current transfer.  Sets the failed flag and notifies the
	 * listener if required.
	 * @param Exception $exception      the failure information.
	 */
	private function notifyError( $exception ) {
		$this->failed = true;
		$this->error = $exception;

		if( $this->closeStream ) {
			fclose( $this->stream );
			$this->stream = null;
		}
		if( $this->listener != null ) {
			$this->listener->notifyError( null, $exception );
		}

	}

} // class DownloadHelper

?>
