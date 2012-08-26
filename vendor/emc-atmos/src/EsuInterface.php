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
 * Defines the API interface for the REST API wrappers.
 */
interface EsuApi {
	/**
	 * Creates a new object in the cloud.
	 * @param Acl $acl Access control list for the new object. Optional, default
	 * is NULL.
	 * @param MetadataList $metadata Metadata list for the new object.  Optional,
	 * default is NULL.
	 * @param string $data The initial contents of the object.  May be appended
	 * to later. Optional, default is NULL (no content).
	 * @param string $mimeType the MIME type of the content.  Optional, 
	 * may be null.  If $data is non-null and $mimeType is null, the MIME
	 * type will default to application/octet-stream.
	 * @param Checksum $checksum if not null, use the Checksum object to compute
	 * the checksum for the create object request.  If appending
	 * to the object with subsequent requests, use the same
	 * checksum object for each request.
	 * @return ObjectId Identifier of the newly created object.
	 * @throws EsuException if the request fails.
	 */
	public function createObject( $acl = null, $metadata = null, $data = null, 
		$mimeType = null, $checksum = null );
		
	/**
	 * Creates a new object in the cloud.
	 * @param Acl $acl Access control list for the new object. Optional, default
	 * is NULL.
	 * @param MetadataList $metadata Metadata list for the new object.  Optional,
	 * default is NULL.
	 * @param string $data The initial contents of the object.  May be appended
	 * to later. Optional, default is NULL (no content).
	 * @param string $mimeType the MIME type of the content.  Optional, 
	 * may be null.  If $data is non-null and $mimeType is null, the MIME
	 * type will default to application/octet-stream.
	 * @param Checksum $checksum if not null, use the Checksum object to compute
     * the checksum for the create object request.  If appending
     * to the object with subsequent requests, use the same
     * checksum object for each request.
	 * @return ObjectId The ObjectId of the newly created object
	 * @throws EsuException if the request fails.
	 */
	public function createObjectOnPath( $path, $acl = null, $metadata = null, 
		$data = null, $mimeType = null, $checksum = null );
	
	/**
	 * Reads an object's content.
	 * @param ObjectId $id the identifier of the object whose content to read.
	 * @param Extent $extent the portion of the object data to read.  Optional.
	 * Default is null to read the entire object.
	 * @param Checksum $checksum if not null, the given checksum object will be used
	 * to verify checksums during the read operation.  Note that only erasure coded objects
	 * will return checksums *and* if you're reading the object in chunks, you'll have to
	 * read the data back sequentially to keep the checksum consistent.  If the read operation
	 * does not return a checksum from the server, the checksum operation will be skipped.
	 * @return string the object data read.
	 */
	public function readObject( $id, $extent = null, $checksum = null,
			$systemTags = null, $userTags = null, $limit = null, &$token = null );

	/**
	 * Updates an object in the cloud.
	 * @param Identifier $id The ID of the object to update
	 * @param Acl $acl Access control list for the new object. Optional, if
	 * null, the ACL will not be modified.
	 * @param MetadataList $metadata Metadata list for the new object.  
	 * Optional.  If null, no changes will be made to the object's metadata.
	 * @param Extent $extent the portion of the object data to write. Optional.
	 * @param string $data The initial contents of the object.  May be appended
	 * to later. Optional, default is NULL (no content changes).
	 * @param string $mimeType the MIME type of the content.  Optional, 
	 * may be null.  If $data is non-null and $mimeType is null, the MIME
	 * type will default to application/octet-stream.
	 * @param Checksum $checksum if not null, use the Checksum object to compute
	 * the checksum for the update object request.  If appending
	 * to the object with subsequent requests, use the same
	 * checksum object for each request.
	 * @throws EsuException if the request fails.
	 */
	public function updateObject( $id, $acl = null, $metadata = null, 
		$extent = null, $data = null, $mimeType = null, $checksum = null );
		
	/**
	 * Deletes an object from the cloud.
	 * @param ObjectId $id the identifier of the object to delete.
	 */
	public function deleteObject( $id );
	
	/**
	 * Renames a file or directory within the namespace.
	 * @param ObjectPath $source The file or directory to rename
	 * @param ObjectPath $destination The new path for the file or directory
	 * @param ObjectPath $force If true, the desination file or
	 * directory will be overwritten.  Directories must be empty to be
	 * overwritten.  Also note that overwrite operations on files are
	 * not synchronous; a delay may be required before the object is
	 * available at its destination.
	 */
	public function rename( $source, $destination, $force );

	/**
	 * Lists the contents of a directory.
	 * @param Identifier $id the identifier of the directory object to list.
	 * @return array the directory entries in the directory.
	 */
	public function listDirectory( $id, $systemTags = null, $userTags = null, $limit = null, &$token = null );

	/**
	 * Returns all of an object's metadata and its ACL in
	 * one call.
	 * @param $id the object's identifier.
	 * @return ObjectMetadata the object's metadata
	 */
	public function getAllMetadata( $id );

	/**
	 * Fetches the system metadata for the object.
	 * @param ObjectId $id the identifier of the object whose system metadata
	 * to fetch.
	 * @param MetadataTags $tags A list of system metadata tags to fetch.  Optional.
	 * Default value is null to fetch all system metadata.
	 * @return MetadataList The list of system metadata for the object.
	 */
	public function getSystemMetadata( $id, $tags = null );

	/**
	 * Fetches the user metadata for the object.
	 * @param ObjectId $id the identifier of the object whose user metadata
	 * to fetch.
	 * @param MetadataTags $tags A list of user metadata tags to fetch.  Optional.
	 * Default value is null to fetch all user metadata.
	 * @return MetadataList The list of user metadata for the object.
	 */
	public function getUserMetadata( $id, $tags = null );
	
	/**
	 * Writes the metadata into the object. If the tag does not exist, it is
	 * created and set to the corresponding value. If the tag exists, the
	 * existing value is replaced.
	 * @param ObjectId $id the identifier of the object to update
	 * @param MetadataList $metadata metadata to write to the object.
	 */
	public function setUserMetadata( $id, $metadata );

	/**
	 * Deletes metadata items from an object.
	 * @param ObjectId $id the identifier of the object whose metadata to
	 * delete.
	 * @param MetadataTags $tags the list of metadata tags to delete.
	 */
	public function deleteUserMetadata( $id, $tags );

	/**
	 * Returns the list of user metadata tags assigned to the object.
	 * @param ObjectId $id the object whose metadata tags to list
	 * @return MetadataTags the list of user metadata tags assigned to the object
	 */
	public function listUserMetadataTags( $id );

	/**
	 * Lists all objects with the given tag.
	 * @param MetadataTag|string $queryTag  the tag to search for.
	 * @return array The list of objects with the given tag.  If no objects
	 * are found the array will be empty.
	 * @throws EsuException if no objects are found (code 1003)
	 */
	public function listObjects( $queryTag, $limit = null, &$token = null );

	/**
	 * Lists all objects with the given tag including their metadata
	 * @param MetadataTag|string $queryTag  the tag to search for.
	 * @param string $systemTags            the system metadata to return.
	 * @param string $userTags              the user metadata to return.
	 * @return array The list of ObjectResult with the given tag.  If no objects
	 * are found the array will be empty.
	 * @throws EsuException if no objects are found (code 1003)
	 */
	public function listObjectsWithMetadata( $queryTag, $systemTags = null, $userTags = null, $limit = null, &$token = null );

	/**
	 * Returns a list of all tags that are listable for the tenant to which
	 * the current user belongs
 	 * @param $tag MetadataTag|string optional.  If specified, the list will
	 * be limited to the tags under the specified tag.
	 * @return MetadataTags the list of listable tags.
	 */
	public function getListableTags( $tag = null );

	/**
	 * Executes a query for objects matching the specified XQuery string.
	 * @param string $xquery the XQuery string to execute against the cloud.
	 * @return array the list of objects matching the query.  If no objects
	 * are found, the array will be empty.
	 */
	public function queryObjects( $xquery );

	/**
	 * Returns an object's ACL
	 * @param ObjectId $id the identifier of the object whose ACL to read
	 * @return Acl the object's ACL
	 */
	public function getAcl( $id );
	
	/**
	 * Sets (overwrites) the ACL on the object.
	 * @param ObjectId $id the identifier of the object to change the ACL on.
	 * @param Acl $acl the new ACL for the object.
	 */
	public function setAcl( $id, $acl );
	
	/**
	 * An Atmos user (UID) can construct a pre-authenticated URL to an
	 * object, which may then be used by anyone to retrieve the
	 * object (e.g., through a browser). This allows an Atmos user
	 * to let a non-Atmos user download a specific object. The
	 * entire object/file is read.
	 * @param Identifier $id the object to generate the URL for
	 * @param int $expiration the expiration date of the URL (in unix time)
	 * @return string a URL that can be used to share the object's content
	 */
	public function getShareableUrl( $id, $expiration );

	/**
	 * Lists the versions of an object.
	 * @param ObjectId $id the object whose versions to list.
	 * @return array The list of versions of the object.  If the object does
	 * not have any versions, the array will be empty.
	 */
	public function listVersions( $id );
	
	/**
	 * Creates a new immutable version of an object.
	 * @param ObjectId $id the object to version
	 * @return ObjectId the id of the newly created version
	 */
	public function versionObject( $id );
	
	/**
	 * Restores content from a version to the base object (i.e. "promote" an 
	 * old version to the current version)
	 * @param ObjectId $id Base object ID (target of the restore)
	 * @param ObjectId $vId Version object ID to restore
	 */
	public function restoreVersion( $id, $vId );
	
	/**
	 * Gets information about an object including its replicas,
	 * retention, and expiration information.
	 * @param Identifier $id The object to get information about.
	 * @return ObjectInfo information about the object
	 */
	public function getObjectInformation( $id );

	/**
	 * Gets information about the web service.  Currently, this only includes
	 * the version of Atmos.
	 * @return ServiceInformation the service information object.
	 */
	public function getServiceInformation();

	/**
	 * Returns the Atmos protocol information
	 * @param $protocolInfo
	 */
	public function getProtocolInformation();

} // interface EsuApi

?>
