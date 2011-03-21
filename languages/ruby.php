<?php

/*
 * Ruby's grammar is basically insane. We're not going to aim to correctly
 * highlight all legal Ruby code because we'll be here all year and we'll still
 * get it wrong, but we're going to have a go at getting the standard stuff
 * right as well as:
 *   heredocs
 *   balanced AND NESTED string/regex delimiters
 *   interpolation
 */

/*
 * TODO: This is very incomplete right now. We have got a skeletal
 * and badly tested implementation consisting of:
 * balanced/nested string delimiters and interpolation,
 * numerics, $@ variables, ...
 *
 * We currently need to do AT LEAST:
 *  keywords, slash delimited regexen, heredoc,
 */

class LuminousRubyScanner extends LuminousScanner {


  // set to true if this is a nested scanner which needs to exit if it
  // encounters a } while nothing else is on the stack, i.e. it is being
  // used to process an interpolated block
  public $interpolation = false;


  // gaaah
  private $numeric = '/
  (?:
    #control codes
    (?:\?(?:\\\[[:alpha:]]-)*[[:alpha:]])
    |
    #hex
    (?:0[xX](?>[0-9A-Fa-f]+)[lL]*)
    |
    # binary
    (?:0[bB][0-1]+)
    |
    #octal
    (?:0[oO0][0-7]+)
    |
    # regular number
    (?:
      (?>[0-9]+)
      (?:
        # fraction
        (?:
          (?:\.?(?>[0-9]+)?
            (?:(?:[eE][\+\-]?)?(?>[0-9]+))?
          )
        )
      )?
    )
    |
    (
      # or only after the point, float x = .1;
      \.(?>[0-9]+)(?:(?:[eE][\+\-]?)?(?>[0-9]+))?
    )
  )
  (?:_+\d+)*
  /x';  

  private static function balance_delimiter($delimiter) {
    $map = array('[' => ']', '{' => '}', '<' => '>', '('=>')');
    $out = isset($map[$delimiter])? $map[$delimiter] : $delimiter;
    return $out;
  }
  private static function is_balanced($delimiter) {
    return ($delimiter === '[' || $delimiter === '{' || $delimiter === '<'
      || $delimiter === '(');
  }



  public function init() {
    $this->add_identifier_mapping('KEYWORD', array('BEGIN', 'END', 'alias',
      'begin', 'break', 'case', 'class', 'def', 'defined', 'do',
      'else', 'elseif', 'end', 'ensure', 'for', 'if', 'module', 'next',
      'redo', 'rescue', 'retry', 'return', 'self', 'super', 'then',
      'undef', 'unless', 'until', 'when', 'while', 'yield',
      'false', 'nil', 'self', 'true', '__FILE__', '__LINE__', 'TRUE', 'FALSE',
      'NIL', 'STDIN', 'STDERR', 'ENV', 'ARGF', 'ARGV', 'DATA', 'RUBY_VERSION',
      'RUBY_RELEASE_DATE', 'RUBY_PLATFORM'
      ));
  }



  public function main() {

    while (!$this->eos()) {

      if ($this->interpolation && $this->state() === null && $this->peek() === '}')
        break;

      // handles nested string delimiters and interpolation
      // interpolation is handled by passing the string down to a sub-scanner,
      // which is expected to figure out where the interpolation ends.

      // TODO, all of these closing delimiters need to test for escaping
      if (($s = $this->state()) !== null) {
        $balanced = $s[1] !== $s[2];
        $interp = $s[4];
        $next_patterns = array($s[1], $s[2]);
        if ($interp) $next_patterns[] = '#{';
        $next = $this->get_next_strpos($next_patterns);
        $old_pos = $this->pos();
        if ($next[0] === -1)
          $this->pos(strlen($this->string()));
        else
          $this->pos($next[0] + strlen($next[1]));
        
        if($next[1] === '#{') {
          $i = count($this->state_);
          while($i--) {
            $s_ = $this->state_[$i];
            if ($s_[0] !== null) {
              $this->record(
                substr($this->string(), $s_[3], $this->pos() - $s_[3]),
                $s_[0]);
              break;
            }
          }
          $interpolation_scanner = new LuminousRubyScanner();
          $interpolation_scanner->string($this->string());
          $interpolation_scanner->pos($this->pos());
          $interpolation_scanner->interpolation = true;
          $interpolation_scanner->main();
          $this->record($interpolation_scanner->tagged(), 'INTERPOLATION', true);
          $this->pos($interpolation_scanner->pos());

          $this->state_[$i][3] = $this->pos();

        }
        elseif ($balanced && $next[1] === $s[1]) { // balanced nesting
          $this->state_[] = array(null, $s[1], $s[2], null, $interp);
        }
        else {
          $pop = array_pop($this->state_);
          if ($pop[0] !== null) {
            $this->record(
              substr($this->string(), $pop[3], $this->pos() - $pop[3]),
              $pop[0]
            );
          }
        }
        continue;
      }

      $this->skip_whitespace();
      $c = $this->peek();
      if ($c === '=' && $this->scan('/^=begin .*? (^=end|\\z)/msx')) {
        $this->record($this->match(), 'DOCCOMMENT');
      }
      elseif($c === '#' && $this->scan('/#.*/'))
        $this->record($this->match(), 'COMMENT');
      
      elseif($this->scan($this->numeric) !== null) {
        $this->record($this->match(), 'NUMERIC');
      }
      elseif( $c === '$' && $this->scan('/\\$
  (?:
    (?:[!@`\'\+1~=\/\\\,;\._0\*\$\?:"])
    |
    (?: &(?:amp|lt|gt); )
    |
    (?: -[0adFiIlpvw])
    |
    (?:DEBUG|FILENAME|LOAD_PATH|stderr|stdin|stdout|VERBOSE)
  )/x') || $this->scan('/(\\$|@@?|:)\w+/')) {
        $this->record($this->match(), 'VARIABLE');
      }
      elseif ( $c === '<' && $this->check('/<<(-?)(?=[\'`"a-z])/i')) {
        // heredoc is a tiny bit ugly.
        // Heredocs can stack, so let's get a list of all the heredocs opened
        // on this line, and keep a list of the declarations as an array:
        // [delimiter, identable?, interpolatable?]
        // BTW I have no idea what happens if you nest an interpolatable one
        // inside a non-interpolatable one.
        $heredoc_queue = array();
        while (!$this->eol()) {
          // TODO:we need this soemhow linked up with all the other rules:
          //     <<-EOF + "a string"  is I think legal.
          $this->skip_whitespace();
          if ($this->scan('/(<<(-?))([\'"`]?)([A-Z_]\w*)(\\3)/i')) {
            $m = $this->match_groups();
            $this->record($m[0], 'KEYWORD');/*
            $this->record($m[1], null);
            if ($m[3]) // 2 is nested in 1
              $this->record($m[3], null);
            $this->record($m[4], 'KEYWORD');
            if ($m[5])
              $this->record($m[5], null);*/
            $hdoc = array($m[4], $m[2] === '-', $m[3] !== "'");
            $heredoc_queue[] = $hdoc;
          }
          // slow
          else $this->record($this->get(), null);
        }
        assert (!empty($heredoc_queue)) or die();
        
        $start = $this->pos();
        
        for($i=0; $i<count($heredoc_queue) ; ) {
          $top = $heredoc_queue[$i];
          list($ident, $identable, $interpolatable) = $top;
          $searches = array(
            sprintf('/^%s%s\\b/m', $identable? "[ \t]*" : '',
              preg_quote($ident, '/'))
          );
          if ($interpolatable)
            $searches[] = '/\#\{/';
          list($next, $matches) = $this->get_next($searches);
          if ($next === -1) {
            $this->record(substr($this->string(), $start), 'HEREDOC');
            $this->terminate();
            break;
          }
          assert($matches !== null);
          if ($matches[0] === '#{') { // interpolation, break heredoc and do that.
            $this->pos($next + strlen($matches[0]));
            $this->record(substr($this->string(), $start, $this->pos()-$start), 'HEREDOC');
            // c+p alert
            $interpolation_scanner = new LuminousRubyScanner();
            $interpolation_scanner->string($this->string());
            $interpolation_scanner->pos($this->pos());
            $interpolation_scanner->interpolation = true;
            $interpolation_scanner->main();
            $this->record($interpolation_scanner->tagged(), 'INTERPOLATION', true);
            $this->pos($interpolation_scanner->pos());
            $start = $this->pos();
          }
          else {
            $this->pos($next);
            $this->record(substr($this->string(), $start, $this->pos()-$start), 'HEREDOC');
            $this->record($matches[0], 'KEYWORD');
            $this->pos($next + strlen($matches[0]));
            $start = $this->pos();
            $i++;
          }
        }
      }
      elseif (($c === '"' || $c === "'" || $c === '`' || $c === '%') &&
        $this->scan('/[\'"`]|%([qQrswWx])(?![[:alnum:]])/')) {
        $interpolation = false;
        $delimiter = $this->match();
        if ($this->match() === '"') {          
          $interpolation = true;
        }
        elseif($this->match() === "'" || $this->match() === '`') {}
        else {
          $delimiter = $this->get();
          $m1 = $this->match_group(1);
          if ($m1 === 'Q' || $m1 === 'r' || $m1 === 'W' || $m1 === 'x')
            $interpolation = true;
        }
        $this->state_[] = array('STRING', $delimiter, self::balance_delimiter($delimiter),
          $this->match_pos(), $interpolation);
      }
      elseif( (ctype_alpha($c) || $c === '_') &&
        $this->scan('/[_a-zA-Z]\w*[!?]?/'))
        $this->record($this->match(), 'IDENT');
      elseif($this->scan('/[!%^&*\-+=:;|<>\/?]+/'))
        $this->record($this->match(), 'OPERATOR');
      else {
        $this->record($this->get(), null);
      }

    }
  }

  
}