<?php

include_once( 'config.php' );

if( $HTTP_GET_VARS['term'] ) {
	
	/////////////////////////////////////////////////////
	// Variable setup
	/////////////////////////////////////////////////////
	
	// This flag is just used to determine whether or not to do spellchecking
	$spellcheck = !( ( $HTTP_GET_VARS['c'] == '1' ) ||
		( $HTTP_GET_VARS['start'] > 1 ) );

	$term = $HTTP_GET_VARS['term'];
	
	if( $HTTP_GET_VARS['start'] ) {
		$start = abs( $HTTP_GET_VARS['start'] );
	} else {
		$start = 1;
	}
	if( $HTTP_GET_VARS['step'] ) {
		$step = abs( $HTTP_GET_VARS['step'] );
	} else {
		$step = 10;
	}
	$stop = $start + $step;
	
	/////////////////////////////////////////////////////
	// Put the search term in LoC syntax
	/////////////////////////////////////////////////////
    
	// convert query syntax from my form of CCL/whatver to the weird prefix
	// notation used by LoC
    $term = Query_Convertor::convert_query( $term );
	
    //@attr 1=4 title
    //@attr 1=1003 author
    //@attr 1=7 ISBN
    //@attr 1=16 LoC Call number
	if( strlen( $term ) > 255 ) {
		ShelveIt::$messages['warnings'][] = "Your search is too long. It has been truncated from" .
			strlen( $term ) .
			"to 255 characters (including markup). This may cause your search to fail entirely.";
		$term = substr( $term, 0, 255 );
	}
    switch( $HTTP_GET_VARS['type'] ) {
    case Title:
        $term = '@attr 1=4 ' . $term;
        break;
    case Author:
        $term = '@attr 1=1003 ' . $term;
        break;
    case ISBN:
		//strip out non-numeric characters
        $term = '@attr 1=7 ' .
			preg_replace('/\D/',
						 '',
						 $term );
		//skip spellchecking
		$check = false;
        break;
    case Keyword:
        // nothing needed
        break;
    default:
        // assume keyword. do nothing
        break;
    }
	
    /////////////////////////////////////////////////////
	// Spellchecking
	/////////////////////////////////////////////////////
	
	
	// we can skip corrections if we're into the results or 
	// this is a corrected query
    if( $spellcheck ) {
	    $checker = new Spellcheck();
		
		$corTerm = $HTTP_GET_VARS['term'];
		$hiTerm = 'hello';
		
		$corTerm = $checker->auto_correct( $corTerm, $hiTerm );
		
		$URL = "search.php?" .
			http_build_query( array( 'type' => $HTTP_GET_VARS['type'],
									 'term' => $corTerm ,
                                     'start' => $start,
                                     'step' => $step,
									 'c' => '1' ) );
		if( $corTerm !== $HTTP_GET_VARS['term'] ) {
			$smarty->assign_by_ref( 'correctedTerm', $hiTerm );
			$smarty->assign( 'correctedURL', $URL );
		}
	}
	
	/////////////////////////////////////////////////////
	// Search
	/////////////////////////////////////////////////////
	$cache = new Query_Cache();
	$books = $cache->getSearchResults( $term, $start, $step, $hits, $pid );
	
	$smarty->assign_by_ref( 'hits', $hits );
	if( $hits > 0 ) {
		$thisURL = "search.php?" .
			http_build_query( array( 'type' => $HTTP_GET_VARS['type'],
									 'term' => $corTerm ,
									 'c' => '1' ) );
		foreach( $books as $book ) {
			$results[] = 
                array( 'addURL' => 'addBook.php?' .
                       http_build_query( array( 'LCC' => $book->LoCClass,
                                                'LCN' => $book->LoCNumber  ) ),
                       'detailURL' => 'bookDetail.php?' .
                       http_build_query( array( 'LCC' => $book->LoCClass,
                                                'LCN' => $book->LoCNumber  ) ),
            
                       'title' => $book->title,
                       'author' => $book->primaryAuthor,
                       'year' => $book->publicationYear
                       );
		}
		$smarty->assign_by_ref( 'results', $results );
		
		// set start
		$nextStart = $stop;
		// set start
		$prevStart = $start - $step;
		
		if( $prevStart > 0 ) {
			$URL = 'search.php?' . http_build_query( array( 'term' => $HTTP_GET_VARS[ 'term' ],
                                            'type' => $type,
                                            'start' => $prevStart,
                                            'step' => $step ) );
			$smarty->assign( 'prevURL', $URL );
		}
		
		if( $nextStart < $hits ) {
			$URL = 'search.php?' . http_build_query( array( 'term' => $HTTP_GET_VARS[ 'term' ],
                                            'type' => $type,
                                            'start' => $nextStart,
                                            'step' => $step ) );
			$smarty->assign( 'nextURL', $URL );
		}
	}
	
} else {
	$smarty->assign( 'step', 10 );
}

/////////////////////////////////////////////////////
// Display stuff
/////////////////////////////////////////////////////

$smarty->assign( 'queryTypes', array( 'Title',
	                                  'Author',
                                      'ISBN',
	                                  'Keyword' ) );

// URLS for sorting purposes
$thisURL = 'search.php?' . http_build_query( array( 'term' => $HTTP_GET_VARS[ 'term' ],
                                                    'type' => $type,
                                                    'start' => $start,
                                                    'step' => $step ) );

$sortURLs[ 'Author' ][ 'asc' ] = $thisURL . '&' . http_build_query( array( 'sort' => 'Author' ) );
$sortURLs[ 'Author' ][ 'desc' ] = $sortURLs[ 'Author' ][ 'asc' ] . '&' .
     http_build_query( array( 'desc' => 1 ) );
$sortURLs[ 'Title' ][ 'asc' ] = $thisURL . '&' . http_build_query( array( 'sort' => 'Title' ) );
$sortURLs[ 'Title' ][ 'desc' ] = $sortURLs[ 'Title' ][ 'asc' ] . '&' .
     http_build_query( array( 'desc' => 1 ) );
$sortURLs[ 'Year' ][ 'asc' ] = $thisURL . '&' . http_build_query( array( 'sort' => 'Year' ) );
$sortURLs[ 'Year' ][ 'desc' ] = $sortURLs[ 'Year' ][ 'asc' ] . '&' .
     http_build_query( array( 'desc' => 1 ) );


$smarty->assign_by_ref( 'sortURLs', $sortURLs );

$smarty->display( 'search.tpl' );

if( isset( $pid ) ) {
	pcntl_waitpid( $pid );
}

?>