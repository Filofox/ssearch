<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

/**
 * Performs spidering operations
 */
class sSearchIndexer{
	
	private $config;
	private $engine;
	
	private $robots_txt_files = array();
	
	// List of indexed URLs
	private $indexed = array();
	
	public function __construct( sSearchConfig $config, sSearchEngine $engine ){
		$this->config = $config;
		$this->engine = $engine;
	}
	
	/**
	 * Open a URL and index it
	 *
	 * @param		string		$url
	 * @param		int			$depth		[Optional]How 'deep' to follow links (default=0, i.e. don't follow links)
	 */
	public function Index( $url, $depth = 0 ){
		
		// Make sure we've got a fully-qualified URL
		$url = new sSearchURL( $url );
		$url_string = $url->toString();

		// Check robots.txt
		if( $this->CheckRobotsTxt( $url ) ){
			// Make sure it hasn't already been indexed
			if( !array_key_exists( $url_string, $this->indexed ) ){
				$this->indexed[ $url_string ] = true;
		
				// Do the request
				$content_item = $this->Fetch( $url_string );
		
				// Will return null if no valid file returned (e.g. 404)
				if( $content_item !== null && $content_item->index ){
					$this->engine->Index( $content_item );
	
					// Spider links?				
					if( $depth > 0 ){
						$depth -= 1;
						if( $links = $content_item->GetLinks() )
						{

							$wait_time = microtime( true ) + ( $this->config->indexer->index_delay );
							foreach( $links as $link ){
								$link_url = new sSearchURL( $link );
								if(
									$link_url->domain === null
									||
									( $link_url->domain == $content_item->domain )
									||
									$this->config->indexer->follow_external_links
								){
									// Wait
									@time_sleep_until( $wait_time );
									
									$this->Index( $link_url->toString( $content_item->domain, $content_item->protocol ), $depth );
									
									$wait_time = microtime( true ) + ( $this->config->indexer->index_delay );
								}
							}
						}
					}
				} else {
					if( $content_item !== null){
						// Remove from index
						$this->engine->Remove( $content_item );
					}
				}
			}
		}
	}
	
	/**
	 * Retrieves content and parses it into a content object
	 *
	 * @param		string		$url
	 *
	 * @return		sSearchContent
	 */
	public function Fetch( $url ){

		require_once( sSearch::GetPathLibrary() . '_lib/LibCurlWrapper.class.php' );
		
		$request = new LibCurlWrapper();
		$request->AddHeader( 'User-Agent', 'sSearchBot' );
		$request->SetRetrieveHeaders( true );
		$output = null;
		try{
			$request->Get( $url );
			if( $request->status == 200 ){
				switch( $request->mime_type ){
					// HTML
					case sSearch::CONTENT_TYPE_HTML:{
						if( in_array( sSearch::CONTENT_TYPE_HTML, $this->config->search_content_types ) ){
							require_once( sSearch::GetPathLibrary() . 'content_types/sSearchContentHTML.class.php' );
							$output = new sSearchContentHTML( $this->config, $url, $request->GetBody() );
						}
						break;
					}
					case sSearch::CONTENT_TYPE_PDF:{
						if( in_array( sSearch::CONTENT_TYPE_PDF, $this->config->search_content_types ) ){
							require_once( sSearch::GetPathLibrary() . 'content_types/sSearchContentPDF.class.php' );
							$output = new sSearchContentPDF( $this->config, $url, $request->GetBody() );
						}
						break;
					}
					default:{
						// Unsupported content type -- do nothing
					}
				}
			}
		} catch( Exception $e ){
		}
		
		return $output;
	}
	
	/**
	 * Check that a URL is allowed by robots.txt
	 *
	 * Apadted from code found here: http://www.the-art-of-web.com/php/parse-robots/
	 * Original PHP code by Chirp Internet: www.chirp.com.au
	 * Please acknowledge use of this code by including this header.
	 *
	 * @param		sSearchURL		$url
	 *
	 * @return		boolean
	 */
	private function CheckRobotsTxt( sSearchURL $url ){
		
		// Have we fetched it yet?
		if( !isset( $this->robots_txt_files[ $url->domain ] ) ){
			// No, get it
			require_once( sSearch::GetPathLibrary() . '_lib/LibCurlWrapper.class.php' );
			$request = new LibCurlWrapper();
			$request->AddHeader( 'User-Agent', 'sSearchBot' );
			$request->SetRetrieveHeaders( true );
			
			try{
				$request->Get( $url->protocol . '://' . $url->domain . '/robots.txt' );

				$ruleapplies = false;
				if( $request->status == 200 ){
					$this->robots_txt_files[ $url->domain ] = array();
					foreach( explode( "\n", $request->GetBody() ) as $line) {
						# skip blank lines
						if(!$line = trim($line)) continue;
						
						# following rules only apply if User-agent matches $useragent or '*'
						if( preg_match('/User-agent: (.*)/i', $line, $match) ) {
							$ruleapplies = preg_match("/(\*|" . $this->config->user_agent_string . ")/i", $match[1]);
						}
						if( $ruleapplies && preg_match('/Disallow:(.*)/i', $line, $regs)) {
							# an empty rule implies full access - no further tests required
							if(!$regs[1]){
								$this->robots_txt_files[ $url->domain ][] = true;
							} else {
								# add rules that apply to array for testing
								$this->robots_txt_files[ $url->domain ][] = preg_quote(trim($regs[1]), '/');
							}
						}
					}
				} else {
					$this->robots_txt_files[ $url->domain ] = false;
				}
			} catch( Exception $e ){
				$this->robots_txt_files[ $url->domain ] = false;
			}
		}
		
		if( $this->robots_txt_files[ $url->domain ] === false ){
			return true;
		} else {
			foreach($this->robots_txt_files[ $url->domain ] as $rule) {
				# check if page is disallowed to us
				if( $rule === true || preg_match( "/^$rule/", $url->path) ){
					return false;
				}
			}
		}

		return true;
	}
	
	
}

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
			$this->path = $output[ 'path' ];
		}
		if( isset( $output[ 'query' ] ) ){
			$this->domain = $output[ 'query' ];
		}
		if( isset( $output[ 'fragment' ] ) ){
			$this->domain = $output[ 'fragment' ];
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

class sSearchResponseHeaders{

	function __construct( $response_headers ){

		foreach( $response_headers as $header ){
			if( strpos( $header, ':' ) === false ){
				$this->status = $header;
			} else {
				list( $name, $value ) = explode( ": ", $header );
				
				// PHP doesn't like hyphens in property names
				$name = strtolower( str_replace( '-', '_', $name ) );
				
				// Parse mime type (and chaset, if present) from content type
				if( $name == 'content_type' ){
					preg_match( '@([-\w/+]+)(;\s+charset=(\S+))?@i', $value, $matches );
					$this->mime_type = $matches[1];
					// Charset?
					if( isset( $matches[ 3 ] ) ){
						$this->charset = $matches[3];
					}
				}
				$this->$name = $value;
			}
		}
	}
}

/**
		// Be friendly -- at least give the site some info
		$opts = array(
		  'http'=>array(
			'method'=>"GET",
			'header'=>
				"User-Agent: sSearchBot\r\n"
		  )
		);
		$context = stream_context_create( $opts );

		$content = file_get_contents( $url, false, $context );
		
		$response_headers = new sSearchResponseHeaders( $http_response_header  );
*/