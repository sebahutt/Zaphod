<?php
/**
 * BaseManager defines generic function for all files manager classes
 */
abstract class BaseManager {
	/**
	 * Configuration data
	 * @var Config
	 */
	protected $_config;
	/**
	 * Base path of the root folder in the local file system
	 * @var string|boolean
	 */
	protected $_basePath;
	/**
	 * Web path of the root folder (if available)
	 * @var string|boolean
	 */
	protected $_webRoot;
	/**
	 * Root folder element
	 * @var Folder
	 */
	protected $_root;
	
	/**
	 * Class constructor
	 * 
	 * @param Config|array $config manager configuration data object or an array
	 */
	public function __construct($config)
	{
		$this->_config = is_array($config) ? new Config($config) : $config;
	}
	
	/**
	 * Check if the file manager is in the local files system
	 * 
	 * @return boolean true if it is in the local files system, else false
	 */
	public function isLocal()
	{
		return ($this->getLocalPath() !== false);
	}
	
	/**
	 * Indique si le gestionnaire est parent du chemin demandé
	 * 
	 * @param string $path le chemin à tester
	 * @return boolean une confirmation
	 */
	public function containsPath($path)
	{
		$root = $this->getLocalPath();
		return (strlen($root) > 0 and strpos($path, $root) === 0);
	}
	
	/**
	 * Get the root folder of the file manager
	 * 
	 * @return Folder the corresponding folder
	 */
	public function getRoot()
	{
		// Caching
		if (!isset($this->_root))
		{
			$this->_root = $this->getFolder('');
		}
		
		return $this->_root;
	}
	
	/**
	 * Get the local path (in the file system) of the root of the file manager, if available,
	 * else return false
	 * 
	 * @return string|boolean the path or false
	 */
	public function getLocalPath()
	{
		// Caching
		if (!isset($this->_basePath))
		{
			$this->_basePath = $this->_config->get('root', false);
		}
		
		return $this->_basePath;
	}
	
	/**
	 * Get the web path of the root of the file manager, if available (files are accessible in HTTP),
	 * else return false
	 * 
	 * @return string|boolean the path or false
	 */
	public function getWebPath()
	{
		// Caching
		if (!isset($this->_webRoot))
		{
			$this->_webRoot = $this->_config->get('web', false);
		}
		
		return $this->_webRoot;
	}
	
	/**
	 * Get a file element within the file manager
	 * 
	 * @param string $path the path relative to the file manager root
	 * @return File the file object
	 */
	public function getFile($path)
	{
		return new File($path, $this);
	}
	
	/**
	 * Get a folder element within the file manager
	 * 
	 * @param string $path the path relative to the file manager root
	 * @return Folder the folder object
	 */
	public function getFolder($path)
	{
		return new Folder($path, $this);
	}
	
	/**
	 * Get the mime type of a file (default function using file extension)
	 * 
	 * @param string $path the file path
	 * @param string $default the default mime if it can't be detected
	 * @return boolean false, mime type detection isn't available over FTP
	 */
	public function getPathMimeType($path, $default = 'text/plain')
	{
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		return isset(self::$mimetypes[$extension]) ? self::$mimetypes[$extension] : $default;
	}
	
	/**
	 * List of mime types
	 * @var array
	 */
	public static $mimetypes = array(
		'dwg'			=> 'application/acad',
		'asd'			=> 'application/astound',
		'tsp'			=> 'application/dsptype',
		'dxf'			=> 'application/dxf',
		'spl'			=> 'application/futuresplash',
		'gz'			=> 'application/gzip',
		'ptlk'			=> 'application/listenup',
		'hqx'			=> 'application/mac-binhex40',
		'mbd'			=> 'application/mbedlet',
		'mif'			=> 'application/mif',
		'xls'			=> 'application/msexcel',
		'xla'			=> 'application/msexcel',
		'hlp'			=> 'application/mshelp',
		'chm'			=> 'application/mshelp',
		'ppt'			=> 'application/mspowerpoint',
		'ppz'			=> 'application/mspowerpoint',
		'pps'			=> 'application/mspowerpoint',
		'pot'			=> 'application/mspowerpoint',
		'doc'			=> 'application/msword',
		'dot'			=> 'application/msword',
		'bin'			=> 'application/octet-stream',
		'exe'			=> 'application/octet-stream',
		'com'			=> 'application/octet-stream',
		'dll'			=> 'application/octet-stream',
		'class'			=> 'application/octet-stream',
		'oda'			=> 'application/oda',
		'pdf'			=> 'application/pdf',
		'pub'			=> 'application/mspublisher',
		'ai'			=> 'application/postscript',
		'eps'			=> 'application/postscript',
		'ps'			=> 'application/postscript',
		'rar'			=> 'application/rar',
		'rtc'			=> 'application/rtc',
		'rtf'			=> 'application/rtf',
		'smp'			=> 'application/studiom',
		'swc'			=> 'application/swc',
		'tbk'			=> 'application/toolbook',
		'vmd'			=> 'application/vocaltec-media-desc',
		'vmf'			=> 'application/vocaltec-media-file',
		'bcpio'			=> 'application/x-bcpio',
		'z'				=> 'application/x-compress',
		'cpio'			=> 'application/x-cpio',
		'csh'			=> 'application/x-csh',
		'dcr'			=> 'application/x-director',
		'dir'			=> 'application/x-director',
		'dxr'			=> 'application/x-director',
		'dvi'			=> 'application/x-dvi',
		'evy'			=> 'application/x-envoy',
		'gtar'			=> 'application/x-gtar',
		'hdf'			=> 'application/x-hdf',
		'php'			=> 'application/x-httpd-php',
		'php3'			=> 'application/x-httpd-php',
		'php4'			=> 'application/x-httpd-php',
		'php5'			=> 'application/x-httpd-php',
		'phtml'			=> 'application/x-httpd-php',
		'js'			=> 'application/x-javascript',
		'latex'			=> 'application/x-latex',
		'bin'			=> 'application/x-macbinary',
		'mif'			=> 'application/x-mif',
		'cdf'			=> 'application/x-netcdf',
		'nc'			=> 'application/x-netcdf',
		'nsc'			=> 'application/x-nschat',
		'sh'			=> 'application/x-sh',
		'shar'			=> 'application/x-shar',
		'swf'			=> 'application/x-shockwave-flash',
		'cab'			=> 'application/x-shockwave-flash',
		'spr'			=> 'application/x-sprite',
		'sprite'		=> 'application/x-sprite',
		'sit'			=> 'application/x-stuffit',
		'sca'			=> 'application/x-supercard',
		'sv4cpio'		=> 'application/x-sv4cpio',
		'sv4crc'		=> 'application/x-sv4crc',
		'tar'			=> 'application/x-tar',
		'tcl'			=> 'application/x-tcl',
		'tex'			=> 'application/x-tex',
		'texinfo'		=> 'application/x-texinfo',
		'texi'			=> 'application/x-texinfo',
		't'				=> 'application/x-troff',
		'tr'			=> 'application/x-troff',
		'roff'			=> 'application/x-troff',
		'man'			=> 'application/x-troff-man',
		'troff'			=> 'application/x-troff-man',
		'me'			=> 'application/x-troff-me',
		'ustar'			=> 'application/x-ustar',
		'src'			=> 'application/x-wais-source',
		'zip'			=> 'application/zip',
		'au'			=> 'audio/basic',
		'snd'			=> 'audio/basic',
		'es'			=> 'audio/echospeech',
		'tsi'			=> 'audio/tsplayer',
		'vox'			=> 'audio/voxware',
		'aif'			=> 'audio/x-aiff',
		'aiff'			=> 'audio/x-aiff',
		'aifc'			=> 'audio/x-aiff',
		'dus'			=> 'audio/x-dspeeh',
		'cht'			=> 'audio/x-dspeeh',
		'mid'			=> 'audio/x-midi',
		'midi'			=> 'audio/x-midi',
		'mp2'			=> 'audio/x-mpeg',
		'ram'			=> 'audio/x-pn-realaudio',
		'ra'			=> 'audio/x-pn-realaudio',
		'rpm'			=> 'audio/x-pn-realaudio-plugin',
		'stream'		=> 'audio/x-qt-stream',
		'wav'			=> 'audio/x-wav',
		'dwf'			=> 'drawing/x-dwf',
		'cod'			=> 'image/cis-cod',
		'ras'			=> 'image/cmu-raster',
		'fif'			=> 'image/fif',
		'gif'			=> 'image/gif',
		'ief'			=> 'image/ief',
		'iff'			=> 'image/iff',
		'jb2'			=> 'image/jb2',
		'jp2'			=> 'image/jp2',
		'jpc'			=> 'image/jpc',
		'jpeg'			=> 'image/jpeg',
		'jpg'			=> 'image/jpeg',
		'jpe'			=> 'image/jpeg',
		'jpx'			=> 'image/jpx',
		'png'			=> 'image/png',
		'tiff'			=> 'image/tiff',
		'tif'			=> 'image/tiff',
		'mcf'			=> 'image/vasa',
		'bmp'			=> 'image/bmp',
		'psd'			=> 'image/psd',
		'wbmp'			=> 'image/vnd.wap.wbmp',
		'fh4'			=> 'image/x-freehand',
		'fh5'			=> 'image/x-freehand',
		'fhc'			=> 'image/x-freehand',
		'pnm'			=> 'image/x-portable-anymap',
		'pbm'			=> 'image/x-portable-bitmap',
		'pgm'			=> 'image/x-portable-graymap',
		'ppm'			=> 'image/x-portable-pixmap',
		'rgb'			=> 'image/x-rgb',
		'xwd'			=> 'image/x-windowdump',
		'xbm'			=> 'image/x-xbitmap',
		'xpm'			=> 'image/x-xpixmap',
		'csv'			=> 'text/comma-separated-values',
		'css'			=> 'text/css',
		'htm'			=> 'text/html',
		'html'			=> 'text/html',
		'shtml'			=> 'text/html',
		'js'			=> 'text/javascript',
		'txt'			=> 'text/plain',
		'rtx'			=> 'text/richtext',
		'rtf'			=> 'text/rtf',
		'tsv'			=> 'text/tab-separated-values',
		'wml'			=> 'text/vnd.wap.wml',
		'wmlc'			=> 'application/vnd.wap.wmlc',
		'wmls'			=> 'text/vnd.wap.wmlscript',
		'wmlsc'			=> 'application/vnd.wap.wmlscriptc',
		'etx'			=> 'text/x-setext',
		'sgm'			=> 'text/x-sgml',
		'sgml'			=> 'text/x-sgml',
		'talk'			=> 'text/x-speech',
		'spc'			=> 'text/x-speech',
		'mpeg'			=> 'video/mpeg',
		'mpg'			=> 'video/mpeg',
		'mpe'			=> 'video/mpeg',
		'qt'			=> 'video/quicktime',
		'mov'			=> 'video/quicktime',
		'viv'			=> 'video/vnd.vivo',
		'vivo'			=> 'video/vnd.vivo',
		'avi'			=> 'video/x-msvideo',
		'movie'			=> 'video/x-sgi-movie',
		'vts'			=> 'workbook/formulaone',
		'vtts'			=> 'workbook/formulaone',
		'3dmf'			=> 'x-world/x-3dmf',
		'3dm'			=> 'x-world/x-3dmf',
		'qd3d'			=> 'x-world/x-3dmf',
		'qd3'			=> 'x-world/x-3dmf',
		'wrl' 			=> 'x-world/x-vrml'
	);
}