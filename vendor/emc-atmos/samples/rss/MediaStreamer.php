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
 * Streams ESU objects via HTTP
 * Takes one parameter: objectId, e.g.
 * http://server/rss/MediaStreamer.php?objectId=5ca1ab1e13435132abc334df389
 * Will look for a metadata tag named "MimeType" and attempt to set that as
 * the output content type.
 */
require_once "RssSettings.php";
require_once "MimeType.php";
require_once "EsuRestApi.php";

$esu = new EsuRestApi( $host, $port, $uid, $secret );

$sid = $_GET["objectId"];
$id = new ObjectId( $sid );

// Get the size
$smeta = $esu->getSystemMetadata( $id );
$size = $smeta->getMetadata( "size" )->getValue();

// Get the mime type
$mlist = $esu->getUserMetadata( $id );
$mimeType = guessMime( $mlist );

// output headers
header( "Content-Type: " . $mimeType );
header( "Content-Length: " . $size );

// Start streaming
$chunkSize = 256*1024;
$read = 0;

while( true ) {
	$extent = null;
	
	// Determine how much data to download.  If we're at the last
	// request in the transfer, only request as many bytes as needed
	// to get to the end of the file.  Use bcmath since these values
	// can easily exceed 2GB.
	if( bccomp( bcadd( $read, $chunkSize ), $size ) > 0 ) {
		// Would go past end of file.  Request less bytes.					
		$extent = new Extent( $read, bcsub( $size, $read ) );
	} else {
		$extent = new Extent( $read, $chunkSize );			
	}
	
	// Read data from the server.
	$data = $esu->readObject( $id, $extent );
	
	// Write to the stream
	print( $data );
	
	$read = bcadd( $read, strlen( $data ) );
	
	// See if we're done.
	if( bccomp( $read, $size ) >= 0 ) {
		return;
	}
}

?>
