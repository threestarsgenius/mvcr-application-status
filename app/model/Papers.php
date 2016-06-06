<?php
namespace App\Model;

use Nette;

class Papers extends Nette\Object {

	const DOCUMENT_LINK = 'http://www.mvcr.cz/soubor/prehled-k-23-5-2016.aspx';
	const FILE_PATH = '/tmp';
	const FILE_NAME = 'docchecker.xls';
	const SHEETNAME = 'Zaměstnanecká karta';
	const INPUT_FILE_NAME = self::FILE_PATH.'/'.self::FILE_NAME;

	public function check($paperNumber) {
		$this->_getLatestFile();
		$objReader = \PHPExcel_IOFactory::createReader('Excel5');
		$objReader->setLoadSheetsOnly(self::SHEETNAME);
		$objPHPExcel = $objReader->load(self::INPUT_FILE_NAME);
		$data = ['date', 'numbers' => []];
		foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
			$data['date'] = $worksheet->getCell('B5')->getValue() ? $worksheet->getCell('B5')->getValue() : 'No data';
			foreach ($worksheet->getRowIterator() as $row) {
				$cellIterator = $row->getCellIterator();
				foreach ($cellIterator as $cell) {
					if (preg_match('/'.$paperNumber.'/', $cell->getValue())) {
						preg_match('/[A-Z-0-9\/]+/', $cell->getValue(), $matches);
						$data['numbers'][] = $matches[0];
					}
				}
			}
		}
		return $data;
	}

	protected function _getLatestFile() {
		if ($this->_getCurrentFileSize() != $this->_getRemoteFileSize()) {
			return file_put_contents(self::FILE_PATH.'/'.self::FILE_NAME, fopen(self::DOCUMENT_LINK, 'r'));
		}
		return false;
	}

	protected function _getCurrentFileSize() {
		if (file_exists(self::FILE_PATH.'/'.self::FILE_NAME)) {
			return filesize(self::FILE_PATH.'/'.self::FILE_NAME);
		}
		return 0;
	}

	protected function _getRemoteFileSize() {
		$size = 0;
		$fileHeaders = $this->_getRemoteFileHeaders();
		if (preg_match('/Content-Length: (\d+)/', $fileHeaders, $matches)) {
			$size = isset($matches[1]) ? $matches[1] : 0;
		}
		return (int) $size;
	}

	protected function _getRemoteFileHeaders() {
		$file = self::DOCUMENT_LINK;

		$ch = curl_init($file);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

}
