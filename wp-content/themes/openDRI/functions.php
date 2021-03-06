<?php
/*
Author: Eddie Machado
URL: http://themble.com/bones/

This is where you can drop your custom functions or
just edit things like thumbnail sizes, header images,
sidebars, comments, etc.
*/

// LOAD BONES CORE (if you remove this, the theme will break)
require_once __DIR__ . '/library/bones.php';

// Load MapBox shortcode
require_once __DIR__ . '/mapbox_shortcode.php';

// CUSTOMIZE THE WORDPRESS ADMIN (off by default)
// require_once( 'library/admin.php' );

/*********************
 * LAUNCH BONES
 * Let's get everything up and running.
 *********************/

function bones_ahoy() {
  
  //Allow editor style.
  add_editor_style( get_stylesheet_directory_uri() . '/library/css/editor-style.css' );
  
  // let's get language support going, if you need it
  load_theme_textdomain( 'bonestheme', get_template_directory() . '/library/translation' );
  
  // USE THIS TEMPLATE TO CREATE CUSTOM POST TYPES EASILY
  require_once __DIR__ . '/library/custom-post-type.php';
  
  // launching operation cleanup
  add_action( 'init', 'bones_head_cleanup' );
  // A better title
  add_filter( 'wp_title', 'rw_title', 10, 3 );
  // remove WP version from RSS
  add_filter( 'the_generator', 'bones_rss_version' );
  // remove pesky injected css for recent comments widget
  add_filter( 'wp_head', 'bones_remove_wp_widget_recent_comments_style', 1 );
  // clean up comment styles in the head
  add_action( 'wp_head', 'bones_remove_recent_comments_style', 1 );
  // clean up gallery output in wp
  add_filter( 'gallery_style', 'bones_gallery_style' );
  
  // enqueue base scripts and styles
  add_action( 'wp_enqueue_scripts', 'bones_scripts_and_styles', 999 );
  // ie conditional wrapper
  
  // launching this stuff after theme setup
  bones_theme_support();
  
  // adding sidebars to Wordpress (these are created in functions.php)
  add_action( 'widgets_init', 'bones_register_sidebars' );
  
  // cleaning up random code around images
  add_filter( 'the_content', 'bones_filter_ptags_on_images' );
  // cleaning up excerpt
  add_filter( 'excerpt_more', 'bones_excerpt_more' );
  
} /* end bones ahoy */

// let's get this party started
add_action( 'after_setup_theme', 'bones_ahoy' );


/************* OEMBED SIZE OPTIONS *************/

if ( ! isset( $content_width ) ) {
  $content_width = 680;
}

/************* THUMBNAIL SIZE OPTIONS *************/

// Thumbnail sizes
add_image_size( 'bones-thumb-600', 600, 150, true );
add_image_size( 'bones-thumb-300', 300, 100, true );

/*
to add more sizes, simply copy a line from above
and change the dimensions & name. As long as you
upload a "featured image" as large as the biggest
set width or height, all the other sizes will be
auto-cropped.

To call a different size, simply change the text
inside the thumbnail function.

For example, to call the 300 x 100 sized image,
we would use the function:
<?php the_post_thumbnail( 'bones-thumb-300' ); ?>
for the 600 x 150 image:
<?php the_post_thumbnail( 'bones-thumb-600' ); ?>

You can change the names and dimensions to whatever
you like. Enjoy!
*/

add_filter( 'image_size_names_choose', 'bones_custom_image_sizes' );

function bones_custom_image_sizes( $sizes ) {
  return array_merge( $sizes, array(
	'bones-thumb-600' => __( '600px by 150px' ),
	'bones-thumb-300' => __( '300px by 100px' ),
  ) );
}

/*
The function above adds the ability to use the dropdown menu to select
the new images sizes you have just created from within the media manager
when you add media to your content blocks. If you add more image sizes,
duplicate one of the lines in the array and name it according to your
new image size.
*/

/************* THEME CUSTOMIZE *********************/

/* 
  A good tutorial for creating your own Sections, Controls and Settings:
  http://code.tutsplus.com/series/a-guide-to-the-wordpress-theme-customizer--wp-33722
  
  Good articles on modifying the default options:
  http://natko.com/changing-default-wordpress-theme-customization-api-sections/
  http://code.tutsplus.com/tutorials/digging-into-the-theme-customizer-components--wp-27162
  
  To do:
  - Create a js for the postmessage transport method
  - Create some sanitize functions to sanitize inputs
  - Create some boilerplate Sections, Controls and Settings
*/

function bones_theme_customizer( $wp_customize ) {
  // $wp_customize calls go here.
  //
  // Uncomment the below lines to remove the default customize sections
  
  // $wp_customize->remove_section('title_tagline');
  // $wp_customize->remove_section('colors');
  // $wp_customize->remove_section('background_image');
  // $wp_customize->remove_section('static_front_page');
  // $wp_customize->remove_section('nav');
  
  // Uncomment the below lines to remove the default controls
  // $wp_customize->remove_control('blogdescription');
  
  // Uncomment the following to change the default section titles
  // $wp_customize->get_section('colors')->title = __( 'Theme Colors' );
  // $wp_customize->get_section('background_image')->title = __( 'Images' );
}

add_action( 'wp_trash_post', 'my_trash_post_function', 1, 1 );
function my_trash_post_function( $post_id ) {
  $cdb_api_key = get_option( 'CDB_API_KEY' );
  $url         = "https://opendri.cartodb.com/api/v2/sql?q=";
  $api_bit     = "&api_key=$cdb_api_key";
  
  wp_remote_get( "https://opendri.cartodb.com/api/v2/sql?q=" . urlencode( "UPDATE wp_projects SET visible = false WHERE wp_post_id = " . $post_id . ";" ) . $api_bit );
}

add_action( 'untrash_post', 'custom_restore_function' );
function custom_restore_function( $post_id ) {
  $cdb_api_key = get_option( 'CDB_API_KEY' );
  $url         = "https://opendri.cartodb.com/api/v2/sql?q=";
  $api_bit     = "&api_key=$cdb_api_key";
  
  wp_remote_get( "https://opendri.cartodb.com/api/v2/sql?q=" . urlencode( "UPDATE wp_projects SET visible = true WHERE wp_post_id = " . $post_id . ";" ) . $api_bit );
}

add_action( 'customize_register', 'bones_theme_customizer' );
function save_on_cartodb( $post_id ) {
  
  if ( wp_is_post_revision( $post_id ) ) {
	return;
  }
  $cdb_api_key = get_option( 'CDB_API_KEY' );
  
  if ( $cdb_api_key && $_REQUEST['action'] != 'grunion-contact-form' && $_REQUEST['post_type'] != 'page' ) {
	
	
	$url                = "https://opendri.cartodb.com/api/v2/sql?q=";
	$api_bit            = "&api_key=$cdb_api_key";
	$geodata__lat       = $_REQUEST["_wppl_lat"];
	$geodata__long      = $_REQUEST["_wppl_long"];
	$geodata__title     = $_REQUEST["post_title"];
	$geodata__url_title = strtolower( str_replace( " ", "-", $geodata__title ) );
	$geodata__address   = $_REQUEST['_wppl_address'];
	$geodata__country   = $_REQUEST['_wppl_country'];
	$geodata__c_name    = $_REQUEST['_wppl_country_long'];
	$categories         = $_REQUEST['post_category'];
	$geodata__content   = ( strlen( $_REQUEST['excerpt'] ) > 0 ) ? wp_strip_all_tags( $_REQUEST['excerpt'] ) : wp_strip_all_tags( str_replace( "'", "`", substr( $_REQUEST['content'], 0, 180 ) ) );
	$visibility         = $_REQUEST['visibility'];
	
	//check pillars and regions
	$pillar = [];
	$region = [];
	if ( is_array( $categories ) ) {
	  if ( in_array( 7, $categories ) ) {
		array_push( $pillar, 'community mapping' );
	  }
	  if ( in_array( 6, $categories ) ) {
		array_push( $pillar, 'open data platforms' );
	  }
	  if ( in_array( 8, $categories ) ) {
		array_push( $pillar, 'risk visualization' );
	  }
	  if ( in_array( 9, $categories ) ) {
		array_push( $region, 'africa' );
	  }
	  if ( in_array( 10, $categories ) ) {
		array_push( $region, 'eastasia' );
	  }
	  if ( in_array( 11, $categories ) ) {
		array_push( $region, 'europe' );
	  }
	  if ( in_array( 12, $categories ) ) {
		array_push( $region, 'latam' );
	  }
	  if ( in_array( 13, $categories ) ) {
		array_push( $region, 'middleeast' );
	  }
	  if ( in_array( 14, $categories ) ) {
		array_push( $region, 'nonwp' );
	  }
	  if ( in_array( 15, $categories ) ) {
		array_push( $region, 'southasia' );
	  }
	  if ( in_array( 281, $categories ) ) {
		array_push( $region, 'grp1' );
	  }
	  if ( in_array( 282, $categories ) ) {
		array_push( $region, 'grp2' );
	  }
	  if ( in_array( 283, $categories ) ) {
		array_push( $region, 'grp3' );
	  }
	  if ( in_array( 284, $categories ) ) {
		array_push( $region, 'grp4' );
	  }
	  $pillar = implode( "|", $pillar );
	  $region = implode( "|", $region );
	  // echo '<pre>'.$pillar.'</pre>';
	  // echo '<pre>'.$region.'</pre>';
	}
	
	//check visibility
	if ( $visibility != 'public' ) {
	  $visibility = 'false';
	} else {
	  $visibility = 'true';
	}
	
	// clean
	wp_remote_get( "https://opendri.cartodb.com/api/v2/sql?q=" . urlencode( "UPDATE wp_projects SET visible = false WHERE wp_post_id = " . $post_id . ";" ) . $api_bit );
	if ( $_REQUEST["original_publish"] != 'Update' ) {
	  // insert new row
	  $query = "INSERT INTO wp_projects (wp_post_id, the_geom, name, location, url, pillar, region, iso, description, visible, country_name) VALUES ($post_id, ST_SetSRID(ST_Point($geodata__long, $geodata__lat),4326), '$geodata__title', '$geodata__address', '$geodata__url_title', '$pillar', '$region', '$geodata__country', '$geodata__content', '$visibility', '$geodata__c_name')";
	} else {
	  // check if the post exists and update or insert
	  $query = "UPDATE wp_projects
      SET the_geom = ST_SetSRID(ST_Point($geodata__long, $geodata__lat),4326), name = '$geodata__title', location = '$geodata__address', url = '$geodata__url_title', pillar = '$pillar', region = '$region', iso = '$geodata__country', description = '$geodata__content', visible = '$visibility', country_name = '$geodata__c_name'
      WHERE wp_post_id = $post_id;

      INSERT INTO wp_projects (wp_post_id, the_geom, name, location, url, pillar, region, iso, description, visible, country_name)

      SELECT $post_id, ST_SetSRID(ST_Point($geodata__long, $geodata__lat),4326), '$geodata__title', '$geodata__address', '$geodata__url_title', '$pillar', '$region', '$geodata__country', '$geodata__content', '$visibility', '$geodata__c_name'

      WHERE NOT EXISTS (SELECT 1 FROM wp_projects WHERE wp_post_id=$post_id)";
	}
	$url           .= urlencode( $query ) . $api_bit;
	$response      = wp_remote_get( $url );
	$response_safe = wp_remote_get( "https://opendri.cartodb.com/api/v2/sql?q=UPDATE wp_projects SET description = REPLACE(description, '\`s', '''s') WHERE cartodb_id is not null" . $api_bit );
  }
}

add_action( 'save_post', 'save_on_cartodb', 10, 3 );

function amgenna_change_grunion_success_message( $msg ) {
  global $contact_form_message;
  
  return '<h4>' . 'Thanks for contacting us' . '</h4>' . wp_kses( $contact_form_message, array(
	  'br'         => array(),
	  'blockquote' => array()
	) );
}

add_filter( 'grunion_contact_form_success_message', 'amgenna_change_grunion_success_message' );

function my_login_logo() { ?>
    <style type="text/css">
        .login h1 a {
            background-image: url(<?php echo get_template_directory_uri(); ?>/logo.svg) !important;
        }
    </style>
<?php }

add_action( 'login_enqueue_scripts', 'my_login_logo' );
function my_login_logo_url() {
  return home_url();
}

add_filter( 'login_headerurl', 'my_login_logo_url' );

function my_login_logo_url_title() {
  return 'Open Data for Resilience Initiative';
}

add_filter( 'login_headertitle', 'my_login_logo_url_title' );

add_filter( 'pre_get_posts', 'query_post_type' );
function query_post_type( $query ) {
  if ( is_category() || is_tag() ) {
	$post_type = get_query_var( 'post_type' );
	if ( $post_type ) {
	  $post_type = $post_type;
	} else {
	  $post_type = array( 'post', 'project', 'resource' );
	}
	$query->set( 'post_type', $post_type );
	
	return $query;
  }
}

//remove_filter( 'the_content', 'wpautop' );
//remove_filter( 'the_excerpt', 'wpautop' );
/************* ACTIVE SIDEBARS ********************/

// Sidebars & Widgetizes Areas
function bones_register_sidebars() {
  register_sidebar( array(
	'id'            => 'sidebar1',
	'name'          => __( 'Sidebar 1', 'bonestheme' ),
	'description'   => __( 'The first (primary) sidebar.', 'bonestheme' ),
	'before_widget' => '<div id="%1$s" class="widget %2$s">',
	'after_widget'  => '</div>',
	'before_title'  => '<h4 class="widgettitle">',
	'after_title'   => '</h4>',
  ) );
  
  /*
  to add more sidebars or widgetized areas, just copy
  and edit the above sidebar code. In order to call
  your new sidebar just use the following code:

  Just change the name to whatever your new
  sidebar's id is, for example:

  register_sidebar(array(
	  'id' => 'sidebar2',
	  'name' => __( 'Sidebar 2', 'bonestheme' ),
	  'description' => __( 'The second (secondary) sidebar.', 'bonestheme' ),
	  'before_widget' => '<div id="%1$s" class="widget %2$s">',
	  'after_widget' => '</div>',
	  'before_title' => '<h4 class="widgettitle">',
	  'after_title' => '</h4>',
  ));

  To call the sidebar in your template, you can just copy
  the sidebar.php file and rename it to your sidebar's name.
  So using the above example, it would be:
  sidebar-sidebar2.php

  */
} // don't remove this bracket!


/************* COMMENT LAYOUT *********************/

// Comment Layout
function bones_comments( $comment, $args, $depth ) {
$GLOBALS['comment'] = $comment; ?>
<div id="comment-<?php comment_ID(); ?>" <?php comment_class( 'cf' ); ?>>
    <article class="cf">
        <header class="comment-author vcard">
		  <?php
		  /*
			this is the new responsive optimized comment image. It used the new HTML5 data-attribute to display comment gravatars on larger screens only. What this means is that on larger posts, mobile sites don't have a ton of requests for comment images. This makes load time incredibly fast! If you'd like to change it back, just replace it with the regular wordpress gravatar call:
			echo get_avatar($comment,$size='32',$default='<path_to_url>' );
		  */
		  ?>
		  <?php // custom gravatar call ?>
		  <?php
		  // create variable
		  $bgauthemail = get_comment_author_email();
		  ?>
            <img data-gravatar="http://www.gravatar.com/avatar/<?php echo md5( $bgauthemail ); ?>?s=40"
                 class="load-gravatar avatar avatar-48 photo" height="40" width="40"
                 src="<?php echo get_template_directory_uri(); ?>/library/images/nothing.gif"/>
		  <?php // end custom gravatar call ?>
		  <?php printf( __( '<cite class="fn">%1$s</cite> %2$s', 'bonestheme' ), get_comment_author_link(), edit_comment_link( __( '(Edit)', 'bonestheme' ), '  ', '' ) ) ?>
            <time datetime="<?php echo comment_time( 'Y-m-j' ); ?>"><a
                        href="<?php echo htmlspecialchars( get_comment_link( $comment->comment_ID ) ) ?>"><?php comment_time( __( 'F jS, Y', 'bonestheme' ) ); ?> </a>
            </time>

        </header>
	  <?php if ( $comment->comment_approved == '0' ) : ?>
          <div class="alert alert-info">
              <p><?php _e( 'Your comment is awaiting moderation.', 'bonestheme' ) ?></p>
          </div>
	  <?php endif; ?>
        <section class="comment_content cf">
		  <?php comment_text() ?>
        </section>
	  <?php comment_reply_link( array_merge( $args, array(
		'depth'     => $depth,
		'max_depth' => $args['max_depth']
	  ) ) ) ?>
    </article>
  <?php // </li> is added by WordPress automatically ?>
  <?php
  } // don't remove this bracket!
  
  
  /*
  This is a modification of a function found in the
  twentythirteen theme where we can declare some
  external fonts. If you're using Google Fonts, you
  can replace these fonts, change it in your scss files
  and be up and running in seconds.
  */
  function bones_fonts() {
	wp_enqueue_style( 'googleFonts', '//fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic' );
  }
  
  add_action( 'wp_enqueue_scripts', 'bones_fonts' );
  
  
  // Custom Functions to add SORT BY MODIFIED to post and page editor.
  // POSTS
  // Register the column
  function post_modified_column_register( $columns ) {
	$columns['post_modified'] = __( 'Modified', 'mytextdomain' );
	
	return $columns;
  }
  
  add_filter( 'manage_edit-post_columns', 'post_modified_column_register' );
  
  // Display the column content
  function post_modified_column_display( $column_name, $post_id ) {
	if ( 'post_modified' != $column_name ) {
	  return;
	}
	$post_modified = get_post_field( 'post_modified', $post_id );
	if ( ! $post_modified ) {
	  $post_modified = '' . __( 'undefined', 'mytextdomain' ) . '';
	}
	echo $post_modified;
  }
  
  add_action( 'manage_posts_custom_column', 'post_modified_column_display', 10, 2 );
  
  // Register the column as sortable
  function post_modified_column_register_sortable( $columns ) {
	$columns['post_modified'] = 'post_modified';
	
	return $columns;
  }
  
  add_filter( 'manage_edit-post_sortable_columns', 'post_modified_column_register_sortable' );
  
  
  // PROJECTS
  // Register the column
  function project_modified_column_register( $columns ) {
	$columns['post_modified'] = __( 'Modified', 'mytextdomain' );
	
	return $columns;
  }
  
  add_filter( 'manage_edit-project_columns', 'project_modified_column_register' );
  
  // Display the column content
  function project_modified_column_display( $column_name, $post_id ) {
	if ( 'post_modified' != $column_name ) {
	  return;
	}
	$post_modified = get_post_field( 'post_modified', $post_id );
	if ( ! $post_modified ) {
	  $post_modified = '' . __( 'undefined', 'mytextdomain' ) . '';
	}
	echo $post_modified;
  }
  
  add_action( 'manage_projects_custom_column', 'project_modified_column_display', 10, 2 );
  
  // Register the column as sortable
  function project_modified_column_register_sortable( $columns ) {
	$columns['post_modified'] = 'post_modified';
	
	return $columns;
  }
  
  add_filter( 'manage_edit-project_sortable_columns', 'project_modified_column_register_sortable' );
  
  
  // RESOURCES
  // Register the column
  function resource_modified_column_register( $columns ) {
	$columns['post_modified'] = __( 'Modified', 'mytextdomain' );
	
	return $columns;
  }
  
  add_filter( 'manage_edit-resource_columns', 'resource_modified_column_register' );
  
  // Display the column content
  function resource_modified_column_display( $column_name, $post_id ) {
	if ( 'post_modified' != $column_name ) {
	  return;
	}
	$post_modified = get_post_field( 'post_modified', $post_id );
	if ( ! $post_modified ) {
	  $post_modified = '' . __( 'undefined', 'mytextdomain' ) . '';
	}
	echo $post_modified;
  }
  
  add_action( 'manage_resources_custom_column', 'resource_modified_column_display', 10, 2 );
  
  // Register the column as sortable
  function resource_modified_column_register_sortable( $columns ) {
	$columns['post_modified'] = 'post_modified';
	
	return $columns;
  }
  
  add_filter( 'manage_edit-resource_sortable_columns', 'resource_modified_column_register_sortable' );
  
  
  //PAGES
  // Register the column
  function page_modified_column_register( $columns ) {
	$columns['page_modified'] = __( 'Modified', 'mytextdomain' );
	
	return $columns;
  }
  
  add_filter( 'manage_edit-page_columns', 'page_modified_column_register' );
  
  // Display the column content
  function page_modified_column_display( $column_name, $page_id ) {
	if ( 'page_modified' != $column_name ) {
	  return;
	}
	$page_modified = get_post_field( 'post_modified', $page_id );
	if ( ! $page_modified ) {
	  $page_modified = '' . __( 'undefined', 'mytextdomain' ) . '';
	}
	echo $page_modified;
  }
  
  add_action( 'manage_pages_custom_column', 'page_modified_column_display', 10, 2 );
  
  // Register the column as sortable
  function page_modified_column_register_sortable( $columns ) {
	$columns['page_modified'] = 'page_modified';
	
	return $columns;
  }
  
  add_filter( 'manage_edit-page_sortable_columns', 'page_modified_column_register_sortable' );
  
  // end SORT BY MODIFIED
  
  /* DON'T DELETE THIS CLOSING TAG */ ?>
