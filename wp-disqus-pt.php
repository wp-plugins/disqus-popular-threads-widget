<?php
/*
 * Plugin Name: Disqus Popular Threads Widget
 * Plugin URI: http://presshive.com
 * Author: <a href="http://presshive.com/">Presshive</a>
 * Version: 1.0
 * Description: Integrates with the Disqus API to show your most popular threads (most commented posts). Can be added via sidebar widget, template tag, or shortcode. 
 * Tags: disqus, popular posts, comments, most commented, most popular, popular threads, disqus most commented
 * License: GPLv2 or later
 */

/*  Copyright 2013  

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
?>
<?php
global $wp_disqus_script_counter;
$wp_disqus_script_counter = 0;
define('WP_DISQUS_PLUGIN_URL', WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)));

add_action( 'admin_menu', 'dp_admin_menu_page' );
function dp_admin_menu_page(){
    add_submenu_page('options-general.php', 'Disqus Settings', 'Disqus Settings', 'manage_options', 'dp_disqus_page', 'dp_disqus_page');
}

// function to show settings page
function dp_disqus_page(){
    $msg = '';
    $submit = isset( $_POST['submit'] ) ? $_POST['submit'] : '';
    if( $submit == 'Save'){
        update_option('_diqus_api_key', $_POST['api_key']);
        update_option('_diqus_forum_ID', $_POST['forum_ID']);
        update_option('_diqus_forum_domain', $_POST['forum_domain']);
        
        $msg = '<div class="updated"><p><strong>Settings saved </strong></p></div>';
    }
    $api_key = get_option('_diqus_api_key');
    $forum_ID = get_option('_diqus_forum_ID');
    $forum_domain = get_option('_diqus_forum_domain');
    ?>
<div class="wrap">
    <h2>Disqus Popular Threads Widget Settings</h2>
    <?php echo $msg; ?>
    <form action="?page=dp_disqus_page" method="post" id="">
        <table width="100%">
            <tr>
                <td><label for="api_key">Disqus Public API Key</label></td>
                <td><input type="text" value="<?php echo $api_key; ?>" name="api_key" id="api_key" class="widefat"/></td>
            </tr>
            <tr>
                <td><label for="forum_ID">Forum ID</label></td>
                <td><input type="text" value="<?php echo $forum_ID; ?>" name="forum_ID" id="forum_ID" class="widefat"/></td>
            </tr>
            <tr>
                <td><label for="forum_domain">Domain</label></td>
                <td><input type="text" value="<?php echo $forum_domain; ?>" name="forum_domain" id="forum_domain" class="widefat"/></td>
            </tr>
            <tr>
                <td colspan="2"> <input type="submit" name="submit" value="Save" class="button"/></td>
            </tr>
        </table>
    </form>
</div>
<?php
}

class WP_Disqus_pt_widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
	 		'WP_Disqus_pt_widget', 
			'Disqus Popular Threads Widget',
			array( 'description' => __( 'Your Most Commented Threads'), ) // Args
		);
	}

 	public function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
		$days_back = isset($instance['days_back']) ? $instance['days_back'] : 90;
                $choices  = array('1h'=> '1 Hour', '6h' => '6 Hour', '12h' => '12 Hour', '1d' => '1 Day', '3d' => '3 Days', '7d' => '7 Days', '30d'=> '30 Days', '90d'=> '90 Days');
                ?>
                <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

                <p><label for="<?php echo $this->get_field_id('days_back'); ?>"><?php _e('Number of days to go back:'); ?></label>
		<select id="<?php echo $this->get_field_id('days_back'); ?>" name="<?php echo $this->get_field_name('days_back'); ?>" >
                <?php foreach ( $choices as $key => $val ){
                    echo '<option value="'.$key.'" '.selected($days_back, $key, false).'>'.$val.'</option>';
                }?>
                </select></p>

                <?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = absint( $new_instance['number'] );
		$instance['days_back'] = $new_instance['days_back'];
		return $instance;
	}

	public function widget( $args, $instance ) {
                global $wp_disqus_script_counter;
 		extract($args, EXTR_SKIP);
 		$output = '';
                $api_key = get_option('_diqus_api_key');
                $forum_ID = get_option('_diqus_forum_ID');
                
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) )
 			$number = 5;
                
		if ( empty( $instance['days_back'] )  )
 			$days_back = '7d';

		$output .= $before_widget;
		if ( $title )
			$output .= $before_title . $title . $after_title;

		
		$output .= "<script type=\"text/javascript\">
                                var pt{$wp_disqus_script_counter} = new WpDisqusPt({
                                    api_key: '{$api_key}',
                                    forum: '{$forum_ID}',
                                    limit: '{$number}',
                                    days_back: '{$days_back}'
                                });
                                pt{$wp_disqus_script_counter}.getData();
                            </script>";
                                    
                $output .= $after_widget;
                
                $wp_disqus_script_counter++;
		echo $output;
	}

}
// register WP_Disqus_pt_widget widget
add_action( 'widgets_init', create_function( '', 'register_widget( "WP_Disqus_pt_widget" );' ) );

// enqueue scripts in front end
function dq_enqueue_scripts(){
    $forum_domain = get_option('_diqus_forum_domain');
    wp_register_script('wp_disqus_script', WP_DISQUS_PLUGIN_URL.'/js/wp-disqus-pt.js', array(), '');
    wp_localize_script('wp_disqus_script', wdpObj, array( 'domain'=> json_encode($forum_domain) ), '');
    wp_enqueue_script('wp_disqus_script');
}

add_action('wp_enqueue_scripts', 'dq_enqueue_scripts');

/**
 * function shows popular threads using Disqus API
 *
 * @param $days_back int no of days to get threads default is 90
 * @param $show_threads int no of threads to show default is 5
 * @param $echo bool echo or return default is false
 *
 * @return string it returns script to show popular threads
 */
function wdp_get_threads( $days_back = '7d', $show_threads = 5, $echo = false ){
    global $wp_disqus_script_counter;
    $api_key = get_option('_diqus_api_key');
    $forum_ID = get_option('_diqus_forum_ID');
    $output = '';
    $output .= "<script type=\"text/javascript\">
                    var pt{$wp_disqus_script_counter} = new WpDisqusPt({
                        api_key: '{$api_key}',
                        forum: '{$forum_ID}',
                        limit: '{$show_threads}',
                        days_back: '{$days_back}'
                    });
                    pt{$wp_disqus_script_counter}.getData();
                </script>";
    $wp_disqus_script_counter++;
    
    if( $echo ){
        echo $output;
    }
    else{
        return $output;
    }
}
/**
 * shortcode claaback function for wdp_threads
 */
function wdp_shortcode_callback( $atts, $content = null ){
    $a = shortcode_atts( array(
                           'days_back' => '7d',
                           'show_threads' => 5,
                           ), $atts
            );
    return wdp_get_threads($a['days_back'], $a['show_threads'] );
}

add_shortcode('wdp_threads', 'wdp_shortcode_callback');
?>
