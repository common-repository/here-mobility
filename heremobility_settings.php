<?php

class HereMobility_Settings
{
    const APP_KEY_OPTION = 'app_key';
    const APP_SECRET_OPTION = 'app_secret';
    const DEFAULT_PICKUP_OPTION = 'default_pickup';
    const DEFAULT_DESTINATION_OPTION = 'default_destination';
    const LOCALIZATION_OPTION = 'localization';
    const HERE_OPTIONS = 'here_options';
    const HERE_SETTINGS_PAGE = 'heremobility';
    const USER_EMAIL_OPTION = 'user';
    const USE_CURRENT_WP_USER = 'use_current_wp_user';

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function init()
    {
        $static = new self();

        add_action('admin_init', array($static, 'here_settings_init'));
        add_action('admin_menu', array($static, 'here_options_page'));
    }

    function here_options_page()
    {
        // add top level menu page
        add_menu_page(
            __('HERE Mobility Settings', 'heremobility'),
            __('HERE Mobility Settings', 'heremobility'),
            'manage_options',
            self::HERE_SETTINGS_PAGE,
            array($this, 'here_options_page_html'),
            'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNCIgaGVpZ2h0PSIxNCIgdmlld0JveD0iMCAwIDE0IDE0Ij48ZyBmaWxsPSIjOUVBM0E4IiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxwYXRoIGQ9Ik0xMS43OCA3LjA0N2E0LjgyNyA0LjgyNyAwIDEgMS05LjY1NSAwIDQuODI3IDQuODI3IDAgMCAxIDkuNjU1IDBNNyAwYTcgNyAwIDAgMC03IDd2N2g3QTcgNyAwIDEgMCA3IDAiLz48cGF0aCBkPSJNNyA0YTMgMyAwIDAgMC0yLjg4OCAzLjgxNmwxLjg0Ny0uNTIyYTEuMDgyIDEuMDgyIDAgMSAxIC43Ni43NWwtLjUgMS44NTNBMyAzIDAgMSAwIDcgNCIvPjwvZz48L3N2Zz4='
        );
    }

    function here_options_page_html()
    {
        // check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_GET['settings-updated']) && !get_settings_errors(self::HERE_OPTIONS)) {
            // add settings saved message with the class of "updated"
            add_settings_error(self::HERE_OPTIONS, 'here_message', __('Settings Saved', 'heremobility'), 'updated');
        }
        ?>
        <div class="here-settings-wrap">
            <div id="here-settings-logo"></div>
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php
            // show error/update messages
            settings_errors(self::HERE_OPTIONS);
            ?>
            <form action="options.php" method="post">
                <?php
                // output security fields for the registered setting
                settings_fields(self::HERE_SETTINGS_PAGE);
                // output setting sections and their fields
                // (sections are registered for "heremobility", each field is registered to a specific section)
                do_settings_sections(self::HERE_SETTINGS_PAGE);
                // output save settings button
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    function here_settings_init()
    {
        // register a new setting for "heremobility" page
        register_setting(self::HERE_SETTINGS_PAGE, self::HERE_OPTIONS, array($this, 'here_sanitize_callback'));

        $sectionCredentials = 'here_settings_section_credentials';
        $sectionUser = 'here_settings_section_user';
        $sectionDefault = 'here_settings_section_default';
        $sectionLocale = 'here_settings_section_locale';

        // register a new section in the "heremobility" page
        add_settings_section(
            $sectionCredentials,
            __('Credentials', 'heremobility'),
            array($this, 'here_section_developers_cb'),
            self::HERE_SETTINGS_PAGE
        );

        add_settings_section(
            $sectionUser,
            __('User ID', 'heremobility'),
            array($this, 'here_section_developers_cb'),
            self::HERE_SETTINGS_PAGE
        );

        add_settings_section(
            $sectionDefault,
            __('Default options customization', 'heremobility'),
            array($this, 'here_section_developers_cb'),
            self::HERE_SETTINGS_PAGE
        );

        add_settings_section(
            $sectionLocale,
            __('Language (Optional)', 'heremobility'),
            array($this, 'here_section_developers_cb'),
            self::HERE_SETTINGS_PAGE
        );

        // register a new field in the "here_settings_section" section, inside the "here_settings" page
        add_settings_field(
            self::APP_SECRET_OPTION,
            '',
            array($this, 'setting_cb'),
            self::HERE_SETTINGS_PAGE,
            $sectionCredentials,
            array(
                'id' => self::APP_SECRET_OPTION,
                'title' => __('', 'heremobility'),
                'placeholder' => __('App Secret', 'heremobility'),
                'classes' => 'here-row',
                'type' => 'password'
            )
        );

        add_settings_field(
            self::APP_KEY_OPTION,
            '',
            array($this, 'setting_cb'),
            self::HERE_SETTINGS_PAGE,
            $sectionCredentials,
            array(
                'id' => self::APP_KEY_OPTION,
                'title' => __('', 'heremobility'),
                'placeholder' => __('App Key', 'heremobility'),
                'classes' => 'here-row',
                'type' => 'text',
                'here_custom_data' => 'custom'
            )
        );

        add_settings_field(
            self::USE_CURRENT_WP_USER,
            '',
            array($this, 'setting_cb'),
            self::HERE_SETTINGS_PAGE,
            $sectionUser,
            array(
                'id' => self::USE_CURRENT_WP_USER,
                'classes' => 'here-radio',
                'values' => array(
                    array(
                        'value' => false,
                        'label' => 'Generate a random userID',
                        'tooltip_text' => 'choose this option if your users are not logged in and we will autogenerate a random ID to proceed to the authentication'
                    ),
                    array(
                        'value' => true,
                        'label' => 'Use wordpress login name as the UserID',
                        'tooltip_text' => 'choose this option if your users are logged in and we will use your users ID (email or phone number) to proceed to the authentication'
                    )
                ),
                'type' => 'radio'
            )
        );

        add_settings_field(
            self::DEFAULT_PICKUP_OPTION,
            '',
            array($this, 'setting_cb'),
            self::HERE_SETTINGS_PAGE,
            $sectionDefault,
            array(
                'id' => self::DEFAULT_PICKUP_OPTION,
                'title' => __('', 'heremobility'),
                'placeholder' => __('Pickup', 'heremobility'),
                'classes' => 'here-row',
                'type' => 'text'
            )
        );

        add_settings_field(
            self::DEFAULT_DESTINATION_OPTION,
            '',
            array($this, 'setting_cb'),
            self::HERE_SETTINGS_PAGE,
            $sectionDefault,
            array(
                'id' => self::DEFAULT_DESTINATION_OPTION,
                'title' => __('', 'heremobility'),
                'placeholder' => __('Destination', 'heremobility'),
                'classes' => 'here-row',
                'type' => 'text'
            )
        );

        add_settings_field(
            self::LOCALIZATION_OPTION,
            '',
            array($this, 'setting_cb'),
            self::HERE_SETTINGS_PAGE,
            $sectionLocale,
            array(
                'id' => self::LOCALIZATION_OPTION,
                'title' => __('', 'heremobility'),
                'classes' => 'here-select',
                'type' => 'select',
                'options' => [
                    'en-US' => 'English',
                    'fr-FR' => 'French',
                    'nl-NL' => 'Dutch',
                    'fi-FI' => 'Finnish',
                    'es-ES' => 'Spanish',
                    'el-GR' => 'Greek',
                    'pt-BR' => 'Portuguese'
                ]
            )
        );
    }

    function here_sanitize_callback($value)
    {
        $message = '';
        $error = false;

        if (empty($value[self::APP_KEY_OPTION])) {
            $error = true;
            $message .= __('App Key is required.</br>', 'heremobility');
        }

        if (empty($value[self::APP_SECRET_OPTION])) {
            $error = true;
            $message .= __('App Secret is required.</br>', 'heremobility');
        }

        if ($error) {
            $message = __('Settings were not saved.</br>', 'heremobility') . $message;
            add_settings_error(self::HERE_OPTIONS, 'here_message', $message, 'error');
            return get_option(self::HERE_OPTIONS);
        }

        return $value;
    }

    function setting_cb($args)
    {
        // get the value of the setting we've registered with register_setting()
        $options = get_option(self::HERE_OPTIONS);
        // output the field
        switch ($args['type']) {
            case 'hidden':?>
                <input type="hidden"
                       id="<?php echo esc_attr($args['id']); ?>"
                       name="here_options[<?php echo esc_attr($args['id']); ?>]"
                       value="<?php echo $options[$args['id']]; ?>" />
                <?php
                break;
            case 'checkbox':?>
                <input type="checkbox"
                       id="<?php echo esc_attr($args['id']); ?>"
                       name="here_options[<?php echo esc_attr($args['id']); ?>]"
                       value="1"
                       <?php echo  $options[$args['id']] ? 'checked': '';?> />
                <label class="label-checkbox  <?php echo esc_attr($args['id']); ?>" for="<?php echo esc_attr($args['id']); ?>">
                    <?php echo esc_attr($args['title']); ?>
                </label>
            <?php
                break;
            case 'password':?>
                <label class="label-text  <?php echo esc_attr($args['id']); ?>" for="<?php echo esc_attr($args['id']); ?>">
                    <?php echo esc_attr($args['title']); ?>
                </label>
                <input type="password"
                       id="<?php echo esc_attr($args['id']); ?>"
                       class="<?php echo $args['classes']; ?>"
                       placeholder="<?php echo $args['placeholder']; ?>"
                       name="here_options[<?php echo esc_attr($args['id']); ?>]"
                       value="<?php echo $options[$args['id']]; ?>"
                />
                <span toggle="#<?php echo esc_attr($args['id']); ?>" class="here-settings-show toggle-password">Show</span>
                <?php
                break;
            case 'text':?>
                <label class="label-text  <?php echo esc_attr($args['id']); ?>" for="<?php echo esc_attr($args['id']); ?>">
                    <?php echo esc_attr($args['title']); ?>
                </label>
                <input type="text"
                       id="<?php echo esc_attr($args['id']); ?>"
                       class="<?php echo $args['classes']; ?>"
                       placeholder="<?php echo $args['placeholder']; ?>"
                       name="here_options[<?php echo esc_attr($args['id']); ?>]"
                       value="<?php echo $options[$args['id']]; ?>"
                />
                <?php
                break;
            case 'radio':
                foreach ($args['values'] as $key => $radio) {
                ?>
                    <p class="radio-container radio-container-<?php echo  $options[$args['id']] == $radio['value'] ? 'active': 'disabled';?>">
                        <input type="radio" id="<?php echo esc_attr($args['id']) . '_' . $key; ?>"
                            <?php echo  $options[$args['id']] == $radio['value'] ? 'checked': '';?>
                               name="here_options[<?php echo esc_attr($args['id']); ?>]" value="<?php echo $radio['value']?>"/>
                        <label for="<?php echo esc_attr($args['id']) . '_' . $key; ?>"><?php echo $radio['label']?><i class="here-settings-tooltip"><span class="tooltiptext"><?php echo $radio['tooltip_text']?></span></i></label>
                    </p>
                <?php
                }
                break;
            case 'select':
                $selected = isset($options[$args['id']]) ? $options[$args['id']] : $args['default'];

                ?>
                <label class="label-select <?php echo esc_attr($args['id']); ?>" for="<?php echo esc_attr($args['id']); ?>">
                    <?php echo esc_attr($args['title']); ?>
                </label>


                <div class="select-dropdown">
                    <select id="<?php echo esc_attr($args['id']); ?>"
                            class="<?php echo $args['classes']; ?>"
                            name="here_options[<?php echo esc_attr($args['id']); ?>]"
                            value="<?php echo $options[$args['id']]; ?>">
                        <?php
                        foreach ($args['options'] as $optionKey => $optionTitle) {
                            ?>
                            <option value="<?php echo $optionKey;?>"
                                <?php echo $optionKey == $selected ? 'selected="selected"' : '';?>
                            >
                                <?php echo $optionTitle;?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <i></i>
                </div>
                <?php
                break;
        }
    }

    function here_section_developers_cb($args)
    {
        ?>
        <p id="<?php echo esc_attr($args['id']); ?>"></p>
        <?php
    }
}
