<?php
//
// iTop module definition file
//

SetupWebPage::AddModule(
	__FILE__, // Path to the current file, all other file names are relative to the directory containing this file
	'task-exchangecalendar/1.0.0',
	array(
		// Identification
		//
		'label' => 'Exchange Calendar integration with WorkOrder',
		'category' => 'business',

		// Setup
		//
		'dependencies' => array(
			'itop-config-mgmt/2.0.0',
			//'itop-endusers-devices/2.0.0'			
		),
		'mandatory' => false,
		'visible' => true,

		// Components
		//
		'datamodel' => array(
			'model.task-exchangecalendar.php',
                        'main.task-exchangecalendar.php',
		),
		'webservice' => array(
			
		),
		'data.struct' => array(
			// add your 'structure' definition XML files here,
		),
		'data.sample' => array(
			// add your sample data XML files here,
		),
		
		// Documentation
		//
		'doc.manual_setup' => '', // hyperlink to manual setup documentation, if any
		'doc.more_information' => '', // hyperlink to more information, if any 

		// Default settings
		//
		'settings' => array(
                            // Module specific settings go here, if any
                            'ews_host'     => 'webmail.ch-eureseine.fr',
                            'ews_username' => 'itopcalendar',
                            'ews_password' => 'password',

                            /**
                             *  Possible Value : 
                             *  Exchange2007
                             *  Exchange2007_SP1
                             *  Exchange2009
                             *  Exchange2010
                             *  Exchange2010_SP2
                             *  Exchange2010_SP1
                             *  Exchange2010_SP2
                             *  Exchange2013
                             *  Exchange2013_SP1
                             *  Exchange2016
                             */
                            'ews_version'  => 'Exchange2010_SP2',  // We have : 2010 SP3 (Exchange 143 Build 1234)
                            'ews_timezone' => 'W. Europe Standard Time', 
		),
	)
);


?>
