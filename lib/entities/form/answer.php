<?php

namespace Uplab\Core\Entities\Form;


use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


/**
 * Class Answer
 *
 * @package Uplab\Core\Entities\Form
 */
class Answer extends EO_Answer
{

	private $isMultipleAnswer;

	public function isMultipleAnswer()
	{
		if ($this->getFieldType()) {
			$fieldType = $this->getFieldType();
		} else {
			$fieldType = $this->fillFieldType();
		}

		if (!isset($this->isMultipleAnswer)) {
			$this->isMultipleAnswer = in_array($fieldType, [
				"checkbox",
				"multiselect",
				"dropdown",
				"radio",
			]);
		}

		return $this->isMultipleAnswer;
	}

}
