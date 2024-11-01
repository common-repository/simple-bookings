<?php
/*
Plugin Name: Simple Bookings
Description: Provides a simple booking request form workflow approval system for your intranet.
Plugin URI: http://www.simpleintranet.org
Description: Provides simple booking request workflow functionality.
Version: 1.0
Author: Simple Intranet
Author URI: http://www.simpleintranet.org
*/

function si_booking_posts_for_current_author($query) {

if( !current_user_can( 'administrator','editor' ) ){
	if($query->is_admin) {

		global $user_ID;
		$query->set('author',  $user_ID);
	}
	return $query;
}
}
add_filter('pre_get_posts', 'si_booking_posts_for_current_author');

function si_custom_post_booking() {
	$label_all = array(
		'name'               => _x( 'Booking Requests', 'post type general name' ),
		'singular_name'      => _x( 'Booking', 'post type singular name' ),
		'add_new'            => _x( 'New Request', 'request' ),
		'add_new_item'       => __( 'Booking Request Form' ),
		'edit_item'          => __( 'Edit Request' ),
		'new_item'           => __( 'New Request' ),		
		'view_item'          => __( 'View Request' ),
		'search_items'       => __( 'Search Requests' ),
		'not_found'          => __( 'No requests found' ),
		'not_found_in_trash' => __( 'No request found in the Trash' ), 
		'parent_item_colon'  => '',
		'menu_name'          => 'Bookings'
	);
	if( current_user_can( 'administrator' ) ){
	$label_subscriber= array( 'all_items'          => __( 'All Requests' ),);
	}
	else {
	$label_subscriber = array();	
	}
	$labels = array_merge((array)$label_all, (array)$label_subscriber);
	
	$args = array(
		'labels'        => $labels,
		

		'supports' => 'revisions',
		'description'   => 'Displays booking requests and approval status.',
		'public'        => true,
		'menu_position' => 5,
		'supports'      => array( 'title' ),
		'has_archive'   => true,	
	    'rewrite' => array('slug' => 'bookings','with_front' => FALSE),
		'exclude_from_search'=> true,
		'capability_type' => 'booking',
		'capabilities' => array(
				'read_post' => 'read_booking',
				'edit_post' => 'edit_booking',
				'edit_posts' => 'edit_bookings',
				'publish_posts' => 'publish_bookings',	
				'delete_post' => 'delete_booking',
				'delete_posts' => 'delete_bookings'				
			),	
	);
	register_post_type( 'booking', $args );	
		flush_rewrite_rules();	
		
// ADD Capabilities
  $caps = array(
		'read_booking',
		'edit_booking',
		'edit_bookings',
		'publish_bookings',	
		'delete_booking',
		'delete_bookings'	     
  );
  $roles = array(
    get_role( 'administrator' ),
    get_role( 'editor' ),
  );
  foreach ($roles as $role) {
    foreach ($caps as $cap) {
      $role->add_cap( $cap );
    }
  }
  // ADD Capabilities for subscribers
  $caps2 = array(  	
   	'publish_bookings', 	
  );
  $roles2 = array(
    get_role( 'subscriber' ),
     );
  foreach ($roles2 as $role2) {
    foreach ($caps2 as $cap2) {
      $role2->add_cap( $cap2 );
    }
  }
  
}
add_action( 'init', 'si_custom_post_booking' );


// Initialize the metabox class

function wpb_booking_initialize_cmb_meta_boxes() {
	if ( ! class_exists( 'cmb_Meta_Box' ) )
		require_once(plugin_dir_path( __FILE__ ) . 'init.php');
		global $current_user;
		$current_user = wp_get_current_user();
		//add_user_meta( $current_user->ID, 'approver_name', '');	
		}

add_action( 'init', 'wpb_booking_initialize_cmb_meta_boxes', 9999 );

// Add Custom Columns

add_filter( 'manage_edit-booking_columns', 'my_edit_booking_columns' ) ;

function my_edit_booking_columns( $columns ) {

	$columns = array(
		'cb' => '<input type="checkbox" />',
		'title' => __( 'Title' ),
		'employee' => __( 'Author' ),
		'booking_type' => __( 'Description' ),				
		'start_date' => __( 'Start Date' ),
		'start_time' => __( 'Start Time' ),
		'end_date' => __( 'End Date' ),
		'end_time' => __( 'End Time' ),
		'approval' => __( 'Approver' ),
		'approved' => __( 'Status' ),
		'date' => __( 'Request Date' ),	
	);

	return $columns;
}

add_action( 'manage_booking_posts_custom_column', 'my_manage_booking_columns', 10, 2 );


function my_manage_booking_columns( $column, $post_id ) {
	global $post,$parent;
	
	switch( $column ) {
		case 'employee' :
		/* Get the post meta. */
		the_author();
		break;
	
		case 'booking_type' :		
		echo get_post_meta($post_id, '_simple_booking_type', true);	
		break;	
		
		case 'start_date' :
		echo get_post_meta($post_id, '_simple_start_date', true);			
		break;	
		
		case 'start_time' :
		echo get_post_meta($post_id, '_simple_start_time', true);			
		break;	
		
		case 'end_date' :
		echo get_post_meta($post_id, '_simple_end_date', true);			
		break;	
		
		case 'end_time' :
		echo get_post_meta($post_id, '_simple_end_time', true);			
		break;	
		
		case 'approval' :
		global $post,$approver_name;
		$approver_id= get_post_meta($post_id, '_simple_approver', true);	
		$user_info = get_userdata($approver_id);
		$approver_name = $user_info->display_name;
		echo $approver_name;
		$current_user = wp_get_current_user();
		update_user_meta($current_user->ID, 'approver_name', $approver_name);		 
		break;	
		
		case 'approved' :
		echo get_post_meta($post_id, '_simple_approved', true);			
		break;	
	}
}

//Add Meta Boxes

function wpb_booking_metaboxes( $meta_boxes ) {
	$prefix = '_simple_'; // Prefix for all fields
	if ( current_user_can('administrator','editor') ) {
$approver_box = array(
				'name'    => 'Approved?',
				'desc'    => 'Approval status of your request.',
				'id'      => $prefix . 'approved',
				'type'     => 'select',		
				'options' => array(
					array( 'name' => 'Pending', 'value' => 'Pending', ),
					array( 'name' => 'Approved', 'value' => 'Approved', ),
					array( 'name' => 'Denied', 'value' => 'Denied', ),
					array( 'name' => 'Discuss', 'value' => 'Discuss', ),
				),
			);
	}
	if ( !current_user_can('administrator','editor') ) {
$approver_box = array(
				'name'    => 'Approved?',
				'desc'    => 'Approval status of your request.',
				'id'      => $prefix . 'approved',
				'type'     => 'select',		
				'options' => array(
					array( 'name' => 'Pending', 'value' => 'Pending', ),					
				),
			);
	}
	$meta_boxes[] = array(
		'id' => 'booking_details',
		'title' => 'Booking Request Details',
		'pages' => array('booking'), // post type
		'context' => 'normal',
		'priority' => 'high',
		'show_names' => true, // Show field names on the left
		'fields' => array(
			array(
				'name' => 'Description:',
				'desc' => 'Please describe your booking.',
				'id' => $prefix . 'booking_type',
				'type' => 'text'
			),
			array(
				'name' => 'Start date:',
				'desc' => 'Click in the box to select the start date.',
				'id' => $prefix . 'start_date',
				'type' => 'text_date'
			),
				array(
				'name' => 'Start time:',
				'desc' => 'Click in the box to select the start time.',
				'id' => $prefix . 'start_time',
				'type' => 'text_time'
			),
			array(
				'name' => 'End date:',
				'desc' => 'Click in the box to select the end date.',
				'id' => $prefix . 'end_date',
				'type' => 'text_date'
			),
				array(
				'name' => 'End time:',
				'desc' => 'Click in the box to select the end time.',
				'id' => $prefix . 'end_time',
				'type' => 'text_time'
			),
			array(
				'name'    => 'Approval by:',
				'desc'    => 'Person who approves your request.',
				'id'      => $prefix . 'approver',
				'type'     => 'selectapprover',				
			),
			$approver_box,	
			
		),
	);

	return $meta_boxes;
	
}

add_filter( 'cmb_meta_boxes', 'wpb_booking_metaboxes' );

// Add custom Approver pull down selection
add_action( 'cmb_render_selectapprover', 'si_booking_render_approver', 10, 2 );
function si_booking_render_approver( $field, $meta ) {
wp_dropdown_users(array('name' => $field['id'],'id'=>$meta,'selected'=>$meta));
}


// Save metabox data
function si_booking_save_my_metadata($ID = false, $post = false)
{
    if($post->post_type != 'booking')
        return;
    update_post_meta($ID, '_simple_approver', $_POST['_simple_approver']);
	if ( current_user_can('administrator','editor') ) {
 update_post_meta($ID, '_simple_approved', $_POST['_simple_approved']);
	}
	if ( !current_user_can('administrator','editor') ) {
		update_post_meta($ID, '_simple_approved', 'Pending');
	}
	 $author_id=$post->post_author;
  update_post_meta($ID, '_simple_author', get_the_author_meta( 'display_name', $author_id ));
}

add_action('save_post', 'si_booking_save_my_metadata');


//Notify Authors when Article Published
function si_booking_email($post) {

$post = get_post($post_id);
if ( ! empty( $_POST['post_type'] ) && 'booking' == $_POST['post_type'] ) {
$author_id=$post->post_author; 

$admin_email = get_option('admin_email'); 
$website_name =get_option('blogname');
$post_author = esc_attr( get_the_author_meta( 'display_name', $author_id ) );
$post_author_email =esc_attr( get_the_author_meta( 'user_email', $author_id ) );

$headers = 'From: '.$post_author.'<'.$post_author_email.'>' . "\r\n";
$headers2 = 'From: '.$website_name.'<'.$admin_email.'>' . "\r\n";

$post_title = html_entity_decode(get_the_title($post),ENT_QUOTES,'UTF-8');
$p=$post_id;
if ($p==''){
$p=$post->ID;
}
  if( $_POST ) {
        update_post_meta( $post->ID, '_simple_approver', $_POST['_simple_approver'] );
        update_post_meta( $post->ID, '_simple_approved', $_POST['_simple_approved'] );
    }
	
$meta_approval_status = get_post_meta($p, '_simple_approved', true);
$meta_approver = get_post_meta($p, '_simple_approver', true);

$app_name = get_the_author_meta('display_name',$meta_approver);
$app_email = get_the_author_meta('user_email',$meta_approver);

$booking_url = admin_url().'post.php?post='.$p.'&action=edit';

$message = "Hi ".$post_author.",
Your booking request ".$post_title." has been sent.
";

if ($app_name!=''){
$message .= $app_name." has been sent an e-mail to approve your request.
" ;
}
if ($meta_approval_status!=''){
$message .= "Current approval status: ".$meta_approval_status." 
";
}
if ($approver_name!=''){
$message .= "Person approving: ".$app_name;
}

$message2 = "Booking request URL: ".$booking_url." 
submitted by: ".$post_author." 
";
if ($meta_approval_status!=''){
$message2 .= "Current approval status: ".$meta_approval_status." 
";
}
if ($approver_name!=''){
$message2 .= "Person approving: ".$approver_name;
}
wp_mail($post_author_email, "Your booking request update has been submitted", $message);
wp_mail($app_email, "Please review this booking request update", $message2, $headers2);

} // Booking Post Types Only

}
add_action('transition_post_status','si_booking_email'); //publish_booking


// Change Title text box default value
function change_booking_default_title( $title ){
     $screen = get_current_screen();
 
     if  ( 'booking' == $screen->post_type ) {
          $title = 'Create a unique title for your booking request here.';
     } 
     return $title;
}
add_filter( 'enter_title_here', 'change_booking_default_title' );

add_filter( 'gettext', 'si_booking_change_publish_button', 10, 2 );

// Rename publish button for booking post type only
function si_booking_change_publish_button( $translation, $text ) {
if ( $text == 'Publish' && !empty($_GET['post_type']) && $_GET['post_type']=='booking' )
    return 'Submit Request';

return $translation;
}
add_action('init', 'kses_init');
// Add shortcode to front end for booking input

add_shortcode( 'booking', 'booking_shortcode' );

function booking_shortcode() {
/**
 * Enqueue the date picker
 */
function enqueue_date_picker(){
                wp_enqueue_script(
			'field-date-js',
			'js/Field_Date.js',
			array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
			time(),
			true
		);	
		wp_enqueue_style( 'jquery-ui-datepicker' );
}	
if(!empty($_POST['booking2']) && $_POST['booking2']=="submit" && !empty( $_POST['action2'] )) {
echo "<font color=\"red\">Thanks for submitting your booking request!</font><br>";
}
?>
<form method="post" name="booking_form" action="" id="booking_form" >
Title of booking:<br /> <input type="text" name="title2" id="title2" value="" /><br />
Description:<br /> <textarea name="_simple_booking_type" id="_simple_booking_type" rows="5" cols="70"></textarea><br />
Start date: <input type="date" name="_simple_start_date[datepicker]" id="cmb_datepicker" class="cmb_text_small cmb_datepicker"/><br />
Start time: 
<?php 

$start =0;
$end = 86400;
echo '<select name="_simple_start_time">';
for ($i = $start; $i <= $end; $i += 600)
{
echo '<option>' . date('h:i A', $i);
}
echo '</select><br>';
?>
End date: <input type="date" name="_simple_end_date[datepicker]" id="cmb_datepicker"  class="cmb_text_small cmb_datepicker"/><br />
End time: 
<?php 

$start =0;
$end = 86400;
echo '<select name="_simple_end_time">';
for ($i = $start; $i <= $end; $i += 600)
{
echo '<option>' . date('h:i A', $i);
}
echo '</select><br>';
?>
Approver: <?php wp_dropdown_users(); ?>
<input type="hidden" name="_simple_approved" id="_simple_approved" value="Pending" /><br />
<input type="hidden" name="booking2" id="booking2" value="submit" />
<input type="hidden" name="action2" value="new_booking" />
<input type="submit" value="Submit Request">
<?php wp_nonce_field( 'new_booking' ); ?>
</form>
<?php 
}
function simple_booking_add_post(){
global $post; // ADDED
$post = get_post($post_id); // ADDED
if(!empty($_POST['booking2']) && $_POST['booking2']=="submit" && !empty( $_POST['action2'] )) {
$title2     = $_POST['title2'];
$booking_type = $_POST['_simple_booking_type'];  
$booking_start = $_POST['_simple_start_date']['datepicker']; 
$booking_start =date('m\/d\/Y',strtotime($booking_start));
$booking_start_time = $_POST['_simple_start_time']; 
$booking_end = $_POST['_simple_end_date']['datepicker']; 
$booking_end =date('m\/d\/Y',strtotime($booking_end));
$booking_end_time = $_POST['_simple_end_time']; 
$booking_approver = $_POST['user']; 
$booking_approved = $_POST['_simple_approved'];

$current_user = wp_get_current_user();
$author_id = $current_user->ID;
$booking_author = esc_attr( get_the_author_meta( 'display_name', $author_id  ) );
$booking_author_email = esc_attr( get_the_author_meta( 'user_email', $author_id  ) );

//the array of arguments to be inserted with wp_insert_post

$new_post = array(
'post_title'    => $title2,
'post_type'     =>'booking',
'post_status'   => 'publish'          
);

//insert the the post into database by passing $new_post to wp_insert_post
$pid = wp_insert_post($new_post);

//we now use $pid (post id) to help add our post meta data
add_post_meta($pid, '_simple_booking_type', $booking_type, true);
add_post_meta($pid, '_simple_start_date', $booking_start, true);
add_post_meta($pid, '_simple_start_time', $booking_start_time, true);
add_post_meta($pid, '_simple_end_date', $booking_end, true);
add_post_meta($pid, '_simple_end_time', $booking_end_time, true);
add_post_meta($pid, '_simple_approver', $booking_approver, true);
add_post_meta($pid, '_simple_approved', $booking_approved, true);
add_post_meta($pid, '_simple_author', $booking_author, true);
add_post_meta($pid, '_simple_author_email', $booking_author_email, true);

// notify user submitted and person approving
$post = get_post($post_id);
if ( ! empty( $_POST['action2'] ) && 'new_booking' == $_POST['action2'] ) {
$author_id=$post->post_author; 

$admin_email = get_option('admin_email'); 
$website_name =get_option('blogname');

$p=$post_id;
if ($p==''){
$p=$post->ID;
}
$meta_approval_status = get_post_meta($p, '_simple_approved', true);
$meta_approver = get_post_meta($p, '_simple_approver', true);

$app_name = get_the_author_meta('display_name',$booking_approver);
$app_email = get_the_author_meta('user_email',$booking_approver);

$headers = 'From: '.$booking_author.'<'.$booking_author_email.'>' . "\r\n";
$headers2 = 'From: '.$website_name.'<'.$admin_email.'>' . "\r\n";

$booking_url = admin_url().'edit.php?post_type=booking';

$message = "Hi ".$booking_author.",
Your booking request ".$title2." has been sent.
";
if ($app_name!=''){
$message .= $app_name." has been sent an e-mail to approve your request.
" ;
}
if ($meta_approval_status!=''){
$message .= "Current approval status: ".$meta_approval_status." 
";
}
if ($approver_name!=''){
$message .= "Person approving: ".$app_name;
}

$message2 = "Booking request URL: ".$booking_url." 
";
if ($booking_author!=''){
$message2 .= "submitted by: ".$booking_author." 
";
}
if ($meta_approval_status!=''){
$message2 .= "Current approval status: Pending. 
";
}
if ($app_name!=''){
$message2 .= "Person approving: ".$app_name;
}
wp_mail($booking_author_email, "Your booking request update has been submitted", $message, $headers); 
wp_mail($app_email, "Please review this booking request update", $message2, $headers2);
// end of email stuff
}
}
}

add_action('init','simple_booking_add_post');

function simple_booking_custom_help_tab() {
	global $post_ID;
	$screen = get_current_screen();

	if( isset($_GET['post_type']) ) $post_type = $_GET['post_type'];
	else $post_type = get_post_type( $post_ID );

	if( $post_type == 'booking' ) :

		$screen->add_help_tab( array(
			'id' => 'booking_custom_id', //unique id for the tab
			'title' => 'Simple Booking Help', //unique visible title for the tab
			'content' => '<h3>Adding A Form To Front-end</h3><p>To add a booking request form to the front-end, insert the shortcode <strong>[booking]</strong> into a post or page.</p>',  //actual help text
		));

	endif;

}

add_action('admin_head', 'simple_booking_custom_help_tab');