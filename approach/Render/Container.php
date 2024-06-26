<?php

/**
 * 
 * This is the Container class, which is the base class for all Approach\Render objects.
 * 
 * Takes advantage of Greenspun's Tenth Rule:
  		"Any sufficiently complicated C or Fortran program contains an ad hoc, 
  			informally-specified, bug-ridden, slow implementation of half of Common Lisp."
 															- Philip Greenspun
 * 
 * Greenspun refer's to the fact that Common Lisp is a language that is capable of being 
 * used to implement any other language, including itself.  This is because Common Lisp is
 * a homoiconic language, which means that it can treat its own code as data, and its data
 * as code.
 * 
 * Importantly, LISPs are very capable when it comes to stream processing.  
 * A stream represents a sequence of elements, which are processed in series. 
 * Approach's Render pipeline is heavily influenced by the stream processing paradigm.
 * Every Node object is a stream, and every Nodes may be made to stream themselves, as an
 * entire tree, subtree, node instance or rendered output.
 * 
 * Since all Containers and Node objects have a List and a Keyed interface, they are
 * capable of being processed as streams.  This means that containers and nodes can
 * be composed by Approach\Composition\Composition, which is a stream
 * processor. All for simply implementing RenderHead(), RenderCorpus(), and RenderTail() 
 * for your given format or specialized element.
 * 
 * Streams are very powerful because they are not limited to being processed by one function.
 * Streams can be processed by many functions, in a chain, or a pipeline.  This is called
 * "stream composition", and is a very powerful way to process data.
 * 
 * Streams can also be processed by functions that take one or multiple streams as input, and return
 * one or multiple streams as output.  This is called "stream multiplexing and demultiplexing", a 
 * powerful way to accelerate generic data processing.
 * 
 * Given that all complex systems end up implementing a subset of Lisp, our subset of Lisp
 * is the most powerful subset of Lisp that we could create. You can further customize the storage 
 * mechanism of your Node or Container, by altering the ArrayObject implementation and electing 
 * some form of ordering for render() and stream().
 * 
 * With all other Layer built on top of Render, you can create any data structure, and render it
 * in any format, and stream it to any destination. Physical and logical separation of concerns
 * is one of the most important aspects of software engineering.  Approach takes this to heart,
 * and provides a solid foundation for building any kind of application through rich typing
 * and semantic definition.
 * 
 * @package Approach
 * @subpackage Render
 * @version 2.0.-1 beta
 * 
 * @license Apache 2.0
 * @since	2023-02-04
 * @see	https://orchetrationsyndicate.com/Approach/Render/Container
 * 
 */

namespace Approach\Render;

use \Approach\nullstate as nullstate;
use \Approach\Render\Stream;
use \Approach\Scope;
use \Traversable;
use \Stringable;

class Container extends \ArrayObject implements Stream
{
	protected static $_render_streams = [];
	private array $_labeled_nodes = [];
	private array $_node_labels = [];


	/**
	 * An array of Node objects that are linked to the Node object.
	 * Generally, these are nodes that are children of the Node object.
	 * 
	 * In Approach\Render\Attributes, all Attribute objects are treated as the same level.
	 * Since nodes may also be references to elsewhere in the currently loaded Node tree(s),
	 * graph structures can be created. This is useful for creating hyperlinks.
	 * 
	 * A concrete example of this is the Resource Protocol, which uses this to create
	 * hyperlinks to other nodes. For example, the following Resource Protocol URL:
	 * 
	 * MariaDB://localhost/MyDatabase/MyTable[MyColumn,MyColumn2:0..10,MyColumn3,$5:MyColumn>1][0..50];MyColumn3 desc,MyColumn2 asc/0..10
	 * 
	 * Is used to translate to the following Node tree:
	 * 
	 * Service\MariaDB\Connector - Protocol Root
	 *     - Resource\MariaDB\Server @ localhost
	 *       - Resource\MariaDB\Database @ MyDatabase
	 *        - Resource\MariaDB\Table @ MyTable
	 *          - Table->pick(MyTable\fields::MyColumn)     // adds a node to the $table->pick[]
	 *          - Table->pick(MyTable\fields::MyColumn2)
	 *          - Table->pick(MyTable\fields::MyColumn3)
	 *          - Table->sift( mode: MariaDB\sift::range, [  MyTable\fields::MyColumn2, 0, 10] )
	 *          - Table->weight( what: MyTable\field::Column0, operator: operator::GREATER_THAN, value: 1, weight: 5 )
	 *          - Table->divide( block_index: 0, block_size: 50 )
	 *          - Table->sort( MyTable\fields::MyColumn3, MariaDB\sort::DESCENDING )
	 *          - Table->sort( MyTable\fields::MyColumn2, MariaDB\sort::ASCENDING )
	 * 
	 * The Node tree is then rendered into a SQL query.
	 *
	 * All elements in $nodes are of type Node, or a subclass of Node.
	 * 
	 * @var Node[] An array of Nodes.
	 */
	public $nodes = [];
	use Streamability;

	/**
	 * This creates a new Node object with properties set from an array.
	 * 
	 * @param array properties An array of properties that need to be set for the Node object.
	 * 
	 * @return Node A Node object.
	 */
	public static function __set_state(array $properties): Node
	{
		$node = new Node();
		if(isset($properties['content']))
			$node->content = $properties['content'];
		if(isset($properties['prerender']))
			$node->prerender = $properties['prerender'];
		if(isset($properties['nodes']))
			$node->nodes = $properties['nodes'];		
		if(isset($properties['_render_id']))
			$node->_render_id = $properties['_render_id'];
		return $node;
	}

	public function __toString()
	{
		return $this->render();
	}
	/**
	 * The function renders content of a Node tree.
	 * 
	 * @return The `render()` method returns a string that is the concatenation of the results of
	 * calling the `RenderHead()`, `RenderCorpus()`, and `RenderTail()` methods.
	 */
	public function render()
	{
		$render = '';
		foreach ($this->stream() as $r)
			$render .= $r;

		return $render;
	}

	/**
	 * This function does the same as render(), using yield instead of concatenation.
	 */
	public function stream()
	{
		// $stream = Scope::$Active->GetOutputStream();	// usually the result of fopen('php://output', 'w')
		foreach ($this->RenderHead() as $r)
			yield $r;
		foreach ($this->RenderCorpus() as $r)
			yield $r;
		foreach ($this->RenderTail() as $r)
			yield $r;
	}


	/**
	 * This function renders the head of a Node tree.
	 */

	public function RenderHead(): Traversable|\Approach\Render\Stream|string|\Stringable
	{
		//TODO: Implement RenderHead() method.
		yield '';
	}


	/**
	 * This function renders the content and nodes of a Node tree.
	 */
	public function RenderCorpus(): Traversable|\Approach\Render\Stream|string|\Stringable
	{
		if (isset($this->content))
			yield $this->content;
		
		foreach ($this->nodes as $n) {
			yield from $n->stream();
			// $n->prerender = true;
		}
	
	}

	/**
	 * This function renders the tail of a Node tree.
	 */

	public function RenderTail(): Traversable|\Approach\Render\Stream|string|\Stringable
	{
		yield '';
	}
}
