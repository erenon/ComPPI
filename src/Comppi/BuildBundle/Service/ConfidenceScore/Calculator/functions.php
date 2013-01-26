<?php
function loadavgs()
{
 global $avgfile;
 $file=fopen($avgfile,'r');
 while (($line=fgets($file,256))!==FALSE)
 {
  $elements=explode("\t",$line);
  if(count($elements)==3)
    $averages[$elements[0]][$elements[1]]=$elements[2];
 }
 fclose($file);
 return $averages;
}

class loctree
{
	//set localization strength

	function __construct($loctreefile,$nagylocok)
	{
     global $verbose,$sql;
	 $this->treenodes=array();
	 $maxID=0;
	 $currentlevel=1;
	 $file=fopen($loctreefile,"r");
	 if($file===FALSE) {$sql->close();die("Loctree.textile not found!");}
	 $tree=new tree(NULL);
	 $tree->name="Cell";
     $tree->term="GO:Cell";
	 $tree->startID=$maxID++;
	 $this->treenodes[]=&$tree;
	 while($line=fgets($file))
	 {
	  if($verbose>=3) echo($line."<br />");
	  $elements=explode(" ",trim($line));
	  if(count($elements)<2) continue;
	  $level=strlen($elements[0]);
	  if($level<$currentlevel)
	  {
	   while ($level<$currentlevel)
	   {
		$tree->endID=$maxID++;
		if($verbose>=3) echo("endID for node {$tree->name} is:{$tree->endID}<br />");
		$tree=&$tree->parnt;
		$currentlevel--;
	   }
	  }
	   $child=new tree($tree);
	   $child->startID=$maxID++;
	   $child->name=substr($line,strlen($elements[0]));
	   preg_match_all("/\\((GO:[^)]*)\\)/", $line, $match);
	   $child->term=$match[1][0];
	   if($verbose>=4) {print_r($match);echo("<br />");}
	   if($verbose>=3)echo("Adding child {$child->name} (term:{$child->term}) at level {$currentlevel}, startID:{$child->startID}<br />");
	   $this->treenodes[]=$child;
	   $tree=&$this->treenodes[count($this->treenodes)-1];
	   $currentlevel++;
	}
   while ($currentlevel>0)
   {
	$tree->endID=$maxID++;
	if($verbose>=3) echo("endID for node {$tree->name} is:{$tree->endID}<br />");
	$tree=$tree->parnt;
	$currentlevel--;
   }
   fclose($file);
   $file=fopen($nagylocok,"r");
   if($file===FALSE) {$sql->close();die("largelocIDs.txt not found!");}
   $this->largelocs=array();
   while($line=fgets($file))
   {
    $elements=explode(" ",trim($line));
	$terms=array();
	for($i=1;$i<count($elements);$i++)
	{
	 if($elements[$i][0]=="!")
	  $terms[]=array(substr($elements[$i],1),0);
	 else $terms[]=array($elements[$i],1); //second element of array tells to recurse underlying terms into localization
	}
	if($verbose>=1)echo("Adding large localization {$elements[0]}...");
	if($verbose>=2){
	foreach($terms as $goterma)
	 {echo($goterma[0]);if($goterma[1]==0) echo("-norecurse "); else if($goterma[1]==1) echo("-recurse ");}
	 }
	if($verbose>=1)echo ("<br />");
	$this->largelocs[$elements[0]]=$terms;
   }
   fclose($file);
 }

	function climb($loc)
		{
         global $verbose;
		  $node=$this->treenodes[0];
		  //climb down to node (faster than foreach search)
		  while($node->startID!=$loc)
		  {
		   if($verbose>=3) echo("Checking node {$node->name} ({$node->startID}-{$node->endID})...<br />");
		   if($node->endID==$loc) {$sql->close();die("I've got an endID (bogus loctree?):(");}
		   foreach($node->children as $child)
			{
			 if($verbose>=3) echo ("Child:{$child->name} ({$child->startID}-{$child->endID})");
			 if(($child->startID<=$loc)&&($child->endID>=$loc)) {$node=&$child;if($verbose>=3) echo(":)");break;}
			 if($verbose>=3) echo (":(");
			}
		  }
		  if($verbose>=2) echo("Node found ({$node->name}) <br />");
		  //climb up to first bigLoc
		  $found=FALSE;
		  $climbed=FALSE;
		  while(($found===FALSE)&&($node->parnt!=NULL))
		  {
			  foreach($this->largelocs as $locname=>$goterms)
			  {
				foreach($goterms as $gotermarray)
					{
					 if(
					 ($node->term==$gotermarray[0])&&
					 (
					  (($gotermarray[1]==0)&&($climbed==false))
					  ||
					  ($gotermarray[1]==1)
					 )
					 )
					 {$found=$locname;if($verbose>=2) echo("Found!");break;}
					}
				if($found!==FALSE) break;
			 }
			$climbed=true;
			$node=&$node->parnt;
            if($verbose>=2) echo("Climbing to... {$node->name} ({$node->term})");
		  }
		  if($verbose>=2) echo("BigLoc found ({$found}) <br />");
		  return $found;
		}

	function getLocArray($id,$sql)
	{
        global $verbose,$fi_loc_exp,$fi_loc_pred,$fi_loc_unknown,$confidence_type_predicted,$confidence_type_experimental,$confidence_type_unknown;
		$ret=array();
		$res=$sql->query("SELECT localizationId,confidenceType FROM ProteinToLocalization INNER JOIN ProtLocToSystemType ON ProtLocToSystemType.protLocId=ProteinToLocalization.id INNER JOIN SystemType ON ProtLocToSystemType.systemTypeId= SystemType.id WHERE ProteinToLocalization.proteinId={$id}");
		while($kl=$res->fetch_assoc())
		{
		  //localization strength
		  $fi_loc=$fi_loc_unknown;
		  if($kl['confidenceType']==$confidence_type_predicted) $fi_loc=$fi_loc_pred;
		  else if($kl['confidenceType']==$confidence_type_experimental) $fi_loc=$fi_loc_exp;
		 $nagyloc=$this->climb($kl['localizationId']);
		 //ez settel lenne szép, de PHPban nincs :(
		 //if new location, then add, if existing, then upgrade if this is stronger
		 if($nagyloc===FALSE) {echo("Warning: No large location found for node {$id} (loc {$kl['localizationId']})\n");
			$nagyloc="Cell";}
		 if(array_key_exists($nagyloc,$ret)==FALSE)
			$ret[$nagyloc]=$fi_loc;
		 else
		  $ret[$nagyloc]=or_operator($fi_loc,$ret[$nagyloc]);
		}
		return $ret;
	}
}


function or_operator($a,$b)
{
	return 1-(1-$a)*(1-$b);
}
function or_array($array)
{
	$running_mult=1;
	foreach ($array as $a)
		$running_mult*=(1-$a);
	return 1-$running_mult;
}

function correct($a,$b,$avg,$pow)
{
    $x=$avg+($b-$avg)*$pow;
    if($b<$avg) return $a*(1-$pow+($pow*$b/$avg));
    else return or_operator($a,$pow*($b-$avg)/(1-$avg));

}

class tree
{
 public $name="";
 public $startID=0;
 public $endID=0;

 function __construct($par)
 {
  if($par!=NULL)
  {
   $this->parnt=&$par;
   $this->parnt->children[]=&$this;
  }
  else $this->parnt=NULL;
 }
}

function getNeighbors($id,$other,$sql)
{
global $verbose;
$neighbors=array();
$res=$sql->query("SELECT actorAid,actorBid FROM Interaction WHERE actorAid={$id} OR actorBid={$id}");
while($n=$res->fetch_assoc())
{
 if(($n['actorAid']==$id)&&($n['actorBid']!=$other))
	$neighbors[]=$n['actorBid'];
 if(($n['actorBid']==$id)&&($n['actorAid']!=$other))
	$neighbors[]=$n['actorAid'];
}
if($verbose>0) echo(count($neighbors)." interactors found for node {$id}.\n");
return $neighbors;
}
?>

