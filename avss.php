<?php
/*
Plugin Name: AVED Site Scanner
Plugin URI: http://avedsoft.com/
Description: Aved Site Scanner
Version: 0.2 alfa
Author: AVEDSoft
Author URI: http://avedsoft.com/
License: GPL
*/


add_action('admin_menu', 'avss_admin_menu');

function avss_admin_menu() {
    add_menu_page( 'Aved Site Scanner', 'Aved Site Scanner', 'manage_options', '', 'check_report');
    add_submenu_page('', 'Sites', 'Sites', 'manage_options', 'avss/sites.php');
    add_submenu_page('', 'Config', 'Config', 'manage_options', 'avss/config.php');
    add_options_page('Aved Site Scanner', 'Aved Site Scanner', 'manage_options', '', 'check_report');
}

function create_plugin_tables()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'sites';
    $sql = "CREATE TABLE IF NOT EXISTS " .$table_name ." (
            site_id int(11) NOT NULL AUTO_INCREMENT,
            site_name varchar(255) DEFAULT NULL,
            site_url varchar(255) DEFAULT NULL,
            notes varchar(255) DEFAULT NULL,
            last_time datetime,
            last_result int(11),
            PRIMARY KEY  (site_id)
    )";
   
    $wpdb->query( $sql );
}

register_activation_hook(__FILE__, 'create_plugin_tables');

add_action( 'wp_enqueue_scripts', 'avss_register_scripts' );

function avss_register_scripts(){
    wp_enqueue_script( 'jquery' );
}

add_action( 'admin_enqueue_scripts', 'avss_register_css' );

function avss_register_css()  
{  
    wp_register_style( 'custom-style', plugins_url( '/style.css', __FILE__ ), array(), '20170608', 'all' );  
    wp_enqueue_style( 'custom-style' );  
}  

add_action('admin_print_footer_scripts', 'check_url_action_javascript', 99);
function check_url_action_javascript() {
    ?>
    <script type="text/javascript" >
    jQuery('#avssResultCheckForm').submit(function(){
        var d;
        var i = 0;
        var inputs_count = jQuery('.hidden_site_urls').length;
        var inputs = new Array();
        jQuery(".result_last_code").html('<img height="10" width="50" src="/wp-content/plugins/avss/ajax-loader-small.gif">');
        jQuery(".result_last_time").html('');
        jQuery("#buttonRefresh").attr('disabled',true);
        
        jQuery('#avssResultCheckForm').find('input').each(function() {
            inputs[i] = jQuery(this).val();
            
            i++;
        });

        i = 0;    
        function go() {
            
            var data = { action: 'check_url_action', s_url: inputs[i] };
            var posting = jQuery.post( ajaxurl, data );
            posting.done(function( result ) {
                var result_last_time = jQuery(result).filter('#result_last_time');
                jQuery('#result_last_time' + i).html(result_last_time);
                var result_last_code = jQuery(result).filter('#result_last_code');
                jQuery('#result_last_code' + i).html(result_last_code);
                i++;
                if ( i > inputs_count ) {
                    // alert('Scan complete');
                    jQuery("#buttonRefresh").removeAttr('disabled');
                } else
                    go();
            });
        }
        go();
        return false;
    });
    </script>
    <?php
}

add_action('wp_ajax_check_url_action', 'check_url_action_callback');
function check_url_action_callback() {
    $s_url = $_POST['s_url'];
    $site_state = check_site_state($s_url);

    global $wpdb;
    $date = date('Y-m-d H:i:s'); 
    $sql = "update $wpdb->prefix" . "sites set last_result=" . intval($site_state) . ", last_time=\"$date\" where site_url=\"$s_url\"";
    
    $wpdb->get_results($sql);
    echo "<span id=\"result_last_code\">$site_state</span>";
    echo "<span id=\"result_last_time\">$date</span>";
    wp_die();
}


function check_site_state($url) {
    $user_agent = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $page = curl_exec($ch);
 
    $error = curl_errno($ch);
    if (!empty($error))
        return $error;
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return ($httpcode);
    curl_close($ch);
}

function background_check() {
    global $wpdb;
    $sql = 'SELECT site_name, site_url FROM ' . $wpdb->prefix . 'sites';
    $urls = $wpdb->get_results($sql);

    foreach ($urls as $val) {
        $answer = check_site_state($val->site_url);
        switch ($answer) {
            case '200':
                break;
            case '404':
                telegramSend($val->site_url . ' 404 - Page not found.');
                break;
            case '28':
                telegramSend($val->site_url . ' is not responding. Time out operation');
                break;
            default:
                telegramSend($val->site_url . ' HTTP code: ' . $answer . ' - ' . curl_strerror($answer) );
                break;
        }
    }
}

function telegramSend($msg){
    $bot_id = get_option('telegram_bot_id');
    $chat_id = get_option('telegram_chat_id');
    wp_remote_get("https://api.telegram.org/bot$bot_id/sendMessage?chat_id=$chat_id&text=$msg");
}



function check_report() {
    global $wpdb;
    $sql = 'SELECT site_id, site_name, site_url, last_time, last_result FROM ' . $wpdb->prefix . 'sites';
    $sites = $wpdb->get_results($sql);
    $k = 0;
?>
    <h1>Site Scanner</h1>
        <form id="avssResultCheckForm">
            <table class="avss-table">
                <tr>
                    <th>Site name</th><th>Site URL</th><th>Last check</th><th>Last Result</th>
                </tr>
            <?php foreach ($sites as $s) { ?>
                <tr>
                    <td> <?php echo $s->site_name ?> </td>
                    <td> <?php echo $s->site_url ?> </td>
                    <td align="center" class="result_last_time" id="result_last_time<?php echo $k; ?>" > <?php echo $s->last_time; ?> </td>
                    <td align="center" class="result_last_code" id="result_last_code<?php echo $k++; ?>"> <?php echo $s->last_result; ?> </td>
                </tr>
                <input type="hidden" name="site_urls[]" class="hidden_site_urls" value= <?php echo $s->site_url ?> >
            <?php } ?>
            </table>
            <p />
<?php 
    if ($k == 0) {
        echo 'Site list is empty. ';
        echo '<a href="?page=avss%2Fsites.php">Add new site</a>';
    } else { ?>
            <input type="submit" id="buttonRefresh" value="Update results">
            </form>
<?php
         }
    }

if (is_admin()) {
    if (function_exists('date_default_timezone_set'))
        date_default_timezone_set('Europe/Zaporozhye');
}


if (!is_admin() && isset($_GET['exec']) && ($_GET['exec'] == 'check')) {
    background_check();            
}

