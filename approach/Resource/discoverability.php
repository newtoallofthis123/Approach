<?php

namespace Approach\Resource;

use \Approach\nullstate;
use \Approach\Resource\Aspect\Aspect;
use \Approach\Resource\Aspect\discover;


trait discoverability
{
	/*
	* @comprehension Resource/Aspects::define
	*
	* $caller is the object calling define() (the object that is being defined)
	* debug_backtrace() returns an array of arrays, each of which has an element 'object'
	* 	[
	* 		0 => [	'object' => $calling_scope_a	],
	* 		1 => [	'object' => $calling_scope_b	]
	* 	]
	* 
	* We know the second element is the object calling define(), because the 
	* first element holds this define() method.
	* 
	* We do this to grab a reference to the SomeType\Source, for example MariaDB\SomeServer or MyEngine\GPU0,
	* calling this Collection's define() methods through the aspect system. ie:
	* 
	* $source calls discover()
	* 	$source->collection calls discover()
	* 		$collection->discover calls Aspect\Collection::define()
	* 		  - $caller is the $collection object
	* 		  - We are not concerned with reflection costs here,
	* 		    this is only called seldomly on startup/updates.
	*	
	* This is a good example of how the aspect system is intended to work.
	* Much of this is still in flux, but the general idea is that Aspects
	* will keep properties and methods *out* of the Resource class, especially
	* for leaf Resources, and instead use the aspect system to define them.
	* 
	* Doing this allows us to keep the Resource class as a simple container to track connectivity 
	* and sharing which can be extended by the user, and always has a payload. Aspects can be 
	* further defined by the user, but especially play a role for library developers.
	* 
	* The aspect system is intended to be used for:
	* 	- defining aspects of a Resource class
	* 	- defining aspects of a Resource instance
	* 	- defining aspects of a Resource instance's payload
	* 	- defining aspects of a Resource instance's payload's properties
	* 	- defining aspects of a Resource instance's payload's methods
	*
	* Aspects are of categories:
	* 	- container
	* 	- location
	* 	- operation
	* 	- field
	* 	- quality
	* 	- quantity
	* 	- map
	* 	- identity
	* 	- access
	* 	- state
	*
	* Each of these top-level categories may have domain-specific sub-categories, supported by the
	* system or libraries. For example, the MariaDB library may have a sub-category of 'table' for
	* the 'container' category, and a sub-category of 'column' for the 'field' category. It could 
	* equivalently simply use the field category for fields and the container category for tables.
	*
	* Constraining resource definitions to these categories allows tooling to reach full-coverage
	* of arbitrary resources, and allows for the creation of generic tools that can be used across
	* domains. For example, a generic tool for creating a CRUD interface for a resource can be
	* created, and then used for any resource that has the 'container' and 'field' categories.
	*
	* Further, such systems may rely on sub-categories as an implementation of a given standard,
	* implying dependencies on the standard; namely libraries that implement the standard must
	* have pre-knowlege of the sub-categories. Reducing standards to taxonomy allows for us to
	* explicitly define interfaces without diamond-problem dependencies, and allows for the creation of
	* generic tools that can be used across domains.
	*
	* Approach doesn't generally enforce dependency constraints like this, however Resources
	* are always "out-of-band signals" such as a database, a file, a network connection, etc and
	* therefor have some connection from the runtime to the resource; whether a simple pointer or
	* an internet connection using OIDC, etc, etc. Resources are never direct-access, and must have
	* considerations for delegating actions to the resource, and receiving actions from the resource.
	*
	* While not all taxonomies are trees, all taxonomies can be modeled as trees. This allows for
	* the creation of generic tools that can be used across domains. We consider "all system resources"
	* to be a heatmap distributed across an N-dimensional space, where each dimension is a connection/type pair. The heatmap
	* is a function of the number of resources in each taxonomy, recursively.
	* 
	* TL;DR: 
	* - Resources come from Sources. They are *re* - sourced, from some source media or system.
	* - This is very vague, so requires constraints to cover all possible resources.
	* - We strapped those onto Sources, Instances of a Type available at such a source.
	* - URLs are sufficient 
			- ResourceType://Source (eg: MariaDB://MyServer, File://home/user/file.txt, MyService://webhook.example.com)
			- ResourceType://Source/Type/Type.. (eg: MariaDB://MyServer/MyDatabase, File://home/user/file.txt/line[3]/char[0], MyService://webhook.example.com/MyEndpoint)
	* - What is available depends on a Resources location discovery, which is a function of the Resource's Aspects.
	* - What can be done to a Resource depends on a Resource's operation discovery, and which methods a library has implemented.
	* - Libraries might do all sorts of things with fields, qualities, quantities, maps, identities, access, and states.
	*
	* A final word-to-the-wise, for library developers:
	* Creating a library for some type of Resource server, you implement your Resource's find()
	* protocol by instead implementing the following methods relying on the aspect system:
	* 	- Resource\MyResource->pick()
	* 	- Resource\MyResoucre->sift()
	* 	- Resource\MyResource->sort()
	* 	- Resource\MyResource->weigh()
	* 	- Resource\MyResource->divide()
	* 	- Resource\MyResource->filter()
	* 
	* If you can accomplish this one herculean task, you will have created a library that can be used
	* by any Resource, and can be used to create generic tools that can be used across domains. Your
	* resource system will be completely scalable in any Approach context, and will be able to be
	* used by any project using Approach.
	*
	* acquire(), transport(), promise(), and bestow()
	* pull(), push(), and exchange() 
	* load(), save(), are all defined in the Resource\sourceability trait.
	*
	* If a Resource is both locatable and sourceable via its aspects, it can be used
	* to drive Components, Compositions, and Services. These trait functions try to provide
	* the library developer with most of the transactional logic required to implement
	* a Resource, and mainly exist as hooks for your library to authenticate, log, register
	* shared data, etc.	They should generally transparently hand-off to your Resource's
	* Connection Service as thinly as possible.
	*
	*/

	/**
	 * Discover the aspects of a Resource class
	 * Generates Aspect classes for a given Resource class, updating class definition files
	 * and meta-programming the Resource class to use Aspect classes
	 * 
	 * @param null|\Stringable|string|Resource $resource - The resource to discover, or null for the context we are in
	 * 
	 * @return array - An array of Aspect objects
	 * 
	 */
	public static function discover(null|\Stringable|string|Resource $resource = null)
	{
		// If no resource was given, use the context we are in
		$namespace = static::class;

		// Allow a resource to take precedence over the context as the
		// generic Resource\Aspect\aspects can be used by any resource
		// and has the primary taxonomy of aspects in its cases()
		if ($resource !== null) {
			// Derrive the namespace from the given resource's underlying class
			$namespace = get_class($resource);
			$namespace = substr($namespace, 0, strrpos($namespace, '\\'));
		}

		// Scan the namespace for Aspect classes matching this enum's cases
		$aspects = [];
		foreach (static::cases() as $case) {
			$aspect = $namespace . '\\' . $case;
			if (($aspect instanceof Aspect)) {
				$ac = explode('\\', get_class($aspect));
				// Remove the last element, which is the class name
				array_pop($ac);
				$discover_class = implode('\\', $ac) . '\\discover';
				$aspects[$case] = $discover_class::define($aspect);		// Build a tree of Aspect objects
			}
		}
		return $aspects;
	}

	/**
	 * Define the aspects of a Resource class
	 * Generates Aspect classes for a given Resource class, updating class definition files
	 * and meta-programming the Resource class to use Aspect classes
	 * 
	 * @param which $which - Which aspect to define, or null for all
	 * 
	 */

	public static function define($caller = null, $which = null)
	{
		$state = nullstate::ambiguous;

		switch ($which) {
			case discover::container:
				static::define_containers($caller);
				break;
			case discover::location:
				static::define_locations($caller);
				break;
			case discover::operation:
				static::define_operations($caller);
				break;
			case discover::field:
				$fields = static::define_fields($caller);
				static::define_profile($caller, $fields);
                break;
			case discover::quality:
				static::define_qualities($caller);
				break;
			case discover::quantity:
				static::define_quantities($caller);
				break;
			case discover::map:
				static::define_maps($caller);
				break;
			case discover::state:
				static::define_states($caller);
				break;
			case discover::access:
				static::define_access($caller);
				break;
			case null:
				static::define_containers($caller);
				static::define_locations($caller);
				static::define_operations($caller);
				$fields = static::define_fields($caller);
                static::define_profile($caller, $fields);
				static::define_qualities($caller);
				static::define_quantities($caller);
				static::define_maps($caller);
				static::define_states($caller);
				static::define_access($caller);
				break;
			default:
		}

		//TODO: Actually implement this
//		static::define_profile();
//		static::define_user_trait();

		// check if parent::class is a self::class
		// if so, call parent::define($which), else nothing

		if (is_subclass_of(parent::class, self::class) || parent::class === self::class) {
			parent::class::define($which);
		}
	}

	public static function define_containers($caller)
	{
		return true;
	}
	public static function define_fields($caller)
	{
		return [];
	}
	public static function define_locations($caller)
	{
		return true;
	}
	public static function define_operations($caller)
	{
		return true;
	}
	public static function define_qualities($caller)
	{
		return true;
	}
	public static function define_quantities($caller)
	{
		return true;
	}
	public static function define_maps($caller)
	{
		return true;
	}
	public static function define_states($caller)
	{
		return true;
	}
	public static function define_access($caller)
	{
		return true;
	}
}
