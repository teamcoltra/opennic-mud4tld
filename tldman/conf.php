<?php

/*
   MUD4TLD - Martin's User and Domain system for Top Level Domains.
   (C) 2012-2014 Martin COLEMAN. All rights reserved.
   Licensed under the 2-clause license. (see COPYING for details)
   Made for the OpenNIC Project.
*/
session_start();
$TLD="oz";
$ws_title="dot OZ";
$domain_expires=1; // to allow domains to expire
$sw_version="0.77";
$dev_link=0;
$user="TEST01"; /* for registrars */
$userkey="1234567890abcdef"; /* for registrars */
$tld_svr="http://opennic.".$TLD."/rm/rm_api.cgi";
$mysql_support=0;
$mysql_server="localhost";
$mysql_username="";
$mysql_password="";
$mysql_database="";
$tld_db="../".$TLD."_tld.sq3";

include("includes.php");
include("templates.php");
?>
