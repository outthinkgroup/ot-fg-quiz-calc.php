<?php
/**
 * Plugin Name:     Outthink Quiz Calc
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     ot-gf-quiz
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Ot_Gf_Quiz
 */


add_action('gform_after_submission_1', 'ot_gf_quiz_after_submission', 10, 2);
function ot_gf_quiz_after_submission($entry, $form) {
	$calculated_cats = [];
	$form_fields = $form['fields'];

	//shape form fields to be easier to work with
	$lookup = array_reduce($form_fields, 'field_reducer', array());

	foreach ($entry as $question_id => $selected_answer_id) {
		if (!array_key_exists($question_id, $lookup)) {
			continue;
		}

		$category = $lookup[$question_id]['admin_label'];
		if ( !array_key_exists( $category, $calculated_cats ) ) {
			$output[$category] = 0;
		}

		$answer_score = $lookup[$question_id]['choices'][$selected_answer_id];
		$calculated_cats[$category] += $answer_score;
	}

	$results_page = get_field('results_page', 'option');
	$results_page .= "?" . http_build_query($calculated_cats);
	wp_redirect($results_page);
	die;
}


function field_reducer($acc, $field) {
	$choices = array_reduce($field['choices'], 'choice_reducer', array());
	$acc[$field['id']] = [
		'label'=>$field['label'], 
		'admin_label'=>$field['adminLabel'], 
		'choices'=>	$choices,
	];
	return $acc;
}

function choice_reducer($acc, $choice) {
	$acc[$choice['value']] = $choice['score'];
	return $acc;
}

const HIGH = "high";
const LOW = "low";
const MEDIUM = "moderate";

function label_score($score) {
	switch ($score) {
	case $score <= 10:
		return LOW;
		break;
	case $score <= 14:
		return MEDIUM;
		break;
	default:
		return HIGH;
		break;
	}
}

add_shortcode('quiz_results', 'quiz_results_shortcode');
function quiz_results_shortcode(){
	$result_sets = get_field('result_sets', 'option');
	$results = array_map(function($set){
		$set_id = $set['set_name'];
		$set['score'] = $_GET[$set_id] ?? 0;
		$set['label'] = label_score($set['score']);
		return $set;
	}, (array) $result_sets);
	usort($results, function($a, $b){
		return $b['score'] - $a['score'];
	});

?>
	<script>
	window.history.replaceState(null, '', window.location.pathname);// this may not be needed
	</script>
	<style>
	.result-layout{
		max-width: 1280px;
		margin: 0 auto;
		padding: 0 20px;
	}	
	.result-layout h3 {
		font-size: 2.5rem;
		margin-bottom: 40px;
	}
	.result-layout h4{
		color: var(--ast-global-color-0);
	}
	.score__label{
		text-transform: capitalize;
		font-weight: 700;
		color:var(--ast-global-color-2)
	}
	.score__value{
		color:var(--ast-global-color-3);
	}
	.result-layout article:not(:last-child){
		margin-bottom: 80px;
		position: relative;
	}
	.result-layout article:not(:last-child):after{
		content: "";
		display: block;
		width: 200px;
		height: 3px;
		background-color: #FAB350;
		position: absolute;
		bottom: -40px;
	}
	</style>
	<div class="result-layout">
	<h3>Your Results</h3>
	<div class="results">
	<?php foreach ($results as $result): ?>
		<article class="set">
			<h4>
				<?php echo $result['set_heading']; ?>: 
				<span class="score">
					<span class="score__label"><?php echo $result['label']; ?></span> 
					<!-- <span class="score__value"><?php echo $result['score']; ?><span>-->
				</span>
			</h4>

			<div class="set__summary"><?php echo $result['set_summary']; ?></div>
			<?php if ( $result['label'] == HIGH ): ?>
			<div class="set__explained">
				<?php echo $result['results_high']; ?>
			</div>
			<?php endif; ?>
		</article>
	<?php endforeach; ?>
	</div>
	</div>
<?php }

