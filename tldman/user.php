<?php
/*
    By Martin COLEMAN (C) 2012-2014. All rights reserved.
    Released under the 2-clause BSD license.
    See COPYING file for details.
*/
include("conf.php");

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
