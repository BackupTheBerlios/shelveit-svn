<?php
include_once 'config.php';

// a class used for brief listings, such as books in your collection
// or search results
class Brief_Book {
    public $title;
    
    // used for sorting by author
    public $primaryAuthor;
    // used for display
    public $fullAuthor;
    // used for social networking
    public $allAuthors;
    
    public $publicationYear;
    public $creationYear;
	
	public $LoCClass;
    public $LoCNumber;
    
	protected $parser;
	
    function __construct( $xmlString = "" ) {
		if( $xmlString != "" ) {
			$this->parser = new Record_Parser;
			$this->parser->srcenc = 'utf-8';
			
			//datafield 050, subfield a == LoC Classification
			//datafield 050, subfield b == LoC Item Number
			//datafield 245, subfield a == title
			//datafield 245, subfield a == title
			//datafield 245, subfield b == subtitle
			//datafield 245, subfield c == authors, etc
			//datafield 260, subfield c == publication and creation year
			$this->parser->dataTree = array( '050' => array( 'a' => null,
															 'b' => null ),
											 '260' => array( 'c' => null ),
											 '245' => array( 'a' => null,
															 'b' => null,
															 'c' => null ) );
			
			$this->parser->parseString( utf8_encode( $xmlString ) );
			
			$this->title = $this->parser->dataTree[ '245' ][ 'a' ];
			
			$l = strlen( $this->title ) - 2;
			// if the title ends in ' /'
			if( substr( $this->title, $l, 2 ) == ' /' ) {
				//there's no subtitle and we should remove that unsightly ' /'
				$this->title = substr( $this->title, 0, $l );
			} else {
				//append the subtitle after removing the trailing ' /'
				$this->title .= ' ' . substr( 
											 $this->parser->dataTree[ '245' ][ 'b' ], 
											 0, 
											 -2 );
			}
			
			//strips off the ending period before storing
			$this->fullAuthor = substr( $this->parser->dataTree[ '245' ][ 'c' ],
										0,
										strlen( $this->parser->dataTree[ '245' ][ 'c' ] ) - 1);
			
			$first = true;
			$offset = 0;
			// matches authors
			$authorPattern = '/((\b)|(by )|(nd )|( & )|( ; )|(, )|(r[sty] )|(or ))(([A-Z]+[\w\.]*\s*)|de\s+)+/';
			while( preg_match( $authorPattern,
							   $this->fullAuthor,
							   $matches,
							   PREG_OFFSET_CAPTURE,
							   $offset ) ) {
				$matches[ 0 ][ 0 ] = substr( $matches[ 0 ][ 0 ],
											 strlen( $matches[ 1 ][ 0 ] ) );
				if( $first ) {
					$this->primaryAuthor = $matches[ 0 ][ 0 ];
					$first = false;
				}
				// append this author to the listing of authors
				$this->allAuthors[] = $matches[ 0 ][ 0 ];
				$offset = $matches[ 0 ][ 1 ] + strlen( $matches[ 0 ][ 0 ] );
			}
			
			// extract a 4-digit year
			$offset = 0;
			preg_match( '/\d{4}/', 
						$this->parser->dataTree[ '260' ][ 'c' ],
						$matches,
						PREG_OFFSET_CAPTURE,
						$offset );
			$this->publicationYear = $matches[ 0 ][ 0 ];
			$offset = $matches[ 0 ][ 1 ] + strlen( $matches[ 0 ][ 0 ] );
			// if two years are present, then the first is the publication
			// year and the second is the year of creation
			if( preg_match( '/\d{4}/', 
							$this->parser->dataTree[ '260' ][ 'c' ],
							$matches,
							PREG_OFFSET_CAPTURE,
							$offset ) ) {
				$this->creationYear = $this->publicationYear;
				$this->publicationYear = $matches[ 0 ][ 0 ];
			}
       
			
			$this->LoCClass = $this->parser->dataTree[ '050' ][ 'a' ];
			$this->LoCNumber = $this->parser->dataTree[ '050' ][ 'b' ];
		}
    }


}

?>