<?php

class EBOrganisations extends EBArray {

  const FILE = 'organisations.txt';
  
  public function linklist(){
    if (!$this->loaded) $this->load();
    echo '<h2>Transgender-Organisationen</h2>'.parent::NL.'<ul id="links">'.parent::NL;
    foreach ($this->array as $org){
      echo '<li>'.$org->logo.'</li>'.parent::NL;
    }
    echo '</ul>'.parent::NL;
  }
  
  protected function load(){
    $file = EB_DATA.self::FILE;
    if (!file_exists($file)) return;
    $rows = file($file);
    foreach ($rows as $row){
      if (($row = trim($row)) == '') continue;
      list($name, $link) = explode(parent::TAB, $row);
      $org = new EBOrganisation($name, $link);
      $this->array[$org->name] = $org;
    }
    $this->loaded = true;
  }
  
  protected function save(){
    $data = implode(parent::NL, $this->array);
    file_put_contents(EB_DATA.self::FILE, $data);
    $this->changed = false;
  }

}

class EBOrganisation {

  private $name, $link, $logo;
  
  public function __construct($name, $link){
    $this->name = $name;
    $this->link = $link;
  }
  
  public function __get($key){
    switch ($key){
      case 'name':
      case 'link': return $this->$key;
      case 'logo': return $this->logo();
      default:     return NULL;
    }
  }
  
  public function __toString(){
    return $this->name.EBArray::TAB.$this->link;
  }
  
  private function logo(){
    $filetypes = array('gif', 'png', 'jpg', 'jpeg');
    $orgname = str_replace(array(' ', '*', '+'), '_', strtolower($this->name));
    foreach ($filetypes as $filetype){
      if (@file_exists(EB_DATA.'logos/'.$orgname.'.'.$filetype)){
        if ($this->link) $html = '<a href="'.htmlspecialchars($this->link).'" target="_blank">';
        $html .= '<img src="data/logos/'.$orgname.'.'.$filetype.'" width="120" height="40" title="'.htmlspecialchars($this->name).'" />';
        if ($this->link) $html .= '</a>';
        return $html;
      }
    }
    if ($this->link) return '<a href="'.htmlspecialchars($this->link).'">'.htmlspecialchars($this->name).'</a>';
    else return htmlspecialchars($this->name);
  }

}
