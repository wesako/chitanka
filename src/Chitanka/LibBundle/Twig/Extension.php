<?php

namespace Chitanka\LibBundle\Twig;

use Chitanka\LibBundle\Util\Number;
use Chitanka\LibBundle\Util\Char;
use Chitanka\LibBundle\Util\String;
use Chitanka\LibBundle\Legacy\Legacy;

class Extension extends \Twig_Extension
{

	public function getName()
	{
		return 'chitanka';
	}

	public function getFunctions()
	{
		return array(
			'anchor_name' => new \Twig_Function_Method($this, 'getAnchorName'),
			'cover' => new \Twig_Function_Method($this, 'getCover'),
			'progressbar' => new \Twig_Function_Method($this, 'getProgressbar'),
		);
	}

	public function getFilters()
	{
		return array(
			'rating_class' => new \Twig_Filter_Method($this, 'getRatingClass'),
			'rating_format' => new \Twig_Filter_Method($this, 'formatRating'),
			'name_format' => new \Twig_Filter_Method($this, 'formatPersonName'),
			'acronym' => new \Twig_Filter_Method($this, 'getAcronym'),
			'first_char' => new \Twig_Filter_Method($this, 'getFirstChar'),
			'email' => new \Twig_Filter_Method($this, 'obfuscateEmail'),
			'doctitle' => new \Twig_Filter_Method($this, 'getDocTitle'),
			'lower' => new \Twig_Filter_Method($this, 'strtolower'),
			'json' => new \Twig_Filter_Method($this, 'getJson'),
			'repeat' => new \Twig_Filter_Method($this, 'repeatString'),
			'replace_var' => new \Twig_Filter_Method($this, 'replaceVar'),
			'join_lists' => new \Twig_Filter_Method($this, 'joinLists'),
			'humandate' => new \Twig_Filter_Method($this, 'getHumanDate'),
			'nl2br' => new \Twig_Filter_Method($this, 'nl2br', array('pre_escape' => 'html', 'is_safe' => array('html'))),
			'dot2br' => new \Twig_Filter_Method($this, 'dot2br'),
			'user_markup' => new \Twig_Filter_Method($this, 'formatUserMarkup'),
			'striptags' => new \Twig_Filter_Method($this, 'stripTags'),
			'domain' => new \Twig_Filter_Method($this, 'getDomain'),
			'link' => new \Twig_Filter_Method($this, 'formatLinks'),
			'encoding' => new \Twig_Filter_Method($this, 'changeEncoding'),
			'urlencode' => new \Twig_Filter_Method($this, 'getUrlEncode'),
			'qrcode' => new \Twig_Filter_Method($this, 'getQrCode'),
		);
	}

	public function getTests()
	{
		return array(
			'url' => new \Twig_Test_Method($this, 'isUrl'),
		);
	}


	public function getRatingClass($rating)
	{
		if ( $rating >= 5.6 ) return 12;
		if ( $rating >= 5.2 ) return 11;
		if ( $rating >= 4.8 ) return 10;
		if ( $rating >= 4.4 ) return 9;
		if ( $rating >= 4.0 ) return 8;
		if ( $rating >= 3.6 ) return 7;
		if ( $rating >= 3.2 ) return 6;
		if ( $rating >= 2.8 ) return 5;
		if ( $rating >= 2.4 ) return 4;
		if ( $rating >= 2.0 ) return 3;
		if ( $rating >= 1.5 ) return 2;
		if ( $rating >= 1.0 ) return 1;
		return 0;
	}

	public function formatRating($rating)
	{
		return Legacy::rmTrailingZeros( Number::formatNumber($rating, 1) );
	}

	public function formatPersonName($name, $sortby = 'first-name')
	{
		if (empty($name)) {
			return $name;
		}
		preg_match('/([^,]+) ([^,]+)(, .+)?/', $name, $m);
		if ( ! isset($m[2]) ) {
			return $name;
		}
		$last = "<span class=\"lastname\">$m[2]</span>";
		$m3 = isset($m[3]) ? $m[3] : '';

		return $sortby == 'last-name' ? $last.', '.$m[1].$m3 : $m[1].' '.$last.$m3;
	}


	public function getAcronym($title)
	{
		$letters = preg_match_all('/ ([a-zA-Zа-яА-Я\d])/u', ' '.$title, $matches);
		$acronym = implode('', $matches[1]);

		return Char::mystrtoupper($acronym);
	}

	public function getFirstChar($string)
	{
		return mb_substr($string, 0, 1, 'UTF-8');
	}

	public function strtolower($string)
	{
		return mb_strtolower($string, 'UTF-8');
	}

	public function getJson($content)
	{
		return json_encode($content);
	}

	public function obfuscateEmail($email)
	{
		return strtr($email,
			array('@' => '&#160;<span title="при сървъра">(при)</span>&#160;'));
	}

	public function getDocTitle($title)
	{
		$title = preg_replace('/\s\s+/', ' ', $title);
		$title = strtr($title, array(
			'<br>' => ' — ',
			'&amp;' => '&', // will be escaped afterwards by Twig
		));
		$title = trim(strip_tags($title));

		return $title;
	}

	public function repeatString($string, $count)
	{
		return str_repeat($string, $count);
	}

	/**
	* @param mixed   $string     Base string to work with
	* @param string  $vars       Array with strings or a string
	* @param string  $value      A replacement value
	*/
	public function replaceVar($string, $vars, $value)
	{
		foreach ((array) $vars as $var) {
			if ($var[strlen($var)-1] == '*') {
				$string = preg_replace('/\{'.substr($var, 0, strlen($var)-1).'.+\}/', $value, $string);
			} else {
				$string = str_replace('{'.$var.'}', $value, $string);
			}
		}

		return $string;
	}

	public function joinLists($string)
	{
		return preg_replace('|</ul>\n<ul[^>]*>|', "\n", $string);
	}

	public function getHumanDate($date)
	{
		return Legacy::humanDate($date);
	}


	private $_xmlElementCreator = null;

	/**
	* Generate an anchor name for a given string.
	*
	* @param string  $text    A string
	* @param boolean $unique  Always generate a unique name
	*                         (consider all previously generated names)
	*/
	public function getAnchorName($text, $unique = true)
	{
		if (is_null($this->_xmlElementCreator)) {
			$this->_xmlElementCreator = new \Sfblib_XmlElement;
		}

		return $this->_xmlElementCreator->getAnchorName($text, $unique);
	}


	public function getCover($id, $width = 200, $format = 'jpg')
	{
		return Legacy::getContentFilePath('book-cover', $id) . ".$width.$format";
	}


	public function getProgressbar($progressInPerc)
	{
		$perc = $progressInPerc .'%';
		$progressBarWidth = '20';
		$bar = str_repeat(' ', $progressBarWidth);
		$bar = substr_replace($bar, $perc, $progressBarWidth/2-1, strlen($perc));
		$curProgressWidth = ceil($progressBarWidth * $progressInPerc / 100);
		// done bar end
		$bar = substr_replace($bar, '</span>', $curProgressWidth, 0);
		$bar = strtr($bar, array(' ' => '&#160;'));

		return "<pre style=\"display:inline\"><span class=\"progressbar\"><span class=\"done\">$bar</span></pre>";
	}


	public function nl2br($value, $sep = '<br>')
	{
		return str_replace("\n", $sep."\n", $value);
	}

	public function dot2br($value)
	{
		return str_replace('.', "<br>\n", $value);
	}


	public function formatUserMarkup($content)
	{
		return String::pretifyInput(String::escapeInput($content));
	}

	public function stripTags($content)
	{
		return strip_tags($content);
	}


	public function changeEncoding($string, $encoding)
	{
		return iconv('UTF-8', $encoding, $string);
	}


	public function getUrlEncode($string)
	{
		return urlencode($string);
	}


	public function getQrCode($url)
	{
		return 'http://chart.apis.google.com/chart?cht=qr&chs=150x150&chld=H|0&chl='. urlencode($url);
	}


	public function getDomain($url)
	{
		return parse_url($url, PHP_URL_HOST);
	}

	public function formatLinks($text)
	{
		return preg_replace('|https?://\S+[^,.\s]|e', "'<a href=\"$0\">'.\$this->getDomain('$0', '$2').'</a>'", $text);
	}

	public function isUrl($string)
	{
		return strpos($string, 'http') === 0;
	}
}
