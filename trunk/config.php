<?php

require_once( 'Smarty.class.php' );
// create a smarty object
$smarty = new Smarty;

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
    
    public static $cookie = array( 'name' => "shelveit_cookie",
                                   'domain' => '.shelveit.net'
                                   'expire' => 31536000, //one year
                                   'secure' => false );

    public static $auth = array( 'isGuest' => true,
                                 'name' => 'Guest',
                                 'id' => 1 );
    
    public static function connect() {
		$mysql = mysql_connect( ShelveIt::$db[ 'host' ],
   							    ShelveIt::$db[ 'user' ],
								ShelveIt::$db[ 'password' ] );

		if( !$mysql ) {
			die( 'CRITICAL ERROR: Failed to connect to the database server.<br />' .
                 'Please contact the administrator and report this.<br />' .
                 'MySQL error: ' .
                 mysql_error( $mysql ). '<br />' );
		}
		
		if( !mysql_select_db( ShelveIt::$db[ 'name' ], $mysql ) ) {
			die( 'CRITICAL ERROR: Failed to select the Shelve It database.<br />' .
                 'Please contact the administrator and report this.<br />' .
                 'MySQL error: ' .
                 mysql_error( $mysql ). '<br />' );
		}
        
        return $mysql;
    }
    
    public static function check_cookie() {
        // Check the authentication cookie, store info in auth
        // if it's set and checks out, then set up the info
        $punbb_users = 'punbb_users';
        $domain = '.shelveit.net';
        
        if( is_set( $_COOKIE[ $that->cookie[ 'name' ] ] ) ) {
            list( $user, $hash ) = unserialize( $_COOKIE[ $that->cookie_name ] );
            if( $user != 'Guest' ) {
                $mysql = ShelveIt::connect();
                
                $query = 'SELECT * FROM ' .
                    $punbb_users .
                    ' WHERE id =\'' .
                     mysql_real_escape_string( $user ) . 
                    '\' AND password='\' .
                    mysql_real_escape_string( $hash ) .'\'';
                
                $results = mysql_query( $query, $mysql );
                if( !$results ) {
                    die( "Cookie check query: $query<br />" .
                         'Invalid query: ' . mysql_error( $this->mysql ) );
                }
                
                if( mysql_num_rows( $results ) == 0 ) {
                    //Bad cookie!
                    
                    //display error
                    array_push( $this->messages[ 'errors' ],
                                "Invalid cookie! Please login again" );
                    //give them a guest cookie
                    setcookie( $that->cookie_name,
                               serialize( array( $that->auth[ 'name' ], '' ) ),
                               now() + $that->cookie[ 'expire' ],
                               '/',
                               $that->cookie[ 'domain' ],
                               $that->cookie[ 'secure' ] );
                } else {
                    $vals = mysql_fetch_object( $results );
                    
                    $this->auth[ 'user' ] = $results->username;
                    $this->auth[ 'id' ] = $results->id;
                }
            }
        } else {
            // user has no cookie, give them a guest cookie
            setcookie( $that->cookie_name,
                       serialize( array( $that->auth[ 'name' ], '' ) ),
                       now() + $that->cookie[ 'expire' ],
                       '/',
                       $that->cookie[ 'domain' ],
                       $that->cookie[ 'secure' ] );
        }
    }
}

ShelveIt::check_cookie();

$smarty->assign_by_ref( 'messages', ShelveIt::$messages );
$smarty->assign_by_ref( 'auth', ShelveIt::$auth );

?>