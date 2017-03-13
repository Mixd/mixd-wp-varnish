<?php
    
    /**
     * This file should contain all of your functions that you need to fire off when in
     * the WordPress back-end. Please ensure you remember to sanitize your variables if
     * handling $_POST or $_GET data.
     *
     * @see https://developer.wordpress.org/plugins/security/data-validation/
     * @see https://developer.wordpress.org/plugins/security/securing-input/
     */
    
    /**
     * Set up permissions for Administrators and Editors to access the configuration Page
     * of this plugin
     *
     * @since 4.0.31
     */
    function mixd_wp_varnish_add_caps() {
        
        $role = get_role( 'administrator' );
        
        // Add new Capabilities
        $role->remove_cap( 'manage_varnish' );
        $role->add_cap( 'mixd_wp_manage_varnish' );
        
        $role = get_role( 'editor' );
        
        // Add new Capabilities
        $role->remove_cap( 'manage_varnish' );
        $role->add_cap( 'mixd_wp_manage_varnish' );
        
    }
    
    add_action( 'admin_init', 'mixd_wp_varnish_add_caps' );
    
    
    
    /**
     * Add a sub menu page underneath the existing Mixd Plugins Page
     *
     * @since 4.0.31
     */
    function mixd_wp_varnish_options_page() {
        
        add_submenu_page(
            'mixd-wp-plugins',                      // $parent_slug
            'Mixd Plugins: Varnish',                // $page_title
            'Varnish Purge',                        // $menu_title
            'mixd_wp_plugins',                      // $capability
            'mixd_wp_varnish_options',              // $menu_slug
            'mixd_wp_varnish_options_screen'        // $callback
        );
        
    }
    
    add_action( 'admin_menu', 'mixd_wp_varnish_options_page' );
    
    
    
    /**
     * Outputs information on the 'NHS Choices' Page in WordPress Admin
     *
     * @since 4.0.31
     */
    function mixd_wp_varnish_options_screen() {
        
        /*
         * Load the options page content
         */
        require_once(plugin_dir_path( __FILE__ ) . 'mixd-wp-varnish-status.php');
    }
    
    
    /**
     * Purge Varnish via WP-CLI
     *
     * @since 3.8
     */
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        include('wp-cli.php');
    }
