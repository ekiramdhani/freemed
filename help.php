<?php
 // $Id$
 //
 // Authors:
 // 	Jeff Buchbinder <jeff@freemedsoftware.org>
 //
 // FreeMED Electronic Medical Record and Practice Management System
 // Copyright (C) 1999-2011 FreeMED Software Foundation
 //
 // This program is free software; you can redistribute it and/or modify
 // it under the terms of the GNU General Public License as published by
 // the Free Software Foundation; either version 2 of the License, or
 // (at your option) any later version.
 //
 // This program is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU General Public License for more details.
 //
 // You should have received a copy of the GNU General Public License
 // along with this program; if not, write to the Free Software
 // Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include_once ( 'lib/freemed.php' );

error_reporting ( );
set_error_handler("help_standard_error_handler");

unset ( $parts ); unset ( $file );
$parts = explode ( '/', $_SERVER['PATH_INFO'] );
$ui = $parts[1];
$locale = $parts[2];
$path = str_replace ( $parts[0].'/'.$parts[1].'/'.$parts[2], '', $_SERVER['PATH_INFO'] );
// Strip leading slash if it exists ...
if ( substr( $path, 0, 1 ) == '/' ) {
	$path = substr ( $path, - (strlen($path)-1) );
}

$fullpath = dirname(__FILE__)."/ui/${ui}/help/${locale}/${path}";

if ( file_exists( $fullpath . ".html" ) ) {
	$fullpath .= ".html";
}

if ( !file_exists( $fullpath ) ) {
	print "Help index ${path} not present.";
	exit;
}

readfile( $fullpath );

//----------------- Functions ----------------------------------------------

function help_standard_error_handler ($no, $str, $file, $line, $context) {
	switch ($no) {
		case E_USER_ERROR:
		die('
			<div style="border: 1px solid #000000; background-color: #ffff00; color: #000000; font-family: sans-serif; padding: 1em; font-size: 8pt;">'.$str.'</div>
		');
		break;
	}
}

?>
