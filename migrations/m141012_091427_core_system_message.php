<?php

use yii\db\Schema;
use yii\db\Migration;

class m141012_091427_core_system_message extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%core_system_message}}', [
            'id' => Schema::TYPE_BIGPK,
            'type' => Schema::TYPE_STRING.'(10)',
            'title' => Schema::TYPE_STRING . ' NOT NULL',
            'message' => Schema::TYPE_STRING,
            'data' => Schema::TYPE_TEXT,
            'created_at' => Schema::TYPE_INTEGER . ' NOT NULL',
            'created_by' => Schema::TYPE_INTEGER,
        ], $tableOptions);

        $this->createIndex($this->db->tablePrefix .'core_system_message_created_at_ix', '{{%core_system_message}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropIndex($this->db->tablePrefix .'core_system_message_created_at_ix', '{{%core_system_message}}');
        $this->dropTable('{{%core_system_message}}');
    }
}
