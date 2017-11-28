<?php

namespace janisto\ycm\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * SlugBehavior
 *
 * Saves pretty url's from titles to be used as unique identifier's
 *
 * @author Chris de Kok <chris.de.kok@gmail.com>
 * @copyright Copyright (c) 2009 Chris de Kok. (http://mech7.net)
 *
 */
class Translit extends Behavior
{

    /**
     * The column name for the unqiue url
     */
    public $translit = 'dir';

    /**
     * The column name for the title
     */
    public $name = 'name';

    /**
     * Поле для группировки строк (транслит будет уникален только в пределах группы)
     * @var string
     */
    public $group;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_INSERT => 'saveHandler',
        ];
    }

    public function saveHandler($e)
    {
        $name = $e->sender->{$this->name};

        $translit = $this->getTranslit($name);

        $modelsQuery = $e->sender->find()->
        where(['like', $this->translit, $translit]);

        if ($e->sender->id) {
            $modelsQuery->andWhere(['!=', 'id', $e->sender->id]);
        }

        if ($this->group) {
            $group = $e->sender->{$this->group};
            $modelsQuery->andWhere([$this->group => $group]);
        }

        $models = $modelsQuery->all();

        $lastId = -1;
        $reg = '/^' . preg_quote($translit, '/') . '(-\d+)?$/i';

        foreach ($models as $model) {
            if (preg_match($reg, $model->{$this->translit}, $matches)) {
                if (!empty($matches[1])) {
                    $currentId = (int)preg_replace('/[^\d]+/', '', $matches[1]);
                } else {
                    $currentId = 0;
                }

                if ($currentId > $lastId) {
                    $lastId = $currentId;
                }
            }
        }

        if ($lastId >= 0) {
            $translit .= '-' . ($lastId + 1);
        }

        $e->sender->{$this->translit} = $translit;
        return true;
    }

    /**
     * метод возвращает транслит для переданной строки
     * @param string $name
     * @return string
     */
    protected function getTranslit($name)
    {
        $converter = array(
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'yo',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ь' => '',
            'ы' => 'y',
            'ъ' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'Yo',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Sch',
            'Э' => 'E',
            'Ю' => 'Yu',
            'Я' => 'Ya',
        );
        $str = strtr($name, $converter);
        $str = strtolower($str);
        $str = preg_replace('~[^-a-z0-9_]+~u', '-', $str);
        $str = preg_replace('~-+~u', '-', $str);
        $str = trim($str, "-");
        return $str;
    }
}
