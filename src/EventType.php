<?php

namespace Maximal\FileSystem\Watch;

enum EventType: string
{
	case FileAdded = 'file_added';
	case FileChanged = 'file_changed';
	case FileDeleted = 'file_deleted';
	case DirectoryAdded = 'directory_added';
	case DirectoryChanged = 'directory_changed';
	case DirectoryDeleted = 'directory_deleted';
}
