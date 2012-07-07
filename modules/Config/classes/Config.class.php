<?php
class Miao_Config
{
	const SECTION_NAME_MAIN = 'main';
	const SECTION_NAME_PROJECT = 'project';
	static public function Main()
	{
		$instance = self::_getDefaultInstance();

		$path = self::SECTION_NAME_MAIN;
		$result = $instance->_get( $path );
		return $result;
	}
	static public function Project()
	{
		$instance = self::_getDefaultInstance();

		$path = self::SECTION_NAME_PROJECT;
		$result = $instance->_get( $path, '' );
		return $result;
	}
	static public function Libs( $className, $throwException = true )
	{
		$default = null;
		if ( !$throwException )
		{
			$default = array();
		}

		$instance = self::_getDefaultInstance();

		$pieces = explode( '_', $className );
		array_shift( $pieces );
		$path = implode( Miao_Config_Base::DELIMETR, $pieces );
		$result = $instance->_get( $path, $className, $default );
		return $result;
	}

	/**
	 *
	 * @var Miao_Config_Base
	 */
	private $_base;

	/**
	 *
	 * @var Miao_Config_File
	 */
	private $_file;
	public function __construct()
	{
		$this->setBase( new Miao_Config_Base( array() ) );
		$this->_file = new Miao_Config_File();
	}

	/**
	 *
	 * @return the $_base
	 */
	public function getBase()
	{
		return $this->_base;
	}

	/**
	 *
	 * @param $base Miao_Config_Base
	 */
	public function setBase( $base )
	{
		$this->_base = $base;
	}

	/**
	 * Return true if config.php exists
	 *
	 * @param string $className
	 * @return bool
	 */
	static public function checkConfig( $className )
	{
		$instance = self::_getDefaultInstance();
		$filename = $instance->_file->getFilenameByClassName( $className );

		$result = true;
		if ( !file_exists( $filename ) )
		{
			$result = false;
		}
		return $result;
	}

	static private function _getDefaultInstance()
	{
		$index = 'Miao_Config::default';
		if ( !Miao_Registry::isRegistered( $index ) )
		{
			$result = new self();
			Miao_Registry::set( $index, $result );
		}
		else
		{
			$result = Miao_Registry::get( $index );
		}
		return $result;
	}

	private function _get( $path, $className = '', $default = null )
	{
		$base = $this->getBase();

		$result = null;
		try
		{
			$result = $base->get( $path, $default );
		}
		catch ( Miao_Config_Exception_PathNotFound $e )
		{
			$ar = explode( Miao_Config_Base::DELIMETR, $path );
			if ( empty( $className ) )
			{
				$className = implode( '_', $ar );
			}

			if ( in_array( $path, array(
				self::SECTION_NAME_MAIN,
				self::SECTION_NAME_PROJECT ) ) )
			{
				$pathMain = $path;
				$funcName = '_getSection' . ucfirst( $path );
				$configData = $this->$funcName( $className );
			}
			else
			{
				$pathMain = $ar[ 0 ];
				$configData = $this->_getSectionDefault( $className );
				$configData = $configData[ $pathMain ];
			}
			if ( !is_array( $configData ) )
			{
				$configData = array( $configData );
			}

			$base->add( $pathMain, $configData );
		}
		$configData = $base->get( $path, $default );
		if ( !is_array( $configData ) )
		{
			$configData = array( $configData );
		}
		$result = new Miao_Config_Base( $configData );
		return $result;
	}

	/**
	 * Especially we don't use file_exists
	 * @param unknown_type $className
	 * @return array
	 */
	private function _getSectionDefault( $className )
	{
		$configFilename = $this->_file->getFilenameByClassName( $className );
		$configData = array();
		if ( file_exists( $configFilename ) )
		{
			$configData = include $configFilename;
		}
		return $configData;
	}

	private function _getSectionMain()
	{
		$configFilename = $this->_file->getFilenameMain();
		$configData = include $configFilename;
		return $configData;
	}

	private function _getSectionProject()
	{
		$configFilename = $this->_file->getFilenameProject();
		$configData = include $configFilename;
		$configData = $configData[ 'config' ];
		if ( isset( $configData[ 'libs' ] ) )
		{
			unset( $configData[ 'libs' ] );
		}
		return $configData;
	}
}