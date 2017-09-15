<?php

require_once(dirname(__FILE__) . "/FfmpegVideoCodecs.php");
require_once(dirname(__FILE__) . "/FfmpegVideoTranscoding.php");

Class FfmpegVideoFile {
	
	private $filename;
	private $ffmpeg;
	private $video;
	private $movie;
	private $rotation;
	
	function __construct($name, $options = array()) {
		$this->filename = $name;
		$this->movie = new ffmpeg_movie($name);
		$this->ffmpeg = FFMpeg\FFMpeg::create();
		$this->video = $this->ffmpeg->open($name);
		$this->rotation = @$options["rotate_add"] ? $options["rotate_add"] : 0;
        $this->autorotate = @$options["autorotate"] ? $options["autorotate"] : FALSE;
		if (@$options["rotation"]) {
			try {
				$this->rotation += FfmpegVideoTranscoding::getRotation($name);
			} catch (Exception $e) {}
		}
	}
	
	function getRotation() {
		return $this->rotation;
	}
	
	function hasVideo() {
		return $this->movie->hasVideo();
	}
	
	function hasAudio() {
		return $this->movie->hasAudio();
	}

	function getDuration() {
		return $this->movie->getDuration();
	}
	
	function getWidth() {
		return $this->rotation % 180 == 0 ? $this->movie->getFrameWidth() : $this->movie->getFrameHeight();
	}

	function getHeight() {
		return $this->rotation % 180 == 0 ? $this->movie->getFrameHeight() : $this->movie->getFrameWidth();
	}

	function getVideoCodec() {
		return $this->movie->getVideoCodec();
	}
	
	function getAudioCodec() {
		return $this->movie->getAudioCodec();
	}
	
	private function getTempFileName() {
		return tempnam(sys_get_temp_dir(), "");
	}
	
	function saveImageBySecond($filename = NULL, $seconds = 0, $extension = "png", $safeRevertToZero = FALSE) {
		$filename = $filename == NULL ? $this->getTempFileName() . "." . $extension : $filename;
		$frameCount = $this->movie->getFrameCount();
		if ($frameCount > 0) {
			$frameDuration = $this->getDuration() / $frameCount;
			if (ceil($seconds / $frameDuration) >= $frameCount)
				$seconds = ($frameCount-1) * $frameDuration;
		}
		$frame = $this->video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($seconds));
		if (@$this->rotation && !$this->autorotate)
			$frame->addFilter(new RotationFrameFilter($this->rotation));
		$frame->save($filename);
		if ($safeRevertToZero && !is_file($filename)) {
			$frame = $this->video->frame(0);
			if (@$this->rotation && !$this->autorotate)
				$frame->addFilter(new RotationFrameFilter($this->rotation));
			$frame->save($filename);
		}
		return $filename;
	}
	
	function saveImageByPercentage($filename = NULL, $percentage = 0, $extension = "png", $safeRevertToZero = FALSE) {
		return $this->saveImageBySecond($filename, $percentage * $this->getDuration(), $extension, $safeRevertToZero);
	}
	
	function saveAudio($filename = NULL, $extension = "wav") {
		$filename = $filename == NULL ? $this->getTempFileName() . "." . $extension : $filename;
		$audio = new FFMpeg\Media\Audio($this->filename, $this->ffmpeg->getFFMpegDriver(), $this->ffmpeg->getFFProbe());
		$audio->save(new NullAudio(), $filename);
		return $filename;
	}
	
	function getVideoType($filename = NULL) {
		return FfmpegVideoCodecs::videoTypeByCodecAndFileName($this->getVideoCodec(), @$filename ? $filename : $this->filename);
	}
	
	function getVideoSubType() {
		return FfmpegVideoCodecs::videoSubTypeByCodec($this->getVideoCodec());
	}

    function getAudioSubType() {
        return FfmpegVideoCodecs::audioSubTypeByCodec($this->getAudioCodec());
    }

}
