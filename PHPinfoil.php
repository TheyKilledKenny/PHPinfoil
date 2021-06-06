<?php
/**
* # PHPinfoil
* Simple PHP, no database, to serve your backups and eshop games to Tinfoil.
* 
* - Cycles through all the files and folders in the given path and creates the json file to be provided to Tinfoil
* - Creates the json index cache, so in case the collection is very large, it is not necessary to rescan all the files. Write permissions to a folder are required.
*   The first time it creates a cache file, the following times if the cache file exists this is provided, otherwise a new file is created
* - Call the PHPinfoil.php with ?reset to delete and recreate the cache file
* - No Database Needed
*   
* ### How To Use:
* * - Put all your games in a folder under the path served by your php webserver, they can be divided into subfolders
* * - Copy the PHPinfoil.php file to a folder (also the same folder of games) on the same http server, reachable by http clients
* * - Check that all your games have [TitleID] in the filename or Tinfoil won't show it in the list
* * - configure the parameters accordingly, see following Example
* 
* 
* ### Example (change the assumptions below to match your case):
*   * Assuming your http server is serving at http://1.2.3.4:8080/
*   * Assuming the games are in /data/games folder of your http server<br>
*      so they can be downloaded from http://1.2.3.4:8080/data/games/file[0100AD8015550000].nsp<br>
*      or subfolders like http://1.2.3.4:8080/data/games/mybackup/file[0100AD8015550000].nsp
*   * Assuming you renamed PHPinfoil.php in index.php and copied in /php folder so can be reached at http://1.2.3.4:8080/php/index.php
*   * Assuming you have write permission on the /cache folder
*   * Assuming you want to list nsp, xci, nsz and xcz file types
* 
* Set parameters with this values:
* ```  $Host = "http://1.2.3.4:8080/";
*   $rootFolder = $_SERVER['DOCUMENT_ROOT'] . "/";
*   $Folder = "data/games";
*   $cacheFolder = "cache/";
*   $cacheFile = "mycache.json";
*   $arrExtensions = ['nsp','xci','nsz','xcz'];
* ```
* in Tinfoil set a location to http://1.2.3.4:8080/php/
* 
* If not using Tinfoil then set 
* $DBI = true;
*
*
* @title	PHPinfoil
* @author	TheyKilledKenny
* @date     29 May 2021
* 
**/



/****************************************
*		Configuration Parameters        *
****************************************/
$MOTD = "Wellcome to my personal NX Shop";			//Message that appears each time Tinfoil start and receive the file for the first time

$Host = "http://this.is.myip/";						//Base http name, if the games are in http://1.2.3.4:8080/games/mygames.nsp
													// then put here http://1.2.3.4:8080/
$rootFolder = $_SERVER['DOCUMENT_ROOT'] . "/";		//The base path to use to browse files and create cache, at the moment must be a folder reachable by http client
$Folder = "data/games";								//Where the games file are stored, can contain subfolders
$cacheFolder = "cache/";							//the cache Folder where to store the cache file. MUST Have write permission
$cacheFile = "cache";								//the cached file. MUST Have write permission
$arrExtensions = ['nsp','xci','nsz', 'xcz'];		//which extensions to list 
$DBI = true;										//if true then output in html to be compatible with DBI installer
$BackColor = "#000000";								//Page Background Color
$ForeColor = "#cccccc";								//Text Color
$AltRowColor = "#222222";							//File list alternate row color

/****************************************
*		Headers for Json File           *
****************************************/
if(!$DBI) {
	header("Content-Type: application/json");
	header('Content-Disposition: filename="main.json"');
}

/****************************************
*				MAIN		            *
****************************************/

//If Cached file exits then send it
if(file_exists($rootFolder.$cacheFolder.$cacheFile) && !isset($_GET['reset'])){
	readfile($rootFolder.$cacheFolder.$cacheFile);

//Else recalculate, save and send cache file
}else{
	set_time_limit(0);			//Try to avoid Timeout for big collection of files
	$aar = recursiveDirectoryIterator($Folder, $Host);
	$aarr['total'] = count($aar);
	asort($aar);				//Try to Sort
	$aarr['files'] = $aar;
	if(trim($MOTD) != "") $aarr['success'] = $MOTD;

	//check if you have write access to the chache folder
	//If not write access then stream out the json as is
	if (!is_dir($rootFolder.$cacheFolder) or !is_writable($rootFolder.$cacheFolder)) {
		if ($DBI) {
			echo createHtml($aar);
		} else {
			echo json_encode($aarr);
		}
		
	} else {
		//Save file and stream it out
		if ($DBI) {
			file_put_contents($rootFolder.$cacheFolder.$cacheFile ,createHtml($aar));
		} else {
			file_put_contents($rootFolder.$cacheFolder.$cacheFile ,json_encode($aarr));
		}
		
		readfile($rootFolder.$cacheFolder.$cacheFile);
	}
}

/**
* @param array $arr		The compiled array results from directory scan
* @return string		The full Html page 
*/
function createHtml($arr = null) {
	
	global $rootFolder;
	global $MOTD;
	global $BackColor;
	global $ForeColor;
	global $AltRowColor;
	
	$htmlPage = "<html>
<head>
	<style type=\"text/css\">

		/* General styles */
		BODY {
			background-color:".$BackColor.";
			color:".$ForeColor.";
			font-family:sans-serif;
		}
		
		A {
			color: ".$ForeColor.";
			text-decoration: none;
		}

		A:hover {
			text-decoration: underline;
		}
		
		table {
			width:90%;
			margin: auto;
			font-size:small;
		}
		
		tr:nth-child(even) {
            background-color: ".$AltRowColor.";
        }
	</style>
	<title>$MOTD</title>
</head>
<body>
	<center><h1>Files Found: ".count($arr)."</h1></center>
	<table>
		<tr><td>&nbsp;</td><td><b>FILES</b></td><td><b>SIZE</b></td><tr>
	";
	
	//Here get the files and create rows
	foreach ( $arr as $file ) {
		
       $htmlPage = $htmlPage . '
	   <tr>
		<td> - </td>
		<td><a href="' . $file['url'] . '">' . $file['name'] . '</a><br /></td>
		<td style="text-align:right;"> '.sprintf("%0.2f", (($file['size'] /1024) / 1024)).' MB </td>
	   </tr>
		';	// info->isFile ()) {
	}

	$htmlPage = $htmlPage . '
	</table>
</body></html>';

		return $htmlPage;
}


/**
* @param string $directory
* @param array $files
* @return array
*/
function recursiveDirectoryIterator ($directory = null, $host, $files = array()) {
    
	global $rootFolder;
	global $arrExtensions;
	global $DBI;
	
	$iterator = new \DirectoryIterator ( $rootFolder.$directory );

    foreach ( $iterator as $info ) {
        if ($info->isFile ()) {
			
			if( in_array($info->getExtension(),$arrExtensions) ){

				$url = $host . str_replace($rootFolder, "", $info->getPathname());
				
				//Try to get a correct file size
				$fsize = $info->getSize();

				//very slow but the only way that works on all platforms.
				if($fsize < 0 ) {
					$fsize = array_change_key_case(get_headers($url, 1),CASE_LOWER);
					if ( strcasecmp($fsize[0], 'HTTP/1.1 200 OK') != 0 ) {
						$fsize = $fsize['content-length'][1]; }
					else {
						$fsize = $fsize['content-length'];
					}
				}
				
				if ($DBI) {
					
					$files [] = [
						'url'=> $host . str_replace($rootFolder, "", $info->getPathname()),
						'name'=> $info->getFilename(),
						'size'=> $fsize
					];
					
				} else {	//if ($info->getSize() > 0 ) {	//Removed the check on filesize, it should be ok now.
					
					$files [] = [
						'url'=> $host . str_replace($rootFolder, "", $info->getPathname()).'#'.urlencode(str_replace('#','',$info->getFilename())),
						'size'=>$info->getSize()
					];
					
				//} else {
				//	$files [] = $host . str_replace($rootFolder, "", $info->getPathname()).'#'.urlencode(str_replace('#','',$info->getFilename()));
				}
			}

        } elseif (!$info->isDot ()) {

            $list = recursiveDirectoryIterator(
                        $directory.DIRECTORY_SEPARATOR.$info->__toString (), $host
            );
            if(!empty($files))
                $files = array_merge_recursive($files, $list);
            else {
                $files = $list;
            }
        }
    }
    return $files;
}

?>
