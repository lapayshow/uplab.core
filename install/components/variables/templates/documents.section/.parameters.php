<?

use Uplab\Core\Components\TemplateBlock;


$set = array(
	'FILES' => array('NAME' => 'Пути к документам', 'MULTIPLE' => 'Y', 'ROWS' => 'Y'),
	'NAMES' => array('NAME' => 'Названия документов', 'MULTIPLE' => 'Y', 'ROWS' => 'Y'),
);


CBitrixComponent::includeComponentClass("uplab.core:template.block");
$arTemplateParameters = TemplateBlock::initParameters($set);
