<?php
include_once( 'config.php' );

class Query_Cache {
	private static $maxHits = 10000;
	private $results;
	private $mysql;
	
	function __construct() {
		$this->results = null;
		$this->mysql = null;
	}
	
	public function getSearchResults( $query, $start, $step, &$hits, &$pid ) {
        $stop = $start + $step;
        
        if( $this->grabQueryFromCache( $query ) ) {
			//pull from cache
			$hits = mysql_num_rows( $this->results );
			for( $i = $start; $i < $stop; $i++ ) {
				$results[] = $this->getCachedBook( $i );
			}
		} else {
			//pull from LoC
			
//  			$mayDemandCache = ( ( $HTTP_GET_VARS['type'] != $HTTP_GET_VARS['sort'] ) &&
//  								( $HTTP_GET_VARS['type'] != 'Keyword' ) &&
//                                 ( !isset( $HTTP_GET_VARS['sort'] ) ) );
 			$mayDemandCache = isset( $_GET['sort'] ) &&
				( !( ( $HTTP_GET_VARS['sort'] == $HTTP_GET_VARS['type'] ) &&
				   ( !isset( $_GET['desc'] ) ) ) ||
				  isset( $_GET['desc'] ) );
            
			$willCache = ( ( ( ShelveIt::$cachePolicy == 'always' ) ||
							 ( ( ShelveIt::$cachePolicy == 'demand' ) && $mayDemandCache ) ) &&
						   !( ShelveIt::$cachePolicy == 'never' ) );
			
			// connect to LoC server
			$LoC = yaz_connect( 'z3950.loc.gov:7090/voyager'  );
			
			//set server options
			yaz_syntax( $LoC, 'marc21' );
			if( $willCache ) {
				// we're going to grab all results
				yaz_range( $LoC, 1, $this->maxHits );
			} else {
				yaz_range( $LoC, $start, $step );
			}
			
			// perform search
			yaz_search( $LoC, 'rpn', $query);
			
			// wait for search to finish
			yaz_wait();
			
			// check for an error
			$error = yaz_error( $LoC );
			if (!empty( $error ) ) {
				ShelveIt::$messages['errors'][] = $error . " $query";
				$hits = 0;
			} else {
				//get results
				$hits = yaz_hits( $LoC );
                
                if( $willCache && ( $hits > ShelveIt::$query_cache[ 'maxHits' ] ) ) {
                    ShelveIt::$messages['warnings'][] = "Your search has too many results to be quickly sorted. It has been limited from $hits to ".ShelveIt::$query_cache['maxHits'].". You can make your search more specific so it has a small enough number of hits, or not sort to see all of the results.";
                    $hits = ShelveIt::$query_cache[ 'maxHits' ];
                }
				
				if( $hits > 0 ) {
                if( $willCache ) {
					for( $i = 1; $i < $start; $i++ ) {
                        $rec = yaz_record( $LoC, $i, 'xml' );
                        $this->cacheBook( $query, new Brief_Book( $rec ) );
                    }
                } else {
                    $i = $start;
                }
                for( ; $i < $stop; $i++ ) {
                    $rec = yaz_record( $LoC, $i, 'xml' );
                    $book = new Brief_Book( $rec );
                    $results[] = $book;
                    if( $willCache ) {
                        $this->cacheBook( $query, $book );
                    }
                }
                
                if( $willCache ) {
// 						// quick note: I can fork using pcntl_fork
// 						$pid = pcntl_fork();
// 						if( $pid == -1 ) {
// 							die( 'CRITICAL ERROR: Forking failed. The server is likely in trouble. Please come back later.' );
// 						} else if(  $pid == 0 ) {
							// pick up where we left off
							for( ; $i <= $hits; $i++ ) {
								$rec = yaz_record( $LoC, $i, 'xml' );
								$this->cacheBook( $query, new Brief_Book( $rec ) );
							}
							
							$this->collectGarbage();
// 							exit( 0 );
// 						}
						
					}
				}
			}
		}
        
		return $results;
	}
	
	// returns false on failure
	private function grabQueryFromCache( $query ) {
		if( ShelveIt::$cachePolicy == 'never' ) {
			// don't even bother checking if we're not caching
			return false;
		}
		// check by seeing selecting results and storing them in results
		// (if it is then all are and this saves results time )
		
		$this->mysql = mysql_connect( ShelveIt::$db[ 'host' ],
									  ShelveIt::$db[ 'user' ],
									  ShelveIt::$db[ 'password' ] );

		if( !$this->mysql ) {
			die( 'CRITICAL ERROR: Failed to connect to the database server.<br />' .
                 'Please contact the administrator and report this.<br />' .
                 'MySQL error: ' .
                 mysql_error( $this->mysql ). '<br />' );
		}
		
		if( !mysql_select_db( ShelveIt::$db[ 'name' ], $this->mysql ) ) {
			die( 'CRITICAL ERROR: Failed to select the Shelve It database.<br />' .
                 'Please contact the administrator and report this.<br />' .
                 'MySQL error: ' .
                 mysql_error( $this->mysql ). '<br />' );
		}
		
		$query = 'SELECT title,year,author,lcc,lcn  FROM ' .
			ShelveIt::$query_cache[ 'table' ] .
			' WHERE query=\'' .
			mysql_real_escape_string( $query, $this->mysql ) .
			'\'';
		
		if( $_GET[ 'sort' ] ) {
			//append sort by option
			$query .= ' ORDER BY ' . $_GET[ 'sort' ];
			if( $_GET[ 'desc' ] ) {
				//append sort by option
				$query .= ' DESC';
			}
		}
		
		$this->results = mysql_query( $query,
									 $this->mysql );
		
        if( !$this->results ) {
            die( "Cache check query: $query<br />" .
                 'Invalid query: ' . mysql_error( $this->mysql ) );
        };
        
		//mysql_close( $this->mysql );
		
		
		return mysql_num_rows( $this->results ) > 0;
	}
	
	// place a book in the query cache
	private function cacheBook( $query, $book ) {
		// escape fields for query use
		$query = mysql_real_escape_string( $query );
		
		
		$book->primaryAuthor = mysql_real_escape_string( $book->primaryAuthor );
		$book->publicationYear = mysql_real_escape_string( $book->publicationYear );
		$book->LoCClass = mysql_real_escape_string( $book->LoCClass );
		$book->LoCNumber = mysql_real_escape_string( $book->LoCNumber );
        
        $fields = '';
        $values = '';
        if( $book->title ) {
            $book->title = mysql_real_escape_string( $book->title );
            $fields.= 'title,';
            $values.= "'{$book->title}',";
        }
        if( $book->primaryAuthor ) {
            $book->primaryAuthor = mysql_real_escape_string( $book->primaryAuthor );
            $fields.= 'author,';
            $values.= "'{$book->primaryAuthor}',";
        }
        if( $book->publicationYear ) {
            $book->publicationYear = mysql_real_escape_string( $book->publicationYear );
            $fields.= 'year,';
            $values.= "'{$book->publicationYear}',";
        }
        if( $book->LoCClass ) {
            $book->LoCClass = mysql_real_escape_string( $book->LoCClass );
            $fields.= 'lcc,';
            $values.= "'{$book->LoCClass}',";
        }
        if( $book->LoCNumber ) {
            $book->LoCNumber = mysql_real_escape_string( $book->LoCNumber );
            $fields.= 'lcn,';
            $values.= "'{$book->LoCNumber}',";
        }
        if( $query ) {
            $query = mysql_real_escape_string( $query );
            $fields.= 'query,';
            $values.= "'{$query}',";
        }
		
        $fields.= 'time';
        $values.= 'NOW()';
		
		// build query
		$sqlquery = 'INSERT INTO ' .
			ShelveIt::$query_cache[ 'table' ] .
			' (' . $fields . ') VALUES (' . $values . ')';
        
		// perform insertion
		if( !mysql_unbuffered_query( $sqlquery, $this->mysql ) ) {
            die( "Insertion query: $sqlquery<br />" .
                 'Invalid query: ' . mysql_error( $this->mysql ) );
        };
	}
	
	// pull a book from the query cache
	private function getCachedBook( $num ) {
		mysql_data_seek( $this->results, $num-1 );
		$row = mysql_fetch_object( $this->results );
		
		//build book
		$book = new Brief_Book();
		$book->title = $row->title;
		$book->primaryAuthor = $row->author;
		$book->publicationYear = $row->year;
		$book->LoCClass = $row->LCC;
		$book->LoCNumber = $row->LCN;
		
		return $book;
	}
	
	//remove queries older than timeout
	private function collectGarbage() {
		$query = 'DELETE FROM ' .//'DELETE LOW_PRIORITY FROM ' .
			ShelveIt::$query_cache[ 'table' ] .
			' WHERE time < NOW() - ' . ShelveIt::$query_cache[ 'expire' ];
        
		if( !mysql_unbuffered_query( $query, $this->mysql ) ) {
            die( "Garbage collect query: $query<br />" .
                 'Invalid query: ' . mysql_error( $this->mysql ) );
        };
	}
}
?>