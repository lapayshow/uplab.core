<?

use Uplab\Core\Components\TemplateBlock;


$set = array(
    'TITLE'     => 'Наименование, заголовок',
    'FILES'     => array('NAME'=>'Пути к изображениям','MULTIPLE'=>'Y','ROWS'=>'Y'),
    'SRC_TPL'   => array('NAME'=>'Шаблон пути', 'ROWS'=>'1'),
    'TPL_START' => array('NAME'=>'Первый элемент по шаблону', 'ROWS'=>'1', 'DEFAULT'=>'1'),
    'TPL_END'   => array('NAME'=>'Последний элемент по шаблону', 'ROWS'=>'1', 'DEFAULT'=>'10')
);


CBitrixComponent::includeComponentClass("uplab.core:template.block");
$arTemplateParameters = TemplateBlock::initParameters($set);