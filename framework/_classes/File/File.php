<?php
/**
 * File object of a file system object element
 */
class File extends FileSystemElement {
	/**
	 * File mime type
	 * @var array
	 */
	protected $_mime;
	/**
	 * Image data (if the file is an image)
	 * @var array
	 */
	protected $_infos;
	/**
	 * Last modified time
	 * @var int
	 */
	protected $_mtime;
	/**
	 * Images mime types list
	 * @var string
	 */
	protected static $_imageFiles = array(
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/bmp',
		'image/psd',
		'image/tiff',
		'application/swc',
		'image/iff',
		'image/jpc',
		'image/jp2',
		'image/jpx',
		'image/jb2',
		'image/x-xbitmap',
		'image/vnd.wap.wbmp',
		'image/x-icon',
		'application/x-shockwave-flash'
	);
	/**
	 * Mime types handled by GD
	 * @var string
	 */
	protected static $_imageEdit = array(
		'image/gif',
		'image/jpeg',
		'image/png'
	);
	/**
	 * Common web mime types
	 * @var string
	 */
	protected static $_imageWeb = array(
		'image/gif',
		'image/jpeg',
		'image/png',
		'image/vnd.wap.wbmp',
		'image/svg'
	);
	
	/**
	 * Clear object cache data
	 * 
	 * @return void
	 * @see FileSystemElement::clearCache()
	 */
	public function clearCache()
	{
		parent::clearCache();
		$this->_mime = NULL;
		$this->_infos = NULL;
		$this->_mtime = NULL;
	}
	
	/**
	 * Check if the element is a file
	 * 
	 * @return boolean always return true
	 */
	public function isFile()
	{
		return true;
	}
	
	/**
	 * Check if the file is an image
	 * 
	 * @return boolean true if the file is an image, else false
	 */
	public function isImage()
	{
		return in_array($this->getMimeType(), self::$_imageFiles);
	}
	
	/**
	 * Check if the file is an web image (that browsers can display)
	 * 
	 * @return boolean true if the file is a web image, else false
	 */
	public function isWebImage()
	{
		return in_array($this->getMimeType(), self::$_imageWeb);
	}
	
	/**
	 * Check if the file is an editable image
	 * 
	 * @return boolean true if the file is an editable image, else false
	 */
	public function isEditableImage()
	{
		return (in_array($this->getMimeType(), self::$_imageEdit) and $this->getManager()->isLocal());
	}
	
	/**
	 * Get file mime infos
	 * 
	 * @return array file mime infos
	 */
	protected function _getMime()
	{
		// Caching
		if (!isset($this->_mime))
		{
			$this->_mime = array(
				'mime' => NULL,
				'charset' => NULL,
			);
			
			// Loading data
			$this->_mime['mime'] = $this->getManager()->getPathMimeType($this->getPath());
			$this->_mime['charset'] = NULL;
			
			// If the file manager couldn't retrieve mime type
			if (!$this->_mime['mime'])
			{
				$this->_mime['mime'] = self::getExtensionMimeType($this->getExtension());
			}
			
			// Charset detection
			if (strpos($this->_mime['mime'], '; charset=') !== false)
			{
				$mimeParts = explode('; charset=', $this->_mime['mime'], 2);
				$this->_mime['mime'] = $mimeParts[0];
				$this->_mime['charset'] = trim($mimeParts[1]);
			}
		}
		
		return $this->_mime;
	}
	
	/**
	 * Get the file mime type
	 * 
	 * @return string the mime type
	 */
	public function getMimeType()
	{
		$mime = $this->_getMime();
		return $mime['mime'];
	}
	
	/**
	 * Get the file charset (if defined)
	 * 
	 * @return string the file charset, or NULL
	 */
	public function getCharset()
	{
		$mime = $this->_getMime();
		return $mime['charset'];
	}
	
	/**
	 * Get full mime type for download (with charset if defined)
	 * 
	 * @return string le type mime complet du fichier
	 */
	public function getFullMimeType()
	{
		$mime = $this->_getMime();
		if (!is_null($mime['charset']))
		{
			return $mime['mime'].'; charset='.$mime['charset'];
		}
		else
		{
			return $mime['mime'];
		}
	}
	
	/**
	 * Get last modified time
	 * 
	 * @return int the last modified time as a Unix timestamp
	 */
	public function getModifiedTime()
	{
		// Caching
		if (!isset($this->_mtime))
		{
			$this->_mtime = $this->getManager()->gePathMTime($this->getPath());
		}
		
		return $this->_mtime;
	}
	
	/**
	 * Get file contents
	 * 
	 * @return string the file contents
	 */
	public function getContents()
	{
		return $this->getManager()->getFileContents($this->getPath());
	}
	
	/**
	 * Write file contents
	 * 
	 * @param string $content the content to write
	 * @return boolean true if the content has been written, else false
	 */
	public function putContents($content)
	{
		return ($this->getManager()->putFileContents($this->getPath(), $content) !== false);
	}
	
	/**
	 * Delete the file
	 * 
	 * @return boolean true if the file has been delete, else false
	 */
	public function delete()
	{
		// Clear thumbnails
		$this->clearThumbnails();
		
		return $this->getManager()->deleteFile($this->getPath());
	}
	
	/**
	 * Clear a image thumbnails cache
	 * 
	 * @return void
	 */
	public function clearThumbnails()
	{
		// If editable image
		if ($this->isEditableImage())
		{
			// Search mask
			$mask = '/^'.preg_quote($this->getFilename(), '/').'_[0-9]+x[0-9]+'.preg_quote('.'.$this->getExtension(), '/').'$/i';
			
			// Fichiers de miniatures
			$miniatures = $this->getFolder()->getChildren(array('folders' => false, 'mask' => $mask));
			foreach ($miniatures as $miniature)
			{
				$this->getManager()->deleteFile($miniature->getPath());
			}
		}
	}
	
	/**
	 * Builds the file HTML code : if it is an image, a <IMG> is used, else a <A>
	 * 
	 * @param string $title any title text for the image/link
	 * @param string $alt any alt text for the image, or text for the link
	 * @param string $add any extra HTML code to add to the img
	 * @return string the HTML code
	 */
	public function buildCode($title = false, $alt = false, $add = '')
	{
		// If web image
		if ($this->isWebImage())
		{
			$width = ($widthValue = $this->getWidth()) ? ' width="'.$widthValue.'"' : '';
			$height = ($heightValue = $this->getHeight()) ? ' height="'.$heightValue.'"' : '';
			$title = $title ? ' title="'.htmlspecialchars($title).'"' : '';
			$alt = $alt ? ' alt="'.htmlspecialchars($alt).'"' : '';
			return '<img src="'.$this->getWebPath().'"'.$width.$height.$title.$alt.$add.' />';
		}
		else
		{
			$title = $title ? ' title="'.htmlspecialchars($title).'"' : '';
			$text = $alt ? $alt : $this->getBasename();
			return '<a href="'.$this->getWebPath().'"'.$title.'>'.htmlspecialchars($text).'</a>';
		}
	}
	
	/**
	 * Get an image thumbnail, with caching
	 * 
	 * @param int|boolean $width final image width, or false to use a proportional width to $height
	 * @param int|boolean $height final image height, false to use a proportional height to $width, or NULL to use same as $width
	 * @param array $options resizing options (see File::resize)
	 * @return File the thumbnail file object (or the file itself if the image is too small)
	 * @throws SCException if the file is not an editable image
	 */
	public function getThumbnail($width, $height = NULL, $options = array())
	{
		// If image is not editable
		if (!$this->isEditableImage())
		{
			throw new SCException('Type de fichier non éditable ('.$this->getMimeType().')');
		}
		
		// Size check
		if (is_null($height))
		{
			$height = $width;
		}
		
		// Check size
		$fileWidth = ($widthValue = $this->getWidth()) ? $widthValue : 100;
		$fileHeight = ($heightValue = $this->getHeight()) ? $heightValue : 100;
		if ($width >= $fileWidth and $height >= $fileHeight)
		{
			// Too small, return image itself
			return $this;
		}
		
		// Compose cache path
		$path = $this->getDirname();
		$filename = $this->getFilename();
		$extension = $this->getExtension();
		if (strlen($path) > 0)
		{
			$path .= '/';
		}
		
		// Check if not already a thumbnail
		if (preg_match('/^(.+)_[0-9]+x[0-9]+$/', $filename, $matches))
		{
			$filename = $matches[1];
		}
		
		// Final path
		$cached = $path.$filename.'_'.$width.'x'.$height.'.'.$extension;
		
		// If already exists
		if ($this->getManager()->pathExists($cached))
		{
			return $this->getManager()->getFile($cached);
		}
		else
		{
			// Resize and cache
			return $this->resize($width, $height, $cached, $options);
		}
	}
	
	/**
	 * Resize the image
	 * 
	 * @param int|boolean $width final image width, or false to use a proportional width to $height
	 * @param int|boolean $height final image height, false to use a proportional height to $width, or NULL to use same as $width
	 * @param string|File|boolean $target path or File object of the target image, or false to erase the original image
	 * @param array $options an array with any of the following options
	 * 	 - boolean crop true to crop image to fit final size, false to resize without cropping (default : false)
	 *   - boolean enableStretch true to enable stretching if image is too small (default : true)
	 * 
	 * @return Image the resized image object
	 * @throws SCException if the file is not an editable image
	 */
	public function resize($width, $height = NULL, $target = false, $options = array())
	{
		// Image check
		if (!$this->isEditableImage())
		{
			throw new SCException('Type de fichier non éditable ('.$this->getMimeType().')');
		}
		
		// Options
		$options = array_merge(array(
			'crop' =>			false,
			'enableStretch' =>	true
		), $options);

		// Size check
		if (is_null($height))
		{
			$height = $width;
		}
		
		// Target
		if (!is_bool($target))
		{
			if (is_string($target))
			{
				$target = $this->getManager()->getFile($target);
			}
		}
		else
		{
			// Erase file
			$target = $this;
			
			// Clear cache
			$this->clearCache();
		}
		
		// If no size at all
		if (!$width and !$height)
		{
			// If target file
			if ($target != $this)
			{
				$target->putContents($this->getContents());
			}
		}
		else
		{
			// Position
			$posX = 0;
			$posY = 0;
			
			// Final size
			$initWidth = ($widthValue = $this->getWidth()) ? $widthValue : 100;
			$initHeight = ($heightValue = $this->getHeight()) ? $heightValue : 100;
			
			if ($width)
			{
				if (!$height)
				{
					// If too small
					if (!$options['enableStretch'] and $width >= $initWidth)
					{
						$target->putContents($this->getContents());
						return $target;
					}
					
					$height = round($width/($initWidth/$initHeight));
				}
				else
				{
					// If too small
					if (!$options['enableStretch'] and $width >= $initWidth and $height >= $initHeight)
					{
						$target->putContents($this->getContents());
						return $target;
					}
					
					// If crop
					if ($options['crop'])
					{
						if ($width/$height > $initWidth/$initHeight)
						{
							$srcHeight = round($initWidth/($width/$height));
							$posY = round(($initHeight-$srcHeight)/2);
							$initHeight = $srcHeight;
						}
						else
						{
							$srcWidth = round($initHeight*($width/$height));
							$posX = round(($initWidth-$srcWidth)/2);
							$initWidth = $srcWidth;
						}
					}
					else
					{
						// Proportional
						if ($width/$height > $initWidth/$initHeight)
						{
							$width = round($height*($initWidth/$initHeight));
						}
						else
						{
							$height = round($width/($initWidth/$initHeight));
						}
					}
				}
			}
			else
			{
				// If too small
				if (!$options['enableStretch'] and $height >= $initHeight)
				{
					$target->putContents($this->getContents());
					return $target;
				}
				
				$width = round($height*($initWidth/$initHeight));
			}
			
			// Load image
			$mime = $this->getMimeType();
			switch ($mime)
			{
				case 'image/jpeg':
					$image = imagecreatefromjpeg($this->getLocalPath());
					break;
				
				case 'image/gif':
					$image = imagecreatefromgif($this->getLocalPath());
					break;
				
				case 'image/png':
					$image = imagecreatefrompng($this->getLocalPath());
					break;
				
				default:
					throw new SCException('Format d\'image non supporté');
					break;
			}
			
			// Target image
			if (!$resized = imagecreatetruecolor($width, $height))
			{
				throw new SCException('Impossible de créer l\'image reidmensionnée');
			}
			
			// Transparency setup
			if ($mime == 'image/png')
			{
				// Disable alpha blending
				imagealphablending($resized, false);
				imagesavealpha($resized, true);
			}
			elseif ($mime == 'image/gif')
			{
				// Transparent color
				$transparencyIndex = imagecolortransparent($image);
				$transparencyColor = array('red' => 255, 'green' => 255, 'blue' => 255);
				if ($transparencyIndex >= 0 and $transparencyIndex < imagecolorstotal($image)) {
					$transparencyColor = imagecolorsforindex($image, $transparencyIndex);   
				}
				
				// Setup
				$transparencyIndex = imagecolorallocate($resized, $transparencyColor['red'], $transparencyColor['green'], $transparencyColor['blue']);
				imagefill($resized, 0, 0, $transparencyIndex);
				imagecolortransparent($resized, $transparencyIndex); 
			}
			
			// Resizing
			imagecopyresampled($resized, $image, 0, 0, $posX, $posY, $width, $height, $initWidth, $initHeight);
			
			// Cleaning
			imagedestroy($image);
			
			// Write final image
			switch ($mime)
			{
				case 'image/jpeg':
					$retour = imagejpeg($resized, $target->getLocalPath(), 80);
					break;
				
				case 'image/gif':
					$retour = imagegif($resized, $target->getLocalPath());
					break;
				
				case 'image/png':
					$retour = imagepng($resized, $target->getLocalPath());
					break;
			}
			
			// Cleaning
			imagedestroy($resized);
			
			// If erase original file, update size infos
			if ($target == $this)
			{
				// Dimensions
				$dimensions = $this->_getDimensions();
				$dimensions['width'] = $width;
				$dimensions['height'] = $height;
				$target->_updateDimensions($dimensions);
			}
		}
		
		return $target;
	}
	
	/**
	 * Display the file in the browser
	 * 
	 * @param boolean $download true to force download, false to view in broser (if available)
	 * @param boolean $filename final name for download (only if $download is true), or NULL to use file name
	 * @return void
	 */
	public function output($download = false, $filename = NULL)
	{
		if ($download)
		{
			if (is_null($filename))
			{
				$filename = $this->getFilename();
			}
			header('Content-Disposition: attachment; filename="'.addslashes($filename).'"');
		}
		
		// Output
		header('Content-type: '.$this->getMimeType());
		echo $this->getContents();
	}
	
	/**
	 * Get the mime type of a file according to file extension (whereas FileManagers use detection)
	 * 
	 * @param string $path the file path, or the bare extension
	 * @param string $default the default mime if it can't be detected
	 * @return string the found mime type
	 */
	public static function getExtensionMimeType($path, $default = 'text/plain')
	{
		// Extension
		if (preg_match('/^[a-z]+$/i',$path ))
		{
			$extension = $path;
		}
		else
		{
			$infos = pathinfo($path);
			if (!isset($infos['extension']) or is_null($infos['extension']) or $infos['extension'] == '')
			{
				return $default;
			}
			$extension = $infos['extension'];
		}
		
		return isset(self::$_mimes[$extension]) ? self::$_mimes[$extension] : $default;
	}
	
	/**
	 * Map of extensions/mimes, for cases where file detection isn't available
	 * @var array
	 */
	protected static $_mimes = array(
		'3dm' => 'x-world/x-3dmf',
		'3dmf' => 'x-world/x-3dmf',
		'a' => 'application/octet-stream',
		'aab' => 'application/x-authorware-bin',
		'aam' => 'application/x-authorware-map',
		'aas' => 'application/x-authorware-seg',
		'abc' => 'text/vnd.abc',
		'acgi' => 'text/html',
		'afl' => 'video/animaflex',
		'ai' => 'application/postscript',
		'aif' => 'audio/aiff',
		'aif' => 'audio/x-aiff',
		'aifc' => 'audio/aiff',
		'aifc' => 'audio/x-aiff',
		'aiff' => 'audio/aiff',
		'aiff' => 'audio/x-aiff',
		'aim' => 'application/x-aim',
		'aip' => 'text/x-audiosoft-intra',
		'ani' => 'application/x-navi-animation',
		'aos' => 'application/x-nokia-9000-communicator-add-on-software',
		'aps' => 'application/mime',
		'arc' => 'application/octet-stream',
		'arj' => 'application/arj',
		'arj' => 'application/octet-stream',
		'art' => 'image/x-jg',
		'asf' => 'video/x-ms-asf',
		'asm' => 'text/x-asm',
		'asp' => 'text/asp',
		'asx' => 'application/x-mplayer2',
		'asx' => 'video/x-ms-asf',
		'asx' => 'video/x-ms-asf-plugin',
		'au' => 'audio/basic',
		'au' => 'audio/x-au',
		'avi' => 'application/x-troff-msvideo',
		'avi' => 'video/avi',
		'avi' => 'video/msvideo',
		'avi' => 'video/x-msvideo',
		'avs' => 'video/avs-video',
		'bcpio' => 'application/x-bcpio',
		'bin' => 'application/mac-binary',
		'bin' => 'application/macbinary',
		'bin' => 'application/octet-stream',
		'bin' => 'application/x-binary',
		'bin' => 'application/x-macbinary',
		'bm' => 'image/bmp',
		'bmp' => 'image/bmp',
		'bmp' => 'image/x-windows-bmp',
		'boo' => 'application/book',
		'book' => 'application/book',
		'boz' => 'application/x-bzip2',
		'bsh' => 'application/x-bsh',
		'bz' => 'application/x-bzip',
		'bz2' => 'application/x-bzip2',
		'c' => 'text/plain',
		'c' => 'text/x-c',
		'c++' => 'text/plain',
		'cat' => 'application/vnd.ms-pki.seccat',
		'cc' => 'text/plain',
		'cc' => 'text/x-c',
		'ccad' => 'application/clariscad',
		'cco' => 'application/x-cocoa',
		'cdf' => 'application/cdf',
		'cdf' => 'application/x-cdf',
		'cdf' => 'application/x-netcdf',
		'cer' => 'application/pkix-cert',
		'cer' => 'application/x-x509-ca-cert',
		'cha' => 'application/x-chat',
		'chat' => 'application/x-chat',
		'class' => 'application/java',
		'class' => 'application/java-byte-code',
		'class' => 'application/x-java-class',
		'com' => 'application/octet-stream',
		'com' => 'text/plain',
		'conf' => 'text/plain',
		'cpio' => 'application/x-cpio',
		'cpp' => 'text/x-c',
		'cpt' => 'application/mac-compactpro',
		'cpt' => 'application/x-compactpro',
		'cpt' => 'application/x-cpt',
		'crl' => 'application/pkcs-crl',
		'crl' => 'application/pkix-crl',
		'crt' => 'application/pkix-cert',
		'crt' => 'application/x-x509-ca-cert',
		'crt' => 'application/x-x509-user-cert',
		'csh' => 'application/x-csh',
		'csh' => 'text/x-script.csh',
		'css' => 'application/x-pointplus',
		'css' => 'text/css',
		'cxx' => 'text/plain',
		'dcr' => 'application/x-director',
		'deepv' => 'application/x-deepv',
		'def' => 'text/plain',
		'der' => 'application/x-x509-ca-cert',
		'dif' => 'video/x-dv',
		'dir' => 'application/x-director',
		'dl' => 'video/dl',
		'dl' => 'video/x-dl',
		'doc' => 'application/msword',
		'dot' => 'application/msword',
		'dp' => 'application/commonground',
		'drw' => 'application/drafting',
		'dump' => 'application/octet-stream',
		'dv' => 'video/x-dv',
		'dvi' => 'application/x-dvi',
		'dwf' => 'drawing/x-dwf (old)',
		'dwf' => 'model/vnd.dwf',
		'dwg' => 'application/acad',
		'dwg' => 'image/vnd.dwg',
		'dwg' => 'image/x-dwg',
		'dxf' => 'application/dxf',
		'dxf' => 'image/vnd.dwg',
		'dxf' => 'image/x-dwg',
		'dxr' => 'application/x-director',
		'el' => 'text/x-script.elisp',
		'elc' => 'application/x-bytecode.elisp (compiled elisp)',
		'elc' => 'application/x-elc',
		'env' => 'application/x-envoy',
		'eps' => 'application/postscript',
		'es' => 'application/x-esrehber',
		'etx' => 'text/x-setext',
		'evy' => 'application/envoy',
		'evy' => 'application/x-envoy',
		'exe' => 'application/octet-stream',
		'f' => 'text/plain',
		'f' => 'text/x-fortran',
		'f77' => 'text/x-fortran',
		'f90' => 'text/plain',
		'f90' => 'text/x-fortran',
		'fdf' => 'application/vnd.fdf',
		'fif' => 'application/fractals',
		'fif' => 'image/fif',
		'fli' => 'video/fli',
		'fli' => 'video/x-fli',
		'flo' => 'image/florian',
		'flx' => 'text/vnd.fmi.flexstor',
		'fmf' => 'video/x-atomic3d-feature',
		'for' => 'text/plain',
		'for' => 'text/x-fortran',
		'fpx' => 'image/vnd.fpx',
		'fpx' => 'image/vnd.net-fpx',
		'frl' => 'application/freeloader',
		'funk' => 'audio/make',
		'g' => 'text/plain',
		'g3' => 'image/g3fax',
		'gif' => 'image/gif',
		'gl' => 'video/gl',
		'gl' => 'video/x-gl',
		'gsd' => 'audio/x-gsm',
		'gsm' => 'audio/x-gsm',
		'gsp' => 'application/x-gsp',
		'gss' => 'application/x-gss',
		'gtar' => 'application/x-gtar',
		'gz' => 'application/x-compressed',
		'gz' => 'application/x-gzip',
		'gzip' => 'application/x-gzip',
		'gzip' => 'multipart/x-gzip',
		'h' => 'text/plain',
		'h' => 'text/x-h',
		'hdf' => 'application/x-hdf',
		'help' => 'application/x-helpfile',
		'hgl' => 'application/vnd.hp-hpgl',
		'hh' => 'text/plain',
		'hh' => 'text/x-h',
		'hlb' => 'text/x-script',
		'hlp' => 'application/hlp',
		'hlp' => 'application/x-helpfile',
		'hlp' => 'application/x-winhelp',
		'hpg' => 'application/vnd.hp-hpgl',
		'hpgl' => 'application/vnd.hp-hpgl',
		'hqx' => 'application/binhex',
		'hqx' => 'application/binhex4',
		'hqx' => 'application/mac-binhex',
		'hqx' => 'application/mac-binhex40',
		'hqx' => 'application/x-binhex40',
		'hqx' => 'application/x-mac-binhex40',
		'hta' => 'application/hta',
		'htc' => 'text/x-component',
		'htm' => 'text/html',
		'html' => 'text/html',
		'htmls' => 'text/html',
		'htt' => 'text/webviewhtml',
		'htx' => 'text/html',
		'ice' => 'x-conference/x-cooltalk',
		'ico' => 'image/x-icon',
		'idc' => 'text/plain',
		'ief' => 'image/ief',
		'iefs' => 'image/ief',
		'iges' => 'application/iges',
		'iges' => 'model/iges',
		'igs' => 'application/iges',
		'igs' => 'model/iges',
		'ima' => 'application/x-ima',
		'imap' => 'application/x-httpd-imap',
		'inf' => 'application/inf',
		'ins' => 'application/x-internett-signup',
		'ip' => 'application/x-ip2',
		'isu' => 'video/x-isvideo',
		'it' => 'audio/it',
		'iv' => 'application/x-inventor',
		'ivr' => 'i-world/i-vrml',
		'ivy' => 'application/x-livescreen',
		'jam' => 'audio/x-jam',
		'jav' => 'text/plain',
		'jav' => 'text/x-java-source',
		'java' => 'text/plain',
		'java' => 'text/x-java-source',
		'jcm' => 'application/x-java-commerce',
		'jfif' => 'image/jpeg',
		'jfif' => 'image/pjpeg',
		'jfif-tbnl' => 'image/jpeg',
		'jpe' => 'image/jpeg',
		'jpe' => 'image/pjpeg',
		'jpeg' => 'image/jpeg',
		'jpeg' => 'image/pjpeg',
		'jpg' => 'image/jpeg',
		'jpg' => 'image/pjpeg',
		'jps' => 'image/x-jps',
		'js' => 'application/x-javascript',
		'js' => 'application/javascript',
		'js' => 'application/ecmascript',
		'js' => 'text/javascript',
		'js' => 'text/ecmascript',
		'jut' => 'image/jutvision',
		'kar' => 'audio/midi',
		'kar' => 'music/x-karaoke',
		'ksh' => 'application/x-ksh',
		'ksh' => 'text/x-script.ksh',
		'la' => 'audio/nspaudio',
		'la' => 'audio/x-nspaudio',
		'lam' => 'audio/x-liveaudio',
		'latex' => 'application/x-latex',
		'lha' => 'application/lha',
		'lha' => 'application/octet-stream',
		'lha' => 'application/x-lha',
		'lhx' => 'application/octet-stream',
		'list' => 'text/plain',
		'lma' => 'audio/nspaudio',
		'lma' => 'audio/x-nspaudio',
		'log' => 'text/plain',
		'lsp' => 'application/x-lisp',
		'lsp' => 'text/x-script.lisp',
		'lst' => 'text/plain',
		'lsx' => 'text/x-la-asf',
		'ltx' => 'application/x-latex',
		'lzh' => 'application/octet-stream',
		'lzh' => 'application/x-lzh',
		'lzx' => 'application/lzx',
		'lzx' => 'application/octet-stream',
		'lzx' => 'application/x-lzx',
		'm' => 'text/plain',
		'm' => 'text/x-m',
		'm1v' => 'video/mpeg',
		'm2a' => 'audio/mpeg',
		'm2v' => 'video/mpeg',
		'm3u' => 'audio/x-mpequrl',
		'man' => 'application/x-troff-man',
		'map' => 'application/x-navimap',
		'mar' => 'text/plain',
		'mbd' => 'application/mbedlet',
		'mc$' => 'application/x-magic-cap-package-1.0',
		'mcd' => 'application/mcad',
		'mcd' => 'application/x-mathcad',
		'mcf' => 'image/vasa',
		'mcf' => 'text/mcf',
		'mcp' => 'application/netmc',
		'me' => 'application/x-troff-me',
		'mht' => 'message/rfc822',
		'mhtml' => 'message/rfc822',
		'mid' => 'application/x-midi',
		'mid' => 'audio/midi',
		'mid' => 'audio/x-mid',
		'mid' => 'audio/x-midi',
		'mid' => 'music/crescendo',
		'mid' => 'x-music/x-midi',
		'midi' => 'application/x-midi',
		'midi' => 'audio/midi',
		'midi' => 'audio/x-mid',
		'midi' => 'audio/x-midi',
		'midi' => 'music/crescendo',
		'midi' => 'x-music/x-midi',
		'mif' => 'application/x-frame',
		'mif' => 'application/x-mif',
		'mime' => 'message/rfc822',
		'mime' => 'www/mime',
		'mjf' => 'audio/x-vnd.audioexplosion.mjuicemediafile',
		'mjpg' => 'video/x-motion-jpeg',
		'mm' => 'application/base64',
		'mm' => 'application/x-meme',
		'mme' => 'application/base64',
		'mod' => 'audio/mod',
		'mod' => 'audio/x-mod',
		'moov' => 'video/quicktime',
		'mov' => 'video/quicktime',
		'movie' => 'video/x-sgi-movie',
		'mp2' => 'audio/mpeg',
		'mp2' => 'audio/x-mpeg',
		'mp2' => 'video/mpeg',
		'mp2' => 'video/x-mpeg',
		'mp2' => 'video/x-mpeq2a',
		'mp3' => 'audio/mpeg3',
		'mp3' => 'audio/x-mpeg-3',
		'mp3' => 'video/mpeg',
		'mp3' => 'video/x-mpeg',
		'mpa' => 'audio/mpeg',
		'mpa' => 'video/mpeg',
		'mpc' => 'application/x-project',
		'mpe' => 'video/mpeg',
		'mpeg' => 'video/mpeg',
		'mpg' => 'audio/mpeg',
		'mpg' => 'video/mpeg',
		'mpga' => 'audio/mpeg',
		'mpp' => 'application/vnd.ms-project',
		'mpt' => 'application/x-project',
		'mpv' => 'application/x-project',
		'mpx' => 'application/x-project',
		'mrc' => 'application/marc',
		'ms' => 'application/x-troff-ms',
		'mv' => 'video/x-sgi-movie',
		'my' => 'audio/make',
		'mzz' => 'application/x-vnd.audioexplosion.mzz',
		'nap' => 'image/naplps',
		'naplps' => 'image/naplps',
		'nc' => 'application/x-netcdf',
		'ncm' => 'application/vnd.nokia.configuration-message',
		'nif' => 'image/x-niff',
		'niff' => 'image/x-niff',
		'nix' => 'application/x-mix-transfer',
		'nsc' => 'application/x-conference',
		'nvd' => 'application/x-navidoc',
		'o' => 'application/octet-stream',
		'oda' => 'application/oda',
		'omc' => 'application/x-omc',
		'omcd' => 'application/x-omcdatamaker',
		'omcr' => 'application/x-omcregerator',
		'p' => 'text/x-pascal',
		'p10' => 'application/pkcs10',
		'p10' => 'application/x-pkcs10',
		'p12' => 'application/pkcs-12',
		'p12' => 'application/x-pkcs12',
		'p7a' => 'application/x-pkcs7-signature',
		'p7c' => 'application/pkcs7-mime',
		'p7c' => 'application/x-pkcs7-mime',
		'p7m' => 'application/pkcs7-mime',
		'p7m' => 'application/x-pkcs7-mime',
		'p7r' => 'application/x-pkcs7-certreqresp',
		'p7s' => 'application/pkcs7-signature',
		'part' => 'application/pro_eng',
		'pas' => 'text/pascal',
		'pbm' => 'image/x-portable-bitmap',
		'pcl' => 'application/vnd.hp-pcl',
		'pcl' => 'application/x-pcl',
		'pct' => 'image/x-pict',
		'pcx' => 'image/x-pcx',
		'pdb' => 'chemical/x-pdb',
		'pdf' => 'application/pdf',
		'pfunk' => 'audio/make',
		'pfunk' => 'audio/make.my.funk',
		'pgm' => 'image/x-portable-graymap',
		'pgm' => 'image/x-portable-greymap',
		'pic' => 'image/pict',
		'pict' => 'image/pict',
		'pkg' => 'application/x-newton-compatible-pkg',
		'pko' => 'application/vnd.ms-pki.pko',
		'pl' => 'text/plain',
		'pl' => 'text/x-script.perl',
		'plx' => 'application/x-pixclscript',
		'pm' => 'image/x-xpixmap',
		'pm' => 'text/x-script.perl-module',
		'pm4' => 'application/x-pagemaker',
		'pm5' => 'application/x-pagemaker',
		'png' => 'image/png',
		'pnm' => 'application/x-portable-anymap',
		'pnm' => 'image/x-portable-anymap',
		'pot' => 'application/mspowerpoint',
		'pot' => 'application/vnd.ms-powerpoint',
		'pov' => 'model/x-pov',
		'ppa' => 'application/vnd.ms-powerpoint',
		'ppm' => 'image/x-portable-pixmap',
		'pps' => 'application/mspowerpoint',
		'pps' => 'application/vnd.ms-powerpoint',
		'ppt' => 'application/mspowerpoint',
		'ppt' => 'application/powerpoint',
		'ppt' => 'application/vnd.ms-powerpoint',
		'ppt' => 'application/x-mspowerpoint',
		'ppz' => 'application/mspowerpoint',
		'pre' => 'application/x-freelance',
		'prt' => 'application/pro_eng',
		'ps' => 'application/postscript',
		'psd' => 'application/octet-stream',
		'pvu' => 'paleovu/x-pv',
		'pwz' => 'application/vnd.ms-powerpoint',
		'py' => 'text/x-script.phyton',
		'pyc' => 'applicaiton/x-bytecode.python',
		'qcp' => 'audio/vnd.qcelp',
		'qd3' => 'x-world/x-3dmf',
		'qd3d' => 'x-world/x-3dmf',
		'qif' => 'image/x-quicktime',
		'qt' => 'video/quicktime',
		'qtc' => 'video/x-qtc',
		'qti' => 'image/x-quicktime',
		'qtif' => 'image/x-quicktime',
		'ra' => 'audio/x-pn-realaudio',
		'ra' => 'audio/x-pn-realaudio-plugin',
		'ra' => 'audio/x-realaudio',
		'ram' => 'audio/x-pn-realaudio',
		'ras' => 'application/x-cmu-raster',
		'ras' => 'image/cmu-raster',
		'ras' => 'image/x-cmu-raster',
		'rast' => 'image/cmu-raster',
		'rexx' => 'text/x-script.rexx',
		'rf' => 'image/vnd.rn-realflash',
		'rgb' => 'image/x-rgb',
		'rm' => 'application/vnd.rn-realmedia',
		'rm' => 'audio/x-pn-realaudio',
		'rmi' => 'audio/mid',
		'rmm' => 'audio/x-pn-realaudio',
		'rmp' => 'audio/x-pn-realaudio',
		'rmp' => 'audio/x-pn-realaudio-plugin',
		'rng' => 'application/ringing-tones',
		'rng' => 'application/vnd.nokia.ringing-tone',
		'rnx' => 'application/vnd.rn-realplayer',
		'roff' => 'application/x-troff',
		'rp' => 'image/vnd.rn-realpix',
		'rpm' => 'audio/x-pn-realaudio-plugin',
		'rt' => 'text/richtext',
		'rt' => 'text/vnd.rn-realtext',
		'rtf' => 'application/rtf',
		'rtf' => 'application/x-rtf',
		'rtf' => 'text/richtext',
		'rtx' => 'application/rtf',
		'rtx' => 'text/richtext',
		'rv' => 'video/vnd.rn-realvideo',
		's' => 'text/x-asm',
		's3m' => 'audio/s3m',
		'saveme' => 'application/octet-stream',
		'sbk' => 'application/x-tbook',
		'scm' => 'application/x-lotusscreencam',
		'scm' => 'text/x-script.guile',
		'scm' => 'text/x-script.scheme',
		'scm' => 'video/x-scm',
		'sdml' => 'text/plain',
		'sdp' => 'application/sdp',
		'sdp' => 'application/x-sdp',
		'sdr' => 'application/sounder',
		'sea' => 'application/sea',
		'sea' => 'application/x-sea',
		'set' => 'application/set',
		'sgm' => 'text/sgml',
		'sgm' => 'text/x-sgml',
		'sgml' => 'text/sgml',
		'sgml' => 'text/x-sgml',
		'sh' => 'application/x-bsh',
		'sh' => 'application/x-sh',
		'sh' => 'application/x-shar',
		'sh' => 'text/x-script.sh',
		'shar' => 'application/x-bsh',
		'shar' => 'application/x-shar',
		'shtml' => 'text/html',
		'shtml' => 'text/x-server-parsed-html',
		'sid' => 'audio/x-psid',
		'sit' => 'application/x-sit',
		'sit' => 'application/x-stuffit',
		'skd' => 'application/x-koan',
		'skm' => 'application/x-koan',
		'skp' => 'application/x-koan',
		'skt' => 'application/x-koan',
		'sl' => 'application/x-seelogo',
		'smi' => 'application/smil',
		'smil' => 'application/smil',
		'snd' => 'audio/basic',
		'snd' => 'audio/x-adpcm',
		'sol' => 'application/solids',
		'spc' => 'application/x-pkcs7-certificates',
		'spc' => 'text/x-speech',
		'spl' => 'application/futuresplash',
		'spr' => 'application/x-sprite',
		'sprite' => 'application/x-sprite',
		'src' => 'application/x-wais-source',
		'ssi' => 'text/x-server-parsed-html',
		'ssm' => 'application/streamingmedia',
		'sst' => 'application/vnd.ms-pki.certstore',
		'step' => 'application/step',
		'stl' => 'application/sla',
		'stl' => 'application/vnd.ms-pki.stl',
		'stl' => 'application/x-navistyle',
		'stp' => 'application/step',
		'sv4cpio' => 'application/x-sv4cpio',
		'sv4crc' => 'application/x-sv4crc',
		'svf' => 'image/vnd.dwg',
		'svf' => 'image/x-dwg',
		'svr' => 'application/x-world',
		'svr' => 'x-world/x-svr',
		'swf' => 'application/x-shockwave-flash',
		't' => 'application/x-troff',
		'talk' => 'text/x-speech',
		'tar' => 'application/x-tar',
		'tbk' => 'application/toolbook',
		'tbk' => 'application/x-tbook',
		'tcl' => 'application/x-tcl',
		'tcl' => 'text/x-script.tcl',
		'tcsh' => 'text/x-script.tcsh',
		'tex' => 'application/x-tex',
		'texi' => 'application/x-texinfo',
		'texinfo' => 'application/x-texinfo',
		'text' => 'application/plain',
		'text' => 'text/plain',
		'tgz' => 'application/gnutar',
		'tgz' => 'application/x-compressed',
		'tif' => 'image/tiff',
		'tif' => 'image/x-tiff',
		'tiff' => 'image/tiff',
		'tiff' => 'image/x-tiff',
		'tr' => 'application/x-troff',
		'tsi' => 'audio/tsp-audio',
		'tsp' => 'application/dsptype',
		'tsp' => 'audio/tsplayer',
		'tsv' => 'text/tab-separated-values',
		'turbot' => 'image/florian',
		'txt' => 'text/plain',
		'uil' => 'text/x-uil',
		'uni' => 'text/uri-list',
		'unis' => 'text/uri-list',
		'unv' => 'application/i-deas',
		'uri' => 'text/uri-list',
		'uris' => 'text/uri-list',
		'ustar' => 'application/x-ustar',
		'ustar' => 'multipart/x-ustar',
		'uu' => 'application/octet-stream',
		'uu' => 'text/x-uuencode',
		'uue' => 'text/x-uuencode',
		'vcd' => 'application/x-cdlink',
		'vcs' => 'text/x-vcalendar',
		'vda' => 'application/vda',
		'vdo' => 'video/vdo',
		'vew' => 'application/groupwise',
		'viv' => 'video/vivo',
		'viv' => 'video/vnd.vivo',
		'vivo' => 'video/vivo',
		'vivo' => 'video/vnd.vivo',
		'vmd' => 'application/vocaltec-media-desc',
		'vmf' => 'application/vocaltec-media-file',
		'voc' => 'audio/voc',
		'voc' => 'audio/x-voc',
		'vos' => 'video/vosaic',
		'vox' => 'audio/voxware',
		'vqe' => 'audio/x-twinvq-plugin',
		'vqf' => 'audio/x-twinvq',
		'vql' => 'audio/x-twinvq-plugin',
		'vrml' => 'application/x-vrml',
		'vrml' => 'model/vrml',
		'vrml' => 'x-world/x-vrml',
		'vrt' => 'x-world/x-vrt',
		'vsd' => 'application/x-visio',
		'vst' => 'application/x-visio',
		'vsw' => 'application/x-visio',
		'w60' => 'application/wordperfect6.0',
		'w61' => 'application/wordperfect6.1',
		'w6w' => 'application/msword',
		'wav' => 'audio/wav',
		'wav' => 'audio/x-wav',
		'wb1' => 'application/x-qpro',
		'wbmp' => 'image/vnd.wap.wbmp',
		'web' => 'application/vnd.xara',
		'wiz' => 'application/msword',
		'wk1' => 'application/x-123',
		'wmf' => 'windows/metafile',
		'wml' => 'text/vnd.wap.wml',
		'wmlc' => 'application/vnd.wap.wmlc',
		'wmls' => 'text/vnd.wap.wmlscript',
		'wmlsc' => 'application/vnd.wap.wmlscriptc',
		'word' => 'application/msword',
		'wp' => 'application/wordperfect',
		'wp5' => 'application/wordperfect',
		'wp5' => 'application/wordperfect6.0',
		'wp6' => 'application/wordperfect',
		'wpd' => 'application/wordperfect',
		'wpd' => 'application/x-wpwin',
		'wq1' => 'application/x-lotus',
		'wri' => 'application/mswrite',
		'wri' => 'application/x-wri',
		'wrl' => 'application/x-world',
		'wrl' => 'model/vrml',
		'wrl' => 'x-world/x-vrml',
		'wrz' => 'model/vrml',
		'wrz' => 'x-world/x-vrml',
		'wsc' => 'text/scriplet',
		'wsrc' => 'application/x-wais-source',
		'wtk' => 'application/x-wintalk',
		'xbm' => 'image/x-xbitmap',
		'xbm' => 'image/x-xbm',
		'xbm' => 'image/xbm',
		'xdr' => 'video/x-amt-demorun',
		'xgz' => 'xgl/drawing',
		'xif' => 'image/vnd.xiff',
		'xl' => 'application/excel',
		'xla' => 'application/excel',
		'xla' => 'application/x-excel',
		'xla' => 'application/x-msexcel',
		'xlb' => 'application/excel',
		'xlb' => 'application/vnd.ms-excel',
		'xlb' => 'application/x-excel',
		'xlc' => 'application/excel',
		'xlc' => 'application/vnd.ms-excel',
		'xlc' => 'application/x-excel',
		'xld' => 'application/excel',
		'xld' => 'application/x-excel',
		'xlk' => 'application/excel',
		'xlk' => 'application/x-excel',
		'xll' => 'application/excel',
		'xll' => 'application/vnd.ms-excel',
		'xll' => 'application/x-excel',
		'xlm' => 'application/excel',
		'xlm' => 'application/vnd.ms-excel',
		'xlm' => 'application/x-excel',
		'xls' => 'application/excel',
		'xls' => 'application/vnd.ms-excel',
		'xls' => 'application/x-excel',
		'xls' => 'application/x-msexcel',
		'xlt' => 'application/excel',
		'xlt' => 'application/x-excel',
		'xlv' => 'application/excel',
		'xlv' => 'application/x-excel',
		'xlw' => 'application/excel',
		'xlw' => 'application/vnd.ms-excel',
		'xlw' => 'application/x-excel',
		'xlw' => 'application/x-msexcel',
		'xm' => 'audio/xm',
		'xml' => 'application/xml',
		'xml' => 'text/xml',
		'xmz' => 'xgl/movie',
		'xpix' => 'application/x-vnd.ls-xpix',
		'xpm' => 'image/x-xpixmap',
		'xpm' => 'image/xpm',
		'x-png' => 'image/png',
		'xsr' => 'video/x-amt-showrun',
		'xwd' => 'image/x-xwd',
		'xwd' => 'image/x-xwindowdump',
		'xyz' => 'chemical/x-pdb',
		'z' => 'application/x-compress',
		'z' => 'application/x-compressed',
		'zip' => 'application/x-compressed',
		'zip' => 'application/x-zip-compressed',
		'zip' => 'application/zip',
		'zip' => 'multipart/x-zip',
		'zoo' => 'application/octet-stream',
		'zsh' => 'text/x-script.zsh'
	);
}