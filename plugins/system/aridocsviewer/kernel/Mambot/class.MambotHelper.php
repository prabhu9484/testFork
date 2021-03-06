<?php
/*
 * ARI Framework Lite
 *
 * @package		ARI Framework Lite
 * @version		1.0.0
 * @author		ARI Soft
 * @copyright	Copyright (c) 2009 www.ari-soft.com. All rights reserved
 * @license		GNU/GPL (http://www.gnu.org/copyleft/gpl.html)
 * 
 */

defined('ARI_FRAMEWORK_LOADED') or die('Direct Access to this location is not allowed.');

AriKernel::import('Utils.Utils');

class AriMambotHelper extends AriObject
{
	function &_currentCallback(&$callback)
	{
		static $currentCallback = null;

		if ($callback !== false)
		{
			$currentCallback = $callback;
		}
		
		return $currentCallback;
	}
	
	function processTagList($text, $tag, &$callback)
	{
		$tagRegExp = '/\{' . $tag . '(\s+[a-z\_0-9]+=(?:"[^"]*"|&quot;.*?&quot;|[^\s}]*))*\s*\}(?:(.*?)\{\/' . $tag . '\})?/si';
		$className = __CLASS__;
		$false = false;
		$oldCallback = AriMambotHelper::_currentCallback($false);

		AriMambotHelper::_currentCallback($callback);
		$backtrack_limit = @ini_set("pcre.backtrack_limit", -1);
		
		$result = preg_replace_callback($tagRegExp, 
			create_function('$matches', 
				'return ' . $className . '::parseTagList($matches);'),
			$text);

		@ini_set("pcre.backtrack_limit", $backtrack_limit);

		AriMambotHelper::_currentCallback($oldCallback);

		return $result;
	}
	
	function parseTagList($matches)
	{
		$false = false;
		$callback =& AriMambotHelper::_currentCallback($false);

		if (!empty($callback) && isset($matches[0]))
		{
			$innerContent = isset($matches[2]) ? $matches[2] : '';
			$attrs = AriMambotHelper::parseAttributes($matches[0]);

			return call_user_func($callback, $attrs, $innerContent);
		}

		return '';
	}

	function parseAttributes($text)
	{
		$pos = strpos($text, '}');
		if ($pos > 0) $text = substr($text, 0, $pos);

		$attrRegExp = '/([a-z\_0-9]+)=("[^"]*"|&quot;.*?&quot;|[^\s]*)/ui';
		$attrs = array();
		$matches = array();
		preg_match_all($attrRegExp, $text, $matches, PREG_SET_ORDER);
		if (is_array($matches))
		{		
			foreach ($matches as $match)
			{
				if (isset($match[1]) && isset($match[2])) $attrs[$match[1]] = trim(html_entity_decode($match[2], ENT_COMPAT, 'UTF-8'), '"');
			}
		}

		return $attrs;
	}
	
	function getParameters($tag, $type = 'content')
	{
		global $database;

		$params = null;

		$plugin =& JPluginHelper::getPlugin($type, $tag);
    	$params = new JParameter(AriUtils::getParam($plugin, 'params', ''));

		return $params;
	}
}
?>