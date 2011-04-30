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
		$this->buildTree();
		de($this->doctype);
		de($this->template);
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
		
		$rgx = '~^(?P<indent>[ \t]*)(?P<type>#|%|\.)(?P<name>[^ \t]+?)(?P<attr>\{.*\})?([ \t]+(?P<value>.*?))?[\r\n]{1,2}~ism';
		d($rgx);
		foreach (String::matchAll($this->getStrippedTemplate(), $rgx) as $element) {
			if ($element['indent'] === '') {
				$level = 0;
			} elseif ($indent === NULL && $level === 0) {
				$indent = $element['indent'];
				$level = 1;
			} else {
				$level = 0;
				do {
					$level++;
					$test = str_repeat($indent, $level);
					if ($level_last + 1 < $level) {
						throw new HamlException("Invalid indentation detected. You should always indent children by one scope only.");
					} elseif (strlen($test) > strlen($element['indent'])) {
						throw new HamlException("Invalid indentation detected. Use either spaces or tabs, but not both.");
					}
				} while ($test !== $element['indent']);
			}
			$this->tree[] = array('level' => $level, 'element' => $element);
			d($element['name'], $level_last, $level);
			/*
			array(
				array('el' =>  $el, 'children' => array()),
			);
			*/
			$level_last = $level;
		}
		de($this->tree);
	}

}

class HamlException extends \Nette\Templating\FilterException {}
