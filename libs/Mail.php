<?php
/**
 * Created by PhpStorm.
 * User: kino
 * Date: 2016/03/23
 * Time: 17:58
 */

class Mail
{
	private $subject;
	private $to;
	private $from;
	private $cc;
	private $bcc;
	private $body;
	private $contentsType;

	/**
	 * @param String $subject
	 */
	public function setSubject($subject)
	{
		$this->subject = $subject;
	}

	/**
	 * @param String $to
	 */
	public function setTo($to)
	{
		$this->to = $to;
	}

	/**
	 * @param String $from
	 */
	public function setFrom($from)
	{
		$this->from = $from;
	}

	/**
	 * @param mixed $cc
	 */
	public function setCc($cc)
	{
		$this->cc = $cc;
	}

	/**
	 * @param String $bcc
	 */
	public function setBcc($bcc)
	{
		$this->bcc = $bcc;
	}

	/**
	 * @param String $body
	 */
	public function setBody($body)
	{
		$this->body = $body;
	}

	/**
	 * @param String $contentsType
	 */
	public function setContentsType($contentsType)
	{
		$this->contentsType = $contentsType;
	}
	
	public function send()
	{
		if (!$this->validMail($this->to)) {
			return false;
		}

		if ((is_array($this->from) && !$this->validFromMail($this->from))
			|| (!is_array($this->from) && !$this->validMail($this->from))) {
			return false;
		}

		if (!is_null($this->cc) && !$this->validMail($this->cc)) {
			return false;
		}

		if (!is_null($this->bcc) && !$this->validMail($this->bcc)) {
			return false;
		}

		if (!is_null($this->bcc) && !$this->validMail($this->bcc)) {
			return false;
		}

		if (!is_null($this->contentsType) && !$this->validContentsType($this->contentsType)) {
			return false;
		}

		if (is_array($this->from)) {
			$mailList = array();
			foreach ($this->from as $name => $mail) {
				$mailList[] = sprintf('%s <%s>', $name, $mail);
			}
			$header = "From: ".join(',', $mailList)."\r\n";
		} else {
			$header = "From: ".$this->from."\r\n";
		}
		if (!is_null($this->cc)) {
			$header .= "CC: ".$this->cc."\r\n";
		}
		if (!is_null($this->bcc)) {
			$header .= "BCC: ".$this->bcc."\r\n";
		}
		if (!is_null($this->contentsType)) {
			$header .= "MIME-Version: 1.0\r\n";
			$header .= "Content-type: ".$this->contentsType."; charset=ISO-2022-JP\r\n";
		}

		mb_internal_encoding("UTF-8");
		return mb_send_mail($this->to, $this->subject, $this->body, $header);
	}

	private function validFromMail($from) {
		foreach ($from as $name => $mail) {
			if (!$this->validMail($mail)) {
				return false;
			}
		}

		return true;
	}

	private function validMail($mailAddress) {
		return preg_match('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/iD', $mailAddress) == 1;
	}

	private function validContentsType($contentsType) {
		return preg_match("/\r\n|\r|\n/", $contentsType) == 0;
	}

}