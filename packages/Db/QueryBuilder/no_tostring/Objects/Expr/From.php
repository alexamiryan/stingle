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
 * Expression class for SQL from
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class From extends QBpart
{
    /**
     * @var string
     */
    private $_from;

    /**
     * @var string
     */
    private $_alias;

    /**
     * @var string
     */
    private $_indexBy;

    /**
     * @param string $from      The class name.
     * @param string $alias     The alias of the class.
     * @param string $indexBy   The index for the from.
     */
    public function __construct($from, $alias, $indexBy = null)
    {
        $this->_from    = $from;
        $this->_alias   = $alias;
        $this->_indexBy = $indexBy;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->_from;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->_alias;
    }

    /**
     * @return string
     */
    public function getString()
    {
    	$returnString = '';
    	
	$from = Base::getStringFromPart($this->_from);
	
    	if($this->_from instanceof QueryBuilder or $this->_from instanceof Unionx){
    		$returnString = "($from)";
    	}
    	else{
    		$returnString = "`$from`";
    	}
    	
        $returnString .= ($this->_alias ? " as `$this->_alias` " : '').
                ($this->_indexBy ? ' USE INDEX (' . $this->_indexBy . ')' : '');
        
        return $returnString;
    }
}