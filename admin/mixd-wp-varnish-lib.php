<?php
    
    /**
     * Purge Varnish Class
     *
     * @since 2.0
     */
    class VarnishPurger
    {
        protected $purgeUrls = [];
        
        /**
         * Init
         *
         * @since  2.0
         * @access public
         */
        public function __construct() {
            
            defined( 'VHP_VARNISH_IP' ) || define( 'VHP_VARNISH_IP', false );
            add_action( 'init', [&$this, 'init'] );
            add_action( 'activity_box_end', [$this, 'varnish_rightnow'], 100 );
        }
        
        /**
         * Plugin Init
         *
         * @since  1.0
         * @access public
         */
        public function init() {
            
            global $blog_id;
            
            // Warning: No Pretty Permalinks!
            if ( '' == get_option( 'permalink_structure' ) && current_user_can( 'manage_options' ) ) {
                add_action( 'admin_notices', [$this, 'prettyPermalinksMessage'] );
                
                return;
            }
            
            // get my events
            $events     = $this->getRegisterEvents();
            $noIDevents = $this->getNoIDEvents();
            
            // make sure we have events and they're in an array
            if ( ! empty( $events ) && ! empty( $noIDevents ) ) {
                
                // Force it to be an array, in case someone's stupid
                $events     = (array) $events;
                $noIDevents = (array) $noIDevents;
                
                // Add the action for each event
                foreach ( $events as $event ) {
                    if ( in_array( $event, $noIDevents ) ) {
                        // These events have no post ID and, thus, will perform a full purge
                        add_action( $event, [$this, 'purgeNoID'] );
                    } else {
                        add_action( $event, [$this, 'purgePost'], 10, 2 );
                    }
                }
            }
            
            add_action( 'shutdown', [$this, 'executePurge'] );
            
            // Success: Admin notice when purging
            if ( isset( $_GET['vhp_flush_all'] ) && check_admin_referer( 'vhp-flush-all' ) ) {
                add_action( 'admin_notices', [$this, 'purgeMessage'] );
            }
            
            // Checking user permissions for who can and cannot use the admin button
            if (
                // SingleSite - admins can always purge
                ( ! is_multisite() && current_user_can( 'mixd_wp_plugins' )) ||
                // Multisite - Network Admin can always purge
                current_user_can( 'manage_network' ) ||
                // Multisite - Site admins can purge UNLESS it's a subfolder install and we're on site #1
                (is_multisite() && current_user_can( 'mixd_wp_plugins' ) && (SUBDOMAIN_INSTALL || ( ! SUBDOMAIN_INSTALL && (BLOG_ID_CURRENT_SITE != $blog_id))))
            ) {
                add_action( 'admin_bar_menu', [$this, 'varnish_rightnow_adminbar'], 100 );
            }
            
        }
        
        /**
         * Purge Message
         * Informs of a succcessful purge
         *
         * @since 2.0
         */
        function purgeMessage() {
            
            echo "<div id='message' class='notice notice-success fade is-dismissible'><p><strong>" . __( 'Varnish cache purged!', 'varnish-http-purge' ) . "</strong></p></div>";
        }
        
        /**
         * Permalinks Message
         * Explains you need Pretty Permalinks on to use this plugin
         *
         * @since 2.0
         */
        function prettyPermalinksMessage() {
            
            echo "<div id='message' class='error'><p>" . sprintf( __( 'Varnish HTTP Purge requires you to use custom permalinks. Please go to the <a href="%1$s">Permalinks Options Page</a> to configure them.', 'varnish-http-purge' ), admin_url( 'options-permalink.php' ) ) . "</p></div>";
        }
        
        /**
         * The Home URL
         * Get the Home URL and allow it to be filterable
         * This is for domain mapping plugins that, for some reason, don't filter
         * on their own (including WPMU, Ron's, and so on).
         *
         * @since 4.0
         */
        static public function the_home_url() {
            
            $home_url = apply_filters( 'vhp_home_url', home_url() );
            
            return $home_url;
        }
        
        /**
         * Varnish Purge Button in the Admin Bar
         *
         * @since 2.0
         */
        function varnish_rightnow_adminbar( $admin_bar ) {
            
            $admin_bar->add_menu( [
                'id'    => 'purge-varnish-cache-all',
                'title' => __( 'Empty Cache', 'varnish-http-purge' ),
                'href'  => wp_nonce_url( add_query_arg( 'vhp_flush_all', 1 ), 'vhp-flush-all' ),
                'meta'  => [
                    'title' => __( 'Empty Cache', 'varnish-http-purge' ),
                ],
            ] );
        }
        
        /**
         * Varnish Right Now Information
         * This information is put on the Dashboard 'Right now' widget
         *
         * @since 1.0
         */
        function varnish_rightnow() {
            
            global $blog_id;
            $url    = wp_nonce_url( add_query_arg( 'vhp_flush_all', 1 ), 'vhp-flush-all' );
            $intro  = sprintf( __( '<a href="%1$s">Varnish HTTP Purge</a> automatically purges your posts when published or updated. Sometimes you need a manual flush.', 'varnish-http-purge' ), 'http://wordpress.org/plugins/varnish-http-purge/' );
            $button = __( 'Press the button below to force it to empty your entire Varnish cache.', 'varnish-http-purge' );
            $button .= '</p><p><span class="button"><a href="' . $url . '"><strong>';
            $button .= __( 'Empty Cache', 'varnish-http-purge' );
            $button .= '</strong></a></span>';
            $nobutton = __( 'You do not have permission to purge the cache for the whole site. Please contact your administrator.', 'varnish-http-purge' );
            
            if (
                // SingleSite - admins can always purge
                ( ! is_multisite() && current_user_can( 'mixd_wp_plugins' )) ||
                // Multisite - Network Admin can always purge
                current_user_can( 'manage_network' ) ||
                // Multisite - Site admins can purge UNLESS it's a subfolder install and we're on site #1
                (is_multisite() && current_user_can( 'mixd_wp_plugins' ) && (SUBDOMAIN_INSTALL || ( ! SUBDOMAIN_INSTALL && (BLOG_ID_CURRENT_SITE != $blog_id))))
            ) {
                $text = $intro . ' ' . $button;
            } else {
                $text = $intro . ' ' . $nobutton;
            }
            echo "<p class='varnish-rightnow'>$text</p>\n";
        }
        
        /**
         * Registered Events
         * These are when the purge is triggered
         *
         * @since  1.0
         * @access protected
         */
        protected function getRegisterEvents() {
            
            // Define registered purge events
            $actions = [
                'switch_theme',                        // After a theme is changed
                'autoptimize_action_cachepurged',    // Compat with https://wordpress.org/plugins/autoptimize/
                'save_post',                            // Save a post
                'deleted_post',                        // Delete a post
                'trashed_post',                        // Empty Trashed post
                'edit_post',                            // Edit a post - includes leaving comments
                'delete_attachment',                    // Delete an attachment - includes re-uploading
            ];
            
            // send back the actions array, filtered
            // @param array $actions the actions that trigger the purge event
            return apply_filters( 'varnish_http_purge_events', $actions );
        }
        
        /**
         * Events that have no post IDs
         * These are when a full purge is triggered
         *
         * @since  3.9
         * @access protected
         */
        protected function getNoIDEvents() {
            
            // Define registered purge events
            $actions = [
                'switch_theme',                        // After a theme is changed
                'autoptimize_action_cachepurged,'    // Compat with https://wordpress.org/plugins/autoptimize/
            ];
            
            // send back the actions array, filtered
            // @param array $actions the actions that trigger the purge event
            // DEVELOPERS! USE THIS SPARINGLY! YOU'RE A GREAT BIG 💩 IF YOU USE IT FLAGRANTLY
            // Remember to add your action to this AND varnish_http_purge_events due to shenanigans
            return apply_filters( 'varnish_http_purge_events_full', $actions );
        }
        
        /**
         * Execute Purge
         * Run the purge command for the URLs. Calls $this->purgeUrl for each URL
         *
         * @since  1.0
         * @access protected
         */
        public function executePurge() {
            
            $purgeUrls = array_unique( $this->purgeUrls );
            
            if ( empty( $purgeUrls ) ) {
                if ( isset( $_GET['vhp_flush_all'] ) && check_admin_referer( 'vhp-flush-all' ) ) {
                    $this->purgeUrl( $this->the_home_url() . '/?vhp-regex' );
                }
            } else {
                foreach ( $purgeUrls as $url ) {
                    $this->purgeUrl( $url );
                }
            }
        }
        
        /**
         * Purge URL
         * Parse the URL for proxy proxies
         *
         * @since  1.0
         *
         * @param array $url the url to be purged
         *
         * @access protected
         */
        public function purgeUrl( $url ) {
            
            $p = parse_url( $url );
            
            if ( isset( $p['query'] ) && ($p['query'] == 'vhp-regex') ) {
                $pregex                = '.*';
                $varnish_x_purgemethod = 'regex';
            } else {
                $pregex                = '';
                $varnish_x_purgemethod = 'default';
            }
            
            // Build a varniship
            if ( VHP_VARNISH_IP != false ) {
                $varniship = VHP_VARNISH_IP;
            } else {
                $varniship = get_option( 'vhp_varnish_ip' );
            }
            $varniship = apply_filters( 'vhp_varnish_ip', $varniship );
            
            if ( isset( $p['path'] ) ) {
                $path = $p['path'];
            } else {
                $path = '';
            }
            
            /**
             * Schema filter
             *
             * Allows default http:// schema to be changed to https
             * varnish_http_purge_schema()
             *
             * @since 3.7.3
             *
             */
            
            $schema = apply_filters( 'varnish_http_purge_schema', 'http://' );
            
            // If we made varniship, let it sail
            if ( isset( $varniship ) && $varniship != null ) {
                $host = $varniship;
            } else {
                $host = $p['host'];
            }
            
            $purgeme = $schema . $host . $path . $pregex;
            
            if ( ! empty( $p['query'] ) && $p['query'] != 'vhp-regex' ) {
                $purgeme .= '?' . $p['query'];
            }
            
            // Cleanup CURL functions to be wp_remote_request and thus better
            // http://wordpress.org/support/topic/incompatability-with-editorial-calendar-plugin
            $response = wp_remote_request( $purgeme, [
                'method'  => 'PURGE',
                'headers' => [
                    'host'           => $p['host'],
                    'X-Purge-Method' => $varnish_x_purgemethod
                ]
            ] );
            
            do_action( 'after_purge_url', $url, $purgeme, $response );
        }
        
        /**
         * Purge - No IDs
         * Flush the whole cache
         *
         * @since  3.9
         * @access private
         */
        public function purgeNoID( $postId ) {
            
            $listofurls = [];
            
            array_push( $listofurls, $this->the_home_url() . '/?vhp-regex' );
            
            // Now flush all the URLs we've collected provided the array isn't empty
            if ( ! empty( $listofurls ) ) {
                foreach ( $listofurls as $url ) {
                    array_push( $this->purgeUrls, $url );
                }
            }
        }
        
        /**
         * Purge Post
         * Flush the post
         *
         * @since  1.0
         *
         * @param array $postId the ID of the post to be purged
         *
         * @access public
         */
        public function purgePost( $postId ) {
            
            // If this is a valid post we want to purge the post,
            // the home page and any associated tags and categories
            
            $validPostStatus = ["publish", "trash"];
            $thisPostStatus  = get_post_status( $postId );
            
            // array to collect all our URLs
            $listofurls = [];
            
            if ( get_permalink( $postId ) == true && in_array( $thisPostStatus, $validPostStatus ) ) {
                // If this is a post with a permalink AND it's published or trashed,
                // we're going to add a ton of things to flush.
                
                // Category purge based on Donnacha's work in WP Super Cache
                $categories = get_the_category( $postId );
                if ( $categories ) {
                    foreach ( $categories as $cat ) {
                        array_push( $listofurls, get_category_link( $cat->term_id ) );
                    }
                }
                // Tag purge based on Donnacha's work in WP Super Cache
                $tags = get_the_tags( $postId );
                if ( $tags ) {
                    foreach ( $tags as $tag ) {
                        array_push( $listofurls, get_tag_link( $tag->term_id ) );
                    }
                }
                
                // Author URL
                array_push( $listofurls,
                    get_author_posts_url( get_post_field( 'post_author', $postId ) ),
                    get_author_feed_link( get_post_field( 'post_author', $postId ) )
                );
                
                // Archives and their feeds
                $archiveurls = [];
                if ( get_post_type_archive_link( get_post_type( $postId ) ) == true ) {
                    array_push( $listofurls,
                        get_post_type_archive_link( get_post_type( $postId ) ),
                        get_post_type_archive_feed_link( get_post_type( $postId ) )
                    );
                }
                
                // Post URL
                array_push( $listofurls, get_permalink( $postId ) );
                
                // Also clean URL for trashed post.
                if ( $thisPostStatus == "trash" ) {
                    $trashpost = get_permalink( $postId );
                    $trashpost = str_replace( "__trashed", "", $trashpost );
                    array_push( $listofurls, $trashpost, $trashpost . 'feed/' );
                }
                
                // Add in AMP permalink if Automattic's AMP is installed
                if ( function_exists( 'amp_get_permalink' ) ) {
                    array_push( $listofurls, amp_get_permalink( $postId ) );
                }
                
                // Regular AMP url for posts
                array_push( $listofurls, get_permalink( $postId ) . 'amp/' );
                
                // Feeds
                array_push( $listofurls,
                    get_bloginfo_rss( 'rdf_url' ),
                    get_bloginfo_rss( 'rss_url' ),
                    get_bloginfo_rss( 'rss2_url' ),
                    get_bloginfo_rss( 'atom_url' ),
                    get_bloginfo_rss( 'comments_rss2_url' ),
                    get_post_comments_feed_link( $postId )
                );
                
                // Home Page and (if used) posts page
                array_push( $listofurls, $this->the_home_url() . '/' );
                if ( get_option( 'show_on_front' ) == 'page' ) {
                    // Ensure we have a page_for_posts setting to avoid empty URL
                    if ( get_option( 'page_for_posts' ) ) {
                        array_push( $listofurls, get_permalink( get_option( 'page_for_posts' ) ) );
                    }
                }
            } else {
                // We're not sure how we got here, but bail instead of processing anything else.
                return;
            }
            
            // Now flush all the URLs we've collected provided the array isn't empty
            if ( ! empty( $listofurls ) ) {
                foreach ( $listofurls as $url ) {
                    array_push( $this->purgeUrls, $url );
                }
            }
            
            // Filter to add or remove urls to the array of purged urls
            // @param array $purgeUrls the urls (paths) to be purged
            // @param int $postId the id of the new/edited post
            $this->purgeUrls = apply_filters( 'vhp_purge_urls', $this->purgeUrls, $postId );
        }
        
    }
    
    $purger = new VarnishPurger();
