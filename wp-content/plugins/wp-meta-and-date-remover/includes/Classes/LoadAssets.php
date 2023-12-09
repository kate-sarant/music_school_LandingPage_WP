<?php
namespace WPMDRMain\Classes;

class LoadAssets
{
    public function enqueueAssets()
    {
        wp_enqueue_script('WPMDR-script-boot', WPMDR_URL . 'assets/js/start.js', array('jquery'), WPMDR_VERSION, false);
        wp_enqueue_style('WPMDR-global-styling', WPMDR_URL . 'assets/css/start.css', array(), WPMDR_VERSION);
    }

    public function dashboardScripts($yoastDatePreviewRemove)
    {
        if ($yoastDatePreviewRemove) {
            wp_enqueue_style( 'WPMDR-yoast-date-preview-css',WPMDR_URL . 'assets/css/admin/yoast.css' );
        }
        wp_enqueue_script( 'WPMDR-yoast-date-preview-js', WPMDR_URL . 'assets/js/admin/yoast.js', [ 'jquery' ] );
        wp_localize_script( 'WPMDR-yoast-date-preview-js', 'obj', [
            'upgradeUrl'      => wpmdr_fs()->get_upgrade_url(),
            'isPaying'        => !wpmdr_fs()->is_not_paying(),
            'isYoastRemoveEnabled' => $yoastDatePreviewRemove,
        ] );
    }
    

}
