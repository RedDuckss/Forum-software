<?php

// error_reporting(E_ALL);
// ini_set("display_errors", "on");
// ini_set("display_startup_errors", "on");

// if ($_SERVER["HTTP_USER_AGENT"] == "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 OPR/45.0.2552.898" || $_SERVER["HTTP_USER_AGENT"] == "Mozilla/5.0 (iPhone; CPU iPhone OS 10_3_2 like Mac OS X) AppleWebKit/603.1.30 (KHTML, like Gecko) CriOS/59.0.3071.102 Mobile/14F89 Safari/602.1" || $_SERVER["REMOTE_ADDR"] == "109.152.71.106")
//	die(file_get_contents("youareanidiot.html"));

if (isset($_COOKIE["ninjabanned"]))
	die(file_get_contents("youareanidiot.html"));

//include(__DIR__."/toast.php");

$starttime = microtime(true);
define('BLARG', 1);

/*if($_SERVER["REMOTE_ADDR"] != "127.0.0.1" || $_SERVER["REMOTE_ADDR"] != "::1") {
	try { // Let's attempt to take CF-CONNECTING-IP header as the IP, because Cloudflare.
		$_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
	}
	catch(Exception $e) {
		// We really don't do nothing here. Just let's go ahead.
	}
}*/


// change this to change your board's default page
define('MAIN_PAGE', 'home');

$ajaxPage = false;
if(isset($_GET['ajax']))
	$ajaxPage = true;

require(__DIR__.'/lib/common.php');

$layout_crumbs = '';
$layout_actionlinks = '';

if (isset($_GET['forcelayout']))
{
	setcookie('forcelayout', (int)$_GET['forcelayout'], time()+365*24*3600, URL_ROOT, "", false, true);
	die(header('Location: '.$_SERVER['HTTP_REFERER']));
}

$layout_birthdays = getBirthdaysText();

$tpl->assign('logusername', htmlspecialchars($loguser['displayname'] ?: $loguser['name']));
$tpl->assign('loguserlink', UserLink($loguser));

$metaStuff = array(
	'description' => Settings::get('metaDescription'),
	'tags' => Settings::get('metaTags')
);


//=======================
// Do the page

if (isset($_GET['page']))
	$page = $_GET['page'];
else
	$page = MAIN_PAGE;
if(!ctype_alnum($page))
	$page = MAIN_PAGE;

define('CURRENT_PAGE', $page);

ob_start();
$layout_crumbs = "";

$fakeerror = false;
if ($loguser['flags'] & 0x2)
{
	if (rand(0,100) <= 75)
	{
		Alert("Could not load requested page: failed to connect to the database. Try again later.", 'Error');
		$fakeerror = true;
	}
}

if (!$fakeerror) {
	try {
		try {
			if(array_key_exists($page, $pluginpages)) {
				$plugin = $pluginpages[$page];
				$self = $plugins[$plugin];
				
				$page = __DIR__.'/plugins/'.$self['dir']."/pages/".$page.".php";
				if(!file_exists($page))
					throw new Exception(404);
				include($page);
				unset($self);
			} else {
				$page = __DIR__.'/pages/'.$page.'.php';
				if(!file_exists($page))
					throw new Exception(404);
				include($page);
			}
		}
		catch(Exception $e)
		{
			if ($e->getMessage() != 404) {
				throw $e;
			}
			require(__DIR__.'/pages/404.php');
		}
	}
	catch(KillException $e)
	{
		// Nothing. Just ignore this exception.
	}
}

if($ajaxPage)
{
	ob_end_flush();
	die();
}

$layout_contents = ob_get_contents();
ob_end_clean();

//Do these things only if it's not an ajax page.
include(__DIR__."/lib/views.php");
setLastActivity();

//=======================
// Panels and footer

require(__DIR__.'/layout/userpanel.php');
require(__DIR__.'/layout/menus.php');

$mobileswitch = '';
if ($mobileLayout) $mobileswitch .= 'Mobile view - ';
if ($_COOKIE['forcelayout']) $mobileswitch .= '<a href="?forcelayout=0" rel="nofollow">Auto view</a>';
else if ($mobileLayout) $mobileswitch .= '<a href="?forcelayout=-1" rel="nofollow">Force normal view</a>';
else $mobileswitch .= '<a href="?forcelayout=1" rel="nofollow">Force mobile view [BETA]</a>';


//=======================
// Notification bars

$notifications = getNotifications();


//=======================
// Misc stuff

$layout_time = formatdatenow();
$layout_onlineusers = getOnlineUsersText();
$layout_birthdays = getBirthdaysText();
$layout_views = '<span id="viewCount">'.number_format($misc['views']).'</span> '.__('views');

$layout_title = htmlspecialchars(Settings::get('boardname'));
if($title != '')
	$layout_title .= ' &raquo; '.$title;

$chat = '
					<span class="navButton"><a href="https://discord.gg/btQdJNw">Discord</a></span>';


//=======================
// Board logo and theme

$layout_logopic = 'img/logo.png';
if (!file_exists(__DIR__.'/'.$layout_logopic))
	$layout_logopic = 'img/logo.jpg';
$layout_logopic = resourceLink($layout_logopic);

$favicon = resourceLink('favicon.jpg');

$themefile = "themes/$theme/style.css";
if(!file_exists(__DIR__.'/'.$themefile))
	$themefile = "themes/$theme/style.php";


$layout_credits = 
'<img src="'.resourceLink('img/poweredbyblarg.png').'" style="float: left; margin-right: 3px;"> Blargboard 1.2 &middot; by StapleButter
Site ran by [user=1], [user=2] [url=/memberlist/?page=memberlist&sort=&order=desc&group=staff&name=]& others[/url].<br>';


$layout_contents = "<div id=\"page_contents\">$layout_contents</div>";

//=======================
// Print everything!

$perfdata = 'Page rendered in '.sprintf('%.03f',microtime(true)-$starttime).' seconds (with '.$queries.' SQL queries and '.sprintf('%.03f',memory_get_usage() / 1024).'K of RAM)';

?>
<!doctype html>
<html>
<head>
	<title><?php print $layout_title; ?></title>
	
	<meta http-equiv="Content-Type" content="text/html; CHARSET=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=10">
	<meta name="description" content="<?php print $metaStuff['description']; ?>">
	<meta name="keywords" content="<?php print $metaStuff['tags']; ?>">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<link rel="shortcut icon" type="image/x-icon" href="<?php print $favicon;?>">
	<link rel="stylesheet" type="text/css" href="<?php print resourceLink("css/common.css");?>">
	<link rel="stylesheet" type="text/css" id="theme_css" href="<?php print resourceLink($themefile); ?>">
	<link rel="stylesheet" type="text/css" href="<?php print resourceLink('css/font-awesome.min.css'); ?>">
	<link rel="apple-touch-icon" href="/apple-touch-icon.png">

	<script>
		(function(document,navigator,standalone) {
			// prevents links from apps from oppening in mobile safari
			// this javascript must be the first script in your <head>
			if ((standalone in navigator) && navigator[standalone]) {
				var curnode, location=document.location, stop=/^(a|html)$/i;
				document.addEventListener('click', function(e) {
					curnode=e.target;
					while (!(stop).test(curnode.nodeName)) {
						curnode=curnode.parentNode;
					}
					// Conditions to do this only on links to your own app
					// if you want all links, use if('href' in curnode) instead.
					if('href' in curnode && ( curnode.href.indexOf('http') || ~curnode.href.indexOf(location.host) ) ) {
						e.preventDefault();
						location.href = curnode.href;
					}
				},false);
			}
		})(document,window.navigator,'standalone');
	</script>
	<script type="text/javascript" src="<?php print resourceLink("js/jquery.js");?>"></script>
	<script type="text/javascript" src="<?php print resourceLink("js/tricks.js");?>"></script>
	<script type="text/javascript" src="<?php print resourceLink("js/jquery.tablednd_0_5.js");?>"></script>
	<script type="text/javascript" src="<?php print resourceLink("js/jquery.scrollTo-1.4.2-min.js");?>"></script>
	<script type="text/javascript" src="<?php print resourceLink("js/jscolor/jscolor.js");?>"></script>
	<script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
	<script type="text/javascript">boardroot = <?php print json_encode(URL_ROOT); ?>;</script>

	<?php $bucket = "pageHeader"; include(__DIR__."/lib/pluginloader.php"); ?>
	
	<?php if ($mobileLayout) { ?>
	<meta name="viewport" content="user-scalable=yes, initial-scale=1.0, width=device-width">
	<script type="text/javascript" src="<?php echo resourceLink('js/mobile.js'); ?>"></script>
	<?php if ($oldAndroid) { ?>
	<style type="text/css"> 
	#mobile-sidebar { height: auto!important; max-height: none!important; } 
	#realbody { max-height: none!important; max-width: none!important; overflow: scroll!important; } 
	</style>
	<?php }
	} ?>
</head>
<body style="width:100%; font-size: <?php echo $loguser['fontsize']; ?>%;">
<form action="<?php echo htmlentities(actionLink('login')); ?>" method="post" id="logout" style="display:none;"><input type="hidden" name="action" value="logout"></form>
<?php 
	if (Settings::get('maintenance'))
		echo '<div style="font-size:30px; font-weight:bold; color:red; background:black; padding:5px; border:2px solid red; position:absolute; top:30px; left:30px;">MAINTENANCE MODE</div>';

	RenderTemplate('pagelayout', array(
		'layout_contents' => $layout_contents,
		'layout_crumbs' => $layout_crumbs,
		'layout_actionlinks' => $layout_actionlinks,
		'headerlinks' => $headerlinks,
		'sidelinks' => $sidelinks,
		'layout_userpanel' => $layout_userpanel,
		'notifications' => $notifications,
		'boardname' => Settings::get('boardname'),
		'poratitle' => Settings::get('PoRATitle'),
		'poratext' => Settings::get('PoRAText'),
		'layout_logopic' => $layout_logopic,
		'layout_time' => $layout_time,
		'layout_views' => $layout_views,
		'layout_onlineusers' => $layout_onlineusers,
		'layout_birthdays' => $layout_birthdays,
		'layout_credits' => parseBBCode($layout_credits),
		'mobileswitch' => $mobileswitch,
		'chat' => $chat,
		'perfdata' => $perfdata)); 
?>
</body>
</html>
<?php

$bucket = "finish"; include(__DIR__.'/lib/pluginloader.php');

?>

