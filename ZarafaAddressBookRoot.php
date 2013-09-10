<?php
/*
 * Copyright 2013 Erwin Tratar
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * "Zarafa" is a registered trademark of Zarafa B.V.
 *
 * This software use SabreDAV, an open source software distributed
 * with New BSD License. Please see <http://code.google.com/p/sabredav/>
 * for more information about SabreDAV
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Project page: <http://github.com/bokxing-it/sabre-zarafa/>
 *
 */


require_once 'common.inc.php';
require_once 'ZarafaLogger.php';

// PHP-MAPI
require_once("mapi/mapi.util.php");
require_once("mapi/mapicode.php");
require_once("mapi/mapidefs.php");
require_once("mapi/mapitags.php");
require_once("mapi/mapiguid.php");

class Zarafa_Address_Book_Root extends Sabre\CardDAV\AddressBookRoot
{
		protected $bridge;


    /**
     * Constructor
     *
     * This constructor needs both a principal and a carddav backend.
     *
     * By default this class will show a list of addressbook collections for
     * principals in the 'principals' collection. If your main principals are
     * actually located in a different path, use the $principalPrefix argument
     * to override this.
     *
     * @param DAVACL\PrincipalBackend\BackendInterface $principalBackend
     * @param Backend\BackendInterface $carddavBackend
     * @param string $principalPrefix
     */
    public function __construct(Zarafa_Bridge $bridge, Sabre\DAVACL\PrincipalBackend\BackendInterface $principalBackend,Sabre\CardDAV\Backend\BackendInterface $carddavBackend, $principalPrefix = 'principals') {

    	parent::__construct($principalBackend, $carddavBackend, $principalPrefix);
			$this->bridge = $bridge;
    }

    /**
     * This method returns a node for a principal.
     *
     * The passed array contains principal information, and is guaranteed to
     * at least contain a uri item. Other properties may or may not be
     * supplied by the authentication backend.
     *
     * @param array $principal
     * @return \Sabre\DAV\INode
     */
    public function getChildForPrincipal(array $principal) {

        return new Zarafa_User_Address_Books($this->bridge, $this->carddavBackend, $principal['uri']);

    }


    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL() {

        return array(
            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->bridge->getPrincipalUri($this->bridge->getConnectedUser()),
                'protected' => true,
            ),

            array(
                'privilege' => '{DAV:}read',
                'principal' => $this->getPrincipalUrl(),
                'protected' => true,
            ),
        );

    }

}
