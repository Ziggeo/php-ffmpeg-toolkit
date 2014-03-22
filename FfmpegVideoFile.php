<?php

require_once(dirname(__FILE__) . "/FfmpegVideoCodecs.php");

Class FfmpegVideoFile {
	
	private $filename;
	private $ffmpeg;
	private $video;
	private $movie;
	
	function __construct($name) {
		$this->filename = $name;
		$this->movie = new ffmpeg_movie($name);
		$this->ffmpeg = FFMpeg\FFMpeg::create();
		$this->video = $this->ffmpeg->open($name);
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
		return $this->movie->getFrameWidth();
	}

	function getHeight() {
		return $this->movie->getFrameHeight();
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
	
	function saveImageBySecond($filename = NULL, $seconds = 0, $extension = "png") {
		$filename = $filename == NULL ? $this->getTempFileName() . "." . $extension : $filename;
		$this->video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($seconds))->save($filename);
		return $filename;
	}
	
	function saveImageByPercentage($filename = NULL, $percentage = 0, $extension = "png") {
		$filename = $filename == NULL ? $this->getTempFileName() . "." . $extension : $filename;
		$this->video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds(floor($percentage * $this->getDuration())))->save($filename);
		return $filename;
	}
	
	function getVideoType($filename = NULL) {
		return FfmpegVideoCodecs::videoTypeByCodecAndFileName($this->getVideoCodec(), @$filename ? $filename : $this->filename);
	}
	
	function getVideoSubType() {
		return FfmpegVideoCodecs::videoSubTypeByCodec($this->getVideoCodec());
	}

}
