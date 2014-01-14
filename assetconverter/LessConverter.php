<?php
/**
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 */

namespace rusporting\core\assetconverter;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\FileHelper;
use yii\web\AssetConverterInterface;

class LessConverter extends Component implements AssetConverterInterface
{
	/**
	 * @var bool Compress output
	 */
	public $compression = false;

    /**
     * @var boolean if true the asset will always be published
     */
    public $force = false;


    /**
     * Converts a given asset file into a CSS or JS file.
     * @param string $asset the asset file path, relative to $basePath
     * @param string $basePath the directory the $asset is relative to.
     * @return string the converted asset file path, relative to $basePath.
     */
    public function convert($asset, $basePath)
    {
        $pos = strrpos($asset, '.');
        if ($pos !== false) {
            $ext = substr($asset, $pos + 1);
            if ($ext == 'less') {

				try
				{
					$result = substr($asset, 0, $pos + 1) . 'css';
					$src = "$basePath/$asset";
					$dst = "$basePath/$result";

					if ($this->force || (@filemtime($dst) < filemtime($src))) {

						$options = [];
						if ($this->compression) {
							$options['compress'] = true;
						}

						$parser = new \Less_Parser();
						$cacheDir = Yii::$app->getRuntimePath().'/cache/lessphp';
						if (!is_dir($cacheDir)) {
							FileHelper::createDirectory($cacheDir, 0777);
						}
						$parser->SetCacheDir($cacheDir);
						$parser->SetOptions($options);
						$parser->parseFile($src);

						$css = $parser->getCss();
						file_put_contents($dst, $css);

						if (YII_DEBUG) {
							Yii::info("Converted $asset into $result ", __CLASS__);
						}
					}
					return $result;

				} catch (Exception $e) {
					throw new Exception(__CLASS__ . ': Failed to compile less file : ' . $e->getMessage() . '.');
				}
            }
        }
        return $asset;
    }
}
