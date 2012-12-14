 <?php
 // Test - Test
 class EBSite {

  private static $users, $events, $organisations;

  private $title;
  
  public function __construct($title){
    $this->title  = $title;
  }
  
  public function __get($key){
    switch ($key){
      case 'title':  return htmlspecialchars($this->title);
      default:       return '';
    }
  }
  
  public function page($action = NULL){
    if (!isset($action)) $action = (isset($_REQUEST['do']) ? $_REQUEST['do'] : 'ShowEvents');
    $class = 'EB'.$action.'Page';
    if (class_exists($class)) return new $class();
    else return new EBErrorPage('Der Befehl «'.$action.'» ist nicht bekannt.', EBPage::ERROR);
  }
  
  public static function user(){
    if ($_REQUEST['do'] == 'LogoutUser') return NULL;
    if (!is_object(self::$users)) self::$users = new EBUsers();
    return self::$users->loggedin;
  }
  
  public static function users(){
    if (!is_object(self::$users)) self::$users = new EBUsers();
    return self::$users;
  }
  
  public static function events(){
    if (!is_object(self::$events)) self::$events = new EBEvents();
    return self::$events;
  }
  
  public static function organisation($name){
    if (!is_object(self::$organisations)) self::$organisations = new EBOrganisations();
    return self::$organisations[$name];
  }
  
  public static function organisations(){
    if (!is_object(self::$organisations)) self::$organisations = new EBOrganisations();
    return self::$organisations;
  }

}

abstract class EBPage {

  const NL = "\n", INDENT = '  ', ERROR = -2, WARNING = -1, INFO = 0, SUCCESS = 1;

  protected $title, $header = self::NL, $content = '', $footer = self::NL, $msg, $code, $view;
  
  public function __construct($msg = NULL, $code = NULL){
    $this->msg  = $msg;
    $this->code = $code;
    $this->login();
    $this->action();
    if (!($this->view instanceof self)) $this->view();
  }
  
  public function __get($key){
    switch ($key){
      case 'title':
        if ($this->view instanceof self) return $this->view->title;
        return htmlspecialchars($this->title);
      case 'message':
        if ($this->view instanceof self) return $this->view->message;
        return $this->message();
      case 'header':
      case 'content': 
      case 'footer':
        if ($this->view instanceof self) return $this->view->$key;
        return $this->$key;
      default:
        return '';
    }
  }
  
  public function action(){}
  public function view(){}
  
  protected function line($content, $indent = 3){
    $this->content .= str_repeat(self::INDENT, $indent).$content.self::NL;
  }
  
  private function message(){
    if (!isset($this->msg)) return self::NL;
    switch ($this->code){
      case self::ERROR:
        $class = 'error message';
        break;
      case self::WARNING:
        $class = 'warning message';
        break;
      case self::INFO:
        $class = 'info message';
        break;
      case self::SUCCESS:
        $class = 'success message';
        break;
      default:
        $class = 'message';
    }
    return '<p class="'.$class.'">'.htmlspecialchars($this->msg).'</p>'.self::NL;
  }
  
  private function login(){
    $user = EBSite::user();
    if (isset($_POST['user']) && isset($_POST['pass'])){
      if (!($user instanceof EBUser) && !isset($this->view)){
        unset($_POST['user']);
        unset($_POST['pass']);
        return $this->view = new EBLoginUserPage('Die Anmeldung schlug fehl. Bitte überprüfen Sie Benutzername und Passwort!', self::ERROR);
      }
      setcookie('user', $_POST['user'], 0, '/', $_SERVER['SERVER_NAME']);
      setcookie('pass', md5($_POST['pass']), 0, '/', $_SERVER['SERVER_NAME']);
    }
    if ($user instanceof EBUser) $this->header = '<p class="user">Eingeloggt als <a href="?do=EditUser" class="profile">'.$user->name.'</a><br /><a href="?do=LogoutUser" class="logout">Logout</a></p>'.self::NL;
  }

}

class EBErrorPage extends EBPage {

  protected $title = 'Ein Fehler ist aufgetreten';

}

class EBDisclaimerPage extends EBPage {

  protected $title = 'Disclaimer';
  
  public function view(){
    $this->line(file_get_contents(EB_DATA.'disclaimer.html'));
  }

}

/**
 * Abstrakte Klasse für Atom-Feeds: Feeds in event.php müssen diese Klasse erweitern (extends)
 */
abstract class EBFeed {

  const NL = "\n", INDENT = '  ';
  
  protected $title, $content = '', $view;
  
  public function __construct(){
    if (!($this->view instanceof self)) $this->view();
  }
  
  public function __get($key){
    switch ($key){
      case 'title':
        if ($this->view instanceof self) return $this->view->title;
        return htmlspecialchars($this->title);
      case 'content': 
        // if ($this->view instanceof self) return $this->view->$key;
        return $this->$key;
      default:
        return '';
    }
  }

  public function view(){}
  
  protected function line($content, $indent = 3){
    $this->content .= str_repeat(self::INDENT, $indent).$content.self::NL;
  }
  
}

abstract class EBArray implements ArrayAccess, IteratorAggregate, Countable {

  const TAB = "\t", NL = "\n";

  protected $array = array(), $loaded = false, $changed = false;
  
  public function __destruct(){
    if ($this->changed) $this->save();
  }
  
  public function offsetExists($key){
    if (!$this->loaded) $this->load();
    return array_key_exists($key, $this->array);
  }
  
  public function offsetGet($key){
    if (!$this->loaded) $this->load();
    return $this->array[$key];
  }
  
  public function offsetSet($key, $value){
    if (!$this->loaded) $this->load();
    if (!$key) $this->array[] = $value;
    else $this->array[$key] = $value;
    $this->changed = true;
  }
  
  public function offsetUnset($key){
    if (!$this->loaded) $this->load();
    unset($this->array[$key]);
    $this->changed = true;
  }
  
  public function count(){
    if (!$this->loaded) $this->load();
    return count($this->array);
  }
  
  public function getIterator(){
    if (!$this->loaded) $this->load();
    return new ArrayIterator($this->array);
  }
  
  abstract protected function load();
  abstract protected function save();

}
