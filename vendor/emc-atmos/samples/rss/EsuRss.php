<?php
// Copyright © 2008, EMC Corporation.
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
 * Takes the contents of a listable tag and converts it to an RSS channel.  If
 * the REF_Thumbnail metadata is present, that will be used as the ID of the
 * thumbnail for the item.  The channel name will be assumed to be the last
 * path element in the listable tag.
 * 
 * One parameter is required: "tag".  The value of this tag should be equal
 * to the listable tag whose contents are served as the channel's items
 */
require_once "RssSettings.php";
require_once "EsuRestApi.php";
require_once "MimeType.php";

echo('<?xml version="1.0" ?'.'>');

$esu = new EsuRestApi( $host, $port, $uid, $secret );

$tag = $_GET["tag"];

if( $tag == null || strlen( $tag ) < 1 ) {
	echo "The parameter 'tag' is required.";
	return;
}
 
// Reconstruct the URL to this dir
$link = strlen($_SERVER["HTTPS"])<1?"http://":"https://";
$link .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
$link .= substr( $_SERVER["REQUEST_URI"], 0, strpos( $_SERVER["REQUEST_URI"], "EsuRss.php" ) ); 

// Get collection name
$parts = explode( '/', $tag );

$channelName = $parts[ count($parts)-1 ];

$objects = $esu->listObjects( $tag );
 
?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
  <title><?=$channelName?></title>
  <link><?=$link?></link>
  <description>Contents of ESU listable tag <?=$tag?></description>
<?php
// Iterate through the object list and create RSS items

foreach( $objects as $id ) {
	// Get user metadata
	$mlist = $esu->getUserMetadata( $id );
	$title = $sid;
	if( $mlist->getMetadata( "Filename" ) != null ) {
		$title = $mlist->getMetadata( "Filename" )->getValue();
	}
	
	// Compute enclosure url
	$enclosure = $link . "MediaStreamer.php?objectId=" . $id;
	
	
	// Get the size (required for enclosure)
	$smeta = $esu->getSystemMetadata( $id );
	$size = $smeta->getMetadata( "size" )->getValue();
	
	// Determine a mime type
	$mimeType = guessMime( $mlist );
	
	print "<item>";
	print "<title>$title</title>\n";
	print "<enclosure url=\"" . $enclosure . "\" length=\"" . $size . "\" " .
			" type=\"". $mimeType . "\"/>\n";
	
	if( $mlist->getMetadata( "REF_Thumbnail" ) != null ) {
		print "<media:thumbnail url=\"" . $link . "MediaStreamer.php?objectId="
			. $mlist->getMetadata( "REF_Thumbnail" )->getValue() . "\""
			. " width=\"120\" height=\"90\" />\n";
	}
	
	print "</item>\n\n";
	
}



?>
</rss>