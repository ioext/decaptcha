<?php

namespace dekuan\decaptcha;

use dekuan\delib\CLib;


/**
 *	class of CCrypterBase64
 */
class CCrypterBase64 implements ICrypter
{
	private $m_sSeed;


	public function __construct( $sCryptSeed )
	{
		assert( is_string( $sCryptSeed ) || is_numeric( $sCryptSeed ) );
		assert( '' != trim( $sCryptSeed ) );

		//	...
		$this->m_sSeed = $sCryptSeed;
	}

	public function CryptString( $sStr )
	{
		if ( ! CLib::IsExistingString( $sStr ) )
		{
			return '';
		}

		//	...
		$nStrLen	= strlen( $sStr );
		$sRandMd5	= md5( rand( 0, 32000 ) );
		$nRandMd5Len	= strlen( $sRandMd5 );
		$nMd5Index	= 0;
		$sValue		= '';

		for ( $i = 0; $i < $nStrLen; $i ++ )
		{
			if ( $nMd5Index == $nRandMd5Len )
			{
				$nMd5Index = 0;
			}

			$sValue .=
				mb_substr( $sRandMd5, $nMd5Index, 1 ) .
				( mb_substr( $sStr, $i, 1 ) ^ mb_substr( $sRandMd5, $nMd5Index, 1 ) );
			$nMd5Index ++;
		}

		return $this->_FilterChars( $this->_Encoding( $sValue ) );
	}

	public function DecryptString( $sStr )
	{
		if ( ! CLib::IsExistingString( $sStr ) )
		{
			return '';
		}

		$sRet		= '';
		$nStrLen	= strlen( $sStr );
		$sStr		= $this->_Encoding( $this->_ResumeChars( $sStr ) );

		for ( $i = 0; $i < $nStrLen; $i ++ )
		{
			$sMd5Value = mb_substr( $sStr, $i, 1 );
			$i ++;
			$sRet .= ( mb_substr( $sStr, $i, 1 ) ^ $sMd5Value );
		}

		return $sRet;
	}



	////////////////////////////////////////////////////////////////////////////////
	//	Private
	//

	private function _Encoding( $sStr )
	{
		$sRet		= '';
		$sMd5Value	= md5( $this->m_sSeed );
		$nMd5ValueLen	= strlen( $sMd5Value );
		$nMd5Index	= 0;

		for ( $strIndex = 0; $strIndex < strlen( $sStr ); $strIndex ++ )
		{
			if ( $nMd5Index == $nMd5ValueLen )
			{
				$nMd5Index = 0;
			}

			$sRet .= mb_substr( $sStr, $strIndex, 1 ) ^ mb_substr( $sMd5Value, $nMd5Index, 1 );
			$nMd5Index ++;
		}

		return $sRet;
	}

	private function _FilterChars( $sStr )
	{
		return base64_encode( $sStr );
	}

	private function _ResumeChars( $sStr )
	{
		return base64_decode( $sStr );
	}
}

