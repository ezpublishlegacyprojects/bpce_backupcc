<?php
/**
 * Cronjob that will export all classes definition as a package file.
 * Will only update the package if classes have been modified since last version
 *
 * @version $Id$
 * @copyright 2009
 *
 * @todo add an ini file to get the parameters from
 */

$packagename = 'all_classes';
$packagesummary = 'Export of all classes ';
$packagecreatorname = 'webmaster';
$packagecreatoremail = 'nospam@ez.no';
$packageexportdir = '';


// create or open existing package
$package = eZPackage::fetch( $packagename );
if ( !$package )
{
    if ( !$isQuiet )
        $cli->output( 'Creating package ' . $packagename );
    $package = eZPackage::create( $packagename,
                                  array( 'summary' => $packagesummary ),
                                  false, false );
    $package->setAttribute( 'install_type', 'install' );
    $package->setAttribute( 'type', 'contentclass' );
    $package->appendChange( $packagecreatorname, $packagecreatoremail, 'Package created' );

    $lastsavedate = 0;
    $packageversion = '1.0';
}
else
{
    $lastsavedate = $package->attribute( 'release-timestamp' );
    $packageversion = $package->attribute( 'version-number' );
}

// find date of last modification of classes
$classes = eZContentClass::fetchList( eZContentClass::VERSION_STATUS_DEFINED, true );
$lastmodification = 0;
foreach( $classes as $class )
{
    if ( $class->attribute( 'modified' ) > $lastmodification )
    {
        $lastmodification = $class->attribute( 'modified' );
    }
    if ( $class->attribute( 'created' ) > $lastmodification )
    {
        $lastmodification = $class->attribute( 'created' );
    }
}

// if no class has changed since last release, quit
if ( $lastmodification <= $lastsavedate && $lastsavedate > 0 )
{
    if ( !$isQuiet )
        $cli->output( 'No class has been modified since last package save. Aborting' );
}
else
{
    if ( $lastsavedate > 0 )
    {
        if ( !$isQuiet )
            $cli->output( 'Updating package ' . $packagename );
    }

    // we always increment the minor version
    $major = substr( $packageversion , 0, strpos( $packageversion, '.' ) );
    $packageversion = substr( $packageversion , strpos( $packageversion, '.' ) + 1 );
    $packageversion = $major . '.' . ( $packageversion + 1 );
    // the release date is set to last date of modification of classes
    $package->setRelease( $packageversion, '1', $lastmodification );

    $handler = $package->packageHandler( 'ezcontentclass' );
    $classlist = array();
    foreach( $classes as $class )
    {
        if ( $class->attribute( 'modified' ) > $lastsavedate ||  $class->attribute( 'created' ) > $lastsavedate )
        {
            $classlist[] = $class->attribute( 'identifier' );
            $parameters = $handler->handleAddParameters( 'ezcontentclass', $package, $cli, array( $class->attribute( 'identifier' ) ) );
            $handler->add( 'ezcontentclass', $package, $cli, $parameters );
        }
    }
    $classlist = implode( ', ', $classlist );
    $package->appendChange( $packagecreatorname, $packagecreatoremail, 'Added classes ' . $classlist );
    $package->store();
    //$cli->output( 'Added classes ' . $classlist );

    $outputfilename = $packageexportdir . eZSys::fileSeparator() . $package->exportName();
    $package->exportToArchive( $outputfilename );
    if ( !$isQuiet )
        $cli->output( 'Saved file ' . $outputfilename );
}

?>