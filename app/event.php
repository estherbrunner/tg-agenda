<?php

class EBEvents extends EBArray {

  const FILE = 'events.txt';
  
  public function sort(){
    uasort($this->array, array($this, 'compare'));
    $this->changed = true;
  }
  
  public function changed(){
    $this->changed = true;
  }
  
  protected function load(){
    $file = EB_DATA.self::FILE;
    if (!file_exists($file)) return;
    $rows = file($file);
    foreach ($rows as $row){
      if (($row = trim($row)) == '') continue;
      list($org, $date, $title, $city, $link, $desc, $draft) = explode(parent::TAB, $row);
      $event = new EBEvent($org, $date, $title, $city, $link, $desc, $draft);
      $this->array[$event->id] = $event;
    }
    $this->loaded = true;
  }
  
  protected function save(){
    // $this->sort();
    $data = implode(parent::NL, $this->array);
    file_put_contents(EB_DATA.self::FILE, $data);
    $this->changed = false;
  }
  
  private function compare($a, $b){
    if ($a->date == $b->date) return 0;
    elseif ($a->date < $b->date) return -1;
    else return 1;
  }

}

class EBEvent {

  private $id, $date, $title, $city, $link, $desc, $org, $draft;

  public function __construct($org, $date, $title, $city, $link, $desc, $draft){
    $this->org   = $org;
    $this->date  = $date;
    $this->title = $title;
    $this->city  = $city;
    $this->link  = $link;
    $this->desc  = $desc;
    $this->draft = (bool)$draft;
  }
  
  public function __get($key){
    switch($key){
      case 'id':    return md5($this->date.$this->title);
      case 'org':
      case 'date':
      case 'title':
      case 'city':
      case 'link':
      case 'desc':  
      case 'draft': return $this->$key;
      default:      return NULL;
    }
  }
  
  public function __set($key, $value){
    switch($key){
      case 'org':
      case 'date':
      case 'title':
      case 'city':
      case 'link':
      case 'desc':  
      case 'draft':
        $this->$key = $value;
        return true;
      default:
        return false;
    }
  }
  
  public function __toString(){
    return implode(EBArray::TAB, array($this->org, $this->date, $this->title, $this->city, $this->link, $this->desc, $this->draft));
  }

}

abstract class EBEventPage extends EBPage {

  /* protected static $cities = array(
    'Aarau', 'Basel', 'Bern', 'Biel', 'Chur', 'Fribourg', 'Genève', 'Konstanz',
    'Lausanne', 'Locarno', 'Lugano', 'Luzern', 'Neuchâtel', 'Schaffhausen',
    'Sion', 'Solothurn', 'St. Gallen', 'Thun', 'Winterthur', 'Zürich'
  ); */
  
  protected $events;
  
  public function __construct($msg = NULL, $code = NULL){
    $this->events = EBSite::events();
    parent::__construct($msg, $code);
  }
  
  protected function date_input($date){
    $this->line('<label for="day">Datum:</label><input type="text" size="2" maxlength="2" id="day" name="day" value="'.date('j', $date).'" />.', 5);
    $this->line('<select id="month" name="month">', 5);
    for ($i = 1; $i < 13; $i++){
      $value    = (strlen($i) == 1 ? '0'.$i : (string)$i);
      $selected = ($value == date('m', $date) ? 'selected="selected"' : '');
      $this->line('<option value="'.$value.'"'.$selected.'>'.$this->monthname($value).'</option>', 6);
    }
    $this->line('</select>', 5);
    $this->line('<input type="text" size="4" maxlength="4" id="year" name="year" value="'.date('Y', $date).'" /><br />', 5);
  }
  
  protected function monthname($month){
    switch ($month){
      case '01': return 'Januar';
      case '02': return 'Februar';
      case '03': return 'März';
      case '04': return 'April';
      case '05': return 'Mai';
      case '06': return 'Juni';
      case '07': return 'Juli';
      case '08': return 'August';
      case '09': return 'September';
      case '10': return 'Oktober';
      case '11': return 'November';
      case '12': return 'Dezember';
      default:   return '';
    }
  }
  
}

class EBShowEventsPage extends EBEventPage {

  const DATE = 1, CITY = 2;

  public function view(){
    /* if (isset($_REQUEST['monat']))   $this->month($_REQUEST['monat']);
    elseif (isset($_REQUEST['ort'])) $this->city($_REQUEST['ort']);
    else                             $this->month(); */
    $this->month();
  }
  
  private function month($month = NULL){
    if (!isset($month)){
      // $this->title = date('Y'); // 'Startseite';
      $start = time() - 24 * 60 * 60;
      $end   = $start + (365 * 24 * 60 * 60); // Standard: zeige nächste 365 Tage
    } else {
      list($year, $month) = explode('-', $month);
      $this->title = $this->monthname($month).' '.$year;
      $start = strtotime($year.'-'.$month);
      if ($month == '12'){
        $year++;
        $month = '01';
      } else {
        $month++;
        if (strlen($month) == 1) $month = '0'.$month;
      }
      $end = strtotime($year.'-'.$month);
    }
    $this->eventlist(self::DATE, array($start, $end));
  }
  
  /*
  private function city($city){
    $this->title = urldecode($city);
    $this->eventlist(self::CITY, $this->title);
  } */

  private function eventlist($filter, $params){
    // $this->refinements();
    $events = array();
    foreach ($this->events as $event){
      if (($filter == self::DATE) && (($event->date < $params[0]) || ($event->date > $params[1]))) continue;
      elseif (($filter == self::CITY) && ($event->city != $params)) continue;
      $events[] = $event;
    }
    $user = EBSite::user();
    if (count($events) == 0){
      $this->no_event();
    } else {
      $keys = array_keys($events);
      $month = '';
      foreach ($events as $event){
        if (!isset($user) && ($event->draft)) continue;
        if (!$month){
          $this->table_begin(isset($user));
          $month = $this->table_newmonth($event->date, isset($user));
          $odd = true;
        } elseif ($month != $this->monthname(date('m', $event->date)).' '.date('Y', $event->date)){
	      $month = $this->table_newmonth($event->date, isset($user));
        }
        $this->line('<tr class="'.($odd ? 'odd' : 'even').($event->draft ? ' draft' : '').'">', 5);
        $this->event_data($event);
        if (isset($user)) $this->event_actions($event);
        $this->line('</tr>', 5);
        $odd = !$odd;
      }
      $this->table_finish();
    }
    if (isset($user)) $this->action_bar();
  }
  
  private function no_event(){
    $this->line('<p class="info message">Leider sind uns für diese Auswahl keine Veranstaltungen bekannt.</p>', 3);
  }
  
  private function event_data($event){
    $this->line('<td headers="date">'.$this->date($event->date).'<br />'.htmlspecialchars($event->city).'</td>', 6);
    $this->line('<td headers="org">'.$this->organisations($event->org).'</td>', 6);
    $this->line('<td headers="title">'.
      ($event->link ? '<a href="'.htmlspecialchars($event->link).'" title="'.htmlspecialchars($event->link).'" target="_blank">' : '').
		  htmlspecialchars($event->title).'<br />'.htmlspecialchars($event->desc).
		  ($event->link ? '</a>' : '').
		  '</td>', 6);
  }
  
  private function event_actions($event){
    $id = $event->id;
    $this->line('<td headers="action" class="right">', 6);
    if ($event->draft){
      $this->event_action($id, 'Save', 'Freischalten', 'accept');
      $this->event_action($id, 'Save', 'Entfernen', 'remove');
    } else {
      $this->event_action($id, 'Edit', 'Bearbeiten');
      $this->event_action($id, 'Duplicate', 'Duplizieren');
    }
    $this->line('</td>', 6);
  }
  
  private function event_action($id, $action, $title, $icon = NULL){
    if (!isset($icon)) $icon = strtolower($action);
    $this->line('<form action="index.php" method="post">', 7);
    $this->line('<input type="hidden" name="id" value="'.$id.'" />', 8);
	  $this->line('<input type="hidden" name="do" value="'.$action.'Event" />', 8);
    $this->line('<button type="submit" name="submit" value="'.$title.'" title="'.$title.'">', 8);
    $this->line('<img src="img/icon/'.$icon.'.png" alt="'.$title.'" height="16" width="16" />', 8);
    $this->line('</button>', 8);
    $this->line('</form>', 7);  
  }
  
  private function action_bar(){
    $this->line('<form action="index.php" method="post">', 3);
    $this->line('<div class="bar">', 4);
    $this->line('<input type="hidden" name="do" value="AddEvent" />', 5);
    $this->line('<span class="right"><input type="submit" name="submit" value="Veranstaltung hinzufügen" /></span>', 5);
    $this->line('</div>', 4);
    $this->line('</form>', 3);
  }
  
  private function table_begin($editor = false){
    $this->line('<table class="events" cellspacing="0">', 3);
    $this->line('<thead>', 4);
    $this->line('<tr>', 5);
    $this->line('<th id="date">Datum und Ort</th>', 6);
    $this->line('<th id="org">Veranstalter</th>', 6);
    $this->line('<th id="title">Veranstaltung und Bemerkungen</th>', 6);
    if ($editor) $this->line('<th id="action" class="right">Aktion</th>', 6);
    $this->line('</tr>', 5);
    $this->line('</thead>', 4);
    $this->line('<tbody>', 4);
  }
  
  private function table_newmonth($date, $editor = false){
    $month = $this->monthname(date('m', $date)).' '.date('Y', $date);
    $this->line('<tr>', 5);
    $this->line('<th colspan="'.($editor ? '4' : '3').'">'.$month.'</th>', 6);
    $this->line('</tr>', 5);
    return $month;
  }
  
  private function table_finish(){
    $this->line('</tbody>', 4);
    $this->line('</table>', 3);
  }
  
  /*
  private function refinements(){
    // $this->calendar();
    // $this->cities();
    $this->line('<br class="clear">', 3);
  }
  
  private function calendar(){
    static $months = array(
      array('01' => 'Jan', '02' => 'Feb', '03' => 'März'),
      array('04' => 'Apr', '05' => 'Mai', '06' => 'Juni'),
      array('07' => 'Juli', '08' => 'Aug', '09' => 'Sept'),
      array('10' => 'Okt', '11' => 'Nov', '12' => 'Dez')
    );
    $year = date('Y');
    $this->line('<div id="calendar">', 3);
    $this->line('<h3>Zeitraum einschränken:</h3>', 4);
    $this->line('<p id="next60days"><span class="current">Nächste 60 Tage</span></p>', 4);
    $this->line('<table class="calendar">', 4);
    $this->line('<thead>', 5);
    $this->line('<tr>', 6);
    $this->line('<th colspan="3">'.$year.'</th>', 7);
    $this->line('</tr>', 6);
    $this->line('</thead>', 5);
    $this->line('<tbody>', 5);
    foreach ($months as $quarter){
      $this->line('<tr>', 6);
      foreach ($quarter as $num => $month){
        $this->line('<td><a href="?monat='.$year.'-'.$num.'" title="'.$this->monthname($num).'">'.$month.'</a></td>', 7);
      }
      $this->line('</tr>', 6);
    }
    $this->line('</tbody>', 5);
    $this->line('</table>', 4);
    $this->line('</div>', 3);
  }
  
  private function cities(){
    $this->line('<div id="col1cities">', 3);
    $this->line('<h3>Ort einschränken:</h3>', 4);
    $this->line('<p id="allcities"><span class="current">Ganze Schweiz</span></p>', 4);
    $this->line('<ul class="cities">', 4);
    $cities = array_slice(parent::$cities, 0, 6);
    foreach ($cities as $city){
      $this->line('<li><a href="?ort='.urlencode($city).'">'.$city.'</a></li>', 5);
    }
    $this->line('</ul>', 4);
    $this->line('</div>', 3);
    $this->line('<div id="col2cities">', 3);
    $this->line('<ul class="cities">', 4);
    $cities = array_slice(parent::$cities, 6, 7);
    foreach ($cities as $city){
      $this->line('<li><a href="?ort='.urlencode($city).'">'.$city.'</a></li>', 5);
    }
    $this->line('</ul>', 4);
    $this->line('</div>', 3);
    $this->line('<div id="col3cities">', 3);
    $this->line('<ul class="cities">', 4);
    $cities = array_slice(parent::$cities, 13, 7);
    foreach ($cities as $city){
      $this->line('<li><a href="?ort='.urlencode($city).'">'.$city.'</a></li>', 5);
    }
    $this->line('</ul>', 4);
    $this->line('</div>', 3);
  } */
  
  private function organisations($orgs){
    $orgs = explode('/', $orgs);
    $result = array();
    foreach ($orgs as $org){
      $org = trim($org);
      $obj = EBSite::organisation($org);
      if ($obj instanceof EBOrganisation) $result[] = $obj->logo;
      else $result[] = $org;
    }
    return implode('<br />', $result);
  }
  
  private function date($date){
    return date('j', $date).'. '.$this->monthname(date('m', $date)); // .date(' Y', $date);
  }

}

class EBAddEventPage extends EBEventPage {
  
  public function view(){
    $user = EBSite::user();
    $this->title = 'Veranstaltung hinzufügen';
    if (!isset($user)) $this->line('<p class="info message">Achtung: Die Veranstaltung wird erst nach der Freischaltung durch die Moderatorin angezeigt werden.</p>', 3);
    $this->line('<form action="index.php" method="post">', 3);
    $this->line('<fieldset>', 4);
    $this->line('<input type="hidden" name="do" value="SaveEvent" />', 5);
    $this->line('<label for="org">Veranstalter:</label><input type="text" id="org" name="org" value="" /><br />', 5);
    $this->date_input(time() + (30 * 24 * 60 * 60)); // now + 30 days
    $this->line('<label for="city">Ort:</label><input type="text" id="city" name="city" value="" /><br />', 5);
    $this->line('<label for="title">Veranstaltungstitel:</label><input class="long" type="text" id="title" name="title" value="" /><br />', 5);
    $this->line('<label for="desc">Bemerkungen:</label><input class="long" type="text" id="desc" name="desc" value="" /><br />', 5);
    $this->line('<label for="link">Link:</label><input class="long" type="text" id="link" name="link" value="" /><br />', 5);
    $this->line('</fieldset>', 4);
    $this->line('<div class="bar">', 4);
    $this->line('<span class="left"><input type="submit" name="submit" value="Abbrechen" /></span>', 5);
    $this->line('<span class="right"><input type="submit" name="submit" value="Hinzufügen" /></span>', 5);
    $this->line('</div>', 4);
    $this->line('</form>', 3);
  }
  
}

class EBEditEventPage extends EBEventPage {

  public function view(){
    $id = $_REQUEST['id'];
    if (isset($id)) $event = $this->events[$id];
    if (!($event instanceof EBEvent)) return $this->view = new EBErrorPage('Die angeforderte Veranstaltung scheint es nicht zu geben.', EBPage::ERROR);
    $this->title = 'Veranstaltung bearbeiten';
    $this->line('<form action="index.php" method="post">', 3);
    $this->line('<fieldset>', 4);
    $this->line('<input type="hidden" name="do" value="SaveEvent" />', 5);
    $this->line('<input type="hidden" name="id" value="'.$id.'" />', 5);
    $this->line('<label for="org">Veranstalter:</label><input type="text" id="org" name="org" value="'.htmlspecialchars($event->org).'" /><br />', 5);
    $this->date_input($event->date);
    $this->line('<label for="city">Ort:</label><input type="text" id="city" name="city" value="'.htmlspecialchars($event->city).'" /><br />', 5);
    $this->line('<label for="title">Veranstaltungstitel:</label><input type="text" id="title" name="title" value="'.htmlspecialchars($event->title).'" /><br />', 5);
    $this->line('<label for="desc">Bemerkungen:</label><input class="long" type="text" id="desc" name="desc" value="'.htmlspecialchars($event->desc).'" /><br />', 5);
    $this->line('<label for="link">Link:</label><input class="long" type="text" id="link" name="link" value="'.htmlspecialchars($event->link).'" /><br />', 5);
    $this->line('</fieldset>', 4);
    $this->line('<div class="bar">', 4);
    $this->line('<span class="left"><input type="submit" name="submit" value="Abbrechen" /></span>', 5);
    $this->line('<span class="right"><input type="submit" name="submit" value="Veranstaltung löschen" /> <input type="submit" name="submit" value="Ändern" /></span>', 5);
    $this->line('</div>', 4);
    $this->line('</form>', 3);
  }

}

class EBDuplicateEventPage extends EBEditEventPage {

  public function view(){
    $id = $_REQUEST['id'];
    if (isset($id)) $event = $this->events[$id];
    if (!($event instanceof EBEvent)) return $this->view = new EBErrorPage('Die angeforderte Veranstaltung scheint es nicht zu geben.', EBPage::ERROR);
    $this->title = 'Veranstaltung duplizieren';
    $this->line('<form action="index.php" method="post">', 3);
    $this->line('<fieldset>', 4);
    $this->line('<input type="hidden" name="do" value="SaveEvent" />', 5);
    $this->line('<label for="org">Veranstalter:</label><input type="text" id="org" name="org" value="'.htmlspecialchars($event->org).'" /><br />', 5);
    $this->date_input($event->date);
    $this->line('<label for="city">Ort:</label><input type="text" id="city" name="city" value="'.htmlspecialchars($event->city).'" /><br />', 5);
    $this->line('<label for="title">Veranstaltungstitel:</label><input type="text" id="title" name="title" value="'.htmlspecialchars($event->title).'" /><br />', 5);
    $this->line('<label for="desc">Bemerkungen:</label><input class="long" type="text" id="desc" name="desc" value="'.htmlspecialchars($event->desc).'" /><br />', 5);
    $this->line('<label for="link">Link:</label><input class="long" type="text" id="link" name="link" value="'.htmlspecialchars($event->link).'" /><br />', 5);
    $this->line('</fieldset>', 4);
    $this->line('<div class="bar">', 4);
    $this->line('<span class="left"><input type="submit" name="submit" value="Abbrechen" /></span>', 5);
    $this->line('<span class="right"><input type="submit" name="submit" value="Hinzufügen" /></span>', 5);
    $this->line('</div>', 4);
    $this->line('</form>', 3);
  }

}

class EBSaveEventPage extends EBEventPage {

  const NL = "\r\n";

  public function action(){
    $id    = (isset($_POST['id']) ? $_POST['id'] : md5($date.$title));
    $title = (isset($_POST['title']) ? $_POST['title'] : $this->events[$id]->title);
    $user  = EBSite::user();
    switch ($_POST['submit']){
      case 'Ändern':
      case 'Hinzufügen':
        $date  = strtotime($_POST['year'].'-'.$_POST['month'].'-'.$_POST['day']);
        if ($date == false) return $this->view = new EBEditEventPage('Das Datumsformat wurde nicht erkannt. Geben Sie das Datum bitte im Format YYYY-MM-DD ein!', EBPage::ERROR);
        $link  = $_POST['link'];
	    	if (!empty($link) && (substr($link, 0, 4) != 'http')) $link = 'http://'.$link; // assume HTTP protocol
	    	$draft = (isset($user) ? false : true);
        $this->events[$id] = new EBEvent($_POST['org'], $date, $title, $_POST['city'], $link, $_POST['desc'], $draft);
        if (($_POST['submit'] == 'Hinzufügen') && $draft) $this->notify($this->events[$id]);
        $msg = 'Veranstaltung «'.htmlspecialchars($title).'» wurde erfolgreich '.(($_POST['submit'] == 'Ändern') ? 'geändert' : 'hinzugefügt').'.';
        $this->events->sort();
        break;
      case 'Freischalten':
        $this->events[$id]->draft = false;
        $this->events->changed();
        $msg = 'Veranstaltung «'.htmlspecialchars($title).'» wurde freigeschaltet.';
        break;
      case 'Veranstaltung löschen':
      case 'Entfernen':
        unset($this->events[$id]);
        $msg = 'Veranstaltung «'.htmlspecialchars($title).'» wurde erfolgreich gelöscht.';
        break;
      default:
        return $this->view = new EBShowEventsPage();
    }
    $this->view = new EBShowEventsPage($msg, EBPage::SUCCESS);
  }
  
  private function notify($event){
    require_once EB_APP.'mail.php';
    $mail = new EBMail(new EBEmail('benachrichtigung@tg-agenda.ch'));
    $mail->to(new EBEmail('christina@transensyndikat.net'));
    $mail->subject('Neuer Veranstaltungshinweis wartet auf Freischaltung');
    $mail->append('Organisation: '.$event->title.self::NL);
    $mail->append('Datum:        '.$this->date($event->date).self::NL);
    $mail->append('Titel:        '.$event->title.self::NL);
    $mail->append('Stadt:        '.$event->city.self::NL);
    $mail->append('Link:         '.$event->link.self::NL);
    $mail->append('Beschreibung: '.$event->desc.self::NL);
    $mail->append(self::NL.'http://www.tg-agenda.ch/?do=LoginUser'.self::NL);
    $mail->send();
  }
  
  private function date($date){
    return date('j', $date).'. '.$this->monthname(date('m', $date)).date(' Y', $date);
  }

}

/**
 * Konkrete Klasse für Atom-Feed
 */
class EBShowEventsFeed extends EBFeed {

  protected $events;
  
  public function __construct(){
    $this->events = EBSite::events();
  }
  
  public function view(){
    $start = time() - 24 * 60 * 60;
    $end   = $start + (365 * 24 * 60 * 60); // Standard: zeige nächste 365 Tage
    echo 'Hello World';
    $this->eventlist($start, $end);
  }
  
  protected function monthname($month){
    switch ($month){
      case '01': return 'Januar';
      case '02': return 'Februar';
      case '03': return 'März';
      case '04': return 'April';
      case '05': return 'Mai';
      case '06': return 'Juni';
      case '07': return 'Juli';
      case '08': return 'August';
      case '09': return 'September';
      case '10': return 'Oktober';
      case '11': return 'November';
      case '12': return 'Dezember';
      default:   return '';
    }
  }

  private function eventlist($start, $end){
    $events = array();
    $allevents = $this->events;
    var_dump($allevents);
    foreach ($allevents as $event){
      if (($event->date < $start) || ($event->date > $end)) continue;
      $events[] = $event;
    }
    if (count($events) == 0){
      $this->no_event();
    } else {
      $keys = array_keys($events);
      foreach ($events as $event){
        $this->line('<entry>', 1);
        $this->event_data($event);
        $this->line('<entry>', 1);
      }
    }
  }
  
  private function no_event(){
    $this->line('<entry><title>Keine Einträge gefunden.</title></entry>', 1);
  }
  
  private function event_data($event){
    $this->line('<id>'.'http://www.tg-agenda.ch/'.md5($event->date.$event->title).'</id>', 2);
    $this->line('<title>'.$event->title.'</title>', 2);
    
    if ($event->link) $this->line('<link href="'.$event->link.'" />', 2);
    $this->line('<contributor>', 2);
    $this->line('<name>'.$this->organisations($event->org).'</name>', 3);
    // $this->line('<uri>'..'</uri>', 3);
    $this->line('</contributor>', 2);
    $this->line('<summary type="html">', 2);
    $this->line('<p>'.$this->date($event->date).'</p>', 3);
    $this->line('<p>'.htmlspecialchars($event->city).'</p>', 3);
    $this->line('<p>'.htmlspecialchars($event->desc).'</p>', 3);
    $this->line('</summary>', 2);
  }
  
  private function date($date){
    return date('j', $date).'. '.$this->monthname(date('m', $date)); // .date(' Y', $date);
  }

}
