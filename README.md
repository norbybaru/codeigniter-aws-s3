# Codeigniter AWS S3
Amazon aws S3 storage libary for codeigniter

## Setup
1. Copy config and library files to your CI installation
2. Edit config/aws.php and config/storage.php with your appropriate settings

## Example Usage
```
//Load storage libray
$this->load->library('storage');

//Upload uploaded file to S3 or local storage
$this->storage->put('images');

or

//Upload file to S3 or local storage
$this->storage->putFile($filePath, $fileName);
```
## Class Methods

### Storage Library:
- put() - Upload file
- putFile() - Upload file using file path
- initConfig() - Override config/storage.php values
- getDisk() - Get the current disk setting. eg. s3, local
- file() - Return UploadFile class with uploaded file details

### S3 Libray
- url() - Return file url
- read() - Read file on s3 if it exist
- remove() - Remove file from s3
- exist() - Check if file exist on s3
- upload() - Upload file to s3
- write() - Upload file to s3
- copyFile() - Copy file to s3
- setBucket() - Set bucket to use
