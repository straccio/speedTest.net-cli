speedtest.Net-cli
=================
>a command line tool to do speedtest.net tests with Nagios output.

Platforms:
----------
* Linux (for Ubuntu run install.sh for dependency)
* Mac OSX

Configuration:
--------------
edit "speedTest.conf.php"
<pre>
    define('LATENCY_ROUNDS', 5);    //How many time do the latency test for server
    define('TIMEOUT_LATENCY',5);		//Timeout for latency request
    define('TIMEOUT_UPLOAD_DOWNLOAD',1000);	//Timeout for download/upload requests
    $maxDistance=100;               //Max distance in km used by servers filter when you don't specify the location
    $tmpdir = '/tmp/';              //temp folder used to download test files
    $downloadSizes=array(2000);     //Specify the size for download images, it download one image for size
    $uploadSizes=array(10M);        //Specify the size for upload file, it automatic create it if needed.
    $smallDownloadSizes=array(750); //Specify the size for download images for small test, it download one image for size
    $smallUploadSizes=array(2M);    //Specify the size for upload file for small test, it automatic create it if needed.
</pre>

Usage:
------
<pre>
    --getlist                       Download the list server.
	--location=[server location]    Tell server localtion example 'cesena'.
                                    If not specified it find the best sever in 100km.
    --server=[server url]           Specify a custom url server
    --nobest                        Don't find the best server, execute test on all servers.
                                    You need to specify a location.
    --proxy=[host:port]             Force using defined proxy server.
    --noproxy                       Force NOT using even if define the env http_proxy.
    --nagios                        Force Nagios output, verbose still disabled
    --smalltest                     Execute test with small upload and download files.
    --verbose                       Execute script with verbose loggin, no Nagios output.
    --help|-h|-?                    This help.
</pre>
Examples:
---------
<pre>
	./test.php
		do test with best server in 100km.

	./test.php --location=rome
		do test with best server located in Rome.

	./test.php --location=rome --nobest
		do test with all servers located in Rome.

	./test.php --server="http://192.168.0.1/speedtest/"
		do test with the server url specified, useful for lan tests.

	./test.php --server="http://192.168.0.1/speedtest/" --smalltest
		do small test with the server url specified, useful for lan tests.
</pre>