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

require_once 'SHA0.php';
 
/**
 * Base ESU exception class that is thrown from the API methods.
 */
class EsuException extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0) {
        // make sure everything is assigned properly
        parent::__construct($message, $code);
    }	
	
}

/**
 * Base class for ESU identifiers: ObjectPath and ObjectId
 */
class Identifier {
}

/**
 * Encapsulates a ESU object identifier.  Performs validation upon construction
 * to ensure that the identifier format is valid.  
 */
class ObjectId extends Identifier {
	/**
	 * Regular expression used to validate identifiers.
	 */
	private static $ID_FORMAT = '/^[0-9a-f]{44}$/';
	
	/**
	 * Stores the string representation of the identifier
	 * @var string
	 */
	private $id = '';
	
	/**
	 * Constructs a new object identifier
	 * @param string $id
	 */
	public function __construct( $id ) {
		// Must be a string
		if( !is_string( $id ) ) {
			throw new EsuException( 'Identifier must be a string' );
		}
		
		// Validate that the ID is correct
		if( ! preg_match( ObjectId::$ID_FORMAT, $id ) ) {
			throw new EsuException( "ObjectId: $id is not in the correct format" );
		}
		
		$this->id = $id;
	}
	
	/**
	 * Returns the identifier as a string
	 * @return string the identifier as a string
	 * 
	 */
	public function __toString() {
		return $this->id;
	}
}


/**
 * Encapsulates a ESU object identified by path.  Performs validation 
 * upon construction to ensure that the path format is valid.  
 */
class ObjectPath extends Identifier {
	/**
	 * Regular expression used to validate identifiers.
	 */
	private static $DIR_TEST = '/\/$/';
	/**
	 * Stores the string representation of the identifier
	 * @var string
	 */
	private $path = '';
	
	/**
	 * Constructs a new object identifier
	 * @param string $id
	 */
	public function __construct( $path ) {
		// Must be a string
		if( !is_string( $path ) ) {
			throw new EsuException( 'Path must be a string' );
		}
		
		$this->path = $path;
	}
	
	/**
	 * Returns the identifier as a string
	 * @return string the identifier as a string
	 * 
	 */
	public function __toString() {
		return $this->path;
	}
	
	public function isDirectory() {
		return (bool) preg_match( ObjectPath::$DIR_TEST, $this->path );
	}
}



/**
 * Encapsulates a piece of object metadata
 */
class Metadata {
	private $name;
	private $value;
	private $listable;
	
	/**
	 * Creates a new piece of metadata
	 * @param string $name the name of the metadata (e.g. 'Title')
	 * @param string $value the metadata value (e.g. 'Hamlet')
	 * @param boolean $listable whether to make the value listable.  You can
	 * query objects with a specific listable metadata tag using the listObjects
	 * method in the API.
	 */
	public function __construct( $name, $value, $listable ) {
		$this->name = $name;
		$this->value = $value;
		$this->listable = $listable;
	}

	/**
	 * Returns a string representation of the metadata.
	 * @return string the metadata object in the format name=value.  Listable
	 * metadata will appear as name(listable)=value
	 */
	public function __toString() {
		return $this->name . ($this->listable ? '(listable)' : '') . '=' . $this->value;
	}
	
	/**
	 * Returns the name of the metadata object
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Returns the metadata object's value
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * Sets the metadata's value.  Use updateObject to change this value on
	 * the server.
	 */
	public function setValue( $value ) {
		$this->value = $value;	
	}
	
	/**
	 * Returns true if this metadata object is listable
	 */
	public function isListable() {
		return $this->listable;
	}
	
	/**
	 * Sets the value of the listable flag.
	 * @param boolean whether this metadata object is listable.
	 */
	public function setListable( $listable ) {
		$this->listable = $listable;
	}
}

/**
 * Contains a list of metadata items
 */
class MetadataList {
	private $byindex;
	private $byname;
	
	/**
	 * Creates a new metadata list
	 */
	public function __construct() {
		$this->byindex = array();
		$this->byname = array();
	}
	
	/**
	 * Adds a metadata item to the list.
	 */
	public function addMetadata( $meta ) {
		$this->byindex[] = $meta;
		$this->byname[$meta->getName()] = $meta;
	}
	
	/**
	 * Removes a metadata item from the list
	 */
	public function removeMetadata( $meta ) {
		unset( $this->byname[$meta->getName()] );
		// find by index
		$index = array_search( $meta, $this->byindex );
		unset( $this->byindex[$index] );
		// reindex
		$this->byindex = array_values( $this->byindex );
	}
	
	/**
	 * Counts the number of items in the metadata list
	 */
	public function count() {
		return count( $this->byindex );
	}
	
	/**
	 * Gets a metadata item by name or index.
	 */
	public function getMetadata( $index_or_name ) {
		if( is_numeric( $index_or_name ) ) {
			// Search by index
			if ( isset( $this->byindex[$index_or_name] ) )
				return $this->byindex[$index_or_name];
		} else {
			// Search by name
			if ( isset( $this->byname[$index_or_name] ) )
				return $this->byname[$index_or_name];
		}
	return null;
	}
}

/**
 * An extent specifies a portion of an object to read or write.  It contains
 * a starting offset and a number of bytes to read or write.  Due to the fact
 * that these values can easily exceed 2GB, they are stored as strings and
 * can be manipulated using the bcmath package.
 */
class Extent {
	/**
	 * A static instance representing an entire object's content.
	 */
	public static $ALL_CONTENT;
	
	private $offset;
	private $size;
	
	/**
	 * Creates a new extent
	 * @param string $offset the starting offset in the object in bytes, 
	 * starting with 0.  Use -1 to represent the entire object.
	 * @param string $size the number of bytes to transfer.  Use -1 to represent
	 * the entire object.
	 */
	public function __construct( $offset, $size ) {
		$this->offset = $offset;
		$this->size = $size;
	}
	
	/**
	 * Returns the size of the extent.
	 * @return string the extent's size
	 */
	public function getSize() {
		return $this->size;
	}
	
	/**
	 * Returns the starting offset of the extent
	 * @return string the extent's starting offset
	 */
	public function getOffset() {
		return $this->offset;
	}
	
	public function __toString() {
		return "Extent: offset: $this->offset size: $this->size\n";
	}
}
// Initialize the ALL_CONTENT instance
Extent::$ALL_CONTENT = new Extent( -1, -1 );

/**
 * A grantee represents a user or group that recieves a permission grant.
 */
class Grantee {
	const USER = 'USER';
	const GROUP = 'GROUP';
	
	/**
	 * Static instance that represents the special group 'other'
	 */
	public static $OTHER;
	
	private $name;
	private $type;
	
	/**
	 * Creates a new grantee.
	 * @param string $name the name of the user or group
	 * @param string $type the type of grantee, e.g. USER or GROUP.  Use the static
	 * contstants in the class to specify the type of grantee
	 */
	public function __construct( $name, $type ) {
		$this->name = $name;
		$this->type = $type;
		
		// If they pass in the subtennant ID, strip it off
		// so we just have the UID.
		if( strpos( $name, '/' ) !== false ) {
			$this->name = substr( $name, strpos( $name, '/' ) +1 );
		}
	}
	
	/**
	 * Gets the grantee's name
	 * @return string the name of the grantee
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Gets the grantee's type.  You can compare this value to the class
	 * constants.
	 * @return string the type of grantee.
	 */
	public function getType() {
		return $this->type;
	}
}
// Initialize the static instance for the 'other' group.
Grantee::$OTHER = new Grantee( 'other', Grantee::GROUP );

class Permission {
	const FULL_CONTROL = 'FULL_CONTROL';
	const WRITE = 'WRITE';
	const READ = 'READ';
	const NONE = 'NONE';
}

/**
 * Used to grant a permission to a grantee (a user or group)
 */
class Grant {
	private $grantee;
	private $permission;
	
	/**
	 * Creates a new grant
	 * @param Grantee $grantee the recipient of the permission
	 * @param string $permission the rights to grant to the grantee.  Use
	 * the constants in the Permission class.
	 */
	public function __construct( $grantee, $permission ) { 
		$this->grantee = $grantee;
		$this->permission = $permission;
	}
	
	/**
	 * Gets the recipient of the grant
	 * @return Grantee the grantee
	 */
	public function getGrantee() {
		return $this->grantee;
	}
	
	/**
	 * Gets the rights assigned the grantee
	 * @return string the permissions assigned
	 */
	public function getPermission() {
		return $this->permission;
	}
	
	/**
	 * Sets the rights assigned the grantee
	 * @param string $permission the rights for the grantee.  Use the constants
	 * in the Permission class.
	 */
	public function setPermission( $permission ) {
		$this->permission = $permission;
	}
	
	/**
	 * Returns the grant in string form: grantee=permission
	 */
	public function __toString() {
		return $this->grantee->getName() . '=' . $this->permission;
	}
}

/**
 * An Access Control List (ACL) is a collection of Grants that assign privileges
 * to users and/or groups.
 */
class Acl {
	private $grants;
	
	/**
	 * Creates a new access control list
	 */
	public function __construct() {
		$this->grants = array();
	}
	
	/**
	 * Adds a grant to the access control list
	 * @param Grant $grant the grant to add.
	 */
	public function addGrant( $grant ) {
		$this->grants[] = $grant;
	}
	
	/**
	 * Removes a grant from the access control list.
	 * @param Grant $grant the grant to remove
	 */
	public function removeGrant( $grant ) {
		$index = array_search( $grant, $this->grants );
		unset( $this->grants[$index] );
		// rebuild the array to remove any gaps.
		$this->grants = array_values( $this->grants );
	}
	
	/**
	 * Returns the number of grants in the access control list
	 * @return integer the number of grants in the ACL
	 */
	public function count() {
		return count( $this->grants );
	}
	
	/**
	 * Gets the grant at the specified index.
	 * @return Grant the requested grant.  If no grant exists at the specified
	 * index, null will be returned.
	 */
	public function getGrant( $index ) {
		return $this->grants[$index];
	}
	
	/**
	 * Clears all the grants in the ACL.
	 */
	public function clear() {
		$this->grants = array();
	}
}

/**
 * A metadata tag identifies a piece of metadata (but not its value)
 */
class MetadataTag {
	private $name;
	private $listable;
	
	/**
	 * Creates a new tag
	 * @param string $name the name of the tag
	 * @param boolean $listable whether the tag is listable
	 */
	public function __construct( $name, $listable ) {
		$this->name = $name;
		$this->listable = $listable;
	}
	
	/**
	 * Gets the name of the tag
	 * @return string the tag's name
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Returns whether the tag is listable
	 * @return boolean the listable flag
	 */
	public function isListable() {
		return $this->listable;
	}
	
	/**
	 * Sets whether the tag is listable
	 * @param boolean the new value for the listable flag.
	 */
	public function setListable( $listable ) {
		$this->listable = $listable;
	}
}

/**
 * The MetadataTags class contains a collection of metadata tags.
 */
class MetadataTags {
	private $byindex;
	private $byname;
	
	/**
	 * Creates a new metadata tag list
	 */
	public function __construct() {
		$this->byindex = array();
		$this->byname = array();
	}
	
	/**
	 * Adds a tag to the list.
	 */
	public function addTag( $tag ) {
		$this->byindex[] = $tag;
		$this->byname[$tag->getName()] = $tag;
	}
	
	/**
	 * Removes a tag from the list
	 */
	public function removeTag( $tag ) {
		unset( $this->byname[$tag->getName()] );
		// find by index
		$index = array_search( $tag, $this->byindex );
		unset( $this->byindex[$index] );
		// reindex
		$this->byindex = array_values( $this->byindex );
	}
	
	/**
	 * Counts the number of tags in the metadata tag list
	 */
	public function count() {
		return count( $this->byindex );
	}
	
	/**
	 * Gets a metadata tag by name or index.
	 */
	public function getTag( $index_or_name ) {
		if( is_numeric( $index_or_name ) ) {
			// Search by index
			if ( isset( $this->byindex[$index_or_name] ) )
				return $this->byindex[$index_or_name];
		} else {
			// Search by name
			if ( isset( $this->byname[$index_or_name] ) )
				return $this->byname[$index_or_name];
		}
	return null;
	}

}

/**
 * Directory entries are returned when you list the contents
 * of a directory.
 */
class DirectoryEntry {
	private $path;
	private $id;
	private $type;
	private $name;
	private $smetadata;
	private $umetadata;

	/**
	 * @return the id
	 */
	public function getId() {
		return $this->id;
	}
	/**
	 * @param id the id to set
	 */
	public function setId($id) {
		$this->id = $id;
	}
	/**
	 * @return the path
	 */
	public function getPath() {
		return $this->path;
	}
	/**
	 * @param path the path to set
	 */
	public function setPath( $path ) {
		$this->path = $path;
	}
	/**
	 * @return the type
	 */
	public function getType() {
		return $this->type;
	}
	/**
	 * @param type the type to set
	 */
	public function setType( $type ) {
		$this->type = $type;
	}
	/**
	 * @return the name
	 */
	public function getName() {
		return $this->name;
	}
	/**
	 * @param name the name to set
	 */
	public function setName( $name ) {
		$this->name = $name;
	}
	/**
	 * @return MetadataList the metadata
	 */
	public function getSystemMetadata() {
		return $this->smetadata;
	}
	/**
	 * @param MetadataList $metadata the metadata to set
	 */
	public function setSystemMetadata($metadata) {
		$this->smetadata = $metadata;
	}
	/**
	 * @return MetadataList the metadata
	 */
	public function getUserMetadata() {
		return $this->umetadata;
	}
	/**
	 * @param MetadataList $metadata the metadata to set
	 */
	public function setUserMetadata($metadata) {
		$this->umetadata = $metadata;
	}

	/**
	 * @see java.lang.Object#toString()
	 */
	public function toString() {
		return $this->path . ' - ' . $this->type . ' - ' . $this->id;
	}
}

/**
 * Used to return all of an object's metadata at once
 */
class ObjectMetadata {
	private $metadata;
	private $acl;
	
	/**
	 * @param metadata the metadata to set
	 */
	public function setMetadata($metadata) {
		$this->metadata = $metadata;
	}
	/**
	 * @return the metadata
	 */
	public function getMetadata() {
		return $this->metadata;
	}
	/**
	 * @param acl the acl to set
	 */
	public function setAcl($acl) {
		$this->acl = $acl;
	}
	/**
	 * @return the acl
	 */
	public function getAcl() {
		return $this->acl;
	}
}

/**
 * ObjectResults are returned from listObjectsWithMetadata.  They contain the
 * object's ID as well as its MetadataList.
 */
class ObjectResult {
	private $id;
	private $metadata;

	/**
	 * @return ObjectId the id
	 */
	public function getId() {
		return $this->id;
	}
	/**
	 * @param ObjectId $id the id to set
	 */
	public function setId($id) {
		$this->id = $id;
	}
	/**
	 * @return MetadataList the metadata
	 */
	public function getMetadata() {
		return $this->metadata;
	}
	/**
	 * @param MetadataList $metadata the metadata to set
	 */
	public function setMetadata($metadata) {
		$this->metadata = $metadata;
	}
	
}

/**
 * ObjectVersions are returned from listVersions.  They contain the
 * version number, version ID and version inception (create) time.
 */
class ObjectVersion {
	/**
	 * Regular expression used to validate identifiers.
	 */
	private static $ID_FORMAT = '/^[0-9a-f]{44}$/';

	private $number;
	private $id;
	private $itime;

	/**
	 * Constructs a new object version
	 */
	public function __construct( $number, $id, $itime ) {
		// Validate our parameters
		if( !is_numeric( $number ) ) {
			throw new EsuException( 'Version number must be a number' );
		}
		if( !is_string( $id ) ) {
			throw new EsuException( 'Version id must be a string' );
		}
		if( ! preg_match( ObjectVersion::$ID_FORMAT, $id ) ) {
			throw new EsuException( "ObjectId: $id is not in the correct format" );
		}
		if( !is_string( $itime ) ) {
			throw new EsuException( 'Version time must be a string' );
		}

		$this->number = $number;
		$this->id     = $id;
		$this->itime  = $itime;
	} // ObjectVersion::__construct

	/**
	 * @return the version number
	 */
	public function getNumber() {
		return $this->number;
	}
	/**
	 * @return the version id
	 */
	public function getId() {
		return $this->id;
	}
	/**
	 * @return the version inception (create) time
	 */
	public function getTime() {
		return $this->itime;
	}
}

class ServiceInformation {
	private $atmosVersion;
	
	/**
	 * Gets the version of Atmos
	 */
	public function getAtmosVersion() {
		return $this->atmosVersion;
	}
	
	/**
	 * Sets the Atmos version
	 * @param string $ver the version of Atmos.
	 */
	public function setAtmosVersion( $ver ) {
		$this->atmosVersion = $ver;
	}
}


class Checksum {
	private $digest;
	private $algorithm;
	private $offset;
	private $expected_value;
	
	public function __construct( $algorithm ) {
		// For now, we only support SHA0
		$this->digest = new SHA0();
		$this->algorithm = 'SHA0';
		$this->offset = '0';
		$this->expected_value = null;
	}
	
	public function getAlgorithmName() {
		return $this->algorithm;
	}
	
	/**
	 * Updates the hash value and current offset.  Note that the user
	 * generally doesn't call this function; it's called internally from
	 * the create/update methods.
	 * @param string $data a string of bytes to update with
	 */
	public function update( &$data ) {
		$this->digest->hash_update( $data );
		$this->offset = bcadd( $this->offset, strlen($data) );
	}
	
	/**
	 * Turns a byte array string into a hex string.
	 * @param string $str
	 */
	function strhex( $str ) {
		$out = '';
		for($i=0; $i<strlen($str); $i++) {
			$x = dechex(ord($str[$i]));
			if( strlen($x) == 1 ){
				$x = '0'.$x;
			}
			$out .= $x;
		}
		return $out;
	}
	
	
	/**
	 * Returns the checksum in a format suitable for inclusion in the
	 * x-emc-wschecksum header.
	 */
	public function __toString() {
		$out = $this->algorithm.'/'.$this->offset.'/'.$this->getHashValue();
		return $out;
	}
	
	/**
	 * Gets the current value of the hash.  Clones the existing hash and performs
	 * a final operation on it so the current partial hash is not finalized.
	 */
	private function getHashValue() {
		$tmpdigest = clone $this->digest;
		$tmpdigest->hash_final();
		return $this->strhex( $tmpdigest->getValue() );
	}
	
	/**
	 * Gets the expected value of this hash.  Call this after a read
	 * operation. Note that it is up
	 * to the application to validate this value.
	 * @return string the expected hash value in x-emc-wschecksum header format.
	 */
	public function getExpectedValue() {
		return $this->expected_value;
	}
	
	/**
	 * Sets the expected value of this hash.
	 * @param string $val
	 */
	public function setExpectedValue( $val ) {
		$this->expected_value = $val;
	}
}

/**
 * Encapsulates information about an object, including its replicas, 
 * retention, and expiration information.
 */
class ObjectInfo {
	/**
	 * Raw response from GetObjectInfo
	 * @var string
	 */
	public $rawXml;
	
	/**
	 * ObjectId of the object
	 * @var ObjectId
	 */
	public $objectId;
	
	/**
	 * Object's selection
	 * @var string
	 */
	public $selection;
	
	/**
	 * Object replicas (where the object is located in the cloud)
	 * @var Array of ObjectReplica
	 */
	public $replicas;
	
	/**
	 * Retention information for the object
	 * @var ObjectRetention
	 */
	public $retention;
	
	/**
	 * Expiration information for the object
	 * @var ObjectExpiration
	 */
	public $expiration;
	
	public function __construct() {
		$replicas = Array();
	}
}

/**
 * Encapsulates replica information about an object
 */
class ObjectReplica {
	/**
	 * Replica identifier
	 * @var string
	 */
	public $id;
	/**
	 * Replica location in the cloud
	 * @var string
	 */
	public $location;
	/**
	 * Replica type (sync or async)
	 * @var string
	 */
	public $replicaType;
	/**
	 * True if the replica is current, false
	 * if the replica needs or is being updated.
	 * @var boolean
	 */
	public $current;
	/**
	 * Storage type for the replica
	 * @var string
	 */
	public $storageType;
}

/**
 * Encapsulates retention information about an object.
 */
class ObjectRetention {
	/**
	 * True if retention is enabled.
	 * @var boolean
	 */
	public $enabled;
	/**
	 * Timestamp when the retention period ends
	 * @var String  
	 */
	public $endAt;
}

class ObjectExpiration {
	/**
	 * True if retention is enabled.
	 * @var boolean
	 */
	public $enabled;
	
	/**
	 * Timestamp when the retention period ends
	 * @var DateTime
	 */
	public $endAt;
}
?>
