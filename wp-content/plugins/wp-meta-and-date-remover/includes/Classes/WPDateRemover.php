<?php

namespace WPMDRMain\Classes;

class WPDateRemover
{
    function __construct()
    {
    }
    
    function additionalLinks( $links )
    {
        $setting_link = '<a href="../wp-admin/options-general.php?page=wp-meta-and-date-remover.php">Settings</a>';
        array_unshift( $links, $setting_link );
        return $links;
    }
    
    function removeWithCSS()
    {
        $options = $this->getOptions();
        if ( $options['removeByCSS'] ) {
            echo  "<style>/* CSS added by WP Meta and Date Remover*/" . $options['cssCode'] . "</style>" ;
        }
    }
    
    function applyVisualRemoverCode()
    {
        $options = $this->getOptions();
        echo  "<style>/* Added by visual remover */" . $options['visualRemoverCSS'] . "</style>" ;
    }
    
    function addOptionToPost()
    {
        global  $post ;
        $options = $this->getOptions();
        if ( !in_array( get_post_type( $post ), $this->getOptions()['targetPostTypes'] ) ) {
            return;
        }
        $value = get_post_meta( $post->ID, 'wpmdr_menu', true );
        
        if ( empty($value) ) {
            add_post_meta(
                $post->ID,
                'wpmdr_menu',
                ( $options['individualPostDefault'] ? 1 : 0 ),
                true
            );
            $value = ( $options['individualPostDefault'] ? 1 : 0 );
        }
        
        $checked = ( $value == 1 ? ' checked="checked"' : '' );
        echo  '<div class="misc-pub-section"><label><input type="checkbox"' . $checked . ' value="1" name="wpmdr_menu_checkbox" /> Remove Meta and Date</label></div>' ;
    }
    
    function updateOptionToPost( $postid )
    {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( isset( $_POST['wpmdr_menu_checkbox'] ) ) {
            update_post_meta( $postid, 'wpmdr_menu', 1 );
        } else {
            update_post_meta( $postid, 'wpmdr_menu', 0 );
        }
    
    }
    
    function yoastSeoFilter()
    {
    }
    
    function yoastModifySchemaGraphPieces( $data )
    {
        return $data;
    }
    
    function resetFilter()
    {
        remove_filter( 'the_date', '__return_false' );
        remove_filter( 'the_time', '__return_false' );
        remove_filter( 'the_modified_date', '__return_false' );
        remove_filter( 'get_the_date', '__return_false' );
        remove_filter( 'get_the_time', '__return_false' );
        remove_filter( 'get_the_modified_date', '__return_false' );
        remove_filter( 'the_author', '__return_false' );
        remove_filter( 'get_the_author', '__return_false' );
        remove_filter( 'get_the_author_display_name', '__return_false' );
    }
    
    function removeWithPHP()
    {
        $options = $this->getOptions();
        
        if ( $options['removeDate'] ) {
            add_filter( 'the_date', '__return_false' );
            add_filter( 'the_time', '__return_false' );
            add_filter( 'the_modified_date', '__return_false' );
            add_filter( 'get_the_date', '__return_false' );
            add_filter( 'get_the_title', '__return_false' );
            add_filter( 'get_the_time', '__return_false' );
            add_filter( 'get_the_modified_date', '__return_false' );
        }
        
        
        if ( $options['removeAuthor'] ) {
            add_filter( 'the_author', '__return_false' );
            add_filter( 'get_the_author', '__return_false' );
            add_filter( 'get_the_author_display_name', '__return_false' );
        }
    
    }
    
    function addIndividualPostOptionCheckbox()
    {
    }
    
    function addIndividualPostOption( $postId )
    {
        add_post_meta(
            $postId,
            'wpmdr_menu',
            1,
            true
        );
        $value = 1;
    }
    
    private function applyRemover( $type )
    {
        $options = $this->getOptions();
        if ( $options['removeByCSS'] && $type === 'css' ) {
            $this->removeWithCSS();
        }
        if ( $options['removeByPHP'] && $type === 'php' ) {
            $this->removeWithPHP();
        }
    }
    
    function removerFilter( $type )
    {
        $options = $this->getOptions();
        if ( (is_home() || is_front_page()) && $options['removeFromHome'] ) {
            $this->applyRemover( $type );
        }
        global  $post ;
        
        if ( is_null( $post ) ) {
            $this->logDebug( "Invalid post to remove meta and date" );
            return;
        }
        
        $this->applyRemover( $type );
    }
    
    function dashboardData()
    {
        $options = $this->getOptions();
        $data = array();
        $targetedPostCount = 0;
        foreach ( $options['targetPostTypes'] as $type ) {
            $targetedPostCount += wp_count_posts( $type )->publish;
        }
        $data['targetedPostCount'] = $targetedPostCount;
        $data['excludedCategoryCount'] = count( $options['excludedCategories'] );
        $args = array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'date_query'     => array(
            'before' => date( 'Y-m-d', strtotime( '-3 years' ) ),
        ),
            'fields'         => 'ids',
            'posts_per_page' => -1,
        );
        $query = new \WP_Query( $args );
        $data['olderPostsCount'] = $query->post_count;
        wp_send_json_success( $data );
        wp_die();
    }
    
    function logDebug( $msg )
    {
        //echo $msg;
        if ( current_user_can( 'manage_options' ) && $this->getOptions()['showDebugLogs'] && !wpmdr_fs()->is_not_paying() ) {
            echo  "<span style='padding:5px;background:black;color:#fff'>{$msg}(Debug mode in WP Meta and Date Remover)</span><br>" ;
        }
    }
    
    public function updateSettings()
    {
        
        if ( !current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
            wp_die();
        }
        
        $data = array();
        $data['removeByCSS'] = filter_var( $_REQUEST['settings']['removeByCSS'], FILTER_VALIDATE_BOOLEAN );
        $data['removeByPHP'] = filter_var( $_REQUEST['settings']['removeByPHP'], FILTER_VALIDATE_BOOLEAN );
        $data['removeByPHPLegacy'] = filter_var( $_REQUEST['settings']['removeByPHPLegacy'], FILTER_VALIDATE_BOOLEAN );
        $data['cssCode'] = sanitize_textarea_field($_REQUEST['settings']['cssCode']);
        $data['removeDate'] = filter_var( $_REQUEST['settings']['removeDate'], FILTER_VALIDATE_BOOLEAN );
        $data['removeAuthor'] = filter_var( $_REQUEST['settings']['removeAuthor'], FILTER_VALIDATE_BOOLEAN );
        $data['targetPostTypes'] = ( isset( $_REQUEST['settings']['targetPostTypes'] ) ? $_REQUEST['settings']['targetPostTypes'] : [] );
        $data['targetPostAge'] = intval( $_REQUEST['settings']['targetPostAge'] );
        $data['targetBasedOnPostAge'] = filter_var( $_REQUEST['settings']['targetBasedOnPostAge'], FILTER_VALIDATE_BOOLEAN );
        $data['individualPostDefault'] = filter_var( $_REQUEST['settings']['individualPostDefault'], FILTER_VALIDATE_BOOLEAN );
        $data['individualPostOption'] = filter_var( $_REQUEST['settings']['individualPostOption'], FILTER_VALIDATE_BOOLEAN );
        $data['removeFromHome'] = filter_var( $_REQUEST['settings']['removeFromHome'], FILTER_VALIDATE_BOOLEAN );
        $data['excludedCategories'] = array_map( 'intval', ( isset( $_REQUEST['settings']['excludedCategories'] ) ? $_REQUEST['settings']['excludedCategories'] : [] ) );
        $data['showDebugLogs'] = filter_var( $_REQUEST['settings']['showDebugLogs'], FILTER_VALIDATE_BOOLEAN );
        $data['yoastSchemaRemoveDatePublished'] = filter_var( $_REQUEST['settings']['yoastSchemaRemoveDatePublished'], FILTER_VALIDATE_BOOLEAN );
        $data['yoastSchemaRemoveDateModified'] = filter_var( $_REQUEST['settings']['yoastSchemaRemoveDateModified'], FILTER_VALIDATE_BOOLEAN );
        $data['adminActivationNotice'] = true;
        $data['visualRemoverCSS'] = sanitize_textarea_field($_REQUEST['settings']['visualRemoverCSS']);
        update_option( 'wpmdr_settings', $data );
        wp_send_json_success( $data );
        wp_die();
    }
    
    function getDefaultOptions()
    {
        $css = ".wp-block-post-author__name{display:none !important;}\n.wp-block-post-date{display:none !important;}\n .entry-meta {display:none !important;}\r\n\t.home .entry-meta { display: none; }\r\n\t.entry-footer {display:none !important;}\r\n\t.home .entry-footer { display: none; }";
        $default = array();
        $default['removeByCSS'] = get_option( 'wpmdr_disable_css', "0" ) == "0";
        $default['removeByPHP'] = get_option( 'wpmdr_disable_php', "0" ) == "0";
        $default['removeByPHPLegacy'] = false;
        $default['cssCode'] = get_option( 'wpmdr_css', $css );
        $default['removeDate'] = get_option( 'wpmdr_remove_date', "1" ) == "1";
        $default['removeAuthor'] = get_option( 'wpmdr_remove_author', "1" ) == "1";
        $default['targetPostTypes'] = get_option( 'wpmdr_included_post_types', [ 'post' ] );
        $default['targetPostAge'] = get_option( 'wpmdr_post_age', 0 );
        $default['targetBasedOnPostAge'] = get_option( 'wpmdr_post_age', "-1" ) != "-1";
        $default['individualPostDefault'] = get_option( 'wpmdr_individual_post_default', 1 );
        $default['individualPostOption'] = get_option( 'wpmdr_individual_post', "0" ) != "0";
        $default['removeFromHome'] = true;
        $default['excludedCategories'] = get_option( 'wpmdr_excluded_categories', [] );
        $default['yoastSchemaRemoveDatePublished'] = get_option( 'wpmdr_yoast_datepublished', "0" ) != "0";
        $default['yoastSchemaRemoveDateModified'] = get_option( 'wpmdr_yoast_dateupdated', "0" ) != "0";
        $default['showDebugLogs'] = get_option( "wpmdr_debug_info", "0" ) != "0";
        $default['adminActivationNotice'] = true;
        $default['visualRemoverCSS'] = "";
        return $default;
    }
    
    function getOptions()
    {
        $data = get_option( 'wpmdr_settings' );
        
        if ( !$data ) {
            $data = $this->getDefaultOptions();
            update_option( 'wpmdr_settings', $data );
        }
        if(!isset($data['removeByPHPLegacy']))
        {
          $data['removeByPHPLegacy'] = false;
        }
        
        return $data;
    }
    
    function getSettings()
    {
        $data = $this->getOptions();
        wp_send_json_success( $data );
        wp_die();
    }
    
    function loadOptions()
    {
        $categories = get_categories();
        $postTypes = array();
        foreach ( get_post_types( array(
            'public' => true,
        ), 'object' ) as $type ) {
            array_push( $postTypes, $type );
        }
        $data = [
            'categories' => $categories,
            'postTypes'  => $postTypes,
        ];
        wp_send_json_success( $data );
        wp_die();
    }

}