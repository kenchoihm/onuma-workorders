<?php
include('config.php');
include('include.php');


include('../plan/authconfig.php');
include('../plan/OPS/include.php');


if ($_GET['email']=='') redirect('requestlist.php?'.getParams());
if ($_GET['workOrderID']==0) redirect('requestlist.php?'.getParams());

if ($_GET['requestEmail']!='') $_GET['email']=$_GET['requestEmail'];

$site=new BIMSite($_GET['siteID']);
$siteInfo=$site->getSiteInfo();
$workOrderSetting=$siteInfo->getWorkOrderSetting();
$project=new BIMProject($site->getProjectID());
$workOrder=new WorkOrder();
$workOrder->load($_GET['workOrderID']);

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

if (isset($_POST['confirm'])) {
	if ($_POST['confirm']==1) {
		$workOrder->setStatus('Completed Confirmed');
	} else {
		$workOrder->setStatus('Completion Declined');
	}
	$completionDescription='';
	if (trim($_POST['completionDescription'])!='') {
		$completionDescription.=date('[Y-m-d H:i:s]');
		$completionDescription.=" ".$workOrder->getSubmittedBy().' added: ';
		$completionDescription.="\r\n";
		$completionDescription.=$_POST['completionDescription'];
	}
	if (trim($workOrder->getCompletionDescription())!='') {
		if (trim($_POST['completionDescription'])!='') {
			$completionDescription.="\r\n";
			$completionDescription.="\r\n";
			$completionDescription.='------------------------------------------------';
			$completionDescription.="\r\n";
		}
		$completionDescription.=trim($workOrder->getCompletionDescription());
	}
	$workOrder->setCompletedConfirmedDate(date('Y-m-d H:i:s'));
	$workOrder->setCompletionDescription($completionDescription);
	$workOrder->update();
	
	require(SYSPATH.'plan/OPS/modulet/workOrderEmail.php');
	if (($workOrder->getStatus()=='Completion Declined') || ($siteInfo->getDisableWorkOrderReminderEmail()==0)) {
		sendCompletedConfirmedEmailToAdmin($workOrder, $_POST['confirm']);
	}
	if ($workOrder->getStatus()=='Completion Declined') {
		sendCompletedDeclinedEmailToCC($workOrder);
		sendCompletedDeclinedEmailToRequestor($workOrder);
	}
	header('location: status.php?'.getParams());
	return;
}
$priorityArr=array("(Not specified)", "Urgent", "Immediate", "Today", "Within a week", "When possible");

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
		<li><a href="requestlist.php?<?php echo getParams(); ?>">My Requests</a></li>
		<li class="active"><?php echo html($workOrder->getRequestNumber()); ?></li>
		<div class="pull-right" style="padding-left:10px;">
        <a href="../plan/OpsBug.php?<?php echo getParams(); ?>" target="bugs">Bugs</a>
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
    
<?php if (strtolower(trim($workOrder->getRequestEmail()))!=strtolower(trim($_GET['email']))) { ?>
    <p class="text-danger text-center"><b>You don't have privilege to access this work order</b></p>
	<p class="text-center"><small>Click <a href="requestlist.php?<?php echo getParams(); ?>">here</a> to return to my other work order requests</small></p>
<?php } else {?>
    
<div class="panel-group">
            <form class="form-horizontal" id="confirmForm" method="post">
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
                <div class="form-group">
                    <label class="control-label col-xs-4">Status:</label>
                    <div class="col-xs-8"><p class="form-control-static<?php
						if ($workOrder->getStatus()=='Completed Confirmed') echo ' text-success';
						if ($workOrder->getStatus()=='Completion Declined') echo ' text-danger';
					?>"><?php echo htmlentities($workOrder->getStatus()); ?></p></div>
                </div>
				<?php
				$workOrderTaskArr=WorkOrderTask::getWorkOrderTaskArr('workOrderID='.$workOrder->getID(), 'date DESC');
				if (count($workOrderTaskArr)) {
				?>
					<div class="form-group">
						<label class="control-label col-xs-4">Last Updated On:</label>
						<div class="col-xs-8"><p class="form-control-static"><?php echo date('m/d/y', strtotime($workOrderTaskArr[0]->getDate())); ?></p></div>
					</div>
					<div class="form-group">
						<label class="control-label col-xs-4">Last Updated By:</label>
						<div class="col-xs-8"><p class="form-control-static"><?php
						$contact=new Contact();
						$contact->load($workOrderTaskArr[0]->getAssignedContactID());
						echo htmlentities($contact->getFname().' '.
											$contact->getLname());
					?></p></div>
					</div>
				<?php } ?>
				<?php if (trim($workOrder->getAdministratorComment())!='') { ?>
					<div class="form-group">
						<label class="control-label col-xs-4">Comments By Administrator:</label>
						<div class="col-xs-8"><p class="form-control-static"><?php echo nl2br(htmlentities($workOrder->getAdministratorComment())); ?></p></div>
					</div>
				<?php } ?>
				<?php if ($workOrder->getStatus()=='Completed') { ?>
				<p class="text-danger text-center">
				<b>Please confirm if the work order is completed?</b>
				</p>
				
				<div class="text-center">
					<button type="submit" class="btn btn-sm btn-default" style="width:100px;" onclick="$('#confirmTxt').val(1);">
					Confirm
					</button>
					<img src="images/clearimage.gif" height="1" width="20" />
					<button type="button" class="btn btn-sm btn-default" style="width:100px;" onclick="
						$('#declineModal').modal('show');
					">
					Decline
					</button>
				</div>
				<input type="hidden" name="confirm" id="confirmTxt" value />
					
<div id="declineModal" class="modal fade">
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header mb5">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="titleTxt">Decline completion</h4>
        </div>
			<div class="col-xs-12">
				<p id="declineMsg">
				Please provide the reason why the work order completion is declined:
				</p>
			</div>
			<div class="clearfix"></div>
			<div class="col-xs-12 mb10" id="txtContainer">
				<textarea name="completionDescription" id="completionDescriptionTxt" class="form-control"
				style="height:100px;"></textarea>
			</div>
			<div class="clearfix"></div>
			<div class="col-xs-12 text-center mb10">
					<button type="button" class="btn btn-sm btn-default" style="width:100px;" onclick="
						if ($.trim($('#completionDescriptionTxt').val())=='') {
							$('#completionDescriptionTxt').focus();
							$('#txtContainer').addClass('has-error');
							$('#declineMsg').addClass('text-danger');
							
							setTimeout(function(){
								$('#txtContainer').removeClass('has-error');
								$('#declineMsg').removeClass('text-danger');
							}, 3000);
						} else {
							$('#confirmTxt').val(0);
							$('#confirmForm').submit();
						}
					">
					Decline
					</button>
			</div>
			<div class="clearfix"></div>
    </div>
</div>
</div>
				
				<?php } ?>
				
            </form>
        
        
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
</div>
<?php } ?>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Onuma</p> 
</div>
</body>
</html>