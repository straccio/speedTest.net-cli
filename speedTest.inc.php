<?php

//dd if=/dev/urandom of=upload_2 bs=1K count=500
//dd if=/dev/urandom of=upload_3 bs=1M count=1
require_once 'speedTest.conf.php';
require_once 'xmlserver.map.php';

global
$curpath,
$maxDistance,
$conInfo,
$downloadSizes,
$randoms,
$tmpdir;

$randoms = rand( 100000000000, 9999999999999 );
$curpath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;


//def Distance(self, one, two):
//    #Calculate the great circle distance between two points
//    #on the earth specified in decimal degrees (haversine formula)
//    #(http://stackoverflow.com/posts/4913653/revisions)
//    # convert decimal degrees to radians
//
//        lon1, lat1, lon2, lat2 = map(radians, [one[0], one[1], two[0], two[1]])
//        # haversine formula
//        dlon = lon2 - lon1
//        dlat = lat2 - lat1
//        a = sin(dlat/2)**2 + cos(lat1) * cos(lat2) * sin(dlon/2)**2
//        c = 2 * asin(sqrt(a))
//        km = 6367 * c
//        return km
function getDistanceInKm ( $p1,
													 $p2
) {
	$iLat = 0;
	$iLon = 1;

	$lon_1 = $p1[ $iLon ];
	$lon_2 = $p2[ $iLon ];
	$lat_1 = $p1[ $iLat ];
	$lat_2 = $p2[ $iLat ];


	$earth_radius = 6367; //in km
	$delta_lat    = $lat_2 - $lat_1;
	$delta_lon    = $lon_2 - $lon_1;
	$alpha        = $delta_lat / 2;
	$beta         = $delta_lon / 2;
	$a            = sin( deg2rad( $alpha ) ) * sin( deg2rad( $alpha ) ) + cos( deg2rad( $lat_1 ) ) * cos( deg2rad( $lat_2 ) ) * sin( deg2rad( $beta ) ) * sin( deg2rad( $beta ) );
	$c            = asin( min( 1, sqrt( $a ) ) );
	$distance     = 2 * $earth_radius * $c;
	$distance     = round( $distance, 4 );

	return $distance;
}

function findBestServer ( &$servers ) {
	global
	$verbose,
	$randoms;
	$ret         = array();
	$bestLatency = $randoms;
	foreach ( $servers as &$server ) {
		//@TODO
		$server[ 'latency' ] = getLatency( $server );
		if ( $server[ 'latency' ] !== FALSE && $server[ 'latency' ][ 'avg' ] < $bestLatency ) {
			$ret         =& $server;
			$bestLatency = $server[ 'latency' ][ 'avg' ];
		}
	}
	if ( $verbose ) {
		echo "\033[91m\nBest server founded is " . $ret[ 'name' ] . "(" . $ret[ 'countrycode' ] . ') at url ' . $ret[ 'url' ] . " with " . $ret[ 'distance' ] . "km of distance\033[0m\n";
	}

	return $ret;
}

function doTest ( $location,
									$findBest = TRUE
) {
	global
	$conInfo,
	$verbose;
	$internal = FALSE;
	if ( is_array( $location ) ) {
		$internal = TRUE;
	}

	$latencyAvarage  = 0;
	$downloadAvarage = 0;
	$uploadAvarage   = 0;
	$getLatency      = FALSE;

	$ret = array();

	if ( !$internal ) {
		$conInfo = getConnectionInfo();
		$servers = getServer( $location );
	} else {
		$servers = array( $location );
	}
	if ( $findBest && count( $servers ) > 1 ) {
		$bestServer = findBestServer( $servers );
		//print_r($bestServer);
		$servers = array( $bestServer );
		$latencyAvarage += $bestServer[ 'latency' ][ 'avg' ];
	} else {
		$getLatency = TRUE;
	}
	foreach ( $servers as $server ) {
		if ( $getLatency ) {
			$l = getLatency( $server );
			if ( $l === FALSE ) {
				return FALSE;
			}
			$latencyAvarage += $l[ 'avg' ];
		}

		$d = getDownload( $server );
		if ( $d === FALSE ) {
			return FALSE;
		}
		$downloadAvarage += $d[ 'avg' ];


		$u = getUpload( $server );
		if ( $u === FALSE ) {
			return FALSE;
		}
		$uploadAvarage += $u[ 'avg' ];
	}

	return array(
		'latency'  => round( $latencyAvarage / count( $servers ), 2 ),
		'download' => round( $downloadAvarage / count( $servers ), 2 ),
		'upload'   => round( $uploadAvarage / count( $servers ), 2 ),
	);

//    $ret['latency'] = getLatency($server['url']);
//    if($ret['latency']===false)
//	return false;
//
//    $ret['download'] = getDownload($server['url']);
//    if($ret['download']===false)
//	return false;
//
//    $ret['upload'] = getUpload($server['url']);
//    if($ret['upload']===false)
//	return false;
//
//    if($verbose)
//	echo "\033[91m\nAvarage latency: " . $ret['latency']['avg'] ."s\nAvarage download: " . $ret['download']['avg']."Mb/s\nAvarage upload: " . $ret['upload']['avg']."Mb/s\n"."\033[0m";

	return $ret;
}

function downloadServerList () {
	global
	$curpath,
	$verbose;
	$cmd = "curl ".SERVER_LIST_URL." > " . $curpath . "testservers.xml";
	//$cmd = "curl http://www.speedtest.net/speedtest-servers.php > " . $curpath . "testservers.xml";
	if ( $verbose ) {
		echo 'Downloading server list from http://www.speedtest.net/speedtest-servers.php' . "\n";
	}
	if ( !$verbose ) {
		$cmd .= " 2> /dev/null";
	}
	shell_exec( $cmd );
}

function getConnectionInfo () {
	global
	$tmpdir,
	$randoms,
	$verbose,
	$curl_proxy;

	$ret = array();

	$file = $tmpdir . $randoms . 'connectionInfo.txt';

	$cmd = "curl \"http://www.speedtest.net/speedtest-config.php?x=" . $randoms . "\" > " . $file . " 2>/dev/null";
	if ( $verbose ) {
		echo "\033[92mGetting connection info\033[0m\n";
	}
	shell_exec( $cmd );

	$cmd = 'cat ' . $file . '|grep -i "<client"';
	$out = shell_exec( $cmd );

	unlink( $file );

	if ( preg_match( '/ip="(?P<ip>[^"]*)".*lat="(?P<lat>[^"]*).*lon="(?P<lon>[^"]*).*isp="(?P<isp>[^"]*).*/', $out, $m ) ) {
		$ret = array(
			'ip'     => $m[ 'ip' ],
			'isp'    => $m[ 'isp' ],
			'coords' => array( $m[ 'lat' ], $m[ 'lon' ] )
		);
	} else {
		return FALSE;
	}

	if ( $verbose ) {
		echo "\tISP:\t" . $ret[ 'isp' ] . "\n\tIP:\t" . $ret[ 'ip' ] . "\n\tLat:\t" . $ret[ 'coords' ][ '0' ] . "\n\tLon:\t" . $ret[ 'coords' ][ 1 ] . "\n\n";
	}

	return $ret;

}

function getServer ( $location ) {
	global
	$xmlservermap,
	$curpath,
	$maxDistance,
	$conInfo,
	$verbose;

	$ret = array();
	//$cmd = 'cat testservers.xml | grep -i "\"'.$location.'\"" |head -1 |sed -E \'s/.* url="(https?:\/\/[^\/]*)\/.*name="([^"]*).*countrycode="([^"]*)".*/\1/\'';
	if ( !file_exists( $curpath . "testservers.xml" ) ) {
		if ( downloadServerList() === FALSE ) {
			return FALSE;
		}
	}
	if ( $location ) {
		$cmd = 'cat ' . $curpath . 'testservers.xml | grep -i "\"' . $location . '\""';
	} else {
		$cmd = 'cat ' . $curpath . 'testservers.xml';
	}
	//$cmd = 'cat testservers.xml | grep -i "\"'.$location.'\"" |head -1';
//	|sed -E \'s/.* url="(?P<url>https?:\/\/[^\/]*)\/.*name="([^"]*).*countrycode="([^"]*)".*/\1/\'';

	if ( $verbose ) {
		echo "\033[92mGetting servers from list\033[0m\n";
	}

	$output = "";
	exec( $cmd, $output );
	foreach ( $output as $row ) {
		$regex = '/.* ' . $xmlservermap[ 'url' ] . '="(?P<url>https?:\/\/[^\/]*)\/.*' . $xmlservermap[ 'lat' ] . '="(?P<lat>[^"]*).*' . $xmlservermap[ 'lon' ] . '="(?P<lon>[^"]*).*' . $xmlservermap[ 'name' ] . '="(?P<name>[^"]*).*' . $xmlservermap[ 'countrycode' ] . '="(?P<countrycode>[^"]*)".*/';
		if ( preg_match( $regex, $row, $m ) ) {
			$s = array(
				'name'        => $m[ 'name' ],
				'url'         => $m[ 'url' ],
				'countrycode' => $m[ 'countrycode' ],
				'coords'      => array( $m[ 'lat' ], $m[ 'lon' ] ),
				'distance'    => getDistanceInKm( $conInfo[ 'coords' ], array( $m[ 'lat' ], $m[ 'lon' ] ) )
			);
			array_push( $ret, $s );
			if ( $verbose && $location ) {
				echo "\tFound server " . $s[ 'name' ] . " at url " . $s[ 'url' ] . " with " . $s[ 'distance' ] . "km of distance\n";
			}
		}
	}
	if ( !$location ) {
		if ( $verbose ) {
			echo "\n\033[92mFiltering server in " . $maxDistance . "km\033[0m\n";
		}

		$ret = array_filter( $ret, "filterByDistance" );
	}


//    $ret = $output[0];

	return $ret;
}

function filterByDistance ( $server ) {
	global
	$maxDistance;

	return $server[ 'distance' ] <= $maxDistance;
}

function getLatency ( $server ) {
	global
	$verbose,
	$randoms,
	$curl_proxy,
	$tmpdir;
	$ret            = array();
	$rounds         = array();
	$globalDuration = 0;


	if ( $verbose ) {
		echo "\n\033[92mGetting latency for " . $server[ 'name' ] . "(" . $server[ 'countrycode' ] . ") from " . $server[ 'url' ] . "\033[0m\n";
	}
	for ( $i = 0; $i <= LATENCY_ROUNDS; $i++ ) {
		$file = $tmpdir . $randoms . 'latency.txt';
		$fp   = fopen( $file, 'w+' );
		$ch   = curl_init( $server[ 'url' ] . "/speedtest/latency.txt?x=" . $randoms );

		if ( $curl_proxy ) curl_setopt( $ch, CURLOPT_PROXY, $curl_proxy );

		curl_setopt( $ch, CURLOPT_HEADER, TRUE );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_TIMEOUT, TIMEOUT_LATENCY );
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
		curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
		curl_setopt( $ch, CURLOPT_FRESH_CONNECT, 1 );

		if ( $verbose ) {
			echo "\tRound " . $i . ":";
		}

		$starttime = microtime( TRUE );
		$response  = curl_exec( $ch );
		$endtime   = microtime( TRUE );
		$duration  = $endtime - $starttime;

		if ( $response === FALSE ) {
			if ( $verbose ) {
				echo "\nRequest failed " . curl_error( $ch ) . "\n";
			}
			curl_close( $ch );
			fclose( $fp );

			return FALSE;
		}

		array_push( $rounds, round( $duration, 2 ) );

		if ( $verbose ) {
			echo "\t" . round( $duration, 2 ) . "s\n";
		}


		$globalDuration += $duration;

		curl_close( $ch );
		fclose( $fp );
		unlink( $file );
	}
	$ret[ 'rounds' ] = $rounds;
	$ret[ 'avg' ]    = round( $globalDuration / LATENCY_ROUNDS, 2 );

	if ( $verbose ) {
		echo "\tAvarage:\t\033[91m" . $ret[ 'avg' ] . "s\033[0m\n";
	}

	return $ret;
}

function getDownload ( $server ) {
	global
	$iface,
	$downloadSizes,
	$verbose,
	$randoms,
	$curl_proxy,
	$tmpdir;

	$ret       = array();
	$downloads = array();

	if ( $verbose ) {
		echo "\n\033[92mGetting download for " . $server[ 'name' ] . "(" . $server[ 'countrycode' ] . ") from " . $server[ 'url' ] . "\033[0m\n";
	}

	$i                  = 1;
	$globalTransferRate = 0;
	foreach ( $downloadSizes as $size ) {
		$file = $tmpdir . $randoms . "_download_" . $size . ".jpg";
		$fp   = fopen( $file, 'w+' );

		$ln = $server[ 'url' ] . "/speedtest/random" . $size . "x" . $size . ".jpg?x=" . $randoms . "-" . $i;
		$i++;
		$ch = curl_init();

		if ( $curl_proxy ) curl_setopt( $ch, CURLOPT_PROXY, $curl_proxy );
		curl_setopt( $ch, CURLOPT_URL, $ln );
		curl_setopt( $ch, CURLOPT_HEADER, TRUE );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_FILE, $fp );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );
		curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
		curl_setopt( $ch, CURLOPT_FRESH_CONNECT, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, TIMEOUT_UPLOAD_DOWNLOAD );
		//curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);


		if ( $verbose ) {
			echo "\tSize " . $size . "x" . $size . ":\n\t\tUrl: " . $ln;
		}

		$starttime = microtime( TRUE );

		$response = curl_exec( $ch );

		$endtime = microtime( TRUE );

		if ( $response === FALSE ) {
			if ( $verbose ) {
				echo "\nRequest failed" . curl_error( $ch ) . "\n";
			}
			curl_close( $ch );
			fclose( $fp );

			return FALSE;
		}

		curl_close( $ch );
		fclose( $fp );

		$duration = round( $endtime - $starttime, 2 );

		$fileSize = round( filesize( $file ) / 1024 / 1024, 2 );
//	$fileSize=round(strlen($response)/1000000,2);
		$transferRate = round( $fileSize * 8 / $duration, 2 );

		$globalTransferRate += $transferRate;

		array_push( $downloads, array( 'size' => $fileSize, 'time' => $duration, 'transfer-rate' => $transferRate ) );


		if ( $verbose ) {
			echo "\n\t\tDownloaded " . $fileSize . "MB in " . $duration . "s at " . $transferRate . "Mbps\n";
		}

		unlink( $file );
	}

	$avarageTransferRate = round( $globalTransferRate / count( $downloadSizes ), 2 );
	if ( $verbose ) {
		echo "\n\tAvarage transfer rate:\t\033[91m" . $avarageTransferRate . "Mbps\033[0m\n";
	}

	$ret[ 'avg' ]       = $avarageTransferRate;
	$ret[ 'downloads' ] = $downloads;

	return $ret;
}


function getUpload ( $server ) {
	global
	$curpath,
	$iface,
	$uploadSizes,
	$verbose,
	$randoms,
	$curl_proxy,
	$tmpdir;

	$ret     = array();
	$uploads = array();

	if ( $verbose ) {
		echo "\n\033[92mGetting uploads for " . $server[ 'name' ] . "(" . $server[ 'countrycode' ] . ") from " . $server[ 'url' ] . "\033[0m\n";
	}
	foreach ( $uploadSizes as $size ) {
		if ( !file_exists( $curpath . 'uploads/' ) ) {
			mkdir( 'uploads' );
		}
		$file = $curpath . 'uploads/' . "upload_" . $size;
		if ( !file_exists( $file ) ) {
			if ( $verbose ) {
				echo "\tCreating upload file for " . $size . "\n";
			}
			$blocks    = substr( $size, 0, -1 );
			$blocksize = substr( $size, -1 );
			if( PHP_OS == "Darwin" ){
				$blocksize = strtolower($blocksize);
			}
			shell_exec( "dd if=/dev/urandom of=" . $file . " bs=1" . $blocksize . " count=" . $blocks . " > /dev/null 2>&1" );
		}

		$ch = curl_init();
		$ln = $server[ 'url' ] . "/speedtest/upload.php?x=0." . $randoms;

		if ( $curl_proxy ) curl_setopt( $ch, CURLOPT_PROXY, $curl_proxy );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_VERBOSE, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)" );
		curl_setopt( $ch, CURLOPT_URL, $ln );
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );
		curl_setopt( $ch, CURLOPT_FRESH_CONNECT, 1 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, TIMEOUT_UPLOAD_DOWNLOAD );


		$post = array(
			"file_box" => "@" . $file,
		);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );

		if ( $verbose ) {
			echo "\tSize " . $size . ":\n\t\tUrl: " . $ln;
		}

		$starttime = microtime( TRUE );

		$response = curl_exec( $ch );

		$endtime = microtime( TRUE );

		if ( $response === FALSE ) {
			if ( $verbose ) {
				echo "\nRequest failed" . curl_error( $ch ) . "\n";
			}
			curl_close( $ch );
			fclose( $fp );

			return FALSE;
		}

		$aiznem = substr( $response, 5 );
		$kopa   = filesize( $file ) + $aiznem;

		$duration = round( $endtime - $starttime, 2 );

		$fileSize     = round( ( filesize( $file ) + $aiznem ) / 1024 / 1024, 2 );
		$transferRate = round( $fileSize * 8 / $duration, 2 );

		$globalTransferRate += $transferRate;

		array_push( $uploads, array( 'size' => $fileSize, 'time' => $duration, 'transfer-rate' => $transferRate ) );


		if ( $verbose ) {
			echo "\n\t\tUploaded " . $fileSize . "MB in " . $duration . "s at " . $transferRate . "Mbps\n";
		}
	}
	$avarageTransferRate = round( $globalTransferRate / count( $uploadSizes ), 2 );
	if ( $verbose ) {
		echo "\n\tAvarage transfer rate:\t\033[91m" . $avarageTransferRate . "Mbps\033[0m\n";
	}

	$ret[ 'avg' ]     = $avarageTransferRate;
	$ret[ 'uploads' ] = $uploads;

	return $ret;
}

?>
