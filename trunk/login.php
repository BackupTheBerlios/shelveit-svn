<?php
require_once 'config.php';

if( $_POST[ 'user' ] ) {
    
    $mysql = ShelveIt::connect();
    
    $query = 'SELECT * FROM ' .
        $punbb_users .
        ' WHERE id =\'' .
        mysql_real_escape_string( $_POST[ 'user' ] ) . 
        '\' AND password='\' .
        mysql_real_escape_string( sha1( $_POST[ 'pass' ] ) .'\'';
    
    $results = mysql_query( $query, $mysql );
    if( !$results ) {
        die( "Cookie check query: $query<br />" .
             'Invalid query: ' . mysql_error( $this->mysql ) );
    }
    
    if( mysql_num_rows( $results ) == 0 ) {
        //Bad cookie!
        
        //display error
        array_push( $this->messages[ 'errors' ],
                    "Incorrect username or password." );
    } else {
        $vals = mysql_fetch_object( $results );
        
        $this->auth[ 'user' ] = $results->username;
        $this->auth[ 'id' ] = $results->id;
        
        
        //give them a cookie
        setcookie( $that->cookie_name,
                   serialize( array( $results->username, $results->password ) ),
                   now() + $that->cookie[ 'expire' ],
                   '/',
                   $that->cookie[ 'domain' ],
                   $that->cookie[ 'secure' ] );
        
        //forward them?
        if( $_POST[ 'forward' ] ) {
            $url = 'http://shelveit.net/' . $_POST[ 'forward' ];
            array_push( ShelveIt::messages[ 'infos' ],
                        "You have succesfully logged in. <a href=\"$url\">Continue ...</a> " );
            header( "Location: $url" );
        }
    }
}

$smarty->display( 'login.tpl' );

?>