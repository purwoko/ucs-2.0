<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<title><?php echo $page_title; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Pragma" content="no-cache" />
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, post-check=0, pre-check=0" />
<meta http-equiv="Expires" content="Sat, 26 Jul 1997 05:00:00 GMT" />
<link rel="icon" href="../webicon.ico" type="image/x-icon" />
<link rel="shortcut icon" href="../webicon.ico" type="image/x-icon" />
<link href="../template/core.style.css" rel="stylesheet" type="text/css" />
<link href="<?php echo ADMIN_WEB_ROOT_DIR.'admin_themes/default/style.css'; ?>" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/updater.js"></script>
<script type="text/javascript" src="../js/gui.js"></script>
<script type="text/javascript" src="../js/form.js"></script>
<script type="text/javascript" src="../js/calendar.js"></script>
<script type="text/javascript" src="../js/tiny_mce/tiny_mce.js"></script>
<!-- new them for Meranti by Eddy Subratha -->
</head>
<body>
<!-- main menu -->
<div id="mainMenu"><?php echo $main_menu; ?></div>
<!-- main menu end -->

<!-- header-->
<div id="header">
	<div id="headerImage">&nbsp;</div>
	<div id="libraryName"><?php echo $sysconf['server']['name']; ?></div>
	<div id="librarySubName"><?php echo $sysconf['server']['subname']; ?></div>
</div>
<!-- header end-->

<table id="main" cellpadding="0" cellspacing="0">
<tr>
    <td id="sidepan">
	    <?php echo $sub_menu; ?>
    </td>
    <td>
    	<a name="top"></a>
	    <div class="loader"><?php echo $info; ?></div>
	    <div id="mainContent">
	    <?php echo $main_content; ?>
	    </div>
    </td>
</tr>
</table>

<!-- license info -->
<div id="footer"><?php echo $sysconf['page_footer']; ?></div>
<!-- license info end -->

<!-- fake submit iframe for search form, DONT REMOVE THIS! -->
<iframe name="blindSubmit" style="visibility: hidden; width: 0; height: 0;"></iframe>
<!-- <iframe name="blindSubmit" style="visibility: visible; width: 100%; height: 300px;"></iframe> -->
<!-- fake submit iframe -->

</body>
</html>
