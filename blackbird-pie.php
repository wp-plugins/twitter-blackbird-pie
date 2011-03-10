<?php
/*
Plugin Name: Blackbird Pie
Plugin URI: http://themergency.com/plugins/twitter-blackbird-pie/
Description: Add tweet visualizations to your site as can be found at http://media.twitter.com/blackbird-pie/. 
Version: 0.4.1.1
Author: Brad Vincent
Author URI: http://themergency.com
License: GPL2
*/

class BlackbirdPie {
	
    //constructor
    function BlackbirdPie() {
        define( 'BBP_NAME',  'blackbirdpie' );
        define( 'BBP_REGEX', '/^(http|https):\/\/twitter\.com\/(?:#!\/)?(\w+)\/status(es)?\/(\d+)$/' );
        define( 'BBP_DIR', plugin_dir_path( __FILE__ ) );
        define( 'BBP_URL', plugins_url( '/', __FILE__ ) );

        if (!is_admin()) {
            //register shortcode
            add_shortcode(BBP_NAME, array(&$this, 'shortcode'));
            //register auto embed
            wp_embed_register_handler( BBP_NAME, BBP_REGEX, array(&$this, 'blackbirdpie_embed_handler') );
        } else {
            //setup WYSIWYG editor
            $this->add_editor_button();
        }
    }
	
    function add_editor_button() {
        // Don't bother doing this stuff if the current user lacks permissions
        if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
            return;

        // Add only in Rich Editor mode
        if ( get_user_option('rich_editing') == 'true' ) {
            add_filter( 'mce_external_plugins', array(&$this, 'add_myplugin_tinymce_plugin') );
            add_filter( 'teeny_mce_buttons', array(&$this, 'register_myplugin_button') );
            add_filter( 'mce_buttons', array(&$this, 'register_myplugin_button') );
        }
    }
	
    function register_myplugin_button($buttons) {
        array_push($buttons, 'separator', BBP_NAME);
        return $buttons;
    }
	 
    // Load the TinyMCE plugin : editor_plugin.js (wp2.5)
    function add_myplugin_tinymce_plugin($plugin_array) {
        $plugin_array[BBP_NAME] = BBP_URL . 'tinymce/editor_plugin_blackbirdpie.js';
        return $plugin_array;
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

    function encode($value) {
        $json = new Services_JSON();
        return $json->encode($value);
    }
	
    function shortcode($atts) {

        // Extract the attributes
        extract(shortcode_atts(array(
            "id" => false,
            "url" => false
        ), $atts));

        //extract the status ID from $id (incase someone incorrectly used a shortcode lie [blackbirdpie id="http://twitter..."])
        if ($id) {
            if (preg_match(BBP_REGEX, $id, $matches)) {
                $id = $matches[4];
            }
        }

        //extract the status ID from $url
        if ($url) {
            if (preg_match(BBP_REGEX, $url, $matches)) {
                $id = $matches[4];
            }
        }

        if ($id) {

            //are we inside the loop?
            global $wp_query;
            if ($wp_query->in_the_loop) {
                global $post;
                $post_id = $post->ID;
            }

            if ($post_id > 0) {
                //try and get the tweet data from the post
                $args = get_post_meta( $post_id, '_'.BBP_NAME.'-'.$id );
            }

            if ( empty($args) ) {
                //we need to get the tweet json data from twitter API
                $data = $this->get_tweet_details($id);

                if ( !empty($data->text) ) {
                    require_once('unicode.php');
                    $oUnicodeReplace = new unicode_replace_entities();

                    //fix for non english tweets
                    $data->text = addslashes($oUnicodeReplace->UTF8entities($data->text));
                    $data->user->screen_name = addslashes($oUnicodeReplace->UTF8entities($data->user->screen_name));
                    $data->user->name = addslashes($oUnicodeReplace->UTF8entities($data->user->name));

                    require_once('autolinker.php');
                    $autolinker = new Twitter_Autolink();

                    //get site date and time formats
                    $dateFormat = get_option('date_format');
                    if (strlen($dateFormat)==0) $dateFormat = __('F j, Y');

                    $timeFormat = get_option('time_format');
                    if (strlen($timeFormat)==0) $timeFormat = __('g:i a');

                    $dateTimeFormat = $dateFormat.' '.$timeFormat;

                    $timeStamp = strtotime($data->created_at);

                    $args = array(
                        'id' => $id,
                        'screen_name' => stripslashes($data->user->screen_name),
                        'real_name' => stripslashes($data->user->name),
                        'tweet_text' => stripslashes($autolinker->autolink($data->text)),
                        'source' => $data->source,

                        'profile_pic' => $data->user->profile_image_url,
                        'profile_bg_color' => $data->user->profile_background_color,
                        'profile_bg_tile' => $data->user->profile_background_tile,
                        'profile_bg_image' => $data->user->profile_background_image_url,
                        'profile_text_color' => $data->user->profile_text_color,
                        'profile_link_color' => $data->user->profile_link_color,

                        'time_stamp' => $timeStamp,
                        'utc_offset' => $data->user->utc_offset,

                        'friendly_date' => date($dateTimeFormat, $timeStamp),
                        'time_ago' => $this->ago($timeStamp, $dateTimeFormat)
                    );

                    // save the tweet JSON data into a custom field
                    if ($post_id > 0) {
                        update_post_meta($post_id, '_'.BBP_NAME.'-'.$id, $args);
                    }
                } //endif http_code == "200"
                else {
                    return 'There was a problem connecting to Twitter.';
                }
            } //endif $args is set
            else {
                $args = $args[0];
            }
            
            if ( !has_filter('bbp_create_tweet') )
                add_filter('bbp_create_tweet', array( &$this, 'create_tweet_html' ));

            return apply_filters('bbp_create_tweet', $args);
        }

        return 'There was a problem with the blakbirdpie shortcode';
    }

    function create_tweet_html($args) {

        if ( !$args['profile_bg_tile'] )
            $profile_bg_tile_HTML = " background-repeat:no-repeat";

        $id = $args['id'];

        $url = "http://twitter.com/#!/{$args['screen_name']}/status/{$id}";

        $profile_url = "http://twitter.com/#!/{$args['screen_name']}";
		//if(is_feed())
        $tweetHTML = "<!-- tweet id : $id -->
        <style type='text/css'>
            #bbpBox_$id a { text-decoration:none; color:#{$args['profile_link_color']} !important; }
            #bbpBox_$id a:hover { text-decoration:underline; }
        </style>
        <div id='bbpBox_$id' class='bbpBox' style='padding:20px; margin:5px 0; background-color:#{$args['profile_bg_color']}; background-image:url({$args['profile_bg_image']});$profile_bg_tile_HTML'><div style='background:#fff; padding:10px; margin:0; min-height:48px; color:#{$args['profile_text_color']}; -moz-border-radius:5px; -webkit-border-radius:5px;'><span style='width:100%; font-size:18px; line-height:22px;'>{$args['tweet_text']}</span><div style='font-size:12px; width:100%; padding:5px 0; margin:0 0 10px 0; border-bottom:1px solid #e6e6e6;'><a title='tweeted on {$args['friendly_date']}' href='{$url}'>{$args['time_ago']}</a> via {$args['source']}</div><div style='float:left; padding:0; margin:0'><a href='{$profile_url}'><img style='width:48px; height:48px; padding-right:7px; border:none; margin:0' src='{$args['profile_pic']}' /></a></div><div style='float:left; padding:0; margin:0'><a style='font-weight:bold' href='{$profile_url}'>@{$args['screen_name']}</a><div style='margin:0; padding-top:2px'>{$args['real_name']}</div></div><div style='clear:both'></div></div></div>
        <!-- end of tweet -->";

        return $tweetHTML;
    }

    function get_tweet_details($id) {
        include_once('json.php');

        $request_url = "http://api.twitter.com/1/statuses/show.json?id={$id}";

        if(!class_exists('WP_Http'))
            include_once(ABSPATH . WPINC . '/class-http.php');

        $request = new WP_Http;
        $result = $request->request($request_url);

        if (gettype($result) == "object" && get_class($result) == "WP_Error")
            return NULL;

        return $this->decode($result["body"]);
    }

    function blackbirdpie_embed_handler( $matches, $attr, $url, $rawattr ) {
        return $this->shortcode( array( 'url' => $url ) );
    }
}

add_action("init", create_function('', 'global $BlackbirdPie; $BlackbirdPie = new BlackbirdPie();'));


?>