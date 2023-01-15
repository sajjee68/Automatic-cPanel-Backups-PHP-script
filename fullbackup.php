<?php
/*
PHP script to allow periodic cPanel backups automatically, optionally to a remote FTP server.
This script contains passwords. It is important to keep access to this file secure (we would ask you to place it in your 
home directory, not public_html)
To schedule the script to run regularly, add this script to run as a cron job in your cpanel.
You need create 'backups' folder in your home directory ( or any other folder that you would like to store your backups in ).
Reference: https://www.namecheap.com/support/knowledgebase/article.aspx/915
*/

// ********* THE FOLLOWING ITEMS NEED TO BE CONFIGURED *********
// Information required for cPanel access
cron_log( 'full-backup', 'start' );
$cpuser      = "*****"; // Username used to login to cPanel
$cppass      = '*****'; // Password used to login to cPanel. NB! you could face some issues with the "$#&/" chars in the password, so if script does not work, please try to change the password.
$domain      = "*****";// Your main domain name
$notifyemail = '*****';

$skin   = "jupiter"; // Set to cPanel skin you use (script will not work if it does not match). Most people run the default "x" theme or "x3" theme
$cpsess = "*****";

// Information required for FTP host
$ftpuser = "*****"; // Username for FTP account
$ftppass = '*****'; // Password for FTP account NB! you could face some issues with the "$#&/" chars in the password, so if script does not work, please try to change the password.
$ftphost = "*****"; // IP address of your hosting account
$ftpmode = "ftp"; // FTP mode: homedir - Home Directory, ftp - Remote FTP Server, passiveftp - Remote FTP Server (passive mode transfer), scp - Secure Copy (SCP)

// Notification information $notifyemail = "any@example.com"; // Email address to send results
// Secure or non-secure mode 
$secure = 1; // Set to 1 for SSL (requires SSL support), otherwise will use standard HTTP
// Set to 1 to have web page result appear in your cron log $debug = 0;

$ftpport = "21";
$ftpdir = "/daily/"; // Directory where backups stored (make it in your /home/ directory). Or you can change 'backups' to the name of any other folder created for the backups;
if ( date('Y-m-d') == date('Y-m-01')) {
	$ftpdir = "/monthly/"; // Directory where backups stored (make it in your /home/ directory). Or you can change 'backups' to the name of any other folder created for the backups;
}elseif (date('w') == 6) {
	$ftpdir = "/weekly/"; // Directory where backups stored (make it in your /home/ directory). Or you can change 'backups' to the name of any other folder created for the backups;
}

// *********** NO CONFIGURATION ITEMS BELOW THIS LINE *********

if ( $secure ) {
	$url  = "ssl://" . $domain;
	$port = 2083;
} else {
	$url  = $domain;
	$port = 2082;
}

$socket = fsockopen( $url, $port );
if ( ! $socket ) {
	cron_log( 'full-backup', "Failed to open socket connection... Bailing out!n" );
	exit;
}

// Encode authentication string
$authstr = $cpuser . ":" . $cppass;
$pass    = base64_encode( $authstr );
$params  = "dest=$ftpmode&email=$notifyemail&server=$ftphost&user=$ftpuser&pass=$ftppass&port=$ftpport&rdir=$ftpdir&submit=Generate Backup";

// Make POST to cPanel

fputs( $socket, "POST /" . $cpsess . "/frontend/" . $skin . "/backup/dofullbackup.html?" . $params . " HTTP/1.0\r\n" );
fputs( $socket, "Host: $domain\r\n" );
fputs( $socket, "Authorization: Basic $pass\r\n" );
fputs( $socket, "Connection: Close\r\n" );
fputs( $socket, "\r\n" );

// Grab response even if we do not do anything with it.

while ( ! feof( $socket ) ) {
	$response = fgets( $socket, 4096 );
}

cron_log( 'full-backup', $response );
fclose( $socket );

function cron_log( $file_name, ...$params ) {
	$log = '';

	foreach ( $params as $message ) {

		$log .= date( '[Y-m-d H:i:s] ' );

		if ( is_array( $message ) || is_object( $message ) ) {
			$log .= print_r( $message, true );
		} elseif ( is_bool( $message ) ) {
			$log .= ( $message ? 'true' : 'false' );
		} else {
			$log .= $message;
		}

		$log .= PHP_EOL;
	}

	file_put_contents( __DIR__ . "/" . $file_name . ".log", $log, FILE_APPEND );
}

?>
