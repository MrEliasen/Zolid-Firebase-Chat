<?php
/**
 *  Zolid Chat - 0.1.0
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

class ZolidChat
{
    // Add any words you wish to filter out to this array.
    private $profanity = array('fuck', 'shit', 'cunt', 'nigger', 'twat', 'retard');
    private $firebaseUrl = 'zolidchat';
    private $firebase;
    private $authtoken;
    private $lastmsg = ''; // Stores the last message the user wrote, will be used to help prevent spamming.

    private $config = array(
        // your firebase secret
        'firebaseSecret' => '',
        // The max length you want a user to be able to submit
        'message_maxlength' => 500,
        // as list of the emoticons you want to use, remember to update the chat.js as well.
        'emoticons' => array(
            ':|' => '[poker]',
            '<3' => '[heart]',
            '>_<' => '[mad]',
            '>.<' => '[mad]'
        )
    );
    
    public function __construct()
    {
        require(CORE_PATH . '/libs/FirebaseAPI/FirebaseLib.php');
        $this->firebase = new fireBase('https://' . $this->firebaseUrl . '.firebaseio.com');

        // This is the system Firebase token
        $this->authtoken = $this->makeAuthToken(
            array(
                'admin' => true,
                'v' => 0,
                'iat' => time(),
                'd' => array('user' => 'admin')
            )
        );
    }

    public function executeRequest()
    {
        if( empty($_POST['request']))
        {
            return false;
        }

        switch( $_POST['request'] )
        {
            case 'getuserdata':
                echo json_encode(array(
                    'username' => $_SESSION['username'],
                    'isadmin' => $_SESSION['isadmin'],
                    'fbtoken' => $this->makeAuthToken(
                                    array(
                                        'admin' => false,
                                        'v' => 0,
                                        'iat' => time(),
                                        'd' => array('user' => $_SESSION['username'], 'label' => $this->getLabel())
                                    )
                                )
                    ));
                break;

            case 'newchatmsg':
                $this->newMessage();
                break;

            case 'deletechatmsg':
                $this->deleteMessage();
                break;

            case 'editchatmsg':
                $this->editMessage();
                break;

            default:
                return false;
                break;
        }
        exit;
    }
    
    protected function checkEmoticons( $string )
    {
        return str_replace( array_keys($this->config['emoticons']), array_values($this->config['emoticons']), $string );
    }
    
    protected function getLabel()
    {
        if( $_SESSION['isadmin'] )
        {
            return 'label-danger';
        }
        else
        {
            return 'label-info';
        }
    }
    
    protected function makeAuthToken(array $data)
    {
        return JWT::encode(
            $data,
            $this->config['firebaseSecret'],
            'HS256'
        );
    }
    
    protected function profanityFilter($message)
    {
        return preg_replace('('.implode('|', $this->profanity).')i', '[Censured]', $message);
    }
    
    protected function newMessage( $message = '', $private = '')
    {
        if( empty($_SESSION['username']) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You are not logged in. Please login to participate.'
                )
            );
            return false;
        }
        
        if( !empty($_POST['message']) )
        {
            $message = $_POST['message'];
        }
        
        if( empty($message) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You cannot send an empty message.'
                )
            );
            return false;
        }
        
        if( !empty($_POST['private']) )
        {
            $private = $_POST['private'];
        }
        
        if( empty($private) && isset($_POST['private']) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'Please select a user to send the message to.'
                )
            );
            return false;
        }
        else if( !empty($private) )
        {
            $private = Security::sanitize( $private, 'username');
            
            if( $private == $_SESSION['username'] )
            {
                echo json_encode(
                    array(
                        'status' => false,
                        'error' => 'You cannot send private messages to yourself.'
                    )
                );
                return false;
            }
            
            $reference = '/private/' . Security::sanitize( $_SESSION['username'], 'username') . '/';
            $url = '/private/' . Security::sanitize( $private, 'username') . '/';
        }
        else
        {
            $url = '/chat/';
        }
        
        // limited to the specified character limit
        if( !empty($this->config['message_maxlength']) )
        {
            $message = substr($message, 0, $this->config['message_maxlength']);
        }

        if( !empty($this->lastmsg) && $message == $this->lastmsg )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'Please do not spam by writing the same message twice after eachother.'
                )
            );
            return false;
        }
        
        $message = $this->profanityFilter( Security::sanitize( $this->checkEmoticons( $message ), 'purestring') );
        
        if( empty($message) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You cannot send an empty message.'
                )
            );
            return false;
        }
        
        $msg_id = uniqid();
        $response = $this->firebase->set($url . $msg_id . '.json?auth=' . $this->authtoken, array(
            'username' => Security::sanitize($_SESSION['username'], 'purestring'),
            'time' => date('D jS - H:i:s', time()),
            'string' => $message,
            'to' => $private,
            'edited' => '',
            'msgid' => $msg_id,
            'label' => $this->getLabel(),
            '.priority' => time()
        ));
        
        if( !empty($private) )
        {
            $msg_id = uniqid();
            $response = $this->firebase->set($reference . $msg_id . '.json?auth=' . $this->authtoken, array(
                'username' => Security::sanitize($_SESSION['username'], 'purestring'),
                'time' => date('D jS - H:i:s', time()),
                'string' => $message,
                'to' => $private,
                'edited' => '',
                'msgid' => $msg_id,
                'label' => $this->getLabel()
            ));
        }
        
        $response = json_decode($response, true);
        
        if( empty($response['error']) )
        {
            echo json_encode(
                array(
                    'status' => true
                )
            );
            return true;
        }
        else
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You do not have permission to write in the chat'
                )
            );
            return false;
        }
    }
    
    protected function deleteMessage( $messageid = 0 )
    {
        if( empty($_SESSION['username']) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You are not logged in. Please login to participate.'
                )
            );
            return false;
        }
        
        if( !$_SESSION['isadmin'] )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You do not have permission to do this'
                )
            );
            return false;
        }
        
        if( !empty($_POST['msgid']) )
        {
            $messageid = $_POST['msgid'];
        }
        
        $messageid = Security::sanitize( $messageid, 'purestring');
        
        if( empty($messageid) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'Invalid message id.'
                )
            );
            return false;
        }
        
        $this->firebase->delete('/chat/' . $messageid . '/.json?auth=' . $this->authtoken);
       
        echo json_encode(
            array(
                'status' => true
            )
        );
        return true;
    }
    
    protected function editMessage( $msgid = 0, $message = '' )
    {
        if( empty($_SESSION['username']) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You are not logged in. Please login to participate.'
                )
            );
            return false;
        }
        
        if( !$_SESSION['isadmin'] )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You do not have permission to do this'
                )
            );
            return false;
        }
        
        if( !empty($_POST['message']) )
        {
            $message = $_POST['message'];
        }
        
        if( !empty($_POST['msgid']) )
        {
            $msgid = $_POST['msgid'];
        }
        
        if( empty($message) || empty($msgid) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You cannot send an empty message.'
                )
            );
            return false;
        }
        
        // limited to 1k character
        if( !empty($this->config['message_maxlength']) )
        {
            $message = substr($message, 0, $this->config['message_maxlength']);
        }

        $message = $this->profanityFilter( Security::sanitize( $this->checkEmoticons( $message ), 'purestring') );
        $msgid = Security::sanitize( $msgid, 'purestring');
        
        if( empty($message) )
        {
            echo json_encode(
                array(
                    'status' => false,
                    'error' => 'You cannot send an empty message.'
                )
            );
            return false;
        }
        
        $response = $this->firebase->patch('/chat/' . $msgid . '.json?auth=' . $this->authtoken, array(
            'string' => $message,
            'edited' => '<i class="glyphicon glyphicon-warning-sign" data-toggle="tooltip" title="" data-original-title="Message edited by ' . $_SESSION['username'] . '"></i>'
        ));
        
        $response = json_decode($response, true);
        
        echo json_encode(
            array(
                'status' => true
            )
        );
        return true;
    }
}