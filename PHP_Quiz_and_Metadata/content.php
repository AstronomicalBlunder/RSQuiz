<?php

/*add quiz to content for single quiz and hide content from single quiz-results*/
add_filter( 'the_content', 'quiz_content' ); 
function quiz_content($content){

	ob_start();  

	global $post;
	
	if($post->post_type == 'quiz-results' && is_main_query() ){
	
	$content = 'unavailable';
	
	}else if( $post->post_type == 'random-timed-quiz'  && is_main_query() && is_single() && in_the_loop() ){
	
	$content = $content . quiz_builder($post);
	
	}
	
	echo $content;
	
	$ReturnString = ob_get_contents();
	ob_end_clean();
	return $ReturnString; 	
	
}


/*no index nofollow on quiz results to block indexing*/
function quiz_noindex_for_results(){
	global $post;

    if ( $post->post_type == 'quiz-results' ) {
        echo '<meta name="robots" content="noindex, follow">';
    }
}
add_action('wp_head', 'quiz_noindex_for_results');


/*shortcode returns quiz content*/
function quiz_sc($atts){
	$post = get_post($atts['id']);
	return quiz_builder($post);
}

/*build quiz content*/
function quiz_builder($post){

$meta = get_post_meta($post->ID, '', true);

$userid = get_current_user_id();


	if(($meta['make_quiz_public'][0] == 'on' || is_user_logged_in()) && $meta['make_quiz_active'][0] == 'on' && ($meta['users_can_take_quiz_only_once'][0] != 'on' || (get_user_meta($userid, 'quiz_'.$post->ID, true) != 'on' && $_SESSION['quiz_qanda'] == null) ) ){
		
	$seconds  = $meta['quiz_duration_in_minutes'][0] * 60;
	$dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");
	
	if($meta['quiz_duration_in_minutes'][0] < 1440){
	$days = ' style="display:none;"';
	}else{
	$days = ' style="background-color: #'.$meta['main_colour'][0].'"';
	}
	if($meta['quiz_duration_in_minutes'][0] < 60){
	$hours = ' style="display:none;"';
	}else{
	$hours = ' style="background-color: #'.$meta['main_colour'][0].'"';
	}
	
	
	?>
<div id="clockdiv">
  <div<?php echo $days; ?>>
    <span class="days" style="background-color: #<?php echo $meta['secondary_colour'][0]; ?>; ?>;"><?php echo $dtF->diff($dtT)->format('%a') ?></span>
    <div class="smalltext">Days</div>
  </div>
  <div<?php echo $hours; ?>>
    <span class="hours" style="background-color: #<?php echo $meta['secondary_colour'][0]; ?>;?>;"><?php echo $dtF->diff($dtT)->format('%h') ?></span>
    <div class="smalltext">Hours</div>
  </div>
  <div style="background-color: #<?php echo $meta['main_colour'][0]; ?>;">
    <span class="minutes" style="background-color: #<?php echo $meta['secondary_colour'][0]; ?>; ?>;"><?php echo $dtF->diff($dtT)->format('%i') ?></span>
    <div class="smalltext">Minutes</div>
  </div>
  <div style="background-color: #<?php echo $meta['main_colour'][0]; ?>;">
    <span class="seconds" style="background-color: #<?php echo $meta['secondary_colour'][0]; ?>; ?>;">00</span>
    <div class="smalltext">Seconds</div>
  </div>
  
  <?php if($meta['show_number_of_questions_remaining'][0] == 'on'){ 
  
  /*count total questions*/
  $total_questions = 0;
  
  foreach($meta as $field => $value){
	if(strpos($field, 'question_category_count_') > -1){
		$total_questions = $total_questions + +$value[0];
	}
  }
  echo '<p id="question_count_text" style="color:#'.$meta['main_colour'][0].';">Question <span id="current_question">1</span> of '. $total_questions .'</p>';

   } ?>
   
   
  <?php if($meta['time_remaining_when_clock_turns_red_in_seconds'][0] > 0){ 
  $redalert = $meta['time_remaining_when_clock_turns_red_in_seconds'][0];
  }else{
  $redalert = 0;
  }
  echo '<input type="hidden" id="red_alert_seconds" value="'.$redalert.'">';
  ?>   
   
</div>

	<?php
	
	echo '<input type="button" class="start_quiz" style="background-color: #'. $meta['main_colour'][0] .';border:2px solid #'. $meta['secondary_colour'][0] .'" value="start the quiz" onclick="presserly_quiz_start_quiz_timer(\''.$meta['quiz_duration_in_minutes'][0].'\');" />';
	
	/*build question list*/
	$questions = array();
	
	foreach($meta as $key => $value){
		if(strpos($key, 'question_category_count_') > -1){
		$qcount = $value[0];
		$k = str_replace('question_category_count_', '', $key);
		$categoryid = $meta['question_category_' . $k][0];
		
		$questions = array_merge(get_quiz_questions($categoryid, $qcount, $questions), $questions);
		
		}
	}
	
	$questions = array_reverse($questions);
	
	/*randomise category question order*/
	if(get_post_meta($meta['display_questions_in_random_order'][0]) == 'on'){
	shuffle($questions);
	}	
	
	/*use qids to make questions*/
	$qhtml = '';
	foreach($questions as $question){
	$qhtml.= get_question_box($question, $post->ID);
	}
	
	/**results page*/
	echo '<div class="quiz_question" id="results"><span id="text_before_complete_redirect">'.$meta['message_to_show_after_last_question_answered'][0].'</span><span id="text_before_timeout_redirect">'.$meta['message_to_show_after_timeout'][0].'</span><div id="results_score"></div></div>';
	?>
	<script>

	
	function save_quiz_answer(question_id, answer_value){

	var question_id = question_id;
	var answer_value = answer_value;

	var homeurl = '<?php echo get_home_url(); ?>';
	var ajaxurl = homeurl + "/wp-admin/admin-ajax.php";
	
	jQuery.ajax({
	type:"POST",
	url: ajaxurl,
	data: ({action : 'save_answer', question_id : question_id, answer_value : answer_value }),
	success:function(data){
	
		if(data.trim() == 'complete'){
			save_quiz('complete_redirect');  
		}
	}
	});
	
	}
	
	
	function save_quiz(type){
	var redirect = type;	
	
	var homeurl = '<?php echo get_home_url(); ?>';
	var ajaxurl = homeurl + "/wp-admin/admin-ajax.php";

	jQuery.ajax({
	type:"POST",
	url: ajaxurl,
	data: ({action : 'save_quiz' }),
	success:function(data){

		if(redirect == 'timeout_redirect'){
			quiz_timeout_redirect();
		}else if(redirect == 'complete_redirect'){
			quiz_complete_redirect();	
		}
		
			var show = '<?php echo $meta['show_score_after_last_question_answered'][0]; ?>';		
			if(show == 'on'){
				show_results(data);
			}			
		
	}
	});	
	
	}
	
	function presserly_start_quiz(){

	var id = '<?php echo $post->ID; ?>';
	var questions = '<?php echo count($questions); ?>';

	var homeurl = '<?php echo get_home_url(); ?>';
	var ajaxurl = homeurl + "/wp-admin/admin-ajax.php";

	jQuery.ajax({
	type:"POST",
	url: ajaxurl,
	data: ({action : 'presserly_start_quiz', id : id, questions : questions }),
	success:function(data){
		
		jQuery('#results').prepend('<?php echo $qhtml;?>');
	}
	});	
	
	}

	function quiz_complete_redirect(){
		var redirect = '<?php echo $meta['redirect_to_page_once_complete'][0]; ?>';
		if(redirect > 0){
		var link = '<?php echo get_permalink($meta['redirect_to_page_once_complete'][0]); ?>';
		window.location.href = link;
		}
	}
	
	function quiz_timeout_redirect(){
		
		var redirect = '<?php echo $meta['redirect_to_page_on_timeout'][0]; ?>';
		if(redirect > 0){
		var link = '<?php echo get_permalink($meta['redirect_to_page_on_timeout'][0]); ?>';
		window.location.href = link;
		}	

	}
	
	function quiz_null_confirmed(){

		var message = '<?php echo $meta['message_when_no_answer_is_selected'][0]; ?>';	
		if(message){
		  alert(message);
		}
	
	}
	
	</script>
	<?php
	
	
	}else{
	
	echo '<p>'. $meta['message_to_show_if_user_cannot_access_quiz'][0] .'</p>';
	
	}
}

function get_quiz_questions($categoryid, $qcount, $questions_list){

	$qs = array();

	$args = array(
	'post_type' => 'quiz-questions',
	'tax_query' => array(
		array(
			'taxonomy' => 'question-category',
			'field'    => 'ID',
			'terms'    => $categoryid
		),
	),
	'orderby' => 'rand',
	'post__not_in' => $questions_list,
	'posts_per_page' => $qcount
	);
	$question_posts = get_posts( $args );
	foreach($question_posts as $post){
	$qs[] = $post->ID;
	}

return $qs;

}

/*build question and answers*/
function get_question_box($question, $quizid){
	
	$meta = get_post_meta($quizid, '', true);
	
	$qcontent = get_post($question); 
	$qcontent = $qcontent->post_content;
	
	$qcontent = nl2br($qcontent);
	if($qcontent){$qcontent = '<p>'.$qcontent.'</p>';}

	echo '<div class="quiz_question" id="'.$question.'"><h3>'. get_the_title($question) . '</h3>' . $qcontent;
	$answers = get_quiz_answer_options($question);
	/*randomize answers*/
	if(get_post_meta($quizid, 'display_answer_options_in_random_order', true) == 'on'){
	shuffle($answers);
	}
	
	$i = 0;
	foreach ($answers as $answer){
	$i++;
	echo '<input type="radio" id="q'.$question.$i.'" name="q'. $question .'" value="'.$answer.'"><label for="q'.$question.$i.'">'. $answer .'</label><br/>';
	
	}
	
	echo '<input type="button" style="color: #'. $meta['main_colour'][0] .';background-color: #'. $meta['secondary_colour'][0] .';border:2px solid #'. $meta['main_colour'][0] .'" class="question_button skip" value="skip">';
	echo '<input type="button" style="background-color: #'. $meta['main_colour'][0] .';border:2px solid #'. $meta['secondary_colour'][0] .'" class="question_button confirm" value="confirm">';	
	
	echo '<p>'. get_post_meta($quizid, 'message_to_show_under_skip_confirm_buttons', true) .'</p>';
	
	echo '</div>';

}

/*return answer options*/
function get_quiz_answer_options($question){

	$maxanswers  = 10;
	$i = 0;
	$answer_options = array();

	while($i < $maxanswers){
	$i++;
	$field = 'answer_'.$i;
	$answer = get_post_meta($question, $field, true);
		if($answer){
		$answer_options[] = $answer;
		}
	}
	
	return $answer_options;

}
?>
