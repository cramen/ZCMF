<?php

class Z_Mail_Mailer
{
	protected $options = array();
	
	public function __construct($options = array())
	{
		$this->set($options);
	}

	public function send()
	{
		$mail = new Zend_Mail('UTF-8');
		$mail->setBodyHtml($this->get('message','message'),'UTF-8',Zend_Mime::ENCODING_BASE64);
		$mail->setFrom($this->get('from','from'),$this->get('from','from'));
		$mail->addTo($this->get('to','to'));
		$mail->setSubject($this->get('theme','theme'));
		$mail->send();
	}

	public function getBody()
	{
		return $this->get('message','message');
	} 
	
	public function get($key=NULL,$default=NULL)
	{
		if ($key)
		{
			if (isset($this->options[$key]))
				return $this->options[$key];
			else
			{
				if ($default) return $default;
				return NULL;
			}
		}
		else
		{
			return $this->options;
		}
	}
	
	public function set($key,$value=NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $elkey=>$el)
			{
				$this->options[$elkey] = $el;
			}
		}
		else
		{
			if ($value!=NULL)
				$this->options[$key] = $value;
			else
				unset($this->options[$key]);
		}
		return $this;
	}	
	
	
}

?>