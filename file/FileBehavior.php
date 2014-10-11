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
use yii\web\UploadedFile;

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
    const EVENT_AFTER_FILE_SAVE = 'afterFileSave';

    /**
     * @var ActiveRecord the owner of this behavior
     */
    public $owner;

    /**
     * @var string Attribute for writing file URL
     */
    public $attribute = null;

    /**
     * @var string Base path for uploading
     */
    public $basePath = '@static/uploads';

    /**
     * @var string Base url for base path
     */
    public $baseUrl = '@staticUrl/uploads';

    /**
     * @var bool
     */
    public $generateName = false;

    /**
     * @var null|String|callable generator for filename
     *
     * Can be an attribute name for transliterate.
     * The signature of the function should be the following: `function ($model, $file, $basename, $extension)`.
     */
    public $nameGenerator = null;

    /**
     * @var bool overwrite if file already exists
     */
    public $overwrite = true;

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
    protected $file = null;
    /**
     * @var string old value of attribute
     */
    protected $oldValue = null;

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
        if (is_null($this->attribute)) {
            throw new Exception('Attribute name must be set.');
        }
        $this->oldValue = $this->owner->{$this->attribute};
    }

    /**
     * Before validate event. Loading UploadFile instance and set it to attribute
     */
    public function beforeValidate()
    {
        if (is_null($this->attribute)) {
            throw new Exception('Attribute name must be set.');
        }

        if (is_null($this->file)) {
            $this->file = UploadedFile::getInstance($this->owner, $this->attribute);
        }

        if ($this->file instanceof UploadedFile && !$this->file->hasError) {
            $this->owner->{$this->attribute} = $this->file;
        } else {
            $this->file = null;
        }
    }

    /**
     * Before save event.
     */
    public function beforeSave()
    {
        if ($this->file instanceof UploadedFile) {
            if (!$this->owner->isNewRecord) {
                if ($this->deleteOldFile) {
                    $this->deleteFiles();
                }
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

            $path = $this->getFilePathInternal();
            $url = $this->getFileUrlInternal();

            $dir = pathinfo($path, PATHINFO_DIRNAME);
            if (!FileHelper::createDirectory($dir)) {
                throw new Exception('Directory "'.$dir.'" creation error.');
            }

            if (!$this->file->saveAs($path)) {
                throw new Exception('File saving error.');
            }
            $this->owner->trigger(static::EVENT_AFTER_FILE_SAVE);
        }
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
            rtrim($this->basePath, '/').
            ($fileName ?: $this->generateFileName())
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
            rtrim($this->baseUrl, '/').
            ($fileName ?: $this->generateFileName())
        );
    }

    /**
     * Generate file name
     * @return string
     */
    protected function generateFileName()
    {
        if ($this->file instanceof UploadedFile) {

            $extension = strtolower($this->file->extension);
            $baseName = mb_substr($this->file->baseName, 0, -strlen($extension)+1);


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