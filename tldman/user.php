<?php
/*
    By Martin COLEMAN (C) 2012-2014. All rights reserved.
    Released under the 2-clause BSD license.
    See COPYING file for details.
*/
include("conf.php");
show_header();

if(!isset($_REQUEST['action']))
{
	echo "Invalid command.";
	die;
} else {
	$action=$_REQUEST['action'];
	switch($action)
	{
		case "frm_login":
			form_login();
			break;
		case "frm_register":
			form_register();
			break;
		case "login":
			if(isset($_POST['username']) && isset($_POST['password']))
			{
				$username=$_POST['username'];
				$password=$_POST['password'];
			} else {
				echo "Data error. Please retry.";
				die;
			}
			if( (strlen($username)<5) && (strlen($password)<5) )
			{
				echo "Invalid data. Please try again.";
				die;
			}
			login($username, $password);
			break;
		case "register":
			if(isset($_POST['username']) && isset($_POST['password1']) && isset($_POST['password2']) && isset($_POST['email']) && isset($_POST['name']))
			{
				$username=$_POST['username'];
				$password1=$_POST['password1'];
				$password2=$_POST['password2'];
				$email=$_POST['email'];
				$name=$_POST['name'];
			} else {
				echo "Data error. Please retry.";
				die;
			}
			if($password1 != $password2)
			{
				echo "Sorry, passwords do not match. Please try again.";
				die;
			}
			if( (strlen($name)<5) && (strlen($username)<5) && (strlen($password1)<5) && (strlen($email)<5) )
			{
				echo "Invalid data. Please try again.";
				die;
			}
			register($username, $name, $email, $password1);
			break;
		case "update_account":
			if(!isset($_POST['password']))
			{
				echo "Current password not specified.";
				die;
			}
			$password=$_POST['password'];
			$password=str_replace(" ", "", $password);
			if(strlen($password)<5)
			{
				echo "Password should be at least 5 characters long.\n"; die;
			}
			if(!isset($_POST['country']))
			{
				echo "Data error. Please retry.";
				die;
			} else {
				$country=$_POST['country'];
			}
			if(isset($_POST['password1']))
			{
				$password1=$_POST['password1'];
				$password2=$_POST['password2'];
				if($password1 != $password2)
				{
					echo "Sorry, passwords do not match. Please try again.";
					die;
				}
				$password=$password1;
			}
			if(strlen($password1)>0)
			{
				if(strlen($password1)<5)
				{
					echo "Remember, your new password needs to be at least 5 characters long.";
					die;
				}
				$password=$password1;
			}
			update_account($country, $password);
			break;
		case "view_account":
			dashboard();
			break;
		case "logout":
			session_destroy();
			header("location: index.php");
			break;
		default:
			echo "Invalid sub-command.";
			die;
	}
}
?>

       <?php
show_footer();
?>
