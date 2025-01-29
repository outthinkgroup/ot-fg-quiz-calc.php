```php
/**
 * Loops through each category and outputs the score based on the `$output_asked_for` and the score.
 *
 * This is where the you say if the score is between x and y output "Medium" or whatever
 *
 * @param Array $scores the categories and the numeric score for quiz taken
 * @param GFForm $form the form the results are for.
 * @param string $output_asked_for what the format of the results should be. Use this to determine what the value should be of the scrores array $scores[$category]=<p>score label</p> or <img src=$score_src />
 **/
add_filter('ot_quiz_result_scores', function ($scores, $form, $output_asked_for){
	if($form['id'] == 4){
		foreach ($scores as $cat=>$score){
			$score = $score / 3; // we always want the avg. There are only ever 3 questions per category

			switch($output_asked_for){
				case "text":
					$scores[$cat] = mc_ot_quiz_result_text($score);
					break;
				case "graphic":
					$scores[$cat] = mc_ot_quiz_result_graphic($score);
					break;
				case "gauge":
					$scores[$cat] = mc_ot_quiz_result_graphic($score);
					break;
				default:
					// by default we just want to show the avg
					$scores[$cat] = $score;
					break;
			}
		}
	}
	return $scores;
}, 10, 3);
```
