<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

/**
 * Core search functions
 */
class sSearch{

	private $config;
	private $indexer;
	private $engine;

	const CONTENT_TYPE_HTML = 'text/html';
	const CONTENT_TYPE_PDF = 'application/pdf';

	const OUTPUT_JSON = 'json';
	const OUTPUT_XML = 'xml';

	/**
	 * Constructor
	 */
	public function __construct( $config_path = null ){
		$this->LoadConfig( $config_path );
		$this->LoadEngine();
	}

	/**
	 * Initiate the index process
	 *
	 * @param		string		$url
	 * @param		int			$depth		[Optional]How 'deep' to follow links (default=0, i.e. don't follow links)
	 */
	public function Index( $url, $depth = 0 ){
		$this->LoadIndexer();

		$this->indexer->Index( $url, $depth );
	}

	/**
	 * Search by passing a query object. Allows greater flexibility than Search()
	 *
	 * @param		sSearchQuery		$query
	 */
	public function Query( sSearchQuery $query ){
		$this->engine->Search( $query );
	}

	/**
	 * Shortcut to doing a search query with Query()...
	 * just pass in query string and basic settings
	 * and get a search query object back
	 *
	 * @param		string		$query_string
	 * @param		int			$start				[Optional] Where to start with result set (default = 0). Set to false to return all results (MySQLMatch only)
	 * @param		int			$max				[Optional] Maximum number of results to return (default = null, i.e. use default from config)
	 *
	 * @return		mixed							Varies according to search_output_type config setting (object, JSON or XML)
	 */
	public function Search( $query_string, $start = 0, $max = null ){

		require_once( sSearch::GetPathLibrary() . 'core/sSearchQuery.class.php' );
		$query = new sSearchQuery();

		$query->terms = $query_string;
		$query->start = $start;
		if( $max !== null ){
			$query->max = $max;
		} else {
			$query->max = $this->config->query->default_max_results;
		}

		$this->Query( $query );

		return $query;
	}

	/**
	 * Remove a URl from the index
	 */
	public function RemoveURL( $url ){
		require_once( dirname( __FILE__ ) . '/core/sSearchURL.class.php' );
		$url = new sSearchURL( $url );
		$url_string = $url->toString();
		if( $this->config->indexer->add_trailing_slash && substr( $url_string, -1 ) != '/' ){
			$url_string .= '/';
		}
		if( $this->config->indexer->remove_trailing_slash && substr( $url_string, -1 ) == '/' ){
			$url_string = substr( $url_string, 0, -1 );
		}

		$this->engine->RemoveURL( $url_string );
	}

	/**
	 * Completely clear the index
	 */
	public function ClearIndex(){
		$this->engine->ClearIndex();
	}

	/**
	 * Load a custom indexer
	 */
	public function SetIndexer( sSearchIndexer $indexer ){
		$this->indexer = $indexer;
	}

	public function GetConfig(){
		return $this->config;
	}
	public function GetEngine(){
		return $this->engine;
	}

	/**
	 * Load and initiate the indexer
	 */
	private function LoadIndexer(){
		if( $this->indexer === null ){
			require_once( $this->GetPathLibrary() . 'core/sSearchIndexer.class.php' );
			$this->indexer = new sSearchIndexer( $this->config, $this->engine );
		}
	}

	/**
	 * Load and initiate the indexer
	 */
	private function LoadEngine(){
		if( $this->engine === null ){
			// Load the relevant engine class
			$engine = 'sSearchEngine' . $this->config->engine;
			require_once( sSearch::GetPathLibrary() . 'engines/' . $engine . '.class.php' );
			$this->engine = new $engine( $this->config );
		}
	}

	/**
	 * Loads the specified config file
	 *
	 * @param		string		$local_config_path
	 */
	private function LoadConfig( $local_config_path ){
		$config = array();

		$this->config = new sSearchConfig();

		// Load default config
		$pathinfo = pathinfo( __FILE__ );
		$default_config_path = $pathinfo[ 'dirname' ] . '/config.default.php';
		require( $default_config_path );
		foreach( $config as $property => $value ){
			$this->config->$property = $value;
		}

		// Load local config
		if( $local_config_path === null ){
			$local_config_path = $pathinfo[ 'dirname' ] . '/config.local.php';
		}
		if( !file_exists( $local_config_path ) ){
			throw new Exception( 'Local config file not found [' . $local_config_path . ']' );
		}
		require( $local_config_path );
		foreach( $config as $property => $value ){
			$this->config->$property = $value;
		}

		// Load stopwords
		$this->config->stopwords = explode( ',', file_get_contents(self::GetPathLibrary() . 'stopwords.txt') );
	}

	/**
	 * Get the base path for sSearch library files
	 *
	 * @return		string
	 */
	public static function GetPathLibrary(){

		static $path;

		if( empty( $path ) ){
			$path_info = pathinfo( __FILE__ );
			$path = realpath( $path_info[ 'dirname' ] ) . '/';
		}

		return $path;
	}

	public static function Snippet( $result, $terms, $content, $snippet_length, $max_terms, $highlight_start, $highlight_end, $join_string ){
		$snippets = array();
		// Split terms
		$terms = explode( ' ', $terms );

		$num_terms = min( $max_terms, count( $terms ) );

		for( $i = 0; $i < $num_terms; $i++ ){
			$term = $terms[ $i ];
			$margin = max( 0, floor( ( ( $snippet_length - strlen( $term ) ) / $num_terms ) / 2 ) );
			if( preg_match( '~\b(.{0,' . $margin . '}' . preg_quote( $term, '~' ) . '.{0,' . $margin . '})\b~i', $content, $matches ) ){
				$text = trim( $matches[ 1 ] );
				$snippets[] = array(
					'pos' => strpos( $content, $text ),
					'term' => $term,
					'text' => $text,
					'text_highlighted' => preg_replace( '~(' . preg_quote( $term, '~' ) . ')~Ui', $highlight_start . '\1' . $highlight_end, $text )
				);
			}
		}
		// Sort by position in original string
		usort( $snippets, array( 'self', 'SortSnippets' ) );

		if( count( $snippets ) > 1 ){
			$indices = array_keys( $snippets );
			$snippet_output = '';
			$snippet_highlighted_output = '';
			$previous_snippet = false;
			for( $i = 0; $i < count( $snippets ); $i++ ){
				$snippet = $snippets[ $i ];
				if( $previous_snippet !== false  ){
					if( $snippet[ 'pos' ] <= $previous_snippet[ 'pos' ] + strlen( $previous_snippet[ 'text' ] ) ){
						$snippet_output = substr( $snippet_output, 0, strripos( $previous_snippet[ 'text' ], $snippet[ 'term' ] ) );
						$snippet_highlighted_output = substr( $snippet_highlighted_output, 0, strripos( $previous_snippet[ 'text_highlighted' ], $snippet[ 'term' ] ) );

						$snippet_output .= substr( $snippet[ 'text' ], stripos( $snippet[ 'text' ], $snippet[ 'term' ] ) );
						$snippet_highlighted_output .= substr( $snippet[ 'text_highlighted' ], stripos( $snippet[ 'text' ], $snippet[ 'term' ] ) );
					} else {
						$snippet_output .= $join_string . $snippet[ 'text' ];
						$snippet_highlighted_output .= $join_string . $snippet[ 'text_highlighted' ];
					}
				} else {
					// First part of snippet
					$snippet_output = $snippet[ 'text' ];
					$snippet_highlighted_output = $snippet[ 'text_highlighted' ];
				}
				$previous_snippet = $snippet;
			}
			$result->snippet = $snippet_output;
			$result->snippet_highlighted = $snippet_highlighted_output;
		} else {
			$snippet = array_pop( $snippets );
			$result->snippet = $snippet[ 'text' ];
			$result->snippet_highlighted = $snippet[ 'text_highlighted' ];
		}

	}

	public static function SortSnippets( $a, $b ){
		if ( $a[ 'pos' ] == $b[ 'pos' ] ) {
			return 0;
		}
		return ( $a[ 'pos' ] < $b[ 'pos' ] ) ? -1 : 1;
	}


}

/**
 * Config container class
 */
class sSearchConfig{
	private $namespaces;
	private $default_namespace = '__default';

	public function __construct(){
		$this->namespaces = new stdClass();
		$this->AddNamespace( $this->default_namespace );
	}

	/**
	 * Add a new property namespace
	 *
	 * @param		string		$namespace
	 */
	public function AddNamespace( $namespace ){
		// Check  it's not already a property name
		if( isset( $this->namespaces->{$this->default_namespace}->$namespace ) ){
			throw new Exception( "Cannot create namespace with same name as existing property ( $namespace )" );
		}
		// Create namespace
		if( !isset( $this->namespaces->$namespace ) ){
			$this->namespaces->$namespace = new sSearchConfigNamespace( $namespace );
		}
	}

	/**
	 * Set a property. This only handles settings properties in the default namespace
	 *
	 * @param		string		$property
	 * @param		mixed		$value
	 */
	public function __set( $property, $value ){
		$namespace = $this->default_namespace;
		if( strpos( $property, '.' ) !== false ){
			list( $namespace, $property ) = explode( '.', $property );
		} else {
			if( isset( $this->namespaces->$property ) ){
				throw new Exception( "Cannot set property with same name as existing namespace ( $property )" );
			}
		}
		if( !isset( $this->namespaces->$namespace ) ){
			$this->AddNamespace( $namespace );
		}
		$this->namespaces->$namespace->$property = $value;
	}

	/**
	 * Retrieve a property. This is always called because there's no direct access to config properties
	 *
	 * @param		string		$property
	 *
	 * @return		mixed
	 */
	public function __get( $property ){
		// Is it a namespace?
		if( isset( $this->namespaces->$property ) ){
			return $this->namespaces->$property;
		} else {
			// Is it a default property?
			return $this->namespaces->{$this->default_namespace}->$property;
		}
	}
}
/**
 * Container for config properties
 */
class sSearchConfigNamespace{

	private $name;
	private $properties;

	public function __construct( $name ){
		$this->name = $name;
		$this->properties = new stdClass();
	}

	public function __set( $property, $value ){
		$this->properties->$property = $value;
	}

	/**
	 * Retrieve a property value
	 *
	 * @param		string		$property
	 */
	public function __get( $property ){
		if( isset( $this->properties->$property ) ){
			return $this->properties->$property;
		} else {
			throw new Exception( "Config property " . $this->name . ".$property is not set." );
		}
	}
}
