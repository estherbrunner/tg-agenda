<?php

class EBUsers extends EBArray {

  const FILE = 'users.txt';
  
  private $loggedin;
  
  public function __get($key){
    switch ($key){
      case 'loggedin':
        if (!$this->loaded) $this->load();
        return $this->loggedin;
      default:
        return NULL;
    }
  }
  
  protected function load(){
    $file = EB_DATA.self::FILE;
    if (!file_exists($file)) return;
    $rows = file($file);
    foreach ($rows as $row){
      if (($row = trim($row)) == '') continue;
      list($id, $pass, $name, $mail) = explode(parent::TAB, $row);
      $this->array[$id] = new EBUser($id, $pass, $name, $mail);
      if (($id == $_POST['user']) || ($id == $_COOKIE['user'])){
        if (($pass === md5($_POST['pass'])) || ($pass === $_COOKIE['pass'])) $this->loggedin = $this->array[$id];
      }
    }
    $this->loaded = true;
  }
  
  protected function save(){
    $data = implode(parent::NL, $this->array);
    file_put_contents(EB_DATA.self::FILE, $data);
    $this->changed = false;
  }
}

class EBUser {

  private $id, $pass, $name, $mail;
  
  public function __construct($id, $pass, $name, $mail){
    $this->id   = $id;
    $this->pass = $pass;
    $this->name = $name;
    $this->mail = $mail;
  }
  
  public function __get($key){
    switch ($key){
      case 'id':
      case 'pass':
      case 'name':
      case 'mail':  return $this->$key;
      default:      return NULL;
    }
  }
  
  public function __toString(){
    return implode(EBArray::TAB, array($this->id, $this->pass, $this->name, $this->mail));
  }

}

abstract class EBUserPage extends EBPage {

  protected $users, $user;
  
  public function __construct($msg = NULL, $code = NULL){
    $this->users = EBSite::users();
    $this->user  = EBSite::user();
    parent::__construct($msg, $code);
  }
  
  protected function logout(){
    setcookie('user', '', 1, '/', $_SERVER['SERVER_NAME']);
    setcookie('pass', '', 1, '/', $_SERVER['SERVER_NAME']);
    unset($_REQUEST['user']);
    unset($_REQUEST['pass']);
    unset($_COOKIE['user']);
    unset($_COOKIE['pass']);
  }

}

class EBAddUserPage extends EBUserPage {
  
  public function view(){
    if (!isset($this->user)) return $this->view = new EBErrorPage('Sie haben keine Berechtigung, um neue Benutzer anzulegen.', parent::ERROR);
    // $this->logout();
    $this->title = 'Neuen Benutzer registrieren';
    $this->line('<form action="index.php" method="post">', 3);
    $this->line('<fieldset>', 4);
    $this->line('<input type="hidden" name="do" value="SaveUser" />', 5);
    $this->line('<label for="user">Benutzername:</label> <input type="text" id="user" name="user" value="" /><br />', 5);
    $this->line('<label for="pass">Passwort:</label> <input type="password" id="pass" name="pass" value="" /><br />', 5);
    $this->line('<label for="pass2">Passwort bestätigen:</label> <input type="password" id="pass2" name="pass2" value="" /><br />', 5);
    $this->line('<label for="name">Voller Name:</label> <input type="text" id="name" name="name" value="" /><br />', 5);
    $this->line('<label for="mail">E-Mail-Adresse:</label> <input type="text" id="mail" name="mail" value="" /><br />', 5);
    $this->line('</fieldset>', 4);
    $this->line('<div class="bar">', 4);
    $this->line('<span class="right"><input type="submit" name="submit" value="Registrieren" /></span>', 5);
    $this->line('</div>', 4);
    $this->line('</form>', 3);
  }
  
}

class EBEditUserPage extends EBUserPage {
  
  public function view(){
    if (!isset($this->user)) return $this->view = new EBLoginUserPage('Sie müssen sich erst einloggen, um Ihr Benutzerprofil zu ändern.', parent::ERROR);
    $this->title = 'Benutzerprofil ändern';
    $this->line('<form action="index.php" method="post">', 3);
    $this->line('<fieldset>', 4);
    $this->line('<input type="hidden" name="do" value="SaveUser" />', 5);
    $this->line('<input type="hidden" name="user" value="'.$this->user->id.'" />', 5);
    $this->line('<span class="label">Benutzername:</span> <span class="field">'.$this->user->id.'</span><br />', 5);
    $this->line('<label for="pass">Passwort:</label> <input type="password" id="pass" name="pass" value="" /><br />', 5);
    $this->line('<label for="pass2">Passwort bestätigen:</label> <input type="password" id="pass2" name="pass2" value="" /><br />', 5);
    $this->line('<label for="name">Voller Name:</label> <input type="text" id="name" name="name" value="'.$this->user->name.'" /><br />', 5);
    $this->line('<label for="mail">E-Mail-Adresse:</label> <input type="text" id="mail" name="mail" value="'.$this->user->mail.'" /><br />', 5);
    $this->line('</fieldset>', 4);
    $this->line('<div class="bar">', 4);
    $this->line('<span class="left"><input type="submit" name="submit" value="Abbrechen" /></span>', 5);
    $this->line('<span class="right"><input type="submit" name="submit" value="Benutzer löschen" /> <input type="submit" name="submit" value="Speichern" /></span>', 5);
    $this->line('</div>', 4);
    $this->line('</form>', 3);
  }
  
}

class EBSaveUserPage extends EBUserPage {

  public function action(){
    $id = $_POST['user'];
    switch ($_REQUEST['submit']){
      case 'Registrieren':
        if (!$this->useravailable($id) || !$this->passmatch()) return $this->view;
        $this->users[$id] = new EBUser($id, md5($_POST['pass']), $_POST['name'], $_POST['mail']);
        $msg = 'Benutzer «'.htmlspecialchars($id).'» wurde erfolgreich hinzugefügt.';
        break;
      case 'Benutzer löschen':
        unset($this->users[$id]);
        $msg = 'Benutzer «'.htmlspecialchars($id).'» wurde erfolgreich gelöscht.';
        break;
      case 'Speichern':
        if (!$this->passmatch()) return $this->view;
        $pass = (($_POST['pass'] == '') ? $this->users[$id]->pass : md5($_POST['pass']));
        $this->users[$id] = new EBUser($id, $pass, $_POST['name'], $_POST['mail']);
        $msg = 'Benutzerprofil von «'.htmlspecialchars($id).'» wurde erfolgreich geändert.';
        break;
      default:
        return $this->view = new EBShowEventsPage();
    }
    $this->view = new EBShowEventsPage($msg, EBPage::SUCCESS);
  }
  
  private function useravailable($id){
    foreach ($this->users as $user){
      if ($user->id == $id){
        $this->view = new EBAddUserPage('Der Benutzername «'.$id.'» wird bereits verwendet. Wählen Sie einen anderen!', EBPage::ERROR);
        return false;
      }
    }
    return true;
  }
  
  private function passmatch(){
    if ($_POST['pass'] === $_POST['pass2']) return true;
    $this->view = new EBEditUserPage('Die Passwörter stimmen nicht überein. Geben Sie Ihr gewünschtes Passwort zwei mal ein!', EBPage::ERROR);
    return false;
  }

}

class EBLoginUserPage extends EBUserPage {

  public function view(){
    $this->title = 'Login';
    $this->line('<form action="index.php" method="post">', 3);
    $this->line('<fieldset>', 4);
    $this->line('<input type="hidden" name="do" value="ShowEvents" />', 5);
    $this->line('<label for="user">Benutzername:</label> <input type="text" id="user" name="user" value="" /><br />', 5);
    $this->line('<label for="pass">Passwort:</label> <input type="password" id="pass" name="pass" value="" /><br />', 5);
    $this->line('</fieldset>', 4);
    $this->line('<div class="bar">', 4);
    $this->line('<span class="right"><input type="submit" name="submit" value="Login" /></span>', 5);
    $this->line('</div>', 4);
    $this->line('</form>', 3);
  }

}

class EBLogoutUserPage extends EBUserPage {
  
  public function action(){
    $this->logout();
    $this->view = new EBShowEventsPage('Sie haben sich abgemeldet.', EBPage::SUCCESS);
  }
  
}
