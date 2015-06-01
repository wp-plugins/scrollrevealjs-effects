<?php
/* 
Plugin Name: ScrollReveal.js Plugin
Plugin URI: http://www.example.com/textwidget
Description: Plugin to demonstrate ScrollReveal.js in WordPress
Version: 0.1 
Author: Jan Benedík
Author URI: http://www.example.com
License: GPL2 
 
    Copyright 2011  Jan Benedík
 
    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License,
    version 2, as published by the Free Software Foundation. 
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of 
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
    GNU General Public License for more details. 
 
    You should have received a copy of the GNU General Public License 
    along with this program; if not, write to the Free Software 
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 
    02110-1301  USA 
*/

global $scroll_reveal_js_db_version;
$scroll_reveal_js_db_version = '1.0'; // version changed from 1.0 to 1.1

function scroll_reveal_js_table_install()
{
    global $wpdb;
    global $scroll_reveal_js_db_version;

    $table_name = $wpdb->prefix . 'scrollrevealjs'; // do not forget about tables prefix

    $sql = "CREATE TABLE " . $table_name . " (
      id int(11) NOT NULL AUTO_INCREMENT,
      selector VARCHAR(500),
      enter VARCHAR(500) NULL,
      move VARCHAR(500) NULL,
      over VARCHAR(500) NULL,
      wait VARCHAR(500) NULL,
      flip VARCHAR(500) NULL,
      spin VARCHAR(500) NULL,
      roll VARCHAR(500) NULL,
      scale VARCHAR(500) NULL,
      reset VARCHAR(500) NULL,
      PRIMARY KEY  (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // save current database version for later use (on upgrade)
    add_option('scroll_reveal_js_db_version', $scroll_reveal_js_db_version);
}

register_activation_hook(__FILE__, 'scroll_reveal_js_table_install');

function scroll_reveal_js_table_install_data()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'scrollrevealjs'; // do not forget about tables prefix

    $wpdb->insert($table_name, array(
        'selector' => '#test',
        'enter' => 'left',
        'move' => '200',
        'over' => '4'
    ));
}

register_activation_hook(__FILE__, 'scroll_reveal_js_table_install_data');

function scroll_reveal_js_update_db_check()
{
    global $scroll_reveal_js_db_version;
    if (get_site_option('scroll_reveal_js_db_version') != $scroll_reveal_js_db_version) {
        scroll_reveal_js_table_install();
    }
}

add_action('plugins_loaded', 'scroll_reveal_js_update_db_check');

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Scroll_Reveal_List_Table extends WP_List_Table
{
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'effect',
            'plural' => 'effects',
        ));
    }
    
    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }
    
    function column_selector($item)
    {
        // links going to /admin.php?page=[your_plugin_page][&other_params]
        // notice how we used $_REQUEST['page'], so action will be done on curren page
        // also notice how we use $this->_args['singular'] so in this example it will
        // be something like &person=2
        $actions = array(
            'edit' => sprintf('<a href="?page=scroll_reveal_js_form&id=%s">%s</a>', $item['id'], __('Edit', 'scroll_reveal_js')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'scroll_reveal_js')),
        );

        return sprintf('%s %s',
            $item['selector'],
            $this->row_actions($actions)
        );
    }
    
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }
    
    function column_params($item)
    {
        $param = '';
        
        if(!empty($item['wait'])) { $param .= 'wait '.$item['wait'].'s, '; }
        if(!empty($item['enter'])) { $param .= 'enter '.$item['enter'].', '; }
        if(!empty($item['move'])) { $param .= 'move '.$item['move'].'px, '; }
        if(!empty($item['flip'])) { $param .= 'flip '.$item['flip'].'deg, '; }
        if(!empty($item['spin'])) { $param .= 'spin '.$item['spin'].'deg, '; }
        if(!empty($item['roll'])) { $param .= 'roll '.$item['roll'].'deg, '; }
        if(!empty($item['scale'])) { $param .= 'scale '.$item['scale'].'%, '; }
        if(!empty($item['over'])) { $param .= 'over '.$item['over'].'s, '; }
        if(!empty($item['reset'])) { $param .= $item['reset']; }
        
        return $param;
    }
    
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'selector' => __('Selector', 'scroll_reveal_js'),
            'params' => __('Parameters', 'scroll_reveal_js')
        );
        return $columns;
    }
    
    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }    
    
    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scrollrevealjs'; // do not forget about tables prefix

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }
    
    function get_sortable_columns()
    {
        $sortable_columns = array(
            'selector' => array('name', true),
        );
        return $sortable_columns;
    }
    
    function prepare_items()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scrollrevealjs'; // do not forget about tables prefix

        $per_page = 5; // constant, how much records will be shown per page

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        // here we configure table headers, defined in our methods
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();

        // will be used in pagination settings
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'selector';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';

        // [REQUIRED] define $items array
        // notice that last argument is ARRAY_A, so we will retrieve array
        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);

        // [REQUIRED] configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));
    }
}

if( !function_exists("scroll_reveal_js") )
{
    function scroll_reveal_js()
    {
        
    }
}

function load_js()
{
    wp_register_script( 'scroll-reveal-js', plugins_url( '/js/scrollReveal.min.js', __FILE__ ), array( 'jquery' ), '', true );
    wp_enqueue_script('scroll-reveal-js');
}

function sr_footer() {
    require dirname( __FILE__ ) . '/templates/footer.php';
}

function sr_init() {
    require dirname( __FILE__ ) . '/templates/sr-init.php';
}

add_action('wp_enqueue_scripts','load_js');

add_action('wp_footer','sr_footer');

add_action( 'wp_footer', 'sr_init', 101 ); //low priority will load after i18n and script loads

add_action( 'admin_menu', 'scroll_revela_js_menu' );

if( !function_exists("scroll_revela_js_menu") )
{
    function scroll_revela_js_menu(){

        $page_title = 'ScrollReveal.js Plugin';
        $menu_title = 'ScrollReveal.js';
        $capability = 'activate_plugins';
        $menu_slug  = 'scroll_reveal_js';
        $function   = 'scroll_reveal_js_page';
        $icon_url   = plugins_url( 'scroll-reveal-js/icon.png' );
        $position   = 64;

        add_menu_page(
            $page_title, 
            $menu_title, 
            $capability, 
            $menu_slug, 
            $function, 
            $icon_url, 
            $position
        );
        
        add_submenu_page('scroll_reveal_js', 
            'Used effects', 
            'Used effects', 
            'activate_plugins', 
            'scroll_reveal_js', 
            'scroll_reveal_js_page'
        );
        
        add_submenu_page('scroll_reveal_js', 
            'Add new effect', 
            'Add new effect', 
            'activate_plugins', 
            'scroll_reveal_js_form', 
            'scroll_reveal_js_form_page'
        );
    }
}

if( !function_exists("scroll_reveal_js_page") )
{
    function scroll_reveal_js_page(){
        ?>
        <h1>ScrollReveal.js Plugin</h1>
        <?php
        
        global $wpdb;

        $table = new Scroll_Reveal_List_Table();
        $table->prepare_items();
        
        $message = '';
        if ('delete' === $table->current_action()) {
            $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'custom_table_example'), count($_REQUEST['id'])) . '</p></div>';
        }?>
        
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2><a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=scroll_reveal_js_form');?>"><?php _e('Add new', 'custom_table_example')?></a>
            </h2>
            <?php echo $message; ?>

            <form id="effects-table" method="GET">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <?php $table->display() ?>
            </form>
        </div>
        <?php
    }
}

if( !function_exists("scroll_reveal_js_form_page") )
{
    function scroll_reveal_js_form_page(){
        ?>
        <h1>ScrollReveal.js Plugin</h1>
        <?php
        
        $pages = get_all_page_ids();
        $page_id = 2;
        ?>
        
        
        <form action="" method="POST">
            <label for="selectPage">Choose page</label>
            <select id="selectPage" name="selectPage" onchange="this.form.submit()">
                <?php
                    foreach ($pages as $page) {
                        echo '<option value="'.$page.'">'.get_the_title($page).'</option>';
                    }
                ?>
            </select>
        </form>
        
        <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'scrollrevealjs'; // do not forget about tables prefix

        $message = '';
        $notice = '';

        // this is default $item which will be used for new records
        $default = array(
            'id' => 0,
            'selector' => '',
            'enter' => '',
            'move' => '',
            'over' => '',
            'wait' => '',
            'flip' => '',
            'spin' => '',
            'roll' => '',
            'scale' => '',
            'reset' => '',
        );
        
        // here we are verifying does this request is post back and have correct nonce
        if (wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
            // combine our default item with request params
            $item = shortcode_atts($default, $_REQUEST);
            // validate data, and if all ok save item to database
            // if id is zero insert otherwise update
            $item_valid = scroll_reveal_js_validate_effect($item);
            if ($item_valid === true) {
                if ($item['id'] == 0) {
                    $result = $wpdb->insert($table_name, $item);
                    $item['id'] = $wpdb->insert_id;
                    if ($result) {
                        $message = __('Item was successfully saved', 'scroll_reveal_js');
                    } else {
                        $notice = __('There was an error while saving item', 'scroll_reveal_js');
                    }
                } else {
                    $result = $wpdb->update($table_name, $item, array('id' => $item['id']));
                    if ($result) {
                        $message = __('Item was successfully updated', 'scroll_reveal_js');
                    } else {
                        $notice = __('There was an error while updating item', 'scroll_reveal_js');
                    }
                }
            } else {
                // if $item_valid not true it contains error message(s)
                $notice = $item_valid;
            }
        }
        else {
            // if this is not post back we load item to edit or give new one to create
            $item = $default;
            if (isset($_REQUEST['id'])) {
                $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
                if (!$item) {
                    $item = $default;
                    $notice = __('Item not found', 'scroll_reveal_js');
                }
            }
        }
        
        // here we adding our custom meta box
        add_meta_box('effects_form_meta_box', 'Effect details', 'scroll_reveal_js_form_meta_box_handler', 'scroll_reveal_js', 'normal', 'default');
        
        ?>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2>
                <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=scroll_reveal_js');?>"><?php _e('back to list', 'scroll_reveal_js')?></a>
            </h2>

            <?php if (!empty($notice)): ?>
            <div id="notice" class="error"><p><?php echo $notice ?></p></div>
            <?php endif;?>
            <?php if (!empty($message)): ?>
            <div id="message" class="updated"><p><?php echo $message ?></p></div>
            <?php endif;?>

            <form id="form" method="POST">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
                <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
                <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

                <div class="metabox-holder" id="poststuff">
                    <div id="post-body">
                        <div id="post-body-content">
                            <?php do_meta_boxes('scroll_reveal_js', 'normal', $item); ?>
                            <input type="submit" value="<?php _e('Save', 'scroll_reveal_js')?>" id="submit" class="button-primary" name="submit">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <script>
            jQuery(function($){
                var $inputs = $('.rotationInput').on('input blur', function () {
                    var value = $.trim(this.value);
                    $inputs.not(this).prop('disabled', value.length != 0);
                })
            });
        </script>
        <?php
    }
}

function scroll_reveal_js_form_meta_box_handler($item)
{
    include(plugin_dir_path(__FILE__).'simple_html_dom.php');
        $page_id = 2;
        
        if(isset($_POST['selectPage'])) {
            $page_id = $_POST['selectPage'];
        }
        
        $url = get_page_link($page_id);
        
        //$dom = new simple_html_dom();
        $dom = file_get_html($url);    //'http://localhost/wp-test/'
        $divs = $dom->find('*[id]'); 
    ?>

    <table cellspacing="2" cellpadding="5" style="width: 100%;" class="form-table">
        <tbody>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="selector">Selector</label>
            </th>
            <td>
                <select id="selector" name="selector">
                    <?php
                        foreach ($divs as $div) {
                            echo '<option value="#'.$div->id.'">'.$div->id.'</option>';
                        }
                    ?>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="enter">Enter</label>
            </th>
            <td>
                <select id="enter" name="enter">
                    <option value="left">left</option>
                    <option value="top">top</option>
                    <option value="rigth">right</option>
                    <option value="bottom">bottom</option>
                </select>
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="move">Move</label>
            </th>
            <td>
                <input id="move" name="move" type="text" style="width: 95%" value="<?php echo esc_attr($item['move'])?>"
                       size="50" class="code" placeholder="Length of move">
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="over">Over</label>
            </th>
            <td>
                <input id="over" name="over" type="text" style="width: 95%" value="<?php echo esc_attr($item['over'])?>"
                       size="50" class="code" placeholder="Time interval in secons">
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="wait">Wait</label>
            </th>
            <td>
                <input id="wait" name="wait" type="text" style="width: 95%" value="<?php echo esc_attr($item['wait'])?>"
                       size="50" class="code" placeholder="Time of delay in second">
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="flip">Flip</label>
            </th>
            <td>
                <input id="flip" name="flip" type="text" class="rotationInput" style="width: 95%" value="<?php echo esc_attr($item['flip'])?>"
                       size="50" class="code" placeholder="Horizontal rotation in degrees">
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="spin">Spin</label>
            </th>
            <td>
                <input id="spin" name="spin" type="text" class="rotationInput" style="width: 95%" value="<?php echo esc_attr($item['spin'])?>"
                       size="50" class="code" placeholder="Vertical rotation in degrees">
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="roll">Roll</label>
            </th>
            <td>
                <input id="roll" name="roll" type="text" class="rotationInput" style="width: 95%" value="<?php echo esc_attr($item['roll'])?>"
                       size="50" class="code" placeholder="Rolling in degrees">
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="scale">Scale</label>
            </th>
            <td>
                <input id="scale" name="scale" type="text" style="width: 95%" value="<?php echo esc_attr($item['scale'])?>"
                       size="50" class="code" placeholder="Ratio in percent">
            </td>
        </tr>
        <tr class="form-field">
            <th valign="top" scope="row">
                <label for="reset">Reset</label>
            </th>
            <td>
                <select id="reset" name="reset">
                    <option value="reset">reset</option>
                    <option value="no reset">no reset</option>
                </select>
            </td>
        </tr>
        </tbody>
    </table>
<?php
}

function add_scroll_reveal_js_meta_box()
{
    add_meta_box("effect_post_meta_box", "ScrollReveal.js Box", "scroll_reveal_js_form_meta_box_handler", "post", "side", "high", null);
}

//add_action("add_meta_boxes", "add_scroll_reveal_js_meta_box");

function scroll_reveal_js_validate_effect($item)
{
    $messages = array();

    //if (empty($item['name'])) $messages[] = __('Name is required', 'custom_table_example');
    //if (!empty($item['email']) && !is_email($item['email'])) $messages[] = __('E-Mail is in wrong format', 'custom_table_example');
    //if (!ctype_digit($item['age'])) $messages[] = __('Age in wrong format', 'custom_table_example');
    //if(!empty($item['age']) && !absint(intval($item['age'])))  $messages[] = __('Age can not be less than zero');
    //if(!empty($item['age']) && !preg_match('/[0-9]+/', $item['age'])) $messages[] = __('Age must be number');
    //...

    if (empty($messages)) return true;
    return implode('<br />', $messages);
}

?>