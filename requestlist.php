<?php
include('config.php');
include('include.php');
session_start();


include('../plan/authconfig.php');
include('../plan/auth.php');
include('../plan/OPS/include.php');




// Fix $_GET
//print_r($_SERVER);
$queryStr=urldecode($_SERVER['QUERY_STRING']);
$_GET=array();
foreach(explode('&', $queryStr) as $str) {
	$tokenArr=explode('=', $str);
	$arr1=array_splice($tokenArr, 0, 1);
	$_GET[$arr1[0]]=implode('=', $tokenArr);
}
// Set default values {
$CheckSecurity = new auth();
$check = $CheckSecurity->page_check($_SESSION['oplannm']);
$fullName='';
$email='';
if ($check) {
	$query = "SELECT *, concat(fname, ' ', lname) as FullName FROM onuma_plan.signup WHERE uname='".$_SESSION["oplannm"]."'";
	$result = mysql_query($query) or die(mysql_error()."<br><br>".$query);
	$userData=mysql_fetch_assoc($result);
	$SYSID=$userData['SYSID'];
	
	$fullName=$userData['FullName'];
	$email=$userData['email'];
}


if (isset($_SESSION['requestEmail'])) $email=$_SESSION['requestEmail'];

if (isset($_POST['requestEmail'])) $email=$_POST['requestEmail'];

if (isset($_GET['email'])) $email=$_GET['email'];
if (isset($_GET['email'])) $_SESSION['requestEmail']=$_GET['email'];
// }

$siteID=0;
$sysID=$_GET['sysID'];
if (isset($_GET['siteIDs'])) $siteIDs=$_GET['siteIDs'];
if (isset($_GET['siteID'])) $siteID=$_GET['siteID'];
$_GET['sysID']=$sysID;
$_REQUEST['sysID']=$sysID;

include('OPS/include.php');

if (!isset($sysID)) {
	echo 'Sys ID is not set';
	exit;
}
$bldgArr=array();
?>
<!DOCTYPE html>
<html lang="en">
<head>
		<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>Assignment Details</title>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
<link rel="stylesheet" type="text/css" href="css/workorder.css">
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.cookie.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/workorder.js"></script>
<script type="text/javascript" language="javaScript"><!--
$(document).ready(function(){ 
});
-->
</script>
<style>
</style>
</head>
<body>


<div class="container">
	<ul class="breadcrumb">
		<li class="active">My Requests</li>
		<div class="pull-right" style="padding-left:10px;">
        <a href="../plan/OpsBug.php?<?php echo getParams(); ?>" target="bugs">Bugs</a>
		</div>
	</ul>
    <div class="clearfix"></div>
		<?php if (trim($email=='')) { ?>
		<p>
		Please provide the your email address to check on the work orders that you submited:
		</p>
				<form class="form-horizontal" name="requestForm" method="get" onsubmit="
					if ($.trim($('#requestEmailTxt').val())=='') {
						alert('Please provide your email address');
						$('#requestEmailTxt').focus();
						return false;
					}
					document.location='requestlist.php?<?php echo getParams(); ?>&email='+$('#requestEmailTxt').val();
					return false;
				">
					<div class="form-group mb5">
						<label class="control-label col-xs-12 col-sm-4">Email:</label>
						<div class="col-xs-12 col-sm-6"><input name="requestEmail" id="requestEmailTxt" type="email" class="form-control input-sm" value="<?php echo html($email); ?>"<?php
																?> placeholder="Email" /></div>
					</div>
					<center>
					<input name="submitBtn" type="submit" class="btn btn-default btn-sm mb5" value="Submit" style="width:150px;" />
					</center>
				</form>
		<?php } else { ?>
		<p>
		Work orders that were submitted by <a href="mailto:<?php echo html($email); ?>"><?php echo html($email); ?></a>
		(check a <a href="requestlist.php?<?php echo getParams(array('email'=>'')); ?>">different email address</a>):
		</p>
		<table class="table table-responsive" style="margin-bottom:0px;">
			<thead>
				<tr>
					<th class="col-xs-2 active">Priority</th>
					<th class="col-xs-2 active">Request #</th>
					<th class="col-xs-4 active">Request Description</th>
					<th class="col-xs-2 active">Status</th>
					<th class="col-xs-2 active">Date</th>
				</tr>
			</thead>
			<tbody>
			   <?php
		$workOrderArr=WorkOrder::getWorkOrderArr("requestEmail='".$_SESSION['requestEmail']."'", 'requestDate DESC, assignedPriority DESC, requestNumber, requestDescription', '');
		$workOrderCounter=0;
		foreach ($workOrderArr as $workOrder) {
			$workOrderCounter++;
			if (!isset($bldgArr[$workOrder->getBldgID()])) {
				$bldgArr[$workOrder->getBldgID()]=new BIMBldg($workOrder->getBldgID());
			}
			?>
            <tr>
            	<td valign="top"><?php echo $priorityArr[$workOrder->getAssignedPriority()]; ?></td>
                <td valign="top"><a href="status.php?<?php echo getParams(array('siteID'=>$bldgArr[$workOrder->getBldgID()]->getSiteID())); ?>&workOrderID=<?php echo $workOrder->getID(); ?>"><?php echo htmlentities($workOrder->getRequestNumber()); ?></a></td>
            	<td valign="top"><?php echo htmlentities($workOrder->getRequestDescription()); ?></td>
            	<td valign="top"<?php
						if ($workOrder->getStatus()=='Completed Confirmed') echo ' class="text-success"';
						if ($workOrder->getStatus()=='Completion Declined') echo ' class="text-danger"';
					?>><?php echo $workOrder->getStatus(); ?></td>
            	<td valign="top"><?php echo date('m/d/y', strtotime($workOrder->getRequestDate())); ?></td>
            </tr>
            <?php
		}
		if ($workOrderCounter==0) {
				?>
				<tr>
					<td colspan="5" align="center">(No work orders submitted by this email address)</td>
				</tr>
				<?php
		}
		?>
			</tbody>
        </table>

		
		<?php } ?>
		<?php if (($_GET['siteIDs']!='') || ($_GET['siteID']!='')) { ?>
		<br />
		<p>
		 or click <a href="request.php?<?php echo getParams(); ?>">here</a> to submit a new request.
		</p>
		<?php } ?>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Onuma</p> 
</div>
</body>
</html>