<?php
namespace Approach\Resource;

use \Approach\Render\Node;
use \Approach\Resource\Resource;
use \Approach\nullstate;
use \Stringable;

trait sourceability
{
	abstract public static function aquire(Stringable|string|Node $where): ?Node;
	abstract public static function pull(Stringable|string|Node $where): ?Node;
	abstract public function load(): ?Node;

	abstract public static function save(Resource $where): ?bool;
	abstract public static function push(Resource $where): ?bool;
	abstract public static function release(Resource $where): ?bool;


	// Create a Service child class with MyConnector::connect() and MyConnector::disconnect()
	// Flow::IN - The service starts by consuming external sources
		// promise
			// if !connected, connect
			// if !acquired source, acquire source
			// if !loaded source resource(s), load source resource(s)
	// or
	// Flow::OUT - The service is creating output for external consumers
		// promise
			// if !connected, connect
			// if !acquired target, acquire target
			// push pre-acquired resource to target
	abstract public static function transport();

	// Derrive branchable->promise() from Service\Branch to define promises
	abstract public static function promise();

	/**
	 *  promise 
	 * 	- if !connected, connect
	 * 	- if !acquired $where, acquire $where
	 *	- send $intent & $support to acquired $where
	 * 	- recieve response resource 
	 */
	abstract public static function act($where, $intent, ...$support);

	// two-way transport 
	// from Service|Resource pair to Service|Resource pair
	abstract public static function exchange();
	// {
		// if !connected, connect
		// if !acquired target, acquire target

		// acquire 

		// transport() source resource with Flow::IN
		// transport() to target resource with Flow::OUT

		// promise
		// push source resources to target location & release if possible
		// recieve response resource
		// if !released, release target
		// return response resource

		// see approach/Dataset/exchangeTransport->prep_exchange()
	// }
	abstract public static function bestow($from, $to, $who);
	// {
		/**
		 * $from and $to both describe location and entity (who have associated RBAC permissions and security realm) to act on
		 * every endpoint inherits from the endpoint above it, so 
		 *  - mariadb://database/table/column inherits from 
		 *  - mariadb://database/table which inherits from 
		 *  - mariadb://database which inherits from mariadb:// which inherits from
		 *  - // which is controlled by the conductor.orchestra security realm in a separate project
		 * 
		 * Aside from the conductor.orchestra security realms, each endpoint in the system matches one or more security realms, 
		 * which are the Approach Layers:
		 * 
		 * 	- Work			- Literally the work being done. The CPU processes moving bits around. Usually no need to operate here.
		 * 	- Render		- The CPU processes creating output. Controlled by the Approach\Render\Node|Stream family of classes.
		 * 	- Imprint		- Renderable trees generated according to Pattern files. Controlled by the Approach\Imprint family of classes.
		 * 	- Resource		- Existing patterns (Approach or 3rd party such as API/DBs/files..) whose source must be repeated into the system and parsed into a structure. Controlled by the Approach\Resource family of classes.
		 * 	- Component		- Aggregations of Resources into a singular unit over an Imprinted Pattern. Controlled by the Approach\Component family of classes.
		 *  - Composition	- Output structuring with dynamic calling of Component streams. Controlled by the Approach\Composition family of classes.
		 * 	- Service		- System-wide typecasting, format conversion, transport and security. Access Compositions and Component APIs. Controlled by the Approach\Service family of classes.
		 *  - Instrument	- Representation of servers, virtual machines, containers - one IP or equivalent network identifier. Controlled by devops tooling. Classes pending.
		 *  - Ensemble		- Representation of a cluster of Instruments. Controlled by devops tooling. Classes pending.
		 *  - Orchestra		- Representation of a service mesh of Ensembles. Controlled by devops tooling. Classes pending.
		 * 
		 * Roles in the system are defined by the security realms they match, and the security realms they inherit from.
		 * Roles may have the following permissions:
		 * 
		 * Entity Ability Grants:
		 *	- Grant			- Grant access to a federated typepath
		 *  				  A role with Grant permission may give any permission they hold to any other role
		 * 					  Exception: A role may be altered by a whitelist/blacklist specific typepaths
		 * 	- Create		- Create a federated entity (org, group, project, instrument, role, etc)
		 * 	- Read			- Read access to a federated entity
		 * 	- Update		- Update a federated entity (write on update)
		 * 	- Replace		- Replace a federated entity (overwrite, updating references and identifiers)
		 * 	- Tennent		- This entity may have tennents (sub-entities)
		 *  - Service		- This entity may have an API key to access a federated entity
		 *  - Masquerade	- This entity may impersonate another entity
		 * 
		 * 
		 * Resource Ability Grants:
		 *	- Revoke		- Revoke access to a federated typepath
		 *	- Create		- Create a federated typepath (write on create)
		 *	- Read			- Read access to a federated typepath
		 *	- Update		- Update a federated typepath (write on update)
		 *	- Replace		- Replace a federated typepath (overwrite, updating references and identifiers)
		 *	- Run			- Execute access to a federated typepath
		 *	- Remove		- Delete a federated typepath (remove from context) 
		 *	- Destroy		- Destroy a federated typepath (cascade delete until source or fail)
		 *	- Lock			- Lock/Unlock a federated typepath
		 *	- Connect		- Connect to a federated typepath
		 *	- Secure		- OpenID & X509 authentication, authorization and encryption/decryption
		 */
	// }
	
	abstract public static function locate($where);
		// robust fetch

	abstract public static function interact();
		// multi-step calls to act, recieve, handler

	// Discovery now part of MyLibrary/Aspect/MyType.php
	// abstract public function discover(null|Resource|Stringable|string $which): nullstate;
		// return nullstate::defined;
}
