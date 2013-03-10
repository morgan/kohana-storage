<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Storage Library Configuration
 * 
 * @package		Storage
 * @category	Base
 * @author		Micheal Morgan <micheal@morgan.ly>
 * @copyright	(c) 2011-2012 Micheal Morgan
 * @license		MIT
 */

	/**
	 * Amazon S3
	 * 
	 * @see		classes/storage/s3.php
	 * @link	https://console.aws.amazon.com/s3/
	 * @link	https://aws-portal.amazon.com/gp/aws/developer/account/index.html?action=access-key
	 */

	define('AWS_DISABLE_CONFIG_AUTO_DISCOVERY', TRUE);

	// REQUIRED - AWS Keys - Under access credentials in AWS Portal
	$config['s3']['key'] 					= NULL;
	$config['s3']['secret'] 				= NULL;
	
	// REQUIRED - Bucket to work with - this can be created under AWS Portal
	$config['s3']['bucket'] 				= NULL;
	
	// OPTIONAL - Prefix path with additional pathing - be sure to include trailing slash "/"
	// If left empty, media will be written to root.
	$config['s3']['directory']				= NULL;
	
	// OPTIONAL - Override default URL with CNAME. This must be configured prior to use of this 
	// library. As of current with S3, CNAME only works with public objects. Include trailing
	// slash without the protocol such as "example.com/"
	$config['s3']['cname']					= NULL;
	
	// OPTIONAL - Create and generate URL's as public. If set to FALSE, will preauth URL's.
	$config['s3']['public']					= FALSE;
	
	// OPTIONAL - Number of seconds file is authorized to be downloaded
	$config['s3']['preauth']				= 30;

	// OPTIONAL - Determines which Cerificate Authority file to use. A value of boolean `false` 
	// will use the Certificate Authority file available on the system. A value of boolean 
	// `true` will use the Certificate Authority provided by the SDK. Passing a file system path to 
	// a Certificate Authority file (chmodded to `0755`) will use that.
	// @link 	https://github.com/amazonwebservices/aws-sdk-for-php/blob/2a67bf8302f00141eca8b39f5f381dcfbb0d10e6/sdk.class.php#L374
	$config['s3']['certificate_authority']	= TRUE;

	// OPTIONAL - Enables the use of the older path-style URI access for all requests (e.g. the DNS vs. Path-style setting)
	// @link	https://forums.aws.amazon.com/message.jspa?messageID=308155#308155
	// @link	https://forums.aws.amazon.com/thread.jspa?threadID=69108
	$config['s3']['path_style']				= FALSE;

	/**
	 * EMC Atmos
	 * 
	 * Requires "pear/HTTP_Request2" and "pear/Net_URL2"
	 * 
	 * Ensure pear is setup and run the following via CLI:
	 * 
	 * pear install "channel://pear.php.net/Net_URL2-0.3.1"
	 * pear install "channel://pear.php.net/http_request2-0.5.2"
	 * 
	 * The bundled SDK uses deprecated functions. It is recommended to disable these notices in 
	 * your bootstrap under error_reporting using "E_ALL & ~E_DEPRECATED"
	 * 
	 * @see		classes/storage/atmos.php
	 * @link	http://www.emc.com/storage/atmos/atmos.htm
	 * @link	http://peer1.com/
	 */
	
	// REQUIRED - Atmos Credentials
	$config['atmos']['host']			= NULL;
	$config['atmos']['uid']				= NULL;
	$config['atmos']['subtenant_id']	= NULL;
	$config['atmos']['secret']			= NULL;		
	
	// OPTIONAL - Additional connection settings
	$config['atmos']['port']			= 443;	
	
	// OPTIONAL - Prefix path with additional pathing - be sure to include trailing slash "/"
	// If left empty, media will be written to root.
	$config['atmos']['directory']		= NULL;
	
	
	/**
	 * Rackspace Cloud Files
	 * 
	 * @see		classes/storage/cf.php
	 * @link	https://manage.rackspacecloud.com/
	 */
	
	// REQUIRED - Rackspace Credentials
	$config['cf']['username']	= NULL;
	$config['cf']['api_key']	= NULL;
	
	// REQUIRED - Container to work within - can be created under Rackspace manage - see link above
	$config['cf']['container'] 	= NULL;	
	
	// OPTIONAL - If the specified container does not exist, it will be created with the following
	// visibility.
	$config['cf']['public']		= FALSE;		
	
	// OPTIONAL - Prefix path with additional pathing - be sure to include trailing slash "/"
	// If left empty, media will be written to root.
	$config['cf']['directory']	= NULL;
	
	/**
	 * FTP
	 * 
	 * @see		classes/storage/ftp.php
	 * @link	http://us.php.net/manual/en/ftp.installation.php
	 */
	
	// REQUIRED - FTP Credentials
	$config['ftp']['host']		= NULL;
	$config['ftp']['username']	= NULL;
	$config['ftp']['password']	= NULL;
	
	// RECOMMENDED - Public URL. When not defined, Storage_Ftp::url returns FALSE
	$config['ftp']['url']		= NULL;
	
	// OPTIONAL - Prefix path with additional pathing - be sure to include trailing slash "/"
	// If left empty, media will be written to root.
	$config['ftp']['directory']	= NULL;	
	
	// OPTIONAL - Additional connection settings
	$config['ftp']['port']		= 21;
	$config['ftp']['timeout']	= 90;
	
	// OPTIONAL - Boolean, whether or not to make a passive connection
	$config['ftp']['passive']	= FALSE;
	
	// OPTIONAL - Boolean, whether or not to use SSL connection
	$config['ftp']['ssl']		= FALSE;
	
	// OPTIONAL - The transfer mode: `FTP_BINARY` or `FTP_ASCII`
	$config['ftp']['transfer']	= FTP_BINARY;

	/**
	 * Local file system
	 * 
	 * @see		classes/storage/local.php
	 * @link	http://us.php.net/manual/en/book.filesystem.php
	 */
	
	// REQUIRED - Root path to work within
	$config['local']['root_path']	= NULL;
	
	// RECOMMENDED - Public URL. When not defined, Storage_Native::url returns FALSE
	$config['local']['url']			= NULL;
	
	// OPTIONAL - Prefix path with additional pathing - be sure to include trailing slash "/"
	// If left empty, media will be written to root.
	$config['local']['directory']	= NULL;
	
	/**
	 * Unit Testing
	 * 
	 * @see		tests/kohana/StorageTest.php
	 */
	
	// Whether or not to run storage tests.
	$config['unittest']['enabled']	= TRUE;
	
	// Directory path to file samples. Not required but useful for testing large files. Simply
	// specify path to a directory of sample files and each one will be tested across all enabled
	// drivers. Disable by setting FALSE. Goal is to test 2 GB files on each driver before 
	// releasing future versions.
	$config['unittest']['samples']	= TRUE;
	
return $config;
