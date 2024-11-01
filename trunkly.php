<?php
/*
Plugin Name: Trunkly
Plugin URI: http://room5.net/2011/01/trunkly
Description: A widget to display your latest Trunk.ly links, and show them in an overlay
Version: 1.2
Author: Matt Bannon
Author URI: http://ttam.org/
Stable tag: 1.2
License: GPL2
*/

add_action( 'widgets_init', 'trunkly_init' );
function trunkly_init() { register_widget( 'Trunkly' ); }

class Trunkly extends WP_Widget {
	function Trunkly() {
		$options  = array( 'classname' => 'trunkly', 'description' => 'Latest Trunk.ly links.' );
		$controls = array( 'width' => 300, 'height' => 350, 'id_base' => 'trunkly' );
		$this->WP_Widget( 'trunkly', 'Trunkly', $options, $controls );
	}

	function widget( $args, $instance ) {
		extract( $args );
		$cacheDir = str_replace(basename(__FILE__), '', __FILE__).'cache';

		$perms = substr(sprintf('%o', fileperms($cacheDir)), -4);

		$title    = apply_filters('widget_title', $instance['title'] );
		$api_key  = $instance['api_key'];
		$username = $instance['username'];
		$limit    = $instance['limit'];

		$cacheFile = $cacheDir . '/' . substr($api_key, 0, 4) . $username;
		$output = $before_widget . $before_title . $title . $after_title . '<ul id="trunklylinks">';
		if( file_exists( $cacheFile ) && ((time()-filemtime($cacheFile))<900) ):
			$data = file_get_contents( $cacheFile );
		else:
			$data = $this->getFromUrl( $api_key, $username, $cacheFile );
		endif;
		
		$data = json_decode( $data );
		$links = $data->links;

		for($i=0;$i<$limit;$i++)
		{
			$link = $links[$i];
			$output .= '<li><a href="'. $link->url .'">'. $link->title .'</a></li>';	
		}

		$output .= '</ul>' . $after_widget;
		echo $output;
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['api_key'] = strip_tags( $new_instance['api_key'] );
		$instance['username'] = strip_tags( $new_instance['username'] );
		$instance['limit'] = (int) $new_instance['limit'];

		return $instance;
	}

	function form( $instance ) {
		$defaults = array(
			'title' => 'Trunk.ly',
			'api_key' => '0000-00000000-0000-0000-0000-000000000000',
			'username' => '',
			'limit' => 10
		);

		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'api_key' ); ?>">API Key: <small>(see http://trunk.ly/settings/profile/)</small></label>
			<input id="<?php echo $this->get_field_id( 'api_key' ); ?>" name="<?php echo $this->get_field_name( 'api_key' ); ?>" value="<?php echo $instance['api_key']; ?>" style="width:100%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'username' ); ?>">Username (leave blank for your own links):</label>
			<input id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo $instance['username']; ?>" style="width:100%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>">Number of links:</label>

			<select id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" class="widefat" style="width:100%;">
			<?php $select = '';
			for($i=1;$i<21;$i++):
				$s = ($i==$instance['limit']) ? ' selected="selected"' : '';
				$select .= '<option'.$s.' value="'.$i.'">'.$i.'</option>';
			endfor;
			echo $select; ?>
			</select>
		</p>
<?php
	}

	function getFromUrl( $api_key, $username, $cacheFile ) {
		$url = 'http://trunk.ly/api/v1/links/';
		if(''!=trim($username)) $url .= $username.'/';
		$url .= '?api_key='.$api_key;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; trunkly-for-wordpress/0.1; +ttam.org/)');
		$data = curl_exec( $curl );

		file_put_contents( $cacheFile, $data );
		return $data;
	}
}

function trunkly_scripts( ) {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'trunklyjs', '/wp-content/plugins/trunkly/trunkly.js', 'jquery' );
	wp_enqueue_script( 'colorbox', '/wp-content/plugins/trunkly/jquery.colorbox-min.js', 'jquery' );

	wp_enqueue_style( 'colorbox', '/wp-content/plugins/trunkly/colorbox.css' );
}

function trunkly_footer( ) {
	$script = <<<JS
	<script type="text/javascript">
	jQuery(function($){
		$( '#trunklylinks a' ).each(function(){
			$( this ).click(function(e){
				trunkly_embed( $(this).attr('href') );
				e.preventDefault( );
			});
		});
	});
	</script>
JS;
	echo $script;
}

add_action('init', 'trunkly_scripts');
add_action('wp_footer', 'trunkly_footer');