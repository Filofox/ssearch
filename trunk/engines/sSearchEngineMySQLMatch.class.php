<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

require_once( sSearch::GetPathLibrary() . 'core/sSearchEngine.class.php' );

/**
 * Use MySQL's own MATCH() method
 */
class sSearchEngineMySQLMatch extends sSearchEngine{

	const table_content = 'content';

	public function __construct( $config ){
		parent::__construct( $config );
		$this->db = mysql_connect
		(
			$this->config->database->host,
			$this->config->database->user,
			$this->config->database->password
		);
		mysql_select_db( $this->config->database->name );
	}

	/**
	 * Add (or update) item in the index
	 *
	 * @param		sSearchContent		$content
	 */
	public function Index( sSearchContent $content ){

		// Simply store the text in the database
		$sql = "
			INSERT INTO " . $this->config->database->table_prefix . "content
			(
				uid,
				mime_type,
				title,
				url,
				protocol,
				domain,
				content,
				date
			)
			VALUES
			(
				'" . md5( $content->url ) . "',
				'" . mysql_real_escape_string( $content->mime_type, $this->db ) . "',
				'" . mysql_real_escape_string( $content->title, $this->db ) . "',
				'" . mysql_real_escape_string( $content->url, $this->db ) . "',
				'" . mysql_real_escape_string( $content->protocol, $this->db ) . "',
				'" . mysql_real_escape_string( $content->domain, $this->db ) . "',
				'" . mysql_real_escape_string( $content->content, $this->db ) . "',
				NOW()
			)
			ON DUPLICATE KEY UPDATE
				mime_type = '" . mysql_real_escape_string( $content->mime_type, $this->db ) . "',
				title = '" . mysql_real_escape_string( $content->title, $this->db ) . "',
				url = '" . mysql_real_escape_string( $content->url, $this->db ) . "',
				protocol = '" . mysql_real_escape_string( $content->protocol, $this->db ) . "',
				domain = '" . mysql_real_escape_string( $content->domain, $this->db ) . "',
				content = '" . mysql_real_escape_string( $content->content, $this->db ) . "',
				date = NOW()
		";

		mysql_query( $sql, $this->db ) or die( mysql_error() );
	}

	/**
	 * ARemove a URL from the index
	 *
	 * @param		sSearchContent		$content
	 */
	public function Remove( sSearchContent $content ){
		$sql = "
			DELETE FROM " . $this->config->database->table_prefix . "content
			WHERE
				'" . md5( $content->url ) . "'
		";
		mysql_query( $sql, $this->db ) or die( mysql_error() );
	}

	/**
	 * Search the database
	 */
	public function Search( sSearchQuery $query ){

		// Simply store the text in the database
		$sql = "
			SELECT SQL_CALC_FOUND_ROWS DISTINCT
				*,
				MATCH ( content )
					AGAINST ( '" . mysql_real_escape_string( $query->terms, $this->db ) . "' IN BOOLEAN MODE ) AS score
			FROM " . $this->config->database->table_prefix . "content
			WHERE
				MATCH ( content )
				AGAINST ( '" . mysql_real_escape_string( $query->terms, $this->db ) . "' IN BOOLEAN MODE )
			ORDER BY score DESC
			LIMIT " . $query->start . "," . $query->max . "
		";

		$db_query = mysql_query( $sql, $this->db ) or die( mysql_error() );
		if( mysql_num_rows( $db_query ) > 0 ){
			while( $row = mysql_fetch_object( $db_query ) ){
				$result = new sSearchResult();
				$result->url = $row->url;
				$result->title = $row->title;
						
				$snippets = array();
				// Split terms
				$terms = explode( ' ', $query->terms );

				$num_terms = min( $this->config->result_max_terms_in_snippet, count( $terms ) );

				for( $i = 0; $i < $num_terms; $i++ ){
					$term = $terms[ $i ];
					$margin = max( 0, floor( ( ( $this->config->result_snippet_length - strlen( $term ) ) / $num_terms ) / 2 ) );
					if( preg_match( '~\b(.{0,' . $margin . '}' . preg_quote( $term, '~' ) . '.{0,' . $margin . '})\b~i', $row->content, $matches ) ){
						$text = trim( $matches[ 1 ] );
						$snippets[] = array(
							'pos' => strpos( $row->content, $text ),
							'term' => $term,
							'text' => $text,
							'text_highlighted' => preg_replace( '~(' . preg_quote( $term, '~' ) . ')~Ui', $this->config->result_snippet_highlight_start . '\1' . $this->config->result_snippet_highlight_end, $text )
						);
					}
				}
				// Sort by position in original string
				usort( $snippets, array( $this, 'SortSnippets' ) );
				
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
								$snippet_output .= $this->config->result_snippet_join_string . $snippet[ 'text' ];
								$snippet_highlighted_output .= $this->config->result_snippet_join_string . $snippet[ 'text_highlighted' ];
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

				$query->AddResult( $result );
			}
		}
		
		// Get total results count
		$db_query = mysql_query( 'SELECT FOUND_ROWS() AS count', $this->db ) or die( mysql_error() );
		$query->total = mysql_fetch_object( $db_query )->count;
		

	}
	
	static function SortSnippets( $a, $b ){
		if ( $a[ 'pos' ] == $b[ 'pos' ] ) {
			return 0;
		}
		return ( $a[ 'pos' ] < $b[ 'pos' ] ) ? -1 : 1;
	}

	/**
	 * Do whatever is needed to install this engine
	 */
	public function Install(){

	}
}