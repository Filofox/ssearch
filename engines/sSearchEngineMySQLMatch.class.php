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
			INSERT INTO " . $this->config->database->table_prefix . $this->config->database->table . "
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
	 * Remove a content item from the index
	 *
	 * @param		sSearchContent		$content
	 */
	public function Remove( sSearchContent $content ){
		$sql = "
			DELETE FROM " . $this->config->database->table_prefix . $this->config->database->table . "
			WHERE
				'uid = " . md5( $content->url ) . "'
		";
		mysql_query( $sql, $this->db ) or die( mysql_error() );
	}

	/**
	 * Remove a URL from the index
	 *
	 * @param		string		$url
	 */
	public function RemoveURL( $url ){
		$sql = "
			DELETE FROM " . $this->config->database->table_prefix . $this->config->database->table . "
			WHERE
				uid = '" . md5( $url ) . "'
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
			FROM " . $this->config->database->table_prefix . $this->config->database->table . "
			WHERE
				MATCH ( content )
				AGAINST ( '" . mysql_real_escape_string( $query->terms, $this->db ) . "' IN BOOLEAN MODE )
			ORDER BY score DESC
		";

		// Setting start to false will return all results
		if( $query->start !== false ){
			$sql .= " LIMIT " . $query->start . "," . $query->max;
		} else {
			$query->max = null;
		}

		$db_query = mysql_query( $sql, $this->db ) or die( mysql_error() );
		if( mysql_num_rows( $db_query ) > 0 ){
			while( $row = mysql_fetch_object( $db_query ) ){
				$result = new sSearchResult();
				$result->url = $row->url;
				$result->title = $row->title;
				sSearch::Snippet( $result, $query->terms, $row->content, $this->config->result_snippet_length, $this->config->result_max_terms_in_snippet, $this->config->result_snippet_highlight_start, $this->config->result_snippet_highlight_end, $this->config->result_snippet_join_string );

				$query->AddResult( $result );
			}
		}

		// Get total results count
		$db_query = mysql_query( 'SELECT FOUND_ROWS() AS count', $this->db ) or die( mysql_error() );
		$query->total = mysql_fetch_object( $db_query )->count;
	}

	/**
	 * Completely clear the index
	 */
	public function ClearIndex(){
		$sql = "
			DELETE FROM " . $this->config->database->table_prefix . $this->config->database->table . "
		";
		mysql_query( $sql, $this->db ) or die( mysql_error() );
	}

	/**
	 * Do whatever is needed to install this engine
	 */
	public function Install(){

	}
}
