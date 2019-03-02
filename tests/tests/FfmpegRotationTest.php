<?php

require_once(dirname(__FILE__) . "/../../vendor/autoload.php");
require_once(dirname(__FILE__) . "/../../FfmpegVideoTranscoding.php");
require_once(dirname(__FILE__) . "/../../FfmpegVideoFile.php");


$SETTINGS = array(
    "autorotate" => FALSE
);

$ASSETS = array(
    "NORMAL_VIDEO" => dirname(__FILE__) . "/../assets/video-640-360.mp4",
    "ROTATED_VIDEO" => dirname(__FILE__) . "/../assets/iphone_rotated.mov"
);

$TMPFILES = array(
    "TMP_IMAGE" => "/tmp/test-img.png",
    "TMP_VIDEO" => "/tmp/test-video.mp4"
);


class FfmpegRotationTest extends PHPUnit\Framework\TestCase {

    public function testReadRotation() {
        global $ASSETS, $SETTINGS;

        $normalVideoNoRotateAdd = new FfmpegVideoFile($ASSETS["NORMAL_VIDEO"], array_merge($SETTINGS, array(
            "rotation" => TRUE
        )));
        $this->assertEquals($normalVideoNoRotateAdd->getWidth(), 640);
        $this->assertEquals($normalVideoNoRotateAdd->getHeight(), 360);
        $this->assertEquals($normalVideoNoRotateAdd->getRotation(), 0);

        $normalVideoWithRotateAdd = new FfmpegVideoFile($ASSETS["NORMAL_VIDEO"], array_merge($SETTINGS, array(
            "rotation" => TRUE,
            "rotate_add" => 90
        )));
        $this->assertEquals($normalVideoWithRotateAdd->getWidth(), 360);
        $this->assertEquals($normalVideoWithRotateAdd->getHeight(), 640);
        $this->assertEquals($normalVideoWithRotateAdd->getRotation(), 90);

        $rotatedVideoNoRotateAdd = new FfmpegVideoFile($ASSETS["ROTATED_VIDEO"], array_merge($SETTINGS, array(
            "rotation" => TRUE
        )));
        $this->assertEquals($rotatedVideoNoRotateAdd->getWidth(), 320);
        $this->assertEquals($rotatedVideoNoRotateAdd->getHeight(), 568);
        $this->assertEquals($rotatedVideoNoRotateAdd->getRotation(), 90);

        $rotatedVideoWithRotateAdd = new FfmpegVideoFile($ASSETS["ROTATED_VIDEO"], array_merge($SETTINGS, array(
            "rotation" => TRUE,
            "rotate_add" => 90
        )));
        $this->assertEquals($rotatedVideoWithRotateAdd->getWidth(), 568);
        $this->assertEquals($rotatedVideoWithRotateAdd->getHeight(), 320);
        $this->assertEquals($rotatedVideoWithRotateAdd->getRotation(), 180);
    }

    public function testWriteRotation() {
        global $ASSETS, $SETTINGS, $TMPFILES;

        $normalVideoNoRotateAdd = new FfmpegVideoFile($ASSETS["NORMAL_VIDEO"], array_merge($SETTINGS, array(
            "rotation" => TRUE
        )));
        @unlink($TMPFILES["TMP_IMAGE"]);
        $normalVideoNoRotateAdd->saveImageByPercentage($TMPFILES["TMP_IMAGE"]);
        $readImg = new FfmpegVideoFile($TMPFILES["TMP_IMAGE"]);
        unlink($TMPFILES["TMP_IMAGE"]);
        $this->assertEquals($readImg->getWidth(), 640);
        $this->assertEquals($readImg->getHeight(), 360);
        $this->assertEquals($readImg->getRotation(), 0);

        $normalVideoWithRotateAdd = new FfmpegVideoFile($ASSETS["NORMAL_VIDEO"], array_merge($SETTINGS, array(
            "rotation" => TRUE,
            "rotate_add" => 90
        )));
        @unlink($TMPFILES["TMP_IMAGE"]);
        $normalVideoWithRotateAdd->saveImageByPercentage($TMPFILES["TMP_IMAGE"]);
        $readImg = new FfmpegVideoFile($TMPFILES["TMP_IMAGE"]);
        unlink($TMPFILES["TMP_IMAGE"]);
        $this->assertEquals($readImg->getWidth(), 360);
        $this->assertEquals($readImg->getHeight(), 640);
        $this->assertEquals($readImg->getRotation(), 0);

        $rotatedVideoNoRotateAdd = new FfmpegVideoFile($ASSETS["ROTATED_VIDEO"], array_merge($SETTINGS, array(
            "rotation" => TRUE
        )));
        @unlink($TMPFILES["TMP_IMAGE"]);
        $rotatedVideoNoRotateAdd->saveImageByPercentage($TMPFILES["TMP_IMAGE"]);
        $readImg = new FfmpegVideoFile($TMPFILES["TMP_IMAGE"]);
        unlink($TMPFILES["TMP_IMAGE"]);
        $this->assertEquals($readImg->getWidth(), 320);
        $this->assertEquals($readImg->getHeight(), 568);
        $this->assertEquals($readImg->getRotation(), 0);

        $rotatedVideoWithRotateAdd = new FfmpegVideoFile($ASSETS["ROTATED_VIDEO"], array_merge($SETTINGS, array(
            "rotation" => TRUE,
            "rotate_add" => 90
        )));
        @unlink($TMPFILES["TMP_IMAGE"]);
        $rotatedVideoWithRotateAdd->saveImageByPercentage($TMPFILES["TMP_IMAGE"]);
        $readImg = new FfmpegVideoFile($TMPFILES["TMP_IMAGE"]);
        unlink($TMPFILES["TMP_IMAGE"]);
        $this->assertEquals($readImg->getWidth(), 568);
        $this->assertEquals($readImg->getHeight(), 320);
        $this->assertEquals($readImg->getRotation(), 0);
    }

    public function testTranscodingRotation() {
        global $ASSETS, $SETTINGS, $TMPFILES;

        @unlink($TMPFILES["TMP_VIDEO"]);
        FfmpegVideoTranscoding::transcodeGracefully($ASSETS["NORMAL_VIDEO"], array_merge($SETTINGS, array(
            "rotate" => TRUE,
            "resizefit" => "fit",
            "target" => $TMPFILES["TMP_VIDEO"],
        )));
        $readVid = new FfmpegVideoFile($TMPFILES["TMP_VIDEO"]);
        unlink($TMPFILES["TMP_VIDEO"]);
        $this->assertEquals($readVid->getWidth(), 640);
        $this->assertEquals($readVid->getHeight(), 360);
        $this->assertEquals($readVid->getRotation(), 0);

        @unlink($TMPFILES["TMP_VIDEO"]);
        FfmpegVideoTranscoding::transcodeGracefully($ASSETS["NORMAL_VIDEO"], array_merge($SETTINGS, array(
            "rotate" => TRUE,
            "resizefit" => "fit",
            "rotate_add" => 90,
            "target" => $TMPFILES["TMP_VIDEO"],
        )));
        $readVid = new FfmpegVideoFile($TMPFILES["TMP_VIDEO"]);
        unlink($TMPFILES["TMP_VIDEO"]);
        $this->assertEquals($readVid->getWidth(), 360);
        $this->assertEquals($readVid->getHeight(), 640);
        $this->assertEquals($readVid->getRotation(), 0);

        @unlink($TMPFILES["TMP_VIDEO"]);
        FfmpegVideoTranscoding::transcodeGracefully($ASSETS["ROTATED_VIDEO"], array_merge($SETTINGS, array(
            "rotate" => TRUE,
            "resizefit" => "fit",
            "target" => $TMPFILES["TMP_VIDEO"],
        )));
        $readVid = new FfmpegVideoFile($TMPFILES["TMP_VIDEO"]);
        unlink($TMPFILES["TMP_VIDEO"]);
        $this->assertEquals($readVid->getWidth(), 320);
        $this->assertEquals($readVid->getHeight(), 568);
        $this->assertEquals($readVid->getRotation(), 0);

        @unlink($TMPFILES["TMP_VIDEO"]);
        FfmpegVideoTranscoding::transcodeGracefully($ASSETS["ROTATED_VIDEO"], array_merge($SETTINGS, array(
            "rotate" => TRUE,
            "resizefit" => "fit",
            "rotate_add" => 90,
            "target" => $TMPFILES["TMP_VIDEO"],
        )));
        $readVid = new FfmpegVideoFile($TMPFILES["TMP_VIDEO"]);
        unlink($TMPFILES["TMP_VIDEO"]);
        $this->assertEquals($readVid->getWidth(), 568);
        $this->assertEquals($readVid->getHeight(), 320);
        $this->assertEquals($readVid->getRotation(), 0);
    }

}
