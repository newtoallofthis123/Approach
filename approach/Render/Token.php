<?php

namespace Approach\Render;

class Token extends Node
{
	public function __construct(
		public $content = null,
		public null|string|\Stringable|Stream $name = null,
	) {
		parent::__construct(prerender: false);
	}
}
