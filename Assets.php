<?php

namespace SimplePHP;

/**
 * Small asset manager for PHP v1.0
 * (https://github.com/simple-php/assets)
 *
 * Copyright 2017-2020 Puchkin Dmitriy
 *
 * Licensed under MIT
 * (https://github.com/simple-php/assets/blob/master/LICENSE)
 */

class Assets {
    static $libs = [
        'jquery' => [
            'https://code.jquery.com/jquery-3.4.1.min.js'
        ],
        'jquery-ui' => [
            'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
            'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js'
        ],
        'bootstrap' => [
            'https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css',
            'https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js',
        ],
        'eModal' => [
            'bootstrap',
            'jquery',
            'https://rawgit.com/saribe/eModal/master/dist/eModal.min.js'
        ],
        'vue' => [
            'debug' => 'https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.10/vue.js',
            'prod' => 'https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.10/vue.min.js'
        ],
    ];

    static $debug = false;

    /**
     * Include js or css file, or defined asset in {@see Assets::$libs}
     * @param $assets string|array Asset(s) to include to page output
     */
    static function add($assets) {
        self::getInstance()->addFromArray($assets);
    }

    static function addScript($script, $scriptId = null) {
        self::getInstance()->script($script, $scriptId);
    }

    static function addStyle($style, $selector = null) {
        self::getInstance()->style($style, $selector);
    }

    private static $_instance = null;

    /**
     * Return singleton instance
     * @return null|Assets
     */
    static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new Assets();
        }
        return self::$_instance;
    }

    static function getJs()
    {
        return self::getInstance()->js();
    }

    static function getCss()
    {
        return self::getInstance()->css();
    }

    protected $_css = [];
    protected $_js = [];
    protected $_libs = [];
    protected $_scriptIds = [];
    protected $_scripts = [];
    protected $_styles = [];

    /**
     * Include asset library, defined in {@see Assets::$libs}
     * @param $name string Library name, must be defined as key in {@see Assets::$libs} array
     */
    function addLib($name) {
        if (isset($this->_libs[$name])) return;
        if (isset(self::$libs[$name])) {
            $this->_libs[$name] = true;
            $this->addFromArray(self::$libs[$name]);
        }
    }

    /**
     * Include single js file
     * @param $url string URL
     */
    function addJs($url) {
        $this->_js[$url] = true;
    }

    /**
     * Include single css file
     * @param $url string URL
     */
    function addCss($url) {
        $this->_css[$url] = true;
    }

    /**
     * Include assets from array
     * @param $assets
     */
    function addFromArray($assets) {
        if (!is_array($assets)) $assets = [$assets];
        foreach ($assets as $key => $asset) {
            if ($key === 'debug') {
                if (self::$debug) $this->addFromArray($asset);
            } else if ($key === 'prod') {
                if (!self::$debug) $this->addFromArray($asset);
            } else {
                if (isset(self::$libs[$asset])) {
                    $this->addLib($asset);
                } elseif (substr($asset, -4) == '.css' || strpos($asset,'.css?v=') !== false) {
                    $this->addCss($asset);
                } else {
                    $this->addJs($asset);
                }
            }
        }
    }

    /**
     * Returns HTML for including all added js assets
     * @param bool $flush Clean all added js assets or not
     * @return string
     */
    function js($flush = true) {
        $js = $this->_js;
        if ($flush) $this->_js = [];
        $html = '';
        foreach ($js as $url => $inc) {
            $html .= '<script src="'.$url.'"></script>'.PHP_EOL;
        }
        if (!empty($this->_scripts)) {
            $scripts = $this->_scripts;
            if ($flush) $this->_scripts = [];
            $html .= '<script>'.PHP_EOL;
            foreach ($scripts as $script) {
                $html .= $script . PHP_EOL;
            }
            $html .= '</script>';
        }
        return $html;
    }

    /**
     * Returns HTML for including all added css assets
     * @param bool $flush Clean all added css assets or not
     * @return string
     */
    function css($flush = true) {
        $css = $this->_css;
        if ($flush) $this->_css = [];
        $html = '';
        foreach ($css as $url => $inc) {
            $html .= '<link rel="stylesheet" href="'.$url.'">'.PHP_EOL;
        }
        if (!empty($this->_styles)) {
            $styles = $this->_styles;
            if ($flush) $this->_styles = [];
            $html .= '<style>'.PHP_EOL;
            foreach ($styles as $selector => $style)
            {
                if ($selector) {
                    $html .= $selector . ' { ' . $style . ' }' . PHP_EOL;
                } else {
                    $html .= $style;
                }
            }
            $html .= '</style>';
        }
        return $html;
    }

    /**
     * Include js code
     * @param $script string js code
     * @param $scriptId string|null Unique js code identifier to omit repeated inclusion
     */
    function script($script, $scriptId = null) {
        if (!isset($scriptId) || !isset($this->_scriptIds[$scriptId])) {
            $this->_scripts[] = $script;
            if (isset($scriptId)) $this->_scriptIds[$scriptId] = true;
        }
    }

    /**
     * Returns, if style with specified selector already was added
     * @param string $selector
     * @return bool
     */
    function hasStyle($selector) {
        return isset($this->_styles[$selector]);
    }

    /**
     * Returns style with specified selector
     * @param string $selector
     * @return string|false style string or false if style with specified selector doesn`t exists
     */
    function getStyle($selector) {
        return (hasStyle($selector) ? $this->_styles[$selector] : false);
    }

    /**
     * Converts style specified as array or Css object to string
     * @param $style
     * @return string
     */
    protected function _styleToString(&$style) {

        if (is_array($style)) {
            $css = $this->newStyle();
            foreach ($style as $prop => $val) {
                $css[$prop] = $val;
            }
            return $css->css();
        } elseif (is_a($style, '\\SimplePHP\\Css\\Style')) {
            return $style->css();
        }
        return $style.PHP_EOL;
    }

    /**
     * Include css code
     * Css code can be string, array, or Css style object {@see Assets::newStyle}
     * @param $style string|array|Css CSS style code
     * @param $selector string|null CSS selector, required if CSS style code contains only css rules,
     *  and must be null if css style code already contains selector
     */
    function style($style, $selector = null) {
        if (!$selector) {
            if (!isset($this->_styles[''])) $this->_styles[''] = '';
            else $this->_styles[''] .= PHP_EOL;
            $this->_styles[''] .= $this->_styleToString($style);
        } else {
            $css = $this->_styleToString($style);
            if ($css) {
                if (!$this->hasStyle($selector)) {
                    $this->_styles[$selector] = '';
                } else {
                    $this->_styles[$selector] .= PHP_EOL;
                }
                $this->_styles[$selector] .= $css;
            }
        }
    }

    /**
     * Returns {@see Style} helper object for building style
     * You can add style from this helper object with {@see Assets::addStyle} method
     * or {@see Style::save save} method inside helper object
     * @return Css
     */
    static function newStyle() {
        return new Style(self::getInstance());
    }
}

/**
 * CSS helper object for building css style
 *
 * # Usage:
 * First, instantiate this object from {@see Assets::newStyle} method
 * Next, you can specify css properties using magic methods of this object
 * or using this object as array, or using method {@see Style::add add}
 * ```
 * $style = Assets::newStyle();
 *
 * $style->backgroundColor = 'red';
 * $style->font = '12px bold Arial';
 *
 * $style['background-color'] = 'red';
 * $style['font'] = '12px bold Arial';
 *
 * $style->add('background-color: red; font: 12px bold Arial');
 * ```
 * To include style to assets use methods {@see Style::save save} or
 * {@see Css::saveClass saveClass} or {@see Assets::addStyle}
 * ```
 * $style->save('span.required');
 * ```
 */
class Style implements \ArrayAccess {
    static protected $_cssIndex = [];
    static protected $_classes = [];

    protected $_assets = null;
    protected $_css = [];
    
    function __construct($assets = null)
    {
        $this->_assets = $assets;
        if (!isset($this->_assets)) {
            $this->_assets = Assets::getInstance();
        }
    }

    function _filterProp($propName) {
        // camelCase to dash-separated
        return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', str_replace('_','-', trim($propName))));
    }

    function __get($prop) {
        $prop = $this->_filterProp($prop);
        return $this->_css[$prop];
    }

    function __set($prop, $value) {
        $prop = $this->_filterProp($prop);
        $this->_css[$prop] = $value;
    }

    function add($style) {
        if (is_array($style)) {
            foreach ($style as $prop => $value) {
                $this->__set($prop, $value);
            }
        } elseif (is_string($style)) {
            $styles = explode(';', $style);
            foreach ($styles as $s) {
                $val = explode(':', $s, 2);
                if (count($val) == 2) {
                    $this[$val[0]] = $val[1];
                }
            }
        }
    }

    function css() {
        $css = '';
        ksort($this->_css);
        foreach ($this->_css as $prop => $value) {
            $css .= $prop .':'.$value.';';
        }
        return $css;
    }

    /**
     * Save CSS style with specified selector
     * @param string $selector Селектор CSS
     */
    function save($selector) {
        $this->_assets->addStyle($this, $selector);
    }

    /**
     * Save CSS style with specified classname selector with reusing already defined classnames
     * @param string $className Preferred classname, if not specified, classname will be random
     * @return bool|string specified className or already existing class name with same style 
     */
    function saveClass($className = '') {
        $css = $this->css();
        if ($css === '') return false;
        if (isset(self::$_cssIndex[$css])) {
            $className = self::$_cssIndex[$css];
        } else {
            if ($className === '') {
                $classNamePrefix = 'class';
                $className = $classNamePrefix . rand(0, 2000000000);
            } else {
                $classNamePrefix = $className;
            }
            while (isset(self::$_classes[$className])) {
                $className = $classNamePrefix . rand(0, 2000000000);
            }
            self::$_classes[$className] = true;
            self::$_cssIndex[$css] = $className;
            $this->_assets->addStyle($css, '.'.$className);
        }

        return $className;
    }

    #region Реализация интерфейса ArrayAccess

    public function offsetExists($offset)
    {
        return isset($this->_css[$this->_filterProp($offset)]);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->_css[$this->_filterProp($offset)]);
    }

    #endregion
}



