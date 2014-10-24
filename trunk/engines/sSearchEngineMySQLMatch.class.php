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
	
	protected $mysql_lib = self::MYSQL_LIBRARY_MYSQL;
	
	const MYSQL_LIBRARY_MYSQL = 0;
	const MYSQL_LIBRARY_MYSQLI = 1;

	public function __construct( $config ){

		$this->mysql_lib = (function_exists('mysqli_connect'))?self::MYSQL_LIBRARY_MYSQLI:self::MYSQL_LIBRARY_MYSQL;

		parent::__construct( $config );
		if( $this->mysql_lib == self::MYSQL_LIBRARY_MYSQL ){
			$this->db = mysql_connect
			(
				$this->config->database->host,
				$this->config->database->user,
				$this->config->database->password
			);
			mysql_select_db( $this->config->database->name, $this->db );
		} else {
			$this->db = mysqli_connect
			(
				$this->config->database->host,
				$this->config->database->user,
				$this->config->database->password
			);
			mysqli_select_db( $this->db, $this->config->database->name );
		}
	}

	protected function Escape( $string ){
		if( $this->mysql_lib == self::MYSQL_LIBRARY_MYSQL ){
			return mysql_real_escape_string( $string, $this->db );
		} else {
			return mysqli_real_escape_string( $this->db, $string );
		}
	}
	
	protected function Query( $sql ){
		if( $this->mysql_lib == self::MYSQL_LIBRARY_MYSQL ){
			return mysql_query( $sql, $this->db ) or die( mysql_error() );
		} else {
			return mysqli_query( $this->db, $sql ) or die( mysql_error() );			
		}
	}
	protected function NumRows( $query ){
		if( $this->mysql_lib == self::MYSQL_LIBRARY_MYSQL ){
			return mysql_num_rows( $db_query );
		} else {
			return mysqli_num_rows( $db_query );			
		}
	}
	protected function Fetch( $db_query ){
		if( $this->mysql_lib == self::MYSQL_LIBRARY_MYSQL ){
			return mysql_fetch_object( $db_query );
		} else {
			return mysqli_fetch_object( $db_query );
		}
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
				'" . $this->Escape( $content->mime_type ) . "',
				'" . $this->Escape( $content->title ) . "',
				'" . $this->Escape( $content->url ) . "',
				'" . $this->Escape( $content->protocol ) . "',
				'" . $this->Escape( $content->domain ) . "',
				'" . $this->Escape( $content->content ) . "',
				NOW()
			)
			ON DUPLICATE KEY UPDATE
				mime_type = '" . $this->Escape( $content->mime_type ) . "',
				title = '" . $this->Escape( $content->title ) . "',
				url = '" . $this->Escape( $content->url ) . "',
				protocol = '" . $this->Escape( $content->protocol ) . "',
				domain = '" . $this->Escape( $content->domain ) . "',
				content = '" . $this->Escape( $content->content ) . "',
				date = NOW()
		";

		$this->Query( $sql );
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
		$this->Query( $sql );
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
		$this->Query( $sql );
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

		$db_query = $this->Query( $sql );
		$num_rows = $this->NuwRows( $db_query );
		if( $num_rows > 0 ){
			while( $row = $this->Fetch( $db_query ) ){
				$result = new sSearchResult();
				$result->url = $row->url;
				$result->title = $row->title;
				sSearch::Snippet( $result, $query->terms, $row->content, $this->config->result_snippet_length, $this->config->result_max_terms_in_snippet, $this->config->result_snippet_highlight_start, $this->config->result_snippet_highlight_end, $this->config->result_snippet_join_string );

				$query->AddResult( $result );
			}
		}

		// Get total results count
		$db_query = $this->Query( 'SELECT FOUND_ROWS() AS count' );
		$query->total = $this->Fetch( $db_query )->count;
	}

	/**
	 * Completely clear the index
	 */
	public function ClearIndex(){
		$sql = "
			DELETE FROM " . $this->config->database->table_prefix . $this->config->database->table . "
		";
		$this->Query( $sql );
	}

	/**
	 * Do whatever is needed to install this engine
	 */
	public function Install(){

	}
}
