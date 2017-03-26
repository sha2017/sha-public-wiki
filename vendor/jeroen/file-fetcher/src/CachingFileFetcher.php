<?php

namespace FileFetcher;

use SimpleCache\Cache\Cache;

/**
 * Decorator for FileFetcher objects that adds caching capabilities.
 *
 * @since 3.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CachingFileFetcher implements FileFetcher {

	private $fileFetcher;
	private $cache;

	public function __construct( FileFetcher $fileFetcher, Cache $cache ) {
		$this->fileFetcher = $fileFetcher;
		$this->cache = $cache;
	}

	/**
	 * @see FileFetcher::fetchFile
	 *
	 * @param string $fileUrl
	 *
	 * @return string
	 * @throws FileFetchingException
	 */
	public function fetchFile( $fileUrl ) {
		$fileContents = $this->cache->get( $fileUrl );

		if ( $fileContents === null ) {
			return $this->retrieveAndCacheFile( $fileUrl );
		}

		return $fileContents;
	}

	private function retrieveAndCacheFile( $fileUrl ) {
		$fileContents = $this->fileFetcher->fetchFile( $fileUrl );

		$this->cache->set( $fileUrl, $fileContents );

		return $fileContents;
	}

}
