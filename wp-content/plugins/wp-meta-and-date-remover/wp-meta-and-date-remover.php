<?php

/**
 * Plugin Name: WP Meta and Date remover
 * Plugin URI: mailto:prasadkirpekar96@gmail.com
 * Description: Remove meta and date information from posts and pages
 * Author: Prasad Kirpekar
 * Author URI: mailto:prasadkirpekar96@gmail.com
 * Version: 2.2.1
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
define( 'WPMDR_URL', plugin_dir_url( __FILE__ ) );
define( 'WPMDR_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPMDR_VERSION', '2.2.1' );
function enqueue_custom_script()
{
    // Enqueue the custom-script.js file
    wp_enqueue_script(
        'custom-script',
        WPMDR_URL . 'assets/js/inspector.js',
        array(),
        '1.0',
        true
    );
}

add_action( 'wp_enqueue_scripts', 'enqueue_custom_script' );
class WPMDRMain
{
    public function boot()
    {
        $this->loadClasses();
        $this->registerShortCodes();
        $this->ActivatePlugin();
        $this->renderMenu();
        $this->registerHooks();
        $this->registerAjax();
    }
    
    public function registerHooks()
    {
        $wpmdr = new \WPMDRMain\Classes\WPDateRemover();
        add_filter(
            'script_loader_tag',
            array( $this, 'addModuleToScript' ),
            10,
            3
        );
        $options = $wpmdr->getOptions();
        
        if ( $options['individualPostOption'] ) {
            add_action( 'add_meta_boxes', array( $wpmdr, 'addIndividualPostOptionCheckbox' ) );
            add_action( 'save_post', array( $wpmdr, 'updateOptionToPost' ) );
        }
        
        add_action( 'wp_head', function () {
            $wpmdr = new \WPMDRMain\Classes\WPDateRemover();
            $wpmdr->removerFilter( 'css' );
        }, 10 );
        
        if ( $options["removeByPHPLegacy"] ) {
            add_action( 'loop_start', function () {
                $wpmdr = new \WPMDRMain\Classes\WPDateRemover();
                $wpmdr->removerFilter( 'php' );
            }, 10 );
        } else {
            if ( !is_admin() ) {
                add_action( 'the_post', function () {
                    $wpmdr = new \WPMDRMain\Classes\WPDateRemover();
                    $wpmdr->resetFilter();
                    $wpmdr->removerFilter( 'php' );
                }, 10 );
            }
        }
        
        add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), array( $wpmdr, 'additionalLinks' ) );
    }
    
    public function registerAjax()
    {
        $wpmdr = new \WPMDRMain\Classes\WPDateRemover();
        add_action( 'wp_ajax_load_options', array( $wpmdr, 'loadOptions' ) );
        add_action( 'wp_ajax_get_settings', array( $wpmdr, 'getSettings' ) );
        add_action( 'wp_ajax_update_settings', array( $wpmdr, 'updateSettings' ) );
        add_action( 'wp_ajax_dashboard_data', array( $wpmdr, 'dashboardData' ) );
    }
    
    public function loadClasses()
    {
        require WPMDR_DIR . 'includes/autoload.php';
    }
    
    public function renderMenu()
    {
        add_action( 'admin_menu', function () {
            if ( !current_user_can( 'manage_options' ) ) {
                return;
            }
            global  $submenu ;
            $page = add_options_page(
                'WP Meta and Date Remover',
                'WP Meta and Date Remover',
                'manage_options',
                basename( __FILE__ ),
                array( $this, 'renderAdminPage' )
            );
        } );
    }
    
    public function addModuleToScript( $tag, $handle, $src )
    {
        if ( $handle === 'WPMDR-script-boot' ) {
            $tag = '<script type="module" id="WPMDR-script-boot" src="' . esc_url( $src ) . '"></script>';
        }
        return $tag;
    }
    
    public function renderAdminPage()
    {
        $loadAssets = new \WPMDRMain\Classes\LoadAssets();
        $loadAssets->enqueueAssets();
        $WPMDR = apply_filters( 'WPMDR/admin_app_vars', array(
            'assets_url'     => WPMDR_URL . 'assets/',
            'ajaxurl'        => admin_url( 'admin-ajax.php' ),
            'is_pro'         => !wpmdr_fs()->is_not_paying(),
            'upgrade_url'    => wpmdr_fs()->get_upgrade_url(),
            'account_url'    => wpmdr_fs()->get_account_url(),
            'plugin_version' => WPMDR_VERSION,
            'site_url'       => site_url(),
        ) );
        wp_localize_script( 'WPMDR-script-boot', 'WPMDRAdmin', $WPMDR );
        echo  '<div class="WPMDR-admin-page" id="WPWVT_app">
            
            <router-view></router-view>
        </div>' ;
    }
    
    public function registerShortCodes()
    {
    }
    
    public function ActivatePlugin()
    {
    }

}

if ( function_exists( 'wpmdr_fs' ) ) {
    wpmdr_fs()->set_basename( false, __FILE__ );
} else {
    
    if ( !function_exists( 'wpmdr_fs' ) ) {
        // Create a helper function for easy SDK access.
        function wpmdr_fs()
        {
            global  $wpmdr_fs ;
            
            if ( !isset( $wpmdr_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $wpmdr_fs = fs_dynamic_init( array(
                    'id'              => '6753',
                    'slug'            => 'wp-meta-and-date-remover',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_6bc68a469d4ab171bcc3dc4717f42',
                    'is_premium'      => false,
                    'premium_suffix'  => 'Pro',
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'has_affiliation' => 'selected',
                    'menu'            => array(
                    'slug'    => 'wp-meta-and-date-remover.php',
                    'support' => false,
                    'parent'  => array(
                    'slug' => 'options-general.php',
                ),
                ),
                    'is_live'         => true,
                ) );
            }
            
            return $wpmdr_fs;
        }
        
        // Init Freemius.
        wpmdr_fs();
        // Signal that SDK was initiated.
        do_action( 'wpmdr_fs_loaded' );
    }
    
    ( new WPMDRMain() )->boot();
}

function wpmdr_custom_hide()
{
    if ( get_option( 'wpmdr_custom_hide', "1" ) == "1" ) {
        return false;
    }
    return true;
}
