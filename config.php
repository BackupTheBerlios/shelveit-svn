<?php

function __autoload($class){
    require_once( "classes/$class.php" );
}

class ShelveIt {
    public static $messages = array( 'infos' => array(),
                                     'warnings' => array(),
                                     'errors' => array() );
    
    public static $cachePolicy = 'demand';
	
	public static $db = array( 'host' => 'localhost',
							   'user' => 'shelvei_shelveit',
							   'password' => 'd12strike',
							   'name' => 'shelvei_shelveit' );
	
	public static $query_cache = array( 'expire' => 900,
										'table' => 'search_cache',
                                        'maxHits' => 50 );
    public static $auth = array( 'isGuest' => true,
                                 'name' => '',
                                 'id' => 0 );
}

// Check the authentication cookie, store info in auth
// if it's set and checks out, then set up the info
?>