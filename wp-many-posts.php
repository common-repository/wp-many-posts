<?php
/*
Plugin Name: WP Many Posts
Plugin URI: http://softrade.it/wp-many-posts-wordpress-plugin
Description: WP Many Posts helps administrators to manage blogs with thousands of posts. Key feature is an advanced inline editing to manage titles, slugs, categories, authors easily and quickly. Advanced search and filter to find and edit posts in one second. PRO version is coming soon with configuration for custom post types and custom fields.
Version: 1.6.2 
Author:       Andrea Somovigo
Author URI:   http://it.linkedin.com/in/andreasomovigo
Text Domain: manyposts
Domain Path: /languages
 **************************************************************************

Copyright (C) 2008-2015 SOFTRADE

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

 **************************************************************************/
load_plugin_textdomain( 'manyposts', FALSE, dirname(plugin_basename( __FILE__ )).'/languages' );

/* * @@ LOAD SCRIPTS @@ * */
function add_ManyPosts_scripts(){
    $options = get_option( 'manyposts_settings' );
    wp_enqueue_style('k-commoncss',plugin_dir_url( __FILE__ ).'lib/css/kendo.common.min.css');
    wp_enqueue_style('k-colorcss',plugin_dir_url( __FILE__ ).'lib/css/kendo.'.$options['grid_style'].'.css');
    wp_enqueue_script( 'kendoc', plugin_dir_url( __FILE__ ).'lib/js/kendo.custom.min.js',array('jquery'),true );

}
add_action('admin_enqueue_scripts','add_ManyPosts_scripts',99);

add_action( 'admin_menu', 'add_ManyPosts_filters' );
function add_ManyPosts_filters(){

    $filter_posts=   add_menu_page( 'WP Many Posts', 'WP Many Posts', 'activate_plugins', 'ManyPostsFilters', 'many_posts_filters',  'dashicons-admin-settings',8); 	
    add_submenu_page('ManyPostsFilters', 'Wp Many Posts',__('Manage Posts'),  'activate_plugins', 'ManyPostsFilters', 'many_posts_filters',  'dashicons-admin-settings',8);

    add_submenu_page(
            'ManyPostsFilters',
            'WP Many Posts Settings',
            __('Settings'),
            'activate_plugins', 
            'manyposts_settings',
            'manyposts_display'
            );
    
    
    function many_posts_filters(){
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        echo '<div class="wrap">';
        echo '<h1>'.__('ADVANCED POSTS FILTER AND EDIT').'</h1>';
        echo '</div>';
        echo '<div class="wrap ">';

        include( plugin_dir_path( __FILE__ ) .'/manyPostsGrid.php' );	
        echo '</div>';
    }
    

    function manyposts_display( ) {
?>
	
	<div class="wrap">
	
		<div id="icon-themes" class="icon32"></div>
		<h2><?php _e( 'WP Many Posts  Options', 'manyposts' ); ?></h2>
		<?php settings_errors(); ?>
		

		<form method="post" action="options.php">
			<?php
        
        settings_fields( 'manyposts_settings' );
        do_settings_sections( 'manyposts_settings' );
        
        submit_button();
        
            ?>
            
		</form>
		
	</div><!-- /.wrap -->
<?php
    } // end manyposts_display
    

    function manyposts_default_input_options() {
        
        $defaults = array(
            
            'manypost_include_draft'	=>	'',

            'grid_style'		=>	'default'	
        );
        
        return apply_filters( 'manyposts_default_input_options', $defaults );
        
    } // end manyposts_default_input_options
    
    
    function manyposts_initialize_input() {

        if( false == get_option( 'manyposts_settings' ) ) {	
            add_option( 'manyposts_settings', apply_filters( 'manyposts_default_input_options', manyposts_default_input_options() ) );
        } // end if

        add_settings_section(
            'input_section',
            '',
            'manyposts_settings_callback',
            'manyposts_settings'
        );

        add_settings_field(
            'Checkbox Element',
            __( 'Show also drafts', 'manyposts' ),
            'manyposts_checkbox_element_callback',
            'manyposts_settings',
            'input_section'
        );
        
        add_settings_field(
            'Select Element',
            __( 'Grid color', 'manyposts' ),
            'manyposts_select_element_callback',
            'manyposts_settings',
            'input_section'
        );
        
        register_setting(
            'manyposts_settings',
            'manyposts_settings',
            'manyposts_validate_input'
        );

    } // end manyposts_initialize_input
    add_action( 'admin_init', 'manyposts_initialize_input' );
    
    /* ------------------------------------------------------------------------ *
     * Section Callbacks
     * ------------------------------------------------------------------------ */ 

    function manyposts_settings_callback() {
        echo '<p>' . __( 'Choose some configuration', 'manyposts' ) . '</p>';
    } // end manyposts_general_options_callback
    


    function manyposts_checkbox_element_callback() {

        $options = get_option( 'manyposts_settings' );
        
        $html = '<input type="checkbox" id="manypost_include_draft" name="manyposts_settings[include_drafts]" value="1"' . checked( 1, $options['include_drafts'], false ) . '/>';
        $html .= '&nbsp;';
        $html .= '<label for="manypost_include_draft">'._e('Include draft and pending?','manyposts').'</label>';
        
        echo $html;

    } 

    function manyposts_select_element_callback() {

        $options = get_option( 'manyposts_settings' );
        
        $html = '<select id="grid_style" name="manyposts_settings[grid_style]">';
		$html .= '<option value="default">' . __( 'Select a style...', 'manyposts' ) . '</option>';
		$html .= '<option value="dark"' . selected( $options['grid_style'], 'dark', false) . '>' . __( 'Dark', 'manyposts' ) . '</option>';
		$html .= '<option value="light"' . selected( $options['grid_style'], 'light', false) . '>' . __( 'Light', 'manyposts' ) . '</option>';
        
        echo $html;

    } 
    
    function manyposts_validate_input( $input ) {

        $output = array();
        
        foreach( $input as $key => $value ) {
            

            if( isset( $input[$key] ) ) {
                
                $output[$key] = strip_tags( stripslashes( $input[ $key ] ) );
                
            } // end if
            
        } // end foreach
        

        return apply_filters( 'manyposts_validate_input', $output, $input );

    } // end manyposts_validate_input
    
    //END
}