<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */

/**
 * Perform an HTTP request using cURL
 *
 * @author Pat Fox
 */
class LibCurlWrapper{

	/** @var		string		Default request method **/
	private $method = 'GET';
	/** @var		array		Supported request methods **/
	private $allowed_methods = array( 'GET', 'POST' );
	/** @var		boolean		Whether or not to retrieve headers with request **/
	private $fetch_headers = false;
	private $cookies_send = false;
	private $cookies;

	public $status;
	public $mime_type;
	public $charset;

	private $headers = array( 'Expect:' );

	/**
	 * Make a cURL request
	 *
	 * @param		string		$url		The URL to request
	 * @param		array		$fields		[Optional] Any fields to pass on request [default = none]
	 *
	 * @return		string					The result of the request
	 */
	public function Get( $url, $fields = false ){

		// Start cURL process
		$curl_handle = curl_init();

		if( $fields ){
			// Encode any data
			$fields = $this->ArrayUrlEncode( $fields );
		}

		// Set options for different request methods
		switch( $this->method ){
			case 'POST':{
				curl_setopt( $curl_handle, CURLOPT_POST, true );
				if( $fields ){
					curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $fields );
				}
				break;
			}
			case 'GET':{
				if( $fields ){
					$url = $url . '?' . $fields;
				}
				break;
			}
		}

		if( $this->cookies_send ){
			$this->SetRetrieveHeaders( true );
			curl_setopt( $curl_handle, CURLOPT_COOKIE, $this->GetCookieString() );
		}

		// Get header
		curl_setopt( $curl_handle, CURLOPT_HEADER, $this->fetch_headers );

		// What to get
		curl_setopt( $curl_handle, CURLOPT_URL, $url );

		// Store output (ie. don't just dump it to screen)
		curl_setopt ( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );

		// Disable checking Peers SSL Cert - needed for self signed certs
		curl_setopt ( $curl_handle, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt ( $curl_handle, CURLOPT_SSL_VERIFYHOST, 0 );

		// This clears the 'Expect:' header (automatically set by cURL) so that Apache doesn't send additional 100 Continue header before response
		curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, $this->headers );

		// Do it
		$response = curl_exec ( $curl_handle );

		// Was there an error?
		if( $error = curl_errno( $curl_handle ) ){

			$error_string = curl_error( $curl_handle );
			// End of cURL process
			curl_close ( $curl_handle );

			throw new Exception( "Curl error connecting to $url [$error]: $error_string" );
		} else {
			// End of cURL process
			curl_close ( $curl_handle );

			// Get headers?
			if( $this->fetch_headers ){
				// Split into headers and body
				$this->ParseHeaders( $response );
			} else {
				// Just return the whole thing
				$this->body = $response;
			}
		}

		return true;
	}

	public function AddHeader( $name, $value ){
		$this->headers[] = "$name:$value";
	}

	/**
	 * Use POST method for request
	 */
	public function SetMethodPost(){
		$this->method = 'POST';
	}
	/**
	 * Use GET method for request
	 */
	public function SetMethodGet(){
		$this->method = 'GET';
	}

	/**
	 * Set any cookies to send on request
	 *
	 * @param		array		$cookies		List of cookies to send as key/value pairs
	 */
	public function SetCookies( $cookies ){
		$this->cookies_send = true;
		$this->cookies = $cookies;

	}
	/**
	 * Get any cookies returned by request
	 *
	 * @return		array		List of cookies by name, array with all available details
	 */
	public function GetCookies(){
		$output = array();

		// Get all headers
		if( $headers = $this->GetHeaders() ){
			// Any cookies?
			if( isset( $headers[ 'Set-Cookie' ] ) ){
				$cookies = $headers[ 'Set-Cookie' ];
				// If there's only one, it'll be a string so convert it into an array to make things easier
				if( !is_array( $cookies ) ) {
					$cookies = array( $cookies );
				}
				foreach( $cookies as $cookie ){
					// Split into parts
					$cookie = explode( ';', $cookie );
					// Get name/value
					preg_match( '~([^=]*)=(.*)~', $cookie[0], $matches );
					$key = trim( $matches[ 1 ] );
					$output[ $key ] = array(
						'value'		=> $matches[ 2 ],
						'expires'	=> false,
						'domain'	=> false,
						'path'		=> false,
						'secure'	=> false
					);
					// Set all other values sent
					for( $i = 1; $i < count( $cookie ); $i++ ){
						preg_match( '~([^=]*)=(.*)~', $cookie[ $i ], $matches );
						$output[ $key ][ strtolower( trim( $matches[ 1 ] ) ) ] = $matches[ 2 ];
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Get all cookies formatted as a string for sending
	 *
	 * @return		string		A string with all cookies
	 */
	private function GetCookieString(){
		$cookies = array();
		foreach( $this->cookies as $cookie => $value ){
			$cookies[] = "$cookie=$value";
		}
		return implode( ';', $cookies );
	}

	/**
	 * Retrieve headers?
	 *
	 * @param		boolean
	 */
	public function SetRetrieveHeaders( $setting ){
		$this->fetch_headers = $setting;
	}

	/**
	 * Get headers that were returned (assumes that setRetrieveHeaders() was set to true
	 *
	 * @return		array		Associative array of headers with their values.
	 *							Response line is split into protocol, version, status, reason
	 */
	public function GetHeaders(){
		return $this->headers;
	}
	/**
	 * Get body of returned data
	 *
	 * @return		string
	 */
	public function GetBody(){
		return $this->body;
	}
	/**
	 * Takes an array and converts it to a URL-encoded string (which will unpack as an array). Works with nested arrays.
	 *
	 * @param		array		$field_value		What to encode
	 * @param		string		$field_name			[Optional] A field name. This is really for recursion, so shouldn't be required on first call
	 *
	 * @return		string							The data in url-encoded format
	 */
	private function ArrayUrlEncode( $field_value, $field_name = false ) {

		$fields = array();

		foreach ( $field_value as $key => $value ) {

			// Recurse arrays
			if ( is_array($value) ) {
				// Is this already an array element?
				if( $field_name ){
					// Yes
					$fields[] = $this->ArrayUrlEncode( $value, "{$field_name}[{$key}]"  );
				} else {
					// No
					$fields[] = $this->ArrayUrlEncode( $value, "{$key}"  );
				}
			} elseif( is_object( $value ) ) {
				// Is this already an array element?
				$value = serialize( $value );
				if( $field_name ){
					// Yes
					$fields[] = "{$field_name}[{$key}]=" . urlencode($value);
				} else {
					// No
					$fields[] = "{$key}=" . urlencode($value);
				}
			} else {
				// Is this already an array element?
				if( $field_name ){
					// Yes
					$fields[] = "{$field_name}[{$key}]=" . urlencode($value);
				} else {
					// No
					$fields[] = "{$key}=" . urlencode($value);
				}
			}
		}

		// Stick it all together
		return implode( '&', $fields );
	}

	/**
	 * Take raw HTTP header string and parse it into some sort of useful form
	 *
	 * @param		string		$raw_data		What was returned by the cURL request
	 *
	 * @return		array						A structured array with response code (split into protocol, version, status and reason) and key/value pairs for other headers
	 */
	private function ParseHeaders( $raw_data ){

		// Split into header and body
		$split_by  ="\r\n\r\n";
		$parts = explode( $split_by, $raw_data );
		$headers = array_shift( $parts );
		$this->body = implode( $split_by, $parts );

		// Parse headers
		$this->headers = array();
		$response = preg_match_all( '~([^\n]*)\n^([^:]*):(.*)$~m', $headers, $matches );

		// Break up response header
		list( $this->headers[ 'response' ][ 'version' ], $this->headers[ 'response' ][ 'status' ], $this->headers[ 'response' ][ 'reason' ] ) = explode( ' ', $matches[ 1 ][ 0 ] );
		list( $this->headers[ 'response' ][ 'protocol' ], $this->headers[ 'response' ][ 'version' ] ) = explode( '/', $this->headers[ 'response' ][ 'version' ] );

		$this->status = (int)$this->headers[ 'response' ][ 'status' ];

		// Header keys/values
		foreach( $matches[ 2 ] as $index => $key ){
			$key = trim( $key );
			$value = trim( $matches[ 3 ][ $index ] );

			// Parse mime type (and chaset, if present) from content type
			if( $key == 'Content-Type' ){
				preg_match( '@([-\w/+]+)(;\s+charset=(\S+))?@i', $value, $content_type_matches );
				$this->mime_type = $content_type_matches[1];
				// Charset?
				if( isset( $content_type_matches[ 3 ] ) ){
					$this->charset = $content_type_matches[3];
				}
			}

			// Checks if there's more than one header with this name
			if( isset( $this->headers[ $key ] ) ){
				// Yes there is, so convert this to an array
				if( !is_array( $this->headers[ $key ] ) ){
					$this->headers[ $key ] = array( $this->headers[ $key ] );
				}
				// Push this value
				$this->headers[ $key ][] = $value;
			} else {
				$this->headers[ $key ] = $value;
			}
		}

	}

}

?>
