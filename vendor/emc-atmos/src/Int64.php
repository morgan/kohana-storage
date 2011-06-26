<?php
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