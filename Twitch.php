<?php

// The script generates m3u-playlist using Twitch API.
// Tested on webos 3.0 + SS IPTV application

// There are 4 pages:
// 1. Main page (param 'page' = empty) - contains 3 fast folders: Twitch Following, Twitch Games, Hearthstone.
// 2. Games - shows all current played games. Most popular first.
// 3. Game - shows list of streams sorted by viewers descending.
// 	&g - game name.
// 4. MyTwitch - shows followed channels.
// 	&user - your twitch login.
// 5. Stream - returns m3u8 file which contains source-quality stream.
// 	&s - streamer name.

$page = $_GET["page"];
if (empty($page)) 
{
	echo <<<EOL
	#EXTM3U 
	#EXTINF:-1 type="playlist" tvg-logo="https://s3-us-west-2.amazonaws.com/web-design-ext-production/p/Twitch_474x356.png", Twitch Following
	#EXTSIZE: Big
	http://192.168.1.39/twitch.php?page=mytwitch&user=ilya138
	#EXTINF:-1 type="playlist" tvg-logo="https://s3-us-west-2.amazonaws.com/web-design-ext-production/p/Twitch_474x356.png", Twitch Games
	#EXTSIZE: big
	http://192.168.1.39/twitch.php?page=games
	#EXTINF:-1 type="playlist" tvg-logo="https://static-cdn.jtvnw.net/ttv-boxart/Hearthstone:%20Heroes%20of%20Warcraft-272x380.jpg", Hearthstone
	#EXTSIZE: big
	http://192.168.1.39/twitch.php?page=game&g=Hearthstone%3A+Heroes+of+Warcraft
	EOL;

}
elseif ($page === "game") 
{
	header("Content-Type:text/plain;charset=utf-8");
	$text = "#EXTM3U\n";
	$response = file_get_contents("https://api.twitch.tv/kraken/streams?client_id=jzkbprff40iqj646a697cyrvl0zt2m6&limit=30&game=".urlencode($_GET['g']));
	$json = json_decode($response, true); 	
	foreach($json['streams'] as $item_t)
	{
		$item = $item_t['channel']['name'];
		$s_viewers = $item_t['viewers'];
		$s_status = $item_t['channel']['status'];
		$s_logo = $item_t['preview']['large'];
		$s_name= $item_t['channel']['display_name'];
		$text = $text . "#EXTINF:-1 mpeg4 mime-type=application/x-mpegURL tvg-logo=". $s_logo .",". $s_name."[".$s_viewers."]\n http://192.168.1.39/twitch.php?page=stream&s=" .$item. "\n";
		$text = $text . "#EXTSIZE: medium\n";
	}
	echo $text;
}
elseif ($page === "games") 
{
	header("Content-Type:text/plain;charset=utf-8");
	$text = "#EXTM3U\n";
	$response = file_get_contents("https://api.twitch.tv/kraken/games/top?client_id=jzkbprff40iqj646a697cyrvl0zt2m6&limit=30&offset=0");
	$json = json_decode($response, true); 
	foreach($json['top'] as $game){
		$name = $game['game']['name'];
		$logo = $game['game']['box']['large'];
		$viewers = $game['viewers'];
		$text = $text . "#EXTINF:-1 mpeg4 type=playlist tvg-logo=". $logo .",". $name."[".$viewers."]\n http://".$_SERVER['SERVER_NAME']."/twitch.php?page=game&g=".urlencode($name)."\n";
		$text = $text . "#EXTSIZE: medium\n";
	}
	echo $text;
}
elseif ($page === "mytwitch") 
{
	header("Content-Type:text/plain;charset=utf-8");
	$user = $_GET["user"];
	$text = "#EXTM3U\n";
	$response = file_get_contents("https://api.twitch.tv/kraken/users/$user/follows/channels?client_id=jzkbprff40iqj646a697cyrvl0zt2m6");
	$json = json_decode($response, true); 
	foreach($json['follows'] as $item_t)
	{
		$item = $item_t['channel']['name'];
		$s_status = $item_t['channel']['status'];
		$s_logo = $item_t['channel']['video_banner'];
		$s_name= $item_t['channel']['display_name'];
		$text = $text . "#EXTINF:-1 mpeg4 mime-type=application/x-mpegURL tvg-logo=". $s_logo .",". $s_name."\n http://".$_SERVER['SERVER_NAME']."/twitch.php?page=stream&s=" .$item. "\n";
		$text = $text . '#EXTSIZE: medium';
	}
	echo $text;
}
elseif ($page === "stream") 
{
	$channel_name = $_GET['s'];
	$response = file_get_contents("http://api.twitch.tv/api/channels/" . $channel_name. "/access_token?client_id=jzkbprff40iqj646a697cyrvl0zt2m6");
	$token_content = json_decode($response, true); 
	$token = $token_content['token'];
	$sig = $token_content['sig'];
	$random = rand(0, 10000000);
	$url_streams = "http://usher.twitch.tv/api/channel/hls/$channel_name.m3u8?player=twitchweb&token=$token&sig=$sig&\$allow_audio_only=true&allow_source=true&type=any&p=$random";
	$streams = explode("\n", file_get_contents($url_streams));
	foreach($streams as $key => $value)
	{
		if(stripos($value, "#EXT-X-STREAM-INF") !== false)
		{
			header("Location: ".$streams[$key + 1]);
			exit();
		}
	}
}
?>
