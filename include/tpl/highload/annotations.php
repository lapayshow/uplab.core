<?php

use Bitrix\Main\Authentication\Context as AuthContext;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\Values;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */



/*
ORMENTITYANNOTATION:__Entity__Table
Данный код не должен исполняться
*/



/**
 * EO___Entity__
 *
 * @see \__Entity__Table
 *
 * Custom methods:
 * ---------------
 *
 * @method \int getId()
 * @method \EO___Entity__ setId(\int $id)
 * @method bool hasId()
 * @method bool isIdFilled()
 * @method bool isIdChanged()
 * @method \int getUfSort()
 * @method \EO___Entity__ setUfSort(\int $ufSort)
 * @method bool hasUfSort()
 * @method bool isUfSortFilled()
 * @method bool isUfSortChanged()
 * @method \int remindActualUfSort()
 * @method \int requireUfSort()
 * @method \EO___Entity__ resetUfSort()
 * @method \EO___Entity__ unsetUfSort()
 * @method \int fillUfSort()
 * @method \int getUfDef()
 * @method \EO___Entity__ setUfDef(\int $ufDef)
 * @method bool hasUfDef()
 * @method bool isUfDefFilled()
 * @method bool isUfDefChanged()
 * @method \int remindActualUfDef()
 * @method \int requireUfDef()
 * @method \EO___Entity__ resetUfDef()
 * @method \EO___Entity__ unsetUfDef()
 * @method \int fillUfDef()
 * @method \int getUfFile()
 * @method \EO___Entity__ setUfFile(\int $ufFile)
 * @method bool hasUfFile()
 * @method bool isUfFileFilled()
 * @method bool isUfFileChanged()
 * @method \int remindActualUfFile()
 * @method \int requireUfFile()
 * @method \EO___Entity__ resetUfFile()
 * @method \EO___Entity__ unsetUfFile()
 * @method \int fillUfFile()
 * @method \string getUfName()
 * @method \EO___Entity__ setUfName(\string $ufName)
 * @method bool hasUfName()
 * @method bool isUfNameFilled()
 * @method bool isUfNameChanged()
 * @method \string remindActualUfName()
 * @method \string requireUfName()
 * @method \EO___Entity__ resetUfName()
 * @method \EO___Entity__ unsetUfName()
 * @method \string fillUfName()
 * @method \string getUfXmlId()
 * @method \EO___Entity__ setUfXmlId(\string $ufXmlId)
 * @method bool hasUfXmlId()
 * @method bool isUfXmlIdFilled()
 * @method bool isUfXmlIdChanged()
 * @method \string remindActualUfXmlId()
 * @method \string requireUfXmlId()
 * @method \EO___Entity__ resetUfXmlId()
 * @method \EO___Entity__ unsetUfXmlId()
 * @method \string fillUfXmlId()
 * @method \string getUfLink()
 * @method \EO___Entity__ setUfLink(\string $ufLink)
 * @method bool hasUfLink()
 * @method bool isUfLinkFilled()
 * @method bool isUfLinkChanged()
 * @method \string remindActualUfLink()
 * @method \string requireUfLink()
 * @method \EO___Entity__ resetUfLink()
 * @method \EO___Entity__ unsetUfLink()
 * @method \string fillUfLink()
 * @method \string getUfDescription()
 * @method \EO___Entity__ setUfDescription(\string $ufDescription)
 * @method bool hasUfDescription()
 * @method bool isUfDescriptionFilled()
 * @method bool isUfDescriptionChanged()
 * @method \string remindActualUfDescription()
 * @method \string requireUfDescription()
 * @method \EO___Entity__ resetUfDescription()
 * @method \EO___Entity__ unsetUfDescription()
 * @method \string fillUfDescription()
 * @method \string getUfFullDescription()
 * @method \EO___Entity__ setUfFullDescription(\string $ufFullDescription)
 * @method bool hasUfFullDescription()
 * @method bool isUfFullDescriptionFilled()
 * @method bool isUfFullDescriptionChanged()
 * @method \string remindActualUfFullDescription()
 * @method \string requireUfFullDescription()
 * @method \EO___Entity__ resetUfFullDescription()
 * @method \EO___Entity__ unsetUfFullDescription()
 * @method \string fillUfFullDescription()
 *
 * Common methods:
 * ---------------
 *
 * @property-read Entity $entity
 * @property-read array  $primary
 * @property-read int    $state @see \Bitrix\Main\ORM\Objectify\State
 * @property AuthContext $authContext
 * @method mixed get($fieldName)
 * @method mixed remindActual($fieldName)
 * @method mixed require ($fieldName)
 * @method bool has($fieldName)
 * @method bool isFilled($fieldName)
 * @method bool isChanged($fieldName)
 * @method \EO___Entity__ set($fieldName, $value)
 * @method \EO___Entity__ reset($fieldName)
 * @method \EO___Entity__ unset($fieldName)
 * @method void addTo($fieldName, $value)
 * @method void removeFrom($fieldName, $value)
 * @method void removeAll($fieldName)
 * @method Result delete()
 * @method void fill($fields = FieldTypeMask::ALL) flag or array of field names
 * @method mixed[] collectValues($valuesType = Values::ALL, $fieldsMask = FieldTypeMask::ALL)
 * @method AddResult|UpdateResult|Result save()
 * @method static \EO___Entity__ wakeUp($data)
 */
class EO___Entity__
{
	/* @var \__Entity__Table */
	static public $dataClass = '\__Entity__Table';

	public function __construct($setDefaultValues = true)
	{
	}
}


/**
 * EO___Entity___Collection
 *
 * Custom methods:
 * ---------------
 *
 * @method \int[] getIdList()
 * @method \int[] getUfSortList()
 * @method fillUfSort()
 * @method \int[] getUfDefList()
 * @method fillUfDef()
 * @method \int[] getUfFileList()
 * @method fillUfFile()
 * @method \string[] getUfNameList()
 * @method fillUfName()
 * @method \string[] getUfXmlIdList()
 * @method fillUfXmlId()
 * @method \string[] getUfLinkList()
 * @method fillUfLink()
 * @method \string[] getUfDescriptionList()
 * @method fillUfDescription()
 * @method \string[] getUfFullDescriptionList()
 * @method fillUfFullDescription()
 *
 * Common methods:
 * ---------------
 *
 * @property-read Entity $entity
 * @method void add(\EO___Entity__ $object)
 * @method bool has(\EO___Entity__ $object)
 * @method bool hasByPrimary($primary)
 * @method \EO___Entity__ getByPrimary($primary)
 * @method \EO___Entity__[] getAll()
 * @method bool remove(\EO___Entity__ $object)
 * @method void removeByPrimary($primary)
 * @method void fill($fields = FieldTypeMask::ALL) flag or array of field names
 * @method static \EO___Entity___Collection wakeUp($data)
 * @method Result save($ignoreEvents = false)
 * @method void offsetSet() ArrayAccess
 * @method void offsetExists() ArrayAccess
 * @method void offsetUnset() ArrayAccess
 * @method void offsetGet() ArrayAccess
 * @method void rewind() Iterator
 * @method \EO___Entity__ current() Iterator
 * @method mixed key() Iterator
 * @method void next() Iterator
 * @method bool valid() Iterator
 * @method int count() Countable
 */
class EO___Entity___Collection implements \ArrayAccess, \Iterator, \Countable
{
	/* @var \__Entity__Table */
	static public $dataClass = '\__Entity__Table';
}


/**
 * @method static EO___Entity___Query query()
 * @method static EO___Entity___Result getByPrimary($primary, array $parameters = array())
 * @method static EO___Entity___Result getById($id)
 * @method static EO___Entity___Result getList(array $parameters = array())
 * @method static EO___Entity___Entity getEntity()
 * @method static \EO___Entity__ createObject($setDefaultValues = true)
 * @method static \EO___Entity___Collection createCollection()
 * @method static \EO___Entity__ wakeUpObject($row)
 * @method static \EO___Entity___Collection wakeUpCollection($rows)
 */
class __Entity__Table extends \Bitrix\Main\ORM\Data\DataManager
{
}


/**
 * @method EO___Entity___Result exec()
 * @method \EO___Entity__ fetchObject()
 * @method \EO___Entity___Collection fetchCollection()
 */
class EO___Entity___Query extends \Bitrix\Main\ORM\Query\Query
{
}


/**
 * @method \EO___Entity__ fetchObject()
 * @method \EO___Entity___Collection fetchCollection()
 */
class EO___Entity___Result extends \Bitrix\Main\ORM\Query\Result
{
}


/**
 * @method \EO___Entity__ createObject($setDefaultValues = true)
 * @method \EO___Entity___Collection createCollection()
 * @method \EO___Entity__ wakeUpObject($row)
 * @method \EO___Entity___Collection wakeUpCollection($rows)
 */
class EO___Entity___Entity extends Entity
{
}
