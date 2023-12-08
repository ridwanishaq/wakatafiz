<?php

/**
 * Plugin Name:         WakataFiz
 * Plugin URI:          https://github.com/ridwanishaq/wakatafiz
 * Description:         A word count plugin for single post statistics.
 * Version:             1.0.2
 * Requires at least:   5.3.1
 * Requires PHP:        7.4
 * License:             GPL v2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:          https://github.com/ridwanishaq/wakatafiz
 * Author:              Rilwanu Isyaku
 * Author URI:          https://github.com/ridwanishaq
 * Text Domain:         wakatafizdomain
 * Domain Path:         /languages
 * 
 */

class WakataFiz {
    
    public function __construct()
    {
        add_action('admin_menu', array($this, 'wfiz_setting_link'));
        add_action('admin_init', array($this, 'settings'));
        add_filter('the_content', array($this, 'ifWrap'), 10, 1);
        add_action('init', array($this, 'languages'));
    }

    public function languages() {
        load_plugin_textdomain( 'wakatafizdomain', false, dirname(plugin_basename( __FILE__ )) . '/languages');
    }

    public function ifWrap($content) {
        if( is_main_query() && is_single() && 
        (
            get_option('wfiz_wordcount', '1') ||
            get_option('wfiz_charactercount', '1') || 
            get_option('wfiz_readtime', '1')
        )) {
            return $this->createHTML($content);
        }
        return $content;
    }

    public function createHTML($content) {
        $html = '<h3>'. esc_html(get_option('wfiz_headline', 'Post Statistics')) .'</h3><p>';

        // get word count once because both wordcount and read time will need it.
        if(get_option('wfiz_wordcount', '1') || get_option( 'wfiz_readtime', '1' )) {
            $wordCount = str_word_count(strip_tags($content));
        }

        if(get_option('wfiz_wordcount', '1')) {
            $html .= esc_html__('This post has', 'wakatafizdomain'). ' '. $wordCount . ' ' . esc_html__('words', 'wakatafizdomain') . '.<br/>';
        }

        if(get_option('wfiz_charactercount', '1')) {
            $html .= 'This post has ' . strlen(strip_tags($content)) . ' characters.<br/>';
        }

        if(get_option('wfiz_readtime', '1')) {
            $html .= 'This post will take about ' . round($wordCount/255) . ' minute(s) to read.<br/>';
        }

        $html  .= '</p>';

        if(get_option('wfiz_location', '0') == '0') {
            return $html . $content;
        }
        return $content . $html;
    }

    public function settings() {
        add_settings_section(
            'wfiz_first_section', 
            null, 
            null, 
            'wfiz-word-count-settings-page'
        );
        
        // Location Field
        add_settings_field(
            'wfiz_location', 
            'Display Location', 
            array($this, 'locationHTML'), 
            'wfiz-word-count-settings-page', 
            'wfiz_first_section'
        );
        register_setting(
            'wordcountplugin', 
            'wfiz_location', 
            array(
                'sanitize_callback' => array($this, 'sanitizeLocation'), 
                'default' => '0'
            )
        );

        // Headline Field
        add_settings_field(
            'wfiz_headline', 
            'Headline Text', 
            array($this, 'headlineHTML'), 
            'wfiz-word-count-settings-page', 
            'wfiz_first_section'
        );
        register_setting(
            'wordcountplugin', 
            'wfiz_headline', 
            array(
                'sanitize_callback' => 'sanitize_text_field', 
                'default' => 'Post Statistic'
            )
        );

        // Word Count
        add_settings_field(
            'wfiz_wordcount', 
            'Word count', 
            array($this, 'checkboxHTML'), 
            'wfiz-word-count-settings-page', 
            'wfiz_first_section', 
            array('theName' => 'wfiz_wordcount')
        );
        register_setting(
            'wordcountplugin', 
            'wfiz_wordcount', 
            array(
                'sanitize_callback' => 'sanitize_text_field', 
                'default' => '1'
            )
        );

        // Character Count
        add_settings_field(
            'wfiz_charactercount', 
            'Character count', array($this, 'checkboxHTML'), 
            'wfiz-word-count-settings-page', 
            'wfiz_first_section', 
            array(
                'theName' => 'wfiz_charactercount'
            )
        );
        register_setting(
            'wordcountplugin', 
            'wfiz_charactercount', 
            array(
                'sanitize_callback' => 'sanitize_text_field', 
                'default' => '1'
            )
        );

        // Read Time
        add_settings_field(
            'wfiz_readtime', 
            'Read time', 
            array($this, 'checkboxHTML'), 
            'wfiz-word-count-settings-page', 
            'wfiz_first_section', 
            array(
                'theName' => 'wfiz_readtime'
            )
        );
        register_setting(
            'wordcountplugin', 
            'wfiz_readtime', 
            array(
                'sanitize_callback' => 'sanitize_text_field', 
                'default' => '1'
            )
        );

    }

    public function sanitizeLocation($input) {
        if($input != '0' && $input != '1'){
            add_settings_error( 'wfiz_location', 'wfiz_location_error', 'Display location must be either beginning or end.');
            return get_option( 'wfiz_location' );
        }
        return $input;
    }

    public function locationHTML() {
        ?>
            <select name='wfiz_location'>
                <option value='0' <?php selected(get_option('wfiz_location', '0')) ?> >Beginning of post</option>
                <option value='1' <?php selected(get_option('wfiz_location', '1')) ?>>End of post</option>
            </select>
        <?php
    }

    public function headlineHTML() { ?>
        <input type="text" name="wfiz_headline" value="<?php echo esc_attr(get_option('wfiz_headline')) ?>">
    <?php 
    }

    // reusable checkbox
    public function checkboxHTML($args) { ?>
        <input type="checkbox" name="<?php echo $args['theName'] ?>" value="1" <?php checked(get_option($args['theName']), '1') ?>>
    <?php
    }

    /**
     * Link in which you can click from Settings sub menus
     */
    public function wfiz_setting_link() {
        add_options_page('WakataFIZ Settings', esc_html__('Wakata FIZ', 'wakatafizdomain'),'manage_options','wfiz-word-count-settings-page', array($this, 'wfiz_settings_page_html'));
    }
    
    /**
     * Overall HTML Setting Page
     * 
     */
    public function wfiz_settings_page_html() {
    
        ?>
        <div class='wrap'>
            <h1>Wakata FIZ Settings</h1>
            <form action='options.php' method='POST'>
            <?php
                settings_fields('wordcountplugin');
                do_settings_sections('wfiz-word-count-settings-page');
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }
}

new WakataFiz();
