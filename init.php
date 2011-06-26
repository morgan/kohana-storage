<?php defined('SYSPATH') or die('No direct script access.');
/**
 * For Storage S3 driver - can be overridden based on environment
 * 
 * @see	https://forums.aws.amazon.com/ann.jspa?annID=1005
 */
if ( ! defined('AWS_CERTIFICATE_AUTHORITY'))
{
	define('AWS_CERTIFICATE_AUTHORITY', TRUE);
}