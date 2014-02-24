<?php
function sqlite_open_now($location,$mode)
{
    global $mysql_support;
    if($mysql_support==1)
    {
		$handle=mysql_connect($mysql_server, $mysql_username, $mysql_password) or die("MySQL error");
		mysql_select_db($mysql_database, $handle);
	} else {
		$handle = new SQLite3($location);
	}
	return $handle;
}

function sqlite_query_now($dbhandle,$query)
{
    global $mysql_support;
	if($mysql_support==1)
	{
		$result = mysql_query($query, $dbhandle);
	} else {
		$array['dbhandle'] = $dbhandle;
		$array['query'] = $query;
		$result = $dbhandle->query($query);
	}
    return $result;
}

function sqlite_fetch_array_now(&$result) //,$type)
{
    #Get Columns
    $i = 0;
    while ($result->columnName($i))
    {
        $columns[ ] = $result->columnName($i);
        $i++;
    }

    $resx = $result->fetchArray(SQLITE3_ASSOC);
    return $resx;
}

function dbNumRows($qid)
{
  $numRows = 0;
  while ($rowR = sqlite_fetch_array_now($qid))
    $numRows++;
  $qid->reset ();
  return ($numRows);
}

function domain_taken($domain)
{
	global $tld_db;
	if($domain=="register" || $domain=="opennic" || $domain=="example")
	{
		return 1;
	}
	$base=sqlite_open_now($tld_db, 0666);
	$query = "SELECT domain FROM domains WHERE domain='".$domain."' LIMIT 1";
	// echo "<BR><B>DEBUG: [".$query."]</B><BR>";
	$results = sqlite_query_now($base, $query);
	if(dbNumRows($results))
	{
		return 1;
	} else {
		return 0;
	}
}

function username_taken($username)
{
	global $tld_db;
	$base=sqlite_open_now($tld_db, 0666);
	$query = "SELECT username FROM users WHERE username='".$username."' LIMIT 1";
	// echo "<BR><B>DEBUG: [".$query."]</B><BR>";
	$results = sqlite_query_now($base, $query);
	if(dbNumRows($results))
	{
		return 1;
	} else {
		return 0;
	}
}


function clean_up_input($str)
{
	$new_str=htmlspecialchars(stripslashes($str));
	$new_str=preg_replace("/[^a-zA-Z0-9\-]/","", $new_str); /* replace characters we do not want */
	$new_str=preg_replace('/^[\-]+/','',$new_str); /* remove starting hyphens */
	$new_str=preg_replace('/[\-]+$/','',$new_str); /* remove ending hyphens */
	$new_str=str_replace(" ", "", $new_str); /* remove spaces */
	$new_str=strtolower($new_str); /* all lower case to remove confusion */
	return $new_str;
}

//function to validate ip address format in php by Roshan Bhattarai(http://roshanbh.com.np)
function validateIPAddress($ip_addr)
{
	// first of all the format of the ip address is matched
	if(preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/",$ip_addr))
	{
		// now all the intger values are separated
		$parts=explode(".",$ip_addr);
		// now we need to check each part can range from 0-255
		foreach($parts as $ip_parts)
		{
			if(intval($ip_parts)>255 || intval($ip_parts)<0)
				return 0; // if number is not within range of 0-255
		}
		return 1;
	}
	else
		return 0; // if format of ip address doesn't matches
}

function unique_id($l = 8)
{
	return substr(md5(uniqid(mt_rand(), true)), 0, $l);
}

function confirm_user($username)
{
	global $tld_db;
	$query = "UPDATE users SET verified=1 WHERE username='".$username."'";
	$base=sqlite_open_now($tld_db, 0666);
	sqlite_query_now($base, $query);
}

/* domain.php functions */

function register_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip)
{
	global $TLD, $user, $userkey;

	$userid=$_SESSION['userid'];
	$username=$_SESSION['username'];
	if(strlen($userid)<1)
	{
		echo "Error validating user session.\n"; die;
	}
	if(strlen($username)<5)
	{
		echo "Error validating user name.\n"; die;
	}
	$ns1=$_POST['ns1'];
	$ns2=$_POST['ns2'];
	if( ($ns1=="enter here") || ($ns2=="enter here") )
	{
		echo "<font color='#ff0000'><b>Error</b></font> Please change the nameservers to your own.\n"; die;
	}
	if( ($ns1=='') || ($ns2==''))
	{
		echo "<font color='#ff0000'><b>Error</b></font> Please change the nameservers to your own.\n"; die;
	}
	if( ($ns1=="") || ($ns2==""))
	{
		echo "<font color='#ff0000'><b>Error</b></font> Please change the nameservers to your own.\n"; die;
	}
	if( (isset($_POST['ns1_ip'])) && (strlen($_POST['ns1_ip'])>0) )
	{
		$ns1_ip=$_POST['ns1_ip'];
		if(validateIPAddress($ns1_ip)==0)
		{
			echo "<font color='#ff0000'><b>Error</b></font> NS1 Custom Nameserver must be a valid IPv4 address"; die;
		}
	}
	if( (isset($_POST['ns2_ip'])) && (strlen($_POST['ns2_ip'])>0) )
	{
		$ns2_ip=$_POST['ns2_ip'];
		if(validateIPAddress($ns2_ip)==0)
		{
			echo "<font color='#ff0000'><b>Error</b></font> NS2 Custom Nameserver must be a valid IPv4 address"; die;
		}
	}
	if( (strlen($domain)<2) && (strlen($domain)>50) && (strlen($ns1)<5) && (strlen($ns2)<5) )
	{
		echo "<font color='#ff0000'><b>Error</b></font> Domain details must adhere to standard lengths.\n"; die;
	}
	if($ns1 == $ns2)
	{
		echo "<font color='#FF8C00'><b>Please Note:</b></font> We highly recommend that you use two different nameserver values instead of the same one.<BR>\n";
	}
	echo "Processing ".$domain.$TLD."...";
	if(domain_taken($domain))
	{
		echo "Sorry, this domain has already been submitted for processing. If you believe this to be in error or you would like to dispute the previous registration, please contact us using the domain <a href=\"abuse.php\">abuse</a> page</a>. Thank you.";
		die;
	}
	if( (strlen($ns1_ip)>7) && (strlen($ns2_ip)>7))
	{
		$URL=$tld_svr."?cmd=register&user=".$user."&userkey=".$userkey."&tld=".$tld."&domain=".$domain."&name=".$name."&email=".$email."&ns1=".$ns1."&ns2=".$ns2."&ns3=".$ns3."&ns2=".$ns2;
	} else {
		$URL=$tld_svr."?cmd=register&user=".$user."&userkey=".$userkey."&tld=".$tld."&domain=".$domain."&name=".$name."&email=".$email."&ns1=".$ns1."&ns2=".$ns2;
	}
	$handle=fopen($URL, "r");
	$ret_data=fread($handle, 1024);
	fclose($handle);

	switch($ret_data)
	{
		case "0":
			echo "<font color=\"#800000\"><b>Error</b></font><BR>An error occured during registration. Please try again.";
			break;
		case "1":
			echo "<font color=\"#008000\"><b>Complete</b></font><BR>Congratulations! Your new domain has been registered and should be live within the next 24 hours.";
			break;
		case "255":
			echo "<font color=\"#800000\"><b>Server Error</b></font><BR>A server error has occured. Please contact this site's administrators.";
			break;
	}
}


function form_check_domain()
{
	global $TLD;
?>
<p>
<form action="domain.php" method="post">
Domain name <input type="text" name="domain">.<?php echo $TLD; ?>&nbsp;<input type="submit" name="check" value="Check">
<input type="hidden" name="action" value="check_domain">
</form>
</p>
<?php
}

function check_domain($domain)
{
    global $TLD;
	/* sanity check the domain */
	$name=htmlspecialchars(stripslashes($domain));
	$name=preg_replace("/[^a-zA-Z0-9\-]/","", $name); /* replace characters we do not want */
	$name=preg_replace('/^[\-]+/','',$name); /* remove starting hyphens */
	$name=preg_replace('/[\-]+$/','',$name); /* remove ending hyphens */
	$name=str_replace(" ", "", $name); /* remove spaces */
	$name=str_replace("--", "-", $name); /* remove double hyphens */
	$name=strtolower($name); /* all lower case to remove confusion */
	if( (strlen($name)<3) || (strlen($name)>50))
	{
		echo "Sorry, domain names must contain at least 3 characters and be no longer than 50 characters.";
		echo "Please go back and try again.";
		die;
	}
	if(strlen($name)>3)
	{
		echo "Checking ".$name.".".$TLD." for you...";
		if(domain_taken($name))
		{
			echo "<font color=\"#ff0000\"><b>Taken</b></font><BR><BR>Sorry, that name is already taken.";
		} else {
			echo "<font color=\"#008000\"><b>Available!</b></font><BR><BR>Congratulations! ".$name.".".$TLD." is available.\n";
			echo "Would you like to register it now?\n<form action=\"domain.php\" method=\"post\">\n<input type=\"hidden\" name=\"domain\" value=\"".$name."\">\n<input type=\"submit\" name=\"submit\" value=\"Yes!\">\n</form>\n";

			frm_register_domain($name.$TLD);
			
		}
		echo "You can use the form below to search for another domain if you like.";
	}
	form_check_domain();
}

function frm_register_domain($domain)
{
	global $TLD, $ws_title;
?>
<table width="500" align="center">
<tr><td align="center"><h1><?php echo $ws_title; ?> Registration</h1></td></tr>
<tr><td>
<p>Please fill out the information below. Make sure the details are correct before clicking "Register Domain" as incorrect details may delay the registration process.</p>
</td></tr>
<tr><td align="center">
<p><br><font color="#008000">You are registering <b><?php echo $domain.".".$TLD; ?></b></font><BR>To register a different domain, please <a href="domain.php">check</a> it first.</p>
<?php
if(!isset($_SESSION['username']))
{
	echo "You must <a href=\"user.php?action=frm_login\">login</a> first before trying to register a domain.";
} else {
?>
<form action="process.php" method="post">
<table width="450" border=0 cellspacing=1 cellpadding=0>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td width="200" valign="top">Username</td><td><?php echo $_SESSION['username']; ?><BR><font size="-1">(not you? <a href="user.php?action=frm_login">Login</a> as the correct user)</font></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2"><b>Nameserver Settings</b></td></tr>
<tr><td>NS1 <font size="-1">eg: ns1.mywebhost.com</font></td><td><input type="text" name="ns1" value="enter here"></td></tr>
<tr><td>NS2 <font size="-1">eg: ns2.mywebhost.com</font></td><td><input type="text" name="ns2" value="enter here"></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2">Custom Nameservers <font size="-1">(Experts only, can be left blank)</font></td></tr>
<tr><td>NS1 <font size="-1">(IPv4 only)</font></td><td><input type="text" name="ns1_ip"></td></tr>
<tr><td>NS2 <font size="-1">(IPv4 only)</font></td><td><input type="text" name="ns2_ip"></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="submit" value="Register Domain"></tr></tr>
</table>
<?php
echo "<input type=\"hidden\" name=\"domain\" value=\"".$domain."\">\n";
?>
</form>
<?php
}
?>
</td></tr>
</table>
<?php
}


function delete_domain($domain)
{
	global $tld_svr, $user, $userkey, $TLD;

	show_header();
	$userid=$_SESSION['userid'];
	$URL=$tld_svr."?cmd=delete&user=".$user."&userkey=".$userkey."&tld=".$TLD."&domain=".$domain;
	$handle=fopen($URL, "r");
	$ret_data=fread($handle, 10);
	fclose($handle);
	switch($ret_data)
	{
        case "0":
            echo "<center><b>Error, domain not deleted</b>. Possibly an administrative glitch.</center>";
            break;
        case "1":
            echo "<center><b>Domain deleted</b>. Changes may take up to 24 hours to take effect.</center>";
            break;
        case "255":
            echo "Server error occured.";
            break;
        default:
            echo "An unknown problem has occured. Please try again later.";
            break;
	}
}

function frm_delete_domain($domain)
{
	global $TLD;
	
	show_header();
	?>
	<center>
	<h2>Cancel <?php echo $domain.$TLD; ?> Registration</h2>
	<form action="domain.php" method="post">
	This means you will no longer be able to manage it and that someone else may register it instead.<BR>
	Are you sure you wish to delete <b><?php echo $domain.".".$TLD;?>?</b><BR>
	<input type="checkbox" name="delete">Yes <input type="submit" value="Confirm">
	<input type="hidden" name="domain" value="<?php echo $domain; ?>">
	<input type="hidden" name="action" value="confirm_delete_domain">
	</form>
	</center>
	<?php
}

function frm_view_domain($domain)
{
	global $TLD, $tld_db;

	show_header();
	$userid=$_SESSION['userid'];
	$base=sqlite_open_now($tld_db, 0666);
	$query = "SELECT * FROM domains WHERE userid='".$userid."' AND domain='".$domain."' LIMIT 1";
	$results = sqlite_query_now($base, $query);
	$arr=sqlite_fetch_array_now($results);
	$real_userid=$arr['userid'];
	if($userid != $real_userid)
	{
		echo "<font color=\"#ff0000\"><b>Error: You do not have permission to modify this domain.</b></font>";
		die;
	}
	echo "<center><h2>".$domain.$TLD." Modification</h2>\n";
	echo "Registered: ".$arr['registered']."<BR><BR>\n";
?>
<form action="domain.php" method="post">
<table width="320" border=0 cellspacing=2 cellpadding=0>
<tr><td colspan="2"><b>Nameserver Settings</b></td></tr>
<tr><td>NS1</td><td><input type="text" name="ns1" value="<?php echo $arr['ns1']; ?>"></td></tr>
<tr><td>NS2</td><td><input type="text" name="ns2" value="<?php echo $arr['ns2']; ?>"></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2">Custom Nameserver Settings<BR><font size="-1">(Experts only)</font></td></tr>
<tr><td>NS1</td><td><input type="text" name="ns1_ip" value="<?php echo $arr['ns1_ip']; ?>"><font size="-1">IPv4 only</font></td></tr>
<tr><td>NS2</td><td><input type="text" name="ns2_ip" value="<?php echo $arr['ns2_ip']; ?>"><font size="-1">IPv4 only</font></td></tr>
<tr><td colspan="2">&nbsp;</td></tr>
<tr><td colspan="2" align="center">
<input type="hidden" name="domain" value="<?php echo $domain; ?>">
<input type="hidden" name="action" value="update">
<input type="submit" name="submit" value="Update Domain">
</td></tr></table>
</form>
<p>&nbsp;</p>

<font color="#ff0000"><b>Careful!</b></font>
<form action="domain.php" method="post">
<input type="hidden" name="action" value="delete_domain">
<input type="hidden" name="domain" value="<?php echo $domain; ?>">
<input type="submit" value="Delete Domain">
</form>
<?php
}

function update_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip)
{
	global $TLD, $tld_db;

	show_header();
	$updated=strftime('%Y-%m-%d');
	$userid=$_SESSION['userid'];
	$base=sqlite_open_now($tld_db, 0666);
	$query = "SELECT userid FROM domains WHERE userid='".$userid."' AND domain='".$domain."' LIMIT 1";
	$results = sqlite_query_now($base, $query);
	$arr=sqlite_fetch_array_now($results);
	$real_userid=$arr['userid'];
	if($userid != $real_userid)
	{
		echo "<font color=\"#ff0000\"><b>Error: You do not have permission to modify this domain.</b></font>";
		die;
	}
	echo "Updating ".$domain.$TLD."...";
	if(($ns1_ip != "NULL") && ($ns2_ip != "NULL"))
	{
		if( (!validateIPAddress($ns1_ip)) && (!validateIPAddress($ns2_ip)) )
		{
			echo "Error. NS1 and NS2 custom nameservers must be IP addresses.";
		} else {
			$query = "UPDATE domains SET ns1='".$ns1."', ns2='".$ns2."', ns1_ip='".$ns1_ip."', ns2_ip='".$ns2_ip."', updated='".$updated."' WHERE domain='".$domain."'";
		}
	} else {
		$query = "UPDATE domains SET ns1='".$ns1."', ns2='".$ns2."', updated='".$updated."' WHERE domain='".$domain."'";
	}
	sqlite_query_now($base, $query);
	echo "Done. The changes should take effect within the hour. Please be aware some networks may not see the changes for up to 72 hours.<BR>";
	if($ns1 == $ns2)
	{
		echo "<b>Please Note:</b> We highly recommend that you use two different nameserver values instead of the same one.";
	}
}

function check_domain1($domain)
{
	/* sanity check the domain */
	$name=htmlspecialchars(stripslashes($domain));
	$name=preg_replace("/[^a-zA-Z0-9\-]/","", $name); /* replace characters we do not want */
	$name=preg_replace('/^[\-]+/','',$name); /* remove starting hyphens */
	$name=preg_replace('/[\-]+$/','',$name); /* remove ending hyphens */
	$name=str_replace(" ", "", $name); /* remove spaces */
	$name=str_replace("--", "-", $name); /* remove double hyphens */
	$name=strtolower($name); /* all lower case to remove confusion */
	if( (strlen($name)<2) || (strlen($name)>50))
	{
		echo "Sorry, domain names must contain at least 2 characters and be no longer than 50 characters.";
		echo "Please go back and try again.";
		die;
	}
	if(strlen($name)>1)
	{
		echo "Checking ".$name.$TLD." for you...";
		if(domain_taken($name))
		{
			echo "<font color=\"#ff0000\"><b>Taken</b></font><BR><BR>Sorry, that name is already taken.";
		} else {
			echo "<font color=\"#008000\"><b>Available!</b></font><BR><BR>Congratulations! ".$name.$TLD." is available.\n";
			echo "Would you like to register it now?\n<form action=\"register.php\" method=\"post\">\n<input type=\"hidden\" name=\"domain\" value=\"".$name."\">\n<input type=\"submit\" name=\"submit\" value=\"Yes!\">\n</form>\n";

			frm_register_domain($name.$TLD);
		}
		echo "You can use the form below to search for another domain if you like.";
	}
	echo "<p><BR>\n<form action=\"domain.php\" method=\"post\">\nDomain name <input type=\"text\" name=\"domain\">".$TLD."&nbsp;<input type=\"submit\" name=\"check\" value=\"Check\">\n</form>\n</p>\n</center>\n";
}

/* user.php functions */

function login($username, $password)
{
	global $tld_db;

	$username=htmlspecialchars(stripslashes($username));
	$password=htmlspecialchars(stripslashes($password));
	$base=sqlite_open_now($tld_db, 0666);
	$real_password=md5($password);
	$query = "SELECT userid, username, email, country FROM users WHERE username='".$username."' AND password='".$real_password."' AND verified=1 LIMIT 1";
	$results = sqlite_query_now($base, $query);
	if(dbNumRows($results))
	{
		$arr=sqlite_fetch_array_now($results);
		$_SESSION['username'] = $username;
		$_SESSION['userid'] = $arr['userid'];
		// $_SESSION['email'] = $arr['email'];
		// $_SESSION['country'] = $arr['country'];
		header("location: index.php");
	} else {
		show_header();
		echo "Incorrect username or password or account not verified. Please try again.";
		die;
	}
}

function form_login()
{
	global $TLD;
	show_header();
?>
<form action="user.php" method="post">
<table width="400" align="center">
<tr><td colspan="2" align="center"><h2><?php echo $TLD; ?> User Login</h2></td></tr>
<tr><td valign="top">Username:</td><td><input type="text" name="username"></td></tr>
<tr><td valign="top">Password:</td><td><input type="password" name="password"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="submit" value="Login"></td></tr>
</table>
<input type="hidden" name="action" value="login">
</form>
<?php
}

function form_register()
{
	global $TLD;
	show_header();
?>
<div class="tm-section tm-section-color-white">
            <div class="uk-container uk-container-center">
<form action="user.php" method="post">
<table width="400" align="center">
<tr><td colspan="2" align="center"><h2><?php echo $TLD; ?> User Registration</h2><font size="-1">All entries must be at least 5 characters long.</font></td></tr>
<tr><td>Name</td><td><input type="text" name="name" maxlength="50"></td></tr>
<tr><td>Email</td><td><input type="text" name="email" maxlength="50"><sup>*</sup></td></tr>
<tr><td>Username</td><td><input type="text" name="username" maxlength="20"></td></tr>
<tr><td valign="top">Password</td><td><input type="password" name="password1"><sup>**</sup></td></tr>
<tr><td valign="top">Password Confirm</td><td><input type="password" name="password2"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="submit" value="Register"></td></tr>
<tr><td colspan="2"><font size="-1">
<sup>*</sup> Choose a reliable email as this can only be changed later by contacting support.<br>
<sup>**</sup> This is encrypted and cannot be retrieved.<br>
</font></td></tr>
</table>
<input type="hidden" name="action" value="register">
</form>
</div>
</div>
<?php
}

function register($username, $name, $email, $password)
{
	global $TLD, $tld_db;

	show_header();
	
	/* prepare clean data */
	$username=htmlspecialchars(stripslashes($username));
	$password=htmlspecialchars(stripslashes($password));
	$name=htmlspecialchars(stripslashes($name));
	$email=htmlspecialchars(stripslashes($email));
	
	/* perform validation checks */
	if(filter_var($email, FILTER_VALIDATE_EMAIL) == FALSE)
	{
		echo "Not a valid email address";
		die;
	}
	$username=clean_up_input($username); /* just in case */
	$username=strtolower($username);
	if(username_taken($username))
	{
		echo "That username is already taken. Please try using another, different username.";
		die;
	}
	
	/* let the user know */
	echo "Creating new account for ".$name." via ".$username."<BR>\n";

	/* generate user verification key */
	$userkeyfile="/tmp/".$username.".txt";
	$fh=fopen($userkeyfile, 'w') or die("Can't create user key verification file. Please report this to the admin.");
	$userkey=unique_id(16);
	fwrite($fh, $userkey);
	fclose($fh);

	/* prepare account */
	$base=sqlite_open_now($tld_db, 0666);
	$real_password=md5($password);
	date_default_timezone_set('Australia/Brisbane');
	$registered=strftime('%Y-%m-%d');
	$query = "INSERT INTO users (username, password, name, email, registered, verified) VALUES('".$username."', '".$real_password."', '".$name."', '".$email."', '".$registered."', 0)";
	$results = sqlite_query_now($base, $query);
	
	/* construct email */
	$msg_FROM = "FROM: hostmaster@opennic".$TLD."";
	$msg_subject = "OpenNIC".$TLD." User Registration.";
	$msg = "Welcome ".$name." to OpenNIC.".$TLD."!\n\n";
	$msg .= "Your details are:\n";
	$msg .= "Username: ".$username."\n";
	$msg .= "Password: (The one you specified during sign up. Remember, this is encrypted and cannot be retrieved.)\n\n";
	$msg .= "Always ensure your contact details are up to date.\n\n";
	$msg .= "To confirm this email and activate your account, please visit http://opennic.".$TLD."/confirm.php?username=".$username."&userkey=".$userkey."\nYou have 24 hours to activate your account, otherwise it will be deleted.\n\n";
	$msg .= "Thank you for your patronage.\nOpenNIC".$TLD." Administration.\n";
	mail($email, $msg_subject, $msg, $msg_FROM);
	echo "If registration was successful, you should receive an email shortly. Please contact hostmaster@opennic.".$TLD." if you do not receive one within 24 hours. Please ensure that email address is on your email whitelist.";
	// echo "DEBUG: [".$msg."]";
}

function dashboard()
{
	global $TLD, $domain_expires, $tld_db;
	show_header();
	
	$username=$_SESSION['username'];
	$userid=$_SESSION['userid'];
?>
<div class="tm-section tm-section-color-white">
            <div class="uk-container uk-container-center uk-text-center">
            <?php
	// echo "<p align=\"right\"><a href=\"user.php?action=logout\">Logout</a></p>\n";
	echo "<H2>Welcome to ".$username."'s Dashboard for .".$TLD."</H2>\n";
	echo "<b>My .".$TLD." domains</b><BR><BR>";
	$base=sqlite_open_now($tld_db, 0666);
	$query="SELECT domain, registered, expires FROM domains WHERE userid=".$userid."";
	$results = sqlite_query_now($base, $query);
	if(dbNumRows($results))
	{
		echo "<table width=\"400\" align=\"center\" border=0 cellspacing=1 cellpadding=0>\n";
		echo "<tr><td>Domain Name</td><td>Created</td>";
		if($domain_expires==1)
		{
			echo "<td>Expires</td>";
		}
		echo "</tr>\n";

		while($arr=sqlite_fetch_array_now($results))
		{
			echo "<tr><td><a href=\"domain.php?action=modify&domain=".$arr['domain']."\">".$arr['domain'].$TLD."</a></td><td>".$arr['registered']."</td>";
			if($domain_expires==1)
			{
				echo "<td>".$arr['expires']."</td>";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
	} else {
		echo "You do not have any domains registered.\n";
	}
	echo "You can register a new ".$TLD." <a href=\"domain.php?action=frm_check_domain\">here</a>.";

	$get_user_details="SELECT name, email, country FROM users WHERE userid='".$userid."' AND username='".$username."' LIMIT 1";
	$base=sqlite_open_now($tld_db, 0666);
	$get_user_details_results = sqlite_query_now($base, $get_user_details);
	$get_user_details_arr=sqlite_fetch_array_now($get_user_details_results);
	$name=$get_user_details_arr['name'];
	$email=$get_user_details_arr['email'];
	$country=$get_user_details_arr['country'];
?>
<BR><BR>
<form action="user.php" method="post">
<table width="450" align="center">
<tr><td colspan="2" align="center"><b>.<?php echo $TLD; ?> User Details</b></td></tr>
<tr><td>Name</td><td><?php echo $name; ?></td></tr>
<tr><td>Email</td><td><?php echo $email; ?><sup>*</sup></td></tr>
<tr><td>Country</td><td>
<select name="country">
<?php
if(strlen($country)>0)
{
	echo "<option value=\"".$country."\" selected>Current (".$country.")</option>\n";
} else {
	echo "<option>Select</option>\n";
}
?>
<option>------</option>
<option value="AU">Australia</option>
<option value="CA">Canada</option>
<option value="DE">Germany</option>
<option value="UK">United Kingdom</option>
<option value="US">United States</option>
<option value="AF">Afghanistan</option>
<option value="AL">Albania</option>
<option value="DZ">Algeria</option>
<option value="AS">American Samoa</option>
<option value="AD">Andorra</option>
<option value="AG">Angola</option>
<option value="AI">Anguilla</option>
<option value="AG">Antigua &amp; Barbuda</option>
<option value="AR">Argentina</option>
<option value="AA">Armenia</option>
<option value="AW">Aruba</option>
<option value="AU">Australia</option>
<option value="AT">Austria</option>
<option value="AZ">Azerbaijan</option>
<option value="BS">Bahamas</option>
<option value="BH">Bahrain</option>
<option value="BD">Bangladesh</option>
<option value="BB">Barbados</option>
<option value="BY">Belarus</option>
<option value="BE">Belgium</option>
<option value="BZ">Belize</option>
<option value="BJ">Benin</option>
<option value="BM">Bermuda</option>
<option value="BT">Bhutan</option>
<option value="BO">Bolivia</option>
<option value="BL">Bonaire</option>
<option value="BA">Bosnia &amp; Herzegovina</option>
<option value="BW">Botswana</option>
<option value="BR">Brazil</option>
<option value="BC">British Indian Ocean Ter</option>
<option value="BN">Brunei</option>
<option value="BG">Bulgaria</option>
<option value="BF">Burkina Faso</option>
<option value="BI">Burundi</option>
<option value="KH">Cambodia</option>
<option value="CM">Cameroon</option>
<option value="CA">Canada</option>
<option value="IC">Canary Islands</option>
<option value="CV">Cape Verde</option>
<option value="KY">Cayman Islands</option>
<option value="CF">Central African Republic</option>
<option value="TD">Chad</option>
<option value="CD">Channel Islands</option>
<option value="CL">Chile</option>
<option value="CN">China</option>
<option value="CI">Christmas Island</option>
<option value="CS">Cocos Island</option>
<option value="CO">Colombia</option>
<option value="CC">Comoros</option>
<option value="CG">Congo</option>
<option value="CK">Cook Islands</option>
<option value="CR">Costa Rica</option>
<option value="CT">Cote D'Ivoire</option>
<option value="HR">Croatia</option>
<option value="CU">Cuba</option>
<option value="CB">Curacao</option>
<option value="CY">Cyprus</option>
<option value="CZ">Czech Republic</option>
<option value="DK">Denmark</option>
<option value="DJ">Djibouti</option>
<option value="DM">Dominica</option>
<option value="DO">Dominican Republic</option>
<option value="TM">East Timor</option>
<option value="EC">Ecuador</option>
<option value="EG">Egypt</option>
<option value="SV">El Salvador</option>
<option value="GQ">Equatorial Guinea</option>
<option value="ER">Eritrea</option>
<option value="EE">Estonia</option>
<option value="ET">Ethiopia</option>
<option value="FA">Falkland Islands</option>
<option value="FO">Faroe Islands</option>
<option value="FJ">Fiji</option>
<option value="FI">Finland</option>
<option value="FR">France</option>
<option value="GF">French Guiana</option>
<option value="PF">French Polynesia</option>
<option value="FS">French Southern Ter</option>
<option value="GA">Gabon</option>
<option value="GM">Gambia</option>
<option value="GE">Georgia</option>
<option value="DE">Germany</option>
<option value="GH">Ghana</option>
<option value="GI">Gibraltar</option>
<option value="GB">Great Britain</option>
<option value="GR">Greece</option>
<option value="GL">Greenland</option>
<option value="GD">Grenada</option>
<option value="GP">Guadeloupe</option>
<option value="GU">Guam</option>
<option value="GT">Guatemala</option>
<option value="GN">Guinea</option>
<option value="GY">Guyana</option>
<option value="HT">Haiti</option>
<option value="HW">Hawaii</option>
<option value="HN">Honduras</option>
<option value="HK">Hong Kong</option>
<option value="HU">Hungary</option>
<option value="IS">Iceland</option>
<option value="IN">India</option>
<option value="ID">Indonesia</option>
<option value="IA">Iran</option>
<option value="IQ">Iraq</option>
<option value="IR">Ireland</option>
<option value="IM">Isle of Man</option>
<option value="IL">Israel</option>
<option value="IT">Italy</option>
<option value="JM">Jamaica</option>
<option value="JP">Japan</option>
<option value="JO">Jordan</option>
<option value="KZ">Kazakhstan</option>
<option value="KE">Kenya</option>
<option value="KI">Kiribati</option>
<option value="NK">Korea North</option>
<option value="KS">Korea South</option>
<option value="KW">Kuwait</option>
<option value="KG">Kyrgyzstan</option>
<option value="LA">Laos</option>
<option value="LV">Latvia</option>
<option value="LB">Lebanon</option>
<option value="LS">Lesotho</option>
<option value="LR">Liberia</option>
<option value="LY">Libya</option>
<option value="LI">Liechtenstein</option>
<option value="LT">Lithuania</option>
<option value="LU">Luxembourg</option>
<option value="MO">Macau</option>
<option value="MK">Macedonia</option>
<option value="MG">Madagascar</option>
<option value="MY">Malaysia</option>
<option value="MW">Malawi</option>
<option value="MV">Maldives</option>
<option value="ML">Mali</option>
<option value="MT">Malta</option>
<option value="MH">Marshall Islands</option>
<option value="MQ">Martinique</option>
<option value="MR">Mauritania</option>
<option value="MU">Mauritius</option>
<option value="ME">Mayotte</option>
<option value="MX">Mexico</option>
<option value="MI">Midway Islands</option>
<option value="MD">Moldova</option>
<option value="MC">Monaco</option>
<option value="MN">Mongolia</option>
<option value="MS">Montserrat</option>
<option value="MA">Morocco</option>
<option value="MZ">Mozambique</option>
<option value="MM">Myanmar</option>
<option value="NA">Nambia</option>
<option value="NU">Nauru</option>
<option value="NP">Nepal</option>
<option value="AN">Netherland Antilles</option>
<option value="NL">Netherlands (Holland, Europe)</option>
<option value="NV">Nevis</option>
<option value="NC">New Caledonia</option>
<option value="NZ">New Zealand</option>
<option value="NI">Nicaragua</option>
<option value="NE">Niger</option>
<option value="NG">Nigeria</option>
<option value="NW">Niue</option>
<option value="NF">Norfolk Island</option>
<option value="NO">Norway</option>
<option value="OM">Oman</option>
<option value="PK">Pakistan</option>
<option value="PW">Palau Island</option>
<option value="PS">Palestine</option>
<option value="PA">Panama</option>
<option value="PG">Papua New Guinea</option>
<option value="PY">Paraguay</option>
<option value="PE">Peru</option>
<option value="PH">Philippines</option>
<option value="PO">Pitcairn Island</option>
<option value="PL">Poland</option>
<option value="PT">Portugal</option>
<option value="PR">Puerto Rico</option>
<option value="QA">Qatar</option>
<option value="ME">Republic of Montenegro</option>
<option value="RS">Republic of Serbia</option>
<option value="RE">Reunion</option>
<option value="RO">Romania</option>
<option value="RU">Russia</option>
<option value="RW">Rwanda</option>
<option value="NT">St Barthelemy</option>
<option value="EU">St Eustatius</option>
<option value="HE">St Helena</option>
<option value="KN">St Kitts-Nevis</option>
<option value="LC">St Lucia</option>
<option value="MB">St Maarten</option>
<option value="PM">St Pierre &amp; Miquelon</option>
<option value="VC">St Vincent &amp; Grenadines</option>
<option value="SP">Saipan</option>
<option value="SO">Samoa</option>
<option value="AS">Samoa American</option>
<option value="SM">San Marino</option>
<option value="ST">Sao Tome &amp; Principe</option>
<option value="SA">Saudi Arabia</option>
<option value="SN">Senegal</option>
<option value="RS">Serbia</option>
<option value="SC">Seychelles</option>
<option value="SL">Sierra Leone</option>
<option value="SG">Singapore</option>
<option value="SK">Slovakia</option>
<option value="SI">Slovenia</option>
<option value="SB">Solomon Islands</option>
<option value="OI">Somalia</option>
<option value="ZA">South Africa</option>
<option value="ES">Spain</option>
<option value="LK">Sri Lanka</option>
<option value="SD">Sudan</option>
<option value="SR">Suriname</option>
<option value="SZ">Swaziland</option>
<option value="SE">Sweden</option>
<option value="CH">Switzerland</option>
<option value="SY">Syria</option>
<option value="TA">Tahiti</option>
<option value="TW">Taiwan</option>
<option value="TJ">Tajikistan</option>
<option value="TZ">Tanzania</option>
<option value="TH">Thailand</option>
<option value="TG">Togo</option>
<option value="TK">Tokelau</option>
<option value="TO">Tonga</option>
<option value="TT">Trinidad &amp; Tobago</option>
<option value="TN">Tunisia</option>
<option value="TR">Turkey</option>
<option value="TU">Turkmenistan</option>
<option value="TC">Turks &amp; Caicos Is</option>
<option value="TV">Tuvalu</option>
<option value="UG">Uganda</option>
<option value="UA">Ukraine</option>
<option value="AE">United Arab Emirates</option>
<option value="GB">United Kingdom</option>
<option value="US">United States of America</option>
<option value="UY">Uruguay</option>
<option value="UZ">Uzbekistan</option>
<option value="VU">Vanuatu</option>
<option value="VS">Vatican City State</option>
<option value="VE">Venezuela</option>
<option value="VN">Vietnam</option>
<option value="VB">Virgin Islands (Brit)</option>
<option value="VA">Virgin Islands (USA)</option>
<option value="WK">Wake Island</option>
<option value="WF">Wallis &amp; Futana Is</option>
<option value="YE">Yemen</option>
<option value="ZR">Zaire</option>
<option value="ZM">Zambia</option>
<option value="ZW">Zimbabwe</option>
</select><sup>**</sup></td></tr>
<tr><td>Current Password</td><td><input type="password" name="password"></td></tr>
<tr><td valign="top">Password</td><td><input type="password" name="password1"><BR><font size="-1">(Must be at least 5 characters long)</font></td></tr>
<tr><td>Password Confirm</td><td><input type="password" name="password2"></td></tr>
<tr><td colspan="2" align="center"><input type="submit" name="submit" value="Update"></td></tr>
<input type="hidden" name="action" value="update_account">
<tr><td colspan="2">
<font size="-1">
<sup>*</sup>Please contact support to change this.<BR>
<sup>**</sup>This is optional and for our statistics only.
</font></td></tr>
</table>
</form>
</div>
</div>
<?php
}

function update_account($country, $password)
{
	global $tld_db;
	show_header();
	if(!isset($_SESSION['userid']))
	{
		echo "No valid account."; die;
	}
	$userid=$_SESSION['userid'];
	$password=htmlspecialchars(stripslashes($password));
	$real_password=md5($password);
	$query = "UPDATE users SET country='".$country."', password='".$real_password."' WHERE userid='".$userid."'";
	$base=sqlite_open_now($tld_db, 0666);
	sqlite_query_now($base, $query);
	echo "Details updated.";
}

function create_domain($domain, $ns1, $ns2, $ns1_ip, $ns2_ip)
{
	global $TLD, $user, $userkey;

	$userid=$_SESSION['userid'];
	$username=$_SESSION['username'];
	if(strlen($userid)<1)
	{
		echo "Error validating user session.\n"; die;
	}
	if(strlen($username)<5)
	{
		echo "Error validating user name.\n"; die;
	}
	$ns1=$_POST['ns1'];
	$ns2=$_POST['ns2'];
	$base=sqlite_open_now($tld_db, 0666);
	$real_password=md5($password);
	date_default_timezone_set('Australia/Brisbane');
	$registered=strftime('%Y-%m-%d');
	$query = "INSERT INTO domains (domain, name, email, ns1, ns2, ns1_ip, ns2_ip, registered, expires, updated, userid) VALUES('".$username."', '".$real_password."', '".$name."', '".$email."', '".$registered."', 0)";
	$results = sqlite_query_now($base, $query);
	
}


?>