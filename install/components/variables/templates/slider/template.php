<?
use Uplab\Core\Helper;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @var array    $arParams
 * @var array    $arResult
 */

$arImages = [];
for ($i = 0; !empty($arParams["FILE{$i}_SRC"]); $i++) {

	$pic = Helper::getFileInfo($arParams["FILE{$i}_SRC"]);
	$pic["DESCRIPTION"] = $arParams["FILE{$i}_TEXT"] ?: $pic["DESCRIPTION"];

	$destinationSrc = "/upload/tmp/" . md5($pic) . "." . $pic["EXT"];
	$destinationPath = "{$_SERVER["DOCUMENT_ROOT"]}{$destinationSrc}";

	if ($pic && $pic["PATH"]) {
		CFile::ResizeImageFile(
			$pic["PATH"],
			$destinationPath,
			[
				"width"  => 800,
				"height" => 600,
			],
			BX_RESIZE_IMAGE_EXACT
		//, false
		//, 80
		);

		$pic["PATH"] = $destinationPath;
		$pic["SRC"] = str_replace($_SERVER["DOCUMENT_ROOT"], "", $destinationPath);
		$arImages[] = $pic;
	}

}

if (!file_exists($destinationPath)) {
	unset($destinationSrc);
}

$arImages = array_filter($arImages);
?>


<? if (!empty($arImages)): ?>

	<div class="slider-wrap">

		<div class="slider"
		     data-slick=""
		     data-depend-on-tab
		>

			<? foreach ($arImages as $item): ?>
				<div class="slider__item">
					<img src="<?= $item["SRC"] ?>" alt="<?= $item["DESCRIPTION"] ?>">
				</div>
			<? endforeach; ?>

		</div>

		<div class="arrow arrow--left"><</div>

		<div class="arrow arrow--right">></div>

	</div>
<? endif; ?>
