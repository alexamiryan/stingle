<?
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
 * name:			extended generator
 * version:			1.1
 * dependencies:	uses autoload generator (generator.php)
 *
 * description:
 * 					additional functionality to autoload generator
 */

  // ---------------- INITIALIZATION --------------------------
  define("DS", DIRECTORY_SEPARATOR);

  // CHANGEABLE. The list of directories to be processed.
  $v_dirs = array (
    // array('location', 'is_recursive')
    array(
    	'path' => './stingle/core',
    	'recursive' => true
    )
  );
  
  // CHANGEABLE. The result file.
  $v_save = './stingle/configs/config.classmap.inc.php';
  
  // CHANGEABLE. The name of the generated array;
  $v_array_name = "stingle_autoloadList";
  
  
  // path to the auto_generator.php file
  $vs_auto_generator = './stingle/scripts/autoload_generator.php';

  // -----------------------------------------------------------------------------------------------
  $arr = array('classes' => array(), 'interfaces' => array());
  foreach ($v_dirs as $dir) {
    
    unset($output);
    $execOutput = exec(
    	"php $vs_auto_generator"
        ." \"{$dir['path']}\""                       // 1st arg (target location)
        .($dir['recursive'] ? " true" : " false")    // 2nd arg (recursive)
        ." false"									 // 3rd arg (save destination)s
        ." temp_array"
        , $output
    );
    
    $str_output = implode("\n", $output);
    eval("?> ".$str_output. "<?php ");
    
    $arr['classes'] = array_merge($arr['classes'], $temp_array['classes']);
    $arr['interfaces'] = array_merge($arr['interfaces'], $temp_array['interfaces']);
    
    echo "\nScanned '{$dir['path']}'\n";
  }

  $$v_array_name = $arr;

  $php_page =
    "<?php \n\n".
    "\$$v_array_name = ".
    stripslashes(var_export($$v_array_name, true)).";".
    "\n?>\n";
  
  $fh = fopen($v_save, 'w') or die("can't open file");
  fwrite($fh, $php_page);
  fclose($fh);
  echo "Saved to '$v_save' with array name $v_array_name\n";

?>