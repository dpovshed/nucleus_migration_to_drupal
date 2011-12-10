<?php

// Dumping essential info about Nucleus DB
//

require("denuke_config.php");
// main script

// load config
require("denuke.cfg.php");

// validate config
$config = new Configuration($cfg);

//if (!$config->validate()) die();

$sql_src = new mysqli($cfg['nucleus_server'], $cfg['nucleus_user'], $cfg['nucleus_pass'], $cfg['nucleus_db']);
if (mysqli_connect_errno()) { throw new Exception('Cannot connect to source: ' . mysqli_connect_error()); }
$nprefix = $config->data['nucleus_prefix'];

if ($result = $sql_src->query("SELECT * FROM " . $nprefix . "member ORDER BY mnumber")) {
  printf("Number of users: %d.\n", $result->num_rows);
  while ($row = $result->fetch_assoc()) {
    printf ("%2d %s\n", $row["mnumber"], $row["mname"]);
  }
  $result->close();
}

if ($result = $sql_src->query("SELECT * FROM " . $nprefix . "blog ORDER BY bnumber")) {
  printf("\nNumber of blogs: %d.\n", $result->num_rows);
  while ($row = $result->fetch_assoc()) {
    printf ("%2d %s\n", $row["bnumber"], $row["bname"]);
  }
  $result->close();
}

if ($result = $sql_src->query("SELECT * FROM " . $nprefix . "blog ORDER BY bnumber")) {
  printf("\nNumber of blogs: %d.\n", $result->num_rows);
  while ($row = $result->fetch_assoc()) {
    printf ("%2d %s\n", $row["bnumber"], $row["bname"]);
    if ($r2 = $sql_src->query("SELECT * FROM " . $nprefix . "category WHERE cblog=" . $row["bnumber"]. " ORDER BY catid")) {
      printf("    categories:");
      while ($row2 = $r2->fetch_assoc()) {
        printf (" (%d, %s)", $row2["catid"], $row2["cname"]);
      }
      printf("\n");
    }
  }
  $result->close();
}

$result = $sql_src->query("SELECT COUNT(*) AS items FROM " . $nprefix . "item");
$row = $result->fetch_assoc();
$num_items = $row["items"];
printf("\nTotal %d posts\n", $num_items);

$result = $sql_src->query("SELECT COUNT(*) AS comments FROM " . $nprefix . "comment");
$row = $result->fetch_assoc();
$num_comments = $row["comments"];
$result = $sql_src->query("SELECT COUNT(distinct(citem)) AS commented FROM " . $nprefix . "comment");
$row = $result->fetch_assoc();
$num_commented = $row["commented"];
$result = $sql_src->query("SELECT COUNT(distinct(cmember)) AS commentators FROM " . $nprefix . "comment WHERE cmember>0");
$row = $result->fetch_assoc();
$num_commentators = $row["commentators"];
printf("\nTotal %d comments, commented %d posts by %d registered users.\n"
  . "Note - having comments and writing comments are optional.\n",
  $num_comments, $num_commented, $num_commentators);


$sql_src->close();
exit();

?>