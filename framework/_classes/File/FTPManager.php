<?php
/**
 * FTP file manager
 */
class FTPManager extends BaseManager implements FileManager {
	/**
	 * FTP connection stream
	 * @var resource
	 */
	protected $_stream;
	
	/**
	 * Return a string depending on file manager type ('local', 'ftp', 'web'...)
	 * @return string the file manager type
	 */
	public function getType()
	{
		return 'ftp';
	}
	
	/**
	 * Get FTP stream (open the connection if needed)
	 * @return resource the FTP stream
	 * @throws SCException if config is invalid or if FTP stream can't be opened
	 */
	protected function _getStream()
	{
		if (!isset($this->_stream))
		{
			// Server
			$host = $this->_config->get('host', false);
			$port = $this->_config->get('port', 21);
			$timeout = $this->_config->get('timeout', 90);
			if (!$host or $host == '')
			{
				throw new SCException('Invalid FTP host');
			}
			
			// Connect
			$stream = $this->_config->get('ssl', false) ? ftp_ssl_connect($host, $port, $timeout) : ftp_connect($host, $port, $timeout);
			if (!$stream)
			{
				throw new SCException('Unable to open FTP connection');
			}
			
			// Login
			if (@ftp_login($stream, $this->_config->get('user', 'anonymous'), $this->_config->get('pass', '')))
			{
				$this->_stream = $stream;
			}
			else
			{
				throw new SCException('Wrong login or password');
			}
			
			// Passive mode
			if ($this->_config->get('passive', false))
			{
				ftp_pasv($this->_stream, true);
			}
		}
		
		return $this->_stream;
	}
	
	/**
	 * Check if a path really exists
	 * @param string $path the path to test
	 * @return boolean true if the path exists, else false
	 */
	public function pathExists($path)
	{
		$children = $this->getPathChildren(dirname($path));
		return ($children !== false) ? in_array(strtolower($path), array_map('strtolower', $children)) : false;
	}
	
	/**
	 * Tests if a path if a file
	 * @param string $path the path to test
	 * @return boolean if the path is a file, else false
	 */
	public function pathIsFile($path)
	{
		return ($this->getPathSize($path) > -1);
	}
	
	/**
	 * Tests if a path if a folder
	 * @param string $path the path to test
	 * @return boolean if the path is a folder, else false
	 */
	public function pathIsFolder($path)
	{
		return ($this->pathExists($path) and $this->getPathSize($path) < 0);
	}
	
	/**
	 * Get the contents of a file
	 * @param string $path the path to read
	 * @return string|boolean the file contents, or false if error
	 */
	public function getFileContents($path)
	{
		// Temp file
		$temp = tmpfile();
		
		// Read file
		if (ftp_fget($this->_getStream(), $temp, $path, $this->_config->get('mode', FTP_ASCII)))
		{
			// File size
			$stat = fstat($temp);
			$size = $stat['size'];
			
			// Read content
			rewind($temp);
			$content = fread($temp, $size);
			fclose($temp);
			return $content;
		}
		else
		{
			fclose($temp);
			return false;
		}
	}
	
	/**
	 * Put the contents of a file
	 * @param string $path the path to use
	 * @param string $content the content to write
	 * @return int|boolean number of bytes written, or false if error
	 */
	public function putFileContents($path, $content)
	{
		// Temp file
		$temp = tmpfile();
		fwrite($temp, $content);
			
		// Read file
		if (ftp_fput($this->_getStream(), $path, $temp, $this->_config->get('mode', FTP_ASCII)))
		{
			// File size
			$stat = fstat($temp);
			$size = $stat['size'];
			fclose($temp);
			return $size;
		}
		else
		{
			fclose($temp);
			return false;
		}
	}
	
	/**
	 * Move an element to a new location within the same file manager
	 * @param string $oldPath the original path
	 * @param string $newPath the target path
	 * @return boolean true if the element was moved, else false
	 */
	public function moveElement($oldPath, $newPath)
	{
		ftp_rename($this->_getStream(), $oldPath, $newPath);
	}
	
	/**
	 * Delete a file
	 * @param string $path the path to delete
	 * @return boolean true if the file was deleted, else false
	 */
	public function deleteFile($path)
	{
		return ftp_delete($this->_getStream(), $path);
	}
	
	/**
	 * Change permissions
	 * @param string $path the path to use
	 * @param int $mode the mode to use, as an octal number
	 * @return boolean true if the permissions were set, else false
	 */
	public function chmod($path, $mode)
	{
		// Some windows FTP servers may return warning on unsupported POSIX permissions, so we hide them with @
		return (@ftp_chmod($this->_getStream(), $mode, $path) == $mode);
	}
	
	/**
	 * Create a folder
	 * @param string $path the path to create
	 * @param int $mode the mode to use, as an octal number
	 * @param boolean $recursive true to create parent folders i f they don't exist
	 * @return boolean true if the folder was created, else false
	 */
	public function createFolder($path, $mode = 0755, $recursive = true)
	{
		// Check parents if recursive
		if ($recursive and strpos($path, '/') !== false)
		{
			$parent = dirname($path);
			if (!$this->pathExists($parent))
			{
				$this->createFolder($parent, $mode, $recursive);
			}
		}
		
		// Check if already exists
		if (!$this->pathExists($path))
		{
			// Create folder
			if (ftp_mkdir($this->_getStream(), $path))
			{
				return $this->chmod($path, $mode);
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * Delete a folder
	 * @param string $path the path to delete
	 * @return boolean true if the folder was deleted, else false
	 */
	public function deleteFolder($path)
	{
		return ftp_rmdir($this->_getStream(), $path);
	}
	
	/**
	 * Get the size of a file
	 * @param string $path the path to use
	 * @return int size in bytes
	 */
	public function getPathSize($path)
	{
		return ftp_size($this->_getStream(), $path);
	}
	
	/**
	 * Get last modified time
	 * @param string $path the path to use
	 * @return int|boolean the last modified time as a Unix timestamp, or false if error
	 */
	public function gePathMTime($path)
	{
		$mtime = ftp_mdtm($this->_getStream(), $path);
		return ($mtime == -1) ? false : $mtime;
	}
	
	/**
	 * Get the size of an image
	 * @param string $path the path to use
	 * @return void
	 * @throws SCException unable to read image size on FTP (would have to download full file first)
	 */
	public function getImageSize($path)
	{
		throw new SCException('Image size reading not supported');
	}
	
	/**
	 * Get the elements in a folder
	 * @param string $path the folder path
	 * @return array the list of the elements, without '.' and '..'
	 */
	public function getPathChildren($path)
	{
		$children = ftp_nlist($this->_getStream(), $path);
		if ($children !== false)
		{
			// Remove '.'
			$index = array_search('.', $children);
			if ($index !== false)
			{
				array_splice($children, $index, 1);
			}
			
			// Remove '..'
			$index = array_search('..', $children);
			if ($index !== false)
			{
				array_splice($children, $index, 1);
			}
			
			// Trim './' for root elements
			$max = count($children);
			for ($i = 0; $i < $max; ++$i)
			{
				if (substr($children[$i], 0, 2) == './')
				{
					$children[$i] = substr($children[$i], 2);
				}
			}
		}
		
		return $children;
	}
}