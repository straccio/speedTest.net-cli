#!/usr/bin/env php
<?php
//require_once 'config.inc.php';
require_once 'speedTest.inc.php';
error_reporting(0);

global
    $smallDownloadSizes,
    $downloadSizes,
    $smallUploadSizes,
    $uploadSizes,
    $maxDistance,
    $getlist,
    $location,
    $noproxy,
    $verbose,
    $curl_proxy;

$shortops=array(
    '?',
    'h'
);

$longopts  = array(
    'getlist',
    "location::",
    "proxy::",
    "noproxy",
    "nobest",
    "verbose",
    "help",
    "nagios",
    "server::",
    "smalltest"
);

$options = getopt("h?", $longopts);

if(isset($options['?'])||isset($options['h'])||isset($options['help'])){
    echo "Usage:\n".
	 "\t--getlist\t\t\tDownload the list server.\n".
	 "\t--location=[server location]\tTell server localtion example 'cesena'.\n\t\t\t\t\tIf not specified it find the best sever in ".$maxDistance."km.\n".
	 "\t--server=[server url]\t\tSpecify a custom url server\n".
	 "\t--nobest\t\t\tDon't find the best server, execute test on all servers.\n\t\t\t\t\tYou need to specify a location.\n".
	 "\t--proxy=[host:port]\t\tForce using defined proxy server.\n".
	 "\t--noproxy\t\t\tForce NOT using even if define the env http_proxy.\n".
	 "\t--nagios\t\t\tForce Nagios output, verbose still disabled\n".
	 "\t--smalltest\t\t\tExecute test with small upload and download files.\n".
	 "\t--verbose\t\t\tExecute script with verbose loggin, no Nagios output.\n".
	 "\t--help|-h|-?:\t\t\tThis help.\n";
    echo "\nExamples:\n";
    echo "\t./test.php\n";
    echo "\t\tdo test with best server in ".$maxDistance."km.\n\n";
    echo "\t./test.php --location=rome\n";
    echo "\t\tdo test with best server located in Rome.\n\n";
    echo "\t./test.php --location=rome --nobest\n";
    echo "\t\tdo test with all servers located in Rome.\n\n";
    echo "\t./test.php --server=\"http://192.168.0.1/speedtest/\"\n";
    echo "\t\tdo test with the server url specified, useful for lan tests.\n\n";
    echo "\t./test.php --server=\"http://192.168.0.1/speedtest/\" --smalltest\n";
    echo "\t\tdo small test with the server url specified, useful for lan tests.\n\n";
    exit(1);
}

$verbose = isset($options['verbose']);
$noproxy = isset($options['noproxy']);
$getlist = isset($options['getlist']);
$nobest = isset($options['nobest']);
$nagios = isset($options['nagios']);
$smalltest = isset($options['smalltest']);
$location = $options['location'];
$server = $options['server'];

if($nagios)
    $verbose=false;

if($nobest && !$location){
    if($verbose){
	echo "\033[91mWith nobest option you need to specify a location.\033[0m\n";
    }else{
	echo "SpeedTest.net ERROR: With nobest option you need to specify a location";
    }
    exit(2);
}

if($smalltest){
    $downloadSizes=$smallDownloadSizes;
    $uploadSizes=$smallUploadSizes;
}

if(!$noproxy){
    $curl_proxy=$options['proxy'];
    if (!$curl_proxy) $curl_proxy=getenv("http_proxy");
}

if($getlist) downloadServerList();

//if($location){
//    $server=getServer($location);
//}
//if($server){
    if($server){
	$data=doTest(array(
	    'name'=>'custom',
	    'url'=>$server,
	    'countrycode'=>'N\A'
	),false);
    }else{
	$data=doTest($location,!$nobest);
    }
    if($data===false){
	if($nagios){
	    echo "SpeedTest.net WARNING: connection lost";
	}else{
	    echo "\033[91mConnection lost\033[0m\n";
	}
	exit(1);
    }else{
	if($nagios){
	    echo "SpeedTest.net OK: Test successful |Latency=" .$data['latency'] ."s;Download=" . $data['download']."Mbps;Upload=" . $data['upload']."Mbps";
	}else{
	    if($verbose) 
		echo "\n";
	    echo "\033[92mAvarage results\033[0m\n\tLatency:\t\t\033[91m".$data['latency'] ."s\033[0m\n\tDownload transfer rate:\t\033[91m" . $data['download']."Mbps\033[0m\n\tUpload transfer rate:\t\033[91m" . $data['upload']."Mbps\033[0m\n";
	}
	exit(0);
    }
    
//}else{
//    if(!$verbose){
//	echo "SpeedTest.net WARNING: no server for location found";
//	exit(1);
//    }
//}


?>
