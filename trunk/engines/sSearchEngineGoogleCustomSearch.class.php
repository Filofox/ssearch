<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

require_once( sSearch::GetPathLibrary() . 'core/sSearchEngine.class.php' );

/**
 * Use Google Custom Search
 */
class sSearchEngineGoogleCustomSearch extends sSearchEngine{

	public function __construct( $config ){
		parent::__construct( $config );
	}

	/**
	 * Add (or update) item in the index
	 */
	public function Index( sSearchContent $content ){
		// Does nothing because Google does its own indexing
	}

	/**
	 * Search Google
	 *
	 * @param		sSearchQuery		$query
	 */
	public function Search( sSearchQuery $query ){

		$params = array
		(
			'key'	=> $this->config->engine_googlecustomsearch->api_key,
			'cx'	=> $this->config->engine_googlecustomsearch->search_engine_id,
			'q'		=> $query->query,
			'num'	=> $max = min( max( $query->max, 1 ), 10 ), // Check max value -- values between 1 and 10
			'start' => $query->start + 1 // Results are 1-indexed
		);

		if( $query->domain != null ){
			$params[ 'q' ] = 'site:' . $query->domain . ' ' . $params[ 'q' ];
		}

		$url = $this->config->engine_googlecustomsearch->url . http_build_query( $params );

		require_once( sSearch::GetPathLibrary() . '_lib/LibCurlWrapper.class.php' );
		$request = new LibCurlWrapper();
		$request->SetRetrieveHeaders( true );
		$request->Get( $url );

		$response = $request->GetBody();

		$results = json_decode( $response );
		$query->total = $results->queries->request[0]->totalResults;

		if( $query->total > 0 ){
			foreach( $results->items as $item ){
				$result = new sSearchResult();
				$result->url = $item->link;
				$result->title = $item->title;
				$result->content = $item->snippet;
				$query->AddResult( $result );
			}
		}
	}
}
