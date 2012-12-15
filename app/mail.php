<?php

/**
 * E-Mail Address
 *
 * @author Esther Brunner <esther@uxaria.org>
 */
class EBEmail {

  private $mail, $name;

  /**
   * Construct e-mail address and check validity
   *
   * @param string $mail - e-mail-address
	 * @param string $name - full name of address holder (optional, default: none)
   */
  public function __construct($mail, $name = ''){
    if (!preg_match('/^[A-z][A-z0-9_\-\.]*\@[A-z0-9_-]+(\.[A-z0-9_-]+)*\.[A-z]{2,6}$/', $mail))
      throw new Exception('Ungültige E-Mail-Adresse: '.$mail, E_USER_WARNING);
    $this->mail = $mail;
    $this->name = $name;
  }
  
  /**
   * Return a RFC 2822 compliant mail address
   *
   * @return string - RFC 2822 compliant mail address, i.e. 'Esther Brunner <esther@uxaria.org>'
   */
  public function __toString(){
    return ($this->name ? $this->name.' <'.$this->mail.'>' : $this->mail);
  }

}

/**
 * Class for sending an E-Mail
 *
 * @author Esther Brunner <esther@uxaria.org>
 */
class EBMail {

  const NL = "\n", WS = '              ', CHARSET = 'ISO-8859-1';

  private $to = array(), $subject = '', $headers = array(), $content = '', $attachments = array();
	
	/**
	 * Constructor sets the mandatory From header
	 *
	 * @param UxEmail $mail - e-mail address
	 */
  public function __construct(EBEmail $mail){
	  $this->headers[] = $this->clean('From: '.$mail);
	}

  /**
	 * Add To, Cc and Bcc recipients
	 *
	 * @param UxEmail $mail - e-mail address
	 */
  public function to(EBEmail $mail){  $this->to[]      = $this->clean($mail); }
	public function cc(EBEmail $mail){  $this->headers[] = $this->clean('Cc: '.$mail); }
	public function bcc(EBEmail $mail){ $this->headers[] = $this->clean('Bcc: '.$mail); }
	
	/**
	 * Set the mail subject
	 *
	 * @param string $text - mail subject
	 */
	public function subject($text){
	  $this->subject = $this->clean($text);
	}
	
	/**
	 * Append to the mail message content
	 *
	 * @param string $text - mail message content
	 */
	public function append($text){
	  $this->content .= $text;
	}
	
	/**
	 * Add an attachment
	 *
	 * @param string $mime: MIME type like 'application/pdf'
	 * @param string $name: filename of attachment
	 * @param string $content: attachment content
	 * @param string $charset: encoding charset of attachment
	 */
	public function attach($mime, $name, $content, $charset = self::CHARSET){
	  $attachment = 'Content-Type: '.$mime.';'.self::NL.
		  self::WS.'charset='.$charset.';'.self::NL.
			self::WS.'name="'.$name.'"'.self::NL.
			'Content-Disposition: attachment;'.self::NL.
			self::WS.'filename="'.$name.'"'.self::NL;
		if (substr($mime, 0, 5) == 'text/') $attachment .= self::NL.$this->encode($content, $charset, 'auto');
		else $attachment .= self::NL.$content;
		$this->attachments[] = $attachment;
	}
	
	/**
	 * Send the mail
	 *
	 * @return bool - success
	 */
	public function send(){
	  $to = $this->encode(join(', ', $this->to));
		if (!empty($this->attachments)){
		  $boundary = md5(time()).rand(1000, 9999); // random value to separate MIME parts
		  $this->headers[] = 'Content-Type: multipart/mixed;'.self::NL.
			  self::WS.'boundary="'.$boundary.'"'.self::NL.self::NL;
		  $glue = self::NL.'--'.$boundary.self::NL;
			$message = 'This is a message in MIME format.'.self::NL.$glue.
			  'Content-Type: text/plain;'.self::NL.
				self::WS.'charset='.strtolower(self::CHARSET).self::NL.
				'Content-Transfer-Encoding: 8bit'.self::NL.self::NL.
				$this->encode($this->content).self::NL.
				$glue.join($glue, $this->attachments).$glue;
		} else {
		  $message = $this->encode($this->content);
		}
		$headers = $this->encode(join(self::NL, $this->headers));
		$subject = $this->encode($this->subject);
	  return mail($to, $subject, $message, $headers);
	}
	
	/**
	 * Strip illegal chars from mail headers
	 *
	 * @param  string $text - mail header
	 * @return string       - cleaned mail header
	 */
	private function clean($text){
	  return preg_replace('/(\\n+|\\r+|%0A|%0D)/i', '', $text);
	}
	
	/**
	 * Wrapper for mb_convert_encoding: convert to ISO-8859-1
	 *
	 * @param  string $text    - text to convert
	 * @param  string $charset - charset to change encoding to
	 * @return string          - converted string
	 */
	private function encode($text, $charset = self::CHARSET){
	  return mb_convert_encoding($text, $charset, 'auto');
	}

}