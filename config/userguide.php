<?php defined('SYSPATH') or die('No direct script access.');

return array
(
	'modules' => array
	(
		'storage' => array
		(
			'enabled'		=> TRUE,
			'name'			=> 'Storage',
			'description'	=> 'A key/value storage abstraction supporting Amazon S3, Rackspace Cloud Files, EMC Atmos, FTP and the local file system.',
			'copyright'		=> '&copy; 2011-2012 Micheal Morgan',
		)
	)
);
