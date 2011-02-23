<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

/**
 * THIS IS THE DEFAULT CONFIGURATION FILE
 * DO NOT EDIT THE CONTENTS OF THIS FILE
 * ANY CHANGES SHOULD BE MADE IN THE LOCAL CONFIG FILE
 */

$config[ 'user_agent_string' ] = 'sSearchBot';
$config[ 'engine' ] = 'MySQLMatch';//'YahooBoss';//'MicrosoftBing';//'GoogleCustomSearch';//'MySQLMatch';

$config[ 'result_max_terms_in_snippet' ] = 3; // Maximum number of separate terms to search for when creating snippet
$config[ 'result_snippet_length' ] = 200; // In characters
$config[ 'result_snippet_highlight_start' ] = '<b>'; // Marker for start of highlighted term in search
$config[ 'result_snippet_highlight_end' ] = '</b>'; // Marker for end of highlighted term in search
$config[ 'result_snippet_join_string' ] = '...'; // Text used to join non-contiguous snippets

// Search these content types
$config[ 'search_content_types' ] = array
	(
		sSearch::CONTENT_TYPE_HTML,
		sSearch::CONTENT_TYPE_PDF
	);

$config[ 'indexer.minimum_word_length' ] = 3;
$config[ 'indexer.follow_external_links' ] = false;
$config[ 'indexer.index_delay' ] = 0.5; // Delay between consecutive requests, in seconds

$config[ 'indexer.exclude_by_marker' ] = true;
$config[ 'indexer.exclude_marker_open' ] = '<!--NOINDEX-->';
$config[ 'indexer.exclude_marker_close' ] = '<!--/NOINDEX-->';
$config[ 'indexer.include_by_marker' ] = false;
$config[ 'indexer.include_marker_open' ] = '<!--search_include-->';
$config[ 'indexer.include_marker_close' ] = '<!--/search_include-->';

// If no title is set in <title> or <h1> of HTML content, use first n words of body
$config[ 'content.title_word_length' ] = 10;

/**
 * Database
 */
// Connection details
$config[ 'database.host' ] 		= '';
$config[ 'database.user' ] 		= '';
$config[ 'database.password' ] 	= '';

$config[ 'database.name' ] 	= 'ssearch';

// Table name prefix
$config[ 'database.table_prefix' ] = 'ssearch_';


// Google custom search
$config[ 'engine_googlecustomsearch.url' ] = 'https://www.googleapis.com/customsearch/v1?';
$config[ 'engine_googlecustomsearch.api_key' ] = '';
$config[ 'engine_googlecustomsearch.search_engine_id' ] = '';

// Bing search API
$config[ 'engine_microsoftbing.url' ] = 'http://api.search.live.net/json.aspx?';
$config[ 'engine_microsoftbing.app_id' ] = '';

// Yahoo BOSS
$config[ 'engine_yahooboss.url' ] = 'http://boss.yahooapis.com/ysearch/web/v1/';
$config[ 'engine_yahooboss.app_id' ] = '';