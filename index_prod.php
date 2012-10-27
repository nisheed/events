<!DOCTYPE HTML>
<html>
<head>
<title>Event Check-In System </title>
<meta charset="UTF-8" />
<meta name="Author" content="nisheed_km@yahoo.com">
<link rel="stylesheet" type="text/css" href="css/reset.css">
<link rel="stylesheet" type="text/css" href="css/structure.css">
<link rel="stylesheet" type="text/css" href="css/event.css">
<link rel="stylesheet" type="text/css" href="css/background_login.css">


<script type="text/javascript">
//window.onload = function() {
    //var txtPwd = document.getElementById("txtUser"); 
    //txtBox.focus();
    //alert(document.cookie);
//};
</script>

</head>

<body>
<?php

if (isset($_POST['txtUser']) && isset($_POST['txtPwd'])) {
	setcookie('UID',$_POST{'txtUser'},time()+900 );
	if (connect($_POST['txtUser'],$_POST['txtPwd'])) {
		setcookie('Valid','1',time()+900 );
		header('Location: checkin.php');
		exit;
	} else {
		setcookie('Valid','0',false );
	}
}

function connect($user, $passwd) {

	require_once '/usr/share/pear/Net/LDAP2.php';

	$config = array (
	    'binddn'    => "uid=$user,ou=people,dc=domain,dc=com",
	    'bindpw'    => "$passwd",
	    'basedn'    => 'dc=domain,dc=com',
	    'host'      => 'ldaprr.domain.com'
	);

	$ldap = Net_LDAP2::connect($config);

	if (PEAR::isError($ldap)) {
	    //echo 'Could not connect to LDAP-server: '.$ldap->getMessage();
	    return FALSE;
	}

    $filter = 'uid='.$user;
    $searchbase = 'dc=domain,dc=com';
    $options = array(
        'scope' => 'sub',
        'attributes' => array('uid', 'cn')
    );

    $result = $ldap->search($searchbase, $filter, $options);

    $entries = $result->entries();

    if (count($entries) != 1){
        echo ".";
    } else {
        foreach ($entries as $entry) {
		    setcookie('UName',$entry->getValue('cn'),time()+900 );
        }
    }

	return TRUE;
}

?>

<h1>Event Check-In System</h1>

<form name="login" class="box login" method="post" action="index.php">
	<fieldset class="boxBody">
	  <label>LDAP Username</label>
	  <input id="txtUser" name="txtUser" type="text" tabindex="1" placeholder="" required>
	  <label>LDAP Password</label>
	  <!--<label><a href="#" class="rLink" tabindex="5">Forget your password?</a>Password</label>-->
	  <input id="txtPwd" name="txtPwd" type="password" tabindex="2" required>
	</fieldset>
	<footer>
		<?php
		if (isset($_POST['txtUser'])) {
			echo '<label class="error">Invalid Username/Password..!</label>';
		}
		?>
	  <input type="submit" class="btnLogin" value="Login" tabindex="4">
	</footer>
</form>
<footer id="main">
 	Copyleft!!
</footer>
</body>
</html>
