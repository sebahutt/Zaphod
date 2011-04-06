<?php
/**
 * Interface for all file protocols
 */
interface FileManager {
	/**
	 * Return a string depending on file manager type ('local', 'ftp'...)
	 * 
	 * @return string the file manager type
	 */
	function getType();
	
	/**
	 * Check if a path really exists
	 * 
	 * @param string $path the path to test
	 * @return boolean true if the path exists, else false
	 */
	function pathExists($path);
	
	/**
	 * Tests if a path if a file
	 * 
	 * @param string $path the path to test
	 * @return boolean if the path is a file, else false
	 */
	function pathIsFile($path);
	
	/**
	 * Tests if a path if a folder
	 * 
	 * @param string $path the path to test
	 * @return boolean if the path is a folder, else false
	 */
	function pathIsFolder($path);
	
	/**
	 * Get the contents of a file
	 * 
	 * @param string $path the path to read
	 * @return string|boolean the file contents, or false if error
	 */
	function getFileContents($path);
	
	/**
	 * Put the contents of a file
	 * 
	 * @param string $path the path to use
	 * @param string $content the content to write
	 * @return int|boolean number of bytes written, or false if error
	 */
	function putFileContents($path, $content);
	
	/**
	 * Move an element to a new location within the same file manager
	 * 
	 * @param string $oldPath the original path
	 * @param string $newPath the target path
	 * @return boolean true if the element was moved, else false
	 */
	function moveElement($oldPath, $newPath);
	
	/**
	 * Delete a file
	 * 
	 * @param string $path the path to delete
	 * @return boolean true if the file was deleted, else false
	 */
	function deleteFile($path);
	
	/**
	 * Change permissions
	 * 
	 * @param string $path the path to use
	 * @param int $mode the mode to use, as an octal number
	 * @return boolean true if the permissions were set, else false
	 */
	function chmod($path, $mode);
	
	/**
	 * Create a folder
	 * 
	 * @param string $path the path to create
	 * @param int $mode the mode to use, as an octal number
	 * @param boolean $recursive true to create parent folders i f they don't exist
	 * @return boolean true if the folder was created, else false
	 */
	function createFolder($path, $mode = 0755, $recursive = true);
	
	/**
	 * Delete a folder
	 * 
	 * @param string $path the path to delete
	 * @return boolean true if the folder was deleted, else false
	 */
	function deleteFolder($path);
	
	/**
	 * Get the size of a file
	 * 
	 * @param string $path the path to use
	 * @return int size in bytes
	 */
	function getPathSize($path);
	
	/**
	 * Get last modified time
	 * 
	 * @param string $path the path to use
	 * @return int|boolean the last modified time as a Unix timestamp, or false if error
	 */
	function gePathMTime($path);
	
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
	function getImageSize($path);
	
	/**
	 * Get the elements in a folder
	 * 
	 * @param string $path the folder path
	 * @return array the list of the elements, with complete path (including $path) and without '.' and '..'
	 */
	function getPathChildren($path);
	
	/**
	 * Get the mime type of a file
	 * 
	 * @param string $path the file path
	 * @param string $default the default mime if it can't be detected
	 * @return string|boolean the found mime type, of false if mime detection isn't available
	 */
	function getPathMimeType($path, $default = 'text/plain');
}