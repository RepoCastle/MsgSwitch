<?php

class SimpleHttpResponse
{
	public function __construct($response)
	{
		list($headerString, $this->content_) = explode("\r\n\r\n", $response, 2);
		list($statusLine, $headerString) = explode("\r\n", $headerString, 2);
		list( , $this->code_, $this->message_) = explode(" ", $statusLine, 3);
		foreach (explode("\r\n", $headerString) as $item) {
			list($name, $value) = explode(":", $item);
			$this->headers_[strtolower($name)] = ltrim($value);
		}
	}

	public function code()
	{
		return $this->code_;	
	}

	public function message()
	{
		return $this->message_;	
	}

	public function headers($name=null)
	{
		if ($name) {
			$name = strtolower($name);	
			if (array_key_exists($name, $this->headers_)) {
				return $this->headers_[$name];	
			} else {
				return null;	
			}
		}
		return $this->headers_;	
	}

	public function content()
	{
		
		return $this->content_;	
	}

	private $code_;
	private $message_;
	private $headers_;
	private $content_;
}
?>
