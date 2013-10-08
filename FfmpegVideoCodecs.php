<?php

Class FfmpegVideoCodecs {
	
	static function videoTypeByCodec($codec) {
		$codec = strtolower($codec);
		if (strpos($codec, "flv") !== FALSE)
			return "flv";
		if (strpos($codec, "h264") !== FALSE)
			return "mp4";
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

}
