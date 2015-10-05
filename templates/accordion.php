<?php
wp_enqueue_script('jquery-ui-accordion');
echo '<div class="utw"><div id="'.$tab_id.'">';
dynamic_sidebar($widget_area);
echo '</div>';
echo '<script type="text/javascript">jQuery(document).ready(function($) { $("#'.$tab_id.'").accordion({header: "> div > h2"}); });</script>';
echo '</div>';
