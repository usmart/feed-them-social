<?php
class FTS_Facebook_Feed extends feed_them_social_functions {
	function __construct() {
		add_shortcode( 'fts facebook group', array($this,'fts_fb_func'));
		add_shortcode( 'fts facebook page', array($this,'fts_fb_func'));
		add_shortcode( 'fts facebook event', array( $this,'fts_fb_func'));
		add_shortcode( 'fts facebook', array( $this,'fts_fb_func'));
		
		
		//Add Scripts
		add_action('wp_enqueue_scripts', array( $this,'fts_fb_head'));
	}
	
	function  fts_fb_head() {
		wp_enqueue_style( 'fts_fb_css', plugins_url( 'facebook/css/styles.css',  dirname(__FILE__ ) ) );
		wp_register_style( 'fts-font-aweseom-min', plugins_url( 'css/font-awesome.min.css', dirname(__FILE__) ) );  
		wp_enqueue_style('fts-font-aweseom-min'); 
	}
	
	
	//Main Funtion
	function fts_fb_func($atts){
	
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		
		//Eventually add premium page file
		if(is_plugin_active('feed-them-premium/feed-them-premium.php')) {
		   include(WP_CONTENT_DIR.'/plugins/feed-them-premium/feeds/facebook/facebook-premium-feed.php'); 
		}
		else 	{
			extract( shortcode_atts( array(
				'id' => '',
				'type' => '',
				'posts_displayed' => '',
				'height' => '',
				'album_id' => '',
				'image_width' => '',
				'image_height' => '',
				'space_between_photos' => '',
				'hide_date_likes_comments' => '',
				'center_container' => '',
				'image_stack_animation' => '',
				'image_position_lr' => '',
				'image_position_top' => '',
			), $atts ) );
			
			$custom_name = $posts_displayed;
			$fts_limiter = '5';
			$fts_fb_id = $id;
			
		}
		//API Access Token
		$custom_access_token = get_option('fts_facebook_custom_api_token');
		if(!empty($custom_access_token)){
			$access_token = get_option('fts_facebook_custom_api_token');
		}
		else{
			//Randomizer (Custom Facebook Feed guy aka SmashBallon hahaha)
			$values = array('226916994002335|ks3AFvyAOckiTA1u_aDoI4HYuuw','358962200939086|lyXQ5-zqXjvYSIgEf8mEhE9gZ_M','705020102908771|rdaGxW9NK2caHCtFrulCZwJNPyY');
			$access_token = $values[array_rand($values,1)];
		}
		
		//Error Check
		if (!$fts_fb_id){
			return 'Please enter a username for this feed.';
		}
		
		ob_start();
		
		switch($type)	{
			case 'group' :
			$fts_view_fb_link ='https://www.facebook.com/groups/'.$fts_fb_id.'/';
			    break;
			
			case 'page':
			$fts_view_fb_link ='https://www.facebook.com/'.$fts_fb_id.'/';
			    break;
			
			case 'event' :
			$fts_view_fb_link ='https://www.facebook.com/events/'.$fts_fb_id.'/';
			    break;	
				
			case 'albums':
			$fts_view_fb_link ='https://www.facebook.com/'.$fts_fb_id.'/photos_stream?tab=photos_albums';
			    break;	
				
			case 'album_photos':
			$fts_view_fb_link ='https://www.facebook.com/'.$fts_fb_id.'/photos_stream';
			    break;	
			
			case 'hashtag':
			$fts_view_fb_link ='https://www.facebook.com/hashtag/'.$fts_fb_id.'/';
			    break;	
				
			//	case 'videos':
				//	$fts_view_fb_link ='https://www.facebook.com/'.$fts_fb_id.'/videos/';
				//	break;	
		}
		//URL to get page info
		$fb_data_cache = WP_CONTENT_DIR.'/plugins/feed-them-social/feeds/facebook/cache/fb-'.$type.'-'.$fts_fb_id.'-num'.$fts_limiter.'.cache';
		
		if(file_exists($fb_data_cache) && !filesize($fb_data_cache) == 0 && filemtime($fb_data_cache) > time() - 900 && false !== strpos($fb_data_cache,'-num'.$fts_limiter.'') and !$_GET['load_more_ajaxing']) {
			$response = $this->fts_get_feed_cache($fb_data_cache);
		}
		else{
			  
			  //URL to get Feeds
			  if ($type == 'page' && $posts_displayed == 'page_only')	{
				  $mulit_data = array(
					'page_data' => 'https://graph.facebook.com/'.$fts_fb_id.'?access_token='.$access_token.''
				  );  
				   if ($_REQUEST['next_url']){
				  	$mulit_data['feed_data'] = $_REQUEST['next_url'];
				  }
				  else{	  
				   	$mulit_data['feed_data'] = 'https://graph.facebook.com/'.$fts_fb_id.'/posts?limit='.$fts_limiter.'&access_token='.$access_token.'';
				  }
			  }
			  elseif ($type == 'albums')	{
				  $mulit_data = array(
					'page_data' => 'https://graph.facebook.com/'.$fts_fb_id.'?access_token='.$access_token.''
				  );
				  //Check If Ajax next URL needs to be used
				  if ($_REQUEST['next_url']){
				  	$mulit_data['feed_data'] = $_REQUEST['next_url'];
				  }
				  else{	  
				  	$mulit_data['feed_data'] = 'https://graph.facebook.com/'.$fts_fb_id.'/albums?limit='.$fts_limiter.'&access_token='.$access_token.'';
				  }
			  }
			  elseif ($type == 'album_photos')	{
			  	  $mulit_data = array(
					'page_data' => 'https://graph.facebook.com/'.$fts_fb_id.'?access_token='.$access_token.''
				  );
				   //Check If Ajax next URL needs to be used
				  if ($_REQUEST['next_url']){
				  	$mulit_data['feed_data'] = $_REQUEST['next_url'];
				  }
				  else{	  
				  	$mulit_data['feed_data'] = 'https://graph.facebook.com/'.$album_id.'/photos?limit='.$fts_limiter.'&access_token='.$access_token.'';
				  }
			  }
			  elseif ($type == 'hashtag')	{
			  	  $mulit_data = array(
					'page_data' => 'https://graph.facebook.com/search?q=%23'.$fts_fb_id.'&access_token='.$access_token.''
				  );
				  
				  //Check If Ajax next URL needs to be used	 
				  if ($_REQUEST['next_url']){
				  	$mulit_data['feed_data'] = $_REQUEST['next_url'];
				  }
				  else{
				 	$mulit_data['feed_data'] = 'https://graph.facebook.com/search?q=%23'.$fts_fb_id.'&limit='.$fts_limiter.'&access_token='.$access_token.'';
				  }
			  }
			  
			   //	elseif ($type == 'videos')	{
			  //		$mulit_data['feed_data'] = 'https://graph.facebook.com/'.$fts_fb_id.'/videos/uploaded?access_token='.$access_token.'';
			 //		}
			 
			  elseif ($type == 'group')	{
				  $mulit_data = array(
					'page_data' => 'https://graph.facebook.com/'.$fts_fb_id.'?access_token='.$access_token.''
				  );
				  
				   //Check If Ajax next URL needs to be used
				  if ($_REQUEST['next_url']){
				  	$mulit_data['feed_data'] = $_REQUEST['next_url'];
				  }
				  else{
				  	$mulit_data['feed_data'] = 'https://graph.facebook.com/'.$fts_fb_id.'/feed?limit='.$fts_limiter.'&access_token='.$access_token.'';
				  }
			  }
			  else	{
				  $mulit_data = array(
					'page_data' => 'https://graph.facebook.com/'.$fts_fb_id.'?access_token='.$access_token.'',
				  );
				  
				   //Check If Ajax next URL needs to be used
				  if ($_REQUEST['next_url']){
				  	$mulit_data['feed_data'] = $_REQUEST['next_url'];
				  }
				  else{
				 	$mulit_data['feed_data'] = 'https://graph.facebook.com/'.$fts_fb_id.'/feed?limit='.$fts_limiter.'&access_token='.$access_token.'';
				  }
			  }

			 $response = $this->fts_get_feed_json($mulit_data);
			 
			 
			//Make sure it's not ajaxing
			if(!$_GET['load_more_ajaxing']){
						 //Create Cache
						 $this->fts_create_feed_cache($fb_data_cache, $response );
				}
		} // end main else			
	
		//Json decode data and build it from cache or response
		$des = json_decode($response['page_data']);
		$data = json_decode($response['feed_data']);
		
		
		// return error if no data retreived
		if ($type == 'page' && !$data->data)	{
				return 'No Posts Found. Are you sure this is a Facebook Page ID and not a Facebook Group or Event ID?';
		}
		
		
	//Make sure it's not ajaxing
	if(!$_GET['load_more_ajaxing']){
		
		$_REQUEST['fts_dynamic_name'] = trim($this->rand_string(10).'_'.$type);
		
		//Create Dynamic Class Name
		$fts_dynamic_class_name =  '';
		if ($_REQUEST['fts_dynamic_name']){
			$fts_dynamic_class_name =  'feed_dynamic_class'.$_REQUEST['fts_dynamic_name'];
		}
		
		
		// so we can remove the fts-jal-fb-header for our special album view
		if(is_plugin_active('feed-them-premium/feed-them-premium.php'))  {
			if ($title == 'yes' or $title == 'yes') {	?>
			
				<?php
			print '<div class="fts-jal-fb-header">';
			   // Print our Facebook Page Title or About Text. Commented out the group description because in the future we will be adding the about description.
				if ($title == 'yes' or $title == '') {
				  print '<h1><a href="'.$fts_view_fb_link.'" target="_blank">'.$des->name.'</a></h1>';
				}
			   if ($description == 'yes' || $description == '') {
				  print '<div class="fts-jal-fb-group-header-desc">'.$this->fts_facebook_tag_filter($des->description).'</div>';	
				}
				
				 print '</div>';
			
			}
		} 
		else {
			print '<div class="fts-jal-fb-header"><h1><a href="'.$fts_view_fb_link.'" target="_blank">'.$des->name.'</a></h1>';
			print '<div class="fts-jal-fb-group-header-desc">'.$this->fts_facebook_tag_filter($des->description).'</div>';
			print '</div><div class="clear"></div>';
		}

} //End check		

//Make sure it's not ajaxing
if(!$_GET['load_more_ajaxing']){
		
		if (!$FBtype && $type == 'albums' || !$FBtype && $type == 'album_photos') {  
		wp_enqueue_script( 'fts_instagram_masonry_pkgd_js', plugins_url( 'instagram/js/masonry.pkgd.min.js',  dirname(__FILE__) ) ); ?>
		<script>
		jQuery(window).load(function(){ 
            // This is only for the slicker instagram feed
            jQuery('.fts-slicker-facebook-albums').masonry({
              // strangely keeping transitionDuration: 0 always stacks blocks perfect.
              transitionDuration: 0,
              // select the items we want to mason
              itemSelector: '.fts-jal-single-fb-post'
            });
		});
        </script>	           
	
        <div class="fts-slicker-facebook-photos fts-slicker-facebook-albums masonry js-masonry <?php echo $fts_dynamic_class_name ?>" style="margin:auto" data-masonry-options='{ "isFitWidth": <?php if ($center_container == 'no') { ?>false<?php } else {?>true<?php } if ($image_stack_animation == 'no') { ?>, "transitionDuration": 0<?php } ?> }'>
			
	<?php	}
		else { 
       ?>
        
        <div class="fts-jal-fb-group-display <?php echo $fts_dynamic_class_name ?><?php if ($height !== 'auto' && empty($height) == NULL) {?> fts-fb-scrollable<?php } ?>" <?php if ($height !== 'auto' && empty($height) == NULL) {?>style="height:<?php echo $height; ?>"<?php } ?>> <?php }
} //End ajaxing Check	
		
		$fb_post_data_cache = WP_CONTENT_DIR.'/plugins/feed-them-social/feeds/facebook/cache/fb-'.$type.'-post-'.$fts_fb_id.'-num'.$fts_limiter.'.cache';
		
		if(file_exists($fb_post_data_cache) && !filesize($fb_post_data_cache) == 0 && filemtime($fb_post_data_cache) > time() - 900 && false !== strpos($fb_post_data_cache,'-num'.$fts_limiter.'' ) && !$_GET['load_more_ajaxing']) {
			$response_post_array = $this->fts_get_feed_cache($fb_post_data_cache);
		}
		else{
			//Build the big post counter.
			$fb_post_array = array();
			
			//			echo '<pre>';
			//					print_r($data);
			//			echo '</pre>';
			
			$set_zero = 0;
			foreach($data->data as $counter) {
				
				 
				if($set_zero==$fts_limiter)
				break;
				
				$FBtype = $counter->type;
				
				if ($counter->object_id){
					$post_data_key = $counter->object_id;
				}
				else {
					$post_data_key = $counter->id;
				}
				
					//Likes & Comments
					$fb_post_array[$post_data_key.'_likes'] = 'https://graph.facebook.com/'.$post_data_key.'/likes?summary=1&access_token='.$access_token;
					$fb_post_array[$post_data_key.'_comments'] = 'https://graph.facebook.com/'.$post_data_key.'/comments?summary=1&access_token='.$access_token;
				
				//Video	
				if($FBtype == 'video') {
				//		  echo '<pre>';
				//			  print_r($counter);
				//		  echo '</pre>';
					$fb_post_array[$post_data_key.'_video'] = 'https://graph.facebook.com/'.$counter->object_id;
				}
				
				//Photo
				$FBalbum_cover = $counter->cover_photo;
				if ($type == 'albums' && !$FBalbum_cover) {
					unset($counter);
					continue;
				}
				if($type == 'albums'){
					  $fb_post_array[$FBalbum_cover.'_photo'] = 'https://graph.facebook.com/'.$FBalbum_cover;
				}
				if($type == 'hashtag'){
					  $fb_post_array[$post_data_key.'_photo'] = 'https://graph.facebook.com/'.$counter->source;
				}
			}
			
			//Response
			$response_post_array = $this->fts_get_feed_json($fb_post_array);
			
			//Make sure it's not ajaxing
			if(!$_GET['load_more_ajaxing']){		
						//Create Cache
						$this->fts_create_feed_cache($fb_post_data_cache, $response_post_array);
			}
		} //End else
		
	$set_zero = 0;
	
		//THE MAIN FEED
		foreach($data->data as $d) {
		if($set_zero==$fts_limiter)
		break;
		
		//		 echo'<pre>';
		//		  print_r($d);
		//		 echo'</pre>';
		
		
		//Create Facebook Variables 
		$FBfinalstory ='';
		$first_dir ='';
		$FBtype = $d->type;
		
		
		if (!$FBtype && $type == 'album_photos'){
			$FBtype = 'photo';
		}
		
		$FBpicture = $d->picture;
		$FBlink = $d->link;
		$FBname = $d->name;
		$FBcaption = $d->caption;
		$FBmessage = $d->message;	
		$FBdescription = $d->description;
		$FBstory = $d->story;
		$FBicon = $d->icon;
		$FBby = $d->properties->text;
		$FBbylink = $d->properties->href;
		$FBpost_id = $d->id;
		$FBpost_share_count = $d->shares->count;
		$FBpost_like_count_array = $d->likes->data;
		$FBpost_comments_count_array = $d->comments->data;
		$FBpost_object_id = $d->object_id;
		$FBalbum_photo_count = $d->count;
		$FBalbum_cover = $d->cover_photo;
			if ($type == 'albums' && !$FBalbum_cover) {
				unset($d);
				continue;
			}
		$FBpost_full_ID = explode('_', $FBpost_id);
		$FBpost_user_id = $FBpost_full_ID[0];
		$FBpost_single_id = $FBpost_full_ID[1];
		
		
		//Create Post Data Key
		if ($d->object_id){
			$post_data_key = $d->object_id;
			
		}
		else {
			$post_data_key = $d->id;
		}
		
		//Get Likes & Comments
		if($response_post_array){
				if($response_post_array[$post_data_key.'_likes']){
					$like_count_data  = json_decode($response_post_array[$post_data_key.'_likes']);
					
					//Like Count
					if (!empty($like_count_data->summary->total_count))	{	
						$FBpost_like_count = $like_count_data->summary->total_count;
					}
					else	{
						$FBpost_like_count = 0;
					}
					if ($FBpost_like_count == '0')	{
						$final_FBpost_like_count = "";
					}
					if ($FBpost_like_count == '1')	{
						$final_FBpost_like_count = "<i class='icon-thumbs-up'></i> 1";
					}
					
					if ($FBpost_like_count > '1')	{
						$final_FBpost_like_count = "<i class='icon-thumbs-up'></i> " . $FBpost_like_count;
					}
				}
				if($response_post_array[$post_data_key.'_comments']){
					$comment_count_data  = json_decode($response_post_array[$post_data_key.'_comments']);
					
					if (!empty($comment_count_data->summary->total_count))	{	
						$FBpost_comments_count = $comment_count_data->summary->total_count;	
					}
					else	{
						$FBpost_comments_count = 0;
					}
					
					if ($FBpost_comments_count == '0')	{
						$final_FBpost_comments_count = "";
					}
					if ($FBpost_comments_count == '1')	{
						$final_FBpost_comments_count = "<i class='icon-comments'></i> 1";
					}
					
					if ($FBpost_comments_count > '1')	{
						$final_FBpost_comments_count = "<i class='icon-comments'></i> " . $FBpost_comments_count;
					}
				}		
		}
		
				//Shares Count
				if ($FBpost_share_count == '0' or !$FBpost_share_count)	{
					$final_FBpost_share_count = "";
				}
				if ($FBpost_share_count == '1')	{
					$final_FBpost_share_count = "<i class='icon-file'></i> 1";
				}
				
				if ($FBpost_share_count > '1')	{
					$final_FBpost_share_count = "<i class='icon-file'></i> " . $FBpost_share_count;
				}

		
		$FBlocation = $d->location;
		$FBembed_vid = $d->embed_html;
		
		$FBfromName = $d->from->name;
		$FBstory = $d->story;
		
		
		 $CustomDateCheck = get_option('fts-date-and-time-format');
			  if($CustomDateCheck) {
				$CustomDateFormat = get_option('fts-date-and-time-format');
			  }
			  else {
				$CustomDateFormat = 'F jS, Y \a\t g:ia'; 
			  }
		
		$CustomTimeFormat = strtotime($d->created_time);
		
		if (!empty($FBstory)) {
			$FBfinalstory  = preg_replace('/'.$FBfromName.'/', '', $FBstory, 1);
		}

		switch($FBtype)	{
			case 'video'  :
		  print '<div class="fts-jal-single-fb-post fts-fb-video-post-wrap">';
		  	break;
			
			case 'app':
			case 'cover':
			case 'profile':
			case 'mobile':
			case 'wall':
			case 'normal':
			case 'photo':
			 print '<div class="fts-jal-single-fb-post  fts-fb-photo-post-wrap" ';
			if ($type == 'album_photos' || $type == 'albums') {
				print 'style="width:'.$image_width.'; height:'.$image_height.'; margin:'.$space_between_photos.'"';
			}
			print '>';
		  	break;
		  case 'album':
		  default:
		   print '<div class="fts-jal-single-fb-post">';
		  break;
		}
		
			  print '<div class="fts-jal-fb-user-thumb">';
			  print '<a href="http://facebook.com/profile.php?id='.$d->from->id.'"><img border="0" alt="'.$d->from->name.'" src="https://graph.facebook.com/'.$d->from->id.'/picture"/></a>'; 
			  print '</div>';
			  
			  print '<div class="fts-jal-fb-right-wrap">';
			  
		if($type == 'album_photos' && $hide_date_likes_comments == 'yes' || $type == 'albums' && $hide_date_likes_comments == 'yes'){ }
				else {
				
			  print '<div class="fts-jal-fb-top-wrap">';
			  print '<span class="fts-jal-fb-user-name" style=""><a href="http://facebook.com/profile.php?id='.$d->from->id.'">'.$d->from->name.'</a>'.$FBfinalstory.'</span>';
			  print '<span class="fts-jal-fb-post-time">'.date($CustomDateFormat, $CustomTimeFormat).'</span><div class="clear"></div>';
		
		//Comments Count
		$FBpost_id_final = substr($FBpost_id, strpos($FBpost_id, "_") + 1);
	
			//filter messages to have urls
			//Output Message  
			if ($FBmessage) {
				 
			   if(is_plugin_active('feed-them-premium/feed-them-premium.php'))  {
					  
					  // here we trim the words for the premium version. The $words string actually comes from the javascript	
						if ($words) {
					 		$trimmed_content = $this->fts_custom_trim_words($FBmessage, $words, $more);
							 print '<div class="fts-jal-fb-message">'.$trimmed_content.'</div><div class="clear"></div> ';
						}
						else {
							$FB_final_message = $this->fts_facebook_tag_filter($FBmessage);
							print '<div class="fts-jal-fb-message">'.nl2br($FB_final_message).'</div><div class="clear"></div> ';
						}
				} //END is_plugin_active
				
				// if the premium plugin is not active we will just show the regular full description
				else {
					$FB_final_message = $this->fts_facebook_tag_filter($FBmessage);
					print '<div class="fts-jal-fb-message">'.nl2br($FB_final_message).'</div><div class="clear"></div> ';
				}
			}//END Output Message 
			elseif (!$FBmessage && $type == 'album_photos' || !$FBmessage && $type == 'albums') {
			
			   print '<div class="fts-jal-fb-description-wrap">';
				  if ($FBname) {
					  print $this->fts_facebook_post_desc($FBname, $words, $FBtype, NULL,$FBby,$type);
				  };
				  //Output Photo Caption
				   if ($FBcaption) {
					 print $this->fts_facebook_post_cap($FBcaption, $words, $FBtype);
				  };
				  if ($FBalbum_photo_count) {
					 print $FBalbum_photo_count.' Photos';
				  };
				  if ($FBlocation) {
				   print $this->fts_facebook_location($FBtype, $FBlocation);
				  }
				  //Output Photo Description
				  if ($FBdescription) {
					  print $this->fts_facebook_post_desc($FBdescription, $words, $FBtype, NULL, $FBby);
				  };
			  print '<div class="clear"></div></div>';						  
			}
		
			  print '</div>'; // end .fts-jal-fb-top-wrap
			  
			  
		 }; //end if for show name date and comments	  
			  
			  
			  
			  
			  
			//Post Type Build 
			switch($FBtype)	{
				
				//START STATUS POST
				case 'status':
					if (!$FBpicture && !$FBname && !$FBdescription ) {
					 
						print '<div class="fts-jal-fb-link-wrap">';
						
							  //Output Link Picture
							  if ($FBpicture) {
								 print $this->fts_facebook_post_photo($FBlink, $FBtype, $d->from->name, $d->picture);
							  };
							  
						  if ($FBname || $FBcaption || $FBdescription)	{
							print '<div class="fts-jal-fb-description-wrap">';
							 //Output Link Name
							 if ($FBname) {
								print $this->fts_facebook_post_name($FBlink, $FBname, $FBtype);
							 };
							  //Output Link Caption
							  if ($FBcaption  == 'Attachment Unavailable. This attachment may have been removed or the person who shared it may not have permission to share it with you.' ) {
									print '<div class="fts-jal-fb-caption" style="width:100% !important">';
									_e('This user\'s permissions are keeping you from seeing this post. Please Click "View on Facebook" to view this post on this group\'s facebook wall.', 'feed-them-social');
									print '</div>';
							  }
							  else { 
								  print $this->fts_facebook_post_cap($FBcaption, $words, $FBtype);
							  };
							  //Output Link Description
							   if ($FBdescription) {
								 print $this->fts_facebook_post_desc($FBdescription, $words, $FBtype);
							  };
							print '<div class="clear"></div></div>';
						  }
						
						print '<div class="clear"></div></div>';
					  } 
				break;
				
				//START LINK POST
				case 'link':
					print '<div class="fts-jal-fb-link-wrap">';
					
						  //start url check
						  $url = $FBlink;
						  $url_parts = parse_url($url);
						  $host = $url_parts['host'];
						  
						  if ($host == 'www.facebook.com'){
							$spliturl= $url_parts['path'];
							$path_components = explode('/', $spliturl);
							$first_dir = $path_components[1];
							$event_id_number = $path_components[2];
						  }
						  //end url check
						  
						  if($host == 'www.facebook.com' and $first_dir == 'events')	{
							  $event_url = 'https://graph.facebook.com/'.$event_id_number.'/?access_token='.$access_token.'';
							  $event_data = json_decode(file_get_contents($event_url));
							  
							  $FB_event_name = $event_data->name;
							  $FB_event_location = $event_data->location;
							  $FB_event_city = $event_data->venue->city;
							  $FB_event_state = $event_data->venue->state;
							  $FB_event_start_time = date('l, F j, Y g:i a',strtotime($event_data->start_time));
							  
							  echo '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-picture"><img class="fts-fb-event-photo" src="http://graph.facebook.com/'.$event_id_number.'/picture"></img></a>';
							  
							  print '<div class="fts-jal-fb-description-wrap">';
								//Output Link Name
								if ($FB_event_name) {
									print $this->fts_facebook_post_name($FBlink, $FB_event_name, $FBtype);
								};
								//Output Link Caption
								if ($FB_event_start_time) {
									print '<div class="fts-fb-event-time">'.$FB_event_start_time.'</div>';
								};
								//Output Link Description
								if (!empty($FB_event_location)) {
									print '<div class="fts-fb-location">'.$FB_event_location;
									if ($FB_event_city or $FB_event_state) {
									  print ' in '.$FB_event_city.', '.$FB_event_state.'';
									}
									print '</div>';
								};
							  print '<div class="clear"></div></div>';
							  
						  }//end if event
						  
						  //Output Link Picture
						  if ($FBpicture) {
							print $this->fts_facebook_post_photo($FBlink, $FBtype, $d->from->name, $d->picture);
						  };
						  
						  print '<div class="fts-jal-fb-description-wrap">';
							//Output Link Name
							if ($FBname) {
							  print $this->fts_facebook_post_name($FBlink, $FBname, $FBtype);
							};
							//Output Link Caption
							if ($FBcaption) {
							  print $this->fts_facebook_post_cap($FBcaption, $words, $FBtype);
							};
							//Output Link Description
							if ($FBdescription) {
							  print $this->fts_facebook_post_desc($FBdescription, $words, $FBtype);
							};
						  print '<div class="clear"></div></div>';
					
					print '<div class="clear"></div></div>';
				break;
				
				//START VIDEO POST
				case 'video'  :
				
						$video_data = json_decode($response_post_array[$post_data_key.'_video']);
						
						//	echo'<pre>';
						//	print_r($video_data);
						//	echo'</pre>'; 
								
						//	echo'<pre>';
						//	print_r($d);
						//	echo'</pre>';
						
						
					print '<div class="fts-jal-fb-vid-wrap">';
						
						if (!empty($FBpicture)) {
								if((strpos($FBlink, 'facebook') > 0)){
									if(!empty($video_data->format)){
										foreach($video_data->format as $video_data_format){
											if($video_data_format->filter == 'native'){
												print '<div class="fts-fluid-videoWrapper-html5">';
												print '<video controls poster="'.$video_data_format->picture.'" width="100%;" style="max-width:100%;" >';
													print '<source src="'.$video_data->source.'" type="video/mp4">';
												print '</video>';
											  print '</div>';
											}
										}
										
										
										
										print '<div class="slicker-facebook-album-photoshadow"></div>';		
									}
								}
								else{	
							
							
							
							
							
							//Create Dynamic Class Name
							$fts_dynamic_vid_name_string = trim($this->rand_string(10).'_'.$type);
							$fts_dynamic_vid_name =  'feed_dynamic_video_class'.$fts_dynamic_vid_name_string;
	
							print '<div class="fts-jal-fb-vid-picture '.$fts_dynamic_class_name.' '.$fts_dynamic_vid_name.'"><img border="0" alt="' .$d->from->name.'" src="'.$d->picture.'"/> <div class="fts-jal-fb-vid-play-btn"></div></div>';
							  
							 
								//strip Youtube URL then ouput Iframe and script
								if (strpos($FBlink, 'youtube') > 0) {
									 $pattern = '#^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x';
									 preg_match($pattern, $FBlink, $matches);
									 $youtubeURLfinal = $matches[1];
							 
									print '<script>';
									print 'jQuery(document).ready(function() {';
									print 'jQuery(".'.$fts_dynamic_vid_name.'").click(function() {';
										print 'jQuery(this).addClass("fts-vid-div");';
										print 'jQuery(this).removeClass("fts-jal-fb-vid-picture");';
										print 'jQuery(this).prepend(\'<div class="fts-fluid-videoWrapper"><iframe height="281" class="video'.$FBpost_id.'" src="http://www.youtube.com/embed/'.$youtubeURLfinal.'?autoplay=1" frameborder="0" allowfullscreen></iframe></div>\');';
									print '});';		
									print '});';	
									print '</script>';	
								}
								//strip Youtube URL then ouput Iframe and script
								else if (strpos($FBlink, 'youtu.be') > 0) {
									$pattern = '#^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x';
									 preg_match($pattern, $FBlink, $matches);
									 $youtubeURLfinal = $matches[1];
									
									print '<script>';
									print 'jQuery(document).ready(function() {';
									print 'jQuery(".'.$fts_dynamic_vid_name.'").click(function() {';
										print 'jQuery(this).addClass("fts-vid-div");';
										print 'jQuery(this).removeClass("fts-jal-fb-vid-picture");';
										print 'jQuery(this).prepend(\'<div class="fts-fluid-videoWrapper"><iframe height="281" class="video'.$FBpost_id.'" src="http://www.youtube.com/embed/'.$youtubeURLfinal.'?autoplay=1" frameborder="0" allowfullscreen></iframe></div>\');';
									print '});';		
									print '});';	
									print '</script>';
								}
								
								//strip Vimeo URL then ouput Iframe and script
								else if (strpos($FBlink, 'vimeo') > 0) {
									
									$pattern = '/(\d+)/';
									 preg_match($pattern, $FBlink, $matches);
									 $vimeoURLfinal = $matches[0];
									
									print '<script>';
									print 'jQuery(document).ready(function() {';
									print 'jQuery(".'.$fts_dynamic_vid_name.'").click(function() {';
										print 'jQuery(this).addClass("fts-vid-div");';
										print 'jQuery(this).removeClass("fts-jal-fb-vid-picture");';
										print 'jQuery(this).prepend(\'<div class="fts-fluid-videoWrapper"><iframe src="http://player.vimeo.com/video/'.$vimeoURLfinal.'?autoplay=1" class="video'.$FBpost_id.'" height="390" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>\');';
									print '});';		
									print '});';	
									print '</script>';
								}
								
								
								else if (strpos($FBlink, 'soundcloud') > 0) {
									//Get the SoundCloud URL
									$url = $FBlink;
									//Get the JSON data of song details with embed code from SoundCloud oEmbed
									$getValues=file_get_contents('http://soundcloud.com/oembed?format=js&url='.$url.'&auto_play=true&iframe=true');
									//Clean the Json to decode
									$decodeiFrame=substr($getValues, 1, -2);
									//json decode to convert it as an array
									$jsonObj = json_decode($decodeiFrame);
									//Change the height of the embed player if you want else uncomment below line
									// echo str_replace('height="400"', 'height="140"', $jsonObj->html);
									print '<script>';
									print 'jQuery(document).ready(function() {';
									print 'jQuery(".'.$fts_dynamic_vid_name.'").click(function() {';
									print 'jQuery(this).addClass("fts-vid-div");';
									print 'jQuery(this).removeClass("fts-jal-fb-vid-picture");';
									print '	jQuery(this).prepend(\'<div class="fts-fluid-videoWrapper">'.$jsonObj->html.'</div>\');';
									print '});';		
									print '});';	
									print '</script>';
									} 
								}
							}
							if ($FBname || $FBcaption || $FBdescription){
								print '<div class="fts-jal-fb-description-wrap fb-id'.$FBpost_id.'">';
								  //Output Video Name
								  if ($FBname) {
									  print $this->fts_facebook_post_name($FBlink, $FBname, $FBtype, $FBpost_id);
								  };
								  //Output Video Caption
								  if ($FBcaption) {
									  print $this->fts_facebook_post_cap($FBcaption, $words, $FBtype, $FBpost_id);
								  };
								  //Output Video Description
								  if ($FBdescription) {
									  print $this->fts_facebook_post_desc($FBdescription, $words, $FBtype, $FBpost_id);
								  };
								print '<div class="clear"></div></div>';
						    }
						
						print '<div class="clear"></div></div>';	
					break;
					  
				//START PHOTO POST
				case 'photo'  :
				
					print '<div class="fts-jal-fb-link-wrap fts-album-photos-wrap"';
			if ($type == 'album_photos' || $type == 'albums') {
				print 'style="line-height:'.$image_height.' !important;"';
			}
			print '>';
					  
					  //Output Photo Picture
					  if (!$FBname && !$FBdescription && $FBpicture)	{
						  if ($FBpost_object_id)	{
							 print '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-picture fts-fb-large-photo"><img border="0" alt="' .$d->from->name.'" src="https://graph.facebook.com/'.$FBpost_object_id.'/picture"/></a>';
						  }
						  else{
						  	 print '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-picture fts-fb-large-photo"><img border="0" alt="' .$d->from->name.'" src="https://graph.facebook.com/'.$FBpost_id.'/picture"/></a>';
						  }
					  }	
					  elseif ($FBpicture) {
						  if ($FBpost_object_id)	{
							 print $this->fts_facebook_post_photo($FBlink, $type, $d->from->name, 'https://graph.facebook.com/'.$FBpost_object_id.'/picture', $image_position_lr, $image_position_top);
						  }
						  else{
							 print $this->fts_facebook_post_photo($FBlink, $type, $d->from->name, 'https://graph.facebook.com/'.$FBpost_id.'/picture', $image_position_lr, $image_position_top);
						  }
						  
					  };
					  print '<div class="slicker-facebook-album-photoshadow"></div>';
					  if(!$type == 'album_photos') {	
						  print '<div class="fts-jal-fb-description-wrap" style="display:none">';
							//Output Photo Name
							if ($FBname) {
								print $this->fts_facebook_post_name($FBlink, $FBname, $FBtype);
							};
							//Output Photo Caption
							 if ($FBcaption) {
							   print $this->fts_facebook_post_cap($FBcaption, $words, $FBtype);
							};
							//Output Photo Description
							if ($FBdescription) {
								print $this->fts_facebook_post_desc($FBdescription, $words, $FBtype, NULL,$FBby);
							};
						  print '<div class="clear"></div></div>';
					   }
					  
					
					print '<div class="clear"></div></div>';
				 break;
				 
				 //START ALBUM POST
				case 'app':
				case 'cover':
				case 'profile':
				case 'mobile':
				case 'wall':
				case 'normal':
				case 'album':
				
				
					print '<div class="fts-jal-fb-link-wrap fts-album-photos-wrap"';
			if ($type == 'album_photos' || $type == 'albums') {
				print 'style="line-height:'.$image_height.' !important;"';
			}
			print '>';
					  
					  //Output Photo Picture
					  if ($FBalbum_cover) {
						 $photo_data = json_decode($response_post_array[$FBalbum_cover.'_photo']);
						 
						  print $this->fts_facebook_post_photo($FBlink, $type, $d->from->name, $photo_data->images[0]->source, $image_position_lr, $image_position_top);
					  };
					  
					  print '<div class="slicker-facebook-album-photoshadow"></div>';
					  if(!$type == 'albums') {	
						  print '<div class="fts-jal-fb-description-wrap">';
						//Output Photo Name
						if ($FBname) {
							print $this->fts_facebook_post_name($FBlink, $FBname, $FBtype);
						};
						//Output Photo Caption
						if ($FBcaption) {
						   print $this->fts_facebook_post_cap($FBcaption, $words, $FBtype);
						};
						
						//Output Photo Description
						if ($FBdescription) {
							print $this->fts_facebook_post_desc($FBdescription, $words, $FBtype, NULL,$FBby);
						};
					  print '<div class="clear"></div></div>';
					
					}
					
					print '<div class="clear"></div></div>';
				 break;		
			}
		
		print '<div class="clear"></div>'; 
		print '</div>';
			
			
		
		print $this->fts_facebook_post_see_more($FBlink, $final_FBpost_like_count, $final_FBpost_comments_count, $final_FBpost_share_count, $FBtype, $FBpost_id, $type, $hide_date_likes_comments, $FBpost_user_id, $FBpost_single_id);
		
			
		print '<div class="clear"></div>'; 
		print '</div>';
		
			 $set_zero++;
			 }	
		
		
		
		$build_shortcode .= '[fts facebook';
	
		  foreach($atts as $attribute => $value){
			  $build_shortcode .= ' '.$attribute.'='.$value;
		  }
		
		$build_shortcode .= ']';
	
		$_REQUEST['next_url'] = $data->paging->next;
		?>
<script>
jQuery(document).ready(function() {	
	//Video Clickable
	jQuery("video").click(function() {
		if (!this.paused) {
		  jQuery(this).trigger("pause");
		} 
		else if (this.paused) {
		  jQuery(this).trigger("play");
		} 
		else{
		  jQuery(this).trigger("play");
		}
	  });

});
	var nextURL_<?php echo $_REQUEST['fts_dynamic_name']; ?>= "<?php echo $_REQUEST['next_url']; ?>";
</script>
<?php	
//Make sure it's not ajaxing
if(!$_GET['load_more_ajaxing'] && !$_REQUEST['fts_no_more_posts'] && !empty($loadmore)){ 
	
	$fts_dynamic_name = $_REQUEST['fts_dynamic_name'];
	
?>
<script>
	 jQuery(document).ready(function() {
		  <?php 
		  // $scrollMore = load_more_posts_style shortcode att
		  if($scrollMore == 'autoscroll') { ?>
			
			// this is where we do SCROLL function to LOADMORE if = autoscroll in shortcode
			jQuery(".<?php echo $fts_dynamic_class_name ?>").bind("scroll",function() {
				 
   				 if(jQuery(this).scrollTop() + jQuery(this).innerHeight() >= jQuery(this)[0].scrollHeight) {
					 
		 <?php }
		 	else { ?>
				// this is where we do CLICK function to LOADMORE if  = button in shortcode
				jQuery("#loadMore_<?php echo $fts_dynamic_name ?>").click(function() {
					
			<?php } ?>
					jQuery("#loadMore_<?php echo $fts_dynamic_name ?>").addClass('fts-fb-spinner');
					
						var button = jQuery('#loadMore_<?php echo $fts_dynamic_name ?>').html('<div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div>');
						console.log(button);
						var build_shortcode = "<? echo $build_shortcode;?>";
						var yes_ajax = "yes";
						var fts_d_name = "<? echo $fts_dynamic_name;?>";
					
					jQuery.ajax({
						data: {action: "my_fts_fb_load_more", next_url: nextURL_<?php echo $fts_dynamic_name ?>, fts_dynamic_name: fts_d_name, rebuilt_shortcode: build_shortcode, load_more_ajaxing: yes_ajax},
						type: 'GET',
						url: myAjax.ajaxurl,
						success: function( data ) { 
							console.log('Well Done and got this from sever: ' + data);
						
				 <?php if ($FBtype && $type == 'albums' || $FBtype && $type == 'album_photos') {  ?>
					 	jQuery('.<?php echo $fts_dynamic_class_name ?>').append(data).filter('.<?php echo $fts_dynamic_class_name ?>').html(); 
						jQuery('.<?php echo $fts_dynamic_class_name ?>').masonry( 'reloadItems' );
						jQuery('.<?php echo $fts_dynamic_class_name ?>').masonry( 'layout' );
						
						if(!nextURL_<?php echo $_REQUEST['fts_dynamic_name']; ?>){
						  jQuery('#loadMore_<?php echo $fts_dynamic_name ?>').replaceWith('<div class="fts-fb-load-more no-more-posts-fts-fb">No More Photos</div>');
						  jQuery('#loadMore_<?php echo $fts_dynamic_name ?>').removeAttr('id');
						  jQuery(".<?php echo $fts_dynamic_class_name ?>").unbind('scroll');
						}
					<?php }
					else { ?>
						var result = jQuery('#output_<?php echo $fts_dynamic_name ?>').append(data).filter('#output_<?php echo $fts_dynamic_name ?>').html();
						jQuery('#output_<?php echo $fts_dynamic_name ?>').html(result);
						
						if(!nextURL_<?php echo $_REQUEST['fts_dynamic_name']; ?>){
						  jQuery('#loadMore_<?php echo $fts_dynamic_name ?>').replaceWith('<div class="fts-fb-load-more no-more-posts-fts-fb">No More Posts</div>');
						  jQuery('#loadMore_<?php echo $fts_dynamic_name ?>').removeAttr('id');
						  jQuery(".<?php echo $fts_dynamic_class_name ?>").unbind('scroll');
						}
					<?php } ?>
						
					 jQuery('#loadMore_<?php echo $fts_dynamic_name ?>').html('Load More');
					  //	jQuery('#loadMore_< ?php echo $fts_dynamic_name ?>').removeClass('flip360-fts-load-more');
					 jQuery("#loadMore_<?php echo $fts_dynamic_name ?>").removeClass('fts-fb-spinner');
							
						}
					}); // end of ajax()
					return false;
					
					<?php // string $scrollMore is at top of this js script. acception for scroll option closing tag
					if($scrollMore == 'autoscroll' ) { ?>
								} // end of scroll ajax load. 
					 <?php } ?>	   
		  }); // end of document.ready
	
	  }); // end of form.submit
</script> 
<?php
 }//End Check	 

			// main closing div not included in ajax check so we can close the wrap at all times
			//Make sure it's not ajaxing
			if(!$_GET['load_more_ajaxing']){
				// this div returns outputs our ajax request via jquery appenc html from above
				print '<div id="output_'.$fts_dynamic_name.'"></div>';
				
				if(is_plugin_active('feed-them-premium/feed-them-premium.php') && $scrollMore == 'autoscroll') {
							 print '<div id="loadMore_'.$fts_dynamic_name.'" class="fts-fb-load-more fts-fb-autoscroll-loader">Facebook</div>';
						}  
			}	
				
		 print '</div>'; // closing main div for fb photos, groups etc
		
		 ?> 
		 <?php //only show this script if the height option is set to a number 
											if($height !== 'auto' && empty($height) == NULL) { ?> 
										 <script>
											// this makes it so the page does not scroll if you reach the end of scroll bar or go back to top
											jQuery.fn.isolatedScrollFacebookFTS = function() {
													this.bind('mousewheel DOMMouseScroll', function (e) {
													var delta = e.wheelDelta || (e.originalEvent && e.originalEvent.wheelDelta) || -e.detail,
														bottomOverflow = this.scrollTop + jQuery(this).outerHeight() - this.scrollHeight >= 0,
														topOverflow = this.scrollTop <= 0;
											
													if ((delta < 0 && bottomOverflow) || (delta > 0 && topOverflow)) {
														e.preventDefault();
													}
												});
												return this;
											};
											jQuery('.fts-fb-scrollable').isolatedScrollFacebookFTS();
										 </script>	
									   <?php } //end $height !== 'auto' && empty($height) == NULL ?>
		 
		 
			

			<?php
			//Make sure it's not ajaxing
			if(!$_GET['load_more_ajaxing']){
					  print '<div class="clear"></div><div id="fb-root"></div>';
					  
						if(is_plugin_active('feed-them-premium/feed-them-premium.php') && $scrollMore == 'button') {
							 print '<div id="loadMore_'.$fts_dynamic_name.'" class="fts-fb-load-more">Load More</div>';
						}
						
						
			}//End Check	
		  unset($_REQUEST['next_url']);
		  return ob_get_clean();   
	}
	
	//Facebook Post Location
	function fts_facebook_location($FBtype = NULL, $location) {
		switch($FBtype)	{
		  case 'app':
		  case 'cover':
		  case 'profile':
		  case 'mobile':
		  case 'wall':
		  case 'normal':
		  case 'album':
			  $output .= '<div class="fts-fb-location">'.$location.'</div>';
				  return $output;
		
		}
	}
	
	//Facebook Post Photo
	function fts_facebook_post_photo($FBlink, $type, $photo_from, $photo_source, $image_position_lr = NULL, $image_position_top = NULL) {
		 if($type == 'album_photos' || $type == 'albums') {	
			  $output .=  '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-picture album-photo-fts"';
			   if($image_position_lr !== '-0%' || $image_position_top !== '-0%') {	
				  	$output .= 'style="right:'.$image_position_lr.';left:'.$image_position_lr.';top:'.$image_position_top.'"';
				   }
				  $output .= '><img border="0" alt="' .$photo_from.'" src="'.$photo_source.'"/></a>';
			  
		 }
		 else {
			 $output .=  '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-picture"><img border="0" alt="' .$photo_from.'" src="'.$photo_source.'"/></a>';
		 }
			  return $output;
	}
	
	//Facebook Post Name
	function fts_facebook_post_name($FBlink, $FBname, $FBtype, $FBpost_id = NULL) {
		switch($FBtype)	{	
			case 'video':
			$FBname = $this->fts_facebook_tag_filter($FBname);
			$output .= '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-name fb-id'.$FBpost_id.'">'.$FBname.'</a>';
				return $output;
					
			default:
			$FBname = $this->fts_facebook_tag_filter($FBname);
			$output .= '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-name">'.$FBname.'</a>';
				return $output;	  
		}
	}
	
	//Facebook Post Description
	function fts_facebook_post_desc($FBdescription, $words, $FBtype, $FBpost_id = NULL,$FBby = NULL, $type = NULL) {
		
		switch($FBtype)	{	
			case 'video':
			$FBdescription = $this->fts_facebook_tag_filter($FBdescription);
			$output .= '<div class="fts-jal-fb-description fb-id'.$FBpost_id.'">'.$FBdescription.'</div>';
				return $output;
			    
				
			case 'photo':
			   if($type == 'album_photos'){
				 if ($words) {
					 $trimmed_content = $this->fts_custom_trim_words($FBdescription, $words, $more);
					  	$output .= '<div class="fts-jal-fb-description">'.$trimmed_content.'</div>';
						return $output;
					}
					else {
						$FBdescription = $this->fts_facebook_tag_filter($FBdescription);
						$output .= '<div class="fts-jal-fb-description">'.nl2br($FBdescription).'</div>';
					    return $output;  
					}
			   }
			   
			   case 'albums':
			   if($type == 'albums'){
				   
				   if ($words) {
					 $trimmed_content = $this->fts_custom_trim_words($FBdescription, $words, $more);
					 $output .= '<div class="fts-jal-fb-description">'.$trimmed_content.'</div>';
						return $output;
					}
					else {
						$FBdescription = $this->fts_facebook_tag_filter($FBdescription);
						$output .= '<div class="fts-jal-fb-description">'.nl2br($FBdescription).'</div>';
					    return $output;  
					}
			   }
			   
			   //Do for Default feeds
			   else{
				   $FBdescription = $this->fts_facebook_tag_filter($FBdescription);
			    	$output .= '<div class="fts-jal-fb-description">'.nl2br($FBdescription).'</div>';
					$output .= '<div>By: <a href="'.$FBlink.'">'.$FBby.'<a/></div>';
			   		return $output;
			   }
			   	
			default:
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if(is_plugin_active('feed-them-premium/feed-them-premium.php'))  {
			   // here we trim the words for the links description text... for the premium version. The $words string actually comes from the javascript
			   
			   if ($words) {
					 $trimmed_content = $this->fts_custom_trim_words($FBdescription, $words, $more);
					 $output .= '<div class="jal-fb-description">'.$trimmed_content.'</div>';
					 return $output;
				}
				else {
				   $FBdescription = $this->fts_facebook_tag_filter($FBdescription);
				   $output .= '<div class="jal-fb-description">'.nl2br($FBdescription).'</div>';
				   return $output;
				   
				}
			} //END is_plugin_active
		
			// if the premium plugin is not active we will just show the regular full description
			else {
				 $FBdescription = $this->fts_facebook_tag_filter($FBdescription);
				 $output .= '<div class="jal-fb-description">'.nl2br($FBdescription).'</div>';
				 return $output;
				 
			}
		}
	
	}
	
	//Facebook Post Caption
	function fts_facebook_post_cap($FBcaption, $words, $FBtype, $FBpost_id = NULL) {
		switch($FBtype)	{	
			case 'video':
			$FBcaption = $this->fts_facebook_tag_filter($FBcaption);
			$output .= '<div class="fts-jal-fb-caption fb-id'.$FBpost_id.'">'.$FBcaption.'</div>';
				return $output;
			    
						
			default:
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if(is_plugin_active('feed-them-premium/feed-them-premium.php'))  {
				   // here we trim the words for the links description text... for the premium version. The $words string actually comes from the javascript	
				   if ($words) {
					 $trimmed_content = $this->fts_custom_trim_words($FBcaption, $words, $more);
					 $output .= '<div class="jal-fb-caption">'.$trimmed_content.'</div>';
					}
					else {
					   $FBcaption = $this->fts_facebook_tag_filter($FBcaption);
					   $output .= '<div class="jal-fb-caption">'.nl2br($FBcaption).'</div>';
					}
			} //END is_plugin_active
			
			// if the premium plugin is not active we will just show the regular full description
			else {
					$FBcaption = $this->fts_facebook_tag_filter($FBcaption);
					$output .= '<div class="jal-fb-caption">'.nl2br($FBcaption).'</div>';
			}
			 return $output;
			 
		}
	}
	
	function fts_facebook_post_see_more($FBlink, $final_FBpost_like_count, $final_FBpost_comments_count, $final_FBpost_share_count, $FBtype, $FBpost_id = NULL, $type, $hide_date_likes_comments, $FBpost_user_id = NULL, $FBpost_single_id = NULL) {
		
		switch($FBtype)	{
		
		  case 'photo':
		 	    $output .= '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-see-more">';
		  		
				if($type == 'album_photos' && $hide_date_likes_comments == 'yes'){ }
				else {
					$output .= ''.$final_FBpost_like_count.' '.$final_FBpost_comments_count.' '.$final_FBpost_share_count.' &nbsp;&nbsp;'; 
				}
				$output .='&nbsp;View on Facebook</a>';
				return $output;
			
		  case 'app':
		  case 'cover':
		  case 'profile':
		  case 'mobile':
		  case 'wall':
		  case 'normal':
		  case 'album':
		  		$output .= '<a href="'.$FBlink.'" target="_blank" class="fts-jal-fb-see-more">';
		  		 
				if($type = 'albums' && $hide_date_likes_comments == 'yes'){ }
				else {
					$output .= ''.$final_FBpost_like_count.' '.$final_FBpost_comments_count.' &nbsp;&nbsp;'; 
				}
				$output .='&nbsp;View on Facebook</a>';
				return $output;
			   
			default:
				$output .= '<a href="http://facebook.com/'.$FBpost_user_id.'/posts/'.$FBpost_single_id.'" target="_blank" class="fts-jal-fb-see-more">';
				$output .= ''.$final_FBpost_like_count.' '.$final_FBpost_comments_count.' &nbsp;&nbsp;&nbsp;View on Facebook</a>'; 
				return $output;
				 
			   
		}
	}
	
	
	function fts_custom_trim_words( $text, $num_words = 45, $more) {
			$more = __( '...' );
	 
		$text = nl2br($text);
		//Filter for Hashtags and Mentions Before returning. 
		$text= $this->fts_facebook_tag_filter($text);
		$text = strip_shortcodes($text);
		// Add tags that you don't want stripped
		$text = strip_tags( $text, '<strong><br><em><i><a>' );
	 
			$words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
			$sep = ' ';
	 
		if ( count( $words_array ) > $num_words ) {
			array_pop( $words_array );
			$text = implode( $sep, $words_array );
			$text = $text . $more;
		} else {
			$text = implode( $sep, $words_array );
		}
		
		
		
		return $text;
	}
	
	function fts_facebook_tag_filter($FBdescription){
		//Converts URLs to Links
		$FBdescription = preg_replace('@(?!(?!.*?<a)[^<]*<\/a>)(?:(?:https?|ftp|file)://|www\.|ftp\.)[-A-‌​Z0-9+&#/%=~_|$?!:,.]*[A-Z0-9+&#/%=~_|$]@i', '<a href="\0" target="_blank">\0</a>', $FBdescription);
		
		//	Mentions
		//	$FBdescription = preg_replace('/(?<!\S)@([0-9a-zA-Z]+)/', '<a target="_blank" href="http://facebook.com/$1">@$1</a>', $FBdescription);
		
		//Hash tags
		$FBdescription = preg_replace('/(?<!\S)#([0-9a-zA-Z]+)/', '<a target="_blank" href="http://facebook.com/hashtag/$1">#$1</a>', $FBdescription);

		return $FBdescription;
	}

	
	function rand_string( $length ) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";	

		$size = strlen( $chars );
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[ rand( 0, $size - 1 ) ];
		}

		return $str;
    }

}// FTS_Facebook_Feed END CLASS
?>