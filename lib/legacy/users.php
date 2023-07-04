<?

namespace Uplab\Core\Legacy;


use Bitrix\Main\Data\Cache;
use Bitrix\Main\UserTable;
use CGroup;
use CUser;


class Users
{
	protected static $validate_keys = array(
		"NAME",
		"LAST_NAME",
		"EMAIL",
		// "PERSONAL_PHONE",
		// "WORK_COMPANY",
		// "WORK_POSITION",
		// "UF_BUSINESS_AREA",
		// "UF_BUSINESS_PROFILE"
	);

	public static function getByID($id = false, $param = [])
	{
		if (!$id) {
			$id = $GLOBALS["USER"]->GetID();
		}

		if (empty($id)) return false;

		$param["filter"]["ID"] = $id;
		$res = self::getList($param);
		if (!array_key_exists($id, $res)) return false;


		return $res[$id];
	}

	public static function getList($param, &$users = false)
	{
		$_select = array(
			"ID",
			"NAME",
			"LAST_NAME",
			"SECOND_NAME",
			"LOGIN",
			"EMAIL",
		);
		$filter = array();
		$short = false;
		$limit = 30;
		extract($param);

		if (empty($select)) {
			$select = $_select;
		} else {
			$select = array_merge((array)$_select, (array)$select);
		}

		if (!empty($search)) {
			$search = "%{$search}%";
			$filter = [$filter];
			$filter[] = array(
				"LOGIC" => "OR",
				["NAME" => $search],
				["LAST_NAME" => $search],
				["LOGIN" => $search],
			);
		}

		$res = UserTable::getList(array(
			"select" => $select,
			"filter" => $filter,
			"limit"  => $limit,
		));

		if (!$users) {
			$users = array();
		}

		while ($user = $res->fetch()) {
			self::prepareItem($user, $short);
			if ($short) {
				$users[] = $user;
			} else {
				$users[$user["ID"]] = $user;
			}
		}

		return $users;
	}

	public static function checkRights($force = false)
	{
		global $USER;

		return !(!defined('AUTH_PAGE') || $force) ||
			!(!$USER->isAuthorized() || !in_array(6, $USER->GetUserGroupArray()));
	}

	public static function getGroups($force = false)
	{
		$arData = array();
		$cacheId = SITE_ID . "/user_groups/";
		$cacheDir = $cacheId;
		$cacheTime = $force === true ? 0 : 3600000;
		$cache = Cache::createInstance();
		if ($cache->initCache($cacheTime, $cacheId, $cacheDir)) {
			$arData = $cache->getVars();
		} elseif ($cache->startDataCache()) {

			$res = CGroup::GetList(
				$by = "c_sort",
				$order = "asc"
			);
			while ($group = $res->Fetch()) {
				$arData[$group["ID"]] = array_intersect_key(
					$group,
					array_flip(["ID", "NAME", "STRING_ID", "C_SORT"])
				);
			}

			$cache->endDataCache($arData);
		}

		return $arData;
	}

	/**
	 * Если в качестве аргумента передан массив, возвращает массив
	 * Если строка, то возвращает строку
	 *
	 * @param $codes
	 *
	 * @return array|bool|mixed
	 */
	public static function getGroupsByCodes($codes)
	{
		if (empty($codes)) return false;

		$isArray = is_array($codes);
		$codes = (array)$codes;
		$groups = self::getGroups();
		$res = array();

		foreach ($groups as $group) {
			if (in_array($group["STRING_ID"], $codes)) {
				$res[$group["ID"]] = $group;
			}
		}

		return $isArray ? $res : current($res);
	}

	public static function getUserGroups($userID = false)
	{
		$groupData = self::getGroups();
		$groupID = CUser::GetUserGroup(intval($userID) ?: $GLOBALS["USER"]->GetID());

		return array_intersect_key(
			$groupData,
			array_flip($groupID)
		);
	}

	/**
	 * Может принимать на вход массив кодов групп пользователей
	 * Проверяет, состоит ли пользователь в этих группах
	 *
	 * @param array|string $search
	 * @param int|bool     $userID Необязательное поле
	 *
	 * @return mixed
	 */
	public static function isInGroup($search, $userID = false)
	{
		$groups = self::getUserGroups($userID);

		return array_intersect(
			(array)$search,
			array_map(function ($v) {
				return $v["STRING_ID"];
			}, $groups)
		);
	}

	public static function isInGroupID($id, $userID = false)
	{
		$groups = self::getUserGroups($userID);

		return array_search(
			$id, array_map(function ($v) {
				return $v["ID"];
			}, $groups)
		);
	}

	public static function validateArray($arUser, &$errorKey)
	{
		foreach (self::$validate_keys as $key) {
			if (empty($arUser[$key])) {
				// echo $key, " ", $arUser[$key];
				$errorKey = $key;

				return false;
			}
		}

		return true;
	}

	public static function getRequired()
	{
		return self::$validate_keys;
	}

	public static function prepareItem(&$user, $short = false)
	{
		$user = array_map("trim", $user);

		$name = trim(implode(" ", [
			$user["NAME"],
			$user["LAST_NAME"],
		]));

		if (empty($name)) {
			$name = $user["LOGIN"];
		}

		if ($short) {
			$users[] = array(
				"id"   => $user["ID"],
				"text" => $name,
				// "user" => $user
			);
		} else {
			$fullName = implode(" ", array_filter([
				$user["LAST_NAME"],
				$user["NAME"],
				$user["SECOND_NAME"],
			]));

			$user["FIRST_NAME"] = $user["NAME"];
			$user["NAME"] = $name;
			$user["FULL_NAME"] = $fullName;

			$user["INITIALS"] = array_filter([
				substr($user["FIRST_NAME"], 0, 1),
				substr($user["SECOND_NAME"], 0, 1),
				$user["LAST_NAME"],
			]);

			if (count($user["INITIALS"]) == 3) {
				$user["~INITIALS"] = implode(".&nbsp;", $user["INITIALS"]);
			} elseif ($user["LAST_NAME"]) {
				$user["~INITIALS"] = $user["LAST_NAME"];
			} else {
				$user["~INITIALS"] = $user["LOGIN"];
			}

			$users[$user["ID"]] = $user;
		}
	}

}
