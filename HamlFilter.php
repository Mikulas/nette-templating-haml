<?php

/**
 * @author Mikuláš Dítě
 */

namespace Nette\Templating\Filters;

use Nette\Object;
use Nette\Utils\Strings as String;
use Nette\Utils\UnsafeHtml as UnsafeHtml;
use Nette\Utils\Html as Html;
use Nette\Utils\Html\Tags;


class Haml extends Object
{

	const FORMAT_XHML = 'xhtml';
	const FORMAT_HTML4 = 'html4';
	const FORMAT_HTML5 = 'html5';



	/** @var string */
	protected $template;

	/** @var array */
	protected $config;

	/** @var array */
	protected $tree;

	/** @var \Nette\Utils\UnsafeHtml */
	protected $defaultContainer;



	/**
	 * @param array $config keys: format
	 */
	public function __construct(array $config = NULL)
	{
		$defaults = array(
			'format' => self::FORMAT_HTML5,
		);

		if ($config === NULL)
			$config = array();

		$this->config = array_merge($defaults, $config);
		$this->defaultContainer = UnsafeHtml::el('div');
		UnsafeHtml::$xhtml = Html::$xhtml = $this->isXhtml();
	}



	/**
	 * @param string $template
	 * @throws Nette\Latte\Filters\HamlException
	 */
	public function __invoke($template)
	{
		return $this->parse($template);
	}



	/**
	 * @param string $template
	 * @throws Nette\Latte\Filters\HamlException
	 * @return string filtered template
	 */
	public function parse($template)
	{
		if (trim($template) === '') {
			return $template;
		}

		$this->template = $template;
		$this->tree = $this->buildTree();

		return $this->toUnsafeHtml();
	}



	/**
	 * @return bool
	 */
	public function isXhtml()
	{
		return $this->config['format'] === self::FORMAT_XHML;
	}



	/**
	 * @return string doctype
	 */
	protected function getDoctype()
	{
		// @todo only search the first line
		$match = String::match($this->template, '~^[ \t]*!{3}([ \t]+(?P<doctype>strict|frameset|5|1\.1|basic|mobile|rdfa|))?$~im');
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



	/**
	 * @throws Nette\Latte\Filters\HamlException
	 * @return array
	 */
	protected function buildTree()
	{
		$tree = array();
		$parents = array(0 => &$tree);

		$indent_master = NULL;
		$last_indent = $indent = '';
		$indents = array(); // level => minimal indent

		$level = $last_level = 0;
		$textual = $last_textual = FALSE;

		$line_number = 0;
		foreach (explode("\n",  $this->template) as $line) {
			$line_number++;

			if (trim($line) === '') continue;

			/** @todo add warning if not on first line */
			if (String::match($line, '~^[ \t]*!{3}([ \t]+(?P<doctype>strict|frameset|5|1\.1|basic|mobile|rdfa|))?$~im')) {
				$tree['children'][] = array('value' => $this->getDoctype(), 'line' => $line_number);
				continue;
			}

			$match = String::match($line, '~^(?P<indent>[ \t]*)(?P<value>.*)$~i');
			$element = String::match($match['value'], '~^(?P<escaped>\\\\)?(%(?P<tag>[A-Z0-9]+))?[ \t]*(?P<spec>((\.|#)[A-Z0-9_-]+)*)[ \t]*(\[(?P<opt>.*)\])?[ \t]*(?P<value>.*)$~i');
			$textual = $element['escaped'] || ($element['tag'] === '' && $element['spec'] === '');
			$indent = $match['indent'];

			$level = NULL;
			if ($indent === '') {
				$level = 0;
				if ($textual || $indent_master === NULL) {
					$indents = array(0 => '');
				} else {
					$indents = array(0 => '', 1 => $indent_master);
				}

			} elseif ($indent_master === NULL && $last_level === 0) {
				$indent_master = $indent;
				$level = $last_textual ? 0 : 1;
				if ($last_textual && $textual) {
					$indents = array(0 => '');
				} elseif ($last_textual || $textual) {
					$indents = array(0 => '', 1 => $indent_master);
				} else {
					$indents = array(0 => '', 1 => $indent_master, 2 => $indent_master . $indent_master);
				}

			} else {
				foreach (array_reverse($indents, TRUE) as $i_level => $i_indent) {
					if (strLen($indent) >= strLen($i_indent)) {
						$level = $i_level;
						break;
					}
				}

				foreach ($indents as $i_level => $i) {
					if ($i_level > $level) {
						unset($indents[$i_level]);
					}
				}

				if (!$textual) {
					$indents[$level + 1] = $indent . $indent_master;
				}

			}
			$last_indent = $indent;

			// validate indenting
			$test = '';
			while(strLen($test) < strLen($indent)) {
				$test .= $indent_master;
			};
			if ($test !== $indent) {
				throw new HamlException("Invalid indentation detected. Use either spaces or tabs, but not both.", NULL, $line_number);
			}

			// if the value is textual, insert it into the tree
			if ($textual) {
				if ($element['escaped'] && ($element['tag'] !== '' || $element['spec'] !== ''))
					$match['value'] = substr ($match['value'], 1);

				$match['value'] = $this->parseMacro($match['value']);
				if (isset($parents[$level]['children']) && count($parents[$level]['children'])
				 && !is_array($parents[$level]['children'][count($parents[$level]['children']) - 1])) {
					$match['value'] = ' ' . $match['value'];
				}
				$parents[$level]['children'][] = array('value' => $match['value'], 'line' => $line_number);

				$last_level = $level;
				$last_textual = TRUE;
				continue;
			}
			$last_textual = FALSE;

			// clean the match
			foreach ($element as $key => $value)
				if (is_int($key)) unset($element[$key]);


			$element['attrs'] = array();
			// set id
			$id = String::match($element['spec'], '~#(?P<id>[A-Z0-9_-]+)~i');
			$element['attrs']['id'] = $id['id'];

			// set attributes
			/** @todo rewrite me plase */
			$rgx_macro = '~\{.*?\}~i';
			$macros = String::matchAll($element['opt'], $rgx_macro);
			foreach ($macros as $index => $q) {
				$element['opt'] = String::replace($element['opt'], '~' . preg_quote($q[0], '~') . '~', "__MACRO_STRING_<$index>__");
			}
			$rgx_quote = '~(\'|")(?P<quoted>.*?)\\1~i';
			$quotes = String::matchAll($element['opt'], $rgx_quote);
			foreach ($quotes as $index => $q) {
				$element['opt'] = String::replace($element['opt'], '~' . preg_quote($q[0], '~') . '~', "__QUOTED_STRING_<$index>__");
			}

			foreach (String::matchAll($element['opt'], '~(?P<key>[:A-Z0-9_-]+)([ \t]*=>[ \t]*(?P<value>.*?)(?=,|$))?~i') as $m) {
				if (isset($m['value'])) {
					foreach ($macros as $index => $q) {
						$m['value'] = String::replace($m['value'], "~__MACRO_STRING_<$index>__~", $q[0]);
					}
					foreach ($quotes as $index => $q) {
						$m['value'] = String::replace($m['value'], "~__QUOTED_STRING_<$index>__~", $q['quoted']);
					}
					$element['attrs'][$m['key']] = $m['value'];
				} else {
					$element['attrs'][$m['key']] = TRUE;
				}
			}

			// set classes
			$element['attrs']['class'] = isset($element['attrs']['class']) ? array($element['attrs']['class']) : array();
			foreach (String::matchAll($element['spec'], '~\.(?P<class>[A-Z0-9_-]+)~i') as $m) {
				if (!in_array($m['class'], $element['attrs']['class']))
					$element['attrs']['class'][] = $m['class'];
			}

			$parents[$level]['children'][] = array('element' => $element, 'children' => array(), 'line' => $line_number);
			$parents[$level + 1] = &$parents[$level]['children'][count($parents[$level]['children']) - 1];

			$element['value'] = $this->parseMacro($element['value']);

			// treat value as text children node
			if ($element['value'] !== '')
				$parents[$level + 1]['children'][] = array('value' => $element['value'], 'line' => $line_number);

			$last_level = $level;
		}

		return $tree;
	}



	/**
	 * @return string html
	 */
	protected function toUnsafeHtml()
	{
		$line = 1;
		$html = $this->nodeToUnsafeHtml($this->tree, $line);
		$html .= "\n";

		return $html;
	}



	/**
	 * @param array $tree
	 * @param int $level
	 * @return string html fragment
	 */
	protected function nodeToUnsafeHtml($tree, & $line, $level = 0)
	{
		$html = '';
		foreach ($tree['children'] as $node) {
			if ($line != $node['line']) {
				$html .= str_repeat("\n", $node['line'] - $line);
				$html .= str_repeat("\t", $level);
				$line = $node['line'];
			}

			if (isset($node['children'])) {
				$element = $node['element'];
				$container = $element['tag'] === '' ? clone $this->defaultContainer : UnsafeHtml::el($element['tag']);
				$container->addAttributes($element['attrs']);

				$last_textual = FALSE;
				$html .= $container->startTag();
				$html .= $this->nodeToUnsafeHtml($node, $line, $level + 1);
				$html .= $container->endTag();

			} else {
				$last_textual = TRUE;
				$html .= $node['value'];
			}
		}
		return $html;
	}


	/**
	 * @return bool
	 */
	private function hasChildrenElements($tree)
	{
		foreach ($tree['children'] as $node) {
			if (is_array($node))
				return TRUE;
		}
		return FALSE;
	}



	/**
	 * Evalutes value node and replaces macros with Latte syntax if found
	 * @param string $string
	 */
	private function parseMacro($string)
	{
		$macro = String::match($string, '~^[ \t]*(?P<raw>!?)=(?!>)[ \t]*(?P<cmd>.*)$~im');
		if ($macro !== NULL) {
			if (String::match($macro['cmd'], '~^($|input|label)~i'))
				$string = '{' . ($macro['raw'] ? '!' : '') . $macro['cmd'] . '}';
			else
				$string = '{'  . ($macro['raw'] ? '!=' : '=') . $macro['cmd'] . '}';
		}

		return $string;
	}

}

class HamlException extends \Nette\Templating\FilterException {}
