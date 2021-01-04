<?php
/*RESULTS CUSTOM POST TYPE*/

/*meta boxes for results*/
function results_add_meta_boxes( $post ){
	add_meta_box( 'result_meta_box', __( 'Results', 'quiz-results' ), 'result_build_meta_box', 'quiz-results', 'normal', 'high' );
}
add_action( 'add_meta_boxes_quiz-results', 'results_add_meta_boxes' );

function result_build_meta_box( $post ){
	wp_nonce_field( basename( __FILE__ ), 'result_build_meta_box_nonce' );
	
	
	$meta = get_post_meta( $post->ID);
	
  echo '<div class="inside">';
  
  
  foreach ($meta as $key => $value){

	if(substr($key, 0, 5) == 'prtq_'){
				
		$key = str_replace("prtq_", "", $key);

			$label = $key;
			$input = $value[0];
			$class = '';
	
			if($key == 'quiz_start_time'){
			$input = date('d/m/y - H:i:s', $value[0]);
			}
	
			if($key == 'test_taken'){
			$input = get_the_title($value[0]);
			}	
	
			if($key == 'test_score'){
			$input = round($value[0], 2)  .'%';
			}		
	
			if(is_numeric($key)){
			$label = get_the_title($key);
			$class = quiz_question_correct($key, $input);
			}
	
 	?>
		<p>
	
		<label style="width:150px;display:inline-block;"><?php echo ucfirst(str_replace("_", " ", $label)); ?></label>
		
		<input type="text" id="<?php echo $key; ?>" name="<?php echo $key; ?>" value="<?php echo $input ?>" class="<?php echo $class ?>">
		</p>
	<?php 
	}
  }
  ?>

</div>
<?php
}

function save_meta_boxes_data( $post_id ){
	if ( !isset( $_POST['result_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['result_meta_box_nonce'], basename( __FILE__ ) ) ){
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}	
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	}	
  
    $fields = array();

    foreach ($fields as $field){
    $slug = strtolower(str_replace(" ", "-", $field));

	update_post_meta( $post_id, $slug, $_POST[$slug] );
		
	}
}
add_action( 'save_post_quiz-results', 'save_meta_boxes_data', 10, 2 );


function remove_publish_box() {
    remove_meta_box( 'submitdiv', 'quiz-results', 'side' );
	remove_meta_box( 'tagsdiv-quiz-category', 'quiz-results', 'side' );
}
add_action( 'admin_menu', 'remove_publish_box' );


/*download csv of results*/
function download_results(){

?>
<form action="" method="post">

<?php

if($_POST['submitted'] == '1'){

	$id = $_POST['id'];

	quiz_write_csv($id);

echo 'sent';

}else{
echo '<h1>Download results</h1><select name="id">';
echo '<option value="">- select a quiz -</option>';
	$args = array(
		'post_type' => 'random-timed-quiz',
		'posts_per_page' => -1,
	);
	$posts = get_posts($args);
	
	foreach($posts as $post){
	echo '<option value="'.$post->ID.'">'.$post->post_title.'</option>';
	}
echo '</select><br/>';
echo '<input type="hidden" value="1" name="submitted"><input type="submit" value="Download" class="button button-primary button-large">';

}
?>

</form>
<?php

}

/*write results to csv format*/
function quiz_write_csv($id){

$data = array();
$i = 0;

	$args = array(
		'post_type' => 'quiz-results',
		'posts_per_page' => -1,
		'tax_query' => array(
		 array(
			'taxonomy' => 'quiz-category',
			'field'    => 'slug',
			'terms'    => $id
		 ),
		),		
	);
	$results = get_posts($args);
	
	foreach($results as $result){
	
	$data[$i] = array();
	
		$meta = get_post_meta( $result->ID);
		
		foreach ($meta as $key => $value){
		
			if(substr($key, 0, 5) == 'prtq_'){
				
				$key  = str_replace('prtq_', "", $key);
				
				$label = $key;
				$input = $value[0];
	
				if($key == 'quiz_start_time'){
				$input = date('d/m/y - H:i:s', $value[0]);
				}
	
				if($key == 'test_taken'){
				$input = get_the_title($value[0]);
				}	
	
				if($key == 'test_score'){
				$input = round($value[0], 2)  .'%';
				}		
	
				if(is_numeric($key)){
				/*question and answer*/
				$label = get_the_title($key);
		
				$data[$i][] = $label;
				$data[$i][] = $input;
		
				/*check if correct*/
				$data[$i][] = quiz_question_correct($key, $input);
		
				}else{
				$data[$i][] = $input;
				}
		
			}
		
		}
		
 		$i++;
	
	}


ob_end_clean();
// output headers so that the file is downloaded rather than displayed
header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="results.csv"');
 
// do not cache the file
header('Pragma: no-cache');
header('Expires: 0');
 
// create a file pointer connected to the output stream
$file = fopen('php://output', 'w');
 
// send the column headers
fputcsv($file, array('Candidate user id', 'Candidate user login', 'Test taken', 'Test score', 'Test passed', 'Quizid', 'Quiz start time', 'Quiz total qs'));
 
// output each row of the data
foreach ($data as $row)
{
fputcsv($file, $row);
}

fclose($file);

exit();

}

?>
