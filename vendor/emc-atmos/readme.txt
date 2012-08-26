----------------------------
| PHP REST API for EMC ESU |
----------------------------

This API allows PHP developers to easily connect to EMC's ESU.  It handles 
all of the low-level tasks such as generating and signing
requests, connecting to the server, and parsing server responses.  It also 
includes helper classes to help automate basic tasks such as creating, updating,
and downloading object content from the cloud.

Requirements
------------
 * PHP 5+
 * HTTP_Request (available from PEAR)
 * phpunit (optional for running unit tests, available from 
            http://www.phpunit.de)
 * dom (built-in)
 * bcmath (built-in)
 
Additionally, the deprecated domxml extension must NOT be installed since it 
conflicts with the dom extension.  XAMPP ships with this extension enabled and 
it must be removed by commenting out extension=php_domxml.dll in php.ini.

Usage
-----

For basic calls to the ESU server, you should add the following line to your
PHP code:

require_once "EsuRestApi.php"

If you want to use the helper classes, you must also include them separately:

require_once "EsuHelpers.php"


In order to use the API, you need to construct an instance of the EsuRestApi
class.  This class contains the parameters used to connect to the server.

$esu = new EsuRestApi( "host", port, "uid", "shared secret" );

Where host is the hostname or IP address of a ESU node that you're authorized
to access, port is the IP port number used to connect to the server (generally
80 for HTTP), UID is the username to connect as, and the shared secret is the
shared secret key assigned to the UID you're using.  The UID and shared secret
are available from your ESU tennant administrator.  The secret key should be
a base-64 encoded string as shown in the tennant administration console, e.g
"jINDh7tV/jkry7o9D+YmauupIQk=".

After you have created your EsuRestApi object, you can use the methods on the
object to manipulate data in the cloud.  For instance, to create a new, empty
object in the cloud, you can simply call:

$id = esu->createObject();

The createObject method will return an ObjectId you can use in subsequent calls
to modify the object.

The helper classes provide some basic functionality when working with ESU like
uploading a file to the cloud.  To create a helper, simply construct the
appropriate class (UploadHelper or DownloadHelper).  The first, required 
argument is your EsuResApi object.  The second argument is optional and defines
the transfer size used for requests.  By default, your file will be uploaded
to the server in 4MB chunks.  After constructing the helper object, there are
a couple ways to upload and download objects.  You can either give the helper
a filename to transfer or a file descriptor resource.  When passing the file
descriptor, you can optionally pass an extra argument telling the helper whether
you want the descriptor closed after the transfer has completed.

$helper = new UploadHelper( $esu );
$id = $helper->createObjectFromFile( 'readme.txt' );

The helper classes also allow you to register a listener class that implements
the ProgressListener interface (from EsuHelpers.php).  If you register a 
listener, it will be notified of transfer progress, when the transfer completes,
or when an error occurs.  You can also access the same status information 
through the helper object's methods.

Note that since a file's status is directly connected to the helper class,
the helper class should not be used for more than one transfer.  Doing so can
produce undesired results.


Source Code
-----------

The source code is broken into five PHP files.  The contents of each class is
described below:

 * EsuRestApi.php - This file contains the EsuRestApi class that implements the
         core API functionality.
 * EsuObjects.php - This file contains the basic objects used in the API to
         handle IDs, metadata, tags, and ACLs.
 * EsuInterface.php - This file contains the abstract interface for the ESU
         API.  Eventually, when the SOAP API is supported**, both API objects
         will implement this interface.
 * EsuHelpers.php - This file contains the UploadHelper and DownloadHelper
         classes as well as the ProgressListener interface.
 * EsuRestApiTest.php - This file contains the phpUnit unit tests.  You can
         browse these testcases for examples on how the API can be used as well
         as to test out the functionality of your ESU system.
         
** PHP's SOAP implementation does not currently support the MTOM extension
   required by ESU's SOAP service.
   
   
Running the Tests
-----------------
To run the tests, ensure you've installed PHPUnit from PEAR (version 3
or higher).  Some newer versions of XAMPP include an old version of 
PHPUnit that is not compatible with the unit tests.  To get the latest 
stable version follow the directions on the phpunit website:
http://www.phpunit.de/manual/current/en/installation.html

(Note: you may also need to upgrade PEAR.  Type: "pear upgrade pear")

Run the tests
from the command line using the phpunit program:

phpunit EsuRestApiTest C:\esu\php\src\EsuRestApiTest.php

Substitute the path you extracted the php files to in the command line above.
Note that during the tests, a couple testcases reproduce failures so you may 
see an error on the screen.  After the test is complete, you should see a 
report:

Time: 29 seconds

OK (23 tests)


Regenerating the Documentation
------------------------------
To (re)generate the documentation, ensure you've installed phpdoc and run it
from the command line:

phpdoc -d C:\esu\php\src -t C:\esu\php\doc -dn "ESU" -dc "ESU" 
  -ti "PHP API for EMC ESU" -s on

Substitute the path to your src and doc directories on the command line.


