<?php
	declare(strict_types=1);

	namespace Fawno\EAN13;

	use Fawno\FPDF\FawnoFPDF;

	class pdfEAN13 extends FawnoFPDF {
		private $ean13_height = 35.433071;
		private $ean13_width = 73.7;
		private $ean13_textheight = 8;
		private $ean13_barwidth = 0.7;
		private $ean13_barheight = 26.7;
		private $ean13_senheight = 30.3;
		private $ean13_ypos = 8;
		private $ean13_xpos = 8;

		// EAN Parity Encodig Table => EAN_pet
		private $EAN_pet = array(
			0 => array('O', 'O', 'O', 'O', 'O', 'O'),
			1 => array('O', 'O', 'E', 'O', 'E', 'E'),
			2 => array('O', 'O', 'E', 'E', 'O', 'E'),
			3 => array('O', 'O', 'E', 'E', 'E', 'O'),
			4 => array('O', 'E', 'O', 'O', 'E', 'E'),
			5 => array('O', 'E', 'E', 'O', 'O', 'E'),
			6 => array('O', 'E', 'E', 'E', 'O', 'O'),
			7 => array('O', 'E', 'E', 'E', 'O', 'E'),
			8 => array('O', 'E', 'O', 'E', 'E', 'O'),
			9 => array('O', 'E', 'E', 'O', 'E', 'O')
		);

		// EAN Character Set Encoding Table => EAN_cet
		private $EAN_cet = array(
			0 => array('O' => '0001101', 'E' => '0100111', 'R' => '1110010'),
			1 => array('O' => '0011001', 'E' => '0110011', 'R' => '1100110'),
			2 => array('O' => '0010011', 'E' => '0011011', 'R' => '1101100'),
			3 => array('O' => '0111101', 'E' => '0100001', 'R' => '1000010'),
			4 => array('O' => '0100011', 'E' => '0011101', 'R' => '1011100'),
			5 => array('O' => '0110001', 'E' => '0111001', 'R' => '1001110'),
			6 => array('O' => '0101111', 'E' => '0000101', 'R' => '1010000'),
			7 => array('O' => '0111011', 'E' => '0010001', 'R' => '1000100'),
			8 => array('O' => '0110111', 'E' => '0001001', 'R' => '1001000'),
			9 => array('O' => '0001011', 'E' => '0010111', 'R' => '1110100')
		);

		// UPC 2-Digit Parity Pattern => UPC_2dpp
		private $UPC_2dpp = array(
			0 => array('O', 'O'),
			1 => array('O', 'E'),
			2 => array('E', 'O'),
			3 => array('E', 'E')
		);

		// UPC 5-Digit Parity Pattern => UPC_5dpp
		private $UPC_5dpp = array(
			0 => array('E', 'E', 'O', 'O', 'O'),
			1 => array('E', 'O', 'E', 'O', 'O'),
			2 => array('E', 'O', 'O', 'E', 'O'),
			3 => array('E', 'O', 'O', 'O', 'E'),
			4 => array('O', 'E', 'E', 'O', 'O'),
			5 => array('O', 'O', 'E', 'E', 'O'),
			6 => array('O', 'O', 'O', 'E', 'E'),
			7 => array('O', 'E', 'O', 'E', 'O'),
			8 => array('O', 'E', 'O', 'O', 'E'),
			9 => array('O', 'O', 'E', 'O', 'E')
		);

		public function ean13_barcode ($x, $y, $message, $supplemental = null, $width = null, $height = null, $angle = 0, $color = 0) {
			$mess = $message;
			$message = str_replace('-', '', $message);
			$checksum = $this->ean13_checksum($message);
			$supp_coded = false;

			// Left Hand Encoding
			$lh_coded = '';
			foreach (str_split(substr($message, 1, 6)) as $pos => $val) {
				$lh_coded .= $this->EAN_cet[$val][$this->EAN_pet[$message[0]][$pos]];
			}
			$lh_coded = wordwrap($lh_coded, 1, ' ', true);

			// Right Hand Encoding
			$rh_coded = '';
			foreach (str_split(substr($message, 7, 5) . $checksum) as $pos => $val) {
				$rh_coded .= $this->EAN_cet[$val]['R'];
			}
			$rh_coded = wordwrap($rh_coded, 1, ' ', true);

			// Supplemental Encoding
			if (strlen($supplemental) == 2 or strlen($supplemental) == 5) {
				$supp_checksum = $this->upc_checksum($supplemental);
				$supp_coded = '1011';
				foreach(str_split($supplemental) as $pos => $val) {
					if (strlen($supplemental) == 2) {
						$this->ean13_width = 94.96;
						$supp_coded .= $this->EAN_cet[$val][$this->UPC_2dpp[$supp_checksum][$pos]] . '01';
					} elseif (strlen($supplemental) == 5) {
						$this->ean13_width = 113.4;
						$supp_coded .= $this->EAN_cet[$val][$this->UPC_5dpp[$supp_checksum][$pos]] . '01';
					}
				}
				$supp_coded = wordwrap(substr($supp_coded, 0 , -2), 1, ' ', true);
			}

			// Graphing
			if (empty($width)) $width = $this->ean13_width / $this->k;
			if (empty($height)) $height = $this->ean13_height / $this->k;
			if (empty($angle)) $angle = 0;
			if (empty($color)) $color = 0;

			$this->ean13_move($x, $y, $angle, $width * $this->k / $this->ean13_width, $height * $this->k / $this->ean13_height);

			$this->SetFont('Helvetica', '', $this->ean13_textheight);
			$this->SetDrawColor($color);
			$this->SetTextColor($color);
			//$this->SetLineWidth($this->ean13_barwidth / $this->k);
			$this->_out(sprintf('%.2f w', $this->ean13_barwidth));

			$this->ean13_sentinel('0 1 0 1');
			$this->ean13_sentinel('45 0 1 0 1 0');
			$this->ean13_sentinel('92 1 0 1');
			$this->ean13_bars('3 ' . $lh_coded);
			$this->ean13_bars('50 ' . $rh_coded);

			$this->ean13_put_text($this->ean13_height - $this->ean13_ypos + 7, '-7 0 ' . $message[0]);
			$this->ean13_put_text($this->ean13_height - $this->ean13_ypos + 7, '3 7 ' . wordwrap(substr($message, 1, 6), 1, ' ', true));
			$this->ean13_put_text($this->ean13_height - $this->ean13_ypos + 7, '50 7 ' . wordwrap(substr($message, 7, 5) . $checksum, 1, ' ', true));

			if ($supp_coded) {
				$this->ean13_bars_supp('105 ' . $supp_coded);
				$this->ean13_put_text($this->ean13_height - $this->ean13_ypos - $this->ean13_barheight + 6, '107 9 ' . wordwrap($supplemental, 1, ' ', true));
				$mess .= '-' . $checksum . '-' . $supplemental;
			} else {
				$mess .= '-' . $checksum;
			}

			$this->ean13_unmove($x, $y, $angle, $width * $this->k / $this->ean13_width, $height * $this->k / $this->ean13_height);

			return($mess);
		}

		private function ean13_move ($x = 0, $y = 0, $angle = 0, $scale_x = 1, $scale_y = 1) {
			// Translate
			$this->_out(sprintf('%.3f %.3f %.3f %.3f %.3f %.3f cm', 1, 0, 0, 1, $x * $this->k, -$y * $this->k));
			// Rotate
			$cos = cos(deg2rad($angle));
			$sin = sin(deg2rad($angle));
			$this->_out(sprintf('%.3f %.3f %.3f %.3f %.3f %.3f cm', $cos, $sin, -$sin, $cos, ($this->h * $this->k) * $sin, ($this->h * $this->k) * (1 - $cos)));
			// Scale
			$this->_out(sprintf('%.3f %.3f %.3f %.3f %.3f %.3f cm', $scale_x, 0, 0, $scale_y, 0, $this->h *$this->k * (1 - $scale_y)));
		}

		private function ean13_unmove ($x = 0, $y = 0, $angle = 0, $scale_x = 1, $scale_y = 1) {
			// Un-Scale
			$this->_out(sprintf('%.3f %.3f %.3f %.3f %.3f %.3f cm', 1 / $scale_x, 0, 0, 1 / $scale_y, 0, $this->h * $this->k * (1 - 1 / $scale_y)));
			// Un-Rotate
			$cos = cos(deg2rad(-$angle));
			$sin = sin(deg2rad(-$angle));
			$this->_out(sprintf('%.3f %.3f %.3f %.3f %.3f %.3f cm', $cos, $sin, -$sin, $cos, ($this->h * $this->k) * $sin, ($this->h * $this->k) * (1 - $cos)));
			// Un-Translate
			$this->_out(sprintf('%.3f %.3f %.3f %.3f %.3f %.3f cm', 1, 0, 0, 1, -$x * $this->k, $y * $this->k));
		}

		public function ean13_checksum ($message) {
			$checksum = 0;
			foreach (str_split(strrev($message)) as $pos => $val) {
				$checksum += $val * (3 - 2 * ($pos % 2));
			}
			return ((10 - ($checksum % 10)) % 10);
		}

		public function upc_checksum ($supplemental) {
			if (strlen($supplemental) == 2) {
				$supp_checksum = $supplemental % 4;
			} elseif (strlen($supplemental) == 5) {
				$supp_checksum = 0;
				foreach (str_split(strrev($supplemental)) as $pos => $val ) {
					$supp_checksum += $val * (3 + 6 * ($pos % 2));
				}
				$supp_checksum = $supp_checksum % 10;
			}
			return $supp_checksum;
		}

		private function ean13_sentinel ($data) {
			$data = explode(' ', $data);
			$pos_init = array_shift($data);
			foreach ($data as $rpos => $val) {
				if ($val) {
					$x = ($this->ean13_xpos + $pos_init + $rpos) * $this->ean13_barwidth + $this->ean13_barwidth / 2;
					$y0 = $this->ean13_height - $this->ean13_ypos - $this->ean13_barheight + $this->ean13_barwidth / 2;
					$y1 = $this->ean13_height - $this->ean13_ypos - $this->ean13_barheight - $this->ean13_barwidth / 2 + $this->ean13_senheight;
					$this->_out(sprintf('%.2f %.2f m %.2f %.2f l S', $x, ($this->h * $this->k) - $y0, $x, ($this->h * $this->k) - $y1));
				}
			}
		}

		private function ean13_bars ($data) {
			$data = explode(' ', $data);
			$pos_init = array_shift($data);
			foreach ($data as $rpos => $val) {
				if ($val) {
					$x = ($this->ean13_xpos + $pos_init + $rpos) * $this->ean13_barwidth + $this->ean13_barwidth / 2;
					$y0 = $this->ean13_height - $this->ean13_ypos - $this->ean13_barheight + $this->ean13_barwidth / 2;
					$y1 = $this->ean13_height - $this->ean13_ypos - $this->ean13_barwidth / 2;
					$this->_out(sprintf('%.2f %.2f m %.2f %.2f l S', $x, ($this->h * $this->k) - $y0, $x, ($this->h * $this->k) - $y1));
				}
			}
		}

		private function ean13_bars_supp ($data) {
			$data = explode(' ', $data);
			$pos_init = array_shift($data);
			foreach ($data as $rpos => $val) {
				if ($val) {
					$x = ($this->ean13_xpos + $pos_init + $rpos) * $this->ean13_barwidth + $this->ean13_barwidth / 2;
					$y0 = $this->ean13_height - $this->ean13_ypos - $this->ean13_barheight + $this->ean13_barwidth / 2 + 7;
					$y1 = $this->ean13_height - $this->ean13_ypos - $this->ean13_barheight - $this->ean13_barwidth / 2 + $this->ean13_senheight;
					$this->_out(sprintf('%.2f %.2f m %.2f %.2f l S', $x, ($this->h * $this->k) - $y0, $x, ($this->h * $this->k) - $y1));
				}
			}
		}

		private function ean13_put_text ($y, $data) {
			$data = explode(' ', $data);
			$pos_init = array_shift($data);
			$inc = array_shift($data);
			foreach ($data as $rpos => $val) {
				$x = ($this->ean13_xpos + $pos_init + $rpos * $inc) * $this->ean13_barwidth + $this->ean13_barwidth / 2;
				//$this->Text($x, $y, $val);
				$s = sprintf('BT %.2f %.2f Td (%s) Tj ET', $x, ($this->h * $this->k) - $y, $this->_escape($val));
				if($this->ColorFlag) $s = 'q '. $this->TextColor . ' ' . $s . ' Q';
				$this->_out($s);
			}
		}
	}
