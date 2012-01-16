<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */
require_once( sSearch::GetPathLibrary() . 'core/sSearchContent.class.php' );
require_once( sSearch::GetPathLibrary() . '_lib/LibPDFToText.class.php' );

/**
 * Extract content from PDF documents
 */
class sSearchContentPDF extends sSearchContent{

	public $mime_type = sSearch::CONTENT_TYPE_PDF;

	function __construct( sSearchConfig $config, $url, $content_string ){

		parent::__construct( $config, $url, $content_string );

		$pdf_converter = new LibPDFToText();

		$this->content = $pdf_converter->Convert( $content_string );
		// Use first line as header
		$this->title = substr( $this->content, 0, strpos( $this->content, "\n" ) );
	}
}

?>
