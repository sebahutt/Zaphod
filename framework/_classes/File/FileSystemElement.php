<?php
/**
 * Base class of any file system element (File & Folder)
 */
abstract class FileSystemElement {
	/**
	 * File manager of the server on which the FSE lives
	 * @var BaseManager
	 */
	protected $_manager;
	/**
	 * Object path (relative to its manager)
	 * @var string
	 */
	protected $_path;
	/**
	 * Infos on the object path
	 * @var array
	 */
	protected $_pathInfo;
	/**
	 * Element size
	 * @var int
	 */
	protected $_size;
	/**
	 * Image sizes
	 * @var array
	 */
	protected $_dimensions;
	/**
	 * Parent folder, or false if the element is the root
	 * @var FileSystemElement|boolean
	 */
	protected $_folder;
	/**
	 * Hidden files indicator
	 * @var boolean
	 */
	protected $_hidden;
	/**
	 * List of hidden system folders
	 * @var array
	 */
	protected static $_hiddenFolders = array(
										'/^CVS$/i', '/^\.svn$/i' 	// CVS and SVN directory
									   );
	/**
	 * List of hidden system files
	 * @var array
	 */
	protected static $_hiddenFiles = array(
										'/^Thumbs.db$/', '/^desktop\.ini$/i', '/^ehthumbs\.db$/i', 		// Explorer files
										'/^\.DS_Store$/',												// Mac OS files
										'/~$/i', '/^\._/i',												// Temporary file
										'/_[0-9]+x[0-9]+\.[a-z]+$/i'									// Thumbnail
									   );
	
	/**
	 * Constructeur
	 * @param string $path the object path
	 * @param BaseManager $manager the server file manager (default : 'local')
	 */
	public function __construct($path, $manager = NULL)
	{
		// Nettoyage
		$path = removeTrailingSlash(trim($path));
		
		// Mémorisation
		$this->_manager = is_null($manager) ? FileServer::get('local') : $manager;
		$this->_path = $path;
	}
	
	/**
	 * Get object internal path (relative to its manager)
	 * @return string the object path
	 */
	public function getPath()
	{
		return $this->_path;
	}
	
	/**
	 * Get object file manager
	 * @return BaseManager the file manager
	 */
	public function getManager()
	{
		return $this->_manager;
	}
	
	/**
	 * Clear object cache data
	 * @return void
	 */
	public function clearCache()
	{
		$this->_size = NULL;
		$this->_dimensions = NULL;
		$this->_hidden = NULL;
	}

	/**
	 * Check if the object really exists
	 * @param boolean $create use true to create if not existing
	 * @return boolean true if the object exists or was created, else false
	 */
	public function exists($create = false)
	{
		// Check
		$exist = $this->getManager()->pathExists($this->getPath());

		// Create if not exist
		if (!$exist and $create)
		{
			if ($this->isFile())
			{
				$this->getManager()->putFileContents($this->getPath(), '');
			}
			else
			{
				$this->getManager()->createFolder($this->getPath(), 0755, true);
			}
			$exist = $this->getManager()->pathExists($this->getPath());
		}

		return $exist;
	}
	
	/**
	 * Check if the element is a file
	 * @return boolean true if the element is a file, else false
	 */
	public function isFile()
	{
		return false;
	}
	
	/**
	 * Check if the element is a folder
	 * @return boolean true if the element is a folder, else false
	 */
	public function isFolder()
	{
		return false;
	}
	
	/**
	 * Check whether the element is a hidden system element
	 * @return boolean true if the element is hidden, else false
	 */
	public function isHidden()
	{
		// Caching
		if (!isset($this->_hidden))
		{
			$this->_hidden = false;
			$basename = $this->getBasename();
			
			// Type
			if ($this->isFolder())
			{
				$tests = self::$_hiddenFolders;
			}
			else
			{
				$tests = self::$_hiddenFiles;
			}
			
			foreach ($tests as $regexp)
			{
				if (preg_match($regexp, $basename))
				{
					$this->_hidden = true;
					break;
				}
			}
		}
		
		return $this->_hidden;
	}
	
	/**
	 * Change permissions on the element
	 * @param int $mode the mode to use, as an octal number
	 * @param boolean $childrenMode the mode to apply to children, or false to ignore (folders only)
	 * @return boolean true if the permissions were set, else false
	 */
	public function chmod($mode, $childrenMode = false)
	{
		$result = $this->getManager()->chmod($this->getPath(), $mode);
		
		// Folders
		if ($result and !is_bool($children) and $this->isFolder())
		{
			$children = $this->getChildren(array('hideSystem' => false));
			foreach ($children as $child)
			{
				$child->chmod($childrenMode, $childrenMode);
			}
		}
		
		return $result;
	}
	
	/**
	 * Get parent folder
	 * @return Folder|boolean the parent folder, or false if the element is the root
	 */
	public function getFolder()
	{
		// Caching
		if (!isset($this->_folder))
		{
			// If parent
			if (strlen($this->getPath()) > 0)
			{
				// Search if there are sub-levels
				if (strpos($this->getPath(), '/') !== false)
				{
					$this->_folder = $this->getManager()->getFolder(substr($this->getPath(), 0, strrpos($this->getPath(), '/')));
				}
				else
				{
					$this->_folder = $this->getManager()->getRoot();
				}
			}
			else
			{
				// Root element
				$this->_folder = false;
			}
		}
		
		return $this->_folder;
	}
	
	/**
	 * Full local path of the object (if available)
	 * @return string|false the local path, or false if not available
	 */
	public function getLocalPath()
	{
		$localPath = $this->getManager()->getLocalPath();
		return $localPath ? $localPath.$this->getPath() : false;
	}
	
	/**
	 * Full web path of the object (if available)
	 * @return string|false the web path, or false if not available
	 */
	public function getWebPath()
	{
		$webPath = $this->getManager()->getWebPath();
		return $webPath ? $webPath.$this->getPath() : false;
	}
	
	/**
	 * Get path informations - see pathinfo() for more details
	 * @return array datas on the path : dirname, basename, extension, filename
	 * @url http://www.php.net/manual/fr/function.pathinfo.php
	 */
	protected function _getPathInfo()
	{
		// Caching
		if (!isset($this->_pathInfo))
		{
			$this->_pathInfo = array_merge(array(
				'dirname' => '',
				'basename' => '',
				'extension' => NULL,
				'filename' => ''
			), pathinfo($this->getPath()));
		}
		
		return $this->_pathInfo;
	}
	
	/**
	 * Update path informations - internal use only
	 * @param array $pathInfo datas on the path : dirname, basename, extension, filename
	 * @return void
	 */
	protected function _updatePathInfo($pathInfo)
	{
		$this->_pathInfo = $pathInfo;
	}
	
	/**
	 * Get image size
	 * @return array an array with the following keys :
	 * 	 - width
	 * 	 - height
	 * 	 - channels
	 * 	 - bits
	 * @see FileManager::getImageSize()
	 */
	protected function _getDimensions()
	{
		// Caching
		if (!isset($this->_dimensions))
		{
			$this->_dimensions = array(
				'width' => 		NULL,
				'height' => 	NULL,
				'channels' => 	NULL,
				'bits' => 		NULL
			);
			
			// If image
			if ($this->isFile() and $this->isImage())
			{
				$this->_dimensions = $this->getManager()->getImageSize($this->getPath());
			}
		}
		
		return $this->_dimensions;
	}
	
	/**
	 * Update image size - internal use only
	 * @param array $dimensions an array with the following keys :
	 * 	 - width
	 * 	 - height
	 * 	 - channels
	 * 	 - bits
	 * @return void
	 */
	protected function _updateDimensions($dimensions)
	{
		$this->_dimensions = $dimensions;
	}
	
	/**
	 * Get the folder part of the object path
	 * @return string the folder path
	 */
	public function getDirname()
	{
		$infos = $this->_getPathInfo();
		return $infos['dirname'];
	}
	
	/**
	 * Get the object name without extension
	 * @return string the object name
	 */
	public function getFilename()
	{
		$infos = $this->_getPathInfo();
		return $infos['filename'];
	}
	
	/**
	 * Get the object name with extension (if defined)
	 * @return string the object name
	 */
	public function getBasename()
	{
		$infos = $this->_getPathInfo();
		return $infos['basename'];
	}
	
	/**
	 * Get the file extension (if defined)
	 * @return string the file extension, or NULL if none
	 */
	public function getExtension()
	{
		// Données
		$infos = $this->_getPathInfo();
		return $infos['extension'];
	}
	
	/**
	 * Get file size
	 * @return int the file size
	 */
	public function getSize()
	{
		// Caching
		if (!isset($this->_size))
		{
			// If file
			if ($this->isFile())
			{
				$this->_size = $this->getManager()->getPathSize($this->getPath());
			}
			else
			{
				// Folder
				$this->_size = 0;
			}
		}
		
		return $this->_size;
	}
	
	/**
	 * Get file size in a readable format (ko, mo...)
	 * @return string the size
	 */
	public function getReadableSize()
	{
		return Number::getReadableSize($this->getSize());
	}
	
	/**
	 * Get image width (if the object is an image)
	 * @return int the width in pixels, or NULL
	 */
	public function getWidth()
	{
		$infos = $this->_getDimensions();
		return $infos['width'];
	}
	
	/**
	 * Get image height (if the object is an image)
	 * @return int the height in pixels, or NULL
	 */
	public function getHeight()
	{
		// Données
		$infos = $this->_getDimensions();
		return $infos['height'];
	}
	
	/**
	 * Get image number of channels (if the object is an image)
	 * @return int the number of channels, or NULL
	 */
	public function getChannels()
	{
		$infos = $this->_getDimensions();
		return $infos['channels'];
	}
	
	/**
	 * Get image color depth by channel in bits (if the object is an image)
	 * @return int the color depth by channel, or NULL
	 */
	public function getBits()
	{
		$infos = $this->_getDimensions();
		return $infos['bits'];
	}
	
	/**
	 * Copy an object to a new path or in a folder
	 * @param string|Folder $target the target path (if in the same file manager) or a folder object
	 * @param string|boolean $name name for the copy, or false to use the same same (only used if $folder is an object)
	 * @param array $options an array of options with any of the following keys:
	 * 	- boolean warnIfExist if true, throws an exception if the target file already exists (default: true)
	 * 	- boolean recursive if true, copy all children elements for folders (default: true)
	 * 	- boolean warnIfChildExist if true, throws an exception if a child of the target file already exists (default: false)
	 * 	- boolean eraseIfExist if true, erase the target file if it already exists (default: true)
	 * 	- boolean mergeIfExist if true (and recursive is true), copy the children of a folder even if it already exists (default: true)
	 * 	- boolean eraseIfDifferent (only if eraseIfExist is true) if true, erase the target file only if sizes are different (default: true)
	 * 	- boolean eraseIfOlder (only if eraseIfExist is true) if true, erase the target file only if older. 
	 * 			  If false, the target file will be erased. Caution: both file servers must be synchonized! (default: false)
	 * 	- boolean deleteOriginal if true, delete the original file once copied (moves the file) (default: false)
	 * @return File\Folder the new copied element
	 * @throws SCException if the target is not a folder, if the target name already exists or if a copy error occurs
	 */
	public function copyTo($target, $name = false, $options = array())
	{
		// Check
		if (is_string($target))
		{
			$target = explode('/', $target);
			$name = array_pop($target);
			$target = $this->getManager()->getFolder(implode('/', $target));
		}
		elseif (!$target->isFolder())
		{
			throw new SCException('The target element is not a folder : '.$target->getPath());
		}
		
		// Target name
		if (!$name)
		{
			$name = $this->getBasename();
		}
		$path = $target->getPath().'/'.$name;
		
		// Extend options
		$options = array_merge(array(
			'warnIfExist' => true,
			'recursive' => true,
			'warnIfChildExist' => false,
			'eraseIfExist' => true,
			'mergeIfExist' => true,
			'eraseIfDifferent' => true,
			'eraseIfOlder' => false,
			'deleteOriginal' => false
		), $options);
		
		// Check if already exists
		$exists = $target->getManager()->pathExists($path);
		if ($exists and $options['warnIfExist'])
		{
			throw new SCException('Target name already exists : '.$path);
		}
		
		// Init
		$deleted = false;
		
		// If file
		if ($this->isFile())
		{
			// Detect if target should be erased
			$erase = true;
			if ($exists)
			{
				if (!$options['eraseIfExist'])
				{
					$erase = false;
				}
				elseif ($options['eraseIfDifferent'] and $this->getSize() == $target->getManager()->getFile($path)->getSize())
				{
					$erase = false;
				}
				elseif ($options['eraseIfOlder'] and $this->getModifiedTime() <= $target->getManager()->getFile($path)->getModifiedTime())
				{
					$erase = false;
				}
			}
			if ($erase)
			{
				// If moving within the same file manager
				if ($options['deleteOriginal'] and $this->getManager() == $target->getManager())
				{
					if (!$this->getManager()->moveElement($this->getPath(), $path))
					{
						throw new SCException('Error while moving '.$this->getPath().' to '.$path);
					}
					$deleted = true;
				}
				else
				{
					if ($target->getManager()->putFileContents($path, $this->getContents()) === false)
					{
						throw new SCException('Error while copying '.$this->getPath().' to '.$path);
					}
				}
			}
			
			// Target element
			$copy = $target->getManager()->getFile($path);
		}
		else
		{
			// Target folder
			$copy = $target->getManager()->getFolder($path);
			
			// Create if needed
			$target->exists(true);
			
			// If recursive
			if ($options['recursive'] and (!$exists or $options['mergeIfExist']))
			{
				// Children options
				$childrenOptions = array_merge($options, array(
					'warnIfExist' => $options['warnIfChildExist']
				));
				
				// Copy children
				$children = $this->getChildren(array('hideSystem' => false));
				foreach ($children as $child)
				{
					$child->copyTo($copy, false, $childrenOptions);
				}
			}
		}
		
		// If moving
		if ($options['deleteOriginal'] and !$deleted)
		{
			$this->delete();
		}
		
		return $copy;
	}
	
	/**
	 * Move an object to a new path or in a folder - shorthand for copyTo()
	 * @param string|Folder $target the target path (if in the same file manager) or a folder object
	 * @param string|boolean $name name for the copy, or false to use the same same (only used if $folder is an object)
	 * @param array $options an array of options (same as FileSystemElement::copyTo())
	 * @return File\Folder the new moved element
	 * @use FileSystemElement::copyTo()
	 */
	public function moveTo($folder, $name = false, $options = array())
	{
		// Copy and delete
		return $this->copyTo($folder, $name, array_merge($options, array('deleteOriginal' => true)));
	}
}