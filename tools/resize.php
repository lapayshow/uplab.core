<?
/** @noinspection PhpIncludeInspection */

use Bitrix\Main\Application;


require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

// dump($_REQUEST);

try {

	if (!CTopPanel::shouldShowPanel()) throw new Exception("Access denied");
	$picture = \Uplab\Core\Helper::getFileInfo($_REQUEST["p"]);
	if (!is_readable($picture["PATH"] ?? "") || !($picture["IS_SVG"] ?? false)) {
		throw new Exception("Wrong input file");
	}
	if (!class_exists("Imagick")) throw new Exception("Imagick is not installed");

	$imagick = new Imagick();
	$imagick->setBackgroundColor(new ImagickPixel("transparent"));
	$imagick->readImage($picture["PATH"]);

	$size = 180;
	if (isset($_REQUEST["sz"])) $size = min($size, (int)$_REQUEST["sz"]);
	$res = $imagick->getImageResolution();
	$xRatio = $res['x'] / $imagick->getImageWidth();
	$yRatio = $res['y'] / $imagick->getImageHeight();
	$imagick->removeImage();
	$imagick->setResolution($size * $yRatio, $size * $yRatio);

	$imagick->readImage($picture["PATH"]);

	// $imagick->resizeImage(120, 120, Imagick::FILLRULE_EVENODD, 1, true);
	$imagick->setImageFormat("png32");

	// dump($imagick);

	header('Content-Type: image/png');
	echo $imagick;

	$imagick->clear();
	$imagick->destroy();

} catch (Exception $e) {

	die($e->getMessage());

}

