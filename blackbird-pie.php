<?php
/*
Plugin Name: Blackbird Pie
Plugin URI: http://themergency.com
Description: Add tweet visualizations to your site as can be found at http://media.twitter.com/blackbird-pie/
Version: 0.1.5
Author: Brad Vincent
Author URI: http://themergency.com
License: GPL2
*/

class BlackbirdPie {
	
	//constructor
	function BlackbirdPie() {
		if (!is_admin()) {
			add_action("wp_enqueue_scripts", array(&$this, "wp_enqueue_scripts"));
			add_shortcode("blackbirdpie", array(&$this, "shortcode"));
		}
	}
	
	function wp_enqueue_scripts()
	{
		wp_enqueue_script("jquery-timeago", plugins_url("/js/jquery.timeago.js", __FILE__), array("jquery"));
		wp_enqueue_script("blackbird-pie", plugins_url("/js/blackbirdpie.js", __FILE__), array("jquery", "jquery-timeago"));
	}
	
	function shortcode($atts) {
		// Extract the attributes
		extract(shortcode_atts(array(
			"id" => false,
			"url" => false
		), $atts));
		
		if ($url) {
			if (preg_match('/^http:\/\/twitter\.com\/(\w+)\/status(es)*\/(\d+)$/', $url, $matches)) {
				$id = $matches[3];
			}
		} 
		
		if ($id) {
		
			//are inside the loop?
			global $wp_query;
			if ($wp_query->in_the_loop) {
				global $post;
				$post_id = $post->ID;
			}
		
			if ($post_id > 0)
			{
				//try and see if we have the blackbird status HTML already saved
				$blackbird = get_post_meta($post_id, 'bbp_status_'.$id, true);
				if (!empty($blackbird))
					return $blackbird;
			}
		
			$data = $this->get_tweet_details($id);
			$http_code = $data->status->http_code;

			if ($http_code == "200") {
				require_once('Autolink.php');
				$autolinker = new Twitter_Autolink();
				$screenName = $data->contents->user->screen_name;
				$realName = $data->contents->user->name;
				$tweetText = $autolinker->autolink($data->contents->text);
				$source = $data->contents->source;
				$profilePic = $data->contents->user->profile_image_url;
				$profileBackgroundColor = $data->contents->user->profile_background_color;
				$profileBackgroundTile = $data->contents->user->profile_background_tile;
				if (!$profileBackgroundTile)
					$profileBackgroundTileHTML = "no-repeat";
					
				$profileBackgroundImage = $data->contents->user->profile_background_image_url;
				$profileTextColor = $data->contents->user->profile_text_color;
				$profileLinkColor = $data->contents->user->profile_link_color;
				$createdAt = $data->contents->created_at;
				$utcOffset = $data->contents->user->utc_offset;
				if (strlen($utcOffset)==0) $utcOffset = '0';
				$timeStamp = strtotime($createdAt.' +'.$utcOffset.' seconds');
				
				$tweetURL = "http://twitter.com/$screenName/status/$id";
				
				$dateFormat = get_option('date_format');
				if (strlen($dateFormat)==0) $dateFormat = __('F j, Y');
				$timeFormat = get_option('time_format');
				if (strlen($timeFormat)==0) $timeFormat = __('g:i a');
				
				$friendlyDate = date($dateFormat.' '.$timeFormat, $timeStamp);

				if ($screenName != $realName) {
					$realNameHTML = "<br/>$realName";
				}
				
				$tweetHTML = "<!-- tweet id : $id -->
			<style type='text/css'>#bbpBox_$id{background:#$profileBackgroundColor url($profileBackgroundImage) $profileBackgroundTileHTML !important;padding:20px;}#bbpBox_$id p.bbpTweet{background:#fff;padding:10px 12px 10px 12px !important;margin:0 !important;min-height:48px;color:#$profileTextColor !important;font-size:18px !important;line-height:22px;-moz-border-radius:5px;-webkit-border-radius:5px}#bbpBox_$id p.bbpTweet a {color:#$profileLinkColor !important}#bbpBox_$id p.bbpTweet span.metadata{display:block;width:100%;clear:both;margin-top:8px  !important;padding-top:12px !important;height:40px;border-top:1px solid #e6e6e6}#bbpBox_$id p.bbpTweet span.metadata span.author{line-height:19px}#bbpBox_$id p.bbpTweet span.metadata span.author img{float:left;margin:0 7px 0px 0px !important;width:38px;height:38px;padding:0 !important;border:none !important;}#bbpBox_$id p.bbpTweet a:hover{text-decoration:underline}#bbpBox_$id p.bbpTweet span.timestamp{font-size:12px;display:block}</style>
			 
			<div id='bbpBox_$id'><p class='bbpTweet'>$tweetText<span class='timestamp'><a title='tweeted on $friendlyDate' class='timeago' href='$tweetURL'>$timeStamp</a> via $source</span><span class='metadata'><span class='author'><a href='http://twitter.com/$screenName'><img src='$profilePic' /></a><strong><a href='http://twitter.com/$screenName'>$screenName</a></strong>$realNameHTML</span></span></p></div>
			<!-- end of tweet -->";

				// save the tweet HTML into a custom field
				if (!empty($tweetHTML) && $post_id > 0)
					update_post_meta($post_id, 'bbp_status_'.$id, $tweetHTML);

				return $tweetHTML;
			} else {
				return "There was a problem connecting to Twitter.";
			}
		}
		
		return NULL;
	}

	function get_tweet_details($id) {
		
		$encoded = urlencode("http://api.twitter.com/1/statuses/show.json?id={$id}");
		
		$request_url = "http://media.twitter.com/tweetproxy/?url={$encoded}";
		
		if(!class_exists('WP_Http'))
			include_once(ABSPATH . WPINC . '/class-http.php');
			
		$request = new WP_Http;
		$result = $request->request($request_url);
		
		if (gettype($result) == "object" && get_class($result) == "WP_Error")
			return NULL;
		
		return json_decode($result["body"]);
	}
}

add_action("init", create_function('', 'global $BlackbirdPie; $BlackbirdPie = new BlackbirdPie();'));

?>