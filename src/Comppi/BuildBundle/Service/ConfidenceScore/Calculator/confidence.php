<?
require("settings.php"); //to reopen SQL connection
require_once("functions.php");
$verbose=-3;
$start=microtime(TRUE);
if ($verbose>=-1) echo("ComPPI confidence calculator opened.\n\n");

if ($verbose>=-1) echo("Parameter values:
Loctree.textile location: {$loctreetextile}
LargelocIDs.txt location: {$nagylocID_file}
avgnscore.txt location: {$avgnscore}
Neighbor prediction power: {$npredpow}
Probability for an interaction being experimental: {$fi_int_exp}
Probability for an interaction being predicted: {$fi_int_pred}
Probability for an interaction with unknown type: {$fi_int_unknown}
Probability for a localization being experimental: {$fi_loc_exp}
Probability for a localization being predicted: {$fi_loc_pred}
Probability for a localization with unknown type: {$fi_loc_unknown}
\n");

if($sql!=FALSE) {if($verbose>=0) echo("SQL connection open.\n");}
else {$sql->close();die("SQL connection failed!");}

$avg=loadavgs();

//if(!isset($_REQUEST['id'])) {echo("ERROR:No interaction ID given (id)!<br />");$sql->close();exit();}

/*if(strpos($_REQUEST['id'],":")!==FALSE)
{
	$linkelements=explode(":",$_REQUEST['id']);
	$linkid="set";
}
else $linkid=$_REQUEST['id'];
if ($verbose>=-1) echo ("Requested link ID is: {$linkid}<br />");
*/
if($verbose>=0) echo("Starting to build loctree...<br />");
$loctree=new loctree($loctreetextile,$nagylocID_file);
$ltc=count($loctree->treenodes);
if($verbose>=0) echo("Loctree complete ({$ltc} localizations).<br />");
if($verbose>=2) echo("Tree nodes:");
if($verbose>=2) foreach($loctree->treenodes as $node) echo("({$node->name},{$node->startID}-{$node->endID}) ");
if($verbose>=2) echo("<br />");

$links=array();
/*if($linkid=="all")
{*/	
	$res=$sql->query("SELECT DISTINCT(id) as ids FROM Interaction");
	while ($ids=$res->fetch_assoc())
		$links[]=$ids['ids'];
/*}
else $links[]=$linkid;*/
$num=count($links);
$current=0;
foreach($links as $lid)
{
$res=$sql->query("SELECT actorAid,actorBid FROM Interaction WHERE id={$lid}");
if($res->num_rows==0) {$sql->close();echo("Warning: No link exists with ID {$lid}!");continue;}
$interaction=$res->fetch_assoc();
if($interaction===FALSE) {fwrite(STDERR,"ERROR: Error retrieving interaction {$lid}!");$sql->close();exit();}
//else if($verbose>=0) echo("Interaction {$id} found. Interactors: {$interaction['actorAid']}, {$interaction['actorBid']}\n");

$res=$sql->query("SELECT specieID FROM Protein WHERE id={$interaction['actorAid']}");
$s=$res->fetch_assoc();
$species=$s['specieID'];

//get interaction strength
$fi_int=1; //removed for the time being

//if($verbose>=0) echo("Interaction strength is {$fi_int}.<br />");
// add to docs: common link is not counted when counting neighbors.
//get neighbors of A
$aNeighbors=getNeighbors($interaction['actorAid'],$interaction['actorBid'],$sql);
//get neighbors of B
$bNeighbors=getNeighbors($interaction['actorBid'],$interaction['actorAid'],$sql);

$degA=count($aNeighbors);
$degB=count($bNeighbors);

$aNeighborsLoc=array();$bNeighborsLoc=array();
$locsA=$loctree->getLocArray($interaction['actorAid'],$sql);
/*if($verbose>=0) {echo($interaction['actorAid']." is known in ".count($locsA)." locations:");
	foreach($locsA as $loc=>$loc_fi)
		echo($loc."[".$loc_fi."]");
	echo "<br />";}
*/
$locsB=$loctree->getLocArray($interaction['actorBid'],$sql);
/*if($verbose>=0) {echo($interaction['actorBid']." is known in ".count($locsB)." locations:");
	foreach($locsB as $loc=>$loc_fi)
		echo($loc."[".$loc_fi."]");
	echo "<br />";}*/

foreach($aNeighbors as $id) {$aNeighborsLoc[]=$loctree->getLocArray($id,$sql);}
/*if($verbose>=1) {echo("Locations for {$interaction['actorAid']}'s neighbors:");
foreach($aNeighborsLoc as $aNeighborLoc)
{
echo("(");
	foreach($aNeighborLoc as $loc=>$loc_fi)
		echo($loc."[".$loc_fi."]");
echo(")");
}
echo("<br />");}*/
foreach($bNeighbors as $id) $bNeighborsLoc[]=$loctree->getLocArray($id,$sql);
/*if($verbose>=1) {echo("Locations for {$interaction['actorBid']}'s neighbors:");
foreach($bNeighborsLoc as $bNeighborLoc)
{
echo("(");
	foreach($bNeighborLoc as $loc=>$loc_fi)
		echo($loc."[".$loc_fi."]");
echo(")");
}
echo("<br />");}
*/
//make all locs appear in both arrays
foreach($loctree->largelocs as $loc=>$terms)
{
	if(!array_key_exists($loc,$locsA)) $locsA[$loc]=0;
	if(!array_key_exists($loc,$locsB)) $locsB[$loc]=0;
foreach($aNeighborsLoc as &$aNeighborLoc)
	if(!array_key_exists($loc,$aNeighborLoc)) $aNeighborLoc[$loc]=0;
foreach($bNeighborsLoc as &$bNeighborLoc)
	if(!array_key_exists($loc,$bNeighborLoc)) $bNeighborLoc[$loc]=0;
	unset($aNeighborLoc);unset($bNeighborLoc);
}
//so it doesn't matter which keys we loop on
$locvals=array();
foreach($loctree->largelocs as $loc=>$terms)
{
 $aPlace=0;
 foreach($aNeighborsLoc as $aNeighborLoc)
   $aPlace+=$aNeighborLoc[$loc];
 $bPlace=0;
 foreach($bNeighborsLoc as $bNeighborLoc)
   $bPlace+=$bNeighborLoc[$loc];
 $sca=$degA==0?0:$aPlace/$degA;
 $scb=$degB==0?0:$bPlace/$degB;
 
 if(!isset($avg[$species][$loc])) echo("Warning: no average score for species {$species}, loc {$loc}!\n");
 $osa=correct($locsA[$loc],$sca,$avg[$species][$loc],$npredpow);//neighborLoc[locID] is the locFi
 $osb=correct($locsB[$loc],$scb,$avg[$species][$loc],$npredpow);
 $locvals[]=$cls=$osa*$osb;
 /*if($verbose>=1){ echo("Individual location score of {$interaction['actorAid']} for localization {$loc}:{$locsA[$loc]}<br />");
  echo("Neighbor location score of {$interaction['actorAid']} for localization {$loc}:{$sca}<br />");
  echo("Summarized location score of {$interaction['actorAid']} for localization {$loc}:{$osa}<br />");
  echo("Individual location score of {$interaction['actorBid']} for localization {$loc}:{$locsB[$loc]}<br />");
  echo("Neighbor location score of {$interaction['actorBid']} for localization {$loc}:{$scb}<br />");
  echo("Summarized location score of {$interaction['actorBid']} for localization {$loc}:{$osb}<br />");
  echo("Location score for localization {$loc}:{$cls}<br />");}*/
}
$pLinkLoc[$lid]=or_array($locvals)*$fi_int;
/*if ($verbose>=-2) echo ("ComPPI confidence for link ID {$lid}: {$pLinkLoc}<br />");
else echo($pLinkLoc);
echo("<br />");*/
$current++;
if($current%1000==0) echo("   > calculating confidence scores (2/2)... ".$current."/".$num."\n");
//echo ($lid." ".$pLinkLoc[$lid]."\n");
}
$sql->close();
/*if($verbose>=0) echo ("SQL connection closed.<br />");
$runtime=microtime(TRUE)-$start;
echo ("Confidence run time:{$runtime} seconds.<br />");*/
