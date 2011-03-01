<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

/**
 * Class for handling indexing/searching via the command line
 *
 * sSearch - simple search library
 *
 * ssearch_cli.php -m [index|search] [args]
 *
 * -m                   Method - either 'index' or 'search'
 * -c                   Config file path (default = [use default location])
 *
 *   NB: arguments with no default values are required.
 *
 *   Indexing:
 *   -u [url string]      URL to index. Use \"quotes\" around this value.
 *   -d [number]          Depth -- how far to follow links (default = 0)
 *   0 = don't follow
 *   1 = links on first page only
 *   2 = links on first page plus immediate children
 *   ...etc.
 *
 *   Example:
 *   ssearch_cli.php -m index -u \"http:www.example.com\" -d 2
 *
 *
 *   Searching
 *   -q [string]          Query to search for. Use \"quotes\" around this value.
 *   -s [number]          Start index (default = 0)
 *   -n [number]          Number of results to return (default = 10)
 *   -f [json|xml]        Format for results, json or XML (default = json)
 *
 *   Example:
 *   ssearch_cli.php -m search -q \"example query\" -s 0 -n 10
 *
 *   Other
 *     -h                   Show this help
 */
class sSearchCLIHandler{
    
    private $method;
    
    public function __construct( $arguments ){
        
        $parameters = $this->ParseArguments( $arguments );
        
        if( isset( $parameters[ 'h' ] ) ){
            $this->ShowHelp();
            exit;
        }

        
        // Get sSearch class
        $pathinfo = pathinfo( __FILE__ );
        require_once( realpath( $pathinfo[ 'dirname' ] . '/../' ) . '/sSearch.class.php' );
        $search = new sSearch();
        
        // Method
        if( isset( $parameters[ 'm' ] ) ){
            switch( $parameters[ 'm' ] ){
                // Index
                case 'index':{
                    if( !isset( $parameters[ 'd' ] ) ){
                        $parameters[ 'd' ] = 0;
                    }
                    $search->Index( $parameters[ 'u' ], $parameters[ 'd' ] );
                    break;
                }
                // Search
                case 'search':{
                    if( !isset( $parameters[ 's' ] ) ){
                        $parameters[ 's' ] = 0;
                    }
                    if( !isset( $parameters[ 'n' ] ) ){
                        $parameters[ 'n' ] = 10;
                    }
                    if( !isset( $parameters[ 'f' ] ) ){
                        $parameters[ 'f' ] = sSearch::OUTPUT_JSON;
                    }
                    $query = $search->Search( $parameters[ 'q' ], $parameters[ 's' ], $parameters[ 'n' ] );
					
					// Output?
					switch( $parameters[ 'f' ] ){
						default:
						case sSearch::OUTPUT_JSON:
						{
							echo( $query->toJSON() );
							break;
						}
						case sSearch::OUTPUT_XML:
						{
							echo( $query->toXML() );
							break;
						}
					}

                    break;
                }
                default:{
                    // Invalid method
                    $this->ShowError( "Invalid method: must be 'search' or 'index'" );
                    exit;
                }
            }
        } else {
            // No method specified so show help
            $this->ShowError( 'You must specify a method' );
            exit;
        }
    }
    
    /**
     * Parse argv array into parameters
     *
     * @param       array
     *
     * @return      array
     */
    private function ParseArguments( $arguments ){
        
        $parameters = array();
        
        $current_argument = false;
        for( $i = 1; $i < count( $arguments ); $i++ ){
            if( substr( $arguments[ $i ], 0, 1 ) == '-' ){
                $current_argument = trim( substr( $arguments[ $i ], 1 ) );
                $parameters[ $current_argument ] = true;
            } else {
                if( $current_argument ){
                    $parameters[ $current_argument ] = $arguments[ $i ];
                }
            }
        }

        return $parameters;
    }
    
	/**
	 * Show an error
	 *
	 * @param		string		$error
	 */
    private function ShowError( $error ){
        echo( "ERROR: $error" );
    }

    /**
     * Show help text
     */
    private function ShowHelp(){
        
        if( $error ){
            echo( "ERROR: $error" );
        }
        
        echo
        (
"
sSearch - simple search library
Perform index or search operations.

ssearch_cli.php -m [index|search] [args]

  -m                   Method - either 'index' or 'search'
  -c                   Config file path (default = [use default location])

  NB: arguments with no default values are required.

  Indexing:
  -u [url string]      URL to index. Use \"quotes\" around this value.
  -d [number]          Depth -- how far to follow links (default = 0)
                         0 = don't follow
                         1 = links on first page only
                         2 = links on first page plus immediate children
                         ...etc.

  Example:
    ssearch_cli.php -m index -u \"http:www.example.com\" -d 2


  Searching
  -q [string]          Query to search for. Use \"quotes\" around this value.
  -s [number]          Start index (default = 0)
  -n [number]          Number of results to return (default = 10)
  -f [json|xml]        Format for results, json or XML (default = json)

  Example:
    ssearch_cli.php -m search -q \"example query\" -s 0 -n 10
    
  Other
  -h                   Show this help
"
        );
    }
    
}

?>