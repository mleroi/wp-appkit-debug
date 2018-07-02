<?php
/*
  Plugin Name: WP-AppKit Debug
  Plugin URI:  https://github.com/uncatcrea/wp-appkit
  Description: Display WP-AppKit debug information in Back Office
  Author:      Uncategorized Creations
  Author URI:  http://getwpappkit.com
  Version:     1.0.0
  License:     GPL-2.0+
  License URI: http://www.gnu.org/licenses/gpl-2.0.txt
  Copyright:   2013-2016 Uncategorized Creations
 */

class WpAppKitDebug {

    public static function hooks() {
        add_action( 'admin_menu', array( __CLASS__, 'add_settings_panels' ), 100 );
    }

    public static function add_settings_panels() {
        
        if ( !class_exists( 'WpAppKit' ) ) {
            return;
        }
        
        add_submenu_page( 
            WpakApps::menu_item, 
            'Debug', 
            'Debug', 
            'manage_options', 
            'wpak_debug', 
            array( __CLASS__, 'settings_panel' ) 
        );
        
    }
    
    public static function settings_panel() {
        $active_tab = !empty( $_GET['wpak_debug_page'] ) ? sanitize_key( $_GET['wpak_debug_page'] ) : 'rewrite_rules';
        $debug_page_base_url = self::get_debug_page_base_url();
        ?>
        <div class="wrap">
            <h2>WP-AppKit debug</h2>
            <h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( add_query_arg( array( 'wpak_debug_page' => 'rewrite_rules' ), $debug_page_base_url ) ); ?>" class="nav-tab <?php echo $active_tab == 'rewrite_rules' ? 'nav-tab-active' : ''; ?>">Rewrite Rules</a>
			</h2>
            <div class="wrap-<?php echo $active_tab; ?>">
				<?php 
					$content_function = 'tab_' . $active_tab;
					if( method_exists( __CLASS__, $content_function ) ) {
						self::$content_function();
					}
				?>
			</div>
        </div>
        <?php
    }
    
    public static function tab_rewrite_rules() {
        global $wp_rewrite;
        $rewrite_rules = $wp_rewrite->wp_rewrite_rules();
        
        $home_url = home_url();
        $wp_config_js_url = WpakBuild::get_appli_dir_url() . '/config.js';
        $wp_config_js_match = self::get_rewrite_rule_match(str_replace( trailingslashit( $home_url ), '', $wp_config_js_url));
        
        $wpak_webservice_url = '';
        $first_app = null;
        $apps_query = new WP_Query( ['post_type' => 'wpak_apps', 'posts_per_page' => 1] );
        if( $apps_query->posts ) {
            $first_app = reset( $apps_query->posts );
            $wpak_webservice_url = WpakWebServices::get_app_web_service_url( $first_app->ID, 'synchronization' );
            $wpak_webservice_url_match = self::get_rewrite_rule_match(str_replace( trailingslashit( $home_url ), '', $wpak_webservice_url));
        }

        $home_path = get_home_path();
        $htaccess_file = $home_path.'.htaccess';

        ?>

        <table class="wp-list-table widefat fixed striped wpak-rewrite-rules">
            <tbody>
                <tr>
                    <th>Home url</th>
                    <td><?php echo $home_url ?></td>
                </tr>
                <tr>
                    <th>First app found</th>
                    <td><?php echo $first_app ? $first_app->post_title : 'None' ?></td>
                </tr>
                <tr>
                    <th>App webservice data url</th>
                    <td><?php echo $wpak_webservice_url ?></td>
                </tr>
                <tr>
                    <th>App webservice data url match</th>
                    <td><?php echo $wpak_webservice_url_match ?></td>
                </tr>
                <tr>
                    <th>Config.js url</th>
                    <td><?php echo $wp_config_js_url ?></td>
                </tr>
                <tr>
                    <th>Config.js rewrite rule match</th>
                    <td><?php echo $wp_config_js_match ?></td>
                </tr>
            </tbody>
        </table>

        <table class="wp-list-table widefat fixed striped wpak-rewrite-rules">
            <thead>
                <tr>
                    <th>Rule</th>
                    <th>Redirect</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach( $rewrite_rules as $rule => $redirect ): ?>
                <tr>
                    <td><?php echo $rule ?></td>
                    <td><?php echo $redirect ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <h3>.htaccess:</h3>
        <div id="htaccess">
            
            <?php if( !is_writable( $home_path ) ): ?>
                <p><?php echo $home_path; ?> is not writable.</p>
            <?php endif; ?>

            <?php if ( !file_exists( $htaccess_file ) ): ?>
                <p>No .htaccess file</p>
            <?php else: ?>
                <?php if( !is_writable( $htaccess_file ) ): ?>
                    <p><?php echo $home_path; ?> is not writable.</p>
                <?php endif; ?>
                <p><?php echo nl2br( file_get_contents( $htaccess_file ) ); ?></p>
            <?php endif; ?>

        </div>

        <style>
            table.wpak-rewrite-rules{ margin: 10px 0; }
            #htaccess{ border:1px solid #e5e5e5; background: #fff; padding: 10px;}
        </style>
        
        <?php
    }
    
    protected static function get_debug_page_base_url() {
        return admin_url( 'admin.php?page=wpak_debug' );
    }

    protected static function get_rewrite_rule_match( $url ) {
        global $wp_rewrite;
        $match = '';
        $rewrite = $wp_rewrite->wp_rewrite_rules();
        foreach ( (array) $rewrite as $match => $query ) {
            if ( preg_match("#^$match#", $url, $matches) ) {
                $match = $match;
                break;
            }
        }
        return $match;
    }
}

WpAppKitDebug::hooks();

