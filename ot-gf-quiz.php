<?php

/**
 * Plugin Name:     Outthink Quiz Calc
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     ot-gf-quiz
 * Domain Path:     /languages
 * Version:         0.2.0
 *
 * @package         Ot_Gf_Quiz
 */


require_once('gf-range-field.php');
// create the gf addon
function bootstrap_addon() {
	if (!method_exists('GFForms', 'include_addon_framework')) {
		return;
	}
	include_once plugin_dir_path(__FILE__) . "/gf-addon.php";
	\GFAddOn::register(OT_QuizAddon::class);
	\GF_Fields::register(new OT_RangeField());
}
add_action('gform_loaded', 'bootstrap_addon');


//		window.history.replaceState(null, '', window.location.pathname); // this may not be needed
/*╭──────────────────────────╮*/
/*│    [   Shortcodes   ]    │*/
/*╰──────────────────────────╯*/

add_shortcode('ot_quiz_result', function ($atts) {
	[
		'cat' => $cat,
		'output' => $output,
	] = shortcode_atts([
		'cat' => null,
		'output' => 'text', // or 'graphic'
	], $atts);
	if (!isset($_GET['quiz_results'])) return "";
	$form = \GFAPI::get_form(intval($_GET['quiz_results']));
	$scores = OT_QuizAddon::results_from_cats($form, $_GET);
	$scores = apply_filters('ot_quiz_result_scores', $scores, $form, ($output ?? "text"));
	return $scores[$cat];
});
