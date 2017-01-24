<?php

// see https://codex.wordpress.org/Creating_Options_Pages
// and http://tutorialzine.com/2012/11/option-panel-wordpress-plugin-settings-api/



class MauticSync {

    protected $option_name = 'mautic-settings-group';

    protected $data = array(
        'mautic_url' => 'Your Mautic API URL (without /api)',
        'mautic_public_key' => 'Mautic API Public Key',
        'mautic_secret_key' => 'Mautic API Secret Key',
        'mautic_auth_info' => 'true if Mautic API details are entered'
    );

    public function __construct() {
        // create this object
        add_action('init', array($this, 'init'));

        // These hooks will handle AJAX interactions. We need to handle
        // ajax requests from both logged in users and anonymous ones:
        //add_action('wp_ajax_nopriv_tz_ajax', array($this, 'ajax'));
        //add_action('wp_ajax_tz_ajax', array($this, 'ajax'));

        // Admin sub-menu
        add_action('admin_init', array($this, 'admin_init'));
        //add_action(is_multisite() ? 'network_admin_menu' : 'admin_menu', 'add_page');
        add_action('admin_menu',  array($this, 'add_page'));

        // Listen for the activate event
        register_activation_hook(MAUTIC_FILE, array($this, 'activate'));

        // Deactivation plugin
        register_deactivation_hook(MAUTIC_FILE, array($this, 'deactivate'));
    }

    public function activate() {
        update_option($this->option_name, $this->data);
    }

    public function deactivate() {
        delete_option($this->option_name);
    }

    public function init() {
        if (preg_match('/\/' . preg_quote(MAUTIC_URL) . '\/?$/', $_SERVER['REQUEST_URI'])) {
        	// This will show the stylesheet in wp_head() in the app/index.php file
            wp_enqueue_style('stylesheet', plugins_url(MAUTIC_PATH.'app/assets/css/styles.css'));

    		// This will show the scripts in the footer
            //wp_deregister_script('jquery');
            //wp_enqueue_script('jquery', 'http://code.jquery.com/jquery-1.8.2.min.js', array(), false, true);
            wp_enqueue_script('script', plugins_url(MAUTIC_PATH.'app/assets/js/script.js'), array('jquery'), false, true);

            require MAUTIC_PATH . 'app/index.php';
            exit;
        }

    }

    // White list our options using the Settings API
    public function admin_init() {
        register_setting('mautic_options', $this->option_name, array($this, 'validate'));
        //echo "testing!\n";
    }

    // Add entry in the settings menu
    public function add_page() {
        add_options_page('Mautic Synchronisation Settings', 'Mautic Settings', 'manage_options', 'mautic_options', array($this, 'options_page'));
        // only show this option if we have well formed authentication details
        $if_auth = get_option($this->option_name['mautic_auth_info']);
        //echo "<p>if_auth = $if_auth</p>\n";
        if ($if_auth) {
            add_options_page('Mautic Authenticate', 'Mautic Auth', 'manage_options', 'mautic_auth', array($this, 'authentication_page'));
        }
    }

    // Print the menu page itself
    public function options_page() {
        $options = get_option($this->option_name);
        ?>
        <div class="wrap">
            <h2>Mautic Synchronisation Settings</h2>
            <form method="post" action="options.php">
                <?php settings_fields('mautic_options'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Mautic API Address (URL)</th>
                        <td><input type="text" name="<?php echo $this->option_name?>[mautic_url]" value="<?php
                            echo $options['mautic_url']; ?>" style="width: 18em;" />
                            <span class="description">This should be the web address of your Mautic instance, probably including an /api.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic API Public Key</th>
                        <td><input type="text" name="<?php echo $this->option_name?>[mautic_public_key]" value="<?php
                            echo $options['mautic_public_key']; ?>" style="width: 30em;" />
                            <span class="description">Is should be a string of numbers and letters <?php echo MAUTIC_KEY_SIZE ?> characters long.</span>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">Mautic API Secret Key</th>
                        <td><input type="text" name="<?php echo $this->option_name?>[mautic_secret_key]" value="<?php
                            echo $options['mautic_secret_key']; ?>" style="width: 30em;" />
                            <span class="description">Is should be a string of numbers and letters <?php echo MAUTIC_KEY_SIZE ?> characters long. Keep this one secret!</span>
                        </td>
                    </tr>

                </table>
                <?php wp_nonce_field('oeru_user_nonce', 'security'); ?>

                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                </p>
            </form>
        </div>
        <?php
    }

    // Make sure the options are valid!
    public function validate($input) {

        $valid = array();
        $valid['mautic_url'] = sanitize_text_field($input['mautic_url']);
        $valid['mautic_public_key'] = sanitize_text_field($input['mautic_public_key']);
        $valid['mautic_secret_key'] = sanitize_text_field($input['mautic_secret_key']);
        $valid['mautic_auth_info'] = false;

        echo ("<p>TESTING ".$valid['mautic_url']."</p>\n");

        if (filter_var($valid['mautic_url'], FILTER_VALIDATE_URL) !== false) {
            echo ("<p>".$valid['mautic_url']." is a valid URL!</p>\n");
        }
        else {
            add_settings_error(
                'mautic_url', 					// setting title
                'mautic_texterror',			// error ID
                'Please enter a valid Mautic API URL',		// error message
                'error'							// type of message
            );
			# Set it to the default value
			$valid['mautic_url'] = $input['mautic_url'];
        }

        $len = $this->is_valid_key($valid['mautic_public_key']);
        if ($len === true ) {
            echo ("<p>".$valid['mautic_public_key']." is a correctly formed Key!</p>\n");
        }
        else {
            add_settings_error(
                'mautic_public_key', 					// setting title
                'mautic_texterror',			// error ID
                'A Mautic Public Key must be a string of digits and letters '.MAUTIC_KEY_SIZE.' long. Yours is '.$len.' long.',		// error message
                'error'							// type of message
            );
            $valid['mautic_public_key'] = sanitize_text_field($input['mautic_public_key']);
        }

        $len2 = $this->is_valid_key($valid['mautic_secret_key']);
        if ($len2 === true) {
            echo ("<p>Your Mautic Secret Key is correctly formed</p>\n");
        }
        else {
            add_settings_error(
                'mautic_secret_key', 					// setting title
                'mautic_texterror',			// error ID
                'A Mautic Secret Key must be a string of digits and letters '.MAUTIC_KEY_SIZE.' long. Yours is '.$len2.' long.',		// error message
                'error'							// type of message
            );
            $valid['mautic_secret_key'] = sanitize_text_field($input['mautic_secret_key']);
        }

        // having got here, we now know that we have correctly formed auth details (which might not be valid)...
        $valid['mautic_auth_info'] = true;

        return $valid;
    }

    private function is_valid_key($key) {
        if (($len = strlen($key)) != MAUTIC_KEY_SIZE) {
            return $len;
        }
        return true;
    }

    public function authentication_page() {

    }

    public function mautic_auth() {
        require_once 'mautic-api-library/lib/MauticApi.php';

        foreach($this->option_name as $key => $opt) {
            echo "<p>$key: $opt\n</p>";
        }

        return true;
    }

    // This method is called when an
    // AJAX request is made to the plugin
    public function ajax() {
        $id = -1;
        $data = '';
        $verb = '';

        $response = array();

        if (isset($_POST['verb'])) {
            $verb = $_POST['verb'];
        }

        if (isset($_POST['id'])) {
            $id = (int) $_POST['id'];
        }

        if (isset($_POST['data'])) {
            $data = wp_strip_all_tags($_POST['data']);
        }

        $post = null;

        switch ($verb) {
            case 'save':
            break;

            case 'delete':
            break;
        }

        // Print the response as json and exit
        header("Content-type: application/json");
        die(json_encode($response));
    }
}
