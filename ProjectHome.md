# Introduction to sSearch #

The initial 's' in sSearch stands for 'simple' -- this library is intended to be a simple search library for small- to medium-size web sites where it is not feasible to install search engine software. It is written entirely in object-oriented PHP5 and by default uses a basic MySQL back-end (although an improved MySQL-based engine is in development).

## Features ##

sSearch currently supports:

  * Index single page
  * Index page and follow links ('spidering') recursively to a custom depth
  * Restrict to one site or spider external links too
  * Process and respect robots.txt rules
  * Process and respect noindex, nofollow meta tags
  * Markers to exclude page content
  * Index HTML, PDF files (PDF support is experimental)
  * Uses simple MySQL search engine
  * Optional support for Google Custom Search, Microsoft Bing, Yahoo! BOSS
  * Plain and highlighted search result snippets
  * Fully object-oriented PHP5 code, E\_STRICT compliant
  * Support for CLI (Command Line Interpreter) -- to enable e.g. regular index updates via CRON

For maximum flexibility the code is designed to be fully modular -- for example, swapping between search engines is achieved through a simple configuration change (engine-specific installation/configuration notwithstanding).

## Getting started ##

Full documentation will arrive in due course, in the meantime check out the [wiki](http://code.google.com/p/ssearch/wiki/Main).

## Possible future additions/enhancements ##

In approximate order of priority/likely implementation

  * Prefer meta description over content snippet (where available)
  * More robust support for different (non-UTF8) character encodings
  * Automated installation
  * Internal inclusion/exclusion rules (using regex?)
  * Per-site configuration overrides
  * Per-query configuration overrides
  * Read and preserve cookies during indexing
  * Site login (i.e. ability to index password-protected sites by providing login details)
  * Improved native MySQL-based search (possibly based on [Sphider](http://www.sphider.eu/)'s indexing set-up) and/or engine based on Apache Lucene (using [Zend's PHP port](http://framework.zend.com/manual/en/zend.search.lucene.html))
  * Profiling/logging
  * Optional support for other document types via external libraries (e.g. [catdoc/xls2csv/catppt](http://www.wagner.pp.ru/~vitus/software/catdoc/))
  * Support for auto-complete/word suggest
  * Stand-alone results page
  * Stand-alone GUI for administration