<?php

require_once 'SHA0.php';
require_once 'SHA0r2.php';

function strhex( $str ) {
	$out = "";
	for($i=0; $i<strlen($str); $i++) {
		$out .= dechex(ord($str[$i]));
	}
	return $out;
}

function arrhex( $arr ) {
	$out = "";
	for( $i=0; $i<count($arr); $i++ ) {
		$out .= dechex($arr[$i]);
	}
	return $out;
}

$str = file_get_contents( "EsuObjects.php" );

$sha = new SHA0();
//$sha->hash_final( $str );
//print "My impl: " . strhex( $sha->get_value() ) . "\n";

$sha->hash_update("hello" );
$sha_partial = clone $sha;
$sha_partial->hash_final( "" );
print "Partial after 'Hello': " . strhex( $sha_partial->get_value() ) . "\n";

$sha->hash_final( " world" );

print 'Final after world: ' . strhex( $sha->get_value()  ) . "\n";

//$sha = new SHA();
//print "other impl: " . arrhex($sha->hash_string( $str )) . "\n";
?>