Platform: *nix 
    on ubuntu run "install.sh" to install dependency.

Open "speedTest.conf.php" for test configuration.


Usage:
	--getlist			Download the list server.
	--location=[server location]	Tell server localtion example 'cesena'.
					If not specified it find the best sever in 100km.
	--server=[server url]		Specify a custom url server
	--nobest			Don't find the best server, execute test on all servers.
					You need to specify a location.
	--proxy=[host:port]		Force using defined proxy server.
	--noproxy			Force NOT using even if define the env http_proxy.
	--nagios			Force Nagios output, verbose still disabled
	--smalltest			Execute test with small upload and download files.
	--verbose			Execute script with verbose loggin, no Nagios output.
	--help|-h|-?:			This help.

Examples:
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