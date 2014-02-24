<?php
/*
    By Martin COLEMAN (C) 2012-2014. All rights reserved.
    Released under the 2-clause BSD license.
    See COPYING file for details.
*/
include("conf.php");
show_header();

	$domain=$_POST['domain'];
	$ns1=$_POST['ns1'];
	$ns2=$_POST['ns2'];
	$ns1_ip=$_POST['ns1_ip'];
	$ns2_ip=$_POST['ns2_ip'];


	create_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip);

show_footer();
?>