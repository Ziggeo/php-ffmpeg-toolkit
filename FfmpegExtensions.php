<?php

Class FFMpegOldDriver extends FFMpeg\Driver\FFMpegDriver {
	
	private function replace($commands, $pattern, $replacement) {
		$i = 0;
		$n = count($commands);
		$j = 0;
		$m = count($pattern) - 1;
		while ($i < $n) {
			if ($pattern[$j] != NULL && $commands[$i] != $pattern[$j])
				$j = 0;
			elseif ($j < $m)
				++$j;
			else {
				$n += count($replacement) - count($pattern);
				array_splice($commands, $i-$m, count($pattern), $replacement);
				$j = 0;
			}
			$i++;
		}
		return $commands;
	}
	
    public function command($command, $bypassErrors = false, $listeners = null) {
    	$command = $this->replace($command, array("-profile:v", "baseline"), array(
			"-cmp", "256",
			"-partitions", "+parti4x4+parti8x8+partp4x4+partp8x8+partb8x8",
			"-me_method", "hex",
			"-flags2", "+mixed_refs",
			"-keyint_min", "25",
			"-qmin", "10",
			"-qmax", "51"		
		));
		$command = $this->replace($command, array("-flags", NULL), array("-flags", "+loop+mv4"));
		$command = $this->replace($command, array("-coder", NULL), array("-coder", "0"));
		$command = $this->replace($command, array("-refs", NULL), array("-refs", "5"));
		$command = $this->replace($command, array("-bf", NULL), array("-bf", "0"));

		$command = $this->replace($command, array("-b:v"), array("-b"));

		$command = $this->replace($command, array("-b:a"), array("-ab"));

		//echo join(" ", $command);

		return parent::command($command, $bypassErrors, $listeners);
    }		
	
}


Class FFMpegOld extends FFMpeg\FFMpeg {

    public static function create($configuration = array(), LoggerInterface $logger = null, FFMpeg\FFProbe $probe = null)
    {
        if (null === $probe) {
            $probe = FFMpeg\FFProbe::create($configuration, $logger, null);
        }

        return new static(FFMpegOldDriver::create($logger, $configuration), $probe);
    }
	
}


Class ExtraParamsDefaultVideo extends FFMpeg\Format\Video\DefaultVideo {

	private $extra_params = array();

    public function getExtraParams() {
        return $this->extra_params;
    }
	
	public function setExtraParams($value) {
		$this->extra_params = $value;
	}
	
    public function getAvailableAudioCodecs() {
        return array();
    }

    public function getAvailableVideoCodecs() {
        return array();
    }
	
	public function supportBFrames() {
        return false;
    }

}


Class X264Baseline extends FFMpeg\Format\Video\X264 {

    public function getExtraParams() {
       	return array("-profile:v", "baseline", "-f", "mp4");
    }	
	
}


Class WatermarkFilter implements FFMpeg\Filters\Video\VideoFilterInterface {
	
    private $priority;
	
	private $filename;
	
	private $scale_of_video;
	
	private $positionx;
	
	private $positiony;

    public function __construct($filename, $scale_of_video, $positionx, $positiony, $priority = 0) {
        $this->priority = $priority;
		$this->filename = $filename;
        $this->scale_of_video = $scale_of_video;
		$this->positionx = $positionx;
		$this->positiony = $positiony;
    }

    public function getPriority() {
        return $this->priority;
    }

    public function apply(FFMpeg\Media\Video $video, FFMpeg\Format\VideoInterface $format)
    {
        $originalWidth = $originalHeight = null;

        foreach ($video->getStreams() as $stream) {
            if ($stream->isVideo()) {
                if ($stream->has('width')) {
                    $originalWidth = $stream->get('width');
                }
                if ($stream->has('height')) {
                    $originalHeight = $stream->get('height');
                }
            }
        }
		
		$image = getimagesize($this->filename);
		$image_width = $image[0];
		$image_height = $image[1];
		
		$scale_width = $image_width;
		$scale_height = $image_height;
		
		$max_width = floor($originalWidth * $this->scale_of_video);
		$max_height = floor($originalHeight * $this->scale_of_video);
		
		if ($image_width > $max_width || $image_height > $max_height) {
			if ($image_width * $max_height > $image_height * $max_width) {
				$scale_width = $max_width;
				$scale_height = round($image_height * $max_width / $image_width);
			} else {
				$scale_height = $max_height;
				$scale_width = round($image_width * $max_height / $image_height);
			}
		}
		
		$posx = floor($this->positionx * ($originalWidth - $scale_width));
		$posy = floor($this->positiony * ($originalHeight - $scale_height));
		
        $commands = array(
        	"-vf",
        	'movie=' . $this->filename . ', scale=' . $scale_width . ":" . $scale_height . ' [wm];[in][wm] overlay=' . $posx . ':' . $posy . ' [out]'
		);

        return $commands;
    }	
}


Class DurationFilter implements FFMpeg\Filters\Video\VideoFilterInterface {
	
    private $priority;
	
	private $duration;

    public function __construct($duration, $priority = 0) {
        $this->priority = $priority;
		$this->duration = $duration;
    }

    public function getPriority() {
        return $this->priority;
    }

    public function apply(FFMpeg\Media\Video $video, FFMpeg\Format\VideoInterface $format) {
    	return array(
    		"-t",
    		$this->duration . ""
		);
    }	
}



Class RotationFilter implements FFMpeg\Filters\Video\VideoFilterInterface {
	
    private $priority;
	
	private $rotation;
	
	private $transpose;
	private $doubleflip;

    public function __construct($rotation, $priority = 0) {
        $this->priority = $priority;
		$this->rotation = $rotation; 
		$this->transpose = 0;
		$this->doubleflip = false;
		if ($this->rotation == 90)
			$this->transpose = 1;
		elseif ($this->rotation == 270)
			$this->transpose = 2;
		elseif ($this->rotation == 180)
			$this->doubleflip = true;
    }

    public function getPriority() {
        return $this->priority;
    }

    public function apply(FFMpeg\Media\Video $video, FFMpeg\Format\VideoInterface $format) {
    	$result = array();
		if ($this->transpose != 0) {
			$result[] = "-vf";
			$result[] = "transpose=" . $this->transpose;
		}
		if ($this->doubleflip) {
			$result[] = "-vf";
			$result[] = "hflip,vflip";
		}
		return $result;
    }
	
	public function getTranspose() {
	}
}
