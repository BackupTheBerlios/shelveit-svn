<?php

// I. Initialize an empty stack (string stack),
//     prepare input infix expression and clear RPN string
// II. Repeat until we reach end of infix expression
// 	A. Get token (operand or operator); skip white spaces
// 	B. If token is:
// 		1. Left parenthesis: Push it into stack
// 		2. Right parenthesis: Keep popping from the stack and prepending
//         to RPN string until we reach the left parenthesis.
// 			If stack becomes empty and we didn't reach the left parenthesis
//          then break out with error "Unbalanced parenthesis"
// 		3. Operator: If stack is empty or operator has a higher precedence
//         than the top of the stack then push operator into stack.
// 			Else if operator has lower precedence then we keep popping
//          and prepending to RPN string, this is repeated until operator in
//          stack has lower precedence than the current operator.
// 		4. An operand: we simply prepend it to RPN string.
// 	C. When the infix expression is finished, we start popping off the
//     stack and appending to RPN string till stack becomes empty.

class Query_Convertor {
    
    private static $tokens;        
    private static $token_count;
    private static $operatorMapping = array(
                                            "AND" => "@and",
                                            "ANDNOT" => "@not",
                                            "AND-NOT" => "@not",
                                            "OR" => "@or" );
    
    // takes a string and converts to an RPN query suitable
    // for use with the Library of Congress Voyager server
    public static function convert_query( $query ) {
        // initialize vars
        $rpn = "";
        $stack = array();
        //last was an operand
        $lastWasOpd = false;
        
        // put spaces on either side of parens to guarantee they're
        // seperate tokens
        $query = str_replace( ')', ' ) ', $query );
        $query = str_replace( '(', ' ( ', $query );
            
        // break the query into tokens, delimited by spaces
        self::$tokens = explode( " ", $query );
        self::$token_count = count( self::$tokens );
        
        //while( false ) {
        for( $i = 0; $i < self::$token_count; $i++ ) {
            
            //ignore empty tokens
            if( self::$tokens[ $i ] != "" ) {
                
                if( self::$tokens[ $i ] == '(' ) {
                    // push it onto the stack
                    $stack[] = '(';
                    
                    $lastWasOpd = false;
                } else if( self::$tokens[ $i ] == ')' ) {
                    $lastWasOpd = true;
                    // Keep popping from the stack and prepending to RPN string
                    // until we reach the left parenthesis.
                    
                    $popped = array_pop( $stack );
                    while( $popped != '(' ) {
                        // If stack becomes empty and we didn't reach the left
                        // parenthesis then break out with error "Unbalanced
                        // parenthesis"
                        if( $popped == null ) {
                            die( "ERROR! Unbalanced parentheses" );
                        }
                        
                        $rpn = $popped . ' ' . $rpn;
                        
                        $lastWasOpd = false;
                        $popped = array_pop( $stack );
                    }
                    
                } else if( isset( self::$operatorMapping[ self::$tokens[ $i ] ] ) ) {
                    // 3. Operator: If stack is empty or operator has a higher
                    // precedence than the top of the stack then push operator
                    // into stack.
                    // 	Else if operator has lower precedence then we keep
                    // popping and prepending to RPN string, self is repeated
                    // until operator in stack has lower precedence than the
                    // current operator.
                    $curOp = self::$operatorMapping[ self::$tokens[ $i ] ];
                    
                    $topOp = array_pop( $stack );
                    
                    if( ( $topOp == null ) || ( $topOp == '(' ) ||
                        ( self::get_precedence( $curOp ) >
                          self::get_precedence( $topOp ) ) ) {
                        $stack[] = $topOp;
                        $stack[] = $curOp;
                    } else {
                        
                        while( ( $topOp != null ) && ( $topOp != '(' ) &&
                               ( self::get_precedence( $curOp ) <=
                                 self::get_precedence( $topOp ) )
                               ) {
                            
                            $rpn = $curOp . ' ' . $rpn;
                            
                            // incrementation step
                            $curOp = $topOp;
                            $topOp = array_pop( $stack );
                        }
                        
                        // we may have popped off one extra, if so put it back on
                        if( $topOp == null ) {
                            $stack[] = $curOp;
                        } else {
                            $stack[] = $topOp;
                        }
                        
                    }
                    
                    $lastWasOpd = false;
                    
                } else {
                    //self must be an operand
                    
                    if( $lastWasOpd ) {
                        // insert an implicit AND and rewind to handle it
                        self::$tokens[ $i - 1 ] = "AND";
                        $i -= 2;
                    } else {
                        $fchar = self::$tokens[ $i ][ 0 ];
                        
                        // detect quotes
                        // note: passing i by ref causes it to be in the right
                        // place. The return value is, therefore, stored in the
                        // last token that was part of the quote. Self both
                        // spares a temp var and allows the part afterwards to
                        // be general
                        if( ( $fchar == '"' ) || ( $fchar == '\'' ) ) {
                            // collect quoted part into the last token forming it
                            $temp = self::collect_string( $fchar, $i );
                            // note self has to be done because of
                            // strangess in eval order - $i is
                            // incremented in collect string, but the
                            // left-hand of the assignment occurs
                            // before the call
                            self::$tokens[ $i ] = $temp;
                            
                        }
                    
                        $rpn = self::convert_operand( self::$tokens[ $i ] ) .
                            ' ' . $rpn;
                        $lastWasOpd = true;
                    }
                    
                }
            }
        }
        
        // if any operators remain on the stack, prepend them now
        for( $topOp = array_pop( $stack );
             $topOp != null;
             $topOp = array_pop( $stack ) ) {
            
            $rpn = $topOp . ' ' . $rpn;
        }
        return $rpn;
    }
    
    private static function collect_string( $closed, &$i ) {
        // it's written self way so it can handle single quoted strings
        // e.g. "phrase" or (subexp)
        
        if( self::$tokens[ $i ] == ( $closed . $closed ) ) {
            return '';
        }

        
        //self and the later replacement ensure we use double and not
        //single quotes
        self::$tokens[ $i ] = substr( self::$tokens[ $i ], 1 );
        
        // stop if we reach the end of the array
        for( ; $i < self::$token_count; $i++ ) {
            if( self::$tokens[ $i ] != '' ) {			
                $l = strlen( self::$tokens[ $i] ) - 1;
                
                // while the current token doesn't end in the delimeter
                if( ( self::$tokens[ $i ] == $closed ) ||
                    ( self::$tokens[ $i ][ $l ] == $closed ) ){
                    return '"' . $val . 
                        str_replace( '"',
                                     '\"',
                                     substr( self::$tokens[ $i ], 0, $l  ) )
                        . '"';
                } else {
                    // acumulate tokens
                    // always escape double quotes within the string
                    $val .= str_replace( '"', '\"', self::$tokens[ $i ] ) . ' ';
                }
            }
        }
        
        die( "ERROR!: Unterminated quoteed phrase" );
    }

    private static function get_precedence( $op ) {
        $i = 0;
        foreach( self::$operatorMapping as $CCL => $RPN ) {
            if( ( $CCL == $op ) || ( $RPN == $op ) ) {
                return $i;
            }
        }
        return null;
    }
    
    private static function convert_operand( $st ) {
        $l = strlen( $st ) - 1;
        // if it ends in a dot or asterick
        if( ( $st[ $l ] == '.' ) || ( $st[ $l ] == '*' ) ) {
            //make it a "right truncated search" (mark as incomplete)
            // note that the return value of an assignment is the value assigned
            $st[ $l ] = '?';
            return;
        }
        return $st;
    }
}

?>