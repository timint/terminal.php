<?php

 /**
  * Terminal.php - Terminal Emulator for PHP
  *
  * @package  Terminal.php
  * @author   SmartWF <hi@smartwf.ir>
  * @author   T. Almroth <info@tim-international.net>
  */

	class TerminalPHP {
		public $whoami = 'unknown';
		public $hostname = '';

		/* Custom Commands for overriding */
		private $registeredCommands = [];

		/* These Commands Doesn't Execute */
		private $disabledCommands = [
			/*'mkdir', 'rm', 'git', 'wget', 'curl', 'chmod', 'rename', 'mv', 'cp'*/
		];

		/**
		 * initialize Class
		 * @param $path string default path to start
		 */
		public function __construct($path = '') {

			if (!function_exists('shell_exec')) {
				http_response_code(403);
				throw new Exception('Sorry, this server has blocked shell access :(');
			}

			if ($path) {
				chdir($path);
			}

			$this->registerCommand('cd', function($arg) {
				chdir($arg);
			});

			$this->registerCommand('pwd', function($arg) {
				return getcwd();
			});

			$this->registerCommand('ping', function($arg) {
				if (strpos($arg, '-c ') !== false) {
					return trim(shell_exec('ping '.$arg));
				}
				return trim(shell_exec('ping -c 4 '.$arg));
			});

			if ($result = $this->runCommand('whoami')) {
			  $this->whoami = preg_replace('#^(.*\\\\)?(.*)$#', '$2', $result);
			}

			$this->hostname = php_uname('n');
		}

		/**
		 * Register Custom Commands
		 * @param $cmd string command
		 * @param $callable callable function
		 * @return bool
		 */
		public function registerCommand($cmd, $callable) {
			$this->registeredCommands[$cmd] = $callable;
			return true;
		}

		/**
		 * Run Command in Terminal
		 * @param $command string command to run
		 * @return string
		 */
		public function runCommand($command) {

			$cmd = strtok($command, ' ');
			$arg = substr($command, strlen($cmd)+1);

			if (array_search($cmd, $this->disabledCommands) !== false) {
				throw new Exception('Command disabled');

			} else if (!empty($this->registeredCommands[$cmd])) {

				return $this->registeredCommands[$cmd]($arg);

			} else {
				if (preg_match('#^WIN#i', PHP_OS)) {
					$result = trim(shell_exec($command .' 2>&1'));
					return sapi_windows_cp_conv(sapi_windows_cp_get('oem'), 65001, $result);
				} else {
					return trim(shell_exec($command));
				}

			}
		}

		/**
		 * Check Command Exists
		 * @param $command string command to check
		 * @return bool
		 */
		private function commandExists($command) {
			if (trim(shell_exec('command -v '.$command))) {
				return true;
			}
			return false;
		}

		/**
		 * Array of All Commands
		 * @return array
		 */
		public function commandList() {

			$internalCommands = array_keys($this->registeredCommands);

			if (is_file('/usr/bin')) {
				$externalCommands = preg_split('#\R+#', $this->runCommand('/usr/bin'), -1, PREG_SPLIT_NO_EMPTY);
			} else {
				$externalCommands = [];
			}

			$commands = array_unique(array_merge($internalCommands, $externalCommands));

			sort($commands);

			return $commands;
		}
	}
