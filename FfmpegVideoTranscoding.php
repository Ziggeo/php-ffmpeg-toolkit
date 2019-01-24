<?php

// TODO: Add -movflags faststart
// TODO: Update Rotation

require_once(dirname(__FILE__) . "/FfmpegExtensions.php");


Class FfmpegVideoTranscoding {

    public static $faststart_binary = "qt-faststart";
    public static $qtrotate_binary = "qtrotate.py";

    public static $ffmpeg_binary = NULL;
    public static $ffprobe_binary = NULL;

    /* Returns target file name
     *
     * Options:
     *  - bool replace (optional): Default is true
     *  - string target (optional)
     */
    public static function faststart($source, $options = array()) {
        $target = @$options["target"] ? $options["target"] : tempnam(sys_get_temp_dir(), "");
        touch($target);
        $command = self::$faststart_binary . " '" . $source . "' '" . $target . "'";
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
     *  - array filters (optional): Other filters
     *  - string format (optional): Mp4 / Flv
     *  - width (optional): New width
     *  - height (optional): New height
     *  - timeout (optional): In seconds. Default is one day.
     *  - bool faststart (optional),
     *  - int duration (optional)
     *  - bool rotate (optional, default false),
     *  - bool autorotate
     *  - int rotate_add (optional)
     *
     */
    public static function transcode($source, $options) {
        $target = @$options["target"] ? $options["target"] : tempnam(sys_get_temp_dir(), "");
        touch($target);
        if (@$options["width"] && $options["width"] % 2 == 1)
            $options["width"]--;
        if (@$options["height"] && $options["height"] % 2 == 1)
            $options["height"]--;
        try {
            $config = array(
                "timeout" => isset($options["timeout"]) ? $options["timeout"] : 60 * 60 * 24
            );
            if (self::$ffmpeg_binary)
                $config["ffmpeg.binaries"] = array(self::$ffmpeg_binary);
            if (self::$ffprobe_binary)
                $config["ffprobe.binaries"] = array(self::$ffprobe_binary);
            $ffmpeg = FFMpeg\FFMpeg::create($config);
            $rotation = @$options["rotate_add"] ? $options["rotate_add"] : 0;
            $autorotate = @$options["autorotate"] ? $options["autorotate"] : FALSE;
            $read_rotate = $rotation;
            try {
                $read_rotate += self::getRotation($source);
            } catch (VideoTranscodingException $e) {
                // Ignore it and assume rotation 0
            }
            if (!$autorotate && @$options["rotate"])
                $rotation = $read_rotate;
            $video = $ffmpeg->open($source);

            $bitSize = 500;
            $audioBitrate = 64;
            try {
                $originalWidth = $originalHeight = null;

                foreach ($video->getStreams() as $stream) {
                    if ($stream->isVideo()) {
                        if ($stream->has('width')) {
                            $originalWidth = $stream->get('width');
                        }
                        if ($stream->has('height')) {
                            $originalHeight = $stream->get('height');
                        }
                    } else if ($stream->isAudio() && !@$options["noaudio"]) {
                        $audioBitrate = max($audioBitrate, round($stream->get("bit_rate") / 1000));
                    }
                }

                $pixelSize = NULL;
                if (@$originalWidth && @$originalHeight) {
                    $pixelSize = $originalWidth * $originalHeight;
                    if (@$options["width"] && @$options["height"])
                        $pixelSize = min($pixelSize, $options["width"] * $options["height"]);
                    $bitSize = round(500 * $pixelSize / (640 * 480));
                }
            } catch (Exception $e) {

            }
            if ($read_rotate == 90 || $read_rotate == 270) {
                $temp = $originalWidth;
                $originalWidth = $originalHeight;
                $originalHeight = $temp;
            }
            if (@$rotation)
                $video->addFilter(new RotationFilter($rotation));
            if (@$originalWidth && @$originalHeight && !@$options["width"] && !@$options["height"] /*&& ($originalWidth % 2 == 1 || $originalHeight % 2 == 1)*/) {
                $options["width"] = $originalWidth;
                $options["height"] = $originalHeight;
                if ($options["width"] % 2 == 1)
                    $options["width"]--;
                if ($options["height"] % 2 == 1)
                    $options["height"]--;
            }
            if (@$options["width"] && @$options["height"])
                $video->addFilter(new RotationResizeFilter($rotation, new FFMpeg\Coordinate\Dimension($options["width"], $options["height"]), @$options["resizefit"] ? $options["resizefit"] : "inset"));
            $video->filters()->framerate(new FFMpeg\Coordinate\FrameRate(25), 250);
            if (!@$options["noaudio"])
                $video->filters()->synchronize();
            if (@$options["filters"])
                foreach ($options["filters"] as $filter)
                    $video->addFilter($filter);
            if (@$options["noaudio"])
                $video->addFilter(new VideoOnlyFilter());
            if (@$options["duration"])
                $video->addFilter(new DurationFilter($options["duration"]));
            if (@$options["format"]) {
                if ($options["format"] == "mp4") {
                    $format = new X264Baseline();
                    $format->setKiloBitrate($bitSize);
                    $format->setAudioKiloBitrate($audioBitrate);
                } elseif ($options["format"] == "flv") {
                    $format = new ExtraParamsDefaultVideo();
                    $format->setKiloBitrate($bitSize);
                    $format->setAudioKiloBitrate($audioBitrate);
                    $format->setExtraParams(array("-f", "flv", "-ar", "44100"));
                } else
                    throw new VideoTranscodingException(VideoTranscodingException::TRANSCODE_UNKNOWN_TARGET_FORMAT, $options["format"]);
            } else
                $format = new ExtraParamsDefaultVideo();
            $video->save($format, $target);
        } catch (Exception $e) {
            throw new VideoTranscodingException(VideoTranscodingException::TRANSCODE_EXCEPTION, (string)$e);
        }
        if (@$options["faststart"] && $options["format"] == "mp4")
            self::faststart($target);
        if (@$options["replace"]) {
            unlink($source);
            if (!rename($target, $source)) {
                @unlink($target);
                throw new VideoTranscodingException(VideoTranscodingException::TRANSCODE_RENAME_FAILED);
            }
        } else
            return $target;
    }

    private static function alternativeRotation($file) {
        $provider = new FFprobeOutputProvider();
        $provider->setMovieFile($file);
        $output = $provider->getOutput();
        $matches = array();
        $result = preg_match('/displaymatrix: rotation of (.+) degrees/', $output, $matches);
        $rotation = 0;
        if ($result && count($matches) === 2)
            $rotation = intval(-$matches[1]);
        return $rotation;
    }

    public static function getRotation($file) {
        $command = self::$qtrotate_binary . " '" . $file . "'";
        $result = 0;
        try {
            exec($command, $output, $result);
            if ($result != 0)
                throw new VideoTranscodingException(VideoTranscodingException::QTROTATE_FAILED);
            return intval($output[0]);
        } catch (Exception $e) {
            try {
                return self::alternativeRotation($file);
            } catch (Exception $f) {
                throw new VideoTranscodingException(VideoTranscodingException::QTROTATE_FAILED, (string)$e);
            }
        }
    }

    public static function extractAudio($source, $format) {
        try {
            $target = tempnam(sys_get_temp_dir(), "") . "." . $format;
            touch($target);
            $config = array(
                "timeout" => 60 * 60 * 24
            );
            if (self::$ffmpeg_binary)
                $config["ffmpeg.binaries"] = array(self::$ffmpeg_binary);
            if (self::$ffprobe_binary)
                $config["ffprobe.binaries"] = array(self::$ffprobe_binary);
            $ffmpeg = FFMpeg\FFMpeg::create($config);
            $video = $ffmpeg->open($source);
            $video->addFilter(new AudioOnlyFilter());
            $format = new ExtraParamsDefaultVideo();
            $video->save($format, $target);
            return $target;
        } catch (Exception $e) {
            throw new VideoTranscodingException(VideoTranscodingException::TRANSCODE_EXCEPTION, (string)$e);
        }
    }

    public static function transcodeAudioVideoSeparately($source, $options) {
        $audio = NULL;
        try {
            $audio = self::extractAudio($source, "aac");
        } catch (Exception $e) {
            return self::transcode($source, $options);
        }
        try {
            $video = self::transcode($source, array(
                "format" => $options["format"],
                "rotate" => $options["rotate"],
                "autorotate" => $options["autorotate"],
                "rotate_add" => $options["rotate_add"]
            ));
            unset($options["rotate"]);
            unset($options["rotate_add"]);
            unset($options["autorotate"]);
            if (!@$options["filters"])
                $options["filters"] = array();
            $options["filters"][] = new MapAndMergeFilter($audio, 0, 0);
            $result = self::transcode($video, $options);
            @unlink($audio);
            @unlink($video);
            return $result;
        } catch (VideoTranscodingException $e) {
            @unlink($audio);
            @unlink($video);
            throw $e;
        }
    }

    public static function transcodeAudioVideoSeparately2($source, $options) {
        $audio = NULL;
        try {
            $audio = self::extractAudio($source, "aac");
        } catch (Exception $e) {
            return self::transcode($source, $options);
        }
        try {
            $video = self::transcode($source, array(
                "noaudio" => TRUE,
                "format" => $options["format"],
                "rotate" => $options["rotate"],
                "autorotate" => $options["autorotate"],
                "rotate_add" => $options["rotate_add"]
            ));
            unset($options["rotate"]);
            unset($options["rotate_add"]);
            unset($options["autorotate"]);
            if (!@$options["filters"])
                $options["filters"] = array();
            $options["filters"][] = new MapAndMergeFilter($audio, 0, 0);
            $result = self::transcode($video, $options);
            @unlink($audio);
            @unlink($video);
            return $result;
        } catch (VideoTranscodingException $e) {
            @unlink($audio);
            @unlink($video);
            throw $e;
        }
    }

    public static function separateAudioVideoTranscodingRequired($source, $options) {
        if (strpos($source, ".webm", strlen($source) - strlen(".webm")) !== FALSE) {
            if (@$options["filters"]) {
                foreach ($options["filters"] as $filter)
                    if ($filter instanceof MapAndMergeFilter)
                        return FALSE;
            }
            return TRUE;
        }
        return FALSE;
    }

    public static function transcodeGracefully($source, $options) {
        try {
            if (self::separateAudioVideoTranscodingRequired($source, $options))
                return self::transcodeAudioVideoSeparately($source, $options);
            else
                return self::transcode($source, $options);
        } catch (Exception $e) {
            return self::transcodeAudioVideoSeparately2($source, $options);
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
    const QTROTATE_FAILED = 7;

    function __construct($message_id, $data = NULL) {
        $this->message_id = $message_id;
        $this->data = $data;
        parent::__construct($this->formatMessage() . " (" . $message_id . ") - " . $data);
    }

    function formatMessage() {
        switch ($this->message_id) {
            case self::FAST_START_EXEC_FAILED :
                return "Fast Start Exec Failed";
            case self::FAST_START_RENAME_FAILED :
                return "Fast Start Rename Failed";
            case self::FAST_START_EXCEPTION :
                return "Fast Start Exception";
            case self::TRANSCODE_RENAME_FAILED :
                return "Transcode Rename Failed";
            case self::TRANSCODE_EXCEPTION :
                return "Transcode Exception";
            case self::TRANSCODE_UNKNOWN_TARGET_FORMAT :
                return "Transcode Unknown Target Format";
            case self::QTROTATE_FAILED :
                return "Rotate Exec Failed";
        }
    }

}

FfmpegVideoTranscoding::$qtrotate_binary = dirname(__FILE__) . "/vendors/qtrotate.py";