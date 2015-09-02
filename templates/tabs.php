<?php
wp_enqueue_script('jquery-ui-tabs');
echo '<div class="utw"><div id="'.$tab_id.'">';

$sidebars_widgets = wp_get_sidebars_widgets();
$tabbed_widgets_ids = (array) $sidebars_widgets[$widget_area];

echo '<ul>';
foreach ($tabbed_widgets_ids as $id) {
	if (!isset($wp_registered_widgets[$id])) continue;

	$callback = array($wp_registered_widgets[$id]['callback'][0], 'get_settings');
	$settings = call_user_func_array($callback, array());
	$number = $wp_registered_widgets[$id]['params'][0]['number'];

	$title = strip_tags($settings[$number]['title']);
	if ($title) echo "<li><a href=\"#$id\">$title</a></li>";
}
echo '</ul>';

dynamic_sidebar($widget_area);

echo '</div>';
echo '<script type="text/javascript">jQuery(document).ready(function($) { $("#'.$tab_id.'").tabs(); });</script>';
echo '</div>';
