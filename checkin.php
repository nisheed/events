<!DOCTYPE HTML>
<html>
<head>
<title>Polling System </title>
<meta charset="UTF-8" />
<meta name="Author" content="nisheed_km@yahoo.com">
<link rel="stylesheet" type="text/css" href="css/reset.css">
<link rel="stylesheet" type="text/css" href="css/structure.css">
<link rel="stylesheet" type="text/css" href="css/buttons.css">
<link rel="stylesheet" type="text/css" href="css/event.css">
<link rel="stylesheet" type="text/css" href="css/background_checkin.css">

	<script language="javascript"> 
	function toggle() {
		var y = document.getElementById("ans_yes");
		var n = document.getElementById("ans_no");

		if(y.checked) {
	    	document.getElementById("checkinform").style.display = "block";
	  	}
		else {
			document.getElementById("checkinform").style.display =  "none";
		}
	} 

    function validateForm() {
        
        //skip if 'no'
        var notgoing=document.forms["frmCheckIn"].elements["ans[]"][1].checked;
        if (notgoing) {
            return true;
        }

        document.forms["frmCheckIn"]["seats_msg"].value = "";
        document.forms["frmCheckIn"]["show_msg"].value = "";

        var idx=document.forms["frmCheckIn"]["slctStime"].selectedIndex
        var opts=document.forms["frmCheckIn"]["slctStime"].options;
        //alert(idx);
        if (opts[idx].value == "header") {
          document.forms["frmCheckIn"]["show_msg"].value = "forgot to select one!";
          //alert("seats must be filled out");
          return false;
        }

        var xavail=document.forms["frmCheckIn"]["seats_" + idx + "_avail"].value;
        var xseats=document.forms["frmCheckIn"]["slctNpers"].value;
        var xseats_prev=document.forms["frmCheckIn"]["prev_npers"].value;
        //alert( xavail + " - " + xseats);
        var temp_x=document.forms["frmCheckIn"]["slctStime"].value;
        var temp_y=document.forms["frmCheckIn"]["prev_stime"].value;
        if (temp_x == temp_y) {
            xseats = parseInt(xseats) - parseInt(xseats_prev);
        }
        //alert( xavail + " - " + xseats);
        if(parseInt(xseats) > parseInt(xavail)) {
            //alert("you are booking more (" + xseats + ") than what is available (" + xavail + ") buddy!");
            document.forms["frmCheckIn"]["seats_msg"].value = "not enough seats available!";
            //document.forms["myform"]["slctNpers"].focus();
            //document.forms["myform"]["slctNpers"].select();
            return false;
        }

        return true;
    }
	</script>

</head>

<body>
<?php

// check for cookies here...
	if ($_COOKIE['Valid'] != 1) {
		header('Location: index.php');
		exit ;
	}

	$curr_event = 2;
    $conn = mysql_connect('events.domain.com', 'eventadmin', 'pa$$w0rd');
    if (!$conn) {
        die('Could not connect: ' . mysql_error());
    }
    //echo 'Connected successfully';
    mysql_select_db("event", $conn);

    //print_r($_POST);
?>

<?php

    function updateShowCount($event) {
        $result = mysql_query("SELECT * FROM tbl_event WHERE e_id='" . $event . "';");
        if (!$result) {
            echo ('Invalid query: ' . mysql_error());
        } elseif (mysql_num_rows($result) > 0 ) {
            $row = mysql_fetch_assoc($result);
            //echo $row['e_showavails'] . " : ";
            $stimes = explode(",", $row['e_showtimes']);
            $smaxs = explode(",", $row['e_showmaxs']);
            for($i = 0; $i < sizeof($stimes); $i++) {
                $qry = "select sum(c_npers) from tbl_checkin where c_ans='yes' and " . 
                        "c_showtime='" . $stimes[$i] . "';"; 
                $result = mysql_query($qry);
                $sum_stimes = mysql_fetch_array($result);
                $smaxs[$i] = $smaxs[$i] - $sum_stimes[0];
                //echo "<br /> sum = $sum_stimes[0] : savailsnew = $savails[$i] <br />";
            }
            $new_showavails = join(",",$smaxs);
            //echo $new_showavails;
            $qry = "UPDATE tbl_event SET e_showavails='" . $new_showavails . "' " .
                    " WHERE e_id='" . $event . "';";
            $result = mysql_query($qry);
            if (!$result) {
                die('Invalid query: ' . mysql_error());
            }
        } else {
            echo "$event record error! <br />" ;
        }
    }

	$message = '';
	$success = 0;

	if (isset($_POST['slctNpers'])) $dirty = 1;

	if (isset($_POST['ans'][0])) {

		if ( ($_POST['ans'][0] == 1) && isset($_POST['slctStime']) ) {
			if ($_POST['slctStime'] == '[select your show]') {
	        	$message = "please choose your show preference.";
		    	$success = 0;
		    	$validated = 0;	
			} else {
				$validated = 1;
			}
		} else {
			$validated = 1;
		}

		if ($validated == 1) {

			//echo $_POST['ans'][0];

			$cans 		= ($_POST['ans'][0] == 1) ? 'yes' 					: 'no';
			$cstime 	= ($_POST['ans'][0] == 1) ? $_POST['slctStime'] 	: '-';
			$cnpers 	= ($_POST['ans'][0] == 1) ? $_POST['slctNpers'] 	: '0';
			$dt 		= ($_POST['ans'][0] == 1) ? date("Y-m-d H:i:s") 	: '';

			$query = "SELECT * FROM tbl_checkin WHERE c_uid='" . $_COOKIE['UID'] . "';";
			//echo $query;
			$out = mysql_query($query);
		    if (!$out) {
		        die('Invalid query: ' . mysql_error());
		    }
			if (mysql_num_rows($out) > 0 ) {
				//echo "update<br>";
				$query = "UPDATE tbl_checkin SET " .
						" c_ans='". $cans ."', " . 
						" c_showtime='" . $cstime . "', " . 
						" c_npers='" . $cnpers . "', " .
						" c_date='" . $dt . "' " . 
                        " WHERE c_uid='" .  $_COOKIE['UID'] . "';";
				$ret = mysql_query($query);
			    if (!$out) {
	        		$message = 'Booking failed! : ' . mysql_error();
			    	$success = 0;
			    } else {
			    	if ($cans == 'no') {
			    		$message = "You (" . $_COOKIE['UName'] . ") have marked yourself as 'not going' successfully!";
			    	} else {
                        //updateShowCount($curr_event);
			    		$message = "You (" . $_COOKIE['UName'] . ") have booked '" . $_POST['slctNpers'] . "' seat(s) for the '" . $_POST['slctStime'] . "' show successfully!";
			    	}
			    	$success = 1;
			    }			
			} else {
				//echo "add<br>";
				$query = "INSERT INTO tbl_checkin (e_id,c_uid,c_uname,c_ans,c_showtime,c_npers,c_date) VALUES ('" . 
					$curr_event 		. "', '" . 
					$_COOKIE['UID'] . "', '" . 
					$_COOKIE['UName'] . "', '" . 
					$cans . "', '" . 
					$cstime . "', '" . 
					$cnpers . "', '" .
					$dt . "');";
				$ret = mysql_query($query);
			    if (!$out) {
		        	$message = 'Booking failed! : ' . mysql_error();
			    	$success = 0;
			    } else {
			    	if ($cans == 'no') {
			    		$message = "You (" . $_COOKIE['UName'] . ") have marked yourself as 'not going' successfully!";
			    	} else {
                        //updateShowCount($curr_event);
			    		$message = "You (" . $_COOKIE['UName'] . ") have booked '" . $_POST['slctNpers'] . "' seat(s) for the '" . $_POST['slctStime'] . "' show successfully!";
			    	}
			    	$success = 1;
			    }
			}
		}
	} elseif (isset($_POST['slctNpers'])) {
    	$message = "confused? put it off..!";
    	$success = 0;
    	$validated = 0;				
	}

    updateShowCount($curr_event);
	//echo $message . "\n";

?>


<div class="wrapper">
<?php

    $result = mysql_query("SELECT * FROM tbl_event WHERE e_id='" . $curr_event . "';");
    $auth = 0;
    if (!$result) {
        echo ('Invalid query: ' . mysql_error());
    } elseif (mysql_num_rows($result) > 0 ) {
        $row = mysql_fetch_assoc($result);
        $admins = explode(",", $row['e_admins']);
        foreach($admins as $admin ){
            if ($_COOKIE['UID'] == $admin) {
                $auth = 1;
            }
        }
    } else {
        //echo it
        header('Location: index.php');
        exit ;
    }

    if ($auth == 1) {
        echo "<div class='download'>";
             echo "<a href='report.php' class='button small green' >Reports</a>";
        echo "</div>";
    }

	echo '<div id="message" class="' . (($success == 1) ? 'green' : 'red'). '" >';
	echo $message;
	echo '</div>';

	echo "<div id='showinfo'>";

		    //$result = mysql_query("SELECT * FROM tbl_event WHERE e_id='" . $curr_event . "';");
		    //if (!$result) {
		    //    die('Invalid query: ' . mysql_error());
		    //} else {
            //    if (mysql_num_rows($result) > 0 ) {
                    mysql_data_seek($result, 0);
                    while ($row = mysql_fetch_assoc($result)) {
                        echo '<div class="header">';
                        echo $row['e_name'];
                        echo '</div>';
                        echo '<div class="details">';
                        echo 'venue : ' . $row['e_venue'] . '<br />';
                        $sdate = strtotime($row['e_date']);
                        $sdate = date("d-M-Y",$sdate);
                        echo 'date : ' . $sdate;
                        echo '</div>';
                    } 
            //   } else {
            //       //echo it
            //       header('Location: index.php');
            //       exit ;
            //   }
		    //}
		    $urow = '';
		   	$query = "SELECT * FROM tbl_checkin WHERE c_uid='" . $_COOKIE['UID'] . "';";
			//echo $query;
			$out = mysql_query($query);
		    if (!$out) {
		        die('Invalid query: ' . mysql_error());
		    }
			if (mysql_num_rows($out) > 0 ) {
				$urow = mysql_fetch_assoc($out);
			}

		?>
	</div>
	
	<form name="frmCheckIn" action="checkin.php" method="post" onsubmit="return validateForm()">
	<table>
		<tr>
			<th class="right">Going?</th>
			<th class="left">				
				<?php $boolChecked = ($urow['c_ans'] == 'yes') ? 'checked' : ''; ?>
				<?php echo '<input type="radio" name="ans[]" id="ans_yes" value="1" onClick="javascript:toggle();" ' . $boolChecked . ' />' ?>
				<?php echo '<label for="ans_yes">Yes</label>' ?>

				<?php $boolChecked = ($urow['c_ans'] == 'no') ? 'checked' : ''; ?>
				<?php echo '<input type="radio" name="ans[]" id="ans_no" value="2" onClick="javascript:toggle();" ' . $boolChecked . ' />' ?>
				<?php echo '<label for="ans_no">No</label>' ?>	
			</th>
		</tr>
		<tr >
			<td colspan=2;>
			<div id="checkinform">
				<table>
					<tr>
						<td class="right">Which show?</td>
						<td class="left">	
                        <?php
							mysql_data_seek($result, 0);
	                        $row = mysql_fetch_assoc($result);
                        	$savails = explode(",", $row['e_showavails']);
                            for($i = 0; $i < sizeof($savails); $i++) {
                                //echo "$savails[$i]";
                                $j = $i + 1; 
                                echo "<input type='hidden' name='seats_" . $j . "_avail' value='" . $savails[$i] . "' />";
                            }
                    	    echo "<select name='slctStime'>";
                            echo "<option selected value='header'>[select your show]</option>";
                        	$stimes = explode(",", $row['e_showtimes']);
                        	//foreach($stimes as $stime ){
                            for($i = 0; $i < sizeof($stimes); $i++) {
                        		$boolSel = '';
                                if ($stimes[$i] == $urow['c_showtime']) $boolSel = "selected";
                                echo "<option $boolSel value='" . $stimes[$i] . "'>" . $stimes[$i] . " ($savails[$i] available)" . "</option>";
                            }
                            echo '</select>';			
                            echo "<input type='hidden' name='prev_stime' value='" . $urow['c_showtime'] . "' />";
                            echo "<input type='hidden' name='prev_npers' value='" . $urow['c_npers'] . "' />";
                        ?>		
                        <input type="text" disabled="disabled" name="show_msg" id="vldn_msg" />
						</td>
					</tr>
					<tr>
						<td class="right">How many are you?<br/>(incl. self)</td>						
						<td class="left">                    	
							<select name='slctNpers'>
	                        <option selected value='1'>1</option>
	                        <?php
								mysql_data_seek($result, 0);
		                        $row = mysql_fetch_assoc($result);
	                        	for ($i = 2; $i <= $row['e_quota']; $i++) {
	                        		$boolSel = '';
                                	if ($i == $urow['c_npers']) $boolSel = "selected";
	                                echo "<option $boolSel value='" . $i . "'>" . $i. "</option>";
	                            }
	                        ?>		
	                        </select>			
                            <input type="text" disabled="disabled" name="seats_msg" id="vldn_msg" />
                    	</td>
					</tr>
				</table>
			</div>
			</td>
		</tr>
		<tr>
			<td class="right"></td>
			<td class="right"><button type="submit">Confirm</button></td>
		</tr>
	</table>
</form>

</div>

<?php echo '<script type="text/javascript"> javascript:toggle(); </script>'; ?>

</body>
</html>
