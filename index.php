<?php
session_start();
require_once 'src/config.php';
require_once 'src/Instagram.php';

function download_remote_file($file_url, $save_to)
{
	$content = file_get_contents($file_url);
	file_put_contents($save_to, $content);
}

function rrmdir($dir) {
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file))
            rrmdir($file);
        else
            unlink($file);
    }
    rmdir($dir);
}

if ($_POST['action'] == 'check')
{
	if (isset($_SESSION['temp_dir']))
		echo is_dir($_SESSION['temp_dir']) ? 'true' : 'false';
	else
		echo 'false';
	exit;
}

if ($_POST['action'] == 'save')
{
	include_once 'src/CreateZipFile.inc.php';
	$createZipFile = new CreateZipFile;
	
	$access_token = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null;
	$instagram = new Instagram(CLIENT_ID, CLIENT_SECRET, $access_token);
	$intagram_media = $instagram->get('users/self/media/recent');
	
	$temp_dir = '';
	$zipName = '';
	
	foreach ($intagram_media->data as $item) {
		//$temp_dir = getcwd() . '/temp/' . $item->user->username;
		$temp_dir = './temp/' . $item->user->username;
		$zipName = $item->user->username . '.zip';
		
		if (!is_dir($temp_dir)) 
		{
			mkdir($temp_dir, 0777);
		}
	}
	
	foreach ($intagram_media->data as $item) 
	{
		download_remote_file($item->images->standard_resolution->url,$temp_dir . '/' . $item->id . '.jpg');
		$fileContents = file_get_contents($temp_dir . '/' . $item->id . '.jpg');
		$createZipFile->addFile($fileContents, './' . $item->id . '.jpg');
	}
	
	//if (isset($_POST['chk_meta']) && $_POST['chk_meta'] == 'true')
	if ($temp_dir != '')
	{
		$metadata_file = $temp_dir . '/metadata.json';
		$file = fopen($metadata_file, 'w');
		fwrite($file, json_encode($intagram_media->data)); 
		fclose($file);
		
		$fileContents = file_get_contents($metadata_file);
		$createZipFile->addFile($fileContents, './metadata.json');
		
		$_SESSION['temp_dir'] = $temp_dir;
	
		//$createZipFile->zipDirectory($temp_dir,'./');
		$fd = fopen($zipName, "wb");
		$out = fwrite($fd,$createZipFile->getZippedfile());
		fclose($fd);
		$createZipFile->forceDownload($zipName);
		@unlink($zipName);
		
		rrmdir($temp_dir);
	}	
}

if ($_POST['action'] == 'logout')
{	
	unset($_SESSION);
	session_destroy();
	header("Location: ./");
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Create a backup of your Instagram photos and meta data with Instagram Backup</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="favicon.ico">
    <meta name="description" content="Create a backup of your Instagram photos and meta data to a single zip file.">
    <meta name="author" content="Carlos Eugenio Torres <cetorres@cetorres.com>">
	<meta property="og:title" content="Create a backup of your Instagram photos and meta data with Instagram Backup" /> 
	<meta property="og:description" content="Create a backup of your Instagram photos and meta data to a single zip file." /> 
	<meta property="og:image" content="http://cetorres.com/instagrambackup/img/instagrambackup.png" />
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
    	body {
        	padding-top: 60px;
        	padding-bottom: 40px;
      	}
    </style>
    <link href="css/bootstrap-responsive.min.css" rel="stylesheet">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
    
    <script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
	<script type="text/javascript" src="js/ib.js"></script>
	<script type="text/javascript">
	/* <![CDATA[ */
		(function() {
			var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
			s.type = 'text/javascript';
			s.async = true;
			s.src = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
			t.parentNode.insertBefore(s, t);
		})();
	/* ]]> */</script>

  </head>

  <body> 	
  	<?
	$access_token = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null;
	$instagram = new Instagram(CLIENT_ID, CLIENT_SECRET, $access_token);
	
	if (!$access_token) 
	{
		$loginUrl = $instagram->authorizeUrl(REDIRECT_URI, array('basic', 'comments', 'likes', 'relationships'));
	} 
	?>	

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="/instagrambackup"><img src="img/instagrambackup.png" width="18" height="18" style="margin-top:-6px" /> Instagram Backup</a>
          <div class="nav-collapse">
            <ul class="nav pull-right">
              <li class="divider-vertical"></li>
              
              <? if (isset($loginUrl)) { ?>
              
              <li class="dropdown">
				<a class="dropdown-toggle"
				   data-toggle="dropdown"
				   href="#">
					<i class="icon-user icon-white"></i> Sign in to Instagram
					<b class="caret"></b>
				</a>
				<ul class="dropdown-menu">
				  <li><a href="<?= $loginUrl ?>">Log in</a></li>
				</ul>
			  </li>
			  
			  <? } else {			  
			  	$access_token = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null;
				$instagram = new Instagram(CLIENT_ID, CLIENT_SECRET, $access_token);
				$intagram_user = $instagram->get('users/self');
				$username = $intagram_user->data->username;
				$profile_picture = $intagram_user->data->profile_picture;
				
				$intagram_media = $instagram->get('users/self/media/recent');
				$media_count = count($intagram_media->data);
			  ?>
			  
			  <li class="dropdown">
				<a class="dropdown-toggle"
				   data-toggle="dropdown"
				   href="#">
				    <img src="<?= $profile_picture ?>" width="18" height="18" />
					<?= $username ?>
					<b class="caret"></b>
				</a>
				<ul class="dropdown-menu">				  
				  <li><a href="javascript:;" onclick="signOut()">Log out</a></li>
				</ul>
			  </li>
			  
			  <? } ?>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="container">

      <div class="hero-unit">
      	<div style="float:right;margin-top:-35px">
			<? /* ?>
			<script type="text/javascript">
				bb_bid = "1606709";
				bb_lang = "en-US";
				bb_keywords = "instagram,backup,export,save,zip,photos,comments,fortaleza";
				bb_name = "custom";
				bb_limit = "4";
				bb_format = "bbm";
			</script>
			<script type="text/javascript" src="http://static.boo-box.com/javascripts/embed.js"></script>
			<? */ ?>
			<script type="text/javascript"><!--
				google_ad_client = "ca-pub-5022107150117818";
				google_ad_slot = "1010734641";
				google_ad_width = 250;
				google_ad_height = 250;
				//-->
			</script>
			<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
			</script>
		</div>
		
        <h1>Instagram Backup <small>beta</small></h1>
        <br/>
        <p>Create a backup of your Instagram photos and meta data to a single zip file.</p>
        
        <? if (isset($loginUrl)) { ?>
        
        <p><a href="<?= $loginUrl ?>" class="btn btn-primary btn-large"><i class="icon-user icon-white"></i> Sign in to Instagram</a></p>
        <br/>
        
        <? } else { ?>

		<? if ($media_count <= 0) { ?>
			<div class="alert alert-error" style="width:400px">Sorry. You don't have any photos on your Instagram account.</div>			
		<? } else { ?>
			<form id="form_save" name="form_save" action="index.php" class="form-inline">
			<input type="hidden" name="action" value="save" />
			<p>Great! You have <strong><?= $media_count ?></strong> photos on your Instagram account.</p>
			<input type="submit" id="but_save" class="btn btn-primary btn-large" value="Backup my Instagram now to a zip file &raquo;" />
			<span id="loading" class="alert alert-success" style="display:none">
				<img src="img/loading.gif" style="margin-top:-5px" />&nbsp;Creating backup file... It may take a while...
			</span>
			</form>
		<? } ?>
		
		<? /* ?>		
		<!--
		<label class="checkbox">
    		<input type="checkbox" id="chk_photos" name="chk_photos" value="true" checked disabled> Photos
		</label>
		<label class="checkbox">
    		<input type="checkbox" id="chk_meta" name="chk_meta" value="true" checked> Meta data (captions, comments, likes, tags)
		</label>		
		&nbsp;&nbsp;
		-->
		<? */ ?>		
		
		<? } ?>		
      </div>
 
      <div class="row">
        
        <div class="span4">
          <h2>Share</h2>
          <p>Spread the word. Share this service with your friends.</p>
          <p>
          	<div style="float:left;width:auto;">
    			<div style="float:left">
                  	<a href="https://twitter.com/share" class="twitter-share-button" data-via="cetorres">Tweet</a>
                	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
				</div>
			    <div style="float:left;width:130px">
			    	<? /* ?><div class="fb-like" data-href="http://cetorres.com/instagrambackup" data-send="false" data-layout="button_count" data-width="130" data-show-faces="false"></div> <? */ ?>
			    	<a name="fb_share" type="button_count"></a> 
					<script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" 
							type="text/javascript">
					</script>
			    </div>
			    <div style="float:left;margin-top:-1px">
			    	<g:plusone size="medium"></g:plusone>
					<script type="text/javascript">
					  (function() {
						var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
						po.src = 'https://apis.google.com/js/plusone.js';
						var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
					  })();
					</script>
			    </div>
            </div>
          </p>
       </div>
       
        <div class="span4">
          <h2>Support</h2>
          <p>If you like this app, please donate and help keeping it online.</p>
          <p>
          	<div style="float:left;width:auto;">
				<div style="float:left">
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="9FUMWGNR2AARY">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypalobjects.com/pt_BR/i/scr/pixel.gif" width="1" height="1">
					</form>
				</div>
				<div style="float:left;margin-top:5px;margin-left:5px">
					<a class="FlattrButton" style="display:none;" rev="flattr;button:compact;" href="http://cetorres.com/instagrambackup/"></a>
					<noscript><a href="http://flattr.com/thing/623947/Instagram-Backup" target="_blank">
					<img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a></noscript>
				</div>
			</div>
          </p>
        </div>
        
        <div class="span4">
          <h2>Ads</h2>
           <p>
           	<script type="text/javascript"><!--
			google_ad_client = "ca-pub-5022107150117818";
			/* Instagram Backup */
			google_ad_slot = "5278344794";
			google_ad_width = 234;
			google_ad_height = 60;
			//-->
			</script>
			<script type="text/javascript"
			src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
			</script>
           </p>
        </div>
      </div>

      <hr>

      <footer style="text-align:center">
        <p>&copy; 2012 <a href="http://about.me/cetorres">Carlos Eug&ecirc;nio Torres</a>. Made with <a href="http://php.net" target="_blank">PHP</a>, <a href="http://jquery.org" target="_blank">JQuery</a> and <a href="http://twitter.github.com/bootstrap/" target="_blank">Twitter Bootstrap</a> in <a href="http://wikipedia.org/wiki/Fortaleza" target="_blank">Fortaleza, Brazil</a>. This service is not associated in any way with <a href="http://instagram.com" target="_blank">Instagram</a>.</p>
      </footer>

	  <form id="form_logout" name="form_logout" action="index.php" method="post">
	  <input type="hidden" name="action" value="logout" />
	  </form>
	  
	  <iframe id="iframe_signout" name="iframe_signout" src="" width="1" height="1" frameborder="0"></iframe>
				  
    </div>

	<script type="text/javascript">
  var uvOptions = {};
  (function() {
    var uv = document.createElement('script'); uv.type = 'text/javascript'; uv.async = true;
    uv.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'widget.uservoice.com/gIotZNEz0ycHYNGe9pkWA.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(uv, s);
  })();
</script>

	<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-30763407-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

  </body>
</html>