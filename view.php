<?php 
include("includes/bootstrap.inc");
connect();
include("theme/normal_header.php");

// Will take both uids and names
if(isset($_REQUEST['name']) && !isset($_REQUEST['uid']))
  $_REQUEST['uid'] = $_REQUEST['name'];
if (strpos($_REQUEST['uid'], "=", strlen($_REQUEST['uid'])-1) !== FALSE) 
  $_REQUEST['uid'] = base64_decode($_REQUEST['uid']);

if( ($record = isRecord($_REQUEST['table'], $_REQUEST['uid'])) !== FALSE) { 
  $row = mysql_fetch_assoc($record);
  $pkey = get_pkey($_REQUEST['table']);
  $name = get_unique_name($_REQUEST['table']);		   
?>

<h1><?php echo beautifulTableName($_REQUEST['table'], 0) . " " . $row[$name] ?></h1>
<div class="boxContent">

<?php 
   $func = "show_" . $_REQUEST['table'];
  // Is there a custom function for that table in includes/general.inc
  // or includes/pedigree.inc?  examples:
   //  line_records = includes/pedigree.inc/show_line_records()
   //  markers = includes/general.inc/show_markers()
   //  breeding_programs = includes/general.inc/show_breeding_programs()
  if(function_exists($func))
    call_user_func($func, $row[$pkey]);
  else {
    // Default to raw table dump using includes/general.inc:show_general().
    show_general($_REQUEST['table'], $row[$pkey]);
  }
  echo "</div>";
} 
else 
  error(1, "No Record Found"); 
echo "</div>";
include("theme/footer.php");
?>
