<?php

/**
 * ファイルアップローダークラス
 * 現在は画像ファイルのみを想定(2015/09/15)
 *
 * Class FileUploader
 *
 * TODO: 複数ファイルアップロードも考慮してるけど動作確認はろくにしてない
 *
 * @author kino
 * @create 2015/09/15
 */
class FileUploader
{
	private $files;
	private $inputName;
	private $debugMode = false;
	private $isMultiUpload = false;
	private $errorMessages = array();
	private $uploadFilePaths = array();

	/**
	 * エラーメッセージ
	 */
	const UPLOAD_NO_FILE = 'ファイルが選択されていません';
	const UPLOAD_FAILED = 'アップロードに失敗しました';
	const UPLOAD_OVERSIZE = 'ファイルサイズが大きすぎます';
	const UPLOAD_NO_TYPE = '画像ファイルではありません';

	/**
	 * fileUpload constructor.
	 *
	 * @param array $files $_FILESの情報
	 * @param String $inputName input[name]の値
	 *
	 * return void
	 */
	public function __construct($files, $inputName)
	{
		$this->files = $files;
		$this->inputName = $inputName;
	}

	/**
	 * デバッグモードを使用するかの設定
	 *
	 * @param boolean $debugMode デバッグモード(ログ出力)
	 */
	public function setDebugMode($debugMode)
	{
		$this->debugMode = $debugMode;
	}

	/**
	 * 複数ファイルのアップロードかの設定
	 *
	 * @param boolean $isMultiUpload 複数ファイルのアップロードか
	 */
	public function setIsMultiUpload($isMultiUpload)
	{
		$this->isMultiUpload = $isMultiUpload;
	}

	/**
	 * デバッグログ
	 *
	 * @param String $message 表示するログ
	 *
	 * return void
	 */
	private function logging($message)
	{
		if (!$this->debugMode) return;
		echo '<pre>', 'Logged: ' . $message, '</pre>';
	}

	/**
	 * 移動先ファイルパスの取得
	 *
	 * @param String $filePath 移動元のファイルパス
	 * @param String $moveDir 移動先ディレクトリ
	 * @return string
	 */
	private function createMoveFilePath($filePath, $moveDir)
	{
		$pathInfo = pathinfo($filePath);
		$extension = '.' . $pathInfo['extension'];
		return $moveDir . uniqid(rand()) . $extension;
	}

	/**
	 * アップロード処理
	 *
	 * @param int $fileNo ファイル番号
	 * @param String $fileTmpName 移動前のファイルの名称
	 * @param String $fileName アップロード後のファイル名
	 * @param String $filePath アップロード先ファイルパス
	 */
	private function upload($fileNo, $fileTmpName, $fileName, $filePath)
	{
		$this->uploadFilePaths[$fileNo] = $filePath;
		try {
			$this->logging(sprintf('tmpFileName is %s', $fileTmpName));
			$this->logging(sprintf('fileName is %s', $filePath));
			if (move_uploaded_file($fileTmpName, $filePath)) {
				$this->logging(sprintf('Upload OK: File => %s Path => %s', $fileName, $filePath));
			} else {
				$this->setErrorMessages($fileNo, self::UPLOAD_FAILED);
				$this->logging(sprintf('Upload Error: File => %s Message => %s', $fileName, self::UPLOAD_FAILED));
			}
		} catch(Exception $e) {
			$this->setErrorMessages($fileNo, self::UPLOAD_FAILED);
			$this->logging(sprintf('Upload Error: File => %s Message => %s', $fileName, $e->getMessage()));
		}
	}

	/**
	 * $_FILESのエラーコードチェック
	 *
	 * @param $fileNo
	 * @param $errorCode
	 * @param $fileType
	 */
	private function checkFilesErr($fileNo, $errorCode, $fileType)
	{
		$isError = false;
		switch ($errorCode) {
			case UPLOAD_ERR_OK: // OK
				break;
			case UPLOAD_ERR_NO_FILE:    // ファイル未選択
				$this->logging(sprintf('Upload Error: FileNo => %d', $fileNo));
				$this->setErrorMessages($fileNo, self::UPLOAD_NO_FILE);
				$isError = true;
				break;
			case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
			case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過
				$this->logging(sprintf('Upload Error: File => %d', $fileNo));
				$this->setErrorMessages($fileNo, self::UPLOAD_OVERSIZE);
				$isError = true;
				break;
			default:
				$this->logging(sprintf('Upload Error: File => %d', $fileNo));
				$this->setErrorMessages($fileNo, self::UPLOAD_FAILED);
				$isError = true;
		}

		$acceptType = array('image/gif', 'image/jpeg', 'image/png');
		if (!$isError && !in_array($fileType, $acceptType)) {
			$this->logging(sprintf('Upload Error: FileType => %s', $fileType));
			$this->setErrorMessages($fileNo, self::UPLOAD_NO_TYPE);
		}
	}

	/**
	 * エラーメッセージの設定
	 *
	 * @param int $fileNo ファイルの番号
	 * @param String $message エラーメッセージ
	 */
	private function setErrorMessages($fileNo, $message)
	{
		$this->errorMessages[$fileNo] = $message;
	}

	/**
	 * $_FILESのチェック
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function checkFiles()
	{
		if ($this->isMultiUpload && !is_array($this->files[$this->inputName])) {
			throw new Exception('Not Multi Upload');
		}

		$this->logging('check start');

		$files = $this->files[$this->inputName];
		// 空配列で来たら問答無用でエラー
		if (empty($files)) {
			$this->setErrorMessages(-1, self::UPLOAD_NO_FILE);
			$this->logging('check end');
			return false;
		}

		if ($this->isMultiUpload) {
			foreach ($files['error'] as $key => $error) {
				$this->checkFilesErr($key, $error, $files['type'][$key]);
			}
		} else {
			$this->checkFilesErr(0, $files['error'], $files['type']);
		}

		if (!empty($this->errorMessages)) {
			$this->logging('check end');
			return false;
		}

		$this->logging('check end');
		return true;
	}

	/**
	 * アップロード処理実行
	 *
	 * @param String $saveDir アップロードするディレクトリ
	 * @return bool アップロード結果
	 * @throws Exception
	 */
	public function exec($saveDir)
	{
		if ($this->isMultiUpload && !is_array($this->files[$this->inputName])) {
			throw new Exception('Not Multi Upload');
		}

		$this->logging(sprintf('saveDir is %s', $saveDir));
		if (!file_exists($saveDir)) {
			$this->logging('Nothing directory');
			$this->logging('Create directory');
			/*
			 * ディレクトリが無いので作成
			 * SVNで作成すると権限でおかしくなるのでこれで対応
			 */
			if (!mkdir($saveDir, '0755', true)) {
				$this->logging('Create directory failed');
				return false;
			}
		}

		if (!is_writable($saveDir)) {
			$this->logging('Write directory: Permission denied');
			$this->logging('permission: '.substr(sprintf('%o', fileperms($saveDir)), -4));
			return false;
		}

		$this->logging('exec start');
		$files = $this->files[$this->inputName];
		// 一応作ってるけどまだ使わない予定 (2015/09/15)
		if ($this->isMultiUpload) {
			foreach ($files['error'] as $key => $error) {
				// ユーザーのファイル名は被ってる可能性があるのでユニークなファイル名を生成する
				$filePath = $this->createMoveFilePath($files['name'][$key], $saveDir);

				// 失敗したファイルの情報を取得するためループ内でtry catchする
				$this->upload($key, $files['tmp_name'][$key], $files['name'][$key], $filePath);
			}
		} else {
			// ユーザーのファイル名は被ってる可能性があるのでユニークなファイル名を生成する
			$filePaths = $this->createMoveFilePath($files['name'], $saveDir);

			$this->upload(0, $files['tmp_name'], $files['name'], $filePaths);
		}

		if (!empty($this->errorMessages)) {
			return false;
		}

		$this->logging('exec end');
		return true;
	}

	/**
	 * エラーメッセージ取得
	 *
	 * @return array エラーメッセージ
	 */
	public function getErrorMessages()
	{
		return $this->errorMessages;
	}

	/**
	 * アップロードしたファイルパスを取得
	 *
	 * @return array ユーザがアップロードしたファイル
	 */
	public function getUploadFilePaths()
	{
		return $this->uploadFilePaths;
	}

	/**
	 *
	 * 対象ファイルを削除
	 *
	 * TODO: できれば別クラスに分けたい
	 *
	 * @param String $targetPath 削除対象のファイルパス
	 * @return bool
	 */
	public function deleteFile($targetPath) {
		$this->logging('Delete target path is '. $targetPath);
		if (!file_exists($targetPath)) {
			$this->logging('Nothing File is '. $targetPath);
			return false;
		}

		if (unlink($targetPath)) {
			$this->logging('Delete success. File is '. $targetPath);
			return true;
		} else {
			$this->logging('Delete failed. File is '. $targetPath);
			return false;
		}
	}

	/**
	 * 対象ファイル移動
	 *
	 * @param String $targetPath 移動対象ファイルパス
	 * @param String $moveDir 移動先ファイルパス
	 * @return bool
	 */
	public function moveFile($targetPath, $moveDir) {
		$this->logging('Target file path is '. $targetPath);
		$this->logging('Move target directories is '. $moveDir);

		if (!file_exists($targetPath)) {
			$this->logging('Nothing File is '. $targetPath);
			return false;
		}

		$fileName = basename($targetPath);
		$this->logging('Target file name is '. $fileName);
		if (!file_exists($moveDir)) {
			$this->logging('Nothing move target directories is '. $moveDir);
			if (!mkdir($moveDir, 0755, true)) return false;
		}

		if (rename($targetPath, $moveDir.$fileName)) {
			$this->logging('Move Success!');
			return $moveDir.$fileName;
		} else {
			$this->logging('Move Failed...');
			return false;
		}
	}

}