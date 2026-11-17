<?php

namespace App\Services;

use RuntimeException;

/** Thrown when a requested archive exceeds the file-count or size limit. */
class ArchiveLimitException extends RuntimeException
{
}
