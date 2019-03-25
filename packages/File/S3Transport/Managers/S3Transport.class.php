<?php
use \Aws\S3\S3Client;
use \Aws\S3\Exception\S3Exception;

class S3Transport {
	
	const ACL_PRIVATE = 'private';
	const ACL_PUBLIC_READ = 'public-read';
	const ACL_PUBLIC_READ_WRITE = 'public-read-write';
	const ACL_AUTHENTICATED_READ = 'authenticated-read';

	public static function getConfigByName($name){
		return ConfigManager::getConfig('File', 'S3Transport')->AuxConfig->configs->$name;
	}
	
	public static function getS3Client($config){
		return new Aws\S3\S3Client([
			'version' => 'latest',
			'region' => $config->region,
			'endpoint' => $config->endpoint,
			'credentials' => [
				'key' => $config->credentials->key,
				'secret' => $config->credentials->secret
			]
		]);
	}

	public static function upload($filePath, $uploadPath, $acl = self::ACL_PUBLIC_READ, $configName = 'default', $bucketName = null) {
		$config = static::getConfigByName($configName);
		$s3Client = static::getS3Client($config);

		if(empty($bucketName)){
			$bucketName = $config->bucket;
		}

		return $s3Client->putObject([
			'ACL' => $acl,
			'Bucket' => $bucketName,
			'Key' => $uploadPath,
			'SourceFile' => $filePath,
		]);
	}
	
	public static function get($filePath, $configName = 'default', $bucketName = null) {
		$config = static::getConfigByName($configName);
		$s3Client = static::getS3Client($config);

		if(empty($bucketName)){
			$bucketName = $config->bucket;
		}

		return $s3Client->getObject([
			'Bucket' => $bucketName,
			'Key' => $filePath,
		]);
	}
	
	public static function download($filePath, $savePath, $configName = 'default', $bucketName = null) {
		$config = static::getConfigByName($configName);
		$s3Client = static::getS3Client($config);

		if(empty($bucketName)){
			$bucketName = $config->bucket;
		}

		return $s3Client->getObject([
			'Bucket' => $bucketName,
			'Key' => $filePath,
			'SaveAs' => $savePath
		]);
	}
	
	public static function getFileUrl($filePath, $configName = 'default', $bucketName = null){
		$config = static::getConfigByName($configName);
		
		if(empty($bucketName)){
			$bucketName = $config->bucket;
		}
		
		return 'https://' . $bucketName . '.' . $config->region . '.' . $config->baseUrl . '/' . $filePath;
	}
	
	public static function fileExists($filePath, $configName = 'default', $bucketName = null) {
		$config = static::getConfigByName($configName);
		$s3Client = static::getS3Client($config);

		if(empty($bucketName)){
			$bucketName = $config->bucket;
		}

		return $s3Client->doesObjectExist($bucketName, $filePath);
	}

	public static function delete($filePath, $configName = 'default', $bucketName = null) {
		$config = static::getConfigByName($configName);
		$s3Client = static::getS3Client($config);

		if(empty($bucketName)){
			$bucketName = $config->bucket;
		}

		return $s3Client->deleteObject([
			'Bucket' => $bucketName,
			'Key' => $filePath
		]);
	}

}
