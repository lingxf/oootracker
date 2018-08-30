<?php
include 'menu_css.php';
$title = 'Vocation/OT/OOO Tracking';
print("<title>$title</title>");
$menu = array(
'Home'       =>'home.php',
'Vocation'  =>'ooo.php?ooo_view=vocation',
'OT'   =>'ooo.php?ooo_view=ot',
'OOO'   =>'ooo.php?ooo_view=ooo',
);

$menu_trans = array(
);

$action = get_url_var('action', 'ui');
include 'menu_show.php';
?>
