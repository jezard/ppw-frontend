<div class="overview-page">
	<section class="section-ln">
		<div class="col-1-2">
			<h3>Recent activities</h3>
			<div id="recent-table">
				<table>
					<tr>
						<th style="width:40px">Wk</th>
						<th style="width:150px">Date</th>
						<th style="width:auto">Activity</th>
					</tr>
					<?php $week_no = 0; ?>
					<?php foreach ($recentActivities as $recentActivity): ?>
						<?php 
							$iter_week = date("W", strtotime($recentActivity['activity_date'])); 
							$timestamp = strtotime($recentActivity['activity_date']);
							if($week_no == $iter_week){
								$div = "";
							}else{
								$div = " border-top: 1px black solid;";
							}
						?>
						<tr data-id="<?php echo $recentActivity['activity_id']; ?>" id="tr-<?php echo strtotime('today', $timestamp).'000';//round down to the midnight before, and match output of date finder ?>">
							<td style="width:initial; font-family: monospace;<?php echo $div; ?>"><?php echo $iter_week ?></td>
							<td style="width:initial; font-family: monospace;<?php echo $div; ?>"><?php echo date("D dS M y", $timestamp); ?></td>
							<td style="width:initial;<?php echo $div; ?>"><?php echo $recentActivity['activity_name']; ?></td>
							<?php $week_no = $iter_week; ?>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>
			<div id="calendar"></div>
		</div>
		<div class="col-1-2 ride-basic">
			<h3>Basic ride info <date id="activity-date"></date></h3>
			<div class="basic-form">
				<?php echo form_open('activity', array('id' => 'frm_activity')); ?>
					<input type="hidden" id="activity_id" name="activity_id" value="">
					<label for="activity_title">Tag:</label>
					<select id="standard-ride-select" name="standard_ride_id">
						<option value="0" data-ride_label="[activity name]">Select a tag to copy options</option>
						<?php 
							foreach ($standard_rides as $standard_ride) {
								echo '<option data-ride_label="'.$standard_ride['ride_label'].'" data-in_or_out="'.$standard_ride['in_or_out'].'" data-race_or_train="'.$standard_ride['race_or_train'].'" value="'.$standard_ride['id'].'">'.$standard_ride['ride_label'].'</option>';
							}
						?>
					</select><br>
					<label for="activity_title">Name:</label>
					<input id="activity_title" name="activity_title" type="text" maxlength="50">
					<label for="activity_description">Notes:</label>
					<textarea id="activity_notes" rows="5" name="activity_notes"></textarea>
					<button class="btn-default" type="submit">Update</button>

					<!-- only for strava connected users -->
					<?php if($strava_user): ?>
					<input type="hidden" id="strava_upload" name="strava_upload" value="">
					<span class="strava-options">

						<!-- don't show buttons if uploading or uploaded to strava instead show link on strava -->
						<button id="strava-it" class="btn-default" style="<?php echo ($poll_strava)? 'display:none' : ''; ?>"><strong><em>OR</em></strong> Update and save to <span style="color:#FB4B02; font-weight:bold; letter-spacing: -1px">STRAVA</span></button>

					</span>
					<?php endif; ?>
				</form>
				<?php echo form_open('activity/delete', array('style' => 'display:inline-block', 'id' => 'delete-activity')); ?>
					<input type="hidden" id="activity_id2" name="activity_id" value="">
					<button id="del-activity" class="btn-danger" type="submit">Delete Activity</button>
				</form>
				<div id="upload-status" style="display:none">
					<span id="status-text" class="note" style="display:block"></span>
				</div>

			</div>
		</div>
	</section>
	<iframe id="activity-container" allowTransparency="true" scrolling="no"></iframe>
</div>

<?php $access_token = (get_token() != "") ? get_token() : "unknown" ?>

<script>
$(document).on("click", ".active", function(e){
	var timestamp = jQuery.fn.dp_calendar.getDate().getTime();
	localStorage.setItem("selectedDate", jQuery.fn.dp_calendar.getDate());
	var originalPos = $('#recent-table').scrollTop();//get original position of tr in table
	$('#recent-table').scrollTop(0);//reset to top
	
	if($('#tr-' + timestamp).length == 0){
		timestamp += 1000 * 60 *60;//add on extra hour if no match e.g. BST->GMT - might need test to check and subtract an hour 
	}

	var rowpos = $('#tr-' + timestamp).position(); //get pos of desired tr
	$('#recent-table').scrollTop(originalPos);//and move back to original pos before anyone guesses what's happened
	$('#recent-table').animate({scrollTop:rowpos.top}, 500, 'swing');//scroll smoothly to position, and then trigger the clicked event
	$('#tr-' + timestamp).trigger("click");;

});



jQuery(document).ready(function(){
	//send the standard ride data to the iframe
	jQuery('#standard-ride-select').on("change", function(){
		var title = jQuery("#standard-ride-select option:selected").data("ride_label");
		jQuery('#activity_title').val(title);
		//set the values of the filters (radio buttons)
		jQuery('#frm_activity').submit();
	});

	//direct user to most recent activity or just updated
	var filename;
	var activity_id = <?php echo '"'.$displayActivity.'"'; ?>;
	var url = <?php echo '"http://'.$this->config->item('go_ip').'/view/activity/"'; ?> + activity_id + <?php echo '"/'.$access_token.'/"'; ?>;
	jQuery('#activity_id, #activity_id2').val(activity_id);
	//get title/name of activity 
	jQuery.post( '<?php echo $this->config->item('base_url') .'activity/get'; ?>', { activity_id: activity_id }, function(data){
		data = JSON.parse(data);

		jQuery('#activity_title').val(data[0].activity_name);
		jQuery('#activity_notes').val(data[0].activity_notes);
		jQuery('#activity-date').text(data[0].activity_date);
		filename = data[0].filename;

		reset_strava_controls(data[0].strava_activity_id);

	});

	jQuery('#activity-container').attr('src', url);

	//send the form data to strava uploader before saving
	jQuery('#strava-it').on("click", function(e){
		jQuery(this).attr("disabled","disabled");
		activity_id = $('#activity_id').val();
		console.log(activity_id);
		e.preventDefault();
		$('#status-text').html('Uploading activity to <span style="color:#FB4B02; font-weight:bold; letter-spacing: -1px">STRAVA</span>');
		var name = $('#activity_title').val();
		var desc = $('#activity_notes').val();

		$('#strava_upload').val('1');

		jQuery.post( '<?php echo $this->config->item('base_url') .'/strava/upload'; ?>', { name: name, description:desc, activity_id: activity_id }, function(data){
			if(data == "error"){
				alert("There was an issue uploading to Strava, If the problem continues try reconnecting to Strava (Menu -> My Account)");
			}else{
				console.log(data);
				$('#upload-status').slideDown(500, function(){
					$( "#frm_activity" ).delay(500).submit();
				});
			}
			

		});
	});
	//still on the strava tip... 
	<?php if($poll_strava): ?>
	jQuery('#strava-it').attr("disabled","disabled");

	var interval;
	interval = setInterval(poll_strava, 5000);


	function poll_strava(){
		jQuery.post( '<?php echo $this->config->item('base_url') .'strava/upload_status'; ?>', { activity_id: activity_id }, function(data){
			console.log(data);
			data_array = data.split('^');
			$('#upload-status').show();
			$('#status-text').html(data_array[0]);
			if(data_array[1] == 'failed' || data_array[1] == 'success'){
				//window.location = document.URL;
				clearInterval(interval);
				jQuery('#strava-it').removeAttr("disabled");	
			}
		});
	}
	<?php endif; ?>

	$('#recent-table tr').on("click", function(e){
		var activity_id = $(this).data('id');
		var url = <?php echo '"http://'.$this->config->item('go_ip').'/view/activity/"'; ?> + activity_id  + <?php echo '"/'.$access_token.'/"'; ?>;
		jQuery('#activity_id, #activity_id2').val(activity_id);

		//get title/name of activity 
		jQuery.post( '<?php echo $this->config->item('base_url') .'activity/get'; ?>', { activity_id: activity_id }, function(data){
			data = JSON.parse(data);
			jQuery('#activity_title').val(data[0].activity_name);
			jQuery('#activity_notes').val(data[0].activity_notes);
			jQuery('#activity-date').text(data[0].activity_name);
			filename = data[0].filename;

			reset_strava_controls(data[0].strava_activity_id);
		});
		jQuery('#activity-container').attr('src', url);
	})


	var events_array = new Array(
		<?php
			$html = '';

			foreach ($recentActivities as $activity) {

				$activityDate = date_create_from_format('Y-m-d H:i:s', $activity['activity_date']);
				$html .= '{
					startDate: new Date('.date_format($activityDate, 'Y, (n-1), j, G').'),
					endDate: new Date('.date_format($activityDate, 'Y, (n-1), j, G').'),
				},';
			}
			$html = rtrim($html, ',');
			echo $html;
		?>
	);

	var d = new Date(localStorage.getItem("selectedDate"));
	var today = new Date();

	if(d.getFullYear() == '1970'){
		d = today;
	}

	jQuery("#calendar").dp_calendar({
		events_array: events_array,
		date_selected: d,
	});

	jQuery("#delete-activity").submit(function(e){
		var reply = confirm("Delete this activity?");

		if (reply != true) {
		    e.preventDefault();
		}else{
			return true;
		}
	});

	//bit gak but this routine will first submit the child (iframe) form and then the parent
	var childSaved = false;//need this to stop a submit loop
	jQuery('#frm_activity').submit(function(e){
		if(childSaved == false){
			e.preventDefault();
			//get the value of the selected standard ride options
			var activity_title = jQuery('#activity_title').val();
			var in_or_out = jQuery("#standard-ride-select option:selected").data("in_or_out");
			var race_or_train = jQuery("#standard-ride-select option:selected").data("race_or_train");
			var standard_ride_id = jQuery("#standard-ride-select option:selected").val();

			var data = {title: activity_title, in_or_out: in_or_out, race_or_train: race_or_train, standard_ride_id: standard_ride_id};

			var o = document.getElementById('activity-container');

			o.contentWindow.postMessage(data, "*");
			childSaved = true;
			jQuery('#frm_activity').submit();
		}		
	});
});

function reset_strava_controls(strava_activity_id){
	//if an activity has already been uploaded to strava for the selected activity
	if(strava_activity_id != null){
		$('#upload-status').show();
		$('#strava-it').hide();
		$('#status-text').html('View activity on <a href="https://www.strava.com/activities/'+ strava_activity_id +'" target="_blank"><span style="color:#FB4B02; font-weight:bold; letter-spacing: -1px">STRAVA</span></a>');
	}else{
		$('#upload-status').hide();
		$('#strava-it').show();
	}
}

</script>