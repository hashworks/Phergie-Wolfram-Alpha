<?php

namespace hashworks\Phergie\Plugin\WolframAlpha;

use Phergie\Irc\Bot\React\AbstractPlugin;
use \WyriHaximus\Phergie\Plugin\Http\Request;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use Phergie\Irc\Plugin\React\Command\CommandEvent as Event;

/**
 * Plugin class.
 *
 * @category Phergie
 * @package hashworks\Phergie\Plugin\WolframAlpha
 */
class Plugin extends AbstractPlugin {

	protected $appid = '';
	protected $useMetric = true;
	protected $processingReply = true;

	protected $podTitleExceptions = array('Plot'); // Answer pods that are unusable for IRC

	public function __construct($config = array()) {
		if (isset($config['appid'])) $this->appid = $config['appid'];
		if (isset($config['useMetric'])) $this->useMetric = $config['useMetric'];
		if (isset($config['processingReply'])) $this->processingReply = $config['processingReply'];
	}

	/**
	 * @return array
	 */
	public function getSubscribedEvents () {
		return array(
				'command.wolfram-alpha'      => 'handleCommand',
				'command.wolfram-alpha.help' => 'handleCommandHelp',
		);
	}

	/**
	 * Sends reply messages.
	 *
	 * @param Event        $event
	 * @param Queue        $queue
	 * @param array|string $messages
	 */
	protected function sendReply (Event $event, Queue $queue, $messages) {
		$method = 'irc' . $event->getCommand();
		if (is_array($messages)) {
			$target = $event->getSource();
			foreach ($messages as $message) {
				$queue->$method($target, $message);
			}
		} else {
			$queue->$method($event->getSource(), $messages);
		}
	}

	/**
	 * @param Event $event
	 * @param Queue $queue
	 */
	public function handleCommandHelp (Event $event, Queue $queue) {
		$this->sendReply($event, $queue, array(
				'Usage: wolfram-alpha <query>',
				'Aks WolframAlpha about the query and returns the best result.'
		));
	}

	/**
	 * @param Event $event
	 * @param Queue $queue
	 */
	public function handleCommand (Event $event, Queue $queue) {

		$query = join(' ', $event->getCustomParams());

		if (!empty($query)) {

			$suffix = '&input=' . rawurlencode($query);
			if ($this->useMetric) {
				$suffix .= '&units=metric';
			}

			$errorCallback = function($error) use ($event, $queue) {
				$this->sendReply($event, $queue, 'Error: ' . $error);
			};

			if ($this->processingReply) {
				$this->sendReply($event, $queue, 'Processing...');
			}

			$this->emitter->emit('http.request', [new Request([
					'url'             => 'http://api.wolframalpha.com/v2/query?format=plaintext&shortest=true&appid=' . $this->appid . $suffix,
					'resolveCallback' => function ($data) use ($event, $queue, $errorCallback) {
						try {
							$data = simplexml_load_string($data);
							if ($data->error == false) {
								unset($data->pod[0]); // Remove input pod
								foreach ($data->pod as $pod) {
									if (!in_array($pod['title'], $this->podTitleExceptions)) {
										$plaintext = (string) $pod->subpod[0]->plaintext;
										$plaintext = preg_replace_callback('/\\\\:([a-fA-F0-9]{4})/', function($matches) {
											return iconv('UCS-4LE', 'UTF-8', pack('V', hexdec('U' . $matches[1])));
										}, $plaintext);
										$plaintext = str_replace('  ', ' ', $plaintext);
										if (!empty($plaintext)) {
											$this->sendReply($event, $queue, explode("\n", $plaintext));
											return;
										}
									}
								}
							} else {
								if ($data->error->code == 1) {
									$errorCallback('Please set a valid appID in your configuration.');
								} else {
									$errorCallback((string) $data->error->msg);
								}
								return;
							}
						} catch (Exception $e) {};
						$errorCallback('Failed to process query.');
					},
					'rejectCallback'  => $errorCallback
			])]);

		} else {
			$this->handleCommandHelp($event, $queue);
		}
	}

}
