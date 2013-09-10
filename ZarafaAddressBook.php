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

class Zarafa_Address_Book extends Sabre\CardDAV\AddressBook
{
    protected $bridge;

    /**
     * Constructor
     *
     * @param Backend\BackendInterface $carddavBackend
     * @param string $principalUri
     */
    public function __construct(Zarafa_Bridge $bridge, Sabre\CardDAV\Backend\BackendInterface $carddavBackend, array $addressBookInfo) {

	parent::__construct($carddavBackend, $addressBookInfo);
	$this->bridge = $bridge;
    }

    /**
     * Returns a card
     *
     * @param string $name
     * @return \ICard
     */
    public function getChild($name) {

        $obj = $this->carddavBackend->getCard($this->addressBookInfo['id'],$name);
        if (!$obj) throw new DAV\Exception\NotFound('Card not found');
        return new Zarafa_Card($this->bridge, $this->carddavBackend,$this->addressBookInfo,$obj);

    }

    /**
     * Returns the full list of cards
     *
     * @return array
     */
    public function getChildren() {

        $objs = $this->carddavBackend->getCards($this->addressBookInfo['id']);
        $children = array();
        foreach($objs as $obj) {
            $children[] = new Zarafa_Card($this->bridge, $this->carddavBackend,$this->addressBookInfo,$obj);
        }
        return $children;

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
                'privilege' => '{DAV:}write',
                'principal' => $this->bridge->getPrincipalUri($this->bridge->getConnectedUser()),
                'protected' => true,
            ),
        );

    }
}
