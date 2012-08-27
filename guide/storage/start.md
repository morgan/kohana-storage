# Configure a Driver

Start by setting up a driver. Check out `config/storage` for configuration details.

# Specifying a Driver

A driver can be set within factory:

	$storage = Storage::factory('ftp');

By default, configuration will be loaded from `config/storage`. A second parameter can also be passed 
in overriding default configuration.

	$storage = Storage::factory('ftp', array('passive' => TRUE));

The first parameter can be ommited and the default driver loaded. The default driver is configured 
by setting `Storage::$driver`. It defaults to `local`. This can be set in the bootstrap to specify 
another default.

# Operations

## Write

There are a few options for setting files. If the file already exists, it will be overwritten.

### Raw Content

Content can simply be passed in.

	$storage = Storage::factory();
	
	$storage->set('test.txt', 'hello world');
	
### File Location
	
	$storage->set('test.txt', 'path/to/file.txt', TRUE);
	
### Open Resource
	
	// Create temporary file
	$handle = tmpfile();
		
	// Write test content
	fset($handle, 'hello world');

	// Reset handle back to the beginning
	rewind($handle);
	
	$storage->set('test.txt', $handle);	
	
## Delete

	// Returns bool indicating success
	$storage->delete('test.txt');
	
## Read

	// Specify local path, if it does not exist, will attempt to create
	$storage->get('test.txt', 'path/to/file.txt');
	
	// Pass open resource
	$storage->get('test.txt', $handler);
	
## File size

	// Always returns an integer
	$storage->size('test.txt');	
	
## Exists

	// Returns a bool
	$storage->exists('test.txt');
	
## URL

	// Outputs "http://localhost/test.txt"
	echo $storage->url('test.txt');

## Directory listing

	// Instance of `Storage_Directory`
	$directory = $storage->listing('path/to/directory');

	// Comprised of `Storage_File` and `Storage_Directory` objects 
	foreach ($directory as $listing)
	{
		if ($listing->is_file())
		{
			echo $listing->name(), $listing->modified(), $listing->mime(), $listing->size();
		}
		else if ($listing->is_directory())
		{
			// We can also iterate over this `$listing` object because it is an 
			// instance of `Storage_Directory`
			echo $listing->name();
		}
	}
