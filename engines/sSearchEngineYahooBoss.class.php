<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

require_once( sSearch::GetPathLibrary() . 'core/sSearchEngine.class.php' );

/**
 * Use Yahoo! Boss
 */
class sSearchEngineYahooBoss extends sSearchEngine{

	public function __construct( $config ){
		parent::__construct( $config );
	}

	/**
	 * Add (or update) item in the index
	 */
	public function Index( sSearchContent $content ){
		// Does nothing because Yahoo! does its own indexing
	}

	/**
	 * Search Yahoo!
	 *
	 * @param		sSearchQuery		$query
	 */
	public function Search( sSearchQuery $query ){

		$params = array
		(
			'appid'	=> $this->config->engine_yahooboss->app_id,
			'start' => $query->start,
			'count'	=> min( max( $query->max, 1 ), 50 ), // Check max value -- values between 1 and 50
		);
		
		if( $query->domain != null ){
			$params[ 'sites' ] = $query->domain;
		}

		$url = $this->config->engine_yahooboss->url . urlencode( $query->query ) . '?' . http_build_query( $params );

		require_once( sSearch::GetPathLibrary() . '_lib/LibCurlWrapper.class.php' );
		$request = new LibCurlWrapper();
		$request->SetRetrieveHeaders( true );
		$request->Get( $url );

		$response = $request->GetBody();
		$results = json_decode( $response );

		$query->total = $results->ysearchresponse->totalhits;

		if( $query->total > 0 ){
			foreach( $results->ysearchresponse->resultset_web as $item ){
				$result = new sSearchResult();
				$result->url = $item->url;
				$result->title = $item->title;
				$result->content = $item->abstract;
				$query->AddResult( $result );
			}
		}
	}
}