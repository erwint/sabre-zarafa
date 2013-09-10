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


class Zarafa_Principal_Collection extends Sabre\DAVACL\AbstractPrincipalCollection
{
    protected $bridge;

    public function __construct(Zarafa_Bridge $bridge, Sabre\DAVACL\PrincipalBackend\BackendInterface $principalBackend, $principalPrefix = 'principals') {
	parent::__construct($principalBackend, $principalPrefix);
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

        return new Zarafa_Principal($this->bridge, $this->principalBackend, $principal);

    }

}


