<?php
class OT_RangeField extends GF_Field {
	public $type = "ot_range";

	public function get_form_editor_field_title() {
		return esc_attr__('Range', 'gravityforms');
	}

	function get_form_editor_field_settings() {
		return array(
			'error_message_setting',
			'label_setting',
			'label_placement_setting',
			'admin_label_setting',
			'rules_setting',
			'duplicate_setting',
			'default_value_setting',
			'description_setting',
			'css_class_setting',
			'range_field_settings',
		);
	}

	public function get_field_input($form, $value = '', $entry = null) {
		$form_id         = $form['id'];
		$is_entry_detail = $this->is_entry_detail();
		$id              = (int) $this->id;

		// if ($is_entry_detail) {
		// 	return "<p>$value</p>";
		// }
		$field_id = $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";
		$type = $this->type;
		ob_start()
?>
		<div class="ginput_container ginput_container_<?= $type; ?>">
			<?php if ($this->failed_validation) : ?>
				<p class="error-message"><?= $this->errorMessage ?></p>
			<?php endif; ?>
			<label for="<?= "input_" . $id; ?>" class="sr-only">
				<?php
					$label = "Slide it to the left if {$this->startRangeLabel} sounds correct, or slide it to the right if {$this->endRangeLabel} sounds more correct.";
					echo apply_filters("ot_quiz_range_slider_label",$label, $this->startRangeLabel, $this->endRangeLabel, $this->label );
				?>
			</label>
			<div data-field-id=<?= $field_id ;?> class="gfield_<?= $type; ?> flex-row <?= $this->isSwapped ? "swap-order":"" ;?>">
				<span class="startRangeLabel rangeLabel" id="<?= $field_id . "_startRangeLabel" ?>"><?= $this->startRangeLabel ?? ""; ?></span>

				<input type="range" id="<?= "input_" . $id; ?>" name="input_<?= $id ?>" value="<?= $value; ?>" min="<?= $this->minValue(); ?>" max="<?= $this->maxValue(); ?>" step="<?= $this->stepValue(); ?>">

				<span class="endRangeLabel rangeLabel" id="<?= $field_id . "_endRangeLabel" ?>"><?= $this->endRangeLabel ?? ""; ?></span>
			</div>
		</div>
<?php
		$input = ob_get_clean();
		return $input;
	}

	public function get_field_content($value, $force_frontend_label, $form) {
		$form_id         = $form['id'];
		$admin_buttons   = $this->get_admin_buttons();
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$is_admin        = $is_entry_detail || $is_form_editor;
		$field_label     = $this->get_field_label($force_frontend_label, $value);
		$field_id        = $is_admin || $form_id == 0 ? "input_{$this->id}" : 'input_' . $form_id . "_{$this->id}";
		$field_content   = !$is_admin ? '{FIELD}' : sprintf("%s<div class=\"isAdmin\"><label class='gfield_label' for='%s'>%s</label>{FIELD}</div>", $admin_buttons, $field_id, esc_html($field_label));
		//
		return $field_content;
	}

	public function maxValue() {
		return $this->maxValue ?? 10;
	}
	public function minValue() {
		return $this->minValue ?? 0;
	}
	public function stepValue() {
		return $this->stepValue ?? 1;
	}

	public function validate($value, $form) {
		if (!$this->isRequired) return;
		if ($value !== "0") return;
		$this->failed_validation = true;
		$this->errorMessage = "This is required. Please move the slider";
	}
}

function array_find($items, $fn) {
	foreach ($items as $item) {
		if ($fn($item)) {
			return $item;
		}
	}
	return false;
}
