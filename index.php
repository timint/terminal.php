<?php

	ini_set('display_errors', 'on');
	mb_http_output('utf-8');

	// Choose a random key Like ('Mmbuge8maD5VAUMc') for Security
	//define('KEY', 'Mmbuge8maD5VAUMc');

	try {

		//if (KEY != '' && (!isset($_GET['key']) || $_GET['key'] != KEY)) {
		//	http_response_code(400);
		//	throw new Exception('Sorry, invalid key provided');
		//}

		require 'terminal.php';
		$terminal = new TerminalPHP();

		$terminal->registerCommand('about', function(){
			return 'Originally developed by <a href="https://github.com/smartwf/" target="_blank">SmartWF</a>.<br />Further developed by <a href="https://www.github.com/timint/" target="_blank">T. Almroth</a>.';
		});

	} catch (Exception $e) {
		die('Error: '. $e->getMessage());
	}

	// Check if Request is Ajax
	if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && isset($_POST['command'])) {

		try {

			if (empty($_POST['command'])) {
				throw new Exception('Missing command');
			}

			if (!empty($_POST['path'])) {
				if (is_dir($_POST['path'])) {
					chdir($_POST['path']);
				} else {
					throw new Exception('No such directory: '. $_POST['path']);
				}
			}

			$command = strtok($_POST['command'], '');
			$arguments = substr($_POST['command'], strlen($_POST['command'])+1);
			$path = isset($_POST['path']) ? $_POST['path'] : '';

			$result = [
				'result' => $terminal->runCommand($_POST['command']),
				'path' => getcwd(),
			];

		} catch (Exception $e) {
			$result = [
			  'result' => $e->getMessage(),
			  'path' => getcwd(),
			];
		}

		header('Content-Type: application/json; charset='. mb_http_output());
		echo json_encode($result, JSON_UNESCAPED_SLASHES);
		exit;
	}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<title>Terminal.php</title>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<link href="https://cdn.rawgit.com/rastikerdar/vazir-code-font/v1.1.2/dist/font-face.css" rel="stylesheet" type="text/css" />
<style>
	:root {
		--background-url: url(https://images.unsplash.com/photo-1485470733090-0aae1788d5af?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&w=1920);
		--font: 'Vazir Code', 'Vazir Code Hack';
		--font-size: 16px;
		--header-color: #000;
		--primary-color: #101010;
		--color-scheme-1: #55c2f9;
		--color-separator: #fff;
		--color-scheme-3: #5af68d;
		--scrollbar-color: #333;
		--title-color: white;
		--cursor-color: #fff;
		--cursor: '|';
		/*--separator: '--->';*/
		--separator: '$';
	}
	* { font-family: var(--font); box-sizing: border-box;}
	body { background: var(--background-url) center no-repeat; background-size: cover; margin: 0; padding: 0; background-attachment: fixed; }
	a { color: #29a9ff; }
	#terminal { display: flex; flex-flow: column; width: 80vw; aspect-ratio: 16/9; position: relative; margin: 2rem auto; background: inherit; border-radius: 10px; max-width: 80rem; overflow: hidden; }
	#terminal { background: var(--primary-color); opacity: .75; backdrop-filter: blur(.5rem); }
	#terminal header { padding: 1rem; background: var(--header-color); border-radius: 10px 10px 0 0; user-select: none; }
	#terminal header title { display: block; text-align: center; color: var(--title-color); }
	#terminal header .buttons { position: absolute; display: inline-block; top: 1rem; left: 1rem; }
	#terminal header .buttons * { display: inline-block; width: 15px; height: 15px; background: rgba(255,255,255,.1); border-radius: 50%; margin-right: 5px; cursor: pointer; }
	#terminal header .buttons [title="close"] { background: #fc615d; }
	#terminal header .buttons [title="maximize"] { background: #fdbc40; }
	#terminal header .buttons [title="minimize"] { background: #34c749; }
	#terminal .content { flex-grow: 1; padding: 1rem; overflow-x: hidden; overflow-y: auto; color: #ececec; font-size: var(--font-size); }
	::-webkit-scrollbar { width: 7px; }
	::-webkit-scrollbar-track {  background: rgba(0,0,0,0); }
	::-webkit-scrollbar-thumb { background: var(--scrollbar-color); border-radius: 5px; }
	#terminal .content .line { display: block; white-space: break-spaces; margin-bottom: 1rem; }
	#terminal .content .path { /*display: block;*/ color: var(--color-scheme-1); }
	#terminal .content .separator { color: var(--color-separator); letter-spacing: -6px; margin-right: 5px; }
	#terminal .content .separator::before { content: var(--separator); }
	#terminal .content .cm { color: var(--color-scheme-3); }
	#terminal .content code { display: inline; margin: 0; white-space: unset;}
	#terminal .content .cursor { color: var(--cursor-color); position: relative; top: -2px; }
	#terminal .content .cursor::before { content: var(--cursor); animation: blink 1s steps(1) infinite; }
	footer { color: white; text-align: center; font-size: 12px; }
	footer a { text-decoration: none; color: #fdbc40; }
	@keyframes blink { 0% { opacity: 1} 50% { opacity: 0} 100% { opacity: 1; } }
</style>
</head>

<body>

<main id="terminal">
	<header>
		<div class="buttons">
			<span title="close"></span>
			<span title="maximize"></span>
			<span title="minimize"></span>
		</div>
		<title>Terminal.php &nbsp; <?php echo '('. $terminal->whoami .'@'. $terminal->hostname .')';?></title>
	</header>

	<div class="content">
		<div class="line current"><span class="path"><?php echo getcwd();?></span> <span class="separator"></span> <span class="input"><span class="cursor"></span></span></div>
	</div>
</main>

<footer><?php echo $terminal->runCommand('about'); ?></footer>

<script>
	let commands_list = <?php echo json_encode($terminal->commandList(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;

	var path = '<?php echo addcslashes(getcwd(), "'\\");?>';
	var command = '';
	var command_history = [];
	var history_index = 0;
	var suggest = false;
	var blink_position = 0;
	var autocomplete_position = 0;
	var autocomplete_search_for = '';
	var autocomplete_temp_results = [];
	var autocomplete_current_result = '';

	var localCommands = {

		'about': function() {
			$('#terminal .content').append("<div class=\"line\"><?php echo addcslashes($terminal->runCommand('about'), "\r\n\""); ?></div>");
		},

		'clear': function() {
			$('#terminal .content').html('');
		},

		'cls': function() {
			$('#terminal .content').html('');
		},

		'history': function(arg) {
			var res = [];
			let start_from = arg.length ? Number.isInteger(Number(arg[0])) ? Number(arg[0]) : 0 : 0;

			if (start_from != 0 && start_from <= command_history.length) {
				for (var i = command_history.length-start_from; i < command_history.length; i++) { res[res.length] = (i+1)+' &nbsp;'+command_history[i]; }
			} else {
				command_history.forEach(function(item, index) { res[res.length] = (index+1)+' &nbsp;'+item; });
			}

			$('#terminal .content').append('<div class="line">'+res.join('<br />')+'</div>');
		}
	};

	/**********************************************************/
	/*                         Events                         */
	/**********************************************************/

	$(document).on('keydown', function(e) {
		var keyCode = typeof e.which == "number" ? e.which : e.keyCode;

		switch (true) {

			case (keyCode == 8): // Backspace

				if (command != '') {
					e.preventDefault();
					backSpace();
				}

				break;

			case (keyCode == 9): // Delete

				if (command != '') {
					e.preventDefault();
					reverseBackSpace();
				}

				break;

			case (keyCode == 46): // Tab

				if (command != '') {
					e.preventDefault();
					autoComplete();
				}

				break;

			case (e.ctrlKey && keyCode == 67): // Ctrl + C

				autocomplete_position = 0;
				endLine();
				newLine();
				reset();

				break;

			case (keyCode == 13): // Enter

				if (autocomplete_position != 0) {
					autocomplete_position = 0;
					command = autocomplete_current_result;
				}

				if (command.toLowerCase().split(' ')[0] in localCommands) {
					localCommands[command.toLowerCase().split(' ')[0]](command.split(' ').slice(1));
				} else if (command.length != 0) {
					$.ajax({
						type: 'POST',
						data: {command: command, path: path},
						dataType: 'json',
						async: false,
						cache: false,
						success: function(result) {
							path = result.path;
							var $output = $('<div class="line"></div>').text(result.result);
							$('#terminal .content').append($output);
						}
					});
				}

				endLine();
				addToHistory(command);
				newLine();
				reset();
				$('#terminal .content').scrollTop($('#terminal .content').prop('scrollHeight'));

				break;

			case (keyCode == 35): // Home
			case (keyCode == 36): // End
			case (keyCode == 37): // Left
			case (keyCode == 39): // Right

			  if (command != '') {
					e.preventDefault();
					$('#terminal .line.current .cursor').remove();

					if (autocomplete_position != 0) {
						autocomplete_position = 0;
						command = autocomplete_current_result;
					}

					switch (keyCode) {
						case 35: blink_position = 0; break;
						case 36: blink_position = command.length*-1; break;
						case 37: blink_position--; break;
						case 39: blink_position++; break;
					}

					printCommand();
				}

			  break;

			case (keyCode == 38): // Up

				if (command == '' || suggest) {
					if (command_history.length && command_history.length >= history_index*-1+1) {
						history_index--;
						command = command_history[command_history.length+history_index];
						printCommand();
						suggest = true;
					}
				}

				break;

			case (keyCode == 40): // Down

				if (command == '' || suggest) {
					if (command_history.length && command_history.length >= history_index*-1 && history_index != 0) {
						history_index++;
						command = (history_index == 0) ? '' : command_history[command_history.length+history_index];
						printCommand();
						suggest = (history_index == 0) ? false : true;
					}
				}

				break;

			case (keyCode == 32):
			case (keyCode == 220):
			case ((keyCode >= 45 && keyCode <= 195) && !(keyCode >= 112 && keyCode <= 123) && $.inArray(keyCode, [46, 91, 93, 144, 145, 45]) == -1):
				type(e.key);
				$('#terminal .content').scrollTop($('#terminal .content').prop('scrollHeight'));
				break;
		}
	});

	function reset() {
		command = '';
		history_index = 0;
		blink_position = 0;
		autocomplete_position = 0;
		autocomplete_current_result = '';
		suggest = false;
	}

	function endLine() {
		$('#terminal .line.current .cursor').remove();
		$('#terminal .line.current').removeClass('current');
	}

	function newLine() {
		$('#terminal .content').append('<div class="line current"><span class="path">'+path+'</span> <span class="separator"></span> <span class="input"><span class="cursor"></span></span></div>');
	}

	function addToHistory(command) {
		if (command.length >= 2 && (command_history.length == 0 || command_history[command_history.length-1] != command)) {
			command_history[command_history.length] = command;
		}
	}

	function printCommand(cmd = '') {
		if (cmd == '') {
			cmd = command;
		} else {
			blink_position = 0;
		}

		let part1 = cmd.substr(0, cmd.length + blink_position);
		let part2 = cmd.substr(cmd.length + blink_position);

		$('#terminal .line.current .input').html(part1 + '<span class="cursor"></span>' + part2);
	}

	function type(t) {
		history_index = 0;
		suggest = false;

		if (autocomplete_position != 0) {
			autocomplete_position = 0;
			command = autocomplete_current_result;
		}
		if (command[command.length-1] == '/' && t == '/') {
			return;
		}

		let part1 = command.substr(0, command.length + blink_position);
		let part2 = command.substr(command.length + blink_position);
		command = part1+t+part2;

		printCommand();
	}

	function backSpace() {
		if (autocomplete_position != 0) {
			autocomplete_position = 0;
			command = autocomplete_current_result;
		}

		let part1 = command.substr(0, command.length + blink_position);
		let part2 = command.substr(command.length + blink_position);

		command = part1.substr(0, part1.length-1)+part2;

		printCommand();
	}

	function reverseBackSpace() {
		let part1 = command.substr(0, command.length + blink_position);
		let part2 = command.substr(command.length + blink_position);

		command = part1+part2.substr(1);

		if (blink_position != 0) {
			blink_position++;
		}

		printCommand();
	}

	function autoComplete() {

		if (autocomplete_search_for != command) {
			autocomplete_search_for = command;
			autocomplete_temp_results = [];

			if (command.split(' ').length == 1) {
				let cmdlist = commands_list.concat(Object.keys(localCommands));
				autocomplete_temp_results = cmdlist
					.filter(function (cm) {return (cm.length > command.length && cm.substr(0, command.length).toLowerCase() == command.toLowerCase()) ? true : false;})
					.reverse().sort(function (a, b) {return b.length - a.length;});
			} else if (command.split(' ').length == 2) {
				let cmd = command.split(' ')[0];
				let cmd_parameter = command.split(' ')[1];
				var temp_cmd = '';

				if (cmd == 'cd' || cmd == 'cp' || cmd == 'mv' || cmd == 'cat') {

					switch (cmd) {

						case 'cd':
							temp_cmd = 'ls -d '+cmd_parameter+'*/';
							break;

						case 'cp':
						case 'mv':
							temp_cmd = 'ls -d '+cmd_parameter+'*/';
							break;

						case 'cat':
							temp_cmd = 'ls -p | grep -v /';
							break;

						default:
							temp_cmd = '';
							break;
					}

					$.ajax({
						type: 'POST',
						data: {command: temp_cmd, path: path},
						dataType: 'json',
						async: false,
						cache: false,
						success: function(result) {
							autocomplete_temp_results = result.filter(function(cm) {
								return (cm.length != 0) ? true : false;
							});
						}
					});
				}
			}
		}

		if (autocomplete_temp_results.length && autocomplete_temp_results.length > Math.abs(autocomplete_position)) {
			autocomplete_position--;
			autocomplete_current_result = ((command.split(' ').length == 2) ? command.split(' ')[0]+' ' : '')+autocomplete_temp_results[autocomplete_temp_results.length+autocomplete_position];
			printCommand(autocomplete_current_result);

		} else {
			autocomplete_position = 0;
			autocomplete_current_result = '';
			printCommand();
		}
	}
</script>
</body>
</html>