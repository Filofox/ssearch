<?php

/**
 * Copyright (c) 2011 Pat Fox
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL (http://www.gnu.org/licenses/gpl.html) licenses
 * http://code.google.com/p/ssearch
 */
require_once( sSearch::GetPathLibrary() . 'core/sSearchContent.class.php' );

/**
 * Extract content from HTML documents
 */
class sSearchContentHTML extends sSearchContent{

	public $mime_type = sSearch::CONTENT_TYPE_HTML;

	function __construct( sSearchConfig $config, $url, $content ){

		parent::__construct( $config, $url, $content );

		$follow_links = true;

		// Read meta tags (regex taken from http://forums.digitalpoint.com/showthread.php?t=104983)
		preg_match_all( '/<[\s]*meta[\s]*name[\s]*=[\s]*["\']?([^>"\']*)["\']?[\s]*content[\s]*=[\s]*["\']?([^>"\']*)["\']?[\s]*[\/]?[\s]*>/si', $content, $matches );
		if( isset( $matches[ '1' ] ) ){
			$robots = array_search( 'robots', $matches[ '1' ] );
			if( $robots !== false ){
				// Index this?
				if( strpos( $matches[ 2 ][ $robots ], 'noindex' ) !== false ){
					$this->index = false;
					return;
				}
				// Follow links?
				if( strpos( $matches[ 2 ][ $robots ], 'nofollow' ) === false ){
					$follow_links = false;
				}
			}
		}

		if( $follow_links ){
			// Find links
			$this->links = $this->FindLinks( $content );
		}

		// Try to find title element
		preg_match( '~<\s*title.*>(.*)</title.*>~Ui', $content, $matches );
		$title = false;
		if( isset( $matches[ 1 ] ) ){
			$title = $matches[ 1 ];
		} else {
			// Try to find h1 element
			preg_match( '~<\s*h1.*>(.*)</h1.*>~Ui', $content, $matches );

			if( isset( $matches[ 1 ] ) ){
				$title = $matches[ 1 ];
			}
		}

		// Grab only the body
		preg_match( '~<body.*>(.*)</body>~Usi', $content, $matches );
		if( isset( $matches[ 1 ] ) ){
			$content = $matches[ 1 ];
		} else {
			$content = '';
		}

		// Exclude content?
		if( $this->config->indexer->exclude_by_marker ){
			$content = preg_replace( '~' . $this->config->indexer->exclude_marker_open . '(.*)' . $this->config->indexer->exclude_marker_close . '~Usi', ' ', $content );
		}
		// Include content?
		if( $this->config->indexer->include_by_marker ){
			preg_match_all( '~' . $this->config->indexer->include_marker_open . '(.*)' . $this->config->indexer->include_marker_close . '~Usi', $content, $matches );
			if( isset( $matches[ 1 ] ) && count( $matches[ 1 ] ) > 0 ){
				$content = implode( ' ', $matches[ 1 ] );
			} else {
				$content = '';
			}
		}

		// Remove HTML tags, script etc
		$this->content = $this->StripHTML( $content );

		// Replace any whitespace with a single space
		$this->content = preg_replace( '~(\s+)~', ' ', $this->content );

		if( strlen( $title ) > 0 ){
			$this->title = $title;
		} else {
			// Set title to first n words of content
			$words = str_word_count( $this->content, 1 );
			$this->title = implode( ' ', array_slice( $words, 0, $this->config->content->title_word_length ) );
		}
	}

	/**
	 * Extract all links from text
	 */
	private function FindLinks( $content ){
		preg_match_all( '/<a.*?href\s*=\s*["\']([^"\']+)[^>]*>.*?<\/a>/si', $content, $matches );

		return array_unique( $matches[ 1 ] );
	}

	/**
	 * Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
	 * All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without
	 * modification, are permitted provided that the following conditions
	 * are met:
	 *
	 *	* Redistributions of source code must retain the above copyright
	 *	  notice, this list of conditions and the following disclaimer.
	 *
	 *	* Redistributions in binary form must reproduce the above
	 *	  copyright notice, this list of conditions and the following
	 *	  disclaimer in the documentation and/or other materials provided
	 *	  with the distribution.
	 *
	 *	* Neither the names of David R. Nadeau or NadeauSoftware.com, nor
	 *	  the names of its contributors may be used to endorse or promote
	 *	  products derived from this software without specific prior
	 *	  written permission.
	 *
	 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
	 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
	 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
	 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
	 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
	 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
	 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
	 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
	 * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
	 * OF SUCH DAMAGE.
	 */

	/*
	 * This is a BSD License approved by the Open Source Initiative (OSI).
	 * See:  http://www.opensource.org/licenses/bsd-license.php
	 */


	/**
	 * Strip out (X)HTML tags and invisible content.  This function
	 * is useful as a prelude to tokenizing the visible text of a page
	 * for use in a search engine or spam detector/remover.
	 *
	 * Unlike PHP's built-in strip_tags() function, this function will
	 * remove invisible parts of a web page that normally should not be
	 * indexed or passed through a spam filter.  This includes style
	 * blocks, scripts, applets, embedded objects, and everything in the
	 * page header.
	 *
	 * In anticipation of tokenizing the visible text, this function
	 * detects (X)HTML block tags (such as divs, paragraphs, and table
	 * cells) and inserts a carriage return before each one.  This
	 * insures that after tags are removed, words before and after the
	 * tag are not erroneously joined into a single word.
	 *
	 * Parameters:
	 * 	text		the (X)HTML text to strip
	 *
	 * Return values:
	 * 	the stripped text
	 *
	 * See:
	 * 	http://nadeausoftware.com/articles/2007/09/php_tip_how_strip_html_tags_web_page
	 */
	private function StripHTML( $text )
	{
		// PHP's strip_tags() function will remove tags, but it
		// doesn't remove scripts, styles, and other unwanted
		// invisible text between tags.  Also, as a prelude to
		// tokenizing the text, we need to insure that when
		// block-level tags (such as <p> or <div>) are removed,
		// neighboring words aren't joined.
		$text = preg_replace(
			array(
				// Remove invisible content
				'@<head[^>]*?>.*?</head>@siu',
				'@<style[^>]*?>.*?</style>@siu',
				'@<script[^>]*?.*?</script>@siu',
				'@<object[^>]*?.*?</object>@siu',
				'@<embed[^>]*?.*?</embed>@siu',
				'@<applet[^>]*?.*?</applet>@siu',
				'@<noframes[^>]*?.*?</noframes>@siu',
				'@<noscript[^>]*?.*?</noscript>@siu',
				'@<noembed[^>]*?.*?</noembed>@siu',

				// Add line breaks before & after blocks
				'@<((br)|(hr))@iu',
				'@</?((address)|(blockquote)|(center)|(del))@iu',
				'@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
				'@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
				'@</?((table)|(th)|(td)|(caption))@iu',
				'@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
				'@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
				'@</?((frameset)|(frame)|(iframe))@iu',
			),
			array(
				' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
				"\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
				"\n\$0", "\n\$0",
			),
			$text );

		// Remove all remaining tags and comments and return.
		return strip_tags( $text );
	}

}

?>
