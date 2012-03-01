<?php

require_once("http_response.class.php");

class FUpload {
	const Path = "/uploader";
	const Host = "upload.sae.sina.com.cn";
	const SessionFileSuffix = ".session";

	const Ok             = 0;
	const SessionFinish  = 1;
	const FileRangeOnly  = 2;
	const Error          = 100;
	const FileRangeError = 105;
	const InternalError  = 500;
	const FatalInternalError = 501;

	const FileStatError  = 600;
	const FileOpenError  = 601;
	const FileReadError  = 602;
	const AddedInfoError = 603;

	const NetError       = 700;

	public function __construct($accesskey, $secretkey) {
		$this->accesskey_ = $accesskey;	
		$this->secretkey_ = $secretkey;

		$this->chunkSize_ = 1024 * 1024;

		$this->errno_ = 0;
		$this->error_ = null;
		$this->debug_ = false;
		$this->showProgress_ = false;
		$this->progressStep_ = 1;

		$this->proxyHost_ = null;
		$this->proxyPort_ = null;
		$this->proxyUser_ = null;
		$this->proxyPasswd_ = null;
	}

	public function __destruct() {
		// do nothing
	}

	public function setProxy($proxyHost, $proxyPort, $proxyUser = null, $proxyPasswd = null) {
		$this->proxyHost_ = $proxyHost;
		$this->proxyPort_ = $proxyPort;
		$this->proxyUser_ = $proxyUser;
		$this->proxyPasswd_ = $proxyPasswd;
	}

	public function setAccesskey($accesskey) {
		$this->accesskey_ = $accesskey;
	}

	public function setSecretkey($secretkey) {
		$this->secretkey_ = $secretkey;	
	}

	public function setDebugOn() {
		$this->debug_ = true;	
	}

	public function setDebugOff() {
		$this->debug_ = false;	
	}

	public function setChunkSize($chunkSize)
   	{
		$this->chunkSize_ = $chunkSize;
	}

	public function setProgressOn() {
		$this->showProgress_ = true;
	}

	public function setProgressOff()
	{
		$this->showProgress_ = false;
	}

	public function setProgressStep($step)
	{
		if ($step > 0 && $step < 100)
			$this->progressStep_ = $step;	
	}

	public function upload($file, $addedInfo = null, $force = false)
   	{
		$stat = stat($file);
		if ($stat == false) {
			$this->setError(FileStatError, "$file stat error");
			return false;
		}

		$filename = basename($file);


		if ($addedInfo && strstr($addedInfo, "\r\n")) {
			$this->setError(AddedInfoError, "$addedInfo contains '\r\n'");	
			return false;
		}

		if ($force) {
			$this->delSession($filename);
			$token = 0;
		} else {
			$token = $this->getSession($filename);
			// if no session exist
			if (!$token) $token = 0;
		}

		$fp = fopen($file, "r");
		if (!$fp) {
			$this->setError(self::FileOpenError, "$file open error");
			return false;
		}

		list($first, $resuming) = ($token != 0) ? array(false, true) : array(true, false);
		list($begin, $end) = ($first) ? array(0, $this->chunkSize_ < $stat["size"] ? $this->chunkSize_ : $stat["size"]) : array(0, 0);

		$finished = 0;
		$lastRatio = 0;
		while (!$finished) {
			$headers = array();
			$this->setHeader($headers, "Host", self::Host);
			$this->setHeader($headers, "FileName", $filename);
			$this->setHeader($headers, "Filesize", $stat["size"]);
			$this->setHeader($headers, "Expect", "");

			// set AddedInfo
			if ($addedInfo) $this->setHeader($headers, "Extra", $addedInfo);

			// set Content-Type
			if (!$resuming) {
				$this->setHeader($headers, "Content-Type", "application/octet-stream");
			}

			// set FileRange
			if (!$resuming) {
				$this->setHeader($headers, "FileRange", "$begin-$end");
			} else {
				$this->setHeader($headers, "FileRange", "0");
			}

			// get FileRange content
			if (!$resuming) {
				$content = $this->getContent($fp, $begin, $end - $begin);
				if (!$content) {
					$this->setError(self::FileReadError, "$file read error");	
					fclose($fp);
					return false;
				}
				// set Content-Length
				$this->setHeader($headers, "Content-Length", $end - $begin);
			}

			// set FileRangeChecksum
			$checksum = (!$resuming) ? md5($content) : time();
			$this->setHeader($headers, "FileRangeChecksum", $checksum);

			// set AccessKey
			$this->setHeader($headers, "AccessKey", $this->accesskey_);

			// set Signature;
			$this->setHeader($headers, "Signature", $this->signature($checksum));

			// set Token
			$this->setHeader($headers, "Token", $token);

			// set Agent
			$this->setHeader($headers, "User-Agent", "SaeSdk");

			if ($this->debug_) {
				echo "[debug] ";
				print_r($headers);
				echo "[debug] transfer $begin - $end\n";
			}

			# proxy
			if ($this->proxyHost_) {
				$host = $this->proxyHost_;
				$port = $this->proxyPort_;
				$path = "http://" . self::Host . self::Path;
				$this->setHeader($headers, "Proxy-Authorization", "Basic" . base64_encode($this->proxyUser_ . ":" . $this->proxyPasswd_));	
			} else {
				$host = self::Host;
				$port = 80;
				$path = self::Path;
			}

			# open socket
			$socket = fsockopen($host, $port, $errno, $error, 30);
			if (!$socket) {
				$this->setError(self::NetError + $errno, $error);			
				fclose($fp);
				return false;
			}

			# splice request
			$request  = "POST $path HTTP/1.1\r\n";
			$request .= implode("\r\n", $headers) . "\r\n";
			$request .= "Connection: close\r\n\r\n";
			if ($this->debug_) {
				echo "[debug] $request";
			}
			if (!$resuming) {
				$request .= $content;	
			}

			# send request
			fwrite($socket, $request);

			# read response
			$response = "";
			while ($t = fread($socket, 128)) {
				$response .= $t;
			}

			# close socket
			fclose($socket);

			if ($this->debug_) {
				echo "[debug] $response\n";	
			}

			$response = new SimpleHttpResponse($response);

			if ($response->code() != 200) {
				$this->setError($response->code(), $response->message());	
				fclose($fp);
				return false;
			}

			if (!($cm = $this->parseResponseBody($response->content()))) {
				$this->setError(self::FatalInternalError, "server error, contact administrator");
				return false;
			}

			list ($code, $message) = $cm;

			if ($code == self::FileRangeOnly ||
				$code == self::Ok || 
				$code == self::FileRangeError) {
				list($begin, $end) = explode("-", $response->headers("NextFileRange"));

				if ($this->debug_) {
					"echo [debug] next transfer $begin - $end\n";	
				}
			}

			$this->setError($code, $message);

			if ($code == self::Ok) {
				$token = $message;
				if ($first) {
					$this->setSession($filename, $token);
				}
				list($first, $resuming) = array(0, 0);
			} else if ($code == self::SessionFinish) {
				$finished = 1;
			} else if ($code == self::FileRangeOnly) {
				list($first, $resuming) = array(0, 0);	
			} else if ($code == self::FileRangeError || $code == self::Error || $code == self::InternalError) {
				if ($this->debug_) {
					echo "[debug] $message, auto retry";
				}
			} else {
				if ($this->debug_) {
					echo "[debug] $message, return";	
				}
				break;
			}

			# print progress
			if ($this->showProgress_) {
				if ($finished) {
					echo "100%\n";
				} else {
					$ratio = intval($begin * 100 / $stat["size"]);
					if ($ratio - $lastRatio >= $this->progressStep_) {
						$lastRatio = $ratio;
						echo "$ratio%\n";
					}
				}
			}

		}
		if ($finished) {
			$this->delSession($filename);	
		}
		return $finished;
	}

	private function setError($errno, $error) {
		$this->errno_ = $errno;
		$this->error_ = $error;	
	}

	private function setHeader(&$headers, $name, $value)
	{
		array_push($headers, "$name: $value");
	}

	public function errno() {
		return $this->errno_;	
	}

	public function error() {
		return $this->error_;
	}

	private function getContent($fp, $offset, $len)
	{
		fseek($fp, $offset, SEEK_SET);
		return fread($fp, $len);
	}

	private function signature($content) {
		$signature = (base64_encode(hash_hmac('sha256', $content, $this->secretkey_, true)));
		if ($this->debug_) {
			echo "[debug] content: $content" . "\n";
			echo "[debug] signature: $signature" . "\n";
		}
		return $signature;
	}

	private function getSession($file)
	{
		$file = ".$file". self::SessionFileSuffix;
		$token = false;
		if (file_exists($file)) {
			$token = file_get_contents($file);
		}
		if ($this->debug_ && $token !== false) {
			echo "[debug] get session $token\n";		
		}
		return $token;
	}

	private function setSession($file, $token)
	{
		$file = ".$file". self::SessionFileSuffix;
		$rc = file_put_contents($file, $token);
		if ($this->debug_ && $rc !== false) {
			echo "[debug] set session $token\n";	
		}
		return $rc == count($token);
	}

	private function delSession($file)
	{
		$file = ".". $file. self::SessionFileSuffix;
		@unlink($file);
		return true;
	}

	private function parseResponseBody($body)
	{
		$a = explode(":", $body, 2);
		if ($a == false) return false;
		return $a;
	}

	private $accesskey_;
	private $secretkey_;
	private $chunkSize_;

	private $errno_;
	private $error_;

	private $showProgress_;
	private $debug_;

	private $proxyHost_;
	private $proxyPort_;
	private $proxyUser_;
	private $proxyPasswd_;
}

# storengine:stor; domain:dbxyz-dbxyz; acl:reserve

class FUploadExtra
{
	public function __construct($opt = null)
	{
		$this->info_["storengine"] = "stor";
		$this->info_["acl"] = "reserve";

		if ($opt) $this->info_ = array_merge($this->info_, $opt);
	}

	# stor
	public function storengine($v = null)
   	{
		if ($v) $this->info_["storengine"] = $v;
		return $this->info_["storengine"];	
	}

	public function domain($v = null)
   	{
		if ($v) $this->info_["domain"] = $v;
		return $this->info_["domain"];
	}

	public function toString($opt = null)
	{
		if ($opt) $this->info_ = array_merge($this->info_, $opt);

		if (!array_key_exists("storengine", $this->info_) || $this->info_["storengine"] == "")
		   	throw new Exception("storengine not set", 1);
		if (!array_key_exists("domain", $this->info_) || $this->info_["domain"] == "")
		   	throw new Exception("domain not set", 2);

		$s = "";
		while (list($key, $value) = each($this->info_)) {
			$s .= "$key: $value; ";
		}
		return $s;
	}

	private $info_;
}
?>
