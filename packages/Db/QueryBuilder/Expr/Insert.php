<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Expression class for building SQL insert statements
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @version $Revision$
 * @author  Alex Amiryan <alex@amiryan.org>
 * @author  Aram Gevorgyan <aram@web-emedianet.com>
 */
class Insert
{
    const TYPE_NORMAL = 0;
    const TYPE_LOW_PRIORITY = 1;
    const TYPE_DELAYED = 2;
    const TYPE_HIGH_PRIORITY = 3;
    const TYPE_IGNORE = 4;
    
    private $tableName;
    private $values = array();
    private $fields = null;
    private $type;
    
    public function __construct($tableName, $type = TYPE_NORMAL){
    	$this->tableName = $tableName;
    	$this->type = $type;
    }

    public function addValues($values){
    	array_push($this->values, $values);
    }
    
    public function setFields($fields){
    	$this->fields = $fields;
    }
    
    public function __tostring(){
    	$returnStr = $this->_getType();
    	
    	$returnStr .= "INTO `{$this->tableName}` ";
    	if($this->fields !== null and is_array($this->fields)){
    		$returnStr .= "(`" . implode("`,`", $this->fields) . "`) ";
    	}
    	
    	if(isset($this->values[0][0]) and $this->values[0][0] instanceof QueryBuilder and $this->values[0][0]->getType() == QueryBuilder::SELECT){
    		$returnStr .= "({$this->values[0][0]})";
    	}
    	else{
    		if(($this->fields !== null and is_array($this->fields)) or (isset($this->values[0]) and !isAssoc($this->values[0]))){
    			$returnStr .= "VALUES ";
    			foreach($this->values as $values){
	    			$returnStr .= "(" . $this->_getValues($values) . "),";
    			}
    			
    			$returnStr = trim($returnStr, ",");
    		}
    		else{
    			$returnStr .= "(`" . implode("`,`", array_keys($this->values[0])) . "`) ";
    			$returnStr .= "VALUES (" . $this->_getValues(array_values($this->values[0])) . ")";
    		}
    	}
    	
    	return $returnStr;
    }
    
    private function _getValues($values){
    	$returnStr = "";
    	foreach($values as $value){
    		if($value instanceof Literal){
    			$returnStr .= $value;
    		}
    		else{
    			$returnStr .= "'$value'";
    		}
    		
    		$returnStr .= ",";
    	}
    	
    	return trim($returnStr, ",");
    }
    
    private function _getType(){
    	switch($this->type){
    		case self::TYPE_DELAYED:
    			return "DELAYED ";
    		case self::TYPE_HIGH_PRIORITY:
    			return "HIGH_PRIORITY ";
    		case self::TYPE_LOW_PRIORITY:
    			return "LOW_PRIORITY ";
    		case self::TYPE_IGNORE:
    			return "IGNORE ";
    		default:
    			return "";
    	}
    }
}