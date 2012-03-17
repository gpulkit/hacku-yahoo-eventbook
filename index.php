<?php
// Provides access to app specific values such as your app id and app secret.
// Defined in 'AppInfo.php'
require_once('AppInfo.php');
// Enforce https on production
if (substr(AppInfo::getUrl(), 0, 8) != 'https://' && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') 
{
	header('Location: https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
	exit();
}

// This provides access to helper functions defined in 'utils.php'
require_once('utils.php');


/*****************************************************************************
 *
 * The content below provides examples of how to fetch Facebook data using the
 * Graph API and FQL.  It uses the helper functions defined in 'utils.php' to
 * do so.  You should change this section so that it prepares all of the
 * information that you want to display to the user.
 *
 ****************************************************************************/

require_once('sdk/src/facebook.php');

$facebook = new Facebook(array(
  'appId'  => AppInfo::appID(),
  'secret' => AppInfo::appSecret(),
));

$user_id = $facebook->getUser();
if ($user_id) {
  try {
    // Fetch the viewer's basic information
    $basic = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    // If the call fails we check if we still have a user. The user will be
    // cleared if the error is because of an invalid accesstoken
    if (!$facebook->getUser()) {
      header('Location: '. AppInfo::getUrl($_SERVER['REQUEST_URI']));
      exit();
    }
  }

  // This fetches some things that you like . 'limit=*" only returns * values.
  // To see the format of the data you are retrieving, use the "Graph API
  // Explorer" which is at https://developers.facebook.com/tools/explorer/
  $likes = idx($facebook->api('/me/likes?limit=4'), 'data', array());

  // This fetches 4 of your friends.
  $friends = idx($facebook->api('/me/friends?limit=4'), 'data', array());

  // And this returns 16 of your photos.
  $photos = idx($facebook->api('/me/photos?limit=16'), 'data', array());

  // Here is an example of a FQL call that fetches all of your friends that are
  // using this app
  $app_using_friends = $facebook->api(array(
    'method' => 'fql.query',
    'query' => 'SELECT uid, name FROM user WHERE uid IN(SELECT uid2 FROM friend WHERE uid1 = me()) AND is_app_user = 1'
  ));

/*
  $events = $facebook->api(array('method' => 'fql.query',
				 'query'  => 'SELECT pic_big, name, description, start_time, end_time, location, venue, eid FROM event WHERE eid IN (SELECT eid FROM event_member WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me()) OR uid = me())'));//  < \'88\''));//   \''.($long+$offset) .'\''));

*/


$events = $facebook->api(array('method' => 'fql.query',
				 'query'  => 'SELECT pic_big, name, description, start_time, end_time, location, venue, eid FROM event WHERE eid IN (SELECT eid FROM event_member WHERE uid = me())'));//  < \'88\''));//   \''.($long+$offset) .'\''));



// AND venue.longitude < \''. ($long+$offset) .'\' AND venue.latitude < \''. ($lat+$offset) .'\' AND venue.longitude > \''. ($long-$offset) .'\' AND venue.latitude > \''. ($lat-$offset) .'\' ORDER BY start_time ASC '));

/*
  $events = $facebook->api(array('method' => 'fql.query',
				 'query'  => 'SELECT pic_big, name, venue, location, start_time, eid FROM event WHERE eid IN (SELECT eid FROM event_member WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me()) OR uid = me()) AND venue.longitude < \''. ($long+$offset) .'\' AND venue.latitude < \''. ($lat+$offset) .'\' AND venue.longitude > \''. ($long-$offset) .'\' AND venue.latitude > \''. ($lat-$offset) .'\' ORDER BY start_time ASC '));
*/

}

// Fetch the basic info of the app that they are using
$app_info = $facebook->api('/'. AppInfo::appID());

$app_name = idx($app_info, 'name', '');

?>
<!DOCTYPE html>
<html xmlns:fb="http://ogp.me/ns/fb#" lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0, user-scalable=yes" />

    <title><?php echo he($app_name); ?></title>
    <link rel="stylesheet" href="stylesheets/screen.css" media="Screen" type="text/css" />
    <link rel="stylesheet" href="stylesheets/mobile.css" media="handheld, only screen and (max-width: 480px), only screen and (max-device-width: 480px)" type="text/css" />

    <!--[if IEMobile]>
    <link rel="stylesheet" href="mobile.css" media="screen" type="text/css"  />
    <![endif]-->

    <!-- These are Open Graph tags.  They add meta data to your  -->
    <!-- site that facebook uses when your content is shared     -->
    <!-- over facebook.  You should fill these tags in with      -->
    <!-- your data.  To learn more about Open Graph, visit       -->
    <!-- 'https://developers.facebook.com/docs/opengraph/'       -->
    <meta property="og:title" content="<?php echo he($app_name); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?php echo AppInfo::getUrl(); ?>" />
    <meta property="og:image" content="<?php echo AppInfo::getUrl('/logo.png'); ?>" />
    <meta property="og:site_name" content="<?php echo he($app_name); ?>" />
    <meta property="og:description" content="My first app" />
    <meta property="fb:app_id" content="<?php echo AppInfo::appID(); ?>" />

    <script type="text/javascript" src="/javascript/jquery-1.7.1.min.js"></script>

    <script type="text/javascript">
      function logResponse(response) {
        if (console && console.log) {
          console.log('The response was', response);
        }
      }
	


      $(function(){
        // Set up so we handle click on the buttons
        $('#postToWall').click(function() {
          FB.ui(
            {
              method : 'feed',
              link   : $(this).attr('data-url')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendToFriends').click(function() {
          FB.ui(
            {
              method : 'send',
              link   : $(this).attr('data-url')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });

        $('#sendRequest').click(function() {
          FB.ui(
            {
              method  : 'apprequests',
              message : $(this).attr('data-message')
            },
            function (response) {
              // If response is null the user canceled the dialog
              if (response != null) {
                logResponse(response);
              }
            }
          );
        });
      });
    </script>

 <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style type="text/css">
      html { height: 200px }
      body { height: 200px; margin: 0; padding: 0 }
      #map_canvas { height: 100% }
    </style>
    <script type="text/javascript"
      src="http://maps.googleapis.com/maps/api/js?key=AIzaSyA_XI29EdGJkjoZB9Q8Igxbtu9rQyX14ek&sensor=false">
    </script>
    <script type="text/javascript">
	
	var map;	
	var markersArray = [];
     function initialize() {
        var myOptions = {
          center: new google.maps.LatLng(42.0, -83.0),
          zoom: 8,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
	
            map = new google.maps.Map(document.getElementById("map_canvas"),myOptions); 
      }


function addMarker(loc, ev_name) {
  	marker = new google.maps.Marker({position:loc,map:map});
	var contentString = ev_name;
      	var infowindow = new google.maps.InfoWindow({content: contentString});
	google.maps.event.addListener(marker, 'click', function() {infowindow.open(map,marker);});
	markersArray.push(marker);
}

function showOverlays() {
  if (markersArray) {
    for (i in markersArray) {
      //markersArray[i].setMap(map);
       google.maps.event.addListener(markersArray[i], 'click', toggleBounce());
    }
  }
}


function toggleBounce() {

  if (marker.getAnimation() != null) {
    marker.setAnimation(null);
  } else {
    marker.setAnimation(google.maps.Animation.BOUNCE);
  }
}



// Determine support for Geolocation
if (navigator.geolocation) {
    // Locate position
    navigator.geolocation.getCurrentPosition(displayPosition, errorFunction);
} else {
    alert('It seems like Geolocation, which is required for this page, is not enabled in your browser. Please use a browser which supports it.');
}

// Success callback function
var mylat = pos.coords.latitude;
var mylong = pos.coords.longitude;

// Error callback function
function errorFunction(pos) {
    alert('Error!');
}
</script>







    </script>
  </head>

  <body onload="">
  	

	<?php 
	//	$ip = $_SERVER['REMOTE_ADDR'];
		//$my_location = file_get_contents('http://api.ipinfodb.com/v3/ip-city/?key=e83cefbb1a1a08c7b2151f44c4464cab0ae503ee1935102f4160a5c753902432&ip='.$ip);
	//	echo $ip;
	 ?> 
  <div id="map_canvas" style="width:100%; height:100%"></div>
  <script type="text/javascript">
  initialize();
  </script>





  <div>
        <h3>List of events
        <script type="text/javascript">
	lat = 42; longi=-83;
	loc = new google.maps.LatLng(lat,longi);
	addMarker(loc,"Current Location");
	google.maps.event.addListener(marker, 'click', toggleBounce());
	</script>
          <?php
            foreach ($events as $fid) {
		$latitude = "42";
		$longitude = "-83";
		$offset = 1.0;

		$venue = idx($fid, 'venue');
		if($id = idx($venue, 'id')){
			$url = 'http://graph.facebook.com/'.$id;
			if($json = file_get_contents($url)){
				$data = json_decode($json);
				$location = $data->{'location'};	
				$long = $location->{'longitude'};
				$lat = $location->{'latitude'};
			}
		}else{

			$long = idx($venue, 'longitude');
			$lat = idx($venue, 'latitude');
                }

		$name = idx($fid, 'name');
		if(isset($long) and isset($lat) and ($long < ($longitude+$offset)) and ($long > ($longitude-$offset)) and ($lat < ($latitude+$offset)) and ($lat > ($latitude-$offset))) {
	      	
		
			echo '<script type="text/javascript">
				loc = new google.maps.LatLng('.$lat.','.$long.');
			      addMarker(loc,"'.$name.'");
			    
				</script>';
				
			echo he($name);
			echo "\n";
		}
            }	 
	 ?>
	</h3>
   </div>


  <script type="text/javascript">
  	//showOverlays();
  </script>


    <div id="fb-root"></div>
    <script type="text/javascript">
      window.fbAsyncInit = function() {
        FB.init({
          appId      : '<?php echo AppInfo::appID(); ?>', // App ID
          channelUrl : '//<?php echo $_SERVER["HTTP_HOST"]; ?>/channel.html', // Channel File
          status     : true, // check login status
          cookie     : true, // enable cookies to allow the server to access the session
          xfbml      : true // parse XFBML
        });

        // Listen to the auth.login which will be called when the user logs in
        // using the Login button
        FB.Event.subscribe('auth.login', function(response) {
          // We want to reload the page now so PHP can read the cookie that the
          // Javascript SDK sat. But we don't want to use
          // window.location.reload() because if this is in a canvas there was a
          // post made to this page and a reload will trigger a message to the
          // user asking if they want to send data again.
          window.location = window.location;
        });

        FB.Canvas.setAutoGrow();
      };

      // Load the SDK Asynchronously
      (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = "//connect.facebook.net/en_US/all.js";
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));
    </script>

    <header class="clearfix">
      <?php if (isset($basic)) { ?>
      <p id="picture" style="background-image: url(https://graph.facebook.com/<?php echo he($user_id); ?>/picture?type=normal)"></p>

      <div>
        <h1>Welcome man, <strong><?php echo he(idx($basic, 'name')); ?></strong></h1>
        <p class="tagline">
          This is your app
          <a href="<?php echo he(idx($app_info, 'link'));?>" target="_top"><?php echo he($app_name); ?></a>
        </p>

        <div id="share-app">
          <p>Share your app:</p>
          <ul>
            <li>
              <a href="#" class="facebook-button" id="postToWall" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="plus">Post to Wall</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button speech-bubble" id="sendToFriends" data-url="<?php echo AppInfo::getUrl(); ?>">
                <span class="speech-bubble">Send Message</span>
              </a>
            </li>
            <li>
              <a href="#" class="facebook-button apprequests" id="sendRequest" data-message="Test this awesome app">
                <span class="apprequests">Send Requests</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
      <?php } else { ?>
      <div>
        <h1>Welcome</h1>
        <div class="fb-login-button" data-scope="user_likes,user_photos,user_events,friends_events"></div>
      </div>
      <?php } ?>
    </header>

    <?php
      if ($user_id) {
    ?>

   </section>

    <?php
      }
    ?>

   
  </body>
</html>
