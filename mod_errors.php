#!/opt/local/bin/php
<?php

//
// Description
// -----------
// This script will find all the error codes in the current module directory
//
//
$path = getcwd();
if( !file_exists($path . '/db') && !file_exists($path . '/public') ) {
    $path = dirname($path);
}
if( !file_exists($path . '/db') && !file_exists($path . '/public') ) {
    print "Must be used inside a module.\n";
    usage();
    exit;
}

//
// Search for the errors
//
$results = `grep "'code'=>" $path/*/*.php`;
$lines = explode("\n", $results);
$errors = array();
foreach($lines as $lid => $line) {
    $line = preg_replace("#^" . $path . "/#", '', $line);
    if( preg_match("/^([^:]*):.*'code'=>'([^\.']*)\.([^\.']*)\.([^\.']*)'/", $line, $matches) ) {
        $errors[] = array('file'=>$matches[1], 'package'=>$matches[2], 'module'=>$matches[3], 'number'=>$matches[4]);
    }
}
uasort($errors, function($a, $b) {
    if( $a['number'] == $b['number'] ) {
        return 0;
    }
    return ($a['number'] < $b['number']) ? -1 : 1;
});

$prev = 0;
$gaps = array();
$max = '';
foreach($errors as $err) {
    print $err['package'] . '.' . $err['module'] . '.' . sprintf("%-3s", $err['number']) . ' - ' . $err['file'] . "\n";
    if( ($err['number'] - $prev) > 1 ) {
        for($i = ($prev+1); $i < $err['number']; $i++) {
            $gaps[] = $i;
        }
    }
    $max = $err['package'] . '.' . $err['module'] . '.' . $err['number']; 
    $prev = $err['number'];
}

print "\n";
if( count($gaps) > 0 ) {
    print "Gaps: " . implode(', ', $gaps) . "\n";
}
exit;

//
// Print the usage of the script
//
function usage() {
    print "mod_errors.php\n\n";
}

?>
