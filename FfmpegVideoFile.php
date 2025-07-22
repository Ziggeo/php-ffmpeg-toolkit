<?php

require_once(dirname(__FILE__) . "/FfmpegVideoCodecs.php");
require_once(dirname(__FILE__) . "/FfmpegVideoTranscoding.php");

use Char0n\FFMpegPHP\Adapters\FFmpegMovie;

Class FfmpegVideoFile {

    private $filename;
    private $ffmpeg;
    private $video;
    private $movie;
    private $rotation;

    function __construct($name, $options = array()) {
        $this->filename = $name;
        $this->movie = new FFmpegMovie($name);
        $config = array();
        if (FfmpegVideoTranscoding::$ffmpeg_binary)
            $config["ffmpeg.binaries"] = array(FfmpegVideoTranscoding::$ffmpeg_binary);
        if (FfmpegVideoTranscoding::$ffprobe_binary)
            $config["ffprobe.binaries"] = array(FfmpegVideoTranscoding::$ffprobe_binary);
        $this->ffmpeg = FFMpeg\FFMpeg::create($config);
        $this->video = $this->ffmpeg->open($name);
        $this->rotation = @$options["rotate_add"] ? $options["rotate_add"] : 0;
        $this->rotation_write = $this->rotation;
        $this->autorotate = @$options["autorotate"] ? $options["autorotate"] : FALSE;
        if (@$options["rotation"]) {
            try {
                $this->rotation += FfmpegVideoTranscoding::getRotation($name);
                if (!$this->autorotate)
                    $this->rotation_write = $this->rotation;
            } catch (Exception $e) {
            }
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
        touch($filename);
        $frameCount = $this->movie->getFrameCount();
        if ($frameCount > 0) {
            $frameDuration = $this->getDuration() / $frameCount;
            if (ceil($seconds / $frameDuration) >= $frameCount)
                $seconds = ($frameCount - 1) * $frameDuration;
        }
        $frame = $this->video->frame(FFMpeg\Coordinate\TimeCode::fromSeconds($seconds));
        if (@$this->rotation_write)
            $frame->addFilter(new RotationFrameFilter($this->rotation_write));
        $frame->save($filename);
        if ($safeRevertToZero && !is_file($filename)) {
            $frame = $this->video->frame(0);
            if (@$this->rotation_write)
                $frame->addFilter(new RotationFrameFilter($this->rotation_write));
            $frame->save($filename);
        }
        return $filename;
    }

    function saveImageByPercentage($filename = NULL, $percentage = 0, $extension = "png", $safeRevertToZero = FALSE) {
        return $this->saveImageBySecond($filename, $percentage * $this->getDuration(), $extension, $safeRevertToZero);
    }

    function saveAudio($filename = NULL, $extension = "wav") {
        $filename = $filename == NULL ? $this->getTempFileName() . "." . $extension : $filename;
        touch($filename);
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
