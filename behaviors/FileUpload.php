<?php

namespace rusporting\core\behaviors;

use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
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
	 * this attribute (or array of attributes) will determine a part of the file name.
	 */
	public $attributeForName = null;

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

	/**
	 * @param \yii\db\ActiveRecord $owner
	 * @throws \yii\base\Exception
	 */
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

	public function beforeSave($insert)
	{
		//Checking emptiness of the attribute
		if (in_array($this->owner->scenario,$this->scenarios)
		) {

			if ($this->file !== null) {
				$file = $this->file;
			} else {
				$file = UploadedFile::getInstance($this->owner, $this->attribute);
			}

			if ($file && ($file instanceof UploadedFile && !$file->hasError)) {

				if (!empty($this->types)) {
					$valid = strpos(strtolower($this->types), strtolower($file->getExtension())) !== false;
				} else {
					$valid = true;
				}


				if ($valid) {

					if (!$insert && $this->deleteOldFile && isset($this->owner->oldAttributes[$this->attribute])) {
						$this->owner->setAttribute($this->attribute, $this->owner->getOldAttribute($this->attribute));
						$this->deleteFile();
						$this->owner->setAttribute($this->attribute, null);
					}

					if ($this->generateName) {
						if ($this->attributeForName !== null && $this->owner->hasAttribute($this->attributeForName)) {
							$filename = $this->prefix . $this->owner->{$this->attributeForName} . '_' . substr(md5_file($file->tempName), 0, 5) . '.' . ($this->forceExt ? $this->forceExt : $file->getExtension());
						}

					} else {
						$filename = $this->prefix . $file->getBaseName() . '.' . ($this->forceExt ? $this->forceExt : $file->getExtension());
					}

					if (!isset($filename) || file_exists($this->getFilePathFromFileName($filename))) {
						$filename = $this->prefix . md5_file($file->tempName) . '.' . ($this->forceExt ? $this->forceExt : $file->getExtension());
					}

					//Check directory
					$path = $this->getFilePathFromFileName($filename);
					$dir = dirname($path);

					if (!is_dir($dir)) {
						if (!FileHelper::createDirectory($dir)) {
							throw new Exception('Directory creation failed!');
						}
					}
					if ($file->saveAs($path)) {
						$this->owner->setAttribute($this->attribute, $this->getFileUrlFromFileName($filename));
						$this->afterUpload();
					} else {
						$this->owner->setAttribute($this->attribute, null);
					}
				}
			} elseif ($this->owner->isAttributeChanged($this->attribute)) {

				$fileName = $this->owner->{$this->attribute};

				if (!$insert && $this->deleteOldFile && isset($this->owner->oldAttributes[$this->attribute])) {
					$this->owner->setAttribute($this->attribute, $this->owner->getOldAttribute($this->attribute));
					$this->deleteFile();
					$this->owner->setAttribute($this->attribute, null);
				}

				if (!empty($fileName)) {

					if (!empty($this->types)) {
						$valid = strpos(strtolower($this->types), strtolower(pathinfo($fileName, PATHINFO_EXTENSION))) !== false;
					} else {
						$valid = true;
					}

					if (!$valid) {
						$this->owner->setAttribute($this->attribute, null);
					} else {

						//Remote file?
						if (preg_match('#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#i', $fileName, $matches)) {

							$content = null;

							if (ini_get('allow_url_fopen')) {
								//try simple
								$content = file_get_contents($fileName);
							} elseif (function_exists('curl_init')) {
								//else curl
								$ch = curl_init();
								curl_setopt($ch, CURLOPT_HEADER, 1);
								curl_setopt($ch, CURLOPT_FAILONERROR, 1);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
								curl_setopt($ch, CURLOPT_URL, $fileName);
								$result = curl_exec($ch);
								curl_close($ch);

								$content = $result;
							}

							if ($content !== null) {

								$ext = ($this->forceExt ? $this->forceExt : pathinfo($fileName, PATHINFO_EXTENSION));

								if ($this->generateName) {
									if ($this->attributeForName !== null && $this->owner->hasAttribute($this->attributeForName)) {
										$newFileName = $this->prefix . $this->owner->{$this->attributeForName} . '_' . substr(md5($content), 0, 5) . '.' . $ext;
									}

								} else {
									$n = basename($fileName);
									$newFileName = $this->prefix . substr($n, 0, strrpos($n, '.')) . '.' . $ext;
								}

								if (!isset($newFileName) || file_exists($this->getFilePathFromFileName($newFileName))) {
									$newFileName = $this->prefix . md5($content) . '.' . $ext;
								}

								$path = $this->getFilePathFromFileName($newFileName);
								$dir = dirname($path);

								if (!is_dir($dir)) {
									if (!FileHelper::createDirectory($dir)) {
										throw new Exception('Directory creation failed!');
									}
								}
								if (file_put_contents($path, $content)) {
									$fileName = $this->getFileUrlFromFileName($newFileName);
								}
							} else {
								$fileName = null;
							}
						}

						//Copy file
						/*if (strpos($fileName, $this->directory) === false) {
							$oldFileName = $fileName;
							$fileName = $this->directory . DIRECTORY_SEPARATOR . basename($fileName);
							if (copy($this->getPathFromUrl($oldFileName), $fileName)) {
								$this->owner->setAttribute($this->attribute, $fileName);
							} else {
								$this->owner->setAttribute($this->attribute, null);
							}
						}*/
						$this->owner->setAttribute($this->attribute, $fileName);

						$this->afterUpload();
					}
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
		return $this->getPathFromUrl($this->owner->getAttribute($this->attribute));
	}

	public function deleteFile()
	{
		$filePath = $this->getPath();
		if (!$filePath) return;

		if (file_exists($filePath)) {
			@unlink($filePath);
		}
	}

	protected function getPathFromUrl($url)
	{
		if (empty($url)) return null;

		$filePath = $url;

		if (strpos($url, $this->directory) !== false) {
			$filePath = str_replace($this->url, $this->directory, $url);
		} elseif (substr($url, 0, 1) === '/') {
			$filePath = Yii::getAlias('@frontendPath' . DIRECTORY_SEPARATOR . substr($url, mb_strlen(Yii::getAlias('@frontendUrl/'))));
		}

		return $filePath;
	}

	protected function afterUpload()
	{
		if ($this->afterUploadMethod !== null && $this->owner->hasMethod($this->afterUploadMethod)) {
			call_user_func_array(array($this->owner, $this->afterUploadMethod), []);
		}
	}
}