**************************

Real Time OFP server query

**************************

------------------------------------------------------------------------------------------------------
Minor inprovements :
- see: ofpr.php (thx Mivo)
- Shows real name of Island (ie: Nogova insteadt of Noe etc.)
- example-file especially for PHP-nuke
------------------------------------------------------------------------------------------------------
New version of [WKK] RealTime Server Query (RTSQ) for OFP

06 Aug 2002 - Changes by Mivo (mivo@post.cz) [CzSk1985] OFP Squad (http://ofp.stopiv.cz)

Major improvements :

- compatible with Operation Flashpoint:Resistance and new netcode versions (1.60 and above ?) servers 
  in Sockets and DirectPlay network mode
- corrected problem in player table if mixed players with and without squad name
- support for TIMELEFT variable (OFP 1.42 and above)

------------------------------------------------------------------------------------------------------
Thanks to :

Maker of original RTSQ - [WKK] LordNam (lordnam@wkk.dk) http://www.wkk.dk
------------------------------------------------------------------------------------------------------

Requirements: A server running PHP version 4.x is required, Linux OR Windows.

******************************************************************************************************
Remember to search for the $system VARIABLE in top of the file "ofpr.php" and set it
for whatever system you run.

$system=0; // for Linux (default)
$system=1; // for Windows

This is important, cause Windows does NOT support timeout functions on sockets in PHP
This means, if an illegal address is entered, when running @ windows - Apache crashes.
So ONLY do queries, against servers you KNOW is up, when running @ windows.

If running @ Linux - this problem is NOT present.

Thats just the way PHP 4.x is ported to windows - sorry folks.
******************************************************************************************************

The source consists of the following files:
------------------------------------------------------------------------------------------------------

"ofpr.php" (class/library for querying OFP servers)
"serverqueries.php" (example-file showing how to query, display results on a webpage).
"serverqueries_nuke.php" (example-file showing how to query, made especially for PHP-nuke)
"pixel1.gif" (1 pixel gif - black, for FANCY serverload graphics).
"pixel2.gif" (1 pixel gif - transparent, for FANCY serverload graphics).

"OFP_RTSQoriginal.zip" - original version of [WKK] RealTime Server Query (RTSQ) for OFP