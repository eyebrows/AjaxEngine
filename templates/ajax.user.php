<?php
//prevent the script from being displayed independently; can only be seen via index.php
if(!defined('ROOT'))
	return;

//this is only a shell showing the important parts - the JS includes and the template's root "container": <div id="desktop_container", line 57
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<script type="text/javascript">
	var DEFAULT_PATH = '<?=$engine->defaultPath()?>';
	</script>
	<script type="text/javascript" src="<?=SITE_URL?>/js/dashboard.ajax_engine.js"></script>
	<script type="text/javascript" src="<?=SITE_URL?>/js/dashboard.user.js"></script>
</head>
<body>
	<div class="fixed_messages">
		<div class="message_okay message"></div>
		<div class="message_alert message"></div>
		<div class="message_error message"></div>
	</div>
	<div id="page">
		<div id="top">
			<div class="mid">
				<div id="top_wrapper">
					<div id="logo" class="button" href="#reload" title="Reload Dashboard"></div>
					<div id="loading" style="float:left;margin-top:32px;margin-left:30px;"></div>
					<div id="top_menu">
						<?=displayButton('Logout', "window.location='".SITE_URL."/?action=Logout';", 'float:right;')?>
						<div id="loggedin" class="right" style="margin-right:10px;">
							Logged in as: <span style="font-weight:bold;"><a href="#_=account"><?=$me->getField('forename').' '.$me->getField('surname')?></a></span><br>
							Account Reference: <span style="font-weight:bold;"><a href="#_=account" title="Edit Your Account"><?=$me->getCompany()->getField('associate_ref')?></a></span>
						</div>
					</div>
				</div>
				<div id="content_wrapper">

					<div id="dashboard_container">
						<div id="block_top" style="background-color:#f0f0f0;border-bottom:1px solid #d0d0d0;">
<?php
foreach($engine->getMenuButtons() as $href=>$button) {
?>
							<div id="<?=$button['ref']?$button['ref']:$href?>_button" style="<?=$button['right']?'float:right;margin-right:0px;margin-left:15px;':''?>" class="button_container button_grey button" href="#_=<?=$href?>">
								<div class="button_left pngfix"></div>
								<div class="button_mid" style="padding:0px 10px 0px 10px;text-align:center;">
									<p style="margin:0px;" class="left">
										<?=$button['name']?>
									</p>
								</div>
								<div class="button_right pngfix"></div>
							</div>
<?php
}
?>
						</div>
						<div id="desktop_container" class="group" style="margin-top:15px;">
							<div class="fader">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>