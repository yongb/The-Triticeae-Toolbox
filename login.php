<?php

// 12/14/2010 JLee  Change to use curator bootstrap

session_start();
$root = "http://".$_SERVER['HTTP_HOST'];
$root .= str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);
$config['base_url'] = "$root";
$root = preg_replace("/\/\/$/", "/", $root);
$config['root_dir'] = (dirname(__FILE__) . '/');
require_once $config['root_dir'] . 'includes/bootstrap_curator.inc';
require_once $config['root_dir'] . 'includes/email.inc';
require_once $config['root_dir'] . 'includes/aes.inc';
require_once $config['root_dir'] . 'theme/normal_header.php';
require_once $config['root_dir'] . 'securimage/securimage.php';
connect();
?>
<h1>Login/Register</h1>
<div class="section">
<?php
/**
 * Return the registraion form fragment.
 */
  function HTMLRegistrationForm($msg="", $name="", $email="", $cemail="",$answer="no",$institution="")
{
  // ensure that we go back to home..
  $_SESSION['login_referer_override'] = '/';
  $c_no = "";
  $c_yes = "";
  $c_forgot="";
  if ($answer == "no")
    $c_no = 'checked="checked"';
  if ($answer == "yes")
    $c_yes = 'checked="checked"';
  $retval = "";
  if (!empty($msg))
    $retval .= "<div id='form_error'>$msg</div>\n";
  $sql = "select institutions_name, email_domain from institutions";
  $result = mysql_query($sql) or die("<pre>".mysql_error()."\n$sql");
  $domainMap = array();
  $email_domain = split('@', $email);
  $email_domain = $email_domain[1];
  while ($row = mysql_fetch_assoc($result)) {
    $edomain = $row['email_domain'];
    $iname = $row['institutions_name'];
    if ($edomain) {
      array_push($domainMap, "'$edomain': '$iname'");
      if ($edomain == $email_domain)
	$institution = $iname;
    }
  }
  $domainMap = '{' . join(", ", $domainMap) . '}';
  $retval .= <<<HTML
<br />
<h2>Registration</h2>
<script type="text/javascript">
  function validatePassword(pw) {
    if (pw.length < 6) {
      alert("Please supply a password of at least 6 characters.");
      return false;
    }
    return true;
  }
  function guessInstitution(email) {
    var dm=$domainMap;
    return dm[email.split('@')[1]] || '';
  }
</script>

<style type="text/css">
  table td {padding: 2px;}
</style>
<form action="{$_SERVER['SCRIPT_NAME']}" method="post"
      onsubmit="return validatePassword(document.getElementById('password').value);">
  <h3>Name</h3>
  &nbsp;&nbsp;<label for="name">My name is:</label>&nbsp;
  <input type="text" name="name" id="name" value="$name" /><br>
  &nbsp;&nbsp;Triticeae CAP participants <b>must</b> give a full name to be approved.
  <h3>Email address</h3>
  <table border="0" cellspacing="0" cellpadding="0"
	 style="border: none; background: none">
    <tr><td style="border: none; text-align: right;">
	<label for="email">My email address is:<label></td>
      <td style="border:none;">
	<input type="text" name="email" id="email" value="$email" onchange="document.getElementById('institution').value = guessInstitution(document.getElementById('email').value)" />
     </td></tr><tr><td style="border: none; text-align: right;">
	<label for="cemail">Type it again:</label></td>
      <td style="border: none;"><input type="text" name="cemail" id="cemail" value="$cemail" /></td></tr></table>
  <h3>Password</h3>
  <table border="0" cellspacing="0" cellpadding="0" style="border: none; background: none">
    <tr><td style="border: none; text-align: right;">
	<label for="password">I want my password to be:</label></td>
      <td style="border: none;">
	<input type="password" name="password" id="password" /></td></tr>
    <tr><td style="border: none; text-align: right;">
	<label for="cpassword">Type it again:</label></td>
      <td style="border:none;">
	<input type="password" name="cpassword" id="cpassword" /></td></tr></table>
  <h3>Institution</h3>
	<table border="0" cellspacing="0" cellpadding="0" style="border: none; background: none"><tr>
	<td style="border: none; text-align: right;">
	<label for="institution">My institution is:<label></td>
	<td style="border:none;">
	<input type="text" name="institution" id="institution"
	       value="$institution" size="30" /> Required for Triticeae CAP participants.
        </td></tr></table>
  <h3>Are you a Triticeae CAP participant?</h3>
  <input $c_no type="radio" value="no" name="answer" id="answer_no" />
  <label for="answer_no">No</label>
  <br />
  <input $c_yes type="radio" value="yes" name="answer"
	 id="answer_yes" />
  <label for="answer_yes">Yes</label>
  <br />
  <table border="0" cellspacing=="0" cellpadding="0"
	 style="border: none; background: none">
    <tr><td><img id="captcha" src="./securimage/securimage_show.php"
		 alt="CAPTCHA image"><br>
	    <a href="#" onclick="document.getElementById('captcha').src = './securimage/securimage_show.php?' + Math.random();
				 return false;"></td>
      <td>CAPTCHA:
	<input type="text" name="captcha_code" size="10"
		 maxlength="6"></td></tr></table>
   </table>
  <br />
  <br />
  <input type="submit" name="submit_registration" value="Register" />
  </form>
HTML;
  return $retval;
}

/**
 * Return the login form fragment.
 */
function HTMLLoginForm($msg = "") {
  $email = "";
  if (isset($_GET['e']) && !empty($_GET['e']))
    $email = base64_decode($_GET['e']);
  $c_no = "";
  $c_yes = "checked=\"checked\"";
  if (isset($_GET['a']) && !empty($_GET['a'])) {
    $c_no = "";
    $c_yes = "checked=\"checked\"";
  }

  $retval = "";
  if (!empty($msg))
    $retval .= "<div id='form_error'>$msg</div>";
  global $config;
  $dir = explode("/", $config['root_dir']);
  // Pop twice.
  $crop = array_pop($dir); $crop = array_pop($dir);
  $retval .= <<<HTML
<form action="{$_SERVER['SCRIPT_NAME']}" method="post">
  <h3>Why Register?</h3>
  <b>Triticeae CAP Participants</b>
  <ul>
    <li>have pre-release access to all phenotype and genotype data from the project.
    <li>will be allowed to add their own private data to the database (feature to be added).
    <li>can test-load their data files in the Sandbox database before submitting them to the curator. 
      For this purpose please register at the <a href=http://malt.pw.usda.gov/t3/sandbox/$crop>Sandbox site</a>.
    <li>can create unique germplasm line panels (<a href="http://malt.pw.usda.gov/t3/barley/curator_data/tutorial/T3_line_panels.pdf">Tutorial</a>)
 </ul>

  <b>All Registered Users</b>
  <ul>
    <li> Selections made during searches are saved from session to session.
  </ul>

    <h3>What is your email address?</h3>
    My email address is:
    <input type="text" name="email" value="$email" />
    <h3>Do you have a password?</h3>
    <input id="answer_yes" $c_yes type="radio" name="answer" value="yes" />
    <label for="answer_yes">Yes, I have a password:</label>
    <input type="password" name="password" onfocus="$('answer_yes').checked = true"/>
    <br />
    <input id="answer_no" $c_no type="radio" name="answer" value="no" />
    <label for="answer_no">No, I am a new user.</label>
    <br />
    <input id="answer_forgot" $c_forgot type="radio" name="answer" value="forgot" />
    <label for="answer_forgot">I forgot my password.</label>
    <br />
    <input id="answer_change" $c_change type="radio" name="answer" value="change" />
    <label for="answer_change">I want to change my password.</label>
    <br />
    <br />
    <input type="submit" name="submit_login" value="Continue" />
   </form>
HTML;
  return $retval;
}

/**
 * Return the html fragment associated with successful login.
 */
function HTMLLoginSuccess() {
  $url = (isset($_SESSION['login_referer'])) ? $_SESSION['login_referer'] : 'index.php';
  return <<< HTML
<p>You have been logged in. Please wait while you are being
redirected or click <a href="$url">here</a>.</p>
<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
<meta http-equiv="refresh" content="2;url=$url" />
HTML;
}

/**
 * Return the html fragment associated with successful registration.
 */
function HTMLRegistrationSuccess($name, $email) {
  $_SESSION['login_referer_override'] = '/';
  $em = $email;
  $email = base64_encode($email);
  return <<< HTML
<p>Welcome, $name. You are being registered. An email has been sent to
$em describing how to confirm your registration.
<!--
Please wait while you are being redirected to login page
or click <a href="{$_SERVER['SCRIPT_NAME']}">here</a>.</p>
<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
<meta http-equiv="refresh" content="2;url={$_SERVER['SCRIPT_NAME']}"/>
-->
HTML;
}

/**
 * Check if the given user/password pair belongs to a properly
 * registered user that can be logged in.
 */
function isUser($email, $pass) {
  $sql_email = mysql_real_escape_string($email);
  $sql_pass = mysql_real_escape_string($pass);
  $public_type_id = USER_TYPE_PUBLIC;
  $sql = "select * from users where users_name='$sql_email' and
pass = MD5('$sql_pass') and (abs(email_verified) > 0 or
user_types_uid=$public_type_id) limit 1";
  $query = mysql_query($sql) or die("<pre>".mysql_error()."\n\n\n".$sql."</pre>");
  return mysql_num_rows($query) > 0;
}

/**
 * Check if the user+password confirmed his email.
 */
function isVerified($email, $pass) {
  $sql_email = mysql_real_escape_string($email);
  $sql_pass = mysql_real_escape_string($pass);
  $sql = "select email_verified from users where
users_name='$sql_email' and pass=MD5('$sql_pass')";
  $r = mysql_query($sql);
  $row = mysql_fetch_assoc($r);
  if ($row)
    return $row['email_verified'];
  return FALSE;
}

/**
 * See if the password is right for the user.
 */
function passIsRight($email, $pass) {
  $sql_email = mysql_real_escape_string($email);
  $sql_pass = mysql_real_escape_string($pass);
  $sql = "select pass=MD5('$sql_pass') as passIsRight from users
where users_name='$sql_email'";
  $r = mysql_query($sql);
  $row = mysql_fetch_assoc($r);
  if ($row)
    return $row['passIsRight'];
  return FALSE;
}

/**
 * See if the given email belongs to a registered user at all.
 */
function isRegistered($email) {
  $sql_email = mysql_real_escape_string($email);
  $sql = "select * from users where users_name = '$sql_email'";
  $query = mysql_query($sql) or die("<pre>".mysql_error()."\n\n\n".$sql."</pre>");
  return mysql_num_rows($query) > 0;
}

/**
 * Process the login attempt and return the appropriate html
 * fragment re that
 */
function HTMLProcessLogin() {
  $email = $_POST['email'];
  $password = $_POST['password'];
  $rv = '';
  if (isUser($email, $password)) {
    // Successful login
    $_SESSION['username'] = $email;
    $_SESSION['password'] = md5($password);
    $sql = "update users set lastaccess = now() where
users_name = '$email'";
    mysql_query($sql) or die("<pre>" . mysql_error() .
			     "\n\n\n$sql.</pre>");
    // Retrieve stored selection of lines, markers and maps.
    $stored = retrieve_session_variables('selected_lines', $email);
    if (-1 != $stored)
      $_SESSION['selected_lines'] = $stored;
    $stored = retrieve_session_variables('clicked_buttons', $email);
    if (-1 != $stored)
      $_SESSION['clicked_buttons'] = $stored;
    $stored = retrieve_session_variables('mapids', $email);
    if (-1 != $stored)
      $_SESSION['mapids'] = $stored;
    $rv = HTMLLoginSuccess();
  }
  else
    if (!passIsRight($_POST['email'], $_POST['password']))
      $rv = HTMLLoginForm("You entered an incorrect e-mail/password combination. Please, try again.");
    else
      if (!isVerified($_POST['email'], $_POST['password']))
	$rv = HTMLLoginForm("You cannot login until you confirm your email (the link was sent to you at the time of registration)");
      else
	$rv = HTMLLoginForm("Login failed for unknown reason.");
  return $rv;
}

/**
 * Process registration attempt and return appropriate html fragment
 */
function HTMLProcessRegistration() {
  if (isRegistered($_POST['email']))
    return HTMLLoginForm("That e-mail address already has an account associated with it. Please, try again.");
  else
    return HTMLRegistrationForm("", "", $_POST['email'], "",
				$_POST['answer'],
				$_POST['institution']);
}

/**
 * Process forgotten password situation and return appropriate html
 * fragment.
 */
function HTMLProcessForgot() {
  global $root;
  // ensure that we go back to home..
  $_SESSION['login_referer_override'] = '/';
  $email = $_POST['email'];
  if (!isRegistered($email))
    return "<h3 style='color: red'>No such user, please register.</h3>";
  else {
    $key = setting('passresetkey');
    $urltoken = urlencode(AESEncryptCtr($email, $key, 128));
    send_email($email, "Triticeae Toolbox : Reset Your Password",
	       "Hi,
Per your request, please visit the following URL to reset your password:
{$root}resetpass.php?token=$urltoken");
    return "An email has been sent to you with a link to reset your
password.";
  }
}

/**
 * Process password change situation and return appropriate html
 * fragment
 */
function HTMLProcessChange() {
  $_SESSION['login_referer_override'] = '/';
  $email = $_POST['txt_email'];
  $pass = $_POST['OldPass'];
  $rv = "";
  if (isset($email)) {
    if (isUser($email, $pass))
      if ($_POST['NewPass1'] == $_POST['NewPass2']) {
	$sql_email = mysql_real_escape_string($email);
	$sql_pass = mysql_real_escape_string($_POST['NewPass1']);
	$sql = "update users  set pass=MD5('$sql_pass')
where users_name='$sql_email'";
	if (mysql_query($sql))
	  $rv .= "<h3>Password successfully updated</h3>";
	else
	  $rv .= "<div id='form_error'>unexpected error while updating your password..</div>";
      }
      else
	$rv .= "<div id='form_error'>the two values you provided do not match..</div>";
    else
      $rv .= "<div id='form_error'>username/password pair not recognized</div>";
  }
  else
    $rv .= <<<HTML
<form action="{$_SERVER['SCRIPT_NAME']}" method="post">
<input type="hidden" name="answer" value="change">
<input type="hidden" name="submit_login" value="">
Email ID: <input name= "txt_email" type="text" value="{$email}">
<br />Old Password: <input name = "OldPass" type="password"></input>
<br /><br />
New Password: <input name="NewPass1" type="password"></input><br />
Retype New Password: <input name="NewPass2" type="password"></input>
<br />
<input name="cmd_submit" type="submit" value="Submit"></input>
</form>
HTML;
  return $rv;
}

if (isset($_POST['submit_login'])) {
  if (isset($_POST['answer'])) {
    if ($_POST['answer'] == "no")
      echo HTMLProcessRegistration();
    else if ($_POST['answer'] == "yes")
      echo HTMLProcessLogin();
    else if ($_POST['answer'] == "forgot")
      echo HTMLProcessForgot();
    else if ($_POST['answer'] == "change")
      echo HTMLProcessChange();
    else
      echo HTMLLoginForm();
  }
  else
    echo HTMLLoginForm();
 }
 else if (isset($_POST['submit_registration'])) {
   $name = $_POST['name'];
   $email = $_POST['email'];
   $cemail = $_POST['cemail'];
   $password = $_POST['password'];
   $cpassword = $_POST['cpassword'];
   $answer = $_POST['answer'];
   $institution = $_POST['institution'];

   $error = false;
   $error_msg = "";

   if (empty($name)) {
     $error = true;
     $error_msg .= "- You must provide your name.\n";
   }
   if (empty($email)) {
     $error = true;
     $error_msg .= "- You must provide your e-mail addresses.\n";
   }
   else {
     if (empty($cemail) || $email != $cemail) {
       $error = true;
       $error_msg .= "- The e-mail address you provided don't match.\n";
     }
   }	
   if (empty($password)) {
     $error = true;
     $error_msg .= "- You must provide a password.\n";
   }
   else {
     if (empty($cpassword) || $password != $cpassword) {
       $error = true;
       $error_msg .= "- The passwords you provided don't match.\n";
     }
   }
   $securimage = new Securimage();
   if (!$securimage->check($_POST['captcha_code'])) {
     $error = true;
     $error_msg .= "- Please enter the CAPTCHA code correctly.\n";
   }
   if (isRegistered($_POST['email'])) {
     $error = true;
     $error_msg .= "That e-mail address already has an account associated with it. Please, try again.";
   }

   if ($error)
     echo HTMLRegistrationForm($error_msg, $name, $email, $cemail,
			       $answer, $institution);
   else {
     $safe_email = mysql_real_escape_string($email);
     $safe_password = mysql_real_escape_string($password);
     $safe_name = mysql_real_escape_string($name);
     $safe_institution = $institution ? "'" . mysql_real_escape_string($institution) . "'" : 'NULL';
     $desired_usertype = ($answer == 'yes' ? USER_TYPE_PARTICIPANT :
			  USER_TYPE_PUBLIC);
     $safe_usertype = USER_TYPE_PUBLIC;
     $sql = "insert into users (user_types_uid, users_name, pass,
name, email, institution) values ($safe_usertype, '$safe_email',
MD5('$safe_password'), '$safe_name', '$safe_email',
$safe_institution)";
     mysql_query($sql) or die("<pre>" . mysql_error() .
			      "\n\n\n$sql</pre>");
     $key = setting('encryptionkey');
     $urltoken = urlencode(AESEncryptCtr($email, $key, 128));
     // If not currently in the Sandbox, mention it.
     $rd = $config['root_dir'];
     if (!strpos($rd, "sandbox")) {
       $dir = explode("/", $rd); 
       // Pop twice.
       $crop = array_pop($dir);
       $crop = array_pop($dir);
       $sbmsg = "\nIf you will be submitting data to be loaded in T3, 
please register also in the Sandbox, 
http://malt.pw.usda.gov/t3/sandbox/$crop
There you can load your own files directly to see the results 
and verify them before sending the files to the curator.\n";
     }
     send_email($email, "Triticeae Toolbox registration in progress",
"<pre>Dear $name,

Thank you for requesting an account on The Triticeae Toolbox.

To complete your registration, please confirm that you requested it 
by visiting the following URL:
{$root}fromemail.php?token=$urltoken

Your registration will be complete when you have performed this step.
$sbmsg
Sincerely,
The Triticeae Toolbox Team
");

     if ($desired_usertype == USER_TYPE_PARTICIPANT) {
       $capkey = setting('capencryptionkey');
       $capurltoken = urlencode(AESEncryptCtr($email, $capkey, 128));
       send_email(setting('capmail'),
		  "[T3] Validate CAP Participant $email",
"Email: $email
Name: $name
Institution: $institution

Please use the following link to confirm or reject participant status 
of this user:
{$root}fromcapemail.php?token=$capurltoken

A message has been sent to the user that he must confirm his email 
address at
{$root}fromemail.php?token=$urltoken
");
     }
     echo HTMLRegistrationSuccess($name, $email);
   }
 }
 else {
   $referer = @(isset($_SESSION['login_referer_override'])) ?
     $_SESSION['login_referer_override'] : $_SERVER['HTTP_REFERER'];
   if (!empty($referer) &&
 	stripos($referer, $_SERVER['HTTP_HOST']) !== FALSE)
     $_SESSION['login_referer'] = $referer;
   unset($_SESSION['login_referer_override']);
   echo HTMLLoginForm();
 }

?>
	</div>
<?php
$footer_div = 1;
include($config['root_dir'].'theme/footer.php');
?>
