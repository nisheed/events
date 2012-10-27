<!DOCTYPE HTML>
<html>
<head>
<title>Polling System </title>
<meta charset="UTF-8" />
<meta name="Author" content="nisheed_km@yahoo.com">
<link rel="stylesheet" type="text/css" href="css/reset.css">
<link rel="stylesheet" type="text/css" href="css/event.css">
<link rel="stylesheet" type="text/css" href="css/buttons.css">
<link rel="stylesheet" type="text/css" href="css/background_checkin.css">


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
    }
    if ($auth != 1) {
        header('Location: index.php');
        exit ;
    }
?>





<div class='report_wrapper'>

    <?php 
        echo "<div class='report_header'>Check-In Report For " . $row['e_name'] . "</div>";
    ?>

    <table id="hor-minimalist-b" summary="Event Check-In Report">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Username</th>
                <th scope="col">UserID</th>
                <th scope="col">Attending</th>
                <th scope="col">Show</th>
                <th scope="col">Seats</th>
                <th scope="col">UpdatedOn</th>
            </tr>
        </thead>
        <tbody>
                <?php
                $result = mysql_query("SELECT * FROM tbl_checkin WHERE e_id='" . 
                          $curr_event . "' order by c_ans DESC,c_showtime ASC,c_uname ASC;");

                if (!$result) {
                    die('Invalid query: ' . mysql_error());
                } elseif (mysql_num_rows($result) > 0 ) {
                    $count = 0;
                    while ($row = mysql_fetch_assoc($result)) {
                        $count += 1;
                        echo "<tr>";
                        echo "<td>" . $count . "</td>";
                        echo "<td>" . $row['c_uname'] . "</td>";
                        echo "<td>" . $row['c_uid'] . "</td>";
                        echo "<td>" . $row['c_ans'] . "</td>";
                        echo "<td>" . $row['c_showtime'] . "</td>";
                        echo "<td>" . $row['c_npers'] . "</td>";
                        echo "<td>" . $row['c_date'] . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
        </tbody>
    </table>

    <?php
        $csv_file = "event_report.csv";
        $handle = fopen($csv_file, 'w') or die('Cannot open file:  '.$my_file);
        $line = "Username,UserID,Attending,Show,Seats,UpdatedOn" . "\r\n";
        fwrite($handle, $line);
        mysql_data_seek($result, 0);
        while ($row = mysql_fetch_assoc($result)) {
            $line = $row['c_uname'] ."," . $row['c_uid'] ."," . $row['c_ans'] ."," . 
                    $row['c_showtime'] ."," . $row['c_npers'] ."," .  $row['c_date'] . "\r\n";
            fwrite($handle, $line);
        }
        
        fwrite($handle, "\r\n\r\n");
        $line = '';

        $result = mysql_query("SELECT * FROM tbl_event WHERE e_id='" . $curr_event . "';");
        if (!$result) {
            echo ('Invalid query: ' . mysql_error());
        } elseif (mysql_num_rows($result) > 0 ) {
            $line = "Show,Capacity,Booked,Available" . "\r\n";
            fwrite($handle, $line);
            $row = mysql_fetch_assoc($result);
            $stimes = explode(",", $row['e_showtimes']);
            $smaxs = explode(",", $row['e_showmaxs']);
            for($i = 0; $i < sizeof($stimes); $i++) {
                $qry = "select sum(c_npers) from tbl_checkin where c_ans='yes' and " .
                        "c_showtime='" . $stimes[$i] . "';";
                $result = mysql_query($qry);
                $sum_stimes = mysql_fetch_array($result);
                $savail = $smaxs[$i] - $sum_stimes[0];
                $line = "$stimes[$i],$smaxs[$i],$sum_stimes[0],$savail" . "\r\n";
                //echo $line;
                fwrite($handle, $line);
            }
        }

        fclose($handle);
    ?>

    <div class='download'>
        <a href="download.php" class="button small blue" >Download CSV</a>
    </div>
</div>

</body>
</html>
