<?php

/*
  Copyright (c) 2009, Roman Drapeko
  All rights reserved.

  Redistribution and use in source and binary forms, with or without modification, 
  are permitted provided that the following conditions are met:
  
  * Redistributions of source code must retain the above copyright notice, 
    this list of conditions and the following disclaimer.
  * Redistributions in binary form must reproduce the above copyright notice, 
    this list of conditions and the following disclaimer in the documentation and/or other 
    materials provided with the distribution.
  * Neither the name of the project nor the names of its contributors may be used to 
    endorse or promote products derived from this software without specific prior written permission.
  
  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. 
  IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, 
  INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
  BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
  LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE 
  OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
  OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/*
 * author: 			Roman Drapeko
 * e-mail:			roman.drapeko@gmail.com
 * homepage:		drapeko.com
 * copyright:		Copyright (c) 2009, Roman Drapeko
 * 
 * name:			generator
 * version:			1.2
 * 
 * description:		
 * 					Scans all files for a presence of classes and interfaces. 
 * 					The result is an array of association between classes/interfaces and their locations
 * 					
 * incoming parameters:
 * 1 	argc[1] - string - a source dir path (folder where to search for the interfaces and classes).
 * 		Default value - some string.
 * 2 	argc[2] - boolean - is the search recursive or not. Default value - false;
 * 3 	argc[3] - boolean/string - should the results be stored in the file. 
 * 		if false, outputs the result; if true, result is stored in the file, default destination is used.
 * 		if string, result is stored in this file. Default value - true;
 * 4	argc[4] - a name of the generated array
 */

  // ---------------- INITIALIZATION --------------------------
  define("DS", DIRECTORY_SEPARATOR);
  
  $argc = $_SERVER['argc'];
  // incoming parameters
  $argv = $_SERVER['argv'];
  
  // CHANGEABLE. Default location of the target folder (if you don't use first parameter).
  $v_target = dirname(__FILE__);
  
  // CHANGEABLE. Are results passed to the file? (if you don't use third parameter)
  $v_save_to_file = true;
  
  // CHANGEABLE. Default result file. (if you don't use third parameter)
  $v_save = $v_target.DIRECTORY_SEPARATOR."autoload_generated.php";

  // CHANGEABLE. Is search recursive? Default value. (if you don't use 2nd parameter)
  $v_recursive = true;
  
  // CHANGEABLE. Name of the generated array.
  $v_array_name = 'autoload_list';
  
  
  // ----------------------------------------------------------------------------------------------------
  
  // a pattern for the name of the class or interface
  $v_class_name = "[A-Za-z_][A-Za-z0-9_]*";

  // a pattern for class detection
  $v_class_pattern = 
  	"/".
    "[;}[:space:]]?class[[:space:]]*($v_class_name)".
    "([[:space:]]*extends[[:space:]]*($v_class_name))?".
    "([[:space:]]*implements[[:space:]]*($v_class_name)([[:space:]]*,[[:space:]]*($v_class_name))*)?".
    "[[:space:]]*{/i";
      
  // a pattern for interface detection
  $v_interface_pattern = 
  	"/".
    "[;}[:space:]]?interface[[:space:]]*($v_class_name)".
    "([[:space:]]*extends[[:space:]]*($v_class_name)([[:space:]]*,[[:space:]]*($v_class_name))*)?".
    "[[:space:]]*{/i";
  
  // main function
  function process($directory, $v_recursive = true, &$result_array = array("classes" => array(), "interfaces" => array())) {
    global $v_class_pattern, $v_interface_pattern;
    
    if(substr($directory,-1) == '/')
      $directory = substr($directory, 0, -1);
        
      if(!file_exists($directory) || !is_dir($directory) || !is_readable($directory)) {
        return;
        
      // if everything is ok
      } else {
        $directory_list = opendir($directory);
        
        while ($file = readdir($directory_list)) {
          
          // filtering by filename
          if(in_array($file, array('.', '..')) || substr($file, 0, 1) == '.')
            continue;
          
          $path = $directory.DS.$file;
          
          if(is_readable($path)) {
            if (is_dir($path) && $v_recursive) {
              process($path, $v_recursive, $result_array);
            } elseif (is_file($path)) {
              
              // filtering by extension
              $extension = end(explode(".", $path));
              if ($extension != 'php') {
                continue;
              } else {
                
                // delete all comments and whitespaces
                $content = php_strip_whitespace($path);
                // erase all strings
                $content = preg_replace('/"([^"].)*"/', '""', $content);
                $content = preg_replace("/'([^'].)*'/", "''", $content);
                
                // class search
                preg_match_all($v_class_pattern, $content, $matches, PREG_SET_ORDER);
                
                foreach($matches as $match) {
                  $tmp_array = array();
                  $tmp_array["path"] = $path;
                  $tmp_array["extends"] = array();
                  $tmp_array["implements"] = array();
                  if (array_key_exists(3, $match) && $match[3] != "") {
                    $tmp_array["extends"][] = $match[3];
                  }
                  if (array_key_exists(4, $match) && $match[4] != "") {
                    $interfaces = split(",", trim(preg_replace("/implements/i", "", $match[4])));
                    $interfaces = array_map("trim", $interfaces);
                    $tmp_array["implements"] = $interfaces;
                  }
                  $result_array["classes"][$match[1]] = $tmp_array;
                }
                
                // interface search
                preg_match_all($v_interface_pattern, $content, $matches, PREG_SET_ORDER);
                
                foreach($matches as $match) {
                  $tmp_array = array();
                  $tmp_array["path"] = $path;
                  $tmp_array["extends"] = array();
                  if (array_key_exists(2, $match) && $match[2] != "") {
                    $interfaces = split(",", trim(preg_replace("/extends/i", "", $match[2])));
                    $interfaces = array_map("trim", $interfaces);
                    $tmp_array["extends"] = $interfaces;
                  }
                  $result_array["interfaces"][$match[1]] = $tmp_array;
                }
              }
            }
          }
        }
        
        closedir($directory_list);
      }
      return $result_array;
  }

  // ------------------------ INCOMING --------------------------
  // number of incoming parameters

  if ($argc >= 2) {
    $v_target = $argv[1];

    if ($argc >= 3) {
      $v_recursive = trim(strtolower($argv[2])) == 'false' ? false : true;
      
      if ($argc >= 4) {
        if (in_array(trim(strtolower($argv[3])), array('true', 'false'))) {
          $v_save_to_file = trim(strtolower($argv[3])) == 'false' ? false : true;
        } else {
          $v_save_to_file = true;
          $v_save = $argv[3];
        }

        if ($argc >= 5) {
          $v_array_name = $argv[4];
        }
      }
    }
  }
 
  // ------------------------ MAIN --------------------------
  // create an array of associations between classes and files
  $class_array = process($v_target, $v_recursive);
  $php_page = 
    "<?php \n\n".
    "\$$v_array_name = ".
    stripslashes(var_export($class_array, true)).";".
    "\n?>\n";

  // --------------------- WRITING TO FILE/ECHO --------------------------
  // writing to a file
  if ($v_save_to_file) {
    
    echo "\n";
    echo "parameters number: $argc\n";
    echo "\n";
    
    echo "target path: ".$v_target."\n";
    echo "recursive: ". ($v_recursive == true? "true" : "false")."\n";
    echo "output: ";
    if ($v_save_to_file) {
      echo "file \n";
      echo "result file: $v_save\n";
    } else {
      echo "echo \n";
    }
    
    $fh = fopen($v_save, 'w') or die("can't open file");
    fwrite($fh, $php_page);
    fclose($fh);
  } else {
    echo $php_page;
  }


?>