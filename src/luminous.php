<?php


require_once(dirname(__FILE__) . '/debug.php');

require_once(dirname(__FILE__) . '/cache.class.php');
require_once(dirname(__FILE__) . '/scanners.class.php');
require_once(dirname(__FILE__) . '/formatters/formatter.class.php');
require_once(dirname(__FILE__) . '/core/scanner.class.php');

/*
 * This file contains the public calling interface for Luminous. It's split
 * into two classes: one is basically the user-interface, the other is a
 * wrapper around it. The wrapper allows a single-line function call, and is
 * procedural. It's wrapped in an abstract class for a namespace.
 * The real class is instantiated into a singleton which is manipulated by
 * the abstract class methods.
 */

/**
 * @cond ALL
 *  This is kind of a pseudo-UI class. It's a singleton which will be
 * manipulated by a few procedural functions, for ease of use.
 * It's technically supposed to be private to this class, but this is a sort
 * of 'agreed' privateness, and we expose an instance of it globally.
 *
 * In fact, it is used by (at least) the diff scanner, which uses its
 * scanner table.
 * 
 * @internal
 * 
 */
class _Luminous {
  public $version = 'master';

  /// Settings array
  public $settings = array(
    'cache-age' => 777600, // 90 days
    'wrap-width' => -1,
    'line-numbers' => true,
    'auto-link' => true,
    'max-height' => 500,
    'format' => 'html',
    'theme' => 'luminous_light',
    'html-strict' => false,
    'relative-root' => null,
    'include-javascript' => false,
    'include-jquery' => true,
    'failure-tag' => 'pre'
  );


  public $scanners; ///< the scanner table
  

  public function __construct() {
    $this->scanners = new LuminousScanners();
    $this->register_default_scanners();
  }

  /// registers builtin scanners
  private function register_default_scanners() {
    // we should probably hide this in an include for neatness
    // when it starts growing.
    $language_dir = luminous::root() . '/languages/';
    
    
    // this is a dummy file which includes ECMAScript dependencies in a 
    // non-circular way.
    $this->scanners->AddScanner('ecma-includes', null, null, 
      "$language_dir/include/ecma.php");
      

    $this->scanners->AddScanner(array('as', 'actionscript'),
    'LuminousActionScriptScanner', 'ActionScript', "$language_dir/as.php", 
    'ecma');

    $this->scanners->AddScanner(array('bnf'), 
      'LuminousBNFScanner', 'Backus Naur Form', "$language_dir/bnf.php");

    $this->scanners->AddScanner(array('bash', 'sh'),
    'LuminousBashScanner', 'Bash', "$language_dir/bash.php");
    
    $this->scanners->AddScanner(array('c', 'cpp', 'h', 'hpp', 'cxx', 'hxx'),
      'LuminousCppScanner', 'C/C++', "$language_dir/cpp.php");
      
    $this->scanners->AddScanner(array('cs', 'csharp', 'c#'),
      'LuminousCSharpScanner', 'C#', "$language_dir/csharp.php");
      
    $this->scanners->AddScanner('css',
      'LuminousCSSScanner', 'CSS', "$language_dir/css.php");
      
    $this->scanners->AddScanner(array('diff', 'patch'),
      'LuminousDiffScanner', 'Diff', "$language_dir/diff.php");
      
    $this->scanners->AddScanner(array('html', 'htm'),
      'LuminousHTMLScanner', 'HTML', "$language_dir/html.php",
      array('js', 'css'));
      
    $this->scanners->AddScanner(array('ecma', 'ecmascript'),
      'LuminousECMAScriptScanner', 'ECMAScript', 
      "$language_dir/ecmascript.php", 'ecma-includes');

    $this->scanners->AddScanner(array('erlang', 'erl', 'hrl'),
      'LuminousErlangScanner', 'Erlang', "$language_dir/erlang.php");

    $this->scanners->AddScanner('go', 'LuminousGoScanner', 'Go',
      "$language_dir/go.php");
    

    $this->scanners->AddScanner(array('groovy'),
      'LuminousGroovyScanner', 'Groovy',
      "$language_dir/groovy.php");

    $this->scanners->AddScanner(array('haskell', 'hs'),
      'LuminousHaskellScanner', 'Haskell', "$language_dir/haskell.php");

    $this->scanners->AddScanner('java',
      'LuminousJavaScanner', 'Java', "$language_dir/java.php");
      
    $this->scanners->AddScanner(array('js', 'javascript'),
      'LuminousJavaScriptScanner', 'JavaScript', "$language_dir/javascript.php",
      array('ecma'));
      
    $this->scanners->AddScanner('json',
      'LuminousJSONScanner', 'JSON', "$language_dir/json.php");

    $this->scanners->AddScanner(array('latex', 'tex'),
      'LuminousLatexScanner', 'LaTeX', "$language_dir/latex.php");
      
    $this->scanners->AddScanner(array('lolcode', 'lolc', 'lol'),
      'LuminousLOLCODEScanner', 'LOLCODE', "$language_dir/lolcode.php");
      
    $this->scanners->AddScanner(array('m', 'matlab'),
      'LuminousMATLABScanner', 'MATLAB', "$language_dir/matlab.php");

    $this->scanners->AddScanner(array('perl', 'pl', 'pm'),
      'LuminousPerlScanner', 'Perl', "$language_dir/perl.php");

    $this->scanners->AddScanner(array('rails','rhtml', 'ror'),
      'LuminousRailsScanner', 'Ruby on Rails',
      "$language_dir/rails.php", array('ruby', 'html'));
      
    $this->scanners->AddScanner(array('ruby','rb'),
      'LuminousRubyScanner', 'Ruby', "$language_dir/ruby.php");

    $this->scanners->AddScanner(array('plain', 'text', 'txt'),
      'LuminousIdentityScanner', 'Plain', "$language_dir/identity.php");

    // PHP Snippet does not require an initial <?php tag to begin highlighting
    $this->scanners->AddScanner('php_snippet', 'LuminousPHPSnippetScanner',
      'PHP Snippet', "$language_dir/php.php", array('html'));
      
    $this->scanners->AddScanner('php',
      'LuminousPHPScanner', 'PHP', "$language_dir/php.php",
      array('html'));
      
    $this->scanners->AddScanner(array('python', 'py'),
      'LuminousPythonScanner', 'Python', "$language_dir/python.php");
    $this->scanners->AddScanner(array('scala', 'scl'),
      'LuminousScalaScanner', 'Scala', "$language_dir/scala.php", 'xml');
    $this->scanners->AddScanner(array('sql', 'mysql'),
      'LuminousSQLScanner', 'SQL', "$language_dir/sql.php");
      
    $this->scanners->AddScanner(array('vim', 'vimscript'),
      'LuminousVimScriptScanner', 'Vim Script', "$language_dir/vim.php");

    $this->scanners->AddScanner(array('vb', 'bas'),
      'LuminousVBScanner', 'Visual Basic', "$language_dir/vb.php",
      'xml');
      
    $this->scanners->AddScanner('xml', 'LuminousXMLScanner', 
      'XML', "$language_dir/xml.php", 'html');

    $this->scanners->SetDefaultScanner('plain');

  }


  /**
   * Returns an instance of the current formatter
   */
  function get_formatter() {
    $fmt_path = dirname(__FILE__) . '/formatters/';

    $fmt = $this->settings['format'];
    if (!is_string($fmt) && is_subclass_of($fmt, 'LuminousFormatter'))
      return clone $fmt;

    switch(strtolower($fmt)) {
      case 'html' :
        require_once($fmt_path . 'htmlformatter.class.php');
        return new LuminousFormatterHTML();
      case 'html-inline':
        require_once($fmt_path . 'htmlformatter.class.php');
        return new LuminousFormatterHTMLInline();
      case 'latex':
        require_once($fmt_path . 'latexformatter.class.php');
        return new LuminousFormatterLatex();
      case null:
      case 'none':
        require_once($fmt_path . 'identityformatter.class.php');
        return new LuminousIdentityFormatter();
      default:
        throw new Exception('Unknown formatter: ' . $this->settings['format']);
        return null;
    }
  }

  /**
   * Sets up a formatter instance according to our current options/settings
   */
  private function set_formatter_options(&$formatter) {
    $formatter->wrap_length = $this->settings['wrap-width'];
    $formatter->line_numbers = $this->settings['line-numbers'];
    $formatter->link = $this->settings['auto-link'];
    $formatter->height = $this->settings['max-height'];
    $formatter->strict_standards = $this->settings['html-strict'];
    $formatter->set_theme(luminous::theme($this->settings['theme']));
  }

  /**
   * calculates a 'cache_id' for the input. This is dependent upon the
   * source code and the settings. This should be (near-as-feasible) unique
   * for any cobmination of source, language and settings
   */
  private function cache_id($scanner, $source) {
    // to figure out the cache id, we mash a load of stuff together and
    // md5 it. This gives us a unique (assuming no collisions) handle to
    // a cache file, which depends on the input source, the relevant formatter
    // settings, the version, and scanner.
    $settings = array($this->settings['wrap-width'],
      $this->settings['line-numbers'],
      $this->settings['auto-link'],
      $this->settings['max-height'],
      $this->settings['format'],
      $this->settings['theme'],
      $this->settings['html-strict'],
      $this->version,
    );

    $id = md5($source);
    $id = md5($id . serialize($scanner));
    $id = md5($id . serialize($settings));
    return $id;
  }


  
  /**
   * The real highlighting function
   * @throw InvalidArgumentException if $scanner is not either a string or a
   *    LuminousScanner instance, or if $source is not a string.
   */
  function highlight($scanner, $source, $use_cache=true) {
    if (!is_string($source)) throw new InvalidArgumentException('Non-string '
        . 'supplied for $source');
    
    if (!($scanner instanceof LuminousScanner)) {
      if (!is_string($scanner)) throw new InvalidArgumentException('Non-string
        or LuminousScanner instance supllied for $scanner');
      $code = $scanner;
      $scanner = $this->scanners->GetScanner($code);
      if ($scanner === null) throw new Exception("No known scanner for '$code' and no default set");
    }
    $cache_obj = null;
    $cache_hit = true;
    $out = null;
    if ($use_cache) {
      $cache_id = $this->cache_id($scanner, $source);
      $cache_obj = new LuminousCache($cache_id);
      $cache_obj->purge_older_than = $this->settings['cache-age'];
      $cache_obj->purge();
      $out = $cache_obj->read();
    }
    if ($out === null) {
      $cache_hit = false;
      $out_raw = $scanner->highlight($source);
      $formatter = $this->get_formatter();
      $this->set_formatter_options($formatter);
      $out = $formatter->format($out_raw);
    }

    if ($use_cache && !$cache_hit) {
      $cache_obj->write($out);
    }
    return $out;
  }
}

/// @endcond
// ends ALL


// Here's our singleton.
global $luminous_; // sometimes need this or the object seems to disappear
$luminous_ = new _Luminous();

/// @cond USER

// here's our 'real' UI class, which uses the above singleton. This is all
// static because these are actually procudural functions, we're using the
// class as a namespace.
/**
 * @brief Users' API
 */
abstract class luminous {

  /**
   * @brief Highlights a string according to the current settings
   * 
   * @param $scanner The scanner to use, this can either be a langauge code,
   *    or it can be an instance of LuminousScanner.
   * @param $source The source string
   * @param $cache Whether or not to use the cache
   * @return the highlighted source code.
   *
   * To specify different output formats or other options, see set().
   */
  static function highlight($scanner, $source, $cache=true) {
    global $luminous_;
    try {
      return $luminous_->highlight($scanner, $source, $cache);
    } catch (InvalidArgumentException $e) {
      // this is a user error, let it bubble
      throw $e;
    }
    catch (Exception $e) {
      // this is an internal error or a scanner error, or something
      // it might not technically be Luminous that caused it, but let's not
      // make it kill the whole page in production code
      if (LUMINOUS_DEBUG) throw $e;
      else {
        $return = $source;
        if (($t = self::setting('failure-tag')))
          $return = "<$t>$return</$t>";
        return $return;
      }
    }
  }

  /**
   * @brief Highlights a file according to the current setings.
   * 
   * @param $scanner The scanner to use, this can either be a langauge code,
   *    or it can be an instance of LuminousScanner.
   * @param $file the source string
   * @param $cache Whether or not to use the cache
   * @return the highlighted source code.
   * 
   * To specify different output formats or other options, see set().
   */
  static function highlight_file($scanner, $file, $cache=true) {
    return self::highlight($scanner, file_get_contents($file), $cache);
  }

  /**
   * @brief Registers a scanner
   * 
   * Registers a scanner with Luminous's scanner table. Utilising this
   * function means that Luminous will handle instantiation and inclusion of
   * the scanner's source file in a lazy-manner.
   * 
   * @param $language_code A string or array of strings designating the
   *    aliases by which this scanner may be accessed
   * @param $classname The class name of the scanner, as string. If you
   *    leave this as 'null', it will be treated as a dummy file (you can use
   *    this to handle a set of non-circular include rules, if you run into
   *    problems).
   * @param $readable_language  A human readable language name
   * @param $path The path to the source file containing your scanner
   * @param dependencies An array of other scanners which this scanner
   *    depends on (as sub-scanners, or superclasses). Each item in the
   *    array should be a $language_code for another scanner.
   */
  static function register_scanner($language_code, $classname,
    $readable_language, $path, $dependencies=null) {
      global $luminous_;
      $luminous_->scanners->AddScanner($language_code, $classname,
        $readable_language, $path, $dependencies);
  }
  
  /**
   * @brief Get the full filesystem path to Luminous
   * @return what Luminous thinks its location is on the filesystem
   * @internal
   */
  static function root() {
    return realpath(dirname(__FILE__) . '/../');
  }

  /**
   * @brief Gets a list of installed themes
   * 
   * @return the list of theme files present in style/.
   * Each theme will simply be a filename, and will end in .css, and will not
   * have any directory prefix.
   */
  static function themes() {
    $themes_uri = self::root() . '/style/';
    $themes = array();
    foreach(glob($themes_uri . '/*.css') as $css) {
      $fn = trim(preg_replace("%.*/%", '', $css));
      switch($fn) {
        // these are special, exclude these
        case 'luminous.css':
        case 'luminous_print.css':
        case 'luminous.min.css':
          continue;
        default:
        $themes[] = $fn;
      }
    }
    return $themes;
  }

  /**
   * @brief Checks whether a theme exists
   * @param $theme the name of a theme, which should be suffixed with .css
   * @return @c TRUE if a theme exists in style/, else @c FALSE
   */
  static function theme_exists($theme) {
    return in_array($theme, self::themes());
  }
  
  /**
   * @brief Reads a CSS theme file
   * Gets the CSS-string content of a theme file.
   * Use this function for reading themes as it involves security
   * checks against reading arbitrary files
   * 
   * @param $theme The name of the theme to retrieve, which may or may not
   *    include the .css suffix.
   * @return the content of a theme; this is the actual CSS text.
   * @internal
   */
  static function theme($theme) {
    if (!preg_match('/\.css$/i', $theme)) $theme .= '.css';
    if (self::theme_exists($theme)) 
      return file_get_contents(self::root() . "/style/" . $theme);
    else
      throw new Exception('No such theme file: ' . $theme);
  }


  
  /**
   * @brief Gets a setting's value
   * @param $option The name of the setting
   * @return The value of the given setting
   * @throws Exception if the option is unrecognised
   */
  static function setting($option) {
    global $luminous_;
    if (!array_key_exists($option, $luminous_->settings))
      throw new Exception("Luminous: No such option: $option");
    return $luminous_->settings[$option];
  }

  /**
   * @brief Sets the given option to the given value
   * @param $option The name of the setting
   * @param $value The new value of the setting
   * @throws Exception if the option is unrecognised.
   */
  static function set($option, $value) {
    global $luminous_;
    if (!array_key_exists($option, $luminous_->settings))
      throw new Exception("Luminous: No such option: $option");
    else $luminous_->settings[$option] = $value;
  }

  /**
   * @brief Gets a list of registered scanners
   * 
   * @return a list of scanners currently registered. The list is in the
   * format:
   * 
   *    language_name => codes,
   * 
   * where language_name is a string, and codes is an array of strings. 
   * 
   * The array is sorted alphabetically by key.
   */
  static function scanners() {
    global $luminous_;
    $scanners = $luminous_->scanners->ListScanners();
    ksort($scanners);
    return $scanners;
  }

  /**
   * @brief Gets a formatter instance
   * 
   * @return an instance of a LuminousFormatter according to the current
   * format setting
   *
   * This shouldn't be necessary for general usage, it is only implemented
   * for testing.
   * @internal
   */
  static function formatter() {
    global $luminous_;
    return $luminous_->get_formatter();
  }

  /**
    * @internal
    * Comparison function for guess_language()'s sorting
    */
  static function __guess_language_cmp($a, $b) {
    $c = $a['p'] - $b['p'];
    if ($c === 0) return 0;
    elseif ($c < 0) return -1;
    else return 1;
  }



  /**
   * @brief Attempts to guess the language of a piece of source code
   * @param $src The source code whose language is to be guessed
   * @param $confidence The desired confidence level: if this is 0.05 but the
   *  best guess has a confidence of 0.04, then $default is returned. Note 
   *  that the confidence level returned by scanners is quite arbitrary, so
   *  don't set this to '1' thinking that'll give you better results. 
   *  A realistic confidence is likely to be quite low, because a scanner will
   *  only return 1 if it's able to pick out a shebang (#!) line or something
   *  else definitive. If there exists no such identifier, a 'strong' 
   *  confidence which is right most of the time might be as low as 0.1. 
   *  Therefore it is recommended to keep this between 0.1 and 0.10.
   * @param $default The default name to return in the event that no scanner
   * thinks this source belongs to them (at the desired confidence).
   *
   * @return A valid code for the best scanner, or $default.
   *
   * This is a wrapper around luminous::guess_language_full
   */
  static function guess_language($src, $confidence=0.05, $default = 'plain') {
    $guess = self::guess_language_full($src);
    if ($guess[0]['p'] >= $confidence) 
      return $guess[0]['codes'][0];
    else 
      return $default;
  }

  /**
   * @brief Attempts to guess the language of a piece of source code
   * @param $src The source code whose language is to be guessed
   * @return An array - the array is ordered by probability, with the most
   *    probable language coming first in the array.
   *    Each array element is an array which represents a language (scanner), 
   *    and has the keys:
   *    \li \c 'language' => Human-readable language description,
   *    \li \c 'codes' => valid codes for the language (array),
   *    \li \c 'p' => the probability (between 0.0 and 1.0 inclusive),
   * 
   * note that \c 'language' and \c 'codes' are the key => value pair from
   * luminous::scanners()
   *
   * @warning Language guessing is inherently unreliable but should be right
   * about 80% of the time on common languages. Bear in mind that guessing is
   * going to be severely hampered in the case that one language is used to
   * generate code in another language.
   *
   * Usage for this function will be something like this:
   * @code
   * $guesses = luminous::guess_language($src);
   * $output = luminous::highlight($guesses[0]['codes'][0], $src);
   * @endcode
   * 
   * @see luminous::guess_language 
   */
  static function guess_language_full($src) {
    global $luminous_;
    // first we're going to make an 'info' array for the source, which
    // precomputes some frequently useful things, like how many lines it 
    // has, etc. It prevents scanners from redundantly figuring these things
    // out themselves
    $lines = preg_split("/\r\n|[\r\n]/", $src);
    $shebang = '';
    if (preg_match('/^#!.*/', $src, $m)) $shebang = $m[0];

    $info = array(
      'lines' => $lines,
      'num_lines' => count($lines),
      'trimmed' => trim($src),
      'shebang' => $shebang
    );

    $return = array();
    foreach(self::scanners() as $lang=>$codes) {
      $scanner_name = $luminous_->scanners->GetScanner($codes[0], false,
        false);
      assert($scanner_name !== null);
      $return[] = array(
        'language' => $lang,
        'codes' => $codes,
        'p' => call_user_func(array($scanner_name, 'guess_language'), $src,
          $info)
      );
    }
    uasort($return, array('luminous', '__guess_language_cmp'));
    $return = array_reverse($return);
    return $return;
  }


/**
  * @brief Gets the markup you need to include in your web page
  * @return a string representing everything that needs to be printed in
  * the \<head\> section of a website.
  *
  * This is influenced by the following settings:
  *     relative-root,
  *     include-javascript,
  *     include-jquery
  *     theme
  */
  static function head_html() {
    global $luminous_;
    $theme = self::setting('theme');
    $relative_root = self::setting('relative-root'); 
    $js = self::setting('include-javascript');
    $jquery = self::setting('include-jquery');
    
    if (!preg_match('/\.css$/i', $theme)) $theme .= '.css';
    if (!self::theme_exists($theme)) $theme = 'luminous_light.css';
    
    if ($relative_root === null) {
      $relative_root = str_replace($_SERVER['DOCUMENT_ROOT'], '/',
        dirname(__FILE__));
      $relative_root = str_replace('\\', '/', $relative_root); // bah windows
      $relative_root = rtrim($relative_root, '/');
      // go up one level.
      $relative_root = preg_replace('%/[^/]*$%', '', $relative_root);
    }
    // if we ended up with any double slashes, let's zap them, and also
    // trim any trailing ones
    $relative_root = preg_replace('%(?<!:)//+%', '/', $relative_root);
    $relative_root = rtrim($relative_root, '/');
    $out = '';
    $out .= "<link rel='stylesheet' type='text/css'
      href='$relative_root/style/luminous.css'>\n";
    $out .= "<link rel='stylesheet' type='text/css'
      href='$relative_root/style/$theme'>\n";
    if ($js){
      if($jquery)
        $out .= "<script type='text/javascript'
          src='$relative_root/client/jquery-1.4.2.min.js'></script>\n";
      $out .= "<script type='text/javascript'
        src='$relative_root/client/luminous.js'></script>\n";
    }
    return $out;
  }
}

/// @endcond 
// ends user
