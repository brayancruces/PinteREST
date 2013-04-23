<?php
	/**
	 * Pinterest Class
     *
	 * This class depends on Simple HMTL DOM
	 **/

	require_once 'simple_html_dom.php';

	class Pinterest {
		/**
		 * Constructor Function
		 * @params = Array of parameters
		 * 	 $user = Required username (String)
		 *   @boards = Array of boards to return pins and data from.
		 *   $limit = Limit of # of pins to return. 0 will return all of the pins.
		 **/
		public function __construct($params) {
			if (is_array($params)) {
				$this->user = isset($params['user']) ? $params['user'] : NULL;
				$this->boards = isset($params['boards']) ? $params['boards'] : NULL;
				$this->limit = isset($params['limit']) ? $params['limit'] : NULL;
			}

			// initialize response object
			$this->response = (object) array(
				'status'=>'',
				'message'=>'',
				'data'=> array()
			);

			// set up the simple dom object
			$this->html = new simple_html_dom();


		}

		/**
		 * Destructor
		 * The html and response objects can grow large, so let's unset them.
		 **/
		public function __destruct() {
			unset($this->html, $this->user, $this->boards, $this->limit, $this->response);
		}

		/**
		 * Get
		 * Determines what type of call (all user pins or boards) returns the response array
		 **/
		public function get() {
			// Case where user was not defined
			if (is_null($this->user)) {
				$this->response->status = "ERROR";
				$this->response->message = "'user' is a required parameter";
			}
			// Case where boards was not defined, so let's get all of the user's pins
			elseif (is_null($this->boards)) {
				$this->getAllPins();
			}
			// Case where boards was defined
			elseif (is_array($this->boards)) {
				$this->getBoards();
			}
			// There was an error with the format of boards
			else {
				$this->response->status = "ERROR";
				$this->response->message = "'boards' was not formatted properly";
			}

			return $this->response;
		}

		/**
		 * getAllPins
		 * Get all pin data for the user
		 **/
		private function getAllPins() {
			$pincount = 0;

			// loop through all of the pins
			$pagenum = 1;
			$pins = array();
			do {
				$html = file_get_html("http://pinterest.com/$this->user/pins/?lazy=1&page=$pagenum");

				// break if last page
				if(!$html->find('div[class=pin]')) {
					break;
				}

				// parse the page
				foreach($html->find('div[class=pin]') as $pin) {
					// parse the pin data
					$pins[] = $this->parsePin($pin);

					// check to see if we have reached our limit
					if ($this->limit > 0) {
						$pincount++;
						if ($pincount >= $this->limit) {
							break;
						}
					}
				}

				$pagenum++;

			} while ( ($pincount < $this->limit) || ($this->limit == 0) );

			$this->response->data = $pins;
		}

		/**
		 * getBoards
		 * Get pin data for specified boards and update response object
		 **/
		private function getBoards() {
			$pincount = 0;

			// loop through each of the boards
			foreach ($this->boards as $board) {
				$pagenum = 1;
				$pins = array();
				do {
					$html = file_get_html("http://pinterest.com/$this->user/$board/?lazy=1&page=$pagenum");

					// break if last page
					if(!$html->find('div[class=pin]')) {
						break;
					}

					// parse the page
					foreach($html->find('div[class=pin]') as $pin) {
						// parse the pin data
						$pins[] = $this->parsePin($pin);

						// check to see if we have reached our limit
						if ($this->limit > 0) {
							$pincount++;
							if ($pincount >= $this->limit) {
								break;
							}
						}
					}

					$pagenum++;

				} while ( ($pincount < $this->limit) || ($this->limit == 0) );

				$board_contents['boardName'] = $board;
				$board_contents['pins'] = $pins;
				$this->response->data[] = $board_contents;
			}
		}

		/**
		 * parsePin
		 * @pin = Simple HTML DOM pin object
		 * Parse all the Pin data from the DOM and return an array of PINs
		 **/
		private function parsePin($pin) {
			// get the pin ID
			$item['id'] = $pin->{'data-id'};

			// thumbnail image
			$item['thumb'] = $pin->{'data-closeup-url'};

			// description
			$item['description'] = $pin->find('.description', 0)->plaintext;

			// link to the original page this was pinned from
			if ($pin->find('.attribution .NoImage a',0)) {
				$item['link'] = $pin->find('.attribution .NoImage a[rel=nofollow]',0)->href;
			} else {
				$item['link'] = "";
			}

			// repin count
			$item['repins'] = intval(preg_replace("/[^0-9,.]/", "", trim($pin->find('.stats .RepinsCount',0)->plaintext)));

			// likes count
			$item['likes'] = intval(preg_replace("/[^0-9,.]/", '', trim($pin->find('.stats .LikesCount',0)->plaintext)));

			// comments count
			$item['comments'] = intval(preg_replace("/[^0-9,.]/", '', trim($pin->find('.stats .CommentsCount',0)->plaintext)));

			return $item;
		}

	}

?>