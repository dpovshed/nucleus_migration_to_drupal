<?php

/**********************************************************************
 *
 * General processing settings
 *
 * process_files - shall script process files as well
 *   default is FALSE because manual copying is more manageable.
 *   In most cases moving data or establishing a filesystem link
 *   shall be enough.
 *
 *********************************************************************/
$cfg['process_files']    = FALSE;

/**********************************************************************
 * DB settings
 *********************************************************************/
$cfg['nucleus_server']   = '127.0.0.1';
$cfg['nucleus_db']       = 'site_nuc';
$cfg['nucleus_user']     = 'root';
$cfg['nucleus_pass']     = 'root-pass';
$cfg['nucleus_prefix']   = 'nucleus_';

// please note - you do not need to enter a Drupal DB info,
// because it is already exists in Drupal configuration file.


/**********************************************************************
 * Migration tuning
 *********************************************************************/
// Nucleus DB can have several blogs, here please set a blog number
$cfg['nucleus_blog_id']      = 1;

// data can be imported into specific content type or
// into content type 'page' marked with taxonomy
// Please select here 'content' or 'taxonomy' (default).
// If 'taxonomy' is selected importing will be made into
// the 'page' content type
//$cfg['import_to']        = 'taxonomy';
$cfg['import_to']        = 'content';

// depending of the parameter above one of the following will be used
// please note that taxonomy term or content-type shall exists
//$cfg['taxonomy_term_id'] = 32;
$cfg['contenttype_name'] = '002blog_1';

// Nucleus categories - tagging mechanism - will became another
// Drupal taxonomy. Please provide vocabulary ID here
$cfg['taxonomy_cat']     = 1;

/**********************************************************************
 *
 * Files section
 *
 * process_files - processing files can be skipped
 *   and made by OS command (recommended). otherwise
 *   please set directories correctly, and ensure
 *   that destination have enough space
 *
 * nucleus_base_dir
 * drupal_base_dir - data directories in OS-depending format.
 *   A must if files processing is enabled, ignored otherwise.
 *
 * drupal_dir_http - path to files from server root for HTTP requests.
 *   In most cases no need to tune default '/sites/default/files'.
 *
 *********************************************************************/
$cfg['nucleus_base_dir'] = '/Apache/htdocs/siteblog/media';
$cfg['drupal_base_dir']  = '/Apache/htdocs/sitedrupal/sites/default/files';
$cfg['drupal_dir_http']  = '/sitedrupal/sites/default/files';

?>
