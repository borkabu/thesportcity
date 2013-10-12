<?php
// 
// ============================================================================
// files.class.php                                        revision: 20010611-01
// ----------------------------------------------------------------------------
// Class can return files/dirs in specified folder, rename files, delete files.
// 
// GLOBAL VARIABLES USED:
//            $conf_home_dir;
// 
// STATUS:    usable
// 
// TASK LIST: - more file handling methods (copy, info about file, etc)
//            - error handling
//            - javadoc style documentation of classes and methods
//            ( - not started yet, * in progress, + done, ? uner question )
// 
// BUGS:      -
// 
// ----------------------------------------------------------------------------
// Authors:   Martynas Majeris <martynas@xxl101.lt>
// ============================================================================
// 

/**
 * files class
 */

// Constants
define("FL_FORCE", true);

class files { 
  // {{{ properties
  
	// current variables
	var $curdir=false;
	var $dirinfo;
	
	// error variables
	var $fl_error;
	var $fl_errno;
  // }}}
	// User functions ===============================================
  // {{{ set()
  
  /**
  ** Sets current folder and populates $dirinfo with its contents
  **
  ** @param $dir full or relative path
  ** @param $reset if TRUE, method will force rescan of the directory
  ** @access public
  ** @return bool always TRUE
  ** @author martynas / martynas@xxl101.lt / 2001.03.18
  **/	
	function set ($dir, $reset=false) {
		$dir = $this->valdir($dir);
		$this->curdir = $dir;
		if (!$this->dirinfo[$dir] || $reset) {
			clearstatcache();
			$d = opendir ($dir);
			$cd = 0;
			$cf = 0;
			unset ($this->dirinfo[$dir]);
			while ($file = readdir($d)) {
				if ($file != "." && $file != "..") {
					if (is_dir($file)) {
						$type = "dirs";
						$this->dirinfo[$dir][$type][$cd]["name"] = $file;
						$cd++;
					}
					else {
						$type = "files";
						$size = filesize("$dir/$file");
						$time = filemtime("$dir/$file");
						$this->dirinfo[$dir][$type][$cf]["name"] = $file;
						$this->dirinfo[$dir][$type][$cf]["size"] = $size;
						$this->dirinfo[$dir][$type][$cf]["time"] = $time;
						$cf++;
					}
				}
			}
			closedir($d);
		}
	}
	// }}}
	// Returns a list of subfolders in specified folder
	// If folder is not specified, current one is used
	
	function getdirs($dir=false) {
		if (!$dir) $dir = $this->curdir;
		$dir = $this->valdir($dir);
		return $this->$dirinfo[$dir]["dirs"];
	}
	
	// Return a list of files in specified folder
	// If folder is not specified, current one is used
	
	function getfiles($dir=false) {
		if (!$dir) $dir = $this->curdir;
		$dir = $this->valdir($dir);
		$val = $this->dirinfo[$dir]["files"];
		return $val;
	}
	
	// Renames the file
	
	function rename($oldfile=false, $newfile=false, $dir=false) {
		clearstatcache();
		if (!$dir) $dir = $this->curdir;
		$dir = $this->valdir($dir);
		if (!file_exists("$dir/$oldfile")) {
			$this->fl_error = "file [$dir/$oldfile] does not exist";
			$this->fl_errno = 601;
			return false;
		}
		elseif (file_exists("$dir/$newfile")) {
			$this->fl_error = "target file [$dir/$oldfile] already exists";
			$this->fl_errno = 602;
			return false;
		}
		else {
			rename("$dir/$oldfile", "$dir/$newfile");
			$this->set($dir, FL_FORCE);
			return true;
		}
	}
	
	// Renames the file but forces the same extention
	
	function frename($oldfile=false, $newfile=false, $dir=false) {
//		$ereg = '\.([a-zA-Z0-9]*)$';
//		ereg($ereg, $oldfile, $arr);
                preg_match('/\.([a-zA-Z0-9]*)$/', $oldfile, $arr);
		if (ereg('/\.([a-zA-Z0-9]*)$/', $newfile)) 
		  $newfile = preg_replace('/\.([a-zA-Z0-9]*)$/', "." . $arr[1], $newfile);
		else $newfile .= "." . $arr[1];
		return $this->rename($oldfile, $newfile, $dir);
	}
	
	// Deletes file or files
	
	function delete($oldfile=false, $dir=false) {
		global $conf_home_dir;
		clearstatcache();
		if (!$dir) $dir = $this->curdir;
		$dir = $this->valdir($dir);
		if (is_array($oldfile)) {
			// delete multiple files
			for ($c=0; $c < sizeof($oldfile); $c++) {
				if (file_exists("$dir/".$oldfile[$c])) unlink("$dir/".$oldfile[$c]);
			}
		}
		else {
			// delete single file
			if (!file_exists($conf_home_dir."$dir/$oldfile")) {
echo "$dir/$oldfile";
				$this->fl_error = "file [$dir/$oldfile] does not exist";
				$this->fl_errno = 601;
				return false;
			}
			else {
				unlink($conf_home_dir."$dir/$oldfile");
			}
		}
		$this->set($dir, FL_FORCE);
		return true;
	}
	
	// Handles file uploads
	// $filter - content type, i.e. "image", "image/gif", "script/php", etc.
	// files will be moved to $dir
	
	function uploads ($filter=false, $dir=false) {
		if (!$dir) $dir = $this->curdir;
		$dir = $this->valdir($dir);
//		global $HTTP_POST_FILES;
		$return = array();
		$ereg = '\.([a-zA-Z0-9]*)$';

		if (is_array($_FILES)) {
			while (list($key, $val) = each($_FILES)) {
				$type = $_FILES[$key]['type'];
				$name = $_FILES[$key]['name'];
				$size = $_FILES[$key]['size'];
				$tmp_name = $_FILES[$key]['tmp_name'];
echo $type.$size.$filter;
print_r($_FILES);
				if ($size > 0) {
					if (!$filter || ($filter && eregi("^$filter", $type))) {
						$c=1;
						$orig_name = $name;
						while (file_exists("$dir/$name")) {
							eregi ($ereg, $orig_name, $arr);
							$name = eregi_replace ($ereg, "$c.".$arr[1], $orig_name);
							$c++;
						}
						if(!copy($tmp_name,"$dir/$name"))print ("failed to copy $tmp_name to $dir/$name...<br>\n");
						//if(!move_uploaded_file($tmp_name,"$dir/$name"))print ("failed to move $tmp_name to $dir/$name...<br>\n");
						$return[] = $name;
					}
				}
			}
			$this->set($dir, FL_FORCE);
			return $return;
		}
		return false;
	}
	
	function valdir($dir) {
		if (preg_match("/(\\$)/",$dir)) 
		  return preg_replace("/(\\$)/","",$dir);
		elseif (preg_match("/(\/$)/",$dir)) 
		   return preg_replace("/(\/$)/","",$dir);
		else return $dir;
	}
	
}
?>
