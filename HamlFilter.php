<?php

/**
 * @author Mikuláš Dítě
 */

namespace Nette\Latte\Filters;

use Nette\Object;
use Nette\Utils\Strings as String;
use Nette\Utils\Html\Tags;


class Haml extends Object
{

	const FORMAT_XHML = 'xhtml';
	const FORMAT_HTML4 = 'html4';
	const FORMAT_HTML5 = 'html5';
	

	protected $template;
	protected $config;
	protected $doctype;
	protected $tree;


	public function __construct(array $config = NULL)
	{
		$defaults = array(
			'format' => self::FORMAT_HTML5,
		);
		if ($config === NULL) {
			$config = array();
		}
		$this->config = array_merge($defaults, $config);
	}

	
	public function parse($template)
	{
		$this->template = $template;
		dd($this->template);
		$this->doctype = $this->getDoctype();
		$this->tree = $this->buildTree();
		dd($this->tree);
		die();
		
		// DO NOT CACHE IT
		$res = $this->toHtml();
		de($res);
		//return $this->toHtml();
	}



	public function isXhtml()
	{
		return $this->config['format'] === self::FORMAT_XHML;
	}


	/** @todo add support for custom encoding, or enforce utf-8 as latte does? */
	protected function getDoctype()
	{
		$match = String::match($this->template, '~^!{3}([ \t]+(?P<doctype>strict|frameset|5|1\.1|basic|mobile|rdfa|)([ \t]+(?P<encoding>[^ \n]+))?)?$~im');
		if (!isset($match['doctype'])) {
			return $this->isXhtml() ? Tags::DOCTYPE_TRANS : Tags::DOCTYPE_4_TRANS;
		}
		switch(strToLower($match['doctype'])) {
		case 'strict':
			return $this->isXhtml() ? Tags::DOCTYPE_STRICT : Tags::DOCTYPE_4_STRICT;
		case 'frameset':
			return $this->isXhtml() ? Tags::DOCTYPE_FRAMESET : Tags::DOCTYPE_4_FRAMESET;
		case '5':
			return Tags::DOCTYPE_5;
		case '1.1':
			return Tags::DOCTYPE_1_1;
		case 'basic':
			return Tags::DOCTYPE_BASIC;
		case 'mobile':
			return Tags::DOCTYPE_MOBILE;
		case 'rdfa':
			return Tags::DOCTYPE_RDFA;
		}
	}
	
	
	/** @todo and how are you planning to put these lines back? */
	protected function getStrippedTemplate()
	{
		return String::replace($this->template, '~^[ \t]*\\\\~m');
	}
	
	
	protected function buildTree()
	{
		$indent = NULL;
		$level = 0;
		$level_last = 0;
		$tree = array();
		$last_node = NULL;
		$line_number = 0;
		$parents = array();
		
		foreach (explode("\n",  $this->template) as $line) {
			$line_number++;
			
			if (trim($line) === '') continue;
			
			$match = String::match($line, '~^(?P<indent>[ \t]*)(?P<value>.*)$~i');
			if ($match['indent'] === '') {
				$level = 0;
			} elseif ($indent === NULL && $level_last === 0) {
				$indent = $match['indent'];
				d('setting indent to', $indent);
				$level = 1;
			} else {
				$level = 0;
				do {
					$level++;
					$test = str_repeat($indent, $level);
					if ($level > $level_last + 1) {
						throw new HamlException("Invalid indentation detected. You should always indent children by one scope only.", NULL, $line_number);
					} elseif (strlen($test) > strlen($match['indent'])) {
						throw new HamlException("Invalid indentation detected. Use either spaces or tabs, but not both.", NULL, $line_number);
					}
				} while ($test !== $match['indent']);
			}
			
			$element = String::match($match['value'], '~^(%(?P<tag>[A-Z0-9]+))?(?P<spec>((\.|#)[A-z0-9_-]+)*)(\[(?<opt>.*)\])?(?P<value>.*$)~i');
			if ($element['tag'] === '' && $element['spec'] === '') {
				if (isset($parent[$level]['value'])) {
					$parent[$level]['value'] .= ' ' . $match['value'];
				} else {
					$parent[$level]['value'] = $match['value'];
				}
				continue;
			}
			
			// clean the match
			foreach ($element as $key => $value)
				if (is_int($key)) unset($element[$key]);
			
			
			$element['attrs'] = array();
			// set id
			$id = String::match($element['spec'], '~#(?P<id>[A-Z0-9_-]+)~i');
			$element['attrs']['id'] = $id['id'];
			
			// set classes
			$element['attrs']['class'] = array();
			foreach (String::matchAll($element['spec'], '~\.(?P<class>[A-Z0-9_]+)~i') as $m) {
				$element['attrs']['class'][] = $m['class'];
			}
			
			// set attributes
			foreach (String::matchAll($element['opt'], '~(?P<key>[A-Z0-9_-]+)[ \t]*=>[ \t]*(?P<value>.*)(?=,|$)~i') as $m) {
				$element['attrs'][$m['key']] = $m['value'];
			}
			unset($element['spec']);
			unset($element['opt']);
			
			
			if ($level === 0) {
				$tree[] = array('parent' => &$tree, 'element' => $element, 'children' => array());
				$last_node = &$tree[count($tree) - 1];
				
			} elseif ($level > $level_last) { // insert child
				$last_node['children'][] = array('parent' => &$last_node, 'element' => $element, 'children' => array());
				$last_node = &$last_node['children'][count($last_node['children']) - 1];
				
			} elseif ($level == $level_last) { // insert sibling
				$last_node['parent']['children'][] = array('parent' => &$last_node['parent'], 'element' => $element, 'children' => array());
				$last_node = &$last_node['parent']['children'][count($last_node['parent']['children']) - 1];
				
			} elseif ($level < $level_last) {
				$temp = $level;
				do {
					$last_node = &$last_node['parent'];
					$temp++;
				} while ($temp != $level_last + 1);
				$last_node['children'][] = array('parent' => &$last_node, 'element' => $element, 'children' => array());
				$last_node = &$last_node['children'][count($last_node['children']) - 1];
			}
			$parents[$level] = &$last_node;
			
			$level_last = $level;
		}

		return $tree;
	}



	protected function toHtml()
	{
		$html = $this->doctype;
		foreach ($this->tree as $key => $value) {
			d($value);
		}
	}

}

class HamlException extends \Nette\Templating\FilterException {}
