<?php

use yii\db\Migration;

class m161002_162238_create_table_images extends Migration
{
    public function up()
    {
        $this->createTable('images', [
            'id' => $this->primaryKey(),
            'model' => $this->string(32),
            'image' => $this->string(64),
            'title' => $this->string(255),
            'link' => $this->string(255),
            'model_id' => $this->integer(),
            'attribute' => $this->string(32),
        ]);
    }

    public function down()
    {
        $this->dropTable('images');
    }
}
