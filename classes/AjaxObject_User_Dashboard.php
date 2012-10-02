<?php
//this contains all code pertaining to displaying a user's dashboard, and doing anything therein, such as submitting questions, updating
//account details, etc
class AjaxObject_User_Dashboard extends AjaxObject {

//an example of how containers work: there are two returned here, which may have their content set by another method which would do
//something like $this->html['documents'] = 'blah'; and $this->html['content'] = 'other blah';
//where $this->html is then picked up by AjaxObject::returnStuff(), passed back to AjaxEngine::formatOutput() and shot back to the client JS
	public function content_desktop() {
		ob_start();
?>
<div class="block_left">
	<div id="documents_container">
		<div class="fader"></div>
	</div>
</div>
<div class="block_right">
	<div id="content_container">
		<div class="fader"></div>
	</div>
</div>
<?php
		$this->html['desktop'] = ob_get_contents();
		ob_end_clean();
	}

	public function content_desktop_documents() {
		$object = new AjaxObject_User_Documents($this->user);
		$this->html['documents'] = $object->getListingHTML();
	}

	public function content_home() {
		ob_start();
?>
<div>
	<ul class="content_nav">
		<li class="nav_item">
			<a href="#_=home/dashboard">Dashboard</a>
		</li>
<?php
		foreach(self::getTabs() as $tab_ref=>$tab) {
?>
		<li class="nav_gap"></li>
		<li class="nav_item">
			<a href="#_=home/question/<?=$tab_ref?>" class="colour_<?=$tab_ref?>"><?=$tab['name']?></a>
		</li>
<?php
		}
?>
	</ul>
</div>
<div id="home_container">
	<div class="fader">
	</div>
</div>
<?php
		$this->html['content'] = ob_get_contents();
		ob_end_clean();
	}
}
?>