#!/usr/bin/env php
<?php
/**
 * Build a standalone executable app.
 *
 * If creating an archive is disabled by phar.readonly, unset it in php.ini or run
 *   php -d phar.readonly=off src/bin/build-phar.php
 **/

$executable = 'opensim-rest-cli';
$pharFile   = $executable . '.phar';
$baseDir    = dirname( dirname( __DIR__ ) );

// Create a new Phar archive
$phar = new Phar( $pharFile, 0, $pharFile );

// Start buffering for Phar creation
$phar->startBuffering();

// Add files to the Phar archive
$phar->buildFromDirectory( $baseDir, '/\.php$/' );

// Set the default stub for the Phar archive
$defaultStub = $phar->createDefaultStub( 'opensim-rest-cli.php' );

// Customize the stub if needed
$stub = "#!/usr/bin/env php\n" . $defaultStub;

// Set the custom stub
$phar->setStub( $stub );

// Compress the Phar file using gzip compression
$phar->compressFiles( Phar::GZ );

// Stop buffering and write the Phar archive to disk
$phar->stopBuffering();

// Rename the Phar file to remove the extension
rename( $pharFile, $executable );

// Set executable permissions on the renamed file
chmod( $executable, 0755 );

echo "Executable file created: $executable\n";
