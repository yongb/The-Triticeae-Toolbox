<?php
//**********************************************  
// Marker importer
//
//
// 11/09/2011  JLee   Fix problem with empty lines in SNP file
// 10/25/2011  JLee   Ignore "cut" portion in annotation input file 
// 08/02/2011  JLee   Allow for empty synonyms and annotations
//
// Author: John Lee         6/15/2011
//**********************************************  
require 'config.php';
/*
 * Logged in page initialization
 */
include($config['root_dir'] . 'includes/bootstrap_curator.inc');
include($config['root_dir'] . 'curator_data/lineuid.php');

connect();
loginTest();

//needed for mac compatibility
ini_set('auto_detect_line_endings',true);

/* ******************************* */
$row = loadUser($_SESSION['username']);

////////////////////////////////////////////////////////////////////////////////
ob_start();

authenticate_redirect(array(USER_TYPE_ADMINISTRATOR, USER_TYPE_CURATOR));
ob_end_flush();

new Markers_Check($_GET['function']);

class Markers_Check {

    private $delimiter = "\t";
    private $storageArr = array (array());
	// Using the class's constructor to decide which action to perform
//**************************************************************
 	public function __construct($function = null) {	
		switch($function) {
			case 'typeDatabaseAnnot':
				$this->type_DatabaseAnnot(); /* update Marker Info */
				break;
				
			case 'typeDatabaseSNP':
				$this->type_DatabaseSNP(); /* update Allele SNP */
				break;

            default:
				$this->typeMarkersCheck(); /* intial case*/
				break;
			
		}	
	}

 //**************************************************************
    private function typeMarkersCheck() {
		global $config;
		include($config['root_dir'] . 'theme/admin_header.php');

		echo "<h2> Enter/Update Markers: Validation</h2>"; 

        if ($_FILES['file']['name'][0] != "") 	
            $this->type_MarkersAnnot();
        elseif ( $_FILES['file']['name'][1] != "")
            $this->type_MarkersSNP();
        else { 
            error(1, "No File Uploaded");
            print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
        }
		$footer_div = 1;
        include($config['root_dir'].'theme/footer.php');
	}
	
//**************************************************************
 	private function type_MarkersAnnot() {
	?>
	<script type="text/javascript">
	
        function update_databaseAnnot(filepath, filename, username) {
            var url='<?php echo $_SERVER[PHP_SELF];?>?function=typeDatabaseAnnot&linedata=' + filepath + '&file_name=' + filename + '&user_name=' + username;
	
			// Opens the url in the same window
	   		window.open(url, "_self");
        }
	</script>
	
	<style type="text/css">
		th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
		table {background: none; border-collapse: collapse}
		td {border: 0px solid #eee !important;}
		h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
	</style>
		
	<style type="text/css">
        table.marker {background: none; border-collapse: collapse}
        th.marker { background: #5b53a6; color: #fff; padding: 5px 0; border: 0; }
        td.marker { padding: 5px 0; border: 0 !important; }
    </style>
<?php
        $error_flag = 0;
        $row = loadUser($_SESSION['username']);
		ini_set("memory_limit","24M");
		$username=$row['name'];
		$tmp_dir="./uploads/tmpdir_".$username."_".rand();
        //	$raw_path= "rawdata/".$_FILES['file']['name'][1];
        //	copy($_FILES['file']['tmp_name'][1], $raw_path);
        umask(0);
        
        if(!file_exists($tmp_dir) || !is_dir($tmp_dir)) {
            mkdir($tmp_dir, 0777);
        }

        $target_path=$tmp_dir."/";
 	
 		$uploadfile =$_FILES['file']['name'][0];
             
        $uftype=$_FILES['file']['type'][0];
        if (strpos($uploadfile, ".txt") === FALSE) {
            error(1, "Expecting an tab-delimited text file. <br> The type of the uploaded file is ".$uftype);
            print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
        }
        else {
		    if(move_uploaded_file($_FILES['file']['tmp_name'][0], $target_path.$uploadfile)) {
            /* start reading the input */
            	$annotfile = $target_path.$uploadfile;
                //echo "Annotate file - " . $annotfile . "<br>";
                /* Read the annotation file */
                if (($reader = fopen($annotfile, "r")) == FALSE) {
                    error(1, "Unable to access file.");
                    exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
                }
                // Check first line for header information
                if (($line = fgets($reader)) == FALSE) {
                    error(1, "Unable to locate header names on first line of file.");
                    exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
                }     
                $header = str_getcsv($line,"\t");
                 
                // Set up header column; all columns are required
                for ($x = 0; $x < count($header); $x++) {
                    switch ($header[$x] ) {
                        case 'Name':
                            $nameIdx = $x;
                            break;
                        case 'Marker_type':
                            $markerTypeIdx = $x;
                            break;
                        case 'Synonym_type':
                            $synonymTypeIdx = $x;
                            break;
                        case 'Annotation_type':
                            $annotationTypeIdx= $x;
                            break;
                        case 'Synonym':
                            $synonymIdx = $x;
                            break;
                        case 'Annotation':
                            $annotationIdx = $x;
                            break;
                    }
                }
  
                // Check if a required col is missing
                if ((!is_numeric($nameIdx)) || (!is_numeric($markerTypeIdx)) || (!is_numeric($synonymIdx)) ||
                    (!is_numeric($synonymTypeIdx)) || (!is_numeric($annotationIdx)) || (!is_numeric($annotationTypeIdx))) {
                    echo "ERROR: Missing One of these required Columns. Please correct it and upload again: <br> Name - ".$nameIdx.
                            "<br>"." Marker_type - ".$markerTypeIdx."<br>"." Synonym - ". $synonymIdx.
                            "<br>"." Synonym_type - ".$synonymTypeIdx."<br>"."Annotation - ".$annotationIdx.
                            "<br>"." Annotation_type - ". $annotationTypeIdx ."<br>";
                    exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
                }
                  
                // Store header
                $i = 0;
                foreach ($header as $value)  {                     
                    $storageArr[0][$i++] = $value;   
                }
                // Store individual records
                $i = 1;
                while(($line = fgets($reader)) !== FALSE) { 
                    if (feof($reader)) {
                        break;
                    }
                    
                    if ((stripos($line, '- cut -') > 0 )) {
                        $error_flag = 0; 
                        break;
                    }
                                        
                    if (trim($line) == '') {
                        continue;
                    }
		    
                    if ($i  > 50) {
                          break;
                    }
                    $j = 0;
                    $data = str_getcsv($line,"\t");
                    
                    //Check for junk line
                    if (count($data) != 6) {
                        echo "ERROR DETECT: Line does not contain 6 columns.". "<br/>". $line . "<br/>" ;
                        exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
                    }    
                                            
                    foreach ($data as $value)  {
                        //echo $value."<br>";
                        if (($j == $nameIdx) && ($value == "")){
                            $error_flag = 1;
                        }
                        $storageArr[$i][$j++] = $value;   
                    }
                    $i ++;
                }  
                unset ($value);
                fclose($reader);   

                if ($error_flag > 0) {
                    echo "ERROR DETECT: The 'Name' field cannot be blank"."<br/>";
                    exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
                } else {
                    // display input data into table for validation 
                    echo "<h3>Here is a sample of the first 50 lines of data from the uploaded Annotation File</h3>";
                    echo "<table >";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th >" . $storageArr[0][$nameIdx] . "</th>";
                    echo "<th >" . $storageArr[0][$markerTypeIdx] . "</th>";
                    echo "<th >" . $storageArr[0][$synonymIdx] . "</th>";
                    echo "<th >" . $storageArr[0][$synonymTypeIdx] . "</th>";
                    echo "<th >" . $storageArr[0][$annotationIdx] . "</th>";
                    echo "<th >" . $storageArr[0][$annotationTypeIdx] . "</th>";
                    echo "</tr>"."<br/>";
                    echo "<thead>"."<br/>";
                    ?>                   
                    <tbody style="padding: 0; height: 200px; width: 4000px; overflow: scroll; ">	
                    <?php
                    for ($i = 1; $i <= count($storageArr) ; $i++)  {
                        //Extract data
                    ?>
                        <tr>
                        <td >
                        <?php $newtext = wordwrap($storageArr[$i][$nameIdx], 10, "<br>", true);  echo $newtext ?>
                        </td> 
                        <td >
                        <?php $newtext = wordwrap($storageArr[$i][$markerTypeIdx], 20, "<br>", true); echo $newtext ?>
                        </td>
                        <td>
                        <?php $newtext = wordwrap($storageArr[$i][$synonymIdx], 20, "<br>", true); echo $newtext ?>
                        </td> 
                        <td >
                        <?php $newtext = wordwrap($storageArr[$i][$synonymTypeIdx], 20, "<br>", true); echo $newtext ?>
                        </td> 
                        <td >
                        <?php $newtext = wordwrap($storageArr[$i][$annotationIdx], 30, "<br>", true); echo $newtext ?>
                        </td> 
                        <td >
                        <?php $newtext = wordwrap($storageArr[$i][$annotationTypeIdx], 20, "<br>", true); echo $newtext ?>
                        </td> 
                        </tr>
                    <?php
                    }/* end of for loop */
                    ?>
                    </tbody>
                    </table>
                    <br>
                    <input type="Button" value="Accept" 
                    onclick="javascript: update_databaseAnnot('<?php echo $annotfile?>','<?php echo $uploadfile?>','<?php echo $username?> ')"/>
                    <input type="Button" value="Cancel" onclick="history.go(-1); return;"/>
                    <?php
                }
		    } // end of if(move_uploaded_file())
		    else {
		      echo "<b>Error</b>: Couldn't save file in curator_data/uploads/ directory.<p>";
		      echo "<input type='Button' value='Cancel' onclick='history.go(-1); return;'/>";
		    }
	}
    } /* end of MarkersAnnot function*/

//**************************************************************
  	private function type_MarkersSNP() {
	?>
	<script type="text/javascript">
	
        function update_databaseSNP(filepath, filename, username, filetype)  {
            var url='<?php echo $_SERVER[PHP_SELF];?>?function=typeDatabaseSNP&linedata=' + filepath + '&file_name=' + filename + '&user_name=' + username + '&file_type=' + filetype;
			// Opens the url in the same window
			window.open(url, "_self");
        }
	</script>
	
	<style type="text/css">
		th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
		table {background: none; border-collapse: collapse}
		td {border: 0px solid #eee !important;}
		h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
	</style>
		
	<style type="text/css">
        table.marker {background: none; border-collapse: collapse}
        th.marker { background: #5b53a6; color: #fff; padding: 5px 0; border: 0; }
        td.marker { padding: 5px 0; border: 0 !important; }
    </style>
<?php

        $error_flag = 0;
        $row = loadUser($_SESSION['username']);
		ini_set("memory_limit","24M");
		$username=$row['name'];
		$tmp_dir="./uploads/tmpdir_".$username."_".rand();
        $infile = "";
        //	$raw_path= "rawdata/".$_FILES['file']['name'][1];
        //	copy($_FILES['file']['tmp_name'][1], $raw_path);
        umask(0);
	
        if(!file_exists($tmp_dir) || !is_dir($tmp_dir)) {
            mkdir($tmp_dir, 0777);
        }

        $target_path=$tmp_dir."/";
        $uploadfile = $_FILES['file']['name'][1];
        $ext = substr(strrchr($uploadfile, '.'), 1);
        
        $uftype = $_FILES['file']['type'][1];
        //Read header to check if Golden Gate or Infinium
        if(move_uploaded_file($_FILES['file']['tmp_name'][1], $target_path.$uploadfile))    {
          $infile = $target_path.$uploadfile;
          if (($reader = fopen($infile, "r")) == FALSE) {
            error(1, "Unable to access file.");
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
          }
          $fileFormat = 0;
          while(!feof($reader)) {
            $line = fgets($reader);
            if (stripos($line, 'Golden Gate') !== false) {
              $fileFormat = 1;
              echo "$line\n";
            } elseif (stripos($line, 'Infinium HD') !== false) {
              echo "$line\n";
              $fileFormat = 2;
            } elseif (stripos($line, 'DArT') !== false) {
              echo "$line\n";
              $fileFormat = 3;
            }
          }
          if ($fileFormat == 0) {
            echo "Warning, using generic file format.<br>\n";
            echo "Generic format is for ACTG base calls. Use Illumina format for AB base calls\n";
          }
          fclose($reader);
        } else {
          error(1, "Unable to upload file to tempory location.");
          exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }
        //Illumina format
        if ($fileFormat == 1) {
            $infile = $target_path.$uploadfile;
            // Convert it to generic format
            $cmd = "perl AB_to_ATCG.pl \"$infile\"";
            //echo "Cmd - " . $cmd . "<br>";
            exec($cmd);
            $infile = $target_path.$uploadfile.".txt";
	    if (!file_exists($infile)) {
		  error(1, "Conversion of .opa file failed.");
		  exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
	    }
        } elseif ($fileFormat == 2) {
            $infile = $target_path.$uploadfile;
            // Convert it to generic format
            $cmd = "perl AB_to_ATCG_Infinium.pl \"$infile\"";
            //echo "Cmd - " . $cmd . "<br>";
            exec($cmd);
            $infile = $target_path.$uploadfile.".txt";
            if (!file_exists($infile)) {
                  error(1, "Conversion of manifest file failed.");
                  exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
            }   
   
        // DArT format
        } elseif ($fileFormat == 3) {
            $infile = $target_path.$uploadfile;

        // Generic SNP format
        } else {
                /* start reading the input */
                $infile = $target_path.$uploadfile;
        }    

        /* Read the file */
        if (($reader = fopen($infile, "r")) == FALSE) {
            error(1, "Unable to access file.");
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }
        
        //Advance to data header area
        while(!feof($reader))  {
            $line = fgets($reader);
            if (stripos($line, 'marker_name') !== false) break;
        }
        
        if (feof($reader)) {
            error(1, "Unable to locate genotype header line.");
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }
        $header = str_getcsv($line,",");
         
        // Set up header column; all columns are required
        $nameIdx = implode(find("marker_name", $header),"");
        $alleleAIdx = implode(find("A_allele", $header),"");
        $alleleBIdx = implode(find("B_allele", $header),"");
        $sequenceIdx = implode(find("sequence", $header),"");
 
        // Check if a required col is missing
        if ($fileFormat == 3) {  //only require name
            if ($nameIdx == "") {
              echo "ERROR: Missing One of these required Columns. Please correct it and upload again: <br> marker_name - ".$nameIdx.
                    "<br>"." A_allele - ".$alleleAIdx."<br>"." B_allele - ". $alleleBIdx.
                    "<br>"." sequence - ".$sequenceIdx."<br>";
              exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
            }
        } else {
          if (($nameIdx == "")||($alleleAIdx == "")||($alleleBIdx == "")|| ($sequenceIdx == "")) {
            echo "ERROR: Missing One of these required Columns. Please correct it and upload again: <br> marker_name - ".$nameIdx.
                    "<br>"." A_allele - ".$alleleAIdx."<br>"." B_allele - ". $alleleBIdx.
                    "<br>"." sequence - ".$sequenceIdx."<br>";
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
          }
        }
          
        // Store header
        $i = 0;
        foreach ($header as $value)  {                     
            $storageArr[0][$i++] = $value;   
        }
        // Store individual records
        $i = 1;
        while (($line = fgets($reader)) !== FALSE) { 
            if (strlen($line) < 2) continue;
            
            if (empty($line)) {
                continue;
            }
            if ($i  > 50) {
                break;
            }
            $j = 0;
            $data = str_getcsv($line,",");
            
            //Check for junk line
            if (count($data) != 4) {
                echo "ERROR DETECT: Line does not contain 4 columns.". "<br/>". $line . "<br/>" ;
                exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
            } 
                                    
            foreach ($data as $value)  {
                //echo $value."<br>";
                if (($j == $nameIdx) && ($value == "")){
                    $error_flag = 1;
                }
                $storageArr[$i][$j++] = $value;   
            }
            if (feof($reader)) {
                break;
            } else {
                $i ++;
            }
        }  
        unset ($value);
        fclose($reader);   

        if ($error_flag > 0)  {
            echo "ERROR DETECT: The 'marker_name' field cannot be blank"."<br/>";
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        } 	else {
            // display input data into table for validation 
            echo "<h3>Here is a sample of the first 50 lines of data from the processed file</h3>";
            echo "<table >";
            echo "<thead>";
            echo "<tr>";
            echo "<th >" . $storageArr[0][$nameIdx] . "</th>";
            echo "<th >" . $storageArr[0][$alleleAIdx] . "</th>";
            echo "<th >" . $storageArr[0][$alleleBIdx] . "</th>";
            echo "<th >" . $storageArr[0][$sequenceIdx] . "</th>";
            echo "</tr>"."<br/>";
            echo "</thead>"."<br/>";
            ?>                   
            <tbody style="padding: 0; height: 200px; width: 4000px;  overflow: scroll;">	
            <?php
            for ($i = 1; $i <= count($storageArr) ; $i++)  {
                //Extract data
            ?>
                <tr>
                <td >
                <?php $newtext = wordwrap($storageArr[$i][$nameIdx], 20, "<br>", true);  echo $newtext ?>
                </td> 
                <td >
                <?php $newtext = wordwrap($storageArr[$i][$alleleAIdx], 10, "<br>", true); echo $newtext ?>
                </td>
                <td>
                <?php $newtext = wordwrap($storageArr[$i][$alleleBIdx], 10, "<br>", true); echo $newtext ?>
                </td> 
                <td >
                <?php $newtext = wordwrap($storageArr[$i][$sequenceIdx], 50, "<br>", true); echo $newtext ?>
                </td> 
                </tr>
            <?php
            }/* end of for loop */
            ?>
            </tbody>
            </table>
            <br>
            <input type="Button" value="Accept" 
            onclick="javascript: update_databaseSNP('<?php echo $infile?>','<?php echo $uploadfile?>','<?php echo $username?>','<?php echo $fileFormat?>')"/>
            <input type="Button" value="Cancel" onclick="history.go(-1); return;"/>
            <?php
        }
    } /* end of MarkersSNP function*/
 

//**************************************************************
     private function type_DatabaseAnnot() {
 
        global $config;
        include($config['root_dir'] . 'theme/admin_header.php');

        //echo "You are in DB portion of annotation import.<br>";
 
       $datafile = $_GET['linedata'];
       $filename = $_GET['file_name'];
       $username = $_GET['user_name'];
        
        //echo "datafile = ".  $datafile  . "<br>";
        //echo "filename = " . $filename . "<br>";
        //echo "username = " . $username . "<br>";
        //exit(0);
        
        $mTypeHash = array ();
        $mAnnotTypeHash = array ();
        $mSynmTypeHash = array ();
        
        $linkID = connect();  
        
        // Setup hash table for the various type lookup
        $sql = "SELECT marker_type_uid, marker_type_name 
            FROM marker_types";
        $res = mysql_query($sql) or die("Database Error: Marker types lookup - ".mysql_error() ."<br>".$sql);
        while ($row = mysql_fetch_assoc($res)) {
           $tempStr =  strtolower($row['marker_type_name']);
           $mTypeHash[$tempStr] =  $row['marker_type_uid'];     
        }
        
        //print_r($mTypeHash);
        //echo "<br>";
        
        $sql = "SELECT marker_annotation_type_uid, name_annotation 
            FROM marker_annotation_types";
        $res = mysql_query($sql) or die("Database Error: Marker annotation types lookup - ".mysql_error() ."<br>".$sql);
        while ($row = mysql_fetch_assoc($res)) {
            $tempStr =  strtolower($row['name_annotation']);
            $mAnnotTypeHash[$tempStr] = $row['marker_annotation_type_uid'];     
        }
        //print_r($mAnnotTypeHash);
        //echo "<br>";
         
        $sql = "SELECT marker_synonym_type_uid, name 
            FROM marker_synonym_types";
        $res = mysql_query($sql) or die("Database Error: Marker synonym types lookup - ".mysql_error() ."<br>".$sql);
        while ($row = mysql_fetch_assoc($res)) {
            $tempStr =  strtolower($row['name']);
            $mSynmTypeHash[$tempStr] = $row['marker_synonym_type_uid'];     
        }
        //print_r( $mSynmTypeHash);
        //echo "<br>";

        //exit(0);
         
        if (($reader = fopen($datafile, "r")) == FALSE) {
            error(1, "Unable to access file.");
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }
        // Check first line for header information
        if (($line = fgets($reader)) == FALSE) {
            error(1, "Unable to locate header names on first line of file.");
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }     
        $header = str_getcsv($line,"\t");
                    
        // Set up header column; all columns are required
        for ($x = 0; $x < count($header); $x++)  {
            switch ($header[$x] ) {
                case 'Name':
                    $nameIdx = $x;
                    break;
                case 'Marker_type':
                    $markerTypeIdx = $x;
                    break;
                case 'Synonym_type':
                    $synonymTypeIdx = $x;
                    break;
                case 'Annotation_type':
                    $annotationTypeIdx= $x;
                    break;
                case 'Synonym':
                    $synonymIdx = $x;
                    break;
                case 'Annotation':
                    $annotationIdx = $x;
                    break;
            }
        } 
        // Store individual records
        $i = 1;
        while (($line = fgets($reader)) !== FALSE) { 
            if (feof($reader)) {
                  break;
            }
             
	    	if ( trim($line) == '') {
            	continue;
            }
            
            if ((stripos($line, '- cut -') > 0 )) {
                break;
            }
            $j = 0;
            $data = str_getcsv($line,"\t");
                        
            //Check for junk line
            if (count($data) != 6)  {
                echo "ERROR DETECT: Invalid number of columns in line $i.<br/>";
				echo "The offending row contains:<br>\"$line\"<br>";
                print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-2); return;\"><br>";
				exit;
            }     
                                                
            foreach ($data as $value) {
                //echo $value."<br>";
                $storageArr[$i][$j++] = trim($value);   
            }
        
	    	$lastmarker = $data[0];
	    	$lastline = $i-1;
	    	$i++;
        }  
        unset ($value);
        fclose($reader);   
        
        $curMarker = '';
        $markerUid = 0;

        for ($i = 1; $i <= count($storageArr) ; $i++)  {

            $marker = $storageArr[$i][$nameIdx];
            $markerType = $storageArr[$i][$markerTypeIdx];
            $synonym = $storageArr[$i][$synonymIdx];
            $synonymType = $storageArr[$i][$synonymTypeIdx];
            $annotation = $storageArr[$i][$annotationIdx];
            $annotationType = $storageArr[$i][$annotationTypeIdx];
            
	    	if ($marker == "") continue;
            // handle repeating marker entries
            if (strcmp($marker, $curMarker) == 0)  {
                
                $doMarker = 0;
                if (empty($synonym) )
                    $doSynonym = 0;
                else 
                    $doSynonym = 1;
                    
                if (empty($annotation)) 
                    $doAnnotation = 0;
                else 
                    $doAnnotation = 1;

            } else {
                $curMarker = $marker;
                $doMarker = 1;
                if (empty($annotation)) 
                    $doAnnotation = 0;
                else 
                    $doAnnotation = 1;

                if (empty($synonym))
                    $doSynonym = 0;
                else 
                    $doSynonym = 1;
            }
            
            if ($doMarker == 1 ) {
                $tmp = strtolower($markerType);
                $markerTypeID = $mTypeHash[$tmp];
                
                if (empty($markerTypeID)) {
                    echo "ERROR DETECT in marker type field:'  ". $markerType ."' has not been defined in the database.<br/> Please check your input file and resubmit it. <br><br><br>";
                    exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-2); return;\">");
                }
                
                //echo "Marker type - ".$markerType . " value = " . $markerTypeID . "<br>";  
                //exit(0);
                //Check to see if marker already exists
                $sql = "SELECT marker_uid
                        FROM markers
                        WHERE marker_name = '$marker'";
                $res = mysql_query($sql) or die("Database Error: marker name lookup - ".mysql_error() ."<br>".$sql);
                $rdata = mysql_fetch_assoc($res);
                $m_uid=$rdata['marker_uid'];
                // Check synomyn
                if (empty($m_uid)) {
                    $sql = "SELECT marker_uid
                            FROM marker_synonyms
                            WHERE value = '$marker'";
                    $res = mysql_query($sql) or die("Database Error: marker synonym name lookup - ".mysql_error() ."<br>".$sql);
                    $rdata = mysql_fetch_assoc($res);
                    $m_uid=$rdata['marker_uid'];
                }
                // if no existing name or synonym 
                if (empty($m_uid)) {
                    $sql = "INSERT INTO markers (marker_type_uid, marker_name, updated_on, created_on)
                            VALUES ($markerTypeID,  '$curMarker', NOW(),  NOW())"; 
                    $res = mysql_query($sql) or die("Database Error: marker insert - ". mysql_error() ."<br>".$sql);
                    $sql = "SELECT marker_uid
                        FROM markers
                        WHERE marker_name = '$marker'";
                    $res = mysql_query($sql) or die("Database Error: marker uid lookup - ".mysql_error() ."<br>".$sql);
                    $rdata = mysql_fetch_assoc($res);
                    $markerUid = $rdata['marker_uid'];
                } else {
                    $sql = "UPDATE markers SET marker_type_uid = '$markerTypeID', updated_on = NOW()
                        WHERE marker_uid = '$m_uid'"; 
                    $res = mysql_query($sql) or die("Database Error: marker update - ". mysql_error() ."<br>".$sql);
                    $markerUid = $m_uid;
                }
            }
            
            if ($doSynonym == 1) {
                $tmp = strtolower($synonymType);
                $synonymTypeID = $mSynmTypeHash[$tmp];
                //echo "Synonym type - ".$synonymType . " value = " . $synonymTypeID . "<br>";  
                if (empty($synonymTypeID)) {
                    echo "ERROR DETECT in synonym type field: ". $synonymType . " has not been defined in the database.<br/> Please check your input file in " . $marker. " and resubmit it. <br><br><br>";
                    exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
                }
                //Check to see if synonym name already exists
                $sql = "SELECT marker_synonym_uid
                        FROM marker_synonyms
                        WHERE value = '$synonym'";
                $res = mysql_query($sql) or die("Database Error: marker synonym name lookup - ".mysql_error() ."<br>".$sql);
                $rdata = mysql_fetch_assoc($res);
                $mSynonym_uid=$rdata['marker_synonym_uid'];
                
                if (empty($mSynonym_uid)) {
                    $sql = "INSERT INTO marker_synonyms (marker_uid, marker_synonym_type_uid, value, updated_on)
                            VALUES ($markerUid, $synonymTypeID, '$synonym', NOW())"; 
                    $res = mysql_query($sql) or die("Database Error: marker synonym insert - ". mysql_error(). "<br>".$sql);
                } else {
                    $sql = "UPDATE marker_synonyms SET marker_uid = '$markerUid', 
                        marker_synonym_type_uid = '$synonymTypeID', updated_on = NOW()
                        WHERE marker_synonym_uid = '$mSynonym_uid'"; 
                    $res = mysql_query($sql) or die("Database Error: marker synonym update - ". mysql_error() ."<br>".$sql);
                }
            }
            
            if ($doAnnotation == 1) {
                $tmp = strtolower($annotationType);
                $annotTypeID = $mAnnotTypeHash[$tmp];
                if (empty($annotTypeID)) {
                    echo "ERROR DETECT in annotation field: ". $annotationType . " has not been defined in the database.<br/> Please check your input file and resubmit it. <br><br><br>";
                    exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
                }
                
                //Check to see if annot value already exists
                // 
                $pos = stripos($annotationType, 'description');
                if ($pos > 0) {
                    $sql = "SELECT marker_annotation_uid
                            FROM marker_annotations
                            WHERE marker_uid = '$markerUid' AND marker_annotation_type_uid = '$annotTypeID'";
                    $res = mysql_query($sql) or die("Database Error: marker annotation lookup - ".mysql_error() ."<br>".$sql);
                    $rdata = mysql_fetch_assoc($res);
                } else {
                    $sql = "SELECT marker_annotation_uid
                            FROM marker_annotations
                            WHERE value = '$annotation' AND marker_uid = '$markerUid' AND marker_annotation_type_uid = '$annotTypeID'";
                    $res = mysql_query($sql) or die("Database Error: marker annotation lookup - ".mysql_error() ."<br>".$sql);
                    $rdata = mysql_fetch_assoc($res);
                }
                $mAnnot_uid = $rdata['marker_annotation_uid'];
                
                if (empty($mAnnot_uid)) {
                    $sql = "INSERT INTO marker_annotations (marker_uid, marker_annotation_type_uid, value, updated_on, created_on)
                            VALUES ($markerUid, $annotTypeID, '$annotation', NOW(), NOW())"; 
                    $res = mysql_query($sql) or die("Database Error: marker annotation insert - ". mysql_error() ."<br>".$sql);
                } else {
                    $sql = "UPDATE marker_annotations SET marker_uid = '$markerUid', 
                        marker_annotation_type_uid = '$annotTypeID', value = '$annotation', updated_on = NOW()
                        WHERE marker_annotation_uid = '$mAnnot_uid'"; 
                    $res = mysql_query($sql) or die("Database Error: marker annotation update - ". mysql_error() ."<br>".$sql);
                }
            }
        }
        echo " <b>The Data is inserted/updated successfully </b><br>";
    	echo "$lastline lines read, last marker = $lastmarker.<br>";
    	echo "Size of storageArr = ".count($storageArr);
        echo "<br/><br/>";
?>
        <a href="./curator_data/markers_upload.php"> Go Back To Main Page </a>
    <?php
        $sql = "SELECT input_file_log_uid from input_file_log 
            WHERE file_name = '$filename'";
        $res = mysql_query($sql) or die("Database Error: input_file lookup  - ". mysql_error() ."<br>".$sql);
        $rdata = mysql_fetch_assoc($res);
        $input_uid = $rdata['input_file_log_uid'];
        
         if (empty($input_uid)) {
            $sql = "INSERT INTO input_file_log (file_name,users_name, created_on)
                VALUES('$filename', '$username', NOW())";
		} else {
            $sql = "UPDATE input_file_log SET users_name = '$username', created_on = NOW()
                 WHERE input_file_log_uid = '$input_uid'"; 
        }
        $lin_table = mysql_query($sql) or die("Database Error: Log record insertion failed - ". mysql_error() ."<br>".$sql);
        $footer_div = 1;
        include($config['root_dir'].'theme/footer.php');
    } /* end of function type_databaseAnnot */
 
 //**************************************************************
    private function type_DatabaseSNP() {

        global $config;
        include($config['root_dir'] . 'theme/admin_header.php');
        
        //echo "You are in DB portion of SNP import." . "<br>";
         
        $datafile = $_GET['linedata'];
        $filename = $_GET['file_name'];
        $username = $_GET['user_name'];
        $fileFormat = $_GET['file_type'];
        
        //echo "datafile = ".  $datafile  . "<br>";
        //echo "filename = " . $filename . "<br>";
        //echo "username = " . $username . "<br>";
        //exit(0);

        if (($reader = fopen($datafile, "r")) == FALSE) {
            error(1, "Unable to access file.");
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
       }
        //Advance to data header area
        while(!feof($reader)) {
            $line = fgets($reader);
            if (stripos($line, 'marker_name') !== false) break;    
        }
        
        if (feof($reader)) {
            error(1, "Unable to locate genotype header line.");
            exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
        }
        $header = str_getcsv($line,",");
         
        // Set up header column; all columns are required
        $nameIdx = implode(find("marker_name", $header),"");
        $alleleAIdx = implode(find("A_allele", $header),"");
        $alleleBIdx = implode(find("B_allele", $header),"");
        $sequenceIdx = implode(find("sequence", $header),"");
  
         // Store individual records
        $i = 1;
        while(($line = fgets($reader)) !== FALSE) { 
            if ( trim($line) == '') {
                continue;
            }
            
            $j = 0;
            $data = str_getcsv($line,",");
                        
            //Check for junk line
            if (count($data) != 4 )  break;   
                                                
            foreach ($data as $value)  {
                //echo $value."<br>";
                if ($fileFormat == 3) {
                } elseif (empty($value)) {
                    $error_flag = 1;
                }
                $storageArr[$i][$j++] = trim($value);   
            }
            if (feof($reader)) {
                break;
            } else {
              $i ++;
            }
        }  
        unset ($value);
        fclose($reader);   
   
        if ($error_flag > 0)  {
            echo "ERROR DETECT: One or more fields contained blank values"."<br/>";
            print "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">";
        } 	else {
            $linkID = connect(); 
            $missing = 0;
            for ($i = 1; $i <= count($storageArr) ; $i++)  {

                $marker = $storageArr[$i][$nameIdx];
                $alleleA = $storageArr[$i][$alleleAIdx];
                $alleleB = $storageArr[$i][$alleleBIdx];
                $sequence = $storageArr[$i][$sequenceIdx];
                
                if ($missing > 50 ) {
                    error(1, "There are too many invalid marker names. <br> Please fix and re-import." );
                    exit( "<input type=\"Button\" value=\"Return\" onClick=\"history.go(-1); return;\">");
                }
                
                /* Find the marker uid*/
                $sql = "SELECT marker_uid
                        FROM markers
                        WHERE marker_name ='$marker'";
                //echo "marker table lookup sql - " . $sql . "<br>";       
                $res = mysql_query($sql) or die("Database Error: Marker name lookup - ".mysql_error() ."<br>".$sql);
                $rdata = mysql_fetch_assoc($res);
                $marker_uid = $rdata['marker_uid'];
                
                //If we didn't get an uid, try the synonym table 
                if (empty ($marker_uid)) {
                    $sql = "SELECT marker_uid
                        FROM marker_synonyms
                        WHERE value ='$marker'";
                    $res = mysql_query($sql) or die("Database Error: Marker name lookup - ".mysql_error() ."<br>".$sql);
                    $rdata = mysql_fetch_assoc($res);
                    $marker_uid = $rdata['marker_uid'];
                }    
                // marker name don't exist in DB
                if (empty ($marker_uid)) {
                    error(1, "Marker name - ". $marker . " don't exist in DB. <br> Skipping this entry ..." );
                    $missing++;
                    continue;
                }
                //echo "marker_uid ".$marker_uid."<br>";

                $sql = "UPDATE markers SET A_allele = '$alleleA', B_allele='$alleleB', sequence='$sequence', updated_on=NOW() 
                            WHERE marker_uid = '$marker_uid'";
                $res = mysql_query($sql) or die("Database Error: SNP sequence update failed - ". mysql_error() ."<br>".$sql);
            }
            echo " <b>The Data is inserted/updated successfully </b><br>";
            echo "<br/><br/>";
        }
?>
        <a href="./curator_data/markers_upload.php"> Go Back To Main Page </a>
<?php
        $sql = "SELECT input_file_log_uid from input_file_log 
            WHERE file_name = '$filename'";
        $res = mysql_query($sql) or die("Database Error: input_file lookup  - ". mysql_error() ."<br>".$sql);
        $rdata = mysql_fetch_assoc($res);
        $input_uid = $rdata['input_file_log_uid'];
        
         if (empty($input_uid)) {
            $sql = "INSERT INTO input_file_log (file_name,users_name, created_on)
                VALUES('$filename', '$username', NOW())";
        } else {
            $sql = "UPDATE input_file_log SET users_name = '$username', created_on = NOW()
                        WHERE input_file_log_uid = '$input_uid'"; 
        }
        $lin_table = mysql_query($sql) or die("Database Error: Log record insertion failed - ". mysql_error() ."<br>".$sql);
        $footer_div = 1;
        include($config['root_dir'].'theme/footer.php');
    } /* end of function type_databaseSNP */
    
} /* end of class */
  
?>

