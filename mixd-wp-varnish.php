<?php
    /**
     * This file is read by WordPress to generate the plugin information in the plugin
     * admin area. This file also includes all of the dependencies used by the plugin,
     * and registers the activation and deactivation functions.
     *
     * @link           https://github.com/Mixd/mixd-wp-varnish
     * @author         Mixd
     * @version        4.0.31
     * @package        mixd-wp-varnish
     *
     * Plugin Name:     Mixd Plugins: Varnish HTTP Purge
     * Plugin URI:      https://github.com/Mixd/mixd-wp-varnish
     * Description:     Automatically purge Varnish Cache when content on your site is modified.
     * Version:         4.0.31
     * Author:          Mika Epstein
     * Author URI:      https://halfelf.org/
     * License:         http://www.apache.org/licenses/LICENSE-2.0
     * Text Domain:     mixd-wp-varnish
     * Network:         true
     *
     *   @license
     *   Copyright 2013-2016: Mika A. Epstein (email: ipstenu@halfelf.org)
     *   Original Author: Leon Weidauer ( http:/www.lnwdr.de/ )
     *   This file is part of Varnish HTTP Purge, a plugin for WordPress.
     *   Varnish HTTP Purge is free software: you can redistribute it and/or modify
     *   it under the terms of the Apache License 2.0 license.
     *   Varnish HTTP Purge is distributed in the hope that it will be useful,
     *   but WITHOUT ANY WARRANTY; without even the implied warranty of
     *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     */
    
    
    
    /**
     * If we're not being loaded by WordPress, abort now
     *
     * @since 4.0.31
     */
    if ( ! defined( 'WPINC' ) ) {
        die;
    }
    
    
    
    /**
     * Load the Mixd Plugin foundation
     *
     * @since 4.0.31
     */
    require_once('mixd-wp-foundation.php');
    
    
    
    /**
     * Load the library
     *
     * @since 4.0.31
     */
    require_once('admin/mixd-wp-varnish-lib.php');
    
    
    
    /**
     * Define the title to display in plugin's admin Page
     *
     * @since  4.0.31
     * @return string
     */
    function mixd_wp_varnish_title() {
        
        return __( "Mixd's Varnish Cache Plugin for WordPress", "mixd-wp-varnish" );
    }
    
    
    
    /**
     * Define a short description to display in the plugin's admin Page
     *
     * @since  4.0.31
     * @return string
     */
    function mixd_wp_varnish_description() {
        
        return __(
            "Automatically purge Varnish Cache when content on your site is modified.",
            "mixd-wp-varnish"
        );
    }
    
    
    
    /**
     * Load the relevant scripts dependant on if the plugin is being loaded on the
     * frontend or the backend
     *
     * @since 4.0.31
     */
    function mixd_wp_varnish_admin() {
        
        if ( is_admin() ) {
            require_once(plugin_dir_path( __FILE__ ) . 'admin/mixd-wp-varnish-admin.php');
        }
    }
    
    add_filter( 'wp_loaded', 'mixd_wp_nhs_choices_admin' );
    
    function mixd_wp_varnish_public() {
        
        require_once(plugin_dir_path( __FILE__ ) . 'public/mixd-wp-varnish-public.php');
    }
    
    add_filter( 'wp_loaded', 'mixd_wp_varnish_public' );
    
    
    
    /**
     * Clear all preferences and options on uninstall
     *
     * @since 4.0.31
     */
    function mixd_wp_varnish_uninstall() {
        
        if ( ! current_user_can( 'delete_plugins' ) ) {
            return;
        }
        
        // Important: Check if the file is the one
        // that was registered during the uninstall hook.
        if ( __FILE__ != WP_UNINSTALL_PLUGIN ) {
            return;
        }
        
        mixd_wp_varnish_clear_capabilities();
        delete_site_option( 'vhp_varnish_url' );
        delete_site_option( 'vhp_varnish_ip' );
        
    }
    
    register_uninstall_hook( __FILE__, 'mixd_wp_varnish_uninstall' );
    
    
    
    /**
     * Clear out user capabilities
     *
     * @since 4.0.31
     */
    function mixd_wp_varnish_clear_capabilities() {
        
        $role = get_role( 'administrator' );
        $role->remove_cap( 'mixd_wp_manage_varnish' );
        
        $role = get_role( 'editor' );
        $role->remove_cap( 'mixd_wp_manage_varnish' );
        
    }
