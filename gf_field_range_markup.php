		<div class="ginput_container ginput_container_<?= $type; ?>">
			<div class="gfield_<?= $type; ?> flex-row">
				<span class="startRangeLabel rangeLabel" id="<?= $field_id . "_startRangeLabel" ?>"><?= $this->startRangeLabel ?? ""; ?></span>

				<input type="range" id="<?= $field_id; ?>" name="input_<?= $id ?>" value="<?= $value; ?>" min="<?= $this->minValue(); ?>" max="<?= $this->maxValue(); ?>" step="<?= $this->stepValue(); ?>">

				<span class="endRangeLabel rangeLabel" id="<?= $field_id . "_endRangeLabel" ?>"><?= $this->endRangeLabel ?? ""; ?></span>
			</div>
		</div>
