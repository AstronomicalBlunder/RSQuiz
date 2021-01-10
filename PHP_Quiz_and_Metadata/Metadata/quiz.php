<?php

/*QUIZ CUSTOM POST TYPE*/


/*meta boxes for quizzes*/
function quiz_add_meta_boxes( $post ){
	add_meta_box( 'quiz_meta_box', __( 'Quiz settings', 'random-timed-quiz' ), 'build_meta_box', 'random-timed-quiz', 'normal', 'high' );	
}
add_action( 'add_meta_boxes_random-timed-quiz', 'quiz_add_meta_boxes' );

function build_meta_box( $post ){
	wp_nonce_field( basename( __FILE__ ), 'build_meta_box_nonce' );
	
    $fields = presserly_quiz_fields();
  
  echo '<div class="inside quiz_admin">';
  
  echo '<p>To add this quiz to your site pages use the shortcode [random_timed_quiz id="'.$post->ID.'"]</p>';
  
  foreach ($fields as $field => $type){
  
	$$slug =  get_post_meta( $post->ID, $field, true );
	
	$selected = '';
	
	if($$slug == 'on'){
		$selected = 'checked';
	}
	
	$label = ucfirst(str_replace("_", " ", $field));
	
	
	/*build titles*/
	$subtitle = '';
	if($field == 'quiz_duration_in_minutes'){$subtitle = 'Basic settings';}
	if($field == 'main_colour'){$subtitle = 'Quiz contents';}
	if($field == 'question_category_1'){$subtitle = 'Quiz questions';}
	if($field == 'redirect_to_page_once_complete'){$subtitle = 'Ending the quiz';}
	if($field == 'show_score_after_last_question_answered'){$subtitle = 'Quiz results';}
	
	
	if($subtitle){
	echo '<h2>'.$subtitle.'</h2>';
	}
	?>
	<p>
	
	<label style="width:300px;display:inline-block;"><?php echo $label; ?></label>

	<?php
	
	if($field == 'main_colour' || $field == 'secondary_colour' ){
	echo '#';
		if($$slug == '' && $field == 'main_colour'){
		$$slug = '00BF96';
		}else if($$slug == '' && $field == 'secondary_colour'){
		$$slug = '00816A';
		}
	}
	
	if($type == 'select'){
	
	$terms = get_terms( array(
    'taxonomy' => 'question-category',
    'hide_empty' => false,
	) );
	
	echo '<select id="'.$field.'" name="'.$field.'">';
	echo '<option value=""';
	if($$slug == ''){ echo 'selected';}
	echo '>- choose a question category -</option>';
	
	foreach($terms as $term){
	echo '<option value="'.$term->term_id.'"';
	if($$slug == $term->term_id){echo ' selected';}
	echo '>'.$term->name.'</option>';
	}
	
	echo '</select>';
	
	
	}elseif($type == 'pageselect'){
	
	$args = array(
		'post_type' => 'page',
		'posts_per_page' => -1
	);
	$pages = get_posts($args);
	
	echo '<select id="'.$field.'" name="'.$field.'">';
	echo '<option value=""';
	if($$slug == ''){ echo 'selected';}
	echo '>- choose a page to redirect to if required -</option>';
	
	foreach($pages as $page){
	echo '<option value="'.$page->ID.'"';
	if($$slug == $page->ID){echo ' selected';}
	echo '>'.$page->post_name.'</option>';
	}
	
	echo '</select>';
	
	}else{
 	?>
				
		<input type="<?php echo $type; ?>" id="<?php echo $field; ?>" name="<?php echo $field; ?>" <?php if($type != 'checkbox'){ echo 'value="'. $$slug .'"';}else{ echo $selected; } ?>>
		
	<?php 
	
	}
	
	/*add notes*/
	if($field == 'email_address_to_notify'){
	echo '<p>- Use commas to separate multiple email addresses.</p>';
	}
	
	echo '</p>';
	  
  }
  ?>

</div>



<?php
}

function save_meta_boxes_data( $post_id ){
	if ( !isset( $_POST['build_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['build_meta_box_nonce'], basename( __FILE__ ) ) ){
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}	
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	}	
  
    $fields = quiz_fields();

    foreach ($fields as $field => $type){

	update_post_meta( $post_id, $field, $_POST[$field] );
		
	}
}
add_action( 'save_post_random-timed-quiz', 'save_meta_boxes_data', 10, 2 );


/*core function to get quiz field array*/
function quiz_fields(){

	$fields = array();

/*quiz settings*/

	$fields['quiz_duration_in_minutes'] = 'number';

	$fields['make_quiz_public'] = 'checkbox';
	
	$fields['make_quiz_active'] = 'checkbox';
	
	$fields['users_can_take_quiz_only_once'] = 'checkbox';	
	
	$fields['display_questions_in_random_order'] = 'checkbox';		
	
	$fields['display_answer_options_in_random_order'] = 'checkbox';	
	
	$fields['time_remaining_when_clock_turns_red_in_seconds'] = 'number';	
	
	$fields['show_number_of_questions_remaining'] = 'checkbox';		
	
	$fields['test_pass_percentage'] = 'number';	

/*quiz contents*/

	$fields['main_colour'] = 'text';

	$fields['secondary_colour'] = 'text';	

	$fields['message_when_no_answer_is_selected'] = 'text';	
	
	$fields['message_to_show_under_skip_confirm_buttons'] = 'text';		
	
	$fields['message_to_show_if_user_cannot_access_quiz'] = 'text';	
	
/*questions*/

	// $count terms in categeory
	$args = array('hide_empty' => false);
	$categories = wp_count_terms( 'question-category', $args );
	if($categories < 1){$categories = 1;}
	
	$i = 0;

	while($i < $categories){
	$i++;
	$field = 'question_category_'.$i;
	$fields[$field] = 'select';
	$field = 'question_category_count_'.$i;
	$fields[$field] = 'number';
	}	

/*ending the quiz*/

	$fields['redirect_to_page_once_complete'] = 'pageselect';
	
	$fields['message_to_show_after_last_question_answered'] = 'text';
	
	$fields['redirect_to_page_on_timeout'] = 'pageselect';
	
	$fields['message_to_show_after_timeout'] = 'text';	

/*quiz results*/

	$fields['show_score_after_last_question_answered'] = 'checkbox';

	$fields['notify_administrator_of_results'] = 'checkbox';
	
	$fields['email_address_to_notify'] = 'text';


return $fields;
}


/*change title field for cpts*/
function quiz_title( $title ){
     $screen = get_current_screen();
  
     if  ( 'random-timed-quiz' == $screen->post_type ) {
          $title = 'Quiz name';
     }
  
     return $title;
} 
add_filter( 'enter_title_here', 'quiz_title' );

?>
