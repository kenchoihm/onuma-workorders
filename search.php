<?php
include('config.php');
include('include.php');
include('../plan/authconfig.php');
include('../plan/OPS/include.php');
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
if (isset($_POST['s'])) $_GET['s']=$_POST['s'];
if (isset($_GET['equipmentID'])) {
	$workOrderEquipment=new WorkOrderEquipment();
	if (!$workOrderEquipment->load2($workOrder->getID(), $_GET['equipmentID'])) {
		$workOrderEquipment->setWorkOrderID($workOrder->getID());
		$workOrderEquipment->setEquipmentID($_GET['equipmentID']);
		$workOrderEquipment->insert();
	}
	header('location: technician.php?'.getParams());
	exit;
}


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


?>
<!DOCTYPE html>
<html lang="en">
<head>
		<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>Search Components</title>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
<link rel="stylesheet" type="text/css" href="css/workorder.css">
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/workorder.js"></script>

<script type="text/javascript" language="javaScript"><!--
var chkNameArr=['n','c','b','t','m','m2'];
function scanBarcode() {
<?php if (isIOS()) { ?>
	setTimeout(function() {
		// if QR Code reader is not installed yet, go to App Store
		window.location = "https://itunes.apple.com/us/app/qr-code-reader-by-scan/id698925807";
	}, 25);
<?php } ?>
<?php if (isAndroid()) { ?>
	setTimeout(function() {
		// if QR Code reader is not installed yet, go to App Store
		window.location = "https://play.google.com/store/apps/details?id=me.scan.android.client";
	}, 25);
<?php } ?>
	// launch pic2shop and tell it to open Google Products with scan result
	//window.location="pic2shop://scan?callback=http%3A//192.168.0.10/wo/search.php?s%3DEAN%26s2%3DEAN2";
	//window.location="pic2shop://scan?callback=http%3A//www.onuma.com/wo/search.php?s%3DEAN";
	//window.location="scan://scan?callback=http%3A//www.onuma.com/wo/search.php?s%3DSCANNED_DATA&openin=chrome";
	var params='';
	$.each(chkNameArr, function(k,v) {
		var c=($('input[name='+v+']')[0].checked?1:0);
		if (v=='b') c=1;
		params+=v+'='+(c)+'%26';
	});
	
	window.location="scan://scan?callback=<?php echo urlencode(DOMAIN); ?>wo/search.php?<?php
		if (isChrome()) echo urlencode('openin=chrome&');
		$s=getParams($overwriteParams=array());
		echo urlencode($s.'&');
	?>"+params+"s%3DSCANNED_DATA";
	
}
function updateText() {
	var s='', a=[];
	if ($('input[name=n]')[0].checked) a.push('Name');
	if ($('input[name=c]')[0].checked) a.push('Unique Mark');
	if ($('input[name=b]')[0].checked) a.push('Barcode');
	if ($('input[name=t]')[0].checked) a.push('Type Name');
	if ($('input[name=m]')[0].checked) a.push('Manufacturer');
	if ($('input[name=m2]')[0].checked) a.push('Model Number');
	var s=a.join(' / ');
   $('#sTxt').attr("placeholder", s);
}
function checkSubmit() {
	var s=$.trim($('input[name=s]')[0].value), checked=false;
	if (s=='') {
		alert('Please type in search criteria');
		$('input[name=s]').focus();
		return false;
	}
	
	$.each(chkNameArr, function(k,v) {
		if ($('input[name='+v+']')[0].checked) checked=true;
	});
	if (checked) return true;
	alert('Please select at least 1 criteria to search for');
	var optionsDiv=$('#optionsDiv');
	if (optionsDiv.hasClass('hidden')) {
		$('#optionsBtn').button('toggle');
		optionsDiv.removeClass('hidden');
	}
	return false;
}

$(document).ready(function(){ 
	updateText();
   $('input[type=checkbox]').click(updateText);
								   
});



--></script>
<style>
.result-btn{ padding-left:40px; padding-right:40px;}
.info-btn{ padding-left:10px; padding-right:10px;}
</style>
</head>
<body>
<div class="content" style="min-width:420px;">
<ul class="breadcrumb">
    <li><a href="list.php?<?php echo getParams(); ?>">My Task Assignments</a></li>
    <li><a href="technician.php?<?php echo getParams(); ?>"><?php echo $workOrder->getRequestNumber(); ?></a></li>
    <li class="active">Search Components</li>
    <div class="pull-right" style="padding-left:10px;">
        <a href="../plan/OpsBug.php?<?php echo getParams(); ?>" target="bugs">Bugs</a>
    </div>
	<div class="clearfix">
	</div>
</ul>
	<div class="logo col-xs12 col-sm-6">
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
		<div class="clearfix">
		</div>
	</div>
	<div class="logo col-xs12 col-sm-6">
			<p>
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
			</p>
	</div>
	<div class="clearfix"></div>
            <h4>Search Components<br />
            <small>Please type in the search criteria or touch the Scan Barcode button.</small>
            </h4>
        <div>
            <form class="form-horizontal" action="search.php?<?php echo getParams(); ?>" method="get" onSubmit="return checkSubmit();">
            <?php
				printRequiredHiddenParam();
			?>
            <div class="form-group">
            <div class="col-xs-12">
                <div class="input-group">
                    <input type="text" id="sTxt" name="s" class="form-control" value="<?php echo $_GET['s']; ?>" plzyaceholder="Name / Unique Mark / Barcode">
                    <span class="input-group-btn">
                    
        <button type="button" id="optionsBtn" class="btn btn-default" onClick="
        	var optionsDiv=$('#optionsDiv');
            if (optionsDiv.hasClass('hidden')) {
            	optionsDiv.removeClass('hidden');
            } else {
            	optionsDiv.addClass('hidden');
            }
        " data-toggle="button" ><span class="glyphicon glyphicon-chevron-down"></span></button>
        
                        
                    <?php if (isMobile() && false) { ?>
                        <button type="button" class="btn btn-default" onClick="scanBarcode()"><span class="glyphicon glyphicon-barcode"></span></button>
                    <?php } ?>
                        <button type="submit" class="btn btn-default" value="Search"><span class="glyphicon glyphicon-search"></span></button>
                    </span>
                </div>
            </div>
            <div class="col-xs-12 hidden" id="optionsDiv">
            	<div class="panel panel-default">
                    <div class="col-xs-12 panel-title" style="margin-top:10px;">
                    <b>Search For</b>
                    </div>
                    <div class="panel-body">
                        <div class="col-xs-6 col-md-2 checkbox">
                            <label><input type="checkbox" name="n"<?php if ((!isset($_GET['s'])) || ($_GET['n']!=0)) echo ' checked="checked"'; ?> value="1"> Name</label>
                        </div>
                        <div class="col-xs-6 col-md-2 checkbox">
                            <label><input type="checkbox" name="c"<?php if ((!isset($_GET['s'])) || ($_GET['c']!=0)) echo ' checked="checked"'; ?> value="1"> Unique Mark</label>
                        </div>
                        <div class="col-xs-6 col-md-2 checkbox">
                            <label><input type="checkbox" name="b"<?php if ((!isset($_GET['s'])) || ($_GET['b']!=0)) echo ' checked="checked"'; ?> value="1"> Barcode</label>
                        </div>
                        <div class="col-xs-6 col-md-2 checkbox">
                            <label><input type="checkbox" name="t"<?php if ((!isset($_GET['s'])) || ($_GET['t']!=0)) echo ' checked="checked"'; ?> value="1"> Type name</label>
                        </div>
                        <div class="col-xs-6 col-md-2 checkbox">
                            <label><input type="checkbox" name="m"<?php if ((!isset($_GET['s'])) || ($_GET['m']!=0)) echo ' checked="checked"'; ?> value="1"> Manufacturer</label>
                        </div>
                        <div class="col-xs-6 col-md-2 checkbox">
                            <label><input type="checkbox" name="m2"<?php if ((!isset($_GET['s'])) || ($_GET['m2']!=0)) echo ' checked="checked"'; ?> value="1"> Model Number</label>
                        </div>
                    </div>
                </div>
            </div>
            </div>
            </form>
        </div>
        <?php if ($_GET['s']!='') { ?>
        <div>
            <h4>
            Search results for "<?php echo html($_GET['s']); ?>":
            <br /><small>Select the component to attach the work order to.</small>
            </h4>
            <?php
	$found=0;
	$bldgInfo=$bldg->getBldgInfo();
	$bldgFlrs=$bldg->getBIMFloors($desc=true);
	$flrCounter=count($bldgFlrs)-$bldgInfo->getFirstFloor();
	$resultArr=array();
	$typeArr=array();
	$contactArr=array();
	for ($i=0; $i<count($bldgFlrs); $i++) {
		if ($flrCounter==0) $flrCounter--;
		if ($bldgFlrs[$i]->getName()=='') $floorName='Floor '.$flrCounter;
		else $floorName=$bldgFlrs[$i]->getName();

		$spaceInd=0;
		$spaces=$bldgFlrs[$i]->getBIMSpaces();
		foreach ($spaces as $space) {
			$spaceInfo=$space->getSpaceInfo();
			$list=false;
			$listEquipmentArr=array();
			$equipments=$space->getBIMEquipments();
			foreach ($equipments as $equipment) {
				$tmpList=false;
				//if ($equipment->getID()==$_GET['s']) $tmpList=true;
				//if ($equipment->getGUID()==$_GET['s']) $tmpList=true;
				if ($_GET['n']==1 && stripos($equipment->getName(), $_GET['s'])!==FALSE) $tmpList=true;
				if ($_GET['c']==1 && stripos($equipment->getComponentName(), $_GET['s'])!==FALSE) $tmpList=true;
				if ($_GET['b']==1 && stripos($equipment->getBarCode(), $_GET['s'])!==FALSE) $tmpList=true;
				if (($_GET['t']==1) || ($_GET['m']==1) || ($_GET['m2']==1)) {
					if ($equipment->getTypeID()!=0) {
						if (!isset($typeArr[$equipment->getTypeID()])) {
							$typeArr[$equipment->getTypeID()]=new CobieType();
							$typeArr[$equipment->getTypeID()]->load($equipment->getTypeID());
						}
						$type=$typeArr[$equipment->getTypeID()];
						if ($_GET['t']==1 && stripos($type->getTypeName(), $_GET['s'])!==FALSE) $tmpList=true;
						if (!isset($contactArr[$type->getManufacturer()])) {
							$contactArr[$type->getManufacturer()]=new Contact();
							$contactArr[$type->getManufacturer()]->load($type->getManufacturer());
						}
						$contact=$contactArr[$type->getManufacturer()];
						$company=$contact->getCompany();
						if ($_GET['m']==1 && stripos($company, $_GET['s'])!==FALSE) $tmpList=true;
						if ($_GET['m2']==1 && stripos($type->getModelNumber(), $_GET['s'])!==FALSE) $tmpList=true;
						
						if (!isset($contactArr[$type->getPartsWarrantyGuarantor()])) {
							$contactArr[$type->getPartsWarrantyGuarantor()]=new Contact();
							$contactArr[$type->getPartsWarrantyGuarantor()]->load($type->getPartsWarrantyGuarantor());
						}
						if (!isset($contactArr[$type->getLabourWarrantyDuration()])) {
							$contactArr[$type->getLabourWarrantyDuration()]=new Contact();
							$contactArr[$type->getLabourWarrantyDuration()]->load($type->getLabourWarrantyDuration());
						}
					}
				}
				if ($tmpList) $equipment->spaceID=$space->getID();
				if ($tmpList) array_push($listEquipmentArr, $equipment);
				if ($tmpList) array_push($resultArr, $equipment);
				$list=$tmpList||$list;
			}
			if (!$list) continue;
			$spaceInd++;
			$found++;
			if ($list && $spaceInd==1) {
				?>
            <div class="col-xs-12" style="padding-left:0px; font-size:larger;"><b><?php echo $floorName; ?></b></div>
                <?php
			}
			?>
            <div class="col-xs-9" style="padding-bottom:5px;"><b><?php echo htmlentities($space->getDisplayName()) ?></b></div>
            <div class="col-xs-3 text-right"><span class="badge"><?php echo count($listEquipmentArr); ?></span></div>
			<div class="clearfix">
			</div>
			<?php
			foreach ($listEquipmentArr as $equipment) {
				?>
            <div class="btn-group" style="padding-left:40px;">
                <button class="btn btn-default result-btn" style="margin-bottom:5px; text-align:left;" onClick="
                	document.location='search.php?<?php echo getParams(); ?>&equipmentID=<?php echo $equipment->getID(); ?>';
                "><?php
					echo html($equipment->getDisplayName());
				?></button>
                <button type="button" class="btn btn-default info-btn" onClick="
					//selectSpace('SPACE_<?php echo $equipment->spaceID; ?>');
	                loadModalContent(<?php echo $equipment->getID(); ?>);
                     $('#componentDetailsModal').modal('show');
                ">Details</button>
            </div>
		    <div class="clearfix"></div>
				<?php
			}
		}
		$flrCounter--;
	}
	if (!$found) {
	?>
	<div class="col-xs-12 text-center"><p>No components found</p></div>
	<?php
	}
	?>
        </div>
<?php printEquipmentModalPanel(); ?>
        <?php } ?>

		
<script type="text/javascript" language="JavaScript"><!--
var currentSpaceID='';
function selectSpace(spaceID) {
	var style="fill: #0000FF;";
	if (currentSpaceID!='') changeCurveProfileStyle(currentSpaceID,style);
	var style="fill: #FF0000;";
	currentSpaceID=currentSpaceID;
	changeCurveProfileStyle(currentSpaceID,style);
}
function onLoadComplete() {
	<?php if ($spaceID!=0) { ?>
	currentSpaceID='SPACE_<?php echo $spaceID; ?>';
	selectSpace(currentSpaceID);
	<?php } ?>
}
--></script>
                <div class="col-xs-12 text-center" style="margin-top:20px;">
                <iframe frameborder="0" width="380" height="400" src="/plan/OPS<?php
						if (!$local) echo '-beta';
					?>/SVG2/getDataSVG.php?sysID=<?php
						echo $_GET['sysID']; ?>&bldgID=<?php echo $workOrder->getBldgID(); ?>&floorID=<?php echo $floorID; ?>&width=380&height=400&hideSpaceKey=1"></iframe>
                </div>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Onuma</p> 

</div>
</body>
</html>
<script language="JavaScript"><!--
var componentArr={};
<?php
if ($_GET['s']!='') {
	$furnDataArr=FurnData::getFurnDataArr('', '', 'furnName');
	foreach ($resultArr as $equipment) {
		$obj=new stdclass();
		$obj->displayName=$equipment->getDisplayName();
		$obj->name=$equipment->getName();
		$obj->componentName=$equipment->getComponentName();
		$obj->serialNumber=$equipment->getSerialNumber();
		if ($obj->serialNumber==0) $obj->serialNumber='';
		$obj->installationDate=(($equipment->getInstallationDate()==0)?'':date('Y-m-d', $equipment->getInstallationDate()));
		if ($equipment->getTypeID()!=0) {
			$type=$typeArr[$equipment->getTypeID()];
			$obj->typeName=$type->getTypeName();
			$obj->manufacturer=$contactArr[$type->getManufacturer()]->getCompany();
			$obj->modelNumber=$type->getModelNumber();
			$obj->partsWarrantyGuarrantor=$contactArr[$type->getPartsWarrantyGuarantor()]->getCompany();
			$obj->partsWarrantyDuration=$type->getPartsWarrantyDuration().' '.$type->getWarrantyDurationUnit();
			if ($obj->partsWarrantyDuration==' ') $obj->partsWarrantyDuration='';
			$obj->labourWarrantyGuarrantor=$contactArr[$type->getLabourWarrantyGuarantor()]->getCompany();
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
}
?>
--></script>