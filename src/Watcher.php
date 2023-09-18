<?php

namespace Maximal\FileSystem\Watch;

use Closure;
use RuntimeException;

/**
 * File system changes watcher.
 *
 * Usage example:
 * ```php
 * Watcher::create(__DIR__)
 *     // Poll the file system every second (1’000’000 microseconds)
 *     ->setPollInterval(1_000_000)
 *     ->onFileAdded(static function (string $path) {
 *         echo 'File added: ', $path, PHP_EOL;
 *     })
 *     ->onFileChanged(static function (string $path, array $stat) {
 *         echo 'File changed: ', $path, '    @ ';
 *         echo (new DateTime('@' . $stat['mtime']))->format('c'), PHP_EOL;
 *     })
 *     ->onFileDeleted(static function (string $path) {
 *         echo 'File deleted: ', $path, PHP_EOL;
 *     })
 *     ->onDirectoryAdded(static function (string $path) {
 *         echo 'Directory added: ', $path, PHP_EOL;
 *     })
 *     ->onDirectoryChanged(static function (string $path, array $stat) {
 *         echo 'Directory changed: ', $path, '    @ ';
 *         echo (new DateTime('@' . $stat['mtime']))->format('c'), PHP_EOL;
 *     })
 *     ->onDirectoryDeleted(static function (string $path) {
 *         echo 'Directory deleted: ', $path, PHP_EOL;
 *     })
 *     // General event handler
 *     ->onAnyEvent(static function (EventType $type, string $path, array $stat) {
 *         echo 'Event ', $type->value, ': ', $path, '    @ ';
 *         echo (new DateTime('@' . $stat['mtime']))->format('c'), PHP_EOL;
 *     })
 *     ->start();
 * ```
 *
 * @author MaximAL
 * @since 2023-09-18
 */
class Watcher
{
	private string $directory;
	private int $pollInterval = 1_000_000;
	private array $files = [];

	private ?Closure $onFileAddedCallback = null;
	private ?Closure $onFileChangedCallback = null;
	private ?Closure $onFileDeletedCallback = null;
	private ?Closure $onDirectoryAddedCallback = null;
	private ?Closure $onDirectoryChangedCallback = null;
	private ?Closure $onDirectoryDeletedCallback = null;
	private ?Closure $onAnyEventCallback = null;

	private function __construct(string $directory)
	{
		$this->directory = $directory;
	}

	public static function create(string $directory): self
	{
		return new self($directory);
	}

	public function setPollInterval(int $microseconds): self
	{
		$this->pollInterval = $microseconds;
		return $this;
	}

	public function onFileAdded(callable $callback): self
	{
		$this->onFileAddedCallback = $callback;
		return $this;
	}

	public function onFileChanged(callable $callback): self
	{
		$this->onFileChangedCallback = $callback;
		return $this;
	}

	public function onFileDeleted(callable $callback): self
	{
		$this->onFileDeletedCallback = $callback;
		return $this;
	}

	public function onDirectoryAdded(callable $callback): self
	{
		$this->onDirectoryAddedCallback = $callback;
		return $this;
	}

	public function onDirectoryChanged(callable $callback): self
	{
		$this->onDirectoryChangedCallback = $callback;
		return $this;
	}

	public function onDirectoryDeleted(callable $callback): self
	{
		$this->onDirectoryDeletedCallback = $callback;
		return $this;
	}

	public function onAnyEvent(callable $callback): self
	{
		$this->onAnyEventCallback = $callback;
		return $this;
	}

	public function start(): void
	{
		$this->processDirectory($this->directory);
		while (true) {
			$prevFiles = $this->files;
			$this->files = [];
			usleep($this->pollInterval);
			$this->processDirectory($this->directory);
			foreach ($this->files as $file => $stat) {
				if (!isset($prevFiles[$file])) {
					if ($stat['is_dir']) {
						$this->callIfSet($this->onDirectoryAddedCallback, $file, $stat);
						$this->callAnyIfSet(EventType::DirectoryAdded, $file, $stat);
					} else {
						$this->callIfSet($this->onFileAddedCallback, $file, $stat);
						$this->callAnyIfSet(EventType::FileAdded, $file, $stat);
					}
					continue;
				}
				if ($prevFiles[$file]['mtime'] !== $stat['mtime']) {
					if ($stat['is_dir']) {
						$this->callIfSet($this->onDirectoryChangedCallback, $file, $stat);
						$this->callAnyIfSet(EventType::DirectoryChanged, $file, $stat);
					} else {
						$this->callIfSet($this->onFileChangedCallback, $file, $stat);
						$this->callAnyIfSet(EventType::FileChanged, $file, $stat);
					}
				}
			}
			foreach ($prevFiles as $file => $stat) {
				if (!isset($this->files[$file])) {
					if ($stat['is_dir']) {
						$this->callIfSet($this->onDirectoryDeletedCallback, $file, $stat);
						$this->callAnyIfSet(EventType::DirectoryDeleted, $file, $stat);
					} else {
						$this->callIfSet($this->onFileDeletedCallback, $file, $stat);
						$this->callAnyIfSet(EventType::FileDeleted, $file, $stat);
					}
				}
			}
			usleep($this->pollInterval);
		}
	}

	private function callIfSet(?callable $callable, string $path, array $stat): void
	{
		if ($callable) {
			$callable($path, $stat);
		}
	}

	private function callAnyIfSet(EventType $type, string $path, array $stat): void
	{
		$callable = $this->onAnyEventCallback;
		if ($callable) {
			$callable($type, $path, $stat);
		}
	}

	private function processDirectory(string $path, $depth = 0): void
	{
		$dir = @opendir($path);
		if (!$dir) {
			throw new RuntimeException('Failed to open directory: ' . $path);
		}

		while (($file = readdir($dir)) !== false) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			$filePath = $path . DIRECTORY_SEPARATOR . $file;
			$handle = fopen($filePath, 'rb');
			if ($handle === false) {
				continue;
			}

			$stat = fstat($handle);
			$isDir = ($stat['mode'] >> 12 & 004) === 004;
			$isLink = ($stat['mode'] >> 12 & 012) === 012;
			fclose($handle);
			$this->files[$filePath] = [
				'is_dir' => $isDir,
				'is_link' => $isLink,
				'is_file' => !$isDir && !$isLink,
				'depth' => $depth,
				'mode' => $stat['mode'],
				'mtime' => $stat['mtime'],
				'atime' => $stat['atime'],
				'size' => $stat['size'],
			];
			//echo str_repeat("\t", $depth);
			//echo "$file : " . ($isDir ? 'dir' : ($isLink ? 'link' : 'file')), ' ', $mTime, PHP_EOL;
			//var_dump($stat, $filePath);
			if ($isDir) {
				$this->processDirectory($filePath, $depth + 1);
			}
		}
		closedir($dir);
	}
}
