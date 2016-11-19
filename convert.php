#!/opt/local/bin/php
<?php
//
// This fill will read in a file and convert all ciniki names to qruqsp names
//

if( !isset($argv['1']) ) {
    print "No file specified\n\n";
    usage();
    exit;
}

//
// Load file
//
$contents = file_get_contents($argv[1]);

//
// Make substitions
//
$contents = preg_replace("/ciniki/", 'qruqsp', $contents);
$contents = preg_replace("/\$qruqsp/", "\$q", $contents);
$contents = preg_replace("/businesses/", 'stations', $contents);
$contents = preg_replace("/business_id/", 'station_id', $contents);
$contents = preg_replace("/business/", 'station', $contents);
$contents = preg_replace("/Business/", 'Station', $contents);


$contents = preg_replace("/curBusinessID/", 'curStationID', $contents);

//
// Save file
//
file_put_contents($argv[1], $contents);

function usage() {
    print "convert.php <filename>\n\n";
}

?>
