<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

/**
 * Base class for search engine wrapper
 */
class sSearchEngine{
	
	protected $config;
	
	public function __construct( $config ){
		$this->config = $config;
	}
	
	public function Index( sSearchContent $content ){
		throw new Exception( 'You must override this method' );
	}
	public function Remove( sSearchContent $content ){
		throw new Exception( 'You must override this method' );
	}
	public function RemoveURL( $url ){
		throw new Exception( 'You must override this method' );
	}
}