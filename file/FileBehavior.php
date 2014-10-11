<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\file;

use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\web\UploadedFile;
use Yii;

/**
 * FileBehavior
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger\core
 *
 */
class FileBehavior extends Behavior
{
    /**
     * @var ActiveRecord the owner of this behavior
     */
    public $owner;

    /**
     * @var string Base path for uploading
     * Defaults to '@static/uploads/[model_table]'
     */
    public $basePath;

    /**
     * @var string Base url for base path
     * Defaults to '@staticUrl/uploads/[model_table]'
     */
    public $baseUrl;

    /**
     * @var string Attribute for writing file URL
     */
    public $attribute = null;

    /**
     * @var null|String|callable generator for filename
     *
     * If its null
     * Can be an attribute name for transliterate.
     * The signature of the function should be the following: `function ($model, $file, $basename, $extension)`.
     */
    public $nameGenerator = null;

    /**
     * @var bool overwrite if file already exists
     */
    public $overwriteFile = true;

    /**
     * @var bool delete old file if it exists
     */
    public $deleteOldFile = true;

    /**
     * @var bool file will be deleted with model deletion
     */
    public $deleteFileWithModel = true;

    /**
     * @var UploadedFile
     */
    public $file = null;
    /**
     * @var string old value of attribute
     */
    protected $oldValue = null;

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function init()
    {
        parent::init();

        if (is_null($this->attribute)) {
            throw new Exception('Attribute name must be set.');
        }
    }

    public function attach($owner)
    {
        parent::attach($owner);
        $folder = Inflector::tableize($this->owner->className()).'/';

        if (!$this->basePath) {
            $this->basePath = '@static/uploads/'.$folder;
        }
        if (!$this->baseUrl) {
            $this->basePath = '@staticUrl/uploads/'.$folder;
        }
    }

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',

            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',

            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * After find event.
     */
    public function afterFind()
    {
        $this->oldValue = $this->owner->{$this->attribute};
    }

    /**
     * Before validate event. Loading UploadFile instance and set it to attribute
     */
    public function beforeValidate()
    {
        if (is_null($this->file)) {
            $this->file = UploadedFile::getInstance($this->owner, $this->attribute);
        }

        if ($this->file instanceof UploadedFile && !$this->file->hasError) {
            $this->owner->{$this->attribute} = $this->file;
        } else {
            $this->file = null;

            //Delete old file if attribute is changed
            if (($this->owner->{$this->attribute} != $this->oldValue) && $this->deleteOldFile) {
                $this->deleteFiles();
            }
        }
    }

    /**
     * Before save event.
     */
    public function beforeSave()
    {
        if ($this->file instanceof UploadedFile) {
            if ($this->deleteOldFile) {
                $this->deleteFiles();
            }
            $this->oldValue = null;
            $this->owner->{$this->attribute} = $this->file->baseName . '.' . $this->file->extension;
        }
    }

    /**
     * After save event.
     * @throws Exception
     */
    public function afterSave()
    {
        if ($this->file instanceof UploadedFile) {

            $this->beforeFileSaving();

            $name = $this->generateName();
            $path = $this->getFilePathInternal($name);
            $url = $this->getFileUrlInternal($name);

            $dir = dirname($path);
            if (!FileHelper::createDirectory($dir)) {
                throw new Exception('Directory "'.$dir.'" creation error.');
            }

            if (!$this->file->saveAs($path)) {
                throw new Exception('File saving error.');
            }

            $this->afterFileSaving();

            $this->owner->setOldAttribute($this->attribute, $url);

            if (!$this->owner->getDb()->createCommand()->update(
                $this->owner->tableName(),
                [
                    $this->attribute => $url
                ],
                $this->owner->getPrimaryKey(true)
            )->execute()) {
                throw new Exception('Model update failed.');
            }
        }
    }

    /**
     * Event before file saving
     */
    public function beforeFileSaving()
    {

    }

    /**
     * Event
     */
    public function afterFileSaving()
    {

    }

    /**
     * Return path to file in attribute
     * @param $attribute string attribute name
     * @return string|null
     */
    public function getFilePath($attribute)
    {
        foreach ($this->owner->behaviors as $behavior) {
            if ($behavior instanceof static && $behavior->attribute == $attribute) {
                /**
                 * @var behavior static
                 */
                return str_replace(
                    \Yii::getAlias($behavior->baseUrl),
                    \Yii::getAlias($behavior->basePath),
                    $behavior->oldValue);
            }
        }

        return null;
    }

    /**
     * Returns file path
     * @param null|string $fileName
     * @return bool|string
     */
    protected function getFilePathInternal($fileName=null)
    {
        return \Yii::getAlias(
            rtrim($this->basePath, '/'). '/'.
            ltrim($fileName ?: $this->generateName(), '/')
        );
    }

    /**
     * Returns file url
     * @param null $fileName
     * @return bool|string
     */
    protected function getFileUrlInternal($fileName=null)
    {
        return \Yii::getAlias(
            rtrim($this->baseUrl, '/'). '/'.
            ltrim($fileName ?: $this->generateName(), '/')
        );
    }

    /**
     * Generate file name
     * @return string
     */
    protected function generateName()
    {
        if ($this->file instanceof UploadedFile) {
            $extension = strtolower($this->file->extension);
            //$baseName = mb_substr($this->file->baseName, 0, -strlen($extension)+1);
            $baseName = $this->file->baseName;
            $name = null;

            if ($this->nameGenerator) {
                if ($this->nameGenerator instanceof \Closure) {
                    $name = call_user_func($this->nameGenerator, $this->owner, $this, $baseName, $extension);
                    if ($name) {
                        return $name;
                    }
                } elseif ($this->owner->hasAttribute($this->nameGenerator)) {
                    $baseName = $this->owner->{$this->nameGenerator};
                }
            }

            return Inflector::slug($baseName).'.'.$extension;
        }
        return null;
    }

    /**
     * Delete old files
     */
    protected function deleteFiles()
    {
        if ($this->oldValue) {
            $filePath = str_replace(
                \Yii::getAlias($this->baseUrl),
                \Yii::getAlias($this->basePath),
                $this->oldValue);

            try {
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }
            catch (\Exception $e) {

            }
            $this->oldValue = null;
        }
    }
}