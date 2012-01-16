<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

require_once( sSearch::GetPathLibrary() . 'core/sSearchEngine.class.php' );

/**
 * Use Microsoft Bing
 */
class sSearchEngineMicrosoftBing extends sSearchEngine{

	public function __construct( $config ){
		parent::__construct( $config );
	}

	/**
	 * Add (or update) item in the index
	 */
	public function Index( sSearchContent $content ){
		// Does nothing because Bing does its own indexing
	}

	/**
	 * Search Bing
	 *
	 * @param		sSearchQuery		$query
	 */
	public function Search( sSearchQuery $query ){

		$params = array
		(
			'Appid'		=> $this->config->engine_microsoftbing->app_id,
			'sources'	=> 'web',
			'query'		=> urlencode( $query->query ),
			'web.count'	=> 	$max = min( max( $query->max, 1 ), 50 ), // Check max value -- values between 1 and 50
			'web.offset' => $query->start
		);

		if( $query->domain != null ){
			$params[ 'query' ] = 'site:' . $query->domain . ' ' . $params[ 'query' ];
		}

		$url = $this->config->engine_microsoftbing->url . http_build_query( $params );

		require_once( sSearch::GetPathLibrary() . '_lib/LibCurlWrapper.class.php' );
		$request = new LibCurlWrapper();
		$request->SetRetrieveHeaders( true );
		$request->Get( $url );

		$response = $request->GetBody();

		$results = json_decode( $response );
		$query->total = $results->SearchResponse->Web->Total;

		if( $query->total > 0 ){
			foreach( $results->SearchResponse->Web->Results as $item ){
				$result = new sSearchResult();
				$result->url = $item->Url;
				$result->title = $item->Title;
				if( isset( $item->Description ) ){
					$result->snippet = $item->Description;
				}
				$query->AddResult( $result );
			}
		}
	}
}
