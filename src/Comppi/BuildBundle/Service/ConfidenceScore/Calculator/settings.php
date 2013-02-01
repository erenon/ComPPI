<?php
//USER VARIABLES
global $db_name,$db_pass,$db_host,$db_user,$nagylocID_file,$avgfile,$fi_int_ext,$fi_int_pred,$fi_int_unknown,$loctreetextile,$fi_loc_exp,$fi_loc_pred,$fi_loc_unknown,$npredpow,$confidence_type_unknown,$confidence_type_predicted,$confidence_type_experimental,$sql,$verbose;

$nagylocID_file="src/Comppi/BuildBundle/Service/ConfidenceScore/Calculator/largelocIDs.txt";
$avgfile="src/Comppi/BuildBundle/Service/ConfidenceScore/Calculator/average_scores.txt";
$fi_int_exp=1;
$fi_int_pred=0.8;
$fi_int_unknown=0.9;
$loctreetextile="databases/loctree/loctree.textile";
$fi_loc_exp=1;
$fi_loc_pred=0.8;
$fi_loc_unknown=0.9;
$npredpow=1;
$confidence_type_unknown=0;
$confidence_type_predicted=1;
$confidence_type_experimental=2;
//USER VARIABLES END HERE

// Hack to get db credentials
global $kernel;
$kernelRoot = $kernel->getRootDir(); // $kernel defined in app.php or app_dev.php
$configPath = $kernelRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'parameters.ini';

$config = parse_ini_file($configPath, true);

$db_host = $config['parameters']['database_host'];
$db_name = $config['parameters']['database_name'];
$db_user = $config['parameters']['database_user'];
$db_pass = $config['parameters']['database_password'];

unset($config);
unset($configPath);
unset($kernelRoot);

$sql=new mysqli("$db_host","$db_user","$db_pass","$db_name");
if(isset($_REQUEST['verbose'])) {$verbose=$_REQUEST['verbose']; if ($verbose>=-1) echo("Verbosity level {$verbose} set.\n");}
  else $verbose=-1;