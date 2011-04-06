<?php
/**
 * Online files manager
 */
class WebManager extends BaseManager implements FileManager {
	/**
	 * Return a string depending on file manager type ('local', 'ftp', 'web'...)
	 * 
	 * @return string the file manager type
	 */
	public function getType()
	{
		return 'web';
	}
	
	/**
	 * Open a new cURL session
	 * 
	 * @param string $url the target url
	 * @return resource|boolean the cURL handler, or false if not available
	 */
	protected function _openSession($url)
	{
		if (function_exists('curl_init'))
		{
			$handler = curl_init($url);
			curl_setopt(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0');
			curl_setopt(CURLOPT_AUTOREFERER, true);
			curl_setopt(CURLOPT_FOLLOWLOCATION, true);
			curl_setopt(CURLOPT_COOKIEFILE, '');
			
			return $handler;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Check if a path really exists
	 * 
	 * @param string $path the path to test
	 * @return boolean true if the path exists, else false
	 */
	public function pathExists($path)
	{
		$url = $this->_config->get('web', '').$path;
		
		// Create session
		if ($handler = $this->_openSession($url))
		{
			curl_setopt($handler, CURLOPT_HEADER, true);
			curl_setopt($handler, CURLOPT_NOBODY, true);
			curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
			$headers = curl_exec($handler);
			curl_close($handler);
			
			// If error, act as if the file doesn't exists
			if ($headers === false)
			{
				return false;
			}
		}
		else
		{
			$headers = get_headers($url);
			$headers = array_shift($headers);
		}
		
		// Test
		preg_match('/HTTP\/.* ([0-9]+) .*/', $headers, $status);
		return ($status[1] == 200);
	}
	
	/**
	 * Tests if a path if a file
	 * 
	 * @param string $path the path to test
	 * @return boolean if the path is a file, else false
	 */
	public function pathIsFile($path)
	{
		// Basic test: check if path looks like a folder
		$infos = pathinfo($path);
		return (isset($infos['extension']) and strlen($infos['extension']) > 0);
	}
	
	/**
	 * Tests if a path if a folder
	 * 
	 * @param string $path the path to test
	 * @return boolean if the path is a folder, else false
	 */
	public function pathIsFolder($path)
	{
		return !$this->pathIsFile($path);
	}
	
	/**
	 * Get the contents of a file
	 * 
	 * @param string $path the path to read
	 * @return string|boolean the file contents, or false if error
	 */
	public function getFileContents($path)
	{
		// Temp file
		$temp = tmpfile();
		$url = $this->_config->get('web', '').$path;
		
		// Create session
		if ($handler = $this->_openSession($url))
		{
			curl_setopt($handler, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($handler, CURLOPT_FILE, $temp);
			$contents = curl_exec($handler);
			curl_close($handler);
		}
		else
		{
			$contents = file_get_contents($url);
		}
		
		// Read file
		if ($contents !== false)
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
	 * 
	 * @param string $path the path to use
	 * @param string $content the content to write
	 * @return void
	 * @throws SCException writing is not supproted by default on an url
	 */
	public function putFileContents($path, $content)
	{
		throw new SCException('Ecriture impossible sur une url');
	}
	
	/**
	 * Move an element to a new location within the same file manager
	 * 
	 * @param string $oldPath the original path
	 * @param string $newPath the target path
	 * @return void
	 * @throws SCException moving a file is not supproted by default on an url
	 */
	public function moveElement($oldPath, $newPath)
	{
		throw new SCException('Déplcament impossible sur une url');
	}
	
	/**
	 * Delete a file
	 * 
	 * @param string $path the path to delete
	 * @return void
	 * @throws SCException delete a file is not supproted by default on an url
	 */
	public function deleteFile($path)
	{
		throw new SCException('Suppression impossible sur une url');
	}
	
	/**
	 * Change permissions
	 * 
	 * @param string $path the path to use
	 * @param int $mode the mode to use, as an octal number
	 * @return void
	 * @throws SCException permissions not handled on HTTP
	 */
	public function chmod($path, $mode)
	{
		throw new SCException('Pas de gestion des permissions en HTTP');
	}
	
	/**
	 * Create a folder
	 * 
	 * @param string $path the path to create
	 * @param int $mode the mode to use, as an octal number
	 * @param boolean $recursive true to create parent folders i f they don't exist
	 * @return void
	 * @throws SCException creating a folder is not supproted by default on an url
	 */
	public function createFolder($path, $mode = 0755, $recursive = true)
	{
		throw new SCException('Création de dossier impossible sur une url');
	}
	
	/**
	 * Delete a folder
	 * 
	 * @param string $path the path to delete
	 * @return void
	 * @throws SCException delete a folder is not supproted by default on an url
	 */
	public function deleteFolder($path)
	{
		throw new SCException('Suppression impossible sur une url');
	}
	
	/**
	 * Get the size of a file
	 * 
	 * @param string $path the path to use
	 * @return int size in bytes
	 * @throws SCException if there are no available methods to get file size
	 */
	public function getPathSize($path)
	{
		$url = $this->_config->get('web', '').$path;
		
		// Create session
		if ($handler = $this->_openSession($url))
		{
			curl_setopt($handler, CURLOPT_HEADER, true);
			curl_setopt($handler, CURLOPT_NOBODY, true);
			curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
			$headers = curl_exec($handler);
			curl_close($handler);
			
			// If error, act as if the file doesn't exists
			if ($headers === false)
			{
				return 0;
			}
			
			// Look for size
			if (preg_match('/Content-Length: (\d+)/', $headers, $matches))
	  		{
				return $matches[1];
			}
		}
		// Use filesize, only for php >= 5.0
		elseif (version_compare('5.0.0', phpversion()) < 1)
		{
			return filesize($url);
		}
		else
		{
			throw new SCException('Aucune méthode disponible pour déterminer le poids du fichier');
		}
	}
	
	/**
	 * Get last modified time
	 * 
	 * @param string $path the path to use
	 * @return int|boolean the last modified time as a Unix timestamp, or false if error
	 * @throws SCException if there are no available methods to get file size
	 */
	public function gePathMTime($path)
	{
		$url = $this->_config->get('web', '').$path;
		
		// Create session
		if ($handler = $this->_openSession($url))
		{
			curl_setopt($handler, CURLOPT_HEADER, true);
			curl_setopt($handler, CURLOPT_NOBODY, true);
			curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
			$headers = curl_exec($handler);
			curl_close($handler);
			
			// If error, act as if the file doesn't exists
			if ($headers === false)
			{
				return false;
			}
			
			// Look for size
			if (preg_match('/Last-Modified: ([a-zA-Z0-9 \-:+]+)/', $headers, $matches))
	  		{
				return strtotime($matches[1]);
			}
		}
		// Use filemtime, only for php >= 5.0
		elseif (version_compare('5.0.0', phpversion()) < 1)
		{
			return filemtime($url);
		}
		else
		{
			throw new SCException('Aucune méthode disponible pour déterminer la date de modification du fichier');
		}
	}
	
	/**
	 * Get the size of an image
	 * 
	 * @param string $path the path to use
	 * @return void
	 * @throws SCException unable to read image size on url (would have to download full file first)
	 */
	public function getImageSize($path)
	{
		throw new SCException('Impossible d\'obtenir les dimensions de l\'image sur une url');
	}
	
	/**
	 * Get the elements in a folder
	 * 
	 * @param string $path the folder path
	 * @return void
	 * @throws SCException folder listing is not supproted by default on an url
	 */
	public function getPathChildren($path)
	{
		throw new SCException('Listing de dossier impossible sur une url');
	}
	
	/**
	 * Get the mime type of a file
	 * 
	 * @param string $path the file path
	 * @param string $default the default mime if it can't be detected
	 * @return boolean false, mime type detection isn't available over url
	 */
	public function getPathMimeType($path, $default = 'text/plain')
	{
		return false;
	}
}