#!/usr/bin/php
<?php
/**
 * This script is designed to be used as a PHP Lint (syntax) check in a precommit hook for Mercurial, it will
 * go through all files trying to be committed and anything with a valid PHP extension (php, phtml, php3, php4, php5)
 * will be given to PHP to lint check.
 *
 * To get this script working, please put the following in your project's hgrc file, or in your user's hgrc
 * and place this script somewhere relative to your home directory and ensure the path below is updated to match.
 * In my environment, I placed this script in my home folder, in the /bin directory.  I chose a user-specific path
 * to help save you from having to have this piece of code in many places for many different repositories.
 *
 * So, in your .hg/hgrc file for your project, or your user file at ~/.hgrc add...
 *
 *     [hooks]
 *     pretxncommit.php_lint_check = ~/bin/php-lint-check-pretxncommit.php
 *
 * If you have any questions, ask me!
 *
 * @author  Farley
 * @see     https://github.com/AndrewFarley
 */

/**
 * Config options
 */
// If you don't want to see the output of this lint checker while you're committing, set verbose = FALSE
$verbose = TRUE;
// Please specify the path to mercurial here
$hgpath  = '/usr/local/bin/hg';
// Check files with these extensions only
$php_extensions = array('php','phtml','php3','php4','php5');

/**
 * Main PHP/HG Lint Check Logic Below
 */
$fail = FALSE;
verbose("Beginning PHP Lint Check");

// Getting the revision we're committing (the tip)
exec($hgpath.' tip', $output);
$temp = explode(':', $output[0]);
$revision = intval(trim($temp[1]));
verbose("  on revision: $revision");

// Getting files changed
$output = array();
verbose("Getting files changed or added...");
exec("hg status --change $revision --added --modified", $output);
verbose("  found ".count($output)." files changed or added: \n----------\n".implode("\n", $output)."\n----------");

// Build our array of files we need to parse
$files = array();
foreach ($output as $file) {
    if (strlen($file) > 2) {
        $file = substr($file, 2);
        if (is_file($file)) {
            $parts = pathinfo($file);
            if (!isset($parts['extension']) || !in_array($parts['extension'], $php_extensions)) {
                verbose("Not a known php extension for file: '$file'");
                continue;
            }
                
            verbose("Checking PHP Syntax for: '$file'...");
            $output = array();
            exec('/usr/bin/php -l '.escapeshellarg($file), $output, $retval);
            if ($retval === 0) {
                verbose("  Successfully passed PHP Lint Check!");
            } else {
                echo "PHP Parsing Error Detected in file '$file'\n----------";
                echo implode("\n", $output)."\n----------\n";
                $fail = TRUE;
            }
        }
    }
}

// If any file failed, return failure so it doesn't commit
if ($fail)
    exit(255);
exit(0);

/**
 * Verbose output if enabled
 */
function verbose($message) {
    if ($GLOBALS['verbose'])
        echo $message."\n";
}