<?php
// Copyright © 2008 EMC Corporation.
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
 * Big-Endian 64-bit integer
 * @author jason
 *
 */
class Int64 {
	private $value;
	
	public function __construct() {
		$this->value = array_fill(0, 8, 0);
	}
	
	public function add( $v ) {
		//print( "counter.add $v\n" );
		$this->add2( 7, $v );
	}
	
	private function add2( $pos, $v ) {
		$val = $v + $this->value[$pos];
		if( $val > 255 ) {
			$this->value[$pos] = $val % 256;
			$this->add2( $pos-1, $val >> 8 );
		} else {
			$this->value[$pos] = $val;
		}
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function toDecimal() {
		return $this->base_convert( "".$this, 16, 10);
	}
	
	/**
	 * Returns the current value as a hex String
	 */
	public function __toString() {
		$out = "";
		for( $i=0; $i<8; $i++ ) {
			$out .= dechex( $this->value[$i] );
		}
		
		return $out;
	}
	
	private function base_convert ($numstring, $frombase, $tobase) {
	
	   $chars = "0123456789abcdefghijklmnopqrstuvwxyz";
	   $tostring = substr($chars, 0, $tobase);
	
	   $length = strlen($numstring);
	   $result = '';
	   for ($i = 0; $i < $length; $i++) {
	       $number[$i] = strpos($chars, $numstring{$i});
	   }
	   do {
	       $divide = 0;
	       $newlen = 0;
	       for ($i = 0; $i < $length; $i++) {
	           $divide = $divide * $frombase + $number[$i];
	           if ($divide >= $tobase) {
	               $number[$newlen++] = (int)($divide / $tobase);
	               $divide = $divide % $tobase;
	           } elseif ($newlen > 0) {
	               $number[$newlen++] = 0;
	           }
	       }
	       $length = $newlen;
	       $result = $tostring{$divide} . $result;
	   }
	   while ($newlen != 0);
	   return $result;
	}
}
?>