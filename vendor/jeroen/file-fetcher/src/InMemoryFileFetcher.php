<?php

namespace FileFetcher;

use InvalidArgumentException;

/**
 * @since 3.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class InMemoryFileFetcher implements FileFetcher {

	/**
	 * @param string[] $files
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $files ) {
		foreach ( $files as $url => $fileContents ) {
			if ( !is_string( $url ) || !is_string( $fileContents ) ) {
				throw new InvalidArgumentException( 'Both file url and file contents need to be of type string' );
			}
		}

		$this->files = $files;
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
		if ( array_key_exists( $fileUrl, $this->files ) ) {
			return $this->files[$fileUrl];
		}

		throw new FileFetchingException( $fileUrl );
	}

}
