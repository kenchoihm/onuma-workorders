<?php
include('config.php');
include('include.php');


include('../plan/authconfig.php');
include('../plan/OPS/include.php');


if ($_GET['email']=='') redirect('setEmail.php?'.getParams());
if ($_GET['workOrderID']==0) redirect('list.php?'.getParams());

$site=new BIMSite($_GET['siteID']);
$siteInfo=$site->getSiteInfo();
$workOrderSetting=$siteInfo->getWorkOrderSetting();
$project=new BIMProject($site->getProjectID());
$userID=$workOrderSetting->getAdministratorID();
if ($userID==0) {
	$userID=$project->getUserID();
}
$workOrder=new WorkOrder();
$workOrder->load($_GET['workOrderID']);
$prevStatus=$workOrder->getStatus();
$contactID=$workOrder->getAssignedContactID();
if ($workOrderSetting->getUseEmergencyTechnician()==1) {
	$contactID=$workOrderSetting->getEmergencyTechnicianContactID();
}
$technician=new Contact();
$technician->load($contactID);

$bldg=new BIMBldg($workOrder->getBldgID());
if ($workOrder->getFloorID()!=0) {
	$floorID=$workOrder->getFloorID();
	$floor=new BIMFloor($workOrder->getFloorID());
	$floorName='';
	if ($floor->getName()!='') {
		$floorName=$floor->getName();
	} else {
		$bldgInfo=$bldg->getBldgInfo();
		$bldgFlrs=$bldg->getBIMFloors($desc=true);
		$flrCounter=count($bldgFlrs)-$bldgInfo->getFirstFloor();
		foreach ($bldgFlrs as $bldgFlr) {
			if ($flrCounter==0) $flrCounter--;
			if ($bldgFlr->getID()==$floor->getID()) {
				$floorName='Floor '.$flrCounter;
				break;
			}
			$flrCounter--;
		}
	}
} else {
	$floorID=0;
	$floorName='';
}

if ($workOrder->getSpaceID()!=0) {
	$spaceID=$workOrder->getSpaceID();
	$space=new BIMSpace($workOrder->getSpaceID());
} else {
	$spaceID=0;
}

$typeArr=array();
$contactArr=array();
$equipmentArr=array();
$workOrderEquipmentArr=WorkOrderEquipment::getWorkOrderEquipmentArr('workOrderID='.$workOrder->getID());
foreach ($workOrderEquipmentArr as $workOrderEquipment) {
	$equipmentArr[$workOrderEquipment->getEquipmentID()]=new BIMEquipment($workOrderEquipment->getEquipmentID());
}


if ($_GET['checkPassword']==1) {
	echo (($_POST['assigneePassword_'.$site->getID()]==$workOrderSetting->getAssigneePassword())?1:0);
	exit;
}

if (isset($_REQUEST['action'])) {
	if ($_REQUEST['action']=='addTask') {
			if (($workOrderSetting->getAssigneePassword()=='') || ($workOrderSetting->getAssigneePassword()==$_POST['assigneePassword_'.$site->getID()])) {

				$costArr=json_decode($_POST['costStr']);
				if ($_POST['completed']==1) {
					$workOrder->setCompletedContactID($_POST['completedContactID']);
					$workOrder->setStatus('Completed');
					$workOrder->setCompletedDate(date('Y-m-d H:i:s'));
				} else {
					$workOrder->setStatus('Work in progress');
				}
				$workOrder->update();
				
				$workOrderTask=new WorkOrderTask();
				$completedHours=($_POST["completedHours"]+$_POST['completedFractionHours']);
				if ($completedHours!=0 || $_POST["description"]!='' || (count($costArr)>0)) {
					$contact=new Contact();
					$contact->load($_POST['completedContactID']);
					$workOrderTask->setHours($completedHours);
					$workOrderTask->setAssignedContactID($_POST['completedContactID']);
					$workOrderTask->setWorkOrderID($workOrder->getID());
					$workOrderTask->setDate(date('Y-m-d H:i:s'));
					$workOrderTask->setDescription($_POST["description"]);
					$workOrderTask->setIsOvertime((isset($_POST["isOvertime"])?1:0));
					$rate=((isset($_POST['isOvertime']))?$contact->getOvertimeHourlyRate():$contact->getHourlyRate());
					$workOrderTask->setRate($rate);
					$workOrderTask->insert();
					foreach ($costArr as $cost){
						$workOrderCost=new WorkOrderCost();
						$workOrderCost->setWorkOrderTaskID($workOrderTask->getID());
						$workOrderCost->setDate(date('Y-m-d'));
						$workOrderCost->setCost($cost[1]);
						$workOrderCost->setDescription($cost[0]);
						$workOrderCost->insert();
					}
				}
				
	$statusChanged=0;
		if (($prevStatus!=$workOrder->getStatus()) && ($_POST['completed']==1)) {
		// Send email to administrator ---------------------------------------------------------------
$bldg=new BIMBldg($workOrder->getBldgID());
$link2=FULLURL.'/plan/OPS/editFloor.php?sysID='.$_REQUEST['sysID'].'&bldgID='.$bldg->getID();
if ($workOrder->getFloorID()!=0) {
	$floor=new BIMFloor($workOrder->getFloorID());
	$floorName='';
	if ($floor->getName()!='') {
		$floorName=$floor->getName();
	} else {
		$bldgInfo=$bldg->getBldgInfo();
		$bldgFlrs=$bldg->getBIMFloors($desc=true);
		$flrCounter=count($bldgFlrs)-$bldgInfo->getFirstFloor();
		foreach ($bldgFlrs as $bldgFlr) {
			if ($flrCounter==0) $flrCounter--;
			if ($bldgFlr->getID()==$floor->getID()) {
				$floorName='Floor '.$flrCounter;
				break;
			}
			$flrCounter--;
		}
	}
	$link2=FULLURL.'/plan/OPS/editFloor.php?sysID='.$_REQUEST['sysID'].'&ID='.$floor->getID().'&bldgID='.$bldg->getID();
}
if ($workOrder->getSpaceID()!=0) {
	$space=new BIMSpace($workOrder->getSpaceID());
	$link2=FULLURL.'/plan/OPS/editSpace.php?sysID='.$_REQUEST['sysID'].'&ID='.$space->getID().'&bldgID='.$bldg->getID();
}

$userID=$workOrderSetting->getAdministratorID();
if ($userID==0) {
	$project=new BIMProject($site->getProjectID());
	$userID=$project->getUserID();
}
$admin=new Signup();
$admin->load($userID);

		require(SYSPATH.'plan/OPS/modulet/workOrderEmail.php');
		if ($siteInfo->getDisableWorkOrderReminderEmail()==0) {
			sendCompletedEmailToAdmin($workOrder);
		}
		if (trim($workOrder->getRequestEmail())!='') {
			sendCompletedEmailToRequestor($workOrder);
		}
		//  ---------------------------------------------------------------
		$statusChanged=1;
	}
				header('location: technician.php?'.getParams());
				return;
			}
	}
	if ($_REQUEST['action']=='addFile') {
		include('../plan/includes/class.upload.php');
	
		// we instanciate the class for each element of $file
		$handle = new Upload($_FILES['attachFile']);
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
	if ($_REQUEST['action']=='deleteWorkOrderEquipment') {
		$workOrderEquipmentArr=WorkOrderEquipment::getWorkOrderEquipmentArr('workOrderID='.$workOrder->getID()." AND equipmentID=".addslashes($_GET['equipmentID']));
		if (count($workOrderEquipmentArr)) {
			foreach ($workOrderEquipmentArr as $workOrderEquipment) {
				$workOrderEquipment->delete();
			}
		}
		header('location: technician.php?'.getParams());
		return;
	}
}
if (isset($_COOKIE['assigneePassword_'.$site->getID()])) {
	if ($workOrderSetting->getAssigneePassword()!=$_COOKIE['assigneePassword_'.$site->getID()]) {
		setcookie('assigneePassword_'.$site->getID(), "", time() - 3600);
		unset($_COOKIE['assigneePassword_'.$site->getID()]);
		header('location: technician.php?'.getParams(array('incorrectPassword'=>1)));
		return;
	}
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
<script type="text/javascript" language="javaScript"><!--
var costArr=[];
var updateCost=function() {
	var html='';
	html+='<table class="table" id="costTable">';
	if (costArr.length>0) {
	html+='<thead>';
	html+='<tr>';
	html+='<th class="col-xs-5 active">Description</th>';
	html+='<th class="col-xs-5 active">Cost</th>';
	html+='<th class="col-xs-2 active"></th>';
	html+='</tr>';
	html+='</thead>';
	}
	html+='<tbody>';
	$.each(costArr, function(k, v){
		html+='<tr>';
		html+='<td>'+v[0]+'</td>';
		html+='<td><?php echo $currencyArr[$siteInfo->getCurrency()]; ?>'+v[1]+'</td>';
		html+='<td class="pull-right"><a class="badge" href="javascript:removeCost('+k+');">&times;</a></td>';
		html+='</tr>';
	});
	html+='</tbody>';
	
	html+='<tfoot>';
	html+='<tr>';
	html+='<td class="col-xs-5"><input type="text" class="form-control" placeholder="Description" id="costDescriptionTxt"></td>';
	html+='<td class="col-xs-5">';
	html+='<div class="input-group">';
	html+='<span class="input-group-addon"><?php echo $currencyArr[$siteInfo->getCurrency()]; ?></span>';
	html+='<input type="number" class="form-control" placeholder="Cost" id="costTxt" onblur="fixCost();">';
	html+='</div>';
	html+='</td>';
	html+='<td class="col-xs-2"><button type="button" class="btn btn-default col-xs-12" onclick="addCost();">Add</button></td>';
	html+='</tr>';
	html+='</tfoot>';
	html+='</table>';
	$('#costDiv').html(html);

}
var addCost=function() {
	var d=$('#costDescriptionTxt').val();
	var c=parseFloat($('#costTxt').val());
	if ($.trim(d)=='') {
		alert('Please provide the description');
		$('#costDescriptionTxt').select();
		return;
	}
	if (isNaN(c)) {
		alert('Please provide the cost');
		$('#costTxt').select();
		return;
	}
	costArr.push([d,c]);
	updateCost();
}
var removeCost=function(k) {
	costArr.splice(k,1);
	updateCost();
}
var fixCost=function() {
	var c=parseFloat($('#costTxt').val());
	if (isNaN(c)) {
		$('#costTxt').val('');
	} else {
		$('#costTxt').val(c);
	}
}
var showCostDetails=function(c, t) {
	var html='';
	html+='<table class="table">';
	html+='<thead>';
	html+='<tr>';
	html+='<th class="col-xs-6 active">Description</th>';
	html+='<th class="col-xs-6 active">Cost</th>';
	html+='</tr>';
	html+='</thead>';
	html+='<tbody>';
	$.each(c, function(k, v){
		html+='<tr>';
		html+='<td>'+v[0]+'</td>';
		html+='<td>'+v[1]+'</td>';
		html+='</tr>';
	});
	html+='</tbody>';
	html+='<tfooter>';
	html+='<tr>';
	html+='<td><b>Total</b></td>';
	html+='<td><b>'+t+'</b></td>';
	html+='</tr>';
	html+='</tfooter>';
	html+='</table>';
	$('#costContentDiv').html(html);
	$('#costDetailsModal').modal('show');
}

var componentArr={};
<?php
$furnDataArr=FurnData::getFurnDataArr('', '', 'furnName');
foreach ($equipmentArr as $equipment) {
	$obj=new stdclass();
	$obj->displayName=$equipment->getDisplayName();
	$obj->name=$equipment->getName();
	$obj->componentName=$equipment->getComponentName();
	$obj->serialNumber=$equipment->getSerialNumber();
	if ($obj->serialNumber==0) $obj->serialNumber='';
	$obj->installationDate=(($equipment->getInstallationDate()==0)?'':date('Y-m-d', $equipment->getInstallationDate()));
	if ($equipment->getTypeID()!=0) {
		if (!isset($typeArr[$equipment->getTypeID()])) {
			$typeArr[$equipment->getTypeID()]=new CobieType();
			$typeArr[$equipment->getTypeID()]->load($equipment->getTypeID());
			$type=$typeArr[$equipment->getTypeID()];
			if ($type->getManufacturer()!=0) {
				if (!isset($contactArr[$type->getManufacturer()])) {
					$contactArr[$type->getManufacturer()]=new Contact();
					$contactArr[$type->getManufacturer()]->load($type->getManufacturer());
				}
			}
			if ($type->getPartsWarrantyGuarantor()!=0) {
				if (!isset($contactArr[$type->getPartsWarrantyGuarantor()])) {
					$contactArr[$type->getPartsWarrantyGuarantor()]=new Contact();
					$contactArr[$type->getPartsWarrantyGuarantor()]->load($type->getPartsWarrantyGuarantor());
				}
			}
			if ($type->getLabourWarrantyGuarantor()!=0) {
				if (!isset($contactArr[$type->getLabourWarrantyGuarantor()])) {
					$contactArr[$type->getLabourWarrantyGuarantor()]=new Contact();
					$contactArr[$type->getLabourWarrantyGuarantor()]->load($type->getLabourWarrantyGuarantor());
				}
			}
			
		}
		$type=$typeArr[$equipment->getTypeID()];
		$obj->typeName=$type->getTypeName();
		if ($type->getManufacturer()!=0) {
			$obj->manufacturer=$contactArr[$type->getManufacturer()]->getCompany();
		} else {
			$obj->manufacturer=='';
		}
		$obj->modelNumber=$type->getModelNumber();
		if ($type->getPartsWarrantyGuarantor()!=0) {
			$obj->partsWarrantyGuarrantor=$contactArr[$type->getPartsWarrantyGuarantor()]->getCompany();
		} else {
			$obj->partsWarrantyGuarrantor='';
		}
		$obj->partsWarrantyDuration=$type->getPartsWarrantyDuration().' '.$type->getWarrantyDurationUnit();
		if ($obj->partsWarrantyDuration==' ') $obj->partsWarrantyDuration='';
		if ($type->getLabourWarrantyGuarantor()!=0) {
			$obj->labourWarrantyGuarrantor=$contactArr[$type->getLabourWarrantyGuarantor()]->getCompany();
		} else {
			$obj->labourWarrantyGuarrantor='';
		}
		$obj->labourWarrantyDuration=$type->getLabourWarrantyDuration().' '.$type->getWarrantyDurationUnit();
		if ($obj->labourWarrantyDuration==' ') $obj->labourWarrantyDuration='';
	} else {
		$obj->typeName='';
		$obj->manufacturer=='';
		$obj->modelNumber=='';
		$obj->partsWarrantyGuarrantor='';
		$obj->partsWarrantyDuration='';
		$obj->labourWarrantyGuarrantor=='';
		$obj->labourWarrantyDuration=='';
	}
	if (isset($furnDataArr[$equipment->getFurnName()])) {
		$obj->imageURL="/plan/OPS/furnPics/".$furnDataArr[$equipment->getFurnName()]->getImgFile().".jpg";
	} else {
		$obj->imageURL="/plan/OPS/furnPics/placeholder.jpg";
	}	
	?>
componentArr["<?php echo $equipment->getID(); ?>"]=<?php echo json_encode2($obj); ?>;
	<?php
}
?>

$(document).ready(function(){ 
	updateCost();				   
	$("#uploadModal").on('hide.bs.modal', function(){
		return false;
    });
});

var passwordChecked=false;
function checkSubmit(){
	$('#addTaskSubmitBtn').attr('disabled', true);
<?php if ($workOrderSetting->getAssigneePassword()!='') { ?>
	var onCheckPassword=function (result) {
		if (result.responseText==1) {
			passwordChecked=true;
			<?php if ($workOrderSetting->getAssigneePasswordDuration()>0) { ?>
			$.cookie("assigneePassword_<?php echo $site->getID(); ?>", $('#assigneePasswordTxt').val(), { expires: <?php echo $workOrderSetting->getAssigneePasswordDuration(); ?> });
			<?php } ?>
			$('#addTaskSubmitBtn').attr('disabled', false);
			$('#addTaskSubmitBtn').click();
		} else {
			alert('Password is not correct');
			$('#assigneePasswordTxt').focus();
			$('#addTaskSubmitBtn').attr('disabled', false);
			return false;
		}
	}
	if (!passwordChecked) {
		if ($.trim($('#assigneePasswordTxt').val())=='') {
			alert('Please provide the password');
			$('#assigneePasswordTxt').focus();
			$('#addTaskSubmitBtn').attr('disabled', false);
			return false;
		}
		$.ajax({
		  url: "technician.php?<?php echo getParams(); ?>&checkPassword=1",
		  type: "POST",
		  data: {
			  "assigneePassword_<?php echo $site->getID(); ?>": $('#assigneePasswordTxt').val()
		  },
		  complete: onCheckPassword
		});
		return false;
	}
<?php } ?>
	if (!confirm('Are you sure you want to submit the task?'+
				'\n(This will not be editable after submission)')) {
		$('#addTaskSubmitBtn').attr('disabled', false);
		return false;
	}
	$('#costStr').val(JSON.stringify(costArr));
	$('#addTaskSubmitBtn').attr('disabled', false);
	return true;
}

--></script>
<style>
<?php if (trim($workOrderSetting->getLogoURL())!='') { ?>
.headerTxtDiv{padding-left:10px;}
<?php } ?>
.component-btn{ padding-left:40px; padding-right:40px;}
.delete-btn{ padding-left:10px; padding-right:10px;}
</style>
</head>
<body>


<div class="container">
	<ul class="breadcrumb">
		<?php if (strtolower(trim($technician->getEmail()))==strtolower(trim($_GET['email']))) { ?>
		<li><a href="list.php?<?php echo getParams(); ?>">My Task Assignments</a></li>
		<li class="active">151-106</li>
		<?php } else { ?>
		<li>&nbsp;</li>
		<?php } ?>
		<div class="pull-right" style="padding-left:10px;">
        <a href="../plan/OpsBug.php?<?php echo getParams(); ?>" target="bugs">Bugs</a>
		</div>
		<div class="pull-right">
			<a title="Print" href="../plan/OPS/modulet/printWorkOrder.php?<?php echo getParams(); ?>" target="print"><img src="../plan/OPS/images/printSM.gif" alt="Print" height="18" width="18" border="0"></a>
		</div>
	</ul>
	<div class="logo col-xs-12">
    <?php if (trim($workOrderSetting->getLogoURL())!='') { ?>
    	<div class="pull-left">
        <img src="<?php echo $workOrderSetting->getLogoURL(); ?>" border="0" />
        </div>
    <?php } ?>
    	<div class="pull-left headerTxtDiv">
            <h3 class="headerTxt1"><?php echo html($project->getName()); ?></h3>
            <h4 class="headerTxt2"><?php echo html($site->getName()); ?></h4>
            <h4 class="headerTxt3"><small><?php echo html($bldg->getDisplayName()); ?></small></h4>
        </div>
	</div>
    <div class="clearfix"></div>
    
<?php if ((strtolower(trim($technician->getEmail()))!=strtolower(trim($_GET['email']))) &&
		  (strpos(strtolower(trim($technician->getCc())), strtolower(trim($_GET['email'])))===FALSE)) { ?>
    <p class="text-danger text-center"><b>You don't have privilege to access this work order</b></p>
	<p class="text-center"><small>Click <a href="list.php?<?php echo getParams(); ?>">here</a> to return to my other task assignments</small></p>
<?php } else {?>
    
<div class="panel-group">
        <div class="panel panel-default">
            <div class="panel-heading"><a href="javascript:;" data-toggle="collapse" data-target="#requestDetailDiv">Request Details</a></div>
            <div id="requestDetailDiv" class="collapse in">
            <form class="form-horizontal">
                <div class="form-group">
                    <label class="control-label col-xs-4">Request Number:</label>
                    <div class="col-xs-8"><p class="form-control-static"><?php echo $workOrder->getRequestNumber(); ?></p></div>
                </div>
                <div class="form-group">
                    <label class="control-label col-xs-4">Request Date:</label>
                    <div class="col-xs-8"><p class="form-control-static"><?php echo date('m/d/y', strtotime($workOrder->getRequestDate())); ?></p></div>
                </div>
                <div class="form-group">
                    <label class="control-label col-xs-4">Priority:</label>
                    <div class="col-xs-8"><p class="form-control-static"><?php echo htmlentities($priorityArr[$workOrder->getAssignedPriority()]); ?></p></div>
                </div>
                <div class="col-xs-12 text-center" style="margin-top:20px;">
<script type="text/javascript" language="JavaScript"><!--

function onLoadComplete() {
	var style="fill: #FF0000;";
	<?php if ($spaceID!=0) { ?>
	changeCurveProfileStyle('SPACE_<?php echo $spaceID; ?>',style);
	<?php } ?>
}
--></script>
                <iframe frameborder="0" width="380" height="400" src="/plan/OPS<?php
						if (!$local) echo '-beta';
					?>/SVG2/getDataSVG.php?sysID=<?php
						echo $_GET['sysID']; ?>&bldgID=<?php echo $workOrder->getBldgID(); ?>&floorID=<?php
							echo (($floorID==0)?$bldg->getFirstFloorID():$floorID);
						?>&width=380&height=400&hideSpaceKey=1"></iframe>
                </div>
                <div class="form-group">
                    <label class="control-label col-xs-4">Submitted By:</label>
                    <div class="col-xs-8"><p class="form-control-static">
                        <?php echo html($workOrder->getSubmittedBy()); ?>
						<?php if (trim($workOrder->getRequestEmail())!='') { ?>
                        &nbsp;(<a href="mailto:<?php echo ($workOrder->getRequestEmail()); ?>"><?php echo html($workOrder->getRequestEmail()); ?></a>)
                        <?php } ?>
						<?php if (trim($workOrder->getRequestTelephone())!='') { ?>
                        <br /><?php echo html($workOrder->getRequestTelephone()); ?>
                        <?php } ?>
                    </p></div>
                </div>
                <div class="form-group">
                    <label class="control-label col-xs-4">Location:</label>
                    <div class="col-xs-8"><p class="form-control-static">
                        <?php echo html($bldg->getDisplayName()); ?>
						<?php if ($workOrder->getFloorID()!=0) { ?>
                        <br /><?php echo html($floorName); ?>
                        <?php } ?>
						<?php if ($workOrder->getSpaceID()!=0) { ?>
                        <br /><?php echo html($space->getDisplayName()); ?>
                        <?php } ?>
						<?php if (trim($workOrder->getLocationDescription())!='') { ?>
                        <br /><?php echo nl2br(html($workOrder->getLocationDescription())); ?>
                        <?php } ?>
                    </p></div>
                </div>
                <div class="form-group">
                    <label class="control-label col-xs-4">Request Description:</label>
                    <div class="col-xs-8"><p class="form-control-static">
                        <?php echo nl2br(htmlentities($workOrder->getRequestDescription())); ?>
                    </p></div>
                </div>
                
	<?php
	if (count($workOrderEquipmentArr)>0) {
		?>
        <?php
		for ($i=0; $i<count($workOrderEquipmentArr); $i++) {
			$equipment=$equipmentArr[$workOrderEquipmentArr[$i]->getEquipmentID()];
		?>
        <div class="form-group">
            <label class="control-label col-xs-4"><?php if ($i==0) echo 'Components:'; ?></label>
			<div class="btn-group col-xs-8">
				<button type="button" class="btn btn-sm btn-default component-btn" onClick="
					loadModalContent(<?php echo $equipment->getID(); ?>);
					$('#componentDetailsModal').modal('show');
				" ><?php echo html($equipment->getDisplayName()); ?></button>
				<button type="button" class="btn btn-sm btn-default delete-btn" onClick="
					document.location='technician.php?<?php echo getParams(); ?>&action=deleteWorkOrderEquipment&equipmentID=<?php echo $equipment->getID(); ?>';
				" >&nbsp;&times;&nbsp;</button>
			</div>
        </div>
        <div class="spacer5"></div>
        <?php
		}
		?>
        <div class="spacer15"></div>
        <?php
	}
	foreach ($workOrderEquipmentArr as $workOrderEquipment) {
		$equipment=new BIMEquipment($workOrderEquipment->getEquipmentID());
	}
	
	?>
		<?php if (strtolower(trim($technician->getEmail()))==strtolower(trim($_GET['email']))) { ?>
			<?php if (($workOrder->getStatus()!='Completed') &&
                        ($workOrder->getStatus()!='Completed Confirmed')) { ?>
             <div class="text-center" style="margin-bottom:20px;"><button type="button" class="btn btn-default" style="width:150px;" onClick="document.location='search.php?<?php echo getParams(); ?>';">Attach Commponent</button></div>
             <?php } ?>
		 <?php } ?>
            </form>
            </div>
        </div>
        <div class="spacer10"></div>
		<?php
        $workOrderTaskArr=WorkOrderTask::getWorkOrderTaskArr('workOrderID='.$workOrder->getID());
        if (count($workOrderTaskArr)) {
        ?>
        <form class="form-horizontal">
        <div class="panel panel-default">
            <div class="panel-heading"><a href="javascript:;" data-toggle="collapse" data-target="#previousTasksDiv"><?php
            if (($workOrder->getStatus()=='Completed') ||
                ($workOrder->getStatus()=='Completed Confirmed')) {
                echo 'Task Details';
            } else {
                echo 'Previous Tasks';
            }
            ?></a></div>
            <div id="previousTasksDiv" class="collapse in table-responsive">
                <table class="table" style="margin-bottom:0px;">
                    <thead>
                        <tr>
                            <th class="col-xs-4 active">Description</th>
                            <th class="col-xs-2 active">Hours</th>
                            <th class="col-xs-2 active">Material Costs</th>
                            <th class="col-xs-2 active">Date</th>
                            <th class="col-xs-2 active">By</th>
                        </tr>
                    </thead>
                    <tbody>
					   <?php
                       $technicianArr=array();
					   $totalHours=0;
					   $totalCost=0;
                       foreach ($workOrderTaskArr as $workOrderTask) {
                           ?>
                           <tr>
                                <td><?php echo nl2br(htmlentities($workOrderTask->getDescription())); ?> </td>
                                <td><?php echo $workOrderTask->getHours(); ?></td>
                                <td>
                                <?php
                                $workOrderCostArr=WorkOrderCost::getWorkOrderCostArr('workOrderTaskID='.$workOrderTask->getID());
                                $cost=0;
                                foreach ($workOrderCostArr as $workOrderCost) {
                                    $cost+=$workOrderCost->getCost();
                                }
								$totalHours+=$workOrderTask->getHours();
                                $totalCost+=$cost;
								
                                if (count($workOrderCostArr)) {
                                ?>
	                            <a href="javascript:;" onClick="
                                var c=[], t=[];
                                <?php foreach ($workOrderCostArr as $workOrderCost) { ?>
                                    c.push(['<?php
                                    	$desc=$workOrderCost->getDescription();
										$desc=str_replace("\n", "\\n", $desc);
										echo html($desc);
									?>', '<?php
										echo $currencyArr[$siteInfo->getCurrency()];
										echo number_format($workOrderCost->getCost(), 2, '.', ',');
									?>']);
								<?php } ?>
                                	showCostDetails(c, '<?php
										echo $currencyArr[$siteInfo->getCurrency()];
										echo number_format($cost, 2, '.', ',');
									?>');
                                    
                                ;"><span class="badge">+</span>
                                <?php
                                }
                                echo $currencyArr[$siteInfo->getCurrency()];
                                echo number_format($cost, 2, '.', ',');
                                if (count($workOrderCostArr)) {
                                ?>
	                            </a>
                                <?php } ?>
                                </td>
                                <td><?php echo date('m/d/y', strtotime($workOrderTask->getDate())); ?></td>
                                <td><?php
                                $contactID=$workOrderTask->getAssignedContactID();
                                if (!isset($technicianArr[$contactID])) {
                                    $tempTechnician=new Contact();
                                    $tempTechnician->load($contactID);
                                    $technicianArr[$contactID]=$tempTechnician;
                                }
                                    echo htmlentities($technicianArr[$contactID]->getFname().' '.
                                                        $technicianArr[$contactID]->getLname());
                                ?></td>
                           </tr>
                           <?php
                       }
                       ?>
                    </tbody>
                    <tfoot>
                    	<tr>
                                <td class="active"><b>Total</b></td>
                                <td class="active"><b><?php echo $totalHours; ?></b></td>
                                <td class="active">
                                <b><?php
                                echo $currencyArr[$siteInfo->getCurrency()];
                                echo number_format($totalCost, 2, '.', ',');
                                ?></b>
                                </td>
                                <td class="active"></td>
                                <td class="active"></td>
                           </tr>
                    </tfoot>
                </table>
            <?php
            if (($workOrder->getStatus()=='Completed') ||
                ($workOrder->getStatus()=='Completed Confirmed')) {
				?>
                <div class="form-group">
                    <label class="control-label col-xs-4">Task Status:</label>
                    <div class="col-xs-8"><p class="form-control-static"><?php echo ($workOrder->getStatus()=='Completed'?'Completed (To be confirmed by requestor)':'Completed and confirmed by requestor'); ?></p></div>
                </div>
                <?php
			}
			?>
            </div>
        </div>
        </form>
        <div class="spacer10"></div>
        <?php } ?>
        
		<?php
        if (($workOrder->getStatus()!='Completed') &&
            ($workOrder->getStatus()!='Completed Confirmed')) {
        ?>
        <div class="panel panel-default">
            <div class="panel-heading"><a href="javascript:;" data-toggle="collapse" data-target="#assignmentDetailDiv">Task Details</a>
            </div>
            <div id="assignmentDetailDiv" class="collapse in">
            <form class="form-horizontal" style="padding-right:5px;" onSubmit="return checkSubmit();" method="post">
                <input type="hidden" name="action" value="addTask" />
                <input type="hidden" name="costStr" id="costStr" />
                <!--<div class="form-group">
                    
                    <div class="clearfix"></div>
                    <label class="control-label col-xs-4">Assigned Hours:</label>
                    <div class="col-xs-8"><p class="form-control-static"><?php echo htmlentities($workOrder->getAssignedHours()); ?></p></div>
                </div>-->
				<div class="form-group">
                    
                    <div class="clearfix"></div>
                    <label class="control-label col-xs-4">Assigned To:</label>
                    <div class="col-xs-8"><p class="form-control-static">
                        <?php echo html($technician->getDisplayName()); ?>
						<?php if (trim($technician->getEmail())!='') { ?>
                        &nbsp;(<a href="mailto:<?php echo ($technician->getEmail()); ?>"><?php echo html($technician->getEmail()); ?></a>)
                        <?php } ?>
					</p></div>
                </div>
			<?php if (strtolower(trim($technician->getEmail()))==strtolower(trim($_GET['email']))) { ?>
                <div class="form-group">
                        <label class="control-label col-xs-4">Completed By:</label>
                        <div class="col-xs-8">
                            <select class="form-control input-sm" name="completedContactID">
                                    <option value="<?php echo $technician->getID(); ?>" selected="selected"><?php
                                        echo $technician->getFname().' '.$technician->getLname().' (Myself)';
                                    ?></option>
                            <?php
                            $sql="SELECT contact.* FROM Bldg 
                                    JOIN BldgContact ON Bldg.ID=BldgContact.bldgID
                                    JOIN onuma_plan.contact ON BldgContact.contactID=contact.ID
                                    WHERE siteID=".$site->getID()."
                                    AND role='O&M'
                                    GROUP BY contact.ID
                                    ORDER BY contact.lname, contact.fname, contact.email
                                    ";
                            $conn=new Conn();
                            $conn->execute($sql);
                            while ($conn->next()) {
                                $contact=new Contact();
                                $contact->populate($conn->row);
                                if ($contactID==$contact->getID()) continue;
                                 ?>
                                    <option value="<?php echo $contact->getID(); ?>" ><?php
                                        echo $contact->getFname().' '.$contact->getLname().' ('.$contact->getEmail().')';
                                    ?></option>
                                <?php 
                            }
                            ?>
                            </select>
                        </div>
                </div>
                <div class="form-group">
                        <label class="control-label col-xs-4">Actual Hours:</label>
                        <div class="col-xs-3" style="padding-right:0px;">
                            <select class="form-control input-sm" name="completedHours">
							<?php for ($i=0; $i<=12; $i++) { ?>
                                <option value="<?php echo $i; ?>"<?php /*if ((floor($workOrder->getCompletedhours()))==$i) echo ' selected="selected"';*/ ?>><?php echo $i; ?> hr</option>
                            <?php } ?>
                            </select>
                        </div>
                        <div class="col-xs-3" style="padding-left:0px;">
                            <select name="completedFractionHours" class="form-control input-sm">
                            <?php
                                $values=array(0, 0.25, 0.5, 0.75);
                                $fraction=$workOrder->getCompletedhours() - floor($workOrder->getCompletedhours());
                                foreach ($values as $value) {
                                ?>
                                <option value="<?php echo $value; ?>"<?php /*if ($fraction==$value) echo ' selected="selected"';*/ ?>><?php echo ($value*60); ?> mins</option>
                            <?php } ?>
                            </select>
                        </div>
            <div class="col-xs-2 checkbox">
                                <label><input type="checkbox" id="isOvertimeChk" name="isOvertime"> Overtime</label>
                        </div>
                </div>
                <div class="form-group">
                        <label class="control-label col-xs-4">Description:</label>
                        <div class="col-xs-8">
                            <textarea class="form-control" name="description" style="height:100px;"></textarea>
                        </div>
                </div>
                <div class="form-group">
                        <label class="control-label col-xs-4">Material Costs:</label>
                        <div class="col-xs-8" id="costDiv"></div>
                </div>
                <div class="form-group">
					<?php if ($workOrder->getStatus()=='Assigned') { ?>
                        <label class="control-label col-xs-4">Task Status:</label>
                        <div class="col-xs-8 checkbox">
                                <label><input type="radio" name="completed" value="0" checked="checked" /> Assigned</label>
                        </div>
					<?php } ?>
                    <div class="clearfix"></div>
                        <label class="control-label col-xs-4"><?php
									if ($workOrder->getStatus()!='Assigned') echo 'Task Status:';
                                ?></label>
                        <div class="col-xs-8 checkbox">
                                <label><input type="radio" name="completed" value="0"<?php if ($workOrder->getStatus()!='Assigned') echo ' checked="checked"'; ?> /> In Progress</label>
                        </div>
                    <div class="clearfix"></div>
                        <label class="control-label col-xs-4"></label>
                        <div class="col-xs-8 checkbox">
                                <label><input type="radio" name="completed" value="1"<?php if (($workOrder->getStatus()=='Completed') ||
																					($workOrder->getStatus()=='Completed Confirmed')) echo ' checked="checked"'; ?> />
                                    Completed</label>
                        </div>
                </div>
<?php if ($workOrderSetting->getAssigneePassword()!='') { ?>
        <?php
		if (!isset($_COOKIE['assigneePassword_'.$site->getID()])) {
		?>
        <div class="form-group<?php if ($_GET['incorrectPassword']==1) echo ' has-error'; ?>">
                <label class="control-label col-xs-4">Password:</label>
                <div class="col-xs-8"><input class="form-control" name="assigneePassword_<?php echo $site->getID(); ?>" id="assigneePasswordTxt" type="password" />
        <?php if ($_GET['incorrectPassword']==1) { ?>
            <span class="help-block">The stored password is incorrect</span>
        <?php } ?>
		        </div>
        </div>
        <?php
		} else {
			?>
        <div class="form-group">
                <label class="control-label col-xs-4">Password:</label>
                <div class="col-xs-8"><p class="form-control-static"><i>The password has been stored by the browser</i></p></div>
        </div>
            <?php
		}
		?>
<?php } ?>
                <div class="form-group" style="margin-bottom:20px;">
                    <div class="col-xs-12 text-center">
                        <button type="submit" class="btn btn-default" style="width:150px;" id="addTaskSubmitBtn">Save</button>
                    </div>
                </div>
			<?php } else { ?>
	        <div class="form-group">
				<label class="control-label col-xs-4">Task Status:</label>
				<div class="col-xs-8"><p class="form-control-static"><?php echo htmlentities($workOrder->getStatus()); ?></p></div>
			</div>
			<?php } ?>
            </form>
            </div>
        </div>
        <div class="spacer10"></div>
        <?php } else { ?>
        <?php } ?>
        <?php
        $description='';
        if ($workOrder->getEquipmentID()!=0) {
            $description=$siteInfo->getEquipmentDescription();
            $description=str_replace("[Capacity]", $equipment->getCapacity(), $description);
            $description=str_replace("[Electrical]", $equipment->getElectrical(), $description);
            $description=str_replace("[FuelConsumption]", $equipment->getFuelConsumption(), $description);
            $description=str_replace("[Power]", $equipment->getPower(), $description);
            switch ($equipment->getPowerOn()) {
                case 1: { $description=str_replace("[On/Off]", 'On', $description); break; }
                case 0: { $description=str_replace("[On/Off]", 'Off', $description); break; }
                case -1: { $description=str_replace("[On/Off]", 'n/a', $description); break; }
            }
            //if ((!isset($_REQUEST['edit'])) || ($_REQUEST['edit']!=1)) {
                if ($equipment->getTypeID()!=0) {
                    global $cobieTypeArr, $manufacturerNameArr;
                    $cobieType=new CobieType();
                    $cobieType->load($equipment->getTypeID());
                    
                    $description=str_replace("[TypeName]", $cobieType->getTypeName(), $description);
                    if ($cobieType->getManufacturer()!=0) {
                        $contact=new Contact();
                        $contact->load($cobieType->getManufacturer());
                        $description=str_replace("[ManufacturerName]", $contact->getCompany(), $description);
                    }
                    $description=str_replace("[ModelNumber]", $cobieType->getModelNumber(), $description);
                    
                } else {
                    $description=str_replace("[TypeName]", "n/a", $description);
                    $description=str_replace("[ManufacturerName]", "n/a", $description);
                    $description=str_replace("[ModelNumber]", "n/a", $description);
                }
            //}
            global $floor;
            $description=str_replace("[SpaceID]", $space->getID(), $description);
            $description=str_replace("[FloorID]", $floor->getID(), $description);
            $description=str_replace("[SysID]", $_REQUEST['sysID'], $description);
            //if ((!isset($_REQUEST['edit'])) || ($_REQUEST['edit']!=1)) {
                $description=str_replace("[Name]", $equipment->getName(), $description);
            //}
            $description=str_replace("[SKU]", $equipment->getSKU(), $description);
            $description=str_replace("[SerialNumber]", $equipment->getSerialNumber(), $description);
            $description=str_replace("[InstallationDate]", (($equipment->getInstallationDate()==0)?'n/a':date('Y-m-d',$equipment->getInstallationDate())), $description);
            $description=str_replace("[WarrantyStartDate]", (($equipment->getWarrantyStartDate()==0)?'n/a':date('Y-m-d',$equipment->getWarrantyStartDate())), $description);
            $description=str_replace("[Custom1]", $equipment->getCustom1(), $description);
            $description=str_replace("[Custom2]", $equipment->getCustom2(), $description);
            $description=str_replace("[Custom3]", $equipment->getCustom3(), $description);
            $description=str_replace("[Custom4]", $equipment->getCustom4(), $description);
            $description=str_replace("[Custom5]", $equipment->getCustom5(), $description);
            /*$description=str_replace("[Custom6]", $equipment->getCustom6(), $description);
            $description=str_replace("[Custom7]", $equipment->getCustom7(), $description);
            $description=str_replace("[Custom8]", $equipment->getCustom8(), $description);
            $description=str_replace("[Custom9]", $equipment->getCustom9(), $description);
            $description=str_replace("[Custom10]", $equipment->getCustom10(), $description);
            $description=str_replace("[Custom11]", $equipment->getCustom11(), $description);
            $description=str_replace("[Custom12]", $equipment->getCustom12(), $description);
            $description=str_replace("[Custom13]", $equipment->getCustom13(), $description);
            $description=str_replace("[Custom14]", $equipment->getCustom14(), $description);
            $description=str_replace("[Custom15]", $equipment->getCustom15(), $description);*/
            
        } else if ($workOrder->getSpaceID()!=0) {
            $customHeader=new CustomHeader();
            $customHeader->load($site->getID());
			$space=new BIMSpace($workOrder->getSpaceID());
			
            $spaceInfo=$space->getSpaceInfo();
            $uscgSpaceInfo=$space->getUSCG_SpaceInfo();
			$spaceCustomSettingArr=$site->getCustomSettingArr('Space');
        
            $description=$siteInfo->getSpaceDescription();
              $description=str_replace("[SpaceID]", $space->getID(), $description);
			  if (isset($floor)) {
				  $description=str_replace("[FloorID]", $floor->getID(), $description);
				  $description=str_replace("[FloorName]", $floor->getName(), $description);
			  }
              $description=str_replace("[SysID]", $_REQUEST['sysID'], $description);
              
              //if ((!isset($_REQUEST['edit'])) || ($_REQUEST['edit']!=1)) {
                $description=str_replace("[SpaceNumber]", $space->getSpaceNumber(), $description);
                $description=str_replace("[SpaceName]", $space->getName(), $description);
              //}
              if ($siteInfo->getUnit()=='IMPERIAL') {
                  //if ((!isset($_REQUEST['edit'])) || ($_REQUEST['edit']!=1)) {
                      $description=str_replace("[Area]", number_format($spaceInfo->getSpaceArea()*SQMETER_TO_SQFEET, 2, '.', '').' sf', $description);
                  //}
                  $description=str_replace("[Height]", number_format($space->getSpaceHeight()*METER_TO_FEET, 2, '.', '').' ft', $description); 	
              } else {
                  //if ((!isset($_REQUEST['edit'])) || ($_REQUEST['edit']!=1)) {
                      $description=str_replace("[Area]", number_format($spaceInfo->getSpaceArea(), 2, '.', '').' m2', $description);
                  //}
                  $description=str_replace("[Height]", number_format($space->getSpaceHeight(), 2, '.', '').' m', $description); 	
              }
              $description=str_replace("[Capacity]", $uscgSpaceInfo->getOccupMax(), $description); 	
              $description=str_replace("[Occupancy]", $uscgSpaceInfo->getOccup(), $description);
              //if ((!isset($_REQUEST['edit'])) || ($_REQUEST['edit']!=1)) {
                  if ($spaceInfo->getDeptID()==0) {
                      $description=str_replace("[DeptID]", 'n/a', $description);
                  } else {
                      $description=str_replace("[DeptID]", $deptName['deptName'.$spaceInfo->getDeptID()], $description);
                  }
              //}
              $evalStr='';
              for ($i=1; $i<=15; $i++) {
                  $evalStr.='
                  $description=str_replace("[Custom'.$i.']", $spaceInfo->getCustom'.$i.'(), $description);
                  if (trim($customHeader->getSpaceCustom'.$i.'())!="") $description=str_replace("[".$customHeader->getSpaceCustom'.$i.'()."]", $spaceInfo->getCustom'.$i.'(), $description);';
              }
              //if ((!isset($_REQUEST['edit'])) || ($_REQUEST['edit']!=1)) {
                  for ($i=1; $i<=30; $i++) {
                      $evalStr.='
                      if (($spaceInfo->getCustomInt'.$i.'()==0) || (!isset($spaceCustomSettingArr['.$i.']))) {
                          $description=str_replace("[CustomInt'.$i.']", "n/a", $description);
                          if ((isset($spaceCustomSettingArr['.$i.'])) && (trim($spaceCustomSettingArr['.$i.']->getCustomSettingName())!="")) $description=str_replace("[".$spaceCustomSettingArr['.$i.']->getCustomSettingName()."]", "n/a", $description);
                      } else {
                          $value="name".$spaceInfo->getCustomInt'.$i.'();
                          $description=str_replace("[CustomInt'.$i.']", $spaceCustomSettingArr['.$i.']->$value, $description);
                          if (trim($spaceCustomSettingArr['.$i.']->getCustomSettingName())!="") $description=str_replace("[".$spaceCustomSettingArr['.$i.']->getCustomSettingName()."]", $spaceCustomSettingArr['.$i.']->$value, $description);
                      }';
                  }
              //}
              eval($evalStr);
        }
        $description='';
        if (trim($description)!='') {
                                ?>
        
        <div class="panel panel-default">
            <div class="panel-heading"><a href="javascript:;" data-toggle="collapse" data-target="#additionalInformationDiv">Additional Information</a>
            </div>
            <div id="additionalInformationDiv" class="collapse in" style="padding:10px;">
			<?php echo $description; ?>
            </div>
        </div>
        <div class="spacer10"></div>                                
        <?php } ?>
        
        
        <div class="panel panel-default">
            <div class="panel-heading"><a href="javascript:;" data-toggle="collapse" data-target="#attachmetDetailDiv">Attachments</a>
            </div>
            <div id="attachmetDetailDiv" class="collapse in">
            <form class="form-horizontal" method="post" enctype="multipart/form-data">
							<?php
							$workOrderFileArr=WorkOrderFile::getWorkOrderFileArr('workOrderID='.$workOrder->getID(), 'ID');
							
							$fileArr=array();
							$imgArr=array();
							foreach ($workOrderFileArr as $workOrderFile) {
								if ($workOrderFile->isImage()) {
									array_push($imgArr, $workOrderFile);
								} else {
									array_push($fileArr, $workOrderFile);
								}
							}
							$fileCounter=0;
							foreach ($fileArr as $workOrderFile) {
								$fileCounter++;
								
								$size=filesize( SYSPATH.'/plan/attach/sys_'.$_REQUEST['sysID'].'/'.$workOrderFile->getFileName());
								?>
                    <div class="col-xs-12 text-left">
                    	<a class="fileLink" href="<?php echo FULLURL.'/plan/attach/sys_'.$_REQUEST['sysID'].'/'.$workOrderFile->getFileName(); ?>" target="_blank"><?php
									echo htmlentities($workOrderFile->getFileName());
								?></a> <small>(<?php
								if ($size > 1024 * 1024 * 1024) {
                                	echo (int)($size / (1024 * 1024 * 1024)).' gb';
								} else if ($size > 1024 * 1024) {
                                	echo (int)($size / (1024 * 1024)).' mb';
								} else if ($size > 1024) {
                                	echo (int)($size / (1024)).' kb';
								} else {
                                	echo $size.' bytes';
								}
								?>)</small>
                    </div>
							<?php }
							if (($fileCounter>0) && (count($imgArr)>0)) {
								?>
                                <!-- Spacer class does not work here -->
								<div>&nbsp;</div>
								<?php
							}
							foreach ($imgArr as $workOrderFile) {
								$fileCounter++;
								?>
                    <div class="col-xs-12 text-center">
                    <a href="<?php echo FULLURL.'/plan/attach/sys_'.$_REQUEST['sysID'].'/'.$workOrderFile->getFileName(); ?>" target="_blank">
								<img src="<?php echo FULLURL.'/plan/attach/sys_'.$_REQUEST['sysID'].'/'.$workOrderFile->getFileName(); ?>" style="width:100%;" title="<?php
									echo htmlentities($workOrderFile->getFileName());
								?>" /></a>
                    </div>
							<?php } ?>
                <?php if (!count($fileArr) && !count($imgArr)) { ?>
                	<div class="spacer5"></div>
                	<div class="col-xs-12 text-center"><p>No attachments related to this work order</p></div>
                <?php } else {?>
                    <!-- Spacer class does not work here -->
                    <div>&nbsp;</div>
                <?php } ?>
			<?php if (strtolower(trim($technician->getEmail()))==strtolower(trim($_GET['email']))) { ?>
                <input type="hidden" name="action" value="addFile" />
                <div class="form-group">
                    <label class="control-label col-xs-4">Add New Attachment:</label>
                    <div class="col-xs-8 text-center">
                        <input type="file" name="attachFile">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <div class="col-xs-12 text-center">
                        <button type="submit" class="btn btn-default" style="width:150px;" onClick=" $('#uploadModal').modal('show');">Add</button>
                    </div>
                </div>
			<?php } else { ?>
                   <div>&nbsp;</div>
			<?php } ?>
            </form>
            </div>
        </div>
        <div class="spacer10"></div>
<?php printEquipmentModalPanel(); ?>
<div id="costDetailsModal" class="modal fade">
<div class="modal-dialog">
    <div class="modal-content form-horizontal">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="titleTxt">Material Costs</h4>
        </div>
        <div id="costContentDiv"></div>
    </div>
</div>
</div>

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

</div>
<?php } ?>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Onuma</p> 
</div>
</body>
</html>