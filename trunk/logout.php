<?php
include_once 'config.php';

// give the user a guest cookie, effectively logging them out
setcookie( $that->cookie_name,
           serialize( array( $that->auth[ 'name' ], '' ) ),
           now() + $that->cookie[ 'expire' ],
           '/',
           $that->cookie[ 'domain' ],
           $that->cookie[ 'secure' ] );

array_push( ShelveIt::messages[ 'infos' ],
            "You have succesfully logged out. <a href=\"http://shelveit.net/\">Continue ...</a> " );

$smarty->display( 'logout.tpl' );

?>