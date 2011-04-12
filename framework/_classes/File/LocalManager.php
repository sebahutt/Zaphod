<?php
/**
 * Local files manager
 */
class LocalManager extends BaseManager implements FileManager {
	/**
	 * Returns the path for use with filesystem encoding
	 *
	 * @param string $path the path to encode
	 * @return the encoded path
	 */
	protected function _encodeName($path)
	{
		//if (DIRECTORY_SEPARATOR == '\\')
		//{
			$path = utf8_decode($path);
		//}
		
		return $path;
	}

	/**
	 * Encodes the filename returned by filesystem encoding
	 *
	 * @param string $path the path to decode
	 * @return the decoded path
	 */
	protected function _decodeName($path)
	{
		//if (DIRECTORY_SEPARATOR == '\\')
		//{
			$path = utf8_encode($path);
		//}
		
		return $path;
	}
	
	/**
	 * Return a string depending on file manager type ('local', 'ftp', 'web'...)
	 *
	 * @return string the file manager type
	 */
	public function getType()
	{
		return 'local';
	}
	
	/**
	 * Check if a path really exists
	 *
	 * @param string $path the path to test
	 * @return boolean true if the path exists, else false
	 */
	public function pathExists($path)
	{
		return file_exists($this->getLocalPath().$this->_encodeName($path));
	}
	
	/**
	 * Tests if a path if a file
	 *
	 * @param string $path the path to test
	 * @return boolean if the path is a file, else false
	 */
	public function pathIsFile($path)
	{
		return is_file($this->getLocalPath().$this->_encodeName($path));
	}
	
	/**
	 * Tests if a path if a folder
	 *
	 * @param string $path the path to test
	 * @return boolean if the path is a folder, else false
	 */
	public function pathIsFolder($path)
	{
		return is_dir($this->getLocalPath().$this->_encodeName($path));
	}
	
	/**
	 * Get the contents of a file
	 *
	 * @param string $path the path to read
	 * @return string|boolean the file contents, or false if error
	 */
	public function getFileContents($path)
	{
		return file_get_contents($this->getLocalPath().$this->_encodeName($path));
	}
	
	/**
	 * Put the contents of a file
	 *
	 * @param string $path the path to use
	 * @param string $content the content to write
	 * @return int|boolean number of bytes written, or false if error
	 */
	public function putFileContents($path, $content)
	{
		return file_put_contents($this->getLocalPath().$this->_encodeName($path), $content);
	}
	
	/**
	 * Move an element to a new location within the same file manager
	 *
	 * @param string $oldPath the original path
	 * @param string $newPath the target path
	 * @return boolean true if the element was moved, else false
	 */
	public function moveElement($oldPath, $newPath)
	{
		return rename($this->getLocalPath().$this->_encodeName($oldPath), $this->getLocalPath().$this->_encodeName($newPath));
	}
	
	/**
	 * Delete a file
	 *
	 * @param string $path the path to delete
	 * @return boolean true if the file was deleted, else false
	 */
	public function deleteFile($path)
	{
		if ($this->pathExists($path))
		{
			return unlink($this->getLocalPath().$this->_encodeName($path));
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Change permissions
	 *
	 * @param string $path the path to use
	 * @param int $mode the mode to use, as an octal number
	 * @return boolean true if the permissions were set, else false
	 */
	public function chmod($path, $mode)
	{
		return chmod($this->getLocalPath().$this->_encodeName($path), $mode);
	}
	
	/**
	 * Create a folder
	 *
	 * @param string $path the path to create
	 * @param int $mode the mode to use, as an octal number
	 * @param boolean $recursive true to create parent folders i f they don't exist
	 * @return boolean true if the folder was created, else false
	 */
	public function createFolder($path, $mode = 0755, $recursive = true)
	{
		return $this->pathExists($path) ? $this->chmod($path, $mode) : mkdir($this->getLocalPath().$this->_encodeName($path), $mode, $recursive);
	}
	
	/**
	 * Delete a folder
	 *
	 * @param string $path the path to delete
	 * @return boolean true if the folder was deleted, else false
	 */
	public function deleteFolder($path)
	{
		return rmdir($this->getLocalPath().$this->_encodeName($path));
	}
	
	/**
	 * Get the size of a file
	 *
	 * @param string $path the path to use
	 * @return int size in bytes
	 */
	public function getPathSize($path)
	{
		return filesize($this->getLocalPath().$this->_encodeName($path));
	}
	
	/**
	 * Get last modified time
	 *
	 * @param string $path the path to use
	 * @return int|boolean the last modified time as a Unix timestamp, or false if error
	 */
	function gePathMTime($path)
	{
		return filemtime($this->getLocalPath().$this->_encodeName($path));
	}
	
	/**
	 * Get the size of an image
	 *
	 * @param string $path the path to use
	 * @return array an array with the following keys :
	 * 	 - width
	 * 	 - height
	 * 	 - channels
	 * 	 - bits
	 */
	public function getImageSize($path)
	{
		// Get data
		$base = getimagesize($this->getLocalPath().$this->_encodeName($path));
		
		// Convert
		return array(
			'width' => $base[0],
			'height' => $base[1],
			'channels' => isset($base['channels']) ? $base['channels'] : NULL,
			'bits' => isset($base['bits']) ? $base['bits'] : NULL
		);
	}
	
	/**
	 * Get the elements in a folder
	 *
	 * @param string $path the folder path
	 * @return array the list of the elements, without '.' and '..'
	 */
	public function getPathChildren($path)
	{
		$children = array();
		
		// If path exists and is a directory
		if ($dir = dir($this->getLocalPath().$this->_encodeName($path)))
		{
			// Internal path
			$path = (strlen($path) == 0) ? '' : $path.'/';
			
			while (false !== ($entry = $dir->read()))
			{
				// Do not include navigation pseudo-entries
				if ($entry != '.' and $entry != '..')
				{
					$children[] = $path.$this->_decodeName($entry);
				}
			}
		}
		
		return $children;
	}
	
	/**
	 * Get the mime type of a file
	 *
	 * @param string $path the file path
	 * @param string $default the default mime if it can't be detected
	 * @return string the found mime type
	 */
	public function getPathMimeType($path, $default = 'text/plain')
	{
		if (function_exists('finfo_open'))
		{
			$finfo = finfo_open(FILEINFO_MIME);
			$mime = finfo_file($finfo, $this->getLocalPath().$this->_encodeName($path));
			return $mime ? $mime : $default;
		}
		else
		{
			return parent::getPathMimeType($path, $default);
		}
	}
}