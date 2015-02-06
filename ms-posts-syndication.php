<?php
/*
Plugin Name: Parent-Child Blog Syndication
Plugin URI: http://arstropica.com
Description: Displays posts from a parent blog on the child blog homepage. Requires StudioPress Genesis Template
Version: 2.1
Author: ArsTropica <info@arstropica.com>
Author URI: http://arstropica.com
/* ----------------------------------------------*/

add_action('after_setup_theme', 'mps_PluginInit', 10);
add_action('genesis_loop', 'output_parent_posts', 100);

register_deactivation_hook(__FILE__, 'mps_unregister');
register_activation_hook(__FILE__, 'mps_preregister');

function mps_PluginInit() {
    if (mps_preregister()){
        add_action('admin_menu', 'mps_admin_actions');
        if (!is_parent_blog())
            remove_action('genesis_after_endwhile', 'genesis_posts_nav');
    }
}

function mps_admin_actions() { add_options_page("", "Posts Syndication", 7, "mps-admin-options", "mps_admin"); }

function mps_admin() {
    global $blog_id;
    if (!current_user_can('manage_options')) { wp_die (__('You do not have sufficient permissions to access this page!')); }

    //Number of posts to display
    $mps_numposts  =get_option('mps_numposts_' . $blog_id);
    $mps_override  =get_option('mps_override');
    $mps_title  =get_option('mps_title');
    if (empty($mps_numposts)) 
        {
            $mps_numposts = 5;
        }

    // Default Parent Override
    if (empty($mps_override) || is_null($mps_override)) 
        {
            $mps_override = '1';
        }

    // MPS Title
    if (empty($mps_title) || is_null($mps_title)) 
        {
            $mps_title = '';
        }

    // Form Processing
    if (isset($_POST[ 'mps_hidden' ]) && isset($_POST[ 'mps_numposts' ]))
    {
        //Change View Setting
        $mps_numposts= $_POST[ 'mps_numposts' ];
        if (isset($_POST[ 'mps_override' ])) $mps_override= '1'; else $mps_override= '0';
        if (isset($_POST[ 'mps_title' ])) $mps_title = strip_tags(esc_attr($_POST['mps_title'])); else $mps_title= '';
        add_action('mps_admin_msg', 'mps_upd_numposts');
    }
    elseif(isset($_POST[ 'mps_hidden' ])) {
        $mps_numposts= 5;
        if (isset($_POST[ 'mps_override' ])) $mps_override= '1'; else $mps_override= '0';
        if (isset($_POST[ 'mps_title' ])) $mps_title = strip_tags(esc_attr($_POST['mps_title'])); else $mps_title= '';
        add_action('mps_admin_msg', 'mps_upd_numposts');
    }
    
    update_option('mps_title', $mps_title);
    update_option('mps_numposts_' . $blog_id, $mps_numposts);
    update_option('mps_override', $mps_override);
    output_mps_form();
}

function output_mps_form() {
    global $blog_id;
    $plugindir      =WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__));
    $postto = get_bloginfo('url') . '/wp-admin/options-general.php?page=mps-admin-options'; 
    $mps_numposts  =get_option('mps_numposts_' . $blog_id);
    $mps_override =get_option('mps_override');
    $mps_title =get_option('mps_title');
    do_action('mps_admin_msg');

    ?>
    <div class = "wrap">
        <?php echo "<h2>" . __('Parent-Child Blog Syndication Settings', 'mps_trdom') . "</h2>"; ?>
        <table width="600">
            <tbody>
                <tr>
                    <td style="vertical-align: top; height:200px;">
                        <form name = "mps_viewset" method = "post" id = "mps_viewset" action="<?php echo $postto; ?>">
                            <input type = "hidden" name = "mps_hidden" value = "Y"><?php echo "<h4>" . __('Parent Posts', 'mps_trdom') . "</h4>"; ?>
                            <p>
                                <?php
                                    _e ("Set title to display above parent posts: ");
                                ?>
                            </p>
                            <p><input type = "text" name = "mps_title" id = "mps_title" value = "<?php echo $mps_title; ?>" /></p>
                            <p>
                                <?php
                                    _e ("Set the number of parent posts to show on child home page: ");
                                ?>
                            </p>
                            <p><input type = "text" name = "mps_numposts" id = "mps_numposts" value = "<?php echo $mps_numposts; ?>" /></p>
                            <?php if (is_parent_blog()): ?>
                            <p>
                                <?php
                                    _e ("Override for all child blogs: ");
                                ?>
                            </p>
                            <p><input type = "checkbox" name = "mps_override" id = "mps_override" value = "override" <?php echo ($mps_override === '1' ? ' checked = "checked"' : ''); ?>></p>
                            <?php endif; ?>
                            <p class = "submit">
                                <input id="submit_mps" type = "submit" name = "Submit" value = "<?php _e('Update', 'mps_trdom' ) ?>"/>
                            </p>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>  
    <?php  
}


function is_Genesis_active() {
    //Check if Genesis Theme is installed / active
    if (function_exists('genesis')) return true;
    return false;
}

function mps_preregister() {
    if (!is_Genesis_active()){
        mps_deactivate('genesis');   
        return false;   
    } elseif (!is_multisite()) {
        mps_deactivate('multisite');   
        return false;   
    }
    return true;
}

function mps_deactivate($condition = 'defualt') {
    unset($_GET['activate']);
    if ($condition == 'genesis') {
    add_action('admin_notices', 'mps_genesis_rqd_error');
    } elseif ($condition == 'multisite') {
    add_action('admin_notices', 'mps_multisite_rqd_error');
    } else {
    add_action('admin_notices', 'mps_error');
    }
    $current = get_settings("active_plugins");
    array_splice($current, array_search(plugin_basename(__FILE__), $current), 1 ); // Array-function!
    update_option("active_plugins", $current);
}

function mps_genesis_rqd_error() {
  echo "<div class=\"error\"><p>The Parent-Child Blog Syndication plugin requires the installation of the Genesis Theme.</div>\n";
  echo "<div class=\"updated highlight\"><p><strong>Go to the <a target=\"_blank\" href=\"http://www.studiopress.com/themes/genesis\">Genesis Theme</a> site to download.</p></div>";
}

function mps_multisite_rqd_error() {
  echo "<div class=\"error\"><p>The Parent-Child Blog Syndication plugin requires an active blog network to function.</div>\n";
  echo "<div class=\"updated highlight\"><p><strong>Please enable the multisite network on your blog and reactivate.</p></div>";
}

function mps_error() {
  echo "<div class=\"error\"><p>An error occurred during activation of Parent-Child Blog Syndication plugin.</div>\n";
}

function mps_upd_numposts() {
    ?>    
        <div class = "updated"><p><strong>
        <?php
            _e ('Posts Setting Changed.');
        ?></strong></p>
        </div>
    <?php
}


function mps_unregister() {
    //
}

// Are we on the parent or a child blog?
function is_parent_blog()  {
    global $current_blog;
    $parent_blog = get_dashboard_blog();
    $parent_blog_id = $parent_blog->blog_id;    
    if ($current_blog->blog_id == $parent_blog_id)
        return true;
    return false;
}

function output_parent_posts() {
    global $current_blog, $paged, $blog_id, $post;
    if (is_parent_blog() || (!is_home())) return false;
    $mps_override = get_option('mps_override');
    $mps_title = get_option('mps_title');
    if ($mps_override === '1')
        $numposts = get_option('mps_numposts_1') ? get_option('mps_numposts_1') : 5;
    else
        $numposts = get_option('mps_numposts_' . $blog_id) ? get_option('mps_numposts_' . $blog_id) : 5;
    if ($paged <= 1):
        switch_to_blog(1);
        if (!is_enqueued_script('jquery')) :
        ?>
            <script type="text/javascript" src="http://code.jquery.com/jquery-latest.min.js"></script>
        <?php endif; ?>
            <script type="text/javascript">jQuery(document).ready(function() {
                jQuery('.parentblog A').attr('target', '_blank');
            });</script>
            <hr />
        <?php if (!empty($mps_title) && !is_null($mps_title)) : ?>
            <h6 class="pptitle"><?php echo $mps_title; ?></h6>
        <?php        
        endif;
        echo "<div class=\"parentblog\">\n";
        genesis_custom_loop('&orderby=date&order=DESC&showposts=' . $numposts);
        echo "</div>\n";
        restore_current_blog();
        genesis_posts_nav();
    endif;
}

function is_enqueued_script( $script )
{
    return isset( $GLOBALS['wp_scripts']->registered[ $script ] );
}
  
?>
