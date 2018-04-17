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
class Join extends QBpart {

	const INNER_JOIN = 'INNER';
	const LEFT_JOIN = 'LEFT';
	const RIGHT_JOIN = 'RIGHT';
	const OUTER_JOIN = 'OUTER';
	const ON = 'ON';
	const WITH = 'WITH';

	private $_joinType;
	private $_join;
	private $_alias;
	private $_condition;
	private $_indexBy;

	public function __construct($joinType, $join, $alias = null, $condition = null, $indexBy = null) {
		$this->_joinType = $joinType;
		$this->_join = $join;
		$this->_alias = $alias;
		$this->_condition = $condition;
		$this->_indexBy = $indexBy;
	}

	public function getAlias() {
		return $this->_alias;
	}

	public function getString() {
		$returnString = strtoupper($this->_joinType) . ' JOIN ';

		$join = Base::getStringFromPart($this->_join);
		$condition = Base::getStringFromPart($this->_condition);
		$indexBy = Base::getStringFromPart($this->_indexBy);
		$alias = Base::getStringFromPart($this->_alias);
		
		
		if ($this->_join instanceof QueryBuilder or $this->_join instanceof Unionx) {
			$returnString .= "(" . $join . ")";
		}
		else {
			$returnString .= "`$join`";
		}

		$returnString .= ($this->_alias ? ' as `' . $alias . '`' : '')
			. ($this->_condition ? ' ON (' . $condition . ')' : '')
			. ($this->_indexBy ? ' INDEX BY ' . $indexBy : '');

		return $returnString;
	}

}
