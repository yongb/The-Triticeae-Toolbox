<?php

require 'config.php';
include($config['root_dir'] . 'includes/bootstrap_curator.inc');
set_include_path(get_include_path() . PATH_SEPARATOR . '../lib/PHPExcel/Classes');
include($config['root_dir'] . 'lib/PHPExcel/Classes/PHPExcel/IOFactory.php');

connect();
$mysqli = connecti();
loginTest();

$user = loadUser($_SESSION['username']);
$userid = $user['users_uid'];
$username = $user['name'];

//needed for mac compatibility
ini_set('auto_detect_line_endings',true);

ob_start();
authenticate_redirect(array(USER_TYPE_ADMINISTRATOR, USER_TYPE_CURATOR));
ob_end_flush();

new Data_Check($_GET['function']);

/**
 * 
 * Phenotype Experiment Results
 *
 */

class Data_Check
{
  /**
   * Using the class's constructor to decide which action to perform
   * @param unknown_type $function
   */
  public function __construct($function = null) {
    switch($function)
      {
      case 'typeDatabase':
        $this->type_Database(); /* update database */
        break;
      default:
        $this->typeExperimentCheck(); /* intial case*/
        break;
      }
  }

/**
 * check experiment data before loading into database
 */
private function typeExperimentCheck()
        {
                global $config;
                include($config['root_dir'] . 'theme/admin_header.php');
                echo "<h2>CSR Phenotype Data Validation</h2>";
                $this->type_Experiment_Name();
                $footer_div = 1;
        include($config['root_dir'].'theme/footer.php');
        }

public function save_raw_file($wavelength) {
  try {
      $dbh = new PDO('sqlite:../raw/phenotype/foo.db');
      echo "saving raw file<br>\n";
      $stmt = $dbh->prepare("INSERT INTO raw (line_name, value) VALUED (:name, : value)");
  } catch (PDOException $e) {
      print "Error!: " . $e->getMessage() . "<br/>";
  }
}

/**
 * check experiment data before loading into database
 */
 private function type_Experiment_Name() {
   global $mysqli;
   global $userid;
?>
   <script type="text/javascript">
     function update_database(filepath, filename, username, rawdatafile) {
     var url='<?php echo $_SERVER[PHP_SELF];?>?function=typeDatabase&expdata=' + filepath + '&file_name=' + filename + '&user_name=' + username + '&raw_data_file=' + rawdatafile;
     // Opens the url in the same window
     window.open(url, "_self");
   }
   </script>
   <!-- style type="text/css">
     th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
     table {background: none; border-collapse: collapse}
     td {border: 0px solid #eee !important;}
     h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
   </style-->
<?php
  global $config;
  $row = loadUser($_SESSION['username']);
  $username=$row['name'];
  $tmp_dir="uploads/tmpdir_".$username."_".rand();
  if (empty($_FILES['file']['name'][1])) {
    if (empty($_POST['filename1'])) {
      die("missing Raw file\n");
    } else {
      $filename1 = $_POST['filename1'];
      $raw_path = "../raw/phenotype/".$_POST['filename1'];
      $unq_file_name = $filename1;
    }
  } else {
    $filename1 = $_FILES['file']['name'][1];
    $raw_path= "../raw/phenotype/".$_FILES['file']['name'][1];
    if (file_exists($raw_path)) {
      $unique_str = chr(rand(65,80)).chr(rand(65,80)).chr(rand(64,80));
      $unq_file_name = $unique_str . "_" . $filename1;
      $raw_path = str_replace("$filename1","$unq_file_name","$raw_path",$count);
      /* echo "renaming file to $raw_path<br>\n";*/
    } else {
      $unq_file_name = $filename1;
    } 
  }
  $experiment_uid = $_POST['exper_uid'];
  if (preg_match("/[0-9]/",$experiment_uid)) {
  } else {
    die("Error: Must select a trial name<br>\n");
  }
  $replace_flag = $_POST['replace'];
  $start_time = $_POST['start_time'];
  $end_time = $_POST['end_time'];
  if (empty($_FILES['file']['name'][0])) {
    if (empty($_POST['filename0'])) {
      echo "missing Annotation file\n";
    } else {
      $metafile0 = $_POST['filename0'];
    }
  } else {
    $filename0 = $_FILES['file']['name'][0];
  }

  if (empty($_FILES['file']['name'][1]) && ($filename1 == "")) {
    error(1, "No File Upoaded");
    print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
  } else {
    umask(0);
    if (!empty($_FILES['file']['name'][1])) {
         if (move_uploaded_file($_FILES['file']['tmp_name'][1], $raw_path) !== TRUE) {
             echo "<font color=red><b>Oops!</b></font> Your raw data file <b>"
             .$_FILES['file']['name'][1]."</b> was not saved in directory ".$config['root_dir']."raw/ and
             will be lost.  Please <a href='".$config['base_url']."feedback.php'>contact the 
             programmers</a>.<p>";
         } else {
             echo "moved file " . $_FILES['file']['name'][1] . " to $raw_path<br/>";

             //file should be tab separated text file
             if (!preg_match("/\.txt/",$raw_path)) {
               echo "<font color=red>Error: CSR Data File should be a text file with .txt extension<br></font>\n";
               die();
             }

             //check file for readability
             $i = 0;
             if (($reader = fopen($raw_path, "r")) == false) {
               die("error - can not read file $raw_path<br>\n");
             }
             $size = 0;
             $count_plot = 0;
             $error_flag = 0;

             //first line should be trial
             $line= fgets($reader);
             $temp = str_getcsv($line,"\t");
             if ($temp[0] != "Trial") {
               echo "Error - Expected \"Trial\" found \"$temp[0]\"<br>\n";
             }
             $sql = "select trial_code from experiments where experiment_uid = $experiment_uid";
             $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
             $row = mysqli_fetch_array($res);
             $trial_code = $row[0]; 
             if ($trial_code != $temp[1]) {
                 echo "<font color=red>Error: Trial Name in the Data File \"$temp[1]\" does not match the Trial Name selected from the drop-down list<br></font>\n";
                 $error_flag = 1;
                 die();
             }
             //get list of valid plot numbers
             $sql = "select plot from fieldbook where experiment_uid = $experiment_uid";
             $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
             while ($row = mysqli_fetch_array($res)) {
               $plot = $row[0];
               $plot_list[$plot] = 1;
             }
             $count = count($plot_list);
             //echo "found $count plots in fieldbook for experiment $trial_code<br>\n";

             if ($temp[2] != "Date") {
               echo "Error - Expected \"Date\" found \"$temp[2]\"<br>\n";
             }
             $date_pattern = '/\d+\/\d+\/\d+/';
             if (!preg_match($date_pattern, $temp[3])) {
               echo "Error - Bad date format, found $temp[3] should be mm/dd/yy<br>\n";
               $error_flag = 1;
             }
             $csr_date = $temp[3];

             //read in plot and check
             $error = 0;
             if ($line = fgets($reader)) {
               $temp = str_getcsv($line,"\t");
               $count = count($temp);
               if (!preg_match("/Plot/", $temp[0])) {
                 echo "Error - Found \"$temp[0]\", expected \"Plot\" in Data File<br>\n";
               }
               for ($i=1; $i<=$count; $i++) {
                 if(is_numeric($temp[$i])) {
                   $count_plot++;
                   if (isset($plot_list[$temp[$i]])) {
                   } else {
                     echo "<font color=red>Error - plot $temp[$i] not defined in fieldbook for experiment $trial_code</font><br>\n";
                     $error_flag = 1;
                   }
                 } elseif ($temp[$i] == "") {
                 } else {
                   $error_flag = 1;
                   echo "Error - The value of \"$temp[$i]\" is not numeric in Plot line<br>\n";
                 }
               }
             }
             if ($error) {
               echo "Error - Plot line had illegal value<br>\n";
             }
             //read in Start time / Stop time and check
             $start_time = "";
             $end_time = "";
             for ($j=1; $j<=2; $j++){
               if ($line = fgets($reader)) {
                 $temp = str_getcsv($line,"\t");
                 $size = count($temp);
                 if (($j == 1) && (!preg_match("/Start/",$temp[0]))) {
                   $error_flag = 1;
                   echo "Error - Found \"$temp[0]\", expected \"Start time\" in Data File<br>\n";
                 } elseif (($j == 2) && (!preg_match("/Stop/", $temp[0]))) {
                   $error_flag = 1;
                   echo "Error - Found \"$temp[0]\", expected \"Stop time\" in Data File<br>\n";
                 }
                 $time_pattern = '/\d+:\d+:\d+/';
                 $i = 1;
                 while ($i<$size) {
                   if (preg_match($time_pattern, $temp[$i], $matches)) {
                     if (($j == 1) && ($start_time == "")) {	//check for case where start time is not specified in annotation file
                       $start_time = $matches[0];
                     }
                     if (($j == 2) && ($start_time == "")) {	//check for case where start time is empty in both files
                       $start_time = $matches[0];
                     }
                     if ($j == 2) {    //check for case where end time is not specified in annotation file
                       $end_time = $matches[0];
                     }
                   } elseif ($temp[$i] == "") {
                   } else {
                     $error_flag = 1;
                     echo "Error - $temp[0] line had illegal value of \"$temp[$i]\"<br>";
                   }
                   $i++;
                 }
               }
             }
             if ($start_time == "") {
               $error_flag = 1;
               echo "Error: a start time is required in either the annotation file or the data file<br>\n";
             } else {
               echo "Start time from data file = $start_time<br>\n";
             }
             if ($end_time == "") {
               $error_flag = 1;
               echo "Error: a stop time is required in either the annotation file or the data file<br>\n";
             } else {
               echo "Stop time from data file = $end_time<br>\n";
             }

             //read in Integration Time and check
             if ($line = fgets($reader)) {
               $temp = str_getcsv($line,"\t");
               if (!preg_match("/Integration/",$temp[0])) {
                 $error_flag = 1;
                 echo "Error - Found \"$temp[0]\", expected \"Integration Time (ms)\" in Data File<br>\n";
               }
               for ($i=1; $i<=$count_plot; $i++) {
                 if(is_numeric($temp[$i])) {
                 } elseif ($temp[$i] == "") {
                 } else {
                   $error_flag = 1;
                   echo "Error - Integration Time line had illegal value of \"$temp[$i]\"<br>\n";
                 }
               }
             }

             $i = 1;
             echo "verifying csr data file<br>";
             while ($line = fgets($reader)) {
               $size_t = 0;
               $temp = str_getcsv($line,"\t");
               $count = count($temp);
               if (preg_match("/[0-9]/",$line)) {
                 if(is_numeric($temp[0])) {
                 } else {
                   $error_flag = 1;
                   echo "Error - expecting frequency in first column, found \"$temp[$i]\" in line $i<br>\n";
                 }
                 for ($j=1; $j<=$count; $j++) {
                   if(is_numeric($temp[$j])) {
                     $size_t++;
                   } elseif ($temp[$j] == "") {
                   } else {
                     $error_flag = 1;
                     $size_t++;
                     echo "Error - data line $i had illegal value of $temp[$j]<br>\n";
                   }
                 }
                 if ($size_t != $count_plot) {
                   echo "<br>Error - line $i size = $size_t expected = $count_plot<br>\n";
                 } else {
                   $i++;
                 }
                 if ($i % 100 == 0) {
                   echo "finished $i lines<br>";
                   flush();
                 }
               }
             }
             $count_wavl = $i - 1;
             echo "$count_wavl (Wavelengths), $count_plot (Plots)<br>\n";
          
             //save to SQLite
             //$this->save_raw_file($raw_path);   
             fclose($reader);
             echo "<br>\n";
 
             #$objPHPExcel = new PHPExcel();
             #$objPHPExcel->setActiveSheetIndex(0);
             #$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'test');
             #$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
             #$objWriter = new PHPExcel_Writer_Excel5($objPHPExcel);
             #$objWriter->save('/tmp/tht/testfile.xls');
         }
    } else {
      print "using $filename1<br>\n";
    }
  }
  if(!file_exists($tmp_dir) || !is_dir($tmp_dir)) {
      mkdir($tmp_dir, 0777);
  }
  $target_path=$tmp_dir."/";
  if (($_FILES['file']['name'][0] == "") && ($metafile0 == "")) {
     error(1, "No File Uploaded");
     print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
  } else {
    if (!empty($_FILES['file']['name'][0])) {
      $uploadfile=$_FILES['file']['name'][0];
      $rawdatafile = $_FILES['file']['name'][1];
      $raw_path= "../raw/phenotype/".$_FILES['file']['name'][0];
      $uftype=$_FILES['file']['type'][0];
      if (move_uploaded_file($_FILES['file']['tmp_name'][0], $raw_path) !== TRUE) {
        echo "error - could not upload file $uploadfile<br>\n";
      } else {
        echo $_FILES['file']['name'][0] . "  $FileType<br>\n";
      }
      $metafile = $raw_path;
      echo "using $metafile<br>\n";
    } else {
      echo "using $metafile0<br>\n";
      $metafile = "../raw/phenotype/".$metafile0;
      echo "using $metafile<br>\n";
    }
      $FileType = PHPExcel_IOFactory::identify($metafile);
      switch ($FileType) {
        case 'Excel2007':
          break;
        case 'Excel5':
          break;
        case 'CSV':
          break;
        default:
          error(1, "Expecting an Excel file. <br> The type of the uploaded file is ".$FileType);
          print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
          die();
      }

               /* Read the Means file */
               $objPHPExcel = PHPExcel_IOFactory::load($metafile);
               $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
               $i = 1;
               $found = 1;
               while ($found) {
                 $tmp1 = $sheetData[$i]["A"];
                 $tmp2 = $sheetData[$i]["B"];
                 if (preg_match("/[A-Za-z0-9]+/",$tmp1)) {
                   $data[$i] = $tmp1;
                   $value[$i] = $tmp2;
                   $i++;
                 } else {
                   $found = 0;
                 }
               }
               $lines_found = $i - 1;

               $sql = "select trial_code from experiments where experiment_uid = $experiment_uid";
               $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
               $row = mysqli_fetch_array($res);
               if ($row[0] != $value[2]) {
                 echo "<font color=red>Error: Trial Name in the Annotation File \"$value[2]\" does not match the Trial Name selected from the drop-down list<br></font>\n";
                 $error_flag = 1;
                 die();
               }

               $sql = "select radiation_dir_uid from csr_measurement_rd where direction = '$value[3]'";
               $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
               if ($row = mysqli_fetch_array($res)) {
                 $dir_uid = $row[0];
               } else {
                 echo "<font color=red>Error - Upwelling / Downwelling not valid $value[3]</font><br>\n";
                 $error_flag = 1;
               } 
               if ($data[3] != "Upwelling / Downwelling") {
                 echo "expected \"Upwelling \/ Downwelling\" found $data[3]<br>\n";
                 $error_flag = 1;
               }
               if ($data[4] !== "Measurement date time") {
                 echo "expected \"Measurement date\" found $data[4]<br>";
                 $error_flag = 1;
               } else {
                 $meta_date = $value[4];
               }
               //if initial file read from form then check that date matches value in CSR file
               if (!empty($_FILES['file']['name'][1])) { 
                 if ($meta_date !== $csr_date) {
                   echo "Measurement date $value[4] does not match date in CSR data file $csr_date<br>";
                   $error_flag = 1;
                 }
               }
               if ($data[5] != "Growth Stage") {
                 echo "expected \"Growth Stage\" found $data[5]<br>";
                 $error_flag = 1;
               }
               if ($data[6] != "Growth Stage name") {
                 echo "expected \"Growth Stage name\" found $data[6]<br>";
                 $error_flag = 1;
               }
               if (preg_match("/[0-9]/",$value[7])) {
                 $start_time = $value[7];
               }
               if (preg_match("/[0-9]/",$value[8])) {
                 $end_time = $value[8];
               }
               if (preg_match("/[0-9]/",$value[9])) {
                 $int_time = $value[9];
               } else {
                 $int_time = "NULL";
               }
               if ($data[11] != "Spectrometer System") {
                 echo "expected \"Spectormeter System\" found $data[11]<br>";
                 $error_flag = 1;
               } else {
                 $sql = "select system_uid from csr_system where system_name = '$value[11]'";
                 $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
                 if ($row = mysqli_fetch_array($res)) {
                   $spect_sys_uid = $row[0];
                 } else {
                   $spect_sys_uid = 99999999;
                   echo "<font color=red>Error - Spectrometer System record $value[11] not found<br></font>\n";
                   echo "$sql<br>\n";
                   $error_flag = 1;
                 }
               }

               //check for unique record
               //multiple raw files are allowed if they use a different time

               $sql = "select measurement_uid from csr_measurement where experiment_uid = $experiment_uid and spect_sys_uid  = $spect_sys_uid and
                       measure_date = str_to_date('$value[4]','%m/%d/%Y') and start_time = str_to_date('$start_time','%h:%i')";
               $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
               $row = mysqli_fetch_array($res);
               if (mysqli_num_rows($res) == 0) {
                 $new_record = 1;
                 $measurement_uid = NULL;
               } else {
                 $measurement_uid = $row[0];
                 if (!$replace_flag && ($error_flag == 0)) {
                   echo "<font color=red>Warning - record with trial name = $value[2], Upwelling/Downwelling = $value[3], and Measurement data time = $value[4] $start_time already exist. ";
                   echo "Do you want to overwrite?</font>";
                   ?>
                   <form action="curator_data/input_csr_exper_check.php" method="post" enctype="multipart/form-data">
                   <input id="exper_uid" type="hidden" name="exper_uid" value="<?php echo $experiment_uid; ?>">
                   <input id="replace" type="hidden" name="replace" value="Yes">
                   <input id="filename0" type="hidden" name="filename0" value="<?php echo $filename0; ?>">
                   <input id="filename1" type="hidden" name="filename1" value="<?php echo $unq_file_name; ?>">
                   <input id="start_time" type="hidden" name="start_time" value="<?php echo $start_time; ?>">
                   <input id="end_time" type="hidden" name="end_time" value="<?php echo $end_time; ?>">
                   <input type="submit" value="Yes">
                   </form>
                   <?php
                   $error_flag = 1;
                 } elseif ($error_flag > 0) {
                   echo "<font color=red>Error - upload rejected because of errors</font><br>\n";
                 }
                 $new_record = 0;
               }

               if ($error_flag == 0) {
                 if ($new_record) {
                   $sql = "insert into csr_measurement (experiment_uid, radiation_dir_uid, measure_date, growth_stage, growth_stage_name, start_time, end_time, integration_time, weather, spect_sys_uid, num_measurements, height_from_canopy, incident_adj, comments, raw_file_name) values ($experiment_uid,$dir_uid,str_to_date('$value[4]','%m/%d/%Y'),'$value[5]','$value[6]','$start_time','$end_time',$int_time,'$value[10]',$spect_sys_uid,'$value[12]','$value[13]','$value[14]','$value[15]','$unq_file_name')";
                   $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
                   echo "saved to database<br>\n";
                   //echo "$sql<br>\n";
                 } else {
                   $sql = "delete from csr_measurement where measurement_uid  = $measurement_uid";
                   $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
                   echo "deleted old entries from database where measurement_uid = $measurement_uid<br>\n";
                   $sql = "insert into csr_measurement (experiment_uid, radiation_dir_uid, measure_date, growth_stage, growth_stage_name, start_time, end_time, integration_time, weather, spect_sys_uid, num_measurements, height_from_canopy, incident_adj, comments, raw_file_name) values ($experiment_uid,$dir_uid,str_to_date('$value[4]','%m/%d/%Y'),'$value[5]','$value[6]','$start_time','$end_time',$int_time,'$value[10]',$spect_sys_uid,'$value[12]','$value[13]','$value[14]','$value[15]','$unq_file_name')";
                   $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
                   echo "saved to database<br>\n";
                 }
                 $sql = "insert into input_file_log (file_name, users_name, created_on)
                        VALUES('$unq_file_name', '$username', NOW())";
                 $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
                 $sql = "select measurement_uid from csr_measurement where experiment_uid = $experiment_uid and spect_sys_uid  = $spect_sys_uid and measure_date = str_to_date('$value[4]','%m/%d/%Y')";
                 $res = mysqli_query($mysqli,$sql) or die(mysqli_error($mysqli) . "<br>$sql");
                 $row = mysqli_fetch_array($res);
                 echo "<br>Check results by viewing <a href=display_csr_exp.php?uid=$row[0]>data stored in database</a><br>";
               } else {
                 echo "<br><font color=red>data not saved to database</font><br>\n";
               }
               echo "<br>Data read from import file<table>\n";
               for ($i=1; $i<=$lines_found; $i++) {
                 echo "<tr><td>$i<td>$data[$i]<td>$value[$i]\n";
               }
               echo "</table>";
    }
  //}

}

}
