#!/opt/local/bin/php
<?php

//
// Description
// -----------
// This script will create the files required for a new module.  This must be run in
// the modules public directory.
//
//
if( !isset($argv[1]) || !isset($argv[2]) || !isset($argv[3]) || !isset($argv[4]) || !isset($argv[5]) ) {
    usage();
    exit;
}

$package = $argv[1];
$module = $argv[2];
$object = $argv[3];
$object_id = $argv[4];
$cur_code = $argv[5];

print "\n";

//
// Load the objects
//
require('../private/objects.php');
$fn = "{$package}_{$module}_objects";
$rc = $fn(array());
$objects = $rc['objects'];

if( !isset($objects[strtolower($object)]) ) {
    print "Missing object definition.\n";
    exit;
}

$object_def = $objects[strtolower($object)];

generate_add();
generate_delete();
generate_get();
generate_history();
generate_list();
generate_update();

print "done\n";

exit;

//
// Print the usage of the script
//
function usage() {
    print "mod_init.php <package> <module> <object> <object_id> <error_code>\n\n";
}

//
// objectAdd.php
//
function generate_add() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;
    global $cur_code;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This method will add a new " . strtolower($object_def['name']) . " for the station.\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// station_id:        The ID of the station to add the {$object_def['name']} to.\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}Add(&\$q) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = qruqsp_core_prepareArgs(\$q, 'no', array(\n"
        . "        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),\n"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= "        '$field_id'=>array('required'=>'" . (isset($field['default'])?'no':'yes') . "', 'blank'=>'" . (isset($field['default'])?'yes':'no') . "', 'name'=>'{$field['name']}'),\n";
    }
    $file .= ""
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Check access to station_id as owner\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, '{$package}', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$q, \$args['station_id'], '{$package}.{$module}.{$object}Add');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n";
    if( isset($object_def['fields']['permalink']) ) {
        $file .= "    //\n"
            . "    // Setup permalink\n"
            . "    //\n"
            . "    if( !isset(\$args['permalink']) || \$args['permalink'] == '' ) {\n"
            . "        qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'makePermalink');\n"
            . "        \$args['permalink'] = qruqsp_core_makePermalink(\$q, \$args['name']);\n"
            . "    }\n"
            . "\n"
            . "    //\n"
            . "    // Make sure the permalink is unique\n"
            . "    //\n"
            . "    \$strsql = \"SELECT id, name, permalink \"\n"
            . "        . \"FROM {$object_def['table']} \"\n"
            . "        . \"WHERE station_id = '\" . qruqsp_core_dbQuote(\$q, \$args['station_id']) . \"' \"\n"
            . "        . \"AND permalink = '\" . qruqsp_core_dbQuote(\$q, \$args['permalink']) . \"' \"\n"
            . "        . \"\";\n"
            . "    \$rc = qruqsp_core_dbHashQuery(\$q, \$strsql, '{$package}.{$module}', 'item');\n"
            . "    if( \$rc['stat'] != 'ok' ) {\n"
            . "        return \$rc;\n"
            . "    }\n"
            . "    if( \$rc['num_rows'] > 0 ) {\n"
            . "        return array('stat'=>'fail', 'err'=>array('pkg'=>'qruqsp', 'code'=>'" . $cur_code++ . "', 'msg'=>'You already have a " . strtolower($object_def['name']) . " with that name, please choose another.'));\n"
            . "    }\n"
            . "\n";
    }
    $file .= "    //\n"
        . "    // Start transaction\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionStart');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');\n"
        . "    \$rc = qruqsp_core_dbTransactionStart(\$q, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Add the " . strtolower($object_def['name']) . " to the database\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'objectAdd');\n"
        . "    \$rc = qruqsp_core_objectAdd(\$q, \$args['station_id'], '{$package}.{$module}." . strtolower($object) . "', \$args, 0x04);\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        qruqsp_core_dbTransactionRollback(\$q, '{$package}.{$module}');\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \${$object_id} = \$rc['id'];\n"
        . "\n"
        . "    //\n"
        . "    // Commit the transaction\n"
        . "    //\n"
        . "    \$rc = qruqsp_core_dbTransactionCommit(\$q, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Update the last_change date in the station modules\n"
        . "    // Ignore the result, as we don't want to stop user updates if this fails.\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'updateModuleChangeDate');\n"
        . "    qruqsp_core_updateModuleChangeDate(\$q, \$args['station_id'], '{$package}', '{$module}');\n"
        . "\n"
        . "    //\n"
        . "    // Update the web index if enabled\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'hookExec');\n"
        . "    qruqsp_core_hookExec(\$q, \$args['station_id'], 'qruqsp', 'web', 'indexObject', array('object'=>'{$package}.{$module}.{$object}', 'object_id'=>\${$object_id}));\n"
        . "\n"
        . "    return array('stat'=>'ok', 'id'=>\${$object_id});\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'Add.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectDelete.php
//
function generate_delete() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;
    global $cur_code;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This method will delete an " . strtolower($object_def['name']) . ".\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// station_id:            The ID of the station the " . strtolower($object_def['name']) . " is attached to.\n"
        . "// {$object_id}:            The ID of the " . strtolower($object_def['name']) . " to be removed.\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}Delete(&\$q) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = qruqsp_core_prepareArgs(\$q, 'no', array(\n"
        . "        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),\n"
        . "        '{$object_id}'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'{$object_def['name']}'),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Check access to station_id as owner\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$q, \$args['station_id'], 'qruqsp.{$module}.{$object}Delete');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Get the current settings for the " . strtolower($object_def['name']) . "\n"
        . "    //\n"
        . "    \$strsql = \"SELECT id, uuid \"\n"
        . "        . \"FROM {$object_def['table']} \"\n"
        . "        . \"WHERE station_id = '\" . qruqsp_core_dbQuote(\$q, \$args['station_id']) . \"' \"\n"
        . "        . \"AND id = '\" . qruqsp_core_dbQuote(\$q, \$args['{$object_id}']) . \"' \"\n"
        . "        . \"\";\n"
        . "    \$rc = qruqsp_core_dbHashQuery(\$q, \$strsql, 'qruqsp.{$module}', '{$object_def['o_name']}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    if( !isset(\$rc['{$object_def['o_name']}']) ) {\n"
        . "        return array('stat'=>'fail', 'err'=>array('pkg'=>'qruqsp', 'code'=>'" . $cur_code++ . "', 'msg'=>'{$object_def['name']} does not exist.'));\n"
        . "    }\n"
        . "    \${$object_def['o_name']} = \$rc['{$object_def['o_name']}'];\n"
        . "\n"
        . "    //\n"
        . "    // Start transaction\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionStart');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbDelete');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'objectDelete');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');\n"
        . "    \$rc = qruqsp_core_dbTransactionStart(\$q, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Remove the {$object_def['o_name']}\n"
        . "    //\n"
        . "    \$rc = qruqsp_core_objectDelete(\$q, \$args['station_id'], '{$package}.{$module}." . strtolower($object) . "',\n"
        . "        \$args['{$object_id}'], \${$object_def['o_name']}['uuid'], 0x04);\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        qruqsp_core_dbTransactionRollback(\$q, '{$package}.{$module}');\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Commit the transaction\n"
        . "    //\n"
        . "    \$rc = qruqsp_core_dbTransactionCommit(\$q, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Update the last_change date in the station modules\n"
        . "    // Ignore the result, as we don't want to stop user updates if this fails.\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'updateModuleChangeDate');\n"
        . "    qruqsp_core_updateModuleChangeDate(\$q, \$args['station_id'], '{$package}', '{$module}');\n"
        . "\n"
        . "    return array('stat'=>'ok');\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'Delete.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectGet.php
//
function generate_get() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;
    global $cur_code;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// ===========\n"
        . "// This method will return all the information about an " . strtolower($object_def['name']) . ".\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// station_id:         The ID of the station the " . strtolower($object_def['name']) . " is attached to.\n"
        . "// {$object_id}:          The ID of the " . strtolower($object_def['name']) . " to get the details for.\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}Get(\$q) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = qruqsp_core_prepareArgs(\$q, 'no', array(\n"
        . "        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),\n"
        . "        '{$object_id}'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'{$object_def['name']}'),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Make sure this module is activated, and\n"
        . "    // check permission to run this function for this station\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, '{$package}', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$q, \$args['station_id'], '{$package}.{$module}.{$object}Get');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Load station settings\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'intlSettings');\n"
        . "    \$rc = qruqsp_core_intlSettings(\$q, \$args['station_id']);\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$intl_timezone = \$rc['settings']['intl-default-timezone'];\n"
        . "    \$intl_currency_fmt = numfmt_create(\$rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);\n"
        . "    \$intl_currency = \$rc['settings']['intl-default-currency'];\n"
        . "\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'datetimeFormat');\n"
        . "    \$datetime_format = qruqsp_core_datetimeFormat(\$q, 'php');\n"
        . "\n"
        . "    //\n"
        . "    // Return default for new {$object_def['name']}\n"
        . "    //\n"
        . "    if( \$args['{$object_id}'] == 0 ) {\n"
        . "        \${$object_def['o_name']} = array('id'=>0,\n"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= "            '$field_id'=>'" . (isset($field['default'])?$field['default']:'') . "',\n";
    }
    $file .= ""
        . "        );\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Get the details for an existing {$object_def['name']}\n"
        . "    //\n"
        . "    else {\n"
        . "        \$strsql = \"SELECT {$object_def['table']}.id"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= ", \"\n"
            . "            . \"{$object_def['table']}.$field_id";
    }
    $file .= " \"\n"
        . "            . \"FROM {$object_def['table']} \"\n"
        . "            . \"WHERE {$object_def['table']}.station_id = '\" . qruqsp_core_dbQuote(\$q, \$args['station_id']) . \"' \"\n"
        . "            . \"AND {$object_def['table']}.id = '\" . qruqsp_core_dbQuote(\$q, \$args['{$object_id}']) . \"' \"\n"
        . "            . \"\";\n"
        . "        qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbHashQuery');\n"
        . "        \$rc = qruqsp_core_dbHashQuery(\$q, \$strsql, '{$package}.{$module}', '{$object_def['o_name']}');\n"
        . "        if( \$rc['stat'] != 'ok' ) {\n"
        . "            return array('stat'=>'fail', 'err'=>array('pkg'=>'{$package}', 'code'=>'" . $cur_code++ . "', 'msg'=>'{$object_def['name']} not found', 'err'=>\$rc['err']));\n"
        . "        }\n"
        . "        if( !isset(\$rc['{$object_def['o_name']}']) ) {\n"
        . "            return array('stat'=>'fail', 'err'=>array('pkg'=>'{$package}', 'code'=>'" . $cur_code++ . "', 'msg'=>'Unable to find {$object_def['name']}'));\n"
        . "        }\n"
        . "        \${$object_def['o_name']} = \$rc['{$object_def['o_name']}'];\n"
        . "    }\n"
        . "\n"
        . "    return array('stat'=>'ok', '{$object_def['o_name']}'=>\${$object_def['o_name']});\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'Get.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectHistory.php
//
function generate_history() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This method will return the list of actions that were applied to an element of an " . strtolower($object_def['name']) . ".\n"
        . "// This method is typically used by the UI to display a list of changes that have occured\n"
        . "// on an element through time. This information can be used to revert elements to a previous value.\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// station_id:         The ID of the station to get the details for.\n"
        . "// {$object_id}:          The ID of the " . strtolower($object_def['name']) . " to get the history for.\n"
        . "// field:                   The field to get the history for.\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}History(\$q) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = qruqsp_core_prepareArgs(\$q, 'no', array(\n"
        . "        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),\n"
        . "        '{$object_id}'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'{$object_def['name']}'),\n"
        . "        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'field'),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Check access to station_id as owner, or sys admin\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, '{$package}', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$q, \$args['station_id'], '{$package}.{$module}.{$object}History');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbGetModuleHistory');\n"
        . "    return qruqsp_core_dbGetModuleHistory(\$q, '{$package}.{$module}', '{$package}_{$module}_history', \$args['station_id'], '{$object_def['table']}', \$args['{$object_id}'], \$args['field']);\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'History.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectList.php
//
function generate_list() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// -----------\n"
        . "// This method will return the list of {$object_def['name']}s for a station.\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "// api_key:\n"
        . "// auth_token:\n"
        . "// station_id:        The ID of the station to get {$object_def['name']} for.\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}List(\$q) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = qruqsp_core_prepareArgs(\$q, 'no', array(\n"
        . "        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Check access to station_id as owner, or sys admin.\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, '{$package}', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$q, \$args['station_id'], '{$package}.{$module}.{$object}List');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Get the list of {$object_def['o_container']}\n"
        . "    //\n"
        . "    \$strsql = \"SELECT {$object_def['table']}.id"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= ", \"\n"
            . "        . \"{$object_def['table']}.$field_id";
    }
    $file .= " \"\n"
        . "        . \"FROM {$object_def['table']} \"\n"
        . "        . \"WHERE {$object_def['table']}.station_id = '\" . qruqsp_core_dbQuote(\$q, \$args['station_id']) . \"' \"\n"
        . "        . \"\";\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbHashQueryArrayTree');\n"
        . "    \$rc = qruqsp_core_dbHashQueryArrayTree(\$q, \$strsql, '{$package}.{$module}', array(\n"
        . "        array('container'=>'" . strtolower($object_def['o_container']) . "', 'fname'=>'id', \n"
        . "            'fields'=>array('id'"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= ", '$field_id'";
    }
    $file .= ""
        . ")),\n"
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    if( isset(\$rc['{$object_def['o_container']}']) ) {\n"
        . "        \${$object_def['o_container']} = \$rc['{$object_def['o_container']}'];\n"
        . "    } else {\n"
        . "        \${$object_def['o_container']} = array();\n"
        . "    }\n"
        . "\n"
        . "    return array('stat'=>'ok', '{$object_def['o_container']}'=>\${$object_def['o_container']});\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'List.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

//
// objectUpdate.php
//
function generate_update() {
    global $package;
    global $module;
    global $object;
    global $object_id;
    global $object_def;
    global $cur_code;

    $file = ""
        . "<?php\n"
        . "//\n"
        . "// Description\n"
        . "// ===========\n"
        . "//\n"
        . "// Arguments\n"
        . "// ---------\n"
        . "//\n"
        . "function {$package}_{$module}_{$object}Update(&\$q) {\n"
        . "    //\n"
        . "    // Find all the required and optional arguments\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'prepareArgs');\n"
        . "    \$rc = qruqsp_core_prepareArgs(\$q, 'no', array(\n"
        . "        'station_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Station'),\n"
        . "        '{$object_id}'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'{$object_def['name']}'),\n"
        . "";
    foreach($object_def['fields'] as $field_id => $field) {
        $file .= "        '$field_id'=>array('required'=>'no', 'blank'=>'" . (isset($field['default'])?'yes':'no') . "', 'name'=>'{$field['name']}'),\n";
    }
    $file .= ""
        . "        ));\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "    \$args = \$rc['args'];\n"
        . "\n"
        . "    //\n"
        . "    // Make sure this module is activated, and\n"
        . "    // check permission to run this function for this station\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, '{$package}', '{$module}', 'private', 'checkAccess');\n"
        . "    \$rc = {$package}_{$module}_checkAccess(\$q, \$args['station_id'], '{$package}.{$module}.{$object}Update');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n";
    if( isset($object_def['fields']['permalink']) && isset($object_def['fields']['name']) ) {
        $file .= ""
            . "    if( isset(\$args['name']) ) {\n"
            . "        qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'makePermalink');\n"
            . "        \$args['permalink'] = qruqsp_core_makePermalink(\$q, \$args['name']);\n"
            . "        //\n"
            . "        // Make sure the permalink is unique\n"
            . "        //\n"
            . "        \$strsql = \"SELECT id, name, permalink \"\n"
            . "            . \"FROM {$object_def['table']} \"\n"
            . "            . \"WHERE station_id = '\" . qruqsp_core_dbQuote(\$q, \$args['station_id']) . \"' \"\n"
            . "            . \"AND permalink = '\" . qruqsp_core_dbQuote(\$q, \$args['permalink']) . \"' \"\n"
            . "            . \"AND id <> '\" . qruqsp_core_dbQuote(\$q, \$args['{$object_id}']) . \"' \"\n"
            . "            . \"\";\n"
            . "        \$rc = qruqsp_core_dbHashQuery(\$q, \$strsql, '{$package}.{$module}', 'item');\n"
            . "        if( \$rc['stat'] != 'ok' ) {\n"
            . "            return \$rc;\n"
            . "        }\n"
            . "        if( \$rc['num_rows'] > 0 ) {\n"
            . "            return array('stat'=>'fail', 'err'=>array('pkg'=>'qruqsp', 'code'=>'" . $cur_code++ . "', 'msg'=>'You already have an " . strtolower($object_def['name']) . " with this name, please choose another.'));\n"
            . "        }\n"
            . "    }\n"
            . "\n";
    }
    if( isset($object_def['fields']['permalink']) && isset($object_def['fields']['title']) ) {
        $file .= ""
            . "    if( isset(\$args['title']) ) {\n"
            . "         qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'makePermalink');\n"
            . "        \$args['permalink'] = qruqsp_core_makePermalink(\$q, \$args['title']);\n"
            . "        //\n"
            . "        // Make sure the permalink is unique\n"
            . "        //\n"
            . "        $strsql = \"SELECT id, title, permalink \"\n"
            . "            . \"FROM {$object_def['table']} \"\n"
            . "             . \"WHERE station_id = '\" . qruqsp_core_dbQuote(\$q, \$args['station_id']) . \"' \"\n"
            . "             . \"AND permalink = '\" . qruqsp_core_dbQuote(\$q, \$args['permalink']) . \"' \"\n"
            . "            . \"AND id <> '\" . qruqsp_core_dbQuote(\$q, \$args['{$object_id}']) . \"' \"\n"
            . "             . \"\";\n"
            . "        \$rc = qruqsp_core_dbHashQuery(\$q, \$strsql, '{$package}.{$module}', 'item');\n"
            . "        if( \$rc['stat'] != 'ok' ) {\n"
            . "            return \$rc;\n"
            . "          }\n"
            . "        if( \$rc['num_rows'] > 0 ) {\n"
            . "             return array('stat'=>'fail', 'err'=>array('pkg'=>'qruqsp', 'code'=>'" . $cur_code++ . "', 'msg'=>'You already have an " . strtolower($object_def['name']) . " with this title, please choose another.'));\n"
            . "        }\n"
            . "    }\n"
            . "\n";
    }
    $file .= "    //\n"
        . "    // Start transaction\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionStart');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionRollback');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbTransactionCommit');\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'dbAddModuleHistory');\n"
        . "    \$rc = qruqsp_core_dbTransactionStart(\$q, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Update the {$object_def['name']} in the database\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'objectUpdate');\n"
        . "    \$rc = qruqsp_core_objectUpdate(\$q, \$args['station_id'], '{$package}.{$module}." . strtolower($object) . "', \$args['{$object_id}'], \$args, 0x04);\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        qruqsp_core_dbTransactionRollback(\$q, '{$package}.{$module}');\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Commit the transaction\n"
        . "    //\n"
        . "    \$rc = qruqsp_core_dbTransactionCommit(\$q, '{$package}.{$module}');\n"
        . "    if( \$rc['stat'] != 'ok' ) {\n"
        . "        return \$rc;\n"
        . "    }\n"
        . "\n"
        . "    //\n"
        . "    // Update the last_change date in the station modules\n"
        . "    // Ignore the result, as we don't want to stop user updates if this fails.\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, '{$package}', 'core', 'private', 'updateModuleChangeDate');\n"
        . "    qruqsp_core_updateModuleChangeDate(\$q, \$args['station_id'], '{$package}', '{$module}');\n"
        . "\n"
        . "    //\n"
        . "    // Update the web index if enabled\n"
        . "    //\n"
        . "    qruqsp_core_loadMethod(\$q, 'qruqsp', 'core', 'private', 'hookExec');\n"
        . "    qruqsp_core_hookExec(\$q, \$args['station_id'], 'qruqsp', 'web', 'indexObject', array('object'=>'{$package}.{$module}.{$object}', 'object_id'=>\$args['{$object_id}']));\n"
        . "\n"
        . "    return array('stat'=>'ok');\n"
        . "}\n"
        . "?>\n"
        . "";

    $filename = $object . 'Update.php';
    if( !file_exists($filename) ) {
        file_put_contents($filename, $file);
    }
}

?>
