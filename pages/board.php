<?php
if (!defined('BLARG')) die();

$board = $pageParams['id'];
if (!$board || !isset($forumBoards[$board])) $board = '';

if($loguserid && isset($_GET['action']) && $_GET['action'] == "markallread") {
	Query("REPLACE INTO {threadsread} (id,thread,date) SELECT {0}, t.id, {1} FROM {threads} t".($board!='' ? ' LEFT JOIN {forums} f ON f.id=t.forum WHERE f.board={2}' : ''), 
		$loguserid, time(), $board);

	die(header("Location: ".actionLink("board", $board)));
}

$links = array();
if($loguserid)
	$links[] = actionLinkTag(__("Mark all forums read"), "board", $board, "action=markallread");

MakeCrumbs(forumCrumbs(array('board' => $board)), $links);

if ($board == '') {
	$statData = Fetch(Query("SELECT
		(SELECT COUNT(*) FROM {threads}) AS numThreads,
		(SELECT COUNT(*) FROM {posts}) AS numPosts,
		(SELECT COUNT(*) FROM {users}) AS numUsers,
		(select count(*) from {posts} where date > {0}) AS newToday,
		(select count(*) from {posts} where date > {1}) AS newLastHour,
		(select count(*) from {posts} where date > {2}) AS newLastWeek,
		(select count(*) from {users} where lastposttime > {3}) AS numActive",
		 time() - 86400, time() - 3600, time() - 604800, time() - 2592000));

	$statData['pctActive'] = $statData['numUsers'] ? ceil((100 / $statData['numUsers']) * $statData['numActive']) : 0;
	$lastUser = Query("select u.(_userfields) from {users} u order by u.regdate desc limit 1");
	if(numRows($lastUser)) {
		$lastUser = getDataPrefix(Fetch($lastUser), "u_");
		$statData['lastUserLink'] = UserLink($lastUser);
	}

	RenderTemplate('boardstats', array('stats' => $statData));
}

if ($board == 'staff') {
	makeStaffAnncBar();
} else {
	makeAnncBar();
}

makeForumListing(0, $board);

?>
