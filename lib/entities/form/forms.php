<?php

namespace Uplab\Core\Entities\Form;

use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


if (!class_exists(__NAMESPACE__ . "\EO_Form_Collection")) return;


/**
 * Class Form
 *
 * @package Uplab\Core\Entities\Form
 */
class Forms extends EO_Form_Collection
{

}
