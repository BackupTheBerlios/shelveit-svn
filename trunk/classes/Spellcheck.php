<?php

Class Spellcheck {
	
	private $speller;
	
	function __construct( $lang='en' ) {
		$this->speller = pspell_new( $lang );
	}
	
	// Returns an array containing misspelled words and a list of
	// suggestions for them
	// highlight is an optional parameter. If passed, i will contain a
	// the string with misspelled words "highlighted" (with pre.word.post
	// for each misspelled word
	public function check( $str,
						   &$highlight = null,
						   $pre  = '<em>',
						   $post = '</em>' ) {
		$words = preg_split('/[\W]+?/', $str );
		
		$suggestions = $this->arrCheck( $words );
		
		if( $highlight !== null ) {
			//there's no point in highlighting if no words are mispelled
			if( empty( $suggestions ) ) {
				$highlight = $str;
			} else {
				$highlight = '';
				foreach( $words as $word ) {
					if( array_key_exists( $word, $suggestions ) ) {
						$highlight .= $pre . $word . $post;
					} else {
						$highlight .= $word;
					}
				}
			}
		}
		
		$suggestions = check( $words );
		
	}
	
	// Returns an array containing misspelled words and a list of
	// suggestions for them
	public function arrCheck( $words ) {
		
		//find misspelled words
		foreach ( $words as $word ) {
			if( !pspell_check($this->speller, $word ) ) {
				$misspelled[] = $word;
			}
		}
		$suggestions = array();
		if( !empty( $misspelled ) ) {
			// return contains a list of suggestions for each mispelled words
			foreach ($misspelled as $value) {
				// don't get suggestions for the same word twice
				if( !array_key_exists( $value, $suggestions ) ) {
					$suggestions[ $value ] =
						pspell_suggest($this->speller, $value);
				}
			}
		}
		
		return $suggestions;
	}
	
	// Returns a corrected string using the first suggestion
	// highlight is an optional parameter. If passed, i will contain a
	// the corrected string "highlighted" (with pre.word.post for each
	// misspelled word
	public function auto_correct( $str,
								  &$highlight = null,
								  $pre  = '<em>',
								  $post = '</em>') {
		
		$words = preg_split('/[\W]+?/', $str );
		
		$suggestions = $this->arrCheck( $words );
		
		// we don't need to do anything if everything's spelled correctly
		if( empty( $suggestions ) ) {
			$highlight = $str;
		} else {
			if( $highlight !== null ) {
				$highlight = '';
			}
			$str = '';
			
			foreach( $words as $word ) {
				if( array_key_exists( $word, $suggestions ) ) {
					// replace the word with the first suggestion
					if( $highlight !== null ) {
						$highlight .= ' ' .$pre .
							$suggestions[ $word ][ 0 ] .
							$post;
					}
					$str .= ' ' . $suggestions[ $word ][ 0 ];
				} else {
					if( $highlight !== null ) {
						$highlight .= ' ' . $word;
					}
					$str .= ' ' . $word;
				}
			}
		}
		
		return $str;
	}
}

?>