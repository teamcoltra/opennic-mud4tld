<?php
/*
    By Martin COLEMAN (C) 2012-2014. All rights reserved.
    Released under the 2-clause BSD license.
    See COPYING file for details.
*/
include("conf.php");
show_header();
?>
<div class="tm-section tm-section-color-white">
            <div class="uk-container uk-container-center uk-text-center">
<?php


if(isset($_REQUEST['action']))
{
	$action=$_REQUEST['action'];
	switch($action)
	{
		case "frm_check_domain":
			form_check_domain();
			break;
		case "check_domain":
			if(!isset($_POST['domain']))
			{
				echo "Error. No domain specified."; die;
			}
			$domain=$_POST['domain'];
			check_domain($domain);
			break;
		case "frm_register_domain":
			frm_register_domain();
			break;
		case "register_domain":
			if(!isset($_POST['domain']))
			{
				echo "Error. No domain specified."; die;
			}
			$domain=$_POST['domain'];
			if(!isset($_POST['ns1_ip']))
			{
				$ns1_ip="NULL";
			}
			if(!isset($_POST['ns2_ip']))
			{
				$ns2_ip="NULL";
			}
			$ns2_ip="NULL";
			register_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip);
			break;
		case "confirm_delete_domain":
			if(!isset($_POST['domain']))
			{
				echo "Error. No domain specified."; die;
			}
			$domain=$_POST['domain'];
			if(!isset($_POST['delete']))
			{
				echo "Error. Deletion validation failed."; die;
			}
			delete_domain($domain);
			break;
		case "delete_domain":
			$domain=$_POST['domain'];
			frm_delete_domain($domain);
			break;
		case "modify":
			if(!isset($_SESSION['userid']))
			{
				die("Domain modification not allowed.");
			}
			if(!isset($_REQUEST['domain']))
			{
				die("Invalid domain request");
			}
			$domain=$_REQUEST['domain'];
			frm_view_domain($domain);
			break;
		case "update":
			if(!isset($_SESSION['userid']))
			{
				die("Domain modification not allowed.");
			}
			if(!isset($_POST['domain']))
			{
				die("Invalid domain request");
			}
			$domain=$_POST['domain'];
			
			/* standard nameservers */
			if(!isset($_POST['ns1']))
			{
				die("Nameserver 1 is required.");
			}
			$ns1=$_POST['ns1'];
			if($ns1=='')
			{
				die("Nameserver 1 is required.");
			}
			if(!isset($_POST['ns2']))
			{
				die("Nameserver 2 is required.");
			}
			$ns2=$_POST['ns2'];
			if($ns2=='')
			{
				die("Nameserver 2 is required.");
			}
			if( (strlen($ns1)<7) && (strlen($ns2)<7) )
			{
				die("Nameservers need to be at least 7 characters long.");
			}
			/* deal with custom nameservers */
			if(isset($_POST['ns1_ip']))
			{
				if(strlen($_POST['ns1_ip'])>0)
				{
					$ns1_ip=$_POST['ns1_ip'];
				} else {
					$ns1_ip="NULL";
				}
			}
			if(isset($_POST['ns2_ip']))
			{
				if(strlen($_POST['ns2_ip'])>0)
				{
					$ns2_ip=$_POST['ns2_ip'];
				} else {
					$ns2_ip="NULL";
				}
			}

			update_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip);
			break;
		case "check_domain":
			$domain=$_POST['domain'];
			check_domain($domain);
			break;
		default:
			echo "Invalid command.";
			die;
	}
} else {
	echo "Unspecified error.";
}
show_footer();
?>