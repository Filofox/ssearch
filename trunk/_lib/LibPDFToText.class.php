<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

/**
 * A class for stripping text content from a PDF file
 *
 * Originally based on code found at //http://community.livejournal.com/php/295413.html by Jonathan Beckett, 2005-05-02
 */
class LibPDFToText{

	/**
	 * Convert a file
	 *
	 * @param		string		$file_name
	 *
	 * @return		string						The content
	 */
	public function ConvertFile( $file_name ){
		return $this->Convert( file_get_contents( $file_name ) );
	}

	/**
	 * Convert a string
	 *
	 * @param		string		$data		The contents of a PDF file
	 *
	 * @return		string					The content
	 */
	public function Convert( $data ){
		// grab objects and then grab their contents (chunks)
		$a_obj = $this->getDataArray($data,"obj","endobj");
		$a_chunks = array();
		$j = 0;
		foreach($a_obj as $obj){
			$a_filter = $this->getDataArray($obj,"<<",">>");
			if (count($a_filter)){
				$j++;
				$a_chunks[$j]["filter"] = $a_filter[0];

				$a_data = $this->getDataArray($obj,"stream","endstream");
				if (count($a_data)){
					$a_chunks[$j]["data"] = substr($a_data[0],strlen("stream\r\n"),strlen($a_data[0])-strlen("stream\r\n")-strlen("endstream"));
				}
			}
		}

		// decode the chunks
		$result_data = '';
		foreach($a_chunks as $chunk){

			// look at each chunk and decide how to decode it - by looking at the contents of the filter
			$a_filter = explode("/",$chunk["filter"]);

			if (isset($chunk["data"]) && $chunk["data"]!=""){
				// look at the filter to find out which encoding has been used
				if (strpos($chunk["filter"],"FlateDecode")!==false && strpos($chunk["filter"],"Metadata") === false && strpos($chunk["filter"],"Image") === false ){
					$data =@ gzuncompress($chunk["data"]);
					if (trim($data)!=""){
						$result_data .= $this->ps2txt($data);
					} else {

						//$result_data .= "x";
					}
				}
			}
		}

		return $result_data;

	}

	/**
	 * Get a list of object from the file
	 */
	private function getDataArray($data,$start_word,$end_word){

		$start = 0;
		$end = 0;
		$a_result = array();

		while ($start!==false && $end!==false){
			$start = strpos($data,$start_word,$end);
			if ($start!==false){
				$end = strpos($data,$end_word,$start);
				if ($end!==false){
					// data is between start and end
					$a_result[] = substr($data,$start,$end-$start+strlen($end_word));
				}
			}
		}
		return $a_result;
	}


	private function ps2txt($ps_data){
		$result = "";
		$a_data = $this->getDataArray($ps_data,"[","]");
		if (is_array($a_data)){
			foreach ($a_data as $ps_text){
				$a_text = $this->getDataArray($ps_text,"(",")");
				if (is_array($a_text)){
					foreach ($a_text as $text){
				//	 $result .= substr($text,1,strlen($text)-2);
						$text = substr($text,1,strlen($text)-2);
						if( !is_numeric( $text ) ){
							@$result .= iconv(mb_detect_encoding($text), 'UTF-8//IGNORE', $text) . "[[return]]";
						}
					}
				}
			}
		} else {
			// the data may just be in raw format (outside of [] tags)
			$a_text = $this->getDataArray($ps_data,"(",")");
			if (is_array($a_text)){
				foreach ($a_text as $text){
					//	 $result .= substr($text,1,strlen($text)-2);
						$text = substr($text,1,strlen($text)-2);
						if( !is_numeric( $text ) ){
							@$result .= iconv(mb_detect_encoding($text), 'UTF-8//IGNORE', $text) . "[[return]]";
						}
				}
			}
		}

		$result = str_replace("[[return]] [[return]] [[return]]", "\n", $result );
		$result = str_replace("[[return]]", "", $result );
		return trim( $result );
	}
}

?>
