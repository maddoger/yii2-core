<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */


namespace maddoger\core\file;


/**
 * ManagerInterface
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger\core
 */
interface ManagerInterface
{
    /**
     * Saves a file
     * @param \yii\web\UploadedFile $file the file uploaded
     * @param string $name the name of the file
     * @param array $options
     * @return boolean
     */
    public function save($file, $name, $options = []);

    /**
     * Removes a file
     * @param string $name the name of the file to remove
     * @return boolean
     */
    public function delete($name);

    /**
     * Checks whether a file exists or not
     * @param string $name the name of the file
     * @return boolean
     */
    public function fileExists($name);

    /**
     * Returns the url of the file or null if the file doesn't exist.
     * @param string $name the name of the file
     * @return string|null
     */
    public function getUrl($name);
}