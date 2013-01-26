<?php
require("getneighborloc.php");
require("settings.php"); //to reopen SQL connection

if($sql!=FALSE) {if($verbose>=0) echo("SQL connection open.\n");}
else {$sql->close();die("SQL connection failed!");}

$avg=loadavgs();

$loctree=new loctree($loctreetextile,$nagylocID_file);
$ltc=count($loctree->treenodes);

$links=array();
$res=$sql->query("SELECT DISTINCT(id) as ids FROM Interaction");
while ($ids=$res->fetch_assoc())
	$links[]=$ids['ids'];
$num=count($links);
$current=0;
foreach($links as $lid)
{
    $res=$sql->query("SELECT actorAid,actorBid FROM Interaction WHERE id={$lid}");
    if($res->num_rows==0) {$sql->close();echo("Warning: No link exists with ID {$lid}!");continue;}
    $interaction=$res->fetch_assoc();
    if($interaction===FALSE) {fwrite(STDERR,"ERROR: Error retrieving interaction {$lid}!");$sql->close();exit();}

    $res=$sql->query("SELECT specieID FROM Protein WHERE id={$interaction['actorAid']}");
    $s=$res->fetch_assoc();
    $species=$s['specieID'];

    //get interaction strength
    $fi_int=1; //removed for the time being

    foreach($loctree->largelocs as $loc=>$terms)
	{

	 if(!isset($avg[$species][$loc])) echo("Warning: no average score for species {$species}, loc {$loc}!\n");
	 $osa=correct($scoreCache[$species][$interaction['actorAid']][$loc][0],
		$scoreCache[$species][$interaction['actorAid']][$loc][1],
		$avg[$species][$loc],$npredpow);//neighborLoc[locID] is the locFi
	 $osb=correct($scoreCache[$species][$interaction['actorBid']][$loc][0],
		$scoreCache[$species][$interaction['actorBid']][$loc][1],
		$avg[$species][$loc],$npredpow);//neighborLoc[locID] is the locFi
	 $locvals[]=$cls=$osa*$osb;
	}
    $pLinkLoc[$lid]=or_array($locvals)*$fi_int;
    $current++;
    if($current%1000==0) echo("   > calculating confidence scores (2/2)... ".$current."/".$num."\n");
}
$sql->close();
