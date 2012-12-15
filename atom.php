<?php

header('Content-Type: application/atom+xml; charset=UTF-8');

echo '<?xml version="1.0" encoding="utf-8"?>';

define("EB_PATH", dirname(__FILE__).'/');
define("EB_APP", EB_PATH.'app/');
define("EB_DATA", EB_PATH.'data/');

require_once EB_APP.'site.php';
require_once EB_APP.'event.php';

global $feed;

$site = new EBSite('Transgender Agenda Schweiz');
$feed = new EBShowEventsFeed();

?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <author>
    <name><?php echo $site->title; ?></name>
  </author>
  <title><?php echo $site->title; ?></title>
  <link href="http://www.tg-agenda.ch/v1.1/atom.php" rel="self" />
  <id>http://tg-agenda.ch/</id>
  <updated><?php echo date('c', filemtime(EB_DATA.EBEvents::FILE)); ?></updated>
  
  <?php 
  $events = new EBEvents();
  
  var_dump($events);
  ?>
  
  <?php echo $feed->content; ?>

</feed>