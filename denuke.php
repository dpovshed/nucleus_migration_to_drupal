<?php

// main script v 1.11
// Importing Nucleus 3.41 to Drupal 6.14

$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

require("denuke_config.php");
require("denuke_drupal_helpers.php");
require("denuke_helpers.php");

// load config
require("denuke.cfg.php");

file_put_contents('errors.log', sprintf("\n*********************\n%s started\n", date('d, H:i:s ')), FILE_APPEND);

// validate config
$config = new Configuration($cfg);

if (!$config->validate()) die();

db_query('SET NAMES "utf8"');
db_query('SET CHARACTER SET "utf8"');


///////////////////////////////////////////////
// Nucleus core data storage
$nmembers    = array();
$nitems      = array();
$ncomments   = array();
$ncat        = array();
$ncat_reverted = array();
$nfailed     = array();

///////////////////////////////////////////////
// Drupal core data storage
$dusers        = array();
$dusers_revrted= array();
//$dnodes      = array();
//$ncomments   = array();
$dtax          = array();
$dtax_reverted = array();

///////////////////////////////////////////////
// Conversion dictionaries
$ncat_dtax     = array(); // nucleus category id => drupal term id
$nitem_dnode   = array(); // nucleus item id => drupal node id

// establish connection to source
$sql_src = new mysqli($config->data['nucleus_server'], $config->data['nucleus_user'], $config->data['nucleus_pass'], $config->data['nucleus_db']);if (mysqli_connect_errno()) { throw new Exception('Cannot connect to source: ' . mysqli_connect_error()); }
$nprefix = $config->data['nucleus_prefix'];

$sql_src->query('SET NAMES "utf8"');
$sql_src->query('SET CHARACTER SET "utf8"');

// get Drupal users
$result = db_query('SELECT uid, name FROM {users} WHERE uid>0 ORDER BY uid');
while ($u = db_fetch_object($result)) {
  //printf("%2d %s\n", $u->uid, $u->name);
  $dusers[$u->uid] = $u->name;
}
$dusers_reverted = array_flip($dusers);
printf("Step 1/5 - users. Drupal DB have %d users\n", count($dusers));

// Migrate users
$added_users = 0;
if ($result = $sql_src->query("SELECT * FROM " . $nprefix . "member ORDER BY mnumber")) {
  printf("Select returned %d rows.\n", $result->num_rows);
  while ($row = $result->fetch_assoc()) {
    $mid = $row["mnumber"];
    $mname = $row["mname"];
    printf("%2d %s ", $mid, $mname);
    $nmembers[$mid] = (object) array('name' => $mname, 'mid' => $mid);
    // compare with Drupal users
    if (isset($dusers_reverted[$mname])) {
      if ($dusers_reverted[$mname] == $mid) {
        printf("- user already exists with the same id\n");
        continue;
      }
    }
    db_query("DELETE FROM {users} WHERE name = '%s'", $mname);
    db_query("DELETE FROM {users} WHERE uid = %d", $mid);

    $new_user = /*(object)*/ array('uid' => $mid, 'name' => $mname, 'pass' => $row["mpassword"],
      'mail' => $row["memail"], 'init' => $row["memail"], 'status' => 1);
    $adding = user_save('', $new_user);
    if (!$adding) {
      var_dump($new_user);
      die("Fatal: cannot add a user " . $mname);
    }
    // patch the password to make it the same as in Nucleus
    db_query("UPDATE {users} SET pass = '%s' WHERE uid = %d", $row["mpassword"], $mid);
    printf("- ADDED Drupal user %s with user ID %d\n", $mname, $mid);
    $dusers_reverted[$mname] = $mid;
    ++$added_users;
  }

  /* free result set */
  $result->close();
}

printf("Added users: %d\n", $added_users);

if ($result = $sql_src->query("SELECT catid, cname FROM " . $nprefix . "category WHERE cblog = " . $config->data['nucleus_blog_id'])) {
  while ($row = $result->fetch_assoc()) {
    $cid = $row["catid"];
    $cname = $row["cname"];
    $ncat[$cid] = $cname;
    $ncat_reverted[$cname] = $cid;
  }
}
load_drupal_tax($config->data['taxonomy_cat'], $dtax, $dtax_reverted);
printf("Step 2/5 - Category to Taxonomy Vocabulary migration. Nucleus/Drupal entries: %d/%d\n", count($ncat), count($dtax));

$added_cats = 0;
foreach($ncat as $cid => $cname) {
  if (isset($dtax_reverted[$cname])) {
    continue;
  }
  $term_new = array('vid' => $config->data['taxonomy_cat'], 'name' => $cname);
  taxonomy_save_term($term_new);
  $tid = db_last_insert_id('term_data', 'tid');
  $added_cats++;
  $dtax[$tid] = $cname;
  $dtax_reverted[$cname] = $tid;
}
printf("Migration dictionary prepared. Added terms: %d\n", $added_cats);

foreach($ncat as $cid => $cname) {
  $ncat_dtax[$cid] = $dtax_reverted[$cname];
}
//var_dump($ncat_dtax);

if (!($result = $sql_src->query("SELECT * FROM " . $nprefix . "item WHERE iblog = " . $config->data['nucleus_blog_id']))) {
  die("Fatal: cannot get Nucleus posts.");
}
printf("Step 3/5 - posts migration. " . $result->num_rows . " posts will be added.\n");

if ($config->data['import_to'] == 'taxonomy') {
  // we will need vocabulary ID in the loop
  $importto_term = taxonomy_get_term($config->data['taxonomy_term_id']);
}
while ($post = $result->fetch_assoc()) {
  // combine teaser and body
  if (trim($post['imore']) != "") {
    $teaser = $post['ibody'];
    $body = $teaser . "<br /><br />" . $post['imore'];
  } else {
     $teaser = '';
     $body = $post['ibody'];
  }

  // make pictures manageable - convert media, if any
  $teaser = parse_nucleus_templates($teaser, $post['iauthor'], $config->data['drupal_dir_http']);
  $body   = parse_nucleus_templates($body, $post['iauthor'], $config->data['drupal_dir_http']);

  $time_created = strtotime($post['itime']);
  if ($time_created <=0 ) {
    // Thats means it is Draft in Nucleus, but Drupal prefer to have valid time there
    $time_created = time();
  }
  $new_node = array(
    'nid'     => NULL,
    'vid'     => NULL,
    'uid'     => $post['iauthor'],
    'name'    => $dusers[$post['iauthor']],
    'created' => $time_created,
    'changed' => $time_created,
    //'title'   => html_entity_decode($post['ititle'], ENT_QUOTES, "UTF-8"),
    'title'   => trim(strip_tags($post['ititle'])),
    'body'    => $body,
    'format'  => 2, // full html
    'comment' => $post['iclosed'] ? 1 : 2, // 1 stands for read-only comments, 2 for read-write
    'status'  => $post['idraft'] ? 0 : 1,
    'promote' => 0,
    'sticky'  => 0,
    'taxonomy'=> array(),
  );
  // retrieve Drupal taxonomy term for Nucleus category
  $term_id = $ncat_dtax[$post['icat']];
  $tagger = array();
  $tagger[$config->data['taxonomy_cat']] = $dtax[$term_id];

  if ($config->data['import_to'] == 'content') {
    $new_node['type'] = $config->data['contenttype_name'];
  }
  else { // taxonomy tagging
    $new_node['type'] = 'page';
    $tagger[$importto_term->vid] = $importto_term->name;
  }
  $new_node['taxonomy']['tags'] = $tagger;

  $new_node_obj = (object) $new_node;
  node_save($new_node_obj);
  if (!$new_node) {
    die("Fatal: cannot add a node");
  }
  $new_id = $new_node_obj->nid;
  if (!$new_id) {
    printf( 'Error: cannot process Nucleus post #' . $post['inumber'] . "\n");
    file_put_contents('errors.log', 'Cannot process Nucleus post #' . $post['inumber'] . "\n", FILE_APPEND);
    $nfailed[$post['inumber']] = $post['inumber'];
    continue;
  }
  $nitem_dnode[$post['inumber']] = $new_id;
  db_query("UPDATE {node} SET changed = '%d' WHERE nid = %d", $time_created, $new_id);
  db_query("UPDATE {node_revisions} SET timestamp = '%d' WHERE nid = %d", $time_created, $new_id);
  printf("Nucleus Item %d imported into Drupal as node %d\n", $post['inumber'], $new_id);
}

////////////////////////////////////////////////////////////////////////////////////
if (!($result = $sql_src->query("SELECT * FROM " . $nprefix . "comment WHERE cblog = " . $config->data['nucleus_blog_id']))) {
  die("Fatal: cannot get Nucleus comments.");
}
printf("Step 4/5 - comments migration. " . $result->num_rows . " comments will be added.\n");

// we're acting as anonymous user, so lets temporarily grant premissions to add cooments without any checks
//
$saved_anon_result = db_result(db_query("SELECT perm FROM {permission} WHERE rid = %d", DRUPAL_ANONYMOUS_RID));
if ($saved_anon_result) {
  $perm_array = array_merge(explode(', ', $saved_anon_result), array('post comments', 'post comments without approval'));
  db_query("UPDATE {permission} SET perm = '%s' WHERE rid = %d", implode(', ', $perm_array), DRUPAL_ANONYMOUS_RID);
}
else {
  $perm_array = array('post comments', 'post comments without approval');
  db_query("INSERT INTO {permission} (perm, rid) VALUES('%s', %d)", implode(', ', $perm_array), DRUPAL_ANONYMOUS_RID);
}

// just to reload permissions
user_access('bla-bla-bla perm', NULL, TRUE);

while ($post = $result->fetch_assoc()) {
  if (isset($nfailed[$post['citem']])) {
    file_put_contents('errors.log',
      sprintf("Skip comment #%d belonging to post #%d\n", $post['cnumber'], $post['citem']), FILE_APPEND);
    continue;
  }

  $time_created = strtotime($post['ctime']);
  // Nucleus does not have comment title at all so lets create one
  // otherwise comments looks ugly
  $subject = truncate_utf8(drupal_html_to_text($post['cbody']), 45, TRUE, TRUE);
  $new_comment = array(
    'cid'     => NULL,
    'pid'     => 0, // Nucleus does not supports comment hierarchy
    'nid'     => $nitem_dnode[$post['citem']],
    'uid'     => $post['cmember'],
    'subject' => $subject,
    'comment' => $post['cbody'],
    'name'    => $post['cuser'],
    'mail'    => $post['cmail'],
    'homepage'=> $post['cemail'],
    'hostname'=> $post['cip'], // $post['chost'] is also an option but in fact Drupal 6 stores IP there
    'status'  => 0,
    'format'  => 2, // full html
  );
  $new_id = comment_save($new_comment);
  if (!$new_id) {
    printf("Error migrating Nucleus comment %d\n", $post['cnumber']);
    continue;
  }
  db_query("UPDATE {comments} SET timestamp = '%d' WHERE cid = %d", $time_created, $new_id);
  printf("Nucleus comment %d imported into Drupal with ID %d\n", $post['cnumber'], $new_id);
}
// restore original anonymous roles
if ($saved_anon_result) {
  db_query("UPDATE {permission} SET perm = '%s' WHERE rid = %d", $saved_anon_result, DRUPAL_ANONYMOUS_RID);
}
else {
  db_query("DELETE FROM {permission} WHERE rid = %d", DRUPAL_ANONYMOUS_RID);
}

$sql_src->close();

if ($config->isFiles()) {
  try {
    printf("\nStep 5/5 - copying files.");
    recurse_copy($config->data['nucleus_base_dir'], $config->data['drupal_base_dir']);
  }
  catch(Exception $e) {
    printf('Media data copying error: ' . $e->getMessage());
    // if something goes wrong with files - do not need to modify SQL at all
    die('Processing terminated.');
  }
  printf("\nCopying files done.\n");
}
else {
  printf("\nStep 5/5 - copying files - skipped.\n");
}

$bad_nodes = check_nodes();
if (count($bad_nodes)) {
  file_put_contents('errors.log', 'Malformed nodes found, erasing: ' . implode(', ', $bad_nodes) . "\n", FILE_APPEND);
  printf("Clean up malformed nodes...\n");
  erase_nodes($bad_nodes);
}

exit();

?>