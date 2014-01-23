<?php

namespace rusporting\core\behaviors;

use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use Yii;

/**
 * This behavior manage a file associated to a model attribute of a ActiveRecord.
 * It will write an uploaded file after saving a model if one is provided,
 * and delete it after removing the model from db.
 * The file name will be calculated with attribute(s) of the model.
 *
 * You can create multiple files from the one sended by the user, by using formats,
 * and use a processor to apply some process on each format.
 * Each format will create a file with a unique suffix in the file name.
 *
 * For an example, see example file.
 */
class FileUpload extends Behavior
{
	/**
	 * @var ActiveRecord the owner of this behavior.
	 */
	public $owner;

	/**
	 * @var UploadedFile
	 */
	public $file;

	/**
	 * the attribute filled by a file url. Must be set
	 */
	public $attribute;

	/**
	 * this attribute (or array of attributes) will determine a part of the file name. Default is model primary key(s).
	 */
	public $attributeForName;

	/**
	 * @var array Scenarios for adding validation rule
	 */
	public $scenarios=array('default');

	/**
	 * Path to directory where to save uploaded images
	 *
	 * @var string
	 */
	public $directory;

	/**
	 * Directory Url, without trailing slash
	 *
	 * @var string
	 */
	public $url;

	/**
	 * @var bool delete old file
	 */
	public $deleteOldFile = true;

	/**
	 * @var bool generate file name
	 */
	public $generateName = false;

	/**
	 * possible extensions of the file name, comma separated. If empty - all file extensions possible
	 */
	public $types = null;

	/**
	 * file prefix. can be used to avoid name clashes for example.
	 */
	public $prefix = '';

	/**
	 * Force the extension of all saved files. Default to null,
	 * which means use the source extension.
	 */
	public $forceExt;

	/**
	 * @var null Model method after upload, e.g., afterUpload()
	 */
	public $afterUploadMethod = null;

	private $_oldValue = null;

	// override to init some things
	public function attach($owner)
	{
		parent::attach($owner);

		if (empty($this->attribute)) {
			throw new Exception('Attribute property must be set.');
		}

		$this->_oldValue = $this->owner->{$this->attribute};
	}

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
		];
	}

	public function beforeSave()
	{
		//Checking emptiness of the attribute
		if (in_array($this->owner->scenario,$this->scenarios)
			&& ($file = UploadedFile::getInstance($this->owner, $this->attribute))
			&& !$file->hasError
		) {
			if (!empty($this->types) && $file) {
				$valid = strpos(strtolower($this->types), strtolower($file->getExtension())) !== false;
			} else {
				$valid = true;
			}

			if ($valid) {

				if ($this->deleteOldFile) {
					$this->deleteFile();
				}

				if ($this->generateName) {
					if ($this->attributeForName !== null && $this->owner->hasAttribute($this->attributeForName)) {
						$filename = $this->prefix . $this->attributeForName . '_' . substr(md5_file($file->tempName), 0, 5) . '.' . ($this->forceExt ? $this->forceExt : $file->getExtension());
					}

				} else {
					$filename = $this->prefix . $file->getBaseName() . '.' . ($this->forceExt ? $this->forceExt : $file->getExtension());
				}

				if (!isset($filename) || file_exists($this->getFilePathFromFileName($filename))) {
					$filename = $this->prefix . md5_file($file->tempName) . '.' . ($this->forceExt ? $this->forceExt : $file->getExtension());
				}

				if ($file->saveAs($this->getFilePathFromFileName($filename))) {
					$this->owner->setAttribute($this->attribute, $this->getFileUrlFromFileName($filename));
					$this->afterUpload();
				}
			}
		}
		return true;
	}

	public function beforeDelete($event)
	{
		$this->deleteFile(); // удалили модель? удаляем и файл, связанный с ней
	}

	public function getFilePathFromFileName($fileName)
	{
		return $this->directory . DIRECTORY_SEPARATOR . $fileName;
	}

	public function getFileUrlFromFileName($fileName)
	{
		return $this->url . DIRECTORY_SEPARATOR . $fileName;
	}


	public function getUrl() {
		return $this->owner->getAttribute($this->attribute);
	}

	public function getPath()
	{
		$url = $this->owner->getAttribute($this->attribute);
		if (empty($url)) return null;

		$filePath = str_replace($this->url, $this->directory, $url);
		return $filePath;
	}

	public function deleteFile()
	{
		$filePath = $this->getPath();
		if (!$filePath) return;

		if (file_exists($filePath)) {
			@unlink($filePath);
		}
	}

	protected function afterUpload()
	{
		if ($this->afterUploadMethod !== null && $this->owner->hasMethod($this->afterUploadMethod)) {
			call_user_func_array(array($this->owner, $this->afterUploadMethod), []);
		}
	}
}