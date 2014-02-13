<?php

namespace maddoger\core\behaviors;

use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\image\ImageDriver;
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
class ImageUpload extends FileUpload
{
	/**
	 * Widget preview height
	 * @var int
	 */
	public $previewHeight;
	/**
	 * Widget preview width
	 * @var int
	 */
	public $previewWidth;

	/**
	 * @var array Settings for image auto-generation
	 * @note
	 * 'preview' & 'original' versions names are reserved for image preview in widget
	 * and original image files
	 * @example
	 *  array(
	 *       'small' => array(
	 *              'resize' => array(200, null),
	 *       ),
	 *      'medium' => array(
	 *              'resize' => array(800, null),
	 *      )
	 *  );
	 */
	public $versions;

	/**
	 * @inheritdoc
	 */
	public function attach($owner)
	{
		parent::attach($owner);

		if (!isset($this->versions['original']))
			$this->versions['original'] = array();
		if (!isset($this->versions['preview']) && ($this->previewWidth || $this->previewHeight))
			$this->versions['preview'] = array('fit' => array($this->previewWidth, $this->previewHeight));
	}

	public function getUrl($version='original')
	{
		/*if (!$this->hasVersion($version)) {
			$this->deleteVersionImages();
			$this->updateImages();
		}*/
		$url = $this->owner->getAttribute($this->attribute);
		if (empty($url)) return null;

		if ($version == 'original') {
			return $url;
		} else {
			$info = pathinfo($url);
			return $info['dirname']. DIRECTORY_SEPARATOR . $version . '_'  . $info['filename']  .'.'. $info['extension'];
		}
	}

	public function getPath($version='original')
	{
		$url = $this->owner->getAttribute($this->attribute);
		if (empty($url)) return null;

		$filePath = $this->getPathFromUrl($url);

		if ($version == 'original') {
			return $filePath;
		} else {
			$info = pathinfo($filePath);
			return $info['dirname']. DIRECTORY_SEPARATOR . $version . '_'  . $info['filename']  .'.'. $info['extension'];
		}
	}

	public function hasVersion($version = 'original')
	{
		$originalImage = $this->getPath($version);
		return file_exists($originalImage);
	}

	/**
	 * Update images
	 */
	public function updateImages()
	{
		//create image preview for gallery manager
		$original = $this->getPath();
		if (!file_exists($original)) return false;
		foreach ($this->versions as $version => $actions) {
			/** @var \yii\image\drivers\Image $image */
			$image = Yii::$app->image->load($original);

			foreach ($actions as $method => $args) {
				call_user_func_array(array($image, $method), is_array($args) ? $args : array($args));
			}
			$image->save($this->getPath($version));
		}
		return true;
	}

	public function deleteFile()
	{
		$filePath = $this->getPath();
		if (!$filePath) return;

		if (@file_exists($filePath)) {
			@unlink($filePath);
		}

		$this->deleteVersionImages();
	}

	public function deleteVersionImages()
	{
		//create image preview for gallery manager
		foreach ($this->versions as $version => $actions) {
			if ($version == 'original') continue;

			$filePath = $this->getPath($version);

			if (@file_exists($filePath)) {
				@unlink($filePath);
			}
		}
	}

	protected function afterUpload()
	{
		$this->updateImages();
		parent::afterUpload();
	}
}