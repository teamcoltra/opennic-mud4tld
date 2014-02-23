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
	global $TLD, $user, $userkey, $tld_svr;
	if($domain=="register" || $domain=="opennic" || $domain=="example")
	{
		return 1;
	}
	$URL=$tld_svr."?cmd=check&user=".$user."&userkey=".$userkey."&tld=".$TLD."&domain=".$domain;
	$handle=fopen($URL, "r");
	$ret_data=fread($handle, 1024);
	fclose($handle);
	return $ret_data;
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

?>