<?php

namespace dekuan\decaptcha;

use dekuan\delib\CLib;


/**
 *	class of CDeCaptcha
 */
class CDrawImage
{
	//	specify your own seed here
	var $m_sCryptSeed	= 'dekuan';

	//	per sec to validate, the generated time of the watermark should be between them
	var $m_nMinDelay	= 3;
	var $m_nMaxDelay	= 1800;	//	30 minutes = 30*60 seconds = 1800

	//	how many noise point as you wish
	var $m_nNoise		= 30;

	//	to draw the line random, should be between 1 and 100, bigger number with more lines
	var $m_nDustVsScratches	= 90;

	var $m_arrFontColor	= [ '119,153,221', '249,00,102', '255,153,0', '0,128,0' ];
	var $m_ArrFont		= [ 'verdana.ttf', 'impact.ttf', 'comic.ttf', 'consola.ttf', 'trebucbi.ttf', 'lucon.ttf' ];


	public function __construct( $sCryptSeed = '' )
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
	//	generate image for output
	//
	public function GenerateOutputImg
	(
		$sDisplayStr,
		$nImgWidth	= 160,
		$nImgHeight	= 45,
		$arrBgColor	= [],
		$arrBorderColor	= []
	)
	{
		header( "Content-type: image/jpeg" );

		$oOutputImg	= imagecreatetruecolor( $nImgWidth, $nImgHeight );

		$arrBorderColor	= $this->_CheckColor( $arrBorderColor, $oOutputImg, 0 );
		$arrBgColor	= $this->_CheckColor( $arrBgColor, $oOutputImg, 255 );

		//	画一矩形并填充
		imagefilledrectangle( $oOutputImg, 0, 0, $nImgWidth, $nImgHeight, $arrBgColor );

		//	draw the borders
		imagefilledrectangle( $oOutputImg, 0, 0, $nImgWidth, 0, $arrBorderColor );
		imagefilledrectangle( $oOutputImg, $nImgWidth - 1, 0, $nImgWidth - 1, $nImgHeight - 1, $arrBorderColor );
		imagefilledrectangle( $oOutputImg, 0, 0, 0, $nImgHeight - 1, $arrBorderColor );
		imagefilledrectangle( $oOutputImg, 0, $nImgHeight - 1, $nImgWidth, $nImgHeight - 1, $arrBorderColor );

		$sFontPath	= dirname( __FILE__ ) . '/fonts/';

		$nMinAngle	= -35;
		$nMaxAngle	= 35;
		$nMinFontSize	= 15;
		$nMaxFontSize	= 30;
		$nYOffset	= 6;
		$nYValue	= $nImgHeight / 2 + $nYOffset;
		$nXValue	= $nImgWidth / 15;
		$nXAddValue	= $nImgWidth / strlen( $sDisplayStr );

		for ( $nIndex = 0, $nMax = strlen( $sDisplayStr ); $nIndex < $nMax; $nIndex ++ )
		{
			if ( 0 != $nIndex )
			{
				$nXValue += $nXAddValue + rand( -2, 2 );
			}

			$nAngle		= rand( $nMinAngle, $nMaxAngle );
			$nFontsIndex	= array_rand( $this->m_ArrFont );
			$nFontSize	= rand( $nMinFontSize, $nMinFontSize );
			$sDisplayChar	= substr( $sDisplayStr, $nIndex, 1 );
			$sFontColorKey	= array_rand( $this->m_arrFontColor );

			$arrColor	= explode( ',', $this->m_arrFontColor[ $sFontColorKey ] );
			$clrFontColor	= imagecolorallocate( $oOutputImg, $arrColor[ 0 ], $arrColor[ 1 ], $arrColor[ 2 ] );

			@ imagettftext
			(
				$oOutputImg,
				$nFontSize,
				$nAngle,
				$nXValue,
				$nYValue,
				$clrFontColor,
				$sFontPath . $this->m_ArrFont[ $nFontsIndex ],
				$sDisplayChar
			);
		}

		$sNoiseColorKey	= array_rand( $this->m_arrFontColor );
		$arrColor[]	= $this->m_arrFontColor[ $sNoiseColorKey ];

		//	...
		$this->_GenerateNoise( $oOutputImg, $nImgWidth, $nImgHeight, $arrColor );

		return @ imagejpeg( $oOutputImg );
	}

	public function GenerateEncryptStr( $strLength = 4, $onlySmallLetters = false, $onlyNumbers = true )
	{
		$hCrypter	= $this->_GetCryptHandler();

		$sStr		= $this->_GenerateRandomString( $strLength, $onlySmallLetters, $onlyNumbers );
		$sCap		= $hCrypter->CryptString( $sStr );
		$sTime		= $hCrypter->CryptString( time() );
		$sIp		= $hCrypter->CryptString( $this->_GetRemoteAddr() );

		return rawurlencode( $sCap . '.' . $sIp . '.' . $sTime );
	}

	public function VerifyInputStr
	(
		$sInputStr,
		$sVerifyStr,
		$bCaseSensitive	= false,
		$nMinDelay	= 0,
		$nMaxDelay	= 0
	)
	{
		//
		//	sInputStr	- [in] 用户输入的串
		//	$sVerifyStr	- [in] 系统生成的校验串
		//	$bCaseSensitive	- [in/opt]
		//	$nMinDelay	- [in/opt]
		//	$nMaxDelay	- [in/opt]
		//
		if ( '' == $sInputStr || '' == $sVerifyStr )
		{
			return false;
		}

		//	...
		$bRet	= false;
		$hCrypter	= $this->_GetCryptHandler();

		//	...
		$arrVerifyStr	= explode( '.', rawurldecode( $sVerifyStr ) );
		if ( is_array( $arrVerifyStr ) && 3 == count( $arrVerifyStr ) )
		{
			$sEn	= isset( $arrVerifyStr[ 0 ] ) ? $arrVerifyStr[ 0 ] : '';
			$sIp	= isset( $arrVerifyStr[ 1 ] ) ? $arrVerifyStr[ 1 ] : '';
			$sTime	= isset( $arrVerifyStr[ 2 ] ) ? $arrVerifyStr[ 2 ] : '';

			if ( 0 == $nMinDelay )
			{
				$nMinDelay = $this->m_nMinDelay;
			}
			if ( 0 == $nMaxDelay )
			{
				$nMaxDelay = $this->m_nMaxDelay;
			}
			if ( false == $bCaseSensitive )
			{
				$sInputStr	= strtolower( $sInputStr );
				$sVerifyStr	= strtolower( $sVerifyStr );
			}

			if ( '' != $sEn && '' != $sIp && '' != $sTime )
			{
				$sRemoteAddr	= $this->_GetRemoteAddr();
				$nRemoteAddrLen	= strlen( $sRemoteAddr );

				$sDecryptedEn	= $hCrypter->DecryptString( $sEn );
				$sDecryptedIp	= $hCrypter->DecryptString( $sIp );
				$nDecryptedTime = intval( $hCrypter->DecryptString( $sTime ) );

				if ( $sInputStr == $sDecryptedEn &&
					0 == strncmp( $sRemoteAddr, $sDecryptedIp, $nRemoteAddrLen ) )
				{
					if ( ( $nDecryptedTime + $nMinDelay <= time() ) && ( $nDecryptedTime + $nMaxDelay >= time() ) )
					{
						$bRet = true;
					}
				}
			}
		}

		return $bRet;
	}

	public function PickupStringForImg( $sCryptedStr )
	{
		$sRet = '1108';

		//	...
		$arrCryptedStr = explode( '.', $sCryptedStr );
		if ( is_array( $arrCryptedStr ) && count( $arrCryptedStr ) > 0 )
		{
			$sEnStr = isset( $arrCryptedStr[ 0 ] ) ? $arrCryptedStr[ 0 ] : '';
			if ( '' != $sEnStr )
			{
				$hCrypter	= $this->_GetCryptHandler();
				$sRet		= $hCrypter->DecryptString( $sEnStr );
			}
		}

		//	...
		return $sRet;
	}

	////////////////////////////////////////////////////////////////////////////////
	//	Private
	//

	private function _GetCryptHandler()
	{
		static $hHandler;

		if ( false == $hHandler instanceof CCrypterBase64 )
		{
			$hHandler = new CCrypterBase64( $this->m_sCryptSeed );
		}

		return $hHandler;
	}

	private function _GetRemoteAddr()
	{
		$sRet		= '';
		$sRemoteAddr	= '';

		if ( is_array( $_SERVER ) && array_key_exists( 'REMOTE_ADDR', $_SERVER ) )
		{
			$sRemoteAddr	= $_SERVER['REMOTE_ADDR'];
			if ( is_string( $sRemoteAddr ) || is_numeric( $sRemoteAddr ) )
			{
				$sRet = str_replace( '.', '', $sRemoteAddr );
			}
		}

		return $sRet;
	}

	private function _GenerateRandomString( $nStrLength = 4, $bOnlySmallLetters = false, $bOnlyNumbers = true )
	{
		$sResultStr	= "";
		$nRandNum	= 0;

		for ( $i = 0; $i < $nStrLength; $i ++ )
		{
			$nMin	= 0;
			$nMax	= 0;

			if ( $bOnlyNumbers )
			{
				$nRandNum = 3;
			}
			else if ( $bOnlySmallLetters )
			{
				$nRandNum = 2;
			}
			else
			{
				$nRandNum = rand( 1, 3 );
			}

			switch ( $nRandNum )
			{
			case 1:
				//	A-Z
				$nMin = 65;
				$nMax = 90;
				break;
			case 2:
				//	a-z
				$nMin = 97;
				$nMax = 122;
				break;
			case 3:
				//	2-9
				$nMin = 50;
				$nMax = 57;
				break;
			}

			$sResultStr .= chr( rand( $nMin, $nMax ) );
		}

		return str_replace
		(
			"O", "A",
			str_replace
			(
				"o", "B",
				str_replace
				(
					"l", "C",
					str_replace( "I", "D", $sResultStr )
				)
			)
		);
	}

	private function _GenerateNoise( $oImage, $nWidth, $nHeight, $arrColor )
	{
		$nMaxX	= $nWidth - 1;
		$nMaxY	= $nHeight - 1;

		$arrColor = imagecolorallocate( $oImage, $arrColor[ 0 ], $arrColor[ 1 ], $arrColor[ 2 ] );

		for ( $i = 0; $i < $this->m_nNoise; ++ $i )
		{
			if ( rand( 1, 100 ) > $this->m_nDustVsScratches )
			{
				imageline
				(
					$oImage,
					rand( 0, $nMaxX ),
					rand( 0, $nMaxY ),
					rand( 0, $nMaxX ),
					rand( 0, $nMaxY ),
					$arrColor
				);
			}
			else
			{
				imagesetpixel( $oImage, rand( 0, $nMaxX ), rand( 0, $nMaxY ), $arrColor );
			}
		}
	}

	private function _CheckColor( $arrInfo, $oOutputImg, $nDefaultColor = 255 )
	{
		//
		//	infoArr	- [in] Array( 'color' => Array( 'r', 'g', 'b' ) )
		//

		$arrColor = isset( $arrInfo[ 'color' ] ) ? $arrInfo[ 'color' ] : '';
		if ( false == empty( $arrColor ) )
		{
			$nRed		= isset( $arrColor[ 'r' ] ) ? $arrColor[ 'r' ] : $nDefaultColor;
			$nGreen		= isset( $arrColor[ 'g' ] ) ? $arrColor[ 'g' ] : $nDefaultColor;
			$nBlue		= isset( $arrColor[ 'b' ] ) ? $arrColor[ 'b' ] : $nDefaultColor;
			$clrColor	= imagecolorallocate( $oOutputImg, $nRed, $nGreen, $nBlue );
		}
		else
		{
			$clrColor = imagecolorallocate( $oOutputImg, $nDefaultColor, $nDefaultColor, $nDefaultColor );
		}

		return $clrColor;
	}
}
