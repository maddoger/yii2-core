<?php

use yii\db\Schema;
use yii\db\Migration;

class m141012_090751_core_config extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%core_config}}', [
            'class' => Schema::TYPE_STRING . ' NOT NULL',
            'data' => Schema::TYPE_STRING . ' NOT NULL',
            'created_at' => Schema::TYPE_INTEGER,
            'created_by' => Schema::TYPE_INTEGER,
            'updated_at' => Schema::TYPE_INTEGER,
            'updated_by' => Schema::TYPE_INTEGER,
        ], $tableOptions);

        $this->addPrimaryKey('pk', '{{%core_config}}', 'class');
    }

    public function safeDown()
    {
        $this->dropPrimaryKey('PRIMARY', '{{%core_config}}');
        $this->dropTable('{{%core_config}}');
    }
}
