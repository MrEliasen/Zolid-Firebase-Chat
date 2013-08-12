<?php
/**
 *  Zolid Chat - 0.1.0
 *  This class is from the Zolid Framework.
 *
 *  A realtime chat based on the awesome technology from Firebase <3
 *
 *  @author     Mark Eliasen
 *  @copyright  (c) 2013 - Mark Eliasen
 *  @version    0.1.0
 */

if( !defined('CORE_PATH') )
{
    die('Direct file access not allowed.');
}

class Security
{
    /**
     * Checks if a CSRF token is valid 
     * 
     * @param  string $key the identifier for the  $_REQUEST and $_SESSION value to compare.
     * @return boolean      true on valid token, false on invalid or missing token.
     */
    public static function csrfCheck($key, $sticky = false)
    {
		// Check if the token exists for the current session
        if( empty( $_SESSION['csrf'][$key] ) ){
			return false;
		}

		// Check if the value is found in the REQUEST
        if( empty( $_REQUEST[$key] ) ){
			return false;
		}

        // Check if the 2 tokens match eachother
        if( $_REQUEST[$key] != $_SESSION['csrf'][$key] ){
			return false;
		}
		
        if( !$sticky )
        {
            //To avoid the token to be used again.
            unset( $_SESSION['csrf'][$key] );
        }
        
        return true;
    }
	
    /**
     * generate a CSRF token.
     * 
     * @param  string $key the name of the token, used when checking if it is valid.
     * @return string      the csrf token. Add this token to a GET or POST value with the same key as the one supplied here.
     */
    public static function csrfGenerate($key)
    {		
		$token = sha1( time() . $_SERVER['REMOTE_ADDR'] . Security::randomGenerator(15) );
        $_SESSION['csrf'][$key] = $token;

        return $token;
    }

    /**
     * Value sanitation. Sanitize input and output with ease using one of the sanitation types below.
     * 
     * @param  string $data the string/value you wish to sanitize
     * @param  string $type the type of sanitation you wish to use.
     * @return string       the sanitized string
     */
    public static function sanitize($data, $type)
    {
		## Use the HTML Purifier, as it help remove malicious scripts and code. ##
		##       HTML Purifier 4.4.0 - Standards Compliant HTML Filtering       ##
		require_once(CORE_PATH . '/libs/htmlpurifier-4.4.0/HTMLPurifier.standalone.php');

		$purifier = new HTMLPurifier();
		$config = HTMLPurifier_Config::createDefault();
		$config->set('Core.Encoding', 'UTF-8');

		switch($type){

			case 'purestring':
				$data = filter_var( strip_tags($data), FILTER_SANITIZE_STRING, FILTER_FLAG_ENCODE_HIGH );
				break;

			case 'username':
				$data = preg_replace( '/[^0-9a-zA-Z\-\_]+/', '', strip_tags($data) );
				break;
		}
		
        /* HTML purifier to help prevent XSS, just in case. */
        $data = $purifier->purify( $data );

		return $data;
	}

    /**
     * generates a random string, "url safe" or with special characters as well
     * 
     * @param  integer $length  how long the string should be, default is 64
     * @return string           the random string
     */
    public static function randomGenerator($length = 64)
    {
        // Because some people just want to see the world burn..
		if( $length < 1 )
        {
			$length = 1;
		}
		
        $options = 'abcdefghijklmnopqrstuvxyz1234567890ABCDEFGHIJKLMNOPQRSTUVXYZ098765432';

		$key = '';
		$alt = mt_rand() % 2;
		for( $i = 0; $i < $length; $i++ )
        {
			$key .= $options[ ( mt_rand() % strlen($options) ) ];
		}
		
		return $key;
	}
}