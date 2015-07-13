# Usage #

A very quick guide to using sSearch.

## Indexing a site ##

To use sSearch, the first step is to build an index. This is done by calling the sSearch::Index( [url](url.md), [depth](depth.md) ) method on a page:

```
require_once( 'sSearch.class.php' );
$search = new sSearch();
$search->Index( 'http://example.com', 1 );
```

This will index the contents of _http://example.com_, with a depth of 1 -- meaning that it will follow links on the first page but no further. Increasing the depth value will allow the spider to follow links more deeply, but will of course take longer.

## Searching ##

Once you've built an index, you can search it. You can do this simply by calling the sSearch::Search( [terms](terms.md), [start\_index](start_index.md), [number\_results](number_results.md) ) method:

```
require_once( 'sSearch.class.php' );
$search = new sSearch();
$query = $search->Search( 'example query', 0, 10 );
```

This will return the first ten results of a search for the terms 'example' and 'query'. To return the next ten results, simply change the start index value to 10.

## Rendering results ##

The Search() method returns an sSearchQuery object, which contains a list of sSearchResult objects as well as some other information about the query.

```
sSearchQuery Object
(
    [terms] => example query
    [start] => 0
    [total] => 125
    [max] => 10
    [count] => 10
    [results] => Array
        (
            [0] => sSearchResult Object
                (
                    [url] => http://example.com
                    [title] => An example web site
                    [snippet] => This is an example web site with...page related to the query above
                    [snippet_highlighted] => This is an <b>example</b> web site with...page related to the <b>query</b> above
                )
   ...
        )
)
```

You can iterate over the query object itself, like this:

```
foreach( $query as $result ){
// Your code to display the result
}
```