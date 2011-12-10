<?php

class Configuration {
  var $data;

  function Configuration($cfg) {
    if (!isset($cfg['nucleus_prefix'])) {
      $cfg['nucleus_prefix'] = '';
    }
    $this->data = $cfg;
  }

  // accessors
  function isFiles()   { return $this->data['process_files']; }

  function validate() {
    try {
      if (($this->data['import_to']=='content') && !isset($this->data['contenttype_name'])) {
        throw new Exception('Please provide destination content type');
      }
      // other logic checks
      // ....

      // check DB connection. Both DB must be available even if we not committing result
      $sql_src = new mysqli($this->data['nucleus_server'], $this->data['nucleus_user'], $this->data['nucleus_pass'], $this->data['nucleus_db']);
      if (mysqli_connect_errno()) { throw new Exception('Cannot connect to source: ' . mysqli_connect_error()); }

      if (!module_exists('taxonomy')) { throw new Exception('Taxonomy support is not enabled'); }

      if ($this->data['import_to'] == 'content') {
        $types = node_get_types();
        $passed = isset($types[$this->data['contenttype_name']]);
        if (!$passed) { throw new Exception('Drupal - not exist selected content type: ' . $this->data['contenttype_name']); }
      }
      else {
        // taxonomy is the default
        $this->data['import_to'] = 'taxonomy';
      }

      $vocabularies = taxonomy_get_vocabularies();
      if ($this->data['import_to']=='taxonomy') {
        $term = taxonomy_get_term($this->data['taxonomy_term_id']);
        if (!$term) { throw new Exception('Drupal - taxonomy term for tagging import not exist, ID: ' . $this->data['taxonomy_term_id']); }
      }

      $passed = isset($vocabularies[$this->data['taxonomy_cat']]);
      if (!$passed) { throw new Exception('Drupal - Vocabulary for tagging not exist, ID: ' . $this->data['taxonomy_cat']); }

      if ($result = $sql_src->query("SELECT * FROM " . $this->data['nucleus_prefix'] . "blog WHERE bnumber = "
           . $this->data['nucleus_blog_id'])) {
        if (!$result->fetch_assoc()) {
          throw new Exception('Nucleus - blog with this ID doen not exists: ' . $this->data['nucleus_blog_id']);
        }
        $result->close();
      }

      if ($this->data['process_files']) {
        if (!is_dir($this->data['nucleus_base_dir'])) {
          throw new Exception('Nucleus - media directory inaccessible: ' . $this->data['nucleus_base_dir']);
        }
        if (!is_dir($this->data['drupal_base_dir'])) {
          throw new Exception('Drupal - media directory inaccessible: ' . $this->data['drupal_base_dir']);
        }
      }
    }
    catch(Exception $e) {
      printf('Configuration error: ' . $e->getMessage());
      return FALSE;
    }
    $sql_src->close();
    return TRUE;
  }
};


?>