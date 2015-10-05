<?php
/*
Plugin Name: Ultimate Tabbed Widgets
Plugin URI: http://vdvn.me/pga
Description: Allows to create widget areas that can be turned into tabs or accordion using widgets or shortcodes
Author: Claude Vedovini
Author URI: http://vdvn.me/
Version: 1.1
Text Domain: ultimate-tabbed-widgets

# The code in this plugin is free software; you can redistribute the code aspects of
# the plugin and/or modify the code under the terms of the GNU Lesser General
# Public License as published by the Free Software Foundation; either
# version 3 of the License, or (at your option) any later version.

# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
#
# See the GNU lesser General Public License for more details.
*/

/** Initialize plugin */
add_action('plugins_loaded', array('UltimateTabbedWidgets', 'get_instance'));

class UltimateTabbedWidgets {

	private static $instance;

	public static function get_instance() {
		if (!self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	function __construct() {
		add_action('init', array(&$this, 'init'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('widgets_init', array(&$this, 'register_widget'));

		// Make plugin available for translation
		// Translations can be filed in the /languages/ directory
		add_filter('load_textdomain_mofile', array(&$this, 'smarter_load_textdomain'), 10, 2);
		load_plugin_textdomain('ultimate-tabbed-widgets', false, dirname(plugin_basename(__FILE__)) . '/languages/' );
	}

	function smarter_load_textdomain($mofile, $domain) {
		if ($domain == 'ultimate-tabbed-widgets' && !is_readable($mofile)) {
			extract(pathinfo($mofile));
			$pos = strrpos($filename, '_');

			if ($pos !== false) {
				# cut off the locale part, leaving the language part only
				$filename = substr($filename, 0, $pos);
				$mofile = $dirname . '/' . $filename . '.' . $extension;
			}
		}

		return $mofile;
	}

	function init() {
		add_action('wp_print_styles', array(&$this, 'register_styles'));
		add_shortcode('utw', 'utw_shortcode');
	}

	function admin_menu() {
		add_filter('plugin_action_links_ultimate-tabbed-widgets/ultimate-tabbed-widgets.php', array(&$this, 'add_settings_link'));
		add_options_page(__('Tabbed Widgets', 'ultimate-tabbed-widgets'), __('Tabbed Widgets', 'ultimate-tabbed-widgets'),
				'manage_options', 'ultimate-tabbed-widgets', array(&$this, 'options_page'));
		add_settings_section('default', '', false, 'ultimate-tabbed-widgets');

		register_setting('ultimate-tabbed-widgets', 'utw_widget_areas');
		add_settings_field('utw_widget_areas', __('Widget areas', 'ultimate-tabbed-widgets'),
				array(&$this, 'widget_areas_field'), 'ultimate-tabbed-widgets', 'default');

		register_setting('ultimate-tabbed-widgets', 'utw_disable_theme');
		add_settings_field('utw_disable_theme', __('Disable default theme', 'ultimate-tabbed-widgets'),
				array(&$this, 'disable_theme_field'), 'ultimate-tabbed-widgets', 'default');
	}

	function is_theme_enabled() {
		return !get_option('utw_disable_theme', false);
	}

	function get_sidebars() {
		$sidebars = get_option('utw_widget_areas', 'Default Tabbed Widgets Area');

		if ($sidebars) {
			$sidebars = explode("\n", $sidebars);
			$ids = array_map('sanitize_title', $sidebars);
			return array_combine($ids, $sidebars);
		} else {
			return array();
		}
	}

	function register_widget() {
		if (function_exists('register_sidebar')) {
			$sidebars = $this->get_sidebars();

			foreach($sidebars as $id => $name) {
				register_sidebar(array(
						'name' => $name,
						'id' => $id,
						'before_widget' => '<div id="%1$s" class="%2$s">',
						'after_widget' => '</div>',
						'before_title' => '<h2>',
						'after_title' => '</h2>',));
			}
		}

		register_widget('UltimateTabbedWidgetsWidget');
	}

	function register_styles() {
		if ($this->is_theme_enabled()) {
			wp_enqueue_style('utw-ui', plugins_url('theme/jquery-ui.css', __FILE__), false, '1.0');
		}
	}

	function add_settings_link($links) {
		$url = site_url('/wp-admin/options-general.php?page=ultimate-tabbed-widgets');
		$links[] = '<a href="' . $url . '">' . __('Settings') . '</a>';
		return $links;
	}

	function options_page() { ?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h1><?php _e('Tabbed Widgets Options', 'ultimate-tabbed-widgets'); ?></h1>
			<form method="POST" action="options.php"><?php
				settings_fields('ultimate-tabbed-widgets');
				do_settings_sections('ultimate-tabbed-widgets');
				submit_button(); ?>
			</form>
		</div><?php
	}

	function widget_areas_field() { ?>
		<textarea id="utw_widget_areas" name="utw_widget_areas" rows="5"
		style="width:50%"><?php echo get_option('utw_widget_areas', 'Default Tabbed Widgets Area'); ?></textarea>
		<p><em><?php _e('List the widgets areas you want to define, one by line.', 'ultimate-tabbed-widgets'); ?></em></p><?php
	}

	function disable_theme_field() { ?>
		<label><input type="checkbox" name="utw_disable_theme"
			value="1" <?php checked(get_option('utw_disable_theme')); ?> />&nbsp;
			<?php _e('Check this option if you want to use your own styling.', 'ultimate-tabbed-widgets') ?></label><?php
	}

}

class UltimateTabbedWidgetsWidget extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'tabbed-widgets', 'description' => __('Displays widgets in tabs', 'ultimate-tabbed-widgets'));
		$control_ops = array('id_base' => 'tabbed-widgets');
		$this->WP_Widget('tabbed-widgets', __('Ultimate Tabbed Widgets', 'ultimate-tabbed-widgets'), $widget_ops, $control_ops);
	}

	function widget($args, $instance) {
		global $wp_registered_widgets;
		extract($args);

		echo $before_widget;

		if (!empty($instance['title']))
			echo $before_title . apply_filters('widget_title', $instance['title'], $instance, $this->id_base) . $after_title;

		echo utw_shortcode(array('widgets' => $instance['widgets'],
									'template' => $instance['template']));
		echo $after_widget;
	}

	public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['widgets'] = sanitize_title($new_instance['widgets']);
		$instance['template'] = sanitize_title($new_instance['template']);
		return $instance;
	}

	function form($instance) {
		$instance = wp_parse_args((array) $instance, array(
				'title' => '',
				'widgets' => '',
				'template' => 'tabs'
			));
		$title = esc_attr($instance['title']);
		$widget_area = esc_attr($instance['widgets']);
		$template = $instance['template'];

		$plugin = UltimateTabbedWidgets::get_instance();
		$sidebars = $plugin->get_sidebars();
?>
<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php
		_e('Title:'); ?></label>
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
		name="<?php echo $this->get_field_name('title'); ?>" type="text"
		value="<?php echo $title; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id('widgets'); ?>"><?php
		_e('Widget Area:', 'ultimate-tabbed-widgets'); ?></label>
	<select class="widefat" id="<?php echo $this->get_field_id('widgets'); ?>"
		name="<?php echo $this->get_field_name('widgets'); ?>">
	<?php foreach($sidebars as $id => $name): ?>
		<option value="<?php echo $id; ?>" <?php
			selected($widget_area, $id); ?>><?php echo $name; ?></option>
	<?php endforeach; ?>
	</select>
</p>
<p>
	<label for="<?php echo $this->get_field_id('template'); ?>"><?php
		_e('Template:', 'ultimate-tabbed-widgets'); ?></label>
	<select class="widefat" id="<?php echo $this->get_field_id('template'); ?>"
		name="<?php echo $this->get_field_name('template'); ?>">
		<option value="tabs" <?php selected($template, 'tabs'); ?>><?php
			_e('Tabs', 'ultimate-tabbed-widgets'); ?></option>
		<option value="accordion" <?php selected($template, 'accordion'); ?>><?php
			_e('Accordion', 'ultimate-tabbed-widgets'); ?></option>
	</select>
</p>
<?php
	}
}

function utw_shortcode($atts) {
	global $wp_registered_widgets;
	$atts = shortcode_atts(array(
			'id' => 'utw-' . wp_generate_password(6, false),
			'template' => 'tabs',
			'widgets' => ''
	), $atts, 'utw_shortcode');
	extract($atts);
	if (!$widgets) return;

	$widget_area = sanitize_title($widgets);
	$tab_id = sanitize_title($id);

	$file = locate_template('utw/'. $template . '.php');

	if (empty($file)) {
		$file = dirname(__FILE__) . '/templates/' . $template . '.php';
	}

	ob_start();
	require($file);
	return ob_get_clean();
}
