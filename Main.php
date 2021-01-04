
application/x-httpd-php presserly_random_timed_quiz.php ( PHP script text )
<?php
/* 
Plugin Name: RS Math Quiz

Description: Create math quizzes

*/
function quiz_scripts()
{
    
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_style('quiz_css', $plugin_url . 'CSS_custom/style.css');
    wp_enqueue_script('quiz_js', $plugin_url . 'JQuery_JS_Functions/quiz.js');
    
}

/*wp-admin styles*/
function admin_scripts()
{
    
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_style('quiz_css', $plugin_url . 'CSS_custom/admin.css');
    
}

/*add shortcodes*/
add_action('init', 'quiz_init');
function quiz_init()
{
    // Turn off shortcode display in Gutenberg
    if(!is_admin()) {
    add_shortcode('random_timed_quiz', 'quiz_sc');
    add_shortcode('random_timed_quiz_results', 'quiz_results_sc');
	}
    add_action('wp_enqueue_scripts', 'quiz_scripts');
    add_action('admin_enqueue_scripts', 'admin_scripts');
    
}

/*post type for quizzes and questions*/
function quiz_post_type()
{
    register_post_type('random-timed-quiz', array(
        'labels' => array(
            'name' => __('Quiz'),
            'singular_name' => __('Quiz'),
            'add_new_item' => __('Add New Quiz', 'compsci-ia'),
            'new_item' => __('New Quiz', 'compsci-ia'),
            'edit_item' => __('Edit Quiz', 'compsci-ia')
        ),
        'public' => true,
        'has_archive' => false,
        'supports' => array(
            'title'
        ),
        'menu_icon' => 'dashicons-welcome-learn-more'
        
    ));
    register_post_type('quiz-questions', array(
        'labels' => array(
            'name' => __('Quiz Questions'),
            'singular_name' => __('Question'),
            'add_new_item' => __('Add New Question', 'compsci-ia'),
            'new_item' => __('New Question', 'compsci-ia'),
            'edit_item' => __('Edit Question', 'compsci-ia')
        ),
        'public' => true,
        'has_archive' => false,
        'supports' => array(
            'title',
            'editor'
        ),
        'menu_icon' => 'dashicons-admin-comments'
        
    ));
    register_post_type('quiz-results', array(
        'capability_type' => 'post',
        'capabilities' => array(
            'create_posts' => 'do_not_allow'
        ),
        'map_meta_cap' => true,
        'labels' => array(
            'name' => __('Quiz Results'),
            'singular_name' => __('Quiz result'),
            'add_new_item' => __('Add New Quiz Result', 'compsci-ia'),
            'new_item' => __('New result', 'compsci-ia'),
            'edit_item' => __('Edit Quiz result', 'compsci-ia')
        ),
        'public' => true,
        'has_archive' => false,
        'supports' => array(
            'title'
        ),
        'menu_icon' => 'dashicons-welcome-write-blog'
        
    ));
}
add_action('init', 'quiz_post_type');

/*taxonomy for question categories and results in quiz category*/
function category_taxonomy()
{
    register_taxonomy('question-category', 'quiz-questions', array(
        'label' => __('Question category'),
        'rewrite' => array(
            'slug' => 'question-category'
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true
    ));
	
    $categories = array('number-theory', 'algebra', 'geometry', 'combinatorics', 'misc');
    foreach ($categories as $value){
	 wp_insert_term($value, 'question-category');
	 wp_insert_term($value .'easy', 'question-category', array('parent' => term_exists($value)));
	 wp_insert_term($value .'medium', 'question-category', array('parent' => term_exists($value)));
	 wp_insert_term($value .'hard', 'question-category', array('parent' => term_exists($value)));
     }

    register_taxonomy('quiz-category', 'quiz-results', array(
        'label' => __('Quiz category'),
        'rewrite' => array(
            'slug' => 'quiz-category'
        ),
        'hierarchical' => false,
        'show_ui' => true,
        'show_admin_column' => true
    ));
}
add_action('init', 'category_taxonomy');

/*function for session timings*/
function register_session()
{

		if (!session_id()){
			session_start();
		}

}

/*function for session results submited*/
function start_quiz()
{

	register_session();

    
    $_SESSION['quiz_qanda']                    = array();
    $_SESSION['quiz_qanda']['quizid']          = sanitize_text_field($_POST['id']);
    $_SESSION['quiz_qanda']['quiz_start_time'] = time();
    $_SESSION['quiz_qanda']['quiz_total_qs']   = sanitize_text_field($_POST['questions']);
    $_SESSION['quiz_qanda']['quiz_total_as']   = '0';
    $_SESSION['quiz_results']                  = '';
    
    echo 'saved';
    
    die();
}
add_action('wp_ajax_start_quiz', 'start_quiz');
add_action('wp_ajax_nopriv_start_quiz', 'start_quiz');

/*function for session results submited*/
function save_answer()
{

	register_session();
    
    $user_id = get_current_user_id();
    
    $_SESSION['quiz_qanda'][$_POST['question_id']] = sanitize_text_field($_POST['answer_value']);
    $answers = $_SESSION['quiz_qanda']['quiz_total_as'];
    $answers++;
    $_SESSION['quiz_qanda']['quiz_total_as'] = $answers;
    
    if ($_SESSION['quiz_qanda']['quiz_total_qs'] == $answers) {
        echo 'complete';
    }else{
        echo 'saved';
    }
    die();
}
add_action('wp_ajax_save_answer', 'save_answer');
add_action('wp_ajax_nopriv_save_answer', 'save_answer');

/*function for saving session*/
function save_quiz()
{

	register_session();
    
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    
    $score_correct = presserly_quiz_get_quiz_score($_SESSION['quiz_qanda']);
    
    $percentage_score = 100 / $_SESSION['quiz_qanda']['quiz_total_qs'] * $score_correct;
    
    if (get_post_meta($_SESSION['quiz_qanda']['quizid'], 'test_pass_percentage', true) <= $percentage_score) {
        $test_passed = 'pass';
    } else {
        $test_passed = 'fail';
    }
    
    
    /*ignore empty test*/
    if ($_SESSION['quiz_qanda']['quizid'] > 0) {
        
        if ($user_id > 0) {
            $login = $current_user->user_login;
        } else {
            $login = $user_id;
        }
        
        $title = date("d/m/y H:i:s") . ' - ' . $login;
        
        $post    = array(
            'post_author' => $user_id,
            'post_content' => '',
            'post_date' => date('Y-m-d H:i:s'),
            'post_date_gmt' => date('Y-m-d H:i:s'),
            'post_status' => 'publish',
            'post_title' => $title,
            'post_type' => 'quiz-results'
        );
        $post_id = wp_insert_post($post);
        
        $user = $user_id;
        if ($user < 1) {
            $user = 'anon';
        }
        
        update_post_meta($post_id, 'prtq_candidate_user_id', $user);
        update_post_meta($post_id, 'prtq_candidate_user_login', $login);
        update_post_meta($post_id, 'prtq_test_taken', $_SESSION['quiz_qanda']['quizid']);
        update_post_meta($post_id, 'prtq_test_score', $percentage_score);
        update_post_meta($post_id, 'prtq_test_passed', $test_passed);
        
        
        foreach ($_SESSION['quiz_qanda'] as $key => $value) {
            update_post_meta($post_id, 'prtq_'. $key, $value);
        }
        
        /*set quiz cat*/
        $qid = $_SESSION['quiz_qanda']['quizid'];
        wp_set_post_terms($post_id, $qid, 'quiz-category', false);
        
        /*restrict quiz*/
        if (get_post_meta($_SESSION['quiz_qanda']['quizid'], 'users_can_take_quiz_only_once', true) == 'on' && $user_id > 0) {
            $key = 'quiz_' . $_SESSION['quiz_qanda']['quizid'];
            update_user_meta($user_id, $key, 'on');
        }
        
        /*email notify*/
        if (get_post_meta($_SESSION['quiz_qanda']['quizid'], 'notify_administrator_of_results', true) == 'on' && $post_id > 0) {
            
            
            $to = get_post_meta($_SESSION['quiz_qanda']['quizid'], 'email_address_to_notify', true);
            if ($to == '') {
                $to = get_option('admin_email');
            }
            
            $email_array = explode(",", $to);
	    $email = $email_array[0];
	    $send = trim($email);
            
            $subject = $user_name . 'math quiz results';
            
            $url = get_option('home') . '/wp-admin/post.php?post=' . $post_id . '&action=edit';
            
            $message = "A new quiz has been taken by this student.  You can view the results here: \r\n\r\n" . $url;
            
	    $response = wp_mail($send, $subject, $message);
            
        }
        
    }
    
	/*reset session after save*/
    $_SESSION['quiz_qanda']   = array();
    $_SESSION['quiz_results'] = $test_passed . round($percentage_score);
    
    echo $test_passed . round($percentage_score);
    
    die();
}
add_action('wp_ajax_save_quiz', 'save_quiz');
add_action('wp_ajax_nopriv_save_quiz', 'save_quiz');

//return results stored for use in shortcode
function quiz_results_sc()
{

	register_session();
    
    if ($_SESSION['quiz_results']) {
        $data = $_SESSION['quiz_results'];
    } else {
        $data = 'void0';
    }
    $html = '<div id="results_score"></div><script>jQuery(function() {presserly_quiz_show_results(\'' . $data . '\');});</script>';
    
    return $html;
}

/*add results summary to dashboard*/
function dashboard_widget($post, $callback_args)
{
    
    /*for each quiz count number of posts and number of pass meta*/
    $args    = array(
        'post_type' => 'random-timed-quiz',
        'posts_per_page' => '-1'
    );
    $quizzes = get_posts($args);
    foreach ($quizzes as $quiz) {
        echo get_the_title($quiz->ID);
        
        $args               = array(
            'post_type' => 'quiz-results',
            'posts_per_page' => '-1',
            'tax_query' => array(
                array(
                    'taxonomy' => 'quiz-category',
                    'field' => 'slug',
                    'terms' => $quiz->ID
                )
            )
        );
        $totaltaken         = count(get_posts($args));
        $args['meta_key']   = 'prtq_test_passed';
        $args['meta_value'] = 'pass';
        $totalpass          = count(get_posts($args));
        $passrate           = 100 / $totaltaken * $totalpass;
        echo ' - ' . $totaltaken . ' tests taken. ' . $totalpass . ' passed. ' . round($passrate, 2) . '%<br>';
    }
    
}

// Function used in the action hook
function add_dashboard_widgets()
{
    wp_add_dashboard_widget('math_quiz_dashboard_widget', 'Quiz Results', 'dashboard_widget');
}

// Register the new dashboard widget with the 'wp_dashboard_setup' action
add_action('wp_dashboard_setup', 'add_dashboard_widgets');

/*add_submenu_page for instructions*/
function admin_actions()
{
    
    add_submenu_page('edit.php?post_type=random-timed-quiz', __('Get started', 'get-started'), __('Get started', 'get-started'), 'manage_options', 'get-started', 'get_started');
    add_submenu_page('edit.php?post_type=random-timed-quiz', __('Download results', 'download-results'), __('Download results', 'download-results'), 'manage_options', 'download-results', 'download_results');
    
}
add_action('admin_menu', 'admin_actions');


/*add some helpful notes in to wp-admin*/
function get_started()
{
?>
<h1>How to build a random timed quiz</h1>
<h3>First we need some questions to ask</h3>
<p>Go to Quiz Questions and Add New. Enter your question in the title bar and add any additional text or images into the text editor.</p>
<p>All questions are multiple choice and only one answer can be selected.  Add up to 10 answer options and tick the box if that is a correct answer.  More than one answer can be marked correct.</p>
<p>Use the question category box on the right hand side to add this question to a category.  You can add the question to as many categories as you need.  Click publish to save and continue adding questions.</p>
<h3>Now set up a quiz</h3>
<h4>Basic settings</h4>
<p>Go to Quiz and Add New. Give your quiz a title and then begin filling in the settings. Enter the number of minutes long the test can last.  If the limit is 1 hour and ten minutes then enter '70'</p>
<p>Tick the box 'Make quiz public' if users do not need to be logged in to take the test.</p>
<p>Tick the box 'Make quiz active' to make the quiz live.  Untick the box to stop the quiz and prevent people starting it.</p>
<p>Tick the box 'Users can take the quiz only once' to limit logged in users to one entry of each quiz only.</p>
<p>Tick the box 'Display questions in random order' to mix up questions from different categories.  If unticked the questions will display in category order with all of one category shown, then all of the next category and so on.</p>
<p>Tick the box 'Display answer options in random order' to shuffle the answer options for each question.  If unticked the answer ooptions will be listed in the order they were added.
<p>The clock can be set to turn red to indicate that time is running out.  To enable this feature just enter a number of seconds in this box. So entering '120' would mean the clock turns red when there is two minutes remaining.</p>
<p>Tick the box 'Show number of questions remaining' to display quiz progress under the clock such as 'Question 4 of 16'.</p>
<p>The test pass percentage is the minimum percentage of answers which must be correct to pass the test.  So if you require 60% correct to pass then enter '60'.</p>
<h4>Quiz contents</h4>
<p>If you wish to change the colours of the clock and quiz buttons enter new 6 digit hex ref codes in these boxes.</p>
<p>There are also 3 optional message boxes to control what message is displayed when a user forgets to select an answer, any message you wish to add under the skip or submit buttons and the message you may wish to show if the current user is not permitted to take the quiz.</p>
<h4>Quiz questions</h4>
<p>While the questions asked in each quiz will be randomly selected you can set a quota to ensure cetain topics or 'question categories' are asked in each quiz.  So, for example, if you were creating a science quiz with 30 random questions you might want to ensure that 
each test includes the same number of chemistry, biology and physics questions.</p>
<p>To do that you would need to have created questions (see above) and added each one to a question category e.g. 'biology'.  You would then choose 'biology' from the question category drop down and enter how many questions should be taken from that category e.g. '10'.</p>
<p>Use the following drop downs to add more categories and the number of questions for each category.  That number of questions will be randomly selected from all the questions you have added.  Click publish to save.</p>
<h4>Ending the quiz</h4>
<p>There are a number of additional options where you can control the messages that are shown and what should happen when the quiz is completed.</p>
<p>You can choose to display a message when teh quiz completes or runs out of time.</p>
<p>Alternatively you can redirect to a specific page when the quiz is completed.  If you wish to display the user's score on another page after the redirect then please add the shortcode [random_timed_quiz_results] to that page.</p>
<h4>Quiz results</h4>
<p>At the end of the quiz you can display a message and the user's test score if you wish.</p>
<p>Tick the box 'Notify administrator of results' if you wish to send out an email whenever a quiz is completed.  Add the addresses to be emailed in the box provided.</p>
<h3>Start your quiz</h3>
<p>Once you have saved your quiz you will see the permalink at the top of the screen by the quiz title.  Use this link to access your live quiz and take the test. Alternatively you can paste the shortcode shown at the top of your quiz settings onto any page to add the quiz.</p>
<h3>View the results</h3>
<p>Test results will be saved under Quiz Results whenever anyone completes the test, runs out of time while taking the test or closes the test while it is active.</p>
<p>A results summary box has been added to your wp-admin dashboard welcome screen to show how many tests have been taken and passed.</p>
<p>To view the specific answers of any saved quiz result go to Quiz Results and click on the title of the result you wish to view.  You'll see correct answers marked green and incorrect ones marked red.</p>
<p>A full csv export of quiz results is available under Quiz / Download results.</p>
<?php
}

function user_profile_fields($user)
{
?>
 
   <h3>Quizzes taken and restricted</h3><p>Untick a restricted quiz to allow user to retake</p>
   <table class="form-table">
    <tr>        
        <td><hr>
          <?php
    
    $args      = array(
        'post_type' => 'quiz-results',
        'posts_per_page' => '-1',
        'author' => $user->ID
    );
    $results   = get_posts($args);
    $quizarray = array();
    foreach ($results as $result) {
        $quizid = get_post_meta($result->ID, 'prtq_quizid', true);
        if (get_post_meta($quizid, 'users_can_take_quiz_only_once', true) == 'on' && !in_array($quizid, $quizarray)) {
            $quizarray[] = $quizid;
        }
    }
    
    foreach ($quizarray as $quizid) {
        $key      = 'quiz_' . $quizid;
        $selected = '';
        if (get_user_meta($user->ID, $key, true) == 'on') {
            $selected = 'checked';
        }
        echo '<input type="checkbox" id="' . $quizid . '" name="quiz_' . $quizid . '" ' . $selected . '><label for="quiz_' . $quizid . '">' . get_the_title($quizid) . '</label><br/>';
    }
    
?>

          
        </td>
    </tr>
 
  </table>
<?php
}
add_action('show_user_profile', 'user_profile_fields');
add_action('edit_user_profile', 'user_profile_fields');

/*SAVE EXTRA FIELDS*/
function save_extra_profile_fields($user_id)
{
    
    if (!current_user_can('edit_user', $user_id))
        return false;
    
    $args    = array(
        'post_type' => 'random-timed-quiz',
        'posts_per_page' => '-1',
        'author' => $user->ID
    );
    $quizzes = get_posts($args);
    foreach ($quizzes as $quiz) {
        $key = 'quiz_' . $quiz->ID;
		$value = sanitize_text_field($_POST[$key]);
        update_usermeta($user_id, $key, $value);
    }
}
add_action('edit_user_profile_update', 'save_extra_profile_fields');
add_action('personal_options_update', 'save_extra_profile_fields');


// ADD NEW COLUMNS FOR RESULTS POSTS
function quiz_columns_head($defaults)
{
    $defaults['quiz-name'] = 'Quiz name';
    
    return $defaults;
}
add_filter('manage_quiz-results_posts_columns', 'quiz_columns_head');

function quiz_columns_content($column_name, $post_ID)
{
    if ($column_name == 'quiz-name') {
        $quiz = get_post_meta($post_ID, 'prtq_quizid', true);
        if ($quiz) {
            echo get_the_title($quiz);
        }
    }
}
add_action('manage_quiz-results_posts_custom_column', 'quiz_columns_content', 10, 2);

add_filter('manage_edit-quiz-results_sortable_columns', 'quiz_sortable_columns');
/*make columns sortable*/
function quiz_sortable_columns($columns)
{
    $columns['quiz-name'] = 'quiz-name';
    return $columns;
}

add_filter('manage_quiz-results_posts_columns', 'results_column_order');
function results_column_order($columns)
{
    $n_columns = array();
    $move      = 'quiz-name'; // what to move
    $before    = 'date'; // move before this
    foreach ($columns as $key => $value) {
        if ($key == $before) {
            $n_columns[$move] = $move;
        }
        $n_columns[$key] = $value;
    }
    return $n_columns;
}

add_action('pre_get_posts', 'presserly_quiz_custom_orderby');
function presserly_quiz_custom_orderby($query)
{
    if (!is_admin())
        return;
    
    $orderby = $query->get('orderby');
    
    if ('quiz-name' == $orderby) {
        $query->set('meta_key', 'prtq_quizid');
        $query->set('orderby', 'meta_value_num');
    }
}

/*Add a quick start link to the plugins page*/
function presserly_quiz_settings_link($links)
{
    $settings_link = '<a href="/wp-admin/edit.php?post_type=random-timed-quiz&page=get-started">' . __('Quick start guide') . '</a>';
    array_push($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'presserly_quiz_settings_link');


include 'cpt_meta/quiz.php';
include 'cpt_meta/question.php';
include 'cpt_meta/result.php';
include 'content/content.php';

?>
