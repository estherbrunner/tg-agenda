<?php

define("EB_PATH", dirname(__FILE__).'/');
define("EB_APP", EB_PATH.'app/');
define("EB_DATA", EB_PATH.'data/');

require_once EB_APP.'site.php';
require_once EB_APP.'event.php';
require_once EB_APP.'user.php';
require_once EB_APP.'org.php';

global $page;

$site = new EBSite('Transgender Agenda Schweiz');
$page = $site->page();
$orgs = EBSite::organisations();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title><?php echo $site->title; ?></title>
    <link rel="stylesheet" media="all" type="text/css" href="css/style.css">
    <link rel="stylesheet" media="print" type="text/css" href="css/print.css" />
    <link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico">
    <!-- <script type="text/javascript" src="js/jquery.js"></script> -->
    <script type="text/javascript" src="js/script.js"></script>
  </head>
  <body>
    <div id="header" role="banner">
      <h1><a href="index.php"><?php echo $site->title; ?></a></h1>
      <?php echo $page->header; ?>
    </div>
    <div id="left">
      <?php
      include EB_DATA.'intro.html';
      echo $orgs->linklist();
      ?>
    </div>
    <div id="content" role="main">
      <?php
      if ($title = $page->title) echo '<h2>'.$title.'</h2>';
      echo $page->message;
      echo $page->content;
      ?>
    </div>
    <div id="footer" role="contentinfo">
      <?php
      echo $page->footer;
      include EB_DATA.'footer.html';
      ?>
      <a title="Clicky Web Analytics" href="http://getclicky.com/97677"><img alt="Clicky Web Analytics" src="http://static.getclicky.com/media/links/badge.gif" border="0" width="1" height="1" /></a>
      <script src="http://static.getclicky.com/97677.js" type="text/javascript"></script>
      <noscript><p><img alt="Clicky" width="1" height="1" src="http://static.getclicky.com/97677-db12.gif" /></p></noscript>
    </div>
  </body>
</html>
