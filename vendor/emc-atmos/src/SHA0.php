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

require_once 'Int64.php';

/**
 * Pure PHP implementation of the SHA0 hash algorithm.
 * @author jason
 */
class SHA0 {
	private $state;
	
	const BLOCK_SIZE = 64;
	
	private $constants = array(); // round 1
            //0x5a827999,
            // round 2
            //0x6ed9eba1,
            // round 3
            //0x8f1bbcdc,
            // round 4
            //0xca62c1d6 );
    private $partial = "";
    private $counter;
    
    private $ta, $tb, $tc, $td, $te;
	
	public function __construct() {
		$this->initialize();
		$this->counter = new Int64();
	}
	
	/**
	 * Makes a copy of the object.  Useful for obtaining partial hash values
	 */
	public function __clone() {
		$this->counter = clone $this->counter;
	}
	
	// Start wth the initalization vector
	public function initialize() {
		$this->state = array();
		$this->state[0] = 0x6745 << 16 | 0x2301;
	        $this->state[1] = 0xefcd << 16 | 0xab89;
        	$this->state[2] = 0x98ba << 16 | 0xdcfe;
	        $this->state[3] = 0x1032 << 16 | 0x5476;
        	$this->state[4] = 0xc3d2 << 16 | 0xe1f0;
	        $this->ta = $this->state[0];
        	$this->tb = $this->state[1];
	        $this->tc = $this->state[2];
        	$this->td = $this->state[3];
	        $this->te = $this->state[4];

        	$this->constants[0] = 0x5a82 << 16 | 0x7999;
		$this->constants[1] = 0x6ed9 << 16 | 0xeba1;
		$this->constants[2] = 0x8f1b << 16 | 0xbcdc;
		$this->constants[3] = 0xca62 << 16 | 0xc1d6;
	}
	
	
	public function hash_update( &$data ) {
		$this->counter->add( strlen($data) <<3 );
		$alldata = $this->partial . $data;
		$this->partial = "";
		
		$blocks = str_split( $alldata, SHA0::BLOCK_SIZE );
		for( $i=0; $i<count($blocks); $i++ ) {
			if( strlen( $blocks[$i] ) < SHA0::BLOCK_SIZE ) {
				// Save partial block for next run.
				$this->partial = $blocks[$i];
				//print "block $i is partial, size " . strlen( $blocks[$i] ) . "\n";
				break;
			}
			$this->internal_hash_update( $blocks[$i] );
		}
	}
	
	/**
	 * Adds two numbers.  Keeps the ints from overflowing
	 * and becoming doubles.
	 */
	private function add( $a, $b, $c=0, $d=0 ) {
		if( $c || $d ) {
			return $this->add( $this->add( $a, $b ), $this->add( $c, $d ) );
		}
		$ma = ($a >> 16) & 0xffff;
		$la = ($a) & 0xffff;
		$mb = ($b >> 16) & 0xffff;
		$lb = ($b) & 0xffff;
		$ls = $la + $lb;

		// Carry
		if ($ls > 0xffff) {
			$ma += 1;
			$ls &= 0xffff;
		}

		// MS add
		$ms = $ma + $mb;
		$ms &= 0xffff;

		$result = ($ms << 16) | $ls;
		return $result;
	}
    
//	function circ_shl( $num, $amt ) {
//		$leftmask = 0xffff | (0xffff << 16);
//		$leftmask <<= 32 - $amt;
//		$rightmask = 0xffff | (0xffff << 16);
//		$rightmask <<= $amt;
//		$rightmask = ~$rightmask;
//
//		$remains = $num & $leftmask;
//		$remains >>= 32 - $amt;  // beware PHP preserves sign bit on right shifts
//		$remains &= $rightmask;
//
//		$res = ($num << $amt) | $remains;
//		return $res;
//	}
    
	private function expand_block( $block ) {
		$nblk = array();
		for( $i=0; $i<16; ++$i) {
			$index = $i * 4;
			$nblk[$i] = 0;
			$nblk[$i] |= (ord($block[$index]) & 0xff) << 24;
			$nblk[$i] |= (ord($block[$index+1]) & 0xff) << 16;
			$nblk[$i] |= (ord($block[$index+2]) & 0xff) << 8;
			$nblk[$i] |= (ord($block[$index+3]) & 0xff);
		}
		for( $i=16; $i<80; ++$i ) {
			$nblk[$i] = //$this->circ_shl(
				$nblk[$i-3] ^ $nblk[$i-8] ^ $nblk[$i-14] ^ $nblk[$i-16];//, 1
				//);
		}

		return $nblk;
	}

	private function internal_hash_update( $data ) {
		//print( "Hashing: $data\n" );

		// Fill buffer for transformations
		//BigEndianBuffer.BlockCopy(array, offset, buffer, 0, BlockSize);
		// copy array
		$buffer = $this->expand_block($data);
		//print_r( $buffer );

		$a = $this->state[0];
		$b = $this->state[1];
		$c = $this->state[2];
		$d = $this->state[3];
		$e = $this->state[4];

		// PHP copies the sign bit on right shifts, so we need to explicitly
		// clear the leftmost bits after each right shift...
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f1($b, $c, $d), $buffer[0]);  $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f1($a, $b, $c), $buffer[1]);  $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f1($e, $a, $b), $buffer[2]);  $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f1($d, $e, $a), $buffer[3]);  $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f1($c, $d, $e), $buffer[4]);  $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f1($b, $c, $d), $buffer[5]);  $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f1($a, $b, $c), $buffer[6]);  $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f1($e, $a, $b), $buffer[7]);  $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f1($d, $e, $a), $buffer[8]);  $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f1($c, $d, $e), $buffer[9]);  $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f1($b, $c, $d), $buffer[10]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f1($a, $b, $c), $buffer[11]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f1($e, $a, $b), $buffer[12]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f1($d, $e, $a), $buffer[13]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f1($c, $d, $e), $buffer[14]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f1($b, $c, $d), $buffer[15]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f1($a, $b, $c), $buffer[16]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f1($e, $a, $b), $buffer[17]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f1($d, $e, $a), $buffer[18]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f1($c, $d, $e), $buffer[19]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f2($b, $c, $d), $buffer[20]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f2($a, $b, $c), $buffer[21]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f2($e, $a, $b), $buffer[22]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f2($d, $e, $a), $buffer[23]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f2($c, $d, $e), $buffer[24]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f2($b, $c, $d), $buffer[25]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f2($a, $b, $c), $buffer[26]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f2($e, $a, $b), $buffer[27]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f2($d, $e, $a), $buffer[28]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f2($c, $d, $e), $buffer[29]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f2($b, $c, $d), $buffer[30]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f2($a, $b, $c), $buffer[31]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f2($e, $a, $b), $buffer[32]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f2($d, $e, $a), $buffer[33]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f2($c, $d, $e), $buffer[34]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f2($b, $c, $d), $buffer[35]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f2($a, $b, $c), $buffer[36]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f2($e, $a, $b), $buffer[37]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f2($d, $e, $a), $buffer[38]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f2($c, $d, $e), $buffer[39]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f3($b, $c, $d), $buffer[40]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f3($a, $b, $c), $buffer[41]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f3($e, $a, $b), $buffer[42]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f3($d, $e, $a), $buffer[43]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f3($c, $d, $e), $buffer[44]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f3($b, $c, $d), $buffer[45]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f3($a, $b, $c), $buffer[46]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f3($e, $a, $b), $buffer[47]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f3($d, $e, $a), $buffer[48]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f3($c, $d, $e), $buffer[49]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f3($b, $c, $d), $buffer[50]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f3($a, $b, $c), $buffer[51]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f3($e, $a, $b), $buffer[52]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f3($d, $e, $a), $buffer[53]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f3($c, $d, $e), $buffer[54]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f3($b, $c, $d), $buffer[55]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f3($a, $b, $c), $buffer[56]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f3($e, $a, $b), $buffer[57]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f3($d, $e, $a), $buffer[58]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f3($c, $d, $e), $buffer[59]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f4($b, $c, $d), $buffer[60]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f4($a, $b, $c), $buffer[61]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f4($e, $a, $b), $buffer[62]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f4($d, $e, $a), $buffer[63]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f4($c, $d, $e), $buffer[64]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f4($b, $c, $d), $buffer[65]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f4($a, $b, $c), $buffer[66]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f4($e, $a, $b), $buffer[67]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f4($d, $e, $a), $buffer[68]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f4($c, $d, $e), $buffer[69]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f4($b, $c, $d), $buffer[70]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f4($a, $b, $c), $buffer[71]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f4($e, $a, $b), $buffer[72]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f4($d, $e, $a), $buffer[73]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f4($c, $d, $e), $buffer[74]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));
		$e = $this->add($e, (($a << 5)|(($a >> 27) & 0x1F)), $this->f4($b, $c, $d), $buffer[75]); $b =(($b << 30)|(($b >> 2) & 0x3FFFFFFF));
		$d = $this->add($d, (($e << 5)|(($e >> 27) & 0x1F)), $this->f4($a, $b, $c), $buffer[76]); $a =(($a << 30)|(($a >> 2) & 0x3FFFFFFF));
		$c = $this->add($c, (($d << 5)|(($d >> 27) & 0x1F)), $this->f4($e, $a, $b), $buffer[77]); $e =(($e << 30)|(($e >> 2) & 0x3FFFFFFF));
		$b = $this->add($b, (($c << 5)|(($c >> 27) & 0x1F)), $this->f4($d, $e, $a), $buffer[78]); $d =(($d << 30)|(($d >> 2) & 0x3FFFFFFF));
		$a = $this->add($a, (($b << 5)|(($b >> 27) & 0x1F)), $this->f4($c, $d, $e), $buffer[79]); $c =(($c << 30)|(($c >> 2) & 0x3FFFFFFF));

		$this->state[0] = $this->add($a, $this->state[0]);
		$this->state[1] = $this->add($b, $this->state[1]);
		$this->state[2] = $this->add($c, $this->state[2]);
		$this->state[3] = $this->add($d, $this->state[3]);
		$this->state[4] = $this->add($e, $this->state[4]);
                
		//print "After block: ". $this->state[0] ." ". $this->state[1]." ". $this->state[2] ." ".$this->state[3]." ". $this->state[4] ."\n";
	}

	private function f1($a, $b, $c) {
		return $this->add(($c^($a&($b^$c))), 0x5A827999);
	}

	private function f2($a, $b, $c)	{
		return $this->add(($a^$b^$c), 0x6ED9EBA1);
	}

	private function f3($a, $b, $c) {
		return $this->add((($a&$b)|($c&($a|$b))), 0x8F1BBCDC);
	}

	private function f4($a, $b, $c) {
		return $this->add(($a^$b^$c), 0xCA62C1D6);
	}
	
	public function hash_final( &$data = "" ) {
		//print "hash_final: $data\n";
		// Consume up to the last block.
		$this->hash_update($data);
		
		// Pad out the final block
		$this->partial .= chr(0x80);
		$final_block = $this->partial;
		$end_offset = SHA0::BLOCK_SIZE-8;
		$final_size = strlen( $final_block );
		if( $final_size > $end_offset ) {
			if( $final_size < SHA0::BLOCK_SIZE ) {
				// Pad it out
				$final_block = str_pad( $final_block, SHA0::BLOCK_SIZE, chr(0x0) );
			}
			$this->internal_hash_update( $final_block );
			$final_block = "" . str_pad( "", SHA0::BLOCK_SIZE-8, chr(0x0) );
		}
		
		// Add some padding if needed
		while( strlen( $final_block ) < SHA0::BLOCK_SIZE ) {
			$final_block .= chr(0x0);
		}
		
		// The last 8 bytes are the count of the number of bits hashed
		// as a 64-bit big integer
		$final_count = $this->counter->getValue();
		for( $i=0; $i<8; $i++ ) {
			//print( "counter $i: $final_count[$i]\n" );
			$final_block[$i+SHA0::BLOCK_SIZE-8] = chr($final_count[$i]);
		}
		
		$this->internal_hash_update($final_block);
	}
	
	/**
	 * Gets the current value as a hex string
	 */
	public function getValue() {
		// The $state variables are 32-bit.  Break them down 
		// into bytes in a big-endian manner
		$output = array_fill( 0, 20, 0 );

		$this->big_endian_out( $this->state[0], $output, 0 );
		$this->big_endian_out( $this->state[1], $output, 4 );
		$this->big_endian_out( $this->state[2], $output, 8 );
		$this->big_endian_out( $this->state[3], $output, 12 );
		$this->big_endian_out( $this->state[4], $output, 16 );
		// print_r( $output );
		
		$strout = $this->byte_implode( $output );
		//print "Output " . strlen( $strout ) . " bytes: $strout \n";
		return $strout;
	}
	
	private function byte_implode( &$arr ) {
		$str = "";
		for( $i=0; $i<count($arr); $i++ ) {
			$str .= chr($arr[$i]);
		}
		return $str;
	}
	
	private function big_endian_out( $value, &$arr, $offset ) {
		//print "big_endian_out: " .   dechex($value)  . '\n';
		$arr[$offset+3] = $value & 0xff;
		$arr[$offset+2] = ($value>>8) & 0xff;
		$arr[$offset+1] = ($value>>16) & 0xff;
		$arr[$offset] = ($value>>24) & 0xff;
		//print_r( $arr );
	}

}

?>