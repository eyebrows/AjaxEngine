<?php
//simple class to encapsulate logging of SQL queries to a file for performance analysis
//requires being turned on by a call to logSqlOn(), then all sql queries (passed through Service_MysqlWrapper at any rate) get automatically logged
//until a call tologSqlOff() stops it again. So, sql analysis can be done at any segment of code, easily, by just calling this class and those methods
class Service_Debug {

	private $log_sql=false, $sql_queries=0, $log_sql_file;

	public function logSqlOn($message='') {
		$this->log_sql = true;
		$this->sql_queries = 0;
		$this->log_sql_file = @fopen(ROOT.'/sql.log', 'a');
		if($message)
			$this->logSqlMessage($message, true);
	}

	public function logSqlOff() {
		if($this->log_sql_file) {
			$this->logSqlMessage('Logging ended after '.$this->sql_queries.' queries', true);
			fclose($this->log_sql_file);
		}
		$this->log_sql = false;
	}

	public function logSql($sql) {
		if($this->log_sql && $this->log_sql_file) {
			++$this->sql_queries;
			fwrite($this->log_sql_file, $sql."\n");
		}
	}

	public function logSqlMessage($message, $intro=false) {
		if($this->log_sql && $this->log_sql_file) {
			$micro = explode(' ', microtime());
			$message.=' | '.Lib_Date::xmlEncode(time(), true).str_replace('0.', '.', $micro[0]);
			if($intro)
				$message = $message."\n".str_repeat('=', strlen($message))."\n";
			else
				$message = "\n".' - '.$message."\n\n";
			fwrite($this->log_sql_file, $message);
		}
	}

//separate method used to mail a var_dump
	public function dump($_, $subject='') {
		ob_start();
		var_dump($_);
		$return = ob_get_contents();
		ob_end_clean();
		self::mail($subject, $return);
	}

//non-logging method for just sending an email to show something
	public function mail($subject, $message='') {
		mail('{my email address}', $subject, $message);
	}
}
?>