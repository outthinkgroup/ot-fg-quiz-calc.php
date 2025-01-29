<?php

\GFForms::include_addon_framework();

class OT_QuizAddon extends \GFAddOn {
	// Im not sure how to use the Constellations
	// Container system to configure these settings
	// Which is why they dont follow that convention
	public const slug = 'ot_quizaddon';
	protected $_version =  "1.1.5";
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = self::slug;
	protected $_path = 'ot-fg-quiz-calc/gf-addon.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Outthink Quiz Calc';
	protected $_short_title = 'Quiz Calc';

	private static $_instance = null;

	// {{{ Singleton's Get instance
	/**
	 * Get an instance of this class.
	 *
	 * @return GFConstellationAddon
	 */
	public static function get_instance() {
		if (self::$_instance == null) {
			self::$_instance = new OT_QuizAddon();
		}

		return self::$_instance;
	}
	//}}}

	// {{{ Form Setting Fields

	/**
	 * @param mixed $form
	 * @todo Make this correct
	 * @return (string|((string|string[][])[]|string[]|(string[]|array<array-key, array>|string)[])[])[][]
	 */
	public function form_settings_fields($form) {
		return [
			[
				'title'  => $this->_title,
				'fields' => [
					[
						'label'   => 'Calculate Quiz Results?',
						'type'    => 'checkbox',
						'name'    => 'enabled',
						'tooltip' => "Results will be sent to the specified results page",
						'choices' => [
							[
								'label' => 'Enable',
								'name'  => 'enabled',
							],
						],
					],
					[
						'label' => 'Results Page',
						'type'  => 'select',
						'name'  => 'results_page',
						'choices' => $this->get_pages(),
					],
					[
						'label' => 'Categories',
						'type'  => 'textarea',
						'tooltip' => 'Put all categories on their own line',
						'name'  => 'categories',
					],
					// TODO: uncomment when ready
					// [
					// 	'label'   => 'Randomize the order of Questions and Answers',
					// 	'type'    => 'checkbox',
					// 	'name'    => 'randomize',
					// 	'choices' => [
					// 		[
					// 			'label' => 'Randomize',
					// 			'name'  => 'randomize',
					// 		],
					// 	],
					// ],
					//
				],
			],
		];
	}
	// }}}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		add_action('gform_after_submission', [$this, 'after_submission'], 10, 2);
		add_action('gform_field_advanced_settings', [$this, 'category_field_settings'], 10, 2);
		add_action('gform_editor_js', [$this, 'init_category_field_editor_script']);

		add_action('gform_field_standard_settings', [$this, 'range_field_settings'], 10, 2);
		//TODO make this work
		// add_action('gfrom_pre_render', [$this, 'shuffle_questions']);
	}

	function init_category_field_editor_script() {
?>
		<script type='text/javascript'>
			//adding setting to fields of type "survey" & "radio"
			fieldSettings.survey += ', .select_category';
			fieldSettings.radio += ', .select_category';
			fieldSettings.ot_range += ', .select_category';
			//binding to the load field settings event to initialize the checkbox
			jQuery(document).on('gform_load_field_settings', function(event, field, form) {
				jQuery('#category_select').prop('value', rgar(field, 'field_category'));
				jQuery('#range_max_value').prop('value', rgar(field, 'maxValue'));
				jQuery('#range_min_value').prop('value', rgar(field, 'minValue'));
				jQuery('#range_step_value').prop('value', rgar(field, 'stepValue'));
				jQuery('#range_start_label').prop('value', rgar(field, 'startRangeLabel'));
				jQuery('#range_end_label').prop('value', rgar(field, 'endRangeLabel'));
				jQuery('#isSwapped').prop('checked', rgar(field, 'isSwapped'));
			});

			gform.addAction('gform_post_set_field_property', function(name, field, value, previousValue) {
				if (field.type == "ot_range") {
					if (name == "startRangeLabel") {
						let selector = `#input_${field.formId}_${field.id}_startRangeLabel`
						jQuery(selector).text(value)
					}
					if (name == "endRangeLabel") {
						let selector = `#input_${field.formId}_${field.id}_endRangeLabel`
						jQuery(selector).text(value)
					}
					if (field.isSwapped) {
						jQuery(`[data-field-id="input_${field.formId}_${field.id}"]`).addClass("swap-order")
					} else {
						jQuery(`[data-field-id="input_${field.formId}_${field.id}"]`).removeClass("swap-order")
					}
				}
			});
		</script>
		<?php
	}

	public function styles() {
		$styles = array(
			array(
				'handle'  => 'ot_quizaddon_styles',
				'src'     => $this->get_base_url() . '/ot_quizaddon.css',
				'version' => $this->_version,
				'enqueue' => array(
					array('field_types' => array('ot_range'))
				)
			)
		);

		return array_merge(parent::styles(), $styles);
	}

	public function after_submission($entry, $form) {
		// we dont want to trigger this for admin page submissions
		// Those need implement their own linking of the star and entry
		if ($form[self::slug]['enabled']) {
			return $this->ot_gf_quiz_after_submission($entry, $form);
		}
	}

	public static function get_categories($form) {
		return array_map('trim', explode("\n", $form[self::slug]['categories']));
	}
	public function get_pages() {
		$pages = get_posts(['post_type' => 'page', 'posts_per_page' => -1]);
		if (count($pages) < 1) {
			return ['label' => "No pages Found", "value" => "0"];
		}
		$page_options = array_map(function ($page) {
			return [
				'label' => $page->post_title,
				'value' => $page->ID,
			];
		}, $pages);
		return $page_options;
	}

	function category_field_settings($position, $form_id) {
		//create settings on position 50 (right after Admin Label)

		if ($position == 50) {
			$form = \GFAPI::get_form($form_id);

			if (!$form[self::slug]['enabled']) return;
			$categories = self::get_categories($form);
			if (!is_array($categories) || count($categories) <= 0) return;
		?>
			<li class="select_category field_setting">
				<label for="category_select" style="display:inline;">
					<?php _e("Select what category this is for", "your_text_domain"); ?>
					<?php /* gform_tooltip("form_field_encrypt_value"); */ ?>
				</label>
				<select id="category_select" onchange="SetFieldProperty('field_category', this.value);">
					<?php foreach ($categories as $cat) : ?>
						<option value="<?= $cat; ?>"><?= $cat; ?></option>
					<?php endforeach; ?>
				</select>

			</li>
		<?php
		}
	}

	public function range_field_settings($position, $form_id) {
		if ($position == 10) {
		?>
			<li class="range_field_settings field_setting">
				<div>
					<label for="range_start_label">Start Label</label>
					<input type="text" id="range_start_label" oninput="SetFieldProperty('startRangeLabel', this.value);" />
				</div>
				<div>
					<label for="range_end_label">End Label</label>
					<input type="text" id="range_end_label" oninput="SetFieldProperty('endRangeLabel', this.value);" />
				</div>
				<div>
					<label for="range_max_value">Max Value</label>
					<input type="number" id="range_max_value" oninput="SetFieldProperty('maxValue', this.value);" />
				</div>
				<div>
					<label for="range_min_value">Min Value</label>
					<input type="number" id="range_min_value" oninput="SetFieldProperty('minValue', this.value);" />
				</div>
				<div>
					<label for="range_step_value">Step Value</label>
					<input type="number" id="range_step_value" oninput="SetFieldProperty('stepValue', this.value);" />
				</div>
				<div>
					<input type="checkbox" id="isSwapped" onchange="SetFieldProperty('isSwapped', this.checked);" />
					<label for="isSwapped" class="inline">Swap Labels</label>
				</div>
			</li>
<?php
		}
	}


	public function ot_gf_quiz_after_submission($entry, $form) {
		$calculated_cats = [];
		$form_fields = $form['fields'];

		//shape form fields to be easier to work with
		$lookup = array_reduce($form_fields, [$this, 'field_reducer'], array());

		foreach ($entry as $question_id => $selected_answer_id) {
			if (!array_key_exists($question_id, $lookup)) {
				continue;
			}

			$field_config = $lookup[$question_id];

			$category = $field_config['category'];
			if (!array_key_exists($category, $calculated_cats)) {
				$calculated_cats[$category] = 0;
			}

			if (array_key_exists('choices', $field_config)) {
				$answer_score = $field_config['choices'][$selected_answer_id];
			} elseif (array_key_exists("isSwapped", $field_config) && $field_config["isSwapped"]) {
				// this reverses the number
				$answer_score = intval($field_config['maxValue']) - intval($selected_answer_id) + intval($field_config['minValue']);
			} else {
				$answer_score = intval($selected_answer_id);
			}

			$calculated_cats[$category] += $answer_score;
		}

		$results_page = get_the_permalink($form[self::slug]['results_page']);
		$results_page .= "?quiz_results={$form['id']}&" . http_build_query($calculated_cats);
		wp_redirect($results_page);
		die;
	}

	public function field_reducer($acc, $field) {
		if ($field['type'] != 'survey' && $field['type'] != 'radio' && $field['type'] != 'ot_range') return $acc;
		$data = [
			'label' => $field['label'],
			'category' => trim($field['field_category']),
		];

		if ($field['type'] == 'survey') {
			$data['choices'] = array_reduce($field['choices'], [$this, 'survey_choice_reducer'], array());
		}
		if ($field['type'] == 'radio') {
			$data['choices'] = array_reduce($field['choices'], [$this, 'radio_choice_reducer'], array());
		}

		if ($field['type'] == "ot_range") {
			$data["isSwapped"] = array_key_exists("isSwapped", (array)$field) ? $field["isSwapped"] : 0;
			$data["maxValue"] = $field['maxValue'];
			$data["minValue"] = $field['minValue'];
		}


		$acc[$field['id']] = $data;
		return $acc;
	}

	public function survey_choice_reducer($acc, $choice) {
		$acc[$choice['value']] = $choice['score'];
		return $acc;
	}
	public function radio_choice_reducer($acc, $choice) {
		$acc[$choice['value']] = is_numeric($choice['value']) ? intval($choice['value']) : 0;  // if we cant do math then just set it to zero.
		return $acc;
	}

	public function shuffle_questions($form) {
		// array_flip
	}
	public function shuffle_choices() {
	}

	public static function results_from_cats($form, $params) {
		$scores = [];
		foreach (self::get_categories($form) as $cat) {
			if (!isset($params[$cat])) continue;
			$scores[$cat] = is_numeric($params[$cat]) ? intval($params[$cat]) : $params[$cat];
		}
		return $scores;
	}
}
