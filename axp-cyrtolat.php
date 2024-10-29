<?php /*

**************************************************************************

Plugin Name:  AXP Cyrillic to Latin
Plugin URI:   https://github.com/axp-dev/axp-cyrtolat
Description:  Converts Cyrillic characters in post slugs to Latin characters
Version:      1.0.0
Author:       Alexander Pushkarev <axp-dev@yandex.com>
Author URI:   https://github.com/axp-dev
Text Domain:  axp-cyrtolat
License:      GPLv2 or later


This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**************************************************************************/

class AXP_CyrToLat {
    public $menu_slug;
    public $fields;
    public $dictionary;
    public $transliteration;

    function __construct() {
        $this->menu_slug        = 'axp-cyrtolat';
        $this->fields           = 'axp-cyrtolat-fields';
        $this->dictionary       = require_once('dictionary.php');
        $this->transliteration  = array(
            'none'      => __('None', 'axp-cyrtolat'),
            'gost'      => __('GOST 16876-71', 'axp-cyrtolat'),
            'bsi'       => 'BSI',
            'bgn'       => 'BGN',
            'yandex'    => __('Yandex', 'axp-cyrtolat'),
        );

        register_activation_hook( __FILE__, array( &$this, 'install' ) );

        add_action( 'plugins_loaded',   array( &$this, 'init_textdomain' ));
        add_action( 'admin_menu',       array( &$this, 'register_menu' ) );
        add_action( 'admin_init',       array( &$this, 'register_settings' )  );
        add_action( 'sanitize_title',   array( &$this, 'translit' ), 0);
    }

    static function install() {
        update_option(
            'axp-cyrtolat-fields',
            array(
                'type-transliteration' => 'gost',
            )
        );
    }

    public function init_textdomain() {
        load_plugin_textdomain( 'axp-cyrtolat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    public function register_menu() {
        add_options_page(
            __('Cyrillic to Latin Settings', 'axp-cyrtolat'),
            __('Cyrillic to Latin', 'axp-cyrtolat'),
            'manage_options',
            $this->menu_slug,
            array(&$this, 'render_page_settings')
        );
    }

    public function register_settings()
    {
        register_setting($this->fields, $this->fields);

        add_settings_section(
            'main_settings',
            __('Settings', 'axp-cyrtolat'),
            null,
            $this->menu_slug
        );

        add_settings_field(
            'type_transliteration',
            __('Type Transliteration', 'axp-cyrtolat'),
            array( $this, 'render_settings_fields' ),
            $this->menu_slug, 'main_settings',
            array(
                'type'      => 'select',
                'id'        => 'type-transliteration',
                'desc'      => __('Select standard', 'axp-cyrtolat'),
                'vals'		=> $this->transliteration
            )
        );
    }

    public function get_dictionary($code) {
        $result = array();

        foreach ( $this->dictionary as $key => $value) {
            $result[$key] = $value[$code];
        }

        return $result;
    }

    public function apx_get_filed( $name ) {
        return get_option( $this->fields )[$name];
    }

    public function translit($str) {
        $standard = $this->apx_get_filed('type-transliteration');

        if ( $standard == 'none' ) {
            return $str;
        } else {
            return strtr( mb_strtoupper($str, 'UTF-8'), $this->get_dictionary($standard) );
        }
    }

    public function render_settings_fields( $arguments ) {
        extract( $arguments );

        $option_name = $this->fields;
        $o = get_option( $option_name );

        switch ( $type ) {
            case 'text':
                $o[$id] = esc_attr( stripslashes($o[$id]) );
                echo "<input class='regular-text' type='text' id='$id' name='" . $option_name . "[$id]' value='$o[$id]' />";
                echo ($desc != '') ? "<p class='description'>$desc</p>" : "";
                break;
            case 'select':
                echo "<select id='$id' name='" . $option_name . "[$id]'>";
                foreach($vals as $v=>$l){
                    $selected = ($o[$id] == $v) ? "selected='selected'" : '';
                    echo "<option value='$v' $selected>$l</option>";
                }
                echo "</select>";
                echo ($desc != '') ? "<p class='description'>$desc</p>" : "";
                break;
        }
    }

    public function render_page_settings() {
        ?>
        <div class="wrap">
            <h2><?php _e('Cyrillic to Latin Settings', 'axp-cyrtolat'); ?></h2>

            <div class="card pressthis">
                <form method="POST" enctype="multipart/form-data" action="options.php">
                    <?php settings_fields( $this->fields ); ?>
                    <?php do_settings_sections( $this->menu_slug ); ?>
                    <?php submit_button(); ?>
                </form>
            </div>

            <div class="card pressthis">
                <p style="display: flex; justify-content: space-between">
                    <a class="button" href="https://paypal.me/axpdev" target="_blank"><?php _e('Donate', 'axp-cyrtolat'); ?></a>
                    <a class="button" href="mailto:axp-dev@yandex.com"><?php _e('Contact the author', 'axp-cyrtolat'); ?></a>
                    <a class="button" href="<?php echo get_home_url( null, 'wp-admin/plugin-install.php?s=axpdev&tab=search&type=term' ); ?>" target="_blank"><?php _e('Other plugins by author', 'axp-cyrtolat'); ?></a>
                </p>
            </div>
        </div>
        <?php
    }
}

$AXP_CyrToLat = new AXP_CyrToLat();