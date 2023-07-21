<?php

/**
 * PHP Library for Project Version Management
 *
 * This library provides tasks for automating versioning in PHP projects. It
 * allows you to increment the version based on different levels (major, minor,
 * patch, dev, beta, rc), and update version references in various files such as
 * PHP files, README.md, package.json, and readme.txt.
 *
 * Usage:
 *   php ./RoboFile.php bumb:version [level]
 *
 * To use with composer, add the following script to your composer.json:
 *
 *   "scripts": {
 *     "bump-version": "robo [--load-from=path/to/RoboFile.php] bump:version"
 *    }
 *
 * --load-from is only needed if RoboFile.php is not in the root path of
 * your project.
 *
 * Level can be major, minor, patch, rc, beta or dev
 *
 * @package php-bump-library
 * @version 1.0.2
 * @author Olivier van Helden
 * @link https://magiiic.com/
 *
 * Donate to support this project
 * @link https://magiiic.com/donation/projet/?project=project-donations-wc
 */

use Robo\Tasks;
use Symfony\Component\Finder\Finder;

/**
 * Class RoboFile
 *
 * This class provides tasks for bumping the version of a project.
 */
class RoboFile extends \Robo\Tasks {

	protected $rootpath;
	protected $package;

	/**
	 * RoboFile constructor.
	 */
	function __construct() {
		$this->setRootPath();
	}

	/**
	 * Bumps the version based on the specified level (major, minor, patch, dev, beta, rc).
	 *
	 * @param string $level The level to increment (major, minor, patch, dev, beta, rc). Default: patch
	 */
	public function bumpVersion( $level = 'patch' ) {
		$this->say( 'Package  ' . $this->package );
		$this->say( 'Rootpath ' . $this->rootpath );

		$versionFile = $this->rootpath . '/.version';

		$currentVersion = file_exists( $versionFile ) ? file_get_contents( $versionFile ) : '0.0.0';
		$nextVersion    = $this->incrementVersion( $currentVersion, $level );
		file_put_contents( $versionFile, $nextVersion );

		$pattern = '\d+\.\d+\.\d+([\.-][a-zA-Z0-9]+)*';

		$mainPhpFile = $this->findMainPhpFile();
		$this->replaceInFile( $mainPhpFile, '/(\*\s*Version:\s*)' . $pattern . '/', "\${1}$nextVersion" );
		$this->replaceInFile(
			$mainPhpFile,
			'/define\(\s*\'([A-Za-z0-9_]+_VERSION)\',\s*\'(' . $pattern . ')\'\s*\);/',
			"define( '$1', '$nextVersion' );"
		);
		$dotNextVersion = preg_replace('/-/', '.', $nextVersion);

		$this->replaceInFile($this->rootpath . '/package.json', '/"version":\s*"' . $pattern . '"\s*,/', '"version": "' . $nextVersion . '",');
		$this->replaceInFile($this->rootpath . '/README.md', '~Version ' . $pattern . '~', "Version $nextVersion");
		$this->replaceInFile($this->rootpath . '/README.md', '~Version/' . $pattern . '/~', "Version/$nextVersion/");
		$this->replaceInFile($this->rootpath . '/README.md', '/Version-' . $pattern . '-/', "Version-$dotNextVersion-");

		if (!preg_match('/(dev|beta|rc)/i', $nextVersion)) {
			$this->replaceInFile($this->rootpath . '/readme.txt', '/Stable tag:\s+' . $pattern . '/', "Stable tag: $nextVersion");
			$this->replaceInFile($this->rootpath . '/README.md', '/Stable ' . $pattern . '/', "Stable $nextVersion");
			$this->replaceInFile($this->rootpath . '/README.md', '~Stable/' . $pattern . '/~', "Stable/$nextVersion/");
			$this->replaceInFile($this->rootpath . '/README.md', '/Stable-' . $pattern . '-/', "Stable-$dotNextVersion-");
		}

		$phpFiles = $this->getPhpFilesWithPackage($this->package); // Replace 'project-donations-wc' with your package name
		$this->replaceInFiles($phpFiles, '/@version\s+' . $pattern . '/', "@version $nextVersion");

		$this->say( "Version bumped to: $nextVersion" );
	}

	/**
	 * Increments the version based on the specified level (major, minor, patch, dev, beta, rc).
	 *
	 * @param string $version The current version.
	 * @param string $level The level to increment (major, minor, patch, dev, beta, rc).
	 * @return string The incremented version.
	 */
	private function incrementVersion( $version, $level ) {
		$parts       = explode( '.', $version );
		$major       = (int) $parts[0];
		$minor       = (int) $parts[1];
		$patch_parts = explode( '-', $parts[2] );
		$patch       = $patch_parts[0];
		$cur_suffix  = isset( $patch_parts[1] ) ? $patch_parts[1] : null;
		$suffix_num  = isset( $patch_parts[2] ) ? (int) $patch_parts[2] : 1;
		$suffix      = '';

		switch ( $level ) {
			case 'major':
				$major++;
				$minor = 0;
				$patch = 0;
				break;
			case 'minor':
				$minor++;
				$patch = 0;
				break;
			case 'patch':
				if ( ! in_array( $cur_suffix, array( 'dev', 'beta', 'rc' ) ) ) {
					error_log( '$cur_suffix ' . $cur_suffix );
					$patch++;
				}
				break;
			case 'dev':
				$suffix = "-$level";
				if ( $cur_suffix === $level ) {
					$suffix = "-$level-" . ( $suffix_num + 1 );
				} else {
					$patch++;
				}
				break;
			case 'beta':
				$suffix = "-$level";
				if ( $cur_suffix === $level ) {
					$suffix = "-$level-" . ( $suffix_num + 1 );
				} elseif ( $cur_suffix !== 'dev' ) {
					$patch++;
				}
				break;
			case 'rc':
				$suffix = "-$level";
				if ( $cur_suffix === $level ) {
					$suffix = "-$level-" . ( $suffix_num + 1 );
				} elseif ( ! in_array( $cur_suffix, array( 'dev', 'beta' ) ) ) {
					$patch++;
				}
				break;
			default:
				break;
		}

		return "$major.$minor.$patch$suffix";
	}

	/**
	 * Replaces the given pattern with the replacement string in the specified files.
	 *
	 * @param array  $files The files to perform the replacement on.
	 * @param string $pattern The pattern to search for.
	 * @param string $replacement The replacement string.
	 */
	private function replaceInFiles( $files, $pattern, $replacement ) {
		foreach ( $files as $file ) {
			$this->replaceInFile( $file, $pattern, $replacement );
		}
	}

	/**
	 * Replaces the given pattern with the replacement string in the specified file.
	 *
	 * @param string $file The file to perform the replacement on.
	 * @param string $pattern The pattern to search for.
	 * @param string $replacement The replacement string.
	 */
	private function replaceInFile( $file, $pattern, $replacement ) {
		if (empty($file) || !file_exists($file)) {
			return;
		}

		$this->say( 'Updating ' . realpath( $file ) );
		// return; // DEBUG: don't apply changes
		$contents = file_get_contents( $file );
		$contents = preg_replace( $pattern, $replacement, $contents );
		file_put_contents( $file, $contents );
	}

	/**
	 * Returns an array of PHP file paths with the specified @package value in the docblocks.
	 *
	 * @param string $package The package value to match.
	 * @return array The PHP file paths.
	 */
	private function getPhpFilesWithPackage( $package ) {
		$finder = new Finder();
		$finder
				->files()
				->in( $this->rootpath )
				->name( '*.php' )
				->exclude( array( 'vendor', 'node_modules' ) )
				->ignoreVCS( true )
				->ignoreDotFiles( true )
				->contains( "@package $package" );

		// Rest of your code...
		$phpFiles = array();

		foreach ( $finder as $file ) {
			$phpFiles[] = $file->getRealPath();
		}

		return $phpFiles;
	}

	/**
	 * Finds the main PHP file in the root folder based on the docblock.
	 *
	 * @return string|null The path of the main PHP file, or null if not found.
	 */
	private function findMainPhpFile() {
		$finder = new Finder();
		$finder
			->files()
			->in( $this->rootpath )
			->name( '*.php' )
			->ignoreVCS( true )
			->ignoreDotFiles( true )
			->depth( '== 0' )
			->contains( '/\*\s*Plugin Name\s*:/' );

		foreach ( $finder as $file ) {
			return $file->getRealPath();
		}

		return null;
	}

	/**
	 * Retrieves the root path of the project.
	 *
	 * @return string|null The root path of the project, or null if not found.
	 */
	private function setRootPath() {
			$gitRoot = exec( 'git rev-parse --show-toplevel' );
			$this->rootpath = $gitRoot !== false ? realpath( $gitRoot ) : null;

			if ( $this->rootpath === null ) {
				$this->say( 'Failed to determine the project root path. Make sure the project is inside a Git repository.' );
				die();
			}

			$composerFile = $this->rootpath . '/composer.json';
			$composerData = file_get_contents($composerFile);
			$composerJson = json_decode($composerData, true);
			$this->package = basename($composerJson['name']);
	}
}
