<? 
require_once("settings.php");
require_once("functions.php");
$start=microtime(TRUE);

$loctree=new loctree($loctreetextile,$nagylocID_file);

$prots=array();
$res=$sql->query("SELECT DISTINCT(id) as ids FROM Protein");
while ($ids=$res->fetch_assoc())
		$prots[]=$ids['ids'];

$num=count($prots);
$current=0;

foreach($prots as $lid)
{
$res=$sql->query("SELECT specieID FROM Protein WHERE id={$lid}");
$s=$res->fetch_assoc();
$species=$s['specieID'];

$neighbors=getNeighbors($lid,-1,$sql);
$deg=count($neighbors);

$locs=$loctree->getLocArray($lid,$sql);

$NeighborsLoc=array();
foreach($neighbors as $nid) {$NeighborsLoc[]=$loctree->getLocArray($nid,$sql);}

//make all locs appear in both arrays
foreach($loctree->largelocs as $loc=>$terms)
{
foreach($NeighborsLoc as &$neighborLoc)
	if(!array_key_exists($loc,$neighborLoc)) $neighborLoc[$loc]=0;
	unset($neighborLoc);
}
$locvals=array();
foreach($loctree->largelocs as $loc=>$terms)
{
 $place=0;
 foreach($NeighborsLoc as $NeighborLoc)
  $place+=$NeighborLoc[$loc];
 $score=$deg==0?0:$place/$deg;

 if(!isset($avgnum)) $avgnum=array();
 if(!isset($avgval)) $avgval=array();
 if(!isset($avgnum[$species][$loc]))
    $avgnum[$species][$loc]=1;
 else $avgnum[$species][$loc]++;
 if(!isset($avgval[$species][$loc]))
    $avgval[$species][$loc]=$score;
 else $avgval[$species][$loc]+=$score;
 //echo ($species.",".$loc.",".$avgnum[$species][$loc].",".$avgval[$species][$loc]."\n");
}

$current++;
if($current%1000==0) echo("   > calculating averages (1/2)... ".$current."/".$num."\n");
}
$file=fopen($avgfile,'w');
foreach($avgval as $spec=>$avgnsp)
    foreach($avgnsp as $lc=>$avgsc)
        fwrite($file,$spec."\t".$lc."\t".$avgsc/$avgnum[$spec][$lc]."\n");
fclose($file);
$sql->close();
/*if($verbose>=0) echo ("SQL connection closed.\n");
$runtime=microtime(TRUE)-$start;
if($verbose>=-1) echo ("Total runtime:{$runtime} seconds.\n");*/
