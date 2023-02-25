<?php

namespace ioext\decaptcha;


/**
 *	Crypter
 */
interface ICrypter
{
	public function CryptString( $sStr );
	public function DecryptString( $sStr );
}