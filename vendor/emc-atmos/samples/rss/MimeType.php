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
 * Utility method to guess an item's mime type
 */
 
function guessMime( $mlist ) {
	$mimeType = "application/octet-stream";
	if( $mlist->getMetadata( "MimeType" ) != null ) {
		$mimeType = $mlist->getMetadata( "MimeType" )->getValue();	
	} else if( $mlist->getMetadata( "Filename" ) != null ){
		// Try by filename
		$filename = $mlist->getMetadata( "Filename" )->getValue();
		$mct = mime_content_type( $filename );
		if( strlen( $mct ) > 0 ) {
			$mimeType = $mct;
		} else {
			// some guesses
			if( stristr( $filename, ".mp4" ) !== FALSE ) {
				$mimeType = "video/mp4";
			} else if( stristr( $filename, ".mov" ) !== FALSE ) {
				$mimeType = "video/quicktime";
			} else if( stristr( $filename, ".avi" ) !== FALSE ) {
				$mimeType = "video/x-msvideo";
			} else if( stristr( $filename, ".wmv" ) !== FALSE ) {
				$mimeType = "audio/x-ms-wmv";
			} else if( stristr( $filename, ".asf" ) !== FALSE ) {
				$mimeType = "video/x-ms-asf";
			} else if( stristr( $filename, ".dv" ) !== FALSE ) {
				$mimeType = "video/x-dv";
			} else if( stristr( $filename, ".jpg" ) !== FALSE ) {
				$mimeType = "image/jpeg";
			} else if( stristr( $filename, ".gif" ) !== FALSE ) {
				$mimeType = "image/gif";
			} else if( stristr( $filename, ".png" ) !== FALSE ) {
				$mimeType = "image/png";
			}
		}
	}
	
	return $mimeType;
}
?>
