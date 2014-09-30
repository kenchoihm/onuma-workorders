<?php
include('config.php');
include('include.php');
session_start();


include('../plan/authconfig.php');
include('../plan/auth.php');
include('../plan/OPS/include.php');


if ($_GET['siteID']!='') {
	$site=new BIMSite($_GET['siteID']);
	$siteInfo=$site->getSiteInfo();
	$workOrderSetting=$siteInfo->getWorkOrderSetting();
	$project=new BIMProject($site->getProjectID());
} else {
	if ($_GET['siteIDs']=='') {
		header('location: requestlist.php?'.getParams());
		return;
	}
}

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
if (isset($_SESSION['name'])) $fullName=$_SESSION['name'];
if (isset($_SESSION['requestEmail'])) $email=$_SESSION['requestEmail'];
if (isset($_SESSION['requestTelephone'])) $telephone=$_SESSION['requestTelephone'];
if (isset($_SESSION['chargeCode'])) $chargeCode=$_SESSION['chargeCode'];

if (isset($_POST['name'])) $fullName=$_POST['name'];
if (isset($_POST['requestEmail'])) $email=$_POST['requestEmail'];
if (isset($_POST['requestTelephone'])) $telephone=$_POST['requestTelephone'];
if (isset($_POST['chargeCode'])) $chargeCode=$_POST['chargeCode'];

if (isset($_GET['name'])) $fullName=$_GET['name'];
if (isset($_GET['phone'])) $telephone=$_GET['phone'];
if (isset($_GET['email'])) $email=$_GET['email'];
$getStr='';
if (isset($_GET['name'])) $getStr.='&name='.$_GET['name'];
if (isset($_GET['phone'])) $getStr.='&phone='.$_GET['phone'];
if (isset($_GET['email'])) $getStr.='&email='.$_GET['email'];
unset($check);
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
	
if ($siteID!=0) {
	$site=new BIMSite($siteID);
	$siteInfo=$site->getSiteInfo();
	$workOrderSetting=$siteInfo->getWorkOrderSetting();
	$bldgs=$site->getBIMBldgs($reload=true, $orderBy='sortNum');
	if ($_GET['checkPassword']==1) {
		echo (($_POST['requestPassword']==$workOrderSetting->getRequestPassword())?1:0);
		exit;
	}
	
	if (isset($_COOKIE['requestPassword'])) {
		if ($workOrderSetting->getRequestPassword()!=$_COOKIE['requestPassword']) {
			setcookie ("requestPassword", "", time() - 3600);
			unset($_COOKIE['requestPassword']);
			header('location: request.php?'.getParams().'&incorrectPassword=1');
			return;
		}
	}
	$contactID=$workOrderSetting->getAdministratorID();
	if ($contactID==0) {
		$project=new BIMProject($site->getProjectID());
		$contactID=$project->getContactID();
	}
} else {
	$siteIDArr=explode(',', $siteIDs);
	$siteArr=array();
	foreach ($siteIDArr as $tmpSiteID) {
		$site=new BIMSite($tmpSiteID);
		$siteArr[$tmpSiteID]=$site;
	}
}

if (isset($_POST['requestEmail'])) {
	if (($workOrderSetting->getRequestPassword()=='') || ($workOrderSetting->getRequestPassword()==$_POST['requestPassword'])) {
		include('../plan/includes/class.upload.php');
		$workOrder=new WorkOrder();
		$workOrder->setAdministratorID($contactID);
		$workOrder->setSubmittedBy($_POST['name']);
		$workOrder->setRequestEmail($_POST['requestEmail']);
		$workOrder->setRequestTelephone($_POST['requestTelephone']);
		$workOrder->setBldgID($_POST["bldgID"]);
		$workOrder->setFloorID(((isset($_POST["floorID"]))?$_POST["floorID"]:0));
		$workOrder->setSpaceID(((isset($_POST["spaceID"]))?$_POST["spaceID"]:0));
		$workOrder->setRequestDate(date('Y-m-d H:i:s'));
		$workOrder->setRequestDescription($_POST["requestDescription"]);
		$workOrder->setLocationDescription($_POST["locationDescription"]);
		$workOrder->setRequestType($_POST["requestType"]);
		$workOrder->setChargeCode($_POST["chargeCode"]);
		$workOrder->insert();
		$workOrder->setRequestNumber($_POST['bldgID'].'-'.$workOrder->getID());
		if ($siteInfo->isUseEmergencyTechnician()) {
			$workOrder->setAssignedContactID($workOrderSetting->getEmergencyTechnicianContactID());
			$workOrder->setStatus("Assigned");
			$workOrder->setAssignedDate(date('Y-m-d H:i:s'));
		}
		$workOrder->update();

		$_SESSION['name']=$_POST['name'];
		$_SESSION['requestEmail']=$_POST['requestEmail'];
		$_SESSION['requestTelephone']=$_POST['requestTelephone'];
		$_SESSION['bldgID']=$_POST['bldgID'];
		$_SESSION['floorID']=((isset($_POST["floorID"]))?$_POST["floorID"]:0);
		$_SESSION['spaceID']=((isset($_POST["spaceID"]))?$_POST["spaceID"]:0);
		$_SESSION['locationDescription']=$_POST['locationDescription'];

		// as it is multiple uploads, we will parse the $_FILES array to reorganize it into $files
		$files = array();
		if (isset($_FILES['fileName'])) {
			foreach ($_FILES['fileName'] as $k => $l) {
				foreach ($l as $i => $v) {
					if (!array_key_exists($i, $files)) 
						$files[$i] = array();
					$files[$i][$k] = $v;
				}
			}
		}
		if (isset($_POST['imgArr'])) {
			$dir='/';
			foreach ($_POST['imgArr'] as $url) {
				//$tmpFile=tempnam('', '');
				$file= SYSPATH."plan/attach/sys_".$sysID.$dir.basename($url);
				//copy($url, $file);

				$ch = curl_init($url);
				$fp = fopen($file, "w");
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_exec($ch);
				curl_close($ch);
				fclose($fp);


				$handle = new upload($file);
				$handle->Process(SYSPATH."plan/attach/sys_".$sysID.$dir);

				$handle->image_resize = true;
				$handle->image_x = 150;
				$handle->image_y = 150;	
				$handle->image_ratio_no_zoom_in = true;

				// we copy the file in the extra thumbnail folder
				$handle->Process(SYSPATH."plan/attach/sys_".$sysID."/thumbnail".$dir);
				// we check if everything went OK
				$workOrderFile=new WorkOrderFile();
				$workOrderFile->setWorkOrderID($workOrder->getID());
				$workOrderFile->setFileName($handle->file_dst_name);
				$workOrderFile->insert();
			}
		}

		$linkarray = array();
		$num = 0;
		// now we can loop through $files, and feed each element to the class
		$numUploadedFiles=0;
		$fileStr='';
		foreach ($files as $fileInd=>$file) {
			$num++;

			// we instanciate the class for each element of $file
			$handle = new Upload($file);
				//print_r($handle);
				//exit;
			//$handle->allowed = array("application/*", "text/*", "plain/*", "image/*");
			$handle->allowed = array("*/*");
			$handle->mime_check = false;

			// then we check if the file has been uploaded properly
			// in its *temporary* location in the server (often, it is /tmp)
			if ($handle->uploaded) {
				$errMsgArr[$fileInd]='';
				$dir='';
				if ($errMsgArr[$fileInd]=='') {
					//print_r($handle);
					//exit;
					$imageData=getimagesize($file['tmp_name']);

					// if the file is an image, we resize it and create a thumbnail
					if($handle->file_is_image){
						// yes, the file is on the server
						// settings to adjust the image file
						$handle->image_resize            = true;
						$handle->image_x = 5000;
						$handle->image_y = 5000;
						$handle->image_ratio_no_zoom_in = true;
						//$handle->image_ratio           = true;

						// now, we start the upload 'process'. That is, to copy the uploaded file
						// from its temporary location to the wanted location
						//$handle->Process(SYSPATH."plan/attach/sys_".$_REQUEST['sysID']."/");
						$handle->Process(SYSPATH."plan/attach/sys_".$_REQUEST['sysID'].$dir);



						// we check if everything went OK
						if ($handle->processed) {
							// everything was fine !
							// add file: $handle->file_dst_name to table AttachFile
							// set AttachFile.isImage to 1
						} else {
							// one error occured
							//echo '<fieldset>';
							echo '  <br />'.$num.'. File not uploaded on the server<br />';
							echo '  Error: ' . $handle->error . '<br />';
							//echo '</fieldset>';
						}

						// we process the file a second time for the thumbnail
						// settings to adjust the image file
						$handle->file_new_name_body=$handle->file_dst_name_body;
						$handle->image_resize = true;
						$handle->image_x = 150;
						$handle->image_y = 150;	
						$handle->image_ratio_no_zoom_in = true;
						//$handle->image_ratio           = true;

						// we copy the file in the extra thumbnail folder
						$handle->Process(SYSPATH."plan/attach/sys_".$_REQUEST['sysID']."/thumbnail".$dir);
						// we check if everything went OK
						if ($handle->processed) {
							// everything was fine !
							// no need to add anything here
						} else {
							// one error occured
							//echo '<fieldset>';
							echo '  <br />'.$num.'. File not uploaded on the server<br />';
							echo '  Error: ' . $handle->error . '<br />';
							//echo '</fieldset>';
						}
					}else{

						$handle->no_script = false;

						// now, we start the upload 'process'. That is, to copy the uploaded file
						// from its temporary location to the wanted location
						$handle->Process(SYSPATH."plan/attach/sys_".$_REQUEST['sysID'].$dir);

						// we check if everything went OK
						if ($handle->processed) {
							// everything was fine !
							// add file: $handle->file_dst_name to database
						} else {
							// one error occured
							//echo '<fieldset>';
							echo '  <br />'.$num.'. File not uploaded on the server<br />';
							echo '  Error: ' . $handle->error . '<br />';

							//echo '</fieldset>';
						}
					}

				   // we delete the temporary files
					$handle-> Clean();
					$workOrderFile=new WorkOrderFile();
					$workOrderFile->setWorkOrderID($workOrder->getID());
					$workOrderFile->setFileName($handle->file_dst_name);
					$workOrderFile->insert();
				}
			}
		}

		$bldg=new BIMBldg($_POST['bldgID']);
		// Send mail {
		$toUser=new Contact();
		$toUser->load($contactID);
		require(SYSPATH.'plan/OPS/modulet/workOrderEmail.php');
		if ($siteInfo->getDisableWorkOrderReminderEmail()==0) {
			$to = $toUser->getEmail();
			sendAdministratorEmail($workOrder, $to);
		}
		if ($siteInfo->isUseEmergencyTechnician()) {
			$emergencyContact=new Contact();
			$emergencyContact->load($workOrderSetting->getEmergencyTechnicianContactID());
			sendTechnicianEmail($workOrder, $emergencyContact, $emergency=true);
		}
		if ($_POST['requestEmail']!='') {
			sendRequestorEmail($workOrder);
		}
		// }  End of Send mail
		header('location: request.php?'.getParams().'&submitted=1');
		return;
	}
}
if (($_GET['pastWorkOrders']==1) && ($email!='')) {
	if (isset($siteIDs)) $_SESSION['siteIDs']=$siteIDs;
	if ($siteID!=0) {
		$_SESSION['siteID']=$siteID;
	} else {
		unset($_SESSION['siteID']);
	}
	$_SESSION['sysID']=$sysID;
	header('location: request.php?'.getParams().'&requestEmail='.$email);
	return;
}

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
<?php if ($_GET['siteID']!='') { ?>
<script type="text/javascript" language="javaScript"><!--
$(document).ready(function(){ 
});
<?php if ($workOrderSetting->getRequestPassword()!='') { ?>
<?php if (isset($_COOKIE['requestPassword'])) { ?>
var passwordChecked=true;
<?php } else { ?>
var passwordChecked=false;
<?php } ?>
<?php } ?>
var imgArr=new Array();
var thumbnailArr=new Array();
var params = {
	name: '',
	requestEmail: '',
	requestTelephone: '',
	bldgID: '',
	floorID: '',
	spaceID: '',
	locationDescription: '',
	requestDescription: ''
};
	
	function listFloors() {
		var bldgID=document.forms['requestForm'].bldgID.value;
		document.forms['requestForm'].spaceID.options.length=0;
		document.forms['requestForm'].spaceID.disabled=true;
		document.forms['requestForm'].floorID.options.length=0;
		document.forms['requestForm'].floorID.disabled=(bldgID=='');
		if (bldgID=='') {
			document.forms['requestForm'].spaceID.options[document.forms['requestForm'].spaceID.options.length]=new Option('(Please select building)', '', true, true);
			document.forms['requestForm'].floorID.options[document.forms['requestForm'].floorID.options.length]=new Option('(Please select building)', '', true, true);
		} else {
			document.forms['requestForm'].spaceID.options[document.forms['requestForm'].spaceID.options.length]=new Option('(Please select floor)', '', true, true);
			document.forms['requestForm'].floorID.options[document.forms['requestForm'].floorID.options.length]=new Option('(Please select floor)', '', true, true);
			
		}
	<?php foreach ($bldgs as $bldg) { ?>
		if (bldgID==<?php echo $bldg->getID(); ?>) {
			<?php
			$bldgFloors=$bldg->getBIMFloors($desc=true, $relaod=true);
			$bldgInfo=$bldg->getBldgInfo();
			$flrCounter=count($bldgFloors)-$bldgInfo->getFirstFloor();
			foreach ($bldgFloors as $floor) {
				if ($flrCounter==0) $flrCounter--;
				if (trim($floor->getName())=='') $floorName='Floor '.$flrCounter;
				else $floorName=$floor->getName();
				?>
		document.forms['requestForm'].floorID.options[document.forms['requestForm'].floorID.options.length]=new Option('<?php
			echo htmlentities(str_replace("'", "\\'", str_replace("\\", "\\\\", $floorName)));
		?>', '<?php echo $floor->getID(); ?>');
				<?php
				$flrCounter--;
			}
			?>
		}
	<?php } ?>
	}
	
	function listSpaces(floorID) {
		var floorID=document.forms['requestForm'].floorID.value;
		document.forms['requestForm'].spaceID.options.length=0;
		document.forms['requestForm'].spaceID.disabled=false;
		document.forms['requestForm'].spaceID.options[document.forms['requestForm'].spaceID.options.length]=new Option('(Please select space)', '', true, true);
	<?php foreach ($bldgs as $bldg) {
			$bldgFloors=$bldg->getBIMFloors($desc=true, $relaod=true);
			foreach ($bldgFloors as $floor) {
				$floorSpaces=$floor->getBIMSpaces($reload=true, $orderBy='displayName');
				?>
		if (floorID==<?php echo $floor->getID(); ?>) {
		<?php
				foreach ($floorSpaces as $space) {
				?>
		document.forms['requestForm'].spaceID.options[document.forms['requestForm'].spaceID.options.length]=new Option('<?php
			echo htmlentities(str_replace("'", "\\'", str_replace("\\", "\\\\", ((trim($space->getSpaceNumber())!='')?$space->getSpaceNumber().' - ':'').$space->getName())));
		?>', '<?php echo $space->getID(); ?>');
				<?php
				}
				?>
				if (document.forms['requestForm'].spaceID.options.length==1) {
					document.forms['requestForm'].spaceID.options.length=0;
					document.forms['requestForm'].spaceID.options[document.forms['requestForm'].spaceID.options.length]=new Option('(No spaces on this floor)', '', true, true);
					document.forms['requestForm'].spaceID.disabled=true;
				}
		}
		<?php
			}
		} ?>
	}
	function checkSubmit() {
		var form=document.forms['requestForm'];
		if ($.trim(form.name.value)=='') {
			alert('Please provide your name.');
			form.name.focus();
			return false;
		}
		if ($.trim(form.requestEmail.value)=='') {
			alert('Please provide your email.');
			form.requestEmail.focus();
			return false;
		}
		<?php if ($workOrderSetting->getTelephoneRequired()) { ?>
		if ($.trim(form.requestTelephone.value)=='') {
			alert('Please provide your telephone.');
			form.requestTelephone.focus();
			return false;
		}
		<?php } ?>
        <?php if ($workOrderSetting->getShowLocation()) { ?>
		if ((form.bldgID.value=='') || ((form.spaceID.value=='') && ($.trim(form.locationDescription.value)==''))) {
			alert('Please provide the location.');
			if (form.bldgID.value=='') {
				form.bldgID.focus();
			} else if (!form.spaceID.disabled) {
				form.spaceID.focus();
			} else {
				form.locationDescription.focus();
			}
			return false;
		}
		<?php } else { ?>
		if ((form.bldgID.value=='') || (form.spaceID.value=='')) {
			alert('Please provide the location.');
			if (form.bldgID.value=='') {
				form.bldgID.focus();
			} else if (!form.spaceID.disabled) {
				form.spaceID.focus();
			}
			return false;
		}
		<?php } ?>
		if ($.trim(form.requestDescription.value)=='') {
			alert('Please describe the reason for your Work Order Request.');
			form.requestDescription.focus();
			return false;
		}
		
<?php if ($workOrderSetting->getRequestPassword()!='') { ?>
	var onCheckPassword=function (result) {
		if (result.responseText==1) {
			passwordChecked=true;
			<?php if ($workOrderSetting->getRequestPasswordDuration()>0) { ?>
			$.cookie("requestPassword", $('#requestPasswordTxt').val(), { expires: <?php echo $workOrderSetting->getRequestPasswordDuration(); ?> });
			<?php } ?>
			$('#submitBtn').attr('disabled', false);
			$('#submitBtn').click();
		} else {
			alert('Password is not correct');
			$('#requestPasswordTxt').focus();
			$('#submitBtn').attr('disabled', false);
			return false;
		}
	}
	if (!passwordChecked) {
		if ($.trim($('#requestPasswordTxt').val())=='') {
			alert('Please provide the password');
			$('#requestPasswordTxt').focus();
			return false;
		}
		$.ajax({
		  url: "request.php?<?php echo getParams(); ?>&checkPassword=1",
		  type: "POST",
		  data: {
			  "requestPassword": $('#requestPasswordTxt').val()
		  },
		  complete: onCheckPassword
		});
		return false;
	}
<?php } ?>
		$('#submitBtn').attr('disabled', true);
		return true;
	}




-->
</script>
<?php } ?>
<style>
<?php if ($_GET['siteID']!=0 && trim($workOrderSetting->getLogoURL())!='') { ?>
.headerTxtDiv{padding-left:10px;}
<?php } ?>
.site-btn{ padding-left:40px; padding-right:40px;}
</style>
</head>
<body>


<div class="container">
	<ul class="breadcrumb">
		<li><a href="requestlist.php?<?php echo getParams(); ?>">My Requests</a></li>
		<li class="active">Submit New Request</li>
		<div class="pull-right" style="padding-left:10px;">
        <a href="../plan/OpsBug.php?<?php echo getParams(); ?>" target="bugs">Bugs</a>
		</div>
	</ul>
        
<?php if ((isset($siteIDs)) && ($siteID==0)) { ?>
	<div class="pull-left headerTxtDiv">
		<h3 class="headerTxt1">Work Order Request</h3>
	</div>
	<div class="clearfix"></div>
	<p>
	Please select which site you would like to submit a work order request for:
	</p>
	<?php foreach($siteArr as $tmpSiteID=>$site) { ?>
	<div class="col-xs-12 text-center mb10">
		<input type="button" class="btn btn-default site-btn" onclick="document.location='request.php?<?php echo getParams(array('siteID'=>$site->getID())); ?>'" value="<?php
		echo htmlentities($site->getName());
		?>" />
	</div>
	<div class="clearfix"></div>
	<?php } ?>
<?php } else { ?>
	<div class="logo col-xs-12">
    <?php if (trim($workOrderSetting->getLogoURL())!='') { ?>
    	<div class="pull-left">
        <img src="<?php echo $workOrderSetting->getLogoURL(); ?>" border="0" />
        </div>
    <?php } ?>
    	<div class="pull-left headerTxtDiv">
            <h3 class="headerTxt1"><?php echo html($project->getName()); ?></h3>
            <h4 class="headerTxt2"><?php echo html($site->getName());
				if (isset($_GET['siteIDs'])) {
					?>
					<br />
					<small>(<a href="request.php?<?php echo getParams(array('siteID'=>'')); ?>">Select a different site</a>)</small>
					<?php
				}
			?></h4>
        </div>
	</div>
    <div class="clearfix"></div>
    <?php if (!$siteInfo->getEnableWorkOrders()) { ?>
	   <h4 class="text-danger text-center">The work orders system is not enabled for this scheme.</h4>
    <?php } else if ($_GET['submitted']==1) { ?>
	   <h4 class="text-success text-center">Your Work Order Request has been submitted.</h4>
		<p class="text-center">Click <a href="requestlist.php?<?php
			echo getParams();
		?>">here</a> to return to My Requests</p>
	 <?php } else { ?>
	<p>
	Please use the form below to fill out your contact details, and provide the details of the maintainence work required.
	</p>
            <form class="form-horizontal" name="requestForm" method="post" onsubmit="return checkSubmit();">
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Name of requestor:</label>
                    <div class="col-xs-12 col-sm-6"><input name="name" type="text" class="form-control input-sm" value="<?php echo html($fullName); ?>"<?php
                                                            ?> placeholder="Name" /></div>
					<div class="clearfix"></div>
                </div>
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Email:</label>
                    <div class="col-xs-12 col-sm-6"><input name="requestEmail" type="text" class="form-control input-sm" value="<?php echo html($email); ?>"<?php
                                                            ?> placeholder="Email" /></div>
                </div>
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Telephone:</label>
                    <div class="col-xs-12 col-sm-6"><input name="requestTelephone" type="text" class="form-control input-sm" value="<?php echo html($telephone); ?>"<?php
                                                            ?> placeholder="Telephone" /></div>
                </div>
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Location:</label>
                    <div class="col-xs-12 col-sm-6">
						<select name="bldgID" class="form-control input-sm mb5" onchange="listFloors();">
                            <option value="">(Please select building)</option>
                            <?php foreach ($bldgs as $bldg) { ?>
                            <option value="<?php echo $bldg->getID(); ?>"><?php echo htmlentities($bldg->getDisplayName()); ?></option>
                            <?php } ?>
                            </select>
                        <select name="floorID" disabled="disabled" class="form-control input-sm mb5" onchange="listSpaces();">
                            <option value="">(Please select building)</option>
                            </select>
                        <select name="spaceID" disabled="disabled" class="form-control input-sm mb5">
                            <option value="">(Please select building)</option>
                            </select>
						
                <?php if ($workOrderSetting->getShowLocation()) { ?>
                        <textarea name="locationDescription" class="form-control input-sm mb5" placeholder="Additional Location Information"></textarea>
                <?php } ?>
	                </div>
                </div>
				
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Request Description:</label>
                    <div class="col-xs-12 col-sm-6"><textarea name="requestDescription" class="form-control input-sm" value=""<?php
													?> placeholder="Please describe your work order request" ></textarea></div>
                </div>
                <?php if ($workOrderSetting->getShowChargeCode()) { ?>
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Charge Code:</label>
                    <div class="col-xs-12 col-sm-6"><input name="chargeCode" type="text" class="form-control input-sm" value="<?php echo html($chargeCode); ?>"<?php
                                                            ?> placeholder="Charge Code" /></div>
                </div>
                <?php } ?>
                <?php if ($workOrderSetting->getShowRequestType()) { ?>
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Request Type:</label>
                    <div class="col-xs-12 col-sm-6">
                        <select name="requestType" class="form-control input-sm mb5">
                            <option value="">(Please select request type)</option>
                            <option value="Building Maintenance">Building Maintenance</option>
                            <option value="Construction">Construction</option>
                            <option value="Custodial">Custodial</option>
                            <option value="Electrical">Electrical</option>
                            <option value="Elevators">Elevators</option>
                            <option value="Fleet Management">Fleet Management</option>
                            <option value="Heating, Ventilation &amp; Air Conditioning">Heating, Ventilation &amp; Air Conditioning</option>
                            <option value="Locks &amp; Door Hardware / Keys">Locks &amp; Door Hardware / Keys</option>
						<?php
							if (($site->getID()!=342) &&
								($site->getID()!=339) &&
								($site->getID()!=331) &&
								($site->getID()!=332)) { ?>
                            <option value="IT / Computers">IT / Computers</option>
						<?php } ?>
                            <option value="Occupant Moves">Occupant Moves</option>
                            <option value="Office &amp; Workstation Reconfiguration">Office &amp; Workstation Reconfiguration</option>
                            <option value="Vandalism">Vandalism</option>
                            <option value="Other">Other</option>
                        </select>
	                </div>
                </div>
                <?php } ?>
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Please attach any relevant photos:</label>
                    <div class="col-xs-12 col-sm-6">
						<input name="fileName[]" id="file_upload_input1" type="file" class="input-sm mb0" />
                        <input name="fileName[]" id="file_upload_input2" type="file" class="input-sm mb0" />
                        <input name="fileName[]" id="file_upload_input3" type="file" class="input-sm mb0" />
					</div>
                </div>
        <?php if ($workOrderSetting->getRequestPassword()!='') {
                if (!isset($_COOKIE['requestPassword'])) {
				?>
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Password:</label>
                    <div class="col-xs-12 col-sm-6">
                <?php
                    if ($_GET['incorrectPassword']==1) {
                        ?>
                <p class="form-control-static text-danger">
					<b>The stored password is incorrect</b>
				</p>
                        <?php
                    }
                ?>
						
						<input name="requestPassword" id="requestPasswordTxt" type="password" class="form-control input-sm mb5" />
					</div>
                </div>
                <?php
                } else {
                    ?>
                <input name="requestPassword" id="requestPasswordTxt" type="hidden" value="<?php echo $_COOKIE['requestPassword']; ?>" />
                <div class="form-group mb5">
                    <label class="control-label col-xs-12 col-sm-4">Password:</label>
                    <div class="col-xs-12 col-sm-6">
						<p class="form-control-static text-success">
							<b>The password has been stored by the browser</b>
						</p>
					</div>
                </div>
                    <?php
                }
                ?>
        <?php } ?>
		<center>
		<input name="submitBtn" id="submitBtn" type="submit" class="btn btn-default btn-sm mb5" value="Submit" style="width:150px;" />
		</center>
	</form>
	<!-- Modal Panel for Uploading New Attachment -->
	<div id="uploadModal" class="modal fade">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title text-center">Uploading...</h4>
			</div>
		</div>
	</div>
	</div>
	<?php } ?>
<?php } ?>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Onuma</p> 
</div>
</body>
</html>