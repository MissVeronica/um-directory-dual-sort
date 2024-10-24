<?php
/**
 * Plugin Name:         Ultimate Member - Directory Dual Sort
 * Description:         Extension to Ultimate Member for a second sort key in the Directory and an option for local language (non-english UTF8) characters collation. The plugin only supports UM using "Custom usermeta table"
 * Version:             1.0.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Plugin URI:          https://github.com/MissVeronica/um-directory-dual-sort
 * Update URI:          https://github.com/MissVeronica/um-directory-dual-sort
 * Text Domain:         ultimate-member
 * Domain Path:         /languages
 * UM version:          2.8.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

class UM_Directory_Dual_Sort {

    function __construct() {

        define( 'Plugin_Basename_DDS', plugin_basename( __FILE__ ));

        if ( UM()->options()->get( 'member_directory_own_table' ) == 1 ) {

            add_action( 'um_pre_users_query', array( $this, 'um_pre_users_query_dual_sort' ), 10, 3 );
            add_action( 'add_meta_boxes',     array( $this, 'add_metabox_directory_dual_sort_um_form' ), 1 );

            define( 'Plugin_File_DDS', __FILE__ );

        } else {

            add_filter( 'plugin_action_links_' . Plugin_Basename_DDS, array( $this, 'dual_sort_settings_link' ));
        }
    }

    function dual_sort_settings_link( $links ) {

        $url = get_admin_url() . 'admin.php?page=um_options&tab=advanced&section=features';
        $links[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Tick the checkbox: Custom usermeta table' ) . '</a>';

        return $links;
    }

    public function um_pre_users_query_dual_sort( $obj, $directory_data, $sortby ) {

        global $wpdb;

        if ( isset( $directory_data['dual_sort_settings'] ) && ! empty( $directory_data['dual_sort_settings'] )) {

            $dual_sort_settings = array_map( 'sanitize_text_field', explode( "\n", $directory_data['dual_sort_settings'] ));

            foreach( $dual_sort_settings as $dual_sort_setting ) {
                $dual_sort = array_map( 'trim', explode( ':', $dual_sort_setting ));

                if ( count( $dual_sort ) == 4 && $sortby == esc_sql( $dual_sort[0] )) {

                    $tagline_fields = maybe_unserialize( $directory_data['tagline_fields'] );
                    if ( in_array( esc_sql( $dual_sort[1] ), $tagline_fields, true )) {

                        $order = strtoupper( esc_sql( $dual_sort[2] ));
                        $order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';

                        $type = strtoupper( esc_sql( $dual_sort[3] ));
                        $type = in_array( $type, array( 'CHAR', 'DATE' ), true ) ? $type : 'CHAR';

                        if ( is_array( $obj->joins ) && strpos( $obj->sql_order, 'ORDER BY' ) !== false ) {

                            $obj->joins[] = $wpdb->prepare( "LEFT JOIN {$wpdb->prefix}um_metadata dual_sort ON ( dual_sort.user_id = u.ID AND dual_sort.um_key = %s )", esc_sql( $dual_sort[1] ));
                            $obj->sql_order .= ", CAST( dual_sort.um_value AS {$type} ) {$order}";

                            if ( isset( $directory_data['dual_sort_collation'] ) && ! empty( $directory_data['dual_sort_collation'] )) {
                                $collate = esc_sql( $directory_data['dual_sort_collation'] );

                                if ( $collate != 'current' && strpos( 'COLLATE', $obj->sql_order ) === false ) {
                                    $obj->sql_order = str_replace( 'CHAR )', "CHAR ) COLLATE {$collate} ", $obj->sql_order );
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function add_metabox_directory_dual_sort_um_form() {

        $plugin_data = get_plugin_data( Plugin_File_DDS );
        $update  = '';
        $version = '';

        if ( ! empty( $plugin_data )) {

            if ( isset( $plugin_data['PluginURI'] ) && ! empty( $plugin_data['PluginURI'] )) {

                $update = sprintf( ' <a href="%s" target="_blank" title="%s">%s</a>',
                                            esc_url( $plugin_data['PluginURI'] ),
                                            esc_html__( 'GitHub plugin documentation and download', 'ultimate-member' ),
                                            esc_html__( 'Plugin documentation', 'ultimate-member' ));
            }

            if ( isset( $plugin_data['Version'] ) && ! empty( $plugin_data['Version'] )) {

                $version = $plugin_data['Version'];
            }
        }

        add_meta_box(  'um-admin-dual-sort-um-form',
                        '<div>' . sprintf( esc_html__( 'Directory Dual Sort %s %s', 'ultimate-member' ), $version, $update ) . '</div>',
                        array( $this, 'load_metabox_directory' ),
                        'um_directory',
                        'normal',
                        'default'
                    );
    }

    public function load_metabox_directory( $object, $box ) {

        global $post_id;
        global $wpdb;

        $collate = array_map( 'trim', explode( 'COLLATE', $wpdb->get_charset_collate() ));
        $charset = trim( substr( $collate[0], strrpos( $collate[0], ' ' )));

        $results = $wpdb->get_results( "SHOW COLLATION WHERE Charset = '{$charset}';" );

        $options = array( 'current' => esc_html__( 'Use the site\'s current database setting', 'ultimate-member' ) );

        foreach( $results as $result ) {
            $options[$result->Collation] = $result->Collation;
        }

        asort( $options );

        $sort_collation = get_post_meta( $post_id, '_um_dual_sort_collation', true );
        if ( empty( $sort_collation )) {
            $sort_collation = 'current';
        }

?>
        <div class="um-admin-metabox">
<?php
            UM()->admin_forms(
                array(
                    'class'     => 'um-member-directory-dual-sort-um-form um-half-column',
                    'prefix_id' => 'um_metadata',
                    'fields'    => array(
                                            array(
                                                'id'          => '_um_dual_sort_settings',
                                                'type'        => 'textarea',
                                                'label'       => esc_html__( 'Primary and secondary meta keys and sorting order', 'ultimate-member' ),
                                                'description' => esc_html__( 'Enter the primary (UM enabled for sorting) and plugin secondary meta keys.', 'ultimate-member' ) . '<br />' .
                                                                 esc_html__( 'Add the Sort order (ASC/DESC) and Type  (CHAR/DATE) of the secondary meta key.', 'ultimate-member' ) . '<br />' .
                                                                 esc_html__( 'All parameters colon separated and one settings pair per line.', 'ultimate-member' ),
                                                'value'       => get_post_meta( $post_id, '_um_dual_sort_settings', true ),
                                            ),

                                            array(
                                                'id'          => '_um_dual_sort_collation',
                                                'type'        => 'select',
                                                'options'     => $options,
                                                'label'       => esc_html__( 'Primary and secondary meta keys non-english UTF8 character collation', 'ultimate-member' ),
                                                'description' => '<a href="https://dev.mysql.com/doc/refman/8.4/en/charset-mysql.html" target="_blank">Character Sets and Collations in MySQL</a><br />' .
                                                                 esc_html__( 'Current database setting:', 'ultimate-member' ) . '<br />' .
                                                                 $wpdb->get_charset_collate(),
                                                'value'       => $sort_collation,
                                            ),
                                        ),
                ),

            )->render_form();
?>
            <div class="clear"></div>
        </div>
<?php

    }


}

new UM_Directory_Dual_Sort();
