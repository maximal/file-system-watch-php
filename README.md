# File system watcher for PHP

This library is a simple poll-based synchronous file system watcher for PHP with no dependencies.


## Usage Example

```php
use Maximal\FileSystem\Watch\EventType;
use Maximal\FileSystem\Watch\Watcher;

Watcher::create(__DIR__)
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
```

Run it:
```shell
php  example.php  <directory>
```


## Contact the author
* Website: https://maximals.ru (Russian)
* Twitter: https://twitter.com/almaximal
* Telegram: https://t.me/maximal
* Sijeko Company: https://sijeko.ru (web, mobile, desktop applications development and graphic design)
* Personal GitHub: https://github.com/maximal
* Company’s GitHub: https://github.com/sijeko
