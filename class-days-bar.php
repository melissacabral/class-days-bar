<?php
/*
Plugin Name: Class days taxonomy and progress bar
Description: Adds the "days" taxonomy, as well as handles the display of the "days" bar
Author: Melissa Cabral
Version: 0.1
*/
/**
 * Style it
 */
add_action( 'wp_enqueue_scripts','ra_days_style');
 function ra_days_style(){
 	wp_enqueue_style( 
 		'ra-days-bar-style', 
 		plugins_url( 'class-days-bar.css', __FILE__ ) 
 	);
 }
/**
 * Set up the post type in the admin panel
 * @since 0.1
 */
add_action( 'init', 'ra_register_types_taxos' );
function ra_register_types_taxos(){
	register_taxonomy( 'class-day', 'post', array(
		'hierarchical' => true, //act like categories
		'rewrite' => array( 'slug' => 'class-day' ),
		'labels' => array( 
			'name' => 'Class Days',
			'singular-name' => 'Class Day',
			'add_new_item' => 'Add New Day of Class',
			),
		) );
}
/**
 * Add 25 days as terms of the taxo
 * @since 0.1
 */
function ra_create_days(){	
	$days = array(
		array('1', 'day-01'),
		array('2', 'day-02'),
		array('3', 'day-03'),
		array('4', 'day-04'),
		array('5', 'day-05'),
		array('6', 'day-06'),
		array('7', 'day-07'),
		array('8', 'day-08'),
		array('9', 'day-09'),
		array('10', 'day-10'),
		array('11', 'day-11'),
		array('12', 'day-12'),
		array('13', 'day-13'),
		array('14', 'day-14'),
		array('15', 'day-15'),
		array('16', 'day-16'),
		array('17', 'day-17'),
		array('18', 'day-18'),
		array('19', 'day-19'),
		array('20', 'day-20'),
		array('21', 'day-21'),
		array('22', 'day-22'),
		array('23', 'day-23'),
		array('24', 'day-24'),
		array('25', 'day-25'),
		);
	foreach($days as $day){
		wp_insert_term( $day[0], 'class-day', array(			
			'slug' => $day[1],			
			)
		);
	}
}
/**
 * Flush Rewrite Rules - Fix 404 errors when the plugin activates, also set up the 25 days on activation
 * @since 0.1
 */
function ra_flush(){
	ra_register_types_taxos();
	ra_create_days();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ra_flush' );
/**
 * display the 25 days of class in a nifty bar
 * @since 0.1
 */
function ra_days_bar($before = 'Day: '){
	$taxonomy = 'class-day';
	$args = array(
		'taxonomy'     	=> $taxonomy,
		'orderby'     	=> 'slug',
		'show_count'   	=> 0,
		'pad_counts'  	=> 0,
		'hierarchical' 	=> 0,
		'title_li'     	=> '',
		'hide_empty'	=> 0,
	);
	?>
	<ul class="ra-days-bar">
		<li class="title-li"><?php echo $before; ?> </li>
	<?php 
	//get all the terms in the taxonomy
	$terms = get_terms($taxonomy, $args);
	//this was necessary to run again so i have IDs for the tax query.
	//TODO: see if this can be dome with only one use of "get_terms"
	$terms_ids = get_terms( 
		$taxonomy, array(
    'hide_empty' => 0,
    'fields' => 'ids'
) );
	//TODO:  add a counter here so the CSS width is based on the number of terms.
	//$posts = get_posts();
	//fetch the latest post so we can show progress bar
	$latest_posts = wp_get_recent_posts( array(
			'numberposts' => 1,
			'post_status' => 'publish',
			'post_type' =>'post',
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'field' => 'id',
					'terms' => $terms_ids,
					
				)
			),
		) );
	foreach($latest_posts as $latest_post){
		 $latest_post_id = $latest_post['ID'];
	}
		//loop through each term (day)		
		foreach ($terms as $term) {
			echo '<li class="bar-item ';
			if(is_tax($taxonomy, $term->slug)){
				echo ' active';
			}
			if( has_term($term->slug, $taxonomy, $latest_post_id) ){
				echo ' current';
			}
			echo '"">';
			//if the term has posts assigned, link to the archive otherwise, no link necessary
			if($term->count){
				echo '<a href="'.get_term_link( $term->slug, $taxonomy ).'" title="View all posts from Day '. $term->name. '">';
			}
			//show the name
			echo  $term->name ;
			//close the link if needed
			if($term->count){
				echo '</a>';
			}
			echo '</li>';
		}
		
	?>
	</ul>
	<?php
}
/**
 * Sidebar Widget
 */
add_action( 'widgets_init', 'rad_register_days_bar_widget' );
function rad_register_days_bar_widget(){
	register_widget( 'Rad_Days_Bar_Widget' );
}
/**
 * Widget Class definition
 */
class Rad_Days_Bar_Widget extends WP_Widget{
	function Rad_Days_Bar_Widget(){
		$widget_settings = array(
			'classname' => 'days-bar-widget',
			'description' => 'Shows the days bar in a widget.',
		);
		$control_settings = array(
			'id-base' => 'days-bar-widget',
		);
		//WP_Widget(id-base, Title, widget settings, control settings)
		$this->WP_Widget('days-bar-widget', 'Days Bar Widget', $widget_settings, 
			$control_settings);
	}
	function widget( $args, $instance ){
		extract($args);
		$title = $instance['title'];
		//make the title work with filter hook
		$title = apply_filters( 'widget_title', $title );
		//begin output
		echo $before_widget;
		echo $before_title . $title . $after_title;
		?>
		<?php ra_days_bar(''); ?>
		<?php
		echo $after_widget;
	}
	function update( $new_instance, $old_instance ){
		$instance = array();
		$instance['title'] = wp_filter_nohtml_kses( $new_instance['title'] );
		return $instance;
	}
	function form( $instance ){
		$defaults = array(
			'title' => 'Class Days',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label>Title:</label>
			<input type="text" class="widefat"
				name="<?php echo $this->get_field_name('title'); ?>" 
				id="<?php echo $this->get_field_id('title'); ?>" 
				value="<?php echo $instance['title'] ?>">
		</p>
	
		<?php
	}
}