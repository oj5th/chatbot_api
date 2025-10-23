<?php
add_theme_support('title-tag');
add_post_type_support('page', 'excerpt');
add_filter('show_admin_bar', '__return_false');

add_action('admin_init', 'posts_order');
function posts_order(){
	add_post_type_support('post', 'page-attributes');
}

function taxhero_remove_version() {
	return '';
}
add_filter('the_generator', 'taxhero_remove_version');

remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
remove_action('template_redirect', 'rest_output_link_header', 11, 0);
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_shortlink_wp_head');

function taxhero_cleanup_query_string($src){ 
	$parts = explode('?', $src); 
	return $parts[0]; 
} 
add_filter('script_loader_src', 'taxhero_cleanup_query_string', 15, 1); 
add_filter('style_loader_src', 'taxhero_cleanup_query_string', 15, 1);

function remove_the_wpautop_function() {
	remove_filter('the_content', 'wpautop');
	remove_filter('the_excerpt', 'wpautop');
}
add_action('after_setup_theme', 'remove_the_wpautop_function');

add_theme_support('post-thumbnails');
add_image_size('taxhero-blog-full', 1140, 640, ['center', 'center']);
add_image_size('taxhero-blog', 960, 640, ['center', 'center']);

add_action('widgets_init', 'taxhero_sidebars');
function taxhero_sidebars(){
	register_sidebar([
		'name'          => esc_html__('taxhero Main Sidebar', 'taxhero'),
		'id'            => 'taxhero-sidebar-1',
		'description'   => esc_html__('Drag and drop your widgets here.', 'taxhero'),
		'before_widget' => '<div id="%1$s" class="widget %2$s widget-wrapper">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	]);
}

function taxhero_body_classes($classes) {
	if (!is_active_sidebar('taxhero-sidebar-1')) {
		$classes[] = 'no-sidebar';
	}
	if (!is_active_sidebar('taxhero-sidebar-shop')) {
		$classes[] = 'no-sidebar-shop';
	}
	if (!is_active_sidebar('taxhero-sidebar-footer1') && !is_active_sidebar('taxhero-sidebar-footer2') && !is_active_sidebar('taxhero-sidebar-footer3')) {
		$classes[] = 'no-sidebar-footer';
	}
	return $classes;
}
add_filter('body_class', 'taxhero_body_classes');

add_shortcode('br_spacer', function($atts, $content) {
	$a = shortcode_atts([
		'class'  => 'spacer-1',
		'height' => '10px',
	], $atts);
	return '<div class="br-spacer ' . esc_attr($a['class']) . '" style="height: ' . esc_attr($a['height']) . '">' . $content . '</div>';
});

function custom_jetpack_related_posts_thumbnail_size($thumbnail_size){
	$thumbnail_size['width'] = 230;
	return $thumbnail_size;
}
add_filter('jetpack_relatedposts_filter_thumbnail_size', 'custom_jetpack_related_posts_thumbnail_size');

function ziptax_blog_calc_inclusion() {
	ob_start();
	include get_template_directory() . '/ziptax-calc-blog-plugin.php';
	return ob_get_clean();
}
add_shortcode('ziptax_calc_blog_plugin_php', 'ziptax_blog_calc_inclusion');

function ziptax_ajax_calc_inclusion() {
	ob_start();
	include get_template_directory() . '/shared/ziptax-api-ajax.php';
	return ob_get_clean();
}
add_shortcode('ziptax_calc_ajax_js', 'ziptax_ajax_calc_inclusion');

function embedable_sales_tax_calc_inclusion() {
	ob_start();
	include get_template_directory() . '/embedable-sales-tax-calc.php';
	return ob_get_clean();
}
add_shortcode('embedable_sales_tax_calc_php', 'embedable_sales_tax_calc_inclusion');

function embeddable_sales_tax_map_inclusion() {
	ob_start();
	include get_template_directory() . '/embeddable-sales-tax-map.php';
	return ob_get_clean();
}
add_shortcode('embeddable_sales_tax_map_php', 'embeddable_sales_tax_map_inclusion');

function embeddable_sales_tax_map_desktop_inclusion() {
	ob_start();
	include get_template_directory() . '/embeddable-sales-tax-map-desktop.php';
	return ob_get_clean();
}
add_shortcode('embeddable_sales_tax_map_desktop_php', 'embeddable_sales_tax_map_desktop_inclusion');

function embeddable_home_testimonial_inclusion() {
	ob_start();
	include get_template_directory() . '/embeddable-home-testimonial.php';
	return ob_get_clean();
}
add_shortcode('embeddable_home_testimonial_php', 'embeddable_home_testimonial_inclusion');

function add_duplicate_link_to_page_row_actions($actions, $post) {
	if ($post->post_type === 'page') {
		$duplicate_link = wp_nonce_url(
			admin_url('admin.php?action=duplicate_post_as_draft&post=' . $post->ID),
			basename(__FILE__),
			'duplicate_nonce'
		);
		$actions['duplicate'] = '<a href="' . esc_url($duplicate_link) . '" title="Duplicate this page" rel="permalink">Duplicate</a>';
	}
	return $actions;
}
add_filter('page_row_actions', 'add_duplicate_link_to_page_row_actions', 10, 2);

function duplicate_post_as_draft() {
	if (
		!isset($_GET['post']) ||
		!isset($_GET['duplicate_nonce']) ||
		!wp_verify_nonce($_GET['duplicate_nonce'], basename(__FILE__))
	) {
		wp_die('Security check failed.');
	}

	$post_id = (int) $_GET['post'];
	$post = get_post($post_id);

	if ($post) {
		$new_post = [
			'post_title'   => $post->post_title . ' (Copy)',
			'post_content' => $post->post_content,
			'post_status'  => 'draft',
			'post_type'    => $post->post_type,
			'post_author'  => get_current_user_id(),
		];

		$new_post_id = wp_insert_post($new_post);

		// ✅ Fire meta + featured image copy
		do_action('dp_duplicate_post', $new_post_id, $post);

		wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
		exit;
	} else {
		wp_die('Page not found.');
	}
}
add_action('admin_action_duplicate_post_as_draft', 'duplicate_post_as_draft');

function custom_duplicate_post_meta($new_post_id, $post) {
	$thumbnail_id = get_post_thumbnail_id($post->ID);
	if ($thumbnail_id) {
		set_post_thumbnail($new_post_id, $thumbnail_id);
		update_post_meta($new_post_id, '_thumbnail_id', $thumbnail_id);
	}

	wp_update_post([
		'ID' => $new_post_id,
		'post_parent' => $post->post_parent,
	]);

	$meta = get_post_meta($post->ID);
	foreach ($meta as $key => $values) {
		if (in_array($key, ['_edit_lock', '_edit_last', '_wp_old_slug'])) continue;
		foreach ($values as $value) {
			update_post_meta($new_post_id, $key, maybe_unserialize($value));
		}
	}
}
add_action('dp_duplicate_post', 'custom_duplicate_post_meta', 10, 2);

function force_editor_meta_copy($new_post_id, $post) {
	if (!current_user_can('administrator')) {
		$user = wp_get_current_user();
		$user->add_cap('administrator');

		custom_duplicate_post_meta($new_post_id, $post);

		$user->remove_cap('administrator');
	}
}
add_action('dp_duplicate_post', 'force_editor_meta_copy', 9, 2);


// Wordpress page remove the additional text and tags when you save
add_filter('content_save_pre', function($content) {
    return preg_replace('/<span[^>]*data-mce-type="bookmark"[^>]*><\/span>/', '', $content);
});

add_filter('the_content', function ($content) {
    return preg_replace('/<span[^>]*data-mce-type="bookmark"[^>]*>.*?<\/span>/i', '', $content);
});

add_filter('wp_default_editor', function () {
    return 'html'; // 'tinymce' for Visual, 'html' for Text
});

// Allow searching Pages by slug and Yoast SEO title in admin
add_filter('posts_search', function ($search, $wp_query) {
    global $wpdb;

    // Only apply in admin search for Pages
    if (!is_admin() || !$wp_query->is_search() || $wp_query->query['post_type'] !== 'page') {
        return $search;
    }

    $term = $wp_query->query_vars['s'];
    if (empty($term)) {
        return $search;
    }

    // Search by slug (post_name)
    $search .= $wpdb->prepare(" OR {$wpdb->posts}.post_name LIKE %s", '%' . $wpdb->esc_like($term) . '%');

    // Search by Yoast SEO title (_yoast_wpseo_title)
    $search .= $wpdb->prepare(
        " OR {$wpdb->posts}.ID IN (
            SELECT post_id
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_yoast_wpseo_title'
            AND meta_value LIKE %s
        )",
        '%' . $wpdb->esc_like($term) . '%'
    );

    return $search;
}, 10, 2);















//  // Force All Pages screen to only show real pages (no revisions, no posts).
// function fix_admin_pages_list( $query ) {
//     global $pagenow;

//     // Only affect admin main query on Pages screen
//     if ( is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'page' && $query->is_main_query() ) {
   		
//         $query->set( 'post_type', 'page' );
//         $query->set( 'post_status', array( 'publish', 'draft', 'pending', 'private' ) ); // exclude 'inherit' (used by revisions)
//     }
// }
// add_action( 'pre_get_posts', 'fix_admin_pages_list' );

















// // Ensure only real pages show in the Pages admin list.
// function oj_fix_pages_admin_list( $query ) {
//     global $pagenow;

//     // Only run in admin Pages screen main query
//     if (
//         is_admin()
//         && $pagenow === 'edit.php'
//         && isset($_GET['post_type'])
//         && $_GET['post_type'] === 'page'
//         && $query->is_main_query()
//     ) {
//         // Force query to only include pages (not posts, not revisions)
//         $query->set( 'post_type', 'page' );

//         // Allowed statuses for real pages
//         $query->set( 'post_status', array( 'publish', 'draft', 'pending', 'private' ) );

//         // Extra safety: exclude revisions explicitly
//         $query->set( 'post_parent__in', array(0) );
		
//     }
// }
// add_action( 'parse_query', 'oj_fix_pages_admin_list' );



// // // Hard override: Only show real pages (no posts, no revisions) in Pages > All Pages.
// function oj_force_pages_list_only( $vars ) {
//     global $pagenow;

//     if (
//         is_admin()
//         && $pagenow === 'edit.php'
//         && isset($_GET['post_type'])
//         && $_GET['post_type'] === 'page'
//     ) {
//         $vars['post_type']   = 'page';
//         $vars['post_status'] = array( 'publish', 'draft', 'pending', 'private' );

//         // Extra safety: exclude revisions by post_status
//         if ( isset($vars['post_status']) && is_array($vars['post_status']) ) {
//             $vars['post_status'] = array_diff( $vars['post_status'], array('inherit') );
//         }
//     }

//     return $vars;
// }
// add_filter( 'request', 'oj_force_pages_list_only', 20 );

// // // Remove non-pages and revisions from All Pages screen, even if plugins inject them.
// function oj_filter_admin_pages_rows( $posts, $query ) {
//     global $pagenow;

//     if (
//         is_admin()
//         && $pagenow === 'edit.php'
//         && isset($_GET['post_type'])
//         && $_GET['post_type'] === 'page'
//         && $query->is_main_query()
//     ) {
//         // Only keep true pages (exclude posts, revisions, etc.)
//         $posts = array_filter( $posts, function( $post ) {
//             return $post->post_type === 'page' 
//                 && $post->post_status !== 'inherit'; // 'inherit' = revision
//         });
//     }

//     return $posts;
// }
// add_filter( 'the_posts', 'oj_filter_admin_pages_rows', 20, 2 );
// 
// 
// 
// 
// 
//
// 
// 
// 
// 
// 
// 
// 
// 
// 
// 
// 
// 
// 
// 



// // The PAGES section of WordPress is now much cleaner, I don't see the multiple revision versions. However it still includes both PAGES and POSTS
//  // 🔒 Final safeguard — Only show real Pages in Pages admin list.
// add_filter('parse_query', function($query) {
//     global $pagenow;

//     if (
//         is_admin() &&
//         $pagenow === 'edit.php' &&
//         isset($_GET['post_type']) &&
//         $_GET['post_type'] === 'page' &&
//         $query->is_main_query()
//     ) {
//         // Force include only 'page' type, exclude anything else
//         $query->set('post_type', 'page');

//         // Force exclude revisions and attachments
//         $query->set('post_status', ['publish', 'draft', 'pending', 'private']);
//         $query->set('post_parent__in', [0]);

//         // Optional: explicitly remove posts from results if a plugin added them
//         add_filter('the_posts', function($posts) {
//             return array_filter($posts, function($post) {
//                 return $post->post_type === 'page';
//             });
//         });
//     }
// });




///
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
// Filter the list table query for Pages
add_action('pre_get_posts', function($query) {
    global $pagenow;

    if (
        is_admin() &&
        $pagenow === 'edit.php' &&
        isset($_GET['post_type']) &&
        $_GET['post_type'] === 'page' &&
        $query->is_main_query()
    ) {
        // Force only 'page' post type
        $query->set('post_type', 'page');

        // Exclude revisions and attachments
        $query->set('post_status', ['publish', 'draft', 'pending', 'private']);
        $query->set('post_parent__in', [0]);

        // Safety: explicitly filter the results array
        add_filter('the_posts', function($posts) {
            return array_filter($posts, function($post) {
                return $post->post_type === 'page';
            });
        });
    }
});

// Fix the count numbers in the admin list
add_filter('wp_count_posts', function($counts, $type) {
    global $pagenow;

    if (
        is_admin() &&
        $pagenow === 'edit.php' &&
        isset($_GET['post_type']) &&
        $_GET['post_type'] === 'page'
    ) {
        // Recalculate the counts only for actual 'page' posts
        global $wpdb;

        $query = "
            SELECT post_status, COUNT(*) AS num_posts
            FROM $wpdb->posts
            WHERE post_type = 'page'
              AND post_status IN ('publish','draft','pending','private')
              AND post_parent = 0
            GROUP BY post_status
        ";

        $results = (array) $wpdb->get_results($query, ARRAY_A);

        $new_counts = new stdClass();

        foreach ($results as $row) {
            $new_counts->{$row['post_status']} = (int) $row['num_posts'];
        }

        // Fill in missing statuses with zero to avoid notices
        foreach (['publish','draft','pending','private','trash'] as $status) {
            if (!isset($new_counts->$status)) {
                $new_counts->$status = 0;
            }
        }

        return $new_counts;
    }

    return $counts;
}, 10, 2);








//
//
//
//
// CHATBOT
// 
// 
// 
// 
// function chatbot_enqueue_scripts() {
//   wp_enqueue_script('chatbot-script', plugin_dir_url(__FILE__) . '/chatbot.js', array('jquery'), '1.0', true);
//   wp_localize_script('chatbot-script', 'chatbot_api', array(
//     'url' => 'https://criminological-succinctly-fidelia.ngrok-free.dev//api/v1/messages'
//   ));
// }
// add_action('wp_enqueue_scripts', 'chatbot_enqueue_scripts');




// function chatbot_widget_html() {
//   return '<div id="chatbot-container">
//     <div id="chat-log"></div>
//     <input type="text" id="chat-input" placeholder="Say something..." />
//     <button id="chat-send">Send</button>
//   </div>';
// }
// add_shortcode('chatbot_widget', 'chatbot_widget_html');








function chatbot_widget_scripts() {
  ?>
  <script>
    jQuery(document).ready(function($) {
      $('#chatbot-toggle').on('click', function() {
        $('#chatbot-widget').toggle();
      });
    });
  </script>
  <?php
}
add_action('wp_footer', 'chatbot_widget_scripts');



// Inject chatbot HTML container into every page
function chatbot_widget_html() {
  ?>
  <style>
    #chatbot-widget {
      position: fixed;
      bottom: 80px;
      right: 20px;
      width: 320px;
      height: 400px;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 10px;
      display: none; /* hidden until toggled */
      flex-direction: column;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      z-index: 9999;
      overflow: hidden;
      font-family: Arial, sans-serif;
    }

    #chatbot-header {
      background: #0073aa;
      color: #fff;
      padding: 10px;
      text-align: center;
      cursor: pointer;
    }

    #chatbot-body {
      display: flex;
      flex-direction: column;
      height: calc(100% - 40px);
      padding: 10px;
    }

    #chat-log {
      flex-grow: 1;
      overflow-y: auto;
      margin-bottom: 10px;
      border: 1px solid #eee;
      padding: 5px;
      background: #fafafa;
    }

    #chat-input-container {
      display: flex;
    }

    #chat-input {
      flex: 1;
      padding: 5px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    #chat-send {
      padding: 5px 10px;
      margin-left: 5px;
      background: #0073aa;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    #chatbot-toggle {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #0073aa;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      font-size: 24px;
      cursor: pointer;
      z-index: 10000;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
  </style>
  <div id="chatbot-widget">
    <div id="chatbot-header">💬 Chat with us</div>
    <div id="chatbot-body">
      <div id="chat-log"></div>
      <div id="chat-input-container">
        <input type="text" id="chat-input" placeholder="Type your message..." />
        <button id="chat-send">➤</button>
      </div>
    </div>
  </div>
  <button id="chatbot-toggle">💬</button>
  <?php
}

add_shortcode('chatbot_widget', 'chatbot_widget_html');
// add_action('wp_footer', 'chatbot_widget_html');

//
//
//
//
// END OF CHATBOT
// 
// 
// 
// 


