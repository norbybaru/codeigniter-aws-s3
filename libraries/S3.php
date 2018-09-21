<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Aws\S3\S3Client;

/**
 * Class S3
 *
 * Aws S3 class
 *
 * To use this class, aws/aws-sdk-php is required within your project.
 * Use composer or any other means to install the AWS PHP SDK .
 *
 * @author Norby Baruani <norbybaru@gmail.com/>
 * @version 1.0.0
 * @since 1.0.0
 */
class S3 {

    // ACL flags
    const ACL_PRIVATE = 'private';
    const ACL_PUBLIC_READ = 'public-read';
    const ACL_PUBLIC_READ_WRITE = 'public-read-write';
    const ACL_AUTHENTICATED_READ = 'authenticated-read';

    // storage class flags
    const STORAGE_CLASS_STANDARD = 'STANDARD';
    const STORAGE_CLASS_RRS = 'REDUCED_REDUNDANCY';

    // server-side encryption flags
    const SSE_NONE = '';
    const SSE_AES256 = 'AES256';

    protected $s3;
    protected $CI;
    protected $bucket;

    /**
     * S3 constructor.
     */
    public function __construct()
    {
        $this->CI =& get_instance();

        //load aws configuration
        $this->CI->config->load('aws');
        //load storage library
        $this->CI->load->library('storage');

        //initialize s3 connection
        $this->s3 = new S3Client(array(
            'version'     => $this->CI->config->item('S3_VERSION'),
            'region'      => $this->CI->config->item('S3_REGION'),
            'credentials' => array(
                'key'    => $this->CI->config->item('AWS_ACCESS_KEY'),
                'secret' => $this->CI->config->item('AWS_SECRET_ACCESS_KEY'),
            ),
        ));

        $this->bucket = $this->CI->config->item('S3_BUCKET');
    }

    /**
     * @param $name
     * @return string
     */
    public function url($name)
    {
        return $this->s3->getObjectUrl($this->bucket, $name);
    }

    /**
     * @param $name
     * @return mixed
     */
    public function read($name)
    {
        if(!$this->exist($name)) exit("File not exist: $name");
        $info = $this->s3->getObject(array(
            'Bucket'       => $this->bucket,
            'Key'          => $name,
        ));
        return $info['Body'];
    }

    /**
     * @param $name
     * @return \Aws\Result
     */
    public function remove($name)
    {
        $info = $this->s3->deleteObject(array(
            'Bucket'       => $this->bucket,
            'Key'          => $name,
        ));
        return $info;
    }

    /**
     * @param $name
     * @return bool
     */
    public function exist($name)
    {
        return $this->s3->doesObjectExist($this->bucket, $name);
    }

    /**
     * @param UploadFile $file
     * @return string
     */
    public function upload(UploadFile &$file)
    {
        if (!empty($file->customName)) {
            $name = $file->customName;
        } else {
            $name = (!empty($file->clientName))
                ? substr($file->clientName, 0, strpos($file->clientName, ".{$file->extension}")) . '/' . $file->name
                : $file->name;
        }

        $result = $this->s3->putObject(array(
            'Bucket'       => $this->bucket,
            'Key'          => $name,
            'SourceFile'   => $file->path,
            'StorageClass' => self::STORAGE_CLASS_STANDARD,
            'ACL'          => self::ACL_PUBLIC_READ,
            'ContentType'  => $file->mime
        ));

        $this->s3->waitUntil('ObjectExists', array(
            'Bucket' => $this->bucket,
            'Key'    => $name,
        ));

        log_message('debug', $result->__toString());

        $file->path = $this->url($name);
        $file->fullPath = $file->path;

        return $this->url($name);
    }

    /**
     * @param $name
     * @param $info
     * @return mixed
     */
    public function write($name, $info)
    {
        $result = $this->s3->upload($this->bucket, $name, $info);
        $this->s3->waitUntil('ObjectExists', array(
            'Bucket' => $this->bucket,
            'Key'    => $name,
        ));
        return $result;
    }

    /**
     * @param $src
     * @param $target
     * @return \Aws\Result
     */
    public function copyFile($src, $target)
    {
        $info = $this->s3->copyObject(array(
            'Bucket'       => $this->bucket,
            'CopySource'   => $this->bucket . '/' . $src,
            'Key'          => $target,
        ));
        return $info;
    }

    /**
     * override bucket name
     *
     * @param $bucket
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * Create input info array for putObject()
     *
     * @param string $file Input file
     * @param mixed $md5sum Use MD5 hash (supply a string if you want to use your own)
     * @return array | false
     */
    public function inputFile($file, $md5sum = true)
    {
        if (!file_exists($file) || !is_file($file) || !is_readable($file))
        {
            trigger_error('S3::inputFile(): Unable to open input file: ' . $file, E_USER_WARNING);
            return false;
        }

        return array(
            'file' => $file,
            'size' => filesize($file),
            'md5sum' => $md5sum !== false
                ? (is_string($md5sum)
                    ? $md5sum
                    : base64_encode(md5_file($file, true)))
                : ''
        );
    }
}

