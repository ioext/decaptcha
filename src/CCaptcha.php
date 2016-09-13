<?php

namespace dekuan\decaptcha;

use dekuan\delib\CLib;


/**
 *	class of CCaptcha
 */
class CCaptcha
{
	var $m_sCryptSeed	= 'dekuan-seed';


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


	public function GenerateEncryptStr( $strLength = 4, $onlySmallLetters = false, $onlyNumbers = true )
	{
		$cDrawImg = new CDrawImage( $this->m_sCryptSeed );
		return $cDrawImg->GenerateEncryptStr( $strLength, $onlySmallLetters, $onlyNumbers );
	}

	public function CreateImage( $sStr = '',  $sOp = '', $nImgWidth = -1, $nImgHeight = -1 )
	{
		$cDrawImg	= new CDrawImage( $this->m_sCryptSeed );

		$sRandomStr	= isset( $sStr ) ? $sStr : '';
		$sOp		= isset( $sOp ) ? $sOp : '';

		$nImgWidth	= ( $nImgWidth >= 0 ) ? $nImgWidth : 132;
		$nImgHeight	= ( $nImgHeight >= 0 ) ? $nImgHeight : 32;
		$nImgWidth	= intval( $nImgWidth );
		$nImgHeight	= intval( $nImgHeight );

		if ( 'refresh' == $sOp )
		{
			//
			//	output a random string
			//
			echo $this->GenerateEncryptStr();
			exit();
		}
		else
		{
			//
			//	generate a image
			//
			$sRandomStr = $cDrawImg->PickupStringForImg( $sRandomStr );

			if ( 0 < $nImgWidth && 0 < $nImgHeight )
			{
				$crBorder	= [ 'color' => [ 'r' => 0xff, 'g' => 0xff, 'b' => 0xff ] ];
				$crBg		= [ 'color' => [ 'r' => 0xff, 'g' => 0xff, 'b' => 0xff ] ];
				return $cDrawImg->GenerateOutputImg( $sRandomStr, $nImgWidth, $nImgHeight, $crBg, $crBorder );
			}
			else
			{
				return $cDrawImg->GenerateOutputImg( $sRandomStr );
			}
		}
	}

	//
	//	Check
	//
	public function Check( $sCheckCode, $sCapRand, $bCaseSensitive = false, $nMinDelay = 0, $nMaxDelay = 0 )
	{
		if ( empty( $sCheckCode ) || empty( $sCapRand ) )
		{
			return false;
		}

		$bRet = false;
		$cDrawImg = new CDrawImage( $this->m_sCryptSeed );

		if ( $cDrawImg->VerifyInputStr( $sCheckCode, $sCapRand , $bCaseSensitive, $nMinDelay, $nMaxDelay ) )
		{
			$bRet = true;
		}

		return $bRet;
	}

}