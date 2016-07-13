<?php
/*
 * Plugin Name: RSS Reader Plugin
 * Plugin URI: http://example.rss
 * Description: This plugin loads titles from a given RSS feed.
 * Version: 1.0.0
 * Author: Anthony Amaumo
 * Author URI: http://example.com
 * License: MIT
 */

class FeedReader{

    function __construct($url){
        $this->url = $url;
        $this->conn = curl_init();
        $this->timeout = 5;

    }

    function request($url){

        curl_setopt($this->conn, CURLOPT_URL, $this->url);
        curl_setopt($this->conn, CURLOPT_USERAGENT, 
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1;
            .NET CLR 1.1.4322)');  //using Mozill as UA for testing purposes only!
        curl_setopt($this->conn, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->conn, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->conn, CURLOPT_TIMEOUT, 30);

        $this->page_content = curl_exec($this->conn);
        $httpcode = curl_getinfo($this->conn, CURLINFO_HTTP_CODE);

        curl_close($this->conn);

        return $this;
    }

    function parse(){
        libxml_use_internal_errors(true);

        $this->content = simplexml_load_string($this->page_content)
            or die("Error: Cannot create object from XML");
        if($this->content === false){
            echo "Failed loading xml\n";
            foreach(libxml_get_errors() as $error){
                echo "\t", $error->message;
            }
        }
        return $this;
    }

    function display(){
        echo '<div class="rssFeed">';
        echo '<h5><a href="' . $this->content->channel->link .'">' . $this->content->channel->title . '</a></h5>';

        foreach($this->content->channel->item as $item){
            $title = $item->title;
            $link = $item->link;
            $pubDate = $item->pubDate;
            $description = $item->description;

            echo '<li class="feed_item">';
            echo '<p><strong><a href="' . $link . '" title="' . $title . '" target="_blank">' . $title . '</a></strong></p>';
            echo 'Created: ' . $pubDate . '<br/>';
            echo $description . "<br/>";
            echo '</li>';
        }
        echo '</div>';
    }
}



function rss_shortcode($atts){
    $params = shortcode_atts(array(
        'url' => ''
    ), $atts);
    $rss = new FeedReader($params['url']);
    $result = $rss->request($params['url'])->parse();
    if(!$result){
        echo "<pre> Error occurred while retrieving remote document</pre>";
        return false;
    }
    $result->display();
    
}

add_shortcode('rss_feeder', 'rss_shortcode');

function rss_scripts(){
    wp_register_script('rss_js', plugins_url('/js/rss.js', __FILE__), array('jquery'));
    wp_enqueue_script('rss_js');

    wp_register_style('rss_style', plugins_url('/css/rss.css', __FILE__), array(), 'all');
    wp_enqueue_style('rss_style');
}
add_action('wp_enqueue_scripts', 'rss_scripts');
