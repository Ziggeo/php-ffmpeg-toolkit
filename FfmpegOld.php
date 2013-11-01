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

