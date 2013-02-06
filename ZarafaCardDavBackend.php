<?php
/*
 * Copyright 2011 - 2012 Guillaume Lapierre
 * Copyright 2012 - 2013 Bokxing IT, http://www.bokxing-it.nl
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

require_once("common.inc.php");

// Logging
include_once ("log4php/Logger.php");
Logger::configure("log4php.xml");

// PHP-MAPI
require_once("mapi/mapi.util.php");
require_once("mapi/mapicode.php");
require_once("mapi/mapidefs.php");
require_once("mapi/mapitags.php");
require_once("mapi/mapiguid.php");
	
class Zarafa_CardDav_Backend extends Sabre_CardDAV_Backend_Abstract {
	
	protected $bridge;
	private $logger;

    public function __construct($zarafaBridge) {
		// Stores a reference to Zarafa Auth Backend so as to get the session
        $this->bridge = $zarafaBridge;
		$this->logger = Logger::getLogger(__CLASS__);		
    }
	
    /**
     * Returns the list of addressbooks for a specific user.
     *
     * Every addressbook should have the following properties:
     *   id - an arbitrary unique id
     *   uri - the 'basename' part of the url
     *   principaluri - Same as the passed parameter
     *
     * Any additional clark-notation property may be passed besides this. Some 
     * common ones are :
     *   {DAV:}displayname
     *   {urn:ietf:params:xml:ns:carddav}addressbook-description
     *   {http://calendarserver.org/ns/}getctag
     * 
     * @param string $principalUri 
     * @return array 
     */
	public function
	getAddressBooksForUser ($principalUri)
	{
		$this->logger->info("getAddressBooksForUser($principalUri)");

		$folders = array_merge(
			$this->bridge->get_folders_private($principalUri),
			$this->bridge->get_folders_public($principalUri)
		);
		$dump = print_r($folders, true);
		$this->logger->debug("Address books:\n$dump");

		return $folders;
	} 

	/**
	 * Updates an addressbook's properties
	 *
	 * See Sabre_DAV_IProperties for a description of the mutations array, as
	 * well as the return value.
	 *
	 * @param mixed $addressBookId
	 * @param array $mutations
	 * @see Sabre_DAV_IProperties::updateProperties
	 * @return bool|array
	 */
	public function
	updateAddressBook ($addressBookId, array $mutations)
	{
		$this->logger->info("updateAddressBook(" . bin2hex($addressBookId). ")");

		if (READ_ONLY) {
			$this->logger->warn(__FUNCTION__.': trying to update read-only address book');
			return FALSE;
		}
		if (FALSE($folder = $this->bridge->get_folder($addressBookId))) {
			$this->logger->warn(__FUNCTION__.': folder not found');
			return FALSE;
		}
		if (FALSE($folder->update_folder($mutations))) {
			$this->logger->fatal(__FUNCTION__.': cannot apply mutations');
			return FALSE;
		}
		return TRUE;
	}

    /**
     * Creates a new address book 
     *
     * @param string $principalUri 
     * @param string $url Just the 'basename' of the url. 
     * @param array $properties 
     * @return void
     */
    public function createAddressBook($principalUri, $url, array $properties) {
		$this->logger->info("createAddressBook($principalUri, $url)");
		
		if (READ_ONLY) {
			$this->logger->warn("Cannot create address book: read-only");
			return false;
		}
		
		$rootFolder = $this->bridge->getRootFolder();
		$displayName = isset($properties['{DAV:}displayname']) ? $properties['{DAV:}displayname'] : '';
		$description = isset($properties['{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description']) ? $properties['{' . Sabre_CardDAV_Plugin::NS_CARDDAV . '}addressbook-description'] : '';
		
		$subFolder = mapi_folder_createfolder($rootFolder, $displayName, $description, MAPI_UNICODE | OPEN_IF_EXISTS, FOLDER_GENERIC);
		mapi_setprops ($subFolder, array (907214878 => 'IPF.Contact'));
		mapi_savechanges($subFolder);

		if (mapi_last_hresult() > 0) {
			$this->logger->fatal("Error saving changes to addressbook: " . get_mapi_error_name());
			return false;
		}
	}

	/**
	 * Deletes an entire addressbook and all its contents
	 *
	 * @param mixed $addressBookId
	 * @return void
	 */
	public function
	deleteAddressBook ($addressBookId)
	{
		$this->logger->info("deleteAddressBook(" . bin2hex($addressBookId) . ")");
	
		if (READ_ONLY || !ALLOW_DELETE_FOLDER) {
			$this->logger->warn(__FUNCTION__.': Cannot delete address book: permission denied by config');
			return FALSE;
		}
		if (FALSE($folder = $this->bridge->get_folder($addressBookId))) {
			$this->logger->warn(__FUNCTION__.': could not find folder');
			return FALSE;
		}
		return $folder->delete_folder();
	}

	/**
	 * Returns all cards for a specific addressbook id.
	 *
	 * This method should return the following properties for each card:
	 *   * carddata - raw vcard data
	 *   * uri - Some unique url
	 *   * lastmodified - A unix timestamp

	 * @param mixed $addressBookId
	 * @return array
	 */
	public function
	getCards ($addressBookId)
	{
		$this->logger->info("getCards(" . bin2hex($addressBookId) . ")");
	
		if (FALSE($folder = $this->bridge->get_folder($addressBookId))) {
			$this->logger->warn(__FUNCTION__.': could not find folder');
			return Array();
		}
		return $folder->get_dav_cards();
	}

	/**
	 * Returns a specfic card
	 *
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @return void
	 */
	public function
	getCard ($addressBookId, $uri)
	{
		$this->logger->info("getCard(" . bin2hex($addressBookId) . ", $uri)");

		if (FALSE($folder = $this->bridge->get_folder($addressBookId))) {
			$this->logger->warn(__FUNCTION__.': could not find folder');
			return Array();
		}
		return $folder->get_dav_card($uri);
	} 

	/**
	 * Creates a new card
	 *
	 * @param mixed $addressBookId
	 * @param string $uri
	 * @param string $data
	 * @return string|null
	 */
	public function
	createCard ($addressBookId, $uri, $data)
	{
		$this->logger->info("createCard - $uri\n$data");

		if (READ_ONLY) {
			$this->logger->warn(__FUNCTION__.': cannot create card: read-only');
			return FALSE;
		}
		if (FALSE($folder = $this->bridge->get_folder($addressBookId))) {
			$this->logger->fatal(__FUNCTION__.': could not find folder');
			return FALSE;
		}
		if (FALSE($folder->create_contact($uri, $data))) {
			$this->logger->fatal(__FUNCTION__.': could not create card');
			return FALSE;
		}
		return NULL;
	} 

	/**
	 * Updates a card
	 *
	 * @param mixed $addressBookId
	 * @param string $uri
	 * @param string $data
	 * @return string|null
	 */
	public function
	updateCard ($addressBookId, $uri, $data)
	{
		$this->logger->info("updateCard - $uri");

		if (READ_ONLY) {
			$this->logger->warn(__FUNCTION__.': cannot update card: read-only');
			return FALSE;
		}
		if (FALSE($folder = $this->bridge->get_folder($addressBookId))) {
			$this->logger->warn(__FUNCTION__.': cannot find folder');
			return FALSE;
		}
		if (FALSE($folder->update_contact($uri, $data))) {
			$this->logger->warn(__FUNCTION__.': failed to update card');
			return FALSE;
		}
		return NULL;
	}

	/**
	 * Deletes a card
	 *
	 * @param mixed $addressBookId
	 * @param string $uri
	 * @return bool
	 */
	public function
	deleteCard ($addressBookId, $uri)
	{
		$this->logger->info("deleteCard($uri)");

		if (READ_ONLY) {
			$this->logger->warn(__FUNCTION__.': cannot delete card: read-only');
			return FALSE;
		}
		if (FALSE($folder = $this->bridge->get_folder($addressBookId))) {
			$this->logger->warn(__FUNCTION__.': cannot find folder');
			return FALSE;
		}
		if (FALSE($folder->delete_contact($uri))) {
			$this->logger->warn(__FUNCTION__.': failed to delete card');
			return FALSE;
		}
		return TRUE;
	}
}

?>