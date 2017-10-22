<?php

$starttime = microtime(true);
define('BLARG', 1);

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

$match = $router->match();

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
		// Throw the 404 page if we don't have a match already.
		if ($match === false)
			throw new Exception(404);
		else {
			// Set up the stuff for our page loader.
			$pageName = $match['target'];
			$pageParams = $match['params'];

			// MabiPro: Set this to Smarty for template purposes
			$tpl->assign('currentPage', $pageName);

			// Check first for plugin pages.
			if(array_key_exists($pageName, $pluginpages)) {
				// TODO: Make this cleaner than a hack.
				$plugin = $pluginpages[$pageName];
				$self = $plugins[$plugin];

				$page = __DIR__ . '/plugins/' . $self['dir'] . '/pages/' . $pageName . '.php';
				if (file_exists($page))
					require_once($page);
				else
					throw new Exception(404);
			} else {
				// Check now for core pages.
				$page = __DIR__ . '/pages/' . $pageName . '.php';

				if (file_exists($page))
					require_once($page);
				else
					throw new Exception(404);
			}
		}
	}
	catch(Exception $e) {
		// is this uded at all?
		if ($e->getMessage() != 404) throw $e;
		require_once(__DIR__.'/pages/404.php');
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

$chat = '<span class="navButton"><a href="https://discord.gg/btQdJNw">Discord</a></span><span class="navButton"><a href="https://www.patreon.com/mariomods/">Patreon</a></span>';


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
'<img src="'.resourceLink('img/poweredbyblarg.png').'" style="float: left; margin-right: 3px;"> Blargboard &middot; by StapleButter
Site ran by [user=1], [user=20] [url=/memberlist/?page=memberlist&sort=&order=desc&group=staff&name=]& others[/url].';


$layout_contents = "<div id=\"page_contents\">$layout_contents</div>";

//=======================
// Print everything!

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
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<base href="<?php echo getServerURLNoSlash($ishttps)?>" /><!--[if IE]></base><![endif]-->

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
		'chat' => $chat)); 
?>
</body>
</html>
<?php

$bucket = "finish"; include(__DIR__.'/lib/pluginloader.php');

?>

