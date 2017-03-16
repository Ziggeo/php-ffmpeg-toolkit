<?php

Class FfmpegVideoCodecs {
	
	static function videoTypeByCodec($codec) {
		$codec = strtolower($codec);
		if (strpos($codec, "flv") !== FALSE)
			return "flv";
		if (strpos($codec, "h264") !== FALSE)
			return "mp4";
		if (strpos($codec, "vp8") !== FALSE)
			return "webm";
		return "unknown";
	}
	
	static function videoTypeByCodecAndFileName($codec, $filename) {
		$codec = strtolower($codec);
		if (strpos(strtolower($filename), ".mov") !== FALSE)
			return "mov";
		if (strpos($codec, "flv") !== FALSE)
			return "flv";
		if (strpos($codec, "h264") !== FALSE)
			return "mp4";
		if (strpos($codec, "vp8") !== FALSE)
			return "webm";
		return "unknown";
	}

	static function videoSubTypeByCodec($codec) {
		$codec = strtolower($codec);
		if (strpos($codec, "flv") !== FALSE)
			return "regular";
		if (strpos($codec, "h264") !== FALSE) {
			if (strpos($codec, "baseline") !== FALSE)
				return "baseline-slowstart";
		}
		return "other";
	}

    static function audioSubTypeByCodec($codec) {
        $codec = strtolower($codec);
        if (strpos($codec, "opus") !== FALSE)
            return "opus";
        return "other";
    }

}
