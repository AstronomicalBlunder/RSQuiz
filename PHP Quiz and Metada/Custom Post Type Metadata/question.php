<?php
/*QUESTION CUSTOM POST TYPE*/

/*meta boxes for questions*/
function presserly_quiz_questions_add_meta_boxes( $post ){
	add_meta_box( 'presserly_question_meta_box', __( 'Answers', 'quiz-questions' ), 'presserly_quiz_question_build_meta_box', 'quiz-questions', 'normal', 'high' );
}
add_action( 'add_meta_boxes_quiz-questions', 'presserly_quiz_questions_add_meta_boxes' );

function presserly_quiz_question_build_meta_box( $post ){
	wp_nonce_field( basename( __FILE__ ), 'presserly_quiz_question_meta_box_nonce' );
	
    $fields = presserly_quiz_question_fields();
  
  echo '<div class="inside">';
  
  
  foreach ($fields as $field => $type){
  
	$$slug =  get_post_meta( $post->ID, $field, true );
	
	$selected = '';
	
	if($$slug == 'on'){
		$selected = 'checked';
	}
 	?>
		<p>
	
		<label style="width:150px;display:inline-block;"><?php echo ucfirst(str_replace("_", " ", $field)); ?></label>
		
		<input type="<?php echo $type; ?>" id="<?php echo $field; ?>" name="<?php echo $field; ?>" <?php if($type != 'checkbox'){ echo 'value="'. $$slug .'"';}else{ echo $selected; } ?>>
		</p>
	<?php 
  }
  
  ?>

</div>
<?php
}

function presserly_quiz_question_save_meta_boxes_data( $post_id ){
	if ( !isset( $_POST['presserly_quiz_question_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['presserly_quiz_question_meta_box_nonce'], basename( __FILE__ ) ) ){
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}	
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	}	
  
    $fields = presserly_quiz_question_fields();

  foreach ($fields as $field => $type){
    $slug = strtolower(str_replace(" ", "-", $field));

	update_post_meta( $post_id, $field, $_POST[$field] );
		
	}
}
add_action( 'save_post_quiz-questions', 'presserly_quiz_question_save_meta_boxes_data', 10, 2 );

/*change title field for cpts*/
function presserly_random_quiz_change_title_text( $title ){
     $screen = get_current_screen();
  
     if  ( 'quiz-questions' == $screen->post_type ) {
          $title = 'Question';
     }
  
     return $title;
}
  
add_filter( 'enter_title_here', 'presserly_random_quiz_change_title_text' );

/*core function to get question field array*/
function presserly_quiz_question_fields(){

	$maxanswers  = 10;
	$i = 0;

	$fields = array();

	while($i < $maxanswers){
	$i++;
	$field = 'answer_'.$i;
	$fields[$field] = 'text';
	$field = 'answer_'.$i.'_correct';
	$fields[$field] = 'checkbox';
	}

return $fields;
}



/*is question correct*/
function presserly_quiz_get_quiz_score($answers){

$number_correct = 0;

	foreach($answers as $key => $value){
	
		$correct = presserly_quiz_question_correct($key, $value);
		
		if($correct == 'correct'){
			$number_correct++;
		}
	
	}

return $number_correct;

}

/*check if specific question is correct*/
function presserly_quiz_question_correct($key, $input){
	$correct_answers = array();

	$answer_meta = get_post_meta($key);
	
	$maxanswers  = 10;
	$i = 0;

	while($i < $maxanswers){
	$i++;
		if($answer_meta['answer_'.$i.'_correct'][0] == 'on'){
		$correct_answers[] = $answer_meta['answer_'.$i][0];
		}
	}	
	
	if($input != '' && in_array($input, $correct_answers)){
	return 'correct';
	}else{
	return 'incorrect';
	}
}

?>
