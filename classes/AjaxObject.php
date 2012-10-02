<?php
//the abstract class defining the template for how to actually process requests. AjaxObjects group together functionality by user type, and
//by the part of the system they belong to
abstract class AjaxObject {
//children of this class should each set these if they need to, and NOT return anything. returnStuff() is called by a descendent of
//AjaxEngine just prior to returning itself.
//$data is used for any other miscellaneous data to be sent back, such as "number of items" when returning "lists of stuff" in pure html

	protected $user, $html, $path, $okay, $alert, $error, $textarea, $data;

//in case a class needs extending to live on multiple base paths, for example an AjaxObject containing routines for managing "lists of companies"
//might be extended once for "external suppliers" and separately for "customers", so both sets can be managed separately, but using common code
	protected $path_base;

	public function __construct($user) {
		$this->user = $user;
	}

	public function getOkay() {
		return $this->okay;
	}

	public function getAlert() {
		return $this->alert;
	}

	public function getError() {
		return $this->error;
	}

//called by AjaxEngine once it's called the relevant methods of a descendent of this class, to package up the variables set by the AjaxObject
//to be returned to the JS client (in JSON form)
	public function returnStuff() {
		return array(
			'html'=>$this->html,
			'path'=>$this->path,
			'okay'=>$this->okay,
			'alert'=>$this->alert,
			'error'=>$this->error,
			'textarea'=>$this->textarea,
			'data'=>$this->data,
		);
	}

//to save having to define them in descendents, as all account types might need this functionality
	public static function redirectDashboard($path) {
		ob_start();
?>
Redirecting...
<script type="text/javascript">
function onLoad() {
	$.bbq.pushState('#_=<?=$path?>');
}
</script>
<?php
		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}

//to save having to define them in descendents, as all account types might need this functionality
	public static function redirectBrowser($url, $seconds=5) {
		ob_start();
?>
<a href="javascript:void(0)" onclick="redirect();" class="href">Redirecting...</a>
<script type="text/javascript">
function onLoad() {
	setTimeout(function() {
		redirect();
	}, <?=$seconds?>*1000);
}

function redirect() {
	window.location.href = '<?=$url?>';
}
</script>
<?php
		$return = ob_get_contents();
		ob_end_clean();
		return $return;
	}
}
?>