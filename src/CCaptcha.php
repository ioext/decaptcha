<?php

namespace dekuan\decaptcha;

use Symfony\Component\HttpFoundation\Response;
use dekuan\delib\CLib;


/**
 *	class of CCaptcha
 */
class CCaptcha
{
	var $m_sCryptSeed	= 'dekuan-seed';

	//	...
	const ARR_SUPPORTED_IMAGE_TYPE	= [ IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG ];

	//
	//	captcha code will be checked as valid at least in 3 seconds before its created,
	//	and in 1800 seconds after its created
	//
	const DEFAULT_DELAY_MIN		= 3;		//	min value of delay
	const DEFAULT_DELAY_MAX		= 1800;		//	max value of delay, 30 minutes = 30 * 60 seconds = 1800


	public function __construct( $sCryptSeed )
	{
		assert( is_string( $sCryptSeed ) || is_numeric( $sCryptSeed ) );
		assert( '' != trim( $sCryptSeed ) );

		$this->SetCryptSeed( $sCryptSeed );
	}


	//
	//	@ Public
	//	set crypt seed
	//
	public function SetCryptSeed( $sCryptSeed )
	{
		if ( CLib::IsExistingString( $sCryptSeed, true ) )
		{
			$this->m_sCryptSeed = $sCryptSeed;
		}
	}

	//
	//	@ Public
	//	check if the image type was supported
	//
	public function IsSupportedImageType( $nImgType )
	{
		return ( is_numeric( $nImgType ) && in_array( $nImgType, self::ARR_SUPPORTED_IMAGE_TYPE ) );
	}

	public function GetDefaultImageType()
	{
		return IMAGETYPE_JPEG;
	}
	public function GetMimeTypeByImageType( $nImgType )
	{
		if ( ! is_numeric( $nImgType ) )
		{
			return $this->GetDefaultImageType();
		}

		//	...
		$sRet	= '';

		switch ( $nImgType )
		{
			case IMAGETYPE_GIF :
				$sRet = 'image/gif';
				break;
			case IMAGETYPE_JPEG :
				$sRet = 'image/jpeg';
				break;
			case IMAGETYPE_PNG :
				$sRet = 'image/png';
				break;
			default:
				$sRet = 'image/jpeg';
				break;
		}

		return $sRet;
	}

	public function GetGen( $nLength = 4, $bOnlySmallLetters = false, $bOnlyNumbers = true )
	{
		$cDrawImg = new CDrawImage( $this->m_sCryptSeed );
		return $cDrawImg->GetGen( $nLength, $bOnlySmallLetters, $bOnlyNumbers );
	}

	public function GetEncryptedGen( $nLength = 4, $bOnlySmallLetters = false, $bOnlyNumbers = true )
	{
		$cDrawImg = new CDrawImage( $this->m_sCryptSeed );
		return $cDrawImg->GetEncryptedGen( $nLength, $bOnlySmallLetters, $bOnlyNumbers );
	}

	public function GetImageResponse( $sEncryptedStr = '', $nImgWidth = -1, $nImgHeight = -1, $nImgType = IMAGETYPE_JPEG )
	{
		return $this->GetImageResponseByEncryptedGen( $sEncryptedStr, $nImgWidth, $nImgHeight, $nImgType );
	}

	public function GetImageResponseByEncryptedGen( $sEncryptedStr = '', $nImgWidth = -1, $nImgHeight = -1, $nImgType = IMAGETYPE_JPEG )
	{
		if ( ! CLib::IsExistingString( $sEncryptedStr ) )
		{
			return null;
		}

		//	...
		$oRet		= null;
		$cDrawImg	= new CDrawImage( $this->m_sCryptSeed );
		$sGen		= $cDrawImg->DecryptGen( $sEncryptedStr );
		if ( CLib::IsExistingString( $sGen ) )
		{
			$oRet = $this->GetImageResponseByGen( $sGen, $nImgWidth, $nImgHeight, $nImgType );
		}

		//	...
		return $oRet;
	}

	public function GetImageResponseByGen( $sGen = '', $nImgWidth = -1, $nImgHeight = -1, $nImgType = IMAGETYPE_JPEG )
	{
		if ( ! CLib::IsExistingString( $sGen ) )
		{
			return null;
		}

		//	...
		$cResponse	= new Response();
		$cDrawImg	= new CDrawImage( $this->m_sCryptSeed );

		$vImageBuffer	= '';
		$nImgType	= $this->IsSupportedImageType( $nImgType ) ? $nImgType : $this->GetDefaultImageType();
		$sMimeType	= $this->GetMimeTypeByImageType( $nImgType );

		//	...
		$nImgWidth	= ( $nImgWidth >= 0 ) ? $nImgWidth : 132;
		$nImgHeight	= ( $nImgHeight >= 0 ) ? $nImgHeight : 32;
		$nImgWidth	= intval( $nImgWidth );
		$nImgHeight	= intval( $nImgHeight );

		if ( $nImgWidth > 0 && $nImgHeight > 0 )
		{
			$crBorder	= [ 'color' => [ 'r' => 0xff, 'g' => 0xff, 'b' => 0xff ] ];
			$crBg		= [ 'color' => [ 'r' => 0xff, 'g' => 0xff, 'b' => 0xff ] ];
			$vImageBuffer	= $cDrawImg->GetImageBuffer( $sGen, $nImgWidth, $nImgHeight, $crBg, $crBorder, $nImgType );
		}
		else
		{
			$nImgType	= IMAGETYPE_JPEG;
			$sMimeType	= $this->GetMimeTypeByImageType( $nImgType );

			//	using default configuration
			$vImageBuffer	= $cDrawImg->GetImageBuffer( $sGen );
		}

		//
		//	send response to client now
		//
		$cResponse->setContent( $vImageBuffer );
		$cResponse->setStatusCode( Response::HTTP_OK );
		$cResponse->headers->set( 'Content-Type', $sMimeType );
		$cResponse->headers->set( 'Cache-Control', 'no-store' );

		//
		//	prints the HTTP headers followed by the content
		//
		return $cResponse;
	}

	//
	//	Check if the input code is a valid captcha
	//
	public function Check
	(
		$sInputCode,
		$sEncryptedGen,
		$bCaseSensitive = false,
		$nMinDelay = self::DEFAULT_DELAY_MIN,
		$nMaxDelay = self::DEFAULT_DELAY_MAX
	)
	{
		if ( ! CLib::IsExistingString( $sInputCode ) || ! CLib::IsExistingString( $sEncryptedGen ) )
		{
			return false;
		}

		$bRet = false;
		$cDrawImg = new CDrawImage( $this->m_sCryptSeed );

		if ( $cDrawImg->VerifyInputWithEncryptedGen( $sInputCode, $sEncryptedGen, $bCaseSensitive, $nMinDelay, $nMaxDelay ) )
		{
			$bRet = true;
		}

		return $bRet;
	}

}