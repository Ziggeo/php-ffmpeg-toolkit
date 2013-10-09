<?php

require_once(dirname(__FILE__) . "/FfmpegExtensions.php");


Class FfmpegVideoTranscoding {
	
	public static $faststart_binary = "qt-faststart";
	public static $qtrotate_binary = "qtrotate.py";
	
	/* Returns target file name
	 * 
	 * Options:
	 *  - bool replace (optional): Default is true
	 *  - string target (optional)
	 */
	public static function faststart($source, $options = array()) {
		$target = @$options["target"] ? $options["target"] : tempnam(sys_get_temp_dir(), "");
		$command = self::$faststart_binary . " " . $source . " " . $target;
		$result = 0;
		try { 
			exec($command, $output, $result);
			if ($result != 0) 
				throw new VideoTranscodingException(VideoTranscodingException::FAST_START_EXEC_FAILED);
			if (file_exists($target) && filesize($target) > 0) {
				if (!isset($options["replace"]) || $options["replace"]) {
					if (!rename($target, $source)) {
						@unlink($target);
						throw new VideoTranscodingException(VideoTranscodingException::FAST_START_RENAME_FAILED);
					}
				}
			} else {
				if (isset($options["replace"]) && !$options["replace"]) {
					if (!rename($source, $target))
						throw new VideoTranscodingException(VideoTranscodingException::FAST_START_RENAME_FAILED);
				}
			}
			if (!isset($options["replace"]) || $options["replace"])
				return $source;
			else
				return $target;
		} catch (Exception $e) {
			throw new VideoTranscodingException(VideoTranscodingException::FAST_START_EXCEPTION, $e->getMessage());
		}
	}
	
	
	/* Returns temporary filename or throws exception
	 * 
	 * Options:
	 *  - bool replace (optional): Default is false
	 *  - string target (optional): Target file name
	 *  - string watermark (optional): Watermark file name
	 *  - string format (optional): Mp4 / Flv
	 *  - width (optional): New width
	 *  - height (optional): New height
	 *  - bool faststart (optional),
	 *  - int duration (optional)
	 *  - bool rotate (optional, default false, only required for old_ffmpeg),
	 *  - bool old_ffmpeg (optional, default false)
	 * 
	 */	
	public static function transcode($source, $options) {
		$target = @$options["target"] ? $options["target"] : tempnam(sys_get_temp_dir(), "");
		try {
			$ffmpeg = @$options["old_ffmpeg"] ? FFMpegOld::create() : FFMpeg\FFMpeg::create();
			if (@$options["rotate"] && @$options["old_ffmpeg"])
				$rotation = self::getRotation($source);
			$video = $ffmpeg->open($source);
			if (@$rotation)
				$video->addFilter(new RotationFilter($rotation));
			if (@$options["width"] && @$options["height"])
				$video->filters()->resize(new FFMpeg\Coordinate\Dimension($options["width"], $options["height"]), "inset");
			$video->filters()->framerate(new FFMpeg\Coordinate\FrameRate(25), 250)->synchronize();
			if (@$options["watermark"])
				$video->addFilter(new WatermarkFilter($options["watermark"], 0.25, 0.95, 0.95));
			if (@$options["duration"])
				$video->addFilter(new DurationFilter($options["duration"]));
			$format = new ExtraParamsDefaultVideo();
			if (@$options["format"]) {
				if ($options["format"] == "mp4") {
					$format = new X264Baseline();
					$format->setKiloBitrate(500);
					$format->setAudioKiloBitrate(64);
				} elseif ($options["format"] == "flv") {
					$format->setKiloBitrate(500);
					$format->setAudioKiloBitrate(64);
					$format->setExtraParams(array("-f", "flv", "-ar", "44100"));
				} else
					throw new VideoTranscodingException(VideoTranscodingException::TRANSCODE_UNKNOWN_TARGET_FORMAT, $options["format"]);
			}
			$video->save($format, $target);
		} catch (Exception $e) {
			throw new VideoTranscodingException(VideoTranscodingException::TRANSCODE_EXCEPTION, $e->getMessage());
		}
		if (@$options["faststart"] && $options["format"] == "mp4")
			self::faststart($target);
		if (@$options["replace"]) {
			if (!rename($target, $source)) {
				@unlink($target);
				throw new VideoTranscodingException(VideoTranscodingException::TRANSCODE_RENAME_FAILED);
			}
		} else
			return $target;
	}

	public static function getRotation($file) {
		$command = self::$qtrotate_binary . " " . $file;
		$result = 0;
		try { 
			exec($command, $output, $result);
			if ($result != 0) 
				throw new VideoTranscodingException(VideoTranscodingException::QTROTATE_FAILED);
			return intval($output[0]);
		} catch (Exception $e) {
			throw new VideoTranscodingException(VideoTranscodingException::QTROTATE_FAILED, $e->getMessage());
		}
	}
	
}


Class VideoTranscodingException extends Exception {
	
	const FAST_START_EXEC_FAILED = 1;
	const FAST_START_RENAME_FAILED = 2;
	const FAST_START_EXCEPTION = 3;
	const TRANSCODE_RENAME_FAILED = 4;
	const TRANSCODE_EXCEPTION = 5;
	const TRANSCODE_UNKNOWN_TARGET_FORMAT = 6;
	
	function __construct($message_id, $data = NULL) {
		$this->message_id = $message_id;
		$this->data = $data;
		parent::__construct($this->formatMessage() . " (" . $message_id . ") - " . $data);
	}
	
	function formatMessage() {
		switch ($this->message_id) {
			case self::FAST_START_EXEC_FAILED : return "Fast Start Exec Failed";
			case self::FAST_START_RENAME_FAILED : return "Fast Start Rename Failed";
			case self::FAST_START_EXCEPTION : return "Fast Start Exception";
			case self::TRANSCODE_RENAME_FAILED : return "Transcode Rename Failed";
			case self::TRANSCODE_EXCEPTION : return "Transcode Exception";
			case self::TRANSCODE_UNKNOWN_TARGET_FORMAT : return "Transcode Unknown Target Format";
			case self::QTROTATE_FAILED : return "Rotate Exec Failed";
		}
	}
	
}