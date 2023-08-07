<?
defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @var array    $arParams
 * @var array    $arResult
 */
?>
<ul class="list list_flat list_inline list_document list_document_bot">
	<? foreach ($arParams["FILES"] as $i => $file): ?>
		<?
		if (intval($file)) {
			$fileItem = CFile::GetFileArray($file);
		} else {
			$fileItem = array(
				"SRC"       => $file,
				"FILE_SIZE" => filesize($_SERVER["DOCUMENT_ROOT"] . $file),
			);
		}

		$fileItem['SIZE'] = CFile::FormatSize($fileItem['FILE_SIZE']);
		$fileItem['EXT'] = strtoupper(GetFileExtension($fileItem['SRC']));
		?>
		<li class="list__item">
			<a target="_blank" href="<?= $fileItem["SRC"] ?>" class="document-item">
			<span class="document-item__type">
				<!--suppress HtmlUnknownTarget -->
				<img src="/images/ico-pdf.png" alt="<?= $fileItem["EXT"] ?>">
			</span>
				<span class="document-item__main">
				<span class="document-item__title"><?= $arParams["NAMES"][$i] ?></span>
				<span class="document-item__size"><?= $fileItem["EXT"], ", ", $fileItem["SIZE"] ?></span>
			</span>
			</a>
			<span class="document-item__read">
			<a target="_blank" href="<?= $fileItem["SRC"] ?>">Читать документ</a>
		</span>
		</li>
	<? endforeach; ?>
</ul>