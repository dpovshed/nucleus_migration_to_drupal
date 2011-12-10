<?php

function recurse_copy($src, $dst) {
  $dir = opendir($src);
  @mkdir($dst);
  while(false !== ( $file = readdir($dir)) ) {
    if (($file == '.' ) || ( $file == '..' )) {
      continue;
    }
    if (is_dir($src . '/' . $file)) {
      recurse_copy($src . '/' . $file, $dst . '/' . $file);
    }
    else {
      copy($src . '/' . $file, $dst . '/' . $file);
      printf('.');
    }
  }
  closedir($dir);
}


function parse_nucleus_templates($data, $uid, $path) {
  $path_user     = $path . "/" . $uid . "/";

  // if file with dirname - 'everyone' or user number
  $data = preg_replace("/<\%image\((.+\/.+)\|(\d+)\|(\d+)\|(.*)\){0,1}\%>/",
    "<img src=\"" . $path . "/$1\" width=\"$2\" height=\"$3\" alt=\"$4\" title=\"$4\" />", $data);
  // in current user's private dir
  $data = preg_replace("/<\%image\((.+)\|(\d+)\|(\d+)\|(.*)\){0,1}\%>/",
    "<img src=\"" . $path_user . "$1\" width=\"$2\" height=\"$3\" alt=\"$4\" title=\"$4\" />", $data);

  $data = preg_replace("/<\%popup\((.+\/.+)\|(\d+)\|(\d+)\|(.*)\)\%>/",
    "<a href=\"" . $path . "/$1\" target=\"_blank\" title=\"Show picture in a new window\">$4</a>", $data);
  $data = preg_replace("/<\%popup\((.+)\|(\d+)\|(\d+)\|(.*)\)\%>/",
    "<a href=\"" . $path_user . "$1\" target=\"_blank\" title=\"Show picture in a new window\">$4</a>", $data);

  return $data;
}

?>
