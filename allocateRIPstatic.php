<! DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" -->
<head>
<title>Allocate</title>
<link rel="STYLESHEET" href="styles/style.css" type="text/css" />
<script type="text/javascript" src= "includes/validateform.js">
</script>
<script type="text/javascript" src="includes/httprequest.js">
</script>
</head>
<body>
<table width="100%">
<tr><td class="nopad">
<table width="500px">
<tr><td class="tan" colspan="2" align="center"><img src="images/allocate.png" border="0" alt="Allocate Page"/></td></tr>
<?php
session_start();
  /////////////////////////////////////////////////////////////
 ///**********************************************************/
/*/+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	+-+-+ +-+-+-+-+-+ +-+-+-+-+-+-+-+
	|B|y| |R|i|l|e|y| |W|i|t|h|e|r|s|
	+-+-+ +-+-+-+ +-+-+-+-+-+-+-+-+-+
	|W|D| |T|A|C| |M|E|T|R|I|C|S|
	 +-+-+ +-+-+-+ +-+-+-+-+-+-+-+
	
	Last Modified 2006-04-08 by Riley Withers 
	addition of insertion of data into searchtable
	
	Last Modified 2007-03-28 by Riley Withers
    removed the kma/state field
	added more validation
	merged two databases
	streamlined the input of the techs	
	2008-01-15 - Added a textbox so billing ticket number
	can be added.
/////////////////////////////////////////////////////*/
require '/usr/share/pear/DB.php';
require '/usr/share/pear/Mail.php';
require '/usr/share/pear/Mail/mime.php';
require('/usr/local/db_pass/db_new.inc');
require('includes/dbconnect_fetchmode_assoc.inc.php');

//////////////////////////////////////////////////////////////////////////////
//Get the user's info saved in the session variable called 'userinfo' for authentication
// [0] => user id from newdex.user db table
// [1] => username
// [2] => first name
// [3] => last name
// [4] => city (not location field)
// [5] => email address
// [6] => level user's been set at 
//[7] => auth - user rights  (2 admin,  1 delete scope, 0 allocate only)
////////////////////////////////////////////////////////////////////////////////////////
if($_SESSION['userinfo'][7] < 0) {
	include('includes/noauth.inc.php');
	exit;
}
//================================================================================

 
   //////////////////////////////////////////////////////////////////////////////////////////////
  //////////////////////////////////////////////////////////////////////////////////////////////
 ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
//////////// first, define function of low ip's.
function low_ips($lowonips) {
?>
	<tr><td align="center">
		<b>Remaining IPs: <?php print $lowonips;?> <br />
		There are not enough STATIC IPs available in the <?php print $_POST['cmtsnamefull'];?> area!<br />
		NW TAP adminstrator has been contacted</b></br>
	</td></tr>
<?php	
// create email to NW TAC	
$mail_body=<<<_TXT_
There are no more IP scopes available for the {$_POST['cmtsnamefull']} area.
Please Upload scope.

Thank you,
NW TAP IP Allocation Tool automailer.
_TXT_;
mail('dlwdnwtacmetrics@chartercom.com','No IP Scopes Available',$mail_body);

exit;
} ///////end of low_ips() function  ////////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////
//===============================================================================

 //////////
//////////
    ////////////////////////////////////////////////////////////////////////////////////////////
   ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
  /// 		Beginning of main logic that directs self-submits
 /// ========================================================================================/
/////////////////////////////////////////////////////////////////////////////////////////////


if($_POST['dupcheck'] == 'continue') {
	
	//process_form(); // go on to process the request from the showform funtion from the html form submitted
	// set the $post allocarea variable to pass it to the process function
	$allocarea = $_POST['allocarea'];
	process_form($allocarea);//To test the functionality of duplicate entries
}	

if (isset($_POST['action']) && $_POST['action'] == 'submitted') {
	//If validate_form() returns errors, pass them to show_form()
	if ($form_errors = validate_form()) {
        show_form($form_errors);
    }
	else {
        duplicate_data();
	}	
} 	
else {
   show_form();
} /////////////  End low IP's function ////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////
//===============================================================================

   //////////////////////////////////////////////////////////////////////////////////////////////
  //////////////////////////////////////////////////////////////////////////////////////////////
 ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
/// funtion to check for duplicate records on user input
function duplicate_data() {
global $db;
	$todaysdate = date('Y-m-d'); //used below to check for duplicate entries entered in the same day
	//print 'cmtsnamefull: '. $_POST['cmtsnamefull'] .'<br />';
	//now explode the cmts value and grab the first part for the area for validation
	$cmtsarea_arr = explode(' ',$_POST['cmtsnamefull']);
	$allocarea = $cmtsarea_arr[1];
	$_POST['cmtsnamefull'] = $cmtsarea_arr[0];
//print 'cmts: '. $_POST['cmtsnamefull'] .'<br />';
//print 'allocarea: '. $allocarea .'<br />';
	//print 'cmtsnamefull: '. $_POST['cmtsnamefull'] .'<br />';
		//check to see that it is not a duplicate submission, usually done the same day within minutes of each other
		// If system tech looses connection, person allocating statics continues to write info to database and while he/she is 
		// doing that, tech calls again to another tac member and that person writes to the database after the first tac member
		// creating a duplicate account.
		
		// To make this work, I have to now add code so it wil check against the iprange table to match say, subnets
		$qcheck = ("SELECT * FROM searchtable
					WHERE account = '{$_POST['acctnum']}'
					AND modemmac = '{$_POST['modemmac']}' 
					AND subnet = '{$_POST['subnet']}' 
					AND allocarea = '{$allocarea}' 
					AND deleted NOT LIKE '1' ");
		
		//print "qcheck: <br />$qcheck <br />";
		
		$qc = $db['ripstatics']->query($qcheck);
			
		while($row = $qc->fetchRow()) {
			$eachrow[] = $row;
		}
		$dupcheck = count($eachrow);

	// if the array is empty, then there are no duplicates
	if($dupcheck == 0 ) {
		process_form($allocarea); // go on to process the request from the showform funtion from the html form submitted
	}
	// If duplicates are found, display them
	else {
print <<<HTML
	<tr>
		<td class="results" colspan="2" align="center">
			Your submission matched the following database records:
		</td>
	</tr>
HTML;

		for ($i = 0, $numrows = count($eachrow); $i < $numrows; $i++) {
		
			print '<tr><td class = "errors" colspan ="2" align = "center" >
			Date Done:'. $eachrow[$i][dtalloc] .' <br />
			Account: '. $_POST['acctnum'] .'<br />
			Modem Mac: '. $_POST['modemmac'] .'<br />
			Static IP:'. $eachrow[$i][iprange] .' <br />
			on CMTS: '. $_POST['cmtsnamefull'] .'<br />
			Done By: '. $_POST['tech'] .'<br />
			From Location: '. $_POST['location'] .'<br />
			Do you want to proceed and allocate a different IP Subnet for the same customer? <br />
			</td></tr><tr><td align="center" class="tan" colspan="2"><form method="post" action="allocate.php" name="dupform">
			<input name="submit" id="submit" type="image" border="0" src="images/img_go_blue.gif" alt="Submit Form"/>
			<input type="hidden" name="dupcheck" value="continue" />
			<input type="hidden" name="id" value="'. $eachrow[$i][id] .'" />
			<input type="hidden" name="allocarea" value="'. $_POST['allocarea'] .'" />
			<input type="hidden" name="acctnum" value="'. $_POST['acctnum'] .'" />
			<input type="hidden" name="modemmac" value="'. $_POST['modemmac'] .'" />
			<input type="hidden" name="ip" value="'. $eachrow[$i][ip] .'" />
			<input type="hidden" name="cmtsnamefull" value="'. $_POST['cmtsnamefull'] .'" />
			<input type="hidden" name="location" value="'. $_POST['location'] .'" />
			<input type="hidden" name="dateallocated" value="'. $_POST['dateallocated'] .'" />
			<input type="hidden" name="tech" value="'. $_POST['tech'] .'" />
			<input type="hidden" name="subnet" value="'. $_POST['subnet'] .'" />
			<input type="hidden" name="allocarea" value="'. $allocarea .'" />
			</form></td></tr>';
		}
	//print '</table>';
	} // end else
} //// End function duplicate_data()  /////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////
//=======================================================================


   //////////////////////////////////////////////////////////////////////////////////////////////////////
  ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
 /// This function grabs all the IP addresses in the database, and after checking that there are  
/// enough, it sends them to the mod_test function
function process_form($allocarea) {
global $db;

	$subnet = $_POST['subnet'];

	//$db['ripstatics']->query("LOCK TABLES $allocarea WRITE");
	//// I ADDED THE ORDER BY ID ON 2008-01-28 TO PREVENT THE SCRIPT FROM GETTING CONFUSED
	$ipquery = ("SELECT ip from $allocarea WHERE acctnum ='0' ORDER BY id");
	//print "<br />$ipquery<br />";
	$result = $db['ripstatics']->query($ipquery);
	while ($ipdata = $result->fetchRow()) {
		//print 'ip: '. $ipdata[ip].'<br />';
		$candidateip_array[]= $ipdata[ip];
	}
	
	$ipsleft = count($candidateip_array);
	 // after grabbing all available ip's, now verify there's enough IPs available and if not,
	// email tac metrics

	if(($ipsleft <= 15) && ($subnet == 28)) {
		// unlock the table first
		//$db->query("UNLOCK TABLES");
		low_ips($ipsleft);
	}

	elseif(($ipsleft <= 7) && ($subnet == 29)) {
		// unlock the table first
		//$db->query("UNLOCK TABLES");
		low_ips($ipsleft);
	}
		
	elseif(($ipsleft <= 3) && ($subnet == 30)) {
		// unlock the table first
		//$db->query("UNLOCK TABLES");
		low_ips($ipsleft);
	}
	
	// If not too low on ip's, continue running the script.
	else {
	//Send email notice if there are only 11 /30 ip scopes left.
		if($ipsleft <= 44) {
$mail_body=<<<_TXT_
			There are 11 /30 IP scopes or less available in {$allocarea}.
			Please Upload another scope.

			Thank you,
			NW TAP IP Allocation Tool automailer.
_TXT_;
			mail('dlwdnwtacmetrics@chartercom.com','Low IP Scopes',$mail_body);
		}
		//print 'going to mod_test function<br />';
		//print_r($candidateip_array) .'<br />';
		mod_test($candidateip_array,$allocarea);
	}

} ///////end of process_form() function  /////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////
//===============================================================================


   //////////////////////////////////////////////////////////////////////////////////////////////
  //////////////////////////////////////////////////////////////////////////////////////////////
 ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
/// function that uses the modulus operand to create a list of available IP's to select from.
function mod_test($candidateips_array,$allocarea) {

	foreach($candidateips_array as $ipaddress) {
		$masklen = $_POST['subnet']; // Initialize $masklen to be the subnet chosen: 30, 29 or 28

		$ipparts = explode('.', $ipaddress);
		$firstthree = $ipparts[0]. '.' . $ipparts[1]. '.' . $ipparts[2];
		$lastoctet = $ipparts[3]; //Initialize $lastoctet to be the last octet of the first IP address in the $candidateips_array
		
		
		$modby = pow(2, (32 - $masklen)); // $modby is the number of IP's needed for customer: 4, 8 or 16
		$convoctet = (float)$lastoctet;  // Convert the last octet to a float so it will work with the modulus operand
		
		//I now have a good NetName
		if($convoctet % $modby  == 0) {
			//Verify that $modby-1 IP's are available, so we build an array of 4, 8 or 16 IP addresses
			for ($i=0; $i<=($modby - 1); $i++) {  
				$possip = $firstthree . '.' . $convoctet;
				$iplist_array[] = $possip;
				//increment the last octet and throw it in the array of ip's to be writen to database
				$convoctet+=1;
				//print 'possible ip: '. $possip .'<br />';
			}
			//exit;
			check_iplist($iplist_array,$modby,$allocarea); 
			// Send the newly created list of possible available IP's to see if they are contigous 
			// $modbcheck_iplist function 
		}
	}
} ///////end of mod_test() function  /////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////
//====================================================================================


    //////////////////////////////////////////////////////////////////////////////////////////////
   //////////////////////////////////////////////////////////////////////////////////////////////
  ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
 ///  Check that the possible ip's built in the $iplist_array array are available in the
///  database in a contigous format
function check_iplist($iplists, $modbynum,$allocarea) {
global $db;
	$subnet = $_POST['subnet'];
	$date= date('Y-m-d');   //for the date in YYYY-MM-DD format
	$time= date("H:i:s");  // 24 hr format with hr, minutes, seconds.

	$result = $db['ripstatics']->query("SELECT ip from $allocarea WHERE acctnum ='0' order by id");
	while ($ipdata = $result->fetchRow()) {
		$candidateip_array[]= $ipdata[ip];
	}
	//print "display all available IPs<br />\n";
	//print_r($candidateip_array)."<br />\n";
	foreach($iplists as $ipcheck) {
		if(in_array($ipcheck, $candidateip_array)) {
			$availip_array[] = $ipcheck;
		}
		elseif(!in_array($ipcheck, $candidateip_array)) {
			//send the array to another function to empty it out
			reset_iplist($iplists, $modbynum,$allocarea);
		}
	}

	  /// *** However, if it passes the test above and there are 4, 8 or 16 ip's  
	 /// available contigously, then write the record to the database table ****	
	// grab the first ip address of available ip's
	$ip = $availip_array;
	//print '<br /><br />Available Ips:<br />'. "\n";
	//print_r($ip) .'<br />\n';
	// find out how many records need to be updated by $i
	if ($subnet == '30') { $x=4; }
	elseif ($subnet == '29') { $x=8; }
	elseif ($subnet == '28') { $x=16; }
	elseif ($subnet == '27') { $x=32; }

	// write the records to the database
	for($i=0; $i<=$x; $i++) { 
		$ipquery = ("UPDATE $allocarea SET acctnum = '{$_POST['acctnum']}', 
		modemmac = '{$_POST['modemmac']}', 	cmts = '{$_POST['cmtsnamefull']}', 
		tech = '{$_POST['tech']}', dateallocated = '{$date}',
		timeallocated = '{$time}', location = '{$_POST['location']}'
		WHERE ip = '{$ip[$i]}' ");
		//print "<br />ipquery:<br />$ipquery<br />";
		$dbupdate = $db['ripstatics']->query($ipquery);
	}
		
	// unclock the table
	//$db->query("UNLOCK TABLES");
	if(!(DB::isError($dbupdate))) {
		//print '<br />going to display_results function<br />';
		display_results($date,$time,$allocarea); //If on successful completion, then display results.
	}

} //check_iplist() function  //////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////////
//====================================================================================




    //////////////////////////////////////////////////////////////////////////////////////////////
   //////////////////////////////////////////////////////////////////////////////////////////////
  ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
 /// remove the ip's that are not usable because they are not contiguous after finding 		
/// a list of subnetting from available
 function reset_iplist($iplist,$modnum,$allocarea) {
global $db;

$result = $db['ripstatics']->query("SELECT ip from $allocarea WHERE acctnum ='0'");
	//loop through ip's that were grabbed from database
	while($ipdata = $result->fetchRow()) {
		$candidateip_array[]= $ipdata[ip];
	}
	$lastip = array_pop($iplist);
	$ipparts = explode('.', $lastip);
	$lastoctet = $ipparts[3];
	$slicenum = (int)$lastoctet;
	// When we slice the array by the last octet of possible i.p's, it jumps by an entire 
	// subnet, ie, if we need 4 and next available is 12, it jumps to 16, so it slices the array by 8. 
	// if we need 8, it slices it by 16, etc.
	$chgmod = ($modnum + 1);
	//$numslice = $slicenum - $modnum;
	$output = array_slice($candidateip_array, ($slicenum - $chgmod)); //instead of slicing it by $lastoctet.
		
	mod_test($output,$allocarea); //Send the sliced $candidateip_array back minus the ones that cannot be used back to mod_test function
					
} ///////end of reset_iplist() function  //////////////////////////////////////////////////////////
 /////////////////////////////////////////////////////////////////////////////////
//===============================================================================				

   //////////////////////////////////////////////////////////////////////////////////////////////
  //////////////////////////////////////////////////////////////////////////////////////////////
 ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
// display the ip information needed to set up the router statically  
function display_results($dtalloc,$timealloc,$allocarea) {	
global $db;	
	$date= date('Y-m-d');   //for the dateuploaded field
	//$time= date("H:i:s");  // 24 hr format with hr, minutes, seconds.
	$subnet = $_POST['subnet'];
	//print 'subnet: '. $subnet .'<br />';
	// Make the state capitals
		// Grab ONLY the first ip that was put in that matches the acct. number  as it will be used to calculate needed static ip information
	$qrow = ("SELECT * FROM $allocarea WHERE acctnum = '{$_POST['acctnum']}' 
					AND dateallocated = '{$dtalloc}' AND timeallocated = '{$timealloc}' "); 
	//print "<br />qrow:<br />$qrow<br />";
	$q = $db['ripstatics']->getRow($qrow);
	
	//print 'row info: '. print_r($q) .'<br />';
	//$q = $qresult->fetchRow();
	// Grab these variables from the statics table to be written into the iprange table
	/* 	ip_netid explanation
	ip_netid is the first ip written to the database
	 thus it will become the network id and it will be used
	in the computation below to calculate the customer's 
	static networking information.
	*/
	// set the variable netid equal the first ip taken from the list that matched our selection based on acct number.
	//print "$qbt[account], $qbt[modemmac], $qbt[allocarea]  <br />";
	$netid = $q['ip'];
	//print 'netid: '. $netid .'<br />';
		if($subnet == '30') {

   		$subnet_mask = "255.255.255.252";
		$ip = ip2long($netid);
		$nm = ip2long($subnet_mask);
		$nw = ($ip & $nm);
		$bc = $nw | (~$nm);

		
		$noh 	= 	($bc - $nw - 2); 	// number of hosts
		$netadd = 	long2ip($nw); 		//network address or netowork id
		$bcastadd = long2ip($bc); 		//broadcast address
		$smask 	= 	long2ip($nm);		//Subnet Mask
		$gway 	= 	long2ip($nw + 1);	//Gateway or modem IP
		//customer's usable ip address:
		$custipfrst =	long2ip($bc - 1);	//Customer's IP address
		$custiplast	=	$custipfrst; 		//  Last customer's usable ip but only one here
		
		$iprange = $custipfrst;
		/*
		// grab this data to insert into the search table
		$qbt = $db->getRow("SELECT b.account, b.modemmac, b.allocarea, b.name, b.address, b.city,  b.zip, b.phone 
					FROM billingdata as b
					WHERE b.account = '{$_POST['acctnum']}'");
		//print "$qbt[account], $qbt[modemmac], $qbt[allocarea], $qbt[name], $qbt[zip] <br />";	
		//Escape backslashes for the name
		$qbt[name] = addslashes($qbt[name]);
		*/	

		$qsearch = ("INSERT INTO searchtable SET account = '{$_POST['acctnum']}', modemmac = '{$_POST['modemmac']}',
			allocarea = '{$allocarea}', subnet = '{$subnet}', submask = '{$subnet_mask}', iprange = '{$iprange}', cmts = '{$_POST['cmtsnamefull']}', 
			netaddr = '{$netadd}', bctaddr = '{$bcastadd}', dtalloc = '{$dtalloc}', allocby ='{$_POST['tech']}',
			timealloc = '{$timealloc}', location = '{$_POST['location']}' "); 
		//print 'qsearch: '. $qsearch .'<br />';
		$q = $db['ripstatics']->query($qsearch);			

		/*
		name ='{$qbt[name]}', address ='{$qbt[address]}',
		city ='{$qbt[city]}', zip = '{$qbt[zip]}', phone = '{$qbt[phone]}'
		*/			
	}
	// If they choose 5 or 13, then the output displayed is a little different shown below
	elseif ($subnet == '29') {
		$subnet_mask = "255.255.255.248";
		$ip = ip2long($netid);
		$nm = ip2long($subnet_mask);
		$nw = ($ip & $nm);
		$bc = $nw | (~$nm);

		$noh 	=	($bc - $nw - 2); 	// number of hosts
		$netadd =	long2ip($nw); 		//network address or netowork id
		$bcastadd =	long2ip($bc); 		//broadcast address
		$smask 	= 	$subnet_mask;		//Subnet Mask
		$gway 	=	long2ip($nw + 1); 	//Gateway or modem IP
		//Customer's usable IP address range:
		$custipfrst =	long2ip($nw + 2);// fisrt customer's usable ip
		$custiplast	=	long2ip($nw + 6);// Last customer's usable ip 

	    $iprange = "{$custipfrst} - {$custiplast}";
		/*
		// grab this data to insert into the search table
		$qbt = $db['ripstatics']->query("SELECT b.account, b.modemmac, b.allocarea, b.name, b.address, b.city,  b.zip, b.phone 
					FROM billingdata as b
					WHERE b.account = '{$_POST['acctnum']}'");
		//Escape backslashes for the name
		$qbt[name] = addslashes($qbt[name]);
		*/

		$qsearch = ("INSERT INTO searchtable SET account = '{$_POST['acctnum']}', modemmac = '{$_POST['modemmac']}',
			allocarea = '{$allocarea}', subnet = '{$subnet}', submask = '{$subnet_mask}', iprange = '{$iprange}', cmts = '{$_POST['cmtsnamefull']}', 
			netaddr = '{$netadd}', bctaddr = '{$bcastadd}', dtalloc = '{$dtalloc}', allocby ='{$_POST['tech']}',
			timealloc = '{$timealloc}', location = '{$_POST['location']}' "); 
		$q = $db['ripstatics']->query($qsearch);			

	}
	elseif ($subnet == '28') {

		$subnet_mask = "255.255.255.240";
		$ip = ip2long($netid);
		$nm = ip2long($subnet_mask);
		$nw = ($ip & $nm);
		$bc = $nw | (~$nm);

		$noh 	=	($bc - $nw - 2); 	// number of hosts
		$netadd =	long2ip($nw); 		//network address or netowork id
		$bcastadd =	long2ip($bc); 		//broadcast address
		$smask 	= 	$subnet_mask; 		//Subnet Mask
		$gway 	=	long2ip($nw + 1); 	//Gateway or modem IP
		//Customer's usable IP address range:
		$custipfrst =	long2ip($nw + 2);// fisrt customer's usable ip
		$custiplast	=	long2ip($nw + 14);// Last customer's usable ip 
   
		$iprange = "{$custipfrst} - {$custiplast}";
		/*
		// grab this data to insert into the search table
		$qbt = $db['ripstatics']->getRow("SELECT b.account, b.modemmac, b.allocarea, b.name, b.address, b.city,  b.zip, b.phone 
					FROM billingdata as b
					WHERE b.account = '{$_POST['acctnum']}'");
		
		//Escape backslashes for the name
		$qbt[name] = addslashes($qbt[name]);	
		*/
		$qsearch = ("INSERT INTO searchtable SET account = '{$_POST['acctnum']}', modemmac = '{$_POST['modemmac']}',
			allocarea = '{$allocarea}', subnet = '{$subnet}', submask = '{$subnet_mask}', iprange = '{$iprange}', cmts = '{$_POST['cmtsnamefull']}', 
			netaddr = '{$netadd}', bctaddr = '{$bcastadd}', dtalloc = '{$dtalloc}', allocby ='{$_POST['tech']}',
			timealloc = '{$timealloc}', location = '{$_POST['location']}' "); 
		$q = $db['ripstatics']->query($qsearch);			

	}
	elseif ($subnet == '27') {

		$subnet_mask = "255.255.255.224";
		$ip = ip2long($netid);
		$nm = ip2long($subnet_mask);
		$nw = ($ip & $nm);
		$bc = $nw | (~$nm);

		$noh 	=	($bc - $nw - 2); 	// number of hosts
		$netadd =	long2ip($nw); 		//network address or netowork id
		$bcastadd =	long2ip($bc); 		//broadcast address
		$smask 	= 	$subnet_mask; 		//Subnet Mask
		$gway 	=	long2ip($nw + 1); 	//Gateway or modem IP
		//Customer's usable IP address range:
		$custipfrst =	long2ip($nw + 2);// fisrt customer's usable ip
		$custiplast	=	long2ip($nw + 30);// Last customer's usable ip 
   		
		$iprange = "{$custipfrst} - {$custiplast}";
		/*
		print 'subnet_mask: '. $subnet_mask .'<br/>';
		print 'ip: '. $ip .'<br/>';
		print 'nm: '. $nm .'<br/>';
		print 'nw: '. $nw .'<br/>';
		print 'bc: '. $bc .'<br/>';
		print 'noh: '. $bc .'<br/>';
		print 'netadd: '. $netadd .'<br/>';
		print 'bcastadd: '. $bcastadd .'<br/>';
		print 'smask: '. $smask .'<br/>';
		print 'gway: '. $gway .'<br/>';
		print 'custipfrst: '. $custipfrst .'<br/>';
		print 'custiplast: '. $custiplast .'<br/>';
		print 'iprange: '. "{$custipfrst} - {$custiplast}" .'<br/>';
		*/
		
		
		/*
		// grab this data to insert into the search table
		$qbt = $db['ripstatics']->getRow("SELECT b.account, b.modemmac, b.allocarea, b.name, b.address, b.city,  b.zip, b.phone 
					FROM billingdata as b
					WHERE b.account = '{$_POST['acctnum']}'");
		
		//Escape backslashes for the name
		$qbt[name] = addslashes($qbt[name]);	
		*/
		$qsearch = ("INSERT INTO searchtable SET account = '{$_POST['acctnum']}', modemmac = '{$_POST['modemmac']}',
			allocarea = '{$allocarea}', subnet = '{$subnet}', submask = '{$subnet_mask}', iprange = '{$iprange}', cmts = '{$_POST['cmtsnamefull']}', 
			netaddr = '{$netadd}', bctaddr = '{$bcastadd}', dtalloc = '{$dtalloc}', allocby ='{$_POST['tech']}',
			timealloc = '{$timealloc}', location = '{$_POST['location']}' "); 
		$q = $db['ripstatics']->query($qsearch);			

	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
	//
	//   Now insert into the ticket number - but first we must grab the record number we just wrote
	$qtick = ("SELECT * FROM searchtable WHERE account = '{$_POST['acctnum']}' AND modemmac = '{$_POST['modemmac']}' 
			AND dtalloc = '{$dtalloc}' AND deleted NOT LIKE '1' ");

	//print "qtick: <br />$qtick <br />";
	$qtdata = $db['ripstatics']->getRow($qtick);
	//$q = $db['ripstatics']->getRow($qrow);
	//print_r($qtdata) .'<br />';
	
	//// Now insert a record in the tickets database record
	$qtickin = ("INSERT INTO ticket_records SET rec_id = '{$qtdata[id]}', ticket_num = '{$_POST['pallticknum']}', 
			network_id = '{$qtdata[netaddr]}', done_by = '{$qtdata[allocby]}', date_done = '{$dtalloc}' ");
	$db['ripstatics']->query($qtickin);
	
	//=======================================================================================
	/////////////////////////////////////////////////////////////////////////////////////////
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	// Grab the ip's again to do a count of the remaining ip's left in the database to display them at the end,
	// used if the mail alert function fails.
	$qcount = ("SELECT ip from $allocarea WHERE acctnum ='0'");
	//print "<br />$qcount<br />";
	$count =& $db['ripstatics']->query($qcount);
	$ipsleft = $count->numRows();
	//print 'ips left: '. $ipsleft .'<br />';
	
	   ///////////////////////////////////////////////////////////////////////////////
	  //++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
	 //print out the information to set up the router for the ip address(es) chosen.
	///////////////////////////////////////////////////////////////////////////////

	// This part is to generate the config scripts with the settings in them 
	//so all they have to do is copy and paste
	
	// get the last 4 of the mac for the modem password
	$macparts 	= explode(".",$_POST['modemmac']);
	$cbnpass	= $macparts[2];
	
	// set up the DNS and rip key depending what area they are allocationg for
	if ($allocarea == 'ca') {
	$primarydns="xx.189.122.26";
	$secondarydns="xx.189.122.19";
	$ripkey= "DoS0m3[@p0eir4";
	}
	if ($allocarea == 'wa') {
	$primarydns="xx.185.34.67";
	$secondarydns="xx.185.32.10";
	$ripkey= "DoS0m3[@p0eir4";
	}
	if ($allocarea == 'ore') {
	$primarydns="xx.116.46.115";
	$secondarydns="xx.116.46.70";
	$ripkey= "DoS0m3[@p0eir4";
	}
	if ($allocarea == 'nv') {
	$primarydns="xx.205.192.61";
	$secondarydns="xx.205.224.36";
	$ripkey= "cDoS0m3[@p0eir4";
	}
	if ($allocarea == 'la') {
	$primarydns="xx.205.1.14";   
	$secondarydns="xx.215.64.14";
	$ripkey= "DoS0m3[@p0eir4";
	}
	if ($allocarea == 'ie') {
	$primarydns="xx.205.1.14";
	$secondarydns="xx.215.64.14";
	$ripkey= "DoS0m3[@p0eir4";
	}
	if ($allocarea == 'nc') {
	$primarydns="xx.205.224.35";
	$secondarydns="xx.214.48.27";
	$ripkey= "DoS0m3[@p0eir4";
	}
	$allocarea = strtoupper($allocarea);
?>
<tr class="tan">
	<td colspan="2">
<table align="center"><tr>
<td>
<table>
	<tr><td>
			<?php print $dtalloc .' at '. $timealloc;?>
		</td></tr>
	<tr><td>
		<b>allocarea and CMTS:</b>
		</td></tr>
	<tr><td>
		<?php print $allocarea .'&nbsp'. $_POST['cmtsnamefull'];?>
		</td></tr>
	<tr><td>
		<b>Allocated by:</b>
		</td></tr>
	<tr><td>
		<?php print $_POST['tech'];?>
		</td></tr>
	<tr><td>
		<b>Network ID:</b>
		</td></tr>
	<tr><td>
		<?php print $netadd;?>
		</td></tr>
	<tr><td>
		<b>Subnet:</b>
		</td></tr>
	<tr><td>
		<?php print $subnet_mask;?>
		</td></tr>
	<tr><td>
		&nbsp;
		</td></tr>
	<tr><td>
		&nbsp;
		</td></tr>
	<tr><td>
		&nbsp;
		</td></tr>
	<tr><td>
		&nbsp;
		</td></tr>
	</table>
	</td>
		<td rowspan="8" colspan="2">
		<table align="center">
		<tr><td width="209">
			<b>Gateway IP:</b>
			</td></tr>
		<tr><td>
			<?php print $gway;?>
			</td></tr>
		<tr><td>
			<b>Customer IPs:</b>
			</td></tr>
		<tr><td>
			<?php print $custipfrst .' - '. $custiplast;?>
			</td></tr>
		<tr><td>
			<b>Mac Address:</b>
			</td></tr>
		<tr><td>
			<?php print $_POST['modemmac'];?>
			</td></tr>
		<tr><td>
			<b>Account Number:</b>
			</td></tr>
		<tr><td>
			<?php print $_POST['acctnum'];?>
			</td></tr>
		<tr><td>
			<b>DNS Servers:</b>
			</td></tr>
		<tr><td>
			<?php print $primarydns .', '. $secondarydns;?>
			</td></tr>
		<tr><td>
			<b>Network Address:</b> 
			</td></tr>
		<tr><td>
			<?php print $netadd;?>
			</td></tr>
		<tr><td>
			<b>Broadcast Address:</b> 
			</td></tr>
		<tr><td>
			<?php print $bcastadd;?>
			</td></tr>
	</table>
</td></tr>
</table>
<tr>
	<td colspan="2" class="results">			
	<table border="1" width="75%" align="center">
		<tr>
			<td class="blanktemplates">
<?php
print <<<HTML
			<b>Configuration Scripts, go </b> <a href="#" onClick="window.open('templatemenu.php?gway=$gway&subnet_mask=$subnet_mask&ripkey=$ripkey&cbnpass=$cbnpass&primarydns=$primarydns&sencondarydns=$secondarydns','','scrollbars=yes,height = 500,width = 400,status = yes,resizable=yes,left=250px,top=150px');">HERE</a></b>
HTML;
?>
			</td>
		</tr>
	</table>
	</td>
</tr>
<tr><td class="tan" width="50%" align="left"><img src="images/lltan.gif" width="14px" height="14px" border="0"/></td><td class="tan" width="50%" align="right"><img src="images/lrtan.gif" width="14px" height="14px" border="0"/></td></tr>
</table>
</td>
</tr>
<tr><td class="nopad" align="left" width="100%">&nbsp;</td>
  </tr>
</table>
</body>
</html>
<?php
exit; // exit script so it does not continue selecting static ip's.
} ////////////// end of display_form_results() function  //////////////////////////////
 ////////////////////////////////////////////////////////////////////////////////
//===============================================================================
//___________________________________________________________
//-----------------------------------------------------------------------------------------------
//*******************************************************************************
  /////////////
 /////////////
/////////////
	   ////////////////////////////////////////////////////////////////////////////////////////////
      ////////////////////////////////////////////////////////////////////////////////////////////
     ////////////////////////////////////////////////////////////////////////////////////////////
    ///++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++/
   /// 		*** Beginning of script here ***     
  /// 	Display the initial form
 /// ========================================================================================/
/////////////////////////////////////////////////////////////////////////////////////////////
function show_form($errors = '') {
global $db;

///////////////////////////////////////////////////////
//++++++++++++++++++++++++++++++++++++++++++++++++++++
// Set display to be true to display a message
$display = 0;
//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
// First, check the statics table to see if there is an allocation area that is full and has no ip's to 
// allocate and display that on the front page later down
//
	$caq =& $db['ripstatics']->query("SELECT ip FROM ca WHERE acctnum = '0' ");
	$cacount = $caq->numRows();
	$ieq =& $db['ripstatics']->query("SELECT ip FROM ie WHERE acctnum = '0' ");
	$iecount = $ieq->numRows();
	$laq =& $db['ripstatics']->query("SELECT ip FROM la WHERE acctnum = '0'");
	$lacount = $laq->numRows();
	$ncq =& $db['ripstatics']->query("SELECT ip FROM la WHERE acctnum = '0'");
	$nccount = $ncq->numRows();
	$nvq =& $db['ripstatics']->query("SELECT ip FROM nv WHERE acctnum = '0'");
	$nvcount = $nvq->numRows();
	$orq =& $db['ripstatics']->query("SELECT ip FROM ore WHERE acctnum = '0'");
	$orcount = $orq->numRows();
	$waq =& $db['ripstatics']->query("SELECT ip FROM wa WHERE acctnum = '0'");
	$wacount = $waq->numRows(); 

print <<<HTML
	<tr>
	    <td>
		<form method="post" action="$_SERVER[PHP_SELF]" name="scatform" onSubmit="return validform(this)"> 
	    Select the CMTS the modem is on:
	    </td>
		<td>
HTML;
		$q = ("SELECT allocarea, cmtsnamefull,cmtsname FROM hostlist
						WHERE allocarea NOT LIKE '0' AND active = '1' ORDER BY allocarea, cmtsname");
		//print 'cmts query: '. $q .'<br />';
		$data = $db['nw-ubrcheck']->query($q);

print <<<HTML
		<select name="cmtsnamefull" type="select">
		<option value="">Select</option>
HTML;
	while ($row = $data->fetchRow()) {
		print "<option value=\"{$row[cmtsnamefull]} {$row[allocarea]}\">" . strtoupper($row[allocarea]) ." - {$row[cmtsnamefull]} - {$row[cmtsname]}</option>";
}
?>
		</select>
	    </td>
	</tr>
	<tr>
		<td>
		Select the subnet:
		</td>
		<td>
		<select name="subnet" >
			<option value="">Select</option>
			<option value="30">255.255.255.252 (/30, 1 pack)</option>
			<option value="29">255.255.255.248 (/29, 5 pack)</option>
			<option value="28">255.255.255.240 (/28, 13 pack)</option>
			<option value="27">255.255.255.224 (/27, 30 pack)</option>
		</select>
		</td>
</tr>
<tr>
		<td>
			Customer billing account number:
		</td>
		<td>
		<input type="text" name="acctnum">
			<br /><div style="font-family:Arial, Verdana;font-size:9pt;color:#FF6699;background-color:#DBD5BD;padding-left:5px;">[ do not enter the site id ]</div>
		</td>
	</tr>
	<tr>
		<td>
			Paladin Site ID Number:
		</td>
		<td>
		<input type="text" name="pallticknum">
		</td>
	</tr>
	<tr>
		<td>
			Modem mac:
		</td>
		<td>
		<input type="text" name="modemmac">
		</td>
	</tr>
	<tr>
	    <td>
			Your name:
	    </td>
<?php
print <<<HTML
	    <td style="color:#666;">{$_SESSION['userinfo'][1]}<input type="hidden" name="tech" value={$_SESSION['userinfo'][1]} />
		</td>
    </tr>
	<tr>
	    <td>
			Your location:
	    </td>
	    <td style="color:#666;">{$_SESSION['userinfo'][4]}<input type="hidden" name="location" value={$_SESSION['userinfo'][4]} />
HTML;
?>
		</td>
    </tr>
	<tr>
	    <td colspan="2" align="center" valign="middle">
	    <input name="submit" type="image" border="0" src="images/img_go_blue.gif" alt="Submit Form"/>
			<input type="hidden" name="action" value="submitted" />
	    	</form>
	    </td>
    </tr>
<?php
// If some errors were passed in, print them out below the form 
    if ($errors) {
		print '<tr><td class="errors" align="center" colspan="2">';
        print 'Please correct these errors:<ul><li>';
        print implode('</li><li>', $errors);
        //print '</li></ul>';
		print '</td></tr>';
	}
print <<<HTML
	<tr>
	<td colspan="2" align="center" valign="middle">
		
HTML;
// This is to let them know what is going on in the same div - var has to be set 
// to 1 to display it which the var is set at the beginning of this function
if($display == 1) {
print <<<HTML
	<div class="divmsgnew" id="divmsg" name="divsmg" align="center">
		<b>New scope uploaded for LA KMA.</b>
	</div>
HTML;
} 

// display a little div letting know there are no ip's available in a certain KMA 
if($cacount == 0){
print <<<HTML
	<div class="noipsdisplay" id="noips" name="noips" align="center">
		No IPs are available for NC, CSG KMA at this time.
	</div>
HTML;
} 	
if($iecount == 0){
print <<<HTML
	<div class="noipsdisplay" id="noips" name="noips" align="center">
		No IPs are available for IE KMA at this time.
	</div>
HTML;
}
if($lacount == 0){
print <<<HTML
	<div class="noipsdisplay" id="noips" align="center">
		No IPs are available for LA KMA at this time.
	</div>
HTML;
}
/* this part not working quite yet for some reason
if($nccount == 0){
print <<<HTML
	<div class="noipsdisplay" id="noips" align="center">
		No IPs are available for NC, ICOMS KMA at this time.
	</div>
HTML;
}
*/
if($nvcount == 0){
print <<<HTML
	<div class="noipsdisplay" id="noips" align="center">
		No IPs are available for NV KMA at this time.
	</div>
HTML;
}
if($orcount == 0){
print <<<HTML
	<div class="noipsdisplay" id="noips" align="center">
		No IPs are available for NW KMA at this time.
	</div>
HTML;
}
if($wacount == 0){
print <<<HTML
	<div class="noipsdisplay" name="noips" id="noips" align="center">
		No IPs are available for NW KMA at this time.
	</div>
HTML;
}
print <<<HTML
	&nbsp;
	</td>
	</tr>
	<tr>
	<td colspan="3" align="center"> 
	<table border="1" width="75%">
	<tr>
	<td class="blanktemplates">
	<b>Blank Templates for Troubleshooting<br />
	Please go <a href="javascript:void(0);" onClick="window.open('templatemenu_blank.php','','scrollbars=yes,height = 600,width = 450,status = yes,resizable=yes,left=250px,top=150px');">HERE</a></b>
	<br />
	<br />
	</td>
	</tr>
	</table>
</td>
</tr>
HTML;

} //////// end of show_form function ///////
//===================================================================================

/////////////////////////////////////////////////////////////////////////////////////
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
// Check that the form data entered is valid

function validate_form() {
	// check what area they are in to verify the length of the account number
	// explode the cmts value and grab the first part for the area for validation
	$cmtsarea_arr = explode(' ',$_POST['cmtsnamefull']);
	//$_POST['cmtsnamefull'] = $cmtsarea_arr[0];
	$allocarea = $cmtsarea_arr[1];
	//print 'cmtsnamefull: '. $_POST['cmtsnamefull'] .'<br />';
	//print 'allocarea: '. $allocarea .'<br />';
    // Start with an empty array of error messages to fill with error messages.
    $errors = array();
	
	$mac = $_POST['modemmac'];
	$chars = ": . , ;";
	preg_match_all("(.)",$chars,$tmp_clean);
	$stripped = str_replace($tmp_clean[0], array_fill(0,count($tmp_clean),""),$mac);
    ereg("([0-9a-fA-F]{4})([0-9a-fA-F]{4})([0-9a-fA-F]{4})", $stripped, $newmac);
	$fixedmac =  $newmac[1].'.'.$newmac[2].'.'.$newmac[3];
	$loweredmac = strtolower($stripped);
	//print 'fixedmac: '. $fixedmac .'<br />';
	//$modemmac = strtolower($fixedmac);

	$_POST['modemmac'] = strtolower($fixedmac);
	
	if($_POST['cmtsnamefull'] == '') {
		$errors[] = 'Please choose a cmts';
	}
	if (($_POST['subnet']) =='') {
        $errors[] = 'Please choose a subnet';
    }
	elseif ((trim(strlen($_POST['acctnum'])) < 8) || (trim(strlen($_POST['acctnum'])) > 10) && ($allocarea == 'ie' || $allocarea == 'la' || $allocarea == 'nc')) {
        $errors[] = 'Acct. number must be less than 10 digits long';
    }
	elseif (trim(strlen($_POST['acctnum']) <> 16) && ($allocarea == 'ca' || $allocarea == 'nv' || $allocarea == 'ore' || $allocarea == 'wa')) {
		$errors[] = 'Acct. number must be 16 digits long';
    }
	elseif ((strlen(trim($_POST['modemmac'])) < 17)) {
        $mac = $_POST['modemmac'];
		$chars = ": . , ;";
		preg_match_all("(.)",$chars,$tmp_clean);
		$stripped = str_replace($tmp_clean[0], array_fill(0,count($tmp_clean),""),$mac);
		$loweredmac = strtolower($stripped);

		if (ereg ("([0-9a-fA-F]{4})([0-9a-fA-F]{4})([0-9a-fA-F]{4})", $loweredmac, $newmac)) {
			$_POST['modemmac'] = $newmac[1].'.'.$newmac[2].'.'.$newmac[3];
		} 
		else {
 			$errors[] = 'modem is incorrect';
		}
	}
	elseif ((strlen(trim($_POST['modemmac'])) > 17)) {
 			$errors[] = 'modem mac is too long.';
	}
    // Return the (possibly empty) array of error messages
   	return $errors;
	
} // End validate erros functions /////////////////////////////////////
//==================================================================
?>
<tr><td class="tan" width="50%" align="left"><img src="images/lltan.gif" width="14px" height="14px" border="0"/></td><td class="tan" width="50%" align="right"><img src="images/lrtan.gif" width="14px" height="14px" border="0"/></td></tr>
</table>
</td>
</tr>
<tr><td class="nopad" align="left" width="100%">&nbsp;</td>
  </tr>
</table>
</body>
</html>

