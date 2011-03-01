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
	
	public function toJSON(){
		return json_encode( $this );
	}
	/**
	 * Convert a query object to XML
	 *
	 * @param		sSearchQuery		$query
	 *
	 * @return		string
	 */
	public function toXML(){
		
		$xml =
'<?xml version="1.0"?>
<root>
	<terms><![CDATA[[[terms]]]]></terms>
	<domain><![CDATA[[[domain]]]]></domain>
	<start>[[start]]</start>
	<max>[[max]]</max>
	<total>[[total]]</total>
	<count>[[count]]</count>
	<results>
		[[results]]
	</results>
</root>';
		
		$results_xml = '';
		foreach( $this as $result ){
			$results_xml .= str_replace
			(
				array
				(
					'[[url]]',
					'[[title]]',
					'[[snippet]]',
					'[[snippet_highlighted]]'
				),
				array
				(
					$result->url,
					$result->title,
					$result->snippet,
					$result->snippet_highlighted
				),
'
		<result>
			<url><![CDATA[[[url]]]]></url>
			<title><![CDATA[[[title]]]]></title>
			<snippet><![CDATA[[[snippet]]]]></snippet>
			<snippet_highlighted><![CDATA[[[snippet_highlighted]]]]></snippet_highlighted>
		</result>
'
			);
		}
		
		return str_replace
		(
			array
			(
				'[[terms]]',
				'[[domain]]',
				'[[start]]',
				'[[max]]',
				'[[total]]',
				'[[count]]',
				'[[results]]'
			),
			array
			(
				$this->terms,
				$this->domain,
				$this->start,
				$this->max,
				$this->total,
				$this->count,
				$results_xml
			),
			$xml
		);
	}
}