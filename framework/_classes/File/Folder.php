<?php
/**
 * Folder object of a file system object element
 */
class Folder extends FileSystemElement {
	/**
	 * Children list
	 * @var array
	 */
	protected $_children;
	
	/**
	 * Clear object cache data
	 * 
	 * @return void
	 * @see FileSystemElement::clearCache()
	 */
	public function clearCache()
	{
		parent::clearCache();
		$this->_children = NULL;
	}
	
	/**
	 * Check if the element is a folder
	 * 
	 * @return boolean always return true
	 */
	public function isFolder()
	{
		return true;
	}
	
	/**
	 * Get the mime type
	 * 
	 * @return string always return 'folder'
	 */
	public function getMimeType()
	{
		return 'folder';
	}
	
	/**
	 * Check if the given name already exists in the folder, and if it does, return the closest available name
	 * 
	 * @param string $name the name to check
	 * @return string the final available name
	 */
	public function getNewName($name)
	{
		// Get file extension
		$infos = pathinfo($name);
		if (isset($infos['extension']))
		{
			$extension = '.'.strtolower($infos['extension']);
		}
		else
		{
			$extension = '';
		}
		
		// Check if name is valid
		$filename = String::cleanForUrl($infos['filename']);
		
		// Check availability
		$counter = 0;
		$testName = $filename.$extension;
		while ($this->getManager()->pathExists($this->getPath().'/'.$testName))
		{
			++$counter;
			$testName = $filename.$counter.$extension;
		}
		
		return $testName;
	}
	
	/**
	 * Get folder children
	 * 
	 * @param array $options an array with any of the following options:
	 * 	- boolean files true to include files (default : true)
	 * 	- boolean folders true to include folders (default : true)
	 * 	- string filter a regular expression to filter children
	 * 	- boolean hideSystem true to hide system files and thumbnails cache (default : true)
	 * 	- string|array sort a string or an array of string of properties to sort the children on (possible values: 'name', 'size', 'modified')
	 * 	- string|array sortDirection a string or an array of string (if sort is an array) of sorting direction (possible values: 'ASC', 'DESC')
	 * 	- boolean underscoreFirst when sorting on name, true to put files beginning with a '_' on top (default : true)
	 * 
	 * @return array the children elements
	 */
	public function getChildren($options = array())
	{
		// Caching
		if (!isset($this->_children))
		{
			$this->_children = array();
			$names = $this->getManager()->getPathChildren($this->getPath());
			
			// Convert to objects
			foreach ($names as $name)
			{
				if ($this->getManager()->pathIsFile($name))
				{
					$this->_children[] = $this->getManager()->getFile($name);
				}
				else
				{
					$this->_children[] = $this->getManager()->getFolder($name);
				}
			}
		}
		
		// Extend options
		$options = array_merge(array(
			'files' => true,
			'folders' => true,
			'filter' => NULL,
			'hideSystem' => true,
			'sort' => array(),
			'sortDirection' => 'ASC',
			'underscoreFirst' => true
		), $options);
		if (!is_array($options['sort']))
		{
			$options['sort'] = array($options['sort']);
		}
		$nbSortFields = count($options['sort']);
		if (!is_array($options['sortDirection']))
		{
			$options['sortDirection'] = array($options['sortDirection']);
		}
		
		// If no filter or type requirement
		if ($options['files'] and $options['folders'] and is_null($options['filter']) and !$options['hideSystem'] and $nbSortFields == 0)
		{
			return $this->_children;
		}
		
		$children = array();
		foreach ($this->_children as $child)
		{
			// Check type
			if (($options['files'] and $child->isFile()) or ($options['folders'] and $child->isFolder()))
			{
				// RegExp
				if (is_null($options['filter']) or preg_match($options['filter'], $child->getBasename()))
				{
					// Hidden system files
					if (!$options['hideSystem'] or !$child->isHidden())
					{
						$children[] = $child;
					}
				}
			}
		}
		
		// If sorting
		if ($nbSortFields > 0)
		{
			// Fill sort directions to match sort fields array length
			$nbDirections = count($options['sortDirection']);
			if ($nbDirections < $nbSortFields)
			{
				$lastDirection = ($nbDirections > 0) ? $options['sortDirection'][$nbDirections-1] : 'ASC';
				$options['sortDirection'] = array_merge($options['sortDirection'], array_fill(0, $nbSortFields-$nbDirections, $lastDirection));
			}
			
			// Build anonymous function
			$function = '';
			
			foreach($options['sort'] as $index => $field)
			{
				// Sorting direction
				$sortASC = (strtoupper($options['sortDirection'][$index]) == 'ASC');
				
				// Get values
				switch ($field)
				{
					case 'size':
						$function .= '$valA = $a->getSize();';
						$function .= '$valB = $b->getSize();';
						break;
					
					case 'modified':
						$function .= '$valA = $a->isFile() ? $a->getModifiedTime() : NULL;';
						$function .= '$valB = $b->isFile() ? $b->getModifiedTime() : NULL;';
						break;
					
					default:
						$function .= '$valA = strtolower($a->getBasename());';
						$function .= '$valB = strtolower($b->getBasename());';
						
						// Special behaviour: names starting by a '_'
						if ($options['underscoreFirst'])
						{
							$function .= '$startA = substr($valA, 0, 1);';
							$function .= '$startB = substr($valB, 0, 1);';
							$function .= 'if ($startA == \'_\' and $startB != \'_\') { return '.($sortASC ? '-1' : '1').'; }';
							$function .= 'elseif ($startA != \'_\' and $startB == \'_\') { return '.($sortASC ? '1' : '-1').'; }';
						}
						break;
				}
				
				$function .= 'if ($valA !== $valB) { return ($valA '.($sortASC ? '>' : '<').' $valB) ? 1 : -1; }';
			}
			
			// Equal entries
			$function .= 'return 0;';
			
			// Run
			usort($children, create_function('$a,$b', $function));
		}
		
		return $children;
	}
	
	/**
	 * Empty the folder
	 * 
	 * @return boolean true if successfully emptied, else false
	 */
	public function emptyFolder()
	{
		$retour = true;
		
		$children = $this->getChildren(array('hideSystem' => false));
		foreach ($children as $child)
		{
			if (!$child->delete())
			{
				$retour = false;
			}
		}
		
		return $retour;
	}
	
	/**
	 * Delete the folder
	 * 
	 * @return boolean true if successfully deleted, else false
	 */
	public function delete()
	{
		if ($this->emptyFolder())
		{
			return $this->getManager()->deleteFolder($this->getPath());
		}
	}
}