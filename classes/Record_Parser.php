<?php

require_once 'XML/Parser/Simple.php';


class Record_Parser extends XML_Parser {
    // this is meant to be set to a 2d array in the form of
    // array( "selector1" => array(
    //                             "selector2" => null ),
    //        "selector1" => array(
    //                             "selector2" => null ) );
    // where selector1 matches a desired datafield and selector2
    // matches a desired subfield of that datafield. After parse is
    // called, the array will be populated with data (where the nulls
    // are). This approch was selected over using the DOM XML
    // extension because Shelve It does not require the overhead of a
    // full parse tree
    public $dataTree;
    private $curdatafield = array();
    private $cursubfield = null;
    private $isSubjectField;

    
    function __construct() {
        parent::__construct();
    }

   /**
    * handle start element
    *
    * @access   private
    * @param    resource    xml parser resource
    * @param    string      name of the element
    * @param    array       attributes
    */
    function startHandler($xp, $name, $attribs) {
		
        switch( $name ) {
        case DATAFIELD:
            if( isset( $this->dataTree[ $attribs[ 'TAG' ] ] ) ) {
                $this->curdatafield =& $this->dataTree[ $attribs[ 'TAG' ] ];
                $this->isSubjectField = ( $attribs[ 'TAG' ] == '650' );
            }
            break;
        case SUBFIELD:
            if( isset( $this->curdatafield ) && array_key_exists( $attribs[ 'CODE' ], $this->curdatafield ) ) {
                $this->cursubfield =& $this->curdatafield[ $attribs[ 'CODE' ] ];
                if( $this->cursubfield === null ) {
                    $this->cursubfield = "";
                }
            }
            break;
        default:
            // just ignore the tag
        }
    }
	
    function cdataHandler($xp, $data) {
        if( isset( $this->cursubfield ) ) {
            //the subject field (650) is the only special case where
            // a datafield is actually repeated
            if( $this->isSubjectField) {
                $this->cursubfield[] = $data;
            } else {
                // General case:
                // append, because parser "takes a break" for entities
                $this->cursubfield .= $data;
            }
        }
	}

   /**
    * handle start element
    *
    * @access   private
    * @param    resource    xml parser resource
    * @param    string      name of the element
    * @param    array       attributes
    */
    function endHandler($xp, $name) {
        switch( $name ) {
        case DATAFIELD:
			unset( $this->curdatafield );
            break;
        case SUBFIELD:
			unset( $this->cursubfield );
            break;
        default:
            // just ignore the tag
        }
    }
}

?>