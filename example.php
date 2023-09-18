<?php

require_once __DIR__ . '/src/EventType.php';
require_once __DIR__ . '/src/Watcher.php';


use Maximal\FileSystem\Watch\EventType;
use Maximal\FileSystem\Watch\Watcher;


Watcher::create($argv[1] ?? __DIR__)
	// Poll the file system every second (1’000’000 microseconds)
	->setPollInterval(1_000_000)
	->onFileAdded(static function (string $path) {
		echo 'File added: ', $path, PHP_EOL;
	})
	->onFileChanged(static function (string $path, array $stat) {
		echo 'File changed: ', $path, '    @ ';
		echo (new DateTime('@' . $stat['mtime']))->format('c'), PHP_EOL;
	})
	->onFileDeleted(static function (string $path) {
		echo 'File deleted: ', $path, PHP_EOL;
	})
	->onDirectoryAdded(static function (string $path) {
		echo 'Directory added: ', $path, PHP_EOL;
	})
	->onDirectoryChanged(static function (string $path, array $stat) {
		echo 'Directory changed: ', $path, '    @ ';
		echo (new DateTime('@' . $stat['mtime']))->format('c'), PHP_EOL;
	})
	->onDirectoryDeleted(static function (string $path) {
		echo 'Directory deleted: ', $path, PHP_EOL;
	})
	// General event handler
	->onAnyEvent(static function (EventType $type, string $path, array $stat) {
		echo 'Event `', $type->value, '`: ', $path, '    @ ';
		echo (new DateTime('@' . $stat['mtime']))->format('c'), PHP_EOL;
	})
	->start();
