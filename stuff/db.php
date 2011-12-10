<?php

class DB {
  var $link;

  function DB() {
  }

  function open($server, $base, $user, $password) {
    $this->link = mysql_connect($server, $user, $password);
    if (!$this->link) { throw new Exception(mysql_error()); }
    if (!mysql_select_db($base, $this->link)) { throw new Exception(mysql_error()); }
  }

  function close() {

  }

};

?>