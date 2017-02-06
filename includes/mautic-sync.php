<?php

// see https://codex.wordpress.org/Creating_Options_Pages
// and http://tutorialzine.com/2012/11/option-panel-wordpress-plugin-settings-api/



class MauticSync {

    protected $data = array(
        'mautic_url' => 'Your Mautic API URL (without /api)',
        'mautic_public_key' => 'Mautic API Public Key',
        'mautic_secret_key' => 'Mautic API Secret Key',
    );

    public function __construct() {
        // create this object
        add_action('init', array($this, 'init'));
        // Admin sub-menu
        add_action('admin_init', array($this, 'admin_init'));
        //add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', 'add_page');
        add_action('admin_menu',  array($this, 'add_page'));
        // Deactivation plugin
        register_deactivation_hook(MAUTIC_FILE, array($this, 'deactivate'));
    }

    public function deactivate() {
        // clean up our options, specified in $this->data
        foreach ($this->data as $option => $value) {
            delete_option($option);
        }
    }

    public function init() {
        // This will show the stylesheet in wp_head() in the app/index.php file
        wp_enqueue_style('stylesheet', MAUTIC_URL.'app/assets/css/styles.css');
        // registering for use elsewhere
        wp_register_script('jquery-validate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.15.0/jquery.validate.js',
            array('jquery'), true);
        // if we're on the Mautic page, add our CSS...
        if (preg_match('~\/' . preg_quote(MAUTIC_URL) . '\/?$~', $_SERVER['REQUEST_URI'])) {
            // This will show the stylesheet in wp_head() in the app/index.php file
            wp_enqueue_style('stylesheet', MAUTIC_URL.'app/assets/css/styles.css');
    		// This will show the scripts in the footer
            wp_enqueue_script('script', MAUTIC_URL.'app/assets/js/script.js', array('jquery'), false, true);
            require MAUTIC_PATH . 'app/index.php';
            exit;
        }
    }

    // White list our options using the Settings API
    public function admin_init() {
        wp_enqueue_script( 'jquery-validate');
        // embed the javascript file that makes the AJAX request
        wp_enqueue_script( 'mautic-ajax-request', MAUTIC_URL.'app/assets/js/ajax.js', array(
            'jquery',
            'jquery-form'
        ));
        // declare the URL to the file that handles the AJAX request (wp-admin/admin-ajax.php)
        wp_localize_script( 'mautic-ajax-request', 'mautic_sync_ajax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'submit_nonce' => wp_create_nonce( 'mautic-submit-nonse'),
            'auth_nonce' => wp_create_nonce( 'mautic-auth-nonse'),
        ));
        // if both logged in and not logged in users can send this AJAX request,
        // add both of these actions, otherwise add only the appropriate one
        //add_action( 'wp_ajax_nopriv_mautic_submit', 'ajax_submit' );
        add_action( 'wp_ajax_mautic_submit', array($this, 'ajax_submit'));
        add_action( 'wp_ajax_mautic_auth', array($this, 'ajax_auth'));
    }

    // Add entry in the settings menu
    public function add_page() {
        add_options_page('Mautic Synchronisation Settings', 'Mautic Settings',
            'manage_options', 'mautic_options', array($this, 'ajax_options_page'));
    }

    // Print the menu page itself
    public function ajax_options_page() {
        $options = $this->get_options();
        $nonce_submit= wp_create_nonce('mautic-submit');
        ?>
        <div class="wrap" id="mautic_sync_ajax">
            <h2>Mautic Synchronisation Settings</h2>
            <!-- <form method="post" action="options.php"> -->
            <form method="post" action="" id="mautic-sync-form">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Mautic API Address (URL)</th>
                        <td><input type="text" id="mautic-url" name="mautic-url"
                            value="<?php echo $options['mautic_url']; ?>" style="width: 30em;" /><br/>
                            <span class="description">Should be a valid web address for your Mautic instance including schema (http:// or https://) and path, e.g. /api.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic API Public Key</th>
                        <td><input type="text" id="mautic-public-key" name="mautic-public-key"
                            value="<?php echo $options['mautic_public_key']; ?>" style="width: 30em;" /><br/>
                            <span class="description">Should be a string of numbers and letters <?php echo MAUTIC_KEY_SIZE ?> characters long.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic API Secret Key</th>
                        <td><input type="text" id="mautic-secret-key" name="mautic-secret-key"
                            value="<?php echo $options['mautic_secret_key']; ?>" style="width: 30em;" /><br/>
                            <span class="description">Should be a string of numbers and letters <?php echo MAUTIC_KEY_SIZE ?> characters long. Keep this one secret!</span>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" id="mautic-submit" class="button-primary" value="Save Changes" />
                    <input type="button" id="mautic-auth" class="button-secondary" value="Test Authentication" />
                    <input type="hidden" id="mautic-submit-nonce" value="<?php echo $nonce_submit; ?>" />
                </p>
                <p id="mautic-userstatus" style="color: red">&nbsp;</p>
            </form>
        </div>
        <?php
    }

    // load saved options, if any
    public function get_options() {
        foreach ($this->data as $option => $default) {
            $this->data[$option] = ($saved = get_option($option)) ? $saved : $default;
        }
        return $this->data;
    }

    // the ajax form should only ever return valid options
    public function save_valid_options() {
        if ($_POST && isset($_POST['url']) && isset($_POST['public_key']) && isset($_POST['secret_key'])) {
            // grab saved values from Ajax form
            $this->data['mautic_url'] = sanitize_text_field($_POST['url']);
            $this->data['mautic_public_key'] = sanitize_text_field($_POST['public_key']);
            $this->data['mautic_secret_key'] = sanitize_text_field($_POST['secret_key']);
            // save values
            foreach ($this->data as $opion => $value) {
                errorlog("updating $option to $value");
                if (!update_option($option, $value, 'yes')) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    // called when the ajax form is successfully submitted
    public function ajax_submit() {
        // get the submitted parameters
        $nonce = $_POST['nonce-submit'];
        // check if the submitted nonce matches the generated nonce created in the auth_init functionality
        if ( ! wp_verify_nonce( $nonce, 'submit-nonse') ) {
            die ("Busted in submit!");
        }

        // otherwise, update the options
        $success=false;
        if ($this->save_valid_options()) {
            $success=true;
        }

        // generate the response
        $response = json_encode( array( 'success' => $success ) );
        // response output
        header( "Content-Type: application/json" );
        echo $response;
        // IMPORTANT: don't forget to "exit"
        exit;
    }
/*
    public function ajax_auth() {
        // get the submitted parameters
        $nonce = $_POST['authNonce'];

        // check if the submitted nonce matches the generated nonce created in the auth_init functionality
        if ( ! wp_verify_nonce( $nonce, 'mautic-auth-nonse') ) {
            die ("Busted in auth!");
        }

        // generate the response
        $response = json_encode( array( 'success' => true ) );

        // response output
        header( "Content-Type: application/json" );
        echo $response;
        // IMPORTANT: don't forget to "exit"
        exit;
    }
*/
/*    public function mautic_auth() {
        require_once 'mautic-api-library/lib/MauticApi.php';

        foreach($this->option_name as $key => $opt) {
            echo "<p>$key: $opt\n</p>";
        }

        return true;
    }*/
}
