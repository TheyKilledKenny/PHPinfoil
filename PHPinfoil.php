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
* 
* ## Setup a Raspberry Pi and a USB HDD (with your backups) as personal shop:
* 1. Follow this link to setup a nginx webserver and php: https://www.raspberrypi.org/documentation/remote-access/web-server/nginx.md
* 2. Connect the usb hdd, open a terminal on your raspberry pi
* 3. type ```sudo fdisk -l``` and find /dev/sdx that match your usb drive
* 4. type ```sudo ls -l /dev/disk/by-uuid/``` and find UUID related to your /dev/sdx usb drive (Needed in step 6)
* 5. type ```sudo mkdir /var/www/html/php``` and copy PHPinfoil.php renamed as index.php in the folder /var/www/html/php
* 6. type ```sudo mkdir /var/www/html/cache``` and ```sudo chmod 777 /var/www/html/cache```
* 7. type ```sudo mkdir /var/www/html/games``` to create a folder where to mount the usb drive
* 8. type `sudo nano /etc/fstab` and add this line at the end
*     `UUID=uuid_Found_In_Step2 /var/www/html/games auto uid=pi,gid=pi 0 0`
* 9. Reboot
* 10. enter the address http://rpi.address.ip/php/ in Tinfoil as a new location or open in a web browser to check the resulting json.
* 
* To check for errors causes, call the page using a standard browser, right click and select Show page source
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
$Folder = "data/games";									//Where the games file are stored, can contain subfolders
$cacheFolder = "cache/";							//the cache Folder where to store the cache file. MUST Have write permission
$cacheFile = "cache.json";							//the cached file. MUST Have write permission
$arrExtensions = ['nsp','xci','nsz', 'xcz'];		//which extensions to list 


/****************************************
*		Headers for Json File           *
****************************************/

	header("Content-Type: application/json");
	header('Content-Disposition: filename="main.json"');


/****************************************
*				MAIN		            *
****************************************/

//If Cached file exits then send it
if(file_exists($rootFolder.$cacheFolder.$cacheFile) && !isset($_GET['reset'])){
	readfile($rootFolder.$cacheFolder.$cacheFile);

//Else recalculate, save and send cache file
}else{
	$aar = recursiveDirectoryIterator($Folder, $Host);
	$aarr['total'] = count($aar);
	$aarr['files'] = $aar;
	if(trim($MOTD) != "") $aarr['success'] = $MOTD;

	//check if you have write access to the chache folder
	//If not write access then stream out the json as is
	if (!is_dir($rootFolder.$cacheFolder) or !is_writable($rootFolder.$cacheFolder)) {
		echo json_encode($aarr);
	} else {
		//Save file and stream it out
		file_put_contents($rootFolder.$cacheFolder.$cacheFile ,json_encode($aarr));
		readfile($rootFolder.$cacheFolder.$cacheFile);
	}
}





/**
* @param string $directory
* @param array $files
* @return array
*/
function recursiveDirectoryIterator ($directory = null, $host, $files = array()) {
    
	global $rootFolder;
	global $arrExtensions;
	
	$iterator = new \DirectoryIterator ( $rootFolder.$directory );

    foreach ( $iterator as $info ) {
        if ($info->isFile ()) {
			
			if( in_array($info->getExtension(),$arrExtensions) ){

				if ($info->getSize() > 0 ) {
					$files [] = [
						'url'=> $host . str_replace($rootFolder, "", $info->getPathname()).'#'.urlencode(str_replace('#','',$info->getFilename())),
						'size'=>$info->getSize()
						];
				} else {
					$files [] = $host . str_replace($rootFolder, "", $info->getPathname()).'#'.urlencode(str_replace('#','',$info->getFilename()));
				}
			}

        } elseif (!$info->isDot ()) {
			/*
			$files ['directories'][] = $host . $info->getPathname() ."/";
			//*/
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