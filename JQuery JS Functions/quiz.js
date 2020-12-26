jQuery(document).ready(function() {


	jQuery(".question_button").on('click', function(){
	
		var answer = '';
		var id = jQuery(this).parent().attr('id');
		
	if(jQuery(this).hasClass("skip")){
		answer = 'skip';
	}else{
		answer = jQuery('input[name=q'+id+']:checked').val();
	}	
		
	if(answer == null){
		presserly_quiz_null_confirmed();
	}else{
	
	    presserly_quiz_save_quiz_answer(id, answer);
		
		/*increase question count*/
		jQuery('#current_question').html( +jQuery('#current_question').html() + +1);
		
		jQuery(this).parent().hide();
		jQuery(window).scrollTop(0); 
		jQuery(this).parent().next().show();
		
		if(jQuery(this).parent().next().attr('id') == 'results' ){
			presserly_quiz_over('complete_redirect');		
		}
		
	}	
	
	});
	
	/*detect browser close*/
	jQuery( window ).unload(function() {
		presserly_quiz_save_quiz();  
		presserly_quiz_over();		

	});	
	
});


function presserly_quiz_start_quiz_timer(mins){

	jQuery('.start_quiz').remove();
	jQuery('#question_count_text').show();

	presserly_start_quiz();

	jQuery('.quiz_question').first().show(); 

	var mins = mins;
	var deadline = new Date(Date.parse(new Date()) + mins * 60 * 1000);
	presserly_quiz_initializeClock('clockdiv', deadline);

}


function presserly_quiz_getTimeRemaining(endtime) {
  var t = Date.parse(endtime) - Date.parse(new Date());
  var seconds = Math.floor((t / 1000) % 60);
  var minutes = Math.floor((t / 1000 / 60) % 60);
  var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
  var days = Math.floor(t / (1000 * 60 * 60 * 24));
  return {
    'total': t,
    'days': days,
    'hours': hours,
    'minutes': minutes,
    'seconds': seconds
  };
}


var timeinterval = '';

function presserly_quiz_stop_the_clock(){
          clearInterval(timeinterval);
		  jQuery('#clockdiv').hide();
}  

function presserly_quiz_initializeClock(id, endtime) {
  var clock = document.getElementById(id);
  var daysSpan = clock.querySelector('.days');
  var hoursSpan = clock.querySelector('.hours');
  var minutesSpan = clock.querySelector('.minutes');
  var secondsSpan = clock.querySelector('.seconds');
  
 var redalert = jQuery('#red_alert_seconds').val();
 

  function presserly_quiz_updateClock() {
    var t = presserly_quiz_getTimeRemaining(endtime);

    daysSpan.innerHTML = t.days;
    hoursSpan.innerHTML = ('0' + t.hours).slice(-2);
    minutesSpan.innerHTML = ('0' + t.minutes).slice(-2);
    secondsSpan.innerHTML = ('0' + t.seconds).slice(-2);

    if (t.total <= 0) {
        clearInterval(timeinterval);
	    presserly_quiz_over('timeout_redirect');
    }else if((t.total/1000) == redalert){
		jQuery('#clockdiv').addClass('redalert');
	}
	
  }
  
  timeinterval = setInterval(presserly_quiz_updateClock, 1000);

  presserly_quiz_updateClock();
}


function presserly_quiz_over(redirect){
	presserly_quiz_stop_the_clock();
	jQuery('.quiz_question').hide();
	jQuery('.start_quiz').hide();
	jQuery('#text_before_'+redirect).show();
	jQuery('#results').show();
	jQuery('input[type=radio]').attr('checked',false);
}


function presserly_quiz_show_results(data){
		
		//presserly_show_test_score
		data = data.trim();
		 var pass = data.substring(0,4);
		 var score = data.substring(4);
		 jQuery('#results_score').html('<div class="presserly_quiz_counter" data-count="'+ score + '">0%</div><h1 class="' + pass + ' passfail">' + pass + '!</h1>');
		 
		 var $this = jQuery('.presserly_quiz_counter'),
		countTo = $this.attr('data-count');
  
		jQuery({ countNum: $this.text()}).animate({
		countNum: countTo
		},

		{

		duration: 300,
		easing:'linear',
		step: function() {
		$this.text(Math.floor(this.countNum));
		},
		complete: function() {
		$this.text(this.countNum + '%');
		jQuery('.passfail').show();
		}

		});  

}
