<?php

class sSearchURL{
	public $protocol = null;
	public $domain = null;
	public $path = null;
	public $query = null;
	public $fragment = null;

	/**
	 * Break a URL up into its constituent parts
	 *
	 * @param		string		$url
	 *
	 * @return		array				As per parse_url()
	 */
	public function __construct( $url ){

		// Check for protocol
		if( !preg_match( '~^[a-z]+://~Ui', $url ) ){
			// No protocol, i.e. this is a path not a full URL
			// Chuck on a dummy host/protocol for the moment
			$url = 'http://dummy.com' . ((substr($url,0,1) != '/')?'/':'') . $url;
			$output = parse_url( $url );
			unset( $output[ 'scheme' ] );
			unset( $output[ 'host' ] );
		} else {
			$output = parse_url( $url );
		}
		if( isset( $output[ 'scheme' ] ) ){
			$this->protocol = $output[ 'scheme' ];
		}
		if( isset( $output[ 'host' ] ) ){
			$this->domain = $output[ 'host' ];
		}
		if( !isset( $output[ 'path' ] ) || ( isset( $output[ 'path' ] ) && $output[ 'path' ] == '' ) ){
			$this->path = '/';
		} else {
			if( substr( $output[ 'path' ], -1 ) == '/' ){
				$output[ 'path' ] = substr( $output[ 'path' ], 0, strlen( $output[ 'path' ] ) - 1);
			}
			$this->path = $output[ 'path' ];
		}

		if( isset( $output[ 'query' ] ) ){
			$this->query = $output[ 'query' ];
		}
		if( isset( $output[ 'fragment' ] ) ){
			$this->fragment = $output[ 'fragment' ];
		}
	}

	/**
	 * Reconstruct the URL
	 *
	 * @param		string		$domain				[Optional] A domain for the URL, in case the parsed URL doesn't have one (default = null, i.e. use curretn domain)
	 * @param		string		$protocol			[Optional] A protocol for the reqyest, in case the parsed URL doesn't have one (default = http)
	 */
	public function toString( $domain = null, $protocol = 'http' ){
		if( $this->domain === null ){
			if( $domain === null ){
				$this->domain = $_SERVER[ 'HTTP_HOST' ];
			} else {
				$this->domain = $domain;
			}
		}
		if( $this->protocol === null ){
			$this->protocol = $protocol;
		}

		// Basic URL
		$output = $this->protocol . '://' . $this->domain . $this->path;

		// Query string
		if( $this->query !== null ){
			$output .= '?' . $this->query;
		}

		return $output;
	}
}
