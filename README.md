php-ffmpeg-toolkit
==================

A php library for running ffmpeg. It requires and is based on two other libraries that drive ffmpeg via php.
The purpose of this library is to provide a high-level easy-to-use interface to ffmpeg.

The two libraries being used are:
- https://github.com/PHP-FFMpeg/PHP-FFMpeg
- https://github.com/char0n/ffmpeg-php

Although both libraries look similar, they don't perform the same tasks, so we really need both.

## Requirements

- PHP 7.4 or higher
- FFmpeg installed on your system

## Installation

1. Clone the repository
2. Run `composer install` to install dependencies
3. Include the php files you need or use Composer's autoloader

## PHP 7.4 Upgrade

This library has been upgraded to work with PHP 7.4. The following changes were made:

1. Updated the minimum PHP requirement to 7.4
2. Updated dependencies:
   - codescale/ffmpeg-php from 2.* to 3.*
   - php-ffmpeg/php-ffmpeg from 0.11.0 to 0.16
3. Updated code to work with the new versions of the libraries:
   - Updated namespace imports for FFmpegMovie class
   - Updated rotation handling to work with the new provider classes
   - Updated tests to accommodate changes in behavior

If you encounter any issues with the PHP 7.4 upgrade, please report them in the issue tracker.
