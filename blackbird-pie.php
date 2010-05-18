<?php
/*
Plugin Name: Blackbird Pie
Plugin URI: http://themergency.com
Description: Add tweet visualizations to your site as can be found at http://media.twitter.com/blackbird-pie/
Version: 0.2.2
Author: Brad Vincent
Author URI: http://themergency.com
License: GPL2
*/

class BlackbirdPie {
	
	//constructor
	function BlackbirdPie() {
		if (!is_admin()) {
			add_shortcode("blackbirdpie", array(&$this, "shortcode"));
		}
	}
	
	/*
	modified from http://www.php.net/manual/en/function.time.php#96097
	*/
    function ago($datefrom, $format) {
        $dateto = time();
        
        // Calculate the difference in seconds betweeen
        // the two timestamps

        $difference = $dateto - $datefrom;

        // Based on the interval, determine the
        // number of units between the two dates
        // From this point on, you would be hard
        // pushed telling the difference between
        // this function and DateDiff. If the $datediff
        // returned is 1, be sure to return the singular
        // of the unit, e.g. 'day' rather 'days'
    
        switch(true)
        {
            // If difference is less than 60 seconds,
            // seconds is a good interval of choice
            case(strtotime('-1 min', $dateto) < $datefrom):
                $datediff = $difference;
                $res = 'less than a minute ago';
                break;
            // If difference is between 60 seconds and
            // 60 minutes, minutes is a good interval
            case(strtotime('-1 hour', $dateto) < $datefrom):
                $datediff = floor($difference / 60);
                $res = ($datediff==1) ? $datediff.' minute ago' : $datediff.' minutes ago';
                break;
            // If difference is between 1 hour and 24 hours
            // hours is a good interval
            case(strtotime('-1 day', $dateto) < $datefrom):
                $datediff = floor($difference / 60 / 60);
                $res = ($datediff==1) ? 'about '.$datediff.' hour ago' : 'about '.$datediff.' hours ago';
                break;				
            default:
				$res = date($format, $datefrom);
        }
        return $res;
	}
	
	function decode($value) {
		$json = new Services_JSON();
		return $json->decode($value);
	}
	
	function shortcode($atts) {
		include_once('json.php');
	
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
			
			$data = false;
			$saveData = false;
		
			if ($post_id > 0) {
				//try and see if we have the tweet JSON data already saved
				$jsonData = get_post_meta($post_id, 'bbp_status_json_'.$id, true);
				$data = $this->decode($jsonData);
			}
			
			if (!$data) {
				//we need to get the tweet json data from twitter API
				$data = $this->get_tweet_details($id);
				$saveData = true;
				//echo 'FETCH FROM TWITTER';
			}
			
			$http_code = $data->status->http_code;
			
			if ($http_code == "200") {
			
				// save the tweet JSON data into a custom field
				if ($saveData && $post_id > 0)
					update_post_meta($post_id, 'bbp_status_json_'.$id, json_encode($data));			
			
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

				$timeStamp = strtotime($createdAt); //.' +'.$utcOffset.' seconds');

				$tweetURL = "http://twitter.com/$screenName/status/$id";
				
				$dateFormat = get_option('date_format');
				if (strlen($dateFormat)==0) $dateFormat = __('F j, Y');
				$timeFormat = get_option('time_format');
				if (strlen($timeFormat)==0) $timeFormat = __('g:i a');
				
				$dateTimeFormat = $dateFormat.' '.$timeFormat;
				
				$friendlyDate = date($dateTimeFormat, $timeStamp);
				
				$timeAgo = $this->ago($timeStamp, $dateTimeFormat);
				
				if ($screenName != $realName) {
					$realNameHTML = "<br/>$realName";
				}
				
				$tweetHTML = "<!-- tweet id : $id -->
			<style type='text/css'>#bbpBox_$id{background:#$profileBackgroundColor url($profileBackgroundImage) $profileBackgroundTileHTML !important;padding:20px;}#bbpBox_$id p.bbpTweet{background:#fff;padding:10px 12px 10px 12px !important;margin:0 !important;min-height:48px;color:#$profileTextColor !important;font-size:18px !important;line-height:22px;-moz-border-radius:5px;-webkit-border-radius:5px}#bbpBox_$id p.bbpTweet a {color:#$profileLinkColor !important}#bbpBox_$id p.bbpTweet span.metadata{display:block;width:100%;clear:both;margin-top:8px  !important;padding-top:12px !important;height:40px;border-top:1px solid #e6e6e6}#bbpBox_$id p.bbpTweet span.metadata span.author{line-height:19px}#bbpBox_$id p.bbpTweet span.metadata span.author img{float:left;margin:0 7px 0px 0px !important;width:38px;height:38px;padding:0 !important;border:none !important;}#bbpBox_$id p.bbpTweet a:hover{text-decoration:underline}#bbpBox_$id p.bbpTweet span.timestamp{font-size:12px;display:block}</style>
			 
			<div id='bbpBox_$id'><p class='bbpTweet'>$tweetText<span class='timestamp'><a title='tweeted on $friendlyDate' href='$tweetURL'>$timeAgo</a> via $source</span><span class='metadata'><span class='author'><a href='http://twitter.com/$screenName'><img src='$profilePic' /></a><strong><a href='http://twitter.com/$screenName'>$screenName</a></strong>$realNameHTML</span></span></p></div>
			<!-- end of tweet -->";

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
		
		return $this->decode($result["body"]);
	}
}

add_action("init", create_function('', 'global $BlackbirdPie; $BlackbirdPie = new BlackbirdPie();'));

?>