<?

namespace Uplab\Core\Orm\Widget;


use CAdminListRow;
use DigitalWand\AdminHelper\Helper\AdminBaseHelper;
use DigitalWand\AdminHelper\Widget\OrmElementWidget;


class OrmSerializedWidget extends OrmElementWidget
{
	public function processEditAction()
	{
		parent::processEditAction();

		$linkedHelper = $this->getSettings("HELPER");

		$valueList = $this->getValue();
		$saveValueList = [];

		foreach ($valueList as $value) {
			$id = $value[$linkedHelper::pk()];
			if ($id) $saveValueList[] = $id;
		}
		$saveValueList = array_unique($saveValueList);
		sort($saveValueList);

		$this->setValue($saveValueList);
	}

	/**
	 * Генерирует HTML для поля в списке.
	 *
	 * @param CAdminListRow $row
	 * @param array         $data Данные текущей строки.
	 */
	public function generateRow(&$row, $data)
	{
		$row->AddViewField($this->getCode(), implode(", ", $this->getValue()));
	}

	public function getMultipleEditHtml()
	{
		?>
		<!--suppress ES6ConvertVarToLetConst, JSCheckFunctionSignatures -->
		<script>
            MultipleWidgetHelper.prototype._generateFieldTemplate = function (fieldTemplate, data) {
                var $containers = $('.fields-container');
                $containers.each(function (i, item) {
                    var $item = $(item);
                    $item.attr('data-container-index', i);
                });
                var containerIndex = this.$fieldsContainer.data('container-index');

                if (!data) {
                    data = {};
                }

                if (typeof data.field_id === 'undefined') {
                    data.field_id = 'new_' + containerIndex + '_' + this.fieldsCounter;
                } else {
                    data.field_id = containerIndex + '_' + data.field_id;
                }

                $.each(data, function (key, value) {
                    // Подставление значений переменных
                    fieldTemplate = fieldTemplate.replace(new RegExp('{{' + key + '}}', ['g']), value);
                });

                // Удаление из шаблона необработанных переменных
                fieldTemplate = fieldTemplate.replace(/{{.+?}}/g, '');

                return fieldTemplate;
            }
		</script>
		<?

		return parent::getMultipleEditHtml();
	}

	public function getMultipleValueReadonly()
	{
		return implode(", ", $this->getValue());
	}

	protected function getOrmElementData()
	{
		$refInfo = [];
		$value = $this->getValue();
		if (!$value) return [];

		// die("<pre>" . print_r(compact("value"), true));

		/** @var AdminBaseHelper $linkedHelper */
		$linkedHelper = $this->getSettings("HELPER");
		$linkedModel = $linkedHelper::getModel();

		$linkedParams = $this->getSettings("ORM_PARAMS");

		$params = array(
			"filter" => array_merge(
				[$linkedHelper::pk() => $value],
				(array)$linkedParams["filter"]
			),
		);
		if ($linkedParams["select"]) {
			$params["select"] = $linkedParams["select"];
		}
		$res = $linkedModel::getList($params);

		while ($item = $res->fetch()) {
			$refInfo[] = $item;
		}

		return $refInfo;
	}
}