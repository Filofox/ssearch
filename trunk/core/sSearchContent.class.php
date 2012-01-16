<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */
require_once( sSearch::GetPathLibrary() . 'core/sSearchContent.class.php' );

/**
 * Base calss for content items
 */
class sSearchContent{

	protected $config;

	public $url;

	public $index = true; //  Set to false to exclude content from search

	public $protocol;
	public $domain;
	public $title;
	public $content;

	public $links = null;

	public function __construct( $config, $url, $content_string ){

		$this->config = $config;

		$this->url = $url;
		
		$url_details = parse_url( $url );
		$this->domain = $url_details[ 'host' ];
		$this->protocol = $url_details[ 'scheme' ];
	}

	/**
	 * Get a list of URLs in this document
	 *
	 * @return		array
	 */
	public function GetLinks(){
		return $this->links;
	}
}

?>
