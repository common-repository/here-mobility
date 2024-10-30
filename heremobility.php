<?php
require_once 'heremobility_settings.php';

/**
 * Plugin Name: HERE Mobility Web Widget
 * Plugin URI: https://mobility.here.com/
 * Description: The HERE Mobility Web Widget is a customizable transportation booking widget, that you can quickly add to your website, to offer your users ride booking and navigation.
The Web Widget supports pickup location, destination, and required departure time, in addition, your users can also  customize their ride according to any special requirements they have from suitcases to number of passengers.
The HERE Mobility Web Widget connects to the Mobility Marketplace to find the most relevant rides matching your users' requests and displays them to your users to choose from and book. To activate your Web Widget, please create an account(https://developer.mobility.here.com/signup) and request your app credentials.
 * Version: 1.0.18
 * Author: HERE Mobility
 * Author URI: https://mobility.here.com/
 * License: GPLv2 or later
 * Text Domain: heremobility
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
class HereMobility
{
    const PLUGIN_VERSION = '1.0.18';
    const SHORTCODE_WIDGET_TAG = 'here_panel';
    const SHORTCODE_LINK_TAG = 'here_link';
    const SHORTCODE_BUTTON_TAG = 'here_button';
    const GET_AUTH_ACTION = 'get_auth_action';
    const WIDGET_URL = 'https://web-widget.mobility.here.com/widget.js';

    private $autoCompleteSettings = array(
        'suggestUrl' => 'https://places.api.here.com/places/v1/autosuggest',
        'appId' => 'qfXhceMnNGqjczjYpdun',
        'appCode' => 'k9_zXXajLc5oltB-eC9ceA',
        'acceptLanguage' => 'en-US;q=0.9'
    );

    /**
     * __construct
     * class constructor will set the needed filter and action hooks
     *
     * @param array $args
     */
    function __construct($args = array())
    {
        if (is_admin()) {
            add_action('admin_head', array($this, 'admin_head'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            HereMobility_Settings::init();
        }

        $this->register_block_type_function();

        // Register gutenberg block script
        add_action('enqueue_block_editor_assets', array($this, 'here_mobility_gutenberg_widget_assets'));

        //add shortcode
        add_shortcode(self::SHORTCODE_WIDGET_TAG, array($this, 'widget_shortcode_handler'));
        add_shortcode(self::SHORTCODE_BUTTON_TAG, array($this, 'widget_buttons_shortcode_handler'));
        add_shortcode(self::SHORTCODE_LINK_TAG, array($this, 'link_shortcode_handler'));

        add_action( 'wp_ajax_get_auth_action', array($this, self::GET_AUTH_ACTION));
        add_action( 'wp_ajax_nopriv_get_auth_action', array($this, self::GET_AUTH_ACTION));

        register_deactivation_hook(__FILE__, array($this, 'here_deactivation'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    function get_auth_action($data)
    {
        $options = get_option(HereMobility_Settings::HERE_OPTIONS);

        if (!isset($_POST['application_key'])) {
            wp_die();
        }

        if ($options[HereMobility_Settings::USE_CURRENT_WP_USER] && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user = $current_user->user_email;
        } else {
            $user = $this->generateRandomUser();
        }

        if (($options[HereMobility_Settings::APP_KEY_OPTION] && $options[HereMobility_Settings::APP_SECRET_OPTION])
            && $_POST['application_key'] == $options[HereMobility_Settings::APP_KEY_OPTION]) {
            $options = get_option(HereMobility_Settings::HERE_OPTIONS);
            $appKey = $options[HereMobility_Settings::APP_KEY_OPTION];
            $appSecret = $options[HereMobility_Settings::APP_SECRET_OPTION];

            $authData = $this->get_auth_data($appKey, $appSecret, $user);
            echo json_encode($authData);
        } elseif (!empty($_POST['post_id'])  && !empty($_POST['application_key']) && $content_post = get_post($_POST['post_id'])){
            $content = htmlspecialchars_decode($content_post->post_content);

            preg_match('/appSecret="([^"]+)"/i', $content, $matchesTinyMce);
            preg_match('/"appSecret":"([^"]+)"/i', $content, $matchesGutenBerg);

            $appSecret = isset($matchesTinyMce[1]) ? $matchesTinyMce[1] : (isset($matchesGutenBerg[1]) ? $matchesGutenBerg[1] : null);

            if ($appSecret) {
                $authData = $this->get_auth_data($_POST['application_key'], $appSecret, $user);
                echo json_encode($authData);
            }
        }

        wp_die();
    }

    function register_block_type_function()
    {
        if ( function_exists( 'register_block_type' ) ) {
            // Hook server side rendering into render callback
            register_block_type(
                'here/mobility-widget', [
                    'render_callback' => array($this, 'widget_shortcode_handler'),
                    'attributes' => array(
                        'id' => array(
                            'type' => 'string',
                        ),
                        'pickup' => array(
                            'type' => 'string',
                        ),
                        'destination' => array(
                            'type' => 'string',
                        ),
                        'appKey' => array(
                            'type' => 'string',
                        ),
                        'appSecret' => array(
                            'type' => 'string'
                        ),
                        'localization' => array(
                            'type' => 'string'
                        )
                    ),
                ]
            );

            register_block_type(
                'here/mobility-button', [
                    'render_callback' => array($this, 'widget_buttons_shortcode_handler'),
                    'attributes' => array(
                        'id' => array(
                            'type' => 'string',
                        ),
                        'pickup' => array(
                            'type' => 'string',
                        ),
                        'destination' => array(
                            'type' => 'string',
                        ),
                        'appKey' => array(
                            'type' => 'string',
                        ),
                        'appSecret' => array(
                            'type' => 'string'
                        ),
                        'localization' => array(
                            'type' => 'string'
                        )
                    ),
                ]
            );

            register_block_type(
                'here/widget-link', [
                    'render_callback' => array($this, 'link_shortcode_handler'),
                    'attributes' => array(
                        'id' => array(
                            'type' => 'string',
                        ),
                        'pickup' => array(
                            'type' => 'string',
                        ),
                        'destination' => array(
                            'type' => 'string',
                        ),
                    ),
                ]
            );
        }
    }

    function add_action_links($links)
    {
        $mylinks = array(
            '<a href="' . admin_url('admin.php?page=' . HereMobility_Settings::HERE_SETTINGS_PAGE) . '">Settings</a>',
        );
        return array_merge($links, $mylinks);
    }

    function here_deactivation()
    {
        delete_option(HereMobility_Settings::HERE_OPTIONS);
    }

    function generateRandomUser()
    {
        return "user_" . bin2hex(openssl_random_pseudo_bytes(5)) . "@here.com";
    }

    /**
     * shortcode_handler
     * @param  array $attrs shortcode attributes
     * @return string
     */
    function widget_buttons_shortcode_handler($attrs)
    {
        if ($this->init_widget_script($attrs)) {
            $attrs = array_merge(
                array(
                    'buttonType' => '',
                    'buttonText' => '',
                    'buttonPosition' => ''
                ),
                array_change_key_case($attrs, CASE_LOWER)
            );

            switch($attrs['buttontype']) {
                case 'rectangle':
                case 'text':
                    $text = empty($attrs['buttontext']) ? 'Book a ride' : $attrs['buttontext'];
                    $resultHtml = sprintf('<div class="btn-%s"><div>%s</div></div>', $attrs['buttontype'], $text);
                    $this->load_widget_button([
                        'button_class' => '.btn-' . $attrs['buttontype'],
                        'type' => 'modal'
                    ]);
                    break;
                case 'round':
                default:
                    $resultHtml = '<button class="btn-round"></button>';
                    $this->load_widget_button([
                        'button_class' => '.btn-round',
                        'type' => 'float'
                    ]);
                    break;
            }

            switch ($attrs['buttonposition']) {
                case 'left':
                    $resultHtml = sprintf("<div class='wordpress-btn-container' style='float:left;'>{$resultHtml}<div style='clear:both;'></div></div>");
                    break;
                case 'center':
                    $resultHtml = sprintf("<div class='wordpress-btn-container'><div style='display: table; margin: auto;'>{$resultHtml}</div></div>");
                    break;
                case 'right':
                default:
                    $resultHtml = sprintf("<div class='wordpress-btn-container' style='float: right;'>{$resultHtml}</div><div style='clear:both;'></div>");
                    break;
            }

            $resultHtml .= sprintf("<div class='wordpress-hmw-container' style='display:none;'><div class='wordpress-hmw' id='%s'></div><div id='table-booking-date'></div></div>",
                $attrs['id']);


            return $resultHtml;
        }

        if (current_user_can('editor') || current_user_can('administrator')) {
            return "<p>[Error] Please set App Key and App Secret on <a href='"
                . admin_url('admin.php?page=here_settings') . "'>HERE Widget Settings Page</a>.</p>";
        }

        return '';
    }

    /**
     * shortcode_handler
     */
    function init_widget_script($attrs)
    {

        // Attributes
        $attrs = array_merge(
            array(
                'pickup' => '',
                'destination' => '',
                'id' => '',
                'appkey' => '',
                'appsecret' => '',
                'localization' => ''
            ),
            array_change_key_case($attrs, CASE_LOWER)
        );

        $options = get_option(HereMobility_Settings::HERE_OPTIONS);

        if (!$attrs['id']) {
            return false;
        }

        if (($options[HereMobility_Settings::APP_KEY_OPTION] && $options[HereMobility_Settings::APP_SECRET_OPTION])
            || ($attrs['appkey'] && $attrs['appsecret'])) {
            $options = get_option(HereMobility_Settings::HERE_OPTIONS);
            $appKey = $attrs['appkey'] ?: $options[HereMobility_Settings::APP_KEY_OPTION];
            $appSecret = $attrs['appsecret'] ?: $options[HereMobility_Settings::APP_SECRET_OPTION];

            if ($options[HereMobility_Settings::USE_CURRENT_WP_USER] && is_user_logged_in()) {
                $current_user = wp_get_current_user();
                $user = $current_user->user_email;
            } else {
                $user = $this->generateRandomUser();
            }

            $this->load_widget_scripts([
                'id' => $attrs['id'],
                'destination' => $attrs['destination'] ?: $options[HereMobility_Settings::DEFAULT_DESTINATION_OPTION],
                'pickup' => $attrs['pickup'] ?: $options[HereMobility_Settings::DEFAULT_PICKUP_OPTION],
                'widgetUrl' => self::WIDGET_URL,
                'appKey' => $appKey,
                'locale' => $attrs['localization'] ?: $options[HereMobility_Settings::LOCALIZATION_OPTION],
                'authData' => $this->get_auth_data($appKey, $appSecret, $user),
                'auth_ajax_url' => admin_url('admin-ajax.php'),
                'auth_ajax_action' => self::GET_AUTH_ACTION,
                'post_id' => get_the_ID()
            ]);

            return true;
        }

        return false;
    }

    /**
     * shortcode_handler
     * @param  array $attrs shortcode attributes
     * @param  string $content shortcode content
     * @return string
     */
    function widget_shortcode_handler($attrs)
    {
        // Attributes
        $attrs = array_merge(
            array(
                'id' => '',
                'width' => '',
                'height' => '',
                'unit' => 'px',
                'position' => 'center'
            ),
            array_change_key_case($attrs, CASE_LOWER)
        );


        if ($this->init_widget_script($attrs)) {

            if ($attrs['width'] || $attrs['height']) {
                $unit = $attrs['unit'] ? : 'px';
                $clear = '';
                $positionAttr = $attrs['position'] ? : 'center';

                switch ($positionAttr) {
                    case 'left':
                        $position = 'float: left;';
                        $clear = 'style="clear:both;"';
                        break;
                    case 'right':
                        $position = 'float: right;';
                        $clear = 'style="clear:both;"';
                        break;
                    default:
                        $position = 'margin: 0 auto;';
                }

                $width = $attrs['width'] ? "width:{$attrs['width']}{$unit};" : '';
                $height = $attrs['height'] ? "height:{$attrs['height']}{$unit};" : '';
                $style = "{$width}{$height}{$position}";

                return sprintf("<div class='wordpress-hmw-container'><div class='wordpress-hmw' id='%s' style='%s'></div><div id='table-booking-date' %s></div></div>",
                    $attrs['id'], $style, $clear);
            } else {
                return sprintf("<div id='wordpress-hmw-container'><div class='wordpress-hmw' id='%s'></div><div id='table-booking-date'></div></div>", $attrs['id']);
            }
        }

        if (current_user_can('editor') || current_user_can('administrator')) {
            return "<p>[Error] Please set App Key and App Secret on <a href='"
                . admin_url('admin.php?page=here_settings') . "'>HERE Widget Settings Page</a>.</p>";
        }

        return '';
    }

    function get_auth_data($appKey, $appSecret, $user)
    {
        $timeNow = time();

        $hash = hash_hmac(
            'sha384',
            implode(
                '.',
                array(
                    base64_encode($appKey),
                    $timeNow
                )
            ),
            $appSecret
        );

        return array(
            'app_id' => $appKey,
            'user_id' => $user,
            'request_timestamp' => $timeNow,
            'hmac' => $hash
        );
    }

    /**
     * shortcode_handler
     * @param  array $atts shortcode attributes
     * @param  string $content shortcode content
     * @return string
     */
    function link_shortcode_handler($attrs, $content = null)
    {
        // Attributes
        $attrs = shortcode_atts(
            array(
                'destination' => '',
                'pickup' => '',
                'id' => ''
            ), $attrs);

        if ($attrs['id']) {
            return "<em class='here-destination' data-id='{$attrs['id']}' data-destination='{$attrs['destination']}' data-pickup='{$attrs['pickup']}'>{$content}</em>";
        }

        //return shortcode output
        return '';
    }

    function load_widget_button($data)
    {
        wp_enqueue_script('tingle_js', plugins_url('js/tingle.min.js', __FILE__), array('here_loader'), self::PLUGIN_VERSION, TRUE);
        wp_enqueue_script('tingle_loader', plugins_url('js/tingleLoader.js', __FILE__), array('tingle_js'), self::PLUGIN_VERSION, TRUE);
        wp_localize_script('tingle_loader', 'tingleData', $data);
        wp_enqueue_style('tingle_css', plugins_url('css/tingle.min.css', __FILE__));
        wp_enqueue_style('tingle_loader', plugins_url('css/tingleLoader.css', __FILE__));
    }

    function load_widget_scripts($data)
    {
        wp_enqueue_script('here_loader', plugins_url('js/loader.js', __FILE__), array('jquery'), self::PLUGIN_VERSION, TRUE);
        wp_localize_script('here_loader', 'hereLoaderData', $data);
        wp_enqueue_style('wordpress_hmw', plugins_url('css/wordpress-hmw.css', __FILE__));
    }

    /**
     * admin_head
     * calls your functions into the correct filters
     * @return void
     */
    function admin_head()
    {
        // check user permissions
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        // check if WYSIWYG is enabled
        if ('true' == get_user_option('rich_editing')) {
            add_filter('mce_external_plugins', array($this, 'mce_external_plugins'));
            add_filter('mce_buttons', array($this, 'mce_buttons'));
        }

    }

    /**
     * mce_external_plugins
     * Adds our tinymce plugin
     * @param  array $plugin_array
     * @return array
     */
    function mce_external_plugins($plugin_array)
    {
        $plugin_array[self::SHORTCODE_WIDGET_TAG] = plugins_url('js/here-widget-button.js', __FILE__);
        $plugin_array[self::SHORTCODE_LINK_TAG] = plugins_url('js/here-link-button.js', __FILE__);
        $plugin_array[self::SHORTCODE_BUTTON_TAG] = plugins_url('js/here-button.js', __FILE__);
        return $plugin_array;
    }

    /**
     * mce_buttons
     * Adds our tinymce button
     * @param  array $buttons
     * @return array
     */
    function mce_buttons($buttons)
    {
        array_push($buttons, self::SHORTCODE_WIDGET_TAG);
        array_push($buttons, self::SHORTCODE_LINK_TAG);
        array_push($buttons, self::SHORTCODE_BUTTON_TAG);
        return $buttons;
    }

    /**
     * admin_enqueue_scripts
     * Used to enqueue custom styles
     * @return void
     */
    function admin_enqueue_scripts()
    {
        add_editor_style( plugins_url('css/mce-here-button.css', __FILE__) );

        wp_enqueue_style('HereMobility_Settings', plugins_url('css/here-settings.css', __FILE__));
        wp_enqueue_style('here_panel_shortcode', plugins_url('css/mce-here-button.css', __FILE__));
        wp_enqueue_style('here_autocomplete', plugins_url('css/auto-complete.css', __FILE__));

        wp_enqueue_script('here_autocomplete', plugins_url('js/autocomplete.js', __FILE__), array('jquery'), self::PLUGIN_VERSION, TRUE);
        wp_localize_script('here_autocomplete', 'autocompleteSettings', $this->autoCompleteSettings);

        wp_enqueue_script('HereMobility_Settings', plugins_url('js/here-settings.js', __FILE__), array('jquery', 'here_autocomplete'), self::PLUGIN_VERSION, TRUE);
    }

    function here_mobility_gutenberg_widget_assets()
    {
        // Scripts.
        wp_enqueue_script(
            'here-mobility-widget',
            plugins_url('js/gutenberg-here-widget.js', __FILE__),
            array('wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element', 'jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'gutenberg-here-widget.js')
        );

        wp_localize_script('here-mobility-widget', 'hereSettings', array(
            'widgetImageUrl' => plugins_url("js/img/widget.png", __FILE__),
            'autocomplete' => array(
                'suggestUrl' => 'https://places.api.here.com/places/v1/autosuggest',
                'appId' => 'qfXhceMnNGqjczjYpdun',
                'appCode' => 'k9_zXXajLc5oltB-eC9ceA',
                'acceptLanguage' => 'en-US;q=0.9'
            )
        ));

        // Styles.
        wp_enqueue_style(
            'here/mobility-widget-editor',
            plugins_url('css/gutenberg-here-block-editor.css', __FILE__),
            array('wp-edit-blocks'),
            filemtime(plugin_dir_path(__FILE__) . 'editor.css')
        );
    }
}

new HereMobility();
