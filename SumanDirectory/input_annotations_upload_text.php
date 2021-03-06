<?

require 'config.php';
/*
 * Logged in page initialization
 */
include($config['root_dir'] . 'includes/bootstrap.inc');

connect();
loginTest();

/* ******************************* */
$row = loadUser($_SESSION['username']);

////////////////////////////////////////////////////////////////////////////////
ob_start();

authenticate_redirect(array(USER_TYPE_ADMINISTRATOR, USER_TYPE_CURATOR));
ob_end_flush();


new Annotations($_GET['function']);

class Annotations
{
    
    private $delimiter = "\t";
    
	
	// Using the class's constructor to decide which action to perform
	public function __construct($function = null)
	{	
		switch($function)
		{
							
					
			default:
				$this->typeAnnotations(); /* intial case*/
				break;
			
		}	
	}


private function typeAnnotations()
	{
		global $config;
		include($config['root_dir'] . 'theme/admin_header.php');

		echo "<h2>Add Experiment Annotation Information </h2>"; 
		
			
		$this->type_Annotation_Name();

		$footer_div = 1;
        include($config['root_dir'].'theme/footer.php');
	}
	
	
	private function type_Annotation_Name()
	{
	?>
	<script type="text/javascript">
	
	
	</script>
	
	<style type="text/css">
			th {background: #5B53A6 !important; color: white !important; border-left: 2px solid #5B53A6}
			table {background: none; border-collapse: collapse}
			td {border: 0px solid #eee !important;}
			h3 {border-left: 4px solid #5B53A6; padding-left: .5em;}
		</style>
		
		<form action="SumanDirectory/input_annotations_check_text.php" method="post" enctype="multipart/form-data">

	<input type="hidden" id="mapsetID" name="MapsetID" value="-1" />
	<p><strong>Annotation File:</strong> <input id="file[]" type="file" name="file[]" size="80%" /> &nbsp;&nbsp;&nbsp;   <a href="/curator_data/examples/Annot_test.txt">Example Annotation File(.txt)</a></p>
	
	
	<p> <strong> Do You Want This Data To Be Public: </strong> <input type='radio' name='flag' value="1"/> Yes &nbsp;&nbsp; <input type='radio' name='flag' value="0"/> No
	<p><input type="submit" value="Upload Annotations File" /></p>
	
	<br/><br/>
	
	<b>NOTE: Please click <a href="http://office.microsoft.com/en-us/excel-help/convert-columns-to-rows-or-rows-to-columns-HP005203138.aspx" target="_blank">here </a> to know how to convert rows to columns and columns to rows in  microsoft excel</b>
	
	

</form>
	
		
		
<?
 
	} /* end of type_Pedigree_Name function*/
	
} /* end of class */

?>