<?php
require 'Brief_Book.php';

// a class used for brief listings, such as books in your collection
// or search results
class Full_Book extends Brief_Book {
	// publishing info
	public $publishLocation;
	public $publisher;
	
	public $extent;
	public $description;
	public $contents;
	public $subjects;
	
	public $ISSN;
    public $ISBN;
    
	// classification
	public $dewey;
    
    function __construct( $xmlString ) {
        parent::__construct( $xmlString );
		
        $this->parser = new Record_Parser;
        $this->parser->srcenc = 'utf-8';
        //datafield 20, subfield a == ISBN
        //datafield 260, subfield a == publisher location
        //datafield 260, subfield b == publisher
		
        //datafield 300, subfield a+c == description
        //datafield 505, subfield a == contents
        
        //datafield 650, subfield a == subjects
        $this->parser->dataTree = array( '020' => array( 'a' => null ),
                                         '022' => array( 'a' => null ),
                                         '082' => array( 'a' => null ),
                                         '260' => array( 'a' => null,
                                                         'b' => null ),
                                         '300' => array( 'a' => null,
                                                         'c' => null ),
                                         '505' => array( 'a' => null ),
                                         '650' => array( 'a' => null ) );
	   
       $this->parser->parseString( utf8_encode( $xmlString ) );
       
       // publishing info
       $this->publishLocation = $this->parser->dataTree[ '260' ][ 'a' ];
       $this->publisher       = $this->parser->dataTree[ '260' ][ 'b' ];
       
       $this->extent          = $this->parser->dataTree[ '300' ][ 'a' ];
       $this->description     = $this->parser->dataTree[ '300' ][ 'c' ];
       
       //contents
       $this->contents  = explode( ' -- ',
                                  $this->parser->dataTree[ '505' ][ 'a' ] );
        foreach( $this->contents as $key => $val ) {
            if( preg_match( '/^\s*(.*)\s*((\s\/\s)|\()\s*(.*?)\s*(\)\.|\.|\)|\b)$/', $val, $matches ) ) {
                $this->contents[ $key ] = array( 'title' => $matches[ 1 ],
                                                 'author' => $matches[ 4 ] );
                $this->allAuthors[] = $matches[ 4 ];
            } else {
                $this->contents[ $key ] = array( 'title' => $val );
            }
        }
       
       $this->subjects = $this->parser->dataTree[ '650' ][ 'a' ];
       
       
       $this->ISSN            = $this->parser->dataTree[ '022' ][ 'a' ];
       if( preg_match( '/(\d{10})|(\d{13})/',
                       $this->parser->dataTree[ '020' ][ 'a' ] ) ) {
           $this->ISBN = $matches[ 0 ];
       }
       
       // classification
       $this->dewey           = $this->parser->dataTree[ '082' ][ 'a' ];
    }


}

?>