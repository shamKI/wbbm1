<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Mail {
    private $mail;
    private $CI;

    function __construct() {
        require_once APPPATH . 'third_party/PHPMailer/class.phpmailer.php';
        
        $this->CI =& get_instance();
        $this->mail = new PHPMailer();

        //initialization 
        $this->mail->isSMTP(); 
        $this->mail->SMTPAuth   = $this->CI->config->item("smtp_auth"); 
        $this->mail->SMTPSecure = $this->CI->config->item("smtp_secure");   
        $this->mail->Host       = $this->CI->config->item("host");       
        $this->mail->Port       = $this->CI->config->item("port");                    
        $this->mail->Username   = $this->CI->config->item("user_name");   
        $this->mail->Password   = $this->CI->config->item("password");            
        $this->mail->isHTML($this->CI->config->item("is_html"));
        $this->mail->From	= $this->CI->config->item("from"); 
        $this->mail->FromName	= $this->CI->config->item("from_name"); 

    }//end of function

    function send($data_set){
        $this->mail->addAddress($data_set["recipient_address"], $data_set["recipient_name"]);
        $this->mail->Subject = $data_set["subject"] ;
        $this->mail->Body    = $data_set["body"] ;
        return $this->mail->send();
    }//end of function
    
}//end of class	
//end of file