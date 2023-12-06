<?php



\GFForms::include_addon_framework();

class OT_QuizAddon extends \GFAddOn {
	// Im not sure how to use the Constellations
	// Container system to configure these settings
	// Which is why they dont follow that convention
	public const slug = 'ot_quizaddon';
	protected $_version =  "1.1.1";
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
	}

	function init_category_field_editor_script() {
?>
		<script type='text/javascript' >
			//adding setting to fields of type "text"
			fieldSettings.survey += ', .select_category';
			//binding to the load field settings event to initialize the checkbox
			jQuery(document).on('gform_load_field_settings', function(event, field, form) {
				jQuery('#category_select').prop('value', rgar(field, 'field_category'));
			});
		</script>
		<?php
	}

	public function after_submission($entry, $form) {
		// we dont want to trigger this for admin page submissions
		// Those need implement their own linking of the star and entry
		if ($form[self::slug]['enabled']) {
			return $this->ot_gf_quiz_after_submission($entry, $form);
		}
	}
	public function get_categories($form) {
		return explode("\n", $form[self::slug]['categories']);
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
			error_log(print_r($position, true));
			$form = \GFAPI::get_form($form_id);

			if (!$form[self::slug]['enabled']) return;
			$categories = $this->get_categories($form);
			if (!is_array($categories) || count($categories) <= 0) return;
		?>
			<li class="select_category field_setting">
				<label for="field_encrypt_value" style="display:inline;">
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

	public function ot_gf_quiz_after_submission($entry, $form) {
		$calculated_cats = [];
		$form_fields = $form['fields'];

		//shape form fields to be easier to work with
		$lookup = array_reduce($form_fields, [$this, 'field_reducer'], array());

		foreach ($entry as $question_id => $selected_answer_id) {
			if (!array_key_exists($question_id, $lookup)) {
				continue;
			}

			$category = $lookup[$question_id]['category'];
			if (!array_key_exists($category, $calculated_cats)) {
				$calculated_cats[$category] = 0;
			}

			$answer_score = $lookup[$question_id]['choices'][$selected_answer_id];
			$calculated_cats[$category] += $answer_score;
		}

		$results_page = get_the_permalink($form[self::slug]['results_page']);
		$results_page .= "?" . http_build_query($calculated_cats);
		wp_redirect($results_page);
		die;
	}


	public function field_reducer($acc, $field) {
		$choices = array_reduce($field['choices'], [$this, 'choice_reducer'], array());
		$acc[$field['id']] = [
			'label' => $field['label'],
			'category' => $field['field_category'],
			'choices' =>	$choices,
		];
		return $acc;
	}

	public function choice_reducer($acc, $choice) {
		$acc[$choice['value']] = $choice['score'];
		return $acc;
	}
}
