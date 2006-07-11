<?php

/*
 *  $Id: PHP5BasicObjectBuilder.php 120 2005-06-17 02:18:41Z hans $
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
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'propel/engine/builder/om/PeerBuilder.php';

/**
 * Generates the empty PHP5 stub peer class for user object model (OM).
 * 
 * This class produces the empty stub class that can be customized with application
 * business logic, custom behavior, etc.
 * 
 * This class replaces the ExtensionPeer.tpl, with the intent of being easier for users
 * to customize (through extending & overriding).
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @package propel.engine.builder.om.php5
 */
class PHP5ExtensionPeerBuilder extends PeerBuilder {
	
	/**
	 * Returns the name of the current class being built.
	 * @return string
	 */
	public function getClassname()
	{
		return $this->getStubObjectBuilder()->getClassname() . 'Peer';
	}

	/**
	 * Adds the include() statements for files that this class depends on or utilizes.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addIncludes(&$script)
	{
		$script .= "
  // include base peer class
  require_once '".$this->getPeerBuilder()->getClassFilePath()."';
  
  // include object class
  include_once '".$this->getStubObjectBuilder()->getClassFilePath()."';
";
	} // addIncludes()
	
	/**
	 * Adds class phpdoc comment and openning of class.
	 * @param string &$script The script will be modified in this method.
	 */
	protected function addClassOpen(&$script)
	{
		
		$table = $this->getTable();
		$tableName = $table->getName();
		$tableDesc = $table->getDescription();
		
		$baseClassname = $this->getPeerBuilder()->getClassname();
		
		$script .= "

/**
 * Skeleton subclass for performing query and update operations on the '$tableName' table.
 *
 * $tableDesc
 *";
		if ($this->getBuildProperty('addTimeStamp')) {
			$now = strftime('%c');
			$script .= "
 * This class was autogenerated by Propel on:
 *
 * $now
 *";
		}
		$script .= "
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package ".$this->getPackage()."
 */	
class ".$this->getClassname()." extends $baseClassname {
";
	}
	
		/**
	 * Specifies the methods that are added as part of the stub peer class.
	 * 
	 * By default there are no methods for the empty stub classes; override this method
	 * if you want to change that behavior.
	 * 
	 * @see ObjectBuilder::addClassBody()
	 */

	protected function addClassBody(&$script)
	{
		// there is no class body
	}
	
	/**
	 * Closes class.
	 * @param string &$script The script will be modified in this method.
	 */	
	protected function addClassClose(&$script)
	{
		$script .= "
} // " . $this->getClassname() . "
";
	}
	
	
} // PHP5ExtensionPeerBuilder
