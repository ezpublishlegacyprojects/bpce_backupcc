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
// find date of last modification of classes
$classes = eZContentClass::fetchList( eZContentClass::VERSION_STATUS_DEFINED, true );
$lastmodification = 0;
foreach( $classes as $class )
{
	$cli->output( $class->attribute( 'identifier' ).":" );
	$packagename = 'classes_'.$class->attribute( 'identifier' );
	$packagesummary = 'Export class '.$class->attribute( 'identifier' );
	$packagecreatorname = 'Administrator';
	$packagecreatoremail = 'nospam@ez.no';
	$packageexportdir = '';
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
		$package->setAttribute( 'documents', '' );
		$package->setAttribute( 'maintainers', $packagecreatorname );
		$package->setAttribute( 'licence', 'GPL' );
		$package->setAttribute( 'state', 'Contenu actif' );
		$package->setAttribute( 'description', '' );
		$package->appendChange( $packagecreatorname, $packagecreatoremail, 'Package created' );
		$lastsavedate = 0;
		$packageversion = '1.0';
	}
	else
	{
		$lastsavedate = $package->attribute( 'release-timestamp' );
		$packageversion = $package->attribute( 'version-number' );
	}

	if ( $class->attribute( 'modified' ) > $lastmodification )
	{
		$lastmodification = $class->attribute( 'modified' );
	}
	if ( $class->attribute( 'created' ) > $lastmodification )
	{
		$lastmodification = $class->attribute( 'created' );
	}

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
		//$classlist = array();
		if ( $class->attribute( 'created' ) > $lastsavedate )
		{
				//$classlist[] = $class->attribute( 'identifier' );
				$parameters = $handler->handleAddParameters( 'ezcontentclass', $package, $cli, array( $class->attribute( 'identifier' ) ) );
				$handler->add( 'ezcontentclass', $package, $cli, $parameters );
				$package->appendChange( $packagecreatorname, $packagecreatoremail, 'Class was created');
		}
		if ( $class->attribute( 'modified' ) > $lastsavedate && $class->attribute( 'modified' )<>$class->attribute( 'created' ))		{
			//$classlist[] = $class->attribute( 'identifier' );
			$parameters = $handler->handleAddParameters( 'ezcontentclass', $package, $cli, array( $class->attribute( 'identifier' ) ) );
			$handler->add( 'ezcontentclass', $package, $cli, $parameters );
			$package->appendChange( $packagecreatorname, $packagecreatoremail, 'Class was modified');
		}

		$package->store();
		$outputfilename = $packageexportdir . eZSys::fileSeparator() . $package->exportName();
		$package->exportToArchive( $outputfilename );
		if ( !$isQuiet )
			$cli->output( 'Saved file ' . $outputfilename );
	}
}
?>