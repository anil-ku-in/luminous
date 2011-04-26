<?php
error_reporting(E_ALL | E_STRICT);
assert_options(ASSERT_BAIL, 1);
require_once('../src/luminous.php');

luminous::set('max-height', 300);
luminous::set('theme', 'geonyx');
luminous::set('relative-root', '../');
if (isset($_POST['theme'])) luminous::set('theme', $_POST['theme']);
if (isset($_POST['format'])) luminous::set('format', $_POST['format']); 
$line_numbers = true;
if (!empty($_POST) && !isset($_POST['line-numbers'])) 
  $line_numbers = false;

luminous::set('line-numbers', $line_numbers);
?>
<!DOCTYPE html>
<html>
  <head>
  <?php echo luminous::head_html(); ?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

  </head>
<body>
  <?php
    if (count($_POST)) {
      // turn off caching for the moment or it's hard to see what changes
      // are having effect
      $t = microtime(true);
      $out = luminous::highlight($_POST['lang'], $_POST['src'], false);
      $t1 = microtime(true);
      echo ($t1-$t) . 'seconds <br>';
      echo strlen($out) . '<br>';
      echo $out;
    }
    ?>
  <div style='text-align:center'>
    <form method='post' action='interface.php'>
    <select name='lang'>
    <?php foreach(luminous::scanners() as $lang=>$codes) {
      $def = (isset($_POST['lang']) && $_POST['lang'] === $codes[0])?
        ' selected' : '';
      echo "<option value='{$codes[0]}'$def>$lang</option>\n";
    } ?>
    <option value='no_such_scanner'>error case</option>
    </select>
    <br/>
    <select name='theme'>
    <?php foreach(luminous::themes() as $t) {
      $def = (isset($_POST['theme']) && $_POST['theme'] === $t)? ' selected': 
          '';
      echo sprintf("<option value='%s'%s>%s</option>\n", $t, $def,
        preg_replace('/\.css$/i', '', $t));
    }
?>  </select>
    <br/>
    <select name='format'>    
    <?php foreach(array('html', 'latex') as $f) {
      $def = (isset($_POST['format']) && $_POST['format'] === $f)? ' selected': 
          '';
      echo sprintf("<option value='%s'%s>%s</option>\n", $f, $def, $f);

    }
?>  </select>
  <br/>
  <label>Line numbers</label>
  <input type='checkbox' name='line-numbers'<?= $line_numbers? ' checked' : ''?>>
    <br/>

    <textarea rows=15 cols=75 name='src'><?php
    if (isset($_POST['src'])) echo htmlspecialchars($_POST['src']);
    ?></textarea>
    <br/>
    <input type=submit>
    </form>
  </div>
</body>
</html>
