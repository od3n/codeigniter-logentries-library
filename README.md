codeIgniter-logentries-library
==============================

Codeigniter Library to connect and make log entries using https://logentries.com/

You'll need to install (or enable) the Socket PHP extension: http://www.php.net/manual/en/sockets.installation.php


## Usage
Set up a controller something like this:

	class Welcome extends CI_Controller {
		
		public function index() {

            $params = array('logger_name' => 'myapp', 'token' => 'XXXXXXX');
            $this->load->library('LeLogger', $params, 'log');
            $this->log->Debug("I'm an informational message");
		}
	}