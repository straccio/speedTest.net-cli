<?php
define('LATENCY_ROUNDS', 5);		//How many time do the latency test for server
$maxDistance=100;			//Max distance in km used by servers filter when you don't specify the location
$tmpdir = '/tmp/';			//temp folder used to download test files
$downloadSizes=array(			//Specify the size for download images, it download one image for size
//    350,
//    500,
//    750,
//    1000,
//    1500,
//    2000,
    2500,
//    3000,
//    3500,
//    4000,
    
);
$uploadSizes=array(			//Specify the size for upload file, it automatic create it if needed.
//    "500K",
//    "1M",
//    "2M",
//    "4M",
    "10M",
);	


$smallDownloadSizes=array(			//Specify the size for download images for small test, it download one image for size
//    350,
//    500,
//    750,
    1000,
//    1500,
//    2000,
//    2500,
//    3000,
//    3500,
//    4000,
);
$smallUploadSizes=array(			//Specify the size for upload file for small test, it automatic create it if needed.
//    "500K",
//    "1M",
    "2M",
//    "4M",
//    "10M",
);	
?>
