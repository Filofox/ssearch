<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

require_once( sSearch::GetPathLibrary() . 'core/sSearchResult.class.php' );

/**
 * A list of search results
 */
class sSearchQuery implements Iterator, Countable{

	private $position = 0;

	public $terms;
	public $domain = null;

	public $start;

	public $total = 0;
	public $max = 0;

	public $count = 0;

	public $results = array();

	public function AddResult( sSearchResult $result ){
		array_push( $this->results, $result );
		$this->count = count( $this->results );
	}

    public function __construct() {
        $this->position = 0;
    }

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        return $this->results[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return isset($this->results[$this->position]);
    }

	public function count(){
		return count( $this->results );
	}
}