<?php
/*
Plugin Name: Shopp Lockdown
Version: 1.0.0
Description: Shopp Lockdown will watch failed credit card attempts and lock out repetitive failed transaction customer.
Plugin URI: http://www.tinyelk.com
Author: tinyElk Studios
Author URI: http://www.tinyelk.com

	This plugin is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This plugin is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this plugin.  If not, see <http://www.gnu.org/licenses/>.
*/
if(!defined('ABSPATH')) die();

$ShoppLockdown = new ShoppLockdown();

class ShoppLockdown{
	var $product = 'shopp-lockdown';

	function __construct(){
		//default stuff
		register_activation_hook(__FILE__, array(&$this, 'on_activation'));

		add_action('admin_menu', array($this, 'add_menu'), 99);
		add_action('admin_notices', array($this, 'notices'));

		//check if customer is already blocked - if so, do not allow them to proceed
		add_filter('shopp_valid_order', array($this, 'limit_failed_txn'));

		//get list of gateways so that we can tie into the auth-fail and capture-fail
		add_action('shopp_auth-fail_order_event', array($this, 'log_failed_txn'));
		add_action('shopp_capture-fail_order_event', array($this, 'log_failed_txn'));
	}

	function notices(){
		if(!is_plugin_active('shopp/Shopp.php')){
			echo '<div class="error"><p><strong>Shopp Contact</strong>: It is highly recommended to have the <a href="http://www.shopplugin.net">Shopp Plugin</a> active before using any of the Shopp Toolbox plugins.</p></div>';
		}
	}

	function toolbox_menu_exist(){
        global $menu;

        $return = false;
        foreach($menu as $menus => $item){
            if($item[0] == 'Shopp Toolbox'){
                $return = true;
            }
        }
        return $return;
    }

	function add_menu(){
		global $menu;
		$position = 52;
		while (isset($menu[$position])) $position++;

		if(!$this->toolbox_menu_exist()){
			add_menu_page('Shopp Toolbox', 'Shopp Toolbox', 'shopp_menu', 'shopp-toolbox', array('ShoppToolbox_Welcome', 'display_welcome'), plugin_dir_url(__FILE__) . 'img/toolbox.png', $position);
			$page = add_submenu_page('shopp-toolbox', 'Shopp Toolbox', 'Welcome', 'shopp_menu', 'shopp-toolbox', array('ShoppToolbox_Welcome', 'display_welcome'));
		}

		$page = add_submenu_page('shopp-toolbox', 'Shopp Lockdown', 'Shopp Lockdown', 'shopp_menu', $this->product, array(&$this, 'display_settings'));
    add_meta_box('shopp_lockdown_save', 'Save', array($this, 'display_save_meta'), $page, 'side', 'default');
		add_meta_box('shopp_lockdown_options', 'Options', array($this, 'display_options'), $page, 'normal', 'default');
	}

	function on_activation(){
		$options = get_option('shopp_lockout_options');
		if(!is_array($options)){
			$options['max_retries'] = 3; // three retries
			$options['ban_period'] = 3600; //one hour

			update_option('shopp_lockout_options', $options);
		}
	}

	function limit_failed_txn($valid){
		$ip = $_SERVER['REMOTE_ADDR'];
		$lockouts = get_option('shopp_lockout_lockouts');

		$this->limit_cleanup();

		if(isset($lockouts[$ip]) && time() < $lockouts[$ip]){
			new ShoppError(__('Too many failed transaction attempts. Please contact customer service for more information.','Shopp'),'invalid_order'.$errors++, SHOPP_TRXN_ERR);
			return false;
		}else{
			return $valid;
		}
	}

	function log_failed_txn($event){
		$retries = get_option('shopp_lockout_retries');
		$lockouts = get_option('shopp_lockout_lockouts');
		$options = get_option('shopp_lockout_options');

		$ip = $_SERVER['REMOTE_ADDR'];
		$now = time();

		if(isset($retries[$ip])){
			$retries[$ip]['retry']++;
		}else{
			$retries[$ip]['retry'] = 1;
			$retries[$ip]['timestamp'] = $now;
		}

		//if the retries equal the limit and the time limit is less than x hours.
		// TODO - make the limit and timeout period variables
		if($retries[$ip]['retry'] >= absint($options['max_retries']) && $retries[$ip]['timestamp'] + absint($options['ban_period']) > $now){
			$lockouts[$ip] = strtotime('+1 day', $now);
			unset($retries[$ip]);
		}

		update_option('shopp_lockout_retries', $retries);
		update_option('shopp_lockout_lockouts', $lockouts);

		$this->limit_cleanup();
	}

	function limit_cleanup(){
		$retries = get_option('shopp_lockout_retries');
		$lockouts = get_option('shopp_lockout_lockouts');
		$options = get_option('shopp_lockout_options');

		$now = time();

		foreach($retries as $ip => $data){
			if($data['timestamp'] + absint($options['ban_period']) < $now){
				unset($retries[$ip]);
			}
		}

		foreach($lockouts as $id => $lockout){
			if($lockout < $now){
				unset($lockouts[$id]);
			}
		}

		update_option('shopp_lockout_retries', $retries);
		update_option('shopp_lockout_lockouts', $lockouts);
	}

	function display_save_meta(){
?>
		<input type="submit" class="button-primary" id="save_settings" value="Save Settings" name="submit" />

<?php
	}

	function display_options(){
		$options = get_option('shopp_lockout_options');
?>
		<ul>
			<li>
				<label for="max_retries">Max Retries</label>
				<input type="text" id="max_retries" name="max_retries" size="3" value="<?php echo esc_attr($options['max_retries']); ?>" />
				<p><strong>This is the maxmium number of tries that a single person can attempt to use a credit card.</strong></p>
			</li>
			<li>
				<label for="ban_period">Lockout Period</label>
				<input type="text" id="ban_period" name="ban_period" size="5" value="<?php echo esc_attr($options['ban_period']); ?>" /> (Seconds)
				<p><strong>This determines how long a person will be banned from making transactions. (Example: 3600 = 1 hour) </strong></p>
			</li>
		</ul>
<?php
	}

	function display_settings(){
		$options = get_option('shopp_lockout_options');

		if(!empty($_POST['submit']) && wp_verify_nonce($_POST['stb_lockdown_nonce'], 'nonce_save_settings')){
			$options['max_retries'] = absint($_POST['max_retries']);
			$options['ban_period'] = absint($_POST['ban_period']);

			update_option('shopp_lockout_options', $options);
		}
?>

		<div id="<?php echo esc_attr($this->product); ?>" class="wrap">
	        <h2>Shopp Lockdown</h2>
	        <div class="description">
	            <p>This plugin will attempt to block repetitive failed transactions within Shopp. The purpose of this is to limit fraud attempts.</p>
	        </div>
	        <div class="messages"></div>
			<form action="" method="post">
              		<div id="poststuff" class="metabox-holder has-right-sidebar">
              			<div id="side-info-column" class="inner-sidebar">
							<?php do_meta_boxes('shopp-toolbox_page_'.$this->product, 'side', null); ?>
						</div>

						<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
							<div id="titlediv">
								<div class="inside">
									<?php do_meta_boxes('shopp-toolbox_page_'.$this->product, 'normal', null); ?>
								</div>
							</div>
						</div>
						</div>

					</div>
                <?php wp_nonce_field('nonce_save_settings', 'stb_lockdown_nonce'); ?>
		    </form>
	    </div>
<?php
	}
}
